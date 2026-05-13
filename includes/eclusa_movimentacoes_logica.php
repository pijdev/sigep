<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('America/Sao_Paulo');
mb_internal_encoding('UTF-8');

$config = require __DIR__ . '/../conf/db.php';
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

function in_str(string $key): ?string
{
    if (!isset($_POST[$key])) {
        return null;
    }

    $value = trim((string) $_POST[$key]);
    return $value === '' ? null : $value;
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
            COUNT(*) AS total_movimentacoes,
            SUM(CASE WHEN data_movimentacao = CURDATE() THEN 1 ELSE 0 END) AS movimentacoes_hoje,
            SUM(CASE WHEN data_movimentacao = CURDATE() AND hora_entrada IS NOT NULL THEN 1 ELSE 0 END) AS entradas_hoje,
            SUM(CASE WHEN data_movimentacao = CURDATE() AND hora_saida IS NOT NULL THEN 1 ELSE 0 END) AS saidas_hoje,
            SUM(CASE WHEN data_movimentacao >= DATE_FORMAT(CURDATE(), '%Y-%m-01') AND data_movimentacao < DATE_ADD(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 1 MONTH) THEN 1 ELSE 0 END) AS movimentacoes_mes
        FROM eclusa_movimentacoes
    ";

    $row = $pdo->query($sql)->fetch();

    return [
        'totalMovimentacoes' => (int) ($row['total_movimentacoes'] ?? 0),
        'movimentacoesHoje' => (int) ($row['movimentacoes_hoje'] ?? 0),
        'entradasHoje' => (int) ($row['entradas_hoje'] ?? 0),
        'saidasHoje' => (int) ($row['saidas_hoje'] ?? 0),
        'movimentacoesMes' => (int) ($row['movimentacoes_mes'] ?? 0),
    ];
}

function get_top_veiculos(PDO $pdo): array
{
    $sql = "
        SELECT
            v.id,
            v.placa,
            v.nome AS tipo_veiculo,
            COUNT(m.id) AS frequencia,
            MAX(CONCAT(m.data_movimentacao, ' ', COALESCE(m.hora_entrada, m.hora_saida, m.hora_chegada, '00:00:00'))) AS ultima_movimentacao
        FROM eclusa_movimentacoes m
        INNER JOIN eclusa_veiculos v ON v.id = m.veiculo_id
        GROUP BY v.id, v.placa, v.nome
        ORDER BY frequencia DESC, ultima_movimentacao DESC
        LIMIT 10
    ";

    return $pdo->query($sql)->fetchAll();
}

function get_top_empresas(PDO $pdo): array
{
    $sql = "
        SELECT
            e.id,
            e.nome AS empresa,
            COUNT(m.id) AS frequencia,
            MAX(CONCAT(m.data_movimentacao, ' ', COALESCE(m.hora_entrada, m.hora_saida, m.hora_chegada, '00:00:00'))) AS ultima_movimentacao
        FROM eclusa_movimentacoes m
        INNER JOIN eclusa_empresas e ON e.id = m.empresa_id
        GROUP BY e.id, e.nome
        ORDER BY frequencia DESC, ultima_movimentacao DESC
        LIMIT 10
    ";

    return $pdo->query($sql)->fetchAll();
}

