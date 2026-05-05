<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'includes/config.php';
requiere_rol('cliente');
$conn = conectar_bd();

// Obtener id_cliente vinculado al usuario
$id_usuario = $_SESSION['usuario_id'];
$row_cli = $conn->query("SELECT id_cliente FROM usuarios WHERE id_usuario=$id_usuario")->fetch_assoc();
$id_cliente = $row_cli['id_cliente'] ?? 0;

// Servicios del catálogo
$catalogo = $conn->query("SELECT * FROM servicios ORDER BY nombre");

// Contratos del cliente
$contratos = [];
if ($id_cliente) {
    $res = $conn->query("
        SELECT ct.*, s.nombre as servicio_nombre, s.descripcion as servicio_desc
        FROM contratos ct
        JOIN servicios s ON ct.id_servicio = s.id_servicio
        WHERE ct.id_cliente = $id_cliente
        ORDER BY ct.fecha_inicio DESC
    ");
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $contratos[] = $r;
        }
    }
}

// Incidencias del cliente
$incidencias = [];
if ($id_cliente) {
    $res = $conn->query("
        SELECT i.*, u.nombre as empleado_nombre
        FROM incidencias i
        LEFT JOIN usuarios u ON i.id_empleado_asignado = u.id_usuario
        WHERE i.id_cliente = $id_cliente
        ORDER BY i.fecha_creacion DESC
        LIMIT 10
    ");
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $incidencias[] = $r;
        }
    }
}

// Vista activa
$vista = isset($_GET['vista']) ? $_GET['vista'] : 'catalogo';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Servicios - Verificanet</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <style>
        .servicios-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 24px;
            margin-top: 20px;
        }

        .servicio-card {
            background: #fff;
            border: 1px solid #dbeafe;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.08);
        }

        .servicio-header {
            background: linear-gradient(135deg, #3b82f6, #60a5fa);
            padding: 20px;
        }

        .servicio-header h3 {
            color: #fff;
            font-size: 16px;
            margin: 0;
        }

        .servicio-body {
            padding: 20px;
        }

        .servicio-body p {
            color: #374151;
            font-size: 14px;
            margin-bottom: 8px;
        }

        .servicio-precio {
            font-size: 22px;
            font-weight: 700;
            color: #1e40af;
            margin: 10px 0;
        }

        .estado-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .estado-reportada {
            background: #dbeafe;
            color: #1e40af;
        }

        .estado-en_proceso {
            background: #fef3c7;
            color: #92400e;
        }

        .estado-resuelta {
            background: #d1fae5;
            color: #065f46;
        }

        .estado-cerrada {
            background: #f3f4f6;
            color: #374151;
        }

        .contrato-item {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 10px;
            padding: 16px;
            margin-bottom: 12px;
        }

        .contrato-item h4 {
            color: #0369a1;
            margin: 0 0 8px;
        }

        .contrato-item p {
            color: #374151;
            font-size: 14px;
            margin: 4px 0;
        }

        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }

        .tab {
            padding: 10px 22px;
            border-radius: 8px;
            text-decoration: none;
            color: #1e40af;
            border: 2px solid #bfdbfe;
            background: #fff;
            font-weight: 500;
            transition: all 0.2s;
        }

        .tab.active {
            background: #2563eb;
            color: #fff;
            border-color: #2563eb;
        }
    </style>
</head>

