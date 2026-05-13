<?php
require_once __DIR__ . '/movimentacoes_logica.php';

// Obter estatísticas do dashboard
$stats = getDashboardStats($pdo);

// Obter dados para os gráficos
$movimentacoesPorData = getMovimentacoesPorData($pdo);
$movimentacoesPorHora = getMovimentacoesPorHora($pdo);
$movimentacoesPorTipo = getMovimentacoesPorTipo($pdo);
$movimentacoesPorDia = getMovimentacoesPorDia($pdo);
$topVeiculos = getTopVeiculos($pdo, 10);
$topEmpresas = getTopEmpresas($pdo, 10);
$movimentacoesPorMes = getMovimentacoesPorMes($pdo);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Dashboard Eclusa - SIGEP</title>

  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <!-- Ionicons -->
  <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
  <!-- Tempusdominus Bootstrap 4 -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tempusdominus-bootstrap-4/5.39.0/css/tempusdominus-bootstrap-4.min.css">
  <!-- iCheck -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/icheck-bootstrap/3.0.1/icheck-bootstrap.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/3.2.0/css/adminlte.min.css">
  <!-- overlayScrollbars -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/overlayscrollbars/1.13.0/css/OverlayScrollbars.min.css">
  <!-- Daterange picker -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-daterangepicker/3.0.5/daterangepicker.css">
  <!-- summernote -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.18/summernote-bs4.min.css">

  <!-- Custom CSS -->
  <link rel="stylesheet" href="assets/css/eclusa_dashboard.css">
