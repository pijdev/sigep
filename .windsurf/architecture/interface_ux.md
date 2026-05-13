# 🎨 **Interface e UX - Sistema SIGEP**

## **📋 Visão Geral da Interface**

O SIGEP utiliza AdminLTE 3 como framework base, customizado para atender às necessidades específicas do ambiente penitenciário, com foco em usabilidade, acessibilidade e experiência otimizada para diferentes perfis de usuários.

---

## **🎨 6.1 Padrões de UI (User Interface)**

### **🏗️ Framework Base: AdminLTE 3.2.0**

#### **Estrutura Principal do Layout**
```html
<div class="wrapper">
  <!-- Header Principal -->
  <nav class="main-header navbar navbar-expand navbar-dark">
    <!-- Logo e Branding -->
    <a href="index.html" class="navbar-brand">
      <img src="assets/img/sigep_light.png" alt="SIGEP Logo" class="brand-image img-circle elevation-3">
      <span class="brand-text font-weight-light">SIGEP</span>
    </a>
    
    <!-- Navbar Toggle -->
    <button class="navbar-toggler" type="button" data-widget="pushmenu">
      <i class="fas fa-bars"></i>
    </button>
    
    <!-- Navbar Right Menu -->
    <ul class="navbar-nav ml-auto">
      <!-- Notificações -->
      <li class="nav-item dropdown">
        <a class="nav-link" data-toggle="dropdown" href="#">
          <i class="far fa-bell"></i>
          <span class="badge badge-danger navbar-badge" id="notification-count">3</span>
        </a>
      </li>
      
      <!-- Perfil do Usuário -->
      <li class="nav-item dropdown user-menu">
        <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown">
          <img src="assets/img/default-avatar.png" class="user-image img-circle elevation-2">
          <span class="d-none d-md-inline"><?= $_SESSION['user_nome'] ?></span>
        </a>
      </li>
    </ul>
  </nav>
  
  <!-- Sidebar Principal -->
  <aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Logo Sidebar -->
    <a href="index.html" class="brand-link">
      <img src="assets/img/sigep_light.png" alt="SIGEP Logo" class="brand-image img-circle elevation-3">
    </a>
    
    <!-- Sidebar Menu -->
    <div class="sidebar">
      <!-- Menu Dinâmico Baseado em Permissões -->
      <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
          <!-- Itens gerados dinamicamente via PHP -->
        </ul>
      </nav>
    </div>
  </aside>
  
  <!-- Content Wrapper -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0"><?= $page_title ?></h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="/">Home</a></li>
              <li class="breadcrumb-item active"><?= $page_title ?></li>
            </ol>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Main Content -->
    <section class="content">
      <!-- Conteúdo dinâmico dos módulos -->
    </section>
  </div>
  
  <!-- Footer -->
  <footer class="main-footer">
    <strong>Copyright &copy; 2024 SIGEP.</strong> Todos os direitos reservados.
  </footer>
</div>
```

### **🎨 Sistema de Cores e Temas**

#### **Paleta de Cores Institucional**
```css
:root {
  /* Cores Primárias */
  --sigep-primary: #007bff;
  --sigep-primary-dark: #0056b3;
  --sigep-primary-light: #66b3ff;
  
  /* Cores Secundárias */
  --sigep-secondary: #6c757d;
  --sigep-success: #28a745;
  --sigep-warning: #ffc107;
  --sigep-danger: #dc3545;
  --sigep-info: #17a2b8;
  
  /* Cores de Status */
  --status-ativo: #28a745;
  --status-inativo: #dc3545;
  --status-pendente: #ffc107;
  --status-concluido: #17a2b8;
  
  /* Cores de Setor */
  --censura-color: #6f42c1;
  --eclusa-color: #fd7e14;
  --laboral-color: #20c997;
  --almoxarifado-color: #0d6efd;
}
```

