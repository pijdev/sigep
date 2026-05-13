/**
 * Módulo Início - JavaScript
 * Funcionalidades específicas do dashboard inicial
 */

// Função para atualizar dinamicamente a previsão de retorno da reconstrução
function atualizarPrevisaoRetorno() {
  const elemento = document.getElementById("previsao-retorno");
  if (!elemento) return;

  const hoje = new Date();
  const dataRetorno = new Date("2026-03-29T18:00:00"); // 29/03 às 18h (alguns dias)

  // Verificar se a data de retorno já passou
  if (hoje >= dataRetorno) {
    elemento.textContent = "Sistema reconstruído e normalizado";
    elemento.style.color = "#28a745"; // Verde
    return;
  }

  // Calcular diferença em dias
  const diffDias = Math.ceil((dataRetorno - hoje) / (1000 * 60 * 60 * 24));

  if (diffDias === 0) {
    elemento.textContent = "Hoje - Final da tarde";
    elemento.style.color = "#ffc107"; // Amarelo/Atenção
  } else if (diffDias === 1) {
    elemento.textContent = "Amanhã - Final da tarde";
    elemento.style.color = "#fd7e14"; // Laranja
  } else if (diffDias <= 3) {
    elemento.textContent = `${diffDias} dias (reconstrução em andamento)`;
    elemento.style.color = "#fd7e14"; // Laranja
  } else {
    elemento.textContent = `${diffDias} dias`;
    elemento.style.color = "#6c757d"; // Cinza
  }
}

// Atualizar ao carregar a página
$(document).ready(function () {
  atualizarPrevisaoRetorno();

  // Atualizar a cada hora caso o usuário mantenha a página aberta
  setInterval(atualizarPrevisaoRetorno, 60000); // 1 minuto
});

function escHtml(value) {
  const s = value === null || value === undefined ? "" : String(value);
  return s
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}

function isBlank(value) {
  return value === null || value === undefined || String(value).trim() === "";
}

function formatLocal(galeria, bloco, cela) {
  const g = isBlank(galeria) ? "" : String(galeria).trim();
  const b = isBlank(bloco) ? "" : String(bloco).trim();
  const c = isBlank(cela) ? "" : String(cela).trim();
  if (!g || !c) return "-";
  return `${g}${b}-${c}`;
}

function formatCurrencyBR(value) {
  const n = Number(value);
  if (!Number.isFinite(n)) return "-";
  return n.toLocaleString("pt-BR", { style: "currency", currency: "BRL" });
}

function formatDateBR(value) {
  if (isBlank(value)) return "-";
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return escHtml(value);
  return d.toLocaleDateString("pt-BR");
}

function formatDateTimeBR(value) {
  if (isBlank(value)) return "-";
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return escHtml(value);
  return d.toLocaleString("pt-BR");
}

function normalizePecasList(pecas) {
  if (!pecas) return [];
  if (Array.isArray(pecas)) {
    // Pode ser [{item, quantidade}] ou strings etc.
    return pecas.map((p) => {
      if (p && typeof p === "object") {
        const name = p.item || p.nome || p.peca || p.tipo || JSON.stringify(p);
        const qtd = p.quantidade ?? p.qtd ?? p.qtde ?? null;
        return {
          nome: String(name),
          quantidade: qtd !== null ? Number(qtd) : null,
        };
      }
      return { nome: String(p), quantidade: null };
    });
  }
  if (typeof pecas === "object") {
    return Object.entries(pecas).map(([k, v]) => {
      const qtd = v === null || v === undefined ? null : Number(v);
      return { nome: String(k), quantidade: Number.isFinite(qtd) ? qtd : null };
    });
  }
  return [{ nome: String(pecas), quantidade: null }];
}

function normalizeText(value) {
  const s = value === null || value === undefined ? "" : String(value);
  try {
    return s
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "")
      .toLowerCase()
      .trim();
  } catch (_) {
    return s.toLowerCase().trim();
  }
}

function formatEletronicoNome(e) {
  const tipo = isBlank(e?.tipo_item) ? "" : String(e.tipo_item).trim();
  const custom = isBlank(e?.nome_item_personalizado)
    ? ""
    : String(e.nome_item_personalizado).trim();
  const customNorm = normalizeText(custom);
  const customOk =
    !!custom && customNorm !== "nao se aplica" && customNorm !== "n/a";

  // Priorizar tipo_item; usar nome custom apenas quando tipo for "Outros" ou não vier preenchido.
  if (tipo && tipo !== "Outros") return tipo;
  if (customOk) return custom;
  return tipo || "Item";
}

// Atualizar hora atual
function updateCurrentTime() {
  const now = new Date();
  const timeString = now.toLocaleTimeString("pt-BR", {
    hour: "2-digit",
    minute: "2-digit",
    second: "2-digit",
  });

  const timeElement = document.getElementById("current-time");
  if (timeElement) {
    timeElement.textContent = timeString;
  }
}

// Atualizar data atual
function updateCurrentDate() {
  const now = new Date();
  const options = {
    weekday: "long",
    year: "numeric",
    month: "long",
    day: "numeric",
  };

  const dateString = now.toLocaleDateString("pt-BR", options);

  const dateElement = document.getElementById("current-date");
  if (dateElement) {
    dateElement.textContent = dateString;
  }
}

function initInicioClock() {
  const timeElement = document.getElementById("current-time");
  const dateElement = document.getElementById("current-date");
  if (!timeElement && !dateElement) return;

  updateCurrentTime();
  updateCurrentDate();

  if (window.__sigepInicioClockTimer)
    clearInterval(window.__sigepInicioClockTimer);
  if (window.__sigepInicioDateTimer)
    clearInterval(window.__sigepInicioDateTimer);

  window.__sigepInicioClockTimer = setInterval(updateCurrentTime, 1000);
  window.__sigepInicioDateTimer = setInterval(updateCurrentDate, 60000);
}

// Animação de contagem para números
function animateNumbers() {
  const numberElements = document.querySelectorAll(".stat-number");

  numberElements.forEach((element) => {
    const finalValue = parseInt(element.textContent);
    let currentValue = 0;
    const increment = Math.ceil(finalValue / 50);

    const timer = setInterval(() => {
      currentValue += increment;
      if (currentValue >= finalValue) {
        currentValue = finalValue;
        clearInterval(timer);
      }
      element.textContent = currentValue;
    }, 30);
  });
}

// Verificar status do sistema
function checkSystemStatus() {
  const statusElements = document.querySelectorAll(".system-status");

  statusElements.forEach((element) => {
    const status = element.dataset.status;
    const icon = element.querySelector(".status-icon");

    if (status === "online") {
      icon.classList.add("text-success");
      icon.classList.remove("text-danger", "text-warning");
    } else if (status === "warning") {
      icon.classList.add("text-warning");
      icon.classList.remove("text-success", "text-danger");
    } else {
      icon.classList.add("text-danger");
      icon.classList.remove("text-success", "text-warning");
    }
  });
}

// Exportar funções para uso global
window.InicioModule = {
  updateCurrentTime,
  updateCurrentDate,
  animateNumbers,
  checkSystemStatus,
};

