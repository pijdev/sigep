// Data atual para os formulários
(function () {
  const today = new Date().toISOString().split("T")[0];
  document.getElementById("data_entrada").value = today;
  document.getElementById("data_saida").value = today;

  // Carregar dados iniciais
  carregarEstoque();
  carregarLocais();
})();

// Carregar estoque atual
function carregarEstoque() {
  fetch("includes/internos_colchoes_logica.php", {
    method: "POST",
    body: new URLSearchParams({ action: "get_estoque" }),
  })
    .then((response) => response.json())
    .then((data) => {
      const divEstoque = document.getElementById("estoque-resumo");

      if (data.success && data.estoque.length > 0) {
        let html = '<div class="row">';
        let total = 0;

        data.estoque.forEach((local) => {
          total += local.quantidade;
          const statusBadge =
            local.quantidade > 0
              ? '<span class="badge bg-success">Ativo</span>'
              : '<span class="badge bg-warning">Vazio</span>';

          html += `
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-info"><i class="fas fa-warehouse"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">${local.nome}</span>
                                    <span class="info-box-number">${local.quantidade} colchões</span>
                                    <div class="progress">
                                        <div class="progress-bar" style="width: ${Math.min(100, (local.quantidade / (local.capacidade_maxima || 100)) * 100)}%"></div>
                                    </div>
                                    <span class="progress-description">
                                        Capacidade: ${local.capacidade_maxima || "Ilimitada"} | ${statusBadge}
                                    </span>
                                </div>
                            </div>
                        </div>
                    `;
        });

        html += "</div>";
        html += `
                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                <strong>Total em Estoque:</strong> ${total} colchões
                            </div>
                        </div>
                    </div>
                `;

        divEstoque.innerHTML = html;
      } else {
        divEstoque.innerHTML =
          '<div class="alert alert-warning">Nenhum estoque encontrado.</div>';
      }
    })
    .catch((error) => {
      console.error("Erro ao carregar estoque:", error);
      document.getElementById("estoque-resumo").innerHTML =
        '<div class="alert alert-danger">Erro ao carregar estoque.</div>';
    });
}

// Carregar locais para selects
function carregarLocais() {
  fetch("includes/internos_colchoes_logica.php", {
    method: "POST",
    body: new URLSearchParams({ action: "get_locais" }),
  })
    .then((response) => response.json())
    .then((data) => {
      const selectOrigem = document.getElementById("id_local_destino");
      const selectOrigemSaida = document.getElementById("id_local_origem");

      selectOrigem.innerHTML = '<option value="">Selecione...</option>';
      selectOrigemSaida.innerHTML = '<option value="">Selecione...</option>';

      if (data.success && data.locais.length > 0) {
        data.locais.forEach((local) => {
          if (local.status === "Ativo") {
            const option = `<option value="${local.id}">${local.nome} (${local.quantidade} disponíveis)</option>`;
            selectOrigem.innerHTML += option;
            selectOrigemSaida.innerHTML += option;
          }
        });
      }
    })
    .catch((error) => {
      console.error("Erro ao carregar locais:", error);
    });
}

// Toggle campos de destino
function toggleDestinoFields() {
  const tipoDestino = document.getElementById("tipo_destino").value;
  const rowOutro = document.getElementById("row_outro");

  if (tipoDestino === "Outro") {
    rowOutro.style.display = "block";
    document.getElementById("destino_outro").required = true;
  } else {
    rowOutro.style.display = "none";
    document.getElementById("destino_outro").required = false;
    document.getElementById("destino_outro").value = "";
  }
}

