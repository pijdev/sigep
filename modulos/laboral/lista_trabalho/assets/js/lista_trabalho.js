// Resetar proteção contra múltiplos carregamentos para permitir recarregamento no SPA
window.listaTrabalhoLoaded = false;

// Variáveis essenciais que devem estar sempre disponíveis (fora da proteção)
// Evitar redeclaração em caso de recarregamento
if (typeof listaTrabalhoViewUrl === "undefined") {
  var listaTrabalhoViewUrl =
    "/modulos/laboral/lista_trabalho/lista_trabalho_view.php";
}
if (typeof listaTrabalhoLogicUrl === "undefined") {
  var listaTrabalhoLogicUrl =
    "/modulos/laboral/lista_trabalho/lista_trabalho_logica.php";
}

// Funções essenciais que devem estar sempre disponíveis (fora da proteção)
function filtrarInternos() {
  navegarComFiltros(true);
}

function limparFiltros() {
  navegarPara(listaTrabalhoViewUrl);
}

function navegarComFiltros(resetPage, queryStringOverride = null) {
  // Se tiver query string override (paginação), usa direto
  if (queryStringOverride) {
    navegarPara(`${listaTrabalhoViewUrl}?${queryStringOverride}`);
    return;
  }

  // Senão, monta query string dos filtros
  const params = new URLSearchParams();
  const fieldIds = [
    "busca",
    "resultado",
    "galeria",
    "estabelecimento",
    "situacao_ctc",
    "mostrar_trabalhando",
  ];

  fieldIds.forEach((fieldId) => {
    const value = $(`#${fieldId}`).val();
    if (value !== null && value !== "") {
      params.set(fieldId, value);
    }
  });

  if (resetPage) {
    params.set("pagina", "1");
  }

  const query = params.toString();
  navegarPara(
    query ? `${listaTrabalhoViewUrl}?${query}` : listaTrabalhoViewUrl,
  );
}

function navegarPara(url) {
  if (typeof loadPage === "function") {
    loadPage(url);
  } else {
    window.location.href = url;
  }
}

// Funções de offcanvas essenciais (fora da proteção)
function abrirOffcanvasCadastrarCTC(ipen = null) {
  resetFormCTC();
  $("#offcanvasCTCTitle").text("Cadastrar CTC");

  if (ipen) {
    // Buscar dados do interno
    postAction({ action: "buscar_interno", ipen: ipen })
      .then((data) => {
        if (data.success) {
          selecionarInternoCtc(data.interno);
        }
      })
      .catch(showRequestError);
  }

  abrirOffcanvas("#offcanvasCTC");
}

