<?php
// paginas/internos_recebimento_livros.php

$config = require __DIR__ . '/../conf/db.php';
date_default_timezone_set('America/Sao_Paulo');

try {
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['user'], $config['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    die("Erro DB: " . $e->getMessage());
}

if (session_status() === PHP_SESSION_NONE) session_start();

$nome_user = $_SESSION['user_nome'] ?? $_SESSION['usuario_nome'] ?? 'Usuario Sistema';
$setor_user = $_SESSION['user_setor'] ?? '';
$usuario_logado = $nome_user . ($setor_user ? " (" . mb_strtoupper($setor_user, 'UTF-8') . ")" : "");

// Verificar se usuário tem acesso total (admin ou censura)
$eh_admin = (isset($_SESSION['user_admin']) && $_SESSION['user_admin'] === true);
$eh_censura = (isset($_SESSION['perm_censura']) && $_SESSION['perm_censura'] > 0);
$tem_acesso_total = $eh_admin || $eh_censura;

// Verificar se usuário é da portaria (mas não se for admin ou censura)
$eh_portaria = (isset($_SESSION['perm_portaria']) && $_SESSION['perm_portaria'] > 0) && !$tem_acesso_total;

// --- AÇÕES BACKEND ---
if (isset($_REQUEST['acao'])) {

    // 1. BUSCAR INTERNO
    if ($_REQUEST['acao'] === 'buscar_interno') {
        ob_clean(); header('Content-Type: application/json');
        $termo = trim($_REQUEST['termo']);
        try {
            $sql = "SELECT ipen, nome, nome_social, galeria, bloco, res FROM internos
                    WHERE (ipen LIKE ? OR nome LIKE ? OR nome_social LIKE ?) AND status = 'A' LIMIT 10";
            $stmt = $pdo->prepare($sql);
            $like = "%$termo%";
            $stmt->execute([$like, $like, $like]);
            echo json_encode(['status' => 'success', 'dados' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch (Exception $e) { echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]); }
        exit;
    }

    // 2. REGISTRAR LIVROS
    if ($_REQUEST['acao'] === 'registrar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        ob_clean(); header('Content-Type: application/json');
        try {
            $pdo->beginTransaction();

            $stmtInsert = $pdo->prepare("INSERT INTO internos_recebimento_livros (id_interno, data_recebimento, entregue_por_tipo, entregue_por_nome, cadastrado_por) VALUES (?, NOW(), ?, ?, ?)");
            $stmtInsert->execute([$_POST['ipen'], $_POST['entregue_por_tipo'], $_POST['entregue_por_nome'], $usuario_logado]);
            $id_recebimento = $pdo->lastInsertId();

            $stmtItem = $pdo->prepare("INSERT INTO internos_recebimento_livros_itens (id_recebimento, titulo_livro, autor) VALUES (?, ?, ?)");
            if(isset($_POST['livros']) && is_array($_POST['livros'])){
                foreach ($_POST['livros'] as $livro) {
                    if (!empty($livro['titulo'])) {
                        $stmtItem->execute([$id_recebimento, $livro['titulo'], $livro['autor'] ?? '']);
                    }
                }
            }
            $pdo->commit();
            echo json_encode(['status' => 'success', 'msg' => 'Registrado com sucesso!', 'id_novo' => $id_recebimento]);
        } catch (Exception $e) { $pdo->rollBack(); echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]); }
        exit;
    }

    // 3. EXCLUIR
    if ($_REQUEST['acao'] === 'excluir' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        ob_clean(); header('Content-Type: application/json');
        try {
            // Restrição: Portaria não pode excluir
            if ($eh_portaria) {
                throw new Exception("Usuário da portaria não tem permissão para excluir registros.");
            }

            $stmt = $pdo->prepare("DELETE FROM internos_recebimento_livros WHERE id = ?");
            $stmt->execute([$_POST['id']]);
            echo json_encode(['status' => 'success', 'msg' => 'Registro excluído.']);
        } catch (Exception $e) { echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]); }
        exit;
    }

    // 4. MARCAR ENTREGUE
    if ($_REQUEST['acao'] === 'marcar_entregue' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        ob_clean(); header('Content-Type: application/json');
        try {
            $ids = explode(',', $_POST['ids_recebimento']);
            $stmt = $pdo->prepare("UPDATE internos_recebimento_livros SET data_entrega_interno = NOW() WHERE id = ? AND data_entrega_interno IS NULL");
            foreach ($ids as $id) $stmt->execute([$id]);
            echo json_encode(['status' => 'success']);
        } catch (Exception $e) { echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]); }
        exit;
    }
}

