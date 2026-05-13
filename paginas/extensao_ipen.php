# iPEN v2 - Guia de Instalação em Rede

## 📦 Métodos de Instalação

### Método 1: Download Manual (Recomendado)
1. Acesse o SIGEP: `http://servidor/sigep/modulos/downloads`
2. Baixe o arquivo `ipenv2.zip`
3. Descompacte em uma pasta de rede compartilhada
4. Instruções para usuários abaixo

### Método 2: Instalação via GPO (Empresas)
- Compacte a extensão em `.zip`
- Distribua via Group Policy
- Configure para instalar automaticamente no Chrome

### Método 3: Script de Instalação Automática
- Use o script PowerShell fornecido
- Execute via logon script ou manualmente

## 📁 Arquivos Necessários

### Estrutura da Extensão:
```
ipenv2/
├── manifest.json          # Configuração da extensão
├── popup.html             # Interface popup
├── popup.js               # Controles do popup
├── content.js             # Orquestrador principal
├── correcoes.js           # Correções de bugs
├── sidebar.js             # Sidebar moderna
├── relatorio15.js         # Relatório 1-5 melhorado
├── autoimport.js           # Importação automática 1-8
└── icon.png               # Ícone da extensão
```

## 🔧 Passo a Passo para Instalação Manual

### Para Usuários:
1. **Baixe a extensão** da pasta de rede: `\\servidor\compartilhamento\ipenv2.zip`
2. **Descompacte** o arquivo na sua área de trabalho
3. **Abra o Chrome** e acesse: `chrome://extensions/`
4. **Ative "Modo do desenvolvedor"** (canto superior direito)
5. **Clique em "Carregar sem compactação"**
6. **Selecione a pasta** `ipenv2` descompactada
7. **Pronto!** A extensão estará ativa

### Para Administradores:
1. **Copie `ipenv2.zip`** para uma pasta de rede acessível
2. **Compartilhe as instruções** abaixo com os usuários
3. **Monitore o uso** através do SIGEP (logs de importação)

## 📋 Instruções para Usuários (Imprima e Distribua)

```
═══════════════════════════════════════════════════════════════════════════
                    INSTALAÇÃO EXTENSÃO iPEN v2 - SIGEP
═══════════════════════════════════════════════════════════════════════════

O que é:
• Extensão para otimizar o uso do sistema iPEN
• Sidebar moderna com design escuro
• Correção automática de erros
• Melhorias no relatório 1-5
• Importação automática do relatório 1-8

Como instalar:
1. Acesse a pasta de rede: \\servidor\compartilhamento\
2. Baixe o arquivo: ipenv2.zip
3. Descompacte na Área de Trabalho
4. Abra o Chrome
5. Digite na barra de endereço: chrome://extensions/
6. Ative "Modo do desenvolvedor" (botão no canto superior direito)
7. Clique em "Carregar sem compactação"
8. Selecione a pasta "ipenv2" que você descompactou
9. Pronto! A extensão aparecerá na lista.

Como usar:
• Acesse o sistema iPEN normalmente
• A extensão ativará automaticamente
• Use o popup (ícone iPEN) para ativar/desativar funções
• A importação automática roda a cada 1 hora (se ativada)

Suporte:
• Em caso de dúvidas, contate o suporte técnico
• A extensão NÃO interfere com o funcionamento normal
• Pode ser desativada a qualquer momento

═══════════════════════════════════════════════════════════════════════════
```

## 📜 Script PowerShell para Instalação Automática

Crie o arquivo `instalar_ipen_v2.ps1`:
```powershell
# Script de Instalação Automática - iPEN v2 Extension
# Execute como Administrador

Write-Host "🚀 Instalando Extensão iPEN v2..." -ForegroundColor Green

# Caminhos
$NetworkPath = "\\servidor\compartilhamento\ipenv2.zip"
$LocalPath = "$env:TEMP\ipenv2.zip"
$ExtractPath = "$env:USERPROFILE\Extensions\ipenv2"

# Baixar da rede
Write-Host "📥 Baixando extensão da rede..." -ForegroundColor Blue
Copy-Item $NetworkPath $LocalPath -Force

# Extrair
Write-Host "📦 Extraindo arquivos..." -ForegroundColor Blue
if (Test-Path $ExtractPath) {
    Remove-Item $ExtractPath -Recurse -Force
}
Expand-Archive $LocalPath -DestinationPath $ExtractPath -Force

# Limpar
Remove-Item $LocalPath -Force

# Abrir Chrome para instalação
Write-Host "🌐 Abrindo Chrome para instalação..." -ForegroundColor Blue
Start-Process "chrome.exe" "chrome://extensions/"

Write-Host "✅ Extensão preparada!" -ForegroundColor Green
Write-Host "📋 Siga os passos no Chrome:" -ForegroundColor Yellow
Write-Host "   1. Ative 'Modo do desenvolvedor'" -ForegroundColor White
Write-Host "   2. Clique em 'Carregar sem compactação'" -ForegroundColor White
Write-Host "   3. Selecione a pasta: $ExtractPath" -ForegroundColor White
Write-Host "   4. Pronto!" -ForegroundColor Green

# Manter janela aberta
Read-Host "Pressione Enter para fechar"
```

