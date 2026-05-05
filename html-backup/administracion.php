<?php
require_once 'includes/config.php';
requiere_login();

$conn = conectar_bd();

$empleados = $conn->query("
    SELECT e.*, d.nombre as departamento, u.username, u.activo as user_activo
    FROM empleados e
    LEFT JOIN departamentos d ON e.id_departamento = d.id_departamento
    LEFT JOIN usuarios u ON u.id_empleado = e.id_empleado
    ORDER BY d.nombre, e.nombre
");

$stats = $conn->query("
    SELECT
        (SELECT COUNT(*) FROM empleados) as total_empleados,
        (SELECT COUNT(*) FROM departamentos) as total_departamentos,
        (SELECT COUNT(*) FROM usuarios WHERE activo = 1) as usuarios_activos,
        (SELECT COUNT(*) FROM incidencias WHERE estado NOT IN ('resuelta','cerrada')) as incidencias_abiertas
")->fetch_assoc();

$por_depto = $conn->query("
    SELECT d.nombre, COUNT(e.id_empleado) as total
    FROM departamentos d
    LEFT JOIN empleados e ON e.id_departamento = d.id_departamento
    GROUP BY d.id_departamento, d.nombre
    ORDER BY total DESC
");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administración - Verificanet</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <style>
        body { background: #eff6ff; }
        .dashboard-header { background: linear-gradient(135deg, #1e40af 0%, #2563eb 100%); box-shadow: 0 2px 8px rgba(30,64,175,0.3); }
        .dashboard-header .container { display: flex; justify-content: space-between; align-items: center; }
        .dashboard-header h1 { color: white; font-size: 20px; font-weight: 700; letter-spacing: 0.05em; }
        .dashboard-header h1 span { font-size: 13px; font-weight: 400; opacity: 0.8; margin-left: 10px; }
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
        .stat-box { background: white; border: 1px solid #dbeafe; border-radius: 12px; padding: 24px; border-top: 3px solid #2563eb; display: flex; align-items: center; gap: 16px; }
        .stat-box:hover { box-shadow: 0 4px 16px rgba(37,99,235,0.12); }
        .stat-icon { width: 48px; height: 48px; border-radius: 12px; background: #dbeafe; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0; }
        .stat-numero { display: block; font-size: 28px; font-weight: 700; color: #2563eb; line-height: 1; }
        .stat-label { display: block; font-size: 13px; color: #6b7280; margin-top: 4px; }
        .content-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; }
        .card { background: white; border: 1px solid #dbeafe; border-radius: 12px; overflow: hidden; }
        .card-header { padding: 18px 24px; border-bottom: 1px solid #dbeafe; display: flex; align-items: center; justify-content: space-between; background: #eff6ff; }
        .card-header h2 { font-size: 15px; font-weight: 600; color: #1e40af; }
        table { width: 100%; border-collapse: collapse; }
        th { padding: 11px 20px; text-align: left; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; background: #f9fafb; border-bottom: 1px solid #dbeafe; }
        td { padding: 13px 20px; font-size: 14px; border-bottom: 1px solid #f3f4f6; color: #374151; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #eff6ff; }
        .avatar { width: 34px; height: 34px; border-radius: 50%; background: linear-gradient(135deg, #2563eb, #3b82f6); display: flex; align-items: center; justify-content: center; color: white; font-size: 13px; font-weight: 700; flex-shrink: 0; }
        .employee-cell { display: flex; align-items: center; gap: 10px; }
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .badge-active { background: #dcfce7; color: #059669; }
        .badge-inactive { background: #fee2e2; color: #dc2626; }
        .depto-list { padding: 16px 20px; }
        .depto-item { margin-bottom: 16px; }
        .depto-header { display: flex; justify-content: space-between; margin-bottom: 6px; }
        .depto-name { font-size: 13px; font-weight: 500; color: #374151; }
        .depto-count { font-size: 13px; color: #6b7280; }
        .bar-bg { background: #eff6ff; border-radius: 4px; height: 8px; overflow: hidden; }
        .bar-fill { height: 100%; border-radius: 4px; background: linear-gradient(90deg, #2563eb, #3b82f6); }
    </style>
</head>
<body>
<header class="dashboard-header">
    <div class="container">
        <h1>VERIFICANET <span>/ Departamento de Administración</span></h1>
        <div style="display:flex;align-items:center;gap:16px;">
            <span style="color:rgba(255,255,255,0.8);font-size:13px;">👤 <?php echo htmlspecialchars($_SESSION['usuario_nombre'] ?? ''); ?></span>
            <a href="logout.php" class="btn-logout">Cerrar Sesión</a>
        </div>
    </div>
</header>
<nav class="dashboard-nav">
    <div class="container">
        <a href="administracion.php" class="active">Inicio</a>
        <a href="manuales.php">Manuales</a>
    </div>
</nav>
<div class="dashboard-container">
    <div class="page-title">Panel de Administración</div>
    <div class="page-sub">Gestión de empleados, departamentos y usuarios del sistema</div>
    <div class="stats-grid">
        <div class="stat-box"><div class="stat-icon"></div><div><span class="stat-numero"><?php echo $stats['total_empleados']; ?></span><span class="stat-label">Empleados</span></div></div>
        <div class="stat-box"><div class="stat-icon"></div><div><span class="stat-numero"><?php echo $stats['total_departamentos']; ?></span><span class="stat-label">Departamentos</span></div></div>
        <div class="stat-box"><div class="stat-icon"></div><div><span class="stat-numero"><?php echo $stats['usuarios_activos']; ?></span><span class="stat-label">Usuarios activos</span></div></div>
        <div class="stat-box"><div class="stat-icon"></div><div><span class="stat-numero"><?php echo $stats['incidencias_abiertas']; ?></span><span class="stat-label">Incidencias abiertas</span></div></div>
    </div>
    <div class="content-grid">
        <div class="card">
            <div class="card-header">
                <h2> Empleados</h2>
                <span style="color:#6b7280;font-size:13px;"><?php echo $empleados->num_rows; ?> registrados</span>
            </div>
            <div style="overflow-x:auto;">
                <table>
                    <thead><tr><th>Empleado</th><th>Departamento</th><th>Teléfono</th><th>Usuario</th><th>Estado</th></tr></thead>
                    <tbody>
                    <?php while ($e = $empleados->fetch_assoc()):
                        $initials = strtoupper(substr($e['nombre'],0,1) . substr($e['apellido'],0,1));
                    ?>
                    <tr>
                        <td>
                            <div class="employee-cell">
                                <div class="avatar"><?php echo $initials; ?></div>
                                <div>
                                    <div style="font-weight:500;"><?php echo htmlspecialchars($e['nombre'] . ' ' . $e['apellido']); ?></div>
                                    <div style="font-size:12px;color:#6b7280;"><?php echo htmlspecialchars($e['email']); ?></div>
                                </div>
                            </div>
                        </td>
                        <td style="color:#6b7280;font-size:13px;"><?php echo htmlspecialchars($e['departamento'] ?? '-'); ?></td>
                        <td style="color:#6b7280;font-size:13px;"><?php echo htmlspecialchars($e['telefono'] ?? '-'); ?></td>
                        <td style="font-family:monospace;font-size:13px;color:#2563eb;"><?php echo htmlspecialchars($e['username'] ?? '-'); ?></td>
                        <td><span class="badge <?php echo $e['user_activo'] ? 'badge-active' : 'badge-inactive'; ?>"><?php echo $e['user_activo'] ? 'Activo' : 'Inactivo'; ?></span></td>
                    </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h2> Departamentos</h2></div>
            <div class="depto-list">
                <?php
                $deptos = []; $max = 0;
                while ($d = $por_depto->fetch_assoc()) { $deptos[] = $d; if ($d['total'] > $max) $max = $d['total']; }
                foreach ($deptos as $d):
                    $pct = $max > 0 ? ($d['total'] / $max * 100) : 0;
                ?>
                <div class="depto-item">
                    <div class="depto-header">
                        <span class="depto-name"><?php echo htmlspecialchars($d['nombre']); ?></span>
                        <span class="depto-count"><?php echo $d['total']; ?> empleados</span>
                    </div>
                    <div class="bar-bg"><div class="bar-fill" style="width:<?php echo $pct; ?>%"></div></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
</body>
</html>
<?php $conn->close(); ?>