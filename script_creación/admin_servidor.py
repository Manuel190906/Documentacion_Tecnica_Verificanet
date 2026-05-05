#!/usr/bin/env python3
# admin_servidor.py - Version consola para llamar desde PHP via Flask
import sys
import subprocess

# Configuracion SSH
USUARIO  = "vagrant"
SSH_PATH = r"C:\Windows\System32\OpenSSH\ssh.exe"
CLAVE    = r"C:\Users\Administrador\.ssh\id_rsa"

# Servidores disponibles
SERVIDORES = {
    "web":      "192.168.50.30",
    "backend1": "192.168.50.41",
    "backend2": "192.168.50.42",
    "database": "192.168.60.50",
    "firewall": "192.168.50.20",
}

def ejecutar_ssh(servidor_ip, comando):
    args = [
        SSH_PATH,
        "-n",
        "-i", CLAVE,
        "-o", "StrictHostKeyChecking=no",
        "-o", "UserKnownHostsFile=C:\\Users\\Administrador\\.ssh\\known_hosts",
        "-o", "ConnectTimeout=5",
        "-o", "BatchMode=yes",
        f"{USUARIO}@{servidor_ip}",
        comando
    ]
    resultado = subprocess.run(
        args,
        capture_output=True,
        timeout=15,
        stdin=subprocess.DEVNULL
    )
    # Decodificamos con utf-8 y reemplazamos caracteres que no se puedan mostrar
    salida = resultado.stdout.decode('utf-8', errors='replace')
    salida += resultado.stderr.decode('utf-8', errors='replace')
    return salida.strip() if salida.strip() else "Sin salida"

def main():
    if len(sys.argv) < 3:
        sys.stdout.buffer.write(b"ERROR: Uso: python admin_servidor.py <accion> <servidor>\n")
        sys.exit(1)

    accion   = sys.argv[1]
    servidor = sys.argv[2]

    if servidor not in SERVIDORES:
        sys.stdout.buffer.write(f"ERROR: Servidor '{servidor}' no reconocido.\n".encode('utf-8'))
        sys.exit(1)

    ip = SERVIDORES[servidor]

    comandos = {
        "estado_nginx":      "systemctl --no-pager status nginx 2>&1",
        "estado_apache":     "systemctl --no-pager status apache2 2>&1",
        "estado_mariadb":    "systemctl --no-pager status mariadb 2>&1",
        "syslog":            "sudo tail -n 20 /var/log/syslog 2>&1",
        "reiniciar_nginx":   "sudo systemctl restart nginx && echo Nginx reiniciado",
        "reiniciar_apache":  "sudo systemctl restart apache2 && echo Apache reiniciado",
        "reiniciar_mariadb": "sudo systemctl restart mariadb && echo MariaDB reiniciado",
        # Consultas a MariaDB
        "usuarios_bd":       "mysql -h 127.0.0.1 -u verificanet_user -pVerific@2024! verificanet_servicios -e 'SELECT username, rol, activo FROM usuarios;' 2>&1",
        "incidencias_bd":    "mysql -h 127.0.0.1 -u verificanet_user -pVerific@2024! verificanet_servicios -e 'SELECT id_incidencia, titulo, estado FROM incidencias ORDER BY fecha_creacion DESC LIMIT 10;' 2>&1",
        "tablas_bd":         "mysql -h 127.0.0.1 -u verificanet_user -pVerific@2024! verificanet_servicios -e 'SHOW TABLES;' 2>&1",
    }

    if accion not in comandos:
        sys.stdout.buffer.write(f"ERROR: Accion '{accion}' no reconocida.\n".encode('utf-8'))
        sys.exit(1)

    salida = ejecutar_ssh(ip, comandos[accion])

    # Escribimos en bytes para evitar problemas de encoding en Windows
    sys.stdout.buffer.write(salida.encode('utf-8', errors='replace'))

if __name__ == '__main__':
    main()