</head>
<body class="hold-transition layout-top-nav">
<div class="wrapper">

  <!-- Preloader -->
  <div class="preloader flex-column justify-content-center align-items-center">
    <img class="animation__shake" src="assets/img/AdminLTELogo.png" alt="AdminLTELogo" height="60" width="60">
  </div>

  <!-- Navbar -->
  <nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
      <li class="nav-item">
        <a href="/dashboard/eclusa" class="nav-link">Dashboard Eclusa</a>
      </li>
    </ul>

    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">
      <li class="nav-item">
        <a class="nav-link" data-widget="fullscreen" href="#" role="button">
          <i class="fas fa-expand-arrows-alt"></i>
        </a>
      </li>
    </ul>
  </nav>
  <!-- /.navbar -->

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Dashboard Eclusa</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">SIGEP</a></li>
              <li class="breadcrumb-item active">Dashboard Eclusa</li>
            </ol>
          </div><!-- /.col -->
        </div><!-- /.row -->
      </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <!-- Info boxes -->
        <div class="row">
          <div class="col-12 col-sm-6 col-md-3">
            <div class="info-box">
              <span class="info-box-icon bg-info elevation-1"><i class="fas fa-truck"></i></span>

              <div class="info-box-content">
                <span class="info-box-text">Total Movimentações</span>
                <span class="info-box-number">
                  <?= number_format($stats['total']) ?>
                </span>
              </div>
              <!-- /.info-box-content -->
            </div>
            <!-- /.info-box -->
          </div>
          <!-- /.col -->
          <div class="col-12 col-sm-6 col-md-3">
            <div class="info-box mb-3">
              <span class="info-box-icon bg-success elevation-1"><i class="fas fa-calendar-day"></i></span>

              <div class="info-box-content">
                <span class="info-box-text">Hoje</span>
                <span class="info-box-number"><?= number_format($stats['hoje']) ?></span>
              </div>
              <!-- /.info-box-content -->
            </div>
            <!-- /.info-box -->
          </div>
          <!-- /.col -->

          <!-- fix for small devices only -->
          <div class="clearfix hidden-md-up"></div>

          <div class="col-12 col-sm-6 col-md-3">
            <div class="info-box mb-3">
              <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-sign-in-alt"></i></span>

              <div class="info-box-content">
                <span class="info-box-text">Entradas Hoje</span>
                <span class="info-box-number"><?= number_format($stats['entradas']) ?></span>
              </div>
              <!-- /.info-box-content -->
            </div>
            <!-- /.info-box -->
          </div>
          <!-- /.col -->
          <div class="col-12 col-sm-6 col-md-3">
            <div class="info-box mb-3">
              <span class="info-box-icon bg-danger elevation-1"><i class="fas fa-sign-out-alt"></i></span>

              <div class="info-box-content">
                <span class="info-box-text">Saídas Hoje</span>
                <span class="info-box-number"><?= number_format($stats['saidas']) ?></span>
              </div>
              <!-- /.info-box-content -->
            </div>
            <!-- /.info-box -->
          </div>
          <!-- /.col -->
        </div>
        <!-- /.row -->

        <!-- Main row -->
        <div class="row">
          <!-- Left col -->
          <div class="col-md-8">
            <!-- Movimentações por Data -->
            <div class="card">
              <div class="card-header">
                <h3 class="card-title">Movimentações por Data</h3>

                <div class="card-tools">
                  <div class="btn-group mr-2">
                    <select class="form-control form-control-sm" id="periodoDataChart" style="width: auto;">
                      <option value="7">Últimos 7 dias</option>
                      <option value="30" selected>Últimos 30 dias</option>
                      <option value="90">Últimos 3 meses</option>
                      <option value="180">Últimos 6 meses</option>
                      <option value="365">Último ano</option>
                      <option value="0">Todo período</option>
                    </select>
                  </div>
                  <button type="button" class="btn btn-tool" data-card-widget="collapse">
                    <i class="fas fa-minus"></i>
                  </button>
                  <button type="button" class="btn btn-tool" data-card-widget="remove">
                    <i class="fas fa-times"></i>
                  </button>
                </div>
              </div>
              <div class="card-body">
                <div class="chart">
                  <canvas id="movimentacoesPorDataChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                </div>
              </div>
              <!-- /.card-body -->
            </div>
            <!-- /.card -->

            <!-- Movimentações por Hora -->
            <div class="card">
              <div class="card-header">
                <h3 class="card-title">Movimentações por Hora do Dia</h3>

                <div class="card-tools">
                  <button type="button" class="btn btn-tool" data-card-widget="collapse">
                    <i class="fas fa-minus"></i>
                  </button>
                  <button type="button" class="btn btn-tool" data-card-widget="remove">
                    <i class="fas fa-times"></i>
                  </button>
                </div>
              </div>
              <div class="card-body">
                <div class="chart">
                  <canvas id="movimentacoesPorHoraChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                </div>
              </div>
              <!-- /.card-body -->
            </div>
            <!-- /.card -->
          </div>
          <!-- /.col -->

          <!-- Right col -->
          <div class="col-md-4">
            <!-- Movimentações por Tipo -->
            <div class="card">
              <div class="card-header">
                <h3 class="card-title">Movimentações por Tipo</h3>

                <div class="card-tools">
                  <button type="button" class="btn btn-tool" data-card-widget="collapse">
                    <i class="fas fa-minus"></i>
                  </button>
                  <button type="button" class="btn btn-tool" data-card-widget="remove">
                    <i class="fas fa-times"></i>
                  </button>
                </div>
              </div>
              <div class="card-body">
                <canvas id="movimentacoesPorTipoChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
              </div>
              <!-- /.card-body -->
            </div>
            <!-- /.card -->

            <!-- Movimentações por Dia da Semana -->
            <div class="card">
              <div class="card-header">
                <h3 class="card-title">Movimentações por Dia da Semana</h3>

                <div class="card-tools">
                  <button type="button" class="btn btn-tool" data-card-widget="collapse">
                    <i class="fas fa-minus"></i>
                  </button>
                  <button type="button" class="btn btn-tool" data-card-widget="remove">
                    <i class="fas fa-times"></i>
                  </button>
                </div>
              </div>
              <div class="card-body">
                <canvas id="movimentacoesPorDiaChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
              </div>
              <!-- /.card-body -->
            </div>
            <!-- /.card -->
          </div>
          <!-- /.col -->
        </div>
        <!-- /.row -->

        <!-- Second row -->
        <div class="row">
          <div class="col-md-6">
            <!-- Top Veículos -->
            <div class="card">
              <div class="card-header">
                <h3 class="card-title">Top 10 Veículos</h3>

                <div class="card-tools">
                  <button type="button" class="btn btn-tool" data-card-widget="collapse">
                    <i class="fas fa-minus"></i>
                  </button>
                  <button type="button" class="btn btn-tool" data-card-widget="remove">
                    <i class="fas fa-times"></i>
                  </button>
                </div>
              </div>
              <div class="card-body">
                <div class="chart">
                  <canvas id="topVeiculosChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                </div>
              </div>
              <!-- /.card-body -->
            </div>
            <!-- /.card -->
          </div>

          <div class="col-md-6">
            <!-- Top Empresas -->
            <div class="card">
              <div class="card-header">
                <h3 class="card-title">Top 10 Empresas</h3>

                <div class="card-tools">
                  <button type="button" class="btn btn-tool" data-card-widget="collapse">
                    <i class="fas fa-minus"></i>
                  </button>
                  <button type="button" class="btn btn-tool" data-card-widget="remove">
                    <i class="fas fa-times"></i>
                  </button>
                </div>
              </div>
              <div class="card-body">
                <div class="chart">
                  <canvas id="topEmpresasChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                </div>
              </div>
              <!-- /.card-body -->
            </div>
            <!-- /.card -->
          </div>
        </div>
        <!-- /.row -->

        <!-- Third row -->
        <div class="row">
          <div class="col-md-12">
            <!-- Movimentações por Mês -->
            <div class="card">
              <div class="card-header">
                <h3 class="card-title">Movimentações por Mês (Últimos 12 meses)</h3>

                <div class="card-tools">
                  <button type="button" class="btn btn-tool" data-card-widget="collapse">
                    <i class="fas fa-minus"></i>
                  </button>
                  <button type="button" class="btn btn-tool" data-card-widget="remove">
                    <i class="fas fa-times"></i>
                  </button>
                </div>
              </div>
              <div class="card-body">
                <div class="chart">
                  <canvas id="movimentacoesPorMesChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                </div>
              </div>
              <!-- /.card-body -->
            </div>
            <!-- /.card -->
          </div>
        </div>
        <!-- /.row -->
      </div><!--/. container-fluid -->
    </section>
    <!-- /.content -->
  </div>
  <!-- /.content-wrapper -->

  <!-- Control Sidebar -->
  <aside class="control-sidebar control-sidebar-dark">
    <!-- Control sidebar content goes here -->
  </aside>
  <!-- /.control-sidebar -->

