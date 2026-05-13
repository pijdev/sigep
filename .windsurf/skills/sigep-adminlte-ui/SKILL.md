---
name: sigep-adminlte-ui
description: Creates AdminLTE 3 compatible UI components for SIGEP with proper styling and responsive design
---

# SIGEP AdminLTE UI Skill

## Purpose

This skill provides standardized AdminLTE 3 UI components and patterns specifically designed for the SIGEP system, ensuring consistency, responsiveness, and proper integration with the existing frontend architecture.

## AdminLTE 3 Standards for SIGEP

### Required CSS Classes

```html
<!-- Standard card structure -->
<div class="card">
  <div class="card-header">
    <h3 class="card-title">
      <i class="fas fa-cog mr-2"></i>
      Card Title
    </h3>
  </div>
  <div class="card-body">
    <!-- Content here -->
  </div>
</div>

<!-- Small box for statistics -->
<div class="small-box bg-info">
  <div class="inner">
    <h3>150</h3>
    <p>New Orders</p>
  </div>
  <div class="icon">
    <i class="fas fa-shopping-cart"></i>
  </div>
</div>
```

### Standard JavaScript Integration

```javascript
// SIGEP standard initialization
$(document).ready(function () {
  // Initialize tooltips
  $('[data-toggle="tooltip"]').tooltip();

  // Initialize popovers
  $('[data-toggle="popover"]').popover();

  // Initialize select2 if available
  if (typeof $.fn.select2 !== "undefined") {
    $(".select2").select2({
      theme: "bootstrap4",
      width: "100%",
    });
  }

  // Initialize toastr if available
  if (typeof toastr !== "undefined") {
    toastr.options = {
      closeButton: true,
      debug: false,
      newestOnTop: false,
      progressBar: false,
      positionClass: "toast-top-right",
      preventDuplicates: false,
      onclick: null,
      showDuration: "300",
      hideDuration: "1000",
      timeOut: "5000",
      extendedTimeOut: "1000",
      showEasing: "swing",
      hideEasing: "linear",
      showMethod: "fadeIn",
      hideMethod: "fadeOut",
    };
  }

  // Initialize data tables if available
  if (typeof $.fn.DataTable !== "undefined") {
    $(".datatable").DataTable({
      responsive: true,
      language: {
        url: "//cdn.datatables.net/plug-ins/1.10.24/i18n/Portuguese-Brasil.json",
      },
    });
  }
});
```

## Component Templates

### Data Tables

```html
<!-- Standard SIGEP data table -->
<div class="card">
  <div class="card-header">
    <h3 class="card-title">
      <i class="fas fa-table mr-2"></i>
      Lista de Registros
    </h3>
    <div class="card-tools">
      <button
        type="button"
        class="btn btn-tool btn-sm"
        data-card-widget="collapse"
      >
        <i class="fas fa-minus"></i>
      </button>
      <button
        type="button"
        class="btn btn-tool btn-sm"
        data-card-widget="maximize"
      >
        <i class="fas fa-expand"></i>
      </button>
    </div>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-bordered table-striped datatable">
        <thead>
          <tr>
            <th>ID</th>
            <th>Nome</th>
            <th>Status</th>
            <th>Criado em</th>
            <th>Ações</th>
          </tr>
        </thead>
        <tbody>
          <!-- Table content will be populated here -->
        </tbody>
      </table>
    </div>
  </div>
</div>
```

### Forms

