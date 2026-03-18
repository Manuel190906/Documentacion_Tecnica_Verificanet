# ============================================================
#  Configurar_DHCP_VerificaNet.ps1
#  Instala y configura el servicio DHCP en Windows Server
#  Empresa: VerificaNet | Dominio: verificanet.local
# ============================================================

# --- VARIABLES (ajusta si es necesario) ---
$servidorIP     = "192.168.50.10"   # IP del servidor Windows
$puertaEnlace   = "192.168.50.1"    # IP del router/gateway
$rangoInicio    = "192.168.50.100"  # Inicio del rango DHCP
$rangoFin       = "192.168.50.200"  # Fin del rango DHCP
$mascara        = "255.255.255.0"
$dominio        = "verificanet.local"
$nombreScope    = "LAN_VerificaNet"

# IPs fijas (reservas) para maquinas Vagrant
$reservas = @(
    @{Nombre="SRV-FIREWALL"; IP="192.168.50.1";  MAC="08-00-27-26-60-63"},
    @{Nombre="SRV-WEB";      IP="192.168.50.30"; MAC="08-00-27-93-03-24"},
    @{Nombre="SRV-BD";       IP="192.168.50.50"; MAC="08-00-27-f5-22-b3"},
    @{Nombre="SRV-BACKEND1"; IP="192.168.50.41"; MAC="08-00-27-3b-60-14"},
    @{Nombre="SRV-BACKEND2"; IP="192.168.50.42"; MAC="08-00-27-d8-24-a7"}
    # FTP va en Windows Server (IP estatica 192.168.50.10) - no necesita reserva DHCP
)

Write-Host "============================================" -ForegroundColor Cyan
Write-Host "  Configurando DHCP - VerificaNet" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan

# ============================================================
# 1. Instalar rol DHCP
# ============================================================
Write-Host "`n[1] Instalando rol DHCP..." -ForegroundColor White
Install-WindowsFeature -Name DHCP -IncludeManagementTools
Write-Host "  [+] Rol DHCP instalado" -ForegroundColor Green

# ============================================================
# 2. Autorizar servidor DHCP en AD
# ============================================================
Write-Host "`n[2] Autorizando servidor DHCP en Active Directory..." -ForegroundColor White
Add-DhcpServerInDC -DnsName "$env:COMPUTERNAME.$dominio" -IPAddress $servidorIP
Write-Host "  [+] Servidor autorizado en AD" -ForegroundColor Green

# ============================================================
# 3. Crear scope (ambito)
# ============================================================
Write-Host "`n[3] Creando scope DHCP..." -ForegroundColor White
Add-DhcpServerv4Scope `
    -Name $nombreScope `
    -StartRange $rangoInicio `
    -EndRange $rangoFin `
    -SubnetMask $mascara `
    -Description "Scope principal LAN VerificaNet" `
    -State Active
Write-Host "  [+] Scope creado: $rangoInicio - $rangoFin" -ForegroundColor Green

# ============================================================
# 4. Opciones del scope (gateway, DNS, dominio)
# ============================================================
Write-Host "`n[4] Configurando opciones del scope..." -ForegroundColor White
$scopeID = (Get-DhcpServerv4Scope).ScopeId

Set-DhcpServerv4OptionValue -ScopeId $scopeID `
    -Router $puertaEnlace `
    -DnsServer $servidorIP `
    -DnsDomain $dominio

Write-Host "  [+] Gateway: $puertaEnlace" -ForegroundColor Green
Write-Host "  [+] DNS: $servidorIP" -ForegroundColor Green
Write-Host "  [+] Dominio: $dominio" -ForegroundColor Green

# ============================================================
# 5. Exclusiones (IPs de servidores y equipos criticos)
# ============================================================
Write-Host "`n[5] Añadiendo exclusiones..." -ForegroundColor White
Add-DhcpServerv4ExclusionRange -ScopeId $scopeID -StartRange "192.168.50.1" -EndRange "192.168.50.50"
Write-Host "  [+] Excluido rango: 192.168.50.1 - 192.168.50.50 (servidores y equipos criticos)" -ForegroundColor Green

# ============================================================
# 6. Reservas para servidores
# ============================================================
Write-Host "`n[6] Creando reservas para servidores..." -ForegroundColor White
foreach ($r in $reservas) {
    Add-DhcpServerv4Reservation `
        -ScopeId $scopeID `
        -IPAddress $r.IP `
        -ClientId $r.MAC `
        -Name $r.Nombre `
        -Description ("Reserva fija para " + $r.Nombre)
    Write-Host "  [+] Reserva: $($r.Nombre) -> $($r.IP)" -ForegroundColor Green
}

# ============================================================
# 7. Reiniciar servicio
# ============================================================
Write-Host "`n[7] Reiniciando servicio DHCP..." -ForegroundColor White
Restart-Service -Name DHCPServer
Write-Host "  [+] Servicio DHCP activo" -ForegroundColor Green

Write-Host "`n============================================" -ForegroundColor Cyan
Write-Host "  DHCP configurado correctamente" -ForegroundColor Cyan
Write-Host "============================================`n" -ForegroundColor Cyan