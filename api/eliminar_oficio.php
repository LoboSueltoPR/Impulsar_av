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

$body = json_decode(file_get_contents("php://input"), true);
$oficioId = (int) ($body["oficio_id"] ?? 0);

if ($oficioId <= 0) {
    http_response_code(400);
    echo json_encode(["error" => "Falta indicar qué oficio borrar"]);
    exit;
}


$stmt = $pdo->prepare("SELECT id FROM oficios WHERE id = :id AND provider_id = :provider_id");
$stmt->execute([":id" => $oficioId, ":provider_id" => $providerId]);
if (!$stmt->fetch()) {
    http_response_code(404);
    echo json_encode(["error" => "No encontramos ese oficio en tu perfil"]);
    exit;
}


$stmt = $pdo->prepare("SELECT tipo, archivo FROM oficios_media WHERE oficio_id = :id");
$stmt->execute([":id" => $oficioId]);
$archivosMedia = $stmt->fetchAll(PDO::FETCH_ASSOC);


$stmt = $pdo->prepare("DELETE FROM oficios WHERE id = :id");
$stmt->execute([":id" => $oficioId]);

foreach ($archivosMedia as $m) {
    $carpeta = $m["tipo"] === "video" ? "videos-trabajo" : "fotos-trabajo";
    @unlink(__DIR__ . "/../uploads/$carpeta/" . $m["archivo"]);
}

echo json_encode(["ok" => true]);
