#!/bin/bash
export DEBIAN_FRONTEND=noninteractive

echo "=========================================="
echo "Configurando Base de Datos..."
echo "=========================================="

apt-get update
apt-get install -y mariadb-server mariadb-client

# Escuchar solo en la red interna
sed -i 's/^bind-address.*/bind-address = 192.168.60.50/' /etc/mysql/mariadb.conf.d/50-server.cnf
systemctl restart mariadb

# Crear base de datos y estructura
mysql -u root <<'EOSQL'
CREATE DATABASE IF NOT EXISTS verificanet_servicios;
USE verificanet_servicios;

CREATE TABLE IF NOT EXISTS departamentos (
    id_departamento INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT
);

CREATE TABLE IF NOT EXISTS empleados (
    id_empleado INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    telefono VARCHAR(20),
    id_departamento INT,
    fecha_contratacion DATE,
    FOREIGN KEY (id_departamento) REFERENCES departamentos(id_departamento)
);

CREATE TABLE IF NOT EXISTS clientes (
    id_cliente INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100),
    apellido VARCHAR(100),
    nombre_empresa VARCHAR(200),
    email VARCHAR(100) UNIQUE NOT NULL,
    telefono VARCHAR(20),
    direccion TEXT,
    tipo_cliente ENUM('particular', 'empresa') DEFAULT 'particular',
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS servicios (
    id_servicio INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    precio_base DECIMAL(10,2),
    tipo_servicio ENUM('soporte', 'mantenimiento', 'desarrollo', 'consultoria') DEFAULT 'soporte'
);

CREATE TABLE IF NOT EXISTS contratos (
    id_contrato INT AUTO_INCREMENT PRIMARY KEY,
    id_cliente INT,
    id_servicio INT,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE,
    estado ENUM('activo', 'inactivo', 'finalizado') DEFAULT 'activo',
    notas TEXT,
    FOREIGN KEY (id_cliente) REFERENCES clientes(id_cliente) ON DELETE CASCADE,
    FOREIGN KEY (id_servicio) REFERENCES servicios(id_servicio)
);

CREATE TABLE IF NOT EXISTS incidencias (
    id_incidencia INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(200) NOT NULL,
    descripcion TEXT,
    estado ENUM('reportada', 'en_proceso', 'resuelta', 'cerrada') DEFAULT 'reportada',
    prioridad ENUM('baja', 'media', 'alta', 'critica') DEFAULT 'media',
    id_cliente INT,
    id_empleado_asignado INT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_resolucion DATETIME,
    notas_resolucion TEXT,
    FOREIGN KEY (id_cliente) REFERENCES clientes(id_cliente) ON DELETE CASCADE,
    FOREIGN KEY (id_empleado_asignado) REFERENCES empleados(id_empleado)
);

CREATE TABLE IF NOT EXISTS usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    rol ENUM('admin', 'empleado', 'cliente') NOT NULL,
    id_empleado INT NULL,
    id_cliente INT NULL,
    activo BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_empleado) REFERENCES empleados(id_empleado) ON DELETE CASCADE,
    FOREIGN KEY (id_cliente) REFERENCES clientes(id_cliente) ON DELETE CASCADE
);

-- ========================================
-- DATOS: DEPARTAMENTOS
-- ========================================
INSERT INTO departamentos (nombre, descripcion) VALUES
('Soporte Técnico', 'Atención al cliente y soporte técnico'),
('Ventas', 'Equipo comercial y ventas'),
('Administración', 'Gestión administrativa'),
('Sistemas', 'Administración de sistemas y desarrollo'),
('Auditoría', 'Auditoría y seguridad');

-- ========================================
-- DATOS: EMPLEADOS (SINCRONIZADOS CON WINDOWS SERVER)
-- ========================================
INSERT INTO empleados (nombre, apellido, email, telefono, id_departamento, fecha_contratacion) VALUES
-- Soporte Técnico
('María', 'González', 'mgonzalez@verificanet.local', '666111222', 1, '2022-01-15'),
('Juan', 'Martínez', 'jmartinez@verificanet.local', '666222333', 1, '2022-03-20'),

-- Ventas
('Laura', 'Fernández', 'lfernandez@verificanet.local', '666555666', 2, '2022-06-15'),
('Sofía', 'Torres', 'storres@verificanet.local', '666777888', 2, '2023-01-10'),

-- Administración
('Ana', 'Rodríguez', 'arodriguez@verificanet.local', '666333444', 3, '2021-11-10'),
('Miguel', 'Navarro', 'mnavarro@verificanet.local', '666888999', 3, '2023-03-15'),

-- Sistemas
('Carlos', 'López', 'clopez@verificanet.local', '666444555', 4, '2023-02-01'),
('Admin', 'VerificaNet', 'adminvnet@verificanet.local', '666999000', 4, '2021-01-01'),

-- Auditoría
('Pedro', 'Sánchez', 'psanchez@verificanet.local', '666666777', 5, '2021-08-20');

-- ========================================
-- DATOS: CLIENTES
-- ========================================
INSERT INTO clientes (nombre, apellido, email, telefono, tipo_cliente) VALUES
('Particular', 'Test', 'cliente1@example.com', '611222333', 'particular'),
(NULL, NULL, 'contacto@empresa.com', '622333444', 'empresa');

UPDATE clientes SET nombre_empresa = 'Empresa Test S.L.' WHERE email = 'contacto@empresa.com';

