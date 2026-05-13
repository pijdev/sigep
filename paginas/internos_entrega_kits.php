<?php
// paginas/internos_entrega_kits.php
$config = require __DIR__ . '/../conf/db.php';
date_default_timezone_set('America/Sao_Paulo');

try {
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['user'], $config['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Erro DB");
}

// --- AJAX ---
if (isset($_REQUEST['acao'])) {

    // Listar tipos de termos
    if ($_REQUEST['acao'] === 'listar_tipos') {
        ob_clean();
        header('Content-Type: application/json');
        try {
            $stmt = $pdo->query("SELECT * FROM internos_termo_kit_tipos WHERE ativo = 'S' ORDER BY nome");
            echo json_encode(['status' => 'success', 'dados' => $stmt->fetchAll()]);
        } catch (Exception $e) {
            // Verificar se é erro de tabela não existente
            if (strpos($e->getMessage(), "doesn't exist") !== false || strpos($e->getMessage(), "Base table or view not found") !== false) {
                echo json_encode(['status' => 'error', 'msg' => 'Tabela não encontrada', 'table_missing' => true]);
            } else {
                echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
            }
        }
        exit;
    }

    // Adicionar tipo de termo
    if ($_REQUEST['acao'] === 'adicionar_tipo') {
        ob_clean();
        header('Content-Type: application/json');
        try {
            $nome = trim($_POST['nome']);
            $descricao = trim($_POST['descricao']);

            if (empty($nome)) throw new Exception("Nome é obrigatório.");

            $stmt = $pdo->prepare("INSERT INTO internos_termo_kit_tipos (nome, descricao) VALUES (?, ?)");
            $stmt->execute([$nome, $descricao]);

            echo json_encode(['status' => 'success', 'msg' => 'Tipo adicionado com sucesso!']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
        }
        exit;
    }

    // Editar tipo de termo
    if ($_REQUEST['acao'] === 'editar_tipo') {
        ob_clean();
        header('Content-Type: application/json');
        try {
            $id = $_POST['id'];
            $nome = trim($_POST['nome']);
            $descricao = trim($_POST['descricao']);

            if (empty($nome)) throw new Exception("Nome é obrigatório.");

            $stmt = $pdo->prepare("UPDATE internos_termo_kit_tipos SET nome = ?, descricao = ? WHERE id = ?");
            $stmt->execute([$nome, $descricao, $id]);

            echo json_encode(['status' => 'success', 'msg' => 'Tipo atualizado com sucesso!']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
        }
        exit;
    }

    // Excluir tipo de termo
    if ($_REQUEST['acao'] === 'excluir_tipo') {
        ob_clean();
        header('Content-Type: application/json');
        try {
            $id = $_POST['id'];

            $stmt = $pdo->prepare("UPDATE internos_termo_kit_tipos SET ativo = 'N' WHERE id = ?");
            $stmt->execute([$id]);

            echo json_encode(['status' => 'success', 'msg' => 'Tipo excluído com sucesso!']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
        }
        exit;
    }

    // Gerar termo
    if ($_REQUEST['acao'] === 'gerar_termo') {
        ob_clean();
        header('Content-Type: application/json');
        try {
            $internos = json_decode($_POST['internos'], true);
            $tipo_termo_id = $_POST['tipo_termo_id'];
            $data_assinatura = $_POST['data_assinatura'];

            if (empty($internos)) throw new Exception("Nenhum interno selecionado.");

            // Tentar buscar dados do tipo de termo no banco
            $tipo_nome = '';
            try {
                $stmtTipo = $pdo->prepare("SELECT nome FROM internos_termo_kit_tipos WHERE id = ?");
                $stmtTipo->execute([$tipo_termo_id]);
                $tipo = $stmtTipo->fetch();

                if ($tipo) {
                    $tipo_nome = $tipo['nome'];
                }
            } catch (Exception $e) {
                // Se tabela não existir, usar o nome enviado como fallback
                $tipo_nome = $_POST['tipo_nome'] ?? 'Termo de Entrega';
            }

            // Se ainda não tiver nome, usar fallback
            if (empty($tipo_nome)) {
                $tipo_nome = $_POST['tipo_nome'] ?? 'Termo de Entrega';
            }

            // Gerar ID único para o termo
            $id_termo = uniqid('termo_');

            // Tentar salvar no log de termos gerados (se tabela existir)
            $table_missing = false;
            try {
                $stmtLog = $pdo->prepare("INSERT INTO internos_termos_log (id_termo, tipo_id, tipo_nome, data_assinatura, internos_json, filtros_usados, data_cadastro) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                $stmtLog->execute([
                    $id_termo,
                    $tipo_termo_id,
                    $tipo_nome,
                    $data_assinatura,
                    json_encode($internos),
                    $_POST['filtros'] ?? '',
                    date('Y-m-d H:i:s')
                ]);
            } catch (Exception $e) {
                // Se tabela não existir, apenas continua sem salvar o log
                $table_missing = true;
                error_log("Tabela internos_termos_log não encontrada: " . $e->getMessage());
            }

            echo json_encode(['status' => 'success', 'id_termo' => $id_termo, 'tipo_nome' => $tipo_nome, 'table_missing' => $table_missing]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
        }
        exit;
    }
}

// --- DADOS PARA A PÁGINA ---

// Filtros
$f_galeria = $_GET['galeria'] ?? '';
$f_bloco = $_GET['bloco'] ?? '';
$f_cela = $_GET['cela'] ?? '';
$f_busca = $_GET['busca'] ?? '';
$f_order = $_GET['order'] ?? 'galeria,bloco,res,nome';
$f_mostrar_inativos = $_GET['mostrar_inativos'] ?? 'N';

// Montar SQL
$where = [];
$params = [];

if (!empty($f_galeria)) {
    $where[] = "i.galeria = ?";
    $params[] = $f_galeria;
}
if (!empty($f_bloco)) {
    $where[] = "i.bloco = ?";
    $params[] = $f_bloco;
}
if (!empty($f_cela)) {
    $where[] = "i.res = ?";
    $params[] = $f_cela;
}
if (!empty($f_busca)) {
    $where[] = "(i.nome LIKE ? OR i.nome_social LIKE ? OR i.ipen LIKE ?)";
    $params[] = "%$f_busca%";
    $params[] = "%$f_busca%";
    $params[] = "%$f_busca%";
}

// Montar ordenação
$order_map = [
    'galeria,bloco,res,nome' => 'i.galeria, i.bloco, i.res, i.nome',
    'nome' => 'i.nome',
    'ipen' => 'i.ipen',
    'galeria' => 'i.galeria, i.bloco, i.res'
];
$order_sql = $order_map[$f_order] ?? 'i.galeria, i.bloco, i.res, i.nome';

// Adicionar filtro de status se necessário
if ($f_mostrar_inativos !== 'S') {
    $where[] = "i.status = 'A'";
}

$sql = "SELECT i.ipen, i.nome, i.nome_social, i.galeria, i.bloco, i.res, i.status, i.situacao
        FROM internos i" .
    (!empty($where) ? "\n        WHERE " . implode(" AND ", $where) : "") . "
        ORDER BY $order_sql";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$internos = $stmt->fetchAll();

// Helpers
$galerias = $pdo->query("SELECT DISTINCT galeria FROM internos WHERE status = 'A' ORDER BY galeria")->fetchAll(PDO::FETCH_COLUMN);
$blocos = $pdo->query("SELECT DISTINCT bloco FROM internos WHERE status = 'A' ORDER BY bloco")->fetchAll(PDO::FETCH_COLUMN);
?>

<script>
    window.pageTitle = 'Entrega de Kits';
    window.currentPage = 'internos_entrega_kits.php';
    window.safeReload = function() {
        console.log('safeReload called');
        const formData = $('#formFiltro').serialize();
        console.log('Form data:', formData);

        if (typeof loadPage === 'function') {
            loadPage('paginas/internos_entrega_kits.php?' + formData);
        } else {
            console.error('loadPage function not found');
            // Fallback: redirecionamento normal
            window.location.href = 'paginas/internos_entrega_kits.php?' + formData;
        }
    }

    // Adicionar listener para Enter nos campos do formulário
    $(document).ready(function() {
        $('#formFiltro input, #formFiltro select').on('keypress', function(e) {
            if (e.which === 13) { // Enter key
                e.preventDefault();
                console.log('Enter pressed, calling safeReload');
                safeReload();
            }
        });
    });
</script>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">

        <!-- BOTÕES AÇÃO -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-info" onclick="abrirModalTipos()">
                        <i class="fas fa-cog"></i> Gerenciar Tipos de Termo
                    </button>
                    <button type="button" class="btn btn-success" onclick="selecionarTodos()">
                        <i class="fas fa-check-square"></i> Selecionar Todos
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="limparSelecao()">
                        <i class="fas fa-square"></i> Limpar Seleção
                    </button>
                </div>
            </div>
        </div>

        <!-- FILTROS -->
        <div class="card card-dark">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-search"></i> Filtros de Busca
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <form class="row g-3" id="formFiltro">
                    <div class="col-md-3">
                        <label class="form-label">Nome ou IPEN</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">
                                    <i class="fas fa-search"></i>
                                </span>
                            </div>
                            <input type="text" class="form-control" name="busca" placeholder="Buscar..." value="<?= htmlspecialchars($f_busca) ?>">
                        </div>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Galeria</label>
                        <select class="form-control" name="galeria">
                            <option value="">Todas</option>
                            <?php foreach ($galerias as $g): ?>
                                <option value="<?= $g ?>" <?= ($f_galeria == $g) ? 'selected' : '' ?>><?= $g ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Bloco</label>
                        <select class="form-control" name="bloco">
                            <option value="">Todos</option>
                            <?php foreach ($blocos as $b): ?>
                                <option value="<?= $b ?>" <?= ($f_bloco == $b) ? 'selected' : '' ?>><?= $b ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Cela</label>
                        <input type="text" class="form-control" name="cela" placeholder="Nº" value="<?= htmlspecialchars($f_cela) ?>">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Ordenar por</label>
                        <select class="form-control" name="order">
                            <option value="galeria,bloco,res,nome" <?= ($f_order == 'galeria,bloco,res,nome') ? 'selected' : '' ?>>Local</option>
                            <option value="nome" <?= ($f_order == 'nome') ? 'selected' : '' ?>>Nome</option>
                            <option value="ipen" <?= ($f_order == 'ipen') ? 'selected' : '' ?>>IPEN</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">
                            <i class="fas fa-eye"></i> Mostrar Inativos
                        </label>
                        <div class="form-check mt-2">
                            <input type="checkbox" class="form-check-input" name="mostrar_inativos" value="S"
                                <?= ($f_mostrar_inativos === 'S') ? 'checked' : '' ?>
                                onchange="window.safeReload()">
                            <label class="form-check-label" for="mostrar_inativos">
                                Incluir internos inativos
                            </label>
                        </div>
                    </div>

                    <div class="col-md-1">
                        <label class="form-label">&nbsp;</label>
                        <button type="button" class="btn btn-primary btn-block" onclick="window.safeReload()">
                            <i class="fas fa-filter"></i> Filtrar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- SELEÇÃO DE TIPO E GERAÇÃO -->
        <div class="card card-success">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-file-contract"></i> Configuração do Termo
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-list-alt"></i> Tipo de Termo
                            </label>
                            <select class="form-control" id="tipoTermo">
                                <option value="">Carregando...</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-calendar"></i> Data Assinatura
                            </label>
                            <input type="date" class="form-control" id="dataAssinatura" value="<?= date('Y-m-d') ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-users"></i> Selecionados
                                <span class="badge badge-primary ml-2" id="contadorSelecionados">0</span>
                            </label>
                            <button type="button" class="btn btn-success btn-block" onclick="gerarTermo()" id="btnGerar" disabled>
                                <i class="fas fa-print"></i> Gerar Termo
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TABELA DE INTERNOS -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-users"></i> Lista de Internos
                </h3>
                <div class="card-tools">
                    <span class="badge badge-info mr-2"><?= count($internos) ?> registros</span>
                </div>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover table-striped table-head-fixed">
                    <thead>
                        <tr>
                            <th width="40">
                                <div class="icheck-primary d-inline">
                                    <input type="checkbox" id="selectAll" onchange="toggleAll()">
                                    <label for="selectAll"></label>
                                </div>
                            </th>
                            <th width="80">IPEN</th>
                            <th>Nome Completo</th>
                            <th width="120">Localização</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($internos as $interno): ?>
                            <tr>
                                <td>
                                    <div class="icheck-primary d-inline">
                                        <input type="checkbox" class="chk-interno" value="<?= $interno['ipen'] ?>"
                                            data-nome="<?= htmlspecialchars($interno['nome_social'] ?: $interno['nome']) ?>"
                                            data-local="<?= $interno['galeria'] . $interno['bloco'] . '-' . $interno['res'] ?>"
                                            data-situacao="<?= htmlspecialchars($interno['situacao'] ?? '') ?>"
                                            id="interno_<?= $interno['ipen'] ?>">
                                        <label for="interno_<?= $interno['ipen'] ?>"></label>
                                    </div>
                                </td>
                                <td class="font-weight-bold"><?= $interno['ipen'] ?></td>
                                <td>
                                    <?= htmlspecialchars($interno['nome_social'] ?: $interno['nome']) ?>
                                    <?php if ($interno['status'] === 'I'): ?>
                                        <span class="badge badge-warning ml-2" title="Inativo">
                                            <i class="fas fa-user-slash"></i> Inativo
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-info"><?= $interno['galeria'] . $interno['bloco'] . '-' . $interno['res'] ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($internos)): ?>
                            <tr>
                                <td colspan="4" class="text-center p-4 text-muted">
                                    <i class="fas fa-search fa-2x mb-2"></i>
                                    <p class="mb-0">Nenhum interno encontrado com os filtros aplicados.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<!-- MODAL TIPOS DE TERMO -->
<div class="modal fade" id="modalTipos" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-cog"></i> Gerenciar Tipos de Termo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <button class="btn btn-primary btn-sm" onclick="adicionarTipo()">
                        <i class="fas fa-plus"></i> Adicionar Novo Tipo
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Descrição</th>
                                <th width="100">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="listaTipos">
                            <tr>
                                <td colspan="3" class="text-center">Carregando...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Carregar tipos de termo ao iniciar
    $(document).ready(function() {
        carregarTipos();
        atualizarContador();

        // Atualizar contador quando marcar/desmarcar
        $('.chk-interno').change(function() {
            atualizarContador();
        });
    });

    function carregarTipos() {
        $.post('paginas/internos_entrega_kits.php', {
            acao: 'listar_tipos'
        }, function(res) {
            if (res.status === 'success') {
                // Preencher select principal
                let html = '<option value="">Selecione um tipo...</option>';
                res.dados.forEach(tipo => {
                    html += `<option value="${tipo.id}">${tipo.nome}</option>`;
                });
                $('#tipoTermo').html(html);

                // Preencher tabela do modal
                html = '';
                res.dados.forEach(tipo => {
                    html += `
                    <tr>
                        <td>${tipo.nome}</td>
                        <td>${tipo.descricao || '-'}</td>
                        <td>
                            <button class="btn btn-xs btn-warning" onclick="editarTipo(${tipo.id}, '${tipo.nome}', '${tipo.descricao || ''}')">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-xs btn-danger" onclick="excluirTipo(${tipo.id})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
                });
                $('#listaTipos').html(html);
            } else {
                // Tratar erro - tabela pode não existir
                console.error('Erro ao carregar tipos:', res.msg);

                // Verificar se é erro de tabela não encontrada
                if (res.table_missing) {
                    // Preencher com opções padrão quando tabela não existe
                    const tiposPadrao = [{
                            id: 'kit_higiene',
                            nome: 'Kit de Higiene'
                        },
                        {
                            id: 'roupas',
                            nome: 'Roupas'
                        },
                        {
                            id: 'peculio',
                            nome: 'Pecúlio'
                        },
                        {
                            id: 'kit_alimentacao',
                            nome: 'Kit Alimentação'
                        },
                        {
                            id: 'material_limpeza',
                            nome: 'Material de Limpeza'
                        },
                        {
                            id: 'itens_diversos',
                            nome: 'Itens Diversos'
                        }
                    ];

                    let html = '<option value="">Selecione um tipo...</option>';
                    tiposPadrao.forEach(tipo => {
                        html += `<option value="${tipo.id}">${tipo.nome}</option>`;
                    });
                    $('#tipoTermo').html(html);

                    $('#listaTipos').html('<tr><td colspan="3" class="text-center text-warning">Execute o SQL para criar a tabela de tipos personalizados. Usando tipos padrão.</td></tr>');
                } else {
                    // Outro tipo de erro
                    $('#tipoTermo').html('<option value="">Erro ao carregar tipos</option>');
                    $('#listaTipos').html('<tr><td colspan="3" class="text-center text-danger">Erro: ' + res.msg + '</td></tr>');
                }
            }
        }).fail(function(xhr, status, error) {
            console.error('Falha na requisição:', error);

            // Fallback para tipos padrão
            const tiposPadrao = [{
                    id: 'kit_higiene',
                    nome: 'Kit de Higiene'
                },
                {
                    id: 'roupas',
                    nome: 'Roupas'
                },
                {
                    id: 'peculio',
                    nome: 'Pecúlio'
                },
                {
                    id: 'kit_alimentacao',
                    nome: 'Kit Alimentação'
                },
                {
                    id: 'material_limpeza',
                    nome: 'Material de Limpeza'
                },
                {
                    id: 'itens_diversos',
                    nome: 'Itens Diversos'
                }
            ];

            let html = '<option value="">Selecione um tipo...</option>';
            tiposPadrao.forEach(tipo => {
                html += `<option value="${tipo.id}">${tipo.nome}</option>`;
            });
            $('#tipoTermo').html(html);

            $('#listaTipos').html('<tr><td colspan="3" class="text-center text-danger">Erro de conexão. Usando tipos padrão.</td></tr>');
        });
    }

    function abrirModalTipos() {
        $('#modalTipos').modal('show');
    }

    function adicionarTipo() {
        const nome = prompt('Nome do tipo de termo:');
        if (!nome) return;

        const descricao = prompt('Descrição (opcional):');

        $.post('paginas/internos_entrega_kits.php', {
            acao: 'adicionar_tipo',
            nome: nome,
            descricao: descricao
        }, function(res) {
            if (res.status === 'success') {
                alert(res.msg);
                carregarTipos();
            } else {
                alert('Erro: ' + res.msg);
            }
        }, 'json');
    }

    function editarTipo(id, nomeAtual, descAtual) {
        const nome = prompt('Nome do tipo de termo:', nomeAtual);
        if (!nome) return;

        const descricao = prompt('Descrição:', descAtual);

        $.post('paginas/internos_entrega_kits.php', {
            acao: 'editar_tipo',
            id: id,
            nome: nome,
            descricao: descricao
        }, function(res) {
            if (res.status === 'success') {
                alert(res.msg);
                carregarTipos();
            } else {
                alert('Erro: ' + res.msg);
            }
        }, 'json');
    }

    function excluirTipo(id) {
        if (!confirm('Tem certeza que deseja excluir este tipo?')) return;

        $.post('paginas/internos_entrega_kits.php', {
            acao: 'excluir_tipo',
            id: id
        }, function(res) {
            if (res.status === 'success') {
                alert(res.msg);
                carregarTipos();
            } else {
                alert('Erro: ' + res.msg);
            }
        }, 'json');
    }

    function toggleAll() {
        const checked = $('#selectAll').is(':checked');
        $('.chk-interno').prop('checked', checked);
        atualizarContador();
    }

    function selecionarTodos() {
        $('.chk-interno').prop('checked', true);
        $('#selectAll').prop('checked', true);
        atualizarContador();
    }

    function limparSelecao() {
        $('.chk-interno').prop('checked', false);
        $('#selectAll').prop('checked', false);
        atualizarContador();
    }

    function atualizarContador() {
        const selecionados = $('.chk-interno:checked').length;
        $('#contadorSelecionados').text(selecionados);
        $('#btnGerar').prop('disabled', selecionados === 0 || $('#tipoTermo').val() === '');
    }

    // Habilitar botão quando selecionar tipo
    $('#tipoTermo').change(function() {
        atualizarContador();
    });

    function gerarTermo() {
        const selecionados = $('.chk-interno:checked');
        const tipoId = $('#tipoTermo').val();
        const dataAssinatura = $('#dataAssinatura').val();

        if (selecionados.length === 0) {
            alert('Selecione pelo menos um interno.');
            return;
        }

        if (!tipoId) {
            alert('Selecione o tipo de termo.');
            return;
        }

        if (!dataAssinatura) {
            alert('Informe a data de assinatura.');
            return;
        }

        // Coletar dados dos internos selecionados
        const internos = [];
        selecionados.each(function() {
            internos.push({
                ipen: $(this).val(),
                nome: $(this).data('nome'),
                local: $(this).data('local'),
                situacao: $(this).data('situacao') || ''
            });
        });

        // Coletar filtros atuais
        const filtros = $('#formFiltro').serialize();

        // Obter nome do tipo selecionado
        const tipoNome = $('#tipoTermo option:selected').text();

        $.post('paginas/internos_entrega_kits.php', {
            acao: 'gerar_termo',
            internos: JSON.stringify(internos),
            tipo_termo_id: tipoId,
            data_assinatura: dataAssinatura,
            filtros: filtros,
            tipo_nome: tipoNome // Enviar nome do tipo como fallback
        }, function(res) {
            if (res.status === 'success') {
                // Abrir impressão em nova janela
                // Se a tabela não existe, usar versão simplificada
                const urlImpressao = res.table_missing ?
                    `paginas/internos_entrega_kits_impressao_simples.php?data=${dataAssinatura}&tipo=${encodeURIComponent(res.tipo_nome)}` :
                    `paginas/internos_entrega_kits_impressao.php?id=${res.id_termo}&data=${dataAssinatura}&tipo=${encodeURIComponent(res.tipo_nome)}`;

                // Se for versão simplificada, enviar dados via POST
                if (res.table_missing) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = urlImpressao;
                    form.target = '_blank';

                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'internos';
                    input.value = JSON.stringify(internos);
                    form.appendChild(input);

                    document.body.appendChild(form);
                    form.submit();
                    document.body.removeChild(form);
                } else {
                    window.open(urlImpressao, '_blank');
                }

                // Limpar seleção após gerar
                limparSelecao();
            } else {
                alert('Erro: ' + res.msg);
            }
        }, 'json');
    }
</script>
