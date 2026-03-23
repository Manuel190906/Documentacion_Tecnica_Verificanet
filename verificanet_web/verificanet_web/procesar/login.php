<?php
require_once '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['usuario'];
    $password = $_POST['password'];
    
    $conn = conectar_bd();
    
    // Buscar usuario
    $stmt = $conn->prepare("SELECT id_usuario, nombre, password, rol FROM usuarios WHERE usuario = ?");
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    if ($resultado->num_rows === 1) {
        $user = $resultado->fetch_assoc();
        
        // Verificar contraseña
        if (password_verify($password, $user['password'])) {
            // Login exitoso
            $_SESSION['usuario_id'] = $user['id_usuario'];
            $_SESSION['usuario_nombre'] = $user['nombre'];
            $_SESSION['rol'] = $user['rol'];
            
            // Redirigir según rol
            switch ($user['rol']) {
                case 'cliente':
                    redirigir('../servicios.php');
                    break;
                case 'empleado':
                    redirigir('../empleado.php');
                    break;
                case 'tecnico':
                    redirigir('../tecnico.php');
                    break;
                case 'admin':
                    redirigir('../admin.php');
                    break;
                default:
                    redirigir('../login.php?error=credenciales');
            }
        } else {
            redirigir('../login.php?error=credenciales');
        }
    } else {
        redirigir('../login.php?error=credenciales');
    }
    
    $stmt->close();
    $conn->close();
} else {
    redirigir('../login.php');
}
?>
