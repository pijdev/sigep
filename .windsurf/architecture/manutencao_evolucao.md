# 🔧 **Manutenção e Evolução - Sistema SIGEP**

## **📋 Visão Geral de Manutenção**

A manutenção e evolução do SIGEP envolve processos estruturados para garantir a continuidade do serviço, documentação adequada, evolução planejada e suporte eficiente aos usuários. Esta seção documenta todas as práticas e procedimentos para manter o sistema robusto e em constante melhoria.

---

## **🔧 10.1 Documentação de APIs**

### **🌐 Arquitetura de APIs do SIGEP**

#### **📋 Estrutura de Endpoints**
```
Base URL: http://localhost/api/

├── 🔐 Autenticação
│   ├── POST /api/auth/login
│   ├── POST /api/auth/logout
│   ├── POST /api/auth/refresh
│   └── GET  /api/auth/session
│
├── 👤 Usuários
│   ├── GET  /api/usuarios
│   ├── GET  /api/usuarios/{id}
│   ├── POST /api/usuarios
│   ├── PUT  /api/usuarios/{id}
│   └── DELETE /api/usuarios/{id}
│
├── 👥 Internos
│   ├── GET  /api/internos
│   ├── GET  /api/internos/{id}
│   ├── POST /api/internos
│   ├── PUT  /api/internos/{id}
│   └── DELETE /api/internos/{id}
│
├── 📧 Censura
│   ├── GET  /api/censura/cartas
│   ├── POST /api/censura/cartas
│   ├── PUT  /api/censura/cartas/{id}
│   └── DELETE /api/censura/cartas/{id}
│
├── 🚛 Eclusa
│   ├── GET  /api/eclusa/movimentacoes
│   ├── POST /api/eclusa/movimentacoes
│   ├── PUT  /api/eclusa/movimentacoes/{id}
│   └── DELETE /api/eclusa/movimentacoes/{id}
│
├── 💰 Laboral
│   ├── GET  /api/laboral/peculio
│   ├── POST /api/laboral/peculio
│   ├── GET  /api/laboral/salarios
│   └── GET  /api/laboral/descontos
│
└── 📊 Relatórios
    ├── GET  /api/relatorios/gerais
    ├── GET  /api/relatorios/movimentacoes
    ├── GET  /api/relatorios/peculio
    └── GET  /api/relatorios/censura
```

#### **📝 Formato de Resposta Padrão**
```json
{
    "success": true,
    "message": "Operação realizada com sucesso",
    "data": {
        // Dados da resposta
    },
    "errors": [],
    "meta": {
        "timestamp": "2024-03-16T14:30:00Z",
        "version": "2.0.0",
        "pagination": {
            "current_page": 1,
            "per_page": 20,
            "total": 150,
            "last_page": 8
        }
    }
}
```

#### **🔐 Autenticação de APIs**
```php
<?php
// api/auth/login.php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validação de entrada
    if (!isset($input['usuario']) || !isset($input['senha'])) {
        throw new InvalidArgumentException('Usuário e senha são obrigatórios');
    }
    
    // Autenticação
    require_once __DIR__ . '/../includes/session_auth.php';
    require_once __DIR__ . '/../conf/db.php';
    
    $pdo = new PDO(
        "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}",
        $config['user'],
        $config['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    $stmt = $pdo->prepare("SELECT id, nome, setor, is_admin FROM acesso_seguro WHERE usuario = ? AND status = 'Ativo'");
    $stmt->execute([$input['usuario']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user || !password_verify($input['senha'], $user['senha_hash'])) {
        throw new Exception('Credenciais inválidas');
    }
    
    // Aplicar rate limiting
    require_once __DIR__ . '/../auth/security_functions.php';
    if (!verificarRateLimit($pdo, $input['usuario'])) {
        throw new Exception('Muitas tentativas de login. Tente novamente em 5 minutos.');
    }
    
    // Criar sessão
    session_start();
    sigep_apply_user_session($user);
    
    // Resetar rate limiting
    resetarRateLimit($pdo, $input['usuario']);
    
    // Registrar auditoria
    require_once __DIR__ . '/../auth/security_functions.php';
    registrarAuditoria($pdo, 'login_success', $user['id'], $user['nome'], [
        'ip_address' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT']
    ]);
    
    // Gerar token para APIs
    $token = bin2hex(random_bytes(32));
    $_SESSION['api_token'] = $token;
    
    echo json_encode([
        'success' => true,
        'message' => 'Login realizado com sucesso',
        'data' => [
            'user_id' => $user['id'],
            'user_nome' => $user['nome'],
            'user_setor' => $user['setor'],
            'is_admin' => (bool)$user['is_admin'],
            'api_token' => $token,
            'permissions' => [
                'perm_censura' => $user['perm_censura'],
                'perm_almoxarifado' => $user['perm_almoxarifado'],
                'perm_laboral' => $user['perm_laboral'],
                // ... outras permissões
            ]
        ]
    ],
        'meta' => [
            'timestamp' => date('c'),
            'version' => '2.0.0'
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'errors' => [$e->getMessage()]
    ]);
}
?>
```

