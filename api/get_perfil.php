<?php
header("Content-Type: application/json; charset=utf-8");
require "db.php";

$providerId = trim($_GET["id"] ?? "");

if ($providerId === "") {
    http_response_code(400);
    echo json_encode(["error" => "Falta indicar qué perfil buscar"]);
    exit;
}


$stmt = $pdo->prepare("
    SELECT provider_id, nombre, apellido, celular, email, instagram, foto
    FROM trabajadores
    WHERE provider_id = :id
");
$stmt->execute([":id" => $providerId]);
$trabajador = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$trabajador) {
    http_response_code(404);
    echo json_encode(["error" => "No encontramos ese perfil"]);
    exit;
}


$stmt = $pdo->prepare("
    SELECT id, rubro, oficio, certificaciones, descripcion
    FROM oficios
    WHERE provider_id = :id
    ORDER BY id ASC
");
$stmt->execute([":id" => $providerId]);
$oficios = $stmt->fetchAll(PDO::FETCH_ASSOC);


$stmtMedia = $pdo->prepare("
    SELECT tipo, archivo
    FROM oficios_media
    WHERE oficio_id = :oficio_id
    ORDER BY id ASC
");

foreach ($oficios as &$of) {
    $of["oficios"] = decodificarOficios($of["oficio"]);
    unset($of["oficio"]);
    $of["certificaciones"] = json_decode($of["certificaciones"] ?? "[]", true) ?: [];

    $stmtMedia->execute([":oficio_id" => $of["id"]]);
    $of["media"] = $stmtMedia->fetchAll(PDO::FETCH_ASSOC);
}
unset($of);


$stmt = $pdo->prepare("
    SELECT AVG(stars) AS promedio, COUNT(*) AS cantidad
    FROM ratings
    WHERE provider_id = :id
");
$stmt->execute([":id" => $providerId]);
$rating = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    "provider_id" => $trabajador["provider_id"],
    "nombre"      => $trabajador["nombre"],
    "apellido"    => $trabajador["apellido"],
    "celular"     => $trabajador["celular"],
    "email"       => $trabajador["email"],
    "instagram"   => $trabajador["instagram"],
    "foto"        => $trabajador["foto"],
    "oficios"     => $oficios,
    "promedio"    => round((float) ($rating["promedio"] ?? 0), 1),
    "cantidad"    => (int) ($rating["cantidad"] ?? 0)
], JSON_UNESCAPED_UNICODE);