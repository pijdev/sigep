<?php
session_start();
ob_start();
date_default_timezone_set('America/Sao_Paulo');
ini_set('display_errors', 0);
error_reporting(E_ALL);
mb_internal_encoding('UTF-8');

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

function json_ok(array $data = []): void
{
    echo json_encode(array_merge(['success' => true], $data), JSON_UNESCAPED_UNICODE);
    exit;
}

function json_error(string $message, array $data = []): void
{
    echo json_encode(array_merge(['success' => false, 'message' => $message, 'error' => $message], $data), JSON_UNESCAPED_UNICODE);
    exit;
}

function req_str(string $key, string $default = ''): string
{
    return isset($_POST[$key]) ? trim((string) $_POST[$key]) : $default;
}

function req_int_or_null(string $key): ?int
{
    if (!isset($_POST[$key])) {
        return null;
    }
    $v = trim((string) $_POST[$key]);
    if ($v === '') {
        return null;
    }
    return (int) $v;
}

function has_table(PDO $pdo, string $table): bool
{
    static $cache = [];
    if (array_key_exists($table, $cache)) {
        return $cache[$table];
    }

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?');
    $stmt->execute([$table]);
    $cache[$table] = ((int) $stmt->fetchColumn()) > 0;
    return $cache[$table];
}

function has_column(PDO $pdo, string $table, string $column): bool
{
    static $cache = [];
    $k = $table . '.' . $column;
    if (array_key_exists($k, $cache)) {
        return $cache[$k];
    }

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?');
    $stmt->execute([$table, $column]);
    $cache[$k] = ((int) $stmt->fetchColumn()) > 0;
    return $cache[$k];
}

$hasEstoquesTable = has_table($pdo, 'censura_estoque_estoques');
$hasProdutoIdEstoque = has_column($pdo, 'censura_estoque_produtos', 'id_estoque');

function resolve_estoque(PDO $pdo, ?int $idEstoque, string $localizacao, bool $hasEstoquesTable, bool $hasProdutoIdEstoque): array
{
    $resolvedId = null;
    $resolvedLocal = trim($localizacao);

    if (!$hasEstoquesTable || !$hasProdutoIdEstoque) {
        return [$resolvedId, $resolvedLocal];
    }

    if ($idEstoque && $idEstoque > 0) {
        $stmt = $pdo->prepare('SELECT id, nome FROM censura_estoque_estoques WHERE id = ?');
        $stmt->execute([$idEstoque]);
        $e = $stmt->fetch();
        if ($e) {
            $resolvedId = (int) $e['id'];
            $resolvedLocal = (string) $e['nome'];
            return [$resolvedId, $resolvedLocal];
        }
    }

    if ($resolvedLocal !== '') {
        $stmt = $pdo->prepare('SELECT id, nome FROM censura_estoque_estoques WHERE TRIM(LOWER(nome)) = TRIM(LOWER(?)) LIMIT 1');
        $stmt->execute([$resolvedLocal]);
        $e = $stmt->fetch();
        if ($e) {
            $resolvedId = (int) $e['id'];
            $resolvedLocal = (string) $e['nome'];
        }
    }

    return [$resolvedId, $resolvedLocal];
}

function base_product_select(bool $hasEstoquesTable, bool $hasProdutoIdEstoque): string
{
    if ($hasEstoquesTable && $hasProdutoIdEstoque) {
        return "
            SELECT
                p.*,
                COALESCE(e.nome, p.localizacao) AS localizacao,
                e.id AS estoque_id,
                e.nome AS estoque_nome,
                e.tipo AS estoque_tipo,
                s.quantidade_atual,
                t.nome AS tipo_nome,
                f.nome AS fornecedor_nome
            FROM censura_estoque_produtos p
            LEFT JOIN censura_estoque_saldo s ON p.id = s.id_produto
            LEFT JOIN censura_estoque_tipos t ON p.id_tipo = t.id
            LEFT JOIN censura_estoque_fornecedores f ON p.id_fornecedor = f.id
            LEFT JOIN censura_estoque_estoques e ON p.id_estoque = e.id
        ";
    }

    return "
        SELECT
            p.*,
            p.localizacao,
            NULL AS estoque_id,
            NULL AS estoque_nome,
            NULL AS estoque_tipo,
            s.quantidade_atual,
            t.nome AS tipo_nome,
            f.nome AS fornecedor_nome
        FROM censura_estoque_produtos p
        LEFT JOIN censura_estoque_saldo s ON p.id = s.id_produto
        LEFT JOIN censura_estoque_tipos t ON p.id_tipo = t.id
        LEFT JOIN censura_estoque_fornecedores f ON p.id_fornecedor = f.id
    ";
}

