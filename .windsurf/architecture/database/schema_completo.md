# 🗄️ Schema Completo SIGEP
> Gerado em: 2026-04-17 15:51:38

## 🔧 Estrutura Completa

```sql
CREATE TABLE `auth_actions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `icon` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_dangerous` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela auth_actions
```sql
-- PRIMARY: id (BTREE)
-- unique_name: name (BTREE)
```

```sql
CREATE TABLE `auth_audit_logs` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `tenant_id` int NOT NULL,
  `session_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `action` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `resource` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `old_data` json DEFAULT NULL,
  `new_data` json DEFAULT NULL,
  `success` tinyint(1) DEFAULT '1',
  `failure_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `risk_level` enum('low','medium','high','critical') COLLATE utf8mb4_unicode_ci DEFAULT 'low',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_tenant` (`user_id`,`tenant_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created` (`created_at`),
  KEY `idx_risk` (`risk_level`),
  KEY `tenant_id` (`tenant_id`),
  CONSTRAINT `auth_audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `auth_audit_logs_ibfk_2` FOREIGN KEY (`tenant_id`) REFERENCES `auth_tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela auth_audit_logs
```sql
-- PRIMARY: id (BTREE)
-- idx_user_tenant: user_id (BTREE)
-- idx_user_tenant: tenant_id (BTREE)
-- idx_action: action (BTREE)
-- idx_created: created_at (BTREE)
-- idx_risk: risk_level (BTREE)
-- tenant_id: tenant_id (BTREE)
```

```sql
CREATE TABLE `auth_modules` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sector_id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `icon` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `route` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `sort_order` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_sector_slug` (`sector_id`,`slug`),
  KEY `idx_sector_active` (`sector_id`,`is_active`),
  CONSTRAINT `auth_modules_ibfk_1` FOREIGN KEY (`sector_id`) REFERENCES `auth_sectors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela auth_modules
```sql
-- PRIMARY: id (BTREE)
-- unique_sector_slug: sector_id (BTREE)
-- unique_sector_slug: slug (BTREE)
-- idx_sector_active: sector_id (BTREE)
-- idx_sector_active: is_active (BTREE)
```

```sql
CREATE TABLE `auth_permissions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `module_id` int NOT NULL,
  `action_id` int NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_system` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_module_action` (`module_id`,`action_id`),
  KEY `action_id` (`action_id`),
  CONSTRAINT `auth_permissions_ibfk_1` FOREIGN KEY (`module_id`) REFERENCES `auth_modules` (`id`) ON DELETE CASCADE,
  CONSTRAINT `auth_permissions_ibfk_2` FOREIGN KEY (`action_id`) REFERENCES `auth_actions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela auth_permissions
```sql
-- PRIMARY: id (BTREE)
-- unique_module_action: module_id (BTREE)
-- unique_module_action: action_id (BTREE)
-- action_id: action_id (BTREE)
```

```sql
CREATE TABLE `auth_role_permissions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `role_id` int NOT NULL,
  `permission_id` int NOT NULL,
  `granted_by` int NOT NULL,
  `granted_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_role_permission` (`role_id`,`permission_id`),
  KEY `permission_id` (`permission_id`),
  KEY `granted_by` (`granted_by`),
  CONSTRAINT `auth_role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `auth_roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `auth_role_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `auth_permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `auth_role_permissions_ibfk_3` FOREIGN KEY (`granted_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=52 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela auth_role_permissions
```sql
-- PRIMARY: id (BTREE)
-- unique_role_permission: role_id (BTREE)
-- unique_role_permission: permission_id (BTREE)
-- permission_id: permission_id (BTREE)
-- granted_by: granted_by (BTREE)
```

