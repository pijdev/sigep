<?php
// includes/index_logica_teste.php
// Versão de teste para diagnosticar o erro 500

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/index_teste.log');

echo "<h1>TESTE DO index_logica.php</h1>";

try {
    echo "<h2>1. Testando session_start()</h2>";
    session_start();
    echo "✅ Session iniciada com sucesso<br>";
    
    echo "<h2>2. Testando headers</h2>";
    header("Cache-Control: no-cache, no-store, must-revalidate");
    header("Pragma: no-cache");
    header("Expires: 0");
    echo "✅ Headers enviados com sucesso<br>";
    
    echo "<h2>3. Verificando sessão</h2>";
    if (!isset($_SESSION['user_id'])) {
        echo "❌ Sessão não encontrada, redirecionando para autenticação<br>";
        header("Location: /autenticacao");
        exit;
    } else {
        echo "✅ Sessão encontrada: User ID = " . $_SESSION['user_id'] . "<br>";
    }
    
    echo "<h2>4. Testando variáveis de sessão</h2>";
    $isKioskMode = isset($_SESSION['kiosk_mode']) && (int)$_SESSION['kiosk_mode'] === 1;
    echo "Kiosk Mode: " . ($isKioskMode ? 'SIM' : 'NÃO') . "<br>";
    
    echo "<h2>5. Testando inatividade</h2>";
    if (!$isKioskMode && isset($_SESSION['ultimo_clique'])) {
        $inatividade = time() - $_SESSION['ultimo_clique'];
        echo "Inatividade: $inatividade segundos<br>";
        
        if ($inatividade > 600) {
            echo "⚠️ Sessão expirada por inatividade<br>";
            
            $usuario_id = $_SESSION['user_id'] ?? null;
            $usuario_nome = $_SESSION['user_nome'] ?? null;
            echo "Usuário: $usuario_id - $usuario_nome<br>";
            
            // Testar se o arquivo security_functions.php existe
            $securityFile = __DIR__ . '/../auth/security_functions.php';
            if (file_exists($securityFile)) {
                echo "✅ Arquivo security_functions.php encontrado<br>";
                
                require_once $securityFile;
                
                // Testar se a função existe
                if (function_exists('registrarAuditoria')) {
                    echo "✅ Função registrarAuditoria encontrada<br>";
                    
                    // Testar configuração do banco
                    $configFile = __DIR__ . '/../conf/db.php';
                    if (file_exists($configFile)) {
                        echo "✅ Arquivo de configuração encontrado<br>";
                        
                        $config = require $configFile;
                        echo "✅ Configuração carregada<br>";
                        
                        // Testar conexão
                        try {
                            $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
                            $pdo = new PDO($dsn, $config['user'], $config['pass'], [
                                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                            ]);
                            echo "✅ Conexão PDO estabelecida<br>";
                            
                            // Testar a função de auditoria
                            $result = registrarAuditoria($pdo, 'sessao_expirou', $usuario_id, $usuario_nome, [
                                'inatividade_segundos' => $inatividade
                            ]);
                            
                            if ($result) {
                                echo "✅ Auditoria registrada com sucesso<br>";
                            } else {
                                echo "❌ Falha ao registrar auditoria<br>";
                            }
                            
                        } catch (PDOException $e) {
                            echo "❌ Erro na conexão PDO: " . $e->getMessage() . "<br>";
                        }
                        
                    } else {
                        echo "❌ Arquivo de configuração NÃO encontrado<br>";
                    }
                    
                } else {
                    echo "❌ Função registrarAuditoria NÃO encontrada<br>";
                }
                
            } else {
                echo "❌ Arquivo security_functions.php NÃO encontrado<br>";
            }
            
        } else {
            echo "✅ Sessão ativa<br>";
        }
    } else {
        echo "ℹ️ Sem registro de último clique<br>";
    }
    
    echo "<h2>6. Atualizando último clique</h2>";
    $_SESSION['ultimo_clique'] = time();
    echo "✅ Último clique atualizado<br>";
    
    echo "<h2>7. Testando conexão principal</h2>";
    try {
        $config = require __DIR__ . '/../conf/db.php';
        $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
        $pdo = new PDO($dsn, $config['user'], $config['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        echo "✅ Conexão principal estabelecida<br>";
    } catch (PDOException $e) {
        echo "❌ Erro na conexão principal: " . $e->getMessage() . "<br>";
    }
    
    echo "<h2>✅ TESTE CONCLUÍDO COM SUCESSO!</h2>";
    echo "<p>O index_logica.php está funcionando corretamente!</p>";
    
} catch (Exception $e) {
    echo "<h2>❌ ERRO CAPTURADO</h2>";
    echo "<p><strong>Erro:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Arquivo:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Linha:</strong> " . $e->getLine() . "</p>";
    echo "<p><strong>Trace:</strong> <pre>" . $e->getTraceAsString() . "</pre></p>";
}

echo "<hr>";
echo "<p><em>Log detalhado em: " . __DIR__ . "/../logs/index_teste.log</em></p>";
?>