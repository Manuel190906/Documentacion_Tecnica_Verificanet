#!/bin/bash
export DEBIAN_FRONTEND=noninteractive
echo "======================================"
echo "CONFIGURANDO WEB (BALANCEADOR NGINX)"
echo "======================================"

apt-get update
apt-get install -y nginx

cat > /etc/nginx/sites-available/default << 'EOF'
upstream backends {
    ip_hash;
    server 192.168.50.41;
    server 192.168.50.42;
}

server {
    listen 80;
    server_name _;

    access_log /var/log/nginx/access.log;
    error_log /var/log/nginx/error.log;

    location / {
        proxy_pass http://backends;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_connect_timeout 60s;
        proxy_send_timeout 60s;
        proxy_read_timeout 60s;
    }

    location /health {
        return 200 "OK - Balanceador Web Activo\n";
        add_header Content-Type text/plain;
    }
}
EOF

systemctl enable nginx
systemctl restart nginx

echo "Balanceador configurado con ip_hash en puerto 80."