#### **📋 Exemplo de CRUD API**
```php
<?php
// api/internos.php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/../auth/security_functions.php';

// Middleware de autenticação
function authenticate_api() {
    $headers = getallheaders();
    if (!isset($headers['Authorization'])) {
        throw new Exception('Token de autenticação não fornecido');
    }
    
    $token = str_replace('Bearer ', '', $headers['Authorization']);
    
    if (!isset($_SESSION['api_token']) || $_SESSION['api_token'] !== $token) {
        throw new Exception('Token de autenticação inválido');
    }
}

try {
    authenticate_api();
    
    require_once __DIR__ . '/../conf/db.php';
    $pdo = new PDO(
        "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}",
        $config['user'],
        $config['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    $method = $_SERVER['REQUEST_METHOD'];
    $id = $_GET['id'] ?? null;
    
    switch ($method) {
        case 'GET':
            if ($id) {
                // Obter interno específico
                $stmt = $pdo->prepare("SELECT * FROM internos WHERE ipen = ?");
                $stmt->execute([$id]);
                $interno = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$interno) {
                    throw new Exception('Interno não encontrado');
                }
                
                echo json_encode([
                    'success' => true,
                    'data' => $interno
                ]);
            } else {
                // Listar internos com paginação
                $page = max(1, intval($_GET['page'] ?? 1));
                $limit = max(1, intval($_GET['limit'] ?? 20));
                $offset = ($page - 1) * $limit;
                
                $stmt = $pdo->prepare("SELECT SQL_CALC_FOUND_ROWS * FROM internos ORDER BY nome LIMIT ? OFFSET ?");
                $stmt->execute([$limit, $offset]);
                $internos = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Total de registros
                $totalStmt = $pdo->query("SELECT FOUND_ROWS()");
                $total = $totalStmt->fetchColumn();
                
                echo json_encode([
                    'success' => true,
                    'data' => $internos,
                    'meta' => [
                        'pagination' => [
                            'current_page' => $page,
                            'per_page' => $limit,
                            'total' => $total,
                            'last_page' => ceil($total / $limit)
                        ]
                    ]
                ]);
            }
            break;
            
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Validação
            if (!isset($input['nome']) || !isset($input['cpf'])) {
                throw new Exception('Nome e CPF são obrigatórios');
            }
            
            // Inserir novo interno
            $stmt = $pdo->prepare("INSERT INTO internos (nome, cpf, data_cadastro) VALUES (?, ?, ?)");
            $stmt->execute([
                $input['nome'],
                $input['cpf'],
                date('Y-m-d H:i:s')
            ]);
            
            $id = $pdo->lastInsertId();
            
            // Registrar auditoria
            registrarAuditoria($pdo, 'interno_criado', $_SESSION['user_id'], $_SESSION['user_nome'], [
                'interno_id' => $id,
                'interno_nome' => $input['nome']
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Interno criado com sucesso',
                'data' => ['id' => $id]
            ]);
            break;
            
        case 'PUT':
            if (!$id) {
                throw new Exception('ID do interno é obrigatório para atualização');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Atualizar interno
            $stmt = $pdo->prepare("UPDATE internos SET nome = ?, cpf = ? WHERE ipen = ?");
            $stmt->execute([
                $input['nome'],
                $input['cpf'],
                $id
            ]);
            
            // Registrar auditoria
            registrarAuditoria($pdo, 'interno_atualizado', $_SESSION['user_id'], $_SESSION['user_nome'], [
                'interno_id' => $id,
                'interno_nome' => $input['nome']
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Interno atualizado com sucesso'
            ]);
            break;
            
        case 'DELETE':
            if (!$id) {
                throw new Exception('ID do interno é obrigatório para exclusão');
            }
            
            // Soft delete (marcar como inativo)
            $stmt = $pdo->prepare("UPDATE internos SET status = 'I', data_inativo = NOW() WHERE ipen = ?");
            $stmt->execute([$id]);
            
            // Registrar auditoria
            registrarAuditoria($pdo, 'interno_excluido', $_SESSION['user_id'], $_SESSION['user_nome'], [
                'interno_id' => $id
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Interno excluído com sucesso'
            ]);
            break;
            
        default:
            throw new Exception('Método não permitido');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'errors' => [$e->getMessage()]
    ]);
}
?>
```

