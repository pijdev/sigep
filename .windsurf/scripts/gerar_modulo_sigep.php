<?php
/**
 * Gerar Módulo SIGEP
 * 
 * Gera estrutura completa de módulo SIGEP
 * Cria view, controller, assets
 * Segue padrões MVC existentes
 * 
 * @category Cascade Scripts
 * @package Geração
 * @author Cascade AI
 * @version 1.0
 * @since 2024-03-25
 * 
 * Uso: php gerar_modulo_sigep.php [setor] [nome_modulo] [opcoes]
 * Opções:
 *   --com-banco: Gera tabela no banco
 *   --com-menu: Adiciona entrada no menu
 *   --forcar: Sobrescreve arquivos existentes
 */

set_time_limit(0);
ini_set('memory_limit', '512M');
date_default_timezone_set('America/Sao_Paulo');

// Argumentos da linha de comando
$options = getopt('', ['com-banco', 'com-menu', 'forcar']);
$comBanco = isset($options['com-banco']);
$comMenu = isset($options['com-menu']);
$forcar = isset($options['forcar']);

// Configuração
$setor = $argv[1] ?? null;
$nomeModulo = $argv[2] ?? null;
$caminhoProjeto = getcwd();

// Funções utilitárias
function logMessage($message, $level = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    echo "[$timestamp] [$level] $message\n";
}

function validarParametros($setor, $nomeModulo) {
    if (empty($setor)) {
        logMessage("ERRO: Setor não informado", 'ERROR');
        return false;
    }
    
    if (empty($nomeModulo)) {
        logMessage("ERRO: Nome do módulo não informado", 'ERROR');
        return false;
    }
    
    // Validar nomes
    if (!preg_match('/^[a-z]+$/', $setor)) {
        logMessage("ERRO: Setor deve conter apenas letras minúsculas", 'ERROR');
        return false;
    }
    
    if (!preg_match('/^[a-z_]+$/', $nomeModulo)) {
        logMessage("ERRO: Nome do módulo deve conter apenas letras minúsculas e underscores", 'ERROR');
        return false;
    }
    
    return true;
}

function criarDiretorio($caminho) {
    if (!is_dir($caminho)) {
        if (!mkdir($caminho, 0755, true)) {
            logMessage("ERRO: Não foi possível criar diretório: $caminho", 'ERROR');
            return false;
        }
        logMessage("Diretório criado: $caminho");
    }
    return true;
}

function criarArquivo($caminho, $conteudo, $forcar = false) {
    if (file_exists($caminho) && !$forcar) {
        logMessage("AVISO: Arquivo já existe (use --forcar para sobrescrever): $caminho", 'WARNING');
        return false;
    }
    
    if (file_put_contents($caminho, $conteudo)) {
        logMessage("Arquivo criado: $caminho");
        return true;
    }
    
    logMessage("ERRO: Não foi possível criar arquivo: $caminho", 'ERROR');
    return false;
}

function formatarNomeClasse($nomeModulo) {
    return ucwords(str_replace('_', ' ', $nomeModulo));
}

