# ============================================================
#  Configurar_FTP_VerificaNet.ps1
#  Instala y configura el servicio FTP en Windows Server (IIS)
#  Empresa: VerificaNet | Dominio: verificanet.local
# ============================================================

# --- VARIABLES ---
$dominioFTP   = "verificanet.local"
$ftpRaiz      = "C:\FTP"
$servidorIP   = "192.168.50.42"

# Departamentos y sus carpetas compartidas
$departamentos = @("SoporteTecnico", "Ventas", "Administracion", "Sistemas", "Auditoria")

# Usuarios FTP por departamento (usuario -> departamento)
$usuariosFTP = @(
    @{Usuario="ftp-soporte"; Password="Soporte@2024!"; Depto="SoporteTecnico"},
    @{Usuario="ftp-ventas";  Password="Ventas@2024!";  Depto="Ventas"},
    @{Usuario="ftp-admin";   Password="Admin@2024!";   Depto="Administracion"},
    @{Usuario="ftp-sistemas";Password="Sistemas@2024!";Depto="Sistemas"},
    @{Usuario="ftp-auditoria";Password="Auditoria@2024!";Depto="Auditoria"},
    @{Usuario="ftp-global";  Password="Global@2024!";  Depto="Compartido"}  # Acceso a carpeta compartida
)

Write-Host "============================================" -ForegroundColor Cyan
Write-Host "  Configurando FTP - VerificaNet" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan

# ============================================================
# 1. Instalar IIS y rol FTP
# ============================================================
Write-Host "`n[1] Instalando IIS y rol FTP..." -ForegroundColor White
Install-WindowsFeature -Name Web-Server, Web-Ftp-Server, Web-Ftp-Service -IncludeManagementTools
Import-Module WebAdministration
Write-Host "  [+] IIS y FTP instalados" -ForegroundColor Green

# ============================================================
# 2. Crear estructura de carpetas
# ============================================================
Write-Host "`n[2] Creando estructura de carpetas FTP..." -ForegroundColor White

# Carpeta raiz
if (-not (Test-Path $ftpRaiz)) { New-Item -ItemType Directory -Path $ftpRaiz | Out-Null }

# Carpeta compartida (todos los departamentos)
$carpetaCompartida = "$ftpRaiz\Compartido"
if (-not (Test-Path $carpetaCompartida)) { New-Item -ItemType Directory -Path $carpetaCompartida | Out-Null }
Write-Host "  [+] Carpeta: $carpetaCompartida" -ForegroundColor Green

# Carpetas por departamento
foreach ($depto in $departamentos) {
    $ruta = "$ftpRaiz\$depto"
    if (-not (Test-Path $ruta)) { New-Item -ItemType Directory -Path $ruta | Out-Null }
    Write-Host "  [+] Carpeta: $ruta" -ForegroundColor Green
}

# ============================================================
# 3. Crear usuarios locales para FTP
# ============================================================
Write-Host "`n[3] Creando usuarios FTP..." -ForegroundColor White
foreach ($u in $usuariosFTP) {
    if (-not (Get-LocalUser -Name $u.Usuario -ErrorAction SilentlyContinue)) {
        $pass = ConvertTo-SecureString $u.Password -AsPlainText -Force
        New-LocalUser -Name $u.Usuario -Password $pass -Description "Usuario FTP - $($u.Depto)" -PasswordNeverExpires
        Write-Host "  [+] Usuario creado: $($u.Usuario)" -ForegroundColor Green
    } else {
        Write-Host "  [=] Ya existe: $($u.Usuario)" -ForegroundColor Yellow
    }
}

# ============================================================
# 4. Crear sitio FTP en IIS
# ============================================================
Write-Host "`n[4] Creando sitio FTP en IIS..." -ForegroundColor White

# Eliminar sitio FTP si ya existe
if (Get-WebSite -Name "FTP_VerificaNet" -ErrorAction SilentlyContinue) {
    Remove-WebSite -Name "FTP_VerificaNet"
}

