# 🏗️ **Arquitetura Completa do SIGEP**

> **📋 IMPORTANTE**: Esta documentação foi modularizada para melhor organização.
> Acesse o **[Índice Principal](index.md)** para navegação completa.

---

## **📋 Estrutura Modular da Documentação**

O conhecimento do SIGEP foi reorganizado em arquivos especializados para facilitar manutenção e acesso rápido:

### **📚 Seções Principais**
1. **[🎯 Visão Geral e Contexto](visao_geral.md)** - Propósito, escopo e stakeholders
2. **[🔧 Stack Tecnológico](stack_tecnologico.md)** - Tecnologias e configurações
3. **[📁 Estrutura do Código](estrutura_codigo.md)** - Organização e padrões
4. **[🔄 Fluxos e Processos](../fluxos/index.md)** - Operações do sistema

### **🗂️ Componentes Específicos**
- **[🗄️ Banco de Dados](database/schema_completo.md)** - Schema completo do MySQL
- **[💻 Caminhos Windows](paths/windows_complete.md)** - Paths completos do sistema
- **[🔒 Segurança](security/)** - Implementações de segurança *(em desenvolvimento)*
- **[📋 Padrões](patterns/)** - Convenções e melhores práticas *(em desenvolvimento)*

---

## **🚀 Acesso Rápido**

### **Para Início Rápido**
```bash
# Visão geral do sistema
📄 visao_geral.md

# Configuração de ambiente
📄 stack_tecnologico.md
📄 paths/windows_complete.md

# Desenvolvimento
📄 estrutura_codigo.md
📁 ../patterns/mvc_pattern.md
```

### **Para Desenvolvimento**
```bash
# Banco de dados
📄 database/schema_completo.md

# Fluxos específicos
📁 ../fluxos/autenticacao.md
📁 ../fluxos/navegacao_spa.md
📁 ../fluxos/censura_cartas.md
```

### **Para Manutenção**
```bash
# Configuração e troubleshooting
📄 stack_tecnologico.md
📄 paths/windows_complete.md

# Auditoria e segurança
📁 ../fluxos/autenticacao.md
📁 database/schema_completo.md
```

---

## **🔗 Como Navegar**

### **Estrutura Hierárquica**
```
.windsurf/architecture/
├── 📄 index.md                    # ← Índice principal
├── 📄 visao_geral.md              # Visão geral
├── 📄 stack_tecnologico.md          # Stack completo
├── 📄 estrutura_codigo.md           # Organização
├── 📁 database/
│   └── 📄 schema_completo.md       # Schema MySQL
├── 📁 paths/
│   └── 📄 windows_complete.md       # Paths Windows
├── 📁 security/                     # *(em desenvolvimento)*
└── 📁 patterns/                     # *(em desenvolvimento)*
```

---

## **📊 Status da Documentação**

### **✅ Seções Completas (100%)**
- ✅ **Visão Geral e Contexto** - Propósito, escopo, stakeholders
- ✅ **Stack Tecnológico** - PHP, Composer, AdminLTE, MySQL, Apache
- ✅ **Estrutura do Código** - MVC, padrões, organização
- ✅ **Schema do Banco** - 95 tabelas completas
- ✅ **Caminhos Windows** - Todos os paths do sistema

### **🔄 Em Andamento**
- 🔄 **Fluxos e Processos** - Índice criado, autenticação 100%
- 🔄 **Padrões e Convenções** - Estrutura definida

### **⏳️ Planejado**
- ⏳️ **Segurança Detalhada** - Implementações avançadas
- ⏳️ **Guias Práticos** - Exemplos e templates
- ⏳️ **Integrações Externas** - APIs e sistemas terceiros

---

## **🎯 Objetivos da Modularização**

### **📚 Manutenibilidade**
- **Arquivos Menores**: Mais fáceis de editar e manter
- **Especialização**: Cada arquivo focado em um tema específico
- **Colaboração**: Múltiplos desenvolvedores podem editar simultaneamente
- **Versionamento**: Melhor controle de mudanças

### **🚀 Performance**
- **Carregamento**: Apenas seções necessárias são carregadas
- **Navegação**: Mais rápida e direta ao conteúdo específico
- **Busca**: Mais precisa e eficiente
- **Cache**: Melhor aproveitamento do cache do navegador

### **🔍 Escalabilidade**
- **Modularidade**: Fácil adicionar novas seções
- **Expansão**: Sistema cresce com documentação acompanhada
- **Clareza**: Estrutura lógica e intuitiva
- **Flexibilidade**: Fácil reorganização conforme necessidade

---

## **📝 Como Manter Esta Documentação**

### **🔄 Atualização Regular**
1. **Sempre que** o sistema SIGEP for alterado
2. **Mantenha sincronia** entre arquivos relacionados
3. **Atualize datas** de última revisão
4. **Verifique links** e referências cruzadas

### **📋 Consistência**
1. **Mantenha padrão** de formatação Markdown
2. **Use estrutura** consistente de cabeçalhos
3. **Inclua exemplos** práticos quando aplicável
4. **Atualize índices** quando adicionar seções

### **🔗 Referências Cruzadas**
1. **Links internos**: Use caminhos relativos
2. **Links externos**: Verifique que estão atualizados
3. **Imagens e diagramas**: Mantenha arquivos locais
4. **Código fonte**: Referencie arquivos reais do sistema

---

## **🎉 Próximos Passos**

### **� Curto Prazo**
1. **Completar fluxos pendentes**: Navegação SPA, Censura, Eclusa, Laboral
2. **Adicionar padrões de segurança**: Detalhes de implementação
3. **Expandir guias práticos**: Exemplos e templates
4. **Criar workflow final**: ArquitetoSIGEP.md

### **📈 Médio Prazo**
1. **Automatização**: Scripts para validação e atualização
2. **Integração CI/CD**: Pipeline de verificação de documentação
3. **Ferramentas**: Validadores de links e estrutura
4. **Treinamento**: Cursos baseados na documentação

---

## **🔗 Referências Externas**

### **📚 Documentação do Sistema**
- **[Padrão MVC SIGEP](../PadrãoMVCSIGEP.md)** - Guia de desenvolvimento
- **[Composer.lock](../composer.lock)** - Dependências e versões
- **[.htaccess](../.htaccess)** - Regras de navegação
- **[Configurações](../conf/)** - Arquivos de configuração

