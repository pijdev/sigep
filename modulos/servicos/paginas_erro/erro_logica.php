<?php
/**
 * SIGEP - Lógica das Páginas de Erro
 * Processamento de erros e logging
 * 
 * @version 1.0.0
 * @author SIGEP Development Team
 */

session_start();
require_once __DIR__ . '/../../../conf/db.php';

// Headers para resposta JSON
header('Content-Type: application/json; charset=utf-8');

// Função para retornar erro JSON
function returnError($message, $code = 500) {
    http_response_code($code);
    echo json_encode(['error' => $message, 'code' => $code], JSON_UNESCAPED_UNICODE);
    exit;
}

// Função para registrar erro no log
function registrarErro($codigo, $mensagem, $detalhes = []) {
    $log_data = [
        'timestamp' => date('Y-m-d H:i:s'),
        'codigo' => $codigo,
        'mensagem' => $mensagem,
        'usuario' => $_SESSION['user_nome'] ?? 'Não autenticado',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'Desconhecido',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Desconhecido',
        'referer' => $_SERVER['HTTP_REFERER'] ?? 'Acesso direto',
        'request_uri' => $_SERVER['REQUEST_URI'] ?? 'Desconhecido',
        'detalhes' => $detalhes
    ];
    
    // Registrar em arquivo de log
    $log_file = __DIR__ . '/logs/erros_' . date('Y-m-d') . '.log';
    $log_dir = dirname($log_file);
    
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    file_put_contents($log_file, json_encode($log_data) . "\n", FILE_APPEND | LOCK_EX);
    
    // Opcional: Registrar no banco de dados
    try {
        $config = require __DIR__ . '/../../../conf/db.php';
        $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
        $pdo = new PDO($dsn, $config['user'], $config['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        
        $stmt = $pdo->prepare("
            INSERT INTO logs_erros (
                codigo, mensagem, usuario_id, usuario_nome, ip, 
                user_agent, referer, request_uri, detalhes, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $codigo,
            $mensagem,
            $_SESSION['user_id'] ?? null,
            $_SESSION['user_nome'] ?? null,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null,
            $_SERVER['HTTP_REFERER'] ?? null,
            $_SERVER['REQUEST_URI'] ?? null,
            json_encode($detalhes)
        ]);
        
    } catch (PDOException $e) {
        // Se falhar no banco, apenas registrar em arquivo
        error_log("Erro ao registrar no banco: " . $e->getMessage());
    }
}

// Verificar se é requisição POST para processar erro
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    ob_clean();
    
    try {
        switch ($_POST['action']) {
            case 'registrar_erro':
                $codigo = $_POST['codigo'] ?? '500';
                $mensagem = $_POST['mensagem'] ?? 'Erro não especificado';
                $detalhes = $_POST['detalhes'] ?? [];
                
                registrarErro($codigo, $mensagem, $detalhes);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Erro registrado com sucesso'
                ], JSON_UNESCAPED_UNICODE);
                break;
                
            case 'listar_erros':
                // Verificar permissão (apenas administradores)
                if (!($_SESSION['user_admin'] ?? false)) {
                    returnError('Sem permissão para listar erros', 403);
                }
                
                $config = require __DIR__ . '/../../../conf/db.php';
                $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
                $pdo = new PDO($dsn, $config['user'], $config['pass'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
                
                $stmt = $pdo->prepare("
                    SELECT * FROM logs_erros 
                    ORDER BY created_at DESC 
                    LIMIT 100
                ");
                $stmt->execute();
                $erros = $stmt->fetchAll();
                
                echo json_encode([
                    'success' => true,
                    'data' => $erros
                ], JSON_UNESCAPED_UNICODE);
                break;
                
            case 'estatisticas_erros':
                // Verificar permissão
                if (!($_SESSION['user_admin'] ?? false)) {
                    returnError('Sem permissão para ver estatísticas', 403);
                }
                
                $config = require __DIR__ . '/../../../conf/db.php';
                $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
                $pdo = new PDO($dsn, $config['user'], $config['pass'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
                
                // Estatísticas por código
                $stmt = $pdo->prepare("
                    SELECT codigo, COUNT(*) as total 
                    FROM logs_erros 
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                    GROUP BY codigo
                    ORDER BY total DESC
                ");
                $stmt->execute();
                $por_codigo = $stmt->fetchAll();
                
                // Estatísticas por hora
                $stmt = $pdo->prepare("
                    SELECT HOUR(created_at) as hora, COUNT(*) as total 
                    FROM logs_erros 
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                    GROUP BY HOUR(created_at)
                    ORDER BY hora
                ");
                $stmt->execute();
                $por_hora = $stmt->fetchAll();
                
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'por_codigo' => $por_codigo,
                        'por_hora' => $por_hora
                    ]
                ], JSON_UNESCAPED_UNICODE);
                break;
                
            default:
                throw new Exception('Ação não reconhecida');
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
    
    exit;
}

// Função para criar tabela de logs se não existir
function criarTabelaLogs() {
    try {
        $config = require __DIR__ . '/../../../conf/db.php';
        $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
        $pdo = new PDO($dsn, $config['user'], $config['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        
        $sql = "
            CREATE TABLE IF NOT EXISTS logs_erros (
                id INT AUTO_INCREMENT PRIMARY KEY,
                codigo VARCHAR(10) NOT NULL,
                mensagem TEXT NOT NULL,
                usuario_id INT NULL,
                usuario_nome VARCHAR(255) NULL,
                ip VARCHAR(45) NULL,
                user_agent TEXT NULL,
                referer TEXT NULL,
                request_uri TEXT NULL,
                detalhes JSON NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_codigo (codigo),
                INDEX idx_created_at (created_at),
                INDEX idx_usuario_id (usuario_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        $pdo->exec($sql);
        
    } catch (PDOException $e) {
        error_log("Erro ao criar tabela logs_erros: " . $e->getMessage());
    }
}

// Criar tabela automaticamente
criarTabelaLogs();

// Se for GET, processar exibição da página de erro
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Registrar o erro automaticamente
    $codigo = $_GET['codigo'] ?? $_GET['404'] ?? $_GET['403'] ?? $_GET['500'] ?? '404';
    $mensagem = $_GET['mensagem'] ?? '';
    $detalhes = [
        'query_string' => $_SERVER['QUERY_STRING'] ?? '',
        'request_method' => $_SERVER['REQUEST_METHOD'] ?? '',
        'server_name' => $_SERVER['SERVER_NAME'] ?? ''
    ];
    
    // Não registrar erros 404 para bots/spiders
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $is_bot = preg_match('/bot|crawl|spider|scraper/i', $user_agent);
    
    if (!$is_bot || $codigo !== '404') {
        registrarErro($codigo, $mensagem, $detalhes);
    }
}
?>