#### **Modo Claro/Escuro**
```css
/* Dark Mode */
.dark-mode {
  --sidebar-bg: #343a40;
  --sidebar-text: #ffffff;
  --content-bg: #1e1e1e;
  --content-text: #ffffff;
  --card-bg: #2d2d2d;
  --border-color: #495057;
}

/* Light Mode (Padrão) */
.light-mode {
  --sidebar-bg: #343a40;
  --sidebar-text: #ffffff;
  --content-bg: #f8f9fa;
  --content-text: #343a40;
  --card-bg: #ffffff;
  --border-color: #dee2e6;
}
```

### **📱 Componentes UI Padrão**

#### **Cards e Widgets**
```html
<!-- Small Box (Estatísticas) -->
<div class="small-box bg-info">
  <div class="inner">
    <h3><?= $numero_cartas_hoje ?></h3>
    <p>Cartas Registradas Hoje</p>
  </div>
  <div class="icon">
    <i class="fas fa-envelope"></i>
  </div>
  <a href="#" class="small-box-footer">
    Ver mais <i class="fas fa-arrow-circle-right"></i>
  </a>
</div>

<!-- Info Box (Indicadores) -->
<div class="info-box">
  <span class="info-box-icon bg-warning"><i class="fas fa-clock"></i></span>
  <div class="info-box-content">
    <span class="info-box-text">Movimentações Pendentes</span>
    <span class="info-box-number"><?= $movimentacoes_pendentes ?></span>
  </div>
</div>

<!-- Card Padrão -->
<div class="card">
  <div class="card-header">
    <h3 class="card-title"><?= $title ?></h3>
    <div class="card-tools">
      <button type="button" class="btn btn-tool" data-card-widget="collapse">
        <i class="fas fa-minus"></i>
      </button>
    </div>
  </div>
  <div class="card-body">
    <!-- Conteúdo do card -->
  </div>
</div>
```

#### **Formulários Padrão**
```html
<!-- Formulário de Registro -->
<form id="form-registro" class="needs-validation" novalidate>
  <div class="row">
    <div class="col-md-6">
      <div class="form-group">
        <label for="interno_nome">Nome do Interno <span class="text-danger">*</span></label>
        <select class="form-control" id="interno_nome" name="interno_nome" required>
          <option value="">Selecione...</option>
          <!-- Options geradas dinamicamente -->
        </select>
        <div class="invalid-feedback">
          Por favor, selecione um interno.
        </div>
      </div>
    </div>
    
    <div class="col-md-6">
      <div class="form-group">
        <label for="data_recebimento">Data de Recebimento</label>
        <input type="date" class="form-control" id="data_recebimento" name="data_recebimento">
      </div>
    </div>
  </div>
  
  <div class="row">
    <div class="col-12">
      <div class="form-group">
        <label for="observacoes">Observações</label>
        <textarea class="form-control" id="observacoes" name="observacoes" rows="3"></textarea>
      </div>
    </div>
  </div>
  
  <div class="row">
    <div class="col-12">
      <button type="submit" class="btn btn-primary">
        <i class="fas fa-save"></i> Salvar
      </button>
      <button type="button" class="btn btn-secondary" onclick="limparFormulario()">
        <i class="fas fa-times"></i> Cancelar
      </button>
    </div>
  </div>
</form>
```

#### **Tabelas de Dados**
```html
<!-- Tabela com DataTables -->
<table id="tabela-dados" class="table table-bordered table-striped">
  <thead>
    <tr>
      <th>ID</th>
      <th>Interno</th>
      <th>Correspondente</th>
      <th>Status</th>
      <th>Data</th>
      <th>Ações</th>
    </tr>
  </thead>
  <tbody>
    <!-- Dados gerados dinamicamente -->
  </tbody>
</table>
```

---

## **🧭 6.2 Navegação e UX**

### **🔄 Sistema de Navegação SPA (Single Page Application)**

#### **Função Principal loadPage()**
```javascript
// assets/js/loadPage.js
function loadPage(page, params = {}) {
    // Loading state
    showLoading();
    
    // Atualizar URL sem refresh
    const url = page + (Object.keys(params).length > 0 ? '?' + new URLSearchParams(params).toString() : '');
    history.pushState({page: page, params: params}, '', url);
    
    // Requisição AJAX para carregar conteúdo
    $.ajax({
        url: page,
        type: 'GET',
        data: params,
        dataType: 'html',
        success: function(response) {
            $('#content-wrapper .content').html(response);
            
            // Atualizar título da página
            updatePageTitle(page);
            
            // Inicializar componentes da página
            initializePageComponents(page);
            
            hideLoading();
        },
        error: function(xhr, status, error) {
            console.error('Erro ao carregar página:', error);
            showError('Não foi possível carregar a página. Tente novamente.');
            hideLoading();
        }
    });
}
```