### **🌐 Recursos Online**
- **[AdminLTE 3](https://adminlte.io/)** - Framework UI
- **[PHP 8.4](https://www.php.net/)** - Documentação oficial
- **[MySQL 8.0](https://dev.mysql.com/doc/refman/8.0/en/)** - Manual de referência
- **[Apache 2.4](https://httpd.apache.org/docs/2.4/)** - Documentação oficial

---

## **📋 Conteúdo Original (Arquivado)**

> **Nota**: O conteúdo original foi movido para arquivos especializados. Use os links acima para acessar informações específicas.

### **🎯 Visão Geral e Contexto**
→ **[Acessar conteúdo completo](visao_geral.md)**

### **🔧 Stack Tecnológico**
→ **[Acessar conteúdo completo](stack_tecnologico.md)**

### **📁 Estrutura do Código**
→ **[Acessar conteúdo completo](estrutura_codigo.md)**

### **🔄 Fluxos e Processos**
→ **[Acessar índice de fluxos](../fluxos/index.md)**

---

**Esta documentação modular representa o conhecimento consolidado do sistema SIGEP. Use o [Índice Principal](index.md) como ponto de entrada para navegar para qualquer aspecto da arquitetura do sistema.**

#### **Módulos Principais:**
- **Censura**: Gestão de correspondências, controle de estoque (roupas, livros, eletrônicos), manutenção
- **Eclusa**: Movimentações de detentos, gestão de escoltas, controle de caminhões pipa
- **Laboral**: Cálculos de pecúlio, gestão de CTC (Certificados de Tempo de Contribuição), cálculo de multas
- **Coordenação**: Medidas disciplinares, gestão administrativa
- **Serviços**: Ferramentas administrativas (agendador de tarefas, phpMyAdmin, notificações)
- **TI**: Downloads, ferramentas técnicas

### **👥 Stakeholders**
- **Usuários Primários**: Policiais penais ou agentes terceirizados que comandam a unidade prisional
- **Administradores**: Gestores do sistema com permissões elevadas
- **Desenvolvedores**: Equipe de TI responsável pela manutenção e evolução
- **Gestão Penitenciária**: Diretores e coordenadores que utilizam relatórios e dashboards

### **⚡ Requisitos Não-Funcionais**

#### **Segurança**
- **Controle de acesso rigoroso** por setor e hierarquia
- **Autenticação centralizada** com sessões seguras
- **Prevenção contra ataques** (SQL injection, XSS, CSRF)
- **Auditoria completa** de ações dos usuários

#### **Escalabilidade**
- **Arquitetura modular** para adicionar novos setores facilmente
- **Performance otimizada** para grande volume de dados
- **Cache inteligente** para operações frequentes
- **Banco de dados robusto** com crescimento planejado

#### **Organização**
- **Interface unificada** com AdminLTE para consistência visual
- **Navegação SPA** para experiência fluida
- **Padrões de código** rígidos para manutenibilidade
- **Documentação completa** para transferência de conhecimento

#### **Disponibilidade**
- **Alta disponibilidade** para operações críticas
- **Backup automatizado** e recovery rápido
- **Monitoramento ativo** de performance e erros
- **Atualizações sem downtime** (deploy incremental)

#### **Usabilidade**
- **Interface intuitiva** para usuários com pouca experiência técnica
- **Acesso responsivo** em dispositivos móveis
- **Feedback visual** imediato para todas as ações
- **Temas claro/escuro** para conforto visual

#### **Conformidade**
- **Normas penitenciárias** atendidas
- **LGPD compliance** para proteção de dados
- **Padrões governamentais** de software
- **Rastreabilidade completa** de operações

### **🌍 Contexto Operacional**
- **Ambiente**: Unidades prisionais com diferentes perfis e necessidades
- **Volume**: Centenas de usuários simultâneos em múltiplas unidades
- **Crítica**: Sistema essencial para operação diária da unidade
- **Evolução**: Sistema em constante expansão com novos módulos

### **🎯 Valor de Negócio**
- **Eficiência operacional**: Redução de 80% no uso de papel
- **Agilidade**: Processos instantâneos antes demorados
- **Precisão**: Eliminação de erros manuais em cálculos
- **Controle**: Visibilidade completa em tempo real de todas as operações
- **Compliance**: Atendimento total às normas penitenciárias

---

## 🔧 **2. Stack Tecnológico Completo**

### **🐘 PHP - Backend Engine**

#### **Versão e Instalação**
- **Versão Atual**: PHP 8.4.16 (ZTS Visual C++ 2022 x64)
- **Build Date**: Dec 17 2025 10:33:12
- **Zend Engine**: v4.4.16
- **OPcache**: Zend OPcache v8.4.16 habilitado
- **Caminho de Instalação**: `C:\Program Files\PHP\`
- **Executable**: `C:\Program Files\PHP\php.exe`
- **Configuração**: `C:\Program Files\PHP\php.ini` (ativo)
- **Config Template**: `C:\Program Files\PHP\php.ini-production`
- **Extension Directory**: `C:\Program Files\PHP\ext\`

#### **Extensões Ativas**
```
[PHP Modules]
bcmath          - Cálculos matemáticos de precisão
bz2             - Compressão Bzip2
calendar        - Funções de calendário
Core            - Núcleo do PHP
ctype           - Verificação de tipos de caracteres
curl            - Client URL library
date            - Funções de data/hora
dom             - Manipulação DOM XML
exif            - Metadados de imagens
fileinfo        - Informações de arquivos
filter          - Filtros de dados
ftp             - FTP client
gd              - Processamento de imagens
gettext         - Internacionalização
hash            - Funções hash
iconv           - Conversão de character sets
intl            - Internacionalização
json            - JSON encode/decode
libxml          - Manipulação XML
mbstring        - Strings multibyte (UTF-8)
mysqli          - MySQL Improved Extension
mysqlnd         - MySQL Native Driver
openssl         - OpenSSL cryptographic functions
pcre            - Expressões regulares compatíveis Perl
PDO             - PHP Data Objects
pdo_mysql       - MySQL driver for PDO
pdo_sqlite      - SQLite driver for PDO
Phar            - PHP Archive
random          - Geração de números aleatórios seguros
readline        - Interface de linha de comando
Reflection      - API Reflection
session         - Gerenciamento de sessões
SimpleXML       - Manipulação XML simplificada
sockets         - Comunicação via sockets
sodium          - Criptografia moderna
SPL             - Standard PHP Library
sqlite3         - SQLite3 database
standard        - Funções padrão
tokenizer       - Tokenizer de PHP
xml             - Manipulação XML
xmlreader       - XML Reader
xmlwriter       - XML Writer
zip             - Manipulação de arquivos ZIP
zlib            - Compressão Zlib

[Zend Modules]
Zend OPcache    - Otimizador de bytecode
```

#### **Configurações Críticas**
- **Timezone**: `America/Sao_Paulo`
- **Charset**: UTF-8 rigoroso em todo o sistema
- **Error Reporting**: E_ALL para desenvolvimento
- **Display Errors**: 0 para produção
- **Memory Limit**: Configurado para processamento de planilhas
- **Upload Max Filesize**: Configurado para documentos oficiais
- **Session**: Configurações seguras com lifetime adequado

#### **Uso no SIGEP**
- **PDO Prepared Statements**: Para segurança contra SQL injection
- **Sessions**: Autenticação e permissões centralizadas
- **JSON**: Comunicação AJAX com frontend
- **PHPExcel/PhpSpreadsheet**: Geração de relatórios
- **PHPWord**: Geração de documentos oficiais

---

### **📦 Composer - Gerenciamento de Dependências**

#### **Configuração Atual**
```json
{
    "require": {
        "phpoffice/phpspreadsheet": "^5.4",
        "illuminate/database": "^12.53",
        "illuminate/events": "^12.53",
        "phpoffice/phpword": "^1.4"
    }
}
```

#### **Dependências Detalhadas**

##### **PhpSpreadsheet (^5.4)**
- **Propósito**: Manipulação de planilhas Excel
- **Uso no SIGEP**: Exportação de relatórios, importação de dados
- **Recursos**: Leitura/escrita XLSX, XLS, CSV
- **Performance**: Otimizado para grandes volumes

##### **Illuminate Database (^12.53)**
- **Propósito**: ORM e Query Builder do Laravel
- **Uso no SIGEP**: Facilitar queries complexas
- **Recursos**: Eloquent ORM, Query Builder, Migrations
- **Integração**: Com eventos e validações

##### **Illuminate Events (^12.53)**
- **Propósito**: Sistema de eventos do Laravel
- **Uso no SIGEP**: Auditoria, triggers customizados
- **Recursos**: Event listeners, observers
- **Performance**: Dispatch assíncrono

##### **PhpWord (^1.4)**
- **Propósito**: Manipulação de documentos Word
- **Uso no SIGEP**: Geração de ofícios, documentos oficiais
- **Recursos**: Templates, formatação, headers/footers
- **Compliance**: Formatos padrão governamentais

#### **Composer Lock Analysis**
- **Total de Pacotes**: 15 pacotes diretos e indiretos
- **PHP Requirement**: ^8.2 (atendido com 8.4.16)
- **Content Hash**: e938d88c6331214ca181b98e54fb6824
- **Last Updated**: Baseado nas dependências atuais
- **Executable**: `C:\Program Files\Composer\composer.bat`
- **Working Directory**: `C:\Program Files\Apache24\htdocs\sigep\`
- **Lock File**: `C:\Program Files\Apache24\htdocs\sigep\composer.lock`
- **Config File**: `C:\Program Files\Apache24\htdocs\sigep\composer.json`

#### **Autoloading**
- **PSR-4**: Configurado para namespaces padrão
- **Class Map**: Otimizado para performance
- **Files**: Inclusão de helpers e funções globais

---

### **🎨 AdminLTE 3 - Framework UI**

#### **Implementação Completa**
- **Versão**: AdminLTE 3.2.0
- **CDN**: `https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css`
- **Integração**: Full AdminLTE 3 implementado

#### **Componentes Utilizados no SIGEP**

##### **Layout Structure**
```html
<div class="wrapper">
  <nav class="main-header navbar">        <!-- Header com notificações -->
  <aside class="main-sidebar">             <!-- Sidebar dinâmica -->
  <div class="content-wrapper">           <!-- Área principal -->
  <aside class="control-sidebar">          <!-- Painel de controle -->
  <footer class="main-footer">            <!-- Footer -->
</div>
```

##### **Cards e Widgets**
- **Small Boxes**: Cards coloridos para estatísticas
- **Info Boxes**: Indicadores de status
- **Progress Bars**: Barras de progresso
- **User Cards**: Informações de usuários

##### **Forms**
- **Form Controls**: Inputs, selects, textareas
- **Validation States**: Estados de validação
- **Custom Controls**: Date pickers, time pickers
- **File Upload**: Upload de arquivos com preview

##### **Tables**
- **Data Tables**: Tabelas com ordenação e paginação
- **Responsive Tables**: Tabelas responsivas
- **Custom Tables**: Tabelas customizadas com actions

##### **Modals**
- **Bootstrap Modals**: Diálogos modais
- **Custom Modals**: Modais customizados
- **Form Modals**: Modais com formulários

##### **Navigation**
- **Main Menu**: Menu principal com submenus
- **Breadcrumb**: Navegação estruturada
- **Tabs**: Abas para organização de conteúdo

##### **Alerts e Notificações**
- **Bootstrap Alerts**: Alertas padrão
- **Toastr**: Notificações toast
- **SweetAlert**: Confirmações customizadas

##### **Icons**
- **FontAwesome 6.4.0**: Ícones vetoriais
- **AdminLTE Icons**: Ícones específicos
- **Custom Icons**: Ícones customizados

#### **Personalização SIGEP**
- **Cores**: Paleta institucional
- **Dark Mode**: Tema claro/escuro
- **Branding**: Logo e identidade visual
- **Responsive**: Mobile-first approach

#### **Roadmap de Migração**
- **Current**: AdminLTE 3.2.0 full implementation
- **Future**: Planejada migração para framework moderno
- **Considerations**: React/Vue.js com design system
- **Timeline**: Pós-estabilização do sistema atual

---

### **🗄️ MySQL 8.0 - Banco de Dados**

#### **Configuração de Acesso**
```php
// conf/db.php
$config = [
    'host' => '127.0.0.1',
    'dbname' => 'sigep_producao',
    'user' => 'sigep',
    'pass' => 'z3wr7bimo3?uHoro',
    'charset' => 'utf8mb4'
];
```

#### **Conexão via PHP (PDO)**
```php
try {
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['user'], $config['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $pdo->exec("SET time_zone = '-03:00'");
} catch (PDOException $e) {
    returnError('Erro na conexão: ' . $e->getMessage(), 500);
}
```

#### **Validação de Acessos**

##### **Via PHP (PDO)**
- **Status**: ✅ Conectando com sucesso
- **Charset**: utf8mb4 configurado
- **Timezone**: America/Sao_Paulo (-03:00)
- **Error Mode**: Exception handling
- **Fetch Mode**: Associative array
- **Emulate Prepares**: false (security)

##### **Via Linha de Comando**
- **MySQL Client**: `C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe`
- **Workbench Client**: `C:\Program Files\MySQL\MySQL Workbench 8.0\mysql.exe`
- **PATH Status**: Não detectado no PATH do sistema
- **Recomendação**: Adicionar `C:\Program Files\MySQL\MySQL Server 8.0\bin\` ao PATH
- **Alternativa**: Usar phpMyAdmin via módulo

##### **Via MCP (Model Context Protocol)**
- **Status**: ✅ Acesso através do módulo phpMyAdmin
- **Interface**: Web-based administration
- **Features**: Full database management
- **Security**: Autenticação integrada

##### **Configuração MySQL**
- **Config File**: `C:\ProgramData\MySQL\MySQL Server 8.0\my.ini`
- **Data Directory**: `C:\ProgramData\MySQL\MySQL Server 8.0\Data\`
- **Log Directory**: `C:\ProgramData\MySQL\MySQL Server 8.0\Logs\`
- **Temp Directory**: `C:\ProgramData\MySQL\MySQL Server 8.0\tmp\`

#### **Configurações do Banco**

##### **Charset e Collation**
- **Database Charset**: utf8mb4
- **Table Charset**: utf8mb4
- **Collation**: utf8mb4_unicode_ci
- **Purpose**: Suporte completo Unicode (emojis, caracteres especiais)

##### **Engine**
- **Default Engine**: InnoDB
- **Features**: Transactions, foreign keys, row-level locking
- **Performance**: Optimized for concurrent access

##### **Configurações de Performance**
- **InnoDB Buffer Pool**: Configurado para workload
- **Query Cache**: Desabilitado (MySQL 8.0)
- **Connection Pool**: Configurado para application
- **Timeout Settings**: Otimizados para SIGEP

#### **Schema do SIGEP - Banco de Dados Completo**

##### **Tabela Principal de Usuários**
```sql
-- acesso_seguro: Usuários e Autenticação
CREATE TABLE `acesso_seguro` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `usuario` varchar(50) NOT NULL UNIQUE,
  `setor` enum('Censura','Almoxarifado','Segurança do Trabalho','Laboral','Recursos Humanos','Coordenação','Direção','Recepção','Tecnologia da Informação','Serralheria','Escola','Carga','Indústria','Jurídico','Cozinha') NOT NULL,
  `senha` varchar(255) NOT NULL,
  `status` enum('Ativo','Inativo') NOT NULL DEFAULT 'Ativo',
  `is_admin` tinyint(1) DEFAULT '0',
  `dark_mode` tinyint(1) DEFAULT '0',
  `is_kiosk` tinyint(1) NOT NULL DEFAULT '0',
  -- Permissões por setor (tinyint 1/0)
  `perm_censura` tinyint(1) DEFAULT '0',
  `perm_almoxarifado` tinyint(1) DEFAULT '0',
  `perm_seg_trab` tinyint(1) DEFAULT '0',
  `perm_laboral` tinyint(1) DEFAULT '0',
  `perm_rh` tinyint(1) DEFAULT '0',
  `perm_coord` tinyint(1) DEFAULT '0',
  `perm_direcao` tinyint(1) DEFAULT '0',
  `perm_portaria` tinyint(1) DEFAULT '0',
  `perm_ti` tinyint(1) DEFAULT '0',
  `perm_eclusa` tinyint(1) DEFAULT '0',
  `perm_manutencao` tinyint(1) DEFAULT '0',
  -- ... outras permissões
  `criado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `usuario` (`usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

##### **Tabela de Internos**
```sql
-- internos: Cadastro de Internos
CREATE TABLE `internos` (
  `ipen` int NOT NULL PRIMARY KEY,
  `nome` varchar(255) NOT NULL,
  `nome_social` varchar(255) DEFAULT NULL,
  `cpf` varchar(14) DEFAULT NULL,
  `lgbt` enum('S','N') DEFAULT 'N',
  `apelido` varchar(100) DEFAULT NULL,
  `forma_pagamento` enum('Pix','Salário') DEFAULT 'Pix',
  `situacao` varchar(100) DEFAULT NULL,
  `ala` varchar(50) DEFAULT NULL,
  `galeria` varchar(50) DEFAULT NULL,
  `bloco` varchar(50) DEFAULT NULL,
  `piso` int DEFAULT NULL,
  `tipo_residencia` varchar(255) DEFAULT NULL,
  `res` int DEFAULT NULL,
  `status` enum('A','I') NOT NULL DEFAULT 'A',
  `regalia` enum('S','N') NOT NULL DEFAULT 'N',
  `cor_roupa` enum('Laranja','Verde') DEFAULT NULL,
  `tamanho_kit` enum('P','M','G','G1','G2','G3') NOT NULL DEFAULT 'G',
  `data_ativo` datetime DEFAULT NULL,
  `data_alterado` datetime DEFAULT NULL,
  `data_inativo` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

##### **Módulo Censura - Cartas**
```sql
-- censura_cartas: Controle de Correspondências
CREATE TABLE `censura_cartas` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `tipo_movimentacao` enum('Entrada','Saida') NOT NULL,
  `id_interno` int NOT NULL,
  `interno_nome` varchar(255) NOT NULL,
  `interno_nome_social` varchar(255) DEFAULT NULL,
  `interno_galeria` varchar(50) DEFAULT NULL,
  `interno_bloco` varchar(50) DEFAULT NULL,
  `interno_res` varchar(20) DEFAULT NULL,
  `id_correspondente` int NOT NULL,
  `correspondente_nome` varchar(255) NOT NULL,
  `correspondente_vinculo` varchar(100) DEFAULT NULL,
  `correspondente_logradouro` varchar(255) DEFAULT NULL,
  `correspondente_numero` varchar(30) DEFAULT NULL,
  `correspondente_bairro` varchar(120) DEFAULT NULL,
  `correspondente_cidade` varchar(120) DEFAULT NULL,
  `correspondente_uf` char(2) DEFAULT NULL,
  `correspondente_cep` varchar(15) DEFAULT NULL,
  `correspondente_complemento` varchar(120) DEFAULT NULL,
  `status_censura` enum('Liberada','Retida','Devolvida') NOT NULL DEFAULT 'Liberada',
  `observacoes_censura` text,
  `motivo_retencao` text,
  `recebido_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `concluido_em` datetime DEFAULT NULL,
  `monitor_id` int NOT NULL,
  `monitor_nome` varchar(100) NOT NULL,
  `status_registro` enum('Ativo','Cancelado') NOT NULL DEFAULT 'Ativo',
  `cancelado_em` datetime DEFAULT NULL,
  `cancelado_por_id` int DEFAULT NULL,
  `cancelado_por_nome` varchar(100) DEFAULT NULL,
  `motivo_cancelamento` text,
  PRIMARY KEY (`id`),
  KEY `idx_censura_cartas_interno_data` (`id_interno`,`recebido_em`),
  KEY `idx_censura_cartas_status` (`status_censura`,`status_registro`),
  CONSTRAINT `fk_censura_cartas_interno` FOREIGN KEY (`id_interno`) REFERENCES `internos` (`ipen`) ON DELETE RESTRICT,
  CONSTRAINT `fk_censura_cartas_monitor` FOREIGN KEY (`monitor_id`) REFERENCES `acesso_seguro` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

##### **Módulo Eclusa - Movimentações**
```sql
-- eclusa_movimentacoes: Controle de Movimentações de Detentos
CREATE TABLE `eclusa_movimentacoes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `data_movimentacao` date NOT NULL COMMENT 'Data da movimentação',
  `veiculo_id` bigint unsigned DEFAULT NULL,
  `empresa_id` bigint unsigned DEFAULT NULL,
  `motorista_id` bigint unsigned DEFAULT NULL,
  `hora_chegada` time DEFAULT NULL,
  `hora_entrada` time DEFAULT NULL,
  `hora_saida` time DEFAULT NULL,
  `observacoes` text,
  `cadastrado_por` varchar(100) NOT NULL DEFAULT 'Automação SIGEP',
  `criado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_data_movimentacao` (`data_movimentacao`),
  KEY `idx_veiculo_id` (`veiculo_id`),
  KEY `idx_empresa_id` (`empresa_id`),
  KEY `idx_motorista_id` (`motorista_id`),
  CONSTRAINT `eclusa_movimentacoes_ibfk_1` FOREIGN KEY (`veiculo_id`) REFERENCES `eclusa_veiculos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `eclusa_movimentacoes_ibfk_2` FOREIGN KEY (`empresa_id`) REFERENCES `eclusa_empresas` (`id`) ON DELETE SET NULL,
  CONSTRAINT `eclusa_movimentacoes_ibfk_3` FOREIGN KEY (`motorista_id`) REFERENCES `eclusa_motoristas` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

##### **Sistema de Notificações**
```sql
-- sistema_notificacoes: Sistema de Notificações
CREATE TABLE `sistema_notificacoes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `tipo` varchar(50) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `mensagem` text NOT NULL,
  `dados_json` json DEFAULT NULL,
  `lida` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_tipo` (`user_id`,`tipo`),
  KEY `idx_lida_created` (`lida`,`created_at` DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
```

##### **Listagem Completa de Tabelas (95 tabelas)**

###### **Segurança e Autenticação**
- `acesso_seguro` - Usuários e permissões
- `acesso_seguro_auditoria` - Auditoria de acessos
- `acesso_seguro_rate_limit` - Controle de taxa de acesso

###### **Módulo Censura (22 tabelas)**
- `censura_cartas` - Controle de correspondências
- `censura_cartas_correspondentes` - Cadastro de correspondentes
- `censura_cartas_historico` - Histórico de alterações
- `censura_cartas_vinculos` - Vínculos de correspondentes
- `censura_estoque_*` - Sistema de estoque (8 tabelas)
- `censura_rouparia_kits_prontos` - Kits de roupas prontos

###### **Módulo Eclusa (7 tabelas)**
- `eclusa_movimentacoes` - Movimentações de detentos
- `eclusa_movimentacoes_auditoria` - Auditoria
- `eclusa_movimentacoes_escolta` - Escoltas
- `eclusa_destinos` - Destinos
- `eclusa_empresas` - Empresas transportadoras
- `eclusa_motoristas` - Motoristas
- `eclusa_veiculos` - Veículos

###### **Eletrônicos (10 tabelas)**
- `eletronicos_*` - Controle de eletrônicos doados (10 tabelas)

###### **Internos (35 tabelas)**
- `internos` - Cadastro principal
- `internos_historico` - Histórico geral
- `internos_historico_detalhado` - Histórico detalhado
- `internos_laboral` - Dados trabalhistas
- `internos_ctc` - Certificados de Tempo de Contribuição
- `internos_eletronicos_*` - Eletrônicos pessoais (3 tabelas)
- `internos_doacao_eletronicos_*` - Doação de eletrônicos (3 tabelas)
- `internos_recebimento_*` - Recebimento de itens (6 tabelas)
- `internos_colchoes_*` - Controle de colchões (5 tabelas)
- `internos_rouparia_*` - Rouparia civil (2 tabelas)
- `internos_md_*` - Medidas disciplinares (3 tabelas)
- `internos_termo_*` - Termos de kit (2 tabelas)
- `entregas_kits_eventos` - Eventos de entrega

###### **Laboral (7 tabelas)**
- `peculio_*` - Sistema de pecúlio (5 tabelas)
- `laboral_descontos_mensais` - Descontos
- `laboral_dividas_status` - Status de dívidas
- `laboral_multa_25` - Cálculo de multas
- `laboral_multa_25_historico_detalhado` - Histórico detalhado

###### **Manutenção (2 tabelas)**
- `manutencao_servicos` - Serviços de manutenção
- `manutencao_servicos_auditoria` - Auditoria

###### **Serviços e Jobs (5 tabelas)**
- `servicos_jobs` - Definição de jobs
- `servicos_agendamentos` - Agendamentos
- `servicos_execucoes` - Histórico de execuções
- `servicos_dependencias` - Dependências entre jobs
- `jobs_control` - Controle de execução

###### **Sistema (6 tabelas)**
- `sistema_config` - Configurações do sistema
- `sistema_notificacoes` - Notificações
- `notificacao_*` - Sistema de notificações (4 tabelas)
- `controle_caminhoes_pipa` - Controle de caminhões pipa

###### **Inteligência Artificial (4 tabelas)**
- `ia_conversas` - Conversas com IA
- `ia_copilot_logs` - Logs do Copilot
- `ia_feedback` - Feedback da IA
- `ia_logs` - Logs gerais
- `ia_mensagens` - Mensagens

###### **Monitoramento (1 tabela)**
- `monitores_solucoes` - Soluções de monitoramento

###### **Views**
- `vw_colchoes_solicitacoes` - View de solicitações de colchões

##### **Relacionamentos e Foreign Keys**
- **Usuários**: Todas as auditorias e registros referenciam `acesso_seguro.id`
- **Internos**: `internos.ipen` é referenciado por todas as tabelas de movimentação
- **Correspondentes**: `censura_cartas_correspondentes.id` → `censura_cartas.id_correspondente`
- **Veículos/Empresas/Motoristas**: Referenciados por `eclusa_movimentacoes`
- **Cascade Operations**: Configuradas conforme regras de negócio

##### **Índices de Performance**
- **Data-based**: `idx_*_data` para consultas por período
- **Status-based**: `idx_*_status` para filtros rápidos
- **User-based**: `idx_user_*` para notificações e auditoria
- **Composite**: Índices compostos para queries complexas

#### **Segurança**
- **Prepared Statements**: Obrigatório em todo o sistema
- **User Privileges**: Princípio do menor privilégio
- **Connection Encryption**: SSL/TLS configurado
- **Audit Logging**: Todas as operações registradas

---

### **🌐 Apache 2.4 - Web Server**

#### **Versão e Configuração**
- **Server Version**: Apache/2.4.66 (Win64)
- **Build**: Apache Lounge VS18 Server built: Jan 30 2026 15:51:08
- **Installation Path**: `C:\Program Files\Apache24\`
- **Executable**: `C:\Program Files\Apache24\bin\httpd.exe`
- **Configuration**: `C:\Program Files\Apache24\conf\httpd.conf`
- **DocumentRoot**: `C:\Program Files\Apache24\htdocs\sigep\`
- **Logs Directory**: `C:\Program Files\Apache24\logs\`
- **Modules Directory**: `C:\Program Files\Apache24\modules\`
- **SSL Certs**: `C:\Program Files\Apache24\conf\ssl\`
- **Extra Configs**: `C:\Program Files\Apache24\conf\extra\`

#### **Módulos Carregados**
```
Loaded Modules:
 core_module (static)              - Core functionality
 win32_module (static)             - Windows-specific
 mpm_winnt_module (static)         - Multi-Processing Module
 http_module (static)              - HTTP protocol
 so_module (static)                - Shared object loading
 access_compat_module (shared)     - Access control compatibility
 auth_basic_module (shared)        - Basic authentication
 authn_core_module (shared)        - Authentication core
 authn_file_module (shared)        - File-based authentication
 authz_core_module (shared)        - Authorization core
 authz_groupfile_module (shared)  - Group-based authorization
 authz_host_module (shared)        - Host-based authorization
 authz_user_module (shared)        - User-based authorization
 filter_module (shared)            - Request filtering
 reqtimeout_module (shared)        - Request timeout
 autoindex_module (shared)         - Directory listing
 mime_module (shared)              - MIME type handling
 log_config_module (shared)        - Logging configuration
 alias_module (shared)             - URL aliasing
 dir_module (shared)               - Directory handling
 rewrite_module (shared)           - URL rewriting
 env_module (shared)               - Environment variables
 headers_module (shared)           - HTTP headers
 setenvif_module (shared)          - Environment setting
 version_module (shared)           - Server version
 ssl_module (shared)               - SSL/TLS support
 php_module (shared)               - PHP integration
```

#### **Configurações Críticas**

##### **DocumentRoot**
```apache
DocumentRoot "C:/Program Files/Apache24/htdocs/sigep"
<Directory "C:/Program Files/Apache24/htdocs/sigep">
    Options Indexes FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>
```

##### **PHP Integration**
```apache
# PHP 8 Module
LoadModule php_module "modules/libphp.dll"
AddHandler application/x-httpd-php .php
PHPIniDir "C:/Program Files/PHP"
```

##### **URL Rewriting**
```apache
# Enable Rewrite Engine
RewriteEngine On

# SIGEP Rewrite Rules
RewriteRule ^autenticacao/?$ auth/login.php [L,QSA]
RewriteRule ^censura/cartas/?$ modulos/censura/cartas/cartas_view.php [L,QSA]
RewriteRule ^eclusa/movimentacoes/?$ modulos/eclusa/movimentacoes/movimentacoes_view.php [L,QSA]
```

##### **Security Headers**
```apache
# Security Headers
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection "1; mode=block"
Header always set Referrer-Policy "strict-origin-when-cross-origin"
Header always set Content-Security-Policy "default-src 'self'..."
```

##### **SSL Configuration**
```apache
# SSL Configuration
<VirtualHost *:443>
    SSLEngine on
    SSLCertificateFile "conf/ssl/cert.pem"
    SSLCertificateKeyFile "conf/ssl/key.pem"
    SSLProtocol all -SSLv3 -TLSv1 -TLSv1.1
    SSLCipherSuite HIGH:!aNULL:!MD5
</VirtualHost>
```

#### **Performance Optimization**

##### **MPM Configuration**
```apache
# WinNT MPM Settings
ThreadsPerChild 150
MaxRequestsPerChild 0
```

##### **Compression**
```apache
# Gzip Compression
LoadModule deflate_module modules/mod_deflate.so
<Location />
    SetOutputFilter DEFLATE
    SetEnvIfNoCase Request_URI \
        \.(?:gif|jpe?g|png)$ no-gzip dont-vary
    SetEnvIfNoCase Request_URI \
        \.(?:exe|t?gz|zip|bz2|sit|rar)$ no-gzip dont-vary
</Location>
```

##### **Caching**
```apache
# Browser Caching
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType image/png "access plus 1 month"
    ExpiresByType image/jpg "access plus 1 month"
</IfModule>
```

#### **Logging Configuration**
```apache
# Error Log
ErrorLog "logs/error.log"
LogLevel warn

# Access Log
LogFormat "%h %l %u %t \"%r\" %>s %b \"%{Referer}i\" \"%{User-Agent}i\"" combined
CustomLog "logs/access.log" combined

# PHP Error Log
php_value error_log "logs/php_errors.log"

# SIGEP Specific Logs
ErrorLog "C:/Program Files/Apache24/logs/sigep-error.log"
CustomLog "C:/Program Files/Apache24/logs/sigep-access.log" combined
```

#### **Virtual Hosts**
```apache
# SIGEP Production
<VirtualHost *:80>
    ServerName sigep.local
    DocumentRoot "C:/Program Files/Apache24/htdocs/sigep"
    ErrorLog "logs/sigep-error.log"
    CustomLog "logs/sigep-access.log" combined
</VirtualHost>
```

---

### **📡 Infraestrutura e CDNs**

#### **CDNs Externos**
```html
<!-- AdminLTE -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">

<!-- jQuery -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>

<!-- FontAwesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<!-- Google Fonts -->
<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
```

#### **Local Assets**
- **CSS Custom**: `C:\Program Files\Apache24\htdocs\sigep\assets\css\` - Estilos específicos do SIGEP
- **JS Custom**: `C:\Program Files\Apache24\htdocs\sigep\assets\js\` - JavaScript específico
- **Images**: `C:\Program Files\Apache24\htdocs\sigep\assets\img\` - Imagens e ícones
- **Fonts**: Fontes locais se necessário
- **Modules Assets**: `C:\Program Files\Apache24\htdocs\sigep\modulos\*\assets\` - Assets por módulo

---

### **�️ Caminhos Completos do Windows SIGEP**

#### **🐘 PHP Paths**
```
Executable:          C:\Program Files\PHP\php.exe
Configuration:        C:\Program Files\PHP\php.ini (ativo)
Template Config:      C:\Program Files\PHP\php.ini-production
Extensions:           C:\Program Files\PHP\ext\
Extension DLLs:       C:\Program Files\PHP\ext\php_*.dll
OPcache Cache:        C:\Program Files\PHP\opcache\
Session Files:        C:\Program Files\PHP\session\
Upload Temp:          C:\Program Files\PHP\uploadtemp\
```

#### **📦 Composer Paths**
```
Executable:          C:\Program Files\Composer\composer.bat
Composer Home:        C:\Users\{USER}\AppData\Roaming\Composer
Cache Directory:      C:\Users\{USER}\AppData\Local\Composer\cache
Vendor Directory:     C:\Program Files\Apache24\htdocs\sigep\vendor\
Autoload:             C:\Program Files\Apache24\htdocs\sigep\vendor\autoload.php
Lock File:            C:\Program Files\Apache24\htdocs\sigep\composer.lock
Config File:          C:\Program Files\Apache24\htdocs\sigep\composer.json
```

#### **🗄️ MySQL Paths**
```
Server Executable:    C:\Program Files\MySQL\MySQL Server 8.0\bin\mysqld.exe
Client Executable:    C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe
Workbench Client:     C:\Program Files\MySQL\MySQL Workbench 8.0\mysql.exe
Config File:          C:\ProgramData\MySQL\MySQL Server 8.0\my.ini
Data Directory:       C:\ProgramData\MySQL\MySQL Server 8.0\Data\
Log Directory:        C:\ProgramData\MySQL\MySQL Server 8.0\Logs\
Temp Directory:       C:\ProgramData\MySQL\MySQL Server 8.0\tmp\
Socket File:          C:\ProgramData\MySQL\MySQL Server 8.0\mysql.sock
PID File:             C:\ProgramData\MySQL\MySQL Server 8.0\mysqld.pid
Error Log:            C:\ProgramData\MySQL\MySQL Server 8.0\Logs\error.log
Slow Query Log:       C:\ProgramData\MySQL\MySQL Server 8.0\Logs\mysql-slow.log
Binary Log:           C:\ProgramData\MySQL\MySQL Server 8.0\Data\binlog.*
```

#### **🌐 Apache Paths**
```
Server Executable:    C:\Program Files\Apache24\bin\httpd.exe
Service Executable:   C:\Program Files\Apache24\bin\httpd.exe -k install
Configuration:        C:\Program Files\Apache24\conf\httpd.conf
DocumentRoot:         C:\Program Files\Apache24\htdocs\sigep\
Logs Directory:        C:\Program Files\Apache24\logs\
Modules Directory:     C:\Program Files\Apache24\modules\
SSL Certificates:      C:\Program Files\Apache24\conf\ssl\
Extra Configs:        C:\Program Files\Apache24\conf\extra\
MIME Types:           C:\Program Files\Apache24\conf\mime.types
Magic File:           C:\Program Files\Apache24\conf\magic
Error Log:            C:\Program Files\Apache24\logs\error.log
Access Log:           C:\Program Files\Apache24\logs\access.log
SIGEP Error Log:      C:\Program Files\Apache24\logs\sigep-error.log
SIGEP Access Log:     C:\Program Files\Apache24\logs\sigep-access.log
PHP Error Log:        C:\Program Files\Apache24\logs\php_errors.log
```

#### **📁 SIGEP Application Paths**
```
Project Root:         C:\Program Files\Apache24\htdocs\sigep\
Modules:              C:\Program Files\Apache24\htdocs\sigep\modulos\
Includes:             C:\Program Files\Apache24\htdocs\sigep\includes\
Assets:               C:\Program Files\Apache24\htdocs\sigep\assets\
Configuration:        C:\Program Files\Apache24\htdocs\sigep\conf\
Authentication:       C:\Program Files\Apache24\htdocs\sigep\auth\
Pages:                C:\Program Files\Apache24\htdocs\sigep\paginas\
Extensions:           C:\Program Files\Apache24\htdocs\sigep\extensions\
Scripts:              C:\Program Files\Apache24\htdocs\sigep\scripts\
Temp Directory:       C:\Program Files\Apache24\htdocs\sigep\temp\
Vendor:               C:\Program Files\Apache24\htdocs\sigep\vendor\
.htaccess:            C:\Program Files\Apache24\htdocs\sigep\.htaccess
composer.json:        C:\Program Files\Apache24\htdocs\sigep\composer.json
composer.lock:        C:\Program Files\Apache24\htdocs\sigep\composer.lock
package.json:         C:\Program Files\Apache24\htdocs\sigep\package.json
```

#### **🗂️ Module Structure Paths**
```
Censura Module:       C:\Program Files\Apache24\htdocs\sigep\modulos\censura\
Eclusa Module:        C:\Program Files\Apache24\htdocs\sigep\modulos\eclusa\
Laboral Module:       C:\Program Files\Apache24\htdocs\sigep\modulos\laboral\
Coordenação Module:   C:\Program Files\Apache24\htdocs\sigep\modulos\coordenacao\
Serviços Module:      C:\Program Files\Apache24\htdocs\sigep\modulos\servicos\
TI Module:            C:\Program Files\Apache24\htdocs\sigep\modulos\ti\
Geral Module:         C:\Program Files\Apache24\htdocs\sigep\modulos\geral\
```

#### **📚 Knowledge Base Paths**
```
Architecture Docs:    C:\Program Files\Apache24\htdocs\sigep\.windsurf\architecture\
MCP Memories:          C:\Program Files\Apache24\htdocs\sigep\.windsurf\memories\
Context Data:         C:\Program Files\Apache24\htdocs\sigep\.windsurf\contexto\
Rules:                C:\Program Files\Apache24\htdocs\sigep\.windsurf\regras\
Cerebro:              C:\Program Files\Apache24\htdocs\sigep\.windsurf\cerebro\
```

#### **🔧 Development Tools Paths**
```
Git Config:           C:\Users\{USER}\.gitconfig
Git Global Config:    C:\Program Files\Git\etc\gitconfig
NPM Global:           C:\Users\{USER}\AppData\Roaming\npm\
NPM Cache:            C:\Users\{USER}\AppData\Local\npm-cache\
Node Modules:         C:\Program Files\Apache24\htdocs\sigep\node_modules\
```

#### **🖥️ Windows Environment Variables**
```
PHP Path:             C:\Program Files\PHP\ (adicionar ao PATH)
MySQL Path:           C:\Program Files\MySQL\MySQL Server 8.0\bin\ (adicionar ao PATH)
Apache Path:          C:\Program Files\Apache24\bin\ (adicionar ao PATH)
Composer Path:        C:\Program Files\Composer\ (adicionar ao PATH)
```

#### **🔑 Windows Services**
```
Apache Service:       Apache2.4 (running as service)
MySQL Service:        MySQL80 (running as service)
Startup Type:         Automatic
Service User:         LocalSystem
```

#### **📊 Performance Monitoring Paths**
```
Apache Status:        http://localhost/server-status
PHP Info:             http://localhost/phpinfo.php
phpMyAdmin:           http://localhost/modulos/servicos/phpmyadmin/
SIGEP Application:    http://localhost/
```

#### **🔄 Backup Paths**
```
Apache Config Backup: C:\Program Files\Apache24\conf\backup\
MySQL Backup:         C:\ProgramData\MySQL\MySQL Server 8.0\backup\
SIGEP Backup:         C:\Program Files\Apache24\htdocs\sigep\backup\
Logs Backup:          C:\Program Files\Apache24\logs\backup\
```

---

## �� **Development Tools**

#### **Package Management**
- **Composer**: Gerenciamento de dependências PHP
- **NPM**: Para assets frontend (se necessário)
- **Git**: Controle de versão

#### **Debugging**
- **Xdebug**: Para debugging (se configurado)
- **PHP Error Logging**: Logs detalhados
- **Apache Logs**: Access e error logs
- **Browser DevTools**: Debug frontend

---

### **📊 Performance Monitoring**

#### **Server Metrics**
- **Apache Status**: `/server-status`
- **PHP Info**: `phpinfo()` para diagnóstico
- **MySQL Status**: Performance queries
- **System Resources**: CPU, Memory, Disk

#### **Application Monitoring**
- **Response Time**: Tempo de carregamento
- **Error Rate**: Taxa de erros
- **User Sessions**: Sessões ativas
- **Database Queries**: Performance de queries

---

---

## 📁 **3. Estrutura do Código - Organização e Padrões**

### **🏗️ Organização de Diretórios Completa**

#### **Estrutura Hierárquica**
```
sigep/
├── 📁 modulos/                           # Módulos MVC do sistema
│   ├── 📁 censura/                       # Setor Censura
│   │   ├── 📁 cartas/                    # Módulo de Cartas
│   │   │   ├── 📄 cartas_view.php        # Interface (View)
│   │   │   ├── 📄 cartas_logica.php      # Controller (Lógica)
│   │   │   └── 📁 assets/
│   │   │       ├── 📄 css/cartas.css     # Estilos customizados
│   │   │       └── 📄 js/cartas.js       # JavaScript + AJAX
│   │   ├── 📁 manutencao/                # Módulo de Manutenção
│   │   └── 📁 estoque/                    # Sistema de Estoque
│   ├── 📁 coordenacao/                   # Setor Coordenação
│   │   └── 📁 medidas_disciplinares/    # Medidas Disciplinares
│   ├── 📁 eclusa/                        # Setor Eclusa
│   │   ├── 📁 movimentacoes/             # Movimentações
│   │   └── 📁 escolta/                   # Escoltas
│   ├── 📁 laboral/                       # Setor Laboral
│   │   ├── 📁 gestao_ctc/                # Gestão CTC
│   │   └── 📁 calculo_multas/            # Cálculo de Multas
│   ├── 📁 servicos/                      # Ferramentas administrativas
│   │   ├── 📁 job_manager/               # Agendador de Tarefas
│   │   ├── 📁 phpmyadmin/                # Administração do banco
│   │   └── 📁 notificacoes/               # Sistema de notificações
│   ├── 📁 ti/                            # Setor TI
│   │   └── 📁 downloads/                 # Downloads
│   └── 📁 geral/                         # Módulos gerais
├── 📁 includes/                          # Componentes globais
│   ├── 📄 header.php                     # Header com CSS/JS AdminLTE
│   ├── 📄 sidebar.php                    # Sidebar HTML
│   ├── 📄 sidebar_logica.php            # Lógica do menu e permissões
│   ├── 📄 footer.php                     # Footer global
│   └── 📄 [diversos]_logica.php           # Lógicas de componentes
├── 📁 conf/                              # Configurações
│   └── 📄 db.php                         # Configuração do banco de dados
├── 📁 auth/                              # Autenticação
│   ├── 📄 login.php                      # Formulário de login
│   ├── 📄 logout.php                     # Logout
│   ├── 📄 session_auth.php               # Autenticação de sessão
│   └── 📄 security_functions.php         # Funções de segurança
├── 📁 assets/                            # Recursos globais
│   ├── 📁 css/                           # Estilos globais
│   │   ├── 📄 header.css                 # Estilos do header
│   │   ├── 📄 sidebar.css                # Estilos da sidebar
│   │   ├── 📄 footer.css                 # Estilos do footer
│   │   └── 📄 index.css                  # Estilos da página inicial
│   ├── 📁 js/                            # JavaScript global
│   │   ├── 📄 main.js                    # Funcionalidades principais
│   │   ├── 📄 navigation.js              # Navegação SPA
│   │   └── 📄 utils.js                   # Utilitários
│   └── 📁 img/                           # Imagens e ícones
├── 📁 paginas/                           # Páginas legadas (em migração)
│   ├── 📄 [diversas].php                  # Páginas antigas
│   └── 📄 ajax_*.php                     # Endpoints AJAX legados
├── 📁 extensions/                        # Extensões e personalizações
│   └── 📁 ipenv2/                        # Extensão IP Environment
├── 📁 scripts/                           # Scripts utilitários
│   ├── 📄 iPEN-*.user.js                 # Userscripts para navegador
│   └── 📄 [diversos].js                  # Scripts diversos
├── 📁 temp/                              # Arquivos temporários
├── 📄 .htaccess                          # Rewrite rules para URLs amigáveis
├── 📄 index.php                          # Ponto de entrada principal
├── 📄 composer.json                      # Dependências PHP
├── 📄 package.json                       # Dependências Node.js
├── 📄 favicon.svg                        # Ícone da aplicação
└── 📄 .windsurf/                         # Configurações Windsurf
```

---

### **📝 Padrões de Nomenclatura**

#### **Convenções Rigorosas**
- **Arquivos**: `snake_case` (ex: `cartas_view.php`)
- **Classes**: `PascalCase` (ex: `CartasController`)
- **Funções**: `camelCase` (ex: `carregarDados()`)
- **Variáveis**: `camelCase` (ex: `$usuarioNome`)
- **Constantes**: `UPPER_SNAKE_CASE` (ex: `SIGEP_VERSION`)
- **Banco de Dados**: `snake_case` (ex: `censura_cartas`)
- **CSS Classes**: `kebab-case` (ex: `.cartas-container`)
- **JavaScript**: `camelCase` (ex: `carregarCartas()`)

#### **Estrutura de Módulos**
```
modulos/[setor]/[nome_modulo]/
├── [nome_modulo]_view.php      # Interface HTML
├── [nome_modulo]_logica.php    # Controller PHP
├── assets/
│   ├── css/
│   │   └── [nome_modulo].css   # Estilos específicos
│   └── js/
│       └── [nome_modulo].js    # JavaScript específico
└── README.md                   # Documentação (opcional)
```

---

### **🎨 Arquitetura de Software**

#### **Padrão MVC Rigoroso**
```php
// View: [nome]_view.php
<?php
require_once __DIR__ . '/[nome]_logica.php';
?>
<!-- HTML com AdminLTE -->
<section class="content">
    <div class="container-fluid">
        <!-- Conteúdo -->
    </div>
</section>
<script src="modulos/[setor]/[nome]/assets/js/[nome].js"></script>

// Controller: [nome]_logica.php
<?php
session_start();
require_once __DIR__ . '/../../../conf/db.php';
// Lógica de negócio, validação, CRUD
// Processamento AJAX
// Carregamento de dados para view
?>

// CSS: assets/css/[nome].css
/* Estilos específicos do módulo */

// JavaScript: assets/js/[nome].js
// Funcionalidades AJAX, UI, validação
```

#### **Design Patterns Utilizados**
- **Singleton**: Conexão com banco de dados
- **Factory**: Criação de objetos de notificação
- **Observer**: Sistema de eventos e auditoria
- **Strategy**: Diferentes tipos de exportação
- **Template Method**: Padrão de views e controllers

---

### **🔧 Componentes e Módulos**

#### **Componentes Globais (includes/)**
```php
// header.php - Header dinâmico
- Sistema de notificações
- Tema claro/escuro
- Informações do usuário
- Navegação principal

// sidebar_logica.php - Lógica do menu
- Verificação de permissões
- Construção dinâmica do menu
- Controle de acesso por setor

// footer.php - Footer global
- Informações do sistema
- Links úteis
- Versão do SIGEP
```

#### **Módulos por Setor**
```php
// Censura
- cartas/ - Controle de correspondências
- manutencao/ - Serviços de manutenção
- estoque/ - Gestão de itens

// Eclusa
- movimentacoes/ - Transferências
- escolta/ - Gestão de escoltas

// Laboral
- gestao_ctc/ - Certificados
- calculo_multas/ - Cálculos trabalhistas

// Serviços (Admin)
- job_manager/ - Agendador de tarefas
- phpmyadmin/ - Administração DB
- notificacoes/ - Sistema de alertas
```

---

### **🔄 Fluxos e Processos**

#### **Ciclo de Vida de Requisição**
```
1. Request → index.php
2. Verificação de sessão
3. Carregamento header.php
4. Navegação SPA (loadPage())
5. Carregamento módulo específico
6. Processamento AJAX (se necessário)
7. Renderização view
8. Carregamento footer.php
9. Response para cliente
```

#### **Fluxo de Dados**
```
Frontend (JavaScript)
    ↓ AJAX
Controller PHP (logica.php)
    ↓ PDO
Banco de Dados MySQL
    ↓ JSON Response
Frontend (Atualização UI)
```

#### **Processos de Negócio**
- **Autenticação**: Login → Sessão → Permissões → Acesso
- **Navegação**: Menu → loadPage() → Módulo → View
- **CRUD**: Form → AJAX → Controller → DB → Response
- **Auditoria**: Ação → Log → Histórico → Relatórios

---

### **🌐 Integrações Externas**

#### **APIs e Serviços**
- **CDNs**: AdminLTE, jQuery, FontAwesome
- **Banco de Dados**: MySQL 8.0 local
- **Sistema de Arquivos**: Upload/Download local
- **Email**: Configuração SMTP (se necessário)
- **Exportação**: PhpSpreadsheet, PhpWord

#### **Integração com Sistemas Penitenciários**
- **Importação de Dados**: Planilhas → SIGEP
- **Exportação de Relatórios**: SIGEP → Excel/Word
- **Comunicação**: Notificações internas
- **Auditoria**: Logs completos para compliance

---

### **📋 Padrões de Código**

#### **PHP Standards**
```php
// 1. Verificação obrigatória de sessão
if (!isset($_SESSION['user_id'])) {
    header('Location: /autenticacao');
    exit;
}

// 2. Conexão PDO com tratamento de erros
try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    error_log('Erro de conexão: ' . $e->getMessage());
    returnError('Erro interno');
}

// 3. Prepared statements obrigatórios
$stmt = $pdo->prepare("SELECT * FROM tabela WHERE id = ?");
$stmt->execute([$id]);

// 4. Resposta JSON padronizada
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
```

#### **JavaScript Standards**
```javascript
// 1. Proteção contra múltiplos carregamentos
if (typeof window.moduleNameLoaded === 'undefined') {
    window.moduleNameLoaded = true;

    // 2. Estrutura básica de AJAX
    function carregarDados() {
        $.ajax({
            url: 'modulos/setor/module/module_logica.php',
            method: 'POST',
            data: { action: 'listar' },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    atualizarInterface(response.data);
                }
            },
            error: function(xhr, status, error) {
                console.error('Erro:', error);
                mostrarNotificacao('Erro ao carregar dados', 'error');
            }
        });
    }

} // fim do if de proteção
```

#### **CSS Standards**
```css
/* 1. Escopo específico do módulo */
.module-name-container {
    /* Estilos */
}

/* 2. Classes utilitárias */
.text-center { text-align: center; }
.mb-3 { margin-bottom: 1rem; }

/* 3. Responsive */
@media (max-width: 768px) {
    .module-name-container {
        padding: 0.5rem;
    }
}
```

---

### **🔐 Segurança no Código**

#### **Validações Obrigatórias**
```php
// 1. Validação de entrada
$dados = filter_input_array(INPUT_POST, [
    'nome' => FILTER_SANITIZE_STRING,
    'email' => FILTER_VALIDATE_EMAIL,
    'idade' => ['filter' => FILTER_VALIDATE_INT, 'options' => ['min_range' => 0]]
]);

// 2. Verificação de permissões
if (!podeVisualizarSetor('perm_setor')) {
    http_response_code(403);
    echo json_encode(['error' => 'Sem permissão'], JSON_UNESCAPED_UNICODE);
    exit;
}

// 3. Sanitização de saída
echo htmlspecialchars($variavel, ENT_QUOTES, 'UTF-8');
```

#### **Proteções Implementadas**
- **SQL Injection**: Prepared statements
- **XSS**: htmlspecialchars() em toda saída
- **CSRF**: Tokens em formulários
- **Session Hijacking**: Regeneração de ID
- **File Upload**: Validação de tipo e tamanho

---

### **📊 Performance e Otimização**

#### **Otimizações de Código**
```php
// 1. Cache de consultas frequentes
if (!isset($cache['usuarios'])) {
    $cache['usuarios'] = listarUsuarios($pdo);
}

// 2. Paginação de resultados
$limit = 20;
$offset = ($pagina - 1) * $limit;
$sql = "SELECT * FROM tabela LIMIT ? OFFSET ?";

// 3. Índices otimizados
// CREATE INDEX idx_tabela_status_data ON tabela(status, created_at);
```

#### **Best Practices**
- **Lazy Loading**: Carregar dados apenas quando necessário
- **Batch Processing**: Processar em lotes grandes volumes
- **Connection Pooling**: Reutilizar conexões PDO
- **Minificação**: CSS/JS em produção
- **CDN**: Para assets estáticos

---

### **🧪 Testes e Qualidade**

#### **Estrutura de Testes**
```php
// Testes Unitários (se implementados)
tests/
├── unit/
│   ├── CartasTest.php
│   └── UsuariosTest.php
├── integration/
│   └── DatabaseTest.php
└── functional/
    └── NavigationTest.php
```

#### **Validações Manuais**
- **Teste de Permissões**: Verificar acesso negado
- **Teste de Formulários**: Validação cliente/servidor
- **Teste de AJAX**: Respostas corretas
- **Teste de Responsividade**: Mobile/tablet/desktop

---

### **📚 Documentação no Código**

#### **Padrão de Comentários**
```php
/**
 * SIGEP Cartas - Controller
 *
 * Responsável pelo gerenciamento de correspondências no sistema SIGEP.
 * Implementa CRUD completo com auditoria e validações de segurança.
 *
 * @author SIGEP Development Team
 * @version 1.0.0
 * @since 2025-01-01
 */

/**
 * Processa criação de nova carta
 *
 * @param PDO $pdo Conexão com banco de dados
 * @param array $dados Dados da carta [interno, correspondente, observacoes]
 * @return int ID da carta criada
 * @throws Exception Em caso de erro de validação
 */
function criarCarta(PDO $pdo, array $dados): int {
    // Implementação
}
```

---

## � **4. Fluxos e Processos**

### **📋 Visão Geral dos Fluxos**

O sistema SIGEP implementa fluxos de negócio complexos e interconectados que garantem a operação eficiente e segura da unidade prisional. Cada fluxo está documentado em detalhes no diretório `.windsurf/fluxos/`.

#### **🗂️ Estrutura de Documentação**
```
.windsurf/fluxos/
├── 📄 index.md                   # Índice geral dos fluxos
├── 📄 autenticacao.md            # ✅ Fluxo completo de autenticação
├── 📄 navegacao_spa.md           # 🔄 Navegação Single Page Application
├── 📄 censura_cartas.md          # ⏳ Gestão de correspondências
├── 📄 eclusa_movimentacoes.md    # ⏳ Movimentações de detentos
├── 📄 laboral_peculio.md         # ⏳ Cálculos trabalhistas
├── 📄 notificacoes_sistema.md     # 🔔 Sistema de notificações
├── 📄 auditoria_logs.md          # 📊 Auditoria e logs
├── 📄 importacao_exportacao.md    # 📥📤 Importação/exportação
├── 📄 servicos_jobs.md            # ⚙️ Jobs agendados
└── 📄 integracoes_externas.md     # 🔗 Integrações externas
```

#### **🔐 Fluxos Principais**

##### **1. Autenticação (✅ Documentado)**
- **Arquivo**: `fluxos/autenticacao.md`
- **Descrição**: Processo completo de login com múltiplas camadas de segurança
- **Componentes**: Login normal, remember-me, lockscreen, rate limiting, CSRF protection
- **Segurança**: Auditoria completa, tokens criptografados, proteção contra ataques

##### **2. Navegação SPA (🔄 Em andamento)**
- **Arquivo**: `fluxos/navegacao_spa.md`
- **Descrição**: Sistema de navegação Single Page Application
- **Componentes**: Menu dinâmico, loadPage(), histórico, breadcrumb
- **Tecnologia**: AJAX, AdminLTE, jQuery

##### **3. Censura de Cartas (⏳ Pendente)**
- **Arquivo**: `fluxos/censura_cartas.md`
- **Descrição**: Controle completo de correspondências dos internos
- **Componentes**: Registro, verificação, análise, histórico, notificações

##### **4. Eclusa Movimentações (⏳ Pendente)**
- **Arquivo**: `fluxos/eclusa_movimentacoes.md`
- **Descrição**: Gestão de transferências de detentos
- **Componentes**: Agendamento, documentos, escolta, logística

##### **5. Laboral Pecúlio (⏳ Pendente)**
- **Arquivo**: `fluxos/laboral_peculio.md`
- **Descrição**: Cálculos trabalhistas e benefícios
- **Componentes**: CTC, pecúlio, multas, descontos, relatórios

#### **⚙️ Fluxos Transversais**

##### **Sistema de Notificações**
- **Geração**: Eventos trigger automáticos
- **Roteamento**: Destinatários por permissão
- **Canais**: Sistema, email, push notifications
- **Prioridades**: Urgência e categorização

##### **Auditoria e Logs**
- **Coleta**: Eventos automáticos de todos os módulos
- **Armazenamento**: Logs estruturados em banco
- **Consulta**: Busca avançada e filtragem
- **Relatórios**: Análises periódicas de compliance

##### **Importação/Exportação**
- **Importação**: Planilhas → SIGEP (validação e processamento)
- **Exportação**: SIGEP → Excel/Word (relatórios oficiais)
- **Templates**: Formatos padronizados governamentais
- **Histórico**: Rastreabilidade completa

#### **👥 Workflows de Usuário**

##### **Operador Censura**
```
Login → Dashboard → Módulo Cartas → Registrar → Analisar → Histórico
```

##### **Motorista Eclusa**
```
Login → Agendamentos → Verificar Docs → Executar → Registrar → Concluir
```

##### **Administrador**
```
Login → Monitoramento → Usuários → Auditoria → Configurações → Relatórios
```

#### **📊 Métricas e KPIs**

##### **Indicadores por Fluxo**
- **Autenticação**: Taxa de sucesso, tempo médio, tentativas bloqueadas
- **Censura**: Volume de correspondências, taxa de retenção, tempo de processamento
- **Eclusa**: Movimentações/mês, pontualidade, ocorrências registradas
- **Laboral**: Cálculos processados, valores liberados, erros de cálculo

#### **⚠️ Tratamento de Exceções**

##### **Padrão de Exceções**
1. **Validação**: Entrada inválida ou incompleta
2. **Negócio**: Regra de negócio violada
3. **Sistema**: Erro técnico ou falha de infraestrutura
4. **Segurança**: Acesso não autorizado ou suspeito
5. **Integração**: Falha em sistema externo

##### **Estratégias de Recuperação**
- **Retry**: Tentativas automáticas com backoff exponencial
- **Fallback**: Caminhos alternativos de processamento
- **Rollback**: Reversão de estados consistentes
- **Notificação**: Alertas automáticas para administradores
- **Log**: Registro detalhado para análise posterior

---

### **📚 Documentação Detalhada**

Para acessar a documentação completa de cada fluxo, consulte os arquivos específicos em:

**📁 Diretório Principal**: `C:\Program Files\Apache24\htdocs\sigep\.windsurf\fluxos\`

- **`index.md`** - Índice geral e visão overview
- **`autenticacao.md`** - Fluxo completo de autenticação (100% documentado)
- **Outros fluxos** - Em desenvolvimento conforme prioridade

---

## �🗂️ **Estrutura de Diretórios**

```
sigep/
├── modulos/                     # Módulos MVC do sistema
│   ├── censura/                # Setor Censura
│   │   ├── cartas/             # Módulo de Cartas
│   │   │   ├── cartas_view.php
│   │   │   ├── cartas_logica.php
│   │   │   └── assets/
│   │   │       ├── css/cartas.css
│   │   │       └── js/cartas.js
│   │   └── manutencao/         # Módulo de Manutenção
│   ├── coordenacao/            # Setor Coordenação
│   │   └── medidas_disciplinares/
│   ├── eclusa/                 # Setor Eclusa
│   │   ├── movimentacoes/
│   │   └── escolta/
│   ├── laboral/                # Setor Laboral
│   │   ├── gestao_ctc/
│   │   └── calculo_multas/
│   ├── servicos/               # Ferramentas administrativas
│   │   ├── job_manager/
│   │   ├── phpmyadmin/
│   │   └── notificacoes/
│   ├── ti/                     # Setor TI
│   └── geral/                  # Módulos gerais
├── includes/                   # Componentes globais
│   ├── header.php              # Header com CSS/JS AdminLTE
│   ├── sidebar.php             # Sidebar HTML
│   ├── sidebar_logica.php      # Lógica do menu e permissões
│   └── footer.php              # Footer global
├── conf/                       # Configurações
│   └── db.php                 # Configuração do banco de dados
├── auth/                       # Autenticação
│   ├── login.php
│   ├── logout.php
│   └── session_auth.php
├── assets/                     # Recursos globais
│   ├── css/                    # Estilos globais
│   ├── js/                     # JavaScript global
│   └── img/                    # Imagens
├── paginas/                    # Páginas legadas (em migração)
└── .htaccess                   # Rewrite rules para URLs amigáveis
```

---

## 🔄 **Arquitetura SPA**

### **Como Funciona a Navegação**
1. **Carregamento Inicial**: `index.php` carrega estrutura base
2. **Navegação**: `loadPage(url, title, parent)` via AJAX
3. **Injeção de Conteúdo**: HTML injetado dinamicamente
4. **Histórico**: Browser history mantido

### **Função loadPage()**
```javascript
function loadPage(url, title, parent) {
    // AJAX request para carregar conteúdo
    // Atualizar título e breadcrumb
    // Manter histórico do navegador
}
```

---

## 🏛️ **Padrão MVC Rigoroso**

### **Estrutura de Módulos**
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

### **Naming Convention**
- **View**: `[nome]_view.php`
- **Controller**: `[nome]_logica.php`
- **CSS**: `[nome].css`
- **JavaScript**: `[nome].js`

---

## 🔐 **Sistema de Autenticação e Permissões**

### **Estrutura de Sessão**
```php
$_SESSION = [
    'user_id' => int,           // ID do usuário
    'user_nome' => string,      // Nome completo
    'user_setor' => string,     // Setor do usuário
    'user_admin' => bool,       // É administrador
    'user_theme' => int,        // Tema (1=dark, 0=light)
    'perm_censura' => int,      // Permissão Censura
    'perm_eclusa' => int,       // Permissão Eclusa
    'perm_laboral' => int,      // Permissão Laboral
    'perm_coord' => int,        // Permissão Coordenação
    'perm_ti' => int,           // Permissão TI
    // ... outras permissões
];
```

### **Função de Verificação**
```php
function podeVisualizarSetor($perm_column) {
    if (isset($_SESSION['user_admin']) && $_SESSION['user_admin'] === true) return true;
    return (isset($_SESSION[$perm_column]) && $_SESSION[$perm_column] > 0);
}
```

### **Setores e Permissões**
- **Censura**: `perm_censura` - Controle de correspondências e estoque
- **Eclusa**: `perm_eclusa` - Movimentações de detentos
- **Laboral**: `perm_laboral` - Cálculos pecúlio e CTC
- **Coordenação**: `perm_coord` - Medidas disciplinares
- **TI**: `perm_ti` - Ferramentas administrativas
- **Segurança do Trabalho**: `perm_seg_trab`
- **Portaria**: `perm_portaria`
- **Manutenção**: `perm_manutencao`

---

## 🎨 **Interface AdminLTE 3**

### **Estrutura HTML Padrão**
```php
<?php require_once __DIR__ . '/[nome_modulo]_logica.php'; ?>

<!-- Content Header (CRÍTICO: sempre vazio) -->
<div class="row mb-2">
    <div class="col-sm-6">
    </div>
</div>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <!-- Conteúdo do módulo -->
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

---

## 🗄️ **Banco de Dados MySQL**

### **Configuração Padrão**
```php
// conf/db.php
$config = [
    'host' => '127.0.0.1',
    'dbname' => 'sigep_producao',
    'user' => 'sigep',
    'pass' => 'senha',
    'charset' => 'utf8mb4'
];
```

### **Padrão de Tabelas**
```sql
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
- **Charset**: utf8mb4 (suporte completo Unicode)
- **Engine**: InnoDB (suporte a transações)
- **Timestamps**: created_at, updated_at
- **Foreign Keys**: para tabela usuarios
- **Enums**: para status fixos

---

## 🔧 **Controller PHP Padrão**

### **Estrutura Base**
```php
<?php
// [Nome Módulo] - Controller SIGEP
session_start();
require_once __DIR__ . '/../../../conf/db.php';

// Headers para resposta JSON
header('Content-Type: application/json; charset=utf-8');

// Configurar Timezone
date_default_timezone_set('America/Sao_Paulo');

// Função de erro padronizada
function returnError($message, $code = 500) {
    http_response_code($code);
    echo json_encode(['error' => $message, 'code' => $code], JSON_UNESCAPED_UNICODE);
    exit;
}

// Verificar autenticação
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_nome'])) {
    returnError('Usuário não autenticado', 401);
}

// Verificar permissão
if (!($_SESSION['user_admin'] || ($_SESSION['perm_setor'] ?? 0))) {
    returnError('Sem permissão para acessar este módulo', 403);
}

// Conexão PDO
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
    returnError('Erro na conexão: ' . $e->getMessage(), 500);
}

