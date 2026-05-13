<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>SIGEP | Master Dashboard Escoltas</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-beta2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --bs-body-bg: #f4f6f9;
        }

        .card-clickable {
            cursor: pointer;
            transition: all 0.3s;
            border: none;
        }

        .card-clickable:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1) !important;
            filter: brightness(1.1);
        }

        /* Cores AdminLTE 4 / Bootstrap 5 com melhor contraste */
        .bg-primary {
            background-color: #0d6efd !important;
            color: #fff !important;
        }

        .bg-info {
            background-color: #0dcaf0 !important;
            color: #000 !important;
        }

        .bg-warning {
            background-color: #ffc107 !important;
            color: #000 !important;
        }

        .bg-danger {
            background-color: #dc3545 !important;
            color: #fff !important;
        }

        .card-header {
            border-bottom: 1px solid rgba(0, 0, 0, .125);
            background-color: transparent;
        }

        .table thead th {
            background-color: #f8f9fa;
            color: #495057;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
        }

        /* CSS DAS PLACAS */
        .placa-base {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-weight: bold;
            font-size: 13px;
            text-transform: uppercase;
            border: 2px solid #333;
            line-height: 1.2;
            box-shadow: 1px 1px 2px rgba(0, 0, 0, 0.1);
        }

        .placa-mercosul {
            background: white;
            border-top: 6px solid #003399;
            color: #333;
            position: relative;
        }

        .placa-antiga {
            background: #c0c0c0;
            border: 2px solid #888;
            color: #333;
        }

        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            z-index: 2000;
            display: none;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(2px);
        }

        .btn-xs {
            padding: 0.125rem 0.25rem;
            font-size: 0.75rem;
        }

        canvas {
            cursor: pointer;
        }
    </style>

    <!-- CSS para Impressão Otimizada -->
    <style media="print">
        /* Esconder elementos desnecessários */
        .d-print-none,
        .loading-overlay,
        .card-clickable,
        .btn,
        .form-control,
        .form-select,
        .dataTables_wrapper .dataTables_paginate,
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter,
        .modal {
            display: none !important;
        }

        /* Resetar margens e padding */
        body {
            margin: 0;
            padding: 15px;
            background: white !important;
            font-size: 12px;
            line-height: 1.2;
        }

        /* Container para impressão */
        .container-fluid {
            max-width: 100%;
            padding: 0;
        }

        /* Cards em impressão */
        .card {
            border: 1px solid #000 !important;
            box-shadow: none !important;
            margin-bottom: 15px !important;
            page-break-inside: avoid;
        }

        .card-body {
            padding: 10px !important;
        }

        /* Títulos */
        h5,
        h6 {
            color: #000 !important;
            margin: 5px 0;
        }

        /* Tabelas otimizadas */
        .table {
            font-size: 10px !important;
            border: 1px solid #000 !important;
        }

        .table th,
        .table td {
            border: 1px solid #000 !important;
            padding: 4px 6px !important;
            color: #000 !important;
            background: white !important;
        }

        .table th {
            background: #f0f0f0 !important;
            font-weight: bold;
        }

        /* Badges em preto e branco */
        .badge {
            border: 1px solid #000 !important;
            background: white !important;
            color: #000 !important;
            padding: 2px 4px !important;
            font-size: 9px !important;
        }

        /* Gráficos - garantir que apareçam */
        canvas {
            max-width: 100% !important;
            height: auto !important;
            page-break-inside: avoid;
        }

        /* Layout em coluna para economizar espaço */
        .row {
            display: flex;
            flex-wrap: wrap;
            margin: 0 -5px;
        }

        .col-md-3 {
            width: 25%;
            margin-bottom: 15px;
            padding: 0 5px;
        }

        .col-md-6 {
            width: 50%;
            margin-bottom: 15px;
            padding: 0 5px;
        }

        /* Quebra de página */
        .card {
            page-break-inside: avoid;
        }

        /* Header e footer */
        @page {
            margin: 10mm;
            size: A4 landscape;
        }

        /* Tabelas otimizadas para paisagem */
        .table {
            font-size: 11px !important;
            border: 1px solid #000 !important;
            width: 100%;
        }

        .table th,
        .table td {
            border: 1px solid #000 !important;
            padding: 6px 8px !important;
            color: #000 !important;
            background: white !important;
            white-space: nowrap;
        }

        .table th {
            background: #f0f0f0 !important;
            font-weight: bold;
            font-size: 10px !important;
        }

        /* Ajustar largura das colunas da tabela */
        .table th:nth-child(1),
        /* Data */
        .table td:nth-child(1) {
            width: 80px;
            text-align: center;
        }

        .table th:nth-child(2),
        /* Interno */
        .table td:nth-child(2) {
            width: 200px;
        }

        .table th:nth-child(3),
        /* Destino */
        .table td:nth-child(3) {
            width: 150px;
        }

        .table th:nth-child(4),
        /* Motorista */
        .table td:nth-child(4) {
            width: 120px;
        }

        .table th:nth-child(5),
        /* Placa */
        .table td:nth-child(5) {
            width: 80px;
            text-align: center;
        }

        .table th:nth-child(6),
        /* Status */
        .table td:nth-child(6) {
            width: 80px;
            text-align: center;
        }

        .table th:nth-child(7),
        /* H. Prevista */
        .table td:nth-child(7),
        .table th:nth-child(8),
        /* H. Chegada */
        .table td:nth-child(8),
        .table th:nth-child(9),
        /* H. Retorno */
        .table td:nth-child(9) {
            width: 70px;
            text-align: center;
        }

        .table th:nth-child(10),
        /* NOT */
        .table td:nth-child(10) {
            width: 50px;
            text-align: center;
        }

        .table th:nth-child(11),
        /* Ações */
        .table td:nth-child(11) {
            width: 50px;
            text-align: center;
        }

        /* Título do relatório */
        .card:first-child .card-body::before {
            content: "Dashboard de Escoltas - Período: " attr(data-periodo);
            display: block;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 10px;
            text-align: center;
        }

        /* Tabela sem paginação */
        .dataTables_wrapper {
            display: block !important;
        }

        .dataTables_wrapper .dataTables_info {
            display: block !important;
            font-size: 10px;
            margin-top: 5px;
        }
    </style>
