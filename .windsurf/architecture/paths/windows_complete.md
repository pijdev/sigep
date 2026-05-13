# 💻 **Caminhos Completos do Windows SIGEP**

## **📋 Visão Geral dos Caminhos**

Documentação completa de todos os caminhos do sistema operacional Windows necessários para instalação, configuração e manutenção do SIGEP.

---

## **🐘 PHP - Backend Engine**

### **Caminhos de Instalação**
```
Executable:          C:\Program Files\PHP\php.exe
Configuration:        C:\Program Files\PHP\php.ini (ativo)
Template Config:      C:\Program Files\PHP\php.ini-production
Extensions:           C:\Program Files\PHP\ext\
Extension DLLs:       C:\Program Files\PHP\ext\php_*.dll
OPcache Cache:        C:\Program Files\PHP\opcache\
Session Files:        C:\Program Files\PHP\session\
Upload Temp:          C:\Program Files\PHP\uploadtemp\
```

### **Extensões Importantes**
```
C:\Program Files\PHP\ext\php_mysqlnd.dll      - MySQL Native Driver
C:\Program Files\PHP\ext\php_pdo_mysql.dll     - PDO MySQL Driver
C:\Program Files\PHP\ext\php_opcache.dll        - OPcache
C:\Program Files\PHP\ext\php_mbstring.dll       - Multibyte Strings
C:\Program Files\PHP\ext\php_openssl.dll       - OpenSSL
C:\Program Files\PHP\ext\php_gd.dll             - Image Processing
C:\Program Files\PHP\ext\php_curl.dll            - Client URL Library
C:\Program Files\PHP\ext\php_json.dll            - JSON Support
```

### **Configurações do php.ini**
```ini
; Configurações críticas para SIGEP
extension_dir = "C:\Program Files\PHP\ext"
extension=php_mysqlnd
extension=php_pdo_mysql
extension=php_mbstring
extension=php_openssl
extension=php_gd
extension=php_curl
extension=php_json

; Configurações de performance
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=4000
opcache.revalidate_freq=2
opcache.fast_shutdown=1

; Configurações de segurança
expose_php=Off
allow_url_fopen=Off
file_uploads=On
upload_max_filesize=50M
post_max_size=50M
max_execution_time=300
memory_limit=512M

; Configurações de sessão
session.save_handler=files
session.save_path="C:\Program Files\PHP\session"
session.cookie_httponly=1
session.use_strict_mode=1
session.cookie_samesite=Strict
```

---

## **📦 Composer - Gerenciamento de Dependências**

### **Caminhos do Composer**
```
Executable:          C:\Program Files\Composer\composer.bat
Composer Home:        C:\Users\{USER}\AppData\Roaming\Composer
Cache Directory:      C:\Users\{USER}\AppData\Local\Composer\cache
Vendor Directory:     C:\Program Files\Apache24\htdocs\sigep\vendor\
Autoload:             C:\Program Files\Apache24\htdocs\sigep\vendor\autoload.php
Lock File:            C:\Program Files\Apache24\htdocs\sigep\composer.lock
Config File:          C:\Program Files\Apache24\htdocs\sigep\composer.json
```

### **Configuração do Ambiente**
```bash
# Adicionar ao PATH do Sistema
SET PATH=%PATH%;C:\Program Files\Composer;C:\Program Files\PHP

# Variáveis de Ambiente (opcionais)
set COMPOSER_HOME=C:\Users\{USER}\AppData\Roaming\Composer
set COMPOSER_CACHE_DIR=C:\Users\{USER}\AppData\Local\Composer\cache
```

### **Comandos Úteis**
```bash
# Verificar versão
composer --version

# Instalar dependências
composer install

# Atualizar dependências
composer update

# Instalar pacote específico
composer require package/name

# Gerar autoloader
composer dump-autoload
```

---

## **🗄️ MySQL 8.0 - Banco de Dados**

