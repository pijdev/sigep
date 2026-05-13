# Scripts Cascade - Arsenal de Ferramentas

## 🎯 **Propósito**
Esta pasta contém o arsenal de scripts do Cascade para automação, análise e geração de código SIGEP.

## 📁 **Scripts Disponíveis**

### **🔍 Análise e Validação**
- `analisar_estrutura_projeto.php` - Análise completa da estrutura do projeto
- `validar_padroes_sigep.php` - Validação de conformidade com padrões SIGEP

### **🛠️ Geração de Código**
- `gerar_modulo_sigep.php` - Geração automática de módulos SIGEP

### **🧪 Testes e Debugging**
- Scripts para debugging avançado
- Scripts para testes automatizados
- Scripts para análise de performance

### **🔄 Migrações e Refatorações**
- Scripts para migração de dados
- Scripts para refatoração de código
- Scripts para sincronização de sistemas

### **🔗 Integrações Externas**
- Scripts para integração com APIs
- Scripts para processamento de dados externos
- Scripts para sincronização com outros sistemas

## 🎨 **Como Usar**

### **Comando Básico**
```bash
php analisar_estrutura_projeto.php [caminho_projeto] [opcoes]
php gerar_modulo_sigep.php [setor] [nome_modulo] [opcoes]
php validar_padroes_sigep.php [caminho_projeto] [opcoes]
```

### **Opções Comuns**
- `--detalhado` - Análise profunda
- `--relatorio-html` - Gera relatório em HTML
- `--com-banco` - Inclui operações de banco
- `--com-menu` - Adiciona entrada no menu
- `--forcar` - Sobrescreve arquivos existentes
- `--strict` - Modo estrito de validação

## 📋 **Exemplos Práticos**

### **Analisar Projeto SIGEP**
```bash
# Análise básica
php analisar_estrutura_projeto.php C:/Sites/sigep

# Análise detalhada com relatório HTML
php analisar_estrutura_projeto.php C:/Sites/sigep --detalhado --relatorio-html
```

### **Gerar Novo Módulo**
```bash
# Gerar módulo básico
php gerar_modulo_sigep.php ti relatorios

# Gerar módulo completo com banco e menu
php gerar_modulo_sigep.php ti relatorios --com-banco --com-menu --forcar
```

### **Validar Padrões**
```bash
# Validação padrão
php validar_padroes_sigep.php C:/Sites/sigep

# Validação estrita com sugestões de correção
php validar_padroes_sigep.php C:/Sites/sigep --strict --corrigir --relatorio-html
```

## 🔧 **Características dos Scripts**

### **✅ Padrões SIGEP**
- Seguem convenções do projeto
- Usam prepared statements PDO
- Validam sessão e autenticação
- Geram código MVC estruturado
- Incluem documentação adequada

### **🛡️ Segurança**
- Validam entradas e caminhos
- Usam funções seguras do PHP
- Implementam tratamento de erros
- Evitam comandos shell diretos

### **📊 Relatórios**
- Geram relatórios detalhados
- Exportam em múltiplos formatos
- Incluem métricas e estatísticas
- Fornecem sugestões de melhoria

### **🔄 Automação**
- Reduzem trabalho manual
- Padronizam processos
- Aceleram desenvolvimento
- Garantem consistência

## 🎯 **Integração com Skills SIGEP**

Os scripts são projetados para trabalhar em conjunto com as skills SIGEP:

- **sigep-php-validator** → Valida código gerado
- **sigep-mysql-operations** → Operações de banco seguras
- **sigep-debug-helper** → Debugging avançado
- **sigep-performance-analyzer** → Otimização de performance
- **sigep-workflow-automation** → Automação de workflows

## 📚 **Documentação**

Cada script inclui:
- Header completo com metadata
- Comentários detalhados
- Exemplos de uso
- Tratamento de erros
- Ajuda integrada (`--help`)

## 🚀 **Resultados Esperados**

Com este arsenal, o Cascade pode:

- **Analisar** projetos em segundos
- **Gerar** código SIGEP automaticamente
- **Validar** conformidade com padrões
- **Diagnosticar** problemas de arquitetura
- **Corrigir** problemas com sugestões
- **Automatizar** tarefas repetitivas

## 🔄 **Atualizações**

Os scripts são atualizados regularmente para:
- Incluir novos padrões SIGEP
- Corrigir bugs e melhorias
- Adicionar novas funcionalidades
- Manter compatibilidade com o projeto

---

**O arsenal de scripts Cascade está pronto para automatizar e melhorar o desenvolvimento SIGEP!** 🚀
