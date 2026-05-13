# 🔧 **Stack Tecnológico Completo - SIGEP**

## **📋 Visão Geral da Stack**

O SIGEP utiliza uma stack robusta e moderna, otimizada para ambiente Windows com foco em segurança, performance e escalabilidade.

---

## **🐘 PHP - Backend Engine**

### **Versão e Instalação**
- **Versão Atual**: PHP 8.4.16 (ZTS Visual C++ 2022 x64)
- **Build Date**: Dec 17 2025 10:33:12
- **Zend Engine**: v4.4.16
- **OPcache**: Zend OPcache v8.4.16 habilitado
- **Caminho de Instalação**: `C:\Program Files\PHP\`
- **Executable**: `C:\Program Files\PHP\php.exe`
- **Configuração**: `C:\Program Files\PHP\php.ini` (ativo)
- **Config Template**: `C:\Program Files\PHP\php.ini-production`
- **Extension Directory**: `C:\Program Files\PHP\ext\`

### **Extensões Ativas**
```
[PHP Modules]
bcmath          - Cálculos matemáticos de precisão
bz2             - Compressão Bzip2
calendar        - Funções de calendário
Core            - Núcleo do PHP
ctype           - Verificação de tipos de caracteres
curl            - Client URL library
date            - Funções de data/hora
dom             - Manipulação DOM XML
exif            - Metadados de imagens
fileinfo        - Informações de arquivos
filter          - Filtros de dados
ftp             - FTP client
gd              - Processamento de imagens
gettext         - Internacionalização
hash            - Funções hash
iconv           - Conversão de character sets
intl            - Internacionalização
json            - JSON encode/decode
libxml          - Manipulação XML
mbstring        - Strings multibyte (UTF-8)
mysqli          - MySQL Improved Extension
mysqlnd         - MySQL Native Driver
openssl         - OpenSSL cryptographic functions
pcre            - Expressões regulares compatíveis Perl
PDO             - PHP Data Objects
pdo_mysql       - MySQL driver for PDO
pdo_sqlite      - SQLite driver for PDO
Phar            - PHP Archive
random          - Geração de números aleatórios seguros
readline        - Interface de linha de comando
Reflection      - API Reflection
session         - Gerenciamento de sessões
SimpleXML       - Manipulação XML simplificada
sockets         - Comunicação via sockets
sodium          - Criptografia moderna
SPL             - Standard PHP Library
sqlite3         - SQLite3 database
standard        - Funções padrão
tokenizer       - Tokenizer de PHP
xml             - Manipulação XML
xmlreader       - XML Reader
xmlwriter       - XML Writer
zip             - Manipulação de arquivos ZIP
zlib            - Compressão Zlib

[Zend Modules]
Zend OPcache    - Otimizador de bytecode
```

### **Configurações Críticas**
- **Timezone**: `America/Sao_Paulo`
- **Charset**: UTF-8 rigoroso em todo o sistema
- **Error Reporting**: E_ALL para desenvolvimento
- **Display Errors**: 0 para produção
- **Memory Limit**: Configurado para processamento de planilhas
- **Upload Max Filesize**: Configurado para documentos oficiais
- **Session**: Configurações seguras com lifetime adequado

### **Uso no SIGEP**
- **PDO Prepared Statements**: Para segurança contra SQL injection
- **Sessions**: Autenticação e permissões centralizadas
- **JSON**: Comunicação AJAX com frontend
- **PHPExcel/PhpSpreadsheet**: Geração de relatórios
- **PHPWord**: Geração de documentos oficiais

---

## **📦 Composer - Gerenciamento de Dependências**

### **Configuração Atual**
```json
{
    "require": {
        "phpoffice/phpspreadsheet": "^5.4",
        "illuminate/database": "^12.53",
        "illuminate/events": "^12.53",
        "phpoffice/phpword": "^1.4"
    }
}
```

### **Dependências Detalhadas**

#### **PhpSpreadsheet (^5.4)**
- **Propósito**: Manipulação de planilhas Excel
- **Uso no SIGEP**: Exportação de relatórios, importação de dados
- **Recursos**: Leitura/escrita XLSX, XLS, CSV
- **Performance**: Otimizado para grandes volumes

#### **Illuminate Database (^12.53)**
- **Propósito**: ORM e Query Builder do Laravel
- **Uso no SIGEP**: Facilitar queries complexas
- **Recursos**: Eloquent ORM, Query Builder, Migrations
- **Integração**: Com eventos e validações

#### **Illuminate Events (^12.53)**
- **Propósito**: Sistema de eventos do Laravel
- **Uso no SIGEP**: Auditoria, triggers customizados
- **Recursos**: Event listeners, observers
- **Performance**: Dispatch assíncrono

#### **PhpWord (^1.4)**
- **Propósito**: Manipulação de documentos Word
- **Uso no SIGEP**: Geração de ofícios, documentos oficiais
- **Recursos**: Templates, formatação, headers/footers
- **Compliance**: Formatos padrão governamentais

### **Composer Lock Analysis**
- **Total de Pacotes**: 15 pacotes diretos e indiretos
- **PHP Requirement**: ^8.2 (atendido com 8.4.16)
- **Content Hash**: e938d88c6331214ca181b98e54fb6824
- **Last Updated**: Baseado nas dependências atuais
- **Executable**: `C:\Program Files\Composer\composer.bat`
- **Working Directory**: `C:\Program Files\Apache24\htdocs\sigep\`
- **Lock File**: `C:\Program Files\Apache24\htdocs\sigep\composer.lock`
- **Config File**: `C:\Program Files\Apache24\htdocs\sigep\composer.json`

### **Autoloading**
- **PSR-4**: Configurado para namespaces padrão
- **Class Map**: Otimizado para performance
- **Files**: Inclusão de helpers e funções globais

---

## **🎨 AdminLTE 3 - Framework UI**

### **Implementação Completa**
- **Versão**: AdminLTE 3.2.0
- **CDN**: `https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css`
- **Integração**: Full AdminLTE 3 implementado

### **Componentes Utilizados no SIGEP**

#### **Layout Structure**
```html
<div class="wrapper">
  <nav class="main-header navbar">        <!-- Header com notificações -->
  <aside class="main-sidebar">             <!-- Sidebar dinâmica -->
  <div class="content-wrapper">           <!-- Área principal -->
  <aside class="control-sidebar">          <!-- Painel de controle -->
  <footer class="main-footer">            <!-- Footer -->
