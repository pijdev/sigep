# SIGEP Job Manager - Serviço Windows com NSSM
# Script para instalar e gerenciar jobs como serviços do Windows

param(
    [Parameter(Mandatory=$false)]
    [string]$Action = "loop",

    [string]$JobId = "",
    [string]$JobName = "",
    [string]$Command = "",
    [string]$WorkingDirectory = "",
    [string]$User = "SIGEP\sigep_service",
    [Parameter(Mandatory=$false)]
    [SecureString]$Password = (ConvertTo-SecureString "z3wr7o3?uHoro" -AsPlainText -Force),
    [string]$DisplayName = "SIGEP - Rotinas"
)

# Função para log
function Write-Log {
    param(
        [string]$Message,
        [string]$Level = "INFO"
    )

    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $logMessage = "[$timestamp] [$Level] $Message"

    $logFile = "C:\Servicos\Backup\SIGEP\log\job_manager_service.log"
    $logDir = Split-Path $logFile -Parent

    if (-not (Test-Path $logDir)) {
        New-Item -ItemType Directory -Path $logDir -Force | Out-Null
    }

    Add-Content -Path $logFile -Value $logMessage -Encoding UTF8
    Write-Host $logMessage -ForegroundColor $(
        switch ($Level) {
            "INFO" { "Green" }
            "WARNING" { "Yellow" }
            "ERROR" { "Red" }
            default { "White" }
        }
    )
}

# Função para garantir dependências via Chocolatey
function Install-Dependencies {
    Write-Log "Verificando dependências..." "INFO"

    # Se não estiver rodando como Admin, não tentar instalar dependências para evitar prompts interativos que travam o serviço
    $currentPrincipal = New-Object Security.Principal.WindowsPrincipal([Security.Principal.WindowsIdentity]::GetCurrent())
    if (-not $currentPrincipal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
        Write-Log "Não é Administrador. Pulando verificação automática de dependências Chocolatey para evitar travamentos." "WARNING"
        return
    }

    if (-not (Get-Command choco -ErrorAction SilentlyContinue)) {
        Write-Log "Chocolatey não encontrado! Instale manualmente: https://chocolatey.org/install" "ERROR"
        return
    }

    $dependencies = @("nssm", "mysql-connector-net")
    foreach ($dep in $dependencies) {
        $installed = choco list --local-only $dep | Select-String $dep
        if (-not $installed) {
            Write-Log "Instalando dependência: $dep..." "INFO"
            choco install $dep -y --no-progress --accept-license
        }
    }
}

# Chamar instalação de dependências no início (exceto se for apenas list)
if ($Action -ne "list") {
    Install-Dependencies
}

# Função para executar comando como serviço
function Install-JobService {
    param(
        [string]$User
    )

    $serviceName = "sigep_service"
    $displayName = "SIGEP - Rotinas"
    $serviceDescription = "Motor de agendamento de tarefas e rotinas automatizadas do SIGEP (SIGEP Crontab)."

    # Criar estrutura de diretórios organizada
    $baseDir = "C:\Servicos"
    $categoryDir = Join-Path $baseDir "Rotinas"
    $logDir = Join-Path $baseDir "Log"

    if (-not (Test-Path $baseDir)) { New-Item -ItemType Directory -Path $baseDir -Force | Out-Null }
    if (-not (Test-Path $categoryDir)) { New-Item -ItemType Directory -Path $categoryDir -Force | Out-Null }
    if (-not (Test-Path $logDir)) { New-Item -ItemType Directory -Path $logDir -Force | Out-Null }

    Write-Log "Instalando serviço: $serviceName" "INFO"

    try {
        # Priorizar NSSM do Chocolatey
        $nssmPath = "C:\ProgramData\chocolatey\bin\nssm.exe"
        if (-not (Test-Path $nssmPath)) {
            $nssmPath = "C:\ProgramData\chocolatey\lib\nssm\tools\nssm.exe"
        }

        if (-not (Test-Path $nssmPath)) {
            Write-Log "NSSM não encontrado!" "ERROR"
            return $false
        }

        # Instalar serviço com NSSM (apenas se não existir)
        $existingService = Get-Service -Name $serviceName -ErrorAction SilentlyContinue
        if (-not $existingService) {
            Write-Log "Instalando novo serviço: $serviceName" "INFO"
            & $nssmPath install $serviceName "C:\Program Files\Apache24\htdocs\sigep\modulos\servicos\job_manager\sigep_scheduler_wrapper.bat"
            & $nssmPath set $serviceName AppDirectory "$PSScriptRoot"
            & $nssmPath set $serviceName DisplayName $displayName
            & $nssmPath set $serviceName Description $serviceDescription
        }

        # Usuário do Serviço
        if ($User -ne "SYSTEM") {
            if ($null -eq $Password) {
                Write-Log "Senha é necessária para ObjectName: $User" "WARNING"
            } else {
                # Converter SecureString para texto puro apenas no momento de passar para o executável externo (NSSM)
                $plainPassword = [System.Runtime.InteropServices.Marshal]::PtrToStringAuto([System.Runtime.InteropServices.Marshal]::SecureStringToBSTR($Password))
                & $nssmPath set $serviceName ObjectName $User $plainPassword
            }
        }

        # Configurar dependências e recovery
        & $nssmPath set $serviceName DependOnService "Tcpip"
        & $nssmPath set $serviceName AppRestartDelay 30000
        & $nssmPath set $serviceName AppThrottle 1000

        Write-Log "Serviço configurado com sucesso." "INFO"
        return $true
    } catch {
        Write-Log "Erro ao instalar serviço: $($_.Exception.Message)" "ERROR"
        return $false
    }
}

