<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/config.php';
requiere_rol('empleado');

$conn = conectar_bd();

// Obtener incidencias del empleado
$id_empleado = $_SESSION['usuario_id'];
$stmt = $conn->prepare("
    SELECT i.*, c.nombre as cliente_nombre, c.nombre_empresa
    FROM incidencias i
    LEFT JOIN clientes c ON i.id_cliente = c.id_cliente
    WHERE i.id_empleado = ?
    ORDER BY i.fecha_creacion DESC
");
$stmt->bind_param("i", $id_empleado);
$stmt->execute();
$incidencias = $stmt->get_result();

// Contar pendientes y resueltas
$stmt_stats = $conn->prepare("
SELECT
        SUM(CASE WHEN estado NOT IN ('resuelta','cerrada') THEN 1 ELSE 0 END) as pendientes,
        SUM(CASE WHEN estado IN ('resuelta','cerrada') THEN 1 ELSE 0 END) as resueltas
    FROM incidencias
    WHERE id_empleado = ?
");
$stmt_stats->bind_param("i", $id_empleado);
$stmt_stats->execute();
$stats = $stmt_stats->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Empleado - Verificanet</title>
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
    <!-- HEADER -->
    <header class="dashboard-header">
        <div class="container">
            <div>
                <h1>Dashboard Empleado</h1>
                <span class="user-info">Bienvenido, <?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?></span>
            </div>
            <a href="procesar/logout.php" class="btn-logout">Cerrar Sesión</a>
        </div>
    </header>

    <!-- NAVEGACIÓN -->
    <nav class="dashboard-nav">
        <div class="container">
            <div class="nav-links">
                <a href="empleado.php" class="active">Mis Incidencias</a>
                <a href="empleado.php?accion=nueva">Nueva Incidencia</a>
            </div>
        </div>
    </nav>

    <!-- CONTENIDO -->
    <div class="dashboard-container">

        <!-- ESTADÍSTICAS -->
        <div class="stats-grid">
            <div class="stat-box">
                <span class="stat-numero"><?php echo $stats['pendientes'] ?: 0; ?></span>
                <span class="stat-label">Pendientes</span>
            </div>
            <div class="stat-box">
                <span class="stat-numero"><?php echo $stats['resueltas'] ?: 0; ?></span>
                <span class="stat-label">Resueltas</span>
            </div>
            <div class="stat-box">
                <span class="stat-numero"><?php echo $incidencias->num_rows; ?></span>
                <span class="stat-label">Total Creadas</span>
            </div>
        </div>

        <?php if (isset($_GET['accion']) && $_GET['accion'] === 'nueva'): ?>
            <!-- FORMULARIO NUEVA INCIDENCIA -->
            <div class="card">
                <div class="card-header">
                    <h2>Nueva Incidencia</h2>
                </div>
                <div class="card-body">
                    <form action="procesar/crear_incidencia.php" method="POST">
                        <div class="grid-2">
                            <div class="form-group">
                                <label for="cliente">Cliente:</label>
                                <select name="id_cliente" id="cliente" required>
                                    <option value="">Seleccione un cliente</option>
                                    <?php
				    $clientes = $conn->query("SELECT id_cliente, nombre, nombre_empresa FROM clientes ORDER BY nombre");
                                    while ($cliente = $clientes->fetch_assoc()):
                                    ?>
                                        <option value="<?php echo $cliente['id_cliente']; ?>">
                                            <?php echo htmlspecialchars($cliente['nombre'] . ' - ' . $cliente['nombre_empresa']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="prioridad">Prioridad:</label>
                                <select name="prioridad" id="prioridad" required>
                                    <option value="Baja">Baja</option>
                                    <option value="Media" selected>Media</option>
                                    <option value="Alta">Alta</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="titulo">Título:</label>
                            <input type="text" name="titulo" id="titulo" required>
                        </div>

                        <div class="form-group">
                            <label for="descripcion">Descripción:</label>
                            <textarea name="descripcion" id="descripcion" required></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">Crear Incidencia</button>
                        <a href="empleado.php" class="btn btn-secondary">Cancelar</a>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <!-- LISTADO DE INCIDENCIAS -->
            <div class="card">
                <div class="card-header">
                    <h2>Mis Incidencias</h2>
                </div>
                <div class="card-body">
                    <?php if ($incidencias->num_rows > 0): ?>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Título</th>
                                        <th>Cliente</th>
                                        <th>Estado</th>
                                        <th>Prioridad</th>
                                        <th>Fecha</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($inc = $incidencias->fetch_assoc()): ?>
                                        <tr>
                                            <td>#<?php echo $inc['id_incidencia']; ?></td>
                                            <td><?php echo htmlspecialchars($inc['titulo']); ?></td>
                                            <td><?php echo htmlspecialchars($inc['nombre_empresa']); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo strtolower(str_replace(' ', '', $inc['estado'])); ?>">
                                                    <?php echo $inc['estado']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?php echo strtolower($inc['prioridad']); ?>">
                                                    <?php echo $inc['prioridad']; ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('d/m/Y', strtotime($inc['fecha_creacion'])); ?></td>
                                            <td>
                                                <a href="ver_incidencia.php?id=<?php echo $inc['id_incidencia']; ?>" class="btn btn-sm btn-secondary">
                                                    Ver Detalles
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p>No has creado ninguna incidencia todavía.</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

    </div>
</body>
</html>
<?php
$conn->close();
?>