</div>
```

#### **Cards e Widgets**
- **Small Boxes**: Cards coloridos para estatísticas
- **Info Boxes**: Indicadores de status
- **Progress Bars**: Barras de progresso
- **User Cards**: Informações de usuários

#### **Forms**
- **Form Controls**: Inputs, selects, textareas
- **Validation States**: Estados de validação
- **Custom Controls**: Date pickers, time pickers
- **File Upload**: Upload de arquivos com preview

#### **Tables**
- **Data Tables**: Tabelas com ordenação e paginação
- **Responsive Tables**: Tabelas responsivas
- **Custom Tables**: Tabelas customizadas com actions

#### **Modals**
- **Bootstrap Modals**: Diálogos modais
- **Custom Modals**: Modais customizados
- **Form Modals**: Modais com formulários

#### **Navigation**
- **Main Menu**: Menu principal com submenus
- **Breadcrumb**: Navegação estruturada
- **Tabs**: Abas para organização de conteúdo

#### **Alerts e Notificações**
- **Bootstrap Alerts**: Alertas padrão
- **Toastr**: Notificações toast
- **SweetAlert**: Confirmações customizadas

#### **Icons**
- **FontAwesome 6.4.0**: Ícones vetoriais
- **AdminLTE Icons**: Ícones específicos
- **Custom Icons**: Ícones customizados

### **Personalização SIGEP**
- **Cores**: Paleta institucional
- **Dark Mode**: Tema claro/escuro
- **Branding**: Logo e identidade visual
- **Responsive**: Mobile-first approach

### **Roadmap de Migração**
- **Current**: AdminLTE 3.2.0 full implementation
- **Future**: Planejada migração para framework moderno
- **Considerations**: React/Vue.js com design system
- **Timeline**: Pós-estabilização do sistema atual

---

## **🗄️ MySQL 8.0 - Banco de Dados**

### **Configuração de Acesso**
```php
// conf/db.php
$config = [
    'host' => '127.0.0.1',
    'dbname' => 'sigep_producao',
    'user' => 'sigep',
    'pass' => 'z3wr7bimo3?uHoro',
    'charset' => 'utf8mb4'
];
```

### **Conexão via PHP (PDO)**
```php
try {
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['user'], $config['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $pdo->exec("SET time_zone = '-03:00'");
} catch (PDOException $e) {
    returnError('Erro na conexão: ' . $e->getMessage(), 500);
}
```

### **Validação de Acessos**

#### **Via PHP (PDO)**
- **Status**: ✅ Conectando com sucesso
- **Charset**: utf8mb4 configurado
- **Timezone**: America/Sao_Paulo (-03:00)
- **Error Mode**: Exception handling
- **Fetch Mode**: Associative array
- **Emulate Prepares**: false (security)

#### **Via Linha de Comando**
- **MySQL Client**: `C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe`
- **Workbench Client**: `C:\Program Files\MySQL\MySQL Workbench 8.0\mysql.exe`
- **PATH Status**: Não detectado no PATH do sistema
- **Recomendação**: Adicionar `C:\Program Files\MySQL\MySQL Server 8.0\bin\` ao PATH
- **Alternativa**: Usar phpMyAdmin via módulo

#### **Via MCP (Model Context Protocol)**
- **Status**: ✅ Acesso através do módulo phpMyAdmin
- **Interface**: Web-based administration
- **Features**: Full database management
- **Security**: Autenticação integrada

#### **Configuração MySQL**
- **Config File**: `C:\ProgramData\MySQL\MySQL Server 8.0\my.ini`
- **Data Directory**: `C:\ProgramData\MySQL\MySQL Server 8.0\Data\`
- **Log Directory**: `C:\ProgramData\MySQL\MySQL Server 8.0\Logs\`
- **Temp Directory**: `C:\ProgramData\MySQL\MySQL Server 8.0\tmp\`

### **Configurações do Banco**

#### **Charset e Collation**
- **Database Charset**: utf8mb4
- **Table Charset**: utf8mb4
- **Collation**: utf8mb4_unicode_ci
- **Purpose**: Suporte completo Unicode (emojis, caracteres especiais)

#### **Engine**
- **Default Engine**: InnoDB
- **Features**: Transactions, foreign keys, row-level locking
- **Performance**: Optimized for concurrent access

#### **Configurações de Performance**
- **InnoDB Buffer Pool**: Configurado para workload
- **Query Cache**: Desabilitado (MySQL 8.0)
- **Connection Pool**: Configurado para application
- **Timeout Settings**: Otimizados para SIGEP

### **Segurança**
- **Prepared Statements**: Obrigatório em todo o sistema
- **User Privileges**: Princípio do menor privilégio
- **Connection Encryption**: SSL/TLS configurado
- **Audit Logging**: Todas as operações registradas

---

## **🌐 Apache 2.4 - Web Server**

### **Versão e Configuração**
- **Server Version**: Apache/2.4.66 (Win64)
- **Build**: Apache Lounge VS18 Server built: Jan 30 2026 15:51:08
- **Installation Path**: `C:\Program Files\Apache24\`
- **Executable**: `C:\Program Files\Apache24\bin\httpd.exe`
- **Configuration**: `C:\Program Files\Apache24\conf\httpd.conf`
- **DocumentRoot**: `C:\Program Files\Apache24\htdocs\sigep\`
- **Logs Directory**: `C:\Program Files\Apache24\logs\`
- **Modules Directory**: `C:\Program Files\Apache24\modules\`
- **SSL Certs**: `C:\Program Files\Apache24\conf\ssl\`
- **Extra Configs**: `C:\Program Files\Apache24\conf\extra\`

### **Módulos Carregados**
```
Loaded Modules:
 core_module (static)              - Core functionality
 win32_module (static)             - Windows-specific
 mpm_winnt_module (static)         - Multi-Processing Module
 http_module (static)              - HTTP protocol
 so_module (static)                - Shared object loading
 access_compat_module (shared)     - Access control compatibility
 auth_basic_module (shared)        - Basic authentication
 authn_core_module (shared)        - Authentication core
 authn_file_module (shared)        - File-based authentication
 authz_core_module (shared)        - Authorization core
 authz_groupfile_module (shared)  - Group-based authorization
 authz_host_module (shared)        - Host-based authorization
 authz_user_module (shared)        - User-based authorization
 filter_module (shared)            - Request filtering
 reqtimeout_module (shared)        - Request timeout
 autoindex_module (shared)         - Directory listing
 mime_module (shared)              - MIME type handling
 log_config_module (shared)        - Logging configuration
 alias_module (shared)             - URL aliasing
 dir_module (shared)               - Directory handling
 rewrite_module (shared)           - URL rewriting
 env_module (shared)               - Environment variables
 headers_module (shared)           - HTTP headers
 setenvif_module (shared)          - Environment setting
 version_module (shared)           - Server version
 ssl_module (shared)               - SSL/TLS support
 php_module (shared)               - PHP integration
```

### **Configurações Críticas**

#### **DocumentRoot**
```apache
DocumentRoot "C:/Program Files/Apache24/htdocs/sigep"
<Directory "C:/Program Files/Apache24/htdocs/sigep">
    Options Indexes FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>
```

#### **PHP Integration**
```apache
# PHP 8 Module
LoadModule php_module "modules/libphp.dll"
AddHandler application/x-httpd-php .php
PHPIniDir "C:/Program Files/PHP"
```

#### **URL Rewriting**
```apache
# Enable Rewrite Engine
RewriteEngine On

# SIGEP Rewrite Rules
RewriteRule ^autenticacao/?$ auth/login.php [L,QSA]
RewriteRule ^censura/cartas/?$ modulos/censura/cartas/cartas_view.php [L,QSA]
RewriteRule ^eclusa/movimentacoes/?$ modulos/eclusa/movimentacoes/movimentacoes_view.php [L,QSA]
```

#### **Security Headers**
```apache
# Security Headers
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection "1; mode=block"
Header always set Referrer-Policy "strict-origin-when-cross-origin"
Header always set Content-Security-Policy "default-src 'self'..."
```

#### **SSL Configuration**
```apache
# SSL Configuration
<VirtualHost *:443>
    SSLEngine on
    SSLCertificateFile "conf/ssl/cert.pem"
    SSLCertificateKeyFile "conf/ssl/key.pem"
    SSLProtocol all -SSLv3 -TLSv1 -TLSv1.1
    SSLCipherSuite HIGH:!aNULL:!MD5
</VirtualHost>
```

### **Performance Optimization**

#### **MPM Configuration**
```apache
# WinNT MPM Settings
ThreadsPerChild 150
MaxRequestsPerChild 0
```

#### **Compression**
```apache
# Gzip Compression
LoadModule deflate_module modules/mod_deflate.so
<Location />
    SetOutputFilter DEFLATE
    SetEnvIfNoCase Request_URI \
        \.(?:gif|jpe?g|png)$ no-gzip dont-vary
    SetEnvIfNoCase Request_URI \
        \.(?:exe|t?gz|zip|bz2|sit|rar)$ no-gzip dont-vary
</Location>
```

#### **Caching**
```apache
# Browser Caching
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType image/png "access plus 1 month"
    ExpiresByType image/jpg "access plus 1 month"
</IfModule>
```

### **Logging Configuration**
```apache
# Error Log
ErrorLog "logs/error.log"
LogLevel warn

# Access Log
LogFormat "%h %l %u %t \"%r\" %>s %b \"%{Referer}i\" \"%{User-Agent}i\"" combined
CustomLog "logs/access.log" combined

# PHP Error Log
php_value error_log "logs/php_errors.log"

# SIGEP Specific Logs
ErrorLog "C:/Program Files/Apache24/logs/sigep-error.log"
CustomLog "C:/Program Files/Apache24/logs/sigep-access.log" combined
```

### **Virtual Hosts**
```apache
# SIGEP Production
<VirtualHost *:80>
    ServerName sigep.local
    DocumentRoot "C:/Program Files/Apache24/htdocs/sigep"
    ErrorLog "logs/sigep-error.log"
    CustomLog "logs/sigep-access.log" combined
</VirtualHost>
```

---

## **📡 Infraestrutura e CDNs**

### **CDNs Externos**
```html
<!-- AdminLTE -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">

<!-- jQuery -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>

<!-- FontAwesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<!-- Google Fonts -->
<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
```

### **Local Assets**
- **CSS Custom**: `C:\Program Files\Apache24\htdocs\sigep\assets\css\` - Estilos específicos do SIGEP
- **JS Custom**: `C:\Program Files\Apache24\htdocs\sigep\assets\js\` - JavaScript específico
- **Images**: `C:\Program Files\Apache24\htdocs\sigep\assets\img\` - Imagens e ícones
- **Fonts**: Fontes locais se necessário
- **Modules Assets**: `C:\Program Files\Apache24\htdocs\sigep\modulos\*\assets\` - Assets por módulo

---

## **🔧 Development Tools**

### **Package Management**
- **Composer**: Gerenciamento de dependências PHP
- **NPM**: Para assets frontend (se necessário)
- **Git**: Controle de versão

### **Debugging**
- **Xdebug**: Para debugging (se configurado)
- **PHP Error Logging**: Logs detalhados
- **Apache Logs**: Access e error logs
- **Browser DevTools**: Debug frontend

---

## **📊 Performance Monitoring**

### **Server Metrics**
- **Apache Status**: `/server-status`
- **PHP Info**: `phpinfo()` para diagnóstico
- **MySQL Status**: Performance queries
- **System Resources**: CPU, Memory, Disk

### **Application Monitoring**
- **Response Time**: Tempo de carregamento
- **Error Rate**: Taxa de erros
- **User Sessions**: Sessões ativas
- **Database Queries**: Performance de queries

---

## **🔗 Documentação Relacionada**

### **Componentes da Stack**
- **[Banco de Dados](database/schema_completo.md)**: Schema completo do MySQL
- **[Caminhos Windows](paths/windows_complete.md)**: Paths completos do sistema
- **[Estrutura Código](estrutura_codigo.md)**: Organização e padrões
- **[Fluxos e Processos](../fluxos/index.md)**: Operações do sistema

### **Configurações Específicas**
- **[Segurança](../security/authentication.md)**: Detalhes de segurança
- **[Padrões](../patterns/mvc_pattern.md)**: Arquitetura MVC
- **[Performance](../patterns/best_practices.md)**: Otimizações

---

**Esta stack tecnológica foi escolhida e configurada para garantir máxima performance, segurança e escalabilidade para as operações críticas do sistema prisional SIGEP.**