### **Caminhos do MySQL Server**
```
Server Executable:    C:\Program Files\MySQL\MySQL Server 8.0\bin\mysqld.exe
Client Executable:    C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe
Workbench Client:     C:\Program Files\MySQL\MySQL Workbench 8.0\mysql.exe
Config File:          C:\ProgramData\MySQL\MySQL Server 8.0\my.ini
Data Directory:       C:\ProgramData\MySQL\MySQL Server 8.0\Data\
Log Directory:        C:\ProgramData\MySQL\MySQL Server 8.0\Logs\
Temp Directory:       C:\ProgramData\MySQL\MySQL Server 8.0\tmp\
Socket File:          C:\ProgramData\MySQL\MySQL Server 8.0\mysql.sock
PID File:             C:\ProgramData\MySQL\MySQL Server 8.0\mysqld.pid
Error Log:            C:\ProgramData\MySQL\MySQL Server 8.0\Logs\error.log
Slow Query Log:       C:\ProgramData\MySQL\MySQL Server 8.0\Logs\mysql-slow.log
Binary Log:           C:\ProgramData\MySQL\MySQL Server 8.0\Data\binlog.*
```

### **Configuração do my.ini**
```ini
[mysqld]
# Configurações básicas
port=3306
basedir="C:/ProgramData/MySQL/MySQL Server 8.0/"
datadir="C:/ProgramData/MySQL/MySQL Server 8.0/Data/"
socket="C:/ProgramData/MySQL/MySQL Server 8.0/mysql.sock"
pid-file="C:/ProgramData/MySQL/MySQL Server 8.0/mysqld.pid"
log-error="C:/ProgramData/MySQL/MySQL Server 8.0/Logs/error.log"

# Configurações de performance
key_buffer_size=256M
max_allowed_packet=64M
table_open_cache=256M
sort_buffer_size=1M
read_buffer_size=1M
read_rnd_buffer_size=4M
myisam_sort_buffer_size=64M

# Configurações de segurança
skip-name-resolve
skip-grant-tables
skip-networking
local-infile=0

# Configurações de log
slow_query_log=1
slow_query_log_file="C:/ProgramData/MySQL/MySQL Server 8.0/Logs/mysql-slow.log"
long_query_time=2
log_queries_not_using_indexes=1

# Charset
character-set-server=utf8mb4
collation-server=utf8mb4_unicode_ci
```

### **Serviços Windows**
```bash
# Instalar como serviço
"C:\Program Files\MySQL\MySQL Server 8.0\bin\mysqld.exe" --install

# Iniciar serviço
net start MySQL80

# Parar serviço
net stop MySQL80

# Verificar status
sc query MySQL80
```

---

## **🌐 Apache 2.4 - Web Server**

### **Caminhos do Apache**
```
Server Executable:    C:\Program Files\Apache24\bin\httpd.exe
Service Executable:   C:\Program Files\Apache24\bin\httpd.exe -k install
Configuration:        C:\Program Files\Apache24\conf\httpd.conf
DocumentRoot:         C:\Program Files\Apache24\htdocs\sigep\
Logs Directory:        C:\Program Files\Apache24\logs\
Modules Directory:     C:\Program Files\Apache24\modules\
SSL Certificates:      C:\Program Files\Apache24\conf\ssl\
Extra Configs:        C:\Program Files\Apache24\conf\extra\
MIME Types:           C:\Program Files\Apache24\conf\mime.types
Magic File:           C:\Program Files\Apache24\conf\magic
Error Log:            C:\Program Files\Apache24\logs\error.log
Access Log:           C:\Program Files\Apache24\logs\access.log
SIGEP Error Log:      C:\Program Files\Apache24\logs\sigep-error.log
SIGEP Access Log:     C:\Program Files\Apache24\logs\sigep-access.log
PHP Error Log:        C:\Program Files\Apache24\logs\php_errors.log
```

### **Configuração do httpd.conf**
```apache
# Configurações básicas
ServerRoot "C:/Program Files/Apache24"
Listen 80
ServerAdmin admin@localhost
ServerName sigep.local
ServerAlias www.sigep.local

# Configuração do DocumentRoot
DocumentRoot "C:/Program Files/Apache24/htdocs/sigep"
<Directory "C:/Program Files/Apache24/htdocs/sigep">
    Options Indexes FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>

# Configuração PHP
LoadModule php_module "modules/libphp.dll"
AddHandler application/x-httpd-php .php
PHPIniDir "C:/Program Files/PHP"

# Configuração de Logs
ErrorLog "logs/error.log"
LogLevel warn
CustomLog "logs/access.log" combined

# Configuração SSL (se aplicável)
LoadModule ssl_module modules/mod_ssl.so
LoadModule socache_shmcb_module modules/mod_socache_shmcb.so
SSLSessionCache "shmcb:/ssl_scache(512000)"
SSLRandomSeed "builtin"
```

