<?php
// Canteiros de Trabalho - Controller SIGEP
// Módulo para gerenciamento e análise de canteiros de trabalho

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

// Parse raw input manualmente se $_POST estiver vazio (para application/x-www-form-urlencoded)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST)) {
    $rawInput = file_get_contents('php://input');
    if (!empty($rawInput)) {
        parse_str($rawInput, $_POST);
    }
}

$isAjax = $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']);

// Funções do módulo
function getEstatisticasCanteiros(PDO $pdo): array
{
    try {
        $stats = [];

        // Total de internos trabalhando (dados reais)
        $stmt = $pdo->query("SELECT COUNT(DISTINCT ipen) as total FROM internos_laboral WHERE status = 'A'");
        $stats['internos_trabalhando'] = $stmt->fetch()['total'];

        // Contar canteiros ativos reais (com internos)
        $stmt = $pdo->query("
            SELECT
                CASE
                    WHEN il.estabelecimento LIKE '%CISER%' THEN 'Ciser'
                    WHEN il.estabelecimento LIKE '%TUTTI%' THEN 'Tutti Baby'
                    WHEN il.estabelecimento LIKE '%TIGRE%' THEN 'Tigre'
                    WHEN il.estabelecimento LIKE '%SOLUÇÕES%' AND il.estabelecimento LIKE '%ALIMENTAÇÃO%' THEN 'Cozinha Fechado'
                    WHEN il.estabelecimento LIKE '%CARGA E DESCARGA%' THEN 'Carga e Descarga'
                    WHEN il.estabelecimento LIKE '%PLASBOHN%' THEN 'Plasbohn'
                    ELSE 'Outros'
                END as canteiro_grupo
            FROM internos_laboral il
            WHERE il.status = 'A'
            GROUP BY canteiro_grupo
        ");
        $canteiros_ativos = $stmt->rowCount();
        $stats['canteiros_ativos'] = $canteiros_ativos;

        // Total de canteiros (estrutura física conhecida)
        $stats['total_canteiros'] = 16; // Baseado na estrutura física real

        // Canteiros vazios (diferença)
        $stats['canteiros_vazios'] = $stats['total_canteiros'] - $stats['canteiros_ativos'];

        // Regalias (internos com regalia = 'S')
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM internos WHERE regalia = 'S' AND status = 'A'");
        $stats['total_regalias'] = $stmt->fetch()['total'];

        // Conveniados (estabelecimentos com SECRETARIA DE ESTADO)
        $stmt = $pdo->query("
            SELECT COUNT(DISTINCT i.ipen) as total
            FROM internos i
            JOIN internos_laboral il ON i.ipen = il.ipen
            WHERE il.status = 'A'
            AND il.estabelecimento LIKE '%SECRETARIA DE ESTADO%'
        ");
        $stats['conveniados'] = $stmt->fetch()['total'];

        return $stats;
    } catch (PDOException $e) {
        throw new Exception('Erro ao buscar estatísticas: ' . $e->getMessage());
    }
}




function getTermosBuscaCanteiro(string $canteiro): array
{
    // Mapear canteiro para termos de busca específicos no banco
    $canteiro_upper = strtoupper($canteiro);

    $mapeamento = [
        'Ciser' => ['%CISER%'],
        'TIGRE' => ['%TIGRE%'],
        'TIGRE S' => ['%TIGRE%SEMI%'],
        'TUTTI' => ['%TUTTI%'],
        'Cozinha Fechado' => ['%SOLUÇÕES%ALIMENTAÇÃO%COZINHA%'],
        'BA' => ['%TUTTI BABY IND. E COM. DE ART. INFANTIS LTDA 01%'],
        'DB' => ['%TUTTI BABY IND. E COM. DE ART. INFANTIS LTDA 02%'],
        'DA' => ['%PLASBOHN%D8%'],
        'EA' => ['%PLASBOHN INDUSTRIA DE PLASTICOS LTDA%'],
    ];

    return $mapeamento[$canteiro_upper] ?? ['%' . $canteiro_upper . '%'];
}

function getDetalhesCanteiro(PDO $pdo, string $canteiro): array
{
    try {
        // Buscar detalhes específicos do canteiro com turnos e contadores
        $termos_busca = getTermosBuscaCanteiro(strtoupper($canteiro));

        $sql = "
            SELECT
                i.ipen,
                i.nome,
                i.bloco,
                i.galeria,
                il.estabelecimento,
                il.remicao_inicio,
                il.liberacao_fim,
                il.remicao_fim,
                il.dias_semana,
                CASE
                    WHEN il.dias_semana LIKE '%SEG%' THEN 'Segunda-feira'
                    WHEN il.dias_semana LIKE '%TER%' THEN 'Terça-feira'
                    WHEN il.dias_semana LIKE '%QUA%' THEN 'Quarta-feira'
                    WHEN il.dias_semana LIKE '%QUI%' THEN 'Quinta-feira'
                    WHEN il.dias_semana LIKE '%SEX%' THEN 'Sexta-feira'
                    ELSE 'Sábado/Domingo'
                END as turno_descricao,
                CASE
                    WHEN il.dias_semana LIKE '%SEG%' THEN '1'
                    WHEN il.dias_semana LIKE '%TER%' THEN '2'
                    WHEN il.dias_semana LIKE '%QUA%' THEN '3'
                    WHEN il.dias_semana LIKE '%QUI%' THEN '4'
                    WHEN il.dias_semana LIKE '%SEX%' THEN '5'
                    ELSE '6/7'
                END as turno_numero,
                0 as eh_lider
            FROM internos_laboral il
            JOIN internos i ON i.ipen = il.ipen
            WHERE il.status = 'A'
        ";

        // Adicionar cláusulas WHERE dinamicamente baseado nos termos
        $where_clauses = [];
        $params = [];

        foreach ($termos_busca as $termo) {
            $where_clauses[] = "il.estabelecimento LIKE ?";
            $params[] = $termo;
        }

        if (!empty($where_clauses)) {
            $sql .= " AND (" . implode(' OR ', $where_clauses) . ")";
        }

        $sql .= " ORDER BY i.bloco, i.nome";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $detalhes = $stmt->fetchAll();

        // Se não encontrou internos, verificar se é um canteiro vazio
        if (empty($detalhes)) {
            $canteiros_vazios = ['AA', 'AB', 'CA', 'CB', 'BB'];
            if (in_array(strtoupper($canteiro), $canteiros_vazios)) {
                return [
                    'canteiro' => $canteiro,
                    'status' => 'vazio',
                    'total_internos' => 0,
                    'turnos' => [],
                    'mensagem' => 'Canteiro vazio'
                ];
            }

            // Se for EB, marcar como inexistente
            if (strtoupper($canteiro) === 'EB') {
                return [
                    'canteiro' => $canteiro,
                    'status' => 'inexistente',
                    'total_internos' => 0,
                    'turnos' => [],
                    'mensagem' => 'Canteiro inexistente'
                ];
            }
        }

        // Agrupar por turno e contar
        $turnos = [];
        $galerias_blocos = [];

        foreach ($detalhes as $interno) {
            $turno = $interno['turno_numero'];
            $galeria_bloco = $interno['bloco'];

            if (!isset($turnos[$turno])) {
                $turnos[$turno] = [
                    'turno' => $interno['turno_descricao'],
                    'numero' => $interno['turno_numero'],
                    'internos' => [],
                    'galerias_blocos' => []
                ];
            }

            $turnos[$turno]['internos'][] = $interno;

            if (!in_array($galeria_bloco, $turnos[$turno]['galerias_blocos'])) {
                $turnos[$turno]['galerias_blocos'][] = $galeria_bloco;
            }
        }

        // Contar por galeria/bloco
        foreach ($turnos as &$turno) {
            $turno['galerias_blocos'] = array_unique($turno['galerias_blocos']);
            $turno['galerias_blocos_count'] = count($turno['galerias_blocos']);
        }

        return [
            'internos' => $detalhes,
            'turnos' => array_values($turnos),
            'total_internos' => count($detalhes)
        ];
    } catch (PDOException $e) {
        throw new Exception('Erro ao buscar detalhes do canteiro: ' . $e->getMessage());
    }
}

function getEstatisticasCanteirosFiltradas(PDO $pdo, $galeria = '', $canteiro = '', $status = ''): array
{
    try {
        $stats = [];

        // Base SQL para contagem
        $sql_base = "
            FROM internos_laboral il
            JOIN internos i ON i.ipen = il.ipen
            WHERE il.status = 'A'
        ";

        $params = [];

        // Aplicar filtros
        if ($galeria) {
            $sql_base .= " AND i.galeria = ?";
            $params[] = $galeria;
        }

        if ($canteiro) {
            switch ($canteiro) {
                case 'Ciser':
                    $sql_base .= " AND il.estabelecimento LIKE '%CISER%'";
                    break;
                case 'TUTTI':
                    $sql_base .= " AND il.estabelecimento LIKE '%TUTTI%'";
                    break;
                case 'TIGRE':
                    $sql_base .= " AND il.estabelecimento LIKE '%TIGRE%'";
                    break;
                case 'PLASBOHN':
                    $sql_base .= " AND il.estabelecimento LIKE '%PLASBOHN%'";
                    break;
                case 'COZINHA':
                    $sql_base .= " AND il.estabelecimento LIKE '%SOLUÇÕES%' AND il.estabelecimento LIKE '%ALIMENTAÇÃO%'";
                    break;
                case 'CARGA':
                    $sql_base .= " AND il.estabelecimento LIKE '%CARGA E DESCARGA%'";
                    break;
            }
        }

        if ($status === 'regalia') {
            $sql_base .= " AND i.regalia = 'S'";
        }

        // Contar internos trabalhando com filtros
        $sql = "SELECT COUNT(DISTINCT i.ipen) as total " . $sql_base;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $stats['internos_trabalhando'] = $stmt->fetch()['total'];

        // Contar canteiros ativos com filtros
        $sql_canteiros = "
            SELECT
                CASE
                    WHEN il.estabelecimento LIKE '%CISER%' THEN 'Ciser'
                    WHEN il.estabelecimento LIKE '%TUTTI%' THEN 'Tutti Baby'
                    WHEN il.estabelecimento LIKE '%TIGRE%' THEN 'Tigre'
                    WHEN il.estabelecimento LIKE '%SOLUÇÕES%' AND il.estabelecimento LIKE '%ALIMENTAÇÃO%' THEN 'Cozinha Fechado'
                    WHEN il.estabelecimento LIKE '%CARGA E DESCARGA%' THEN 'Carga e Descarga'
                    WHEN il.estabelecimento LIKE '%PLASBOHN%' THEN 'Plasbohn'
                    ELSE 'Outros'
                END as canteiro_grupo
            " . $sql_base . "
            GROUP BY canteiro_grupo
        ";
        $stmt = $pdo->prepare($sql_canteiros);
        $stmt->execute($params);
        $stats['canteiros_ativos'] = $stmt->rowCount();

        // Contar regalias com filtros
        $sql_regalia_query = "SELECT COUNT(*) as total " . $sql_base . " AND i.regalia = 'S'";
        $stmt = $pdo->prepare($sql_regalia_query);
        $stmt->execute($params);
        $stats['total_regalias'] = $stmt->fetch()['total'];

        // Contar conveniados com filtros
        $sql_conveniados = "SELECT COUNT(DISTINCT i.ipen) as total " . $sql_base . " AND il.estabelecimento LIKE '%SECRETARIA DE ESTADO%'";
        $stmt = $pdo->prepare($sql_conveniados);
        $stmt->execute($params);
        $stats['conveniados'] = $stmt->fetch()['total'];

        // Total de canteiros (mantém fixo)
        $stats['total_canteiros'] = 16;
        $stats['canteiros_vazios'] = $stats['total_canteiros'] - $stats['canteiros_ativos'];

        return $stats;
    } catch (PDOException $e) {
        throw new Exception('Erro ao buscar estatísticas filtradas: ' . $e->getMessage());
    }
}

function getListaCanteiros(PDO $pdo, $pagina = 1, $limite = 10, $busca = '', $status = '', $ordenacao = 'nome'): array
{
    try {
        $offset = ($pagina - 1) * $limite;

        // SQL base para lista de canteiros
        $sql = "
            SELECT
                CASE
                    WHEN il.estabelecimento LIKE '%CISER%' THEN 'Ciser'
                    WHEN il.estabelecimento LIKE '%TUTTI BABY 01%' THEN 'BA'
                    WHEN il.estabelecimento LIKE '%TUTTI BABY 02%' THEN 'DB'
                    WHEN il.estabelecimento LIKE '%TUTTI BABY 05%' THEN 'Tutti Baby 05'
                    WHEN il.estabelecimento LIKE '%TIGRE 01%' THEN 'Tigre 01'
                    WHEN il.estabelecimento LIKE '%TIGRE 02%' THEN 'Tigre 02'
                    WHEN il.estabelecimento LIKE '%TIGRE%SEMI%' THEN 'Tigre Semiaberto'
                    WHEN il.estabelecimento LIKE '%PLASBOHN%D8%' THEN 'DA'
                    WHEN il.estabelecimento LIKE '%PLASBOHN INDUSTRIA DE PLASTICOS LTDA%' THEN 'EA'
                    WHEN il.estabelecimento LIKE '%SOLUÇÕES%ALIMENTAÇÃO%' THEN 'Cozinha Fechado'
                    WHEN il.estabelecimento LIKE '%CARGA E DESCARGA%' THEN 'Carga e Descarga'
                    ELSE 'Outros'
                END as canteiro_nome,
                CASE
                    WHEN il.estabelecimento LIKE '%CISER%' THEN 'Ciser'
                    WHEN il.estabelecimento LIKE '%TUTTI%' THEN 'Tutti Baby IND. E COM. DE ART. INFANTIS LTDA'
                    WHEN il.estabelecimento LIKE '%TIGRE%' THEN 'TIGRE MATERIAIS E SOLUCOES PARA CONSTRUCAO LTDA'
                    WHEN il.estabelecimento LIKE '%PLASBOHN%' THEN 'PLASBOHN INDUSTRIA DE PLASTICOS LTDA'
                    WHEN il.estabelecimento LIKE '%SOLUÇÕES%ALIMENTAÇÃO%' THEN 'Soluções Alimentação'
                    WHEN il.estabelecimento LIKE '%CARGA E DESCARGA%' THEN 'CARGA E DESCARGA DAS CONVENIADAS'
                    ELSE 'Outros'
                END as empresa,
                COUNT(DISTINCT i.ipen) as total_internos,
                GROUP_CONCAT(DISTINCT CONCAT(i.galeria, '-', i.bloco) ORDER BY i.galeria, i.bloco SEPARATOR ', ') as galerias,
                GROUP_CONCAT(DISTINCT il.dias_semana ORDER BY il.dias_semana SEPARATOR ', ') as turnos,
                'ativo' as status
            FROM internos_laboral il
            JOIN internos i ON i.ipen = il.ipen
            WHERE il.status = 'A'
        ";

        $params = [];

        // Aplicar filtros
        if ($busca) {
            $sql .= " AND (il.estabelecimento LIKE ? OR i.galeria LIKE ? OR i.bloco LIKE ?)";
            $params[] = "%{$busca}%";
            $params[] = "%{$busca}%";
            $params[] = "%{$busca}%";
        }

        // Ordenação
        switch ($ordenacao) {
            case 'internos':
                $sql .= " GROUP BY canteiro_nome, empresa ORDER BY total_internos DESC";
                break;
            case 'status':
                $sql .= " GROUP BY canteiro_nome, empresa ORDER BY status DESC";
                break;
            default:
                $sql .= " GROUP BY canteiro_nome, empresa ORDER BY canteiro_nome ASC";
        }

        // Contar total para paginação
        $sql_count = "SELECT COUNT(*) as total FROM ({$sql}) as subquery";
        $stmt = $pdo->prepare($sql_count);
        $stmt->execute($params);
        $total = $stmt->fetch()['total'];

        // Aplicar paginação
        $sql .= " LIMIT {$limite} OFFSET {$offset}";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $canteiros = $stmt->fetchAll();

        // Adicionar canteiros vazios (estrutura fixa)
        $canteiros_vazios = [
            ['canteiro_nome' => 'AA', 'empresa' => 'Vazio', 'total_internos' => 0, 'galerias' => '', 'turnos' => '', 'status' => 'vazio'],
            ['canteiro_nome' => 'AB', 'empresa' => 'Vazio', 'total_internos' => 0, 'galerias' => '', 'turnos' => '', 'status' => 'vazio'],
            ['canteiro_nome' => 'CA', 'empresa' => 'Vazio', 'total_internos' => 0, 'galerias' => '', 'turnos' => '', 'status' => 'vazio'],
            ['canteiro_nome' => 'CB', 'empresa' => 'Vazio', 'total_internos' => 0, 'galerias' => '', 'turnos' => '', 'status' => 'vazio'],
            ['canteiro_nome' => 'BB', 'empresa' => 'Vazio', 'total_internos' => 0, 'galerias' => '', 'turnos' => '', 'status' => 'vazio'],
            ['canteiro_nome' => 'EB', 'empresa' => 'Inexistente', 'total_internos' => 0, 'galerias' => '', 'turnos' => '', 'status' => 'inexistente']
        ];

        // Filtrar canteiros vazios se necessário
        if ($status !== 'ativos') {
            $canteiros = array_merge($canteiros, $canteiros_vazios);
        }

        return [
            'canteiros' => $canteiros,
            'total' => $total + count($canteiros_vazios),
            'pagina' => $pagina,
            'limite' => $limite,
            'total_paginas' => ceil(($total + count($canteiros_vazios)) / $limite)
        ];
    } catch (PDOException $e) {
        throw new Exception('Erro ao buscar lista de canteiros: ' . $e->getMessage());
    }
}

function getListaInternos(PDO $pdo, $pagina = 1, $limite = 10, $busca = '', $galeria = '', $bloco = '', $regalia = '', $empresa = ''): array
{
    try {
        $offset = ($pagina - 1) * $limite;

        // SQL base para lista de internos
        $sql = "
            SELECT
                i.ipen,
                i.nome,
                i.galeria,
                i.bloco,
                i.regalia,
                i.status,
                il.estabelecimento as empresa,
                il.dias_semana as turno,
                il.remicao_inicio as data_inicio,
                il.remicao_fim as data_fim,
                CASE
                    WHEN i.regalia = 'S' THEN 'Com Regalia'
                    ELSE 'Sem Regalia'
                END as regalia_descricao,
                CASE
                    WHEN il.estabelecimento LIKE '%CISER%' THEN 'Ciser'
                    WHEN il.estabelecimento LIKE '%TUTTI%' THEN 'Tutti Baby'
                    WHEN il.estabelecimento LIKE '%TIGRE%' THEN 'Tigre'
                    WHEN il.estabelecimento LIKE '%PLASBOHN%' THEN 'Plasbohn'
                    WHEN il.estabelecimento LIKE '%SOLUÇÕES%ALIMENTAÇÃO%' THEN 'Cozinha Fechado'
                    WHEN il.estabelecimento LIKE '%CARGA E DESCARGA%' THEN 'Carga e Descarga'
                    ELSE 'Outros'
                END as empresa_curta
            FROM internos i
            LEFT JOIN internos_laboral il ON i.ipen = il.ipen AND il.status = 'A'
            WHERE i.status = 'A'
        ";

        $params = [];

        // Aplicar filtros
        if ($busca) {
            $sql .= " AND (i.nome LIKE ? OR i.ipen LIKE ? OR i.galeria LIKE ? OR i.bloco LIKE ?)";
            $params[] = "%{$busca}%";
            $params[] = "%{$busca}%";
            $params[] = "%{$busca}%";
            $params[] = "%{$busca}%";
        }

        if ($galeria) {
            $sql .= " AND i.galeria = ?";
            $params[] = $galeria;
        }

        if ($bloco) {
            $sql .= " AND i.bloco = ?";
            $params[] = $bloco;
        }

        if ($regalia) {
            $sql .= " AND i.regalia = ?";
            $params[] = $regalia;
        }

        if ($empresa) {
            switch ($empresa) {
                case 'Ciser':
                    $sql .= " AND il.estabelecimento LIKE '%CISER%'";
                    break;
                case 'TUTTI':
                    $sql .= " AND il.estabelecimento LIKE '%TUTTI%'";
                    break;
                case 'TIGRE':
                    $sql .= " AND il.estabelecimento LIKE '%TIGRE%'";
                    break;
                case 'PLASBOHN':
                    $sql .= " AND il.estabelecimento LIKE '%PLASBOHN%'";
                    break;
                case 'COZINHA':
                    $sql .= " AND il.estabelecimento LIKE '%SOLUÇÕES%ALIMENTAÇÃO%'";
                    break;
            }
        }

        // Ordenação padrão
        $sql .= " ORDER BY i.nome ASC";

        // Contar total para paginação
        $sql_count = "SELECT COUNT(*) as total FROM ({$sql}) as subquery";
        $stmt = $pdo->prepare($sql_count);
        $stmt->execute($params);
        $total = $stmt->fetch()['total'];

        // Aplicar paginação
        $sql .= " LIMIT {$limite} OFFSET {$offset}";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $internos = $stmt->fetchAll();

        return [
            'internos' => $internos,
            'total' => $total,
            'pagina' => $pagina,
            'limite' => $limite,
            'total_paginas' => ceil($total / $limite)
        ];
    } catch (PDOException $e) {
        throw new Exception('Erro ao buscar lista de internos: ' . $e->getMessage());
    }
}

function getFichaInterno(PDO $pdo, $ipen): array
{
    try {
        // Dados básicos do interno
        $sql = "
            SELECT
                i.ipen,
                i.nome,
                i.galeria,
                i.bloco,
                i.regalia,
                i.regalia_setor,
                i.status,
                i.data_entrada,
                i.data_saida_prevista,
                CASE
                    WHEN i.regalia = 'S' THEN 'Com Regalia'
                    ELSE 'Sem Regalia'
                END as regalia_descricao
            FROM internos i
            WHERE i.ipen = ?
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$ipen]);
        $interno = $stmt->fetch();

        if (!$interno) {
            throw new Exception('Interno não encontrado');
        }

        // Dados trabalhistas
        $sql_laboral = "
            SELECT
                il.estabelecimento,
                il.dias_semana,
                il.remicao_inicio as data_inicio,
                il.remicao_fim as data_fim,
                il.status,
                CASE
                    WHEN il.estabelecimento LIKE '%CISER%' THEN 'Ciser'
                    WHEN il.estabelecimento LIKE '%TUTTI%' THEN 'Tutti Baby'
                    WHEN il.estabelecimento LIKE '%TIGRE%' THEN 'Tigre'
                    WHEN il.estabelecimento LIKE '%PLASBOHN%' THEN 'Plasbohn'
                    WHEN il.estabelecimento LIKE '%SOLUÇÕES%ALIMENTAÇÃO%' THEN 'Cozinha Fechado'
                    WHEN il.estabelecimento LIKE '%CARGA E DESCARGA%' THEN 'Carga e Descarga'
                    ELSE 'Outros'
                END as empresa_curta
            FROM internos_laboral il
            WHERE il.ipen = ?
            ORDER BY il.remicao_inicio DESC
        ";

        $stmt = $pdo->prepare($sql_laboral);
        $stmt->execute([$ipen]);
        $historico_trabalho = $stmt->fetchAll();

        // Trabalho atual
        $trabalho_atual = null;
        foreach ($historico_trabalho as $trabalho) {
            if ($trabalho['status'] === 'A') {
                $trabalho_atual = $trabalho;
                break;
            }
        }

        return [
            'interno' => $interno,
            'trabalho_atual' => $trabalho_atual,
            'historico_trabalho' => $historico_trabalho
        ];
    } catch (PDOException $e) {
        throw new Exception('Erro ao buscar ficha do interno: ' . $e->getMessage());
    }
}

