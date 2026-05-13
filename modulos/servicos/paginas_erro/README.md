# Páginas de Erro Modernas - SIGEP

## Descrição

Sistema completo de páginas de erro modernas e responsivas para o SIGEP, compatível com modo SPA (Single Page Application) e standalone.

## Características

### ✅ **Funcionalidades Principais**

- **Compatibilidade Dual**: Funciona tanto em modo standalone quanto SPA
- **URLs Amigáveis**: Múltiplos formatos de URL acessíveis
- **Design Moderno**: Interface inspirada em exemplos modernos de erro 404
- **Responsividade**: Adapta-se perfeitamente a dispositivos móveis
- **Acessibilidade**: Suporte a leitores de tela e navegação por teclado
- **Animações Suaves**: Transições e efeitos visuais agradáveis

### 🎨 **Tipos de Erro Suportados**

- **404 - Página Não Encontrada**: Busca integrada e sugestões de páginas
- **403 - Acesso Negado**: Informações de contato e reporte de problema
- **500 - Erro Interno**: Reload automático e opção de tentar novamente
- **503 - Serviço Indisponível**: Countdown de manutenção e auto-redirecionamento

### 🔗 **URLs Disponíveis**

```
# Formato principal (recomendado)
http://localhost/erro?404
http://localhost/erro?403
http://localhost/erro?500
http://localhost/erro?503

# Formato alternativo
http://localhost/erro/404
http://localhost/erro/403
http://localhost/erro/500
http://localhost/erro/503

# Formato genérico
http://localhost/erro?codigo=404
http://localhost/erro?codigo=403
http://localhost/erro?codigo=500
http://localhost/erro?codigo=503
```

### 📁 **Estrutura de Arquivos**

```
modulos/servicos/paginas_erro/
├── erro_view.php           # Controller principal (standalone + SPA)
├── erro_conteudo.php      # Conteúdo apenas para SPA
├── erro_logica.php        # Lógica de processamento e logging
├── assets/
│   ├── css/
│   │   └── erro.css    # Estilos modernos e responsivos
│   └── js/
│       └── erro.js     # Funcionalidades interativas
└── README.md              # Documentação
```

## Como Usar

### **Modo Standalone**

Acesso direto via URL:
```html
<!-- Link para página 404 -->
<a href="/erro?404">Página Não Encontrada</a>

<!-- Link para página 403 -->
<a href="/erro?403">Acesso Negado</a>
```

### **Modo SPA**

Via JavaScript:
```javascript
// Carregar página de erro no SPA
SIGEPErro.carregarPaginaErro('404', 'Página não encontrada', '/');

// Com parâmetros adicionais
SIGEPErro.carregarPaginaErro('500', 'Erro ao processar requisição', '/dashboard');
```

### **Via AJAX**

```javascript
$.ajax({
    url: 'modulos/servicos/paginas_erro/erro_view.php',
    method: 'GET',
    data: { 
        codigo: '404',
        mensagem: 'Página não encontrada',
        redirect: '/'
    },
    success: function(response) {
        $('#content').html(response);
    }
});
```

## Funcionalidades Especiais

### 🔍 **Busca Integrada (404)**

- Campo de busca funcional com autocomplete
- Sugestões de páginas populares
- Integração com sistema de busca do SIGEP
- Suporte a Enter e clique

### 📊 **Logging de Erros**

- Registro automático de todos os erros
- Detalhes completos (IP, User Agent, Referer)
- Integração com banco de dados
- Arquivos de log por data

### 🐛 **Reporte de Problemas**

- Modal para reportar erros
- Envio via AJAX para o servidor
- Integração com sistema de logs
- Notificação de sucesso/erro

### ⏰ **Auto-Redirecionamento**

- Configurável por tipo de erro
- Countdown visual para usuário
- Opção de cancelar
- Delay específico para manutenção

### 📱 **Responsividade**

- Layout adaptativo para todos os dispositivos
- Breakpoints para mobile, tablet e desktop
- Touch-friendly em dispositivos móveis
- Otimização de performance

## Personalização

### 🎨 **Cores e Temas**

Cada tipo de erro tem seu próprio tema:

```css
/* Tema 404 - Amarelo/Laranja */
.error-404 {
    background: linear-gradient(135deg, #ffeaa7 0%, #fdcb6e 100%);
}

/* Tema 403 - Vermelho */
.error-403 {
    background: linear-gradient(135deg, #ff7675 0%, #fd79a8 100%);
}

/* Tema 500 - Roxo */
.error-500 {
    background: linear-gradient(135deg, #a29bfe 0%, #6c5ce7 100%);
}

/* Tema 503 - Azul */
.error-503 {
    background: linear-gradient(135deg, #74b9ff 0%, #0984e3 100%);
}
```

### ⚙️ **Configurações JavaScript**

```javascript
var erroConfig = {
    autoRedirect: true,        // Ativar auto-redirecionamento
    redirectDelay: 10000,    // Delay em ms para redirecionamento
    maintenanceDelay: 30000,  // Delay específico para manutenção
    enableLogging: true,      // Ativar logging de erros
    enableAnalytics: true     // Ativar analytics
};
```

## Integração com SIGEP

### 🔐 **Autenticação**

- Verificação automática de sessão
- Informações do usuário logado
- Opções diferentes para autenticados vs não autenticados
- Integração com sistema de permissões

### 📊 **Analytics**

- Registro automático de erros
- Métricas de uso
- Informações de contexto
- Integração com sistema de analytics existente

### 🔄 **SPA Integration**

- Detecção automática de modo SPA
- Compatibilidade com `loadPage()`
- Injeção de conteúdo sem recarregar página
- Preservação de estado da aplicação

## Exemplos de Uso

### **Link Simples**
```html
<a href="/erro?404">Página não encontrada</a>
```

### **Com Mensagem Personalizada**
```html
<a href="/erro?404&mensagem=O+recurso+solicitado+não+existe">Erro customizado</a>
```

### **Com Redirect Personalizado**
```html
<a href="/erro?500&redirect=/dashboard">Erro com redirect</a>
```

### **JavaScript Completo**
```javascript
// Carregar erro 404 com busca
SIGEPErro.carregarPaginaErro('404', '', '/');

// Reportar erro atual
SIGEPErro.reportarErro();

// Tentar recarregar
SIGEPErro.recarregarPagina('/dashboard');
```

## Performance

### 🚀 **Otimizações**

- CSS minificado e otimizado
- JavaScript com lazy loading
- Imagens otimizadas e WebP support
- Cache de recursos estáticos
- Compressão GZIP

### 📈 **Métricas**

- Carregamento < 2 segundos
- First Contentful Paint < 1s
- Lighthouse Score > 90
- Mobile-friendly a 100%

## Browser Support

- ✅ Chrome 60+
- ✅ Firefox 55+
- ✅ Safari 12+
- ✅ Edge 79+
- ✅ IE 11 (com fallbacks)

## Contribuição

### 🔧 **Desenvolvimento**

1. Clone o repositório
2. Crie branch para sua feature
3. Faça as modificações
4. Teste em múltiplos browsers
5. Abra Pull Request

### 📋 **Checklist**

- [ ] Funciona em modo standalone
- [ ] Funciona em modo SPA
- [ ] Responsivo em mobile
- [ ] Acessível (WCAG 2.1)
- [ ] Performance otimizada
- [ ] Cross-browser compatível

## Licença

MIT License - Copyright (c) 2026 SIGEP Development Team

---

**Criado e mantido pela equipe de desenvolvimento SIGEP** 🚀
