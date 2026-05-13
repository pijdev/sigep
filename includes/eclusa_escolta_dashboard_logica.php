<?php
require_once __DIR__ . '/../conf/db.php';

try {
    $config = require __DIR__ . '/../conf/db.php';
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['user'], $config['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    die('Erro de conexão: ' . $e->getMessage());
}

// Função para obter estatísticas gerais
function getDashboardStats(PDO $pdo) {
    // Total de escoltas
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM eclusa_movimentacoes_escolta");
    $total = $stmt->fetch()['total'];

    // Escoltas hoje
    $stmt = $pdo->prepare("SELECT COUNT(*) as hoje FROM eclusa_movimentacoes_escolta WHERE DATE(data_cadastro) = CURDATE()");
    $stmt->execute();
    $hoje = $stmt->fetch()['hoje'];

    // Escoltas finalizadas hoje
    $stmt = $pdo->prepare("SELECT COUNT(*) as finalizadas FROM eclusa_movimentacoes_escolta WHERE DATE(data_cadastro) = CURDATE() AND status = 'Finalizado'");
    $stmt->execute();
    $finalizadas = $stmt->fetch()['finalizadas'];

    // Escoltas pendentes hoje
    $stmt = $pdo->prepare("SELECT COUNT(*) as pendentes FROM eclusa_movimentacoes_escolta WHERE DATE(data_cadastro) = CURDATE() AND status = 'Pendente'");
    $stmt->execute();
    $pendentes = $stmt->fetch()['pendentes'];

    // Escoltas esta semana
    $stmt = $pdo->prepare("SELECT COUNT(*) as semana FROM eclusa_movimentacoes_escolta WHERE YEARWEEK(data_cadastro) = YEARWEEK(CURDATE())");
    $stmt->execute();
    $semana = $stmt->fetch()['semana'];

    // Escoltas este mês
    $stmt = $pdo->prepare("SELECT COUNT(*) as mes FROM eclusa_movimentacoes_escolta WHERE YEAR(data_cadastro) = YEAR(CURDATE()) AND MONTH(data_cadastro) = MONTH(CURDATE())");
    $stmt->execute();
    $mes = $stmt->fetch()['mes'];

    // Total NOT
    $stmt = $pdo->query("SELECT COUNT(*) as total_not FROM eclusa_movimentacoes_escolta WHERE eh_not = 'Sim'");
    $stmt->execute();
    $total_not = $stmt->fetch()['total_not'];

    return [
        'total' => $total,
        'hoje' => $hoje,
        'finalizadas' => $finalizadas,
        'pendentes' => $pendentes,
        'semana' => $semana,
        'mes' => $mes,
        'total_not' => $total_not
    ];
}

// Função para obter escoltas por data com período configurável
function getEscoltasPorData(PDO $pdo, $periodo = '30') {
    $intervalos = [
        '7' => '7 DAY',
        '30' => '30 DAY',
        '90' => '90 DAY',
        '180' => '180 DAY',
        '365' => '365 DAY',
        '0' => '100 YEAR' // Todo período
    ];

    $interval = $intervalos[$periodo] ?? '30 DAY';

    $sql = "
        SELECT
            DATE(data_cadastro) as data,
            COUNT(*) as total,
            SUM(CASE WHEN status = 'Finalizado' THEN 1 ELSE 0 END) as finalizadas,
            SUM(CASE WHEN status = 'Pendente' THEN 1 ELSE 0 END) as pendentes,
            SUM(CASE WHEN eh_not = 'Sim' THEN 1 ELSE 0 END) as escoltas_not
        FROM eclusa_movimentacoes_escolta
        WHERE data_cadastro >= DATE_SUB(CURDATE(), INTERVAL $interval)
        GROUP BY DATE(data_cadastro)
        ORDER BY data ASC
    ";

    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}

// Função para obter escoltas por status
function getEscoltasPorStatus(PDO $pdo) {
    $sql = "
        SELECT
            status,
            COUNT(*) as quantidade
        FROM eclusa_movimentacoes_escolta
        GROUP BY status
        ORDER BY quantidade DESC
    ";

    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}

// Função para obter top destinos
function getTopDestinos(PDO $pdo, $limit = 10) {
    $sql = "
        SELECT
            destino,
            COUNT(*) as total_escoltas,
            COUNT(DISTINCT DATE(data_cadastro)) as dias_ativos,
            MAX(data_cadastro) as ultima_escolta
        FROM eclusa_movimentacoes_escolta
        WHERE destino IS NOT NULL AND destino != ''
        GROUP BY destino
        ORDER BY total_escoltas DESC
        LIMIT " . $limit;

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll();
}

// Função para obter top motoristas
function getTopMotoristas(PDO $pdo, $limit = 10) {
    $sql = "
        SELECT
            motorista,
            COUNT(*) as total_escoltas,
            COUNT(DISTINCT DATE(data_cadastro)) as dias_ativos,
            MAX(data_cadastro) as ultima_escolta
        FROM eclusa_movimentacoes_escolta
        WHERE motorista IS NOT NULL AND motorista != ''
        GROUP BY motorista
        ORDER BY total_escoltas DESC
        LIMIT " . $limit;

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll();
}

// Função para obter escoltas por mês
function getEscoltasPorMes(PDO $pdo) {
    $sql = "
        SELECT
            DATE_FORMAT(data_cadastro, '%Y-%m') as mes,
            COUNT(*) as total,
            SUM(CASE WHEN status = 'Finalizado' THEN 1 ELSE 0 END) as finalizadas,
            SUM(CASE WHEN status = 'Pendente' THEN 1 ELSE 0 END) as pendentes,
            SUM(CASE WHEN eh_not = 'Sim' THEN 1 ELSE 0 END) as escoltas_not
        FROM eclusa_movimentacoes_escolta
        WHERE data_cadastro >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(data_cadastro, '%Y-%m')
        ORDER BY mes ASC
    ";

    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}

// Função para obter escoltas por dia da semana
function getEscoltasPorDia(PDO $pdo) {
    $sql = "
        SELECT
            DAYNAME(data_cadastro) as dia_semana,
            COUNT(*) as total
        FROM eclusa_movimentacoes_escolta
        WHERE data_cadastro >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY dia_semana
        ORDER BY dia_semana ASC
    ";

    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}

// Função para obter estatísticas de NOT
function getEstatisticasNOT(PDO $pdo) {
    $sql = "
        SELECT
            COUNT(*) as total_not,
            COUNT(CASE WHEN status = 'Finalizado' AND eh_not = 'Sim' THEN 1 ELSE 0 END) as not_finalizadas,
            COUNT(CASE WHEN status = 'Pendente' AND eh_not = 'Sim' THEN 1 ELSE 0 END) as not_pendentes
        FROM eclusa_movimentacoes_escolta
        WHERE eh_not = 'Sim'
    ";

    $stmt = $pdo->query($sql);
    return $stmt->fetch();
}

// Função para obter distribuição por hora
function getEscoltasPorHora(PDO $pdo) {
    $sql = "
        SELECT
            HOUR(hora_prevista) as hora,
            COUNT(*) as total
        FROM eclusa_movimentacoes_escolta
        WHERE hora_prevista IS NOT NULL
        GROUP BY HOUR(hora_prevista)
        ORDER BY hora ASC
    ";

    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}

// Funções para detalhes dos gráficos
// Funções para detalhes dos gráficos (com nomes diferentes para evitar conflitos)
function getEscoltasDetalhadasPorData(PDO $pdo, $data) {
    $sql = "
        SELECT
            data_cadastro,
            interno,
            destino,
            motorista,
            placa,
            status,
            hora_prevista,
            hora_chegada,
            hora_retorno,
            eh_not,
            cadastrado_por
        FROM eclusa_movimentacoes_escolta
        WHERE DATE(data_cadastro) = :data
        ORDER BY data_cadastro DESC, hora_prevista ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['data' => $data]);
    return $stmt->fetchAll();
}