function getDadosAnalytics(PDO $pdo, $periodo = 30, $tipo = 'ocupacao', $agrupamento = 'diario'): array
{
    try {
        $data_inicio = date('Y-m-d', strtotime("-{$periodo} days"));

        // Dados para gráfico de empresas
        $sql_empresas = "
            SELECT
                CASE
                    WHEN il.estabelecimento LIKE '%CISER%' THEN 'Ciser'
                    WHEN il.estabelecimento LIKE '%TUTTI%' THEN 'Tutti Baby'
                    WHEN il.estabelecimento LIKE '%TIGRE%' THEN 'Tigre'
                    WHEN il.estabelecimento LIKE '%PLASBOHN%' THEN 'Plasbohn'
                    WHEN il.estabelecimento LIKE '%SOLUÇÕES%ALIMENTAÇÃO%' THEN 'Cozinha Fechado'
                    WHEN il.estabelecimento LIKE '%CARGA E DESCARGA%' THEN 'Carga e Descarga'
                    ELSE 'Outros'
                END as empresa,
                COUNT(DISTINCT i.ipen) as total_internos
            FROM internos_laboral il
            JOIN internos i ON i.ipen = il.ipen
            WHERE il.status = 'A' AND i.status = 'A'
            GROUP BY empresa
            ORDER BY total_internos DESC
        ";

        $stmt = $pdo->prepare($sql_empresas);
        $stmt->execute();
        $dados_empresas = $stmt->fetchAll();

        // Dados para gráfico de galerias
        $sql_galerias = "
            SELECT
                i.galeria,
                COUNT(DISTINCT i.ipen) as total_internos
            FROM internos i
            WHERE i.status = 'A'
            GROUP BY i.galeria
            ORDER BY i.galeria ASC
        ";

        $stmt = $pdo->prepare($sql_galerias);
        $stmt->execute();
        $dados_galerias = $stmt->fetchAll();

        // Dados para gráfico de regalias
        $sql_regalias = "
            SELECT
                CASE WHEN i.regalia = 'S' THEN 'Com Regalia' ELSE 'Sem Regalia' END as tipo,
                COUNT(*) as total
            FROM internos i
            WHERE i.status = 'A'
            GROUP BY i.regalia
            ORDER BY total DESC
        ";

        $stmt = $pdo->prepare($sql_regalias);
        $stmt->execute();
        $dados_regalias = $stmt->fetchAll();

        // Dados para evolução temporal (simulado - dados históricos não disponíveis)
        $dados_evolucao = [];
        for ($i = $periodo; $i >= 0; $i--) {
            $data = date('Y-m-d', strtotime("-{$i} days"));
            $valor = rand(380, 450); // Simulação de variação
            $dados_evolucao[] = [
                'data' => date('d/m', strtotime($data)),
                'valor' => $valor
            ];
        }

        // Calcular KPIs
        $total_internos = array_sum(array_column($dados_empresas, 'total_internos'));
        $total_canteiros = 16;
        $canteiros_ativos = count($dados_empresas);
        $taxa_ocupacao = $total_canteiros > 0 ? round(($canteiros_ativos / $total_canteiros) * 100, 1) : 0;
        $media_por_canteiro = $canteiros_ativos > 0 ? round($total_internos / $canteiros_ativos, 1) : 0;
        $crescimento = rand(-5, 15); // Simulação
        $canteiros_criticos = rand(0, 3); // Simulação

        return [
            'empresas' => $dados_empresas,
            'galerias' => $dados_galerias,
            'regalias' => $dados_regalias,
            'evolucao' => $dados_evolucao,
            'kpis' => [
                'taxa_ocupacao' => $taxa_ocupacao,
                'crescimento' => $crescimento,
                'media_canteiro' => $media_por_canteiro,
                'canteiros_criticos' => $canteiros_criticos
            ]
        ];
    } catch (PDOException $e) {
        throw new Exception('Erro ao buscar dados analytics: ' . $e->getMessage());
    }
}

