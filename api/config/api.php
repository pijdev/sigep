<?php
// Configuração simples da API
header('Content-Type: application/json');

// Configurações
$config = [
    'db' => require __DIR__ . '/../../conf/db.php',
    'token' => 'visTFRIxtMp1cFx8h4SUBwIOmECXUqWIPU0QBgo5Ib119c0DqdsO1gelZ1tSPO1S'
];

// Conexão PDO
function getDB()
{
    global $config;
    $db = $config['db'];
    return new PDO(
        "mysql:host={$db['host']};dbname={$db['dbname']};charset={$db['charset']}",
        $db['user'],
        $db['pass']
    );
}

// Verificar token
function checkToken()
{
    global $config;
    $token = $_SERVER['HTTP_X_API_TOKEN'] ?? $_GET['token'] ?? '';
    if ($token !== $config['token']) {
        http_response_code(401);
        die(json_encode(['error' => 'Token inválido']));
    }
}

// Resposta JSON
function json($data, $status = 200)
{
    http_response_code($status);
    echo json_encode($data);
    exit;
}
