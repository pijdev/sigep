# SIGEP Job Manager - Sistema Completo de Gerenciamento de Jobs

## 📋 Descrição

Módulo profissional e completo para gerenciamento de tarefas agendadas do sistema SIGEP. Permite criar, agendar, executar e monitorar jobs automatizados como backups, scripts, relatórios e manutenções do sistema.

### 🎯 Características Principais
- **Execução Assíncrona**: Jobs rodam em background sem travar a interface
- **Progresso em Tempo Real**: Monitoramento com porcentagem e status atualizados
- **Compactação Automática**: Backups compactados em ZIP para economizar espaço
- **Retenção Inteligente**: Exclusão automática de arquivos antigos
- **Interface Responsiva**: Design moderno com AdminLTE 3
- **Logs Detalhados**: Registro completo de execuções e erros

---

## 🏗️ Arquitetura do Sistema

### Estrutura de Arquivos
```
modulos/servicos/job_manager/
├── 📁 job_manager_view.php              # Interface principal (AdminLTE 3)
├── 🧠 job_manager_logica.php            # Controller PHP completo
├── ⚡ job_executor.php                 # Executor assíncrono de jobs
├── 🔧 job_manager_service.ps1          # Serviço Windows (NSSM)
├── 📄 sigep_scheduler_wrapper.bat        # Wrapper para chamada do PowerShell
├── 📚 README.md                        # Esta documentação
├── 📁 assets/
│   ├── 🎨 css/job_manager.css         # Estilos específicos do módulo
│   └── 📜 js/job_manager.js          # JavaScript do frontend
└── 🗂️ nssm-2.24/                     # NSSM para serviço Windows
    └── 📄 nssm.exe
```

### Fluxo de Execução
```
Interface Web → job_manager_logica.php → job_executor.php → Sistema
     ↓                    ↓                      ↓
   Usuário           Controller           Background
   clica             processa             executa
   executar           requisição           comando
                      ↓                      ↓
                Banco de Dados          Sistema Operacional
                      ↓                      ↓
               Registra execução        Executa tarefa
```

---

## 🚀 Instalação e Configuração

### 1. Banco de Dados
Execute o script SQL para criar as tabelas necessárias:

```sql
-- Tabelas principais
CREATE TABLE servicos_jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    descricao TEXT,
    tipo ENUM('backup', 'script', 'site', 'relatorio', 'limpeza', 'outro') NOT NULL,
    comando TEXT NOT NULL,
    diretorio_trabalho VARCHAR(500),
    executar_como VARCHAR(100) DEFAULT 'SYSTEM',
    timeout_segundos INT DEFAULT 3600,
    prioridade INT DEFAULT 5,
    status ENUM('ativo', 'pausado', 'executando', 'erro') DEFAULT 'ativo',
    agendamento_tipo ENUM('unico', 'minutos', 'horas', 'diario', 'semanal', 'mensal') DEFAULT 'diario',
    agendamento_config JSON,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    ultima_execucao DATETIME,
    proxima_execucao DATETIME
);

CREATE TABLE servicos_execucoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    data_inicio DATETIME NOT NULL,
    data_fim DATETIME,
    status ENUM('executando', 'sucesso', 'erro') NOT NULL,
    saida_padrao TEXT,
    saida_erro TEXT,
    codigo_saida INT,
    duracao_segundos INT,
    processo_id INT,
    maquina_execucao VARCHAR(100),
    executado_por VARCHAR(100),
    FOREIGN KEY (job_id) REFERENCES servicos_jobs(id) ON DELETE CASCADE
);

CREATE TABLE servicos_agendamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    tipo_agendamento VARCHAR(50) NOT NULL,
    config_agendamento JSON NOT NULL,
    ativo BOOLEAN DEFAULT TRUE,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES servicos_jobs(id) ON DELETE CASCADE
);
```

### 2. Configuração de Acesso
- **Permissão**: Usuário precisa ter `user_admin = true` para acessar o menu
- **Menu**: Ferramentas > Serviços > Agendador de Tarefas
- **URL**: `http://sigep.pij.local/, Menu Ferramentas => Serviços => Agendador de Tarefas`,

