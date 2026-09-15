<?php
header("Content-Type: application/json; charset=utf-8");
require "db.php";

$stmt = $pdo->query("
    SELECT provider_id, AVG(stars) AS promedio, COUNT(*) AS cantidad
    FROM ratings
    GROUP BY provider_id
");

$resultado = [];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
    $resultado[$fila["provider_id"]] = [
        "promedio" => round((float) $fila["promedio"], 1),
        "cantidad" => (int) $fila["cantidad"]
    ];
}

echo json_encode($resultado);