<?php
header("Content-Type: application/json; charset=utf-8");
require "db.php";

$body = json_decode(file_get_contents("php://input"), true);

$providerId = isset($body["provider_id"]) ? trim($body["provider_id"]) : "";
$stars = isset($body["stars"]) ? (int) $body["stars"] : 0;
$ip = $_SERVER["REMOTE_ADDR"];

// Validaciones
if ($providerId === "" || $stars < 1 || $stars > 5) {
    http_response_code(400);
    echo json_encode(["error" => "Datos inválidos"]);
    exit;
}

try {
    $stmt = $pdo->prepare(
        "INSERT INTO ratings (provider_id, stars, ip_address) VALUES (:pid, :stars, :ip)"
    );
    $stmt->execute([
        ":pid" => $providerId,
        ":stars" => $stars,
        ":ip" => $ip
    ]);
} catch (PDOException $e) {
    if ($e->getCode() === "23000") {
        http_response_code(409);
        echo json_encode(["error" => "Ya calificaste a esta persona antes"]);
        exit;
    }
    http_response_code(500);
    echo json_encode(["error" => "No se pudo guardar la reseña"]);
    exit;
}

// Devuelve el promedio actualizado de esa persona
$stmt = $pdo->prepare(
    "SELECT AVG(stars) AS promedio, COUNT(*) AS cantidad FROM ratings WHERE provider_id = :pid"
);
$stmt->execute([":pid" => $providerId]);
$fila = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    "promedio" => round((float) $fila["promedio"], 1),
    "cantidad" => (int) $fila["cantidad"]
]);