<?php
header("Content-Type: application/json; charset=utf-8");
require "db.php";


$nombre     = trim($_POST["nombre"] ?? "");
$apellido   = trim($_POST["apellido"] ?? "");
$edad       = (int) ($_POST["edad"] ?? 0);
$celular    = trim($_POST["celular"] ?? "");
$email      = trim($_POST["email"] ?? "");
$instagram  = trim($_POST["instagram"] ?? "");
$estudios   = trim($_POST["estudios"] ?? "");
$password   = $_POST["password"] ?? "";

$estudiosValidos = ["primario", "secundario", "terciario", "universitario"];
$dominiosPermitidos = [
    "gmail.com", "hotmail.com", "hotmail.com.ar",
    "outlook.com", "outlook.com.ar", "outlook.es",
    "yahoo.com", "yahoo.com.ar"
];
$soloLetras = '/^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ\s]+$/u';


$errores = [];

if ($nombre === "" || !preg_match($soloLetras, $nombre) || strlen($nombre) > 100) {
    $errores[] = "Nombre inválido";
}
if ($apellido === "" || !preg_match($soloLetras, $apellido) || strlen($apellido) > 100) {
    $errores[] = "Apellido inválido";
}
if ($edad < 16 || $edad > 99) $errores[] = "La edad tiene que estar entre 16 y 99 años";

if ($celular === "" || !preg_match('/^[0-9\s-]+$/', $celular)) {
    $errores[] = "Número de celular inválido";
} else {
    $soloDigitos = preg_replace('/[\s-]/', '', $celular);
    if (strlen($soloDigitos) < 8 || strlen($soloDigitos) > 15) {
        $errores[] = "Número de celular inválido";
    }
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errores[] = "Email inválido";
} else {
    $dominio = strtolower(substr(strrchr($email, "@"), 1));
    if (!in_array($dominio, $dominiosPermitidos, true)) {
        $errores[] = "Usá un email de Gmail, Hotmail, Outlook o Yahoo";
    }
}

if (!in_array($estudios, $estudiosValidos, true)) $errores[] = "Estudios inválidos";
if (strlen($password) < 6) $errores[] = "La contraseña tiene que tener al menos 6 caracteres";

if (!empty($errores)) {
    http_response_code(400);
    echo json_encode(["error" => implode(". ", $errores)]);
    exit;
}


$stmt = $pdo->prepare("SELECT COUNT(*) FROM trabajadores WHERE email = :email");
$stmt->execute([":email" => $email]);
$yaExisteComoTrabajador = $stmt->fetchColumn() > 0;

$stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE email = :email");
$stmt->execute([":email" => $email]);
$yaExisteComoUsuario = $stmt->fetchColumn() > 0;

if ($yaExisteComoTrabajador || $yaExisteComoUsuario) {
    http_response_code(409);
    echo json_encode(["error" => "Ese email ya está registrado. Iniciá sesión o usá otro email."]);
    exit;
}


if (!isset($_FILES["foto"]) || $_FILES["foto"]["error"] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(["error" => "Falta la foto de perfil"]);
    exit;
}

$foto = $_FILES["foto"];
$tiposPermitidos = ["image/jpeg" => "jpg", "image/png" => "png", "image/webp" => "webp"];
$mime = mime_content_type($foto["tmp_name"]);

if (!isset($tiposPermitidos[$mime])) {
    http_response_code(400);
    echo json_encode(["error" => "La foto tiene que ser JPG, PNG o WEBP"]);
    exit;
}

if ($foto["size"] > 3 * 1024 * 1024) {
    http_response_code(400);
    echo json_encode(["error" => "La foto no puede pesar más de 3MB"]);
    exit;
}


function generarSlug($texto) {
    $texto = strtolower($texto);
    $texto = str_replace(
        ["á", "é", "í", "ó", "ú", "ñ"],
        ["a", "e", "i", "o", "u", "n"],
        $texto
    );
    $texto = preg_replace("/[^a-z0-9]+/", "-", $texto);
    return trim($texto, "-");
}

$slugBase = generarSlug($nombre . "-" . $apellido);
$slug = $slugBase;
$intento = 1;

$stmt = $pdo->prepare("SELECT COUNT(*) FROM trabajadores WHERE provider_id = :id");
while (true) {
    $stmt->execute([":id" => $slug]);
    if ($stmt->fetchColumn() == 0) break;
    $intento++;
    $slug = $slugBase . "-" . $intento;
}


$nombreArchivo = $slug . "-" . uniqid() . "." . $tiposPermitidos[$mime];
$carpetaDestino = __DIR__ . "/../uploads/fotos-perfil/";
$rutaDestino = $carpetaDestino . $nombreArchivo;

if (!move_uploaded_file($foto["tmp_name"], $rutaDestino)) {
    http_response_code(500);
    echo json_encode(["error" => "No se pudo guardar la foto"]);
    exit;
}


try {
    $stmt = $pdo->prepare("
        INSERT INTO trabajadores
            (provider_id, nombre, apellido, edad, celular, email, instagram, estudios, foto, password_hash)
        VALUES
            (:provider_id, :nombre, :apellido, :edad, :celular, :email, :instagram, :estudios, :foto, :password_hash)
    ");

    $stmt->execute([
        ":provider_id"   => $slug,
        ":nombre"        => $nombre,
        ":apellido"      => $apellido,
        ":edad"          => $edad,
        ":celular"       => $celular,
        ":email"         => $email,
        ":instagram"     => $instagram !== "" ? $instagram : null,
        ":estudios"      => $estudios,
        ":foto"          => $nombreArchivo,
        ":password_hash" => password_hash($password, PASSWORD_DEFAULT)
    ]);
} catch (PDOException $e) {
    @unlink($rutaDestino);

    if ($e->getCode() === "23000") {
        http_response_code(409);
        echo json_encode(["error" => "Ese email ya está registrado"]);
        exit;
    }
    http_response_code(500);
    echo json_encode(["error" => "No se pudo completar el registro"]);
    exit;
}

echo json_encode([
    "ok" => true,
    "provider_id" => $slug
]);

// Avisa a make que mande el mail de bienvenida
$payload = json_encode([
    "tipo" => "bienvenida",
    "email" => $email,
    "nombre" => $nombre,
    "apellido" => $apellido
]);

$ch = curl_init($MAKE_WEBHOOK_URL);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 8);
curl_exec($ch);
curl_close($ch);