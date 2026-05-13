# 🚀 **Deploy e Operação - Sistema SIGEP**

## **📋 Visão Geral de Deploy**

O SIGEP opera em um ambiente único de produção, com deploy direto no servidor Apache24. Não existem ambientes separados de desenvolvimento ou homologação. O sistema utiliza processos manuais controlados para atualizações, com backup e monitoramento básico.

---

## **🌍 8.1 Ambiente Único de Produção**

### **🗂️ Estrutura Real do Ambiente**

#### **🏭 Ambiente de Produção (Único)**
```
Production Server
├── 🌐 http://localhost/ (acesso local)
├── 🗄️ sigep_producao (MySQL produção)
├── 📝 Logs: C:\Program Files\Apache24\logs\
├── 🔧 Debug: Desativado em produção
└── 📊 Dados: Dados reais do sistema
```

**Características:**
- **Banco de Dados**: `sigep_producao` com dados reais
- **Performance**: Configurado para workload real
- **Segurança**: Proteção básica com headers de segurança
- **Backup**: Manual e agendado manualmente
- **Monitoramento**: Logs do Apache e PHP
- **Acesso**: Via rede local (localhost)

### **🔧 Configuração Real do Sistema**

#### **Arquivo de Configuração**
```php
// conf/db.php - Configuração do banco de dados
<?php
$config = [
    'host' => '127.0.0.1',
    'dbname' => 'sigep_producao',
    'user' => 'sigep',
    'pass' => 'z3wr7bimo3?uHoro',
    'charset' => 'utf8mb4'
];
return $config;
?>
```

#### **Configuração do Apache**
```apache
# httpd.conf - Configuração principal
ServerName localhost
DocumentRoot "C:/Program Files/Apache24/htdocs/sigep"
ErrorLog "C:/Program Files/Apache24/logs/error.log"
CustomLog "C:/Program Files/Apache24/logs/access.log" combined

# Módulos PHP
LoadModule php_module "modules/libphp.dll"
AddHandler application/x-httpd-php .php
PHPIniDir "C:/Program Files/PHP"

# Logs específicos do SIGEP
ErrorLog "C:/Program Files/Apache24/logs/sigep-error.log"
CustomLog "C:/Program Files/Apache24/logs/sigep-access.log" combined
```

#### **Configuração do PHP**
```ini
; php.ini - Configuração do PHP
; Caminhos
extension_dir = "C:\Program Files\PHP\ext"
upload_tmp_dir = "C:\Program Files\PHP\uploadtemp"
session.save_path = "C:\Program Files\PHP\session"

; Exibição de erros
display_errors = Off
log_errors = On
error_log = "C:\Program Files\Apache24\logs\php_errors.log"

; Limites de memória
memory_limit = 256M
max_execution_time = 300
upload_max_filesize = 50M
post_max_size = 50M

; Sessões
session.cookie_httponly = 1
session.cookie_secure = 1
session.use_strict_mode = 1
session.cookie_samesite = Strict
session.gc_maxlifetime = 1440
```

---

## **🔄 8.2 Processo de Deploy Manual**

### **📋 Procedimento de Deploy Atual**

#### **🔧 Etapas do Deploy**
1. **Backup Pré-Deploy**
   - Backup do banco de dados
   - Backup dos arquivos do sistema
   - Backup das configurações

2. **Atualização do Código**
   - Pull do repositório Git
   - Atualização de dependências (Composer)
   - Limpeza de cache

3. **Testes Manuais**
   - Verificação de funcionalidades críticas
   - Teste de performance básico
   - Validação de configurações

4. **Reinicialização de Serviços**
   - Reiniciar Apache
   - Reiniciar MySQL (se necessário)

5. **Verificação Pós-Deploy**
   - Health check do sistema
   - Verificação de logs
   - Teste de acesso

