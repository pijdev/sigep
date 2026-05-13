// assets/js/internos_doacao_eletronicos.js

// Funções de aprovação (escopo global - fora de qualquer bloco condicional)
async function aprovarDoacao(idDoacao) {
    if (!confirm('Deseja aprovar esta doação? Os itens serão transferidos para o receptor.')) {
        return;
    }

    try {
        const res = await fetch('paginas/internos_doacao_eletronicos_logica.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({acao: 'aprovar_doacao', id_doacao: idDoacao})
        });

        const json = await res.json();
        if (json.status === 'success') {
            alert('Doação aprovada com sucesso!');
            location.reload();
        } else {
            alert('Erro: ' + json.msg);
        }
    } catch (err) {
        console.error('Erro ao aprovar doação:', err);
        alert('Falha na comunicação');
    }
}

async function cancelarDoacao(idDoacao) {
    const motivo = prompt('Motivo do cancelamento:');
    if (!motivo) return;

    try {
        const res = await fetch('paginas/internos_doacao_eletronicos_logica.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({acao: 'cancelar_doacao', id_doacao: idDoacao, motivo: motivo})
        });

        const json = await res.json();
        if (json.status === 'success') {
            alert('Doação cancelada com sucesso!');
            location.reload();
        } else {
            alert('Erro: ' + json.msg);
        }
    } catch (err) {
        console.error('Erro ao cancelar doação:', err);
        alert('Falha na comunicação');
    }
}

function imprimirTermo(idDoacao) {
    const url = '/termo-doacao-eletronicos/' + idDoacao;
    console.log('Abrindo termo de doação:', url);
    window.open(url, '_blank');
}

async function verDetalhesDoacao(idDoacao) {
    try {
        const res = await fetch('paginas/internos_doacao_eletronicos_logica.php?acao=ver_detalhes_doacao&id_doacao=' + idDoacao);
        const json = await res.json();

        if (json.status === 'success') {
            const doacao = json.doacao;

            let html = `
                <div class="detalhes-doacao">
                    <h6>Doação #${doacao.id}</h6>

                    <div class="row">
                        <div class="col-6">
                            <strong>Doador:</strong> ${doacao.id_doador} - ${doacao.nome_doador}<br>
                            <small>Galeria ${doacao.galeria_doador} - Bloco ${doacao.bloco_doador} - Cela ${doacao.cela_doador}</small>
                        </div>
                        <div class="col-6">
                            <strong>Receptor:</strong> `;

            if (doacao.tipo_receptor === 'CELA') {
                html += `Cela Galeria ${doacao.galeria_receptor} - Bloco ${doacao.bloco_receptor} - Cela ${doacao.cela_receptor}`;
            } else {
                html += `${doacao.id_receptor} - ${doacao.nome_receptor}`;
            }

            html += `
                        </div>
                    </div>

                    <div class="mt-3">
                        <strong>Data:</strong> ${new Date(doacao.data_doacao).toLocaleString('pt-BR')}<br>
                        <strong>Cadastrado por:</strong> ${doacao.usuario_cadastro}<br>
                        <strong>Status:</strong> ${doacao.status || 'Pendente'}
                    </div>

                    <div class="mt-3">
                        <strong>Itens doados:</strong>
                        <ul>`;

            if (json.itens && json.itens.length > 0) {
                json.itens.forEach(item => {
                    html += `<li>${item.tipo_item} - ${item.marca_modelo || 'Sem marca'} (${item.cor || 'Sem cor'})</li>`;
                });
            } else {
                html += `<li>Nenhum item encontrado</li>`;
            }

            html += `
                        </ul>
                    </div>

                    <div class="mt-3">
                        <strong>Histórico:</strong>
                        <div class="mt-2">`;

            if (json.historico && json.historico.length > 0) {
                json.historico.forEach(hist => {
                    html += `
                        <div class="historico-item">
                            <strong>${hist.acao}</strong><br>
                            ${hist.detalhes}<br>
                            <small>${hist.usuario} - ${new Date(hist.data_hora).toLocaleString('pt-BR')}</small>
                        </div>`;
                });
            } else {
                html += `<div class="historico-item">Nenhum histórico encontrado</div>`;
            }

            html += `
                        </div>
                    </div>
                </div>`;

            // Mostrar em um modal simples
            const modal = document.createElement('div');
            modal.className = 'modal fade';
            modal.innerHTML = `
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Detalhes da Doação</h5>
                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                        </div>
                        <div class="modal-body">
                            ${html}
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
                        </div>
                    </div>
                </div>`;

            document.body.appendChild(modal);
            const modalInstance = new bootstrap.Modal(modal);
            modalInstance.show();

            // Remover modal do DOM quando fechado
            modal.addEventListener('hidden.bs.modal', () => {
                document.body.removeChild(modal);
            });

        } else {
            alert('Erro: ' + json.msg);
        }
    } catch (err) {
        console.error('Erro ao carregar detalhes:', err);
        alert('Falha na comunicação');
    }
}

