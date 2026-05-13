<?php
ob_start();
date_default_timezone_set('America/Sao_Paulo');
session_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

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

// Conexão com o banco (mantida para outras tabelas)
try {
    $config = require __DIR__ . '/../../../../conf/db.php';
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['user'], $config['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Erro 500: Falha na conexão com o Banco de Dados.");
}

// CONFIGURAÇÃO DA API
require_once __DIR__ . '/../../../../api/config/api.php';
$api_token = $config['token'];
$api_base_url = 'http://sigep.pij.local/api/endpoints/internos.php';
$kits_prontos_api_url = 'http://sigep.pij.local/api/endpoints/kits_prontos.php';
$historico_api_url = 'http://sigep.pij.local/api/endpoints/internos_historico.php';
$rouparia_civil_api_url = 'http://sigep.pij.local/api/endpoints/rouparia_civil.php';
$procedures_api_url = 'http://sigep.pij.local/api/endpoints/kits_procedures.php';

// FUNÇÃO PARA CHAMAR API DE INTERNOS
function callInternosAPI($method = 'GET', $params = [], $data = null)
{
    global $api_base_url, $api_token;
    $url = $api_base_url;
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }

    $options = [
        'http' => [
            'method' => $method,
            'header' => [
                'X-API-Token: ' . $api_token,
                'Content-Type: application/json',
                'Accept: application/json'
            ],
            'ignore_errors' => true // Para capturar erros HTTP como 404, 500
        ]
    ];

    if ($data !== null) {
        $options['http']['content'] = json_encode($data);
    }

    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);

    if ($response === false) {
        $error = error_get_last();
        return ['success' => false, 'error' => 'Erro ao acessar a API: ' . ($error['message'] ?? 'Erro desconhecido')];
    }

    $http_response_header_str = implode("\r\n", $http_response_header);
    preg_match('/HTTP\/\d\.\d\s+(\d+)/', $http_response_header_str, $matches);
    $http_status = (int)($matches[1] ?? 500);

    $decoded_response = json_decode($response, true);

    if ($http_status >= 400) {
        return ['success' => false, 'error' => $decoded_response['message'] ?? 'Erro na API: Status ' . $http_status];
    }

    return ['success' => true, 'data' => $decoded_response['data'] ?? []];
}

// FUNÇÕES PARA OUTRAS APIs
function callKitsProntosAPI($method = 'GET', $params = [], $data = null)
{
    global $kits_prontos_api_url, $api_token;
    return callGenericAPI($kits_prontos_api_url, $method, $params, $data, $api_token);
}

function callHistoricoAPI($method = 'GET', $params = [], $data = null)
{
    global $historico_api_url, $api_token;
    return callGenericAPI($historico_api_url, $method, $params, $data, $api_token);
}

function callRoupariaCivilAPI($method = 'GET', $params = [], $data = null)
{
    global $rouparia_civil_api_url, $api_token;
    return callGenericAPI($rouparia_civil_api_url, $method, $params, $data, $api_token);
}

function callProceduresAPI($method = 'POST', $data = null)
{
    global $procedures_api_url, $api_token;
    return callGenericAPI($procedures_api_url, $method, [], $data, $api_token);
}

function callGenericAPI($url, $method, $params, $data, $token)
{
    $full_url = $url;
    if (!empty($params)) {
        $full_url .= '?' . http_build_query($params);
    }

    $options = [
        'http' => [
            'method' => $method,
            'header' => [
                'X-API-Token: ' . $token,
                'Content-Type: application/json',
                'Accept: application/json'
            ],
            'ignore_errors' => true
        ]
    ];

    if ($data !== null) {
        $options['http']['content'] = json_encode($data);
    }

    $context = stream_context_create($options);
    $response = @file_get_contents($full_url, false, $context);

    if ($response === false) {
        $error = error_get_last();
        return ['success' => false, 'error' => 'Erro ao acessar a API: ' . ($error['message'] ?? 'Erro desconhecido')];
    }

    $http_response_header_str = implode("\r\n", $http_response_header);
    preg_match('/HTTP\/\d\.\d\s+(\d+)/', $http_response_header_str, $matches);
    $http_status = (int)($matches[1] ?? 500);

    $decoded_response = json_decode($response, true);

    if ($http_status >= 400) {
        return ['success' => false, 'error' => $decoded_response['message'] ?? 'Erro na API: Status ' . $http_status];
    }

    return ['success' => true, 'data' => $decoded_response['data'] ?? []];
}

// QUERY PARA KITS PRONTOS (HISTÓRICO COMPLETO) - USANDO API
$result = callKitsProntosAPI('GET');
$kits_prontos = ($result['success'] && is_array($result['data'])) ? $result['data'] : [];

// MAPA DE KITS DISPONÍVEIS PARA VERIFICAÇÃO (apenas pronto e refazendo)
$ready_kits_map = [];
foreach ($kits_prontos as $kp) {
    if (in_array($kp['status'], ['pronto', 'refazendo'])) {
        $ready_kits_map[$kp['kit_numero']] = $kp;
    }
}
$ready_kit_numbers = array_keys($ready_kits_map);