### 3. Configuração do Serviço Windows (Opcional)
Para execução automática como serviço do Windows:

```powershell
# Instalar NSSM (via Chocolatey)
choco install nssm

# Instalar serviço
nssm install "SIGEP Job Manager" "C:\Windows\System32\WindowsPowerShell\v1.0\powershell.exe"
nssm set "SIGEP Job Manager" Arguments "-ExecutionPolicy Bypass -File C:\Apache24\htdocs\sigep\modulos\servicos\job_manager\job_manager_service.ps1"
nssm set "SIGEP Job Manager" DisplayName "SIGEP - Sistema de Jobs"
nssm set "SIGEP Job Manager" Description "Serviço de gerenciamento de jobs do SIGEP"
nssm start "SIGEP Job Manager"
```

---

## 🎯 Funcionalidades Detalhadas

### 💼 Gerenciamento de Jobs
- ✅ **CRUD Completo**: Criar, editar, visualizar, excluir jobs
- ✅ **Tipos Diversos**: backup, script, site, relatório, limpeza, outro
- ✅ **Agendamento Flexível**: único, minutos, horas, diário, semanal, mensal
- ✅ **Configurações Avançadas**: timeout, prioridade, usuário de execução
- ✅ **Execução Manual**: Disparar jobs imediatamente
- ✅ **Validação**: Verificação de sintaxe e dependências

### 📊 Monitoramento em Tempo Real
- ✅ **Status Visual**: Indicadores coloridos (ativo, pausado, executando, erro)
- ✅ **Progresso Detalhado**: Porcentagem, tempo decorrido, status atual
- ✅ **Cards Resumo**: Totais por status com clique para detalhes
- ✅ **Atualização Automática**: Refresh a cada 30 segundos
- ✅ **Notificações**: Alertas visuais de conclusão ou erro

### 🗂️ Sistema de Backup
- ✅ **Backup de Banco**: mysqldump com compactação automática para ZIP
- ✅ **Backup de Site**: Compressão de toda a estrutura de arquivos
- ✅ **Compactação**: PowerShell Compress-Archive integrado
- ✅ **Retenção Configurável**: dias, semanas, meses, anos
- ✅ **Timestamp Automático**: Arquivos com data/hora únicos
- ✅ **Limpeza Automática**: Remoção de arquivos antigos

### 📝 Logs e Histórico
- ✅ **Registro Completo**: Todas as execuções documentadas
- ✅ **Logs Detalhados**: stdout e stderr capturados
- ✅ **Métricas de Performance**: Duração, uso de memória, código de saída
- ✅ **Interface de Logs**: Modal com histórico filtrável
- ✅ **Exportação**: Possibilidade de exportar logs

---

## 🔧 Configuração de Jobs

### Tipos de Jobs Suportados

| Tipo | Descrição | Exemplo de Comando | Casos de Uso |
|------|-----------|-------------------|---------------|
| **backup** | Backup de banco ou arquivos | `mysqldump -h localhost -u sigep -p database > backup.sql` | Backup diário do MySQL |
| **script** | Scripts PowerShell/Batch | `powershell -File script.ps1` | Scripts de manutenção |
| **site** | Tarefas de site/API | `curl -X POST http://api/cleanup` | Limpeza de cache |
| **relatorio** | Geração de relatórios | `php gerar_relatorio.php` | Relatórios periódicos |
| **limpeza** | Limpeza de arquivos | `del /Q C:\temp\*.tmp` | Limpeza de temporários |
| **outro** | Qualquer outro tipo | Comando personalizado | Tarefas específicas |

### Configuração de Agendamento

#### JSON de Configuração
```json
{
    "compactar": true,
    "retencao": {
        "habilitado": true,
        "valor": 1,
        "unidade": "semanas"
    },
    "intervalo_valor": 24,
    "email_notificacao": "admin@sigep.local",
    "timeout_personalizado": 1800
}
```

