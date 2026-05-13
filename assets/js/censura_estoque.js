// JavaScript para Sistema de Controle de Estoque da Censura

// ===== VARIÁVEIS GLOBAIS =====
let currentOffcanvas = null;
let currentProdutoId = null;

// ===== FUNÇÕES DE BUSCA DE INTERNOS =====
let internosTimeout;

function buscarInternos(termo) {
    if (termo.length < 2) {
        document.getElementById('internos-sugestoes').style.display = 'none';
        return;
    }

    fetch('includes/censura_estoque_controle_logica.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `db_action=buscar_internos&termo=${encodeURIComponent(termo)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            mostrarSugestoesInternos(data.internos);
        } else {
            console.error('Erro ao buscar internos:', data.error);
        }
    })
    .catch(error => {
        console.error('Erro na requisição:', error);
    });
}

function mostrarSugestoesInternos(internos) {
    const container = document.getElementById('internos-sugestoes');

    if (!internos || internos.length === 0) {
        container.innerHTML = '<div class="nenhum-resultado">Nenhum interno encontrado</div>';
        container.style.display = 'block';
        return;
    }

    container.innerHTML = '';

    internos.forEach(interno => {
        const item = document.createElement('div');
        item.className = 'sugestao-item';
        item.onclick = () => selecionarInterno(interno);

        const nomeSocial = interno.nome_social && interno.nome_social !== interno.nome ?
            `<span class="nome-social">(${interno.nome_social})</span>` : '';

        item.innerHTML = `
            <span class="ipen">${interno.ipen}</span>
            <span class="nome">${interno.nome}</span>
            ${nomeSocial}
        `;

        container.appendChild(item);
    });

    container.style.display = 'block';
}

function selecionarInterno(interno) {
    // Preencher campos
    document.getElementById('mov_id_interno').value = interno.id;
    document.getElementById('mov_interno_busca').value = `${interno.ipen} - ${interno.nome}`;

    // Esconder sugestões
    document.getElementById('internos-sugestoes').style.display = 'none';

    // Mostrar informação do interno selecionado
    const nomeSocial = interno.nome_social && interno.nome_social !== interno.nome ?
        ` (${interno.nome_social})` : '';

    document.getElementById('interno-info').textContent = `${interno.ipen} - ${interno.nome}${nomeSocial}`;
    document.getElementById('interno-selecionado').style.display = 'block';

    // Limpar campo de busca para evitar confusão
    document.getElementById('mov_interno_busca').blur();
}

function limparInternoSelecionado() {
    document.getElementById('mov_id_interno').value = '';
    document.getElementById('mov_interno_busca').value = '';
    document.getElementById('interno-selecionado').style.display = 'none';
    document.getElementById('internos-sugestoes').style.display = 'none';
}

