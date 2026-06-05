<?php
// inicio/inicio_logica.php
// Lógica PHP para o módulo início
// session_start() já foi chamado em index_logica.php

// Verificar autenticação
if (!isset($_SESSION['user_id'])) {
    header("Location: /autenticacao");
    exit;
}

// Verificar inatividade (apenas se não for modo quiosque)
$isKioskMode = isset($_SESSION['kiosk_mode']) && (int)$_SESSION['kiosk_mode'] === 1;
if (!$isKioskMode && isset($_SESSION['ultimo_clique'])) {
    $inatividade = time() - $_SESSION['ultimo_clique'];
    if ($inatividade > 600) {
        session_unset();
        session_destroy();
        header("Location: /autenticacao?expirou");
        exit;
    }
}

$_SESSION['ultimo_clique'] = time();

// Conexão com banco de dados
$pdo = null;
try {
    $config = require __DIR__ . '/../../conf/db.php';
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['user'], $config['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Erro 500: Erro Crítico no Banco.");
}

// Carregar configurações do menu
require_once __DIR__ . '/../../config/sidebar.php';
$menuConfig = getMenuConfig();
$userInfo = getUserInfo();

// Preparar dados para a view
$dashboardData = [
    'userName' => $_SESSION['user_nome'],
    'userSector' => $_SESSION['user_setor'],
    'showCensura' => isset($menuConfig['censura']),
    'showEclusa' => isset($menuConfig['eclusa']),
    'showPortaria' => isset($menuConfig['portaria']),
    'menuConfig' => $menuConfig,
    'userInfo' => $userInfo
];

// Contadores principais (Internos)
$internosCounters = [
    'total_ativos' => 0,
    'saida_temporaria' => 0,
    'trabalho_interno' => 0,
    'remicao_ativa' => 0,
    'regalias' => 0,
    'ctc_ativo' => 0,
    'eletronicos_na_cela' => 0,
];

try {
    $stmt = $pdo->query("
        SELECT
            COUNT(*) AS total_ativos,
            SUM(CASE WHEN UPPER(COALESCE(situacao, '')) LIKE '%TEMPOR%' THEN 1 ELSE 0 END) AS saida_temporaria,
            SUM(CASE WHEN UPPER(COALESCE(situacao, '')) LIKE '%TRABALHO INTERNO%' THEN 1 ELSE 0 END) AS trabalho_interno,
            SUM(CASE WHEN regalia = 'S' OR regalia_galeria = 'S' THEN 1 ELSE 0 END) AS regalias
        FROM internos
        WHERE status = 'A'
    ");
    $row = $stmt->fetch();
    if ($row) {
        $internosCounters['total_ativos'] = (int)$row['total_ativos'];
        $internosCounters['saida_temporaria'] = (int)$row['saida_temporaria'];
        $internosCounters['trabalho_interno'] = (int)$row['trabalho_interno'];
        $internosCounters['regalias'] = (int)$row['regalias'];
    }

    // Contagem de internos com trabalho cadastrado com remicao (internos_laboral)
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT l.ipen) AS total
        FROM internos_laboral l
        JOIN internos i ON i.ipen = l.ipen AND i.status = 'A'
        WHERE l.status = 'A'
          AND UPPER(l.estabelecimento) LIKE :rem
    ");
    $stmt->execute([':rem' => '%REMI%']);
    $internosCounters['remicao_ativa'] = (int)($stmt->fetchColumn() ?: 0);

    // CTC ativo
    $stmt = $pdo->query("
        SELECT COUNT(DISTINCT c.ipen) AS total
        FROM internos_ctc c
        JOIN internos i ON i.ipen = c.ipen AND i.status = 'A'
        WHERE c.status = 'Ativo'
    ");
    $internosCounters['ctc_ativo'] = (int)($stmt->fetchColumn() ?: 0);

    // Internos com eletrônicos na cela
    $stmt = $pdo->query("
        SELECT COUNT(DISTINCT e.id_interno) AS total
        FROM internos_eletronicos e
        JOIN internos i ON i.ipen = e.id_interno AND i.status = 'A'
        WHERE e.situacao = 'Na Cela'
    ");
    $internosCounters['eletronicos_na_cela'] = (int)($stmt->fetchColumn() ?: 0);
} catch (Exception $e) {
    // Silenciar para não quebrar o início
}

// Dados para gráficos (dashboard do início)
$inicioChartsData = [
    'situacao' => ['labels' => [], 'values' => []],
    'eletronicos_na_cela' => ['labels' => [], 'values' => []],
    'escoltas_14d' => ['labels' => [], 'total' => [], 'finalizadas' => [], 'pendentes' => []],
    'eclusa_14d' => ['labels' => [], 'entradas' => [], 'saidas' => []],
];
$inicioHoje = [
    'escoltas_total' => 0,
    'eclusa_total' => 0,
    'eclusa_entradas' => 0,
    'eclusa_saidas' => 0,
    'cartas_total' => 0,
];

try {
    // Pie: Internos por situação (categorias normalizadas)
    $stmt = $pdo->query("
        SELECT
            CASE
                WHEN TRIM(COALESCE(situacao, '')) = '' THEN 'Não informado'
                WHEN UPPER(COALESCE(situacao, '')) LIKE '%TRABALHO INTERNO%' THEN 'Trabalho interno'
                WHEN UPPER(COALESCE(situacao, '')) LIKE '%TRABALHO EXTERNO%' THEN 'Trabalho externo'
                WHEN UPPER(COALESCE(situacao, '')) LIKE '%TEMPOR%' THEN 'Saída temporária'
                WHEN UPPER(COALESCE(situacao, '')) LIKE '%ESTUDO%' THEN 'Estudo'
                WHEN UPPER(COALESCE(situacao, '')) LIKE '%RECOLH%' THEN 'Recolhido'
                ELSE 'Outros'
            END AS categoria,
            COUNT(*) AS total
        FROM internos
        WHERE status = 'A'
        GROUP BY categoria
        ORDER BY total DESC
    ");
    $rows = $stmt->fetchAll();
    foreach ($rows as $r) {
        $inicioChartsData['situacao']['labels'][] = $r['categoria'];
        $inicioChartsData['situacao']['values'][] = (int)$r['total'];
    }

    // Pie: Eletrônicos na cela por tipo
    $stmt = $pdo->query("
        SELECT
            COALESCE(NULLIF(TRIM(tipo_item), ''), 'Outros') AS tipo,
            COUNT(*) AS total
        FROM internos_eletronicos e
        JOIN internos i ON i.ipen = e.id_interno AND i.status = 'A'
        WHERE e.situacao = 'Na Cela'
        GROUP BY tipo
        ORDER BY total DESC
        LIMIT 12
    ");
    $rows = $stmt->fetchAll();
    foreach ($rows as $r) {
        $inicioChartsData['eletronicos_na_cela']['labels'][] = $r['tipo'];
        $inicioChartsData['eletronicos_na_cela']['values'][] = (int)$r['total'];
    }

    // Série: Escoltas últimos 14 dias
    $stmt = $pdo->query("
        SELECT
            data_cadastro AS dia,
            COUNT(*) AS total,
            SUM(CASE WHEN UPPER(COALESCE(status, '')) = 'FINALIZADO' THEN 1 ELSE 0 END) AS finalizadas,
            SUM(CASE WHEN UPPER(COALESCE(status, '')) = 'PENDENTE' THEN 1 ELSE 0 END) AS pendentes
        FROM eclusa_movimentacoes_escolta
        WHERE data_cadastro >= DATE_SUB(CURDATE(), INTERVAL 13 DAY)
        GROUP BY dia
        ORDER BY dia ASC
    ");
    $map = [];
    foreach ($stmt->fetchAll() as $r) {
        $map[$r['dia']] = [
            'total' => (int)$r['total'],
            'finalizadas' => (int)$r['finalizadas'],
            'pendentes' => (int)$r['pendentes'],
        ];
    }
    for ($i = 13; $i >= 0; $i--) {
        $d = (new DateTime())->modify("-{$i} day")->format('Y-m-d');
        $inicioChartsData['escoltas_14d']['labels'][] = (new DateTime($d))->format('d/m');
        $inicioChartsData['escoltas_14d']['total'][] = $map[$d]['total'] ?? 0;
        $inicioChartsData['escoltas_14d']['finalizadas'][] = $map[$d]['finalizadas'] ?? 0;
        $inicioChartsData['escoltas_14d']['pendentes'][] = $map[$d]['pendentes'] ?? 0;
    }

    // Série: Eclusa - entradas/saídas últimos 14 dias (por movimentação de veículos)
    $stmt = $pdo->query("
        SELECT
            data_movimentacao AS dia,
            SUM(CASE WHEN hora_entrada IS NOT NULL THEN 1 ELSE 0 END) AS entradas,
            SUM(CASE WHEN hora_saida IS NOT NULL THEN 1 ELSE 0 END) AS saidas
        FROM eclusa_movimentacoes
        WHERE data_movimentacao >= DATE_SUB(CURDATE(), INTERVAL 13 DAY)
        GROUP BY dia
        ORDER BY dia ASC
    ");
    $map = [];
    foreach ($stmt->fetchAll() as $r) {
        $map[$r['dia']] = [
            'entradas' => (int)$r['entradas'],
            'saidas' => (int)$r['saidas'],
        ];
    }
    for ($i = 13; $i >= 0; $i--) {
        $d = (new DateTime())->modify("-{$i} day")->format('Y-m-d');
        $inicioChartsData['eclusa_14d']['labels'][] = (new DateTime($d))->format('d/m');
        $inicioChartsData['eclusa_14d']['entradas'][] = $map[$d]['entradas'] ?? 0;
        $inicioChartsData['eclusa_14d']['saidas'][] = $map[$d]['saidas'] ?? 0;
    }

    // Hoje (cards rápidos)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM eclusa_movimentacoes_escolta WHERE data_cadastro = CURDATE()");
    $stmt->execute();
    $inicioHoje['escoltas_total'] = (int)($stmt->fetchColumn() ?: 0);

    $stmt = $pdo->prepare("
        SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN hora_entrada IS NOT NULL THEN 1 ELSE 0 END) AS entradas,
            SUM(CASE WHEN hora_saida IS NOT NULL THEN 1 ELSE 0 END) AS saidas
        FROM eclusa_movimentacoes
        WHERE data_movimentacao = CURDATE()
    ");
    $stmt->execute();
    $r = $stmt->fetch();
    if ($r) {
        $inicioHoje['eclusa_total'] = (int)$r['total'];
        $inicioHoje['eclusa_entradas'] = (int)$r['entradas'];
        $inicioHoje['eclusa_saidas'] = (int)$r['saidas'];
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM censura_cartas WHERE status_registro='Ativo' AND DATE(recebido_em)=CURDATE()");
    $stmt->execute();
    $inicioHoje['cartas_total'] = (int)($stmt->fetchColumn() ?: 0);
} catch (Exception $e) {
    // Silenciar para não quebrar o início
}

// Autoload config (manter compatibilidade)
$autoloadConfig = null;
$start = $_GET['start'] ?? '';
if ($start === 'rouparia') {
    $autoloadConfig = [
        'page' => 'paginas/censura_rouparia_numeros.php',
        'title' => 'Rouparia',
        'parent' => 'Censura',
    ];
}