// Verificar se doacaoAtual já foi declarado para evitar conflitos
if (typeof doacaoAtual === 'undefined') {
    var doacaoAtual = {
        id_doador: null,
        dados_doador: null,
        tipo_receptor: null,
        dados_receptor: null,
        itens_selecionados: []
    };
}

// Função principal para abrir o offcanvas de doação
function abrirOffcanvasDoacao() {
    resetarDoacao();
    document.getElementById('offcanvasDoacao').style.transform = 'translateX(0)';
    document.body.style.overflow = 'hidden';

    // Adicionar overlay
    const overlay = document.createElement('div');
    overlay.id = 'doacaoOverlay';
    overlay.style.cssText = 'position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1059;';
    overlay.onclick = fecharOffcanvasDoacao;
    document.body.appendChild(overlay);
}

// Função para fechar o offcanvas
function fecharOffcanvasDoacao() {
    document.getElementById('offcanvasDoacao').style.transform = 'translateX(100%)';
    document.body.style.overflow = 'auto';

    const overlay = document.getElementById('doacaoOverlay');
    if (overlay) overlay.remove();

    resetarDoacao();
}

// Resetar dados da doação
function resetarDoacao() {
    doacaoAtual = {
        id_doador: null,
        dados_doador: null,
        tipo_receptor: null,
        dados_receptor: null,
        itens_selecionados: []
    };

    // Resetar steps
    document.querySelectorAll('.step').forEach(step => step.classList.remove('active', 'completed'));
    document.getElementById('step1').classList.add('active');

    // Esconder todos os passos
    document.querySelectorAll('.passo-content').forEach(passo => passo.style.display = 'none');
    document.getElementById('passo1').style.display = 'block';

    // Limpar formulários
    document.getElementById('buscaDoador').value = '';
    document.getElementById('sugestoesDoador').innerHTML = '';
    document.getElementById('doadorSelecionado').style.display = 'none';
    document.getElementById('formReceptorCela').style.display = 'none';
    document.getElementById('formReceptorInterno').style.display = 'none';
    document.getElementById('listaItensDoador').innerHTML = '';
    document.getElementById('itensSelecionados').style.display = 'none';
    document.getElementById('resumoDoacao').innerHTML = '';
    document.getElementById('termoContainer').style.display = 'none';

    // Desmarcar checkboxes
    document.getElementById('confirmacaoIrrevogavel').checked = false;
    document.getElementById('confirmacaoMonitor').checked = false;
    document.getElementById('btnFinalizarDoacao').disabled = true;
}

// Função auxiliar para fazer requisições com credenciais
async function fetchComSessao(url, options = {}) {
    const defaultOptions = {
        credentials: 'include', // Incluir cookies
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    };

    // Se options já tem headers, fazer merge sem sobrescrever
    if (options.headers) {
        defaultOptions.headers = {...defaultOptions.headers, ...options.headers};
    }

    return fetch(url, {...defaultOptions, ...options});
}

// Buscar interno para doador
async function buscarInternoDoador() {
    const val = document.getElementById('buscaDoador').value.trim();
    if(val.length < 3) {
        document.getElementById('sugestoesDoador').style.display = 'none';
        return;
    }

    try {
        const res = await fetchComSessao('paginas/internos_doacao_eletronicos_logica.php?acao=buscar_interno_doador&termo=' + encodeURIComponent(val));
        const json = await res.json();

        if(json.status === 'success' && json.dados) {
            let html = '';
            json.dados.forEach(function(i) {
                const nome = i.nome_social || i.nome;
                const ipen = i.ipen;
                const galeria = i.galeria;
                const bloco = i.bloco;
                const cela = i.res;
                html += '<div class="search-item" onclick="selecionarDoador(\'' + ipen + '\', \'' + nome.replace(/'/g, '\\\'') + '\', \'' + galeria + '\', \'' + bloco + '\', \'' + cela + '\')">' +
                    '<strong>' + ipen + '</strong> - ' + nome + '<br>' +
                    '<small>Galeria ' + galeria + ' - Bloco ' + bloco + ' - Cela ' + cela + '</small>' +
                    '</div>';
            });
            document.getElementById('sugestoesDoador').innerHTML = html;
            document.getElementById('sugestoesDoador').style.display = 'block';
        }
    } catch(err) {
        console.error('Erro na busca:', err);
    }
}

