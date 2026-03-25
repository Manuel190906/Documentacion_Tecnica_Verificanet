<?php
error_reporting(0);
require_once 'includes/config.php';
requiere_rol('admin');

$conn = conectar_bd();

// Estadísticas generales
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

// Todas las incidencias con detalle
$incidencias = $conn->query("
    SELECT i.*, 
           c.nombre as cliente_nombre,
           u.nombre as empleado_nombre
    FROM incidencias i
    LEFT JOIN clientes c ON i.id_cliente = c.id_cliente
    LEFT JOIN usuarios u ON i.id_empleado_asignado = u.id_usuario
    ORDER BY i.fecha_creacion DESC
");

// Lista de empleados para asignar
$empleados = $conn->query("SELECT id_usuario, nombre, rol FROM usuarios WHERE rol IN ('empleado','tecnico') AND activo=1 ORDER BY nombre");

// Procesar asignación
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['asignar'])) {
        $id_inc = (int)$_POST['id_incidencia'];
        $id_emp = (int)$_POST['id_empleado'];
        $conn->query("UPDATE incidencias SET id_empleado_asignado=$id_emp, id_empleado=$id_emp, estado='en_proceso' WHERE id_incidencia=$id_inc");
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
    $msgs = ['asignado'=>'✅ Incidencia asignada.','eliminado'=>'✅ Incidencia eliminada.','estado'=>'✅ Estado actualizado.'];
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
        .estado-badge { padding:4px 10px; border-radius:12px; font-size:12px; font-weight:600; }
        .estado-reportada { background:#dbeafe; color:#1e40af; }
        .estado-en_proceso { background:#fef3c7; color:#92400e; }
        .estado-resuelta { background:#d1fae5; color:#065f46; }
        .estado-cerrada { background:#f3f4f6; color:#374151; }
        .prioridad-critica { color:#dc2626; font-weight:700; }
        .prioridad-alta { color:#d97706; font-weight:600; }
        .prioridad-media { color:#2563eb; }
        .prioridad-baja { color:#6b7280; }
        .modal-overlay { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; justify-content:center; align-items:center; }
        .modal-overlay.active { display:flex; }
        .modal-box { background:#fff; border-radius:12px; padding:30px; width:480px; max-width:95%; }
        .modal-box h3 { color:#1e3a8a; margin-bottom:20px; }
        .modal-box select, .modal-box textarea, .modal-box input { width:100%; padding:10px; border:1px solid #bfdbfe; border-radius:6px; margin-bottom:15px; font-size:14px; }
        .alerta-ok { background:#d1fae5; color:#065f46; padding:12px 20px; border-radius:8px; margin-bottom:20px; }
        .btn-danger { background:#dc2626; color:#fff; }
        .btn-danger:hover { background:#b91c1c; }
        .tabs { display:flex; gap:10px; margin-bottom:20px; flex-wrap:wrap; }
        .tab { padding:10px 20px; border-radius:8px; cursor:pointer; font-weight:500; text-decoration:none; color:#1e40af; border:2px solid #bfdbfe; background:#fff; transition:all 0.2s; }
        .tab.active { background:#2563eb; color:#fff; border-color:#2563eb; }
    </style>
</head>
<body>
    <header class="main-header">
        <div class="container">
            <div class="logo">
                <h1>VERIFICANET</h1>
                <span class="tagline">Panel Administrador</span>
            </div>
            <nav class="main-nav">
                <span class="nav-user">Bienvenido, <?= htmlspecialchars($_SESSION['usuario_nombre']) ?></span>
                <a href="logout.php" class="btn-logout">Cerrar Sesión</a>
            </nav>
        </div>
    </header>

    <main class="dashboard-main">
        <div class="container">
            <?php if ($ok): ?>
                <div class="alerta-ok"><?= $ok ?></div>
            <?php endif; ?>

            <!-- Estadísticas -->
            <div class="stats-row">
                <div class="stat-card">
                    <span class="stat-number"><?= $stats['total'] ?></span>
                    <span class="stat-label">Total Incidencias</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number" style="color:#d97706"><?= (int)$stats['reportadas'] + (int)$stats['en_proceso'] ?></span>
                    <span class="stat-label">Pendientes</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number" style="color:#10b981"><?= $stats['resueltas'] ?></span>
                    <span class="stat-label">Resueltas</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number" style="color:#6366f1"><?= $total_clientes ?></span>
                    <span class="stat-label">Clientes</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number" style="color:#0ea5e9"><?= $total_empleados ?></span>
                    <span class="stat-label">Empleados</span>
                </div>
            </div>

            <!-- Tabla principal -->
            <div class="card">
                <div class="card-header">
                    <h2>Gestión de Incidencias</h2>
                </div>
                <div class="table-wrapper">
                    <table class="data-table">
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
                                <tr><td colspan="8" style="text-align:center; padding:30px; color:#6b7280;">No hay incidencias</td></tr>
                            <?php else: ?>
                                <?php while ($inc = $incidencias->fetch_assoc()): ?>
                                    <tr>
                                        <td>#<?= $inc['id_incidencia'] ?></td>
                                        <td><?= htmlspecialchars($inc['titulo']) ?></td>
                                        <td><?= htmlspecialchars($inc['cliente_nombre'] ?? '—') ?></td>
                                        <td><?= htmlspecialchars($inc['empleado_nombre'] ?? '<em>Sin asignar</em>') ?></td>
                                        <td><span class="prioridad-<?= $inc['prioridad'] ?>"><?= ucfirst($inc['prioridad']) ?></span></td>
                                        <td><span class="estado-badge estado-<?= $inc['estado'] ?>"><?= ucfirst(str_replace('_',' ',$inc['estado'])) ?></span></td>
                                        <td><?= date('d/m/Y', strtotime($inc['fecha_creacion'])) ?></td>
                                        <td style="display:flex; gap:5px; flex-wrap:wrap;">
                                            <a href="ver_incidencia.php?id=<?= $inc['id_incidencia'] ?>" class="btn btn-sm btn-outline">Ver</a>
                                            <button onclick="abrirAsignar(<?= $inc['id_incidencia'] ?>)" class="btn btn-sm btn-primary">Asignar</button>
                                            <button onclick="abrirEstado(<?= $inc['id_incidencia'] ?>, '<?= $inc['estado'] ?>')" class="btn btn-sm btn-outline">Estado</button>
                                            <button onclick="confirmarEliminar(<?= $inc['id_incidencia'] ?>)" class="btn btn-sm btn-danger">Eliminar</button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal Asignar -->
    <div class="modal-overlay" id="modalAsignar">
        <div class="modal-box">
            <h3>Asignar Incidencia</h3>
            <form method="POST">
                <input type="hidden" name="asignar" value="1">
                <input type="hidden" name="id_incidencia" id="asignar_id">
                <label style="font-weight:600; color:#1e3a8a;">Asignar a:</label>
                <select name="id_empleado">
                    <?php
                    $empleados->data_seek(0);
                    while ($emp = $empleados->fetch_assoc()):
                    ?>
                        <option value="<?= $emp['id_usuario'] ?>"><?= htmlspecialchars($emp['nombre']) ?> (<?= $emp['rol'] ?>)</option>
                    <?php endwhile; ?>
                </select>
                <div style="display:flex; gap:10px; justify-content:flex-end;">
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
                <label style="font-weight:600; color:#1e3a8a;">Nuevo Estado:</label>
                <select name="nuevo_estado" id="modal_estado_sel">
                    <option value="reportada">Reportada</option>
                    <option value="en_proceso">En Proceso</option>
                    <option value="resuelta">Resuelta</option>
                    <option value="cerrada">Cerrada</option>
                </select>
                <label style="font-weight:600; color:#1e3a8a;">Notas:</label>
                <textarea name="notas_resolucion" placeholder="Notas de resolución..."></textarea>
                <div style="display:flex; gap:10px; justify-content:flex-end;">
                    <button type="button" onclick="cerrarModales()" class="btn btn-outline">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Eliminar -->
    <div class="modal-overlay" id="modalEliminar">
        <div class="modal-box">
            <h3>¿Eliminar incidencia?</h3>
            <p style="color:#374151; margin-bottom:20px;">Esta acción no se puede deshacer.</p>
            <form method="POST">
                <input type="hidden" name="eliminar" value="1">
                <input type="hidden" name="id_incidencia" id="eliminar_id">
                <div style="display:flex; gap:10px; justify-content:flex-end;">
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