// ===== EVENT LISTENERS PARA BUSCA DE INTERNOS =====
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar tema baseado na preferência do sistema ou armazenada
    inicializarTema();

    // Inicializar tabela dinâmica
    carregarTabelaMovimentacoes();

    // Event listeners para busca de internos
    const buscaInterno = document.getElementById('mov_interno_busca');
    if (buscaInterno) {
        buscaInterno.addEventListener('input', function() {
            clearTimeout(internosTimeout);
            internosTimeout = setTimeout(() => {
                buscarInternos(this.value);
            }, 300);
        });

        buscaInterno.addEventListener('focus', function() {
            if (this.value.length >= 2) {
                buscarInternos(this.value);
            }
        });

        buscaInterno.addEventListener('blur', function() {
            // Pequeno delay para permitir clique nas sugestões
            setTimeout(() => {
                document.getElementById('internos-sugestoes').style.display = 'none';
            }, 200);
        });
    }

    // Botão para limpar interno selecionado
    const btnLimparInterno = document.getElementById('btn-limpar-interno');
    if (btnLimparInterno) {
        btnLimparInterno.addEventListener('click', limparInternoSelecionado);
    }

    // Criar objeto de funções no window
    window.estoqueFunctions = {

        // ===== FUNÇÕES DE PRODUTOS =====
        novoProduto: function() {
            currentProdutoId = null;
            limparFormProduto();
            exibirOffcanvas('offcanvasProduto');
        },

        editarProduto: function(id) {
            currentProdutoId = id;
            carregarProduto(id);
            exibirOffcanvas('offcanvasProduto');
        },

        salvarProduto: function(event) {
            event.preventDefault();
            const formData = new FormData(event.target);

            // Adicionar ação
            if (currentProdutoId) {
                formData.append('db_action', 'editar_produto');
                formData.append('id', currentProdutoId);
            } else {
                formData.append('db_action', 'salvar_produto');
            }

            fetch('includes/censura_estoque_controle_logica.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Produto salvo com sucesso!');
                    this.fecharOffcanvas();
                    location.reload();
                } else {
                    alert('Erro: ' + (data.message || 'Erro desconhecido'));
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao salvar produto');
            });
        },

        // ===== FUNÇÕES DE FORNECEDORES =====
        novoFornecedor: function() {
            limparFormFornecedor();
            exibirOffcanvas('offcanvasFornecedor');
        },

        salvarFornecedor: function(event) {
            event.preventDefault();
            const formData = new FormData(event.target);
            formData.append('db_action', 'salvar_fornecedor');

            fetch('includes/censura_estoque_controle_logica.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Fornecedor salvo com sucesso!');
                    this.fecharOffcanvas();
                    location.reload();
                } else {
                    alert('Erro: ' + (data.message || 'Erro desconhecido'));
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao salvar fornecedor');
            });
        },

        // ===== FUNÇÕES DE MOVIMENTAÇÕES =====
        novaMovimentacao: function(tipo) {
            limparFormMovimentacao();
            document.getElementById('tipo_movimentacao').value = tipo;
            atualizarCamposMovimentacao();
            exibirOffcanvas('offcanvasMovimentacao');
        },

        salvarMovimentacao: function(event) {
            event.preventDefault();
            const formData = new FormData(event.target);
            formData.append('db_action', 'salvar_movimentacao');

            fetch('includes/censura_estoque_controle_logica.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Movimentação registrada com sucesso!');
                    this.fecharOffcanvas();
                    location.reload();
                } else {
                    alert('Erro: ' + (data.message || 'Erro desconhecido'));
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao registrar movimentação');
            });
        },

        cancelarMovimentacao: function(id) {
            if (!confirm('Tem certeza que deseja cancelar esta movimentação?')) return;

            const formData = new FormData();
            formData.append('db_action', 'cancelar_movimentacao');
            formData.append('id', id);

            fetch('includes/censura_estoque_controle_logica.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Movimentação cancelada com sucesso!');
                    location.reload();
                } else {
                    alert('Erro: ' + (data.message || 'Erro desconhecido'));
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao cancelar movimentação');
            });
        },

        // ===== FUNÇÕES DE FILTROS =====
        aplicarFiltros: function() {
            const formData = new FormData(document.getElementById('formFiltros'));
            formData.append('db_action', 'filtrar_movimentacoes');

            fetch('includes/censura_estoque_controle_logica.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    atualizarTabelaMovimentacoes(data.movimentacoes);
                } else {
                    alert('Erro ao filtrar: ' + (data.message || 'Erro desconhecido'));
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao aplicar filtros');
            });
        },

        limparFiltros: function() {
            document.getElementById('formFiltros').reset();
            location.reload();
        },

        // ===== FUNÇÕES GERAIS =====
        fecharOffcanvas: function() {
            document.querySelectorAll('.offcanvas-right').forEach(oc => {
                oc.classList.remove('show');
                oc.style.transform = 'translateX(100%)';
            });
            currentOffcanvas = null;
            currentProdutoId = null;
        },

        gerarRelatorio: function(tipo) {
            alert('Funcionalidade de relatório em desenvolvimento');
        }
    };

    // ===== EVENT LISTENERS =====
    // Fechar offcanvas com ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && currentOffcanvas) {
            window.estoqueFunctions.fecharOffcanvas();
        }
    });

    // Fechar offcanvas clicando fora
    document.addEventListener('click', function(e) {
        if (currentOffcanvas && !e.target.closest('.offcanvas-right') && !e.target.closest('[onclick*="exibirOffcanvas"]')) {
            window.estoqueFunctions.fecharOffcanvas();
        }
    });

    // Auto-complete para internos
    const ipenInput = document.getElementById('mov_ipen');
    if (ipenInput) {
        let timeout;
        ipenInput.addEventListener('input', function() {
            clearTimeout(timeout);
            timeout = setTimeout(buscarInterno, 500);
        });
    }

    // Atualizar saldo ao selecionar produto
    const produtoSelect = document.getElementById('mov_produto');
    if (produtoSelect) {
        produtoSelect.addEventListener('change', atualizarSaldoProduto);
    }

    // Atualizar campos de destino
    const tipoDestinoSelect = document.getElementById('mov_tipo_destino');
    if (tipoDestinoSelect) {
        tipoDestinoSelect.addEventListener('change', atualizarCamposDestino);
        atualizarCamposDestino(); // Executar uma vez no carregamento
    }
});