function gerarController($setor, $nomeModulo) {
    $nomeClasse = formatarNomeClasse($nomeModulo);
    
    return <<<PHP
<?php
/**
 * $nomeClasse - Controller SIGEP
 * 
 * Controller para o módulo $nomeModulo no setor $setor
 * Gerado automaticamente em {date('d/m/Y H:i:s')}
 */

session_start();
require_once __DIR__ . '/../../../conf/db.php';

// Headers para resposta JSON
header('Content-Type: application/json; charset=utf-8');

// Função para retornar erro JSON
function returnError(\$message, \$code = 500) {
    http_response_code(\$code);
    echo json_encode(['error' => \$message, 'code' => \$code], JSON_UNESCAPED_UNICODE);
    exit;
}

// Verificar se usuário está autenticado
if (!isset(\$_SESSION['user_id']) || !isset(\$_SESSION['user_nome'])) {
    returnError('Usuário não autenticado', 401);
}

// Verificar permissão específica
if (!(\$_SESSION['user_admin'] || (\$_SESSION['perm_$setor'] ?? 0))) {
    returnError('Sem permissão para acessar este módulo', 403);
}

// Configurar conexão PDO
try {
    \$config = require __DIR__ . '/../../../conf/db.php';
    \$dsn = "mysql:host={\$config['host']};dbname={\$config['dbname']};charset={\$config['charset']}";
    \$pdo = new PDO(\$dsn, \$config['user'], \$config['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    \$pdo->exec("SET time_zone = '-03:00'");
} catch (PDOException \$e) {
    returnError('Erro na conexão com banco de dados: ' . \$e->getMessage(), 500);
}

// Funções CRUD
function criarItem(\$pdo, \$dados) {
    try {
        \$stmt = \$pdo->prepare("INSERT INTO {$setor}_{$nomeModulo} (campo1, campo2, created_at) VALUES (?, ?, NOW())");
        \$stmt->execute([\$dados['campo1'], \$dados['campo2']]);
        return \$pdo->lastInsertId();
    } catch (PDOException \$e) {
        throw new Exception('Erro ao criar item: ' . \$e->getMessage());
    }
}

function listarItens(\$pdo, \$limit = 20, \$offset = 0) {
    try {
        \$stmt = \$pdo->prepare("SELECT * FROM {$setor}_{$nomeModulo} ORDER BY created_at DESC LIMIT ? OFFSET ?");
        \$stmt->execute([\$limit, \$offset]);
        return \$stmt->fetchAll();
    } catch (PDOException \$e) {
        throw new Exception('Erro ao listar itens: ' . \$e->getMessage());
    }
}

function obterItem(\$pdo, \$id) {
    try {
        \$stmt = \$pdo->prepare("SELECT * FROM {$setor}_{$nomeModulo} WHERE id = ?");
        \$stmt->execute([\$id]);
        return \$stmt->fetch();
    } catch (PDOException \$e) {
        throw new Exception('Erro ao obter item: ' . \$e->getMessage());
    }
}

function atualizarItem(\$pdo, \$id, \$dados) {
    try {
        \$stmt = \$pdo->prepare("UPDATE {$setor}_{$nomeModulo} SET campo1 = ?, campo2 = ?, updated_at = NOW() WHERE id = ?");
        \$stmt->execute([\$dados['campo1'], \$dados['campo2'], \$id]);
        return \$stmt->rowCount();
    } catch (PDOException \$e) {
        throw new Exception('Erro ao atualizar item: ' . \$e->getMessage());
    }
}

function excluirItem(\$pdo, \$id) {
    try {
        \$stmt = \$pdo->prepare("DELETE FROM {$setor}_{$nomeModulo} WHERE id = ?");
        \$stmt->execute([\$id]);
        return \$stmt->rowCount();
    } catch (PDOException \$e) {
        throw new Exception('Erro ao excluir item: ' . \$e->getMessage());
    }
}

function contarItens(\$pdo) {
    try {
        \$stmt = \$pdo->prepare("SELECT COUNT(*) as total FROM {$setor}_{$nomeModulo}");
        \$stmt->execute();
        \$result = \$stmt->fetch();
        return \$result['total'];
    } catch (PDOException \$e) {
        throw new Exception('Erro ao contar itens: ' . \$e->getMessage());
    }
}

// Processar requisições AJAX
if (\$_SERVER['REQUEST_METHOD'] === 'POST' && isset(\$_POST['action'])) {

    ob_clean();

    try {
        switch (\$_POST['action']) {
            case 'listar':
                \$limit = \$_POST['limit'] ?? 20;
                \$page = \$_POST['page'] ?? 1;
                \$offset = (\$page - 1) * \$limit;
                
                \$itens = listarItens(\$pdo, \$limit, \$offset);
                \$total = contarItens(\$pdo);
                \$totalPaginas = ceil(\$total / \$limit);
                
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'dados' => \$itens,
                        'total' => \$total,
                        'pagina' => \$page,
                        'total_paginas' => \$totalPaginas
                    ]
                ], JSON_UNESCAPED_UNICODE);
                break;

            case 'criar':
                \$item_id = criarItem(\$pdo, \$_POST);
                echo json_encode(['success' => true, 'data' => ['id' => \$item_id]], JSON_UNESCAPED_UNICODE);
                break;

            case 'obter':
                \$id = \$_POST['id'];
                \$item = obterItem(\$pdo, \$id);
                if (\$item) {
                    echo json_encode(['success' => true, 'data' => \$item], JSON_UNESCAPED_UNICODE);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Item não encontrado'], JSON_UNESCAPED_UNICODE);
                }
                break;

            case 'atualizar':
                \$id = \$_POST['id'];
                \$rows = atualizarItem(\$pdo, \$id, \$_POST);
                echo json_encode(['success' => true, 'data' => ['rows_affected' => \$rows]], JSON_UNESCAPED_UNICODE);
                break;

            case 'excluir':
                \$id = \$_POST['id'];
                \$rows = excluirItem(\$pdo, \$id);
                echo json_encode(['success' => true, 'data' => ['rows_affected' => \$rows]], JSON_UNESCAPED_UNICODE);
                break;

            default:
                throw new Exception('Ação não reconhecida');
        }

    } catch (Exception \$e) {
        echo json_encode(['success' => false, 'message' => \$e->getMessage()], JSON_UNESCAPED_UNICODE);
    }

    exit;
}

