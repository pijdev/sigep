@echo off
REM SIGEP Job Manager Wrapper
REM Este script simplifica a chamada do PowerShell para o NSSM

SET SCRIPT_PATH="C:\Program Files\Apache24\htdocs\sigep\modulos\servicos\job_manager\job_manager_service.ps1"

echo [%date% %time%] Iniciando Wrapper SIGEP... >> "C:\Servicos\Backup\SIGEP\log\wrapper.log"

C:\Windows\System32\WindowsPowerShell\v1.0\powershell.exe -NoProfile -ExecutionPolicy Bypass -File %SCRIPT_PATH% loop

echo [%date% %time%] Script finalizado com codigo %errorlevel% >> "C:\Servicos\Backup\SIGEP\log\wrapper.log"
