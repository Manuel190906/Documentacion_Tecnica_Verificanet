# ============================================================================
# SCRIPT DE INSTALACIÓN DE ACTIVE DIRECTORY - SOLO DOMINIO
# Proyecto: Verificanet
# Descripción: Instala AD DS y crea el dominio verificanet.local
# ============================================================================

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "INSTALACIÓN DE ACTIVE DIRECTORY" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# ============================================================================
# VERIFICAR PERMISOS
# ============================================================================
$esAdmin = ([Security.Principal.WindowsPrincipal][Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)

if (-not $esAdmin) {
    Write-Host "ERROR: Ejecuta como Administrador" -ForegroundColor Red
    exit
}

# ============================================================================
# CONFIGURACIÓN
# ============================================================================
$dominio = "verificanet.local"
$netbios = "VERIFICANET"
$passwordDSRM = "Admin123!"

Write-Host "Dominio: $dominio" -ForegroundColor Yellow
Write-Host "NetBIOS: $netbios" -ForegroundColor Yellow
Write-Host ""

$continuar = Read-Host "¿Continuar? (S/N)"
if ($continuar -ne "S") {
    exit
}

Write-Host ""

# ============================================================================
# PASO 1: INSTALAR AD-DOMAIN-SERVICES
# ============================================================================
Write-Host "[1/2] Instalando AD-Domain-Services..." -ForegroundColor Yellow

$installed = Get-WindowsFeature -Name AD-Domain-Services | Where-Object { $_.Installed }

if ($installed) {
    Write-Host "✓ Ya está instalado" -ForegroundColor Green
} else {
    Install-WindowsFeature -Name AD-Domain-Services -IncludeManagementTools
    Write-Host "✓ Instalado" -ForegroundColor Green
}

Write-Host ""

# ============================================================================
# PASO 2: PROMOVER A DOMAIN CONTROLLER
# ============================================================================
Write-Host "[2/2] Configurando Domain Controller..." -ForegroundColor Yellow

$isDC = Get-WmiObject -Class Win32_ComputerSystem | Select-Object -ExpandProperty DomainRole

if ($isDC -ge 4) {
    Write-Host "✓ Ya es un Domain Controller" -ForegroundColor Green
    Write-Host "  Dominio: $((Get-ADDomain).DNSRoot)" -ForegroundColor Cyan
} else {
    Write-Host "Creando dominio..." -ForegroundColor Cyan
    Write-Host "El servidor se reiniciará automáticamente." -ForegroundColor Red
    Write-Host ""
    
    $safePwd = ConvertTo-SecureString $passwordDSRM -AsPlainText -Force
    
    Install-ADDSForest `
        -DomainName $dominio `
        -DomainNetbiosName $netbios `
        -ForestMode "WinThreshold" `
        -DomainMode "WinThreshold" `
        -InstallDns:$true `
        -SafeModeAdministratorPassword $safePwd `
        -Force:$true `
        -NoRebootOnCompletion:$false
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host "COMPLETADO" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""
Write-Host "Dominio: $dominio" -ForegroundColor White
Write-Host "Contraseña DSRM: $passwordDSRM" -ForegroundColor White
Write-Host ""