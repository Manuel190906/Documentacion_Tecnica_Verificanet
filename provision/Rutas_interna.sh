#!/bin/bash
# CONFIGURAR RUTAS - RED INTERNA
# Este script hace que la VM use el firewall como gateway en lugar del NAT

echo "=========================================="
echo "CONFIGURANDO RUTAS A TRAVÉS DEL FIREWALL"
echo "=========================================="

# Borrar la ruta por defecto del NAT de Vagrant
ip route del default via 10.0.2.2 2>/dev/null

# Añadir ruta por defecto al Firewall (RED INTERNA)
ip route add default via 192.168.60.1

# Hacer permanente
cat > /etc/netplan/99-custom-routes.yaml <<EOF
network:
  version: 2
  ethernets:
    enp0s9:
      routes:
        - to: 0.0.0.0/0
          via: 192.168.60.1
          metric: 100
EOF

netplan apply

echo " Ruta configurada: Todo el tráfico va por el Firewall"
ip route show