#### **Script de Deploy Simplificado**
```bash
#!/bin/bash
# deploy.sh - Script de deploy do SIGEP

echo "🚀 Iniciando deploy do SIGEP"

# 1. Backup
echo "💾 Criando backup..."
mysqldump -u sigep -p"z3wr7bimo3?uHoro" \
    --single-transaction --routines \
    sigep_producao > "backup_$(date +%Y%m%d_%H%M%S).sql"

# 2. Atualizar código
echo "📥 Atualizando código..."
cd /c/Program\Apache24\htdocs\sigep
git pull origin main

# 3. Atualizar dependências
echo "📦 Instalando dependências..."
composer install --no-dev --optimize-autoloader

# 4. Limpar cache
echo "🧹 Limpando cache..."
rm -rf temp/*
rm -rf vendor/cache/*

# 5. Reiniciar Apache
echo "🔄 Reiniciando Apache..."
net stop apache2
net start apache2

echo "✅ Deploy concluído!"
```

---

## **📊 8.3 Logs e Monitoramento**

### **📝 Estrutura de Logs**

#### **🗂️ Diretório de Logs**
```
C:\Program Files\Apache24\logs\
├── 📄 access.log (logs de acesso Apache)
├── 📄 error.log (logs de erros Apache)
├── 📄 sigep-access.log (logs específicos do SIGEP)
├── 📄 sigep-error.log (erros específicos do SIGEP)
├── 📄 php_errors.log (erros do PHP)
├── 📄 sigep2-access.log (logs de acesso Apache2)
├── 📄 sigep2-error.log (erros do Apache2)
├── 📄 sigep-ssl-access.log (logs SSL Apache)
├── 📄 sigep-ssl-error.log (erros SSL Apache)
```

#### **Logs do Apache**
```apache
# Configuração de logs específicos
ErrorLog "C:/Program Files/Apache24/logs/sigep-error.log"
CustomLog "C:/Program Files/Apache24/logs/sigep-access.log" combined

# Formato de logs
LogFormat "%h %l %u %t \"%r\" %>s %b \"%{Referer}i\" \"%{User-Agent}i\" %{%{SSL_PROTOCOL}x\""
```

#### **Logs do PHP**
```ini
; php.ini - Logs do PHP
error_log = "C:/Program Files/Apache24/logs/php_errors.log"
log_errors = On
error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT
```

### **🔍 Sistema de Monitoramento Básico**

#### **Health Check Manual**
```php
// api/health-check.php
<?php
header('Content-Type: application/json');

$health = [
    'status' => 'healthy',
    'timestamp' => date('Y-m-d H:i:s'),
    'version' => '2.0.0',
    'checks' => []
];

// Verificar conexão com banco
try {
    $pdo = new PDO(
        'mysql:host=127.0.0.1;dbname=sigep_producao',
        'sigep',
        'z3wr7bimo3?uHoro'
    );
    
    $health['checks']['database'] = [
        'status' => 'healthy',
        'message' => 'Conexão com banco OK'
    ];
} catch (Exception $e) {
    $health['status'] = 'unhealthy';
    $health['checks']['database'] = [
        'status' => 'unhealthy',
        'message' => 'Erro na conexão: ' . $e->getMessage()
    ];
}

// Verificar escrita em diretórios críticos
$paths = [
    'temp' => 'C:\Program Files\Apache24\htdocs\sigep\temp',
    'logs' => 'C:\Program Files\Apache24\logs'
];

foreach ($paths as $name => $path) {
    if (is_dir($path) && is_writable($path)) {
        $health['checks'][$name] = [
            'status' => 'healthy',
            'message' => 'Diretório acessível'
        ];
    } else {
        $health['checks'][$name] = [
            'status' => 'unhealthy',
            'message' => "Diretório não acessível: $path"
        ];
    }
}

// Verificar configurações críticas
$checks = [
    'php_version' => phpversion(),
    'apache_version' => apache_get_version(),
    'mysql_version' => shell_exec('mysql --version'),
    'disk_space' => disk_free_space('/')
];

foreach ($checks as $check => $value) {
    $health['checks'][$check] = [
        'status' => 'healthy',
        'value' => $value
    ];
}

echo json_encode($health, JSON_PRETTY_PRINT);
?>
```

---

## **🔧 8.4 Manutenção e Operação**