// --- DADOS PARA LISTAGEM E FILTROS ---
$where = ["1=1"];
$params = [];

// Filtros
if (!empty($_GET['busca'])) {
    $where[] = "(i.nome LIKE ? OR i.ipen LIKE ? OR l.entregue_por_nome LIKE ?)";
    $params[] = "%{$_GET['busca']}%"; $params[] = "%{$_GET['busca']}%"; $params[] = "%{$_GET['busca']}%";
}
if (!empty($_GET['galeria'])) { $where[] = "i.galeria = ?"; $params[] = $_GET['galeria']; }
if (!empty($_GET['bloco']))   { $where[] = "i.bloco = ?"; $params[] = $_GET['bloco']; }
if (!empty($_GET['cela']))    { $where[] = "i.res = ?"; $params[] = $_GET['cela']; }

$sqlList = "SELECT l.*, i.nome as nome_interno, i.nome_social, i.galeria, i.bloco, i.res,
            (SELECT GROUP_CONCAT(titulo_livro SEPARATOR '; ') FROM internos_recebimento_livros_itens li WHERE li.id_recebimento = l.id) as resumo_livros,
            (SELECT COUNT(*) FROM internos_recebimento_livros_itens li WHERE li.id_recebimento = l.id) as qtd_livros
            FROM internos_recebimento_livros l
            JOIN internos i ON l.id_interno = i.ipen
            WHERE " . implode(" AND ", $where) . "
            ORDER BY l.data_recebimento DESC LIMIT 50";

try {
    $stmtList = $pdo->prepare($sqlList);
    $stmtList->execute($params);
    $recebimentos = $stmtList->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $recebimentos = []; }

// Dados para Selects de Filtro (Carrega do banco o que existe)
$galerias = $pdo->query("SELECT DISTINCT galeria FROM internos ORDER BY galeria")->fetchAll(PDO::FETCH_COLUMN);
$blocos = $pdo->query("SELECT DISTINCT bloco FROM internos ORDER BY bloco")->fetchAll(PDO::FETCH_COLUMN);
?>

<script>
    window.pageTitle = 'Recebimento de Livros';
    window.currentPage = 'internos_recebimento_livros.php';

    window.safeReload = function() {
        if(typeof loadPage === 'function') loadPage('paginas/internos_recebimento_livros.php');
        else if (typeof window.reloadContent === 'function') window.reloadContent('paginas/internos_recebimento_livros.php');
        else window.location.reload();
    }
</script>

