<?php
// Script de correção do banco - Módulo Manutenção
// Corrige inconsistência entre 'situacao' e 'status'

require_once __DIR__ . '/../../conf/db.php';

try {
    $pdo = new PDO("mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}", $config['user'], $config['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    echo "<h2>🔧 CORRIGINDO MÓDULO MANUTENÇÃO</h2>";

    // 1. Verificar se tabela existe
    echo "<h3>1. Verificando tabela manutencao_servicos...</h3>";
    $stmt = $pdo->query("SHOW TABLES LIKE 'manutencao_servicos'");
    $table_exists = $stmt->rowCount() > 0;

    if (!$table_exists) {
        echo "❌ Tabela não existe. Executando instalação completa...<br>";

        // Executar instalação completa
        include_once 'instalar_banco.php';
        exit;
    }

    echo "✅ Tabela existe<br>";

    // 2. Verificar estrutura atual
    echo "<h3>2. Verificando estrutura da tabela...</h3>";
    $stmt = $pdo->query("DESCRIBE manutencao_servicos");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $has_situacao = false;
    $has_status = false;

    foreach ($columns as $column) {
        if ($column['Field'] === 'situacao') $has_situacao = true;
        if ($column['Field'] === 'status') $has_status = true;
        echo "📋 Coluna: {$column['Field']} - {$column['Type']}<br>";
    }

    // 3. Corrigir se necessário
    if ($has_situacao && !$has_status) {
        echo "<h3>3. Renomeando coluna 'situacao' para 'status'...</h3>";
        $pdo->exec("ALTER TABLE manutencao_servicos CHANGE COLUMN situacao status ENUM('PENDENTE','EXECUTADO','CANCELADO') DEFAULT 'PENDENTE'");
        echo "✅ Coluna renomeada com sucesso!<br>";

        // Atualizar índice
        echo "<h3>4. Atualizando índices...</h3>";
        $pdo->exec("DROP INDEX IF EXISTS idx_situacao ON manutencao_servicos");
        $pdo->exec("CREATE INDEX idx_status ON manutencao_servicos(status)");
        echo "✅ Índices atualizados!<br>";

    } elseif ($has_status && !$has_situacao) {
        echo "<h3>3. Coluna 'status' já existe - OK!</h3>";

    } elseif ($has_situacao && $has_status) {
        echo "<h3>3. Ambas colunas existem - Removendo 'situacao'...</h3>";
        $pdo->exec("ALTER TABLE manutencao_servicos DROP COLUMN situacao");
        echo "✅ Coluna 'situacao' removida!<br>";

    } else {
        echo "<h3>3. Adicionando coluna 'status'...</h3>";
        $pdo->exec("ALTER TABLE manutencao_servicos ADD COLUMN status ENUM('PENDENTE','EXECUTADO','CANCELADO') DEFAULT 'PENDENTE' AFTER data_execucao");
        echo "✅ Coluna 'status' adicionada!<br>";
    }

    // 4. Verificar e corrigir ENUM de tipo_servico
    echo "<h3>4. Verificando ENUM de tipo_servico...</h3>";
    $stmt = $pdo->query("SELECT COLUMN_TYPE FROM information_schema.COLUMNS
                          WHERE TABLE_SCHEMA = '{$config['dbname']}'
                          AND TABLE_NAME = 'manutencao_servicos'
                          AND COLUMN_NAME = 'tipo_servico'");
    $result = $stmt->fetch();

    if ($result) {
        $enum_values = $result['COLUMN_TYPE'];
        echo "📋 ENUM atual: $enum_values<br>";

        // Verificar se está em maiúsculas
        if (strpos($enum_values, 'instalacao') !== false) {
            echo "🔧 Corrigindo ENUM para maiúsculas...<br>";
            $pdo->exec("ALTER TABLE manutencao_servicos
                        MODIFY COLUMN tipo_servico
                        ENUM('INSTALACAO','TROCA','MANUTENCAO','REPARO','REMOCAO') NOT NULL");
            echo "✅ ENUM corrigido para maiúsculas!<br>";
        } else {
            echo "✅ ENUM já está em maiúsculas<br>";
        }
    }

    // 5. Verificar ENUM de status
    echo "<h3>5. Verificando ENUM de status...</h3>";
    $stmt = $pdo->query("SELECT COLUMN_TYPE FROM information_schema.COLUMNS
                          WHERE TABLE_SCHEMA = '{$config['dbname']}'
                          AND TABLE_NAME = 'manutencao_servicos'
                          AND COLUMN_NAME = 'status'");
    $result = $stmt->fetch();

    if ($result) {
        $enum_values = $result['COLUMN_TYPE'];
        echo "📋 ENUM status atual: $enum_values<br>";

        // Verificar se está em maiúsculas
        if (strpos($enum_values, 'pendente') !== false) {
            echo "🔧 Corrigindo ENUM status para maiúsculas...<br>";
            $pdo->exec("ALTER TABLE manutencao_servicos
                        MODIFY COLUMN status
                        ENUM('PENDENTE','EXECUTADO','CANCELADO') DEFAULT 'PENDENTE'");
            echo "✅ ENUM status corrigido para maiúsculas!<br>";
        } else {
            echo "✅ ENUM status já está em maiúsculas<br>";
        }
    }

    // 5. Verificar e corrigir nomes de colunas de timestamp
    echo "<h3>5. Verificando colunas de timestamp...</h3>";

    // Verificar se existe criado_em e renomear para created_at
    $stmt = $pdo->query("SHOW COLUMNS FROM manutencao_servicos LIKE 'criado_em'");
    $has_criado_em = $stmt->rowCount() > 0;

    $stmt = $pdo->query("SHOW COLUMNS FROM manutencao_servicos LIKE 'created_at'");
    $has_created_at = $stmt->rowCount() > 0;

    if ($has_criado_em && !$has_created_at) {
        echo "🔧 Renomeando 'criado_em' para 'created_at'...<br>";
        $pdo->exec("ALTER TABLE manutencao_servicos CHANGE COLUMN criado_em created_at DATETIME DEFAULT CURRENT_TIMESTAMP");
        echo "✅ Coluna renomeada com sucesso!<br>";
    } elseif (!$has_criado_em && !$has_created_at) {
        echo "🔧 Adicionando coluna 'created_at'...<br>";
        $pdo->exec("ALTER TABLE manutencao_servicos ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP AFTER observacoes");
        echo "✅ Coluna 'created_at' adicionada com sucesso!<br>";
    } else {
        echo "✅ Coluna 'created_at' já existe<br>";
    }

    // Verificar se existe atualizado_em e renomear para updated_at
    $stmt = $pdo->query("SHOW COLUMNS FROM manutencao_servicos LIKE 'atualizado_em'");
    $has_atualizado_em = $stmt->rowCount() > 0;

    $stmt = $pdo->query("SHOW COLUMNS FROM manutencao_servicos LIKE 'updated_at'");
    $has_updated_at = $stmt->rowCount() > 0;

    if ($has_atualizado_em && !$has_updated_at) {
        echo "🔧 Renomeando 'atualizado_em' para 'updated_at'...<br>";
        $pdo->exec("ALTER TABLE manutencao_servicos CHANGE COLUMN atualizado_em updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
        echo "✅ Coluna renomeada com sucesso!<br>";
    } elseif (!$has_atualizado_em && !$has_updated_at) {
        echo "🔧 Adicionando coluna 'updated_at'...<br>";
        $pdo->exec("ALTER TABLE manutencao_servicos ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at");
        echo "✅ Coluna 'updated_at' adicionada com sucesso!<br>";
    } else {
        echo "✅ Coluna 'updated_at' já existe<br>";
    }

    // Remover colunas duplicadas se existirem
    if ($has_criado_em && $has_created_at) {
        echo "🔧 Removendo coluna duplicada 'criado_em'...<br>";
        $pdo->exec("ALTER TABLE manutencao_servicos DROP COLUMN criado_em");
        echo "✅ Coluna 'criado_em' removida!<br>";
    }

    if ($has_atualizado_em && $has_updated_at) {
        echo "🔧 Removendo coluna duplicada 'atualizado_em'...<br>";
        $pdo->exec("ALTER TABLE manutencao_servicos DROP COLUMN atualizado_em");
        echo "✅ Coluna 'atualizado_em' removida!<br>";
    }
    // 6. Verificar estrutura final
    echo "<h3>6. Estrutura final da tabela:</h3>";
    $stmt = $pdo->query("DESCRIBE manutencao_servicos");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Coluna</th><th>Tipo</th><th>Nulo</th><th>Chave</th><th>Default</th></tr>";

    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";

    // 7. Verificar índices
    echo "<h3>7. Índices da tabela:</h3>";
    $stmt = $pdo->query("SHOW INDEX FROM manutencao_servicos");
    $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Tabela</th><th>Non_unique</th><th>Key_name</th><th>Seq_in_index</th><th>Column_name</th></tr>";

    foreach ($indexes as $index) {
        echo "<tr>";
        echo "<td>{$index['Table']}</td>";
        echo "<td>{$index['Non_unique']}</td>";
        echo "<td>{$index['Key_name']}</td>";
        echo "<td>{$index['Seq_in_index']}</td>";
        echo "<td>{$index['Column_name']}</td>";
        echo "</tr>";
    }
    echo "</table>";

    // 8. Testar query do módulo
    echo "<h3>8. Testando query principal do módulo...</h3>";
    try {
        $sql = "
            SELECT
                ms.id,
                ms.id_eletronico,
                ms.tipo_servico,
                ms.cela_destino,
                ms.data_solicitacao,
                ms.data_execucao,
                ms.status,
                ms.usuario_solicitante,
                ms.usuario_executante,
                ms.observacoes,
                ms.created_at,
                ie.tipo_item,
                ie.marca_modelo,
                ie.cor as cor_eletronico,
                COALESCE(i.nome, i.nome_social, 'Sem Nome') as nome_interno,
                i.ala,
                i.galeria,
                i.bloco,
                COALESCE(d.nome, d.nome_social, 'Sem nome') as nome_dono
            FROM manutencao_servicos ms
            LEFT JOIN internos_eletronicos ie ON ms.id_eletronico = ie.id
            LEFT JOIN internos i ON ie.id_interno = i.ipen
            LEFT JOIN internos d ON ie.id_dono = d.ipen
            WHERE 1=1
            LIMIT 5
        ";

        $stmt = $pdo->query($sql);
        $resultados = $stmt->fetchAll();

        echo "✅ Query executada com sucesso!<br>";
        echo "📊 Registros encontrados: " . count($resultados) . "<br>";

        if (count($resultados) > 0) {
            echo "<table border='1' style='border-collapse: collapse; width: 100%; margin-top: 10px;'>";
            echo "<tr><th>ID</th><th>Tipo</th><th>Status</th><th>Data Solicitação</th></tr>";

            foreach ($resultados as $row) {
                echo "<tr>";
                echo "<td>{$row['id']}</td>";
                echo "<td>{$row['tipo_servico']}</td>";
                echo "<td><span style='background: #007bff; color: white; padding: 2px 6px; border-radius: 3px;'>{$row['status']}</span></td>";
                echo "<td>" . date('d/m/Y H:i', strtotime($row['data_solicitacao'])) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }

    } catch (Exception $e) {
        echo "❌ Erro na query: " . $e->getMessage() . "<br>";
    }

    echo "<h3>✅ CORREÇÃO CONCLUÍDA COM SUCESSO!</h3>";
    echo "<p><a href='manutencao_view.php'>📋 Acessar Módulo de Manutenção</a></p>";

} catch (Exception $e) {
    echo "❌ Erro geral: " . $e->getMessage() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
