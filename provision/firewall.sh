#!/bin/bash
export DEBIAN_FRONTEND=noninteractive

mkdir -p /etc/iptables

apt-get update
apt-get install -y iptables iptables-persistent || true

# Habilitar Forwarding permanente
sysctl -w net.ipv4.ip_forward=1
sed -i '/net.ipv4.ip_forward/d' /etc/sysctl.conf
echo "net.ipv4.ip_forward=1" >> /etc/sysctl.conf

# Limpieza total
iptables -F
iptables -X
iptables -t nat -F

# POLÍTICAS POR DEFECTO
iptables -P INPUT DROP
iptables -P FORWARD DROP
iptables -P OUTPUT ACCEPT

# Conexiones ya establecidas
iptables -A FORWARD -m state --state ESTABLISHED,RELATED -j ACCEPT
iptables -A INPUT -m state --state ESTABLISHED,RELATED -j ACCEPT

# SSH
iptables -A INPUT -p tcp --dport 22 -j ACCEPT

# Tráfico entre DMZ y RED INTERNA (bidireccional)
iptables -A FORWARD -i enp0s8 -o enp0s9 -j ACCEPT
iptables -A FORWARD -i enp0s9 -o enp0s8 -j ACCEPT

# Balanceador -> Backends (puerto 80)
iptables -A FORWARD -s 192.168.50.30 -d 192.168.50.41 -p tcp --dport 80 -j ACCEPT
iptables -A FORWARD -s 192.168.50.30 -d 192.168.50.42 -p tcp --dport 80 -j ACCEPT

# Backends -> BD (puerto 3306)
iptables -A FORWARD -s 192.168.50.41 -d 192.168.60.50 -p tcp --dport 3306 -j ACCEPT
iptables -A FORWARD -s 192.168.50.42 -d 192.168.60.50 -p tcp --dport 3306 -j ACCEPT

# Windows Server -> BD
iptables -A FORWARD -s 192.168.50.10 -d 192.168.60.50 -p tcp --dport 3306 -j ACCEPT

# NAT salida a Internet
iptables -t nat -A POSTROUTING -o enp0s3 -j MASQUERADE
iptables -A FORWARD -s 192.168.50.0/24 -i enp0s8 -o enp0s3 -j ACCEPT
iptables -A FORWARD -s 192.168.60.0/24 -i enp0s9 -o enp0s3 -j ACCEPT

echo ">>> Guardando reglas de firewall..."
iptables-save > /etc/iptables/rules.v4