// Selecionar doador
function selecionarDoador(ipen, nome, galeria, bloco, cela) {
    doacaoAtual.id_doador = ipen;
    doacaoAtual.dados_doador = {ipen, nome, galeria, bloco, cela};

    document.getElementById('infoDoador').innerHTML = `
        <strong>${ipen}</strong> - ${nome}<br>
        <small class="text-muted">Galeria ${galeria} - Bloco ${bloco} - Cela ${cela}</small>
    `;
    document.getElementById('sugestoesDoador').style.display = 'none';
    document.getElementById('doadorSelecionado').style.display = 'block';
}

// Navegação entre passos
function proximoPasso(passo) {
    document.querySelectorAll('.passo-content').forEach(p => p.style.display = 'none');
    document.getElementById('passo' + passo).style.display = 'block';

    // Atualizar indicador de passo
    document.querySelectorAll('.step').forEach((step, index) => {
        const stepNum = index + 1;
        step.classList.remove('active', 'completed');
        if (stepNum < passo) {
            step.classList.add('completed');
        } else if (stepNum === passo) {
            step.classList.add('active');
        }
    });
}

function voltarPasso(passo) {
    proximoPasso(passo);
}

// Selecionar tipo de receptor
function selecionarTipoReceptor(tipo) {
    doacaoAtual.tipo_receptor = tipo;

    document.querySelectorAll('.card-doacao').forEach(card => card.classList.remove('selected'));
    event.currentTarget.classList.add('selected');

    if (tipo === 'CELA') {
        document.getElementById('formReceptorCela').style.display = 'block';
        document.getElementById('formReceptorInterno').style.display = 'none';
    } else {
        document.getElementById('formReceptorInterno').style.display = 'block';
        document.getElementById('formReceptorCela').style.display = 'none';
    }
}

// Carregar blocos para receptor
async function carregarBlocosReceptor() {
    const galeria = document.getElementById('galeriaReceptor').value;
    if (!galeria) return;

    try {
        const res = await fetchComSessao('paginas/internos_doacao_eletronicos_logica.php?acao=carregar_blocos_receptor&galeria=' + galeria);
        const json = await res.json();

        if (json.status === 'success') {
            let options = '<option value="">Bloco</option>';
            json.blocos.forEach(bloco => {
                options += `<option value="${bloco}">${bloco}</option>`;
            });
            document.getElementById('blocoReceptor').innerHTML = options;
        }
    } catch(err) {
        console.error('Erro ao carregar blocos:', err);
    }
}

// Carregar celas para receptor
async function carregarCelasReceptor() {
    const galeria = document.getElementById('galeriaReceptor').value;
    const bloco = document.getElementById('blocoReceptor').value;
    if (!galeria || !bloco) return;

    try {
        const res = await fetchComSessao('paginas/internos_doacao_eletronicos_logica.php?acao=carregar_celas_receptor&galeria=' + galeria + '&bloco=' + bloco);
        const json = await res.json();

        if (json.status === 'success') {
            let options = '<option value="">Cela</option>';
            json.celas.forEach(cela => {
                options += `<option value="${cela}">${cela}</option>`;
            });
            document.getElementById('celaReceptor').innerHTML = options;
        }
    } catch(err) {
        console.error('Erro ao carregar celas:', err);
    }
}

// Validar receptor cela
function validarReceptorCela() {
    const galeria = document.getElementById('galeriaReceptor').value;
    const bloco = document.getElementById('blocoReceptor').value;
    const cela = document.getElementById('celaReceptor').value;

    if (!galeria || !bloco || !cela) {
        alert('Selecione galeria, bloco e cela.');
        return;
    }

    doacaoAtual.dados_receptor = {galeria, bloco, cela};
    carregarItensDoador();
    proximoPasso(3);
}