<style>
    .status-badge { font-size: 0.8rem; padding: 4px 8px; border-radius: 4px; font-weight: bold; }
    .bg-pendente { background: #ffeeba; color: #856404; }
    .bg-entregue { background: #d4edda; color: #155724; }

    /* Offcanvas */
    .offcanvas-right {
        position: fixed; top: 0; right: 0; width: 600px; height: 100%;
        background: #fff; box-shadow: -10px 0 30px rgba(0,0,0,0.3);
        transform: translateX(100%); transition: transform 0.3s ease-in-out;
        z-index: 1060; display: flex; flex-direction: column;
    }
    .offcanvas-header { background: #17a2b8; color: white; padding: 15px; display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid #117a8b; }
    .offcanvas-body { padding: 20px; overflow-y: auto; flex: 1; }

    /* Search Results */
    .search-results {
        position: absolute; width: 100%; z-index: 10000; background: white;
        border: 1px solid #ddd; max-height: 200px; overflow-y: auto; display: none;
        box-shadow: 0 4px 15px rgba(0,0,0,0.3);
    }
    .search-item { padding: 10px; cursor: pointer; border-bottom: 1px solid #eee; }
    .search-item:hover { background-color: #f0f4ff; }

    /* Dark Mode */
    body.dark-mode .offcanvas-right { background-color: #343a40; color: #fff; }
    body.dark-mode .offcanvas-header { background-color: #212529; border-bottom-color: #17a2b8; }
    body.dark-mode .form-control { background-color: #3f474e; border-color: #6c757d; color: #fff; }
    body.dark-mode .search-results { background-color: #3f474e; border-color: #6c757d; }
    body.dark-mode .search-item { border-bottom-color: #4b545c; color: #fff; }
    body.dark-mode .search-item:hover { background-color: #4b545c; }
    body.dark-mode .input-group-text { background-color: #3f474e; border-color: #6c757d; color: #fff; }
    body.dark-mode .table-hover tbody tr:hover { color: #fff; background-color: rgba(255,255,255,0.075); }
    body.dark-mode .card { background-color: #343a40; color: #fff; }
</style>

<section class="content pt-3">
    <div class="container-fluid">

        <div class="row mb-3">
            <div class="col-lg-3 d-flex gap-2 mb-2">
                <button class="btn btn-info shadow-sm btn-block" onclick="window.abrirNovoRecebimento()">
                    <i class="fas fa-book-open"></i> Novo Recebimento
                </button>
            </div>
            <div class="col-lg-9">
                <form class="form-inline float-right" id="formFiltro" onsubmit="return false;">

                    <!-- Busca Texto -->
                    <div class="input-group input-group-sm mr-2 mb-1">
                        <input type="text" class="form-control" name="busca" placeholder="Nome, IPEN..." value="<?= $_GET['busca']??'' ?>">
                    </div>

                    <!-- Filtro Galeria -->
                    <div class="input-group input-group-sm mr-2 mb-1">
                        <select class="form-control" name="galeria">
                            <option value="">Galeria</option>
                            <?php foreach($galerias as $g) echo "<option value='$g' ".($_GET['galeria']==$g?'selected':'').">$g</option>"; ?>
                        </select>
                    </div>

                    <!-- Filtro Bloco -->
                    <div class="input-group input-group-sm mr-2 mb-1">
                        <select class="form-control" name="bloco">
                            <option value="">Bloco</option>
                            <?php foreach($blocos as $b) echo "<option value='$b' ".($_GET['bloco']==$b?'selected':'').">$b</option>"; ?>
                        </select>
                    </div>

                    <!-- Filtro Cela -->
                    <div class="input-group input-group-sm mr-2 mb-1">
                        <input type="text" class="form-control" name="cela" placeholder="Cela" size="5" value="<?= $_GET['cela']??'' ?>">
                    </div>

                    <button type="button" class="btn btn-sm btn-outline-info mb-1" onclick="window.safeReloadWithFilters()">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>
        </div>

        <div class="card shadow">
            <div class="card-header border-0">
                <h3 class="card-title">Entradas de Livros</h3>
                <div class="card-tools">
                    <?php if (!$eh_portaria): ?>
                        <button class="btn btn-success btn-sm" onclick="imprimirSelecionados('termo')" <?= ($_SESSION['user_nome'] == 'Rouparia' ? 'disabled' : '') ?>><i class="fas fa-file-signature"></i> Termo Interno</button>
                    <?php endif; ?>
                    <button class="btn btn-secondary btn-sm" onclick="imprimirSelecionados('recibo')" <?= ($_SESSION['user_nome'] == 'Rouparia' ? 'disabled' : '') ?>><i class="fas fa-receipt"></i> Recibo Visita</button>
                </div>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover table-striped">
                    <thead>
                        <tr>
                            <th width="40"><input type="checkbox" onclick="$('.chk-print').prop('checked', this.checked)"></th>
                            <th>Data</th>
                            <th>IPEN</th>
                            <th>Interno</th>
                            <th>Livros</th>
                            <th>Entregador</th>
                            <th>Status</th>
                            <th width="50" class="text-center">Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($recebimentos as $r):
                            $nome = $r['nome_social'] ? "<strong>{$r['nome_social']}</strong> <small>({$r['nome_interno']})</small>" : $r['nome_interno'];
                            $dtEntrega = $r['data_entrega_interno'] ? date('d/m/Y H:i', strtotime($r['data_entrega_interno'])) : '';
                        ?>
                        <tr>
                            <td>
                                <input type="checkbox" class="chk-print" value="<?= $r['id'] ?>"
                                       data-entregue="<?= $r['data_entrega_interno'] ? '1' : '0' ?>"
                                       data-data-entregue="<?= $dtEntrega ?>">
                            </td>
                            <td><?= date('d/m/Y H:i', strtotime($r['data_recebimento'])) ?></td>
                            <td><?= $r['id_interno'] ?></td>
                            <td><?= $nome ?><br><small class="text-muted"><?= "{$r['galeria']}-{$r['bloco']}-{$r['res']}" ?></small></td>
                            <td>
                                <span class="badge badge-info"><?= $r['qtd_livros'] ?></span> <small><?= mb_strimwidth($r['resumo_livros'], 0, 50, '...') ?></small>
                            </td>
                            <td><?= $r['entregue_por_tipo'] ?>: <?= $r['entregue_por_nome'] ?></td>
                            <td>
                                <?php if($r['data_entrega_interno']): ?>
                                    <span class='status-badge bg-entregue'>Entregue <?= date('d/m', strtotime($r['data_entrega_interno'])) ?></span>
                                <?php else: ?>
                                    <span class='status-badge bg-pendente'>Pendente</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if (!$eh_portaria): ?>
                                    <button class="btn btn-sm btn-danger" title="Excluir" onclick="window.excluirRecebimento(<?= $r['id'] ?>)"><i class="fas fa-trash"></i></button>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-secondary" title="Apenas visualização" disabled>
                                        <i class="fas fa-eye"></i>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<!-- OFFCANVAS: NOVO CADASTRO -->
<div id="offcanvasNovo" class="offcanvas-right">
    <div class="offcanvas-header">
        <h5 class="m-0"><i class="fas fa-book"></i> Entrada de Livros</h5>
        <button class="btn btn-sm btn-outline-light" onclick="window.fecharNovoRecebimento()"><i class="fas fa-times"></i></button>
    </div>
    <div class="offcanvas-body">
        <form id="formRegistrar">
            <input type="hidden" name="acao" value="registrar">
            <input type="hidden" name="ipen" id="hiddenIpen" required>

            <div class="form-group position-relative">
                <label>Buscar Interno</label>
                <div class="input-group">
                    <input type="text" class="form-control" id="buscaInternoInput" placeholder="IPEN ou Nome..." autocomplete="off">
                    <div class="input-group-append">
                        <span class="input-group-text" id="search-spinner" style="display:none"><i class="fas fa-spinner fa-spin"></i></span>
                    </div>
                </div>
                <div id="sugestoesInterno" class="search-results"></div>
            </div>

            <div class="form-group">
                <label>Interno Selecionado</label>
                <input type="text" class="form-control font-weight-bold" id="displayNomeInterno" readonly>
            </div>

            <div class="row">
                <div class="col-4">
                    <div class="form-group">
                        <label>Origem</label>
                        <select class="form-control" name="entregue_por_tipo">
                            <option value="Visitante">Visitante</option>
                            <option value="Advogado">Advogado</option>
                            <option value="Correios">Correios</option>
                            <option value="Doação">Doação</option>
                        </select>
                    </div>
                </div>
                <div class="col-8">
                    <div class="form-group">
                        <label>Nome do Entregador / Remetente</label>
                        <input type="text" class="form-control" name="entregue_por_nome" required>
                    </div>
                </div>
            </div>

            <hr>
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="m-0 font-weight-bold">Lista de Livros</h6>
                <button type="button" class="btn btn-sm btn-outline-success" onclick="window.addLinhaLivro()">
                    <i class="fas fa-plus"></i> Adicionar Livro
                </button>
            </div>

            <div id="containerLivros" class="mb-3">
                <!-- Linhas inseridas via JS -->
            </div>

            <div class="d-flex justify-content-end pt-3 border-top">
                 <button type="submit" class="btn btn-info btn-lg"><i class="fas fa-save"></i> Salvar Entrada</button>
            </div>
        </form>
    </div>
</div>

<script>
    // --- CONTROLES UI ---
    window.abrirNovoRecebimento = function() {
        document.getElementById('formRegistrar').reset();
        $('#hiddenIpen').val('');
        $('#displayNomeInterno').val('');
        $('#containerLivros').html('');
        window.addLinhaLivro();
        document.getElementById('offcanvasNovo').style.transform = 'translateX(0)';
    }

    window.fecharNovoRecebimento = function() { document.getElementById('offcanvasNovo').style.transform = 'translateX(100%)'; }

    window.safeReloadWithFilters = function() {
        const query = $('#formFiltro').serialize();
        if(typeof loadPage === 'function') loadPage('paginas/internos_recebimento_livros.php?' + query);
        else if (typeof window.reloadContent === 'function') window.reloadContent('paginas/internos_recebimento_livros.php?' + query);
        else window.location.href = 'paginas/internos_recebimento_livros.php?' + query;
    }

    // --- PESQUISA INTERNO ---
    let timeoutBusca = null;
    $('#buscaInternoInput').on('input', function() {
        const termo = $(this).val();
        if(termo.length < 3) { $('#sugestoesInterno').hide(); return; }

        clearTimeout(timeoutBusca);
        $('#search-spinner').show();

        timeoutBusca = setTimeout(async () => {
            try {
                const res = await fetch('paginas/internos_recebimento_livros.php?acao=buscar_interno&termo=' + encodeURIComponent(termo));
                const json = await res.json();
                let html = '';
                if(json.status === 'success' && json.dados.length > 0) {
                    json.dados.forEach(d => {
                        const nomeExib = d.nome_social ? `${d.nome_social} (${d.nome})` : d.nome;
                        const safeNome = nomeExib.replace(/'/g, "\\'");
                        html += `<div class="search-item" onclick="selecionarInterno('${d.ipen}', '${safeNome}')">
                                    <div class="font-weight-bold text-info">${d.ipen}</div>
                                    <div>${nomeExib}</div>
                                    <small class="text-muted">Loc: ${d.galeria}-${d.bloco}-${d.res}</small>
                                 </div>`;
                    });
                    $('#sugestoesInterno').html(html).show();
                } else {
                    $('#sugestoesInterno').html('<div class="p-3 text-muted text-center">Nenhum encontrado.</div>').show();
                }
            } catch(e) { console.error(e); } finally { $('#search-spinner').hide(); }
        }, 400);
    });

    window.selecionarInterno = function(ipen, nome) {
        $('#hiddenIpen').val(ipen);
        $('#displayNomeInterno').val(`${ipen} - ${nome}`);
        $('#sugestoesInterno').hide();
        $('#buscaInternoInput').val('');
    }

    // --- DINAMICA DE LIVROS ---
    let livroCount = 0;
    window.addLinhaLivro = function() {
        const idx = livroCount++;
        const html = `
            <div class="card mb-2 border-light bg-light" id="linha_livro_${idx}">
                <div class="card-body p-2">
                    <div class="form-group mb-1">
                        <input type="text" class="form-control form-control-sm font-weight-bold"
                               name="livros[${idx}][titulo]" placeholder="Título do Livro (Obrigatório)" required>
                    </div>
                    <div class="d-flex justify-content-between">
                        <input type="text" class="form-control form-control-sm mr-2"
                               name="livros[${idx}][autor]" placeholder="Autor (Opcional)">
                        <button type="button" class="btn btn-xs btn-danger" onclick="$('#linha_livro_${idx}').remove()">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>`;
        $('#containerLivros').append(html);
    }

    // --- SALVAR ---
    $('#formRegistrar').on('submit', async function(e){
        e.preventDefault();
        if(!$('#hiddenIpen').val()) return alert('Selecione um interno!');

        try {
            const res = await fetch('paginas/internos_recebimento_livros.php', {method:'POST', body: new FormData(this)});
            const json = await res.json();
            if(json.status === 'success') {
                alert(json.msg);
                if(confirm('Imprimir Recibo?')) window.open('paginas/imprimir_recibo_livros.php?ids=' + json.id_novo, '_blank');
                window.fecharNovoRecebimento();
                window.safeReloadWithFilters();
            } else alert(json.msg);
        } catch(err) { alert('Erro: ' + err); }
    });

    // --- IMPRESSÃO ---
    window.imprimirSelecionados = async function(tipo) {
        let ids = [], jaEntregues = [];
        $('.chk-print:checked').each(function() {
            ids.push($(this).val());
            if($(this).data('entregue') == 1) jaEntregues.push($(this).data('data-entregue'));
        });

        if(!ids.length) return alert('Selecione registros.');

        // Recibo Visita
        if(tipo === 'recibo') return window.open('paginas/imprimir_recibo_livros.php?ids=' + ids.join(','), '_blank');

        // Termo Interno
        let acaoBanco = true;
        if(jaEntregues.length > 0) {
            if(!confirm(`Alguns itens JÁ FORAM ENTREGUES (${jaEntregues[0]}).\nDeseja reimprimir sem alterar a data?`)) return;
            acaoBanco = false;
        } else {
            if(!confirm('Gerar termo e marcar como ENTREGUE AO INTERNO?')) return;
        }

        if(acaoBanco) {
            const fd = new FormData(); fd.append('acao','marcar_entregue'); fd.append('ids_recebimento',ids.join(','));
            const res = await fetch('paginas/internos_recebimento_livros.php', {method:'POST', body: fd});
            const json = await res.json();
            if(json.status !== 'success') return alert(json.msg);
        }
        window.open('paginas/imprimir_termo_livros.php?ids=' + ids.join(','), '_blank');
        if(acaoBanco) window.safeReloadWithFilters();
    }

    window.excluirRecebimento = async function(id) {
        if(!confirm('Excluir este registro permanentemente?')) return;
        const fd = new FormData(); fd.append('acao','excluir'); fd.append('id',id);
        const res = await fetch('paginas/internos_recebimento_livros.php', {method:'POST', body: fd});
        const json = await res.json();
        alert(json.msg);
        if(json.status === 'success') window.safeReloadWithFilters();
    }
</script>