<!-- REQUIRED SCRIPTS -->
<!-- jQuery -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.2/js/bootstrap.bundle.min.js"></script>
<!-- ChartJS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
<!-- AdminLTE App -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/3.2.0/js/adminlte.min.js"></script>

<!-- Custom JavaScript -->
<script>
  // Dados dos gráficos vindos do PHP
  var movimentacoesPorData = <?= json_encode($movimentacoesPorData) ?>;
  var movimentacoesPorHora = <?= json_encode($movimentacoesPorHora) ?>;
  var movimentacoesPorTipo = <?= json_encode($movimentacoesPorTipo) ?>;
  var movimentacoesPorDia = <?= json_encode($movimentacoesPorDia) ?>;
  var topVeiculos = <?= json_encode($topVeiculos) ?>;
  var topEmpresas = <?= json_encode($topEmpresas) ?>;
  var movimentacoesPorMes = <?= json_encode($movimentacoesPorMes) ?>;

  $(document).ready(function() {
    var movimentacoesPorDataChart; // Declarar variável global

    // Função para atualizar gráfico de movimentações por data
    function atualizarGraficoPorData(periodo) {
      $.post('/modulos/eclusa/movimentacoes/movimentacoes_logica.php', {
        action: 'get_chart_data',
        chart_type: 'data',
        periodo: periodo
      }, function(data) {
        // Atualizar dados do gráfico
        movimentacoesPorDataChart.data.labels = data.map(item => {
          var date = new Date(item.data);
          return date.toLocaleDateString('pt-BR');
        });
        movimentacoesPorDataChart.data.datasets[0].data = data.map(item => item.total);
        movimentacoesPorDataChart.data.datasets[1].data = data.map(item => item.entradas);
        movimentacoesPorDataChart.data.datasets[2].data = data.map(item => item.saidas);
        movimentacoesPorDataChart.update();

        // Atualizar variável global com novos dados
        window.movimentacoesPorData = data;
      });
    }

    // Event listener para mudança de período
    $('#periodoDataChart').on('change', function() {
      var periodo = $(this).val();
      atualizarGraficoPorData(periodo);
    });

    // Gráfico Movimentações por Data
    var ctx1 = document.getElementById('movimentacoesPorDataChart').getContext('2d');
    movimentacoesPorDataChart = new Chart(ctx1, {
      type: 'line',
      data: {
        labels: movimentacoesPorData.map(item => {
          var date = new Date(item.data);
          return date.toLocaleDateString('pt-BR');
        }),
        datasets: [{
          label: 'Total',
          data: movimentacoesPorData.map(item => item.total),
          borderColor: 'rgb(75, 192, 192)',
          backgroundColor: 'rgba(75, 192, 192, 0.2)',
          tension: 0.1
        }, {
          label: 'Entradas',
          data: movimentacoesPorData.map(item => item.entradas),
          borderColor: 'rgb(54, 162, 235)',
          backgroundColor: 'rgba(54, 162, 235, 0.2)',
          tension: 0.1
        }, {
          label: 'Saídas',
          data: movimentacoesPorData.map(item => item.saidas),
          borderColor: 'rgb(255, 99, 132)',
          backgroundColor: 'rgba(255, 99, 132, 0.2)',
          tension: 0.1
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        onClick: (event, elements) => {
          if (elements.length > 0) {
            const index = elements[0].index;
            const dataPoint = movimentacoesPorData[index];
            if (dataPoint) {
              mostrarDetalhesMovimentacoesPorData(dataPoint.data);
            }
          }
        },
        plugins: {
          legend: {
            position: 'top',
          },
          title: {
            display: true,
            text: 'Movimentações Diárias'
          }
        }
      }
    });

    // Gráfico Movimentações por Tipo
    var ctx2 = document.getElementById('movimentacoesPorTipoChart').getContext('2d');
    var movimentacoesPorTipoChart = new Chart(ctx2, {
      type: 'doughnut',
      data: {
        labels: movimentacoesPorTipo.map(item => {
          switch(item.tipo) {
            case 'entrada_saida': return 'Entrada/Saída';
            case 'entrada': return 'Entrada';
            case 'saida': return 'Saída';
            default: return 'Indefinido';
          }
        }),
        datasets: [{
          data: movimentacoesPorTipo.map(item => item.quantidade),
          backgroundColor: [
            'rgb(255, 99, 132)',
            'rgb(54, 162, 235)',
            'rgb(255, 205, 86)',
            'rgb(75, 192, 192)'
          ],
          hoverOffset: 4
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        onClick: (event, elements) => {
          if (elements.length > 0) {
            const index = elements[0].index;
            const dataPoint = movimentacoesPorTipo[index];
            if (dataPoint) {
              mostrarDetalhesMovimentacoesPorTipo(dataPoint.tipo);
            }
          }
        },
        plugins: {
          legend: {
            position: 'bottom',
          }
        }
      }
    });

    // Gráfico Top Veículos
    var ctx3 = document.getElementById('topVeiculosChart').getContext('2d');
    var topVeiculosChart = new Chart(ctx3, {
      type: 'bar',
      data: {
        labels: topVeiculos.map(item => item.veiculo.substring(0, 20) + (item.veiculo.length > 20 ? '...' : '')),
        datasets: [{
          label: 'Movimentações',
          data: topVeiculos.map(item => item.total_movimentacoes),
          backgroundColor: 'rgba(54, 162, 235, 0.8)',
          borderColor: 'rgb(54, 162, 235)',
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        onClick: (event, elements) => {
          if (elements.length > 0) {
            const index = elements[0].index;
            const dataPoint = topVeiculos[index];
            if (dataPoint) {
              mostrarDetalhesMovimentacoesPorVeiculo(dataPoint.veiculo);
            }
          }
        },
        plugins: {
          legend: {
            display: false
          }
        },
        scales: {
          y: {
            beginAtZero: true
          }
        }
      }
    });

    // Gráfico Top Empresas
    var ctx4 = document.getElementById('topEmpresasChart').getContext('2d');
    var topEmpresasChart = new Chart(ctx4, {
      type: 'bar',
      data: {
        labels: topEmpresas.map(item => item.empresa.substring(0, 20) + (item.empresa.length > 20 ? '...' : '')),
        datasets: [{
          label: 'Movimentações',
          data: topEmpresas.map(item => item.total_movimentacoes),
          backgroundColor: 'rgba(255, 99, 132, 0.8)',
          borderColor: 'rgb(255, 99, 132)',
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        onClick: (event, elements) => {
          if (elements.length > 0) {
            const index = elements[0].index;
            const dataPoint = topEmpresas[index];
            if (dataPoint) {
              mostrarDetalhesMovimentacoesPorEmpresa(dataPoint.empresa);
            }
          }
        },
        plugins: {
          legend: {
            display: false
          }
        },
        scales: {
          y: {
            beginAtZero: true
          }
        }
      }
    });

    // Gráfico Movimentações por Hora
    var ctx5 = document.getElementById('movimentacoesPorHoraChart').getContext('2d');
    var movimentacoesPorHoraChart = new Chart(ctx5, {
      type: 'bar',
      data: {
        labels: Array.from({length: 24}, (_, i) => i + ':00'),
        datasets: [{
          label: 'Movimentações',
          data: Array.from({length: 24}, (_, i) => {
            var found = movimentacoesPorHora.find(item => item.hora == i);
            return found ? found.quantidade : 0;
          }),
          backgroundColor: 'rgba(75, 192, 192, 0.8)',
          borderColor: 'rgb(75, 192, 192)',
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        onClick: (event, elements) => {
          if (elements.length > 0) {
            const index = elements[0].index;
            mostrarDetalhesMovimentacoesPorHora(index);
          }
        },
        plugins: {
          legend: {
            display: false
          }
        },
        scales: {
          y: {
            beginAtZero: true
          }
        }
      }
    });

    // Gráfico Movimentações por Dia da Semana
    var ctx6 = document.getElementById('movimentacoesPorDiaChart').getContext('2d');
    var movimentacoesPorDiaChart = new Chart(ctx6, {
      type: 'bar',
      data: {
        labels: movimentacoesPorDia.map(item => item.dia_semana),
        datasets: [{
          label: 'Movimentações',
          data: movimentacoesPorDia.map(item => item.quantidade),
          backgroundColor: 'rgba(255, 99, 132, 0.8)',
          borderColor: 'rgb(255, 99, 132)',
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        onClick: (event, elements) => {
          if (elements.length > 0) {
            const index = elements[0].index;
            const dataPoint = movimentacoesPorDia[index];
            if (dataPoint) {
              mostrarDetalhesMovimentacoesPorDiaSemana(dataPoint.dia_semana);
            }
          }
        },
        plugins: {
          legend: {
            display: false
          }
        },
        scales: {
          y: {
            beginAtZero: true
          }
        }
      }
    });

    // Gráfico Movimentações por Mês
    var ctx7 = document.getElementById('movimentacoesPorMesChart').getContext('2d');
    var movimentacoesPorMesChart = new Chart(ctx7, {
      type: 'line',
      data: {
        labels: movimentacoesPorMes.map(item => item.mes_formatado),
        datasets: [{
          label: 'Movimentações',
          data: movimentacoesPorMes.map(item => item.quantidade),
          borderColor: 'rgb(153, 102, 255)',
          backgroundColor: 'rgba(153, 102, 255, 0.2)',
          tension: 0.1
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        onClick: (event, elements) => {
          if (elements.length > 0) {
            const index = elements[0].index;
            const dataPoint = movimentacoesPorMes[index];
            if (dataPoint) {
              mostrarDetalhesMovimentacoesPorMes(dataPoint.mes_formatado);
            }
          }
        },
        plugins: {
          legend: {
            position: 'top',
          }
        },
        scales: {
          y: {
            beginAtZero: true
          }
        }
      }
    });
  });

  // Função para mostrar detalhes das movimentações por data
  function mostrarDetalhesMovimentacoesPorData(data) {
    $.post('/modulos/eclusa/movimentacoes/movimentacoes_logica.php', {
      action: 'get_movimentacoes_detalhadas',
      data: data
    }, function(movimentacoes) {
      // Verificação de segurança robusta
      if (!movimentacoes || typeof movimentacoes !== 'object' || !Array.isArray(movimentacoes)) {
        console.warn('Dados inválidos recebidos:', movimentacoes);
        alert('Nenhuma movimentação encontrada para esta data.');
        return;
      }

      if (movimentacoes.length === 0) {
        alert('Nenhuma movimentação encontrada para esta data.');
        return;
      }

      criarModalDetalhes(`Movimentações do dia ${new Date(data).toLocaleDateString('pt-BR')}`, movimentacoes);
    });
  }

  // Funções para outros tipos de detalhes
  function mostrarDetalhesMovimentacoesPorTipo(tipo) {
    $.post('/modulos/eclusa/movimentacoes/movimentacoes_logica.php', {
      action: 'get_movimentacoes_por_tipo',
      tipo: tipo
    }, function(movimentacoes) {
      if (!movimentacoes || typeof movimentacoes !== 'object' || !Array.isArray(movimentacoes) || movimentacoes.length === 0) {
        alert('Nenhuma movimentação encontrada para este tipo.');
        return;
      }
      criarModalDetalhes(`Movimentações - Tipo: ${tipo}`, movimentacoes);
    });
  }

  function mostrarDetalhesMovimentacoesPorVeiculo(veiculo) {
    $.post('/modulos/eclusa/movimentacoes/movimentacoes_logica.php', {
      action: 'get_movimentacoes_por_veiculo',
      veiculo: veiculo
    }, function(movimentacoes) {
      if (!movimentacoes || typeof movimentacoes !== 'object' || !Array.isArray(movimentacoes) || movimentacoes.length === 0) {
        alert('Nenhuma movimentação encontrada para este veículo.');
        return;
      }
      criarModalDetalhes(`Movimentações - Veículo: ${veiculo}`, movimentacoes);
    });
  }

  function mostrarDetalhesMovimentacoesPorEmpresa(empresa) {
    $.post('/modulos/eclusa/movimentacoes/movimentacoes_logica.php', {
      action: 'get_movimentacoes_por_empresa',
      empresa: empresa
    }, function(movimentacoes) {
      if (!movimentacoes || typeof movimentacoes !== 'object' || !Array.isArray(movimentacoes) || movimentacoes.length === 0) {
        alert('Nenhuma movimentação encontrada para esta empresa.');
        return;
      }
      criarModalDetalhes(`Movimentações - Empresa: ${empresa}`, movimentacoes);
    });
  }

  function mostrarDetalhesMovimentacoesPorHora(hora) {
    $.post('/modulos/eclusa/movimentacoes/movimentacoes_logica.php', {
      action: 'get_movimentacoes_por_hora',
      hora: hora
    }, function(movimentacoes) {
      if (!movimentacoes || typeof movimentacoes !== 'object' || !Array.isArray(movimentacoes) || movimentacoes.length === 0) {
        alert('Nenhuma movimentação encontrada para esta hora.');
        return;
      }
      criarModalDetalhes(`Movimentações - ${hora}:00h`, movimentacoes);
    });
  }

  function mostrarDetalhesMovimentacoesPorDiaSemana(dia) {
    $.post('/modulos/eclusa/movimentacoes/movimentacoes_logica.php', {
      action: 'get_movimentacoes_por_dia_semana',
      dia: dia
    }, function(movimentacoes) {
      if (!movimentacoes || typeof movimentacoes !== 'object' || !Array.isArray(movimentacoes) || movimentacoes.length === 0) {
        alert('Nenhuma movimentação encontrada para este dia da semana.');
        return;
      }
      criarModalDetalhes(`Movimentações - ${dia}`, movimentacoes);
    });
  }

  function mostrarDetalhesMovimentacoesPorMes(mes) {
    $.post('/modulos/eclusa/movimentacoes/movimentacoes_logica.php', {
      action: 'get_movimentacoes_por_mes',
      mes: mes
    }, function(movimentacoes) {
      if (!movimentacoes || typeof movimentacoes !== 'object' || !Array.isArray(movimentacoes) || movimentacoes.length === 0) {
        alert('Nenhuma movimentação encontrada para este mês.');
        return;
      }
      criarModalDetalhes(`Movimentações - ${mes}`, movimentacoes);
    });
  }

  // Função genérica para criar modal de detalhes
  function criarModalDetalhes(titulo, movimentacoes) {
    // Criar HTML da tabela
    let html = `
      <div class="table-responsive">
        <table class="table table-striped table-hover">
          <thead>
            <tr>
              <th>Data</th>
              <th>Hora Chegada</th>
              <th>Hora Entrada</th>
              <th>Hora Saída</th>
              <th>Placa</th>
              <th>Veículo</th>
              <th>Empresa</th>
              <th>Motorista</th>
              <th>Tipo</th>
              <th>Observações</th>
              <th>Cadastrado Por</th>
            </tr>
          </thead>
          <tbody>
    `;

    movimentacoes.forEach(function(mov) {
      const data = new Date(mov.data_movimentacao).toLocaleDateString('pt-BR');
      html += `
        <tr>
          <td>${data}</td>
          <td>${mov.hora_chegada || '-'}</td>
          <td>${mov.hora_entrada || '-'}</td>
          <td>${mov.hora_saida || '-'}</td>
          <td>${mov.placa || '-'}</td>
          <td>${mov.veiculo_nome || '-'}</td>
          <td>${mov.empresa_nome || '-'}</td>
          <td>${mov.motorista_nome || '-'}</td>
          <td><span class="badge badge-${mov.tipo_movimento === 'Entrada' ? 'success' : mov.tipo_movimento === 'Saída' ? 'danger' : 'info'}">${mov.tipo_movimento}</span></td>
          <td>${mov.observacoes || '-'}</td>
          <td>${mov.cadastrado_por || '-'}</td>
        </tr>
      `;
    });

    html += `
          </tbody>
        </table>
      </div>
    `;

    // Criar e mostrar modal
    const modalHtml = `
      <div class="modal fade" id="modalDetalhesMovimentacoes" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-xl" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">${titulo}</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span>&times;</span>
              </button>
            </div>
            <div class="modal-body">
              ${html}
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
            </div>
          </div>
        </div>
      </div>
    `;

    // Remover modal anterior se existir
    $('#modalDetalhesMovimentacoes').remove();

    // Adicionar novo modal ao body
    $('body').append(modalHtml);

    // Mostrar modal
    $('#modalDetalhesMovimentacoes').modal('show');
  }
</script>
<!-- Tabela Completa de Movimentações -->
    <div class="row mt-4">
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">
              <i class="fas fa-list mr-2"></i>
              Todas as Movimentações
              <small class="text-muted">(Ordenado da mais recente para mais antiga)</small>
            </h3>
            <div class="card-tools">
              <div class="btn-group mr-2">
                <select class="form-control form-control-sm" id="periodoTabela" style="width: auto;">
                  <option value="30">Últimos 30 dias</option>
                  <option value="60">Últimos 60 dias</option>
                  <option value="90">Últimos 3 meses</option>
                  <option value="180">Últimos 6 meses</option>
                  <option value="365">Último ano</option>
                  <option value="0">Todo período</option>
                </select>
              </div>
              <div class="btn-group">
                <button class="btn btn-sm btn-default" type="button" id="btnImprimirTabela" title="Imprimir tabela">
                  <i class="fas fa-print"></i>
                  <span class="d-none d-sm-inline ml-1">Imprimir</span>
                </button>
              </div>
            </div>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-striped table-hover table-sm" id="tabelaMovimentacoesGeral">
                <thead>
                  <tr>
                    <th>Data</th>
                    <th>Hora Chegada</th>
                    <th>Hora Entrada</th>
                    <th>Hora Saída</th>
                    <th>Placa</th>
                    <th>Veículo</th>
                    <th>Empresa</th>
                    <th>Motorista</th>
                    <th>Tipo</th>
                    <th>Observações</th>
                    <th>Cadastrado Por</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td colspan="11" class="text-center py-4">
                      <i class="fas fa-spinner fa-spin mr-2"></i>
                      Carregando movimentações...
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-3">
              <div class="text-muted">
                <span id="infoPaginacaoMovimentacoes"></span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

<!-- JavaScript para Tabela Completa de Movimentações -->
<script src="/assets/js/tabela_movimentacoes.js?v=<?php echo time(); ?>"></script>
  <!-- Main Footer -->
  <footer class="main-footer">
    <strong>Copyright &copy; 2026 <a href="#">SIGEP</a>.</strong>
    Todos os direitos reservados.
    <div class="float-right d-none d-sm-inline-block">
      <b>Versão</b> 1.0.0
    </div>
  </footer>
</div>
<!-- ./wrapper -->
</body>
</html>
