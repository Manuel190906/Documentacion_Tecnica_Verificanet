<?php
error_reporting(0);
require_once 'includes/config.php';
requiere_rol('admin');

$conn = conectar_bd();

$stats = $conn->query("
    SELECT
        COUNT(*) as total,
        SUM(estado='reportada') as reportadas,
        SUM(estado='en_proceso') as en_proceso,
        SUM(estado='resuelta') as resueltas,
        SUM(estado='cerrada') as cerradas
    FROM incidencias
")->fetch_assoc();

$total_clientes = $conn->query("SELECT COUNT(*) as n FROM clientes")->fetch_assoc()['n'];
$total_empleados = $conn->query("SELECT COUNT(*) as n FROM usuarios WHERE rol IN ('empleado','tecnico')")->fetch_assoc()['n'];

$incidencias = $conn->query("
    SELECT i.*,
           c.nombre as cliente_nombre,
           u.nombre as empleado_nombre
    FROM incidencias i
    LEFT JOIN clientes c ON i.id_cliente = c.id_cliente
    LEFT JOIN usuarios u ON i.id_empleado_asignado = u.id_usuario
    ORDER BY i.fecha_creacion DESC
");

$empleados = $conn->query("SELECT id_usuario, nombre, rol FROM usuarios WHERE rol IN ('empleado','tecnico') AND activo=1 ORDER BY nombre");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['asignar'])) {
        $id_inc = (int)$_POST['id_incidencia'];
        $id_emp = (int)$_POST['id_empleado'];
        $conn->query("UPDATE incidencias SET id_empleado_asignado=$id_emp, estado='en_proceso' WHERE id_incidencia=$id_inc");
        header("Location: admin.php?ok=asignado");
        exit();
    }
    if (isset($_POST['eliminar'])) {
        $id_inc = (int)$_POST['id_incidencia'];
        $conn->query("DELETE FROM incidencias WHERE id_incidencia=$id_inc");
        header("Location: admin.php?ok=eliminado");
        exit();
    }
    if (isset($_POST['cambiar_estado'])) {
        $id = (int)$_POST['id_incidencia'];
        $estado = $conn->real_escape_string($_POST['nuevo_estado']);
        $notas = $conn->real_escape_string($_POST['notas_resolucion']);
        $conn->query("UPDATE incidencias SET estado='$estado', notas_resolucion='$notas' WHERE id_incidencia=$id");
        header("Location: admin.php?ok=estado");
        exit();
    }
}

