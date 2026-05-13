<?php
session_start();
require_once __DIR__ . '/../conf/db.php';

date_default_timezone_set('America/Sao_Paulo');

try {
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['user'], $config['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    die("Erro de conexão com o banco de dados.");
}

// AJAX Actions
if (isset($_REQUEST['action'])) {
    header('Content-Type: application/json');
    
    try {
        switch ($_REQUEST['action']) {
            case 'buscar_interno':
                $ipen = $_POST['ipen'] ?? '';
                
                // Debug: mostrar o IPEN recebido
                error_log("DEBUG: Buscando IPEN: " . $ipen);
                
                if (empty($ipen)) {
                    echo json_encode(['success' => false, 'error' => 'IPEN não informado']);
                    exit;
                }
                
                try {
                    $stmt = $pdo->prepare("SELECT ipen, nome, nome_social, galeria, bloco, res FROM internos WHERE ipen = ? AND status = 'A'");
                    $stmt->execute([$ipen]);
                    $interno = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    error_log("DEBUG: Resultado busca: " . ($interno ? 'Encontrado' : 'Não encontrado'));
                    
                    if ($interno) {
                        // Buscar itens apreendidos deste interno ou da cela
                        $stmtItens = $pdo->prepare("
                            SELECT ia.*, i.nome as interno_nome, i.galeria, i.bloco, i.res
                            FROM internos_md_itens_apreendidos ia
                            JOIN internos_md_medidas m ON ia.id_medida = m.id
                            JOIN internos i ON m.id_interno = i.ipen
                            WHERE (m.id_interno = ? OR (i.galeria = ? AND i.bloco = ? AND i.res = ?))
                            AND ia.status_item = 'Retido'
                            ORDER BY ia.id DESC
                        ");
                        $stmtItens->execute([$ipen, $interno['galeria'], $interno['bloco'], $interno['res']]);
                        $itens_apreendidos = $stmtItens->fetchAll(PDO::FETCH_ASSOC);
                        
                        echo json_encode(['success' => true, 'interno' => $interno, 'itens_apreendidos' => $itens_apreendidos]);
                    } else {
                        echo json_encode(['success' => false, 'error' => 'Interno não encontrado ou inativo']);
                    }
                } catch (PDOException $e) {
                    error_log("ERRO SQL: " . $e->getMessage());
                    echo json_encode(['success' => false, 'error' => 'Erro na consulta: ' . $e->getMessage()]);
                }
                break;
                
            case 'salvar_md':
                $id_interno = $_POST['id_interno'];
                $data_inicio = $_POST['data_inicio'];
                $data_fim = $_POST['data_fim'];
                $motivo = $_POST['motivo'];
                $local_castigo = $_POST['local_castigo'];
                $observacoes = $_POST['observacoes'] ?? null;
                
                // Validar datas
                if (strtotime($data_fim) <= strtotime($data_inicio)) {
                    echo json_encode(['success' => false, 'error' => 'Data fim deve ser posterior à data início']);
                    break;
                }
                
                // Verificar se já existe MD ativa para este interno
                $stmt = $pdo->prepare("SELECT id FROM internos_md_medidas WHERE id_interno = ? AND status = 'Ativa' AND data_fim >= CURDATE()");
                $stmt->execute([$id_interno]);
                if ($stmt->rowCount() > 0) {
                    echo json_encode(['success' => false, 'error' => 'Este interno já possui uma medida disciplinar ativa']);
                    break;
                }
                
                // Inserir MD
                $stmt = $pdo->prepare("
                    INSERT INTO internos_md_medidas 
                    (id_interno, data_inicio, data_fim, motivo, local_castigo, id_usuario_cadastro, observacoes) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$id_interno, $data_inicio, $data_fim, $motivo, $local_castigo, $_SESSION['user_id'], $observacoes]);
                
                $id_medida = $pdo->lastInsertId();
                
                // Processar itens apreendidos se existirem
                if (isset($_POST['itens_apreendidos']) && is_array($_POST['itens_apreendidos'])) {
                    foreach ($_POST['itens_apreendidos'] as $item) {
                        if (!empty($item['tipo_item'])) {
                            $stmt = $pdo->prepare("
                                INSERT INTO internos_md_itens_apreendidos 
                                (id_medida, tipo_item, marca, modelo, cor, descricao, local_retido) 
                                VALUES (?, ?, ?, ?, ?, ?, ?)
                            ");
                            $stmt->execute([
                                $id_medida,
                                $item['tipo_item'],
                                $item['marca'] ?? null,
                                $item['modelo'] ?? null,
                                $item['cor'] ?? null,
                                $item['observacoes'] ?? null, // Usar observacoes como descricao
                                $item['local_retido'] ?? 'Coordenação'
                            ]);
                        }
                    }
                }
                
                echo json_encode(['success' => true, 'id_medida' => $id_medida]);
                break;
                
            case 'listar_mds':
                $where = ["m.status IN ('Ativa', 'Concluida')"];
                $params = [];
                
                if (!empty($_GET['busca'])) {
                    $where[] = "(i.nome LIKE ? OR i.ipen LIKE ? OR m.motivo LIKE ?)";
                    $busca = "%{$_GET['busca']}%";
                    array_push($params, $busca, $busca, $busca);
                }
                
                if (!empty($_GET['status'])) {
                    $where[] = "m.status = ?";
                    $params[] = $_GET['status'];
                }
                
                $sql = "
                    SELECT m.*, i.nome, i.nome_social, i.galeria, i.bloco, i.res,
                           u.nome as usuario_cadastro
                    FROM internos_md_medidas m
                    JOIN internos i ON m.id_interno = i.ipen
                    JOIN acesso_seguro u ON m.id_usuario_cadastro = u.id
                    WHERE " . implode(" AND ", $where) . "
                    ORDER BY m.data_cadastro DESC
                    LIMIT 50
                ";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $mds = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode(['success' => true, 'data' => $mds]);
                break;
                
            case 'concluir_md':
                $id_medida = $_POST['id_medida'];
                $stmt = $pdo->prepare("
                    UPDATE internos_md_medidas 
                    SET status = 'Concluida', data_conclusao = NOW(), id_usuario_conclusao = ?
                    WHERE id = ? AND status = 'Ativa'
                ");
                $stmt->execute([$_SESSION['user_id'], $id_medida]);
                
                echo json_encode(['success' => true]);
                break;
                
            case 'buscar_md':
                $id_medida = $_GET['id'];
                $stmt = $pdo->prepare("
                    SELECT m.*, i.nome, i.nome_social, i.galeria, i.bloco, i.res
                    FROM internos_md_medidas m
                    JOIN internos i ON m.id_interno = i.ipen
                    WHERE m.id = ?
                ");
                $stmt->execute([$id_medida]);
                $md = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($md) {
                    // Buscar itens apreendidos
                    $stmtItens = $pdo->prepare("
                        SELECT * FROM internos_md_itens_apreendidos WHERE id_medida = ?
                    ");
                    $stmtItens->execute([$id_medida]);
                    $itens = $stmtItens->fetchAll(PDO::FETCH_ASSOC);
                    
                    echo json_encode(['success' => true, 'md' => $md, 'itens' => $itens]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'MD não encontrada']);
                }
                break;
                
            case 'editar_md':
                $id_medida = $_POST['id_medida'];
                $data_inicio = $_POST['data_inicio'];
                $data_fim = $_POST['data_fim'];
                $motivo = $_POST['motivo'];
                $local_castigo = $_POST['local_castigo'];
                $observacoes = $_POST['observacoes'] ?? null;
                
                // Validar datas
                if (strtotime($data_fim) <= strtotime($data_inicio)) {
                    echo json_encode(['success' => false, 'error' => 'Data fim deve ser posterior à data início']);
                    break;
                }
                
                // Atualizar MD
                $stmt = $pdo->prepare("
                    UPDATE internos_md_medidas 
                    SET data_inicio = ?, data_fim = ?, motivo = ?, local_castigo = ?, observacoes = ?
                    WHERE id = ?
                ");
                $stmt->execute([$data_inicio, $data_fim, $motivo, $local_castigo, $observacoes, $id_medida]);
                
                // Processar itens apreendidos se existirem
                if (isset($_POST['itens_apreendidos']) && is_array($_POST['itens_apreendidos'])) {
                    // Remover itens existentes
                    $stmt = $pdo->prepare("DELETE FROM internos_md_itens_apreendidos WHERE id_medida = ?");
                    $stmt->execute([$id_medida]);
                    
                    // Inserir novos itens
                    foreach ($_POST['itens_apreendidos'] as $item) {
                        if (!empty($item['tipo_item'])) {
                            $stmt = $pdo->prepare("
                                INSERT INTO internos_md_itens_apreendidos 
                                (id_medida, tipo_item, marca, modelo, cor, descricao, local_retido) 
                                VALUES (?, ?, ?, ?, ?, ?, ?)
                            ");
                            $stmt->execute([
                                $id_medida,
                                $item['tipo_item'],
                                $item['marca'] ?? null,
                                $item['modelo'] ?? null,
                                $item['cor'] ?? null,
                                $item['observacoes'] ?? null,
                                $item['local_retido'] ?? 'Coordenação'
                            ]);
                        }
                    }
                }
                
                echo json_encode(['success' => true]);
                break;
                
            case 'excluir_md':
                $id_medida = $_POST['id_medida'];
                
                // Verificar se não está ativa
                $stmt = $pdo->prepare("SELECT status FROM internos_md_medidas WHERE id = ?");
                $stmt->execute([$id_medida]);
                $md = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$md) {
                    echo json_encode(['success' => false, 'error' => 'MD não encontrada']);
                    break;
                }
                
                if ($md['status'] === 'Ativa') {
                    echo json_encode(['success' => false, 'error' => 'Não é possível excluir uma MD ativa. Conclua-a primeiro.']);
                    break;
                }
                
                // Excluir MD (cascade deletará os itens)
                $stmt = $pdo->prepare("DELETE FROM internos_md_medidas WHERE id = ?");
                $stmt->execute([$id_medida]);
                
                echo json_encode(['success' => true]);
                break;
                
            case 'upload_anexo':
                if (!isset($_FILES['arquivo'])) {
                    echo json_encode(['success' => false, 'error' => 'Nenhum arquivo enviado']);
                    break;
                }
                
                $id_medida = $_POST['id_medida'];
                $arquivo = $_FILES['arquivo'];
                
                // Validar arquivo
                $tipos_permitidos = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
                $tamanho_maximo = 5 * 1024 * 1024; // 5MB
                
                if (!in_array($arquivo['type'], $tipos_permitidos)) {
                    echo json_encode(['success' => false, 'error' => 'Tipo de arquivo não permitido']);
                    break;
                }
                
                if ($arquivo['size'] > $tamanho_maximo) {
                    echo json_encode(['success' => false, 'error' => 'Arquivo muito grande (máximo 5MB)']);
                    break;
                }
                
                // Criar diretório se não existir
                $diretorio = __DIR__ . '/../uploads/md_anexos/' . date('Y/m');
                if (!is_dir($diretorio)) {
                    mkdir($diretorio, 0755, true);
                }
                
                // Gerar nome único
                $extensao = pathinfo($arquivo['name'], PATHINFO_EXTENSION);
                $nome_arquivo = 'md_' . $id_medida . '_' . time() . '_' . uniqid() . '.' . $extensao;
                $caminho_completo = $diretorio . '/' . $nome_arquivo;
                
                // Mover arquivo
                if (move_uploaded_file($arquivo['tmp_name'], $caminho_completo)) {
                    $stmt = $pdo->prepare("
                        INSERT INTO internos_md_anexos 
                        (id_medida, nome_arquivo, arquivo_original, tipo_arquivo, tamanho_arquivo, caminho_completo, id_usuario_upload)
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $id_medida,
                        $nome_arquivo,
                        $arquivo['name'],
                        $arquivo['type'],
                        $arquivo['size'],
                        $caminho_completo,
                        $_SESSION['user_id']
                    ]);
                    
                    echo json_encode(['success' => true]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Erro ao fazer upload do arquivo']);
                }
                break;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Carregar dados para a página
$stats = [
    'ativas' => $pdo->query("SELECT COUNT(*) FROM internos_md_medidas WHERE status = 'Ativa'")->fetchColumn(),
    'concluidas' => $pdo->query("SELECT COUNT(*) FROM internos_md_medidas WHERE status = 'Concluida'")->fetchColumn(),
    'itens_retidos' => $pdo->query("SELECT COUNT(*) FROM internos_md_itens_apreendidos WHERE status_item = 'Retido'")->fetchColumn()
];
?>

<script>
    window.pageTitle = 'Medidas Disciplinares';
    window.currentPage = 'internos_md_cadastro.php';
</script>

<style>
    .md-card {
        border-left: 4px solid #007bff;
        transition: all 0.3s ease;
    }
    .md-card:hover {
        box-shadow: 0 4px 12px rgba(0,123,255,0.15);
        transform: translateY(-2px);
    }
    .md-status-ativa {
        border-left-color: #28a745;
    }
    .md-status-concluida {
        border-left-color: #6c757d;
    }
    .md-pulse {
        animation: pulse-red 2s infinite;
    }
    @keyframes pulse-red {
        0% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7); }
        70% { box-shadow: 0 0 0 10px rgba(220, 53, 69, 0); }
        100% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
    }
    .dark-mode .md-card {
        background-color: #343a40;
        border-color: #495057;
    }
    .dark-mode .md-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    }
</style>

<section class="content pt-3">
    <div class="container-fluid">
        
        <!-- CARDS ESTATÍSTICA -->
        <div class="row mb-3">
            <div class="col-lg-4">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3><?= number_format($stats['ativas'], 0) ?></h3>
                        <p>MDs Ativas</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="small-box bg-secondary">
                    <div class="inner">
                        <h3><?= number_format($stats['concluidas'], 0) ?></h3>
                        <p>MDs Concluídas</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3><?= number_format($stats['itens_retidos'], 0) ?></h3>
                        <p>Itens Retidos</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-box"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="m-0 text-dark dark-mode-text font-weight-bold">
                <i class="fas fa-gavel text-primary mr-2"></i> Medidas Disciplinares
            </h4>
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalNovaMD">
                <i class="fas fa-plus mr-2"></i>Nova Medida Disciplinar
            </button>
        </div>

        <!-- FILTROS -->
        <div class="card card-outline card-primary shadow-sm mb-3">
            <div class="card-body py-3">
                <form id="formFiltro" onsubmit="carregarMDs(); return false;">
                    <div class="row">
                        <div class="col-md-4">
                            <input type="text" class="form-control" name="busca" placeholder="Buscar por interno, IPEN ou motivo...">
                        </div>
                        <div class="col-md-3">
                            <select class="form-control" name="status">
                                <option value="">Todos os status</option>
                                <option value="Ativa">Ativas</option>
                                <option value="Concluida">Concluídas</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search mr-2"></i>Filtrar
                            </button>
                            <button type="button" class="btn btn-outline-secondary ml-2" onclick="limparFiltros()">
                                <i class="fas fa-times mr-2"></i>Limpar
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- LISTA DE MDs -->
        <div class="card shadow border-0">
            <div class="card-body">
                <div id="listaMDs">
                    <div class="text-center p-4">
                        <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                        <p class="mt-2">Carregando...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- MODAL NOVA MD -->
<div class="modal fade" id="modalNovaMD" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-gavel mr-2"></i>Nova Medida Disciplinar
                </h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="formNovaMD">
                    <input type="hidden" name="id_medida" id="id_medida">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="small font-weight-bold">IPEN do Interno <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="ipen_interno" name="id_interno" required>
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-outline-secondary" onclick="buscarInterno()">
                                            <i class="fas fa-search"></i>
                                        </button>
                                    </div>
                                </div>
                                <small class="text-muted">Digite o IPEN e clique na lupa para buscar</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="small font-weight-bold">Local de Castigo <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="local_castigo" required 
                                       placeholder="Ex: Cela de Castigo 1, Isolamento...">
                            </div>
                        </div>
                    </div>
                    
                    <div id="dadosInterno" class="alert alert-info d-none">
                        <h6><i class="fas fa-user mr-2"></i>Dados do Interno</h6>
                        <div id="infoInterno"></div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="small font-weight-bold">Data Início <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="data_inicio" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="small font-weight-bold">Data Fim <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="data_fim" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="small font-weight-bold">Motivo <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="motivo" rows="3" required 
                                  placeholder="Descreva detalhadamente o motivo da medida disciplinar..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="small font-weight-bold">Observações</label>
                        <textarea class="form-control" name="observacoes" rows="2" 
                                  placeholder="Informações adicionais..."></textarea>
                    </div>
                    
                    <!-- ITENS APREENDIDOS -->
                    <div class="border-top pt-3">
                        <h6><i class="fas fa-box mr-2"></i>Itens Apreendidos</h6>
                        <div id="itensApreendidos">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle mr-2"></i>
                                <strong>Busque o interno para carregar os itens apreendidos vinculados a ele ou à sua cela.</strong>
                            </div>
                        </div>
                    </div>
                    
                    <!-- ANEXOS -->
                    <div class="border-top pt-3 mt-3">
                        <h6><i class="fas fa-paperclip mr-2"></i>Anexos (Fotos/Documentos)</h6>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="anexoMD" accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx">
                            <label class="custom-file-label" for="anexoMD">Escolher arquivo...</label>
                        </div>
                        <small class="text-muted">Formatos aceitos: JPG, PNG, GIF, PDF, DOC, DOCX (máx. 5MB)</small>
                        <div id="listaAnexos" class="mt-2"></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="salvarMD()">
                    <i class="fas fa-save mr-2"></i>Salvar Medida Disciplinar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let contadorItens = 1;
let anexosParaUpload = [];

// Carregar MDs ao iniciar
$(document).ready(function() {
    carregarMDs();
    
    // Setar datas padrão
    const hoje = new Date().toISOString().split('T')[0];
    $('input[name="data_inicio"]').val(hoje);
    
    const dataFim = new Date();
    dataFim.setDate(dataFim.getDate() + 7); // 7 dias padrão
    $('input[name="data_fim"]').val(dataFim.toISOString().split('T')[0]);
});

function carregarMDs() {
    const params = $('#formFiltro').serialize();
    
    fetch('paginas/internos_md_cadastro.php?action=listar_mds&' + params)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderizarMDs(data.data);
            } else {
                $('#listaMDs').html('<div class="alert alert-danger">Erro ao carregar dados.</div>');
            }
        })
        .catch(error => {
            $('#listaMDs').html('<div class="alert alert-danger">Erro de conexão.</div>');
        });
}

function renderizarMDs(mds) {
    if (mds.length === 0) {
        $('#listaMDs').html('<div class="text-center p-4 text-muted">Nenhuma medida disciplinar encontrada.</div>');
        return;
    }
    
    let html = '<div class="row">';
    
    mds.forEach(md => {
        const nomeExib = md.nome_social ? 
            `<strong>${md.nome_social}</strong><br><small class="text-muted">(${md.nome})</small>` : 
            `<strong>${md.nome}</strong>`;
            
        const localInterno = `${md.galeria}${md.bloco}-${md.res}`;
        const statusClass = md.status === 'Ativa' ? 'md-status-ativa' : 'md-status-concluida';
        const statusBadge = md.status === 'Ativa' ? 
            '<span class="badge badge-success">Ativa</span>' : 
            '<span class="badge badge-secondary">Concluída</span>';
            
        const dataFim = new Date(md.data_fim);
        const hoje = new Date();
        const diasRestantes = Math.ceil((dataFim - hoje) / (1000 * 60 * 60 * 24));
        const estaVencendo = md.status === 'Ativa' && diasRestantes <= 2;
        const vencimentoClass = estaVencendo ? 'md-pulse' : '';
        
        html += `
            <div class="col-lg-6 mb-3">
                <div class="card md-card ${statusClass} ${vencimentoClass}">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h6 class="card-title mb-1">
                                    <i class="fas fa-user mr-2"></i>${nomeExib}
                                </h6>
                                <small class="text-muted">
                                    <i class="fas fa-id-badge mr-1"></i>${md.id_interno} | 
                                    <i class="fas fa-map-marker-alt mr-1"></i>${localInterno}
                                </small>
                            </div>
                            <div class="text-right">
                                ${statusBadge}
                                ${estaVencendo ? '<br><small class="text-danger"><i class="fas fa-exclamation-triangle"></i> Vence em ' + diasRestantes + ' dias</small>' : ''}
                            </div>
                        </div>
                        
                        <div class="row text-sm mb-2">
                            <div class="col-6">
                                <strong>Início:</strong> ${formatDate(md.data_inicio)}
                            </div>
                            <div class="col-6">
                                <strong>Fim:</strong> ${formatDate(md.data_fim)}
                            </div>
                        </div>
                        
                        <div class="mb-2">
                            <strong>Motivo:</strong><br>
                            <small>${md.motivo.substring(0, 100)}${md.motivo.length > 100 ? '...' : ''}</small>
                        </div>
                        
                        <div class="mb-2">
                            <strong>Local:</strong> ${md.local_castigo}
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                <i class="fas fa-user mr-1"></i>${md.usuario_cadastro} em ${formatDateTime(md.data_cadastro)}
                            </small>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" onclick="verDetalhes(${md.id})">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-outline-warning" onclick="editarMD(${md.id})">
                                    <i class="fas fa-edit"></i>
                                </button>
                                ${md.status === 'Ativa' ? `
                                    <button class="btn btn-outline-success" onclick="concluirMD(${md.id})">
                                        <i class="fas fa-check"></i>
                                    </button>
                                ` : ''}
                                ${md.status === 'Concluida' ? `
                                    <button class="btn btn-outline-danger" onclick="excluirMD(${md.id})">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
    
    html += '</div>';
    $('#listaMDs').html(html);
}

function buscarInterno() {
    const ipen = $('#ipen_interno').val().trim();
    
    if (!ipen) {
        alert('Digite o IPEN do interno.');
        return;
    }
    
    fetch('paginas/internos_md_cadastro.php?action=buscar_interno', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'ipen=' + encodeURIComponent(ipen)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const interno = data.interno;
            const nomeExib = interno.nome_social ? 
                `${interno.nome_social} (${interno.nome})` : interno.nome;
                
            $('#dadosInterno').removeClass('d-none').html(`
                <strong>Nome:</strong> ${nomeExib}<br>
                <strong>Local:</strong> ${interno.galeria}${interno.bloco}-${interno.res}
            `);
            
            // Carregar itens apreendidos existentes
            carregarItensApreendidos(data.itens_apreendidos || []);
        } else {
            $('#dadosInterno').addClass('d-none');
            alert(data.error || 'Interno não encontrado.');
        }
    })
    .catch(error => {
        alert('Erro ao buscar interno.');
        console.error(error);
    });
}

function carregarItensApreendidos(itens) {
    let html = '';
    
    if (itens.length > 0) {
        html = '<div class="alert alert-info mb-3"><strong>Itens já apreendidos (vinculados ao interno ou à cela):</strong></div>';
        
        itens.forEach((item, index) => {
            html += `
                <div class="item-apreendido row mb-2 border p-2 bg-light">
                    <div class="col-md-3">
                        <input type="text" class="form-control" value="${item.tipo_item}" readonly>
                        <input type="hidden" name="itens_apreendidos[${index}][tipo_item]" value="${item.tipo_item}">
                    </div>
                    <div class="col-md-3">
                        <input type="text" class="form-control" name="itens_apreendidos[${index}][observacoes]" 
                               placeholder="Observações (danificado, etc.)" value="${item.observacoes || ''}">
                    </div>
                    <div class="col-md-2">
                        <input type="text" class="form-control" name="itens_apreendidos[${index}][quem_recolheu]" 
                               placeholder="Quem recolheu" value="${item.quem_recolheu || ''}">
                    </div>
                    <div class="col-md-2">
                        <select class="form-control" name="itens_apreendidos[${index}][local_retido]">
                            <option value="Coordenação" ${item.local_retido === 'Coordenação' ? 'selected' : ''}>Coordenação</option>
                            <option value="Censura" ${item.local_retido === 'Censura' ? 'selected' : ''}>Censura</option>
                            <option value="Direção" ${item.local_retido === 'Direção' ? 'selected' : ''}>Direção</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <small class="text-muted">Marca: ${item.marca || 'N/A'}</small><br>
                        <small class="text-muted">Modelo: ${item.modelo || 'N/A'}</small>
                    </div>
                </div>
            `;
        });
        
        contadorItens = itens.length;
    } else {
        html = '<div class="alert alert-warning mb-3"><i class="fas fa-info-circle mr-2"></i><strong>Nenhum item apreendido encontrado</strong><br><small class="text-muted">Não há itens vinculados a este interno ou à cela onde ele está.</small></div>';
    }
    
    $('#itensApreendidos').html(html);
    contadorItens++;
}

function adicionarItem() {
    const html = `
        <div class="item-apreendido row mb-2">
            <div class="col-md-3">
                <select class="form-control" name="itens_apreendidos[${contadorItens}][tipo_item]">
                    <option value="">Selecione...</option>
                    <option value="TV">TV</option>
                    <option value="Radio">Rádio</option>
                    <option value="Ventilador">Ventilador</option>
                    <option value="Maquina Cabelo">Máquina de Cabelo</option>
                    <option value="Chaleira">Chaleira</option>
                    <option value="Outro">Outro</option>
                </select>
            </div>
            <div class="col-md-2">
                <input type="text" class="form-control" name="itens_apreendidos[${contadorItens}][marca]" placeholder="Marca">
            </div>
            <div class="col-md-2">
                <input type="text" class="form-control" name="itens_apreendidos[${contadorItens}][modelo]" placeholder="Modelo">
            </div>
            <div class="col-md-2">
                <select class="form-control" name="itens_apreendidos[${contadorItens}][local_retido]">
                    <option value="Coordenação">Coordenação</option>
                    <option value="Censura">Censura</option>
                    <option value="Direção">Direção</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="button" class="btn btn-sm btn-danger" onclick="removerItem(this)">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `;
    
    $('#itensApreendidos').append(html);
    contadorItens++;
}

function removerItem(botao) {
    $(botao).closest('.item-apreendido').remove();
}

// Upload de anexos
$('#anexoMD').change(function() {
    const arquivo = this.files[0];
    if (!arquivo) return;
    
    // Validar arquivo
    const tiposPermitidos = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    const tamanhoMaximo = 5 * 1024 * 1024; // 5MB
    
    if (!tiposPermitidos.includes(arquivo.type)) {
        alert('Tipo de arquivo não permitido.');
        $(this).val('');
        return;
    }
    
    if (arquivo.size > tamanhoMaximo) {
        alert('Arquivo muito grande (máximo 5MB).');
        $(this).val('');
        return;
    }
    
    anexosParaUpload.push(arquivo);
    
    const html = `
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <i class="fas fa-paperclip mr-2"></i>
            <strong>${arquivo.name}</strong> (${formatFileSize(arquivo.size)})
            <button type="button" class="close" onclick="removerAnexo(this, '${arquivo.name}')">
                <span>&times;</span>
            </button>
        </div>
    `;
    
    $('#listaAnexos').append(html);
    $(this).val('');
});

function removerAnexo(btn, nomeArquivo) {
    anexosParaUpload = anexosParaUpload.filter(a => a.name !== nomeArquivo);
    $(btn).closest('.alert').remove();
}

function salvarMD() {
    const form = document.getElementById('formNovaMD');
    const formData = new FormData(form);
    
    // Validar formulário
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    // Adicionar action
    formData.append('action', 'salvar_md');
    
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Salvando...';
    btn.disabled = true;
    
    fetch('paginas/internos_md_cadastro.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Fazer upload dos anexos
            if (anexosParaUpload.length > 0) {
                uploadAnexos(data.id_medida);
            } else {
                finalizarSalvarMD();
            }
        } else {
            alert('Erro: ' + data.error);
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    })
    .catch(error => {
        alert('Erro de conexão.');
        btn.innerHTML = originalText;
        btn.disabled = false;
        console.error(error);
    });
}

function uploadAnexos(idMedida) {
    const promises = anexosParaUpload.map(arquivo => {
        const formData = new FormData();
        formData.append('action', 'upload_anexo');
        formData.append('id_medida', idMedida);
        formData.append('arquivo', arquivo);
        
        return fetch('paginas/internos_md_cadastro.php', {
            method: 'POST',
            body: formData
        });
    });
    
    Promise.all(promises)
        .then(() => {
            finalizarSalvarMD();
        })
        .catch(error => {
            console.error('Erro no upload:', error);
            finalizarSalvarMD(); // Mesmo com erro no upload, a MD foi salva
        });
}

function finalizarSalvarMD() {
    $('#modalNovaMD').modal('hide');
    alert('Medida disciplinar salva com sucesso!');
    
    // Limpar formulário
    document.getElementById('formNovaMD').reset();
    $('#dadosInterno').addClass('d-none');
    $('#listaAnexos').empty();
    anexosParaUpload = [];
    
    // Recarregar lista
    carregarMDs();
}

function concluirMD(id) {
    if (!confirm('Deseja concluir esta medida disciplinar?')) return;
    
    fetch('paginas/internos_md_cadastro.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=concluir_md&id_medida=' + id
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            carregarMDs();
        } else {
            alert('Erro: ' + data.error);
        }
    })
    .catch(error => {
        alert('Erro ao concluir MD.');
        console.error(error);
    });
}

