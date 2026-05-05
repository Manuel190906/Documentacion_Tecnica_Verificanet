#!/bin/bash
echo ">>> Configurando rutas en RED INTERNA..."

ip route del 192.168.50.0/24 2>/dev/null

# Gateway es el FIREWALL (192.168.60.20)
ip route add 192.168.50.0/24 via 192.168.60.20

cat > /etc/netplan/99-custom-routes.yaml <<EOF
network:
  version: 2
  ethernets:
    enp0s8:
      routes:
        - to: 192.168.50.0/24
          via: 192.168.60.20
EOF
netplan apply
echo ">>> Ruta hacia DMZ configurada via 192.168.60.20"