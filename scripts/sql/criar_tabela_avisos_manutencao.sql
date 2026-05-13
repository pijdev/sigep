-- Tabela para controle de avisos de manutenção do SIGEP
-- Criada em: 2026-03-26
-- Autor: Cascade

CREATE TABLE IF NOT EXISTS `avisos_manutencao` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titulo` varchar(255) NOT NULL COMMENT 'Título do aviso de manutenção',
  `mensagem` text NOT NULL COMMENT 'Mensagem detalhada do aviso',
  `severidade` enum('info','success','warning','danger') NOT NULL DEFAULT 'warning' COMMENT 'Nível de severidade do aviso',
  `data_inicio` datetime NOT NULL COMMENT 'Data/hora de início da manutenção',
  `data_fim` datetime NOT NULL COMMENT 'Data/hora de término da manutenção',
  `setores_impactados` json DEFAULT NULL COMMENT 'Array com setores impactados',
  `sistemas_impactados` json DEFAULT NULL COMMENT 'Array com sistemas específicos impactados',
  `ativo` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Se o aviso está ativo',
  `criado_por` varchar(100) DEFAULT NULL COMMENT 'Usuário que criou o aviso',
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Data de criação',
  `atualizado_por` varchar(100) DEFAULT NULL COMMENT 'Usuário que atualizou pela última vez',
  `atualizado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Última atualização',
  PRIMARY KEY (`id`),
  KEY `idx_data_inicio` (`data_inicio`),
  KEY `idx_data_fim` (`data_fim`),
  KEY `idx_ativo` (`ativo`),
  KEY `idx_severidade` (`severidade`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Avisos de manutenção do sistema SIGEP';

-- Inserir o aviso atual do Painel de Internos
INSERT INTO `avisos_manutencao` (
  `titulo`, 
  `mensagem`, 
  `severidade`, 
  `data_inicio`, 
  `data_fim`, 
  `sistemas_impactados`,
  `criado_por`
) VALUES (
  'Aviso de Manutenção! ** Reconstrução do Painel de Internos **',
  'Prezados usuários, estamos realizando uma **reconstrução completa do Painel de Internos** para garantir maior estabilidade, performance e entrega de informações.\nIdentificamos no desenvolvimento da página que esta necessita de uma reestruturação urgente.\nDurante este período, o **Painel de Internos permanecerá indisponível**, mas **todas as demais funcionalidades do SIGEP continuam operando normalmente**.',
  'warning',
  '2026-03-26 09:00:00',
  '2026-03-29 18:00:00',
  '["Painel de Internos"]',
  'admin'
);

-- Estrutura dos dados JSON para referência:
-- setores_impactados: ["TI", "Censura", "Coordenação", "Eclusa"]
-- sistemas_impactados: ["Painel de Internos", "Relatórios", "Gestão de Usuários"]
