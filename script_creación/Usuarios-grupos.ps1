# ============================================================
#  Crear_Usuarios_Grupos_AD_VerificaNet.ps1
#  Crea grupos de seguridad y usuarios en Active Directory
#  Empresa: VerificaNet | Dominio: verificanet.local
# ============================================================

Import-Module ActiveDirectory

# --- VARIABLES ---
$dominio      = (Get-ADDomain).DistinguishedName
$empresa      = "VerificaNet"
$dominioUPN   = "verificanet.local"
$passDefecto  = ConvertTo-SecureString "Verific@2024!" -AsPlainText -Force

$ouUsuarios   = "OU=Usuarios,OU=$empresa,$dominio"
$ouGrupos     = "OU=Grupos,OU=$empresa,$dominio"

Write-Host "============================================" -ForegroundColor Cyan
Write-Host "  Creando Usuarios y Grupos - VerificaNet" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan

# ============================================================
# FUNCION auxiliar: crea grupo si no existe
# ============================================================
function New-GrupoSiNoExiste {
    param([string]$Nombre, [string]$Descripcion)
    if (-not (Get-ADGroup -Filter {Name -eq $Nombre} -ErrorAction SilentlyContinue)) {
        New-ADGroup -Name $Nombre -GroupScope Global -GroupCategory Security `
            -Path $ouGrupos -Description $Descripcion
        Write-Host "  [+] Grupo creado: $Nombre" -ForegroundColor Green
    } else {
        Write-Host "  [=] Ya existe: $Nombre" -ForegroundColor Yellow
    }
}

# ============================================================
# FUNCION auxiliar: crea usuario si no existe
# ============================================================
function New-UsuarioSiNoExiste {
    param(
        [string]$Nombre,
        [string]$Apellido,
        [string]$SamAccount,
        [string]$Departamento,
        [string]$Cargo
    )
    if (-not (Get-ADUser -Filter {SamAccountName -eq $SamAccount} -ErrorAction SilentlyContinue)) {
        $ouDepto = "OU=$Departamento,$ouUsuarios"
        New-ADUser `
            -GivenName $Nombre `
            -Surname $Apellido `
            -Name "$Nombre $Apellido" `
            -SamAccountName $SamAccount `
            -UserPrincipalName "$SamAccount@$dominioUPN" `
            -EmailAddress "$SamAccount@$dominioUPN" `
            -Department $Departamento `
            -Title $Cargo `
            -Path $ouDepto `
            -AccountPassword $passDefecto `
            -Enabled $true `
            -ChangePasswordAtLogon $true    # El usuario debe cambiar la contraseña al primer inicio
        Write-Host "  [+] Usuario creado: $SamAccount ($Nombre $Apellido)" -ForegroundColor Green
    } else {
        Write-Host "  [=] Ya existe: $SamAccount" -ForegroundColor Yellow
    }
}

# ============================================================
# 1. CREAR GRUPOS DE SEGURIDAD
# ============================================================
Write-Host "`n[1] Creando grupos de seguridad..." -ForegroundColor White

# Grupos por departamento
New-GrupoSiNoExiste -Nombre "GRP_SoporteTecnico" -Descripcion "Empleados de Soporte Tecnico"
New-GrupoSiNoExiste -Nombre "GRP_Ventas"         -Descripcion "Empleados de Ventas"
New-GrupoSiNoExiste -Nombre "GRP_Administracion" -Descripcion "Empleados de Administracion"
New-GrupoSiNoExiste -Nombre "GRP_Sistemas"       -Descripcion "Empleados de Sistemas"
New-GrupoSiNoExiste -Nombre "GRP_Auditoria"      -Descripcion "Empleados de Auditoria"

# Grupos funcionales
New-GrupoSiNoExiste -Nombre "GRP_Administradores_AD" -Descripcion "Administradores del dominio"
New-GrupoSiNoExiste -Nombre "GRP_Todos_Empleados"    -Descripcion "Todos los empleados de VerificaNet"
New-GrupoSiNoExiste -Nombre "GRP_FTP_Acceso"         -Descripcion "Usuarios con acceso al servidor FTP"

# ============================================================
# 2. CREAR USUARIOS
# ============================================================
Write-Host "`n[2] Creando usuarios..." -ForegroundColor White

# Soporte Tecnico
New-UsuarioSiNoExiste -Nombre "Maria"  -Apellido "Gonzalez" -SamAccount "mgonzalez" `
    -Departamento "SoporteTecnico" -Cargo "Tecnico de Soporte"
New-UsuarioSiNoExiste -Nombre "Juan"   -Apellido "Martinez" -SamAccount "jmartinez" `
    -Departamento "SoporteTecnico" -Cargo "Tecnico de Soporte"

# Ventas
New-UsuarioSiNoExiste -Nombre "Laura"  -Apellido "Fernandez" -SamAccount "lfernandez" `
    -Departamento "Ventas" -Cargo "Comercial"
New-UsuarioSiNoExiste -Nombre "Sofia"  -Apellido "Torres"    -SamAccount "storres" `
    -Departamento "Ventas" -Cargo "Comercial"

# Administracion
New-UsuarioSiNoExiste -Nombre "Ana"    -Apellido "Rodriguez" -SamAccount "arodriguez" `
    -Departamento "Administracion" -Cargo "Responsable Administrativo"
New-UsuarioSiNoExiste -Nombre "Miguel" -Apellido "Navarro"   -SamAccount "mnavarro" `
    -Departamento "Administracion" -Cargo "Administrativo"

# Sistemas
New-UsuarioSiNoExiste -Nombre "Carlos" -Apellido "Lopez"     -SamAccount "clopez" `
    -Departamento "Sistemas" -Cargo "Administrador de Sistemas"

# Auditoria
New-UsuarioSiNoExiste -Nombre "Pedro"  -Apellido "Sanchez"   -SamAccount "psanchez" `
    -Departamento "Auditoria" -Cargo "Auditor de Seguridad"

# Usuario administrador del dominio
New-UsuarioSiNoExiste -Nombre "Admin"  -Apellido "VerificaNet" -SamAccount "adminvnet" `
    -Departamento "Sistemas" -Cargo "Administrador de Dominio"

# ============================================================
# 3. ASIGNAR USUARIOS A SUS GRUPOS
# ============================================================
Write-Host "`n[3] Asignando usuarios a grupos..." -ForegroundColor White

# Grupos departamentales
Add-ADGroupMember -Identity "GRP_SoporteTecnico" -Members "mgonzalez","jmartinez" -ErrorAction SilentlyContinue
Add-ADGroupMember -Identity "GRP_Ventas"         -Members "lfernandez","storres"      -ErrorAction SilentlyContinue
Add-ADGroupMember -Identity "GRP_Administracion" -Members "arodriguez","mnavarro"     -ErrorAction SilentlyContinue
Add-ADGroupMember -Identity "GRP_Sistemas"       -Members "clopez","adminvnet"    -ErrorAction SilentlyContinue
Add-ADGroupMember -Identity "GRP_Auditoria"      -Members "psanchez"              -ErrorAction SilentlyContinue

# Grupo global de todos los empleados
Add-ADGroupMember -Identity "GRP_Todos_Empleados" `
    -Members "mgonzalez","jmartinez","lfernandez","storres","arodriguez","mnavarro","clopez","psanchez","adminvnet" `
    -ErrorAction SilentlyContinue

# Grupo FTP (todos acceden al FTP)
Add-ADGroupMember -Identity "GRP_FTP_Acceso" `
    -Members "mgonzalez","jmartinez","lfernandez","storres","arodriguez","mnavarro","clopez","psanchez" `
    -ErrorAction SilentlyContinue

# Administrador del dominio
Add-ADGroupMember -Identity "GRP_Administradores_AD" -Members "adminvnet" -ErrorAction SilentlyContinue
# En Windows Server en español el grupo se llama "Admins. del dominio"
$grupoAdmins = Get-ADGroup -Filter {SamAccountName -eq "Domain Admins"} -ErrorAction SilentlyContinue
if (-not $grupoAdmins) {
    $grupoAdmins = Get-ADGroup -Filter {Name -like "Admins*dominio*"} -ErrorAction SilentlyContinue
}
if ($grupoAdmins) {
    Add-ADGroupMember -Identity $grupoAdmins -Members "adminvnet" -ErrorAction SilentlyContinue
    Write-Host "  [+] adminvnet añadido a $($grupoAdmins.Name)" -ForegroundColor Green
} else {
    Write-Host "  [!] No se encontro el grupo Domain Admins - añadelo manualmente" -ForegroundColor Red
}

Write-Host "  [+] Asignaciones completadas" -ForegroundColor Green

# ============================================================
# RESUMEN
# ============================================================
Write-Host "`n============================================" -ForegroundColor Cyan
Write-Host "  Usuarios y Grupos creados correctamente" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Contrasena por defecto: Verific@2024!" -ForegroundColor Yellow
Write-Host "(Los usuarios deberan cambiarla en el primer inicio de sesion)" -ForegroundColor Yellow
Write-Host ""
Write-Host "Usuarios creados:" -ForegroundColor White
Write-Host "  mgonzalez   -> GRP_SoporteTecnico" -ForegroundColor Gray
Write-Host "  jmartinez   -> GRP_SoporteTecnico" -ForegroundColor Gray
Write-Host "  lfernandez  -> GRP_Ventas" -ForegroundColor Gray
Write-Host "  storres     -> GRP_Ventas" -ForegroundColor Gray
Write-Host "  arodriguez  -> GRP_Administracion" -ForegroundColor Gray
Write-Host "  mnavarro    -> GRP_Administracion" -ForegroundColor Gray
Write-Host "  clopez      -> GRP_Sistemas" -ForegroundColor Gray
Write-Host "  psanchez    -> GRP_Auditoria" -ForegroundColor Gray
Write-Host "  adminvnet   -> GRP_Administradores_AD + Domain Admins" -ForegroundColor Gray
Write-Host ""