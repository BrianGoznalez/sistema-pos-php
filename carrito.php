<?php
session_start();
require_once 'conexion.php';

// Activar reporte de errores de PDO
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

// Inicializar el carrito si no existe
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

// 1. AGREGAR AL CARRITO
if (isset($_POST['agregar'])) {
    $id_prod = (int)$_POST['id_producto'];
    $cantidad = (int)$_POST['cantidad'];

    $stmt = $pdo->prepare("SELECT id, producto, precio, stock FROM productos WHERE id = :id");
    $stmt->execute(['id' => $id_prod]);
    $prod = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($prod && $cantidad > 0 && $cantidad <= $prod['stock']) {
        if (isset($_SESSION['carrito'][$id_prod])) {
            $_SESSION['carrito'][$id_prod]['cantidad'] += $cantidad;
        } else {
            $_SESSION['carrito'][$id_prod] = [
                'producto' => $prod['producto'],
                'precio'   => $prod['precio'],
                'cantidad' => $cantidad
            ];
        }
    }
    header("Location: carrito.php");
    exit;
}

// 2. ELIMINAR DEL CARRITO
if (isset($_GET['quitar'])) {
    $id_quitar = (int)$_GET['quitar'];
    unset($_SESSION['carrito'][$id_quitar]);
    header("Location: carrito.php");
    exit;
}

// 3. VACIAR CARRITO
if (isset($_GET['vaciar'])) {
    $_SESSION['carrito'] = [];
    header("Location: carrito.php");
    exit;
}

// ASEGURAR QUE LA COLUMNA PRECIO EXISTA EN LA BD
try {
    $pdo->exec("ALTER TABLE productos ADD COLUMN precio DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER stock");
} catch (PDOException $e) {
    // Si la columna ya existe, ignora el error
}