function initInicioBusca() {
  const inputBusca = document.getElementById("inicioBuscaInterno");
  const resultados = document.getElementById("inicioBuscaResultados");
  const btn = document.getElementById("inicioBtnDossie");
  const btnPrint = document.getElementById("inicioBtnImprimirDossie");
  const hint = document.getElementById("inicioBuscaHint");
  const selecionado = document.getElementById("inicioBuscaSelecionado");
  const modalEl = document.getElementById("modalDossieInterno");
  const dossieBody = document.getElementById("inicioDossieBody");

  if (
    !inputBusca ||
    !resultados ||
    !btn ||
    !btnPrint ||
    !hint ||
    !selecionado ||
    !modalEl ||
    !dossieBody
  )
    return;

  let selectedIpen = null;
  let selectedLabel = "";
  let debounceTimer = null;
  let lastReqId = 0;

  const hideResults = () => {
    resultados.style.display = "none";
    resultados.innerHTML = "";
  };

  const setSelected = (ipen, label) => {
    selectedIpen = ipen;
    selectedLabel = label || "";
    btn.disabled = !(selectedIpen && selectedIpen > 0);
    selecionado.textContent = selectedLabel
      ? "Selecionado: " + selectedLabel
      : "";
  };

  const renderResults = (items) => {
    if (!items || items.length === 0) {
      resultados.innerHTML = `<div class="list-group-item text-muted small">Nenhum interno encontrado.</div>`;
      resultados.style.display = "block";
      return;
    }

    resultados.innerHTML = items
      .map((it) => {
        const nome =
          it.nome_social && String(it.nome_social).trim()
            ? it.nome_social
            : it.nome;
        const apelido =
          it.apelido && String(it.apelido).trim()
            ? ` <span class="text-muted">(${escHtml(it.apelido)})</span>`
            : "";
        const local = formatLocal(it.galeria, it.bloco, it.res);
        const badges = [
          it.regalia === "S" || it.regalia_galeria === "S"
            ? '<span class="badge badge-danger mr-1">Regalia</span>'
            : "",
          it.lgbt === "S"
            ? '<span class="badge badge-info mr-1">LGBT</span>'
            : "",
        ]
          .filter(Boolean)
          .join("");

        const label = `${nome} (IPEN ${it.ipen})`;

        return `
                <button type="button"
                        class="list-group-item list-group-item-action inicio-busca-item"
                        data-ipen="${escHtml(it.ipen)}"
                        data-label="${escHtml(label)}">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="mr-2">
                            <div class="font-weight-bold">${escHtml(nome)}${apelido}</div>
                            <div class="text-muted small">${local ? "Local: " + local : "Local: -"}</div>
                        </div>
                        <div class="text-right">
                            ${badges}
                            <span class="badge badge-light">IPEN ${escHtml(it.ipen)}</span>
                        </div>
                    </div>
                </button>
            `;
      })
      .join("");
    resultados.style.display = "block";
  };

  const fetchBusca = async (termo) => {
    const reqId = ++lastReqId;
    hint.textContent = "Buscando...";
    try {
      const url = `/modulos/inicio/inicio_api.php?action=search_interno&termo=${encodeURIComponent(termo)}`;
      const res = await fetch(url, { credentials: "same-origin" });
      const json = await res.json();
      if (reqId !== lastReqId) return;
      if (json && json.success) renderResults(json.resultados || []);
      else renderResults([]);
    } catch (e) {
      if (reqId !== lastReqId) return;
      resultados.innerHTML = `<div class="list-group-item text-danger small">Erro ao buscar internos.</div>`;
      resultados.style.display = "block";
    } finally {
      if (reqId === lastReqId)
        hint.textContent = "Digite ao menos 2 caracteres.";
    }
  };

  const openDossie = async (ipen) => {
    if (!ipen || ipen <= 0) return;
    dossieBody.innerHTML = `
            <div class="text-center p-5 text-muted">
                <i class="fas fa-spinner fa-spin fa-2x mb-3"></i>
                <div>Carregando...</div>
            </div>
        `;
    btnPrint.disabled = true;
    $("#modalDossieInterno").modal("show");

    try {
      const url = `/modulos/inicio/inicio_api.php?action=get_dossie&ipen=${encodeURIComponent(ipen)}`;
      const res = await fetch(url, { credentials: "same-origin" });
      const json = await res.json();

      if (!json || !json.success) {
        dossieBody.innerHTML = `<div class="alert alert-danger">Falha ao carregar dossiê.</div>`;
        return;
      }

      window.__sigepInicioLastDossie = json;
      btnPrint.disabled = false;

      const interno = json.interno || {};
      const nome =
        interno.nome_social && String(interno.nome_social).trim()
          ? interno.nome_social
          : interno.nome;

      const local = formatLocal(interno.galeria, interno.bloco, interno.res);

      const lgbt = interno.lgbt || "N";
      const lgbtText = lgbt === "S" ? "Sim" : "Não";
      const nomeSocialVazio = isBlank(interno.nome_social);

      let nomeSocialHtml = escHtml(interno.nome_social || "");
      if (nomeSocialVazio) {
        if (lgbt === "S") {
          nomeSocialHtml = `
                        <span class="text-warning font-weight-bold">Não cadastrado</span>
                        <span class="badge badge-warning ml-1">Atenção</span>
                        <div class="text-muted small mt-1">Nem todo interno LGBT usa nome social, mas vale conferir.</div>
                    `;
        } else {
          nomeSocialHtml = `<span class="text-muted">Não se aplica.</span>`;
        }
      }

      const badges = [
        interno.regalia === "S" || interno.regalia_galeria === "S"
          ? '<span class="badge badge-danger mr-1">Regalia</span>'
          : "",
        lgbt === "S" ? '<span class="badge badge-info mr-1">LGBT</span>' : "",
        json.ctc_ativo
          ? '<span class="badge badge-success mr-1">CTC ativo</span>'
          : "",
        Array.isArray(json.md_historico) &&
        json.md_historico.some((m) => m.status === "Ativa")
          ? '<span class="badge badge-warning mr-1">MD ativa</span>'
          : "",
      ]
        .filter(Boolean)
        .join("");

      const laboralAtivo = json.laboral_ativo;
      const laboralTxt = laboralAtivo
        ? `${laboralAtivo.estabelecimento || "-"}`
        : "-";

      const kitNome =
        json.kit_tipo && json.kit_tipo.nome
          ? json.kit_tipo.nome
          : interno.kit
            ? "Kit #" + interno.kit
            : "-";

      const eleNaCela = Array.isArray(json.eletronicos_na_cela)
        ? json.eletronicos_na_cela
        : [];
      const livrosItens = Array.isArray(json.livros_itens)
        ? json.livros_itens
        : [];
      const cartasRecentes = Array.isArray(json.cartas_recentes)
        ? json.cartas_recentes
        : [];
      const ctcHist = Array.isArray(json.ctc_historico)
        ? json.ctc_historico
        : [];
      const mdHist = Array.isArray(json.md_historico) ? json.md_historico : [];
      const escoltas = Array.isArray(json.escoltas) ? json.escoltas : [];
      const condicoes = Array.isArray(json.condicoes_especiais)
        ? json.condicoes_especiais
        : [];
      const condicoesAtivas = Array.isArray(json.condicoes_especiais_ativas)
        ? json.condicoes_especiais_ativas
        : [];
      const roupasItens = Array.isArray(json.roupas_itens)
        ? json.roupas_itens
        : [];
      const cosmeticosItens = Array.isArray(json.cosmeticos_itens)
        ? json.cosmeticos_itens
        : [];
      const doacoes = Array.isArray(json.doacoes) ? json.doacoes : [];
      const doacoesItens =
        json.doacoes_itens && typeof json.doacoes_itens === "object"
          ? json.doacoes_itens
          : {};
      const alteracoesInterno = Array.isArray(json.alteracoes_interno)
        ? json.alteracoes_interno
        : [];
      const alteracoesLaboral = Array.isArray(json.alteracoes_laboral)
        ? json.alteracoes_laboral
        : [];
      const peculioPix = Array.isArray(json.peculio_pix)
        ? json.peculio_pix
        : [];
      const peculioTrabalho = Array.isArray(json.peculio_trabalho)
        ? json.peculio_trabalho
        : [];
      const roupariaPecas = normalizePecasList(json.rouparia_civil_pecas);

      const renderList = (items, renderItem, emptyText) => {
        if (!items || items.length === 0) {
          return `<div class="text-muted small">${escHtml(emptyText || "Sem registros.")}</div>`;
        }
        return `<ul class="list-group list-group-flush">${items.map(renderItem).join("")}</ul>`;
      };

      const htmlEle = renderList(
        eleNaCela.slice(0, 20),
        (e) => {
          const nomeItem = formatEletronicoNome(e);
          const mm = e.marca_modelo ? " - " + e.marca_modelo : "";
          return `<li class="list-group-item px-0 py-2">
                        <div class="d-flex justify-content-between">
                            <div class="font-weight-bold">${escHtml(nomeItem)}${escHtml(mm)}</div>
                            <span class="badge badge-light">${escHtml(e.situacao)}</span>
                        </div>
                        <div class="text-muted small">${escHtml([e.cor, e.estado_conservacao].filter(Boolean).join(" | ") || "-")}</div>
                    </li>`;
        },
        "Nenhum eletrônico cadastrado na cela.",
      );

      const htmlLivros = renderList(
        livrosItens.slice(0, 15),
        (l) => {
          const titulo = l.titulo_livro || "-";
          const autor = l.autor ? " · " + l.autor : "";
          const entregue = l.data_entrega_interno
            ? '<span class="badge badge-success ml-2">Entregue</span>'
            : '<span class="badge badge-secondary ml-2">Pendente</span>';
          return `<li class="list-group-item px-0 py-2">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="mr-2">
                                <div class="font-weight-bold">${escHtml(titulo)}${escHtml(autor)}</div>
                                <div class="text-muted small">${escHtml(l.estado_conservacao || "")}</div>
                            </div>
                            <div>${entregue}</div>
                        </div>
                    </li>`;
        },
        "Nenhum recebimento de livros.",
      );

      const htmlCartas = renderList(
        cartasRecentes.slice(0, 8),
        (c) => {
          const st = c.status_censura || "-";
          const dt = c.recebido_em
            ? new Date(c.recebido_em).toLocaleDateString("pt-BR")
            : "";
          return `<li class="list-group-item px-0 py-2">
                        <div class="d-flex justify-content-between">
                            <div>${escHtml(c.tipo_movimentacao || "Carta")} · <span class="text-muted">${escHtml(c.correspondente_nome || "")}</span></div>
                            <span class="badge badge-light">${escHtml(st)}</span>
                        </div>
                        <div class="text-muted small">${dt ? "Recebida em " + dt : ""}</div>
                    </li>`;
        },
        "Sem cartas registradas.",
      );

      const htmlCTC = renderList(
        ctcHist.slice(0, 6),
        (c) => {
          const dt = c.data_ctc
            ? new Date(c.data_ctc).toLocaleDateString("pt-BR")
            : "-";
          const st = c.status || "";
          return `<li class="list-group-item px-0 py-2">
                        <div class="d-flex justify-content-between">
                            <div>${dt} · ${escHtml(c.resultado || "-")}</div>
                            <span class="badge badge-${st === "Ativo" ? "success" : st === "Aguardando" ? "warning" : "secondary"}">${escHtml(st || "-")}</span>
                        </div>
                    </li>`;
        },
        "Sem CTC registrada.",
      );

      const htmlMD = renderList(
        mdHist.slice(0, 6),
        (m) => {
          const di = m.data_inicio
            ? new Date(m.data_inicio).toLocaleDateString("pt-BR")
            : "-";
          const df = m.data_fim
            ? new Date(m.data_fim).toLocaleDateString("pt-BR")
            : "-";
          const st = m.status || "-";
          return `<li class="list-group-item px-0 py-2">
                        <div class="d-flex justify-content-between">
                            <div>${di} -> ${df}</div>
                            <span class="badge badge-${st === "Ativa" ? "warning" : st === "Concluida" ? "success" : "secondary"}">${escHtml(st)}</span>
                        </div>
                        <div class="text-muted small text-truncate">${escHtml(m.motivo || "")}</div>
                    </li>`;
        },
        "Sem MD registrada.",
      );

      const htmlEscoltas = renderList(
        escoltas.slice(0, 6),
        (e) => {
          const d = e.data_cadastro
            ? new Date(e.data_cadastro).toLocaleDateString("pt-BR")
            : "-";
          return `<li class="list-group-item px-0 py-2">
                        <div class="d-flex justify-content-between">
                            <div>${d} · ${escHtml(e.destino || "-")}</div>
                            <span class="badge badge-light">${escHtml(e.status || "-")}</span>
                        </div>
                    </li>`;
        },
        "Sem escoltas registradas.",
      );

      const htmlCondicoes = renderList(
        condicoes.slice(0, 8),
        (c) => {
          const di = formatDateBR(c.data_inicio);
          const df = formatDateBR(c.data_fim);
          const st = c.status || "-";
          const badge =
            st === "Ativa"
              ? "warning"
              : st === "Concluida"
                ? "success"
                : "secondary";
          return `<li class="list-group-item px-0 py-2">
                        <div class="d-flex justify-content-between">
                            <div class="font-weight-bold">${escHtml(c.tipo || "-")}</div>
                            <span class="badge badge-${badge}">${escHtml(st)}</span>
                        </div>
                        <div class="text-muted small">${escHtml(di)} -> ${escHtml(df)}</div>
                    </li>`;
        },
        "Sem condições especiais.",
      );

      const htmlRoupas = renderList(
        roupasItens.slice(0, 12),
        (r) => {
          const dt = formatDateTimeBR(r.data_recebimento);
          const entregue = r.data_entrega_interno
            ? '<span class="badge badge-success ml-2">Entregue</span>'
            : '<span class="badge badge-secondary ml-2">Pendente</span>';
          const detalhe = [
            r.detalhes,
            r.entregue_por_tipo ? "Por: " + r.entregue_por_tipo : null,
          ]
            .filter(Boolean)
            .join(" · ");
          return `<li class="list-group-item px-0 py-2">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="mr-2">
                                <div class="font-weight-bold">${escHtml(r.item || "-")} <span class="text-muted">x${escHtml(r.quantidade || 0)}</span></div>
                                <div class="text-muted small">${escHtml(dt)}${detalhe ? " · " + escHtml(detalhe) : ""}</div>
                            </div>
                            <div>${entregue}</div>
                        </div>
                    </li>`;
        },
        "Sem recebimentos de roupas.",
      );

      const htmlCosmeticos = renderList(
        cosmeticosItens.slice(0, 12),
        (r) => {
          const dt = formatDateTimeBR(r.data_recebimento);
          const entregue = r.data_entrega_interno
            ? '<span class="badge badge-success ml-2">Entregue</span>'
            : '<span class="badge badge-secondary ml-2">Pendente</span>';
          const detalhe = [
            r.detalhes,
            r.entregue_por_tipo ? "Por: " + r.entregue_por_tipo : null,
          ]
            .filter(Boolean)
            .join(" · ");
          return `<li class="list-group-item px-0 py-2">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="mr-2">
                                <div class="font-weight-bold">${escHtml(r.item || "-")} <span class="text-muted">x${escHtml(r.quantidade || 0)}</span></div>
                                <div class="text-muted small">${escHtml(dt)}${detalhe ? " · " + escHtml(detalhe) : ""}</div>
                            </div>
                            <div>${entregue}</div>
                        </div>
                    </li>`;
        },
        "Sem recebimentos de cosméticos.",
      );

      const htmlRoupariaCivil = (() => {
        if (!roupariaPecas || roupariaPecas.length === 0) {
          return `<div class="text-muted small">Sem rouparia civil cadastrada.</div>`;
        }
        const items = roupariaPecas
          .slice(0, 40)
          .map((p) => {
            const qtd =
              p.quantidade !== null && Number.isFinite(p.quantidade)
                ? ` <span class="text-muted">x${escHtml(p.quantidade)}</span>`
                : "";
            return `<li class="list-group-item px-0 py-2 d-flex justify-content-between">
                        <span>${escHtml(p.nome)}</span>
                        <span>${qtd}</span>
                    </li>`;
          })
          .join("");
        return `<ul class="list-group list-group-flush">${items}</ul>`;
      })();

      const htmlDoacoes = renderList(
        doacoes.slice(0, 8),
        (d) => {
          const dt = formatDateTimeBR(d.data_doacao);
          const role =
            Number(d.id_doador) === Number(interno.ipen)
              ? "Doador"
              : "Receptor";
          const termo =
            String(d.termo_assinado) === "1" || d.termo_assinado === 1
              ? '<span class="badge badge-success ml-1">Termo</span>'
              : '<span class="badge badge-secondary ml-1">Sem termo</span>';
          const localRec =
            d.tipo_receptor === "CELA"
              ? formatLocal(
                  d.galeria_receptor,
                  d.bloco_receptor,
                  d.cela_receptor,
                )
              : d.id_receptor
                ? "IPEN " + d.id_receptor
                : "-";
          const itens =
            doacoesItens[String(d.id)] || doacoesItens[Number(d.id)] || [];
          const itensTxt =
            Array.isArray(itens) && itens.length
              ? itens
                  .slice(0, 4)
                  .map((i) => i.tipo_item || "Item")
                  .join(", ")
              : "";

          // Verificar se o interno foi receptor para mostrar itens clicáveis
          const isReceptor = Number(d.id_receptor) === Number(interno.ipen);
          const itensClicaveis =
            isReceptor && Array.isArray(itens) && itens.length
              ? itens
                  .slice(0, 4)
                  .map(
                    (i) =>
                      `<span class="badge badge-info cursor-pointer" onclick="mostrarModalDoacao(${d.id}, '${escHtml(i.tipo_item || "Item")}', '${escHtml(i.marca_modelo || "")}', '${escHtml(i.cor || "")}')" style="cursor: pointer; text-decoration: underline;" title="Clique para ver detalhes">${escHtml(i.tipo_item || "Item")}</span>`,
                  )
                  .join(", ")
              : itensTxt;

          const termoBtn =
            String(d.termo_assinado) === "1" || d.termo_assinado === 1
              ? `<button class="btn btn-outline-success btn-xs ml-2" onclick="imprimirTermoDoacao(${d.id})" title="Imprimir termo"><i class="fas fa-print"></i></button>`
              : "";

          return `<li class="list-group-item px-0 py-2">
                        <div class="d-flex justify-content-between">
                            <div class="font-weight-bold">${escHtml(role)} · ${escHtml(localRec)}</div>
                            <div><span class="badge badge-info">${escHtml(Array.isArray(itens) ? itens.length : 0)}</span>${termo}${termoBtn}</div>
                        </div>
                        <div class="text-muted small">${escHtml(dt)}${itensClicaveis ? " · " + itensClicaveis : ""}</div>
                    </li>`;
        },
        "Sem doações registradas.",
      );

      const htmlAlteracoes = (title, items) =>
        renderList(
          items.slice(0, 8),
          (a) => {
            const dt = formatDateTimeBR(a.data_alteracao);
            const op = a.operacao || "";
            return `<li class="list-group-item px-0 py-2">
                        <div class="d-flex justify-content-between">
                            <div class="font-weight-bold">${escHtml(a.campo || "-")}</div>
                            <span class="badge badge-light">${escHtml(op)}</span>
                        </div>
                        <div class="text-muted small">${escHtml(dt)}</div>
                    </li>`;
          },
          `Sem alterações em ${title}.`,
        );

      const htmlPeculio = (() => {
        const pixTop = peculioPix[0] || null;
        const trabTop = peculioTrabalho[0] || null;
        return `
                    <div class="small">
                        <div class="d-flex justify-content-between"><span class="text-muted">Forma:</span><span>${escHtml(interno.forma_pagamento || "-")}</span></div>
                        <div class="d-flex justify-content-between"><span class="text-muted">Pix (último):</span><span>${pixTop ? escHtml(pixTop.mes_referencia) + " · " + escHtml(formatCurrencyBR(pixTop.valor)) : "-"}</span></div>
                        <div class="d-flex justify-content-between"><span class="text-muted">Trabalho (último):</span><span>${trabTop ? escHtml(trabTop.mes_referencia) + " · " + escHtml(formatCurrencyBR(trabTop.valor)) : "-"}</span></div>
                    </div>
                `;
      })();

      dossieBody.innerHTML = `
                <div class="inicio-dossie-top mb-3">
                    <div class="d-flex justify-content-between flex-wrap">
                        <div class="mr-2">
                            <h4 class="mb-1">${escHtml(nome)} <small class="text-muted">IPEN ${escHtml(interno.ipen)}</small></h4>
                            <div class="text-muted small">
                                <i class="fas fa-map-marker-alt mr-1"></i> ${escHtml(local)}
                                <span class="mx-2">|</span>
                                <i class="fas fa-info-circle mr-1"></i> Situação: ${escHtml(interno.situacao || "-")}
                            </div>
                            <div class="mt-2">${badges}</div>
                        </div>
                        <div class="text-right mt-2 mt-md-0">
                            <a href="#" class="btn btn-outline-primary btn-sm" onclick="loadPage('/paginas/internos_painel.php?ipen=${escHtml(interno.ipen)}', 'Painel de Internos', 'TI'); return false;">
                                <i class="fas fa-th mr-1"></i> Abrir no painel
                            </a>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-6">
                        <div class="callout callout-info">
                            <h6 class="mb-2"><i class="fas fa-user mr-2"></i>Dados</h6>
                            <div class="row small">
                                <div class="col-6 text-muted">Nome cadastro</div>
                                <div class="col-6">${escHtml(interno.nome || "-")}</div>
                                <div class="col-6 text-muted">Nome social</div>
                                <div class="col-6">${nomeSocialHtml}</div>
                                <div class="col-6 text-muted">Apelido</div>
                                <div class="col-6">${escHtml(interno.apelido || "-")}</div>
                                <div class="col-6 text-muted">LGBT</div>
                                <div class="col-6">${escHtml(lgbtText)}</div>
                            </div>
                        </div>

                        <div class="callout callout-success">
                            <h6 class="mb-2"><i class="fas fa-briefcase mr-2"></i>Trabalho</h6>
                            <div class="small">
                                <div><span class="text-muted">Laboral ativo:</span> ${escHtml(laboralTxt)}</div>
                                <div class="mt-1"><span class="text-muted">Situação:</span> ${escHtml(interno.situacao || "-")}</div>
                            </div>
                        </div>

                        <div class="callout callout-danger">
                            <h6 class="mb-2"><i class="fas fa-box-open mr-2"></i>Kit e regalia</h6>
                            <div class="small">
                                <div><span class="text-muted">Kit:</span> ${escHtml(kitNome)} <span class="text-muted">(tam. ${escHtml(interno.tamanho_kit || "-")})</span></div>
                                <div class="mt-1"><span class="text-muted">Regalia:</span> ${escHtml(interno.regalia === "S" || interno.regalia_galeria === "S" ? "Sim" : "Não")}</div>
                                ${interno.regalia_setor ? `<div class="mt-1"><span class="text-muted">Setor:</span> ${escHtml(interno.regalia_setor)}</div>` : ""}
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card card-outline card-info mb-3">
                            <div class="card-header py-2">
                                <h3 class="card-title text-sm mb-0"><i class="fas fa-tv mr-2"></i>Eletrônicos na cela <span class="badge badge-info ml-1">${eleNaCela.length}</span></h3>
                            </div>
                            <div class="card-body py-2">${htmlEle}</div>
                        </div>

                        <div class="card card-outline card-secondary mb-3">
                            <div class="card-header py-2">
                                <h3 class="card-title text-sm mb-0"><i class="fas fa-book mr-2"></i>Livros <span class="badge badge-secondary ml-1">${escHtml(json.livros_total || 0)}</span></h3>
                            </div>
                            <div class="card-body py-2">${htmlLivros}</div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-4">
                        <div class="card card-outline card-primary mb-3">
                            <div class="card-header py-2">
                                <h3 class="card-title text-sm mb-0"><i class="fas fa-envelope mr-2"></i>Cartas <span class="badge badge-primary ml-1">${escHtml(json.cartas_total || 0)}</span></h3>
                            </div>
                            <div class="card-body py-2">${htmlCartas}</div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card card-outline card-success mb-3">
                            <div class="card-header py-2">
                                <h3 class="card-title text-sm mb-0"><i class="fas fa-clipboard-check mr-2"></i>CTC</h3>
                            </div>
                            <div class="card-body py-2">${htmlCTC}</div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card card-outline card-warning mb-3">
                            <div class="card-header py-2">
                                <h3 class="card-title text-sm mb-0"><i class="fas fa-gavel mr-2"></i>MD</h3>
                            </div>
                            <div class="card-body py-2">${htmlMD}</div>
                        </div>
                    </div>
                </div>

                <div class="card card-outline card-dark mb-0">
                    <div class="card-header py-2">
                        <h3 class="card-title text-sm mb-0"><i class="fas fa-shield-alt mr-2"></i>Escoltas</h3>
                    </div>
                    <div class="card-body py-2">${htmlEscoltas}</div>
                </div>

                <div class="row mt-3">
                    <div class="col-lg-6">
                        <div class="card card-outline card-warning mb-3">
                            <div class="card-header py-2">
                                <h3 class="card-title text-sm mb-0"><i class="fas fa-user-shield mr-2"></i>Condições especiais <span class="badge badge-warning ml-1">${condicoesAtivas.length}</span></h3>
                            </div>
                            <div class="card-body py-2">${htmlCondicoes}</div>
                        </div>

                        <div class="card card-outline card-secondary mb-3">
                            <div class="card-header py-2">
                                <h3 class="card-title text-sm mb-0"><i class="fas fa-tshirt mr-2"></i>Recebimento de roupas <span class="badge badge-secondary ml-1">${escHtml(json.roupas_total || 0)}</span></h3>
                            </div>
                            <div class="card-body py-2">${htmlRoupas}</div>
                        </div>

                        <div class="card card-outline card-secondary mb-0">
                            <div class="card-header py-2">
                                <h3 class="card-title text-sm mb-0"><i class="fas fa-pump-soap mr-2"></i>Recebimento de cosméticos <span class="badge badge-secondary ml-1">${escHtml(json.cosmeticos_total || 0)}</span></h3>
                            </div>
                            <div class="card-body py-2">${htmlCosmeticos}</div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card card-outline card-info mb-3">
                            <div class="card-header py-2">
                                <h3 class="card-title text-sm mb-0"><i class="fas fa-user-tag mr-2"></i>Rouparia civil</h3>
                            </div>
                            <div class="card-body py-2">${htmlRoupariaCivil}</div>
                        </div>

                        <div class="card card-outline card-info mb-3">
                            <div class="card-header py-2">
                                <h3 class="card-title text-sm mb-0"><i class="fas fa-hand-holding-heart mr-2"></i>Doações de eletrônicos <span class="badge badge-info ml-1">${doacoes.length}</span></h3>
                            </div>
                            <div class="card-body py-2">${htmlDoacoes}</div>
                        </div>

                        <div class="card card-outline card-light mb-3">
                            <div class="card-header py-2">
                                <h3 class="card-title text-sm mb-0"><i class="fas fa-history mr-2"></i>Últimas alterações</h3>
                            </div>
                            <div class="card-body py-2">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="text-muted small mb-2">Interno</div>
                                        ${htmlAlteracoes("interno", alteracoesInterno)}
                                    </div>
                                    <div class="col-md-6">
                                        <div class="text-muted small mb-2">Laboral</div>
                                        ${htmlAlteracoes("laboral", alteracoesLaboral)}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card card-outline card-success mb-0">
                            <div class="card-header py-2">
                                <h3 class="card-title text-sm mb-0"><i class="fas fa-wallet mr-2"></i>Pecúlio</h3>
                            </div>
                            <div class="card-body py-2">${htmlPeculio}</div>
                        </div>
                    </div>
                </div>
            `;
    } catch (e) {
      dossieBody.innerHTML = `<div class="alert alert-danger">Erro ao carregar dossiê.</div>`;
    }
  };

  inputBusca.addEventListener("input", (e) => {
    const termo = String(e.target.value || "").trim();

    setSelected(null, "");

    if (debounceTimer) clearTimeout(debounceTimer);
    if (termo.length < 2) {
      hideResults();
      hint.textContent = "Digite ao menos 2 caracteres.";
      return;
    }

    debounceTimer = setTimeout(() => fetchBusca(termo), 250);
  });

  resultados.addEventListener("click", (e) => {
    const btnItem = e.target.closest(".inicio-busca-item");
    if (!btnItem) return;
    const ipen = parseInt(btnItem.getAttribute("data-ipen") || "0", 10);
    const label = btnItem.getAttribute("data-label") || "";
    setSelected(ipen, label);
    hideResults();
  });

  btn.addEventListener("click", async () => {
    if (selectedIpen && selectedIpen > 0) {
      openDossie(selectedIpen);
      return;
    }

    const termo = String(inputBusca.value || "").trim();
    const numeric = termo.replace(/\D/g, "");
    const ipen = numeric ? parseInt(numeric, 10) : 0;
    if (ipen > 0) {
      openDossie(ipen);
      return;
    }

    alert("Selecione um interno na lista antes de procurar.");
  });

  // Fechar resultados ao clicar fora
  if (!window.__sigepInicioOutsideClick) {
    window.__sigepInicioOutsideClick = true;
    document.addEventListener("click", (e) => {
      const wrap = document.getElementById("inicioBuscaResultados");
      const input = document.getElementById("inicioBuscaInterno");
      if (!wrap || !input) return;
      if (wrap.contains(e.target) || input.contains(e.target)) return;
      wrap.style.display = "none";
    });
  }

  // Reset ao fechar modal
  $("#modalDossieInterno")
    .off("hidden.bs.modal")
    .on("hidden.bs.modal", () => {
      dossieBody.innerHTML = `
            <div class="text-center p-5 text-muted">
                <i class="fas fa-spinner fa-spin fa-2x mb-3"></i>
                <div>Carregando...</div>
            </div>
        `;
      btnPrint.disabled = true;
      window.__sigepInicioLastDossie = null;
    });

  btnPrint.addEventListener("click", () => {
    const d = window.__sigepInicioLastDossie;
    if (!d || !d.success) return;
    imprimirDossieA4(d);
  });
}

