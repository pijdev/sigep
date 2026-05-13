<?php
// includes/caminhoes_pipa_logica.php
// Lógica PHP para controle de caminhões pipa

require_once __DIR__ . '/../conf/db.php';

// Função para conectar ao banco de dados
function conectarDB() {
    $config = require __DIR__ . '/../conf/db.php';
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";

    try {
        $pdo = new PDO($dsn, $config['user'], $config['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]);
        return $pdo;
    } catch (PDOException $e) {
        throw new Exception('Erro na conexão com o banco de dados: ' . $e->getMessage());
    }
}

// Função para obter contadores
function getContadores($pdo) {
    try {
        // Total de registros
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM controle_caminhoes_pipa");
        $stmt->execute();
        $totalRegistros = $stmt->fetch(PDO::FETCH_COLUMN);

        // Registros de hoje
        $stmt = $pdo->prepare("SELECT COUNT(*) as hoje FROM controle_caminhoes_pipa WHERE DATE(data_abastecimento) = CURDATE()");
        $stmt->execute();
        $registrosHoje = $stmt->fetch(PDO::FETCH_COLUMN);

        // Total de litros
        $stmt = $pdo->prepare("SELECT SUM(quantidade_litros) as total_litros FROM controle_caminhoes_pipa");
        $stmt->execute();
        $totalLitros = $stmt->fetch(PDO::FETCH_COLUMN) ?: 0;

        // Média de litros
        $stmt = $pdo->prepare("SELECT AVG(quantidade_litros) as media_litros FROM controle_caminhoes_pipa");
        $stmt->execute();
        $mediaLitros = $stmt->fetch(PDO::FETCH_COLUMN) ?: 0;

        // Total de motoristas únicos
        $stmt = $pdo->prepare("SELECT COUNT(DISTINCT id_motorista) as total_motoristas FROM controle_caminhoes_pipa");
        $stmt->execute();
        $totalMotoristas = $stmt->fetch(PDO::FETCH_COLUMN);

        // Total de veículos únicos
        $stmt = $pdo->prepare("SELECT COUNT(DISTINCT id_veiculo) as total_veiculos FROM controle_caminhoes_pipa");
        $stmt->execute();
        $totalVeiculos = $stmt->fetch(PDO::FETCH_COLUMN);

        return [
            'totalRegistros' => $totalRegistros,
            'registrosHoje' => $registrosHoje,
            'totalLitros' => $totalLitros,
            'mediaLitros' => $mediaLitros,
            'totalMotoristas' => $totalMotoristas,
            'totalVeiculos' => $totalVeiculos
        ];
    } catch (PDOException $e) {
        throw new Exception('Erro ao obter contadores: ' . $e->getMessage());
    }
}

// Função para listar registros com paginação
function listarRegistros($pdo, $filtros = [], $page = 1, $limit = 10) {
    try {
        $offset = ($page - 1) * $limit;

        // Query simplificada para debug
        $sql = "
            SELECT
                ccp.id,
                ccp.data_abastecimento,
                ccp.id_motorista,
                ccp.id_empresa,
                ccp.id_veiculo,
                ccp.hora_chegada,
                ccp.quantidade_litros,
                ccp.criado_em,
                m.nome as motorista_nome,
                e.nome as empresa_nome,
                v.nome as veiculo_nome,
                v.placa as veiculo_placa,
                v.modelo as veiculo_modelo
            FROM controle_caminhoes_pipa ccp
            LEFT JOIN eclusa_motoristas m ON ccp.id_motorista = m.id
            LEFT JOIN eclusa_empresas e ON ccp.id_empresa = e.id
            LEFT JOIN eclusa_veiculos v ON ccp.id_veiculo = v.id
            WHERE 1=1
            ORDER BY ccp.data_abastecimento DESC, ccp.hora_chegada DESC
            LIMIT :limit OFFSET :offset
        ";

        $params = [
            ':limit' => $limit,
            ':offset' => $offset
        ];

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $registros = $stmt->fetchAll();

        // Formatar dados para o frontend
        $registrosFormatados = [];
        foreach ($registros as $registro) {
            $registrosFormatados[] = [
                'id' => $registro['id'],
                'data_registro' => $registro['data_abastecimento'],
                'placa' => $registro['veiculo_placa'] ?? 'N/A',
                'motorista' => $registro['motorista_nome'] ?? 'N/A',
                'empresa' => $registro['empresa_nome'] ?? 'N/A',
                'tipo' => 'Pipa', // Tipo não existe na tabela, vamos usar padrão
                'litros' => $registro['quantidade_litros'],
                'km_inicial' => 0, // Não existe na tabela
                'km_final' => 0, // Não existe na tabela
                'status' => 'Ativo', // Status não existe na tabela
                'observacoes' => '',
                'veiculo_nome' => $registro['veiculo_nome'] ?? 'N/A',
                'veiculo_placa' => $registro['veiculo_placa'] ?? 'N/A',
                'veiculo_modelo' => $registro['veiculo_modelo'] ?? 'N/A',
                'hora_chegada' => $registro['hora_chegada']
            ];
        }

        // Contar total para paginação
        $countSql = "SELECT COUNT(*) FROM controle_caminhoes_pipa";
        $stmt = $pdo->prepare($countSql);
        $stmt->execute();
        $totalRegistros = $stmt->fetch(PDO::FETCH_COLUMN);
        $totalPages = ceil($totalRegistros / $limit);

        return [
            'success' => true,
            'data' => [
                'registros' => $registrosFormatados,
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'totalRegistros' => $totalRegistros
            ]
        ];
    } catch (PDOException $e) {
        throw new Exception('Erro ao listar registros: ' . $e->getMessage());
    }
}

// Função para buscar opções de filtros
function listarOpcoes($pdo) {
    try {
        // Placas únicas dos veículos
        $stmt = $pdo->prepare("SELECT DISTINCT v.placa FROM eclusa_veiculos v
                                    INNER JOIN controle_caminhoes_pipa ccp ON v.id = ccp.id_veiculo
                                    WHERE v.placa IS NOT NULL ORDER BY v.placa");
        $stmt->execute();
        $placas = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Motoristas únicos
        $stmt = $pdo->prepare("SELECT DISTINCT m.nome FROM eclusa_motoristas m
                                    INNER JOIN controle_caminhoes_pipa ccp ON m.id = ccp.id_motorista
                                    WHERE m.nome IS NOT NULL ORDER BY m.nome");
        $stmt->execute();
        $motoristas = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Empresas únicas
        $stmt = $pdo->prepare("SELECT DISTINCT e.nome FROM eclusa_empresas e
                                    INNER JOIN controle_caminhoes_pipa ccp ON e.id = ccp.id_empresa
                                    WHERE e.nome IS NOT NULL ORDER BY e.nome");
        $stmt->execute();
        $empresas = $stmt->fetchAll(PDO::FETCH_COLUMN);

        return [
            'success' => true,
            'data' => [
                'placas' => $placas,
                'motoristas' => $motoristas,
                'empresas' => $empresas
            ]
        ];
    } catch (PDOException $e) {
        throw new Exception('Erro ao listar opções: ' . $e->getMessage());
    }
}

// Função para buscar registro por ID
function buscarRegistro($pdo, $id) {
    try {
        $sql = "
            SELECT
                ccp.id,
                ccp.data_abastecimento,
                ccp.id_motorista,
                ccp.id_empresa,
                ccp.id_veiculo,
                ccp.hora_chegada,
                ccp.quantidade_litros,
                ccp.criado_em,
                m.nome as motorista_nome,
                e.nome as empresa_nome,
                v.nome as veiculo_nome,
                v.placa as veiculo_placa,
                v.modelo as veiculo_modelo
            FROM controle_caminhoes_pipa ccp
            LEFT JOIN eclusa_motoristas m ON ccp.id_motorista = m.id
            LEFT JOIN eclusa_empresas e ON ccp.id_empresa = e.id
            LEFT JOIN eclusa_veiculos v ON ccp.id_veiculo = v.id
            WHERE ccp.id = :id
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);

        $registro = $stmt->fetch();

        if ($registro) {
            // Formatar dados para o frontend
            $registroFormatado = [
                'id' => $registro['id'],
                'data_registro' => $registro['data_abastecimento'],
                'placa' => $registro['veiculo_placa'] ?? 'N/A',
                'motorista' => $registro['motorista_nome'] ?? 'N/A',
                'empresa' => $registro['empresa_nome'] ?? 'N/A',
                'tipo' => 'Pipa', // Tipo não existe na tabela
                'litros' => $registro['quantidade_litros'],
                'km_inicial' => 0, // Não existe na tabela
                'km_final' => 0, // Não existe na tabela
                'status' => 'Ativo', // Status não existe na tabela
                'observacoes' => '',
                'veiculo_nome' => $registro['veiculo_nome'] ?? 'N/A',
                'veiculo_placa' => $registro['veiculo_placa'] ?? 'N/A',
                'veiculo_modelo' => $registro['veiculo_modelo'] ?? 'N/A',
                'hora_chegada' => $registro['hora_chegada']
            ];

            return [
                'success' => true,
                'data' => $registroFormatado
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Registro não encontrado'
            ];
        }
    } catch (PDOException $e) {
        throw new Exception('Erro ao buscar registro: ' . $e->getMessage());
    }
}

// Função para salvar registro
function salvarRegistro($pdo, $dados) {
    try {
        if ($dados['action'] === 'atualizar') {
            $sql = "
                UPDATE controle_caminhoes_pipa SET
                    placa = :placa,
                    motorista = :motorista,
                    empresa = :empresa,
                    tipo = :tipo,
                    litros = :litros,
                    km_inicial = :km_inicial,
                    km_final = :km_final,
                    status = :status,
                    observacoes = :observacoes,
                    data_atualizacao = NOW()
                WHERE id = :id
            ";

            $params = [
                ':placa' => $dados['placa'],
                ':motorista' => $dados['motorista'],
                ':empresa' => $dados['empresa'],
                ':tipo' => $dados['tipo'],
                ':litros' => (float)$dados['litros'],
                ':km_inicial' => (int)$dados['km_inicial'],
                ':km_final' => (int)$dados['km_final'],
                ':status' => $dados['status'],
                ':observacoes' => $dados['observacoes'],
                ':id' => $dados['id']
            ];

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            return ['success' => true, 'message' => 'Registro atualizado com sucesso'];
        } else {
            $sql = "
                INSERT INTO controle_caminhoes_pipa (
                    placa, motorista, empresa, tipo, litros,
                    km_inicial, km_final, status, observacoes,
                    data_registro, data_cadastro
                ) VALUES (
                    :placa, :motorista, :empresa, :tipo, :litros,
                    :km_inicial, :km_final, :status, :observacoes,
                    NOW(), NOW()
                )
            ";

            $params = [
                ':placa' => $dados['placa'],
                ':motorista' => $dados['motorista'],
                ':empresa' => $dados['empresa'],
                ':tipo' => $dados['tipo'],
                ':litros' => (float)$dados['litros'],
                ':km_inicial' => (int)$dados['km_inicial'],
                ':km_final' => (int)$dados['km_final'],
                ':status' => $dados['status'],
                ':observacoes' => $dados['observacoes']
            ];

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            return ['success' => true, 'message' => 'Registro salvo com sucesso'];
        }
    } catch (PDOException $e) {
        throw new Exception('Erro ao salvar registro: ' . $e->getMessage());
    }
}

// Router principal - só executar se for requisição AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    try {
        $pdo = conectarDB();
        $action = $_POST['action'] ?? '';

        switch ($action) {
        case 'get_contadores':
            $contadores = getContadores($pdo);
            echo json_encode(['success' => true, 'data' => $contadores]);
            break;

        case 'listar':
            $page = (int)($_POST['page'] ?? 1);
            $filtros = $_POST;
            unset($filtros['action'], $filtros['page']);

            $resultado = listarRegistros($pdo, $filtros, $page);
            echo json_encode($resultado);
            break;

        case 'listar_opcoes':
            $resultado = listarOpcoes($pdo);
            echo json_encode($resultado);
            break;

        case 'buscar':
            $id = (int)($_POST['id'] ?? 0);
            $resultado = buscarRegistro($pdo, $id);
            echo json_encode($resultado);
            break;

        case 'salvar':
        case 'atualizar':
            // Validar campos obrigatórios
            $camposObrigatorios = ['placa', 'motorista', 'empresa', 'tipo', 'litros', 'km_inicial', 'km_final', 'status'];
            $erros = [];

            foreach ($camposObrigatorios as $campo) {
                if (empty($_POST[$campo])) {
                    $erros[] = "O campo $campo é obrigatório";
                }
            }

            if (!empty($erros)) {
                echo json_encode(['success' => false, 'message' => implode(', ', $erros)]);
                break;
            }

            $dados = [
                'action' => $action,
                'id' => $_POST['id'] ?? null,
                'placa' => $_POST['placa'],
                'motorista' => $_POST['motorista'],
                'empresa' => $_POST['empresa'],
                'tipo' => $_POST['tipo'],
                'litros' => $_POST['litros'],
                'km_inicial' => $_POST['km_inicial'],
                'km_final' => $_POST['km_final'],
                'status' => $_POST['status'],
                'observacoes' => $_POST['observacoes'] ?? ''
            ];

            $resultado = salvarRegistro($pdo, $dados);
            echo json_encode($resultado);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Ação não reconhecida']);
            break;
    }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>
