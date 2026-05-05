# SISTEMA UNIFICADO DE INCIDENCIAS VERIFICANET
# Login según rol: Empleado, Técnico o Jefe Técnico

import datetime

ARCHIVO = 'incidencias.txt'

# USUARIOS DEL SISTEMA (usuario: contraseña, rol)
USUARIOS = {
    'mgonzalez': ('Verific@2024!', 'empleado'),
    'jmartinez': ('Verific@2024!', 'empleado'),
    'arodriguez': ('Verific@2024!', 'empleado'),
    'clopez': ('Verific@2024!', 'tecnico'),
    'adminvnet': ('Verific@2024!', 'jefe'),
}

# ========================================
# FUNCIONES COMUNES
# ========================================

def cargar():
    lista = []
    try:
        f = open(ARCHIVO, 'r', encoding='utf-8')
        lineas = f.readlines()
        f.close()

        i = 0
        while i < len(lineas):
            id_num = int(lineas[i].strip())
            titulo = lineas[i+1].strip()
            cliente = lineas[i+2].strip()
            desc = lineas[i+3].strip()
            empleado = lineas[i+4].strip()
            estado = lineas[i+5].strip()
            fecha = lineas[i+6].strip()
            notas = lineas[i+7].strip()

            lista.append([id_num, titulo, cliente, desc, empleado, estado, fecha, notas])
            i = i + 8
    except:
        pass
    return lista

def guardar(lista):
    f = open(ARCHIVO, 'w', encoding='utf-8')
    i = 0
    while i < len(lista):
        inc = lista[i]
        f.write(str(inc[0]) + '\n')
        f.write(inc[1] + '\n')
        f.write(inc[2] + '\n')
        f.write(inc[3] + '\n')
        f.write(inc[4] + '\n')
        f.write(inc[5] + '\n')
        f.write(inc[6] + '\n')
        f.write(inc[7] + '\n')
        i = i + 1
    f.close()

# ========================================
# INTERFAZ EMPLEADO
# ========================================

def menu_empleado(nombre):
    while True:
        print("\n========== EMPLEADO ==========")
        print("1. Crear incidencia")
        print("2. Ver mis pendientes")
        print("3. Ver mis resueltas")
        print("4. Salir")
        print("==============================")
        
        op = input("> ")
        datos = cargar()

        if op == '1':
            print("\n--- CREAR INCIDENCIA ---")
            
            if len(datos) == 0:
                id_nuevo = 1
            else:
                mayor = 0
                i = 0
                while i < len(datos):
                    if datos[i][0] > mayor:
                        mayor = datos[i][0]
                    i = i + 1
                id_nuevo = mayor + 1

            titulo = input("Titulo: ")
            cliente = input("Cliente: ")
            desc = input("Descripcion: ")
            fecha = datetime.datetime.now().strftime("%d/%m/%Y %H:%M")

            nueva = [id_nuevo, titulo, cliente, desc, nombre, 'Pendiente', fecha, '']
            datos.append(nueva)
            guardar(datos)

            print("✓ Creada incidencia #" + str(id_nuevo))

        elif op == '2':
            print("\n--- MIS INCIDENCIAS PENDIENTES ---")
            hay = False
            i = 0
            while i < len(datos):
                if datos[i][4] == nombre and datos[i][5] != 'Resuelto':
                    hay = True
                    print("#" + str(datos[i][0]) + " [" + datos[i][5] + "] " + datos[i][1] + " - " + datos[i][2])
                i = i + 1

            if not hay:
                print("No tienes incidencias pendientes")

        elif op == '3':
            print("\n--- MIS INCIDENCIAS RESUELTAS ---")
            hay = False
            i = 0
            while i < len(datos):
                if datos[i][4] == nombre and datos[i][5] == 'Resuelto':
                    hay = True
                    print("#" + str(datos[i][0]) + " " + datos[i][1] + " - " + datos[i][2])
                    if datos[i][7] != '':
                        print("  Nota: " + datos[i][7])
                i = i + 1

            if not hay:
                print("No tienes incidencias resueltas")

        elif op == '4':
            break
        else:
            print("Opción no válida")

