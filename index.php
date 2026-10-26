<?php
session_start();
require_once 'conexion.php';

$error_login = '';
$error_registro = '';
$exito_registro = '';

// 1. PROCESAR INICIO DE SESIÓN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion_login'])) {
    $usuario  = trim($_POST['usuario']);
    $password = trim($_POST['password']);

    if (!empty($usuario) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT id, usuario, password, rol FROM usuarios WHERE usuario = :usuario");
        $stmt->execute(['usuario' => $usuario]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && $password === $user['password']) {
            // REGISTRAR ACCESO EXITOSO EN LA BITÁCORA
            $stmt_bitacora = $pdo->prepare("INSERT INTO bitacora (usuario_id, usuario_ingresado, estado) VALUES (:uid, :u, 'Exitoso')");
            $stmt_bitacora->execute(['uid' => $user['id'], 'u' => $user['usuario']]);

            $_SESSION['usuario_id'] = $user['id'];
            $_SESSION['usuario']    = $user['usuario'];
            $_SESSION['rol']        = $user['rol'];

            if ($_SESSION['rol'] === 'admin') {
                header("Location: crud_usuarios.php");
            } else {
                header("Location: bienvenido.php");
            }
            exit;
        } else {
            // REGISTRAR INTENTO FALLIDO EN LA BITÁCORA
            $uid = $user ? $user['id'] : null; // Si el usuario existe, guarda su ID; si no, queda como NULL
            $stmt_bitacora = $pdo->prepare("INSERT INTO bitacora (usuario_id, usuario_ingresado, estado) VALUES (:uid, :u, 'Fallido')");
            $stmt_bitacora->execute(['uid' => $uid, 'u' => $usuario]);

            $error_login = "Usuario o contraseña incorrectos.";
        }
    } else {
        $error_login = "Por favor, llena todos los campos.";
    }
}

// 2. PROCESAR REGISTRO DE NUEVO USUARIO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion_registro'])) {
    $nuevo_usuario = trim($_POST['reg_usuario']);
    $nueva_password = trim($_POST['reg_password']);
    $rol_predeterminado = 'usuario';

    if (!empty($nuevo_usuario) && !empty($nueva_password)) {
        $stmt_check = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = :usuario");
        $stmt_check->execute(['usuario' => $nuevo_usuario]);
        
        if ($stmt_check->fetch()) {
            $error_registro = "El nombre de usuario ya está registrado. Elige otro.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO usuarios (usuario, password, rol) VALUES (:u, :p, :r)");
            if ($stmt->execute(['u' => $nuevo_usuario, 'p' => $nueva_password, 'r' => $rol_predeterminado])) {
                $exito_registro = "¡Registro exitoso! Ahora puedes iniciar sesión.";
            } else {
                $error_registro = "Ocurrió un error al registrar la cuenta.";
            }
        }
    } else {
        $error_registro = "Por favor, completa todos los campos de registro.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Acceso al Sistema</title>
    <style>
        body { font-family: Arial, sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; background: #f4f4f9; margin: 0; }
        .box { background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); width: 320px; }
        .error { color: #d9534f; background: #f2dede; padding: 10px; border-radius: 4px; margin-bottom: 15px; font-size: 14px; text-align: center; }
        .exito { color: #3c763d; background: #dff0d8; padding: 10px; border-radius: 4px; margin-bottom: 15px; font-size: 14px; text-align: center; }
        input[type="text"], input[type="password"] { width: 100%; padding: 8px; margin: 8px 0; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background: #0275d8; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 15px; }
        button:hover { background: #025aa5; }
        button.btn-green { background: #5cb85c; }
        button.btn-green:hover { background: #4cae4c; }
        .tabs { display: flex; margin-bottom: 20px; border-bottom: 2px solid #ddd; }
        .tab-btn { flex: 1; padding: 10px; text-align: center; cursor: pointer; font-weight: bold; color: #555; background: none; border: none; border-bottom: 2px solid transparent; }
        .tab-btn.active { color: #0275d8; border-bottom-color: #0275d8; }
        .form-section { display: none; }
        .form-section.active { display: block; }
    </style>
</head>
<body>
    <div class="box">
        <div class="tabs">
            <button id="tab-login" class="tab-btn active" onclick="mostrarFormulario('login')">Ingresar</button>
            <button id="tab-registro" class="tab-btn" onclick="mostrarFormulario('registro')">Registrarse</button>
        </div>

        <div id="form-login" class="form-section active">
            <h2>Iniciar Sesión</h2>
            <?php if (!empty($error_login)): ?>
                <div class="error"><?= htmlspecialchars($error_login) ?></div>
            <?php endif; ?>
            <form method="POST" action="index.php">
                <input type="hidden" name="accion_login" value="1">
                <label>Usuario:</label>
                <input type="text" name="usuario" required>
                <label>Contraseña:</label>
                <input type="password" name="password" required>
                <button type="submit">Ingresar</button>
            </form>
        </div>

        <div id="form-registro" class="form-section">
            <h2>Crear Cuenta</h2>
            <?php if (!empty($error_registro)): ?>
                <div class="error"><?= htmlspecialchars($error_registro) ?></div>
            <?php endif; ?>
            <?php if (!empty($exito_registro)): ?>
                <div class="exito"><?= htmlspecialchars($exito_registro) ?></div>
            <?php endif; ?>
            <form method="POST" action="index.php">
                <input type="hidden" name="accion_registro" value="1">
                <label>Nuevo Usuario:</label>
                <input type="text" name="reg_usuario" required>
                <label>Nueva Contraseña:</label>
                <input type="password" name="reg_password" required>
                <button type="submit" class="btn-green">Registrarse</button>
            </form>
        </div>
    </div>

    <script>
        function mostrarFormulario(tipo) {
            document.getElementById('form-login').classList.remove('active');
            document.getElementById('form-registro').classList.remove('active');
            document.getElementById('tab-login').classList.remove('active');
            document.getElementById('tab-registro').classList.remove('active');

            if (tipo === 'login') {
                document.getElementById('form-login').classList.add('active');
                document.getElementById('tab-login').classList.add('active');
            } else {
                document.getElementById('form-registro').classList.add('active');
                document.getElementById('tab-registro').classList.add('active');
            }
        }

        <?php if (!empty($error_registro) || !empty($exito_registro)): ?>
            mostrarFormulario('registro');
        <?php endif; ?>
    </script>
</body>
</html>