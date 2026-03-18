#!/bin/bash
export DEBIAN_FRONTEND=noninteractive

echo "=========================================="
echo "CONFIGURANDO WEB FRONTEND (BALANCEADOR)"
echo "=========================================="

apt-get update
apt-get install -y nginx

# Crear configuración del balanceador
cat > /etc/nginx/sites-available/default << 'EOF'
upstream backends {
    server 192.168.50.41;
    server 192.168.50.42;
}

server {
    listen 80;
    server_name _;

    access_log /var/log/nginx/balanceador_access.log;
    error_log  /var/log/nginx/balanceador_error.log;

    location / {
        proxy_pass http://backends;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    }

    # Health check del balanceador
    location /health {
        return 200 "OK - Balanceador activo\n";
        add_header Content-Type text/plain;
    }
}
EOF

# Página HTML de prueba
cat > /var/www/html/index.html << 'EOFHTML'
<!DOCTYPE html>
<html>
<head>
    <title>Balanceador Verificanet</title>
</head>
<body>
    <h1>Balanceador funcionando correctamente</h1>
    <p>Este servidor distribuye tráfico entre:</p>
    <ul>
        <li>Backend1 → 192.168.50.41</li>
        <li>Backend2 → 192.168.50.42</li>
    </ul>
    <p>Pruebas:</p>
    <ul>
        <li><a href="/health">Health Check del Balanceador</a></li>
        <li><a href="/api.php?action=health">Health Check de Backends (vía balanceador)</a></li>
    </ul>
</body>
</html>
EOFHTML

# Permisos
chown -R www-data:www-data /var/www/html
chmod -R 755 /var/www/html

# Reiniciar Nginx
systemctl enable nginx
systemctl restart nginx

echo ""
echo "=========================================="
echo " BALANCEADOR NGINX CONFIGURADO"
echo " IP: 192.168.50.30"
echo " Health Check: http://192.168.50.30/health"
echo "=========================================="