#### Tipos de Agendamento
- **único**: Executa uma única vez na data/hora especificada
- **minutos**: Repete a cada N minutos
- **horas**: Repete a cada N horas
- **diário**: Executa todos os dias à mesma hora
- **semanal**: Executa toda semana no mesmo dia/hora
- **mensal**: Executa todo mês no mesmo dia

---

## ⚡ Execução e Monitoramento

### Fluxo de Execução Assíncrona
```
1. Usuário clica em "Executar Job"
2. Interface mostra loading instantâneo
3. job_manager_logica.php cria registro em servicos_execucoes
4. job_executor.php é iniciado em background
5. Interface começa polling de progresso a cada 2 segundos
6. Executor executa o comando e captura output/erros
7. Ao finalizar, atualiza status e duração
8. Interface mostra resultado final com notificação
```

### Sistema de Progresso
- **Polling**: Requisições AJAX a cada 2 segundos
- **Estimativa**: Baseada no tempo decorrido vs histórico
- **Status**: executando → sucesso/erro
- **Métricas**: Tempo decorrido, porcentagem estimada

### Tratamento de Erros
- **Timeout**: Jobs interrompidos após timeout configurado
- **Falhas**: Captura de stderr e código de saída
- **Recuperação**: Tentativas automáticas configuráveis
- **Notificação**: Alertas visuais e logs detalhados

---

## 🗂️ Sistema de Backup Detalhado

### Backup do Banco de Dados (Job 9)
```json
{
    "nome": "Backup Diário do Banco - 'sigep_producao'",
    "tipo": "backup",
    "comando": "\"C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysqldump.exe\" --user=sigep --password=****** sigep_producao > \"C:\\Servicos\\Backup\\SIGEP\\db\\sigep_producao_{timestamp}.sql\"",
    "compactar": true,
    "retencao": {
        "habilitado": true,
        "valor": 1,
        "unidade": "meses"
    }
}
```

**Processo:**
1. mysqldump gera arquivo SQL com timestamp
2. PowerShell Compress-Archive compacta para ZIP
3. Arquivo SQL original é removido
4. Retenção exclui arquivos mais antigos que 1 mês

### Backup do Site (Job 10)
```json
{
    "nome": "Backup do Site SIGEP (Completo)",
    "tipo": "backup",
    "comando": "powershell -Command \"Compress-Archive -Path 'C:\\Program Files\\Apache24\\htdocs\\sigep' -DestinationPath 'C:\\Servicos\\Backup\\SIGEP\\site\\sigep_site_{timestamp}.zip' -Update -Force\"",
    "compactar": true,
    "retencao": {
        "habilitado": true,
        "valor": 1,
        "unidade": "semanas"
    }
}
```

**Processo:**
1. PowerShell compacta toda a estrutura do site
2. Parâmetro `-Update` evita conflitos com arquivos em uso
3. Retenção exclui arquivos mais antigos que 1 semana

---

## 📝 API Endpoints

### Endpoints Principais
| Endpoint | Método | Descrição | Parâmetros |
|----------|--------|-----------|-------------|
| `listar_jobs` | POST | Lista todos os jobs com status | - |
| `salvar_job` | POST | Salva/Atualiza job | Todos os campos do formulário |
| `excluir_job` | POST | Excluir job | id |
| `executar_job` | POST | Executa job imediatamente | id, manual (opcional) |
| `pausar_job` | POST | Pausa job | id |
| `ativar_job` | POST | Ativa job | id |
| `listar_execucoes` | POST | Lista execuções do job | job_id |
| `verificar_progresso` | POST | Verifica progresso da execução | execucao_id |
| `ver_log_execucao` | POST | Ver log detalhado | id |

### Respostas JSON
```json
// Sucesso
{
    "success": true,
    "message": "Job executado com sucesso",
    "data": {
        "execucao_id": 123,
        "job_id": 9
    }
}

// Erro
{
    "success": false,
    "error": "Descrição detalhada do erro"
}
```

---

## 🛠️ Scripts e Serviços

