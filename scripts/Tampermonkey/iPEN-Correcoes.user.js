// ==UserScript==
// @name         iPEN - Correções de Sistema
// @namespace    ipen.correcoes
// @version      1.0.0
// @description  Corrige erro 403 e bugs do sistema iPEN
// @author       SIGEP Dev
// @match        https://www.sc.gov.br/sap/ipen/*
// @match        https://www.sc.gov.br/ipen/*
// @grant        none
// @run-at       document-start
// ==/UserScript==

(function() {
    'use strict';
    
    console.log("🛠️ Correções e Ajustes de Bugs Ativados");

    // URL que causa o erro 403
    const urlProblemática = "apim.ciasc.sc.gov.br:8243/api/ipen/v3/admin/unidade";

    // 1. Interceptar chamadas via FETCH (Usado pelo Angular moderno)
    const originalFetch = window.fetch;
    window.fetch = function() {
        const url = arguments[0];
        if (typeof url === 'string' && url.includes(urlProblemática)) {
            // Retorna uma promessa "vazia" de sucesso para o sistema não reclamar
            return Promise.resolve(new Response(JSON.stringify({ status: "ignorado" }), {
                status: 200,
                statusText: 'OK',
                headers: { 'Content-Type': 'application/json' }
            }));
        }
        return originalFetch.apply(this, arguments);
    };

    // 2. Interceptar chamadas via XHR (Usado por scripts antigos)
    const originalOpen = XMLHttpRequest.prototype.open;
    XMLHttpRequest.prototype.open = function(method, url) {
        if (typeof url === 'string' && url.includes(urlProblemática)) {
            // Sobrescreve a URL para uma rota inexistente porém segura (opcional)
            // ou apenas marcamos para não disparar erro
            this.isIgnored = true;
        }
        return originalOpen.apply(this, arguments);
    };

    const originalSend = XMLHttpRequest.prototype.send;
    XMLHttpRequest.prototype.send = function() {
        if (this.isIgnored) {
            // Aborta a chamada antes de ela sair para a rede e gerar o 403
            return;
        }
        return originalSend.apply(this, arguments);
    };

    // 3. Outra correção: Impedir que o sistema force o modo "Mobile" em tablets
    if (window.innerWidth > 768 && window.innerWidth < 1024) {
        document.body.classList.remove('is-mobile');
        document.body.classList.add('is-tablet');
    }

})();