function initInicioModule() {
  initInicioClock();
  initInicioBusca();
  initInicioCharts();
}

async function ensureChartJsLoaded() {
  if (window.Chart) return true;
  if (window.__sigepChartJsLoading) return window.__sigepChartJsLoading;

  window.__sigepChartJsLoading = new Promise((resolve) => {
    const existing = document.querySelector("script[data-sigep-chartjs]");
    if (existing) {
      existing.addEventListener("load", () => resolve(true));
      existing.addEventListener("error", () => resolve(false));
      return;
    }

    const script = document.createElement("script");
    script.src =
      "https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js";
    script.async = true;
    script.setAttribute("data-sigep-chartjs", "1");
    script.onload = () => resolve(true);
    script.onerror = () => resolve(false);
    document.head.appendChild(script);
  });

  return window.__sigepChartJsLoading;
}

function destroyInicioCharts() {
  const inst = window.__sigepInicioChartInstances || {};
  Object.values(inst).forEach((c) => {
    try {
      c.destroy();
    } catch (_) {}
  });
  window.__sigepInicioChartInstances = {};
}

async function initInicioCharts() {
  const dataWrap = window.sigepInicioChartsData;
  const data = dataWrap && dataWrap.charts ? dataWrap.charts : null;
  if (!data) return;

  const elSitu = document.getElementById("inicioChartSituacao");
  const elEle = document.getElementById("inicioChartEletronicos");
  const elEsc = document.getElementById("inicioChartEscoltas");
  const elEcl = document.getElementById("inicioChartEclusa");
  if (!elSitu && !elEle && !elEsc && !elEcl) return;

  const ok = await ensureChartJsLoaded();
  if (!ok || !window.Chart) return;

  destroyInicioCharts();

  // AdminLTE/Bootstrap palette-ish
  const palette = {
    primary: "#007bff",
    info: "#17a2b8",
    success: "#28a745",
    warning: "#ffc107",
    danger: "#dc3545",
    secondary: "#6c757d",
    dark: "#343a40",
  };

  try {
    window.Chart.defaults.font.family =
      "'Source Sans Pro', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif";
    window.Chart.defaults.color = "#495057";
  } catch (_) {}

  const makeDoughnut = (ctx, labels, values, colors) => {
    return new window.Chart(ctx, {
      type: "doughnut",
      data: {
        labels,
        datasets: [
          {
            data: values,
            backgroundColor: colors,
            borderWidth: 1,
            borderColor: "#fff",
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: "62%",
        plugins: {
          legend: { position: "bottom", labels: { boxWidth: 10 } },
          tooltip: {
            callbacks: {
              label: (c) => {
                const v = c.parsed ?? 0;
                const label = c.label || "";
                return `${label}: ${v}`;
              },
            },
          },
        },
      },
    });
  };

  const makeLine = (ctx, labels, datasets) => {
    return new window.Chart(ctx, {
      type: "line",
      data: { labels, datasets },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: "index", intersect: false },
        scales: {
          x: { grid: { display: false } },
          y: { beginAtZero: true, ticks: { precision: 0 } },
        },
        plugins: {
          legend: { position: "bottom", labels: { boxWidth: 10 } },
        },
      },
    });
  };

  const makeBar = (ctx, labels, datasets) => {
    return new window.Chart(ctx, {
      type: "bar",
      data: { labels, datasets },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          x: { stacked: true, grid: { display: false } },
          y: { stacked: true, beginAtZero: true, ticks: { precision: 0 } },
        },
        plugins: {
          legend: { position: "bottom", labels: { boxWidth: 10 } },
        },
      },
    });
  };

  if (elSitu) {
    const labels = data.situacao?.labels || [];
    const values = data.situacao?.values || [];
    const colors = [
      palette.primary,
      palette.info,
      palette.success,
      palette.warning,
      palette.danger,
      palette.secondary,
      palette.dark,
    ];
    window.__sigepInicioChartInstances.inicioChartSituacao = makeDoughnut(
      elSitu.getContext("2d"),
      labels,
      values,
      labels.map((_, i) => colors[i % colors.length]),
    );
  }

  if (elEle) {
    const labels = data.eletronicos_na_cela?.labels || [];
    const values = data.eletronicos_na_cela?.values || [];
    const colors = [
      palette.info,
      palette.primary,
      palette.success,
      palette.warning,
      palette.danger,
      palette.secondary,
      palette.dark,
    ];
    window.__sigepInicioChartInstances.inicioChartEletronicos = makeDoughnut(
      elEle.getContext("2d"),
      labels,
      values,
      labels.map((_, i) => colors[i % colors.length]),
    );
  }

  if (elEsc) {
    const labels = data.escoltas_14d?.labels || [];
    const total = data.escoltas_14d?.total || [];
    const fin = data.escoltas_14d?.finalizadas || [];
    const pen = data.escoltas_14d?.pendentes || [];

    window.__sigepInicioChartInstances.inicioChartEscoltas = makeLine(
      elEsc.getContext("2d"),
      labels,
      [
        {
          label: "Total",
          data: total,
          borderColor: palette.primary,
          backgroundColor: "rgba(0,123,255,0.12)",
          fill: true,
          tension: 0.25,
          pointRadius: 2,
        },
        {
          label: "Finalizadas",
          data: fin,
          borderColor: palette.success,
          backgroundColor: "rgba(40,167,69,0.0)",
          fill: false,
          tension: 0.25,
          pointRadius: 2,
        },
        {
          label: "Pendentes",
          data: pen,
          borderColor: palette.warning,
          backgroundColor: "rgba(255,193,7,0.0)",
          fill: false,
          tension: 0.25,
          pointRadius: 2,
        },
      ],
    );
  }

  if (elEcl) {
    const labels = data.eclusa_14d?.labels || [];
    const entradas = data.eclusa_14d?.entradas || [];
    const saidas = data.eclusa_14d?.saidas || [];
    window.__sigepInicioChartInstances.inicioChartEclusa = makeBar(
      elEcl.getContext("2d"),
      labels,
      [
        {
          label: "Entradas",
          data: entradas,
          backgroundColor: "rgba(40,167,69,0.85)",
        },
        {
          label: "Saídas",
          data: saidas,
          backgroundColor: "rgba(220,53,69,0.85)",
        },
      ],
    );
  }
}