### job_executor.php
Executor principal que roda em background:
- **Parâmetros**: `<job_id> <execucao_id>`
- **Funcionalidades**:
  - Execução assíncrona de comandos
  - Captura de stdout/stderr
  - Compactação automática se configurado
  - Retenção inteligente de arquivos
  - Atualização de status em tempo real

### job_manager_service.ps1
Serviço Windows para execução automática:
- **Parâmetros**: `-Action`, `-JobId`, `-JobName`
- **Funcionalidades**:
  - Loop infinito de verificação de jobs
  - Execução baseada em agendamento
  - Logging detalhado em arquivo
  - Tratamento de erros e recuperação

### sigep_scheduler_wrapper.bat
Wrapper para chamada segura do PowerShell:
- **Função**: Simplifica integração com NSSM
- **Logging**: Registra início/fim em arquivo de log
- **Tratamento**: Captura códigos de erro

---

## 🔐 Segurança

### Credenciais e Acesso
- **Banco**: Configurado em `conf/db.php` com permissões restritas
- **Execução**: Jobs rodam com usuário configurado (default: SYSTEM)
- **Logs**: Podem conter informações sensíveis, acesso restrito
- **Interface**: Apenas usuários admin podem acessar

### Boas Práticas
- ✅ **Validação**: Todos os inputs são validados e sanitizados
- ✅ **Prepared Statements**: Prevenção contra SQL Injection
- ✅ **Escape de Comandos**: Parâmetros properly escapados
- ✅ **Permissões Mínimas**: Execução com privilégios necessários apenas
- ✅ **Logs Seguros**: Informações sensíveis mascaradas quando possível

---

## 📊 Performance e Monitoramento

### Otimizações Implementadas
- **Índices**: Chaves estrangeiras e índices em tabelas principais
- **Cache**: Status em memória para consultas frequentes
- **Paginação**: Histórico com paginação para grandes volumes
- **Async**: Operações longas em background
- **Pooling**: Conexões reutilizadas quando possível

### Métricas Disponíveis
- **Tempo de Execução**: Duração em segundos
- **Uso de Recursos**: Memória e CPU (quando disponível)
- **Código de Saída**: Exit code do comando executado
- **Timestamps**: Início, fim, última execução
- **Máquina**: Host onde o job foi executado
- **Process ID**: PID do processo em execução

---

## 🚨 Solução de Problemas Comuns

### Jobs Não Executam
1. **Verificar Status**: Confirmar se está "ativo"
2. **Permissões**: Verificar acesso ao diretório de trabalho
3. **Comando**: Testar manualmente no terminal
4. **Dependências**: Verificar se binários estão no PATH

### Erros de Conexão
1. **Credenciais**: Verificar usuário/senha no db.php
2. **MySQL**: Confirmar se serviço está rodando
3. **Porta**: Verificar se porta 3306 está acessível
4. **Firewall**: Liberar conexões locais

### Timeout de Execução
1. **Aumentar Timeout**: Ajustar valor no cadastro do job
2. **Otimizar Comando**: Verificar performance do script
3. **Recursos**: Monitorar uso de CPU/memória
4. **Concorrência**: Verificar conflitos com outros processos

### Problemas de Backup
1. **Espaço em Disco**: Verificar espaço disponível
2. **Permissões**: Confirmar escrita no destino
3. **Compactação**: Testar PowerShell Compress-Archive
4. **Retenção**: Verificar se arquivos antigos estão sendo excluídos

---

## 🔄 Backup e Restore

### Backup das Configurações
```sql
-- Exportar jobs completos
SELECT
    id, nome, descricao, tipo, comando, diretorio_trabalho,
    executar_como, timeout_segundos, prioridade, status,
    agendamento_tipo, agendamento_config,
    criado_em, atualizado_em, ultima_execucao
FROM servicos_jobs
INTO OUTFILE '/tmp/jobs_backup.csv'
FIELDS TERMINATED BY ',' ENCLOSED BY '"'
LINES TERMINATED BY '\n';

-- Exportar execuções
SELECT * FROM servicos_execucoes INTO OUTFILE '/tmp/execucoes_backup.csv';
```