function getEscoltasDetalhadasPorDestino(PDO $pdo, $destino) {
    $sql = "
        SELECT
            data_cadastro,
            interno,
            destino,
            motorista,
            placa,
            status,
            hora_prevista,
            hora_chegada,
            hora_retorno,
            eh_not,
            cadastrado_por
        FROM eclusa_movimentacoes_escolta
        WHERE destino = :destino
        ORDER BY data_cadastro DESC, hora_prevista ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['destino' => $destino]);
    return $stmt->fetchAll();
}

function getEscoltasDetalhadasPorMotorista(PDO $pdo, $motorista) {
    $sql = "
        SELECT
            data_cadastro,
            interno,
            destino,
            motorista,
            placa,
            status,
            hora_prevista,
            hora_chegada,
            hora_retorno,
            eh_not,
            cadastrado_por
        FROM eclusa_movimentacoes_escolta
        WHERE motorista = :motorista
        ORDER BY data_cadastro DESC, hora_prevista ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['motorista' => $motorista]);
    return $stmt->fetchAll();
}

function getEscoltasDetalhadasPorHora(PDO $pdo, $hora) {
    $sql = "
        SELECT
            data_cadastro,
            interno,
            destino,
            motorista,
            placa,
            status,
            hora_prevista,
            hora_chegada,
            hora_retorno,
            eh_not,
            cadastrado_por
        FROM eclusa_movimentacoes_escolta
        WHERE HOUR(hora_prevista) = :hora
        ORDER BY data_cadastro DESC, hora_prevista ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['hora' => $hora]);
    return $stmt->fetchAll();
}

