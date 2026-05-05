<?php
// Solo pueden entrar los administradores
require_once 'includes/config.php';
requiere_rol('admin');

// Direccion del servidor Flask en Windows
$flask = 'http://192.168.50.10:5000';

// Lista de servidores que vamos a monitorizar
$servidores = array(
    "web"      => array("nombre" => "Balanceador Web", "ip" => "192.168.50.30", "servicio" => "NGINX",   "accion" => "estado_nginx"),
    "backend1" => array("nombre" => "Backend 1",       "ip" => "192.168.50.41", "servicio" => "APACHE",  "accion" => "estado_apache"),
    "backend2" => array("nombre" => "Backend 2",       "ip" => "192.168.50.42", "servicio" => "APACHE",  "accion" => "estado_apache"),
    "database" => array("nombre" => "Base de Datos",   "ip" => "192.168.60.50", "servicio" => "MARIADB", "accion" => "estado_mariadb"),
    "firewall" => array("nombre" => "Firewall",        "ip" => "192.168.50.20", "servicio" => "SISTEMA", "accion" => "syslog"),
);

// Variables para guardar el resultado
$resultado      = null;
$accion_label   = null;
$servidor_label = null;

// Si se ha pulsado un boton del formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $accion   = $_POST['accion'];
    $servidor = $_POST['servidor'];

    // Llamamos al Flask con la accion y el servidor elegido
    $url = $flask . '/estado?accion=' . $accion . '&servidor=' . $servidor;
    $respuesta = @file_get_contents($url);

    if ($respuesta == false) {
        $resultado = "Error: No se pudo conectar al servidor Flask en Windows";
    } else {
        $json = json_decode($respuesta, true);
        if (isset($json['error'])) {
            $resultado = "Error: " . $json['error'];
        } else {
            $resultado = $json['resultado'];
        }
    }

    $accion_label   = $accion;
    $servidor_label = $servidores[$servidor]['nombre'];
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Estado del Sistema - Verificanet</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <style>
        .cabecera {
            background: linear-gradient(135deg, #1e40af, #3b82f6);
            padding: 16px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .cabecera .titulo {
            color: white;
            font-size: 20px;
            font-weight: bold;
        }

        .cabecera .titulo span {
            color: rgba(255, 255, 255, 0.7);
            font-size: 15px;
            font-weight: normal;
            margin-left: 8px;
        }

        .cabecera .usuario {
            color: rgba(255, 255, 255, 0.85);
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .cabecera a.cerrar {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 7px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 13px;
        }

        .menu {
            background: white;
            border-bottom: 1px solid #e5e7eb;
            padding: 0 30px;
            display: flex;
        }

        .menu a {
            color: #6b7280;
            text-decoration: none;
            padding: 14px 16px;
            font-size: 14px;
            font-weight: 500;
            border-bottom: 2px solid transparent;
        }

        .menu a:hover {
            color: #2563eb;
        }

        .menu a.activo {
            color: #2563eb;
            border-bottom-color: #2563eb;
        }

        .contenido {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 30px;
        }

        .contenido h2 {
            color: #1e40af;
            font-size: 22px;
            margin-bottom: 4px;
        }

        .contenido p.sub {
            color: #6b7280;
            font-size: 13px;
            margin-bottom: 20px;
        }

        .aviso {
            background: #fef9c3;
            border: 1px solid #fde047;
            color: #854d0e;
            padding: 12px 16px;
            border-radius: 6px;
            font-size: 13px;
            margin-bottom: 24px;
        }

        .resultado {
            background: #1e293b;
            color: #e2e8f0;
            font-family: monospace;
            font-size: 13px;
            padding: 20px;
            border-radius: 8px;
            white-space: pre-wrap;
            margin-bottom: 28px;
            line-height: 1.6;
        }

        .resultado .etiqueta {
            color: #93c5fd;
            font-weight: bold;
            display: block;
            margin-bottom: 10px;
        }

        .tarjetas {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 20px;
        }

        .tarjeta {
            background: white;
            border: 1px solid #dbeafe;
            border-radius: 10px;
            padding: 18px;
            box-shadow: 0 2px 6px rgba(59, 130, 246, 0.08);
        }

        .tarjeta h3 {
            color: #1e40af;
            font-size: 15px;
            margin-bottom: 5px;
        }

        .tarjeta p {
            color: #6b7280;
            font-size: 13px;
            margin-bottom: 3px;
        }

        .tarjeta p strong {
            color: #2563eb;
        }

        .tarjeta .botones {
            display: flex;
            gap: 8px;
            margin-top: 14px;
            flex-wrap: wrap;
        }

        .btn-ver {
            background: #2563eb;
            color: white;
            border: none;
            padding: 7px 13px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
        }

        .btn-ver:hover {
            background: #1d4ed8;
        }

        .btn-reiniciar {
            background: #dc2626;
            color: white;
            border: none;
            padding: 7px 13px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
        }

        .btn-reiniciar:hover {
            background: #b91c1c;
        }

        /* Botones de consulta BD en verde */
        .btn-bd {
            background: #059669;
            color: white;
            border: none;
            padding: 7px 13px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            margin-top: 6px;
        }

        .btn-bd:hover {
            background: #047857;
        }

        /* Separador dentro de la tarjeta BD */
        .separador {
            border: none;
            border-top: 1px solid #dbeafe;
            margin: 12px 0;
        }

        .subtitulo-bd {
            color: #6b7280;
            font-size: 12px;
            margin-bottom: 6px;
        }
    </style>
</head>

<body>

    <div class="cabecera">
        <div class="titulo">
            VERIFICANET <span>/ Estado del Sistema</span>
        </div>
        <div class="usuario">
            <span>Bienvenido, <?php echo $_SESSION['usuario_nombre']; ?></span>
            <a href="logout.php" class="cerrar">Cerrar Sesion</a>
        </div>
    </div>

    <nav class="menu">
        <a href="admin.php">Incidencias</a>
        <a href="estado_sistema.php" class="activo">Estado del Sistema</a>
        <a href="manuales.php">Manuales</a>
    </nav>

    <div class="contenido">
        <h2>Monitorizacion de Infraestructura</h2>
        <p class="sub">Servidores consultados a traves del Windows Server (192.168.50.10)</p>

        <div class="aviso">
            La consulta puede tardar unos segundos ya que conecta con el Windows Server que a su vez consulta cada servidor Linux.
        </div>

        <!-- Contenedor de resultados -->
        <?php if ($resultado != null): ?>
            <div class="resultado">
                <span class="etiqueta">&gt; <?php echo $accion_label; ?> en <?php echo $servidor_label; ?></span>

                <?php
                // Detectamos si la respuesta viene con tabuladores (formato de tabla de base de datos)
                if (strpos($resultado, "\t") !== false) {
                    echo "<div style='overflow-x: auto; margin-top: 10px;'>";
                    echo "<table style='width:100%; border-collapse: collapse; font-family: sans-serif; font-size: 0.9em;'>";

                    $lineas = explode("\n", trim($resultado));
                    foreach ($lineas as $i => $linea) {
                        // Dividimos la línea por los tabuladores que envía MariaDB
                        $columnas = explode("\t", $linea);
                        echo "<tr style='border-bottom: 1px solid #475569;'>";

                        foreach ($columnas as $col) {
                            // Si es la primera fila, la ponemos en negrita como encabezado
                            $tag = ($i == 0) ? "th" : "td";
                            $style = "padding: 8px; text-align: left; color: " . ($i == 0 ? "#60a5fa" : "#78caf0") . ";";
                            echo "<$tag style='$style'>" . htmlspecialchars($col) . "</$tag>";
                        }
                        echo "</tr>";
                    }
                    echo "</table>";
                    echo "</div>";
                } else {
                    // Si no es una tabla (ej. estado de un servicio), lo mostramos como texto de consola
                    echo "<pre style='margin: 10px 0 0 0; white-space: pre-wrap; font-family: monospace; color: #ffffff;'>" . htmlspecialchars($resultado) . "</pre>";
                }
                ?>
            </div>
        <?php endif; ?>

        <div class="tarjetas">
            <?php foreach ($servidores as $clave => $info): ?>
                <div class="tarjeta">
                    <h3><?php echo $info['nombre']; ?></h3>
                    <p><?php echo $info['ip']; ?></p>
                    <p>Servicio: <strong><?php echo $info['servicio']; ?></strong></p>
                    <div class="botones">

                        <!-- Boton Comprobar Estado -->
                        <form method="POST">
                            <input type="hidden" name="servidor" value="<?php echo $clave; ?>">
                            <input type="hidden" name="accion" value="<?php echo $info['accion']; ?>">
                            <button type="submit" class="btn-ver">Comprobar Estado</button>
                        </form>

                        <!-- Boton Reiniciar (no aparece en firewall) -->
                        <?php if ($clave != 'firewall'): ?>
                            <form method="POST" onsubmit="return confirm('Reiniciar <?php echo $info['nombre']; ?>?');">
                                <input type="hidden" name="servidor" value="<?php echo $clave; ?>">
                                <input type="hidden" name="accion" value="reiniciar_<?php echo strtolower($info['servicio']); ?>">
                                <button type="submit" class="btn-reiniciar">Reiniciar</button>
                            </form>
                        <?php endif; ?>

                    </div>

                    <!-- Botones extra solo para la base de datos -->
                    <?php if ($clave == 'database'): ?>
                        <hr class="separador">
                        <p class="subtitulo-bd">Consultas a la base de datos:</p>
                        <div class="botones">

                            <form method="POST">
                                <input type="hidden" name="servidor" value="database">
                                <input type="hidden" name="accion" value="usuarios_bd">
                                <button type="submit" class="btn-bd">Ver Usuarios</button>
                            </form>

                            <form method="POST">
                                <input type="hidden" name="servidor" value="database">
                                <input type="hidden" name="accion" value="incidencias_bd">
                                <button type="submit" class="btn-bd">Ver Incidencias</button>
                            </form>

                            <form method="POST">
                                <input type="hidden" name="servidor" value="database">
                                <input type="hidden" name="accion" value="tablas_bd">
                                <button type="submit" class="btn-bd">Ver Tablas</button>
                            </form>

                        </div>
                    <?php endif; ?>

                </div>
            <?php endforeach; ?>
        </div>
    </div>

</body>

</html>