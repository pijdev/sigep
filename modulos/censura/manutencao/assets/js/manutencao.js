/* =====================================================
   JavaScript - MÓDULO CONTROLE DE MANUTENÇÕES
   ===================================================== */

// Variáveis globais
let dadosCarregados = {
  servicos: [],
  eletronicos: [],
  internos: [],
};

// Inicialização
$(document).ready(function () {
  inicializarEventos();
  carregarDadosIniciais();
});

// Eventos principais
function inicializarEventos() {
  // Eventos de busca em tempo real
  $("#busca_eletronico_modal").on(
    "input",
    debounce(function () {
      if ($(this).val().length >= 2) {
        buscarEletronicosModal();
      }
    }, 300),
  );

  $("#busca_interno_modal").on(
    "input",
    debounce(function () {
      if ($(this).val().length >= 2) {
        buscarInternosModal();
      }
    }, 300),
  );

  // Eventos de formulário
  $("#formNovoServico").on("submit", function (e) {
    e.preventDefault();
    salvarServico();
  });

  // Eventos de filtros
  $("#filtro_situacao, #filtro_tipo").on("change", aplicarFiltros);

  // Auto-complete desativado temporariamente (jQuery UI não disponível)
  // Usar modais de busca ao invés
  // inicializarAutoComplete();
}

// Carregar dados iniciais
function carregarDadosIniciais() {
  mostrarLoading();

  $.ajax({
    url: "/modulos/censura/manutencao/manutencao_logica.php",
    method: "POST",
    data: { action: "listar_servicos" },
    dataType: "json",
    success: function (response) {
      if (response.success) {
        dadosCarregados.servicos = response.data;
        atualizarTabelaServicos(response.data);
      } else {
        mostrarAlerta("error", response.error || "Erro ao carregar serviços");
      }
    },
    error: function () {
      mostrarAlerta("error", "Erro de comunicação com o servidor");
    },
    complete: function () {
      esconderLoading();
    },
  });
}

// Funções de modais
function abrirModalNovoServico() {
  limparFormularioServico();
  $("#modalNovoServico").modal("show");
}

function abrirModalBuscaEletronicos() {
  $("#modalBuscaEletronicos").modal("show");
  // Focar no campo de busca
  setTimeout(() => {
    $("#busca_eletronico_modal").focus();
  }, 500);
}

function abrirModalBuscaInternos() {
  $("#modalBuscaInternos").modal("show");
  // Focar no campo de busca
  setTimeout(() => {
    $("#busca_interno_modal").focus();
  }, 500);
}

function abrirModalConsultaEletronicos() {
  abrirModalBuscaEletronicos();
}

// Funções de busca
function buscarEletronicosModal() {
  console.log("buscarEletronicosModal chamado");

  const termo = $("#busca_eletronico_modal").val();
  const tipo = $("#filtro_tipo_eletronico").val();
  const situacao = $("#filtro_situacao_eletronico").val();

  console.log("Parâmetros:", { termo, tipo, situacao });

  mostrarLoading();

  $.ajax({
    url: "/modulos/censura/manutencao/manutencao_logica.php",
    method: "POST",
    data: {
      action: "buscar_eletronicos",
      termo: termo,
      tipo: tipo,
      situacao: situacao,
      limit: 50,
    },
    dataType: "json",
    success: function (response) {
      console.log("Resposta buscarEletronicosModal:", response);
      if (response.success) {
        exibirResultadosEletronicos(response.data);
      } else {
        mostrarAlerta("error", response.error || "Erro na busca");
      }
    },
    error: function (xhr, status, error) {
      console.error("Erro AJAX buscarEletronicosModal:", {
        xhr,
        status,
        error,
      });
      mostrarAlerta("error", "Erro de comunicação");
    },
    complete: function () {
      esconderLoading();
    },
  });
}

function buscarInternosModal() {
  console.log("buscarInternosModal chamado");

  const termo = $("#busca_interno_modal").val();

  console.log("Termo de busca:", termo);

  if (termo.length < 2) {
    $("#resultados_internos").html(
      '<div class="alert alert-info">Digite pelo menos 2 caracteres</div>',
    );
    return;
  }

  mostrarLoading();

  $.ajax({
    url: "/modulos/censura/manutencao/manutencao_logica.php",
    method: "POST",
    data: {
      action: "buscar_interno",
      termo: termo,
      limit: 20,
    },
    dataType: "json",
    success: function (response) {
      console.log("Resposta buscarInternosModal:", response);
      if (response.success) {
        exibirResultadosInternos(response.data);
      } else {
        mostrarAlerta("error", response.error || "Erro na busca");
      }
    },
    error: function (xhr, status, error) {
      console.error("Erro AJAX buscarInternosModal:", { xhr, status, error });
      mostrarAlerta("error", "Erro de comunicação");
    },
    complete: function () {
      esconderLoading();
    },
  });
}

