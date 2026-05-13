document.addEventListener('DOMContentLoaded', () => {
    const sideBtn = document.getElementById('sidebar-toggle');
    const relBtn = document.getElementById('relatorio-toggle');
    const corrBtn = document.getElementById('correcoes-toggle');
    const coletaBtn = document.getElementById('coleta-toggle');

    // Carregar configurações salvas (Default é TRUE)
    chrome.storage.local.get(['sidebarActive', 'relatorioActive', 'correcoesActive', 'coletaActive'], (res) => {
        sideBtn.checked = res.sidebarActive !== false;
        relBtn.checked = res.relatorioActive !== false;
        corrBtn.checked = res.correcoesActive !== false;
        coletaBtn.checked = res.coletaActive !== false;
    });

    // Salvar mudanças
    sideBtn.onchange = () => chrome.storage.local.set({ sidebarActive: sideBtn.checked });
    relBtn.onchange = () => chrome.storage.local.set({ relatorioActive: relBtn.checked });
    corrBtn.onchange = () => chrome.storage.local.set({ correcoesActive: corrBtn.checked });
    coletaBtn.onchange = () => chrome.storage.local.set({ coletaActive: coletaBtn.checked });

    // Listener para status updates do AutoImport
    window.addEventListener('autoimportStatusUpdate', (event) => {
        const { text, type, details } = event.detail;
        updateAutoImportStatus(text, type, details);
    });

    // Função para atualizar status visual
    function updateAutoImportStatus(text, type, details) {
        const statusBadge = document.getElementById('autoimport-status');
        if (!statusBadge) return;

        const statusText = statusBadge.querySelector('.status-text');
        const statusDetails = statusBadge.querySelector('.status-details');

        if (statusText) {
            statusText.textContent = text;
            statusText.className = `status-text status-${type}`;
        }

        if (statusDetails && details) {
            statusDetails.textContent = details;
        }
    }

    // Inicializa status ao abrir o popup
    setTimeout(() => {
        // Envia mensagem para o content script obter status
        chrome.tabs.query({ active: true, currentWindow: true }, (tabs) => {
            if (tabs[0]) {
                chrome.tabs.sendMessage(tabs[0].id, { action: 'getAutoImportStatus' }, (response) => {
                    if (response && response.status) {
                        updateAutoImportStatus(response.status.text, response.status.type, response.status.details);
                    }
                });
            }
        });
    }, 100);

    // Evento do botão "Forçar Importação"
    const forceImportBtn = document.getElementById('force-import-btn');
    if (forceImportBtn) {
        forceImportBtn.addEventListener('click', () => {
            // Desabilita botão e mostra loading
            forceImportBtn.disabled = true;
            forceImportBtn.classList.add('loading');
            forceImportBtn.innerHTML = '<i class="fas fa-spinner"></i> Processando...';

            // Envia mensagem para forçar importação
            chrome.tabs.query({ active: true, currentWindow: true }, (tabs) => {
                if (tabs[0]) {
                    chrome.tabs.sendMessage(tabs[0].id, { action: 'forceImport' }, (response) => {
                        // Reabilita botão após 3 segundos (independente da resposta)
                        setTimeout(() => {
                            forceImportBtn.disabled = false;
                            forceImportBtn.classList.remove('loading');
                            forceImportBtn.innerHTML = '<i class="fas fa-play"></i> Forçar Importação';
                        }, 3000);
                    });
                }
            });
        });
    }
});