### **Serviços Windows**
```bash
# Instalar como serviço
"C:\Program Files\Apache24\bin\httpd.exe" -k install

# Iniciar serviço
net start Apache2.4

# Parar serviço
net stop Apache2.4

# Reiniciar serviço
net restart Apache2.4

# Verificar status
sc query Apache2.4
```

---

## **📁 SIGEP Application Paths**

### **Diretório Principal**
```
Project Root:         C:\Program Files\Apache24\htdocs\sigep\
Modules:              C:\Program Files\Apache24\htdocs\sigep\modulos\
Includes:             C:\Program Files\Apache24\htdocs\sigep\includes\
Assets:               C:\Program Files\Apache24\htdocs\sigep\assets\
Configuration:        C:\Program Files\Apache24\htdocs\sigep\conf\
Authentication:       C:\Program Files\Apache24\htdocs\sigep\auth\
Pages:                C:\Program Files\Apache24\htdocs\sigep\paginas\
Extensions:           C:\Program Files\Apache24\htdocs\sigep\extensions\
Scripts:              C:\Program Files\Apache24\htdocs\sigep\scripts\
Temp Directory:       C:\Program Files\Apache24\htdocs\sigep\temp\
Vendor:               C:\Program Files\Apache24\htdocs\sigep\vendor\
```

### **Arquivos de Configuração**
```
.htaccess:            C:\Program Files\Apache24\htdocs\sigep\.htaccess
composer.json:        C:\Program Files\Apache24\htdocs\sigep\composer.json
composer.lock:        C:\Program Files\Apache24\htdocs\sigep\composer.lock
package.json:         C:\Program Files\Apache24\htdocs\sigep\package.json
favicon.svg:          C:\Program Files\Apache24\htdocs\sigep\favicon.svg
```

### **Estrutura de Módulos**
```
Censura Module:       C:\Program Files\Apache24\htdocs\sigep\modulos\censura\
Eclusa Module:        C:\Program Files\Apache24\htdocs\sigep\modulos\eclusa\
Laboral Module:       C:\Program Files\Apache24\htdocs\sigep\modulos\laboral\
Coordenação Module:   C:\Program Files\Apache24\htdocs\sigep\modulos\coordenacao\
Serviços Module:      C:\Program Files\Apache24\htdocs\sigep\modulos\servicos\
TI Module:            C:\Program Files\Apache24\htdocs\sigep\modulos\ti\
Geral Module:         C:\Program Files\Apache24\htdocs\sigep\modulos\geral\
```

---

## **🗂️ Knowledge Base Paths**

### **Documentação do Sistema**
```
Architecture Docs:    C:\Program Files\Apache24\htdocs\sigep\.windsurf\architecture\
Fluxos Docs:         C:\Program Files\Apache24\htdocs\sigep\.windsurf\fluxos\
Patterns Docs:        C:\Program Files\Apache24\htdocs\sigep\.windsurf\patterns\
MCP Memories:          C:\Program Files\Apache24\htdocs\sigep\.windsurf\memories\
Context Data:         C:\Program Files\Apache24\htdocs\sigep\.windsurf\contexto\
Rules:                C:\Program Files\Apache24\htdocs\sigep\.windsurf\regras\
Cerebro:              C:\Program Files\Apache24\htdocs\sigep\.windsurf\cerebro\
Workflows:            C:\Program Files\Apache24\htdocs\sigep\.windsurf\workflows\
```

### **Arquivos Importantes**
```
Arquitetura Principal: C:\Program Files\Apache24\htdocs\sigep\.windsurf\architecture\arquiteturasigep.md
Padrão MVC:           C:\Program Files\Apache24\htdocs\sigep\PadrãoMVCSIGEP.md
Config DB:            C:\Program Files\Apache24\htdocs\sigep\conf\db.php
Header Global:         C:\Program Files\Apache24\htdocs\sigep\includes\header.php
Sidebar Lógica:         C:\Program Files\Apache24\htdocs\sigep\includes\sidebar_logica.php
```

---

## **🔧 Development Tools Paths**