// Carregar histórico
function carregarHistorico() {
  const tipo = document.getElementById("filtro_tipo").value;
  const dataInicio = document.getElementById("filtro_data_inicio").value;
  const dataFim = document.getElementById("filtro_data_fim").value;

  const params = new URLSearchParams({
    action: "get_historico",
    tipo: tipo,
    data_inicio: dataInicio,
    data_fim: dataFim,
  });

  const div = document.getElementById("historico-lista");
  div.innerHTML =
    '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Carregando...</div>';

  fetch("includes/internos_colchoes_logica.php", {
    method: "POST",
    body: params,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success && data.historico.length > 0) {
        let html = `
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Tipo</th>
                                    <th>Quantidade</th>
                                    <th>Local</th>
                                    <th>Destino</th>
                                    <th>Responsável</th>
                                    <th>Observações</th>
                                </tr>
                            </thead>
                            <tbody>
                `;

        data.historico.forEach((item) => {
          const tipoIcon =
            item.tipo === "Entrada"
              ? "fa-plus-circle text-success"
              : "fa-minus-circle text-danger";
          html += `
                        <tr>
                            <td>${formatDate(item.data)}</td>
                            <td><i class="fas ${tipoIcon}"></i> ${item.tipo}</td>
                            <td>${item.quantidade}</td>
                            <td>${item.local_origem || item.local_destino || "-"}</td>
                            <td>${item.destino || "-"}</td>
                            <td>${item.responsavel || "-"}</td>
                            <td>${item.observacoes || "-"}</td>
                        </tr>
                    `;
        });

        html += "</tbody></table></div>";
        div.innerHTML = html;
      } else {
        div.innerHTML =
          '<div class="alert alert-info">Nenhum histórico encontrado com os filtros selecionados.</div>';
      }
    })
    .catch((error) => {
      console.error("Erro ao carregar histórico:", error);
      div.innerHTML =
        '<div class="alert alert-danger">Erro ao carregar histórico.</div>';
    });
}

// Formulário de entrada
document
  .getElementById("form-entrada-colchoes")
  .addEventListener("submit", function (e) {
    e.preventDefault();

    const formData = new FormData(this);
    formData.append("action", "registrar_entrada");

    fetch("/sigep/paginas/internos_colchoes_gestao.php", {
      method: "POST",
      body: formData,
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          showAlert("Entrada registrada com sucesso!", "success");
          this.reset();
          document.getElementById("data_entrada").value = new Date()
            .toISOString()
            .split("T")[0];
          carregarEstoque();
          carregarLocais();
        } else {
          showAlert(data.message || "Erro ao registrar entrada.", "error");
        }
      })
      .catch((error) => {
        console.error("Erro:", error);
        showAlert("Erro ao registrar entrada.", "error");
      });
  });

// Formulário de saída
document
  .getElementById("form-saida-colchoes")
  .addEventListener("submit", function (e) {
    e.preventDefault();

    const formData = new FormData(this);
    formData.append("action", "registrar_saida");

    fetch("/sigep/paginas/internos_colchoes_gestao.php", {
      method: "POST",
      body: formData,
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          showAlert("Saída registrada com sucesso!", "success");
          this.reset();
          document.getElementById("data_saida").value = new Date()
            .toISOString()
            .split("T")[0];
          document.getElementById("row_outro").style.display = "none";
          carregarEstoque();
          carregarLocais();
        } else {
          showAlert(data.message || "Erro ao registrar saída.", "error");
        }
      })
      .catch((error) => {
        console.error("Erro:", error);
        showAlert("Erro ao registrar saída.", "error");
      });
  });

// Função auxiliar para formatar datas
function formatDate(dateStr) {
  if (!dateStr) return "-";
  const date = new Date(dateStr);
  return date.toLocaleDateString("pt-BR");
}

