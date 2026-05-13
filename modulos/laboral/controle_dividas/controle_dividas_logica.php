<?php
// SIGEP Controle de Dívidas - Controller
// Gestão de Dívidas de Internos

session_start();
require_once __DIR__ . '/../../../conf/db.php';

// Headers para resposta JSON
header('Content-Type: application/json; charset=utf-8');

// Tratamento de erros
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Configurar Timezone para São Paulo
date_default_timezone_set('America/Sao_Paulo');

// Função para retornar erro JSON
function returnError($message, $code = 500)
{
    http_response_code($code);
    echo json_encode(['error' => $message, 'code' => $code], JSON_UNESCAPED_UNICODE);
    exit;
}

// Verificar se usuário está autenticado
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_nome'])) {
    returnError('Usuário não autenticado', 401);
}

// Verificar permissão específica
if (!($_SESSION['user_admin'] || ($_SESSION['perm_laboral'] ?? 0))) {
    returnError('Sem permissão para acessar este módulo', 403);
}

// Configurar conexão PDO
try {
    $config = require __DIR__ . '/../../../conf/db.php';
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['user'], $config['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $pdo->exec("SET time_zone = '-03:00'");
} catch (PDOException $e) {
    returnError('Erro na conexão com banco de dados: ' . $e->getMessage(), 500);
}

// Funções CRUD
function buscarDividas($pdo, $filtros = [])
{
    try {
        $sql = "SELECT
                    ld.*,
                    i.nome as interno_nome,
                    i.nome_social as interno_nome_social,
                    i.status as interno_status,
                    i.situacao as interno_trabalho,
                    COALESCE(SUM(ldd.valor_desconto), 0) as total_descontado,
                    COALESCE(COUNT(ldd.id), 0) as quantidade_lancamentos
                FROM laboral_controle_dividas ld
                LEFT JOIN internos i ON ld.ipen = i.ipen
                LEFT JOIN laboral_controle_dividas_descontos ldd ON ldd.multa_id = ld.id
                WHERE 1=1";

        $params = [];

        // Filtros
        if (!empty($filtros['busca'])) {
            $sql .= " AND (i.nome LIKE ? OR i.nome_social LIKE ? OR ld.ipen LIKE ? OR ld.autos LIKE ?)";
            $busca = '%' . $filtros['busca'] . '%';
            $params = array_merge($params, [$busca, $busca, $busca, $busca]);
        }

        if (!empty($filtros['status_detalhado'])) {
            $sql .= " AND ld.status_detalhado = ?";
            $params[] = $filtros['status_detalhado'];
        }

        if (!empty($filtros['status'])) {
            $sql .= " AND ld.status = ?";
            $params[] = $filtros['status'];
        }

        if (!empty($filtros['tipo'])) {
            $sql .= " AND ld.tipo_divida = ?";
            $params[] = $filtros['tipo'];
        }

        if (empty($filtros['mostrar_inativos'])) {
            $sql .= " AND ld.status = 'A'";
        }

        $sql .= " GROUP BY ld.id, ld.ipen, ld.cpf, ld.autos, ld.tipo_divida, ld.valor_divida, ld.salario_base, ld.valor_atual, ld.percentual_desconto, ld.status, ld.data_cadastro, ld.data_alterado, ld.usuario_cadastro, ld.usuario_alterado, ld.data_ultima_atualizacao, ld.status_detalhado, ld.data_quitacao, ld.pensao_favorecido, ld.pensao_banco, ld.pensao_agencia, ld.pensao_conta, ld.pensao_op, ld.pensao_tipo_conta, ld.pensao_determinacao, i.nome, i.nome_social, i.status, i.situacao
                ORDER BY ld.data_cadastro DESC";

        // Paginação
        if (!empty($filtros['limit'])) {
            $sql .= " LIMIT ?";
            $params[] = (int)$filtros['limit'];

            if (!empty($filtros['offset'])) {
                $sql .= " OFFSET ?";
                $params[] = (int)$filtros['offset'];
            }
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        throw new Exception('Erro ao buscar dívidas: ' . $e->getMessage());
    }
}

function contarDividas($pdo, $filtros = [])
{
    try {
        $sql = "SELECT COUNT(*) as total
                FROM laboral_controle_dividas ld
                LEFT JOIN internos i ON ld.ipen = i.ipen
                WHERE 1=1";

        $params = [];

        // Mesmos filtros da busca
        if (!empty($filtros['busca'])) {
            $sql .= " AND (i.nome LIKE ? OR i.nome_social LIKE ? OR ld.ipen LIKE ? OR ld.autos LIKE ?)";
            $busca = '%' . $filtros['busca'] . '%';
            $params = array_merge($params, [$busca, $busca, $busca, $busca]);
        }

        if (!empty($filtros['status_detalhado'])) {
            $sql .= " AND ld.status_detalhado = ?";
            $params[] = $filtros['status_detalhado'];
        }

        if (!empty($filtros['status'])) {
            $sql .= " AND ld.status = ?";
            $params[] = $filtros['status'];
        }

        if (!empty($filtros['tipo'])) {
            $sql .= " AND ld.tipo_divida = ?";
            $params[] = $filtros['tipo'];
        }

        if (empty($filtros['mostrar_inativos'])) {
            $sql .= " AND ld.status = 'A'";
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch()['total'];
    } catch (PDOException $e) {
        throw new Exception('Erro ao contar dívidas: ' . $e->getMessage());
    }
}

function salvarDivida($pdo, $dados)
{
    try {
        // Para Pensão, valor_divida pode ser null
        $valorDivida = ($dados['tipo_divida'] === 'Pensão') ? null : $dados['valor_divida'];

        if (!empty($dados['id'])) {
            // Atualizar
            $sql = "UPDATE laboral_controle_dividas SET
                        ipen = ?, cpf = ?, autos = ?, tipo_divida = ?,
                        valor_divida = ?, salario_base = ?, valor_atual = ?, percentual_desconto = ?,
                        status_detalhado = ?, usuario_alterado = ?, data_alterado = NOW(),
                        pensao_favorecido = ?, pensao_banco = ?, pensao_agencia = ?,
                        pensao_conta = ?, pensao_op = ?, pensao_tipo_conta = ?,
                        pensao_determinacao = ?
                    WHERE id = ?";
            $params = [
                $dados['ipen'],
                $dados['cpf'],
                $dados['autos'],
                $dados['tipo_divida'],
                $valorDivida,
                $valorDivida, // salario_base
                $valorDivida, // valor_atual
                $dados['percentual_desconto'],
                $dados['status_detalhado'],
                $_SESSION['user_id'], // usuario_alterado
                $dados['pensao_favorecido'],
                $dados['pensao_banco'],
                $dados['pensao_agencia'],
                $dados['pensao_conta'],
                $dados['pensao_op'],
                $dados['pensao_tipo_conta'],
                $dados['pensao_determinacao'],
                $dados['id']
            ];
        } else {
            // Inserir
            $sql = "INSERT INTO laboral_controle_dividas (
                        ipen, cpf, autos, tipo_divida, valor_divida, salario_base, valor_atual, percentual_desconto,
                        status, status_detalhado, usuario_cadastro,
                        pensao_favorecido, pensao_banco, pensao_agencia,
                        pensao_conta, pensao_op, pensao_tipo_conta, pensao_determinacao,
                        data_cadastro
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $params = [
                $dados['ipen'],
                $dados['cpf'],
                $dados['autos'],
                $dados['tipo_divida'],
                $valorDivida,
                $valorDivida, // salario_base = valor_divida (ou null para Pensão)
                $valorDivida, // valor_atual = valor_divida (ou null para Pensão)
                $dados['percentual_desconto'],
                'A', // status = Ativo
                $dados['status_detalhado'] ?: 'Pendente',
                $_SESSION['user_id'], // usuario_cadastro obrigatório
                $dados['pensao_favorecido'],
                $dados['pensao_banco'],
                $dados['pensao_agencia'],
                $dados['pensao_conta'],
                $dados['pensao_op'],
                $dados['pensao_tipo_conta'],
                $dados['pensao_determinacao']
            ];
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $dividaId = !empty($dados['id']) ? $dados['id'] : $pdo->lastInsertId();

        // Registrar auditoria
        if (!empty($dados['id'])) {
            // Atualização
            $dadosAntes = buscarDividaPorId($pdo, $dados['id']);
            $dadosDepois = array_merge($dados, ['id' => $dados['id']]);
            registrarAuditoria(
                $pdo,
                $dados['id'],
                $dados['ipen'],
                $dados['tipo_divida'],
                'UPDATE',
                'laboral_controle_dividas',
                $dados['id'],
                $dadosAntes,
                $dadosDepois,
                "Dívida atualizada: {$dados['tipo_divida']} - IPEN: {$dados['ipen']}",
                $_SESSION['user_id']
            );
        } else {
            // Inserção
            $dadosDepois = array_merge($dados, ['id' => $dividaId]);
            registrarAuditoria(
                $pdo,
                $dividaId,
                $dados['ipen'],
                $dados['tipo_divida'],
                'INSERT',
                'laboral_controle_dividas',
                $dividaId,
                null,
                $dadosDepois,
                "Nova dívida cadastrada: {$dados['tipo_divida']} - IPEN: {$dados['ipen']}",
                $_SESSION['user_id']
            );
        }

        return $dividaId;
    } catch (PDOException $e) {
        throw new Exception('Erro ao salvar dívida: ' . $e->getMessage());
    }
}

function buscarDividaPorId($pdo, $id)
{
    try {
        $stmt = $pdo->prepare("SELECT * FROM laboral_controle_dividas WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        throw new Exception('Erro ao buscar dívida: ' . $e->getMessage());
    }
}

function excluirDivida($pdo, $id)
{
    try {
        $stmt = $pdo->prepare("DELETE FROM laboral_controle_dividas WHERE id = ?");
        $stmt->execute([$id]);
        return true;
    } catch (PDOException $e) {
        throw new Exception('Erro ao excluir dívida: ' . $e->getMessage());
    }
}

function buscarInternoPorTermo($pdo, $termo)
{
    try {
        $stmt = $pdo->prepare("SELECT ipen, nome, nome_social, cpf, status, situacao
                               FROM internos
                               WHERE (nome LIKE ? OR nome_social LIKE ? OR ipen LIKE ?)
                               LIMIT 5");
        $busca = '%' . $termo . '%';
        $stmt->execute([$busca, $busca, $busca]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        throw new Exception('Erro ao buscar interno: ' . $e->getMessage());
    }
}

function buscarStats($pdo)
{
    try {
        $stats = [];

        // Total de dívidas ativas
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM laboral_controle_dividas WHERE status = 'A'");
        $stmt->execute();
        $stats['total_ativas'] = $stmt->fetch()['total'];

        // Total arrecadado no mês
        $stmt = $pdo->prepare("SELECT SUM(valor_desconto) as total
                               FROM laboral_controle_dividas_descontos
                               WHERE mes_referencia = DATE_FORMAT(NOW(), '%Y-%m')");
        $stmt->execute();
        $stats['arrecadado_mes'] = $stmt->fetch()['total'] ?: 0;

        // Dívidas pendentes
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM laboral_controle_dividas WHERE status_detalhado = 'Pendente'");
        $stmt->execute();
        $stats['pendentes'] = $stmt->fetch()['total'];

        // Internos inadimplentes
        $stmt = $pdo->prepare("SELECT COUNT(DISTINCT ipen) as total
                               FROM laboral_controle_dividas
                               WHERE status = 'A' AND status_detalhado NOT IN ('Quitada', 'Inativa')");
        $stmt->execute();
        $stats['inadimplentes'] = $stmt->fetch()['total'];

        return $stats;
    } catch (PDOException $e) {
        throw new Exception('Erro ao buscar estatísticas: ' . $e->getMessage());
    }
}

function buscarDetalhesKPI($pdo, $tipo)
{
    try {
        switch ($tipo) {
            case 'arrecadado_mes':
                $sql = "SELECT ld.id, ld.ipen, ld.valor_divida, ld.valor_atual, ld.status_detalhado,
                            ld.tipo_divida, ld.percentual_desconto, ldl.valor_desconto,
                            ldl.salario_real, ldl.mes_referencia, ldl.data_lancamento,
                            u.nome as usuario_lancamento
                        FROM laboral_controle_dividas_descontos ldl
                        JOIN laboral_controle_dividas ld ON ldl.multa_id = ld.id
                        LEFT JOIN internos i ON ld.ipen = i.ipen
                        LEFT JOIN users u ON ldl.usuario_lancamento = u.id
                        WHERE DATE_FORMAT(ldl.mes_referencia, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')
                        ORDER BY ldl.data_lancamento DESC";
                break;

            case 'total_ativas':
                $sql = "SELECT
                            ld.ipen, i.nome as interno_nome, i.nome_social as interno_nome_social,
                            ld.tipo_divida, ld.valor_divida, ld.valor_atual, ld.percentual_desconto,
                            ld.status_detalhado, ld.data_cadastro
                        FROM laboral_controle_dividas ld
                        LEFT JOIN internos i ON ld.ipen = i.ipen
                        WHERE ld.status = 'A'
                        ORDER BY ld.data_cadastro DESC";
                break;

            case 'pendentes':
                $sql = "SELECT
                            ld.ipen, i.nome as interno_nome, i.nome_social as interno_nome_social,
                            ld.tipo_divida, ld.valor_divida, ld.valor_atual, ld.percentual_desconto,
                            ld.status_detalhado, i.status as interno_status
                        FROM laboral_controle_dividas ld
                        LEFT JOIN internos i ON ld.ipen = i.ipen
                        WHERE ld.status_detalhado = 'Pendente'
                        ORDER BY ld.data_cadastro DESC";
                break;

            case 'inadimplentes':
                $sql = "SELECT
                            ld.ipen, i.nome as interno_nome, i.nome_social as interno_nome_social,
                            COUNT(*) as qtd_dividas, SUM(ld.valor_atual) as total_devido,
                            i.status as interno_status
                        FROM laboral_controle_dividas ld
                        LEFT JOIN internos i ON ld.ipen = i.ipen
                        WHERE ld.status = 'A' AND ld.status_detalhado NOT IN ('Quitada', 'Inativa')
                        GROUP BY ld.ipen, i.nome, i.nome_social, i.status
                        ORDER BY total_devido DESC";
                break;

            default:
                throw new Exception('Tipo de KPI inválido');
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return ['tipo' => $tipo, 'dados' => $stmt->fetchAll()];
    } catch (PDOException $e) {
        throw new Exception('Erro ao buscar detalhes KPI: ' . $e->getMessage());
    }
}

function lancarSalario($pdo, $dados)
{
    try {
        $sql = "INSERT INTO laboral_controle_dividas_descontos (
                    multa_id, ipen, mes_referencia, salario_real, percentual_desconto, valor_desconto,
                    saldo_anterior, saldo_novo, status, usuario_lancamento, data_lancamento
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $params = [
            $dados['divida_id'],
            $dados['ipen'],
            $dados['mes_referencia'],
            $dados['salario_real'],
            $dados['percentual_desconto'],
            $dados['valor_desconto'],
            $dados['saldo_anterior'],
            $dados['saldo_novo'],
            'Processado', // status padrão
            $_SESSION['user_id'] ?? 0 // fallback para 0 se nulo
        ];

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $lancamentoId = $pdo->lastInsertId();

        // Buscar dados da dívida para auditoria
        $divida = buscarDividaPorId($pdo, $dados['divida_id']);

        // Registrar auditoria do lançamento
        $dadosDepois = array_merge($dados, [
            'id' => $lancamentoId,
            'data_lancamento' => date('Y-m-d H:i:s')
        ]);
        registrarAuditoria(
            $pdo,
            $dados['divida_id'],
            $dados['ipen'],
            $divida['tipo_divida'],
            'LANCAMENTO',
            'laboral_controle_dividas_descontos',
            $lancamentoId,
            null,
            $dadosDepois,
            "Lançamento de desconto: R$ {$dados['valor_desconto']} - Mês: {$dados['mes_referencia']} - IPEN: {$dados['ipen']}",
            $_SESSION['user_id']
        );

        // Atualizar valor atual da dívida
        $updateSql = "UPDATE laboral_controle_dividas SET valor_atual = ? WHERE id = ?";
        $updateStmt = $pdo->prepare($updateSql);
        $updateStmt->execute([$dados['saldo_novo'], $dados['divida_id']]);

        // Atualizar status baseado no mês vigente
        atualizarStatusPorMesVigente($pdo);

        return $lancamentoId;
    } catch (PDOException $e) {
        throw new Exception('Erro ao lançar salário: ' . $e->getMessage());
    }
}

function buscarHistoricoDivida($pdo, $dividaId)
{
    try {
        $sql = "SELECT ldl.*, u.nome as usuario_nome
                FROM laboral_controle_dividas_descontos ldl
                LEFT JOIN users u ON ldl.usuario_lancamento = u.id
                WHERE ldl.multa_id = ?
                ORDER BY ldl.mes_referencia DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$dividaId]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        throw new Exception('Erro ao buscar histórico: ' . $e->getMessage());
    }
}

function buscarProjecaoDivida($pdo, $dividaId)
{
    try {
        // Buscar dados da dívida
        $sql = "SELECT ld.*,
                       (SELECT SUM(valor_desconto) FROM laboral_controle_dividas_descontos WHERE multa_id = ld.id) as total_descontado,
                       (SELECT COUNT(*) FROM laboral_controle_dividas_descontos WHERE multa_id = ld.id) as quantidade_lancamentos,
                       (SELECT AVG(valor_desconto) FROM laboral_controle_dividas_descontos WHERE multa_id = ld.id) as media_desconto
                FROM laboral_controle_dividas ld
                WHERE ld.id = ?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$dividaId]);
        $divida = $stmt->fetch();

        if (!$divida) {
            return ['status' => 'erro', 'message' => 'Dívida não encontrada'];
        }

        // Se já estiver quitada ou inativa
        if ($divida['status_detalhado'] === 'Quitada') {
            return [
                'status' => 'quitada',
                'total_descontado' => $divida['total_descontado'],
                'message' => 'Dívida já quitada'
            ];
        }

        // Se não tiver lançamentos suficientes
        if ($divida['quantidade_lancamentos'] < 1) {
            return [
                'status' => 'insuficiente',
                'message' => 'Sem lançamentos suficientes para calcular projeção'
            ];
        }

        // Calcular projeção
        $totalDescontado = floatval($divida['total_descontado'] ?? 0);
        $mediaDesconto = floatval($divida['media_desconto'] ?? 0);
        $valorDivida = floatval($divida['valor_divida'] ?? 0);
        $saldoRestante = $valorDivida - $totalDescontado;

        // Para dívidas do tipo Pensão, não há previsão de término
        if ($divida['tipo_divida'] === 'Pensão') {
            return [
                'status' => 'calculado',
                'tipo' => 'Pensão',
                'total_descontado' => $totalDescontado,
                'media_desconto' => $mediaDesconto,
                'quantidade_lancamentos' => $divida['quantidade_lancamentos'],
                'projecao_meses' => null,
                'data_estimada' => null,
                'message' => 'Pensão mensal sem previsão de término'
            ];
        }

        // Para outras dívidas, calcular previsão de quitação
        if ($mediaDesconto <= 0) {
            return [
                'status' => 'insuficiente',
                'message' => 'Média de descontos insuficiente para calcular'
            ];
        }

        $projecaoMeses = ceil($saldoRestante / $mediaDesconto);

        // Calcular data estimada
        $dataEstimada = date('Y-m-d', strtotime("+$projecaoMeses months"));
        $dataEstimadaFormatada = date('d/m/Y', strtotime("+$projecaoMeses months"));

        return [
            'status' => 'calculado',
            'tipo' => $divida['tipo_divida'],
            'total_descontado' => $totalDescontado,
            'media_desconto' => $mediaDesconto,
            'saldo_restante' => $saldoRestante,
            'quantidade_lancamentos' => $divida['quantidade_lancamentos'],
            'projecao_meses' => $projecaoMeses,
            'data_estimada' => $dataEstimadaFormatada,
            'data_estimada_iso' => $dataEstimada
        ];
    } catch (PDOException $e) {
        throw new Exception('Erro ao buscar projeção: ' . $e->getMessage());
    }
}

function buscarEstatisticasParcelas($pdo, $dividaId)
{
    try {
        // Buscar dados da dívida
        $stmt = $pdo->prepare("SELECT * FROM laboral_controle_dividas WHERE id = ?");
        $stmt->execute([$dividaId]);
        $divida = $stmt->fetch();

        if (!$divida) {
            return ['status' => 'erro', 'message' => 'Dívida não encontrada'];
        }

        // Se não for pensão, retornar estatísticas básicas
        if ($divida['tipo_divida'] !== 'Pensão') {
            $stmt = $pdo->prepare("SELECT
                        COUNT(*) as total_lancamentos,
                        SUM(valor_desconto) as total_descontado,
                        MIN(mes_referencia) as primeiro_mes,
                        MAX(mes_referencia) as ultimo_mes
                    FROM laboral_controle_dividas_descontos
                    WHERE multa_id = ?");
            $stmt->execute([$dividaId]);
            $estatisticas = $stmt->fetch();

            return [
                'status' => 'sucesso',
                'tipo_divida' => $divida['tipo_divida'],
                'total_lancamentos' => (int)$estatisticas['total_lancamentos'],
                'total_descontado' => floatval($estatisticas['total_descontado'] ?? 0),
                'primeiro_mes' => $estatisticas['primeiro_mes'],
                'ultimo_mes' => $estatisticas['ultimo_mes'],
                'parcelas_pagas' => (int)$estatisticas['total_lancamentos'],
                'parcelas_nao_pagas' => 0,
                'meses_pulados' => [],
                'valor_total_pago' => floatval($estatisticas['total_descontado'] ?? 0)
            ];
        }

        // Para pensão, calcular estatísticas detalhadas
        $dataInicio = new DateTime($divida['data_cadastro']);
        $dataAtual = new DateTime();

        // Calcular o período esperado (meses desde o cadastro até hoje)
        $periodoEsperado = $dataInicio->diff($dataAtual);
        $totalMesesEsperado = $periodoEsperado->y * 12 + $periodoEsperado->m;

        // Buscar todos os lançamentos
        $stmt = $pdo->prepare("SELECT mes_referencia, valor_desconto, data_lancamento
                               FROM laboral_controle_dividas_descontos
                               WHERE multa_id = ?
                               ORDER BY mes_referencia ASC");
        $stmt->execute([$dividaId]);
        $lancamentos = $stmt->fetchAll();

        // Gerar lista de todos os meses esperados
        $mesesEsperados = [];
        $dataTemp = clone $dataInicio;
        while ($dataTemp <= $dataAtual) {
            $mesesEsperados[] = $dataTemp->format('Y-m');
            $dataTemp->modify('+1 month');
        }

        // Identificar meses com e sem lançamento
        $mesesComLancamento = array_column($lancamentos, 'mes_referencia');
        $mesesPulados = array_diff($mesesEsperados, $mesesComLancamento);

        // Estatísticas básicas
        $totalLancamentos = count($lancamentos);
        $totalDescontado = array_sum(array_column($lancamentos, 'valor_desconto'));
        $parcelasPagas = $totalLancamentos;
        $parcelasNaoPagas = count($mesesPulados);

        return [
            'status' => 'sucesso',
            'tipo_divida' => 'Pensão',
            'total_lancamentos' => $totalLancamentos,
            'total_descontado' => $totalDescontado,
            'primeiro_mes' => $totalLancamentos > 0 ? $lancamentos[0]['mes_referencia'] : null,
            'ultimo_mes' => $totalLancamentos > 0 ? end($lancamentos)['mes_referencia'] : null,
            'parcelas_pagas' => $parcelasPagas,
            'parcelas_nao_pagas' => $parcelasNaoPagas,
            'meses_pulados' => $mesesPulados,
            'total_meses_esperados' => $totalMesesEsperado,
            'percentual_adimplencia' => $totalMesesEsperado > 0 ? round(($parcelasPagas / $totalMesesEsperado) * 100, 2) : 0,
            'valor_total_pago' => $totalDescontado,
            'valor_medio_parcela' => $parcelasPagas > 0 ? round($totalDescontado / $parcelasPagas, 2) : 0
        ];
    } catch (PDOException $e) {
        throw new Exception('Erro ao buscar estatísticas de parcelas: ' . $e->getMessage());
    }
}

// Função para registrar auditoria
function registrarAuditoria($pdo, $multaId, $ipen, $tipoDivida, $tipoAcao, $tabelaOrigem, $registroOrigemId, $dadosAntes, $dadosDepois, $descricao, $usuarioId)
{
    try {
        $stmt = $pdo->prepare("CALL sp_registrar_auditoria_divida(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $multaId,
            $ipen,
            $tipoDivida,
            $tipoAcao,
            $tabelaOrigem,
            $registroOrigemId,
            $dadosAntes ? json_encode($dadosAntes) : null,
            $dadosDepois ? json_encode($dadosDepois) : null,
            $descricao,
            $usuarioId
        ]);
    } catch (PDOException $e) {
        // Não falhar a operação principal se a auditoria falhar
        error_log("Erro ao registrar auditoria: " . $e->getMessage());
    }
}

// Atualizar status das dívidas baseado no mês vigente
function atualizarStatusPorMesVigente($pdo)
{
    try {
        $mesAtual = date('Y-m');

        // Marcar como "Ativa" as dívidas que têm lançamento no mês atual
        $sql1 = "UPDATE laboral_controle_dividas ld
                 SET ld.status_detalhado = 'Ativa'
                 WHERE ld.status = 'A'
                 AND EXISTS (
                     SELECT 1 FROM laboral_controle_dividas_descontos lmd
                     WHERE lmd.multa_id = ld.id
                     AND lmd.mes_referencia = ?
                 )";
        $stmt1 = $pdo->prepare($sql1);
        $stmt1->execute([$mesAtual]);

        // Marcar como "Pendente" as dívidas que não têm lançamento no mês atual
        $sql2 = "UPDATE laboral_controle_dividas ld
                 SET ld.status_detalhado = 'Pendente'
                 WHERE ld.status = 'A'
                 AND ld.status_detalhado != 'Quitada'
                 AND NOT EXISTS (
                     SELECT 1 FROM laboral_controle_dividas_descontos lmd
                     WHERE lmd.multa_id = ld.id
                     AND lmd.mes_referencia = ?
                 )";
        $stmt2 = $pdo->prepare($sql2);
        $stmt2->execute([$mesAtual]);

        // Marcar como "Quitada" as dívidas que já foram pagas integralmente
        $sql3 = "UPDATE laboral_controle_dividas ld
                 SET ld.status_detalhado = 'Quitada', ld.data_quitacao = NOW()
                 WHERE ld.status = 'A'
                 AND ld.valor_divida IS NOT NULL
                 AND (
                     SELECT COALESCE(SUM(lmd.valor_desconto), 0)
                     FROM laboral_controle_dividas_descontos lmd
                     WHERE lmd.multa_id = ld.id
                 ) >= ld.valor_divida";
        $stmt3 = $pdo->prepare($sql3);
        $stmt3->execute();

        // Retornar estatísticas
        $sqlStats = "SELECT
                       COUNT(CASE WHEN status_detalhado = 'Ativa' THEN 1 END) as ativas,
                       COUNT(CASE WHEN status_detalhado = 'Pendente' THEN 1 END) as pendentes
                    FROM laboral_controle_dividas
                    WHERE status = 'A'";
        $stmtStats = $pdo->prepare($sqlStats);
        $stmtStats->execute();
        $stats = $stmtStats->fetch();

        return [
            'ativas' => $stats['ativas'],
            'pendentes' => $stats['pendentes']
        ];
    } catch (PDOException $e) {
        throw new Exception('Erro ao atualizar status: ' . $e->getMessage());
    }
}

// Função para consultar histórico de auditoria
function buscarHistoricoAuditoria($pdo, $multaId = null, $filtros = [])
{
    try {
        $sql = "SELECT h.*, u.nome as usuario_nome
                FROM laboral_controle_dividas_historico h
                LEFT JOIN users u ON h.usuario_responsavel = u.id
                WHERE 1=1";
        $params = [];

        if ($multaId) {
            $sql .= " AND h.multa_id = ?";
            $params[] = $multaId;
        }

        if (!empty($filtros['tipo_acao'])) {
            $sql .= " AND h.tipo_acao = ?";
            $params[] = $filtros['tipo_acao'];
        }

        if (!empty($filtros['data_inicio'])) {
            $sql .= " AND h.data_acao >= ?";
            $params[] = $filtros['data_inicio'];
        }

        if (!empty($filtros['data_fim'])) {
            $sql .= " AND h.data_acao <= ?";
            $params[] = $filtros['data_fim'];
        }

        $sql .= " ORDER BY h.data_acao DESC";

        if (!empty($filtros['limit'])) {
            $sql .= " LIMIT ?";
            $params[] = (int)$filtros['limit'];
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        throw new Exception('Erro ao buscar histórico de auditoria: ' . $e->getMessage());
    }
}

// Processar requisições AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    ob_clean();

    try {
        switch ($_POST['action']) {
            case 'listar':
                $filtros = [
                    'busca' => $_POST['busca'] ?? '',
                    'status_detalhado' => $_POST['status_detalhado'] ?? '',
                    'status' => $_POST['status'] ?? '',
                    'tipo' => $_POST['tipo'] ?? '',
                    'mostrar_inativos' => isset($_POST['mostrar_inativos']),
                    'limit' => $_POST['limit'] ?? 50,
                    'offset' => $_POST['offset'] ?? 0
                ];

                $dividas = buscarDividas($pdo, $filtros);
                $total = contarDividas($pdo, $filtros);

                echo json_encode([
                    'success' => true,
                    'data' => $dividas,
                    'total' => $total
                ], JSON_UNESCAPED_UNICODE);
                break;

            case 'salvar':
                $divida_id = salvarDivida($pdo, $_POST);
                echo json_encode(['success' => true, 'data' => ['id' => $divida_id]], JSON_UNESCAPED_UNICODE);
                break;

            case 'buscar_interno':
                $internos = buscarInternoPorTermo($pdo, $_POST['termo']);
                echo json_encode(['success' => true, 'data' => $internos], JSON_UNESCAPED_UNICODE);
                break;

            case 'stats':
                $stats = buscarStats($pdo);
                echo json_encode(['success' => true, 'data' => $stats], JSON_UNESCAPED_UNICODE);
                break;

            case 'detalhes_kpi':
                $detalhes = buscarDetalhesKPI($pdo, $_POST['tipo']);
                echo json_encode(['success' => true, 'data' => $detalhes], JSON_UNESCAPED_UNICODE);
                break;

            case 'lancar_salario':
                $lancamento_id = lancarSalario($pdo, $_POST);
                echo json_encode(['success' => true, 'data' => ['id' => $lancamento_id]], JSON_UNESCAPED_UNICODE);
                break;

            case 'historico':
                $historico = buscarHistoricoDivida($pdo, $_POST['divida_id']);
                echo json_encode(['success' => true, 'data' => $historico], JSON_UNESCAPED_UNICODE);
                break;

            case 'projecao':
                $projecao = buscarProjecaoDivida($pdo, $_POST['divida_id']);
                echo json_encode(['success' => true, 'data' => $projecao], JSON_UNESCAPED_UNICODE);
                break;

            case 'estatisticas_parcelas':
                $estatisticas = buscarEstatisticasParcelas($pdo, $_POST['divida_id']);
                echo json_encode(['success' => true, 'data' => $estatisticas], JSON_UNESCAPED_UNICODE);
                break;

            case 'auditoria':
                $multaId = $_POST['multa_id'] ?? null;
                $filtros = [
                    'tipo_acao' => $_POST['tipo_acao'] ?? null,
                    'data_inicio' => $_POST['data_inicio'] ?? null,
                    'data_fim' => $_POST['data_fim'] ?? null,
                    'limit' => $_POST['limit'] ?? 100
                ];
                $auditoria = buscarHistoricoAuditoria($pdo, $multaId, $filtros);
                echo json_encode(['success' => true, 'data' => $auditoria], JSON_UNESCAPED_UNICODE);
                break;

            case 'excluir':
                $result = excluirDivida($pdo, $_POST['id']);
                echo json_encode(['success' => true, 'data' => $result], JSON_UNESCAPED_UNICODE);
                break;

            case 'corrigir_status':
                $resultados = atualizarStatusPorMesVigente($pdo);
                echo json_encode(['success' => true, 'message' => "Status atualizado: {$resultados['ativas']} ativa(s), {$resultados['pendentes']} pendente(s)"], JSON_UNESCAPED_UNICODE);
                break;

            default:
                throw new Exception('Ação não reconhecida');
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }

    exit;
}

// Carregar dados para a view
try {
    $stats = buscarStats($pdo);
} catch (Exception $e) {
    $stats = [
        'total_ativas' => 0,
        'arrecadado_mes' => 0,
        'pendentes' => 0,
        'inadimplentes' => 0
    ];
}