# ========================================
# INTERFAZ TÉCNICO
# ========================================

def menu_tecnico(nombre):
    while True:
        print("\n========== TÉCNICO ==========")
        print("1. Ver todas las incidencias")
        print("2. Ver pendientes")
        print("3. Cambiar estado")
        print("4. Salir")
        print("=============================")
        
        op = input("> ")
        datos = cargar()

        if op == '1':
            print("\n--- TODAS LAS INCIDENCIAS ---")
            if len(datos) == 0:
                print("No hay incidencias")
            else:
                i = 0
                while i < len(datos):
                    inc = datos[i]
                    print("#" + str(inc[0]) + " [" + inc[5] + "] " + inc[1] + " - " + inc[2] + " (" + inc[4] + ")")
                    if inc[7] != '':
                        print("  Nota: " + inc[7])
                    i = i + 1

        elif op == '2':
            print("\n--- PENDIENTES ---")
            hay = False
            i = 0
            while i < len(datos):
                if datos[i][5] != 'Resuelto':
                    hay = True
                    print("#" + str(datos[i][0]) + " [" + datos[i][5] + "] " + datos[i][1] + " - " + datos[i][2])
                i = i + 1

            if not hay:
                print("No hay pendientes")

        elif op == '3':
            print("\n--- CAMBIAR ESTADO ---")
            
            try:
                id_buscar = int(input("ID: "))
            except:
                print("ERROR: Debes escribir un número")
                continue

            pos = -1
            i = 0
            while i < len(datos):
                if datos[i][0] == id_buscar:
                    pos = i
                    break
                i = i + 1

            if pos == -1:
                print("No encontrada")
            else:
                print("Estado actual: " + datos[pos][5])
                print("\n1. Pendiente  2. En progreso  3. En mantenimiento  4. Resuelto  5. Cerrado")
                
                num = input("Nuevo estado (1-5): ")
                
                if num == '1':
                    datos[pos][5] = 'Pendiente'
                elif num == '2':
                    datos[pos][5] = 'En progreso'
                elif num == '3':
                    datos[pos][5] = 'En mantenimiento'
                elif num == '4':
                    datos[pos][5] = 'Resuelto'
                elif num == '5':
                    datos[pos][5] = 'Cerrado'
                else:
                    print("Opción inválida")
                    continue

                nota = input("Nota (qué has hecho): ")
                datos[pos][7] = nota

                guardar(datos)
                print(" Incidencia actualizada")

        elif op == '4':
            break
        else:
            print("Opción no válida")

# ========================================
# INTERFAZ JEFE TÉCNICO
# ========================================

