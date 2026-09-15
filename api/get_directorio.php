<?php
header("Content-Type: application/json; charset=utf-8");
require "db.php";


$trabajadores = $pdo->query("
    SELECT provider_id, nombre, apellido, celular, email, instagram, foto
    FROM trabajadores
    ORDER BY created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);


$oficiosRows = $pdo->query("
    SELECT provider_id, oficio
    FROM oficios
    ORDER BY id ASC
")->fetchAll(PDO::FETCH_ASSOC);

$oficiosPorProvider = [];
foreach ($oficiosRows as $fila) {
    $listaOficios = decodificarOficios($fila["oficio"]);
    foreach ($listaOficios as $nombreOficio) {
        $oficiosPorProvider[$fila["provider_id"]][] = $nombreOficio;
    }
}


$ratingsRows = $pdo->query("
    SELECT provider_id, AVG(stars) AS promedio, COUNT(*) AS cantidad
    FROM ratings
    GROUP BY provider_id
")->fetchAll(PDO::FETCH_ASSOC);

$ratingsPorProvider = [];
foreach ($ratingsRows as $fila) {
    $ratingsPorProvider[$fila["provider_id"]] = [
        "promedio" => round((float) $fila["promedio"], 1),
        "cantidad" => (int) $fila["cantidad"]
    ];
}


$resultado = [];
foreach ($trabajadores as $t) {
    $pid = $t["provider_id"];

    if (empty($oficiosPorProvider[$pid])) {
        continue;
    }

    $resultado[] = [
        "provider_id" => $pid,
        "nombre"      => $t["nombre"],
        "apellido"    => $t["apellido"],
        "celular"     => $t["celular"],
        "email"       => $t["email"],
        "instagram"   => $t["instagram"],
        "foto"        => $t["foto"],
        "oficios"     => $oficiosPorProvider[$pid],
        "promedio"    => $ratingsPorProvider[$pid]["promedio"] ?? 0,
        "cantidad"    => $ratingsPorProvider[$pid]["cantidad"] ?? 0
    ];
}

echo json_encode($resultado, JSON_UNESCAPED_UNICODE);