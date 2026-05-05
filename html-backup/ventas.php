<?php
require_once 'includes/config.php';
requiere_login();

$conn = conectar_bd();

$clientes = $conn->query("SELECT c.*, COUNT(co.id_contrato) as total_contratos 
    FROM clientes c 
    LEFT JOIN contratos co ON c.id_cliente = co.id_cliente 
    GROUP BY c.id_cliente ORDER BY c.fecha_registro DESC");

$contratos = $conn->query("SELECT co.*, c.nombre as cliente_nombre, c.nombre_empresa, s.nombre as servicio_nombre, s.precio_base
    FROM contratos co
    JOIN clientes c ON co.id_cliente = c.id_cliente
    JOIN servicios s ON co.id_servicio = s.id_servicio
    ORDER BY co.fecha_inicio DESC");

$servicios = $conn->query("SELECT * FROM servicios ORDER BY tipo_servicio, nombre");

$stats = $conn->query("
    SELECT 
        (SELECT COUNT(*) FROM clientes) as total_clientes,
        (SELECT COUNT(*) FROM contratos WHERE estado = 'activo') as contratos_activos,
        (SELECT COUNT(*) FROM servicios) as total_servicios,
        (SELECT COALESCE(SUM(s.precio_base),0) FROM contratos co JOIN servicios s ON co.id_servicio = s.id_servicio WHERE co.estado = 'activo') as ingresos
")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ventas - Verificanet</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <style>
        body { background: #eff6ff; }
        .dashboard-header { background: linear-gradient(135deg, #1e40af 0%, #2563eb 100%); box-shadow: 0 2px 8px rgba(30,64,175,0.3); }
        .dashboard-header .container { display: flex; justify-content: space-between; align-items: center; }
        .dashboard-header h1 { color: white; font-size: 20px; font-weight: 700; letter-spacing: 0.05em; }
        .dashboard-header h1 span { font-size: 13px; font-weight: 400; opacity: 0.8; margin-left: 10px; }
        .user-info { color: rgba(255,255,255,0.8); font-size: 13px; }
        .btn-logout { background: rgba(255,255,255,0.15); color: white; border: 1px solid rgba(255,255,255,0.3); padding: 7px 16px; border-radius: 6px; text-decoration: none; font-size: 13px; }
        .btn-logout:hover { background: rgba(255,255,255,0.25); }
        .dashboard-nav { background: white; border-bottom: 2px solid #dbeafe; }
        .dashboard-nav .container { display: flex; gap: 4px; }
        .dashboard-nav a { color: #6b7280; text-decoration: none; padding: 14px 16px; font-size: 14px; font-weight: 500; border-bottom: 2px solid transparent; margin-bottom: -2px; display: inline-block; }
        .dashboard-nav a:hover { color: #2563eb; }
        .dashboard-nav a.active { color: #2563eb; border-bottom-color: #2563eb; }
        .dashboard-container { max-width: 1400px; margin: 0 auto; padding: 32px; }
        .page-title { font-size: 24px; font-weight: 700; color: #1e40af; margin-bottom: 4px; }
        .page-sub { color: #6b7280; font-size: 14px; margin-bottom: 28px; }
        .stats-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 16px; margin-bottom: 28px; }
        .stat-box { background: white; border: 1px solid #dbeafe; border-radius: 12px; padding: 24px; border-top: 3px solid #2563eb; }
        .stat-box:hover { box-shadow: 0 4px 16px rgba(37,99,235,0.12); }
        .stat-numero { display: block; font-size: 32px; font-weight: 700; color: #2563eb; }
        .stat-label { display: block; font-size: 13px; color: #6b7280; margin-top: 4px; }
        .content-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .card { background: white; border: 1px solid #dbeafe; border-radius: 12px; overflow: hidden; margin-bottom: 20px; }
        .card-header { padding: 18px 24px; border-bottom: 1px solid #dbeafe; display: flex; align-items: center; justify-content: space-between; background: #eff6ff; }
        .card-header h2 { font-size: 15px; font-weight: 600; color: #1e40af; }
        table { width: 100%; border-collapse: collapse; }
        th { padding: 11px 20px; text-align: left; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; background: #f9fafb; border-bottom: 1px solid #dbeafe; }
        td { padding: 13px 20px; font-size: 14px; border-bottom: 1px solid #f3f4f6; color: #374151; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #eff6ff; }
        .badge { padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .badge-activo { background: #dcfce7; color: #059669; }
        .badge-inactivo { background: #fee2e2; color: #dc2626; }
        .badge-finalizado { background: #f3f4f6; color: #6b7280; }
        .badge-empresa { background: #dbeafe; color: #1e40af; }
        .badge-particular { background: #fef3c7; color: #d97706; }
        .servicio-item { display: flex; align-items: center; justify-content: space-between; padding: 13px 20px; border-bottom: 1px solid #f3f4f6; }
        .servicio-item:last-child { border-bottom: none; }
        .servicio-item:hover { background: #eff6ff; }
        .servicio-icon { width: 36px; height: 36px; border-radius: 8px; background: #dbeafe; display: flex; align-items: center; justify-content: center; font-size: 16px; margin-right: 12px; flex-shrink: 0; }
        .servicio-info { display: flex; align-items: center; }
        .servicio-name { font-size: 14px; font-weight: 500; color: #374151; }
        .servicio-type { font-size: 12px; color: #6b7280; }
        .servicio-price { font-weight: 700; color: #2563eb; font-size: 15px; }
    </style>
</head>
<body>
<header class="dashboard-header">
    <div class="container">
        <h1>VERIFICANET <span>/ Departamento de Ventas</span></h1>
        <div style="display:flex;align-items:center;gap:16px;">
            <span class="user-info">👤 <?php echo htmlspecialchars($_SESSION['usuario_nombre'] ?? ''); ?></span>
            <a href="logout.php" class="btn-logout">Cerrar Sesión</a>
        </div>
    </div>
</header>
<nav class="dashboard-nav">
    <div class="container">
        <a href="ventas.php" class="active">Inicio</a>
        <a href="manuales.php">Manuales</a>
    </div>
</nav>
<div class="dashboard-container">
    <div class="page-title">Panel de Ventas</div>
    <div class="page-sub">Gestión de clientes, contratos y servicios</div>
    <div class="stats-grid">
        <div class="stat-box"><span class="stat-numero"><?php echo $stats['total_clientes']; ?></span><span class="stat-label">👥 Clientes totales</span></div>
        <div class="stat-box"><span class="stat-numero"><?php echo $stats['contratos_activos']; ?></span><span class="stat-label">📋 Contratos activos</span></div>
        <div class="stat-box"><span class="stat-numero"><?php echo $stats['total_servicios']; ?></span><span class="stat-label">🛠️ Servicios disponibles</span></div>
        <div class="stat-box"><span class="stat-numero"><?php echo number_format($stats['ingresos'], 0, ',', '.'); ?>€</span><span class="stat-label">💰 Ingresos activos</span></div>
    </div>
    <div class="content-grid">
        <div class="card">
            <div class="card-header">
                <h2>👥 Clientes</h2>
                <span style="color:#6b7280;font-size:13px;"><?php echo $clientes->num_rows; ?> registrados</span>
            </div>
            <table>
                <thead><tr><th>Cliente</th><th>Tipo</th><th>Contratos</th></tr></thead>
                <tbody>
                <?php while ($c = $clientes->fetch_assoc()): ?>
                <tr>
                    <td>
                        <div style="font-weight:500;color:#1e3a8a;"><?php echo htmlspecialchars($c['nombre_empresa'] ?: ($c['nombre'] . ' ' . $c['apellido'])); ?></div>
                        <div style="font-size:12px;color:#6b7280;"><?php echo htmlspecialchars($c['email']); ?></div>
                    </td>
                    <td><span class="badge badge-<?php echo $c['tipo_cliente']; ?>"><?php echo $c['tipo_cliente']; ?></span></td>
                    <td style="font-weight:700;color:#2563eb;"><?php echo $c['total_contratos']; ?></td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <div class="card">
            <div class="card-header"><h2>🛠️ Catálogo de Servicios</h2></div>
            <?php
            $icons = ['soporte'=>'🔧','mantenimiento'=>'⚙️','desarrollo'=>'💻','consultoria'=>'📊'];
            while ($s = $servicios->fetch_assoc()): ?>
            <div class="servicio-item">
                <div class="servicio-info">
                    <div class="servicio-icon"><?php echo $icons[$s['tipo_servicio']] ?? '📦'; ?></div>
                    <div>
                        <div class="servicio-name"><?php echo htmlspecialchars($s['nombre']); ?></div>
                        <div class="servicio-type"><?php echo $s['tipo_servicio']; ?></div>
                    </div>
                </div>
                <div class="servicio-price"><?php echo number_format($s['precio_base'], 2, ',', '.'); ?>€</div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><h2>📋 Contratos</h2></div>
        <table>
            <thead><tr><th>Cliente</th><th>Servicio</th><th>Precio</th><th>Fecha inicio</th><th>Estado</th></tr></thead>
            <tbody>
            <?php while ($co = $contratos->fetch_assoc()): ?>
            <tr>
                <td style="font-weight:500;"><?php echo htmlspecialchars($co['nombre_empresa'] ?: $co['cliente_nombre']); ?></td>
                <td><?php echo htmlspecialchars($co['servicio_nombre']); ?></td>
                <td style="font-weight:700;color:#2563eb;"><?php echo number_format($co['precio_base'], 2, ',', '.'); ?>€</td>
                <td style="color:#6b7280;"><?php echo date('d/m/Y', strtotime($co['fecha_inicio'])); ?></td>
                <td><span class="badge badge-<?php echo $co['estado']; ?>"><?php echo $co['estado']; ?></span></td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
<?php $conn->close(); ?>