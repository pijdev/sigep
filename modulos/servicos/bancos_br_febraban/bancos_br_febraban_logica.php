<?php
/**
 * SIGEP - Módulo de Serviço: Bancos BR - Febraban
 * Controller: bancos_br_febraban_logica.php
 * 
 * Responsabilidade: Sincronizar dados da BrasilAPI com BD local
 * Executar: Chamada AJAX ou CLI (cronjob)
 * 
 * Endpoints:
 *   - GET/POST ?action=sincronizar : Sincroniza BrasilAPI -> BD local
 *   - GET/POST ?action=listar : Lista bancos do BD local
 *   - GET/POST ?action=status : Retorna status da sincronização
 * 
 * @module servicos/bancos_br_febraban
 * @version 1.0.0
 */

session_start();
require_once __DIR__ . '/../../../conf/db.php';

header('Content-Type: application/json; charset=utf-8');

/**
 * Sincroniza dados da BrasilAPI com tabela local
 * @param PDO $pdo Conexão com banco de dados
 * @return array Resultado da operação
 */
function sincronizarBancosComAPI($pdo) {
    try {
        // Buscar dados da BrasilAPI
        $ch = curl_init('https://brasilapi.com.br/api/banks/v1');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => ['Accept: application/json', 'User-Agent: SIGEP-BancosSync/1.0']
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($httpCode !== 200 || !$response) {
            return [
                'success' => false,
                'error' => 'Falha ao consultar BrasilAPI: ' . ($error ?: "HTTP {$httpCode}"),
                'fonte' => 'brasilapi'
            ];
        }
        
        $bancos = json_decode($response, true);
        
        if (!is_array($bancos) || empty($bancos)) {
            return [
                'success' => false,
                'error' => 'Resposta inválida da BrasilAPI',
                'fonte' => 'brasilapi'
            ];
        }
        
        // Iniciar transação
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("INSERT INTO brasil_bancos_ativos (codigo, nome, ispb) 
                               VALUES (:codigo, :nome, :ispb)
                               ON DUPLICATE KEY UPDATE 
                               nome = VALUES(nome), 
                               ispb = VALUES(ispb),
                               atualizado_em = NOW()");
        
        $inseridos = 0;
        $atualizados = 0;
        
        foreach ($bancos as $banco) {
            if (!empty($banco['code']) && !empty($banco['name'])) {
                $codigo = str_pad($banco['code'], 3, '0', STR_PAD_LEFT);
                
                $stmt->execute([
                    ':codigo' => $codigo,
                    ':nome' => $banco['name'],
                    ':ispb' => $banco['ispb'] ?? null
                ]);
                
                $rowCount = $stmt->rowCount();
                if ($rowCount == 1) {
                    $inseridos++;
                } elseif ($rowCount == 2) {
                    $atualizados++;
                }
            }
        }
        
        $pdo->commit();
        
        return [
            'success' => true,
            'message' => 'Sincronização concluída com sucesso',
            'total_api' => count($bancos),
            'inseridos' => $inseridos,
            'atualizados' => $atualizados,
            'data_execucao' => date('Y-m-d H:i:s')
        ];
        
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return [
            'success' => false,
            'error' => 'Erro no banco de dados: ' . $e->getMessage(),
            'fonte' => 'banco_de_dados'
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => 'Erro inesperado: ' . $e->getMessage(),
            'fonte' => 'sistema'
        ];
    }
}

/**
 * Lista bancos do banco de dados local
 * @param PDO $pdo Conexão com banco de dados
 * @return array Lista de bancos
 */
function listarBancosLocais($pdo) {
    try {
        $stmt = $pdo->query("SELECT codigo, nome, ispb, atualizado_em 
                             FROM brasil_bancos_ativos 
                             ORDER BY codigo");
        $bancos = $stmt->fetchAll();
        
        // Última sincronização
        $ultimaSync = null;
        if (!empty($bancos)) {
            $stmt = $pdo->query("SELECT MAX(atualizado_em) as ultima_sync FROM brasil_bancos_ativos");
            $result = $stmt->fetch();
            $ultimaSync = $result['ultima_sync'];
        }
        
        return [
            'success' => true,
            'data' => $bancos,
            'total' => count($bancos),
            'ultima_sincronizacao' => $ultimaSync,
            'data_consulta' => date('Y-m-d H:i:s')
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => 'Erro ao consultar banco de dados: ' . $e->getMessage()
        ];
    }
}

/**
 * Retorna status do serviço
 * @param PDO $pdo Conexão com banco de dados
 * @return array Status
 */
function getStatusServico($pdo) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total, MAX(atualizado_em) as ultima_sync 
                             FROM brasil_bancos_ativos");
        $dbStatus = $stmt->fetch();
        
        // Testar conectividade com BrasilAPI
        $ch = curl_init('https://brasilapi.com.br/api/banks/v1');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_NOBODY => true
        ]);
        curl_exec($ch);
        $apiOnline = curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200;
        curl_close($ch);
        
        return [
            'success' => true,
            'servico' => 'bancos_br_febraban',
            'status' => 'ativo',
            'banco_dados' => [
                'registros' => (int)$dbStatus['total'],
                'ultima_sincronizacao' => $dbStatus['ultima_sync'],
                'status' => $dbStatus['total'] > 0 ? 'populado' : 'vazio'
            ],
            'brasilapi' => [
                'online' => $apiOnline,
                'url' => 'https://brasilapi.com.br/api/banks/v1'
            ],
            'timestamp' => date('Y-m-d H:i:s')
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => 'Erro ao verificar status: ' . $e->getMessage()
        ];
    }
}

// === PROCESSAMENTO DE REQUISIÇÕES ===

// Verificar autenticação (exceto para chamadas CLI)
if (php_sapi_name() !== 'cli' && !isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit;
}

try {
    $config = require __DIR__ . '/../../../conf/db.php';
    $pdo = new PDO(
        "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}",
        $config['user'],
        $config['pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    
    $action = $_REQUEST['action'] ?? 'status';
    
    switch ($action) {
        case 'sincronizar':
        case 'sync':
            $result = sincronizarBancosComAPI($pdo);
            break;
            
        case 'listar':
            $result = listarBancosLocais($pdo);
            break;
            
        case 'status':
        default:
            $result = getStatusServico($pdo);
            break;
    }
    
    http_response_code($result['success'] ? 200 : 500);
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro interno do servidor: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