---

## **🔍 10.2 Troubleshooting**

### **📋 Sistema de Diagnóstico de Problemas**

#### **🔧 Ferramentas de Diagnóstico**
```php
<?php
// includes/diagnostic.php
class SIGEPDiagnostic {
    private $issues = [];
    
    public function runFullDiagnostic() {
        $this->checkDatabaseConnection();
        $this->checkFilePermissions();
        $this->checkPhpConfiguration();
        $this->checkApacheConfiguration();
        $this->checkMemoryUsage();
        $this->checkDiskSpace();
        $this->checkServicesStatus();
        
        return [
            'status' => empty($this->issues) ? 'healthy' : 'warning',
            'issues' => $this->issues,
            'recommendations' => $this->getRecommendations()
        ];
    }
    
    private function checkDatabaseConnection() {
        try {
            require_once __DIR__ . '/../conf/db.php';
            $pdo = new PDO(
                "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}",
                $config['user'],
                $config['pass'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            
            // Testar query simples
            $stmt = $pdo->query("SELECT 1");
            $stmt->fetch();
            
        } catch (PDOException $e) {
            $this->issues[] = [
                'type' => 'database',
                'severity' => 'critical',
                'message' => 'Erro de conexão com banco de dados: ' . $e->getMessage(),
                'solution' => 'Verificar configurações do MySQL e credenciais'
            ];
        }
    }
    
    private function checkFilePermissions() {
        $criticalPaths = [
            '../conf/db.php',
            '../temp/',
            '../logs/',
            '../uploads/'
        ];
        
        foreach ($criticalPaths as $path) {
            if (!file_exists($path)) {
                $this->issues[] = [
                    'type' => 'file_system',
                    'severity' => 'warning',
                    'message' => "Diretório não existe: $path",
                    'solution' => 'Criar diretório necessário'
                ];
            } elseif (!is_writable($path)) {
                $this->issues[] = [
                    'type' => 'file_system',
                    'severity' => 'critical',
                    'message' => "Diretório sem permissão de escrita: $path",
                    'solution' => 'Ajustar permissões (chmod 755)'
                ];
            }
        }
    }
    
    private function checkPhpConfiguration() {
        $requiredSettings = [
            'display_errors' => 'Off',
            'log_errors' => 'On',
            'max_execution_time' => '>=30',
            'memory_limit' => '>=128M',
            'upload_max_filesize' => '>=10M'
        ];
        
        foreach ($requiredSettings as $setting => $expected) {
            $actual = ini_get($setting);
            
            if (strpos($expected, '>=') !== false) {
                $expectedValue = (int)str_replace('>=', '', $expected);
                $actualValue = $this->parseMemoryLimit($actual);
                
                if ($actualValue < $expectedValue) {
                    $this->issues[] = [
                        'type' => 'php_config',
                        'severity' => 'warning',
                        'message' => "Configuração PHP inadequada: $setting = $actual",
                        'solution' => "Ajustar $setting para $expected no php.ini"
                    ];
                }
            } elseif ($actual !== $expected) {
                $this->issues[] = [
                    'type' => 'php_config',
                    'severity' => 'warning',
                    'message' => "Configuração PHP incorreta: $setting = $actual (esperado: $expected)",
                        'solution' => "Ajustar $setting para $expected no php.ini"
                    ];
                }
            }
        }
    }
    
    private function parseMemoryLimit($limit) {
        $limit = strtoupper($limit);
        $value = (int)$limit;
        
        if (strpos($limit, 'G') !== false) {
            $value *= 1024 * 1024 * 1024;
        } elseif (strpos($limit, 'M') !== false) {
            $value *= 1024 * 1024;
        }
        
        return $value;
    }
    
    private function checkMemoryUsage() {
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = $this->parseMemoryLimit(ini_get('memory_limit'));
        
        $usagePercent = ($memoryUsage / $memoryLimit) * 100;
        
        if ($usagePercent > 80) {
            $this->issues[] = [
                'type' => 'performance',
                'severity' => 'warning',
                'message' => "Uso de memória elevado: " . round($usagePercent, 2) . "%",
                'solution' => 'Otimizar código ou aumentar memory_limit'
            ];
        }
    }
    
    private function checkDiskSpace() {
        $freeSpace = disk_free_space('/');
        $totalSpace = disk_total_space('/');
        $usedPercent = (($totalSpace - $freeSpace) / $totalSpace) * 100;
        
        if ($usedPercent > 85) {
            $this->issues[] = [
                'type' => 'disk_space',
                'severity' => 'critical',
                'message' => "Espaço em disco baixo: " . round($usedPercent, 2) . "%",
                'solution' => 'Limpar logs antigos ou aumentar espaço em disco'
            ];
        }
    }
    
    private function getRecommendations() {
        $recommendations = [];
        
        foreach ($this->issues as $issue) {
            if ($issue['severity'] === 'critical') {
                $recommendations[] = "URGENTE: " . $issue['solution'];
            }
        }
        
        if ($issue['type'] === 'performance') {
            $recommendations[] = "PERFORMANCE: " . $issue['solution'];
        }
        }
        
        return array_unique($recommendations);
    }
}

// Endpoint de diagnóstico
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $_GET['diagnostic'] === 'true') {
    header('Content-Type: application/json; charset=utf-8');
    
    $diagnostic = new SIGEPDiagnostic();
    $result = $diagnostic->runFullDiagnostic();
    
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
?>
```

