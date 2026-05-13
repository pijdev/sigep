# 🌐 **Fluxo de Navegação SPA - Sistema SIGEP**

## **📋 Visão Geral**

O Sistema SIGEP implementa uma arquitetura **Single Page Application (SPA)** robusta baseada em AdminLTE 3, onde todas as páginas são carregadas dinamicamente via AJAX, garantindo uma experiência fluida e moderna de navegação.

---

## **🚨 REGRA CRÍTICA DE NAVEGAÇÃO**

### **❌ PROIBIDO - Acesso Direto via Arquivo**
```bash
# NUNCA acessar diretamente na URL:
http://localhost/paginas/caminhoes_pipa.php
http://localhost/modulos/laboral/gestao_ctc/gestao_ctc_view.php
http://localhost/includes/qualquer_coisa.php
```

### **✅ CORRETO - Navegação SPA**
```
## 🖥️ Como Acessar no SIGEP

### Passo 1: Acessar o Sistema
- Abra navegador e acesse: http://sigep.pij.local ou http://10.40.88.200/
- Faça login com credenciais completas

### Passo 2: Navegar no Menu Lateral
- Localize no sidebar a seção correspondente
- Clique na opção do módulo desejado
- Sistema carrega página dinamicamente via AJAX

### Passo 3: Localizar Funcionalidade
- Use filtros e navegação dentro do módulo
```

---

## **🔧 Arquitetura Técnica**

### **Estrutura Base SPA**
```
📁 C:\Program Files\Apache24\htdocs\sigep\
├── index.php              # Ponto de entrada da SPA
├── assets/js/main.js      # Funções de navegação
├── includes/
│   ├── sidebar_logica.php # Lógica do menu lateral
│   └── header.php         # Header global
└── paginas/               # Módulos carregados dinamicamente
    ├── caminhoes_pipa.php
    ├── censura_estoque_controle_v2.php
    └── ...
```

### **Função Principal: loadPage()**
```javascript
// assets/js/main.js
function loadPage(url, pushState = true) {
    // Mostrar loading
    showLoading();

    // AJAX request
    fetch(url)
        .then(response => response.text())
        .then(html => {
            // Injetar no container principal
            document.getElementById('main-content').innerHTML = html;

            // Atualizar histórico do browser
            if (pushState) {
                history.pushState({url: url}, '', '#' + url);
            }

            // Executar scripts da página
            executePageScripts(html);

            // Esconder loading
            hideLoading();
        })
        .catch(error => {
            console.error('Erro ao carregar página:', error);
            showError('Erro ao carregar página');
        });
}
```

### **Container Principal**
```html
<!-- index.php -->
<div id="main-content">
    <!-- Conteúdo dinâmico carregado aqui -->
</div>
```

---

## **🗂️ Componentes do Sistema de Navegação**

### **1. Menu Lateral Dinâmico**
**Arquivo**: `includes/sidebar_logica.php`

#### **Estrutura do Menu**
```php
function getMenuConfig() {
    $menu = [];

    // Menu Laboral
    if (podeVisualizarSetor('perm_laboral')) {
        $menu['laboral'] = [
            'title' => 'Laboral',
            'icon' => 'fa-solid fa-briefcase',
            'items' => [
                ['title' => 'Gestão CTC', 'icon' => 'fas fa-clipboard-check text-success', 'page' => '/modulos/laboral/gestao_ctc/gestao_ctc_view.php'],
                ['title' => 'Pecúlio', 'icon' => 'fas fa-coins text-warning', 'page' => '/paginas/laboral_peculio.php']
            ]
        ];
    }

    return $menu;
}
```

#### **Renderização HTML**
```html
<!-- Menu renderizado dinamicamente -->
<ul class="nav nav-pills nav-sidebar flex-column">
    <li class="nav-item">
        <a href="#" onclick="loadPage('paginas/caminhoes_pipa.php')" class="nav-link">
            <i class="nav-icon fas fa-truck-pickup"></i>
            <p>Controle Caminhões Pipa</p>
        </a>
    </li>
</ul>
```

### **2. Histórico do Browser**
```javascript
// Suporte a botão voltar/avançar
window.addEventListener('popstate', function(event) {
    if (event.state && event.state.url) {
        loadPage(event.state.url, false);
    }
});
```

### **3. Breadcrumb Dinâmico**
```javascript
function updateBreadcrumb(pageTitle, path = []) {
    const breadcrumb = document.getElementById('breadcrumb');
    let html = '<li class="breadcrumb-item"><a href="#" onclick="loadPage(\'index.php\')">Home</a></li>';

    path.forEach(item => {
        html += `<li class="breadcrumb-item">${item}</li>`;
    });

    html += `<li class="breadcrumb-item active">${pageTitle}</li>`;
    breadcrumb.innerHTML = html;
}
```

### **4. Estados de Loading**
```javascript
function showLoading() {
    const loader = document.getElementById('page-loader');
    if (loader) loader.style.display = 'block';
}

function hideLoading() {
    const loader = document.getElementById('page-loader');
    if (loader) loader.style.display = 'none';
}
```

---

## **🔐 Controle de Acesso e Permissões**

### **Verificação de Permissões**
```php
// includes/sidebar_logica.php
function podeVisualizarSetor($permissao) {
    return isset($_SESSION[$permissao]) && $_SESSION[$permissao] == 1;
}

// Verificação antes do carregamento
if (!podeAcessarModulo($modulo)) {
    echo json_encode(['error' => 'Acesso negado']);
    exit;
}
```

