# 🗄️ **Schema Completo do Banco de Dados SIGEP**

> **Última Atualização**: {TIMESTAMP}
> **Total de Tabelas**: {TOTAL_TABELAS}
> **Extraído via**: MCP mysql-sigep
> **Propósito**: Replicação exata da estrutura `sigep_producao`

---

## **📋 Visão Geral do Schema**

O banco de dados `sigep_producao` contém {TOTAL_TABELAS} tabelas organizadas por módulos, totalizando um sistema completo para gestão penitenciária.

**Este arquivo contém 100% da estrutura real do banco para permitir replicação idêntica.**

---

## **� Estrutura Completa do Banco de Dados**

{CREATE_TABLES_COMPLETOS}

---

## **� Listagem de Tabelas por Módulo**

{LISTAGEM_MODULOS}

---

## **� Relacionamentos e Foreign Keys**

{FOREIGN_KEYS_COMPLETAS}

---

## **� Índices de Performance**

{INDICES_COMPLETOS}

---

## **� Triggers e Constraints**

{TRIGGERS_COMPLETOS}

---

## **📊 Estatísticas do Schema**

### **Volume de Dados**
- **Total de Tabelas**: {TOTAL_TABELAS}
- **Total de Índices**: {TOTAL_INDICES}
- **Total de Foreign Keys**: {TOTAL_FKS}
- **Total de Triggers**: {TOTAL_TRIGGERS}

### **Módulos**
- **Segurança**: {COUNT_SEGURANCA} tabelas
- **Censura**: {COUNT_CENSURA} tabelas
- **Eclusa**: {COUNT_ECLUSA} tabelas
- **Eletrônicos**: {COUNT_ELETRONICOS} tabelas
- **Internos**: {COUNT_INTERNOS} tabelas
- **Laboral/Pecúlio**: {COUNT_LABORAL} tabelas
- **Manutenção**: {COUNT_MANUTENCAO} tabelas
- **Serviços**: {COUNT_SERVICOS} tabelas
- **Sistema**: {COUNT_SISTEMA} tabelas
- **IA/Logs**: {COUNT_IA} tabelas
- **Outros**: {COUNT_OUTROS} tabelas

---

## **🔄 Histórico de Atualizações**

- {TIMESTAMP}: Schema completo extraído via MCP mysql-sigep
- [Histórico anterior...]

---

## **📝 Como Usar Este Schema**

### **Para replicação exata:**
```sql
-- 1. Criar banco de dados
CREATE DATABASE novo_sigep CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

-- 2. Usar o banco
USE novo_sigep;

-- 3. Executar todos os CREATE TABLE acima
-- (Copiar e colar toda a seção "Estrutura Completa do Banco de Dados")
```

### **Para análise offline:**
- Todas as tabelas com seus tipos e constraints
- Todos os índices para análise de performance
- Todas as foreign keys para entender relacionamentos
- Todos os triggers para lógica de negócio

---

**⚠️ AVISO**: Este schema representa a estrutura 100% real do `sigep_producao`. Qualquer alteração deve ser testada em ambiente de desenvolvimento antes de aplicar em produção.
