<?php
require_once '../includes/config.php';
requiere_rol('empleado');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_cliente = $_POST['id_cliente'];
    $prioridad = $_POST['prioridad'];
    $titulo = $_POST['titulo'];
    $descripcion = $_POST['descripcion'];
    $id_empleado = $_SESSION['usuario_id'];
    
    $conn = conectar_bd();
    
    // Insertar incidencia
    $stmt = $conn->prepare("
        INSERT INTO incidencias 
        (titulo, descripcion, prioridad, id_cliente, id_empleado, id_empleado_asignado, estado, fecha_creacion) 
        VALUES (?, ?, ?, ?, ?, ?, 'reportada', NOW())
    ");
    
    $stmt->bind_param("sssiii", $titulo, $descripcion, $prioridad, $id_cliente, $id_empleado, $id_empleado);
    
    if ($stmt->execute()) {
        redirigir('../empleado.php?msg=creada');
    } else {
        redirigir('../empleado.php?error=crear');
    }
    
    $stmt->close();
    $conn->close();
} else {
    redirigir('../empleado.php');
}
?>
