# API Endpoints - SIGEP

## Estrutura de Organização

```
api/
├── README.md
├── AGENTS.md
├── v1/
│   ├── README.md
│   └── endpoints/
│       ├── bancos_br_febraban.php      # Lista de bancos brasileiros (BrasilAPI/Febraban)
│       ├── internos_consulta.php       # Consulta de internos
│       └── ...
└── v2/ (futuro)
```

## Padrões de Nomenclatura

### Endpoints:
- `{recurso}_{contexto}_{fonte}.php`
- Exemplos:
  - `bancos_br_febraban.php` - Bancos brasileiros, dados Febraban/BrasilAPI
  - `internos_consulta_sigep.php` - Consulta de internos, fonte SIGEP
  - `salarios_historico_laboral.php` - Histórico de salários, módulo laboral

### Recursos Principais:
- `bancos_` - Informações de instituições financeiras
- `internos_` - Dados de internos do sistema
- `salarios_` - Remuneração e histórico
- `dividas_` - Dívidas e obrigações
- `usuarios_` - Usuários do sistema

## Versões da API

- **v1** (atual): Endpoints básicos, sem autenticação para dados públicos
- **v2** (futuro): Autenticação JWT, rate limiting, paginação padronizada

## Endpoints Atuais (v1)

### bancos_br_febraban.php
- **Fonte**: https://brasilapi.com.br/api/banks/v1
- **Descrição**: Lista de bancos brasileiros com código FEBRABAN
- **Cache**: 1 hora (HTTP header)
- **Fallback**: Banco de dados local (`brasil_bancos_ativos`)
- **Query Params**:
  - `source=local` - Forçar consulta ao banco de dados local

### Exemplo de uso:
```
GET /api/v1/endpoints/bancos_br_febraban.php
Response: { "success": true, "data": [...], "fonte": "brasilapi" }
```
