/**
 * SIGEP - Módulo de Avisos de Manutenção
 * JavaScript Principal - Gestão de Avisos
 */

// Proteger contra múltiplos carregamentos no SPA
if (typeof window.avisoManutencaoLoaded === 'undefined') {
    window.avisoManutencaoLoaded = true;

// Variáveis globais
var avisosData = {
    todos: [],
    ativos: []
};

// Inicialização quando documento estiver pronto
$(document).ready(function() {
    carregarAvisos();
    inicializarComponentes();
    atualizarEstatisticas();
});

// Carregar todos os avisos
function carregarAvisos() {
    $.ajax({
        url: '/modulos/geral/aviso_manutencao/aviso_manutencao_logica.php',
        method: 'POST',
        data: { action: 'listar_todos' },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                avisosData.todos = response.data;
                atualizarTabela();
                atualizarEstatisticas();
            } else {
                mostrarNotificacao('Erro ao carregar avisos: ' + response.message, 'error');
            }
        },
        error: function(xhr, status, error) {
            console.error('Erro ao carregar avisos:', error);
            mostrarNotificacao('Falha na comunicação com o servidor', 'error');
        }
    });
}

// Inicializar componentes
function inicializarComponentes() {
    // Configurar Select2 para selects múltiplos
    $('#aviso_setores').select2({
        theme: 'bootstrap4',
        placeholder: 'Selecione os setores...',
        allowClear: true
    });

    // Configurar datetime-local com valores padrão
    var agora = new Date();
    var amanha = new Date(agora.getTime() + 24 * 60 * 60 * 1000);
    
    $('#aviso_data_inicio').val(formatarDateTimeLocal(agora));
    $('#aviso_data_fim').val(formatarDateTimeLocal(amanha));

    // Configurar validação do formulário
    $('#formAviso').on('submit', function(e) {
        e.preventDefault();
        salvarAviso();
    });
}

// Formatar data para datetime-local
function formatarDateTimeLocal(date) {
    var ano = date.getFullYear();
    var mes = String(date.getMonth() + 1).padStart(2, '0');
    var dia = String(date.getDate()).padStart(2, '0');
    var horas = String(date.getHours()).padStart(2, '0');
    var minutos = String(date.getMinutes()).padStart(2, '0');
    
    return ano + '-' + mes + '-' + dia + 'T' + horas + ':' + minutos;
}

