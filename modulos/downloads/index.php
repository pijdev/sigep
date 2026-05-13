<?php
if (session_status() === PHP_SESSION_NONE) session_start();
?>

<section class="content">
    <div class="container-fluid">

        <!-- Cabeçalho -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-download"></i>
                            Downloads
                        </h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lista de Downloads -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <!-- iPEN - Correções de Sistema -->
                            <div class="col-md-6 col-lg-4">
                                <div class="card card-outline card-success">
                                    <div class="card-header">
                                        <h5 class="card-title">
                                            <i class="fas fa-tools"></i>
                                            iPEN - Correções
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <p class="card-text">
                                            Corrige erro 403 e bugs do sistema iPEN.
                                            Interceptor de chamadas e correções de interface.
                                        </p>
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-check text-success"></i> Evita erro 403 na API</li>
                                            <li><i class="fas fa-check text-success"></i> Corrige detecção de tablet</li>
                                            <li><i class="fas fa-check text-success"></i> Interceptor FETCH/XHR</li>
                                        </ul>
                                    </div>
                                    <div class="card-footer">
                                        <a href="/scripts/iPEN-Correcoes.user.js"
                                           class="btn btn-success btn-block"
                                           onclick="installTampermonkey('/scripts/iPEN-Correcoes.user.js'); return false;">
                                            <i class="fas fa-download"></i>
                                            Instalar Script
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- iPEN - Sidebar Shadcn -->
                            <div class="col-md-6 col-lg-4">
                                <div class="card card-outline card-primary">
                                    <div class="card-header">
                                        <h5 class="card-title">
                                            <i class="fas fa-columns"></i>
                                            iPEN - Sidebar
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <p class="card-text">
                                            Sidebar moderna baseada em Shadcn/UI com tema Zinc 950.
                                            Substitui a sidebar original do sistema.
                                        </p>
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-check text-success"></i> Design Shadcn/UI moderno</li>
                                            <li><i class="fas fa-check text-success"></i> Tema Zinc 950 escuro</li>
                                            <li><i class="fas fa-check text-success"></i> Navegação otimizada</li>
                                        </ul>
                                    </div>
                                    <div class="card-footer">
                                        <a href="/scripts/iPEN-Sidebar.user.js"
                                           class="btn btn-primary btn-block"
                                           onclick="installTampermonkey('/scripts/iPEN-Sidebar.user.js'); return false;">
                                            <i class="fas fa-download"></i>
                                            Instalar Script
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- iPEN - Relatório 1-5 -->
                            <div class="col-md-6 col-lg-4">
                                <div class="card card-outline card-info">
                                    <div class="card-header">
                                        <h5 class="card-title">
                                            <i class="fas fa-file-image"></i>
                                            iPEN - Relatório 1-5
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <p class="card-text">
                                            Transformação completa do relatório 1-5 com design moderno.
                                            Cards com fotos e filtros inteligentes.
                                        </p>
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-check text-success"></i> Cards Clover modernos</li>
                                            <li><i class="fas fa-check text-success"></i> Filtros e busca</li>
                                            <li><i class="fas fa-check text-success"></i> Visualizador de fotos</li>
                                        </ul>
                                    </div>
                                    <div class="card-footer">
                                        <a href="/scripts/iPEN-Relatorio15.user.js"
                                           class="btn btn-info btn-block"
                                           onclick="installTampermonkey('/scripts/iPEN-Relatorio15.user.js'); return false;">
                                            <i class="fas fa-download"></i>
                                            Instalar Script
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- iPEN - AutoImport 1-8 -->
                            <div class="col-md-6 col-lg-4">
                                <div class="card card-outline card-warning">
                                    <div class="card-header">
                                        <h5 class="card-title">
                                            <i class="fas fa-robot"></i>
                                            iPEN - AutoImport 1-8
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <p class="card-text">
                                            Importação automática do relatório 1-8 para SIGEP.
                                            Zero intervenção humana, timing inteligente.
                                        </p>
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-check text-success"></i> Importação automática</li>
                                            <li><i class="fas fa-check text-success"></i> Timing inteligente (>1h)</li>
                                            <li><i class="fas fa-check text-success"></i> Fallback clipboard</li>
                                        </ul>
                                    </div>
                                    <div class="card-footer">
                                        <a href="/scripts/iPEN-AutoImport.user.js"
                                           class="btn btn-warning btn-block"
                                           onclick="installTampermonkey('/scripts/iPEN-AutoImport.user.js'); return false;">
                                            <i class="fas fa-download"></i>
                                            Instalar Script
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- iPEN v2 - Extensão Completa -->
                            <div class="col-md-6 col-lg-4">
                                <div class="card card-outline card-dark">
                                    <div class="card-header bg-gradient-dark">
                                        <h5 class="card-title text-white">
                                            <i class="fas fa-rocket"></i>
                                            iPEN v2 - Completa
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <p class="card-text">
                                            Extensão Chrome completa com TODAS as funcionalidades.
                                            Sidebar, correções, relatórios e AutoImport.
                                        </p>
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-check text-success"></i> 4 módulos em 1</li>
                                            <li><i class="fas fa-check text-success"></i> Interface unificada</li>
                                            <li><i class="fas fa-check text-success"></i> Instalação fácil</li>
                                        </ul>
                                    </div>
                                    <div class="card-footer">
                                        <a href="/paginas/extensao_ipen.php"
                                           class="btn btn-dark btn-block">
                                            <i class="fas fa-external-link-alt"></i>
                                            Ver Página Completa
                                        </a>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Instruções -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-info-circle"></i>
                            Como Instalar
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Passo 1: Instalar Tampermonkey</h6>
                                <ol>
                                    <li>Instale a extensão <strong>Tampermonkey</strong> no Microsoft Edge</li>
                                    <li>Edge: <a href="https://microsoftedge.microsoft.com/addons/detail/tampermonkey/iikmkjmpaadaobahmlepeloendndfphd" target="_blank">Microsoft Edge Add-ons</a></li>
                                    <li>Chrome: <a href="https://chrome.google.com/webstore/detail/tampermonkey/dhdgffkkebhmkfjojejmpbldmpobfkfo" target="_blank">Chrome Web Store</a></li>
                                    <li>Firefox: <a href="https://addons.mozilla.org/pt-BR/firefox/addon/tampermonkey/" target="_blank">Firefox Add-ons</a></li>
                                </ol>
                            </div>
                            <div class="col-md-6">
                                <h6>Passo 2: Instalar o Script</h6>
                                <ol>
                                    <li>Clique no botão "Instalar Script"</li>
                                    <li>O script abrirá em nova aba</li>
                                    <li>Siga as instruções do modal que aparece</li>
                                    <li>Arraste o script para o Tampermonkey ou use a URL</li>
                                </ol>
                                <div class="alert alert-info mt-2">
                                    <i class="fas fa-info-circle"></i>
                                    <strong>Tablet:</strong> Use o método de URL manual se arrastar não funcionar
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</section>

