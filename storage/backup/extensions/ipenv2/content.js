// O "Maestro" da Extensão
chrome.storage.local.get(['sidebarActive', 'relatorioActive', 'correcoesActive', 'coletaActive'], (res) => {

    // 1. Aplica Correções de Bug (se ativo ou primeira vez)
    if (res.correcoesActive !== false) {
        if (typeof initCorrecoes === "function") initCorrecoes();
    }

    // 2. Aplica Sidebar Moderna
    if (res.sidebarActive !== false) {
        if (typeof initSidebar === "function") initSidebar();
    }

    // 3. Aplica Transformação de Relatório
    if (res.relatorioActive !== false) {
        if (typeof initRelatorio15 === "function") initRelatorio15();
    }

    // 4. Aplica Coleta de Dados
    if (res.coletaActive !== false) {
        if (typeof initColetaDados === "function") initColetaDados();
    }
});