function fmt_where_dates(array &$where, array &$params): void
{
    $dataInicio = req_str('data_inicio');
    $dataFim = req_str('data_fim');

    if ($dataInicio !== '') {
        $where[] = 'm.data_movimentacao >= ?';
        $params[] = $dataInicio;
    }
    if ($dataFim !== '') {
        $where[] = 'm.data_movimentacao <= ?';
        $params[] = $dataFim;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['db_action'])) {
    ob_clean();
    header('Content-Type: application/json; charset=utf-8');

    $action = (string) $_POST['db_action'];

    try {
        if ($action === 'salvar_produto' || $action === 'editar_produto') {
            $id = req_int_or_null('id');
            $nome = req_str('nome');
            $descricao = req_str('descricao');
            $idTipo = req_int_or_null('id_tipo');
            $idFornecedor = req_int_or_null('id_fornecedor');
            $qMin = max(0, (int) req_str('quantidade_minima', '0'));
            $qAlerta = max(0, (int) req_str('quantidade_alerta', '0'));
            $unidade = req_str('unidade_medida', 'un');
            $status = req_str('status', 'Ativo');
            $idEstoqueInput = req_int_or_null('id_estoque');
            $localizacaoInput = req_str('localizacao');

            if ($nome === '' || !$idTipo) {
                json_error('Nome e tipo do produto sao obrigatorios.');
            }

            [$idEstoque, $localizacao] = resolve_estoque($pdo, $idEstoqueInput, $localizacaoInput, $hasEstoquesTable, $hasProdutoIdEstoque);
            $userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;

            if ($action === 'salvar_produto') {
                if ($hasProdutoIdEstoque) {
                    $sql = 'INSERT INTO censura_estoque_produtos
                        (nome, descricao, id_tipo, id_fornecedor, quantidade_minima, quantidade_alerta, id_estoque, localizacao, unidade_medida, status, criado_por)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
                    $params = [$nome, $descricao ?: null, $idTipo, $idFornecedor, $qMin, $qAlerta, $idEstoque, $localizacao ?: null, $unidade, $status, $userId];
                } else {
                    $sql = 'INSERT INTO censura_estoque_produtos
                        (nome, descricao, id_tipo, id_fornecedor, quantidade_minima, quantidade_alerta, localizacao, unidade_medida, status, criado_por)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
                    $params = [$nome, $descricao ?: null, $idTipo, $idFornecedor, $qMin, $qAlerta, $localizacao ?: null, $unidade, $status, $userId];
                }
                $pdo->prepare($sql)->execute($params);
                $produtoId = (int) $pdo->lastInsertId();

                $pdo->prepare('INSERT INTO censura_estoque_saldo (id_produto, quantidade_atual, atualizado_por) VALUES (?, 0, ?)')
                    ->execute([$produtoId, $userId]);

                json_ok(['message' => 'Produto cadastrado com sucesso.']);
            }

            if (!$id) {
                json_error('ID do produto nao informado.');
            }

            if ($hasProdutoIdEstoque) {
                $sql = 'UPDATE censura_estoque_produtos SET
                    nome = ?, descricao = ?, id_tipo = ?, id_fornecedor = ?,
                    quantidade_minima = ?, quantidade_alerta = ?, id_estoque = ?, localizacao = ?,
                    unidade_medida = ?, status = ?, atualizado_por = ?
                    WHERE id = ?';
                $params = [$nome, $descricao ?: null, $idTipo, $idFornecedor, $qMin, $qAlerta, $idEstoque, $localizacao ?: null, $unidade, $status, $userId, $id];
            } else {
                $sql = 'UPDATE censura_estoque_produtos SET
                    nome = ?, descricao = ?, id_tipo = ?, id_fornecedor = ?,
                    quantidade_minima = ?, quantidade_alerta = ?, localizacao = ?,
                    unidade_medida = ?, status = ?, atualizado_por = ?
                    WHERE id = ?';
                $params = [$nome, $descricao ?: null, $idTipo, $idFornecedor, $qMin, $qAlerta, $localizacao ?: null, $unidade, $status, $userId, $id];
            }

            $pdo->prepare($sql)->execute($params);
            json_ok(['message' => 'Produto atualizado com sucesso.']);
        }

        if ($action === 'excluir_produto') {
            $id = req_int_or_null('id');
            if (!$id) {
                json_error('ID do produto nao informado.');
            }

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM censura_estoque_movimentacoes WHERE id_produto = ? AND status = 'Ativo'");
            $stmt->execute([$id]);
            if ((int) $stmt->fetchColumn() > 0) {
                json_error('Nao e possivel excluir produto com movimentacoes ativas.');
            }

            $pdo->prepare('DELETE FROM censura_estoque_saldo WHERE id_produto = ?')->execute([$id]);
            $pdo->prepare('DELETE FROM censura_estoque_produtos WHERE id = ?')->execute([$id]);
            json_ok(['message' => 'Produto excluido com sucesso.']);
        }

        if ($action === 'salvar_fornecedor') {
            $id = req_int_or_null('id');
            $nome = req_str('nome');
            $cnpjCpf = req_str('cnpj_cpf');
            $telefone = req_str('telefone');
            $email = req_str('email');
            $endereco = req_str('endereco');
            $status = req_str('status', 'Ativo');

            if ($nome === '') {
                json_error('Nome do fornecedor e obrigatorio.');
            }

            if ($id) {
                $sql = 'UPDATE censura_estoque_fornecedores SET nome = ?, cnpj_cpf = ?, telefone = ?, email = ?, endereco = ?, status = ? WHERE id = ?';
                $pdo->prepare($sql)->execute([$nome, $cnpjCpf ?: null, $telefone ?: null, $email ?: null, $endereco ?: null, $status, $id]);
                json_ok(['message' => 'Fornecedor atualizado com sucesso.']);
            }

            $sql = 'INSERT INTO censura_estoque_fornecedores (nome, cnpj_cpf, telefone, email, endereco, status) VALUES (?, ?, ?, ?, ?, ?)';
            $pdo->prepare($sql)->execute([$nome, $cnpjCpf ?: null, $telefone ?: null, $email ?: null, $endereco ?: null, $status]);
            json_ok(['message' => 'Fornecedor cadastrado com sucesso.']);
        }

        if ($action === 'load_fornecedor') {
            $id = req_int_or_null('id');
            if (!$id) {
                json_error('ID do fornecedor nao informado.');
            }

            $stmt = $pdo->prepare('SELECT * FROM censura_estoque_fornecedores WHERE id = ?');
            $stmt->execute([$id]);
            $fornecedor = $stmt->fetch();
            if (!$fornecedor) {
                json_error('Fornecedor nao encontrado.');
            }

            json_ok(['fornecedor' => $fornecedor]);
        }

        if ($action === 'excluir_fornecedor') {
            $id = req_int_or_null('id');
            if (!$id) {
                json_error('ID do fornecedor nao informado.');
            }

            $stmt = $pdo->prepare('SELECT COUNT(*) FROM censura_estoque_produtos WHERE id_fornecedor = ?');
            $stmt->execute([$id]);
            if ((int) $stmt->fetchColumn() > 0) {
                json_error('Nao e possivel excluir fornecedor vinculado a produtos.');
            }

            $stmt = $pdo->prepare('SELECT COUNT(*) FROM censura_estoque_movimentacoes WHERE id_fornecedor = ? AND status = "Ativo"');
            $stmt->execute([$id]);
            if ((int) $stmt->fetchColumn() > 0) {
                json_error('Nao e possivel excluir fornecedor com movimentacoes ativas.');
            }

            $pdo->prepare('DELETE FROM censura_estoque_fornecedores WHERE id = ?')->execute([$id]);
            json_ok(['message' => 'Fornecedor excluido com sucesso.']);
        }
        if ($action === 'salvar_tipo') {
            $nome = req_str('nome');
            $descricao = req_str('descricao');
            if ($nome === '') {
                json_error('Nome do tipo e obrigatorio.');
            }

            $stmt = $pdo->prepare('SELECT id FROM censura_estoque_tipos WHERE TRIM(LOWER(nome)) = TRIM(LOWER(?)) LIMIT 1');
            $stmt->execute([$nome]);
            if ($stmt->fetch()) {
                json_error('Ja existe um tipo com esse nome.');
            }

            $pdo->prepare('INSERT INTO censura_estoque_tipos (nome, descricao, status) VALUES (?, ?, "Ativo")')->execute([$nome, $descricao ?: null]);
            json_ok(['message' => 'Tipo cadastrado com sucesso.']);
        }

        if ($action === 'editar_tipo') {
            $id = req_int_or_null('id');
            $nome = req_str('nome');
            $descricao = req_str('descricao');
            $status = req_str('status', 'Ativo');

            if (!$id || $nome === '') {
                json_error('Dados invalidos para atualizar tipo.');
            }

            $stmt = $pdo->prepare('SELECT id FROM censura_estoque_tipos WHERE TRIM(LOWER(nome)) = TRIM(LOWER(?)) AND id <> ? LIMIT 1');
            $stmt->execute([$nome, $id]);
            if ($stmt->fetch()) {
                json_error('Ja existe outro tipo com esse nome.');
            }

            $pdo->prepare('UPDATE censura_estoque_tipos SET nome = ?, descricao = ?, status = ? WHERE id = ?')
                ->execute([$nome, $descricao ?: null, $status, $id]);
            json_ok(['message' => 'Tipo atualizado com sucesso.']);
        }

        if ($action === 'excluir_tipo') {
            $id = req_int_or_null('id');
            if (!$id) {
                json_error('ID do tipo nao informado.');
            }

            $stmt = $pdo->prepare('SELECT COUNT(*) FROM censura_estoque_produtos WHERE id_tipo = ?');
            $stmt->execute([$id]);
            if ((int) $stmt->fetchColumn() > 0) {
                $pdo->prepare('UPDATE censura_estoque_tipos SET status = "Inativo" WHERE id = ?')->execute([$id]);
                json_ok(['message' => 'Tipo inativado pois possui produtos vinculados.']);
            }

            $pdo->prepare('DELETE FROM censura_estoque_tipos WHERE id = ?')->execute([$id]);
            json_ok(['message' => 'Tipo excluido com sucesso.']);
        }

        if ($action === 'salvar_estoque') {
            if (!$hasEstoquesTable) {
                json_error('Tabela de estoques nao existe. Execute a migracao do modulo.');
            }

            $nome = req_str('nome');
            $descricao = req_str('descricao');
            $tipo = req_str('tipo', 'Geral');
            $capacidade = max(0, (int) req_str('capacidade_maxima', '0'));
            if ($nome === '') {
                json_error('Nome do estoque e obrigatorio.');
            }

            $stmt = $pdo->prepare('SELECT id FROM censura_estoque_estoques WHERE TRIM(LOWER(nome)) = TRIM(LOWER(?)) LIMIT 1');
            $stmt->execute([$nome]);
            if ($stmt->fetch()) {
                json_error('Ja existe um estoque com esse nome.');
            }

            $pdo->prepare('INSERT INTO censura_estoque_estoques (nome, descricao, tipo, capacidade_maxima, status) VALUES (?, ?, ?, ?, "Ativo")')
                ->execute([$nome, $descricao ?: null, $tipo, $capacidade]);
            json_ok(['message' => 'Estoque cadastrado com sucesso.']);
        }

        if ($action === 'editar_estoque') {
            if (!$hasEstoquesTable) {
                json_error('Tabela de estoques nao existe. Execute a migracao do modulo.');
            }

            $id = req_int_or_null('id');
            $nome = req_str('nome');
            $descricao = req_str('descricao');
            $tipo = req_str('tipo', 'Geral');
            $capacidade = max(0, (int) req_str('capacidade_maxima', '0'));
            if (!$id || $nome === '') {
                json_error('Dados invalidos para atualizar estoque.');
            }

            $stmt = $pdo->prepare('SELECT id FROM censura_estoque_estoques WHERE TRIM(LOWER(nome)) = TRIM(LOWER(?)) AND id <> ? LIMIT 1');
            $stmt->execute([$nome, $id]);
            if ($stmt->fetch()) {
                json_error('Ja existe outro estoque com esse nome.');
            }

            $pdo->prepare('UPDATE censura_estoque_estoques SET nome = ?, descricao = ?, tipo = ?, capacidade_maxima = ? WHERE id = ?')
                ->execute([$nome, $descricao ?: null, $tipo, $capacidade, $id]);
            json_ok(['message' => 'Estoque atualizado com sucesso.']);
        }

        if ($action === 'excluir_estoque') {
            if (!$hasEstoquesTable) {
                json_error('Tabela de estoques nao existe. Execute a migracao do modulo.');
            }
            $id = req_int_or_null('id');
            if (!$id) {
                json_error('ID do estoque nao informado.');
            }

            if ($hasProdutoIdEstoque) {
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM censura_estoque_produtos WHERE id_estoque = ?');
                $stmt->execute([$id]);
                if ((int) $stmt->fetchColumn() > 0) {
                    json_error('Nao e possivel excluir estoque vinculado a produtos.');
                }
            }

            $pdo->prepare('DELETE FROM censura_estoque_estoques WHERE id = ?')->execute([$id]);
            json_ok(['message' => 'Estoque excluido com sucesso.']);
        }

        if ($action === 'salvar_movimentacao') {
            $idProduto = req_int_or_null('id_produto');
            $tipoMov = req_str('tipo_movimentacao');
            $quantidade = max(1, (int) req_str('quantidade', '1'));
            $dataMov = req_str('data_movimentacao', date('Y-m-d'));
            $tipoDestino = req_str('tipo_destino_origem', 'Outro');
            $motivo = req_str('motivo_movimentacao');
            $idFornecedor = req_int_or_null('id_fornecedor');
            $idInterno = req_int_or_null('id_interno');
            $idFuncionario = req_str('id_funcionario');
            $outro = req_str('destino_origem_outro');
            $documento = req_str('documento_referencia');
            $obs = req_str('observacoes');
            $userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;

            if (!$idProduto || !in_array($tipoMov, ['Entrada', 'Saida'], true)) {
                json_error('Dados da movimentacao invalidos.');
            }

            if ($tipoMov === 'Saida') {
                $stmt = $pdo->prepare('SELECT quantidade_atual FROM censura_estoque_saldo WHERE id_produto = ?');
                $stmt->execute([$idProduto]);
                $saldoAtual = (int) $stmt->fetchColumn();
                if ($saldoAtual < $quantidade) {
                    json_error('Saldo insuficiente para esta movimentacao.');
                }
            }

            $sql = 'INSERT INTO censura_estoque_movimentacoes
                (id_produto, tipo_movimentacao, quantidade, data_movimentacao, tipo_destino_origem,
                 id_interno, id_funcionario, destino_origem_outro, id_fornecedor, documento_referencia,
                 motivo_movimentacao, observacoes, cadastrado_por)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
            $pdo->prepare($sql)->execute([
                $idProduto,
                $tipoMov,
                $quantidade,
                $dataMov,
                $tipoDestino,
                $idInterno,
                $idFuncionario ?: null,
                $outro ?: null,
                $idFornecedor,
                $documento ?: null,
                $motivo ?: null,
                $obs ?: null,
                $userId,
            ]);

            $signal = $tipoMov === 'Entrada' ? '+' : '-';
            $sqlSaldo = "UPDATE censura_estoque_saldo SET quantidade_atual = quantidade_atual {$signal} ?, atualizado_por = ? WHERE id_produto = ?";
            $pdo->prepare($sqlSaldo)->execute([$quantidade, $userId, $idProduto]);

            json_ok(['message' => 'Movimentacao registrada com sucesso.']);
        }

        if ($action === 'cancelar_movimentacao') {
            if (!isset($_SESSION['user_id'])) {
                json_error('Sessao expirada.');
            }

            $idMov = req_int_or_null('id_mov');
            if (!$idMov) {
                $idMov = req_int_or_null('id');
            }
            if (!$idMov) {
                json_error('ID da movimentacao nao informado.');
            }

            $stmt = $pdo->prepare('SELECT * FROM censura_estoque_movimentacoes WHERE id = ?');
            $stmt->execute([$idMov]);
            $mov = $stmt->fetch();
            if (!$mov) {
                json_error('Movimentacao nao encontrada.');
            }
            if ($mov['status'] === 'Cancelado') {
                json_error('Movimentacao ja foi cancelada.');
            }

            $signal = $mov['tipo_movimentacao'] === 'Entrada' ? '-' : '+';
            $sqlSaldo = "UPDATE censura_estoque_saldo SET quantidade_atual = quantidade_atual {$signal} ?, atualizado_por = ? WHERE id_produto = ?";
            $pdo->prepare($sqlSaldo)->execute([(int) $mov['quantidade'], (int) $_SESSION['user_id'], (int) $mov['id_produto']]);
            $pdo->prepare('UPDATE censura_estoque_movimentacoes SET status = "Cancelado" WHERE id = ?')->execute([$idMov]);

            json_ok(['message' => 'Movimentacao cancelada com sucesso.']);
        }

        if ($action === 'load_produto') {
            $id = req_int_or_null('id');
            if (!$id) {
                json_error('ID do produto nao informado.');
            }

            $sql = base_product_select($hasEstoquesTable, $hasProdutoIdEstoque) . ' WHERE p.id = ? LIMIT 1';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id]);
            $produto = $stmt->fetch();
            if (!$produto) {
                json_error('Produto nao encontrado.');
            }

            json_ok(['produto' => $produto, 'saldo' => (int) ($produto['quantidade_atual'] ?? 0)]);
        }
        if ($action === 'listar_produtos' || $action === 'listar_produtos_ativos' || $action === 'relatorio_produtos_ativos') {
            $sql = base_product_select($hasEstoquesTable, $hasProdutoIdEstoque) . " WHERE p.status = 'Ativo' ORDER BY p.nome";
            $produtos = $pdo->query($sql)->fetchAll();
            json_ok(['produtos' => $produtos]);
        }

        if ($action === 'listar_produtos_alerta' || $action === 'relatorio_produtos_alerta' || $action === 'relatorio_estoque_baixo') {
            $sql = base_product_select($hasEstoquesTable, $hasProdutoIdEstoque) . "
                WHERE p.status = 'Ativo' AND COALESCE(s.quantidade_atual,0) <= p.quantidade_alerta
                ORDER BY COALESCE(s.quantidade_atual,0) ASC, p.nome";
            $produtos = $pdo->query($sql)->fetchAll();
            json_ok(['produtos' => $produtos]);
        }

        if ($action === 'listar_produtos_criticos' || $action === 'relatorio_produtos_criticos') {
            $sql = base_product_select($hasEstoquesTable, $hasProdutoIdEstoque) . "
                WHERE p.status = 'Ativo' AND COALESCE(s.quantidade_atual,0) <= p.quantidade_minima
                ORDER BY COALESCE(s.quantidade_atual,0) ASC, p.nome";
            $produtos = $pdo->query($sql)->fetchAll();
            json_ok(['produtos' => $produtos]);
        }

        if ($action === 'listar_fornecedores') {
            $stmt = $pdo->query("SELECT * FROM censura_estoque_fornecedores WHERE status = 'Ativo' ORDER BY nome");
            json_ok(['fornecedores' => $stmt->fetchAll()]);
        }

        if ($action === 'listar_tipos' || $action === 'listar_tipos_select') {
            $stmt = $pdo->query("SELECT * FROM censura_estoque_tipos WHERE status = 'Ativo' ORDER BY nome");
            json_ok(['tipos' => $stmt->fetchAll()]);
        }

        if ($action === 'listar_estoques' || $action === 'listar_estoques_select') {
            if (!$hasEstoquesTable) {
                json_ok(['estoques' => []]);
            }
            $stmt = $pdo->query("SELECT id, nome, descricao, tipo, capacidade_maxima, status FROM censura_estoque_estoques WHERE status = 'Ativo' ORDER BY nome");
            json_ok(['estoques' => $stmt->fetchAll()]);
        }

        if ($action === 'buscar_saldo') {
            $idProduto = req_int_or_null('id_produto');
            if (!$idProduto) {
                json_error('Produto nao informado.');
            }

            $stmt = $pdo->prepare('SELECT p.quantidade_minima, p.quantidade_alerta, COALESCE(s.quantidade_atual,0) as saldo FROM censura_estoque_produtos p LEFT JOIN censura_estoque_saldo s ON s.id_produto = p.id WHERE p.id = ? LIMIT 1');
            $stmt->execute([$idProduto]);
            $r = $stmt->fetch();
            if (!$r) {
                json_error('Produto nao encontrado.');
            }

            json_ok([
                'saldo' => (int) $r['saldo'],
                'minimo' => (int) $r['quantidade_minima'],
                'alerta' => (int) $r['quantidade_alerta'],
            ]);
        }

        if ($action === 'buscar_interno' || $action === 'buscar_internos') {
            $ipen = req_str('ipen');
            $termo = req_str('termo', $ipen);
            if ($termo === '') {
                json_error('Informe um termo de busca.');
            }

            $like = '%' . $termo . '%';
            $stmt = $pdo->prepare('SELECT ipen AS id, ipen, nome, nome_social FROM internos WHERE CAST(ipen AS CHAR) LIKE ? OR nome LIKE ? OR nome_social LIKE ? ORDER BY nome LIMIT 20');
            $stmt->execute([$like, $like, $like]);
            $internos = $stmt->fetchAll();

            if ($action === 'buscar_interno') {
                if (ctype_digit($termo)) {
                    $stmtOne = $pdo->prepare('SELECT ipen AS id, ipen, nome, nome_social FROM internos WHERE ipen = ? LIMIT 1');
                    $stmtOne->execute([(int) $termo]);
                    $one = $stmtOne->fetch();
                    if ($one) {
                        json_ok(['interno' => $one, 'internos' => $internos]);
                    }
                }
                if (!empty($internos)) {
                    json_ok(['interno' => $internos[0], 'internos' => $internos]);
                }
                json_error('Interno nao encontrado.', ['internos' => []]);
            }

            json_ok(['internos' => $internos]);
        }

        if ($action === 'atualizar_estatisticas') {
            $totalProdutos = (int) $pdo->query("SELECT COUNT(*) FROM censura_estoque_produtos WHERE status = 'Ativo'")->fetchColumn();
            $produtosAlerta = (int) $pdo->query("SELECT COUNT(*) FROM censura_estoque_produtos p INNER JOIN censura_estoque_saldo s ON p.id = s.id_produto WHERE p.status = 'Ativo' AND s.quantidade_atual <= p.quantidade_alerta")->fetchColumn();
            $produtosCriticos = (int) $pdo->query("SELECT COUNT(*) FROM censura_estoque_produtos p INNER JOIN censura_estoque_saldo s ON p.id = s.id_produto WHERE p.status = 'Ativo' AND s.quantidade_atual <= p.quantidade_minima")->fetchColumn();
            $movHoje = (int) $pdo->query("SELECT COUNT(*) FROM censura_estoque_movimentacoes WHERE DATE(data_movimentacao) = CURDATE() AND status = 'Ativo'")->fetchColumn();

            json_ok([
                'total_produtos' => $totalProdutos,
                'produtos_alerta' => $produtosAlerta,
                'produtos_criticos' => $produtosCriticos,
                'mov_hoje' => $movHoje,
            ]);
        }

        if ($action === 'filtrar_movimentacoes' || $action === 'relatorio_movimentacoes') {
            $where = ["(m.status = 'Ativo' OR m.status = 'Cancelado')"];
            $params = [];

            if ($action === 'filtrar_movimentacoes') {
                $search = req_str('search');
                $idTipo = req_int_or_null('id_tipo');
                $idFornecedor = req_int_or_null('id_fornecedor');
                $tipoMov = req_str('tipo_movimentacao');

                if ($search !== '') {
                    $where[] = '(p.nome LIKE ? OR m.motivo_movimentacao LIKE ? OR m.documento_referencia LIKE ?)';
                    $params[] = "%{$search}%";
                    $params[] = "%{$search}%";
                    $params[] = "%{$search}%";
                }
                if ($idTipo) {
                    $where[] = 'p.id_tipo = ?';
                    $params[] = $idTipo;
                }
                if ($idFornecedor) {
                    $where[] = 'p.id_fornecedor = ?';
                    $params[] = $idFornecedor;
                }
                if ($tipoMov !== '') {
                    $where[] = 'm.tipo_movimentacao = ?';
                    $params[] = $tipoMov;
                }
            }

            fmt_where_dates($where, $params);

            $sql = "
                SELECT
                    m.*,
                    p.nome AS produto_nome,
                    p.unidade_medida,
                    t.nome AS tipo_nome,
                    f.nome AS fornecedor_nome,
                    i.nome AS interno_nome,
                    i.ipen AS interno_ipen,
                    CASE
                        WHEN m.tipo_destino_origem = 'Interno' THEN CONCAT(i.ipen, ' - ', i.nome)
                        WHEN m.tipo_destino_origem = 'Funcionario' THEN m.id_funcionario
                        WHEN m.tipo_destino_origem = 'Fornecedor' THEN f.nome
                        ELSE m.destino_origem_outro
                    END AS destino_origem
                FROM censura_estoque_movimentacoes m
                LEFT JOIN censura_estoque_produtos p ON m.id_produto = p.id
                LEFT JOIN censura_estoque_tipos t ON p.id_tipo = t.id
                LEFT JOIN censura_estoque_fornecedores f ON m.id_fornecedor = f.id
                LEFT JOIN internos i ON m.id_interno = i.ipen
                WHERE " . implode(' AND ', $where) . "
                ORDER BY CASE WHEN m.status = 'Cancelado' THEN 1 ELSE 0 END, m.data_movimentacao DESC, m.data_cadastro DESC
                LIMIT 500
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $movs = $stmt->fetchAll();

            json_ok(['movimentacoes' => $movs]);
        }

        if ($action === 'relatorio_entradas') {
            $where = ["m.status = 'Ativo'", "m.tipo_movimentacao = 'Entrada'"];
            $params = [];
            fmt_where_dates($where, $params);

            $sql = "SELECT m.*, p.nome AS produto_nome, p.unidade_medida, f.nome AS fornecedor_nome
                    FROM censura_estoque_movimentacoes m
                    INNER JOIN censura_estoque_produtos p ON p.id = m.id_produto
                    LEFT JOIN censura_estoque_fornecedores f ON f.id = m.id_fornecedor
                    WHERE " . implode(' AND ', $where) . "
                    ORDER BY m.data_movimentacao DESC, m.data_cadastro DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $entradas = $stmt->fetchAll();
            $totalQuantidade = 0;
            foreach ($entradas as $e) {
                $totalQuantidade += (int) $e['quantidade'];
            }

            json_ok(['entradas' => $entradas, 'total_entradas' => count($entradas), 'total_quantidade' => $totalQuantidade]);
        }

        if ($action === 'relatorio_saidas') {
            $where = ["m.status = 'Ativo'", "m.tipo_movimentacao = 'Saida'"];
            $params = [];
            fmt_where_dates($where, $params);

            $sql = "SELECT m.*, p.nome AS produto_nome, p.unidade_medida,
                    CASE
                        WHEN m.tipo_destino_origem = 'Interno' THEN CONCAT(i.ipen, ' - ', i.nome)
                        WHEN m.tipo_destino_origem = 'Funcionario' THEN m.id_funcionario
                        WHEN m.tipo_destino_origem = 'Fornecedor' THEN f.nome
                        ELSE m.destino_origem_outro
                    END AS destino_origem
                    FROM censura_estoque_movimentacoes m
                    INNER JOIN censura_estoque_produtos p ON p.id = m.id_produto
                    LEFT JOIN internos i ON i.ipen = m.id_interno
                    LEFT JOIN censura_estoque_fornecedores f ON f.id = m.id_fornecedor
                    WHERE " . implode(' AND ', $where) . "
                    ORDER BY m.data_movimentacao DESC, m.data_cadastro DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $saidas = $stmt->fetchAll();
            $totalQuantidade = 0;
            foreach ($saidas as $s) {
                $totalQuantidade += (int) $s['quantidade'];
            }

            json_ok(['saidas' => $saidas, 'total_saidas' => count($saidas), 'total_quantidade' => $totalQuantidade]);
        }

        if ($action === 'relatorio_mais_entraram') {
            $where = ["m.status = 'Ativo'", "m.tipo_movimentacao = 'Entrada'"];
            $params = [];
            fmt_where_dates($where, $params);

            $sql = "SELECT p.nome AS produto_nome, p.unidade_medida,
                    COUNT(*) AS total_entradas,
                    SUM(m.quantidade) AS total_quantidade,
                    MAX(f.nome) AS fornecedor_nome
                    FROM censura_estoque_movimentacoes m
                    INNER JOIN censura_estoque_produtos p ON p.id = m.id_produto
                    LEFT JOIN censura_estoque_fornecedores f ON f.id = m.id_fornecedor
                    WHERE " . implode(' AND ', $where) . "
                    GROUP BY p.id, p.nome, p.unidade_medida
                    ORDER BY SUM(m.quantidade) DESC
                    LIMIT 50";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            json_ok(['produtos' => $stmt->fetchAll()]);
        }

        if ($action === 'relatorio_mais_sairam') {
            $where = ["m.status = 'Ativo'", "m.tipo_movimentacao = 'Saida'"];
            $params = [];
            fmt_where_dates($where, $params);

            $sql = "SELECT p.nome AS produto_nome, p.unidade_medida,
                    COUNT(*) AS total_saidas,
                    SUM(m.quantidade) AS total_quantidade,
                    MAX(CASE
                        WHEN m.tipo_destino_origem = 'Interno' THEN CONCAT(i.ipen, ' - ', i.nome)
                        WHEN m.tipo_destino_origem = 'Funcionario' THEN m.id_funcionario
                        WHEN m.tipo_destino_origem = 'Fornecedor' THEN f.nome
                        ELSE m.destino_origem_outro
                    END) AS destino_mais_comum
                    FROM censura_estoque_movimentacoes m
                    INNER JOIN censura_estoque_produtos p ON p.id = m.id_produto
                    LEFT JOIN internos i ON i.ipen = m.id_interno
                    LEFT JOIN censura_estoque_fornecedores f ON f.id = m.id_fornecedor
                    WHERE " . implode(' AND ', $where) . "
                    GROUP BY p.id, p.nome, p.unidade_medida
                    ORDER BY SUM(m.quantidade) DESC
                    LIMIT 50";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            json_ok(['produtos' => $stmt->fetchAll()]);
        }

        if ($action === 'relatorio_nao_recebem_tempo') {
            $sql = "
                SELECT
                    p.nome,
                    p.unidade_medida,
                    COALESCE(s.quantidade_atual, 0) AS quantidade_atual,
                    f.nome AS fornecedor_nome,
                    MAX(CASE WHEN m.tipo_movimentacao = 'Entrada' AND m.status = 'Ativo' THEN m.data_movimentacao END) AS ultima_entrada,
                    DATEDIFF(CURDATE(), MAX(CASE WHEN m.tipo_movimentacao = 'Entrada' AND m.status = 'Ativo' THEN m.data_movimentacao END)) AS dias_sem_receber
                FROM censura_estoque_produtos p
                LEFT JOIN censura_estoque_saldo s ON s.id_produto = p.id
                LEFT JOIN censura_estoque_fornecedores f ON f.id = p.id_fornecedor
                LEFT JOIN censura_estoque_movimentacoes m ON m.id_produto = p.id
                WHERE p.status = 'Ativo'
                GROUP BY p.id, p.nome, p.unidade_medida, s.quantidade_atual, f.nome
                ORDER BY (ultima_entrada IS NULL) DESC, dias_sem_receber DESC
                LIMIT 200
            ";
            $produtos = $pdo->query($sql)->fetchAll();
            json_ok(['produtos' => $produtos]);
        }

        json_error('Acao nao reconhecida.');
    } catch (Throwable $e) {
        json_error('Erro no processamento da requisicao.', ['details' => $e->getMessage()]);
    }
}