// ===== FUNÇÕES AUXILIARES =====

function exibirOffcanvas(id) {
    // Fechar todos os offcanvs primeiro
    document.querySelectorAll('.offcanvas-right').forEach(oc => {
        oc.classList.remove('show');
        oc.style.transform = 'translateX(100%)';
    });

    // Exibir o offcanvas solicitado
    const offcanvas = document.getElementById(id);
    if (offcanvas) {
        offcanvas.classList.add('show');
        offcanvas.style.transform = 'translateX(0)';
        currentOffcanvas = id;

        // Carregar dados específicos se necessário
        if (id === 'offcanvasProduto') {
            if (currentProdutoId) {
                carregarProduto(currentProdutoId);
            } else {
                limparFormProduto();
            }
        }
    }
}

function carregarProduto(id) {
    fetch('includes/censura_estoque_controle_logica.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `db_action=load_produto&id=${id}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('produto_id').value = data.produto.id;
            document.getElementById('produto_nome').value = data.produto.nome;
            document.getElementById('produto_descricao').value = data.produto.descricao || '';
            document.getElementById('produto_tipo').value = data.produto.id_tipo;
            document.getElementById('produto_fornecedor').value = data.produto.id_fornecedor || '';
            document.getElementById('produto_qtd_minima').value = data.produto.quantidade_minima;
            document.getElementById('produto_qtd_alerta').value = data.produto.quantidade_alerta;
            document.getElementById('produto_localizacao').value = data.produto.localizacao || '';
            document.getElementById('produto_unidade').value = data.produto.unidade_medida;
            document.getElementById('produto_status').value = data.produto.status;

            // Atualizar saldo atual
            document.getElementById('produto_saldo_atual').textContent = data.saldo || 0;
        }
    })
    .catch(error => {
        console.error('Erro ao carregar produto:', error);
        alert('Erro ao carregar produto');
    });
}

function limparFormProduto() {
    document.getElementById('formProduto').reset();
    document.getElementById('produto_id').value = '';
    document.getElementById('produto_saldo_atual').textContent = '0';
}

function limparFormFornecedor() {
    document.getElementById('formFornecedor').reset();
    document.getElementById('fornecedor_id').value = '';
}

function limparFormMovimentacao() {
    document.getElementById('formMovimentacao').reset();
    document.getElementById('mov_data').value = new Date().toISOString().split('T')[0];
    atualizarCamposDestino();
}

function atualizarCamposDestino() {
    const tipoDestino = document.getElementById('mov_tipo_destino').value;
    const camposInterno = document.getElementById('campos_interno');
    const camposFuncionario = document.getElementById('campos_funcionario');
    const camposOutro = document.getElementById('campos_outro');
    const camposFornecedor = document.getElementById('campos_fornecedor');

    // Esconder todos os campos
    if (camposInterno) camposInterno.style.display = 'none';
    if (camposFuncionario) camposFuncionario.style.display = 'none';
    if (camposOutro) camposOutro.style.display = 'none';
    if (camposFornecedor) camposFornecedor.style.display = 'none';

    // Mostrar campos relevantes
    if (tipoDestino === 'Interno' && camposInterno) {
        camposInterno.style.display = 'block';
    } else if (tipoDestino === 'Funcionario' && camposFuncionario) {
        camposFuncionario.style.display = 'block';
    } else if (tipoDestino === 'Outro' && camposOutro) {
        camposOutro.style.display = 'block';
    } else if (tipoDestino === 'Fornecedor' && camposFornecedor) {
        camposFornecedor.style.display = 'block';
    }
}

function atualizarCamposMovimentacao() {
    atualizarCamposDestino();
}

