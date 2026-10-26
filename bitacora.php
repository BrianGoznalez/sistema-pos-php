<?php
session_start();
require_once 'conexion.php';

// Control de acceso estricto: solo superusuario/admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: index.php");
    exit;
}

// Obtener los registros de la bitácora ordenados por los más recientes primero
$stmt = $pdo->query("SELECT id, usuario_id, usuario_ingresado, estado, fecha FROM bitacora ORDER BY fecha DESC");
$registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Bitácora de Accesos</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f8f9fa; }
        .header { display: flex; justify-content: space-between; align-items: center; background: #343a40; color: white; padding: 10px 20px; border-radius: 5px; }
        .header a { color: #ffc107; text-decoration: none; font-weight: bold; }
        .container { margin-top: 20px; background: white; padding: 20px; border-radius: 5px; border: 1px solid #ddd; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: center; }
        th { background: #17a2b8; color: white; }
        .badge-exito { background: #d4edda; color: #155724; padding: 4px 8px; border-radius: 4px; font-weight: bold; }
        .badge-fallido { background: #f8d7da; color: #721c24; padding: 4px 8px; border-radius: 4px; font-weight: bold; }
        .btn { padding: 8px 12px; background: #0275d8; color: white; border-radius: 4px; text-decoration: none; font-size: 14px; }
    </style>
</head>
<body>

    <div class="header">
        <h2>Bitácora de Accesos al Sistema</h2>
        <div>
            <a href="crud_usuarios.php" class="btn">👥 Volver a Gestión de Usuarios</a>
            <a href="logout.php" style="margin-left: 10px;">Cerrar Sesión</a>
        </div>
    </div>

    <div class="container">
        <h3>Historial de Intentos de Ingreso</h3>
        <table>
            <thead>
                <tr>
                    <th># Bitácora</th>
                    <th>ID Usuario</th>
                    <th>Usuario Ingresado</th>
                    <th>Estado</th>
                    <th>Fecha y Hora</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($registros)): ?>
                    <?php foreach ($registros as $row): ?>
                        <tr>
                            <td><?= $row['id'] ?></td>
                            <td><?= $row['usuario_id'] ? $row['usuario_id'] : '<i>N/A</i>' ?></td>
                            <td><?= htmlspecialchars($row['usuario_ingresado']) ?></td>
                            <td>
                                <?php if ($row['estado'] === 'Exitoso'): ?>
                                    <span class="badge-exito">Exitoso</span>
                                <?php else: ?>
                                    <span class="badge-fallido">Fallido</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $row['fecha'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5">No hay registros de accesos aún.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</body>
</html>