# AGENTS.md - Scripts Cascade (Arsenal de Ferramentas)

## 🎯 **Contexto Específico**
Este arquivo define padrões e comportamentos do Cascade ao trabalhar com scripts na pasta `.windsurf/scripts/` - seu arsenal de ferramentas.

## 🏗️ **Propósito do Arsenal Scripts**

A pasta `.windsurf/scripts/` é o **arsenal de ferramentas** do Cascade para:
- **Validação e análise** de projetos
- **Geração de código** automatizada
- **Testes e debugging** avançados
- **Migrações e refatorações**
- **Integrações externas**
- **Utilitários de desenvolvimento**

## 📁 **Categorias de Scripts**

### **1. 🔍 Análise e Validação**
- Scripts para analisar estrutura de projetos
- Validação de padrões e convenções
- Análise de dependências
- Verificação de qualidade

### **2. 🛠️ Geração de Código**
- Templates e boilerplates
- Geração de módulos SIGEP
- Criação de estruturas MVC
- Automação de tarefas repetitivas

### **3. 🧪 Testes e Debugging**
- Scripts de teste automatizados
- Debugging avançado
- Análise de performance
- Validação de funcionalidades

### **4. 🔄 Migrações e Refatorações**
- Scripts de migração de dados
- Refatoração de código
- Atualização de estruturas
- Sincronização de sistemas

### **5. 🔗 Integrações Externas**
- Scripts para APIs externas
- Processamento de dados externos
- Sincronização com outros sistemas
- Importação/exportação

## 🎨 **Padrões de Nomenclatura**

### **Formato Padrão**
```
[categoria]_[ação]_[especificidade].php
```

### **Categorias**
- `analisar_` - Análise e validação
- `gerar_` - Geração de código
- `testar_` - Testes e debugging
- `migrar_` - Migrações e refatorações
- `integrar_` - Integrações externas
- `validar_` - Validação de padrões
- `otimizar_` - Otimização de performance
- `debugar_` - Debugging específico

### **Exemplos**
- ✅ `analisar_estrutura_projeto.php`
- ✅ `gerar_modulo_sigep.php`
- ✅ `testar_performance_banco.php`
- ✅ `migrar_dados_legado.php`
- ✅ `integrar_api_externa.php`
- ✅ `validar_padroes_codigo.php`
- ❌ `script1.php` (sem contexto)
- ❌ `teste.php` (genérico)
- ❌ `ferramenta.php` (sem especificidade)

## 📋 **Estrutura Padrão dos Scripts**

### **Header Obrigatório**
```php
<?php
/**
 * [Nome do Script] - [Breve Descrição]
 * 
 * @category Cascade Scripts
 * @package [Categoria]
 * @author Cascade AI
 * @version 1.0
 * @since 2024-03-25
 * 
 * Descrição detalhada do que o script faz:
 * - O que analisa/gera/testa
 * - Quais tecnologias usa
 * - Como usar
 * - Dependências necessárias
 */

// Configurações iniciais
set_time_limit(0);
ini_set('memory_limit', '2G');
date_default_timezone_set('America/Sao_Paulo');

// Funções utilitárias
function logMessage($message, $level = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    echo "[$timestamp] [$level] $message\n";
}

function validarProjeto($caminho) {
    if (!is_dir($caminho)) {
        logMessage("ERRO: Projeto não encontrado: $caminho", 'ERROR');
        return false;
    }
    return true;
}

// Execução principal
logMessage("Iniciando [ação do script]...");

// Lógica principal do script
// ...

logMessage("[ação do script] concluída com sucesso!");
?>
```

