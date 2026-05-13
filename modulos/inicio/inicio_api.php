<?php
// modulos/inicio/inicio_api.php
// Endpoint AJAX do módulo início (SPA): busca e dossiê de internos.

session_start();
header('Content-Type: application/json; charset=utf-8');
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Não autenticado']);
    exit;
}

try {
    $config = require __DIR__ . '/../../conf/db.php';
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['user'], $config['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erro crítico no banco']);
    exit;
}

$action = $_REQUEST['action'] ?? '';

if ($action === 'search_interno') {
    $termo = trim($_REQUEST['termo'] ?? $_REQUEST['q'] ?? '');

    if (mb_strlen($termo, 'UTF-8') < 2) {
        echo json_encode(['success' => true, 'resultados' => []]);
        exit;
    }

    $like = '%' . $termo . '%';
    $stmt = $pdo->prepare("
        SELECT DISTINCT
            ipen, nome, nome_social, apelido, lgbt,
            situacao, galeria, bloco, ala, res,
            regalia, regalia_galeria
        FROM internos
        WHERE status = 'A'
          AND (
            CAST(ipen AS CHAR) LIKE :t OR
            nome LIKE :t OR
            nome_social LIKE :t OR
            apelido LIKE :t
          )
        ORDER BY nome ASC
        LIMIT 20
    ");
    $stmt->execute([':t' => $like]);
    echo json_encode(['success' => true, 'resultados' => $stmt->fetchAll()]);
    exit;
}

if ($action === 'get_dossie') {
    $ipen = (int)($_REQUEST['ipen'] ?? 0);
    if ($ipen <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'IPEN inválido']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM internos WHERE ipen = :ipen LIMIT 1");
    $stmt->execute([':ipen' => $ipen]);
    $interno = $stmt->fetch();
    if (!$interno) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Interno não encontrado']);
        exit;
    }

    // Kit (tipo)
    $kitTipo = null;
    $stmt = $pdo->prepare("SELECT id, nome, descricao FROM internos_termo_kit_tipos WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => (int)$interno['kit']]);
    $kitTipo = $stmt->fetch() ?: null;

    // Condições especiais (tornozeleira, livramento etc.)
    $stmt = $pdo->prepare("
        SELECT id, tipo, data_inicio, data_fim, status, observacoes
        FROM internos_condicoes_especiais
        WHERE ipen = :ipen
        ORDER BY data_inicio DESC, id DESC
        LIMIT 10
    ");
    $stmt->execute([':ipen' => $ipen]);
    $condicoesEspeciais = $stmt->fetchAll();
    $condicoesAtivas = array_values(array_filter($condicoesEspeciais, fn($x) => ($x['status'] ?? '') === 'Ativa'));

    // Rouparia civil (último registro)
    $stmt = $pdo->prepare("
        SELECT id, nome, pecas, criado_por, criado_em
        FROM internos_rouparia_civil
        WHERE ipen = :ipen
        ORDER BY id DESC
        LIMIT 1
    ");
    $stmt->execute([':ipen' => $ipen]);
    $roupariaCivil = $stmt->fetch() ?: null;
    $roupariaCivilPecas = null;
    if ($roupariaCivil && isset($roupariaCivil['pecas'])) {
        $roupariaCivilPecas = json_decode($roupariaCivil['pecas'], true);
        if (!is_array($roupariaCivilPecas)) $roupariaCivilPecas = null;
    }

    // Laboral (atual + histórico)
    $stmt = $pdo->prepare("
        SELECT id, estabelecimento, remicao_inicio, remicao_fim, liberacao_inicio, liberacao_fim, dias_semana, status, data_ativo, data_inativo
        FROM internos_laboral
        WHERE ipen = :ipen
        ORDER BY data_ativo DESC
        LIMIT 5
    ");
    $stmt->execute([':ipen' => $ipen]);
    $laboralHistorico = $stmt->fetchAll();
    $laboralAtivo = null;
    foreach ($laboralHistorico as $l) {
        if (($l['status'] ?? '') === 'A') {
            $laboralAtivo = $l;
            break;
        }
    }

    // Eletrônicos (na cela / outros)
    $stmt = $pdo->prepare("
        SELECT id, tipo_item, marca_modelo, cor, estado_conservacao, situacao, data_entrada, data_entrega_interno, data_retirada,
               polegadas, tem_controle, tem_fonte, tamanho, capacidade, comprimento, nome_item_personalizado, descricao_personalizada
        FROM internos_eletronicos
        WHERE id_interno = :ipen
        ORDER BY data_entrada DESC
        LIMIT 200
    ");
    $stmt->execute([':ipen' => $ipen]);
    $eletronicos = $stmt->fetchAll();
    $eletronicosNaCela = [];
    $eletronicosOutros = [];
    foreach ($eletronicos as $e) {
        if (($e['situacao'] ?? '') === 'Na Cela') $eletronicosNaCela[] = $e;
        else $eletronicosOutros[] = $e;
    }

    // Livros (itens)
    $stmt = $pdo->prepare("
        SELECT r.id AS recebimento_id, r.data_recebimento, r.data_entrega_interno, r.entregue_por_tipo, r.entregue_por_nome,
               it.id AS item_id, it.titulo_livro, it.autor, it.estado_conservacao
        FROM internos_recebimento_livros r
        JOIN internos_recebimento_livros_itens it ON it.id_recebimento = r.id
        WHERE r.id_interno = :ipen
        ORDER BY r.data_recebimento DESC, it.id DESC
        LIMIT 200
    ");
    $stmt->execute([':ipen' => $ipen]);
    $livrosItens = $stmt->fetchAll();
    $livrosEntregues = array_values(array_filter($livrosItens, fn($x) => !empty($x['data_entrega_interno'])));

    // Roupas (itens)
    $stmt = $pdo->prepare("
        SELECT r.id AS recebimento_id, r.data_recebimento, r.data_entrega_interno, r.entregue_por_tipo, r.entregue_por_nome,
               it.id AS item_id, it.item, it.quantidade, it.detalhes
        FROM internos_recebimento_roupas r
        JOIN internos_recebimento_roupas_itens it ON it.id_recebimento = r.id
        WHERE r.id_interno = :ipen
        ORDER BY r.data_recebimento DESC, it.id DESC
        LIMIT 250
    ");
    $stmt->execute([':ipen' => $ipen]);
    $roupasItens = $stmt->fetchAll();
    $roupasEntregues = array_values(array_filter($roupasItens, fn($x) => !empty($x['data_entrega_interno'])));

    // Cosméticos (itens)
    $stmt = $pdo->prepare("
        SELECT r.id AS recebimento_id, r.data_recebimento, r.data_entrega_interno, r.entregue_por_tipo, r.entregue_por_nome,
               it.id AS item_id, it.item, it.quantidade, it.detalhes
        FROM internos_recebimento_cosmeticos r
        JOIN internos_recebimento_cosmeticos_itens it ON it.id_recebimento = r.id
        WHERE r.id_interno = :ipen
        ORDER BY r.data_recebimento DESC, it.id DESC
        LIMIT 250
    ");
    $stmt->execute([':ipen' => $ipen]);
    $cosmeticosItens = $stmt->fetchAll();
    $cosmeticosEntregues = array_values(array_filter($cosmeticosItens, fn($x) => !empty($x['data_entrega_interno'])));

    // Cartas (total + últimas)
    $stmt = $pdo->prepare("
        SELECT id, tipo_movimentacao, status_censura, recebido_em, concluido_em, correspondente_nome, correspondente_vinculo
        FROM censura_cartas
        WHERE id_interno = :ipen
          AND status_registro = 'Ativo'
        ORDER BY recebido_em DESC
        LIMIT 10
    ");
    $stmt->execute([':ipen' => $ipen]);
    $cartasRecentes = $stmt->fetchAll();

    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS total
        FROM censura_cartas
        WHERE id_interno = :ipen
          AND status_registro = 'Ativo'
    ");
    $stmt->execute([':ipen' => $ipen]);
    $cartasTotal = (int)($stmt->fetchColumn() ?: 0);

    // CTC (últimas + flag ativo)
    $stmt = $pdo->prepare("
        SELECT id, data_ctc, resultado, decisao_juiz, data_decisao, data_proxima_ctc, status, refazer
        FROM internos_ctc
        WHERE ipen = :ipen
        ORDER BY data_ctc DESC, id DESC
        LIMIT 10
    ");
    $stmt->execute([':ipen' => $ipen]);
    $ctcHistorico = $stmt->fetchAll();
    $ctcAtivo = false;
    foreach ($ctcHistorico as $c) {
        if (($c['status'] ?? '') === 'Ativo') {
            $ctcAtivo = true;
            break;
        }
    }

    // MD (histórico + itens apreendidos da última ativa)
    $stmt = $pdo->prepare("
        SELECT id, data_inicio, data_fim, status, motivo, local_castigo, data_conclusao
        FROM internos_md_medidas
        WHERE id_interno = :ipen
        ORDER BY data_inicio DESC, id DESC
        LIMIT 15
    ");
    $stmt->execute([':ipen' => (string)$ipen]);
    $mdHistorico = $stmt->fetchAll();
    $mdAtivaId = null;
    foreach ($mdHistorico as $m) {
        if (($m['status'] ?? '') === 'Ativa') {
            $mdAtivaId = (int)$m['id'];
            break;
        }
    }

    $mdItensApreendidos = [];
    if ($mdAtivaId) {
        $stmt = $pdo->prepare("
            SELECT id, tipo_item, marca, modelo, cor, local_retido, status_item, data_devolucao
            FROM internos_md_itens_apreendidos
            WHERE id_medida = :id
            ORDER BY id DESC
            LIMIT 50
        ");
        $stmt->execute([':id' => $mdAtivaId]);
        $mdItensApreendidos = $stmt->fetchAll();
    }

    // Escoltas (histórico)
    $stmt = $pdo->prepare("
        SELECT id, data_cadastro, destino, status, motivo, hora_prevista, hora_chegada, hora_retorno, motorista, placa
        FROM eclusa_movimentacoes_escolta
        WHERE interno_id = :ipen
        ORDER BY id DESC
        LIMIT 15
    ");
    $stmt->execute([':ipen' => $ipen]);
    $escoltas = $stmt->fetchAll();

    // Entregas de kits (eventos)
    $stmt = $pdo->prepare("
        SELECT ei.id, ei.status_entrega, ei.data_confirmacao, ev.data_assinatura
        FROM internos_entregas_itens ei
        LEFT JOIN entregas_kits_eventos ev ON ev.id = ei.id_evento
        WHERE ei.ipen = :ipen
        ORDER BY ei.id DESC
        LIMIT 15
    ");
    $stmt->execute([':ipen' => $ipen]);
    $kitsEntregas = $stmt->fetchAll();

    // Doações de eletrônicos (interno como doador ou receptor)
    $stmt = $pdo->prepare("
        SELECT id, id_doador, id_receptor, tipo_receptor, galeria_receptor, bloco_receptor, cela_receptor,
               data_doacao, usuario_cadastro, termo_assinado, observacoes
        FROM internos_doacao_eletronicos
        WHERE id_doador = :ipen OR id_receptor = :ipen
        ORDER BY data_doacao DESC, id DESC
        LIMIT 10
    ");
    $stmt->execute([':ipen' => $ipen]);
    $doacoes = $stmt->fetchAll();
    $doacaoIds = array_map(fn($d) => (int)$d['id'], $doacoes);
    $doacoesItens = [];
    if (!empty($doacaoIds)) {
        $placeholders = implode(',', array_fill(0, count($doacaoIds), '?'));
        $stmt = $pdo->prepare("
            SELECT id, id_doacao, tipo_item, marca_modelo, cor, estado_conservacao, nota_fiscal
            FROM internos_doacao_eletronicos_itens
            WHERE id_doacao IN ($placeholders)
            ORDER BY id DESC
        ");
        $stmt->execute($doacaoIds);
        $rows = $stmt->fetchAll();
        foreach ($rows as $r) {
            $did = (int)$r['id_doacao'];
            if (!isset($doacoesItens[$did])) $doacoesItens[$did] = [];
            $doacoesItens[$did][] = $r;
        }
    }

    // Alterações cadastrais (interno / laboral)
    $stmt = $pdo->prepare("
        SELECT campo, valor_antigo, valor_novo, data_alteracao, operacao
        FROM internos_historico_detalhado
        WHERE ipen = :ipen
        ORDER BY data_alteracao DESC, id DESC
        LIMIT 15
    ");
    $stmt->execute([':ipen' => $ipen]);
    $alteracoesInterno = $stmt->fetchAll();

    $stmt = $pdo->prepare("
        SELECT campo, valor_antigo, valor_novo, data_alteracao, operacao
        FROM internos_laboral_historico_detalhado
        WHERE ipen = :ipen
        ORDER BY data_alteracao DESC, id DESC
        LIMIT 15
    ");
    $stmt->execute([':ipen' => $ipen]);
    $alteracoesLaboral = $stmt->fetchAll();

    // Pecúlio (resumo)
    $stmt = $pdo->prepare("
        SELECT mes_referencia, valor, data_cadastro
        FROM peculio_saldos_pix
        WHERE ipen = :ipen
        ORDER BY mes_referencia DESC, id DESC
        LIMIT 6
    ");
    $stmt->execute([':ipen' => $ipen]);
    $peculioPix = $stmt->fetchAll();

    $stmt = $pdo->prepare("
        SELECT mes_referencia, valor
        FROM peculio_saldos_trabalho
        WHERE ipen = :ipen
        ORDER BY mes_referencia DESC, id DESC
        LIMIT 6
    ");
    $stmt->execute([':ipen' => $ipen]);
    $peculioTrabalho = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'interno' => $interno,
        'kit_tipo' => $kitTipo,
        'condicoes_especiais' => $condicoesEspeciais,
        'condicoes_especiais_ativas' => $condicoesAtivas,
        'rouparia_civil' => $roupariaCivil,
        'rouparia_civil_pecas' => $roupariaCivilPecas,
        'laboral_ativo' => $laboralAtivo,
        'laboral_historico' => $laboralHistorico,
        'eletronicos_na_cela' => $eletronicosNaCela,
        'eletronicos_outros' => $eletronicosOutros,
        'livros_total' => count($livrosItens),
        'livros_entregues_total' => count($livrosEntregues),
        'livros_itens' => array_slice($livrosItens, 0, 60),
        'roupas_total' => count($roupasItens),
        'roupas_entregues_total' => count($roupasEntregues),
        'roupas_itens' => array_slice($roupasItens, 0, 80),
        'cosmeticos_total' => count($cosmeticosItens),
        'cosmeticos_entregues_total' => count($cosmeticosEntregues),
        'cosmeticos_itens' => array_slice($cosmeticosItens, 0, 80),
        'cartas_total' => $cartasTotal,
        'cartas_recentes' => $cartasRecentes,
        'ctc_ativo' => $ctcAtivo,
        'ctc_historico' => $ctcHistorico,
        'md_historico' => $mdHistorico,
        'md_itens_apreendidos' => $mdItensApreendidos,
        'escoltas' => $escoltas,
        'kits_entregas' => $kitsEntregas,
        'doacoes' => $doacoes,
        'doacoes_itens' => $doacoesItens,
        'alteracoes_interno' => $alteracoesInterno,
        'alteracoes_laboral' => $alteracoesLaboral,
        'peculio_pix' => $peculioPix,
        'peculio_trabalho' => $peculioTrabalho,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'error' => 'Ação inválida']);
exit;