<body>
    <header class="main-header">
        <div class="container">
            <div class="logo">
                <h1>VERIFICANET</h1>
                <span class="tagline">Portal Cliente</span>
            </div>
            <nav class="main-nav">
                <span class="nav-user">Bienvenido, <?= htmlspecialchars($_SESSION['usuario_nombre']) ?></span>
                <a href="logout.php" class="btn-logout">Cerrar Sesión</a>
            </nav>
        </div>
    </header>

    <main class="dashboard-main">
        <div class="container">
            <!-- Tabs de navegación -->
            <div class="tabs">
                <a href="servicios.php?vista=catalogo" class="tab <?= $vista === 'catalogo' ? 'active' : '' ?>">Catálogo de Servicios</a>
                <a href="servicios.php?vista=contratos" class="tab <?= $vista === 'contratos' ? 'active' : '' ?>">Mis Contratos (<?= count($contratos) ?>)</a>
                <a href="servicios.php?vista=incidencias" class="tab <?= $vista === 'incidencias' ? 'active' : '' ?>">Mis Incidencias (<?= count($incidencias) ?>)</a>
            </div>

            <!-- Catálogo -->
            <?php if ($vista === 'catalogo'): ?>
                <div class="card">
                    <div class="card-header">
                        <h2>Catálogo de Servicios</h2>
                        <p style="color:#6b7280; margin-top:4px;">Servicios disponibles de Verificanet</p>
                    </div>
                    <div class="servicios-grid">
                        <?php if ($catalogo && $catalogo->num_rows > 0): ?>
                            <?php while ($srv = $catalogo->fetch_assoc()): ?>
                                <div class="servicio-card">
                                    <div class="servicio-header">
                                        <h3><?= htmlspecialchars($srv['nombre']) ?></h3>
                                    </div>
                                    <div class="servicio-body">
                                        <p><?= htmlspecialchars($srv['descripcion'] ?? 'Servicio profesional Verificanet') ?></p>
                                        <?php if (!empty($srv['precio'])): ?>
                                            <div class="servicio-precio"><?= number_format($srv['precio'], 2) ?>€<span style="font-size:14px; color:#6b7280;">/mes</span></div>
                                        <?php endif; ?>
                                        <p style="color:#2563eb; font-size:13px; margin-top:8px;">Contacta con nosotros para contratar</p>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <!-- Servicios por defecto si la tabla está vacía -->
                            <?php
                            $servicios_default = [
                                ['nombre' => 'Correo Corporativo', 'descripcion' => 'Buzones seguros con 50GB de almacenamiento', 'precio' => '29.00'],
                                ['nombre' => 'VPN Empresarial', 'descripcion' => 'Acceso remoto cifrado a la red corporativa', 'precio' => '19.00'],
                                ['nombre' => 'Cloud Storage', 'descripcion' => '500GB en la nube con alta disponibilidad', 'precio' => '49.00'],
                                ['nombre' => 'Backup Automático', 'descripcion' => 'Copias de seguridad diarias automáticas', 'precio' => '39.00'],
                                ['nombre' => 'Gestión Servidores', 'descripcion' => 'Administración y mantenimiento 24/7', 'precio' => '199.00'],
                                ['nombre' => 'Seguridad IT', 'descripcion' => 'Firewall avanzado y auditorías de seguridad', 'precio' => '89.00'],
                            ];
                            foreach ($servicios_default as $srv):
                            ?>
                                <div class="servicio-card">
                                    <div class="servicio-header">
                                        <h3><?= $srv['nombre'] ?></h3>
                                    </div>
                                    <div class="servicio-body">
                                        <p><?= $srv['descripcion'] ?></p>
                                        <div class="servicio-precio"><?= $srv['precio'] ?>€<span style="font-size:14px; color:#6b7280;">/mes</span></div>
                                        <p style="color:#2563eb; font-size:13px; margin-top:8px;">Contacta con nosotros para contratar</p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Contratos -->
            <?php elseif ($vista === 'contratos'): ?>
                <div class="card">
                    <div class="card-header">
                        <h2>Mis Contratos Activos</h2>
                    </div>
                    <?php if (empty($contratos)): ?>
                        <div style="padding:40px; text-align:center; color:#6b7280;">
                            <p>No tienes contratos activos actualmente.</p>
                            <a href="servicios.php?vista=catalogo" class="btn btn-primary" style="margin-top:16px; display:inline-block;">Ver Catálogo</a>
                        </div>
                    <?php else: ?>
                        <div style="padding:20px;">
                            <?php foreach ($contratos as $ct): ?>
                                <div class="contrato-item">
                                    <h4><?= htmlspecialchars($ct['servicio_nombre']) ?></h4>
                                    <p><?= htmlspecialchars($ct['servicio_desc'] ?? '') ?></p>
                                    <?php if (!empty($ct['fecha_inicio'])): ?>
                                        <p><strong>Inicio:</strong> <?= date('d/m/Y', strtotime($ct['fecha_inicio'])) ?></p>
                                    <?php endif; ?>
                                    <?php if (!empty($ct['fecha_fin'])): ?>
                                        <p><strong>Fin:</strong> <?= date('d/m/Y', strtotime($ct['fecha_fin'])) ?></p>
                                    <?php endif; ?>
                                    <?php if (!empty($ct['estado'])): ?>
                                        <p><strong>Estado:</strong> <span class="estado-badge estado-resuelta"><?= ucfirst($ct['estado']) ?></span></p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Incidencias -->
            <?php elseif ($vista === 'incidencias'): ?>
                <div class="card">
                    <div class="card-header">
                        <h2>Mis Incidencias</h2>
                    </div>
                    <!-- Botón de Manuales para Clientes -->
                    <div style="background: #d1ecf1; border-left: 4px solid #17a2b8; padding: 20px; margin: 20px 0; border-radius: 4px;">
                        <h2 style="margin: 0 0 10px 0;"> ¿Necesitas ayuda?</h2>
                        <p style="margin: 0 0 15px 0; font-size: 16px;">Consulta nuestros manuales de autoservicio. La mayoría de problemas tienen solución inmediata sin necesidad de crear una incidencia.</p>
                        <a href="manuales.php" style="background: #17a2b8; color: white; padding: 12px 25px; text-decoration: none; border-radius: 4px; display: inline-block; font-size: 16px;">
                            🔍 Buscar en Manuales de Ayuda
                        </a>
                    </div>
                    <?php if (empty($incidencias)): ?>
                        <div style="padding:40px; text-align:center; color:#6b7280;">
                            <p>No tienes incidencias registradas.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-wrapper">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Título</th>
                                        <th>Prioridad</th>
                                        <th>Estado</th>
                                        <th>Técnico</th>
                                        <th>Fecha</th>
                                        <th>Detalle</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($incidencias as $inc): ?>
                                        <tr>
                                            <td>#<?= $inc['id_incidencia'] ?></td>
                                            <td><?= htmlspecialchars($inc['titulo']) ?></td>
                                            <td><?= ucfirst($inc['prioridad']) ?></td>
                                            <td><span class="estado-badge estado-<?= $inc['estado'] ?>"><?= ucfirst(str_replace('_', ' ', $inc['estado'])) ?></span></td>
                                            <td><?= htmlspecialchars($inc['empleado_nombre'] ?? '—') ?></td>
                                            <td><?= date('d/m/Y', strtotime($inc['fecha_creacion'])) ?></td>
                                            <td><a href="ver_incidencia.php?id=<?= $inc['id_incidencia'] ?>" class="btn btn-sm btn-outline">Ver</a></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>

</html>