### **Funções Utilitárias Padrão**
```php
// Logging estruturado
function logMessage($message, $level = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    $logLine = "[$timestamp] [$level] $message\n";
    echo $logLine;
    
    // Opcional: salvar em arquivo de log
    global $arquivoLog;
    if (isset($arquivoLog)) {
        file_put_contents($arquivoLog, $logLine, FILE_APPEND);
    }
}

// Validação de projeto
function validarProjeto($caminho, $obrigatorio = true) {
    if (!is_dir($caminho)) {
        if ($obrigatorio) {
            logMessage("ERRO: Projeto obrigatório não encontrado: $caminho", 'ERROR');
            return false;
        } else {
            logMessage("AVISO: Projeto opcional não encontrado: $caminho", 'WARNING');
        }
    }
    return true;
}

// Análise de arquivos
function analisarArquivos($diretorio, $extensao = 'php') {
    $arquivos = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($diretorio)
    );
    
    foreach ($iterator as $arquivo) {
        if ($arquivo->isFile() && $arquivo->getExtension() === $extensao) {
            $arquivos[] = $arquivo->getPathname();
        }
    }
    
    return $arquivos;
}

// Geração de relatórios
function gerarRelatorio($dados, $formato = 'html') {
    switch ($formato) {
        case 'html':
            return gerarRelatorioHTML($dados);
        case 'json':
            return json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        case 'csv':
            return gerarRelatorioCSV($dados);
        default:
            return print_r($dados, true);
    }
}

// Validação de código
function validarCodigoPHP($codigo) {
    $erros = [];
    
    // Verificar sintaxe
    if (!@php_check_syntax($codigo)) {
        $erros[] = 'Erro de sintaxe PHP';
    }
    
    // Verificar padrões SIGEP
    if (strpos($codigo, '<?php') !== 0) {
        $erros[] = 'Tag PHP não está no início do arquivo';
    }
    
    // Verificar prepared statements
    if (strpos($codigo, 'PDO::prepare') === false && strpos($codigo, '$pdo->prepare') === false) {
        $erros[] = 'Não foram encontrados prepared statements PDO';
    }
    
    return $erros;
}
```

## 🔧 **Scripts Recomendados por Categoria**

### **🔍 Análise e Validação**
```php
// analisar_estrutura_projeto.php
// Analisa estrutura completa de um projeto
// Verifica padrões MVC, dependências, organização
// Gera relatório detalhado da arquitetura

// validar_padroes_sigep.php
// Valida se projeto segue padrões SIGEP
// Verifica estrutura de arquivos, convenções
// Gera lista de não conformidades

// analisar_dependencias.php
// Analisa dependências do projeto
// Verifica composer.json, package.json
// Identifica dependências obsoletas ou vulneráveis
```

### **🛠️ Geração de Código**
```php
// gerar_modulo_sigep.php
// Gera estrutura completa de módulo SIGEP
// Cria view, controller, assets
// Segue padrões MVC existentes

// gerar_crud_banco.php
// Gera CRUD completo para tabela
// Cria controller, model, views
// Inclui validações e segurança

// gerar_api_rest.php
// Gera API REST para recursos
// Inclui endpoints CRUD
// Documentação automática
```

### **🧪 Testes e Debugging**
```php
// testar_performance_banco.php
// Testa performance de queries
// Identifica bottlenecks
// Gera relatório de otimização

// debugar_erro_especifico.php
// Debug de erro específico
// Analisa stack trace
// Sugere correções

// validar_seguranca_codigo.php
// Verifica vulnerabilidades de segurança
- SQL injection, XSS
- Validação de inputs
- Best practices
```

### **🔄 Migrações e Refatorações**
```php
// migrar_dados_legado.php
// Migração de dados de sistema legado
- Validação e limpeza
- Transformação de dados
- Rollback automático

// refatorar_codigo_obsoleto.php
// Refatora código obsoleto
- Atualiza sintaxe
- Substitui funções deprecated
- Gera relatório de mudanças

// sincronizar_bancos.php
// Sincroniza múltiplos bancos
- Compara estruturas
- Identifica diferenças
- Aplica migrações
```

### **🔗 Integrações Externas**
```php
// integrar_api_externa.php
// Integra com API externa
- Autenticação
- Rate limiting
- Cache de respostas

// processar_dados_csv.php
// Processa arquivos CSV externos
- Validação de estrutura
- Limpeza de dados
- Importação para banco

// sincronizar_sistemas.php
// Sincroniza com outros sistemas
- Webhooks
- Eventos
- Consistência de dados
```

## 🚨 **Segurança nos Scripts**

### **Validações Obrigatórias**
- [ ] Verificar permissões de arquivos
- [ ] Sanitizar entradas externas
- [ ] Validar caminhos de diretórios
- [ ] Usar prepared statements
- [ ] Limitar uso de recursos
- [ ] Implementar timeout

### **Boas Práticas**
```php
// Nunca executar comandos shell diretamente
// Usar funções seguras do PHP

// Para processamento de arquivos
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($diretorio)
);

// Para operações de banco
$stmt = $pdo->prepare("SELECT * FROM tabela WHERE condicao = ?");
$stmt->execute([$valor]);

// Para logging seguro
$logMessage = "Script executado por usuário: " . ($_SESSION['user_id'] ?? 'sistema');
logMessage($logMessage);
```