// Função para mostrar alertas
function showAlert(message, type) {
  const alertClass = type === "success" ? "alert-success" : "alert-danger";
  const alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    `;

  // Inserir no topo do conteúdo
  const firstCard = document.querySelector(".card");
  if (firstCard) {
    firstCard.insertAdjacentHTML("beforebegin", alertHtml);
  }

  // Auto remover após 5 segundos
  setTimeout(() => {
    const alert = document.querySelector(".alert");
    if (alert) {
      alert.remove();
    }
  }, 5000);
}

// Funções para gerenciar locais
function abrirGerenciarLocais() {
  $("#modalGerenciarLocais").modal("show");
  carregarListaLocais();
}

// Fechar offcanvas ao clicar no backdrop
$(document).on("click", ".offcanvas-backdrop", function () {
  $("#offcanvasGerenciarLocais").removeClass("show");
  $(".offcanvas-backdrop").removeClass("show");
});

// Fechar offcanvas ao clicar no botão close
$(document).on("click", '[data-bs-dismiss="offcanvas"]', function () {
  $("#offcanvasGerenciarLocais").removeClass("show");
  $(".offcanvas-backdrop").removeClass("show");
});

function carregarListaLocais() {
  fetch("includes/internos_colchoes_logica.php", {
    method: "POST",
    body: new URLSearchParams({ action: "get_locais" }),
  })
    .then((response) => response.json())
    .then((data) => {
      const div = document.getElementById("lista-locais");

      if (data.success) {
        let html = '<div class="list-group">';
        data.locais.forEach((local) => {
          const statusBadge =
            local.status === "Ativo"
              ? '<span class="badge bg-success">Ativo</span>'
              : '<span class="badge bg-secondary">Inativo</span>';

          html += `
                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">${local.nome}</h6>
                                <div>${statusBadge}</div>
                            </div>
                            <p class="mb-1">${local.descricao || ""}</p>
                            <small>Tipo: ${local.tipo} | Capacidade: ${local.capacidade_maxima || "Não definida"}</small>
                            <div class="mt-2">
                                <button class="btn btn-sm btn-outline-primary" onclick="editarLocal(${local.id})">
                                    <i class="fas fa-edit"></i> Editar
                                </button>
                                <button class="btn btn-sm btn-outline-danger" onclick="excluirLocal(${local.id})">
                                    <i class="fas fa-trash"></i> Excluir
                                </button>
                            </div>
                        </div>
                    `;
        });
        html += "</div>";
        div.innerHTML = html;
      } else {
        div.innerHTML =
          '<div class="alert alert-warning">Nenhum local encontrado.</div>';
      }
    })
    .catch((error) => {
      console.error("Erro ao carregar locais:", error);
      document.getElementById("lista-locais").innerHTML =
        '<div class="alert alert-danger">Erro ao carregar locais.</div>';
    });
}

function mostrarFormLocal(localId = null) {
  const formContainer = document.getElementById("form-local-container");
  const form = document.getElementById("form-local");
  const title = document.getElementById("form-local-title");

  // Limpar formulário
  form.reset();
  document.getElementById("local_id").value = "";

  if (localId) {
    title.textContent = "Editar Local";
    // Carregar dados do local
    fetch("includes/internos_colchoes_logica.php", {
      method: "POST",
      body: new URLSearchParams({ action: "get_local", id: localId }),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          const local = data.local;
          document.getElementById("local_id").value = local.id;
          document.getElementById("local_nome").value = local.nome;
          document.getElementById("local_descricao").value =
            local.descricao || "";
          document.getElementById("local_tipo").value = local.tipo;
          document.getElementById("local_capacidade").value =
            local.capacidade_maxima || "";
          document.getElementById("local_status").value = local.status;
        }
      });
  } else {
    title.textContent = "Novo Local";
  }

  formContainer.style.display = "block";
}

function cancelarFormLocal() {
  document.getElementById("form-local-container").style.display = "none";
  document.getElementById("form-local").reset();
  document.getElementById("local_id").value = "";
}

function salvarLocal() {
  const form = document.getElementById("form-local");
  const formData = new FormData(form);
  formData.append(
    "action",
    formData.get("id") ? "atualizar_local" : "criar_local",
  );

  fetch("/sigep/paginas/internos_colchoes_gestao.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        showAlert(data.message, "success");
        cancelarFormLocal();
        carregarListaLocais();
        carregarLocais(); // Atualizar selects
        carregarEstoque(); // Atualizar estoque
      } else {
        showAlert(data.message || "Erro ao salvar local.", "error");
      }
    })
    .catch((error) => {
      console.error("Erro:", error);
      showAlert("Erro ao salvar local.", "error");
    });
}

function editarLocal(id) {
  mostrarFormLocal(id);
}

function excluirLocal(id) {
  if (
    confirm(
      "Tem certeza que deseja excluir este local? Isso também excluirá o registro de estoque.",
    )
  ) {
    fetch("/sigep/paginas/internos_colchoes_gestao.php", {
      method: "POST",
      body: new URLSearchParams({
        action: "excluir_local",
        id: id,
      }),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          showAlert(data.message, "success");
          carregarListaLocais();
          carregarLocais();
          carregarEstoque();
        } else {
          showAlert(data.message || "Erro ao excluir local.", "error");
        }
      })
      .catch((error) => {
        console.error("Erro:", error);
        showAlert("Erro ao excluir local.", "error");
      });
  }
}

// Função para imprimir relatório de colchões
function imprimirRelatorioColchoes() {
  const tipo = document.getElementById("filtro_tipo").value;
  const dataInicio = document.getElementById("filtro_data_inicio").value;
  const dataFim = document.getElementById("filtro_data_fim").value;

  // Construir URL com parâmetros
  const params = new URLSearchParams({
    tipo: tipo,
    data_inicio: dataInicio,
    data_fim: dataFim,
  });

  // Abrir relatório em nova janela com URL amigável
  window.open(
    `/relatorios/relatorio-colchoes?${params.toString()}`,
    "_blank",
    "width=1200,height=800,scrollbars=yes",
  );
}