#### **Breadcrumb Dinâmico**
```javascript
function updateBreadcrumb(pageTitle, items = []) {
    let breadcrumb = '<ol class="breadcrumb float-sm-right">';
    breadcrumb += '<li class="breadcrumb-item"><a href="/">Home</a></li>';
    
    items.forEach(item => {
        if (item.active) {
            breadcrumb += `<li class="breadcrumb-item active">${item.label}</li>`;
        } else {
            breadcrumb += `<li class="breadcrumb-item"><a href="${item.url}">${item.label}</a></li>`;
        }
    });
    
    breadcrumb += '</ol>';
    $('.content-header .breadcrumb').html(breadcrumb);
}
```

### **🎯 Experiência do Usuário**

#### **Feedback Visual Imediato**
```javascript
// Feedback para ações do usuário
function showSuccess(message, title = 'Sucesso') {
    Swal.fire({
        icon: 'success',
        title: title,
        text: message,
        timer: 3000,
        showConfirmButton: false
    });
}

function showError(message, title = 'Erro') {
    Swal.fire({
        icon: 'error',
        title: title,
        text: message,
        confirmButtonText: 'OK'
    });
}

function showLoading(message = 'Carregando...') {
    const loadingHtml = `
        <div class="loading-overlay">
            <div class="spinner-border text-primary" role="status">
                <span class="sr-only">${message}</span>
            </div>
            <div class="mt-2">${message}</div>
        </div>
    `;
    $('body').append(loadingHtml);
}
```

#### **Validação em Tempo Real**
```javascript
// Validação de formulários em tempo real
$('#form-registro input, #form-registro select, #form-registro textarea').on('input blur', function() {
    const field = $(this);
    const value = field.val();
    const fieldName = field.attr('name');
    
    // Remover feedback anterior
    field.removeClass('is-valid is-invalid');
    field.next('.invalid-feedback, .valid-feedback').remove();
    
    // Validação específica do campo
    if (validateField(fieldName, value)) {
        field.addClass('is-valid');
        field.after('<div class="valid-feedback">Campo válido</div>');
    } else {
        field.addClass('is-invalid');
        field.after('<div class="invalid-feedback">Campo inválido</div>');
    }
});
```

### **🔍 Busca e Filtros**

#### **Sistema de Busca Global**
```html
<!-- Barra de busca global -->
<div class="input-group">
    <input type="text" class="form-control" id="busca-global" placeholder="Buscar em todo o sistema...">
    <div class="input-group-append">
        <button class="btn btn-navbar" type="button">
            <i class="fas fa-search"></i>
        </button>
    </div>
</div>
```

```javascript
// Busca global AJAX
$('#busca-global').on('input', debounce(function() {
    const term = $(this).val();
    
    if (term.length >= 3) {
        $.ajax({
            url: '/api/busca-global',
            type: 'GET',
            data: {term: term},
            dataType: 'json',
            success: function(results) {
                displaySearchResults(results);
            }
        });
    } else {
        hideSearchResults();
    }
}, 300));
```

#### **Filtros Avançados**
```html
<!-- Filtros por módulo -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Filtros</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                <i class="fas fa-minus"></i>
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label for="filtro-status">Status</label>
                    <select class="form-control" id="filtro-status">
                        <option value="">Todos</option>
                        <option value="Liberada">Liberada</option>
                        <option value="Retida">Retida</option>
                        <option value="Devolvida">Devolvida</option>
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="filtro-data-inicio">Data Início</label>
                    <input type="date" class="form-control" id="filtro-data-inicio">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="filtro-data-fim">Data Fim</label>
                    <input type="date" class="form-control" id="filtro-data-fim">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>&nbsp;</label>
                    <button type="button" class="btn btn-primary btn-block" onclick="aplicarFiltros()">
                        <i class="fas fa-filter"></i> Aplicar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
```