```html
<!-- Standard SIGEP form -->
<div class="card">
  <div class="card-header">
    <h3 class="card-title">
      <i class="fas fa-edit mr-2"></i>
      Formulário de Cadastro
    </h3>
  </div>
  <form id="sigep-form" class="form-horizontal">
    <div class="card-body">
      <div class="form-group row">
        <label for="nome" class="col-sm-2 col-form-label">Nome</label>
        <div class="col-sm-10">
          <input
            type="text"
            class="form-control"
            id="nome"
            name="nome"
            required
          />
          <span class="help-block">Informe o nome completo</span>
        </div>
      </div>

      <div class="form-group row">
        <label for="email" class="col-sm-2 col-form-label">Email</label>
        <div class="col-sm-10">
          <input
            type="email"
            class="form-control"
            id="email"
            name="email"
            required
          />
          <span class="help-block">Email institucional</span>
        </div>
      </div>

      <div class="form-group row">
        <label for="setor" class="col-sm-2 col-form-label">Setor</label>
        <div class="col-sm-10">
          <select class="form-control select2" id="setor" name="setor" required>
            <option value="">Selecione...</option>
            <!-- Options populated via AJAX -->
          </select>
        </div>
      </div>

      <div class="form-group row">
        <label for="observacoes" class="col-sm-2 col-form-label"
          >Observações</label
        >
        <div class="col-sm-10">
          <textarea
            class="form-control"
            id="observacoes"
            name="observacoes"
            rows="3"
          ></textarea>
        </div>
      </div>
    </div>
    <div class="card-footer">
      <button type="submit" class="btn btn-primary">
        <i class="fas fa-save mr-2"></i>
        Salvar
      </button>
      <button type="button" class="btn btn-default ml-2" onclick="resetForm()">
        <i class="fas fa-undo mr-2"></i>
        Cancelar
      </button>
    </div>
  </form>
</div>
```

### Modals

```html
<!-- Standard SIGEP modal -->
<div class="modal fade" id="sigep-modal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="fas fa-info-circle mr-2"></i>
          <span id="modal-title">Título do Modal</span>
        </h5>
        <button
          type="button"
          class="close"
          data-dismiss="modal"
          aria-label="Close"
        >
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div id="modal-content">
          <!-- Modal content will be populated here -->
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" id="modal-confirm">
          <i class="fas fa-check mr-2"></i>
          Confirmar
        </button>
        <button type="button" class="btn btn-default" data-dismiss="modal">
          <i class="fas fa-times mr-2"></i>
          Cancelar
        </button>
      </div>
    </div>
  </div>
</div>
```

### Alert Messages

```html
<!-- Success alert -->
<div class="alert alert-success alert-dismissible">
  <button type="button" class="close" data-dismiss="alert" aria-hidden="true">
    &times;
  </button>
  <h5><i class="icon fas fa-check"></i> Sucesso!</h5>
  Operação realizada com sucesso.
</div>

<!-- Error alert -->
<div class="alert alert-danger alert-dismissible">
  <button type="button" class="close" data-dismiss="alert" aria-hidden="true">
    &times;
  </button>
  <h5><i class="icon fas fa-ban"></i> Erro!</h5>
  Ocorreu um erro ao processar a solicitação.
</div>

<!-- Warning alert -->
<div class="alert alert-warning alert-dismissible">
  <button type="button" class="close" data-dismiss="alert" aria-hidden="true">
    &times;
  </button>
  <h5><i class="icon fas fa-exclamation-triangle"></i> Atenção!</h5>
  Verifique os dados informados.
</div>

<!-- Info alert -->
<div class="alert alert-info alert-dismissible">
  <button type="button" class="close" data-dismiss="alert" aria-hidden="true">
    &times;
  </button>
  <h5><i class="icon fas fa-info"></i> Informação</h5>
  Dados atualizados recentemente.
</div>
```

## JavaScript Patterns

### AJAX Integration

