<?php
session_start();
header("Content-Type: application/json; charset=utf-8");

if (isset($_SESSION["tipo"])) {
    echo json_encode([
        "logueado" => true,
        "tipo" => $_SESSION["tipo"],
        "id" => $_SESSION["id"],
        "nombre" => $_SESSION["nombre"]
    ]);
} else {
    echo json_encode(["logueado" => false]);
}