#### **📋 Problemas Comuns e Soluções**

| Problema | Sintoma | Causa Provável | Solução |
|---------|----------|---------------|----------|
| **Login não funciona** | Usuário/senha incorretos | Rate limiting ativado | Aguardar 5 minutos ou verificar credenciais |
| **Página branca** | Erro PHP 500 | Erro de sintaxe ou conexão BD | Verificar logs de erro e debug |
| **Conexão BD falha** | Access denied | Credenciais incorretas | Verificar usuário/senha e permissões |
| **Upload de arquivo falha** | Arquivo muito grande | upload_max_filesize | Aumentar limite ou otimizar |
| **Consulta lenta** | Timeout 30s | Query sem índice | Adicionar índice ou otimizar query |
| **Sessão expira** | Logout automático | session.gc_maxlifetime | Aumentar timeout ou renovar sessão |
| **CSS não carrega** | Layout quebrado | Path incorreto | Verificar caminho do arquivo CSS |
| **JavaScript não funciona** | Erro no console | Sintaxe JS | Verificar console e debug |
| **Relatório vazio** | Nenhum registro | Filtro incorreto | Verificar filtros e período |
| **Permissão negada** | Acesso bloqueado | Falta de permissão | Verificar permissões do setor |

---

## **📈 10.3 Monitoramento e Métricas**

### **📊 Sistema de Monitoramento**

