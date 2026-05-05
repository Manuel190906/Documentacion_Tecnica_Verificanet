#!/bin/bash
export DEBIAN_FRONTEND=noninteractive

echo "=========================================="
echo "Configurando Base de Datos..."
echo "=========================================="

apt-get update
apt-get install -y mariadb-server mariadb-client

# Escuchar en todas las interfaces
sed -i 's/^bind-address.*/bind-address = 0.0.0.0/' /etc/mysql/mariadb.conf.d/50-server.cnf
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
    nombre VARCHAR(100),
    email VARCHAR(100),
    rol ENUM('admin', 'empleado', 'tecnico', 'cliente') NOT NULL,
    id_empleado INT NULL,
    id_cliente INT NULL,
    activo BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_empleado) REFERENCES empleados(id_empleado) ON DELETE CASCADE,
    FOREIGN KEY (id_cliente) REFERENCES clientes(id_cliente) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS kb_articulos (
    id_articulo INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(200) NOT NULL,
    problema TEXT,
    solucion TEXT,
    keywords VARCHAR(500),
    veces_util INT DEFAULT 0,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- DEPARTAMENTOS
INSERT INTO departamentos (nombre, descripcion) VALUES
('Soporte Técnico', 'Atención al cliente y soporte técnico'),
('Ventas', 'Equipo comercial y ventas'),
('Administración', 'Gestión administrativa'),
('Sistemas', 'Administración de sistemas y desarrollo'),
('Auditoría', 'Auditoría y seguridad');

-- EMPLEADOS
INSERT INTO empleados (nombre, apellido, email, telefono, id_departamento, fecha_contratacion) VALUES
('María', 'González', 'mgonzalez@verificanet.local', '666111222', 1, '2022-01-15'),
('Juan', 'Martínez', 'jmartinez@verificanet.local', '666222333', 1, '2022-03-20'),
('Laura', 'Fernández', 'lfernandez@verificanet.local', '666555666', 2, '2022-06-15'),
('Sofía', 'Torres', 'storres@verificanet.local', '666777888', 2, '2023-01-10'),
('Ana', 'Rodríguez', 'arodriguez@verificanet.local', '666333444', 3, '2021-11-10'),
('Miguel', 'Navarro', 'mnavarro@verificanet.local', '666888999', 3, '2023-03-15'),
('Carlos', 'López', 'clopez@verificanet.local', '666444555', 4, '2023-02-01'),
('Admin', 'VerificaNet', 'adminvnet@verificanet.local', '666999000', 4, '2021-01-01'),
('Pedro', 'Sánchez', 'psanchez@verificanet.local', '666666777', 5, '2021-08-20');

-- CLIENTES
INSERT INTO clientes (nombre, apellido, email, telefono, tipo_cliente) VALUES
('Particular', 'Test', 'cliente1@example.com', '611222333', 'particular'),
(NULL, NULL, 'contacto@empresa.com', '622333444', 'empresa');
UPDATE clientes SET nombre_empresa = 'Empresa Test S.L.' WHERE email = 'contacto@empresa.com';

-- SERVICIOS
INSERT INTO servicios (nombre, descripcion, precio_base, tipo_servicio) VALUES
('Soporte Técnico Básico', 'Asistencia técnica remota 24/7', 299.99, 'soporte'),
('Mantenimiento Preventivo', 'Mantenimiento mensual de sistemas', 499.99, 'mantenimiento'),
('Desarrollo Web', 'Desarrollo de aplicaciones web a medida', 2999.99, 'desarrollo'),
('Consultoría IT', 'Consultoría en infraestructura y seguridad', 1499.99, 'consultoria'),
('Auditoría de Seguridad', 'Análisis completo de seguridad informática', 1999.99, 'consultoria'),
('Backup Cloud', 'Servicio de copias de seguridad en la nube', 199.99, 'soporte');

-- CONTRATOS
INSERT INTO contratos (id_cliente, id_servicio, fecha_inicio, estado) VALUES
(1, 1, '2024-01-01', 'activo'),
(1, 6, '2024-01-01', 'activo'),
(2, 2, '2024-02-01', 'activo'),
(2, 3, '2024-02-15', 'activo'),
(2, 5, '2024-03-01', 'activo');

-- INCIDENCIAS
INSERT INTO incidencias (titulo, descripcion, estado, prioridad, id_cliente, id_empleado_asignado) VALUES
('Error en conexión VPN', 'No se puede conectar a la VPN corporativa', 'en_proceso', 'alta', 2, 1),
('Lentitud en servidor', 'El servidor web responde muy lento', 'reportada', 'media', 2, 2),
('Backup fallido', 'El backup automático no se ejecutó anoche', 'resuelta', 'alta', 1, 1),
('Solicitud nueva funcionalidad', 'Añadir módulo de reportes en la aplicación', 'reportada', 'baja', 2, 7),
('Problema con correo', 'No recibo correos desde esta mañana', 'cerrada', 'critica', 1, 1),
('Actualización sistema', 'Necesito actualizar el sistema operativo', 'en_proceso', 'media', 2, 2);

-- USUARIOS con contraseñas hasheadas (hash de Verific@2024!)
INSERT INTO usuarios (username, password, nombre, email, rol, id_empleado) VALUES
('adminvnet', '$2y$10$Fmhe0Rf3zQIfTKpMNtGktO4m7RSydcggVxEFkAktULo9mnoZxVAr.', 'Administrador', 'admin@verificanet.com', 'admin', 8),
('mgonzalez', '$2y$10$Fmhe0Rf3zQIfTKpMNtGktO4m7RSydcggVxEFkAktULo9mnoZxVAr.', 'María González', 'mgonzalez@verificanet.com', 'empleado', 1),
('jmartinez', '$2y$10$Fmhe0Rf3zQIfTKpMNtGktO4m7RSydcggVxEFkAktULo9mnoZxVAr.', 'Juan Martínez', 'jmartinez@verificanet.com', 'empleado', 2),
('lfernandez', '$2y$10$Fmhe0Rf3zQIfTKpMNtGktO4m7RSydcggVxEFkAktULo9mnoZxVAr.', 'Laura Fernández', 'lfernandez@verificanet.com', 'empleado', 3),
('storres', '$2y$10$Fmhe0Rf3zQIfTKpMNtGktO4m7RSydcggVxEFkAktULo9mnoZxVAr.', 'Sofía Torres', 'storres@verificanet.com', 'empleado', 4),
('arodriguez', '$2y$10$Fmhe0Rf3zQIfTKpMNtGktO4m7RSydcggVxEFkAktULo9mnoZxVAr.', 'Ana Rodríguez', 'arodriguez@verificanet.com', 'empleado', 5),
('mnavarro', '$2y$10$Fmhe0Rf3zQIfTKpMNtGktO4m7RSydcggVxEFkAktULo9mnoZxVAr.', 'Miguel Navarro', 'mnavarro@verificanet.com', 'empleado', 6),
('clopez', '$2y$10$Fmhe0Rf3zQIfTKpMNtGktO4m7RSydcggVxEFkAktULo9mnoZxVAr.', 'Carlos López', 'clopez@verificanet.com', 'tecnico', 7),
('psanchez', '$2y$10$Fmhe0Rf3zQIfTKpMNtGktO4m7RSydcggVxEFkAktULo9mnoZxVAr.', 'Pedro Sánchez', 'psanchez@verificanet.com', 'empleado', 9);

INSERT INTO usuarios (username, password, nombre, email, rol, id_cliente) VALUES
('cliente1', '$2y$10$Fmhe0Rf3zQIfTKpMNtGktO4m7RSydcggVxEFkAktULo9mnoZxVAr.', 'Cliente Demo', 'cliente1@example.com', 'cliente', 1),
('empresa1', '$2y$10$Fmhe0Rf3zQIfTKpMNtGktO4m7RSydcggVxEFkAktULo9mnoZxVAr.', 'Empresa Demo', 'contacto@empresa.com', 'cliente', 2);

-- KB ARTÍCULOS
INSERT INTO kb_articulos (titulo, problema, solucion, keywords, veces_util) VALUES
('No puedo conectarme a la VPN', 'El cliente no puede conectarse a la VPN corporativa', 'Verificar credenciales y que el cliente VPN esté actualizado', 'vpn conexion red', 15),
('Correo no llega', 'No se reciben correos desde esta mañana', 'Revisar carpeta spam y configuración del servidor SMTP', 'correo email smtp', 10),
('Lentitud en el sistema', 'El sistema va muy lento', 'Limpiar caché del navegador y reiniciar el equipo', 'lento rendimiento velocidad', 8);

-- USUARIOS DE BASE DE DATOS
CREATE USER IF NOT EXISTS 'verificanet_user'@'192.168.50.41' IDENTIFIED BY 'Verific@2024!';
CREATE USER IF NOT EXISTS 'verificanet_user'@'192.168.50.42' IDENTIFIED BY 'Verific@2024!';
CREATE USER IF NOT EXISTS 'verificanet_user'@'192.168.50.30' IDENTIFIED BY 'Verific@2024!';
CREATE USER IF NOT EXISTS 'verificanet_user'@'192.168.50.10' IDENTIFIED BY 'Verific@2024!';
CREATE USER IF NOT EXISTS 'verificanet_user'@'192.168.60.50' IDENTIFIED BY 'Verific@2024!';
CREATE USER IF NOT EXISTS 'verificanet_user'@'localhost' IDENTIFIED BY 'Verific@2024!';

GRANT SELECT, INSERT, UPDATE, DELETE ON verificanet_servicios.* TO 'verificanet_user'@'192.168.50.41';
GRANT SELECT, INSERT, UPDATE, DELETE ON verificanet_servicios.* TO 'verificanet_user'@'192.168.50.42';
GRANT SELECT, INSERT, UPDATE, DELETE ON verificanet_servicios.* TO 'verificanet_user'@'192.168.50.30';
GRANT SELECT, INSERT, UPDATE, DELETE ON verificanet_servicios.* TO 'verificanet_user'@'192.168.50.10';
GRANT SELECT, INSERT, UPDATE, DELETE ON verificanet_servicios.* TO 'verificanet_user'@'192.168.60.50';
GRANT SELECT, INSERT, UPDATE, DELETE ON verificanet_servicios.* TO 'verificanet_user'@'localhost';

FLUSH PRIVILEGES;
EOSQL

echo ""
echo "=========================================="
echo " BASE DE DATOS CONFIGURADA"
echo " Contraseña de todos los usuarios: password"
echo "=========================================="