/**
 * SIGEP Canteiros de Trabalho - JavaScript Principal
 * Funcionalidades AJAX + UI AdminLTE para gerenciamento de canteiros
 */

// Proteger contra múltiplos carregamentos no SPA
if (typeof window.canteirosLoaded === "undefined") {
  window.canteirosLoaded = true;

  // Variáveis globais
  var currentData = {
    estatisticas: {},
  };

  // URLs do módulo
  const canteirosViewUrl = "/modulos/laboral/canteiros/canteiros_view.php";
  const canteirosLogicUrl = "/modulos/laboral/canteiros/canteiros_logica.php";

  // Inicialização quando documento estiver pronto
  $(document).ready(function () {
    inicializarComponentes();
    carregarDados();

    // Event listeners
    $(document).on("click", "#btn-salvar", function () {
      salvarItem();
    });

    // Evento de busca por Enter
    $(document).on("keypress", "#busca-canteiro", function (e) {
      if (e.which === 13) {
        buscarCanteiros();
        return false;
      }
    });

    // Evento de busca por Enter para internos
    $(document).on("keypress", "#busca-interno", function (e) {
      if (e.which === 13) {
        buscarInternos();
        return false;
      }
    });

    // Auto-refresh a cada 30 segundos
    setInterval(autoRefresh, 30000);
  });

  // Inicializar componentes
  function inicializarComponentes() {
    // Componentes básicos inicializados
  }

  // Carregar dados iniciais
  function carregarDados() {
    mostrarLoading(true);
    Promise.all([
      carregarEstatisticas(),
      carregarListaCanteiros(),
      carregarListaInternos(),
      carregarDadosAnalytics(),
    ])
      .then(function () {
        atualizarInterface();
        atualizarTimestamp();
      })
      .catch(function (error) {
        console.error("Erro ao carregar dados:", error);
        mostrarNotificacao("Erro ao carregar dados", "error");
      })
      .finally(function () {
        mostrarLoading(false);
      });
  }

  // Mostrar/ocultar indicador de loading
  function mostrarLoading(show) {
    const indicator = $("#loading-indicator");
    if (show) {
      indicator.show();
    } else {
      indicator.hide();
    }
  }

  // Atualizar timestamp da última atualização
  function atualizarTimestamp() {
    const agora = new Date();
    const hora = agora.getHours().toString().padStart(2, "0");
    const minuto = agora.getMinutes().toString().padStart(2, "0");
    const segundo = agora.getSeconds().toString().padStart(2, "0");
    $("#last-update").text(`Atualizado ${hora}:${minuto}:${segundo}`);
  }

  // Carregar estatísticas dos canteiros
  function carregarEstatisticas() {
    return postAction({ action: "estatisticas" }).then(function (response) {
      if (response.success) {
        currentData.estatisticas = response.data;
        atualizarEstatisticas();
      }
    });
  }

  // Atualizar cards de estatísticas
  function atualizarEstatisticas() {
    const stats = currentData.estatisticas;

    $("#stats-total-canteiros").text(stats.total_canteiros || 0);
    $("#stats-canteiros-ativos").text(stats.canteiros_ativos || 0);
    $("#stats-canteiros-vazios").text(stats.canteiros_vazios || 0);
    $("#stats-internos-trabalhando").text(stats.internos_trabalhando || 0);
    $("#stats-total-regalias").text(stats.total_regalias || 0);
    $("#stats-conveniados").text(stats.conveniados || 0);
  }

  // Ver detalhes do canteiro
  function verDetalhesCanteiro(canteiro) {
    postAction({ action: "detalhes", canteiro: canteiro })
      .then(function (response) {
        if (response.success) {
          mostrarDetalhesCanteiroModal(canteiro, response.data);
        }
      })
      .catch(function (error) {
        console.error("Erro detalhes canteiro:", error);
        mostrarNotificacao(
          "Erro ao carregar detalhes do canteiro: " + error.message,
          "error",
        );
      });
  }

  // Mostrar modal de detalhes
  function mostrarDetalhesCanteiroModal(canteiro, dados) {
    const modal = $("#modalDetalhesCanteiro");
    const conteudo = $("#conteudo-detalhes-canteiro");

    // Verificar se é canteiro vazio ou inexistente
    if (dados.status === "vazio") {
      conteudo.html(`
        <div class="text-center py-4">
          <i class="fas fa-lock fa-3x text-muted mb-3"></i>
          <h5>Canteiro ${canteiro}</h5>
          <p class="text-muted">Canteiro vazio - sem internos alocados</p>
        </div>
      `);
      modal.modal("show");
      return;
    }

    if (dados.status === "inexistente") {
      conteudo.html(`
        <div class="text-center py-4">
          <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
          <h5>Canteiro ${canteiro}</h5>
          <p class="text-muted">Canteiro inexistente na estrutura física</p>
        </div>
      `);
      modal.modal("show");
      return;
    }

    const internos = dados.internos || [];
    let html = `
        <div class="row mb-3">
            <div class="col-md-6">
                <h5><i class="fas fa-industry mr-2"></i>${canteiro}</h5>
                <p class="text-muted">Canteiro de Trabalho</p>
            </div>
            <div class="col-md-6 text-right">
                <span class="badge badge-info">${internos.length} internos</span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>IPEN</th>
                        <th>Nome</th>
                        <th>Local</th>
                        <th>Turno</th>
                        <th>Início</th>
                        <th>Término</th>
                    </tr>
                </thead>
                <tbody>
    `;

    if (internos.length === 0) {
      html += `
            <tr>
                <td colspan="6" class="text-center text-muted">
                    Nenhum interno trabalhando neste canteiro
                </td>
            </tr>
        `;
    } else {
      internos.forEach(function (interno) {
        const local = interno.galeria
          ? `${interno.galeria}-${interno.bloco}`
          : interno.bloco;

        html += `
                <tr>
                    <td><strong>${interno.ipen}</strong></td>
                    <td>${interno.nome}</td>
                    <td><span class="badge badge-info">${local}</span></td>
                    <td><small class="text-muted">${interno.turno_descricao || "-"}</small></td>
                    <td>${formatarData(interno.remicao_inicio)}</td>
                    <td>${formatarData(interno.liberacao_fim || interno.remicao_fim)}</td>
                </tr>
            `;
      });
    }

    html += `
                </tbody>
            </table>
        </div>
    `;

    conteudo.html(html);
    modal.modal("show");
  }

  // Ver turnos do canteiro
  function verTurnosCanteiro(canteiro) {
    postAction({ action: "detalhes_turnos", canteiro: canteiro })
      .then(function (response) {
        if (response.success) {
          mostrarModalTurnos(canteiro, response.data);
        } else {
          mostrarNotificacao("Erro ao carregar turnos do canteiro", "error");
        }
      })
      .catch(function (error) {
        mostrarNotificacao("Erro ao carregar turnos do canteiro", "error");
      });
  }

  // Mostrar modal de turnos
  function mostrarModalTurnos(canteiro, dados) {
    const modal = $("#modalTurnosCanteiro");
    const conteudo = $("#conteudo-turnos-canteiro");

    let html = `
        <div class="row mb-3">
            <div class="col-md-6">
                <h5><i class="fas fa-industry mr-2"></i>${canteiro}</h5>
                <p class="text-muted">Turnos de Trabalho</p>
            </div>
            <div class="col-md-6 text-right">
                <span class="badge badge-info">${dados.total_internos} internos</span>
            </div>
        </div>

        <div class="row">
    `;

    // Adicionar turnos como cards
    if (dados.turnos && dados.turnos.length > 0) {
      dados.turnos.forEach(function (turno) {
        const galerias_blocos_text = turno.galerias_blocos.join(", ");
        const horario_turno = getHorarioTurno(turno.turno_descricao);

        html += `
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="card">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-clock mr-2"></i>${turno.turno_descricao}
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12">
                                <strong>Horário:</strong><br>
                                <span class="text-muted">${horario_turno}</span>
                            </div>
                            <div class="col-12 mt-2">
                                <strong>Internos:</strong><br>
                                <span class="badge badge-primary">${turno.internos.length}</span>
                            </div>
                            <div class="col-12 mt-2">
                                <strong>Locais:</strong><br>
                                <small class="text-muted">${galerias_blocos_text || "Nenhum local"}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
      });
    } else {
      html += `
            <div class="col-12">
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle mr-2"></i>
                    Nenhum turno encontrado para este canteiro
                </div>
            </div>
        `;
    }

    html += `
        </div>
    `;

    conteudo.html(html);
    modal.modal("show");
  }

  // Utilitários
  function postAction(payload) {
    return fetch(canteirosLogicUrl, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
      },
      body: new URLSearchParams(payload).toString(),
    }).then(async function (response) {
      const json = await response.json();
      if (!response.ok || json.success === false) {
        throw new Error(json.message || "Erro ao processar a requisição.");
      }
      return json;
    });
  }

  function mostrarNotificacao(mensagem, tipo = "info") {
    if (typeof toastr !== "undefined") {
      toastr[tipo](mensagem);
    } else {
      console.log(`[${tipo.toUpperCase()}] ${mensagem}`);
    }
  }

  function formatarData(data) {
    if (!data) return "-";
    const date = new Date(data);
    return date.toLocaleDateString("pt-BR");
  }

  function getHorarioTurno(turno_descricao) {
    const horarios = {
      "Segunda-feira": "07:30 - 17:30",
      "Terça-feira": "07:30 - 17:30",
      "Quarta-feira": "07:30 - 17:30",
      "Quinta-feira": "07:30 - 17:30",
      "Sexta-feira": "07:30 - 17:30",
      "Sábado/Domingo": "07:30 - 17:30",
    };

    return horarios[turno_descricao] || "Horário não definido";
  }

  function atualizarInterface() {
    // Atualizar elementos da interface
    atualizarEstatisticas();
  }

  function autoRefresh() {
    carregarDados();
  }

  // Funções de Filtros
  function aplicarFiltros() {
    const galeria = $("#filtro-galeria").val();
    const canteiro = $("#filtro-canteiro").val();
    const status = $("#filtro-status").val();

    // Filtrar mapa de galerias
    filtrarMapaGalerias(galeria, canteiro, status);

    // Atualizar estatísticas com filtros
    atualizarEstatisticasComFiltros(galeria, canteiro, status);

    mostrarNotificacao("Filtros aplicados com sucesso", "success");
  }

  function limparFiltros() {
    $("#filtro-galeria").val("");
    $("#filtro-canteiro").val("");
    $("#filtro-status").val("");

    // Mostrar todos os elementos
    $(".card").removeClass("d-none");
    $(".small-box").parent().removeClass("d-none");

    // Recarregar estatísticas gerais
    carregarEstatisticas();

    mostrarNotificacao("Filtros limpos", "info");
  }

  function filtrarMapaGalerias(galeria, canteiro, status) {
    // Resetar todos os cards
    $(".card").removeClass("d-none");

    // Filtrar por galeria
    if (galeria) {
      $(".card").each(function () {
        const cardTitle = $(this).find(".card-title").text();
        if (
          !cardTitle.includes(`Galeria ${galeria}`) &&
          !cardTitle.includes("Área Industrial") &&
          !cardTitle.includes("Semiaberto") &&
          !cardTitle.includes(`Galeria H`)
        ) {
          $(this).addClass("d-none");
        }
      });
    }

    // Filtrar por canteiro
    if (canteiro) {
      $(".small-box").each(function () {
        const boxText = $(this).find("h3").text();
        const shouldHide =
          (canteiro === "CISER" && !boxText.includes("CISER")) ||
          (canteiro === "TUTTI" && !boxText.includes("TUTTI")) ||
          (canteiro === "TIGRE" && !boxText.includes("TIGRE")) ||
          (canteiro === "PLASBOHN" && !boxText.includes("PLASBOHN")) ||
          (canteiro === "COZINHA" && !boxText.includes("COZINHA")) ||
          (canteiro === "CARGA" && !boxText.includes("CARGA"));

        if (shouldHide) {
          $(this).parent().addClass("d-none");
        }
      });
    }

    // Filtrar por status
    if (status) {
      $(".small-box").each(function () {
        const hasClass = $(this).hasClass("bg-success"); // Apenas ativos
        const shouldHide =
          (status === "ativos" && !hasClass) ||
          (status === "vazios" && hasClass) ||
          (status === "regalia" && !$(this).find(".fa-star").length);

        if (shouldHide) {
          $(this).parent().addClass("d-none");
        }
      });
    }
  }

  function atualizarEstatisticasComFiltros(galeria, canteiro, status) {
    // Buscar estatísticas filtradas do backend
    return postAction({
      action: "estatisticas_filtradas",
      galeria: galeria,
      canteiro: canteiro,
      status: status,
    })
      .then(function (response) {
        if (response.success) {
          currentData.estatisticas = response.data;
          atualizarEstatisticas();
        }
      })
      .catch(function (error) {
        console.error("Erro ao carregar estatísticas filtradas:", error);
        mostrarNotificacao("Erro ao carregar estatísticas filtradas", "error");
      });
  }

  // Funções da Lista de Canteiros
  var listaCanteirosData = {
    pagina: 1,
    limite: 10,
    busca: "",
    status: "",
    ordenacao: "nome",
  };

  function carregarListaCanteiros() {
    mostrarLoading(true);

    return postAction({
      action: "lista_canteiros",
      pagina: listaCanteirosData.pagina,
      limite: listaCanteirosData.limite,
      busca: listaCanteirosData.busca,
      status: listaCanteirosData.status,
      ordenacao: listaCanteirosData.ordenacao,
    })
      .then(function (response) {
        if (response.success) {
          renderizarTabelaCanteiros(response.data);
          renderizarPaginacao(response.data);
        }
      })
      .catch(function (error) {
        console.error("Erro ao carregar lista de canteiros:", error);
        mostrarNotificacao("Erro ao carregar lista de canteiros", "error");
      })
      .finally(function () {
        mostrarLoading(false);
      });
  }

  function renderizarTabelaCanteiros(data) {
    const tbody = $("#corpo-tabela-canteiros");
    tbody.empty();

    if (data.canteiros.length === 0) {
      tbody.append(`
        <tr>
          <td colspan="7" class="text-center text-muted">
            <i class="fas fa-search fa-2x mb-2"></i>
            <p>Nenhum canteiro encontrado</p>
          </td>
        </tr>
      `);
      return;
    }

    data.canteiros.forEach(function (canteiro) {
      const statusBadge = getStatusBadge(canteiro.status);
      const internosBadge = getInternosBadge(canteiro.total_internos);

      const row = `
        <tr>
          <td><strong>${canteiro.canteiro_nome}</strong></td>
          <td>${canteiro.empresa}</td>
          <td>${statusBadge}</td>
          <td>${internosBadge}</td>
          <td><small>${canteiro.galerias || "-"}</small></td>
          <td><small>${canteiro.turnos || "-"}</small></td>
          <td>
            <div class="btn-group btn-group-sm">
              <button type="button" class="btn btn-info" onclick="verDetalhesCanteiro('${canteiro.canteiro_nome}')" title="Ver Detalhes">
                <i class="fas fa-eye"></i>
              </button>
              <button type="button" class="btn btn-warning" onclick="verTurnosCanteiro('${canteiro.canteiro_nome}')" title="Ver Turnos">
                <i class="fas fa-clock"></i>
              </button>
            </div>
          </td>
        </tr>
      `;
      tbody.append(row);
    });

    // Atualizar informações de paginação
    const inicio = (data.pagina - 1) * data.limite + 1;
    const fim = Math.min(data.pagina * data.limite, data.total);
    $("#registro-inicio").text(data.total > 0 ? inicio : 0);
    $("#registro-fim").text(fim);
    $("#registro-total").text(data.total);
  }

  function getStatusBadge(status) {
    switch (status) {
      case "ativo":
        return '<span class="badge badge-success">Ativo</span>';
      case "vazio":
        return '<span class="badge badge-secondary">Vazio</span>';
      case "inexistente":
        return '<span class="badge badge-dark">Inexistente</span>';
      default:
        return '<span class="badge badge-secondary">Desconhecido</span>';
    }
  }

  function getInternosBadge(total) {
    if (total === 0) {
      return '<span class="badge badge-light">0</span>';
    } else if (total <= 10) {
      return `<span class="badge badge-info">${total}</span>`;
    } else if (total <= 20) {
      return `<span class="badge badge-warning">${total}</span>`;
    } else {
      return `<span class="badge badge-danger">${total}</span>`;
    }
  }

  function renderizarPaginacao(data) {
    const pagination = $("#paginacao-canteiros");
    pagination.empty();

    if (data.total_paginas <= 1) return;

    // Botão anterior
    const prevDisabled = data.pagina === 1 ? "disabled" : "";
    pagination.append(`
      <li class="paginate_button page-item ${prevDisabled}">
        <a href="#" onclick="mudarPagina(${data.pagina - 1})" class="page-link">Anterior</a>
      </li>
    `);

    // Páginas numeradas
    const maxPages = 5;
    let startPage = Math.max(1, data.pagina - Math.floor(maxPages / 2));
    let endPage = Math.min(data.total_paginas, startPage + maxPages - 1);

    if (endPage - startPage < maxPages - 1) {
      startPage = Math.max(1, endPage - maxPages + 1);
    }

    for (let i = startPage; i <= endPage; i++) {
      const active = i === data.pagina ? "active" : "";
      pagination.append(`
        <li class="paginate_button page-item ${active}">
          <a href="#" onclick="mudarPagina(${i})" class="page-link">${i}</a>
        </li>
      `);
    }

    // Botão próximo
    const nextDisabled = data.pagina === data.total_paginas ? "disabled" : "";
    pagination.append(`
      <li class="paginate_button page-item ${nextDisabled}">
        <a href="#" onclick="mudarPagina(${data.pagina + 1})" class="page-link">Próximo</a>
      </li>
    `);
  }

  function mudarPagina(pagina) {
    listaCanteirosData.pagina = pagina;
    carregarListaCanteiros();
    return false;
  }

  function buscarCanteiros() {
    listaCanteirosData.busca = $("#busca-canteiro").val();
    listaCanteirosData.pagina = 1;
    carregarListaCanteiros();
  }

  function filtrarListaCanteiros() {
    listaCanteirosData.status = $("#filtro-status-lista").val();
    listaCanteirosData.pagina = 1;
    carregarListaCanteiros();
  }

  function ordenarListaCanteiros() {
    listaCanteirosData.ordenacao = $("#ordenacao-lista").val();
    listaCanteirosData.pagina = 1;
    carregarListaCanteiros();
  }

  function recarregarListaCanteiros() {
    carregarListaCanteiros();
  }

  function exportarListaCanteiros() {
    // Implementar exportação (CSV/Excel)
    mostrarNotificacao("Função de exportação em desenvolvimento", "info");
  }

  // Funções do Analytics Dashboard
  var analyticsData = {
    graficos: {},
    dados: null,
  };

  function carregarDadosAnalytics() {
    const periodo = $("#periodo-analytics").val();
    const tipo = $("#tipo-grafico").val();
    const agrupamento = $("#agrupamento").val();

    mostrarLoading(true);

    return postAction({
      action: "analytics",
      periodo: periodo,
      tipo: tipo,
      agrupamento: agrupamento,
    })
      .then(function (response) {
        if (response.success) {
          analyticsData.dados = response.data;
          renderizarGraficos(response.data);
          atualizarKPIs(response.data.kpis);
        }
      })
      .catch(function (error) {
        console.error("Erro ao carregar dados analytics:", error);
        mostrarNotificacao("Erro ao carregar dados analytics", "error");
      })
      .finally(function () {
        mostrarLoading(false);
      });
  }

  function renderizarGraficos(dados) {
    // Destruir gráficos existentes
    Object.values(analyticsData.graficos).forEach((grafico) => {
      if (grafico) grafico.destroy();
    });

    // Gráfico de Pizza - Empresas
    const ctxEmpresas = document
      .getElementById("graficoEmpresas")
      .getContext("2d");
    analyticsData.graficos.empresas = new Chart(ctxEmpresas, {
      type: "pie",
      data: {
        labels: dados.empresas.map((item) => item.empresa),
        datasets: [
          {
            data: dados.empresas.map((item) => item.total_internos),
            backgroundColor: [
              "#007bff",
              "#28a745",
              "#ffc107",
              "#dc3545",
              "#6f42c1",
              "#20c997",
              "#fd7e14",
              "#6c757d",
            ],
          },
        ],
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            position: "bottom",
          },
        },
      },
    });

    // Gráfico de Barras - Galerias
    const ctxGalerias = document
      .getElementById("graficoGalerias")
      .getContext("2d");
    analyticsData.graficos.galerias = new Chart(ctxGalerias, {
      type: "bar",
      data: {
        labels: dados.galerias.map((item) => "Galeria " + item.galeria),
        datasets: [
          {
            label: "Internos",
            data: dados.galerias.map((item) => item.total_internos),
            backgroundColor: "#007bff",
          },
        ],
      },
      options: {
        responsive: true,
        scales: {
          y: {
            beginAtZero: true,
          },
        },
      },
    });

    // Gráfico de Linhas - Evolução
    const ctxEvolucao = document
      .getElementById("graficoEvolucao")
      .getContext("2d");
    analyticsData.graficos.evolucao = new Chart(ctxEvolucao, {
      type: "line",
      data: {
        labels: dados.evolucao.map((item) => item.data),
        datasets: [
          {
            label: "Internos Trabalhando",
            data: dados.evolucao.map((item) => item.valor),
            borderColor: "#28a745",
            backgroundColor: "rgba(40, 167, 69, 0.1)",
            tension: 0.4,
          },
        ],
      },
      options: {
        responsive: true,
        scales: {
          y: {
            beginAtZero: false,
          },
        },
      },
    });

    // Gráfico de Donut - Regalias
    const ctxRegalias = document
      .getElementById("graficoRegalias")
      .getContext("2d");
    analyticsData.graficos.regalias = new Chart(ctxRegalias, {
      type: "doughnut",
      data: {
        labels: dados.regalias.map((item) => item.tipo),
        datasets: [
          {
            data: dados.regalias.map((item) => item.total),
            backgroundColor: ["#ffc107", "#6c757d"],
          },
        ],
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            position: "bottom",
          },
        },
      },
    });
  }

  function atualizarKPIs(kpis) {
    $("#kpitaxaOcupacao").text(kpis.taxa_ocupacao + "%");
    $("#kpicrescimento").text(kpis.crescimento + "%");
    $("#kpimediaCanteiro").text(kpis.media_canteiro);
    $("#kpicanteirosCriticos").text(kpis.canteiros_criticos);

    // Adicionar classes de cor baseadas nos valores
    if (kpis.taxa_ocupacao > 80) {
      $("#kpitaxaOcupacao").addClass("text-success");
    } else if (kpis.taxa_ocupacao > 50) {
      $("#kpitaxaOcupacao").addClass("text-warning");
    } else {
      $("#kpitaxaOcupacao").addClass("text-danger");
    }

    if (kpis.crescimento > 0) {
      $("#kpicrescimento").addClass("text-success");
    } else {
      $("#kpicrescimento").addClass("text-danger");
    }

    if (kpis.canteiros_criticos > 0) {
      $("#kpicanteirosCriticos").addClass("text-danger");
    } else {
      $("#kpicanteirosCriticos").addClass("text-success");
    }
  }

  function atualizarGraficos() {
    carregarDadosAnalytics();
  }

  function exportarGraficos() {
    // Implementar exportação de gráficos
    mostrarNotificacao(
      "Função de exportação de gráficos em desenvolvimento",
      "info",
    );
  }

  // Buscar interno por IPEN
  function buscarInternoPorIPEN(ipen) {
    mostrarLoading(true);

    return postAction({ action: "buscar_interno", ipen: ipen })
      .then(function (response) {
        if (response.success) {
          mostrarResultadoBuscaInterno(response.data);
        }
      })
      .catch(function (error) {
        console.error("Erro buscar interno:", error);
        mostrarNotificacao("Erro ao buscar interno: " + error.message, "error");
      })
      .finally(function () {
        mostrarLoading(false);
      });
  }

  function mostrarResultadoBuscaInterno(dados) {
    if (!dados.encontrado) {
      mostrarNotificacao(dados.mensagem, "warning");
      return;
    }

    if (!dados.trabalhando) {
      mostrarNotificacao(
        `${dados.interno.nome} (IPEN: ${dados.interno.ipen}) - ${dados.mensagem}`,
        "info",
      );
      return;
    }

    const interno = dados.interno;
    const mensagem = `
      <strong>${interno.nome}</strong> (IPEN: ${interno.ipen})<br>
      <small>Canteiro: ${interno.canteiro || "Não definido"}</small><br>
      <small>Galeria: ${interno.galeria}-${interno.bloco}</small><br>
      <small>Empresa: ${interno.estabelecimento || "Não definida"}</small><br>
      <small>Turno: ${interno.dias_semana || "Não definido"}</small>
    `;

    mostrarNotificacao(mensagem, "success", 10000);
  }

  // Debug de internos por empresa
  function debugEmpresa(empresa) {
    mostrarLoading(true);

    return postAction({ action: "debug_empresa", empresa: empresa })
      .then(function (response) {
        if (response.success) {
          console.log("=== DEBUG EMPRESA ===");
          console.log("Empresa:", response.data.empresa_procurada);
          console.log("Total encontrados:", response.data.total_encontrados);
          console.log("Internos:", response.data.internos);

          mostrarNotificacao(
            `Debug completo! ${response.data.total_encontrados} internos encontrados. Veja o console para detalhes.`,
            "info",
          );
        }
      })
      .catch(function (error) {
        console.error("Erro debug empresa:", error);
        mostrarNotificacao("Erro ao debug empresa: " + error.message, "error");
      })
      .finally(function () {
        mostrarLoading(false);
      });
  }

  // Funções de Exportação
  function exportarCSV(tipo = "canteiros") {
    mostrarLoading(true);

    // Criar form para download
    const form = document.createElement("form");
    form.method = "POST";
    form.action = "/modulos/laboral/canteiros/canteiros_logica.php";

    const inputAction = document.createElement("input");
    inputAction.type = "hidden";
    inputAction.name = "action";
    inputAction.value = "exportar_csv";
    form.appendChild(inputAction);

    const inputTipo = document.createElement("input");
    inputTipo.type = "hidden";
    inputTipo.name = "tipo";
    inputTipo.value = tipo;
    form.appendChild(inputTipo);

    // Adicionar filtros atuais se for lista de canteiros
    if (tipo === "canteiros") {
      const inputBusca = document.createElement("input");
      inputBusca.type = "hidden";
      inputBusca.name = "filtros[busca]";
      inputBusca.value = $("#busca-canteiro").val() || "";
      form.appendChild(inputBusca);

      const inputStatus = document.createElement("input");
      inputStatus.type = "hidden";
      inputStatus.name = "filtros[status]";
      inputStatus.value = $("#filtro-status-canteiros").val() || "";
      form.appendChild(inputStatus);
    }

    // Adicionar filtros atuais se for lista de internos
    if (tipo === "internos") {
      const inputBusca = document.createElement("input");
      inputBusca.type = "hidden";
      inputBusca.name = "filtros[busca]";
      inputBusca.value = $("#busca-interno").val() || "";
      form.appendChild(inputBusca);

      const inputGaleria = document.createElement("input");
      inputGaleria.type = "hidden";
      inputGaleria.name = "filtros[galeria]";
      inputGaleria.value = $("#filtro-galeria-internos").val() || "";
      form.appendChild(inputGaleria);

      const inputRegalia = document.createElement("input");
      inputRegalia.type = "hidden";
      inputRegalia.name = "filtros[regalia]";
      inputRegalia.value = $("#filtro-regalia").val() || "";
      form.appendChild(inputRegalia);
    }

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);

    mostrarLoading(false);
    mostrarNotificacao("Download do arquivo CSV iniciado", "success");
  }

  function exportarListaCanteirosCSV() {
    exportarCSV("canteiros");
  }

  function exportarListaInternosCSV() {
    exportarCSV("internos");
  }

  function exportarExcel(tipo = "canteiros") {
    // Para exportação Excel, usaremos CSV por enquanto
    // Futuramente pode ser implementado com biblioteca como PhpSpreadsheet
    mostrarNotificacao(
      "Exportação Excel será implementada como CSV formatado",
      "info",
    );
    exportarCSV(tipo);
  }

  function exportarGraficoComoImagem(graficoId) {
    const canvas = document.getElementById(graficoId);
    if (!canvas) {
      mostrarNotificacao("Gráfico não encontrado", "error");
      return;
    }

    // Converter canvas para imagem
    const url = canvas.toDataURL("image/png");
    const link = document.createElement("a");
    link.download = graficoId + "_" + new Date().getTime() + ".png";
    link.href = url;
    link.click();

    mostrarNotificacao("Gráfico exportado como imagem", "success");
  }

  function exportarTodosGraficos() {
    const graficos = [
      "graficoEmpresas",
      "graficoGalerias",
      "graficoEvolucao",
      "graficoRegalias",
    ];
    let count = 0;

    graficos.forEach(function (graficoId) {
      setTimeout(function () {
        exportarGraficoComoImagem(graficoId);
        count++;

        if (count === graficos.length) {
          mostrarNotificacao(
            "Todos os gráficos exportados com sucesso",
            "success",
          );
        }
      }, count * 500); // Pequeno delay entre downloads
    });
  }

  // Funções da Lista de Internos
  var listaInternosData = {
    pagina: 1,
    limite: 10,
    busca: "",
    galeria: "",
    bloco: "",
    regalia: "",
    empresa: "",
  };

  function carregarListaInternos() {
    mostrarLoading(true);

    return postAction({
      action: "lista_internos",
      pagina: listaInternosData.pagina,
      limite: listaInternosData.limite,
      busca: listaInternosData.busca,
      galeria: listaInternosData.galeria,
      bloco: listaInternosData.bloco,
      regalia: listaInternosData.regalia,
      empresa: listaInternosData.empresa,
    })
      .then(function (response) {
        if (response.success) {
          renderizarTabelaInternos(response.data);
          renderizarPaginacaoInternos(response.data);
        }
      })
      .catch(function (error) {
        console.error("Erro ao carregar lista de internos:", error);
        mostrarNotificacao("Erro ao carregar lista de internos", "error");
      })
      .finally(function () {
        mostrarLoading(false);
      });
  }

  function renderizarTabelaInternos(data) {
    const tbody = $("#corpo-tabela-internos");
    tbody.empty();

    if (data.internos.length === 0) {
      tbody.append(`
        <tr>
          <td colspan="8" class="text-center text-muted">
            <i class="fas fa-search fa-2x mb-2"></i>
            <p>Nenhum interno encontrado</p>
          </td>
        </tr>
      `);
      return;
    }

    data.internos.forEach(function (interno) {
      const regaliaBadge = getRegaliaBadge(interno.regalia);
      const statusBadge = getStatusBadgeInterno(interno.status);
      const local =
        interno.galeria && interno.bloco
          ? `${interno.galeria}-${interno.bloco}`
          : "-";

      const row = `
        <tr>
          <td><strong>${interno.ipen}</strong></td>
          <td>${interno.nome}</td>
          <td><span class="badge badge-info">${local}</span></td>
          <td><small>${interno.empresa_curta || "-"}</small></td>
          <td><small>${interno.turno || "-"}</small></td>
          <td>${regaliaBadge}</td>
          <td>${statusBadge}</td>
          <td>
            <div class="btn-group btn-group-sm">
              <button type="button" class="btn btn-primary" onclick="verFichaInterno('${interno.ipen}')" title="Ver Ficha">
                <i class="fas fa-user"></i>
              </button>
              <button type="button" class="btn btn-info" onclick="verHistoricoTrabalho('${interno.ipen}')" title="Ver Histórico">
                <i class="fas fa-history"></i>
              </button>
            </div>
          </td>
        </tr>
      `;
      tbody.append(row);
    });

    // Atualizar informações de paginação
    const inicio = (data.pagina - 1) * data.limite + 1;
    const fim = Math.min(data.pagina * data.limite, data.total);
    $("#interno-inicio").text(data.total > 0 ? inicio : 0);
    $("#interno-fim").text(fim);
    $("#interno-total").text(data.total);
  }

  function getRegaliaBadge(regalia) {
    if (regalia === "S") {
      return '<span class="badge badge-warning"><i class="fas fa-star mr-1"></i>Com Regalia</span>';
    } else {
      return '<span class="badge badge-secondary">Sem Regalia</span>';
    }
  }

  function getStatusBadgeInterno(status) {
    switch (status) {
      case "A":
        return '<span class="badge badge-success">Ativo</span>';
      case "I":
        return '<span class="badge badge-danger">Inativo</span>';
      default:
        return '<span class="badge badge-secondary">Desconhecido</span>';
    }
  }

  function renderizarPaginacaoInternos(data) {
    const pagination = $("#paginacao-internos");
    pagination.empty();

    if (data.total_paginas <= 1) return;

    // Botão anterior
    const prevDisabled = data.pagina === 1 ? "disabled" : "";
    pagination.append(`
      <li class="paginate_button page-item ${prevDisabled}">
        <a href="#" onclick="mudarPaginaInternos(${data.pagina - 1})" class="page-link">Anterior</a>
      </li>
    `);

    // Páginas numeradas
    const maxPages = 5;
    let startPage = Math.max(1, data.pagina - Math.floor(maxPages / 2));
    let endPage = Math.min(data.total_paginas, startPage + maxPages - 1);

    if (endPage - startPage < maxPages - 1) {
      startPage = Math.max(1, endPage - maxPages + 1);
    }

    for (let i = startPage; i <= endPage; i++) {
      const active = i === data.pagina ? "active" : "";
      pagination.append(`
        <li class="paginate_button page-item ${active}">
          <a href="#" onclick="mudarPaginaInternos(${i})" class="page-link">${i}</a>
        </li>
      `);
    }

    // Botão próximo
    const nextDisabled = data.pagina === data.total_paginas ? "disabled" : "";
    pagination.append(`
      <li class="paginate_button page-item ${nextDisabled}">
        <a href="#" onclick="mudarPaginaInternos(${data.pagina + 1})" class="page-link">Próximo</a>
      </li>
    `);
  }

  function mudarPaginaInternos(pagina) {
    listaInternosData.pagina = pagina;
    carregarListaInternos();
    return false;
  }

  function buscarInternos() {
    listaInternosData.busca = $("#busca-interno").val();
    listaInternosData.pagina = 1;
    carregarListaInternos();
  }

  function filtrarListaInternos() {
    listaInternosData.galeria = $("#filtro-galeria-internos").val();
    listaInternosData.bloco = $("#filtro-bloco").val();
    listaInternosData.regalia = $("#filtro-regalia").val();
    listaInternosData.empresa = $("#filtro-empresa").val();
    listaInternosData.pagina = 1;
    carregarListaInternos();
  }

  function recarregarListaInternos() {
    carregarListaInternos();
  }

  function verFichaInterno(ipen) {
    postAction({ action: "ficha_interno", ipen: ipen })
      .then(function (response) {
        if (response.success) {
          mostrarFichaInternoModal(response.data);
        }
      })
      .catch(function (error) {
        mostrarNotificacao("Erro ao carregar ficha do interno", "error");
      });
  }

  function verHistoricoTrabalho(ipen) {
    // Implementar histórico de trabalho
    mostrarNotificacao("Histórico de trabalho em desenvolvimento", "info");
  }

  function mostrarFichaInternoModal(data) {
    const modal = $("#modalFichaInterno");
    const conteudo = $("#conteudo-ficha-interno");

    const interno = data.interno;
    const trabalho = data.trabalho_atual;
    const historico = data.historico_trabalho;

    let html = `
        <!-- Dados Pessoais -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-user mr-2"></i>Dados Pessoais
                        </h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <tr>
                                <td><strong>IPEN:</strong></td>
                                <td>${interno.ipen}</td>
                            </tr>
                            <tr>
                                <td><strong>Nome:</strong></td>
                                <td>${interno.nome}</td>
                            </tr>
                            <tr>
                                <td><strong>Local:</strong></td>
                                <td>${interno.galeria}-${interno.bloco}</td>
                            </tr>
                            <tr>
                                <td><strong>Regalia:</strong></td>
                                <td>${interno.regalia_descricao}</td>
                            </tr>
                            <tr>
                                <td><strong>Status:</strong></td>
                                <td>${getStatusBadgeInterno(interno.status)}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-briefcase mr-2"></i>Trabalho Atual
                        </h5>
                    </div>
                    <div class="card-body">
    `;

    if (trabalho) {
      html += `
                        <table class="table table-sm">
                            <tr>
                                <td><strong>Empresa:</strong></td>
                                <td>${trabalho.empresa_curta}</td>
                            </tr>
                            <tr>
                                <td><strong>Turno:</strong></td>
                                <td>${trabalho.dias_semana || "-"}</td>
                            </tr>
                            <tr>
                                <td><strong>Início:</strong></td>
                                <td>${formatarData(trabalho.data_inicio)}</td>
                            </tr>
                            <tr>
                                <td><strong>Término:</strong></td>
                                <td>${formatarData(trabalho.data_fim) || "-"}</td>
                            </tr>
                        </table>
      `;
    } else {
      html += `
                        <div class="alert alert-warning text-center">
                          <i class="fas fa-exclamation-triangle mr-2"></i>
                          Interno sem trabalho atual
                        </div>
      `;
    }

    html += `
                    </div>
                </div>
            </div>
        </div>

        <!-- Histórico de Trabalho -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-history mr-2"></i>Histórico de Trabalho
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Empresa</th>
                                        <th>Turno</th>
                                        <th>Início</th>
                                        <th>Término</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
    `;

    if (historico.length === 0) {
      html += `
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">
                                            Nenhum histórico de trabalho encontrado
                                        </td>
                                    </tr>
      `;
    } else {
      historico.forEach(function (item) {
        const statusBadge =
          item.status === "A"
            ? '<span class="badge badge-success">Ativo</span>'
            : '<span class="badge badge-secondary">Inativo</span>';

        html += `
                                    <tr>
                                        <td>${item.empresa_curta}</td>
                                        <td>${item.dias_semana || "-"}</td>
                                        <td>${formatarData(item.data_inicio)}</td>
                                        <td>${formatarData(item.data_fim) || "-"}</td>
                                        <td>${statusBadge}</td>
                                    </tr>
        `;
      });
    }

    html += `
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;

    conteudo.html(html);
    modal.modal("show");
  }

  // Fechar bloco de proteção contra múltiplos carregamentos
} // fim do if (typeof window.canteirosLoaded === 'undefined')
