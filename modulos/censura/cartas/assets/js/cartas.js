// Variável global para arquivos selecionados
let selectedFiles = [];

// Proteger contra múltiplos carregamentos no SPA
if (typeof window.cartasLoaded === "undefined") {
  window.cartasLoaded = true;

  // Função de notificação
  function mostrarNotificacao(mensagem, tipo = "info") {
    const notificacao = document.createElement("div");
    notificacao.className = `cartas-notificacao alert alert-${tipo} alert-dismissible fade show position-fixed`;
    notificacao.innerHTML = `
        ${mensagem}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    notificacao.style.zIndex = "9999";
    document.body.appendChild(notificacao);

    setTimeout(() => {
      notificacao.remove();
    }, 5000);
  }

  // Validação de arquivos
  function validateFile(file) {
    const maxSize = 10 * 1024 * 1024; // 10MB
    const allowedTypes = [
      "image/jpeg",
      "image/png",
      "image/gif",
      "application/pdf",
      "application/msword",
      "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
    ];

    if (!allowedTypes.includes(file.type)) {
      mostrarNotificacao(
        "⚠️ Tipo de arquivo não permitido: " + file.type,
        "warning",
      );
      return false;
    }

    if (file.size > maxSize) {
      mostrarNotificacao(
        "⚠️ Arquivo muito grande. Máximo permitido: 10MB",
        "warning",
      );
      return false;
    }

    return true;
  }

  // Atualizar lista de arquivos selecionados
  function updateFileList() {
    const fileList = document.getElementById("fileList");
    if (!fileList) return;

    if (selectedFiles.length === 0) {
      fileList.innerHTML =
        '<p class="text-muted">Nenhum arquivo selecionado</p>';
      return;
    }

    let html = '<div class="row">';
    selectedFiles.forEach((file, index) => {
      html += `
            <div class="col-md-6 mb-2">
                <div class="card">
                    <div class="card-body p-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <i class="bi bi-file-earmark text-primary"></i>
                                <small class="text-truncate">${file.name}</small>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removerFile(${index})">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
    html += "</div>";
    fileList.innerHTML = html;
  }

  // Remover arquivo da lista
  function removerFile(index) {
    selectedFiles.splice(index, 1);
    updateFileList();
  }

  // Funções de Anexos
  window.abrirModalAnexos = function (cartaId, ipen, nomeInterno) {
    // Atualizar header do modal
    document.getElementById("anexosHeader").textContent =
      ipen + " - " + nomeInterno;
    document.getElementById("anexosCount").textContent = "0";

    // Limpar grid de anexos
    document.getElementById("anexosGrid").innerHTML =
      '<div class="col-12 text-center py-4"><div class="spinner-border text-primary" role="status"></div> Carregando anexos...</div>';

    // Abrir modal
    const modal = new bootstrap.Modal(document.getElementById("modalAnexos"));
    modal.show();

    // Carregar anexos via AJAX
    carregarAnexos(cartaId);
  };

  window.carregarAnexos = async function (cartaId) {
    try {
      const response = await fetch(
        "/modulos/censura/cartas/cartas_view.php?acao=listar_anexos&id_carta=" +
          encodeURIComponent(cartaId),
      );
      const data = await response.json();

      if (data.status === "success") {
        exibirAnexos(data.anexos);
      } else {
        document.getElementById("anexosGrid").innerHTML =
          '<div class="col-12 text-center py-4"><p class="text-danger">Erro ao carregar anexos</p></div>';
      }
    } catch (error) {
      console.error("Erro ao carregar anexos:", error);
      document.getElementById("anexosGrid").innerHTML =
        '<div class="col-12 text-center py-4"><p class="text-danger">Falha na comunicação</p></div>';
    }
  };

  function exibirAnexos(anexos) {
    const grid = document.getElementById("anexosGrid");
    const count = document.getElementById("anexosCount");

    count.textContent = anexos.length;

    if (anexos.length === 0) {
      grid.innerHTML =
        '<div class="col-12 text-center py-4"><p class="text-muted">Nenhum anexo encontrado</p></div>';
      return;
    }

    let html = "";
    anexos.forEach((anexo) => {
      const icon = getFileIcon(anexo.mime_type);
      const size = formatFileSize(anexo.tamanho_bytes);

      html += `
            <div class="col-md-6 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <i class="${icon} text-primary me-2"></i>
                            <div class="flex-grow-1">
                                <h6 class="mb-0">${anexo.nome_arquivo_original}</h6>
                                <small class="text-muted">${size} - ${anexo.mime_type}</small>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between">
                            <small class="text-muted">${new Date(anexo.created_at).toLocaleString()}</small>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" onclick="baixarAnexo(${anexo.id})">
                                    <i class="bi bi-download"></i>
                                </button>
                                <button class="btn btn-outline-danger" onclick="excluirAnexo(${anexo.id})">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });

    grid.innerHTML = html;
  }

  function getFileIcon(mimeType) {
    if (mimeType.startsWith("image/")) return "bi bi-image";
    if (mimeType === "application/pdf") return "bi bi-file-pdf";
    if (mimeType.includes("word")) return "bi bi-file-word";
    if (mimeType.includes("excel")) return "bi bi-file-excel";
    if (mimeType.includes("powerpoint")) return "bi bi-file-ppt";
    return "bi bi-file-earmark";
  }

  function formatFileSize(bytes) {
    if (bytes === 0) return "0 Bytes";
    const k = 1024;
    const sizes = ["Bytes", "KB", "MB", "GB"];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + " " + sizes[i];
  }

  window.baixarAnexo = async function (anexoId) {
    try {
      const response = await fetch(
        "/modulos/censura/cartas/cartas_view.php?acao=baixar_anexo&id=" +
          anexoId,
      );
      const blob = await response.blob();
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      a.download =
        response.headers
          .get("Content-Disposition")
          ?.split("filename=")[1]
          ?.replace(/"/g, "") || "anexo";
      document.body.appendChild(a);
      a.click();
      window.URL.revokeObjectURL(url);
      document.body.removeChild(a);
    } catch (error) {
      console.error("Erro ao baixar anexo:", error);
      mostrarNotificacao("Erro ao baixar anexo", "danger");
    }
  };

  window.excluirAnexo = async function (anexoId) {
    if (!confirm("Tem certeza que deseja excluir este anexo?")) return;

    try {
      const formData = new FormData();
      formData.append("acao", "excluir_anexo");
      formData.append("id", anexoId);

      const response = await fetch("/modulos/censura/cartas/cartas_view.php", {
        method: "POST",
        body: formData,
      });

      const data = await response.json();

      if (data.status === "success") {
        mostrarNotificacao("Anexo excluído com sucesso", "success");
        // Recarregar anexos
        const cartaId = document
          .getElementById("anexosHeader")
          .textContent.split(" - ")[0];
        carregarAnexos(cartaId);
      } else {
        mostrarNotificacao("Erro ao excluir anexo: " + data.msg, "danger");
      }
    } catch (error) {
      console.error("Erro ao excluir anexo:", error);
      mostrarNotificacao("Falha na comunicação", "danger");
    }
  };

  // Funções principais do módulo
  function aplicarFiltrosCartas() {
    const form = document.getElementById("formFiltros");
    const params = new URLSearchParams(new FormData(form)).toString();
    if (typeof loadPage === "function")
      loadPage(
        "modulos/censura/cartas/cartas_view.php?" + params,
        "Cartas",
        "Censura",
      );
    else
      window.location.href = "modulos/censura/cartas/cartas_view.php?" + params;
  }

  function nowLocalDateTimeValue() {
    const dt = new Date();
    dt.setMinutes(dt.getMinutes() - dt.getTimezoneOffset());
    return dt.toISOString().slice(0, 16);
  }

  function preencherCorrespondente(c) {
    $("#hiddenCorrespondente").val(c.id || "");
    $("#correspondenteNome").val(c.nome || "");
    $("#correspondenteVinculo").val(c.vinculo || "");
    $("#correspondenteLogradouro").val(c.logradouro || "");
    $("#correspondenteNumero").val(c.numero || "");
    $("#correspondenteBairro").val(c.bairro || "");
    $("#correspondenteCidade").val(c.cidade || "");
    $("#correspondenteUf").val((c.uf || "").toUpperCase());
    $("#correspondenteCep").val(c.cep || "");
    $("#correspondenteComplemento").val(c.complemento || "");
  }

  function atualizarDeParaHint() {
    const tipo = $("#tipoMov").val();
    const interno = $("#displayInterno").val() || "Interno";
    const correspondente = $("#correspondenteNome").val() || "Correspondente";
    if (tipo === "Entrada")
      $("#deParaHint").text(
        "Entrada: Remetente = " +
          correspondente +
          " | Destinatário = " +
          interno,
      );
    else
      $("#deParaHint").text(
        "Saída: Remetente = " + interno + " | Destinatário = " + correspondente,
      );
  }

  function abrirModalNovaCarta() {
    document.getElementById("formNovaCarta").reset();
    $("#hiddenIpen").val("");
    $("#hiddenCorrespondente").val("");
    $("#displayInterno").val("");
    $("#displayLocal").text("");
    $("#recebidoEm").val(nowLocalDateTimeValue());
    atualizarDeParaHint();
    $("#modalNovaCarta").modal("show");
  }

  function abrirModalStatus(idCarta, status, obs, motivo, statusRegistro) {
    $("#statusIdCarta").val(idCarta);
    $("#statusNovo").val(status || "Liberada");
    $("#statusObs").val(obs || "");
    $("#statusMotivo").val(motivo || "");
    $("#btnCancelarRegistro").prop("disabled", statusRegistro === "Cancelado");
    $("#modalStatusCarta").modal("show");
  }

  async function buscarInterno(termo) {
    const res = await fetch(
      "modulos/censura/cartas/cartas_view.php?acao=buscar_interno&termo=" +
        encodeURIComponent(termo),
    );
    return await res.json();
  }

  async function buscarCorrespondente(termo) {
    const res = await fetch(
      "modulos/censura/cartas/cartas_view.php?acao=buscar_correspondente&termo=" +
        encodeURIComponent(termo),
    );
    return await res.json();
  }

  function drawSuggest(boxId, rows, renderFn, clickFn) {
    const box = $("#" + boxId);
    if (!rows || !rows.length) return box.hide();
    box.html(
      rows
        .map(
          (r, i) =>
            '<div class="search-item" data-i="' +
            i +
            '">' +
            renderFn(r) +
            "</div>",
        )
        .join(""),
    );
    box.find(".search-item").on("click", function () {
      clickFn(rows[$(this).data("i")]);
      box.hide();
    });
    box.show();
  }

  async function sugerirCorrespondente() {
    const ipen = $("#hiddenIpen").val();
    if (!ipen) return alert("Selecione o interno primeiro.");
    const tipo = $("#tipoMov").val();
    const res = await fetch(
      "modulos/censura/cartas/cartas_view.php?acao=sugestao_por_interno&ipen=" +
        encodeURIComponent(ipen) +
        "&tipo_movimentacao=" +
        encodeURIComponent(tipo),
    );
    const data = await res.json();
    if (data.status === "success" && data.sugestao) {
      preencherCorrespondente(data.sugestao);
      atualizarDeParaHint();
    } else alert("Nenhuma sugestão encontrada.");
  }

  async function sugerirInterno() {
    const idCorresp = $("#hiddenCorrespondente").val();
    if (!idCorresp) return alert("Selecione o correspondente primeiro.");
    const tipo = $("#tipoMov").val();
    const res = await fetch(
      "modulos/censura/cartas/cartas_view.php?acao=sugestao_por_correspondente&id_correspondente=" +
        encodeURIComponent(idCorresp) +
        "&tipo_movimentacao=" +
        encodeURIComponent(tipo),
    );
    const data = await res.json();
    if (data.status === "success" && data.sugestao) {
      const i = data.sugestao;
      $("#hiddenIpen").val(i.ipen);
      $("#displayInterno").val(
        (i.ipen || "") + " - " + (i.nome_social || i.nome || ""),
      );

      // Formatar localização no padrão correto: GALERIA+BLOCO-CELA
      const galeria = i.galeria || "";
      const bloco = i.bloco || "";
      const cela = i.res || "";
      const localizacao = `${galeria}${bloco}-${cela}`
        .replace(/--/g, "-")
        .replace(/^-|-$/g, "");

      $("#displayLocal").text(localizacao);
      console.log("Localização sugerida:", localizacao, "Dados:", {
        galeria,
        bloco,
        cela,
      });
      atualizarDeParaHint();
    } else alert("Nenhuma sugestão encontrada.");
  }

  async function mostrarHistorico(idCarta) {
    const res = await fetch(
      "modulos/censura/cartas/cartas_view.php?acao=listar_historico&id_carta=" +
        encodeURIComponent(idCarta),
    );
    const data = await res.json();
    const box = $("#historicoConteudo");
    if (data.status !== "success" || !data.dados || !data.dados.length) {
      box.html(
        '<div class="text-muted">Sem histórico para este registro.</div>',
      );
    } else {
      let html = '<div class="timeline">';
      data.dados.forEach((item) => {
        html += `
                <div class="timeline-item ${item.operacao.toLowerCase()}">
                    <div class="timeline-marker">
                        <i class="bi bi-${getTimelineIcon(item.operacao)}"></i>
                    </div>
                    <div class="timeline-content">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <strong>${item.operacao}</strong>
                                <br><small class="text-muted">${new Date(item.data_hora).toLocaleString()}</small>
                                <br><small>por ${item.usuario_nome}</small>
                            </div>
                        </div>
                        ${item.valor_antigo ? `<div class="mt-2"><small class="text-muted">Antes:</small><br><pre>${JSON.stringify(item.valor_antigo, null, 2)}</pre></div>` : ""}
                        ${item.valor_novo ? `<div class="mt-2"><small class="text-muted">Depois:</small><br><pre>${JSON.stringify(item.valor_novo, null, 2)}</pre></div>` : ""}
                    </div>
                </div>
            `;
      });
      html += "</div>";
      box.html(html);
    }
    $("#modalHistoricoCarta").modal("show");
  }

  function getTimelineIcon(operacao) {
    const icons = {
      INSERT: "plus-circle",
      UPDATE: "pencil",
      STATUS: "arrow-repeat",
      CANCELAMENTO: "x-circle",
      RETIFICACAO: "arrow-clockwise",
    };
    return icons[operacao] || "circle";
  }

  function imprimirCarta(idCarta) {
    window.open(
      "/modulos/censura/cartas/cartas_view.php?acao=imprimir_carta&id=" +
        idCarta,
      "_blank",
    );
  }

  function exportarCSV() {
    const form = document.getElementById("formFiltros");
    const params = new URLSearchParams(new FormData(form)).toString();
    window.open(
      "/modulos/censura/cartas/cartas_view.php?acao=exportar_csv&" + params,
      "_blank",
    );
  }

  // Event Listeners
  $(document).ready(function () {
    // Auto-complete para internos
    $("#ipenInterno").on("input", async function () {
      const termo = $(this).val();
      if (termo.length < 3) {
        $("#resultadosBusca").hide();
        return;
      }
      const data = await buscarInterno(termo);
      if (data.status === "success") {
        drawSuggest(
          "resultadosBusca",
          data.dados,
          (r) =>
            `${r.ipen} - ${r.nome_social || r.nome} (${r.galeria || ""}${r.bloco || ""}-${r.res || ""})`,
          (r) => {
            $("#hiddenIpen").val(r.ipen);
            $("#displayInterno").val(`${r.ipen} - ${r.nome_social || r.nome}`);

            // Formatar localização no padrão correto: GALERIA+BLOCO-CELA
            const galeria = r.galeria || "";
            const bloco = r.bloco || "";
            const cela = r.res || "";
            const localizacao = `${galeria}${bloco}-${cela}`
              .replace(/--/g, "-")
              .replace(/^-|-$/g, "");

            $("#displayLocal").text(localizacao);
            console.log("Localização preenchida:", localizacao, "Dados:", {
              galeria,
              bloco,
              cela,
            });
            atualizarDeParaHint();
          },
        );
      }
    });

    // Auto-complete para correspondentes
    $("#correspondenteNome").on("input", async function () {
      const termo = $(this).val();
      if (termo.length < 3) {
        $("#resultadosCorrespondente").hide();
        return;
      }
      const data = await buscarCorrespondente(termo);
      if (data.status === "success") {
        drawSuggest(
          "resultadosCorrespondente",
          data.dados,
          (r) => `${r.nome} (${r.vinculo || ""})`,
          (r) => preencherCorrespondente(r),
        );
      }
    });

    // Mudança de tipo
    $("#tipoMov").on("change", atualizarDeParaHint);

    // Formulário nova carta
    $("#formNovaCarta").on("submit", async function (e) {
      e.preventDefault();
      const formData = new FormData(this);
      formData.append("acao", "salvar_carta");

      try {
        const res = await fetch("modulos/censura/cartas/cartas_view.php", {
          method: "POST",
          body: formData,
        });
        const data = await res.json();

        if (data.status === "success") {
          mostrarNotificacao("Carta cadastrada com sucesso!", "success");
          $("#modalNovaCarta").modal("hide");
          if (typeof loadPage === "function")
            loadPage(
              "modulos/censura/cartas/cartas_view.php",
              "Cartas",
              "Censura",
            );
          else location.reload();
        } else {
          mostrarNotificacao("Erro: " + data.msg, "danger");
        }
      } catch (error) {
        mostrarNotificacao("Falha na comunicação", "danger");
      }
    });

    // Formulário status
    $("#formStatusCarta").on("submit", async function (e) {
      e.preventDefault();
      const formData = new FormData(this);
      formData.append("acao", "atualizar_status");

      try {
        const res = await fetch("modulos/censura/cartas/cartas_view.php", {
          method: "POST",
          body: formData,
        });
        const data = await res.json();

        if (data.status === "success") {
          mostrarNotificacao("Status atualizado com sucesso!", "success");
          $("#modalStatusCarta").modal("hide");
          if (typeof loadPage === "function")
            loadPage(
              "modulos/censura/cartas/cartas_view.php",
              "Cartas",
              "Censura",
            );
          else location.reload();
        } else {
          mostrarNotificacao("Erro: " + data.msg, "danger");
        }
      } catch (error) {
        mostrarNotificacao("Falha na comunicação", "danger");
      }
    });

    // Fechar sugestões ao clicar fora
    $(document).on("click", function (e) {
      if (!$(e.target).closest("#ipenInterno, #resultadosBusca").length) {
        $("#resultadosBusca").hide();
      }
      if (
        !$(e.target).closest("#correspondenteNome, #resultadosCorrespondente")
          .length
      ) {
        $("#resultadosCorrespondente").hide();
      }
    });

    // Atualizar estatísticas a cada 30 segundos
    setInterval(async function () {
      try {
        const res = await fetch(
          "modulos/censura/cartas/cartas_view.php?acao=stats",
        );
        const data = await res.json();
        if (data.status === "success") {
          $("#stats-total").text(data.total);
          $("#stats-liberadas").text(data.liberadas);
          $("#stats-retidas").text(data.retidas);
          $("#stats-devolvidas").text(data.devolvidas);
        }
      } catch (error) {
        console.error("Erro ao atualizar estatísticas:", error);
      }
    }, 30000);
  });

  // Fechar bloco de proteção contra múltiplos carregamentos
} // fim do if (typeof window.cartasLoaded === 'undefined')
