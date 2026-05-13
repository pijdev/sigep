# 📚 **Desenvolvimento - Sistema SIGEP**

## **📋 Visão Geral do Desenvolvimento**

O desenvolvimento do SIGEP segue um processo estruturado baseado em padrões consistentes, ferramentas específicas e um fluxo de trabalho bem definido. Esta seção documenta todo o ecossistema de desenvolvimento para novos e experientes desenvolvedores.

---

## **🛠️ 9.1 Setup Inicial**

### **🚀 Ambiente de Desenvolvimento Local**

#### **📁 Estrutura de Diretórios**
```
C:\Program Files\Apache24\htdocs\sigep\
├── 📁 logs\ (logs do sistema)
├── 📁 temp\ (arquivos temporários)
├── 📁 cache\ (cache de desenvolvimento)
└── 📁 vendor\ (dependências Composer)
```

#### **🌐 Configuração do Servidor Local**
```apache
# httpd-vhosts.conf (Apache)
<VirtualHost *:8080>
    ServerName sigep.local
    DocumentRoot "C:/dev/sigep/htdocs"
    ErrorLog "C:/dev/sigep/logs/apache_error.log"
    CustomLog "C:/dev/sigep/logs/apache_access.log" combined

    # PHP
    LoadModule php_module "C:/Program Files/PHP/php8apache2_4.dll"
    AddHandler application/x-httpd-php .php
    PHPIniDir "C:/Program Files/PHP"

    # Headers de desenvolvimento
    Header always set X-Debug "1"
    Header always set X-Environment "development"
</VirtualHost>
```

#### **🗄️ Banco de Dados Local**
```sql
-- Criar banco de desenvolvimento
CREATE DATABASE sigep_dev CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

-- Importar estrutura do banco de produção
-- mysqldump -u root -p --no-data sigep_producao | mysql -u root -p sigep_dev

-- Copiar dados selecionados (anonimizados)
-- INSERT INTO sigep_dev.internos SELECT * FROM sigep_producao.internos LIMIT 100;
```

#### **⚙️ Configuração do PHP para Desenvolvimento**
```ini
; php-dev.ini
; Exibição de erros
display_errors = On
error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT
log_errors = On
error_log = "C:/dev/sigep/logs/php_errors.log"

; Limites de desenvolvimento
memory_limit = 512M
max_execution_time = 300
max_input_vars = 3000
upload_max_filesize = 100M
post_max_size = 100M

; Xdebug para debugging
zend_extension = xdebug
xdebug.mode = debug
xdebug.start_with_request = yes
xdebug.client_host = 127.0.0.1
xdebug.client_port = 9003
xdebug.idekey = VSCODE
```

#### **🚀 Nota Importante**
**Não existe ambiente de desenvolvimento separado**. O SIGEP opera em um único ambiente de produção em `C:\dev\sigep\`.

**Configuração de Desenvolvimento** deve ser feita no ambiente local do desenvolvedor, não no servidor de produção.

**Para desenvolvimento local**, o desenvolvedor deve:**
1. **Criar seu próprio ambiente local** com Docker ou WAMP
2. **Usar o `PadrãoMVCSIGEP.md`** como referência
3. **Configurar seu IDE** com as extensões das skills
4. **Seguir as orientações** de desenvolvimento documentadas

#### **⚙️ Configuração do PHP para Desenvolvimento**
```ini
; php-dev.ini
; Exibição de erros
display_errors = On
error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT
log_errors = On
error_log = "C:/dev/sigep/logs/php_errors.log"

; Limites de desenvolvimento
memory_limit = 512M
max_execution_time = 300
max_input_vars = 3000
upload_max_filesize = 100M
post_max_size = 100M

