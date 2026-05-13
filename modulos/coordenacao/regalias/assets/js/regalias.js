/**
 * SIGEP Painel Regalias - JavaScript Principal
 * Funcionalidades AJAX + UI AdminLTE
 */

// Proteger contra múltiplos carregamentos no SPA
if (typeof window.regaliasLoaded === 'undefined') {
    window.regaliasLoaded = true;

// Variáveis globais
var currentData = {
    regalias: [],
    estatisticas: [],
    filtros: {
        galeria: '',
        setor: '',
        busca: ''
    }
};

// Inicialização quando documento estiver pronto
$(document).ready(function() {
    carregarDados();
    carregarEstatisticas();
    inicializarComponentes();

    // Auto-refresh a cada 30 segundos
    setInterval(autoRefresh, 30000);
});

// Carregar dados via AJAX
function carregarDados() {
    $.ajax({
        url: 'modulos/coordenacao/regalias/regalias_logica.php',
        method: 'POST',
        data: { action: 'listar', ...currentData.filtros },
        dataType: 'json',
        beforeSend: function() {
            mostrarLoading(true);
        },
        success: function(response) {
            if (response.success) {
                currentData.regalias = response.data;
                atualizarTabela();
                atualizarContadores();
            } else {
                mostrarNotificacao('Erro ao carregar dados: ' + response.message, 'error');
            }
        },
        error: function(xhr, status, error) {
            console.error('Erro ao carregar dados:', error);
            mostrarNotificacao('Falha na comunicação com o servidor', 'error');
        },
        complete: function() {
            mostrarLoading(false);
        }
    });
}

// Carregar estatísticas
function carregarEstatisticas() {
    $.ajax({
        url: 'modulos/coordenacao/regalias/regalias_logica.php',
        method: 'POST',
        data: { action: 'estatisticas' },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                currentData.estatisticas = response.data;
                atualizarGraficos();
            }
        },
        error: function(xhr, status, error) {
            console.error('Erro ao carregar estatísticas:', error);
        }
    });
}

// Aplicar filtros
function aplicarFiltros() {
    currentData.filtros = {
        galeria: $('#filtro-galeria').val(),
        setor: $('#filtro-setor').val(),
        busca: $('#busca-nome').val()
    };

    carregarDados();
}

// Limpar filtros
function limparFiltros() {
    $('#filtro-galeria').val('');
    $('#filtro-setor').val('');
    $('#busca-nome').val('');

    currentData.filtros = {
        galeria: '',
        setor: '',
        busca: ''
    };

    carregarDados();
}

// Filtrar por categoria (cards)
function filtrarRegalias(categoria) {
    switch(categoria) {
        case 'todos':
            $('#filtro-setor').val('');
            break;
        case 'alimentacao':
            $('#filtro-setor').val('Alimentação');
            break;
        case 'fundo':
            $('#filtro-setor').val('Fundo Rotativo');
            break;
        case 'corte':
            $('#filtro-setor').val('Corte de Cabelo');
            break;
    }

    aplicarFiltros();
}

// Atualizar tabela
function atualizarTabela() {
    var tbody = $('#tabela-corpo');
    tbody.empty();

    if (currentData.regalias.length === 0) {
        tbody.append(`
            <tr>
                <td colspan="9" class="text-center">
                    <div class="empty-state">
                        <i class="fas fa-search"></i>
                        <p>Nenhuma regalia encontrada</p>
                    </div>
                </td>
            </tr>
        `);
        $('#contador-regalias').text('0');
        return;
    }

    currentData.regalias.forEach(function(regalia) {
        var linha = `
            <tr data-galeria="${regalia.galeria}" data-setor="${regalia.regalia_setor}">
                <td>${regalia.ipen}</td>
                <td>${regalia.nome}</td>
                <td>
                    <span class="badge badge-${getCorGaleria(regalia.galeria)}">
                        ${regalia.galeria}
                    </span>
                </td>
                <td>${regalia.bloco}</td>
                <td>
                    <span class="badge badge-${getCorSetor(regalia.regalia_setor)}">
                        ${regalia.regalia_setor}
                    </span>
                </td>
                <td>${getFuncaoDescricao(regalia.regalia_setor)}</td>
                <td>${regalia.dias_trabalho || 'Não definido'}</td>
                <td>
                    <span class="badge badge-success">Ativo</span>
                </td>
                <td>
                    <button type="button" class="btn btn-sm btn-info" onclick="mostrarDetalhes(${regalia.ipen})">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-warning" onclick="editarRegalia(${regalia.ipen})">
                        <i class="fas fa-edit"></i>
                    </button>
                </td>
            </tr>
        `;
        tbody.append(linha);
    });

    $('#contador-regalias').text(currentData.regalias.length);
}

