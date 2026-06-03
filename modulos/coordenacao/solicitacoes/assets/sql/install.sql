-- Criação das tabelas necessárias para o módulo de solicitações de internos
-- Atualizado para suportar Kanban estilo Microsoft Planner

CREATE TABLE IF NOT EXISTS sectors (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(120) NOT NULL UNIQUE,
    nome VARCHAR(255) NOT NULL,
    ativo CHAR(1) NOT NULL DEFAULT 'S',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS internos_solicitacoes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_interno INT UNSIGNED NOT NULL,
    ipen INT UNSIGNED NOT NULL,
    nome_interno VARCHAR(255) NOT NULL,
    nome_social VARCHAR(255) NULL,
    galeria VARCHAR(64) NULL,
    bloco VARCHAR(64) NULL,
    res VARCHAR(255) NULL,
    setor_destino VARCHAR(255) NULL,
    descricao TEXT NOT NULL,
    tarefas JSON NULL,
    data_limite DATE NULL,
    categoria VARCHAR(100) NULL,
    prioridade ENUM('Baixa','Média','Alta','Urgente') DEFAULT 'Média',
    responsavel_nome VARCHAR(150) NULL,
    responsavel_foto VARCHAR(255) NULL,
    status ENUM('Pendentes','Em Atendimento','Aguardando','Atendidas','Canceladas') NOT NULL DEFAULT 'Pendentes',
    criado_por VARCHAR(150) NULL,
    atualizado_por VARCHAR(150) NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    ativo CHAR(1) NOT NULL DEFAULT 'S',
    INDEX (status),
    INDEX (id_interno),
    INDEX (setor_destino),
    INDEX (categoria),
    INDEX (prioridade),
    INDEX (data_limite)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS internos_solicitacoes_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    solicitacao_id BIGINT UNSIGNED NOT NULL,
    acao ENUM('Criado','Atualizado','Status','Resposta','Tarefa') NOT NULL DEFAULT 'Atualizado',
    descricao TEXT NULL,
    dados_anteriores JSON NULL,
    dados_novos JSON NULL,
    usuario VARCHAR(150) NULL,
    setor_usuario VARCHAR(150) NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (solicitacao_id),
    INDEX (acao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Script para adicionar novos campos em tabelas existentes (executar se tabela já existe)
ALTER TABLE internos_solicitacoes 
ADD COLUMN IF NOT EXISTS data_limite DATE NULL AFTER tarefas,
ADD COLUMN IF NOT EXISTS categoria VARCHAR(100) NULL AFTER data_limite,
ADD COLUMN IF NOT EXISTS prioridade ENUM('Baixa','Média','Alta','Urgente') DEFAULT 'Média' AFTER categoria,
ADD COLUMN IF NOT EXISTS responsavel_nome VARCHAR(150) NULL AFTER prioridade,
ADD COLUMN IF NOT EXISTS responsavel_foto VARCHAR(255) NULL AFTER responsavel_nome,
ADD INDEX IF NOT EXISTS idx_categoria (categoria),
ADD INDEX IF NOT EXISTS idx_prioridade (prioridade),
ADD INDEX IF NOT EXISTS idx_data_limite (data_limite);
