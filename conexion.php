<?php
$host = "localhost";
$db   = "sistema_db"; // Asegúrate de que este sea el nombre correcto de tu BD
$user = "root";
$pass = ""; // En XAMPP por defecto está vacío

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Si falla la conexión, muestra el error real de MySQL y detiene el código
    die("Error crítico en la conexión a la Base de Datos: " . $e->getMessage());
}
?>