# ============================================================
#  Crear_Estructura_AD_VerificaNet.ps1
#  Crea la estructura de Unidades Organizativas en Active Directory
#  Empresa: VerificaNet
#  Autor: Proyecto Intermodular ASO
# ============================================================

Import-Module ActiveDirectory

# --- VARIABLES ---
$dominio = (Get-ADDomain).DistinguishedName   # Obtiene el DN del dominio automáticamente
$empresa = "VerificaNet"

Write-Host "============================================" -ForegroundColor Cyan
Write-Host "  Creando estructura AD para $empresa" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan

# ============================================================
# FUNCION auxiliar: crea una OU solo si no existe
# ============================================================
function New-OUSiNoExiste {
    param(
        [string]$Nombre,
        [string]$Ruta,
        [string]$Descripcion = ""
    )
    $dn = "OU=$Nombre,$Ruta"
    if (-not (Get-ADOrganizationalUnit -Filter {DistinguishedName -eq $dn} -ErrorAction SilentlyContinue)) {
        New-ADOrganizationalUnit -Name $Nombre -Path $Ruta -Description $Descripcion -ProtectedFromAccidentalDeletion $true
        Write-Host "  [+] OU creada: $dn" -ForegroundColor Green
    } else {
        Write-Host "  [=] Ya existe: $dn" -ForegroundColor Yellow
    }
}

# ============================================================
# 1. OU RAIZ de la empresa
# ============================================================
Write-Host "`n[1] Creando OU raiz de la empresa..." -ForegroundColor White
New-OUSiNoExiste -Nombre $empresa -Ruta $dominio -Descripcion "Unidad raiz de VerificaNet"

$ouEmpresa = "OU=$empresa,$dominio"

# ============================================================
# 2. OUs DE PRIMER NIVEL (grandes bloques)
# ============================================================
Write-Host "`n[2] Creando OUs de primer nivel..." -ForegroundColor White

New-OUSiNoExiste -Nombre "Usuarios"    -Ruta $ouEmpresa -Descripcion "Todos los usuarios de la empresa"
New-OUSiNoExiste -Nombre "Grupos"      -Ruta $ouEmpresa -Descripcion "Grupos de seguridad y distribucion"
New-OUSiNoExiste -Nombre "Equipos"     -Ruta $ouEmpresa -Descripcion "Equipos informaticos de la empresa"
New-OUSiNoExiste -Nombre "Servidores"  -Ruta $ouEmpresa -Descripcion "Servidores de la infraestructura"
New-OUSiNoExiste -Nombre "Impresoras"  -Ruta $ouEmpresa -Descripcion "Impresoras y colas de impresion"

# ============================================================
# 3. SUB-OUs DE DEPARTAMENTOS (dentro de Usuarios)
# ============================================================
Write-Host "`n[3] Creando sub-OUs de departamentos..." -ForegroundColor White

$ouUsuarios = "OU=Usuarios,$ouEmpresa"

$departamentos = @(
    @{Nombre="SoporteTecnico"; Desc="Atencion al cliente y resolucion de incidencias"},
    @{Nombre="Ventas";         Desc="Gestion comercial y contratos con clientes"},
    @{Nombre="Administracion"; Desc="Gestion administrativa, facturacion y pagos"},
    @{Nombre="Sistemas";       Desc="Gestion y mantenimiento de la infraestructura IT"},
    @{Nombre="Auditoria";      Desc="Control de calidad y auditoria de seguridad"}
)

foreach ($dept in $departamentos) {
    New-OUSiNoExiste -Nombre $dept.Nombre -Ruta $ouUsuarios -Descripcion $dept.Desc
}

# ============================================================
# 4. SUB-OUs DE EQUIPOS POR DEPARTAMENTO
# ============================================================
Write-Host "`n[4] Creando sub-OUs de equipos por departamento..." -ForegroundColor White

$ouEquipos = "OU=Equipos,$ouEmpresa"

foreach ($dept in $departamentos) {
    New-OUSiNoExiste -Nombre $dept.Nombre -Ruta $ouEquipos -Descripcion ("Equipos de " + $dept.Desc)
}

# ============================================================
# RESUMEN FINAL
# ============================================================
Write-Host "`n============================================" -ForegroundColor Cyan
Write-Host "  Estructura creada correctamente" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Estructura generada:" -ForegroundColor White
Write-Host ""
Write-Host "  $dominio" -ForegroundColor Gray
Write-Host "  └── OU=VerificaNet" -ForegroundColor White
Write-Host "       ├── OU=Usuarios" -ForegroundColor White
Write-Host "       │    ├── OU=SoporteTecnico" -ForegroundColor Gray
Write-Host "       │    ├── OU=Ventas" -ForegroundColor Gray
Write-Host "       │    ├── OU=Administracion" -ForegroundColor Gray
Write-Host "       │    ├── OU=Desarrollo" -ForegroundColor Gray
Write-Host "       │    └── OU=Auditoria" -ForegroundColor Gray
Write-Host "       ├── OU=Grupos" -ForegroundColor White
Write-Host "       ├── OU=Equipos" -ForegroundColor White
Write-Host "       │    ├── OU=SoporteTecnico" -ForegroundColor Gray
Write-Host "       │    ├── OU=Ventas" -ForegroundColor Gray
Write-Host "       │    ├── OU=Administracion" -ForegroundColor Gray
Write-Host "       │    ├── OU=Desarrollo" -ForegroundColor Gray
Write-Host "       │    └── OU=Auditoria" -ForegroundColor Gray
Write-Host "       ├── OU=Servidores" -ForegroundColor White
Write-Host "       └── OU=Impresoras" -ForegroundColor White
Write-Host ""