function busca_autocomplete_top(PDO $pdo, string $campo): array
{
    if ($campo === 'placa') {
        $sql = "
            SELECT
                v.id,
                v.placa AS value,
                CONCAT(v.placa, ' - ', v.nome) AS label,
                v.nome AS tipo_veiculo,
                v.empresa_id
            FROM eclusa_veiculos v
            ORDER BY v.id DESC
            LIMIT 10
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    if ($campo === 'veiculo') {
        $sql = "
            SELECT
                v.id,
                v.nome AS value,
                CONCAT(v.nome, ' (', v.placa, ')') AS label,
                v.placa,
                v.empresa_id
            FROM eclusa_veiculos v
            ORDER BY v.id DESC
            LIMIT 10
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    if ($campo === 'empresa') {
        $sql = "
            SELECT
                e.id,
                e.nome AS value,
                e.nome AS label
            FROM eclusa_empresas e
            ORDER BY e.id DESC
            LIMIT 10
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    if ($campo === 'motorista') {
        $sql = "
            SELECT
                m.id,
                m.nome AS value,
                m.nome AS label
            FROM eclusa_motoristas m
            ORDER BY m.id DESC
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
            SELECT
                v.id,
                v.placa AS value,
                CONCAT(v.placa, ' - ', v.nome) AS label,
                v.nome AS tipo_veiculo,
                v.empresa_id
            FROM eclusa_veiculos v
            WHERE v.placa LIKE ?
            ORDER BY v.placa ASC
            LIMIT 20
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$like]);
        return $stmt->fetchAll();
    }

    if ($campo === 'veiculo') {
        $sql = "
            SELECT
                v.id,
                v.nome AS value,
                CONCAT(v.nome, ' (', v.placa, ')') AS label,
                v.placa,
                v.empresa_id
            FROM eclusa_veiculos v
            WHERE v.nome LIKE ? OR v.modelo LIKE ?
            ORDER BY v.nome ASC
            LIMIT 20
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$like, $like]);
        return $stmt->fetchAll();
    }

    if ($campo === 'empresa') {
        $sql = "
            SELECT
                e.id,
                e.nome AS value,
                e.nome AS label
            FROM eclusa_empresas e
            WHERE e.nome LIKE ?
            ORDER BY e.nome ASC
            LIMIT 20
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$like]);
        return $stmt->fetchAll();
    }

    if ($campo === 'motorista') {
        $sql = "
            SELECT
                m.id,
                m.nome AS value,
                m.nome AS label
            FROM eclusa_motoristas m
            WHERE m.nome LIKE ?
            ORDER BY m.nome ASC
            LIMIT 20
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$like]);
        return $stmt->fetchAll();
    }

    return [];
}

function build_where_from_filtros(array $filtros, array &$params): string
{
    $where = [];

    if (!empty($filtros['search'])) {
        $term = '%' . $filtros['search'] . '%';
        $where[] = '(v.placa LIKE ? OR v.nome LIKE ? OR e.nome LIKE ? OR mot.nome LIKE ? OR m.observacoes LIKE ?)';
        array_push($params, $term, $term, $term, $term, $term);
    }

    if (!empty($filtros['placa'])) {
        $where[] = 'v.placa LIKE ?';
        $params[] = '%' . $filtros['placa'] . '%';
    }

    if (!empty($filtros['veiculo'])) {
        $where[] = 'v.nome LIKE ?';
        $params[] = '%' . $filtros['veiculo'] . '%';
    }

    if (!empty($filtros['empresa'])) {
        $where[] = 'e.nome LIKE ?';
        $params[] = '%' . $filtros['empresa'] . '%';
    }

    if (!empty($filtros['motorista'])) {
        $where[] = 'mot.nome LIKE ?';
        $params[] = '%' . $filtros['motorista'] . '%';
    }

    if (!empty($filtros['tipo_movimento'])) {
        if ($filtros['tipo_movimento'] === 'entrada') {
            $where[] = 'm.hora_entrada IS NOT NULL';
        }
        if ($filtros['tipo_movimento'] === 'saida') {
            $where[] = 'm.hora_saida IS NOT NULL';
        }
    }

    if (!empty($filtros['data_inicio'])) {
        $where[] = 'm.data_movimentacao >= ?';
        $params[] = $filtros['data_inicio'];
    }

    if (!empty($filtros['data_fim'])) {
        $where[] = 'm.data_movimentacao <= ?';
        $params[] = $filtros['data_fim'];
    }

    return count($where) ? ('WHERE ' . implode(' AND ', $where)) : '';
}