// CONSULTAR PRODUCTOS
$catalogos = [];
$error_db = '';
try {
    $stmt = $pdo->query("SELECT id, producto, stock, precio, (imagen IS NOT NULL AND LENGTH(imagen) > 0) AS tiene_imagen FROM productos ORDER BY id DESC");
    $catalogos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_db = "Error al consultar la base de datos: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo y Carrito de Compras</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 20px; background: #f8f9fa; }
        .header { display: flex; justify-content: space-between; align-items: center; background: #343a40; color: white; padding: 15px 20px; border-radius: 8px; }
        .header h2 { margin: 0; font-size: 20px; }
        .grid-catalogo { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 15px; margin-top: 20px; margin-bottom: 30px; }
        .card { background: white; border: 1px solid #ddd; border-radius: 8px; padding: 15px; text-align: center; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .card img { width: 120px; height: 120px; object-fit: cover; border-radius: 6px; }
        .precio-sub { font-size: 13px; color: #666; margin-top: 5px; }
        .precio-iva { font-size: 15px; color: #28a745; font-weight: bold; margin-bottom: 5px; }
        .btn { padding: 8px 12px; border: none; border-radius: 5px; cursor: pointer; color: white; text-decoration: none; font-size: 13px; display: inline-block; font-weight: 600; }
        .btn-green { background: #28a745; }
        .btn-red { background: #d9534f; }
        .btn-blue { background: #0275d8; }
        .carrito-box { background: white; padding: 20px; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px; border: 1px solid #ddd; text-align: center; }
        th { background: #0275d8; color: white; }
        .alert-error { background: #f8d7da; color: #721c24; padding: 12px; border-radius: 5px; margin-top: 15px; }
    </style>
</head>
<body>

    <div class="header">
        <h2>🛍️ Tienda y Catálogo de Productos</h2>
        <div>
            <a href="bienvenido.php" class="btn btn-blue">🏠 Inicio</a>
            <a href="logout.php" class="btn btn-red" style="margin-left: 10px;">🚪 Cerrar Sesión</a>
        </div>
    </div>

    <?php if (!empty($error_db)): ?>
        <div class="alert-error"><?= htmlspecialchars($error_db) ?></div>
    <?php endif; ?>

    <!-- SECCIÓN DE CATÁLOGO -->
    <h3 style="margin-top: 25px; color: #333;">Productos Disponibles</h3>
    <div class="grid-catalogo">
        <?php if (!empty($catalogos)): ?>
            <?php foreach ($catalogos as $p): ?>
                <?php 
                    $precio_base = isset($p['precio']) ? (float)$p['precio'] : 0.00;
                    $precio_con_iva = $precio_base * 1.16;
                ?>
                <div class="card">
                    <?php if ($p['tiene_imagen']): ?>
                        <img src="ver_imagen.php?id=<?= $p['id'] ?>" alt="prod">
                    <?php else: ?>
                        <div style="height:120px; background:#eee; line-height:120px; color:#888; border-radius: 6px;">Sin Imagen</div>
                    <?php endif; ?>
                    
                    <h4 style="margin: 10px 0 5px 0;"><?= htmlspecialchars($p['producto']) ?></h4>
                    <div class="precio-sub">Precio Base: $<?= number_format($precio_base, 2) ?></div>
                    <div class="precio-iva">Con IVA (16%): $<?= number_format($precio_con_iva, 2) ?></div>
                    <small style="color: #666;">Stock disponible: <?= $p['stock'] ?></small>
                    
                    <?php if ($p['stock'] > 0): ?>
                        <form method="POST" action="carrito.php" style="margin-top: 10px;">
                            <input type="hidden" name="id_producto" value="<?= $p['id'] ?>">
                            <input type="number" name="cantidad" value="1" min="1" max="<?= $p['stock'] ?>" style="width: 50px; padding: 4px; text-align: center;">
                            <button type="submit" name="agregar" class="btn btn-green">🛒 Agregar</button>
                        </form>
                    <?php else: ?>
                        <div style="color: #d9534f; margin-top: 10px; font-weight: bold; font-size: 13px;">Agotado</div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="color: #666;">No hay productos registrados en el inventario.</p>
        <?php endif; ?>
    </div>

    <!-- SECCIÓN DE MI CARRITO DE COMPRAS -->
    <div class="carrito-box">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; color: #333;">Mi Carrito de Compras</h3>
            <?php if (!empty($_SESSION['carrito'])): ?>
                <div>
                    <a href="carrito.php?vaciar=1" class="btn btn-red">Vaciar Carrito</a>
                    <a href="generar_ticket.php" class="btn btn-green" style="font-size: 14px; margin-left: 10px;">💳 Finalizar Compra y Ver Ticket</a>
                </div>
            <?php endif; ?>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Cantidad</th>
                    <th>Precio Base Unit.</th>
                    <th>Subtotal (Sin IVA)</th>
                    <th>IVA (16%)</th>
                    <th>Subtotal (Con IVA)</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $total_general_subtotal = 0;
                $total_general_iva = 0;
                $total_general_final = 0;
                ?>
                <?php if (!empty($_SESSION['carrito'])): ?>
                    <?php foreach ($_SESSION['carrito'] as $id => $item): ?>
                        <?php 
                            $precio_item = isset($item['precio']) ? $item['precio'] : 0;
                            $subtotal_item = $precio_item * $item['cantidad'];
                            $iva_item = $subtotal_item * 0.16;
                            $total_item = $subtotal_item + $iva_item;

                            $total_general_subtotal += $subtotal_item;
                            $total_general_iva += $iva_item;
                            $total_general_final += $total_item;
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($item['producto']) ?></td>
                            <td><?= $item['cantidad'] ?></td>
                            <td>$<?= number_format($precio_item, 2) ?></td>
                            <td>$<?= number_format($subtotal_item, 2) ?></td>
                            <td>$<?= number_format($iva_item, 2) ?></td>
                            <td><strong>$<?= number_format($total_item, 2) ?></strong></td>
                            <td><a href="carrito.php?quitar=<?= $id ?>" class="btn btn-red">Quitar</a></td>
                        </tr>
                    <?php endforeach; ?>
                    <!-- TOTALES -->
                    <tr style="background: #f1f1f1; font-weight: bold;">
                        <td colspan="3" style="text-align: right;">TOTALES:</td>
                        <td>$<?= number_format($total_general_subtotal, 2) ?></td>
                        <td>$<?= number_format($total_general_iva, 2) ?></td>
                        <td style="color: #28a745; font-size: 16px;">$<?= number_format($total_general_final, 2) ?></td>
                        <td></td>
                    </tr>
                <?php else: ?>
                    <tr><td colspan="7">El carrito está vacío. Agrega productos desde el catálogo arriba.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</body>
</html>