// 1. BLOCO DE SALVAMENTO AJAX (POST)
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['db_action'])) {
    ob_clean();
    header('Content-Type: application/json');
    if ($_POST['db_action'] === 'update_regalia') {
        // Usar API para atualizar dados do interno
        try {
            $api_data = [
                'ipen' => $_POST['reg_ipen'],
                'regalia' => $_POST['reg_st'],
                'cor_roupa' => $_POST['reg_co'],
                'regalia_setor' => $_POST['reg_se'],
                'regalia_kit' => $_POST['reg_ki'] ?: null
            ];

            $result = callInternosAPI('PUT', [], $api_data);

            if ($result['success']) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => $result['error'] ?? 'Erro na API']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
    if ($_POST['db_action'] === 'update_inline') {
        try {
            $kit = $_POST['kit'] !== '' ? $_POST['kit'] : 0;
            $reg_k = $_POST['reg_k'] !== '' ? $_POST['reg_k'] : 0;
            $tam = $_POST['tam'];

            // Usar API para atualizar dados do interno
            $api_data = [
                'ipen' => $_POST['ipen'],
                'kit' => $kit,
                'regalia_kit' => $reg_k,
                'tamanho_kit' => $tam
            ];

            $result = callInternosAPI('PUT', [], $api_data);

            if ($result['success']) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => $result['error'] ?? 'Erro na API']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Erro ao atualizar: ' . $e->getMessage()]);
        }
        exit;
    }
    if ($_POST['db_action'] === 'update_regalia_inline') {
        // Usar API para atualizar dados do interno
        try {
            $api_data = [
                'ipen' => $_POST['ipen'],
                'kit' => $_POST['kit'] ?: null,
                'regalia_kit' => $_POST['regalia_kit'] ?: null,
                'regalia_setor' => $_POST['regalia_setor']
            ];

            $result = callInternosAPI('PUT', [], $api_data);

            if ($result['success']) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => $result['error'] ?? 'Erro na API']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
    if ($_POST['db_action'] === 'cadastrar_kit_pronto') {
        $usuario = $_SESSION['nome'] ?? 'Sistema';

        // Usar API de procedures para refazer_kit
        $result_refazer = callProceduresAPI('POST', [
            'action' => 'refazer_kit',
            'kit_numero' => $_POST['kit_numero'],
            'cor' => $_POST['cor'],
            'info_adicional' => $_POST['info_adicional'],
            'usuario' => $usuario
        ]);

        if (!$result_refazer['success']) {
            echo json_encode(['success' => false, 'error' => $result_refazer['error']]);
            exit;
        }

        // Se o kit foi marcado como pronto, finalizar a confecção
        if (isset($_POST['marcar_como_pronto']) && $_POST['marcar_como_pronto'] == '1') {
            $result_finalizar = callProceduresAPI('POST', [
                'action' => 'finalizar_confeccao_kit',
                'kit_numero' => $_POST['kit_numero'],
                'cor' => $_POST['cor'],
                'info_adicional' => $_POST['info_adicional'],
                'usuario' => $usuario
            ]);

            if (!$result_finalizar['success']) {
                echo json_encode(['success' => false, 'error' => $result_finalizar['error']]);
                exit;
            }
        }

        // Get updated history via API
        $result_updated = callKitsProntosAPI('GET', ['limit' => 10]);
        $kits_prontos_updated = $result_updated['success'] ? $result_updated['data'] : [];

        $rows = array_map(static function ($kp) {
            $info = (string)($kp['info_adicional'] ?? '');
            return [
                'id' => (int)$kp['id'],
                'kit_numero' => htmlspecialchars((string)$kp['kit_numero']),
                'cor' => htmlspecialchars((string)$kp['cor']),
                'data_cadastro_fmt' => date('d/m/Y H:i', strtotime($kp['data_cadastro'])),
                'info_adicional_full' => htmlspecialchars($info),
                'info_adicional_short' => htmlspecialchars(mb_strimwidth($info, 0, 20, '...')),
            ];
        }, $kits_prontos_updated);

        echo json_encode(['success' => true, 'rows' => $rows]);
        exit;
    }
    if ($_POST['db_action'] === 'buscar_internos') {
        $query = trim($_POST['query'] ?? '');

        if (strlen($query) < 2) {
            echo json_encode([]);
            exit;
        }

        // Usar API para buscar internos
        try {
            $result = callInternosAPI('GET', ['search' => $query, 'limit' => 10]);

            if ($result['success']) {
                // Filtrar apenas campos necessários
                $filtered = array_map(function ($interno) {
                    return [
                        'ipen' => $interno['ipen'],
                        'nome' => $interno['nome'],
                        'nome_social' => $interno['nome_social'],
                        'galeria' => $interno['galeria'],
                        'bloco' => $interno['bloco'],
                        'res' => $interno['res'],
                        'situacao' => $interno['situacao'],
                        'status' => $interno['status']
                    ];
                }, $result['data']);

                echo json_encode($filtered);
            } else {
                echo json_encode([]);
            }
        } catch (Exception $e) {
            echo json_encode([]);
        }
        exit;
    }

    // AJAX PARA HISTÓRICO DE KIT
    if ($_POST['db_action'] === 'buscar_historico_kit') {
        $kit = $_POST['kit_numero'];

        // Buscar histórico via API
        $result_hist = callHistoricoAPI('GET', ['kit' => $kit]);
        $hist = $result_hist['success'] ? $result_hist['data'] : [];

        // Buscar info do kit via API
        $result_kit = callKitsProntosAPI('GET', ['kit_numero' => $kit]);
        $kit_info = $result_kit['success'] && !empty($result_kit['data']) ? $result_kit['data'][0] : null;

        $status_alerts = [];

        // Verificar se kit está atribuído a interno ativo - USANDO API
        $result = callInternosAPI('GET', ['kit' => $kit, 'status' => 'A']);
        if ($result['success'] && !empty($result['data'])) {
            $assigned = $result['data'][0];
            $status_alerts[] = [
                'level' => 'danger',
                'icon' => 'fa-user',
                'message' => 'Este kit está atribuído ao interno ativo: <strong>' . htmlspecialchars($assigned['ipen'] . ' - ' . $assigned['nome']) . '</strong>'
            ];
        }

        // Verificar se kit está em quarentena - USANDO API
        $result_quarantine = callInternosAPI('GET', ['kit' => $kit, 'status' => 'I']);
        if ($result_quarantine['success']) {
            $data_limite = date('Y-m-d H:i:s', strtotime('-7 days'));
            foreach ($result_quarantine['data'] as $interno) {
                if (!empty($interno['data_inativo']) && $interno['data_inativo'] >= $data_limite) {
                    $data_inativo_timestamp = strtotime($interno['data_inativo']);
                    $data_liberacao_timestamp = strtotime($interno['data_inativo'] . ' + 7 days');

                    // Validar se os timestamps são válidos
                    if ($data_inativo_timestamp && $data_liberacao_timestamp) {
                        $data_lib = date('d/m/Y H:i', $data_liberacao_timestamp);
                        $dias_restantes = ceil(($data_liberacao_timestamp - time()) / 86400);

                        // Se dias restantes for negativo, mostrar o valor real (não usar max(0))
                        if ($dias_restantes < 0) {
                            $dias_restantes_text = abs($dias_restantes) . " dias atrás (erro de data)";
                        } else {
                            $dias_restantes_text = $dias_restantes . " dias restantes";
                        }

                        $status_alerts[] = [
                            'level' => 'warning',
                            'icon' => 'fa-clock',
                            'message' => 'Este kit está em quarentena até <strong>' . $data_lib . '</strong> (' . $dias_restantes_text . ')'
                        ];
                        break;
                    }
                }
            }
        }
        if (!$assigned && empty($status_alerts) && !$kit_info) {
            $status_alerts[] = [
                'level' => 'success',
                'icon' => 'fa-tools',
                'message' => 'Este kit está disponível para confecção na rouparia'
            ];
        }

        echo json_encode([
            'status_alerts' => $status_alerts,
            'kit_numero' => $kit,
            'kit_info' => $kit_info ? [
                'kit_numero' => htmlspecialchars((string)$kit_info['kit_numero']),
                'cor' => htmlspecialchars((string)$kit_info['cor']),
                'status' => htmlspecialchars((string)$kit_info['status']),
                'data_cadastro_fmt' => date('d/m/Y H:i', strtotime($kit_info['data_cadastro'])),
                'info_adicional' => htmlspecialchars((string)($kit_info['info_adicional'] ?? '')),
            ] : null,
            'hist' => array_map(static function ($h) {
                return [
                    'data_alteracao_fmt' => date('d/m/Y H:i', strtotime($h['data_alteracao'])),
                    'ipen' => htmlspecialchars((string)$h['ipen']),
                    'nome' => htmlspecialchars((string)$h['nome']),
                    'tipo_alteracao' => $h['tipo_alteracao'],
                    'valor_antigo' => htmlspecialchars((string)($h['valor_antigo'] ?? '')),
                    'valor_novo' => htmlspecialchars((string)($h['valor_novo'] ?? '')),
                ];
            }, $hist),
        ]);
        exit;
    }
}
// 2. CONFIGURAÇÃO DE FILTROS E PARÂMETROS
$f = [
    'search'      => $_GET['search'] ?? '',
    'situacao'    => $_GET['situacao'] ?? '',
    'galeria'     => $_GET['galeria'] ?? '',
    'bloco'       => $_GET['bloco'] ?? '',
    'res'         => $_GET['res'] ?? '',
    'regalia'     => $_GET['regalia'] ?? '',
    'cor'         => $_GET['cor'] ?? '',
    'setor'       => $_GET['setor'] ?? '',
    'kit_f'       => $_GET['kit_f'] ?? '',
    'regkit_f'    => $_GET['regkit_f'] ?? '',
    'tam'         => $_GET['tam'] ?? '',
    'status'      => $_GET['status'] ?? 'A',
    'kit_num'     => $_GET['kit_num'] ?? '',
    'regkit_num'  => $_GET['regkit_num'] ?? ''
];

// Configuração de Ordenação
$sort_by = $_GET['sort_by'] ?? 'nome';
$sort_order = $_GET['sort_order'] ?? 'ASC';

$valid_sorts = [
    'ipen' => 'ipen',
    'nome' => 'nome',
    'local' => 'galeria, bloco, res',
    'situacao' => 'situacao',
    'regalia' => 'regalia',
    'cor' => 'cor_roupa',
    'kit' => 'kit',
    'kit_reg' => 'regalia_kit',
    'tam' => 'tamanho_kit'
];

if (array_key_exists($sort_by, $valid_sorts)) {
    $col_sql = $valid_sorts[$sort_by];
    if ($sort_by === 'local') {
        $order_sql = "galeria $sort_order, bloco $sort_order, res $sort_order";
    } elseif ($sort_by === 'nome') {
        $order_sql = "COALESCE(NULLIF(nome_social, ''), nome) $sort_order";
    } else {
        $order_sql = "$col_sql $sort_order";
    }
} else {
    $order_sql = "nome ASC";
}

// Construção do WHERE
$where = ["1=1"];
$params = [];

if ($f['search'] !== '') {
    $where[] = "(nome LIKE :s OR nome_social LIKE :s OR ipen LIKE :s)";
    $params[':s'] = "%" . $f['search'] . "%";
}
if ($f['situacao'] !== '') {
    $where[] = "situacao = :sit";
    $params[':sit'] = $f['situacao'];
}
if ($f['galeria'] !== '') {
    $where[] = "galeria = :gal";
    $params[':gal'] = $f['galeria'];
}
if ($f['bloco'] !== '') {
    $where[] = "bloco = :blo";
    $params[':blo'] = $f['bloco'];
}
if ($f['res'] !== '') {
    $where[] = "res = :res_n";
    $params[':res_n'] = $f['res'];
}
if ($f['regalia'] !== '') {
    $where[] = "regalia = :reg";
    $params[':reg'] = $f['regalia'];
}
if ($f['cor'] !== '') {
    $where[] = "cor_roupa = :cor";
    $params[':cor'] = $f['cor'];
}
if ($f['setor'] !== '') {
    $where[] = "regalia_setor LIKE :setor";
    $params[':setor'] = "%" . $f['setor'] . "%";
}
if ($f['tam'] !== '') {
    $where[] = "tamanho_kit = :tam";
    $params[':tam'] = $f['tam'];
}
if ($f['status'] !== '') {
    $where[] = "status = :st";
    $params[':st'] = $f['status'];
}
if ($f['kit_num'] !== '') {
    $where[] = "kit = :k_num";
    $params[':k_num'] = $f['kit_num'];
}
if ($f['regkit_num'] !== '') {
    $where[] = "regalia_kit = :rk_num";
    $params[':rk_num'] = $f['regkit_num'];
}
if ($f['kit_f'] === 'com') {
    $where[] = "kit > 0";
}
if ($f['kit_f'] === 'sem') {
    $where[] = "(kit IS NULL OR kit = 0)";
}

$sql_base = " FROM internos WHERE " . implode(" AND ", $where);

// --- LÓGICA DE KITS ---

// 1. Identificar Kits Repetidos (Conflitos) - USANDO API
// Mantém regra de ignorar G e separar Semi do Fechado para CONFLITOS (vermelho)
$internos_ativos = buscarTodosInternos('A');

// Replicar exatamente a lógica SQL: GROUP BY kit HAVING COUNT(*) > 1
// 1. Kits repetidos no fechado
$fechado_kits_count = [];
foreach ($internos_ativos as $interno) {
    if ($interno['kit'] > 0 && $interno['galeria'] !== 'S' && $interno['galeria'] !== 'G') {
        $fechado_kits_count[] = $interno['kit'];
    }
}

$ids_fechado = array_unique(array_filter(array_count_values($fechado_kits_count), function ($count) {
    return $count > 1;
}));
$ids_fechado = array_keys($ids_fechado);

// 2. Kits repetidos no semiaberto
$semi_kits_count = [];
foreach ($internos_ativos as $interno) {
    if ($interno['kit'] > 0 && $interno['galeria'] === 'S') {
        $semi_kits_count[] = $interno['kit'];
    }
}

$ids_semi = array_unique(array_filter(array_count_values($semi_kits_count), function ($count) {
    return $count > 1;
}));
$ids_semi = array_keys($ids_semi);

$repetidos_ids = array_unique(array_merge($ids_fechado, $ids_semi));

// Lista detalhada de repetidos - igual à query original
$rep_lista = array_filter($internos_ativos, function ($interno) use ($ids_fechado, $ids_semi) {
    if ($interno['kit'] > 0) {
        if ($interno['galeria'] !== 'S' && $interno['galeria'] !== 'G') {
            return in_array($interno['kit'], $ids_fechado);
        } elseif ($interno['galeria'] === 'S') {
            return in_array($interno['kit'], $ids_semi);
        }
    }
    return false;
});

// Ordenar por kit e galeria
usort($rep_lista, function ($a, $b) {
    if ($a['kit'] != $b['kit']) return $a['kit'] - $b['kit'];
    return strcmp($a['galeria'], $b['galeria']);
});

// 3. Kits Livres (Disponíveis) - COM REGRA DE 7~8 DIAS (Quarentena) - USANDO API
// A query original era: SELECT kit FROM internos WHERE kit > 0 AND (status = 'A' OR (status = 'I' AND data_inativo >= DATE_SUB(NOW(), INTERVAL 7 DAY)))
// Vamos replicar isso com chamadas API paginadas

// Função para buscar todos os dados da API (paginado)
function buscarTodosInternos($status)
{
    $todos = [];
    $offset = 0;
    $limit = 100; // limite da API (não remover, é o limite interno da API)

    do {
        $result = callInternosAPI('GET', ['status' => $status, 'limit' => $limit, 'offset' => $offset]);

        if ($result['success'] && is_array($result['data'])) {
            $todos = array_merge($todos, $result['data']);
            $offset += $limit;

            // Se retornou menos que o limite, chegamos ao fim
            if (count($result['data']) < $limit) {
                break;
            }
        } else {
            break;
        }
    } while (true);

    return $todos;
}

// Buscar todos os internos ativos
$internos_ativos = buscarTodosInternos('A');

// Buscar todos os internos inativos
$internos_inativos_quarentena = buscarTodosInternos('I');

$data_limite = date('Y-m-d H:i:s', strtotime('-7 days'));

// Coletar kits ocupados por ativos
$kits_ocupados_ativos = [];
foreach ($internos_ativos as $interno) {
    $kit_value = isset($interno['kit']) ? (int)$interno['kit'] : 0;
    if ($kit_value > 0) {
        $kits_ocupados_ativos[] = $kit_value;
    }
}

// Coletar kits em quarentena (inativos há menos de 7 dias)
$kits_quarentena = [];
foreach ($internos_inativos_quarentena as $interno) {
    $kit_value = isset($interno['kit']) ? (int)$interno['kit'] : 0;
    $data_inativo = $interno['data_inativo'] ?? null;

    if ($kit_value > 0 && $data_inativo && $data_inativo >= $data_limite) {
        $kits_quarentena[] = $kit_value;
    }
}

// Combinar kits ocupados (ativos + quarentena)
$kits_ocupados_todos = array_unique(array_merge($kits_ocupados_ativos, $kits_quarentena));

// Calcular kits livres (1-1100 menos os ocupados)
$livres_p = array_diff(range(1, 1100), $kits_ocupados_todos);

// 4. Sem Kit (Geral) - USANDO API
$internos_sem_kit = buscarTodosInternos('A');

$sem_p = array_filter($internos_sem_kit, function ($interno) {
    return $interno['kit'] == 0 || $interno['kit'] === null;
});

// Ordenar por nome social ou nome
usort($sem_p, function ($a, $b) {
    $nome_a = !empty($a['nome_social']) ? $a['nome_social'] : $a['nome'];
    $nome_b = !empty($b['nome_social']) ? $b['nome_social'] : $b['nome'];
    return strcmp($nome_a, $nome_b);
});

// 5. Kits em "Quarentena" (Inativos há menos de 7 dias) - USANDO API
$internos_quarentena = buscarTodosInternos('I');

$data_limite = date('Y-m-d H:i:s', strtotime('-7 days'));
$kits_quarentena_tela = array_filter($internos_quarentena, function ($interno) use ($data_limite) {
    return $interno['kit'] > 0 && $interno['data_inativo'] >= $data_limite;
});

// Adicionar data_liberacao calculada igual à query original
foreach ($kits_quarentena_tela as &$quar) {
    $quar['data_liberacao'] = date('Y-m-d H:i:s', strtotime($quar['data_inativo'] . ' + 7 days'));
}
unset($quar); // liberar referência

// Ordenar por data_inativo DESC
usort($kits_quarentena_tela, function ($a, $b) {
    return strcmp($b['data_inativo'], $a['data_inativo']);
});

// Garantir que todas as variáveis essenciais existam
$livres_p = $livres_p ?? [];
// $sem_p = $sem_p ?? []; // REMOVIDO - não sobrescrever valor filtrado
$rep_lista = $rep_lista ?? [];
$repetidos_ids = $repetidos_ids ?? [];
$regalias_fechado_tela = $regalias_fechado_tela ?? [];
$regalias_semiaberto_tela = $regalias_semiaberto_tela ?? [];

// --- LÓGICA DE REGALIAS FECHADO ---

// 1. Regalias do Fechado (todas as galerias do regime fechado) - USANDO API
$internos_fechado = buscarTodosInternos('A');

$galerias_fechado = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'T'];

$regalias_fechado_tela = array_filter($internos_fechado, function ($interno) use ($galerias_fechado) {
    return $interno['regalia'] === 'S' && in_array($interno['galeria'], $galerias_fechado);
});

// Ordenar por setor e nome
usort($regalias_fechado_tela, function ($a, $b) {
    if ($a['regalia_setor'] != $b['regalia_setor']) {
        return strcmp($a['regalia_setor'] ?? '', $b['regalia_setor'] ?? '');
    }
    return strcmp($a['nome'], $b['nome']);
});

// 2. Identificar números de regalia ocupados (1-35)
$regalias_ocupadas = [];
foreach ($regalias_fechado_tela as $reg) {
    if ($reg['regalia_kit'] > 0 && $reg['regalia_kit'] <= 35) {
        $regalias_ocupadas[] = $reg['regalia_kit'];
    }
}
$regalias_ocupadas = array_unique($regalias_ocupadas);