<script>
// Função para instalação direta do script
function installTampermonkey(scriptUrl) {
    // Abre o script diretamente em nova aba
    // O usuário precisará arrastar para Tampermonkey ou usar o menu
    const newWindow = window.open(scriptUrl, '_blank');

    // Mostra instruções detalhadas
    showInstallInstructions(scriptUrl);
}

// Mostra instruções de instalação manual
function showInstallInstructions(scriptUrl) {
    const modal = document.createElement('div');
    modal.className = 'modal fade show';
    modal.style.display = 'block';
    modal.style.backgroundColor = 'rgba(0,0,0,0.5)';
    modal.innerHTML = `
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-info-circle"></i>
                        Como Instalar o Script
                    </h5>
                    <button type="button" class="close" onclick="this.closest('.modal').remove()">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6><i class="fas fa-mouse-pointer"></i> Método 1: Arrastar e Soltar</h6>
                            <ol>
                                <li>Abra a extensão Tampermonkey</li>
                                <li>Clique em "Utilitários" → "Instalar script a partir de..."</li>
                                <li>Arraste a aba do script para a janela do Tampermonkey</li>
                                <li>Confirme a instalação</li>
                            </ol>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="fas fa-keyboard"></i> Método 2: URL Manual</h6>
                            <ol>
                                <li>Abra o Tampermonkey</li>
                                <li>Clique em "Utilitários" → "Instalar script a partir de URL"</li>
                                <li>Copie a URL da aba que abriu</li>
                                <li>Cole no campo e instale</li>
                            </ol>
                        </div>
                    </div>
                    <div class="alert alert-info mt-3">
                        <i class="fas fa-lightbulb"></i>
                        <strong>Dica:</strong> A URL do script é: <code>${window.location.origin}${scriptUrl}</code>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="this.closest('.modal').remove()">Entendi</button>
                </div>
            </div>
        </div>
    `;

    document.body.appendChild(modal);
}

// Função para obter ID do Tampermonkey (mantida para referência)
function getTampermonkeyId() {
    // IDs comuns do Tampermonkey
    const tmIds = {
        chrome: 'dhdgffkkebhmkfjojejmpbldmpobfkfo',
        firefox: '{e4a8a97b-f2ed-450b-b12d-ee082ba24821}',
        edge: 'iikmkjmpaadaobahmlepeloendndfphd'
    };

    if (navigator.userAgent.includes('Chrome') && !navigator.userAgent.includes('Edg')) {
        return tmIds.chrome;
    } else if (navigator.userAgent.includes('Firefox')) {
        return tmIds.firefox.replace(/[{}]/g, '');
    } else if (navigator.userAgent.includes('Edg')) {
        return tmIds.edge;
    }

    return 'dhdgffkkebhmkfjojejmpbldmpobfkfo'; // Default Chrome
}

// Inicialização - não verifica mais automaticamente para evitar bloqueio
document.addEventListener('DOMContentLoaded', function() {
    console.log('Página de downloads carregada');
});
</script>
