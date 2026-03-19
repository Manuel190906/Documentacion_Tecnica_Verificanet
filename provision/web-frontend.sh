#!/bin/bash
export DEBIAN_FRONTEND=noninteractive

echo "=========================================="
echo "CONFIGURANDO WEB FRONTEND (NGINX)"
echo "=========================================="

apt-get update
apt-get install -y nginx

# Configuración CORRECTA de Nginx
cat > /etc/nginx/sites-available/default << 'EOF'
upstream backends {
    server 192.168.50.41;
    server 192.168.50.42;
}

server {
    listen 80;
    server_name _;
    root /var/www/html;
    index index.html;

    access_log /var/log/nginx/verificanet_access.log;
    error_log  /var/log/nginx/verificanet_error.log;

    # Servir archivos estáticos (HTML, CSS, JS)
    location / {
        try_files $uri $uri/ /index.html;
    }

    # Solo API va a backends
    location /api.php {
        proxy_pass http://backends;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    }

    # Health check
    location /health {
        return 200 "OK - Verificanet Web\n";
        add_header Content-Type text/plain;
    }
}
EOF

# Instalar web profesional si existe el archivo
if [ -f /vagrant/verificanet_PRO.tar.gz ]; then
    echo "Instalando web profesional..."
    cd /vagrant
    tar -xzf verificanet_PRO.tar.gz
    rm -rf /var/www/html/*
    cp -r verificanet_pro/* /var/www/html/
    chown -R www-data:www-data /var/www/html
    chmod -R 755 /var/www/html
else
    echo "Archivo verificanet_PRO.tar.gz no encontrado, instalando página de prueba..."
    cat > /var/www/html/index.html << 'EOFHTML'
<!DOCTYPE html>
<html>
<head>
    <title>Verificanet</title>
</head>
<body>
    <h1>Verificanet - Instalación pendiente</h1>
    <p>Coloca verificanet_PRO.tar.gz en la carpeta del proyecto y ejecuta:</p>
    <pre>vagrant provision web</pre>
</body>
</html>
EOFHTML
fi

# Permisos
chown -R www-data:www-data /var/www/html
chmod -R 755 /var/www/html

# Reiniciar Nginx
systemctl enable nginx
systemctl restart nginx

echo ""
echo "=========================================="
echo " WEB FRONTEND CONFIGURADO"
echo " IP: 192.168.50.30"
echo " Acceso: http://localhost:8080"
echo "=========================================="