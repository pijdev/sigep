<?php
// temp/create_channel_tables.php
// Script para criar tabelas do sistema de canais de notificações

$config = require __DIR__ . '/../conf/db.php';
$pdo = new PDO("mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}",
               $config['user'], $config['pass'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

echo "Criando tabelas do sistema de canais de notificações...\n";

// Tabela de canais
$sql1 = "
CREATE TABLE IF NOT EXISTS notificacao_canais (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    ativo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_nome (nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

$pdo->exec($sql1);
echo "✓ Tabela notificacao_canais criada\n";

// Tabela de inscrições nos canais
$sql2 = "
CREATE TABLE IF NOT EXISTS notificacao_canal_inscricoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    canal_id INT NOT NULL,
    tipo ENUM('user', 'setor') NOT NULL,
    identificador VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (canal_id) REFERENCES notificacao_canais(id) ON DELETE CASCADE,
    UNIQUE KEY unique_inscricao (canal_id, tipo, identificador)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

$pdo->exec($sql2);
echo "✓ Tabela notificacao_canal_inscricoes criada\n";

// Tabela de tipos de notificação por canal
$sql3 = "
CREATE TABLE IF NOT EXISTS notificacao_canal_tipos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    canal_id INT NOT NULL,
    tipo_notificacao VARCHAR(50) NOT NULL,
    ativo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (canal_id) REFERENCES notificacao_canais(id) ON DELETE CASCADE,
    UNIQUE KEY unique_canal_tipo (canal_id, tipo_notificacao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

$pdo->exec($sql3);
echo "✓ Tabela notificacao_canal_tipos criada\n";

// Inserir canal padrão para agendador de tarefas
$stmt = $pdo->prepare("INSERT IGNORE INTO notificacao_canais (nome, descricao) VALUES (?, ?)");
$stmt->execute(['agendador_tarefas', 'Canal para notificações do agendador de tarefas (execução, erros, atrasos)']);
$canalId = $pdo->lastInsertId();

if ($canalId) {
    echo "✓ Canal 'agendador_tarefas' criado\n";

    // Adicionar tipos de notificação para o canal
    $tipos = ['executado', 'erro', 'atrasado', 'cancelado'];
    $stmtTipo = $pdo->prepare("INSERT IGNORE INTO notificacao_canal_tipos (canal_id, tipo_notificacao) VALUES (?, ?)");
    foreach ($tipos as $tipo) {
        $stmtTipo->execute([$canalId, $tipo]);
    }
    echo "✓ Tipos de notificação adicionados ao canal\n";
} else {
    echo "ℹ Canal 'agendador_tarefas' já existe\n";
}

echo "✅ Sistema de canais de notificações criado com sucesso!\n";
?>