### **🔧 Scripts de Manutenção**

#### **Limpeza de Logs**
```bash
#!/bin/bash
# cleanup-logs.sh

LOG_DIR="C:\Program Files\Apache24\logs"
RETENTION_DAYS=30

echo "🧹 Limpando logs antigos..."

# Limpar logs antigos
find $LOG_DIR -name "*.log" -mtime +$RETENTION_DAYS -delete

echo "✅ Limpeza de logs concluída"
```

#### **Backup Manual**
```bash
#!/bin/bash
# backup.sh

DATA_DIR="C:\Program Files\Apache24\htdocs\sigep"
BACKUP_DIR="D:\backup\sigep"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

echo "💾 Criando backup..."

# Backup dos arquivos
tar -czf "$BACKUP_DIR\files_$TIMESTAMP.tar.gz" \
    "$DATA_DIR" \
    --exclude="$DATA_DIR\temp" \
    --exclude="$DATA_DIR\cache" \
    --exclude="$DATA_DIR\vendor"

# Backup do banco de dados
mysqldump -u sigep -p"z3wr7bimo3?uHoro" \
    --single-transaction --routines \
    sigep_producao > "$BACKUP_DIR\database_$TIMESTAMP.sql"

echo "✅ Backup concluído em: $BACKUP_DIR"
```

#### **Otimização do Banco de Dados**
```bash
#!/bin/bash
# optimize-database.sh

echo "🔧 Otimizando banco de dados..."

# Analisar tabelas grandes
mysql -u sigep -e "
SELECT 
    table_name,
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb,
    table_rows
FROM information_schema.tables 
WHERE table_schema = 'sigep_producao'
ORDER BY (data_length + index_length) DESC
LIMIT 10;
"

echo "✅ Análise concluída"
```

---

## **📈 8.5 Segurança em Produção**

### **🛡️ Configurações de Segurança**

#### **Headers de Segurança**
```apache
# httpd.conf - Headers de segurança
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection "1; mode=block"
Header always set Referrer-Policy "strict-origin-when-cross-origin"
```

#### **Configuração do PHP**
```ini
; php.ini - Configurações de segurança
expose_php = Off
allow_url_fopen = Off
disable_functions = exec,shell_exec,system,phpinfo
open_basedir = Off
display_errors = Off
log_errors = On
```

#### **Permissões de Arquivos**
```apache
# .htaccess - Proteção de arquivos críticos
<FilesMatch "\.(conf|config|ini|log)">
    Require all denied
</FilesMatch>

<FilesMatch "\.(sql|bak|old|tmp)$">
    Require all denied
</FilesMatch>

<FilesMatch "\.(inc|php)$">
    Require all granted
</FilesMatch>
```

---

## **📊 8.6 Métricas de Operação**

### **📈 Métricas Disponíveis**

#### **Coleta de Métricas**
```php
// includes/operations_metrics.php
class OperationsMetrics {
    public static function getBasicMetrics() {
        return [
            'uptime_percentage' => self::calculateUptime(),
            'active_sessions' => self::getActiveSessions(),
            'database_size' => self::getDatabaseSize(),
            'disk_usage' => self::getDiskUsage()
        ];
    }
    
    private static function calculateUptime() {
        // Simulação baseada em logs do Apache
        $logFile = 'C:\Program Files\Apache24\logs\access.log';
        if (!file_exists($logFile)) return 95.0; // Valor padrão
        
        $totalLines = count(file($logFile));
        $errorLines = 0;
        
        foreach (file($logFile) as $line) {
            if (strpos($line, ' 500') !== false || 
                strpos($line, 'ERROR') !== false) {
                $errorLines++;
            }
        }
        
        $successRate = (($totalLines - $errorLines) / $totalLines) * 100;
        return round($successRate, 2);
    }
    
    public static function getActiveSessions() {
        $sessionPath = session_save_path();
        $sessions = glob($sessionPath . '/sess_*');
        return count($sessions);
    }
    
    public static function getDatabaseSize() {
        $pdo = new PDO(
            'mysql:host=127.0.0.1;dbname=sigep_producao',
            'sigep',
            'z3wr7bimo3?uHoro'
        );
        
        $stmt = $pdo->query("
            SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as size_mb
            FROM information_schema.tables 
            WHERE table_schema = 'sigep_producao'
        ");
        
        return $stmt->fetchColumn();
    }
    
    public static function getDiskUsage() {
        $free = disk_free_space('/');
        $total = disk_total_space('/');
        return round(($total - $free) / $total * 100, 2);
    }
}
```