---

## **♿ 6.3 Acessibilidade**

### **🎯 WCAG 2.1 Compliance**

#### **Navegação por Teclado**
```html
<!-- Tab order correto -->
<button tabindex="1" onclick="primeiraAcao()">Primeira Ação</button>
<input type="text" tabindex="2" id="campo1" placeholder="Campo 1">
<input type="text" tabindex="3" id="campo2" placeholder="Campo 2">
<button tabindex="4" onclick="segundaAcao()">Segunda Ação</button>

<!-- Skip links para navegação rápida -->
<a href="#main-content" class="skip-link">Ir para o conteúdo principal</a>
<a href="#navigation" class="skip-link">Ir para navegação</a>
```

#### **ARIA Labels e Roles**
```html
<!-- Formulários acessíveis -->
<form role="form" aria-labelledby="form-title">
    <h2 id="form-title">Registro de Correspondência</h2>
    
    <div class="form-group">
        <label for="interno-nome" id="interno-label">Nome do Interno</label>
        <select 
            id="interno-nome" 
            name="interno_nome" 
            class="form-control"
            aria-labelledby="interno-label"
            aria-describedby="interno-help"
            required
            aria-required="true">
            <option value="">Selecione...</option>
        </select>
        <small id="interno-help" class="form-text text-muted">
            Selecione o interno da lista
        </small>
    </div>
</form>

<!-- Status de operações -->
<div id="status-message" role="status" aria-live="polite" aria-atomic="true">
    <!-- Mensagens de status para screen readers -->
</div>

<!-- Regiões landmark -->
<header role="banner">
    <!-- Header do site -->
</header>

<nav role="navigation" aria-label="Menu principal">
    <!-- Navegação principal -->
</nav>

<main id="main-content" role="main" aria-label="Conteúdo principal">
    <!-- Conteúdo principal -->
</main>

<aside role="complementary" aria-label="Informações adicionais">
    <!-- Sidebar -->
</aside>

<footer role="contentinfo">
    <!-- Footer -->
</footer>
```

#### **Contraste e Legibilidade**
```css
/* Alto contraste para melhor legibilidade */
.text-high-contrast {
    color: #000000 !important;
    background-color: #ffffff !important;
}

/* Focus indicators visíveis */
.btn:focus,
input:focus,
select:focus,
textarea:focus {
    outline: 3px solid #007bff;
    outline-offset: 2px;
}

/* Texto escalável */
@media (prefers-reduced-motion: no-preference) {
    body {
        font-size: 16px; /* Base size para zoom */
    }
}

/* Modo de alto contraste */
@media (prefers-contrast: high) {
    .card {
        border: 2px solid #000000;
    }
    
    .btn {
        border: 2px solid currentColor;
    }
}
```

### **👂 Suporte a Leitores de Tela**

#### **Estrutura Semântica**
```html
<!-- Tabelas acessíveis -->
<table role="table" aria-label="Lista de correspondências">
    <caption>Correspondências registradas no sistema</caption>
    <thead>
        <tr>
            <th scope="col" aria-sort="none">ID</th>
            <th scope="col" aria-sort="none">Interno</th>
            <th scope="col" aria-sort="none">Status</th>
            <th scope="col" aria-sort="none">Ações</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>123</td>
            <td>João Silva</td>
            <td>
                <span class="badge badge-success" aria-label="Status: Liberada">Liberada</span>
            </td>
            <td>
                <button aria-label="Editar correspondência 123" onclick="editar(123)">
                    <i class="fas fa-edit"></i>
                </button>
            </td>
        </tr>
    </tbody>
</table>
```

#### **Notificações Acessíveis**
```javascript
// Notificações para screen readers
function announceToScreenReader(message, priority = 'polite') {
    const announcement = document.createElement('div');
    announcement.setAttribute('aria-live', priority);
    announcement.setAttribute('aria-atomic', 'true');
    announcement.className = 'sr-only';
    announcement.textContent = message;
    
    document.body.appendChild(announcement);
    
    // Remover após anúncio
    setTimeout(() => {
        document.body.removeChild(announcement);
    }, 1000);
}

// Uso em operações
function salvarDados() {
    // Salvar dados...
    
    // Anunciar sucesso
    announceToScreenReader('Dados salvos com sucesso', 'polite');
    
    // Mostrar notificação visual
    showSuccess('Dados salvos com sucesso');
}
```