```javascript
// Standard AJAX wrapper for SIGEP
class SIGEPAjax {
  static request(url, data = {}, method = "POST") {
    return $.ajax({
      url: url,
      method: method,
      data: data,
      dataType: "json",
      beforeSend: function () {
        SIGEPU.showLoading();
      },
      complete: function () {
        SIGEPU.hideLoading();
      },
      error: function (xhr, status, error) {
        SIGEPU.showError("Erro na comunicação com o servidor");
        console.error("AJAX Error:", error);
      },
    });
  }

  static post(url, data) {
    return this.request(url, data, "POST");
  }

  static get(url, params = {}) {
    return this.request(url, params, "GET");
  }
}

// Form submission handler
function handleFormSubmit(formId, successCallback) {
  $(`#${formId}`).on("submit", function (e) {
    e.preventDefault();

    const formData = new FormData(this);
    const data = Object.fromEntries(formData.entries());

    SIGEPAjax.post($(this).attr("action"), data).done(function (response) {
      if (response.success) {
        SIGEPU.showSuccess(
          response.message || "Operação realizada com sucesso",
        );
        if (successCallback) successCallback(response);
      } else {
        SIGEPU.showError(response.error || "Erro na operação");
      }
    });
  });
}
```

### UI Utilities

```javascript
// UI utility functions
const SIGEPU = {
  // Loading states
  showLoading: function () {
    if (!$(".loading-overlay").length) {
      $("body").append(`
                <div class="loading-overlay">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Carregando...</span>
                    </div>
                </div>
            `);
    }
  },

  hideLoading: function () {
    $(".loading-overlay").remove();
  },

  // Notifications
  showSuccess: function (message) {
    this.showAlert(message, "success");
  },

  showError: function (message) {
    this.showAlert(message, "danger");
  },

  showWarning: function (message) {
    this.showAlert(message, "warning");
  },

  showInfo: function (message) {
    this.showAlert(message, "info");
  },

  showAlert: function (message, type) {
    const alertId = "alert-" + Date.now();
    const alertHtml = `
            <div class="alert alert-${type} alert-dismissible" id="${alertId}">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                ${message}
            </div>
        `;

    // Add alert to top of content
    $(".content-wrapper").prepend(alertHtml);

    // Auto-remove after 5 seconds
    setTimeout(() => {
      $(`#${alertId}`).fadeOut("slow", function () {
        $(this).remove();
      });
    }, 5000);
  },

  // Modal utilities
  showModal: function (title, content, confirmCallback) {
    $("#modal-title").text(title);
    $("#modal-content").html(content);
    $("#sigep-modal").modal("show");

    $("#modal-confirm")
      .off("click")
      .on("click", function () {
        if (confirmCallback) confirmCallback();
        $("#sigep-modal").modal("hide");
      });
  },

  hideModal: function () {
    $("#sigep-modal").modal("hide");
  },

  // Form utilities
  resetForm: function (formId) {
    $(`#${formId}`)[0].reset();
    $(`#${formId} .is-invalid`).removeClass("is-invalid");
    $(`#${formId} .invalid-feedback`).remove();
  },

  validateForm: function (formId) {
    const form = $(`#${formId}`);
    let isValid = true;

    form.find("[required]").each(function () {
      const field = $(this);
      const value = field.val().trim();

      if (!value) {
        field.addClass("is-invalid");
        if (!field.next(".invalid-feedback").length) {
          field.after('<div class="invalid-feedback">Campo obrigatório</div>');
        }
        isValid = false;
      } else {
        field.removeClass("is-invalid");
        field.next(".invalid-feedback").remove();
      }
    });

    return isValid;
  },

  // Table utilities
  refreshTable: function (tableId, data) {
    const table = $(`#${tableId}`);
    const tbody = table.find("tbody");

    tbody.empty();

    data.forEach(function (item) {
      const row = createTableRow(item);
      tbody.append(row);
    });

    // Reinitialize DataTable if exists
    if ($.fn.DataTable && table.hasClass("datatable")) {
      table.DataTable().clear().rows.add(data).draw();
    }
  },

  // Date formatting
  formatDate: function (dateString) {
    if (!dateString) return "-";

    const date = new Date(dateString);
    return date.toLocaleDateString("pt-BR", {
      day: "2-digit",
      month: "2-digit",
      year: "numeric",
    });
  },

  formatDateTime: function (dateString) {
    if (!dateString) return "-";

    const date = new Date(dateString);
    return date.toLocaleString("pt-BR", {
      day: "2-digit",
      month: "2-digit",
      year: "numeric",
      hour: "2-digit",
      minute: "2-digit",
    });
  },

  // Currency formatting
  formatCurrency: function (value) {
    return new Intl.NumberFormat("pt-BR", {
      style: "currency",
      currency: "BRL",
    }).format(value);
  },
};