### **Menu Baseado em Perfil**
```php
// Diferentes menus para diferentes perfis
switch ($_SESSION['user_perfil']) {
    case 'admin':
        $menu = getMenuAdmin();
        break;
    case 'operador':
        $menu = getMenuOperador();
        break;
    case 'motorista':
        $menu = getMenuMotorista();
        break;
}
```

---

## **📱 Estados e Transições**

### **Estados da Navegação**
```javascript
const navigationStates = {
    LOADING: 'loading',
    LOADED: 'loaded',
    ERROR: 'error',
    TRANSITIONING: 'transitioning'
};

function setNavigationState(state) {
    document.body.setAttribute('data-nav-state', state);
}
```

### **Transições Suaves**
```css
/* Transições CSS para loading */
#main-content {
    transition: opacity 0.3s ease;
}

#main-content[data-loading="true"] {
    opacity: 0.7;
    pointer-events: none;
}
```

---

## **⚠️ Tratamento de Erros**

### **Erros de Carregamento**
```javascript
function handleNavigationError(error, url) {
    console.error(`Erro ao carregar ${url}:`, error);

    // Fallback para página de erro
    document.getElementById('main-content').innerHTML = `
        <div class="alert alert-danger">
            <h4>Erro ao carregar página</h4>
            <p>Não foi possível carregar a página solicitada.</p>
            <button onclick="retryLoad('${url}')" class="btn btn-primary">Tentar Novamente</button>
        </div>
    `;
}
```

### **Retry Automático**
```javascript
function retryLoad(url, attempts = 0) {
    const maxAttempts = 3;

    if (attempts >= maxAttempts) {
        handleNavigationError('Máximo de tentativas excedido', url);
        return;
    }

    setTimeout(() => {
        loadPage(url).catch(() => {
            retryLoad(url, attempts + 1);
        });
    }, 1000 * (attempts + 1));
}
```

---

## **🎯 Casos de Uso**

### **Navegação Básica**
1. **Usuário clica** em item do menu lateral
2. **Sistema chama** `loadPage(url)`
3. **Mostra loading** visual
4. **Carrega via AJAX** o conteúdo da página
5. **Injeta no DOM** o HTML retornado
6. **Executa scripts** da página carregada
7. **Atualiza breadcrumb** e histórico

### **Navegação com Histórico**
1. **Usuário usa** botão voltar do browser
2. **Sistema detecta** evento `popstate`
3. **Carrega página** do histórico sem pushState
4. **Mantém consistência** da navegação

### **Navegação com Erro**
1. **Falha no carregamento** da página
2. **Sistema mostra** mensagem de erro
3. **Oferece retry** automático
4. **Mantém usuário** na página atual

---

## **📊 Monitoramento e Métricas**

### **Métricas de Performance**
```javascript
// Tempo de carregamento das páginas
const loadStart = performance.now();

loadPage(url).then(() => {
    const loadTime = performance.now() - loadStart;
    console.log(`Página ${url} carregada em ${loadTime}ms`);

    // Enviar métrica para analytics
    sendMetric('page_load_time', loadTime);
});
```

### **Tracking de Navegação**
```javascript
// Rastreamento de páginas visitadas
function trackPageView(url) {
    // Enviar para sistema de analytics
    fetch('/api/analytics/track', {
        method: 'POST',
        body: JSON.stringify({
            page: url,
            timestamp: new Date().toISOString(),
            user: getCurrentUser()
        })
    });
}
```

---

## **🔧 Configuração e Setup**

### **Configurações Necessárias**
```php
// config.php - Configurações SPA
define('SPA_BASE_URL', '/sigep/');
define('SPA_LOADING_TIMEOUT', 30000); // 30 segundos
define('SPA_MAX_RETRIES', 3);
define('SPA_CACHE_ENABLED', true);
```

### **Inicialização**
```javascript
// Inicialização da SPA
document.addEventListener('DOMContentLoaded', function() {
    // Verificar se estamos em uma URL direta (não deve acontecer)
    const currentUrl = window.location.href;
    if (currentUrl.includes('/paginas/') || currentUrl.includes('/modulos/')) {
        // Redirecionar para SPA
        window.location.href = '/';
        return;
    }

    // Inicializar navegação
    initNavigation();

    // Carregar página inicial
    loadInitialPage();
});
```

---

## **🛠️ Desenvolvimento e Manutenção**

### **Padrões para Desenvolvedores**
1. **Nunca criar** links diretos para arquivos PHP
2. **Sempre usar** `loadPage()` para navegação
3. **Implementar** loading states em todas as páginas
4. **Tratar erros** adequadamente
5. **Testar navegação** com histórico do browser

### **Debugging**
```javascript
// Modo debug para desenvolvimento
if (window.location.hostname === 'localhost') {
    window.SPA_DEBUG = true;

    // Logs detalhados
    window.addEventListener('spa:pageLoaded', (e) => {
        console.log('Página carregada:', e.detail);
    });

    window.addEventListener('spa:navigationError', (e) => {
        console.error('Erro de navegação:', e.detail);
    });
}
```

---

## **📋 Próximos Passos**

### **Melhorias Planejadas**
- [ ] **Cache inteligente** de páginas frequentemente acessadas
- [ ] **Prefetching** de páginas adjacentes no menu
- [ ] **Service Worker** para offline
- [ ] **Progressive Web App** (PWA)
- [ ] **Lazy loading** de componentes
- [ ] **Virtual scrolling** para tabelas grandes

### **Monitoramento Contínuo**
- [ ] **Performance monitoring** de carregamento
- [ ] **Error tracking** em produção
- [ ] **User journey analytics**
- [ ] **A/B testing** de navegação

---

**Este fluxo garante que o SIGEP mantenha uma experiência de usuário moderna e fluida, com navegação rápida e intuitiva, seguindo os melhores padrões de SPAs atuais.**