function editarMD(id) {
    fetch('paginas/internos_md_cadastro.php?action=buscar_md&id=' + id)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const md = data.md;
                const itens = data.itens || [];
                
                // Preencher formulário
                $('#id_medida').val(md.id);
                $('#ipen_interno').val(md.id_interno);
                $('input[name="data_inicio"]').val(md.data_inicio);
                $('input[name="data_fim"]').val(md.data_fim);
                $('textarea[name="motivo"]').val(md.motivo);
                $('textarea[name="observacoes"]').val(md.observacoes);
                $('select[name="local_castigo"]').val(md.local_castigo);
                
                // Buscar dados do interno
                buscarInterno();
                
                // Carregar itens existentes
                setTimeout(() => {
                    carregarItensApreendidos(itens);
                }, 500);
                
                // Mudar título do modal
                $('#modalNovaMD .modal-title').html('<i class="fas fa-edit"></i> Editar Medida Disciplinar');
                
                // Mudar botão de salvar
                $('#modalNovaMD .modal-footer .btn-primary').attr('onclick', 'atualizarMD()').html('<i class="fas fa-save mr-2"></i>Atualizar Medida Disciplinar');
                
                // Abrir modal
                $('#modalNovaMD').modal('show');
            } else {
                alert('Erro: ' + data.error);
            }
        })
        .catch(error => {
            alert('Erro ao buscar MD.');
            console.error(error);
        });
}

