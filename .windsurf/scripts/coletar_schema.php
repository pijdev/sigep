<?php
/**
 * Coletor Automático de Schema SIGEP
 * Executado pelo workflow ArquitetoSIGEP
 */

function coletarSchemaCompleto() {
    $config = require __DIR__ . '/../../conf/db.php';
    
    try {
        $pdo = new PDO(
            "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}",
            $config['user'], 
            $config['pass'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        // 1. Listar tabelas
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        
        $schema = "# 🗄️ Schema Completo SIGEP\n";
        $schema .= "> Gerado em: " . date('Y-m-d H:i:s') . "\n\n";
        $schema .= "## 🔧 Estrutura Completa\n\n";
        
        // 2. Para cada tabela
        foreach ($tables as $table) {
            $create = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch();
            $schema .= "```sql\n{$create['Create Table']};\n```\n\n";
            
            // Índices
            $indexes = $pdo->query("SHOW INDEX FROM `{$table}`")->fetchAll();
            if (!empty($indexes)) {
                $schema .= "### Índices da tabela {$table}\n";
                $schema .= "```sql\n";
                foreach ($indexes as $index) {
                    $schema .= "-- {$index['Key_name']}: {$index['Column_name']} ({$index['Index_type']})\n";
                }
                $schema .= "```\n\n";
            }
        }
        
        // 3. Triggers
        $triggers = $pdo->query("SHOW TRIGGERS")->fetchAll();
        if (!empty($triggers)) {
            $schema .= "## 🔒 Triggers\n\n";
            foreach ($triggers as $trigger) {
                $schema .= "```sql\n{$trigger['SQL Original Statement']}\n```\n\n";
            }
        }
        
        // 4. Salvar arquivo
        file_put_contents(
            __DIR__ . '/../architecture/database/schema_completo.md', 
            $schema
        );
        
        return "✅ Schema SIGEP 100% completo atualizado";
        
    } catch (Exception $e) {
        return "❌ Erro: " . $e->getMessage();
    }
}

echo coletarSchemaCompleto();
