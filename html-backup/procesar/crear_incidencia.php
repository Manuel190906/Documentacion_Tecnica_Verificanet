<?php
require_once '../includes/config.php';
requiere_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_cliente   = escapeshellarg($_POST['id_cliente']);
    $prioridad    = escapeshellarg($_POST['prioridad']);
    $titulo       = escapeshellarg($_POST['titulo']);
    $descripcion  = escapeshellarg($_POST['descripcion']);
    // $id_empleado  = escapeshellarg($_SESSION['usuario_id']);
    $id_empleado  = escapeshellarg($_SESSION['usuario_id'] ?? '0');

    // Llamar al script Python con los datos como argumentos
    $python = '/usr/bin/python3';
    $script = '/var/www/html/procesar/incidencias.py';

    $cmd = "$python $script $id_cliente $prioridad $titulo $descripcion $id_empleado 2>&1";
    $output = shell_exec($cmd);

    // Si el Python devuelve OK redirigir al dashboard
    if ($output !== null && strpos($output, 'OK') !== false) {
        header('Location: ../empleado.php?msg=creada');
        exit;
    } else {
        // Guardamos el error real en el log del sistema para que no sea "misterioso"
        error_log("Fallo en incidencias.py. Salida: " . ($output ?: 'Sin respuesta'));
        header('Location: ../empleado.php?error=crear');
        exit;
    }
} else {
    header('Location: ../empleado.php');
    exit;
}
?>