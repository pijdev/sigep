function initRelatorio15() {
    console.log("📸 Relatório 1-5 Ativado");
// ==UserScript==
// @name         iPEN - Relatório 1-5 (com Foto)
// @namespace    ipen.relatorio.15
// @version      1.0
// @description  Transformação completa do relatório 1-5 para design Shadcn/UI com Status Reais.
// @author       Dev | SIGEP
// @match        https://www.sc.gov.br/ipen/RelatorioIpen_004_1ReltorioPorSetorImprimir.asp*
// @grant        none
// @run-at       document-start
// ==/UserScript==

(function () {
    'use strict';

    let processado = false;

    // 1. BLOQUEIO DE IMPRESSÃO AUTOMÁTICA (Regra de Negócio)
    window.print = () => { console.warn("Impressão automática bloqueada. Use a função do sistema."); };

    // 2. CSS DESIGN SYSTEM (SHADCN ZINC THEME)
    const injetarCSS = () => {
        if (document.getElementById("tm-relatorio-css")) return;
        const style = document.createElement("style");
        style.id = "tm-relatorio-css";
        style.innerHTML = `
            @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap');

            /* RESET E ESCONDER ORIGINAL */
            thead, tfoot, hr, #button { display:none !important; }
            body.tm-applied > table, body.tm-applied .FonteRelatorioCabUnidade1 { display:none !important; }

            /* FUNDO BLACK */
            body {
                margin: 0; padding: 0;
                background: #09090b !important;
                color: #fafafa !important;
                font-family: 'Inter', sans-serif !important;
                overflow-x: hidden;
            }

            /* DASHBOARD TOP BAR (Estatísticas) */
            .dashboard-header { padding: 30px 30px 10px 30px; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; }
            .stat-card { background: #09090b; border: 1px solid #27272a; padding: 20px; border-radius: 12px; }
            .stat-label { font-size: 11px; color: #71717a; font-weight: 700; text-transform: uppercase; margin-bottom: 5px; }
            .stat-value { font-size: 24px; font-weight: 800; color: #fff; }

            /* BARRA DE CONTROLES (BUSCA E STATUS) */
            .topo-controle {
                position: sticky; top: 0; background: rgba(9,9,11,0.8); padding: 20px 30px;
                z-index: 9998; border-bottom: 1px solid #27272a; backdrop-filter: blur(12px);
                display: flex; align-items: center; justify-content: space-between; gap: 20px;
            }
            .titulo-cela { font-size: 20px; font-weight: 800; color: #3b82f6; }
            .controles-group { display: flex; gap: 12px; flex-grow: 1; justify-content: flex-end; }

            .input-shadcn {
                background: #09090b; border: 1px solid #27272a; color: #fff;
                padding: 10px 15px; border-radius: 8px; font-size: 13px; outline: none; transition: 0.2;
            }
            .input-shadcn:focus { border-color: #3b82f6; }
            .busca-input { width: 300px; }

            /* GRID DE CARDS */
            #tm-root { padding: 10px 30px 40px 30px; }
            .grid-cards { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }

            .card-preso {
                background: #09090b; border-radius: 16px; padding: 25px;
                display: flex; flex-direction: column; align-items: center; text-align: center;
                border: 1px solid #27272a; transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            }
            .card-preso:hover { border-color: #3f3f46; transform: translateY(-4px); background: #0c0c0e; }

            /* FOTO REDONDA */
            .foto-moldura {
                width: 120px; height: 120px; border-radius: 50%; border: 4px solid #18181b;
                overflow: hidden; margin-bottom: 20px; cursor: pointer; transition: 0.3s;
                background: #000; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.5);
            }
            .foto-moldura:hover { border-color: #3b82f6; }
            .foto-moldura img { width: 100%; height: 100%; object-fit: cover; }

            /* LABELS E STATUS */
            .status-badge { background: #1e1e21; color: #3b82f6; font-size: 9px; font-weight: 800; padding: 4px 12px; border-radius: 6px; margin-bottom: 15px; text-transform: uppercase; border: 1px solid #27272a; }
            .nome-preso { font-size: 14px; font-weight: 700; color: #fff; text-transform: uppercase; margin-bottom: 15px; min-height: 38px; line-height: 1.3; }
            .info-box { width: 100%; font-size: 12px; color: #a1a1aa; margin-bottom: 7px; display: flex; justify-content: space-between; }
            .info-box b { color: #52525b; text-transform: uppercase; font-size: 9px; }
            .rj-val { color: #3b82f6; font-weight: 800; font-size: 15px; }
            .arts-tag { margin-top: 15px; padding-top: 12px; border-top: 1px solid #18181b; color: #ef4444; font-size: 10px; font-weight: 700; width: 100%; }

            /* LIGHTBOX (VISUALIZADOR) */
            #tm-viewer { position: fixed; inset: 0; background: rgba(0,0,0,0.95); z-index: 1000000; display: none; align-items: center; justify-content: center; cursor: zoom-out; backdrop-filter: blur(8px); }
            #tm-viewer img { max-width: 90%; max-height: 90%; border-radius: 12px; border: 1px solid #27272a; }

            /* REGRA DE IMPRESSÃO (VOLTAR AO BRANCO ORIGINAL) */
            @media print {
                body { background: #fff !important; color: #000 !important; padding: 0 !important; margin: 0 !important; }
                #tm-root, #tm-viewer, .dashboard-header, .topo-controle { display: none !important; }
                table { display: table !important; width: 100% !important; background: #fff !important; }
                td, th, b, span, p { color: #000 !important; }
                .FonteRelatorioCabUnidade1 { display: block !important; }
            }
        `;
        document.documentElement.appendChild(style);
    };

    // 3. FUNÇÕES GLOBAIS (MODAL E FILTRO)
    window.tmVerFoto = (url) => {
        let box = document.getElementById("tm-viewer");
        if (!box) {
            box = document.createElement("div");
            box.id = "tm-viewer";
            box.innerHTML = `<img src="" id="tm-img-full">`;
            box.onclick = () => box.style.display = "none";
            document.body.appendChild(box);
        }
        document.getElementById("tm-img-full").src = url.replace(/w=\d+/, "w=1000");
        box.style.display = "flex";
    };

    window.tmFiltro = () => {
        const q = document.getElementById('tm-search').value.toLowerCase();
        const s = document.getElementById('tm-select-status').value;
        document.querySelectorAll('.card-preso').forEach(card => {
            const matchTxt = card.innerText.toLowerCase().includes(q);
            const matchStatus = (s === "TODOS" || card.getAttribute('data-status') === s);
            card.style.display = (matchTxt && matchStatus) ? "flex" : "none";
        });
    };

    // 4. MOTOR DE EXTRAÇÃO INTELIGENTE (Captura o Status Real)
    const extrairDados = () => {
        // Verifica se é a página de impressão
        if (processado || !window.location.href.includes("Imprimir.asp")) return;

        const tabelasPresos = Array.from(document.querySelectorAll('table[bgcolor="#EFF4FC"]'));
        if (tabelasPresos.length === 0) return;

        processado = true;
        const detentos = [];
        let statusAtual = "Na Cela";
        let mapaStatus = new Set(["TODOS"]);

        const cellData = document.querySelector('td[style*="background: #DFE8F6"]')?.innerText || "";
        const idCela = (cellData.match(/GALERIA:\s*(\w+)/)?.[1] || "") + (cellData.match(/BLOCO:\s*(\w+)/)?.[1] || "") + "-" + (cellData.match(/RESIDÊNCIA:\s*(\d+)/)?.[1] || "");

        // Varredura Top-Down para associar Status aos Detentos
        const nodes = document.body.querySelectorAll('.Fonte4, table[bgcolor="#EFF4FC"]');
        nodes.forEach(el => {
            if (el.classList.contains('Fonte4')) {
                statusAtual = el.innerText.trim();
                mapaStatus.add(statusAtual);
            } else {
                const txt = el.innerText;
                detentos.push({
                    status: statusAtual,
                    nome: txt.match(/Nome:\s*(.*)/i)?.[1]?.trim() || "NÃO LOCALIZADO",
                    rj: txt.match(/Prontuário:\s*(\d+)/i)?.[1] || "---",
                    mae: txt.match(/Mãe:\s*(.*)/i)?.[1]?.trim() || "NÃO INFORMADA",
                    nasc: txt.match(/Nascimento:\s*(.*)/i)?.[1]?.trim() || "---",
                    arts: txt.match(/Artigo\(s\):\s*(.*)/i)?.[1]?.trim() || "",
                    foto: el.querySelector('img')?.src.replace(/w=\d+/, "w=300")
                });
            }
        });

        // 5. CONSTRUÇÃO DA NOVA UI
        const root = document.createElement("div");
        root.id = "tm-root";

        let options = "";
        mapaStatus.forEach(st => { options += `<option value="${st}">${st}</option>`; });

        root.innerHTML = `
            <div class="dashboard-header no-print">
                <div class="stat-card"><div class="stat-label">Total Internos</div><div class="stat-value">${detentos.length}</div></div>
                <div class="stat-card"><div class="stat-label">Cela Selecionada</div><div class="stat-value" style="color:#3b82f6">${idCela}</div></div>
            </div>
            <div class="topo-controle no-print">
                <div class="titulo-cela">RELATÓRIO <span style="color:#fff">1-5</span></div>
                <div class="controles-group">
                    <select id="tm-select-status" class="input-shadcn" onchange="window.tmFiltro()">${options}</select>
                    <input type="text" id="tm-search" class="input-shadcn busca-input" placeholder="Insira iPEN ou Nome" oninput="window.tmFiltro()">
                </div>
            </div>
            <div class="grid-cards">
                ${detentos.map(p => `
                    <div class="card-preso" data-status="${p.status}">
                        <div class="status-badge">${p.status}</div>
                        <div class="foto-moldura" onclick="window.tmVerFoto('${p.foto}')">
                            <img src="${p.foto}">
                        </div>
                        <div class="nome-preso">${p.nome}</div>
                        <div class="info-box"><b>Prontuário</b> <span class="rj-val">${p.rj}</span></div>
                        <div class="info-box"><b>Mãe</b> <span>${p.mae.substring(0,25)}</span></div>
                        <div class="info-box"><b>Nascimento</b> <span>${p.nasc}</span></div>
                        ${p.arts ? `<div class="arts-tag">Artigos: ${p.arts}</div>` : ""}
                    </div>
                `).join('')}
            </div>`;

        document.body.appendChild(root);
        document.body.classList.add('tm-applied');
    };

    // 6. MONITORAMENTO CONTÍNUO
    const run = () => {
        injetarCSS();
        extrairDados();
    };

    const observer = new MutationObserver(run);
    observer.observe(document.documentElement, { childList: true, subtree: true });

})();
}