New-WebFtpSite `
    -Name "FTP_VerificaNet" `
    -Port 21 `
    -PhysicalPath $ftpRaiz `
    -IPAddress $servidorIP `
    -Force

Write-Host "  [+] Sitio FTP creado en puerto 21" -ForegroundColor Green

# ============================================================
# 5. Configurar autenticacion basica
# ============================================================
Write-Host "`n[5] Configurando autenticacion..." -ForegroundColor White
Set-WebConfigurationProperty -Filter "/system.ftpServer/security/authentication/basicAuthentication" `
    -Name "enabled" -Value $true -PSPath "IIS:\Sites\FTP_VerificaNet"
Set-WebConfigurationProperty -Filter "/system.ftpServer/security/authentication/anonymousAuthentication" `
    -Name "enabled" -Value $false -PSPath "IIS:\Sites\FTP_VerificaNet"
Write-Host "  [+] Autenticacion basica activada, anonima desactivada" -ForegroundColor Green

# ============================================================
# 6. Configurar aislamiento de usuarios
# ============================================================
Write-Host "`n[6] Configurando aislamiento de usuarios..." -ForegroundColor White
Set-WebConfigurationProperty -Filter "system.ftpServer/userIsolation" `
    -Name "mode" -Value "IsolateRoots" -PSPath "IIS:\Sites\FTP_VerificaNet"
Write-Host "  [+] Aislamiento de usuarios activado" -ForegroundColor Green

# ============================================================
# 7. Asignar permisos de carpetas a usuarios
# ============================================================
Write-Host "`n[7] Asignando permisos..." -ForegroundColor White
foreach ($u in $usuariosFTP) {
    $rutaDepto = "$ftpRaiz\$($u.Depto)"
    if (Test-Path $rutaDepto) {
        $acl = Get-Acl $rutaDepto
        $permiso = New-Object System.Security.AccessControl.FileSystemAccessRule(
            $u.Usuario, "Modify", "ContainerInherit,ObjectInherit", "None", "Allow"
        )
        $acl.SetAccessRule($permiso)
        Set-Acl -Path $rutaDepto -AclObject $acl
        Write-Host "  [+] Permiso Modify -> $($u.Usuario) en \$($u.Depto)" -ForegroundColor Green
    }
}

# Permisos de solo lectura en carpeta compartida para todos
foreach ($u in $usuariosFTP) {
    $acl = Get-Acl $carpetaCompartida
    $permiso = New-Object System.Security.AccessControl.FileSystemAccessRule(
        $u.Usuario, "ReadAndExecute", "ContainerInherit,ObjectInherit", "None", "Allow"
    )
    $acl.SetAccessRule($permiso)
    Set-Acl -Path $carpetaCompartida -AclObject $acl
}
Write-Host "  [+] Todos los usuarios tienen lectura en \Compartido" -ForegroundColor Green

# ============================================================
# 8. Abrir puerto 21 en el firewall
# ============================================================
Write-Host "`n[8] Abriendo puertos en firewall..." -ForegroundColor White
New-NetFirewallRule -DisplayName "FTP Puerto 21" -Direction Inbound -Protocol TCP -LocalPort 21 -Action Allow -ErrorAction SilentlyContinue
New-NetFirewallRule -DisplayName "FTP Pasivo 1024-1048" -Direction Inbound -Protocol TCP -LocalPort 1024-1048 -Action Allow -ErrorAction SilentlyContinue
Write-Host "  [+] Puerto 21 y rango pasivo (1024-1048) abiertos" -ForegroundColor Green

# ============================================================
# 9. Iniciar sitio FTP
# ============================================================
Write-Host "`n[9] Iniciando sitio FTP..." -ForegroundColor White
Start-WebSite -Name "FTP_VerificaNet"
Write-Host "  [+] Sitio FTP activo" -ForegroundColor Green

Write-Host "`n============================================" -ForegroundColor Cyan
Write-Host "  FTP configurado correctamente" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Estructura de carpetas:" -ForegroundColor White
Write-Host "  C:\FTP\" -ForegroundColor Gray
Write-Host "  ├── Compartido\    (lectura para todos)" -ForegroundColor Gray
Write-Host "  ├── SoporteTecnico\" -ForegroundColor Gray
Write-Host "  ├── Ventas\" -ForegroundColor Gray
Write-Host "  ├── Administracion\" -ForegroundColor Gray
Write-Host "  ├── Sistemas\" -ForegroundColor Gray
Write-Host "  └── Auditoria\" -ForegroundColor Gray
Write-Host ""
Write-Host "Usuarios FTP creados:" -ForegroundColor White
foreach ($u in $usuariosFTP) {
    Write-Host "  $($u.Usuario) -> \$($u.Depto)" -ForegroundColor Gray
}
Write-Host ""