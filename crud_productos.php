<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$mensaje = '';
$error = '';

// 1. REGISTRAR PRODUCTO
if (isset($_POST['crear_producto'])) {
    $producto = trim($_POST['producto']);
    $stock    = (int)$_POST['stock'];
    $precio   = (float)$_POST['precio'];
    $bytes_imagen = null;

    if (!empty($_FILES['imagen']['tmp_name'])) {
        $bytes_imagen = file_get_contents($_FILES['imagen']['tmp_name']);
    }

    if (!empty($producto) && $precio >= 0) {
        $stmt = $pdo->prepare("INSERT INTO productos (producto, stock, precio, imagen, fecha_registro) VALUES (:p, :s, :pr, :img, NOW())");
        $stmt->bindParam(':p', $producto);
        $stmt->bindParam(':s', $stock, PDO::PARAM_INT);
        $stmt->bindParam(':pr', $precio);
        $stmt->bindParam(':img', $bytes_imagen, PDO::PARAM_LOB);

        if ($stmt->execute()) {
            $mensaje = "Producto registrado correctamente.";
        } else {
            $error = "No se pudo agregar el producto.";
        }
    } else {
        $error = "El nombre y el precio son obligatorios.";
    }
}

// 2. ELIMINAR PRODUCTO
if (isset($_GET['eliminar'])) {
    $id_eliminar = (int)$_GET['eliminar'];
    $stmt = $pdo->prepare("DELETE FROM productos WHERE id = :id");
    $stmt->execute(['id' => $id_eliminar]);
    $mensaje = "Producto eliminado correctamente.";
}

// 3. ACTUALIZAR PRODUCTO
if (isset($_POST['actualizar_producto'])) {
    $id_editar = (int)$_POST['id_editar'];
    $producto  = trim($_POST['producto']);
    $stock     = (int)$_POST['stock'];
    $precio    = (float)$_POST['precio'];

    if (!empty($producto) && $precio >= 0) {
        if (!empty($_FILES['imagen']['tmp_name'])) {
            $bytes_imagen = file_get_contents($_FILES['imagen']['tmp_name']);
            $stmt = $pdo->prepare("UPDATE productos SET producto = :p, stock = :s, precio = :pr, imagen = :img WHERE id = :id");
            $stmt->bindParam(':img', $bytes_imagen, PDO::PARAM_LOB);
        } else {
            $stmt = $pdo->prepare("UPDATE productos SET producto = :p, stock = :s, precio = :pr WHERE id = :id");
        }
        
        $stmt->bindParam(':p', $producto);
        $stmt->bindParam(':s', $stock, PDO::PARAM_INT);
        $stmt->bindParam(':pr', $precio);
        $stmt->bindParam(':id', $id_editar, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            $mensaje = "Producto actualizado correctamente.";
        } else {
            $error = "Error al actualizar el producto.";
        }
    } else {
        $error = "El nombre y el precio son obligatorios.";
    }
}

// OBTENER PRODUCTO A EDITAR
$prod_editar = null;
if (isset($_GET['editar'])) {
    $id_edit = (int)$_GET['editar'];
    $stmt = $pdo->prepare("SELECT id, producto, stock, precio FROM productos WHERE id = :id");
    $stmt->execute(['id' => $id_edit]);
    $prod_editar = $stmt->fetch(PDO::FETCH_ASSOC);
}

