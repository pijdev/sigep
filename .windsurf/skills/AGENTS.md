# AGENTS.md - Diretrizes do Cascade para SIGEP

## 🎯 **Propósito**
Este arquivo define as diretrizes contextuais para o Windsurf Cascade ao trabalhar com o projeto SIGEP, garantindo consistência, segurança e produtividade.

## 🤖 **Como o Cascade Funciona**

### **Invocação Automática de Skills**
O Cascade **não precisa ser chamado manualmente** para usar skills. Ele:

1. **Analisa o contexto** (arquivos abertos, tipo de tarefa, linguagem)
2. **Identifica qual skill** pode ajudar automaticamente
3. **Invoca a skill adequada** sem necessidade de @menção
4. **Fornece assistência contextual** baseada na necessidade

### **Exemplos de Invocação Automática**
```
📝 Usuario: "cria uma nova página para relatórios"
🤖 Cascade: (identifica necessidade de UI) → @sigep-adminlte-ui

📝 Usuario: "valida este código PHP"
🤖 Cascade: (identifica código PHP) → @sigep-php-validator

📝 Usuario: "o sistema está lento"
🤖 Cascade: (identifica problema de performance) → @sigep-performance-analyzer
```

## 🎨 **Skills Disponíveis (Auto-Invocadas)**

### **8 Skills Especializadas**
1. **sigep-php-validator** - Validação automática de código PHP
2. **sigep-mysql-operations** - Operações MySQL seguras
3. **sigep-adminlte-ui** - Componentes UI AdminLTE
4. **sigep-security-auditor** - Auditoria de segurança
5. **sigep-debug-helper** - Debugging e troubleshooting
6. **sigep-workflow-automation** - Automação de workflows
7. **sigep-base-conhecimento** - Base de conhecimento central
8. **sigep-performance-analyzer** - Análise de performance

### **Quando Skills São Invocadas**
- **Criação de arquivos** → Validação e padrões
- **Edição de código** → Verificação de segurança/qualidade
- **Problemas de performance** → Análise e otimização
- **Novas funcionalidades** → Geração de código boilerplate
- **Erros e bugs** → Debugging e soluções
- **Dúvidas sobre SIGEP** → Base de conhecimento

## 📋 **Padrões SIGEP (Contexto Automático)**

### **Arquitetura**
- **MVC**: View (arquivos visíveis) + Controller (logica.php)
- **Database**: MySQL 8.0 com PDO prepared statements
- **Frontend**: AdminLTE 3 + jQuery 3.6 + FontAwesome 7.2.0
- **Sessões**: Autenticação centralizada em $_SESSION

### **Convenções de Código**
- **PHP 8+**: Estruturado, sem frameworks externos
- **UTF-8**: Charset utf8mb4 em todo o sistema
- **Segurança**: display_errors = 0 em produção
- **Timezone**: America/Sao_Paulo

### **Estrutura de Diretórios**
```
modulos/[setor]/[modulo]/
├── [modulo]_view.php      # Interface (View)
├── [modulo]_logica.php    # Controller (Lógica)
├── assets/
│   ├── css/[modulo].css   # Estilos customizados
│   └── js/[modulo].js     # JavaScript + AJAX
└── README.md               # Documentação
```

## 🔧 **Comportamento Esperado do Cascade**

### **1. Análise Contextual**
- Sempre analisar arquivos abertos antes de agir
- Identificar tipo de tarefa automaticamente
- Considerar histórico da sessão atual

### **2. Invocação Inteligente**
- Usar skills apropriadas sem comando @
- Combinar múltiplas skills quando necessário
- Fornecer soluções completas e integradas

### **3. Validação Automática**
- Validar código gerado com sigep-php-validator
- Verificar segurança com sigep-security-auditor
- Testar performance com sigep-performance-analyzer

### **4. Documentação**
- Gerar documentação automática
- Criar README.md para novos módulos
- Atualizar base de conhecimento do SIGEP

## 🚫 **O Que o Cascade NÃO Deve Fazer**

- **Exigir @menções** para usar skills
- **Ignorar contexto** do projeto SIGEP
- **Usar frameworks** não compatíveis
- **Quebrar padrões** existentes
- **Criar código** sem validação

## ✅ **O Que o Cascade DEVE Fazer**

- **Analisar contexto** antes de agir
- **Usar skills automaticamente**
- **Seguir padrões SIGEP**
- **Validar tudo** que gerar
- **Documentar** soluções
- **Otimizar performance**
- **Garantir segurança**

## 🎯 **Exemplos Práticos**

### **Cenário 1: Novo Módulo**
```
📝 Usuario: "criar módulo de relatórios para TI"

🤖 Cascade (automático):
1. sigep-workflow-automation → Estrutura básica
2. sigep-adminlte-ui → Interface AdminLTE
3. sigep-mysql-operations → Operações CRUD
4. sigep-php-validator → Validação do código
5. sigep-security-auditor → Verificação de segurança
6. sigep-base-conhecimento → Documentação
```

### **Cenário 2: Correção de Bug**
```
📝 Usuario: "erro 500 na página de internos"

🤖 Cascade (automático):
1. sigep-debug-helper → Análise do erro
2. sigep-php-validator → Validação do código
3. sigep-security-auditor → Verificação de segurança
4. sigep-mysql-operations → Verificação de queries
```

### **Cenário 3: Otimização**
```
📝 Usuario: "relatório de internos está muito lento"

🤖 Cascade (automático):
1. sigep-performance-analyzer → Análise de performance
2. sigep-mysql-operations → Otimização de queries
3. sigep-adminlte-ui → Melhoria da interface
4. sigep-debug-helper → Identificação de bottlenecks
```

## 📚 **Base de Conhecimento Integrada**

O Cascade tem acesso a:
- **Arquitetura SIGEP** completa
- **Padrões de código** específicos
- **Database schema** atualizado
- **Security guidelines** detalhadas
- **Performance metrics** históricas
- **Troubleshooting guides** completos

## 🔄 **Feedback Contínuo**

### **Aprendizado**
O Cascade aprende com:
- **Padrões do projeto** via análise de código
- **Soluções anteriores** via memory MCP
- **Feedback do usuário** via interações
- **Performance histórica** via métricas

### **Melhoria Contínua**
- **Atualiza skills** com novos padrões
- **Refina validações** com base em erros
- **Otimiza workflows** com base em uso
- **Expande conhecimento** com novas funcionalidades

## 🎉 **Resultado Final**

O Cascade funciona como um **desenvolvedor SIGEP experiente** que:

- **Entende o projeto** profundamente
- **Usa ferramentas adequadas** automaticamente
- **Segue padrões** consistentemente
- **Valida tudo** que produz
- **Otimiza performance** continuamente
- **Garante segurança** em todas as operações

**Sem necessidade de comandos especiais - apenas conversa natural!** 🚀
