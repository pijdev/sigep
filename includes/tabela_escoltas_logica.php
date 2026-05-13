<?php
$config = require __DIR__ . '/../conf/db.php';

// Endpoint AJAX para obter escoltas com período
if (isset($_POST['action']) && $_POST['action'] === 'get_todas_escoltas') {
    try {
        $periodo = isset($_POST['periodo']) ? (int)$_POST['periodo'] : 30;

        $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
        $pdo = new PDO($dsn, $config['user'], $config['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        // Construir WHERE clause para período
        $whereClause = "";
        $params = [];

        if ($periodo > 0) {
            $whereClause = " WHERE e.data_cadastro >= DATE_SUB(CURDATE(), INTERVAL :periodo DAY)";
            $params[':periodo'] = $periodo;
        }

        $sql = "
            SELECT
                e.id,
                e.data_cadastro,
                e.interno,
                e.destino,
                e.motorista,
                e.placa,
                e.status,
                e.hora_prevista,
                e.hora_chegada,
                e.hora_retorno,
                e.eh_not,
                e.cadastrado_por,
                DATE(e.data_cadastro) as data_formatada
            FROM eclusa_movimentacoes_escolta e
            $whereClause
            ORDER BY e.data_cadastro DESC, e.hora_prevista DESC
            LIMIT 1000
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $escoltas = $stmt->fetchAll();

        // Obter total para paginação
        $sqlTotal = "
            SELECT COUNT(*) as total
            FROM eclusa_movimentacoes_escolta e
            $whereClause
        ";
        $stmtTotal = $pdo->prepare($sqlTotal);
        $stmtTotal->execute($params);
        $total = $stmtTotal->fetch()['total'];

        echo json_encode([
            'escoltas' => $escoltas,
            'total' => $total,
            'periodo' => $periodo
        ]);

    } catch (PDOException $e) {
        echo json_encode(['error' => 'Erro ao carregar escoltas: ' . $e->getMessage()]);
    }
    exit;
}

// Endpoint para obter escoltas detalhadas por data
if (isset($_POST['action']) && $_POST['action'] === 'get_escoltas_detalhadas') {
    try {
        $data = $_POST['data'] ?? '';

        $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
        $pdo = new PDO($dsn, $config['user'], $config['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $sql = "
            SELECT
                e.id,
                e.data_cadastro,
                e.interno,
                e.destino,
                e.motorista,
                e.placa,
                e.status,
                e.hora_prevista,
                e.hora_chegada,
                e.hora_retorno,
                e.eh_not,
                e.cadastrado_por
            FROM eclusa_movimentacoes_escolta e
            WHERE DATE(e.data_cadastro) = :data
            ORDER BY e.hora_prevista ASC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute(['data' => $data]);
        $escoltas = $stmt->fetchAll();

        echo json_encode($escoltas);

    } catch (PDOException $e) {
        echo json_encode(['error' => 'Erro ao carregar escoltas: ' . $e->getMessage()]);
    }
    exit;
}

// Endpoint para obter escoltas por status
if (isset($_POST['action']) && $_POST['action'] === 'get_escoltas_por_status') {
    try {
        $status = $_POST['status'] ?? '';

        $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
        $pdo = new PDO($dsn, $config['user'], $config['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $sql = "
            SELECT
                e.id,
                e.data_cadastro,
                e.interno,
                e.destino,
                e.motorista,
                e.placa,
                e.status,
                e.hora_prevista,
                e.hora_chegada,
                e.hora_retorno,
                e.eh_not,
                e.cadastrado_por
            FROM eclusa_movimentacoes_escolta e
            WHERE e.status = :status
            ORDER BY e.data_cadastro DESC, e.hora_prevista DESC
            LIMIT 500
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute(['status' => $status]);
        $escoltas = $stmt->fetchAll();

        echo json_encode($escoltas);

    } catch (PDOException $e) {
        echo json_encode(['error' => 'Erro ao carregar escoltas: ' . $e->getMessage()]);
    }
    exit;
}

// Endpoint para obter escoltas por destino
if (isset($_POST['action']) && $_POST['action'] === 'get_escoltas_por_destino') {
    try {
        $destino = $_POST['destino'] ?? '';

        $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
        $pdo = new PDO($dsn, $config['user'], $config['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $sql = "
            SELECT
                e.id,
                e.data_cadastro,
                e.interno,
                e.destino,
                e.motorista,
                e.placa,
                e.status,
                e.hora_prevista,
                e.hora_chegada,
                e.hora_retorno,
                e.eh_not,
                e.cadastrado_por
            FROM eclusa_movimentacoes_escolta e
            WHERE e.destino = :destino
            ORDER BY e.data_cadastro DESC, e.hora_prevista DESC
            LIMIT 500
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute(['destino' => $destino]);
        $escoltas = $stmt->fetchAll();

        echo json_encode($escoltas);

    } catch (PDOException $e) {
        echo json_encode(['error' => 'Erro ao carregar escoltas: ' . $e->getMessage()]);
    }
    exit;
}

// Endpoint para obter escoltas por motorista
if (isset($_POST['action']) && $_POST['action'] === 'get_escoltas_por_motorista') {
    try {
        $motorista = $_POST['motorista'] ?? '';

        $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
        $pdo = new PDO($dsn, $config['user'], $config['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $sql = "
            SELECT
                e.id,
                e.data_cadastro,
                e.interno,
                e.destino,
                e.motorista,
                e.placa,
                e.status,
                e.hora_prevista,
                e.hora_chegada,
                e.hora_retorno,
                e.eh_not,
                e.cadastrado_por
            FROM eclusa_movimentacoes_escolta e
            WHERE e.motorista = :motorista
            ORDER BY e.data_cadastro DESC, e.hora_prevista DESC
            LIMIT 500
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute(['motorista' => $motorista]);
        $escoltas = $stmt->fetchAll();

        echo json_encode($escoltas);

    } catch (PDOException $e) {
        echo json_encode(['error' => 'Erro ao carregar escoltas: ' . $e->getMessage()]);
    }
    exit;
}

// Endpoint para obter escoltas por hora
if (isset($_POST['action']) && $_POST['action'] === 'get_escoltas_por_hora') {
    try {
        $hora = $_POST['hora'] ?? '';

        $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
        $pdo = new PDO($dsn, $config['user'], $config['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $sql = "
            SELECT
                e.id,
                e.data_cadastro,
                e.interno,
                e.destino,
                e.motorista,
                e.placa,
                e.status,
                e.hora_prevista,
                e.hora_chegada,
                e.hora_retorno,
                e.eh_not,
                e.cadastrado_por
            FROM eclusa_movimentacoes_escolta e
            WHERE HOUR(e.hora_prevista) = :hora
            ORDER BY e.data_cadastro DESC, e.hora_prevista DESC
            LIMIT 500
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute(['hora' => $hora]);
        $escoltas = $stmt->fetchAll();

        echo json_encode($escoltas);

    } catch (PDOException $e) {
        echo json_encode(['error' => 'Erro ao carregar escoltas: ' . $e->getMessage()]);
    }
    exit;
}

// Endpoint para obter escoltas por dia da semana
if (isset($_POST['action']) && $_POST['action'] === 'get_escoltas_por_dia_semana') {
    try {
        $dia = $_POST['dia'] ?? '';

        $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
        $pdo = new PDO($dsn, $config['user'], $config['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $sql = "
            SELECT
                e.id,
                e.data_cadastro,
                e.interno,
                e.destino,
                e.motorista,
                e.placa,
                e.status,
                e.hora_prevista,
                e.hora_chegada,
                e.hora_retorno,
                e.eh_not,
                e.cadastrado_por
            FROM eclusa_movimentacoes_escolta e
            WHERE DAYNAME(e.data_cadastro) = :dia
            ORDER BY e.data_cadastro DESC, e.hora_prevista DESC
            LIMIT 500
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute(['dia' => $dia]);
        $escoltas = $stmt->fetchAll();

        echo json_encode($escoltas);

    } catch (PDOException $e) {
        echo json_encode(['error' => 'Erro ao carregar escoltas: ' . $e->getMessage()]);
    }
    exit;
}

// Endpoint para obter escoltas por mês
if (isset($_POST['action']) && $_POST['action'] === 'get_escoltas_por_mes') {
    try {
        $mes = $_POST['mes'] ?? '';

        $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
        $pdo = new PDO($dsn, $config['user'], $config['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $sql = "
            SELECT
                e.id,
                e.data_cadastro,
                e.interno,
                e.destino,
                e.motorista,
                e.placa,
                e.status,
                e.hora_prevista,
                e.hora_chegada,
                e.hora_retorno,
                e.eh_not,
                e.cadastrado_por
            FROM eclusa_movimentacoes_escolta e
            WHERE DATE_FORMAT(e.data_cadastro, '%Y-%m') = :mes
            ORDER BY e.data_cadastro DESC, e.hora_prevista DESC
            LIMIT 500
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute(['mes' => $mes]);
        $escoltas = $stmt->fetchAll();

        echo json_encode($escoltas);

    } catch (PDOException $e) {
        echo json_encode(['error' => 'Erro ao carregar escoltas: ' . $e->getMessage()]);
    }
    exit;
}
?>
