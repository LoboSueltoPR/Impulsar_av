<?php
session_start();
header("Content-Type: application/json; charset=utf-8");
require "db.php";

$body = json_decode(file_get_contents("php://input"), true);

$nombre = trim($body["nombre"] ?? "");
$apellido = trim($body["apellido"] ?? "");
$email = trim($body["email"] ?? "");
$password = $body["password"] ?? "";

$soloLetras = '/^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ\s]+$/u';

$errores = [];
if ($nombre === "" || !preg_match($soloLetras, $nombre) || strlen($nombre) > 100) {
    $errores[] = "Nombre inválido";
}
if ($apellido === "" || !preg_match($soloLetras, $apellido) || strlen($apellido) > 100) {
    $errores[] = "Apellido inválido";
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errores[] = "Email inválido";
}
if (strlen($password) < 6) {
    $errores[] = "La contraseña tiene que tener al menos 6 caracteres";
}

if (!empty($errores)) {
    http_response_code(400);
    echo json_encode(["error" => implode(". ", $errores)]);
    exit;
}


$stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE email = :email");
$stmt->execute([":email" => $email]);
$yaExisteComoUsuario = $stmt->fetchColumn() > 0;

$stmt = $pdo->prepare("SELECT COUNT(*) FROM trabajadores WHERE email = :email");
$stmt->execute([":email" => $email]);
$yaExisteComoTrabajador = $stmt->fetchColumn() > 0;

if ($yaExisteComoUsuario || $yaExisteComoTrabajador) {
    http_response_code(409);
    echo json_encode(["error" => "Ese email ya está registrado. Iniciá sesión o usá otro email."]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO usuarios (nombre, apellido, email, password_hash)
        VALUES (:nombre, :apellido, :email, :password_hash)
    ");
    $stmt->execute([
        ":nombre" => $nombre,
        ":apellido" => $apellido,
        ":email" => $email,
        ":password_hash" => password_hash($password, PASSWORD_DEFAULT)
    ]);
} catch (PDOException $e) {
    if ($e->getCode() === "23000") {
        http_response_code(409);
        echo json_encode(["error" => "Ese email ya está registrado"]);
        exit;
    }
    http_response_code(500);
    echo json_encode(["error" => "No se pudo crear la cuenta"]);
    exit;
}


$_SESSION["tipo"] = "usuario";
$_SESSION["id"] = $pdo->lastInsertId();
$_SESSION["nombre"] = "$nombre $apellido";

echo json_encode(["ok" => true, "tipo" => "usuario", "nombre" => $_SESSION["nombre"]]);