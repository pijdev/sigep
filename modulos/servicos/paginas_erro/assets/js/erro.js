/**
 * SIGEP - JavaScript das Páginas de Erro
 * Funcionalidades interativas e compatibilidade SPA
 *
 * @version 1.0.0
 * @author SIGEP Development Team
 */

// Proteger contra múltiplos carregamentos no SPA
if (typeof window.erroPagesLoaded === "undefined") {
  window.erroPagesLoaded = true;

  // Variáveis globais
  var erroConfig = {
    autoRedirect: true,
    redirectDelay: 10000, // 10 segundos para erros gerais
    maintenanceDelay: 30000, // 30 segundos para manutenção
    enableLogging: true,
    enableAnalytics: true,
  };

  // Inicialização quando documento estiver pronto
  $(document).ready(function () {
    inicializarPaginaErro();
    configurarEventListeners();
    iniciarAutoRedirect();
  });

  // Inicializar página de erro
  function inicializarPaginaErro() {
    // Detectar tipo de erro
    const urlParams = new URLSearchParams(window.location.search);
    const codigoErro =
      urlParams.get("codigo") ||
      urlParams.get("404") ||
      urlParams.get("403") ||
      urlParams.get("500") ||
      "404";

    // Adicionar classe específica ao body
    document.body.classList.add(`error-${codigoErro}`);

    // Registrar erro se habilitado
    if (erroConfig.enableLogging) {
      registrarErroAnalytics(codigoErro);
    }

    // Configurar comportamento específico por tipo de erro
    configurarComportamentoErro(codigoErro);

    // Inicializar animações
    inicializarAnimacoes();

    // Configurar busca se for 404
    if (codigoErro === "404") {
      configurarBusca404();
    }
  }

  // Configurar event listeners
  function configurarEventListeners() {
    // Botão de voltar
    $(document).on("click", ".btn-voltar", function (e) {
      e.preventDefault();
      history.back();
    });

    // Botão de tentar novamente
    $(document).on("click", ".btn-tentar-novamente", function (e) {
      e.preventDefault();
      const redirectUrl = $(this).data("redirect") || window.location.pathname;
      recarregarPagina(redirectUrl);
    });

    // Botão de busca
    $(document).on("click", ".btn-buscar", function (e) {
      e.preventDefault();
      const searchTerm = $("#searchError").val().trim();
      if (searchTerm) {
        realizarBusca(searchTerm);
      }
    });

    // Enter no campo de busca
    $(document).on("keypress", "#searchError", function (e) {
      if (e.key === "Enter") {
        e.preventDefault();
        const searchTerm = $(this).val().trim();
        if (searchTerm) {
          realizarBusca(searchTerm);
        }
      }
    });

    // Botão de reportar erro
    $(document).on("click", ".btn-reportar-erro", function (e) {
      e.preventDefault();
      reportarErro();
    });

    // Detectar quando usuário voltar da página de erro
    window.addEventListener("pageshow", function (event) {
      if (event.persisted) {
        // Usuário voltou via cache do navegador
        limparTimeoutsErro();
      }
    });
  }

  // Configurar comportamento específico por tipo de erro
  function configurarComportamentoErro(codigo) {
    switch (codigo) {
      case "404":
        // Sugerir páginas populares
        sugerirPaginasPopulares();
        break;

      case "403":
        // Mostrar informações de contato
        mostrarInformacoesContato();
        break;

      case "500":
        // Habilitar reload automático
        configurarReloadAutomatico();
        break;

      case "503":
        // Configurar countdown de manutenção
        configurarCountdownManutencao();
        break;
    }
  }

  // Iniciar auto-redirecionamento
  function iniciarAutoRedirect() {
    const urlParams = new URLSearchParams(window.location.search);
    const codigoErro = urlParams.get("codigo") || "404";
    const redirectUrl = urlParams.get("redirect") || "/";

    if (!erroConfig.autoRedirect) return;

    let delay = erroConfig.redirectDelay;

    // Delay específico para manutenção
    if (codigoErro === "503") {
      delay = erroConfig.maintenanceDelay;
    }

    // Não redirecionar erros 403 ou 404
    if (["403", "404"].includes(codigoErro)) return;

    // Mostrar countdown
    mostrarCountdown(delay);

    // Configurar timeout
    window.erroRedirectTimeout = setTimeout(function () {
      redirecionarPara(redirectUrl);
    }, delay);
  }

  // Mostrar countdown
  function mostrarCountdown(delay) {
    let tempoRestante = Math.floor(delay / 1000);
    const countdownElement = $("#countdown");

    if (countdownElement.length === 0) {
      $(".error-actions").after(`
            <div class="alert alert-info mt-3">
                <i class="fas fa-clock mr-2"></i>
                Redirecionando automaticamente em <span id="countdown">${tempoRestante}</span> segundos...
                <button class="btn btn-sm btn-outline-info ml-2" onclick="limparTimeoutsErro()">
                    Cancelar
                </button>
            </div>
        `);
    }

    // Atualizar countdown
    window.countdownInterval = setInterval(function () {
      tempoRestante--;
      $("#countdown").text(tempoRestante);

      if (tempoRestante <= 0) {
        clearInterval(window.countdownInterval);
      }
    }, 1000);
  }

  // Configurar busca 404
  function configurarBusca404() {
    // Obter termo buscado anterior
    const referrer = document.referrer;
    if (referrer) {
      try {
        const referrerUrl = new URL(referrer);
        const searchParam =
          referrerUrl.searchParams.get("search") ||
          referrerUrl.searchParams.get("q");

        if (searchParam) {
          $("#searchError").val(decodeURIComponent(searchParam));
        }
      } catch (e) {
        // Ignorar erros de parsing
      }
    }

    // Configurar autocomplete
    $("#searchError").on("input", function () {
      const termo = $(this).val().trim();
      if (termo.length >= 3) {
        buscarSugestoes(termo);
      } else {
        esconderSugestoes();
      }
    });
  }

  // Buscar sugestões
  function buscarSugestoes(termo) {
    // Simular busca de sugestões (pode ser implementado com API real)
    const sugestoes = [
      "Dashboard",
      "Internos",
      "Movimentações",
      "Censura",
      "Relatórios",
      "Configurações",
    ].filter((s) => s.toLowerCase().includes(termo.toLowerCase()));

    if (sugestoes.length > 0) {
      mostrarSugestoes(sugestoes);
    } else {
      esconderSugestoes();
    }
  }

  // Mostrar sugestões
  function mostrarSugestoes(sugestoes) {
    esconderSugestoes();

    const sugestoesHtml = sugestoes
      .map(
        (s) => `
        <div class="sugestao-item" onclick="selecionarSugestao('${s}')">
            <i class="fas fa-search mr-2"></i>${s}
        </div>
    `,
      )
      .join("");

    $("#searchError").after(`
        <div class="sugestoes-container">
            ${sugestoesHtml}
        </div>
    `);
  }

  // Esconder sugestões
  function esconderSugestoes() {
    $(".sugestoes-container").remove();
  }

  // Selecionar sugestão
  function selecionarSugestao(sugestao) {
    $("#searchError").val(sugestao);
    esconderSugestoes();
    realizarBusca(sugestao);
  }

  // Realizar busca
  function realizarBusca(termo) {
    if (typeof loadPage === "function") {
      // Se estiver no SPA
      loadPage(`/?search=${encodeURIComponent(termo)}`);
    } else {
      // Se estiver em página standalone
      window.location.href = `/?search=${encodeURIComponent(termo)}`;
    }
  }

  // Sugerir páginas populares
  function sugerirPaginasPopulares() {
    const paginasPopulares = [
      { nome: "Dashboard Principal", url: "/" },
      { nome: "Painel de Internos", url: "/ferramentas/cadastro-internos" },
      { nome: "Movimentações", url: "/eclusa/movimentacoes" },
      { nome: "Censura de Cartas", url: "/censura/cartas" },
      { nome: "Relatórios", url: "/relatorios" },
    ];

    const paginasHtml = paginasPopulares
      .map(
        (p) => `
        <a href="${p.url}" class="pagina-popular">
            <i class="fas fa-chevron-right mr-2"></i>${p.nome}
        </a>
    `,
      )
      .join("");

    $(".error-description").after(`
        <div class="paginas-populares mt-4">
            <h5 class="mb-3">Páginas Populares</h5>
            <div class="paginas-lista">
                ${paginasHtml}
            </div>
        </div>
    `);
  }

  // Mostrar informações de contato
  function mostrarInformacoesContato() {
    $(".error-description").after(`
        <div class="informacoes-contato mt-4">
            <div class="alert alert-warning">
                <h5><i class="fas fa-exclamation-triangle mr-2"></i>Acesso Negado</h5>
                <p>Você não tem permissão para acessar esta página.</p>
                <hr>
                <p class="mb-0">
                    <strong>Se você acredita que isso é um erro:</strong><br>
                    Entre em contato com o administrador do sistema.
                </p>
                <div class="mt-3">
                    <button class="btn btn-warning btn-sm" onclick="reportarErro()">
                        <i class="fas fa-bug mr-2"></i>Reportar Problema
                    </button>
                </div>
            </div>
        </div>
    `);
  }

  // Configurar reload automático
  function configurarReloadAutomatico() {
    $(".error-actions").append(`
        <button class="btn btn-outline-primary" onclick="recarregarPagina()">
            <i class="fas fa-sync-alt mr-2"></i>Tentar Agora
        </button>
    `);
  }

  // Configurar countdown de manutenção
  function configurarCountdownManutencao() {
    // Estimativa de retorno (pode ser configurada)
    const estimativaRetorno = new Date();
    estimativaRetorno.setMinutes(estimativaRetorno.getMinutes() + 30);

    $(".error-description").after(`
        <div class="manutencao-info mt-4">
            <div class="alert alert-info">
                <h5><i class="fas fa-tools mr-2"></i>Manutenção em Andamento</h5>
                <p>Estamos realizando melhorias no sistema.</p>
                <p class="mb-0">
                    <strong>Previsão de retorno:</strong> ${estimativaRetorno.toLocaleString("pt-BR")}
                </p>
            </div>
        </div>
    `);
  }

  // Inicializar animações
  function inicializarAnimacoes() {
    // Animar entrada dos elementos
    $(".error-box").css("opacity", "0").css("transform", "translateY(30px)");

    setTimeout(function () {
      $(".error-box").css("transition", "all 0.6s ease-out");
      $(".error-box").css("opacity", "1").css("transform", "translateY(0)");
    }, 100);

    // Animar ícone
    $(".error-icon").addClass("animate__animated animate__bounceIn");

    // Adicionar efeito parallax no fundo (se existir)
    if ($(".error-background").length > 0) {
      $(document).on("mousemove", function (e) {
        const x = e.clientX / window.innerWidth;
        const y = e.clientY / window.innerHeight;

        $(".error-background").css(
          "transform",
          `translate(${x * 20}px, ${y * 20}px)`,
        );
      });
    }
  }

  // Registrar erro no analytics
  function registrarErroAnalytics(codigo) {
    if (!erroConfig.enableAnalytics) return;

    // Simular analytics (pode ser integrado com Google Analytics ou similar)
    const dadosErro = {
      codigo: codigo,
      url: window.location.href,
      referrer: document.referrer,
      userAgent: navigator.userAgent,
      timestamp: new Date().toISOString(),
      usuario: typeof userNome !== "undefined" ? userNome : "Não autenticado",
    };

    // Enviar para analytics (implementar conforme necessário)
    console.log("Erro registrado:", dadosErro);

    // Opcional: Enviar para servidor
    if (typeof enviarAnalytics === "function") {
      enviarAnalytics("erro_pagina", dadosErro);
    }
  }

  // Reportar erro
  function reportarErro() {
    const urlParams = new URLSearchParams(window.location.search);
    const codigo = urlParams.get("codigo") || "404";
    const mensagem = urlParams.get("mensagem") || "";

    // Criar modal de reporte
    const modalHtml = `
        <div class="modal fade" id="modalReportarErro" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-bug mr-2"></i>Reportar Erro
                        </h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="formReportarErro">
                            <div class="form-group">
                                <label>Tipo de Erro:</label>
                                <input type="text" class="form-control" value="${codigo}" readonly>
                            </div>
                            <div class="form-group">
                                <label>Descrição do Problema:</label>
                                <textarea class="form-control" rows="3" placeholder="Descreva o que aconteceu..." required></textarea>
                            </div>
                            <div class="form-group">
                                <label>Seu Email:</label>
                                <input type="email" class="form-control" placeholder="seu@email.com">
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-primary" onclick="enviarReporteErro()">
                            <i class="fas fa-paper-plane mr-2"></i>Enviar Reporte
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Adicionar modal ao body e mostrar
    $("body").append(modalHtml);
    $("#modalReportarErro").modal("show");
  }

  // Enviar reporte de erro
  function enviarReporteErro() {
    const descricao = $("#formReportarErro textarea").val().trim();
    const email = $('#formReportarErro input[type="email"]').val().trim();

    if (!descricao) {
      mostrarNotificacao("Por favor, descreva o problema", "warning");
      return;
    }

    // Enviar reporte (implementar chamada AJAX)
    $.ajax({
      url: "modulos/servicos/paginas_erro/erro_logica.php",
      method: "POST",
      data: {
        action: "registrar_erro",
        codigo:
          new URLSearchParams(window.location.search).get("codigo") || "404",
        mensagem: descricao,
        detalhes: {
          email: email,
          url: window.location.href,
          userAgent: navigator.userAgent,
        },
      },
      dataType: "json",
      success: function (response) {
        if (response.success) {
          mostrarNotificacao("Erro reportado com sucesso!", "success");
          $("#modalReportarErro").modal("hide");
        } else {
          mostrarNotificacao("Erro ao reportar: " + response.message, "error");
        }
      },
      error: function () {
        mostrarNotificacao("Falha na comunicação", "error");
      },
    });
  }

  // Recarregar página
  function recarregarPagina(url) {
    mostrarNotificacao("Recarregando página...", "info");

    setTimeout(function () {
      if (url) {
        if (typeof loadPage === "function") {
          loadPage(url);
        } else {
          window.location.href = url;
        }
      } else {
        window.location.reload();
      }
    }, 1000);
  }

  // Redirecionar para URL
  function redirecionarPara(url) {
    if (typeof loadPage === "function") {
      loadPage(url);
    } else {
      window.location.href = url;
    }
  }

  // Limpar timeouts de erro
  function limparTimeoutsErro() {
    if (window.erroRedirectTimeout) {
      clearTimeout(window.erroRedirectTimeout);
    }
    if (window.countdownInterval) {
      clearInterval(window.countdownInterval);
    }

    $('.alert:contains("Redirecionando")').remove();
  }

  // Mostrar notificação
  function mostrarNotificacao(mensagem, tipo = "info") {
    if (typeof toastr !== "undefined") {
      toastr[tipo](mensagem);
    } else if (typeof swal !== "undefined") {
      swal({
        icon: tipo === "error" ? "error" : tipo,
        title: mensagem,
        timer: 3000,
        showConfirmButton: false,
      });
    } else {
      alert(mensagem);
    }
  }

  // Detectar se está em modo SPA
  function isSPAMode() {
    return typeof loadPage === "function" && $("#content").length > 0;
  }

  // Função para carregar página de erro no SPA
  function carregarPaginaErro(codigo, mensagem, redirect) {
    if (isSPAMode()) {
      $.ajax({
        url: "modulos/servicos/paginas_erro/erro_view.php",
        method: "GET",
        data: {
          codigo: codigo,
          mensagem: mensagem,
          redirect: redirect,
        },
        success: function (response) {
          $("#content").html(response);
          inicializarPaginaErro();
        },
        error: function () {
          // Fallback: redirecionar
          window.location.href = `/erro?codigo=${codigo}`;
        },
      });
    } else {
      window.location.href = `/erro?codigo=${codigo}`;
    }
  }

  // Exportar funções para uso global
  window.SIGEPErro = {
    carregarPaginaErro: carregarPaginaErro,
    reportarErro: reportarErro,
    recarregarPagina: recarregarPagina,
    limparTimeoutsErro: limparTimeoutsErro,
  };

  // Fechar bloco de proteção contra múltiplos carregamentos
} // fim do if (typeof window.erroPagesLoaded === 'undefined')
