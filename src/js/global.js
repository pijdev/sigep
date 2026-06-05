window.loadPage = function (pageUrl, title = "Painel", parent = "SIGEP") {
  if ($("body").hasClass("sidebar-open")) {
    $('[data-widget="pushmenu"]').PushMenu("toggle");
  }

  $("#breadcrumb-parent").text(parent);
  $("#breadcrumb-title").text(title);
  $("#content-main-title").text(title);
  $(".nav-sidebar .nav-link").removeClass("active");
  $(`.nav-sidebar .nav-link[onclick*="${pageUrl}"]`).addClass("active");

  const $mainContent = $("#main-content");
  $mainContent.html(`<div class="text-center p-5"><i class="fas fa-spinner fa-spin fa-2x text-primary"></i></div>`);

  try {
    const novaUrlVisivel = window.location.origin + window.location.pathname + `?page=${encodeURIComponent(pageUrl)}&title=${encodeURIComponent(title)}&parent=${encodeURIComponent(parent)}`;
    window.history.replaceState({ pageUrl, title, parent }, title, novaUrlVisivel);
  } catch (e) {
    console.warn("Não foi possível atualizar o histórico de navegação:", e);
  }

  $mainContent.load(pageUrl, function (response, status, xhr) {
    if (status === "error") {
      console.error("Erro ao carregar página:", xhr.status, xhr.statusText);

      if (typeof SIGEPErro !== "undefined" && SIGEPErro.carregarPaginaErro) {
        let codigoErro = xhr.status.toString();
        let mensagemErro = `Erro HTTP ${xhr.status}`;

        if (xhr.status === 403) mensagemErro = "Acesso negado a esta página";
        if (xhr.status === 404) mensagemErro = "A página solicitada não foi encontrada";
        if (xhr.status >= 500) mensagemErro = "Erro interno do servidor";

        SIGEPErro.carregarPaginaErro(codigoErro, mensagemErro, pageUrl);
      } else {
        $mainContent.html(`<div class='alert alert-danger shadow-sm'>ERRO ${xhr.status}: ${xhr.statusText}</div>`);
      }
    } else {
      if (typeof window.updateSortVariables === "function") {
        window.updateSortVariables();
      }

      if (window.pageTitle) {
        $("#breadcrumb-title").text(window.pageTitle);
        $("#content-main-title").text(window.pageTitle);
      }
    }
  });
};

function mostrarLoading() {
  if (typeof Swal === "undefined") return;
  const appName = $("#app-name-short").text().trim() || "SIGEP";
  const isDarkMode = $("body").hasClass("dark-mode");
  const bgColor = isDarkMode ? '#343a40' : '#ffffff';
  const textColor = isDarkMode ? '#f8f9fa' : '#212529';
  const subColor = isDarkMode ? '#c2c7d0' : '#6c757d';

  Swal.fire({
    html: `
      <div class="d-flex flex-column align-items-center py-2">
        <!-- Favicon Dinâmico da Raiz do Site com Animação de Pulso -->
        <div class="mb-3 position-relative d-flex align-items-center justify-content-center">
          <img src="/favicon.svg" alt="Logo" class="img-fluid" style="width: 60px; height: 60px; object-fit: contain;">
          
          <!-- Spinner discreto em volta ou abaixo da logo -->
          <div class="spinner-border text-primary position-absolute" role="status" 
               style="width: 76px; height: 76px; border-width: 0.15em; opacity: 0.8; top: -8px;">
            <span class="sr-only">Carregando...</span>
          </div>
        </div>
        
        <!-- Texto Solicitado com Branding do PHP (Cor Dinâmica) -->
        <h6 class="font-weight-bold mt-2 mb-1" style="color: ${textColor}; letter-spacing: 0.3px; line-height: 1.4;">
          Recarregando ${appName}
        </h6>
        
        <p class="small mb-1" style="color: ${subColor};">Aguarde...</p>
      </div>
    `,
    timer: 1200,
    timerProgressBar: true,
    allowOutsideClick: false,
    allowEscapeKey: false,
    showConfirmButton: false,
    background: bgColor,
    customClass: {
      popup: 'border-0 shadow-lg rounded-lg px-4',
      timerProgressBar: 'bg-primary'
    }
  });
}

function ocultarLoading() {
  if (typeof Swal !== "undefined") {
    Swal.close();
  }
}

document.addEventListener('DOMContentLoaded', () => {
  const urlParams = new URLSearchParams(window.location.search);
  const page = urlParams.get('page');
  const title = urlParams.get('title') || 'Painel';
  const parent = urlParams.get('parent') || 'SIGEP';

  if (page) {
    window.loadPage(page, title, parent);
  }
});

window.addEventListener("keydown", function (e) {
  const pressionouF5 = (e.keyCode === 116 || e.key === "F5");
  const pressionouR = (e.keyCode === 82 || e.key === "r" || e.key === "R");
  const temCtrl = (e.ctrlKey || e.metaKey);

  if (pressionouF5 || (pressionouR && temCtrl)) {
    const isHardRefresh = e.shiftKey || (pressionouF5 && temCtrl);
    const urlParams = new URLSearchParams(window.location.search);
    const page = urlParams.get('page');
    const title = urlParams.get('title') || 'Painel';
    const parent = urlParams.get('parent') || 'SIGEP';

    if (isHardRefresh) {
      mostrarLoading();
      return; 
    }

    e.preventDefault();
    mostrarLoading();

    setTimeout(() => {
      if (page) {
         window.loadPage(page, title, parent);
         ocultarLoading();
      } else {
         window.location.reload();
      }
    }, 1200);
  }
});

(function () {
  const script = document.createElement("script");
  script.src = "modulos/servicos/paginas_erro/assets/js/erro.js";
  script.async = true;
  document.head.appendChild(script);
})();

function sincronizarNavbarTema() {
  const $navbar = $("#main-navbar");
  if (!$navbar.length) return;

  if ($("body").hasClass("dark-mode")) {
    $navbar.removeClass("navbar-light bg-white")
           .addClass("navbar-dark bg-dark"); 
  } else {
    $navbar.removeClass("navbar-dark bg-dark")
           .addClass("navbar-light bg-white");
  }
}
