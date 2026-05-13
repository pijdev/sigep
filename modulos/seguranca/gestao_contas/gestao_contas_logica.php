<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('America/Sao_Paulo');

// Verificação de autenticação e permissões
$eh_admin = isset($_SESSION['user_admin']) && $_SESSION['user_admin'] === true;
$tem_permissao_gestao_contas = $eh_admin || (isset($_SESSION['perm_gestao_contas']) && $_SESSION['perm_gestao_contas'] > 0);

if (!$tem_permissao_gestao_contas) {
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

// Matriz de acesso
$matriz_acesso = [
    'censura' => 'Censura',
    'almoxarifado' => 'Almoxarifado',
    'laboral' => 'Laboral',
    'seg_trab' => 'Segurança do Trabalho',
    'rh' => 'Recursos Humanos',
    'coord' => 'Coordenação',
    'eclusa' => 'Eclusa',
    'direcao' => 'Direção',
    'portaria' => 'Portaria',
    'ti' => 'Tecnologia da Informação',
    'serralheria' => 'Serralheria',
    'escola' => 'Escola',
    'carga' => 'Carga / Logística',
    'industria' => 'Indústria',
    'juridico' => 'Jurídico',
    'cozinha' => 'Cozinha'
];

// Funções auxiliares
function gestao_contas_json_response(array $payload, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function adminGenerateKioskToken(PDO $pdo, int $targetId, bool $force): array
{
    $stmt = $pdo->prepare("SELECT id, status, is_kiosk, kiosk_token FROM acesso_seguro WHERE id = ? LIMIT 1");
    $stmt->execute([$targetId]);
    $user = $stmt->fetch();

    if (!$user) {
        throw new Exception('Usuário não encontrado.');
    }
    if (($user['status'] ?? 'Inativo') !== 'Ativo') {
        throw new Exception('Usuário inativo não pode ter token kiosk.');
    }
    if ((int)($user['is_kiosk'] ?? 0) !== 1) {
        throw new Exception('Ative o Modo Kiosk para este usuário antes de gerar token.');
    }
    if (!$force && !empty($user['kiosk_token'])) {
        return [
            'success' => false,
            'code' => 'token_exists',
            'message' => 'Já existe token ativo. Use Regenerar para substituir.'
        ];
    }

    $plain = bin2hex(random_bytes(32));
    $hash = password_hash($plain, PASSWORD_DEFAULT);

    $up = $pdo->prepare("UPDATE acesso_seguro SET kiosk_token = ?, kiosk_token_updated_at = NOW() WHERE id = ?");
    $up->execute([$hash, $targetId]);

    $whenStmt = $pdo->prepare("SELECT DATE_FORMAT(kiosk_token_updated_at, '%d/%m/%Y %H:%i') as atualizado_em FROM acesso_seguro WHERE id = ?");
    $whenStmt->execute([$targetId]);
    $atualizado = $whenStmt->fetchColumn() ?: '';

    return [
        'success' => true,
        'token' => $plain,
        'token_updated_at_fmt' => $atualizado,
        'message' => $force ? 'Token regenerado com sucesso.' : 'Token gerado com sucesso.'
    ];
}

// Lógica principal
$viewData = [
    'matriz_acesso' => $matriz_acesso,
    'usuarios' => []
];

// Processar actions AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (ob_get_level() > 0) {
        ob_clean();
    }
    header('Content-Type: application/json');

    try {
        if ($_POST['action'] === 'save_user') {
            $pass_hash = !empty($_POST['senha']) ? password_hash($_POST['senha'], PASSWORD_DEFAULT) : null;
            $status = $_POST['status'];
            $is_adm = isset($_POST['is_admin']) ? 1 : 0;
            $is_kiosk = isset($_POST['is_kiosk']) ? 1 : 0;

            $fields = "nome = ?, usuario = ?, setor = ?, status = ?, is_admin = ?, is_kiosk = ?";
            $params = [$_POST['nome'], $_POST['usuario'], $_POST['setor'], $status, $is_adm, $is_kiosk];

            foreach ($matriz_acesso as $slug => $label) {
                $col = 'perm_' . $slug;
                $val = (int)($_POST[$col] ?? 0);
                $fields .= ", $col = ?";
                $params[] = $val;
            }

            if (!empty($_POST['user_id'])) {
                $targetId = (int)$_POST['user_id'];
                if ($pass_hash) {
                    $sql = "UPDATE acesso_seguro SET $fields, senha = ? WHERE id = ?";
                    $params[] = $pass_hash;
                    $params[] = $targetId;
                } else {
                    $sql = "UPDATE acesso_seguro SET $fields WHERE id = ?";
                    $params[] = $targetId;
                }
                $pdo->prepare($sql)->execute($params);

                if ($is_kiosk === 0) {
                    $pdo->prepare("UPDATE acesso_seguro SET kiosk_token = NULL, kiosk_token_updated_at = NULL WHERE id = ?")->execute([$targetId]);
                }
            } else {
                if (!$pass_hash) {
                    throw new Exception('Senha obrigatória para novos usuários.');
                }

                $permCols = implode(', ', array_map(function ($s) {
                    return 'perm_' . $s;
                }, array_keys($matriz_acesso)));

                $cols_names = "nome, usuario, setor, status, is_admin, is_kiosk, senha, $permCols";
                $sql = "INSERT INTO acesso_seguro ($cols_names) VALUES (" . implode(', ', array_fill(0, 7 + count($matriz_acesso), '?')) . ")";

                $insert_params = [$_POST['nome'], $_POST['usuario'], $_POST['setor'], $status, $is_adm, $is_kiosk, $pass_hash];
                foreach ($matriz_acesso as $slug => $label) {
                    $insert_params[] = (int)($_POST['perm_' . $slug] ?? 0);
                }

                $pdo->prepare($sql)->execute($insert_params);
            }

            gestao_contas_json_response(['success' => true]);
        }

        if ($_POST['action'] === 'delete_user') {
            if ((int)$_POST['id'] === (int)$_SESSION['user_id']) {
                throw new Exception('Você não pode excluir sua própria conta.');
            }
            $pdo->prepare('DELETE FROM acesso_seguro WHERE id = ?')->execute([(int)$_POST['id']]);
            gestao_contas_json_response(['success' => true]);
        }

        if ($_POST['action'] === 'generate_kiosk_token_admin' || $_POST['action'] === 'regenerate_kiosk_token_admin') {
            $targetId = (int)($_POST['user_id'] ?? 0);
            if ($targetId <= 0) {
                throw new Exception('Usuário inválido para token kiosk.');
            }

            $force = $_POST['action'] === 'regenerate_kiosk_token_admin';
            $result = adminGenerateKioskToken($pdo, $targetId, $force);
            gestao_contas_json_response($result);
        }
    } catch (Exception $e) {
        gestao_contas_json_response(['success' => false, 'message' => $e->getMessage()]);
    }
}

// Carregar lista de usuários para a view
$viewData['usuarios'] = $pdo->query("SELECT * FROM acesso_seguro ORDER BY nome ASC")->fetchAll();
