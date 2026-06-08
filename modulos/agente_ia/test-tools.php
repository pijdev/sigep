<?php
/**
 * Arquivo de teste para as InternosTools
 * Execute: php modulos/agente_ia/test-tools.php
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/InternosTools.php';

use Config\Database;
use AgentIA\InternosTools;

echo "=== Teste das InternosTools ===\n\n";

try {
    $pdo = Database::getConnection();
    $tools = new InternosTools($pdo);
    
    echo "✓ Conexão estabelecida com sucesso\n\n";
    
    // Teste 1: Listar situações disponíveis
    echo "--- Teste 1: Listar situações disponíveis ---\n";
    echo $tools->listar_situacoes();
    echo "\n\n";
    
    // Teste 2: Buscar um interno por IPEN (será necessário usar um IPEN válido)
    echo "--- Teste 2: Buscar por IPEN ---\n";
    echo "Para testar, use: \$tools->buscar_por_ipen('SEU_IPEN_AQUI');\n\n";
    
    // Teste 3: Contar internos em uma cela
    echo "--- Teste 3: Contar internos em cela S-A-1 ---\n";
    echo $tools->contar_cela('S', 'A', '1');
    echo "\n\n";
    
    // Teste 4: Listar internos em uma cela
    echo "--- Teste 4: Listar internos em cela S-A-1 ---\n";
    echo $tools->internos_cela('S', 'A', '1');
    echo "\n\n";
    
    // Teste 5: Listar internos por situação
    echo "--- Teste 5: Listar internos em portaria ---\n";
    echo $tools->internos_por_situacao('PORTARIA');
    echo "\n\n";
    
    echo "=== Testes concluídos ===\n";

} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
