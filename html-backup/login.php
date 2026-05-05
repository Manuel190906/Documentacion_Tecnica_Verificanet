<?php
require_once 'includes/config.php';

if (esta_logueado()) {
    switch ($_SESSION['rol']) {
        case 'cliente':  redirigir('servicios.php'); break;
        case 'empleado': redirigir('empleado.php'); break;
        case 'tecnico':  redirigir('tecnico.php'); break;
        case 'admin':    redirigir('admin.php'); break;
    }
}
$error = isset($_GET['error']) ? $_GET['error'] : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login - Verificanet</title>
    <link rel="stylesheet" href="css/forms.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <h1>VERIFICANET</h1>
                <p>Acceso al Sistema</p>
            </div>
            <?php if ($error == 'credenciales'): ?>
                <div class="error-msg">Usuario o contraseña incorrectos</div>
            <?php endif; ?>
            <form action="procesar/login.php" method="POST" class="login-form">
                <div class="form-group">
                    <label>Usuario:</label>
                    <input type="text" name="usuario" required autofocus>
                </div>
                <div class="form-group">
                    <label>Contraseña:</label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Iniciar Sesión</button>
            </form>
            <div style="text-align:center; margin-top:16px;">
                <a href="registro.php" style="color:#2563eb;">Crear cuenta nueva</a>
            </div>
            <div class="login-footer">
                <a href="index.php">← Volver al inicio</a>
            </div>
        </div>
    </div>
</body>
</html>
