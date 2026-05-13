-- =====================================================
-- REESTRUTURAÇÃO COMPLETA DA TABELA DE AUDITORIA
-- Módulo: Controle de Dívidas
-- Autor: SIGEP Development Team
-- Data: 2026-04-08
-- =====================================================

-- 1. Backup da tabela atual (caso exista algum dado)
CREATE TABLE IF NOT EXISTS laboral_controle_dividas_historico_backup LIKE laboral_controle_dividas_historico;

INSERT INTO
    laboral_controle_dividas_historico_backup
SELECT *
FROM
    laboral_controle_dividas_historico;

-- 2. Remover tabela atual
DROP TABLE IF EXISTS laboral_controle_dividas_historico;

-- 3. Criar nova estrutura otimizada para auditoria
CREATE TABLE laboral_controle_dividas_historico (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

-- Dados da dívida relacionada
multa_id BIGINT UNSIGNED NOT NULL,
ipen INT NOT NULL,
tipo_divida VARCHAR(50) NOT NULL,

-- Dados do movimento/alteração
tipo_acao ENUM(
    'INSERT',
    'UPDATE',
    'DELETE',
    'LANCAMENTO',
    'STATUS_CHANGE',
    'CORRECAO'
) NOT NULL,
tabela_origem VARCHAR(50) NOT NULL, -- 'laboral_controle_dividas' ou 'laboral_controle_dividas_descontos'
registro_origem_id BIGINT UNSIGNED, -- ID do registro na tabela de origem

-- Dados antes e depois da alteração (para auditoria completa)
dados_antes JSON, -- Dados completos antes da alteração
dados_depois JSON, -- Dados completos depois da alteração

-- Campos específicos para lançamentos
mes_referencia VARCHAR(7), -- YYYY-MM
valor_movimento DECIMAL(10, 2), -- Valor da movimentação
saldo_anterior DECIMAL(10, 2), -- Saldo antes do movimento
saldo_novo DECIMAL(10, 2), -- Saldo depois do movimento

-- Metadados da auditoria
descricao TEXT, -- Descrição detalhada da ação
usuario_responsavel INT NOT NULL, -- ID do usuário que realizou a ação
data_acao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
ip_address VARCHAR(45), -- IP do usuário
user_agent TEXT, -- Browser/cliente usado

-- Controle de sessão
session_id VARCHAR(255), -- ID da sessão PHP

-- Índices para performance
PRIMARY KEY (id),
INDEX idx_multa_id (multa_id),
INDEX idx_ipen (ipen),
INDEX idx_tipo_acao (tipo_acao),
INDEX idx_tabela_origem (tabela_origem),
INDEX idx_registro_origem (registro_origem_id),
INDEX idx_data_acao (data_acao),
INDEX idx_usuario_responsavel (usuario_responsavel),
INDEX idx_mes_referencia (mes_referencia),

-- Chave estrangeira para a dívida
FOREIGN KEY (multa_id) REFERENCES laboral_controle_dividas (id) ON DELETE CASCADE,

-- Chave estrangeira para o usuário
FOREIGN KEY (usuario_responsavel) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Triggers automáticos para auditoria

-- Trigger para INSERT em laboral_controle_dividas
DELIMITER /
/

CREATE TRIGGER tr_laboral_controle_dividas_insert
AFTER INSERT ON laboral_controle_dividas
FOR EACH ROW
BEGIN
    INSERT INTO laboral_controle_dividas_historico (
        multa_id, ipen, tipo_divida, tipo_acao, tabela_origem, registro_origem_id,
        dados_depois, descricao, usuario_responsavel, ip_address, user_agent
    ) VALUES (
        NEW.id, NEW.ipen, NEW.tipo_divida, 'INSERT', 'laboral_controle_dividas', NEW.id,
        JSON_OBJECT(
            'id', NEW.id,
            'ipen', NEW.ipen,
            'cpf', NEW.cpf,
            'autos', NEW.autos,
            'tipo_divida', NEW.tipo_divida,
            'valor_divida', NEW.valor_divida,
            'salario_base', NEW.salario_base,
            'valor_atual', NEW.valor_atual,
            'percentual_desconto', NEW.percentual_desconto,
            'status', NEW.status,
            'status_detalhado', NEW.status_detalhado,
            'data_cadastro', NEW.data_cadastro,
            'pensao_favorecido', NEW.pensao_favorecido,
            'pensao_banco', NEW.pensao_banco,
            'pensao_agencia', NEW.pensao_agencia,
            'pensao_conta', NEW.pensao_conta,
            'pensao_op', NEW.pensao_op,
            'pensao_tipo_conta', NEW.pensao_tipo_conta,
            'pensao_determinacao', NEW.pensao_determinacao,
            'usuario_cadastro', NEW.usuario_cadastro
        ),
        CONCAT('Nova dívida cadastrada: ', NEW.tipo_divida, ' - IPEN: ', NEW.ipen),
        NEW.usuario_cadastro,
        CONNECTION_ID(),
        SYSTEM_USER()
    );
END
/
/

DELIMITER;

-- Trigger para UPDATE em laboral_controle_dividas
DELIMITER /
/

CREATE TRIGGER tr_laboral_controle_dividas_update
AFTER UPDATE ON laboral_controle_dividas
FOR EACH ROW
BEGIN
    DECLARE mudancas TEXT;

    -- Detectar campos alterados
    SET mudancas = JSON_OBJECT(
        'campos_alterados', JSON_OBJECT(
            IF(OLD.valor_divida != NEW.valor_divida, 'valor_divida', NULL),
            IF(OLD.valor_divida != NEW.valor_divida, JSON_OBJECT('antes', OLD.valor_divida, 'depois', NEW.valor_divida), NULL),

            IF(OLD.valor_atual != NEW.valor_atual, 'valor_atual', NULL),
            IF(OLD.valor_atual != NEW.valor_atual, JSON_OBJECT('antes', OLD.valor_atual, 'depois', NEW.valor_atual), NULL),

            IF(OLD.status != NEW.status, 'status', NULL),
            IF(OLD.status != NEW.status, JSON_OBJECT('antes', OLD.status, 'depois', NEW.status), NULL),

            IF(OLD.status_detalhado != NEW.status_detalhado, 'status_detalhado', NULL),
            IF(OLD.status_detalhado != NEW.status_detalhado, JSON_OBJECT('antes', OLD.status_detalhado, 'depois', NEW.status_detalhado), NULL),

            IF(OLD.percentual_desconto != NEW.percentual_desconto, 'percentual_desconto', NULL),
            IF(OLD.percentual_desconto != NEW.percentual_desconto, JSON_OBJECT('antes', OLD.percentual_desconto, 'depois', NEW.percentual_desconto), NULL)
        )
    );

    INSERT INTO laboral_controle_dividas_historico (
        multa_id, ipen, tipo_divida, tipo_acao, tabela_origem, registro_origem_id,
        dados_antes, dados_depois, descricao, usuario_responsavel, data_acao
    ) VALUES (
        NEW.id, NEW.ipen, NEW.tipo_divida, 'UPDATE', 'laboral_controle_dividas', NEW.id,
        JSON_OBJECT(
            'id', OLD.id,
            'ipen', OLD.ipen,
            'valor_divida', OLD.valor_divida,
            'valor_atual', OLD.valor_atual,
            'status', OLD.status,
            'status_detalhado', OLD.status_detalhado,
            'percentual_desconto', OLD.percentual_desconto,
            'data_alterado', OLD.data_alterado,
            'usuario_alterado', OLD.usuario_alterado
        ),
        JSON_OBJECT(
            'id', NEW.id,
            'ipen', NEW.ipen,
            'valor_divida', NEW.valor_divida,
            'valor_atual', NEW.valor_atual,
            'status', NEW.status,
            'status_detalhado', NEW.status_detalhado,
            'percentual_desconto', NEW.percentual_desconto,
            'data_alterado', NEW.data_alterado,
            'usuario_alterado', NEW.usuario_alterado
        ),
        CONCAT('Dívida atualizada: ', NEW.tipo_divida, ' - IPEN: ', NEW.ipen, ' - Mudanças: ', mudancas),
        NEW.usuario_alterado,
        NOW()
    );
END
/
/

DELIMITER;

-- Trigger para DELETE em laboral_controle_dividas
DELIMITER /
/

CREATE TRIGGER tr_laboral_controle_dividas_delete
AFTER DELETE ON laboral_controle_dividas
FOR EACH ROW
BEGIN
    INSERT INTO laboral_controle_dividas_historico (
        multa_id, ipen, tipo_divida, tipo_acao, tabela_origem, registro_origem_id,
        dados_antes, descricao, usuario_responsavel
    ) VALUES (
        OLD.id, OLD.ipen, OLD.tipo_divida, 'DELETE', 'laboral_controle_dividas', OLD.id,
        JSON_OBJECT(
            'id', OLD.id,
            'ipen', OLD.ipen,
            'cpf', OLD.cpf,
            'autos', OLD.autos,
            'tipo_divida', OLD.tipo_divida,
            'valor_divida', OLD.valor_divida,
            'salario_base', OLD.salario_base,
            'valor_atual', OLD.valor_atual,
            'percentual_desconto', OLD.percentual_desconto,
            'status', OLD.status,
            'status_detalhado', OLD.status_detalhado,
            'data_cadastro', OLD.data_cadastro,
            'data_alterado', OLD.data_alterado,
            'data_ultima_atualizacao', OLD.data_ultima_atualizacao,
            'pensao_favorecido', OLD.pensao_favorecido,
            'pensao_banco', OLD.pensao_banco,
            'pensao_agencia', OLD.pensao_agencia,
            'pensao_conta', OLD.pensao_conta,
            'pensao_op', OLD.pensao_op,
            'pensao_tipo_conta', OLD.pensao_tipo_conta,
            'pensao_determinacao', OLD.pensao_determinacao,
            'usuario_cadastro', OLD.usuario_cadastro,
            'usuario_alterado', OLD.usuario_alterado
        ),
        CONCAT('Dívida excluída: ', OLD.tipo_divida, ' - IPEN: ', OLD.ipen),
        @current_user_id
    );
END
/
/

DELIMITER;

-- 5. Triggers para auditoria de lançamentos (descontos)

-- Trigger para INSERT em laboral_controle_dividas_descontos
DELIMITER /
/

CREATE TRIGGER tr_laboral_controle_dividas_descontos_insert
AFTER INSERT ON laboral_controle_dividas_descontos
FOR EACH ROW
BEGIN
    INSERT INTO laboral_controle_dividas_historico (
        multa_id, ipen, tipo_acao, tabela_origem, registro_origem_id,
        mes_referencia, valor_movimento, saldo_anterior, saldo_novo,
        dados_depois, descricao, usuario_responsavel, ip_address, user_agent
    ) VALUES (
        NEW.multa_id, NEW.ipen, 'LANCAMENTO', 'laboral_controle_dividas_descontos', NEW.id,
        NEW.mes_referencia, NEW.valor_desconto, NEW.saldo_anterior, NEW.saldo_novo,
        JSON_OBJECT(
            'id', NEW.id,
            'multa_id', NEW.multa_id,
            'ipen', NEW.ipen,
            'mes_referencia', NEW.mes_referencia,
            'salario_real', NEW.salario_real,
            'percentual_desconto', NEW.percentual_desconto,
            'valor_desconto', NEW.valor_desconto,
            'saldo_anterior', NEW.saldo_anterior,
            'saldo_novo', NEW.saldo_novo,
            'status', NEW.status,
            'data_lancamento', NEW.data_lancamento,
            'observacoes', NEW.observacoes,
            'usuario_lancamento', NEW.usuario_lancamento
        ),
        CONCAT('Lançamento de desconto: R$ ', NEW.valor_desconto, ' - Mês: ', NEW.mes_referencia, ' - IPEN: ', NEW.ipen),
        NEW.usuario_lancamento,
        CONNECTION_ID(),
        SYSTEM_USER()
    );
END
/
/

DELIMITER;

-- Trigger para UPDATE em laboral_controle_dividas_descontos
DELIMITER /
/

CREATE TRIGGER tr_laboral_controle_dividas_descontos_update
AFTER UPDATE ON laboral_controle_dividas_descontos
FOR EACH ROW
BEGIN
    INSERT INTO laboral_controle_dividas_historico (
        multa_id, ipen, tipo_acao, tabela_origem, registro_origem_id,
        mes_referencia, valor_movimento, dados_antes, dados_depois,
        descricao, usuario_responsavel, data_acao
    ) VALUES (
        NEW.multa_id, NEW.ipen, 'UPDATE', 'laboral_controle_dividas_descontos', NEW.id,
        NEW.mes_referencia, NEW.valor_desconto,
        JSON_OBJECT(
            'id', OLD.id,
            'multa_id', OLD.multa_id,
            'ipen', OLD.ipen,
            'mes_referencia', OLD.mes_referencia,
            'salario_real', OLD.salario_real,
            'percentual_desconto', OLD.percentual_desconto,
            'valor_desconto', OLD.valor_desconto,
            'saldo_anterior', OLD.saldo_anterior,
            'saldo_novo', OLD.saldo_novo,
            'status', OLD.status,
            'data_lancamento', OLD.data_lancamento,
            'observacoes', OLD.observacoes,
            'usuario_lancamento', OLD.usuario_lancamento
        ),
        JSON_OBJECT(
            'id', NEW.id,
            'multa_id', NEW.multa_id,
            'ipen', NEW.ipen,
            'mes_referencia', NEW.mes_referencia,
            'salario_real', NEW.salario_real,
            'percentual_desconto', NEW.percentual_desconto,
            'valor_desconto', NEW.valor_desconto,
            'saldo_anterior', NEW.saldo_anterior,
            'saldo_novo', NEW.saldo_novo,
            'status', NEW.status,
            'data_lancamento', NEW.data_lancamento,
            'observacoes', NEW.observacoes,
            'usuario_lancamento', NEW.usuario_lancamento
        ),
        CONCAT('Lançamento atualizado: Mês ', NEW.mes_referencia, ' - IPEN: ', NEW.ipen),
        NEW.usuario_lancamento,
        NOW()
    );
END
/
/

DELIMITER;

-- Trigger para DELETE em laboral_controle_dividas_descontos
DELIMITER /
/

CREATE TRIGGER tr_laboral_controle_dividas_descontos_delete
AFTER DELETE ON laboral_controle_dividas_descontos
FOR EACH ROW
BEGIN
    INSERT INTO laboral_controle_dividas_historico (
        multa_id, ipen, tipo_acao, tabela_origem, registro_origem_id,
        mes_referencia, valor_movimento, dados_antes,
        descricao, usuario_responsavel
    ) VALUES (
        OLD.multa_id, OLD.ipen, 'DELETE', 'laboral_controle_dividas_descontos', OLD.id,
        OLD.mes_referencia, OLD.valor_desconto,
        JSON_OBJECT(
            'id', OLD.id,
            'multa_id', OLD.multa_id,
            'ipen', OLD.ipen,
            'mes_referencia', OLD.mes_referencia,
            'salario_real', OLD.salario_real,
            'percentual_desconto', OLD.percentual_desconto,
            'valor_desconto', OLD.valor_desconto,
            'saldo_anterior', OLD.saldo_anterior,
            'saldo_novo', OLD.saldo_novo,
            'status', OLD.status,
            'data_lancamento', OLD.data_lancamento,
            'observacoes', OLD.observacoes,
            'usuario_lancamento', OLD.usuario_lancamento
        ),
        CONCAT('Lançamento excluído: Mês ', OLD.mes_referencia, ' - Valor: R$ ', OLD.valor_desconto, ' - IPEN: ', OLD.ipen),
        @current_user_id
    );
END
/
/

DELIMITER;

-- 6. Procedure para migrar dados existentes (se houver)
DELIMITER /
/

CREATE PROCEDURE sp_migrar_historico_dividas()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_id BIGINT;
    DECLARE v_ipen INT;
    DECLARE v_tipo_divida VARCHAR(50);
    DECLARE v_data_cadastro DATETIME;
    DECLARE v_usuario_cadastro INT;

    DECLARE cursor_dados CURSOR FOR
        SELECT id, ipen, tipo_divida, data_cadastro, usuario_cadastro
        FROM laboral_controle_dividas
        WHERE id NOT IN (SELECT DISTINCT registro_origem_id FROM laboral_controle_dividas_historico WHERE tabela_origem = 'laboral_controle_dividas');

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

    OPEN cursor_dados;

    migration_loop: LOOP
        FETCH cursor_dados INTO v_id, v_ipen, v_tipo_divida, v_data_cadastro, v_usuario_cadastro;

        IF done THEN
            LEAVE migration_loop;
        END IF;

        -- Inserir registro de migração para dívidas existentes
        INSERT INTO laboral_controle_dividas_historico (
            multa_id, ipen, tipo_divida, tipo_acao, tabela_origem, registro_origem_id,
            dados_depois, descricao, usuario_responsavel, data_acao
        ) VALUES (
            v_id, v_ipen, v_tipo_divida, 'INSERT', 'laboral_controle_dividas', v_id,
            (SELECT JSON_OBJECT(
                'id', id, 'ipen', ipen, 'cpf', cpf, 'autos', autos,
                'tipo_divida', tipo_divida, 'valor_divida', valor_divida,
                'salario_base', salario_base, 'valor_atual', valor_atual,
                'percentual_desconto', percentual_desconto, 'status', status,
                'status_detalhado', status_detalhado, 'data_cadastro', data_cadastro,
                'pensao_favorecido', pensao_favorecido, 'pensao_banco', pensao_banco,
                'pensao_agencia', pensao_agencia, 'pensao_conta', pensao_conta,
                'pensao_op', pensao_op, 'pensao_tipo_conta', pensao_tipo_conta,
                'pensao_determinacao', pensao_determinacao, 'usuario_cadastro', usuario_cadastro
            ) FROM laboral_controle_dividas WHERE id = v_id),
            CONCAT('Migração: Dívida cadastrada em ', v_data_cadastro, ' - IPEN: ', v_ipen),
            v_usuario_cadastro,
            v_data_cadastro
        );
    END LOOP;

    CLOSE cursor_dados;
END
/
/

DELIMITER;

-- 7. Procedure para limpar histórico antigo (opcional)
DELIMITER /
/

CREATE PROCEDURE sp_limpar_historico_dividas(IN anos_manter INT)
BEGIN
    DECLARE data_limite DATE;
    SET data_limite = DATE_SUB(CURDATE(), INTERVAL anos_manter YEAR);

    DELETE FROM laboral_controle_dividas_historico
    WHERE data_acao < data_limite
    AND tipo_acao NOT IN ('INSERT', 'LANCAMENTO'); -- Manter registros de criação e lançamentos

    SELECT ROW_COUNT() AS registros_removidos;
END
/
/

DELIMITER;

-- 8. Views para consultas facilitadas

-- View para auditoria completa
CREATE OR REPLACE VIEW vw_laboral_controle_dividas_audit AS
SELECT
    h.id,
    h.data_acao,
    h.tipo_acao,
    h.tabela_origem,
    h.registro_origem_id,
    h.descricao,
    h.multa_id,
    h.ipen,
    h.tipo_divida,
    h.mes_referencia,
    h.valor_movimento,
    h.saldo_anterior,
    h.saldo_novo,
    u.nome as usuario_responsavel_nome,
    h.ip_address,
    h.session_id,
    CASE h.tipo_acao
        WHEN 'INSERT' THEN 'Criação'
        WHEN 'UPDATE' THEN 'Atualização'
        WHEN 'DELETE' THEN 'Exclusão'
        WHEN 'LANCAMENTO' THEN 'Lançamento'
        WHEN 'STATUS_CHANGE' THEN 'Mudança de Status'
        WHEN 'CORRECAO' THEN 'Correção'
        ELSE h.tipo_acao
    END as tipo_acao_desc
FROM
    laboral_controle_dividas_historico h
    LEFT JOIN users u ON h.usuario_responsavel = u.id
ORDER BY h.data_acao DESC;

-- View para resumo por dívida
CREATE OR REPLACE VIEW vw_laboral_controle_dividas_resumo AS
SELECT
    multa_id,
    ipen,
    tipo_divida,
    COUNT(*) as total_acoes,
    SUM(
        CASE
            WHEN tipo_acao = 'LANCAMENTO' THEN 1
            ELSE 0
        END
    ) as total_lancamentos,
    SUM(
        CASE
            WHEN tipo_acao = 'LANCAMENTO' THEN valor_movimento
            ELSE 0
        END
    ) as total_lancado,
    MAX(data_acao) as ultima_acao,
    MIN(data_acao) as primeira_acao
FROM
    vw_laboral_controle_dividas_audit
GROUP BY
    multa_id,
    ipen,
    tipo_divida;

-- 9. Executar migração de dados existentes
CALL sp_migrar_historico_dividas ();

-- 10. Remover procedures temporárias (opcional)
-- DROP PROCEDURE IF EXISTS sp_migrar_historico_dividas;
-- DROP PROCEDURE IF EXISTS sp_limpar_historico_dividas;

-- =====================================================
-- FIM DA REESTRUTURAÇÃO
-- =====================================================
