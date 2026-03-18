#!/bin/bash
export DEBIAN_FRONTEND=noninteractive

echo "======================================"
echo "CONFIGURANDO FIREWALL CON DMZ + RED INTERNA"
echo "======================================"

apt-get update
apt-get install -y iptables iptables-persistent

# Habilitar IP Forwarding
sysctl -w net.ipv4.ip_forward=1
echo "net.ipv4.ip_forward=1" >> /etc/sysctl.conf

# Limpiar reglas
iptables -F
iptables -t nat -F
iptables -X

# ============================
# NAT (salida a Internet)
# ============================
# NAT por enp0s3 (NAT de Vagrant)
iptables -t nat -A POSTROUTING -o enp0s3 -j MASQUERADE

# ============================
# POLÍTICAS POR DEFECTO
# ============================
iptables -P INPUT DROP
iptables -P FORWARD DROP
iptables -P OUTPUT ACCEPT

# ============================
# REGLAS BÁSICAS
# ============================
iptables -A INPUT -i lo -j ACCEPT
iptables -A INPUT -m state --state ESTABLISHED,RELATED -j ACCEPT
iptables -A FORWARD -m state --state ESTABLISHED,RELATED -j ACCEPT

# SSH desde DMZ e Interna
iptables -A INPUT -p tcp --dport 22 -s 192.168.50.0/24 -j ACCEPT
iptables -A INPUT -p tcp --dport 22 -s 192.168.60.0/24 -j ACCEPT
iptables -A INPUT -p tcp --dport 22 -i enp0s3 -j ACCEPT

# ICMP
iptables -A INPUT -p icmp -j ACCEPT

# ============================
# DMZ (192.168.50.0/24)
# ============================
# Web puede recibir tráfico desde Internet (NAT)
iptables -A FORWARD -d 192.168.50.30 -j ACCEPT

# Web → Backends (HTTP)
iptables -A FORWARD -s 192.168.50.30 -d 192.168.50.41 -p tcp --dport 80 -j ACCEPT
iptables -A FORWARD -s 192.168.50.30 -d 192.168.50.42 -p tcp --dport 80 -j ACCEPT

# ============================
# RED INTERNA (192.168.60.0/24)
# ============================
# Backends → Base de datos
iptables -A FORWARD -s 192.168.60.41 -d 192.168.60.50 -p tcp --dport 3306 -j ACCEPT
iptables -A FORWARD -s 192.168.60.42 -d 192.168.60.50 -p tcp --dport 3306 -j ACCEPT

# Respuestas
iptables -A FORWARD -s 192.168.60.50 -j ACCEPT

# ============================
# RUTEO ENTRE DMZ ↔ INTERNA
# ============================
iptables -A FORWARD -i enp0s8 -o enp0s9 -j ACCEPT
iptables -A FORWARD -i enp0s9 -o enp0s8 -j ACCEPT

# ============================
# LOGGING
# ============================
iptables -A FORWARD -j LOG --log-prefix "FW-BLOCKED: " --log-level 4
iptables -A INPUT -j LOG --log-prefix "FW-INPUT-BLOCKED: " --log-level 4

# Guardar reglas
netfilter-persistent save

echo "Firewall configurado correctamente."