#### **🔧 Métricas de Performance**
```php
<?php
// includes/metrics_collector.php
class SIGEPMetricsCollector {
    public function collectSystemMetrics() {
        return [
            'timestamp' => date('Y-m-d H:i:s'),
            'system' => $this->getSystemMetrics(),
            'application' => $this->getApplicationMetrics(),
            'database' => $this->getDatabaseMetrics(),
            'users' => $this->getUserMetrics()
        ];
    }
    
    private function getSystemMetrics() {
        return [
            'cpu_usage' => sys_getloadavg()[0],
            'memory_usage' => $this->getMemoryUsage(),
            'disk_usage' => $this->getDiskUsage(),
            'network_connections' => $this->getNetworkConnections(),
            'uptime' => $this->getUptime()
        ];
    }
    
    private function getApplicationMetrics() {
        return [
            'active_sessions' => $this->getActiveSessions(),
            'php_errors_24h' => $this->getRecentErrors(),
            'slow_queries_24h' => $this->getSlowQueries(),
            'api_requests_24h' => $this->getApiRequests(),
            'response_time_avg' => $this->getAverageResponseTime()
        ];
    }
    
    private function getDatabaseMetrics() {
        try {
            require_once __DIR__ . '/../conf/db.php';
            $pdo = new PDO(
                "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}",
                $config['user'],
                $config['pass'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            
            $stmt = $pdo->query("SHOW STATUS LIKE 'Connections'");
            $connections = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $stmt = $pdo->query("SHOW STATUS LIKE 'Slow_queries'");
            $slowQueries = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $stmt = $pdo->query("SELECT table_schema, ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb 
                     FROM information_schema.tables 
                     WHERE table_schema = '{$config['dbname']}'");
            $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'connections' => $connections['Value'] ?? 0,
                'slow_queries' => $slowQueries['Value'] ?? 0,
                'database_size_mb' => array_sum(array_column($tables, 'size_mb')),
                'table_count' => count($tables)
            ];
            
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    private function getUserMetrics() {
        try {
            require_once __DIR__ . '/../conf/db.php';
            $pdo = new PDO(
                "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}",
                $config['user'],
                $config['pass'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM acesso_seguro WHERE status = 'Ativo'");
            $activeUsers = $stmt->fetchColumn();
            
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM internos WHERE status = 'A'");
            $activeInternos = $stmt->fetchColumn();
            
            return [
                'active_users' => $activeUsers,
                'active_internos' => $activeInternos,
                'total_users' => $activeUsers + $activeInternos
            ];
            
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    private function getActiveSessions() {
        $sessionPath = session_save_path();
        $sessions = glob($sessionPath . '/sess_*');
        return count($sessions);
    }
    
    private function getRecentErrors() {
        $errorLog = '/var/log/apache2/error.log';
        if (!file_exists($errorLog)) {
            return 0;
        }
        
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $command = "grep '$yesterday' $errorLog | wc -l";
        $count = (int)shell_exec($command);
        
        return $count;
    }
    
    private function getSlowQueries() {
        try {
            require_once __DIR__ . '/../conf/db.php';
            $pdo = new PDO(
                "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}",
                $config['user'],
                $config['pass'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            
            $yesterday = date('Y-m-d H:i:s', strtotime('-1 day'));
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM mysql.slow_log WHERE start_time >= ?");
            $stmt->execute([$yesterday]);
            
            return $stmt->fetchColumn();
            
        } catch (Exception $e) {
            return 0;
        }
    }
}

// Endpoint de métricas
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $_GET['metrics'] === 'true') {
    header('Content-Type: application/json; charset=utf-8');
    
    $collector = new SIGEPMetricsCollector();
    $metrics = $collector->collectSystemMetrics();
    
    echo json_encode($metrics, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
?>
```