// Carregar dados para a view
try {
    \$itens = listarItens(\$pdo, 5); // Limitar para view inicial
} catch (Exception \$e) {
    \$itens = [];
}
?>
PHP;
}

function gerarView($setor, $nomeModulo) {
    $nomeClasse = formatarNomeClasse($nomeModulo);
    
    return <<<HTML
<?php
require_once __DIR__ . '/{$nomeModulo}_logica.php';
?>

<!-- Content Header -->
<div class="row mb-2">
    <div class="col-sm-6">
        <h1 class="m-0">$nomeClasse</h1>
    </div>
    <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="/">Home</a></li>
            <li class="breadcrumb-item"><a href="/modulos/$setor/">$setor</a></li>
            <li class="breadcrumb-item active">$nomeClasse</li>
        </ol>
    </div>
</div>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">

        <!-- Cards Resumo -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info pointer">
                    <div class="inner">
                        <h3 id="stats-total">0</h3>
                        <p>Total</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-database"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success pointer">
                    <div class="inner">
                        <h3 id="stats-ativos">0</h3>
                        <p>Ativos</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-check"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning pointer">
                    <div class="inner">
                        <h3 id="stats-pendentes">0</h3>
                        <p>Pendentes</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger pointer">
                    <div class="inner">
                        <h3 id="stats-inativos">0</h3>
                        <p>Inativos</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-times"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card Principal -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-cog mr-2"></i>
                            Gerenciamento de $nomeClasse
                        </h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool btn-sm" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                            <button type="button" class="btn btn-tool btn-sm" data-card-widget="maximize">
                                <i class="fas fa-expand"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        
                        <!-- Formulário -->
                        <div class="row mb-3">
                            <div class="col-12">
                                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalForm">
                                    <i class="fas fa-plus mr-2"></i>Novo Item
                                </button>
                                <button type="button" class="btn btn-secondary" onclick="location.reload()">
                                    <i class="fas fa-sync mr-2"></i>Atualizar
                                </button>
                            </div>
                        </div>

                        <!-- Tabela -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Campo 1</th>
                                        <th>Campo 2</th>
                                        <th>Status</th>
                                        <th>Criado em</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody id="tabela-dados">
                                    <?php if (empty(\$itens)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted">
                                                <i class="fas fa-info-circle mr-2"></i>Nenhum item encontrado.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach (\$itens as \$item): ?>
                                            <tr>
                                                <td><?= \$item['id'] ?></td>
                                                <td><?= htmlspecialchars(\$item['campo1'] ?? '-') ?></td>
                                                <td><?= htmlspecialchars(\$item['campo2'] ?? '-') ?></td>
                                                <td>
                                                    <span class="badge badge-success">Ativo</span>
                                                </td>
                                                <td><?= date('d/m/Y H:i', strtotime(\$item['created_at'])) ?></td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-info" onclick="editarItem(<?= \$item['id'] ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-danger" onclick="excluirItem(<?= \$item['id'] ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Paginação -->
                        <div class="row">
                            <div class="col-sm-12 col-md-5">
                                <div class="dataTables_info">
                                    Mostrando <span id="mostrando-inicio">1</span> a <span id="mostrando-fim">5</span> de <span id="total-registros">0</span> registros
                                </div>
                            </div>
                            <div class="col-sm-12 col-md-7">
                                <div class="dataTables_paginate paging_simple_numbers">
                                    <ul class="pagination">
                                        <li class="paginate_button page-item previous disabled" id="btn-anterior">
                                            <a href="#" aria-controls="example1" data-dt-idx="0" tabindex="0">
                                                <i class="fas fa-chevron-left"></i> Anterior
                                            </a>
                                        </li>
                                        <li class="paginate_button page-item active">
                                            <a href="#" aria-controls="example1" data-dt-idx="1" tabindex="0">1</a>
                                        </li>
                                        <li class="paginate_button page-item next" id="btn-proximo">
                                            <a href="#" aria-controls="example1" data-dt-idx="2" tabindex="0">
                                                Próximo <i class="fas fa-chevron-right"></i>
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

    </div>
</section>

<!-- Modal Formulário -->
<div class="modal fade" id="modalForm" tabindex="-1" aria-labelledby="modalFormLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalFormLabel">Novo Item</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="form-item">
                <div class="modal-body">
                    <input type="hidden" name="id" id="item-id">
                    
                    <div class="form-group">
                        <label for="campo1">Campo 1</label>
                        <input type="text" class="form-control" name="campo1" id="campo1" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="campo2">Campo 2</label>
                        <input type="text" class="form-control" name="campo2" id="campo2" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="modulos/$setor/$nomeModulo/assets/js/{$nomeModulo}.js"></script>
HTML;
}

function gerarJavaScript($setor, $nomeModulo) {
    return <<<JS
/**
 * SIGEP $nomeModulo - JavaScript Principal
 * Funcionalidades AJAX + UI AdminLTE
 * Gerado automaticamente em {date('d/m/Y H:i:s')}
 */

// Proteger contra múltiplos carregamentos no SPA
if (typeof window.{$nomeModulo}Loaded === 'undefined') {
    window.{$nomeModulo}Loaded = true;

// Variáveis globais
var currentData = {
    itens: [],
    pagina: 1,
    totalPaginas: 1,
    totalRegistros: 0
};

// Inicialização quando documento estiver pronto
\$(document).ready(function() {
    carregarDados();
    inicializarComponentes();
    setInterval(autoRefresh, 30000);
});

// Carregar dados via AJAX
function carregarDados() {
    \$.ajax({
        url: 'modulos/$setor/$nomeModulo/{$nomeModulo}_logica.php',
        method: 'POST',
        data: { 
            action: 'listar',
            page: currentData.pagina,
            limit: 20
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                currentData.itens = response.data.dados;
                currentData.totalPaginas = response.data.total_paginas;
                currentData.totalRegistros = response.data.total;
                atualizarInterface();
                atualizarPaginacao();
            } else {
                mostrarNotificacao('Erro ao carregar dados: ' + response.message, 'error');
            }
        },
        error: function(xhr, status, error) {
            console.error('Erro ao carregar dados:', error);
            mostrarNotificacao('Falha na comunicação', 'error');
        }
    });
}

// Inicializar componentes
function inicializarComponentes() {
    // Formulário
    \$('#form-item').on('submit', function(e) {
        e.preventDefault();
        salvarItem();
    });
    
    // Modal
    \$('#modalForm').on('show.bs.modal', function(e) {
        var button = \$(e.relatedTarget);
        var id = button.data('id');
        
        if (id) {
            // Editar
            \$('#modalFormLabel').text('Editar Item');
            carregarItem(id);
        } else {
            // Novo
            \$('#modalFormLabel').text('Novo Item');
            \$('#form-item')[0].reset();
            \$('#item-id').val('');
        }
    });
    
    // Botões de paginação
    \$('#btn-anterior').on('click', function() {
        if (currentData.pagina > 1) {
            currentData.pagina--;
            carregarDados();
        }
    });
    
    \$('#btn-proximo').on('click', function() {
        if (currentData.pagina < currentData.totalPaginas) {
            currentData.pagina++;
            carregarDados();
        }
    });
}

// Salvar item
function salvarItem() {
    var dados = {
        action: \$('#item-id').val() ? 'atualizar' : 'criar',
        id: \$('#item-id').val(),
        campo1: \$('#campo1').val(),
        campo2: \$('#campo2').val()
    };
    
    \$.ajax({
        url: 'modulos/$setor/$nomeModulo/{$nomeModulo}_logica.php',
        method: 'POST',
        data: dados,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                mostrarNotificacao('Item salvo com sucesso', 'success');
                \$('#modalForm').modal('hide');
                carregarDados();
            } else {
                mostrarNotificacao('Erro: ' + response.message, 'error');
            }
        },
        error: function() {
            mostrarNotificacao('Falha na comunicação', 'error');
        }
    });
}

// Carregar item para edição
function carregarItem(id) {
    \$.ajax({
        url: 'modulos/$setor/$nomeModulo/{$nomeModulo}_logica.php',
        method: 'POST',
        data: { action: 'obter', id: id },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                var item = response.data;
                \$('#item-id').val(item.id);
                \$('#campo1').val(item.campo1);
                \$('#campo2').val(item.campo2);
            } else {
                mostrarNotificacao('Erro ao carregar item', 'error');
            }
        },
        error: function() {
            mostrarNotificacao('Falha na comunicação', 'error');
        }
    });
}