function exportarCSV(PDO $pdo, $tipo = 'canteiros', $filtros = []): string
{
    try {
        $csv = '';

        if ($tipo === 'canteiros') {
            // Header CSV
            $csv .= "Canteiro,Empresa,Status,Internos,Galerias,Turnos\n";

            // Dados dos canteiros
            $sql = "
                SELECT
                    CASE
                        WHEN il.estabelecimento LIKE '%CISER%' THEN 'Ciser'
                        WHEN il.estabelecimento LIKE '%TUTTI BABY 01%' THEN 'BA'
                        WHEN il.estabelecimento LIKE '%TUTTI BABY 02%' THEN 'DB'
                        WHEN il.estabelecimento LIKE '%TUTTI BABY 05%' THEN 'Tutti Baby 05'
                        WHEN il.estabelecimento LIKE '%TIGRE 01%' THEN 'Tigre 01'
                        WHEN il.estabelecimento LIKE '%TIGRE 02%' THEN 'Tigre 02'
                        WHEN il.estabelecimento LIKE '%TIGRE%SEMI%' THEN 'Tigre Semiaberto'
                        WHEN il.estabelecimento LIKE '%PLASBOHN%D8%' THEN 'DA'
                        WHEN il.estabelecimento LIKE '%PLASBOHN INDUSTRIA DE PLASTICOS LTDA%' THEN 'EA'
                        WHEN il.estabelecimento LIKE '%SOLUÇÕES%ALIMENTAÇÃO%' THEN 'Cozinha Fechado'
                        WHEN il.estabelecimento LIKE '%CARGA E DESCARGA%' THEN 'Carga e Descarga'
                        ELSE 'Outros'
                    END as canteiro_nome,
                    CASE
                        WHEN il.estabelecimento LIKE '%CISER%' THEN 'Ciser'
                        WHEN il.estabelecimento LIKE '%TUTTI%' THEN 'Tutti Baby IND. E COM. DE ART. INFANTIS LTDA'
                        WHEN il.estabelecimento LIKE '%TIGRE%' THEN 'TIGRE MATERIAIS E SOLUCOES PARA CONSTRUCAO LTDA'
                        WHEN il.estabelecimento LIKE '%PLASBOHN%' THEN 'PLASBOHN INDUSTRIA DE PLASTICOS LTDA'
                        WHEN il.estabelecimento LIKE '%SOLUÇÕES%ALIMENTAÇÃO%' THEN 'Soluções Alimentação'
                        WHEN il.estabelecimento LIKE '%CARGA E DESCARGA%' THEN 'CARGA E DESCARGA DAS CONVENIADAS'
                        ELSE 'Outros'
                    END as empresa,
                    COUNT(DISTINCT i.ipen) as total_internos,
                    GROUP_CONCAT(DISTINCT CONCAT(i.galeria, '-', i.bloco) ORDER BY i.galeria, i.bloco SEPARATOR ', ') as galerias,
                    GROUP_CONCAT(DISTINCT il.dias_semana ORDER BY il.dias_semana SEPARATOR ', ') as turnos,
                    'ativo' as status
                FROM internos_laboral il
                JOIN internos i ON i.ipen = il.ipen
                WHERE il.status = 'A'
                GROUP BY canteiro_nome, empresa
                ORDER BY canteiro_nome ASC
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $canteiros = $stmt->fetchAll();

            // Adicionar canteiros vazios
            $canteiros_vazios = [
                ['canteiro_nome' => 'AA', 'empresa' => 'Vazio', 'total_internos' => 0, 'galerias' => '', 'turnos' => '', 'status' => 'vazio'],
                ['canteiro_nome' => 'AB', 'empresa' => 'Vazio', 'total_internos' => 0, 'galerias' => '', 'turnos' => '', 'status' => 'vazio'],
                ['canteiro_nome' => 'CA', 'empresa' => 'Vazio', 'total_internos' => 0, 'galerias' => '', 'turnos' => '', 'status' => 'vazio'],
                ['canteiro_nome' => 'CB', 'empresa' => 'Vazio', 'total_internos' => 0, 'galerias' => '', 'turnos' => '', 'status' => 'vazio'],
                ['canteiro_nome' => 'BB', 'empresa' => 'Vazio', 'total_internos' => 0, 'galerias' => '', 'turnos' => '', 'status' => 'vazio'],
                ['canteiro_nome' => 'EB', 'empresa' => 'Inexistente', 'total_internos' => 0, 'galerias' => '', 'turnos' => '', 'status' => 'inexistente']
            ];

            $todos_canteiros = array_merge($canteiros, $canteiros_vazios);

            foreach ($todos_canteiros as $canteiro) {
                $csv .= '"' . $canteiro['canteiro_nome'] . '",';
                $csv .= '"' . $canteiro['empresa'] . '",';
                $csv .= '"' . ucfirst($canteiro['status']) . '",';
                $csv .= $canteiro['total_internos'] . ',';
                $csv .= '"' . ($canteiro['galerias'] ?: '-') . '",';
                $csv .= '"' . ($canteiro['turnos'] ?: '-') . '"' . "\n";
            }
        } elseif ($tipo === 'internos') {
            // Header CSV
            $csv .= "IPEN,Nome,Galeria,Bloco,Empresa,Turno,Regalia,Status\n";

            // Dados dos internos
            $sql = "
                SELECT
                    i.ipen,
                    i.nome,
                    i.galeria,
                    i.bloco,
                    i.regalia,
                    i.status,
                    il.estabelecimento as empresa,
                    il.dias_semana as turno,
                    CASE
                        WHEN i.regalia = 'S' THEN 'Com Regalia'
                        ELSE 'Sem Regalia'
                    END as regalia_descricao,
                    CASE
                        WHEN il.estabelecimento LIKE '%CISER%' THEN 'Ciser'
                        WHEN il.estabelecimento LIKE '%TUTTI%' THEN 'Tutti Baby'
                        WHEN il.estabelecimento LIKE '%TIGRE%' THEN 'Tigre'
                        WHEN il.estabelecimento LIKE '%PLASBOHN%' THEN 'Plasbohn'
                        WHEN il.estabelecimento LIKE '%SOLUÇÕES%ALIMENTAÇÃO%' THEN 'Cozinha Fechado'
                        WHEN il.estabelecimento LIKE '%CARGA E DESCARGA%' THEN 'Carga e Descarga'
                        ELSE 'Outros'
                    END as empresa_curta
                FROM internos i
                LEFT JOIN internos_laboral il ON i.ipen = il.ipen AND il.status = 'A'
                WHERE i.status = 'A'
                ORDER BY i.nome ASC
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $internos = $stmt->fetchAll();

            foreach ($internos as $interno) {
                $csv .= $interno['ipen'] . ',';
                $csv .= '"' . $interno['nome'] . '",';
                $csv .= $interno['galeria'] . ',';
                $csv .= $interno['bloco'] . ',';
                $csv .= '"' . ($interno['empresa_curta'] ?: '-') . '",';
                $csv .= '"' . ($interno['turno'] ?: '-') . '",';
                $csv .= '"' . $interno['regalia_descricao'] . '",';
                $csv .= '"' . ($interno['status'] === 'A' ? 'Ativo' : 'Inativo') . '"' . "\n";
            }
        }

        return $csv;
    } catch (PDOException $e) {
        throw new Exception('Erro ao gerar CSV: ' . $e->getMessage());
    }
}

