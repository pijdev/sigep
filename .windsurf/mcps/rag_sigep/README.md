# 🤖 MCP Server RAG SIGEP

Servidor MCP especializado em RAG para o Sistema Prisional Integrado SIGEP.

## 📍 Localização
```
C:\Sites\sigep\.windsurf\mcps\rag_sigep\
```

## 🚀 Instalação

1. **Instalar dependência**:
   ```bash
   pip install mcp>=1.0.0
   ```

2. **Configurar Windsurf** - Adicionar ao `settings.json`:
   ```json
   {
     "mcpServers": {
       "rag-sigep": {
         "command": "python",
         "args": ["C:\\Sites\\sigep\\.windsurf\\mcps\\rag_sigep\\server.py"],
         "env": {
           "SIGEP_PATH": "C:\\Sites\\sigep",
           "OLLAMA_HOST": "http://localhost:11434",
           "OLLAMA_MODEL": "llama3.2:8b"
         }
       }
     }
   }
   ```

3. **Reiniciar Windsurf**

## 🛠️ Ferramentas Disponíveis

- `rag_query` - Consultas RAG inteligentes sobre o SIGEP
- `list_sigep_modules` - Lista todos os módulos do sistema
- `get_sigep_database` - Informações das tabelas do banco
- `get_sigep_patterns` - Padrões de código e desenvolvimento
- `get_sigep_workflows` - Fluxos de trabalho e processos
- `analyze_sigep_structure` - Análise da estrutura de arquivos

## 📋 Exemplos de Uso

### Consultar sobre módulos:
```
rag_query({"query": "Como funciona o módulo de internos?"})
```

### Listar todos os módulos:
```
list_sigep_modules()
```

### Obter padrões de código:
```
get_sigep_patterns({"pattern": "mvc"})
```

### Analisar estrutura:
```
analyze_sigep_structure({"component": "controllers"})
```

## 🎯 Benefícios

- **Busca semântica** em todo o conhecimento SIGEP
- **Integração com Ollama** para respostas avançadas
- **Análise de estrutura** em tempo real
- **Conhecimento estruturado** do sistema
- **Modo simulado** se Ollama não estiver disponível

## 📊 Status

- ✅ Servidor MCP funcional
- ✅ Conhecimento SIGEP estruturado
- ✅ Integração Ollama (opcional)
- ✅ Análise de arquivos do projeto
- ✅ 6 ferramentas especializadas