// Buscar interno receptor
async function buscarInternoReceptor() {
    const val = document.getElementById('buscaReceptorInterno').value.trim();
    if(val.length < 3) {
        document.getElementById('sugestoesReceptor').style.display = 'none';
        return;
    }

    try {
        const res = await fetchComSessao('paginas/internos_doacao_eletronicos_logica.php?acao=buscar_interno_receptor&termo=' + encodeURIComponent(val) + '&id_doador=' + doacaoAtual.id_doador);
        const json = await res.json();

        if(json.status === 'success' && json.dados) {
            let html = '';
            json.dados.forEach(function(i) {
                const nome = i.nome_social || i.nome;
                const ipen = i.ipen;
                const galeria = i.galeria;
                const bloco = i.bloco;
                const cela = i.res;
                html += '<div class="search-item" onclick="selecionarReceptorInterno(\'' + ipen + '\', \'' + nome.replace(/'/g, '\\\'') + '\', \'' + galeria + '\', \'' + bloco + '\', \'' + cela + '\')">' +
                    '<strong>' + ipen + '</strong> - ' + nome + '<br>' +
                    '<small>Galeria ' + galeria + ' - Bloco ' + bloco + ' - Cela ' + cela + '</small>' +
                    '</div>';
            });
            document.getElementById('sugestoesReceptor').innerHTML = html;
            document.getElementById('sugestoesReceptor').style.display = 'block';
        }
    } catch(err) {
        console.error('Erro na busca:', err);
    }
}

// Selecionar receptor interno
function selecionarReceptorInterno(ipen, nome, galeria, bloco, cela) {
    doacaoAtual.dados_receptor = {ipen, nome, galeria, bloco, cela};
    carregarItensDoador();
    proximoPasso(3);
}