function buscarInterno() {
    const ipen = document.getElementById('mov_ipen').value.trim();
    const nomeElement = document.getElementById('interno_nome');
    const inputElement = document.getElementById('mov_ipen');

    if (!ipen) {
        if (nomeElement) nomeElement.textContent = '';
        return;
    }

    fetch('includes/censura_estoque_controle_logica.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `db_action=buscar_interno&ipen=${ipen}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (nomeElement) nomeElement.textContent = data.interno.nome;
            if (inputElement) inputElement.style.borderColor = '#28a745';
        } else {
            if (nomeElement) nomeElement.textContent = 'Interno não encontrado';
            if (inputElement) inputElement.style.borderColor = '#dc3545';
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        if (nomeElement) nomeElement.textContent = 'Erro na busca';
    });
}

function atualizarSaldoProduto() {
    const produtoId = document.getElementById('mov_produto').value;
    const saldoElement = document.getElementById('saldo_atual');

    if (!produtoId || !saldoElement) {
        if (saldoElement) saldoElement.textContent = '0';
        return;
    }

    fetch('includes/censura_estoque_controle_logica.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `db_action=buscar_saldo&id_produto=${produtoId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            saldoElement.textContent = data.saldo;
            saldoElement.className = 'info-value-lg';

            if (data.saldo <= data.minimo) {
                saldoElement.style.color = '#dc3545';
            } else if (data.saldo <= data.alerta) {
                saldoElement.style.color = '#ffc107';
            } else {
                saldoElement.style.color = '#28a745';
            }
        }
    })
    .catch(error => {
        console.error('Erro:', error);
    });
}

function atualizarTabelaMovimentacoes(movimentacoes) {
    // Função para atualizar a tabela quando filtros são aplicados
    // Implementação pode ser adicionada conforme necessário
    console.log('Atualizando tabela com', movimentacoes.length, 'movimentações');
}
function fecharOffcanvas() {
    document.querySelectorAll('.offcanvas-right').forEach(oc => {
        oc.classList.remove('show');
        oc.style.transform = 'translateX(100%)';
    });
    currentOffcanvas = null;
    currentProdutoId = null;
}

// Funções de Produto
function novoProduto() {
    currentProdutoId = null;
    exibirOffcanvas('offcanvasProduto');
}

function editarProduto(id) {
    currentProdutoId = id;
    exibirOffcanvas('offcanvasProduto');
}

function carregarProduto(id) {
    fetch('includes/censura_estoque_controle_logica.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `db_action=load_produto&id=${id}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('produto_id').value = data.produto.id;
            document.getElementById('produto_nome').value = data.produto.nome;
            document.getElementById('produto_descricao').value = data.produto.descricao || '';
            document.getElementById('produto_tipo').value = data.produto.id_tipo;
            document.getElementById('produto_fornecedor').value = data.produto.id_fornecedor || '';
            document.getElementById('produto_qtd_minima').value = data.produto.quantidade_minima;
            document.getElementById('produto_qtd_alerta').value = data.produto.quantidade_alerta;
            document.getElementById('produto_localizacao').value = data.produto.localizacao || '';
            document.getElementById('produto_unidade').value = data.produto.unidade_medida;
            document.getElementById('produto_status').value = data.produto.status;
            
            // Atualizar saldo atual
            document.getElementById('produto_saldo_atual').textContent = data.saldo || 0;
        }
    })
    .catch(error => {
        console.error('Erro ao carregar produto:', error);
        alert('Erro ao carregar produto');
    });
}

function limparFormProduto() {
    document.getElementById('formProduto').reset();
    document.getElementById('produto_id').value = '';
    document.getElementById('produto_saldo_atual').textContent = '0';
}

function salvarProduto(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    formData.append('db_action', 'save_produto');
    
    // Adicionar ID se estiver editando
    const id = document.getElementById('produto_id').value;
    if (id) {
        formData.append('id', id);
    }
    
    // Mostrar loading
    const btnSalvar = event.target.querySelector('button[type="submit"]');
    const originalText = btnSalvar.innerHTML;
    btnSalvar.innerHTML = '<span class="spinner-loading"></span> Salvando...';
    btnSalvar.disabled = true;
    
    fetch('includes/censura_estoque_controle_logica.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Produto salvo com sucesso!');
            fecharOffcanvas();
            // Recarregar página para mostrar dados atualizados
            window.location.reload();
        } else {
            alert('Erro ao salvar produto: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao salvar produto');
    })
    .finally(() => {
        btnSalvar.innerHTML = originalText;
        btnSalvar.disabled = false;
    });
}

