<?php

// PARÂMETROS INICIAIS
ob_start();
date_default_timezone_set('America/Sao_Paulo');
session_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);
// FIM DOS PARÂMETROS INICIAIS

// VERIFICAÇÃO DE SESSÃO
if (!isset($_SESSION['user_id'])) {
    if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Sessao expirada.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    header('Location: /autenticacao');
    exit;
}
// FIM DA VERIFICAÇÃO DE SESSÃO

// INÍCIO DAS API ENDPOINTS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');

    try {
        switch ($_POST['action']) {
            case 'carregar_internos':
                echo carregarInternos($pdo);
                break;
            case 'carregar_estatisticas':
                echo carregarEstatisticas($pdo);
                break;
            default:
                throw new Exception('Ação não reconhecida');
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
    exit;
}
// FIM DAS API ENDPOINTS

// FUNÇÕES AUXILIARES
function carregarInternos($pdo) {
    // Capturar filtros do POST
    $filtros = [
        'search' => $_POST['search'] ?? '',
        'situacao' => $_POST['situacao'] ?? '',
        'galeria' => $_POST['galeria'] ?? '',
        'bloco' => $_POST['bloco'] ?? ''
    ];

    $where = ["1=1"];
    $params = [];

    if (!empty($filtros['search'])) {
        $where[] = "(nome LIKE ? OR ipen LIKE ?)";
        $params[] = "%{$filtros['search']}%";
        $params[] = "%{$filtros['search']}%";
    }

    if (!empty($filtros['situacao'])) {
        $where[] = "situacao = ?";
        $params[] = $filtros['situacao'];
    }

    if (!empty($filtros['galeria'])) {
        $where[] = "galeria = ?";
        $params[] = $filtros['galeria'];
    }

    if (!empty($filtros['bloco'])) {
        $where[] = "bloco = ?";
        $params[] = $filtros['bloco'];
    }

    $sql = "SELECT ipen, nome, nome_social, galeria, bloco, res, situacao, kit, regalia, cor_roupa
             FROM internos
             WHERE " . implode(" AND ", $where) . "
             ORDER BY nome ASC
             LIMIT 100";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $internos = $stmt->fetchAll();

    return json_encode(['success' => true, 'data' => $internos], JSON_UNESCAPED_UNICODE);
}

function carregarEstatisticas($pdo) {
    // Kits Disponíveis (baseado na lógica original)
    $kits_ocupados = $pdo->query("SELECT kit FROM internos WHERE kit > 0 AND status = 'A'")->fetchAll(PDO::FETCH_COLUMN);
    $kits_disponiveis = count(array_diff(range(1, 1100), $kits_ocupados));

    // Internos Sem Kit
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM internos WHERE status = 'A' AND (kit = 0 OR kit IS NULL)");
    $stmt->execute();
    $sem_kit = $stmt->fetchColumn();

    // Conflitos (kits repetidos)
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT kit) FROM internos WHERE kit > 0 AND status = 'A' GROUP BY kit HAVING COUNT(*) > 1");
    $stmt->execute();
    $conflitos = $stmt->fetchColumn();

    // Internos Ativos
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM internos WHERE status = 'A'");
    $stmt->execute();
    $ativos = $stmt->fetchColumn();

    return json_encode([
        'success' => true,
        'data' => [
            'kits_disponiveis' => $kits_disponiveis,
            'internos_ativos' => $ativos,
            'sem_kit' => $sem_kit,
            'conflitos' => $conflitos ?: 0
        ]
    ], JSON_UNESCAPED_UNICODE);
}