// Funções essenciais de detalhes (fora da proteção)
function verDetalhesInterno(ipen) {
  postAction({ action: "detalhes", ipen })
    .then((data) => {
      const interno = data.interno || {};
      const ctcs = data.ctcs || [];

      // Criar HTML do modal
      const modalHtml = `
        <div class="modal fade" id="modalDetalhesStat" tabindex="-1">
          <div class="modal-dialog modal-lg">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">Detalhes do Interno - ${interno.ipen}</h5>
                <button type="button" class="close" data-dismiss="modal">
                  <span>&times;</span>
                </button>
              </div>
              <div class="modal-body">
                <div class="row">
                  <div class="col-md-6">
                    <strong>IPEN:</strong> ${interno.ipen}<br>
                    <strong>Nome:</strong> ${interno.nome}<br>
                    <strong>Local:</strong> ${interno.local}<br>
                    <strong>Última Entrada:</strong> ${interno.ultima_entrada}
                  </div>
                  <div class="col-md-6">
                    <strong>Trabalha:</strong> ${interno.trabalha ? "Sim" : "Não"}<br>
                    <strong>Onde Trabalha:</strong> ${interno.onde_trabalha || "-"}<br>
                    <strong>Início Trabalho:</strong> ${interno.inicio_trabalho || "-"}
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

      $("#modalDetalhesStat").remove();
      $("body").append(modalHtml);
      $("#modalDetalhesStat")
        .modal("show")
        .on("hidden.bs.modal", function () {
          $(this).off("hidden.bs.modal");
          setTimeout(() => $(this).remove(), 100);
        });
    })
    .catch(showRequestError);
}

// Funções essenciais de AJAX (fora da proteção)
function postAction(payload) {
  return fetch(listaTrabalhoLogicUrl, {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
    },
    body: new URLSearchParams(payload).toString(),
  }).then(async (response) => {
    const json = await response.json();
    if (!response.ok || json.success === false) {
      throw new Error(json.message || "Erro ao processar a requisição.");
    }
    return json;
  });
}

// Funções essenciais de formulário (fora da proteção)
function resetFormCTC() {
  $("#formCTC")[0].reset();
  $("#ctc_id").val("");
  $("#ctc_ipen").val("");
  $("#ctc_busca_resultados").hide().empty();
  selectedInternoBuscaCtc = null;
}

// Funções essenciais de offcanvas (fora da proteção)
let offcanvasBackdrop = null;

function abrirOffcanvas(selector) {
  const element = $(selector);
  if (!element.length) {
    return;
  }

  fecharOffcanvasAtual();
  ensureOffcanvasBackdrop();
  element.addClass("show");
  $("body").addClass("offcanvas-open");
}

function fecharOffcanvasAtual() {
  $(".offcanvas-custom.show").removeClass("show");
  if (offcanvasBackdrop) {
    offcanvasBackdrop.removeClass("show");
    setTimeout(() => offcanvasBackdrop?.remove(), 200);
    offcanvasBackdrop = null;
  }
  $("body").removeClass("offcanvas-open");
}

function ensureOffcanvasBackdrop() {
  if (offcanvasBackdrop) {
    offcanvasBackdrop.remove();
  }

  offcanvasBackdrop = $('<div class="offcanvas-backdrop-custom"></div>');
  offcanvasBackdrop.on("click", fecharOffcanvasAtual);
  $("body").append(offcanvasBackdrop);

  requestAnimationFrame(() => offcanvasBackdrop.addClass("show"));
}

// Proteção contra múltiplos carregamentos no SPA
if (typeof window.listaTrabalhoLoaded === "undefined") {
  window.listaTrabalhoLoaded = true;

  // Variáveis movidas para fora do bloco de proteção

  if (typeof ipenAtual === "undefined") {
    var ipenAtual = null;
  }

  let offcanvasBackdrop = null;
  let selectedInternoBuscaCtc = null;
  let selectedInternoExclusao = null;

  $(document).ready(function () {
    bindStaticEvents();
    toggleMotivoOutro();
    toggleMotivoDesfavoravel();
  });

  function bindStaticEvents() {
    $(".js-stat-card").on("click", function () {
      abrirDetalhesStat($(this).data("stat-title"), $(this).data("stat"));
    });

    $("#formCTC").on("submit", function (event) {
      event.preventDefault();
      salvarCTC();
    });

    $("#formNovaExclusao").on("submit", function (event) {
      event.preventDefault();
      salvarNovaExclusao();
    });

    $("#ctc_resultado").on("change", toggleMotivoDesfavoravel);
    $("#exc_motivo").on("change", toggleMotivoOutro);

    $(document).on("click", "[data-offcanvas-close]", fecharOffcanvasAtual);
    $(document).on("keydown", function (event) {
      if (event.key === "Escape") {
        fecharOffcanvasAtual();
      }
    });

    $("#modalListaExclusao").on("show.bs.modal", carregarListaExclusao);

    $("#ctc_interno_nome").on("keydown", function (event) {
      if (event.key === "Enter") {
        event.preventDefault();
        buscarInternoCTC();
      }
    });

    let ctcSearchTimeout;
    $("#ctc_interno_nome").on("input", function () {
      selectedInternoBuscaCtc = null;
      $("#ctc_ipen").val("");
      clearTimeout(ctcSearchTimeout);

      const termo = $(this).val().trim();
      if (termo.length < 2) {
        renderBuscaInternoCtc([]);
        return;
      }

      ctcSearchTimeout = setTimeout(() => {
        buscarInternosPorTermo(termo).then(renderBuscaInternoCtc);
      }, 250);
    });

    let exclusaoSearchTimeout;
    $("#exc_ipen").on("input", function () {
      selectedInternoExclusao = null;
      $("#exc_nome").hide().empty();
      clearTimeout(exclusaoSearchTimeout);

      const termo = $(this).val().trim();
      if (termo.length < 2) {
        return;
      }

      exclusaoSearchTimeout = setTimeout(() => {
        buscarInternosPorTermo(termo).then(renderBuscaInternoExclusao);
      }, 250);
    });

    $(document).on("click", ".js-select-interno-ctc", function () {
      const interno = parseDatasetInterno($(this).attr("data-interno"));
      selecionarInternoCtc(interno);
    });

    $(document).on("click", ".js-select-interno-exclusao", function () {
      const interno = parseDatasetInterno($(this).attr("data-interno"));
      selecionarInternoExclusao(interno);
    });
  }

  function abrirDetalhesStat(titulo, stat) {
    postAction({ action: "stat_detalhes", stat })
      .then((data) => {
        const rows = (data.internos || [])
          .map(
            (interno) => `
            <tr>
              <td class="text-center">${escapeHtml(interno.ipen)}</td>
              <td>${escapeHtml(interno.nome_completo || interno.nome || "")}</td>
              <td class="text-center">${escapeHtml(interno.local_formatado || "-")}</td>
              <td class="text-center">${escapeHtml(interno.situacao || "-")}</td>
              <td class="text-center">${escapeHtml(interno.onde_trabalha || "-")}</td>
              <td class="text-center">${formatDate(interno.data_ctc)}</td>
            </tr>
          `,
          )
          .join("");

        const modalHtml = `
        <div class="modal fade" id="modalDetalhesStat" tabindex="-1" role="dialog" aria-hidden="true">
          <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">${escapeHtml(titulo)}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
              <div class="modal-body">
                <p class="text-muted mb-3">Total encontrado: <strong>${data.total}</strong></p>
                <div class="table-responsive">
                  <table class="table table-striped table-hover table-sm">
                    <thead>
                      <tr>
                        <th>IPEN</th>
                        <th>Nome</th>
                        <th>Local</th>
                        <th>Situação</th>
                        <th>Onde trabalha</th>
                        <th>Data CTC</th>
                      </tr>
                    </thead>
                    <tbody>${rows || '<tr><td colspan="6" class="text-center text-muted py-3">Nenhum registro encontrado.</td></tr>'}</tbody>
                  </table>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
              </div>
            </div>
          </div>
        </div>
      `;

        $("#modalDetalhesStat").remove();
        $("body").append(modalHtml);
        $("#modalDetalhesStat")
          .modal("show")
          .on("hidden.bs.modal", function () {
            // Remover evento primeiro para evitar memory leaks
            $(this).off("hidden.bs.modal");
            // Remover modal com delay para garantir que o evento seja processado
            setTimeout(() => $(this).remove(), 100);
          });
      })
      .catch(showRequestError);
  }

  // Função movida para fora do bloco de proteção

  function abrirOffcanvasEditarCTC(ipen) {
    resetFormCTC();
    $("#offcanvasCTCTitle").text("Editar CTC ativo");

    postAction({ action: "detalhes", ipen })
      .then((data) => {
        const interno = data.interno;
        const ctcAtivo =
          (data.ctcs || []).find((item) => item.status === "Ativo") ||
          data.ctcs?.[0];

        selecionarInternoCtc({
          ipen: interno.ipen,
          nome: interno.nome,
          nome_social: interno.nome_social || "",
          apelido: interno.apelido || "",
          galeria: interno.galeria,
          bloco: interno.bloco,
          res: interno.res,
        });

        if (ctcAtivo) {
          $("#ctc_id").val(ctcAtivo.id || "");
          $("#ctc_data_ctc").val(ctcAtivo.data_ctc || "");
          $("#ctc_resultado").val(ctcAtivo.resultado || "");
          $("#ctc_decisao_juiz").val(ctcAtivo.decisao_juiz || "");
          $("#ctc_motivo_desfavoravel").val(ctcAtivo.motivo_desfavoravel || "");
          $("#ctc_observacoes").val(ctcAtivo.observacoes || "");
        }

        toggleMotivoDesfavoravel();
        abrirOffcanvas("#offcanvasCTC");
      })
      .catch(showRequestError);
  }

  function salvarCTC() {
    const payload = {
      action: "salvar_ctc",
      ipen: $("#ctc_ipen").val(),
      data_ctc: $("#ctc_data_ctc").val(),
      resultado: $("#ctc_resultado").val(),
      decisao_juiz: $("#ctc_decisao_juiz").val(),
      motivo_desfavoravel: $("#ctc_motivo_desfavoravel").val(),
      observacoes: $("#ctc_observacoes").val(),
    };

    if (!payload.ipen) {
      window.alert("Selecione um interno antes de salvar o CTC.");
      return;
    }

    if (!payload.data_ctc || !payload.resultado) {
      window.alert("Preencha a data do CTC e o resultado.");
      return;
    }

    postAction(payload)
      .then((data) => {
        window.alert(data.message || "CTC salvo com sucesso.");
        fecharOffcanvasAtual();
        navegarComFiltros(false);
      })
      .catch(showRequestError);
  }

  function excluirCTC(ipen) {
    if (
      !window.confirm("Deseja realmente excluir o CTC ativo deste interno?")
    ) {
      return;
    }

    postAction({ action: "excluir_ctc", ipen })
      .then((data) => {
        window.alert(data.message || "CTC excluído com sucesso.");
        navegarComFiltros(false);
      })
      .catch(showRequestError);
  }

  // Função movida para fora do bloco de proteção

  function buscarInternoCTC() {
    const termo = $("#ctc_interno_nome").val().trim();
    if (!termo) {
      window.alert("Digite o IPEN ou nome do interno.");
      return;
    }

    buscarInternosPorTermo(termo)
      .then((internos) => {
        if (!internos.length) {
          window.alert("Nenhum interno encontrado.");
          return;
        }

        if (internos.length === 1) {
          selecionarInternoCtc(internos[0]);
          return;
        }

        renderBuscaInternoCtc(internos);
      })
      .catch(showRequestError);
  }

  function preloadInternoParaCtc(ipen) {
    postAction({ action: "buscar_interno", ipen })
      .then((data) => {
        selecionarInternoCtc(data.interno);
      })
      .catch(showRequestError);
  }

  function selecionarInternoCtc(interno) {
    selectedInternoBuscaCtc = interno;
    $("#ctc_ipen").val(interno.ipen);
    $("#ctc_interno_nome").val(buildInternoLabel(interno));
    renderBuscaInternoCtc([]);
  }

  function renderBuscaInternoCtc(internos) {
    let container = $("#ctc_busca_resultados");
    if (!container.length) {
      container = $(
        '<div id="ctc_busca_resultados" class="list-group inicio-busca-resultados mt-2"></div>',
      );
      $("#ctc_interno_nome").closest(".form-group").append(container);
    }

    if (!internos.length) {
      container.hide().empty();
      return;
    }

    container
      .html(
        internos
          .map((interno) => {
            const payload = escapeHtml(JSON.stringify(interno)).replaceAll(
              '"',
              "&quot;",
            );
            return `
            <button type="button" class="list-group-item list-group-item-action js-select-interno-ctc" data-interno="${payload}">
              <strong>${escapeHtml(buildInternoLabel(interno))}</strong>
              <span class="d-block text-muted small">${escapeHtml(buildInternoLocal(interno))}</span>
            </button>
          `;
          })
          .join(""),
      )
      .show();
  }

  function buscarInternoExclusao() {
    const termo = $("#exc_ipen").val().trim();
    if (!termo) {
      window.alert("Digite o IPEN ou nome do interno.");
      return;
    }

    buscarInternosPorTermo(termo)
      .then((internos) => {
        if (!internos.length) {
          window.alert("Nenhum interno encontrado.");
          return;
        }

        if (internos.length === 1) {
          selecionarInternoExclusao(internos[0]);
          return;
        }

        renderBuscaInternoExclusao(internos);
      })
      .catch(showRequestError);
  }

  function renderBuscaInternoExclusao(internos) {
    const container = $("#exc_nome");
    if (!internos.length) {
      container.hide().empty();
      return;
    }

    container
      .html(
        internos
          .map((interno) => {
            const payload = escapeHtml(JSON.stringify(interno)).replaceAll(
              '"',
              "&quot;",
            );
            return `
            <button type="button" class="list-group-item list-group-item-action js-select-interno-exclusao" data-interno="${payload}">
              <strong>${escapeHtml(buildInternoLabel(interno))}</strong>
              <span class="d-block text-muted small">${escapeHtml(buildInternoLocal(interno))}</span>
            </button>
          `;
          })
          .join(""),
      )
      .show();
  }

  function selecionarInternoExclusao(interno) {
    selectedInternoExclusao = interno;
    $("#exc_ipen").val(interno.ipen);
    $("#exc_nome").hide().empty();
  }

  function mostrarFormAdicionarExclusao() {
    $("#formAdicionarExclusao").removeClass("d-none");
    $("#exc_ipen").trigger("focus");
  }

  function cancelarAdicionarExclusao() {
    $("#formAdicionarExclusao").addClass("d-none");
    $("#formNovaExclusao")[0].reset();
    $("#exc_nome").hide().empty();
    selectedInternoExclusao = null;
  }

  function toggleMotivoOutro() {
    const selected = $("#exc_motivo").val();
    const wrapper = $("#exc_motivo_outro").closest(".form-group");

    if (selected === "Outro") {
      wrapper.show();
      $("#exc_motivo_outro").prop("required", true);
    } else {
      wrapper.hide();
      $("#exc_motivo_outro").prop("required", false).val("");
    }
  }

  function salvarNovaExclusao() {
    const motivoSelecionado = $("#exc_motivo").val();
    const motivoOutro = $("#exc_motivo_outro").val().trim();
    const motivoFinal =
      motivoSelecionado === "Outro" ? motivoOutro : motivoSelecionado;

    const payload = {
      action: "lista_exclusao",
      sub_action: "adicionar",
      ipen: $("#exc_ipen").val().trim(),
      motivo: motivoFinal,
      data_inicio: $("#exc_data_inicio").val(),
      data_fim: $("#exc_data_fim").val(),
      observacoes: $("#exc_observacoes").val().trim(),
    };

    if (!payload.ipen || !payload.motivo || !payload.data_inicio) {
      window.alert("Preencha IPEN, motivo e data de início.");
      return;
    }

    postAction(payload)
      .then((data) => {
        window.alert(data.message || "Lista de exclusão atualizada.");
        cancelarAdicionarExclusao();
        carregarListaExclusao();
      })
      .catch(showRequestError);
  }

  function carregarListaExclusao() {
    postAction({ action: "lista_exclusao", sub_action: "listar" })
      .then((data) => {
        atualizarTabelaExclusao(data.itens || []);
      })
      .catch(showRequestError);
  }

  function atualizarTabelaExclusao(itens) {
    const tbody = $("#tabelaExclusao tbody");

    if (!itens.length) {
      tbody.html(
        '<tr><td colspan="6" class="text-center text-muted py-3">Nenhum interno na lista de exclusão.</td></tr>',
      );
      return;
    }

    tbody.html(
      itens
        .map(
          (item) => `
          <tr>
            <td>${escapeHtml(item.ipen)}</td>
            <td>${escapeHtml(item.nome_interno || "-")}</td>
            <td>${escapeHtml(item.motivo || "-")}</td>
            <td>${escapeHtml(item.data_inicio_formatada || "-")}</td>
            <td>${escapeHtml(item.data_fim_formatada || "-")}</td>
            <td>
              <button type="button" class="btn btn-sm btn-danger" onclick="removerItemExclusao(${item.id})">
                <i class="fas fa-trash"></i>
              </button>
            </td>
          </tr>
        `,
        )
        .join(""),
    );
  }

  function removerItemExclusao(id) {
    if (!window.confirm("Deseja remover este item da lista de exclusão?")) {
      return;
    }

    postAction({ action: "remover_exclusao", id })
      .then((data) => {
        window.alert(data.message || "Item removido.");
        carregarListaExclusao();
        navegarComFiltros(false);
      })
      .catch(showRequestError);
  }

  function toggleMotivoDesfavoravel() {
    const resultado = $("#ctc_resultado").val();
    const row = $("#row_motivo_desfavoravel");

    if (resultado === "Desfavorável") {
      row.show();
      $("#ctc_motivo_desfavoravel").prop("required", true);
    } else {
      row.hide();
      $("#ctc_motivo_desfavoravel").prop("required", false).val("");
    }
  }

  // Função movida para fora do bloco de proteção

  // Função movida para fora do bloco de proteção

  // Função movida para fora do bloco de proteção

  // Função movida para fora do bloco de proteção

  // Função movida para fora do bloco de proteção

  function buscarInternosPorTermo(termo) {
    return postAction({ action: "buscar_interno_por_termo", termo }).then(
      (data) => data.internos || [],
    );
  }

  function buildInternoLabel(interno) {
    let label = interno.nome || "";
    if (interno.nome_social) {
      label += ` (${interno.nome_social})`;
    }
    if (interno.apelido) {
      label += ` - "${interno.apelido}"`;
    }
    return `${interno.ipen} - ${label}`;
  }

  function buildInternoLocal(interno) {
    return `Local: ${interno.galeria || "-"}${interno.bloco || ""}-${interno.res || ""}`;
  }

  function formatDate(value) {
    if (!value) {
      return "-";
    }

    if (/^\d{4}-\d{2}-\d{2}/.test(value)) {
      const [year, month, day] = value.substring(0, 10).split("-");
      return `${day}/${month}/${year}`;
    }

    return value;
  }

  function escapeHtml(value) {
    return String(value ?? "")
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }

  function parseDatasetInterno(rawValue) {
    try {
      return JSON.parse(
        String(rawValue ?? "")
          .replaceAll("&quot;", '"')
          .replaceAll("&amp;", "&"),
      );
    } catch (error) {
      return {};
    }
  }

  function showRequestError(error) {
    window.alert(error.message || "Erro de comunicação com o servidor.");
  }

  // Fechar bloco de proteção contra múltiplos carregamentos no SPA
} // fim do if (typeof window.listaTrabalhoLoaded === 'undefined')
