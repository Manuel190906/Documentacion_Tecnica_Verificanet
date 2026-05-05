<?php
require_once __DIR__ . '/config.php';

function esta_logueado() {
    return isset($_SESSION['usuario_id']);
}

function tiene_rol($rol) {
    return isset($_SESSION['rol']) && $_SESSION['rol'] === $rol;
}

function redirigir($url) {
    header("Location: $url");
    exit();
}

function requiere_login() {
    if (!esta_logueado()) {
        redirigir('/login.php');
    }
}

function requiere_rol($rol) {
    requiere_login();
    if (!tiene_rol($rol)) {
        redirigir('/login.php');
    }
}