#### **📊 Dashboard de Monitoramento**
```html
<!-- dashboard_monitoramento.html -->
<div class="content">
    <div class="container-fluid">
        <div class="row">
            <!-- Métricas do Sistema -->
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3 id="cpu-usage">0%</h3>
                        <p>Uso CPU</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-microchip"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3 id="memory-usage">0%</h3>
                        <p>Uso Memória</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-memory"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3 id="disk-usage">0%</h3>
                        <p>Uso Disco</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-hdd"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3 id="active-users">0</h3>
                        <p>Usuários Ativos</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Métricas da Aplicação -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Performance da Aplicação</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-sm-6">
                                <div class="info-box">
                                    <span class="info-box-text">Sessões Ativas</span>
                                    <span class="info-box-number" id="active-sessions">0</span>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="info-box">
                                    <span class="info-box-text">Erros PHP (24h)</span>
                                    <span class="info-box-number" id="php-errors">0</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-sm-6">
                                <div class="info-box">
                                    <span class="info-box-text">Queries Lentas</span>
                                    <span class="info-box-number" id="slow-queries">0</span>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="info-box">
                                    <span class="info-box-text">Tempo Médio Resposta</span>
                                    <span class="info-box-number" id="avg-response-time">0ms</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Métricas do Banco de Dados</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-sm-4">
                                <div class="info-box">
                                    <span class="info-box-text">Conexões</span>
                                    <span class="info-box-number" id="db-connections">0</span>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="info-box">
                                    <span class="info-box-text">Tamanho BD</span>
                                    <span class="info-box-number" id="db-size">0MB</span>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="info-box">
                                    <span class="info-box-text">Tabelas</span>
                                    <span class="info-box-number" id="table-count">0</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Logs em Tempo Real -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Logs em Tempo Real</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool btn-sm" onclick="refreshLogs()">
                                <i class="fas fa-sync"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="logs-container" style="height: 400px; overflow-y: auto; font-family: monospace; font-size: 12px; background: #000; color: #0f0; padding: 10px;">
                            <!-- Logs serão carregados aqui via AJAX -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Atualizar métricas a cada 30 segundos
setInterval(updateMetrics, 30000);

function updateMetrics() {
    fetch('/api/metrics?metrics=true')
        .then(response => response.json())
        .then(data => {
            // Atualizar métricas do sistema
            document.getElementById('cpu-usage').textContent = data.system.cpu_usage + '%';
            document.getElementById('memory-usage').textContent = data.system.memory_usage + '%';
            document.getElementById('disk-usage').textContent = data.system.disk_usage + '%';
            document.getElementById('active-users').textContent = data.users.total_users;
            
            // Atualizar métricas da aplicação
            document.getElementById('active-sessions').textContent = data.application.active_sessions;
            document.getElementById('php-errors').textContent = data.application.php_errors_24h;
            document.getElementById('slow-queries').textContent = data.application.slow_queries_24h;
            document.getElementById('avg-response-time').textContent = data.application.response_time_avg + 'ms';
            
            // Atualizar métricas do banco
            document.getElementById('db-connections').textContent = data.database.connections;
            document.getElementById('db-size').textContent = data.database.database_size_mb + 'MB';
            document.getElementById('table-count').textContent = data.database.table_count;
        })
        .catch(error => console.error('Erro ao atualizar métricas:', error));
}

function refreshLogs() {
    fetch('/api/logs?tail=true')
        .then(response => response.text())
        .then(logs => {
            document.getElementById('logs-container').textContent = logs;
        })
        .catch(error => console.error('Erro ao carregar logs:', error));
}

// Carregar métricas iniciais
updateMetrics();
</script>
```

---

## **🚀 10.4 Roadmap e Evolução**

### **📋 Visão Estratégica de Evolução**

#### **🎯 Objetivos de Longo Prazo (1-2 anos)**
1. **Modernização da Interface**
   - Migração para AdminLTE 4
   - Implementação de design system
   - Melhoria da acessibilidade (WCAG 2.1 AA)
   - Otimização para mobile-first

2. **Arquitetura de Microserviços**
   - Separação de frontend e backend
   - Implementação de APIs RESTful
   - Containerização com Docker
   - Autenticação via JWT

3. **Inteligência Artificial**
   - Chatbot para suporte ao usuário
   - Análise preditiva de movimentações
   - Classificação automática de correspondências
   - Otimização de rotas de eclusa

4. **Integrações Avançadas**
   - Sistema de biometria
   - Integração com sistemas externos
   - API para parceiros
   - Webhooks para eventos automáticos

#### **📅 Médio Prazo (6-12 meses)**
1. **Performance e Escalabilidade**
   - Implementação de cache Redis
   - Otimização de queries complexas
   - Paginação infinita (scroll)
   - CDN para assets estáticos

2. **Segurança Avançada**
   - Autenticação de dois fatores
   - Rate limiting por usuário
   - Criptografia de dados sensíveis
   - Auditoria avançada com machine learning

3. **Experiência do Usuário**
   - Personalização de dashboard
   - Notificações push
   - Offline mode funcional
   - Temas customizáveis

4. **Ferramentas de Desenvolvimento**
   - CI/CD completo com GitHub Actions
   - Testes automatizados em pipeline
   - Code review automatizado
   - Deploy blue-green

#### **🔓 Curto Prazo (1-3 meses)**
1. **Correções Críticas**
   - Bugs de segurança
   - Problemas de performance
   - Falhas de backup
   - Erros de integridade

2. **Melhorias Incrementais**
   - Refatoração de código legado
   - Melhoria de documentação
   - Otimização de queries específicas
   - Ajustes finos na interface

