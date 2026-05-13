# API v1 - Endpoints

## Endpoints Disponíveis

### bancos_br_febraban.php
**Descrição**: Lista de bancos brasileiros com código FEBRABAN

**Fonte**: https://brasilapi.com.br/api/banks/v1

**Cache**: 1 hora (HTTP header)

**Autenticação**: Não necessária

**Query Params**:
| Parâmetro | Tipo | Descrição | Padrão |
|-----------|------|-----------|--------|
| source | string | 'brasilapi' ou 'local' | brasilapi |

**Response**:
```json
{
  "success": true,
  "data": [
    {
      "codigo": "001",
      "nome": "Banco do Brasil S.A.",
      "ispb": "00000000"
    }
  ],
  "total": 146,
  "fonte": "brasilapi",
  "timestamp": "2026-04-08 12:00:00"
}
```

**Erros**:
- `503 Service Unavailable`: BrasilAPI e banco de dados local indisponíveis

## Padrões

### Nomenclatura
`{recurso}_{contexto}_{fonte}.php`

Exemplos:
- `bancos_br_febraban.php` - Bancos brasileiros, dados Febraban
- `internos_consulta_sigep.php` - Internos, fonte SIGEP
- `salarios_historico_laboral.php` - Salários, módulo laboral

### Estrutura de Response
```json
{
  "success": true|false,
  "data": [],
  "total": 0,
  "fonte": "string",
  "timestamp": "YYYY-MM-DD HH:MM:SS",
  "error": "string" // apenas em caso de erro
}
```
