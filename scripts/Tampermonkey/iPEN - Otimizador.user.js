// ==UserScript==
// @name         iPEN - Otimizador Operacional
// @namespace    ipen
// @version      16.0
// @description  Dark Mode, Tablet-Friendly, Visualizador de Fotos e Status Reais.
// @author       Dev | SIGEP
// @match        https://www.sc.gov.br/sap/ipen/*
// @match        https://www.sc.gov.br/ipen/*
// @grant        none
// @run-at       document-start
// ==/UserScript==

(function () {
    'use strict';

    let processado = false;

    // 1. CSS PROFISSIONAL (DARK MODE & TABLET)
    const injetarCSS = () => {
        if (document.getElementById("tm-style-final-v2")) return;
        const style = document.createElement("style");
        style.id = "tm-style-final-v2";
        style.innerHTML = `
            /* Esconder lixo original */
            app-sidebar, thead, tfoot, hr, #button, body > table { display:none !important; }
            body.tm-applied > table { display:none !important; }

            body { margin: 0; padding: 0; background: #000 !important; color: #fff; font-family: 'Segoe UI', system-ui, sans-serif; }
            body.sidebar-active { margin-left: 260px !important; }

            /* SIDEBAR DARK FIXO */
            .tm-sidebar { position: fixed; top: 0; left: 0; width: 260px; height: 100vh; background: #111; color: #fff; z-index: 99999; overflow-y: auto; border-right: 1px solid #333; display: block !important; }
            .tm-brand { padding: 20px; text-align: center; border-bottom: 1px solid #333; }
            .tm-brand img { height: 30px; filter: brightness(0) invert(1); }
            .tm-menu { padding: 10px 0; }
            .tm-item { padding: 12px 20px; display: flex; align-items: center; gap: 12px; cursor: pointer; font-size: 14px; color: #aaa; transition: 0.2s; }
            .tm-item:hover { background: #222; color: #fff; }
            .tm-header { padding: 15px 20px 5px; font-size: 11px; text-transform: uppercase; color: #555; font-weight: bold; }
            .tm-submenu { background: #000; display: none; }
            .tm-open .tm-submenu { display: block; }
            .tm-arrow { margin-left: auto; font-size: 10px; }

            /* CABEÇALHO E BUSCA */
            .topo-controle {
                position: sticky; top: 0; background: rgba(0,0,0,0.95);
                padding: 15px 25px; z-index: 9998; border-bottom: 1px solid #333;
                display: flex; align-items: center; justify-content: space-between;
                backdrop-filter: blur(10px);
            }
            .titulo-cela { font-size: 24px; font-weight: 800; color: #007bff; }
            .busca-container { width: 40%; }
            .busca-container input {
                width: 100%; padding: 12px 20px; border-radius: 10px; border: 1px solid #444;
                background: #1c1c1e; color: #fff; font-size: 14px; outline: none;
            }
            .btn-imprimir { padding: 10px 25px; background: #007bff; color: #fff; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; }

            /* GRID DE CARDS - AJUSTADO PARA NÃO CORTAR TEXTO */
            #tm-root { padding: 20px; }
            .grid-cards { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }

            .card-preso {
                background: #1c1c1e; border-radius: 16px; padding: 20px;
                display: flex; flex-direction: column; align-items: center;
                border: 1px solid #333; transition: 0.3s;
            }
            .card-preso:hover { border-color: #007bff; transform: translateY(-5px); }

            .foto-container {
                width: 120px; height: 120px; border-radius: 50%;
                border: 4px solid #333; overflow: hidden;
                margin-bottom: 15px; cursor: pointer; background: #2c2c2e;
            }
            .foto-container img { width: 100%; height: 100%; object-fit: cover; }

            .status-tag { background: #007bff; color: #fff; font-size: 10px; font-weight: bold; padding: 4px 12px; border-radius: 20px; margin-bottom: 12px; text-transform: uppercase; }
            .nome-preso { font-size: 14px; font-weight: bold; text-align: center; color: #fff; text-transform: uppercase; margin-bottom: 15px; min-height: 35px; line-height: 1.3; }

            .info-box { width: 100%; font-size: 12px; color: #bbb; margin-bottom: 6px; display: flex; justify-content: space-between; gap: 10px; }
            .info-box b { color: #666; text-transform: uppercase; font-size: 10px; white-space: nowrap; }
            .info-box span { text-align: right; overflow: visible; white-space: normal; }
            .rj-val { color: #007bff; font-weight: 800; font-size: 15px; }
            .artigo-tag { margin-top: 10px; padding-top: 10px; border-top: 1px solid #333; color: #ff453a; font-size: 11px; text-align: center; font-weight: bold; width: 100%; }

            /* VISUALIZADOR */
            #tm-lightbox { position: fixed; inset: 0; background: rgba(0,0,0,0.9); z-index: 1000000; display: none; align-items: center; justify-content: center; cursor: pointer; }
            #tm-lightbox img { max-width: 90%; max-height: 90%; border-radius: 10px; border: 2px solid #444; }

            @media print {
                .tm-sidebar, .no-print, .topo-controle { display: none !important; }
                body { margin-left: 0 !important; background: #fff !important; color: #000 !important; }
                .grid-cards { grid-template-columns: repeat(2, 1fr) !important; }
                .card-preso { background: #fff; color: #000; border: 1px solid #ddd; page-break-inside: avoid; }
                .nome-preso, .info-box, .info-box span { color: #000; }
            }
        `;
        document.documentElement.appendChild(style);
    };

    // 2. FUNÇÕES DE SUPORTE
    window.abrirFotoGrande = (url) => {
        let box = document.getElementById("tm-lightbox");
        if (!box) {
            box = document.createElement("div");
            box.id = "tm-lightbox";
            box.innerHTML = `<img src="" id="tm-img-full">`;
            box.onclick = () => box.style.display = "none";
            document.body.appendChild(box);
        }
        document.getElementById("tm-img-full").src = url.replace(/w=\d+/, "w=1000");
        box.style.display = "flex";
    };

    window.executarBusca = (valor) => {
        const q = valor.toLowerCase();
        document.querySelectorAll('.card-preso').forEach(card => {
            card.style.display = card.innerText.toLowerCase().includes(q) ? "flex" : "none";
        });
    };

    // 3. CAPTURA E PROCESSAMENTO
    const processarRelatorio = () => {
        if (processado || !window.location.href.includes("Imprimir.asp")) return;

        const fotos = document.querySelectorAll('img[src*="visualizadorDeFotos.asp"]');
        if (fotos.length === 0) return;

        processado = true;
        const detentos = [];

        // Identificar Cela (SE-3)
        const cellData = document.querySelector('td[style*="background: #DFE8F6"]')?.innerText || "";
        const g = cellData.match(/GALERIA:\s*(\w+)/)?.[1] || "";
        const b = cellData.match(/BLOCO:\s*(\w+)/)?.[1] || "";
        const r = cellData.match(/RESIDÊNCIA:\s*(\d+)/)?.[1] || "";
        const codigoCela = (g || b || r) ? `${g}${b}-${r}` : "RELATÓRIO";

        fotos.forEach(img => {
            try {
                const tab = img.closest('table');
                if (!tab || !tab.innerText.includes("Nome:")) return;

                const txt = tab.innerText;
                let statusReal = "ALOCADO";
                let container = tab.closest('table').parentElement.closest('table');
                let header = container ? container.previousElementSibling : null;
                while(header && header.innerText.trim() === "") header = header.previousElementSibling;
                if (header) statusReal = header.innerText.split('\n')[0].trim();

                detentos.push({
                    status: statusReal,
                    nome: txt.match(/Nome:\s*(.*)/i)?.[1]?.trim() || "NÃO IDENTIFICADO",
                    rj: txt.match(/Prontuário:\s*(\d+)/i)?.[1] || "---",
                    mae: txt.match(/Mãe:\s*(.*)/i)?.[1]?.trim() || "NÃO INFORMADA",
                    nasc: txt.match(/Nascimento:\s*(.*)/i)?.[1]?.trim() || "---",
                    arts: txt.match(/Artigo\(s\):\s*(.*)/i)?.[1]?.trim() || "",
                    foto: img.src.replace(/w=\d+/, "w=300")
                });
            } catch (e) {}
        });

        // RECONSTRUÇÃO
        const containerRoot = document.createElement("div");
        containerRoot.id = "tm-root";

        let html = `
            <div class="topo-controle no-print">
                <div class="titulo-cela">${codigoCela}</div>
                <div class="busca-container">
                    <input type="text" placeholder="Insira iPEN ou Nome" oninput="executarBusca(this.value)">
                </div>
                <button class="btn-imprimir" onclick="window.print()">IMPRIMIR</button>
            </div>
            <div class="grid-cards">`;

        detentos.forEach(p => {
            html += `
                <div class="card-preso">
                    <div class="status-tag">${p.status}</div>
                    <div class="foto-container" onclick="abrirFotoGrande('${p.foto}')">
                        <img src="${p.foto}">
                    </div>
                    <div class="nome-preso">${p.nome}</div>
                    <div class="info-box"><b>Prontuário</b> <span class="rj-val">${p.rj}</span></div>
                    <div class="info-box"><b>Mãe</b> <span>${p.mae}</span></div>
                    <div class="info-box"><b>Nascimento</b> <span>${p.nasc}</span></div>
                    ${p.arts ? `<div class="artigo-tag">${p.arts}</div>` : ""}
                </div>`;
        });

        containerRoot.innerHTML = html + `</div>`;

        // Esconder o original SEM deletar (preserva a sidebar)
        document.body.querySelectorAll('table, hr, .FonteRelatorioCabUnidade1').forEach(el => el.style.setProperty('display', 'none', 'important'));
        document.body.appendChild(containerRoot);
        document.body.classList.add('tm-applied');
    };

    // 4. SIDEBAR (RESTAURADA)
    const criarSidebar = () => {
        if (document.getElementById("lte-sidebar") || window.self !== window.top) return;

        const aside = document.createElement("aside");
        aside.className = "tm-sidebar";
        aside.id = "lte-sidebar";
        aside.innerHTML = `
            <div class="tm-brand"><img src="https://www.sc.gov.br/sap/ipen/assets/icons/logo-ipen.png"></div>
            <div class="tm-menu">
                <div class="tm-item" onclick="location.href='/sap/ipen/inicio'">🏠 Início</div>
                <div class="tm-item" onclick="location.href='/sap/ipen/ipenv2'">🔗 iPEN V2</div>
                <div class="tm-header">Relatórios</div>
                <div class="tm-parent" onclick="this.classList.toggle('tm-open')">
                    <div class="tm-item">📊 Módulo Detentos <span class="tm-arrow">▼</span></div>
                    <div class="tm-submenu">
                        <div class="tm-item" id="btn-side-15">📄 Relatório 1-5</div>
                        <div class="tm-item" id="btn-side-18">📄 Relatório 1-8</div>
                    </div>
                </div>
            </div>`;

        document.body.prepend(aside);
        document.body.classList.add('sidebar-active');

        const u = localStorage.getItem('tm_unidade') || "8019";
        document.getElementById("btn-side-15").onclick = () => {
            location.href = `https://www.sc.gov.br/ipen/RelatorioIpen_004ReltorioPorSetor.asp?cd_Unidade=${u}`;
        };
        document.getElementById("btn-side-18").onclick = () => {
            window.open(`https://www.sc.gov.br/ipen/RelatorioIpen_028DetentosAlocadosAlfabeticaImprimir.asp?cd_Unidade=${u}&Unidades=undefined&cd_Ordenacao=1`, "_blank");
        };
    };

    // 5. MONITORAMENTO
    const main = () => {
        injetarCSS();
        criarSidebar();
        processarRelatorio();

        // Limpeza de avisos
        const av = document.getElementById("DivAvisoPendencias");
        if (av) av.remove();

        // Capturar Unidade se mudar
        const urlU = new URLSearchParams(window.location.search).get('cd_Unidade');
        if(urlU && urlU !== "0") localStorage.setItem('tm_unidade', urlU);
    };

    // Observer para carregar sidebar e processar relatórios dinâmicos
    const observer = new MutationObserver(main);
    observer.observe(document.documentElement, { childList: true, subtree: true });

})();