; Xdebug para debugging
zend_extension = xdebug
xdebug.mode = debug
xdebug.start_with_request = yes
xdebug.client_host = 127.0.0.1
xdebug.client_port = 9003
xdebug.idekey = VSCODE
```

---

## **🔧 9.2 Ferramentas de Desenvolvimento**

### **💻 IDEs e Editores**

#### **🎯 Visual Studio Code (Recomendado)**
```json
// .vscode/settings.json
{
    "php.validate.executablePath": "C:/Program Files/PHP/php.exe",
    "php.debug.executablePath": "C:/Program Files/PHP/php.exe",
    "files.associations": {
        "*.php": {
            "editor": "vscode-php-language-features",
            "files.defaultLanguage": "html"
        }
    },
    "emmet.includeLanguages": {
        "php": "html"
    },
    "php.suggest.basic": false,
    "editor.formatOnSave": true,
    "editor.tabSize": 4,
    "editor.insertSpaces": true,
    "editor.wordWrap": true,
    "files.exclude": {
        "**/vendor/**": true,
        "**/node_modules/**": true,
        "**/.git/**": true
    }
}
```

#### **🔧 Extensões VS Code Essenciais**
```json
// .vscode/extensions.json
[
    "xdebug.php-debug",
    "php-debug",
    "php-intellisense",
    "composer",
    "gitlens",
    "bradlc.vscode-tailwindcss",
    "formulahendry.auto-rename-tag",
    "esbenp.prettier-vscode",
    "ms-vscode.vscode-json"
]
```

#### **🐘 PHPStorm (Alternativa)**
- **Configuração**: Interpreter PHP 8.4
- **Database**: MySQL 8.0 connection
- **Xdebug**: Port 9000
- **Code Style**: PSR-12
- **Integration**: Git, Composer

### **📋 Ferramentas Essenciais**

#### **🔧 Stack de Desenvolvimento**
| Ferramenta | Versão | Uso | Configuração |
|---------|--------|-----|-------------|
| **PHP** | 8.4.16 | Backend principal | `C:/Program Files/PHP/` |
| **Composer** | 2.7.0 | Dependências | Global install |
| **MySQL** | 8.0.44 | Banco de dados | `C:/Program Files/MySQL/MySQL Server 8.0/` |
| **Apache** | 2.4.58 | Servidor web | `C:/Program Files/Apache24/` |
| **Git** | 2.45.2 | Controle de versão | Global install |
| **Node.js** | 20.11.0 | Frontend tools | `C:/Program Files/nodejs/` |

#### **🌐 Navegadores para Testes**
| Navegador   | Uso         | DevTools           |
| -------------| -------------| --------------------|
| **Chrome**  | Principal   | F12, DevTools      |
| **Firefox** | Alternativo | F12, Firebug       |
| **Edge**    | Windows     | F12, DevTools      |
| **Safari**  | macOS       | F12, Web Inspector |

---

## **📝 9.3 Padrões de Código**

### **🏗️ Padrão MVC do SIGEP**

#### **📁 Estrutura de Arquivos Padrão**
```
módulo/
├── 📄 [módulo]_view.php (Interface HTML)
├── 📄 [módulo]_logica.php (Controller PHP)
├── 📁 assets/
│   ├── 📄 css/[módulo].css (Estilos)
│   ├── 📄 js/[módulo].js (JavaScript)
│   └── 📄 img/[módulo]/ (Imagens)
└── 📁 README.md (Documentação do módulo)
```

#### **📝 Padrão de View (Interface)**
```php
<?php
// [módulo]_view.php
session_start();
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . "/[módulo]_logica.php";
?>

<!-- Page Header -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Título do Módulo</h1>
            </div>
            <div class="col-sm-6">
                <!-- Breadcrumb ou ações -->
            </div>
        </div>
    </div>
</div>

<!-- Page Content -->
<section class="content">
    <div class="container-fluid">
        <!-- Conteúdo principal aqui -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Card Title</h3>
            </div>
            <div class="card-body">
                <!-- Formulário, tabela, etc -->
            </div>
        </div>
    </div>
</section>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
```

#### **📝 Padrão de Controller (Lógica)**
```php
<?php
// [módulo]_logica.php
require_once __DIR__ . '/../conf/db.php';

// Configuração do módulo
define('MODULO_NOME', '[Nome do Módulo]');
define('MODULO_PERMISSAO', 'perm_[modulo]');

// Conexão com banco
try {
    $pdo = new PDO(
        "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}",
        $config['user'],
        $config['pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]
    );
} catch (PDOException $e) {
    die("Erro de conexão: " . $e->getMessage());
}

// Funções do módulo
function modulo_verificar_permissao() {
    return isset($_SESSION[MODULO_PERMISSAO]) && $_SESSION[MODULO_PERMISSAO] == 1;
}

function modulo_registrar_auditoria($acao, $detalhes = []) {
    global $pdo;

    $sql = "INSERT INTO acesso_seguro_auditoria
            (usuario_id, usuario_nome, ip_address, user_agent, acao, detalhes, sessao_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $_SESSION['user_id'] ?? null,
        $_SESSION['user_nome'] ?? 'system',
        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        $acao,
        json_encode($detalhes),
        session_id()
    ]);
}

