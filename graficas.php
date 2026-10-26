<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: index.php");
    exit;
}

// 1. Obtener ventas por fecha
$stmt_fechas = $pdo->query("SELECT DATE(fecha) AS fecha_dia, SUM(total) AS total_dia FROM ventas GROUP BY DATE(fecha) ORDER BY fecha_dia ASC LIMIT 10");
$datos_fechas = $stmt_fechas->fetchAll(PDO::FETCH_ASSOC);

$fechas_labels = [];
$fechas_totales = [];

foreach ($datos_fechas as $df) {
    $fechas_labels[] = $df['fecha_dia'];
    $fechas_totales[] = (float)$df['total_dia'];
}

// 2. Obtener los 5 productos con menos stock (alertas de inventario)
$stmt_stock = $pdo->query("SELECT producto, stock FROM productos ORDER BY stock ASC LIMIT 5");
$datos_stock = $stmt_stock->fetchAll(PDO::FETCH_ASSOC);

$stock_labels = [];
$stock_cantidades = [];

foreach ($datos_stock as $ds) {
    $stock_labels[] = $ds['producto'];
    $stock_cantidades[] = (int)$ds['stock'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Estadísticas y Gráficas de Ventas</title>
    <!-- Incluir Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 20px; background: #f8f9fa; }
        .header { display: flex; justify-content: space-between; align-items: center; background: #343a40; color: white; padding: 15px 20px; border-radius: 8px; }
        .btn { padding: 8px 12px; border: none; border-radius: 5px; cursor: pointer; color: white; text-decoration: none; font-size: 13px; font-weight: 600; display: inline-block; }
        .btn-blue { background: #0275d8; }
        .grid-charts { display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px; margin-top: 25px; }
        .chart-card { background: white; border: 1px solid #ddd; border-radius: 8px; padding: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .chart-card h3 { margin-top: 0; color: #333; text-align: center; }
    </style>
</head>
<body>

    <div class="header">
        <h2>📊 Panel de Gráficas y Métricas</h2>
        <div>
            <a href="crud_usuarios.php" class="btn btn-blue">👥 Usuarios</a>
            <a href="crud_productos.php" class="btn" style="background: #28a745; margin-left: 5px;">📦 Productos</a>
            <a href="bienvenido.php" class="btn" style="background: #17a2b8; margin-left: 5px;">🏠 Inicio</a>
        </div>
    </div>

    <div class="grid-charts">
        <!-- GRÁFICA 1: Historial de Ventas -->
        <div class="chart-card">
            <h3>Ventas Totales por Día ($)</h3>
            <canvas id="graficaVentas"></canvas>
        </div>

        <!-- GRÁFICA 2: Estado del Stock -->
        <div class="chart-card">
            <h3>Productos con Menor Stock</h3>
            <canvas id="graficaStock"></canvas>
        </div>
    </div>

    <script>
        // 1. Gráfica de Líneas para Ventas por Día
        const ctxVentas = document.getElementById('graficaVentas').getContext('2d');
        new Chart(ctxVentas, {
            type: 'line',
            data: {
                labels: <?= json_encode($fechas_labels) ?>,
                datasets: [{
                    label: 'Ingresos ($ MXN)',
                    data: <?= json_encode($fechas_totales) ?>,
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });

        // 2. Gráfica de Barras para el Stock
        const ctxStock = document.getElementById('graficaStock').getContext('2d');
        new Chart(ctxStock, {
            type: 'bar',
            data: {
                labels: <?= json_encode($stock_labels) ?>,
                datasets: [{
                    label: 'Unidades Disponibles',
                    data: <?= json_encode($stock_cantidades) ?>,
                    backgroundColor: [
                        '#dc3545', '#ffc107', '#17a2b8', '#0275d8', '#6c757d'
                    ]
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    </script>

</body>
</html>