## 🌐 Página de Distribuição no SIGEP

Vou criar uma página dedicada para facilitar o acesso:
<tool_call>write_to_file
<arg_key>CodeContent</arg_key>
<arg_value><section class="content">
    <div class="container-fluid">

        <!-- Cabeçalho -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-gradient-success">
                        <h3 class="card-title text-white">
                            <i class="fas fa-rocket"></i>
                            iPEN v2 - Extensão para Chrome
                        </h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Download da Extensão Completa -->
        <div class="row">
            <div class="col-md-8">
                <div class="card card-outline card-success shadow-lg">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-download"></i>
                            Baixar Extensão Completa
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <h5><i class="fas fa-info-circle"></i> O que está incluído:</h5>
                            <ul class="mb-0">
                                <li><strong>Sidebar Moderna</strong> - Design escuro Zinc 950</li>
                                <li><strong>Correções Automáticas</strong> - Evita erro 403</li>
                                <li><strong>Relatório 1-5</strong> - Cards com fotos</li>
                                <li><strong>AutoImport 1-8</strong> - Importação automática</li>
                                <li><strong>Interface Popup</strong> - Controle ON/OFF</li>
                            </ul>
                        </div>

                        <div class="text-center mb-3">
                            <a href="\\10.40.88.200\extensions\ipenv2\ipenv2.zip"
                               class="btn btn-success btn-lg shadow"
                               download="ipen-v2-extensao.zip">
                                <i class="fas fa-download mr-2"></i>
                                Baixar Extensão Completa
                            </a>
                        </div>

                        <div class="text-muted small text-center">
                            <i class="fas fa-hdd"></i> Tamanho: ~150KB |
                            <i class="fas fa-code-branch"></i> Versão: 1.0.0 |
                            <i class="fas fa-shield-alt"></i> Seguro para uso
                        </div>
                    </div>
                </div>
            </div>

            <!-- Instruções Rápidas -->
            <div class="col-md-4">
                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-list-ol"></i>
                            Instalação Rápida
                        </h5>
                    </div>
                    <div class="card-body">
                        <ol class="list-unstyled numbered-list">
                            <li class="mb-2">
                                <strong>1. Baixe</strong> o arquivo acima
                            </li>
                            <li class="mb-2">
                                <strong>2. Descompacte</strong> na Área de Trabalho
                            </li>
                            <li class="mb-2">
                                <strong>3. Chrome:</strong> digite <code>chrome://extensions/</code>
                            </li>
                            <li class="mb-2">
                                <strong>4. Ative</strong> "Modo do desenvolvedor"
                            </li>
                            <li class="mb-2">
                                <strong>5. Clique</strong> "Carregar sem compactação"
                            </li>
                            <li class="mb-2">
                                <strong>6. Selecione</strong> a pasta <code>ipenv2</code>
                            </li>
                        </ol>

                        <div class="alert alert-info mt-3">
                            <h5><i class="fas fa-lightbulb"></i>
                                <strong>Dica:</strong> Salve a pasta para facilitar atualizações futuras
                            </h5>
                            <small>Caminho da pasta: <code>\\10.40.88.200\extensions\ipenv2\</code></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Funcionalidades Detalhadas -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-cogs"></i>
                            Funcionalidades da Extensão
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- Sidebar -->
                            <div class="col-md-6 col-lg-3">
                                <div class="info-box">
                                    <span class="info-box-icon bg-info">
                                        <i class="fas fa-columns"></i>
                                    </span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Sidebar</span>
                                        <span class="info-box-number">Shadcn UI</span>
                                        <div class="progress">
                                            <div class="progress-bar bg-info" style="width: 100%"></div>
                                        </div>
                                        <span class="progress-description">
                                            Menu lateral moderno com tema escuro
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Correções -->
                            <div class="col-md-6 col-lg-3">
                                <div class="info-box">
                                    <span class="info-box-icon bg-success">
                                        <i class="fas fa-tools"></i>
                                    </span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Correções</span>
                                        <span class="info-box-number">Anti-403</span>
                                        <div class="progress">
                                            <div class="progress-bar bg-success" style="width: 100%"></div>
                                        </div>
                                        <span class="progress-description">
                                            Interceptor de erros e bugs
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Relatório 1-5 -->
                            <div class="col-md-6 col-lg-3">
                                <div class="info-box">
                                    <span class="info-box-icon bg-primary">
                                        <i class="fas fa-file-image"></i>
                                    </span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Relatório 1-5</span>
                                        <span class="info-box-number">Cards</span>
                                        <div class="progress">
                                            <div class="progress-bar bg-primary" style="width: 100%"></div>
                                        </div>
                                        <span class="progress-description">
                                            Design moderno com fotos
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- AutoImport -->
                            <div class="col-md-6 col-lg-3">
                                <div class="info-box">
                                    <span class="info-box-icon bg-warning">
                                        <i class="fas fa-robot"></i>
                                    </span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">AutoImport</span>
                                        <span class="info-box-number">1-8</span>
                                        <div class="progress">
                                            <div class="progress-bar bg-warning" style="width: 100%"></div>
                                        </div>
                                        <span class="progress-description">
                                            Importação automática SIGEP
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Vídeo Tutorial -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-video"></i>
                            Tutorial de Instalação
                        </h5>
                    </div>
                    <div class="card-body text-center">
                        <div class="embed-responsive embed-responsive-16by9">
                            <iframe class="embed-responsive-item"
                                    src="https://www.youtube.com/embed/dQw4w9WgXcQ"
                                    allowfullscreen>
                            </iframe>
                        </div>
                        <p class="mt-2 text-muted">
                            <small>Vídeo tutorial passo a passo da instalação</small>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Suporte e FAQ -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-question-circle"></i>
                            Perguntas Frequentes
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="accordion" id="faqAccordion">
                            <div class="card">
                                <div class="card-header" id="faq1">
                                    <h2 class="mb-0">
                                        <button class="btn btn-link btn-block text-left" type="button" data-toggle="collapse" data-target="#collapse1">
                                            A extensão é segura?
                                        </button>
                                    </h2>
                                </div>
                                <div id="collapse1" class="collapse" data-parent="#faqAccordion">
                                    <div class="card-body">
                                        Sim! A extensão é desenvolvida internamente, só funciona no domínio do iPEN e não coleta dados pessoais.
                                    </div>
                                </div>
                            </div>

                            <div class="card">
                                <div class="card-header" id="faq2">
                                    <h2 class="mb-0">
                                        <button class="btn btn-link btn-block text-left" type="button" data-toggle="collapse" data-target="#collapse2">
                                            Posso desativar funções?
                                        </button>
                                    </h2>
                                </div>
                                <div id="collapse2" class="collapse" data-parent="#faqAccordion">
                                    <div class="card-body">
                                        Sim! Use o popup da extensão (ícone iPEN) para ativar/desativar cada função individualmente.
                                    </div>
                                </div>
                            </div>

                            <div class="card">
                                <div class="card-header" id="faq3">
                                    <h2 class="mb-0">
                                        <button class="btn btn-link btn-block text-left" type="button" data-toggle="collapse" data-target="#collapse3">
                                            Como atualizar a extensão?
                                        </button>
                                    </h2>
                                </div>
                                <div id="collapse3" class="collapse" data-parent="#faqAccordion">
                                    <div class="card-body">
                                        Baixe a nova versão e substitua a pasta. Ou use o script de atualização automática.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-headset"></i>
                            Suporte Técnico
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="callout callout-info">
                            <h5><i class="fas fa-info-circle"></i> Canais de Suporte</h5>
                            <p><strong>Email:</strong> suporte@empresa.com.br</p>
                            <p><strong>Telefone:</strong> Ramal 1234</p>
                            <p><strong>Horário:</strong> Seg-Sex, 8h-17h</p>
                        </div>

                        <div class="callout callout-warning mt-3">
                            <h5><i class="fas fa-exclamation-triangle"></i> Antes de Contatar</h5>
                            <ul class="mb-0">
                                <li>Verifique se o Chrome está atualizado</li>
                                <li>Tente reinstalar a extensão</li>
                                <li>Limpe o cache do navegador</li>
                            </ul>
                        </div>

                        <div class="text-center mt-3">
                            <button class="btn btn-primary" onclick="window.print()">
                                <i class="fas fa-print mr-2"></i>
                                Imprimir Guia
                            </button>
                        </div>

                        <div class="alert alert-info mt-3">
                            <small><strong>Acessibilidade:</strong> Pasta de rede: <code>\\10.40.88.200\extensions\ipenv2\</code></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</section>

<style>
.numbered-list {
    counter-reset: item;
    padding-left: 0;
}

.numbered-list li {
    counter-increment: item;
    list-style-type: none;
    position: relative;
    padding-left: 30px;
    margin-bottom: 8px;
}

.numbered-list li::before {
    content: counter(item);
    position: absolute;
    left: 0;
    top: 0;
    background: #007bff;
    color: white;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    text-align: center;
    line-height: 20px;
    font-size: 12px;
    font-weight: bold;
}
</style>

<script>
// Função para imprimir guia
function printGuide() {
    window.print();
}
</script>