```sql
CREATE TABLE `auth_roles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `slug` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hierarchy_level` int DEFAULT '0',
  `is_sector_manager` tinyint(1) DEFAULT '0',
  `is_system_admin` tinyint(1) DEFAULT '0',
  `is_default` tinyint(1) DEFAULT '0',
  `color` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_hierarchy` (`hierarchy_level`),
  KEY `idx_active` (`is_system_admin`,`is_sector_manager`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela auth_roles
```sql
-- PRIMARY: id (BTREE)
-- slug: slug (BTREE)
-- idx_hierarchy: hierarchy_level (BTREE)
-- idx_active: is_system_admin (BTREE)
-- idx_active: is_sector_manager (BTREE)
```

```sql
CREATE TABLE `auth_sectors` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `icon` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `color` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `sort_order` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_slug` (`slug`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela auth_sectors
```sql
-- PRIMARY: id (BTREE)
-- slug: slug (BTREE)
-- idx_slug: slug (BTREE)
-- idx_active: is_active (BTREE)
```

```sql
CREATE TABLE `auth_tenants` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) DEFAULT '1',
  `encryption_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `idx_code` (`code`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela auth_tenants
```sql
-- PRIMARY: id (BTREE)
-- code: code (BTREE)
-- idx_code: code (BTREE)
-- idx_active: is_active (BTREE)
```

```sql
CREATE TABLE `auth_user_permissions_custom` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `permission_id` int NOT NULL,
  `tenant_id` int NOT NULL,
  `granted_by` int NOT NULL,
  `granted_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` timestamp NULL DEFAULT NULL,
  `is_granted` tinyint(1) DEFAULT '1',
  `reason` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_permission_tenant` (`user_id`,`permission_id`,`tenant_id`),
  KEY `permission_id` (`permission_id`),
  KEY `tenant_id` (`tenant_id`),
  KEY `granted_by` (`granted_by`),
  KEY `idx_expires` (`expires_at`),
  CONSTRAINT `auth_user_permissions_custom_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `auth_user_permissions_custom_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `auth_permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `auth_user_permissions_custom_ibfk_3` FOREIGN KEY (`tenant_id`) REFERENCES `auth_tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `auth_user_permissions_custom_ibfk_4` FOREIGN KEY (`granted_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela auth_user_permissions_custom
```sql
-- PRIMARY: id (BTREE)
-- unique_user_permission_tenant: user_id (BTREE)
-- unique_user_permission_tenant: permission_id (BTREE)
-- unique_user_permission_tenant: tenant_id (BTREE)
-- permission_id: permission_id (BTREE)
-- tenant_id: tenant_id (BTREE)
-- granted_by: granted_by (BTREE)
-- idx_expires: expires_at (BTREE)
```

```sql
CREATE TABLE `auth_user_roles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `role_id` int NOT NULL,
  `tenant_id` int NOT NULL,
  `sector_id` int DEFAULT NULL,
  `granted_by` int NOT NULL,
  `granted_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_role_tenant` (`user_id`,`role_id`,`tenant_id`),
  KEY `role_id` (`role_id`),
  KEY `tenant_id` (`tenant_id`),
  KEY `sector_id` (`sector_id`),
  KEY `granted_by` (`granted_by`),
  KEY `idx_user_active` (`user_id`,`is_active`),
  KEY `idx_expires` (`expires_at`),
  CONSTRAINT `auth_user_roles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `auth_user_roles_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `auth_roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `auth_user_roles_ibfk_3` FOREIGN KEY (`tenant_id`) REFERENCES `auth_tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `auth_user_roles_ibfk_4` FOREIGN KEY (`sector_id`) REFERENCES `auth_sectors` (`id`) ON DELETE SET NULL,
  CONSTRAINT `auth_user_roles_ibfk_5` FOREIGN KEY (`granted_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela auth_user_roles
```sql
-- PRIMARY: id (BTREE)
-- unique_user_role_tenant: user_id (BTREE)
-- unique_user_role_tenant: role_id (BTREE)
-- unique_user_role_tenant: tenant_id (BTREE)
-- role_id: role_id (BTREE)
-- tenant_id: tenant_id (BTREE)
-- sector_id: sector_id (BTREE)
-- granted_by: granted_by (BTREE)
-- idx_user_active: user_id (BTREE)
-- idx_user_active: is_active (BTREE)
-- idx_expires: expires_at (BTREE)
```

```sql
CREATE TABLE `auth_user_tenants` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `tenant_id` int NOT NULL,
  `is_primary` tinyint(1) DEFAULT '0',
  `last_login_at` timestamp NULL DEFAULT NULL,
  `login_count` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_tenant` (`user_id`,`tenant_id`),
  KEY `tenant_id` (`tenant_id`),
  KEY `idx_user_primary` (`user_id`,`is_primary`),
  CONSTRAINT `auth_user_tenants_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `auth_user_tenants_ibfk_2` FOREIGN KEY (`tenant_id`) REFERENCES `auth_tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela auth_user_tenants
```sql
-- PRIMARY: id (BTREE)
-- unique_user_tenant: user_id (BTREE)
-- unique_user_tenant: tenant_id (BTREE)
-- tenant_id: tenant_id (BTREE)
-- idx_user_primary: user_id (BTREE)
-- idx_user_primary: is_primary (BTREE)
```

```sql
CREATE TABLE `avisos_manutencao` (
  `id` int NOT NULL AUTO_INCREMENT,
  `titulo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Título do aviso de manutenção',
  `mensagem` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Mensagem detalhada do aviso',
  `severidade` enum('info','success','warning','danger') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'warning' COMMENT 'Nível de severidade do aviso',
  `data_inicio` datetime NOT NULL COMMENT 'Data/hora de início da manutenção',
  `data_fim` datetime NOT NULL COMMENT 'Data/hora de término da manutenção',
  `setores_impactados` json DEFAULT NULL COMMENT 'Array com setores impactados',
  `sistemas_impactados` json DEFAULT NULL COMMENT 'Array com sistemas específicos impactados',
  `ativo` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Se o aviso está ativo',
  `criado_por` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Usuário que criou o aviso',
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Data de criação',
  `atualizado_por` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Usuário que atualizou pela última vez',
  `atualizado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Última atualização',
  PRIMARY KEY (`id`),
  KEY `idx_data_inicio` (`data_inicio`),
  KEY `idx_data_fim` (`data_fim`),
  KEY `idx_ativo` (`ativo`),
  KEY `idx_severidade` (`severidade`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Avisos de manutenção do sistema SIGEP';
```

### Índices da tabela avisos_manutencao
```sql
-- PRIMARY: id (BTREE)
-- idx_data_inicio: data_inicio (BTREE)
-- idx_data_fim: data_fim (BTREE)
-- idx_ativo: ativo (BTREE)
-- idx_severidade: severidade (BTREE)
```

```sql
CREATE TABLE `backup_acesso_seguro_20260406` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `usuario` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `setor` enum('Censura','Almoxarifado','Segurança do Trabalho','Laboral','Recursos Humanos','Coordenação','Direção','Recepção','Tecnologia da Informação','Serralheria','Escola','Carga','Indústria','Jurídico','Cozinha','Eclusa') COLLATE utf8mb4_general_ci NOT NULL,
  `senha` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('Ativo','Inativo') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Ativo',
  `is_admin` tinyint(1) DEFAULT '0',
  `criado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `dark_mode` tinyint(1) DEFAULT '0',
  `is_kiosk` tinyint(1) NOT NULL DEFAULT '0',
  `kiosk_token` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `kiosk_token_updated_at` datetime DEFAULT NULL,
  `perm_dados` tinyint(1) DEFAULT '0',
  `perm_importacao` tinyint(1) DEFAULT '0',
  `perm_censura` tinyint(1) DEFAULT '0',
  `perm_almoxarifado` tinyint(1) DEFAULT '0',
  `perm_seg_trab` tinyint(1) DEFAULT '0',
  `perm_laboral` tinyint(1) DEFAULT '0',
  `perm_rh` tinyint(1) DEFAULT '0',
  `perm_coord` tinyint(1) DEFAULT '0',
  `perm_direcao` tinyint(1) DEFAULT '0',
  `perm_portaria` tinyint(1) DEFAULT '0',
  `perm_ti` tinyint(1) DEFAULT '0',
  `perm_gestao_contas` tinyint(1) DEFAULT '0',
  `perm_serralheria` tinyint(1) DEFAULT '0',
  `perm_escola` tinyint(1) DEFAULT '0',
  `perm_carga` tinyint(1) DEFAULT '0',
  `perm_industria` tinyint(1) DEFAULT '0',
  `perm_juridico` tinyint(1) DEFAULT '0',
  `perm_cozinha` tinyint(1) DEFAULT '0',
  `perm_recepcao` tinyint(1) DEFAULT '0',
  `perm_eclusa` tinyint(1) DEFAULT '0',
  `perm_manutencao` tinyint(1) DEFAULT '0',
  `perm_social` tinyint(1) DEFAULT '0',
  `perm_chefeseg` tinyint(1) DEFAULT '0',
  `perm_apoio` tinyint(1) DEFAULT '0',
  `perm_saude` tinyint(1) DEFAULT '0',
  `remember_token` varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `remember_expiry` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `usuario` (`usuario`),
  KEY `idx_remember_token` (`remember_token`),
  KEY `idx_remember_expiry` (`remember_expiry`),
  KEY `idx_setor` (`setor`),
  KEY `idx_status` (`status`),
  KEY `idx_setor_status` (`setor`,`status`),
  KEY `idx_is_admin` (`is_admin`),
  KEY `idx_is_kiosk` (`is_kiosk`),
  KEY `idx_kiosk_token` (`kiosk_token`),
  KEY `idx_perm_ti` (`perm_ti`),
  KEY `idx_perm_laboral` (`perm_laboral`),
  KEY `idx_perm_censura` (`perm_censura`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

### Índices da tabela backup_acesso_seguro_20260406
```sql
-- PRIMARY: id (BTREE)
-- usuario: usuario (BTREE)
-- idx_remember_token: remember_token (BTREE)
-- idx_remember_expiry: remember_expiry (BTREE)
-- idx_setor: setor (BTREE)
-- idx_status: status (BTREE)
-- idx_setor_status: setor (BTREE)
-- idx_setor_status: status (BTREE)
-- idx_is_admin: is_admin (BTREE)
-- idx_is_kiosk: is_kiosk (BTREE)
-- idx_kiosk_token: kiosk_token (BTREE)
-- idx_perm_ti: perm_ti (BTREE)
-- idx_perm_laboral: perm_laboral (BTREE)
-- idx_perm_censura: perm_censura (BTREE)
```

```sql
CREATE TABLE `backup_acesso_seguro_auditoria_20260406` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `usuario_id` int DEFAULT NULL,
  `usuario_nome` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `acao` enum('login_sucesso','login_falha','logout','sessao_expirou','conta_bloqueada') COLLATE utf8mb4_unicode_ci NOT NULL,
  `detalhes` json DEFAULT NULL,
  `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `sessao_id` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_ip` (`ip_address`),
  KEY `idx_timestamp` (`timestamp`)
) ENGINE=InnoDB AUTO_INCREMENT=670 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela backup_acesso_seguro_auditoria_20260406
```sql
-- PRIMARY: id (BTREE)
-- idx_usuario: usuario_id (BTREE)
-- idx_ip: ip_address (BTREE)
-- idx_timestamp: timestamp (BTREE)
```

```sql
CREATE TABLE `backup_acesso_seguro_rate_limit_20260406` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `usuario` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tentativas` int DEFAULT '1',
  `bloqueado_ate` timestamp NULL DEFAULT NULL,
  `ultimo_reset` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_ip_usuario` (`ip_address`,`usuario`),
  KEY `idx_bloqueado` (`bloqueado_ate`)
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela backup_acesso_seguro_rate_limit_20260406
```sql
-- PRIMARY: id (BTREE)
-- unique_ip_usuario: ip_address (BTREE)
-- unique_ip_usuario: usuario (BTREE)
-- idx_bloqueado: bloqueado_ate (BTREE)
```

```sql
CREATE TABLE `brasil_bancos_ativos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ispb` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `atualizado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_codigo` (`codigo`)
) ENGINE=InnoDB AUTO_INCREMENT=459092 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela brasil_bancos_ativos
```sql
-- PRIMARY: id (BTREE)
-- uk_codigo: codigo (BTREE)
```

```sql
CREATE TABLE `carta_anexos` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `carta_id` bigint DEFAULT NULL,
  `nome_arquivo_original` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nome_arquivo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `caminho_arquivo` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tamanho_bytes` bigint unsigned NOT NULL,
  `hash_md5` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_carta_anexos_carta` (`carta_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela carta_anexos
```sql
-- PRIMARY: id (BTREE)
-- idx_carta_anexos_carta: carta_id (BTREE)
```

```sql
CREATE TABLE `censura_cartas` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `tipo_movimentacao` enum('Entrada','Saida') COLLATE utf8mb4_general_ci NOT NULL,
  `id_interno` int NOT NULL,
  `interno_nome` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `interno_nome_social` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `interno_galeria` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `interno_bloco` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `interno_res` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `id_correspondente` int NOT NULL,
  `correspondente_nome` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `correspondente_vinculo` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `correspondente_logradouro` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `correspondente_numero` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `correspondente_bairro` varchar(120) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `correspondente_cidade` varchar(120) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `correspondente_uf` char(2) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `correspondente_cep` varchar(15) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `correspondente_complemento` varchar(120) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status_censura` enum('Liberada','Retida','Devolvida') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Liberada',
  `observacoes_censura` text COLLATE utf8mb4_general_ci,
  `motivo_retencao` text COLLATE utf8mb4_general_ci,
  `recebido_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `concluido_em` datetime DEFAULT NULL,
  `monitor_id` int NOT NULL,
  `monitor_nome` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `status_registro` enum('Ativo','Cancelado') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Ativo',
  `cancelado_em` datetime DEFAULT NULL,
  `cancelado_por_id` int DEFAULT NULL,
  `cancelado_por_nome` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `motivo_cancelamento` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id`),
  KEY `idx_censura_cartas_interno_data` (`id_interno`,`recebido_em`),
  KEY `idx_censura_cartas_corresp_data` (`id_correspondente`,`recebido_em`),
  KEY `idx_censura_cartas_status` (`status_censura`,`status_registro`),
  KEY `idx_censura_cartas_monitor_data` (`monitor_id`,`recebido_em`),
  KEY `fk_censura_cartas_cancelado_por` (`cancelado_por_id`),
  CONSTRAINT `fk_censura_cartas_cancelado_por` FOREIGN KEY (`cancelado_por_id`) REFERENCES `backup_acesso_seguro_20260406` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_censura_cartas_corresp` FOREIGN KEY (`id_correspondente`) REFERENCES `censura_cartas_correspondentes` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_censura_cartas_interno` FOREIGN KEY (`id_interno`) REFERENCES `internos` (`ipen`) ON DELETE RESTRICT,
  CONSTRAINT `fk_censura_cartas_monitor` FOREIGN KEY (`monitor_id`) REFERENCES `backup_acesso_seguro_20260406` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

### Índices da tabela censura_cartas
```sql
-- PRIMARY: id (BTREE)
-- idx_censura_cartas_interno_data: id_interno (BTREE)
-- idx_censura_cartas_interno_data: recebido_em (BTREE)
-- idx_censura_cartas_corresp_data: id_correspondente (BTREE)
-- idx_censura_cartas_corresp_data: recebido_em (BTREE)
-- idx_censura_cartas_status: status_censura (BTREE)
-- idx_censura_cartas_status: status_registro (BTREE)
-- idx_censura_cartas_monitor_data: monitor_id (BTREE)
-- idx_censura_cartas_monitor_data: recebido_em (BTREE)
-- fk_censura_cartas_cancelado_por: cancelado_por_id (BTREE)
```

```sql
CREATE TABLE `censura_cartas_correspondentes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `vinculo` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `logradouro` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `numero` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bairro` varchar(120) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cidade` varchar(120) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `uf` char(2) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cep` varchar(15) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `complemento` varchar(120) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ativo` enum('S','N') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'S',
  `criado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `criado_por` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_censura_cartas_corresp_nome` (`nome`),
  KEY `idx_censura_cartas_corresp_ativo` (`ativo`),
  KEY `fk_censura_cartas_corresp_user` (`criado_por`),
  CONSTRAINT `fk_censura_cartas_corresp_user` FOREIGN KEY (`criado_por`) REFERENCES `backup_acesso_seguro_20260406` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

### Índices da tabela censura_cartas_correspondentes
```sql
-- PRIMARY: id (BTREE)
-- idx_censura_cartas_corresp_nome: nome (BTREE)
-- idx_censura_cartas_corresp_ativo: ativo (BTREE)
-- fk_censura_cartas_corresp_user: criado_por (BTREE)
```

```sql
CREATE TABLE `censura_cartas_historico` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `id_carta` bigint NOT NULL,
  `operacao` enum('INSERT','UPDATE','STATUS','CANCELAMENTO','RETIFICACAO') COLLATE utf8mb4_general_ci NOT NULL,
  `valor_antigo` json DEFAULT NULL,
  `valor_novo` json DEFAULT NULL,
  `usuario_id` int DEFAULT NULL,
  `usuario_nome` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `data_hora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_censura_cartas_hist_carta` (`id_carta`),
  KEY `idx_censura_cartas_hist_data` (`data_hora`),
  KEY `fk_censura_cartas_hist_user` (`usuario_id`),
  CONSTRAINT `fk_censura_cartas_hist_carta` FOREIGN KEY (`id_carta`) REFERENCES `censura_cartas` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_censura_cartas_hist_user` FOREIGN KEY (`usuario_id`) REFERENCES `backup_acesso_seguro_20260406` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

### Índices da tabela censura_cartas_historico
```sql
-- PRIMARY: id (BTREE)
-- idx_censura_cartas_hist_carta: id_carta (BTREE)
-- idx_censura_cartas_hist_data: data_hora (BTREE)
-- fk_censura_cartas_hist_user: usuario_id (BTREE)
```

```sql
CREATE TABLE `censura_cartas_vinculos` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `id_interno` int NOT NULL,
  `id_correspondente` int NOT NULL,
  `score_entrada` int NOT NULL DEFAULT '0',
  `score_saida` int NOT NULL DEFAULT '0',
  `ultimo_uso_entrada` datetime DEFAULT NULL,
  `ultimo_uso_saida` datetime DEFAULT NULL,
  `preferencial_entrada` enum('S','N') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'N',
  `preferencial_saida` enum('S','N') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'N',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_censura_cartas_vinculo` (`id_interno`,`id_correspondente`),
  KEY `idx_censura_cartas_vinculos_entrada` (`score_entrada`,`ultimo_uso_entrada`),
  KEY `idx_censura_cartas_vinculos_saida` (`score_saida`,`ultimo_uso_saida`),
  KEY `fk_censura_cartas_vinc_corresp` (`id_correspondente`),
  CONSTRAINT `fk_censura_cartas_vinc_corresp` FOREIGN KEY (`id_correspondente`) REFERENCES `censura_cartas_correspondentes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_censura_cartas_vinc_interno` FOREIGN KEY (`id_interno`) REFERENCES `internos` (`ipen`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

### Índices da tabela censura_cartas_vinculos
```sql
-- PRIMARY: id (BTREE)
-- uq_censura_cartas_vinculo: id_interno (BTREE)
-- uq_censura_cartas_vinculo: id_correspondente (BTREE)
-- idx_censura_cartas_vinculos_entrada: score_entrada (BTREE)
-- idx_censura_cartas_vinculos_entrada: ultimo_uso_entrada (BTREE)
-- idx_censura_cartas_vinculos_saida: score_saida (BTREE)
-- idx_censura_cartas_vinculos_saida: ultimo_uso_saida (BTREE)
-- fk_censura_cartas_vinc_corresp: id_correspondente (BTREE)
```

```sql
CREATE TABLE `censura_estoque_estoques` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `descricao` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tipo` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `capacidade_maxima` int NOT NULL DEFAULT '0',
  `status` enum('Ativo','Inativo') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Ativo',
  `criado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_censura_estoque_estoques_nome` (`nome`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

### Índices da tabela censura_estoque_estoques
```sql
-- PRIMARY: id (BTREE)
-- uq_censura_estoque_estoques_nome: nome (BTREE)
```

```sql
CREATE TABLE `censura_estoque_fornecedores` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `cnpj_cpf` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `telefone` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `endereco` text COLLATE utf8mb4_general_ci,
  `status` enum('Ativo','Inativo') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Ativo',
  `criado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

### Índices da tabela censura_estoque_fornecedores
```sql
-- PRIMARY: id (BTREE)
```

```sql
CREATE TABLE `censura_estoque_movimentacoes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_produto` int NOT NULL,
  `id_variante` int DEFAULT NULL,
  `tipo_movimentacao` enum('Entrada','Saida') COLLATE utf8mb4_general_ci NOT NULL,
  `quantidade` int NOT NULL,
  `data_movimentacao` date NOT NULL,
  `tipo_destino_origem` enum('Interno','Funcionario','Alojamento_Policia','Outro','Fornecedor') COLLATE utf8mb4_general_ci NOT NULL,
  `id_interno` int DEFAULT NULL,
  `id_funcionario` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `destino_origem_outro` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `id_fornecedor` int DEFAULT NULL,
  `documento_referencia` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `motivo_movimentacao` text COLLATE utf8mb4_general_ci,
  `observacoes` text COLLATE utf8mb4_general_ci,
  `cadastrado_por` int DEFAULT NULL,
  `data_cadastro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `editado_em` datetime DEFAULT NULL,
  `editado_por` int DEFAULT NULL,
  `status` enum('Ativo','Cancelado') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Ativo',
  PRIMARY KEY (`id`),
  KEY `id_produto` (`id_produto`),
  KEY `id_interno` (`id_interno`),
  KEY `id_fornecedor` (`id_fornecedor`),
  KEY `cadastrado_por` (`cadastrado_por`),
  KEY `data_movimentacao` (`data_movimentacao`),
  KEY `idx_mov_data_tipo_status` (`data_movimentacao`,`tipo_movimentacao`,`status`),
  KEY `idx_mov_prod_var` (`id_produto`,`id_variante`),
  KEY `idx_mov_editado_por` (`editado_por`),
  KEY `fk_mov_id_variante` (`id_variante`),
  CONSTRAINT `censura_estoque_movimentacoes_ibfk_1` FOREIGN KEY (`id_produto`) REFERENCES `censura_estoque_produtos` (`id`),
  CONSTRAINT `censura_estoque_movimentacoes_ibfk_2` FOREIGN KEY (`id_interno`) REFERENCES `internos` (`ipen`) ON DELETE SET NULL,
  CONSTRAINT `censura_estoque_movimentacoes_ibfk_3` FOREIGN KEY (`id_fornecedor`) REFERENCES `censura_estoque_fornecedores` (`id`) ON DELETE SET NULL,
  CONSTRAINT `censura_estoque_movimentacoes_ibfk_4` FOREIGN KEY (`cadastrado_por`) REFERENCES `backup_acesso_seguro_20260406` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_mov_editado_por` FOREIGN KEY (`editado_por`) REFERENCES `backup_acesso_seguro_20260406` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_mov_id_variante` FOREIGN KEY (`id_variante`) REFERENCES `censura_estoque_produto_variantes` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=128 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

### Índices da tabela censura_estoque_movimentacoes
```sql
-- PRIMARY: id (BTREE)
-- id_produto: id_produto (BTREE)
-- id_interno: id_interno (BTREE)
-- id_fornecedor: id_fornecedor (BTREE)
-- cadastrado_por: cadastrado_por (BTREE)
-- data_movimentacao: data_movimentacao (BTREE)
-- idx_mov_data_tipo_status: data_movimentacao (BTREE)
-- idx_mov_data_tipo_status: tipo_movimentacao (BTREE)
-- idx_mov_data_tipo_status: status (BTREE)
-- idx_mov_prod_var: id_produto (BTREE)
-- idx_mov_prod_var: id_variante (BTREE)
-- idx_mov_editado_por: editado_por (BTREE)
-- fk_mov_id_variante: id_variante (BTREE)
```

```sql
CREATE TABLE `censura_estoque_movimentacoes_auditoria` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `id_movimentacao` int NOT NULL,
  `acao` enum('edicao','cancelamento','reativacao') COLLATE utf8mb4_general_ci NOT NULL,
  `snapshot_antes` json DEFAULT NULL,
  `snapshot_depois` json DEFAULT NULL,
  `observacao` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `usuario_id` int DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_censura_estoque_aud_mov` (`id_movimentacao`),
  KEY `idx_censura_estoque_aud_data` (`criado_em`),
  KEY `fk_censura_estoque_aud_user` (`usuario_id`),
  CONSTRAINT `fk_censura_estoque_aud_mov` FOREIGN KEY (`id_movimentacao`) REFERENCES `censura_estoque_movimentacoes` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_censura_estoque_aud_user` FOREIGN KEY (`usuario_id`) REFERENCES `backup_acesso_seguro_20260406` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=178 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

### Índices da tabela censura_estoque_movimentacoes_auditoria
```sql
-- PRIMARY: id (BTREE)
-- idx_censura_estoque_aud_mov: id_movimentacao (BTREE)
-- idx_censura_estoque_aud_data: criado_em (BTREE)
-- fk_censura_estoque_aud_user: usuario_id (BTREE)
```

```sql
CREATE TABLE `censura_estoque_produto_variantes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_produto` int NOT NULL,
  `cor` varchar(60) COLLATE utf8mb4_general_ci NOT NULL,
  `tamanho` varchar(30) COLLATE utf8mb4_general_ci NOT NULL,
  `sku_interno` varchar(80) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `codigo_barras` varchar(80) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `quantidade_minima` int NOT NULL DEFAULT '0',
  `quantidade_alerta` int NOT NULL DEFAULT '0',
  `status` enum('Ativo','Inativo') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Ativo',
  `criado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_censura_estoque_variantes_prod_cor_tam` (`id_produto`,`cor`,`tamanho`),
  KEY `idx_censura_estoque_variantes_status` (`status`),
  CONSTRAINT `fk_censura_estoque_variantes_produto` FOREIGN KEY (`id_produto`) REFERENCES `censura_estoque_produtos` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=133 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

### Índices da tabela censura_estoque_produto_variantes
```sql
-- PRIMARY: id (BTREE)
-- uq_censura_estoque_variantes_prod_cor_tam: id_produto (BTREE)
-- uq_censura_estoque_variantes_prod_cor_tam: cor (BTREE)
-- uq_censura_estoque_variantes_prod_cor_tam: tamanho (BTREE)
-- idx_censura_estoque_variantes_status: status (BTREE)
```

```sql
CREATE TABLE `censura_estoque_produtos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `descricao` text COLLATE utf8mb4_general_ci,
  `id_tipo` int NOT NULL,
  `id_fornecedor` int DEFAULT NULL,
  `quantidade_minima` int NOT NULL DEFAULT '0',
  `quantidade_alerta` int NOT NULL DEFAULT '0',
  `id_estoque` int DEFAULT NULL,
  `localizacao` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `unidade_medida` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'un',
  `status` enum('Ativo','Inativo') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Ativo',
  `criado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `criado_por` int DEFAULT NULL,
  `atualizado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `atualizado_por` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_tipo` (`id_tipo`),
  KEY `id_fornecedor` (`id_fornecedor`),
  KEY `criado_por` (`criado_por`),
  KEY `atualizado_por` (`atualizado_por`),
  KEY `idx_censura_estoque_produtos_id_estoque` (`id_estoque`),
  CONSTRAINT `censura_estoque_produtos_ibfk_1` FOREIGN KEY (`id_tipo`) REFERENCES `censura_estoque_tipos` (`id`),
  CONSTRAINT `censura_estoque_produtos_ibfk_2` FOREIGN KEY (`id_fornecedor`) REFERENCES `censura_estoque_fornecedores` (`id`) ON DELETE SET NULL,
  CONSTRAINT `censura_estoque_produtos_ibfk_3` FOREIGN KEY (`criado_por`) REFERENCES `backup_acesso_seguro_20260406` (`id`) ON DELETE SET NULL,
  CONSTRAINT `censura_estoque_produtos_ibfk_4` FOREIGN KEY (`atualizado_por`) REFERENCES `backup_acesso_seguro_20260406` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_censura_estoque_produtos_id_estoque` FOREIGN KEY (`id_estoque`) REFERENCES `censura_estoque_estoques` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=68 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

### Índices da tabela censura_estoque_produtos
```sql
-- PRIMARY: id (BTREE)
-- id_tipo: id_tipo (BTREE)
-- id_fornecedor: id_fornecedor (BTREE)
-- criado_por: criado_por (BTREE)
-- atualizado_por: atualizado_por (BTREE)
-- idx_censura_estoque_produtos_id_estoque: id_estoque (BTREE)
```

```sql
CREATE TABLE `censura_estoque_saldo` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_produto` int NOT NULL,
  `quantidade_atual` int NOT NULL DEFAULT '0',
  `ultima_atualizacao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `atualizado_por` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_produto` (`id_produto`),
  KEY `atualizado_por` (`atualizado_por`),
  CONSTRAINT `censura_estoque_saldo_ibfk_1` FOREIGN KEY (`id_produto`) REFERENCES `censura_estoque_produtos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `censura_estoque_saldo_ibfk_2` FOREIGN KEY (`atualizado_por`) REFERENCES `backup_acesso_seguro_20260406` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

### Índices da tabela censura_estoque_saldo
```sql
-- PRIMARY: id (BTREE)
-- id_produto: id_produto (BTREE)
-- atualizado_por: atualizado_por (BTREE)
```

```sql
CREATE TABLE `censura_estoque_saldo_variantes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_variante` int NOT NULL,
  `quantidade_atual` int NOT NULL DEFAULT '0',
  `atualizado_por` int DEFAULT NULL,
  `ultima_atualizacao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_censura_estoque_saldo_variantes` (`id_variante`),
  KEY `idx_censura_estoque_saldo_var_qtd` (`quantidade_atual`),
  KEY `fk_censura_estoque_saldo_var_user` (`atualizado_por`),
  CONSTRAINT `fk_censura_estoque_saldo_var_user` FOREIGN KEY (`atualizado_por`) REFERENCES `backup_acesso_seguro_20260406` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_censura_estoque_saldo_var_variante` FOREIGN KEY (`id_variante`) REFERENCES `censura_estoque_produto_variantes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=107 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

### Índices da tabela censura_estoque_saldo_variantes
```sql
-- PRIMARY: id (BTREE)
-- uq_censura_estoque_saldo_variantes: id_variante (BTREE)
-- idx_censura_estoque_saldo_var_qtd: quantidade_atual (BTREE)
-- fk_censura_estoque_saldo_var_user: atualizado_por (BTREE)
```

```sql
CREATE TABLE `censura_estoque_tipos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `descricao` text COLLATE utf8mb4_general_ci,
  `status` enum('Ativo','Inativo') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Ativo',
  `criado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nome` (`nome`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

### Índices da tabela censura_estoque_tipos
```sql
-- PRIMARY: id (BTREE)
-- nome: nome (BTREE)
```

```sql
CREATE TABLE `censura_rouparia_kits_prontos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kit_numero` int NOT NULL,
  `cor` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pronto','atribuido','liberado','refazendo') COLLATE utf8mb4_unicode_ci DEFAULT 'pronto',
  `ipen_atribuido` int DEFAULT NULL,
  `nome_atribuido` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_atribuicao` datetime DEFAULT NULL,
  `data_liberacao` datetime DEFAULT NULL,
  `ipen_liberado` int DEFAULT NULL,
  `nome_liberado` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `motivo_liberacao` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_cadastro` datetime DEFAULT CURRENT_TIMESTAMP,
  `info_adicional` text COLLATE utf8mb4_unicode_ci,
  `usuario` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_kit_numero` (`kit_numero`),
  KEY `idx_status` (`status`),
  KEY `idx_data_liberacao` (`data_liberacao`)
) ENGINE=InnoDB AUTO_INCREMENT=370 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela censura_rouparia_kits_prontos
```sql
-- PRIMARY: id (BTREE)
-- idx_kit_numero: kit_numero (BTREE)
-- idx_status: status (BTREE)
-- idx_data_liberacao: data_liberacao (BTREE)
```

```sql
CREATE TABLE `controle_caminhoes_pipa` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `data_abastecimento` date NOT NULL,
  `id_motorista` bigint unsigned NOT NULL,
  `id_empresa` bigint unsigned NOT NULL,
  `id_veiculo` bigint unsigned NOT NULL,
  `hora_chegada` time NOT NULL,
  `quantidade_litros` decimal(10,2) NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_controle_caminhoes_pipa_motorista` (`id_motorista`),
  KEY `fk_controle_caminhoes_pipa_empresa` (`id_empresa`),
  KEY `fk_controle_caminhoes_pipa_veiculo` (`id_veiculo`),
  CONSTRAINT `fk_controle_caminhoes_pipa_empresa` FOREIGN KEY (`id_empresa`) REFERENCES `eclusa_empresas` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_controle_caminhoes_pipa_motorista` FOREIGN KEY (`id_motorista`) REFERENCES `eclusa_motoristas` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_controle_caminhoes_pipa_veiculo` FOREIGN KEY (`id_veiculo`) REFERENCES `eclusa_veiculos` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela controle_caminhoes_pipa
```sql
-- PRIMARY: id (BTREE)
-- fk_controle_caminhoes_pipa_motorista: id_motorista (BTREE)
-- fk_controle_caminhoes_pipa_empresa: id_empresa (BTREE)
-- fk_controle_caminhoes_pipa_veiculo: id_veiculo (BTREE)
```

```sql
CREATE TABLE `documentos_digitais` (
  `id` int NOT NULL AUTO_INCREMENT,
  `interno_id` int NOT NULL COMMENT 'ID do interno relacionado',
  `setor_origem` enum('Censura','Laboral','Jurídico','Coordenação','Direção','RH','Almoxarifado','TI') COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo_documento` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Ex: carta, foto, memorando, peculio, kit_higiene, kit_roupas',
  `titulo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` text COLLATE utf8mb4_unicode_ci,
  `data_documento` date DEFAULT NULL COMMENT 'Data do documento físico',
  `arquivo_nome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `arquivo_path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `arquivo_tamanho` bigint NOT NULL,
  `arquivo_mime` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pendente','aprovado','rejeitado','arquivado') COLLATE utf8mb4_unicode_ci DEFAULT 'pendente',
  `responsavel_upload` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Usuário que fez upload',
  `responsavel_aprovacao` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Usuário que aprovou',
  `data_aprovacao` datetime DEFAULT NULL,
  `motivo_rejeicao` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_interno_id` (`interno_id`),
  KEY `idx_setor_origem` (`setor_origem`),
  KEY `idx_tipo_documento` (`tipo_documento`),
  KEY `idx_data_documento` (`data_documento`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Documentos digitalizados do SIGEP';
```

### Índices da tabela documentos_digitais
```sql
-- PRIMARY: id (BTREE)
-- idx_interno_id: interno_id (BTREE)
-- idx_setor_origem: setor_origem (BTREE)
-- idx_tipo_documento: tipo_documento (BTREE)
-- idx_data_documento: data_documento (BTREE)
-- idx_status: status (BTREE)
-- idx_created_at: created_at (BTREE)
```

```sql
CREATE TABLE `documentos_tipos_config` (
  `id` int NOT NULL AUTO_INCREMENT,
  `setor` enum('Censura','Laboral','Jurídico','Coordenação','Direção','RH','Almoxarifado','TI') COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo_documento` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime_permitidos` json NOT NULL COMMENT 'Array com MIME types permitidos',
  `tamanho_maximo_mb` int NOT NULL DEFAULT '10',
  `obrigatorio_aprovacao` tinyint(1) NOT NULL DEFAULT '1',
  `dias_retention` int DEFAULT NULL COMMENT 'Dias para manter arquivo (null = permanente)',
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_setor_tipo` (`setor`,`tipo_documento`),
  KEY `idx_setor` (`setor`),
  KEY `idx_ativo` (`ativo`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Configuração de tipos de documentos por setor';
```

### Índices da tabela documentos_tipos_config
```sql
-- PRIMARY: id (BTREE)
-- uk_setor_tipo: setor (BTREE)
-- uk_setor_tipo: tipo_documento (BTREE)
-- idx_setor: setor (BTREE)
-- idx_ativo: ativo (BTREE)
```

```sql
CREATE TABLE `eclusa_destinos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nome` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo` enum('destino','empresa') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'destino',
  `observacoes` longtext COLLATE utf8mb4_unicode_ci,
  `criado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_escolta_destinos_nome` (`nome`)
) ENGINE=InnoDB AUTO_INCREMENT=131 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela eclusa_destinos
```sql
-- PRIMARY: id (BTREE)
-- uk_escolta_destinos_nome: nome (BTREE)
```

```sql
CREATE TABLE `eclusa_empresas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nome` varchar(200) NOT NULL,
  `observacoes` longtext NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nome` (`nome`)
) ENGINE=InnoDB AUTO_INCREMENT=283 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
```

### Índices da tabela eclusa_empresas
```sql
-- PRIMARY: id (BTREE)
-- nome: nome (BTREE)
```

```sql
CREATE TABLE `eclusa_escolta_auditoria` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_escolta` bigint unsigned NOT NULL,
  `campo` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `valor_antigo` text COLLATE utf8mb4_unicode_ci,
  `valor_novo` text COLLATE utf8mb4_unicode_ci,
  `data_alteracao` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `quem_alterou` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `operacao` enum('criacao','alteracao','exclusao') COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_id_escolta` (`id_escolta`),
  KEY `idx_data_alteracao` (`data_alteracao`),
  KEY `idx_operacao` (`operacao`),
  CONSTRAINT `eclusa_escolta_auditoria_ibfk_1` FOREIGN KEY (`id_escolta`) REFERENCES `eclusa_movimentacoes_escolta` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=438 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela eclusa_escolta_auditoria
```sql
-- PRIMARY: id (BTREE)
-- idx_id_escolta: id_escolta (BTREE)
-- idx_data_alteracao: data_alteracao (BTREE)
-- idx_operacao: operacao (BTREE)
```

```sql
CREATE TABLE `eclusa_motoristas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nome` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cargo` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_motoristas_nome` (`nome`)
) ENGINE=InnoDB AUTO_INCREMENT=239 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela eclusa_motoristas
```sql
-- PRIMARY: id (BTREE)
-- uk_motoristas_nome: nome (BTREE)
```

```sql
CREATE TABLE `eclusa_movimentacoes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `data_movimentacao` date NOT NULL COMMENT 'Data da movimentação (convertida do serial Excel)',
  `veiculo_id` bigint unsigned DEFAULT NULL COMMENT 'Referência para eclusa_veiculos.id',
  `empresa_id` bigint unsigned DEFAULT NULL COMMENT 'Referência para eclusa_empresas.id',
  `motorista_id` bigint unsigned DEFAULT NULL COMMENT 'Referência para eclusa_motoristas.id',
  `hora_chegada` time DEFAULT NULL COMMENT 'Hora de chegada (decimal → TIME)',
  `hora_entrada` time DEFAULT NULL COMMENT 'Hora de entrada na eclusa (decimal → TIME)',
  `hora_saida` time DEFAULT NULL COMMENT 'Hora de saída da eclusa (decimal → TIME)',
  `observacoes` text COLLATE utf8mb4_unicode_ci COMMENT 'Observações adicionais',
  `cadastrado_por` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Automação SIGEP',
  `criado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_data_movimentacao` (`data_movimentacao`),
  KEY `idx_veiculo_id` (`veiculo_id`),
  KEY `idx_empresa_id` (`empresa_id`),
  KEY `idx_motorista_id` (`motorista_id`),
  CONSTRAINT `eclusa_movimentacoes_ibfk_1` FOREIGN KEY (`veiculo_id`) REFERENCES `eclusa_veiculos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `eclusa_movimentacoes_ibfk_2` FOREIGN KEY (`empresa_id`) REFERENCES `eclusa_empresas` (`id`) ON DELETE SET NULL,
  CONSTRAINT `eclusa_movimentacoes_ibfk_3` FOREIGN KEY (`motorista_id`) REFERENCES `eclusa_motoristas` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3908 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela eclusa_movimentacoes
```sql
-- PRIMARY: id (BTREE)
-- idx_data_movimentacao: data_movimentacao (BTREE)
-- idx_veiculo_id: veiculo_id (BTREE)
-- idx_empresa_id: empresa_id (BTREE)
-- idx_motorista_id: motorista_id (BTREE)
```

```sql
CREATE TABLE `eclusa_movimentacoes_auditoria` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_movimentacao` bigint unsigned NOT NULL,
  `acao` enum('INSERT','UPDATE','DELETE') COLLATE utf8mb4_unicode_ci NOT NULL,
  `snapshot_antes` json DEFAULT NULL,
  `snapshot_depois` json DEFAULT NULL,
  `observacao` text COLLATE utf8mb4_unicode_ci,
  `usuario_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_id_movimentacao` (`id_movimentacao`),
  KEY `idx_usuario_id` (`usuario_id`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `eclusa_movimentacoes_auditoria_ibfk_1` FOREIGN KEY (`id_movimentacao`) REFERENCES `eclusa_movimentacoes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Auditoria de alterações nas movimentações da eclusa';
```

### Índices da tabela eclusa_movimentacoes_auditoria
```sql
-- PRIMARY: id (BTREE)
-- idx_id_movimentacao: id_movimentacao (BTREE)
-- idx_usuario_id: usuario_id (BTREE)
-- idx_created_at: created_at (BTREE)
```

```sql
CREATE TABLE `eclusa_movimentacoes_escolta` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `data_cadastro` date NOT NULL,
  `interno` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'IPEN - NOME do interno',
  `hora_prevista` time DEFAULT NULL,
  `destino` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `motivo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Campo adicional se aplicável',
  `hora_chegada` time DEFAULT NULL,
  `hora_retorno` time DEFAULT NULL,
  `motorista` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `placa` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `eh_not` enum('Sim','Não') COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Veículo é do NOT?',
  `cadastrado_por` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Automação SIGEP',
  `criado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `interno_id` int DEFAULT NULL COMMENT 'Referência para ipen da tabela internos',
  PRIMARY KEY (`id`),
  KEY `idx_data_cadastro` (`data_cadastro`),
  KEY `idx_interno` (`interno`(50)),
  KEY `idx_destino` (`destino`(50)),
  KEY `idx_status` (`status`),
  KEY `idx_motorista` (`motorista`(50)),
  KEY `idx_placa` (`placa`),
  KEY `idx_eh_not` (`eh_not`),
  KEY `idx_interno_id` (`interno_id`),
  CONSTRAINT `fk_interno_id` FOREIGN KEY (`interno_id`) REFERENCES `internos` (`ipen`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2314 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela eclusa_movimentacoes_escolta
```sql
-- PRIMARY: id (BTREE)
-- idx_data_cadastro: data_cadastro (BTREE)
-- idx_interno: interno (BTREE)
-- idx_destino: destino (BTREE)
-- idx_status: status (BTREE)
-- idx_motorista: motorista (BTREE)
-- idx_placa: placa (BTREE)
-- idx_eh_not: eh_not (BTREE)
-- idx_interno_id: interno_id (BTREE)
```

```sql
CREATE TABLE `eclusa_veiculos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nome` varchar(150) NOT NULL,
  `modelo` varchar(150) NOT NULL,
  `placa` varchar(10) NOT NULL,
  `eh_viatura` tinyint(1) NOT NULL,
  `tipo_origem` enum('PIJ','PRJ','PFJ','NOT','Policia Civil','Policia Militar','Outra Policia Penal','Outros') NOT NULL DEFAULT 'Outros',
  `empresa_id` bigint unsigned DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `placa` (`placa`),
  KEY `fk_veiculos_eclusa_empresa` (`empresa_id`),
  CONSTRAINT `fk_veiculos_eclusa_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `eclusa_empresas` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2277 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
```

### Índices da tabela eclusa_veiculos
```sql
-- PRIMARY: id (BTREE)
-- placa: placa (BTREE)
-- fk_veiculos_eclusa_empresa: empresa_id (BTREE)
```

```sql
CREATE TABLE `eletronicos_capacidade` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tipo_item` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `capacidade` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `frequencia` int DEFAULT '1',
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_tipo_marca` (`tipo_item`,`capacidade`),
  KEY `idx_tipo` (`tipo_item`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela eletronicos_capacidade
```sql
-- PRIMARY: id (BTREE)
-- unique_tipo_marca: tipo_item (BTREE)
-- unique_tipo_marca: capacidade (BTREE)
-- idx_tipo: tipo_item (BTREE)
```

```sql
CREATE TABLE `eletronicos_comprimento` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tipo_item` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `comprimento` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `frequencia` int DEFAULT '1',
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_tipo_marca` (`tipo_item`,`comprimento`),
  KEY `idx_tipo` (`tipo_item`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela eletronicos_comprimento
```sql
-- PRIMARY: id (BTREE)
-- unique_tipo_marca: tipo_item (BTREE)
-- unique_tipo_marca: comprimento (BTREE)
-- idx_tipo: tipo_item (BTREE)
```

```sql
CREATE TABLE `eletronicos_cores` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tipo_item` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cor` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `frequencia` int DEFAULT '1',
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_tipo_cor` (`tipo_item`,`cor`),
  KEY `idx_tipo` (`tipo_item`)
) ENGINE=InnoDB AUTO_INCREMENT=481 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela eletronicos_cores
```sql
-- PRIMARY: id (BTREE)
-- unique_tipo_cor: tipo_item (BTREE)
-- unique_tipo_cor: cor (BTREE)
-- idx_tipo: tipo_item (BTREE)
```

```sql
CREATE TABLE `eletronicos_detalhes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tipo_item` varchar(50) NOT NULL,
  `campo` varchar(50) NOT NULL,
  `valor` varchar(100) NOT NULL,
  `frequencia` int DEFAULT '1',
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_tipo_campo_valor` (`tipo_item`,`campo`,`valor`)
) ENGINE=InnoDB AUTO_INCREMENT=128 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
```

### Índices da tabela eletronicos_detalhes
```sql
-- PRIMARY: id (BTREE)
-- unique_tipo_campo_valor: tipo_item (BTREE)
-- unique_tipo_campo_valor: campo (BTREE)
-- unique_tipo_campo_valor: valor (BTREE)
```

```sql
CREATE TABLE `eletronicos_donos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo` enum('interno','setor','outro') COLLATE utf8mb4_unicode_ci DEFAULT 'interno',
  `descricao` text COLLATE utf8mb4_unicode_ci,
  `ativo` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_nome` (`nome`),
  KEY `idx_tipo` (`tipo`),
  KEY `idx_ativo` (`ativo`)
) ENGINE=InnoDB AUTO_INCREMENT=1023 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela eletronicos_donos
```sql
-- PRIMARY: id (BTREE)
-- idx_nome: nome (BTREE)
-- idx_tipo: tipo (BTREE)
-- idx_ativo: ativo (BTREE)
```

```sql
CREATE TABLE `eletronicos_marcas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tipo_item` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `marca` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `frequencia` int DEFAULT '1',
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_tipo_marca` (`tipo_item`,`marca`),
  KEY `idx_tipo` (`tipo_item`)
) ENGINE=InnoDB AUTO_INCREMENT=369 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela eletronicos_marcas
```sql
-- PRIMARY: id (BTREE)
-- unique_tipo_marca: tipo_item (BTREE)
-- unique_tipo_marca: marca (BTREE)
-- idx_tipo: tipo_item (BTREE)
```

```sql
CREATE TABLE `eletronicos_polegadas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tipo_item` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `polegadas` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `frequencia` int DEFAULT '1',
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_tipo_marca` (`tipo_item`,`polegadas`),
  KEY `idx_tipo` (`tipo_item`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela eletronicos_polegadas
```sql
-- PRIMARY: id (BTREE)
-- unique_tipo_marca: tipo_item (BTREE)
-- unique_tipo_marca: polegadas (BTREE)
-- idx_tipo: tipo_item (BTREE)
```

```sql
CREATE TABLE `eletronicos_tamanho` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tipo_item` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tamanho` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `frequencia` int DEFAULT '1',
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_tipo_marca` (`tipo_item`,`tamanho`),
  KEY `idx_tipo` (`tipo_item`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela eletronicos_tamanho
```sql
-- PRIMARY: id (BTREE)
-- unique_tipo_marca: tipo_item (BTREE)
-- unique_tipo_marca: tamanho (BTREE)
-- idx_tipo: tipo_item (BTREE)
```

```sql
CREATE TABLE `eletronicos_tem_controle` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tipo_item` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tem_controle` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `frequencia` int DEFAULT '1',
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_tipo_marca` (`tipo_item`,`tem_controle`),
  KEY `idx_tipo` (`tipo_item`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela eletronicos_tem_controle
```sql
-- PRIMARY: id (BTREE)
-- unique_tipo_marca: tipo_item (BTREE)
-- unique_tipo_marca: tem_controle (BTREE)
-- idx_tipo: tipo_item (BTREE)
```

```sql
CREATE TABLE `eletronicos_tem_fonte` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tipo_item` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tem_fonte` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `frequencia` int DEFAULT '1',
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_tipo_marca` (`tipo_item`,`tem_fonte`),
  KEY `idx_tipo` (`tipo_item`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela eletronicos_tem_fonte
```sql
-- PRIMARY: id (BTREE)
-- unique_tipo_marca: tipo_item (BTREE)
-- unique_tipo_marca: tem_fonte (BTREE)
-- idx_tipo: tipo_item (BTREE)
```

```sql
CREATE TABLE `entregas_kits_eventos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `data_assinatura` date NOT NULL,
  `filtros_usados` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_geracao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=176 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela entregas_kits_eventos
```sql
-- PRIMARY: id (BTREE)
```

```sql
CREATE TABLE `internos` (
  `ipen` int NOT NULL,
  `nome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nome_social` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nome social escolhido pelo interno LGBT',
  `cpf` varchar(14) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lgbt` enum('S','N') COLLATE utf8mb4_unicode_ci DEFAULT 'N',
  `apelido` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `forma_pagamento` enum('Pix','Salário') COLLATE utf8mb4_unicode_ci DEFAULT 'Pix',
  `situacao` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ala` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `galeria` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bloco` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `piso` int DEFAULT NULL,
  `tipo_residencia` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `res` int DEFAULT NULL,
  `status` enum('A','I') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'A' COMMENT 'A=Ativo, I=Inativo',
  `regalia` enum('S','N') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'N' COMMENT 'S=Sim, N=Não',
  `regalia_galeria` enum('S','N') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'N',
  `cor_roupa` enum('Laranja','Verde') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `regalia_setor` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `regalia_kit` int DEFAULT NULL,
  `data_ativo` datetime DEFAULT NULL,
  `data_alterado` datetime DEFAULT NULL,
  `data_inativo` datetime DEFAULT NULL,
  `kit` int NOT NULL,
  `tamanho_kit` enum('P','M','G','G1','G2','G3') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'G',
  PRIMARY KEY (`ipen`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela internos
```sql
-- PRIMARY: ipen (BTREE)
```

```sql
CREATE TABLE `internos_colchoes_entradas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `data_entrada` date NOT NULL,
  `quantidade` int NOT NULL,
  `origem` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `documento_referencia` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_local_destino` int NOT NULL,
  `observacoes` text COLLATE utf8mb4_unicode_ci,
  `cadastrado_por` int DEFAULT NULL,
  `data_cadastro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_local_destino_entrada` (`id_local_destino`),
  CONSTRAINT `fk_local_destino_entrada` FOREIGN KEY (`id_local_destino`) REFERENCES `internos_colchoes_locais` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela internos_colchoes_entradas
```sql
-- PRIMARY: id (BTREE)
-- fk_local_destino_entrada: id_local_destino (BTREE)
```

```sql
CREATE TABLE `internos_colchoes_entregas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_interno` int NOT NULL,
  `id_saida` int NOT NULL,
  `data_entrega` date NOT NULL,
  `data_previsao_retorno` date DEFAULT NULL,
  `data_retorno_efetivo` date DEFAULT NULL,
  `status` enum('Entregue','Devolvido','Perdido','Danificado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Entregue',
  `observacoes_entrega` text COLLATE utf8mb4_unicode_ci,
  `observacoes_devolucao` text COLLATE utf8mb4_unicode_ci,
  `cadastrado_por` int DEFAULT NULL,
  `data_cadastro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_saida_entrega` (`id_saida`),
  KEY `fk_interno_entrega` (`id_interno`),
  CONSTRAINT `fk_interno_entrega` FOREIGN KEY (`id_interno`) REFERENCES `internos` (`ipen`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_saida_entrega` FOREIGN KEY (`id_saida`) REFERENCES `internos_colchoes_saidas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=328 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela internos_colchoes_entregas
```sql
-- PRIMARY: id (BTREE)
-- fk_saida_entrega: id_saida (BTREE)
-- fk_interno_entrega: id_interno (BTREE)
```

```sql
CREATE TABLE `internos_colchoes_estoque` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_local` int NOT NULL,
  `quantidade` int NOT NULL DEFAULT '0',
  `ultima_atualizacao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `atualizado_por` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `local_unico` (`id_local`),
  KEY `fk_local_estoque` (`id_local`),
  CONSTRAINT `fk_local_estoque` FOREIGN KEY (`id_local`) REFERENCES `internos_colchoes_locais` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=363 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela internos_colchoes_estoque
```sql
-- PRIMARY: id (BTREE)
-- local_unico: id_local (BTREE)
-- fk_local_estoque: id_local (BTREE)
```

```sql
CREATE TABLE `internos_colchoes_locais` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` text COLLATE utf8mb4_unicode_ci,
  `tipo` enum('Censura','Semiaberto','Outro') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Censura',
  `capacidade_maxima` int DEFAULT NULL,
  `status` enum('Ativo','Inativo') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Ativo',
  `criado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `criado_por` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nome` (`nome`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela internos_colchoes_locais
```sql
-- PRIMARY: id (BTREE)
-- nome: nome (BTREE)
```

```sql
CREATE TABLE `internos_colchoes_saidas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `data_saida` date NOT NULL,
  `quantidade` int NOT NULL DEFAULT '1',
  `tipo_destino` enum('Interno','Alojamento_Policia','Outro') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Interno',
  `id_local_origem` int NOT NULL,
  `id_interno` int DEFAULT NULL,
  `destino_outro` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `motivo_saida` text COLLATE utf8mb4_unicode_ci,
  `observacoes` text COLLATE utf8mb4_unicode_ci,
  `cadastrado_por` int DEFAULT NULL,
  `data_cadastro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('Ativo','Cancelado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Ativo',
  PRIMARY KEY (`id`),
  KEY `fk_local_origem_saida` (`id_local_origem`),
  KEY `fk_interno_saida` (`id_interno`),
  CONSTRAINT `fk_interno_saida` FOREIGN KEY (`id_interno`) REFERENCES `internos` (`ipen`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_local_origem_saida` FOREIGN KEY (`id_local_origem`) REFERENCES `internos_colchoes_locais` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=328 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela internos_colchoes_saidas
```sql
-- PRIMARY: id (BTREE)
-- fk_local_origem_saida: id_local_origem (BTREE)
-- fk_interno_saida: id_interno (BTREE)
```

```sql
CREATE TABLE `internos_colchoes_solicitacoes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_interno` int NOT NULL,
  `ipen` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nome_interno` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `galeria` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bloco` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `res` int DEFAULT NULL,
  `data_solicitacao` date NOT NULL,
  `data_ultimo_recebimento` date DEFAULT NULL,
  `dias_desde_ultimo` int DEFAULT NULL,
  `status_entrega` enum('SEM DIREITO','COM DIREITO','PRIORIDADE','PENDENTE') COLLATE utf8mb4_unicode_ci NOT NULL,
  `observacoes` text COLLATE utf8mb4_unicode_ci,
  `status_solicitacao` enum('Aberta','Atendida','Cancelada') COLLATE utf8mb4_unicode_ci DEFAULT 'Aberta',
  `data_atendimento` date DEFAULT NULL,
  `id_local_entrega` int DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `criado_por` int NOT NULL,
  `atualizado_em` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `atualizado_por` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_interno` (`id_interno`),
  KEY `criado_por` (`criado_por`),
  KEY `atualizado_por` (`atualizado_por`),
  KEY `id_local_entrega` (`id_local_entrega`),
  KEY `idx_ipen` (`ipen`),
  KEY `idx_status_entrega` (`status_entrega`),
  KEY `idx_status_solicitacao` (`status_solicitacao`),
  KEY `idx_data_solicitacao` (`data_solicitacao`),
  KEY `idx_data_ultimo_recebimento` (`data_ultimo_recebimento`),
  CONSTRAINT `internos_colchoes_solicitacoes_ibfk_1` FOREIGN KEY (`id_interno`) REFERENCES `internos` (`ipen`),
  CONSTRAINT `internos_colchoes_solicitacoes_ibfk_2` FOREIGN KEY (`criado_por`) REFERENCES `backup_acesso_seguro_20260406` (`id`),
  CONSTRAINT `internos_colchoes_solicitacoes_ibfk_3` FOREIGN KEY (`atualizado_por`) REFERENCES `backup_acesso_seguro_20260406` (`id`),
  CONSTRAINT `internos_colchoes_solicitacoes_ibfk_4` FOREIGN KEY (`id_local_entrega`) REFERENCES `internos_colchoes_locais` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=228 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela internos_colchoes_solicitacoes
```sql
-- PRIMARY: id (BTREE)
-- id_interno: id_interno (BTREE)
-- criado_por: criado_por (BTREE)
-- atualizado_por: atualizado_por (BTREE)
-- id_local_entrega: id_local_entrega (BTREE)
-- idx_ipen: ipen (BTREE)
-- idx_status_entrega: status_entrega (BTREE)
-- idx_status_solicitacao: status_solicitacao (BTREE)
-- idx_data_solicitacao: data_solicitacao (BTREE)
-- idx_data_ultimo_recebimento: data_ultimo_recebimento (BTREE)
```

```sql
CREATE TABLE `internos_condicoes_especiais` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ipen` int NOT NULL,
  `tipo` enum('Extinção de pena','Tornozeleira','Livramento condicional','Prisão albergue') COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_inicio` date NOT NULL,
  `data_fim` date NOT NULL,
  `observacoes` text COLLATE utf8mb4_unicode_ci,
  `status` enum('Ativa','Concluida','Cancelada') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Ativa',
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `criado_por` int NOT NULL,
  `data_conclusao` datetime DEFAULT NULL,
  `id_usuario_conclusao` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_condicao_ipen_tipo` (`ipen`,`tipo`),
  KEY `idx_condicao_ipen` (`ipen`),
  KEY `idx_condicao_status` (`status`),
  KEY `criado_por` (`criado_por`),
  KEY `id_usuario_conclusao` (`id_usuario_conclusao`),
  CONSTRAINT `internos_condicoes_especiais_ibfk_1` FOREIGN KEY (`ipen`) REFERENCES `internos` (`ipen`),
  CONSTRAINT `internos_condicoes_especiais_ibfk_2` FOREIGN KEY (`criado_por`) REFERENCES `backup_acesso_seguro_20260406` (`id`),
  CONSTRAINT `internos_condicoes_especiais_ibfk_3` FOREIGN KEY (`id_usuario_conclusao`) REFERENCES `backup_acesso_seguro_20260406` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela internos_condicoes_especiais
```sql
-- PRIMARY: id (BTREE)
-- uq_condicao_ipen_tipo: ipen (BTREE)
-- uq_condicao_ipen_tipo: tipo (BTREE)
-- idx_condicao_ipen: ipen (BTREE)
-- idx_condicao_status: status (BTREE)
-- criado_por: criado_por (BTREE)
-- id_usuario_conclusao: id_usuario_conclusao (BTREE)
```

```sql
CREATE TABLE `internos_ctc` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ipen` int NOT NULL,
  `data_ctc` date NOT NULL,
  `resultado` enum('Favorável','Desfavorável') COLLATE utf8mb4_unicode_ci NOT NULL,
  `decisao_juiz` enum('Favorável','Desfavorável') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_decisao` date DEFAULT NULL,
  `motivo_desfavoravel` text COLLATE utf8mb4_unicode_ci,
  `refazer` enum('Sim','Não') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Não',
  `data_proxima_ctc` date DEFAULT NULL,
  `observacoes` text COLLATE utf8mb4_unicode_ci,
  `status` enum('Ativo','Inativo','Aguardando') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Ativo',
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `criado_por` int NOT NULL,
  `atualizado_em` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `atualizado_por` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ctc_ipen_data` (`ipen`,`data_ctc`),
  KEY `idx_ctc_ipen` (`ipen`),
  KEY `idx_ctc_status` (`status`),
  KEY `idx_ctc_resultado` (`resultado`),
  KEY `criado_por` (`criado_por`),
  KEY `atualizado_por` (`atualizado_por`),
  CONSTRAINT `internos_ctc_ibfk_1` FOREIGN KEY (`ipen`) REFERENCES `internos` (`ipen`),
  CONSTRAINT `internos_ctc_ibfk_2` FOREIGN KEY (`criado_por`) REFERENCES `backup_acesso_seguro_20260406` (`id`),
  CONSTRAINT `internos_ctc_ibfk_3` FOREIGN KEY (`atualizado_por`) REFERENCES `backup_acesso_seguro_20260406` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela internos_ctc
```sql
-- PRIMARY: id (BTREE)
-- uq_ctc_ipen_data: ipen (BTREE)
-- uq_ctc_ipen_data: data_ctc (BTREE)
-- idx_ctc_ipen: ipen (BTREE)
-- idx_ctc_status: status (BTREE)
-- idx_ctc_resultado: resultado (BTREE)
-- criado_por: criado_por (BTREE)
-- atualizado_por: atualizado_por (BTREE)
```

```sql
CREATE TABLE `internos_ctc_exclusao` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ipen` int NOT NULL,
  `motivo` enum('Extinção de pena','Tornozeleira','Livramento condicional','Prisão albergue','Outro') COLLATE utf8mb4_unicode_ci NOT NULL,
  `motivo_outro` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_inicio` date NOT NULL,
  `data_fim` date DEFAULT NULL,
  `observacoes` text COLLATE utf8mb4_unicode_ci,
  `status` enum('Ativa','Concluida','Cancelada') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Ativa',
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `criado_por` int NOT NULL,
  `data_conclusao` datetime DEFAULT NULL,
  `id_usuario_conclusao` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ipen` (`ipen`),
  KEY `idx_status` (`status`),
  KEY `idx_data_fim` (`data_fim`),
  KEY `criado_por` (`criado_por`),
  KEY `id_usuario_conclusao` (`id_usuario_conclusao`),
  CONSTRAINT `internos_ctc_exclusao_ibfk_1` FOREIGN KEY (`ipen`) REFERENCES `internos` (`ipen`) ON DELETE CASCADE,
  CONSTRAINT `internos_ctc_exclusao_ibfk_2` FOREIGN KEY (`criado_por`) REFERENCES `backup_acesso_seguro_20260406` (`id`),
  CONSTRAINT `internos_ctc_exclusao_ibfk_3` FOREIGN KEY (`id_usuario_conclusao`) REFERENCES `backup_acesso_seguro_20260406` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela internos_ctc_exclusao
```sql
-- PRIMARY: id (BTREE)
-- idx_ipen: ipen (BTREE)
-- idx_status: status (BTREE)
-- idx_data_fim: data_fim (BTREE)
-- criado_por: criado_por (BTREE)
-- id_usuario_conclusao: id_usuario_conclusao (BTREE)
```

```sql
CREATE TABLE `internos_doacao_eletronicos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_doador` int NOT NULL,
  `id_receptor` int DEFAULT NULL,
  `galeria_receptor` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bloco_receptor` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cela_receptor` int DEFAULT NULL,
  `tipo_receptor` enum('CELA','INTERNO') COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('Pendente','Aprovado','Cancelado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pendente',
  `data_doacao` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `usuario_cadastro` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `termo_assinado` tinyint(1) DEFAULT '0',
  `observacoes` text COLLATE utf8mb4_unicode_ci,
  `data_aprovacao` datetime DEFAULT NULL,
  `data_cancelamento` datetime DEFAULT NULL,
  `motivo_cancelamento` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `idx_doador` (`id_doador`),
  KEY `idx_receptor` (`id_receptor`),
  KEY `idx_tipo` (`tipo_receptor`),
  KEY `idx_data` (`data_doacao`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela internos_doacao_eletronicos
```sql
-- PRIMARY: id (BTREE)
-- idx_doador: id_doador (BTREE)
-- idx_receptor: id_receptor (BTREE)
-- idx_tipo: tipo_receptor (BTREE)
-- idx_data: data_doacao (BTREE)
```

```sql
CREATE TABLE `internos_doacao_eletronicos_historico` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_doacao` int DEFAULT NULL,
  `id_item` int DEFAULT NULL,
  `acao` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `detalhes` text COLLATE utf8mb4_unicode_ci,
  `usuario` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_hora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_doacao` (`id_doacao`),
  KEY `idx_item` (`id_item`),
  KEY `idx_acao` (`acao`),
  KEY `idx_usuario` (`usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela internos_doacao_eletronicos_historico
```sql
-- PRIMARY: id (BTREE)
-- idx_doacao: id_doacao (BTREE)
-- idx_item: id_item (BTREE)
-- idx_acao: acao (BTREE)
-- idx_usuario: usuario (BTREE)
```

```sql
CREATE TABLE `internos_doacao_eletronicos_itens` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_doacao` int NOT NULL,
  `id_eletronico_original` int NOT NULL,
  `tipo_item` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `marca_modelo` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cor` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado_conservacao` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nota_fiscal` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_eletronico_transferido` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_doacao` (`id_doacao`),
  KEY `idx_eletronico` (`id_eletronico_original`),
  KEY `idx_id_eletronico_transferido` (`id_eletronico_transferido`),
  CONSTRAINT `internos_doacao_eletronicos_itens_ibfk_1` FOREIGN KEY (`id_doacao`) REFERENCES `internos_doacao_eletronicos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela internos_doacao_eletronicos_itens
```sql
-- PRIMARY: id (BTREE)
-- idx_doacao: id_doacao (BTREE)
-- idx_eletronico: id_eletronico_original (BTREE)
-- idx_id_eletronico_transferido: id_eletronico_transferido (BTREE)
```

```sql
CREATE TABLE `internos_eletronicos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_interno` int DEFAULT NULL,
  `id_dono` int DEFAULT NULL,
  `tipo_item` enum('TV','Radio','Ventilador','Chaleira','Maquina Cabelo','Extensao','Cabo Antena','Antena Digital','Bola','Banqueta','Violao','Outros','Chuveiro') COLLATE utf8mb4_unicode_ci NOT NULL,
  `marca_modelo` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cor` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Preto',
  `estado_conservacao` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Novo',
  `nota_fiscal` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `situacao` enum('Na Cela','Estoque','Retirado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Estoque',
  `data_entrada` datetime NOT NULL,
  `entregue_por` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cadastrado_por` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_entrega_interno` datetime DEFAULT NULL,
  `data_retirada` datetime DEFAULT NULL,
  `obs` text COLLATE utf8mb4_unicode_ci,
  `polegadas` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tem_controle` enum('Sim','Não') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Não',
  `tem_fonte` enum('Sim','Não') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Não',
  `tamanho` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Tamanho em cm ou metros',
  `capacidade` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `comprimento` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Comprimento em metros',
  `nome_item_personalizado` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Nome para itens Outros',
  `descricao_personalizada` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'DescriþÒo para itens Outros',
  `cela_atual` int DEFAULT NULL COMMENT 'IPEN do interno onde o item está atualmente (para itens de setores)',
  `sincronizado_ipen` enum('Sim','Não','Erro') COLLATE utf8mb4_unicode_ci DEFAULT 'Não',
  `data_sincronizacao` datetime DEFAULT NULL,
  `ipen_erro` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `fk_eletro_interno` (`id_interno`),
  KEY `idx_id_dono` (`id_dono`),
  CONSTRAINT `fk_eletro_dono` FOREIGN KEY (`id_dono`) REFERENCES `eletronicos_donos` (`id`),
  CONSTRAINT `fk_eletro_interno` FOREIGN KEY (`id_interno`) REFERENCES `internos` (`ipen`)
) ENGINE=InnoDB AUTO_INCREMENT=1389 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela internos_eletronicos
```sql
-- PRIMARY: id (BTREE)
-- fk_eletro_interno: id_interno (BTREE)
-- idx_id_dono: id_dono (BTREE)
```

```sql
CREATE TABLE `internos_eletronicos_doacoes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_item_origem` int NOT NULL COMMENT 'ID do item doado',
  `id_interno_origem` int NOT NULL COMMENT 'IPEN do doador',
  `id_interno_destino` int DEFAULT NULL COMMENT 'IPEN do receptor (NULL se for cela)',
  `galeria_destino` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Galeria da cela de destino',
  `cela_destino` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Cela de destino',
  `tipo_destino` enum('CELA','INTERNO') COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Tipo de destino',
  `motivo_doacao` text COLLATE utf8mb4_unicode_ci COMMENT 'Motivo da doaþÒo',
  `data_doacao` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `usuario_registro` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Quem registrou a doaþÒo',
  `observacoes` text COLLATE utf8mb4_unicode_ci COMMENT 'Observaþ§es adicionais',
  `status` enum('ATIVA','CANCELADA','CONCLUIDA') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ATIVA',
  PRIMARY KEY (`id`),
  KEY `idx_item_origem` (`id_item_origem`),
  KEY `idx_interno_origem` (`id_interno_origem`),
  KEY `idx_interno_destino` (`id_interno_destino`),
  KEY `idx_data_doacao` (`data_doacao`),
  KEY `idx_status` (`status`),
  CONSTRAINT `internos_eletronicos_doacoes_ibfk_1` FOREIGN KEY (`id_item_origem`) REFERENCES `internos_eletronicos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `internos_eletronicos_doacoes_ibfk_2` FOREIGN KEY (`id_interno_origem`) REFERENCES `internos` (`ipen`) ON DELETE CASCADE,
  CONSTRAINT `internos_eletronicos_doacoes_ibfk_3` FOREIGN KEY (`id_interno_destino`) REFERENCES `internos` (`ipen`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela internos_eletronicos_doacoes
```sql
-- PRIMARY: id (BTREE)
-- idx_item_origem: id_item_origem (BTREE)
-- idx_interno_origem: id_interno_origem (BTREE)
-- idx_interno_destino: id_interno_destino (BTREE)
-- idx_data_doacao: data_doacao (BTREE)
-- idx_status: status (BTREE)
```

```sql
CREATE TABLE `internos_eletronicos_historico` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_eletronico` int NOT NULL,
  `acao` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `detalhes` text COLLATE utf8mb4_unicode_ci,
  `usuario` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_hora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3054 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela internos_eletronicos_historico
```sql
-- PRIMARY: id (BTREE)
```

```sql
CREATE TABLE `internos_entregas_itens` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_evento` int DEFAULT NULL,
  `ipen` int NOT NULL,
  `status_entrega` enum('Pendente','Entregue') COLLATE utf8mb4_unicode_ci DEFAULT 'Pendente',
  `data_confirmacao` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_evento` (`id_evento`),
  CONSTRAINT `internos_entregas_itens_ibfk_1` FOREIGN KEY (`id_evento`) REFERENCES `entregas_kits_eventos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2950 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela internos_entregas_itens
```sql
-- PRIMARY: id (BTREE)
-- id_evento: id_evento (BTREE)
```

```sql
CREATE TABLE `internos_historico` (
  `id` int NOT NULL AUTO_INCREMENT,
  `data_importacao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `registros_novos` int DEFAULT '0',
  `registros_atualizados` int DEFAULT '0',
  `registros_inativados` int DEFAULT '0',
  `total_importados` int DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=137 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela internos_historico
```sql
-- PRIMARY: id (BTREE)
```

```sql
CREATE TABLE `internos_historico_detalhado` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ipen` int NOT NULL,
  `campo` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `valor_antigo` text COLLATE utf8mb4_unicode_ci,
  `valor_novo` text COLLATE utf8mb4_unicode_ci,
  `data_alteracao` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `operacao` enum('INSERIDO','ATUALIZADO','INATIVADO') COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=35360 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela internos_historico_detalhado
```sql
-- PRIMARY: id (BTREE)
```

```sql
CREATE TABLE `internos_laboral` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ipen` int NOT NULL,
  `estabelecimento` varchar(255) NOT NULL,
  `remicao_inicio` date DEFAULT NULL,
  `remicao_fim` date DEFAULT NULL,
  `liberacao_inicio` date DEFAULT NULL,
  `liberacao_fim` date DEFAULT NULL,
  `dias_semana` varchar(50) DEFAULT NULL,
  `dias_semana_json` json DEFAULT NULL,
  `status` enum('A','I') NOT NULL DEFAULT 'A',
  `data_ativo` datetime NOT NULL,
  `data_alterado` datetime DEFAULT NULL,
  `data_inativo` datetime DEFAULT NULL,
  `importado_em` datetime NOT NULL,
  `importado_por` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_interno_laboral_ipen` (`ipen`,`status`),
  KEY `idx_laboral_status` (`status`),
  KEY `idx_laboral_ipen` (`ipen`)
) ENGINE=InnoDB AUTO_INCREMENT=1262 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
```

### Índices da tabela internos_laboral
```sql
-- PRIMARY: id (BTREE)
-- uq_interno_laboral_ipen: ipen (BTREE)
-- uq_interno_laboral_ipen: status (BTREE)
-- idx_laboral_status: status (BTREE)
-- idx_laboral_ipen: ipen (BTREE)
```

```sql
CREATE TABLE `internos_laboral_historico` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `data_importacao` datetime NOT NULL,
  `total_importados` int NOT NULL DEFAULT '0',
  `registros_novos` int NOT NULL DEFAULT '0',
  `registros_atualizados` int NOT NULL DEFAULT '0',
  `registros_inativados` int NOT NULL DEFAULT '0',
  `internos_encontrados` int NOT NULL DEFAULT '0',
  `internos_nao_encontrados` int NOT NULL DEFAULT '0',
  `importado_por` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_laboral_hist_data` (`data_importacao`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
```

### Índices da tabela internos_laboral_historico
```sql
-- PRIMARY: id (BTREE)
-- idx_laboral_hist_data: data_importacao (BTREE)
```

```sql
CREATE TABLE `internos_laboral_historico_detalhado` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_historico` bigint unsigned DEFAULT NULL,
  `id_interno_laboral` bigint unsigned DEFAULT NULL,
  `ipen` int NOT NULL,
  `campo` varchar(80) NOT NULL,
  `valor_antigo` text,
  `valor_novo` text,
  `data_alteracao` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `operacao` enum('INSERIDO','ATUALIZADO','INATIVADO','REATIVADO','EXCLUIDO') NOT NULL,
  `alterado_por` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_laboral_det_hist` (`id_historico`),
  KEY `idx_laboral_det_ipen` (`ipen`),
  KEY `idx_laboral_det_data` (`data_alteracao`)
) ENGINE=InnoDB AUTO_INCREMENT=2337 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
```

### Índices da tabela internos_laboral_historico_detalhado
```sql
-- PRIMARY: id (BTREE)
-- idx_laboral_det_hist: id_historico (BTREE)
-- idx_laboral_det_ipen: ipen (BTREE)
-- idx_laboral_det_data: data_alteracao (BTREE)
```

```sql
CREATE TABLE `internos_md_anexos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_medida` int NOT NULL,
  `nome_arquivo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `arquivo_original` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo_arquivo` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tamanho_arquivo` int NOT NULL,
  `caminho_completo` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_upload` datetime DEFAULT CURRENT_TIMESTAMP,
  `id_usuario_upload` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_id_medida` (`id_medida`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela internos_md_anexos
```sql
-- PRIMARY: id (BTREE)
-- idx_id_medida: id_medida (BTREE)
```

```sql
CREATE TABLE `internos_md_itens_apreendidos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_medida` int NOT NULL,
  `tipo_item` enum('TV','Radio','Ventilador','Maquina Cabelo','Chaleira','Outro') COLLATE utf8mb4_unicode_ci NOT NULL,
  `marca` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `modelo` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cor` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descricao` text COLLATE utf8mb4_unicode_ci,
  `local_retido` enum('Coordenação','Censura','Direção') COLLATE utf8mb4_unicode_ci NOT NULL,
  `status_item` enum('Retido','Devolvido','Perdido') COLLATE utf8mb4_unicode_ci DEFAULT 'Retido',
  `data_devolucao` datetime DEFAULT NULL,
  `id_usuario_devolucao` int DEFAULT NULL,
  `observacoes_devolucao` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `idx_id_medida` (`id_medida`),
  KEY `idx_status_item` (`status_item`),
  KEY `idx_local_retido` (`local_retido`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela internos_md_itens_apreendidos
```sql
-- PRIMARY: id (BTREE)
-- idx_id_medida: id_medida (BTREE)
-- idx_status_item: status_item (BTREE)
-- idx_local_retido: local_retido (BTREE)
```

```sql
CREATE TABLE `internos_md_medidas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_interno` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'IPEN do interno',
  `data_inicio` date NOT NULL,
  `data_fim` date NOT NULL,
  `motivo` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `local_castigo` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Local onde cumpre o castigo',
  `status` enum('Ativa','Concluida','Cancelada') COLLATE utf8mb4_unicode_ci DEFAULT 'Ativa',
  `id_usuario_cadastro` int NOT NULL COMMENT 'ID do usuário que cadastrou',
  `data_cadastro` datetime DEFAULT CURRENT_TIMESTAMP,
  `data_conclusao` datetime DEFAULT NULL,
  `id_usuario_conclusao` int DEFAULT NULL COMMENT 'ID do usuário que concluiu',
  `observacoes` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `idx_id_interno` (`id_interno`),
  KEY `idx_status` (`status`),
  KEY `idx_datas` (`data_inicio`,`data_fim`),
  KEY `idx_vencimento` (`data_fim`,`status`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela internos_md_medidas
```sql
-- PRIMARY: id (BTREE)
-- idx_id_interno: id_interno (BTREE)
-- idx_status: status (BTREE)
-- idx_datas: data_inicio (BTREE)
-- idx_datas: data_fim (BTREE)
-- idx_vencimento: data_fim (BTREE)
-- idx_vencimento: status (BTREE)
```

```sql
CREATE TABLE `internos_md_notificacoes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_usuario` int NOT NULL,
  `tipo_notificacao` enum('md_vencimento','item_retido') COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_referencia` int NOT NULL COMMENT 'ID da MD ou do item apreendido',
  `titulo` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mensagem` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `lida` enum('N','S') COLLATE utf8mb4_unicode_ci DEFAULT 'N',
  `data_criacao` datetime DEFAULT CURRENT_TIMESTAMP,
  `data_leitura` datetime DEFAULT NULL,
  `acao` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'URL ou ação quando clicar',
  PRIMARY KEY (`id`),
  KEY `idx_id_usuario` (`id_usuario`),
  KEY `idx_lida` (`lida`),
  KEY `idx_tipo` (`tipo_notificacao`),
  KEY `idx_data_criacao` (`data_criacao`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela internos_md_notificacoes
```sql
-- PRIMARY: id (BTREE)
-- idx_id_usuario: id_usuario (BTREE)
-- idx_lida: lida (BTREE)
-- idx_tipo: tipo_notificacao (BTREE)
-- idx_data_criacao: data_criacao (BTREE)
```

```sql
CREATE TABLE `internos_recebimento_cosmeticos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_interno` int NOT NULL,
  `data_recebimento` datetime NOT NULL,
  `entregue_por_tipo` enum('Visitante','Advogado','Correios','Laboral') COLLATE utf8mb4_unicode_ci NOT NULL,
  `entregue_por_nome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cadastrado_por` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_entrega_interno` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_cosm_interno` (`id_interno`),
  CONSTRAINT `fk_cosm_interno` FOREIGN KEY (`id_interno`) REFERENCES `internos` (`ipen`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela internos_recebimento_cosmeticos
```sql
-- PRIMARY: id (BTREE)
-- fk_cosm_interno: id_interno (BTREE)
```

```sql
CREATE TABLE `internos_recebimento_cosmeticos_historico` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tabela_afetada` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_registro` int NOT NULL,
  `operacao` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `valor_antigo` text COLLATE utf8mb4_unicode_ci,
  `valor_novo` text COLLATE utf8mb4_unicode_ci,
  `usuario` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_hora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=72 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela internos_recebimento_cosmeticos_historico
```sql
-- PRIMARY: id (BTREE)
```

```sql
CREATE TABLE `internos_recebimento_cosmeticos_itens` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_recebimento` int NOT NULL,
  `item` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantidade` int NOT NULL,
  `detalhes` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_cosm_item_rec` (`id_recebimento`),
  CONSTRAINT `fk_cosm_item_rec` FOREIGN KEY (`id_recebimento`) REFERENCES `internos_recebimento_cosmeticos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=121 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela internos_recebimento_cosmeticos_itens
```sql
-- PRIMARY: id (BTREE)
-- fk_cosm_item_rec: id_recebimento (BTREE)
```

```sql
CREATE TABLE `internos_recebimento_cosmeticos_limites` (
  `id` int NOT NULL AUTO_INCREMENT,
  `item_nome` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantidade_maxima` int NOT NULL,
  `regras` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela internos_recebimento_cosmeticos_limites
```sql
-- PRIMARY: id (BTREE)
```

```sql
CREATE TABLE `internos_recebimento_livros` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_interno` int NOT NULL,
  `data_recebimento` datetime NOT NULL,
  `entregue_por_tipo` enum('Visitante','Advogado','Correios','Doação') COLLATE utf8mb4_unicode_ci NOT NULL,
  `entregue_por_nome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cadastrado_por` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_entrega_interno` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_livros_interno` (`id_interno`),
  CONSTRAINT `fk_livros_interno` FOREIGN KEY (`id_interno`) REFERENCES `internos` (`ipen`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela internos_recebimento_livros
```sql
-- PRIMARY: id (BTREE)
-- fk_livros_interno: id_interno (BTREE)
```

```sql
CREATE TABLE `internos_recebimento_livros_historico` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tabela_afetada` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_registro` int NOT NULL,
  `operacao` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `valor_antigo` text COLLATE utf8mb4_unicode_ci,
  `valor_novo` text COLLATE utf8mb4_unicode_ci,
  `usuario` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_hora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=65 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela internos_recebimento_livros_historico
```sql
-- PRIMARY: id (BTREE)
```

```sql
CREATE TABLE `internos_recebimento_livros_itens` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_recebimento` int NOT NULL,
  `titulo_livro` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `autor` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado_conservacao` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Bom',
  PRIMARY KEY (`id`),
  KEY `fk_livros_item_rec` (`id_recebimento`),
  CONSTRAINT `fk_livros_item_rec` FOREIGN KEY (`id_recebimento`) REFERENCES `internos_recebimento_livros` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela internos_recebimento_livros_itens
```sql
-- PRIMARY: id (BTREE)
-- fk_livros_item_rec: id_recebimento (BTREE)
```

```sql
CREATE TABLE `internos_recebimento_roupas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_interno` int NOT NULL,
  `id_periodo` int NOT NULL,
  `data_recebimento` datetime NOT NULL,
  `entregue_por_tipo` enum('Visitante','Advogado') COLLATE utf8mb4_unicode_ci NOT NULL,
  `entregue_por_nome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cadastrado_por` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_entrega_interno` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_rec_interno` (`id_interno`),
  CONSTRAINT `fk_rec_interno` FOREIGN KEY (`id_interno`) REFERENCES `internos` (`ipen`)
) ENGINE=InnoDB AUTO_INCREMENT=280 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela internos_recebimento_roupas
```sql
-- PRIMARY: id (BTREE)
-- fk_rec_interno: id_interno (BTREE)
```

```sql
CREATE TABLE `internos_recebimento_roupas_historico` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tabela_afetada` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_registro` int NOT NULL,
  `operacao` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `valor_antigo` text COLLATE utf8mb4_unicode_ci,
  `valor_novo` text COLLATE utf8mb4_unicode_ci,
  `usuario` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_hora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1518 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela internos_recebimento_roupas_historico
```sql
-- PRIMARY: id (BTREE)
```

```sql
CREATE TABLE `internos_recebimento_roupas_itens` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_recebimento` int NOT NULL,
  `item` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantidade` int NOT NULL,
  `detalhes` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_rec_item_recebimento` (`id_recebimento`),
  CONSTRAINT `fk_rec_item_recebimento` FOREIGN KEY (`id_recebimento`) REFERENCES `internos_recebimento_roupas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=959 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela internos_recebimento_roupas_itens
```sql
-- PRIMARY: id (BTREE)
-- fk_rec_item_recebimento: id_recebimento (BTREE)
```

```sql
CREATE TABLE `internos_recebimento_roupas_limites` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_periodo` int NOT NULL,
  `item_nome` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantidade_maxima` int NOT NULL,
  `regras_especificas` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `fk_limite_periodo` (`id_periodo`),
  CONSTRAINT `fk_limite_periodo` FOREIGN KEY (`id_periodo`) REFERENCES `internos_recebimento_roupas_periodos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela internos_recebimento_roupas_limites
```sql
-- PRIMARY: id (BTREE)
-- fk_limite_periodo: id_periodo (BTREE)
```

```sql
CREATE TABLE `internos_recebimento_roupas_periodos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_inicio` date NOT NULL,
  `data_fim` date NOT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela internos_recebimento_roupas_periodos
```sql
-- PRIMARY: id (BTREE)
```

```sql
CREATE TABLE `internos_rouparia_civil` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ipen` int NOT NULL,
  `nome` varchar(255) NOT NULL,
  `pecas` json NOT NULL COMMENT 'JSON com peças e quantidades',
  `criado_por` varchar(255) NOT NULL,
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=625 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
```

### Índices da tabela internos_rouparia_civil
```sql
-- PRIMARY: id (BTREE)
```

```sql
CREATE TABLE `internos_rouparia_civil_historico` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_rouparia` int NOT NULL,
  `acao` enum('INSERT','UPDATE','DELETE') NOT NULL,
  `dados_antigos` json DEFAULT NULL,
  `dados_novos` json DEFAULT NULL,
  `alterado_por` varchar(255) NOT NULL,
  `alterado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `id_rouparia` (`id_rouparia`),
  CONSTRAINT `internos_rouparia_civil_historico_ibfk_1` FOREIGN KEY (`id_rouparia`) REFERENCES `internos_rouparia_civil` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
```

### Índices da tabela internos_rouparia_civil_historico
```sql
-- PRIMARY: id (BTREE)
-- id_rouparia: id_rouparia (BTREE)
```

```sql
CREATE TABLE `internos_termo_kit_tipos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` text COLLATE utf8mb4_unicode_ci,
  `ativo` enum('S','N') COLLATE utf8mb4_unicode_ci DEFAULT 'S',
  `data_cadastro` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ativo` (`ativo`),
  KEY `idx_nome` (`nome`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela internos_termo_kit_tipos
```sql
-- PRIMARY: id (BTREE)
-- idx_ativo: ativo (BTREE)
-- idx_nome: nome (BTREE)
```

```sql
CREATE TABLE `internos_termos_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_termo` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo_id` int NOT NULL,
  `tipo_nome` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_assinatura` date NOT NULL,
  `internos_json` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `filtros_usados` text COLLATE utf8mb4_unicode_ci,
  `data_cadastro` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_termo` (`id_termo`),
  KEY `idx_id_termo` (`id_termo`),
  KEY `idx_tipo_id` (`tipo_id`),
  KEY `idx_data_assinatura` (`data_assinatura`),
  KEY `idx_data_cadastro` (`data_cadastro`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela internos_termos_log
```sql
-- PRIMARY: id (BTREE)
-- id_termo: id_termo (BTREE)
-- idx_id_termo: id_termo (BTREE)
-- idx_tipo_id: tipo_id (BTREE)
-- idx_data_assinatura: data_assinatura (BTREE)
-- idx_data_cadastro: data_cadastro (BTREE)
```

```sql
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
```

### Índices da tabela job_batches
```sql
-- PRIMARY: id (BTREE)
```

```sql
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
```

### Índices da tabela jobs
```sql
-- PRIMARY: id (BTREE)
-- jobs_queue_index: queue (BTREE)
```

```sql
CREATE TABLE `jobs_control` (
  `id` int NOT NULL AUTO_INCREMENT,
  `job_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `job_type` enum('EVENT','PROCEDURE','MANUAL') COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `status` enum('ACTIVE','INACTIVE','RUNNING','COMPLETED','FAILED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'INACTIVE',
  `last_run` datetime DEFAULT NULL,
  `next_run` datetime DEFAULT NULL,
  `run_count` int DEFAULT '0',
  `success_count` int DEFAULT '0',
  `error_count` int DEFAULT '0',
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `job_name` (`job_name`),
  KEY `idx_status` (`status`),
  KEY `idx_next_run` (`next_run`),
  KEY `idx_job_name` (`job_name`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela jobs_control
```sql
-- PRIMARY: id (BTREE)
-- job_name: job_name (BTREE)
-- idx_status: status (BTREE)
-- idx_next_run: next_run (BTREE)
-- idx_job_name: job_name (BTREE)
```

```sql
CREATE TABLE `laboral_controle_dividas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ipen` int NOT NULL,
  `cpf` varchar(14) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `autos` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipo_divida` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pensão',
  `valor_divida` decimal(10,2) DEFAULT NULL,
  `salario_base` decimal(10,2) DEFAULT NULL,
  `valor_atual` decimal(10,2) DEFAULT NULL,
  `percentual_desconto` decimal(5,2) NOT NULL DEFAULT '25.00',
  `status` enum('A','I') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'A',
  `data_cadastro` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `data_alterado` datetime DEFAULT NULL,
  `usuario_cadastro` int NOT NULL,
  `usuario_alterado` int DEFAULT NULL,
  `data_ultima_atualizacao` datetime DEFAULT NULL,
  `status_detalhado` enum('Pendente','Ativa','Suspensa','Quitada','Inativa') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_quitacao` datetime DEFAULT NULL,
  `pensao_favorecido` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pensao_banco` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pensao_agencia` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pensao_conta` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pensao_op` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pensao_tipo_conta` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'Corrente',
  `pensao_determinacao` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `idx_ipen` (`ipen`),
  KEY `idx_autos` (`autos`),
  KEY `idx_status` (`status`),
  KEY `idx_percentual_desconto` (`percentual_desconto`),
  KEY `idx_data_cadastro` (`data_cadastro`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela laboral_controle_dividas
```sql
-- PRIMARY: id (BTREE)
-- idx_ipen: ipen (BTREE)
-- idx_autos: autos (BTREE)
-- idx_status: status (BTREE)
-- idx_percentual_desconto: percentual_desconto (BTREE)
-- idx_data_cadastro: data_cadastro (BTREE)
```

```sql
CREATE TABLE `laboral_controle_dividas_descontos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `multa_id` bigint unsigned NOT NULL,
  `ipen` int NOT NULL,
  `mes_referencia` varchar(7) COLLATE utf8mb4_unicode_ci NOT NULL,
  `salario_real` decimal(10,2) NOT NULL,
  `percentual_desconto` decimal(5,2) NOT NULL,
  `valor_desconto` decimal(10,2) NOT NULL,
  `saldo_anterior` decimal(10,2) DEFAULT NULL,
  `saldo_novo` decimal(10,2) DEFAULT NULL,
  `status` enum('Pendente','Processado','Cancelado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pendente',
  `data_lancamento` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `usuario_lancamento` int NOT NULL,
  `observacoes` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_mes_multa` (`multa_id`,`mes_referencia`),
  KEY `idx_multa_id` (`multa_id`),
  KEY `idx_ipen` (`ipen`),
  KEY `idx_mes_referencia` (`mes_referencia`),
  KEY `idx_status` (`status`),
  KEY `idx_data_lancamento` (`data_lancamento`),
  CONSTRAINT `laboral_controle_dividas_descontos_ibfk_1` FOREIGN KEY (`multa_id`) REFERENCES `laboral_controle_dividas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=47 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela laboral_controle_dividas_descontos
```sql
-- PRIMARY: id (BTREE)
-- unique_mes_multa: multa_id (BTREE)
-- unique_mes_multa: mes_referencia (BTREE)
-- idx_multa_id: multa_id (BTREE)
-- idx_ipen: ipen (BTREE)
-- idx_mes_referencia: mes_referencia (BTREE)
-- idx_status: status (BTREE)
-- idx_data_lancamento: data_lancamento (BTREE)
```

```sql
CREATE TABLE `laboral_controle_dividas_historico` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `multa_id` bigint unsigned NOT NULL,
  `ipen` int NOT NULL,
  `tipo_divida` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo_acao` enum('INSERT','UPDATE','DELETE','LANCAMENTO','STATUS_CHANGE','CORRECAO') COLLATE utf8mb4_unicode_ci NOT NULL,
  `tabela_origem` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `registro_origem_id` bigint unsigned DEFAULT NULL,
  `dados_antes` json DEFAULT NULL,
  `dados_depois` json DEFAULT NULL,
  `mes_referencia` varchar(7) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `valor_movimento` decimal(10,2) DEFAULT NULL,
  `saldo_anterior` decimal(10,2) DEFAULT NULL,
  `saldo_novo` decimal(10,2) DEFAULT NULL,
  `descricao` text COLLATE utf8mb4_unicode_ci,
  `usuario_responsavel` int NOT NULL,
  `data_acao` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `session_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_multa_id` (`multa_id`),
  KEY `idx_ipen` (`ipen`),
  KEY `idx_tipo_acao` (`tipo_acao`),
  KEY `idx_tabela_origem` (`tabela_origem`),
  KEY `idx_registro_origem` (`registro_origem_id`),
  KEY `idx_data_acao` (`data_acao`),
  KEY `idx_usuario_responsavel` (`usuario_responsavel`),
  KEY `idx_mes_referencia` (`mes_referencia`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela laboral_controle_dividas_historico
```sql
-- PRIMARY: id (BTREE)
-- idx_multa_id: multa_id (BTREE)
-- idx_ipen: ipen (BTREE)
-- idx_tipo_acao: tipo_acao (BTREE)
-- idx_tabela_origem: tabela_origem (BTREE)
-- idx_registro_origem: registro_origem_id (BTREE)
-- idx_data_acao: data_acao (BTREE)
-- idx_usuario_responsavel: usuario_responsavel (BTREE)
-- idx_mes_referencia: mes_referencia (BTREE)
```

```sql
CREATE TABLE `manutencao_servicos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_eletronico` int NOT NULL,
  `id_interno` int DEFAULT NULL,
  `tipo_servico` enum('instalacao','manutencao','reparo','remocao','transferencia') COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_solicitacao` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `data_execucao` datetime DEFAULT NULL,
  `usuario_solicitante` int NOT NULL,
  `usuario_executante` int DEFAULT NULL,
  `status` enum('pendente','executado','cancelado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendente',
  `observacoes` text COLLATE utf8mb4_unicode_ci,
  `cela_destino` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_id_eletronico` (`id_eletronico`),
  KEY `idx_id_interno` (`id_interno`),
  KEY `idx_status` (`status`),
  KEY `idx_data_solicitacao` (`data_solicitacao`),
  KEY `idx_tipo_servico` (`tipo_servico`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela manutencao_servicos
```sql
-- PRIMARY: id (BTREE)
-- idx_id_eletronico: id_eletronico (BTREE)
-- idx_id_interno: id_interno (BTREE)
-- idx_status: status (BTREE)
-- idx_data_solicitacao: data_solicitacao (BTREE)
-- idx_tipo_servico: tipo_servico (BTREE)
```

```sql
CREATE TABLE `manutencao_servicos_auditoria` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_servico` int NOT NULL,
  `data_acao` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `usuario_acao` int NOT NULL,
  `tipo_acao` enum('criacao','execucao','cancelamento','alteracao') COLLATE utf8mb4_unicode_ci NOT NULL,
  `campo_alterado` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `valor_antigo` text COLLATE utf8mb4_unicode_ci,
  `valor_novo` text COLLATE utf8mb4_unicode_ci,
  `observacoes` text COLLATE utf8mb4_unicode_ci,
  `ip_usuario` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_id_servico` (`id_servico`),
  KEY `idx_data_acao` (`data_acao`),
  KEY `idx_usuario_acao` (`usuario_acao`),
  KEY `idx_tipo_acao` (`tipo_acao`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela manutencao_servicos_auditoria
```sql
-- PRIMARY: id (BTREE)
-- idx_id_servico: id_servico (BTREE)
-- idx_data_acao: data_acao (BTREE)
-- idx_usuario_acao: usuario_acao (BTREE)
-- idx_tipo_acao: tipo_acao (BTREE)
```

```sql
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
```

### Índices da tabela migrations
```sql
-- PRIMARY: id (BTREE)
```

```sql
CREATE TABLE `monitores_solucoes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nome` varchar(150) NOT NULL,
  `cargo` enum('Monitor','Supervisor') NOT NULL,
  `equipe` enum('alpha','beta','charlie','delta') NOT NULL DEFAULT 'alpha',
  `criado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nome` (`nome`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
```

### Índices da tabela monitores_solucoes
```sql
-- PRIMARY: id (BTREE)
-- nome: nome (BTREE)
```

```sql
CREATE TABLE `notificacao_canais` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` text COLLATE utf8mb4_unicode_ci,
  `ativo` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_nome` (`nome`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela notificacao_canais
```sql
-- PRIMARY: id (BTREE)
-- unique_nome: nome (BTREE)
```

```sql
CREATE TABLE `notificacao_canal_inscricoes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `canal_id` int NOT NULL,
  `tipo` enum('user','setor') COLLATE utf8mb4_unicode_ci NOT NULL,
  `identificador` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_inscricao` (`canal_id`,`tipo`,`identificador`),
  CONSTRAINT `notificacao_canal_inscricoes_ibfk_1` FOREIGN KEY (`canal_id`) REFERENCES `notificacao_canais` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela notificacao_canal_inscricoes
```sql
-- PRIMARY: id (BTREE)
-- unique_inscricao: canal_id (BTREE)
-- unique_inscricao: tipo (BTREE)
-- unique_inscricao: identificador (BTREE)
```

```sql
CREATE TABLE `notificacao_canal_tipos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `canal_id` int NOT NULL,
  `tipo_notificacao` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ativo` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_canal_tipo` (`canal_id`,`tipo_notificacao`),
  CONSTRAINT `notificacao_canal_tipos_ibfk_1` FOREIGN KEY (`canal_id`) REFERENCES `notificacao_canais` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela notificacao_canal_tipos
```sql
-- PRIMARY: id (BTREE)
-- unique_canal_tipo: canal_id (BTREE)
-- unique_canal_tipo: tipo_notificacao (BTREE)
```

```sql
CREATE TABLE `notificacoes_preferencias` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `tipo` varchar(50) NOT NULL,
  `ativa` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_tipo` (`user_id`,`tipo`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
```

### Índices da tabela notificacoes_preferencias
```sql
-- PRIMARY: id (BTREE)
-- unique_user_tipo: user_id (BTREE)
-- unique_user_tipo: tipo (BTREE)
```

```sql
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
```

### Índices da tabela password_reset_tokens
```sql
-- PRIMARY: email (BTREE)
```

```sql
CREATE TABLE `peculio_controle` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ipen` int NOT NULL,
  `nome_na_epoca` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `local_na_epoca` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mes_referencia` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `valor_total` decimal(10,2) DEFAULT '0.00',
  `status_entrega` enum('Pendente','Entregue','Não Entregue') COLLATE utf8mb4_unicode_ci DEFAULT 'Pendente',
  `incidencia` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'Nenhuma',
  `observacao` text COLLATE utf8mb4_unicode_ci,
  `data_geracao` datetime DEFAULT CURRENT_TIMESTAMP,
  `data_entrega` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_pedido` (`ipen`,`mes_referencia`)
) ENGINE=InnoDB AUTO_INCREMENT=104 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela peculio_controle
```sql
-- PRIMARY: id (BTREE)
-- unique_pedido: ipen (BTREE)
-- unique_pedido: mes_referencia (BTREE)
```

```sql
CREATE TABLE `peculio_controle_itens` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_controle` int NOT NULL,
  `id_item` int NOT NULL,
  `quantidade` int NOT NULL DEFAULT '1',
  `status_item` enum('OK','Faltante','Defeito') COLLATE utf8mb4_unicode_ci DEFAULT 'OK',
  `qtd_faltante` int DEFAULT '0',
  `qtd_defeito` int DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `id_controle` (`id_controle`),
  KEY `id_item` (`id_item`),
  CONSTRAINT `peculio_controle_itens_ibfk_1` FOREIGN KEY (`id_controle`) REFERENCES `peculio_controle` (`id`) ON DELETE CASCADE,
  CONSTRAINT `peculio_controle_itens_ibfk_2` FOREIGN KEY (`id_item`) REFERENCES `peculio_itens` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=246 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela peculio_controle_itens
```sql
-- PRIMARY: id (BTREE)
-- id_controle: id_controle (BTREE)
-- id_item: id_item (BTREE)
```

```sql
CREATE TABLE `peculio_historico` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_controle` int NOT NULL,
  `status_tentativa` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `incidencia` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `itens_problema` text COLLATE utf8mb4_unicode_ci,
  `observacao` text COLLATE utf8mb4_unicode_ci,
  `data_registro` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `id_controle` (`id_controle`),
  CONSTRAINT `peculio_historico_ibfk_1` FOREIGN KEY (`id_controle`) REFERENCES `peculio_controle` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela peculio_historico
```sql
-- PRIMARY: id (BTREE)
-- id_controle: id_controle (BTREE)
```

```sql
CREATE TABLE `peculio_itens` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `maximo` int DEFAULT '1',
  `preco` decimal(10,2) DEFAULT '0.00',
  `ordem` int DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=64 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela peculio_itens
```sql
-- PRIMARY: id (BTREE)
```

```sql
CREATE TABLE `peculio_saldos_pix` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ipen` int NOT NULL,
  `mes_referencia` varchar(7) COLLATE utf8mb4_unicode_ci NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `data_cadastro` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_pix_mes` (`ipen`,`mes_referencia`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela peculio_saldos_pix
```sql
-- PRIMARY: id (BTREE)
-- unique_pix_mes: ipen (BTREE)
-- unique_pix_mes: mes_referencia (BTREE)
```

```sql
CREATE TABLE `peculio_saldos_trabalho` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ipen` int DEFAULT NULL,
  `mes_referencia` varchar(7) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `valor` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ipen` (`ipen`,`mes_referencia`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela peculio_saldos_trabalho
```sql
-- PRIMARY: id (BTREE)
-- ipen: ipen (BTREE)
-- ipen: mes_referencia (BTREE)
```

```sql
CREATE TABLE `rouparia_itens` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome_item` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `tamanho` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `genero` enum('M','F','U') COLLATE utf8mb4_general_ci DEFAULT 'U',
  `estoque_atual` int NOT NULL DEFAULT '0',
  `estoque_minimo` int NOT NULL DEFAULT '10',
  `data_cadastro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

### Índices da tabela rouparia_itens
```sql
-- PRIMARY: id (BTREE)
```

```sql
CREATE TABLE `servicos_agendamentos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `job_id` int NOT NULL,
  `tipo` enum('unico','diario','semanal','mensal','intervalo','minutos','horas') COLLATE utf8mb4_unicode_ci NOT NULL,
  `configuracao` json NOT NULL COMMENT 'Configura├º├úo detalhada do agendamento',
  `proximo_execucao` datetime NOT NULL,
  `ativo` tinyint(1) DEFAULT '1',
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_job_id` (`job_id`),
  KEY `idx_proximo_execucao` (`proximo_execucao`),
  CONSTRAINT `servicos_agendamentos_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `servicos_jobs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Agendamentos dos jobs';
```

### Índices da tabela servicos_agendamentos
```sql
-- PRIMARY: id (BTREE)
-- idx_job_id: job_id (BTREE)
-- idx_proximo_execucao: proximo_execucao (BTREE)
```

```sql
CREATE TABLE `servicos_dependencias` (
  `id` int NOT NULL AUTO_INCREMENT,
  `job_id` int NOT NULL,
  `job_dependente_id` int NOT NULL,
  `tipo` enum('sucesso','erro','sempre') COLLATE utf8mb4_unicode_ci DEFAULT 'sucesso' COMMENT 'Quando executar',
  `ativo` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `idx_job_id` (`job_id`),
  KEY `idx_job_dependente_id` (`job_dependente_id`),
  CONSTRAINT `servicos_dependencias_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `servicos_jobs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `servicos_dependencias_ibfk_2` FOREIGN KEY (`job_dependente_id`) REFERENCES `servicos_jobs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Depend├¬ncias entre jobs';
```

### Índices da tabela servicos_dependencias
```sql
-- PRIMARY: id (BTREE)
-- idx_job_id: job_id (BTREE)
-- idx_job_dependente_id: job_dependente_id (BTREE)
```

```sql
CREATE TABLE `servicos_execucoes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `job_id` int NOT NULL,
  `processo_id` int DEFAULT NULL,
  `data_inicio` datetime NOT NULL,
  `data_fim` datetime DEFAULT NULL COMMENT 'Data/hora de t├®rmino',
  `status` enum('executando','sucesso','erro','timeout','cancelado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'executando',
  `codigo_saida` int DEFAULT NULL COMMENT 'C├│digo de sa├¡da do processo',
  `saida_padrao` longtext COLLATE utf8mb4_unicode_ci COMMENT 'Output padr├úo do processo',
  `saida_erro` longtext COLLATE utf8mb4_unicode_ci COMMENT 'Output de erro do processo',
  `duracao_segundos` int DEFAULT NULL COMMENT 'Dura├º├úo em segundos',
  `memoria_usada_mb` decimal(10,2) DEFAULT NULL COMMENT 'Mem├│ria usada em MB',
  `process_id` int DEFAULT NULL COMMENT 'PID do processo',
  `maquina_execucao` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'M├íquina onde executou',
  `observacoes` text COLLATE utf8mb4_unicode_ci COMMENT 'Observa├º├Áes adicionais',
  PRIMARY KEY (`id`),
  KEY `idx_job_id` (`job_id`),
  KEY `idx_data_inicio` (`data_inicio`),
  KEY `idx_status` (`status`),
  CONSTRAINT `servicos_execucoes_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `servicos_jobs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=20910 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Hist├│rico de execu├º├Áes dos jobs';
```

### Índices da tabela servicos_execucoes
```sql
-- PRIMARY: id (BTREE)
-- idx_job_id: job_id (BTREE)
-- idx_data_inicio: data_inicio (BTREE)
-- idx_status: status (BTREE)
```

```sql
CREATE TABLE `servicos_jobs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nome do servi├ºo/job',
  `descricao` text COLLATE utf8mb4_unicode_ci COMMENT 'Descri├º├úo detalhada do servi├ºo',
  `tipo` enum('backup','script','site','relatorio','limpeza','outro') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'script' COMMENT 'Tipo do servi├ºo',
  `comando` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Comando ou script a ser executado',
  `parametros` text COLLATE utf8mb4_unicode_ci COMMENT 'Par├ómetros adicionais (JSON)',
  `diretorio_trabalho` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Diret├│rio de trabalho',
  `executar_como` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'SYSTEM' COMMENT 'Usu├írio para execu├º├úo',
  `agendamento_tipo` enum('unico','diario','semanal','mensal','intervalo','minutos','horas') COLLATE utf8mb4_unicode_ci DEFAULT 'unico',
  `agendamento_config` text COLLATE utf8mb4_unicode_ci COMMENT 'Configura├º├úo do agendamento (JSON)',
  `proximo_execucao` datetime DEFAULT NULL COMMENT 'Pr├│xima data/hora de execu├º├úo',
  `ultima_execucao` datetime DEFAULT NULL COMMENT 'Data/hora da ├║ltima execu├º├úo',
  `status` enum('ativo','inativo','pausado','executando','erro') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ativo' COMMENT 'Status atual',
  `priority` tinyint DEFAULT '5' COMMENT 'Prioridade (1-10)',
  `timeout` int DEFAULT '3600' COMMENT 'Timeout em segundos',
  `tentativas_max` int DEFAULT '3' COMMENT 'M├íximo de tentativas em caso de erro',
  `tentativas_atual` int DEFAULT '0' COMMENT 'Tentativas atuais',
  `email_notificar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'E-mail para notifica├º├Áes',
  `log_arquivo` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Arquivo de log',
  `criado_por` int DEFAULT NULL COMMENT 'Usu├írio que criou',
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `alterado_por` int DEFAULT NULL COMMENT 'Usu├írio que alterou',
  `alterado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_proximo_execucao` (`proximo_execucao`),
  KEY `idx_status` (`status`),
  KEY `idx_tipo` (`tipo`),
  KEY `idx_criado_em` (`criado_em`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Servi├ºos e Jobs do SIGEP';
```

### Índices da tabela servicos_jobs
```sql
-- PRIMARY: id (BTREE)
-- idx_proximo_execucao: proximo_execucao (BTREE)
-- idx_status: status (BTREE)
-- idx_tipo: tipo (BTREE)
-- idx_criado_em: criado_em (BTREE)
```

```sql
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `payload` longtext NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
```

### Índices da tabela sessions
```sql
-- PRIMARY: id (BTREE)
-- sessions_user_id_index: user_id (BTREE)
-- sessions_last_activity_index: last_activity (BTREE)
```

```sql
CREATE TABLE `sistema_config` (
  `id` int NOT NULL AUTO_INCREMENT,
  `chave` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `valor` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `chave` (`chave`)
) ENGINE=InnoDB AUTO_INCREMENT=145 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela sistema_config
```sql
-- PRIMARY: id (BTREE)
-- chave: chave (BTREE)
```

```sql
CREATE TABLE `sistema_notificacoes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `tipo` varchar(50) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `mensagem` text NOT NULL,
  `dados_json` json DEFAULT NULL,
  `lida` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_tipo` (`user_id`,`tipo`),
  KEY `idx_lida_created` (`lida`,`created_at` DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
```

### Índices da tabela sistema_notificacoes
```sql
-- PRIMARY: id (BTREE)
-- idx_user_tipo: user_id (BTREE)
-- idx_user_tipo: tipo (BTREE)
-- idx_lida_created: lida (BTREE)
-- idx_lida_created: created_at (BTREE)
```

```sql
CREATE TABLE `user_login_attempts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `usuario` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tentativas` int DEFAULT '1',
  `bloqueado_ate` timestamp NULL DEFAULT NULL,
  `ultimo_reset` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_ip_usuario` (`ip_address`,`usuario`),
  KEY `idx_bloqueado` (`bloqueado_ate`)
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela user_login_attempts
```sql
-- PRIMARY: id (BTREE)
-- unique_ip_usuario: ip_address (BTREE)
-- unique_ip_usuario: usuario (BTREE)
-- idx_bloqueado: bloqueado_ate (BTREE)
```

```sql
CREATE TABLE `user_sessions` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `usuario_id` int DEFAULT NULL,
  `usuario_nome` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `acao` enum('login_sucesso','login_falha','logout','sessao_expirou','conta_bloqueada') COLLATE utf8mb4_unicode_ci NOT NULL,
  `detalhes` json DEFAULT NULL,
  `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `sessao_id` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_ip` (`ip_address`),
  KEY `idx_timestamp` (`timestamp`)
) ENGINE=InnoDB AUTO_INCREMENT=1081 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices da tabela user_sessions
```sql
-- PRIMARY: id (BTREE)
-- idx_usuario: usuario_id (BTREE)
-- idx_ip: ip_address (BTREE)
-- idx_timestamp: timestamp (BTREE)
```

```sql
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `usuario` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `setor` enum('Censura','Almoxarifado','Segurança do Trabalho','Laboral','Recursos Humanos','Coordenação','Direção','Recepção','Tecnologia da Informação','Serralheria','Escola','Carga','Indústria','Jurídico','Cozinha','Eclusa') COLLATE utf8mb4_general_ci NOT NULL,
  `senha` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('Ativo','Inativo') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Ativo',
  `is_admin` tinyint(1) DEFAULT '0',
  `criado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `dark_mode` tinyint(1) DEFAULT '0',
  `is_kiosk` tinyint(1) NOT NULL DEFAULT '0',
  `kiosk_token` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `kiosk_token_updated_at` datetime DEFAULT NULL,
  `perm_dados` tinyint(1) DEFAULT '0',
  `perm_importacao` tinyint(1) DEFAULT '0',
  `perm_censura` tinyint(1) DEFAULT '0',
  `perm_almoxarifado` tinyint(1) DEFAULT '0',
  `perm_seg_trab` tinyint(1) DEFAULT '0',
  `perm_laboral` tinyint(1) DEFAULT '0',
  `perm_rh` tinyint(1) DEFAULT '0',
  `perm_coord` tinyint(1) DEFAULT '0',
  `perm_direcao` tinyint(1) DEFAULT '0',
  `perm_portaria` tinyint(1) DEFAULT '0',
  `perm_ti` tinyint(1) DEFAULT '0',
  `perm_gestao_contas` tinyint(1) DEFAULT '0',
  `perm_serralheria` tinyint(1) DEFAULT '0',
  `perm_escola` tinyint(1) DEFAULT '0',
  `perm_carga` tinyint(1) DEFAULT '0',
  `perm_industria` tinyint(1) DEFAULT '0',
  `perm_juridico` tinyint(1) DEFAULT '0',
  `perm_cozinha` tinyint(1) DEFAULT '0',
  `perm_recepcao` tinyint(1) DEFAULT '0',
  `perm_eclusa` tinyint(1) DEFAULT '0',
  `perm_manutencao` tinyint(1) DEFAULT '0',
  `perm_social` tinyint(1) DEFAULT '0',
  `perm_chefeseg` tinyint(1) DEFAULT '0',
  `perm_apoio` tinyint(1) DEFAULT '0',
  `perm_saude` tinyint(1) DEFAULT '0',
  `remember_token` varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `remember_expiry` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `usuario` (`usuario`),
  KEY `idx_remember_token` (`remember_token`),
  KEY `idx_remember_expiry` (`remember_expiry`),
  KEY `idx_setor` (`setor`),
  KEY `idx_status` (`status`),
  KEY `idx_setor_status` (`setor`,`status`),
  KEY `idx_is_admin` (`is_admin`),
  KEY `idx_is_kiosk` (`is_kiosk`),
  KEY `idx_kiosk_token` (`kiosk_token`),
  KEY `idx_perm_ti` (`perm_ti`),
  KEY `idx_perm_laboral` (`perm_laboral`),
  KEY `idx_perm_censura` (`perm_censura`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

### Índices da tabela users
```sql
-- PRIMARY: id (BTREE)
-- usuario: usuario (BTREE)
-- idx_remember_token: remember_token (BTREE)
-- idx_remember_expiry: remember_expiry (BTREE)
-- idx_setor: setor (BTREE)
-- idx_status: status (BTREE)
-- idx_setor_status: setor (BTREE)
-- idx_setor_status: status (BTREE)
-- idx_is_admin: is_admin (BTREE)
-- idx_is_kiosk: is_kiosk (BTREE)
-- idx_kiosk_token: kiosk_token (BTREE)
-- idx_perm_ti: perm_ti (BTREE)
-- idx_perm_laboral: perm_laboral (BTREE)
-- idx_perm_censura: perm_censura (BTREE)
```

## 🔒 Triggers

```sql

```

```sql

```

```sql

```

```sql

```

```sql

```

```sql

```

```sql

```

```sql

```

```sql

```

```sql

```

```sql

```

```sql

```

```sql

```

```sql

```

```sql

```

```sql

```

```sql

```

```sql

```

```sql

```

```sql

```