// Funções de Fornecedor
function novoFornecedor() {
    exibirOffcanvas('offcanvasFornecedor');
}

function salvarFornecedor(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    formData.append('db_action', 'save_fornecedor');
    
    // Adicionar ID se estiver editando
    const id = document.getElementById('fornecedor_id').value;
    if (id) {
        formData.append('id', id);
    }
    
    const btnSalvar = event.target.querySelector('button[type="submit"]');
    const originalText = btnSalvar.innerHTML;
    btnSalvar.innerHTML = '<span class="spinner-loading"></span> Salvando...';
    btnSalvar.disabled = true;
    
    fetch('includes/censura_estoque_controle_logica.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Fornecedor salvo com sucesso!');
            fecharOffcanvas();
            window.location.reload();
        } else {
            alert('Erro ao salvar fornecedor: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao salvar fornecedor');
    })
    .finally(() => {
        btnSalvar.innerHTML = originalText;
        btnSalvar.disabled = false;
    });
}

// Funções de Movimentação
function novaMovimentacao(tipo) {
    currentProdutoId = null;
    document.getElementById('mov_tipo').value = tipo || '';
    document.getElementById('mov_data').value = new Date().toISOString().split('T')[0];
    exibirOffcanvas('offcanvasMovimentacao');
}

function salvarMovimentacao(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    formData.append('db_action', 'save_movimentacao');
    
    const btnSalvar = event.target.querySelector('button[type="submit"]');
    const originalText = btnSalvar.innerHTML;
    btnSalvar.innerHTML = '<span class="spinner-loading"></span> Registrando...';
    btnSalvar.disabled = true;
    
    fetch('includes/censura_estoque_controle_logica.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Movimentação registrada com sucesso!');
            fecharOffcanvas();
            window.location.reload();
        } else {
            alert('Erro ao registrar movimentação: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao registrar movimentação');
    })
    .finally(() => {
        btnSalvar.innerHTML = originalText;
        btnSalvar.disabled = false;
    });
}

function cancelarMovimentacao(id) {
    if (!confirm('Tem certeza que deseja cancelar esta movimentação? Esta ação não poderá ser desfeita.')) {
        return;
    }
    
    fetch('includes/censura_estoque_controle_logica.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `db_action=cancel_movimentacao&id_mov=${id}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Movimentação cancelada com sucesso!');
            window.location.reload();
        } else {
            alert('Erro ao cancelar movimentação: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao cancelar movimentação');
    });
}

// Funções de UI
function atualizarCamposDestino() {
    const tipoDestino = document.getElementById('mov_tipo_destino').value;
    const camposInterno = document.getElementById('campos_interno');
    const camposFuncionario = document.getElementById('campos_funcionario');
    const camposOutro = document.getElementById('campos_outro');
    const camposFornecedor = document.getElementById('campos_fornecedor');
    
    // Esconder todos os campos
    camposInterno.style.display = 'none';
    camposFuncionario.style.display = 'none';
    camposOutro.style.display = 'none';
    camposFornecedor.style.display = 'none';
    
    // Mostrar campos relevantes
    if (tipoDestino === 'Interno') {
        camposInterno.style.display = 'block';
    } else if (tipoDestino === 'Funcionario') {
        camposFuncionario.style.display = 'block';
    } else if (tipoDestino === 'Outro') {
        camposOutro.style.display = 'block';
    } else if (tipoDestino === 'Fornecedor') {
        camposFornecedor.style.display = 'block';
    }
}

function buscarInterno() {
    const ipen = document.getElementById('mov_ipen').value.trim();
    if (!ipen) {
        document.getElementById('interno_nome').textContent = '';
        return;
    }
    
    fetch('includes/censura_estoque_controle_logica.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `db_action=buscar_interno&ipen=${ipen}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('interno_nome').textContent = data.interno.nome;
            document.getElementById('mov_ipen').style.borderColor = '#28a745';
        } else {
            document.getElementById('interno_nome').textContent = 'Interno não encontrado';
            document.getElementById('mov_ipen').style.borderColor = '#dc3545';
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        document.getElementById('interno_nome').textContent = 'Erro na busca';
    });
}

