let contadorItens = 1;
let anexosParaUpload = [];

// Carregar MDs ao iniciar
$(document).ready(function() {
    carregarMDs();
    
    // Setar datas padrão
    const hoje = new Date().toISOString().split('T')[0];
    $('input[name="data_inicio"]').val(hoje);
    
    const dataFim = new Date();
    dataFim.setDate(dataFim.getDate() + 7); // 7 dias padrão
    $('input[name="data_fim"]').val(dataFim.toISOString().split('T')[0]);
});

function carregarMDs() {
    const params = $('#formFiltro').serialize();
    
    fetch('modulos/coordenacao/medidas_disciplinares/md_logica.php?action=listar_mds&' + params)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderizarMDs(data.data);
            } else {
                $('#listaMDs').html('<div class="alert alert-danger">Erro ao carregar dados.</div>');
            }
        })
        .catch(error => {
            $('#listaMDs').html('<div class="alert alert-danger">Erro de conexão.</div>');
        });
}

function renderizarMDs(mds) {
    if (mds.length === 0) {
        $('#listaMDs').html('<div class="text-center p-4 text-muted">Nenhuma medida disciplinar encontrada.</div>');
        return;
    }
    
    let html = '<div class="row">';
    
    mds.forEach(md => {
        const nomeExib = md.nome_social ? 
            `<strong>${md.nome_social}</strong><br><small class="text-muted">(${md.nome})</small>` : 
            `<strong>${md.nome}</strong>`;
        
        const localInterno = `${md.galeria}${md.bloco}-${md.res}`;
        const statusClass = md.status === 'Ativa' ? 'md-status-ativa' : 'md-status-concluida';
        const statusBadge = md.status === 'Ativa' ? 
            '<span class="badge badge-success">Ativa</span>' : 
            '<span class="badge badge-secondary">Concluída</span>';
        
        const dataFim = new Date(md.data_fim);
        const hoje = new Date();
        const diasRestantes = Math.ceil((dataFim - hoje) / (1000 * 60 * 60 * 24));
        const estaVencendo = md.status === 'Ativa' && diasRestantes <= 2;
        const vencimentoClass = estaVencendo ? 'md-pulse' : '';
        
        html += `
            <div class="col-lg-6 mb-3">
                <div class="card md-card ${statusClass} ${vencimentoClass}">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h6 class="card-title mb-1">
                                    <i class="fas fa-user mr-2"></i>${nomeExib}
                                </h6>
                                <small class="text-muted">
                                    <i class="fas fa-id-badge mr-1"></i>${md.id_interno} | 
                                    <i class="fas fa-map-marker-alt mr-1"></i>${localInterno}
                                </small>
                            </div>
                            <div class="text-right">
                                ${statusBadge}
                                ${estaVencendo ? '<br><small class="text-danger"><i class="fas fa-exclamation-triangle"></i> Vence em ' + diasRestantes + ' dias</small>' : ''}
                            </div>
                        </div>
                        
                        <div class="row text-sm mb-2">
                            <div class="col-6">
                                <strong>Início:</strong> ${formatDate(md.data_inicio)}
                            </div>
                            <div class="col-6">
                                <strong>Fim:</strong> ${formatDate(md.data_fim)}
                            </div>
                        </div>
                        
                        <div class="mb-2">
                            <strong>Motivo:</strong><br>
                            <small>${md.motivo.substring(0, 100)}${md.motivo.length > 100 ? '...' : ''}</small>
                        </div>
                        
                        <div class="mb-2">
                            <strong>Local:</strong> ${md.local_castigo}
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                <i class="fas fa-user mr-1"></i>${md.usuario_cadastro} em ${formatDateTime(md.data_cadastro)}
                            </small>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" onclick="verDetalhes(${md.id})">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-outline-warning" onclick="editarMD(${md.id})">
                                    <i class="fas fa-edit"></i>
                                </button>
                                ${md.status === 'Ativa' ? `
                                    <button class="btn btn-outline-success" onclick="concluirMD(${md.id})">
                                        <i class="fas fa-check"></i>
                                    </button>
                                ` : ''}
                                ${md.status === 'Concluida' ? `
                                    <button class="btn btn-outline-danger" onclick="excluirMD(${md.id})">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
    
    html += '</div>';
    $('#listaMDs').html(html);
}

function buscarInterno() {
    const ipen = $('#ipen_interno').val().trim();
    
    if (!ipen) {
        alert('Digite o IPEN do interno.');
        return;
    }
    
    fetch('modulos/coordenacao/medidas_disciplinares/md_logica.php?action=buscar_interno', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'ipen=' + encodeURIComponent(ipen)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const interno = data.interno;
            const nomeExib = interno.nome_social ? 
                `${interno.nome_social} (${interno.nome})` : interno.nome;
            
            $('#dadosInterno').removeClass('d-none').html(`
                <strong>Nome:</strong> ${nomeExib}<br>
                <strong>Local:</strong> ${interno.galeria}${interno.bloco}-${interno.res}
            `);
            
            // Carregar itens apreendidos existentes
            carregarItensApreendidos(data.itens_apreendidos || []);
        } else {
            $('#dadosInterno').addClass('d-none');
            alert(data.error || 'Interno não encontrado.');
        }
    })
    .catch(error => {
        alert('Erro ao buscar interno.');
        console.error(error);
    });
}

