<?php
ob_start();
date_default_timezone_set('America/Sao_Paulo');
ini_set('display_errors', 0);
error_reporting(E_ALL);

// CONEXÃO
$pdo = null;
try {
    $config = require __DIR__ . '/../../conf/db.php';
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['user'], $config['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Erro 500: Falha no Banco.");
}

// ==================== SISTEMA DE CACHE ====================
// Diretório para cache
define('CACHE_DIR', __DIR__ . '/../temp/cache/');

// Função para obter dados do cache
function getCache($key)
{
    $cacheFile = CACHE_DIR . md5($key) . '.json';
    if (file_exists($cacheFile)) {
        $data = json_decode(file_get_contents($cacheFile), true);
        if ($data && isset($data['expires']) && time() < $data['expires']) {
            return $data['data'];
        }
        // Cache expirado, remover arquivo
        unlink($cacheFile);
    }
    return null;
}

// Função para salvar dados no cache
function setCache($key, $data, $ttl_seconds = 300)
{
    if (!is_dir(CACHE_DIR)) {
        mkdir(CACHE_DIR, 0755, true);
    }
    $cacheFile = CACHE_DIR . md5($key) . '.json';
    $cacheData = [
        'data' => $data,
        'expires' => time() + $ttl_seconds,
        'created' => time()
    ];
    file_put_contents($cacheFile, json_encode($cacheData));
}

// Função para limpar cache expirado (chamada opcional)
function cleanExpiredCache()
{
    if (!is_dir(CACHE_DIR)) return;
    $files = glob(CACHE_DIR . '*.json');
    foreach ($files as $file) {
        $data = json_decode(file_get_contents($file), true);
        if (!$data || !isset($data['expires']) || time() >= $data['expires']) {
            unlink($file);
        }
    }
}

// ==================== PROCESSAMENTO AJAX - Buscar internos da cela ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    ob_clean();
    header('Content-Type: application/json');

    try {
        if ($_POST['action'] === 'fetch_cela_internos') {
            $galeria = trim($_POST['galeria'] ?? '');
            $bloco = trim($_POST['bloco'] ?? '');
            $ala = trim($_POST['ala'] ?? '');
            $cela = trim($_POST['cela'] ?? '');
            $mostrar_todos = isset($_POST['mostrar_todos']) && $_POST['mostrar_todos'] === 'true';

            // Semi-aberto (galeria S) exige bloco para não misturar celas (SE-3 vs ST-3)
            if (empty($galeria) || empty($cela)) {
                echo json_encode(['success' => false, 'error' => 'Parâmetros inválidos']);
                exit;
            }
            if ($galeria === 'S' && $bloco === '') {
                echo json_encode(['success' => false, 'error' => 'Para galeria Semi-Aberto informe o bloco (ex: SE, ST).']);
                exit;
            }

            // Buscar data da última importação
            $ultima_importacao = null;
            try {
                $result = $pdo->query("SELECT MAX(data_importacao) AS ultima_data FROM internos_importacao");
                if ($result && $row = $result->fetch()) {
                    $ultima_importacao = $row['ultima_data'];
                }
            } catch (Exception $e) {
                $ultima_importacao = null;
            }
            if (!$ultima_importacao) {
                $ultima_importacao = date('Y-m-d H:i:s', strtotime('-30 days'));
            }

            // Definir data limite baseada na opção mostrar_todos
            $data_limite = $mostrar_todos
                ? $ultima_importacao
                : date('Y-m-d 00:00:00');

            // Buscar internos ATIVOS da cela (sempre filtrar por galeria+bloco+res quando bloco informado)
            if ($bloco !== '') {
                // Caso especial para galerias Semiaberto (SA, SB, SC, etc.)
                if (in_array($galeria, ['SA', 'SB', 'SC', 'SD', 'SE', 'ST'])) {
                    // Para Semiaberto, galeria no banco é 'S' e bloco é a letra (A, B, C, etc.)
                    $query = "
                        SELECT ipen, nome, nome_social, status, situacao, regalia, kit
                        FROM internos
                        WHERE galeria = 'S' AND bloco = :bloco_real AND res = :cela AND status = 'A'
                        ORDER BY nome
                    ";
                    $bloco_real = substr($galeria, 1); // Extrai A de SA, B de SB, etc.
                    $stmt = $pdo->prepare($query);
                    $stmt->execute([':bloco_real' => $bloco_real, ':cela' => $cela]);
                } else {
                    // Para galerias normais
                    $query = "
                        SELECT ipen, nome, nome_social, status, situacao, regalia, kit
                        FROM internos
                        WHERE galeria = :gal AND bloco = :bloco AND res = :cela AND status = 'A'
                        ORDER BY nome
                    ";
                    $stmt = $pdo->prepare($query);
                    $stmt->execute([':gal' => $galeria, ':bloco' => $bloco, ':cela' => $cela]);
                }
            } else {
                $query = "
                    SELECT ipen, nome, nome_social, status, situacao, regalia, kit
                    FROM internos
                    WHERE galeria = :gal AND res = :cela AND status = 'A'
                    ORDER BY nome
                ";
                $stmt = $pdo->prepare($query);
                $stmt->execute([':gal' => $galeria, ':cela' => $cela]);
            }
            $internos = $stmt->fetchAll();
            $ipens_cela = array_column($internos, 'ipen');

            // Eletrônicos na cela: só itens com situacao = 'Na Cela' dos internos desta cela
            $eletronicos = ['Chaleira' => 0, 'Maquina Cabelo' => 0, 'Radio' => 0, 'TV' => 0, 'Ventilador' => 0, 'Bola' => 0, 'Banqueta' => 0, 'Extensao' => 0, 'Outro' => 0];
            if (!empty($ipens_cela)) {
                try {
                    $placeholders = implode(',', array_fill(0, count($ipens_cela), '?'));
                    $sql_ele = "SELECT tipo_item, COUNT(*) as qtd FROM internos_eletronicos WHERE id_interno IN ($placeholders) AND situacao = 'Na Cela' GROUP BY tipo_item";
                    $stmt_ele = $pdo->prepare($sql_ele);
                    $stmt_ele->execute($ipens_cela);
                    while ($row = $stmt_ele->fetch()) {
                        $tipo = $row['tipo_item'];
                        if (isset($eletronicos[$tipo])) {
                            $eletronicos[$tipo] = (int) $row['qtd'];
                        } else {
                            $eletronicos['Outro'] += (int) $row['qtd'];
                        }
                    }
                } catch (Exception $e) {
                    // Tabela pode não existir
                }
            }

            // Buscar medidas disciplinares ativas dos internos desta cela
            $medidas_disciplinares = [];
            if (!empty($ipens_cela)) {
                try {
                    $placeholders = implode(',', array_fill(0, count($ipens_cela), '?'));
                    $sql_md = "
                        SELECT md.id, md.id_interno, md.data_inicio, md.data_fim, md.motivo, md.local_castigo, md.status,
                               i.nome, i.nome_social
                        FROM internos_md_medidas md
                        JOIN internos i ON md.id_interno = i.ipen
                        WHERE md.id_interno IN ($placeholders) AND md.status = 'Ativa'
                        ORDER BY md.data_fim ASC
                    ";
                    $stmt_md = $pdo->prepare($sql_md);
                    $stmt_md->execute($ipens_cela);
                    $medidas_disciplinares = $stmt_md->fetchAll();
                } catch (Exception $e) {
                    // Tabela pode não existir ainda
                    $medidas_disciplinares = [];
                }
            }

            // Buscar itens apreendidos relacionados às medidas disciplinares
            $itens_apreendidos = [];
            if (!empty($medidas_disciplinares)) {
                $md_ids = array_column($medidas_disciplinares, 'id');
                $placeholders = implode(',', array_fill(0, count($md_ids), '?'));
                $sql_itens = "
                    SELECT ia.*, md.id_interno
                    FROM internos_md_itens_apreendidos ia
                    JOIN internos_md_medidas md ON ia.id_medida = md.id
                    WHERE ia.id_medida IN ($placeholders) AND ia.status_item = 'Retido'
                    ORDER BY ia.tipo_item
                ";
                $stmt_itens = $pdo->prepare($sql_itens);
                $stmt_itens->execute($md_ids);
                $itens_apreendidos = $stmt_itens->fetchAll();
            }

            // Para cada interno, buscar movimentação desde última importação e montar label "Antiga cela"
            $internos_com_movimento = [];
            foreach ($internos as $interno) {
                $movimento = null;
                try {
                    $hist_query = "
                        SELECT campo, valor_antigo, valor_novo, data_alteracao, operacao
                        FROM internos_historico_detalhado
                        WHERE ipen = :ipen AND data_alteracao >= :data_importacao AND (
                            campo IN ('res', 'status') OR operacao IN ('INSERIDO')
                        )
                        ORDER BY data_alteracao DESC
                        LIMIT 1
                    ";
                    $hist_stmt = $pdo->prepare($hist_query);
                    $hist_stmt->execute([':ipen' => $interno['ipen'], ':data_importacao' => $ultima_importacao]);
                    $movimento = $hist_stmt->fetch();
                    if ($movimento && $movimento['campo'] === 'res' && !empty($movimento['valor_antigo'])) {
                        // Formatar antiga cela como galeria-bloco-cela
                        $antiga_cela = $movimento['valor_antigo'];
                        if (!preg_match('/[a-zA-Z]/', $antiga_cela)) {
                            // Se é apenas um número, precisamos descobrir a galeria e bloco NA ÉPOCA da mudança
                            // Buscar histórico completo para encontrar galeria/bloco na época

                            $hist_epoca_stmt = $pdo->prepare("
                                SELECT campo, valor_antigo, valor_novo, data_alteracao
                                FROM internos_historico_detalhado
                                WHERE ipen = ? AND data_alteracao <= ?
                                ORDER BY data_alteracao DESC
                            ");
                            $hist_epoca_stmt->execute([$interno['ipen'], $movimento['data_alteracao']]);

                            $galeria_epoca = null;
                            $bloco_epoca = null;

                            while ($hist = $hist_epoca_stmt->fetch()) {
                                if ($hist['campo'] === 'galeria' && $hist['valor_antigo']) {
                                    $galeria_epoca = $hist['valor_antigo'];
                                }
                                if ($hist['campo'] === 'bloco' && $hist['valor_antigo']) {
                                    $bloco_epoca = $hist['valor_antigo'];
                                }

                                // Se já encontrou galeria e bloco, pode parar
                                if ($galeria_epoca && $bloco_epoca) {
                                    break;
                                }
                            }

                            if ($galeria_epoca && $bloco_epoca) {
                                $antiga_cela = $galeria_epoca . ($bloco_epoca ?: '') . '-' . $antiga_cela;
                            } else {
                                // Fallback: usar informações atuais
                                $info_stmt = $pdo->prepare("SELECT galeria, bloco FROM internos WHERE ipen = ?");
                                $info_stmt->execute([$interno['ipen']]);
                                $info = $info_stmt->fetch();
                                if ($info) {
                                    $antiga_cela = $info['galeria'] . ($info['bloco'] ?: '') . '-' . $antiga_cela;
                                }
                            }
                        } elseif (preg_match('/^([A-Z])-([0-9]+)$/', $antiga_cela, $matches)) {
                            // Se está no formato "A-6", completa com a galeria atual
                            $antiga_cela = $interno['galeria'] . '-' . $antiga_cela;
                        }
                        $movimento['antiga_cela_label'] = $antiga_cela;
                    }
                } catch (Exception $e) {
                    $movimento = null;
                }
                $interno['movimento'] = $movimento;
                $internos_com_movimento[] = $interno;
            }

            // Formatar identificador da cela (galeria-bloco-cela)
            $cela_identificador = $galeria . ($bloco ?: '') . '-' . $cela;

            $tem_mais_dados = false; // Inicializar variável

            // Saídas da cela: quem saiu DESTA CELA ESPECÍFICA (galeria+bloco+cela)
            $saidas = [];
            try {
                // Buscar todas as movimentações onde valor_antigo = número desta cela
                $sql_saidas = "
                    SELECT h.ipen, h.valor_antigo, h.valor_novo, h.data_alteracao
                    FROM internos_historico_detalhado h
                    WHERE h.campo = 'res'
                    AND h.valor_antigo = :cela_num
                    AND h.data_alteracao >= :data_limite
                    ORDER BY h.data_alteracao DESC
                    LIMIT 100
                ";
                $stmt_s = $pdo->prepare($sql_saidas);
                $stmt_s->execute([
                    ':cela_num' => $cela,
                    ':data_limite' => $data_limite
                ]);

                $ipens_processados = [];
                while ($row = $stmt_s->fetch()) {
                    if (in_array($row['ipen'], $ipens_processados)) continue; // Evitar duplicatas

                    // Verificar se na época da mudança, este interno estava nesta cela específica
                    // Para isso, verificamos as mudanças de galeria/bloco na época

                    // Buscar mudanças de galeria/bloco na mesma data
                    $stmt_gal = $pdo->prepare("
                        SELECT campo, valor_antigo, valor_novo
                        FROM internos_historico_detalhado
                        WHERE ipen = ? AND campo IN ('galeria', 'bloco')
                        AND data_alteracao = ?
                    ");
                    $stmt_gal->execute([$row['ipen'], $row['data_alteracao']]);

                    $galeria_epoca = null;
                    $bloco_epoca = null;

                    while ($hist = $stmt_gal->fetch()) {
                        if ($hist['campo'] === 'galeria' && $hist['valor_antigo']) {
                            $galeria_epoca = $hist['valor_antigo'];
                        }
                        if ($hist['campo'] === 'bloco' && $hist['valor_antigo']) {
                            $bloco_epoca = $hist['valor_antigo'];
                        }
                    }

                    // Se encontrou galeria e bloco que correspondem à nossa cela, é uma saída válida
                    if ($galeria_epoca == $galeria && $bloco_epoca == $bloco) {
                        // Buscar informações atuais do interno
                        $info_atual_stmt = $pdo->prepare("
                            SELECT galeria, bloco, res, nome, nome_social
                            FROM internos
                            WHERE ipen = ?
                        ");
                        $info_atual_stmt->execute([$row['ipen']]);
                        $info_atual = $info_atual_stmt->fetch();

                        if ($info_atual) {
                            // Formatar destino
                            $destino = $row['valor_novo'];
                            if ($destino && !preg_match('/[a-zA-Z]/', $destino)) {
                                // Se é apenas número, tenta inferir galeria/bloco pelo destino atual
                                $dest_info_stmt = $pdo->prepare("
                                    SELECT galeria, bloco
                                    FROM internos
                                    WHERE ipen = ? AND res = ?
                                ");
                                $dest_info_stmt->execute([$row['ipen'], $destino]);
                                $dest_info = $dest_info_stmt->fetch();

                                if ($dest_info) {
                                    $destino = $dest_info['galeria'] . ($dest_info['bloco'] ?: '') . '-' . $destino;
                                } else {
                                    $destino = 'Cela ' . $destino;
                                }
                            }

                            $saidas[] = [
                                'ipen' => $row['ipen'],
                                'nome' => $info_atual['nome_social'] ?: $info_atual['nome'],
                                'data_saida' => $row['data_alteracao'],
                                'destino' => $destino
                            ];

                            $ipens_processados[] = $row['ipen'];
                        }
                    }
                }
            } catch (Exception $e) {
                $saidas = [];
            }

            // Entradas na cela: quem veio PARA ESTA CELA ESPECÍFICA (galeria+bloco+cela)
            $entradas = [];
            try {
                // Usar a mesma lógica de detecção de mudanças para encontrar entradas
                $sql_entradas = "
                    SELECT h.ipen, h.valor_antigo, h.data_alteracao
                    FROM internos_historico_detalhado h
                    WHERE h.campo = 'res'
                    AND h.valor_novo = :cela_num
                    AND h.data_alteracao >= :data_limite
                    AND EXISTS (
                        SELECT 1 FROM internos_historico_detalhado h2
                        WHERE h2.ipen = h.ipen
                        AND h2.data_alteracao = h.data_alteracao
                        AND h2.campo = 'galeria'
                        AND h2.valor_novo = :galeria
                    )
                    AND (
                        (:bloco = '' AND NOT EXISTS (
                            SELECT 1 FROM internos_historico_detalhado h3
                            WHERE h3.ipen = h.ipen
                            AND h3.data_alteracao = h.data_alteracao
                            AND h3.campo = 'bloco'
                        )) OR
                        (:bloco != '' AND EXISTS (
                            SELECT 1 FROM internos_historico_detalhado h3
                            WHERE h3.ipen = h.ipen
                            AND h3.data_alteracao = h.data_alteracao
                            AND h3.campo = 'bloco'
                            AND h3.valor_novo = :bloco
                        ))
                    )
                    ORDER BY h.data_alteracao DESC
                    LIMIT 50
                ";
                $stmt_e = $pdo->prepare($sql_entradas);
                $stmt_e->execute([
                    ':cela_num' => $cela,
                    ':data_limite' => $data_limite,
                    ':galeria' => $galeria,
                    ':bloco' => $bloco
                ]);

                $ipens_encontrados = [];
                while ($row = $stmt_e->fetch()) {
                    // Verificar se este interno está atualmente nesta cela específica
                    $info_stmt = $pdo->prepare("SELECT galeria, bloco, res FROM internos WHERE ipen = ?");
                    $info_stmt->execute([$row['ipen']]);
                    $info = $info_stmt->fetch();

                    // Só incluir se o interno realmente está nesta cela específica (galeria+bloco+cela)
                    if ($info && $info['galeria'] == $galeria && $info['bloco'] == $bloco && $info['res'] == $cela) {
                        if (!in_array($row['ipen'], $ipens_encontrados)) {
                            $ipens_encontrados[] = $row['ipen'];

                            // Buscar nome
                            $nome_stmt = $pdo->prepare("SELECT nome, nome_social FROM internos WHERE ipen = ?");
                            $nome_stmt->execute([$row['ipen']]);
                            $n = $nome_stmt->fetch();

                            // Formatar origem usando o histórico completo
                            $origem = $row['valor_antigo'];
                            if ($origem && !preg_match('/[a-zA-Z]/', $origem)) {
                                // Se é apenas um número, precisa descobrir a galeria e bloco na época
                                // Para isso, busca o histórico completo do interno na época da mudança

                                $hist_completo_stmt = $pdo->prepare("
                                    SELECT campo, valor_antigo, valor_novo, data_alteracao
                                    FROM internos_historico_detalhado
                                    WHERE ipen = ? AND data_alteracao <= ?
                                    ORDER BY data_alteracao DESC
                                ");
                                $hist_completo_stmt->execute([$row['ipen'], $row['data_alteracao']]);

                                $galeria_epoca_origem = null;
                                $bloco_epoca_origem = null;

                                while ($hist = $hist_completo_stmt->fetch()) {
                                    if ($hist['campo'] === 'galeria' && $hist['valor_antigo']) {
                                        $galeria_epoca_origem = $hist['valor_antigo'];
                                    }
                                    if ($hist['campo'] === 'bloco' && $hist['valor_antigo']) {
                                        $bloco_epoca_origem = $hist['valor_antigo'];
                                    }

                                    // Se já encontrou galeria e bloco, pode parar
                                    if ($galeria_epoca_origem && $bloco_epoca_origem) {
                                        break;
                                    }
                                }

                                // Se encontrou galeria e bloco da época, usa eles
                                if ($galeria_epoca_origem && $bloco_epoca_origem) {
                                    $origem = $galeria_epoca_origem . ($bloco_epoca_origem ?: '') . '-' . $origem;
                                } else {
                                    // Fallback: busca alguma referência atual
                                    $ref_stmt = $pdo->prepare("
                                        SELECT galeria, bloco
                                        FROM internos
                                        WHERE res = ?
                                        LIMIT 1
                                    ");
                                    $ref_stmt->execute([$origem]);
                                    $ref = $ref_stmt->fetch();

                                    if ($ref) {
                                        $origem = $ref['galeria'] . ($ref['bloco'] ?: '') . '-' . $origem;
                                    } else {
                                        $origem = 'Cela ' . $origem;
                                    }
                                }
                            } elseif ($origem && preg_match('/^([A-Z])-([0-9]+)$/', $origem, $matches)) {
                                // Se está no formato "A-6", busca galeria atual para completar
                                $origem = $galeria . '-' . $origem;
                            }

                            $entradas[] = [
                                'ipen' => $row['ipen'],
                                'nome' => $n ? ($n['nome_social'] ?: $n['nome']) : '',
                                'data_entrada' => $row['data_alteracao'],
                                'origem' => $origem ?: 'Não informado'
                            ];
                        }
                    }
                }
            } catch (Exception $e) {
                $entradas = [];
            }

            echo json_encode([
                'success' => true,
                'galeria' => $galeria,
                'bloco' => $bloco,
                'ala' => $ala,
                'cela' => $cela,
                'internos' => $internos_com_movimento,
                'eletronicos' => $eletronicos,
                'saidas' => $saidas,
                'entradas' => $entradas,
                'medidas_disciplinares' => $medidas_disciplinares,
                'itens_apreendidos' => $itens_apreendidos,
                'ultima_importacao' => $ultima_importacao,
                'tem_mais_dados' => $tem_mais_dados,
                'data_limite' => $data_limite
            ]);
            exit;
        }

        // BUSCA POR IPEN/NOME
        if ($_POST['action'] === 'search_interno') {
            $termo = trim($_POST['termo'] ?? '');

            if (strlen($termo) < 2) {
                echo json_encode(['success' => true, 'resultados' => []]);
                exit;
            }

            $query = "
                SELECT DISTINCT ipen, nome, nome_social, galeria, bloco, ala, res
                FROM internos
                WHERE status = 'A' AND (
                    ipen LIKE :termo OR
                    nome LIKE :termo OR
                    nome_social LIKE :termo
                )
                ORDER BY nome ASC
                LIMIT 20
            ";

            $termo_search = "%$termo%";
            $stmt = $pdo->prepare($query);
            $stmt->execute([':termo' => $termo_search]);
            $resultados = $stmt->fetchAll();

            echo json_encode([
                'success' => true,
                'resultados' => $resultados
            ]);
            exit;
        }

        // BUSCAR DETALHES DE ELETRÔNICOS POR TIPO NA CELA
        if ($_POST['action'] === 'get_eletronicos_detalhes') {
            $galeria = trim($_POST['galeria'] ?? '');
            $bloco = trim($_POST['bloco'] ?? '');
            $ala = trim($_POST['ala'] ?? '');
            $cela = trim($_POST['cela'] ?? '');
            $tipo = trim($_POST['tipo'] ?? '');

            if (empty($galeria) || empty($cela) || empty($tipo)) {
                echo json_encode(['success' => false, 'error' => 'Parâmetros inválidos']);
                exit;
            }

            // Buscar internos da cela
            if ($bloco !== '') {
                $query = "
                    SELECT ipen
                    FROM internos
                    WHERE galeria = :gal AND bloco = :bloco AND ala = :ala AND res = :cela AND status = 'A'
                ";
                $stmt = $pdo->prepare($query);
                $stmt->execute([':gal' => $galeria, ':bloco' => $bloco, ':ala' => $ala, ':cela' => $cela]);
            } else {
                $query = "
                    SELECT ipen
                    FROM internos
                    WHERE galeria = :gal AND ala = :ala AND res = :cela AND status = 'A'
                ";
                $stmt = $pdo->prepare($query);
                $stmt->execute([':gal' => $galeria, ':ala' => $ala, ':cela' => $cela]);
            }
            $internos = $stmt->fetchAll();
            $ipens_cela = array_column($internos, 'ipen');

            if (empty($ipens_cela)) {
                echo json_encode(['success' => true, 'itens' => []]);
                exit;
            }

            // Buscar itens do tipo
            $placeholders = implode(',', array_fill(0, count($ipens_cela), '?'));
            $sql_ele = "SELECT e.data_entrada, e.id_interno, i.nome, i.nome_social FROM internos_eletronicos e JOIN internos i ON e.id_interno = i.ipen WHERE e.id_interno IN ($placeholders) AND e.situacao = 'Na Cela' AND e.tipo_item = ? ORDER BY i.nome";
            $stmt_ele = $pdo->prepare($sql_ele);
            $params = array_merge($ipens_cela, [$tipo]);
            $stmt_ele->execute($params);
            $itens = $stmt_ele->fetchAll();

            // Format
            $formatted = array_map(function ($item) {
                return [
                    'ipen' => $item['id_interno'],
                    'nome' => $item['nome_social'] ?: $item['nome'],
                    'data_entrada' => $item['data_entrada']
                ];
            }, $itens);

            echo json_encode(['success' => true, 'itens' => $formatted]);
            exit;
        } elseif ($_POST['action'] === 'fetch_itens_cela') {
            $galeria = $_POST['galeria'] ?? '';
            $bloco = $_POST['bloco'] ?? '';
            $cela = $_POST['cela'] ?? '';
            $tipo_item = $_POST['tipo_item'] ?? '';

            if (empty($galeria) || empty($cela) || empty($tipo_item)) {
                echo json_encode(['success' => false, 'error' => 'Parâmetros inválidos']);
                exit;
            }

            try {
                // Buscar internos da cela com tratamento especial para Semiaberto
                if ($bloco !== '') {
                    // Caso especial para galerias Semiaberto (SA, SB, SC, SD, SE, ST)
                    if (in_array($galeria, ['SA', 'SB', 'SC', 'SD', 'SE', 'ST'])) {
                        // Para Semiaberto, galeria no banco é 'S' e bloco é a letra (A, B, C, etc.)
                        $query = "
                            SELECT ipen, nome, nome_social
                            FROM internos
                            WHERE galeria = 'S' AND bloco = :bloco_real AND res = :cela AND status = 'A'
                        ";
                        $bloco_real = substr($galeria, 1); // Extrai E de SE, A de SA, etc.
                        $stmt = $pdo->prepare($query);
                        $stmt->execute([':bloco_real' => $bloco_real, ':cela' => $cela]);
                    } else {
                        // Para galerias normais
                        $query = "
                            SELECT ipen, nome, nome_social
                            FROM internos
                            WHERE galeria = :gal AND bloco = :bloco AND res = :cela AND status = 'A'
                        ";
                        $stmt = $pdo->prepare($query);
                        $stmt->execute([':gal' => $galeria, ':bloco' => $bloco, ':cela' => $cela]);
                    }
                } else {
                    $query = "
                        SELECT ipen, nome, nome_social
                        FROM internos
                        WHERE galeria = :gal AND res = :cela AND status = 'A'
                    ";
                    $stmt = $pdo->prepare($query);
                    $stmt->execute([':gal' => $galeria, ':cela' => $cela]);
                }
                $internos = $stmt->fetchAll();
                $ipens_cela = array_column($internos, 'ipen');

                if (empty($ipens_cela)) {
                    echo json_encode(['success' => true, 'itens' => []]);
                    exit;
                }

                // Buscar itens do tipo com modelo e observações
                $placeholders = implode(',', array_fill(0, count($ipens_cela), '?'));
                $sql_ele = "
                    SELECT e.id_interno, i.nome, i.nome_social, e.data_entrada, e.tipo_item,
                           e.marca_modelo, e.tem_fonte, e.tem_controle, e.obs as observacoes,
                           e.cor, e.estado_conservacao, e.polegadas, e.tamanho, e.capacidade,
                           e.comprimento
                    FROM internos_eletronicos e
                    JOIN internos i ON e.id_interno = i.ipen
                    WHERE e.id_interno IN ($placeholders) AND e.situacao = 'Na Cela' AND (e.tipo_item = ? OR e.tipo_item = ?)
                    ORDER BY i.nome
                ";
                $stmt_ele = $pdo->prepare($sql_ele);
                $params = array_merge($ipens_cela, [$tipo_item, $tipo_item . 's']); // Tenta singular e plural
                $stmt_ele->execute($params);
                $itens = $stmt_ele->fetchAll();

                // Format com detalhes completos
                $formatted = array_map(function ($item) {
                    return [
                        'ipen' => $item['id_interno'],
                        'nome' => $item['nome_social'] ?: $item['nome'],
                        'data_entrada' => $item['data_entrada'],
                        'marca_modelo' => $item['marca_modelo'],
                        'tem_fonte' => $item['tem_fonte'],
                        'tem_controle' => $item['tem_controle'],
                        'observacoes' => $item['observacoes'],
                        'cor' => $item['cor'],
                        'estado_conservacao' => $item['estado_conservacao'],
                        'polegadas' => $item['polegadas'],
                        'tamanho' => $item['tamanho'],
                        'capacidade' => $item['capacidade'],
                        'comprimento' => $item['comprimento']
                    ];
                }, $itens);

                echo json_encode(['success' => true, 'itens' => $formatted]);
                exit;
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                exit;
            }
        } elseif ($_POST['action'] === 'fetch_historico_cela') {
            // Limpar buffer e forçar header JSON
            ob_clean();
            header('Content-Type: application/json');

            $galeria = $_POST['galeria'] ?? '';
            $bloco = $_POST['bloco'] ?? '';
            $cela = $_POST['cela'] ?? '';

            if (empty($galeria) || empty($cela)) {
                echo json_encode(['success' => false, 'error' => 'Parâmetros inválidos']);
                exit;
            }

            try {
                $historico = [];

                // MEDIDAS DISCIPLINARES dos internos da cela
                if ($bloco !== '') {
                    $query_internos = "
                        SELECT ipen FROM internos
                        WHERE galeria = :gal AND bloco = :bloco AND res = :cela AND status = 'A'
                    ";
                    $stmt_internos = $pdo->prepare($query_internos);
                    $stmt_internos->execute([':gal' => $galeria, ':bloco' => $bloco, ':cela' => $cela]);
                } else {
                    $query_internos = "
                        SELECT ipen FROM internos
                        WHERE galeria = :gal AND res = :cela AND status = 'A'
                    ";
                    $stmt_internos = $pdo->prepare($query_internos);
                    $stmt_internos->execute([':gal' => $galeria, ':cela' => $cela]);
                }
                $internos_cela = $stmt_internos->fetchAll(PDO::FETCH_COLUMN, 0);

                if (!empty($internos_cela)) {
                    $placeholders = implode(',', array_fill(0, count($internos_cela), '?'));
                    $query_md = "
                        SELECT i.ipen, i.nome, i.nome_social, md.data_inicio, md.data_fim, md.tipo_medida,
                               'medida_disciplinar' as tipo
                        FROM internos_md_medidas md
                        JOIN internos i ON md.ipen = i.ipen
                        WHERE md.ipen IN ($placeholders)
                        AND (md.data_fim >= CURDATE() OR md.data_fim IS NULL)
                        ORDER BY md.data_inicio DESC
                        LIMIT 50
                    ";
                    $stmt_md = $pdo->prepare($query_md);
                    $stmt_md->execute($internos_cela);
                    $medidas = $stmt_md->fetchAll();

                    foreach ($medidas as $medida) {
                        $historico[] = [
                            'data' => $medida['data_inicio'],
                            'tipo' => 'medida_disciplinar',
                            'ipen' => $medida['ipen'],
                            'nome' => $medida['nome_social'] ?: $medida['nome'],
                            'origem' => null,
                            'destino' => $medida['tipo_medida'],
                            'observacoes' => $medida['data_fim'] ? "Até " . date('d/m/Y', strtotime($medida['data_fim'])) : 'Indeterminado'
                        ];
                    }
                }

                // Ordenar histórico por data
                usort($historico, function ($a, $b) {
                    return strtotime($b['data']) - strtotime($a['data']);
                });

                echo json_encode(['success' => true, 'historico' => $historico]);
                exit;
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                exit;
            }
        } else {
            // Ações não reconhecidas
            echo json_encode(['success' => false, 'error' => 'Ação não reconhecida']);
            exit;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

// FUNÇÃO PARA AGRUPAR CELAS POR BLOCO
function agruparCelasPorBloco($galeria, $celas_array)
{
    $blocos = [];

    if (in_array($galeria, ['A', 'B', 'C', 'D'])) {
        // Galerias A-D: Bloco A (1-9), Bloco B (10-18)
        $bloco_a = [];
        $bloco_b = [];
        foreach ($celas_array as $cela_num => $cela_data) {
            $num = (int)$cela_num;
            if ($num >= 1 && $num <= 9) {
                $bloco_a[$cela_num] = $cela_data;
            } elseif ($num >= 10 && $num <= 18) {
                $bloco_b[$cela_num] = $cela_data;
            }
        }
        if (!empty($bloco_a)) $blocos['A'] = $bloco_a;
        if (!empty($bloco_b)) $blocos['B'] = $bloco_b;
    } elseif ($galeria === 'E') {
        // Galeria E: Bloco A (1-8), Bloco B (9-18) - Celas 9 e 10 são LGBT
        $bloco_a = [];
        $bloco_b = [];
        foreach ($celas_array as $cela_num => $cela_data) {
            $num = (int)$cela_num;
            if ($num >= 1 && $num <= 8) {
                $bloco_a[$cela_num] = $cela_data;
            } elseif ($num >= 9 && $num <= 18) {
                $bloco_b[$cela_num] = $cela_data;
            }
        }
        if (!empty($bloco_a)) $blocos['A'] = $bloco_a;
        if (!empty($bloco_b)) $blocos['B'] = $bloco_b;
    } else {
        // Todas as demais (SA, SB, SC, SD, SE, ST, T, F, G, H): sem blocos internos
        $blocos[''] = $celas_array;
    }

    return $blocos;
}

// Obter última data de importação
$ultima_importacao_geral = null;
try {
    $result = $pdo->query("SELECT MAX(data_importacao) AS ultima_data FROM internos_importacao");
    if ($result && $row = $result->fetch()) {
        $ultima_importacao_geral = $row['ultima_data'];
    }
} catch (Exception $e) {
    $ultima_importacao_geral = null;
}

// BUSCAR ESTRUTURA COMPLETA DA CADEIA - CONTANDO APENAS ATIVOS
$cache_key_estrutura = 'estrutura_cadeia_ativos';
$resultado = getCache($cache_key_estrutura);

if ($resultado === null) {
    $estrutura_query = "
        SELECT
            i.galeria,
            i.bloco,
            i.ala,
            i.res AS cela,
            COUNT(*) AS total_ativos
        FROM internos i
        WHERE i.status = 'A' AND i.res NOT IN ('0', '') AND i.res IS NOT NULL
        GROUP BY i.galeria, i.bloco, i.ala, i.res
        ORDER BY i.galeria, i.bloco, i.ala, CAST(i.res AS UNSIGNED)
    ";
    $resultado = $pdo->query($estrutura_query)->fetchAll();
    setCache($cache_key_estrutura, $resultado, 300); // 5 minutos
}

// Organizar em estrutura hierárquica (galeria -> bloco -> cela) COM detecção de mudanças
$estrutura_cadeia = [];
foreach ($resultado as $row) {
    $galeria = $row['galeria'];
    $bloco = $row['bloco'];
    $cela = $row['cela'];

    // Para semi-aberto (galeria S), criar chave virtual: SA, SB, SC, SD, SE, ST
    if ($galeria === 'S') {
        $galeria_virtual = 'S' . $bloco;
    } else {
        $galeria_virtual = $galeria;
    }

    if (!isset($estrutura_cadeia[$galeria_virtual])) {
        $estrutura_cadeia[$galeria_virtual] = [];
    }

    // Verificar se há mudanças recentes nesta cela
    $cache_key_mudanca = "mudanca_cela_{$galeria}_{$bloco}_{$cela}";
    $tem_mudanca_recente = getCache($cache_key_mudanca);

    if ($tem_mudanca_recente === null) {
        try {
            $mudanca_query = "
                SELECT COUNT(*) as cnt FROM internos_historico_detalhado h
                WHERE h.campo = 'res'
                AND (h.valor_antigo = :cela OR h.valor_novo = :cela)
                AND h.data_alteracao >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                AND EXISTS (
                    SELECT 1 FROM internos_historico_detalhado h2
                    WHERE h2.ipen = h.ipen
                    AND h2.data_alteracao = h.data_alteracao
                    AND h2.campo = 'galeria'
                    AND h2.valor_antigo = :galeria
                )
                AND (
                    (:bloco = '' AND NOT EXISTS (
                        SELECT 1 FROM internos_historico_detalhado h3
                        WHERE h3.ipen = h.ipen
                        AND h3.data_alteracao = h.data_alteracao
                        AND h3.campo = 'bloco'
                    )) OR
                    (:bloco != '' AND EXISTS (
                        SELECT 1 FROM internos_historico_detalhado h3
                        WHERE h3.ipen = h.ipen
                        AND h3.data_alteracao = h.data_alteracao
                        AND h3.campo = 'bloco'
                        AND h3.valor_antigo = :bloco
                    ))
                )
                LIMIT 1
            ";
            $mudanca_stmt = $pdo->prepare($mudanca_query);
            $mudanca_stmt->execute([':cela' => $cela, ':galeria' => $galeria, ':bloco' => $bloco]);
            $tem_mudanca_recente = $mudanca_stmt->fetch()['cnt'] > 0;
            setCache($cache_key_mudanca, $tem_mudanca_recente, 120); // 2 minutos
        } catch (Exception $e) {
            $tem_mudanca_recente = false;
        }
    }

    // Verificar se há MDs ativas nesta cela
    $cache_key_md = "md_cela_{$galeria}_{$bloco}_{$cela}";
    $tem_md_ativa = getCache($cache_key_md);

    if ($tem_md_ativa === null) {
        try {
            $md_query = "
                    SELECT COUNT(*) as cnt
                    FROM internos_md_medidas md
                    JOIN internos i ON md.id_interno = i.ipen
                    WHERE i.galeria = :galeria
                    AND i.bloco = :bloco
                    AND i.res = :cela
                    AND md.status = 'Ativa'
                ";
            $md_stmt = $pdo->prepare($md_query);
            $md_stmt->execute([':galeria' => $galeria, ':bloco' => $bloco, ':cela' => $cela]);
            $tem_md_ativa = $md_stmt->fetch()['cnt'] > 0;
            setCache($cache_key_md, $tem_md_ativa, 300); // 5 minutos
        } catch (Exception $e) {
            $tem_md_ativa = false;
        }
    }

    $estrutura_cadeia[$galeria_virtual][$cela] = [
        'cela' => $cela,
        'total_ativos' => (int)$row['total_ativos'],
        'ala' => $row['ala'],
        'galeria_original' => $galeria_virtual,
        'bloco_original' => $bloco,
        'tem_mudanca' => $tem_mudanca_recente, // Nova flag
        'tem_md' => $tem_md_ativa // Nova flag para MDs
    ];
}

// Reorganizar para agrupar por blocos
$estrutura_com_blocos = [];
foreach ($estrutura_cadeia as $galeria => $celas) {
    $blocos = agruparCelasPorBloco($galeria, $celas);
    $estrutura_com_blocos[$galeria] = $blocos;
}

// Garantir que galerias semi-abertas e especiais existam (mesmo que vazias)
$galerias_obrigatorias = ['SA', 'SB', 'SC', 'SD', 'SE', 'ST', 'T'];
foreach ($galerias_obrigatorias as $gal) {
    if (!isset($estrutura_com_blocos[$gal])) {
        $estrutura_com_blocos[$gal] = ['' => []];
    }
}

// Para galeria T (MD/Castigo), garantir 4 celas (1, 2, 3, 4)
if (isset($estrutura_com_blocos['T'])) {
    $celas_t = $estrutura_com_blocos['T'][''];

    // Adicionar celas faltando (2, 3, 4 se não existirem)
    for ($i = 1; $i <= 4; $i++) {
        if (!isset($celas_t[$i])) {
            $celas_t[$i] = [
                'cela' => $i,
                'total_ativos' => 0,
                'ala' => 'M', // Assumir ala M
                'tem_mudanca' => false
            ];
        }
    }

    $estrutura_com_blocos['T'] = ['' => $celas_t];
}



// ESTATÍSTICAS GERAIS - APENAS ATIVOS
$cache_key_stats = 'stats_gerais';
$stats = getCache($cache_key_stats);

if ($stats === null) {
    $stats_query = "
        SELECT
            COUNT(*) AS total,
            COUNT(DISTINCT galeria) AS total_galerias,
            COUNT(DISTINCT CONCAT(galeria, ala)) AS total_alas,
            COUNT(DISTINCT CONCAT(galeria, ala, res)) AS total_celas
        FROM internos
        WHERE status = 'A'
    ";
    $stats = $pdo->query($stats_query)->fetch();
    setCache($cache_key_stats, $stats, 600); // 10 minutos
}

// Última atualização do histórico
$cache_key_atualizacao = 'ultima_atualizacao';
$ultima_atualizacao = getCache($cache_key_atualizacao);

if ($ultima_atualizacao === null) {
    $ultima_atualizacao_query = "SELECT MAX(data_alteracao) AS ultima_data FROM internos_historico_detalhado";
    $ultima_atualizacao = $pdo->query($ultima_atualizacao_query)->fetch()['ultima_data'] ?? null;
    setCache($cache_key_atualizacao, $ultima_atualizacao, 60); // 1 minuto
}

// Contar por galeria - APENAS ATIVOS
$cache_key_galeria = 'contagem_por_galeria';
$por_galeria = getCache($cache_key_galeria);

if ($por_galeria === null) {
    $por_galeria_query = "
        SELECT
            galeria,
            COUNT(*) AS total
        FROM internos
        WHERE status = 'A'
        GROUP BY galeria
        ORDER BY FIELD(galeria, 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'S', 'T')
    ";
    $por_galeria = $pdo->query($por_galeria_query)->fetchAll(PDO::FETCH_KEY_PAIR);
    setCache($cache_key_galeria, $por_galeria, 600); // 10 minutos
}

// Função para obter informações da galeria
function obterInfoGaleria($galeria)
{
    $info = [
        'A' => ['bg' => '#1e7e34', 'text' => '#fff', 'label' => 'Galeria A'],
        'B' => ['bg' => '#fd7e14', 'text' => '#fff', 'label' => 'Galeria B'],
        'C' => ['bg' => '#0c63e4', 'text' => '#fff', 'label' => 'Galeria C'],
        'D' => ['bg' => '#dc3545', 'text' => '#fff', 'label' => 'Galeria D'],
        'E' => ['bg' => '#6f42c1', 'text' => '#fff', 'label' => 'Galeria E'],
        'F' => ['bg' => '#20c997', 'text' => '#fff', 'label' => 'Galeria F (Enfermaria)'],
        'G' => ['bg' => '#e83e8c', 'text' => '#fff', 'label' => 'Galeria G (Isolados)'],
        'H' => ['bg' => '#6c757d', 'text' => '#fff', 'label' => 'Galeria H (DAL)'],
        'SA' => ['bg' => '#ffc107', 'text' => '#000', 'label' => 'Galeria SA (Semi Aberto A) - 15 celas'],
        'SB' => ['bg' => '#ffb300', 'text' => '#000', 'label' => 'Galeria SB (Semi Aberto B) - 15 celas'],
        'SC' => ['bg' => '#ff9a00', 'text' => '#fff', 'label' => 'Galeria SC (Semi Aberto C) - 15 celas'],
        'SD' => ['bg' => '#ff8c00', 'text' => '#fff', 'label' => 'Galeria SD (Semi Aberto D) - EM REFORMA'],
        'SE' => ['bg' => '#f8b500', 'text' => '#000', 'label' => 'Galeria SE (Semi Aberto)'],
        'ST' => ['bg' => '#ff9800', 'text' => '#fff', 'label' => 'Galeria ST (Semi Aberto)'],
        'T' => ['bg' => '#495057', 'text' => '#fff', 'label' => 'Galeria T (MD / Castigo)'],
    ];

    return $info[$galeria] ?? ['bg' => '#495057', 'text' => '#fff', 'label' => "Galeria $galeria"];
}

// Função para determinar cor do contador (azul normal, vermelho se tem alteração recente)
function obterCorContador($cela_num)
{
    // Verificar se cela tem mudança recente
    // Por enquanto, sempre azul
    return '#0056b3';
}

// Função para determinar classe de cor do contador
function obterClasseContador($cela_num)
{
    return 'contador-normal';
}
