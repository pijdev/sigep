(function () {
  "use strict";

  class EclusaEscolta {
    constructor() {
      // Ajustar endpoint para nova estrutura MVC
      this.endpoint = "/modulos/eclusa/escolta/escolta_logica.php";

      this.lastKpiTitle = "Detalhes";
      this.lastKpiContentHtml = "";
      this.currentPage = 1;
      this.totalPages = 1;
      this.currentFilters = {};
      this.debounceTimers = {};
      this.saving = false;
      this.deleting = false;

      this.form = document.getElementById("movimentacaoForm");
      this.offcanvasMov = document.getElementById("offcanvasMovimentacao");
      this.offcanvasRelatorio = document.getElementById("offcanvasRelatorio");
      this.offcanvasChegada = document.getElementById("offcanvasChegada");
      this.offcanvasFinalizar = document.getElementById("offcanvasFinalizar");
      this.offcanvasKpi = document.getElementById("offcanvasKpiDetalhes");

      this.init();
    }

    init() {
      this.bindEvents();
      this.bindAutocomplete();
      this.setDefaultFormDate();
      this.loadFilterOptions();
      this.reloadAll();
    }

    bindEvents() {
      // Monitorar campo de placa para aplicar estilos visuais
      const placaInput = document.getElementById("placa");
      if (placaInput) {
        placaInput.addEventListener("input", () =>
          this.updatePlateStyle(placaInput),
        );
        // Inicializar estilo se já houver valor
        this.updatePlateStyle(placaInput);
      }

      document
        .getElementById("btnNovaMovimentacao")
        ?.addEventListener("click", () => this.openFormForCreate());
      document
        .getElementById("btnAbrirRelatorio")
        ?.addEventListener("click", () =>
          this.openOffcanvas(this.offcanvasRelatorio),
        );
      document
        .getElementById("btnAtualizarTudo")
        ?.addEventListener("click", () => this.reloadAll());

      document
        .getElementById("filtrosMovForm")
        ?.addEventListener("submit", (event) => {
          event.preventDefault();
          this.applyFilters();
        });

      document
        .getElementById("btnLimparFiltros")
        ?.addEventListener("click", () => {
          const form = document.getElementById("filtrosMovForm");
          form?.reset();
          this.currentFilters = {};
          this.currentPage = 1;
          this.loadEscoltas();
        });

      this.form?.addEventListener("submit", (event) => {
        event.preventDefault();
        this.saveEscolta();
      });

      const finalizarForm = document.getElementById("finalizarForm");
      finalizarForm?.addEventListener("submit", (event) => {
        event.preventDefault();
        this.finalizarEscolta();
      });

      const chegadaForm = document.getElementById("chegadaForm");
      chegadaForm?.addEventListener("submit", (event) => {
        event.preventDefault();
        this.registrarChegada();
      });

      document.getElementById("movPrev")?.addEventListener("click", () => {
        if (this.currentPage > 1) {
          this.currentPage -= 1;
          this.loadEscoltas();
        }
      });

      document.getElementById("movNext")?.addEventListener("click", () => {
        if (this.currentPage < this.totalPages) {
          this.currentPage += 1;
          this.loadEscoltas();
        }
      });

      document
        .getElementById("btnGerarRelatorio")
        ?.addEventListener("click", () => this.gerarRelatorio());
      document
        .getElementById("btnImprimirKpi")
        ?.addEventListener("click", () => this.printKpiDetails());

      // Eventos para dropdown de destinos comuns
      document.querySelectorAll(".destino-comum").forEach((item) => {
        item.addEventListener("click", (event) => {
          event.preventDefault();
          const destino = event.target.getAttribute("data-destino");
          const destinoInput = document.getElementById("destino");
          if (destinoInput) {
            destinoInput.value = destino;
            destinoInput.focus();
          }
        });
      });

      document.querySelectorAll("[data-offcanvas-close]").forEach((btn) => {
        btn.addEventListener("click", (event) => {
          const offcanvas = event.target.closest(".offcanvas-eclusa");
          this.closeOffcanvas(offcanvas);
        });
      });

      document.querySelectorAll(".kpi-card").forEach((card) => {
        card.addEventListener("click", () => {
          const kpi = card.getAttribute("data-kpi") || "";
          this.showKpiDetails(kpi);
        });
      });

      document
        .getElementById("movTableBody")
        ?.addEventListener("click", (event) => {
          const button = event.target.closest("button[data-action]");
          if (!button) {
            return;
          }

          const action = button.getAttribute("data-action");
          const id = Number(button.getAttribute("data-id"));
          if (!id) {
            return;
          }

          if (action === "edit") {
            this.openFormForEdit(id);
          }
          if (action === "arrival") {
            this.openArrivalModal(id);
          }
          if (action === "finalize") {
            this.openFinalizeModal(id);
          }
          if (action === "resumo") {
            this.showEscoltaResumo(id);
          }
          if (action === "delete") {
            this.deleteEscolta(id);
          }
        });
    }

    bindAutocomplete() {
      document
        .querySelectorAll(".busca-inteligente input[data-campo]")
        .forEach((input) => {
          const field = input.getAttribute("data-campo");
          if (!field) {
            return;
          }

          input.addEventListener("input", () => {
            const term = input.value.trim();
            clearTimeout(this.debounceTimers[field]);
            this.debounceTimers[field] = setTimeout(() => {
              this.fetchAutocomplete(field, term, input);
            }, 300);
          });

          input.addEventListener("focus", () => {
            const term = input.value.trim();
            if (term.length >= 0) {
              this.performAutocomplete(input);
            }
          });
        });

      document.addEventListener("click", (event) => {
        if (!event.target.closest(".busca-inteligente")) {
          document
            .querySelectorAll(".busca-inteligente-resultados")
            .forEach((box) => {
              box.style.display = "none";
            });
        }
      });
    }

    async fetchAutocomplete(field, term, input) {
      try {
        const result = await this.post({
          db_action: "busca_autocomplete",
          campo: field,
          termo: term,
        });

        this.showAutocompleteResults(
          input,
          Array.isArray(result.data) ? result.data : [],
        );
      } catch (error) {
        this.notifyError(error.message || "Erro ao buscar sugestões.");
      }
    }

    showAutocompleteResults(input, items) {
      const container = input.closest(".busca-inteligente");
      if (!container) {
        return;
      }

      const field = input.getAttribute("data-campo");

      let list = container.querySelector(".busca-inteligente-resultados");
      if (!list) {
        list = document.createElement("div");
        list.className = "busca-inteligente-resultados";
        list.style.cssText =
          "position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1px solid #ddd; border-top: none; max-height: 200px; overflow-y: auto; z-index: 1000;";
        container.appendChild(list);
      }

      list.innerHTML = "";
      if (items.length === 0) {
        list.style.display = "none";
        return;
      }

      items.forEach((item) => {
        const div = document.createElement("div");
        div.className = "autocomplete-item";
        div.style.cssText =
          "padding: 8px 12px; cursor: pointer; border-bottom: 1px solid #eee; display: flex; align-items: center; justify-content: space-between;";

        const value = item.label || item.value;

        if (field === "placa") {
          div.innerHTML = `<span>${this.getFormattedPlateHtml(value)}</span>`;
        } else {
          div.textContent = value;
        }

        div.addEventListener("click", () => {
          input.value = item.value;
          if (field === "placa") this.updatePlateStyle(input);
          list.style.display = "none";
        });
        div.addEventListener("mouseenter", () => {
          div.style.backgroundColor = "#f5f5f5";
        });
        div.addEventListener("mouseleave", () => {
          div.style.backgroundColor = "white";
        });
        list.appendChild(div);
      });

      list.style.display = "block";
    }

    hideAutocompleteResults(input) {
      const container = input.closest(".busca-inteligente");
      if (!container) {
        return;
      }

      const list = container.querySelector(".busca-inteligente-resultados");
      if (list) {
        list.style.display = "none";
      }
    }

    performAutocomplete(input) {
      const field = input.getAttribute("data-campo");
      const term = input.value.trim();

      if (term.length >= 0) {
        this.fetchAutocomplete(field, term, input);
      }
    }

    async loadFilterOptions() {
      try {
        const [destinos, motoristas, placas, internos] = await Promise.all([
          this.post({
            db_action: "busca_autocomplete",
            campo: "destino",
            termo: "",
          }),
          this.post({
            db_action: "busca_autocomplete",
            campo: "motorista",
            termo: "",
          }),
          this.post({
            db_action: "busca_autocomplete",
            campo: "placa",
            termo: "",
          }),
          this.post({
            db_action: "busca_autocomplete",
            campo: "interno",
            termo: "",
          }),
        ]);

        this.populateSelect("filtro_destino", destinos.data || []);
        this.populateSelect("filtro_motorista", motoristas.data || []);
        this.populateSelect("filtro_placa", placas.data || []);
        this.populateSelect("filtro_interno", internos.data || []);
      } catch (error) {
        console.error("Erro ao carregar filtros:", error);
      }
    }

    populateSelect(selectId, items) {
      const select = document.getElementById(selectId);
      if (!select) return;

      const currentValue = select.value;

      // Limpa opções exceto a primeira
      while (select.children.length > 1) {
        select.removeChild(select.lastChild);
      }

      items.forEach((item) => {
        const option = document.createElement("option");
        option.value = item.value;
        option.textContent = item.label || item.value;
        select.appendChild(option);
      });

      select.value = currentValue;
    }

    setDefaultFormDate() {
      if (this.form) {
        const dataInput = this.form.querySelector(
          'input[name="data_cadastro"]',
        );
        if (dataInput && !dataInput.value) {
          dataInput.value = new Date().toISOString().split("T")[0];
        }
      }
    }

    async reloadAll() {
      await Promise.all([this.loadContadores(), this.loadEscoltas()]);
    }

    async loadContadores() {
      try {
        const result = await this.post({ db_action: "get_contadores" });
        const data = result.data || {};

        document.getElementById("kpiTotal").textContent =
          data.totalEscoltas || 0;
        document.getElementById("kpiHoje").textContent = data.escoltasHoje || 0;
        document.getElementById("kpiEntradas").textContent =
          data.finalizadasHoje || 0;
        document.getElementById("kpiSaidas").textContent =
          data.pendentesHoje || 0;
      } catch (error) {
        console.error("Erro ao carregar contadores:", error);
      }
    }

    async loadEscoltas() {
      try {
        const tbody = document.getElementById("movTableBody");
        if (tbody) {
          tbody.innerHTML = `
                        <tr>
                            <td colspan="11" class="text-center py-4 text-muted">
                                <i class="fas fa-spinner fa-spin mr-2"></i>Carregando...
                            </td>
                        </tr>
                    `;
        }

        const result = await this.post({
          db_action: "listar",
          page: this.currentPage,
          limit: 20,
          ...this.currentFilters,
        });

        this.renderEscoltasTable(result.data);
        this.updatePagination(result.data);
      } catch (error) {
        this.notifyError(error.message || "Erro ao carregar escoltas.");
      }
    }

    renderEscoltasTable(data) {
      const tbody = document.getElementById("movTableBody");
      if (!tbody) return;

      const rows = data.dados || [];

      if (rows.length === 0) {
        tbody.innerHTML = `
                    <tr>
                        <td colspan="11" class="text-center py-4 text-muted">
                            <i class="fas fa-info-circle mr-2"></i>Nenhuma escolta encontrada.
                        </td>
                    </tr>
                `;
        return;
      }

      tbody.innerHTML = rows.map((row) => this.renderEscoltaRow(row)).join("");
    }

    renderEscoltaRow(row) {
      const statusBadge = this.getStatusBadge(row.status);
      const notBadge =
        row.eh_not === "Sim"
          ? '<span class="badge badge-warning">NOT</span>'
          : '<span class="badge badge-secondary">Normal</span>';

      // Verificar se é cancelado sem motivo
      const canceladoSemMotivo =
        row.status?.toLowerCase() === "cancelado" &&
        (!row.motivo || row.motivo.trim() === "");

      // Verificar campos obrigatórios faltantes (só para status que não seja "pendente")
      const camposObrigatorios = [];

      // Só verificar campos se não estiver pendente (pois pendente naturalmente faltam dados)
      if (row.status?.toLowerCase() !== "pendente") {
        if (!row.cadastrado_por || row.cadastrado_por.trim() === "") {
          camposObrigatorios.push("Cadastro");
        }

        if (!row.interno || row.interno.trim() === "") {
          camposObrigatorios.push("Interno");
        }

        if (!row.destino || row.destino.trim() === "") {
          camposObrigatorios.push("Destino");
        }

        if (!row.hora_prevista || row.hora_prevista.trim() === "") {
          camposObrigatorios.push("Hora Prevista");
        }

        if (!row.placa || row.placa.trim() === "") {
          camposObrigatorios.push("Placa");
        }

        if (!row.motorista || row.motorista.trim() === "") {
          camposObrigatorios.push("Motorista");
        }

        if (!row.hora_chegada || row.hora_chegada.trim() === "") {
          camposObrigatorios.push("Hora de Chegada");
        }

        if (!row.hora_retorno || row.hora_retorno.trim() === "") {
          camposObrigatorios.push("Hora de Retorno");
        }
      }

      // Verificar se está finalizado com sucesso e COMPLETO
      const finalizadoCompleto =
        row.status?.toLowerCase() === "finalizado" &&
        row.cadastrado_por &&
        row.cadastrado_por.trim() !== "" &&
        row.interno &&
        row.interno.trim() !== "" &&
        row.destino &&
        row.destino.trim() !== "" &&
        row.hora_prevista &&
        row.hora_prevista.trim() !== "" &&
        row.placa &&
        row.placa.trim() !== "" &&
        row.motorista &&
        row.motorista.trim() !== "" &&
        row.hora_chegada &&
        row.hora_chegada.trim() !== "" &&
        row.hora_retorno &&
        row.hora_retorno.trim() !== "";

      // Criar badge apropriado
      let dadosIncompletosBadge = "";
      let rowClass = "";

      if (canceladoSemMotivo) {
        dadosIncompletosBadge = `<span class="badge badge-danger ml-2" title="Cancelada sem motivo">
            <i class="fas fa-times mr-1"></i>Cancelada sem Motivo!
          </span>`;
        rowClass = "table-danger";
      } else if (finalizadoCompleto) {
        dadosIncompletosBadge = `<span class="badge badge-success ml-2" title="Finalizada com sucesso">
            <i class="fas fa-check mr-1"></i>Concluída
          </span>`;
        rowClass = "table-success";
      } else if (camposObrigatorios.length > 0) {
        dadosIncompletosBadge = `<span class="badge badge-warning ml-2" title="Campos obrigatórios faltantes: ${camposObrigatorios.join(", ")}">
            <i class="fas fa-exclamation-triangle mr-1"></i>Dados Incompletos
          </span>`;
        rowClass = "table-warning";
      }

      // Aplicar estilo de placa na tabela
      const placaFormatada = this.getFormattedPlateHtml(row.placa);

      return `
                <tr class="${rowClass}">
                    <td>${this.formatDate(row.data_cadastro)}${dadosIncompletosBadge}</td>
                    <td>${row.interno || "-"}</td>
                    <td>${row.destino || "-"}</td>
                    <td>${row.motorista || "-"}</td>
                    <td>${placaFormatada}</td>
                    <td>${statusBadge}</td>
                    <td>${row.hora_prevista || "-"}</td>
                    <td>${row.hora_chegada || "-"}</td>
                    <td>${row.hora_retorno || "-"}</td>
                    <td>${notBadge}</td>
                    <td class="text-center">
                        ${
                          row.status === "Pendente"
                            ? `
                            ${
                              !row.hora_chegada
                                ? `
                                <button class="btn btn-sm btn-outline-info" data-action="arrival" data-id="${row.id}" title="Registrar Chegada da Polícia">
                                    <i class="fas fa-clock"></i>
                                </button>
                            `
                                : ""
                            }
                            <button class="btn btn-sm btn-outline-success" data-action="finalize" data-id="${row.id}" title="Finalizar Escolta">
                                <i class="fas fa-check"></i>
                            </button>
                        `
                            : ""
                        }
                        <button class="btn btn-sm btn-outline-primary" data-action="edit" data-id="${row.id}" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        ${
                          row.status?.toLowerCase() === "finalizado" ||
                          row.status?.toLowerCase() === "cancelado"
                            ? `
                            <button class="btn btn-sm btn-info" data-action="resumo" data-id="${row.id}" title="Resumo da Escolta">
                                <i class="fas fa-chart-pie"></i>
                            </button>
                        `
                            : ""
                        }
                        <button class="btn btn-sm btn-outline-danger" data-action="delete" data-id="${row.id}" title="Excluir">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
    }

    getStatusBadge(status) {
      const badges = {
        Pendente: '<span class="badge badge-warning">Pendente</span>',
        Finalizado: '<span class="badge badge-success">Finalizado</span>',
        Cancelado: '<span class="badge badge-danger">Cancelado</span>',
      };
      return (
        badges[status] ||
        '<span class="badge badge-secondary">Desconhecido</span>'
      );
    }

    updatePagination(data) {
      this.currentPage = data.pagina || 1;
      this.totalPages = data.total_paginas || 1;

      const pageInfo = document.getElementById("movPageInfo");
      if (pageInfo) {
        pageInfo.textContent = `Página ${this.currentPage} de ${this.totalPages} (${data.total} registros)`;
      }

      const prevBtn = document.getElementById("movPrev");
      const nextBtn = document.getElementById("movNext");

      if (prevBtn) prevBtn.disabled = this.currentPage <= 1;
      if (nextBtn) nextBtn.disabled = this.currentPage >= this.totalPages;
    }

    applyFilters() {
      const form = document.getElementById("filtrosMovForm");
      if (!form) return;

      const formData = new FormData(form);
      this.currentFilters = {};

      for (const [key, value] of formData.entries()) {
        if (value.trim()) {
          this.currentFilters[key] = value.trim();
        }
      }

      this.currentPage = 1;
      this.loadEscoltas();
    }

    openFormForCreate() {
      if (!this.form) return;

      this.form.reset();
      this.form.querySelector('input[name="db_action"]').value = "salvar";
      this.form.querySelector('input[name="id"]').value = "";
      document.getElementById("tituloMovForm").textContent = "Nova Escolta";

      const placaInput = this.form.querySelector('input[name="placa"]');
      if (placaInput) {
        this.updatePlateStyle(placaInput);
      }

      this.setDefaultFormDate();
      this.openOffcanvas(this.offcanvasMov);
    }

    async openFormForEdit(id) {
      try {
        const result = await this.post({ db_action: "obter", id });
        const escolta = result.data;

        if (!escolta) {
          this.notifyError("Escolta não encontrada.");
          return;
        }

        this.populateForm(escolta);
        document.getElementById("tituloMovForm").textContent = "Editar Escolta";
        this.openOffcanvas(this.offcanvasMov);
      } catch (error) {
        this.notifyError(error.message || "Erro ao carregar escolta.");
      }
    }

    async openArrivalModal(id) {
      try {
        const result = await this.post({ db_action: "obter", id });
        const escolta = result.data;

        if (!escolta) {
          this.notifyError("Escolta não encontrada.");
          return;
        }

        // Preencher o formulário de chegada
        document.getElementById("chegada_id").value = escolta.id;
        document.getElementById("chegada_hora").value = "";

        // Definir hora atual como padrão
        const agora = new Date();
        const horaAtual = agora.toTimeString().slice(0, 5);
        document.getElementById("chegada_hora").value = horaAtual;

        this.openOffcanvas(this.offcanvasChegada);
      } catch (error) {
        this.notifyError(error.message || "Erro ao carregar escolta.");
      }
    }

    async registrarChegada() {
      const form = document.getElementById("chegadaForm");
      if (!form) return;

      const formData = new FormData(form);
      const horaChegada = formData.get("hora_chegada");

      // Validações
      if (!horaChegada) {
        this.notifyError("Informe a hora de chegada.");
        return;
      }

      try {
        const result = await this.post({
          db_action: "registrar_chegada",
          id: formData.get("id"),
          hora_chegada: horaChegada,
        });

        this.notifySuccess(result.message || "Chegada registrada com sucesso.");
        this.closeOffcanvas(this.offcanvasChegada);
        this.reloadAll();
      } catch (error) {
        this.notifyError(error.message || "Erro ao registrar chegada.");
      }
    }

    async openFinalizeModal(id) {
      try {
        const result = await this.post({ db_action: "obter", id });
        const escolta = result.data;

        if (!escolta) {
          this.notifyError("Escolta não encontrada.");
          return;
        }

        // Preencher o formulário de finalização
        document.getElementById("finalizar_id").value = escolta.id;
        document.getElementById("status_finalizacao").value = "";
        document.getElementById("hora_retorno").value = "";
        document.getElementById("motivo_finalizacao").value = "";

        // Definir hora atual como padrão para hora de retorno
        const agora = new Date();
        const horaAtual = agora.toTimeString().slice(0, 5);
        document.getElementById("hora_retorno").value = horaAtual;

        // Mostrar informação sobre chegada já registrada
        if (escolta.hora_chegada) {
          this.notifyInfo(`Chegada já registrada às ${escolta.hora_chegada}`);
        }

        this.openOffcanvas(this.offcanvasFinalizar);
      } catch (error) {
        this.notifyError(error.message || "Erro ao carregar escolta.");
      }
    }

    async finalizarEscolta() {
      const form = document.getElementById("finalizarForm");
      if (!form) return;

      const formData = new FormData(form);
      const escoltaId = formData.get("id");
      const status = formData.get("status_finalizacao");
      const horaRetorno = formData.get("hora_retorno");
      const motivo = formData.get("motivo_finalizacao");

      // Validações básicas do formulário
      if (!status) {
        this.notifyError("Selecione o status da finalização.");
        return;
      }

      if (!horaRetorno) {
        this.notifyError("Informe a hora de retorno.");
        return;
      }

      if (status !== "Finalizado" && !motivo) {
        this.notifyError(
          'O motivo é obrigatório para status diferente de "Finalizado".',
        );
        return;
      }

      // VALIDAÇÃO ADICIONAL: Verificar campos obrigatórios da escolta
      try {
        const result = await this.post({ db_action: "obter", id: escoltaId });
        const escolta = result.data;

        if (!escolta) {
          this.notifyError("Escolta não encontrada.");
          return;
        }

        // Debug: mostrar no console o que está sendo verificado
        console.log("DEBUG - Verificando escolta:", {
          id: escoltaId,
          status: escolta?.status || "N/A",
          cadastrado_por: escolta?.cadastrado_por || "N/A",
          interno: escolta?.interno || "N/A",
          destino: escolta?.destino || "N/A",
          hora_prevista: escolta?.hora_prevista || "N/A",
          placa: escolta?.placa || "N/A",
          motorista: escolta?.motorista || "N/A",
          hora_chegada: escolta?.hora_chegada || "N/A",
          hora_retorno: escolta?.hora_retorno || "N/A",
          motivo: escolta?.motivo || "N/A",
        });

        // Debug: mostrar dados do formulário
        console.log("DEBUG - Dados do formulário:", {
          id: formData.get("id"),
          status: formData.get("status_finalizacao"),
          hora_retorno: formData.get("hora_retorno"),
          motivo: formData.get("motivo_finalizacao"),
        });

        // Verificar campos obrigatórios faltantes (só para status que não seja "pendente")
        const camposObrigatorios = [];

        // Só verificar campos se não estiver pendente (pois pendente naturalmente faltam dados)
        if (
          escolta &&
          escolta.status &&
          escolta.status.toLowerCase() !== "pendente"
        ) {
          if (!escolta.cadastrado_por || escolta.cadastrado_por.trim() === "") {
            camposObrigatorios.push("Cadastro");
          }

          if (!escolta.interno || escolta.interno.trim() === "") {
            camposObrigatorios.push("Interno");
          }

          if (!escolta.destino || escolta.destino.trim() === "") {
            camposObrigatorios.push("Destino");
          }

          if (!escolta.hora_prevista || escolta.hora_prevista.trim() === "") {
            camposObrigatorios.push("Hora Prevista");
          }

          if (!escolta.placa || escolta.placa.trim() === "") {
            camposObrigatorios.push("Placa");
          }

          if (!escolta.motorista || escolta.motorista.trim() === "") {
            camposObrigatorios.push("Motorista");
          }

          if (!escolta.hora_chegada || escolta.hora_chegada.trim() === "") {
            camposObrigatorios.push("Hora de Chegada");
          }

          if (!escolta.hora_retorno || escolta.hora_retorno.trim() === "") {
            camposObrigatorios.push("Hora de Retorno");
          }
        }

        // Debug: mostrar campos faltantes
        console.log(
          "DEBUG - Campos obrigatórios faltantes:",
          camposObrigatorios,
        );

        // Se houver campos obrigatórios em branco, fechar offcanvas e mostrar warning
        if (camposObrigatorios.length > 0) {
          const camposStr = camposObrigatorios.join(", ");

          // Fechar o offcanvas de finalização
          this.closeOffcanvas(this.offcanvasFinalizar);

          // Mostrar toast warning orientando para usar editar
          this.notifyWarning(
            `Não é possível finalizar a escolta. Os seguintes campos obrigatórios estão em branco: ${camposStr}. Por favor, clique em editar nas ações do cadastro e preencha todos os campos obrigatórios.`,
          );

          return;
        }
      } catch (error) {
        console.error("DEBUG - Erro ao verificar dados da escolta:", error);
        this.notifyError("Erro ao verificar dados da escolta.");
        return;
      }

      // Se passou por todas as validações, prosseguir com finalização
      try {
        // Debug: mostrar dados que serão enviados
        console.log("DEBUG - Enviando requisição de finalização:", {
          id: escoltaId,
          status: status,
          hora_retorno: horaRetorno,
          motivo: motivo || null,
        });

        const result = await this.post({
          db_action: "finalizar",
          id: escoltaId,
          status_finalizacao: status, // Corrigido: nome do campo do formulário
          hora_retorno: horaRetorno,
          motivo_finalizacao: motivo || null, // Corrigido: nome do campo do formulário
        });

        this.notifySuccess(result.message || "Escolta finalizada com sucesso.");
        this.closeOffcanvas(this.offcanvasFinalizar);
        this.reloadAll();
      } catch (error) {
        console.error("DEBUG - Erro na finalização:", error);
        this.notifyError(error.message || "Erro ao finalizar escolta.");
      }
    }

    populateForm(data) {
      if (!this.form) return;

      this.form.querySelector('input[name="db_action"]').value = "salvar";
      this.form.querySelector('input[name="id"]').value = data.id || "";
      this.form.querySelector('input[name="data_cadastro"]').value =
        data.data_cadastro || "";
      this.form.querySelector('input[name="interno"]').value =
        data.interno || "";
      this.form.querySelector('input[name="destino"]').value =
        data.destino || "";
      this.form.querySelector('select[name="status"]').value =
        data.status || "Pendente";
      this.form.querySelector('input[name="hora_prevista"]').value =
        data.hora_prevista || "";
      this.form.querySelector('input[name="hora_chegada"]').value =
        data.hora_chegada || "";
      this.form.querySelector('input[name="hora_retorno"]').value =
        data.hora_retorno || "";
      this.form.querySelector('input[name="motivo"]').value = data.motivo || "";

      const placaInput = this.form.querySelector('input[name="placa"]');
      if (placaInput) {
        placaInput.value = data.placa || "";
        this.updatePlateStyle(placaInput);
      }

      this.form.querySelector('input[name="motorista"]').value =
        data.motorista || "";
      this.form.querySelector('select[name="eh_not"]').value =
        data.eh_not || "Não";
      this.form.querySelector('input[name="cadastrado_por"]').value =
        data.cadastrado_por || "";
    }

    async saveEscolta() {
      if (!this.form) return;

      const formData = new FormData(this.form);
      const destino = formData.get("destino");
      const interno = formData.get("interno");

      // Validação client-side para destinos genéricos
      if (destino) {
        const destinosProibidos = [
          "CONSULTA EXTERNA",
          "CONSULTA",
          "EXTERNO",
          "EXTERNA",
          "SAÍDA",
          "SAIDA",
          "TRÂNSITO",
          "TRANSITO",
          "EM TRÂNSITO",
          "EM TRANSITO",
          "AVALIAÇÃO",
          "AVALIACAO",
          "ATENDIMENTO",
          "ATENDIMENTO EXTERNO",
          "REVISÃO",
          "REVISAO",
          "PERÍCIA",
          "PERICIA",
          "AUDIÊNCIA",
          "AUDIENCIA",
        ];

        const destinoUpper = destino.toUpperCase();

        for (const proibido of destinosProibidos) {
          if (destinoUpper.includes(proibido)) {
            this.notifyError(
              `Destino "${destino}" é muito genérico. Seja específico: "Hospitão do Joãozinho" em vez de "${proibido}".`,
            );
            return;
          }
        }

        if (destino.trim().length < 5) {
          this.notifyError("Destino muito curto. Informe o local completo.");
          return;
        }
      }

      // Validação client-side para formato do interno
      if (interno) {
        // Verificar se está no padrão IPEN - NOME
        if (!/^(\d{6,})\s*-\s*(.+)$/i.test(interno.trim())) {
          if (/^\d+$/.test(interno.trim())) {
            this.notifyError(
              "Informe o nome completo. Use o formato: IPEN - NOME COMPLETO",
            );
            return;
          }

          if (interno.trim().length < 5) {
            this.notifyError(
              "Nome do interno muito curto. Use o formato: IPEN - NOME COMPLETO",
            );
            return;
          }
        }
      }

      try {
        this.saving = true;
        this.showLoading(true);

        const formData = new FormData(this.form);
        const data = Object.fromEntries(formData.entries());

        const result = await this.post(data);
        this.notifySuccess(result.message || "Escolta salva com sucesso.");

        this.closeOffcanvas(this.offcanvasMov);
        await this.reloadAll();
      } catch (error) {
        this.notifyError(error.message || "Erro ao salvar escolta.");
      } finally {
        this.saving = false;
        this.showLoading(false);
      }
    }

    async deleteEscolta(id) {
      if (this.deleting) return;

      try {
        console.log("DEBUG - Tentando obter dados da escolta ID:", id);

        // Obter dados da escolta para mostrar no modal
        const result = await this.post({ db_action: "obter", id });
        const escolta = result.data;

        console.log("DEBUG - Dados obtidos:", escolta);

        if (!escolta) {
          this.notifyError("Escolta não encontrada.");
          return;
        }

        // Configurar modal de exclusão
        this.setupDeleteModal(escolta);

        // Abrir modal
        $("#modalExcluirEscolta").modal("show");
      } catch (error) {
        console.error("DEBUG - Erro ao carregar dados da escolta:", error);
        this.notifyError(error.message || "Erro ao carregar dados da escolta.");
      }
    }

    setupDeleteModal(escolta) {
      console.log("DEBUG - Configurando modal com dados:", escolta);

      // Verificar se os elementos existem
      const elementoNumero = document.getElementById("escoltaNumero");
      const elementoNomeEsperado = document.getElementById("nomeEsperado");
      const elementoNomeInput = document.getElementById("nomeConfirmacao");

      console.log("DEBUG - Elementos encontrados:", {
        escoltaNumero: !!elementoNumero,
        nomeEsperado: !!elementoNomeEsperado,
        nomeConfirmacao: !!elementoNomeInput,
      });

      if (!elementoNumero || !elementoNomeEsperado || !elementoNomeInput) {
        console.error("DEBUG - Elementos do modal não encontrados!");
        this.notifyError("Erro ao preparar modal de exclusão.");
        return;
      }

      // Atualizar informações no modal
      elementoNumero.textContent = "#" + String(escolta.id).padStart(4, "0");
      elementoNomeEsperado.textContent =
        escolta.cadastrado_por || "NOME ESPERADO";

      // Atualizar placeholder do campo
      elementoNomeInput.placeholder =
        "Digite: " + (escolta.cadastrado_por || "NOME ESPERADO");
      elementoNomeInput.value = "";

      // Limpar eventos anteriores
      const btnConfirmar = document.getElementById("btnConfirmarExclusao");
      if (!btnConfirmar) {
        console.error("DEBUG - Botão confirmar não encontrado!");
        return;
      }

      const newBtn = btnConfirmar.cloneNode(true);
      btnConfirmar.parentNode.replaceChild(newBtn, btnConfirmar);

      // Adicionar evento de clique
      newBtn.addEventListener("click", () => {
        this.confirmarExclusao(escolta.id, escolta.cadastrado_por);
      });
    }

    async confirmarExclusao(id, nomeEsperado) {
      const nomeInput = document.getElementById("nomeConfirmacao");
      const nomeDigitado = nomeInput.value.trim();

      // Validar nome
      if (!nomeDigitado) {
        this.notifyError(
          "Por favor, digite seu nome para confirmar a exclusão.",
        );
        nomeInput.focus();
        return;
      }

      if (nomeDigitado.toLowerCase() !== nomeEsperado.toLowerCase()) {
        this.notifyError(
          "Nome digitado não corresponde ao nome de quem cadastrou a escolta.",
        );
        nomeInput.focus();
        nomeInput.select();
        return;
      }

      try {
        this.deleting = true;
        this.showLoading(true);

        // Fechar modal
        $("#modalExcluirEscolta").modal("hide");

        // Executar exclusão
        const result = await this.post({
          db_action: "excluir",
          id: id,
          nome_confirmacao: nomeDigitado,
        });

        this.notifySuccess(result.message || "Escolta excluída com sucesso.");
        await this.reloadAll();
      } catch (error) {
        this.notifyError(error.message || "Erro ao excluir escolta.");

        // Reabrir modal se houver erro
        $("#modalExcluirEscolta").modal("show");
        nomeInput.focus();
        nomeInput.select();
      } finally {
        this.deleting = false;
        this.showLoading(false);
      }
    }

    async gerarRelatorio() {
      try {
        this.showLoading(true);

        const form = document.getElementById("relatorioForm");
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());

        const result = await this.post({
          db_action: "gerar_relatorio",
          ...data,
        });

        this.notifySuccess(result.message || "Relatório gerado com sucesso.");

        if (result.redirect) {
          window.location.href = "/" + result.redirect;
        }
      } catch (error) {
        this.notifyError(error.message || "Erro ao gerar relatório.");
      } finally {
        this.showLoading(false);
      }
    }

    async showKpiDetails(kpi) {
      try {
        let data, title;

        switch (kpi) {
          case "total":
            const resultTotal = await this.post({
              db_action: "get_contadores",
            });
            data = resultTotal.data;
            title = "Total de Escoltas";
            break;
          case "veiculos":
            const resultDestinos = await this.post({
              db_action: "get_top_destinos",
            });
            data = resultDestinos.data;
            title = "Top Destinos";
            break;
          case "empresas":
            const resultMotoristas = await this.post({
              db_action: "get_top_motoristas",
            });
            data = resultMotoristas.data;
            title = "Top Motoristas";
            break;
          default:
            return;
        }

        this.renderKpiDetails(title, data);
        this.openOffcanvas(this.offcanvasKpi);
      } catch (error) {
        this.notifyError(error.message || "Erro ao carregar detalhes.");
      }
    }

    renderKpiDetails(title, data) {
      const content = document.getElementById("kpiDetalhesConteudo");
      const titleElement = document.getElementById("kpiDetalhesTitulo");

      if (titleElement) titleElement.textContent = title;

      html += "</div>";
      content.innerHTML = html;

      this.lastKpiTitle = title;
      this.lastKpiContentHtml = html;
    }

    formatKpiLabel(key) {
      const labels = {
        totalEscoltas: "Total de Escoltas",
        escoltasHoje: "Escoltas Hoje",
        finalizadasHoje: "Finalizadas Hoje",
        pendentesHoje: "Pendentes Hoje",
        escoltasMes: "Escoltas no Mês",
        totalNot: "Total NOT",
      };
      return labels[key] || key;
    }

    printKpiDetails() {
      const printWindow = window.open("", "_blank");
      printWindow.document.write(`
                <html>
                    <head>
                        <title>${this.lastKpiTitle}</title>
                        <style>
                            body { font-family: Arial, sans-serif; margin: 20px; }
                            .list-group { border: 1px solid #ddd; }
                            .list-group-item { padding: 10px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; }
                            .badge { background: #007bff; color: white; padding: 2px 8px; border-radius: 12px; }
                            h1 { color: #333; }
                        </style>
                    </head>
                    <body>
                        <h1>${this.lastKpiTitle}</h1>
                        ${this.lastKpiContentHtml}
                    </body>
                </html>
            `);
      printWindow.document.close();
      printWindow.print();
    }

    openOffcanvas(offcanvas) {
      if (!offcanvas) return;

      offcanvas.classList.add("show");
      document.body.classList.add("modal-open");

      // Criar backdrop
      let backdrop = document.querySelector(".offcanvas-backdrop");
      if (!backdrop) {
        backdrop = document.createElement("div");
        backdrop.className = "offcanvas-backdrop";
        backdrop.style.cssText =
          "position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1040;";
        document.body.appendChild(backdrop);
      }

      backdrop.addEventListener("click", () => this.closeOffcanvas(offcanvas));
    }

    closeOffcanvas(offcanvas) {
      if (!offcanvas) return;

      offcanvas.classList.remove("show");
      document.body.classList.remove("modal-open");

      const backdrop = document.querySelector(".offcanvas-backdrop");
      if (backdrop) {
        backdrop.remove();
      }
    }

    showLoading(show) {
      const overlay = document.getElementById("loadingOverlay");
      if (overlay) {
        overlay.style.display = show ? "flex" : "none";
      }
    }

    getFormattedPlateHtml(placa) {
      if (!placa || placa === "-") return "-";

      const value = placa.trim().toUpperCase();
      const mercosulRegex = /^[A-Z]{3}[0-9][A-Z][0-9]{2}$/;
      const antigaRegex = /^[A-Z]{3}-?[0-9]{4}$/;

      let className = "";
      if (mercosulRegex.test(value)) {
        className = "plate-mercosul";
      } else if (antigaRegex.test(value)) {
        className = "plate-antiga";
      }

      if (className) {
        return `<div class="plate-container"><span class="plate-style ${className}">${value}</span></div>`;
      }
      return value;
    }

    updatePlateStyle(input) {
      const value = input.value.trim().toUpperCase();
      const container = input.closest(".plate-container");
      if (!container) return;

      const mercosulRegex = /^[A-Z]{3}[0-9][A-Z][0-9]{2}$/;
      const antigaRegex = /^[A-Z]{3}-?[0-9]{4}$/;

      input.classList.remove("plate-mercosul", "plate-antiga");
      input.classList.add("plate-style");

      if (mercosulRegex.test(value)) {
        input.classList.add("plate-mercosul");
      } else if (antigaRegex.test(value)) {
        input.classList.add("plate-antiga");
      }
    }

    notifySuccess(message) {
      this.showToast(message, "success");
    }

    notifyError(message) {
      this.showToast(message, "error");
    }

    notifyInfo(message) {
      this.showToast(message, "info");
    }

    notifyWarning(message) {
      this.showToast(message, "warning");
    }
    showToast(message, type = "info") {
      const toast = document.createElement("div");
      toast.className = `alert alert-${type === "error" ? "danger" : type === "success" ? "success" : type === "warning" ? "warning" : "info"} alert-dismissible fade show position-fixed`;
      toast.style.cssText =
        "top: 20px; right: 20px; z-index: 9999; min-width: 300px;";
      toast.innerHTML = `
                ${message}
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            `;

      document.body.appendChild(toast);

      setTimeout(() => {
        toast.remove();
      }, 5000);

      toast.querySelector(".close")?.addEventListener("click", () => {
        toast.remove();
      });
    }

    async showEscoltaResumo(id) {
      try {
        this.showLoading(true);

        const result = await this.post({ db_action: "obter", id });
        const escolta = result.data;

        if (!escolta) {
          this.notifyError("Escolta não encontrada.");
          return;
        }

        // Criar modal com HTML personalizado baseado no modelo
        const modalHtml = `
          <div class="modal-escolta-overlay" id="modalEscolta${escolta.id}">
            <div class="modal-escolta-content">
              <div class="modal-escolta-header">
                <h2>📊 Escolta #${escolta.id}</h2>
                <span class="badge ${this.getStatusBadgeClass(escolta.status)}">${escolta.status}</span>
              </div>

              <div class="modal-escolta-grid">
                <div class="modal-escolta-card">
                  <strong>📋 Dados Principais</strong><br>
                  Data: ${this.formatDate(escolta.data_cadastro)}<br>
                  Interno: ${escolta.interno || "-"}<br>
                  Destino: ${escolta.destino || "-"}<br>
                  Motorista: ${escolta.motorista || "-"}<br>
                  Placa: ${this.getFormattedPlateHtml(escolta.placa)}
                </div>

                <div class="modal-escolta-card">
                  <strong>⏰ Horários</strong><br>
                  Prevista: ${escolta.hora_prevista || "-"}<br>
                  Chegada: ${escolta.hora_chegada || "-"}<br>
                  Retorno: ${escolta.hora_retorno || "-"}
                </div>

                <div class="modal-escolta-card modal-escolta-full">
                  <strong>📊 Status</strong><br><br>
                  <span class="badge ${this.getStatusBadgeClass(escolta.status)}">${escolta.status}</span>
                  <span class="badge badge-outline">NOT: ${escolta.eh_not || "Não"}</span>

                  <div class="modal-escolta-separator"></div>

                  <strong>📝 Motivo ${
                    escolta.status?.toLowerCase() !== "finalizado"
                      ? '<span class="text-danger">*</span> (obrigatório se status ≠ Finalizado)'
                      : ""
                  }</strong><br>
                  ${
                    escolta.status?.toLowerCase() !== "finalizado"
                      ? escolta.motivo
                        ? `<div class="alert alert-info mt-2 mb-0"><small><strong>Motivo informado:</strong><br>${escolta.motivo}</small></div>`
                        : `<div class="alert alert-danger mt-2 mb-0"><small><strong>⚠️ Motivo OBRIGATÓRIO não preenchido!</strong><br>Este campo é obrigatório quando o status não é "Finalizado".</small></div>`
                      : escolta.motivo
                        ? `<div class="alert alert-light mt-2 mb-0 border"><small><strong>Motivo informado:</strong><br>${escolta.motivo}</small></div>`
                        : `<small class="text-muted">Não aplicável (escolta finalizada com sucesso)</small>`
                  }

                  <div class="modal-escolta-separator"></div>

                  <small>
                    Cadastrado por: ${escolta.cadastrado_por || "-"}<br>
                    Criado em: ${escolta.criado_em || "-"}<br>
                    Atualizado em: ${escolta.atualizado_em || "-"}
                  </small>
                </div>
              </div>

              <div class="modal-escolta-close">&times;</div>
            </div>
          </div>
        `;

        // Adicionar CSS para o modal se ainda não existir
        if (!document.getElementById("modal-escolta-styles")) {
          const style = document.createElement("style");
          style.id = "modal-escolta-styles";
          style.textContent = `
            .modal-escolta-overlay {
              display: flex;
              position: fixed;
              top: 0; left: 0;
              width: 100%; height: 100%;
              background: rgba(0,0,0,0.5);
              align-items: center;
              justify-content: center;
              z-index: 9999;
            }
            .modal-escolta-content {
              background: #fff;
              width: 600px;
              border-radius: 12px;
              padding: 20px;
              box-shadow: 0 10px 25px rgba(0,0,0,0.2);
              position: relative;
              max-height: 90vh;
              overflow-y: auto;
            }
            .modal-escolta-header {
              display: flex;
              justify-content: space-between;
              align-items: center;
              margin-bottom: 15px;
            }
            .modal-escolta-header h2 {
              margin: 0;
              font-size: 1.5rem;
            }
            .badge {
              padding: 5px 10px;
              border-radius: 8px;
              font-size: 12px;
              font-weight: bold;
              display: inline-block;
            }
            .badge-success { background: #28a745; color: white; }
            .badge-warning { background: #ffc107; color: #212529; }
            .badge-danger { background: #dc3545; color: white; }
            .badge-primary { background: #007bff; color: white; }
            .badge-outline { border: 1px solid #999; color: #555; background: transparent; }
            .modal-escolta-grid {
              display: grid;
              grid-template-columns: 1fr 1fr;
              gap: 10px;
            }
            .modal-escolta-card {
              border: 1px solid #eee;
              border-radius: 10px;
              padding: 10px;
            }
            .modal-escolta-full { grid-column: span 2; }
            .modal-escolta-separator {
              border-top: 1px solid #eee;
              margin: 10px 0;
            }
            .modal-escolta-close {
              cursor: pointer;
              font-size: 24px;
              position: absolute;
              top: 15px;
              right: 15px;
              color: #999;
              width: 30px;
              height: 30px;
              display: flex;
              align-items: center;
              justify-content: center;
              border-radius: 50%;
              background: #f8f9fa;
            }
            .modal-escolta-close:hover {
              background: #e9ecef;
              color: #666;
            }
            @media (max-width: 768px) {
              .modal-escolta-content {
                width: 95%;
                margin: 10px;
              }
              .modal-escolta-grid {
                grid-template-columns: 1fr;
              }
              .modal-escolta-full {
                grid-column: span 1;
              }
            }
          `;
          document.head.appendChild(style);
        }

        // Adicionar modal ao body
        document.body.insertAdjacentHTML("beforeend", modalHtml);

        // Adicionar eventos para fechar o modal
        const modal = document.getElementById(`modalEscolta${escolta.id}`);
        const closeBtn = modal.querySelector(".modal-escolta-close");

        const closeModal = () => {
          modal.remove();
        };

        closeBtn.addEventListener("click", closeModal);
        modal.addEventListener("click", (e) => {
          if (e.target === modal) {
            closeModal();
          }
        });

        // Fechar com ESC
        const escHandler = (e) => {
          if (e.key === "Escape") {
            closeModal();
            document.removeEventListener("keydown", escHandler);
          }
        };
        document.addEventListener("keydown", escHandler);
      } catch (error) {
        this.notifyError(
          error.message || "Erro ao carregar resumo da escolta.",
        );
      } finally {
        this.showLoading(false);
      }
    }

    getStatusBadgeClass(status) {
      const classes = {
        Pendente: "badge-warning",
        Finalizado: "badge-success",
        Cancelado: "badge-danger",
        "Em andamento": "badge-primary",
      };
      return classes[status] || "badge-secondary";
    }

    formatDate(dateString) {
      if (!dateString) return "-";
      const date = new Date(dateString);
      return date.toLocaleDateString("pt-BR");
    }

    async post(data) {
      const formData = new FormData();
      Object.entries(data).forEach(([key, value]) => {
        formData.append(key, value);
      });

      try {
        const response = await fetch(this.endpoint, {
          method: "POST",
          body: formData,
          credentials: "same-origin",
        });

        if (!response.ok) {
          throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const result = await response.json();

        if (!result.success) {
          throw new Error(result.message || "Erro na resposta do servidor");
        }

        return result;
      } catch (error) {
        console.error("Erro na requisição:", error);
        throw error;
      }
    }
  }

  // Inicializar quando o DOM estiver pronto
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", () => new EclusaEscolta());
  } else {
    new EclusaEscolta();
  }
})();