function carregarItensApreendidos(itens) {
    let html = '';
    
    if (itens.length > 0) {
        html = '<div class="alert alert-info mb-3"><strong>Itens já apreendidos (vinculados ao interno ou à cela):</strong></div>';
        
        itens.forEach((item, index) => {
            html += `
                <div class="item-apreendido row mb-2 border p-2 bg-light">
                    <div class="col-md-3">
                        <input type="text" class="form-control" value="${item.tipo_item}" readonly>
                        <input type="hidden" name="itens_apreendidos[${index}][tipo_item]" value="${item.tipo_item}">
                    </div>
                    <div class="col-md-3">
                        <input type="text" class="form-control" name="itens_apreendidos[${index}][observacoes]" 
                               placeholder="Observações (danificado, etc.)" value="${item.observacoes || ''}">
                    </div>
                    <div class="col-md-2">
                        <input type="text" class="form-control" name="itens_apreendidos[${index}][quem_recolheu]" 
                               placeholder="Quem recolheu" value="${item.quem_recolheu || ''}">
                    </div>
                    <div class="col-md-2">
                        <select class="form-control" name="itens_apreendidos[${index}][local_retido]">
                            <option value="Coordenação" ${item.local_retido === 'Coordenação' ? 'selected' : ''}>Coordenação</option>
                            <option value="Censura" ${item.local_retido === 'Censura' ? 'selected' : ''}>Censura</option>
                            <option value="Direção" ${item.local_retido === 'Direção' ? 'selected' : ''}>Direção</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <small class="text-muted">Marca: ${item.marca || 'N/A'}</small><br>
                        <small class="text-muted">Modelo: ${item.modelo || 'N/A'}</small>
                    </div>
                </div>
            `;
        });
        
        contadorItens = itens.length;
    } else {
        html = '<div class="alert alert-warning mb-3"><i class="fas fa-info-circle mr-2"></i><strong>Nenhum item apreendido encontrado</strong><br><small class="text-muted">Não há itens vinculados a este interno ou à cela onde ele está.</small></div>';
    }
    
    $('#itensApreendidos').html(html);
    contadorItens++;
}

function adicionarItem() {
    const html = `
        <div class="item-apreendido row mb-2">
            <div class="col-md-3">
                <select class="form-control" name="itens_apreendidos[${contadorItens}][tipo_item]">
                    <option value="">Selecione...</option>
                    <option value="TV">TV</option>
                    <option value="Radio">Rádio</option>
                    <option value="Ventilador">Ventilador</option>
                    <option value="Maquina Cabelo">Máquina de Cabelo</option>
                    <option value="Chaleira">Chaleira</option>
                    <option value="Outro">Outro</option>
                </select>
            </div>
            <div class="col-md-2">
                <input type="text" class="form-control" name="itens_apreendidos[${contadorItens}][marca]" placeholder="Marca">
            </div>
            <div class="col-md-2">
                <input type="text" class="form-control" name="itens_apreendidos[${contadorItens}][modelo]" placeholder="Modelo">
            </div>
            <div class="col-md-2">
                <select class="form-control" name="itens_apreendidos[${contadorItens}][local_retido]">
                    <option value="Coordenação">Coordenação</option>
                    <option value="Censura">Censura</option>
                    <option value="Direção">Direção</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="button" class="btn btn-sm btn-danger" onclick="removerItem(this)">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `;
    
    $('#itensApreendidos').append(html);
    contadorItens++;
}

function removerItem(botao) {
    $(botao).closest('.item-apreendido').remove();
}

// Upload de anexos
$('#anexoMD').change(function() {
    const arquivo = this.files[0];
    if (!arquivo) return;
    
    // Validar arquivo
    const tiposPermitidos = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    const tamanhoMaximo = 5 * 1024 * 1024; // 5MB
    
    if (!tiposPermitidos.includes(arquivo.type)) {
        alert('Tipo de arquivo não permitido.');
        $(this).val('');
        return;
    }
    
    if (arquivo.size > tamanhoMaximo) {
        alert('Arquivo muito grande (máximo 5MB).');
        $(this).val('');
        return;
    }
    
    anexosParaUpload.push(arquivo);
    
    const html = `
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <i class="fas fa-paperclip mr-2"></i>
            <strong>${arquivo.name}</strong> (${formatFileSize(arquivo.size)})
            <button type="button" class="close" onclick="removerAnexo(this, '${arquivo.name}')">
                <span>&times;</span>
            </button>
        </div>
    `;
    
    $('#listaAnexos').append(html);
    $(this).val('');
});

function removerAnexo(btn, nomeArquivo) {
    anexosParaUpload = anexosParaUpload.filter(a => a.name !== nomeArquivo);
    $(btn).closest('.alert').remove();
}

