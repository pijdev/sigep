<?php
// Script de instalação do banco de dados - Módulo Manutenção
// Executar via: http://sigep.pij.local/modulos/censura/manutencao/instalar_banco.php

require_once __DIR__ . '/../../conf/db.php';

try {
    $pdo = new PDO("mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}", $config['user'], $config['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    echo "<h2>🔧 Instalando Módulo Controle de Manutenções</h2>";

    // 1. Adicionar 'Chuveiro' ao ENUM
    echo "<h3>1. Atualizando ENUM tipo_item...</h3>";
    try {
        $sql = "ALTER TABLE internos_eletronicos
                MODIFY COLUMN tipo_item ENUM(
                    'TV','Radio','Ventilador','Chaleira','Maquina Cabelo',
                    'Extensao','Cabo Antena','Antena Digital','Bola','Banqueta',
                    'Violao','Outros','Chuveiro'
                ) NOT NULL";

        $pdo->exec($sql);
        echo "✅ ENUM atualizado com sucesso!<br>";
    } catch (Exception $e) {
        echo "❌ Erro ao atualizar ENUM: " . $e->getMessage() . "<br>";
    }

    // 2. Criar tabela manutencao_servicos
    echo "<h3>2. Criando tabela manutencao_servicos...</h3>";
    try {
        $sql = "CREATE TABLE IF NOT EXISTS manutencao_servicos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            id_eletronico INT NOT NULL,
            id_interno INT,
            tipo_servico ENUM('INSTALACAO','TROCA','MANUTENCAO','REPARO','REMOCAO') NOT NULL,
            data_solicitacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            data_execucao DATETIME,
            usuario_solicitante INT NOT NULL,
            usuario_executante INT,
            status ENUM('PENDENTE','EXECUTADO','CANCELADO') NOT NULL DEFAULT 'PENDENTE',
            observacoes TEXT,
            cela_destino VARCHAR(20),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

            INDEX idx_id_eletronico (id_eletronico),
            INDEX idx_id_interno (id_interno),
            INDEX idx_status (status),
            INDEX idx_data_solicitacao (data_solicitacao),
            INDEX idx_tipo_servico (tipo_servico)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $pdo->exec($sql);
        echo "✅ Tabela manutencao_servicos criada com sucesso!<br>";
    } catch (Exception $e) {
        echo "❌ Erro ao criar tabela manutencao_servicos: " . $e->getMessage() . "<br>";
    }

    // 3. Criar tabela manutencao_servicos_auditoria
    echo "<h3>3. Criando tabela manutencao_servicos_auditoria...</h3>";
    try {
        $sql = "CREATE TABLE IF NOT EXISTS manutencao_servicos_auditoria (
            id INT AUTO_INCREMENT PRIMARY KEY,
            id_servico INT NOT NULL,
            data_acao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            usuario_acao INT NOT NULL,
            tipo_acao ENUM('criacao','execucao','cancelamento','alteracao') NOT NULL,
            campo_alterado VARCHAR(50),
            valor_antigo TEXT,
            valor_novo TEXT,
            observacoes TEXT,
            ip_usuario VARCHAR(45),

            INDEX idx_id_servico (id_servico),
            INDEX idx_data_acao (data_acao),
            INDEX idx_usuario_acao (usuario_acao),
            INDEX idx_tipo_acao (tipo_acao)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $pdo->exec($sql);
        echo "✅ Tabela manutencao_servicos_auditoria criada com sucesso!<br>";
    } catch (Exception $e) {
        echo "❌ Erro ao criar tabela manutencao_servicos_auditoria: " . $e->getMessage() . "<br>";
    }

    // 4. Verificar instalação
    echo "<h3>4. Verificando instalação...</h3>";

    // Verificar ENUM
    $stmt = $pdo->query("SELECT COLUMN_TYPE FROM information_schema.COLUMNS
                          WHERE TABLE_SCHEMA = 'sigep_producao'
                          AND TABLE_NAME = 'internos_eletronicos'
                          AND COLUMN_NAME = 'tipo_item'");
    $result = $stmt->fetch();
    echo "📋 ENUM atual: " . $result['COLUMN_TYPE'] . "<br>";

    // Verificar tabelas
    $stmt = $pdo->query("SELECT TABLE_NAME, TABLE_ROWS
                          FROM information_schema.TABLES
                          WHERE TABLE_SCHEMA = 'sigep_producao'
                          AND TABLE_NAME LIKE 'manutencao%'");

    while ($row = $stmt->fetch()) {
        echo "📋 Tabela " . $row['TABLE_NAME'] . ": " . $row['TABLE_ROWS'] . " registros<br>";
    }

    echo "<h2>🎉 Instalação concluída!</h2>";
    echo "<p><a href='/censura/manutencao/'>Acessar Módulo de Manutenção</a></p>";
    echo "<p><a href='/paginas/internos_recebimento_eletronicos.php'>Cadastrar Chuveiro em Entrada de Eletrônicos</a></p>";

} catch (Exception $e) {
    echo "❌ Erro na conexão: " . $e->getMessage();
}
?>
