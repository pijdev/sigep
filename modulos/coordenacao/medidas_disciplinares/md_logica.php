<?php
session_start();
require_once dirname(__DIR__, 3) . '/conf/db.php';

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
                
                if (empty($ipen)) {
                    echo json_encode(['success' => false, 'error' => 'IPEN não informado']);
                    exit;
                }
                
                try {
                    $stmt = $pdo->prepare("SELECT ipen, nome, nome_social, galeria, bloco, res FROM internos WHERE ipen = ? AND status = 'A'");
                    $stmt->execute([$ipen]);
                    $interno = $stmt->fetch(PDO::FETCH_ASSOC);
                    
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
                                $item['observacoes'] ?? null,
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
                $diretorio = dirname(__DIR__, 3) . '/uploads/md_anexos/' . date('Y/m');
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