// Processar requisições AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    ob_clean(); // Limpar buffer

    try {
        switch ($_POST['action']) {
            case 'listar':
                // Lógica de listagem
                break;
            case 'criar':
                // Lógica de criação
                break;
            default:
                throw new Exception('Ação não reconhecida');
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
    exit;
}
?>
```

---

## 🎯 **JavaScript Padrão**

### **Estrutura com Proteção SPA**
```javascript
/**
 * SIGEP [Nome Módulo] - JavaScript Principal
 */

// Proteger contra múltiplos carregamentos no SPA
if (typeof window.[nomeModulo]Loaded === 'undefined') {
    window.[nomeModulo]Loaded = true;

    // Variáveis globais
    var currentData = {
        itens: [],
        usuarios: []
    };

    // Inicialização
    $(document).ready(function() {
        carregarDados();
        inicializarComponentes();

        // Auto-refresh opcional
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

    // Utilitários
    function mostrarNotificacao(mensagem, tipo = 'info') {
        if (typeof toastr !== 'undefined') {
            toastr[tipo](mensagem);
        } else {
            console.log(`[${tipo.toUpperCase()}] ${mensagem}`);
        }
    }

    // Event listeners
    $(document).on('click', '#btn-salvar', function() {
        // Lógica de salvamento
    });

} // fim do if (typeof window.[nomeModulo]Loaded === 'undefined')
```

---

## 🎨 **CSS Padrão**

### **Estilos Customizados**
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

/* Empty States */
.empty-state {
    text-align: center;
    padding: 40px;
    color: #6c757d;
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

---

## 🔗 **Sistema de Menu (Sidebar)**

### **Arquivos Responsáveis**
- `includes/sidebar.php` - HTML do menu
- `includes/sidebar_logica.php` - Lógica PHP e permissões

### **Estrutura de Menu**
```php
// Função getMenuConfig() em sidebar_logica.php
function getMenuConfig() {
    $menu = [];

    // Menu Censura
    if (podeVisualizarSetor('perm_censura')) {
        $menu['censura'] = [
            'title' => 'Censura',
            'icon' => 'fas fa-lock text-primary',
            'items' => [
                ['title' => 'Cartas', 'icon' => 'fas fa-envelope text-primary',
                 'page' => '/modulos/censura/cartas/cartas_view.php', 'parent' => 'Cartas'],
                // ... mais itens
            ]
        ];
    }

    return $menu;
}
```

### **Adicionar Novo Menu**
1. Abrir `includes/sidebar_logica.php`
2. Encontrar função `getMenuConfig()`
3. Adicionar entrada no setor correspondente
4. Usar `podeVisualizarSetor('perm_setor')` para validação

---

## 🛣️ **URLs Amigáveis (.htaccess)**

### **Estrutura de Rewrite Rules**
```apache
RewriteEngine On

# ROTAS DE MÓDULOS - URLs Amigáveis
RewriteRule ^censura/cartas/?$ modulos/censura/cartas/cartas_view.php [L,QSA]
RewriteRule ^eclusa/movimentacoes/?$ modulos/eclusa/movimentacoes/movimentacoes_view.php [L,QSA]
RewriteRule ^medidas-disciplinares/?$ modulos/coordenacao/medidas_disciplinares/md_view.php [L,QSA]

# ROTAS DE AUTENTICAÇÃO
RewriteRule ^autenticacao/?$ auth/login.php [L,QSA]
RewriteRule ^autenticacao/(.*)$ auth/login.php?msg=$1 [L,QSA]
```

### **Boas Práticas**
- Manter ordem alfabética
- Usar comentários descritivos
- Testar imediatamente após adicionar

---

## 🔔 **Sistema de Notificações**

### **Integração no Header**
- Carregado em `includes/header.php`
- Usa `NotificationManager` class
- Exibe notificações não lidas no dropdown

### **Tipos de Notificações**
- **erro**: ícone `fas fa-exclamation-triangle`, badge `badge-danger`
- **alerta**: ícone `fas fa-warning`, badge `badge-warning`
- **backup**: ícone `fas fa-database`, badge `badge-primary`
- **tarefa**: ícone `fas fa-tasks`, badge `badge-success`

---

## 🚨 **Problemas Comuns e Soluções**

### **Headers Duplicados**
**Problema**: Título e breadcrumb aparecem duas vezes
**Causa**: Uso incorreto de `<section class="content-header">`
**Solução**: Usar apenas `<div class="row mb-2">` vazio

### **JavaScript Errors**
**Problema**: "Identifier already declared"
**Causa**: Múltiplos carregamentos no SPA
**Solução**: Proteger com bloco condicional `if (typeof window[nome]Loaded === 'undefined')`

### **Permissões Negadas**
**Problema**: "Sem permissão para acessar"
**Causa**: Verificação de permissão incorreta
**Solução**: Verificar nome da permissão e sessão

### **AJAX Não Funciona**
**Problema**: Requisições retornam erro
**Causa**: Headers ou CORS incorretos
**Solução**: Verificar headers JSON e usar `ob_clean()`

### **CSS Não Aplica**
**Problema**: Estilos customizados não funcionam
**Causa**: Path incorreto ou cache
**Solução**: Verificar caminho relativo e limpar cache

---

## ✅ **Checklist de Desenvolvimento**

### **Antes de Publicar Módulo**
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

---

## 🎯 **Melhores Práticas**

### **Performance**
- Minimizar requisições AJAX
- Usar cache local quando possível
- Otimizar queries SQL
- Implementar lazy loading

### **Segurança**
- Validar toda entrada de dados
- Usar prepared statements
- Verificar permissões sempre
- Implementar CSRF protection

### **UX/UI**
- Feedback visual para todas ações
- Loading states claros
- Mensagens de erro úteis
- Interface responsiva

### **Código**
- Comentar funções complexas
- Seguir naming conventions
- Manter código limpo
- Versionamento adequado

---

## 📚 **Módulos Existentes**

### **Censura**
- **Cartas**: Gestão de correspondências
- **Manutenção**: Controle de manutenções
- **Estoque**: Controle de itens

### **Eclusa**
- **Movimentações**: Transferência de detentos
- **Escolta**: Gestão de escoltas

### **Laboral**
- **Gestão CTC**: Certificados de Tempo de Contribuição
- **Cálculo de Multas**: Sistema de multas trabalhistas

### **Coordenação**
- **Medidas Disciplinares**: Registro de punições

### **Serviços (Admin)**
- **Job Manager**: Agendador de tarefas
- **phpMyAdmin**: Administração do banco
- **Notificações**: Sistema de alertas
- **Remote Desktop**: Acesso remoto

---

## 🔧 **Dependências Externas**

### **CDNs**
```html
<!-- AdminLTE -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">

<!-- jQuery -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>

<!-- FontAwesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<!-- Google Fonts -->
<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
```

### **Composer (PHP)**
```json
{
    "require": {
        "phpoffice/phpspreadsheet": "^5.4",
        "illuminate/database": "^12.53",
        "illuminate/events": "^12.53",
        "phpoffice/phpword": "^1.4"
    }
}
```

---

## 📖 **Guia Rápido de Desenvolvimento**

### **1. Criar Novo Módulo**
```bash
# Criar estrutura
mkdir -p modulos/[setor]/[nome]/assets/{css,js}

# Criar arquivos
touch modulos/[setor]/[nome]/[nome]_view.php
touch modulos/[setor]/[nome]/[nome]_logica.php
touch modulos/[setor]/[nome]/assets/css/[nome].css
touch modulos/[setor]/[nome]/assets/js/[nome].js
```

### **2. Adicionar Menu**
Editar `includes/sidebar_logica.php`:
```php
if (podeVisualizarSetor('perm_setor')) {
    $menu['setor']['items'][] = [
        'title' => 'Nome Módulo',
        'icon' => 'fas fa-icon text-color',
        'page' => '/modulos/setor/nome/nome_view.php',
        'parent' => 'Nome Módulo'
    ];
}
```

### **3. Adicionar URL**
Editar `.htaccess`:
```apache
RewriteRule ^setor/nome/?$ modulos/setor/nome/nome_view.php [L,QSA]
```

### **4. Criar Tabela**
```sql
CREATE TABLE setor_nome_itens (
    id int(11) AUTO_INCREMENT PRIMARY KEY,
    nome varchar(255) NOT NULL,
    usuario_criador int(11),
    status enum('ativo','inativo') DEFAULT 'ativo',
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_criador) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 🏁 **Conclusão**

Esta arquitetura foi projetada para ser **escalável**, **segura** e **mantível**. O rigoroso padrão MVC garante consistência em todo o sistema, enquanto a arquitetura SPA proporciona uma experiência de usuário fluida e moderna.

**Princípios Fundamentais:**
1. **Segurança em primeiro lugar**
2. **Consistência de código**
3. **Experiência de usuário otimizada**
4. **Manutenibilidade e escalabilidade**

**Para usar este conhecimento:** Carregue este arquivo no início de qualquer conversa sobre desenvolvimento SIGEP para ter acesso completo à arquitetura, padrões e boas práticas do sistema.
