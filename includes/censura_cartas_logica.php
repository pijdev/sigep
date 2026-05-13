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
    $config = require __DIR__ . '/../conf/db.php';
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

if (isset($_REQUEST['acao'])) {
    if (!$tem_permissao_cartas) {
        cartas_json_response(['status' => 'error', 'msg' => 'Acesso negado ao módulo de cartas.'], 403);
    }

    $acao = (string)$_REQUEST['acao'];

    if ($acao === 'buscar_interno') {
        $termo = trim((string)($_GET['termo'] ?? ''));
        if ($termo === '') {
            cartas_json_response(['status' => 'success', 'dados' => []]);
        }

        $like = '%' . $termo . '%';
        $stmt = $pdo->prepare(
            "SELECT ipen, nome, nome_social, galeria, bloco, res
             FROM internos
             WHERE status = 'A' AND (CAST(ipen AS CHAR) LIKE ? OR nome LIKE ? OR nome_social LIKE ?)
             ORDER BY COALESCE(NULLIF(nome_social, ''), nome) ASC
             LIMIT 10"
        );
        $stmt->execute([$like, $like, $like]);
        cartas_json_response(['status' => 'success', 'dados' => $stmt->fetchAll()]);
    }

    if ($acao === 'buscar_correspondente') {
        $termo = trim((string)($_GET['termo'] ?? ''));
        if ($termo === '') {
            cartas_json_response(['status' => 'success', 'dados' => []]);
        }

        $like = '%' . $termo . '%';
        $stmt = $pdo->prepare(
            "SELECT id, nome, vinculo, logradouro, numero, bairro, cidade, uf, cep, complemento
             FROM censura_cartas_correspondentes
             WHERE ativo = 'S' AND (nome LIKE ? OR vinculo LIKE ? OR cidade LIKE ? OR bairro LIKE ?)
             ORDER BY nome ASC
             LIMIT 15"
        );
        $stmt->execute([$like, $like, $like, $like]);
        cartas_json_response(['status' => 'success', 'dados' => $stmt->fetchAll()]);
    }

    if ($acao === 'sugestao_por_interno') {
        $ipen = (int)($_GET['ipen'] ?? 0);
        $tipo = cartas_normalize_tipo_movimentacao($_GET['tipo_movimentacao'] ?? 'Entrada');

        if ($ipen <= 0) {
            cartas_json_response(['status' => 'success', 'sugestao' => null, 'recentes' => []]);
        }

        $orderCampoPreferencial = $tipo === 'Entrada' ? 'v.preferencial_entrada' : 'v.preferencial_saida';
        $orderCampoScore = $tipo === 'Entrada' ? 'v.score_entrada' : 'v.score_saida';
        $orderCampoUltimoUso = $tipo === 'Entrada' ? 'v.ultimo_uso_entrada' : 'v.ultimo_uso_saida';

        $stmt = $pdo->prepare(
            "SELECT
                v.id_correspondente,
                v.score_entrada, v.score_saida, v.ultimo_uso_entrada, v.ultimo_uso_saida,
                c.id, c.nome, c.vinculo, c.logradouro, c.numero, c.bairro, c.cidade, c.uf, c.cep, c.complemento
             FROM censura_cartas_vinculos v
             INNER JOIN censura_cartas_correspondentes c ON c.id = v.id_correspondente
             WHERE v.id_interno = ? AND c.ativo = 'S'
             ORDER BY {$orderCampoPreferencial} DESC, {$orderCampoScore} DESC, {$orderCampoUltimoUso} DESC, c.nome ASC
             LIMIT 5"
        );
        $stmt->execute([$ipen]);
        $rows = $stmt->fetchAll();

        cartas_json_response([
            'status' => 'success',
            'sugestao' => $rows[0] ?? null,
            'recentes' => $rows
        ]);
    }

    if ($acao === 'sugestao_por_correspondente') {
        $idCorrespondente = (int)($_GET['id_correspondente'] ?? 0);
        $tipo = cartas_normalize_tipo_movimentacao($_GET['tipo_movimentacao'] ?? 'Entrada');

        if ($idCorrespondente <= 0) {
            cartas_json_response(['status' => 'success', 'sugestao' => null, 'recentes' => []]);
        }

        $orderCampoPreferencial = $tipo === 'Entrada' ? 'v.preferencial_entrada' : 'v.preferencial_saida';
        $orderCampoScore = $tipo === 'Entrada' ? 'v.score_entrada' : 'v.score_saida';
        $orderCampoUltimoUso = $tipo === 'Entrada' ? 'v.ultimo_uso_entrada' : 'v.ultimo_uso_saida';

        $stmt = $pdo->prepare(
            "SELECT
                v.id_interno,
                v.score_entrada, v.score_saida, v.ultimo_uso_entrada, v.ultimo_uso_saida,
                i.ipen, i.nome, i.nome_social, i.galeria, i.bloco, i.res
             FROM censura_cartas_vinculos v
             INNER JOIN internos i ON i.ipen = v.id_interno
             WHERE v.id_correspondente = ? AND i.status = 'A'
             ORDER BY {$orderCampoPreferencial} DESC, {$orderCampoScore} DESC, {$orderCampoUltimoUso} DESC, COALESCE(NULLIF(i.nome_social, ''), i.nome) ASC
             LIMIT 5"
        );
        $stmt->execute([$idCorrespondente]);
        $rows = $stmt->fetchAll();

        cartas_json_response([
            'status' => 'success',
            'sugestao' => $rows[0] ?? null,
            'recentes' => $rows
        ]);
    }

    if ($acao === 'registrar_carta' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $tipo = cartas_normalize_tipo_movimentacao($_POST['tipo_movimentacao'] ?? 'Entrada');
        $ipen = (int)($_POST['ipen'] ?? 0);
        $idCorrespondente = (int)($_POST['id_correspondente'] ?? 0);
        $statusCensura = $_POST['status_censura'] ?? 'Liberada';
        $observacoes = trim((string)($_POST['observacoes_censura'] ?? ''));
        $motivoRetencao = trim((string)($_POST['motivo_retencao'] ?? ''));
        $recebidoEm = trim((string)($_POST['recebido_em'] ?? ''));

        if (!in_array($statusCensura, ['Liberada', 'Retida', 'Devolvida'], true)) {
            cartas_json_response(['status' => 'error', 'msg' => 'Status de censura inválido.'], 422);
        }

        if ($ipen <= 0) {
            cartas_json_response(['status' => 'error', 'msg' => 'Selecione um interno válido.'], 422);
        }

        if ($statusCensura === 'Retida' && $motivoRetencao === '') {
            cartas_json_response(['status' => 'error', 'msg' => 'Informe o motivo da retenção.'], 422);
        }

        if ($recebidoEm === '') {
            $recebidoEm = date('Y-m-d H:i:s');
        }

        try {
            $pdo->beginTransaction();

            $stmtInterno = $pdo->prepare(
                "SELECT ipen, nome, nome_social, galeria, bloco, res
                 FROM internos
                 WHERE ipen = ?
                 LIMIT 1"
            );
            $stmtInterno->execute([$ipen]);
            $interno = $stmtInterno->fetch();
            if (!$interno) {
                throw new RuntimeException('Interno não encontrado.');
            }

            if ($idCorrespondente <= 0) {
                $correspondenteNome = trim((string)($_POST['correspondente_nome'] ?? ''));
                if ($correspondenteNome === '') {
                    throw new RuntimeException('Informe ou selecione um correspondente.');
                }

                $stmtInsCorresp = $pdo->prepare(
                    "INSERT INTO censura_cartas_correspondentes
                    (nome, vinculo, logradouro, numero, bairro, cidade, uf, cep, complemento, ativo, criado_por)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'S', ?)"
                );
                $stmtInsCorresp->execute([
                    $correspondenteNome,
                    trim((string)($_POST['correspondente_vinculo'] ?? '')) ?: null,
                    trim((string)($_POST['correspondente_logradouro'] ?? '')) ?: null,
                    trim((string)($_POST['correspondente_numero'] ?? '')) ?: null,
                    trim((string)($_POST['correspondente_bairro'] ?? '')) ?: null,
                    trim((string)($_POST['correspondente_cidade'] ?? '')) ?: null,
                    trim((string)($_POST['correspondente_uf'] ?? '')) ?: null,
                    trim((string)($_POST['correspondente_cep'] ?? '')) ?: null,
                    trim((string)($_POST['correspondente_complemento'] ?? '')) ?: null,
                    $monitor_id > 0 ? $monitor_id : null
                ]);
                $idCorrespondente = (int)$pdo->lastInsertId();
            }

            $stmtCorresp = $pdo->prepare(
                "SELECT id, nome, vinculo, logradouro, numero, bairro, cidade, uf, cep, complemento
                 FROM censura_cartas_correspondentes
                 WHERE id = ? AND ativo = 'S'
                 LIMIT 1"
            );
            $stmtCorresp->execute([$idCorrespondente]);
            $correspondente = $stmtCorresp->fetch();
            if (!$correspondente) {
                throw new RuntimeException('Correspondente não encontrado ou inativo.');
            }

            $concluidoEm = null;
            if ($statusCensura === 'Liberada' || $statusCensura === 'Devolvida') {
                $concluidoEm = date('Y-m-d H:i:s');
            }

            $stmtInsert = $pdo->prepare(
                "INSERT INTO censura_cartas
                (
                    tipo_movimentacao, id_interno, interno_nome, interno_nome_social, interno_galeria, interno_bloco, interno_res,
                    id_correspondente, correspondente_nome, correspondente_vinculo, correspondente_logradouro, correspondente_numero,
                    correspondente_bairro, correspondente_cidade, correspondente_uf, correspondente_cep, correspondente_complemento,
                    status_censura, observacoes_censura, motivo_retencao, recebido_em, concluido_em, monitor_id, monitor_nome, status_registro
                ) VALUES
                (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                )"
            );

            $stmtInsert->execute([
                $tipo,
                (int)$interno['ipen'],
                $interno['nome'],
                $interno['nome_social'],
                $interno['galeria'],
                $interno['bloco'],
                (string)$interno['res'],
                $idCorrespondente,
                $correspondente['nome'],
                $correspondente['vinculo'],
                $correspondente['logradouro'],
                $correspondente['numero'],
                $correspondente['bairro'],
                $correspondente['cidade'],
                $correspondente['uf'],
                $correspondente['cep'],
                $correspondente['complemento'],
                $statusCensura,
                $observacoes !== '' ? $observacoes : null,
                $motivoRetencao !== '' ? $motivoRetencao : null,
                $recebidoEm,
                $concluidoEm,
                $monitor_id,
                $monitor_nome,
                'Ativo'
            ]);

            $idCarta = (int)$pdo->lastInsertId();
            cartas_atualizar_vinculo($pdo, (int)$interno['ipen'], $idCorrespondente, $tipo);

            cartas_registrar_historico(
                $pdo,
                $idCarta,
                'INSERT',
                null,
                [
                    'tipo_movimentacao' => $tipo,
                    'id_interno' => (int)$interno['ipen'],
                    'id_correspondente' => $idCorrespondente,
                    'status_censura' => $statusCensura,
                    'recebido_em' => $recebidoEm,
                    'monitor_id' => $monitor_id
                ],
                $monitor_id > 0 ? $monitor_id : null,
                $monitor_nome
            );

            $pdo->commit();
            cartas_json_response(['status' => 'success', 'msg' => 'Carta registrada com sucesso.', 'id' => $idCarta]);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            cartas_json_response(['status' => 'error', 'msg' => $e->getMessage()], 400);
        }
    }

    if ($acao === 'atualizar_status_censura' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $idCarta = (int)($_POST['id_carta'] ?? 0);
        $novoStatus = $_POST['status_censura'] ?? '';
        $obs = trim((string)($_POST['observacoes_censura'] ?? ''));
        $motivoRet = trim((string)($_POST['motivo_retencao'] ?? ''));

        if ($idCarta <= 0) {
            cartas_json_response(['status' => 'error', 'msg' => 'Registro inválido.'], 422);
        }
        if (!in_array($novoStatus, ['Liberada', 'Retida', 'Devolvida'], true)) {
            cartas_json_response(['status' => 'error', 'msg' => 'Status inválido.'], 422);
        }
        if ($novoStatus === 'Retida' && $motivoRet === '') {
            cartas_json_response(['status' => 'error', 'msg' => 'Informe o motivo da retenção.'], 422);
        }

        try {
            $pdo->beginTransaction();

            $stmtAtual = $pdo->prepare("SELECT * FROM censura_cartas WHERE id = ? LIMIT 1");
            $stmtAtual->execute([$idCarta]);
            $atual = $stmtAtual->fetch();
            if (!$atual) {
                throw new RuntimeException('Carta não encontrada.');
            }
            if ($atual['status_registro'] === 'Cancelado') {
                throw new RuntimeException('Não é possível alterar status de registro cancelado.');
            }

            $concluidoEm = null;
            if ($novoStatus === 'Liberada' || $novoStatus === 'Devolvida') {
                $concluidoEm = date('Y-m-d H:i:s');
            }

            $stmtUp = $pdo->prepare(
                "UPDATE censura_cartas
                 SET status_censura = ?, observacoes_censura = ?, motivo_retencao = ?, concluido_em = ?
                 WHERE id = ?"
            );
            $stmtUp->execute([
                $novoStatus,
                $obs !== '' ? $obs : null,
                $motivoRet !== '' ? $motivoRet : null,
                $concluidoEm,
                $idCarta
            ]);

            cartas_registrar_historico(
                $pdo,
                $idCarta,
                'STATUS',
                [
                    'status_censura' => $atual['status_censura'],
                    'observacoes_censura' => $atual['observacoes_censura'],
                    'motivo_retencao' => $atual['motivo_retencao']
                ],
                [
                    'status_censura' => $novoStatus,
                    'observacoes_censura' => $obs !== '' ? $obs : null,
                    'motivo_retencao' => $motivoRet !== '' ? $motivoRet : null
                ],
                $monitor_id > 0 ? $monitor_id : null,
                $monitor_nome
            );

            $pdo->commit();
            cartas_json_response(['status' => 'success', 'msg' => 'Status atualizado com sucesso.']);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            cartas_json_response(['status' => 'error', 'msg' => $e->getMessage()], 400);
        }
    }

    if ($acao === 'cancelar_registro' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $idCarta = (int)($_POST['id_carta'] ?? 0);
        $motivo = trim((string)($_POST['motivo_cancelamento'] ?? ''));

        if ($idCarta <= 0) {
            cartas_json_response(['status' => 'error', 'msg' => 'Registro inválido.'], 422);
        }
        if ($motivo === '') {
            cartas_json_response(['status' => 'error', 'msg' => 'Informe o motivo do cancelamento.'], 422);
        }

        try {
            $pdo->beginTransaction();

            $stmtAtual = $pdo->prepare("SELECT * FROM censura_cartas WHERE id = ? LIMIT 1");
            $stmtAtual->execute([$idCarta]);
            $atual = $stmtAtual->fetch();
            if (!$atual) {
                throw new RuntimeException('Carta não encontrada.');
            }
            if ($atual['status_registro'] === 'Cancelado') {
                throw new RuntimeException('Registro já está cancelado.');
            }

            $stmtCancel = $pdo->prepare(
                "UPDATE censura_cartas
                 SET status_registro = 'Cancelado',
                     cancelado_em = NOW(),
                     cancelado_por_id = ?,
                     cancelado_por_nome = ?,
                     motivo_cancelamento = ?
                 WHERE id = ?"
            );
            $stmtCancel->execute([
                $monitor_id > 0 ? $monitor_id : null,
                $monitor_nome,
                $motivo,
                $idCarta
            ]);

            cartas_registrar_historico(
                $pdo,
                $idCarta,
                'CANCELAMENTO',
                ['status_registro' => 'Ativo'],
                [
                    'status_registro' => 'Cancelado',
                    'cancelado_por_id' => $monitor_id > 0 ? $monitor_id : null,
                    'motivo_cancelamento' => $motivo
                ],
                $monitor_id > 0 ? $monitor_id : null,
                $monitor_nome
            );

            $pdo->commit();
            cartas_json_response(['status' => 'success', 'msg' => 'Registro cancelado com sucesso.']);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            cartas_json_response(['status' => 'error', 'msg' => $e->getMessage()], 400);
        }
    }

    if ($acao === 'listar_historico') {
        $idCarta = (int)($_GET['id_carta'] ?? 0);
        $operacao = trim((string)($_GET['operacao'] ?? ''));
        $usuario = trim((string)($_GET['usuario'] ?? ''));
        $dataIni = trim((string)($_GET['data_ini'] ?? ''));
        $dataFim = trim((string)($_GET['data_fim'] ?? ''));

        $whereHist = ['1=1'];
        $paramsHist = [];

        if ($idCarta > 0) {
            $whereHist[] = 'h.id_carta = ?';
            $paramsHist[] = $idCarta;
        }
        if (in_array($operacao, ['INSERT', 'UPDATE', 'STATUS', 'CANCELAMENTO', 'RETIFICACAO'], true)) {
            $whereHist[] = 'h.operacao = ?';
            $paramsHist[] = $operacao;
        }
        if ($usuario !== '') {
            $whereHist[] = 'h.usuario_nome LIKE ?';
            $paramsHist[] = '%' . $usuario . '%';
        }
        if ($dataIni !== '') {
            $whereHist[] = 'DATE(h.data_hora) >= ?';
            $paramsHist[] = $dataIni;
        }
        if ($dataFim !== '') {
            $whereHist[] = 'DATE(h.data_hora) <= ?';
            $paramsHist[] = $dataFim;
        }

        $stmt = $pdo->prepare(
            "SELECT h.id, h.id_carta, h.operacao, h.valor_antigo, h.valor_novo, h.usuario_id, h.usuario_nome, h.data_hora,
                    c.tipo_movimentacao, c.id_interno, c.correspondente_nome
             FROM censura_cartas_historico h
             LEFT JOIN censura_cartas c ON c.id = h.id_carta
             WHERE " . implode(' AND ', $whereHist) . "
             ORDER BY h.data_hora DESC, h.id DESC
             LIMIT 200"
        );
        $stmt->execute($paramsHist);
        cartas_json_response(['status' => 'success', 'dados' => $stmt->fetchAll()]);
    }

    cartas_json_response(['status' => 'error', 'msg' => 'Ação não suportada.'], 400);
}