function atualizarSaldoProduto() {
    const produtoId = document.getElementById('mov_produto').value;
    if (!produtoId) {
        document.getElementById('saldo_atual').textContent = '0';
        return;
    }
    
    // Buscar saldo atual do produto
    fetch('includes/censura_estoque_controle_logica.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `db_action=buscar_saldo&id_produto=${produtoId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('saldo_atual').textContent = data.saldo;
            
            // Atualizar cor do saldo baseado nos limites
            const saldoElement = document.getElementById('saldo_atual');
            saldoElement.className = 'info-value-lg';
            
            if (data.saldo <= data.minimo) {
                saldoElement.style.color = '#dc3545';
            } else if (data.saldo <= data.alerta) {
                saldoElement.style.color = '#ffc107';
            } else {
                saldoElement.style.color = '#28a745';
            }
        }
    })
    .catch(error => {
        console.error('Erro:', error);
    });
}

// Event Listeners
document.addEventListener('DOMContentLoaded', function() {
    // Fechar offcanvas com ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && currentOffcanvas) {
            fecharOffcanvas();
        }
    });
    
    // Fechar offcanvas clicando fora
    document.addEventListener('click', function(e) {
        if (currentOffcanvas && !e.target.closest('.offcanvas-right') && !e.target.closest('[onclick*="exibirOffcanvas"]')) {
            fecharOffcanvas();
        }
    });
    
    // Auto-complete para internos
    const ipenInput = document.getElementById('mov_ipen');
    if (ipenInput) {
        let timeout;
        ipenInput.addEventListener('input', function() {
            clearTimeout(timeout);
            timeout = setTimeout(buscarInterno, 500);
        });
    }
    
    // Atualizar saldo ao selecionar produto
    const produtoSelect = document.getElementById('mov_produto');
    if (produtoSelect) {
        produtoSelect.addEventListener('change', atualizarSaldoProduto);
    }
    
    // Atualizar campos de destino
    const tipoDestinoSelect = document.getElementById('mov_tipo_destino');
    if (tipoDestinoSelect) {
        tipoDestinoSelect.addEventListener('change', atualizarCamposDestino);
        atualizarCamposDestino(); // Executar uma vez no carregamento
    }
});

// Funções de Filtros
function aplicarFiltros() {
    const form = document.getElementById('formFiltros');
    if (form) {
        const formData = new FormData(form);
        const params = new URLSearchParams(formData);
        window.location.href = `paginas/censura_estoque_controle.php?${params.toString()}`;
    }
}

function limparFiltros() {
    window.location.href = 'paginas/censura_estoque_controle.php';
}

// Funções de Relatórios
function gerarRelatorio(tipo) {
    let url = `paginas/censura_estoque_controle.php?relatorio=${tipo}`;
    
    if (tipo === 'movimentacoes') {
        // Adicionar filtros atuais
        const form = document.getElementById('formFiltros');
        if (form) {
            const formData = new FormData(form);
            const params = new URLSearchParams(formData);
            url += '&' + params.toString();
        }
    }
    
    window.open(url, '_blank');
}

// ===== FUNÇÕES DE TEMA =====
function inicializarTema() {
    // Verificar se há preferência armazenada
    const temaSalvo = localStorage.getItem('tema-sigep');
    const temaSistema = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';

    const temaAtual = temaSalvo || temaSistema;

    // Aplicar tema
    document.documentElement.setAttribute('data-theme', temaAtual);

    // Salvar preferência se não havia uma salva
    if (!temaSalvo) {
        localStorage.setItem('tema-sigep', temaAtual);
    }

    // Adicionar botão de alternância de tema se não existir
    adicionarBotaoTema();
}

// ===== FUNÇÕES DE TABELA DINÂMICA =====
function carregarTabelaMovimentacoes() {
    const loadingOverlay = document.getElementById('loadingOverlay');
    const tbody = document.getElementById('movementsTableBody');

    if (loadingOverlay) loadingOverlay.style.display = 'flex';
    if (tbody) tbody.innerHTML = '';

    // Obter filtros atuais
    const form = document.getElementById('formFiltros');
    const formData = form ? new FormData(form) : new FormData();
    formData.append('db_action', 'carregar_movimentacoes_ajax');

    fetch('includes/censura_estoque_controle_logica.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            renderizarTabelaMovimentacoes(data.movimentacoes);
            atualizarContadores(data.contadores);
            document.getElementById('totalRegistros').textContent = data.movimentacoes.length + ' registros';
        } else {
            console.error('Erro ao carregar movimentações:', data.error);
            tbody.innerHTML = '<tr><td colspan="8" class="text-center text-danger py-4"><i class="fas fa-exclamation-triangle"></i> Erro ao carregar dados</td></tr>';
        }
    })
    .catch(error => {
        console.error('Erro na requisição:', error);
        tbody.innerHTML = '<tr><td colspan="8" class="text-center text-danger py-4"><i class="fas fa-exclamation-triangle"></i> Erro de conexão</td></tr>';
    })
    .finally(() => {
        if (loadingOverlay) loadingOverlay.style.display = 'none';
    });
}

