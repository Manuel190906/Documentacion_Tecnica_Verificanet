# VERIFICANET WEB - SISTEMA COMPLETO

Sistema de gestión de incidencias IT con diseño profesional en azules claros.

## ESTRUCTURA DE ARCHIVOS

```
verificanet_web/
├── index.php              # Página pública principal
├── login.php              # Página de login
├── empleado.php           # Dashboard empleado
├── tecnico.php            # Dashboard técnico (por implementar)
├── admin.php              # Dashboard admin (por implementar)
├── servicios.php          # Catálogo y servicios (por implementar)
├── css/
│   ├── public.css         # Estilos página pública
│   ├── dashboard.css      # Estilos dashboards
│   └── forms.css          # Estilos formularios y login
├── includes/
│   └── config.php         # Configuración y funciones
├── procesar/
│   ├── login.php          # Procesa login
│   └── logout.php         # Cierra sesión
└── img/                   # Carpeta para imágenes/logo

```

## INSTALACIÓN

### 1. Copiar archivos
Copiar toda la carpeta `verificanet_web` a `/var/www/html/` en tu servidor web.

### 2. Configurar base de datos
Editar `includes/config.php` si tus credenciales de BD son diferentes:

```php
define('DB_HOST', '192.168.60.50');
define('DB_NAME', 'verificanet_servicios');
define('DB_USER', 'verificanet_user');
define('DB_PASS', 'Verific@2024!');
```

### 3. Estructura de base de datos necesaria

```sql
-- Tabla usuarios (si no existe)
CREATE TABLE IF NOT EXISTS usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    rol ENUM('cliente', 'empleado', 'tecnico', 'admin') NOT NULL,
    email VARCHAR(100),
    activo BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla incidencias (ya debería existir)
-- Asegúrate de que tiene estos campos
ALTER TABLE incidencias 
ADD COLUMN IF NOT EXISTS id_empleado INT,
ADD FOREIGN KEY (id_empleado) REFERENCES usuarios(id_usuario);
```

### 4. Crear usuarios de prueba

```sql
-- IMPORTANTE: Las contraseñas deben estar hasheadas con password_hash()
-- Estas son contraseñas de ejemplo: Verific@2024!

INSERT INTO usuarios (usuario, password, nombre, rol) VALUES
('adminvnet', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador', 'admin'),
('mgonzalez', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'María González', 'empleado'),
('clopez', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Carlos López', 'tecnico'),
('cliente1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Cliente Demo', 'cliente');
```

### 5. Script PHP para generar contraseñas hasheadas

Si necesitas generar tus propias contraseñas, usa este script:

```php
<?php
// Guardar como generar_password.php
$password = "Verific@2024!";
$hash = password_hash($password, PASSWORD_DEFAULT);
echo "Password: $password<br>";
echo "Hash: $hash<br>";
?>
```

## ACCESO AL SISTEMA

### Página pública
http://tu-servidor/verificanet_web/index.php

### Login
http://tu-servidor/verificanet_web/login.php

### Usuarios de prueba (contraseña para todos: Verific@2024!)
- **Admin:** adminvnet
- **Empleado:** mgonzalez
- **Técnico:** clopez
- **Cliente:** cliente1

## COLORES DEL DISEÑO

- **Azul oscuro:** #1e40af (header, títulos principales)
- **Azul medio:** #3b82f6 (botones, enlaces)
- **Azul claro:** #60a5fa (hover, secundario)
- **Azul muy claro:** #eff6ff, #dbeafe (fondos, tarjetas)
- **Azul texto:** #1e3a8a, #2563eb (textos)

## CARACTERÍSTICAS

### Página Pública (index.php)
- Hero section con llamada a la acción
- Grid de 6 servicios con precios
- Sección "Por qué elegirnos" con 4 características
- Estadísticas (500+ empresas, 15min respuesta, 99.9% disponibilidad)
- Footer con 3 columnas de información
- Diseño responsive

### Sistema de Login
- Autenticación con base de datos
- Redirección automática según rol
- Mensajes de error
- Sesiones seguras

### Dashboard Empleado
- Estadísticas personales (pendientes, resueltas, total)
- Listado de sus incidencias
- Formulario para crear nueva incidencia
- Badges de estado y prioridad coloridos
- Tabla responsive

## PENDIENTE DE IMPLEMENTAR

Los siguientes archivos están referenciados pero necesitan ser creados:

1. **tecnico.php** - Dashboard para técnicos
2. **admin.php** - Dashboard para administradores
3. **servicios.php** - Catálogo y gestión de servicios para clientes
4. **ver_incidencia.php** - Vista detalle de una incidencia
5. **procesar/crear_incidencia.php** - Procesa creación de incidencias

## PERSONALIZACIÓN

### Cambiar logo
1. Agregar tu logo en `img/logo.png`
2. En los archivos PHP, reemplazar el texto "VERIFICANET" por:
```html
<img src="img/logo.png" alt="Verificanet" height="40">
```

### Cambiar colores
Editar los archivos CSS y buscar los valores hexadecimales:
- `#1e40af` - Azul oscuro principal
- `#3b82f6` - Azul medio
- `#dbeafe` - Azul claro

### Agregar más servicios
Editar `index.php` y duplicar el bloque `.servicio-card` con la nueva información.

## SOPORTE

Para cualquier duda o problema:
- Email: info@verificanet.com
- Tel: +34 900 123 456

## CRÉDITOS

Desarrollado para el proyecto ASIR
© 2026 Verificanet - Todos los derechos reservados
