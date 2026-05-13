<?php

/**
 * SIGEP - Páginas de Erro Modernas
 * Controller principal para páginas de erro compatíveis com SPA e Standalone
 *
 * @version 1.0.0
 * @author SIGEP Development Team
 */

session_start();
require_once __DIR__ . '/../../../conf/db.php';

// Headers para resposta JSON (para AJAX) e HTML (para direto)
header('Content-Type: text/html; charset=utf-8');

// Função para retornar erro JSON (para AJAX)
function returnError($message, $code = 500)
{
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        http_response_code($code);
        echo json_encode(['error' => $message, 'code' => $code], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// Verificar se usuário está autenticado (opcional para páginas de erro)
$esta_autenticado = isset($_SESSION['user_id']) && isset($_SESSION['user_nome']);

// Obter informações do erro
$erro_codigo = $_GET['codigo'] ?? $_GET['404'] ?? $_GET['403'] ?? $_GET['500'] ?? '404';
$erro_mensagem = $_GET['mensagem'] ?? '';
$erro_redirect = $_GET['redirect'] ?? '/';

// Mapeamento de erros
$erros = [
    '404' => [
        'titulo' => 'Página Não Encontrada',
        'descricao' => 'A página que você está procurando não existe ou foi movida.',
        'icone' => 'fas fa-search',
        'cor' => 'warning',
        'acao_texto' => 'Voltar ao Início',
        'acao_url' => '/'
    ],
    '403' => [
        'titulo' => 'Acesso Negado',
        'descricao' => 'Você não tem permissão para acessar esta página.',
        'icone' => 'fas fa-lock',
        'cor' => 'danger',
        'acao_texto' => 'Sair',
        'acao_url' => '/auth/logout'
    ],
    '500' => [
        'titulo' => 'Erro Interno do Servidor',
        'descricao' => 'Ocorreu um erro inesperado. Nossa equipe já foi notificada.',
        'icone' => 'fas fa-exclamation-triangle',
        'cor' => 'danger',
        'acao_texto' => 'Tentar Novamente',
        'acao_url' => $erro_redirect
    ],
    '503' => [
        'titulo' => 'Serviço Indisponível',
        'descricao' => 'O sistema está temporariamente em manutenção.',
        'icone' => 'fas fa-tools',
        'cor' => 'info',
        'acao_texto' => 'Tentar Novamente',
        'acao_url' => $erro_redirect
    ]
];

// Obter dados do erro atual
$erro_atual = $erros[$erro_codigo] ?? $erros['404'];

// Se for requisição AJAX, retornar JSON
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    echo json_encode([
        'success' => false,
        'error' => $erro_atual['titulo'],
        'code' => $erro_codigo,
        'message' => $erro_mensagem ?: $erro_atual['descricao'],
        'redirect' => $erro_atual['acao_url']
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Função para detectar se está em modo SPA
function isSPARequest()
{
    return isset($_SERVER['HTTP_REFERER']) &&
        strpos($_SERVER['HTTP_REFERER'], 'index.php') !== false;
}

// Se for SPA e não tiver layout completo, retornar apenas o conteúdo
if (isSPARequest() && !isset($_GET['full'])) {
    echo '<div id="error-content" class="error-page-wrapper">';
    include __DIR__ . '/erro_conteudo.php';
    echo '</div>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $erro_atual['titulo']; ?> - SIGEP</title>

    <!-- AdminLTE 3 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Font: Source Sans Pro -->
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+Pro:wght@300;400;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Source Sans Pro', sans-serif;
            margin: 0;
            padding: 0;
        }

        .error-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .error-box {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            width: 100%;
            padding: 40px;
            text-align: center;
            animation: fadeInUp 0.6s ease-out;
        }

        .error-icon {
            font-size: 80px;
            margin-bottom: 20px;
            animation: bounce 2s infinite;
        }

        .error-code {
            font-size: 72px;
            font-weight: 700;
            color: #dc3545;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
        }

        .error-title {
            font-size: 28px;
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
        }

        .error-description {
            font-size: 16px;
            color: #666;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .error-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn-error {
            padding: 12px 30px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
            border: none;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        .error-details {
            margin-top: 30px;
            padding: 20px;
            background: rgba(0, 0, 0, 0.05);
            border-radius: 10px;
            font-size: 14px;
            color: #666;
        }

        .search-box {
            margin: 30px 0;
            position: relative;
        }

        .search-box input {
            width: 100%;
            padding: 15px 50px 15px 20px;
            border: 2px solid #e9ecef;
            border-radius: 25px;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .search-box input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .search-box button {
            position: absolute;
            right: 5px;
            top: 50%;
            transform: translateY(-50%);
            background: #667eea;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .search-box button:hover {
            background: #5a6fd8;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes bounce {

            0%,
            20%,
            50%,
            80%,
            100% {
                transform: translateY(0);
            }

            40% {
                transform: translateY(-10px);
            }

            60% {
                transform: translateY(-5px);
            }
        }

        @media (max-width: 768px) {
            .error-box {
                padding: 30px 20px;
            }

            .error-code {
                font-size: 48px;
            }

            .error-title {
                font-size: 24px;
            }

            .error-actions {
                flex-direction: column;
                align-items: center;
            }

            .btn-error {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>

<body>
    <div class="error-container">
        <div class="error-box">
            <div class="error-icon <?php echo $erro_atual['cor'] === 'warning' ? 'text-warning' : 'text-danger'; ?>">
                <i class="<?php echo $erro_atual['icone']; ?>"></i>
            </div>

            <div class="error-code"><?php echo $erro_codigo; ?></div>

            <h1 class="error-title"><?php echo $erro_atual['titulo']; ?></h1>

            <p class="error-description">
                <?php echo $erro_mensagem ?: $erro_atual['descricao']; ?>
            </p>

            <?php if ($erro_codigo === '404'): ?>
                <div class="search-box">
                    <form method="GET" action="/">
                        <input
                            type="text"
                            name="search"
                            placeholder="Buscar no SIGEP..."
                            autocomplete="off">
                        <button type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
            <?php endif; ?>

            <div class="error-actions">
                <a href="<?php echo $erro_atual['acao_url']; ?>" class="btn-error btn-primary">
                    <i class="fas fa-<?php echo $erro_codigo === '403' ? 'sign-out-alt' : 'home'; ?>"></i>
                    <?php echo $erro_atual['acao_texto']; ?>
                </a>

                <?php if ($esta_autenticado): ?>
                    <a href="javascript:history.back()" class="btn-error btn-secondary">
                        <i class="fas fa-arrow-left"></i>
                        Voltar
                    </a>
                <?php endif; ?>
            </div>

            <?php if ($erro_mensagem): ?>
                <div class="error-details">
                    <strong>Detalhes do erro:</strong><br>
                    <?php echo htmlspecialchars($erro_mensagem); ?>
                </div>
            <?php endif; ?>

            <div class="error-details">
                <small>
                    <strong>Data/Hora:</strong> <?php echo date('d/m/Y H:i:s'); ?><br>
                    <strong>Código:</strong> <?php echo $erro_codigo; ?><br>
                    <strong>Referência:</strong> <?php echo $_SERVER['HTTP_REFERER'] ?? 'Acesso direto'; ?>
                </small>
            </div>
        </div>
    </div>

    <!-- AdminLTE 3 JS -->
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>

    <script>
        // Auto-redirecionamento para erros 503
        <?php if ($erro_codigo === '503'): ?>
            setTimeout(function() {
                window.location.href = '<?php echo $erro_redirect; ?>';
            }, 30000); // 30 segundos
        <?php endif; ?>

        // Animação adicional
        document.addEventListener('DOMContentLoaded', function() {
            const errorBox = document.querySelector('.error-box');
            errorBox.style.opacity = '0';
            errorBox.style.transform = 'translateY(20px)';

            setTimeout(function() {
                errorBox.style.transition = 'all 0.6s ease-out';
                errorBox.style.opacity = '1';
                errorBox.style.transform = 'translateY(0)';
            }, 100);
        });

        // Função para SPA
        function loadErrorPage(code, message, redirect) {
            if (typeof loadPage === 'function') {
                // Se estiver no SPA, carregar via AJAX
                $.ajax({
                    url: 'modulos/servicos/paginas_erro/erro_view.php',
                    method: 'GET',
                    data: {
                        codigo: code,
                        mensagem: message,
                        redirect: redirect
                    },
                    success: function(response) {
                        $('#content').html(response);
                    },
                    error: function() {
                        // Fallback: redirecionar
                        window.location.href = '/erro?codigo=' + code;
                    }
                });
            } else {
                // Fallback: redirecionar
                window.location.href = '/erro?codigo=' + code;
            }
        }
    </script>
</body>

</html>