def menu_jefe(nombre):
    while True:
        print("\n========== JEFE TÉCNICO ==========")
        print("1. Ver todas")
        print("2. Ver pendientes")
        print("3. Cambiar estado")
        print("4. Eliminar incidencia")
        print("5. Estadísticas")
        print("6. Salir")
        print("==================================")
        
        op = input("> ")
        datos = cargar()

        if op == '1':
            print("\n--- TODAS LAS INCIDENCIAS ---")
            if len(datos) == 0:
                print("No hay incidencias")
            else:
                i = 0
                while i < len(datos):
                    inc = datos[i]
                    print("#" + str(inc[0]) + " [" + inc[5] + "] " + inc[1] + " - " + inc[2] + " (" + inc[4] + ")")
                    if inc[7] != '':
                        print("  Nota: " + inc[7])
                    i = i + 1

        elif op == '2':
            print("\n--- PENDIENTES ---")
            hay = False
            i = 0
            while i < len(datos):
                if datos[i][5] != 'Resuelto':
                    hay = True
                    print("#" + str(datos[i][0]) + " [" + datos[i][5] + "] " + datos[i][1] + " - " + datos[i][2])
                i = i + 1

            if not hay:
                print("No hay pendientes")

        elif op == '3':
            print("\n--- CAMBIAR ESTADO ---")
            
            try:
                id_buscar = int(input("ID: "))
            except:
                print("ERROR: Número inválido")
                continue

            pos = -1
            i = 0
            while i < len(datos):
                if datos[i][0] == id_buscar:
                    pos = i
                    break
                i = i + 1

            if pos == -1:
                print("No encontrada")
            else:
                print("Estado actual: " + datos[pos][5])
                print("\n1. Pendiente  2. En progreso  3. En mantenimiento  4. Resuelto  5. Cerrado")
                
                num = input("Nuevo estado (1-5): ")
                
                if num == '1':
                    datos[pos][5] = 'Pendiente'
                elif num == '2':
                    datos[pos][5] = 'En progreso'
                elif num == '3':
                    datos[pos][5] = 'En mantenimiento'
                elif num == '4':
                    datos[pos][5] = 'Resuelto'
                elif num == '5':
                    datos[pos][5] = 'Cerrado'
                else:
                    print("Opción inválida")
                    continue

                nota = input("Nota: ")
                datos[pos][7] = nota

                guardar(datos)
                print(" Actualizada")

        elif op == '4':
            print("\n--- ELIMINAR ---")
            print(" CUIDADO: No se puede deshacer")
            
            try:
                id_borrar = int(input("ID: "))
            except:
                print("ERROR: Número inválido")
                continue

            nueva = []
            encontrada = False
            i = 0
            while i < len(datos):
                if datos[i][0] == id_borrar:
                    encontrada = True
                else:
                    nueva.append(datos[i])
                i = i + 1

            guardar(nueva)

            if encontrada:
                print(" Eliminada")
            else:
                print("No encontrada")

        elif op == '5':
            print("\n--- ESTADÍSTICAS ---")
            print("Total: " + str(len(datos)))
            
            pendiente = 0
            progreso = 0
            mantenimiento = 0
            resuelto = 0
            cerrado = 0
            
            i = 0
            while i < len(datos):
                estado = datos[i][5]
                if estado == 'Pendiente':
                    pendiente = pendiente + 1
                elif estado == 'En progreso':
                    progreso = progreso + 1
                elif estado == 'En mantenimiento':
                    mantenimiento = mantenimiento + 1
                elif estado == 'Resuelto':
                    resuelto = resuelto + 1
                elif estado == 'Cerrado':
                    cerrado = cerrado + 1
                i = i + 1
            
            if pendiente > 0:
                print("  Pendiente: " + str(pendiente))
            if progreso > 0:
                print("  En progreso: " + str(progreso))
            if mantenimiento > 0:
                print("  En mantenimiento: " + str(mantenimiento))
            if resuelto > 0:
                print("  Resuelto: " + str(resuelto))
            if cerrado > 0:
                print("  Cerrado: " + str(cerrado))

        elif op == '6':
            break
        else:
            print("Opción no válida")

# ========================================
# PROGRAMA PRINCIPAL - LOGIN
# ========================================

print("=" * 50)
print(" SISTEMA DE INCIDENCIAS VERIFICANET")
print("=" * 50)
print()

# Login
usuario = input("Usuario: ")
contrasena = input("Contraseña: ")

# Verificar credenciales
if usuario in USUARIOS:
    pass_correcta, rol = USUARIOS[usuario]
    
    if contrasena == pass_correcta:
        print("\n✓ Acceso concedido - Rol: " + rol.upper())
        print()
        
        # Redirigir según rol
        if rol == 'empleado':
            menu_empleado(usuario)
        elif rol == 'tecnico':
            menu_tecnico(usuario)
        elif rol == 'jefe':
            menu_jefe(usuario)
    else:
        print("\n Contraseña incorrecta")
else:
    print("\n Usuario no encontrado")

print("\n¡Hasta luego!")