<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Principal</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #e0eafc 0%, #cfdef3 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .card {
            background: #ffffff;
            width: 100%;
            max-width: 480px;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            padding: 35px 30px;
            text-align: center;
            transition: transform 0.2s ease;
        }
        .user-avatar {
            width: 75px;
            height: 75px;
            background: #0275d8;
            color: #ffffff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            font-weight: bold;
            margin: 0 auto 15px auto;
            box-shadow: 0 4px 10px rgba(2, 117, 216, 0.3);
        }
        h1 {
            color: #2c3e50;
            font-size: 24px;
            margin-bottom: 8px;
        }
        p.subtitle {
            color: #6c757d;
            font-size: 14px;
            margin-bottom: 25px;
        }
        .badge-role {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 20px;
        }
        .badge-admin {
            background-color: #d4edda;
            color: #155724;
        }
        .badge-user {
            background-color: #cce5ff;
            color: #004085;
        }
        .menu-section {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 25px;
        }
        .btn-link {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 12px 20px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            font-size: 15px;
            color: #ffffff;
            transition: all 0.2s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        .btn-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
        }
        .btn-blue { background-color: #0275d8; }
        .btn-green { background-color: #28a745; }
        .btn-info { background-color: #17a2b8; }
        .btn-warning { background-color: #ffc107; color: #212529; }

        .admin-box {
            background: #f8f9fa;
            border: 1px dashed #ced4da;
            border-radius: 12px;
            padding: 18px 15px;
            margin-top: 10px;
        }
        .admin-title {
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #495057;
            margin-bottom: 12px;
            font-weight: 700;
        }
        .btn-logout {
            display: inline-block;
            color: #dc3545;
            font-weight: 600;
            text-decoration: none;
            font-size: 14px;
            padding: 8px 16px;
            border-radius: 8px;
            transition: background 0.2s ease;
        }
        .btn-logout:hover {
            background-color: #ffe6e6;
        }
    </style>
</head>
<body>

    <div class="card">
        <div class="user-avatar">
            <?= strtoupper(substr($_SESSION['usuario'], 0, 1)) ?>
        </div>

        <h1>¡Bienvenido, <?= htmlspecialchars($_SESSION['usuario']) ?>!</h1>
        <p class="subtitle">Has iniciado sesión correctamente en el sistema.</p>

        <span class="badge-role <?= $_SESSION['rol'] === 'admin' ? 'badge-admin' : 'badge-user' ?>">
            Rol: <?= htmlspecialchars($_SESSION['rol']) ?>
        </span>

        <div class="menu-section">
            <!-- Acceso a la Tienda para todos -->
            <a href="carrito.php" class="btn-link btn-blue">
                🛒 Ir a la Tienda / Carrito de Compras
            </a>

            <!-- Accesos para Administrador -->
            <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin'): ?>
                <div class="admin-box">
                    <div class="admin-title">Herramientas de Administración</div>
                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        <a href="crud_usuarios.php" class="btn-link btn-green">
                            👥 Gestión de Usuarios (CRUD)
                        </a>
                        <a href="crud_productos.php" class="btn-link btn-info">
                            📦 Gestión de Productos
                        </a>
                        <a href="graficas.php" class="btn-link btn-warning">
                            📊 Estadísticas y Gráficas de Ventas
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <a href="logout.php" class="btn-logout">🚪 Cerrar Sesión</a>
    </div>

</body>
</html>