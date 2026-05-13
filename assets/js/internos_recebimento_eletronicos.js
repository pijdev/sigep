window.pageTitle = 'Entrada de Eletrônicos';

window.currentPage = 'internos_recebimento_eletronicos.php';



// Função para obter parâmetros atuais da URL

function getCurrentParams() {

    const urlParams = new URLSearchParams(window.location.search);

    return {

        page: urlParams.get('page') || '1',

        per_page: urlParams.get('per_page') || '20',

        sort_by: urlParams.get('sort_by') || 'data_entrada',

        sort_order: urlParams.get('sort_order') || 'DESC'

    };

}



window.safeReload = function() {

    const params = getCurrentParams();

    const url = 'paginas/internos_recebimento_eletronicos.php?' +

        'page=' + params.page +

        '&per_page=' + params.per_page +

        '&sort_by=' + params.sort_by +

        '&sort_order=' + params.sort_order;

    loadPage(url);

}



// Função de ordenação

window.sortTable = function(column) {

    const params = getCurrentParams();

    let newOrder = 'ASC';



    // Se já está ordenando por esta coluna, inverte a ordem

    if (params.sort_by === column) {

        newOrder = params.sort_order === 'ASC' ? 'DESC' : 'ASC';

    }



    const url = 'paginas/internos_recebimento_eletronicos.php?' +

        'page=1' + // Volta para página 1 ao ordenar

        '&per_page=' + params.per_page +

        '&sort_by=' + column +

        '&sort_order=' + newOrder;

    loadPage(url);

}



// Função para mudar itens por página

window.changePerPage = function(perPage) {

    const params = getCurrentParams();

    const url = 'paginas/internos_recebimento_eletronicos.php?' +

        'page=1' + // Volta para página 1 ao mudar itens por página

        '&per_page=' + params.per_page +

        '&sort_by=' + params.sort_by +

        '&sort_order=' + params.sort_order;

    loadPage(url);

}



// Função para ir para página específica

window.goToPage = function(page) {

    const params = getCurrentParams();

    const url = 'paginas/internos_recebimento_eletronicos.php?' +

        'page=' + page +

        '&per_page=' + params.per_page +

        '&sort_by=' + params.sort_by +

        '&sort_order=' + params.sort_order;

    loadPage(url);

}



// Funções do Offcanvas de Itens

window.abrirOffcanvasItens = function() {

    document.getElementById('offcanvasBackdropItens').style.display = 'block';

    document.getElementById('offcanvasItens').style.transform = 'translateX(0)';

    $('body').addClass('offcanvas-active');

    atualizarContadorItens();

}



window.fecharOffcanvasItens = function() {

    document.getElementById('offcanvasBackdropItens').style.display = 'none';

    document.getElementById('offcanvasItens').style.transform = 'translateX(100%)';

    $('body').removeClass('offcanvas-active');

}



window.toggleDetalhes = function(key) {

    const detalhes = $('#detalhes_' + key.replace(/\s+/g, ''));

    const isVisible = detalhes.is(':visible');



    // Ocultar todos os detalhes primeiro

    $('.detalhes-item').hide();

    $('.btn-detalhes').hide();



    if (!isVisible) {

        detalhes.show();

        // Encontrar o botão usando o card atual

        $('.chk-item-offcanvas[data-item="' + key + '"]').closest('.card').find('.btn-detalhes').show();

    }

}



window.atualizarContadorItens = function() {

    const selecionados = $('.chk-item-offcanvas:checked').length;

    $('#contadorItens').text(selecionados + ' itens selecionados');

}



window.limparSelecao = function() {

    $('.chk-item-offcanvas').prop('checked', false);

    $('.detalhes-item').hide();

    $('.btn-detalhes').hide();

    // Limpar campos de detalhes

    $('.detalhes-item input, .detalhes-item select').val('');

    atualizarContadorItens();

}