### **Git e Versionamento**
```
Git Config:           C:\Users\{USER}\.gitconfig
Git Global Config:    C:\Program Files\Git\etc\gitconfig
Git Executable:       C:\Program Files\Git\bin\git.exe
SSH Keys:             C:\Users\{USER}\.ssh\
```

### **Node.js e NPM**
```
Node Executable:       C:\Program Files\nodejs\node.exe
NPM Executable:       C:\Program Files\nodejs\npm.cmd
NPM Global:           C:\Users\{USER}\AppData\Roaming\npm\
NPM Cache:            C:\Users\{USER}\AppData\Local\npm-cache\
Node Modules:         C:\Program Files\Apache24\htdocs\sigep\node_modules\
```

### **IDE e Editores**
```
VS Code Config:        C:\Users\{USER}\.vscode\
PHPStorm Config:      C:\Users\{USER}\.WebStorm80\
Sublime Text:         C:\Users\{USER}\AppData\Roaming\Sublime Text 3\
```

---

## **🖥️ Windows Environment Variables**

### **PATH do Sistema**
```bash
# Adicionar ao PATH do Sistema (variáveis de ambiente)
C:\Program Files\PHP\
C:\Program Files\MySQL\MySQL Server 8.0\bin\
C:\Program Files\Apache24\bin\
C:\Program Files\Composer\
C:\Program Files\Git\bin
C:\Program Files\nodejs\
```

### **Variáveis de Ambiente Adicionais**
```bash
# Configurações para SIGEP
SIGEP_ROOT=C:\Program Files\Apache24\htdocs\sigep
SIGEP_CONFIG=C:\Program Files\Apache24\htdocs\sigep\conf
SIGEP_LOGS=C:\Program Files\Apache24\logs
SIGEP_TEMP=C:\Program Files\Apache24\htdocs\sigep\temp

# Configurações de desenvolvimento
PHP_INI=C:\Program Files\PHP\php.ini
COMPOSER_HOME=C:\Users\{USER}\AppData\Roaming\Composer
MYSQL_HOME=C:\ProgramData\MySQL\MySQL Server 8.0
```

### **Como Configurar**
```bash
# Via GUI (Recomendido)
1. Painel de Controle > Sistema e Segurança > Variáveis de Ambiente
2. Novo > Variável do Sistema
3. Nome da variável: SIGEP_ROOT
4. Valor: C:\Program Files\Apache24\htdocs\sigep
5. Repetir para outras variáveis

# Via Linha de Comando (Administrador)
setx SIGEP_ROOT "C:\Program Files\Apache24\htdocs\sigep"
setx SIGEP_CONFIG "C:\Program Files\Apache24\htdocs\sigep\conf"
setx PATH "%PATH%;C:\Program Files\PHP"
```

---

## **🔑 Windows Services**

### **Serviços do SIGEP**
```
Apache Service:       Apache2.4 (running as service)
MySQL Service:        MySQL80 (running as service)
Startup Type:         Automatic
Service User:         LocalSystem
Log On As:           Local System
```

### **Comandos de Gerenciamento**
```bash
# Verificar status dos serviços
sc query Apache2.4
sc query MySQL80

# Iniciar serviços
net start Apache2.4
net start MySQL80

# Parar serviços
net stop Apache2.4
net stop MySQL80

# Reiniciar serviços
net restart Apache2.4
net restart MySQL80

# Configurar tipo de inicialização
sc config Apache2.4 start= auto
sc config MySQL80 start= auto
```

---

## **📊 Performance Monitoring Paths**

### **URLs de Monitoramento**
```
Apache Status:        http://localhost/server-status
PHP Info:             http://localhost/phpinfo.php
phpMyAdmin:           http://localhost/modulos/servicos/phpmyadmin/
SIGEP Application:    http://localhost/
```

### **Logs de Sistema**
```
Apache Error:          C:\Program Files\Apache24\logs\error.log
Apache Access:         C:\Program Files\Apache24\logs\access.log
PHP Error:            C:\Program Files\Apache24\logs\php_errors.log
MySQL Error:           C:\ProgramData\MySQL\MySQL Server 8.0\Logs\error.log
MySQL Slow Query:      C:\ProgramData\MySQL\MySQL Server 8.0\Logs\mysql-slow.log
```

