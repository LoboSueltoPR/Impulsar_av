<?php
session_start();
header("Content-Type: application/json; charset=utf-8");
require "db.php";


if (!isset($_SESSION["tipo"]) || $_SESSION["tipo"] !== "trabajador") {
    http_response_code(403);
    echo json_encode(["error" => "Necesitás iniciar sesión como trabajador"]);
    exit;
}

$providerId = $_SESSION["id"];


$nombre    = trim($_POST["nombre"] ?? "");
$apellido  = trim($_POST["apellido"] ?? "");
$edad      = (int) ($_POST["edad"] ?? 0);
$celular   = trim($_POST["celular"] ?? "");
$email     = trim($_POST["email"] ?? "");
$instagram = trim($_POST["instagram"] ?? "");
$estudios  = trim($_POST["estudios"] ?? "");

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

if (!empty($errores)) {
    http_response_code(400);
    echo json_encode(["error" => implode(". ", $errores)]);
    exit;
}


$stmt = $pdo->prepare("SELECT COUNT(*) FROM trabajadores WHERE email = :email AND provider_id <> :id");
$stmt->execute([":email" => $email, ":id" => $providerId]);
$emailEnUsoPorOtroTrabajador = $stmt->fetchColumn() > 0;

$stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE email = :email");
$stmt->execute([":email" => $email]);
$emailEnUsoPorUsuario = $stmt->fetchColumn() > 0;

if ($emailEnUsoPorOtroTrabajador || $emailEnUsoPorUsuario) {
    http_response_code(409);
    echo json_encode(["error" => "Ese email ya está en uso por otra cuenta"]);
    exit;
}


$nombreArchivoNuevo = null;
$rutaArchivoNuevo = null;
$fotoAnterior = null;

if (isset($_FILES["foto"]) && $_FILES["foto"]["error"] === UPLOAD_ERR_OK) {
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

    $carpetaDestino = __DIR__ . "/../uploads/fotos-perfil/";
    $nombreArchivoNuevo = $providerId . "-" . uniqid() . "." . $tiposPermitidos[$mime];
    $rutaArchivoNuevo = $carpetaDestino . $nombreArchivoNuevo;

    if (!move_uploaded_file($foto["tmp_name"], $rutaArchivoNuevo)) {
        http_response_code(500);
        echo json_encode(["error" => "No se pudo guardar la foto"]);
        exit;
    }
}


try {
    if ($nombreArchivoNuevo) {
        $stmt = $pdo->prepare("SELECT foto FROM trabajadores WHERE provider_id = :id");
        $stmt->execute([":id" => $providerId]);
        $fotoAnterior = $stmt->fetchColumn();
    }

    $sql = "UPDATE trabajadores SET nombre = :nombre, apellido = :apellido, edad = :edad,
            celular = :celular, email = :email, instagram = :instagram, estudios = :estudios";
    $params = [
        ":nombre" => $nombre,
        ":apellido" => $apellido,
        ":edad" => $edad,
        ":celular" => $celular,
        ":email" => $email,
        ":instagram" => $instagram !== "" ? $instagram : null,
        ":estudios" => $estudios,
        ":id" => $providerId
    ];

    if ($nombreArchivoNuevo) {
        $sql .= ", foto = :foto";
        $params[":foto"] = $nombreArchivoNuevo;
    }

    $sql .= " WHERE provider_id = :id";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
} catch (PDOException $e) {
    if ($rutaArchivoNuevo) @unlink($rutaArchivoNuevo);

    if ($e->getCode() === "23000") {
        http_response_code(409);
        echo json_encode(["error" => "Ese email ya está en uso por otra cuenta"]);
        exit;
    }
    http_response_code(500);
    echo json_encode(["error" => "No se pudieron guardar los cambios"]);
    exit;
}

if ($fotoAnterior) {
    @unlink(__DIR__ . "/../uploads/fotos-perfil/" . $fotoAnterior);
}

$_SESSION["nombre"] = $nombre . " " . $apellido;

echo json_encode([
    "ok" => true,
    "nombre" => $_SESSION["nombre"],
    "foto" => $nombreArchivoNuevo
]);