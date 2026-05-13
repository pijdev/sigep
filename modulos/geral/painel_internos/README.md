# Painel de Internos

## 📋 **Descrição**

Módulo do SIGEP para visualização e gerenciamento do painel de internos do presídio.

## 🗂️ **Estrutura de Arquivos**

```
modulos/geral/painel_internos/
├── internos_painel_view.php              ← Interface principal (View)
├── internos_painel_logica.php            ← Lógica de negócio (Controller)
├── assets/
│   ├── css/
│   │   └── internos_painel.css        ← Estilos específicos
│   └── js/
│       └── internos_painel.js          ← JavaScript unificado
└── README.md                             ← Documentação
```

## 🎯 **Funcionalidades**

- **Visualização em grid** de todas as celas do presídio
- **Busca de internos** por IPEN, nome ou nome social
- **Offcanvas lateral** com detalhes completos da cela
- **Contagem de eletrônicos** por tipo (TV, rádio, ventilador, etc.)
- **Histórico de movimentações** (entradas/saídas)
- **Medidas disciplinares** ativas na cela
- **Modais detalhados** para cada tipo de item

## 🔄 **Navegação**

Acessível através do menu lateral:
```
TI → Painel de Internos
```

URL: `modulos/geral/painel_internos/internos_painel_view.php`

## 🔧 **Dependências**

- **PHP 8+** com PDO para conexão MySQL
- **MySQL 8.0** com charset utf8mb4
- **jQuery 3.6+** para manipulação DOM
- **AdminLTE 3** para componentes UI
- **FontAwesome 6.4.0** para ícones

## 📊 **Tabelas do Banco**

- `internos` - Dados principais dos internos
- `internos_eletronicos` - Eletrônicos nas celas
- `internos_md_medidas` - Medidas disciplinares
- `internos_md_itens_apreendidos` - Itens apreendidos
- `internos_historico_detalhado` - Histórico de alterações
- `internos_importacao` - Controle de importações

## 🚨 **Observações Importantes**

- **Cache implementado** para otimizar performance
- **Proteção SPA** contra múltiplos carregamentos
- **Tratamento especial** para galerias semi-aberto (SA, SB, SC, SD, SE, ST)
- **Validação de sessão** e permissões
- **UTF-8** em todo o sistema

## 📅 **Histórico de Mudanças**

- **Data**: 2026-03-25
- **Motivo**: Unificação do módulo (movido de paginas/ e includes/)
- **Alterações**: 
  - Movidos arquivos para estrutura MVC
  - Unificadas funções JavaScript
  - Ajustados caminhos relativos
  - Removidas duplicatas do global.js

## 👥 **Desenvolvedor**

Mantido pela equipe de TI do SIGEP
