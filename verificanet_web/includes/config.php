<?php
// Configuración de la base de datos
define('DB_HOST', '192.168.60.50');
define('DB_NAME', 'verificanet_servicios');
define('DB_USER', 'verificanet_user');
define('DB_PASS', 'Verific@2024!');

// Conexión a base de datos
function conectar_bd() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("Error de conexión: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8");
    return $conn;
}

// Iniciar sesión segura
session_start();

// Función para verificar si está logueado
function esta_logueado() {
    return isset($_SESSION['usuario_id']);
}

// Función para verificar rol
function tiene_rol($rol) {
    return isset($_SESSION['rol']) && $_SESSION['rol'] === $rol;
}

// Función para redirigir
function redirigir($url) {
    header("Location: $url");
    exit();
}

// Función para proteger páginas
function requiere_login() {
    if (!esta_logueado()) {
        redirigir('login.php');
    }
}

// Función para proteger por rol
function requiere_rol($rol) {
    requiere_login();
    if (!tiene_rol($rol)) {
        redirigir('login.php');
    }
}
?>
