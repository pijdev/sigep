/**
 * SIGEP Controle de Dívidas - JavaScript Principal
 * Gestão de Dívidas de Internos com AJAX e AdminLTE 3
 */

console.log("[DEBUG] controle_dividas.js carregando...");
console.log(
  "[DEBUG] controleDividasLoaded:",
  typeof window.controleDividasLoaded,
);

// Resetar proteção contra múltiplos carregamentos para permitir recarregamento no SPA
window.controleDividasLoaded = false;
window.controleDividasInitialized = false;

// Proteger contra múltiplos carregamentos no SPA
if (!window.controleDividasLoaded) {
  window.controleDividasLoaded = true;
  console.log("[DEBUG] Script será executado (primeira vez)");

  // Variáveis globais
  var currentData = {
    dividas: [],
    stats: {},
    filtros: {
      busca: "",
      status_detalhado: "",
      status: "",
      tipo: "",
      mostrar_inativos: false,
      limit: 50,
      offset: 0,
    },
    paginacao: {
      total: 0,
      pagina: 1,
      total_paginas: 0,
    },
    kpiDetalhes: {
      tipo: "",
      dados: [],
    },
  };

  // Função para mostrar/esconder campos de pensão
  function toggleCamposPensao() {
    var tipoDivida = $("#tipo-divida").val();
    var camposPensao = $("#campos-pensao");
    var campoValorDivida = $("#campo-valor-divida");
    var valorDivida = $("#valor-divida");
    var ajudaValorDivida = $("#ajuda-valor-divida");

    console.log("[DEBUG] toggleCamposPensao chamado. Tipo:", tipoDivida);

    if (tipoDivida === "Pensão") {
      console.log(
        "[DEBUG] Mostrando campos de pensão e ocultando valor da dívida...",
      );

      // Mostrar campos de pensão
      if (camposPensao.length > 0) {
        camposPensao.slideDown(300);
      }
      $("#pensao-favorecido").prop("required", true);
      $("#pensao-banco").prop("required", true);
      $("#pensao-agencia").prop("required", true);
      $("#pensao-conta").prop("required", true);
      $("#pensao-determinacao").prop("required", true);

      // Ocultar campo Valor da Dívida
      if (campoValorDivida.length > 0) {
        campoValorDivida.slideUp(300);
      }
      valorDivida.prop("required", false);
      valorDivida.val("");
      ajudaValorDivida.text(
        "Para Pensão, apenas os descontos mensais são considerados",
      );
    } else {
      console.log(
        "[DEBUG] Escondendo campos de pensão e mostrando valor da dívida...",
      );

      // Esconder campos de pensão
      if (camposPensao.length > 0) {
        camposPensao.slideUp(300);
      }
      $("#pensao-favorecido").prop("required", false);
      $("#pensao-banco").prop("required", false);
      $("#pensao-agencia").prop("required", false);
      $("#pensao-conta").prop("required", false);
      $("#pensao-determinacao").prop("required", false);
      // Limpar campos de pensão
      $("#pensao-favorecido").val("");
      $("#pensao-banco").val("");
      $("#pensao-agencia").val("");
      $("#pensao-conta").val("");
      $("#pensao-op").val("");
      $("#pensao-tipo-conta").val("Corrente");
      $("#pensao-determinacao").val("");

      // Mostrar campo Valor da Dívida
      if (campoValorDivida.length > 0) {
        campoValorDivida.slideDown(300);
      }
      valorDivida.prop("required", true);
      ajudaValorDivida.text("Valor total da dívida a ser quitada");
    }
  }

  /**
   * Carrega lista de bancos da API v1
   * Endpoint: api/v1/endpoints/bancos_br_febraban.php
   *
   * Fluxo:
   * 1. Tenta BrasilAPI (fonte primária)
   * 2. Se falhar, usa BD local (fallback automático da API)
   * 3. Se ambos falharem, mostra erro
   */
  function carregarBancos() {
    console.log("[DEBUG] Carregando bancos via API v1...");

    $.ajax({
      url: "api/v1/endpoints/bancos_br_febraban.php",
      method: "GET",
      dataType: "json",
      timeout: 10000, // 10 segundos timeout
      success: function (response) {
        if (
          response.success &&
          Array.isArray(response.data) &&
          response.data.length > 0
        ) {
          popularSelectBancos(response.data, response.fonte);
          console.log(
            "[DEBUG] Bancos carregados:",
            response.data.length,
            "- Fonte:",
            response.fonte,
            response.fallback ? "(fallback ativado)" : "",
          );
        } else {
          console.error("[DEBUG] API retornou dados vazios");
          mostrarErroBancos();
        }
      },
      error: function (xhr, status, error) {
        console.error("[DEBUG] Erro ao carregar bancos:", status, error);
        mostrarErroBancos();
      },
    });
  }

  /**
   * Popula o select de bancos com dados da API
   * @param {Array} bancos - Lista de bancos
   * @param {String} fonte - Fonte dos dados
   */
  function popularSelectBancos(bancos, fonte) {
    var select = $("#pensao-banco");
    select.empty();
    select.append('<option value="">Selecione...</option>');

    // Filtrar e ordenar
    var bancosValidos = bancos
      .filter(function (b) {
        return b.codigo && b.nome;
      })
      .sort(function (a, b) {
        return parseInt(a.codigo) - parseInt(b.codigo);
      });

    // Adicionar opções
    bancosValidos.forEach(function (banco) {
      var codigo = String(banco.codigo).padStart(3, "0");
      select.append(
        '<option value="' +
          codigo +
          '">' +
          codigo +
          " - " +
          banco.nome +
          "</option>",
      );
    });

    // Adicionar "Outro" no final
    select.append('<option value="outro">Outro (especificar)</option>');

    console.log(
      "[DEBUG] Select populado com",
      bancosValidos.length,
      "bancos - Fonte:",
      fonte,
    );
  }

  /**
   * Mostra estado de erro no select quando API falha
   */
  function mostrarErroBancos() {
    var select = $("#pensao-banco");
    select.empty();
    select.append('<option value="">Erro ao carregar bancos</option>');
    select.append(
      '<option value="outro">Outro (especificar manualmente)</option>',
    );
    select.prop("disabled", false);
    console.error("[DEBUG] Não foi possível carregar lista de bancos");
  }

  // Inicialização quando documento estiver pronto
  $(document).ready(function () {
    // Evitar inicialização múltipla no SPA
    if (window.controleDividasInitialized) {
      console.log("[DEBUG] Módulo já inicializado, pulando inicialização...");
      return;
    }

    window.controleDividasInitialized = true;
    console.log("[DEBUG] Inicializando módulo pela primeira vez...");

    carregarBancos(); // BrasilAPI first, BD local como fallback
    inicializarComponentes();
    carregarStats();
    carregarDados();

    // Debug: Verificar se campos de pensão existem
    console.log(
      "[DEBUG] Campos de pensão encontrados:",
      $("#campos-pensao").length,
    );
    console.log(
      "[DEBUG] Select de bancos encontrado:",
      $("#pensao-banco").length,
    );

    // Auto-refresh a cada 30 segundos
    setInterval(autoRefresh, 30000);
  });

  // Inicializar componentes da interface
  function inicializarComponentes() {
    // Máscaras para campos monetários (implementação manual)
    $(".money").on("input", function () {
      formatarMoneyInput(this);
    });

    // Configurar mês atual como padrão no modal de lançamento
    var hoje = new Date();
    var mesAtual = hoje.toISOString().slice(0, 7);
    $("#mes-referencia").val(mesAtual);

    // Event listeners
    $(document).on("click", "#btn-salvar", salvarDívida);
    $(document).on("click", "#btn-lancar", lancarSalario);

    // Event listener para tipo de dívida
    $(document).on("change", "#tipo-divida", function () {
      console.log(
        "[DEBUG] Evento change disparado em #tipo-divida. Valor:",
        $(this).val(),
      );
      toggleCamposPensao();
    });

    // Auto-cálculo do desconto
    $("#salario-real").on("input", function () {
      calcularDescontoPreview();
    });

    $("#percentual-desconto").on("input", function () {
      calcularDescontoPreview();
    });

    // Limpar formulários ao fechar modais
    $("#modal-cadastro").on("hidden.bs.modal", limparFormularioCadastro);
    $("#modal-lancamento").on("hidden.bs.modal", limparFormularioLancamento);

    // Mostrar/esconder campos de pensão quando tipo mudar
    console.log("[DEBUG] Registrando event listener para #tipo-divida");
    $("#tipo-divida").on("change", function () {
      console.log(
        "[DEBUG] Evento change disparado em #tipo-divida. Valor:",
        $(this).val(),
      );
      toggleCamposPensao();
    });

    // Tecla Enter nos campos de busca
    $("#busca").on("keypress", function (e) {
      if (e.which === 13) {
        aplicarFiltros();
      }
    });

    $("#busca-interno").on("keypress", function (e) {
      if (e.which === 13) {
        buscarInterno();
      }
    });
  }

  // Carregar estatísticas dos KPIs
  function carregarStats() {
    console.log("[DEBUG] Carregando estatísticas...");
    $.ajax({
      url: "modulos/laboral/controle_dividas/controle_dividas_logica.php",
      method: "POST",
      data: { action: "stats" },
      dataType: "json",
      success: function (response) {
        console.log("[DEBUG] Resposta das stats:", response);
        if (response.success) {
          currentData.stats = response.data;
          console.log("[DEBUG] Stats carregadas:", currentData.stats);
          atualizarKPIs();
        } else {
          console.error("[DEBUG] Erro na resposta stats:", response);
        }
      },
      error: function (xhr, status, error) {
        console.error("[DEBUG] Erro AJAX ao carregar estatísticas:", error);
        console.error("[DEBUG] Resposta completa:", xhr.responseText);
        mostrarNotificacao("Erro ao carregar estatísticas", "error");
      },
    });
  }

  // Atualizar cards KPI
  function atualizarKPIs() {
    console.log("[DEBUG] Atualizando KPIs com dados:", currentData.stats);

    var totalAtivas = currentData.stats.total_ativas || 0;
    var arrecadadoMes = currentData.stats.arrecadado_mes || 0;
    var pendentes = currentData.stats.pendentes || 0;
    var inadimplentes = currentData.stats.inadimplentes || 0;

    console.log("[DEBUG] Valores para KPIs:", {
      total_ativas: totalAtivas,
      arrecadado_mes: arrecadadoMes,
      pendentes: pendentes,
      inadimplentes: inadimplentes,
    });

    $("#stats-total").text(totalAtivas);
    $("#stats-arrecadado").text("R$ " + formatMoney(arrecadadoMes));
    $("#stats-pendentes").text(pendentes);
    $("#stats-inadimplentes").text(inadimplentes);

    console.log("[DEBUG] KPIs atualizados no DOM");
  }

  // Carregar dados da tabela
  function carregarDados() {
    mostrarLoading(true);

    $.ajax({
      url: "modulos/laboral/controle_dividas/controle_dividas_logica.php",
      method: "POST",
      data: {
        action: "listar",
        ...currentData.filtros,
      },
      dataType: "json",
      success: function (response) {
        if (response.success) {
          currentData.dividas = response.data;
          currentData.paginacao.total = response.total;
          currentData.paginacao.total_paginas = Math.ceil(
            response.total / currentData.filtros.limit,
          );

          atualizarTabela();
          atualizarPaginacao();
        }
      },
      error: function (xhr, status, error) {
        console.error("Erro ao carregar dados:", error);
        mostrarNotificacao("Erro ao carregar dados", "error");
      },
      complete: function () {
        mostrarLoading(false);
      },
    });
  }

  // C:\Sites\sigep\modulos\laboral\calculo_multas\assets\js\calculo_multas.js

  function alignTable() {
    // Select all table headers and cells
    const headers = document.querySelectorAll("th, td");

    // Loop through each header and cell
    headers.forEach((element) => {
      // Set width for each cell to match the header content
      element.style.width = `${element.offsetWidth}px`;
    });
  }

  // Call the function when the DOM is fully loaded
  document.addEventListener("DOMContentLoaded", alignTable);

  // Atualizar tabela de dívidas
  function atualizarTabela() {
    var tbody = $("#tabela-corpo");
    tbody.empty();

    // Verificar se dividas existe e tem dados
    if (!currentData.dividas || currentData.dividas.length === 0) {
      tbody.append(`
            <tr>
                <td colspan="11" class="text-center">
                    <i class="fas fa-inbox"></i> Nenhuma dívida encontrada
                </td>
            </tr>
        `);
      return;
    }

    currentData.dividas.forEach(function (divida) {
      var row = criarLinhaTabela(divida);
      tbody.append(row);
    });
  }

  // Criar linha da tabela
  function criarLinhaTabela(divida) {
    var nomeExibicao =
      divida.interno_nome_social || divida.interno_nome || "N/A";
    var statusBadge = getStatusBadge(
      divida.status_detalhado,
      divida.interno_status,
      divida.interno_trabalho,
    );
    var acoes = getBotoesAcao(divida);

    return `
        <tr>
            <td style="width: 180px;">${nomeExibicao}</td>
            <td style="width: 80px;">${divida.ipen || "N/A"}</td>
            <td style="width: 120px;">${formatarCPF(divida.cpf) || "N/A"}</td>
            <td style="width: 100px;">${divida.tipo_divida || "N/A"}</td>
            <td style="width: 100px;">${
              divida.tipo_divida === "Pensão"
                ? "R$ " + formatMoney(divida.total_descontado || 0)
                : "R$ " + formatMoney(divida.valor_divida)
            }</td>
            <td style="width: 80px;">${divida.percentual_desconto}%</td>
            <td style="width: 100px;">${
              divida.tipo_divida === "Pensão"
                ? "R$ " + formatMoney(divida.total_descontado || 0)
                : "R$ " + formatMoney(divida.valor_atual)
            }</td>
            <td style="width: 90px;">${divida.autos || "N/A"}</td>
            <td style="width: 80px;">${divida.status === "A" ? '<span class="badge badge-success">Ativo</span>' : '<span class="badge badge-danger">Inativo</span>'}</td>
            <td style="width: 110px;">${statusBadge}</td>
            <td style="width: 120px;">${acoes}</td>
        </tr>
    `;
  }

  // Obter badge de status
  function getStatusBadge(statusDetalhado, statusInterno, situacaoTrabalho) {
    if (statusInterno === "I") {
      return '<span class="badge badge-danger">Inativo</span>';
    }

    if (!situacaoTrabalho || situacaoTrabalho === "") {
      return '<span class="badge badge-warning">Não Trabalhando</span>';
    }

    switch (statusDetalhado) {
      case "Pendente":
        return '<span class="badge badge-warning">Pendente</span>';
      case "Ativa":
        return '<span class="badge badge-info">Ativa</span>';
      case "Suspensa":
        return '<span class="badge badge-secondary">Suspensa</span>';
      case "Quitada":
        return '<span class="badge badge-success">Quitada</span>';
      case "Inativa":
        return '<span class="badge badge-dark">Inativa</span>';
      default:
        if (statusDetalhado && statusDetalhado.startsWith("Lançado")) {
          return (
            '<span class="badge badge-success">' + statusDetalhado + "</span>"
          );
        }
        return '<span class="badge badge-secondary">N/A</span>';
    }
  }

  // Obter botões de ação
  function getBotoesAcao(divida) {
    var botaoLancar = "";
    var botaoExcluir = "";

    // Verificar se já existe lançamento no mês atual
    var mesAtual = new Date().toISOString().slice(0, 7);
    var podeLancar = !currentData.dividas.some(function (d) {
      return (
        d.ipen === divida.ipen &&
        d.id !== divida.id &&
        // Aqui deveria verificar no histórico se já existe lançamento este mês
        false
      );
    });

    if (podeLancar && divida.status_detalhado !== "Quitada") {
      botaoLancar = `
            <button class="btn btn-sm btn-success" onclick="abrirModalLancamento(${divida.id}, '${divida.ipen}', '${divida.interno_nome || divida.interno_nome_social}')" title="Lançar Salário do Mês">
                <i class="fas fa-money-bill-wave"></i>
            </button>
        `;
    }

    // Botão excluir (habilitado para todas as dívidas)
    botaoExcluir = `
            <button class="btn btn-sm btn-danger" onclick="confirmarExclusao(${divida.id}, '${divida.ipen}', '${divida.interno_nome || divida.interno_nome_social}')" title="Excluir Dívida">
                <i class="fas fa-trash"></i>
            </button>
        `;

    return `
        <div class="btn-group" role="group">
            <button class="btn btn-sm btn-primary" onclick="editarDivida(${divida.id})" title="Editar">
                <i class="fas fa-edit"></i>
            </button>
            <button class="btn btn-sm btn-info" onclick="visualizarDivida(${divida.id})" title="Visualizar">
                <i class="fas fa-eye"></i>
            </button>
            ${botaoLancar}
            ${botaoExcluir}
        </div>
    `;
  }

  // Aplicar filtros
  function aplicarFiltros() {
    currentData.filtros.busca = $("#busca").val();
    currentData.filtros.status_detalhado = $("#filtro-status-detalhado").val();
    currentData.filtros.status = $("#filtro-status").val();
    currentData.filtros.tipo = $("#filtro-tipo").val();
    currentData.filtros.mostrar_inativos =
      $("#mostrar-inativos").is(":checked");
    currentData.filtros.offset = 0;
    currentData.paginacao.pagina = 1;

    carregarDados();
  }

  // Limpar filtros
  function limparFiltros() {
    $("#busca").val("");
    $("#filtro-status-detalhado").val("");
    $("#filtro-status").val("");
    $("#filtro-tipo").val("");
    $("#mostrar-inativos").prop("checked", false);

    currentData.filtros = {
      busca: "",
      status_detalhado: "",
      status: "",
      tipo: "",
      mostrar_inativos: false,
      limit: 50,
      offset: 0,
    };

    carregarDados();
  }

  // Modal de cadastro/edição
  function abrirModalCadastro(dividaId = null) {
    limparFormularioCadastro();

    if (dividaId) {
      // Editar dívida existente
      var divida = currentData.dividas.find((d) => d.id === dividaId);
      if (divida) {
        $("#modal-titulo").text("Editar Dívida");
        $("#divida-id").val(divida.id);
        $("#interno-id").val(divida.ipen);
        $("#interno-selecionado").html(`
                <i class="fas fa-user"></i> ${divida.interno_nome || divida.interno_nome_social} (IPEN: ${divida.ipen})
            `);
        $("#tipo-divida").val(divida.tipo_divida);
        $("#valor-divida").val("R$ " + formatMoney(divida.valor_divida));
        $("#percentual-desconto").val(divida.percentual_desconto);
        $("#autos").val(divida.autos);
        $("#status-detalhado").val(divida.status_detalhado);

        // Preencher campos de pensão
        $("#pensao-favorecido").val(divida.pensao_favorecido || "");
        $("#pensao-banco").val(divida.pensao_banco || "");
        $("#pensao-agencia").val(divida.pensao_agencia || "");
        $("#pensao-conta").val(divida.pensao_conta || "");
        $("#pensao-op").val(divida.pensao_op || "");
        $("#pensao-tipo-conta").val(divida.pensao_tipo_conta || "Corrente");
        $("#pensao-determinacao").val(divida.pensao_determinacao || "");

        // Mostrar/esconder campos de pensão
        toggleCamposPensao();
      }
    } else {
      // Nova dívida
      $("#modal-titulo").text("Nova Dívida");
      // Garantir que campos de pensão estejam escondidos para nova dívida
      toggleCamposPensao();
    }

    // Configurar eventos do modal para gerenciar foco corretamente
    var $modalCadastro = $("#modal-cadastro");

    // Remover eventos anteriores para evitar duplicação
    $modalCadastro.off("show.bs.modal hidden.bs.modal");

    // Remover foco de elementos antes de mostrar o modal
    $modalCadastro.on("show.bs.modal", function () {
      // Remover foco de qualquer elemento que possa ter foco
      $(":focus").blur();
      // Impedir que Bootstrap adicione aria-hidden
      $(this).removeAttr("aria-hidden");
    });

    // Limpar foco quando o modal for fechado
    $modalCadastro.on("hidden.bs.modal", function () {
      // Remover completamente o aria-hidden
      $(this).removeAttr("aria-hidden");
      // Retornar foco para o body ou elemento apropriado
      $("body").focus();
    });

    // Prevenir que Bootstrap defina aria-hidden durante o show
    $modalCadastro.on("show.bs.modal", function () {
      setTimeout(function () {
        $modalCadastro.removeAttr("aria-hidden");
      }, 0);
    });

    $modalCadastro.modal("show");
  }

  // Buscar interno
  function buscarInterno() {
    var termo = $("#busca-interno").val().trim();

    if (!termo) {
      mostrarNotificacao("Digite um termo para busca", "warning");
      return;
    }

    $.ajax({
      url: "modulos/laboral/controle_dividas/controle_dividas_logica.php",
      method: "POST",
      data: {
        action: "buscar_interno",
        termo: termo,
      },
      dataType: "json",
      success: function (response) {
        if (response.success && response.data.length > 0) {
          var interno = response.data[0]; // Pega o primeiro resultado
          selecionarInterno(interno);
        } else {
          mostrarNotificacao("Nenhum interno encontrado", "warning");
          limparSelecaoInterno();
        }
      },
      error: function (xhr, status, error) {
        console.error("Erro ao buscar interno:", error);
        mostrarNotificacao("Erro ao buscar interno", "error");
      },
    });
  }

  // Selecionar interno encontrado
  function selecionarInterno(interno) {
    $("#interno-id").val(interno.ipen);
    $("#interno-selecionado").html(`
        <i class="fas fa-user"></i> ${interno.nome || interno.nome_social} (IPEN: ${interno.ipen})
        ${interno.status === "I" ? '<br><small class="text-danger">Interno Inativo</small>' : ""}
        ${!interno.situacao ? '<br><small class="text-warning">Não está trabalhando</small>' : ""}
    `);

    // Preencher CPF formatado ou deixar vazio se não tiver
    var cpfFormatado = interno.cpf ? formatarCPF(interno.cpf) : "";
    $("#cpf").val(cpfFormatado);
  }

  // Limpar seleção de interno
  function limparSelecaoInterno() {
    $("#interno-id").val("");
    $("#interno-selecionado").html(
      '<i class="fas fa-user"></i> Nenhum interno selecionado',
    );
    $("#cpf").val("");
  }

  // Função para limpar valores monetários
  function limparValorMonetario(valor) {
    if (!valor) return 0;
    // Remove R$, espaços, pontos e vírgulas, depois converte para número
    var limpo = valor.replace(/[R$\s.]/g, "").replace(",", ".");
    return parseFloat(limpo) || 0;
  }

  // Salvar dívida
  function salvarDívida() {
    var dados = {
      id: $("#divida-id").val() || null,
      ipen: $("#interno-id").val(),
      cpf: $("#cpf").val(),
      autos: $("#autos").val(),
      tipo_divida: $("#tipo-divida").val(),
      valor_divida: limparValorMonetario($("#valor-divida").val()),
      percentual_desconto: $("#percentual-desconto").val(),
      status_detalhado: $("#status-detalhado").val(),
      // Campos de pensão
      pensao_favorecido: $("#pensao-favorecido").val() || null,
      pensao_banco: $("#pensao-banco").val() || null,
      pensao_agencia: $("#pensao-agencia").val() || null,
      pensao_conta: $("#pensao-conta").val() || null,
      pensao_op: $("#pensao-op").val() || null,
      pensao_tipo_conta: $("#pensao-tipo-conta").val() || "Corrente",
      pensao_determinacao: $("#pensao-determinacao").val() || null,
    };

    // Debug detalhado dos valores
    console.log("Valores do formulário:", {
      ipen: dados.ipen,
      cpf: dados.cpf,
      autos: dados.autos,
      tipo_divida: dados.tipo_divida,
      valor_divida: dados.valor_divida,
      percentual_desconto: dados.percentual_desconto,
    });

    // Validação básica com tratamento de strings vazias
    if (!dados.ipen || dados.ipen.trim() === "") {
      mostrarNotificacao("Selecione um interno", "warning");
      return;
    }
    if (!dados.tipo_divida || dados.tipo_divida.trim() === "") {
      mostrarNotificacao("Selecione o tipo de dívida", "warning");
      return;
    }

    // Validação de valor da dívida (apenas para tipos que não são Pensão)
    if (dados.tipo_divida !== "Pensão") {
      if (!dados.valor_divida || dados.valor_divida <= 0) {
        mostrarNotificacao("Informe o valor da dívida", "warning");
        return;
      }
    }

    // Validação específica para Pensão

    // Validação dos campos de pensão (apenas quando tipo for Pensão)
    if (dados.tipo_divida === "Pensão") {
      if (!dados.pensao_favorecido || dados.pensao_favorecido.trim() === "") {
        mostrarNotificacao(
          "Preencha o nome do favorecido da pensão",
          "warning",
        );
        return;
      }
      if (!dados.pensao_banco || dados.pensao_banco.trim() === "") {
        mostrarNotificacao("Selecione o banco da pensão", "warning");
        return;
      }
      if (!dados.pensao_agencia || dados.pensao_agencia.trim() === "") {
        mostrarNotificacao("Preencha a agência da conta", "warning");
        return;
      }
      if (!dados.pensao_conta || dados.pensao_conta.trim() === "") {
        mostrarNotificacao("Preencha o número da conta", "warning");
        return;
      }
      if (
        !dados.pensao_determinacao ||
        dados.pensao_determinacao.trim() === ""
      ) {
        mostrarNotificacao("Preencha a determinação da pensão", "warning");
        return;
      }
    }

    $.ajax({
      url: "modulos/laboral/controle_dividas/controle_dividas_logica.php",
      method: "POST",
      data: {
        action: "salvar",
        ...dados,
      },
      dataType: "json",
      success: function (response) {
        console.log("Resposta do servidor:", response);
        if (response.success) {
          mostrarNotificacao("Dívida salva com sucesso", "success");
          $("#modal-cadastro").modal("hide");
          carregarDados();
          carregarStats();
        } else {
          mostrarNotificacao("Erro: " + response.message, "error");
        }
      },
      error: function (xhr, status, error) {
        console.error("Erro ao salvar dívida:", error);
        console.error("Resposta completa:", xhr.responseText);

        // Verificar se é erro de chave duplicada
        if (
          xhr.status === 500 &&
          xhr.responseText.includes("Duplicate entry")
        ) {
          Swal.fire({
            icon: "warning",
            title: "Atenção!",
            html: "Já existe um lançamento para este mês nesta dívida.<br><small>Deseja editar o lançamento existente ou usar outro mês?</small>",
            confirmButtonColor: "#3085d6",
            confirmButtonText: "Entendido",
            showCancelButton: true,
            cancelButtonText: "Editar Lançamento",
            reverseButtons: true,
          }).then((result) => {
            if (result.isConfirmed) {
              // Usuário entendeu, apenas fechar o modal
              console.log("Usuário ciente do lançamento duplicado");
            } else if (result.isDismissed && result.dismiss === "cancel") {
              // Usuário quer editar o lançamento existente
              console.log("Usuário deseja editar lançamento existente");
              // Aqui poderia abrir modal para editar o lançamento existente
              mostrarNotificacao(
                "Funcionalidade de edição de lançamento em desenvolvimento",
                "info",
              );
            }
          });
        } else {
          mostrarNotificacao("Falha na comunicação", "error");
        }
      },
    });
  }

  // Modal de lançamento
  function abrirModalLancamento(dividaId, ipen, nomeInterno) {
    $("#lancamento-divida-id").val(dividaId);
    $("#lancamento-ipen").val(ipen);
    $("#lancamento-nome").text(nomeInterno);
    $("#salario-real").val("");
    $("#calculo-preview").text("Salário: R$ 0,00 × Desconto: 25% = R$ 0,00");

    // Configurar eventos do modal para gerenciar foco corretamente
    var $modalLancamento = $("#modal-lancamento");

    // Remover eventos anteriores para evitar duplicação
    $modalLancamento.off("show.bs.modal hidden.bs.modal");

    // Remover foco de elementos antes de mostrar o modal
    $modalLancamento.on("show.bs.modal", function () {
      // Remover foco de qualquer elemento que possa ter foco
      $(":focus").blur();
      // Impedir que Bootstrap adicione aria-hidden
      $(this).removeAttr("aria-hidden");
    });

    // Limpar foco quando o modal for fechado
    $modalLancamento.on("hidden.bs.modal", function () {
      // Remover completamente o aria-hidden
      $(this).removeAttr("aria-hidden");
      // Retornar foco para o body ou elemento apropriado
      $("body").focus();
    });

    // Prevenir que Bootstrap defina aria-hidden durante o show
    $modalLancamento.on("show.bs.modal", function () {
      setTimeout(function () {
        $modalLancamento.removeAttr("aria-hidden");
      }, 0);
    });

    $modalLancamento.modal("show");
  }

  // Calcular preview do desconto
  function calcularDescontoPreview() {
    var salario =
      parseFloat(
        $("#salario-real")
          .val()
          .replace(/[R$\s.]/g, "")
          .replace(",", "."),
      ) || 0;
    var percentual = parseFloat($("#percentual-desconto").val()) || 25;
    var desconto = (salario * percentual) / 100;

    $("#calculo-preview").text(
      `Salário: R$ ${formatMoney(salario)} × Desconto: ${percentual}% = R$ ${formatMoney(desconto)}`,
    );
  }

  // Lançar salário
  function lancarSalario() {
    // Verificar se o campo existe antes de pegar o valor
    var dividaId = $("#lancamento-divida-id").val();
    if (!dividaId) {
      console.error("Campo lancamento-divida-id não encontrado ou vazio!");
      mostrarNotificacao("Erro: ID da dívida não encontrado", "error");
      return;
    }

    var salarioReal = limparValorMonetario($("#salario-real").val());
    var percentualDesconto = 25; // percentual padrão, poderia vir da dívida

    // Calcular valores
    var valorDesconto = (salarioReal * percentualDesconto) / 100;
    var saldoAnterior = 0; // poderia buscar o saldo atual da dívida
    var saldoNovo = saldoAnterior + valorDesconto;

    var dados = {
      divida_id: dividaId,
      ipen: $("#lancamento-ipen").val(),
      mes_referencia: $("#mes-referencia").val(),
      salario_real: salarioReal,
      percentual_desconto: percentualDesconto,
      valor_desconto: valorDesconto,
      saldo_anterior: saldoAnterior,
      saldo_novo: saldoNovo,
    };

    // Debug dos dados sendo enviados
    console.log("Dados do lançamento:", dados);
    console.log("Valor do divida_id:", dividaId);
    console.log("Valor do ipen:", $("#lancamento-ipen").val());
    console.log("Valor do mes_referencia:", $("#mes-referencia").val());
    console.log("Valor do salario_real:", $("#salario-real").val());

    // Verificar se todos os campos obrigatórios estão preenchidos
    if (
      !dados.divida_id ||
      !dados.ipen ||
      !dados.mes_referencia ||
      !dados.salario_real
    ) {
      Swal.fire({
        icon: "warning",
        title: "Campos Obrigatórios",
        text: "Preencha todos os campos obrigatórios para continuar.",
        confirmButtonColor: "#3085d6",
      });
      return;
    }

    $.ajax({
      url: "modulos/laboral/controle_dividas/controle_dividas_logica.php",
      method: "POST",
      data: {
        action: "lancar_salario",
        ...dados,
      },
      dataType: "json",
      success: function (response) {
        console.log("Resposta do servidor:", response);
        if (response.success) {
          Swal.fire({
            icon: "success",
            title: "Sucesso!",
            text: "Salário lançado com sucesso!",
            timer: 2000,
            showConfirmButton: false,
          }).then(function () {
            $("#modal-lancamento").modal("hide");
            carregarDados();
          });
        } else {
          // Verificar se é erro de duplicação de chave única
          var mensagem = response.message;
          var isDuplicacao =
            mensagem.includes("Duplicate entry") &&
            mensagem.includes("unique_mes_multa");

          var isSalarioDiferente =
            mensagem.includes("Já existe salário lançado") ||
            mensagem.includes("mesmo valor de salário");

          if (isDuplicacao) {
            // Erro de duplicação de mês
            Swal.fire({
              icon: "warning",
              title: "Lançamento Duplicado!",
              html: "Não é possível lançar o salário duas vezes no mesmo mês.<br><small>Este mês já possui um lançamento para esta dívida.</small>",
              confirmButtonColor: "#3085d6",
              confirmButtonText: "Entendido",
            });
          } else {
            var swalConfig = {
              icon: isSalarioDiferente ? "error" : "warning",
              title: isSalarioDiferente ? "Salário Inconsistente!" : "Erro",
              html: mensagem,
              confirmButtonColor: isSalarioDiferente ? "#d33" : "#3085d6",
              confirmButtonText: "Entendido",
            };

            if (isSalarioDiferente) {
              swalConfig.footer =
                '<small class="text-muted">Todos os lançamentos do mesmo mês devem usar o mesmo valor de salário.</small>';
            }

            Swal.fire(swalConfig);
          }
        }
      },
      error: function (xhr, status, error) {
        console.error("Erro na requisição:", error);
        console.error("Resposta completa:", xhr.responseText);

        // Verificar se é erro de chave duplicada (unique_mes_multa)
        if (
          xhr.status === 500 &&
          xhr.responseText.includes("Duplicate entry") &&
          xhr.responseText.includes("unique_mes_multa")
        ) {
          Swal.fire({
            icon: "warning",
            title: "Lançamento Duplicado!",
            html: "Não é possível lançar o salário duas vezes no mesmo mês.<br><small>Este mês já possui um lançamento para esta dívida.</small>",
            confirmButtonColor: "#3085d6",
            confirmButtonText: "Entendido",
          });
        } else {
          Swal.fire({
            icon: "error",
            title: "Falha na Comunicação",
            text: "Não foi possível conectar ao servidor. Tente novamente.",
            confirmButtonColor: "#d33",
          });
        }
      },
    });
  }

  // Editar dívida
  function editarDivida(dividaId) {
    abrirModalCadastro(dividaId);
  }

  // Confirmar exclusão com SweetAlert
  function confirmarExclusao(dividaId, ipen, nomeInterno) {
    if (typeof Swal === "undefined") {
      // Fallback se SweetAlert não estiver disponível
      var ipenConfirm = prompt(
        `ATENÇÃO: Para excluir a dívida do interno ${nomeInterno} (IPEN: ${ipen}), digite o IPEN para confirmar:`,
      );

      if (ipenConfirm === ipen) {
        excluirDivida(dividaId, ipen);
      } else if (ipenConfirm !== null) {
        mostrarNotificacao("IPEN incorreto. Exclusão cancelada.", "error");
      }
      return;
    }

    Swal.fire({
      title: "Confirmar Exclusão",
      html: `
                <div class="text-left">
                    <p>Tem certeza que deseja excluir a dívida do interno <strong>${nomeInterno}</strong>?</p>
                    <p class="text-danger">IPEN: <strong>${ipen}</strong></p>
                    <p class="text-warning mb-3">Esta ação não poderá ser desfeita!</p>
                    <div class="form-group">
                        <label for="ipen-confirmacao" class="small">Digite o IPEN <strong>${ipen}</strong> para confirmar:</label>
                        <input type="text" id="ipen-confirmacao" class="form-control" placeholder="Confirme o IPEN">
                    </div>
                </div>
            `,
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#d33",
      cancelButtonColor: "#3085d6",
      confirmButtonText: "Sim, excluir",
      cancelButtonText: "Cancelar",
      preConfirm: function () {
        var ipenConfirmacao = document.getElementById("ipen-confirmacao").value;
        if (!ipenConfirmacao) {
          Swal.showValidationMessage("Por favor, digite o IPEN para confirmar");
          return false;
        }
        if (ipenConfirmacao !== ipen.toString()) {
          Swal.showValidationMessage("IPEN incorreto");
          return false;
        }
        return ipenConfirmacao;
      },
    }).then(function (result) {
      if (result.isConfirmed) {
        excluirDivida(dividaId, result.value);
      }
    });
  }

  // Excluir dívida
  function excluirDivida(dividaId, ipenConfirmacao) {
    $.ajax({
      url: "modulos/laboral/controle_dividas/controle_dividas_logica.php",
      method: "POST",
      data: {
        action: "excluir",
        id: dividaId,
        ipen_confirmacao: ipenConfirmacao,
      },
      dataType: "json",
      success: function (response) {
        if (response.success) {
          mostrarNotificacao("Dívida excluída com sucesso", "success");
          carregarDados();
          carregarStats();
        } else {
          mostrarNotificacao("Erro: " + response.message, "error");
        }
      },
      error: function (xhr, status, error) {
        console.error("Erro ao excluir dívida:", error);
        mostrarNotificacao("Falha na comunicação", "error");
      },
    });
  }

  // Abrir SweetAlert de detalhes do KPI
  function abrirModalKPI(tipoKPI) {
    // Definir mensagens para cada tipo de KPI
    var mensagens = {
      total_ativas: {
        title: "Dívidas Ativas",
        html:
          '<div style="text-align: center; padding: 10px;">' +
          '<p style="margin-bottom: 20px; color: #6c757d;">Este card mostra o total de dívidas ativas cadastradas no sistema. Inclui todas as dívidas que ainda não foram quitadas.</p>' +
          '<div style="border-top: 1px solid #e9ecef; padding-top: 20px; margin-top: 10px;">' +
          '<p style="font-weight: 600; margin-bottom: 15px; color: #495057;">Ações disponíveis:</p>' +
          '<div style="display: flex; justify-content: center; align-items: center; gap: 10px;">' +
          '<a href="laboral/controle-dividas/relatorio-dividas/" target="_blank" ' +
          'style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; padding: 12px 24px; border-radius: 25px; font-weight: 500; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);" ' +
          "onmouseover=\"this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(102, 126, 234, 0.4)'\" " +
          "onmouseout=\"this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(102, 126, 234, 0.3)'\">" +
          '<i class="fas fa-file-alt"></i><span>Relatório Completo</span></a>' +
          "</div></div></div>",
        icon: "info",
      },
      arrecadado_mes: {
        title: "Descontos do Mês",
        html:
          '<div style="text-align: center; padding: 10px;">' +
          '<p style="margin-bottom: 20px; color: #6c757d;">Este card mostra o valor total descontado dos salários dos internos no mês atual. Reflete os valores já deduzidos.</p>' +
          '<div style="border-top: 1px solid #e9ecef; padding-top: 20px; margin-top: 10px;">' +
          '<p style="font-weight: 600; margin-bottom: 15px; color: #495057;">Ações disponíveis:</p>' +
          '<div style="display: flex; justify-content: center; align-items: center; gap: 10px;">' +
          '<a href="laboral/controle-dividas/relatorio-descontos-mes/" target="_blank" ' +
          'style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; border: none; padding: 12px 24px; border-radius: 25px; font-weight: 500; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);" ' +
          "onmouseover=\"this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(40, 167, 69, 0.4)'\" " +
          "onmouseout=\"this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(40, 167, 69, 0.3)'\">" +
          '<i class="fas fa-money-bill-wave"></i><span>Relatório de Descontos</span></a>' +
          "</div></div></div>",
        icon: "success",
      },
      pendentes: {
        title: "Dívidas Pendentes",
        html:
          '<div style="text-align: center; padding: 10px;">' +
          '<p style="margin-bottom: 20px; color: #6c757d;">Este card mostra o total de dívidas que estão pendentes de pagamento ou lançamento. Ainda não foram processadas.</p>' +
          '<div style="border-top: 1px solid #e9ecef; padding-top: 20px; margin-top: 10px;">' +
          '<p style="font-weight: 600; margin-bottom: 15px; color: #495057;">Ações disponíveis:</p>' +
          '<div style="display: flex; justify-content: center; align-items: center; gap: 10px;">' +
          '<a href="laboral/controle-dividas/relatorio-dividas/?status_detalhado=Pendente" target="_blank" ' +
          'style="background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%); color: #212529; border: none; padding: 12px 24px; border-radius: 25px; font-weight: 500; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(255, 193, 7, 0.3);" ' +
          "onmouseover=\"this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(255, 193, 7, 0.4)'\" " +
          "onmouseout=\"this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(255, 193, 7, 0.3)'\">" +
          '<i class="fas fa-exclamation-triangle"></i><span>Ver Pendentes</span></a>' +
          "</div></div></div>",
        icon: "warning",
      },
      inadimplentes: {
        title: "Internos Inadimplentes",
        html:
          '<div style="text-align: center; padding: 10px;">' +
          '<p style="margin-bottom: 20px; color: #6c757d;">Este card mostra o número de internos que possuem dívidas pendentes e não estão em dia com seus pagamentos.</p>' +
          '<div style="border-top: 1px solid #e9ecef; padding-top: 20px; margin-top: 10px;">' +
          '<p style="font-weight: 600; margin-bottom: 15px; color: #495057;">Ações disponíveis:</p>' +
          '<div style="display: flex; justify-content: center; align-items: center; gap: 10px;">' +
          '<a href="laboral/controle-dividas/relatorio-inadimplentes/" target="_blank" ' +
          'style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white; border: none; padding: 12px 24px; border-radius: 25px; font-weight: 500; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);" ' +
          "onmouseover=\"this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(220, 53, 69, 0.4)'\" " +
          "onmouseout=\"this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(220, 53, 69, 0.3)'\">" +
          '<i class="fas fa-user-times"></i><span>Relatório de Inadimplentes</span></a>' +
          "</div></div></div>",
        icon: "error",
      },
    };

    var config = mensagens[tipoKPI] || {
      title: "KPI",
      html: "<p style='text-align: center; color: #6c757d;'>Indicador de desempenho do sistema</p>",
      icon: "info",
    };

    Swal.fire({
      title: config.title,
      html: config.html,
      icon: config.icon,
      confirmButtonColor: "#6c757d",
      confirmButtonText: "Fechar",
      showCancelButton: false,
      width: "550px",
      padding: "20px",
    });
  }

  // Visualizar dívida
  function visualizarDivida(dividaId) {
    var divida = currentData.dividas.find((d) => d.id === dividaId);
    if (!divida) return;

    console.log("Abrindo modal para dívida ID:", dividaId);

    // Buscar histórico de salários
    $.ajax({
      url: "modulos/laboral/controle_dividas/controle_dividas_logica.php",
      method: "POST",
      data: { action: "historico", divida_id: dividaId },
      dataType: "json",
      success: function (response) {
        console.log("Resposta histórico:", response);
        if (response.success) {
          // Buscar projeção de encerramento
          $.ajax({
            url: "modulos/laboral/controle_dividas/controle_dividas_logica.php",
            method: "POST",
            data: { action: "projecao", divida_id: dividaId },
            dataType: "json",
            success: function (projResponse) {
              console.log("Resposta projeção:", projResponse);
              // Buscar estatísticas de parcelas (apenas para pensão)
              $.ajax({
                url: "modulos/laboral/controle_dividas/controle_dividas_logica.php",
                method: "POST",
                data: { action: "estatisticas_parcelas", divida_id: dividaId },
                dataType: "json",
                success: function (estatResponse) {
                  console.log("Resposta estatísticas:", estatResponse);
                  if (projResponse.success) {
                    criarModalVisualizacao(
                      divida,
                      response.data,
                      projResponse.data,
                      estatResponse.data,
                    );
                  } else {
                    criarModalVisualizacao(
                      divida,
                      response.data,
                      projResponse.data,
                      null,
                    );
                  }
                },
                error: function (xhr, status, error) {
                  console.error("Erro nas estatísticas:", error);
                  if (projResponse.success) {
                    criarModalVisualizacao(
                      divida,
                      response.data,
                      projResponse.data,
                      null,
                    );
                  } else {
                    criarModalVisualizacao(divida, response.data, null, null);
                  }
                },
              });
            },
            error: function (xhr, status, error) {
              console.error("Erro na projeção:", error);
              criarModalVisualizacao(divida, response.data, null, null);
            },
          });
        } else {
          mostrarNotificacao(
            "Erro ao buscar histórico: " + response.message,
            "error",
          );
        }
      },
      error: function (xhr, status, error) {
        console.error("Erro no histórico:", error);
        mostrarNotificacao("Falha na comunicação", "error");
      },
    });
  }

  // Criar modal de visualização
  function criarModalVisualizacao(
    divida,
    historicoSalarios,
    projecao,
    estatisticasParcelas,
  ) {
    var nomeExibicao =
      divida.interno_nome_social || divida.interno_nome || "N/A";
    var cpfFormatado = formatarCPF(divida.cpf) || "N/A";
    var statusBadge = getStatusBadge(
      divida.status_detalhado,
      divida.interno_status,
      divida.interno_trabalho,
    );

    // Calcular total de salários
    var totalSalarios = historicoSalarios.reduce(function (total, item) {
      return total + parseFloat(item.salario_real);
    }, 0);

    // Gerar projeção de encerramento
    var projecaoHtml = "";
    if (projecao && projecao.status === "calculado") {
      // Para Pensão, mostra apenas média sem previsão de término
      if (projecao.tipo === "Pensão") {
        projecaoHtml = `
                <tr>
                    <td><strong>Média Mensal:</strong></td>
                    <td class="text-info">
                        <i class="fas fa-chart-line mr-1"></i>
                        R$ ${formatMoney(projecao.media_desconto)}
                    </td>
                </tr>
                <tr>
                    <td><strong>Total Lançamentos:</strong></td>
                    <td class="text-info">${projecao.quantidade_lancamentos}</td>
                </tr>
                <tr>
                    <td><strong>Previsão:</strong></td>
                    <td class="text-muted">
                        <i class="fas fa-infinity mr-1"></i>
                        Pagamento mensal contínuo
                    </td>
                </tr>
            `;
      } else {
        // Para outras dívidas, mostra previsão de quitação
        var classeCor =
          projecao.projecao_meses <= 3
            ? "text-success"
            : projecao.projecao_meses <= 6
              ? "text-warning"
              : "text-danger";

        projecaoHtml = `
                <tr>
                    <td><strong>Previsão Encerramento:</strong></td>
                    <td class="${classeCor}">
                        <i class="fas fa-calendar-alt mr-1"></i>
                        ${projecao.data_estimada} (${projecao.projecao_meses} meses)
                    </td>
                </tr>
                <tr>
                    <td><strong>Média Descontos:</strong></td>
                    <td class="text-info">R$ ${formatMoney(projecao.media_desconto)}</td>
                </tr>
                <tr>
                    <td><strong>Saldo Restante:</strong></td>
                    <td class="text-warning">R$ ${formatMoney(projecao.saldo_restante)}</td>
                </tr>
            `;
      }
    } else if (projecao && projecao.status === "quitada") {
      projecaoHtml = `
                <tr>
                    <td><strong>Status:</strong></td>
                    <td class="text-success">
                        <i class="fas fa-check-circle mr-1"></i>
                        Dívida Quitada
                    </td>
                </tr>
            `;
    } else if (projecao && projecao.status === "insuficiente") {
      projecaoHtml = `
                <tr>
                    <td><strong>Previsão:</strong></td>
                    <td class="text-muted">
                        <i class="fas fa-info-circle mr-1"></i>
                        Insuficiente para calcular
                    </td>
                </tr>
            `;
    }

    // Criar tabela de histórico de salários
    var tabelaHistorico = "";
    if (historicoSalarios.length === 0) {
      tabelaHistorico = `
            <tr>
                <td colspan="6" class="text-center text-muted">
                    <i class="fas fa-info-circle"></i> Nenhum salário lançado para esta dívida
                </td>
            </tr>
        `;
    } else {
      tabelaHistorico = historicoSalarios
        .map(function (item) {
          return `
                <tr>
                    <td>${formatarData(item.mes_referencia)}</td>
                    <td>R$ ${formatMoney(item.salario_real)}</td>
                    <td>R$ ${formatMoney(item.valor_desconto)}</td>
                    <td>R$ ${formatMoney(item.saldo_anterior)}</td>
                    <td>R$ ${formatMoney(item.saldo_novo)}</td>
                    <td>${item.usuario_nome || "N/A"}</td>
                </tr>
            `;
        })
        .join("");
    }

    // Criar modal HTML
    var modalHtml = `
        <div class="modal fade" id="modal-visualizar" tabindex="-1">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-eye mr-2"></i>
                            Detalhes da Dívida
                        </h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <!-- Dados da Dívida -->
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="card-title mb-0">
                                            <i class="fas fa-file-invoice-dollar mr-2"></i>
                                            Informações da Dívida
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <table class="table table-sm table-borderless">
                                            <tr>
                                                <td><strong>Interno:</strong></td>
                                                <td>${nomeExibicao}</td>
                                            </tr>
                                            <tr>
                                                <td><strong>IPEN:</strong></td>
                                                <td>${divida.ipen || "N/A"}</td>
                                            </tr>
                                            <tr>
                                                <td><strong>CPF:</strong></td>
                                                <td>${cpfFormatado}</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Autos:</strong></td>
                                                <td>${divida.autos || "N/A"}</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Tipo:</strong></td>
                                                <td>${divida.tipo_divida || "N/A"}</td>
                                            </tr>
                                            ${
                                              divida.tipo_divida !== "Pensão"
                                                ? `
                                            <tr>
                                                <td><strong>Valor Original:</strong></td>
                                                <td class="text-danger font-weight-bold">R$ ${formatMoney(divida.valor_divida)}</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Valor Atual:</strong></td>
                                                <td class="text-danger font-weight-bold">R$ ${formatMoney(divida.valor_atual)}</td>
                                            </tr>`
                                                : `
                                            <tr>
                                                <td><strong>Total Descontado:</strong></td>
                                                <td class="text-info font-weight-bold">R$ ${formatMoney(divida.total_descontado || 0)}</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Quantidade de Lançamentos:</strong></td>
                                                <td class="text-info font-weight-bold">${divida.quantidade_lancamentos || 0}</td>
                                            </tr>`
                                            }
                                            <tr>
                                                <td><strong>Percentual:</strong></td>
                                                <td>${divida.percentual_desconto}%</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Status:</strong></td>
                                                <td>${statusBadge}</td>
                                            </tr>
                                            ${projecaoHtml}
                                        </table>
                                    </div>
                                </div>

                                ${
                                  divida.tipo_divida === "Pensão" &&
                                  divida.pensao_favorecido
                                    ? `
                                <!-- Dados da Pensão -->
                                <div class="card mt-3 border-primary">
                                    <div class="card-header bg-primary text-white">
                                        <h6 class="card-title mb-0">
                                            <i class="fas fa-money-check-alt mr-2"></i>
                                            Dados da Pensão Alimentícia
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <table class="table table-sm table-borderless">
                                            <tr>
                                                <td><strong>Favorecido:</strong></td>
                                                <td>${divida.pensao_favorecido}</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Banco:</strong></td>
                                                <td>${divida.pensao_banco || "N/A"}</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Agência:</strong></td>
                                                <td>${divida.pensao_agencia || "N/A"}</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Conta:</strong></td>
                                                <td>${divida.pensao_conta || "N/A"}</td>
                                            </tr>
                                            ${
                                              divida.pensao_op
                                                ? `
                                            <tr>
                                                <td><strong>Operação:</strong></td>
                                                <td>${divida.pensao_op}</td>
                                            </tr>
                                            `
                                                : ""
                                            }
                                            <tr>
                                                <td><strong>Tipo Conta:</strong></td>
                                                <td>${divida.pensao_tipo_conta || "Corrente"}</td>
                                            </tr>
                                            ${
                                              divida.pensao_determinacao
                                                ? `
                                            <tr>
                                                <td colspan="2"><strong>Determinação:</strong></td>
                                            </tr>
                                            <tr>
                                                <td colspan="2" class="text-muted" style="white-space: pre-wrap;">${divida.pensao_determinacao}</td>
                                            </tr>
                                            `
                                                : ""
                                            }
                                        </table>
                                    </div>
                                </div>
                                `
                                    : ""
                                }
                            </div>

                            <!-- Estatísticas de Parcelas (apenas para pensão) -->
                            ${
                              estatisticasParcelas &&
                              estatisticasParcelas.tipo_divida === "Pensão"
                                ? `
                            <div class="col-md-6">
                                <div class="card border-success">
                                    <div class="card-header bg-success text-white">
                                        <h6 class="card-title mb-0">
                                            <i class="fas fa-chart-pie mr-2"></i>
                                            Estatísticas de Parcelas
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <table class="table table-sm table-borderless">
                                            <tr>
                                                <td><strong>Parcelas Pagas:</strong></td>
                                                <td class="text-success font-weight-bold">${estatisticasParcelas.parcelas_pagas}</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Parcelas Não Pagas:</strong></td>
                                                <td class="text-danger font-weight-bold">${estatisticasParcelas.parcelas_nao_pagas}</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Total Esperado:</strong></td>
                                                <td>${estatisticasParcelas.total_meses_esperados}</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Percentual Adimplência:</strong></td>
                                                <td>
                                                    <div class="progress" style="height: 20px;">
                                                        <div class="progress-bar bg-success" style="width: ${estatisticasParcelas.percentual_adimplencia}%">
                                                            ${estatisticasParcelas.percentual_adimplencia}%
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td><strong>Valor Total Pago:</strong></td>
                                                <td class="text-info font-weight-bold">R$ ${formatMoney(estatisticasParcelas.valor_total_pago)}</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Valor Médio Parcela:</strong></td>
                                                <td class="text-primary">R$ ${formatMoney(estatisticasParcelas.valor_medio_parcela)}</td>
                                            </tr>
                                        </table>

                                        ${
                                          estatisticasParcelas.meses_pulados
                                            .length > 0
                                            ? `
                                            <div class="mt-3">
                                                <h6 class="text-danger">
                                                    <i class="fas fa-exclamation-triangle mr-2"></i>
                                                    Meses com Pagamentos Pulados:
                                                </h6>
                                                <div class="alert alert-warning">
                                                    <strong>Atenção:</strong> Os meses abaixo não tiveram lançamentos registrados.
                                                </div>
                                                <div class="table-responsive">
                                                    <table class="table table-sm table-bordered">
                                                        <thead>
                                                            <tr>
                                                                <th>Mês</th>
                                                                <th>Status</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            ${estatisticasParcelas.meses_pulados
                                                              .map(
                                                                (mes) => `
                                                                <tr>
                                                                    <td>${formatarData(mes)}</td>
                                                                    <td><span class="badge badge-danger">Sem Pagamento</span></td>
                                                                </tr>
                                                            `,
                                                              )
                                                              .join("")}
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        `
                                            : ""
                                        }
                                    </div>
                                </div>
                            `
                                : ""
                            }

                            <!-- Histórico de Salários -->
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="card-title mb-0">
                                            <i class="fas fa-money-bill-wave mr-2"></i>
                                            Histórico de Salários Lançados
                                        </h6>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="table-responsive">
                                            <table class="table table-sm table-hover mb-0">
                                                <thead class="thead-light">
                                                    <tr>
                                                        <th>Mês</th>
                                                        <th>Salário</th>
                                                        <th>Desconto</th>
                                                        <th>Saldo Ant.</th>
                                                        <th>Saldo Novo</th>
                                                        <th>Usuário</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    ${tabelaHistorico}
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="card-footer bg-light">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <strong>Total dos Salários:</strong>
                                                <span class="badge badge-success badge-pill font-size-16">
                                                    R$ ${formatMoney(totalSalarios)}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" onclick="imprimirHistoricoDivida(${divida.id})">
                            <i class="fas fa-print mr-1"></i> Imprimir Histórico
                        </button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <i class="fas fa-times mr-1"></i>
                            Fechar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Remover modal anterior se existir e limpar foco
    $("#modal-visualizar").remove();
    $("body").append(modalHtml);

    // Configurar eventos do modal para gerenciar foco corretamente
    var $modal = $("#modal-visualizar");

    // Remover eventos anteriores para evitar duplicação
    $modal.off("show.bs.modal hidden.bs.modal");

    // Remover foco de elementos antes de mostrar o modal
    $modal.on("show.bs.modal", function () {
      // Remover foco de qualquer elemento que possa ter foco
      $(":focus").blur();
      // Impedir que Bootstrap adicione aria-hidden
      $(this).removeAttr("aria-hidden");
    });

    // Limpar foco quando o modal for fechado
    $modal.on("hidden.bs.modal", function () {
      // Remover completamente o aria-hidden
      $(this).removeAttr("aria-hidden");
      // Remover o modal do DOM completamente
      $(this).remove();
      // Retornar foco para o body ou elemento apropriado
      $("body").focus();
    });

    // Prevenir que Bootstrap defina aria-hidden durante o show
    $modal.on("show.bs.modal", function () {
      setTimeout(function () {
        $modal.removeAttr("aria-hidden");
      }, 0);
    });

    $modal.modal("show");
  }

  // Limpar formulário de cadastro
  function limparFormularioCadastro() {
    $("#form-cadastro")[0].reset();
    $("#multa-id").val("");
    $("#interno-id").val("");
    $("#interno-selecionado").html(
      '<i class="fas fa-user"></i> Nenhum interno selecionado',
    );
    $("#percentual-desconto").val("25.00");
  }

  // Limpar formulário de lançamento
  function limparFormularioLancamento() {
    $("#form-lancamento")[0].reset();
    $("#calculo-preview").text("Salário: R$ 0,00 × Desconto: 25% = R$ 0,00");
  }

  // Atualizar paginação
  function atualizarPaginacao() {
    var info = `Mostrando ${currentData.dividas ? currentData.dividas.length : 0} de ${currentData.paginacao.total || 0} registros`;
    $("#tabela-info").text(info);

    var paginacaoHtml = "";
    if (currentData.paginacao.total_paginas > 1) {
      paginacaoHtml =
        '<nav><ul class="pagination pagination-sm justify-content-end">';

      // Botão anterior
      paginacaoHtml += `
            <li class="page-item ${currentData.paginacao.pagina === 1 ? "disabled" : ""}">
                <a class="page-link" href="#" onclick="mudarPagina(${currentData.paginacao.pagina - 1})">Anterior</a>
            </li>
        `;

      // Páginas
      for (var i = 1; i <= currentData.paginacao.total_paginas; i++) {
        if (
          i === 1 ||
          i === currentData.paginacao.total_paginas ||
          (i >= currentData.paginacao.pagina - 2 &&
            i <= currentData.paginacao.pagina + 2)
        ) {
          paginacaoHtml += `
                    <li class="page-item ${i === currentData.paginacao.pagina ? "active" : ""}">
                        <a class="page-link" href="#" onclick="mudarPagina(${i})">${i}</a>
                    </li>
                `;
        }
      }

      // Botão próximo
      paginacaoHtml += `
            <li class="page-item ${currentData.paginacao.pagina === currentData.paginacao.total_paginas ? "disabled" : ""}">
                <a class="page-link" href="#" onclick="mudarPagina(${currentData.paginacao.pagina + 1})">Próximo</a>
            </li>
        `;

      paginacaoHtml += "</ul></nav>";
    }

    $("#tabela-paginacao").html(paginacaoHtml);
  }

  // Mudar página
  function mudarPagina(pagina) {
    if (pagina < 1 || pagina > currentData.paginacao.total_paginas) return;

    currentData.paginacao.pagina = pagina;
    currentData.filtros.offset = (pagina - 1) * currentData.filtros.limit;

    carregarDados();
  }

  // Mostrar/esconder loading
  function mostrarLoading(mostrar) {
    if (mostrar) {
      $("#tabela-corpo").html(`
            <tr>
                <td colspan="11" class="text-center">
                    <i class="fas fa-spinner fa-spin"></i> Carregando dados...
                </td>
            </tr>
        `);
    }
  }

  // Auto-refresh
  function autoRefresh() {
    carregarStats();
  }

  // Utilitários
  function mostrarNotificacao(mensagem, tipo = "info") {
    if (typeof toastr !== "undefined") {
      toastr[tipo](mensagem);
    } else {
      console.log(`[${tipo.toUpperCase()}] ${mensagem}`);
    }
  }

  function formatMoney(valor) {
    return parseFloat(valor || 0)
      .toFixed(2)
      .replace(".", ",")
      .replace(/\B(?=(\d{3})+(?!\d))/g, ".");
  }

  function formatarMoneyInput(input) {
    // Remover tudo que não é número
    let value = input.value.replace(/\D/g, "");

    // Converter para número
    let num = parseFloat(value) / 100;

    // Formatar como dinheiro
    if (isNaN(num)) {
      input.value = "";
      return;
    }

    input.value =
      "R$ " +
      num
        .toFixed(2)
        .replace(".", ",")
        .replace(/\B(?=(\d{3})+(?!\d))/g, ".");
  }

  function formatarCPFInput(input) {
    // Remover tudo que não é número
    let value = input.value.replace(/\D/g, "");

    // Limitar a 11 dígitos
    if (value.length > 11) {
      value = value.slice(0, 11);
    }

    // Aplicar máscara CPF: XXX.XXX.XXX-XX
    if (value.length > 9) {
      value =
        value.slice(0, 3) +
        "." +
        value.slice(3, 6) +
        "." +
        value.slice(6, 9) +
        "-" +
        value.slice(9);
    } else if (value.length > 6) {
      value =
        value.slice(0, 3) + "." + value.slice(3, 6) + "." + value.slice(6);
    } else if (value.length > 3) {
      value = value.slice(0, 3) + "." + value.slice(3);
    }

    input.value = value;
  }

  function formatarCPF(cpf) {
    if (!cpf) return "";
    return cpf.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, "$1.$2.$3-$4");
  }

  function formatarData(data) {
    if (!data) return "";
    var date = new Date(data);
    return date.toLocaleDateString("pt-BR");
  }

  // Atualizar status pendentes automaticamente
  function atualizarStatusPendentes() {
    $.ajax({
      url: "modulos/laboral/controle_dividas/controle_dividas_logica.php",
      method: "POST",
      data: { action: "atualizar_status_pendentes" },
      dataType: "json",
      success: function (response) {
        if (response.success) {
          console.log("Status pendentes atualizado com sucesso");
        } else {
          console.error("Erro ao atualizar status:", response.message);
        }
      },
      error: function (xhr, status, error) {
        console.error("Erro na comunicação:", error);
      },
    });
  }

  // Imprimir histórico da dívida
  function imprimirHistoricoDivida(multaId) {
    var url =
      "modulos/laboral/controle_dividas/relatorios/relatorio_historico_divida.php?multa_id=" +
      multaId;
    window.open(
      url,
      "_blank",
      "width=800,height=600,scrollbars=yes,resizable=yes",
    );
  }

  // Função para imprimir relatório com filtros atuais
  function imprimirRelatorio() {
    // Coletar filtros atuais
    var filtros = {
      busca: $("#busca").val() || "",
      status_detalhado: $("#filtro-status-detalhado").val() || "",
      status: $("#filtro-status").val() || "",
      tipo: $("#filtro-tipo").val() || "",
      mostrar_inativos: $("#mostrar-inativos").is(":checked") ? 1 : 0,
      sort_by: $("#sort-by").val() || "data_cadastro",
      sort_order: $("#sort-order").val() || "DESC",
    };

    // Construir URL com parâmetros
    var params = [];
    for (var key in filtros) {
      if (filtros[key]) {
        params.push(key + "=" + encodeURIComponent(filtros[key]));
      }
    }

    var url =
      "modulos/laboral/controle_dividas/relatorios/relatorio_dividas.php";
    if (params.length > 0) {
      url += "?" + params.join("&");
    }

    // Abrir relatório em nova aba
    window.open(url, "_blank");
  }

  // Função para formatar data
  function formatDate(dataString) {
    if (!dataString) return "N/A";
    var date = new Date(dataString);
    return date.toLocaleDateString("pt-BR", {
      day: "2-digit",
      month: "2-digit",
      year: "numeric",
      hour: "2-digit",
      minute: "2-digit",
    });
  }

  // Fechar bloco de proteção contra múltiplos carregamentos
} // fim do if (typeof window.controleDividasLoaded === 'undefined')
