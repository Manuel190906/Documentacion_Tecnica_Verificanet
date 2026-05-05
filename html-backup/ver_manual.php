<?php
require_once 'includes/config.php';
requiere_login();

$conn = conectar_bd();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id === 0) {
    redirigir('manuales.php');
}

// Obtener artículo
$stmt = $conn->prepare("SELECT * FROM kb_articulos WHERE id_articulo = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$articulo = $stmt->get_result()->fetch_assoc();

if (!$articulo) {
    redirigir('manuales.php');
}

// Marcar como útil
if (isset($_POST['marcar_util'])) {
    $conn->query("UPDATE kb_articulos SET veces_util = veces_util + 1 WHERE id_articulo = $id");
    $mensaje_exito = "¡Gracias por tu feedback! Nos alegra que te haya ayudado.";
    // Recargar artículo actualizado
    $stmt->execute();
    $articulo = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($articulo['titulo']); ?> - Verificanet</title>
    <link rel="stylesheet" href="css/public.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <style>
        .manual-container {
            max-width: 800px;
            margin: 30px auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .manual-title {
            font-size: 28px;
            color: #333;
            margin-bottom: 20px;
            border-bottom: 3px solid #007bff;
            padding-bottom: 15px;
        }
        .manual-problema {
            background: #f8f9fa;
            padding: 15px;
            border-left: 4px solid #ffc107;
            margin: 20px 0;
            border-radius: 4px;
        }
        .manual-solucion {
            background: #e7f4e7;
            padding: 20px;
            border-left: 4px solid #28a745;
            margin: 20px 0;
            border-radius: 4px;
            white-space: pre-line;
            line-height: 1.8;
        }
        .manual-meta {
            color: #666;
            font-size: 14px;
            margin: 15px 0;
        }
        .feedback-section {
            margin: 30px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            text-align: center;
        }
        .btn-util {
            background: #28a745;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .btn-util:hover {
            background: #218838;
        }
        .btn-incidencia {
            background: #dc3545;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            display: inline-block;
            margin-left: 10px;
        }
        .btn-back {
            display: inline-block;
            margin: 20px 0;
            color: #007bff;
            text-decoration: none;
        }
        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 4px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="manuales.php" class="btn-back">← Volver a búsqueda</a>

        <div class="manual-container">
            <h1 class="manual-title"><?php echo htmlspecialchars($articulo['titulo']); ?></h1>

            <div class="manual-meta">
                 Publicado: <?php echo date('d/m/Y', strtotime($articulo['fecha_creacion'])); ?>
                |  Ha ayudado a <strong><?php echo $articulo['veces_util']; ?></strong> personas
            </div>

            <div class="manual-problema">
                <strong> Problema:</strong><br>
                <?php echo htmlspecialchars($articulo['problema']); ?>
            </div>

            <div class="manual-solucion">
                <strong> Solución:</strong><br><br>
                <?php echo htmlspecialchars($articulo['solucion']); ?>
            </div>

            <?php if (isset($mensaje_exito)): ?>
                <div class="alert-success"><?php echo $mensaje_exito; ?></div>
            <?php endif; ?>

            <div class="feedback-section">
                <h3>¿Te ayudó esta solución?</h3>
                <form method="POST" style="display: inline;">
                    <button type="submit" name="marcar_util" class="btn-util">
                         Sí, me funcionó
                    </button>
                </form>
                <a href="<?php echo tiene_rol('cliente') ? 'servicios.php' : 'empleado.php?accion=nueva'; ?>" class="btn-incidencia">
                     No funcionó, crear incidencia
                </a>
            </div>
        </div>
    </div>
</body>
</html>