window.aplicarSelecao = function() {

    const itensSelecionados = [];

    let resumo = [];



    $('.chk-item-offcanvas:checked').each(function() {

        const itemKey = $(this).data('item');

        const itemData = {

            tipo: itemKey,

            nome_item: itemKey === 'Outros' ? $('#nome_item_' + itemKey).val() : '',

            descricao: itemKey === 'Outros' ? $('#descricao_' + itemKey).val() : '',

            marca: itemKey !== 'Outros' ? ($('#marca_' + itemKey).val() || $('#marca_custom_' + itemKey).val()) : '',

            cor: $('#cor_' + itemKey).val() || $('#cor_custom_' + itemKey).val() || 'Preto',

            estado: $('#estado_' + itemKey).val() || 'Novo',

            nf: $('#nf_' + itemKey).val() || '',

            // Campos específicos

            polegadas: $('#polegadas_' + itemKey).val() || '',

            tem_controle: $('#tem_controle_' + itemKey).val() || 'Não',

            tem_fonte: $('#tem_fonte_' + itemKey).val() || 'Não',

            tamanho: $('#tamanho_' + itemKey).val() || '',

            capacidade: $('#capacidade_' + itemKey).val() || '',

            comprimento: $('#comprimento_' + itemKey).val() || ''

        };

        itensSelecionados.push(itemData);



        // Criar resumo para exibição

        const label = $(this).closest('.card').find('.custom-control-label').text();

        resumo.push(label);

    });

    // Armazenar dados no campo hidden (se existir)
    const $itensSelecionados = $('#itensSelecionados');
    if($itensSelecionados.length) {
        $itensSelecionados.val(JSON.stringify(itensSelecionados));
    }

    // Atualizar resumo visual
    if (resumo.length > 0) {
        $('#resumoItens').html('<strong>Itens selecionados:</strong> ' + resumo.join(', '));
    } else {
        $('#resumoItens').html('');
    }

    // Fechar offcanvas
    fecharOffcanvasItens();
}

// Funções para carregar marcas e cores dinâmicas

window.carregarMarcasTipo = async function(tipoItem) {

    try {

        const res = await fetch('paginas/internos_recebimento_eletronicos.php?acao=carregar_marcas&tipo_item=' + encodeURIComponent(tipoItem));

        const json = await res.json();

        if(json.status === 'success') {

            return json.marcas;

        }

        return [];

    } catch(err) {

        console.error('Erro ao carregar marcas:', err);

        return [];

    }

}



window.carregarCoresTipo = async function(tipoItem) {

    try {

        const res = await fetch('paginas/internos_recebimento_eletronicos.php?acao=carregar_cores&tipo_item=' + encodeURIComponent(tipoItem));

        const json = await res.json();

        if(json.status === 'success') {

            return json.cores;

        }

        return [];

    } catch(err) {

        console.error('Erro ao carregar cores:', err);

        return [];

    }

}



// Carregar marcas e cores quando abrir detalhes

window.toggleDetalhes = async function(key) {

    const detalhes = $('#detalhes_' + key.replace(/\s+/g, ''));

    const isVisible = detalhes.is(':visible');



    // Ocultar todos os detalhes primeiro

    $('.detalhes-item').hide();

    $('.btn-detalhes').hide();



    if (!isVisible) {

        detalhes.show();

        // Encontrar o botão usando o card atual

        $('.chk-item-offcanvas[data-item="' + key + '"]').closest('.card').find('.btn-detalhes').show();



        // Carregar marcas dinâmicas se tiver campo de marca

        if($('#marca_' + key).length > 0) {

            const marcas = await carregarMarcasTipo(key);

            const select = $('#marca_' + key);

            select.html('<option value="">Selecione...</option>');

            marcas.forEach(marca => {

                select.append(`<option value="${marca}">${marca}</option>`);

            });

            select.append('<option value="OUTRA">Outra...</option>');



            // Adicionar evento de change para mostrar campo customizado

            select.off('change').on('change', function() {

                handleMarcaChange(key);

            });

        }



        // Carregar cores dinâmicas se tiver campo de cor

        if($('#cor_' + key).length > 0 && key !== 'Banqueta') {

            const cores = await carregarCoresTipo(key);

            const select = $('#cor_' + key);

            select.html('<option value="">Selecione...</option>');

            cores.forEach(cor => {

                select.append(`<option value="${cor}">${cor}</option>`);

            });

            select.append('<option value="OUTRA">Outra...</option>');



            // Adicionar evento de change para mostrar campo customizado

            select.off('change').on('change', function() {

                handleCorChange(key);

            });

        }

    }

}



window.handleMarcaChange = function(tipoItem) {

    const select = $('#marca_' + tipoItem);

    let input = $('#marca_custom_' + tipoItem);



    if(select.val() === 'OUTRA') {

        // Mostrar campo customizado com input group

        if(input.length === 0) {

            // Criar input group se não existir

            select.wrap('<div class="input-group"></div>');

            select.after(`

                <div class="input-group-append">

                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="salvarMarcaCustom('${tipoItem}')">

                        <i class="fas fa-plus"></i>

                    </button>

                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="cancelarMarcaCustom('${tipoItem}')">

                        <i class="fas fa-times"></i>

                    </button>

                </div>

            `);

            input = $('<input type="text" class="form-control form-control-sm" id="marca_custom_' + tipoItem + '" placeholder="Digite a marca..." style="display:none;">');

            select.parent('.input-group').append(input);

        }



        select.hide();

        input.show();

        input.focus();

    } else {

        if(input.length > 0) {

            input.hide();

            select.show();

        }

    }

}



