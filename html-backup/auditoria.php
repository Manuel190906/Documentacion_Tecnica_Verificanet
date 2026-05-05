<?php
require_once 'includes/config.php';
requiere_login();

$conn = conectar_bd();

$incidencias_cerradas = $conn->query("
    SELECT i.*, c.nombre as cliente_nombre, c.nombre_empresa,
           e.nombre as empleado_nombre, e.apellido as empleado_apellido
    FROM incidencias i
    LEFT JOIN clientes c ON i.id_cliente = c.id_cliente
    LEFT JOIN empleados e ON i.id_empleado_asignado = e.id_empleado
    WHERE i.estado IN ('resuelta', 'cerrada')
    ORDER BY i.fecha_creacion DESC LIMIT 20
");

$stats = $conn->query("
    SELECT
        (SELECT COUNT(*) FROM incidencias WHERE estado IN ('resuelta','cerrada')) as resueltas,
        (SELECT COUNT(*) FROM incidencias WHERE estado NOT IN ('resuelta','cerrada')) as abiertas,
        (SELECT COUNT(*) FROM incidencias WHERE prioridad = 'critica') as criticas,
        (SELECT COUNT(*) FROM usuarios) as total_usuarios
")->fetch_assoc();

$por_prioridad = $conn->query("
    SELECT prioridad, COUNT(*) as total FROM incidencias GROUP BY prioridad ORDER BY FIELD(prioridad,'critica','alta','media','baja')
");

$mes = isset($_GET['mes']) ? (int)$_GET['mes'] : (int)date('m');
$anio = isset($_GET['anio']) ? (int)$_GET['anio'] : (int)date('Y');
if ($mes < 1) { $mes = 12; $anio--; }
if ($mes > 12) { $mes = 1; $anio++; }

$reuniones = [
    date('Y-m-d', mktime(0,0,0,$mes,5,$anio)) => ['Revisión seguridad trimestral', 'critica'],
    date('Y-m-d', mktime(0,0,0,$mes,10,$anio)) => ['Auditoría accesos usuarios', 'alta'],
    date('Y-m-d', mktime(0,0,0,$mes,15,$anio)) => ['Reunión equipo TI', 'media'],
    date('Y-m-d', mktime(0,0,0,$mes,20,$anio)) => ['Revisión incidencias mes', 'media'],
    date('Y-m-d', mktime(0,0,0,$mes,25,$anio)) => ['Informe mensual dirección', 'alta'],
];

$primer_dia = date('N', mktime(0,0,0,$mes,1,$anio));
$dias_mes = date('t', mktime(0,0,0,$mes,1,$anio));
$nombre_mes = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
$hoy = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auditoría - Verificanet</title>
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
        .stat-box { background: white; border: 1px solid #dbeafe; border-radius: 12px; padding: 24px; border-top: 3px solid #2563eb; }
        .stat-box:hover { box-shadow: 0 4px 16px rgba(37,99,235,0.12); }
        .stat-numero { display: block; font-size: 32px; font-weight: 700; color: #2563eb; }
        .stat-label { display: block; font-size: 13px; color: #6b7280; margin-top: 4px; }
        .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .card { background: white; border: 1px solid #dbeafe; border-radius: 12px; overflow: hidden; margin-bottom: 20px; }
        .card-header { padding: 18px 24px; border-bottom: 1px solid #dbeafe; display: flex; align-items: center; justify-content: space-between; background: #eff6ff; }
        .card-header h2 { font-size: 15px; font-weight: 600; color: #1e40af; }
        table { width: 100%; border-collapse: collapse; }
        th { padding: 11px 20px; text-align: left; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; background: #f9fafb; border-bottom: 1px solid #dbeafe; }
        td { padding: 13px 20px; font-size: 14px; border-bottom: 1px solid #f3f4f6; color: #374151; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #eff6ff; }
        .badge { padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .badge-resuelta { background: #dcfce7; color: #059669; }
        .badge-cerrada { background: #f3f4f6; color: #6b7280; }
        .badge-critica { background: #fee2e2; color: #dc2626; }
        .badge-alta { background: #fef3c7; color: #d97706; }
        .badge-media { background: #dbeafe; color: #2563eb; }
        .badge-baja { background: #dcfce7; color: #059669; }

        /* CALENDARIO */
        .cal-nav { display: flex; align-items: center; gap: 12px; }
        .cal-btn { background: white; border: 1px solid #dbeafe; color: #2563eb; width: 28px; height: 28px; border-radius: 6px; text-decoration: none; display: flex; align-items: center; justify-content: center; font-size: 14px; }
        .cal-btn:hover { background: #dbeafe; }
        .cal-month { font-size: 14px; font-weight: 600; color: #1e40af; }
        .calendar { padding: 16px; }
        .cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 3px; }
        .cal-day-name { text-align: center; padding: 8px 4px; font-size: 11px; font-weight: 600; color: #6b7280; text-transform: uppercase; }
        .cal-day { min-height: 52px; padding: 6px; border-radius: 6px; background: #f9fafb; }
        .cal-day:hover { background: #dbeafe; }
        .cal-day.empty { background: transparent; }
        .cal-day.today { background: #dbeafe; border: 1px solid #2563eb; }
        .cal-day.has-event { background: #eff6ff; }
        .day-num { font-size: 12px; color: #6b7280; margin-bottom: 3px; }
        .cal-day.today .day-num { color: #2563eb; font-weight: 700; }
        .event-dot { font-size: 9px; padding: 2px 4px; border-radius: 3px; line-height: 1.3; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .event-critica { background: #fee2e2; color: #dc2626; }
        .event-alta { background: #fef3c7; color: #d97706; }
        .event-media { background: #dcfce7; color: #059669; }

        .reunion-item { display: flex; align-items: center; gap: 12px; padding: 12px 20px; border-bottom: 1px solid #f3f4f6; }
        .reunion-item:last-child { border-bottom: none; }
        .reunion-item:hover { background: #eff6ff; }
        .priority-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
        .dot-critica { background: #dc2626; }
        .dot-alta { background: #d97706; }
        .dot-media { background: #2563eb; }
        .reunion-date { font-size: 12px; color: #6b7280; min-width: 60px; }
        .reunion-title { font-size: 13px; color: #374151; flex: 1; }

        .prio-list { padding: 16px 20px; }
        .prio-item { margin-bottom: 14px; }
        .prio-header { display: flex; justify-content: space-between; margin-bottom: 5px; }
        .prio-name { font-size: 12px; font-weight: 600; text-transform: uppercase; }
        .prio-count { font-size: 12px; color: #6b7280; }
        .bar-bg { background: #eff6ff; border-radius: 4px; height: 8px; overflow: hidden; }
        .bar-fill { height: 100%; border-radius: 4px; }
    </style>
</head>
<body>
<header class="dashboard-header">
    <div class="container">
        <h1>VERIFICANET <span>/ Departamento de Auditoría</span></h1>
        <div style="display:flex;align-items:center;gap:16px;">
            <span style="color:rgba(255,255,255,0.8);font-size:13px;">👤 <?php echo htmlspecialchars($_SESSION['usuario_nombre'] ?? ''); ?></span>
            <a href="logout.php" class="btn-logout">Cerrar Sesión</a>
        </div>
    </div>
</header>
<nav class="dashboard-nav">
    <div class="container">
        <a href="auditoria.php" class="active">Inicio</a>
        <a href="manuales.php">Manuales</a>
    </div>
</nav>
<div class="dashboard-container">
    <div class="page-title">Panel de Auditoría</div>
    <div class="page-sub">Registro de incidencias, reuniones y seguridad del sistema</div>
    <div class="stats-grid">
        <div class="stat-box"><span class="stat-numero" style="color:#059669;"><?php echo $stats['resueltas']; ?></span><span class="stat-label">✅ Incidencias resueltas</span></div>
        <div class="stat-box"><span class="stat-numero" style="color:#d97706;"><?php echo $stats['abiertas']; ?></span><span class="stat-label">🔓 Incidencias abiertas</span></div>
        <div class="stat-box"><span class="stat-numero" style="color:#dc2626;"><?php echo $stats['criticas']; ?></span><span class="stat-label">🔴 Incidencias críticas</span></div>
        <div class="stat-box"><span class="stat-numero"><?php echo $stats['total_usuarios']; ?></span><span class="stat-label">👥 Usuarios del sistema</span></div>
    </div>

    <div class="grid-3">
        <div class="card" style="grid-column:span 2;">
            <div class="card-header">
                <h2> Calendario de Reuniones</h2>
                <div class="cal-nav">
                    <a href="?mes=<?php echo $mes-1; ?>&anio=<?php echo $anio; ?>" class="cal-btn">‹</a>
                    <span class="cal-month"><?php echo $nombre_mes[$mes] . ' ' . $anio; ?></span>
                    <a href="?mes=<?php echo $mes+1; ?>&anio=<?php echo $anio; ?>" class="cal-btn">›</a>
                </div>
            </div>
            <div class="calendar">
                <div class="cal-grid">
                    <?php foreach (['Lun','Mar','Mié','Jue','Vie','Sáb','Dom'] as $d): ?>
                    <div class="cal-day-name"><?php echo $d; ?></div>
                    <?php endforeach; ?>
                    <?php for ($i=1; $i<$primer_dia; $i++): ?><div class="cal-day empty"></div><?php endfor; ?>
                    <?php for ($dia=1; $dia<=$dias_mes; $dia++):
                        $fecha = date('Y-m-d', mktime(0,0,0,$mes,$dia,$anio));
                        $esHoy = $fecha === $hoy;
                        $tieneEvento = isset($reuniones[$fecha]);
                        $clases = 'cal-day' . ($esHoy ? ' today' : '') . ($tieneEvento ? ' has-event' : '');
                    ?>
                    <div class="<?php echo $clases; ?>">
                        <div class="day-num"><?php echo $dia; ?></div>
                        <?php if ($tieneEvento): $ev = $reuniones[$fecha]; ?>
                        <div class="event-dot event-<?php echo $ev[1]; ?>"><?php echo $ev[0]; ?></div>
                        <?php endif; ?>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>
        </div>

        <div style="display:flex;flex-direction:column;gap:20px;">
            <div class="card">
                <div class="card-header"><h2> Próximas Reuniones</h2></div>
                <?php foreach ($reuniones as $fecha => $ev): ?>
                <div class="reunion-item">
                    <div class="priority-dot dot-<?php echo $ev[1]; ?>"></div>
                    <div class="reunion-date"><?php echo date('d M', strtotime($fecha)); ?></div>
                    <div class="reunion-title"><?php echo $ev[0]; ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="card">
                <div class="card-header"><h2> Incidencias por Prioridad</h2></div>
                <div class="prio-list">
                    <?php
                    $prios = []; $pmax = 0;
                    while ($p = $por_prioridad->fetch_assoc()) { $prios[] = $p; if($p['total']>$pmax) $pmax=$p['total']; }
                    $colors = ['critica'=>'#dc2626','alta'=>'#d97706','media'=>'#2563eb','baja'=>'#059669'];
                    foreach ($prios as $p):
                        $pct = $pmax > 0 ? ($p['total']/$pmax*100) : 0;
                    ?>
                    <div class="prio-item">
                        <div class="prio-header">
                            <span class="prio-name" style="color:<?php echo $colors[$p['prioridad']] ?? '#374151'; ?>"><?php echo strtoupper($p['prioridad']); ?></span>
                            <span class="prio-count"><?php echo $p['total']; ?></span>
                        </div>
                        <div class="bar-bg"><div class="bar-fill" style="width:<?php echo $pct; ?>%;background:<?php echo $colors[$p['prioridad']] ?? '#2563eb'; ?>;"></div></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2> Registro de Incidencias Resueltas</h2>
            <span style="color:#6b7280;font-size:13px;"><?php echo $incidencias_cerradas->num_rows; ?> registros</span>
        </div>
        <div style="overflow-x:auto;">
            <table>
                <thead><tr><th>ID</th><th>Título</th><th>Cliente</th><th>Empleado</th><th>Prioridad</th><th>Estado</th><th>Fecha</th></tr></thead>
                <tbody>
                <?php while ($i = $incidencias_cerradas->fetch_assoc()): ?>
                <tr>
                    <td style="color:#6b7280;font-size:13px;">#<?php echo $i['id_incidencia']; ?></td>
                    <td><?php echo htmlspecialchars($i['titulo']); ?></td>
                    <td style="color:#6b7280;"><?php echo htmlspecialchars($i['nombre_empresa'] ?: $i['cliente_nombre']); ?></td>
                    <td style="color:#6b7280;"><?php echo htmlspecialchars($i['empleado_nombre'] . ' ' . $i['empleado_apellido']); ?></td>
                    <td><span class="badge badge-<?php echo $i['prioridad']; ?>"><?php echo $i['prioridad']; ?></span></td>
                    <td><span class="badge badge-<?php echo $i['estado']; ?>"><?php echo $i['estado']; ?></span></td>
                    <td style="font-size:13px;color:#6b7280;"><?php echo date('d/m/Y', strtotime($i['fecha_creacion'])); ?></td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
<?php $conn->close(); ?>