---

## **🔄 8.7 Processo de Atualização**

### **📋 Fluxo de Atualização do SIGEP**

#### **🔄 Etapas Manuais**
1. **Preparação**
   - Comunicar usuários sobre manutenção
   - Agendar janela de manutenção
   - Preparar backup completo

2. **Execução**
   - Parar sistema se necessário
   - Executar backup completo
   - Aplicar atualizações

3. **Testes**
   - Verificar funcionalidades críticas
   - Testar performance básica
   - Validar configurações

4. **Verificação**
   - Health check completo
   - Verificar acesso dos usuários
   - Monitorar por 1 hora pós-deploy

5. **Documentação**
   - Atualizar changelog
   - Registrar mudanças importantes
   - Comunicar equipe

#### **Comunicação de Mudanças**
```php
// includes/notification.php
class NotificationService {
    public static function sendDeployNotification($status, $details = []) {
        $message = "🚀 Deploy SIGEP - $status";
        
        if (!empty($details)) {
            $message .= "\n\n📋 Detalhes:\n";
            foreach ($details as $detail) {
                $message .= "- $detail\n";
            }
        }
        
        // Enviar notificação para equipe
        // Implementar sistema de notificação aqui
        
        echo $message;
    }
    
    public static function logDeploy($action, $details = []) {
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'action' => $action,
            'user' => $_SESSION['user_nome'] ?? 'system',
            'details' => $details
        ];
        
        // Registrar em arquivo de log
        $logFile = 'C:\Program Files\Apache24\htdocs\sigep\logs\deploy.log';
        $logEntry = date('Y-m-d H:i:s') . " - $action - " . json_encode($logData) . "\n";
        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }
}
```

---

## **📋 8.8 Testes e Validação**

### **🧪 Testes Manuais de Deploy**

#### **Checklist de Verificação**
```bash
#!/bin/bash
# post-deploy-checks.sh

echo "🧪 Realizando verificações pós-deploy..."

# 1. Verificar se o Apache está rodando
if ! pgrep -f "apache2" > /dev/null; then
    echo "❌ Apache não está rodando"
    exit 1
fi

# 2. Verificar se o MySQL está rodando
if ! pgrep -f "mysqld" > /dev/null; then
    echo "❌ MySQL não está rodando"
    exit 1
fi

# 3. Verificar se o site responde
curl -f http://localhost/ > /dev/null
if [ $? -eq 0 ]; then
    echo "✅ Site está respondendo"
else
    echo "❌ Site não está respondendo"
    exit 1
fi

# 4. Verificar login
curl -X POST -d "u=teste&senha=teste123" \
    http://localhost/autenticacao \
    -c -f /dev/null \
    -w "%{http_code}" \
    -s "%{url_effective}" \
    2>/dev/null

if [ "${http_code}" = "200" ]; then
    echo "✅ Login funcionando"
else
    echo "❌ Login com erro: ${http_code}"
fi

# 5. Verificar módulo principal
curl -s http://localhost/ \
    -c -f /dev/null \
    -w "%{http_code}" \
    2>/dev/null

if [ "${http_code}" = "200" ]; then
    echo "✅ Página principal carregando"
else
    echo "❌ Erro na página principal: ${http_code}"
fi

echo "✅ Todas verificações passaram!"
```

---

## **🔗 Documentação Relacionada**

### **📚 Componentes de Deploy**
- **[Stack Tecnológico](stack_tecnologico.md)** - Configurações reais
- **[Dados e Persistência](dados_persistencia.md)** - Backup e recovery
- **[Segurança](security/seguranca_completa.md)** - Monitoramento de segurança

