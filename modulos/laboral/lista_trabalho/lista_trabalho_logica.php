<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('LISTA_TRABALHO_PAGE_SIZE', 50);

$isAjax = $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']);

// Parse raw input manualmente se $_POST estiver vazio (para application/x-www-form-urlencoded)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST)) {
    $rawInput = file_get_contents('php://input');
    if (!empty($rawInput)) {
        parse_str($rawInput, $_POST);
    }
}

if (!isset($_SESSION['user_id'])) {
    if ($isAjax) {
        jsonResponse(['success' => false, 'message' => 'Sessao nao iniciada.'], 403);
    }

    http_response_code(403);
    exit('Sessao nao iniciada.');
}

$hasLaboralPermission = (isset($_SESSION['perm_laboral']) && (int) $_SESSION['perm_laboral'] > 0)
    || (!empty($_SESSION['user_admin']));

if (! $hasLaboralPermission) {
    if ($isAjax) {
        jsonResponse(['success' => false, 'message' => 'Acesso negado. Permissao insuficiente.'], 403);
    }

    http_response_code(403);
    exit('Acesso negado.');
}

$config = require __DIR__ . '/../../../conf/db.php';
$dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
$pdo = new PDO($dsn, $config['user'], $config['pass'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

if ($isAjax) {
    handleAjaxRequest($pdo);
}

$filters = normalizeFilters($_GET);
$page = max(1, (int) ($_GET['pagina'] ?? 1));
$data = buildListaTrabalhoData($pdo, $filters, $page, LISTA_TRABALHO_PAGE_SIZE);

$internos = $data['items'];
$total = $data['total'];
$pagina = $data['page'];
$total_paginas = $data['total_pages'];
$limite = $data['per_page'];
$estatisticas = buildEstatisticas($pdo);

function handleAjaxRequest(PDO $pdo): void
{
    try {
        $action = $_POST['action'] ?? '';

        switch ($action) {
            case 'listar':
                $filters = normalizeFilters($_POST);
                $page = max(1, (int) ($_POST['pagina'] ?? 1));
                $data = buildListaTrabalhoData($pdo, $filters, $page, LISTA_TRABALHO_PAGE_SIZE);

                jsonResponse([
                    'success' => true,
                    'internos' => $data['items'],
                    'total' => $data['total'],
                    'pagina' => $data['page'],
                    'total_paginas' => $data['total_pages'],
                    'estatisticas' => buildEstatisticas($pdo),
                ]);
                break;

            case 'stat_detalhes':
                $stat = trim((string) ($_POST['stat'] ?? ''));
                if ($stat === '') {
                    throw new InvalidArgumentException('Parametro stat nao informado.');
                }

                $mapping = [
                    'total_internos' => ['titulo' => 'Total de Internos', 'filtros' => []],
                    'ctc_favoraveis' => ['titulo' => 'CTC Favoraveis', 'filtros' => ['resultado' => 'Favorável']],
                    'ctc_desfavoraveis' => ['titulo' => 'CTC Desfavoraveis', 'filtros' => ['resultado' => 'Desfavorável']],
                    'ctc_vencidos' => ['titulo' => 'CTC Vencidos', 'filtros' => ['situacao_ctc' => 'Vencido']],
                    'trabalhando' => ['titulo' => 'Internos Trabalhando', 'filtros' => ['mostrar_trabalhando' => 'true', 'somente_trabalhando' => true]],
                    'nao_trabalhando' => ['titulo' => 'Internos que Nao Trabalham', 'filtros' => ['mostrar_trabalhando' => 'false']],
                    'aguardando_ctc' => ['titulo' => 'Aguardando CTC', 'filtros' => ['somente_aguardando_ctc' => true]],
                ];

                if (!isset($mapping[$stat])) {
                    throw new InvalidArgumentException('Stat invalido.');
                }

                $filters = normalizeFilters($mapping[$stat]['filtros']);
                $items = buildListaTrabalhoData($pdo, $filters, 1, 5000)['items'];

                jsonResponse([
                    'success' => true,
                    'titulo' => $mapping[$stat]['titulo'],
                    'stat' => $stat,
                    'total' => count($items),
                    'internos' => $items,
                ]);
                break;

            case 'detalhes':
                $ipen = (int) ($_POST['ipen'] ?? 0);
                if ($ipen <= 0) {
                    throw new InvalidArgumentException('IPEN nao informado.');
                }

                $interno = getInternoByIpen($pdo, $ipen);
                if ($interno === null) {
                    throw new RuntimeException('Interno nao encontrado.');
                }

                jsonResponse([
                    'success' => true,
                    'interno' => $interno,
                    'ctcs' => getCTCsInterno($pdo, $ipen),
                    'condicoes' => getCondicoesEspeciais($pdo, $ipen),
                    'elegibilidade' => evaluateElegibilidade($interno),
                    'exclusoes' => getExclusoesInterno($pdo, $ipen),
                ]);
                break;

            case 'buscar_interno':
                $ipen = (int) ($_POST['ipen'] ?? 0);
                if ($ipen <= 0) {
                    throw new InvalidArgumentException('IPEN nao informado.');
                }

                $interno = getInternoByIpen($pdo, $ipen);
                if ($interno === null) {
                    throw new RuntimeException('Interno nao encontrado.');
                }

                jsonResponse([
                    'success' => true,
                    'interno' => $interno,
                    'condicoes' => getCondicoesEspeciais($pdo, $ipen),
                ]);
                break;

            case 'buscar_interno_por_termo':
                $termo = trim((string) ($_POST['termo'] ?? ''));
                if ($termo === '') {
                    throw new InvalidArgumentException('Termo de busca nao informado.');
                }

                jsonResponse([
                    'success' => true,
                    'internos' => searchInternos($pdo, $termo),
                ]);
                break;

            case 'salvar_ctc':
                saveCtc($pdo, $_POST);
                break;

            case 'excluir_ctc':
                deleteCtc($pdo, (int) ($_POST['ipen'] ?? 0));
                break;

            case 'cadastrar_condicao':
                saveCondicaoEspecial($pdo, $_POST);
                break;

            case 'lista_exclusao':
                handleListaExclusao($pdo, $_POST);
                break;

            case 'remover_exclusao':
                removeExclusao($pdo, (int) ($_POST['id'] ?? 0));
                break;

            default:
                throw new InvalidArgumentException('Acao nao reconhecida.');
        }
    } catch (Throwable $e) {
        error_log('lista_trabalho_logica.php - Erro: ' . $e->getMessage());
        jsonResponse([
            'success' => false,
            'message' => $e->getMessage(),
        ], 400);
    }
}

function buildListaTrabalhoData(PDO $pdo, array $filters, int $page, int $perPage): array
{
    $allItems = fetchInternosBase($pdo, $filters);
    $total = count($allItems);
    $totalPages = max(1, (int) ceil($total / $perPage));
    $page = min($page, $totalPages);
    $offset = ($page - 1) * $perPage;

    return [
        'items' => array_slice($allItems, $offset, $perPage),
        'total' => $total,
        'page' => $page,
        'total_pages' => $totalPages,
        'per_page' => $perPage,
    ];
}

function fetchInternosBase(PDO $pdo, array $filters = []): array
{
    $sql = "
        SELECT
            i.ipen,
            i.nome,
            i.nome_social,
            i.apelido,
            i.status,
            i.galeria,
            i.bloco,
            i.res,
            i.data_ativo,
            i.data_ativo AS ultima_entrada_cadeia,
            COALESCE(lab.estabelecimento_ativo, '') AS empresa,
            lab.remicao_inicio_ativo AS data_inicio_trabalho,
            lab.ultima_data_trabalho AS data_ultimo_trabalho,
            CASE WHEN COALESCE(lab.trabalha_ativo, 0) = 1 THEN 'S' ELSE 'N' END AS trabalha,
            CASE WHEN COALESCE(lab.total_registros, 0) > 0 THEN 'S' ELSE 'N' END AS ja_trabalhou,
            ctc.id AS ctc_id,
            ctc.data_ctc,
            ctc.resultado AS resultado_ctc,
            ctc.decisao_juiz,
            ctc.motivo_desfavoravel,
            ctc.observacoes AS observacoes_ctc,
            ctc.data_proxima_ctc,
            CASE WHEN md.ipen IS NOT NULL THEN 1 ELSE 0 END AS tem_md,
            md.data_inicio AS data_medida,
            md.motivo AS tipo_medida,
            CASE WHEN ce.ipen IS NOT NULL THEN 1 ELSE 0 END AS tem_condicao_especial,
            ce.tipo AS tipo_condicao_especial,
            CASE WHEN exc.ipen IS NOT NULL THEN 1 ELSE 0 END AS esta_excluido,
            exc.motivo AS motivo_exclusao,
            exc.data_fim AS data_fim_exclusao,
            CASE
                WHEN i.galeria IN ('A','B','C','D','E','F','G','H','T') THEN 'Fechado'
                WHEN i.galeria IN ('SA','SB','SC','SD','SE','ST') THEN 'Semiaberto'
                ELSE 'Outro'
            END AS regime_interno
        FROM internos i
        LEFT JOIN (
            SELECT
                ipen,
                MAX(CASE WHEN status = 'A' AND estabelecimento IS NOT NULL AND estabelecimento <> '' THEN 1 ELSE 0 END) AS trabalha_ativo,
                MAX(CASE WHEN status = 'A' THEN estabelecimento END) AS estabelecimento_ativo,
                MAX(CASE WHEN status = 'A' THEN remicao_inicio END) AS remicao_inicio_ativo,
                MAX(COALESCE(remicao_fim, remicao_inicio, liberacao_fim, liberacao_inicio)) AS ultima_data_trabalho,
                COUNT(*) AS total_registros
            FROM internos_laboral
            GROUP BY ipen
        ) lab ON lab.ipen = i.ipen
        LEFT JOIN internos_ctc ctc
            ON ctc.ipen = i.ipen
           AND ctc.status = 'Ativo'
        LEFT JOIN (
            SELECT
                id_interno AS ipen,
                MAX(data_inicio) AS data_inicio,
                MAX(motivo) AS motivo
            FROM internos_md_medidas
            WHERE status = 'Ativa'
              AND (data_fim IS NULL OR data_fim >= CURDATE())
            GROUP BY id_interno
        ) md ON md.ipen = i.ipen
        LEFT JOIN (
            SELECT
                ipen,
                MAX(tipo) AS tipo
            FROM internos_condicoes_especiais
            WHERE status = 'Ativa'
              AND (data_fim IS NULL OR data_fim >= CURDATE())
            GROUP BY ipen
        ) ce ON ce.ipen = i.ipen
        LEFT JOIN (
            SELECT
                ipen,
                MAX(motivo) AS motivo,
                MAX(data_fim) AS data_fim
            FROM internos_ctc_exclusao
            WHERE status = 'Ativa'
              AND (data_fim IS NULL OR data_fim >= CURDATE())
            GROUP BY ipen
        ) exc ON exc.ipen = i.ipen
        WHERE i.status = 'A'
        ORDER BY i.nome ASC
    ";

    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll();
    $items = [];

    foreach ($rows as $row) {
        $interno = enrichInterno($row);
        if (matchInternoFilters($interno, $filters)) {
            $items[] = $interno;
        }
    }

    usort($items, static function (array $a, array $b): int {
        if ($a['ordem_grupo'] !== $b['ordem_grupo']) {
            return $a['ordem_grupo'] <=> $b['ordem_grupo'];
        }

        if ($a['prioridade'] !== $b['prioridade']) {
            return $b['prioridade'] <=> $a['prioridade'];
        }

        return strcmp($a['nome_completo'], $b['nome_completo']);
    });

    return $items;
}

function enrichInterno(array $row): array
{
    $row['dias_preso'] = $row['data_ativo']
        ? max(0, (new DateTime($row['data_ativo']))->diff(new DateTime())->days)
        : 0;

    $row['local_formatado'] = trim(sprintf(
        '%s%s-%s',
        (string) ($row['galeria'] ?? ''),
        (string) ($row['bloco'] ?? ''),
        (string) ($row['res'] ?? '')
    ), '-');

    $row['nome_completo'] = $row['nome_social']
        ? $row['nome'] . ' (' . $row['nome_social'] . ')'
        : $row['nome'];

    $validade = verificarValidadeCTC($row);
    $row['ctc_valido'] = $validade['valido'];
    $row['ctc_validade_motivo'] = $validade['motivo'];
    $row['ctc_validade_data'] = $validade['validade'] ?? null;

    $row['quando_refazer'] = ($row['data_ctc'] && $row['resultado_ctc'])
        ? calcularQuandoRefazerCTC($row['data_ctc'], $row['resultado_ctc'], $row['regime_interno'])
        : null;

    $row['situacao_ctc'] = inferSituacaoCtc($row);
    $row['elegibilidade'] = evaluateElegibilidade($row);
    $row['prioridade'] = calcularPrioridadeTrabalho($row);
    $row['situacao'] = inferSituacaoTrabalho($row);
    $row['onde_trabalha'] = $row['trabalha'] === 'S' && $row['empresa']
        ? $row['empresa']
        : 'Nao Trabalha';
    $row['ordem_grupo'] = inferOrdemGrupo($row);

    return $row;
}

function inferSituacaoCtc(array $interno): string
{
    if (empty($interno['data_ctc'])) {
        return 'Sem CTC';
    }

    if (($interno['resultado_ctc'] ?? '') === 'Desfavorável') {
        if (!empty($interno['data_proxima_ctc']) && $interno['data_proxima_ctc'] <= date('Y-m-d')) {
            return 'Vencido';
        }

        return 'Desfavorável Vigente';
    }

    return ($interno['ctc_valido'] ?? false) ? 'Regular' : 'Vencido';
}

function inferSituacaoTrabalho(array $interno): string
{
    if (($interno['trabalha'] ?? 'N') === 'S') {
        return 'Trabalhando';
    }

    if (!empty($interno['esta_excluido'])) {
        return 'Impedido - Lista de exclusao';
    }

    if (!empty($interno['tem_md'])) {
        return 'Impedido - Medida disciplinar';
    }

    if (!empty($interno['tem_condicao_especial'])) {
        return 'Impedido - Condicao especial';
    }

    if (!empty($interno['ctc_valido'])) {
        return 'Apto - CTC valido';
    }

    if (!empty($interno['data_ctc']) && ($interno['resultado_ctc'] ?? '') === 'Desfavorável') {
        return 'Pendente - Aguardar nova CTC';
    }

    return 'Apto - Priorizar avaliacao';
}

function inferOrdemGrupo(array $interno): int
{
    if (($interno['trabalha'] ?? 'N') === 'S') {
        return 3;
    }

    if (!($interno['elegibilidade']['pode'] ?? false)) {
        return 2;
    }

    return 1;
}

function evaluateElegibilidade(array $interno): array
{
    $motivos = [];

    if (!empty($interno['esta_excluido'])) {
        $motivos[] = 'Lista de exclusao ativa';
    }

    if (!empty($interno['tem_md'])) {
        $motivos[] = 'Medida disciplinar ativa';
    }

    if (!empty($interno['tem_condicao_especial'])) {
        $motivos[] = 'Condicao especial ativa';
    }

    return [
        'pode' => empty($motivos),
        'motivos' => $motivos,
        'motivo' => empty($motivos) ? null : implode('; ', $motivos),
    ];
}

function calcularPrioridadeTrabalho(array $interno): int
{
    if (($interno['trabalha'] ?? 'N') === 'S') {
        return 0;
    }

    if (!($interno['elegibilidade']['pode'] ?? false)) {
        return 0;
    }

    $prioridade = (int) ($interno['dias_preso'] ?? 0);

    if (($interno['ja_trabalhou'] ?? 'N') === 'N') {
        $prioridade += 1000;
    }

    if (!empty($interno['data_ultimo_trabalho'])) {
        $diasSemTrabalhar = max(0, (new DateTime($interno['data_ultimo_trabalho']))->diff(new DateTime())->days);
        $prioridade += min(500, $diasSemTrabalhar);
    }

    if (empty($interno['ctc_valido'])) {
        $prioridade += 250;
    }

    return $prioridade;
}

function verificarValidadeCTC(array $interno): array
{
    if (empty($interno['data_ctc'])) {
        return ['valido' => false, 'motivo' => 'Sem CTC cadastrado'];
    }

    if (($interno['trabalha'] ?? 'N') === 'S') {
        return ['valido' => true, 'motivo' => 'Interno esta trabalhando'];
    }

    $dataCtc = new DateTime($interno['data_ctc']);
    $dataValidade = clone $dataCtc;
    $resultado = $interno['resultado_ctc'] ?? '';

    if ($resultado === 'Favorável') {
        $meses = ($interno['regime_interno'] ?? '') === 'Fechado' ? 12 : 6;
    } else {
        $meses = 6;
    }

    $dataValidade->add(new DateInterval("P{$meses}M"));
    $hoje = new DateTime('today');

    if ($hoje <= $dataValidade) {
        return [
            'valido' => true,
            'motivo' => 'Dentro da validade',
            'validade' => $dataValidade->format('d/m/Y'),
        ];
    }

    return [
        'valido' => false,
        'motivo' => 'CTC vencido em ' . $dataValidade->format('d/m/Y'),
        'validade' => $dataValidade->format('d/m/Y'),
    ];
}

function calcularQuandoRefazerCTC(string $dataCtc, string $resultado, string $regime): ?array
{
    if (!$dataCtc) {
        return null;
    }

    $meses = 6;
    if ($resultado === 'Favorável') {
        $meses = $regime === 'Fechado' ? 12 : 6;
    }

    $data = new DateTime($dataCtc);
    $data->add(new DateInterval("P{$meses}M"));
    $hoje = new DateTime('today');
    $intervalo = $hoje->diff($data);
    $diasRestantes = (int) $intervalo->days;

    if ($data < $hoje) {
        $diasRestantes *= -1;
    }

    return [
        'data' => $data->format('Y-m-d'),
        'formatada' => $data->format('d/m/Y'),
        'dias_restantes' => $diasRestantes,
    ];
}

function normalizeFilters(array $source): array
{
    return [
        'busca' => trim((string) ($source['busca'] ?? '')),
        'resultado' => trim((string) ($source['resultado'] ?? '')),
        'galeria' => trim((string) ($source['galeria'] ?? '')),
        'estabelecimento' => trim((string) ($source['estabelecimento'] ?? '')),
        'situacao_ctc' => trim((string) ($source['situacao_ctc'] ?? '')),
        'mostrar_trabalhando' => isset($source['mostrar_trabalhando']) ? (string) $source['mostrar_trabalhando'] : 'true',
        'somente_trabalhando' => !empty($source['somente_trabalhando']),
        'somente_aguardando_ctc' => !empty($source['somente_aguardando_ctc']),
    ];
}

function matchInternoFilters(array $interno, array $filters): bool
{
    $filters = normalizeFilters($filters);

    if ($filters['busca'] !== '') {
        $needle = mb_strtolower((string) $filters['busca']);
        $haystack = mb_strtolower(implode(' ', [
            $interno['ipen'],
            $interno['nome'] ?? '',
            $interno['nome_social'] ?? '',
            $interno['apelido'] ?? '',
        ]));

        if (mb_strpos($haystack, $needle) === false) {
            return false;
        }
    }

    if ($filters['galeria'] !== '' && mb_strtolower((string) ($interno['galeria'] ?? '')) !== mb_strtolower((string) $filters['galeria'])) {
        return false;
    }

    if ($filters['resultado'] !== '' && ($interno['resultado_ctc'] ?? '') !== $filters['resultado']) {
        return false;
    }

    if ($filters['estabelecimento'] !== '') {
        $empresa = mb_strtolower((string) ($interno['empresa'] ?? ''));
        if (mb_strpos($empresa, mb_strtolower((string) $filters['estabelecimento'])) === false) {
            return false;
        }
    }

    if ($filters['situacao_ctc'] !== '') {
        if (($interno['situacao_ctc'] ?? '') !== $filters['situacao_ctc']) {
            return false;
        }
    }

    if ($filters['mostrar_trabalhando'] === 'false' && ($interno['trabalha'] ?? 'N') === 'S') {
        return false;
    }

    if ($filters['somente_trabalhando'] && ($interno['trabalha'] ?? 'N') !== 'S') {
        return false;
    }

    if ($filters['somente_aguardando_ctc'] && ($interno['situacao'] ?? '') !== 'Pendente - Aguardar nova CTC') {
        return false;
    }

    return true;
}

function buildEstatisticas(PDO $pdo): array
{
    $items = fetchInternosBase($pdo);

    $stats = [
        'total_internos' => count($items),
        'ctc_favoraveis' => 0,
        'ctc_desfavoraveis' => 0,
        'ctc_vencidos' => 0,
        'aguardando_ctc' => 0,
        'trabalhando' => 0,
        'nao_trabalhando' => 0,
    ];

    foreach ($items as $interno) {
        if (($interno['resultado_ctc'] ?? '') === 'Favorável') {
            $stats['ctc_favoraveis']++;
        }

        if (($interno['resultado_ctc'] ?? '') === 'Desfavorável') {
            $stats['ctc_desfavoraveis']++;
        }

        if (($interno['situacao_ctc'] ?? '') === 'Vencido') {
            $stats['ctc_vencidos']++;
        }

        if (($interno['situacao'] ?? '') === 'Pendente - Aguardar nova CTC') {
            $stats['aguardando_ctc']++;
        }

        if (($interno['trabalha'] ?? 'N') === 'S') {
            $stats['trabalhando']++;
        } else {
            $stats['nao_trabalhando']++;
        }
    }

    return $stats;
}

function getInternoByIpen(PDO $pdo, int $ipen): ?array
{
    foreach (fetchInternosBase($pdo) as $interno) {
        if ((int) $interno['ipen'] === $ipen) {
            return $interno;
        }
    }

    return null;
}

function getCTCsInterno(PDO $pdo, int $ipen): array
{
    $stmt = $pdo->prepare("
        SELECT
            ctc.*,
            DATE_FORMAT(ctc.data_ctc, '%d/%m/%Y') AS data_ctc_formatada,
            DATE_FORMAT(ctc.data_proxima_ctc, '%d/%m/%Y') AS data_proxima_ctc_formatada,
            criado.nome AS usuario_cadastro,
            atualizado.nome AS usuario_atualizacao
        FROM internos_ctc ctc
        LEFT JOIN users criado ON criado.id = ctc.criado_por
        LEFT JOIN users atualizado ON atualizado.id = ctc.atualizado_por
        WHERE ctc.ipen = ?
        ORDER BY ctc.data_ctc DESC, ctc.id DESC
    ");
    $stmt->execute([$ipen]);

    return $stmt->fetchAll();
}

function getCondicoesEspeciais(PDO $pdo, int $ipen): array
{
    $stmt = $pdo->prepare("
        SELECT
            ce.*,
            DATE_FORMAT(ce.data_inicio, '%d/%m/%Y') AS data_inicio_formatada,
            DATE_FORMAT(ce.data_fim, '%d/%m/%Y') AS data_fim_formatada,
            criado.nome AS usuario_cadastro,
            concluido.nome AS usuario_conclusao
        FROM internos_condicoes_especiais ce
        LEFT JOIN users criado ON criado.id = ce.criado_por
        LEFT JOIN users concluido ON concluido.id = ce.id_usuario_conclusao
        WHERE ce.ipen = ?
        ORDER BY ce.data_inicio DESC, ce.id DESC
    ");
    $stmt->execute([$ipen]);

    return $stmt->fetchAll();
}

function getExclusoesInterno(PDO $pdo, int $ipen): array
{
    $stmt = $pdo->prepare("
        SELECT
            exc.*,
            DATE_FORMAT(exc.data_inicio, '%d/%m/%Y') AS data_inicio_formatada,
            DATE_FORMAT(exc.data_fim, '%d/%m/%Y') AS data_fim_formatada,
            criado.nome AS usuario_cadastro
        FROM internos_ctc_exclusao exc
        LEFT JOIN users criado ON criado.id = exc.criado_por
        WHERE exc.ipen = ?
        ORDER BY exc.data_inicio DESC, exc.id DESC
    ");
    $stmt->execute([$ipen]);

    return $stmt->fetchAll();
}

function searchInternos(PDO $pdo, string $termo): array
{
    if (ctype_digit($termo)) {
        $stmt = $pdo->prepare("
            SELECT ipen, nome, nome_social, apelido, galeria, bloco, res
            FROM internos
            WHERE status = 'A'
              AND ipen = ?
            LIMIT 10
        ");
        $stmt->execute([(int) $termo]);
    } else {
        $like = '%' . $termo . '%';
        $stmt = $pdo->prepare("
            SELECT ipen, nome, nome_social, apelido, galeria, bloco, res
            FROM internos
            WHERE status = 'A'
              AND (
                nome LIKE ?
                OR nome_social LIKE ?
                OR apelido LIKE ?
              )
            ORDER BY nome ASC
            LIMIT 10
        ");
        $stmt->execute([$like, $like, $like]);
    }

    return $stmt->fetchAll();
}

function saveCtc(PDO $pdo, array $data): void
{
    $ipen = (int) ($data['ipen'] ?? 0);
    $dataCtc = trim((string) ($data['data_ctc'] ?? ''));
    $resultado = trim((string) ($data['resultado'] ?? ''));
    $decisaoJuiz = trim((string) ($data['decisao_juiz'] ?? ''));
    $motivoDesfavoravel = trim((string) ($data['motivo_desfavoravel'] ?? ''));
    $observacoes = trim((string) ($data['observacoes'] ?? ''));

    if ($ipen <= 0 || $dataCtc === '' || $resultado === '') {
        throw new InvalidArgumentException('Campos obrigatorios nao informados.');
    }

    $interno = getInternoByIpen($pdo, $ipen);
    if ($interno === null) {
        throw new RuntimeException('Interno nao encontrado.');
    }

    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare("
            UPDATE internos_ctc
            SET status = 'Inativo', atualizado_em = NOW(), atualizado_por = ?
            WHERE ipen = ? AND status = 'Ativo'
        ");
        $stmt->execute([$_SESSION['user_id'], $ipen]);

        $dataProxima = null;
        if ($resultado === 'Desfavorável') {
            $refazer = calcularQuandoRefazerCTC($dataCtc, $resultado, $interno['regime_interno']);
            $dataProxima = $refazer['data'];
        }

        $stmt = $pdo->prepare("
            INSERT INTO internos_ctc
                (ipen, data_ctc, resultado, decisao_juiz, motivo_desfavoravel, data_proxima_ctc, observacoes, status, criado_por)
            VALUES
                (?, ?, ?, ?, ?, ?, ?, 'Ativo', ?)
        ");
        $stmt->execute([
            $ipen,
            $dataCtc,
            $resultado,
            $decisaoJuiz !== '' ? $decisaoJuiz : null,
            $motivoDesfavoravel !== '' ? $motivoDesfavoravel : null,
            $dataProxima,
            $observacoes !== '' ? $observacoes : null,
            $_SESSION['user_id'],
        ]);

        $pdo->commit();
        jsonResponse(['success' => true, 'message' => 'CTC salvo com sucesso.']);
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function deleteCtc(PDO $pdo, int $ipen): void
{
    if ($ipen <= 0) {
        throw new InvalidArgumentException('IPEN nao informado.');
    }

    $stmt = $pdo->prepare("
        UPDATE internos_ctc
        SET status = 'Inativo', atualizado_em = NOW(), atualizado_por = ?
        WHERE ipen = ? AND status = 'Ativo'
    ");
    $stmt->execute([$_SESSION['user_id'], $ipen]);

    jsonResponse(['success' => true, 'message' => 'CTC excluido com sucesso.']);
}

function saveCondicaoEspecial(PDO $pdo, array $data): void
{
    $ipen = (int) ($data['ipen'] ?? 0);
    $tipo = trim((string) ($data['tipo'] ?? ''));
    $dataInicio = trim((string) ($data['data_inicio'] ?? ''));
    $dataFim = trim((string) ($data['data_fim'] ?? ''));
    $observacoes = trim((string) ($data['observacoes'] ?? ''));

    if ($ipen <= 0 || $tipo === '' || $dataInicio === '' || $dataFim === '') {
        throw new InvalidArgumentException('Campos obrigatorios nao informados.');
    }

    $stmt = $pdo->prepare("
        INSERT INTO internos_condicoes_especiais
            (ipen, tipo, data_inicio, data_fim, observacoes, status, criado_por)
        VALUES
            (?, ?, ?, ?, ?, 'Ativa', ?)
    ");
    $stmt->execute([
        $ipen,
        $tipo,
        $dataInicio,
        $dataFim,
        $observacoes !== '' ? $observacoes : null,
        $_SESSION['user_id'],
    ]);

    jsonResponse(['success' => true, 'message' => 'Condicao especial cadastrada com sucesso.']);
}

function handleListaExclusao(PDO $pdo, array $data): void
{
    $subAction = trim((string) ($data['sub_action'] ?? 'listar'));

    if ($subAction === 'adicionar') {
        $ipen = (int) ($data['ipen'] ?? 0);
        $motivo = trim((string) ($data['motivo'] ?? ''));
        $dataInicio = trim((string) ($data['data_inicio'] ?? ''));
        $dataFim = trim((string) ($data['data_fim'] ?? ''));
        $observacoes = trim((string) ($data['observacoes'] ?? ''));

        if ($ipen <= 0 || $motivo === '' || $dataInicio === '') {
            throw new InvalidArgumentException('Campos obrigatorios nao informados.');
        }

        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare("
                UPDATE internos_ctc_exclusao
                SET status = 'Concluida', data_conclusao = NOW(), id_usuario_conclusao = ?
                WHERE ipen = ? AND status = 'Ativa'
            ");
            $stmt->execute([$_SESSION['user_id'], $ipen]);

            $stmt = $pdo->prepare("
                INSERT INTO internos_ctc_exclusao
                    (ipen, motivo, data_inicio, data_fim, observacoes, status, criado_por)
                VALUES
                    (?, ?, ?, ?, ?, 'Ativa', ?)
            ");
            $stmt->execute([
                $ipen,
                $motivo,
                $dataInicio,
                $dataFim !== '' ? $dataFim : null,
                $observacoes !== '' ? $observacoes : null,
                $_SESSION['user_id'],
            ]);

            $pdo->commit();
            jsonResponse(['success' => true, 'message' => 'Interno adicionado a lista de exclusao.']);
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    $stmt = $pdo->query("
        SELECT
            exc.*,
            i.nome AS nome_interno,
            i.nome_social,
            DATE_FORMAT(exc.data_inicio, '%d/%m/%Y') AS data_inicio_formatada,
            DATE_FORMAT(exc.data_fim, '%d/%m/%Y') AS data_fim_formatada,
            criado.nome AS usuario_cadastro
        FROM internos_ctc_exclusao exc
        INNER JOIN internos i ON i.ipen = exc.ipen
        LEFT JOIN users criado ON criado.id = exc.criado_por
        WHERE exc.status = 'Ativa'
        ORDER BY exc.data_inicio DESC, exc.id DESC
    ");

    jsonResponse([
        'success' => true,
        'itens' => $stmt->fetchAll(),
    ]);
}

function removeExclusao(PDO $pdo, int $id): void
{
    if ($id <= 0) {
        throw new InvalidArgumentException('ID nao informado.');
    }

    $stmt = $pdo->prepare("
        UPDATE internos_ctc_exclusao
        SET status = 'Concluida', data_conclusao = NOW(), id_usuario_conclusao = ?
        WHERE id = ?
    ");
    $stmt->execute([$_SESSION['user_id'], $id]);

    jsonResponse(['success' => true, 'message' => 'Item removido da lista de exclusao.']);
}

function jsonResponse(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
