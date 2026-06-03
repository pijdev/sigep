// ==UserScript==
// @name         iPEN - Sidebar Shadcn UI
// @namespace    ipen.sidebar
// @version      1.0.0
// @description  Sidebar exclusiva baseada em Shadcn/UI (Zinc 950). Minimalista e funcional.
// @author       SIGEP Dev
// @match        https://www.sc.gov.br/sap/ipen/*
// @match        https://www.sc.gov.br/ipen/*
// @grant        none
// @run-at       document-start
// ==/UserScript==

(function () {
    'use strict';

    console.log("🎨 Sidebar Ativada");

    const LOGO_URL = "https://www.sc.gov.br/sap/ipen/assets/icons/logo-ipen.png";

    // 1. ESTILO SHADCN (ZINC 950)
    const injetarCSS = () => {
        if (document.getElementById("tm-sidebar-shadcn-css")) return;
        const style = document.createElement("style");
        style.id = "tm-sidebar-shadcn-css";
        style.innerHTML = `
            @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

            /* Esconder a barra original do sistema */
            app-sidebar { display:none !important; }

            /* Ajuste do corpo para abrir espaço para a sidebar */
            body {
                margin: 0; padding: 0;
                font-family: 'Inter', sans-serif !important;
            }

            /* Classe que empurra o conteúdo para a direita */
            body.tm-sidebar-push {
                padding-left: 260px !important;
            }

            /* Resiliência para Mobile/Tablet: Sidebar não ocupa a tela toda */
            @media (max-width: 1024px) {
                body.tm-sidebar-push { padding-left: 0 !important; }
                .tm-sidebar { transform: translateX(-100%); transition: transform 0.3s ease; }
                .tm-sidebar.open { transform: translateX(0); }
            }

            /* SIDEBAR DESIGN SHADCN (ZINC 950) */
            .tm-sidebar {
                position: fixed; top: 0; left: 0;
                width: 260px !important;
                min-width: 260px !important;
                max-width: 260px !important;
                height: 100vh;
                background-color: #09090b; /* Zinc 950 */
                border-right: 1px solid #27272a; /* Zinc 800 */
                z-index: 999999;
                display: flex;
                flex-direction: column;
                color: #fafafa;
            }

            /* LOGO AREA */
            .tm-brand {
                height: 60px;
                display: flex;
                align-items: center;
                padding: 0 24px;
                border-bottom: 1px solid #18181b;
            }
            .tm-brand img {
                height: 22px;
                filter: brightness(0) invert(1); /* Logo branca */
            }

            /* MENU ITEMS */
            .tm-nav-container {
                flex: 1;
                padding: 20px 12px;
                overflow-y: auto;
            }
            .tm-nav-label {
                font-size: 11px;
                font-weight: 600;
                color: #71717a; /* Zinc 400 */
                text-transform: uppercase;
                letter-spacing: 0.05em;
                padding: 0 12px 8px;
            }
            .tm-nav-item {
                display: flex;
                align-items: center;
                gap: 12px;
                padding: 8px 12px;
                font-size: 14px;
                font-weight: 500;
                color: #a1a1aa; /* Zinc 400 */
                border-radius: 6px;
                cursor: pointer;
                transition: all 0.2s;
                margin-bottom: 2px;
                text-decoration: none !important;
            }
            .tm-nav-item:hover {
                background-color: #18181b; /* Zinc 900 */
                color: #ffffff;
            }
            .tm-nav-item i {
                font-style: normal;
                font-size: 16px;
            }

            /* SUBMENU */
            .tm-parent-group { margin-bottom: 4px; }
            .tm-submenu {
                display: none;
                padding-left: 28px;
                margin-top: 2px;
            }
            .tm-parent-group.open .tm-submenu {
                display: block;
            }
            .tm-parent-group.open > .tm-nav-item {
                color: #ffffff;
            }
            .tm-arrow {
                margin-left: auto;
                font-size: 10px;
                transition: transform 0.2s;
                opacity: 0.5;
            }
            .tm-parent-group.open .tm-arrow {
                transform: rotate(90deg);
            }

            /* RODAPÉ DA SIDEBAR */
            .tm-footer {
                padding: 16px;
                border-top: 1px solid #18181b;
            }
            .tm-unit-box {
                background-color: #18181b;
                border: 1px solid #27272a;
                border-radius: 8px;
                padding: 10px;
                text-align: center;
            }
            .tm-unit-label { font-size: 10px; color: #71717a; font-weight: 700; text-transform: uppercase; }
            .tm-unit-value { font-size: 12px; color: #3b82f6; font-weight: 600; margin-top: 2px; }
        `;
        document.documentElement.appendChild(style);
    };

    // 2. CAPTURA DE UNIDADE (PERSISTENTE)
    const getUnidade = () => {
        const text = document.body ? document.body.innerText : "";
        const match = text.match(/UNIDADE DE TRABALHO ATUAL\s*(\d+)/i) || text.match(/UNIDADE:\s*(\d+)/i);
        let unidade = match ? match[1] : (localStorage.getItem('tm_unidade_persistente') || "8019");
        if (match) localStorage.setItem('tm_unidade_persistente', unidade);
        return unidade;
    };

    // 3. NAVEGAÇÃO SINCORNIZADA (IFRAME V2)
    const abrirLinkIpen = (url) => {
        const iframe = document.getElementById("iPenV2");
        if (iframe) {
            iframe.src = url;
        } else {
            sessionStorage.setItem('tm_jump_target', url);
            window.location.href = "/sap/ipen/ipenv2";
        }
    };

    // 4. CONSTRUÇÃO DO DOM DA SIDEBAR
    const construirSidebar = () => {
        if (document.getElementById("lte-sidebar") || window.self !== window.top) return;

        const unidade = getUnidade();
        document.body.classList.add('tm-sidebar-push');

        const aside = document.createElement("aside");
        aside.className = "tm-sidebar";
        aside.id = "lte-sidebar";
        aside.innerHTML = `
            <div class="tm-brand">
                <img src="${LOGO_URL}" alt="iPEN Logo">
            </div>
            <div class="tm-nav-container">
                <div class="tm-nav-label">Navegação</div>
                <a class="tm-nav-item" href="/sap/ipen/inicio"><i>🏠</i> Início</a>
                <a class="tm-nav-item" href="/sap/ipen/ipenv2"><i>🔗</i> iPEN V2</a>

                <div style="height: 20px;"></div>

                <div class="tm-nav-label">Serviços</div>
                <div class="tm-parent-group" id="group-relatorios">
                    <div class="tm-nav-item">
                        <i>📊</i> Relatórios <span class="tm-arrow">▶</span>
                    </div>
                    <div class="tm-submenu">
                        <div class="tm-nav-item" id="btn-rel-15">📄 Relatório 1-5</div>
                        <div class="tm-nav-item" id="btn-rel-18">📄 Relatório 1-8</div>
                    </div>
                </div>
            </div>
            <div class="tm-footer">
                <div class="tm-unit-box">
                    <div class="tm-unit-label">Unidade Atual</div>
                    <div class="tm-unit-value">${unidade}</div>
                </div>
            </div>
        `;

        document.body.prepend(aside);

        // --- EVENTOS ---

        // Toggle do Submenu
        document.getElementById("group-relatorios").onclick = function(e) {
            this.classList.toggle("open");
        };

        // Relatório 1-5 (No Iframe ou redirect)
        document.getElementById("btn-rel-15").onclick = (e) => {
            e.stopPropagation();
            abrirLinkIpen(`https://www.sc.gov.br/ipen/RelatorioIpen_004ReltorioPorSetor.asp?cd_Unidade=${unidade}`);
        };

        // Relatório 1-8 (Nova Aba)
        document.getElementById("btn-rel-18").onclick = (e) => {
            e.stopPropagation();
            window.open(`https://www.sc.gov.br/ipen/RelatorioIpen_028DetentosAlocadosAlfabeticaImprimir.asp?cd_Unidade=${unidade}&Unidades=undefined&cd_Ordenacao=1`, "_blank");
        };
    };

    // 5. LOOP DE MONITORAMENTO (F5 E AJUSTES)
    const monitorar = () => {
        injetarCSS();
        construirSidebar();

        // REMOÇÃO DO AVISO DE PENDÊNCIAS (SEMPRE ATIVO)
        const aviso = document.getElementById("DivAvisoPendencias");
        if (aviso) aviso.remove();

        // Sincronia de Iframe (Redirecionamento Inteligente)
        if (window.location.pathname.includes("ipenv2")) {
            const jumpUrl = sessionStorage.getItem('tm_jump_target');
            const iframe = document.getElementById("iPenV2");
            if (jumpUrl && iframe) {
                iframe.src = jumpUrl;
                sessionStorage.removeItem('tm_jump_target');
            }
        }
    };

    // Inicia o observador de mudanças na página
    const observer = new MutationObserver(monitorar);
    observer.observe(document.documentElement, { childList: true, subtree: true });

})();
