<?php
session_start();
header("Content-Type: application/json; charset=utf-8");
require "db.php";


if (!isset($_SESSION["tipo"]) || $_SESSION["tipo"] !== "trabajador") {
    http_response_code(403);
    echo json_encode(["error" => "Necesitás iniciar sesión como trabajador para ver esta página"]);
    exit;
}

$providerId = $_SESSION["id"];


$stmt = $pdo->prepare("
    SELECT provider_id, nombre, apellido, edad, celular, email, instagram, estudios, foto
    FROM trabajadores
    WHERE provider_id = :id
");
$stmt->execute([":id" => $providerId]);
$trabajador = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$trabajador) {
    http_response_code(404);
    echo json_encode(["error" => "No encontramos tu perfil"]);
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


$stmt = $pdo->prepare("SELECT COUNT(*) FROM vistas_perfil WHERE provider_id = :id");
$stmt->execute([":id" => $providerId]);
$vistasTotal = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT DATE(created_at) AS fecha, COUNT(*) AS cantidad
    FROM vistas_perfil
    WHERE provider_id = :id AND created_at >= (CURDATE() - INTERVAL 29 DAY)
    GROUP BY DATE(created_at)
");
$stmt->execute([":id" => $providerId]);
$vistasPorFecha = [];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
    $vistasPorFecha[$fila["fecha"]] = (int) $fila["cantidad"];
}

$vistasPorDia = [];
$vistasUltimos30Dias = 0;
for ($i = 29; $i >= 0; $i--) {
    $fecha = date("Y-m-d", strtotime("-$i day"));
    $cantidad = $vistasPorFecha[$fecha] ?? 0;
    $vistasUltimos30Dias += $cantidad;
    $vistasPorDia[] = ["fecha" => $fecha, "cantidad" => $cantidad];
}


$stmt = $pdo->prepare("SELECT COUNT(*) FROM contactos WHERE provider_id = :id");
$stmt->execute([":id" => $providerId]);
$contactosTotal = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT tipo, COUNT(*) AS cantidad
    FROM contactos
    WHERE provider_id = :id
    GROUP BY tipo
");
$stmt->execute([":id" => $providerId]);

$contactosPorTipo = ["llamada" => 0, "whatsapp" => 0, "email" => 0, "instagram" => 0];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
    if (isset($contactosPorTipo[$fila["tipo"]])) {
        $contactosPorTipo[$fila["tipo"]] = (int) $fila["cantidad"];
    }
}


$cantidadOficios = count($oficios);


$tasaContacto = $vistasTotal > 0 ? round(($contactosTotal / $vistasTotal) * 100, 1) : 0;

echo json_encode([
    "trabajador" => $trabajador,
    "oficios" => $oficios,
    "estadisticas" => [
        "vistas_total" => $vistasTotal,
        "vistas_ultimos_30_dias" => $vistasUltimos30Dias,
        "vistas_por_dia" => $vistasPorDia,
        "contactos_total" => $contactosTotal,
        "contactos_por_tipo" => $contactosPorTipo,
        "cantidad_oficios" => $cantidadOficios,
        "tasa_contacto" => $tasaContacto
    ]
], JSON_UNESCAPED_UNICODE);