# Função do Loop de Serviço (Crontab do SIGEP)
function Start-JobServiceLoop {
    Write-Log "Iniciando loop de verificação de jobs..." "INFO"
    while ($true) {
        try {
            $phpPath = "C:\Program Files\PHP\php.exe"
            $scriptPath = Join-Path $PSScriptRoot "job_manager_logica.php"

            # Log de depuração antes de chamar o PHP
            Write-Log "DEBUG: Chamando PHP CLI para processar fila: $phpPath `"$scriptPath`"" "INFO"

            # Tentar processar via PHP CLI (Fallback Robusto)
            $processInfo = New-Object System.Diagnostics.ProcessStartInfo
            $processInfo.FileName = $phpPath
            $processInfo.Arguments = "`"$scriptPath`" action=processar_fila_cli"
            $processInfo.RedirectStandardOutput = $true
            $processInfo.RedirectStandardError = $true
            $processInfo.UseShellExecute = $false
            $process = [System.Diagnostics.Process]::Start($processInfo)
            $output = $process.StandardOutput.ReadToEnd()
            $stdErrOutput = $process.StandardError.ReadToEnd()
            $process.WaitForExit()

            if ($output -ne "" -and $output -ne "[]") {
                Write-Log "Processamento finalizado: $output" "INFO"
            }
            if ($stdErrOutput -ne "") {
                Write-Log "Erro no PHP CLI: $stdErrOutput" "ERROR"
            }
        } catch {
            Write-Log "Erro no loop: $($_.Exception.Message)" "ERROR"
        }
        Start-Sleep -Seconds 60
    }
}

# Função para remover serviço
function Remove-JobService {
    $serviceName = "sigep_service"

    try {
        $nssmPath = "C:\ProgramData\chocolatey\bin\nssm.exe"
        if (-not (Test-Path $nssmPath)) {
            $nssmPath = "C:\ProgramData\chocolatey\lib\nssm\tools\nssm.exe"
        }

        if (Get-Service -Name $serviceName -ErrorAction SilentlyContinue) {
            & $nssmPath stop $serviceName
            & $nssmPath remove $serviceName confirm
            Write-Log "Serviço removido." "INFO"
        }
    } catch {
        Write-Log "Erro ao remover serviço: $($_.Exception.Message)" "ERROR"
    }
}

# Funções de Controle de Serviço
function Get-JobServices {
    $service = Get-Service -Name "sigep_service" -ErrorAction SilentlyContinue
    if ($service) {
        Write-Host "Serviço SIGEP: $($service.Status)" -ForegroundColor Green
    } else {
        Write-Host "Serviço não instalado." -ForegroundColor Yellow
    }
}

# Programa principal
switch ($Action) {
    "install" { Install-JobService -User $User; break }
    "remove"  { Remove-JobService; break }
    "list"    { Get-JobServices; break }
    "loop"    { Start-JobServiceLoop; break }
    "start"   { Start-Service "sigep_service"; break }
    "stop"    { Stop-Service "sigep_service" -Force; break }
    default {
        Write-Host "Ações: install, remove, list, start, stop, loop" -ForegroundColor Cyan
        exit 1
    }
}