// LISTAR PRODUCTOS
$stmt = $pdo->query("SELECT id, producto, stock, precio, fecha_registro, (imagen IS NOT NULL AND LENGTH(imagen) > 0) AS tiene_imagen FROM productos ORDER BY fecha_registro DESC");
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Productos</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f8f9fa; }
        .header { display: flex; justify-content: space-between; align-items: center; background: #343a40; color: white; padding: 10px 20px; border-radius: 5px; }
        .header a { color: #ffc107; text-decoration: none; font-weight: bold; }
        .container { display: flex; gap: 20px; margin-top: 20px; }
        .form-box { background: white; padding: 20px; border-radius: 5px; border: 1px solid #ddd; width: 320px; height: fit-content; }
        .table-box { flex-grow: 1; background: white; padding: 20px; border-radius: 5px; border: 1px solid #ddd; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: center; }
        th { background: #28a745; color: white; }
        input[type="text"], input[type="number"], input[type="file"] { width: 100%; padding: 8px; margin: 5px 0 15px 0; box-sizing: border-box; }
        .btn { padding: 8px 12px; border: none; border-radius: 4px; cursor: pointer; color: white; text-decoration: none; font-size: 13px; display: inline-block; }
        .btn-green { background: #28a745; }
        .btn-blue { background: #0275d8; }
        .btn-red { background: #d9534f; }
        .alert-success { background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        .alert-danger { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        .img-thumb { width: 60px; height: 60px; object-fit: cover; border-radius: 4px; border: 1px solid #ccc; }
    </style>
</head>
<body>

    <div class="header">
        <h2>Panel de Productos - Administrador</h2>
        <div>
            <a href="crud_usuarios.php" class="btn btn-blue">👥 Usuarios</a>
            <a href="bitacora.php" class="btn" style="background: #17a2b8;">📋 Bitácora</a>
            <a href="bienvenido.php" style="margin-left: 10px;">Inicio</a> | 
            <a href="logout.php">Cerrar Sesión</a>
        </div>
    </div>

    <br>
    <?php if ($mensaje): ?><div class="alert-success"><?= htmlspecialchars($mensaje) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="container">
        <div class="form-box">
            <?php if ($prod_editar): ?>
                <h3>Editar Producto #<?= $prod_editar['id'] ?></h3>
                <form method="POST" action="crud_productos.php" enctype="multipart/form-data">
                    <input type="hidden" name="id_editar" value="<?= $prod_editar['id'] ?>">
                    
                    <label>Producto:</label>
                    <input type="text" name="producto" value="<?= htmlspecialchars($prod_editar['producto']) ?>" required>
                    
                    <label>Precio Base ($):</label>
                    <input type="number" step="0.01" name="precio" value="<?= $prod_editar['precio'] ?>" required min="0">

                    <label>Stock:</label>
                    <input type="number" name="stock" value="<?= $prod_editar['stock'] ?>" required min="0">
                    
                    <label>Cambiar Imagen (opcional):</label>
                    <input type="file" name="imagen" accept="image/*">
                    
                    <button type="submit" name="actualizar_producto" class="btn btn-blue">Guardar Cambios</button>
                    <a href="crud_productos.php" class="btn btn-red">Cancelar</a>
                </form>
            <?php else: ?>
                <h3>Agregar Nuevo Producto</h3>
                <form method="POST" action="crud_productos.php" enctype="multipart/form-data">
                    <label>Nombre del Producto:</label>
                    <input type="text" name="producto" placeholder="Ej: Laptop HP" required>
                    
                    <label>Precio Base ($):</label>
                    <input type="number" step="0.01" name="precio" placeholder="0.00" required min="0">

                    <label>Stock Inicial:</label>
                    <input type="number" name="stock" value="1" required min="0">
                    
                    <label>Imagen del Producto:</label>
                    <input type="file" name="imagen" accept="image/*">
                    
                    <button type="submit" name="crear_producto" class="btn btn-green">➕ Agregar Producto</button>
                </form>
            <?php endif; ?>
        </div>

        <div class="table-box">
            <h3>Inventario de Productos</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Imagen</th>
                        <th>Producto</th>
                        <th>Precio Base</th>
                        <th>Precio + IVA (16%)</th>
                        <th>Stock</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($productos)): ?>
                        <?php foreach ($productos as $p): ?>
                            <tr>
                                <td><?= $p['id'] ?></td>
                                <td>
                                    <?php if ($p['tiene_imagen']): ?>
                                        <img src="ver_imagen.php?id=<?= $p['id'] ?>" class="img-thumb" alt="prod">
                                    <?php else: ?>
                                        <span style="color: #aaa; font-size: 12px;">Sin imagen</span>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?= htmlspecialchars($p['producto']) ?></strong></td>
                                <td>$<?= number_format($p['precio'], 2) ?></td>
                                <td style="color: #28a745; font-weight: bold;">$<?= number_format($p['precio'] * 1.16, 2) ?></td>
                                <td><?= $p['stock'] ?></td>
                                <td>
                                    <a href="crud_productos.php?editar=<?= $p['id'] ?>" class="btn btn-blue">Editar</a>
                                    <a href="crud_productos.php?eliminar=<?= $p['id'] ?>" class="btn btn-red" onclick="return confirm('¿Deseas eliminar este producto?');">Eliminar</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7">No hay productos registrados.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>