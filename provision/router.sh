#!/bin/bash
export DEBIAN_FRONTEND=noninteractive

echo "======================================"
echo "CONFIGURANDO..."
echo "======================================"

apt-get update
apt-get install -y iptables iptables-persistent

# ESTA ES LA LÍNEA QUE FALTA:
mkdir -p /etc/iptables

sysctl -w net.ipv4.ip_forward=1

iptables -F
iptables -t nat -F
iptables -t nat -A POSTROUTING -o enp0s3 -j MASQUERADE

# El router permite el tráfico general, el Firewall es quien filtra.
iptables -P FORWARD ACCEPT
# Guardar reglas
iptables-save > /etc/iptables/rules.v4


# Al final, cuando hagas el volcado, ya no fallará:
iptables-save > /etc/iptables/rules.v4