// Atualizar tabela de avisos
function atualizarTabela() {
    var tbody = $('#tabelaAvisosBody');
    
    if (avisosData.todos.length === 0) {
        tbody.html('<tr><td colspan="7" class="text-center">Nenhum aviso encontrado</td></tr>');
        return;
    }

    var html = '';
    avisosData.todos.forEach(function(aviso) {
        var badgeClasse = 'badge-' + aviso.severidade;
        var statusBadge = getStatusBadge(aviso);
        var periodo = formatarPeriodo(aviso.data_inicio, aviso.data_fim);
        
        html += `
            <tr>
                <td>${aviso.id}</td>
                <td>
                    <strong>${escapeHtml(aviso.titulo)}</strong>
                    ${aviso.sistemas_impactados ? '<br><small class="text-muted">' + escapeHtml(aviso.sistemas_impactados) + '</small>' : ''}
                </td>
                <td><span class="badge ${badgeClasse}">${aviso.severidade.toUpperCase()}</span></td>
                <td><small>${periodo}</small></td>
                <td>${statusBadge}</td>
                <td><small>${formatarDataHora(aviso.criado_em)}</small></td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-info" onclick="editarAviso(${aviso.id})" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-outline-${aviso.ativo ? 'warning' : 'success'}" onclick="toggleAtivo(${aviso.id}, ${aviso.ativo})" title="${aviso.ativo ? 'Desativar' : 'Ativar'}">
                            <i class="fas fa-${aviso.ativo ? 'eye-slash' : 'eye'}"></i>
                        </button>
                        <button class="btn btn-outline-danger" onclick="excluirAviso(${aviso.id})" title="Excluir">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    });
    
    tbody.html(html);
}

// Obter badge de status
function getStatusBadge(aviso) {
    var agora = new Date();
    var dataInicio = new Date(aviso.data_inicio);
    var dataFim = new Date(aviso.data_fim);
    
    if (!aviso.ativo) {
        return '<span class="badge badge-secondary">Inativo</span>';
    }
    
    if (agora < dataInicio) {
        return '<span class="badge badge-info">Agendado</span>';
    } else if (agora >= dataInicio && agora <= dataFim) {
        return '<span class="badge badge-success">Ativo</span>';
    } else {
        return '<span class="badge badge-secondary">Expirado</span>';
    }
}

// Formatar período de manutenção
function formatarPeriodo(dataInicio, dataFim) {
    var inicio = new Date(dataInicio);
    var fim = new Date(dataFim);
    
    return formatarData(inicio) + ' à ' + formatarData(fim);
}

// Formatar data
function formatarData(data) {
    return data.toLocaleDateString('pt-BR') + ' ' + data.toLocaleTimeString('pt-BR', {hour: '2-digit', minute:'2-digit'});
}

// Formatar data e hora
function formatarDataHora(dataString) {
    var data = new Date(dataString);
    return data.toLocaleString('pt-BR');
}

// Abrir modal para novo aviso
function abrirModalNovoAviso() {
    $('#modalAvisoTitleText').text('Novo Aviso de Manutenção');
    $('#formAviso')[0].reset();
    $('#aviso_id').val('');
    
    // Configurar valores padrão
    var agora = new Date();
    var amanha = new Date(agora.getTime() + 24 * 60 * 60 * 1000);
    
    $('#aviso_data_inicio').val(formatarDateTimeLocal(agora));
    $('#aviso_data_fim').val(formatarDateTimeLocal(amanha));
    $('#aviso_severidade').val('warning');
    $('#aviso_ativo').prop('checked', true);
    
    $('#modalAviso').modal('show');
}

// Editar aviso
function editarAviso(id) {
    $.ajax({
        url: '/modulos/geral/aviso_manutencao/aviso_manutencao_logica.php',
        method: 'POST',
        data: { action: 'buscar', id: id },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                var aviso = response.data;
                $('#modalAvisoTitleText').text('Editar Aviso de Manutenção');
                $('#aviso_id').val(aviso.id);
                $('#aviso_titulo').val(aviso.titulo);
                $('#aviso_mensagem').val(aviso.mensagem);
                $('#aviso_severidade').val(aviso.severidade);
                $('#aviso_data_inicio').val(formatarDateTimeLocal(new Date(aviso.data_inicio)));
                $('#aviso_data_fim').val(formatarDateTimeLocal(new Date(aviso.data_fim)));
                $('#aviso_ativo').prop('checked', aviso.ativo == 1);
                
                // Configurar setores impactados
                $('#aviso_setores').val(aviso.setores_impactados || []).trigger('change');
                
                // Configurar sistemas impactados
                var sistemas = aviso.sistemas_impactados ? (Array.isArray(aviso.sistemas_impactados) ? aviso.sistemas_impactados.join(', ') : aviso.sistemas_impactados) : '';
                $('#aviso_sistemas').val(sistemas);
                
                $('#modalAviso').modal('show');
            } else {
                mostrarNotificacao('Erro ao carregar aviso: ' + response.message, 'error');
            }
        },
        error: function() {
            mostrarNotificacao('Falha na comunicação', 'error');
        }
    });
}

// Salvar aviso
function salvarAviso() {
    var formData = $('#formAviso').serializeArray();
    var dados = {};
    
    formData.forEach(function(item) {
        if (item.name === 'setores_impactados[]') {
            if (!dados.setores_impactados) dados.setores_impactados = [];
            dados.setores_impactados.push(item.value);
        } else if (item.name !== 'setores_impactados[]') {
            dados[item.name] = item.value;
        }
    });
    
    // Processar sistemas impactados
    if ($('#aviso_sistemas').val()) {
        dados.sistemas_impactados = $('#aviso_sistemas').val().split(',').map(function(s) {
            return s.trim();
        });
    }
    
    // Converter checkbox para boolean
    dados.ativo = dados.ativo ? 1 : 0;
    
    var action = dados.id ? 'atualizar' : 'criar';
    
    $.ajax({
        url: '/modulos/geral/aviso_manutencao/aviso_manutencao_logica.php',
        method: 'POST',
        data: { action: action, ...dados },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#modalAviso').modal('hide');
                mostrarNotificacao(response.message || 'Aviso salvo com sucesso', 'success');
                carregarAvisos();
            } else {
                mostrarNotificacao('Erro: ' + response.message, 'error');
            }
        },
        error: function() {
            mostrarNotificacao('Falha na comunicação', 'error');
        }
    });
}

// Toggle ativo/inativo
function toggleAtivo(id, statusAtual) {
    var novoStatus = statusAtual ? 0 : 1;
    var mensagem = novoStatus ? 'ativar' : 'desativar';
    
    if (!confirm('Deseja realmente ' + mensagem + ' este aviso?')) {
        return;
    }
    
    $.ajax({
        url: '/modulos/geral/aviso_manutencao/aviso_manutencao_logica.php',
        method: 'POST',
        data: { action: 'atualizar', id: id, ativo: novoStatus },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                mostrarNotificacao('Aviso ' + mensagem + 'do com sucesso', 'success');
                carregarAvisos();
            } else {
                mostrarNotificacao('Erro: ' + response.message, 'error');
            }
        },
        error: function() {
            mostrarNotificacao('Falha na comunicação', 'error');
        }
    });
}

// Excluir aviso
function excluirAviso(id) {
    if (!confirm('Deseja realmente excluir este aviso? Esta ação não pode ser desfeita.')) {
        return;
    }
    
    $.ajax({
        url: '/modulos/geral/aviso_manutencao/aviso_manutencao_logica.php',
        method: 'POST',
        data: { action: 'excluir', id: id },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                mostrarNotificacao('Aviso excluído com sucesso', 'success');
                carregarAvisos();
            } else {
                mostrarNotificacao('Erro: ' + response.message, 'error');
            }
        },
        error: function() {
            mostrarNotificacao('Falha na comunicação', 'error');
        }
    });
}

// Recarregar avisos
function recarregarAvisos() {
    carregarAvisos();
    mostrarNotificacao('Avisos recarregados', 'info');
}

// Atualizar estatísticas
function atualizarEstatisticas() {
    var stats = {
        total: 0,
        warning: 0,
        danger: 0,
        success: 0
    };
    
    var agora = new Date();
    
    avisosData.todos.forEach(function(aviso) {
        var dataInicio = new Date(aviso.data_inicio);
        var dataFim = new Date(aviso.data_fim);
        
        if (aviso.ativo && agora >= dataInicio && agora <= dataFim) {
            stats.total++;
            stats[aviso.severidade]++;
        }
    });
    
    $('#stats-total').text(stats.total);
    $('#stats-warning').text(stats.warning);
    $('#stats-danger').text(stats.danger);
    $('#stats-success').text(stats.success);
}

// Utilitários
function mostrarNotificacao(mensagem, tipo = 'info') {
    if (typeof toastr !== 'undefined') {
        toastr[tipo](mensagem);
    } else {
        console.log(`[${tipo.toUpperCase()}] ${mensagem}`);
    }
}

function escapeHtml(text) {
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Fechar bloco de proteção contra múltiplos carregamentos
} // fim do if (typeof window.avisoManutencaoLoaded === 'undefined')