</head>

<body class="bg-light">

    <div class="loading-overlay" id="loader">
        <div class="spinner-border text-primary"></div>
    </div>

    <div class="container-fluid py-4" data-periodo="<?= date('d/m/Y', strtotime(date('Y-m-01'))) ?> a <?= date('d/m/Y') ?>">

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0"><i class="fas fa-filter me-2"></i>Filtros Avançados</h5>
                    <button type="button" onclick="window.print()" class="btn btn-info btn-sm d-print-none">
                        <i class="fas fa-print"></i> Imprimir
                    </button>
                </div>
                <form id="formFiltro" class="row g-2">
                    <div class="col-md-2">
                        <label class="small text-muted">Data Inicial</label>
                        <input type="date" id="f_ini" class="form-control form-control-sm" value="<?= date('Y-m-01') ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="small text-muted">Data Final</label>
                        <input type="date" id="f_fim" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="small text-muted">Status</label>
                        <select id="f_status" class="form-select form-select-sm">
                            <option value="">Todos</option>
                            <option value="Pendente">Pendente</option>
                            <option value="Em Curso">Em Curso</option>
                            <option value="Concluído">Concluído</option>
                            <option value="Cancelado">Cancelado</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="small text-muted">Termo de busca (Interno / Placa)</label>
                        <input type="text" id="f_busca" class="form-control form-control-sm" placeholder="Pesquisar...">
                        <input type="hidden" id="f_destino" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-2 d-flex align-items-end gap-1">
                        <button type="button" onclick="carregarDash()" class="btn btn-dark btn-sm flex-fill">Aplicar Filtros</button>
                        <button type="button" onclick="limparFiltros()" class="btn btn-outline-secondary btn-sm">Limpar</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card card-clickable shadow-sm bg-primary text-white" onclick="abrirModal('Total')">
                    <div class="card-body">
                        <h6>Total de Escoltas</h6>
                        <h2 id="st_total">0</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-clickable shadow-sm bg-info text-white" onclick="abrirModal('NOT')">
                    <div class="card-body">
                        <h6>Missões NOT</h6>
                        <h2 id="st_not">0</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-clickable shadow-sm bg-warning text-dark" onclick="abrirModal('Pendente')">
                    <div class="card-body">
                        <h6>Pendentes</h6>
                        <h2 id="st_pendente">0</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-clickable shadow-sm bg-danger text-white" onclick="abrirModal('Cancelado')">
                    <div class="card-body">
                        <h6>Canceladas</h6>
                        <h2 id="st_cancelado">0</h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white fw-bold">Distribuição por Status</div>
                    <div class="card-body">
                        <canvas id="chartStatus" style="max-height: 300px;"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white fw-bold">Top 5 Destinos</div>
                    <div class="card-body">
                        <canvas id="chartDestinos" style="max-height: 300px;"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white fw-bold">Registros Encontrados</div>
            <div class="card-body p-3">
                <table id="tabelaEscoltas" class="table table-hover align-middle w-100">
                    <thead>
                        <tr class="small text-muted text-uppercase">
                            <th>Data</th>
                            <th>Interno</th>
                            <th>Destino</th>
                            <th>Motorista</th>
                            <th>Placa</th>
                            <th>Status</th>
                            <th>H. Prevista</th>
                            <th>H. Chegada</th>
                            <th>H. Retorno</th>
                            <th>NOT</th>
                            <th class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalInfo" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold" id="modalTitulo">Detalhes</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="modalCorpo">
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        let dt;
        let chartStatus, chartDestinos;

        // Função para identificar o tipo de placa e retornar o HTML/CSS
        function formataPlaca(placa) {
            if (!placa) return '---';
            const p = placa.replace('-', '').toUpperCase();
            // Regex para Mercosul (ABC1D23)
            const mercosul = /^[A-Z]{3}[0-9][A-Z][0-9]{2}$/;

            if (mercosul.test(p)) {
                return `<span class="placa-base placa-mercosul">${p}</span>`;
            } else {
                return `<span class="placa-base placa-antiga">${p.slice(0,3)}-${p.slice(3)}</span>`;
            }
        }

        function renderCharts(data) {
            const statusCtx = document.getElementById('chartStatus').getContext('2d');
            const statusLabels = data.status.map(i => i.label);
            const statusValues = data.status.map(i => i.value);
            const statusColors = data.status.map(i => {
                const label = i.label.toUpperCase();
                if (label === 'CONCLUÍDO' || label === 'FINALIZADO') return '#28a745';
                if (label === 'EM CURSO' || label === 'EM ANDAMENTO') return '#007bff';
                if (label === 'PENDENTE') return '#ffc107';
                if (label.startsWith('CANCELAD')) return '#dc3545';
                return '#6c757d';
            });

            if (chartStatus) chartStatus.destroy();
            chartStatus = new Chart(statusCtx, {
                type: 'pie',
                data: {
                    labels: statusLabels,
                    datasets: [{
                        data: statusValues,
                        backgroundColor: statusColors
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    },
                    onClick: (evt, elements) => {
                        if (elements.length > 0) {
                            const index = elements[0].index;
                            const status = statusLabels[index];
                            $('#f_status').val(status);
                            carregarDash();
                        }
                    }
                }
            });

            // Gráfico de Destinos (Bar)
            const destinosCtx = document.getElementById('chartDestinos').getContext('2d');
            const destinosLabels = data.destinos.map(i => i.label);
            const destinosValues = data.destinos.map(i => i.value);

            if (chartDestinos) chartDestinos.destroy();
            chartDestinos = new Chart(destinosCtx, {
                type: 'bar',
                data: {
                    labels: destinosLabels,
                    datasets: [{
                        label: 'Escoltas',
                        data: destinosValues,
                        backgroundColor: '#0d6efd'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    },
                    onClick: (evt, elements) => {
                        if (elements.length > 0) {
                            const index = elements[0].index;
                            const destino = destinosLabels[index];
                            $('#f_destino').val(destino);
                            carregarDash();
                        }
                    }
                }
            });
        }

        async function carregarDash() {
            $('#loader').css('display', 'flex');

            const params = $.param({
                data_ini: $('#f_ini').val(),
                data_fim: $('#f_fim').val(),
                status: $('#f_status').val(),
                busca: $('#f_busca').val(),
                destino: $('#f_destino').val() // Adicionar campo de destino separado
            });

            try {
                const res = await fetch(`/modulos/eclusa/escolta/dashboard/api.php?${params}`);
                const data = await res.json();

                // Atualiza Stats
                $('#st_total').text(data.stats.total || 0);
                $('#st_not').text(data.stats.not_total || 0);
                $('#st_pendente').text(data.stats.pendentes || 0);
                $('#st_cancelado').text(data.stats.cancelados || 0);

                // Renderiza Gráficos
                renderCharts(data.charts);

                // Destruir tabela anterior se existir
                if (dt) dt.destroy();

                // Popular Tabela
                let rows = '';
                data.lista.forEach(i => {
                    const statusBadge = `<span class="badge text-bg-${getBadge(i.status)}">${i.status}</span>`;
                    const notBadge = i.eh_not === 'Sim' ? '<span class="badge bg-purple text-white">NOT</span>' : '---';

                    rows += `
                <tr class="small">
                    <td>${i.data_cadastro}</td>
                    <td><b>${i.interno}</b><br><small class="text-muted">${i.ala || ''} ${i.galeria || ''}</small></td>
                    <td>${i.destino}</td>
                    <td>${i.motorista || '---'}</td>
                    <td>${formataPlaca(i.placa)}</td>
                    <td>${statusBadge}</td>
                    <td class="text-center font-monospace">${i.hora_prevista || '---'}</td>
                    <td class="text-center font-monospace">${i.hora_chegada || '---'}</td>
                    <td class="text-center font-monospace">${i.hora_retorno || '---'}</td>
                    <td class="text-center">${notBadge}</td>
                    <td class="text-center">
                        <button class="btn btn-xs btn-outline-primary" onclick="abrirModalDetalhes(${i.id})" title="Ver Detalhes">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                </tr>`;
                });
                $('#tabelaEscoltas tbody').html(rows);

                // Inicializa DataTable (Paginação)
                dt = $('#tabelaEscoltas').DataTable({
                    language: {
                        url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json'
                    },
                    pageLength: 10,
                    order: [
                        [0, 'desc']
                    ]
                });

            } catch (e) {
                console.error(e);
            }
            $('#loader').hide();
        }

        function getBadge(s) {
            if (!s) return 'secondary';
            const status = s.toUpperCase();
            if (status === 'CONCLUÍDO' || status === 'FINALIZADO') return 'success';
            if (status === 'EM CURSO' || status === 'EM ANDAMENTO') return 'primary';
            if (status === 'PENDENTE') return 'warning';
            if (status.startsWith('CANCELAD')) return 'danger';
            return 'secondary';
        }

        async function abrirModalDetalhes(id) {
            $('#loader').css('display', 'flex');
            const myModalElement = document.getElementById('modalInfo');
            const myModal = new bootstrap.Modal(myModalElement);
            $('#modalTitulo').text(`Detalhes da Escolta #${id}`);

            try {
                const res = await fetch(`/modulos/eclusa/escolta/dashboard/api.php?id=${id}`);
                const data = await res.json();
                const i = data.item;

                if (!i) {
                    $('#modalCorpo').html('<div class="alert alert-danger">Escolta não encontrada.</div>');
                } else {
                    const html = `
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="small text-muted d-block">Interno</label>
                                <strong>${i.interno}</strong><br>
                                <small class="text-muted">${i.ala || ''} ${i.galeria || ''}</small>
                            </div>
                            <div class="col-md-6 text-end">
                                <label class="small text-muted d-block">Status</label>
                                <span class="badge text-bg-${getBadge(i.status)}">${i.status}</span>
                            </div>
                            <hr>
                            <div class="col-md-4">
                                <label class="small text-muted d-block">Data</label>
                                <strong>${i.data_cadastro}</strong>
                            </div>
                            <div class="col-md-4">
                                <label class="small text-muted d-block">Placa</label>
                                ${formataPlaca(i.placa)}
                            </div>
                            <div class="col-md-4">
                                <label class="small text-muted d-block">NOT</label>
                                <strong>${i.eh_not}</strong>
                            </div>
                            <div class="col-md-12">
                                <label class="small text-muted d-block">Destino</label>
                                <strong>${i.destino}</strong>
                            </div>
                            <div class="col-md-12">
                                <label class="small text-muted d-block">Motorista</label>
                                <strong>${i.motorista || '---'}</strong>
                            </div>
                            <div class="col-md-4">
                                <label class="small text-muted d-block">H. Prevista</label>
                                <strong>${i.hora_prevista || '---'}</strong>
                            </div>
                            <div class="col-md-4">
                                <label class="small text-muted d-block">H. Chegada</label>
                                <strong>${i.hora_chegada || '---'}</strong>
                            </div>
                            <div class="col-md-4">
                                <label class="small text-muted d-block">H. Retorno</label>
                                <strong>${i.hora_retorno || '---'}</strong>
                            </div>
                            <div class="col-md-12">
                                <label class="small text-muted d-block">Motivo / Observações</label>
                                <div class="p-2 bg-light border rounded mt-1">${i.motivo || 'Nenhuma observação registrada.'}</div>
                            </div>
                        </div>
                    `;
                    $('#modalCorpo').html(html);
                }
            } catch (e) {
                $('#modalCorpo').html('<div class="alert alert-danger">Erro ao carregar detalhes.</div>');
            }

            $('#loader').hide();
            myModal.show();
        }

        async function abrirModal(tipo) {
            $('#loader').css('display', 'flex');
            const myModalElement = document.getElementById('modalInfo');
            const myModal = new bootstrap.Modal(myModalElement);
            $('#modalTitulo').text(`Relatório de Escoltas: ${tipo}`);

            // Pega as datas atuais dos filtros da tela
            const params = new URLSearchParams({
                data_ini: $('#f_ini').val(),
                data_fim: $('#f_fim').val(),
                tipo_relatorio: tipo
            });

            console.log('Buscando drill-down:', params.toString());

            try {
                // Ajustando o caminho para ser relativo ao diretório atual ou absoluto do sistema
                const res = await fetch(`/modulos/eclusa/escolta/dashboard/api.php?${params.toString()}`);
                const data = await res.json();

                console.log('Resposta da API:', data);
                console.log('Lista encontrada:', data.lista);
                console.log('Quantidade:', data.lista ? data.lista.length : 0);

                let html = `
                    <div class="table-responsive">
                        <table class="table table-sm table-hover table-striped small">
                            <thead>
                                <tr class="bg-light">
                                    <th>Data</th>
                                    <th>Interno</th>
                                    <th>Destino</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                `;

                if (data.lista && data.lista.length > 0) {
                    data.lista.forEach(i => {
                        html += `
                            <tr>
                                <td>${i.data_cadastro}</td>
                                <td>${i.interno}</td>
                                <td>${i.destino}</td>
                                <td><span class="badge text-bg-${getBadge(i.status)}">${i.status}</span></td>
                            </tr>
                        `;
                    });
                } else {
                    html += `<tr><td colspan="4" class="text-center text-muted">Nenhum registro encontrado para ${tipo} entre ${$('#f_ini').val()} e ${$('#f_fim').val()}.</td></tr>`;
                }

                html += '</tbody></table></div>';
                $('#modalCorpo').html(html);
            } catch (e) {
                console.error('Erro no fetch do modal:', e);
                $('#modalCorpo').html('<div class="alert alert-danger">Erro ao carregar dados do relatório.</div>');
            }

            $('#loader').hide();
            myModal.show();
        }

        function limparFiltros() {
            // Limpa todos os campos do formulário
            $('#f_ini').val('<?= date('Y-m-01') ?>');
            $('#f_fim').val('<?= date('Y-m-d') ?>');
            $('#f_status').val('');
            $('#f_busca').val('');
            $('#f_destino').val(''); // Limpar campo de destino também

            // Recarrega os dados com os filtros limpos
            carregarDash();
        }

        $(document).ready(() => {
            carregarDash();
            setInterval(carregarDash, 30000);

            // Prevenir recarregamento ao pressionar Enter nos campos do formulário
            $('#formFiltro').on('submit', function(e) {
                e.preventDefault();
                carregarDash();
            });
        });
    </script>
</body>

</html>
