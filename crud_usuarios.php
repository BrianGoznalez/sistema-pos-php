<?php
session_start();
require_once 'conexion.php';

// Control de acceso: solo superusuarios/admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$mensaje = '';
$error = '';

// --- PROCESAR ACCIONES DEL CRUD ---

// 1. CREAR USUARIO
if (isset($_POST['crear'])) {
    $nuevo_usuario = trim($_POST['nuevo_usuario']);
    $nueva_pass    = trim($_POST['nueva_password']);
    $nuevo_rol     = trim($_POST['nuevo_rol']);

    if (!empty($nuevo_usuario) && !empty($nueva_pass)) {
        $stmt = $pdo->prepare("INSERT INTO usuarios (usuario, password, rol) VALUES (:u, :p, :r)");
        if ($stmt->execute(['u' => $nuevo_usuario, 'p' => $nueva_pass, 'r' => $nuevo_rol])) {
            $mensaje = "Usuario creado correctamente.";
        } else {
            $error = "No se pudo crear el usuario.";
        }
    } else {
        $error = "Completa los campos obligatorios para crear.";
    }
}

// 2. ELIMINAR USUARIO
if (isset($_GET['eliminar'])) {
    $id_eliminar = (int)$_GET['eliminar'];
    if ($id_eliminar !== $_SESSION['usuario_id']) {
        $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = :id");
        $stmt->execute(['id' => $id_eliminar]);
        $mensaje = "Usuario eliminado correctamente.";
    } else {
        $error = "No puedes eliminar tu propia cuenta activa.";
    }
}

// 3. ACTUALIZAR USUARIO
if (isset($_POST['actualizar'])) {
    $id_editar     = (int)$_POST['id_editar'];
    $editar_user   = trim($_POST['editar_usuario']);
    $editar_pass   = trim($_POST['editar_password']);
    $editar_rol    = trim($_POST['editar_rol']);

    if (!empty($editar_user)) {
        if (!empty($editar_pass)) {
            $stmt = $pdo->prepare("UPDATE usuarios SET usuario = :u, password = :p, rol = :r WHERE id = :id");
            $stmt->execute(['u' => $editar_user, 'p' => $editar_pass, 'r' => $editar_rol, 'id' => $id_editar]);
        } else {
            $stmt = $pdo->prepare("UPDATE usuarios SET usuario = :u, rol = :r WHERE id = :id");
            $stmt->execute(['u' => $editar_user, 'r' => $editar_rol, 'id' => $id_editar]);
        }
        $mensaje = "Usuario actualizado correctamente.";
    } else {
        $error = "El campo usuario no puede estar vacío.";
    }
}

// OBTENER DATOS PARA EDICIÓN
$usuario_editar = null;
if (isset($_GET['editar'])) {
    $id_edit = (int)$_GET['editar'];
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = :id");
    $stmt->execute(['id' => $id_edit]);
    $usuario_editar = $stmt->fetch(PDO::FETCH_ASSOC);
}

