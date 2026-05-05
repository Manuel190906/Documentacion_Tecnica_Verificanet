#!/usr/bin/env python3
# crear_incidencia.py
# Llamado desde crear_incidencia.php con los datos como argumentos
# Uso: python3 crear_incidencia.py <id_cliente> <prioridad> <titulo> <descripcion> <id_empleado>

import sys
import pymysql

# Configuracion base de datos
DB_HOST = '192.168.60.50'
DB_USER = 'verificanet_user'
DB_PASS = 'Verific@2024!'
DB_NAME = 'verificanet_servicios'

def crear_incidencia(id_cliente, prioridad, titulo, descripcion, id_empleado):
    try:
        conn = pymysql.connect(
            host=DB_HOST,
            user=DB_USER,
            password=DB_PASS,
            database=DB_NAME,
            charset='utf8mb4'
        )
        cursor = conn.cursor()

        sql = """
            INSERT INTO incidencias 
            (titulo, descripcion, prioridad, id_cliente, id_empleado_asignado, estado, fecha_creacion) 
            VALUES (%s, %s, %s, %s, %s, 'reportada', NOW())
        """
        cursor.execute(sql, (titulo, descripcion, prioridad, id_cliente, id_empleado))
        conn.commit()

        id_nuevo = cursor.lastrowid
        cursor.close()
        conn.close()

        print("OK:" + str(id_nuevo))

    except Exception as e:
        print(f"ERROR:{str(e)}", file=sys.stderr)
        sys.exit(1)

if __name__ == '__main__':
    if len(sys.argv) != 6:
        print("ERROR:Argumentos incorrectos. Uso: crear_incidencia.py <id_cliente> <prioridad> <titulo> <descripcion> <id_empleado>")
        sys.exit(1)

    id_cliente  = sys.argv[1]
    prioridad   = sys.argv[2]
    titulo      = sys.argv[3]
    descripcion = sys.argv[4]
    id_empleado = sys.argv[5]

    crear_incidencia(sys.argv[1], sys.argv[2], sys.argv[3], sys.argv[4], sys.argv[5])