---

## **🌍 6.4 Internacionalização (i18n)**

### **🗂️ Sistema de Múltiplos Idiomas**

#### **Estrutura de Traduções**
```javascript
// assets/js/i18n.js
const translations = {
    pt: {
        // Geral
        'home': 'Início',
        'logout': 'Sair',
        'save': 'Salvar',
        'cancel': 'Cancelar',
        'edit': 'Editar',
        'delete': 'Excluir',
        'search': 'Buscar',
        'filter': 'Filtrar',
        
        // Módulos
        'censura': 'Censura',
        'eclusa': 'Eclusa',
        'laboral': 'Laboral',
        'almoxarifado': 'Almoxarifado',
        
        // Mensagens
        'success_save': 'Dados salvos com sucesso',
        'error_required': 'Campo obrigatório',
        'confirm_delete': 'Tem certeza que deseja excluir?',
        
        // Cartas
        'cartas_title': 'Cartas',
        'carta_entrada': 'Carta de Entrada',
        'carta_saida': 'Carta de Saída',
        'correspondente': 'Correspondente',
        'status_liberada': 'Liberada',
        'status_retida': 'Retida',
        'status_devolvida': 'Devolvida'
    },
    
    en: {
        // General
        'home': 'Home',
        'logout': 'Logout',
        'save': 'Save',
        'cancel': 'Cancel',
        'edit': 'Edit',
        'delete': 'Delete',
        'search': 'Search',
        'filter': 'Filter',
        
        // Modules
        'censura': 'Censorship',
        'eclusa': 'Transfer',
        'laboral': 'Labor',
        'almoxarifado': 'Warehouse',
        
        // Messages
        'success_save': 'Data saved successfully',
        'error_required': 'Required field',
        'confirm_delete': 'Are you sure you want to delete?',
        
        // Letters
        'cartas_title': 'Letters',
        'carta_entrada': 'Incoming Letter',
        'carta_saida': 'Outgoing Letter',
        'correspondente': 'Correspondent',
        'status_liberada': 'Released',
        'status_retida': 'Retained',
        'status_devolvida': 'Returned'
    }
};

// Função de tradução
function t(key, params = {}) {
    const lang = getCurrentLanguage();
    let text = translations[lang][key] || key;
    
    // Substituir parâmetros
    Object.keys(params).forEach(param => {
        text = text.replace(`{${param}}`, params[param]);
    });
    
    return text;
}
```

#### **Implementação no Frontend**
```html
<!-- Elementos com data-i18n -->
<h1 data-i18n="cartas_title">Cartas</h1>
<button type="button" class="btn btn-primary" data-i18n="save">Salvar</button>
<span data-i18n="status_liberada">Liberada</span>

<!-- Placeholder dinâmicos -->
<input type="text" placeholder="placeholder_buscar" data-i18n-placeholder="search">

<!-- Tooltips -->
<button type="button" title="title_editar" data-i18n-title="edit">
    <i class="fas fa-edit"></i>
</button>
```

```javascript
// Aplicar traduções ao carregar página
function applyTranslations() {
    const lang = getCurrentLanguage();
    
    // Traduzir elementos com data-i18n
    $('[data-i18n]').each(function() {
        const key = $(this).data('i18n');
        $(this).text(t(key));
    });
    
    // Traduzir placeholders
    $('[data-i18n-placeholder]').each(function() {
        const key = $(this).data('i18n-placeholder');
        $(this).attr('placeholder', t(key));
    });
    
    // Traduzir tooltips
    $('[data-i18n-title]').each(function() {
        const key = $(this).data('i18n-title');
        $(this).attr('title', t(key));
    });
    
    // Traduzir opções de select
    $('select[data-i18n-options]').each(function() {
        const optionsKey = $(this).data('i18n-options');
        const options = translations[lang][optionsKey];
        
        $(this).find('option').each(function(index) {
            if (options[index]) {
                $(this).text(options[index]);
            }
        });
    });
}

// Mudar idioma
function changeLanguage(lang) {
    localStorage.setItem('sigep_lang', lang);
    location.reload();
}
```