function getEscoltasDetalhadasPorDiaSemana(PDO $pdo, $dia) {
    $sql = "
        SELECT
            data_cadastro,
            interno,
            destino,
            motorista,
            placa,
            status,
            hora_prevista,
            hora_chegada,
            hora_retorno,
            eh_not,
            cadastrado_por
        FROM eclusa_movimentacoes_escolta
        WHERE DAYNAME(data_cadastro) = :dia
        ORDER BY data_cadastro DESC, hora_prevista ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['dia' => $dia]);
    return $stmt->fetchAll();
}

function getEscoltasDetalhadasPorMes(PDO $pdo, $mes) {
    $sql = "
        SELECT
            data_cadastro,
            interno,
            destino,
            motorista,
            placa,
            status,
            hora_prevista,
            hora_chegada,
            hora_retorno,
            eh_not,
            cadastrado_por
        FROM eclusa_movimentacoes_escolta
        WHERE DATE_FORMAT(data_cadastro, '%Y-%m') = :mes
        ORDER BY data_cadastro DESC, hora_prevista ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['mes' => $mes]);
    return $stmt->fetchAll();
}

// Função para obter dados do gráfico por período
function getChartData(PDO $pdo, $chartType, $periodo) {
    if ($chartType === 'data') {
        $periodo = (int)$periodo;
        $where = $periodo > 0 ? "WHERE data_cadastro >= DATE_SUB(CURDATE(), INTERVAL $periodo DAY)" : "";

        $sql = "
            SELECT
                DATE(data_cadastro) as data,
                COUNT(*) as total,
                SUM(CASE WHEN status = 'Finalizado' THEN 1 ELSE 0 END) as finalizadas,
                SUM(CASE WHEN status != 'Finalizado' THEN 1 ELSE 0 END) as pendentes
            FROM eclusa_movimentacoes_escolta
            $where
            GROUP BY DATE(data_cadastro)
            ORDER BY data ASC
        ";

        $stmt = $pdo->query($sql);
        return $stmt->fetchAll();
    }

    return [];
}

