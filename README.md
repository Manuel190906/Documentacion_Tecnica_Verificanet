# Verificanet - Proyecto Intermodular ASIR

Sistema de gestión de servicios IT con infraestructura virtualizada en DMZ.

##  Documentación

La **documentación técnica completa** se encuentra en el archivo PDF del repositorio.

##  Descripción

Proyecto intermodular que implementa:

- **Infraestructura virtualizada** con Vagrant + VirtualBox
- **DMZ con dos subredes** (pública e interna)
- **Servidores Linux:** Firewall, Web (Nginx), Backend (PHP), Base de datos (MariaDB)
- **Servidor Windows:** Active Directory, DHCP, DNS
- **Aplicación web** para gestión de incidencias
- **Sistema de incidencias** en Python para empleados y técnicos

##  Instalación

```bash
vagrant up
```

Acceso web: http://localhost:8080

##  Usuarios de prueba

- **Admin:** adminvnet / Verific@2024!
- **Empleado:** mgonzalez / Verific@2024!
- **Cliente:** cliente1 / cliente123

##  Autor

Manuel Ramírez Rodríguez - ASIR
Curso -> CFGS Administración de Sistemas en Red
Año -> 2025/2026