function buscarInternoPorIPEN(PDO $pdo, int $ipen): array
{
    try {
        // Buscar dados do interno
        $sql = "
            SELECT
                i.ipen,
                i.nome,
                i.galeria,
                i.bloco,
                i.regalia,
                i.status,
                il.estabelecimento,
                il.dias_semana,
                il.remicao_inicio,
                il.remicao_fim,
                il.status as status_laboral,
                CASE
                    WHEN il.estabelecimento LIKE '%CISER%' THEN 'Ciser'
                    WHEN il.estabelecimento LIKE '%TUTTI BABY 01%' THEN 'BA'
                    WHEN il.estabelecimento LIKE '%TUTTI BABY 02%' THEN 'DB'
                    WHEN il.estabelecimento LIKE '%TUTTI BABY 05%' THEN 'Tutti Baby 05'
                    WHEN il.estabelecimento LIKE '%TIGRE 01%' THEN 'Tigre 01'
                    WHEN il.estabelecimento LIKE '%TIGRE 02%' THEN 'Tigre 02'
                    WHEN il.estabelecimento LIKE '%TIGRE%SEMI%' THEN 'Tigre Semiaberto'
                    WHEN il.estabelecimento LIKE '%PLASBOHN%D8%' THEN 'DA'
                    WHEN il.estabelecimento LIKE '%PLASBOHN INDUSTRIA DE PLASTICOS LTDA%' THEN 'EA'
                    WHEN il.estabelecimento LIKE '%SOLUÇÕES%ALIMENTAÇÃO%' THEN 'Cozinha Fechado'
                    WHEN il.estabelecimento LIKE '%CARGA E DESCARGA%' THEN 'Carga e Descarga'
                    ELSE 'Outros'
                END as canteiro
            FROM internos i
            LEFT JOIN internos_laboral il ON i.ipen = il.ipen AND il.status = 'A'
            WHERE i.ipen = ?
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$ipen]);
        $interno = $stmt->fetch();

        if (!$interno) {
            return [
                'encontrado' => false,
                'mensagem' => 'IPEN não encontrado no sistema'
            ];
        }

        if ($interno['status_laboral'] !== 'A') {
            return [
                'encontrado' => true,
                'trabalhando' => false,
                'interno' => $interno,
                'mensagem' => 'Interno não está trabalhando atualmente'
            ];
        }

        return [
            'encontrado' => true,
            'trabalhando' => true,
            'interno' => $interno,
            'mensagem' => 'Interno encontrado trabalhando'
        ];
    } catch (PDOException $e) {
        throw new Exception('Erro ao buscar interno: ' . $e->getMessage());
    }
}