// Carregar itens do doador
async function carregarItensDoador() {
    document.getElementById('listaItensDoador').innerHTML = `
        <div class="text-center">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
            <p>Carregando itens do doador...</p>
        </div>
    `;

    try {
        const res = await fetchComSessao('paginas/internos_doacao_eletronicos_logica.php?acao=carregar_itens_doador&id_doador=' + doacaoAtual.id_doador);
        const json = await res.json();

        if (json.status === 'success') {
            if (json.itens.length === 0) {
                document.getElementById('listaItensDoador').innerHTML = `
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        O doador não possui itens disponíveis para doação (todos já foram retirados).
                    </div>
                `;
                return;
            }

            let html = '<p>Selecione os itens que deseja doar:</p>';
            let temItensJaDoados = false;

            json.itens.forEach(item => {
                const itemKey = item.id;
                const jaDoado = item.ja_doado == 1;
                if (jaDoado) temItensJaDoados = true;

                const classeExtra = jaDoado ? 'border-warning bg-light' : '';
                const avisoJaDoado = jaDoado ? '<br><small class="text-warning"><i class="fas fa-exclamation-triangle"></i> Item já foi doado anteriormente</small>' : '';

                html += `
                    <div class="item-card ${classeExtra}" onclick="selecionarItem('${itemKey}', ${item.id})">
                        <div class="d-flex justify-content-between">
                            <div>
                                <strong>${item.tipo_item}</strong> - ${item.marca_modelo || 'Sem marca'}<br>
                                <small>Cor: ${item.cor || 'Não informado'} | Estado: ${item.estado_conservacao || 'Não informado'}</small><br>
                                <small class="text-muted">NF: ${item.nota_fiscal || 'Não informado'}</small>
                                ${avisoJaDoado}
                            </div>
                            <div>
                                <input type="checkbox" class="chk-item" id="chk_${itemKey}" style="display: none;">
                            </div>
                        </div>
                    </div>
                `;
            });

            if (temItensJaDoados) {
                html = `
                    <div class="doacao-warning mb-3">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-exclamation-triangle alert-icon"></i>
                            <div>
                                <strong>ATENÇÃO:</strong> Alguns itens marcados em amarelo já foram doados anteriormente.
                                A doação de itens já doados é uma ação excepcional e será registrada no histórico.
                            </div>
                        </div>
                    </div>
                ` + html;
            }

            document.getElementById('listaItensDoador').innerHTML = html;
        } else {
            document.getElementById('listaItensDoador').innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> Erro ao carregar itens: ${json.msg}
                </div>
            `;
        }
    } catch(err) {
        console.error('Erro ao carregar itens:', err);
        document.getElementById('listaItensDoador').innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> Erro de comunicação.
            </div>
        `;
    }
}

// Selecionar item
function selecionarItem(itemKey, itemId) {
    const card = event.currentTarget;
    const checkbox = document.getElementById('chk_' + itemKey);

    if (card.classList.contains('selected')) {
        card.classList.remove('selected');
        checkbox.checked = false;
        doacaoAtual.itens_selecionados = doacaoAtual.itens_selecionados.filter(id => id !== itemId);
    } else {
        card.classList.add('selected');
        checkbox.checked = true;
        doacaoAtual.itens_selecionados.push(itemId);
    }

    atualizarContadorItens();
}

// Atualizar contador de itens selecionados
function atualizarContadorItens() {
    const selecionados = doacaoAtual.itens_selecionados.length;

    if (selecionados > 0) {
        let html = '<p><strong>Itens selecionados:</strong></p><ul>';
        // Aqui poderia adicionar detalhes dos itens, mas por simplicidade mostramos apenas contagem
        html += `<li>${selecionados} item(s) selecionado(s)</li>`;
        html += '</ul>';
        html += '<button type="button" class="btn btn-primary" onclick="confirmarItens()">Confirmar Seleção <i class="fas fa-arrow-right"></i></button>';

        document.getElementById('resumoItensSelecionados').innerHTML = html;
        document.getElementById('itensSelecionados').style.display = 'block';
    } else {
        document.getElementById('itensSelecionados').style.display = 'none';
    }
}

// Confirmar itens e ir para passo 4
function confirmarItens() {
    if (doacaoAtual.itens_selecionados.length === 0) {
        alert('Selecione pelo menos um item.');
        return;
    }

    prepararResumoDoacao();
    proximoPasso(4);
}

// Preparar resumo da doação
function prepararResumoDoacao() {
    let html = `
        <div class="resumo-doacao">
            <h6><i class="fas fa-hand-holding-heart"></i> Resumo da Doação</h6>

            <div class="row">
                <div class="col-6">
                    <strong>Doador:</strong><br>
                    ${doacaoAtual.dados_doador.ipen} - ${doacaoAtual.dados_doador.nome}<br>
                    <small>Galeria ${doacaoAtual.dados_doador.galeria} - Bloco ${doacaoAtual.dados_doador.bloco} - Cela ${doacaoAtual.dados_doador.cela}</small>
                </div>
                <div class="col-6">
                    <strong>Receptor:</strong><br>
    `;

    if (doacaoAtual.tipo_receptor === 'CELA') {
        html += `Cela da Galeria ${doacaoAtual.dados_receptor.galeria} - Bloco ${doacaoAtual.dados_receptor.bloco} - Cela ${doacaoAtual.dados_receptor.cela}`;
    } else {
        html += `${doacaoAtual.dados_receptor.ipen} - ${doacaoAtual.dados_receptor.nome}<br>
                <small>Galeria ${doacaoAtual.dados_receptor.galeria} - Bloco ${doacaoAtual.dados_receptor.bloco} - Cela ${doacaoAtual.dados_receptor.cela}</small>`;
    }

    html += `
                </div>
            </div>

            <div class="mt-3">
                <strong>Itens a doar: ${doacaoAtual.itens_selecionados.length}</strong>
            </div>
        </div>
    `;

    document.getElementById('resumoDoacao').innerHTML = html;
    document.getElementById('termoContainer').style.display = 'block';

    // Verificar confirmações
    verificarConfirmacoes();
}

// Verificar confirmações
function verificarConfirmacoes() {
    const irrevogavel = document.getElementById('confirmacaoIrrevogavel').checked;
    const monitor = document.getElementById('confirmacaoMonitor').checked;

    console.log('verificarConfirmacoes - irrevogavel:', irrevogavel, 'monitor:', monitor);
    console.log('btnFinalizarDoacao element:', document.getElementById('btnFinalizarDoacao'));

    document.getElementById('btnFinalizarDoacao').disabled = !(irrevogavel && monitor);
    console.log('btnFinalizarDoacao.disabled:', document.getElementById('btnFinalizarDoacao').disabled);
}

// Finalizar doação
async function finalizarDoacao() {
    console.log('finalizarDoacao() chamada');
    console.log('doacaoAtual:', doacaoAtual);

    if (!document.getElementById('confirmacaoIrrevogavel').checked || !document.getElementById('confirmacaoMonitor').checked) {
        alert('Confirme todas as declarações antes de prosseguir.');
        return;
    }

    if (!confirm('ATENÇÃO: Esta doação é IRREVOGÁVEL. Deseja realmente prosseguir?')) {
        return;
    }

    // Verificar se há itens já doados selecionados
    let temItensJaDoados = false;
    doacaoAtual.itens_selecionados.forEach(itemId => {
        const itemCard = document.querySelector(`[onclick*="selecionarItem"][onclick*="${itemId}"]`);
        if (itemCard && itemCard.classList.contains('bg-light')) {
            temItensJaDoados = true;
        }
    });

    // Se há itens já doados, exigir confirmação especial
    if (temItensJaDoados) {
        const confirmacaoEspecial = prompt(
            'ATENÇÃO CRÍTICA: Você está tentando doar itens que JÁ FORAM DOADOS anteriormente.\n\n' +
            'Esta é uma ação excepcional da unidade prisional.\n\n' +
            'Digite "CONFIRMO" (em maiúsculo) para prosseguir, ou cancele a operação:'
        );

        if (confirmacaoEspecial !== 'CONFIRMO') {
            alert('Operação cancelada. Doação não realizada.');
            return;
        }
    }

    // Preparar dados
    const dados = {
        id_doador: doacaoAtual.id_doador,
        tipo_receptor: doacaoAtual.tipo_receptor,
        itens_ids: doacaoAtual.itens_selecionados,
        confirmacao_ja_doados: temItensJaDoados
    };

    if (doacaoAtual.tipo_receptor === 'CELA') {
        dados.galeria_receptor = doacaoAtual.dados_receptor.galeria;
        dados.bloco_receptor = doacaoAtual.dados_receptor.bloco;
        dados.cela_receptor = doacaoAtual.dados_receptor.cela;
    } else {
        dados.id_receptor = parseInt(doacaoAtual.dados_receptor.ipen);
    }

    try {
        console.log('Enviando requisição para processar_doacao...');
        console.log('URL: paginas/internos_doacao_eletronicos_logica.php');
        console.log('Método: POST');
        console.log('Headers:', {'Content-Type': 'application/json'});
        console.log('Dados:', dados);

        const res = await fetchComSessao('paginas/internos_doacao_eletronicos_logica.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({acao: 'processar_doacao', ...dados})
        });

        console.log('Resposta recebida:', res);
        console.log('Status:', res.status);
        console.log('Headers:', [...res.headers.entries()]);
        console.log('Status Text:', res.statusText);

        const responseText = await res.text();
        console.log('Response text bruto:', responseText);

        // Verificar se response está vazia
        if (!responseText || responseText.trim() === '') {
            console.error('ERRO: Resposta vazia do servidor');
            throw new Error('Resposta vazia do servidor');
        }

        let json;
        try {
            json = JSON.parse(responseText);
        } catch (e) {
            console.error('Erro ao fazer parse do JSON:', e);
            console.error('Response text que falhou:', responseText);
            throw new Error('Resposta inválida do servidor: ' + responseText.substring(0, 200));
        }

        console.log('JSON parseado:', json);

        if (json.status === 'success') {
            alert(json.msg);

            // Assinar termo automaticamente
            await assinarTermo(json.id_doacao);

            // Fechar offcanvas e recarregar página
            fecharOffcanvasDoacao();
            location.reload();
        } else {
            alert('Erro: ' + json.msg);
        }
    } catch(err) {
        console.error('Erro ao processar doação:', err);
        alert('Erro de comunicação.');
    }
}

