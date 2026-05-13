<?php
session_start();
ob_start();
date_default_timezone_set('America/Sao_Paulo');
ini_set('display_errors', 0);
error_reporting(E_ALL);
mb_internal_encoding('UTF-8');

if (!isset($_SESSION['user_id'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Sessao expirada.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    header('Location: /autenticacao');
    exit;
}

try {
    $config = require __DIR__ . '/../conf/db.php';
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['user'], $config['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die('Erro 500: Falha na conexao com o Banco de Dados.');
}

function v2_ok(array $data = []): void
{
    echo json_encode(array_merge(['success' => true], $data), JSON_UNESCAPED_UNICODE);
    exit;
}

function v2_error(string $message, ?string $code = null, array $data = []): void
{
    $payload = array_merge(['success' => false, 'message' => $message], $data);
    if ($code !== null) {
        $payload['code'] = $code;
    }
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function in_str(string $key, string $default = ''): string
{
    return isset($_POST[$key]) ? trim((string) $_POST[$key]) : $default;
}

function in_int_or_null(string $key): ?int
{
    if (!isset($_POST[$key])) {
        return null;
    }
    $v = trim((string) $_POST[$key]);
    return $v === '' ? null : (int) $v;
}

function require_write_permission(): void
{
    if (!isset($_SESSION['user_id'])) {
        v2_error('Sessao expirada.', 'AUTH_REQUIRED');
    }
}

function movimentacao_destino_expr(): string
{
    return "CASE
        WHEN m.tipo_destino_origem = 'Interno' THEN CONCAT(i.ipen, ' - ', i.nome)
        WHEN m.tipo_destino_origem = 'Funcionario' THEN m.id_funcionario
        WHEN m.tipo_destino_origem = 'Fornecedor' THEN f.nome
        ELSE m.destino_origem_outro
    END";
}

function audit_mov(PDO $pdo, int $idMov, string $acao, ?array $before, ?array $after, ?string $obs = null): void
{
    $sql = "INSERT INTO censura_estoque_movimentacoes_auditoria
            (id_movimentacao, acao, snapshot_antes, snapshot_depois, observacao, usuario_id)
            VALUES (?, ?, ?, ?, ?, ?)";
    $pdo->prepare($sql)->execute([
        $idMov,
        $acao,
        $before ? json_encode($before, JSON_UNESCAPED_UNICODE) : null,
        $after ? json_encode($after, JSON_UNESCAPED_UNICODE) : null,
        $obs,
        (int) $_SESSION['user_id'],
    ]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['db_action'])) {
    ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    $action = in_str('db_action');

    try {
        if ($action === 'tipo_listar') {
            $rows = $pdo->query("SELECT * FROM censura_estoque_tipos ORDER BY status DESC, nome")->fetchAll();
            v2_ok(['data' => $rows]);
        }

        if ($action === 'tipo_salvar') {
            require_write_permission();
            $nome = in_str('nome');
            $descricao = in_str('descricao');
            if ($nome === '') {
                v2_error('Nome do tipo e obrigatorio.', 'VALIDATION');
            }
            $stmt = $pdo->prepare('INSERT INTO censura_estoque_tipos (nome, descricao, status) VALUES (?, ?, "Ativo")');
            $stmt->execute([$nome, $descricao ?: null]);
            v2_ok(['message' => 'Tipo cadastrado com sucesso.']);
        }

        if ($action === 'tipo_editar') {
            require_write_permission();
            $id = in_int_or_null('id');
            $nome = in_str('nome');
            $descricao = in_str('descricao');
            $status = in_str('status', 'Ativo');
            if (!$id || $nome === '') {
                v2_error('Dados invalidos para tipo.', 'VALIDATION');
            }
            $stmt = $pdo->prepare('UPDATE censura_estoque_tipos SET nome = ?, descricao = ?, status = ? WHERE id = ?');
            $stmt->execute([$nome, $descricao ?: null, $status, $id]);
            v2_ok(['message' => 'Tipo atualizado com sucesso.']);
        }

        if ($action === 'tipo_inativar') {
            require_write_permission();
            $id = in_int_or_null('id');
            if (!$id) {
                v2_error('Tipo nao informado.', 'VALIDATION');
            }
            $pdo->prepare("UPDATE censura_estoque_tipos SET status = 'Inativo' WHERE id = ?")->execute([$id]);
            v2_ok(['message' => 'Tipo inativado com sucesso.']);
        }

        if ($action === 'fornecedor_listar') {
            $rows = $pdo->query('SELECT * FROM censura_estoque_fornecedores ORDER BY status DESC, nome')->fetchAll();
            v2_ok(['data' => $rows]);
        }

        if ($action === 'fornecedor_obter') {
            $id = in_int_or_null('id');
            if (!$id) {
                v2_error('Fornecedor nao informado.', 'VALIDATION');
            }
            $stmt = $pdo->prepare('SELECT * FROM censura_estoque_fornecedores WHERE id = ?');
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            if (!$row) {
                v2_error('Fornecedor nao encontrado.', 'NOT_FOUND');
            }
            v2_ok(['data' => $row]);
        }

        if ($action === 'fornecedor_salvar') {
            require_write_permission();
            $id = in_int_or_null('id');
            $nome = in_str('nome');
            if ($nome === '') {
                v2_error('Nome do fornecedor e obrigatorio.', 'VALIDATION');
            }
            $cnpj = in_str('cnpj_cpf');
            $telefone = in_str('telefone');
            $email = in_str('email');
            $endereco = in_str('endereco');
            $status = in_str('status', 'Ativo');

            if ($id) {
                $sql = 'UPDATE censura_estoque_fornecedores SET nome=?, cnpj_cpf=?, telefone=?, email=?, endereco=?, status=? WHERE id=?';
                $pdo->prepare($sql)->execute([$nome, $cnpj ?: null, $telefone ?: null, $email ?: null, $endereco ?: null, $status, $id]);
                v2_ok(['message' => 'Fornecedor atualizado com sucesso.']);
            }

            $sql = 'INSERT INTO censura_estoque_fornecedores (nome, cnpj_cpf, telefone, email, endereco, status) VALUES (?, ?, ?, ?, ?, ?)';
            $pdo->prepare($sql)->execute([$nome, $cnpj ?: null, $telefone ?: null, $email ?: null, $endereco ?: null, $status]);
            v2_ok(['message' => 'Fornecedor cadastrado com sucesso.']);
        }

        if ($action === 'fornecedor_inativar') {
            require_write_permission();
            $id = in_int_or_null('id');
            if (!$id) {
                v2_error('Fornecedor nao informado.', 'VALIDATION');
            }
            $pdo->prepare("UPDATE censura_estoque_fornecedores SET status = 'Inativo' WHERE id = ?")->execute([$id]);
            v2_ok(['message' => 'Fornecedor inativado com sucesso.']);
        }

        if ($action === 'estoque_listar') {
            $rows = $pdo->query('SELECT * FROM censura_estoque_estoques ORDER BY status DESC, nome')->fetchAll();
            v2_ok(['data' => $rows]);
        }
        if ($action === 'estoque_salvar') {
            require_write_permission();
            $nome = in_str('nome');
            $descricao = in_str('descricao');
            $tipo = in_str('tipo', 'Geral');
            $cap = max(0, (int) in_str('capacidade_maxima', '0'));
            if ($nome === '') {
                v2_error('Nome do estoque e obrigatorio.', 'VALIDATION');
            }
            $sql = 'INSERT INTO censura_estoque_estoques (nome, descricao, tipo, capacidade_maxima, status) VALUES (?, ?, ?, ?, "Ativo")';
            $pdo->prepare($sql)->execute([$nome, $descricao ?: null, $tipo, $cap]);
            v2_ok(['message' => 'Estoque cadastrado com sucesso.']);
        }

        if ($action === 'estoque_editar') {
            require_write_permission();
            $id = in_int_or_null('id');
            $nome = in_str('nome');
            $descricao = in_str('descricao');
            $tipo = in_str('tipo', 'Geral');
            $cap = max(0, (int) in_str('capacidade_maxima', '0'));
            $status = in_str('status', 'Ativo');
            if (!$id || $nome === '') {
                v2_error('Dados invalidos para estoque.', 'VALIDATION');
            }
            $sql = 'UPDATE censura_estoque_estoques SET nome=?, descricao=?, tipo=?, capacidade_maxima=?, status=? WHERE id=?';
            $pdo->prepare($sql)->execute([$nome, $descricao ?: null, $tipo, $cap, $status, $id]);
            v2_ok(['message' => 'Estoque atualizado com sucesso.']);
        }

        if ($action === 'estoque_inativar') {
            require_write_permission();
            $id = in_int_or_null('id');
            if (!$id) {
                v2_error('Estoque nao informado.', 'VALIDATION');
            }
            $pdo->prepare("UPDATE censura_estoque_estoques SET status = 'Inativo' WHERE id = ?")->execute([$id]);
            v2_ok(['message' => 'Estoque inativado com sucesso.']);
        }

        if ($action === 'produto_listar') {
            $sql = "SELECT p.*, t.nome AS tipo_nome, f.nome AS fornecedor_nome, e.nome AS estoque_nome,
                           (SELECT COUNT(*) FROM censura_estoque_produto_variantes v WHERE v.id_produto = p.id AND v.status = 'Ativo') AS total_variantes
                    FROM censura_estoque_produtos p
                    LEFT JOIN censura_estoque_tipos t ON t.id = p.id_tipo
                    LEFT JOIN censura_estoque_fornecedores f ON f.id = p.id_fornecedor
                    LEFT JOIN censura_estoque_estoques e ON e.id = p.id_estoque
                    ORDER BY p.status DESC, p.nome";
            v2_ok(['data' => $pdo->query($sql)->fetchAll()]);
        }

        if ($action === 'produto_obter') {
            $id = in_int_or_null('id');
            if (!$id) {
                v2_error('Produto nao informado.', 'VALIDATION');
            }
            $stmt = $pdo->prepare('SELECT * FROM censura_estoque_produtos WHERE id = ?');
            $stmt->execute([$id]);
            $produto = $stmt->fetch();
            if (!$produto) {
                v2_error('Produto nao encontrado.', 'NOT_FOUND');
            }
            $stmtVar = $pdo->prepare("SELECT v.*, COALESCE(sv.quantidade_atual,0) AS quantidade_atual
                                      FROM censura_estoque_produto_variantes v
                                      LEFT JOIN censura_estoque_saldo_variantes sv ON sv.id_variante = v.id
                                      WHERE v.id_produto = ?
                                      ORDER BY v.status DESC, v.cor, v.tamanho");
            $stmtVar->execute([$id]);
            v2_ok(['data' => ['produto' => $produto, 'variantes' => $stmtVar->fetchAll()]]);
        }

        if ($action === 'produto_salvar') {
            require_write_permission();
            $id = in_int_or_null('id');
            $nome = in_str('nome');
            $descricao = in_str('descricao');
            $idTipo = in_int_or_null('id_tipo');
            $idFornecedor = in_int_or_null('id_fornecedor');
            $idEstoque = in_int_or_null('id_estoque');
            $unidade = in_str('unidade_medida', 'un');
            $status = in_str('status', 'Ativo');
            $qMin = max(0, (int) in_str('quantidade_minima', '0'));
            $qAlerta = max(0, (int) in_str('quantidade_alerta', '0'));
            $localizacao = in_str('localizacao');

            if ($nome === '' || !$idTipo) {
                v2_error('Nome e tipo do produto sao obrigatorios.', 'VALIDATION');
            }

            if ($id) {
                $sql = "UPDATE censura_estoque_produtos
                        SET nome=?, descricao=?, id_tipo=?, id_fornecedor=?, id_estoque=?, localizacao=?, unidade_medida=?,
                            quantidade_minima=?, quantidade_alerta=?, status=?, atualizado_por=?
                        WHERE id=?";
                $pdo->prepare($sql)->execute([
                    $nome, $descricao ?: null, $idTipo, $idFornecedor, $idEstoque, $localizacao ?: null, $unidade,
                    $qMin, $qAlerta, $status, (int) $_SESSION['user_id'], $id
                ]);
                v2_ok(['message' => 'Produto atualizado com sucesso.']);
            }

            $sql = "INSERT INTO censura_estoque_produtos
                    (nome, descricao, id_tipo, id_fornecedor, id_estoque, localizacao, unidade_medida, quantidade_minima, quantidade_alerta, status, criado_por)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $pdo->prepare($sql)->execute([
                $nome, $descricao ?: null, $idTipo, $idFornecedor, $idEstoque, $localizacao ?: null, $unidade,
                $qMin, $qAlerta, $status, (int) $_SESSION['user_id']
            ]);
            $newId = (int) $pdo->lastInsertId();
            $pdo->prepare('INSERT INTO censura_estoque_saldo (id_produto, quantidade_atual, atualizado_por) VALUES (?, 0, ?)')
                ->execute([$newId, (int) $_SESSION['user_id']]);
            v2_ok(['message' => 'Produto cadastrado com sucesso.', 'data' => ['id' => $newId]]);
        }

        if ($action === 'produto_inativar') {
            require_write_permission();
            $id = in_int_or_null('id');
            if (!$id) {
                v2_error('Produto nao informado.', 'VALIDATION');
            }
            $pdo->prepare("UPDATE censura_estoque_produtos SET status = 'Inativo', atualizado_por = ? WHERE id = ?")
                ->execute([(int) $_SESSION['user_id'], $id]);
            v2_ok(['message' => 'Produto inativado com sucesso.']);
        }

        if ($action === 'variante_salvar') {
            require_write_permission();
            $id = in_int_or_null('id');
            $idProduto = in_int_or_null('id_produto');
            $cor = in_str('cor');
            $tamanho = in_str('tamanho');
            $sku = in_str('sku_interno');
            $barcode = in_str('codigo_barras');
            $qMin = max(0, (int) in_str('quantidade_minima', '0'));
            $qAlerta = max(0, (int) in_str('quantidade_alerta', '0'));
            $status = in_str('status', 'Ativo');

            if (!$idProduto || $cor === '' || $tamanho === '') {
                v2_error('Produto, cor e tamanho sao obrigatorios.', 'VALIDATION');
            }

            if ($id) {
                $sql = "UPDATE censura_estoque_produto_variantes
                        SET cor=?, tamanho=?, sku_interno=?, codigo_barras=?, quantidade_minima=?, quantidade_alerta=?, status=?
                        WHERE id=?";
                $pdo->prepare($sql)->execute([$cor, $tamanho, $sku ?: null, $barcode ?: null, $qMin, $qAlerta, $status, $id]);
                v2_ok(['message' => 'Variante atualizada com sucesso.']);
            }

            $sql = "INSERT INTO censura_estoque_produto_variantes
                    (id_produto, cor, tamanho, sku_interno, codigo_barras, quantidade_minima, quantidade_alerta, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $pdo->prepare($sql)->execute([$idProduto, $cor, $tamanho, $sku ?: null, $barcode ?: null, $qMin, $qAlerta, $status]);
            $newVarId = (int) $pdo->lastInsertId();
            $pdo->prepare('INSERT INTO censura_estoque_saldo_variantes (id_variante, quantidade_atual, atualizado_por) VALUES (?, 0, ?)')
                ->execute([$newVarId, (int) $_SESSION['user_id']]);
            v2_ok(['message' => 'Variante cadastrada com sucesso.', 'data' => ['id' => $newVarId]]);
        }

        if ($action === 'variante_inativar') {
            require_write_permission();
            $id = in_int_or_null('id');
            if (!$id) {
                v2_error('Variante nao informada.', 'VALIDATION');
            }
            $pdo->prepare("UPDATE censura_estoque_produto_variantes SET status = 'Inativo' WHERE id = ?")->execute([$id]);
            v2_ok(['message' => 'Variante inativada com sucesso.']);
        }

        if ($action === 'variante_obter') {
            $id = in_int_or_null('id');
            if (!$id) {
                v2_error('Variante nao informada.', 'VALIDATION');
            }
            $stmt = $pdo->prepare('SELECT * FROM censura_estoque_produto_variantes WHERE id = ?');
            $stmt->execute([$id]);
            $variante = $stmt->fetch();
            if (!$variante) {
                v2_error('Variante nao encontrada.', 'NOT_FOUND');
            }
            v2_ok(['data' => $variante]);
        }

        if ($action === 'variante_editar') {
            require_write_permission();
            $id = in_int_or_null('id');
            $cor = in_str('cor');
            $tamanho = in_str('tamanho');
            $sku = in_str('sku_interno');
            $barcode = in_str('codigo_barras');
            $qMin = max(0, (int) in_str('quantidade_minima', '0'));
            $qAlerta = max(0, (int) in_str('quantidade_alerta', '0'));
            $status = in_str('status', 'Ativo');

            if (!$id || $cor === '' || $tamanho === '') {
                v2_error('ID, cor e tamanho sao obrigatorios.', 'VALIDATION');
            }

            $sql = "UPDATE censura_estoque_produto_variantes
                    SET cor=?, tamanho=?, sku_interno=?, codigo_barras=?, quantidade_minima=?, quantidade_alerta=?, status=?
                    WHERE id=?";
            $pdo->prepare($sql)->execute([$cor, $tamanho, $sku ?: null, $barcode ?: null, $qMin, $qAlerta, $status, $id]);
            v2_ok(['message' => 'Variante atualizada com sucesso.']);
        }

        if ($action === 'variante_herdar') {
            require_write_permission();
            $idProdutoDestino = in_int_or_null('id_produto_destino');
            $idProdutoFonte = in_int_or_null('id_produto_fonte');

            if (!$idProdutoDestino || !$idProdutoFonte) {
                v2_error('Produtos de destino e fonte sao obrigatorios.', 'VALIDATION');
            }

            if ($idProdutoDestino === $idProdutoFonte) {
                v2_error('Nao e possivel herdar variantes do mesmo produto.', 'VALIDATION');
            }

            $pdo->beginTransaction();

            // Buscar variantes do produto fonte
            $stmt = $pdo->prepare('SELECT * FROM censura_estoque_produto_variantes WHERE id_produto = ? AND status = "Ativo"');
            $stmt->execute([$idProdutoFonte]);
            $variantesFonte = $stmt->fetchAll();

            if (!$variantesFonte) {
                $pdo->rollBack();
                v2_error('Produto fonte nao possui variantes ativas.', 'NOT_FOUND');
            }

            $variantesCriadas = 0;
            foreach ($variantesFonte as $variante) {
                // Verificar se variante já existe no destino
                $stmtCheck = $pdo->prepare('SELECT COUNT(*) FROM censura_estoque_produto_variantes
                                         WHERE id_produto = ? AND cor = ? AND tamanho = ? AND status = "Ativo"');
                $stmtCheck->execute([$idProdutoDestino, $variante['cor'], $variante['tamanho']]);
                $exists = (int) $stmtCheck->fetchColumn();

                if (!$exists) {
                    // Inserir nova variante
                    $sql = "INSERT INTO censura_estoque_produto_variantes
                            (id_produto, cor, tamanho, sku_interno, codigo_barras, quantidade_minima, quantidade_alerta, status)
                            VALUES (?, ?, ?, ?, ?, ?, ?, 'Ativo')";
                    $pdo->prepare($sql)->execute([
                        $idProdutoDestino,
                        $variante['cor'],
                        $variante['tamanho'],
                        $variante['sku_interno'] ? $variante['sku_interno'] . '_HERDADO' : null,
                        $variante['codigo_barras'],
                        $variante['quantidade_minima'],
                        $variante['quantidade_alerta']
                    ]);

                    $newVarId = (int) $pdo->lastInsertId();
                    $pdo->prepare('INSERT INTO censura_estoque_saldo_variantes (id_variante, quantidade_atual, atualizado_por) VALUES (?, 0, ?)')
                        ->execute([$newVarId, (int) $_SESSION['user_id']]);

                    $variantesCriadas++;
                }
            }

            $pdo->commit();
            v2_ok(['message' => "Foram herdadas {$variantesCriadas} variantes com sucesso.", 'data' => ['criadas' => $variantesCriadas]]);
        }
        if ($action === 'saldo_variante_obter') {
            $idVar = in_int_or_null('id_variante');
            if (!$idVar) {
                v2_error('Variante nao informada.', 'VALIDATION');
            }
            $stmt = $pdo->prepare("SELECT v.id, v.quantidade_minima, v.quantidade_alerta, COALESCE(sv.quantidade_atual,0) AS quantidade_atual
                                   FROM censura_estoque_produto_variantes v
                                   LEFT JOIN censura_estoque_saldo_variantes sv ON sv.id_variante = v.id
                                   WHERE v.id = ?");
            $stmt->execute([$idVar]);
            $row = $stmt->fetch();
            if (!$row) {
                v2_error('Variante nao encontrada.', 'NOT_FOUND');
            }
            v2_ok(['data' => $row]);
        }

        if ($action === 'interno_buscar') {
            $termo = in_str('termo', in_str('ipen'));
            if ($termo === '') {
                v2_ok(['data' => []]);
            }
            $like = '%' . $termo . '%';
            $stmt = $pdo->prepare("SELECT ipen AS id, ipen, nome, nome_social
                                   FROM internos
                                   WHERE CAST(ipen AS CHAR) LIKE ? OR nome LIKE ? OR nome_social LIKE ?
                                   ORDER BY nome
                                   LIMIT 20");
            $stmt->execute([$like, $like, $like]);
            v2_ok(['data' => $stmt->fetchAll()]);
        }

        if ($action === 'movimentacao_salvar') {
            require_write_permission();
            $idProduto = in_int_or_null('id_produto');
            $idVar = in_int_or_null('id_variante');
            $tipoMov = in_str('tipo_movimentacao');
            $quantidade = max(1, (int) in_str('quantidade', '1'));
            $dataMov = in_str('data_movimentacao', date('Y-m-d'));
            $tipoDest = in_str('tipo_destino_origem', 'Outro');
            $idInterno = in_int_or_null('id_interno');
            $idFornecedor = in_int_or_null('id_fornecedor');
            $idFunc = in_str('id_funcionario');
            $destOutro = in_str('destino_origem_outro');
            $doc = in_str('documento_referencia');
            $motivo = in_str('motivo_movimentacao');
            $obs = in_str('observacoes');

            if (!$idProduto || !$idVar || !in_array($tipoMov, ['Entrada', 'Saida'], true)) {
                v2_error('Dados da movimentacao invalidos.', 'VALIDATION');
            }

            $pdo->beginTransaction();
            $stmtSaldo = $pdo->prepare('SELECT quantidade_atual FROM censura_estoque_saldo_variantes WHERE id_variante = ? FOR UPDATE');
            $stmtSaldo->execute([$idVar]);
            $saldoAtual = (int) $stmtSaldo->fetchColumn();

            if ($tipoMov === 'Saida' && $saldoAtual < $quantidade) {
                $pdo->rollBack();
                v2_error('Saldo insuficiente para a saida.', 'INSUFFICIENT_STOCK');
            }

            $sqlMov = "INSERT INTO censura_estoque_movimentacoes
                       (id_produto, id_variante, tipo_movimentacao, quantidade, data_movimentacao, tipo_destino_origem, id_interno,
                        id_funcionario, destino_origem_outro, id_fornecedor, documento_referencia, motivo_movimentacao, observacoes,
                        cadastrado_por, status)
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Ativo')";
            $pdo->prepare($sqlMov)->execute([
                $idProduto, $idVar, $tipoMov, $quantidade, $dataMov, $tipoDest, $idInterno,
                $idFunc ?: null, $destOutro ?: null, $idFornecedor, $doc ?: null, $motivo ?: null, $obs ?: null, (int) $_SESSION['user_id']
            ]);

            // Verificar se existe saldo para a variante, se não existir, criar
            $stmtCheckSaldo = $pdo->prepare('SELECT COUNT(*) FROM censura_estoque_saldo_variantes WHERE id_variante = ?');
            $stmtCheckSaldo->execute([$idVar]);
            $saldoExists = (int) $stmtCheckSaldo->fetchColumn();

            if (!$saldoExists) {
                // Criar registro de saldo para a variante
                $novoSaldo = $tipoMov === 'Entrada' ? $quantidade : 0;
                $pdo->prepare('INSERT INTO censura_estoque_saldo_variantes (id_variante, quantidade_atual, atualizado_por) VALUES (?, ?, ?)')
                    ->execute([$idVar, $novoSaldo, (int) $_SESSION['user_id']]);
            } else {
                // Atualizar saldo existente
                $signal = $tipoMov === 'Entrada' ? '+' : '-';
                $pdo->prepare("UPDATE censura_estoque_saldo_variantes SET quantidade_atual = quantidade_atual {$signal} ?, atualizado_por = ? WHERE id_variante = ?")
                    ->execute([$quantidade, (int) $_SESSION['user_id'], $idVar]);
            }

            $pdo->commit();
            v2_ok(['message' => 'Movimentacao registrada com sucesso.']);
        }

        if ($action === 'movimentacao_obter') {
            $id = in_int_or_null('id');
            if (!$id) {
                v2_error('Movimentacao nao informada.', 'VALIDATION');
            }
            $destExpr = movimentacao_destino_expr();
            $sql = "SELECT m.*, p.nome AS produto_nome, v.cor, v.tamanho, p.unidade_medida,
                           {$destExpr} AS destino_origem
                    FROM censura_estoque_movimentacoes m
                    INNER JOIN censura_estoque_produtos p ON p.id = m.id_produto
                    LEFT JOIN censura_estoque_produto_variantes v ON v.id = m.id_variante
                    LEFT JOIN internos i ON i.ipen = m.id_interno
                    LEFT JOIN censura_estoque_fornecedores f ON f.id = m.id_fornecedor
                    WHERE m.id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            if (!$row) {
                v2_error('Movimentacao nao encontrada.', 'NOT_FOUND');
            }
            v2_ok(['data' => $row]);
        }

        if ($action === 'movimentacao_editar') {
            require_write_permission();
            $id = in_int_or_null('id');
            if (!$id) {
                v2_error('Movimentacao nao informada.', 'VALIDATION');
            }
            $novoTipo = in_str('tipo_movimentacao');
            $novaQtd = max(1, (int) in_str('quantidade', '1'));
            $novaData = in_str('data_movimentacao', date('Y-m-d'));
            $novoMotivo = in_str('motivo_movimentacao');
            $novaObs = in_str('observacoes');
            $novaRef = in_str('documento_referencia');

            $pdo->beginTransaction();
            $stmt = $pdo->prepare('SELECT * FROM censura_estoque_movimentacoes WHERE id = ? FOR UPDATE');
            $stmt->execute([$id]);
            $old = $stmt->fetch();
            if (!$old) {
                $pdo->rollBack();
                v2_error('Movimentacao nao encontrada.', 'NOT_FOUND');
            }
            if ($old['status'] === 'Cancelado') {
                $pdo->rollBack();
                v2_error('Nao e possivel editar movimentacao cancelada.', 'INVALID_STATE');
            }

            $stmtSaldo = $pdo->prepare('SELECT quantidade_atual FROM censura_estoque_saldo_variantes WHERE id_variante = ? FOR UPDATE');
            $stmtSaldo->execute([(int) $old['id_variante']]);
            $saldoAtual = (int) $stmtSaldo->fetchColumn();

            $deltaOld = $old['tipo_movimentacao'] === 'Entrada' ? -(int) $old['quantidade'] : (int) $old['quantidade'];
            $deltaNew = $novoTipo === 'Entrada' ? $novaQtd : -$novaQtd;
            $saldoFinal = $saldoAtual + $deltaOld + $deltaNew;
            if ($saldoFinal < 0) {
                $pdo->rollBack();
                v2_error('Saldo insuficiente apos edicao.', 'INSUFFICIENT_STOCK');
            }

            $pdo->prepare('UPDATE censura_estoque_saldo_variantes SET quantidade_atual = ?, atualizado_por = ? WHERE id_variante = ?')
                ->execute([$saldoFinal, (int) $_SESSION['user_id'], (int) $old['id_variante']]);

            $sqlUp = "UPDATE censura_estoque_movimentacoes
                      SET tipo_movimentacao=?, quantidade=?, data_movimentacao=?, motivo_movimentacao=?, observacoes=?, documento_referencia=?, editado_em=NOW(), editado_por=?
                      WHERE id=?";
            $pdo->prepare($sqlUp)->execute([$novoTipo, $novaQtd, $novaData, $novoMotivo ?: null, $novaObs ?: null, $novaRef ?: null, (int) $_SESSION['user_id'], $id]);

            $stmtNew = $pdo->prepare('SELECT * FROM censura_estoque_movimentacoes WHERE id = ?');
            $stmtNew->execute([$id]);
            $newRow = $stmtNew->fetch();
            audit_mov($pdo, $id, 'edicao', $old, $newRow, 'Edicao de movimentacao');
            $pdo->commit();
            v2_ok(['message' => 'Movimentacao editada com sucesso.']);
        }
        if ($action === 'movimentacao_cancelar') {
            require_write_permission();
            $id = in_int_or_null('id');
            if (!$id) {
                v2_error('Movimentacao nao informada.', 'VALIDATION');
            }
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('SELECT * FROM censura_estoque_movimentacoes WHERE id = ? FOR UPDATE');
            $stmt->execute([$id]);
            $mov = $stmt->fetch();
            if (!$mov) {
                $pdo->rollBack();
                v2_error('Movimentacao nao encontrada.', 'NOT_FOUND');
            }
            if ($mov['status'] === 'Cancelado') {
                $pdo->rollBack();
                v2_error('Movimentacao ja esta cancelada.', 'INVALID_STATE');
            }

            $stmtSaldo = $pdo->prepare('SELECT quantidade_atual FROM censura_estoque_saldo_variantes WHERE id_variante = ? FOR UPDATE');
            $stmtSaldo->execute([(int) $mov['id_variante']]);
            $saldoAtual = (int) $stmtSaldo->fetchColumn();

            $delta = $mov['tipo_movimentacao'] === 'Entrada' ? -(int) $mov['quantidade'] : (int) $mov['quantidade'];
            $novoSaldo = $saldoAtual + $delta;
            if ($novoSaldo < 0) {
                $pdo->rollBack();
                v2_error('Nao foi possivel cancelar: saldo ficaria negativo.', 'INSUFFICIENT_STOCK');
            }

            $pdo->prepare('UPDATE censura_estoque_saldo_variantes SET quantidade_atual = ?, atualizado_por = ? WHERE id_variante = ?')
                ->execute([$novoSaldo, (int) $_SESSION['user_id'], (int) $mov['id_variante']]);

            $pdo->prepare("UPDATE censura_estoque_movimentacoes SET status='Cancelado', editado_em=NOW(), editado_por=? WHERE id=?")
                ->execute([(int) $_SESSION['user_id'], $id]);

            $after = $mov;
            $after['status'] = 'Cancelado';
            audit_mov($pdo, $id, 'cancelamento', $mov, $after, 'Cancelamento de movimentacao');
            $pdo->commit();
            v2_ok(['message' => 'Movimentacao cancelada com sucesso.']);
        }

        if ($action === 'movimentacao_listar_paginado') {
            $page = max(1, (int) in_str('page', '1'));
            $perPage = min(100, max(10, (int) in_str('per_page', '20')));
            $offset = ($page - 1) * $perPage;
            $search = in_str('search');
            $tipo = in_str('tipo_movimentacao');
            $status = in_str('status');
            $idProduto = in_int_or_null('id_produto');
            $idVariante = in_int_or_null('id_variante');
            $idFornecedor = in_int_or_null('id_fornecedor');
            $dataInicio = in_str('data_inicio');
            $dataFim = in_str('data_fim');
            $sort = in_str('sort', 'data_movimentacao');
            $dir = strtolower(in_str('dir', 'desc')) === 'asc' ? 'ASC' : 'DESC';

            $sortMap = [
                'data_movimentacao' => 'm.data_movimentacao',
                'produto' => 'p.nome',
                'quantidade' => 'm.quantidade',
                'tipo' => 'm.tipo_movimentacao',
                'status' => 'm.status',
            ];
            $orderBy = $sortMap[$sort] ?? 'm.data_movimentacao';

            $where = ['1=1'];
            $params = [];
            if ($search !== '') {
                $where[] = '(p.nome LIKE ? OR m.motivo_movimentacao LIKE ? OR m.documento_referencia LIKE ?)';
                $params[] = "%{$search}%";
                $params[] = "%{$search}%";
                $params[] = "%{$search}%";
            }
            if ($tipo !== '') {
                $where[] = 'm.tipo_movimentacao = ?';
                $params[] = $tipo;
            }
            if ($status !== '') {
                $where[] = 'm.status = ?';
                $params[] = $status;
            }
            if ($idProduto) {
                $where[] = 'm.id_produto = ?';
                $params[] = $idProduto;
            }
            if ($idVariante) {
                $where[] = 'm.id_variante = ?';
                $params[] = $idVariante;
            }
            if ($idFornecedor) {
                $where[] = 'm.id_fornecedor = ?';
                $params[] = $idFornecedor;
            }
            if ($dataInicio !== '') {
                $where[] = 'm.data_movimentacao >= ?';
                $params[] = $dataInicio;
            }
            if ($dataFim !== '') {
                $where[] = 'm.data_movimentacao <= ?';
                $params[] = $dataFim;
            }

            $destExpr = movimentacao_destino_expr();
            $base = "FROM censura_estoque_movimentacoes m
                     INNER JOIN censura_estoque_produtos p ON p.id = m.id_produto
                     LEFT JOIN censura_estoque_produto_variantes v ON v.id = m.id_variante
                     LEFT JOIN internos i ON i.ipen = m.id_interno
                     LEFT JOIN censura_estoque_fornecedores f ON f.id = m.id_fornecedor
                     WHERE " . implode(' AND ', $where);

            $stmtCount = $pdo->prepare("SELECT COUNT(*) {$base}");
            $stmtCount->execute($params);
            $total = (int) $stmtCount->fetchColumn();

            $sql = "SELECT m.*, p.nome AS produto_nome, p.unidade_medida, v.cor, v.tamanho, f.nome AS fornecedor_nome,
                           {$destExpr} AS destino_origem
                    {$base}
                    ORDER BY {$orderBy} {$dir}, m.id DESC
                    LIMIT {$perPage} OFFSET {$offset}";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            v2_ok(['data' => $stmt->fetchAll(), 'meta' => ['page' => $page, 'per_page' => $perPage, 'total' => $total]]);
        }

        if ($action === 'dashboard_contadores') {
            $totalProdutos = (int) $pdo->query("SELECT COUNT(*) FROM censura_estoque_produtos WHERE status='Ativo'")->fetchColumn();
            $totalVariantes = (int) $pdo->query("SELECT COUNT(*) FROM censura_estoque_produto_variantes WHERE status='Ativo'")->fetchColumn();
            $itensAlerta = (int) $pdo->query("SELECT COUNT(*) FROM censura_estoque_produto_variantes v LEFT JOIN censura_estoque_saldo_variantes sv ON sv.id_variante=v.id WHERE v.status='Ativo' AND COALESCE(sv.quantidade_atual,0) <= v.quantidade_alerta")->fetchColumn();
            $itensCritico = (int) $pdo->query("SELECT COUNT(*) FROM censura_estoque_produto_variantes v LEFT JOIN censura_estoque_saldo_variantes sv ON sv.id_variante=v.id WHERE v.status='Ativo' AND COALESCE(sv.quantidade_atual,0) <= v.quantidade_minima")->fetchColumn();
            $entradasHoje = (int) $pdo->query("SELECT COUNT(*) FROM censura_estoque_movimentacoes WHERE status='Ativo' AND tipo_movimentacao='Entrada' AND DATE(data_movimentacao)=CURDATE()")->fetchColumn();
            $saidasHoje = (int) $pdo->query("SELECT COUNT(*) FROM censura_estoque_movimentacoes WHERE status='Ativo' AND tipo_movimentacao='Saida' AND DATE(data_movimentacao)=CURDATE()")->fetchColumn();
            v2_ok(['data' => compact('totalProdutos', 'totalVariantes', 'itensAlerta', 'itensCritico', 'entradasHoje', 'saidasHoje')]);
        }

        if ($action === 'card_produtos_listar') {
            $sql = "SELECT p.*, t.nome AS tipo_nome, f.nome AS fornecedor_nome, e.nome AS estoque_nome,
                           (SELECT COUNT(*) FROM censura_estoque_produto_variantes v WHERE v.id_produto = p.id AND v.status = 'Ativo') AS total_variantes,
                           (SELECT COALESCE(SUM(sv.quantidade_atual),0) FROM censura_estoque_produto_variantes v
                            LEFT JOIN censura_estoque_saldo_variantes sv ON sv.id_variante = v.id
                            WHERE v.id_produto = p.id AND v.status = 'Ativo') AS saldo_total
                    FROM censura_estoque_produtos p
                    LEFT JOIN censura_estoque_tipos t ON t.id = p.id_tipo
                    LEFT JOIN censura_estoque_fornecedores f ON f.id = p.id_fornecedor
                    LEFT JOIN censura_estoque_estoques e ON e.id = p.id_estoque
                    WHERE p.status = 'Ativo'
                    ORDER BY p.nome";
            v2_ok(['data' => $pdo->query($sql)->fetchAll()]);
        }

        if ($action === 'card_variantes_listar') {
            $sql = "SELECT v.*, p.nome AS produto_nome, p.unidade_medida, t.nome AS tipo_nome,
                           COALESCE(sv.quantidade_atual,0) AS quantidade_atual,
                           CASE
                               WHEN COALESCE(sv.quantidade_atual,0) <= v.quantidade_minima THEN 'Crítico'
                               WHEN COALESCE(sv.quantidade_atual,0) <= v.quantidade_alerta THEN 'Alerta'
                               ELSE 'Normal'
                           END AS status_saldo
                    FROM censura_estoque_produto_variantes v
                    INNER JOIN censura_estoque_produtos p ON p.id = v.id_produto
                    LEFT JOIN censura_estoque_tipos t ON t.id = p.id_tipo
                    LEFT JOIN censura_estoque_saldo_variantes sv ON sv.id_variante = v.id
                    WHERE v.status = 'Ativo'
                    ORDER BY p.nome, v.cor, v.tamanho";
            v2_ok(['data' => $pdo->query($sql)->fetchAll()]);
        }

        if ($action === 'card_alerta_listar') {
            $sql = "SELECT v.*, p.nome AS produto_nome, p.unidade_medida, t.nome AS tipo_nome,
                           COALESCE(sv.quantidade_atual,0) AS quantidade_atual,
                           v.quantidade_alerta, v.quantidade_minima
                    FROM censura_estoque_produto_variantes v
                    INNER JOIN censura_estoque_produtos p ON p.id = v.id_produto
                    LEFT JOIN censura_estoque_tipos t ON t.id = p.id_tipo
                    LEFT JOIN censura_estoque_saldo_variantes sv ON sv.id_variante = v.id
                    WHERE v.status = 'Ativo' AND COALESCE(sv.quantidade_atual,0) <= v.quantidade_alerta
                    ORDER BY COALESCE(sv.quantidade_atual,0) ASC";
            v2_ok(['data' => $pdo->query($sql)->fetchAll()]);
        }

        if ($action === 'card_critico_listar') {
            $sql = "SELECT v.*, p.nome AS produto_nome, p.unidade_medida, t.nome AS tipo_nome,
                           COALESCE(sv.quantidade_atual,0) AS quantidade_atual,
                           v.quantidade_alerta, v.quantidade_minima
                    FROM censura_estoque_produto_variantes v
                    INNER JOIN censura_estoque_produtos p ON p.id = v.id_produto
                    LEFT JOIN censura_estoque_tipos t ON t.id = p.id_tipo
                    LEFT JOIN censura_estoque_saldo_variantes sv ON sv.id_variante = v.id
                    WHERE v.status = 'Ativo' AND COALESCE(sv.quantidade_atual,0) <= v.quantidade_minima
                    ORDER BY COALESCE(sv.quantidade_atual,0) ASC";
            v2_ok(['data' => $pdo->query($sql)->fetchAll()]);
        }

        if ($action === 'card_entradas_hoje_listar') {
            $sql = "SELECT m.*, p.nome AS produto_nome, v.cor, v.tamanho, p.unidade_medida, f.nome AS fornecedor_nome
                    FROM censura_estoque_movimentacoes m
                    INNER JOIN censura_estoque_produtos p ON p.id = m.id_produto
                    LEFT JOIN censura_estoque_produto_variantes v ON v.id = m.id_variante
                    LEFT JOIN censura_estoque_fornecedores f ON f.id = m.id_fornecedor
                    WHERE m.status='Ativo' AND m.tipo_movimentacao='Entrada' AND DATE(m.data_movimentacao)=CURDATE()
                    ORDER BY m.data_movimentacao DESC, m.id DESC";
            v2_ok(['data' => $pdo->query($sql)->fetchAll()]);
        }

        if ($action === 'card_saidas_hoje_listar') {
            $destExpr = movimentacao_destino_expr();
            $sql = "SELECT m.*, p.nome AS produto_nome, v.cor, v.tamanho, p.unidade_medida,
                           {$destExpr} AS destino_origem, m.motivo_movimentacao
                    FROM censura_estoque_movimentacoes m
                    INNER JOIN censura_estoque_produtos p ON p.id = m.id_produto
                    LEFT JOIN censura_estoque_produto_variantes v ON v.id = m.id_variante
                    LEFT JOIN internos i ON i.ipen = m.id_interno
                    LEFT JOIN censura_estoque_fornecedores f ON f.id = m.id_fornecedor
                    WHERE m.status='Ativo' AND m.tipo_movimentacao='Saida' AND DATE(m.data_movimentacao)=CURDATE()
                    ORDER BY m.data_movimentacao DESC, m.id DESC";
            v2_ok(['data' => $pdo->query($sql)->fetchAll()]);
        }

        // Incluir função de geração de ofício e configuração
require_once __DIR__ . '/gerar_oficio_modelo.php';
require_once __DIR__ . '/config_oficio_modelo.php';

        if ($action === 'dashboard_almoxarifado') {
            require_write_permission();

            // Dados gerais do estoque
            $sqlKPIs = "SELECT
                           COUNT(DISTINCT p.id) AS total_produtos,
                           COUNT(DISTINCT v.id) AS total_variantes,
                           COALESCE(SUM(sv.quantidade_atual),0) AS saldo_total,
                           COUNT(CASE WHEN sv.quantidade_atual <= v.quantidade_alerta THEN 1 END) AS itens_alerta,
                           COUNT(CASE WHEN sv.quantidade_atual <= v.quantidade_minima THEN 1 END) AS itens_critico
                    FROM censura_estoque_produtos p
                    LEFT JOIN censura_estoque_produto_variantes v ON v.id_produto = p.id AND v.status = 'Ativo'
                    LEFT JOIN censura_estoque_saldo_variantes sv ON sv.id_variante = v.id
                    WHERE p.status = 'Ativo'";
            $kpis = $pdo->query($sqlKPIs)->fetch();

            // Movimentações dos últimos 30 dias
            $sqlMovimentacoes = "SELECT
                                   DATE(m.data_movimentacao) AS data,
                                   COUNT(*) AS total_movimentacoes,
                                   SUM(CASE WHEN m.tipo_movimentacao = 'Entrada' THEN m.quantidade ELSE 0 END) AS entradas_dia,
                                   SUM(CASE WHEN m.tipo_movimentacao = 'Saida' THEN m.quantidade ELSE 0 END) AS saidas_dia
                            FROM censura_estoque_movimentacoes m
                            WHERE m.status = 'Ativo'
                            AND m.data_movimentacao >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                            GROUP BY DATE(m.data_movimentacao)
                            ORDER BY DATE(m.data_movimentacao)";
            $movimentacoes = $pdo->query($sqlMovimentacoes)->fetchAll();

            // Top 10 produtos mais movimentados
            $sqlTopProdutos = "SELECT
                                   p.nome AS produto_nome,
                                   COUNT(m.id) AS total_movimentacoes,
                                   SUM(CASE WHEN m.tipo_movimentacao = 'Entrada' THEN m.quantidade ELSE 0 END) AS total_entradas,
                                   SUM(CASE WHEN m.tipo_movimentacao = 'Saida' THEN m.quantidade ELSE 0 END) AS total_saidas
                            FROM censura_estoque_movimentacoes m
                            INNER JOIN censura_estoque_produtos p ON p.id = m.id_produto
                            WHERE m.status = 'Ativo'
                            AND m.data_movimentacao >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                            GROUP BY p.id, p.nome
                            ORDER BY total_movimentacoes DESC
                            LIMIT 10";
            $topProdutos = $pdo->query($sqlTopProdutos)->fetchAll();

            // Estoque por categoria
            $sqlPorCategoria = "SELECT
                                   t.nome AS categoria,
                                   COUNT(DISTINCT p.id) AS total_produtos,
                                   COUNT(DISTINCT v.id) AS total_variantes,
                                   COALESCE(SUM(sv.quantidade_atual),0) AS saldo_atual
                            FROM censura_estoque_produtos p
                            LEFT JOIN censura_estoque_tipos t ON t.id = p.id_tipo
                            LEFT JOIN censura_estoque_produto_variantes v ON v.id_produto = p.id AND v.status = 'Ativo'
                            LEFT JOIN censura_estoque_saldo_variantes sv ON sv.id_variante = v.id
                            WHERE p.status = 'Ativo'
                            GROUP BY t.id, t.nome
                            ORDER BY saldo_atual DESC";
            $porCategoria = $pdo->query($sqlPorCategoria)->fetchAll();

            // Itens em alerta e crítico
            $sqlItensCriticos = "SELECT
                                       p.nome AS produto_nome,
                                       v.cor,
                                       v.tamanho,
                                       sv.quantidade_atual,
                                       v.quantidade_alerta,
                                       v.quantidade_minima,
                                       t.nome AS categoria
                                FROM censura_estoque_produto_variantes v
                                INNER JOIN censura_estoque_produtos p ON p.id = v.id_produto
                                LEFT JOIN censura_estoque_tipos t ON t.id = p.id_tipo
                                LEFT JOIN censura_estoque_saldo_variantes sv ON sv.id_variante = v.id
                                WHERE v.status = 'Ativo'
                                AND sv.quantidade_atual <= v.quantidade_minima
                                ORDER BY sv.quantidade_atual ASC
                                LIMIT 10";
            $itensCriticos = $pdo->query($sqlItensCriticos)->fetchAll();

            v2_ok([
                'data' => [
                    'kpis' => $kpis,
                    'movimentacoes' => $movimentacoes,
                    'top_produtos' => $topProdutos,
                    'por_categoria' => $porCategoria,
                    'itens_criticos' => $itensCriticos
                ]
            ]);
        }

        if ($action === 'gerar_oficio_estoque') {
            require_write_permission();

            $dataInicio = in_str('data_inicio', date('Y-m-01'));
            $dataFim = in_str('data_fim', date('Y-m-t'));
            $mesAno = date('m/Y', strtotime($dataInicio));

            // Buscar dados do período
            $sqlEntradas = "SELECT COUNT(*) AS total, COALESCE(SUM(m.quantidade),0) AS quantidade_total
                           FROM censura_estoque_movimentacoes m
                           WHERE m.status='Ativo' AND m.tipo_movimentacao='Entrada'
                           AND m.data_movimentacao BETWEEN ? AND ?";
            $stmtEntradas = $pdo->prepare($sqlEntradas);
            $stmtEntradas->execute([$dataInicio, $dataFim]);
            $entradas = $stmtEntradas->fetch();

            $sqlSaidas = "SELECT COUNT(*) AS total, COALESCE(SUM(m.quantidade),0) AS quantidade_total
                         FROM censura_estoque_movimentacoes m
                         WHERE m.status='Ativo' AND m.tipo_movimentacao='Saida'
                         AND m.data_movimentacao BETWEEN ? AND ?";
            $stmtSaidas = $pdo->prepare($sqlSaidas);
            $stmtSaidas->execute([$dataInicio, $dataFim]);
            $saidas = $stmtSaidas->fetch();

            // Buscar produtos por categoria
            $sqlCategorias = "SELECT
                                p.nome AS produto_nome,
                                t.nome AS tipo_nome,
                                COALESCE(SUM(CASE WHEN m.tipo_movimentacao='Entrada' THEN m.quantidade ELSE 0 END),0) AS entradas,
                                COALESCE(SUM(CASE WHEN m.tipo_movimentacao='Saida' THEN m.quantidade ELSE 0 END),0) AS saidas,
                                COALESCE(SUM(CASE WHEN m.tipo_movimentacao='Entrada' THEN m.quantidade ELSE 0 END),0) -
                                COALESCE(SUM(CASE WHEN m.tipo_movimentacao='Saida' THEN m.quantidade ELSE 0 END),0) AS saldo
                            FROM censura_estoque_produtos p
                            LEFT JOIN censura_estoque_tipos t ON t.id = p.id_tipo
                            LEFT JOIN censura_estoque_movimentacoes m ON m.id_produto = p.id AND m.status='Ativo'
                                AND m.data_movimentacao BETWEEN ? AND ?
                            WHERE p.status = 'Ativo'
                            GROUP BY p.id, p.nome, t.nome
                            ORDER BY t.nome, p.nome";
            $stmtCategorias = $pdo->prepare($sqlCategorias);
            $stmtCategorias->execute([$dataInicio, $dataFim]);
            $categorias = $stmtCategorias->fetchAll();

            // Buscar itens em alerta e crítico
            $sqlAlerta = "SELECT v.*, p.nome AS produto_nome, p.unidade_medida, t.nome AS tipo_nome,
                           COALESCE(sv.quantidade_atual,0) AS quantidade_atual,
                           v.quantidade_alerta, v.quantidade_minima
                    FROM censura_estoque_produto_variantes v
                    INNER JOIN censura_estoque_produtos p ON p.id = v.id_produto
                    LEFT JOIN censura_estoque_tipos t ON t.id = p.id_tipo
                    LEFT JOIN censura_estoque_saldo_variantes sv ON sv.id_variante = v.id
                    WHERE v.status = 'Ativo' AND COALESCE(sv.quantidade_atual,0) <= v.quantidade_alerta
                    ORDER BY COALESCE(sv.quantidade_atual,0) ASC";
            $itensAlerta = $pdo->query($sqlAlerta)->fetchAll();

            $sqlCritico = "SELECT v.*, p.nome AS produto_nome, p.unidade_medida, t.nome AS tipo_nome,
                            COALESCE(sv.quantidade_atual,0) AS quantidade_atual,
                            v.quantidade_alerta, v.quantidade_minima
                    FROM censura_estoque_produto_variantes v
                    INNER JOIN censura_estoque_produtos p ON p.id = v.id_produto
                    LEFT JOIN censura_estoque_tipos t ON t.id = p.id_tipo
                    LEFT JOIN censura_estoque_saldo_variantes sv ON sv.id_variante = v.id
                    WHERE v.status = 'Ativo' AND COALESCE(sv.quantidade_atual,0) <= v.quantidade_minima
                    ORDER BY COALESCE(sv.quantidade_atual,0) ASC";
            $itensCritico = $pdo->query($sqlCritico)->fetchAll();

            // Usar dados da configuração
            $dadosPresidio = include __DIR__ . '/config_oficio.php';

            // Gerar HTML do ofício
            $oficioHTML = gerarHTMLoficioModelo($mesAno, $dadosPresidio, $entradas, $saidas, $categorias, $itensAlerta, $itensCritico, $dataInicio, $dataFim);

            v2_ok(['data' => ['html' => $oficioHTML, 'mes_ano' => $mesAno]]);
        }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['db_action'])) {
            $dataInicio = in_str('data_inicio', date('Y-m-01'));
            $dataFim = in_str('data_fim', date('Y-m-d'));

            if ($action === 'relatorio_entradas') {
                $sql = "SELECT m.data_movimentacao, p.nome AS produto_nome, v.cor, v.tamanho, m.quantidade, p.unidade_medida, f.nome AS fornecedor_nome
                        FROM censura_estoque_movimentacoes m
                        INNER JOIN censura_estoque_produtos p ON p.id = m.id_produto
                        LEFT JOIN censura_estoque_produto_variantes v ON v.id = m.id_variante
                        LEFT JOIN censura_estoque_fornecedores f ON f.id = m.id_fornecedor
                        WHERE m.status='Ativo' AND m.tipo_movimentacao='Entrada' AND m.data_movimentacao BETWEEN ? AND ?
                        ORDER BY m.data_movimentacao DESC, m.id DESC";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$dataInicio, $dataFim]);
                v2_ok(['data' => $stmt->fetchAll()]);
            }

            if ($action === 'relatorio_saidas') {
                $destExpr = movimentacao_destino_expr();
                $sql = "SELECT m.data_movimentacao, p.nome AS produto_nome, v.cor, v.tamanho, m.quantidade, p.unidade_medida,
                               {$destExpr} AS destino_origem, m.motivo_movimentacao
                        FROM censura_estoque_movimentacoes m
                        INNER JOIN censura_estoque_produtos p ON p.id = m.id_produto
                        LEFT JOIN censura_estoque_produto_variantes v ON v.id = m.id_variante
                        LEFT JOIN internos i ON i.ipen = m.id_interno
                        LEFT JOIN censura_estoque_fornecedores f ON f.id = m.id_fornecedor
                        WHERE m.status='Ativo' AND m.tipo_movimentacao='Saida' AND m.data_movimentacao BETWEEN ? AND ?
                        ORDER BY m.data_movimentacao DESC, m.id DESC";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$dataInicio, $dataFim]);
                v2_ok(['data' => $stmt->fetchAll()]);
            }

            if ($action === 'relatorio_estoque_baixo') {
                $sql = "SELECT p.nome AS produto_nome, v.cor, v.tamanho, COALESCE(sv.quantidade_atual,0) AS saldo_atual, v.quantidade_alerta, v.quantidade_minima, p.unidade_medida
                        FROM censura_estoque_produto_variantes v
                        INNER JOIN censura_estoque_produtos p ON p.id = v.id_produto
                        LEFT JOIN censura_estoque_saldo_variantes sv ON sv.id_variante = v.id
                        WHERE v.status='Ativo' AND COALESCE(sv.quantidade_atual,0) <= v.quantidade_alerta
                        ORDER BY COALESCE(sv.quantidade_atual,0) ASC";
                v2_ok(['data' => $pdo->query($sql)->fetchAll()]);
            }

            if ($action === 'relatorio_sem_reposicao') {
                $sql = "SELECT p.nome AS produto_nome, v.cor, v.tamanho, MAX(CASE WHEN m.tipo_movimentacao='Entrada' AND m.status='Ativo' THEN m.data_movimentacao END) AS ultima_entrada,
                               DATEDIFF(CURDATE(), MAX(CASE WHEN m.tipo_movimentacao='Entrada' AND m.status='Ativo' THEN m.data_movimentacao END)) AS dias_sem_reposicao
                        FROM censura_estoque_produto_variantes v
                        INNER JOIN censura_estoque_produtos p ON p.id = v.id_produto
                        LEFT JOIN censura_estoque_movimentacoes m ON m.id_variante = v.id
                        WHERE v.status='Ativo'
                        GROUP BY v.id, p.nome, v.cor, v.tamanho
                        ORDER BY (ultima_entrada IS NULL) DESC, dias_sem_reposicao DESC";
                v2_ok(['data' => $pdo->query($sql)->fetchAll()]);
            }

            if ($action === 'relatorio_giro_produtos') {
                $sql = "SELECT p.nome AS produto_nome, v.cor, v.tamanho,
                               SUM(CASE WHEN m.tipo_movimentacao='Entrada' AND m.status='Ativo' THEN m.quantidade ELSE 0 END) AS total_entradas,
                               SUM(CASE WHEN m.tipo_movimentacao='Saida' AND m.status='Ativo' THEN m.quantidade ELSE 0 END) AS total_saidas
                        FROM censura_estoque_produto_variantes v
                        INNER JOIN censura_estoque_produtos p ON p.id = v.id_produto
                        LEFT JOIN censura_estoque_movimentacoes m ON m.id_variante = v.id AND m.data_movimentacao BETWEEN ? AND ?
                        WHERE v.status='Ativo'
                        GROUP BY v.id, p.nome, v.cor, v.tamanho
                        ORDER BY total_saidas DESC, total_entradas DESC";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$dataInicio, $dataFim]);
                v2_ok(['data' => $stmt->fetchAll()]);
            }

            $stmtEntradas = $pdo->prepare("SELECT COUNT(*) AS qtd, COALESCE(SUM(quantidade),0) AS total FROM censura_estoque_movimentacoes WHERE status='Ativo' AND tipo_movimentacao='Entrada' AND data_movimentacao BETWEEN ? AND ?");
            $stmtEntradas->execute([$dataInicio, $dataFim]);
            $entr = $stmtEntradas->fetch();
            $stmtSaidas = $pdo->prepare("SELECT COUNT(*) AS qtd, COALESCE(SUM(quantidade),0) AS total FROM censura_estoque_movimentacoes WHERE status='Ativo' AND tipo_movimentacao='Saida' AND data_movimentacao BETWEEN ? AND ?");
            $stmtSaidas->execute([$dataInicio, $dataFim]);
            $sai = $stmtSaidas->fetch();
            v2_ok(['data' => ['entradas' => $entr, 'saidas' => $sai, 'periodo' => ['inicio' => $dataInicio, 'fim' => $dataFim]]]);
        }

        v2_error('Acao nao reconhecida.', 'UNKNOWN_ACTION');
    } catch (Throwable $e) {
        v2_error('Falha ao processar requisicao.', 'SERVER_ERROR', ['details' => $e->getMessage()]);
    }
}

try {
    $tipos = $pdo->query("SELECT * FROM censura_estoque_tipos WHERE status='Ativo' ORDER BY nome")->fetchAll();
    $fornecedores = $pdo->query("SELECT * FROM censura_estoque_fornecedores WHERE status='Ativo' ORDER BY nome")->fetchAll();
    $estoques = $pdo->query("SELECT * FROM censura_estoque_estoques WHERE status='Ativo' ORDER BY nome")->fetchAll();
    $produtos = $pdo->query("SELECT p.*, t.nome AS tipo_nome FROM censura_estoque_produtos p LEFT JOIN censura_estoque_tipos t ON t.id=p.id_tipo WHERE p.status='Ativo' ORDER BY p.nome")->fetchAll();
    $contadores = [
        'totalProdutos' => (int) $pdo->query("SELECT COUNT(*) FROM censura_estoque_produtos WHERE status='Ativo'")->fetchColumn(),
        'totalVariantes' => (int) $pdo->query("SELECT COUNT(*) FROM censura_estoque_produto_variantes WHERE status='Ativo'")->fetchColumn(),
        'itensAlerta' => (int) $pdo->query("SELECT COUNT(*) FROM censura_estoque_produto_variantes v LEFT JOIN censura_estoque_saldo_variantes sv ON sv.id_variante=v.id WHERE v.status='Ativo' AND COALESCE(sv.quantidade_atual,0) <= v.quantidade_alerta")->fetchColumn(),
        'itensCritico' => (int) $pdo->query("SELECT COUNT(*) FROM censura_estoque_produto_variantes v LEFT JOIN censura_estoque_saldo_variantes sv ON sv.id_variante=v.id WHERE v.status='Ativo' AND COALESCE(sv.quantidade_atual,0) <= v.quantidade_minima")->fetchColumn(),
        'entradasHoje' => (int) $pdo->query("SELECT COUNT(*) FROM censura_estoque_movimentacoes WHERE status='Ativo' AND tipo_movimentacao='Entrada' AND DATE(data_movimentacao)=CURDATE()")->fetchColumn(),
        'saidasHoje' => (int) $pdo->query("SELECT COUNT(*) FROM censura_estoque_movimentacoes WHERE status='Ativo' AND tipo_movimentacao='Saida' AND DATE(data_movimentacao)=CURDATE()")->fetchColumn(),
    ];
} catch (Throwable $e) {
    $tipos = [];
    $fornecedores = [];
    $estoques = [];
    $produtos = [];
    $contadores = [
        'totalProdutos' => 0,
        'totalVariantes' => 0,
        'itensAlerta' => 0,
        'itensCritico' => 0,
        'entradasHoje' => 0,
        'saidasHoje' => 0,
    ];
}
?>
