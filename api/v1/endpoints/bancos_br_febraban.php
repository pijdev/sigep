<?php

/**
 * SIGEP API v1 - Endpoint: bancos_br_febraban
 *
 * Retorna lista de bancos brasileiros com código FEBRABAN
 * Fonte primária: https://brasilapi.com.br/api/banks/v1
 * Fallback: Tabela brasil_bancos_ativos (MySQL)
 *
 * Método: GET
 * Autenticação: Não necessária (dados públicos)
 * Cache: 1 hora (HTTP header)
 *
 * Query Parameters:
 *   - source: 'brasilapi' (padrão) | 'local' (força BD local)
 *
 * Response:
 *   {
 *     "success": true,
 *     "data": [
 *       { "codigo": "001", "nome": "Banco do Brasil S.A.", "ispb": "00000000" }
 *     ],
 *     "total": 146,
 *     "fonte": "brasilapi",
 *     "timestamp": "2026-04-08 12:00:00"
 *   }
 *
 * @version 1.0.0
 * @author SIGEP System
 * @date 2026-04-08
 */

session_start();
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Cache-Control: public, max-age=3600'); // Cache de 1 hora

// Configuração de caminho para includes
$basePath = realpath(__DIR__ . '/../../../');
require_once $basePath . '/conf/db.php';

$source = $_GET['source'] ?? 'brasilapi';

/**
 * Busca bancos da BrasilAPI (fonte primária)
 * @return array|false Resultado ou false em caso de erro
 */
function fetchFromBrasilAPI()
{
    $ch = curl_init('https://brasilapi.com.br/api/banks/v1');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => ['Accept: application/json', 'User-Agent: SIGEP-API/1.0']
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || !$response) {
        return false;
    }

    $data = json_decode($response, true);
    if (!is_array($data)) {
        return false;
    }

    // Formatar dados padronizados
    $result = [];
    foreach ($data as $item) {
        if (!empty($item['code']) && !empty($item['name'])) {
            $result[] = [
                'codigo' => str_pad($item['code'], 3, '0', STR_PAD_LEFT),
                'nome' => $item['name'],
                'ispb' => $item['ispb'] ?? null
            ];
        }
    }

    // Ordenar por código
    usort($result, fn($a, $b) => strcmp($a['codigo'], $b['codigo']));

    return [
        'success' => true,
        'data' => $result,
        'total' => count($result),
        'fonte' => 'brasilapi',
        'timestamp' => date('Y-m-d H:i:s')
    ];
}

/**
 * Busca bancos do banco de dados local (fallback)
 * @param PDO $pdo Conexão com banco de dados
 * @return array|false Resultado ou false em caso de erro
 */
function fetchFromLocalDB($pdo)
{
    try {
        $stmt = $pdo->query("SELECT codigo, nome, ispb FROM brasil_bancos_ativos ORDER BY codigo");
        $data = $stmt->fetchAll();

        if (empty($data)) {
            return false;
        }

        return [
            'success' => true,
            'data' => $data,
            'total' => count($data),
            'fonte' => 'banco_de_dados_local',
            'timestamp' => date('Y-m-d H:i:s'),
            'fallback' => true
        ];
    } catch (Exception $e) {
        return false;
    }
}

// === EXECUÇÃO PRINCIPAL ===

$result = null;

if ($source === 'brasilapi') {
    // Tentar BrasilAPI primeiro
    $result = fetchFromBrasilAPI();

    // Se falhar, tentar banco local
    if (!$result || !$result['success']) {
        try {
            $config = require $basePath . '/conf/db.php';
            $pdo = new PDO(
                "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}",
                $config['user'],
                $config['pass'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            $result = fetchFromLocalDB($pdo);
            if ($result && $result['success']) {
                $result['erro_fonte_primaria'] = 'BrasilAPI indisponível ou timeout';
            }
        } catch (Exception $e) {
            // Continua para retornar erro
        }
    }
} else {
    // Forçar consulta ao banco local
    try {
        $config = require $basePath . '/conf/db.php';
        $pdo = new PDO(
            "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}",
            $config['user'],
            $config['pass'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        $result = fetchFromLocalDB($pdo);
    } catch (Exception $e) {
        $result = [
            'success' => false,
            'error' => 'Erro ao consultar banco de dados local: ' . $e->getMessage()
        ];
    }
}

// Retornar resultado ou erro
if (!$result || !$result['success']) {
    http_response_code(503);
    echo json_encode([
        'success' => false,
        'error' => 'Serviço temporariamente indisponível. Nem BrasilAPI nem banco de dados local estão acessíveis.',
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

http_response_code(200);
echo json_encode($result, JSON_UNESCAPED_UNICODE);