function debugInternosPorEmpresa(PDO $pdo, string $empresa): array
{
    try {
        $sql = "
            SELECT
                i.ipen,
                i.nome,
                i.galeria,
                i.bloco,
                il.estabelecimento,
                il.dias_semana,
                il.remicao_inicio,
                il.remicao_fim,
                il.status as status_laboral,
                CASE
                    WHEN il.estabelecimento LIKE '%CISER%' THEN 'Ciser'
                    WHEN il.estabelecimento LIKE '%TUTTI BABY 01%' THEN 'BA'
                    WHEN il.estabelecimento LIKE '%TUTTI BABY 02%' THEN 'DB'
                    WHEN il.estabelecimento LIKE '%TUTTI BABY 05%' THEN 'Tutti Baby 05'
                    WHEN il.estabelecimento LIKE '%TIGRE 01%' THEN 'Tigre 01'
                    WHEN il.estabelecimento LIKE '%TIGRE 02%' THEN 'Tigre 02'
                    WHEN il.estabelecimento LIKE '%TIGRE%SEMI%' THEN 'Tigre Semiaberto'
                    WHEN il.estabelecimento LIKE '%PLASBOHN%D8%' THEN 'DA'
                    WHEN il.estabelecimento LIKE '%PLASBOHN INDUSTRIA DE PLASTICOS LTDA%' THEN 'EA'
                    WHEN il.estabelecimento LIKE '%SOLUÇÕES%ALIMENTAÇÃO%' THEN 'Cozinha Fechado'
                    WHEN il.estabelecimento LIKE '%CARGA E DESCARGA%' THEN 'Carga e Descarga'
                    ELSE 'Outros'
                END as canteiro_mapeado
            FROM internos i
            JOIN internos_laboral il ON i.ipen = il.ipen
            WHERE il.estabelecimento LIKE ? AND il.status = 'A'
            ORDER BY i.nome
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute(['%' . $empresa . '%']);
        $internos = $stmt->fetchAll();

        return [
            'empresa_procurada' => $empresa,
            'total_encontrados' => count($internos),
            'internos' => $internos
        ];
    } catch (PDOException $e) {
        throw new Exception('Erro ao debug internos por empresa: ' . $e->getMessage());
    }
}