$ok = '';
if (isset($_GET['ok'])) {
    $msgs = ['asignado'=>'Incidencia asignada correctamente.','eliminado'=>'Incidencia eliminada.','estado'=>'Estado actualizado correctamente.'];
    $ok = $msgs[$_GET['ok']] ?? '';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Admin - Verificanet</title>
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
        .dashboard-nav a { color: #6b7280; text-decoration: none; padding: 14px 16px; font-size: 14px; font-weight: 500; border-bottom: 2px solid transparent; margin-bottom: -2px; display: inline-block; transition: all 0.2s; }
        .dashboard-nav a:hover { color: #2563eb; }
        .dashboard-nav a.active { color: #2563eb; border-bottom-color: #2563eb; }
        .dashboard-container { max-width: 1400px; margin: 0 auto; padding: 32px; }
        .page-title { font-size: 24px; font-weight: 700; color: #1e40af; margin-bottom: 4px; }
        .page-sub { color: #6b7280; font-size: 14px; margin-bottom: 28px; }

        .stats-grid { display: grid; grid-template-columns: repeat(5,1fr); gap: 16px; margin-bottom: 28px; }
        .stat-box { background: white; border: 1px solid #dbeafe; border-radius: 12px; padding: 20px 24px; border-top: 3px solid #2563eb; }
        .stat-box:hover { box-shadow: 0 4px 16px rgba(37,99,235,0.12); }
        .stat-numero { display: block; font-size: 28px; font-weight: 700; color: #2563eb; }
        .stat-label { display: block; font-size: 13px; color: #6b7280; margin-top: 4px; }

        .card { background: white; border: 1px solid #dbeafe; border-radius: 12px; overflow: hidden; margin-bottom: 20px; }
        .card-header { padding: 18px 24px; border-bottom: 1px solid #dbeafe; background: #eff6ff; display: flex; align-items: center; justify-content: space-between; }
        .card-header h2 { font-size: 16px; font-weight: 600; color: #1e40af; }

        .alerta-ok { background: #dcfce7; color: #059669; padding: 12px 20px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; border: 1px solid #bbf7d0; }

        table { width: 100%; border-collapse: collapse; }
        th { padding: 12px 20px; text-align: left; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; background: #f9fafb; border-bottom: 1px solid #dbeafe; }
        td { padding: 14px 20px; font-size: 14px; border-bottom: 1px solid #f3f4f6; color: #374151; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #eff6ff; }

        .badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .badge-reportada { background: #dbeafe; color: #1e40af; }
        .badge-en_proceso { background: #fef3c7; color: #92400e; }
        .badge-resuelta { background: #dcfce7; color: #059669; }
        .badge-cerrada { background: #f3f4f6; color: #374151; }
        .prioridad-critica { color: #dc2626; font-weight: 700; }
        .prioridad-alta { color: #d97706; font-weight: 600; }
        .prioridad-media { color: #2563eb; }
        .prioridad-baja { color: #6b7280; }

        .btn { padding: 7px 14px; border-radius: 6px; border: none; cursor: pointer; font-size: 13px; font-weight: 500; text-decoration: none; display: inline-block; transition: all 0.2s; }
        .btn-primary { background: #2563eb; color: white; }
        .btn-primary:hover { background: #1e40af; }
        .btn-outline { background: white; color: #2563eb; border: 1px solid #dbeafe; }
        .btn-outline:hover { background: #eff6ff; }
        .btn-danger { background: #dc2626; color: white; }
        .btn-danger:hover { background: #b91c1c; }
        .actions-cell { display: flex; gap: 6px; flex-wrap: wrap; }

        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); z-index: 1000; justify-content: center; align-items: center; }
        .modal-overlay.active { display: flex; }
        .modal-box { background: white; border-radius: 12px; padding: 32px; width: 480px; max-width: 95%; box-shadow: 0 20px 60px rgba(0,0,0,0.15); }
        .modal-box h3 { color: #1e40af; font-size: 18px; font-weight: 700; margin-bottom: 20px; }
        .modal-box select, .modal-box textarea { width: 100%; padding: 10px 12px; border: 1px solid #dbeafe; border-radius: 6px; margin-bottom: 16px; font-size: 14px; color: #374151; }
        .modal-box select:focus, .modal-box textarea:focus { outline: none; border-color: #2563eb; }
        .modal-box label { display: block; font-size: 13px; font-weight: 600; color: #1e40af; margin-bottom: 6px; }
        .modal-footer { display: flex; gap: 10px; justify-content: flex-end; margin-top: 8px; }
    </style>
</head>
<body>

<header class="dashboard-header">
    <div class="container">
        <h1>VERIFICANET <span>/ Panel de Administración</span></h1>
        <div style="display:flex;align-items:center;gap:16px;">
            <span style="color:rgba(255,255,255,0.8);font-size:13px;">Bienvenido, <?= htmlspecialchars($_SESSION['usuario_nombre']) ?></span>
            <a href="logout.php" class="btn-logout">Cerrar Sesión</a>
        </div>
    </div>
</header>

<nav class="dashboard-nav">
    <div class="container">
        <a href="admin.php" class="active">Incidencias</a>
        <a href="estado_sistema.php">Estado del Sistema</a>
        <a href="manuales.php">Manuales</a>
    </div>
</nav>

<div class="dashboard-container">
    <div class="page-title">Panel de Administración</div>
    <div class="page-sub">Gestión de incidencias y monitorización del sistema</div>

    <?php if ($ok): ?>
    <div class="alerta-ok"><?= $ok ?></div>
    <?php endif; ?>

    <div class="stats-grid">
        <div class="stat-box">
            <span class="stat-numero"><?= $stats['total'] ?></span>
            <span class="stat-label">Total Incidencias</span>
        </div>
        <div class="stat-box">
            <span class="stat-numero" style="color:#d97706;"><?= (int)$stats['reportadas'] + (int)$stats['en_proceso'] ?></span>
            <span class="stat-label">Pendientes</span>
        </div>
        <div class="stat-box">
            <span class="stat-numero" style="color:#059669;"><?= $stats['resueltas'] ?></span>
            <span class="stat-label">Resueltas</span>
        </div>
        <div class="stat-box">
            <span class="stat-numero" style="color:#6366f1;"><?= $total_clientes ?></span>
            <span class="stat-label">Clientes</span>
        </div>
        <div class="stat-box">
            <span class="stat-numero" style="color:#0ea5e9;"><?= $total_empleados ?></span>
            <span class="stat-label">Empleados</span>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2>Gestión de Incidencias</h2>
            <span style="color:#6b7280;font-size:13px;"><?= $stats['total'] ?> registros</span>
        </div>
        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Título</th>
                        <th>Cliente</th>
                        <th>Asignado a</th>
                        <th>Prioridad</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($incidencias->num_rows === 0): ?>
                    <tr><td colspan="8" style="text-align:center;padding:30px;color:#6b7280;">No hay incidencias</td></tr>
                    <?php else: ?>
                    <?php while ($inc = $incidencias->fetch_assoc()): ?>
                    <tr>
                        <td style="color:#6b7280;">#<?= $inc['id_incidencia'] ?></td>
                        <td style="font-weight:500;"><?= htmlspecialchars($inc['titulo']) ?></td>
                        <td style="color:#6b7280;"><?= htmlspecialchars($inc['cliente_nombre'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($inc['empleado_nombre'] ?? 'Sin asignar') ?></td>
                        <td><span class="prioridad-<?= $inc['prioridad'] ?>"><?= ucfirst($inc['prioridad']) ?></span></td>
                        <td><span class="badge badge-<?= $inc['estado'] ?>"><?= ucfirst(str_replace('_',' ',$inc['estado'])) ?></span></td>
                        <td style="color:#6b7280;font-size:13px;"><?= date('d/m/Y', strtotime($inc['fecha_creacion'])) ?></td>
                        <td>
                            <div class="actions-cell">
                                <a href="ver_incidencia.php?id=<?= $inc['id_incidencia'] ?>" class="btn btn-outline">Ver</a>
                                <button onclick="abrirAsignar(<?= $inc['id_incidencia'] ?>)" class="btn btn-primary">Asignar</button>
                                <button onclick="abrirEstado(<?= $inc['id_incidencia'] ?>, '<?= $inc['estado'] ?>')" class="btn btn-outline">Estado</button>
                                <button onclick="confirmarEliminar(<?= $inc['id_incidencia'] ?>)" class="btn btn-danger">Eliminar</button>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Asignar -->
<div class="modal-overlay" id="modalAsignar">
    <div class="modal-box">
        <h3>Asignar Incidencia</h3>
        <form method="POST">
            <input type="hidden" name="asignar" value="1">
            <input type="hidden" name="id_incidencia" id="asignar_id">
            <label>Asignar a:</label>
            <select name="id_empleado">
                <?php $empleados->data_seek(0); while ($emp = $empleados->fetch_assoc()): ?>
                <option value="<?= $emp['id_usuario'] ?>"><?= htmlspecialchars($emp['nombre']) ?> (<?= $emp['rol'] ?>)</option>
                <?php endwhile; ?>
            </select>
            <div class="modal-footer">
                <button type="button" onclick="cerrarModales()" class="btn btn-outline">Cancelar</button>
                <button type="submit" class="btn btn-primary">Asignar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Estado -->
<div class="modal-overlay" id="modalEstado">
    <div class="modal-box">
        <h3>Cambiar Estado</h3>
        <form method="POST">
            <input type="hidden" name="cambiar_estado" value="1">
            <input type="hidden" name="id_incidencia" id="estado_id">
            <label>Nuevo Estado:</label>
            <select name="nuevo_estado" id="modal_estado_sel">
                <option value="reportada">Reportada</option>
                <option value="en_proceso">En Proceso</option>
                <option value="resuelta">Resuelta</option>
                <option value="cerrada">Cerrada</option>
            </select>
            <label>Notas:</label>
            <textarea name="notas_resolucion" rows="3" placeholder="Notas de resolución..."></textarea>
            <div class="modal-footer">
                <button type="button" onclick="cerrarModales()" class="btn btn-outline">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Eliminar -->
<div class="modal-overlay" id="modalEliminar">
    <div class="modal-box">
        <h3>Eliminar Incidencia</h3>
        <p style="color:#374151;margin-bottom:20px;font-size:14px;">Esta acción no se puede deshacer.</p>
        <form method="POST">
            <input type="hidden" name="eliminar" value="1">
            <input type="hidden" name="id_incidencia" id="eliminar_id">
            <div class="modal-footer">
                <button type="button" onclick="cerrarModales()" class="btn btn-outline">Cancelar</button>
                <button type="submit" class="btn btn-danger">Eliminar</button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirAsignar(id) { document.getElementById('asignar_id').value=id; document.getElementById('modalAsignar').classList.add('active'); }
function abrirEstado(id, est) { document.getElementById('estado_id').value=id; document.getElementById('modal_estado_sel').value=est; document.getElementById('modalEstado').classList.add('active'); }
function confirmarEliminar(id) { document.getElementById('eliminar_id').value=id; document.getElementById('modalEliminar').classList.add('active'); }
function cerrarModales() { document.querySelectorAll('.modal-overlay').forEach(m=>m.classList.remove('active')); }
</script>
</body>
</html>
<?php $conn->close(); ?>