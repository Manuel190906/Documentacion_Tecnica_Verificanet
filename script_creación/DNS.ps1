# ============================================================
#  Configurar_DNS_VerificaNet.ps1
#  Instala y configura el servicio DNS en Windows Server
#  Empresa: VerificaNet | Dominio: verificanet.local
# ============================================================

# --- VARIABLES ---
$dominio      = "verificanet.local"
$zonaInversa  = "50.168.192.in-addr.arpa"   # Inversa para 192.168.50.0/24
$servidorIP   = "192.168.50.10"

# Registros A que se crearán en la zona directa
$registrosA = @(
    @{Nombre="srv-win";      IP="192.168.50.10"},  # Windows Server (DC + FTP)
    @{Nombre="srv-web";      IP="192.168.50.30"},  # Web Frontend
    @{Nombre="srv-bd";       IP="192.168.50.50"},  # Base de Datos
    @{Nombre="srv-backend1"; IP="192.168.50.41"},  # Backend 1
    @{Nombre="srv-backend2"; IP="192.168.50.42"},  # Backend 2
    @{Nombre="srv-firewall"; IP="192.168.50.1"}    # Firewall/Gateway
)

Write-Host "============================================" -ForegroundColor Cyan
Write-Host "  Configurando DNS - VerificaNet" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan

# ============================================================
# 1. Instalar rol DNS
# ============================================================
Write-Host "`n[1] Instalando rol DNS..." -ForegroundColor White
Install-WindowsFeature -Name DNS -IncludeManagementTools
Write-Host "  [+] Rol DNS instalado" -ForegroundColor Green

# ============================================================
# 2. Crear zona de busqueda directa
# ============================================================
Write-Host "`n[2] Creando zona directa: $dominio..." -ForegroundColor White
if (-not (Get-DnsServerZone -Name $dominio -ErrorAction SilentlyContinue)) {
    Add-DnsServerPrimaryZone `
        -Name $dominio `
        -ReplicationScope "Forest" `
        -DynamicUpdate "Secure"
    Write-Host "  [+] Zona directa creada: $dominio" -ForegroundColor Green
} else {
    Write-Host "  [=] Ya existe la zona: $dominio" -ForegroundColor Yellow
}

# ============================================================
# 3. Crear zona de busqueda inversa
# ============================================================
Write-Host "`n[3] Creando zona inversa: $zonaInversa..." -ForegroundColor White
if (-not (Get-DnsServerZone -Name $zonaInversa -ErrorAction SilentlyContinue)) {
    Add-DnsServerPrimaryZone `
        -NetworkID "192.168.50.0/24" `
        -ReplicationScope "Forest" `
        -DynamicUpdate "Secure"
    Write-Host "  [+] Zona inversa creada: $zonaInversa" -ForegroundColor Green
} else {
    Write-Host "  [=] Ya existe la zona inversa" -ForegroundColor Yellow
}

# ============================================================
# 4. Crear registros A (directos) y PTR (inversos)
# ============================================================
Write-Host "`n[4] Creando registros A y PTR..." -ForegroundColor White
foreach ($r in $registrosA) {
    # Registro A
    if (-not (Get-DnsServerResourceRecord -ZoneName $dominio -Name $r.Nombre -ErrorAction SilentlyContinue)) {
        Add-DnsServerResourceRecordA `
            -ZoneName $dominio `
            -Name $r.Nombre `
            -IPv4Address $r.IP `
            -CreatePtr
        Write-Host "  [+] A:   $($r.Nombre).$dominio -> $($r.IP)" -ForegroundColor Green
    } else {
        Write-Host "  [=] Ya existe: $($r.Nombre)" -ForegroundColor Yellow
    }
}

# ============================================================
# 5. Registros CNAME (alias utiles)
# ============================================================
Write-Host "`n[5] Creando registros CNAME..." -ForegroundColor White
$cnames = @(
    @{Alias="www";    Destino="srv-web.$dominio."},
    @{Alias="ftp";    Destino="srv-ftp.$dominio."},
    @{Alias="bd";     Destino="srv-bd.$dominio."}
)

foreach ($c in $cnames) {
    if (-not (Get-DnsServerResourceRecord -ZoneName $dominio -Name $c.Alias -ErrorAction SilentlyContinue)) {
        Add-DnsServerResourceRecordCName `
            -ZoneName $dominio `
            -Name $c.Alias `
            -HostNameAlias $c.Destino
        Write-Host "  [+] CNAME: $($c.Alias).$dominio -> $($c.Destino)" -ForegroundColor Green
    } else {
        Write-Host "  [=] Ya existe CNAME: $($c.Alias)" -ForegroundColor Yellow
    }
}

# ============================================================
# 6. Reiniciar servicio
# ============================================================
Write-Host "`n[6] Reiniciando servicio DNS..." -ForegroundColor White
Restart-Service -Name DNS
Write-Host "  [+] Servicio DNS activo" -ForegroundColor Green

Write-Host "`n============================================" -ForegroundColor Cyan
Write-Host "  DNS configurado correctamente" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Registros creados:" -ForegroundColor White
Write-Host "  srv-win.verificanet.local      -> 192.168.50.10  (Windows Server + FTP)" -ForegroundColor Gray
Write-Host "  srv-web.verificanet.local      -> 192.168.50.30  (Web Frontend)" -ForegroundColor Gray
Write-Host "  srv-bd.verificanet.local       -> 192.168.50.50  (Base de Datos)" -ForegroundColor Gray
Write-Host "  srv-backend1.verificanet.local -> 192.168.50.41  (Backend 1)" -ForegroundColor Gray
Write-Host "  srv-backend2.verificanet.local -> 192.168.50.42  (Backend 2)" -ForegroundColor Gray
Write-Host "  srv-firewall.verificanet.local -> 192.168.50.1   (Firewall/Gateway)" -ForegroundColor Gray
Write-Host "  www  (CNAME -> srv-web)" -ForegroundColor Gray
Write-Host "  ftp  (CNAME -> srv-win)" -ForegroundColor Gray
Write-Host "  bd   (CNAME -> srv-bd)" -ForegroundColor Gray
Write-Host ""