// Exibir resultados de busca
function exibirResultadosEletronicos(eletronicos) {
  const tbody = $("#resultados_eletronicos");
  tbody.empty();

  if (eletronicos.length === 0) {
    tbody.html(
      '<tr><td colspan="8" class="text-center text-muted">Nenhum eletrônico encontrado</td></tr>',
    );
    return;
  }

  eletronicos.forEach(function (eletronico) {
    const row = `
            <tr class="resultado-eletronico" data-id="${eletronico.id}" data-tipo="${eletronico.tipo_item}" data-modelo="${eletronico.marca_modelo}">
                <td>${eletronico.id}</td>
                <td><span class="badge badge-info">${eletronico.tipo_item}</span></td>
                <td>${eletronico.marca_modelo}</td>
                <td>${eletronico.cor}</td>
                <td>${eletronico.nome_interno || "-"}</td>
                <td>${eletronico.nome_dono || "-"}</td>
                <td>${formatarData(eletronico.data_entrada)}</td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="selecionarEletronicoModal(${eletronico.id}, '${eletronico.tipo_item}', '${eletronico.marca_modelo}')">
                        <i class="fas fa-check"></i> Selecionar
                    </button>
                </td>
            </tr>
        `;
    tbody.append(row);
  });

  // Adicionar evento de clique duplo para seleção rápida
  $(".resultado-eletronico").on("dblclick", function () {
    const id = $(this).data("id");
    const tipo = $(this).data("tipo");
    const modelo = $(this).data("modelo");
    selecionarEletronicoModal(id, tipo, modelo);
  });
}

function exibirResultadosInternos(internos) {
  const container = $("#resultados_internos");
  container.empty();

  if (internos.length === 0) {
    container.html(
      '<div class="alert alert-info">Nenhum interno encontrado</div>',
    );
    return;
  }

  internos.forEach(function (interno) {
    const card = `
            <div class="resultado-busca" onclick="selecionarInternoModal(${interno.ipen}, '${interno.texto}')">
                <strong>${interno.texto}</strong><br>
                <small>${interno.descricao}</small>
            </div>
        `;
    container.append(card);
  });
}

// Funções de seleção
function selecionarEletronicoModal(id, tipo, modelo) {
  $("#id_eletronico").val(id);
  $("#busca_eletronico").val(`${tipo} - ${modelo}`);
  $("#modalBuscaEletronicos").modal("hide");
  mostrarAlerta("success", "Eletrônico selecionado com sucesso");
}

function selecionarInternoModal(ipen, texto) {
  $("#ipen_interno").val(ipen);
  $("#busca_interno").val(texto);
  $("#modalBuscaInternos").modal("hide");
  mostrarAlerta("success", "Interno selecionado com sucesso");
}

function selecionarEletronico(id, tipo, modelo) {
  $("#id_eletronico").val(id);
  $("#busca_eletronico").val(`${tipo} - ${modelo}`);
  $("#tipo_servico").val("INSTALACAO").trigger("change");
  abrirModalNovoServico();
}

// CRUD de serviços
function salvarServico() {
  const dados = {
    action: "salvar_servico",
    id_eletronico: $("#id_eletronico").val(),
    tipo_servico: $("#tipo_servico").val(),
    cela_destino: $("#cela_destino").val(),
    ipen_interno: $("#ipen_interno").val(),
    observacoes: $("#observacoes").val(),
  };

  // Validações básicas
  if (
    !dados.id_eletronico ||
    !dados.tipo_servico ||
    !dados.cela_destino ||
    !dados.ipen_interno
  ) {
    mostrarAlerta("warning", "Preencha todos os campos obrigatórios");
    return;
  }

  // Validar formato da cela
  if (!/^([A-Z]{1,2}-\d+)$/.test(dados.cela_destino)) {
    mostrarAlerta("warning", "Formato da cela inválido. Use: SE-3, A-1, BB-10");
    return;
  }

  mostrarLoading();

  $.ajax({
    url: "/modulos/censura/manutencao/manutencao_logica.php",
    method: "POST",
    data: dados,
    dataType: "json",
    success: function (response) {
      if (response.success) {
        $("#modalNovoServico").modal("hide");
        limparFormularioServico();
        carregarDadosIniciais();
        mostrarAlerta(
          "success",
          response.message || "Serviço cadastrado com sucesso",
        );
      } else {
        mostrarAlerta("error", response.error || "Erro ao salvar serviço");
      }
    },
    error: function () {
      mostrarAlerta("error", "Erro de comunicação com o servidor");
    },
    complete: function () {
      esconderLoading();
    },
  });
}