// Assinar termo
async function assinarTermo(idDoacao) {
    try {
        const res = await fetchComSessao('paginas/internos_doacao_eletronicos_logica.php', {
            method: 'POST',
            body: new URLSearchParams({acao: 'assinar_termo', id_doacao: idDoacao})
        });

        const json = await res.json();
        if (json.status !== 'success') {
            console.warn('Erro ao assinar termo:', json.msg);
        }
    } catch(err) {
        console.error('Erro ao assinar termo:', err);
    }
}

// Funções do Modal de Histórico
function abrirModalHistorico() {
    $('#modalHistorico').modal('show');
    carregarHistoricoCompleto();
}

async function carregarHistoricoCompleto() {
    try {
        const formData = new FormData(document.getElementById('formFiltroHistorico'));
        const data = Object.fromEntries(formData.entries());

        // Adicionar parâmetros específicos
        data.acao = 'buscar_historico_completo';
        data.acao_filtro = data.hist_acao || '';
        data.id_doacao = data.hist_id_doacao || '';
        data.id_item = data.hist_id_item || '';
        data.usuario = data.hist_usuario || '';
        data.data_inicio = data.hist_data_inicio || '';
        data.data_fim = data.hist_data_fim || '';

        const response = await fetch('paginas/internos_doacao_eletronicos_logica.php', {
            method: 'POST',
            body: JSON.stringify(data),
            headers: {
                'Content-Type': 'application/json'
            }
        });

        const result = await response.json();

        if (result.status === 'success') {
            renderizarTabelaHistorico(result.data);
        } else {
            console.error('Erro ao carregar histórico:', result.msg);
            $('#corpoTabelaHistorico').html(`
                <tr>
                    <td colspan="9" class="text-center text-danger">
                        <i class="fas fa-exclamation-triangle"></i> Erro: ${result.msg}
                    </td>
                </tr>
            `);
        }
    } catch (error) {
        console.error('Erro na requisição:', error);
        $('#corpoTabelaHistorico').html(`
            <tr>
                <td colspan="9" class="text-center text-danger">
                    <i class="fas fa-exclamation-triangle"></i> Falha na comunicação
                </td>
            </tr>
        `);
    }
}