### **Logs de Aplicação**
```
SIGEP Error:          C:\Program Files\Apache24\logs\sigep-error.log
SIGEP Access:         C:\Program Files\Apache24\logs\sigep-access.log
Application Logs:     C:\Program Files\Apache24\htdocs\sigep\temp\
```

---

## **🔄 Backup Paths**

### **Configurações de Backup**
```
Apache Config Backup: C:\Program Files\Apache24\conf\backup\
MySQL Backup:         C:\ProgramData\MySQL\MySQL Server 8.0\backup\
SIGEP Backup:         C:\Program Files\Apache24\htdocs\sigep\backup\
Logs Backup:          C:\Program Files\Apache24\logs\backup\
Database Dumps:       C:\Program Files\Apache24\htdocs\sigep\backup\database\
```

### **Scripts de Backup**
```
Backup Script:         C:\Program Files\Apache24\htdocs\sigep\scripts\backup.bat
Restore Script:        C:\Program Files\Apache24\htdocs\sigep\scripts\restore.bat
Database Backup:       C:\Program Files\Apache24\htdocs\sigep\scripts\db_backup.bat
```

---

## **🔐 Segurança e Permissões**

### **Permissões de Diretórios**
```bash
# Permissões recomendadas para SIGEP
C:\Program Files\Apache24\htdocs\sigep\ (755)
C:\Program Files\Apache24\htdocs\sigep\temp\ (777)
C:\Program Files\Apache24\htdocs\sigep\vendor\ (755)
C:\Program Files\Apache24\htdocs\sigep\logs\ (777)
C:\Program Files\Apache24\htdocs\sigep\uploads\ (777)
```

### **Configuração de Firewall**
```bash
# Portas necessárias para SIGEP
Port 80 (HTTP) - Apache
Port 443 (HTTPS) - Apache SSL
Port 3306 (MySQL) - Banco de Dados
Port 22 (SSH) - Acesso Remoto (se aplicável)
```

### **Antivírus**
```
Excluir diretórios do SIGEP:
C:\Program Files\Apache24\htdocs\sigep\temp\
C:\Program Files\Apache24\htdocs\sigep\vendor\
C:\Program Files\Apache24\htdocs\sigep\logs\
```

---

## **📱 Troubleshooting Paths**

### **Verificações de Configuração**
```bash
# Verificar instalação do PHP
php -v

# Verificar módulos PHP
php -m

# Verificar configuração do Apache
"C:\Program Files\Apache24\bin\httpd.exe" -V

# Verificar configuração do MySQL
"C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe" --version

# Verificar PATH
echo %PATH%
```

### **Logs de Errores Comuns**
```
PHP Startup Errors:    C:\Program Files\Apache24\logs\php_errors.log
Apache Startup Errors:  C:\Program Files\Apache24\logs\error.log
MySQL Connection Errors: C:\ProgramData\MySQL\MySQL Server 8.0\Logs\error.log
```

---

## **🚀 Performance Optimization**

### **Configurações de Cache**
```
OPcache Cache:        C:\Program Files\PHP\opcache\
Session Cache:        C:\Program Files\PHP\session\
Browser Cache:        Configurado via .htaccess
MySQL Query Cache:     Desabilitado (MySQL 8.0)
```

### **Monitoramento de Recursos**
```
Task Manager:         taskmgr.exe
Resource Monitor:      perfmon.exe
Process Explorer:     procexp.exe
```

---

## **📋 Checklist de Instalação**

### **✅ Pré-requisitos Verificados**
- [ ] PHP 8.4+ instalado
- [ ] MySQL 8.0+ instalado
- [ ] Apache 2.4+ instalado
- [ ] Composer instalado
- [ ] PATH configurado
- [ ] Permissões configuradas
- [ ] Serviços rodando
- [ ] Firewall configurado

### **✅ Configurações SIGEP**
- [ ] Banco de dados criado
- [ ] Usuários criados
- [ ] Permissões configuradas
- [ .htaccess configurado
- [ ] Logs funcionando
- [ ] Backup agendado

---

**Este documento serve como guia completo para instalação, configuração e manutenção do ambiente SIGEP no Windows, garantindo que todas as ferramentas e caminhos necessários estejam acessíveis e corretamente configurados.**
