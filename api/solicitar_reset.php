<?php
header("Content-Type: application/json; charset=utf-8");
require "db.php";

$body = json_decode(file_get_contents("php://input"), true);
$email = trim($body["email"] ?? "");

$respuestaGenerica = ["ok" => true, "mensaje" => "Si el email está registrado, te llegó un correo con instrucciones."];

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode($respuestaGenerica);
    exit;
}

// Busca el email entre la tabla de trabajadores, si no lo encuentra busca en usuarios
$tipo = null;
$identificador = null;
$nombre = null;

$stmt = $pdo->prepare("SELECT provider_id, nombre FROM trabajadores WHERE email = :email");
$stmt->execute([":email" => $email]);
$fila = $stmt->fetch(PDO::FETCH_ASSOC);

if ($fila) {
    $tipo = "trabajador";
    $identificador = $fila["provider_id"];
    $nombre = $fila["nombre"];
} else {
    $stmt = $pdo->prepare("SELECT id, nombre FROM usuarios WHERE email = :email");
    $stmt->execute([":email" => $email]);
    $fila = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($fila) {
        $tipo = "usuario";
        $identificador = (string) $fila["id"];
        $nombre = $fila["nombre"];
    }
}

if (!$tipo) {
    echo json_encode($respuestaGenerica);
    exit;
}

// Genera el token y lo guarda
$token = bin2hex(random_bytes(32));
$expira = date("Y-m-d H:i:s", strtotime("+1 hour"));

$stmt = $pdo->prepare("
    INSERT INTO password_resets (tipo, identificador, email, token, expira)
    VALUES (:tipo, :identificador, :email, :token, :expira)
");
$stmt->execute([
    ":tipo" => $tipo,
    ":identificador" => $identificador,
    ":email" => $email,
    ":token" => $token,
    ":expira" => $expira
]);


// Avisa a make que mande el correo
$link = "$SITE_URL/restablecer.html?token=$token";

$payload = json_encode([
    "tipo" => "recuperar_password",
    "email" => $email,
    "nombre" => $nombre,
    "link" => $link
]);

$ch = curl_init($MAKE_WEBHOOK_URL);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 8);
curl_exec($ch);
curl_close($ch);

echo json_encode($respuestaGenerica);