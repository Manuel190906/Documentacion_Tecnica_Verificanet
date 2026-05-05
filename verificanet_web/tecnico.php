<?php
error_reporting(0);
require_once 'includes/config.php';
requiere_rol('tecnico');

$conn = conectar_bd();

// Obtener todas las incidencias
$stmt = $conn->prepare("
    SELECT i.*, 
           c.nombre as cliente_nombre,
           u.nombre as empleado_nombre
    FROM incidencias i
    LEFT JOIN clientes c ON i.id_cliente = c.id_cliente
    LEFT JOIN usuarios u ON i.id_empleado_asignado = u.id_usuario
    ORDER BY i.fecha_creacion DESC
");
$stmt->execute();
$incidencias = $stmt->get_result();

// Estadísticas
$stmt_stats = $conn->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN estado IN ('reportada','en_proceso') THEN 1 ELSE 0 END) as pendientes,
        SUM(CASE WHEN estado IN ('resuelta','cerrada') THEN 1 ELSE 0 END) as resueltas,
        SUM(CASE WHEN estado = 'en_proceso' THEN 1 ELSE 0 END) as en_proceso
    FROM incidencias
");
$stmt_stats->execute();
$stats = $stmt_stats->get_result()->fetch_assoc();

// Procesar cambio de estado
$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_estado'])) {
    $id = (int)$_POST['id_incidencia'];
    $nuevo_estado = $_POST['nuevo_estado'];
    $notas = $_POST['notas_resolucion'];
    $estados_validos = ['reportada','en_proceso','resuelta','cerrada'];
    if (in_array($nuevo_estado, $estados_validos)) {
        $fecha_res = ($nuevo_estado === 'resuelta' || $nuevo_estado === 'cerrada') ? 'NOW()' : 'NULL';
        $stmt_upd = $conn->prepare("UPDATE incidencias SET estado=?, notas_resolucion=?, fecha_resolucion=$fecha_res WHERE id_incidencia=?");
        $stmt_upd->bind_param("ssi", $nuevo_estado, $notas, $id);
        $stmt_upd->execute();
        $mensaje = 'Estado actualizado correctamente.';
        // Refrescar datos
        header("Location: tecnico.php?ok=1");
        exit();
    }
}

// Filtro de estado
$filtro = isset($_GET['estado']) ? $_GET['estado'] : '';
$ok = isset($_GET['ok']) ? '✅ Estado actualizado correctamente.' : '';

// Recoger incidencias según filtro
$sql_filtro = "SELECT i.*, c.nombre as cliente_nombre, u.nombre as empleado_nombre
               FROM incidencias i
               LEFT JOIN clientes c ON i.id_cliente = c.id_cliente
               LEFT JOIN usuarios u ON i.id_empleado_asignado = u.id_usuario";
