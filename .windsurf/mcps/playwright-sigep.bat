@echo off
REM Playwright CLI para SIGEP com configuração personalizada
REM Uso: playwright-sigep [comando] [argumentos]

if "%1"=="" (
    echo Playwright CLI para SIGEP
    echo Uso: playwright-sigep [comando] [argumentos]
    echo Exemplo: playwright-sigep open http://localhost/ --headed
    goto :EOF
)

playwright-cli --config .windsurf/mcps/cli.config.json %*