$filtroBusca = trim((string)($_GET['busca'] ?? ''));
$filtroTipo = trim((string)($_GET['tipo'] ?? ''));
$filtroStatus = trim((string)($_GET['status_censura'] ?? ''));
$filtroStatusRegistro = trim((string)($_GET['status_registro'] ?? ''));
$filtroMonitor = trim((string)($_GET['monitor'] ?? ''));
$filtroGaleria = trim((string)($_GET['galeria'] ?? ''));
$filtroBloco = trim((string)($_GET['bloco'] ?? ''));
$filtroCela = trim((string)($_GET['cela'] ?? ''));
$filtroDataIni = trim((string)($_GET['data_ini'] ?? ''));
$filtroDataFim = trim((string)($_GET['data_fim'] ?? ''));

$where = ['1=1'];
$params = [];

if ($filtroBusca !== '') {
    $where[] = "(CAST(c.id_interno AS CHAR) LIKE ? OR c.interno_nome LIKE ? OR c.interno_nome_social LIKE ? OR c.correspondente_nome LIKE ?)";
    $like = '%' . $filtroBusca . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}
if (in_array($filtroTipo, ['Entrada', 'Saida'], true)) {
    $where[] = "c.tipo_movimentacao = ?";
    $params[] = $filtroTipo;
}
if (in_array($filtroStatus, ['Liberada', 'Retida', 'Devolvida'], true)) {
    $where[] = "c.status_censura = ?";
    $params[] = $filtroStatus;
}
if (in_array($filtroStatusRegistro, ['Ativo', 'Cancelado'], true)) {
    $where[] = "c.status_registro = ?";
    $params[] = $filtroStatusRegistro;
}
if ($filtroMonitor !== '') {
    $where[] = "c.monitor_nome LIKE ?";
    $params[] = '%' . $filtroMonitor . '%';
}
if ($filtroGaleria !== '') {
    $where[] = "c.interno_galeria = ?";
    $params[] = $filtroGaleria;
}
if ($filtroBloco !== '') {
    $where[] = "c.interno_bloco = ?";
    $params[] = $filtroBloco;
}
if ($filtroCela !== '') {
    $where[] = "c.interno_res = ?";
    $params[] = $filtroCela;
}
if ($filtroDataIni !== '') {
    $where[] = "DATE(c.recebido_em) >= ?";
    $params[] = $filtroDataIni;
}
if ($filtroDataFim !== '') {
    $where[] = "DATE(c.recebido_em) <= ?";
    $params[] = $filtroDataFim;
}

$sqlList = "SELECT c.*
            FROM censura_cartas c
            WHERE " . implode(' AND ', $where) . "
            ORDER BY c.recebido_em DESC, c.id DESC
            LIMIT 150";

$registros_cartas = [];
if ($tem_permissao_cartas) {
    $stmtList = $pdo->prepare($sqlList);
    $stmtList->execute($params);
    $registros_cartas = $stmtList->fetchAll();
}

$galerias_cartas = [];
$blocos_cartas = [];
if ($tem_permissao_cartas) {
    $galerias_cartas = $pdo->query("SELECT DISTINCT galeria FROM internos WHERE galeria IS NOT NULL AND galeria <> '' ORDER BY galeria")
        ->fetchAll(PDO::FETCH_COLUMN);
    $blocos_cartas = $pdo->query("SELECT DISTINCT bloco FROM internos WHERE bloco IS NOT NULL AND bloco <> '' ORDER BY bloco")
        ->fetchAll(PDO::FETCH_COLUMN);
}
