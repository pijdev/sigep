// assets/js/global.js
// Funções globais do sistema SIGEP

// Carregar script de páginas de erro se disponível
(function () {
  const script = document.createElement("script");
  script.src = "modulos/servicos/paginas_erro/assets/js/erro.js";
  script.async = true;
  document.head.appendChild(script);
})();

// Função global para carregar páginas via AJAX
async function loadPage(pageUrl, title = "Painel", parent = "SIGEP") {
  if ($("body").hasClass("sidebar-open"))
    $('[data-widget="pushmenu"]').PushMenu("toggle");
  document.getElementById("breadcrumb-parent").innerText = parent;
  document.getElementById("breadcrumb-title").innerText = title;
  document.getElementById("content-main-title").innerText = title;
  const mainContent = document.getElementById("main-content");
  mainContent.innerHTML = `<div class="text-center p-5"><i class="fas fa-spinner fa-spin fa-2x text-primary"></i></div>`;
  try {
    const response = await fetch(pageUrl);
    if (!response.ok) {
      // Tratar diferentes tipos de erro HTTP
      if (response.status === 403) {
        // Erro 403 - Acesso Negado
        if (typeof SIGEPErro !== "undefined" && SIGEPErro.carregarPaginaErro) {
          SIGEPErro.carregarPaginaErro(
            "403",
            "Acesso negado a esta página",
            pageUrl,
          );
          return;
        } else {
          throw new Error("Acesso negado (403 Forbidden).");
        }
      } else if (response.status === 404) {
        // Erro 404 - Página Não Encontrada
        if (typeof SIGEPErro !== "undefined" && SIGEPErro.carregarPaginaErro) {
          SIGEPErro.carregarPaginaErro(
            "404",
            "A página solicitada não foi encontrada",
            pageUrl,
          );
          return;
        } else {
          throw new Error("Página não localizada (404 Not Found).");
        }
      } else if (response.status >= 500) {
        // Erro 5xx - Erro do Servidor
        if (typeof SIGEPErro !== "undefined" && SIGEPErro.carregarPaginaErro) {
          SIGEPErro.carregarPaginaErro(
            "500",
            "Erro interno do servidor",
            pageUrl,
          );
          return;
        } else {
          throw new Error(
            `Erro do servidor (${response.status} Internal Server Error).`,
          );
        }
      } else {
        // Outros erros HTTP
        if (typeof SIGEPErro !== "undefined" && SIGEPErro.carregarPaginaErro) {
          SIGEPErro.carregarPaginaErro(
            response.status.toString(),
            `Erro HTTP ${response.status}`,
            pageUrl,
          );
          return;
        } else {
          throw new Error(
            `Erro HTTP ${response.status}: ${response.statusText}`,
          );
        }
      }
    }
    let html = await response.text();

    // Processar links CSS e JS antes de inserir no DOM
    const tempDiv = document.createElement("div");
    tempDiv.innerHTML = html;

    // Função para corrigir caminhos relativos
    function fixRelativePath(href, basePath = "/sigep/") {
      if (!href) return href;
      if (href.startsWith("http")) return href; // Já é absoluto
      if (href.startsWith("/")) return href; // Já é absoluto da raiz

      // Tratar caminhos relativos que começam com ../
      if (href.startsWith("../")) {
        // Remove o ../ e adiciona diretamente ao basePath
        const relativePath = href.substring(3); // Remove '../'
        return basePath + relativePath;
      }

      // Para outros caminhos relativos, adicionar ao basePath
      return basePath + href;
    }

    // Processar links CSS
    const cssLinks = tempDiv.querySelectorAll('link[rel="stylesheet"]');
    cssLinks.forEach((link) => {
      const href = link.getAttribute("href");
      const correctedHref = fixRelativePath(href);

      if (
        correctedHref &&
        !document.querySelector(`link[href="${correctedHref}"]`)
      ) {
        const newLink = document.createElement("link");
        newLink.rel = "stylesheet";
        newLink.href = correctedHref;
        document.head.appendChild(newLink);
      }
      link.remove(); // Remover do HTML para evitar duplicação
    });

    // Processar scripts externos
    const scriptTags = tempDiv.querySelectorAll("script[src]");
    scriptTags.forEach((script) => {
      const src = script.getAttribute("src");
      const correctedSrc = fixRelativePath(src);

      if (
        correctedSrc &&
        !document.querySelector(`script[src="${correctedSrc}"]`)
      ) {
        const newScript = document.createElement("script");
        newScript.src = correctedSrc;
        document.head.appendChild(newScript);
      }
      script.remove(); // Remover do HTML para evitar duplicação
    });

    // Inserir HTML processado
    mainContent.innerHTML = tempDiv.innerHTML;

    // Processar scripts inline (sem src)
    const scripts = mainContent.querySelectorAll("script:not([src])");
    scripts.forEach((oldScript) => {
      const newScript = document.createElement("script");
      Array.from(oldScript.attributes).forEach((attr) =>
        newScript.setAttribute(attr.name, attr.value),
      );
      newScript.appendChild(document.createTextNode(oldScript.innerHTML));
      oldScript.parentNode.replaceChild(newScript, oldScript);
    });

    // Aguarda um pouco para garantir que os elementos estão no DOM
    await new Promise((resolve) => setTimeout(resolve, 200));

    // Atualiza variáveis de ordenação para a página carregada
    if (typeof window.updateSortVariables === "function") {
      window.updateSortVariables();
    }

    // Verifica se a página carregada define seu próprio título
    if (window.pageTitle) {
      title = window.pageTitle;
      document.getElementById("breadcrumb-title").innerText = title;
      document.getElementById("content-main-title").innerText = title;
    }
  } catch (e) {
    // Tratar erros de forma mais específica
    console.error("Erro ao carregar página:", e);

    // Se for erro de rede ou fetch, tentar usar página de erro
    if (
      e.message.includes("Failed to fetch") ||
      e.message.includes("403") ||
      e.message.includes("404") ||
      e.message.includes("500")
    ) {
      if (typeof SIGEPErro !== "undefined" && SIGEPErro.carregarPaginaErro) {
        // Extrair código do erro da mensagem
        let codigoErro = "500"; // padrão
        let mensagemErro = e.message;

        if (e.message.includes("403")) {
          codigoErro = "403";
          mensagemErro = "Acesso negado a esta página";
        } else if (e.message.includes("404")) {
          codigoErro = "404";
          mensagemErro = "A página solicitada não foi encontrada";
        } else if (e.message.includes("500")) {
          codigoErro = "500";
          mensagemErro = "Erro interno do servidor";
        }

        SIGEPErro.carregarPaginaErro(codigoErro, mensagemErro, pageUrl);
        return;
      }
    }

    mainContent.innerHTML = `<div class='alert alert-danger shadow-sm'>ERRO: ${e.message}</div>`;
  }
}

// Funções do painel de internos movidas para modulos/geral/painel_internos/assets/js/internos_painel.js
