<?php
require_once 'includes/config.php';

// Si ya está logueado, redirigir según rol
if (esta_logueado()) {
    switch ($_SESSION['rol']) {
        case 'cliente':
            redirigir('servicios.php');
            break;
        case 'empleado':
            redirigir('empleado.php');
            break;
        case 'tecnico':
            redirigir('tecnico.php');
            break;
        case 'admin':
            redirigir('admin.php');
            break;
    }
}

$error = isset($_GET['error']) ? $_GET['error'] : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
                <div class="error-msg">
                    Usuario o contraseña incorrectos
                </div>
            <?php endif; ?>
            
            <form action="procesar/login.php" method="POST" class="login-form">
                <div class="form-group">
                    <label for="usuario">Usuario:</label>
                    <input type="text" id="usuario" name="usuario" required autofocus>
                </div>
                
                <div class="form-group">
                    <label for="password">Contraseña:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">
                    Iniciar Sesión
                </button>
            </form>
            <div style="text-align:center; margin-top:16px;">
                <p style="color:#6b7280; font-size:14px;">¿No tienes cuenta?</p>
                <a href="registro.php" style="color:#2563eb; font-weight:600; font-size:14px;">Crear cuenta nueva</a>
            </div>
            
            <div class="login-footer">
                <a href="index.php">&larr; Volver al inicio</a>
            </div>
        </div>
    </div>
</body>
</html>
