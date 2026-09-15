<?php 
$DB_HOST = "localhost";
$DB_NAME = "nexo_tierras_schema";
$DB_USER = "root";
$DB_PASS = "";

$SITE_URL = "http://localhost/nexo-barrio";
$MAKE_WEBHOOK_URL = "https://hook.us2.make.com/ctm63cyvtrdy9bfu8rt4wc6su9s7sy9m";


$CRON_SECRET = "impulsar2026";

function firmarRespuestaEmail($seguimientoId, $respuesta) {
    global $CRON_SECRET;
    return hash_hmac("sha256", "$seguimientoId:$respuesta", $CRON_SECRET);
}
 
try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "No se pudo conectar a la base de datos"]);
    exit;
}

function decodificarOficios($valor) {
    $decodificado = json_decode($valor, true);
    if (is_array($decodificado)) return $decodificado;
    return [$valor];
}