function renderizarTabelaMovimentacoes(movimentacoes) {
    const tbody = document.getElementById('movementsTableBody');

    if (!tbody) return;

    if (!movimentacoes || movimentacoes.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-4"><i class="fas fa-info-circle"></i> Nenhuma movimentação encontrada no período.</td></tr>';
        return;
    }

    tbody.innerHTML = '';

    movimentacoes.forEach(mov => {
        const rowClass = mov.status === 'Cancelado' ? 'table-secondary' :
                        mov.tipo_movimentacao === 'Entrada' ? 'table-success' : 'table-danger';

        const destino = formatarDestino(mov);
        const internoNome = mov.interno_nome_social || mov.interno_nome || '';

        const row = document.createElement('tr');
        row.className = `${rowClass} animate__animated animate__fadeIn`;

        row.innerHTML = `
            <td class="text-center text-nowrap">
                ${formatarData(mov.data_movimentacao)}
                <br><small class="text-muted">${formatarHora(mov.data_cadastro)}</small>
            </td>
            <td>
                <strong>${mov.produto_nome}</strong>
                ${mov.tipo_nome ? `<br><small class="text-muted">${mov.tipo_nome}</small>` : ''}
            </td>
            <td class="text-center">
                <span class="badge bg-${mov.tipo_movimentacao === 'Entrada' ? 'success' : 'danger'}">
                    ${mov.tipo_movimentacao}
                </span>
            </td>
            <td class="text-center">
                <strong>${mov.quantidade}</strong>
                <br><small class="text-muted">${mov.unidade_medida}</small>
            </td>
            <td>${destino}</td>
            <td title="${mov.motivo_movimentacao}">
                ${mov.motivo_movimentacao.length > 50 ? mov.motivo_movimentacao.substring(0, 50) + '...' : mov.motivo_movimentacao}
            </td>
            <td class="text-center">${mov.documento_referencia || '-'}</td>
            <td class="text-center">
                <div class="btn-group btn-group-sm" role="group">
                    ${mov.status === 'Ativo' ?
                        `<button class="btn btn-outline-danger btn-sm" onclick="cancelarMovimentacao(${mov.id})" title="Cancelar">
                            <i class="fas fa-times"></i>
                        </button>
                        <button class="btn btn-outline-primary btn-sm" onclick="editarMovimentacao(${mov.id})" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>` :
                        '<span class="text-muted small">Cancelada</span>'
                    }
                </div>
            </td>
        `;

        tbody.appendChild(row);
    });
}

function formatarData(data) {
    return new Date(data).toLocaleDateString('pt-BR');
}

function formatarHora(data) {
    return new Date(data).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
}

function formatarDestino(mov) {
    switch(mov.tipo_destino_origem) {
        case 'Interno':
            const nomeInterno = mov.interno_nome_social || mov.interno_nome || '';
            return `${mov.interno_ipen} - ${nomeInterno}`;
        case 'Funcionario':
            return mov.id_funcionario || 'Funcionário';
        case 'Fornecedor':
            return mov.fornecedor_nome || 'Fornecedor';
        default:
            return mov.destino_origem_outro || '-';
    }
}

function editarMovimentacao(id) {
    // Implementar edição de movimentação
    alert('Função de edição ainda não implementada. ID: ' + id);
}

function cancelarMovimentacao(id) {
    if (!confirm('Tem certeza que deseja cancelar esta movimentação? Esta ação não pode ser desfeita.')) {
        return;
    }

    fetch('includes/censura_estoque_controle_logica.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `db_action=cancelar_movimentacao&id=${id}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Recarregar tabela sem loading animation
            carregarTabelaMovimentacoes();
            atualizarContadores(data.contadores);
        } else {
            alert('Erro ao cancelar movimentação: ' + (data.error || 'Erro desconhecido'));
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro de conexão');
    });
}

