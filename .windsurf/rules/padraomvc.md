---
description: Workflow completo para criar módulos SIGEP seguindo padrão MVC rigoroso com AdminLTE 3
auto_execution_mode: 2
trigger: always_on
---

# Padrão MVC - Guia Definitivo para Módulos SIGEP

## 🎯 **Visão Geral**

Este workflow ensina como criar módulos completos para o SIGEP seguindo rigorosamente o padrão MVC existente, com interface AdminLTE 3 e integração SPA (Single Page Application).

## 🏗️ **Arquitetura SIGEP**

### **Como o SIGEP Funciona**
- **SPA (Single Page Application)**: Navegação via AJAX com `loadPage()`
- **Header Global**: `includes/header.php` carrega CSS/JS AdminLTE
- **Sidebar Dinâmico**: `includes/sidebar.php` + `sidebar_logica.php`
- **Sessão Centralizada**: Autenticação e permissões via `$_SESSION`
- **URL Amigáveis**: `.htaccess` para rewrite rules
- **UTF-8 Rigoroso**: Charset utf8mb4 em todo o sistema

### **Tecnologias Padrão**
- **Backend**: PHP 8+ com PDO prepared statements
- **Frontend**: AdminLTE 3 + jQuery 3.6.0 + Bootstrap 4
- **Banco**: MySQL 8.0 com charset utf8mb4
- **Icons**: FontAwesome 6.4.0
- **Navegação**: SPA com AJAX

## 📁 **Estrutura de Módulos**

### **Padrão de Diretórios**
```
modulos/[setor]/[nome_modulo]/
├── [nome_modulo]_view.php      # Interface (View)
├── [nome_modulo]_logica.php    # Controller (Lógica)
├── assets/
│   ├── css/
│   │   └── [nome_modulo].css   # Estilos customizados
│   └── js/
│       └── [nome_modulo].js    # JavaScript + AJAX
└── README.md                   # Documentação (opcional)
```

### **Exemplo Real**
```
modulos/ti/planner/
├── planner_view.php
├── planner_logica.php
├── assets/
│   ├── css/planner.css
│   └── js/planner.js
```

## 🔄 **Fluxo de Trabalho**

### **Passo 1: Estrutura Base**
1. Criar diretórios do módulo
2. Criar arquivos MVC básicos
3. Seguir naming convention rigorosa

### **Passo 2: Integração Menu**
1. Adicionar entrada em `includes/sidebar_logica.php`
2. Definir permissões corretas
3. Usar `loadPage()` para SPA

### **Passo 3: URL Amigável**
1. Adicionar regra no `.htaccess`
2. Apontar para view file
3. Testar navegação

### **Passo 4: Banco de Dados**
1. Criar tabelas com charset utf8mb4
2. Definir foreign keys
3. Preparar queries com PDO

### **Passo 5: Implementação**
1. Desenvolver view com AdminLTE
2. Implementar controller com segurança
3. Adicionar JavaScript com proteção SPA

## 📋 **Template View PHP**

### **Estrutura Padrão**
```php
<?php
require_once __DIR__ . '/[nome_modulo]_logica.php';
?>

<!-- Content Header -->
<div class="row mb-2">
    <div class="col-sm-6">
    </div>
</div>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">

        <!-- Cards Resumo (Opcional) -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info pointer">
                    <div class="inner">
                        <h3 id="stats-total">0</h3>
                        <p>Total</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-database"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Conteúdo Principal -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-cog mr-2"></i>
                            Título do Módulo
                        </h3>
                    </div>
                    <div class="card-body">
                        <!-- Conteúdo aqui -->
                    </div>
                </div>
            </div>
        </div>

    </div>
</section>

<!-- Scripts -->
<script src="modulos/[setor]/[nome_modulo]/assets/js/[nome_modulo].js"></script>
```

### **⚠️ Ponto Crítico: Headers**
- **NUNCA** usar `<section class="content-header">`
- **SIGEP injeta automaticamente** título e breadcrumb
- **Use sempre** `<div class="row mb-2">` vazio
- **Evita duplicação** de headers

## 📝 **Template Controller PHP**