window.handleCorChange = function(tipoItem) {

    const select = $('#cor_' + tipoItem);

    let input = $('#cor_custom_' + tipoItem);



    if(select.val() === 'OUTRA') {

        // Mostrar campo customizado com input group

        if(input.length === 0) {

            // Criar input group se não existir

            select.wrap('<div class="input-group"></div>');

            select.after(`

                <div class="input-group-append">

                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="salvarCorCustom('${tipoItem}')">

                        <i class="fas fa-plus"></i>

                    </button>

                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="cancelarCorCustom('${tipoItem}')">

                        <i class="fas fa-times"></i>

                    </button>

                </div>

            `);

            input = $('<input type="text" class="form-control form-control-sm" id="cor_custom_' + tipoItem + '" placeholder="Digite a cor..." style="display:none;">');

            select.parent('.input-group').append(input);

        }



        select.hide();

        input.show();

        input.focus();

    } else {

        if(input.length > 0) {

            input.hide();

            select.show();

        }

    }

}



window.salvarMarcaCustom = async function(tipoItem) {

    const input = $('#marca_custom_' + tipoItem);

    const select = $('#marca_' + tipoItem);

    const marca = input.val().trim();



    if (!marca) {

        alert('Digite uma marca válida.');

        return;

    }



    try {

        // Salvar marca no banco

        const res = await fetch('paginas/internos_recebimento_eletronicos.php?acao=salvar_marca_custom', {

            method: 'POST',

            body: JSON.stringify({

                tipo_item: tipoItem,

                marca: marca

            }),

            headers: {

                'Content-Type': 'application/json'

            }

        });



        const json = await res.json();

        if(json.status === 'success') {

            // Recarregar marcas

            const marcas = await carregarMarcasTipo(tipoItem);

            select.html('<option value="">Selecione...</option>');

            marcas.forEach(m => {

                select.append(`<option value="${m}">${m}</option>`);

            });

            select.append('<option value="OUTRA">Outra...</option>');



            // Selecionar a marca que foi salva

            select.val(marca);

            select.show();

            input.hide();



            alert('Marca salva com sucesso!');

        } else {

            alert('Erro ao salvar marca: ' + json.msg);

        }

    } catch(err) {

        console.error('Erro ao salvar marca:', err);

        alert('Erro ao salvar marca.');

    }

}



window.cancelarMarcaCustom = function(tipoItem) {

    const select = $('#marca_' + tipoItem);

    const input = $('#marca_custom_' + tipoItem);



    input.hide();

    select.show();

    select.val('');

}



window.salvarCorCustom = async function(tipoItem) {

    const input = $('#cor_custom_' + tipoItem);

    const select = $('#cor_' + tipoItem);

    const cor = input.val().trim();



    if (!cor) {

        alert('Digite uma cor válida.');

        return;

    }



    try {

        // Salvar cor no banco

        const res = await fetch('paginas/internos_recebimento_eletronicos.php?acao=salvar_cor_custom', {

            method: 'POST',

            body: JSON.stringify({

                tipo_item: tipoItem,

                cor: cor

            }),

            headers: {

                'Content-Type': 'application/json'

            }

        });



        const json = await res.json();

        if(json.status === 'success') {

            // Recarregar cores

            const cores = await carregarCoresTipo(tipoItem);

            select.html('<option value="">Selecione...</option>');

            cores.forEach(c => {

                select.append(`<option value="${c}">${c}</option>`);

            });

            select.append('<option value="OUTRA">Outra...</option>');



            // Selecionar a cor que foi salva

            select.val(cor);

            select.show();

            input.hide();



            alert('Cor salva com sucesso!');

        } else {

            alert('Erro ao salvar cor: ' + json.msg);

        }

    } catch(err) {

        console.error('Erro ao salvar cor:', err);

        alert('Erro ao salvar cor.');

    }

}



window.cancelarCorCustom = function(tipoItem) {

    const select = $('#cor_' + tipoItem);

    const input = $('#cor_custom_' + tipoItem);



    input.hide();

    select.show();

    select.val('');

}



