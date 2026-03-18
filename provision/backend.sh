#!/bin/bash
export DEBIAN_FRONTEND=noninteractive

echo "=========================================="
echo "Configurando Backend PHP..."
echo "=========================================="

apt-get update
apt-get install -y apache2 php php-mysql

# Página principal
echo "<?php echo '<h1>Backend funcionando en ' . gethostname() . '</h1>'; ?>" > /var/www/html/index.php

systemctl enable apache2
systemctl restart apache2

# ==========================================
# API PHP COMPLETA
# ==========================================
cat > /var/www/html/api.php << 'EOFPHP'
<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Manejar OPTIONS para CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Conexión a la base de datos
$host = "192.168.60.50";
$user = "verificanet_user";
$pass = "Verific@2024!";
$db   = "verificanet_servicios";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    echo json_encode(["error" => "Error de conexión: " . $conn->connect_error]);
    exit;
}

$conn->set_charset("utf8");

// Obtener acción
$action = $_GET['action'] ?? 'health';

switch($action) {

    // ==========================================
    // HEALTH CHECK
    // ==========================================
    case 'health':
        echo json_encode([
            "status" => "ok", 
            "database" => "connected",
            "server" => gethostname()
        ]);
        break;

    // ==========================================
    // LOGIN
    // ==========================================
    case 'login':
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        $stmt = $conn->prepare("
            SELECT u.*, 
                   e.nombre as emp_nombre, e.apellido as emp_apellido,
                   c.nombre as cli_nombre, c.apellido as cli_apellido, c.nombre_empresa
            FROM usuarios u
            LEFT JOIN empleados e ON u.id_empleado = e.id_empleado
            LEFT JOIN clientes c ON u.id_cliente = c.id_cliente
            WHERE u.username = ? AND u.password = ? AND u.activo = 1
        ");
        
        $stmt->bind_param("ss", $username, $password);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Construir nombre completo
            $nombre_completo = '';
            if ($user['emp_nombre']) {
                $nombre_completo = $user['emp_nombre'] . ' ' . $user['emp_apellido'];
            } elseif ($user['cli_nombre']) {
                $nombre_completo = $user['cli_nombre'] . ' ' . $user['cli_apellido'];
            } elseif ($user['nombre_empresa']) {
                $nombre_completo = $user['nombre_empresa'];
            }
            
            echo json_encode([
                "success" => true,
                "user" => [
                    "id_usuario" => $user['id_usuario'],
                    "username" => $user['username'],
                    "rol" => $user['rol'],
                    "nombre" => $nombre_completo,
                    "id_empleado" => $user['id_empleado'],
                    "id_cliente" => $user['id_cliente']
                ]
            ]);
        } else {
            echo json_encode(["success" => false, "error" => "Credenciales inválidas"]);
        }
        break;

    // ==========================================
    // REGISTRO DE NUEVO CLIENTE
    // ==========================================
    case 'register':
        $data = json_decode(file_get_contents('php://input'), true);
        
        $tipo = $data['tipo'] ?? 'particular';
        $email = $data['email'] ?? '';
        $telefono = $data['telefono'] ?? '';
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';
        
        // Verificar si el usuario ya existe
        $stmt = $conn->prepare("SELECT id_usuario FROM usuarios WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            echo json_encode(["success" => false, "error" => "El usuario ya existe"]);
            break;
        }
        
        // Verificar si el email ya existe
        $stmt = $conn->prepare("SELECT id_cliente FROM clientes WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            echo json_encode(["success" => false, "error" => "El email ya está registrado"]);
            break;
        }
        
        // Insertar cliente
        if ($tipo === 'empresa') {
            $nombre_empresa = $data['nombre_empresa'] ?? '';
            $stmt = $conn->prepare("
                INSERT INTO clientes (nombre_empresa, email, telefono, tipo_cliente)
                VALUES (?, ?, ?, 'empresa')
            ");
            $stmt->bind_param("sss", $nombre_empresa, $email, $telefono);
        } else {
            $nombre = $data['nombre'] ?? '';
            $apellido = $data['apellido'] ?? '';
            $stmt = $conn->prepare("
                INSERT INTO clientes (nombre, apellido, email, telefono, tipo_cliente)
                VALUES (?, ?, ?, ?, 'particular')
            ");
            $stmt->bind_param("ssss", $nombre, $apellido, $email, $telefono);
        }
        
        if ($stmt->execute()) {
            $id_cliente = $conn->insert_id;
            
            // Crear usuario
            $stmt = $conn->prepare("
                INSERT INTO usuarios (username, password, rol, id_cliente, activo)
                VALUES (?, ?, 'cliente', ?, 1)
            ");
            $stmt->bind_param("ssi", $username, $password, $id_cliente);
            
            if ($stmt->execute()) {
                echo json_encode(["success" => true, "message" => "Usuario registrado correctamente"]);
            } else {
                echo json_encode(["success" => false, "error" => "Error al crear el usuario"]);
            }
        } else {
            echo json_encode(["success" => false, "error" => "Error al registrar el cliente"]);
        }
        break;

    // ==========================================
    // ESTADÍSTICAS
    // ==========================================
    case 'stats':
        $stats = [];
        
        $result = $conn->query("SELECT COUNT(*) as total FROM clientes");
        $stats['total_clientes'] = $result->fetch_assoc()['total'];
        
        $result = $conn->query("SELECT COUNT(*) as total FROM incidencias");
        $stats['total_incidencias'] = $result->fetch_assoc()['total'];
        
        $result = $conn->query("SELECT COUNT(*) as total FROM incidencias WHERE estado IN ('reportada', 'en_proceso')");
        $stats['incidencias_abiertas'] = $result->fetch_assoc()['total'];
        
        echo json_encode($stats);
        break;

    // ==========================================
    // LISTAR INCIDENCIAS
    // ==========================================
    case 'incidencias':
        $where = "";
        
        // Filtrar por empleado
        if (isset($_GET['empleado'])) {
            $id_empleado = intval($_GET['empleado']);
            $where = " WHERE i.id_empleado_asignado = $id_empleado";
        }
        
        // Filtrar por cliente
        if (isset($_GET['cliente'])) {
            $id_cliente = intval($_GET['cliente']);
            $where = " WHERE i.id_cliente = $id_cliente";
        }
        
        $query = "
            SELECT i.*, 
                   c.nombre as cliente_nombre, 
                   c.apellido as cliente_apellido,
                   c.nombre_empresa,
                   e.nombre as empleado_nombre,
                   e.apellido as empleado_apellido
            FROM incidencias i
            LEFT JOIN clientes c ON i.id_cliente = c.id_cliente
            LEFT JOIN empleados e ON i.id_empleado_asignado = e.id_empleado
            $where
            ORDER BY i.fecha_creacion DESC
            LIMIT 50
        ";
        
        $result = $conn->query($query);
        $incidencias = [];
        
        while($row = $result->fetch_assoc()) {
            // Construir nombre cliente
            $nombre_cliente = '';
            if ($row['nombre_empresa']) {
                $nombre_cliente = $row['nombre_empresa'];
            } elseif ($row['cliente_nombre']) {
                $nombre_cliente = $row['cliente_nombre'] . ' ' . $row['cliente_apellido'];
            }
            
            $row['cliente_nombre_completo'] = $nombre_cliente;
            $incidencias[] = $row;
        }
        
        echo json_encode($incidencias);
        break;

    // ==========================================
    // CREAR INCIDENCIA
    // ==========================================
    case 'crear_incidencia':
        $data = json_decode(file_get_contents('php://input'), true);
        
        $titulo = $data['titulo'] ?? '';
        $descripcion = $data['descripcion'] ?? '';
        $prioridad = $data['prioridad'] ?? 'media';
        $id_cliente = $data['id_cliente'] ?? 0;
        
        if (empty($titulo) || empty($descripcion) || $id_cliente == 0) {
            echo json_encode(["success" => false, "error" => "Datos incompletos"]);
            break;
        }
        
        $stmt = $conn->prepare("
            INSERT INTO incidencias (titulo, descripcion, prioridad, id_cliente, estado, fecha_creacion)
            VALUES (?, ?, ?, ?, 'reportada', NOW())
        ");
        
        $stmt->bind_param("sssi", $titulo, $descripcion, $prioridad, $id_cliente);
        
        if ($stmt->execute()) {
            echo json_encode([
                "success" => true, 
                "id_incidencia" => $conn->insert_id,
                "message" => "Incidencia creada correctamente"
            ]);
        } else {
            echo json_encode(["success" => false, "error" => "Error al crear la incidencia"]);
        }
        break;

    // ==========================================
    // ACTUALIZAR INCIDENCIA
    // ==========================================
    case 'actualizar_incidencia':
        $data = json_decode(file_get_contents('php://input'), true);
        
        $id_incidencia = $data['id_incidencia'] ?? 0;
        $updates = [];
        $params = [];
        $types = "";
        
        if (isset($data['estado'])) {
            $updates[] = "estado = ?";
            $params[] = $data['estado'];
            $types .= "s";
        }
        
        if (isset($data['prioridad'])) {
            $updates[] = "prioridad = ?";
            $params[] = $data['prioridad'];
            $types .= "s";
        }
        
        if (isset($data['id_empleado_asignado'])) {
            $updates[] = "id_empleado_asignado = ?";
            $params[] = $data['id_empleado_asignado'];
            $types .= "i";
        }
        
        if (empty($updates)) {
            echo json_encode(["success" => false, "error" => "No hay datos para actualizar"]);
            break;
        }
        
        $params[] = $id_incidencia;
        $types .= "i";
        
        $query = "UPDATE incidencias SET " . implode(", ", $updates) . " WHERE id_incidencia = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        
        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Incidencia actualizada"]);
        } else {
            echo json_encode(["success" => false, "error" => "Error al actualizar"]);
        }
        break;

    // ==========================================
    // ACCIÓN NO VÁLIDA
    // ==========================================
    default:
        echo json_encode(["error" => "Acción no válida: $action"]);
}

$conn->close();
?>
EOFPHP

# Permisos
chown -R www-data:www-data /var/www/html
chmod -R 755 /var/www/html

echo ""
echo "=========================================="
echo " BACKEND PHP CONFIGURADO CORRECTAMENTE"
echo " Funciones API disponibles:"
echo "  - health"
echo "  - login"
echo "  - register"
echo "  - stats"
echo "  - incidencias"
echo "  - crear_incidencia"
echo "  - actualizar_incidencia"
echo "=========================================="