function imprimirDossieA4(json) {
  const interno = json.interno || {};
  const nome =
    interno.nome_social && String(interno.nome_social).trim()
      ? interno.nome_social
      : interno.nome || "";
  const local = formatLocal(interno.galeria, interno.bloco, interno.res);
  const now = new Date().toLocaleString("pt-BR");

  const condicoes = Array.isArray(json.condicoes_especiais)
    ? json.condicoes_especiais
    : [];
  const eleNaCela = Array.isArray(json.eletronicos_na_cela)
    ? json.eletronicos_na_cela
    : [];
  const livros = Array.isArray(json.livros_itens) ? json.livros_itens : [];
  const roupas = Array.isArray(json.roupas_itens) ? json.roupas_itens : [];
  const cosmeticos = Array.isArray(json.cosmeticos_itens)
    ? json.cosmeticos_itens
    : [];
  const cartas = Array.isArray(json.cartas_recentes)
    ? json.cartas_recentes
    : [];
  const ctc = Array.isArray(json.ctc_historico) ? json.ctc_historico : [];
  const md = Array.isArray(json.md_historico) ? json.md_historico : [];
  const escoltas = Array.isArray(json.escoltas) ? json.escoltas : [];
  const doacoes = Array.isArray(json.doacoes) ? json.doacoes : [];
  const doacoesItens =
    json.doacoes_itens && typeof json.doacoes_itens === "object"
      ? json.doacoes_itens
      : {};
  const alteracoesInterno = Array.isArray(json.alteracoes_interno)
    ? json.alteracoes_interno
    : [];
  const alteracoesLaboral = Array.isArray(json.alteracoes_laboral)
    ? json.alteracoes_laboral
    : [];
  const peculioPix = Array.isArray(json.peculio_pix) ? json.peculio_pix : [];
  const peculioTrabalho = Array.isArray(json.peculio_trabalho)
    ? json.peculio_trabalho
    : [];
  const roupariaPecas = normalizePecasList(json.rouparia_civil_pecas);

  const lgbt = (interno.lgbt || "N") === "S" ? "Sim" : "Não";
  const regalia =
    interno.regalia === "S" || interno.regalia_galeria === "S" ? "Sim" : "Não";

  const sec = (title, html) => `
        <section class="sec">
            <h2>${escHtml(title)}</h2>
            ${html}
        </section>
    `;

  const table = (rows) => `
        <table class="t">
            <tbody>
                ${rows.map(([k, v]) => `<tr><th>${escHtml(k)}</th><td>${v}</td></tr>`).join("")}
            </tbody>
        </table>
    `;

  const list = (items, renderItem, emptyText = "Sem registros.") => {
    if (!items || items.length === 0)
      return `<div class="muted">${escHtml(emptyText)}</div>`;
    return `<ul class="ul">${items.map(renderItem).join("")}</ul>`;
  };

  const htmlDados = table([
    ["IPEN", escHtml(interno.ipen)],
    ["Nome", escHtml(interno.nome || "-")],
    [
      "Nome social",
      isBlank(interno.nome_social)
        ? `<span class="muted">${escHtml(interno.lgbt === "S" ? "Não cadastrado" : "Não se aplica.")}</span>`
        : escHtml(interno.nome_social),
    ],
    ["Apelido", escHtml(interno.apelido || "-")],
    ["CPF", escHtml(interno.cpf || "-")],
    ["Local", escHtml(local)],
    ["Situação", escHtml(interno.situacao || "-")],
    ["LGBT", escHtml(lgbt)],
    ["Regalia", escHtml(regalia)],
    ["Kit", escHtml(interno.kit ? `#${interno.kit}` : "-")],
    ["Tamanho kit", escHtml(interno.tamanho_kit || "-")],
  ]);

  const htmlTrabalho = (() => {
    const l = json.laboral_ativo;
    if (!l) return `<div class="muted">Sem laboral ativo.</div>`;
    return table([
      ["Estabelecimento", escHtml(l.estabelecimento || "-")],
      [
        "Remição",
        `${escHtml(formatDateBR(l.remicao_inicio))} -> ${escHtml(formatDateBR(l.remicao_fim))}`,
      ],
      [
        "Liberação",
        `${escHtml(formatDateBR(l.liberacao_inicio))} -> ${escHtml(formatDateBR(l.liberacao_fim))}`,
      ],
      ["Dias", escHtml(l.dias_semana || "-")],
      ["Status", escHtml(l.status || "-")],
    ]);
  })();

  const htmlCondicoes = list(
    condicoes,
    (c) =>
      `<li><strong>${escHtml(c.tipo || "-")}</strong> · ${escHtml(formatDateBR(c.data_inicio))} -> ${escHtml(formatDateBR(c.data_fim))} · ${escHtml(c.status || "-")}</li>`,
    "Sem condições especiais.",
  );
  const htmlEle = list(
    eleNaCela,
    (e) =>
      `<li><strong>${escHtml(formatEletronicoNome(e))}</strong>${e.marca_modelo ? " · " + escHtml(e.marca_modelo) : ""} · ${escHtml(e.cor || "-")} · ${escHtml(e.estado_conservacao || "-")}</li>`,
    "Sem eletrônicos na cela.",
  );
  const htmlLivros = list(
    livros.slice(0, 60),
    (l) =>
      `<li>${escHtml(l.titulo_livro || "-")}${l.autor ? " · " + escHtml(l.autor) : ""} · ${l.data_entrega_interno ? "Entregue" : "Pendente"}</li>`,
    "Sem livros.",
  );
  const htmlRoupas = list(
    roupas.slice(0, 60),
    (r) =>
      `<li>${escHtml(r.item || "-")} x${escHtml(r.quantidade || 0)} · ${escHtml(formatDateTimeBR(r.data_recebimento))} · ${r.data_entrega_interno ? "Entregue" : "Pendente"}</li>`,
    "Sem recebimentos de roupas.",
  );
  const htmlCos = list(
    cosmeticos.slice(0, 60),
    (r) =>
      `<li>${escHtml(r.item || "-")} x${escHtml(r.quantidade || 0)} · ${escHtml(formatDateTimeBR(r.data_recebimento))} · ${r.data_entrega_interno ? "Entregue" : "Pendente"}</li>`,
    "Sem recebimentos de cosméticos.",
  );
  const htmlCartas = list(
    cartas.slice(0, 30),
    (c) =>
      `<li>${escHtml(c.tipo_movimentacao || "Carta")} · ${escHtml(formatDateTimeBR(c.recebido_em))} · ${escHtml(c.status_censura || "-")} · ${escHtml(c.correspondente_nome || "")}</li>`,
    "Sem cartas.",
  );
  const htmlCTC = list(
    ctc.slice(0, 30),
    (c) =>
      `<li>${escHtml(formatDateBR(c.data_ctc))} · ${escHtml(c.resultado || "-")} · ${escHtml(c.status || "-")}</li>`,
    "Sem CTC.",
  );
  const htmlMD = list(
    md.slice(0, 30),
    (m) =>
      `<li>${escHtml(formatDateBR(m.data_inicio))} -> ${escHtml(formatDateBR(m.data_fim))} · ${escHtml(m.status || "-")} · ${escHtml((m.motivo || "").toString().slice(0, 140))}</li>`,
    "Sem MD.",
  );
  const htmlEscoltas = list(
    escoltas.slice(0, 30),
    (e) =>
      `<li>${escHtml(formatDateBR(e.data_cadastro))} · ${escHtml(e.destino || "-")} · ${escHtml(e.status || "-")}</li>`,
    "Sem escoltas.",
  );

  const htmlRouparia = list(
    roupariaPecas,
    (p) =>
      `<li>${escHtml(p.nome)}${p.quantidade !== null && Number.isFinite(p.quantidade) ? " x" + escHtml(p.quantidade) : ""}</li>`,
    "Sem rouparia civil.",
  );

  const htmlDoacoes = list(
    doacoes,
    (d) => {
      const role =
        Number(d.id_doador) === Number(interno.ipen) ? "Doador" : "Receptor";
      const localRec =
        d.tipo_receptor === "CELA"
          ? formatLocal(d.galeria_receptor, d.bloco_receptor, d.cela_receptor)
          : d.id_receptor
            ? "IPEN " + d.id_receptor
            : "-";
      const itens =
        doacoesItens[String(d.id)] || doacoesItens[Number(d.id)] || [];
      const itensTxt =
        Array.isArray(itens) && itens.length
          ? itens.map((i) => i.tipo_item || "Item").join(", ")
          : "-";
      return `<li>${escHtml(formatDateTimeBR(d.data_doacao))} · ${escHtml(role)} · ${escHtml(localRec)} · Itens: ${escHtml(itensTxt)}</li>`;
    },
    "Sem doações.",
  );

  const htmlAlteracoes = list(
    alteracoesInterno.concat(alteracoesLaboral).slice(0, 30),
    (a) =>
      `<li>${escHtml(formatDateTimeBR(a.data_alteracao))} · ${escHtml(a.operacao || "-")}: ${escHtml(a.campo || "-")}</li>`,
    "Sem alterações.",
  );

  const pixTop = peculioPix[0] || null;
  const trabTop = peculioTrabalho[0] || null;
  const htmlPeculio = table([
    ["Forma pagamento", escHtml(interno.forma_pagamento || "-")],
    [
      "Pix (último)",
      pixTop
        ? `${escHtml(pixTop.mes_referencia)} · ${escHtml(formatCurrencyBR(pixTop.valor))}`
        : "-",
    ],
    [
      "Trabalho (último)",
      trabTop
        ? `${escHtml(trabTop.mes_referencia)} · ${escHtml(formatCurrencyBR(trabTop.valor))}`
        : "-",
    ],
  ]);

  const html = `
        <div class="hdr">
            <div class="hdr-title">Dossiê do Interno</div>
            <div class="hdr-sub">${escHtml(nome)} · IPEN ${escHtml(interno.ipen)} · ${escHtml(local)}</div>
            <div class="hdr-meta">Gerado em ${escHtml(now)}</div>
        </div>
        ${sec("Dados", htmlDados)}
        ${sec("Trabalho", htmlTrabalho)}
        ${sec("Condições especiais", htmlCondicoes)}
        ${sec("Rouparia civil", htmlRouparia)}
        ${sec("Eletrônicos na cela", htmlEle)}
        ${sec("Livros", htmlLivros)}
        ${sec("Recebimento de roupas", htmlRoupas)}
        ${sec("Recebimento de cosméticos", htmlCos)}
        ${sec("Cartas", htmlCartas)}
        ${sec("CTC", htmlCTC)}
        ${sec("Medidas disciplinares", htmlMD)}
        ${sec("Escoltas", htmlEscoltas)}
        ${sec("Doações de eletrônicos", htmlDoacoes)}
        ${sec("Alterações (interno/laboral)", htmlAlteracoes)}
        ${sec("Pecúlio", htmlPeculio)}
    `;

  const styles = `
        @page { size: A4; margin: 12mm; }
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; color: #111; font-size: 11pt; }
        .hdr { border-bottom: 2px solid #111; padding-bottom: 8px; margin-bottom: 12px; }
        .hdr-title { font-size: 18pt; font-weight: 700; }
        .hdr-sub { margin-top: 4px; font-size: 11.5pt; }
        .hdr-meta { margin-top: 2px; color: #444; font-size: 9.5pt; }
        .sec { margin: 10px 0 0; break-inside: avoid; page-break-inside: avoid; }
        .sec h2 { font-size: 12pt; margin: 0 0 6px; padding: 6px 8px; background: #f2f2f2; border: 1px solid #ddd; }
        .muted { color: #555; }
        .t { width: 100%; border-collapse: collapse; }
        .t th, .t td { border: 1px solid #ddd; padding: 6px 8px; vertical-align: top; }
        .t th { width: 33%; text-align: left; background: #fafafa; }
        .ul { margin: 0; padding-left: 18px; }
        .ul li { margin: 2px 0; }
    `;

  const w = window.open("", "_blank");
  if (!w) {
    alert("Pop-up bloqueado. Permita pop-ups para imprimir o dossiê.");
    return;
  }
  w.document.open();
  w.document.write(
    `<!doctype html><html lang="pt-br"><head><meta charset="utf-8"><title>Dossiê IPEN ${escHtml(interno.ipen)}</title><style>${styles}</style></head><body>${html}</body></html>`,
  );
  w.document.close();
  w.focus();
  setTimeout(() => {
    w.print();
    w.close();
  }, 350);
}

