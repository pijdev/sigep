<?php

// includes/internos_recebimento_eletronicos_logica.php

$config = require __DIR__ . '/../conf/db.php';

date_default_timezone_set('America/Sao_Paulo');



try {

    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";

    $pdo = new PDO($dsn, $config['user'], $config['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

} catch (PDOException $e) { die("Erro DB: " . $e->getMessage()); }



if (session_status() === PHP_SESSION_NONE) session_start();

$nome_user = $_SESSION['user_nome'] ?? 'Usuario Sistema';

$setor_user = $_SESSION['user_setor'] ?? '';

$usuario_logado = $nome_user . ($setor_user ? " (" . mb_strtoupper($setor_user, 'UTF-8') . ")" : "");



// Verificar se usuário tem acesso total (admin ou censura)

$eh_admin = (isset($_SESSION['user_admin']) && $_SESSION['user_admin'] === true);

$eh_censura = (isset($_SESSION['perm_censura']) && $_SESSION['perm_censura'] > 0);

$tem_acesso_total = $eh_admin || $eh_censura;



// Verificar se usuário é da portaria (mas não se for admin ou censura)

$eh_portaria = (isset($_SESSION['perm_portaria']) && $_SESSION['perm_portaria'] > 0) && !$tem_acesso_total;



// Regra de Data (Dias 1-10 meses pares)

$dia = (int)date('d'); $mes = (int)date('n');

$periodoValido = ($mes % 2 == 0) && ($dia >= 1 && $dia <= 10);



// --- FUNÇÕES AUXILIARES ---

function carregarMarcas($pdo, $tipoItem) {

    $stmt = $pdo->prepare("SELECT marca FROM eletronicos_marcas WHERE tipo_item = ? ORDER BY frequencia DESC, marca ASC LIMIT 10");

    $stmt->execute([$tipoItem]);

    return $stmt->fetchAll(PDO::FETCH_COLUMN);

}



function carregarCores($pdo, $tipoItem) {

    $stmt = $pdo->prepare("SELECT cor FROM eletronicos_cores WHERE tipo_item = ? ORDER BY frequencia DESC, cor ASC LIMIT 10");

    $stmt->execute([$tipoItem]);

    return $stmt->fetchAll(PDO::FETCH_COLUMN);

}



function registrarHistorico($pdo, $idItem, $acao, $usuario, $observacoes = '') {

    $stmt = $pdo->prepare("INSERT INTO internos_eletronicos_historico (id_eletronico, acao, usuario, detalhes) VALUES (?, ?, ?, ?)");

    $stmt->execute([$idItem, $acao, $usuario, $observacoes]);

}



function atualizarFrequenciaMarca($pdo, $tipoItem, $marca) {

    $stmt = $pdo->prepare("INSERT INTO eletronicos_marcas (tipo_item, marca, frequencia) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE frequencia = frequencia + 1");

    $stmt->execute([$tipoItem, $marca]);

}



function atualizarFrequenciaCor($pdo, $tipoItem, $cor) {

    $stmt = $pdo->prepare("INSERT INTO eletronicos_cores (tipo_item, cor, frequencia) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE frequencia = frequencia + 1");

    $stmt->execute([$tipoItem, $cor]);

}



// --- BACKEND ---

if (isset($_REQUEST['acao'])) {

    // 1. BUSCAR INTERNO

    if ($_REQUEST['acao'] === 'buscar_interno') {

        ob_clean(); header('Content-Type: application/json');

        $termo = trim($_REQUEST['termo']);

        try {

            $l = "%$termo%";

            // Buscar internos
            $sql = "SELECT ipen, nome, nome_social, galeria, bloco, res,
                           'interno' as tipo_dono, galeria, bloco, res
                    FROM internos
                    WHERE (ipen LIKE ? OR nome LIKE ? OR nome_social LIKE ?) AND status = 'A'
                    LIMIT 5";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([$l,$l,$l]);
            $internos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Buscar donos (setores e outros)
            $sql = "SELECT id, nome, tipo, descricao,
                           'dono' as tipo_dono,
                           NULL as galeria, NULL as bloco, NULL as res,
                           id as ipen
                    FROM eletronicos_donos
                    WHERE (nome LIKE ? OR descricao LIKE ?) AND ativo = 1 AND tipo = 'setor'
                    LIMIT 5";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([$l,$l]);
            $donos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Combinar resultados
            $resultados = array_merge($internos, $donos);

            // Ordenar por relevância (nome exato primeiro)
            usort($resultados, function($a, $b) use ($termo) {
                $a_nome = strtolower($a['nome']);
                $b_nome = strtolower($b['nome']);
                $termo_lower = strtolower($termo);

                // Exato match primeiro
                if ($a_nome === $termo_lower && $b_nome !== $termo_lower) return -1;
                if ($a_nome !== $termo_lower && $b_nome === $termo_lower) return 1;

                // Depois começa com
                if (strpos($a_nome, $termo_lower) === 0 && strpos($b_nome, $termo_lower) !== 0) return -1;
                if (strpos($a_nome, $termo_lower) !== 0 && strpos($b_nome, $termo_lower) === 0) return 1;

                // Depois contém
                if (strpos($a_nome, $termo_lower) !== false && strpos($b_nome, $termo_lower) === false) return -1;
                if (strpos($a_nome, $termo_lower) === false && strpos($b_nome, $termo_lower) !== false) return 1;

                return strcmp($a_nome, $b_nome);
            });

            // Limitar a 10 resultados no total
            $resultados = array_slice($resultados, 0, 10);

            echo json_encode(['status'=>'success', 'dados'=>$resultados]);

        } catch (Exception $e) { echo json_encode(['error'=>$e->getMessage()]); }

        exit;

    }



    // 2. REGISTRAR LOTE

    if ($_REQUEST['acao'] === 'registrar_lote' && $_SERVER['REQUEST_METHOD'] === 'POST') {

        ob_clean(); header('Content-Type: application/json');

        try {

            // Restrição: Portaria só pode registrar em períodos válidos

            if ($eh_portaria && !$periodoValido) {

                throw new Exception("Usuário da portaria só pode registrar entradas nos dias 01 a 10 dos meses pares.");

            }



            $pdo->beginTransaction();

            $ipen = $_POST['ipen'];
            $tipo_dono = $_POST['tipo_dono'] ?? 'interno';

            $ids_gerados = [];

            $registrados = 0;



            $stmtInsert = $pdo->prepare("INSERT INTO internos_eletronicos (id_interno, id_dono, tipo_item, marca_modelo, cor, estado_conservacao, nota_fiscal, polegadas, tem_controle, tem_fonte, tamanho, capacidade, comprimento, nome_item_personalizado, descricao_personalizada, situacao, data_entrada, entregue_por, cadastrado_por) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Estoque', NOW(), ?, ?)");



            // Verificar se está usando o novo formato (offcanvas)

            if (!empty($_POST['itensSelecionados'])) {

                $itensSelecionados = json_decode($_POST['itensSelecionados'], true);



                // Determinar id_dono e id_interno baseado no tipo
            $id_dono = null;
            $id_interno_final = $ipen;

            if ($tipo_dono === 'dono') {
                // Para setores, buscar id_dono e deixar id_interno como null
                $stmt = $pdo->prepare("SELECT id FROM eletronicos_donos WHERE id = ? AND ativo = 1");
                $stmt->execute([$ipen]);
                $dono = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($dono) {
                    $id_dono = $dono['id'];
                    $id_interno_final = null;
                }
            } else {
                // Para internos, buscar id_dono correspondente
                $stmt = $pdo->prepare("SELECT id FROM eletronicos_donos WHERE nome = (SELECT nome FROM internos WHERE ipen = ?) AND tipo = 'interno'");
                $stmt->execute([$ipen]);
                $dono_interno = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($dono_interno) {
                    $id_dono = $dono_interno['id'];
                }
            }

            foreach($itensSelecionados as $item) {

                    // Extrair campos específicos baseados no tipo

                    $polegadas = $item['polegadas'] ?? null;

                    $tem_controle = $item['tem_controle'] ?? null;

                    $tem_fonte = $item['tem_fonte'] ?? null;

                    $tamanho = $item['tamanho'] ?? null;

                    $capacidade = $item['capacidade'] ?? null;

                    $comprimento = $item['comprimento'] ?? null;

                    $nome_item_personalizado = $item['nome_item'] ?? null;

                    $descricao_personalizada = $item['descricao'] ?? null;



                    // Tratar campos vazios como 'Não se aplica'

                    if (empty($polegadas)) $polegadas = 'Não se aplica';

                    if (empty($tem_controle)) $tem_controle = 'Não se aplica';

                    if (empty($tem_fonte)) $tem_fonte = 'Não se aplica';

                    if (empty($tamanho)) $tamanho = 'Não se aplica';

                    if (empty($capacidade)) $capacidade = 'Não se aplica';

                    if (empty($comprimento)) $comprimento = 'Não se aplica';

                    if (empty($nome_item_personalizado)) $nome_item_personalizado = 'Não se aplica';

                    if (empty($descricao_personalizada)) $descricao_personalizada = 'Não se aplica';



                    // Montar marca/modelo baseado no tipo

                    $marcaModelo = '';

                    if ($item['tipo'] === 'Outros') {

                        $marcaModelo = $item['nome_item'];

                        if (!empty($item['descricao'])) {

                            $marcaModelo .= ' - ' . $item['descricao'];

                        }

                    } else {
                        $marcaModelo = $item['marca'] ?? '';
                    }

                    $stmtInsert->execute([
                        $id_interno_final,
                        $id_dono,
                        $item['tipo'],
                        $marcaModelo,
                        $item['cor'] ?: 'Preto',
                        $item['estado'] ?: 'Novo',
                        $item['nf'] ?: '',
                        $polegadas,
                        $tem_controle,
                        $tem_fonte,
                        $tamanho,
                        $capacidade,
                        $comprimento,
                        $nome_item_personalizado,
                        $descricao_personalizada,
                        $_POST['entregue_por'],
                        $usuario_logado
                    ]);

                    $idGerado = $pdo->lastInsertId();
                    $ids_gerados[] = $idGerado;
                    $registrados++;



                    // Registrar no histórico

                    registrarHistorico($pdo, $idGerado, 'ENTRADA', $usuario_logado, "Item {$item['tipo']} recebido no estoque");



                    // Atualizar frequência de marca e cor

                    if ($item['tipo'] !== 'Outros' && !empty($item['marca'])) {

                        atualizarFrequenciaMarca($pdo, $item['tipo'], $item['marca']);

                    }

                    if (!empty($item['cor'])) {

                        atualizarFrequenciaCor($pdo, $item['tipo'], $item['cor']);

                    }

                }

            } else {

                // Formato antigo (compatibilidade)

                $itens = $_POST['itens'] ?? [];



                foreach($itens as $tipo => $dados) {

                    if(isset($dados['check'])) {

                        if($tipo === 'Outros') {

                            // Para item "Outros", usa campos personalizados

                            $stmtInsert->execute([

                                $ipen,

                                $dados['nome_item'], // tipo_item = nome personalizado

                                $dados['descricao'], // marca_modelo = descrição

                                $dados['cor'] ?? 'Preto',

                                $dados['estado'],

                                $dados['nf'],

                                'Não se aplica', // polegadas

                                'Não se aplica', // tem_controle

                                'Não se aplica', // tem_fonte

                                'Não se aplica', // tamanho

                                'Não se aplica', // capacidade

                                'Não se aplica', // comprimento

                                $dados['nome_item'], // nome_item_personalizado

                                $dados['descricao'], // descricao_personalizada

                                $_POST['entregue_por'],

                                $usuario_logado

                            ]);

                        } else {

                            // Itens padrão

                            $stmtInsert->execute([

                                $ipen,

                                $tipo, // tipo_item

                                $dados['marca'], // marca_modelo

                                $dados['cor'],

                                $dados['estado'],

                                $dados['nf'],

                                'Não se aplica', // polegadas

                                'Não se aplica', // tem_controle

                                'Não se aplica', // tem_fonte

                                'Não se aplica', // tamanho

                                'Não se aplica', // capacidade

                                'Não se aplica', // comprimento

                                'Não se aplica', // nome_item_personalizado

                                'Não se aplica', // descricao_personalizada

                                $_POST['entregue_por'],

                                $usuario_logado

                            ]);

                        }

                        $ids_gerados[] = $pdo->lastInsertId();

                        $registrados++;

                    }

                }

            }



            if($registrados == 0) throw new Exception("Nenhum item selecionado.");



            $pdo->commit();

            echo json_encode(['status'=>'success', 'msg'=>"$registrados itens recebidos no ESTOQUE.", 'ids'=>implode(',', $ids_gerados)]);

        } catch (Exception $e) {

            $pdo->rollBack();

            echo json_encode(['status'=>'error', 'msg'=>$e->getMessage()]);

        }

        exit;

    }



    // 3. MARCAR ENTREGUE

    if ($_REQUEST['acao'] === 'marcar_entregue') {

        ob_clean(); header('Content-Type: application/json');

        try {

            $ids = explode(',', $_POST['ids']);

            $erros_conflito = [];

            $sucessos = 0;



            $stmtGet = $pdo->prepare("SELECT id_interno, tipo_item FROM internos_eletronicos WHERE id = ?");

            $stmtCheck = $pdo->prepare("SELECT id FROM internos_eletronicos WHERE id_interno = ? AND tipo_item = ? AND situacao = 'Na Cela'");

            $stmtUpdate = $pdo->prepare("UPDATE internos_eletronicos SET situacao = 'Na Cela', data_entrega_interno = NOW() WHERE id = ?");



            $pdo->beginTransaction();



            foreach($ids as $id) {

                $stmtGet->execute([$id]);

                $item = $stmtGet->fetch(PDO::FETCH_ASSOC);

                if(!$item) continue;



                $stmtCheck->execute([$item['id_interno'], $item['tipo_item']]);



                // Permite múltiplos ventiladores ou máquinas? Se não, mantém a trava.

                // Aqui mantendo a trava rígida de 1 por tipo na cela.

                if ($stmtCheck->rowCount() > 0) {

                    $erros_conflito[] = $item['tipo_item'];

                } else {

                    $stmtUpdate->execute([$id]);

                    $sucessos++;

                }

            }



            $pdo->commit();



            if (count($erros_conflito) > 0) {

                $itens_bloqueados = implode(', ', array_unique($erros_conflito));

                echo json_encode([

                    'status' => 'warning',

                    'msg' => "Atenção: $sucessos itens liberados.\n\nBLOQUEADOS ($itens_bloqueados): Já existe item ativo na cela. Faça a baixa do antigo primeiro."

                ]);

            } else {

                echo json_encode(['status' => 'success']);

            }



        } catch (Exception $e) {

            $pdo->rollBack();

            echo json_encode(['status'=>'error', 'msg'=>$e->getMessage()]);

        }

        exit;

    }



    // 5. CARREGAR MARCAS DINÂMICAS

    if ($_REQUEST['acao'] === 'carregar_marcas') {

        ob_clean(); header('Content-Type: application/json');

        $tipoItem = $_REQUEST['tipo_item'];

        try {

            $marcas = carregarMarcas($pdo, $tipoItem);

            echo json_encode(['status'=>'success', 'marcas'=>$marcas]);

        } catch(Exception $e) { echo json_encode(['status'=>'error', 'msg'=>$e->getMessage()]); }

        exit;

    }



    // 6. CARREGAR CORES DINÂMICAS

    if ($_REQUEST['acao'] === 'carregar_cores') {

        ob_clean(); header('Content-Type: application/json');

        $tipoItem = $_REQUEST['tipo_item'];

        try {

            $cores = carregarCores($pdo, $tipoItem);

            echo json_encode(['status'=>'success', 'cores'=>$cores]);

        } catch(Exception $e) { echo json_encode(['status'=>'error', 'msg'=>$e->getMessage()]); }

        exit;

    }



    // 8. SALVAR MARCA CUSTOM

    if ($_REQUEST['acao'] === 'salvar_marca_custom') {

        ob_clean(); header('Content-Type: application/json');

        $data = json_decode(file_get_contents('php://input'), true);

        try {

            $tipoItem = $data['tipo_item'];

            $marca = trim($data['marca']);



            if (empty($marca)) {

                throw new Exception("Marca inválida.");

            }



            // Inserir ou atualizar marca

            $stmt = $pdo->prepare("INSERT INTO eletronicos_marcas (tipo_item, marca, frequencia) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE frequencia = frequencia + 1");

            $stmt->execute([$tipoItem, $marca]);



            echo json_encode(['status' => 'success', 'msg' => 'Marca salva com sucesso!']);

        } catch (Exception $e) {

            echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);

        }

        exit;

    }



    // 9. SALVAR COR CUSTOM

    if ($_REQUEST['acao'] === 'salvar_cor_custom') {

        ob_clean(); header('Content-Type: application/json');

        $data = json_decode(file_get_contents('php://input'), true);

        try {

            $tipoItem = $data['tipo_item'];

            $cor = trim($data['cor']);



            if (empty($cor)) {

                throw new Exception("Cor inválida.");

            }



            // Inserir ou atualizar cor

            $stmt = $pdo->prepare("INSERT INTO eletronicos_cores (tipo_item, cor, frequencia) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE frequencia = frequencia + 1");

            $stmt->execute([$tipoItem, $cor]);



            echo json_encode(['status' => 'success', 'msg' => 'Cor salva com sucesso!']);

        } catch (Exception $e) {

            echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);

        }

        exit;

    }



    // 7. EXCLUIR

    if ($_REQUEST['acao'] === 'excluir') {

        ob_clean(); header('Content-Type: application/json');

        try {

            // Restrição: Portaria não pode excluir

            if ($eh_portaria) {

                throw new Exception("Usuário da portaria não tem permissão para excluir registros.");

            }



            $pdo->prepare("DELETE FROM internos_eletronicos WHERE id = ?")->execute([$_POST['id']]);

            echo json_encode(['status'=>'success']);

        } catch (Exception $e) { echo json_encode(['status'=>'error', 'msg'=>$e->getMessage()]); }

        exit;

    }

}



