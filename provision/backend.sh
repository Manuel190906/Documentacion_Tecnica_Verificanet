#!/bin/bash
export DEBIAN_FRONTEND=noninteractive

echo "=========================================="
echo "Configurando Backend PHP..."
echo "=========================================="

apt-get update
apt-get install -y apache2 php php-mysql

# 1. Limpieza inicial (Se hace antes de poner cualquier archivo nuevo)
echo ">>> Limpiando directorio web..."
rm -rf /var/www/html/*

# 2. Instalación de archivos del proyecto (html-backup)
if [ -d "/vagrant/html-backup" ]; then
    echo ">>> Copiando archivos desde html-backup..."
    cp -r /vagrant/html-backup/. /var/www/html/
    echo ">>> Archivos copiados con éxito."
else
    echo ">>> ERROR: No se encontró la carpeta /vagrant/html-backup"
    # Creamos un index de emergencia si no hay archivos
    echo "<?php echo '<h1>Backend funcionando en ' . gethostname() . '</h1>'; ?>" > /var/www/html/index.php
fi

# 3. Creación de la API (Se hace DESPUÉS de limpiar la carpeta)
echo ">>> Generando archivo api.php..."
cat > /var/www/html/api.php << 'EOFPHP'
<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

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

$action = $_GET['action'] ?? 'health';

switch($action) {
    case 'health':
        echo json_encode([
            "status" => "ok", 
            "database" => "connected",
            "server" => gethostname()
        ]);
        break;

    case 'login':
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        // ... resto de tu código de login ...
        break;

    default:
        echo json_encode(["error" => "Acción no válida: $action"]);
}
$conn->close();
?>
EOFPHP

# 4. Ajuste de permisos FINAL (Corregido el nombre del usuario)
echo ">>> Aplicando permisos finales..."
chown -R www-data:www-data /var/www/html
chmod -R 755 /var/www/html

# 5. Reiniciar y habilitar servicio
rm -f /var/www/html/index.html
systemctl enable apache2
systemctl restart apache2

echo ""
echo "=========================================="
echo " BACKEND PHP CONFIGURADO CORRECTAMENTE"
echo "=========================================="