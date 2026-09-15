<?php
session_start();
header("Content-Type: application/json; charset=utf-8");
require "db.php";


$providerId = isset($_POST["provider_id"]) ? trim($_POST["provider_id"]) : "";
$oficios = isset($_POST["oficios"]) ? json_decode($_POST["oficios"], true) : [];

if (!is_array($oficios)) $oficios = [];

if ($providerId === "" || count($oficios) === 0) {
    http_response_code(400);
    echo json_encode(["error" => "Faltan datos para completar el registro"]);
    exit;
}

/* Confirma que el trabajador existe */

$stmt = $pdo->prepare("SELECT COUNT(*) FROM trabajadores WHERE provider_id = :id");
$stmt->execute([":id" => $providerId]);
if ($stmt->fetchColumn() == 0) {
    http_response_code(404);
    echo json_encode(["error" => "No encontramos tu registro de la Parte 1"]);
    exit;
}

$tiposFoto = ["image/jpeg" => "jpg", "image/png" => "png", "image/webp" => "webp"];
$tiposVideo = ["video/mp4" => "mp4", "video/webm" => "webm", "video/quicktime" => "mov"];

$carpetaFotos = __DIR__ . "/../uploads/fotos-trabajo/";
$carpetaVideos = __DIR__ . "/../uploads/videos-trabajo/";

const MAX_FOTO_BYTES = 3 * 1024 * 1024;
const MAX_VIDEO_BYTES = 15 * 1024 * 1024;
const MAX_ARCHIVOS_POR_OFICIO = 10;


$oficiosLimpios = [];
foreach ($oficios as $indice => $o) {
    $rubro = trim($o["rubro"] ?? "");
    $descripcion = trim($o["descripcion"] ?? "");
    $certs = is_array($o["certificaciones"] ?? null) ? $o["certificaciones"] : [];
    $listaOficios = is_array($o["oficios"] ?? null) ? $o["oficios"] : [];

    $oficiosLimpiosNombres = array_values(array_filter(array_map(function ($nombre) {
        $nombre = trim((string) $nombre);
        return strlen($nombre) > 0 && strlen($nombre) <= 150 ? $nombre : null;
    }, $listaOficios)));

    if ($rubro === "" || strlen($rubro) > 100 || count($oficiosLimpiosNombres) === 0) {
        http_response_code(400);
        echo json_encode(["error" => "Uno de los rubros cargados tiene datos inválidos"]);
        exit;
    }

    if (strlen($descripcion) > 1000) {
        http_response_code(400);
        echo json_encode(["error" => "La descripción de experiencia es demasiado larga"]);
        exit;
    }

    $certsLimpias = array_values(array_filter(array_map(function ($c) {
        $c = trim((string) $c);
        return strlen($c) > 0 && strlen($c) <= 150 ? $c : null;
    }, $certs)));

    $oficiosLimpios[$indice] = [
        "rubro" => $rubro,
        "oficios" => $oficiosLimpiosNombres,
        "certificaciones" => $certsLimpias,
        "descripcion" => $descripcion !== "" ? $descripcion : null
    ];
}


$archivosPorOficio = [];

foreach ($oficiosLimpios as $indice => $_) {
    $archivos = [];
    $j = 0;
    while (isset($_FILES["media_{$indice}_{$j}"])) {
        $archivo = $_FILES["media_{$indice}_{$j}"];
        $j++;

        if ($archivo["error"] !== UPLOAD_ERR_OK) continue;
        if (count($archivos) >= MAX_ARCHIVOS_POR_OFICIO) {
            http_response_code(400);
            echo json_encode(["error" => "Como máximo se pueden subir " . MAX_ARCHIVOS_POR_OFICIO . " archivos por oficio"]);
            exit;
        }

        $mime = mime_content_type($archivo["tmp_name"]);

        if (isset($tiposFoto[$mime])) {
            if ($archivo["size"] > MAX_FOTO_BYTES) {
                http_response_code(400);
                echo json_encode(["error" => "Una de las fotos pesa más de 3MB"]);
                exit;
            }
            $archivos[] = ["tipo" => "foto", "tmp" => $archivo["tmp_name"], "ext" => $tiposFoto[$mime]];
        } elseif (isset($tiposVideo[$mime])) {
            if ($archivo["size"] > MAX_VIDEO_BYTES) {
                http_response_code(400);
                echo json_encode(["error" => "Uno de los videos pesa más de 15MB"]);
                exit;
            }
            $archivos[] = ["tipo" => "video", "tmp" => $archivo["tmp_name"], "ext" => $tiposVideo[$mime]];
        } else {
            http_response_code(400);
            echo json_encode(["error" => "Uno de los archivos no es un formato de foto o video permitido"]);
            exit;
        }
    }
    $archivosPorOficio[$indice] = $archivos;
}


$archivosGuardadosEnDisco = [];

try {
    $pdo->beginTransaction();

    $stmtOficio = $pdo->prepare("
        INSERT INTO oficios (provider_id, rubro, oficio, certificaciones, descripcion)
        VALUES (:provider_id, :rubro, :oficio, :certificaciones, :descripcion)
    ");

    $stmtMedia = $pdo->prepare("
        INSERT INTO oficios_media (oficio_id, tipo, archivo)
        VALUES (:oficio_id, :tipo, :archivo)
    ");

    foreach ($oficiosLimpios as $indice => $o) {
        $stmtOficio->execute([
            ":provider_id" => $providerId,
            ":rubro" => $o["rubro"],
            ":oficio" => json_encode($o["oficios"], JSON_UNESCAPED_UNICODE),
            ":certificaciones" => json_encode($o["certificaciones"], JSON_UNESCAPED_UNICODE),
            ":descripcion" => $o["descripcion"]
        ]);
        $oficioId = $pdo->lastInsertId();

        foreach ($archivosPorOficio[$indice] as $archivo) {
            $carpeta = $archivo["tipo"] === "video" ? $carpetaVideos : $carpetaFotos;
            $nombreArchivo = $providerId . "-" . uniqid() . "." . $archivo["ext"];
            $rutaDestino = $carpeta . $nombreArchivo;

            if (!move_uploaded_file($archivo["tmp"], $rutaDestino)) {
                throw new Exception("No se pudo guardar un archivo");
            }
            $archivosGuardadosEnDisco[] = $rutaDestino;

            $stmtMedia->execute([
                ":oficio_id" => $oficioId,
                ":tipo" => $archivo["tipo"],
                ":archivo" => $nombreArchivo
            ]);
        }
    }

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    foreach ($archivosGuardadosEnDisco as $ruta) {
        @unlink($ruta);
    }
    http_response_code(500);
    echo json_encode(["error" => "No se pudo guardar el registro"]);
    exit;
}

echo json_encode(["ok" => true, "cantidad" => count($oficiosLimpios)]);


$stmt = $pdo->prepare("SELECT nombre, apellido FROM trabajadores WHERE provider_id = :id");
$stmt->execute([":id" => $providerId]);
$datosTrabajador = $stmt->fetch(PDO::FETCH_ASSOC);

if ($datosTrabajador) {
    $_SESSION["tipo"] = "trabajador";
    $_SESSION["id"] = $providerId;
    $_SESSION["nombre"] = $datosTrabajador["nombre"] . " " . $datosTrabajador["apellido"];
}