## 📚 **Integração com Skills SIGEP**

### **Skills Relevantes para Scripts**
- `sigep-php-validator` → Valida código dos scripts
- `sigep-mysql-operations` → Operações de banco seguras
- `sigep-debug-helper` → Debugging avançado
- `sigep-performance-analyzer` → Otimização de performance
- `sigep-workflow-automation` → Automação de workflows

### **Uso Automático de Skills**
Ao criar ou editar scripts, o Cascade deve:
1. **Validar código** com `sigep-php-validator`
2. **Verificar segurança** com `sigep-security-auditor`
3. **Otimizar performance** com `sigep-performance-analyzer`
4. **Documentar** com `sigep-base-conhecimento`

## 🔄 **Ciclo de Vida dos Scripts**

### **Criação**
1. Definir propósito e escopo
2. Escolher categoria e nome adequado
3. Implementar estrutura padrão
4. Adicionar validações e logging
5. Testar com projetos amostra
6. Documentar uso e parâmetros

### **Manutenção**
1. Revisar logs de erro
2. Atualizar para novos padrões
3. Otimizar performance
4. Atualizar documentação
5. Versionar mudanças

### **Depreciação**
1. Marcar como obsoleto no header
2. Criar script substituto
3. Migrar funcionalidades
4. Atualizar documentação
5. Arquivar script antigo

## 📋 **Checklist para Novos Scripts**

### **Antes de Criar**
- [ ] Verificar se script similar já existe
- [ ] Definir categoria e nome adequado
- [ ] Identificar dependências necessárias
- [ ] Planejar estrutura de arquivos

### **Durante Criação**
- [ ] Usar header padrão
- [ ] Implementar logging adequado
- [ ] Adicionar validações de segurança
- [ ] Tratar erros adequadamente
- [ ] Otimizar para performance

### **Após Criação**
- [ ] Testar com projetos reais
- [ ] Validar com skills SIGEP
- [ ] Documentar uso e parâmetros
- [ ] Criar exemplos de uso
- [ ] Adicionar ao AGENTS.md

## 🎯 **Exemplos Práticos**

### **Script de Análise de Projeto**
```php
<?php
/**
 * Analisar Estrutura de Projeto SIGEP
 * 
 * Analisa estrutura completa de um projeto SIGEP
 * Verifica padrões MVC, dependências, organização
 * Gera relatório detalhado da arquitetura
 */

set_time_limit(0);
ini_set('memory_limit', '2G');

// Implementação completa...
?>
```

### **Script de Geração de Módulo**
```php
<?php
/**
 * Gerar Módulo SIGEP
 * 
 * Gera estrutura completa de módulo SIGEP
 * Cria view, controller, assets
 * Segue padrões MVC existentes
 */

set_time_limit(0);
ini_set('memory_limit', '512M');

// Implementação completa...
?>
```

## 📖 **Documentação Obrigatória**

### **README.md da Pasta Scripts**
```markdown
# Scripts Cascade - Arsenal de Ferramentas

## Organização
- `analisar_` - Análise e validação
- `gerar_` - Geração de código
- `testar_` - Testes e debugging
- `migrar_` - Migrações e refatorações
- `integrar_` - Integrações externas

## Como Usar
1. Verificar requisitos no header do script
2. Configurar parâmetros necessários
3. Executar via linha de comando: `php script.php [parametros]`
4. Verificar logs de saída

## Segurança
- Scripts sempre validam entradas
- Usam prepared statements para banco
- Implementam logging adequado
- Limitam uso de recursos
```

## 🔄 **Integração com Outros AGENTS.md**

Este arquivo tem precedência sobre:
- `/AGENTS.md` (regras globais)
- `/scripts/AGENTS.md` (scripts temporários)

Mas deve ser consistente com:
- `.windsurfrules` (regras principais)
- Skills SIGEP (funcionalidades)
- Memory MCP (conhecimento persistente)

## 🚀 **Resultados Esperados**

Com este padrão, a pasta `.windsurf/scripts/` se torna um **arsenal completo** para:
- **Analisar projetos** com precisão
- **Gerar código** automatizado
- **Testar funcionalidades** avançadas
- **Migrar sistemas** com segurança
- **Integrar tecnologias** externas
- **Garantir qualidade** e consistência

**O Cascade deve sempre consultar este AGENTS.md ao trabalhar com scripts do arsenal!** 🚀