// --- LISTAGEM COM PAGINAÇÃO E ORDENAÇÃO ---

// Parâmetros de paginação

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

$per_page = isset($_GET['per_page']) ? min(100, max(10, (int)$_GET['per_page'])) : 20;



// Parâmetros de ordenação

$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'data_entrada';

$sort_order = isset($_GET['sort_order']) && $_GET['sort_order'] === 'asc' ? 'ASC' : 'DESC';



// Mapeamento de campos permitidos para ordenação

$allowed_sort_fields = [

    'data_entrada' => 'e.data_entrada',

    'id_interno' => 'e.id_interno',

    'tipo_item' => 'e.tipo_item',

    'situacao' => 'e.situacao',

    'entregue_por' => 'e.entregue_por'

];



// Validar campo de ordenação

$sort_field = isset($allowed_sort_fields[$sort_by]) ? $allowed_sort_fields[$sort_by] : 'e.data_entrada';



// Calcular offset

$offset = ($page - 1) * $per_page;



// Query para contar total de registros

$sqlCount = "SELECT COUNT(*) as total FROM internos_eletronicos e JOIN internos i ON e.id_interno = i.ipen";

$stmtCount = $pdo->query($sqlCount);

$total_registros = $stmtCount->fetch(PDO::FETCH_ASSOC)['total'];

$total_pages = ceil($total_registros / $per_page);



// Query principal com paginação e ordenação

$sqlList = "SELECT e.*, i.nome, i.nome_social, i.galeria, i.bloco, i.res

            FROM internos_eletronicos e

            JOIN internos i ON e.id_interno = i.ipen

            ORDER BY {$sort_field} {$sort_order}, e.id DESC

            LIMIT {$per_page} OFFSET {$offset}";

$lista = $pdo->query($sqlList)->fetchAll(PDO::FETCH_ASSOC);