function renderizarTabelaHistorico(dados) {
    if (!dados || dados.length === 0) {
        $('#corpoTabelaHistorico').html(`
            <tr>
                <td colspan="9" class="text-center text-muted">
                    <i class="fas fa-info-circle"></i> Nenhum registro encontrado
                </td>
            </tr>
        `);
        return;
    }

    let html = '';
    dados.forEach(item => {
        const dataFormatada = new Date(item.data_hora).toLocaleString('pt-BR');
        const badgeAcao = getBadgeAcao(item.acao);
        const dePara = getDePara(item);

        html += `
            <tr>
                <td>${dataFormatada}</td>
                <td><span class="badge badge-secondary">${item.id_doacao || '-'}</span></td>
                <td><span class="badge badge-info">${item.id_item || '-'}</span></td>
                <td>${badgeAcao}</td>
                <td>${item.detalhes || '-'}</td>
                <td><small>${item.usuario}</small></td>
                <td>${item.tipo_item}${item.marca_modelo ? ' - ' + item.marca_modelo : ''}</td>
                <td>${dePara.de}</td>
                <td>${dePara.para}</td>
            </tr>
        `;
    });

    $('#corpoTabelaHistorico').html(html);
}

function getBadgeAcao(acao) {
    const badges = {
        'DOACAO_CRIADA': '<span class="badge badge-primary">Doação Criada</span>',
        'DOACAO_APROVADA': '<span class="badge badge-success">Doação Aprovada</span>',
        'DOACAO_CANCELADA': '<span class="badge badge-danger">Doação Cancelada</span>',
        'ITEM_DOADO': '<span class="badge badge-info">Item Doado</span>',
        'ITEM_TRANSFERIDO': '<span class="badge badge-success">Item Transferido</span>',
        'ITEM_DEVOLVIDO': '<span class="badge badge-warning">Item Devolvido</span>',
        'TERMO_ASSINADO': '<span class="badge badge-dark">Termo Assinado</span>'
    };
    return badges[acao] || `<span class="badge badge-secondary">${acao}</span>`;
}

function getDePara(item) {
    if (item.acao === 'ITEM_DOADO') {
        return {
            de: `<small class="text-muted">IPEN ${item.id_doador}</small>`,
            para: '<small class="text-info">Doação</small>'
        };
    } else if (item.acao === 'ITEM_TRANSFERIDO') {
        return {
            de: `<small class="text-muted">IPEN ${item.id_doador}</small>`,
            para: `<small class="text-success">${item.origem_destino}</small>`
        };
    } else if (item.acao === 'ITEM_DEVOLVIDO') {
        return {
            de: '<small class="text-info">Doação</small>',
            para: `<small class="text-warning">IPEN ${item.id_doador}</small>`
        };
    } else {
        return {
            de: '-',
            para: '-'
        };
    }
}

function filtrarHistorico() {
    carregarHistoricoCompleto();
}