// Auto-initialize when DOM is ready
$(document).ready(function () {
  // Initialize all tooltips
  $('[data-toggle="tooltip"]').tooltip();

  // Initialize all popovers
  $('[data-toggle="popover"]').popover();

  // Auto-hide alerts after 5 seconds
  $(".alert").each(function () {
    const alert = $(this);
    setTimeout(() => {
      alert.fadeOut("slow", function () {
        $(this).remove();
      });
    }, 5000);
  });
});
```

### Table Row Creation

```javascript
// Create table row dynamically
function createTableRow(data) {
  let row = "<tr>";

  // ID column
  row += `<td>${data.id || "-"}</td>`;

  // Name column with optional link
  if (data.edit_url) {
    row += `<td><a href="${data.edit_url}" class="btn-link">${data.name || "-"}</a></td>`;
  } else {
    row += `<td>${data.name || "-"}</td>`;
  }

  // Status column with badge
  const statusClass = getStatusClass(data.status);
  row += `<td><span class="badge ${statusClass}">${data.status || "-"}</span></td>`;

  // Date column
  row += `<td>${SIGEPU.formatDate(data.created_at)}</td>`;

  // Actions column
  row += `<td>
        <div class="btn-group btn-group-sm">
            <button type="button" class="btn btn-info btn-sm" onclick="editRecord(${data.id})" title="Editar">
                <i class="fas fa-edit"></i>
            </button>
            <button type="button" class="btn btn-danger btn-sm" onclick="deleteRecord(${data.id})" title="Excluir">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    </td>`;

  row += "</tr>";

  return row;
}

// Get status badge class
function getStatusClass(status) {
  const statusMap = {
    ativo: "badge-success",
    inativo: "badge-secondary",
    pendente: "badge-warning",
    cancelado: "badge-danger",
    finalizado: "badge-primary",
  };

  return statusMap[status] || "badge-secondary";
}
```

## Responsive Design Patterns

### Mobile-First Tables

```html
<!-- Responsive table for mobile -->
<div class="table-responsive">
  <table class="table table-striped">
    <thead class="thead-dark">
      <tr>
        <th scope="col">#</th>
        <th scope="col">Nome</th>
        <th scope="col" class="d-none d-md-table-cell">Email</th>
        <th scope="col" class="d-none d-lg-table-cell">Setor</th>
        <th scope="col">Ações</th>
      </tr>
    </thead>
    <tbody>
      <!-- Table rows -->
    </tbody>
  </table>
</div>
```

### Card Stacking on Mobile

```html
<!-- Cards that stack on mobile -->
<div class="row">
  <div class="col-lg-3 col-md-6 col-sm-12 mb-3">
    <div class="small-box bg-info">
      <div class="inner">
        <h3>150</h3>
        <p>Novos Usuários</p>
      </div>
      <div class="icon">
        <i class="fas fa-users"></i>
      </div>
    </div>
  </div>

  <div class="col-lg-3 col-md-6 col-sm-12 mb-3">
    <div class="small-box bg-success">
      <div class="inner">
        <h3>53</h3>
        <p>Tarefas Concluídas</p>
      </div>
      <div class="icon">
        <i class="fas fa-check"></i>
      </div>
    </div>
  </div>
</div>
```

## Custom CSS for SIGEP

### Additional Styles

```css
/* SIGEP-specific styles */
.sigep-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 1rem;
  border-radius: 0.5rem;
  margin-bottom: 1rem;
}

.sigep-card {
  border-left: 4px solid #007bff;
  transition: all 0.3s ease;
}

.sigep-card:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  transform: translateY(-2px);
}

.sigep-badge-primary {
  background-color: #007bff;
  color: white;
  padding: 0.25rem 0.5rem;
  border-radius: 0.25rem;
  font-size: 0.75rem;
}

.sigep-loading {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}

.sigep-loading .spinner {
  width: 3rem;
  height: 3rem;
  border-width: 0.3rem;
}

/* Mobile optimizations */
@media (max-width: 768px) {
  .sigep-header {
    padding: 0.5rem;
  }

  .card-body {
    padding: 1rem;
  }

  .btn-group {
    flex-direction: column;
  }

  .btn-group .btn {
    margin-bottom: 0.25rem;
  }
}

