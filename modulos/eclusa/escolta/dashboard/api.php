<?php
header('Content-Type: application/json');
ini_set('display_errors', 0);

$host = 'localhost';
$db   = 'sigep_producao';
$user = 'sigep';
$pass = 'z3wr7bimo3?uHoro';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // Filtros
    $ini      = $_GET['data_ini'] ?? date('Y-m-01');
    $fim      = $_GET['data_fim'] ?? date('Y-m-d');
    $status   = $_GET['status']   ?? '';
    $busca    = $_GET['busca']    ?? '';
    $motorista = $_GET['motorista'] ?? '';
    $destino  = $_GET['destino']  ?? '';
    $id_escolta = $_GET['id'] ?? '';
    $tipo_relatorio = $_GET['tipo_relatorio'] ?? '';

    // Se pedir relatório de um card (drill-down)
    if ($tipo_relatorio) {
        $whereRelatorio = "WHERE data_cadastro BETWEEN :ini AND :fim";
        $paramsRelatorio = [':ini' => $ini, ':fim' => $fim];

        if ($tipo_relatorio === 'NOT') {
            $whereRelatorio .= " AND e.eh_not = 'Sim'";
        } elseif ($tipo_relatorio === 'Pendente') {
            $whereRelatorio .= " AND e.status = 'Pendente'";
        } elseif ($tipo_relatorio === 'Cancelado') {
            $whereRelatorio .= " AND (e.status LIKE 'Cancelado%' OR e.status LIKE 'Cancelada%')";
        }

        $stmt = $pdo->prepare("SELECT e.id, e.data_cadastro, e.interno, e.destino, e.motorista, e.placa, e.status,
                                      e.hora_prevista, e.hora_chegada, e.hora_retorno, e.eh_not,
                                      i.ala, i.galeria
                               FROM eclusa_movimentacoes_escolta e
                               LEFT JOIN internos i ON e.interno_id = i.ipen
                               $whereRelatorio
                               ORDER BY e.data_cadastro DESC, e.id DESC");
        $stmt->execute($paramsRelatorio);
        $result = $stmt->fetchAll();

        // Log para debug (remover após validar)
        // error_log("Drill-down $tipo_relatorio: " . count($result) . " registros encontrados.");

        echo json_encode(['lista' => $result]);
        exit;
    }

    // Se pedir ID específico, retorna apenas um item
    if ($id_escolta) {
        $stmt = $pdo->prepare("SELECT e.*, i.ala, i.galeria FROM eclusa_movimentacoes_escolta e
                                LEFT JOIN internos i ON e.interno_id = i.ipen
                                WHERE e.id = :id");
        $stmt->execute([':id' => $id_escolta]);
        echo json_encode(['item' => $stmt->fetch()]);
        exit;
    }

    $where = "WHERE data_cadastro BETWEEN :ini AND :fim";
    $params = [':ini' => $ini, ':fim' => $fim];

    if ($status) {
        $where .= " AND status = :status";
        $params[':status'] = $status;
    }
    if ($busca) {
        $where .= " AND (interno LIKE :b OR placa LIKE :b)";
        $params[':b'] = "%$busca%";
    }
    if ($motorista) {
        $where .= " AND motorista = :mot";
        $params[':mot'] = $motorista;
    }
    if ($destino) {
        $where .= " AND destino = :dest";
        $params[':dest'] = $destino;
    }

    // Stats para os Cards
    $stats = $pdo->prepare("SELECT
        COUNT(*) as total,
        SUM(CASE WHEN eh_not = 'Sim' THEN 1 ELSE 0 END) as not_total,
        SUM(CASE WHEN status = 'Pendente' THEN 1 ELSE 0 END) as pendentes,
        SUM(CASE WHEN status LIKE 'Cancelado%' OR status LIKE 'Cancelada%' THEN 1 ELSE 0 END) as cancelados
        FROM eclusa_movimentacoes_escolta $where");
    $stats->execute($params);
    $resStats = $stats->fetch();

    // Lista Principal
    $lista = $pdo->prepare("SELECT e.id, e.data_cadastro, e.interno, e.destino, e.motorista, e.placa, e.status,
                                   e.hora_prevista, e.hora_chegada, e.hora_retorno, e.eh_not, e.motivo,
                                   i.ala, i.galeria
                            FROM eclusa_movimentacoes_escolta e
                            LEFT JOIN internos i ON e.interno_id = i.ipen $where ORDER BY e.id DESC LIMIT 100");
    $lista->execute($params);

    // Dados para Gráficos (Respeitando filtros)
    $qDestinos = $pdo->prepare("SELECT destino as label, COUNT(*) as value FROM eclusa_movimentacoes_escolta $where GROUP BY destino ORDER BY value DESC LIMIT 5");
    $qDestinos->execute($params);

    $qStatus = $pdo->prepare("SELECT status as label, COUNT(*) as value FROM eclusa_movimentacoes_escolta $where GROUP BY status");
    $qStatus->execute($params);

    echo json_encode([
        'stats' => $resStats ?: ['total' => 0, 'not_total' => 0, 'pendentes' => 0, 'cancelados' => 0],
        'lista' => $lista->fetchAll(),
        'charts' => [
            'destinos' => $qDestinos->fetchAll(),
            'status' => $qStatus->fetchAll()
        ]
    ]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