// Função para obter todas as escoltas para a tabela
function getTodasEscoltas(PDO $pdo, $periodo = 30) {
    $periodo = (int)$periodo;
    $where = $periodo > 0 ? "WHERE data_cadastro >= DATE_SUB(CURDATE(), INTERVAL $periodo DAY)" : "";

    $sql = "
        SELECT
            data_cadastro,
            interno,
            destino,
            motorista,
            placa,
            status,
            hora_prevista,
            hora_chegada,
            hora_retorno,
            eh_not,
            motivo,
            cadastrado_por
        FROM eclusa_movimentacoes_escolta
        $where
        ORDER BY data_cadastro DESC, hora_prevista DESC
    ";

    $stmt = $pdo->query($sql);
    $escoltas = $stmt->fetchAll();

    // Obter total
    $sqlTotal = "SELECT COUNT(*) as total FROM eclusa_movimentacoes_escolta $where";
    $stmtTotal = $pdo->query($sqlTotal);
    $total = $stmtTotal->fetch()['total'];

    return [
        'escoltas' => $escoltas,
        'total' => $total,
        'periodo' => $periodo
    ];
}

// Switch para ações AJAX
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'get_chart_data':
            $chartType = $_POST['chart_type'] ?? '';
            $periodo = $_POST['periodo'] ?? 30;
            $data = getChartData($pdo, $chartType, $periodo);
            echo json_encode($data);
            break;

        case 'get_escoltas_detalhadas':
            $data = $_POST['data'] ?? '';
            $escoltas = getEscoltasDetalhadasPorData($pdo, $data);
            echo json_encode($escoltas);
            break;

        case 'get_escoltas_por_status':
            $status = $_POST['status'] ?? '';
            $sql = "
                SELECT
                    e.id,
                    e.data_cadastro,
                    e.interno,
                    e.destino,
                    e.motorista,
                    e.placa,
                    e.status,
                    e.hora_prevista,
                    e.hora_chegada,
                    e.hora_retorno,
                    e.eh_not,
                    e.cadastrado_por
                FROM eclusa_movimentacoes_escolta e
                WHERE e.status = :status
                ORDER BY e.data_cadastro DESC, e.hora_prevista DESC
                LIMIT 500
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['status' => $status]);
            echo json_encode($stmt->fetchAll());
            break;

        case 'get_escoltas_por_destino':
            $destino = $_POST['destino'] ?? '';
            $escoltas = getEscoltasDetalhadasPorDestino($pdo, $destino);
            echo json_encode($escoltas);
            break;

        case 'get_escoltas_por_motorista':
            $motorista = $_POST['motorista'] ?? '';
            $escoltas = getEscoltasDetalhadasPorMotorista($pdo, $motorista);
            echo json_encode($escoltas);
            break;

        case 'get_escoltas_por_hora':
            $hora = $_POST['hora'] ?? '';
            $escoltas = getEscoltasDetalhadasPorHora($pdo, $hora);
            echo json_encode($escoltas);
            break;

        case 'get_escoltas_por_dia_semana':
            $dia = $_POST['dia'] ?? '';
            $escoltas = getEscoltasDetalhadasPorDiaSemana($pdo, $dia);
            echo json_encode($escoltas);
            break;

        case 'get_escoltas_por_mes':
            $mes = $_POST['mes'] ?? '';
            $escoltas = getEscoltasDetalhadasPorMes($pdo, $mes);
            echo json_encode($escoltas);
            break;

        case 'get_todas_escoltas':
            $periodo = $_POST['periodo'] ?? 30;
            $resultado = getTodasEscoltas($pdo, $periodo);
            echo json_encode($resultado);
            break;

        default:
            echo json_encode(['error' => 'Ação não reconhecida']);
    }
    exit;
}

// Função para obter escoltas gerais para a tabela
function getEscoltasGeral(PDO $pdo, $dias = 30) {
    $sql = "
        SELECT
            e.id,
            DATE(e.data_cadastro) as data,
            e.interno,
            e.destino,
            e.motorista,
            e.placa,
            e.status,
            e.eh_not,
            e.hora_prevista,
            e.hora_chegada,
            e.hora_retorno,
            e.motivo,
            e.cadastrado_por
        FROM eclusa_movimentacoes_escolta e
        WHERE e.data_cadastro >= DATE_SUB(CURDATE(), INTERVAL :dias DAY)
        ORDER BY e.data_cadastro DESC, e.hora_prevista DESC
        LIMIT 1000
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['dias' => $dias]);
    return $stmt->fetchAll();
}
?>