// 3. Regalias disponíveis (1-35 que não estão ocupadas)
$regalias_disponiveis = array_diff(range(1, 35), $regalias_ocupadas);

// --- LÓGICA DE REGALIAS SEMIABERTO ---

// 1. Regalias do Semiaberto (apenas galeria S) - USANDO API
$internos_semi = buscarTodosInternos('A');

$regalias_semiaberto_tela = array_filter($internos_semi, function ($interno) {
    return $interno['regalia'] === 'S' && $interno['galeria'] === 'S' && $interno['kit'] > 0;
});

// Ordenar por setor e kit
usort($regalias_semiaberto_tela, function ($a, $b) {
    if ($a['regalia_setor'] != $b['regalia_setor']) {
        return strcmp($a['regalia_setor'] ?? '', $b['regalia_setor'] ?? '');
    }
    return $a['kit'] - $b['kit'];
});

// 2. Identificar números de kit ocupados (1-50) para semiaberto - usa kit padrão
$regalias_ocupadas_semi = [];
foreach ($regalias_semiaberto_tela as $reg) {
    if ($reg['kit'] > 0 && $reg['kit'] <= 50) {
        $regalias_ocupadas_semi[] = $reg['kit'];
    }
}
$regalias_ocupadas_semi = array_unique($regalias_ocupadas_semi);

// 3. Kits disponíveis (1-50 que não estão ocupados) para semiaberto
$regalias_disponiveis_semi = array_diff(range(1, 50), $regalias_ocupadas_semi);