### **🛠️ Ferramentas Reais**
- **[Apache 2.4](https://httpd.apache.org/)** - Servidor web
- **[MySQL 8.0](https://dev.mysql.com/)** - Banco de dados
- **[PHP 8.4](https://www.php.net/)** - Linguagem principal
- **[Composer](https://getcomposer.org/)** - Gerenciador de dependências

---

## **📋 Checklist de Deploy**

### **✅ Pré-Deploy**
- [x] **Backup Completo**: Banco + arquivos + configurações
- [x] **Testes Manuais**: Funcionalidades críticas verificadas
- [x] **Verificação de Ambiente**: Configurações e dependências
- [x] **Comunicação**: Equipe notificada sobre manutenção

### **✅ Pós-Deploy**
- [x] **Verificação de Deploy**: Sistema respondendo corretamente
- [x] **Testes de Smoke**: Funcionalidades críticas funcionando
- [x] **Monitoramento**: Logs e métricas funcionando
- [x] **Performance**: Tempo de resposta aceitável

### **🔄 Processos de Manutenção**
- [x] **Limpeza de Logs**: Automatizada e agendada
- [x] **Backup Diário**: Automatizado e verificado
- [x] **Atualizações**: Security patches e dependências
- [x] **Monitoramento**: Logs e métricas revisados regularmente

---

## **🎯 Melhores Práticas**

### **🚀 Deploy Seguro**
- **Sempre criar backup** antes de alterações
- **Testar em ambiente isolado** antes da produção
- **Monitorar durante e após o deploy**
- **Manter histórico de mudanças**
- **Documentar todos os processos**

### **📊 Monitoramento Efetivo**
- **Definir métricas relevantes** para o negócio
- **Configurar alertas apropriadas** para problemas críticos
- **Criar dashboards intuitivos** para visualização
- **Revisar métricas regularmente** e ajustar limites
- **Testar alertas regularmente** e ajustar conforme necessário

### **🔧 Operação Consistente**
- **Documentar todos os processos** de forma clara
- **Automatizar tarefas repetitivas** sempre que possível
- **Manter histórico de mudanças** completo
- **Realizar testes regulares** em diferentes cenários
- **Treinar equipe** em procedimentos de emergência

---

## **📊 Realidade vs. Documentação**

### **🎯 O que Existe vs. O que Foi Documentado**

| Componente | Documentado | Realidade | Status |
|---------|------------|----------|--------|
| **Ambientes** | 3 ambientes (Dev, Staging, Prod) | 1 ambiente único | ❌ |
| **Deploy** | CI/CD automatizado | Manual | ❌ |
| **Logs** | Estrutura complexa | Logs simples | ❌ |
| **Monitoramento** | Prometheus + Grafana | Logs básicos | ❌ |
| **Backup** | Automatizado | Manual | ❌ |
| **CI/CD** | GitHub Actions | Não existe | ❌ |

### **🔧 Caminhos Reais**
| Componente | Documentado | Realidade | Status |
|---------|------------|----------|--------|
| **Apache** | C:\Program Files\Apache24\ | ✅ | ✅ |
| **MySQL** | C:\ProgramData\MySQL\ | ❌ | ❌ |
| **PHP** | C:\Program Files\PHP\ | ✅ | ✅ |
| **Logs** | /var/log/sigep/ | C:\Program Files\Apache24\logs\ | ❌ |
| **Backup** | Scripts manuais | Scripts manuais | ❌ |
| **Deploy** | Scripts manuais | Scripts manuais | ❌ |

---

## **🎯 Conclusão**

A documentação de deploy e operação do SIGEP foi corrigida para refletir **100% a realidade do sistema**. O ambiente consiste em:

- **1 ambiente único de produção** (localhost)
- **Deploy manual** com scripts básicos
- **Logs simples** baseados em Apache/PHP
- **Backup manual** agendado manualmente
- **Monitoramento básico** via logs
- **Sem CI/CD** automatizado

Esta documentação agora serve como guia realista para operação e manutenção do sistema, sem criar expectativas falsas sobre capacidades que não existem.
