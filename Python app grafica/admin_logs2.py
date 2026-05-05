#!/usr/bin/env python3
# Panel de Administración Verificanet - Diseño Sobrio
# Para ejecutar desde Windows Server hacia servidor Linux

import tkinter as tk
from tkinter import scrolledtext, messagebox
import os
import subprocess


# -------------------------------------------------------
# Configuración del servidor
# -------------------------------------------------------
SERVIDOR = "192.168.50.41"
USUARIO  = "vagrant"


# -------------------------------------------------------
# Función para ejecutar comandos en el servidor por SSH
# -------------------------------------------------------
def ejecutar_ssh(comando):
    ssh_path = r"C:\Windows\System32\OpenSSH\ssh.exe"
    clave_vagrant = r"C:\Users\clopez\.ssh\id_rsa"

    comando_completo = f'"{ssh_path}" -i "{clave_vagrant}" -o StrictHostKeyChecking=no {USUARIO}@{SERVIDOR} "{comando}"'

    resultado = subprocess.run(
        comando_completo,
        shell=True,
        capture_output=True,
        text=True,
        encoding="utf-8",       
        errors="replace"
    )

    salida = resultado.stdout + resultado.stderr
    return salida if salida.strip() else "Sin salida"
# Función que muestra texto en el cuadro de resultados
# -------------------------------------------------------
def mostrar_en_pantalla(titulo, texto):
    cuadro_texto.delete(1.0, tk.END)
    cuadro_texto.insert(tk.END, "-" * 70 + "\n")
    cuadro_texto.insert(tk.END, titulo + "\n")
    cuadro_texto.insert(tk.END, "-" * 70 + "\n\n")
    cuadro_texto.insert(tk.END, str(texto))

# -------------------------------------------------------
# Botón: Ver log de errores de Nginx
# -------------------------------------------------------
def ver_errores():
    mostrar_en_pantalla("Cargando...", "Conectando con el servidor...")
    salida = ejecutar_ssh("sudo tail -n 50 /var/log/nginx/error.log")
    mostrar_en_pantalla("ERRORES NGINX - Últimas 50 líneas", salida)


# -------------------------------------------------------
# Botón: Ver log de accesos de Nginx
# -------------------------------------------------------
def ver_accesos():
    mostrar_en_pantalla("Cargando...", "Conectando con el servidor...")
    salida = ejecutar_ssh("sudo tail -n 50 /var/log/nginx/verificanet_access.log 2>&1")
    mostrar_en_pantalla("ACCESOS WEB - Últimas 50 líneas", salida)


# -------------------------------------------------------
# Botón: Ver log del sistema
# -------------------------------------------------------
def ver_syslog():
    mostrar_en_pantalla("Cargando...", "Conectando con el servidor...")
    salida = ejecutar_ssh("sudo tail -n 50 /var/log/syslog")
    mostrar_en_pantalla("LOG DEL SISTEMA - Últimas 50 líneas", salida)


# -------------------------------------------------------
# Botón: Ver estado de Nginx
# -------------------------------------------------------
def ver_estado():
    mostrar_en_pantalla("Cargando...", "Conectando con el servidor...")
    salida = ejecutar_ssh("sudo systemctl status nginx")
    mostrar_en_pantalla("ESTADO DE NGINX", salida)


# -------------------------------------------------------
# Botón: Reiniciar Nginx (pide confirmación)
# -------------------------------------------------------
def reiniciar_nginx():
    confirmado = messagebox.askyesno(
        "Confirmar reinicio",
        "¿Está seguro de reiniciar Nginx?\n\nEsto interrumpirá el servicio brevemente."
    )

    if not confirmado:
        return

    mostrar_en_pantalla("Reiniciando...", "Enviando comando al servidor...")
    ejecutar_ssh("sudo systemctl restart nginx")

    estado = ejecutar_ssh("sudo systemctl is-active nginx").strip()

    if estado == "active":
        mostrar_en_pantalla("REINICIO COMPLETADO", f"Nginx se reinició correctamente.\nEstado actual: {estado}")
        messagebox.showinfo("Éxito", "Nginx reiniciado correctamente.")
    else:
        mostrar_en_pantalla("ERROR EN EL REINICIO", f"No se pudo reiniciar Nginx.\nEstado actual: {estado}")
        messagebox.showerror("Error", "Error al reiniciar Nginx.")