// Atualizar contadores
function atualizarContadores() {
    var total = currentData.regalias.length;
    var alimentacao = currentData.regalias.filter(r => r.regalia_setor === 'Alimentação').length;
    var fundo = currentData.regalias.filter(r => r.regalia_setor === 'Fundo Rotativo').length;
    var corte = currentData.regalias.filter(r => r.regalia_setor === 'Corte de Cabelo').length;

    $('#stats-total').text(total);
    $('#stats-alimentacao').text(alimentacao);
    $('#stats-fundo').text(fundo);
    $('#stats-corte').text(corte);
}

// Mostrar detalhes da regalia
function mostrarDetalhes(ipen) {
    $.ajax({
        url: 'modulos/coordenacao/regalias/regalias_logica.php',
        method: 'POST',
        data: { action: 'detalhes', ipen: ipen },
        dataType: 'json',
        beforeSend: function() {
            $('#modal-detalhes-conteudo').html(`
                <div class="loading-spinner">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Carregando detalhes...</p>
                </div>
            `);
        },
        success: function(response) {
            if (response.success) {
                var detalhes = response.data;
                var conteudo = `
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Informações Pessoais</h5>
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>IPEN:</strong></td>
                                    <td>${detalhes.ipen}</td>
                                </tr>
                                <tr>
                                    <td><strong>Nome:</strong></td>
                                    <td>${detalhes.nome}</td>
                                </tr>
                                <tr>
                                    <td><strong>Galeria:</strong></td>
                                    <td>
                                        <span class="badge badge-${getCorGaleria(detalhes.galeria)}">
                                            ${detalhes.galeria}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Bloco:</strong></td>
                                    <td>${detalhes.bloco}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h5>Informações Trabalhistas</h5>
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>Setor:</strong></td>
                                    <td>
                                        <span class="badge badge-${getCorSetor(detalhes.regalia_setor)}">
                                            ${detalhes.regalia_setor}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Função:</strong></td>
                                    <td>${getFuncaoDescricao(detalhes.regalia_setor)}</td>
                                </tr>
                                <tr>
                                    <td><strong>Estabelecimento:</strong></td>
                                    <td>${detalhes.estabelecimento || 'Não definido'}</td>
                                </tr>
                                <tr>
                                    <td><strong>Dias de Trabalho:</strong></td>
                                    <td>${detalhes.dias_semana || 'Não definido'}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                `;
                $('#modal-detalhes-conteudo').html(conteudo);
            } else {
                $('#modal-detalhes-conteudo').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        Erro ao carregar detalhes: ${response.message}
                    </div>
                `);
            }
        },
        error: function() {
            $('#modal-detalhes-conteudo').html(`
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    Falha na comunicação com o servidor
                </div>
            `);
        }
    });

    $('#modal-detalhes').modal('show');
}

// Editar regalia (placeholder)
function editarRegalia(ipen) {
    mostrarNotificacao('Funcionalidade de edição em desenvolvimento', 'info');
}

// Exportar dados
function exportarDados() {
    mostrarNotificacao('Funcionalidade de exportação em desenvolvimento', 'info');
}

// Atualizar dados
function atualizarDados() {
    carregarDados();
    carregarEstatisticas();
}

// Auto-refresh
function autoRefresh() {
    if (!$('#modal-detalhes').hasClass('show')) {
        carregarDados();
    }
}

// Inicializar componentes
function inicializarComponentes() {
    // Tooltips
    $('[data-toggle="tooltip"]').tooltip();

    // Event listeners
    $('#filtro-galeria, #filtro-setor').change(aplicarFiltros);
    $('#busca-nome').on('keyup', function(e) {
        if (e.keyCode === 13) {
            aplicarFiltros();
        }
    });
}

// Inicializar gráficos
function inicializarGraficos() {
    carregarEstatisticas();
}

// Atualizar gráficos
function atualizarGraficos() {
    if (!currentData.estatisticas.por_setor) return;

    // Verificar se Chart.js está disponível
    if (typeof Chart === 'undefined') {
        console.log('Chart.js não disponível, gráficos não serão exibidos');
        return;
    }

    // Gráfico de pizza - Distribuição por Setor
    var ctxSetor = document.getElementById('grafico-setor');
    if (ctxSetor) {
        new Chart(ctxSetor.getContext('2d'), {
            type: 'pie',
            data: {
                labels: currentData.estatisticas.por_setor.map(item => item.regalia_setor),
                datasets: [{
                    data: currentData.estatisticas.por_setor.map(item => item.total),
                    backgroundColor: [
                        '#28a745',
                        '#ffc107',
                        '#dc3545',
                        '#17a2b8',
                        '#6f42c1',
                        '#343a40',
                        '#fd7e14',
                        '#20c997'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }

    // Gráfico de barras - Distribuição por Galeria
    var ctxGaleria = document.getElementById('grafico-galeria');
    if (ctxGaleria) {
        new Chart(ctxGaleria.getContext('2d'), {
            type: 'bar',
            data: {
                labels: currentData.estatisticas.por_galeria.map(item => 'Galeria ' + item.galeria),
                datasets: [{
                    label: 'Quantidade de Regalias',
                    data: currentData.estatisticas.por_galeria.map(item => item.total),
                    backgroundColor: '#007bff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }
}

// Funções utilitárias
function getCorGaleria(galeria) {
    var cores = {
        'A': 'primary',
        'B': 'secondary',
        'C': 'success',
        'D': 'danger',
        'E': 'warning',
        'G': 'info',
        'H': 'dark',
        'S': 'purple'
    };
    return cores[galeria] || 'secondary';
}

function getCorSetor(setor) {
    if (setor.includes('Alimentação')) return 'success';
    if (setor.includes('Fundo Rotativo')) return 'warning';
    if (setor.includes('Corte de Cabelo')) return 'danger';
    if (setor.includes('Conveniado')) return 'info';
    if (setor.includes('Manutenção')) return 'secondary';
    return 'primary';
}

function getFuncaoDescricao(setor) {
    var descricoes = {
        'Alimentação': 'Entrega de Marmitas (Por Remição)',
        'Fundo Rotativo': 'Serviços Gerais',
        'Corte de Cabelo': 'Barbeiro do Bloco (Por Remição)',
        'Conveniado - Hospital': 'Hospital',
        'Manutenção': 'Manutenção Predial',
        'Limpeza': 'Limpeza e Organização',
        'OBRAS': 'Construção Civil',
        'Serviços Gerais': 'Serviços Gerais',
        'Censura': 'Rouparia, Eletrônicos e Cartas',
        'HORTA': 'Horta e Jardinagem',
        'Biblioteca': 'Organização e Limpeza',
        'COZINHA': 'Preparo de Alimentação',
        'Pintura': 'Pintura e Acabamentos',
        'Roçada': 'Jardinagem e Limpeza',
        'Reciclagem': 'Separação de Materiais',
        'Serralheria': 'Serralheria',
        'Almoxarifado': 'Controle de Estoque',
        'Eletrônica / Informática': 'Manutenção de Equipamentos e Desenvolvimento de Sistemas',
        'Manutenção Geral': 'Manutenções Gerais'
    };

    for (var chave in descricoes) {
        if (setor.includes(chave)) {
            return descricoes[chave];
        }
    }

    return 'Função não especificada';
}

function mostrarNotificacao(mensagem, tipo = 'info') {
    if (typeof toastr !== 'undefined') {
        toastr[tipo](mensagem);
    } else {
        console.log(`[${tipo.toUpperCase()}] ${mensagem}`);
    }
}

function mostrarLoading(mostrar) {
    if (mostrar) {
        $('body').addClass('loading');
    } else {
        $('body').removeClass('loading');
    }
}

// Event listeners
$(document).on('click', '#btn-salvar', function() {
    salvarRegalia();
});

// Fechar bloco de proteção contra múltiplos carregamentos
} // fim do if (typeof window.regaliasLoaded === 'undefined')
