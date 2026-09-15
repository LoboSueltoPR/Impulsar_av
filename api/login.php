<?php
session_start();
header("Content-Type: application/json; charset=utf-8");
require "db.php";

$body = json_decode(file_get_contents("php://input"), true);
$email = trim($body["email"] ?? "");
$password = $body["password"] ?? "";

if ($email === "" || $password === "") {
    http_response_code(400);
    echo json_encode(["error" => "Completá email y contraseña"]);
    exit;
}

$stmt = $pdo->prepare("SELECT provider_id, nombre, apellido, password_hash FROM trabajadores WHERE email = :email");
$stmt->execute([":email" => $email]);
$trabajador = $stmt->fetch(PDO::FETCH_ASSOC);

if ($trabajador && password_verify($password, $trabajador["password_hash"])) {
    $_SESSION["tipo"] = "trabajador";
    $_SESSION["id"] = $trabajador["provider_id"];
    $_SESSION["nombre"] = $trabajador["nombre"] . " " . $trabajador["apellido"];

    echo json_encode(["ok" => true, "tipo" => "trabajador", "nombre" => $_SESSION["nombre"]]);
    exit;
}

$stmt = $pdo->prepare("SELECT id, nombre, apellido, password_hash FROM usuarios WHERE email = :email");
$stmt->execute([":email" => $email]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if ($usuario && password_verify($password, $usuario["password_hash"])) {
    $_SESSION["tipo"] = "usuario";
    $_SESSION["id"] = $usuario["id"];
    $_SESSION["nombre"] = $usuario["nombre"] . " " . $usuario["apellido"];

    echo json_encode(["ok" => true, "tipo" => "usuario", "nombre" => $_SESSION["nombre"]]);
    exit;
}

http_response_code(401);
echo json_encode(["error" => "Email o contraseña incorrectos"]);