#### **Implementação no Backend (PHP)**
```php
// includes/i18n.php
class Translator {
    private $lang;
    private $translations = [];
    
    public function __construct($lang = 'pt') {
        $this->lang = $lang;
        $this->loadTranslations();
    }
    
    private function loadTranslations() {
        $file = __DIR__ . "/../assets/i18n/{$this->lang}.json";
        if (file_exists($file)) {
            $this->translations = json_decode(file_get_contents($file), true);
        }
    }
    
    public function translate($key, $params = []) {
        $text = $this->translations[$key] ?? $key;
        
        foreach ($params as $param => $value) {
            $text = str_replace("{{$param}}", $value, $text);
        }
        
        return $text;
    }
    
    public function __($key, $params = []) {
        return $this->translate($key, $params);
    }
}

// Uso nas views
$translator = new Translator($_SESSION['user_lang'] ?? 'pt');

<h1><?= $translator->__('cartas_title') ?></h1>
<button><?= $translator->__('save') ?></button>
```

### **🌐 Suporte a Localização**

#### **Formatação de Datas e Números**
```javascript
// Formatação de datas
function formatDate(date, lang = 'pt') {
    const options = {
        pt: { day: '2-digit', month: '2-digit', year: 'numeric' },
        en: { year: 'numeric', month: 'short', day: '2-digit' }
    };
    
    return new Date(date).toLocaleDateString(lang, options[lang]);
}

// Formatação de números
function formatNumber(number, lang = 'pt') {
    const options = {
        pt: { style: 'decimal', minimumFractionDigits: 2 },
        en: { style: 'decimal', minimumFractionDigits: 2 }
    };
    
    return new Intl.NumberFormat(lang, options[lang]).format(number);
}

// Formatação de moeda
function formatCurrency(value, lang = 'pt') {
    const options = {
        pt: { style: 'currency', currency: 'BRL' },
        en: { style: 'currency', currency: 'USD' }
    };
    
    return new Intl.NumberFormat(lang, options[lang]).format(value);
}
```

#### **Timezones e Horários**
```php
// Configuração de timezone por idioma
function getTimezoneByLanguage($lang) {
    $timezones = [
        'pt' => 'America/Sao_Paulo',
        'en' => 'America/New_York',
        'es' => 'Europe/Madrid'
    ];
    
    return $timezones[$lang] ?? 'UTC';
}

// Formatação de data/hora localizada
function formatLocalizedDateTime($datetime, $lang = 'pt') {
    $timezone = getTimezoneByLanguage($lang);
    $date = new DateTime($datetime, new DateTimeZone($timezone));
    
    $formats = [
        'pt' => 'd/m/Y H:i',
        'en' => 'm/d/Y h:i A'
    ];
    
    return $date->format($formats[$lang]);
}
```

---

## **📱 6.5 Responsividade e Mobile**

### **📱 Design Mobile-First**

#### **Breakpoints Responsivos**
```css
/* Breakpoints */
@media (max-width: 575.98px) {
    /* Extra small devices (phones) */
    .main-sidebar {
        margin-left: -250px;
    }
    
    .main-sidebar.sidebar-open {
        margin-left: 0;
    }
    
    .content-wrapper {
        margin-left: 0;
    }
    
    .card-body {
        padding: 1rem;
    }
    
    .table-responsive {
        font-size: 0.875rem;
    }
}

@media (min-width: 576px) and (max-width: 767.98px) {
    /* Small devices (landscape phones) */
    .col-sm-6 {
        flex: 0 0 50%;
        max-width: 50%;
    }
}

@media (min-width: 768px) and (max-width: 991.98px) {
    /* Medium devices (tablets) */
    .main-sidebar {
        margin-left: 0;
    }
    
    .content-wrapper {
        margin-left: 250px;
    }
}

@media (min-width: 992px) {
    /* Large devices (desktops) */
    .main-sidebar {
        margin-left: 0;
    }
    
    .content-wrapper {
        margin-left: 250px;
    }
}
```