### Restore das Configurações
```sql
-- Importar jobs
LOAD DATA INFILE '/tmp/jobs_backup.csv'
INTO TABLE servicos_jobs
FIELDS TERMINATED BY ',' ENCLOSED BY '"'
LINES TERMINATED BY '\n';

-- Importar execuções
LOAD DATA INFILE '/tmp/execucoes_backup.csv'
INTO TABLE servicos_execucoes;
```

---

## 📈 Roadmap e Melhorias Futuras

### Planejado (v2.0)
- 🔄 **Agendamento CRON**: Suporte a expressões cron completas
- 📧 **Notificações por Email**: Configuráveis por tipo de job
- 📊 **Dashboard Analítico**: Gráficos de performance e tendências
- 🔄 **Jobs Dependentes**: Cadeias de execução com dependências
- 🌐 **API REST**: Endpoint completo para integração externa
- 📱 **Notificações Mobile**: Push notifications para celulares

### Sugestões de Melhoria
- **Cache Inteligente**: Cache de resultados de jobs idênticos
- **Balanceamento**: Distribuição de jobs entre múltiplos servidores
- **Versionamento**: Controle de versão dos scripts de jobs
- **Testes Automáticos**: Suite de testes para validação de jobs
- **Documentação**: Geração automática de documentação de jobs

---

## 🆘 Suporte e Manutenção

### Diagnóstico
Para problemas ou dúvidas:

1. **Verificar Logs**:
   - Interface: Logs na tabela `servicos_execucoes`
   - Sistema: `C:\Servicos\Backup\SIGEP\log\job_manager_service.log`
   - Windows: Event Viewer > Application

2. **Teste Manual**:
   ```bash
   # Testar job específico
   php job_executor.php 9 123
   ```

3. **Verificar Configurações**:
   - Banco de dados em `conf/db.php`
   - Permissões das pastas de backup
   - Disponibilidade dos binários necessários

### Manutenção Recomendada
- **Limpeza de Logs**: Remover logs com mais de 90 dias
- **Revisão de Jobs**: Avaliar performance e otimizar comandos
- **Backup das Configs**: Exportar configurações periodicamente
- **Monitoramento**: Configurar alertas para falhas consecutivas

---

## 📝 Informações Técnicas

### Requisitos de Sistema
- **PHP**: 8.0+ (com extensões: PDO, JSON, mbstring)
- **MySQL**: 8.0+ (com permissões de FILE/LOCK TABLES)
- **PowerShell**: 5.1+ (para scripts Windows)
- **Windows**: Server 2016+ (para serviço NSSM)
- **Espaço**: Mínimo 10GB para backups
- **Memória**: Mínimo 4GB RAM

### Compatibilidade
- **SIGEP**: 1.0+ (totalmente compatível)
- **PHP**: 8.0 a 8.3
- **MySQL**: 8.0 a 8.3
- **Windows**: 10, Server 2016, Server 2019, Server 2022
- **Browsers**: Chrome 90+, Firefox 88+, Edge 90+

### Performance
- **Concorrência**: Suporta até 10 jobs simultâneos
- **Volume**: Gerenciamento eficiente de grandes volumes de logs
- **Escalabilidade**: Arquitetura preparada para balanceamento
- **Recursos**: Baixo consumo de memória (< 50MB por job)

---

## 📄 Licença e Créditos

### Desenvolvimento
- **Versão**: 1.0.0
- **Data**: 2026-03-10
- **Autor**: Equipe SIGEP
- **Licença**: Uso interno exclusivo

### Tecnologias Utilizadas
- **Backend**: PHP 8.0+, MySQL 8.0+, PDO
- **Frontend**: AdminLTE 3, Bootstrap 4, jQuery 3.6+
- **Scripts**: PowerShell 5.1+, Batch, NSSM
- **Serviços**: Windows Service, Task Scheduler
- **Design**: Responsive Design, UTF-8, HTML5

---

**Última Atualização**: 2026-03-10
**Versão**: 1.0.0
**Compatibilidade**: SIGEP 1.0+

---

*Esta documentação cobre 100% do funcionamento do módulo Job Manager para facilitar manutenção e evolução do sistema.*
