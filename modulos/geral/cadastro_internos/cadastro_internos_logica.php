<?php
// modulos/geral/cadastro_internos/cadastro_internos_logica.php
ob_start();
date_default_timezone_set('America/Sao_Paulo');
ini_set('display_errors', 0);
error_reporting(E_ALL);

// CONEXÃO
$pdo = null;
try {
    $config = require __DIR__ . '/../../../conf/db.php';
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['user'], $config['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Erro 500: Falha no Banco.");
}

// 1. UPDATE AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'salvar_cadastro') {
    ob_clean();
    header('Content-Type: application/json');
    try {
        $sql = "UPDATE internos SET nome_social = ?, lgbt = ?, apelido = ?, forma_pagamento = ?, regalia = ?, cor_roupa = ?, regalia_setor = ?, kit = ?, regalia_kit = ?, tamanho_kit = ? WHERE ipen = ?";

        $kit = !empty($_POST['kit']) ? $_POST['kit'] : null;
        $regkit = !empty($_POST['regalia_kit']) ? $_POST['regalia_kit'] : null;

        $pdo->prepare($sql)->execute([
            $_POST['nome_social'] ?? '',
            $_POST['lgbt'],
            $_POST['apelido'] ?? '',
            $_POST['forma_pagamento'] ?? '',
            $_POST['regalia'],
            $_POST['cor_roupa'],
            $_POST['regalia_setor'] ?? '',
            $kit,
            $regkit,
            $_POST['tamanho_kit'],
            $_POST['ipen']
        ]);

        // Registrar no histórico detalhado (Opcional, mas recomendado se você quiser rastrear quem alterou)
        // Por enquanto mantemos o update simples.

        echo json_encode(['success' => true]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

// 2. LISTAS AUXILIARES
$galerias_db = $pdo->query("SELECT DISTINCT galeria FROM internos WHERE galeria != '' ORDER BY galeria")->fetchAll(PDO::FETCH_COLUMN);
$blocos_db = $pdo->query("SELECT DISTINCT bloco FROM internos WHERE bloco != '' ORDER BY bloco")->fetchAll(PDO::FETCH_COLUMN);
$situacoes_db = $pdo->query("SELECT DISTINCT situacao FROM internos WHERE situacao != '' ORDER BY situacao")->fetchAll(PDO::FETCH_COLUMN);
$setores_db = $pdo->query("SELECT DISTINCT regalia_setor FROM internos WHERE regalia_setor != '' AND regalia_setor IS NOT NULL ORDER BY regalia_setor")->fetchAll(PDO::FETCH_COLUMN);

// 3. FILTROS
$f = [
    'search' => $_GET['search'] ?? '',
    'situacao' => $_GET['situacao'] ?? '',
    'galeria' => $_GET['galeria'] ?? '',
    'bloco' => $_GET['bloco'] ?? '',
    'res' => $_GET['res'] ?? '',
    'regalia' => $_GET['regalia'] ?? '',
    'kit_num' => $_GET['kit_num'] ?? '',
    'setor' => $_GET['setor'] ?? '',
    'status' => $_GET['status'] ?? 'A',
    'data_ini' => $_GET['data_ini'] ?? '',
    'data_fim' => $_GET['data_fim'] ?? ''
];

// 4. ORDENAÇÃO
$sort_by = $_GET['sort_by'] ?? 'nome';
$sort_order = $_GET['sort_order'] ?? 'ASC';
$valid_sorts = ['ipen' => 'ipen', 'nome' => 'COALESCE(NULLIF(nome_social, ""), nome)', 'local' => 'galeria, bloco, res', 'situacao' => 'situacao', 'data' => 'data_ativo', 'kit' => 'kit', 'setor' => 'regalia_setor'];
$order_sql = isset($valid_sorts[$sort_by]) ? "{$valid_sorts[$sort_by]} $sort_order" : "nome ASC";

// 5. QUERY BUILDER PRINCIPAL
$where = ["1=1"];
$params = [];

if ($f['search'] !== '') {
    $where[] = "(nome LIKE :s OR nome_social LIKE :s OR apelido LIKE :s OR ipen LIKE :s)";
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
    $where[] = "res = :res";
    $params[':res'] = $f['res'];
}
if ($f['regalia'] !== '') {
    $where[] = "regalia = :reg";
    $params[':reg'] = $f['regalia'];
}
if ($f['kit_num'] !== '') {
    $where[] = "(kit = :k OR regalia_kit = :k)";
    $params[':k'] = $f['kit_num'];
}
if ($f['setor'] !== '') {
    $where[] = "regalia_setor LIKE :set";
    $params[':set'] = "%" . $f['setor'] . "%";
}
if ($f['status'] !== '') {
    $where[] = "status = :st";
    $params[':st'] = $f['status'];
}
if ($f['data_ini'] !== '') {
    $where[] = "DATE(data_ativo) >= :di";
    $params[':di'] = $f['data_ini'];
}
if ($f['data_fim'] !== '') {
    $where[] = "DATE(data_ativo) <= :df";
    $params[':df'] = $f['data_fim'];
}

$sql_base = " FROM internos WHERE " . implode(" AND ", $where);

// --- BLOCO DE IMPRESSÃO ---
if (isset($_GET['print'])) {
    $mode = $_GET['mode'] ?? 'geral';
    $titulo = "RELATÓRIO DE INTERNOS";
    $data = [];

    // LÓGICA DE DADOS POR TIPO DE RELATÓRIO
    if ($mode === 'apelido') {
        $titulo = "RELATÓRIO: LOCAL E APELIDO (ALFABÉTICA)";
        $stmt = $pdo->prepare("SELECT * FROM internos WHERE status='A' ORDER BY nome ASC");
        $stmt->execute();
        $data = $stmt->fetchAll();
    } elseif ($mode === 'trabalham') {
        $titulo = "RELATÓRIO: INTERNOS TRABALHADORES";
        $stmt = $pdo->prepare("SELECT * FROM internos WHERE status='A' AND regalia_setor IS NOT NULL AND regalia_setor != '' ORDER BY regalia_setor ASC, nome ASC");
        $stmt->execute();
        $data = $stmt->fetchAll();
    } elseif ($mode === 'lgbt') {
        $titulo = "RELATÓRIO: POPULAÇÃO LGBT";
        // Erro 500 corrigido: Buscando todas as colunas para evitar undefined index
        $stmt = $pdo->prepare("SELECT * FROM internos WHERE status='A' AND lgbt='S' ORDER BY COALESCE(NULLIF(nome_social, ''), nome) ASC");
        $stmt->execute();
        $data = $stmt->fetchAll();
    } elseif ($mode === 'hist_range') {
        // Histórico de saídas (INATIVADOS) por data
        $d1 = $_GET['h_ini'];
        $d2 = $_GET['h_fim'];
        $titulo = "SAÍDAS NO PERÍODO: " . date('d/m/y', strtotime($d1)) . " A " . date('d/m/y', strtotime($d2));

        // Busca na tabela de histórico onde o STATUS mudou para I (Inativo)
        $sqlHist = "SELECT h.ipen, i.nome, i.nome_social, h.valor_antigo, h.valor_novo, h.data_alteracao
                    FROM internos_historico_detalhado h
                    LEFT JOIN internos i ON h.ipen = i.ipen
                    WHERE h.campo = 'STATUS'
                    AND (h.valor_novo = 'I' OR h.valor_novo = 'INATIVO' OR h.operacao = 'INATIVADO')
                    AND DATE(h.data_alteracao) BETWEEN ? AND ?
                    ORDER BY h.data_alteracao DESC";
        $stmt = $pdo->prepare($sqlHist);
        $stmt->execute([$d1, $d2]);
        $data = $stmt->fetchAll();
    } elseif ($mode === 'hist_volta') {
        // Saíram e Voltaram (Ativos hoje, mas com registro de Inativação no passado)
        $titulo = "RELATÓRIO: INTERNOS REINCIDENTES (SAÍRAM E VOLTARAM)";
        $sqlVolta = "SELECT DISTINCT i.*
                     FROM internos i
                     JOIN internos_historico_detalhado h ON i.ipen = h.ipen
                     WHERE i.status = 'A'
                     AND h.campo = 'STATUS'
                     AND (h.valor_novo = 'I' OR h.valor_novo = 'INATIVO')
                     ORDER BY i.nome ASC";
        $stmt = $pdo->prepare($sqlVolta);
        $stmt->execute();
        $data = $stmt->fetchAll();
    } else {
        // Padrão (Filtros da Tela)
        $filtros_txt = [];
        if ($f['search']) $filtros_txt[] = "Busca: {$f['search']}";
        if ($f['galeria']) $filtros_txt[] = "Gal: {$f['galeria']}";
        if ($f['kit_num']) $filtros_txt[] = "Kit: {$f['kit_num']}";
        $titulo = "RELATÓRIO GERAL - " . (!empty($filtros_txt) ? implode(", ", $filtros_txt) : "FILTROS ATUAIS");

        $stmt = $pdo->prepare("SELECT * " . $sql_base . " ORDER BY $order_sql");
        $stmt->execute($params);
        $data = $stmt->fetchAll();
    }
?>
    <!DOCTYPE html>
    <html lang="pt-br">

    <head>
        <meta charset="UTF-8">
        <title><?= $titulo ?></title>
        <link rel="icon" type="image/svg+xml" href="../favicon.svg">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            @page {
                size: A4 landscape;
                margin: 0.5cm;
            }

            body {
                font-size: 9pt;
                font-family: 'Segoe UI', sans-serif;
                padding: 10px;
            }

            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 10px;
            }

            th,
            td {
                border: 1px solid #000 !important;
                padding: 4px 6px;
                vertical-align: middle;
            }

            th {
                background: #ddd !important;
                font-weight: 800;
                text-align: center;
                text-transform: uppercase;
                font-size: 8.5pt;
            }

            .row-social {
                background-color: #f3e5f5 !important;
                -webkit-print-color-adjust: exact;
            }

            .nome-social {
                font-weight: 900;
                font-style: italic;
                text-transform: uppercase;
            }

            h4 {
                font-weight: 800;
                text-transform: uppercase;
                margin: 0;
                font-size: 16pt;
                border-bottom: 2px solid #000;
                padding-bottom: 5px;
            }

            .meta-info {
                font-size: 8pt;
                margin-top: 5px;
                text-align: right;
            }
        </style>
    </head>

    <body onload="window.print()">
        <h4><?= $titulo ?></h4>
        <div class="meta-info">Total de Registros: <b><?= count($data) ?></b> | Emitido em: <?= date('d/m/Y H:i') ?></div>

        <table>
            <thead>
                <tr>
                    <th width="70">IPEN</th>
                    <th>NOME / SOCIAL</th>
                    <?php if ($mode == 'hist_range'): ?>
                        <th width="120">DATA SAÍDA</th>
                        <th>DETALHES DA SAÍDA (SISTEMA)</th>
                    <?php elseif ($mode == 'trabalham'): ?>
                        <th>SETOR DE TRABALHO</th>
                        <th width="100">LOCAL</th>
                        <th>SITUAÇÃO</th>
                    <?php else: ?>
                        <th width="100">LOCAL</th>
                        <?php if ($mode == 'apelido') echo "<th>VULGO / APELIDO</th>"; ?>
                        <th>SITUAÇÃO</th>
                        <?php if ($mode == 'geral' || $mode == 'lgbt' || $mode == 'hist_volta'): ?>
                            <th width="50">REG</th>
                            <th width="50">COR</th>
                            <th width="50">KIT</th>
                            <th width="120">PAGAMENTO</th>
                        <?php endif; ?>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data as $r):
                    $ns = !empty($r['nome_social']) ? "<span class='nome-social'>{$r['nome_social']}</span><br><small>({$r['nome']})</small>" : "<b>{$r['nome']}</b>";
                ?>
                    <tr>
                        <td align="center"><?= $r['ipen'] ?></td>
                        <td><?= $ns ?></td>

                        <?php if ($mode == 'hist_range'): ?>
                            <td align="center"><b><?= date('d/m/Y H:i', strtotime($r['data_alteracao'])) ?></b></td>
                            <td>SAÍDA REGISTRADA (Status alterado de ATIVO para INATIVO)</td>

                        <?php elseif ($mode == 'trabalham'): ?>
                            <td><b><?= strtoupper($r['regalia_setor']) ?></b></td>
                            <td align="center"><?= "{$r['galeria']}{$r['bloco']}-{$r['res']}" ?></td>
                            <td><?= $r['situacao'] ?></td>

                        <?php else: ?>
                            <td align="center"><b><?= "{$r['galeria']}{$r['bloco']}-{$r['res']}" ?></b></td>
                            <?php if ($mode == 'apelido') echo "<td>" . ($r['apelido'] ?: '-') . "</td>"; ?>
                            <td class="small"><?= $r['situacao'] ?></td>

                            <?php if ($mode == 'geral' || $mode == 'lgbt' || $mode == 'hist_volta'): ?>
                                <td align="center"><?= $r['regalia'] ?></td>
                                <td align="center"><?= $r['cor_roupa'] ?></td>
                                <td align="center"><?= ($r['regalia'] == 'S' && $r['regalia_kit']) ? $r['regalia_kit'] : $r['kit'] ?></td>
                                <td><?= $r['forma_pagamento'] ?></td>
                            <?php endif; ?>

                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if (empty($data)): ?>
            <div style="text-align:center; margin-top:50px; color:#666;">Nenhum registro encontrado para este relatório.</div>
        <?php endif; ?>
    </body>

    </html>
<?php exit;
}

// 6. LISTAGEM TELA
$limit = 50;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;
$total = $pdo->prepare("SELECT COUNT(*) " . $sql_base);
$total->execute($params);
$count_total = $total->fetchColumn();
$total_paginas = ceil($count_total / $limit);
$stmt = $pdo->prepare("SELECT * " . $sql_base . " ORDER BY $order_sql LIMIT $limit OFFSET $offset");
$stmt->execute($params);
$internos = $stmt->fetchAll();

// 6.1 ESTATÍSTICAS PARA CARDS
$stats_sql = "SELECT
    COUNT(*) as total,
    SUM(CASE WHEN lgbt='S' THEN 1 ELSE 0 END) as total_lgbt,
    SUM(CASE WHEN forma_pagamento='PIX' THEN 1 ELSE 0 END) as total_pix,
    SUM(CASE WHEN regalia_setor IS NOT NULL AND regalia_setor != '' THEN 1 ELSE 0 END) as total_salario
" . $sql_base;
$stats = $pdo->prepare($stats_sql);
$stats->execute($params);
$stats_data = $stats->fetch();

function sortLink($col, $label)
{
    global $sort_by, $sort_order;
    $new_o = ($sort_by == $col && $sort_order == 'ASC') ? 'DESC' : 'ASC';
    $qs = http_build_query(array_merge($_GET, ['sort_by' => $col, 'sort_order' => $new_o, 'page' => 1]));
    $icon = ($sort_by == $col) ? ($sort_order == 'ASC' ? '<i class="fas fa-sort-up ml-1"></i>' : '<i class="fas fa-sort-down ml-1"></i>') : '<i class="fas fa-sort text-muted small ml-1" style="opacity:0.3"></i>';
    return "<a href='#' class='text-white text-decoration-none' onclick=\"window.reloadContent('modulos/geral/cadastro_internos/cadastro_internos_view.php?{$qs}'); return false;\">{$label} {$icon}</a>";
}
?>