3. **Atualizações de Dependências**
   - Security patches do PHP
   - Atualizações de segurança do MySQL
   - Versões recentes de bibliotecas
   - Correções de vulnerabilidades

### **📋 Roadmap Detalhado**

#### **🚀 Q1 2024: Modernização da Interface**
- **Mês 1-2**: Planejamento e design
- **Mês 3**: Migração para AdminLTE 4 (beta)
- **Mês 4**: Implementação de componentes customizados
- **Mês 5**: Testes e ajustes finais
- **Mês 6**: Deploy em produção

#### **🔧 Q2 2024: APIs RESTful**
- **Mês 7-8**: Design das APIs
- **Mês 9**: Implementação backend
- **Mês 10**: Documentação e testes
- **Mês 11**: Integração com frontend
- **Mês 12**: Deploy em ambiente de homologação

#### **🤖 Q3 2024: Inteligência Artificial**
- **Mês 1-2**: Pesquisa de soluções
- **Mês 3-4**: Protótipo de chatbot
- **Mês 5-6**: Treinamento com dados históricos
- **Mês 7-8**: Implementação de classificação
- **Mês 9-10**: Integração com sistema principal
- **Mês 11-12**: Testes e deploy

#### **📱 Q4 2024: Microserviços**
- **Mês 1-2**: Arquitetura e planejamento
- **Mês 3-4**: Containerização (Docker)
- **Mês 5-6**: Separação frontend/backend
- **Mês 7-8**: Implementação de APIs
- **Mês 9-10**: Orquestração com Kubernetes
- **Mês 11-12**: Migração gradual

### **📊 Critérios de Priorização**

| Critério | Peso | Descrição |
|----------|------|----------|
| **Impacto no Negócio** | 30% | Afeta operações críticas do sistema penitenciário |
| **Segurança** | 25% | Corrige vulnerabilidades e protege dados sensíveis |
| **Performance** | 20% | Melhora experiência do usuário e escalabilidade |
| **Manutenibilidade** | 15% | Facilita desenvolvimento e correções futuras |
| **Inovação** | 10% | Recursos tecnológicos e novas funcionalidades |

---

## **🔄 10.5 Decisões Arquitetônicas**

### **📋 Histórico de Decisões Importantes**

#### **🔐 Escolha do Framework AdminLTE**
**Decisão**: Adotar AdminLTE 3.2 como framework principal  
**Data**: Janeiro 2024  
**Contexto**: Necessidade de interface administrativa robusta e profissional  
**Alternativas Consideradas**: Bootstrap puro, Tailwind CSS, Material-UI  
**Decisão**: AdminLTE 3.2  
**Justificativa**: 
- Componentes prontos para painéis administrativos
- Documentação extensa e comunidade ativa
- Compatibilidade com PHP 8.4
- Design responsivo e mobile-friendly
- Menor curva de aprendizado para a equipe
- Sistema de temas e cores institucionais

#### **🗄️ Arquitetura de Banco de Dados**
**Decisão**: Manter MySQL 8.0 com PDO nativo  
**Data**: Março 2024  
**Contexto**: Performance e estabilidade para grandes volumes  
**Alternativas Consideradas**: PostgreSQL, MariaDB, Eloquent ORM  
**Decisão**: MySQL 8.0 + PDO  
**Justificativa**:
- Melhor performance para workloads de leitura intensiva
- Compatibilidade total com stack existente
- Menor complexidade de migração
- Ferramentas maduras e estáveis
- Equipe já possui expertise em MySQL
- Custo menor de manutenção

#### **🔧 Estrutura MVC Customizada**
**Decisão**: Implementar MVC customizado em vez de framework padrão  
**Data**: Fevereiro 2024  
**Contexto**: Necessidade de flexibilidade e controle total  
**Alternativas Consideradas**: Laravel, Symfony, CodeIgniter  
**Decisão**: MVC SIGEP customizado  
**Justificativa**:
- Controle total sobre estrutura e convenções
- Adaptado especificamente para necessidades penitenciárias
- Menor overhead de framework
- Aprendizado da equipe não impactado por mudanças externas
- Flexibilidade para integrações específicas

