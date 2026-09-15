<?php
session_start();
header("Content-Type: application/json; charset=utf-8");
require "db.php";

$body = json_decode(file_get_contents("php://input"), true);
$token = trim($body["token"] ?? "");
$password = $body["password"] ?? "";

if ($token === "" || strlen($password) < 6) {
    http_response_code(400);
    echo json_encode(["error" => "Completá una contraseña de al menos 6 caracteres"]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT * FROM password_resets
    WHERE token = :token AND usado = 0 AND expira > NOW()
");
$stmt->execute([":token" => $token]);
$reset = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reset) {
    http_response_code(400);
    echo json_encode(["error" => "El link no es válido o ya venció. Pedí uno nuevo."]);
    exit;
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);

try {
    $pdo->beginTransaction();

    if ($reset["tipo"] === "trabajador") {
        $stmt = $pdo->prepare("UPDATE trabajadores SET password_hash = :ph WHERE provider_id = :id");
        $stmt->execute([":ph" => $passwordHash, ":id" => $reset["identificador"]]);

        $stmt = $pdo->prepare("SELECT nombre, apellido FROM trabajadores WHERE provider_id = :id");
        $stmt->execute([":id" => $reset["identificador"]]);
        $datos = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $stmt = $pdo->prepare("UPDATE usuarios SET password_hash = :ph WHERE id = :id");
        $stmt->execute([":ph" => $passwordHash, ":id" => $reset["identificador"]]);

        $stmt = $pdo->prepare("SELECT nombre, apellido FROM usuarios WHERE id = :id");
        $stmt->execute([":id" => $reset["identificador"]]);
        $datos = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    $stmt = $pdo->prepare("UPDATE password_resets SET usado = 1 WHERE id = :id");
    $stmt->execute([":id" => $reset["id"]]);

    $pdo->commit();
} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(["error" => "No se pudo actualizar la contraseña"]);
    exit;
}


$_SESSION["tipo"] = $reset["tipo"];
$_SESSION["id"] = $reset["identificador"];
$_SESSION["nombre"] = $datos["nombre"] . " " . $datos["apellido"];

echo json_encode(["ok" => true]);