-- ========================================
-- DATOS: SERVICIOS
-- ========================================
INSERT INTO servicios (nombre, descripcion, precio_base, tipo_servicio) VALUES
('Soporte Técnico Básico', 'Asistencia técnica remota 24/7', 299.99, 'soporte'),
('Mantenimiento Preventivo', 'Mantenimiento mensual de sistemas', 499.99, 'mantenimiento'),
('Desarrollo Web', 'Desarrollo de aplicaciones web a medida', 2999.99, 'desarrollo'),
('Consultoría IT', 'Consultoría en infraestructura y seguridad', 1499.99, 'consultoria'),
('Auditoría de Seguridad', 'Análisis completo de seguridad informática', 1999.99, 'consultoria'),
('Backup Cloud', 'Servicio de copias de seguridad en la nube', 199.99, 'soporte');

-- ========================================
-- DATOS: CONTRATOS
-- ========================================
INSERT INTO contratos (id_cliente, id_servicio, fecha_inicio, estado) VALUES
(1, 1, '2024-01-01', 'activo'),
(1, 6, '2024-01-01', 'activo'),
(2, 2, '2024-02-01', 'activo'),
(2, 3, '2024-02-15', 'activo'),
(2, 5, '2024-03-01', 'activo');

-- ========================================
-- DATOS: INCIDENCIAS
-- ========================================
INSERT INTO incidencias (titulo, descripcion, estado, prioridad, id_cliente, id_empleado_asignado) VALUES
('Error en conexión VPN', 'No se puede conectar a la VPN corporativa', 'en_proceso', 'alta', 2, 1),
('Lentitud en servidor', 'El servidor web responde muy lento', 'reportada', 'media', 2, 2),
('Backup fallido', 'El backup automático no se ejecutó anoche', 'resuelta', 'alta', 1, 1),
('Solicitud nueva funcionalidad', 'Añadir módulo de reportes en la aplicación', 'reportada', 'baja', 2, 7),
('Problema con correo', 'No recibo correos desde esta mañana', 'cerrada', 'critica', 1, 1),
('Actualización sistema', 'Necesito actualizar el sistema operativo', 'en_proceso', 'media', 2, 2);

-- ========================================
-- DATOS: USUARIOS WEB (SINCRONIZADOS CON WINDOWS SERVER)
-- ========================================

-- ADMINISTRADORES
INSERT INTO usuarios (username, password, rol, id_empleado) VALUES
('adminvnet', 'Verific@2024!', 'admin', 8);

-- EMPLEADOS (TODOS con contraseña unificada Verific@2024!)
INSERT INTO usuarios (username, password, rol, id_empleado) VALUES
('mgonzalez', 'Verific@2024!', 'empleado', 1),
('jmartinez', 'Verific@2024!', 'empleado', 2),
('lfernandez', 'Verific@2024!', 'empleado', 3),
('storres', 'Verific@2024!', 'empleado', 4),
('arodriguez', 'Verific@2024!', 'empleado', 5),
('mnavarro', 'Verific@2024!', 'empleado', 6),
('clopez', 'Verific@2024!', 'empleado', 7),
('psanchez', 'Verific@2024!', 'empleado', 9);

-- CLIENTES
INSERT INTO usuarios (username, password, rol, id_cliente) VALUES
('cliente1', 'cliente123', 'cliente', 1),
('empresa1', 'empresa123', 'cliente', 2);

-- ========================================
-- USUARIOS DE BASE DE DATOS (para backends)
-- ========================================
CREATE USER IF NOT EXISTS 'verificanet_user'@'192.168.60.41' IDENTIFIED BY 'Verific@2024!';
CREATE USER IF NOT EXISTS 'verificanet_user'@'192.168.60.42' IDENTIFIED BY 'Verific@2024!';

GRANT SELECT, INSERT, UPDATE, DELETE ON verificanet_servicios.* TO 'verificanet_user'@'192.168.60.41';
GRANT SELECT, INSERT, UPDATE, DELETE ON verificanet_servicios.* TO 'verificanet_user'@'192.168.60.42';

FLUSH PRIVILEGES;

-- ========================================
-- VERIFICACIÓN FINAL
-- ========================================
SELECT '========================================' as '';
SELECT 'RESUMEN DE SINCRONIZACIÓN' as '';
SELECT '========================================' as '';

SELECT 
    d.nombre as 'Departamento',
    COUNT(e.id_empleado) as 'Empleados',
    COUNT(u.id_usuario) as 'Con usuario web'
FROM departamentos d
LEFT JOIN empleados e ON d.id_departamento = e.id_departamento
LEFT JOIN usuarios u ON u.id_empleado = e.id_empleado
GROUP BY d.id_departamento, d.nombre
ORDER BY d.nombre;

SELECT '========================================' as '';
SELECT 'USUARIOS SINCRONIZADOS' as '';
SELECT '========================================' as '';

SELECT 
    u.username as 'Usuario',
    CONCAT(e.nombre, ' ', e.apellido) as 'Nombre',
    d.nombre as 'Departamento',
    u.rol as 'Rol'
FROM usuarios u
LEFT JOIN empleados e ON u.id_empleado = e.id_empleado
LEFT JOIN departamentos d ON e.id_departamento = d.id_departamento
WHERE u.rol IN ('empleado', 'admin')
ORDER BY d.nombre, e.nombre;

EOSQL

# Firewall interno (solo backends pueden acceder)
apt-get install -y iptables

iptables -A INPUT -p tcp --dport 3306 -s 192.168.60.41 -j ACCEPT
iptables -A INPUT -p tcp --dport 3306 -s 192.168.60.42 -j ACCEPT
iptables -A INPUT -p tcp --dport 3306 -j DROP

echo ""
echo "=========================================="
echo " BASE DE DATOS CONFIGURADA Y SINCRONIZADA"
echo " - 9 empleados en 5 departamentos"
echo " - 8 usuarios empleados + 1 admin"
echo " - TODOS con contraseña: Verific@2024!"
echo " - 2 clientes (particular + empresa)"
echo "=========================================="