$f = [
    'search' => $_GET['search'] ?? '',
    'id_tipo' => $_GET['id_tipo'] ?? '',
    'id_fornecedor' => $_GET['id_fornecedor'] ?? '',
    'tipo_movimentacao' => $_GET['tipo_movimentacao'] ?? '',
    'data_inicio' => $_GET['data_inicio'] ?? date('Y-m-01'),
    'data_fim' => $_GET['data_fim'] ?? date('Y-m-d'),
    'ver_canceladas' => isset($_GET['ver_canceladas']),
];

try {
    $tipos = $pdo->query("SELECT * FROM censura_estoque_tipos WHERE status = 'Ativo' ORDER BY nome")->fetchAll();
    $fornecedores = $pdo->query("SELECT * FROM censura_estoque_fornecedores WHERE status = 'Ativo' ORDER BY nome")->fetchAll();

    if ($hasEstoquesTable) {
        $estoques = $pdo->query("SELECT id, nome, tipo FROM censura_estoque_estoques WHERE status = 'Ativo' ORDER BY nome")->fetchAll();
    } else {
        $estoques = [];
    }

    $produtosSql = base_product_select($hasEstoquesTable, $hasProdutoIdEstoque) . " WHERE p.status = 'Ativo' ORDER BY p.nome";
    $produtos = $pdo->query($produtosSql)->fetchAll();

    $where = ["(m.status = 'Ativo' OR m.status = 'Cancelado')"];
    $params = [];

    if (!$f['ver_canceladas']) {
        $where[] = "m.status != 'Cancelado'";
    }

    if ($f['search'] !== '') {
        $where[] = '(p.nome LIKE ? OR m.motivo_movimentacao LIKE ? OR m.documento_referencia LIKE ?)';
        $params[] = '%' . $f['search'] . '%';
        $params[] = '%' . $f['search'] . '%';
        $params[] = '%' . $f['search'] . '%';
    }
    if ($f['id_tipo'] !== '') {
        $where[] = 'p.id_tipo = ?';
        $params[] = $f['id_tipo'];
    }
    if ($f['id_fornecedor'] !== '') {
        $where[] = 'p.id_fornecedor = ?';
        $params[] = $f['id_fornecedor'];
    }
    if ($f['tipo_movimentacao'] !== '') {
        $where[] = 'm.tipo_movimentacao = ?';
        $params[] = $f['tipo_movimentacao'];
    }
    if ($f['data_inicio'] !== '') {
        $where[] = 'm.data_movimentacao >= ?';
        $params[] = $f['data_inicio'];
    }
    if ($f['data_fim'] !== '') {
        $where[] = 'm.data_movimentacao <= ?';
        $params[] = $f['data_fim'];
    }

    $sqlMov = "
        SELECT
            m.*, p.nome AS produto_nome, p.unidade_medida, p.quantidade_minima, p.quantidade_alerta,
            t.nome AS tipo_nome, f.nome AS fornecedor_nome, i.nome AS interno_nome, i.ipen AS interno_ipen,
            s.quantidade_atual,
            CASE
                WHEN m.tipo_destino_origem = 'Interno' THEN CONCAT(i.ipen, ' - ', i.nome)
                WHEN m.tipo_destino_origem = 'Funcionario' THEN m.id_funcionario
                WHEN m.tipo_destino_origem = 'Fornecedor' THEN f.nome
                ELSE m.destino_origem_outro
            END AS destino_origem
        FROM censura_estoque_movimentacoes m
        LEFT JOIN censura_estoque_produtos p ON m.id_produto = p.id
        LEFT JOIN censura_estoque_tipos t ON p.id_tipo = t.id
        LEFT JOIN censura_estoque_fornecedores f ON m.id_fornecedor = f.id
        LEFT JOIN internos i ON m.id_interno = i.ipen
        LEFT JOIN censura_estoque_saldo s ON p.id = s.id_produto
        WHERE " . implode(' AND ', $where) . "
        ORDER BY m.data_movimentacao DESC, m.data_cadastro DESC
        LIMIT 200
    ";
    $stmt = $pdo->prepare($sqlMov);
    $stmt->execute($params);
    $movimentacoes = $stmt->fetchAll();

    $total_produtos = (int) $pdo->query("SELECT COUNT(*) FROM censura_estoque_produtos WHERE status = 'Ativo'")->fetchColumn();
    $produtos_alerta = (int) $pdo->query("SELECT COUNT(*) FROM censura_estoque_produtos p INNER JOIN censura_estoque_saldo s ON p.id = s.id_produto WHERE p.status = 'Ativo' AND s.quantidade_atual <= p.quantidade_alerta")->fetchColumn();
    $produtos_criticos = (int) $pdo->query("SELECT COUNT(*) FROM censura_estoque_produtos p INNER JOIN censura_estoque_saldo s ON p.id = s.id_produto WHERE p.status = 'Ativo' AND s.quantidade_atual <= p.quantidade_minima")->fetchColumn();
    $mov_hoje = (int) $pdo->query("SELECT COUNT(*) FROM censura_estoque_movimentacoes WHERE DATE(data_movimentacao) = CURDATE() AND status = 'Ativo'")->fetchColumn();
} catch (Throwable $e) {
    $tipos = [];
    $fornecedores = [];
    $estoques = [];
    $produtos = [];
    $movimentacoes = [];
    $total_produtos = 0;
    $produtos_alerta = 0;
    $produtos_criticos = 0;
    $mov_hoje = 0;
}
?>