function modulo_json_response($success, $message = '', $data = []) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Validação de permissão
if (!modulo_verificar_permissao()) {
    header('Location: /acesso_negado');
    exit;
}
?>
```

### **🎨 Padrões de CSS**

#### **📋 Convenções de Nomenclatura CSS**
```css
/* BEM (Block, Element, Modifier) */
.card { /* Block */ }
.card__header { /* Element */ }
.card__header--primary { /* Modifier */ }

/* Classes utilitárias */
.text-center { text-align: center; }
.text-left { text-align: left; }
.text-right { text-align: right; }
.mb-3 { margin-bottom: 1rem; }
.mt-2 { margin-top: 0.5rem; }
.p-3 { padding: 1rem; }

/* Cores institucionais */
.bg-primary-sigep { background-color: #007bff; }
.bg-secondary-sigep { background-color: #6c757d; }
.text-primary-sigep { color: #007bff; }
.text-secondary-sigep { color: #6c757d; }

/* Responsividade */
@media (max-width: 768px) {
    .hide-mobile { display: none; }
    .mobile-full { width: 100%; }
}
```

#### **📱 Padrões de JavaScript**

#### **📋 Convenções de Nomenclatura JS**
```javascript
// Variáveis e funções
const nomeVariavel = 'valor';
function nomeFuncao() { /* camelCase */ }

// Event handlers
function handleButtonClick(event) {
    event.preventDefault();
    // Lógica aqui
}

// Classes (se usar ES6+)
class NomeClasse {
    constructor() {
        this.propriedade = 'valor';
    }

    metodo() {
        return this.propriedade;
    }
}

// Módulos (ES6)
export default class Modulo {
    static metodoEstatico() {
        return 'valor';
    }
}
```

#### **📝 Padrões de Código JavaScript**
```javascript
// DOM Ready
document.addEventListener('DOMContentLoaded', function() {
    // Inicialização aqui
});

// jQuery (compatível com AdminLTE)
$(document).ready(function() {
    // Inicialização com jQuery
});

// Event delegation (performance)
document.addEventListener('click', function(event) {
    if (event.target.matches('.btn-action')) {
        handleButtonClick(event);
    }
});

// AJAX com jQuery
function ajaxRequest(url, data, callback) {
    $.ajax({
        url: url,
        method: 'POST',
        data: data,
        dataType: 'json',
        beforeSend: function() {
            // Loading state
        },
        success: function(response) {
            callback(response);
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
        }
    });
}

// Validação de formulários
function validateForm(form) {
    const inputs = form.querySelectorAll('input[required]');
    let isValid = true;

    inputs.forEach(input => {
        if (!input.value.trim()) {
            isValid = false;
            input.classList.add('is-invalid');
        } else {
            input.classList.remove('is-invalid');
        }
    });

    return isValid;
}
```

---

## **🔄 9.4 Processo de Desenvolvimento**

### **🌿 Workflow de Git**

#### **📋 Branch Strategy**
```
main (produção)
├── develop (desenvolvimento ativo)
├── feature/nova-funcionalidade (features específicas)
├── hotfix/corrigem-urgente (correções rápidas)
└── release/v1.0.0 (tags de versão)
```

#### **📝 Convenções de Commit**
```
tipo(escopo): descrição

Exemplos:
feat(censura): adicionar validação de correspondência
fix(eclusa): corrigir erro de movimentação
docs(readme): atualizar documentação
style(adminlte): ajustar layout do dashboard
refactor(laboral): otimizar queries de pecúlio
test(auth): adicionar testes de autenticação
chore(deps): atualizar dependências
```

#### **🔄 Pull Request Template**
```markdown
## Descrição
Breve descrição da mudança.

## Tipo de Mudança
- [ ] Bug fix
- [ ] New feature
- [ ] Breaking change
- [ ] Documentation update

## Testes Realizados
- [ ] Testes manuais
- [ ] Testes automatizados
- [ ] Testes de integração

## Checklist
- [ ] Código segue padrões SIGEP
- [ ] Segurança revisada
- [ ] Performance testada
- [ ] Documentação atualizada
- [ ] Logs de auditoria mantidos
```

### **🧪 Testes Automatizados**

#### **📋 Estrutura de Testes**
```
tests/
├── 📁 Unit/
│   ├── 📄 UsuarioTest.php (testes de usuário)
│   ├── 📄 InternoTest.php (testes de internos)
│   └── 📄 MovimentacaoTest.php (testes de movimentação)
├── 📁 Integration/
│   ├── 📄 AuthIntegrationTest.php (testes de autenticação)
│   └── 📄 DatabaseIntegrationTest.php (testes de BD)
├── 📁 Feature/
│   ├── 📄 CensuraCartasTest.php (testes E2E)
│   └── 📄 EclusaMovimentacoesTest.php (testes E2E)
└── 📁 bootstrap.php (configuração de testes)
```

#### **📝 Exemplo de Teste Unitário**
```php
<?php
// tests/Unit/UsuarioTest.php
use PHPUnit\Framework\TestCase;

class UsuarioTest extends TestCase {
    private $pdo;

    protected function setUp(): void {
        $this->pdo = new PDO('sqlite::memory:');
        // Criar estrutura de testes
        $this->pdo->exec("CREATE TABLE usuarios (
            id INTEGER PRIMARY KEY,
            nome VARCHAR(255),
            email VARCHAR(255),
            senha VARCHAR(255),
            setor VARCHAR(100)
        )");
    }

    public function testCriarUsuario() {
        $usuario = new Usuario();
        $usuario->setNome('João Silva');
        $usuario->setEmail('joao.silva@email.com');
        $usuario->setSenha('senha123');
        $usuario->setSetor('Censura');

        $this->assertEquals('João Silva', $usuario->getNome());
        $this->assertEquals('joao.silva@email.com', $usuario->getEmail());
        $this->assertEquals('Censura', $usuario->getSetor());
    }

    public function testValidarEmail() {
        $usuario = new Usuario();

        // Email válido
        $this->assertTrue($usuario->setEmail('valido@email.com'));

        // Email inválido
        $this->assertFalse($usuario->setEmail('email-invalido'));
    }

    public function testHashSenha() {
        $senha = 'senha123';
        $hash = password_hash($senha, PASSWORD_DEFAULT);

        $this->assertTrue(password_verify($senha, $hash));
        $this->assertFalse(password_verify('senha-diferente', $hash));
    }
}
```

#### **🔧 Configuração do PHPUnit**
```xml
<!-- phpunit.xml -->
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="https://schema.phpunit.de/9.3/phpunit.xsd"
         bootstrap="tests/bootstrap.php"
         colors="true">
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Integration">
            <directory>tests/Integration</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory>tests/Feature</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

---

## **🔍 9.5 Code Review e Qualidade**

### **📋 Checklist de Code Review**

#### **🔧 Revisão de Código PHP**
- [ ] **Segurança**: Uso de prepared statements, validação de entrada
- [ ] **Performance**: Queries otimizadas, uso de índices
- [ ] **Padrões**: Segue convenções SIGEP, estrutura MVC
- [ ] **Legibilidade**: Nomes descritivos, comentários adequados
- [ ] **Tratamento de Erros**: Try-catch adequados, logging
- [ ] **Sessões**: Uso correto de sessão e timeout
- [ ] **UTF-8**: Charset configurado corretamente
- [ ] **Dependências**: Composer atualizado, sem vulnerabilidades

#### **🎨 Revisão de Frontend**
- [ ] **AdminLTE**: Uso correto de componentes e classes
- [ ] **Responsividade**: Layout funciona em mobile
- [ ] **Acessibilidade**: ARIA labels, navegação por teclado
- [ ] **Performance**: Lazy loading, cache adequado
- [ ] **Validação**: Client-side e server-side
- [ ] **UX**: Feedback visual adequado, loading states
- [ ] **Cross-browser**: Compatibilidade Chrome, Firefox, Edge
- [ ] **JavaScript**: Sem erros no console, tratamento adequado

#### **🗄️ Revisão de Banco de Dados**
- [ ] **Índices**: Colunas frequentemente filtradas estão indexadas
- [ ] **Relacionamentos**: Foreign keys configuradas corretamente
- [ ] **Performance**: Queries com EXPLAIN analisadas
- [ ] **Segurança**: Privilégios mínimos necessários
- [ ] **Normalização**: 3NF ou superior, sem redundâncias
- [ ] **Charset**: UTF-8 em todas as tabelas
- [ ] **Backup**: Estratégia de backup adequada

### **📊 Métricas de Qualidade**

#### **🔧 Ferramentas de Análise Estática**
```bash
# PHP Code Sniffer (PSR-12)
composer require --dev squizlabs/php_codesniffer
vendor/bin/phpcs --standard=PSR12 --colors src/

# PHP Mess Detector
composer require --dev phpmd/phpmd
vendor/bin/phpmd src/ text cleancode,codesize,controversial,design

# PHP Copy/Paste Detector
composer require --dev phan/phan
vendor/bin/phan --quick src/

# JavaScript ESLint
npm install -g eslint
npx eslint assets/js/

# CSS Stylelint
npm install -g stylelint
npx stylelint assets/css/
```

#### **📈 Cobertura de Código**
```bash
# PHPUnit com coverage
vendor/bin/phpunit --coverage-html coverage/ --coverage-text

# Xdebug para coverage
php -d xdebug.coverage_enable=1 tests/
```

---

## **🚀 9.6 Deployment e Integração Contínua**

### **🔄 Processo de Deploy Atual**

#### **📋 Fluxo de Deploy Manual**
1. **Preparação**
   ```bash
   # Backup do banco
   mysqldump -u sigep -p sigep_producao > backup_$(date +%Y%m%d_%H%M%S).sql

   # Backup dos arquivos
   tar -czf backup_files_$(date +%Y%m%d_%H%M%S).tar.gz htdocs/

   # Parar serviços se necessário
   net stop apache2
   ```

2. **Atualização**
   ```bash
   # Pull do código
   git pull origin main

   # Atualizar dependências
   composer install --no-dev --optimize-autoloader

   # Limpar cache
   rm -rf temp/* cache/*
   ```

3. **Testes**
   ```bash
   # Testes automatizados
   vendor/bin/phpunit

   # Verificar configurações
   php -l

   # Testar conexão com banco
   php -r "echo 'Test OK';"
   ```

4. **Deploy**
   ```bash
   # Reiniciar serviços
   net start apache2

   # Verificar status
   curl -f http://localhost/
   ```

5. **Verificação Pós-Deploy**
   ```bash
   # Health check
   curl -X POST http://localhost/api/health-check

   # Verificar logs
   tail -f /var/log/apache2/error.log

   # Testar funcionalidades críticas
   # Testes manuais rápidos
   ```

### **📋 Script de Deploy Automatizado**
```bash
#!/bin/bash
# deploy.sh - Script de deploy automatizado

set -e

# Configurações
PROJECT_DIR="/var/www/sigep"
BACKUP_DIR="/backup/sigep"
LOG_FILE="/var/log/deploy.log"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# Função de logging
log_message() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a $LOG_FILE
}

# Função de rollback
rollback_deploy() {
    log_message "ERRO: Iniciando rollback..."

    # Restaurar arquivos
    if [ -d "$BACKUP_DIR/files_last" ]; then
        rm -rf $PROJECT_DIR/*
        tar -xzf $BACKUP_DIR/files_last.tar.gz -C $PROJECT_DIR/
    fi

    # Restaurar banco (se necessário)
    if [ -f "$BACKUP_DIR/db_last.sql" ]; then
        mysql -u sigep -p sigep_producao < $BACKUP_DIR/db_last.sql
    fi

    # Reiniciar serviços
    systemctl restart apache2

    log_message "Rollback concluído"
    exit 1
}

# Início do deploy
log_message "Iniciando deploy do SIGEP..."

# 1. Backup pré-deploy
log_message "Criando backup pré-deploy..."
mkdir -p $BACKUP_DIR/$TIMESTAMP

# Backup dos arquivos
tar -czf $BACKUP_DIR/$TIMESTAMP/files.tar.gz -C $PROJECT_DIR .
ln -sf $BACKUP_DIR/$TIMESTAMP/files.tar.gz $BACKUP_DIR/files_last

# Backup do banco
mysqldump -u sigep -p sigep_producao > $BACKUP_DIR/$TIMESTAMP/db.sql
ln -sf $BACKUP_DIR/$TIMESTAMP/db.sql $BACKUP_DIR/db_last

# 2. Atualização do código
log_message "Atualizando código..."
cd $PROJECT_DIR
git fetch origin
git pull origin main

if [ $? -ne 0 ]; then
    log_message "ERRO: Falha no git pull"
    rollback_deploy
fi

# 3. Atualização de dependências
log_message "Atualizando dependências..."
composer install --no-dev --optimize-autoloader

if [ $? -ne 0 ]; then
    log_message "ERRO: Falha no composer install"
    rollback_deploy
fi

# 4. Limpeza de cache
log_message "Limpando cache..."
rm -rf temp/* cache/*

# 5. Verificação de sintaxe
log_message "Verificando sintaxe PHP..."
php -l $(find . -name "*.php" | head -20)

# 6. Testes automatizados
log_message "Executando testes..."
vendor/bin/phpunit

if [ $? -ne 0 ]; then
    log_message "ERRO: Falha nos testes"
    rollback_deploy
fi

# 7. Reinício de serviços
log_message "Reiniciando serviços..."
systemctl reload apache2

# 8. Verificação pós-deploy
log_message "Verificando deploy..."
sleep 5

# Health check
HEALTH_CHECK=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/api/health-check)
if [ "$HEALTH_CHECK" != "200" ]; then
    log_message "ERRO: Health check falhou (HTTP $HEALTH_CHECK)"
    rollback_deploy
fi

# Verificação de funcionalidades críticas
CRITICAL_TESTS=(
    "curl -s http://localhost/autenticacao"
    "curl -s http://localhost/paginas/internos.php"
    "curl -s http://localhost/modulos/censura/cartas/cartas_view.php"
)

for test in "${CRITICAL_TESTS[@]}"; do
    if ! $test > /dev/null; then
        log_message "ERRO: Falha no teste crítico: $test"
        rollback_deploy
    fi
done

# Deploy concluído com sucesso
log_message "Deploy concluído com sucesso!"

# Limpeza de backups antigos (manter últimos 5)
find $BACKUP_DIR -maxdepth 1 -type d -name "20*" | sort -r | tail -n +6 | xargs rm -rf

log_message "Deploy finalizado com sucesso!"
```

---

## **📊 9.7 Monitoramento e Debugging**

### **🔧 Ferramentas de Debugging**

#### **🐛 Xdebug Configuration**
```ini
; php.ini (development)
[xdebug]
xdebug.mode = debug
xdebug.start_with_request = yes
xdebug.client_host = 127.0.0.1
xdebug.client_port = 9003
xdebug.idekey = VSCODE
xdebug.log = /var/log/xdebug.log
xdebug.log_level = 0
xdebug.max_nesting_level = 200
```

#### **🔌 VS Code Debug Configuration**
```json
// .vscode/launch.json
{
    "version": "0.2.0",
    "configurations": [
        {
            "name": "Listen for Xdebug",
            "type": "php",
            "request": "launch",
            "port": 9003,
            "host": "127.0.0.1",
            "pathMappings": {
                "/var/www/sigep": "${workspaceFolder}/htdocs/sigep"
            },
            "xdebugSettings": {
                "max_data": 1024,
                "max_children": 128
            }
        }
    ]
}
```

#### **📊 Logging Estruturado**
```php
// includes/logger.php
class SIGEPLogger {
    private static $loggers = [];

    public static function init() {
        self::$loggers['debug'] = new Logger('debug', 'logs/debug.log');
        self::$loggers['error'] = new Logger('error', 'logs/error.log');
        self::$loggers['audit'] = new Logger('audit', 'logs/audit.log');
    }

    public static function debug($message, $context = []) {
        self::$loggers['debug']->info($message, $context);
    }

    public static function error($message, $context = []) {
        self::$loggers['error']->error($message, $context);
    }

    public static function audit($action, $context = []) {
        $context['user'] = $_SESSION['user_nome'] ?? 'anonymous';
        $context['ip'] = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        self::$loggers['audit']->info($action, $context);
    }
}

// Uso no código
SIGEPLogger::debug('Iniciando processamento', ['step' => 1]);
SIGEPLogger::error('Erro de conexão', ['error' => $e->getMessage()]);
SIGEPLogger::audit('LOGIN_SUCCESS', ['user_id' => $userId]);
```

### **📈 Performance Monitoring**

#### **🔧 Ferramentas de Performance**
```bash
# Blackfire (profiling PHP)
composer require --dev blackfire/blackfire
php -d blackfire=1 script.php

# XHProf (profiling PHP)
pecl install xhprof
php -d xhprof.enable=1 script.php

# MySQL Slow Query Log
# my.cnf
[mysqld]
slow_query_log = /var/log/mysql/slow.log
long_query_time = 2
log_queries_not_using_indexes = 1
```

#### **📊 Métricas de Desenvolvimento**
```php
// includes/metrics.php
class DevelopmentMetrics {
    public static function trackPageLoad($page) {
        $start = microtime(true);

        // Lógica da página

        $end = microtime(true);
        $duration = ($end - $start) * 1000;

        if ($duration > 1000) { // > 1 segundo
            SIGEPLogger::warning('Página lenta detectada', [
                'page' => $page,
                'duration' => $duration,
                'threshold' => 1000
            ]);
        }

        return $duration;
    }

    public static function trackQuery($sql, $duration) {
        if ($duration > 500) { // > 500ms
            SIGEPLogger::warning('Query lenta detectada', [
                'sql' => $sql,
                'duration' => $duration,
                'threshold' => 500
            ]);
        }
    }
}

// Uso no controller
$startTime = microtime(true);
// ... lógica do controller
DevelopmentMetrics::trackPageLoad($_SERVER['REQUEST_URI']);
```

---

## **🔗 Documentação Relacionada**

### **📚 Componentes de Desenvolvimento**
- **[Stack Tecnológico](stack_tecnologico.md)** - Configurações detalhadas
- **[Padrão MVC](../PadrãoMVCSIGEP.md)** - Convenções específicas
- **[Estrutura do Código](index.md)** - Organização completa
- **[Skills de Desenvolvimento](../skills/)** - Ferramentas de validação

### **🛠️ Ferramentas Externas**
- **[PHP Documentation](https://www.php.net/docs/)** - Documentação oficial
- **[MySQL Reference](https://dev.mysql.com/doc/)** - Documentação do banco
- **[AdminLTE Docs](https://adminlte.io/docs/3.1/)** - Documentação do framework
- **[PHPUnit](https://phpunit.de/documentation.html)** - Framework de testes
- **[Composer](https://getcomposer.org/doc/)** - Gerenciamento de dependências

---

## **📋 Checklist de Desenvolvimento**

### **✅ Setup Inicial**
- [x] **Ambiente local** configurado e funcionando
- [x] **Banco de dados** de desenvolvimento criado
- [x] **Dependências** Composer instaladas
- [x] **Ferramentas** IDE configurada com plugins
- [x] **Debugging** Xdebug configurado
- [x] **Versionamento** Git configurado

### **✅ Padrões e Convenções**
- [x] **Estrutura MVC** seguida corretamente
- [x] **Nomenclatura** PHP e JavaScript padronizada
- [x] **CSS** seguindo AdminLTE e convenções SIGEP
- [x] **Segurança** prepared statements implementados
- [x] **UTF-8** charset configurado em todo o projeto

### **✅ Processos Automatizados**
- [x] **Code review** checklist implementada
- [x] **Testes** unitários e de integração
- [x] **Deploy** script automatizado funcionando
- [x] **Monitoramento** logs estruturados ativos
- [x] **Performance** métricas coletadas

### **✅ Qualidade e Manutenção**
- [x] **Análise estática** integrada ao workflow
- [x] **Cobertura de código** configurada
- [x] **Documentação** sempre atualizada com mudanças
- [x] **Dependências** segurança verificada regularmente
- [x] **Backup** automático de código e dados

---

## **🎯 Melhores Práticas**

### **🚀 Desenvolvimento Eficiente**
1. **Sempre começar com testes** antes de implementar
2. **Usar branch develop** para desenvolvimento ativo
3. **Fazer commits pequenos e frequentes** com mensagens claras
4. **Testar em ambiente local** antes do deploy
5. **Manter dependências atualizadas** e seguras
6. **Documentar decisões importantes** e alternativas consideradas

### **🔒 Segurança no Desenvolvimento**
1. **Nunca commitar senhas** ou dados sensíveis
2. **Usar variáveis de ambiente** para configurações
3. **Validar todas as entradas** de usuário
4. **Implementar rate limiting** em APIs
5. **Usar HTTPS** em todos os ambientes
6. **Manter frameworks atualizados** contra vulnerabilidades

### **📈 Performance no Desenvolvimento**
1. **Perfil código lentos** com ferramentas apropriadas
2. **Otimizar queries** com EXPLAIN e índices
3. **Implementar cache** para dados frequentes
4. **Usar lazy loading** para grandes listas
5. **Minimizar requisições** HTTP com cache adequado
6. **Monitorar métricas** de performance em produção

---

**Esta seção fornece um guia completo para o desenvolvimento do SIGEP, desde o setup inicial até as melhores práticas de manutenção e evolução do código.**