function get_movimentacoes(PDO $pdo, array $filtros, int $page, int $limit): array
{
    $offset = ($page - 1) * $limit;
    $params = [];
    $whereSql = build_where_from_filtros($filtros, $params);

    $baseFrom = "
        FROM eclusa_movimentacoes m
        LEFT JOIN eclusa_veiculos v ON v.id = m.veiculo_id
        LEFT JOIN eclusa_empresas e ON e.id = m.empresa_id
        LEFT JOIN eclusa_motoristas mot ON mot.id = m.motorista_id
    ";

    $sqlList = "
        SELECT
            m.id,
            m.data_movimentacao,
            m.hora_chegada,
            m.hora_entrada,
            m.hora_saida,
            m.observacoes,
            m.cadastrado_por,
            m.veiculo_id,
            m.empresa_id,
            m.motorista_id,
            v.placa AS placa_veiculo,
            v.nome AS tipo_veiculo,
            e.nome AS empresa,
            mot.nome AS motorista,
            CASE
                WHEN m.hora_entrada IS NOT NULL AND m.hora_saida IS NULL THEN 'entrada'
                WHEN m.hora_saida IS NOT NULL AND m.hora_entrada IS NULL THEN 'saida'
                WHEN m.hora_entrada IS NOT NULL AND m.hora_saida IS NOT NULL THEN 'entrada_saida'
                ELSE 'indefinido'
            END AS tipo_movimento
            {$baseFrom}
            {$whereSql}
        ORDER BY m.data_movimentacao DESC, COALESCE(m.hora_entrada, m.hora_saida, m.hora_chegada, '00:00:00') DESC, m.id DESC
        LIMIT {$limit} OFFSET {$offset}
    ";

    $stmtList = $pdo->prepare($sqlList);
    $stmtList->execute($params);
    $rows = $stmtList->fetchAll();

    $sqlCount = "SELECT COUNT(*) {$baseFrom} {$whereSql}";
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

function get_movimentacao_by_id(PDO $pdo, int $id): ?array
{
    $sql = "
        SELECT
            m.id,
            m.data_movimentacao,
            m.hora_chegada,
            m.hora_entrada,
            m.hora_saida,
            m.observacoes,
            m.cadastrado_por,
            m.veiculo_id,
            m.empresa_id,
            m.motorista_id,
            v.placa AS placa_veiculo,
            v.nome AS tipo_veiculo,
            e.nome AS empresa,
            mot.nome AS motorista
        FROM eclusa_movimentacoes m
        LEFT JOIN eclusa_veiculos v ON v.id = m.veiculo_id
        LEFT JOIN eclusa_empresas e ON e.id = m.empresa_id
        LEFT JOIN eclusa_motoristas mot ON mot.id = m.motorista_id
        WHERE m.id = ?
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function gerar_titulo_relatorio(array $filtros): string
{
    $partes = ['Relatório de Movimentações da Eclusa'];

    if (!empty($filtros['data_inicio']) || !empty($filtros['data_fim'])) {
        $inicio = !empty($filtros['data_inicio']) ? date('d/m/Y', strtotime($filtros['data_inicio'])) : '...';
        $fim = !empty($filtros['data_fim']) ? date('d/m/Y', strtotime($filtros['data_fim'])) : '...';
        $partes[] = sprintf('período %s a %s', $inicio, $fim);
    }

    if (!empty($filtros['placa'])) {
        $partes[] = 'placa ' . $filtros['placa'];
    }

    if (!empty($filtros['empresa'])) {
        $partes[] = 'empresa ' . $filtros['empresa'];
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

        if ($action === 'get_top_veiculos') {
            eclusa_ok(['data' => get_top_veiculos($pdo)]);
        }

        if ($action === 'get_top_empresas') {
            eclusa_ok(['data' => get_top_empresas($pdo)]);
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

        if ($action === 'salvar_veiculo') {
            require_write_permission();

            $placa = in_str('placa');
            $nome = in_str('nome');
            $modelo = in_str('modelo') ?: $nome;

            if (!$placa || !$nome) {
                eclusa_error('Placa e nome são obrigatórios.');
            }

            $placa = formatar_placa($placa);

            // Verificar se já existe
            $stmt = $pdo->prepare('SELECT id FROM eclusa_veiculos WHERE placa = ? LIMIT 1');
            $stmt->execute([$placa]);
            if ($stmt->fetchColumn()) {
                eclusa_error('Veículo com esta placa já existe.');
            }

            $stmt = $pdo->prepare('INSERT INTO eclusa_veiculos (nome, modelo, placa, eh_viatura, tipo_origem) VALUES (?, ?, ?, 0, ?)');
            $stmt->execute([$nome, $modelo, $placa, 'Manual']);

            eclusa_ok(['message' => 'Veículo cadastrado com sucesso.', 'data' => ['id' => (int) $pdo->lastInsertId()]]);
        }

        if ($action === 'salvar_empresa') {
            require_write_permission();

            $nome = in_str('nome');

            if (!$nome) {
                eclusa_error('Nome da empresa é obrigatório.');
            }

            // Verificar se já existe
            $stmt = $pdo->prepare('SELECT id FROM eclusa_empresas WHERE nome = ? LIMIT 1');
            $stmt->execute([$nome]);
            if ($stmt->fetchColumn()) {
                eclusa_error('Empresa já existe.');
            }

            $stmt = $pdo->prepare('INSERT INTO eclusa_empresas (nome) VALUES (?)');
            $stmt->execute([$nome]);

            eclusa_ok(['message' => 'Empresa cadastrada com sucesso.', 'data' => ['id' => (int) $pdo->lastInsertId()]]);
        }

        if ($action === 'salvar_motorista') {
            require_write_permission();

            $nome = in_str('nome');

            if (!$nome) {
                eclusa_error('Nome do motorista é obrigatório.');
            }

            // Verificar se já existe
            $stmt = $pdo->prepare('SELECT id FROM eclusa_motoristas WHERE nome = ? LIMIT 1');
            $stmt->execute([$nome]);
            if ($stmt->fetchColumn()) {
                eclusa_error('Motorista já existe.');
            }

            $stmt = $pdo->prepare('INSERT INTO eclusa_motoristas (nome) VALUES (?)');
            $stmt->execute([$nome]);

            eclusa_ok(['message' => 'Motorista cadastrado com sucesso.', 'data' => ['id' => (int) $pdo->lastInsertId()]]);
        }

        if ($action === 'listar') {
            $filtros = [
                'search' => in_str('search'),
                'placa' => in_str('placa'),
                'veiculo' => in_str('veiculo'),
                'empresa' => in_str('empresa'),
                'motorista' => in_str('motorista'),
                'tipo_movimento' => in_str('tipo_movimento'),
                'data_inicio' => normalize_date(in_str('data_inicio')),
                'data_fim' => normalize_date(in_str('data_fim')),
            ];

            $page = max(1, in_int('page') ?? 1);
            $limit = in_int('limit') ?? 20;
            if ($limit < 1 || $limit > 100) {
                $limit = 20;
            }

            eclusa_ok(['data' => get_movimentacoes($pdo, $filtros, $page, $limit)]);
        }

        if ($action === 'obter') {
            $id = in_int('id');
            if (!$id) {
                eclusa_error('ID não informado.');
            }

            $mov = get_movimentacao_by_id($pdo, $id);
            if (!$mov) {
                eclusa_error('Movimentação não encontrada.', 'not_found', 404);
            }

            eclusa_ok(['data' => $mov]);
        }

        if ($action === 'salvar') {
            require_write_permission();

            $id = in_int('id');
            $dataMovimentacao = normalize_date(in_str('data_movimentacao'));
            $horaChegada = normalize_time(in_str('hora_chegada'));
            $horaEntrada = normalize_time(in_str('hora_entrada'));
            $horaSaida = normalize_time(in_str('hora_saida'));
            $observacoes = in_str('observacoes');
            $cadastradoPor = in_str('cadastrado_por') ?: ((isset($_SESSION['nome']) && $_SESSION['nome']) ? (string) $_SESSION['nome'] : 'SIGEP');

            if (!$dataMovimentacao) {
                eclusa_error('Informe a data da movimentação.');
            }

            if (!$horaChegada && !$horaEntrada && !$horaSaida) {
                eclusa_error('Informe ao menos um horário: chegada, entrada ou saída.');
            }

            $empresaId = resolve_empresa_id($pdo, in_int('empresa_id'), in_str('empresa'));
            $motoristaId = resolve_motorista_id($pdo, in_int('motorista_id'), in_str('motorista'));
            $veiculoId = resolve_veiculo_id($pdo, in_int('veiculo_id'), in_str('placa_veiculo'), in_str('tipo_veiculo'), $empresaId);

            if ($id) {
                $stmt = $pdo->prepare('SELECT id FROM eclusa_movimentacoes WHERE id = ? LIMIT 1');
                $stmt->execute([$id]);
                if (!$stmt->fetchColumn()) {
                    eclusa_error('Movimentação não encontrada para edição.', 'not_found', 404);
                }

                $sql = 'UPDATE eclusa_movimentacoes
                        SET data_movimentacao = ?, veiculo_id = ?, empresa_id = ?, motorista_id = ?,
                            hora_chegada = ?, hora_entrada = ?, hora_saida = ?, observacoes = ?, cadastrado_por = ?, atualizado_em = NOW()
                        WHERE id = ?';
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $dataMovimentacao,
                    $veiculoId,
                    $empresaId,
                    $motoristaId,
                    $horaChegada,
                    $horaEntrada,
                    $horaSaida,
                    $observacoes,
                    $cadastradoPor,
                    $id,
                ]);

                eclusa_ok(['message' => 'Movimentação atualizada com sucesso.', 'id' => $id]);
            }

            $sql = 'INSERT INTO eclusa_movimentacoes
                    (data_movimentacao, veiculo_id, empresa_id, motorista_id, hora_chegada, hora_entrada, hora_saida, observacoes, cadastrado_por)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $dataMovimentacao,
                $veiculoId,
                $empresaId,
                $motoristaId,
                $horaChegada,
                $horaEntrada,
                $horaSaida,
                $observacoes,
                $cadastradoPor,
            ]);

            eclusa_ok(['message' => 'Movimentação cadastrada com sucesso.', 'id' => (int) $pdo->lastInsertId()]);
        }

        if ($action === 'excluir') {
            require_write_permission();

            $id = in_int('id');
            if (!$id) {
                eclusa_error('ID não informado.');
            }

            $stmt = $pdo->prepare('DELETE FROM eclusa_movimentacoes WHERE id = ?');
            $stmt->execute([$id]);

            if ($stmt->rowCount() === 0) {
                eclusa_error('Movimentação não encontrada.', 'not_found', 404);
            }

            eclusa_ok(['message' => 'Movimentação excluída com sucesso.']);
        }

        if ($action === 'gerar_relatorio') {
            $filtros = [
                'search' => null,
                'placa' => in_str('placa'),
                'veiculo' => in_str('veiculo'),
                'empresa' => in_str('empresa'),
                'motorista' => in_str('motorista'),
                'tipo_movimento' => in_str('tipo_movimento'),
                'data_inicio' => normalize_date(in_str('data_inicio')),
                'data_fim' => normalize_date(in_str('data_fim')),
            ];

            $dados = get_movimentacoes($pdo, $filtros, 1, 5000);
            $_SESSION['relatorio_eclusa'] = [
                'titulo' => gerar_titulo_relatorio($filtros),
                'filtros' => $filtros,
                'dados' => $dados['dados'],
            ];

            eclusa_ok([
                'message' => 'Relatório gerado com sucesso.',
                'redirect' => 'paginas/eclusa_relatorio.php',
            ]);
        }

        eclusa_error('Ação não reconhecida.', 'invalid_action');
    } catch (Throwable $e) {
        eclusa_error('Erro ao processar a ação: ' . $e->getMessage(), 'exception', 500);
    }
}

$contadores = get_contadores($pdo);
$top_veiculos = get_top_veiculos($pdo);
$top_empresas = get_top_empresas($pdo);
?>