// 4. LEER TODOS LOS USUARIOS
$stmt = $pdo->query("SELECT id, usuario, password, rol FROM usuarios ORDER BY id ASC");
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Administración - CRUD</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f8f9fa; }
        .header { display: flex; justify-content: space-between; align-items: center; background: #343a40; color: white; padding: 10px 20px; border-radius: 5px; }
        .header a { color: #ffc107; text-decoration: none; font-weight: bold; }
        .container { display: flex; gap: 20px; margin-top: 20px; }
        .form-box { background: white; padding: 20px; border-radius: 5px; border: 1px solid #ddd; width: 300px; height: fit-content; }
        .table-box { flex-grow: 1; background: white; padding: 20px; border-radius: 5px; border: 1px solid #ddd; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background: #0275d8; color: white; }
        input[type="text"], input[type="password"], select { width: 100%; padding: 8px; margin: 5px 0 15px 0; box-sizing: border-box; }
        .btn { padding: 8px 12px; border: none; border-radius: 4px; cursor: pointer; color: white; text-decoration: none; font-size: 13px; display: inline-block; }
        .btn-green { background: #28a745; }
        .btn-blue { background: #0275d8; }
        .btn-red { background: #d9534f; }
        .btn-warning { background: #ffc107; color: #212529; font-weight: bold; }
        .alert-success { background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        .alert-danger { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
    </style>
</head>
<body>

    <div class="header">
        <h2>Panel CRUD - Super Usuario (<?= htmlspecialchars($_SESSION['usuario']) ?>)</h2>
        <div>
            <a href="crud_productos.php" class="btn btn-green" style="margin-right: 10px;">📦 Productos</a>
            <a href="graficas.php" class="btn btn-warning" style="margin-right: 10px;">📊 Gráficas</a>
            <a href="bitacora.php" class="btn" style="background: #17a2b8; margin-right: 10px;">📋 Bitácora</a>
            <a href="bienvenido.php">Ir a Inicio</a> | 
            <a href="logout.php">Cerrar Sesión</a>
        </div>
    </div>

    <br>
    <?php if ($mensaje): ?>
        <div class="alert-success"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="container">
        <!-- FORMULARIO: CREAR O EDITAR -->
        <div class="form-box">
            <?php if ($usuario_editar): ?>
                <h3>Editar Usuario #<?= $usuario_editar['id'] ?></h3>
                <form method="POST" action="crud_usuarios.php">
                    <input type="hidden" name="id_editar" value="<?= $usuario_editar['id'] ?>">
                    
                    <label>Usuario:</label>
                    <input type="text" name="editar_usuario" value="<?= htmlspecialchars($usuario_editar['usuario']) ?>" required>
                    
                    <label>Nueva Contraseña (dejar vacío para conservar):</label>
                    <input type="password" name="editar_password">
                    
                    <label>Rol:</label>
                    <select name="editar_rol">
                        <option value="usuario" <?= $usuario_editar['rol'] === 'usuario' ? 'selected' : '' ?>>Usuario</option>
                        <option value="admin" <?= $usuario_editar['rol'] === 'admin' ? 'selected' : '' ?>>Administrador</option>
                    </select>
                    
                    <button type="submit" name="actualizar" class="btn btn-blue">Guardar Cambios</button>
                    <a href="crud_usuarios.php" class="btn btn-red">Cancelar</a>
                </form>
            <?php else: ?>
                <h3>Registrar Nuevo Usuario</h3>
                <form method="POST" action="crud_usuarios.php">
                    <label>Usuario:</label>
                    <input type="text" name="nuevo_usuario" required>
                    
                    <label>Contraseña:</label>
                    <input type="password" name="nueva_password" required>
                    
                    <label>Rol:</label>
                    <select name="nuevo_rol">
                        <option value="usuario">Usuario</option>
                        <option value="admin">Administrador</option>
                    </select>
                    
                    <button type="submit" name="crear" class="btn btn-green">Crear Usuario</button>
                </form>
            <?php endif; ?>
        </div>

        <!-- TABLA DE LECTURA DE USUARIOS -->
        <div class="table-box">
            <h3>Usuarios Registrados</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Usuario</th>
                        <th>Contraseña</th>
                        <th>Rol</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $u): ?>
                        <tr>
                            <td><?= $u['id'] ?></td>
                            <td><?= htmlspecialchars($u['usuario']) ?></td>
                            <td><?= htmlspecialchars($u['password']) ?></td>
                            <td><?= htmlspecialchars($u['rol']) ?></td>
                            <td>
                                <a href="crud_usuarios.php?editar=<?= $u['id'] ?>" class="btn btn-blue">Editar</a>
                                <?php if ($u['id'] !== $_SESSION['usuario_id']): ?>
                                    <a href="crud_usuarios.php?eliminar=<?= $u['id'] ?>" class="btn btn-red" onclick="return confirm('¿Seguro de eliminar este usuario?');">Eliminar</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>