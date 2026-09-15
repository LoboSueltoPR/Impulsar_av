<?php
header("Content-Type: application/json; charset=utf-8");
require "db.php";

$body = json_decode(file_get_contents("php://input"), true);
$providerId = trim($body["provider_id"] ?? "");

if ($providerId === "") {
    echo json_encode(["ok" => true]);
    exit;
}

$stmt = $pdo->prepare("INSERT INTO vistas_perfil (provider_id) VALUES (:id)");
$stmt->execute([":id" => $providerId]);

echo json_encode(["ok" => true]);