// Atualizar contador quando checkbox é alterado

$(document).on('change', '.chk-item-offcanvas', function() {

    const itemKey = $(this).data('item');

    const isChecked = $(this).is(':checked');



    // Usar o próprio checkbox como referência em vez do ID

    const $btn = $(this).closest('.card').find('.btn-detalhes');



    if (isChecked) {

        $btn.show();

    } else {

        $btn.hide();

        $('#detalhes_' + itemKey.replace(/\s+/g, '')).hide();

    }



    atualizarContadorItens();

});



// Fechar offcanvas ao clicar no backdrop

$(document).on('click', '#offcanvasBackdropItens', function() {

    fecharOffcanvasItens();

});



// Buscar interno (com botão)

window.buscarInterno = async function() {

    const val = $('#buscaInput').val().trim();

    if(val.length < 3) {

        $('#sugestoes').hide().html('');

        return;

    }



    try {

        const res = await fetch('paginas/internos_recebimento_eletronicos.php?acao=buscar_interno&termo='+encodeURIComponent(val));

        const json = await res.json();

        let html = '';

        if(json.status === 'success' && json.dados) {

            json.dados.forEach(i => {

                const nome = i.nome_social || i.nome;

                // Determinar visualização baseada no tipo de dono
                let detalhes = '';
                let tipoIcon = '';

                if (i.tipo_dono === 'dono') {
                    tipoIcon = '🏢'; // ícone de prédio para setores
                    detalhes = `<small class="text-info">${i.tipo.toUpperCase()} - ${i.descricao || 'Setor'}</small>`;
                } else {
                    tipoIcon = '👤'; // ícone de pessoa para internos
                    detalhes = `<small>${i.galeria}-${i.bloco}-${i.res}</small>`;
                }

                html += `<div class="search-item" onclick="selInterno('${i.ipen}', '${nome}', '${i.tipo_dono}')">
                         <span class="mr-1">${tipoIcon}</span>
                         <b>${i.ipen}</b> - ${nome}<br>
                         ${detalhes}
                         </div>`;

            });

            $('#sugestoes').html(html).show();

        } else {

            $('#sugestoes').html('<div class="search-item text-muted">Nenhum interno encontrado</div>').show();

        }

    } catch(err) {

        console.error('Erro na busca:', err);

        $('#sugestoes').html('<div class="search-item text-danger">Erro na busca</div>').show();

    }

}



// Busca automática (opcional - pode ser removida se quiser só com botão)

if (typeof window.timerBusca === 'undefined') {

    window.timerBusca = null;

}

$('#buscaInput').on('input', function() {

    let val = $(this).val();

    if(val.length < 3) { $('#sugestoes').hide(); return; }

    clearTimeout(window.timerBusca);

    window.timerBusca = setTimeout(() => {

        // Comentado para usar apenas botão: buscarInterno();

    }, 400);

});



window.selInterno = function(ipen, nome, tipo_dono = 'interno') {

    $('#hiddenIpen').val(ipen);
    $('#nomeSelecionado').val(ipen + ' - ' + nome);

    // Guardar tipo de dono para uso no registro
    $('#hiddenTipoDono').val(tipo_dono);

    // Mostrar tipo de dono selecionado
    let tipoTexto = tipo_dono === 'dono' ? '🏢 Setor' : '👤 Interno';
    $('#tipoDonoSelecionado').html(`<small class="text-muted">${tipoTexto}</small>`).show();

    $('#sugestoes').hide(); $('#buscaInput').val('');

}