/* Print styles */
@media print {
  .no-print {
    display: none !important;
  }

  .card {
    break-inside: avoid;
    border: 1px solid #ddd !important;
  }

  .btn {
    display: none !important;
  }
}
```

## Integration Examples

### Complete Page Template

```html
<!DOCTYPE html>
<html lang="pt-br">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>SIGEP - Módulo</title>

    <!-- AdminLTE CSS -->
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css"
    />
    <!-- FontAwesome -->
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.2.0/css/all.min.css"
    />
    <!-- Select2 -->
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css"
    />
    <!-- Toastr -->
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/2.1.4/toastr.min.css"
    />
    <!-- SIGEP Custom CSS -->
    <link rel="stylesheet" href="assets/css/sigep-custom.css" />
  </head>
  <body class="hold-transition sidebar-mini layout-fixed">
    <!-- Content Wrapper -->
    <div class="content-wrapper">
      <!-- Content Header -->
      <div class="content-header">
        <div class="container-fluid">
          <div class="row mb-2">
            <div class="col-sm-6">
              <h1 class="m-0">Módulo SIGEP</h1>
            </div>
            <div class="col-sm-6">
              <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item active">Módulo</li>
              </ol>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Content -->
      <section class="content">
        <div class="container-fluid">
          <!-- Statistics Cards -->
          <div class="row">
            <div class="col-lg-3 col-6">
              <div class="small-box bg-info">
                <div class="inner">
                  <h3 id="total-users">0</h3>
                  <p>Usuários Ativos</p>
                </div>
                <div class="icon">
                  <i class="fas fa-users"></i>
                </div>
              </div>
            </div>
          </div>

          <!-- Main Card -->
          <div class="card">
            <div class="card-header">
              <h3 class="card-title">
                <i class="fas fa-list mr-2"></i>
                Lista de Registros
              </h3>
              <div class="card-tools">
                <button
                  type="button"
                  class="btn btn-sm btn-primary"
                  onclick="showAddModal()"
                >
                  <i class="fas fa-plus mr-1"></i>
                  Novo
                </button>
              </div>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table
                  class="table table-bordered table-striped"
                  id="main-table"
                >
                  <thead>
                    <tr>
                      <th>ID</th>
                      <th>Nome</th>
                      <th>Status</th>
                      <th>Criado em</th>
                      <th>Ações</th>
                    </tr>
                  </thead>
                  <tbody>
                    <!-- Content populated via AJAX -->
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="add-modal" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Novo Registro</h5>
            <button type="button" class="close" data-dismiss="modal">
              &times;
            </button>
          </div>
          <form id="add-form">
            <div class="modal-body">
              <div class="form-group">
                <label for="nome">Nome</label>
                <input
                  type="text"
                  class="form-control"
                  id="nome"
                  name="nome"
                  required
                />
              </div>
              <div class="form-group">
                <label for="email">Email</label>
                <input
                  type="email"
                  class="form-control"
                  id="email"
                  name="email"
                  required
                />
              </div>
            </div>
            <div class="modal-footer">
              <button type="submit" class="btn btn-primary">Salvar</button>
              <button
                type="button"
                class="btn btn-default"
                data-dismiss="modal"
              >
                Cancelar
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- AdminLTE JS -->
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
    <!-- FontAwesome -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.2.0/js/all.min.js"></script>
    <!-- Select2 -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <!-- Toastr -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/2.1.4/toastr.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.5.1/dist/chart.umd.js"></script>
    <!-- SIGEP Custom JS -->
    <script src="assets/js/sigep-module.js"></script>
  </body>