function salvarMD() {
    const form = document.getElementById('formNovaMD');
    const formData = new FormData(form);
    
    // Validar formulário
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    // Adicionar action
    formData.append('action', 'salvar_md');
    
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Salvando...';
    btn.disabled = true;
    
    fetch('modulos/coordenacao/medidas_disciplinares/md_logica.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Fazer upload dos anexos
            if (anexosParaUpload.length > 0) {
                uploadAnexos(data.id_medida);
            } else {
                finalizarSalvarMD();
            }
        } else {
            alert('Erro: ' + data.error);
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    })
    .catch(error => {
        alert('Erro de conexão.');
        btn.innerHTML = originalText;
        btn.disabled = false;
        console.error(error);
    });
}

function uploadAnexos(idMedida) {
    const promises = anexosParaUpload.map(arquivo => {
        const formData = new FormData();
        formData.append('action', 'upload_anexo');
        formData.append('id_medida', idMedida);
        formData.append('arquivo', arquivo);
        
        return fetch('modulos/coordenacao/medidas_disciplinares/md_logica.php', {
            method: 'POST',
            body: formData
        });
    });
    
    Promise.all(promises)
        .then(() => {
            finalizarSalvarMD();
        })
        .catch(error => {
            console.error('Erro no upload:', error);
            finalizarSalvarMD(); // Mesmo com erro no upload, a MD foi salva
        });
}

function finalizarSalvarMD() {
    $('#modalNovaMD').modal('hide');
    alert('Medida disciplinar salva com sucesso!');
    
    // Limpar formulário
    document.getElementById('formNovaMD').reset();
    $('#dadosInterno').addClass('d-none');
    $('#listaAnexos').empty();
    anexosParaUpload = [];
    
    // Recarregar lista
    carregarMDs();
}

function concluirMD(id) {
    if (!confirm('Deseja concluir esta medida disciplinar?')) return;
    
    fetch('modulos/coordenacao/medidas_disciplinares/md_logica.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=concluir_md&id_medida=' + id
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            carregarMDs();
        } else {
            alert('Erro: ' + data.error);
        }
    })
    .catch(error => {
        alert('Erro ao concluir MD.');
        console.error(error);
    });
}

function editarMD(id) {
    fetch('modulos/coordenacao/medidas_disciplinares/md_logica.php?action=buscar_md&id=' + id)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const md = data.md;
                const itens = data.itens || [];
                
                // Preencher formulário
                $('#id_medida').val(md.id);
                $('#ipen_interno').val(md.id_interno);
                $('input[name="data_inicio"]').val(md.data_inicio);
                $('input[name="data_fim"]').val(md.data_fim);
                $('textarea[name="motivo"]').val(md.motivo);
                $('textarea[name="observacoes"]').val(md.observacoes);
                $('select[name="local_castigo"]').val(md.local_castigo);
                
                // Buscar dados do interno
                buscarInterno();
                
                // Carregar itens existentes
                setTimeout(() => {
                    carregarItensApreendidos(itens);
                }, 500);
                
                // Mudar título do modal
                $('#modalNovaMD .modal-title').html('<i class="fas fa-edit"></i> Editar Medida Disciplinar');
                
                // Mudar botão de salvar
                $('#modalNovaMD .modal-footer .btn-primary').attr('onclick', 'atualizarMD()').html('<i class="fas fa-save mr-2"></i>Atualizar Medida Disciplinar');
                
                // Abrir modal
                $('#modalNovaMD').modal('show');
            } else {
                alert('Erro: ' + data.error);
            }
        })
        .catch(error => {
            alert('Erro ao buscar MD.');
            console.error(error);
        });
}

function atualizarMD() {
    const form = document.getElementById('formNovaMD');
    const formData = new FormData(form);
    formData.append('action', 'editar_md');
    
    fetch('modulos/coordenacao/medidas_disciplinares/md_logica.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            $('#modalNovaMD').modal('hide');
            carregarMDs();
            alert('Medida disciplinar atualizada com sucesso!');
        } else {
            alert('Erro: ' + data.error);
        }
    })
    .catch(error => {
        alert('Erro ao atualizar MD.');
        console.error(error);
    });
}

function excluirMD(id) {
    if (!confirm('Deseja excluir esta medida disciplinar? Esta ação não poderá ser desfeita.')) return;
    
    fetch('modulos/coordenacao/medidas_disciplinares/md_logica.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=excluir_md&id_medida=' + id
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            carregarMDs();
            alert('Medida disciplinar excluída com sucesso!');
        } else {
            alert('Erro: ' + data.error);
        }
    })
    .catch(error => {
        alert('Erro ao excluir MD.');
        console.error(error);
    });
}

function verDetalhes(id) {
    // TODO: Implementar modal de detalhes
    alert('Funcionalidade em desenvolvimento.');
}

function limparFiltros() {
    $('#formFiltro')[0].reset();
    carregarMDs();
}

// Funções utilitárias
function formatDate(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleDateString('pt-BR');
}

function formatDateTime(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleDateString('pt-BR') + ' ' + date.toLocaleTimeString('pt-BR', {hour: '2-digit', minute:'2-digit'});
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}