if ($filtro) {
    $sql_filtro .= " WHERE i.estado = '" . $conn->real_escape_string($filtro) . "'";
}
$sql_filtro .= " ORDER BY i.fecha_creacion DESC";
$incidencias = $conn->query($sql_filtro);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Técnico - Verificanet</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <style>
        .estado-badge { padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; }
        .estado-reportada { background: #dbeafe; color: #1e40af; }
        .estado-en_proceso { background: #fef3c7; color: #92400e; }
        .estado-resuelta { background: #d1fae5; color: #065f46; }
        .estado-cerrada { background: #f3f4f6; color: #374151; }
        .prioridad-critica { color: #dc2626; font-weight: 700; }
        .prioridad-alta { color: #d97706; font-weight: 600; }
        .prioridad-media { color: #2563eb; }
        .prioridad-baja { color: #6b7280; }
        .modal-overlay { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; justify-content:center; align-items:center; }
        .modal-overlay.active { display:flex; }
        .modal-box { background:#fff; border-radius:12px; padding:30px; width:480px; max-width:95%; }
        .modal-box h3 { color:#1e3a8a; margin-bottom:20px; }
        .modal-box select, .modal-box textarea { width:100%; padding:10px; border:1px solid #bfdbfe; border-radius:6px; margin-bottom:15px; font-size:14px; }
        .modal-box textarea { height:100px; resize:vertical; }
        .alerta-ok { background:#d1fae5; color:#065f46; padding:12px 20px; border-radius:8px; margin-bottom:20px; }
    </style>
</head>
<body>
    <header class="main-header">
        <div class="container">
            <div class="logo">
                <h1>VERIFICANET</h1>
                <span class="tagline">Panel Técnico</span>
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
                    <span class="stat-number" style="color:#d97706"><?= $stats['pendientes'] ?></span>
                    <span class="stat-label">Pendientes</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number" style="color:#f59e0b"><?= $stats['en_proceso'] ?></span>
                    <span class="stat-label">En Proceso</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number" style="color:#10b981"><?= $stats['resueltas'] ?></span>
                    <span class="stat-label">Resueltas</span>
                </div>
            </div>

            <!-- Filtros -->
            <div class="card">
                <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
                    <h2>Todas las Incidencias</h2>
                    <div style="display:flex; gap:10px; flex-wrap:wrap;">
                        <a href="tecnico.php" class="btn btn-sm <?= !$filtro ? 'btn-primary' : 'btn-outline' ?>">Todas</a>
                        <a href="tecnico.php?estado=reportada" class="btn btn-sm <?= $filtro==='reportada' ? 'btn-primary' : 'btn-outline' ?>">Reportadas</a>
                        <a href="tecnico.php?estado=en_proceso" class="btn btn-sm <?= $filtro==='en_proceso' ? 'btn-primary' : 'btn-outline' ?>">En Proceso</a>
                        <a href="tecnico.php?estado=resuelta" class="btn btn-sm <?= $filtro==='resuelta' ? 'btn-primary' : 'btn-outline' ?>">Resueltas</a>
                        <a href="tecnico.php?estado=cerrada" class="btn btn-sm <?= $filtro==='cerrada' ? 'btn-primary' : 'btn-outline' ?>">Cerradas</a>
                    </div>
                </div>
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Título</th>
                                <th>Cliente</th>
                                <th>Empleado</th>
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
                                        <td><?= htmlspecialchars($inc['empleado_nombre'] ?? '—') ?></td>
                                        <td><span class="prioridad-<?= $inc['prioridad'] ?>"><?= ucfirst($inc['prioridad']) ?></span></td>
                                        <td><span class="estado-badge estado-<?= $inc['estado'] ?>"><?= ucfirst(str_replace('_',' ',$inc['estado'])) ?></span></td>
                                        <td><?= date('d/m/Y', strtotime($inc['fecha_creacion'])) ?></td>
                                        <td style="display:flex; gap:6px;">
                                            <a href="ver_incidencia.php?id=<?= $inc['id_incidencia'] ?>" class="btn btn-sm btn-outline">Ver</a>
                                            <button onclick="abrirModal(<?= $inc['id_incidencia'] ?>, '<?= $inc['estado'] ?>')" class="btn btn-sm btn-primary">Estado</button>
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

    <!-- Modal cambio de estado -->
    <div class="modal-overlay" id="modalEstado">
        <div class="modal-box">
            <h3>Cambiar Estado de Incidencia</h3>
            <form method="POST" action="tecnico.php">
                <input type="hidden" name="cambiar_estado" value="1">
                <input type="hidden" name="id_incidencia" id="modal_id">
                <label style="font-weight:600; color:#1e3a8a;">Nuevo Estado:</label>
                <select name="nuevo_estado" id="modal_estado">
                    <option value="reportada">Reportada</option>
                    <option value="en_proceso">En Proceso</option>
                    <option value="resuelta">Resuelta</option>
                    <option value="cerrada">Cerrada</option>
                </select>
                <label style="font-weight:600; color:#1e3a8a;">Notas de resolución:</label>
                <textarea name="notas_resolucion" placeholder="Describe qué se hizo para resolver la incidencia..."></textarea>
                <div style="display:flex; gap:10px; justify-content:flex-end;">
                    <button type="button" onclick="cerrarModal()" class="btn btn-outline">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambio</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function abrirModal(id, estadoActual) {
            document.getElementById('modal_id').value = id;
            document.getElementById('modal_estado').value = estadoActual;
            document.getElementById('modalEstado').classList.add('active');
        }
        function cerrarModal() {
            document.getElementById('modalEstado').classList.remove('active');
        }
    </script>
</body>
</html>