// Processar requisições AJAX
if ($isAjax) {
    ob_clean();

    try {
        $action = $_POST['action'] ?? '';

        switch ($action) {
            case 'estatisticas':
                $stats = getEstatisticasCanteiros($pdo);
                echo json_encode(['success' => true, 'data' => $stats], JSON_UNESCAPED_UNICODE);
                break;

            case 'estatisticas_filtradas':
                $galeria = $_POST['galeria'] ?? '';
                $canteiro = $_POST['canteiro'] ?? '';
                $status = $_POST['status'] ?? '';
                $stats = getEstatisticasCanteirosFiltradas($pdo, $galeria, $canteiro, $status);
                echo json_encode(['success' => true, 'data' => $stats], JSON_UNESCAPED_UNICODE);
                break;

            case 'lista_canteiros':
                $pagina = intval($_POST['pagina'] ?? 1);
                $limite = intval($_POST['limite'] ?? 10);
                $busca = $_POST['busca'] ?? '';
                $status = $_POST['status'] ?? '';
                $ordenacao = $_POST['ordenacao'] ?? 'nome';

                $lista = getListaCanteiros($pdo, $pagina, $limite, $busca, $status, $ordenacao);
                echo json_encode(['success' => true, 'data' => $lista], JSON_UNESCAPED_UNICODE);
                break;

            case 'lista_internos':
                $pagina = intval($_POST['pagina'] ?? 1);
                $limite = intval($_POST['limite'] ?? 10);
                $busca = $_POST['busca'] ?? '';
                $galeria = $_POST['galeria'] ?? '';
                $bloco = $_POST['bloco'] ?? '';
                $regalia = $_POST['regalia'] ?? '';
                $empresa = $_POST['empresa'] ?? '';

                $lista = getListaInternos($pdo, $pagina, $limite, $busca, $galeria, $bloco, $regalia, $empresa);
                echo json_encode(['success' => true, 'data' => $lista], JSON_UNESCAPED_UNICODE);
                break;

            case 'ficha_interno':
                $ipen = $_POST['ipen'] ?? '';
                if (empty($ipen)) {
                    throw new Exception('IPEN não informado');
                }

                $ficha = getFichaInterno($pdo, $ipen);
                echo json_encode(['success' => true, 'data' => $ficha], JSON_UNESCAPED_UNICODE);
                break;

            case 'analytics':
                $periodo = intval($_POST['periodo'] ?? 30);
                $tipo = $_POST['tipo'] ?? 'ocupacao';
                $agrupamento = $_POST['agrupamento'] ?? 'diario';

                $dados = getDadosAnalytics($pdo, $periodo, $tipo, $agrupamento);
                echo json_encode(['success' => true, 'data' => $dados], JSON_UNESCAPED_UNICODE);
                break;

            case 'exportar_csv':
                $tipo_export = $_POST['tipo'] ?? 'canteiros';
                $filtros = $_POST['filtros'] ?? [];

                $csv = exportarCSV($pdo, $tipo_export, $filtros);

                // Configurar headers para download
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename="' . $tipo_export . '_' . date('Y-m-d_H-i-s') . '.csv"');
                header('Cache-Control: no-cache, must-revalidate');
                header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

                // Adicionar BOM para UTF-8
                echo "\xEF\xBB\xBF" . $csv;
                exit;
                break;

            case 'buscar_interno':
                $ipen = intval($_POST['ipen'] ?? 0);
                if ($ipen <= 0) {
                    throw new Exception('IPEN inválido');
                }

                $resultado = buscarInternoPorIPEN($pdo, $ipen);
                echo json_encode(['success' => true, 'data' => $resultado], JSON_UNESCAPED_UNICODE);
                break;

            case 'debug_empresa':
                $empresa = $_POST['empresa'] ?? '';
                if (empty($empresa)) {
                    throw new Exception('Empresa não informada');
                }

                $resultado = debugInternosPorEmpresa($pdo, $empresa);
                echo json_encode(['success' => true, 'data' => $resultado], JSON_UNESCAPED_UNICODE);
                break;

            case 'detalhes':
                $canteiro = $_POST['canteiro'] ?? '';
                $detalhes = getDetalhesCanteiro($pdo, $canteiro);
                echo json_encode(['success' => true, 'data' => $detalhes], JSON_UNESCAPED_UNICODE);
                break;

            case 'detalhes_turnos':
                $canteiro = $_POST['canteiro'] ?? '';
                $detalhes = getDetalhesCanteiro($pdo, $canteiro);
                echo json_encode(['success' => true, 'data' => $detalhes], JSON_UNESCAPED_UNICODE);
                break;

            default:
                throw new Exception('Ação não reconhecida');
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }

    exit;
}