// Editar item
function editarItem(id) {
    \$('#modalForm').modal('show');
    \$('#modalForm').data('id', id);
}

// Excluir item
function excluirItem(id) {
    if (confirm('Tem certeza que deseja excluir este item?')) {
        \$.ajax({
            url: 'modulos/$setor/$nomeModulo/{$nomeModulo}_logica.php',
            method: 'POST',
            data: { action: 'excluir', id: id },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    mostrarNotificacao('Item excluído com sucesso', 'success');
                    carregarDados();
                } else {
                    mostrarNotificacao('Erro: ' + response.message, 'error');
                }
            },
            error: function() {
                mostrarNotificacao('Falha na comunicação', 'error');
            }
        });
    }
}

// Atualizar interface
function atualizarInterface() {
    var tbody = \$('#tabela-dados');
    tbody.empty();
    
    if (currentData.itens.length === 0) {
        tbody.html(`
            <tr>
                <td colspan="6" class="text-center text-muted">
                    <i class="fas fa-info-circle mr-2"></i>Nenhum item encontrado.
                </td>
            </tr>
        `);
        return;
    }
    
    currentData.itens.forEach(function(item) {
        var row = `
            <tr>
                <td>\${item.id}</td>
                <td>\${item.campo1 || '-'}</td>
                <td>\${item.campo2 || '-'}</td>
                <td><span class="badge badge-success">Ativo</span></td>
                <td>\${new Date(item.created_at).toLocaleString('pt-BR')}</td>
                <td>
                    <button type="button" class="btn btn-sm btn-info" onclick="editarItem(\${item.id})">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-danger" onclick="excluirItem(\${item.id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        tbody.append(row);
    });
    
    // Atualizar cards de estatísticas
    \$('#stats-total').text(currentData.totalRegistros);
    \$('#stats-ativos').text(currentData.totalRegistros);
    \$('#stats-pendentes').text('0');
    \$('#stats-inativos').text('0');
}

// Atualizar paginação
function atualizarPaginacao() {
    var inicio = ((currentData.pagina - 1) * 20) + 1;
    var fim = Math.min(currentData.pagina * 20, currentData.totalRegistros);
    
    \$('#mostrando-inicio').text(inicio);
    \$('#mostrando-fim').text(fim);
    \$('#total-registros').text(currentData.totalRegistros);
    
    // Atualizar botões
    \$('#btn-anterior').toggleClass('disabled', currentData.pagina <= 1);
    \$('#btn-proximo').toggleClass('disabled', currentData.pagina >= currentData.totalPaginas);
}

// Utilitários
function mostrarNotificacao(mensagem, tipo = 'info') {
    if (typeof toastr !== 'undefined') {
        toastr[tipo](mensagem);
    } else {
        console.log('[' + tipo.toUpperCase() + '] ' + mensagem);
    }
}

function autoRefresh() {
    carregarDados();
}

// Fechar bloco de proteção contra múltiplos carregamentos
}
JS;
}

function gerarCSS($setor, $nomeModulo) {
    return <<<CSS
/**
 * SIGEP $nomeModulo - Estilos Customizados
 * Baseado em AdminLTE 3
 * Gerado automaticamente em {date('d/m/Y H:i:s')}
 */

/* Cards Enhancement */
.small-box {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.small-box:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

/* Loading States */
.loading-spinner {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px;
    color: #6c757d;
}

.loading-spinner i {
    font-size: 2rem;
    margin-bottom: 1rem;
}

/* Empty States */
.empty-state {
    text-align: center;
    padding: 40px;
    color: #6c757d;
}

.empty-state i {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

/* Table Enhancements */
.table th {
    background-color: #f8f9fa;
    border-top: 2px solid #dee2e6;
}

.table-striped tbody tr:nth-of-type(odd) {
    background-color: rgba(0,0,0,.02);
}

/* Button Enhancements */
.btn {
    transition: all 0.2s ease;
}

.btn:hover {
    transform: translateY(-1px);
}

/* Modal Enhancements */
.modal-content {
    border: none;
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
}

.modal-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

/* Responsive Design */
@media (max-width: 768px) {
    .small-box {
        margin-bottom: 1rem;
    }
    
    .table-responsive {
        border: none;
    }
    
    .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }
}

/* Animation Classes */
.fade-in {
    animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Custom Colors for this module */
.module-primary {
    background-color: #007bff;
    border-color: #007bff;
}

.module-success {
    background-color: #28a745;
    border-color: #28a745;
}

.module-warning {
    background-color: #ffc107;
    border-color: #ffc107;
}

.module-danger {
    background-color: #dc3545;
    border-color: #dc3545;
}

/* Status badges */
.badge-active {
    background-color: #28a745;
}

.badge-inactive {
    background-color: #6c757d;
}

.badge-pending {
    background-color: #ffc107;
    color: #212529;
}
CSS;
}

function gerarTabelaBanco($setor, $nomeModulo) {
    return <<<SQL
-- Tabela para o módulo $nomeModulo
-- Gerada automaticamente em {date('Y-m-d H:i:s')}

CREATE TABLE IF NOT EXISTS `{$setor}_{$nomeModulo}` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `campo1` varchar(255) NOT NULL COMMENT 'Campo 1',
    `campo2` varchar(255) DEFAULT NULL COMMENT 'Campo 2',
    `status` enum('ativo','inativo','pendente') NOT NULL DEFAULT 'ativo',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_by` int(11) DEFAULT NULL COMMENT 'ID do usuário que criou',
    `updated_by` int(11) DEFAULT NULL COMMENT 'ID do usuário que atualizou',
    PRIMARY KEY (`id`),
    KEY `idx_status` (`status`),
    KEY `idx_created_at` (`created_at`),
    KEY `idx_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabela do módulo $nomeModulo';

-- Inserir trigger para auditoria se necessário
DELIMITER $$
CREATE TRIGGER IF NOT EXISTS tr_{$setor}_{$nomeModulo}_insert
BEFORE INSERT ON `{$setor}_{$nomeModulo}`
FOR EACH ROW
BEGIN
    SET NEW.created_by = IF(NEW.created_by IS NULL, @current_user_id, NEW.created_by);
END$$
DELIMITER ;
SQL;
}

function atualizarMenu($setor, $nomeModulo) {
    $nomeClasse = formatarNomeClasse($nomeModulo);
    
    return <<<PHP
<?php
/**
 * Atualização do menu para incluir módulo $nomeClasse
 * Adicionar em includes/sidebar_logica.php
 */

// Encontrar a função getMenuConfig() e adicionar no setor $setor:

/*
if (podeVisualizarSetor('perm_$setor')) {
    \$menu['$setor']['items'][] = [
        'title' => '$nomeClasse',
        'icon' => 'fas fa-cog text-info',
        'page' => '/modulos/$setor/$nomeModulo/{$nomeModulo}_view.php',
        'parent' => '$nomeClasse'
    ];
}
*/
?>
PHP;
}

// Classe para geração de módulo
class GeradorModuloSIGEP {
    private $setor;
    private $nomeModulo;
    private $caminhoProjeto;
    private $caminhoModulo;
    
    public function __construct($setor, $nomeModulo, $caminhoProjeto) {
        $this->setor = $setor;
        $this->nomeModulo = $nomeModulo;
        $this->caminhoProjeto = $caminhoProjeto;
        $this->caminhoModulo = $caminhoProjeto . '/modulos/' . $setor . '/' . $nomeModulo;
    }
    
    public function gerar() {
        global $comBanco, $comMenu, $forcar;
        
        logMessage("Gerando módulo {$this->nomeModulo} no setor {$this->setor}...");
        
        // Criar estrutura de diretórios
        $this->criarEstrutura();
        
        // Gerar arquivos
        $this->gerarArquivos();
        
        // Gerar tabela no banco se solicitado
        if ($comBanco) {
            $this->gerarTabelaBanco();
        }
        
        // Gerar atualização de menu se solicitado
        if ($comMenu) {
            $this->gerarAtualizacaoMenu();
        }
        
        $this->mostrarResumo();
        
        return true;
    }
    
    private function criarEstrutura() {
        logMessage("Criando estrutura de diretórios...");
        
        // Criar diretório principal
        if (!criarDiretorio($this->caminhoModulo)) {
            throw new Exception("Não foi possível criar diretório do módulo");
        }
        
        // Criar subdiretórios
        criarDiretorio($this->caminhoModulo . '/assets');
        criarDiretorio($this->caminhoModulo . '/assets/css');
        criarDiretorio($this->caminhoModulo . '/assets/js');
    }
    
    private function gerarArquivos() {
        global $forcar;
        
        logMessage("Gerando arquivos do módulo...");
        
        // Gerar controller
        $controller = gerarController($this->setor, $this->nomeModulo);
        $caminhoController = $this->caminhoModulo . '/' . $this->nomeModulo . '_logica.php';
        criarArquivo($caminhoController, $controller, $forcar);
        
        // Gerar view
        $view = gerarView($this->setor, $this->nomeModulo);
        $caminhoView = $this->caminhoModulo . '/' . $this->nomeModulo . '_view.php';
        criarArquivo($caminhoView, $view, $forcar);
        
        // Gerar JavaScript
        $js = gerarJavaScript($this->setor, $this->nomeModulo);
        $caminhoJS = $this->caminhoModulo . '/assets/js/' . $this->nomeModulo . '.js';
        criarArquivo($caminhoJS, $js, $forcar);
        
        // Gerar CSS
        $css = gerarCSS($this->setor, $this->nomeModulo);
        $caminhoCSS = $this->caminhoModulo . '/assets/css/' . $this->nomeModulo . '.css';
        criarArquivo($caminhoCSS, $css, $forcar);
    }
    
    private function gerarTabelaBanco() {
        logMessage("Gerando tabela no banco de dados...");
        
        $sql = gerarTabelaBanco($this->setor, $this->nomeModulo);
        $arquivoSQL = $this->caminhoModulo . '/tabela.sql';
        
        if (criarArquivo($arquivoSQL, $sql)) {
            logMessage("SQL da tabela salvo em: $arquivoSQL");
            logMessage("Execute o SQL no banco de dados para criar a tabela");
        }
    }
    
    private function gerarAtualizacaoMenu() {
        logMessage("Gerando atualização de menu...");
        
        $menu = atualizarMenu($this->setor, $this->nomeModulo);
        $arquivoMenu = $this->caminhoModulo . '/menu_update.php';
        
        if (criarArquivo($arquivoMenu, $menu)) {
            logMessage("Atualização de menu salva em: $arquivoMenu");
            logMessage("Adicione o código ao arquivo includes/sidebar_logica.php");
        }
    }
    
    private function mostrarResumo() {
        echo "\n=== MÓDULO GERADO COM SUCESSO ===\n";
        echo "Setor: {$this->setor}\n";
        echo "Módulo: {$this->nomeModulo}\n";
        echo "Caminho: {$this->caminhoModulo}\n\n";
        
        echo "Arquivos criados:\n";
        echo "- Controller: {$this->nomeModulo}_logica.php\n";
        echo "- View: {$this->nomeModulo}_view.php\n";
        echo "- JavaScript: assets/js/{$this->nomeModulo}.js\n";
        echo "- CSS: assets/css/{$this->nomeModulo}.css\n";
        
        if ($comBanco) {
            echo "- SQL da tabela: tabela.sql\n";
        }
        
        if ($comMenu) {
            echo "- Atualização de menu: menu_update.php\n";
        }
        
        echo "\nPróximos passos:\n";
        echo "1. Execute o SQL da tabela (se --com-banco)\n";
        echo "2. Adicione o menu (se --com-menu)\n";
        echo "3. Ajuste os campos conforme necessário\n";
        echo "4. Teste o módulo no navegador\n";
    }
}

// Execução principal
try {
    logMessage("=== INÍCIO DA GERAÇÃO DE MÓDULO SIGEP ===");
    
    // Validar parâmetros
    if (!validarParametros($setor, $nomeModulo)) {
        logMessage("Uso: php gerar_modulo_sigep.php [setor] [nome_modulo] [opcoes]");
        logMessage("Exemplo: php gerar_modulo_sigep.php ti relatorios --com-banco --com-menu");
        exit(1);
    }
    
    // Validar projeto
    if (!is_dir($caminhoProjeto . '/modulos')) {
        logMessage("ERRO: Estrutura de módulos não encontrada em: $caminhoProjeto", 'ERROR');
        exit(1);
    }
    
    // Gerar módulo
    $gerador = new GeradorModuloSIGEP($setor, $nomeModulo, $caminhoProjeto);
    $gerador->gerar();
    
    logMessage("=== GERAÇÃO CONCLUÍDA COM SUCESSO ===");
    
} catch (Exception $e) {
    logMessage("ERRO FATAL: " . $e->getMessage(), 'ERROR');
    exit(1);
}
?>