// Funções de filtros atualizadas para usar AJAX
function aplicarFiltros() {
    carregarTabelaMovimentacoes();
}

function limparFiltros() {
    const form = document.getElementById('formFiltros');
    if (form) {
        form.reset();
        carregarTabelaMovimentacoes();
    }
}

function atualizarContadores(contadores) {
    if (contadores) {
        // Atualizar badges de estatísticas se existirem
        const produtosAtivosEl = document.getElementById('produtosAtivosCount');
        const produtosAlertaEl = document.getElementById('produtosAlertaCount');
        const produtosCriticosEl = document.getElementById('produtosCriticosCount');

        if (produtosAtivosEl) produtosAtivosEl.textContent = contadores.produtos_ativos || 0;
        if (produtosAlertaEl) produtosAlertaEl.textContent = contadores.produtos_alerta || 0;
        if (produtosCriticosEl) produtosCriticosEl.textContent = contadores.produtos_criticos || 0;
    }
}

function alternarTema() {
    const temaAtual = document.documentElement.getAttribute('data-theme') || 'light';
    const novoTema = temaAtual === 'light' ? 'dark' : 'light';

    document.documentElement.setAttribute('data-theme', novoTema);
    localStorage.setItem('tema-sigep', novoTema);

    // Feedback visual
    mostrarNotificacaoTema(novoTema);
}

function adicionarBotaoTema() {
    // Verificar se já existe
    if (document.getElementById('btn-tema')) return;

    // Criar botão
    const btnTema = document.createElement('button');
    btnTema.id = 'btn-tema';
    btnTema.className = 'btn btn-outline-secondary btn-sm';
    btnTema.innerHTML = '<i class="fas fa-moon"></i>';
    btnTema.title = 'Alternar Tema';
    btnTema.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        transition: all 0.3s ease;
    `;

    btnTema.addEventListener('click', alternarTema);
    btnTema.addEventListener('mouseenter', function() {
        this.style.transform = 'scale(1.1)';
        this.style.boxShadow = '0 6px 20px rgba(0,0,0,0.25)';
    });
    btnTema.addEventListener('mouseleave', function() {
        this.style.transform = 'scale(1)';
        this.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
    });

    // Atualizar ícone baseado no tema atual
    atualizarIconeTema(btnTema);

    document.body.appendChild(btnTema);
}

function atualizarIconeTema(btn) {
    const temaAtual = document.documentElement.getAttribute('data-theme') || 'light';
    btn.innerHTML = temaAtual === 'light' ?
        '<i class="fas fa-moon"></i>' :
        '<i class="fas fa-sun"></i>';
}

function mostrarNotificacaoTema(tema) {
    // Criar notificação temporária
    const notificacao = document.createElement('div');
    notificacao.style.cssText = `
        position: fixed;
        top: 80px;
        right: 20px;
        background: ${tema === 'dark' ? '#007bff' : '#28a745'};
        color: white;
        padding: 10px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 10000;
        font-weight: 600;
        animation: slideInRight 0.3s ease-out;
    `;

    notificacao.innerHTML = `<i class="fas fa-${tema === 'dark' ? 'moon' : 'sun'}"></i> Tema ${tema === 'dark' ? 'Escuro' : 'Claro'} ativado`;

    document.body.appendChild(notificacao);

    setTimeout(() => {
        notificacao.style.animation = 'fadeOut 0.3s ease-out';
        setTimeout(() => notificacao.remove(), 300);
    }, 2000);
}

// Atualizar botão quando tema muda
const observer = new MutationObserver(function(mutations) {
    mutations.forEach(function(mutation) {
        if (mutation.type === 'attributes' && mutation.attributeName === 'data-theme') {
            const btn = document.getElementById('btn-tema');
            if (btn) atualizarIconeTema(btn);
        }
    });
});

observer.observe(document.documentElement, {
    attributes: true,
    attributeFilter: ['data-theme']
});

// Exportar funções para uso global
window.estoqueFunctions = {
    exibirOffcanvas,
    fecharOffcanvas,
    novoProduto,
    editarProduto,
    salvarProduto,
    novoFornecedor,
    salvarFornecedor,
    novaMovimentacao,
    salvarMovimentacao,
    cancelarMovimentacao,
    atualizarCamposDestino,
    buscarInterno,
    atualizarSaldoProduto,
    aplicarFiltros,
    limparFiltros,
    gerarRelatorio
};
