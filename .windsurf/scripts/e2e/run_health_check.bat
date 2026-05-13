@echo off
echo ========================================
echo   SIGEP E2E Health Check
echo ========================================
echo.

cd /d "%~dp0"

echo Verificando Node.js...
node --version >nul 2>&1
if errorlevel 1 (
    echo [ERRO] Node.js nao encontrado. Instale Node.js primeiro.
    pause
    exit /b 1
)

echo Verificando dependencias...
if not exist "node_modules" (
    echo Instalando Puppeteer...
    call npm install
    if errorlevel 1 (
        echo [ERRO] Falha ao instalar dependencias.
        pause
        exit /b 1
    )
)

echo.
echo Executando teste de saude do SIGEP...
echo.

node test_sigep_health.js

if errorlevel 0 (
    echo.
    echo [SUCESSO] SIGEP esta saudavel!
) else if errorlevel 1 (
    echo.
    echo [AVISO] SIGEP com problemas. Verifique relatorio.
) else (
    echo.
    echo [ERRO] Falha critica no teste.
)

echo.
echo Relatorio: health_report.json
echo Screenshots: screenshots\
echo.
pause