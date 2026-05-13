// SIGEP Job Manager - JavaScript
// Gerenciador de Tarefas e Serviços

$(document).ready(function() {
    carregarJobs();
    atualizarCardsResumo();

    // Auto-refresh a cada 30 segundos
    setInterval(function() {
        if (!$('.modal.show').length) { // Não atualizar se modal estiver aberto
            carregarJobs();
            atualizarCardsResumo();
        }
    }, 30000);
});

// Função principal para carregar jobs
function carregarJobs() {
    $.ajax({
        url: '/modulos/servicos/job_manager/job_manager_logica.php',
        type: 'POST',
        data: { action: 'listar_jobs' },
        dataType: 'json',
        success: function(response) {
            if (response.error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: 'Erro ao carregar jobs: ' + response.error
                });
                return;
            }

            renderizarTabelaJobs(response.jobs);
            atualizarCardsResumo();
        },
        error: function(xhr, status, error) {
            console.error('Erro AJAX:', error);
            Swal.fire({
                icon: 'error',
                title: 'Erro',
                text: 'Erro de comunicação com o servidor'
            });
        }
    });
}

// Renderizar tabela de jobs
function renderizarTabelaJobs(jobs) {
    const tbody = $('#corpo-tabela-jobs');
    tbody.empty();

    if (jobs.length === 0) {
        tbody.html('<tr><td colspan="7" class="text-center">Nenhum job cadastrado</td></tr>');
        return;
    }

    jobs.forEach(job => {
        const row = `
            <tr>
                <td>
                    <div class="d-flex align-items-center">
                        <i class="fas fa-${getIconeTipo(job.tipo)} type-icon type-${job.tipo}"></i>
                        <div>
                            <strong>${job.nome}</strong><br>
                            <small class="text-muted">${job.descricao || 'Sem descrição'}</small>
                        </div>
                    </div>
                </td>
                <td>
                    <span class="badge badge-secondary">${job.tipo}</span>
                </td>
                <td>
                    <span class="status-pill status-${job.status}">${job.status}</span>
                </td>
                <td>
                    <small>${job.proximo_execucao_formatada}</small>
                </td>
                <td>
                    <small>${job.ultima_execucao_formatada}</small>
                </td>
                <td>
                    <span class="priority-badge priority-${getPriorityClass(job.priority)}">${job.priority}</span>
                </td>
                <td>
                    <div class="btn-group" role="group">
                        <button class="btn btn-sm btn-action btn-executar" onclick="executarJob(${job.id})" title="Executar Agora">
                            <i class="fas fa-play"></i>
                        </button>
                        <button class="btn btn-sm btn-action ${job.status === 'pausado' ? 'btn-ativar' : 'btn-pausar'}"
                                onclick="${job.status === 'pausado' ? 'ativarJob' : 'pausarJob'}(${job.id})"
                                title="${job.status === 'pausado' ? 'Ativar' : 'Pausar'}">
                            <i class="fas fa-${job.status === 'pausado' ? 'play' : 'pause'}"></i>
                        </button>
                        <button class="btn btn-sm btn-action btn-editar" onclick="editarJob(${job.id})" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-action" onclick="verExecucoes(${job.id})" title="Ver Execuções">
                            <i class="fas fa-history"></i>
                        </button>
                        <button class="btn btn-sm btn-action btn-excluir" onclick="excluirJob(${job.id})" title="Excluir">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
        tbody.append(row);
    });
}

// Atualizar cards de resumo
function atualizarCardsResumo() {
    $.ajax({
        url: '/modulos/servicos/job_manager/job_manager_logica.php',
        type: 'POST',
        data: { action: 'listar_jobs' },
        dataType: 'json',
        success: function(response) {
            const jobs = response.jobs || [];

            $('#jobs-total').text(jobs.length);
            $('#jobs-ativos').text(jobs.filter(j => j.status === 'ativo').length);
            $('#jobs-executando').text(jobs.filter(j => j.status === 'executando').length);
            $('#jobs-erro').text(jobs.filter(j => j.status === 'erro').length);
        }
    });
}

// Abrir modal para novo job
function abrirModalJob() {
    $('#job-id').val('');
    $('#form-job')[0].reset();
    $('#titulo-modal-job').text('Novo Job');
    $('#modalJob').modal('show');
}

// Editar job
function editarJob(id) {
    // Buscar dados do job
    $.ajax({
        url: '/modulos/servicos/job_manager/job_manager_logica.php',
        type: 'POST',
        data: { action: 'listar_jobs' },
        dataType: 'json',
        success: function(response) {
            const job = response.jobs.find(j => j.id == id);
            if (!job) {
                mostrarAlerta('Job não encontrado', 'danger');
                return;
            }

            // Preencher formulário
            $('#job-id').val(job.id);
            $('#job-nome').val(job.nome);
            $('#job-descricao').val(job.descricao || '');
            $('#job-tipo').val(job.tipo);
            $('#job-comando').val(job.comando);
            $('#job-diretorio').val(job.diretorio_trabalho || '');
            $('#job-executar-como').val(job.executar_como || 'SYSTEM');
            $('#job-agendamento-tipo').val(job.agendamento_tipo || 'unico');

            const config = job.agendamento_config || {};
            $('#job-intervalo-valor').val(config.intervalo_valor || 1);
            $('#job-compactar').prop('checked', config.compactar == 1);

            // Retenção
            const retencao = config.retencao || { habilitado: 0, valor: 7, unidade: 'dias' };
            $('#job-versionamento').prop('checked', retencao.habilitado == 1);
            $('#job-retencao-valor').val(retencao.valor || 7);
            $('#job-retencao-unidade').val(retencao.unidade || 'dias');

            toggleCamposAgendamento();
            toggleCamposVersionamento();

            $('#job-proxima-execucao').val(job.proximo_execucao ? job.proximo_execucao.replace(' ', 'T') : '');
            $('#job-priority').val(job.priority || 5);
            $('#job-timeout').val(job.timeout || 3600);
            $('#job-email').val(job.email_notificar || '');
            $('#job-log').val(job.log_arquivo || '');

            $('#titulo-modal-job').text('Editar Job');
            $('#modalJob').modal('show');
        }
    });
}

// Salvar job
function salvarJob() {
    const id = $('#job-id').val();
    const agendamentoTipo = $('#job-agendamento-tipo').val();
    const data = {
        action: 'salvar_job',
        id: id || null,
        nome: $('#job-nome').val(),
        descricao: $('#job-descricao').val(),
        tipo: $('#job-tipo').val(),
        comando: $('#job-comando').val(),
        diretorio_trabalho: $('#job-diretorio').val(),
        executar_como: $('#job-executar-como').val(),
        agendamento_tipo: agendamentoTipo,
        agendamento_config: JSON.stringify({
            intervalo_valor: (agendamentoTipo === 'minutos' || agendamentoTipo === 'horas') ? $('#job-intervalo-valor').val() : null,
            compactar: $('#job-compactar').is(':checked') ? 1 : 0,
            retencao: {
                habilitado: $('#job-versionamento').is(':checked') ? 1 : 0,
                valor: parseInt($('#job-retencao-valor').val()),
                unidade: $('#job-retencao-unidade').val()
            }
        }),
        proxima_execucao: $('#job-proxima-execucao').val(),
        priority: parseInt($('#job-priority').val()),
        timeout: parseInt($('#job-timeout').val()),
        email_notificar: $('#job-email').val(),
        log_arquivo: $('#job-log').val()
    };

    $.ajax({
        url: '/modulos/servicos/job_manager/job_manager_logica.php',
        type: 'POST',
        data: data,
        dataType: 'json',
        beforeSend: function() {
            $('button[onclick="salvarJob()"]').html('<i class="fas fa-spinner fa-spin"></i> Salvando...').prop('disabled', true);
        },
        success: function(response) {
            if (response.error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: response.error
                });
                return;
            }

            Swal.fire({
                icon: 'success',
                title: 'Sucesso',
                text: response.success,
                timer: 2000,
                showConfirmButton: false
            });
            $('#modalJob').modal('hide');
            carregarJobs();
        },
        error: function() {
            Swal.fire({
                icon: 'error',
                title: 'Erro',
                text: 'Erro de comunicação com o servidor'
            });
        },
        complete: function() {
            $('button[onclick="salvarJob()"]').html('<i class="fas fa-save"></i> Salvar').prop('disabled', false);
        }
    });
}

// Executar job
function executarJob(id) {
    const btn = $(`.btn-executar[data-id="${id}"]`);
    const statusBadge = $(`.status-badge[data-id="${id}"]`);
    const originalBtnHtml = btn.html();

    Swal.fire({
        title: 'Executar Job',
        text: 'Deseja executar este job agora?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sim, executar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            // Feedback visual imediato
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
            statusBadge.removeClass('bg-success bg-danger bg-warning bg-info bg-secondary')
                       .addClass('bg-warning text-dark')
                       .html('<i class="fas fa-spinner fa-spin"></i> EXECUTANDO');

            // Atualizar cards imediatamente para mostrar "executando"
            atualizarCardsResumo();

            $.ajax({
                url: '/modulos/servicos/job_manager/job_manager_logica.php',
                type: 'POST',
                data: { action: 'executar_job', id: id, manual: 1 },
                dataType: 'json',
                success: function(response) {
                    if (response.error) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Erro',
                            text: response.error
                        });
                        btn.prop('disabled', false).html(originalBtnHtml);
                        carregarJobs();
                    } else {
                        Swal.fire({
                            icon: 'success',
                            title: 'Enviado',
                            text: 'Job enviado para execução!',
                            timer: 1500,
                            showConfirmButton: false
                        });
                        // Verificar progresso a cada 2 segundos por até 5 minutos
                        let checks = 0;
                        const checkInterval = setInterval(() => {
                            checks++;
                            verificarProgresso(response.execucao_id, checkInterval);
                            if (checks >= 150) { // 5 minutos máximo
                                clearInterval(checkInterval);
                            }
                        }, 2000);

                        carregarJobs();
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro',
                        text: 'Erro de comunicação com o servidor'
                    });
                    btn.prop('disabled', false).html(originalBtnHtml);
                    carregarJobs();
                }
            });
        }
    });
}

// Pausar job
function pausarJob(id) {
    $.ajax({
        url: '/modulos/servicos/job_manager/job_manager_logica.php',
        type: 'POST',
        data: { action: 'pausar_job', id: id },
        dataType: 'json',
        success: function(response) {
            if (response.error) {
                mostrarAlerta('Erro: ' + response.error, 'danger');
                return;
            }

            mostrarAlerta(response.success, 'warning');
            carregarJobs();
        },
        error: function() {
            mostrarAlerta('Erro de comunicação com o servidor', 'danger');
        }
    });
}

// Ativar job
function ativarJob(id) {
    $.ajax({
        url: '/modulos/servicos/job_manager/job_manager_logica.php',
        type: 'POST',
        data: { action: 'ativar_job', id: id },
        dataType: 'json',
        success: function(response) {
            if (response.error) {
                mostrarAlerta('Erro: ' + response.error, 'danger');
                return;
            }

            mostrarAlerta(response.success, 'success');
            carregarJobs();
        },
        error: function() {
            mostrarAlerta('Erro de comunicação com o servidor', 'danger');
        }
    });
}

// Excluir job
function excluirJob(id) {
    Swal.fire({
        title: 'Tem certeza?',
        text: 'Esta ação não pode ser desfeita!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sim, excluir!',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '/modulos/servicos/job_manager/job_manager_logica.php',
                type: 'POST',
                data: { action: 'excluir_job', id: id },
                dataType: 'json',
                success: function(response) {
                    if (response.error) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Erro',
                            text: response.error
                        });
                        return;
                    }

                    Swal.fire({
                        icon: 'success',
                        title: 'Excluído!',
                        text: response.success,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    carregarJobs();
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro',
                        text: 'Erro de comunicação com o servidor'
                    });
                }
            });
        }
    });
}

// Ver execuções do job
function verExecucoes(jobId) {
    $.ajax({
        url: '/modulos/servicos/job_manager/job_manager_logica.php',
        type: 'POST',
        data: { action: 'listar_execucoes', job_id: jobId },
        dataType: 'json',
        success: function(response) {
            if (response.error) {
                mostrarAlerta('Erro: ' + response.error, 'danger');
                return;
            }

            renderizarTabelaExecucoes(response.execucoes);
            $('#modalExecucoes').modal('show');
        },
        error: function() {
            mostrarAlerta('Erro de comunicação com o servidor', 'danger');
        }
    });
}

// Renderizar tabela de execuções
function renderizarTabelaExecucoes(execucoes) {
    const tbody = $('#corpo-tabela-execucoes');
    tbody.empty();

    if (execucoes.length === 0) {
        tbody.html('<tr><td colspan="7" class="text-center">Nenhuma execução encontrada</td></tr>');
        return;
    }

    execucoes.forEach(exec => {
        const row = `
            <tr>
                <td><small>${exec.data_inicio_formatada}</small></td>
                <td><small>${exec.data_fim_formatada}</small></td>
                <td>
                    <span class="status-pill status-${exec.status}">${exec.status}</span>
                </td>
                <td><small>${exec.duracao_formatada}</small></td>
                <td>
                    <button class="btn btn-sm btn-outline-info" onclick="verLogDetalhes(${exec.id})" title="Ver Log">
                        <i class="fas fa-file-alt"></i>
                    </button>
                </td>
                <td><small>${exec.codigo_saida || '-'}</small></td>
                <td>
                    <small class="text-muted">
                        PID: ${exec.processo_id || '-'}<br>
                        ${exec.maquina_execucao || '-'}
                    </small>
                </td>
            </tr>
        `;
        tbody.append(row);
    });
}

// Ver log detalhes
function verLogDetalhes(execucaoId) {
    $.ajax({
        url: '/modulos/servicos/job_manager/job_manager_logica.php',
        type: 'POST',
        data: { action: 'ver_log_execucao', id: execucaoId },
        dataType: 'json',
        success: function(response) {
            if (response.error) {
                mostrarAlerta('Erro: ' + response.error, 'danger');
                return;
            }

            $('#log-saida').text(response.saida_padrao);
            $('#log-erro').text(response.saida_erro);
            $('#modalLog').modal('show');
        },
        error: function() {
            mostrarAlerta('Erro de comunicação com o servidor', 'danger');
        }
    });
}

// Aplicar filtros
function aplicarFiltros() {
    const status = $('#filtro-status').val();
    const tipo = $('#filtro-tipo').val();
    const nome = $('#filtro-nome').val().toLowerCase();

    $('#corpo-tabela-jobs tr').each(function() {
        const row = $(this);
        const statusMatch = !status || row.find('.status-pill').text().toLowerCase() === status;
        const tipoMatch = !tipo || row.find('.badge-secondary').text().toLowerCase() === tipo;
        const nomeMatch = !nome || row.find('strong').text().toLowerCase().includes(nome);

        row.toggle(statusMatch && tipoMatch && nomeMatch);
    });
}

// Atualizar lista
function atualizarLista() {
    carregarJobs();
    mostrarAlerta('Lista atualizada', 'info');
}

// Executar todos os jobs ativos
function executarTodosJobs() {
    if (!confirm('Deseja executar todos os jobs ativos?')) {
        return;
    }

    let executados = 0;
    let erros = 0;

    $('#corpo-tabela-jobs tr').each(function() {
        const row = $(this);
        const status = row.find('.status-pill').text();

        if (status === 'ativo') {
            const jobId = row.find('button[onclick*="executarJob"]').attr('onclick').match(/\d+/)[0];

            $.ajax({
                url: '/modulos/servicos/job_manager/job_manager_logica.php',
                type: 'POST',
                data: { action: 'executar_job', id: jobId },
                dataType: 'json',
                async: false,
                success: function(response) {
                    if (response.error) {
                        erros++;
                    } else {
                        executados++;
                    }
                },
                error: function() {
                    erros++;
                }
            });
        }
    });

    mostrarAlerta(`Jobs executados: ${executados}, Erros: ${erros}`, executados > 0 ? 'success' : 'warning');
    carregarJobs();
}

// Pausar todos os jobs
function pausarTodosJobs() {
    if (!confirm('Deseja pausar todos os jobs ativos?')) {
        return;
    }

    let pausados = 0;

    $('#corpo-tabela-jobs tr').each(function() {
        const row = $(this);
        const status = row.find('.status-pill').text();

        if (status === 'ativo' || status === 'executando') {
            const jobId = row.find('button[onclick*="pausarJob"]').attr('onclick').match(/\d+/)[0];

            $.ajax({
                url: '/modulos/servicos/job_manager/job_manager_logica.php',
                type: 'POST',
                data: { action: 'pausar_job', id: jobId },
                dataType: 'json',
                async: false,
                success: function(response) {
                    if (!response.error) {
                        pausados++;
                    }
                }
            });
        }
    });

    mostrarAlerta(`${pausados} jobs pausados`, 'warning');
    carregarJobs();
}

// Funções utilitárias
function getIconeTipo(tipo) {
    const icones = {
        'backup': 'database',
        'script': 'code',
        'site': 'globe',
        'relatorio': 'file-alt',
        'limpeza': 'broom',
        'outro': 'cog'
    };
    return icones[tipo] || 'cog';
}

function getPriorityClass(priority) {
    if (priority >= 8) return 'high';
    if (priority >= 5) return 'medium';
    return 'low';
}

function toggleCamposAgendamento() {
    const tipo = $('#job-agendamento-tipo').val();
    if (tipo === 'minutos' || tipo === 'horas') {
        $('#div-intervalo-valor').show();
    } else {
        $('#div-intervalo-valor').hide();
    }
}

function toggleCamposVersionamento() {
    if ($('#job-versionamento').is(':checked')) {
        $('#div-versionamento').show();
    } else {
        $('#div-versionamento').hide();
    }
}

// Ver detalhes do card
function verDetalhesCard(tipo) {
    $.ajax({
        url: '/modulos/servicos/job_manager/job_manager_logica.php',
        type: 'POST',
        data: { action: 'listar_jobs' },
        dataType: 'json',
        success: function(response) {
            if (response.error) return;

            let jobsFiltrados = response.jobs;
            let titulo = 'Todos os Jobs';

            if (tipo === 'ativos') {
                jobsFiltrados = response.jobs.filter(j => j.status === 'ativo');
                titulo = 'Jobs Ativos';
            } else if (tipo === 'executando') {
                jobsFiltrados = response.jobs.filter(j => j.status === 'executando');
                titulo = 'Jobs em Execução';
            } else if (tipo === 'erro') {
                jobsFiltrados = response.jobs.filter(j => j.status === 'erro');
                titulo = 'Jobs com Erro';
            }

            $('#titulo-modal-detalhes').text(titulo);
            const tbody = $('#tabela-detalhes-card tbody');
            tbody.empty();

            if (jobsFiltrados.length === 0) {
                $('#tabela-detalhes-card').hide();
                $('#mensagem-vazia-card').show().find('h5').text(
                    tipo === 'executando' ? 'Nenhum job em execução no momento :).' : 'Nenhum job encontrado :).'
                );
            } else {
                $('#tabela-detalhes-card').show();
                $('#mensagem-vazia-card').hide();

                jobsFiltrados.forEach(job => {
                    let statusClass = 'success';
                    if (job.status === 'executando') statusClass = 'warning';
                    if (job.status === 'erro') statusClass = 'danger';
                    if (job.status === 'pausado') statusClass = 'secondary';

                    tbody.append(`
                        <tr>
                            <td>${job.nome}</td>
                            <td><span class="badge bg-light">${job.tipo}</span></td>
                            <td><span class="badge bg-${statusClass}">${job.status.toUpperCase()}</span></td>
                            <td>${job.ultima_execucao ? formatarData(job.ultima_execucao) : '-'}</td>
                        </tr>
                    `);
                });
            }

            $('#modalDetalhesCard').modal('show');
        }
    });
}

// Verificar progresso da execução
function verificarProgresso(execucaoId, intervalId) {
    $.ajax({
        url: '/modulos/servicos/job_manager/job_manager_logica.php',
        type: 'POST',
        data: { action: 'verificar_progresso', execucao_id: execucaoId },
        dataType: 'json',
        success: function(response) {
            if (response.error) {
                clearInterval(intervalId);
                return;
            }

            // Atualizar cards
            atualizarCardsResumo();
            carregarJobs();

            // Mostrar progresso se disponível
            if (response.progress !== undefined) {
                const jobRow = $(`tr:has(button[onclick*="executarJob(${execucaoId})"])`);
                if (jobRow.length) {
                    const statusBadge = jobRow.find('.status-pill');
                    if (response.status === 'executando') {
                        statusBadge.removeClass().addClass('status-pill status-executando')
                                .html(`<i class="fas fa-spinner fa-spin"></i> ${response.status_text}`);
                    } else if (response.status === 'sucesso') {
                        clearInterval(intervalId);
                        statusBadge.removeClass().addClass('status-pill status-success')
                                .html('CONCLUÍDO');
                        Swal.fire({
                            icon: 'success',
                            title: 'Job Concluído',
                            text: 'Execução finalizada com sucesso!',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    } else if (response.status === 'erro') {
                        clearInterval(intervalId);
                        statusBadge.removeClass().addClass('status-pill status-danger')
                                .html('ERRO');
                        Swal.fire({
                            icon: 'error',
                            title: 'Erro na Execução',
                            text: response.saida_erro || 'Ocorreu um erro durante a execução'
                        });
                    }
                }
            }

            // Parar se não estiver mais executando
            if (response.status !== 'executando') {
                clearInterval(intervalId);
            }
        },
        error: function() {
            clearInterval(intervalId);
        }
    });
}

// Formatar data no formato brasileiro
function formatarData(dataString) {
    if (!dataString) return '-';
    // Se já tem T, usa direto, senão adiciona
    const dateString = dataString.includes('T') ? dataString : dataString + 'T';
    const data = new Date(dateString);
    if (isNaN(data.getTime())) return '-'; // Verificar se data é válida
    return data.toLocaleDateString('pt-BR') + ' ' + data.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
}

// Sobrescrever mostrarAlerta para usar SweetAlert2
function mostrarAlerta(mensagem, tipo) {
    let icon = 'info';
    if (tipo === 'danger') icon = 'error';
    if (tipo === 'success') icon = 'success';
    if (tipo === 'warning') icon = 'warning';

    Swal.fire({
        icon: icon,
        title: icon.charAt(0).toUpperCase() + icon.slice(1),
        text: mensagem,
        timer: 3000,
        showConfirmButton: false
    });
}

// Event listeners para filtros
$('#filtro-status, #filtro-tipo, #filtro-nome').on('input change', aplicarFiltros);

// Teclas de atalho
$(document).on('keydown', function(e) {
    // Ctrl+N: Novo job
    if (e.ctrlKey && e.key === 'n') {
        e.preventDefault();
        abrirModalJob();
    }

    // F5: Atualizar
    if (e.key === 'F5') {
        e.preventDefault();
        atualizarLista();
    }

    // Ctrl+E: Executar todos
    if (e.ctrlKey && e.key === 'e') {
        e.preventDefault();
        executarTodosJobs();
    }
});
