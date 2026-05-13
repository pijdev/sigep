<?php
/**
 * SIGEP - Serviço de Sincronização de Bancos
 * Módulo: modulos/servicos/bancos
 * Responsabilidade: Sincronizar dados da BrasilAPI com BD local
 * 
 * Executar via: modulos/servicos/bancos/bancos_sync_logica.php?action=sync
 */

session_start();
require_once __DIR__ . '/../../../conf/db.php';

header('Content-Type: application/json; charset=utf-8');

/**
 * Sincroniza bancos da BrasilAPI com o banco de dados local
 * @param PDO $pdo Conexão com banco de dados
 * @return array Resultado da sincronização
 */
function sincronizarBancos($pdo) {
    try {
        // Buscar dados da BrasilAPI
        $ch = curl_init('https://brasilapi.com.br/api/banks/v1');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'User-Agent: SIGEP-BancosSync/1.0'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($httpCode !== 200 || !$response) {
            return [
                'success' => false,
                'message' => 'Erro ao consultar BrasilAPI: ' . ($error ?: 'HTTP ' . $httpCode)
            ];
        }
        
        $bancos = json_decode($response, true);
        
        if (!is_array($bancos) || empty($bancos)) {
            return [
                'success' => false,
                'message' => 'Resposta inválida da API'
            ];
        }
        
        // Iniciar transação
        $pdo->beginTransaction();
        
        // Preparar statement para INSERT/UPDATE
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
                
                if ($stmt->rowCount() > 0) {
                    if ($stmt->rowCount() == 1) {
                        $inseridos++;
                    } else {
                        $atualizados++;
                    }
                }
            }
        }
        
        $pdo->commit();
        
        return [
            'success' => true,
            'message' => 'Sincronização concluída',
            'total' => count($bancos),
            'inseridos' => $inseridos,
            'atualizados' => $atualizados,
            'data' => date('Y-m-d H:i:s')
        ];
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return [
            'success' => false,
            'message' => 'Erro: ' . $e->getMessage()
        ];
    }
}

/**
 * Retorna lista de bancos do banco de dados local
 * @param PDO $pdo Conexão com banco de dados
 * @return array Lista de bancos
 */
function getBancosLocais($pdo) {
    $stmt = $pdo->query("SELECT codigo, nome, ispb FROM brasil_bancos_ativos ORDER BY codigo");
    return $stmt->fetchAll();
}

// Processar requisições
if ($_SERVER['REQUEST_METHOD'] === 'GET' || $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar autenticação
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Não autorizado']);
        exit;
    }
    
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
    
    $action = $_REQUEST['action'] ?? 'listar';
    
    switch ($action) {
        case 'sync':
        case 'sincronizar':
            $resultado = sincronizarBancos($pdo);
            echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
            break;
            
        case 'listar':
        default:
            $bancos = getBancosLocais($pdo);
            
            // Verificar última sincronização
            $ultimaSync = null;
            if (!empty($bancos)) {
                $stmt = $pdo->query("SELECT MAX(atualizado_em) as ultima_sync FROM brasil_bancos_ativos");
                $result = $stmt->fetch();
                $ultimaSync = $result['ultima_sync'];
            }
            
            echo json_encode([
                'success' => true,
                'data' => $bancos,
                'total' => count($bancos),
                'ultima_sync' => $ultimaSync,
                'fonte' => 'banco_de_dados'
            ], JSON_UNESCAPED_UNICODE);
            break;
    }
    
    exit;
}
