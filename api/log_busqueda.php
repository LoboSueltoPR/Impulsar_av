<?php
session_start();
header("Content-Type: application/json; charset=utf-8");
require "db.php";

$body = json_decode(file_get_contents("php://input"), true);
$termino = trim($body["termino"] ?? "");

if ($termino === "" || strlen($termino) > 150) {
    echo json_encode(["ok" => true]);
    exit;
}

$usuarioNombre = $_SESSION["nombre"] ?? null;

$stmt = $pdo->prepare("
    INSERT INTO busquedas (termino, usuario_nombre)
    VALUES (:termino, :usuario_nombre)
");
$stmt->execute([
    ":termino" => $termino,
    ":usuario_nombre" => $usuarioNombre
]);

echo json_encode(["ok" => true]);