### **Estrutura Padrão**
```php
<?php
// [Nome Módulo] - Controller SIGEP
// Descrição do módulo

session_start();
require_once __DIR__ . '/../../../conf/db.php';

// Headers para resposta JSON
header('Content-Type: application/json; charset=utf-8');

// Tratamento de erros
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Configurar Timezone para São Paulo
date_default_timezone_set('America/Sao_Paulo');

// Função para retornar erro JSON
function returnError($message, $code = 500) {
    http_response_code($code);
    echo json_encode(['error' => $message, 'code' => $code], JSON_UNESCAPED_UNICODE);
    exit;
}

// Verificar se usuário está autenticado
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_nome'])) {
    returnError('Usuário não autenticado', 401);
}

// Verificar permissão específica
if (!($_SESSION['user_admin'] || ($_SESSION['perm_setor'] ?? 0))) {
    returnError('Sem permissão para acessar este módulo', 403);
}

// Configurar conexão PDO
try {
    $config = require __DIR__ . '/../../../conf/db.php';
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['user'], $config['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $pdo->exec("SET time_zone = '-03:00'");
} catch (PDOException $e) {
    returnError('Erro na conexão com banco de dados: ' . $e->getMessage(), 500);
}

// Funções CRUD
function criarItem($pdo, $dados) {
    try {
        $stmt = $pdo->prepare("INSERT INTO tabela (campo1, campo2, created_at) VALUES (?, ?, NOW())");
        $stmt->execute([$dados['campo1'], $dados['campo2']]);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        throw new Exception('Erro ao criar item: ' . $e->getMessage());
    }
}

function listarItens($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM tabela ORDER BY created_at DESC");
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        throw new Exception('Erro ao listar itens: ' . $e->getMessage());
    }
}

// Processar requisições AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    ob_clean();

    try {
        switch ($_POST['action']) {
            case 'listar':
                $itens = listarItens($pdo);
                echo json_encode(['success' => true, 'data' => $itens], JSON_UNESCAPED_UNICODE);
                break;

            case 'criar':
                $item_id = criarItem($pdo, $_POST);
                echo json_encode(['success' => true, 'data' => ['id' => $item_id]], JSON_UNESCAPED_UNICODE);
                break;

            default:
                throw new Exception('Ação não reconhecida');
        }

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }

    exit;
}

// Carregar dados para a view
try {
    $itens = listarItens($pdo);
} catch (Exception $e) {
    $itens = [];
}
?>
```

### **🔒 Segurança Obrigatória**
- **Sempre** verificar sessão e permissões
- **Usar** PDO prepared statements
- **Filtrar** e validar entrada de dados
- **Retornar** erros via JSON padronizados

## 🎨 **Template JavaScript**

### **Estrutura Padrão**
```javascript
/**
 * SIGEP [Nome Módulo] - JavaScript Principal
 * Funcionalidades AJAX + UI AdminLTE
 */

// Proteger contra múltiplos carregamentos no SPA
if (typeof window.[nomeModulo]Loaded === 'undefined') {
    window.[nomeModulo]Loaded = true;

// Variáveis globais
var currentData = {
    itens: [],
    usuarios: []
};

// Inicialização quando documento estiver pronto
$(document).ready(function() {
    carregarDados();
    inicializarComponentes();

    // Auto-refresh a cada 30 segundos (opcional)
    setInterval(autoRefresh, 30000);
});

// Carregar dados via AJAX
function carregarDados() {
    $.ajax({
        url: 'modulos/[setor]/[nome_modulo]/[nome_modulo]_logica.php',
        method: 'POST',
        data: { action: 'listar' },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                currentData.itens = response.data;
                atualizarInterface();
            }
        },
        error: function(xhr, status, error) {
            console.error('Erro ao carregar dados:', error);
            mostrarNotificacao('Erro ao carregar dados', 'error');
        }
    });
}

// Salvar item
function salvarItem() {
    const dados = {
        campo1: $('#campo1').val(),
        campo2: $('#campo2').val()
    };

    $.ajax({
        url: 'modulos/[setor]/[nome_modulo]/[nome_modulo]_logica.php',
        method: 'POST',
        data: {
            action: 'criar',
            ...dados
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                mostrarNotificacao('Item criado com sucesso', 'success');
                carregarDados();
            } else {
                mostrarNotificacao('Erro: ' + response.message, 'error');
            }
        },
        error: function() {
            mostrarNotificacao('Falha na comunicação', 'error');
        }
    });
}

// Utilitários
function mostrarNotificacao(mensagem, tipo = 'info') {
    // Usar sistema de notificações do SIGEP se disponível
    if (typeof toastr !== 'undefined') {
        toastr[tipo](mensagem);
    } else {
        console.log(`[${tipo.toUpperCase()}] ${mensagem}`);
    }
}

function atualizarInterface() {
    // Atualizar elementos da interface
    $('#stats-total').text(currentData.itens.length);
}

function autoRefresh() {
    carregarDados();
}

// Event listeners
$(document).on('click', '#btn-salvar', function() {
    salvarItem();
});

// Fechar bloco de proteção contra múltiplos carregamentos
} // fim do if (typeof window.[nomeModulo]Loaded === 'undefined')
```

### **⚠️ Proteção SPA**
- **Sempre** usar bloco condicional
- **Evita** redeclaração de variáveis
- **Funciona** com navegação AJAX

## 🎨 **Template CSS**