function execututarServico(id) {
  mostrarConfirmacao(
    "Executar Serviço",
    "Tem certeza que deseja executar este serviço? Esta ação não poderá ser desfeita.",
    function () {
      mostrarLoading();

      $.ajax({
        url: "/modulos/censura/manutencao/manutencao_logica.php",
        method: "POST",
        data: {
          action: "executar_servico",
          id_servico: id,
        },
        dataType: "json",
        success: function (response) {
          if (response.success) {
            carregarDadosIniciais();
            mostrarAlerta(
              "success",
              response.message || "Serviço executado com sucesso",
            );
          } else {
            mostrarAlerta(
              "error",
              response.error || "Erro ao executar serviço",
            );
          }
        },
        error: function () {
          mostrarAlerta("error", "Erro de comunicação");
        },
        complete: function () {
          esconderLoading();
        },
      });
    },
  );
}

function cancelarServico(id) {
  mostrarConfirmacao(
    "Cancelar Serviço",
    "Tem certeza que deseja cancelar este serviço? Esta ação não poderá ser desfeita.",
    function () {
      mostrarLoading();

      $.ajax({
        url: "/modulos/censura/manutencao/manutencao_logica.php",
        method: "POST",
        data: {
          action: "cancelar_servico",
          id_servico: id,
        },
        dataType: "json",
        success: function (response) {
          if (response.success) {
            carregarDadosIniciais();
            mostrarAlerta(
              "success",
              response.message || "Serviço cancelado com sucesso",
            );
          } else {
            mostrarAlerta(
              "error",
              response.error || "Erro ao cancelar serviço",
            );
          }
        },
        error: function () {
          mostrarAlerta("error", "Erro de comunicação");
        },
        complete: function () {
          esconderLoading();
        },
      });
    },
  );
}

function verDetalhes(id) {
  mostrarLoading();

  // Buscar detalhes do serviço
  $.ajax({
    url: "/modulos/censura/manutencao/manutencao_logica.php",
    method: "POST",
    data: {
      action: "listar_servicos",
      // Aqui poderíamos ter uma action específica para buscar detalhes
    },
    dataType: "json",
    success: function (response) {
      if (response.success) {
        const servico = response.data.find((s) => s.id == id);
        if (servico) {
          exibirDetalhesServico(servico);
          $("#modalDetalhesServico").modal("show");
        } else {
          mostrarAlerta("error", "Serviço não encontrado");
        }
      } else {
        mostrarAlerta("error", response.error || "Erro ao buscar detalhes");
      }
    },
    error: function () {
      mostrarAlerta("error", "Erro de comunicação");
    },
    complete: function () {
      esconderLoading();
    },
  });
}

function exibirDetalhesServico(servico) {
  const conteudo = `
        <div class="row">
            <div class="col-md-6">
                <h5>Informações do Serviço</h5>
                <table class="table table-sm">
                    <tr><td><strong>ID:</strong></td><td>${servico.id}</td></tr>
                    <tr><td><strong>Tipo:</strong></td><td><span class="badge badge-${getTipoServicoBadge(servico.tipo_servico)}">${servico.tipo_servico}</span></td></tr>
                    <tr><td><strong>Situação:</strong></td><td><span class="badge badge-${getSituacaoBadge(servico.situacao)}">${servico.situacao}</span></td></tr>
                    <tr><td><strong>Cela Destino:</strong></td><td>${servico.cela_destino}</td></tr>
                    <tr><td><strong>Data Solicitação:</strong></td><td>${servico.data_solicitacao_fmt}</td></tr>
                    <tr><td><strong>Data Execução:</strong></td><td>${servico.data_execucao_fmt || "-"}</td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <h5>Informações do Eletrônico</h5>
                <table class="table table-sm">
                    <tr><td><strong>Tipo:</strong></td><td>${servico.tipo_item}</td></tr>
                    <tr><td><strong>Modelo:</strong></td><td>${servico.marca_modelo}</td></tr>
                    <tr><td><strong>Cor:</strong></td><td>${servico.cor_eletronico}</td></tr>
                    <tr><td><strong>Interno:</strong></td><td>${servico.nome_interno}</td></tr>
                    <tr><td><strong>Localização:</strong></td><td>${servico.ala}-${servico.galeria}-${servico.bloco}</td></tr>
                </table>
            </div>
        </div>
        ${
          servico.observacoes
            ? `
        <div class="row mt-3">
            <div class="col-12">
                <h5>Observações</h5>
                <p>${servico.observacoes}</p>
            </div>
        </div>
        `
            : ""
        }
    `;

  $("#detalhes_servico_conteudo").html(conteudo);
}

