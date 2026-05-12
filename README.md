# Verificanet - Proyecto Intermodular ASIR

Sistema de gestión de servicios IT con infraestructura virtualizada en DMZ.

vagrant ssh backend1
sudo tee /vagrant/README.md << 'EOF'
# Verificanet - Sistema de Gestión de Incidencias

**Proyecto Intermodular ASIR 2025/2026**

Sistema de gestión de servicios IT con infraestructura virtualizada y segmentación de redes (DMZ + Red Interna).

---

##  Instalación Rápida

```bash
git clone https://github.com/Manuel190906/Documentacion_Tecnica_Verificanet.git
cd Documentacion_Tecnica_Verificanet
vagrant up
```

**Acceso web:** http://localhost:8080  
**Tiempo:** ~15 minutos en el primer arranque

---

##  Usuarios de Prueba

| Usuario | Contraseña | Rol |
|---------|-----------|-----|
| `adminvnet` | `Verific@2024!` | Administrador |
| `mgonzalez` | `Verific@2024!` | Empleado Soporte |
| `cliente1` | `Verific@2024!` | Cliente |

---

##  Arquitectura

**6 máquinas virtuales:**
- **Router** (.50.1/.60.1) - Gateway y NAT
- **Firewall** (.50.20/.60.20) - iptables entre redes
- **Web** (.50.30) - Nginx balanceador
- **Backend 1/2** (.50.41/.42) - Apache + PHP 8.1
- **Database** (.60.50) - MariaDB en red interna
- **Windows Server** (.50.10) - AD, DNS, DHCP

**2 redes segmentadas:**
- DMZ (192.168.50.0/24) - Servicios públicos
- Red Interna (192.168.60.0/24) - Base de datos aislada

---

##  Funcionalidades

 Sistema de gestión de incidencias con 3 niveles de prioridad  
 Base de conocimiento con manuales técnicos  
 Roles: Admin, Empleado, Técnico, Cliente  
 Balanceo de carga con sesiones persistentes  
 Firewall con políticas restrictivas  
 Integración con Active Directory  

---

##  Documentación

La documentación técnica completa está en el archivo **`Documentacion_Verificanet.pdf`** del repositorio.

**Incluye:**
- Diagramas de red detallados
- Configuración de todos los servicios
- Esquema de base de datos
- Manual de administración

---

##  Stack Tecnológico

- **Virtualización:** Vagrant 2.4 + VirtualBox 7.0
- **SO:** Ubuntu Server 22.04 LTS + Windows Server 2019
- **Web:** Nginx 1.18 + Apache 2.4 + PHP 8.1
- **BD:** MariaDB 10.6
- **Firewall:** iptables + netfilter-persistent

---

##  Comandos Útiles

```bash
# Levantar/parar infraestructura
vagrant up
vagrant halt

# Acceder a una VM
vagrant ssh firewall ... 

# Ver estado
vagrant status

# Reiniciar todo
vagrant destroy -f && vagrant up
```

---

##  Autor

**Manuel Ramírez Rodríguez**  
CFGS Administración de Sistemas Informáticos en Red  
IES Albarregas - 2025/2026

---

**Repositorio:** https://github.com/Manuel190906/Documentacion_Tecnica_Verificanet

