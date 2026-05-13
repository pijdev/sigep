# 🏥 SIGEP E2E Health Check

## 🎯 **Objetivo**

Script automatizado para verificar se o sistema SIGEP está online e funcional através de testes End-to-End.

## 📋 **Funcionalidades**

### ✅ **Validações Realizadas**
- **Acesso ao Sistema**: Navegação para http://sigep.pij.local/
- **Login Automático**: Autenticação com usuário `cascade`
- **Dashboard Validation**: Verificação de elementos principais
- **Performance Monitoring**: Tempos de carregamento
- **Error Detection**: Captura de erros JavaScript/console
- **Screenshot Capture**: Evidências visuais

### 🎯 **Indicadores de Saúde**
- **healthy**: Sistema 100% funcional
- **partial**: Sistema acessível mas com problemas
- **failed**: Sistema inacessível ou com erros críticos

## 🚀 **Como Usar**

### **Instalação**
```bash
cd .windsurf/scripts/e2e
npm install
```

### **Execução**
```bash
# Teste completo
npm test

# Ou diretamente
node test_sigep_health.js

# Em modo produção (headless)
NODE_ENV=production node test_sigep_health.js
```

### **Resultados**
- **Console**: Relatório em tempo real
- **JSON**: `.windsurf/scripts/e2e/health_report.json`
- **Screenshots**: `.windsurf/scripts/e2e/screenshots/`

## 📊 **Relatório Gerado**

```json
{
  "status": "healthy",
  "timestamp": "2026-03-17T08:45:00.000Z",
  "summary": {
    "totalSteps": 8,
    "errors": 0,
    "performance": {
      "pageLoad": 2340,
      "login": 1250,
      "validation": 450
    }
  },
  "steps": [...],
  "errors": [...]
}
```

## 🔧 **Configuração**

### **Credenciais** (em `test_sigep_health.js`)
```javascript
const CONFIG = {
    url: 'http://sigep.pij.local/',
    usuario: 'cascade',
    senha: 'cascade123',
    timeout: 30000
};
```

### **Validação Customizada**
- **SUCCESS_INDICATORS**: Elementos que indicam sucesso
- **ERROR_INDICATORS**: Elementos que indicam erro

## 🎯 **Integração CI/CD**

### **Exit Codes**
- `0`: Sistema saudável
- `1**: Sistema com problemas
- `2**: Erro fatal no script

### **Exemplo Pipeline**
```bash
#!/bin/bash
cd .windsurf/scripts/e2e
npm test
if [ $? -eq 0 ]; then
    echo "✅ SIGEP saudável"
else
    echo "❌ SIGEP com problemas"
    exit 1
fi
```

## 📁 **Estrutura de Arquivos**

```
.windsurf/scripts/e2e/
├── test_sigep_health.js    # Script principal
├── package.json           # Dependências
├── README.md              # Documentação
├── screenshots/           # Evidências visuais
│   ├── dashboard_success.png
│   ├── login_error.png
│   └── ...
└── health_report.json     # Relatório detalhado
```

## 🚨 **Troubleshooting**

### **Problemas Comuns**
1. **Puppeteer não instala**: `npm install puppeteer --ignore-scripts`
2. **Timeout**: Aumentar `CONFIG.timeout`
3. **Credenciais**: Verificar usuário `cascade` no banco
4. **URL**: Confirmar `http://sigep.pij.local/` acessível

### **Debug Mode**
```bash
# Ver detalhes do navegador
node test_sigep_health.js --debug
```

## 🔄 **Agendamento**

### **Windows Task Scheduler**
```cmd
# Executar a cada 5 minutos
schtasks /create /tn "SIGEP Health Check" /tr "node C:\...\test_sigep_health.js" /sc minute /mo 5
```

### **Cron (Linux)**
```bash
# A cada 5 minutos
*/5 * * * * cd /path/to/sigep/.windsurf/scripts/e2e && node test_sigep_health.js
```

## 📈 **Métricas Monitoradas**

- **Page Load Time**: Tempo carregar página inicial
- **Login Time**: Tempo autenticação
- **Validation Time**: Tempo validar dashboard
- **Element Count**: Número de elementos dinâmicos
- **Error Count**: Erros JavaScript/console

---

**Criado por**: Cascade AI  
**Versão**: 1.0.0  
**Data**: 2026-03-17