#### **🌐 Estratégia de Autenticação**
**Decisão**: Implementar autenticação baseada em sessão com rate limiting  
**Data**: Abril 2024  
**Contexto**: Segurança e controle de acesso  
**Alternativas Consideradas**: JWT stateless, OAuth 2.0, SAML  
**Decisão**: Sessão PHP + Rate Limiting  
**Justificativa**:
- Simplicidade de implementação e manutenção
- Compatibilidade com sistema existente
- Controle granular de timeout e expiração
- Menor dependência de bibliotecas externas
- Auditoria nativa mais fácil de implementar
- Performance adequada para volume de acessos

#### **📱 Estratégia de Deploy**
**Decisão**: Deploy manual com scripts automatizados  
**Data**: Maio 2024  
**Contexto**: Necessidade de controle e validação  
**Alternativas Consideradas**: CI/CD completo, blue-green deployment  
**Decisão**: Deploy manual + Scripts  
**Justificativa**:
- Maior controle sobre o processo
- Validação humana antes de ir para produção
- Menor complexidade de infraestrutura
- Permite rollback rápido em caso de problemas
- Adequado para equipe e processo atual
- Menor risco de falhas em cascata

---

## **🔗 Documentação Relacionada**

### **📚 Componentes de Manutenção**
- **[Desenvolvimento](desenvolvimento.md)** - Processos e padrões
- **[Segurança](security/seguranca_completa.md)** - Monitoramento e auditoria
- **[Dados e Persistência](dados_persistencia.md)** - Backup e recovery
- **[Deploy e Operação](deploy_operacao.md)** - Processos de produção

### **🛠️ Ferramentas Externas**
- **[MySQL Documentation](https://dev.mysql.com/doc/)** - Guia oficial
- **[PHP Debugging](https://xdebug.org/docs/)** - Ferramenta de debug
- **[Apache Performance](https://httpd.apache.org/docs/)** - Otimização web server
- **[API Testing](https://www.postman.com/)** - Teste de APIs

---

## **📋 Checklist de Manutenção**

### **✅ Monitoramento Proativo**
- [x] **Métricas de performance** coletadas em tempo real
- [x] **Logs de erros** monitorados com alertas
- [x] **Health checks** automáticos funcionando
- [x] **Uso de recursos** dentro de limites aceitáveis
- [x] **Backup automatizado** e verificado regularmente
- [x] **Security scans** executados periodicamente

### **✅ Processos de Evolução**
- [x] **Roadmap** definido com prioridades claras
- [x] **Decisões arquitetônicas** documentadas
- [x] **Critérios de priorização** estabelecidos
- [x] **Feedback dos usuários** coletado e analisado
- [x] **Tendências tecnológicas** acompanhadas
- [x] **Inovações** avaliadas e implementadas

### **✅ Suporte e Documentação**
- [x] **APIs documentadas** com exemplos práticos
- [x] **Troubleshooting guide** atualizado regularmente
- [x] **Procedimentos de emergência** definidos
- [x] **Base de conhecimento** acessível e pesquisável
- [x] **Treinamento da equipe** contínuo e planejado

---

## **🎯 Melhores Práticas**

### **🔄 Manutenção Proativa**
1. **Monitorar métricas** e agir sobre anomalias
2. **Manter documentação** sempre atualizada
3. **Planejar evolução** baseada em dados reais
4. **Testar backups** regularmente em ambiente isolado
5. **Manter segurança** atualizada contra ameaças
6. **Coletar feedback** dos usuários continuamente

### **🔒 Segurança na Manutenção**
1. **Aplicar patches** de segurança assim que disponíveis
2. **Monitorar vulnerabilidades** em dependências
3. **Realizar testes de penetração** regularmente
4. **Manter auditoria** completa e protegida
5. **Revisar permissões** de acesso regularmente
6. **Implementar defesa em profundidade** contra ataques

### **📈 Evolução Orientada a Dados**
1. **Analisar padrões de uso** para identificar oportunidades
2. **Priorizar funcionalidades** com base no valor gerado
3. **Validar novas tecnologias** com proof of concept
4. **Migrar gradualmente** para evitar grandes rupturas
5. **Manter compatibilidade** backward quando possível
6. **Documentar decisões** e alternativas consideradas

---

**Esta seção completa o ciclo de vida do SIGEP, desde a documentação técnica até a evolução estratégica, garantindo a sustentabilidade e melhoria contínua do sistema penitenciário.**
