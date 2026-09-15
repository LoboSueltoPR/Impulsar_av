<?php
session_start();
header("Content-Type: application/json; charset=utf-8");
require "db.php";

$body = json_decode(file_get_contents("php://input"), true);
$providerId = trim($body["provider_id"] ?? "");
$tipo = trim($body["tipo"] ?? "");

$tiposValidos = ["llamada", "whatsapp", "email", "instagram"];

if ($providerId === "" || !in_array($tipo, $tiposValidos, true)) {
    echo json_encode(["ok" => true]);
    exit;
}

$stmt = $pdo->prepare("INSERT INTO contactos (provider_id, tipo) VALUES (:id, :tipo)");
$stmt->execute([":id" => $providerId, ":tipo" => $tipo]);


if (isset($_SESSION["tipo"]) && isset($_SESSION["id"])) {
    $contactanteTipo = $_SESSION["tipo"];
    $contactanteId = $_SESSION["id"];

    if (!($contactanteTipo === "trabajador" && $contactanteId === $providerId)) {

        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM seguimientos
            WHERE provider_id = :provider_id
              AND contactante_tipo = :tipo
              AND contactante_id = :id
              AND estado != 'finalizado'
        ");
        $stmt->execute([":provider_id" => $providerId, ":tipo" => $contactanteTipo, ":id" => $contactanteId]);
        $yaExisteUno = $stmt->fetchColumn() > 0;

        if (!$yaExisteUno) {
            $stmt = $pdo->prepare("SELECT email, nombre" . ($contactanteTipo === "trabajador" ? ", apellido" : ", apellido") . "
                FROM " . ($contactanteTipo === "trabajador" ? "trabajadores" : "usuarios") . "
                WHERE " . ($contactanteTipo === "trabajador" ? "provider_id" : "id") . " = :id
            ");
            $stmt->execute([":id" => $contactanteId]);
            $datosContactante = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($datosContactante) {
                $proximaFecha = date("Y-m-d H:i:s", strtotime("+5 days"));
                $stmt = $pdo->prepare("
                    INSERT INTO seguimientos
                        (provider_id, contactante_tipo, contactante_id, contactante_email, contactante_nombre, proxima_fecha)
                    VALUES
                        (:provider_id, :tipo, :id, :email, :nombre, :fecha)
                ");
                $stmt->execute([
                    ":provider_id" => $providerId,
                    ":tipo" => $contactanteTipo,
                    ":id" => $contactanteId,
                    ":email" => $datosContactante["email"],
                    ":nombre" => $datosContactante["nombre"] . " " . $datosContactante["apellido"],
                    ":fecha" => $proximaFecha
                ]);
            }
        }
    }
}

echo json_encode(["ok" => true]);