#### **Componentes Mobile-First**
```html
<!-- Cards responsivos -->
<div class="row">
    <div class="col-12 col-md-6 col-lg-3">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>150</h3>
                <p>Cartas Hoje</p>
            </div>
            <div class="icon">
                <i class="fas fa-envelope"></i>
            </div>
        </div>
    </div>
    <!-- Outros cards -->
</div>

<!-- Tabelas responsivas -->
<div class="table-responsive">
    <table class="table table-bordered">
        <!-- Conteúdo da tabela -->
    </table>
</div>

<!-- Formulários responsivos -->
<form>
    <div class="row">
        <div class="col-12 col-md-6">
            <div class="form-group">
                <label for="campo1">Campo 1</label>
                <input type="text" class="form-control" id="campo1">
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="form-group">
                <label for="campo2">Campo 2</label>
                <input type="text" class="form-control" id="campo2">
            </div>
        </div>
    </div>
</form>
```

### **📊 Touch-Friendly Interface**

#### **Botões e Controles Touch**
```css
/* Botões touch-friendly */
.btn {
    min-height: 44px; /* Mínimo para touch */
    min-width: 44px;
    padding: 0.75rem 1.25rem;
}

/* Espaçamento para evitar toques acidentais */
.btn + .btn {
    margin-top: 0.5rem;
}

@media (max-width: 767.98px) {
    .btn-group {
        display: flex;
        flex-direction: column;
    }
    
    .btn-group .btn {
        border-radius: 0.25rem;
        margin-bottom: 0.5rem;
    }
}

/* Inputs touch-friendly */
.form-control {
    min-height: 44px;
    font-size: 16px; /* Evita zoom no iOS */
}

/* Selects touch-friendly */
select.form-control {
    appearance: none;
    background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6,9 12,15 18,9'%3E%3C/polyline%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 0.75rem center;
    background-size: 1.5rem;
    padding-right: 2.5rem;
}
```

---

## **🎨 6.6 Personalização e Temas**

### **🎨 Sistema de Temas**

#### **Configuração de Temas**
```javascript
// assets/js/themes.js
const themes = {
    light: {
        name: 'Claro',
        sidebar: 'bg-white',
        sidebarText: 'text-dark',
        content: 'bg-gray-100',
        contentText: 'text-dark',
        card: 'bg-white',
        border: 'border-gray-300'
    },
    dark: {
        name: 'Escuro',
        sidebar: 'bg-gray-800',
        sidebarText: 'text-white',
        content: 'bg-gray-900',
        contentText: 'text-white',
        card: 'bg-gray-800',
        border: 'border-gray-700'
    },
    blue: {
        name: 'Azul',
        sidebar: 'bg-blue-900',
        sidebarText: 'text-white',
        content: 'bg-blue-50',
        contentText: 'text-gray-900',
        card: 'bg-white',
        border: 'border-blue-200'
    }
};

function applyTheme(themeName) {
    const theme = themes[themeName];
    
    // Remover classes de tema anteriores
    Object.values(themes).forEach(t => {
        $(`.main-sidebar`).removeClass(t.sidebar);
        $(`.main-sidebar`).removeClass(t.sidebarText);
        $(`.content-wrapper`).removeClass(t.content);
        $(`.content-wrapper`).removeClass(t.contentText);
        $('.card').removeClass(t.card);
        $('.card').removeClass(t.border);
    });
    
    // Aplicar novo tema
    $('.main-sidebar').addClass(theme.sidebar);
    $('.main-sidebar').addClass(theme.sidebarText);
    $('.content-wrapper').addClass(theme.content);
    $('.content-wrapper').addClass(theme.contentText);
    $('.card').addClass(theme.card);
    $('.card').addClass(theme.border);
    
    // Salvar preferência
    localStorage.setItem('sigep_theme', themeName);
    
    // Atualizar botão de tema
    updateThemeButton(themeName);
}
```