// --- 3. BLOCO DE IMPRESSÃO INTEGRAL ---
if (isset($_GET['execute_print'])) {
    // BLOQUEAR USUÁRIO ROUPARIA DE ACESSAR RELATÓRIOS DIRETAMENTE PELA URL
    if (isset($_SESSION['user_nome']) && $_SESSION['user_nome'] === 'rouparia') {
        die('<div style="padding: 50px; text-align: center; font-family: Arial; color: #dc3545;">
            <h2><i class="fas fa-exclamation-triangle"></i> Acesso Negado</h2>
            <p>Usuário rouparia não tem permissão para acessar relatórios.</p>
            <p><a href="javascript:history.back()" class="btn btn-primary">Voltar</a></p>
        </div>');
    }

    $mode = $_GET['mode'] ?? 'completo';
    $isLavanderia = ($mode === 'lavanderia');

    // Se for modo roupa_civil, usar bloco específico
    if ($mode === 'roupa_civil') {
        // BLOCO ESPECÍFICO PARA RELATÓRIO DE ROUPA CIVIL - MOVIDO PARA CIMA
        try {
            // Query para buscar internos com dados de roupa civil
            $sql = "SELECT
                i.ipen,
                i.nome,
                i.nome_social,
                i.galeria,
                i.bloco,
                i.res,
                i.situacao,
                i.regalia,
                i.regalia_setor,
                i.cor_roupa,
                rc.pecas as roupa_civil_pecas,
                rc.criado_por as roupa_civil_criado_por,
                rc.criado_em as roupa_civil_data
            FROM internos i
            INNER JOIN internos_rouparia_civil rc ON i.ipen = rc.ipen
            WHERE 1=1";

            // Aplicar filtros se houver
            $params = [];
            if (!empty($_GET['galeria'])) {
                $sql .= " AND i.galeria = ?";
                $params[] = $_GET['galeria'];
            }
            if (!empty($_GET['bloco'])) {
                $sql .= " AND i.bloco = ?";
                $params[] = $_GET['bloco'];
            }
            if (!empty($_GET['situacao'])) {
                $sql .= " AND i.situacao = ?";
                $params[] = $_GET['situacao'];
            }
            if (!empty($_GET['regalia'])) {
                if ($_GET['regalia'] === 'S') {
                    $sql .= " AND i.regalia = 'S'";
                } elseif ($_GET['regalia'] === 'N') {
                    $sql .= " AND i.regalia = 'N'";
                }
            }
            if (!empty($_GET['cor'])) {
                $sql .= " AND i.cor_roupa = ?";
                $params[] = $_GET['cor'];
            }
            if (!empty($_GET['criado_por'])) {
                $sql .= " AND rc.criado_por LIKE ?";
                $params[] = "%{$_GET['criado_por']}%";
            }

            // Filtro de período
            if (!empty($_GET['data_inicio'])) {
                $sql .= " AND rc.criado_em >= ?";
                $params[] = $_GET['data_inicio'] . ' 00:00:00';
            }
            if (!empty($_GET['data_fim'])) {
                $sql .= " AND rc.criado_em <= ?";
                $params[] = $_GET['data_fim'] . ' 23:59:59';
            }

            // Filtro de pesquisa por interno (IPEN, nome ou nome social)
            if (!empty($_GET['pesquisa_interno'])) {
                $pesquisa = trim($_GET['pesquisa_interno']);
                $sql .= " AND (i.ipen LIKE ? OR i.nome LIKE ? OR i.nome_social LIKE ?)";
                $params[] = "%{$pesquisa}%";
                $params[] = "%{$pesquisa}%";
                $params[] = "%{$pesquisa}%";
            }

            // Filtro para mostrar apenas internos com roupa civil
            if (isset($_GET['apenas_com_kit']) && $_GET['apenas_com_kit'] === '1') {
                $sql .= " AND rc.pecas IS NOT NULL";
            }

            $sql .= " ORDER BY i.nome ASC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Estatísticas
            $total_internos = count($dados);
            $internos_com_roupa_civil = count(array_filter($dados, function ($d) {
                return !empty($d['roupa_civil_pecas']);
            }));
            $internos_sem_roupa_civil = $total_internos - $internos_com_roupa_civil;

            // Contagem de peças por tipo
            $estatisticas_pecas = [
                'camiseta' => 0,
                'bermuda' => 0,
                'blusa' => 0,
                'casaco' => 0,
                'calca' => 0,
                'jaqueta' => 0,
                'bone' => 0,
                'tenis' => 0,
                'sapato' => 0,
                'meia' => 0,
                'luva' => 0,
                'bolsa' => 0,
                'mochila' => 0,
                'cueca' => 0,
                'chapeu' => 0,
                'outros' => 0
            ];

            foreach ($dados as $interno) {
                if (!empty($interno['roupa_civil_pecas'])) {
                    $pecas_json = json_decode($interno['roupa_civil_pecas'], true);
                    if ($pecas_json) {
                        if (!empty($pecas_json['predefinidos'])) {
                            foreach ($pecas_json['predefinidos'] as $item) {
                                $tipo = strtolower($item['tipo']);
                                if (isset($estatisticas_pecas[$tipo])) {
                                    $estatisticas_pecas[$tipo] += $item['quantidade'];
                                }
                            }
                        }
                        if (!empty($pecas_json['outros'])) {
                            foreach ($pecas_json['outros'] as $item) {
                                $estatisticas_pecas['outros'] += $item['quantidade'];
                            }
                        }
                    }
                }
            }


            // HTML para impressão
            $html = '
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <title>Relatório Detalhado - Roupa Civil</title>
                <style>
                    body { font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; margin: 20px; color: #333; }
                    h1, h2, h3 { color: #2c3e50; margin-bottom: 20px; }
                    h1 { text-align: center; border-bottom: 3px solid #3498db; padding-bottom: 10px; }
                    h2 { border-bottom: 2px solid #95a5a6; padding-bottom: 5px; }
                    .header-info { display: flex; justify-content: space-between; margin-bottom: 30px; background: #f8f9fa; padding: 15px; border-radius: 5px; }
                    .filters-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px; margin-bottom: 20px; padding: 15px; background: #e9ecef; border-radius: 5px; }
                    .filter-item { font-size: 0.85em; }
                    .filter-label { font-weight: bold; color: #495057; }
                    .filter-value { color: #007bff; }
                    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin-bottom: 30px; }
                    .stat-card { background: white; border: 1px solid #dee2e6; border-radius: 8px; padding: 15px; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
                    .stat-number { font-size: 2em; font-weight: bold; color: #3498db; }
                    .stat-label { font-size: 0.9em; color: #6c757d; text-transform: uppercase; margin-top: 5px; }
                    table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 0.85em; }
                    th, td { border: 1px solid #dee2e6; padding: 8px; text-align: left; vertical-align: top; }
                    th { background: #f8f9fa; font-weight: 600; text-transform: uppercase; font-size: 0.75em; }
                    tr:nth-child(even) { background: #f8f9fa; }
                    .text-center { text-align: center; }
                    .badge { display: inline-block; padding: 3px 8px; border-radius: 12px; font-size: 0.7em; font-weight: bold; text-transform: uppercase; }
                    .badge-success { background: #d4edda; color: #155724; }
                    .badge-warning { background: #fff3cd; color: #856404; }
                    .badge-danger { background: #f8d7da; color: #721c24; }
                    .badge-info { background: #d1ecf1; color: #0c5460; }
                    .roupa-civil-info { background: #e8f5e8; border-left: 4px solid #28a745; padding: 8px; margin: 2px 0; }
                    .sem-roupa-civil { background: #fff5f5; border-left: 4px solid #dc3545; padding: 8px; margin: 2px 0; }
                    .pecas-lista { margin: 0; padding-left: 15px; }
                    .pecas-item { margin: 2px 0; font-size: 0.8em; }
                    .local-info { font-weight: bold; color: #495057; }
                    .pecas-summary { margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px; }
                    .pecas-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 10px; margin-top: 10px; }
                    .peca-stat { text-align: center; padding: 8px; background: white; border-radius: 4px; border: 1px solid #dee2e6; }
                    .peca-stat .numero { font-size: 1.2em; font-weight: bold; color: #28a745; }
                    .peca-stat .nome { font-size: 0.8em; color: #6c757d; text-transform: uppercase; }
                    @media print {
                        body { margin: 0; font-size: 12px; }
                        .no-print { display: none; }
                        table { font-size: 10px; }
                        th, td { padding: 4px; }
                        .pecas-summary { page-break-inside: avoid; }
                    }
                </style>
            </head>
            <body>
                <h1>SIGEP - Sistema Prisional Integrado</h1>
                <h2>Relatório Detalhado: Kits de Roupa Civil</h2>

                <div class="header-info">
                    <div>
                        <strong>Data de Emissão:</strong> ' . date('d/m/Y H:i:s') . '<br>
                        <strong>Usuário:</strong> ' . ($_SESSION['nome_usuario'] ?? 'Sistema') . '
                    </div>
                    <div>
                        <strong>Período:</strong> ' . (!empty($_GET['data_inicio']) ? date('d/m/Y', strtotime($_GET['data_inicio'])) : 'Todos') . ' à ' . (!empty($_GET['data_fim']) ? date('d/m/Y', strtotime($_GET['data_fim'])) : 'Todos') . '<br>
                        <strong>Registros Filtrados:</strong> ' . $total_internos . '
                    </div>
                </div>

                ' . (!empty($_GET['galeria']) || !empty($_GET['bloco']) || !empty($_GET['situacao']) || !empty($_GET['regalia']) || !empty($_GET['cor']) || !empty($_GET['criado_por']) || !empty($_GET['pesquisa_interno']) || !empty($_GET['apenas_com_kit']) ? '
                <div class="filters-grid">
                    <div class="filter-item">
                        <span class="filter-label">Galeria:</span>
                        <span class="filter-value">' . ($_GET['galeria'] ?? 'Todas') . '</span>
                    </div>
                    <div class="filter-item">
                        <span class="filter-label">Bloco:</span>
                        <span class="filter-value">' . ($_GET['bloco'] ?? 'Todos') . '</span>
                    </div>
                    <div class="filter-item">
                        <span class="filter-label">Situação:</span>
                        <span class="filter-value">' . ($_GET['situacao'] ?? 'Todas') . '</span>
                    </div>
                    <div class="filter-item">
                        <span class="filter-label">Regalia:</span>
                        <span class="filter-value">' . (isset($_GET['regalia']) ? ($_GET['regalia'] === 'S' ? 'Sim' : 'Não') : 'Todas') . '</span>
                    </div>
                    <div class="filter-item">
                        <span class="filter-label">Cor:</span>
                        <span class="filter-value">' . ($_GET['cor'] ?? 'Todas') . '</span>
                    </div>
                    <div class="filter-item">
                        <span class="filter-label">Cadastrado por:</span>
                        <span class="filter-value">' . ($_GET['criado_por'] ?? 'Todos') . '</span>
                    </div>
                    <div class="filter-item">
                        <span class="filter-label">Pesquisa Interno:</span>
                        <span class="filter-value">' . ($_GET['pesquisa_interno'] ?? 'Todos') . '</span>
                    </div>
                    <div class="filter-item">
                        <span class="filter-label">Apenas c/ Kit:</span>
                        <span class="filter-value">' . (isset($_GET['apenas_com_kit']) && $_GET['apenas_com_kit'] === '1' ? 'Sim' : 'Não') . '</span>
                    </div>
                </div>
                ' : '') . '

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number">' . $total_internos . '</div>
                        <div class="stat-label">Total de Internos</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">' . $internos_com_roupa_civil . '</div>
                        <div class="stat-label">Com Kit Roupa Civil</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">' . $internos_sem_roupa_civil . '</div>
                        <div class="stat-label">Sem Kit Roupa Civil</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">' . round($internos_com_roupa_civil > 0 ? ($internos_com_roupa_civil / $total_internos) * 100 : 0, 1) . '%</div>
                        <div class="stat-label">Cobertura</div>
                    </div>
                </div>

                <div class="pecas-summary">
                    <h3>📊 Resumo de Peças Entregues</h3>
                    <div class="pecas-grid">
                        ' . implode('', array_map(function ($nome, $quantidade) {
                return "<div class='peca-stat'><div class='numero'>{$quantidade}</div><div class='nome'>{$nome}</div></div>";
            }, array_keys($estatisticas_pecas), array_values($estatisticas_pecas))) . '
                    </div>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 80px;">IPEN</th>
                            <th style="width: 200px;">Nome Completo</th>
                            <th class="text-center" style="width: 100px;">Local</th>
                            <th class="text-center" style="width: 80px;">Situação</th>
                            <th class="text-center" style="width: 60px;">Regalia</th>
                            <th style="width: 300px;">Kit de Roupa Civil</th>
                        </tr>
                    </thead>
                    <tbody>';

            foreach ($dados as $interno) {
                $nome_exib = !empty($interno['nome_social']) ?
                    "<strong>{$interno['nome_social']}</strong><br><small>({$interno['nome']})</small>" :
                    "<strong>{$interno['nome']}</strong>";

                // Adicionar badge de status
                $status_badge = $interno['status'] == 'A' ?
                    '<span class="badge badge-success ml-2" style="font-size: 0.7em;">ATIVO</span>' :
                    '<span class="badge badge-danger ml-2" style="font-size: 0.7em;">INATIVO</span>';
                $nome_exib .= $status_badge;

                $local = "{$interno['galeria']}{$interno['bloco']}-{$interno['res']}";
                $situacao = $interno['situacao'] ?: 'N/A';

                // Verificar se o interno está inativo e adicionar tag
                $row_style = '';
                if ($interno['status'] == 'I') {
                    $row_style = ' style="background-color: #fff3cd;"';
                    $situacao .= ' <span style="background-color: #dc3545; color: white; padding: 2px 6px; border-radius: 4px; font-size: 0.7em; font-weight: bold;">INATIVO</span>';
                }
                $regalia = $interno['regalia'] == 'S' ?
                    "<span class='badge badge-success'>SIM</span>" :
                    "<span class='badge badge-danger'>NÃO</span>";

                $roupa_civil_info = '';
                if (!empty($interno['roupa_civil_pecas'])) {
                    $pecas_json = json_decode($interno['roupa_civil_pecas'], true);
                    $itens_formatados = [];

                    if ($pecas_json && (isset($pecas_json['predefinidos']) || isset($pecas_json['outros']))) {
                        // Itens pré-definidos
                        if (!empty($pecas_json['predefinidos'])) {
                            foreach ($pecas_json['predefinidos'] as $item) {
                                $obs = !empty($item['observacao']) ? " <em>({$item['observacao']})</em>" : '';
                                $itens_formatados[] = "<span class='pecas-item'>• {$item['quantidade']}x {$item['tipo']}{$obs}</span>";
                            }
                        }

                        // Itens outros
                        if (!empty($pecas_json['outros'])) {
                            foreach ($pecas_json['outros'] as $item) {
                                $obs = !empty($item['observacao']) ? " <em>({$item['observacao']})</em>" : '';
                                $itens_formatados[] = "<span class='pecas-item'>• {$item['quantidade']}x {$item['tipo']}{$obs}</span>";
                            }
                        }
                    }

                    $data_cadastro = date('d/m/Y', strtotime($interno['roupa_civil_data']));
                    $criado_por = $interno['roupa_civil_criado_por'];

                    $roupa_civil_info = "
                        <div class='roupa-civil-info'>
                            <strong>Kit Cadastrado</strong><br>
                            <small>Data: {$data_cadastro} | Por: {$criado_por}</small>
                            <div class='pecas-lista'>" . implode('', $itens_formatados) . "</div>
                        </div>";
                } else {
                    $roupa_civil_info = "<div class='sem-roupa-civil'><em>Sem kit cadastrado</em></div>";
                }

                $html .= "
                    <tr$row_style>
                        <td class='text-center'><strong>{$interno['ipen']}</strong></td>
                        <td>{$nome_exib}</td>
                        <td class='text-center local-info'>{$local}</td>
                        <td class='text-center'>{$situacao}</td>
                        <td class='text-center'>{$regalia}</td>
                        <td>{$roupa_civil_info}</td>
                    </tr>";
            }

            $html .= '
                    </tbody>
                </table>

                <div style="margin-top: 30px; padding: 15px; background: #f8f9fa; border-radius: 5px; font-size: 0.8em;">
                    <strong>Relatório gerado em:</strong> ' . date('d/m/Y H:i:s') . '<br>
                    <strong>Filtros aplicados:</strong> ' . (!empty(array_filter($_GET, function ($v, $k) {
                return strpos($k, 'data_') === false && $k !== 'execute_print' && $k !== 'mode' && !empty($v);
            }, ARRAY_FILTER_USE_BOTH)) ? 'Sim' : 'Nenhum') . '<br>
                    <strong>Período:</strong> ' . (!empty($_GET['data_inicio']) ? date('d/m/Y', strtotime($_GET['data_inicio'])) : 'Desde o início') . ' à ' . (!empty($_GET['data_fim']) ? date('d/m/Y', strtotime($_GET['data_fim'])) : 'Data atual') . '
                </div>
            </body>
            </html>';

            echo $html;
            exit;
        } catch (Exception $e) {
            echo '<h2>Erro ao gerar relatório</h2><p>' . $e->getMessage() . '</p>';
            exit;
        }
    } else {

        // Consulta Principal
        $st_p = $pdo->prepare("SELECT * " . $sql_base . " ORDER BY $order_sql");
        $st_p->execute($params);
        $data_p = $st_p->fetchAll();

        $txt_filtro = "FILTROS APLICADOS";
        if ($sort_by == 'nome') $txt_filtro = "ALFABÉTICA";
        if ($sort_by == 'kit') $txt_filtro = "NUMÉRICA";
        if ($mode == 'lavanderia') $txt_filtro = "LAVANDERIA";
        if ($mode == 'filtros') $txt_filtro = "FILTRO CUSTOMIZADO";
        if ($mode == 'vagas') $txt_filtro = "KITS DISPONÍVEIS";
        if ($mode == 'sem_kit') $txt_filtro = "SEM KIT CADASTRADO";
        if ($mode == 'conflitos') $txt_filtro = "KIT's REPETIDOS";
        if ($mode == 'regalias_fechado') $txt_filtro = "REGALIAS FECHADO";
        if ($mode == 'regalias_semiaberto') $txt_filtro = "REGALIAS SEMIABERTO";

        $titulo_relatorio = "RELATÓRIO DE INTERNOS - $txt_filtro - " . date("d/m/Y");
?>
        <!DOCTYPE html>
        <html lang="pt-br">

        <head>
            <meta charset="UTF-8">
            <title><?= $titulo_relatorio ?></title>
            <link rel="icon" type="image/svg+xml" href="../favicon.svg">
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <style>
                @page {
                    size: A4 landscape;
                    margin: 0.5cm;
                }

                body {
                    font-size: 9pt;
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    background: white;
                    padding: 15px;
                    color: black;
                }

                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 20px;
                }

                th,
                td {
                    border: 1px solid black !important;
                    padding: 4px 6px !important;
                }

                th {
                    background: #e9ecef !important;
                    font-weight: bold;
                    text-align: center;
                    text-transform: uppercase;
                    font-size: 0.85rem;
                }

                .row-laranja {
                    background-color: #fff3e0 !important;
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                }

                .row-verde {
                    background-color: #e8f5e9 !important;
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                }

                .row-repetido {
                    background-color: #ced4da !important;
                    font-weight: bold;
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                }

                .tam-especial {
                    background-color: #000 !important;
                    color: #fff !important;
                    font-weight: bold;
                    text-align: center;
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                }

                .row-social {
                    background-color: #f3e5f5 !important;
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                }

                .nome-social-destaque {
                    font-weight: 900;
                    text-transform: uppercase;
                    font-style: italic;
                }

                h4 {
                    font-weight: 800;
                    text-transform: uppercase;
                    margin-bottom: 0;
                }

                h5 {
                    border-bottom: 2px solid black;
                    margin-top: 20px;
                    text-transform: uppercase;
                    font-size: 10pt;
                    font-weight: bold;
                }

                /* Evitar quebra de página em separadores de setor */
                .sector-separator {
                    page-break-inside: avoid;
                    page-break-after: avoid;
                    break-inside: avoid;
                }
            </style>
        </head>

        <body onload="window.print();">
            <div class="d-flex justify-content-between align-items-end mb-3 border-bottom pb-2">
                <h4><?= $titulo_relatorio ?></h4>
                <div class="text-end small">
                    <div>Sistema Prisional Integrado</div>
                    <div>Emitido em: <?= date("d/m/Y H:i:s") ?></div>
                </div>
            </div>

            <?php if ($mode === 'vagas'): ?>
                <table border="1">
                    <tbody><?php $chunk = array_chunk($livres_p, 18);
                            foreach ($chunk as $r) {
                                echo "<tr>";
                                foreach ($r as $v) echo "<td align='center'>$v</td>";
                                echo "</tr>";
                            } ?></tbody>
                </table>
            <?php elseif ($mode === 'sem_kit'): ?>
                <!-- CORRIGIDO: Adicionado LOCAL -->
                <table border="1">
                    <thead>
                        <tr>
                            <th>IPEN</th>
                            <th>NOME / NOME SOCIAL</th>
                            <th>LOCAL</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sem_p as $s) {
                            $ns = !empty($s['nome_social']) ? "<b>{$s['nome_social']}</b><br><small>({$s['nome']})</small>" : $s['nome'];
                            echo "<tr>
                            <td width='100' align='center'>{$s['ipen']}</td>
                            <td>{$ns}</td>
                            <td align='center' width='100'>{$s['galeria']}{$s['bloco']}-{$s['res']}</td>
                          </tr>";
                        } ?>
                    </tbody>
                </table>
            <?php elseif ($mode === 'conflitos'): ?>
                <div class="alert alert-light border mb-2 py-1 small">
                    <strong>Regra de Conflito:</strong> Galeria G ignorada. Semi-Aberto (S) não conflita com Fechado.
                </div>
                <table border="1">
                    <thead>
                        <tr>
                            <th>KIT</th>
                            <th>IPEN</th>
                            <th>NOME / NOME SOCIAL</th>
                            <th>LOCAL</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rep_lista as $r) {
                            $ns = !empty($r['nome_social']) ? "<b>{$r['nome_social']}</b>" : $r['nome'];
                            echo "<tr><td align='center' width='80'><b>{$r['kit']}</b></td><td width='100'>{$r['ipen']}</td><td>{$ns}</td><td align='center'>{$r['galeria']}{$r['bloco']}-{$r['res']}</td></tr>";
                        } ?>
                    </tbody>
                </table>
            <?php elseif ($mode === 'regalias_fechado'): ?>
                <div class="alert alert-light border mb-2 py-1 small">
                    <strong>Regra de Regalias:</strong> Todas as galerias do regime fechado (A, B, C, D, E, F, G, H, T).
                </div>

                <!-- Resumo de Regalias -->
                <div class="row mb-3">
                    <div class="col-6">
                        <div class="card border-success">
                            <div class="card-body text-center p-2">
                                <h4 class="text-success mb-0"><?= count($regalias_disponiveis) ?></h4>
                                <small class="text-muted">Disponíveis (1-35)</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="card border-warning">
                            <h4 class="text-warning mb-0"><?= count($regalias_ocupadas) ?></h4>
                            <small class="text-muted">Ocupadas</small>
                        </div>
                    </div>
                    <!-- Lista de Regalias Ocupadas -->
                    <h5 class="text-center mb-3">REGALIAS ATRIBUÍDAS</h5>
                    <table border="1">
                        <thead>
                            <tr>
                                <th width="80">IPEN</th>
                                <th>Nome Completo</th>
                                <th width="100">Local</th>
                                <th width="80">Kit Padrão</th>
                                <th width="80">Kit Regalia</th>
                                <th width="100">Setor</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $current_sector = '';
                            usort($regalias_fechado_tela, function ($a, $b) {
                                return strcmp($a['regalia_setor'], $b['regalia_setor']);
                            });
                            foreach ($regalias_fechado_tela as $reg):
                                $nome_exib = !empty($reg['nome_social']) ? "<b>{$reg['nome_social']}</b><br><small>({$reg['nome']})</small>" : $reg['nome'];

                                // Adicionar separador de setor
                                if ($current_sector !== $reg['regalia_setor']) {
                                    $current_sector = $reg['regalia_setor'];
                                    if ($current_sector !== '') {
                            ?>
                                        <tr class="sector-separator">
                                            <td colspan="6" align="center" style="background-color: #e9ecef; font-weight: bold; border-top: 2px solid #dee2e6;">
                                                <i class="fas fa-briefcase mr-1"></i> SETOR: <?= $current_sector ?>
                                            </td>
                                        </tr>
                                <?php
                                    }
                                }
                                ?>
                                <tr>
                                    <td align="center"><?= $reg['ipen'] ?></td>
                                    <td><?= $nome_exib ?></td>
                                    <td align="center"><?= "{$reg['galeria']}{$reg['bloco']}-{$reg['res']}" ?></td>
                                    <td align="center"><?= $reg['kit'] ?: '-' ?></td>
                                    <td align="center" class="font-weight-bold text-warning"><?= $reg['regalia_kit'] ?: '-' ?></td>
                                    <td align="center"><?= $reg['regalia_setor'] ?: '-' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                <?php elseif ($mode === 'regalias_semiaberto'): ?>
                    <div class="alert alert-light border mb-2 py-1 small">
                        <strong>Regra de Regalias:</strong> Apenas galeria do regime semiaberto (S).
                    </div>

                    <!-- Resumo de Regalias -->
                    <div class="row mb-3">
                        <div class="col-6">
                            <div class="card border-success">
                                <div class="card-body text-center p-2">
                                    <h4 class="text-success mb-0"><?= count($regalias_disponiveis_semi) ?></h4>
                                    <small class="text-muted">Disponíveis (1-50)</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="card border-warning">
                                <h4 class="text-warning mb-0"><?= count($regalias_ocupadas_semi) ?></h4>
                                <small class="text-muted">Ocupadas</small>
                            </div>
                        </div>
                        <!-- Lista de Regalias Ocupadas -->
                        <h5 class="text-center mb-3">REGALIAS ATRIBUÍDAS</h5>
                        <table border="1">
                            <thead>
                                <tr>
                                    <th width="80">IPEN</th>
                                    <th>Nome Completo</th>
                                    <th width="100">Local</th>
                                    <th width="80">Kit Padrão</th>
                                    <th width="80">Kit Regalia</th>
                                    <th width="100">Setor</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $current_sector = '';
                                usort($regalias_semiaberto_tela, function ($a, $b) {
                                    return strcmp($a['regalia_setor'], $b['regalia_setor']);
                                });
                                foreach ($regalias_semiaberto_tela as $reg):
                                    $nome_exib = !empty($reg['nome_social']) ? "<b>{$reg['nome_social']}</b><br><small>({$reg['nome']})</small>" : $reg['nome'];

                                    // Adicionar separador de setor
                                    if ($current_sector !== $reg['regalia_setor']) {
                                        $current_sector = $reg['regalia_setor'];
                                        if ($current_sector !== '') {
                                ?>
                                            <tr class="sector-separator">
                                                <td colspan="6" align="center" style="background-color: #e9ecef; font-weight: bold; border-top: 2px solid #dee2e6;">
                                                    <i class="fas fa-briefcase mr-1"></i> SETOR: <?= $current_sector ?>
                                                </td>
                                            </tr>
                                    <?php
                                        }
                                    }
                                    ?>
                                    <tr>
                                        <td align="center"><?= $reg['ipen'] ?></td>
                                        <td><?= $nome_exib ?></td>
                                        <td align="center"><?= "{$reg['galeria']}{$reg['bloco']}-{$reg['res']}" ?></td>
                                        <td align="center"><?= $reg['kit'] ?: '-' ?></td>
                                        <td align="center" class="font-weight-bold text-warning"><?= $reg['kit'] ?: '-' ?></td>
                                        <td align="center"><?= $reg['regalia_setor'] ?: '-' ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                    <?php else: ?>
                        <!-- Tabela principal para relatórios completos (alfabética, numérica, etc.) -->
                        <table border="1">
                            <thead>
                                <tr>
                                    <th width="80">IPEN</th>
                                    <th>NOME / SOCIAL</th>
                                    <th width="100">LOCAL</th>
                                    <?php if (!$isLavanderia): ?><th>SITUAÇÃO / SETOR</th>
                                        <th width="50">REG.</th><?php endif; ?>
                                    <th width="80">COR</th>
                                    <th width="60">KIT</th>
                                    <?php if (!$isLavanderia): ?><th width="60">KIT REG</th>
                                        <th width="50">TAM.</th><?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data_p as $i):
                                    $rep_class = in_array($i['kit'], $repetidos_ids) ? 'row-repetido' : '';
                                    $tam_class = ($i['tamanho_kit'] != 'G') ? 'tam-especial' : '';
                                    $kx = ($i['regalia'] == 'S' && !$i['regalia_kit']) ? $i['kit'] : $i['regalia_kit'];
                                    $sx = ($i['regalia'] == 'S') ? $i['situacao'] . " - " . $i['regalia_setor'] : $i['situacao'];
                                    $k_fin = ($i['regalia'] == 'S' && $i['regalia_kit'] > 0) ? $i['regalia_kit'] : $i['kit'];
                                    $cor_css = mb_strtolower($i['cor_roupa'] ?: 'laranja');

                                    $hasSocial = !empty($i['nome_social']);
                                    $social_class = $hasSocial ? 'row-social' : '';
                                    $nome_exib = $hasSocial ? "<span class='nome-social-destaque'>{$i['nome_social']}</span> <small>({$i['nome']})</small>" : "<b>{$i['nome']}</b>";
                                ?>
                                    <tr class="row-<?= $cor_css ?> <?= $rep_class ?> <?= $social_class ?>">
                                        <td align="center"><?= $i['ipen'] ?></td>
                                        <td><?= $nome_exib ?></td>
                                        <td align="center"><?= "{$i['galeria']}{$i['bloco']}-{$i['res']}" ?></td>
                                        <?php if (!$isLavanderia): ?><td><?= $sx ?></td>
                                            <td align="center"><?= $i['regalia'] ?></td><?php endif; ?>
                                        <td align="center"><?= $i['cor_roupa'] ?></td>
                                        <td align="center"><b><?= $isLavanderia ? $k_fin : $i['kit'] ?></b></td>
                                        <?php if (!$isLavanderia): ?><td align="center"><?= $kx ?></td>
                                            <td class="<?= $tam_class ?>"><?= $i['tamanho_kit'] ?></td><?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <?php if (!$isLavanderia && $mode != 'filtros' && $mode != 'vagas' && $mode != 'sem_kit' && $mode != 'conflitos' && $mode != 'regalias_fechado'): ?>
                            <div class="row">
                                <div class="col-4">
                                    <h5>Sem Kit Cadastrado (<?= count($sem_p) ?>)</h5>
                                    <!-- CORRIGIDO: Adicionado LOCAL -->
                                    <table border="1" class="small table-bordered">
                                        <thead>
                                            <tr>
                                                <th>IPEN</th>
                                                <th>Nome</th>
                                                <th>Loc</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach (array_slice($sem_p, 0, 10) as $sk) {
                                                echo "<tr><td>{$sk['ipen']}</td><td>" . ($sk['nome_social'] ?: $sk['nome']) . "</td><td>{$sk['galeria']}{$sk['bloco']}-{$sk['res']}</td></tr>";
                                            } ?>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="col-4">
                                    <h5>Kits Repetidos (<?= count($rep_lista) ?>)</h5>
                                    <table border="1" class="small table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Kit</th>
                                                <th>Nome</th>
                                                <th>Loc</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach (array_slice($rep_lista, 0, 10) as $rk) {
                                                echo "<tr><td width='40' align='center'><b>{$rk['kit']}</b></td><td>" . ($rk['nome_social'] ?: $rk['nome']) . "</td><td>{$rk['galeria']}{$rk['bloco']}-{$rk['res']}</td></tr>";
                                            } ?>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="col-4">
                                    <h5>KITS DISPONÍVEIS (Total: <?= count($livres_p) ?>)</h5>
                                    <div style="font-size: 7pt; line-height: 1.1; text-align: justify; border: 1px solid #000; padding: 5px;">
                                        <?= implode(", ", $livres_p) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
        </body>

        </html>
<?php
        exit;
    }
}
// --- 4. PREPARAÇÃO DA VISUALIZAÇÃO ---
$limit = 100;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Buscar dados principais usando API - USANDO API
// Preparar parâmetros para a API
$api_params = [
    'sort_by' => $sort_by ?? 'nome',
    'sort_order' => $sort_order ?? 'ASC'
];

// Aplicar filtros da query $sql_base
if (!empty($f['situacao'])) $api_params['situacao'] = $f['situacao'];
if (!empty($f['galeria'])) $api_params['galeria'] = $f['galeria'];
if (!empty($f['bloco'])) $api_params['bloco'] = $f['bloco'];
if (!empty($f['res'])) $api_params['res'] = $f['res'];
if (!empty($f['regalia'])) $api_params['regalia'] = $f['regalia'];
if (!empty($f['cor'])) $api_params['cor_roupa'] = $f['cor'];
if (!empty($f['setor'])) $api_params['regalia_setor'] = $f['setor'];
if (!empty($f['tam'])) $api_params['tamanho_kit'] = $f['tam'];
if (!empty($f['status'])) $api_params['status'] = $f['status'];
if (!empty($f['kit_num'])) $api_params['kit'] = $f['kit_num'];
if (!empty($f['regkit_num'])) $api_params['regalia_kit'] = $f['regkit_num'];
if ($f['kit_f'] === 'com') $api_params['kit_greater_than'] = 0;
if ($f['kit_f'] === 'sem') $api_params['kit_is_null'] = 1;

$result = callInternosAPI('GET', $api_params);

$todos_internos = ($result['success'] and is_array($result['data'])) ? $result['data'] : [];
$count_total = count($todos_internos);

// Aplicar paginação manual
$internos = array_slice($todos_internos, $offset, $limit);
$total_paginas = ceil($count_total / $limit);

// Dados estatísticos para Cards - AGORA USANDO DADOS FILTRADOS
$k_livres_tela = $livres_p ?? []; // Garantir que seja um array
$duplicados_ids = $repetidos_ids ?? [];

// Queries para Offcanvas - AGORA USANDO DADOS FILTRADOS
// Recalcular sem kit e repetidos baseado nos dados filtrados
$sem_k_tela = array_filter($todos_internos, function ($interno) {
    return $interno['kit'] == 0 || $interno['kit'] === null;
});

// Recalcular repetidos baseado nos dados filtrados
$fechado_kits_count_filtrados = [];
$semi_kits_count_filtrados = [];

foreach ($todos_internos as $interno) {
    if ($interno['kit'] > 0) {
        if ($interno['galeria'] !== 'S' && $interno['galeria'] !== 'G') {
            $fechado_kits_count_filtrados[] = $interno['kit'];
        } elseif ($interno['galeria'] === 'S') {
            $semi_kits_count_filtrados[] = $interno['kit'];
        }
    }
}

$ids_fechado_filtrados = array_unique(array_filter(array_count_values($fechado_kits_count_filtrados), function ($count) {
    return $count > 1;
}));
$ids_fechado_filtrados = array_keys($ids_fechado_filtrados);

$ids_semi_filtrados = array_unique(array_filter(array_count_values($semi_kits_count_filtrados), function ($count) {
    return $count > 1;
}));
$ids_semi_filtrados = array_keys($ids_semi_filtrados);

$repet_tela = array_filter($todos_internos, function ($interno) use ($ids_fechado_filtrados, $ids_semi_filtrados) {
    if ($interno['kit'] > 0) {
        if ($interno['galeria'] !== 'S' && $interno['galeria'] !== 'G') {
            return in_array($interno['kit'], $ids_fechado_filtrados);
        } elseif ($interno['galeria'] === 'S') {
            return in_array($interno['kit'], $ids_semi_filtrados);
        }
    }
    return false;
});

// Queries para Popular os Comboboxes de Filtro - USANDO API
// Buscar galerias - USANDO API
$result_galerias = callInternosAPI('GET');
$galerias_unicas = array_unique(array_filter(array_column(($result_galerias['success'] && is_array($result_galerias['data'])) ? $result_galerias['data'] : [], 'galeria')));
sort($galerias_unicas);
$galerias_db = $galerias_unicas;

// Buscar blocos - USANDO API
$result_blocos = callInternosAPI('GET');
$blocos_unicas = array_unique(array_filter(array_column(($result_blocos['success'] && is_array($result_blocos['data'])) ? $result_blocos['data'] : [], 'bloco')));
sort($blocos_unicas);
$blocos_db = $blocos_unicas;

// Buscar setores - USANDO API
$result_setores = callInternosAPI('GET');
$setores_unicas = array_unique(array_filter(array_column(($result_setores['success'] && is_array($result_setores['data'])) ? $result_setores['data'] : [], 'regalia_setor')));
sort($setores_unicas);
$setores_db = $setores_unicas;

function generateSortLink($c, $d)
{
    global $sort_by, $sort_order;
    $new_order = ($sort_by == $c && $sort_order == 'ASC') ? 'DESC' : 'ASC';
    $params = $_GET;
    $params['sort_by'] = $c;
    $params['sort_order'] = $new_order;
    $params['page'] = 1;
    $qs = http_build_query($params);
    $icon = '<i class="fas fa-sort text-muted small ml-1" style="opacity:0.3"></i>';
    if ($sort_by == $c) {
        $icon = ($sort_order == 'ASC') ? '<i class="fas fa-sort-up ml-1"></i>' : '<i class="fas fa-sort-down ml-1"></i>';
    }
    return "<a href='paginas/censura_rouparia_numeros.php?{$qs}' class='text-white text-decoration-none' onclick=\"loadPage(this.href); return false;\">{$d} {$icon}</a>";
}

// --- LÓGICA ROUPA CIVIL ---

// Cadastro de roupa civil
if (isset($_POST['db_action']) && $_POST['db_action'] === 'cadastrar_roupa_civil') {
    $ipen = (int)$_POST['ipen'];
    $pecas_json = trim($_POST['pecas_json']);

    if (empty($ipen) || empty($pecas_json)) {
        echo json_encode(['success' => false, 'error' => 'IPEN e peças são obrigatórios.']);
        exit;
    }

    // Usar API de roupa civil para cadastrar
    $result = callRoupariaCivilAPI('POST', [], [
        'ipen' => $ipen,
        'pecas' => $pecas_json,
        'criado_por' => $_SESSION['nome_usuario'] ?? 'Sistema'
    ]);

    if ($result['success']) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $result['error']]);
    }
    exit;
}

// Listar registros de roupa civil
if (isset($_POST['db_action']) && $_POST['db_action'] === 'listar_roupa_civil') {
    // Usar API de roupa civil
    $result = callRoupariaCivilAPI('GET');
    $rows = [];

    foreach (($result['success'] && is_array($result['data'])) ? $result['data'] : [] as $row) {
        $nome_exib = mb_strtoupper($row['nome']);
        $pecas_json = json_decode($row['pecas'], true);
        $pecas_formatadas = htmlspecialchars($row['pecas']);
        if ($pecas_json && (isset($pecas_json['predefinidos']) || isset($pecas_json['outros']))) {
            $itens = [];
            foreach (($pecas_json['predefinidos'] ?? []) as $item) {
                $obs = !empty($item['observacao']) ? ' (' . htmlspecialchars($item['observacao']) . ')' : '';
                $itens[] = htmlspecialchars($item['quantidade'] . 'x ' . $item['tipo']) . $obs;
            }
            foreach (($pecas_json['outros'] ?? []) as $item) {
                $obs = !empty($item['observacao']) ? ' (' . htmlspecialchars($item['observacao']) . ')' : '';
                $itens[] = htmlspecialchars($item['quantidade'] . 'x ' . $item['tipo']) . $obs;
            }
            $pecas_formatadas = '<div class="pecas-formatadas">' . implode(', ', $itens) . '</div>';
        }

        $rows[] = [
            'id' => (int)$row['id'],
            'ipen' => htmlspecialchars((string)$row['ipen']),
            'nome_exib' => $nome_exib,
            'pecas_formatadas' => $pecas_formatadas,
            'criado_por' => htmlspecialchars((string)$row['criado_por']),
            'data_fmt' => date('d/m/Y H:i', strtotime($row['criado_em'])),
            'is_inativo' => isset($row['interno_status']) && $row['interno_status'] === 'I',
        ];
    }

    echo json_encode(['success' => true, 'rows' => $rows]);
    exit;
}

// Listagem para modal de etiquetas (seleção múltipla)
if (isset($_POST['db_action']) && $_POST['db_action'] === 'listar_etiquetas_roupa_civil') {
    // Usar API de roupa civil
    $result = callRoupariaCivilAPI('GET');
    $rows = [];

    foreach (($result['success'] && is_array($result['data'])) ? $result['data'] : [] as $row) {
        $nome_exib = !empty($row['interno_nome_social']) ? $row['interno_nome_social'] : $row['interno_nome'];
        $nome_exib = htmlspecialchars($nome_exib);

        $rows[] = [
            'id' => (int)$row['id'],
            'ipen' => htmlspecialchars((string)$row['ipen']),
            'pecas' => htmlspecialchars($row['pecas']),
            'data_fmt' => date('d/m/Y H:i', strtotime($row['criado_em'])),
            'criado_por' => htmlspecialchars((string)$row['criado_por']),
            'nome_exib' => $nome_exib,
            'is_inativo' => isset($row['interno_status']) && $row['interno_status'] === 'I',
        ];
    }

    echo json_encode(['success' => true, 'rows' => $rows]);
    exit;
}

// Buscar registros com itens perdidos (sem descrição)
if (isset($_POST['db_action']) && $_POST['db_action'] === 'buscar_itens_perdidos_roupa_civil') {
    // Usar API de roupa civil
    $result = callRoupariaCivilAPI('GET');
    $rows = [];

    foreach (($result['success'] && is_array($result['data'])) ? $result['data'] : [] as $row) {
        $pecas_json = json_decode($row['pecas'], true);
        $tem_item_perdido = false;
        $itens_formatados = [];

        // Verificar se tem itens perdidos
        if ($pecas_json && (isset($pecas_json['predefinidos']) || isset($pecas_json['outros']))) {
            // Verificar itens pré-definidos
            if (isset($pecas_json['predefinidos'])) {
                foreach ($pecas_json['predefinidos'] as $item) {
                    $obs = !empty($item['observacao']) ? ' (' . htmlspecialchars($item['observacao']) . ')' : '';
                    $tipo = !empty($item['tipo']) ? htmlspecialchars($item['tipo']) : '<span class="text-danger">[SEM TIPO]</span>';
                    $itens_formatados[] = htmlspecialchars($item['quantidade']) . 'x ' . $tipo . $obs;

                    if (empty($item['tipo']) || $item['tipo'] === '') {
                        $tem_item_perdido = true;
                    }
                }
            }

            // Verificar itens outros
            if (isset($pecas_json['outros'])) {
                foreach ($pecas_json['outros'] as $item) {
                    $obs = !empty($item['observacao']) ? ' (' . htmlspecialchars($item['observacao']) . ')' : '';
                    $tipo = !empty($item['tipo']) ? htmlspecialchars($item['tipo']) : '<span class="text-danger">[SEM TIPO]</span>';
                    $itens_formatados[] = htmlspecialchars($item['quantidade']) . 'x ' . $tipo . $obs;

                    if (empty($item['tipo']) || $item['tipo'] === '') {
                        $tem_item_perdido = true;
                    }
                }
            }
        }

        // Adicionar apenas se tiver item perdido
        if ($tem_item_perdido) {
            $rows[] = [
                'id' => (int)$row['id'],
                'ipen' => htmlspecialchars((string)$row['ipen']),
                'nome_exib' => mb_strtoupper($row['nome']),
                'pecas_formatadas' => '<div class="pecas-formatadas">' . implode(', ', $itens_formatados) . '</div>',
                'criado_por' => htmlspecialchars((string)$row['criado_por']),
                'data_fmt' => date('d/m/Y H:i', strtotime($row['criado_em'])),
                'is_inativo' => isset($row['interno_status']) && $row['interno_status'] === 'I',
                'tem_item_perdido' => true
            ];
        }
    }

    echo json_encode(['success' => true, 'rows' => $rows, 'total' => count($rows)]);
    exit;
}

// Obter registro de roupa civil para edição
if (isset($_POST['db_action']) && $_POST['db_action'] === 'obter_roupa_civil') {
    $id = (int)$_POST['id'];

    if (empty($id)) {
        echo json_encode(['success' => false, 'error' => 'ID é obrigatório.']);
        exit;
    }

    // Usar API para buscar registro específico
    $result = callRoupariaCivilAPI('GET', ['id' => $id]);

    if ($result['success'] && !empty($result['data'])) {
        echo json_encode(['success' => true, 'data' => $result['data'][0]]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Registro não encontrado.']);
    }
    exit;
}

// Editar registro de roupa civil
if (isset($_POST['db_action']) && $_POST['db_action'] === 'editar_roupa_civil') {
    $id = (int)$_POST['id'];
    $ipen = (int)$_POST['ipen'];
    $pecas_json = trim($_POST['pecas_json']);

    if (empty($id) || empty($ipen) || empty($pecas_json)) {
        echo json_encode(['success' => false, 'error' => 'ID, IPEN e peças são obrigatórios.']);
        exit;
    }

    // Usar API para atualizar
    $result = callRoupariaCivilAPI('PUT', [], [
        'id' => $id,
        'ipen' => $ipen,
        'pecas' => $pecas_json
    ]);

    if ($result['success']) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $result['error']]);
    }
    exit;
}

// Excluir registro de roupa civil
if (isset($_POST['db_action']) && $_POST['db_action'] === 'excluir_roupa_civil') {
    $id = (int)$_POST['id'];

    if (empty($id)) {
        echo json_encode(['success' => false, 'error' => 'ID é obrigatório.']);
        exit;
    }

    // Usar API para excluir
    $result = callRoupariaCivilAPI('DELETE', ['id' => $id]);

    if ($result['success']) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $result['error']]);
    }
    exit;
}

// Impressão de relatório integrado - Internos com Roupa Civil
if (isset($_GET['execute_print']) && $_GET['mode'] === 'roupa_civil') {
    // BLOQUEAR USUÁRIO ROUPARIA DE ACESSAR RELATÓRIOS
    if (isset($_SESSION['user_nome']) && $_SESSION['user_nome'] === 'rouparia') {
        die('<div style="padding: 50px; text-align: center; font-family: Arial; color: #dc3545;">
            <h2><i class="fas fa-exclamation-triangle"></i> Acesso Negado</h2>
            <p>Usuário rouparia não tem permissão para acessar relatórios.</p>
            <p><a href="javascript:history.back()" class="btn btn-primary">Voltar</a></p>
        </div>');
    }

    try {
        // Query para buscar apenas internos com roupa civil cadastrada
        $sql = "SELECT
            i.ipen,
            i.nome,
            i.nome_social,
            i.galeria,
            i.bloco,
            i.res,
            i.situacao,
            i.status,
            i.regalia,
            i.regalia_setor,
            i.cor_roupa,
            rc.pecas as roupa_civil_pecas,
            rc.criado_por as roupa_civil_criado_por,
            rc.criado_em as roupa_civil_data
        FROM internos i
        INNER JOIN internos_rouparia_civil rc ON i.ipen = rc.ipen";

        // Aplicar filtros se houver
        $params = [];
        if (!empty($_GET['galeria'])) {
            $sql .= " AND i.galeria = ?";
            $params[] = $_GET['galeria'];
        }
        if (!empty($_GET['bloco'])) {
            $sql .= " AND i.bloco = ?";
            $params[] = $_GET['bloco'];
        }
        if (!empty($_GET['situacao'])) {
            $sql .= " AND i.situacao = ?";
            $params[] = $_GET['situacao'];
        }
        if (!empty($_GET['regalia'])) {
            if ($_GET['regalia'] === 'S') {
                $sql .= " AND i.regalia = 'S'";
            } elseif ($_GET['regalia'] === 'N') {
                $sql .= " AND i.regalia = 'N'";
            }
        }
        if (!empty($_GET['cor'])) {
            $sql .= " AND i.cor_roupa = ?";
            $params[] = $_GET['cor'];
        }
        if (!empty($_GET['criado_por'])) {
            $sql .= " AND rc.criado_por LIKE ?";
            $params[] = "%{$_GET['criado_por']}%";
        }

        // Filtro de período
        if (!empty($_GET['data_inicio'])) {
            $sql .= " AND rc.criado_em >= ?";
            $params[] = $_GET['data_inicio'] . ' 00:00:00';
        }
        if (!empty($_GET['data_fim'])) {
            $sql .= " AND rc.criado_em <= ?";
            $params[] = $_GET['data_fim'] . ' 23:59:59';
        }

        // Filtro de pesquisa por interno (IPEN, nome ou nome social)
        if (!empty($_GET['pesquisa_interno'])) {
            $pesquisa = trim($_GET['pesquisa_interno']);
            $sql .= " AND (i.ipen LIKE ? OR i.nome LIKE ? OR i.nome_social LIKE ?)";
            $params[] = "%{$pesquisa}%";
            $params[] = "%{$pesquisa}%";
            $params[] = "%{$pesquisa}%";
        }

        $sql .= " ORDER BY i.nome ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Estatísticas
        $total_internos = count($dados);

        // Contagem de peças por tipo
        $estatisticas_pecas = [
            'camiseta' => 0,
            'bermuda' => 0,
            'blusa' => 0,
            'casaco' => 0,
            'calca' => 0,
            'jaqueta' => 0,
            'bone' => 0,
            'tenis' => 0,
            'sapato' => 0,
            'meia' => 0,
            'luva' => 0,
            'bolsa' => 0,
            'mochila' => 0,
            'cueca' => 0,
            'chapeu' => 0,
            'outros' => 0
        ];

        foreach ($dados as $interno) {
            if (!empty($interno['roupa_civil_pecas'])) {
                $pecas_json = json_decode($interno['roupa_civil_pecas'], true);
                if ($pecas_json) {
                    if (!empty($pecas_json['predefinidos'])) {
                        foreach ($pecas_json['predefinidos'] as $item) {
                            $tipo = strtolower($item['tipo']);
                            if (isset($estatisticas_pecas[$tipo])) {
                                $estatisticas_pecas[$tipo] += $item['quantidade'];
                            }
                        }
                    }
                    if (!empty($pecas_json['outros'])) {
                        foreach ($pecas_json['outros'] as $item) {
                            $estatisticas_pecas['outros'] += $item['quantidade'];
                        }
                    }
                }
            }
        }

        // HTML para impressão
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Relatório Detalhado - Roupa Civil</title>
            <style>
                body { font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; margin: 20px; color: #333; }
                h1, h2, h3 { color: #2c3e50; margin-bottom: 20px; }
                h1 { text-align: center; border-bottom: 3px solid #3498db; padding-bottom: 10px; }
                h2 { border-bottom: 2px solid #95a5a6; padding-bottom: 5px; }
                .header-info { display: flex; justify-content: space-between; margin-bottom: 30px; background: #f8f9fa; padding: 15px; border-radius: 5px; }
                .filters-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px; margin-bottom: 20px; padding: 15px; background: #e9ecef; border-radius: 5px; }
                .filter-item { font-size: 0.85em; }
                .filter-label { font-weight: bold; color: #495057; }
                .filter-value { color: #007bff; }
                .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin-bottom: 30px; }
                .stat-card { background: white; border: 1px solid #dee2e6; border-radius: 8px; padding: 15px; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
                .stat-number { font-size: 2em; font-weight: bold; color: #3498db; }
                .stat-label { font-size: 0.9em; color: #6c757d; text-transform: uppercase; margin-top: 5px; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 0.85em; }
                th, td { border: 1px solid #dee2e6; padding: 8px; text-align: left; vertical-align: top; }
                th { background: #f8f9fa; font-weight: 600; text-transform: uppercase; font-size: 0.75em; }
                tr:nth-child(even) { background: #f8f9fa; }
                .text-center { text-align: center; }
                .badge { display: inline-block; padding: 3px 8px; border-radius: 12px; font-size: 0.7em; font-weight: bold; text-transform: uppercase; }
                .badge-success { background: #d4edda; color: #155724; }
                .badge-warning { background: #fff3cd; color: #856404; }
                .badge-danger { background: #f8d7da; color: #721c24; }
                .badge-info { background: #d1ecf1; color: #0c5460; }
                .roupa-civil-info { background: #e8f5e8; border-left: 4px solid #28a745; padding: 8px; margin: 2px 0; }
                .sem-roupa-civil { background: #fff5f5; border-left: 4px solid #dc3545; padding: 8px; margin: 2px 0; }
                .pecas-lista { margin: 0; padding-left: 15px; }
                .pecas-item { margin: 2px 0; font-size: 0.8em; }
                .local-info { font-weight: bold; color: #495057; }
                .pecas-summary { margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px; }
                .pecas-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 10px; margin-top: 10px; }
                .peca-stat { text-align: center; padding: 8px; background: white; border-radius: 4px; border: 1px solid #dee2e6; }
                .peca-stat .numero { font-size: 1.2em; font-weight: bold; color: #28a745; }
                .peca-stat .nome { font-size: 0.8em; color: #6c757d; text-transform: uppercase; }
                @media print {
                    body { margin: 0; font-size: 12px; }
                    .no-print { display: none; }
                    table { font-size: 10px; }
                    th, td { padding: 4px; }
                    .pecas-summary { page-break-inside: avoid; }
                }
            </style>
        </head>
        <body>
            <h1>SIGEP - Sistema Prisional Integrado</h1>
            <h2>Relatório Detalhado: Kits de Roupa Civil</h2>

            <div class="header-info">
                <div>
                    <strong>Data de Emissão:</strong> ' . date('d/m/Y H:i:s') . '<br>
                    <strong>Usuário:</strong> ' . ($_SESSION['nome_usuario'] ?? 'Sistema') . '
                </div>
                <div>
                    <strong>Período:</strong> ' . (!empty($_GET['data_inicio']) ? date('d/m/Y', strtotime($_GET['data_inicio'])) : 'Todos') . ' à ' . (!empty($_GET['data_fim']) ? date('d/m/Y', strtotime($_GET['data_fim'])) : 'Todos') . '<br>
                    <strong>Registros Filtrados:</strong> ' . $total_internos . '
                </div>
            </div>

            ' . (!empty($_GET['galeria']) || !empty($_GET['bloco']) || !empty($_GET['situacao']) || !empty($_GET['regalia']) || !empty($_GET['cor']) || !empty($_GET['criado_por']) || !empty($_GET['apenas_com_kit']) ? '
            <div class="filters-grid">
                <div class="filter-item">
                    <span class="filter-label">Galeria:</span>
                    <span class="filter-value">' . ($_GET['galeria'] ?? 'Todas') . '</span>
                </div>
                <div class="filter-item">
                    <span class="filter-label">Bloco:</span>
                    <span class="filter-value">' . ($_GET['bloco'] ?? 'Todos') . '</span>
                </div>
                <div class="filter-item">
                    <span class="filter-label">Situação:</span>
                    <span class="filter-value">' . ($_GET['situacao'] ?? 'Todas') . '</span>
                </div>
                <div class="filter-item">
                    <span class="filter-label">Regalia:</span>
                    <span class="filter-value">' . (isset($_GET['regalia']) ? ($_GET['regalia'] === 'S' ? 'Sim' : 'Não') : 'Todas') . '</span>
                </div>
                <div class="filter-item">
                    <span class="filter-label">Cor:</span>
                    <span class="filter-value">' . ($_GET['cor'] ?? 'Todas') . '</span>
                </div>
                <div class="filter-item">
                    <span class="filter-label">Cadastrado por:</span>
                    <span class="filter-value">' . ($_GET['criado_por'] ?? 'Todos') . '</span>
                </div>
                <div class="filter-item">
                    <span class="filter-label">Apenas c/ Kit:</span>
                    <span class="filter-value">' . (isset($_GET['apenas_com_kit']) && $_GET['apenas_com_kit'] === '1' ? 'Sim' : 'Não') . '</span>
                </div>
            </div>
            ' : '') . '

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number">' . $total_internos . '</div>
                    <div class="stat-label">Total de Internos</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">' . $internos_com_roupa_civil . '</div>
                    <div class="stat-label">Com Kit Roupa Civil</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">' . $internos_sem_roupa_civil . '</div>
                    <div class="stat-label">Sem Kit Roupa Civil</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">' . round($internos_com_roupa_civil > 0 ? ($internos_com_roupa_civil / $total_internos) * 100 : 0, 1) . '%</div>
                    <div class="stat-label">Cobertura</div>
                </div>
            </div>

            <div class="pecas-summary">
                <h3>📊 Resumo de Peças Entregues</h3>
                <div class="pecas-grid">
                    ' . implode('', array_map(function ($nome, $quantidade) {
            return "<div class='peca-stat'><div class='numero'>{$quantidade}</div><div class='nome'>{$nome}</div></div>";
        }, array_keys($estatisticas_pecas), array_values($estatisticas_pecas))) . '
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th class="text-center" style="width: 80px;">IPEN</th>
                        <th style="width: 200px;">Nome Completo</th>
                        <th class="text-center" style="width: 100px;">Local</th>
                        <th class="text-center" style="width: 80px;">Situação</th>
                        <th class="text-center" style="width: 60px;">Regalia</th>
                        <th style="width: 300px;">Kit de Roupa Civil</th>
                    </tr>
                </thead>
                <tbody>';

        foreach ($dados as $interno) {
            $nome_exib = !empty($interno['nome_social']) ?
                "<strong>{$interno['nome_social']}</strong><br><small>({$interno['nome']})</small>" :
                "<strong>{$interno['nome']}</strong>";

            // Adicionar badge de status
            $status_badge = $interno['status'] == 'A' ?
                '<span class="badge badge-success ml-2" style="font-size: 0.7em;">ATIVO</span>' :
                '<span class="badge badge-danger ml-2" style="font-size: 0.7em;">INATIVO</span>';
            $nome_exib .= $status_badge;

            $local = "{$interno['galeria']}{$interno['bloco']}-{$interno['res']}";
            $situacao = $interno['situacao'] ?: 'N/A';
            $regalia = $interno['regalia'] == 'S' ?
                "<span class='badge badge-success'>SIM</span>" :
                "<span class='badge badge-danger'>NÃO</span>";

            $roupa_civil_info = '';
            if (!empty($interno['roupa_civil_pecas'])) {
                $pecas_json = json_decode($interno['roupa_civil_pecas'], true);
                $itens_formatados = [];

                if ($pecas_json && (isset($pecas_json['predefinidos']) || isset($pecas_json['outros']))) {
                    // Itens pré-definidos
                    if (!empty($pecas_json['predefinidos'])) {
                        foreach ($pecas_json['predefinidos'] as $item) {
                            $obs = !empty($item['observacao']) ? " <em>({$item['observacao']})</em>" : '';
                            $itens_formatados[] = "<span class='pecas-item'>• {$item['quantidade']}x {$item['tipo']}{$obs}</span>";
                        }
                    }

                    // Itens outros
                    if (!empty($pecas_json['outros'])) {
                        foreach ($pecas_json['outros'] as $item) {
                            $obs = !empty($item['observacao']) ? " <em>({$item['observacao']})</em>" : '';
                            $itens_formatados[] = "<span class='pecas-item'>• {$item['quantidade']}x {$item['tipo']}{$obs}</span>";
                        }
                    }
                }

                $data_cadastro = date('d/m/Y', strtotime($interno['roupa_civil_data']));
                $criado_por = $interno['roupa_civil_criado_por'];

                $roupa_civil_info = "
                    <div class='roupa-civil-info'>
                        <strong>Kit Cadastrado</strong><br>
                        <small>Data: {$data_cadastro} | Por: {$criado_por}</small>
                        <div class='pecas-lista'>" . implode('', $itens_formatados) . "</div>
                    </div>";
            } else {
                $roupa_civil_info = "<div class='sem-roupa-civil'><em>Sem kit cadastrado</em></div>";
            }

            $html .= "
                <tr>
                    <td class='text-center'><strong>{$interno['ipen']}</strong></td>
                    <td>{$nome_exib}</td>
                    <td class='text-center local-info'>{$local}</td>
                    <td class='text-center'>{$situacao}</td>
                    <td class='text-center'>{$regalia}</td>
                    <td>{$roupa_civil_info}</td>
                </tr>";
        }

        $html .= '
                </tbody>
            </table>

            <div style="margin-top: 30px; padding: 15px; background: #f8f9fa; border-radius: 5px; font-size: 0.8em;">
                <strong>Relatório gerado em:</strong> ' . date('d/m/Y H:i:s') . '<br>
                <strong>Filtros aplicados:</strong> ' . (!empty(array_filter($_GET, function ($v, $k) {
            return strpos($k, 'data_') === false && $k !== 'execute_print' && $k !== 'mode' && !empty($v);
        }, ARRAY_FILTER_USE_BOTH)) ? 'Sim' : 'Nenhum') . '<br>
                <strong>Período:</strong> ' . (!empty($_GET['data_inicio']) ? date('d/m/Y', strtotime($_GET['data_inicio'])) : 'Desde o início') . ' à ' . (!empty($_GET['data_fim']) ? date('d/m/Y', strtotime($_GET['data_fim'])) : 'Data atual') . '
            </div>
        </body>
        </html>';

        echo $html;
        exit;
    } catch (Exception $e) {
        echo '<h2>Erro ao gerar relatório</h2><p>' . $e->getMessage() . '</p>';
        exit;
    }
}

// Impressão de Etiquetas - Roupa Civil
if (isset($_GET['execute_print']) && $_GET['mode'] === 'etiquetas_roupa_civil') {
    // BLOQUEAR USUÁRIO ROUPARIA DE ACESSAR RELATÓRIOS
    if (isset($_SESSION['user_nome']) && $_SESSION['user_nome'] === 'rouparia') {
        die('<div style="padding: 50px; text-align: center; font-family: Arial; color: #dc3545;">
            <h2><i class="fas fa-exclamation-triangle"></i> Acesso Negado</h2>
            <p>Usuário rouparia não tem permissão para acessar relatórios.</p>
            <p><a href="javascript:history.back()" class="btn btn-primary">Voltar</a></p>
        </div>');
    }

    try {
        $idsParam = trim($_GET['ids'] ?? '');
        $ids = array_values(array_filter(array_map('intval', explode(',', $idsParam)), function ($v) {
            return $v > 0;
        }));

        if (empty($ids)) {
            echo '<h2>Nenhum registro selecionado para impressão.</h2>';
            exit;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT
            rc.id,
            rc.ipen,
            rc.pecas as roupa_civil_pecas,
            rc.criado_em,
            i.nome,
            i.nome_social,
            i.status
        FROM internos_rouparia_civil rc
        LEFT JOIN internos i ON i.ipen = rc.ipen
        WHERE rc.id IN ($placeholders)
        ORDER BY rc.ipen ASC, rc.criado_em DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($ids);
        $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // HTML para impressão de etiquetas
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Etiquetas - Roupa Civil</title>
            <style>
                @page { size: A4 landscape; margin: 10mm; }
                body { font-family: Arial, Helvetica, sans-serif; font-size: 14px; line-height: 1.15; margin: 0; padding: 8mm; color: #000; }
                .titulo { text-align: center; margin-bottom: 10px; font-size: 16px; font-weight: 700; }
                .etiqueta { display: grid; grid-template-columns: 1.7fr 1.2fr 1fr; column-gap: 8px; align-items: center; border-bottom: 1px solid #666; padding: 4px 2px; min-height: 28px; }
                .bloco-principal { font-size: 1em; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
                .nome-principal { font-weight: 800; font-size: 1.05em; }
                .status { font-size: 0.95em; }
                .itens { font-size: 0.95em; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
                .ultima-verificacao { font-size: 0.95em; text-align: right; white-space: nowrap; }
                .mochila { font-weight: 800; }
            </style>
        </head>
        <body>
            <div class="titulo">
                SIGEP - ETIQUETAS DE ROUPA CIVIL - ' . date('d/m/Y H:i') . '
            </div>';

        foreach ($dados as $interno) {
            $nome = mb_strtoupper($interno['nome_social'] ?: $interno['nome']);
            $ipen = $interno['ipen'];
            $statusInterno = ($interno['status'] ?? '') === 'I' ? 'INATIVO' : 'ATIVO';
            $ultimaVerificacao = date('d/m/Y \à\s H:i', strtotime($interno['criado_em'])) . 'h';

            $itens_lista = [];
            if (!empty($interno['roupa_civil_pecas'])) {
                $pecas_json = json_decode($interno['roupa_civil_pecas'], true);
                if ($pecas_json) {
                    if (!empty($pecas_json['predefinidos'])) {
                        foreach ($pecas_json['predefinidos'] as $item) {
                            $itens_lista[] = [
                                'texto' => $item['quantidade'] . 'x ' . $item['tipo'],
                                'is_mochila' => mb_strtolower($item['tipo']) === 'mochila'
                            ];
                        }
                    }
                    if (!empty($pecas_json['outros'])) {
                        foreach ($pecas_json['outros'] as $item) {
                            $itens_lista[] = [
                                'texto' => $item['quantidade'] . 'x ' . $item['tipo'],
                                'is_mochila' => mb_strtolower($item['tipo']) === 'mochila'
                            ];
                        }
                    }
                }
            }

            usort($itens_lista, function ($a, $b) {
                if ($a['is_mochila'] === $b['is_mochila']) return 0;
                return $a['is_mochila'] ? -1 : 1;
            });

            $itens_formatados = [];
            foreach ($itens_lista as $item) {
                $txt = htmlspecialchars($item['texto']);
                if ($item['is_mochila']) {
                    $txt = "<strong class='mochila'>{$txt}</strong>";
                }
                $itens_formatados[] = $txt;
            }
            $itens_str = implode(', ', $itens_formatados);

            $html .= "
            <div class='etiqueta'>
                <span class='bloco-principal'><span class='nome-principal'>{$ipen} - {$nome}</span> - <span class='status'>{$statusInterno}</span></span>
                <span class='itens'>({$itens_str})</span>
                <span class='ultima-verificacao'>Última Verificação: {$ultimaVerificacao}</span>
            </div>";
        }

        $html .= '
        </body>
        </html>';

        echo $html;
        exit;
    } catch (Exception $e) {
        echo '<h2>Erro ao gerar etiquetas</h2><p>' . $e->getMessage() . '</p>';
        exit;
    }
}

// --- PREPARAÇÃO DE DADOS PARA VIEW ---

// 1. Buscar situações para filtros - USANDO API
$result_situacoes = callInternosAPI('GET');
$situacoes_unicas = array_unique(array_filter(array_column(($result_situacoes['success'] && is_array($result_situacoes['data'])) ? $result_situacoes['data'] : [], 'situacao')));
sort($situacoes_unicas);
$situacoes = $situacoes_unicas;

// 2. Buscar galerias, blocos e setores para filtros - JÁ FORAM BUSCADOS ACIMA
// (Reutilizando as variáveis já definidas: $galerias_db, $blocos_db, $setores_db)

// 3. Função para gerar links de paginação
function getPagLink($p)
{
    $q = $_GET;
    $q['page'] = $p;
    return "/modulos/censura/rouparia/gestao_kits/view.php?" . http_build_query($q);
}

// 4. Processar dados dos internos para exibição
$internos_processados = [];
foreach ($internos as $i) {
    $isR = in_array($i['kit'], $repetidos_ids);
    $ct = mb_strtolower($i['cor_roupa'] ?: 'laranja');
    $sit = ($i['regalia'] == 'S' ? $i['situacao'] . " - " . $i['regalia_setor'] : $i['situacao']);
    $kr = ($i['regalia'] == 'S' && !$i['regalia_kit']) ? $i['kit'] : $i['regalia_kit'];

    $hasSocial = !empty($i['nome_social']);
    $social_class = $hasSocial ? 'row-social' : '';
    $nome_exib = $hasSocial ? "<span class='nome-social-text'>{$i['nome_social']}</span> <span class='nome-masculino'>({$i['nome']})</span>" : $i['nome'];

    $internos_processados[] = array_merge($i, [
        'isR' => $isR,
        'ct' => $ct,
        'sit' => $sit,
        'kr' => $kr,
        'social_class' => $social_class,
        'nome_exib' => $nome_exib
    ]);
}

// 5. Processar kits disponíveis com classes e títulos
$kits_disponiveis_processados = [];
foreach ($k_livres_tela as $v) {
    $extra_class = '';
    $title = '';
    $status_text = '';

    if (isset($ready_kits_map[$v])) {
        $cor = $ready_kits_map[$v]['cor'];
        $status = $ready_kits_map[$v]['status'];
        $extra_class = ($cor == 'Verde') ? 'kit-pronto-verde' : 'kit-pronto';

        switch ($status) {
            case 'pronto':
                $status_text = 'Pronto para entrega';
                break;
            case 'refazendo':
                $status_text = 'Em reconfeccção';
                $extra_class .= ' kit-refazendo';
                break;
            default:
                $status_text = 'Disponível';
        }

        $title = "Status: $status_text - Cor: $cor, Data: " . date('d/m/Y H:i', strtotime($ready_kits_map[$v]['data_cadastro'])) . ", Info: " . htmlspecialchars($ready_kits_map[$v]['info_adicional']);
    }

    $kits_disponiveis_processados[] = [
        'numero' => $v,
        'extra_class' => $extra_class,
        'title' => $title,
        'badge_id' => "badge-kit-$v"
    ];
}

// 6. Processar kits em quarentena
$kits_quarentena_processados = [];
foreach ($kits_quarentena_tela as $kq) {
    $nome_exib = !empty($kq['nome_social']) ? $kq['nome_social'] : explode(' ', $kq['nome'])[0];
    $data_lib = date('d/m', strtotime($kq['data_liberacao']));

    // Calcular dias restantes igual ao teste original
    $dias_restantes = max(0, ceil((strtotime($kq['data_liberacao']) - time()) / 86400));

    $kits_quarentena_processados[] = array_merge($kq, [
        'nome_exib' => $nome_exib,
        'data_lib' => $data_lib,
        'dias_restantes' => $dias_restantes
    ]);
}

// 7. Processar sem kit
$sem_k_processados = [];
foreach ($sem_k_tela as $sk) {
    $n = !empty($sk['nome_social']) ? "<b>{$sk['nome_social']}</b><br><small>({$sk['nome']})</small>" : $sk['nome'];
    $sem_k_processados[] = array_merge($sk, ['nome_formatado' => $n]);
}

// 8. Processar repetidos
// $repet_processados = []; // REMOVIDO - não inicializar como array vazio
foreach ($repet_tela as $r) {
    $n = !empty($r['nome_social']) ? "<b>{$r['nome_social']}</b>" : $r['nome'];
    $repet_processados[] = array_merge($r, ['nome_formatado' => $n]);
}

// 9. Calcular estatísticas de kits
// Garantir que as variáveis existam
$k_livres_tela = $k_livres_tela ?? [];
$ready_kit_numbers = $ready_kit_numbers ?? [];

// Calcular kits prontos livres e para fazer
$kits_prontos_livres = array_intersect($k_livres_tela, $ready_kit_numbers);
$kits_livres_para_fazer = array_diff($k_livres_tela, $ready_kit_numbers);

// Garantir que sejam arrays
$kits_prontos_livres = is_array($kits_prontos_livres) ? $kits_prontos_livres : [];
$kits_livres_para_fazer = is_array($kits_livres_para_fazer) ? $kits_livres_para_fazer : [];

// 10. Processar regalias fechado com setores
$regalias_fechado_processadas = [];
$current_sector = '';
foreach ($regalias_fechado_tela as $reg) {
    $nome_exib = !empty($reg['nome_social']) ? "<b>{$reg['nome_social']}</b><br><small>({$reg['nome']})</small>" : $reg['nome'];

    // Detectar mudança de setor
    $mudou_setor = $current_sector !== $reg['regalia_setor'];
    if ($mudou_setor) {
        $current_sector = $reg['regalia_setor'];
    }

    $regalias_fechado_processadas[] = array_merge($reg, [
        'nome_exib' => $nome_exib,
        'mudou_setor' => $mudou_setor,
        'setor_atual' => $current_sector
    ]);
}

// 11. Processar regalias semiaberto com setores
$regalias_semiaberto_processadas = [];
$current_sector = '';
foreach ($regalias_semiaberto_tela as $reg) {
    $nome_exib = !empty($reg['nome_social']) ? "<b>{$reg['nome_social']}</b><br><small>({$reg['nome']})</small>" : $reg['nome'];

    // Detectar mudança de setor
    $mudou_setor = $current_sector !== $reg['regalia_setor'];
    if ($mudou_setor) {
        $current_sector = $reg['regalia_setor'];
    }

    $regalias_semiaberto_processadas[] = array_merge($reg, [
        'nome_exib' => $nome_exib,
        'mudou_setor' => $mudou_setor,
        'setor_atual' => $current_sector
    ]);
}

// 12. Substituir arrays originais pelos processados
$internos = $internos_processados;
$kits_disponiveis_tela = $kits_disponiveis_processados;
$kits_quarentena_tela = $kits_quarentena_processados;
$sem_k_tela = $sem_k_processados;
$repet_tela = $repet_processados;
$regalias_fechado_tela = $regalias_fechado_processadas;
$regalias_semiaberto_tela = $regalias_semiaberto_processadas;

?>