function atualizarMD() {
    const form = document.getElementById('formNovaMD');
    const formData = new FormData(form);
    formData.append('action', 'editar_md');
    
    fetch('paginas/internos_md_cadastro.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            $('#modalNovaMD').modal('hide');
            carregarMDs();
            alert('Medida disciplinar atualizada com sucesso!');
        } else {
            alert('Erro: ' + data.error);
        }
    })
    .catch(error => {
        alert('Erro ao atualizar MD.');
        console.error(error);
    });
}

function excluirMD(id) {
    if (!confirm('Deseja excluir esta medida disciplinar? Esta ação não poderá ser desfeita.')) return;
    
    fetch('paginas/internos_md_cadastro.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=excluir_md&id_medida=' + id
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            carregarMDs();
            alert('Medida disciplinar excluída com sucesso!');
        } else {
            alert('Erro: ' + data.error);
        }
    })
    .catch(error => {
        alert('Erro ao excluir MD.');
        console.error(error);
    });
}

function verDetalhes(id) {
    // TODO: Implementar modal de detalhes
    alert('Funcionalidade em desenvolvimento.');
}

function limparFiltros() {
    $('#formFiltro')[0].reset();
    carregarMDs();
}

// Funções utilitárias
function formatDate(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleDateString('pt-BR');
}

function formatDateTime(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleDateString('pt-BR') + ' ' + date.toLocaleTimeString('pt-BR', {hour: '2-digit', minute:'2-digit'});
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}
</script>