#### **Personalização por Setor**
```php
// Cores por setor de usuário
$sectorColors = [
    'Censura' => [
        'primary' => '#6f42c1',
        'secondary' => '#9c88ff',
        'accent' => '#e9d5ff'
    ],
    'Eclusa' => [
        'primary' => '#fd7e14',
        'secondary' => '#ffa94d',
        'accent' => '#fff4e6'
    ],
    'Laboral' => [
        'primary' => '#20c997',
        'secondary' => '#63e6be',
        'accent' => '#d3f9d8'
    ],
    'Almoxarifado' => [
        'primary' => '#0d6efd',
        'secondary' => '#74c0fc',
        'accent' => '#cfe2ff'
    ]
];

$userSector = $_SESSION['user_setor'] ?? 'Default';
$colors = $sectorColors[$userSector] ?? $sectorColors['Default'];

// Aplicar cores dinâmicas
echo "<style>
:root {
    --sigep-primary: {$colors['primary']};
    --sigep-secondary: {$colors['secondary']};
    --sigep-accent: {$colors['accent']};
}
</style>";
```

---

## **📊 6.7 Performance da Interface**

### **⚡ Otimizações de Carregamento**

#### **Lazy Loading de Componentes**
```javascript
// Lazy loading de imagens
function lazyLoadImages() {
    const images = document.querySelectorAll('img[data-src]');
    
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.removeAttribute('data-src');
                observer.unobserve(img);
            }
        });
    });
    
    images.forEach(img => imageObserver.observe(img));
}

// Lazy loading de módulos
function loadModuleOnDemand(moduleName) {
    return import(`/assets/js/modules/${moduleName}.js`)
        .then(module => {
            module.initialize();
        })
        .catch(error => {
            console.error(`Erro ao carregar módulo ${moduleName}:`, error);
        });
}
```

#### **Cache de Componentes**
```javascript
// Cache de templates
const templateCache = new Map();

function getTemplate(templateName) {
    if (templateCache.has(templateName)) {
        return Promise.resolve(templateCache.get(templateName));
    }
    
    return fetch(`/templates/${templateName}.html`)
        .then(response => response.text())
        .then(template => {
            templateCache.set(templateName, template);
            return template;
        });
}
```

---

## **🔗 Documentação Relacionada**

### **📚 Componentes de UI**
- **[Stack Tecnológico](stack_tecnologico.md)** - AdminLTE 3 e configurações
- **[Estrutura do Código](estrutura_codigo.md)** - Organização de assets
- **[Fluxos de Navegação](../fluxos/navegacao_spa.md)** - Sistema SPA *(planejado)*

### **🎨 Recursos Externos**
- **[AdminLTE 3 Docs](https://adminlte.io/docs/3.0/)** - Documentação oficial
- **[Bootstrap 4](https://getbootstrap.com/docs/4.5/)** - Framework base
- **[FontAwesome 6](https://fontawesome.com/docs/)** - Ícones
- **[jQuery](https://jquery.com/)** - Biblioteca JavaScript

---

## **📋 Checklist de UI/UX**

### **✅ Implementações Atuais**
- [x] **AdminLTE 3 Completo**: Todos os componentes implementados
- [x] **Navegação SPA**: loadPage() com AJAX
- [x] **Responsividade**: Mobile-first design
- [x] **Acessibilidade**: WCAG 2.1 compliance básico
- [x] **Internacionalização**: PT/EN implementado
- [x] **Temas**: Claro/Escuro com personalização
- [x] **Feedback Visual**: Toasts e modais
- [x] **Validação em Tempo Real**: Formulários reativos

### **🔄 Melhorias Planejadas**
- [ ] **Acessibilidade Avançada**: Suporte completo a screen readers
- [ ] **Performance**: Lazy loading e cache avançado
- [ ] **Animações**: Transições suaves e micro-interações
- [ ] **Dark Mode Avançado**: Mais opções de personalização
- [ ] **Componentes Customizados**: Biblioteca de componentes SIGEP

---

**Esta seção de Interface e UX documenta completamente o sistema de apresentação do SIGEP, garantindo uma experiência de usuário otimizada, acessível e alinhada com as necessidades específicas do ambiente penitenciário.**