</html>
```

### JavaScript Module Pattern

```javascript
// assets/js/sigep-module.js
(function ($) {
  "use strict";

  // Module namespace
  window.SIGEPModule = {
    // Initialize module
    init: function () {
      this.loadStatistics();
      this.loadTable();
      this.bindEvents();
    },

    // Load statistics
    loadStatistics: function () {
      SIGEPAjax.get("api/statistics").done(function (response) {
        if (response.success) {
          $("#total-users").text(response.data.total_users);
        }
      });
    },

    // Load table data
    loadTable: function () {
      SIGEPAjax.get("api/records").done(function (response) {
        if (response.success) {
          SIGEPU.refreshTable("main-table", response.data);
        }
      });
    },

    // Bind events
    bindEvents: function () {
      // Form submission
      handleFormSubmit("add-form", function (response) {
        $("#add-modal").modal("hide");
        SIGEPU.resetForm("add-form");
        SIGEPModule.loadTable();
        SIGEPModule.loadStatistics();
      });
    },

    // Show add modal
    showAddModal: function () {
      $("#add-modal").modal("show");
    },

    // Edit record
    editRecord: function (id) {
      SIGEPAjax.get(`api/records/${id}`).done(function (response) {
        if (response.success) {
          // Populate form fields
          $("#nome").val(response.data.nome);
          $("#email").val(response.data.email);

          // Show modal
          $("#add-modal").modal("show");
        }
      });
    },

    // Delete record
    deleteRecord: function (id) {
      SIGEPU.showModal(
        "Confirmar Exclusão",
        "Tem certeza que deseja excluir este registro?",
        function () {
          SIGEPAjax.post(`api/records/${id}/delete`).done(function (response) {
            if (response.success) {
              SIGEPModule.loadTable();
              SIGEPModule.loadStatistics();
            }
          });
        },
      );
    },
  };

  // Auto-initialize when DOM is ready
  $(document).ready(function () {
    SIGEPModule.init();
  });
})(jQuery);
```

## Usage Examples

### Create a new page

```
@sigep-adminlte-ui create a new SIGEP page with:
- Title: "Gerenciamento de Internos"
- Statistics cards: Total internos, Ativos, Inativos, Novos este mês
- Data table with columns: ID, Nome, Prontuário, Status, Unidade, Ações
- Add/Edit/Delete functionality
- Responsive design for mobile
```

### Create a form

```
@sigep-adminlte-ui create a registration form with:
- Fields: Nome, Email, CPF, Telefone, Setor, Cargo
- Validation rules
- Save and Cancel buttons
- Modal presentation
```

### Create a dashboard

```
@sigep-adminlte-ui create a dashboard with:
- 4 statistics cards with different colors
- 2 charts (line and bar)
- Recent activities table
- Quick actions buttons
```

## Troubleshooting

### Common Issues

1. **CSS not loading** - Check AdminLTE CDN links
2. **JavaScript errors** - Verify jQuery is loaded first
3. **Responsive issues** - Test on different screen sizes
4. **Modal not showing** - Check for JavaScript conflicts
5. **Table not responsive** - Add table-responsive wrapper

### Debug Mode

```javascript
// Enable debug mode
window.SIGEP_DEBUG = true;

if (window.SIGEP_DEBUG) {
  console.log("SIGEP UI Debug Mode Enabled");
  // Add debug logging here
}
```

## Maintenance

### Regular Updates

- [ ] Update AdminLTE version when available
- [ ] Test new browser compatibility
- [ ] Optimize JavaScript performance
- [ ] Review accessibility compliance
- [ ] Update FontAwesome icons

### Performance Optimization

- [ ] Minimize CSS and JavaScript files
- [ ] Use CDN for external resources
- [ ] Implement lazy loading for images
- [ ] Optimize database queries
- [ ] Cache static assets

## Resources and References

### AdminLTE Documentation

- [AdminLTE 3 Documentation](https://adminlte.io/docs/3.2/)
- [Component Examples](https://adminlte.io/docs/3.2/components/)
- [Plugins Integration](https://adminlte.io/docs/3.2/plugins/)

### SIGEP Standards

- [UI Guidelines](../../../architecture/interface_ux.md)
- [CSS Standards](../../../assets/css/README.md)
- [JavaScript Patterns](../../../assets/js/README.md)

### External Resources

- [Bootstrap 4 Documentation](https://getbootstrap.com/docs/4.6/)
- [FontAwesome 6 Documentation](https://fontawesome.com/docs/)
- [jQuery Documentation](https://api.jquery.com/)
