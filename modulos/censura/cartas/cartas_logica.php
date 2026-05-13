<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('America/Sao_Paulo');

$eh_admin = isset($_SESSION['user_admin']) && $_SESSION['user_admin'] === true;
$eh_censura = isset($_SESSION['perm_censura']) && (int)$_SESSION['perm_censura'] > 0;
$tem_permissao_cartas = $eh_admin || $eh_censura;

function cartas_json_response(array $payload, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function cartas_normalize_tipo_movimentacao(?string $tipo): string
{
    return ($tipo === 'Saida') ? 'Saida' : 'Entrada';
}

function cartas_json_encode_safe(array $data): string
{
    return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function cartas_registrar_historico(PDO $pdo, int $idCarta, string $operacao, ?array $antes, ?array $depois, ?int $usuarioId, ?string $usuarioNome): void
{
    $stmtHist = $pdo->prepare(
        "INSERT INTO censura_cartas_historico
        (id_carta, operacao, valor_antigo, valor_novo, usuario_id, usuario_nome, data_hora)
        VALUES (?, ?, ?, ?, ?, ?, NOW())"
    );

    $stmtHist->execute([
        $idCarta,
        $operacao,
        $antes === null ? null : cartas_json_encode_safe($antes),
        $depois === null ? null : cartas_json_encode_safe($depois),
        $usuarioId,
        $usuarioNome
    ]);
}

function cartas_atualizar_vinculo(PDO $pdo, int $ipen, int $idCorrespondente, string $tipoMov): void
{
    $stmtInsert = $pdo->prepare(
        "INSERT INTO censura_cartas_vinculos
        (id_interno, id_correspondente, score_entrada, score_saida, ultimo_uso_entrada, ultimo_uso_saida, preferencial_entrada, preferencial_saida)
        VALUES (?, ?, 0, 0, NULL, NULL, 'N', 'N')
        ON DUPLICATE KEY UPDATE id = id"
    );
    $stmtInsert->execute([$ipen, $idCorrespondente]);

    if ($tipoMov === 'Entrada') {
        $stmtUpdate = $pdo->prepare(
            "UPDATE censura_cartas_vinculos
             SET score_entrada = score_entrada + 1, ultimo_uso_entrada = NOW()
             WHERE id_interno = ? AND id_correspondente = ?"
        );
    } else {
        $stmtUpdate = $pdo->prepare(
            "UPDATE censura_cartas_vinculos
             SET score_saida = score_saida + 1, ultimo_uso_saida = NOW()
             WHERE id_interno = ? AND id_correspondente = ?"
        );
    }

    $stmtUpdate->execute([$ipen, $idCorrespondente]);
}

try {
    $config = require __DIR__ . '/../../../conf/db.php';
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['user'], $config['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (Throwable $e) {
    if (isset($_REQUEST['acao'])) {
        cartas_json_response(['status' => 'error', 'msg' => 'Falha de conexão com banco de dados.'], 500);
    }
    throw $e;
}

$monitor_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$monitor_nome = $_SESSION['user_nome'] ?? 'Usuario Sistema';

// Carregar estatísticas
$stmtStats = $pdo->query("SELECT
    COUNT(*) as total,
    SUM(CASE WHEN status_censura = 'Liberada' THEN 1 ELSE 0 END) as liberadas,
    SUM(CASE WHEN status_censura = 'Retida' THEN 1 ELSE 0 END) as retidas,
    SUM(CASE WHEN status_censura = 'Devolvida' THEN 1 ELSE 0 END) as devolvidas
    FROM censura_cartas WHERE status_registro = 'Ativo'");
$stats = $stmtStats->fetch();

$total_cartas = $stats['total'] ?? 0;
$cartas_liberadas = $stats['liberadas'] ?? 0;
$cartas_retidas = $stats['retidas'] ?? 0;
$cartas_devolvidas = $stats['devolvidas'] ?? 0;

// Construir query principal
$query = "SELECT * FROM censura_cartas WHERE status_registro = 'Ativo'";
$params = [];

if (!empty($_GET['busca'])) {
    $busca = $_GET['busca'];
    $query .= " AND (id_interno LIKE ? OR interno_nome LIKE ? OR interno_nome_social LIKE ? OR correspondente_nome LIKE ?)";
    $params = array_merge($params, ["%$busca%", "%$busca%", "%$busca%", "%$busca%"]);
}

if (!empty($_GET['tipo'])) {
    $query .= " AND tipo_movimentacao = ?";
    $params[] = $_GET['tipo'];
}

if (!empty($_GET['status_censura'])) {
    $query .= " AND status_censura = ?";
    $params[] = $_GET['status_censura'];
}

if (!empty($_GET['status_registro'])) {
    $query .= " AND status_registro = ?";
    $params[] = $_GET['status_registro'];
}

$query .= " ORDER BY recebido_em DESC, id DESC LIMIT 100";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$cartas = $stmt->fetchAll();

if (isset($_REQUEST['acao'])) {
    $acao = (string)$_REQUEST['acao'];

    if ($acao === 'stats') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => 'success',
            'total' => $total_cartas,
            'liberadas' => $cartas_liberadas,
            'retidas' => $cartas_retidas,
            'devolvidas' => $cartas_devolvidas
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($acao === 'buscar_interno') {
        $termo = trim((string)($_GET['termo'] ?? ''));
        if ($termo === '') {
            cartas_json_response(['status' => 'success', 'dados' => []]);
        }

        $stmt = $pdo->prepare(
            "SELECT ipen, nome, nome_social, galeria, bloco, res
             FROM internos
             WHERE status = 'A' AND (ipen LIKE ? OR nome LIKE ? OR nome_social LIKE ?)
             ORDER BY nome LIMIT 10"
        );
        $stmt->execute(["%$termo%", "%$termo%", "%$termo%"]);
        $internos = $stmt->fetchAll();

        cartas_json_response(['status' => 'success', 'dados' => $internos]);
    }

    if ($acao === 'buscar_correspondente') {
        $termo = trim((string)($_GET['termo'] ?? ''));
        if ($termo === '') {
            cartas_json_response(['status' => 'success', 'dados' => []]);
        }

        $stmt = $pdo->prepare(
            "SELECT id, nome, vinculo, logradouro, numero, bairro, cidade, uf, cep, complemento
             FROM censura_cartas_correspondentes
             WHERE ativo = 'S' AND nome LIKE ?
             ORDER BY nome LIMIT 10"
        );
        $stmt->execute(["%$termo%"]);
        $correspondentes = $stmt->fetchAll();

        cartas_json_response(['status' => 'success', 'dados' => $correspondentes]);
    }

    if ($acao === 'sugestao_por_interno') {
        $ipen = (int)($_GET['ipen'] ?? 0);
        $tipo = (string)($_GET['tipo_movimentacao'] ?? '');

        $stmt = $pdo->prepare(
            "SELECT c.* FROM censura_cartas_correspondentes c
             INNER JOIN censura_cartas_vinculos v ON c.id = v.id_correspondente
             WHERE v.id_interno = ? AND c.ativo = 'S'
             ORDER BY v.score_{$tipo} DESC, v.ultimo_uso_{$tipo} DESC
             LIMIT 1"
        );
        $stmt->execute([$ipen]);
        $sugestao = $stmt->fetch();

        cartas_json_response(['status' => 'success', 'sugestao' => $sugestao]);
    }

    if ($acao === 'sugestao_por_correspondente') {
        $idCorresp = (int)($_GET['id_correspondente'] ?? 0);
        $tipo = (string)($_GET['tipo_movimentacao'] ?? '');

        $stmt = $pdo->prepare(
            "SELECT i.ipen, i.nome, i.nome_social, i.galeria, i.bloco, i.res
             FROM internos i
             INNER JOIN censura_cartas_vinculos v ON i.ipen = v.id_interno
             WHERE v.id_correspondente = ? AND i.status = 'A'
             ORDER BY v.score_{$tipo} DESC, v.ultimo_uso_{$tipo} DESC
             LIMIT 1"
        );
        $stmt->execute([$idCorresp]);
        $sugestao = $stmt->fetch();

        cartas_json_response(['status' => 'success', 'sugestao' => $sugestao]);
    }

    if ($acao === 'salvar_carta') {
        if (!$tem_permissao_cartas) {
            cartas_json_response(['status' => 'error', 'msg' => 'Sem permissão para acessar este módulo.'], 403);
        }

        $dados = $_POST;
        $dados['monitor_id'] = $monitor_id;
        $dados['monitor_nome'] = $monitor_nome;

        // Buscar dados do interno
        $stmtInterno = $pdo->prepare("SELECT nome, nome_social, galeria, bloco, res FROM internos WHERE ipen = ?");
        $stmtInterno->execute([$dados['id_interno']]);
        $interno = $stmtInterno->fetch();

        if (!$interno) {
            cartas_json_response(['status' => 'error', 'msg' => 'Interno não encontrado.']);
        }

        $dados['interno_nome'] = $interno['nome'];
        $dados['interno_nome_social'] = $interno['nome_social'];
        $dados['interno_galeria'] = $interno['galeria'];
        $dados['interno_bloco'] = $interno['bloco'];
        $dados['interno_res'] = $interno['res'];

        // Inserir carta
        $stmt = $pdo->prepare(
            "INSERT INTO censura_cartas
            (tipo_movimentacao, id_interno, interno_nome, interno_nome_social, interno_galeria, interno_bloco, interno_res,
             id_correspondente, correspondente_nome, correspondente_vinculo, correspondente_logradouro, correspondente_numero,
             correspondente_bairro, correspondente_cidade, correspondente_uf, correspondente_cep, correspondente_complemento,
             status_censura, observacoes_censura, motivo_retencao, recebido_em, monitor_id, monitor_nome)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $dados['tipo_movimentacao'],
            $dados['id_interno'],
            $dados['interno_nome'],
            $dados['interno_nome_social'],
            $dados['interno_galeria'],
            $dados['interno_bloco'],
            $dados['interno_res'],
            $dados['id_correspondente'],
            $dados['correspondente_nome'],
            $dados['correspondente_vinculo'],
            $dados['correspondente_logradouro'],
            $dados['correspondente_numero'],
            $dados['correspondente_bairro'],
            $dados['correspondente_cidade'],
            $dados['correspondente_uf'],
            $dados['correspondente_cep'],
            $dados['correspondente_complemento'],
            $dados['status_censura'],
            $dados['observacoes_censura'],
            $dados['motivo_retencao'],
            $dados['recebido_em'],
            $dados['monitor_id'],
            $dados['monitor_nome']
        ]);

        $idCarta = $pdo->lastInsertId();

        // Atualizar vínculo
        cartas_atualizar_vinculo($pdo, $dados['id_interno'], $dados['id_correspondente'], $dados['tipo_movimentacao']);

        // Registrar histórico
        cartas_registrar_historico($pdo, $idCarta, 'INSERT', null, $dados, $monitor_id, $monitor_nome);

        cartas_json_response(['status' => 'success', 'msg' => 'Carta cadastrada com sucesso!', 'id' => $idCarta]);
    }

    if ($acao === 'atualizar_status') {
        if (!$tem_permissao_cartas) {
            cartas_json_response(['status' => 'error', 'msg' => 'Sem permissão para acessar este módulo.'], 403);
        }

        $idCarta = (int)($_POST['id_carta'] ?? 0);
        $novoStatus = (string)($_POST['status_censura'] ?? '');
        $observacoes = (string)($_POST['observacoes_censura'] ?? '');
        $motivoRetencao = (string)($_POST['motivo_retencao'] ?? '');

        if ($idCarta <= 0 || empty($novoStatus)) {
            cartas_json_response(['status' => 'error', 'msg' => 'Dados inválidos.']);
        }

        // Buscar dados atuais
        $stmtAtual = $pdo->prepare("SELECT * FROM censura_cartas WHERE id = ?");
        $stmtAtual->execute([$idCarta]);
        $atual = $stmtAtual->fetch();

        if (!$atual) {
            cartas_json_response(['status' => 'error', 'msg' => 'Carta não encontrada.']);
        }

        // Atualizar
        $stmt = $pdo->prepare(
            "UPDATE censura_cartas
             SET status_censura = ?, observacoes_censura = ?, motivo_retencao = ?, concluido_em = ?
             WHERE id = ?"
        );
        $stmt->execute([$novoStatus, $observacoes, $motivoRetencao, ($novoStatus !== 'Liberada' ? date('Y-m-d H:i:s') : null), $idCarta]);

        // Registrar histórico
        $depois = $atual;
        $depois['status_censura'] = $novoStatus;
        $depois['observacoes_censura'] = $observacoes;
        $depois['motivo_retencao'] = $motivoRetencao;
        $depois['concluido_em'] = ($novoStatus !== 'Liberada' ? date('Y-m-d H:i:s') : null);

        cartas_registrar_historico($pdo, $idCarta, 'STATUS', $atual, $depois, $monitor_id, $monitor_nome);

        cartas_json_response(['status' => 'success', 'msg' => 'Status atualizado com sucesso!']);
    }

    if ($acao === 'listar_historico') {
        $idCarta = (int)($_GET['id_carta'] ?? 0);

        $stmt = $pdo->prepare(
            "SELECT * FROM censura_cartas_historico
             WHERE id_carta = ? ORDER BY data_hora DESC"
        );
        $stmt->execute([$idCarta]);
        $historico = $stmt->fetchAll();

        // Decodificar JSON
        foreach ($historico as &$item) {
            $item['valor_antigo'] = $item['valor_antigo'] ? json_decode($item['valor_antigo'], true) : null;
            $item['valor_novo'] = $item['valor_novo'] ? json_decode($item['valor_novo'], true) : null;
        }

        cartas_json_response(['status' => 'success', 'dados' => $historico]);
    }

    if ($acao === 'cancelar_carta') {
        if (!$tem_permissao_cartas) {
            cartas_json_response(['status' => 'error', 'msg' => 'Sem permissão para acessar este módulo.'], 403);
        }

        $idCarta = (int)($_POST['id_carta'] ?? 0);
        $motivo = (string)($_POST['motivo_cancelamento'] ?? '');

        if ($idCarta <= 0 || empty($motivo)) {
            cartas_json_response(['status' => 'error', 'msg' => 'Dados inválidos.']);
        }

        // Buscar dados atuais
        $stmtAtual = $pdo->prepare("SELECT * FROM censura_cartas WHERE id = ?");
        $stmtAtual->execute([$idCarta]);
        $atual = $stmtAtual->fetch();

        if (!$atual) {
            cartas_json_response(['status' => 'error', 'msg' => 'Carta não encontrada.']);
        }

        // Atualizar
        $stmt = $pdo->prepare(
            "UPDATE censura_cartas
             SET status_registro = 'Cancelado', cancelado_em = NOW(), cancelado_por_id = ?, cancelado_por_nome = ?, motivo_cancelamento = ?
             WHERE id = ?"
        );
        $stmt->execute([$monitor_id, $monitor_nome, $motivo, $idCarta]);

        // Registrar histórico
        $depois = $atual;
        $depois['status_registro'] = 'Cancelado';
        $depois['cancelado_em'] = date('Y-m-d H:i:s');
        $depois['cancelado_por_id'] = $monitor_id;
        $depois['cancelado_por_nome'] = $monitor_nome;
        $depois['motivo_cancelamento'] = $motivo;

        cartas_registrar_historico($pdo, $idCarta, 'CANCELAMENTO', $atual, $depois, $monitor_id, $monitor_nome);

        cartas_json_response(['status' => 'success', 'msg' => 'Carta cancelada com sucesso!']);
    }

    if ($acao === 'exportar_csv') {
        if (!$tem_permissao_cartas) {
            cartas_json_response(['status' => 'error', 'msg' => 'Sem permissão para acessar este módulo.'], 403);
        }

        // Construir query com filtros
        $query = "SELECT * FROM censura_cartas WHERE 1=1";
        $params = [];

        if (!empty($_GET['busca'])) {
            $busca = $_GET['busca'];
            $query .= " AND (id_interno LIKE ? OR interno_nome LIKE ? OR correspondente_nome LIKE ?)";
            $params = array_merge($params, ["%$busca%", "%$busca%", "%$busca%"]);
        }

        if (!empty($_GET['tipo'])) {
            $query .= " AND tipo_movimentacao = ?";
            $params[] = $_GET['tipo'];
        }

        if (!empty($_GET['status_censura'])) {
            $query .= " AND status_censura = ?";
            $params[] = $_GET['status_censura'];
        }

        $query .= " ORDER BY recebido_em DESC, id DESC";

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $cartas = $stmt->fetchAll();

        // Gerar CSV
        $filename = 'cartas_' . date('Y-m-d_H-i-s') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');

        // Cabeçalho
        fputcsv($output, [
            'ID',
            'Data',
            'Tipo',
            'IPEN',
            'Interno',
            'Nome Social',
            'Galeria',
            'Bloco',
            'Res',
            'Correspondente',
            'Vínculo',
            'Logradouro',
            'Número',
            'Bairro',
            'Cidade',
            'UF',
            'CEP',
            'Status',
            'Observações',
            'Motivo Retenção',
            'Monitor',
            'Data Conclusão'
        ]);

        // Dados
        foreach ($cartas as $carta) {
            fputcsv($output, [
                $carta['id'],
                date('d/m/Y H:i', strtotime($carta['recebido_em'])),
                $carta['tipo_movimentacao'],
                $carta['id_interno'],
                $carta['interno_nome'],
                $carta['interno_nome_social'],
                $carta['interno_galeria'],
                $carta['interno_bloco'],
                $carta['interno_res'],
                $carta['correspondente_nome'],
                $carta['correspondente_vinculo'],
                $carta['correspondente_logradouro'],
                $carta['correspondente_numero'],
                $carta['correspondente_bairro'],
                $carta['correspondente_cidade'],
                $carta['correspondente_uf'],
                $carta['correspondente_cep'],
                $carta['status_censura'],
                $carta['observacoes_censura'],
                $carta['motivo_retencao'],
                $carta['monitor_nome'],
                $carta['concluido_em'] ? date('d/m/Y H:i', strtotime($carta['concluido_em'])) : ''
            ]);
        }

        fclose($output);
        exit;
    }
}
