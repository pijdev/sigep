<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('America/Sao_Paulo');
mb_internal_encoding('UTF-8');

$config = require __DIR__ . '/../../../conf/db.php';
$dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
$pdo = new PDO($dsn, $config['user'], $config['pass'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

function eclusa_ok(array $data = []): void
{
    echo json_encode(array_merge(['success' => true], $data), JSON_UNESCAPED_UNICODE);
    exit;
}

function eclusa_error(string $message, ?string $code = null, int $httpCode = 400): void
{
    http_response_code($httpCode);
    echo json_encode([
        'success' => false,
        'message' => $message,
        'code' => $code,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function registrar_auditoria(PDO $pdo, int $id_escolta, string $campo, ?string $valor_antigo, ?string $valor_novo, string $quem_alterou, string $operacao): void
{
    try {
        $stmt = $pdo->prepare('
            INSERT INTO eclusa_escolta_auditoria
            (id_escolta, campo, valor_antigo, valor_novo, quem_alterou, operacao)
            VALUES (?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $id_escolta,
            $campo,
            $valor_antigo,
            $valor_novo,
            $quem_alterou,
            $operacao
        ]);
    } catch (PDOException $e) {
        // Log do erro de auditoria, mas não interrompe a operação principal
        error_log("Erro ao registrar auditoria: " . $e->getMessage());
    }
}

function validar_destino(?string $destino): void
{
    if (!$destino) {
        eclusa_error('Informe o destino da escolta.');
    }

    // Lista de destinos genéricos/proibidos
    $destinos_proibidos = [
        'CONSULTA EXTERNA',
        'CONSULTA',
        'EXTERNO',
        'EXTERNA',
        'SAÍDA',
        'SAIDA',
        'TRÂNSITO',
        'TRANSITO',
        'EM TRÂNSITO',
        'EM TRANSITO',
        'AVALIAÇÃO',
        'AVALIACAO',
        'ATENDIMENTO',
        'ATENDIMENTO EXTERNO',
        'REVISÃO',
        'REVISAO',
        'PERÍCIA',
        'PERICIA',
        'AUDIÊNCIA',
        'AUDIENCIA'
    ];

    $destino_upper = strtoupper(trim($destino));

    // Verificar se o destino contém palavras proibidas
    foreach ($destinos_proibidos as $proibido) {
        if (strpos($destino_upper, $proibido) !== false) {
            eclusa_error("Destino '{$destino}' é muito genérico. Seja específico: 'Hospitão do Joãozinho' em vez de '{$proibido}'.");
        }
    }

    // Verificar se o destino tem menos de 5 caracteres (muito curto)
    if (strlen(trim($destino)) < 5) {
        eclusa_error('Destino muito curto. Informe o local completo.');
    }
}

function validar_interno(?string $interno): void
{
    if (!$interno) {
        eclusa_error('Informe o interno.');
    }

    // Verificar se está no padrão IPEN (6 dígitos) - NOME COMPLETO
    if (preg_match('/^(\d{6,})\s*-\s*(.+)$/i', trim($interno), $matches)) {
        $ipen = $matches[1];
        $nome = trim($matches[2]);

        // Validar se o nome tem pelo menos 3 caracteres
        if (strlen($nome) < 3) {
            eclusa_error('Nome do interno muito curto. Use o formato: IPEN - NOME COMPLETO');
        }

        // Validar se o IPEN é numérico
        if (!is_numeric($ipen)) {
            eclusa_error('IPEN deve conter apenas números. Use o formato: IPEN - NOME COMPLETO');
        }

        return; // Formato válido
    }

    // Se não está no padrão, verificar se é apenas IPEN numérico
    if (is_numeric(trim($interno))) {
        eclusa_error('Informe o nome completo. Use o formato: IPEN - NOME COMPLETO');
    }

    // Para outros formatos, verificar se é um nome válido
    if (strlen(trim($interno)) < 5) {
        eclusa_error('Nome do interno muito curto. Use o formato: IPEN - NOME COMPLETO');
    }
}

function in_str(string $key): ?string
{
    if (!isset($_POST[$key])) {
        return null;
    }

    $value = trim((string) $_POST[$key]);
    return $value === '' ? null : $value;
}

// Funções do Dashboard
function getDashboardStats(PDO $pdo)
{
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

function getEscoltasPorData(PDO $pdo, $periodo = '30')
{
    $intervalos = [
        '7' => '7 DAY',
        '30' => '30 DAY',
        '90' => '90 DAY',
        '180' => '180 DAY',
        '365' => '365 DAY',
        '0' => '100 YEAR'
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

function getEscoltasPorStatus(PDO $pdo)
{
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

function getTopDestinos(PDO $pdo, $limit = 10)
{
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

function getTopMotoristas(PDO $pdo, $limit = 10)
{
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

function getEscoltasPorMes(PDO $pdo)
{
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

function getEscoltasPorDia(PDO $pdo)
{
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

function getEstatisticasNOT(PDO $pdo)
{
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

function getEscoltasPorHora(PDO $pdo)
{
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
function getEscoltasDetalhadasPorData(PDO $pdo, $data)
{
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

function getEscoltasDetalhadasPorDestino(PDO $pdo, $destino)
{
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
        ORDER BY data_cadastro DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['destino' => $destino]);
    return $stmt->fetchAll();
}

function getEscoltasDetalhadasPorStatus(PDO $pdo, $status)
{
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
        WHERE status = :status
        ORDER BY data_cadastro DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['status' => $status]);
    return $stmt->fetchAll();
}

function getEscoltasDetalhadasPorMotorista(PDO $pdo, $motorista)
{
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
        ORDER BY data_cadastro DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['motorista' => $motorista]);
    return $stmt->fetchAll();
}

function getEscoltasDetalhadasPorHora(PDO $pdo, $hora)
{
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
        ORDER BY data_cadastro DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['hora' => $hora]);
    return $stmt->fetchAll();
}

function getEscoltasDetalhadasPorDiaSemana(PDO $pdo, $dia)
{
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
        ORDER BY data_cadastro DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['dia' => $dia]);
    return $stmt->fetchAll();
}

function getEscoltasDetalhadasPorMes(PDO $pdo, $mes)
{
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
        ORDER BY data_cadastro DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['mes' => $mes]);
    return $stmt->fetchAll();
}

function in_int(string $key): ?int
{
    if (!isset($_POST[$key])) {
        return null;
    }

    $raw = trim((string) $_POST[$key]);
    if ($raw === '' || !is_numeric($raw)) {
        return null;
    }

    return (int) $raw;
}

function normalize_date(?string $value): ?string
{
    if ($value === null || $value === '') {
        return null;
    }

    $value = trim($value);
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
        return $value;
    }

    $ts = strtotime($value);
    if ($ts === false) {
        return null;
    }

    return date('Y-m-d', $ts);
}

function normalize_time(?string $value): ?string
{
    if ($value === null || $value === '') {
        return null;
    }

    $value = trim($value);
    if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $value)) {
        return null;
    }

    if (strlen($value) === 5) {
        return $value . ':00';
    }

    return $value;
}

function formatar_placa(string $placa): string
{
    $clean = preg_replace('/[^A-Z0-9]/', '', strtoupper($placa));
    if (strlen($clean) !== 7) {
        return $clean;
    }

    return substr($clean, 0, 3) . '-' . substr($clean, 3);
}

function require_write_permission(): void
{
    // Módulo liberado para o perfil atual. Caso precise, adicionar validação de sessão aqui.
}

function resolve_empresa_id(PDO $pdo, ?int $empresaId, ?string $empresaNome): ?int
{
    if ($empresaId) {
        return $empresaId;
    }

    if (!$empresaNome) {
        return null;
    }

    $stmt = $pdo->prepare('SELECT id FROM eclusa_empresas WHERE nome = ? LIMIT 1');
    $stmt->execute([$empresaNome]);
    $id = $stmt->fetchColumn();
    if ($id) {
        return (int) $id;
    }

    $stmt = $pdo->prepare('INSERT INTO eclusa_empresas (nome, observacoes) VALUES (?, ?)');
    $stmt->execute([$empresaNome, 'Criada automaticamente pelo módulo de movimentações']);

    return (int) $pdo->lastInsertId();
}

function resolve_motorista_id(PDO $pdo, ?int $motoristaId, ?string $motoristaNome): ?int
{
    if ($motoristaId) {
        return $motoristaId;
    }

    if (!$motoristaNome) {
        return null;
    }

    $stmt = $pdo->prepare('SELECT id FROM eclusa_motoristas WHERE nome = ? LIMIT 1');
    $stmt->execute([$motoristaNome]);
    $id = $stmt->fetchColumn();
    if ($id) {
        return (int) $id;
    }

    $stmt = $pdo->prepare('INSERT INTO eclusa_motoristas (nome, cargo) VALUES (?, NULL)');
    $stmt->execute([$motoristaNome]);

    return (int) $pdo->lastInsertId();
}

function resolve_veiculo_id(PDO $pdo, ?int $veiculoId, ?string $placa, ?string $tipoVeiculo, ?int $empresaId): ?int
{
    if ($veiculoId) {
        return $veiculoId;
    }

    $placa = $placa ? formatar_placa($placa) : null;
    if (!$placa) {
        return null;
    }

    $stmt = $pdo->prepare('SELECT id FROM eclusa_veiculos WHERE placa = ? LIMIT 1');
    $stmt->execute([$placa]);
    $id = $stmt->fetchColumn();
    if ($id) {
        return (int) $id;
    }

    $nome = $tipoVeiculo ?: $placa;
    $modelo = $tipoVeiculo ?: $placa;

    $stmt = $pdo->prepare('INSERT INTO eclusa_veiculos (nome, modelo, placa, eh_viatura, tipo_origem, empresa_id) VALUES (?, ?, ?, 0, ?, ?)');
    $stmt->execute([$nome, $modelo, $placa, 'Outros', $empresaId]);

    return (int) $pdo->lastInsertId();
}

function get_contadores(PDO $pdo): array
{
    $sql = "
        SELECT
            COUNT(*) AS total_escoltas,
            SUM(CASE WHEN data_cadastro = CURDATE() THEN 1 ELSE 0 END) AS escoltas_hoje,
            SUM(CASE WHEN data_cadastro = CURDATE() AND status = 'Finalizado' THEN 1 ELSE 0 END) AS finalizadas_hoje,
            SUM(CASE WHEN data_cadastro = CURDATE() AND status = 'Pendente' THEN 1 ELSE 0 END) AS pendentes_hoje,
            SUM(CASE WHEN data_cadastro >= DATE_FORMAT(CURDATE(), '%Y-%m-01') AND data_cadastro < DATE_ADD(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 1 MONTH) THEN 1 ELSE 0 END) AS escoltas_mes,
            SUM(CASE WHEN eh_not = 'Sim' THEN 1 ELSE 0 END) AS total_not
        FROM eclusa_movimentacoes_escolta
    ";

    $row = $pdo->query($sql)->fetch();

    return [
        'totalEscoltas' => (int) ($row['total_escoltas'] ?? 0),
        'escoltasHoje' => (int) ($row['escoltas_hoje'] ?? 0),
        'finalizadasHoje' => (int) ($row['finalizadas_hoje'] ?? 0),
        'pendentesHoje' => (int) ($row['pendentes_hoje'] ?? 0),
        'escoltasMes' => (int) ($row['escoltas_mes'] ?? 0),
        'totalNot' => (int) ($row['total_not'] ?? 0),
    ];
}

function get_top_destinos(PDO $pdo): array
{
    $sql = "
        SELECT
            destino,
            COUNT(*) AS frequencia,
            MAX(criado_em) AS ultima_escolta
        FROM eclusa_movimentacoes_escolta
        WHERE destino IS NOT NULL AND destino != ''
        GROUP BY destino
        ORDER BY frequencia DESC, ultima_escolta DESC
        LIMIT 10
    ";

    return $pdo->query($sql)->fetchAll();
}

function get_top_motoristas(PDO $pdo): array
{
    $sql = "
        SELECT
            motorista,
            COUNT(*) AS frequencia,
            MAX(criado_em) AS ultima_escolta
        FROM eclusa_movimentacoes_escolta
        WHERE motorista IS NOT NULL AND motorista != ''
        GROUP BY motorista
        ORDER BY frequencia DESC, ultima_escolta DESC
        LIMIT 10
    ";

    return $pdo->query($sql)->fetchAll();
}

function busca_autocomplete_top(PDO $pdo, string $campo): array
{
    if ($campo === 'placa') {
        $sql = "
            SELECT DISTINCT placa AS value, placa AS label, criado_em
            FROM eclusa_movimentacoes_escolta
            WHERE placa IS NOT NULL AND placa != ''
            ORDER BY criado_em DESC
            LIMIT 10
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    if ($campo === 'destino') {
        $sql = "
            SELECT DISTINCT destino AS value, destino AS label, criado_em
            FROM eclusa_movimentacoes_escolta
            WHERE destino IS NOT NULL AND destino != ''
            ORDER BY criado_em DESC
            LIMIT 10
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    if ($campo === 'motorista') {
        $sql = "
            SELECT DISTINCT motorista AS value, motorista AS label, criado_em
            FROM eclusa_movimentacoes_escolta
            WHERE motorista IS NOT NULL AND motorista != ''
            ORDER BY criado_em DESC
            LIMIT 10
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    if ($campo === 'interno') {
        // Mostrar internos mais recentemente utilizados (ativos e inativos)
        $sql = "
            SELECT
                CONCAT(ipen, ' - ', nome) AS value,
                CONCAT(ipen, ' - ', nome) AS label,
                ipen,
                nome,
                status,
                situacao
            FROM internos
            WHERE ipen IS NOT NULL
            ORDER BY data_alterado DESC, ipen ASC
            LIMIT 10
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    return [];
}

function busca_autocomplete(PDO $pdo, string $campo, string $termo): array
{
    $like = '%' . $termo . '%';

    if ($campo === 'placa') {
        $sql = "
            SELECT DISTINCT placa AS value, placa AS label, criado_em
            FROM eclusa_movimentacoes_escolta
            WHERE placa LIKE ?
            ORDER BY placa ASC
            LIMIT 20
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$like]);
        return $stmt->fetchAll();
    }

    if ($campo === 'destino') {
        $sql = "
            SELECT DISTINCT destino AS value, destino AS label, criado_em
            FROM eclusa_movimentacoes_escolta
            WHERE destino LIKE ?
            ORDER BY destino ASC
            LIMIT 20
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$like]);
        return $stmt->fetchAll();
    }

    if ($campo === 'motorista') {
        $sql = "
            SELECT DISTINCT motorista AS value, motorista AS label, criado_em
            FROM eclusa_movimentacoes_escolta
            WHERE motorista LIKE ?
            ORDER BY motorista ASC
            LIMIT 20
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$like]);
        return $stmt->fetchAll();
    }

    if ($campo === 'interno') {
        // Buscar na tabela principal de internos (ativos e inativos)
        $sql = "
            SELECT
                CONCAT(ipen, ' - ', nome) AS value,
                CONCAT(ipen, ' - ', nome) AS label,
                ipen,
                nome,
                status,
                situacao
            FROM internos
            WHERE (ipen LIKE ? OR nome LIKE ?)
            ORDER BY
                CASE
                    WHEN ipen LIKE ? THEN 1
                    WHEN nome LIKE ? THEN 2
                    ELSE 3
                END,
                ipen ASC
            LIMIT 20
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$like, $like, $like, $like]);
        return $stmt->fetchAll();
    }

    return [];
}

function build_where_from_filtros(array $filtros, array &$params): string
{
    $where = [];

    if (!empty($filtros['search'])) {
        $term = '%' . $filtros['search'] . '%';
        $where[] = '(interno LIKE ? OR destino LIKE ? OR motorista LIKE ? OR placa LIKE ? OR motivo LIKE ?)';
        array_push($params, $term, $term, $term, $term, $term);
    }

    if (!empty($filtros['placa'])) {
        $where[] = 'placa LIKE ?';
        $params[] = '%' . $filtros['placa'] . '%';
    }

    if (!empty($filtros['destino'])) {
        $where[] = 'destino LIKE ?';
        $params[] = '%' . $filtros['destino'] . '%';
    }

    if (!empty($filtros['motorista'])) {
        $where[] = 'motorista LIKE ?';
        $params[] = '%' . $filtros['motorista'] . '%';
    }

    if (!empty($filtros['interno'])) {
        $where[] = 'interno LIKE ?';
        $params[] = '%' . $filtros['interno'] . '%';
    }

    if (!empty($filtros['status'])) {
        $where[] = 'status = ?';
        $params[] = $filtros['status'];
    }

    if (!empty($filtros['eh_not'])) {
        $where[] = 'eh_not = ?';
        $params[] = $filtros['eh_not'];
    }

    if (!empty($filtros['data_inicio'])) {
        $where[] = 'data_cadastro >= ?';
        $params[] = $filtros['data_inicio'];
    }

    if (!empty($filtros['data_fim'])) {
        $where[] = 'data_cadastro <= ?';
        $params[] = $filtros['data_fim'];
    }

    return count($where) ? ('WHERE ' . implode(' AND ', $where)) : '';
}

function get_escoltas(PDO $pdo, array $filtros, int $page, int $limit): array
{
    $offset = ($page - 1) * $limit;
    $params = [];
    $whereSql = build_where_from_filtros($filtros, $params);

    $sqlList = "
        SELECT
            id,
            data_cadastro,
            interno,
            hora_prevista,
            destino,
            status,
            motivo,
            hora_chegada,
            hora_retorno,
            motorista,
            placa,
            eh_not,
            cadastrado_por,
            criado_em,
            atualizado_em,
            interno_id
        FROM eclusa_movimentacoes_escolta
        {$whereSql}
        ORDER BY data_cadastro DESC, criado_em DESC, id DESC
        LIMIT {$limit} OFFSET {$offset}
    ";

    $stmtList = $pdo->prepare($sqlList);
    $stmtList->execute($params);
    $rows = $stmtList->fetchAll();

    $sqlCount = "SELECT COUNT(*) FROM eclusa_movimentacoes_escolta {$whereSql}";
    $stmtCount = $pdo->prepare($sqlCount);
    $stmtCount->execute($params);
    $total = (int) $stmtCount->fetchColumn();

    return [
        'dados' => $rows,
        'total' => $total,
        'pagina' => $page,
        'limite' => $limit,
        'total_paginas' => max(1, (int) ceil($total / $limit)),
    ];
}

function get_escolta_by_id(PDO $pdo, int $id): ?array
{
    $sql = "
        SELECT
            id,
            data_cadastro,
            interno,
            hora_prevista,
            destino,
            status,
            motivo,
            hora_chegada,
            hora_retorno,
            motorista,
            placa,
            eh_not,
            cadastrado_por,
            criado_em,
            atualizado_em,
            interno_id
        FROM eclusa_movimentacoes_escolta
        WHERE id = ?
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function gerar_titulo_relatorio(array $filtros): string
{
    $partes = ['Relatório de Escoltas'];

    if (!empty($filtros['data_inicio']) || !empty($filtros['data_fim'])) {
        $inicio = !empty($filtros['data_inicio']) ? date('d/m/Y', strtotime($filtros['data_inicio'])) : '...';
        $fim = !empty($filtros['data_fim']) ? date('d/m/Y', strtotime($filtros['data_fim'])) : '...';
        $partes[] = sprintf('período %s a %s', $inicio, $fim);
    }

    if (!empty($filtros['placa'])) {
        $partes[] = 'placa ' . $filtros['placa'];
    }

    if (!empty($filtros['destino'])) {
        $partes[] = 'destino ' . $filtros['destino'];
    }

    if (!empty($filtros['motorista'])) {
        $partes[] = 'motorista ' . $filtros['motorista'];
    }

    return implode(' - ', $partes);
}

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['db_action'])) {
    if (ob_get_level() > 0) {
        ob_clean();
    }
    header('Content-Type: application/json; charset=utf-8');

    try {
        $action = in_str('db_action') ?? '';

        if ($action === 'get_contadores') {
            eclusa_ok(['data' => get_contadores($pdo)]);
        }

        if ($action === 'get_top_destinos') {
            eclusa_ok(['data' => get_top_destinos($pdo)]);
        }

        if ($action === 'get_top_motoristas') {
            eclusa_ok(['data' => get_top_motoristas($pdo)]);
        }

        if ($action === 'busca_autocomplete') {
            $campo = in_str('campo') ?? '';
            $termo = in_str('termo') ?? '';

            if (mb_strlen($termo) < 1) {
                // Mostrar top 10 quando vazio
                eclusa_ok(['data' => busca_autocomplete_top($pdo, $campo)]);
            }

            eclusa_ok(['data' => busca_autocomplete($pdo, $campo, $termo)]);
        }

        if ($action === 'listar') {
            $filtros = [
                'search' => in_str('search'),
                'placa' => in_str('placa'),
                'destino' => in_str('destino'),
                'motorista' => in_str('motorista'),
                'interno' => in_str('interno'),
                'status' => in_str('status'),
                'eh_not' => in_str('eh_not'),
                'data_inicio' => normalize_date(in_str('data_inicio')),
                'data_fim' => normalize_date(in_str('data_fim')),
            ];

            $page = max(1, in_int('page') ?? 1);
            $limit = in_int('limit') ?? 20;
            if ($limit < 1 || $limit > 100) {
                $limit = 20;
            }

            eclusa_ok(['data' => get_escoltas($pdo, $filtros, $page, $limit)]);
        }

        if ($action === 'obter') {
            $id = in_int('id');
            if (!$id) {
                eclusa_error('ID não informado.');
            }

            $escolta = get_escolta_by_id($pdo, $id);
            if (!$escolta) {
                eclusa_error('Escolta não encontrada.', 'not_found', 404);
            }

            eclusa_ok(['data' => $escolta]);
        }

        if ($action === 'salvar') {
            require_write_permission();

            $id = in_int('id');
            $dataCadastro = normalize_date(in_str('data_cadastro'));
            $interno = in_str('interno');
            $horaPrevista = normalize_time(in_str('hora_prevista'));
            $destino = in_str('destino');
            $status = in_str('status') ?: 'Pendente';
            $motivo = in_str('motivo');
            $horaChegada = normalize_time(in_str('hora_chegada'));
            $horaRetorno = normalize_time(in_str('hora_retorno'));
            $motorista = in_str('motorista');
            $placa = in_str('placa');
            $ehNot = in_str('eh_not') ?: 'Não';
            $cadastradoPor = in_str('cadastrado_por') ?: ((isset($_SESSION['nome']) && $_SESSION['nome']) ? (string) $_SESSION['nome'] : 'SIGEP');

            if (!$dataCadastro) {
                eclusa_error('Informe a data da escolta.');
            }

            if (!$interno) {
                eclusa_error('Informe o interno.');
            }

            // Validar formato do interno (permite cadastro manual no padrão IPEN - NOME)
            validar_interno($interno);

            // Validar destino (bloqueia genéricos)
            validar_destino($destino);

            if ($id) {
                // Obter dados antigos para auditoria
                $stmt = $pdo->prepare('SELECT * FROM eclusa_movimentacoes_escolta WHERE id = ? LIMIT 1');
                $stmt->execute([$id]);
                $dadosAntigos = $stmt->fetch();

                if (!$dadosAntigos) {
                    eclusa_error('Escolta não encontrada para edição.', 'not_found', 404);
                }

                $sql = 'UPDATE eclusa_movimentacoes_escolta
                        SET data_cadastro = ?, interno = ?, hora_prevista = ?, destino = ?, status = ?,
                            motivo = ?, hora_chegada = ?, hora_retorno = ?, motorista = ?, placa = ?,
                            eh_not = ?, cadastrado_por = ?, atualizado_em = NOW()
                        WHERE id = ?';
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $dataCadastro,
                    $interno,
                    $horaPrevista,
                    $destino,
                    $status,
                    $motivo,
                    $horaChegada,
                    $horaRetorno,
                    $motorista,
                    $placa,
                    $ehNot,
                    $cadastradoPor,
                    $id,
                ]);

                // Registrar auditoria das alterações
                $novosDados = [
                    'data_cadastro' => $dataCadastro,
                    'interno' => $interno,
                    'hora_prevista' => $horaPrevista,
                    'destino' => $destino,
                    'status' => $status,
                    'motivo' => $motivo,
                    'hora_chegada' => $horaChegada,
                    'hora_retorno' => $horaRetorno,
                    'motorista' => $motorista,
                    'placa' => $placa,
                    'eh_not' => $ehNot,
                    'cadastrado_por' => $cadastradoPor
                ];

                foreach ($novosDados as $campo => $novoValor) {
                    $valorAntigo = $dadosAntigos[$campo] ?? null;
                    if ($valorAntigo != $novoValor) {
                        registrar_auditoria($pdo, $id, $campo, $valorAntigo, $novoValor, $cadastradoPor, 'alteracao');
                    }
                }

                eclusa_ok(['message' => 'Escolta atualizada com sucesso.', 'id' => $id]);
            }

            $sql = 'INSERT INTO eclusa_movimentacoes_escolta
                    (data_cadastro, interno, hora_prevista, destino, status, motivo, hora_chegada, hora_retorno, motorista, placa, eh_not, cadastrado_por)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $dataCadastro,
                $interno,
                $horaPrevista,
                $destino,
                $status,
                $motivo,
                $horaChegada,
                $horaRetorno,
                $motorista,
                $placa,
                $ehNot,
                $cadastradoPor,
            ]);

            $newId = (int) $pdo->lastInsertId();

            // Registrar auditoria da criação
            registrar_auditoria($pdo, $newId, 'criacao', null, json_encode([
                'data_cadastro' => $dataCadastro,
                'interno' => $interno,
                'hora_prevista' => $horaPrevista,
                'destino' => $destino,
                'status' => $status,
                'motivo' => $motivo,
                'hora_chegada' => $horaChegada,
                'hora_retorno' => $horaRetorno,
                'motorista' => $motorista,
                'placa' => $placa,
                'eh_not' => $ehNot,
                'cadastrado_por' => $cadastradoPor
            ]), $cadastradoPor, 'criacao');

            eclusa_ok(['message' => 'Escolta cadastrada com sucesso.', 'id' => $newId]);
        }

        if ($action === 'registrar_chegada') {
            require_write_permission();

            $id = in_int('id');
            $horaChegada = normalize_time(in_str('hora_chegada'));

            if (!$id) {
                eclusa_error('ID não informado.');
            }

            if (!$horaChegada) {
                eclusa_error('Hora de chegada não informada.');
            }

            // Verificar se escolta existe e está pendente
            $stmt = $pdo->prepare('SELECT id, status, hora_chegada FROM eclusa_movimentacoes_escolta WHERE id = ? LIMIT 1');
            $stmt->execute([$id]);
            $escolta = $stmt->fetch();

            if (!$escolta) {
                eclusa_error('Escolta não encontrada.', 'not_found', 404);
            }

            if ($escolta['status'] !== 'Pendente') {
                eclusa_error('Apenas escoltas com status "Pendente" podem registrar chegada.');
            }

            if (!empty($escolta['hora_chegada'])) {
                eclusa_error('Chegada já foi registrada para esta escolta.');
            }

            // Atualizar apenas a hora de chegada
            $sql = 'UPDATE eclusa_movimentacoes_escolta SET hora_chegada = ?, atualizado_em = NOW() WHERE id = ?';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$horaChegada, $id]);

            eclusa_ok(['message' => 'Chegada registrada com sucesso.', 'id' => $id]);
        }

        if ($action === 'finalizar') {
            require_write_permission();

            $id = in_int('id');
            $status = in_str('status_finalizacao'); // Corrigido: campo do formulário
            $horaRetorno = normalize_time(in_str('hora_retorno'));
            $motivo = in_str('motivo_finalizacao'); // Corrigido: campo do formulário

            if (!$id) {
                eclusa_error('ID não informado.');
            }

            if (!$status) {
                eclusa_error('Status não informado.');
            }

            if (!$horaRetorno) {
                eclusa_error('Hora de retorno não informada.');
            }

            // Validação: motivo obrigatório se status não for "Finalizado"
            if ($status !== 'Finalizado' && empty($motivo)) {
                eclusa_error('Motivo é obrigatório quando o status não é "Finalizado".');
            }

            // VALIDAÇÃO ADICIONAL: Verificar campos obrigatórios antes de finalizar
            $stmt = $pdo->prepare('SELECT * FROM eclusa_movimentacoes_escolta WHERE id = ? LIMIT 1');
            $stmt->execute([$id]);
            $escolta = $stmt->fetch();

            if (!$escolta) {
                eclusa_error('Escolta não encontrada.', 'not_found', 404);
            }

            // Verificar campos obrigatórios Apenas os essenciais para finalização
            $camposObrigatorios = [];

            // Apenas interno e destino são obrigatórios para finalização
            if (empty(trim($escolta['interno']))) {
                $camposObrigatorios[] = 'Interno';
            }

            if (empty(trim($escolta['destino']))) {
                $camposObrigatorios[] = 'Destino';
            }

            // Se houver campos obrigatórios em branco, impedir finalização
            if (!empty($camposObrigatorios)) {
                $camposStr = implode(', ', $camposObrigatorios);
                eclusa_error("Não é possível finalizar a escolta. Os seguintes campos obrigatórios estão em branco: {$camposStr}.", 'campos_obrigatorios', 400);
            }

            if ($escolta['status'] !== 'Pendente') {
                eclusa_error('Apenas escoltas com status "Pendente" podem ser finalizadas.');
            }

            // Atualizar escolta (não atualiza hora_chegada, já foi registrada antes)
            $sql = 'UPDATE eclusa_movimentacoes_escolta
                    SET status = ?, hora_retorno = ?, motivo = ?, atualizado_em = NOW()
                    WHERE id = ?';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$status, $horaRetorno, $motivo, $id]);

            eclusa_ok(['message' => 'Escolta finalizada com sucesso.', 'id' => $id]);
        }

        if ($action === 'excluir') {
            require_write_permission();

            $id = in_int('id');
            $nomeConfirmacao = in_str('nome_confirmacao');

            if (!$id) {
                eclusa_error('ID não informado.');
            }

            if (!$nomeConfirmacao) {
                eclusa_error('Nome de confirmação não informado.');
            }

            // Obter dados da escolta para validação e auditoria
            $stmt = $pdo->prepare('SELECT * FROM eclusa_movimentacoes_escolta WHERE id = ? LIMIT 1');
            $stmt->execute([$id]);
            $escolta = $stmt->fetch();

            if (!$escolta) {
                eclusa_error('Escolta não encontrada.', 'not_found', 404);
            }

            // Validar se o nome de confirmação corresponde ao nome de quem cadastrou
            if (strcasecmp(trim($nomeConfirmacao), trim($escolta['cadastrado_por'])) !== 0) {
                eclusa_error('Nome de confirmação não corresponde ao nome de quem cadastrou a escolta.');
            }

            // Registrar auditoria da exclusão (antes de excluir)
            registrar_auditoria($pdo, $id, 'exclusao', json_encode($escolta), null, $escolta['cadastrado_por'], 'exclusao');

            // Excluir a escolta
            $stmt = $pdo->prepare('DELETE FROM eclusa_movimentacoes_escolta WHERE id = ?');
            $stmt->execute([$id]);

            if ($stmt->rowCount() === 0) {
                eclusa_error('Escolta não encontrada.', 'not_found', 404);
            }

            eclusa_ok(['message' => 'Escolta #' . str_pad($id, 4, '0', STR_PAD_LEFT) . ' excluída com sucesso.']);
        }

        if ($action === 'gerar_relatorio') {
            $filtros = [
                'search' => null,
                'placa' => in_str('placa'),
                'destino' => in_str('destino'),
                'motorista' => in_str('motorista'),
                'interno' => in_str('interno'),
                'status' => in_str('status'),
                'eh_not' => in_str('eh_not'),
                'data_inicio' => normalize_date(in_str('data_inicio')),
                'data_fim' => normalize_date(in_str('data_fim')),
            ];

            $dados = get_escoltas($pdo, $filtros, 1, 5000);
            $_SESSION['relatorio_escolta'] = [
                'titulo' => gerar_titulo_relatorio($filtros),
                'filtros' => $filtros,
                'dados' => $dados['dados'],
            ];

            eclusa_ok([
                'message' => 'Relatório gerado com sucesso.',
                'redirect' => 'relatorio.php',
            ]);
        }

        // Actions do Dashboard
        if ($action === 'get_chart_data') {
            $chartType = in_str('chart_type');
            $periodo = in_str('periodo') ?? '30';

            if ($chartType === 'data') {
                eclusa_ok(['data' => getEscoltasPorData($pdo, $periodo)]);
            } elseif ($chartType === 'status') {
                eclusa_ok(['data' => getEscoltasPorStatus($pdo)]);
            } elseif ($chartType === 'hora') {
                eclusa_ok(['data' => getEscoltasPorHora($pdo)]);
            } elseif ($chartType === 'dia') {
                eclusa_ok(['data' => getEscoltasPorDia($pdo)]);
            } elseif ($chartType === 'mes') {
                eclusa_ok(['data' => getEscoltasPorMes($pdo)]);
            } else {
                eclusa_error('Tipo de gráfico não reconhecido.');
            }
        }

        if ($action === 'get_escoltas_detalhadas') {
            $data = in_str('data');
            if (!$data) {
                eclusa_error('Data não informada.');
            }
            eclusa_ok(['data' => getEscoltasDetalhadasPorData($pdo, $data)]);
        }

        if ($action === 'get_escoltas_por_status') {
            $status = in_str('status');
            if (!$status) {
                eclusa_error('Status não informado.');
            }
            eclusa_ok(['data' => getEscoltasDetalhadasPorStatus($pdo, $status)]);
        }

        if ($action === 'get_escoltas_por_destino') {
            $destino = in_str('destino');
            if (!$destino) {
                eclusa_error('Destino não informado.');
            }
            eclusa_ok(['data' => getEscoltasDetalhadasPorDestino($pdo, $destino)]);
        }

        if ($action === 'get_escoltas_por_motorista') {
            $motorista = in_str('motorista');
            if (!$motorista) {
                eclusa_error('Motorista não informado.');
            }
            eclusa_ok(['data' => getEscoltasDetalhadasPorMotorista($pdo, $motorista)]);
        }

        if ($action === 'get_escoltas_por_hora') {
            $hora = in_str('hora');
            if (!$hora) {
                eclusa_error('Hora não informada.');
            }
            eclusa_ok(['data' => getEscoltasDetalhadasPorHora($pdo, $hora)]);
        }

        if ($action === 'get_escoltas_por_dia_semana') {
            $dia = in_str('dia');
            if (!$dia) {
                eclusa_error('Dia da semana não informado.');
            }
            eclusa_ok(['data' => getEscoltasDetalhadasPorDiaSemana($pdo, $dia)]);
        }

        if ($action === 'get_escoltas_por_mes') {
            $mes = in_str('mes');
            if (!$mes) {
                eclusa_error('Mês não informado.');
            }
            eclusa_ok(['data' => getEscoltasDetalhadasPorMes($pdo, $mes)]);
        }

        if ($action === 'get_todas_escoltas') {
            $periodo = in_str('periodo') ?? '30';
            $filtros = ['data_inicio' => null, 'data_fim' => null];

            if ($periodo !== '0') {
                $intervalos = [
                    '7' => '7 DAY',
                    '30' => '30 DAY',
                    '90' => '90 DAY',
                    '180' => '180 DAY',
                    '365' => '365 DAY'
                ];
                $interval = $intervalos[$periodo] ?? '30 DAY';
                $filtros['data_inicio'] = date('Y-m-d', strtotime("-{$interval}"));
                $filtros['data_fim'] = date('Y-m-d');
            }

            eclusa_ok(['data' => get_escoltas($pdo, $filtros, 1, 1000)['dados']]);
        }

        eclusa_error('Ação não reconhecida.', 'invalid_action');
    } catch (Throwable $e) {
        eclusa_error('Erro ao processar a ação: ' . $e->getMessage(), 'exception', 500);
    }
}

$viewData = [
    'contadores' => get_contadores($pdo),
    'top_destinos' => get_top_destinos($pdo),
    'top_motoristas' => get_top_motoristas($pdo),
];
