<?php
error_reporting(0);
require_once 'includes/config.php';

// Verificar que está logueado (cualquier rol)
if (!esta_logueado()) {
    redirigir('login.php');
}

$conn = conectar_bd();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    redirigir('login.php');
}

// Obtener incidencia con detalle
$stmt = $conn->prepare("
    SELECT i.*, 
           c.nombre as cliente_nombre, c.email as cliente_email, c.telefono as cliente_tel,
           u.nombre as empleado_nombre, u.email as empleado_email
    FROM incidencias i
    LEFT JOIN clientes c ON i.id_cliente = c.id_cliente
    LEFT JOIN usuarios u ON i.id_empleado_asignado = u.id_usuario
    WHERE i.id_incidencia = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$inc = $stmt->get_result()->fetch_assoc();

if (!$inc) {
    echo '<p style="text-align:center; padding:40px;">Incidencia no encontrada.</p>';
    exit();
}

// Control de acceso por rol
$rol = $_SESSION['rol'];
if ($rol === 'cliente') {
    // Cliente solo puede ver sus propias incidencias
    $id_usuario = $_SESSION['usuario_id'];
    $row_cli = $conn->query("SELECT id_cliente FROM usuarios WHERE id_usuario=$id_usuario")->fetch_assoc();
    $id_cli = $row_cli['id_cliente'] ?? 0;
    if ($inc['id_cliente'] != $id_cli) {
        redirigir('servicios.php');
    }
}

// Enlace de vuelta según rol
$back = ['admin'=>'admin.php','tecnico'=>'tecnico.php','empleado'=>'empleado.php','cliente'=>'servicios.php?vista=incidencias'];
$back_url = $back[$rol] ?? 'login.php';

$estado_labels = ['reportada'=>'Reportada','en_proceso'=>'En Proceso','resuelta'=>'Resuelta','cerrada'=>'Cerrada'];
$estado_colores = ['reportada'=>'#dbeafe:#1e40af','en_proceso'=>'#fef3c7:#92400e','resuelta'=>'#d1fae5:#065f46','cerrada'=>'#f3f4f6:#374151'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Incidencia #<?= $inc['id_incidencia'] ?> - Verificanet</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <style>
        .detail-grid { display:grid; grid-template-columns:2fr 1fr; gap:24px; }
        @media (max-width:768px) { .detail-grid { grid-template-columns:1fr; } }
        .info-section { background:#f0f9ff; border:1px solid #bae6fd; border-radius:10px; padding:20px; margin-bottom:16px; }
        .info-section h4 { color:#0369a1; margin:0 0 12px; font-size:14px; text-transform:uppercase; letter-spacing:0.05em; }
        .info-row { display:flex; gap:12px; margin-bottom:10px; }
        .info-label { font-weight:600; color:#374151; min-width:130px; font-size:14px; }
        .info-value { color:#1e3a8a; font-size:14px; }
        .estado-badge { padding:6px 14px; border-radius:14px; font-size:13px; font-weight:600; display:inline-block; }
        .estado-reportada { background:#dbeafe; color:#1e40af; }
        .estado-en_proceso { background:#fef3c7; color:#92400e; }
        .estado-resuelta { background:#d1fae5; color:#065f46; }
        .estado-cerrada { background:#f3f4f6; color:#374151; }
        .prioridad-critica { color:#dc2626; font-weight:700; }
        .prioridad-alta { color:#d97706; font-weight:600; }
        .prioridad-media { color:#2563eb; }
        .prioridad-baja { color:#6b7280; }
        .descripcion-box { background:#fff; border:1px solid #dbeafe; border-radius:10px; padding:20px; line-height:1.7; color:#374151; }
        .notas-box { background:#fef9c3; border:1px solid #fde68a; border-radius:10px; padding:20px; line-height:1.7; color:#374151; }
    </style>
</head>
<body>
    <header class="main-header">
        <div class="container">
            <div class="logo">
                <h1>VERIFICANET</h1>
                <span class="tagline">Detalle de Incidencia</span>
            </div>
            <nav class="main-nav">
                <span class="nav-user"><?= htmlspecialchars($_SESSION['usuario_nombre']) ?></span>
                <a href="logout.php" class="btn-logout">Cerrar Sesión</a>
            </nav>
        </div>
    </header>

    <main class="dashboard-main">
        <div class="container">
            <!-- Cabecera -->
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:12px;">
                <div>
                    <a href="<?= $back_url ?>" style="color:#2563eb; text-decoration:none; font-size:14px;">← Volver</a>
                    <h1 style="color:#1e3a8a; margin:8px 0 4px;">Incidencia #<?= $inc['id_incidencia'] ?></h1>
                    <p style="color:#6b7280; font-size:14px;">Creada el <?= date('d/m/Y H:i', strtotime($inc['fecha_creacion'])) ?></p>
                </div>
                <span class="estado-badge estado-<?= $inc['estado'] ?>"><?= $estado_labels[$inc['estado']] ?? ucfirst($inc['estado']) ?></span>
            </div>

            <div class="detail-grid">
                <!-- Columna izquierda -->
                <div>
                    <div class="card" style="margin-bottom:20px;">
                        <div class="card-header">
                            <h2><?= htmlspecialchars($inc['titulo']) ?></h2>
                        </div>
                        <div style="padding:20px;">
                            <h4 style="color:#1e3a8a; margin-bottom:10px;">Descripción</h4>
                            <div class="descripcion-box">
                                <?= nl2br(htmlspecialchars($inc['descripcion'] ?? 'Sin descripción.')) ?>
                            </div>

                            <?php if (!empty($inc['notas_resolucion'])): ?>
                                <h4 style="color:#1e3a8a; margin:20px 0 10px;">Notas de Resolución</h4>
                                <div class="notas-box">
                                    <?= nl2br(htmlspecialchars($inc['notas_resolucion'])) ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($inc['fecha_resolucion'])): ?>
                                <p style="margin-top:16px; font-size:14px; color:#6b7280;">
                                    <strong>Fecha de resolución:</strong> <?= date('d/m/Y H:i', strtotime($inc['fecha_resolucion'])) ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Columna derecha -->
                <div>
                    <div class="info-section">
                        <h4>Estado e Información</h4>
                        <div class="info-row">
                            <span class="info-label">Estado:</span>
                            <span class="info-value"><span class="estado-badge estado-<?= $inc['estado'] ?>"><?= $estado_labels[$inc['estado']] ?? ucfirst($inc['estado']) ?></span></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Prioridad:</span>
                            <span class="info-value prioridad-<?= $inc['prioridad'] ?>"><?= ucfirst($inc['prioridad']) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Creada:</span>
                            <span class="info-value"><?= date('d/m/Y H:i', strtotime($inc['fecha_creacion'])) ?></span>
                        </div>
                    </div>

                    <?php if (!empty($inc['cliente_nombre'])): ?>
                        <div class="info-section">
                            <h4>Cliente</h4>
                            <div class="info-row">
                                <span class="info-label">Nombre:</span>
                                <span class="info-value"><?= htmlspecialchars($inc['cliente_nombre']) ?></span>
                            </div>
                            <?php if (!empty($inc['cliente_email'])): ?>
                                <div class="info-row">
                                    <span class="info-label">Email:</span>
                                    <span class="info-value"><?= htmlspecialchars($inc['cliente_email']) ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($inc['cliente_tel'])): ?>
                                <div class="info-row">
                                    <span class="info-label">Teléfono:</span>
                                    <span class="info-value"><?= htmlspecialchars($inc['cliente_tel']) ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div class="info-section">
                        <h4>Asignación</h4>
                        <div class="info-row">
                            <span class="info-label">Técnico:</span>
                            <span class="info-value"><?= htmlspecialchars($inc['empleado_nombre'] ?? 'Sin asignar') ?></span>
                        </div>
                        <?php if (!empty($inc['empleado_email'])): ?>
                            <div class="info-row">
                                <span class="info-label">Email:</span>
                                <span class="info-value"><?= htmlspecialchars($inc['empleado_email']) ?></span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Botones de acción según rol -->
                    <?php if ($rol === 'admin' || $rol === 'tecnico'): ?>
                        <div style="margin-top:10px;">
                            <a href="<?= $back_url ?>" class="btn btn-outline" style="display:block; text-align:center; margin-bottom:10px;">← Volver al panel</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
