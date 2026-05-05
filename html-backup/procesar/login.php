<?php
require_once '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['usuario'];
    $password = $_POST['password'];
    $conn = conectar_bd();
    
    $stmt = $conn->prepare("
        SELECT u.id_usuario, u.nombre, u.password, u.rol, u.id_empleado,
               d.nombre as departamento
        FROM usuarios u
        LEFT JOIN empleados e ON u.id_empleado = e.id_empleado
        LEFT JOIN departamentos d ON e.id_departamento = d.id_departamento
        WHERE u.username = ?
    ");
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    if ($resultado->num_rows === 1) {
        $user = $resultado->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['usuario_id'] = $user['id_usuario'];
            $_SESSION['usuario_nombre'] = $user['nombre'];
            $_SESSION['rol'] = $user['rol'];
            $_SESSION['departamento'] = $user['departamento'];

            switch ($user['rol']) {
                case 'cliente':
                    redirigir('../servicios.php');
                    break;
                case 'tecnico':
                    redirigir('../tecnico.php');
                    break;
                case 'admin':
                    redirigir('../admin.php');
                    break;
                case 'empleado':
                    switch ($user['departamento']) {
                        case 'Ventas':
                            redirigir('../ventas.php');
                            break;
                        case 'Administración':
                            redirigir('../administracion.php');
                            break;
                        case 'Auditoría':
                            redirigir('../auditoria.php');
                            break;
                        default:
                            redirigir('../empleado.php');
                            break;
                    }
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
} else {
    redirigir('../login.php');
}