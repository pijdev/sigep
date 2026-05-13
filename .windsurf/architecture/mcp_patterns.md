# 🔄 MCP Patterns - Padrões de Uso MCP SIGEP

## 📋 **Padrões Estabelecidos**

### **🧠 Memory MCP - add_observations**

#### **❌ Formatos Incorretos (Evitar)**

```javascript
// ERRO 1 - Formato string inválido
mcp5_add_observations("texto simples");

// ERRO 2 - Falta entityName
mcp5_add_observations({
  observations: ["array de strings"]
});

// ERRO 3 - entityName undefined
mcp5_add_observations({
  observations: [{
    contents: ["array"],
    entityName: undefined
  }]
});
```

#### **✅ Formato Correto (Obrigatório)**

```javascript
// ESTRUTURA VÁLIDA
mcp5_add_observations({
  observations: [{
    contents: [
      "Observação 1",
      "Observação 2",
      "Observação 3"
    ],
    entityName: "Nome da Entidade"
  }]
});
```

#### **🎯 Exemplos Práticos**

**1. Registrar alteração de schema:**
```javascript
mcp5_add_observations({
  observations: [{
    contents: [
      "ALTERAÇÃO: ADD COLUMN na tabela internos",
      "Descrição: Adicionada coluna data_ultima_atualizacao DATETIME",
      "Data: 2026-03-17T09:32:00.000Z",
      "Impacto: Queries devem considerar nova coluna"
    ],
    entityName: "Arquitetura SIGEP"
  }]
});
```

**2. Documentar criação de script:**
```javascript
mcp5_add_observations({
  observations: [{
    contents: [
      "CRIAÇÃO: Script E2E Health Check implementado",
      "Descrição: Criado suite completa em .windsurf/scripts/e2e/",
      "Data: 2026-03-17T08:48:00.000Z",
      "Impacto: Monitoramento automatizado da saúde do sistema"
    ],
    entityName: "Arquitetura SIGEP"
  }]
});
```

---

### **🖥️ Windows MCP - Comandos Shell**

#### **✅ Comandos Permitidos**

```javascript
// PowerShell
mcp9_PowerShell("Get-Process httpd");
mcp9_PowerShell("C:\\Program Files\\PHP\\php.exe script.php");

// FileSystem
mcp9_FileSystem("mode=list&path=C:\\path\\file");
mcp9_FileSystem("mode=write&path=test.txt&content=Hello");

// Process
mcp9_Process("mode=list&sort_by=name");
```

#### **❌ Comandos Proibidos**

```javascript
// Nunca usar bash ou comandos Unix
bash "ls -la";
bash "php script.php";
bash "cat file.txt";
```

---

### **🗄️ MySQL MCP - Operações Banco**

#### **✅ Padrões SQL**

```javascript
// Listar tabelas
mcp6_listar_tabelas();

// Verificar schema
mcp6_ver_esquema("nome_tabela");

// Executar SQL
mcp6_executar_sql("SELECT * FROM tabela LIMIT 10");
```

---

## **🔄 Círculo de Ferro - Sincronização**

### **Regra Obrigatória:**

1. **Alteração no MySQL** → **2. Memory MCP** → **3. Arquivos SIGEP**

```javascript
// 1. Alterar banco
mcp6_executar_sql("ALTER TABLE tabela ADD COLUMN coluna VARCHAR(100)");

// 2. Registrar no memory (OBRIGATÓRIO)
mcp5_add_observations({
  observations: [{
    contents: [
      "ALTERAÇÃO: ADD COLUMN na tabela tabela",
      "Descrição: Adicionada coluna para controle",
      "Data: " + new Date().toISOString(),
      "Impacto: Queries devem considerar nova coluna"
    ],
    entityName: "Arquitetura SIGEP"
  }]
});

// 3. Atualizar arquivos
// Editar .windsurf/architecture/database/schema_completo.md
```

---

## **📊 Codemaps - Documentação Viva**

### **Localização:**
```
c:\Users\ti\.codeium\windsurf\codemaps\
```

### **Codemaps Disponíveis:**
1. **AdminLTE 3** - Arquitetura SPA
2. **Autenticação** - Sistema completo
3. **Módulo Eclusa** - Movimentações
4. **Importação iPEN** - Relatórios
5. **Controle Estoque** - Sistema Censura
6. **Entrada Eletrônicos** - Recebimento
7. **Referências index.php** - SPA
8. **Outros fluxos específicos**

### **Estrutura de Cada Codemap:**
```json
{
  "schemaVersion": 1,
  "id": "identificador-unico",
  "title": "Título Descritivo",
  "traces": [
    {
      "id": "1",
      "title": "Fluxo Principal",
      "locations": [
        {
          "id": "1a",
          "path": "c:\\path\\arquivo.php",
          "lineNumber": 42,
          "lineContent": "código da linha",
          "description": "explicação detalhada"
        }
      ]
    }
  ]
}
```

---

## **⚠️ Erros Comuns e Soluções**

### **MCP-32602: Input validation error**

**Causa:** Estrutura incorreta no add_observations  
**Solução:** Verificar formato com entityName obrigatório

### **MCP error: Could not find Chrome**

**Causa:** Puppeteer sem Chrome instalado  
**Solução:** Usar executablePath para Chrome existente

### **Access denied - path outside allowed directories**

**Causa:** Tentativa de acesso fora dos diretórios permitidos  
**Solução:** Usar windows-mcp para paths externos

---

## **🎯 Best Practices**

1. **Sempre** validar estrutura antes de executar MCP
2. **Sempre** usar entityName em add_observations
3. **Sempre** usar windows-mcp para operações shell no Windows
4. **Sempre** seguir o círculo de ferro de sincronização
5. **Sempre** registrar alterações no memory MCP

---

**Atualizado**: 2026-03-17  
**Versão**: 1.0  
**Aplicável**: Projeto SIGEP completo