window.salvarLote = async function(e) {

    e.preventDefault();

    if(!$('#hiddenIpen').val()) return alert('Selecione o interno.');

    // Verificar se há itens selecionados (verificação direta nos checkboxes)
    const checkboxesSelecionados = $('.chk-item-offcanvas:checked');
    const itensSelecionadosVal = $('#itensSelecionados').val() || '';
    const temItensSelecionados = checkboxesSelecionados.length > 0 || itensSelecionadosVal.trim() !== '';

    if(!temItensSelecionados) return alert('Selecione um item.');

    // Se não há dados no campo hidden mas há checkboxes, coletar os dados agora
    if(checkboxesSelecionados.length > 0 && itensSelecionadosVal.trim() === '') {
        const itensParaSalvar = [];
        checkboxesSelecionados.each(function() {
            const itemKey = $(this).data('item');
            const itemData = {
                tipo: itemKey,
                nome_item: itemKey === 'Outros' ? $('#nome_item_' + itemKey).val() : '',
                descricao: itemKey === 'Outros' ? $('#descricao_' + itemKey).val() : '',
                marca: itemKey !== 'Outros' ? ($('#marca_' + itemKey).val() || $('#marca_custom_' + itemKey).val()) : '',
                cor: $('#cor_' + itemKey).val() || $('#cor_custom_' + itemKey).val() || 'Preto',
                estado: $('#estado_' + itemKey).val() || 'Novo',
                nf: $('#nf_' + itemKey).val() || '',
                // Campos específicos
                polegadas: $('#polegadas_' + itemKey).val() || '',
                tem_controle: $('#tem_controle_' + itemKey).val() || 'Não',
                tem_fonte: $('#tem_fonte_' + itemKey).val() || 'Não',
                tamanho: $('#tamanho_' + itemKey).val() || '',
                capacidade: $('#capacidade_' + itemKey).val() || '',
                comprimento: $('#comprimento_' + itemKey).val() || ''
            };
            itensParaSalvar.push(itemData);
        });

        // Salvar no campo hidden
        $('#itensSelecionados').val(JSON.stringify(itensParaSalvar));
    }

    if(!confirm('Confirmar entrada na CENSURA?')) return;


    const fd = new FormData(e.target);

    try {

        const res = await fetch('paginas/internos_recebimento_eletronicos.php', {method:'POST', body:fd});

        const json = await res.json();

        if(json.status === 'success') {

            alert(json.msg);

            if(confirm('Imprimir Recibo para Visitante?')) {

                window.open('paginas/imprimir_recibo_eletronicos.php?ids='+json.ids, '_blank');

            }

            // Limpar formulário após sucesso

            $('#formEletro')[0].reset();

            $('#hiddenIpen').val('');

            $('#nomeSelecionado').val('');

            const $itensSelecionados = $('#itensSelecionados');
            if($itensSelecionados.length) {
                $itensSelecionados.val('');
            }

            $('#resumoItens').html('');

            $('.item-card').removeClass('selected');

            $('.inputs-item').hide().css({opacity: 0.5, pointerEvents: 'none'});

            $('.inputs-item input, .inputs-item select').prop('disabled', true);

            window.safeReload();

        } else {

            // Mensagem amigável para erros de período da portaria

            if(json.msg && json.msg.includes('portaria')) {

                alert('⚠️ ' + json.msg);

            } else {

                alert('Erro: ' + json.msg);

            }

        }

    } catch(err) {

        alert('Erro de conexão. Tente novamente.');

    }

}



window.imprimirSelecionados = async function(tipo) {

    let ids = [], jaNaCela = [];

    $('.chk-print:checked').each(function() {

        ids.push($(this).val());

        if($(this).data('situacao') == 'Na Cela') jaNaCela.push(1);

    });

    if(!ids.length) return alert('Selecione registros.');

    // Recibo Visita (Apenas imprime)

    if(tipo === 'recibo') return window.open('paginas/imprimir_recibo_eletronicos.php?ids='+ids.join(','), '_blank');

    // Termo Interno (Move para Cela)

    if(jaNaCela.length > 0) {

        if(!confirm('Alguns itens JÁ ESTÃO na cela. Deseja apenas reimprimir o termo?')) return;

        // Se já está na cela, só imprime

        window.open('paginas/imprimir_termo_eletronicos.php?ids='+ids.join(','), '_blank');

    } else {

        // Se está no estoque, tenta mover

        if(!confirm('CONFIRMAR ENTREGA NA CELA?\nIsso moverá o item do Estoque para o Interno e gerará o termo.')) return;



        const fd = new FormData(); fd.append('acao','marcar_entregue'); fd.append('ids', ids.join(','));

        const res = await fetch('paginas/internos_recebimento_eletronicos.php', {method:'POST', body:fd});

        const json = await res.json();



        if(json.status === 'success') {

            window.open('paginas/imprimir_termo_eletronicos.php?ids='+ids.join(','), '_blank');

            window.safeReload();

        } else if (json.status === 'warning') {

            alert(json.msg); // Mostra o erro de conflito

            window.safeReload(); // Recarrega para ver os que deram certo

        } else {

            alert('Erro: ' + json.msg);

        }

    }

}



window.excluir = async function(id) {

    if(!confirm('Excluir registro?')) return;

    const fd = new FormData(); fd.append('acao','excluir'); fd.append('id',id);

    const res = await fetch('paginas/internos_recebimento_eletronicos.php', {method:'POST', body:fd});

    const json = await res.json();

    if(json.status === 'success') window.safeReload();

}
