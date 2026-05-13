# Módulo de Serviço: Bancos BR - Febraban

## Descrição
Serviço de sincronização de dados bancários brasileiros entre a BrasilAPI e o banco de dados local SIGEP.

## Responsabilidade
- Sincronizar lista de bancos da BrasilAPI → Tabela `brasil_bancos_ativos`
- Fornecer fallback de dados quando BrasilAPI estiver indisponível
- Manter base atualizada automaticamente (a cada 1 hora)

## Estrutura MVC
```
bancos_br_febraban/
├── bancos_br_febraban_logica.php    # Controller (lógica de negócio)
├── assets/
│   └── js/
│       └── bancos_br_febraban.js     # Service Worker (auto-sincronização)
└── README.md                          # Documentação
```

## Endpoints do Controller

### `?action=sincronizar` (GET/POST)
Sincroniza dados da BrasilAPI com banco local.

**Response**:
```json
{
  "success": true,
  "message": "Sincronização concluída com sucesso",
  "total_api": 146,
  "inseridos": 0,
  "atualizados": 2,
  "data_execucao": "2026-04-08 12:00:00"
}
```

### `?action=listar` (GET/POST)
Lista bancos do banco de dados local.

**Response**:
```json
{
  "success": true,
  "data": [...],
  "total": 146,
  "ultima_sincronizacao": "2026-04-08 11:00:00",
  "data_consulta": "2026-04-08 12:00:00"
}
```

### `?action=status` (GET/POST)
Retorna status do serviço.

**Response**:
```json
{
  "success": true,
  "servico": "bancos_br_febraban",
  "status": "ativo",
  "banco_dados": {
    "registros": 146,
    "ultima_sincronizacao": "2026-04-08 11:00:00",
    "status": "populado"
  },
  "brasilapi": {
    "online": true,
    "url": "https://brasilapi.com.br/api/banks/v1"
  },
  "timestamp": "2026-04-08 12:00:00"
}
```

## JavaScript Service

O arquivo `bancos_br_febraban.js` é carregado automaticamente pelo `footer.php` e executa:

1. **Verificação inicial**: Checa se sincronização é necessária (a cada 1 hora)
2. **Auto-sincronização**: Chama `?action=sincronizar` automaticamente
3. **Agendamento**: Define intervalo de 1 hora para próximas sincronizações

### Uso via Console
```javascript
// Forçar sincronização
SIGEP.Servicos.BancosBRFebraban.sincronizar();

// Verificar status
SIGEP.Servicos.BancosBRFebraban.checkStatus().then(console.log);

// Listar bancos locais
SIGEP.Servicos.BancosBRFebraban.listarBancos().then(console.log);
```

## Fluxo de Dados

```
BrasilAPI (online)
    ↓ (sincronização periódica)
Serviço SIGEP (bancos_br_febraban.js)
    ↓ (chama controller)
Controller (bancos_br_febraban_logica.php)
    ↓ (INSERT/UPDATE)
Tabela brasil_bancos_ativos (MySQL)
    ↑ (consulta fallback)
API Endpoint (api/v1/endpoints/bancos_br_febraban.php)
    ↑ (consulta primária)
Módulos SIGEP (calculo_multas.js, etc.)
```

## Configuração

### Tabela MySQL
```sql
CREATE TABLE brasil_bancos_ativos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(10) NOT NULL UNIQUE,
    nome VARCHAR(255) NOT NULL,
    ispb VARCHAR(20),
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### Cronjob (opcional)
Para garantir sincronização mesmo sem usuários ativos:
```bash
# /etc/cron.d/sigep-bancos
0 * * * * root curl -s "http://localhost/sigep/modulos/servicos/bancos_br_febraban/bancos_br_febraban_logica.php?action=sincronizar" > /dev/null
```

## Logs

O serviço loga no console do navegador:
```
[BancosBRFebraban] Serviço iniciado
[BancosBRFebraban] Iniciando sincronização...
[BancosBRFebraban] ✓ Sincronização concluída | Total API: 146 | Inseridos: 0 | Atualizados: 2
[BancosBRFebraban] Próxima sincronização em 55 minutos
```

## Dependências
- PHP 8.0+
- MySQL 8.0+
- cURL (PHP extension)
- jQuery 3.6+ (para módulos consumidores)

## Autor
SIGEP System

## Versão
1.0.0 - 2026-04-08
