<?php
require_once 'conexion.php';

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT imagen FROM productos WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $producto = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($producto && !empty($producto['imagen'])) {
        header("Content-Type: image/jpeg"); // O puedes usar un detector de mime si manejas PNG/GIF
        echo $producto['imagen'];
        exit;
    }
}

// Imagen por defecto transparente si no hay archivo subido
header("Content-Type: image/png");
echo base64_decode('iVBORw0KGgoAAAANSUheader...'); // O envía un 404