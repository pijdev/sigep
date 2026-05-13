<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('America/Sao_Paulo');

// Verificação de autenticação e permissões
$eh_admin = isset($_SESSION['user_admin']) && $_SESSION['user_admin'] === true;
$eh_manutencao = isset($_SESSION['perm_manutencao']) && (int)$_SESSION['perm_manutencao'] > 0;
$eh_censura = isset($_SESSION['perm_censura']) && (int)$_SESSION['perm_censura'] > 0;
$eh_coordenacao = isset($_SESSION['perm_coordenacao']) && (int)$_SESSION['perm_coordenacao'] > 0;
$eh_direcao = isset($_SESSION['perm_direcao']) && (int)$_SESSION['perm_direcao'] > 0;

// Regras de acesso
$acesso_total = $eh_admin || $eh_manutencao;
$acesso_consulta = $acesso_total || $eh_censura || $eh_coordenacao || $eh_direcao;

if (!$acesso_consulta) {
    header('HTTP/1.0 403 Forbidden');
    echo 'Acesso negado.';
    exit;
}

// Conexão com banco de dados
$config = require __DIR__ . '/../../../conf/db.php';
$dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
$pdo = new PDO($dsn, $config['user'], $config['pass'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);

// Funções auxiliares
function manutencao_json_response(array $payload, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function registrarAuditoria($pdo, $id_servico, $acao, $detalhes_anteriores = null, $detalhes_novos = null)
{
    $stmt = $pdo->prepare("
        INSERT INTO manutencao_servicos_auditoria
        (id_servico, acao, usuario_execucao, data_acao, detalhes_anteriores, detalhes_novos, ip_acesso)
        VALUES (?, ?, ?, NOW(), ?, ?, ?)
    ");

    $stmt->execute([
        $id_servico,
        $acao,
        $_SESSION['user_id'] ?? 'system',
        $detalhes_anteriores ? json_encode($detalhes_anteriores) : null,
        $detalhes_novos ? json_encode($detalhes_novos) : null,
        $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
    ]);
}

function validarInstalacao($pdo, $id_eletronico, $ipen_interno, $cela_destino)
{
    $erros = [];

    // 1. Interno existe e está ativo?
    $stmt = $pdo->prepare("SELECT ipen, nome, status, ala, galeria, bloco FROM internos WHERE ipen = ? AND status = 'A'");
    $stmt->execute([$ipen_interno]);
    $interno = $stmt->fetch();

    if (!$interno) {
        $erros[] = "Interno não encontrado ou inativo";
        return $erros;
    }

    // 2. Cela existe e está válida?
    if (!preg_match('/^[A-Z]{1,2}-\d+$/', $cela_destino)) {
        $erros[] = "Formato de cela inválido (use: SE-3, A-1, BB-10)";
    }

    // 3. Eletrônico existe e está em estoque?
    $stmt = $pdo->prepare("SELECT id, id_interno, tipo_item, situacao FROM internos_eletronicos WHERE id = ? AND situacao = 'Estoque'");
    $stmt->execute([$id_eletronico]);
    $eletronico = $stmt->fetch();

    if (!$eletronico) {
        $erros[] = "Eletrônico não encontrado ou não está em estoque";
        return $erros;
    }

    // 4. Já tem chuveiro instalado na cela? (apenas para chuveiros)
    if ($eletronico['tipo_item'] === 'Chuveiro') {
        $stmt = $pdo->prepare("
            SELECT ie.id, ie.id_interno, i.nome
            FROM internos_eletronicos ie
            JOIN internos i ON ie.id_interno = i.ipen
            WHERE ie.tipo_item = 'Chuveiro'
            AND ie.situacao = 'Na Cela'
            AND i.ala = ?
            AND i.galeria = ?
        ");
        $stmt->execute([substr($cela_destino, 0, 1), substr($cela_destino, 2, 1)]);

        if ($stmt->fetch()) {
            $erros[] = "Já existe um chuveiro instalado nesta cela";
        }
    }

    return $erros;
}

// Lógica principal
$viewData = [
    'servicos' => [],
    'eletronicos_estoque' => [],
    'estatisticas' => [],
    'erro' => null,
    'sucesso' => null,
    'acesso_total' => $acesso_total,
    'acesso_consulta' => $acesso_consulta
];

// Processar actions AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    switch ($_POST['action']) {

        case 'buscar_eletronicos':
            if (!$acesso_consulta) {
                manutencao_json_response(['error' => 'Acesso negado']);
            }

            $termo = $_POST['termo'] ?? '';
            $tipo = $_POST['tipo'] ?? '';
            $situacao = $_POST['situacao'] ?? 'Estoque';
            $limit = min(50, max(5, (int)($_POST['limit'] ?? 10)));

            try {
                $sql = "
                    SELECT
                        ie.id,
                        ie.id_interno,
                        COALESCE(i.nome, i.nome_social, 'Sem Nome') as nome_interno,
                        ie.tipo_item,
                        ie.marca_modelo,
                        ie.cor,
                        ie.estado_conservacao,
                        ie.situacao,
                        ie.data_entrada,
                        ie.cela_atual,
                        i.ala,
                        i.galeria,
                        i.bloco,
                        CASE
                            WHEN i.ala IS NOT NULL AND i.galeria IS NOT NULL AND i.bloco IS NOT NULL
                            THEN CONCAT(i.ala, '-', i.galeria, '-', i.bloco)
                            ELSE 'Sem localização'
                        END as cela_formatada,
                        ie.id_dono,
                        COALESCE(d.nome, d.nome_social, 'Sem nome') as nome_dono
                    FROM internos_eletronicos ie
                    LEFT JOIN internos i ON ie.id_interno = i.ipen
                    LEFT JOIN internos d ON ie.id_dono = d.ipen
                    WHERE ie.situacao = ?
                ";

                $params = [$situacao];

                if (!empty($termo)) {
                    $sql .= " AND (
                        ie.tipo_item LIKE ? OR
                        ie.marca_modelo LIKE ? OR
                        i.nome LIKE ? OR
                        i.nome_social LIKE ? OR
                        i.ipen LIKE ?
                    )";
                    $termoBusca = "%{$termo}%";
                    $params = array_merge($params, [$termoBusca, $termoBusca, $termoBusca, $termoBusca, $termoBusca]);
                }

                if (!empty($tipo)) {
                    $sql .= " AND ie.tipo_item = ?";
                    $params[] = $tipo;
                }

                $sql .= " ORDER BY ie.data_entrada DESC LIMIT {$limit}";

                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $eletronicos = $stmt->fetchAll();

                manutencao_json_response(['success' => true, 'data' => $eletronicos]);
            } catch (Exception $e) {
                manutencao_json_response(['error' => 'Erro na busca: ' . $e->getMessage()]);
            }
            break;

        case 'buscar_interno':
            if (!$acesso_consulta) {
                manutencao_json_response(['error' => 'Acesso negado']);
            }

            $termo = $_POST['termo'] ?? '';
            $limit = min(20, max(5, (int)($_POST['limit'] ?? 10)));

            if (strlen($termo) < 2) {
                manutencao_json_response(['error' => 'Digite pelo menos 2 caracteres']);
            }

            try {
                $stmt = $pdo->prepare("
                    SELECT
                        i.ipen,
                        i.nome,
                        i.nome_social,
                        i.apelido,
                        i.ala,
                        i.galeria,
                        i.bloco,
                        CASE
                            WHEN i.ala IS NOT NULL AND i.galeria IS NOT NULL AND i.bloco IS NOT NULL
                            THEN CONCAT(i.ala, '-', i.galeria, '-', i.bloco)
                            ELSE 'Sem localização'
                        END as cela_formatada
                    FROM internos i
                    WHERE i.status = 'A'
                    AND (
                        i.ipen LIKE ? OR
                        i.nome LIKE ? OR
                        i.nome_social LIKE ? OR
                        i.apelido LIKE ?
                    )
                    ORDER BY
                        CASE
                            WHEN i.ipen LIKE ? THEN 1
                            WHEN i.nome LIKE ? THEN 2
                            WHEN i.nome_social LIKE ? THEN 3
                            WHEN i.apelido LIKE ? THEN 4
                            ELSE 5
                        END,
                        i.nome
                    LIMIT {$limit}
                ");

                $termoBusca = "%{$termo}%";
                $stmt->execute([
                    $termoBusca,
                    $termoBusca,
                    $termoBusca,
                    $termoBusca,  // Para WHERE
                    $termoBusca,
                    $termoBusca,
                    $termoBusca,
                    $termoBusca   // Para ORDER BY
                ]);
                $internos = $stmt->fetchAll();

                $resultados = array_map(function ($interno) {
                    $nomeExibicao = !empty($interno['nome_social']) ? $interno['nome_social'] : $interno['nome'];
                    if (!empty($interno['apelido'])) {
                        $nomeExibicao .= ' (' . $interno['apelido'] . ')';
                    }

                    return [
                        'ipen' => $interno['ipen'],
                        'nome' => $interno['nome'],
                        'nome_social' => $interno['nome_social'],
                        'apelido' => $interno['apelido'],
                        'cela_formatada' => $interno['cela_formatada'],
                        'texto' => $interno['ipen'] . ' - ' . $nomeExibicao,
                        'descricao' => $interno['cela_formatada']
                    ];
                }, $internos);

                manutencao_json_response(['success' => true, 'data' => $resultados]);
            } catch (Exception $e) {
                manutencao_json_response(['error' => 'Erro na busca: ' . $e->getMessage()]);
            }
            break;

        case 'salvar_servico':
            if (!$acesso_total) {
                manutencao_json_response(['error' => 'Acesso negado']);
            }

            $id_eletronico = (int)($_POST['id_eletronico'] ?? 0);
            $tipo_servico = $_POST['tipo_servico'] ?? '';
            $cela_destino = $_POST['cela_destino'] ?? '';
            $ipen_interno = (int)($_POST['ipen_interno'] ?? 0);
            $observacoes = $_POST['observacoes'] ?? '';

            // Validações básicas
            if ($id_eletronico <= 0) {
                manutencao_json_response(['error' => 'Eletrônico inválido']);
            }

            if (empty($tipo_servico)) {
                manutencao_json_response(['error' => 'Tipo de serviço é obrigatório']);
            }

            if (empty($cela_destino)) {
                manutencao_json_response(['error' => 'Cela de destino é obrigatória']);
            }

            if ($ipen_interno <= 0) {
                manutencao_json_response(['error' => 'Interno inválido']);
            }

            // Validar instalação
            $erros = validarInstalacao($pdo, $id_eletronico, $ipen_interno, $cela_destino);
            if (!empty($erros)) {
                manutencao_json_response(['error' => implode('<br>', $erros)]);
            }

            try {
                $pdo->beginTransaction();

                // Inserir serviço
                $stmt = $pdo->prepare("
                    INSERT INTO manutencao_servicos
                    (id_eletronico, tipo_servico, cela_destino, usuario_solicitante, observacoes)
                    VALUES (?, ?, ?, ?, ?)
                ");

                $stmt->execute([
                    $id_eletronico,
                    $tipo_servico,
                    $cela_destino,
                    $_SESSION['user_id'] ?? 'system',
                    $observacoes
                ]);

                $id_servico = $pdo->lastInsertId();

                // Se for instalação, atualizar eletrônico
                if ($tipo_servico === 'INSTALACAO') {
                    $stmt = $pdo->prepare("
                        UPDATE internos_eletronicos
                        SET situacao = 'Na Cela',
                            cela_atual = ?,
                            data_entrega_interno = NOW(),
                            data_retirada = NULL
                        WHERE id = ?
                    ");
                    $stmt->execute([$cela_destino, $id_eletronico]);
                }

                // Registrar auditoria
                registrarAuditoria($pdo, $id_servico, 'CRIADO', null, [
                    'id_eletronico' => $id_eletronico,
                    'tipo_servico' => $tipo_servico,
                    'cela_destino' => $cela_destino,
                    'ipen_interno' => $ipen_interno,
                    'observacoes' => $observacoes
                ]);

                $pdo->commit();

                manutencao_json_response([
                    'success' => true,
                    'message' => 'Serviço cadastrado com sucesso',
                    'id' => $id_servico
                ]);
            } catch (Exception $e) {
                $pdo->rollBack();
                manutencao_json_response(['error' => 'Erro ao salvar: ' . $e->getMessage()]);
            }
            break;

        case 'executar_servico':
            if (!$acesso_total) {
                manutencao_json_response(['error' => 'Acesso negado']);
            }

            $id_servico = (int)($_POST['id_servico'] ?? 0);

            if ($id_servico <= 0) {
                manutencao_json_response(['error' => 'Serviço inválido']);
            }

            try {
                $pdo->beginTransaction();

                // Buscar serviço
                $stmt = $pdo->prepare("SELECT * FROM manutencao_servicos WHERE id = ?");
                $stmt->execute([$id_servico]);
                $servico = $stmt->fetch();

                if (!$servico) {
                    throw new Exception('Serviço não encontrado');
                }

                if ($servico['status'] !== 'PENDENTE') {
                    throw new Exception('Serviço já foi executado ou cancelado');
                }

                // Atualizar serviço
                $stmt = $pdo->prepare("
                    UPDATE manutencao_servicos
                    SET status = 'EXECUTADO',
                        data_execucao = NOW(),
                        usuario_executante = ?
                    WHERE id = ?
                ");
                $stmt->execute([$_SESSION['user_id'] ?? 'system', $id_servico]);

                // Se for instalação, atualizar eletrônico
                if ($servico['tipo_servico'] === 'INSTALACAO') {
                    $stmt = $pdo->prepare("
                        UPDATE internos_eletronicos
                        SET situacao = 'Na Cela',
                            cela_atual = ?,
                            data_entrega_interno = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([$servico['cela_destino'], $servico['id_eletronico']]);
                }

                // Registrar auditoria
                registrarAuditoria($pdo, $id_servico, 'EXECUTADO', $servico, [
                    'status' => 'EXECUTADO',
                    'data_execucao' => date('Y-m-d H:i:s'),
                    'usuario_executante' => $_SESSION['user_id'] ?? 'system'
                ]);

                $pdo->commit();

                manutencao_json_response([
                    'success' => true,
                    'message' => 'Serviço executado com sucesso'
                ]);
            } catch (Exception $e) {
                $pdo->rollBack();
                manutencao_json_response(['error' => 'Erro ao executar: ' . $e->getMessage()]);
            }
            break;

        case 'cancelar_servico':
            if (!$acesso_total) {
                manutencao_json_response(['error' => 'Acesso negado']);
            }

            $id_servico = (int)($_POST['id_servico'] ?? 0);

            if ($id_servico <= 0) {
                manutencao_json_response(['error' => 'Serviço inválido']);
            }

            try {
                $pdo->beginTransaction();

                // Buscar serviço
                $stmt = $pdo->prepare("SELECT * FROM manutencao_servicos WHERE id = ?");
                $stmt->execute([$id_servico]);
                $servico = $stmt->fetch();

                if (!$servico) {
                    throw new Exception('Serviço não encontrado');
                }

                if ($servico['status'] !== 'PENDENTE') {
                    throw new Exception('Apenas serviços pendentes podem ser cancelados');
                }

                // Se for instalação, retornar eletrônico para estoque
                if ($servico['tipo_servico'] === 'INSTALACAO') {
                    $stmt = $pdo->prepare("
                        UPDATE internos_eletronicos
                        SET situacao = 'Estoque',
                            cela_atual = NULL,
                            data_entrega_interno = NULL
                        WHERE id = ?
                    ");
                    $stmt->execute([$servico['id_eletronico']]);
                }

                // Atualizar serviço
                $stmt = $pdo->prepare("
                    UPDATE manutencao_servicos
                    SET status = 'CANCELADO'
                    WHERE id = ?
                ");
                $stmt->execute([$id_servico]);

                // Registrar auditoria
                registrarAuditoria($pdo, $id_servico, 'CANCELADO', $servico, [
                    'status' => 'CANCELADO'
                ]);

                $pdo->commit();

                manutencao_json_response([
                    'success' => true,
                    'message' => 'Serviço cancelado com sucesso'
                ]);
            } catch (Exception $e) {
                $pdo->rollBack();
                manutencao_json_response(['error' => 'Erro ao cancelar: ' . $e->getMessage()]);
            }
            break;

        case 'listar_servicos':
            if (!$acesso_consulta) {
                manutencao_json_response(['error' => 'Acesso negado']);
            }

            $situacao = $_POST['situacao'] ?? '';
            $tipo_servico = $_POST['tipo_servico'] ?? '';
            $data_inicio = $_POST['data_inicio'] ?? '';
            $data_fim = $_POST['data_fim'] ?? '';

            try {
                $sql = "
                    SELECT
                        ms.id,
                        ms.id_eletronico,
                        ms.tipo_servico,
                        ms.cela_destino,
                        ms.data_solicitacao,
                        ms.data_execucao,
                        ms.status,
                        ms.usuario_solicitante,
                        ms.usuario_executante,
                        ms.observacoes,
                        ms.created_at,
                        ie.tipo_item,
                        ie.marca_modelo,
                        ie.cor as cor_eletronico,
                        COALESCE(i.nome, i.nome_social, 'Sem Nome') as nome_interno,
                        i.ala,
                        i.galeria,
                        i.bloco,
                        COALESCE(d.nome, d.nome_social, 'Sem nome') as nome_dono
                    FROM manutencao_servicos ms
                    LEFT JOIN internos_eletronicos ie ON ms.id_eletronico = ie.id
                    LEFT JOIN internos i ON ie.id_interno = i.ipen
                    LEFT JOIN internos d ON ie.id_dono = d.ipen
                    WHERE 1=1
                ";

                $params = [];

                if (!empty($situacao)) {
                    $sql .= " AND ms.status = ?";
                    $params[] = $situacao;
                }

                if (!empty($tipo_servico)) {
                    $sql .= " AND ms.tipo_servico = ?";
                    $params[] = $tipo_servico;
                }

                if (!empty($data_inicio)) {
                    $sql .= " AND DATE(ms.data_solicitacao) >= ?";
                    $params[] = $data_inicio;
                }

                if (!empty($data_fim)) {
                    $sql .= " AND DATE(ms.data_solicitacao) <= ?";
                    $params[] = $data_fim;
                }

                $sql .= " ORDER BY ms.data_solicitacao DESC";

                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $servicos = $stmt->fetchAll();

                // Formatar dados
                $servicos = array_map(function ($servico) {
                    $servico['data_solicitacao_fmt'] = date('d/m/Y H:i', strtotime($servico['data_solicitacao']));
                    $servico['data_execucao_fmt'] = $servico['data_execucao'] ? date('d/m/Y H:i', strtotime($servico['data_execucao'])) : null;
                    $servico['created_at_fmt'] = date('d/m/Y H:i', strtotime($servico['created_at']));
                    return $servico;
                }, $servicos);

                manutencao_json_response(['success' => true, 'data' => $servicos]);
            } catch (Exception $e) {
                manutencao_json_response(['error' => 'Erro ao listar: ' . $e->getMessage()]);
            }
            break;

        default:
            manutencao_json_response(['error' => 'Action inválida']);
    }

    exit;
}

// Carregar dados para a view (GET)
try {
    // Carregar estatísticas
    $stmt = $pdo->prepare("
        SELECT
            COUNT(CASE WHEN status = 'PENDENTE' THEN 1 END) as pendentes,
            COUNT(CASE WHEN status = 'EXECUTADO' THEN 1 END) as executados,
            COUNT(CASE WHEN status = 'CANCELADO' THEN 1 END) as cancelados,
            COUNT(CASE WHEN DATE(data_solicitacao) = CURDATE() THEN 1 END) as hoje,
            COUNT(CASE WHEN tipo_servico = 'INSTALACAO' AND status = 'EXECUTADO' THEN 1 END) as instalacoes
        FROM manutencao_servicos
    ");
    $stmt->execute();
    $viewData['estatisticas'] = $stmt->fetch();

    // Carregar eletrônicos em estoque
    $stmt = $pdo->prepare("
        SELECT
            ie.id,
            ie.tipo_item,
            ie.marca_modelo,
            ie.cor,
            ie.data_entrada,
            COALESCE(i.nome, i.nome_social, 'Sem Nome') as nome_interno,
            COALESCE(d.nome, d.nome_social, 'Sem nome') as nome_dono
        FROM internos_eletronicos ie
        LEFT JOIN internos i ON ie.id_interno = i.ipen
        LEFT JOIN internos d ON ie.id_dono = d.ipen
        WHERE ie.situacao = 'Estoque' AND ie.origem = 'Manutenção'
        ORDER BY ie.data_entrada DESC
        LIMIT 20
    ");
    $stmt->execute();
    $viewData['eletronicos_estoque'] = $stmt->fetchAll();

    // Carregar serviços recentes
    $stmt = $pdo->prepare("
        SELECT
            ms.id,
            ms.tipo_servico,
            ms.cela_destino,
            ms.data_solicitacao,
            ms.status,
            ie.tipo_item,
            ie.marca_modelo,
            COALESCE(i.nome, i.nome_social, 'Sem Nome') as nome_interno
        FROM manutencao_servicos ms
        LEFT JOIN internos_eletronicos ie ON ms.id_eletronico = ie.id
        LEFT JOIN internos i ON ie.id_interno = i.ipen
        ORDER BY ms.data_solicitacao DESC
        LIMIT 10
    ");
    $stmt->execute();
    $viewData['servicos'] = $stmt->fetchAll();
} catch (Exception $e) {
    $viewData['erro'] = 'Erro ao carregar dados: ' . $e->getMessage();
}