### **Estrutura Padrão**
```css
/**
 * SIGEP [Nome Módulo] - Estilos Customizados
 * Baseado em AdminLTE 3
 */

/* Cards Enhancement */
.small-box {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.small-box:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

/* Loading States */
.loading-spinner {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px;
    color: #6c757d;
}

.loading-spinner i {
    font-size: 2rem;
    margin-bottom: 1rem;
}

/* Empty States */
.empty-state {
    text-align: center;
    padding: 40px;
    color: #6c757d;
}

.empty-state i {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

/* Responsive Design */
@media (max-width: 768px) {
    .small-box {
        margin-bottom: 1rem;
    }
}

/* Animation Classes */
.fade-in {
    animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
```

## 🔗 **Integração com Sidebar**

### **Como Adicionar Menu**
1. **Abrir** `includes/sidebar_logica.php`
2. **Encontrar** função `getMenuConfig()`
3. **Adicionar** entrada no setor correspondente

### **Exemplo de Entrada**
```php
// Menu TI
if (podeVisualizarSetor('perm_ti')) {
    $menu['ti'] = [
        'title' => 'TI',
        'icon' => 'fa-duotone fa-solid fa-network-wired',
        'items' => [
            ['title' => 'Nome Módulo', 'icon' => 'fas fa-cog text-success', 'page' => '/modulos/ti/nome_modulo/nome_modulo_view.php', 'parent' => 'Nome Módulo']
        ]
    ];
}
```

### **Validação de Permissões**
- **Usar** `podeVisualizarSetor('perm_setor')`
- **Verificar** se usuário tem permissão
- **Definir** ícone e cores adequadas

## 🛣️ **Configuração .htaccess**

### **Como Adicionar URL Amigável**
1. **Abrir** arquivo `.htaccess` na raiz
2. **Adicionar** regra no final
3. **Testar** navegação

### **Exemplo de Regra**
```apache
# Friendly URL for [Nome Módulo] - [Setor]

RewriteRule ^[setor]/[nome_modulo]/?$ modulos/[setor]/[nome_modulo]/[nome_modulo]_view.php [L,QSA]
```

### **Boas Práticas**
- **Manter** ordem alfabética
- **Usar** comentários descritivos
- **Testar** imediatamente

## 🗄️ **Banco de Dados**

### **Padrão de Tabelas**
```sql
-- Tabela principal
CREATE TABLE nome_modulo_itens (
    id int(11) AUTO_INCREMENT PRIMARY KEY,
    nome varchar(255) NOT NULL,
    descricao text,
    usuario_criador int(11),
    status enum('ativo','inativo') DEFAULT 'ativo',
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_criador) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### **Regras Obrigatórias**
- **Charset**: utf8mb4
- **Engine**: InnoDB
- **Timestamps**: created_at, updated_at
- **Foreign Keys**: para tabela usuarios
- **Enums**: para status fixos

## 🚨 **Problemas Comuns e Soluções**

### **Headers Duplicados**
**Problema**: Título e breadcrumb aparecem duas vezes
**Causa**: Uso incorreto de `<section class="content-header">`
**Solução**: Usar apenas `<div class="row mb-2">` vazio

### **JavaScript Errors**
**Problema**: "Identifier already declared"
**Causa**: Múltiplos carregamentos no SPA
**Solução**: Proteger com bloco condicional

### **Permissões Negadas**
**Problema**: "Sem permissão para acessar"
**Causa**: Verificação de permissão incorreta
**Solução**: Verificar nome da permissão e sessão

### **AJAX Não Funciona**
**Problema**: Requisições retornam erro
**Causa**: Headers ou CORS incorretos
**Solução**: Verificar headers JSON e ob_clean()

### **CSS Não Aplica**
**Problema**: Estilos customizados não funcionam
**Causa**: Path incorreto ou cache
**Solução**: Verificar caminho relativo e limpar cache

## ✅ **Checklist Final**

### **Antes de Publicar**
- [ ] Estrutura MVC correta
- [ ] Headers sem duplicação
- [ ] JavaScript com proteção SPA
- [ ] Permissões configuradas
- [ ] URL amigável funcionando
- [ ] Banco de dados criado
- [ ] Interface responsiva
- [ ] Testes realizados

### **Testes Obrigatórios**
- [ ] Acesso via menu
- [ ] Acesso via URL direta
- [ ] CRUD funcionando
- [ ] Permissões negadas corretamente
- [ ] Mobile responsivo
- [ ] Navegação SPA

## 🎯 **Melhores Práticas**

### **Performance**
- **Minimizar** requisições AJAX
- **Usar** cache local quando possível
- **Otimizar** queries SQL

### **Segurança**
- **Validar** toda entrada de dados
- **Usar** prepared statements
- **Verificar** permissões sempre

### **UX/UI**
- **Feedback** visual para todas ações
- **Loading states** claros
- **Mensagens** de erro úteis

### **Código**
- **Comentar** funções complexas
- **Seguir** naming conventions
- **Manter** código limpo

---

**Este workflow serve como guia definitivo para criação de módulos SIGEP. Siga rigorosamente todos os passos para garantir integração perfeita com o sistema existente.**
