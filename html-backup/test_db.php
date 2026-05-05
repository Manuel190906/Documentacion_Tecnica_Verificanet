<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Intentando conectar...<br>";

$conn = new mysqli('192.168.60.50', 'verificanet_user', 'Verific@2024!', 'verificanet_servicios');

if ($conn->connect_error) {
    die(" Error de conexión: " . $conn->connect_error);
}

echo " Conexión exitosa a la BD<br>";

$result = $conn->query("SELECT username, nombre, rol FROM usuarios LIMIT 3");
while ($row = $result->fetch_assoc()) {
    echo "Usuario: {$row['username']} - {$row['nombre']} - {$row['rol']}<br>";
}

$conn->close();
?>