// Funções para doações no dossiê
function mostrarModalDoacao(idDoacao, tipoItem, marcaModelo, cor) {
  const modalHtml = `
        <div class="modal fade" id="modalDetalhesDoacao" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-info-circle mr-2"></i>
                            Detalhes do Item Doado
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-12">
                                <div class="callout callout-info">
                                    <h6><i class="fas fa-tv mr-2"></i>Item Recebido por Doação</h6>
                                    <div class="row small">
                                        <div class="col-4 text-muted">Tipo:</div>
                                        <div class="col-8">${escHtml(tipoItem)}</div>
                                        <div class="col-4 text-muted">Marca/Modelo:</div>
                                        <div class="col-8">${escHtml(marcaModelo || "Não informado")}</div>
                                        <div class="col-4 text-muted">Cor:</div>
                                        <div class="col-8">${escHtml(cor || "Não informada")}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="text-center">
                                    <p class="text-muted mb-2">Este item foi recebido através do sistema de doações de eletrônicos.</p>
                                    <button class="btn btn-outline-primary btn-sm" onclick="verDetalhesCompletosDoacao(${idDoacao})">
                                        <i class="fas fa-list mr-1"></i> Ver Todos os Itens da Doação
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
                    </div>
                </div>
            </div>
        </div>
    `;

  // Remover modal existente se houver
  const existingModal = document.getElementById("modalDetalhesDoacao");
  if (existingModal) {
    existingModal.remove();
  }

  // Adicionar novo modal ao body
  document.body.insertAdjacentHTML("beforeend", modalHtml);

  // Mostrar o modal
  $("#modalDetalhesDoacao").modal("show");
}

function verDetalhesCompletosDoacao(idDoacao) {
  // Fechar modal atual
  $("#modalDetalhesDoacao").modal("hide");

  // Redirecionar para página de doações com filtro
  window.open(
    `/paginas/internos_doacao_eletronicos.php?doacao_id=${idDoacao}`,
    "_blank",
  );
}

function imprimirTermoDoacao(idDoacao) {
  // Abrir termo em nova janela usando URL amigável
  const urlTermo = `/termo-doacao-eletronicos/${idDoacao}`;
  console.log("Abrindo termo de doação:", urlTermo);
  window.open(urlTermo, "_blank");
}

// SPA: esse script pode ser executado tanto no load inicial quanto via loadPage().
initInicioModule();