// Filtros
function aplicarFiltros() {
  const situacao = $("#filtro_situacao").val();
  const tipo = $("#filtro_tipo").val();
  const dataInicio = $("#filtro_data_inicio").val();
  const dataFim = $("#filtro_data_fim").val();

  mostrarLoading();

  $.ajax({
    url: "/modulos/censura/manutencao/manutencao_logica.php",
    method: "POST",
    data: {
      action: "listar_servicos",
      situacao: situacao,
      tipo_servico: tipo,
      data_inicio: dataInicio,
      data_fim: dataFim,
    },
    dataType: "json",
    success: function (response) {
      if (response.success) {
        atualizarTabelaServicos(response.data);
      } else {
        mostrarAlerta("error", response.error || "Erro ao aplicar filtros");
      }
    },
    error: function () {
      mostrarAlerta("error", "Erro de comunicação");
    },
    complete: function () {
      esconderLoading();
    },
  });
}

function limparFiltros() {
  $("#filtro_situacao").val("");
  $("#filtro_tipo").val("");
  $("#filtro_data_inicio").val("");
  $("#filtro_data_fim").val("");
  carregarDadosIniciais();
}

// Utilitários
function limparFormularioServico() {
  $("#formNovoServico")[0].reset();
  $("#id_eletronico").val("");
  $("#ipen_interno").val("");
}

function recarregarDados() {
  carregarDadosIniciais();
  mostrarAlerta("info", "Dados recarregados");
}

function atualizarTabelaServicos(servicos) {
  const tbody = $("#tabela_servicos tbody");
  tbody.empty();

  if (servicos.length === 0) {
    tbody.html(
      '<tr><td colspan="9" class="text-center text-muted">Nenhum serviço encontrado</td></tr>',
    );
    return;
  }

  servicos.forEach(function (servico) {
    const row = `
            <tr>
                <td>${servico.id}</td>
                <td><span class="badge badge-${getTipoServicoBadge(servico.tipo_servico)}">${servico.tipo_servico}</span></td>
                <td>${servico.tipo_item} - ${servico.marca_modelo}</td>
                <td>${servico.nome_interno}</td>
                <td>${servico.cela_destino}</td>
                <td>${servico.data_solicitacao_fmt}</td>
                <td>${servico.data_execucao_fmt || "-"}</td>
                <td><span class="badge badge-${getSituacaoBadge(servico.status)}">${servico.status}</span></td>
                <td>
                    <div class="btn-group">
                        <button class="btn btn-sm btn-info" onclick="verDetalhes(${servico.id})">
                            <i class="fas fa-eye"></i>
                        </button>
                        ${
                          servico.status === "PENDENTE"
                            ? `
                        <button class="btn btn-sm btn-success" onclick="executarServico(${servico.id})">
                            <i class="fas fa-check"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="cancelarServico(${servico.id})">
                            <i class="fas fa-times"></i>
                        </button>
                        `
                            : ""
                        }
                    </div>
                </td>
            </tr>
        `;
    tbody.append(row);
  });
}

function formatarData(dataString) {
  const data = new Date(dataString);
  return data.toLocaleDateString("pt-BR");
}

function getTipoServicoBadge(tipo) {
  const badges = {
    INSTALACAO: "primary",
    TROCA: "warning",
    MANUTENCAO: "info",
    REPARO: "secondary",
    REMOCAO: "danger",
  };
  return badges[tipo] || "secondary";
}

function getSituacaoBadge(situacao) {
  const badges = {
    PENDENTE: "warning",
    EXECUTADO: "success",
    CANCELADO: "danger",
  };
  return badges[situacao] || "secondary";
}

// Sistema de alertas com SweetAlert2
function mostrarAlerta(tipo, mensagem) {
  const config = getSweetAlertConfig(tipo, mensagem);
  Swal.fire(config);
}

function getSweetAlertConfig(tipo, mensagem) {
  const configs = {
    success: {
      icon: "success",
      title: "Sucesso!",
      text: mensagem,
      confirmButtonColor: "#28a745",
      timer: 3000,
      timerProgressBar: true,
      showConfirmButton: false,
    },
    error: {
      icon: "error",
      title: "Erro!",
      text: mensagem,
      confirmButtonColor: "#dc3545",
      confirmButtonText: "OK",
    },
    warning: {
      icon: "warning",
      title: "Atenção!",
      text: mensagem,
      confirmButtonColor: "#ffc107",
      confirmButtonText: "Entendi",
    },
    info: {
      icon: "info",
      title: "Informação",
      text: mensagem,
      confirmButtonColor: "#17a2b8",
      confirmButtonText: "OK",
      timer: 2000,
      timerProgressBar: true,
      showConfirmButton: false,
    },
  };

  return configs[tipo] || configs["info"];
}