function exportarHistorico() {
    // Obter dados da tabela
    const table = document.getElementById('tabelaHistorico');
    const rows = table.getElementsByTagName('tr');

    let csv = 'Data/Hora,ID Doacao,ID Item,Acao,Detalhes,Usuario,Tipo Item,De,Para\n';

    for (let i = 1; i < rows.length; i++) {
        const cols = rows[i].getElementsByTagName('td');
        if (cols.length === 9) {
            const rowData = [
                cols[0].textContent.replace(/,/g, ';'),
                cols[1].textContent.replace(/,/g, ';'),
                cols[2].textContent.replace(/,/g, ';'),
                cols[3].textContent.replace(/,/g, ';'),
                cols[4].textContent.replace(/,/g, ';'),
                cols[5].textContent.replace(/,/g, ';'),
                cols[6].textContent.replace(/,/g, ';'),
                cols[7].textContent.replace(/,/g, ';'),
                cols[8].textContent.replace(/,/g, ';')
            ];
            csv += rowData.join(',') + '\n';
        }
    }

    // Download do CSV
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', `historico_doacoes_${new Date().toISOString().slice(0,10)}.csv`);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Inicialização
document.addEventListener('DOMContentLoaded', function() {
    // Adicionar listeners para checkboxes de confirmação
    document.getElementById('confirmacaoIrrevogavel').addEventListener('change', verificarConfirmacoes);
    document.getElementById('confirmacaoMonitor').addEventListener('change', verificarConfirmacoes);

    // Verificar se há parâmetro doador na URL para pré-selecionar
    const urlParams = new URLSearchParams(window.location.search);
    const doadorParam = urlParams.get('doador');
    if (doadorParam) {
        // Buscar dados do doador e pré-selecionar
        buscarDoadorPorParametro(doadorParam);
    }
});

// Função auxiliar para buscar doador por parâmetro da URL
async function buscarDoadorPorParametro(ipen) {
    try {
        const res = await fetchComSessao('paginas/internos_doacao_eletronicos_logica.php?acao=buscar_interno_doador&termo=' + encodeURIComponent(ipen));
        const json = await res.json();

        if(json.status === 'success' && json.dados && json.dados.length > 0) {
            const doador = json.dados[0];
            selecionarDoador(doador.ipen, doador.nome_social || doador.nome, doador.galeria, doador.bloco, doador.res);
        }
    } catch(err) {
        console.error('Erro ao buscar doador por parâmetro:', err);
    }
}

// Função selInterno que pode estar sendo chamada (compatibilidade)
function selInterno(ipen, nome) {
    // Esta função pode estar sendo chamada de algum lugar
    // Redirecionar para a função correta
    selecionarDoador(ipen, nome, '', '', '');
}

// Função para abrir doação a partir da gestão de eletrônicos
function abrirDoacaoFromGestao(ipen, nome) {
    // Abrir o offcanvas de doação
    abrirOffcanvasDoacao();

    // Pré-preencher o doador com os dados fornecidos
    // Buscar dados completos do interno primeiro
    buscarDadosDoadorParaGestao(ipen, nome);
}

// Buscar dados completos do doador quando chamado da gestão
async function buscarDadosDoadorParaGestao(ipen, nome) {
    try {
        const res = await fetchComSessao('paginas/internos_doacao_eletronicos_logica.php?acao=buscar_interno_doador&termo=' + encodeURIComponent(ipen));
        const json = await res.json();

        if(json.status === 'success' && json.dados && json.dados.length > 0) {
            const doador = json.dados[0];
            selecionarDoador(
                doador.ipen,
                doador.nome_social || doador.nome,
                doador.galeria,
                doador.bloco,
                doador.res
            );
        } else {
            // Se não encontrar pelo IPEN, tenta pelo nome
            const resNome = await fetchComSessao('paginas/internos_doacao_eletronicos_logica.php?acao=buscar_interno_doador&termo=' + encodeURIComponent(nome));
            const jsonNome = await resNome.json();

            if(jsonNome.status === 'success' && jsonNome.dados && jsonNome.dados.length > 0) {
                const doador = jsonNome.dados[0];
                selecionarDoador(
                    doador.ipen,
                    doador.nome_social || doador.nome,
                    doador.galeria,
                    doador.bloco,
                    doador.res
                );
            } else {
                // Se não encontrar, mostra erro
                document.getElementById('buscaDoador').value = nome;
                alert('Interno não encontrado. Você pode buscar manualmente.');
            }
        }
    } catch(err) {
        console.error('Erro ao buscar dados do doador:', err);
        document.getElementById('buscaDoador').value = nome;
        alert('Erro ao buscar dados do interno. Você pode buscar manualmente.');
    }
}
