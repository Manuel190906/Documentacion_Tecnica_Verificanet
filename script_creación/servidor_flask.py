#!/usr/bin/env python3
# servidor_flask.py - Servidor HTTP que ejecuta admin_servidor.py
# Ejecutar en Windows Server: python servidor_flask.py
# Escucha en http://192.168.50.10:5000

from flask import Flask, request, jsonify
import subprocess
import sys
import os

app = Flask(__name__)

# Ruta al script de consulta
PYTHON  = r"C:\Program Files\Python312\python.exe"
SCRIPT  = r"C:\Scripts\admin_servidor.py"

# Acciones permitidas
ACCIONES_PERMITIDAS = [
    "estado_nginx",
    "estado_apache",
    "estado_mariadb",
    "syslog",
    "reiniciar_nginx",
    "reiniciar_apache",
    "reiniciar_mariadb",
    "usuarios_bd",
    "incidencias_bd",
    "tablas_bd",
]

# Servidores permitidos
SERVIDORES_PERMITIDOS = ["web", "backend1", "backend2", "database", "firewall"]


@app.route('/estado', methods=['GET'])
def estado():
    accion   = request.args.get('accion', '')
    servidor = request.args.get('servidor', '')

    if not accion or not servidor:
        return jsonify({"error": "Faltan parametros: accion y servidor"}), 400

    if accion not in ACCIONES_PERMITIDAS:
        return jsonify({"error": f"Accion no permitida: {accion}"}), 400

    if servidor not in SERVIDORES_PERMITIDOS:
        return jsonify({"error": f"Servidor no permitido: {servidor}"}), 400

    try:
        resultado = subprocess.run(
            [PYTHON, SCRIPT, accion, servidor],
            capture_output=True,
            timeout=20,
            stdin=subprocess.DEVNULL
        )
        salida = resultado.stdout.decode('utf-8', errors='replace') + resultado.stderr.decode('utf-8', errors='replace')
        return jsonify({"resultado": salida.strip() if salida.strip() else "Sin respuesta"})

    except subprocess.TimeoutExpired:
        return jsonify({"error": "Timeout al consultar el servidor"}), 504
    except Exception as e:
        return jsonify({"error": str(e)}), 500


@app.route('/ping', methods=['GET'])
def ping():
    return jsonify({"status": "ok", "mensaje": "Servidor Flask activo"})


if __name__ == '__main__':
    print("Iniciando servidor Flask en http://0.0.0.0:5000")
    app.run(host='0.0.0.0', port=5000, debug=False)