# -------------------------------------------------------
# Crear ventana principal
# -------------------------------------------------------
ventana = tk.Tk()
ventana.title("Verificanet - Panel de Administración")
ventana.geometry("900x650")
ventana.configure(bg="#f5f5f5")

# -------------------------------------------------------
# Encabezado
# -------------------------------------------------------
frame_header = tk.Frame(ventana, bg="#ffffff", relief=tk.RIDGE, bd=1)
frame_header.pack(fill=tk.X, padx=10, pady=10)

tk.Label(
    frame_header,
    text="Panel de Administración - Verificanet",
    bg="#ffffff",
    fg="#333333",
    font=("Segoe UI", 14, "bold")
).pack(pady=8)

tk.Label(
    frame_header,
    text=f"Servidor: {SERVIDOR}  |  Usuario: {USUARIO}",
    bg="#ffffff",
    fg="#666666",
    font=("Segoe UI", 9)
).pack(pady=(0, 8))

# -------------------------------------------------------
# Frame de botones
# -------------------------------------------------------
frame_botones = tk.Frame(ventana, bg="#f5f5f5")
frame_botones.pack(pady=15)

# Estilo de botones
btn_config = {
    "width": 15,
    "height": 2,
    "font": ("Segoe UI", 9),
    "relief": tk.FLAT,
    "cursor": "hand2"
}

tk.Button(
    frame_botones, 
    text="Errores Nginx", 
    command=ver_errores,
    bg="#e8e8e8",
    fg="#333333",
    activebackground="#d0d0d0",
    **btn_config
).grid(row=0, column=0, padx=5, pady=5)

tk.Button(
    frame_botones, 
    text="Accesos Web", 
    command=ver_accesos,
    bg="#e8e8e8",
    fg="#333333",
    activebackground="#d0d0d0",
    **btn_config
).grid(row=0, column=1, padx=5, pady=5)

tk.Button(
    frame_botones, 
    text="Log Sistema", 
    command=ver_syslog,
    bg="#e8e8e8",
    fg="#333333",
    activebackground="#d0d0d0",
    **btn_config
).grid(row=0, column=2, padx=5, pady=5)

tk.Button(
    frame_botones, 
    text="Estado Nginx", 
    command=ver_estado,
    bg="#e8e8e8",
    fg="#333333",
    activebackground="#d0d0d0",
    **btn_config
).grid(row=1, column=0, padx=5, pady=5)

tk.Button(
    frame_botones, 
    text="Reiniciar Nginx", 
    command=reiniciar_nginx,
    bg="#d9534f",
    fg="#ffffff",
    activebackground="#c9302c",
    **btn_config
).grid(row=1, column=1, padx=5, pady=5)

# -------------------------------------------------------
# Área de resultados
# -------------------------------------------------------
frame_resultado = tk.Frame(ventana, bg="#f5f5f5")
frame_resultado.pack(fill=tk.BOTH, expand=True, padx=10, pady=(0, 10))

tk.Label(
    frame_resultado,
    text="Resultados:",
    bg="#f5f5f5",
    fg="#333333",
    font=("Segoe UI", 10, "bold"),
    anchor="w"
).pack(fill=tk.X, pady=(0, 5))

cuadro_texto = scrolledtext.ScrolledText(
    frame_resultado,
    width=100,
    height=25,
    bg="#ffffff",
    fg="#000000",
    font=("Consolas", 9),
    relief=tk.RIDGE,
    bd=1,
    insertbackground="#000000",
    wrap=tk.WORD
)
cuadro_texto.pack(fill=tk.BOTH, expand=True)

# Mensaje inicial
cuadro_texto.insert(tk.END, f"Panel de Administración Verificanet\n\n")
cuadro_texto.insert(tk.END, f"Servidor: {SERVIDOR}\n")
cuadro_texto.insert(tk.END, f"Usuario:  {USUARIO}\n\n")
cuadro_texto.insert(tk.END, f"Seleccione una acción para continuar.\n")

# -------------------------------------------------------
# Pie de página
# -------------------------------------------------------
tk.Label(
    ventana,
    text="Verificanet © 2025 - Sistema de Gestión de Incidencias",
    bg="#f5f5f5",
    fg="#999999",
    font=("Segoe UI", 8)
).pack(side=tk.BOTTOM, pady=5)

# -------------------------------------------------------
# Iniciar aplicación
# -------------------------------------------------------
ventana.mainloop()
