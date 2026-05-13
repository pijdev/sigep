<?php
ob_start(); 
date_default_timezone_set('America/Sao_Paulo');
ini_set('display_errors', 0);

$pdo = null;
try {
    $config = require __DIR__ . '/../conf/db.php'; 
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['user'], $config['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, 
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) { die("Erro 500"); }

// --- MODO IMPRESSÃO (ESTILIZADO PARA COLETA DE ASSINATURA) ---
if (isset($_GET['print_termo'])) {
    $mes_ref = $_GET['f_mes'];
    $f_gal = $_GET['f_gal'] ?? '';
    $f_blo = $_GET['f_blo'] ?? '';
    $f_res = $_GET['f_res'] ?? '';
    $f_search = $_GET['f_search'] ?? '';

    // SQL que busca pedidos pendentes aplicando filtros e priorizando Nome Social
    $sql_p = "SELECT pc.ipen, pc.valor_total, pc.mes_referencia,
              IFNULL(NULLIF(i.nome_social, ''), pc.nome_na_epoca) as nome_f,
              i.galeria, i.bloco, i.res
              FROM peculio_controle pc
              JOIN internos i ON pc.ipen = i.ipen
              WHERE pc.status_entrega = 'Pendente' AND pc.mes_referencia = ?";
    
    if($f_gal) $sql_p .= " AND i.galeria = '$f_gal'";
    if($f_blo) $sql_p .= " AND i.bloco = '$f_blo'";
    if($f_res) $sql_p .= " AND i.res = '$f_res'";
    if($f_search) $sql_p .= " AND (pc.nome_na_epoca LIKE '%$f_search%' OR pc.ipen = '$f_search')";

    $sql_p .= " ORDER BY i.galeria, i.bloco, CAST(i.res AS UNSIGNED), nome_f";
    
    $stmt = $pdo->prepare($sql_p);
    $stmt->execute([$mes_ref]);
    $lista = $stmt->fetchAll();
    ?>
    <html><head><title>Termo de Assinatura Pecúlio</title>
    <style>
        @page { size: A4 landscape; margin: 0.5cm; }
        body { font-family: 'Arial', sans-serif; margin: 0; padding: 0; text-transform: uppercase; }
        .wrapper { width: 100%; border: 2pt solid #000; }
        .title { text-align: center; font-size: 19pt; font-weight: bold; padding: 12px; border-bottom: 2pt solid #000; background-color: #eee !important; -webkit-print-color-adjust: exact; }
        table { width: 100%; border-collapse: collapse; }
        th { border: 1.2pt solid #000; padding: 6px; font-size: 12pt; background-color: #f2f2f2 !important; -webkit-print-color-adjust: exact; }
        td { border: 1.2pt solid #000; padding: 5px; font-size: 11pt; height: 35px; }
        .text-center { text-align: center; }
        .bold { font-weight: bold; }
        /* Ajuste de colunas */
        .col-ipen { width: 90px; }
        .col-nome { width: auto; }
        .col-local { width: 90px; }
        .col-valor { width: 110px; }
        .col-ass { width: 380px; }
    </style></head>
    <body onload="window.print();">
        <div class="wrapper">
            <div class="title">TERMO DE RECEBIMENTO DE PECÚLIO - REF: <?= $mes_ref ?></div>
            <table>
                <thead>
                    <tr>
                        <th class="col-ipen">IPEN</th>
                        <th class="col-nome">NOME DO INTERNO</th>
                        <th class="col-local">LOCAL</th>
                        <th class="col-valor">VALOR R$</th>
                        <th class="col-ass">ASSINATURA DO RECEBEDOR</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(!$lista) echo "<tr><td colspan='5' align='center'>Nenhum pecúlio pendente encontrado para os filtros selecionados.</td></tr>"; ?>
                    <?php foreach($lista as $reg): ?>
                    <tr>
                        <td class="text-center bold"><?= $reg['ipen'] ?></td>
                        <td class="bold px-2"><?= $reg['nome_f'] ?></td>
                        <td class="text-center bold"><?= "{$reg['galeria']}{$reg['bloco']}-{$reg['res']}" ?></td>
                        <td class="text-center bold">R$ <?= number_format($reg['valor_total'], 2, ',', '.') ?></td>
                        <td></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div style="margin-top: 5px; font-size: 8pt; text-align: right;">Relatório Gerado em: <?= date('d/m/Y H:i') ?></div>
    </body></html>
    <?php exit;
}

// --- FILTROS DE TELA ---
$f_mes = $_GET['f_mes'] ?? date('m/Y');
$f_gal = $_GET['f_gal'] ?? '';
$f_blo = $_GET['f_blo'] ?? '';
$f_res = $_GET['f_res'] ?? '';
$f_search = $_GET['f_search'] ?? '';

// SQL de Preview
$sql = "SELECT pc.*, i.galeria as g_at, i.bloco as b_at, i.res as c_at,
        IFNULL(NULLIF(i.nome_social, ''), pc.nome_na_epoca) as nome_exibicao
        FROM peculio_controle pc 
        JOIN internos i ON pc.ipen = i.ipen
        WHERE pc.status_entrega = 'Pendente' AND pc.mes_referencia = ?";

if($f_search) $sql .= " AND (pc.nome_na_epoca LIKE '%$f_search%' OR pc.ipen = '$f_search')";
if($f_gal)    $sql .= " AND i.galeria = '$f_gal'";
if($f_blo)    $sql .= " AND i.bloco = '$f_blo'";
if($f_res)    $sql .= " AND i.res = '$f_res'";

$sql .= " ORDER BY i.galeria, i.bloco, CAST(i.res AS UNSIGNED), nome_exibicao";
$stmt_t = $pdo->prepare($sql);
$stmt_t->execute([$f_mes]);
$internos_preview = $stmt_t->fetchAll();
?>

<div class="container-fluid my-4">
    <h3 class="mb-4 text-white uppercase fw-bold border-bottom pb-2">
        <i class="bi bi-printer-fill me-2 text-warning"></i> Gerar Lista de Assinaturas (Pecúlio)
    </h3>

    <div class="card bg-dark text-white border-secondary shadow mb-4">
        <div class="card-body">
            <form action="paginas/peculio_assinatura.php" onsubmit="handleDynamicSubmit(event)">
                <div class="row g-3">
                    <div class="col-md-2">
                        <label class="small fw-bold text-info">MÊS DE REFERÊNCIA:</label>
                        <select name="f_mes" class="form-select form-select-sm">
                            <?php
                            $meses_nome = ["","Janeiro","Fevereiro","Março","Abril","Maio","Junho","Julho","Agosto","Setembro","Outubro","Novembro","Dezembro"];
                            for ($i = -4; $i <= 4; $i++) {
                                $time = strtotime("$i months");
                                $val = date("m/Y", $time);
                                $label = $meses_nome[(int)date("m", $time)] . " / " . date("Y", $time);
                                echo "<option value='$val' ".($f_mes==$val?'selected':'').">$label</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="small fw-bold">NOME OU IPEN:</label>
                        <input type="text" name="f_search" class="form-control form-control-sm" placeholder="Buscar..." value="<?= htmlspecialchars($f_search) ?>">
                    </div>
                    <div class="col-md-1">
                        <label class="small fw-bold">GALERIA:</label>
                        <select name="f_gal" class="form-select form-select-sm"><option value="">Tudo</option><?php foreach(['A','B','C','D','E','F','G','H','S','T','ST','LGBT'] as $v) echo "<option value='$v'".($f_gal==$v?'selected':'').">$v</option>"; ?></select>
                    </div>
                    <div class="col-md-1">
                        <label class="small fw-bold">BLOCO:</label>
                        <select name="f_blo" class="form-select form-select-sm"><option value="">Tudo</option><?php foreach(['A','B','C','D','E'] as $v) echo "<option value='$v'".($f_blo==$v?'selected':'').">$v</option>"; ?></select>
                    </div>
                    <div class="col-md-1">
                        <label class="small fw-bold">CELA:</label>
                        <input type="text" name="f_res" class="form-control form-control-sm" placeholder="Nº" value="<?= $f_res ?>">
                    </div>
                    <div class="col-md-2 d-flex align-items-end gap-1">
                        <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold">FILTRAR</button>
                        <button type="button" onclick="loadPage('paginas/peculio_assinatura.php')" class="btn btn-secondary btn-sm">Limpar</button>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="button" onclick="window.imprimirTermoAssinatura()" class="btn btn-success w-100 fw-bold shadow-sm">
                            <i class="bi bi-file-earmark-pdf"></i> GERAR LISTA DE ASSINATURA
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="table-responsive rounded bg-white shadow">
        <table class="table table-sm table-bordered table-hover align-middle m-0">
            <thead class="table-secondary small text-uppercase fw-bold text-center">
                <tr><th>IPEN</th><th>NOME (SOCIAL)</th><th>LOCAL ATUAL</th><th>MÊS REFERENTE</th><th>VALOR DO PEDIDO</th></tr>
            </thead>
            <tbody>
                <?php if(!$internos_preview) echo "<tr><td colspan='5' class='text-center p-4 text-muted'>Nenhum registro pendente para estes filtros.</td></tr>"; ?>
                <?php foreach($internos_preview as $reg): ?>
                <tr>
                    <td class="text-center fw-bold"><?= $reg['ipen'] ?></td>
                    <td class="px-2 fw-bold text-uppercase small"><?= $reg['nome_exibicao'] ?></td>
                    <td class="text-center fw-bold"><?= "{$reg['g_at']}{$reg['b_at']}-{$reg['c_at']}" ?></td>
                    <td class="text-center"><?= $reg['mes_referencia'] ?></td>
                    <td class="text-center fw-bold text-success">R$ <?= number_format($reg['valor_total'], 2, ',', '.') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
window.imprimirTermoAssinatura = function() {
    // Captura os valores dos inputs para passar na URL da impressão
    const mes = document.querySelector('select[name="f_mes"]').value;
    const search = document.querySelector('input[name="f_search"]').value;
    const gal = document.querySelector('select[name="f_gal"]').value;
    const blo = document.querySelector('select[name="f_blo"]').value;
    const res = document.querySelector('input[name="f_res"]').value;

    const query = new URLSearchParams({
        print_termo: 'true',
        f_mes: mes,
        f_search: search,
        f_gal: gal,
        f_blo: blo,
        f_res: res
    });

    window.open('paginas/peculio_assinatura.php?' + query.toString(), '_blank');
};
</script>

<?php ob_end_flush(); ?>