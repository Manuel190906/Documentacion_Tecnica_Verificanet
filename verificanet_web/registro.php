<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/config.php';

// Si ya está logueado, redirigir
if (esta_logueado()) {
    redirigir('servicios.php');
}

$error = '';
$exito = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre    = trim($_POST['nombre'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $telefono  = trim($_POST['telefono'] ?? '');
    $empresa   = trim($_POST['empresa'] ?? '');
    $usuario   = trim($_POST['usuario'] ?? '');
    $password  = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    // Validaciones
    if (!$nombre || !$email || !$usuario || !$password) {
        $error = 'Por favor rellena todos los campos obligatorios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'El email no tiene un formato válido.';
    } elseif (strlen($password) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres.';
    } elseif ($password !== $password2) {
        $error = 'Las contraseñas no coinciden.';
    } else {
        $conn = conectar_bd();

        // Verificar que el usuario no existe ya
        $stmt = $conn->prepare("SELECT id_usuario FROM usuarios WHERE username = ?");
        $stmt->bind_param("s", $usuario);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = 'Ese nombre de usuario ya está en uso. Elige otro.';
        } else {
            $stmt->close();

            // Verificar que el email no existe ya
            $stmt2 = $conn->prepare("SELECT id_cliente FROM clientes WHERE email = ?");
            $stmt2->bind_param("s", $email);
            $stmt2->execute();
            $stmt2->store_result();

            if ($stmt2->num_rows > 0) {
                $error = 'Ya existe una cuenta con ese email.';
            } else {
                $stmt2->close();

                // Insertar cliente en tabla clientes
                $stmt3 = $conn->prepare("INSERT INTO clientes (nombre, email, telefono, nombre_empresa) VALUES (?, ?, ?, ?)");
                $stmt3->bind_param("ssss", $nombre, $email, $telefono, $empresa);
                $stmt3->execute();
                $id_cliente = $conn->insert_id;
                $stmt3->close();

                // Insertar usuario en tabla usuarios
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt4 = $conn->prepare("INSERT INTO usuarios (username, password, nombre, email, rol, id_cliente) VALUES (?, ?, ?, ?, 'cliente', ?)");
                $stmt4->bind_param("ssssi", $usuario, $hash, $nombre, $email, $id_cliente);
                $stmt4->execute();
                $stmt4->close();

                $exito = '¡Cuenta creada correctamente! Ya puedes iniciar sesión.';
            }
        }

        if (isset($conn) && $conn) $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Verificanet</title>
    <link rel="stylesheet" href="css/forms.css">
<style>
    .login-container { max-width: 780px; align-items: flex-start; padding: 30px 20px; }
    .register-box { max-width: 780px; width: 100%; }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    @media (max-width: 480px) { .form-row { grid-template-columns: 1fr; } }
    .required { color: #dc2626; }
    .hint { font-size: 12px; color: #6b7280; margin-top: 4px; }
</style>
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-box register-box">
            <div class="login-header">
                <h1>VERIFICANET</h1>
                <p>Crear cuenta nueva</p>
            </div>

            <?php if ($error): ?>
                <div class="error-msg"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($exito): ?>
                <div style="background:#d1fae5; color:#065f46; padding:14px; border-radius:8px; margin-bottom:20px; text-align:center; border:1px solid #6ee7b7;">
                    <?= $exito ?>
                    <br><br>
                    <a href="login.php" style="color:#065f46; font-weight:600;">Ir al inicio de sesión →</a>
                </div>
            <?php else: ?>

            <form action="registro.php" method="POST" class="login-form">

                <p style="font-size:13px; color:#6b7280; margin-bottom:18px;">
                    Los campos marcados con <span class="required">*</span> son obligatorios.
                </p>

                <div class="form-row">
                    <div class="form-group">
                        <label>Nombre completo <span class="required">*</span></label>
                        <input type="text" name="nombre" value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>" required placeholder="Juan García">
                    </div>
                    <div class="form-group">
                        <label>Email <span class="required">*</span></label>
                        <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required placeholder="correo@empresa.com">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Teléfono</label>
                        <input type="tel" name="telefono" value="<?= htmlspecialchars($_POST['telefono'] ?? '') ?>" placeholder="600 000 000">
                    </div>
                    <div class="form-group">
                        <label>Empresa</label>
                        <input type="text" name="empresa" value="<?= htmlspecialchars($_POST['empresa'] ?? '') ?>" placeholder="Nombre de la empresa">
                    </div>
                </div>

                <div class="form-group">
                    <label>Nombre de usuario <span class="required">*</span></label>
                    <input type="text" name="usuario" value="<?= htmlspecialchars($_POST['usuario'] ?? '') ?>" required placeholder="usuario123" autocomplete="off">
                    <p class="hint">Solo letras, números y guiones. Sin espacios.</p>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Contraseña <span class="required">*</span></label>
                        <input type="password" name="password" required placeholder="Mínimo 6 caracteres">
                    </div>
                    <div class="form-group">
                        <label>Repetir contraseña <span class="required">*</span></label>
                        <input type="password" name="password2" required placeholder="Repite la contraseña">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    Crear Cuenta
                </button>
            </form>

            <?php endif; ?>

            <div class="login-footer" style="margin-top:20px;">
                <a href="login.php">← Volver al inicio de sesión</a>
            </div>
        </div>
    </div>
</body>
</html>