// Confirmações com SweetAlert2
function mostrarConfirmacao(
  titulo,
  mensagem,
  callbackConfirm,
  callbackCancel = null,
) {
  Swal.fire({
    title: titulo,
    text: mensagem,
    icon: "question",
    showCancelButton: true,
    confirmButtonColor: "#28a745",
    cancelButtonColor: "#dc3545",
    confirmButtonText: "Sim, confirmar",
    cancelButtonText: "Cancelar",
    reverseButtons: true,
  }).then((result) => {
    if (result.isConfirmed) {
      if (callbackConfirm) callbackConfirm();
    } else if (result.isDismissed && callbackCancel) {
      callbackCancel();
    }
  });
}

// Loading states com SweetAlert2
let loadingAlert = null;

function mostrarLoading() {
  loadingAlert = Swal.fire({
    title: "Processando...",
    text: "Por favor, aguarde.",
    allowOutsideClick: false,
    allowEscapeKey: false,
    showConfirmButton: false,
    didOpen: () => {
      Swal.showLoading();
    },
  });
}

function esconderLoading() {
  if (loadingAlert) {
    loadingAlert.close();
    loadingAlert = null;
  }
}

// Auto-complete
function inicializarAutoComplete() {
  // Configurar autocomplete para busca de eletrônicos
  $("#busca_eletronico").autocomplete({
    source: function (request, response) {
      $.ajax({
        url: "/modulos/censura/manutencao/manutencao_logica.php",
        method: "POST",
        data: {
          action: "buscar_eletronicos",
          termo: request.term,
          situacao: "Estoque",
          limit: 10,
        },
        dataType: "json",
        success: function (data) {
          if (data.success) {
            response(
              $.map(data.data, function (item) {
                return {
                  label: `${item.tipo_item} - ${item.marca_modelo} (${item.nome_interno})`,
                  value: item.id,
                  data: item,
                };
              }),
            );
          }
        },
      });
    },
    minLength: 2,
    select: function (event, ui) {
      $("#id_eletronico").val(ui.item.value);
      $("#busca_eletronico").val(ui.item.label);
      return false;
    },
  });

  // Configurar autocomplete para busca de internos
  $("#busca_interno").autocomplete({
    source: function (request, response) {
      $.ajax({
        url: "/modulos/censura/manutencao/manutencao_logica.php",
        method: "POST",
        data: {
          action: "buscar_interno",
          termo: request.term,
          limit: 10,
        },
        dataType: "json",
        success: function (data) {
          if (data.success) {
            response(
              $.map(data.data, function (item) {
                return {
                  label: item.texto,
                  value: item.ipen,
                  data: item,
                };
              }),
            );
          }
        },
      });
    },
    minLength: 2,
    select: function (event, ui) {
      $("#ipen_interno").val(ui.item.value);
      $("#busca_interno").val(ui.item.label);
      return false;
    },
  });
}

// Debounce para evitar múltiplas requisições
function debounce(func, wait) {
  let timeout;
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout);
      func(...args);
    };
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
  };
}

// Teclas de atalho
$(document).on("keydown", function (e) {
  // Ctrl+N para novo serviço
  if (e.ctrlKey && e.key === "n") {
    e.preventDefault();
    abrirModalNovoServico();
  }

  // Ctrl+F para buscar
  if (e.ctrlKey && e.key === "f") {
    e.preventDefault();
    abrirModalBuscaEletronicos();
  }

  // F5 para recarregar dados
  if (e.key === "F5") {
    e.preventDefault();
    recarregarDados();
  }
});

// Tratamento de erros globais
$(document).ajaxError(function (event, xhr, settings, error) {
  if (xhr.status === 403) {
    mostrarAlerta(
      "error",
      "Acesso negado. Você não tem permissão para realizar esta ação.",
    );
  } else if (xhr.status === 500) {
    mostrarAlerta(
      "error",
      "Erro interno do servidor. Tente novamente mais tarde.",
    );
  } else {
    mostrarAlerta("error", "Erro de comunicação. Verifique sua conexão.");
  }
});

// Limpar variáveis globais ao descarregar página
$(window).on("beforeunload", function () {
  dadosCarregados.servicos = [];
  dadosCarregados.eletronicos = [];
  dadosCarregados.internos = [];
});
