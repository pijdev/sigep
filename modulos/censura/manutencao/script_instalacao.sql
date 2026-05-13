-- =====================================================
-- SCRIPT SQL - MÓDULO CONTROLE DE MANUTENÇÕES
-- =====================================================
-- Execute este script manualmente no banco de dados SIGEP

-- 1. Adicionar 'Chuveiro' ao ENUM de tipo_item
ALTER TABLE internos_eletronicos
MODIFY COLUMN tipo_item ENUM('TV','Radio','Ventilador','Maquina Cabelo','Extensao','Chaleira','Bola','Banqueta','Cabo Antena','Antena Digital','Violao','Chuveiro','Outros');

-- 2. Criar tabela principal de serviços
CREATE TABLE manutencao_servicos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_eletronico INT NOT NULL,
    tipo_servico ENUM('INSTALACAO','TROCA','MANUTENCAO','REPARO','REMOCAO') NOT NULL,
    cela_destino VARCHAR(20) NOT NULL,
    data_solicitacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    data_execucao DATETIME NULL,
    status ENUM('PENDENTE','EXECUTADO','CANCELADO') DEFAULT 'PENDENTE',
    usuario_solicitante VARCHAR(100) NOT NULL,
    usuario_executante VARCHAR(100) NULL,
    observacoes TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (id_eletronico) REFERENCES internos_eletronicos(id),
    INDEX idx_status (status),
    INDEX idx_data_execucao (data_execucao),
    INDEX idx_cela_destino (cela_destino),
    INDEX idx_id_eletronico (id_eletronico)
);

-- 3. Criar tabela de auditoria completa
CREATE TABLE manutencao_servicos_auditoria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_servico INT NOT NULL,
    acao ENUM('CRIADO','EXECUTADO','CANCELADO','ALTERADO') NOT NULL,
    usuario_execucao VARCHAR(100) NOT NULL,
    data_acao DATETIME DEFAULT CURRENT_TIMESTAMP,
    detalhes_anteriores TEXT NULL,
    detalhes_novos TEXT NULL,
    ip_acesso VARCHAR(45) NULL,

    FOREIGN KEY (id_servico) REFERENCES manutencao_servicos(id),
    INDEX idx_acao (acao),
    INDEX idx_data_acao (data_acao),
    INDEX idx_id_servico (id_servico)
);

-- 4. Inserir primeiro serviço de exemplo (opcional)
-- INSERT INTO manutencao_servicos (id_eletronico, tipo_servico, cela_destino, usuario_solicitante, observacoes)
-- VALUES (1, 'INSTALACAO', 'SE-3', 'admin', 'Serviço de exemplo para teste');

-- =====================================================
-- SCRIPT CONCLUÍDO
-- =====================================================
