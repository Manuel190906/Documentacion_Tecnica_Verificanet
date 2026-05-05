<?php
require_once 'includes/config.php';
requiere_login();

$conn = conectar_bd();

// Determinar página de volver según departamento
$volver = 'empleado.php';
if (tiene_rol('cliente')) {
    $volver = 'servicios.php';
} elseif (isset($_SESSION['departamento'])) {
    switch ($_SESSION['departamento']) {
        case 'Ventas': $volver = 'ventas.php'; break;
        case 'Administración': $volver = 'administracion.php'; break;
        case 'Auditoría': $volver = 'auditoria.php'; break;
    }
}

// Buscar si hay término de búsqueda
$busqueda = isset($_GET['q']) ? trim($_GET['q']) : '';
$resultados = [];

if (!empty($busqueda)) {
    $stmt = $conn->prepare("
        SELECT id_articulo, titulo, problema, veces_util 
        FROM kb_articulos 
        WHERE titulo LIKE ? OR problema LIKE ? OR keywords LIKE ?
        ORDER BY veces_util DESC, fecha_creacion DESC
    ");
    $like_busqueda = '%' . $busqueda . '%';
    $stmt->bind_param('sss', $like_busqueda, $like_busqueda, $like_busqueda);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $resultados[] = $row;
    }
}

// Categorías populares
$populares = $conn->query("
    SELECT id_articulo, titulo, veces_util 
    FROM kb_articulos 
    ORDER BY veces_util DESC 
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Manuales de Ayuda - Verificanet</title>
    <link rel="stylesheet" href="css/public.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <style>
        .search-box {
            max-width: 600px;
            margin: 30px auto;
            padding: 20px;
            background: #f5f5f5;
            border-radius: 8px;
        }
        .search-input {
            width: 100%;
            padding: 12px;
            font-size: 16px;
            border: 2px solid #ddd;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        .btn-search {
            background: #007bff;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .btn-search:hover { background: #0056b3; }
        .article-card {
            background: white;
            padding: 20px;
            margin: 15px 0;
            border-radius: 8px;
            border-left: 4px solid #007bff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .article-title {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        .article-preview { color: #666; margin-bottom: 15px; }
        .article-meta { font-size: 12px; color: #999; margin-bottom: 10px; }
        .btn-ver {
            background: #28a745;
            color: white;
            padding: 8px 20px;
            text-decoration: none;
            border-radius: 4px;
            display: inline-block;
        }
        .btn-ver:hover { background: #218838; }
        .btn-back {
            display: inline-block;
            margin: 20px 0;
            color: #007bff;
            text-decoration: none;
        }
        .popular-section { margin: 30px 0; }
        .popular-list { list-style: none; padding: 0; }
        .popular-list li { padding: 10px; border-bottom: 1px solid #eee; }
        .popular-list a { color: #007bff; text-decoration: none; }
        .popular-list a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <a href="<?php echo $volver; ?>" class="btn-back">← Volver</a>
        
        <h1>📚 Manuales de Ayuda</h1>
        <p>Busca soluciones a problemas comunes antes de crear una incidencia</p>

        <div class="search-box">
            <form method="GET" action="manuales.php">
                <input type="text" 
                       name="q" 
                       class="search-input" 
                       placeholder="Escribe tu problema: ej. DHCP, correo, contraseña..." 
                       value="<?php echo htmlspecialchars($busqueda); ?>"
                       autofocus>
                <button type="submit" class="btn-search">🔍 Buscar Solución</button>
            </form>
        </div>

        <?php if (!empty($busqueda)): ?>
            <h2>Resultados para: "<?php echo htmlspecialchars($busqueda); ?>"</h2>
            
            <?php if (count($resultados) > 0): ?>
                <?php foreach ($resultados as $art): ?>
                    <div class="article-card">
                        <div class="article-title"><?php echo htmlspecialchars($art['titulo']); ?></div>
                        <div class="article-preview"><?php echo htmlspecialchars(substr($art['problema'], 0, 150)); ?>...</div>
                        <div class="article-meta">
                             Esta solución ha ayudado a <?php echo $art['veces_util']; ?> personas
                        </div>
                        <a href="ver_manual.php?id=<?php echo $art['id_articulo']; ?>" class="btn-ver">Ver Solución Completa</a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="article-card">
                    <p> No se encontraron manuales para "<?php echo htmlspecialchars($busqueda); ?>"</p>
                    <p>Puedes crear una incidencia y nuestro equipo te ayudará.</p>
                    <a href="<?php echo tiene_rol('cliente') ? 'servicios.php' : $volver . '?accion=nueva'; ?>" class="btn-ver">Crear Incidencia</a>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="popular-section">
                <h2> Problemas más consultados</h2>
                <ul class="popular-list">
                    <?php foreach ($populares as $pop): ?>
                        <li>
                            <a href="ver_manual.php?id=<?php echo $pop['id_articulo']; ?>">
                                <?php echo htmlspecialchars($pop['titulo']); ?>
                                <span style="color:#999;font-size:12px;">(<?php echo $pop['veces_util']; ?> personas)</span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="popular-section">
                <h3> Prueba buscar:</h3>
                <p>
                    <a href="?q=correo">Correo</a> | 
                    <a href="?q=contraseña">Contraseña</a> | 
                    <a href="?q=internet">Internet</a> | 
                    <a href="?q=vpn">VPN</a> | 
                    <a href="?q=lentitud">Lentitud</a>
                </p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>