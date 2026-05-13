(function () {
  const endpoint = 'includes/censura_estoque_controle_v2_logica.php';
  const state = {
    mov: { page: 1, perPage: 20, sort: 'data_movimentacao', dir: 'desc' },
    produtoAtual: null,
    relatorioAtual: 'relatorio_resumo_periodo',
    herdarVariantes: { produtoDestino: null, produtoFonte: null },
    novaMovimentacao: { produto: null, variante: null }
  };

  function toast(msg, icon = 'info') {
    if (window.Swal) {
      Swal.fire({ toast: true, position: 'top-end', timer: 2200, showConfirmButton: false, icon, title: msg });
    } else {
      alert(msg);
    }
  }

  async function api(action, payload = {}) {
    const fd = new FormData();
    fd.append('db_action', action);
    Object.entries(payload).forEach(([k, v]) => {
      if (v !== undefined && v !== null) fd.append(k, v);
    });
    const res = await fetch(endpoint, { method: 'POST', body: fd });
    const data = await res.json();
    if (!data.success) throw new Error(data.message || 'Falha');
    return data;
  }

  function openOc(id) { document.getElementById(id)?.classList.add('show'); }
  function closeAllOc() { document.querySelectorAll('.offcanvas-v2').forEach(el => el.classList.remove('show')); }

  function fmtDate(d) {
    if (!d) return '-';
    const dt = new Date(d + 'T00:00:00');
    return dt.toLocaleDateString('pt-BR');
  }

  async function refreshKpis() {
    try {
      const r = await api('dashboard_contadores');
      const d = r.data;
      document.getElementById('kpiProdutos').textContent = d.totalProdutos;
      document.getElementById('kpiVariantes').textContent = d.totalVariantes;
      document.getElementById('kpiAlerta').textContent = d.itensAlerta;
      document.getElementById('kpiCritico').textContent = d.itensCritico;
      document.getElementById('kpiEntradasHoje').textContent = d.entradasHoje;
      document.getElementById('kpiSaidasHoje').textContent = d.saidasHoje;
    } catch (_) {}
  }

  async function carregarMovimentacoes() {
    const form = document.getElementById('filtrosMovForm');
    const formData = new FormData(form);
    const payload = Object.fromEntries(formData.entries());
    Object.assign(payload, state.mov);

    const body = document.getElementById('movTableBody');
    body.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-4">Carregando...</td></tr>';

    try {
      const r = await api('movimentacao_listar_paginado', payload);
      const rows = r.data || [];
      const meta = r.meta || { page: 1, total: 0, per_page: 20 };
      if (!rows.length) {
        body.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-4">Nenhuma movimentação encontrada</td></tr>';
      } else {
        body.innerHTML = rows.map(m => {
          const badge = m.tipo_movimentacao === 'Entrada' ? 'badge-mov-entrada' : 'badge-mov-saida';
          return `<tr>
            <td>${fmtDate(m.data_movimentacao)}</td>
            <td>${m.produto_nome || '-'}</td>
            <td>${m.cor || '-'} / ${m.tamanho || '-'}</td>
            <td><span class="badge ${badge}">${m.tipo_movimentacao}</span></td>
            <td>${m.quantidade} ${m.unidade_medida || ''}</td>
            <td>${m.destino_origem || '-'}</td>
            <td>${m.status}</td>
            <td>
              <button class="btn btn-xs btn-outline-primary" data-mov-edit="${m.id}"><i class="fas fa-edit"></i></button>
              <button class="btn btn-xs btn-outline-danger" data-mov-cancel="${m.id}"><i class="fas fa-ban"></i></button>
            </td>
          </tr>`;
        }).join('');
      }
      const ini = meta.total === 0 ? 0 : ((meta.page - 1) * meta.per_page) + 1;
      const fim = Math.min(meta.total, meta.page * meta.per_page);
      document.getElementById('movMeta').textContent = `Exibindo ${ini}-${fim} de ${meta.total}`;
      document.getElementById('movPrev').disabled = meta.page <= 1;
      document.getElementById('movNext').disabled = fim >= meta.total;
    } catch (e) {
      body.innerHTML = `<tr><td colspan="8" class="text-center text-danger py-4">${e.message}</td></tr>`;
    }
  }

  async function carregarProdutosGrid() {
    const box = document.getElementById('produtosGridV2');
    box.innerHTML = '<div class="text-muted">Carregando produtos...</div>';
    try {
      const r = await api('produto_listar');
      const data = r.data || [];
      if (!data.length) {
        box.innerHTML = '<div class="text-muted">Nenhum produto cadastrado.</div>';
        return;
      }
      box.innerHTML = `<table class="table table-sm"><thead><tr><th>Produto</th><th>Tipo</th><th>Variantes</th><th></th></tr></thead><tbody>${data.map(p => `<tr>
        <td>${p.nome}</td><td>${p.tipo_nome || '-'}</td><td>${p.total_variantes || 0}</td>
        <td>
          <button class="btn btn-xs btn-outline-primary" data-prod-open="${p.id}">Abrir</button>
          ${!p.total_variantes || p.total_variantes == 0 ? `<button class="btn btn-xs btn-outline-info ms-1" data-herdar="${p.id}" title="Herdar Variantes"><i class="fas fa-copy"></i></button>` : ''}
        </td>
      </tr>`).join('')}</tbody></table>`;
    } catch (e) {
      box.innerHTML = `<div class="text-danger">${e.message}</div>`;
    }
  }

  async function abrirProduto(id) {
    try {
      const r = await api('produto_obter', { id });
      const p = r.data.produto;
      const vars = r.data.variantes || [];
      console.log('Variantes carregadas:', vars); // Debug
      state.produtoAtual = p.id;
      document.getElementById('produto_id_v2').value = p.id;
      const pf = document.getElementById('produtoFormV2');
      pf.nome.value = p.nome || '';
      pf.descricao.value = p.descricao || '';
      pf.id_tipo.value = p.id_tipo || '';
      pf.id_fornecedor.value = p.id_fornecedor || '';
      pf.id_estoque.value = p.id_estoque || '';
      document.getElementById('variante_produto_id_v2').value = p.id;

      const vg = document.getElementById('variantesGridV2');
      const tableHTML = vars.length ? `<table class="table table-sm"><thead><tr><th>Cor</th><th>Tam</th><th>Saldo</th><th>Mín</th><th>Alerta</th><th>Ações</th></tr></thead><tbody>${vars.map(v => `<tr>
        <td>${v.cor}</td><td>${v.tamanho}</td><td>${v.quantidade_atual}</td><td>${v.quantidade_minima}</td><td>${v.quantidade_alerta}</td>
        <td>
          <button class="btn btn-xs btn-outline-primary" data-var-edit="${v.id}"><i class="fas fa-edit"></i></button>
          <button class="btn btn-xs btn-outline-danger" data-var-delete="${v.id}"><i class="fas fa-trash"></i></button>
        </td>
      </tr>`).join('')}</tbody></table>` : '<div class="text-muted">Sem variantes.</div>';

      console.log('HTML da tabela:', tableHTML); // Debug
      vg.innerHTML = tableHTML;
    } catch (e) {
      toast(e.message, 'error');
    }
  }

  async function carregarFornecedoresGrid() {
    const box = document.getElementById('fornecedoresGridV2');
    box.innerHTML = '<div class="text-muted">Carregando fornecedores...</div>';
    try {
      const r = await api('fornecedor_listar');
      const d = r.data || [];
      box.innerHTML = `<table class="table table-sm"><thead><tr><th>Nome</th><th>Contato</th><th>Status</th></tr></thead><tbody>${d.map(f => `<tr><td>${f.nome}</td><td>${f.telefone || '-'} ${f.email || ''}</td><td>${f.status}</td></tr>`).join('')}</tbody></table>`;
    } catch (e) {
      box.innerHTML = `<div class="text-danger">${e.message}</div>`;
    }
  }

  async function carregarEstoquesGrid() {
    const box = document.getElementById('estoquesGridV2');
    box.innerHTML = '<div class="text-muted">Carregando estoques...</div>';
    try {
      const r = await api('estoque_listar');
      const d = r.data || [];
      box.innerHTML = `<table class="table table-sm"><thead><tr><th>Nome</th><th>Tipo</th><th>Capacidade</th><th>Status</th></tr></thead><tbody>${d.map(e => `<tr><td>${e.nome}</td><td>${e.tipo}</td><td>${e.capacidade_maxima}</td><td>${e.status}</td></tr>`).join('')}</tbody></table>`;
    } catch (e) {
      box.innerHTML = `<div class="text-danger">${e.message}</div>`;
    }
  }

  async function carregarRelatorio(action) {
    const box = document.getElementById('relatoriosGridV2');
    const formData = new FormData(document.getElementById('relatoriosFormV2'));
    const payload = Object.fromEntries(formData.entries());
    box.innerHTML = '<div class="text-muted">Gerando relatório...</div>';
    try {
      const r = await api(action, payload);
      const rows = r.data || [];
      if (!Array.isArray(rows)) {
        box.innerHTML = `<pre class="small">${JSON.stringify(rows, null, 2)}</pre>`;
        return;
      }
      if (!rows.length) {
        box.innerHTML = '<div class="text-muted">Sem dados para o período.</div>';
        return;
      }
      const cols = Object.keys(rows[0]);
      box.innerHTML = `<div class="table-responsive"><table class="table table-sm"><thead><tr>${cols.map(c => `<th>${c}</th>`).join('')}</tr></thead><tbody>${rows.map(row => `<tr>${cols.map(c => `<td>${row[c] ?? ''}</td>`).join('')}</tr>`).join('')}</tbody></table></div>`;
    } catch (e) {
      box.innerHTML = `<div class="text-danger">${e.message}</div>`;
    }
  }

  async function abrirModalHerdarVariantes(idProdutoDestino) {
    state.herdarVariantes.produtoDestino = idProdutoDestino;
    state.herdarVariantes.produtoFonte = null;

    // Resetar modal
    document.getElementById('buscaProdutoFonte').value = '';
    document.getElementById('listaProdutosFonte').innerHTML = '<div class="text-muted">Busque produtos para selecionar...</div>';
    document.getElementById('btnConfirmarHerdar').disabled = true;

    // Abrir modal - forma compatível com diferentes versões
    const modal = document.getElementById('modalHerdarVariantes');

    // Adicionar classes necessárias para o modal
    modal.classList.add('show');
    modal.style.display = 'block';
    document.body.classList.add('modal-open');

    // Criar backdrop se não existir
    if (!document.querySelector('.modal-backdrop')) {
      const backdrop = document.createElement('div');
      backdrop.className = 'modal-backdrop fade show';
      document.body.appendChild(backdrop);
    }

    // Tentar usar Bootstrap se disponível
    if (window.bootstrap && window.bootstrap.Modal) {
      try {
        const bsModal = new window.bootstrap.Modal(modal);
        bsModal.show();
      } catch (e) {
        console.warn('Erro ao abrir modal com Bootstrap, usando fallback:', e);
      }
    }
  }

  async function buscarProdutosParaHerdar(termo) {
    if (!termo || termo.length < 2) {
      document.getElementById('listaProdutosFonte').innerHTML = '<div class="text-muted">Digite pelo menos 2 caracteres...</div>';
      return;
    }

    try {
      const r = await api('produto_listar');
      const produtos = r.data || [];
      const filtrados = produtos.filter(p =>
        p.id !== state.herdarVariantes.produtoDestino &&
        (p.total_variantes || 0) > 0 &&
        (p.nome.toLowerCase().includes(termo.toLowerCase()) ||
         (p.tipo_nome && p.tipo_nome.toLowerCase().includes(termo.toLowerCase())))
      );

      if (!filtrados.length) {
        document.getElementById('listaProdutosFonte').innerHTML = '<div class="text-muted">Nenhum produto encontrado com variantes.</div>';
        return;
      }

      document.getElementById('listaProdutosFonte').innerHTML = `<div class="list-group">${filtrados.map(p => `
        <button type="button" class="list-group-item list-group-item-action" data-produto-fonte="${p.id}">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <strong>${p.nome}</strong>
              <small class="text-muted d-block">${p.tipo_nome || 'Sem tipo'} - ${p.total_variantes} variantes</small>
            </div>
            <i class="fas fa-copy text-info"></i>
          </div>
        </button>
      `).join('')}</div>`;
    } catch (e) {
      document.getElementById('listaProdutosFonte').innerHTML = `<div class="text-danger">${e.message}</div>`;
    }
  }

  async function confirmarHerdarVariantes() {
    if (!state.herdarVariantes.produtoDestino || !state.herdarVariantes.produtoFonte) {
      toast('Selecione um produto fonte.', 'warning');
      return;
    }

    try {
      const r = await api('variante_herdar', {
        id_produto_destino: state.herdarVariantes.produtoDestino,
        id_produto_fonte: state.herdarVariantes.produtoFonte
      });

      toast(r.message, 'success');

      // Fechar modal - forma compatível e simples
      const modal = document.getElementById('modalHerdarVariantes');

      // Remover classes do modal
      modal.classList.remove('show');
      modal.style.display = 'none';
      document.body.classList.remove('modal-open');

      // Remover backdrop
      const backdrop = document.querySelector('.modal-backdrop');
      if (backdrop) {
        backdrop.remove();
      }

      // Atualizar grade de produtos e se o produto destino estiver aberto, atualizar variantes
      await carregarProdutosGrid();
      if (state.produtoAtual === state.herdarVariantes.produtoDestino) {
        await abrirProduto(state.produtoAtual);
      }
      refreshKpis();
    } catch (e) {
      toast(e.message, 'error');
    }
  }

  async function abrirOffcanvasNovaEntrada() {
    // Resetar formulário
    document.getElementById('novaEntradaForm').reset();
    document.getElementById('entrada_id_produto').value = '';
    document.getElementById('entrada_id_variante').value = '';

    // Resetar select de variantes
    const varianteSelect = document.getElementById('entrada_variante_select');
    varianteSelect.innerHTML = '<option value="">Selecione um produto primeiro</option>';
    varianteSelect.disabled = true;

    // Abrir offcanvas
    openOc('offcanvasNovaEntrada');
  }

  async function abrirOffcanvasNovaSaida() {
    // Resetar formulário
    document.getElementById('novaSaidaForm').reset();
    document.getElementById('saida_id_produto').value = '';
    document.getElementById('saida_id_variante').value = '';
    document.getElementById('saida_interno_id').value = '';

    // Resetar select de variantes
    const varianteSelect = document.getElementById('saida_variante_select');
    varianteSelect.innerHTML = '<option value="">Selecione um produto primeiro</option>';
    varianteSelect.disabled = true;

    // Resetar campos de destino
    document.getElementById('saida_destino_interno').style.display = 'none';
    document.getElementById('saida_destino_funcionario').style.display = 'none';
    document.getElementById('saida_destino_outro').style.display = 'none';
    document.getElementById('saida_interno_dados').style.display = 'none';

    // Abrir offcanvas
    openOc('offcanvasNovaSaida');
  }

  async function buscarProdutosParaEntrada(termo) {
    if (!termo || termo.length < 2) {
      return;
    }

    try {
      const r = await api('produto_listar');
      const produtos = r.data || [];
      const filtrados = produtos.filter(p =>
        p.nome.toLowerCase().includes(termo.toLowerCase())
      );

      const resultadosDiv = document.getElementById('entrada_produto_resultados');
      if (filtrados.length === 0) {
        resultadosDiv.innerHTML = '<div class="p-2 text-muted">Nenhum produto encontrado</div>';
      } else {
        resultadosDiv.innerHTML = filtrados.map(p => `
          <div class="p-2 border-bottom cursor-pointer hover-bg-light"
               data-entrada-produto-id="${p.id}"
               data-entrada-produto-nome="${p.nome}">
            <strong>${p.nome}</strong>
            <br><small class="text-muted">${p.tipo_nome || ''}</small>
          </div>
        `).join('');
      }
      resultadosDiv.style.display = 'block';
    } catch (e) {
      console.error('Erro ao buscar produtos:', e);
    }
  }

  async function buscarProdutosParaSaida(termo) {
    if (!termo || termo.length < 2) {
      return;
    }

    try {
      const r = await api('produto_listar');
      const produtos = r.data || [];
      const filtrados = produtos.filter(p =>
        p.nome.toLowerCase().includes(termo.toLowerCase())
      );

      const resultadosDiv = document.getElementById('saida_produto_resultados');
      if (filtrados.length === 0) {
        resultadosDiv.innerHTML = '<div class="p-2 text-muted">Nenhum produto encontrado</div>';
      } else {
        resultadosDiv.innerHTML = filtrados.map(p => `
          <div class="p-2 border-bottom cursor-pointer hover-bg-light"
               data-saida-produto-id="${p.id}"
               data-saida-produto-nome="${p.nome}">
            <strong>${p.nome}</strong>
            <br><small class="text-muted">${p.tipo_nome || ''}</small>
          </div>
        `).join('');
      }
      resultadosDiv.style.display = 'block';
    } catch (e) {
      console.error('Erro ao buscar produtos:', e);
    }
  }

  async function carregarVariantesDoProdutoEntrada(idProduto) {
    try {
      const r = await api('produto_obter', { id: idProduto });
      const variantes = r.data.variantes || [];

      const varianteSelect = document.getElementById('entrada_variante_select');
      varianteSelect.innerHTML = '<option value="">Selecione uma variante...</option>';

      if (variantes.length === 0) {
        varianteSelect.innerHTML = '<option value="">Este produto não possui variantes</option>';
      } else {
        variantes.forEach(v => {
          const option = document.createElement('option');
          option.value = v.id;
          option.textContent = `${v.cor} / ${v.tamanho}`;
          varianteSelect.appendChild(option);
        });
      }

      varianteSelect.disabled = false;
    } catch (e) {
      console.error('Erro ao carregar variantes:', e);
    }
  }

  async function carregarVariantesDoProdutoSaida(idProduto) {
    try {
      const r = await api('produto_obter', { id: idProduto });
      const variantes = r.data.variantes || [];

      const varianteSelect = document.getElementById('saida_variante_select');
      varianteSelect.innerHTML = '<option value="">Selecione uma variante...</option>';

      if (variantes.length === 0) {
        varianteSelect.innerHTML = '<option value="">Este produto não possui variantes</option>';
      } else {
        variantes.forEach(v => {
          const option = document.createElement('option');
          option.value = v.id;
          option.textContent = `${v.cor} / ${v.tamanho}`;
          varianteSelect.appendChild(option);
        });
      }

      varianteSelect.disabled = false;
    } catch (e) {
      console.error('Erro ao carregar variantes:', e);
    }
  }

  async function buscarInternosParaSaida(termo) {
    if (!termo || termo.length < 2) {
      return;
    }

    try {
      const r = await api('interno_buscar', { termo });
      const internos = r.data || [];

      const resultadosDiv = document.getElementById('saida_interno_resultados');
      if (internos.length === 0) {
        resultadosDiv.innerHTML = '<div class="p-2 text-muted">Nenhum interno encontrado</div>';
      } else {
        resultadosDiv.innerHTML = internos.map(i => `
          <div class="p-2 border-bottom cursor-pointer hover-bg-light"
               data-saida-interno-id="${i.id}"
               data-saida-interno-ipen="${i.ipen}"
               data-saida-interno-nome="${i.nome}"
               data-saida-interno-nome-social="${i.nome_social}">
            <strong>${i.ipen}</strong> - ${i.nome}
            ${i.nome_social ? `<br><small class="text-muted">Nome social: ${i.nome_social}</small>` : ''}
          </div>
        `).join('');
      }
      resultadosDiv.style.display = 'block';
    } catch (e) {
      console.error('Erro ao buscar internos:', e);
    }
  }

  function selecionarInternoParaSaida(interno) {
    document.getElementById('saida_interno_id').value = interno.id;
    document.getElementById('saida_interno_ipen').textContent = interno.ipen;
    document.getElementById('saida_interno_nome').textContent = interno.nome;
    document.getElementById('saida_interno_nome_social').textContent = interno.nome_social || '';
    document.getElementById('saida_interno_dados').style.display = 'block';
    document.getElementById('saida_interno_resultados').style.display = 'none';
    document.getElementById('saida_interno_busca').value = `${interno.ipen} - ${interno.nome}`;
  }

  async function abrirDetalhesCard(tipoCard) {
    const cardConfig = {
      produtos: {
        titulo: 'Produtos Ativos',
        subtitulo: 'Lista completa de produtos cadastrados',
        acao: 'card_produtos_listar',
        colunas: ['nome', 'tipo_nome', 'fornecedor_nome', 'total_variantes', 'saldo_total'],
        headers: ['Produto', 'Tipo', 'Fornecedor', 'Variantes', 'Saldo Total']
      },
      variantes: {
        titulo: 'Variantes Ativas',
        subtitulo: 'Todas as variantes com seus saldos',
        acao: 'card_variantes_listar',
        colunas: ['produto_nome', 'cor', 'tamanho', 'quantidade_atual', 'status_saldo', 'unidade_medida'],
        headers: ['Produto', 'Cor', 'Tamanho', 'Saldo Atual', 'Status', 'Unidade']
      },
      alerta: {
        titulo: 'Itens em Alerta',
        subtitulo: 'Itens com estoque baixo',
        acao: 'card_alerta_listar',
        colunas: ['produto_nome', 'cor', 'tamanho', 'quantidade_atual', 'quantidade_alerta', 'unidade_medida'],
        headers: ['Produto', 'Cor', 'Tamanho', 'Saldo', 'Alerta', 'Unidade']
      },
      critico: {
        titulo: 'Itens Críticos',
        subtitulo: 'Itens com estoque crítico',
        acao: 'card_critico_listar',
        colunas: ['produto_nome', 'cor', 'tamanho', 'quantidade_atual', 'quantidade_minima', 'unidade_medida'],
        headers: ['Produto', 'Cor', 'Tamanho', 'Saldo', 'Mínimo', 'Unidade']
      },
      entradas: {
        titulo: 'Entradas de Hoje',
        subtitulo: 'Todas as entradas registradas hoje',
        acao: 'card_entradas_hoje_listar',
        colunas: ['data_movimentacao', 'produto_nome', 'cor', 'tamanho', 'quantidade', 'fornecedor_nome'],
        headers: ['Data', 'Produto', 'Cor', 'Tamanho', 'Quantidade', 'Fornecedor']
      },
      saidas: {
        titulo: 'Saídas de Hoje',
        subtitulo: 'Todas as saídas registradas hoje',
        acao: 'card_saidas_hoje_listar',
        colunas: ['data_movimentacao', 'produto_nome', 'cor', 'tamanho', 'quantidade', 'destino_origem'],
        headers: ['Data', 'Produto', 'Cor', 'Tamanho', 'Quantidade', 'Destino']
      }
    };

    const config = cardConfig[tipoCard];
    if (!config) return;

    // Atualizar título
    document.getElementById('detalhesCardTitulo').innerHTML = `<i class="fas fa-chart-bar mr-2"></i>${config.titulo}`;
    document.getElementById('detalhesCardSubtitulo').textContent = config.subtitulo;

    // Mostrar loading
    document.getElementById('detalhesCardConteudo').innerHTML = `
      <div class="text-center text-muted py-4">
        <i class="fas fa-spinner fa-spin fa-2x mb-3"></i>
        <p>Carregando dados...</p>
      </div>
    `;

    // Abrir offcanvas
    openOc('offcanvasDetalhesCard');

    try {
      const r = await api(config.acao);
      const dados = r.data || [];

      if (!dados.length) {
        document.getElementById('detalhesCardConteudo').innerHTML = `
          <div class="text-center text-muted py-4">
            <i class="fas fa-inbox fa-2x mb-3"></i>
            <p>Nenhum registro encontrado</p>
          </div>
        `;
        return;
      }

      // Criar tabela
      let tabela = `
        <div class="table-responsive">
          <table class="table table-sm table-striped" id="tabelaDetalhesCard">
            <thead>
              <tr>
                ${config.headers.map(h => `<th>${h}</th>`).join('')}
              </tr>
            </thead>
            <tbody>
      `;

      dados.forEach(item => {
        tabela += '<tr>';
        config.colunas.forEach(coluna => {
          let valor = item[coluna] || '-';

          if (coluna.includes('data') && valor !== '-') {
            valor = fmtDate(valor);
          }

          if (coluna === 'status_saldo') {
            const badgeClass = valor === 'Crítico' ? 'danger' : valor === 'Alerta' ? 'warning' : 'success';
            valor = `<span class="badge badge-${badgeClass}">${valor}</span>`;
          }

          if (coluna.includes('quantidade') && valor !== '-') {
            valor = `${valor} ${item.unidade_medida || ''}`;
          }

          tabela += `<td>${valor}</td>`;
        });
        tabela += '</tr>';
      });

      tabela += `
            </tbody>
          </table>
        </div>
      `;

      document.getElementById('detalhesCardConteudo').innerHTML = tabela;

    } catch (e) {
      document.getElementById('detalhesCardConteudo').innerHTML = `
        <div class="text-center text-danger py-4">
          <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
          <p>Erro ao carregar dados: ${e.message}</p>
        </div>
      `;
    }
  }

  function imprimirDetalhesCard() {
    const titulo = document.getElementById('detalhesCardTitulo').textContent;
    const tabela = document.getElementById('tabelaDetalhesCard');

    if (!tabela) {
      toast('Nenhuma tabela para imprimir', 'warning');
      return;
    }

    // Criar janela de impressão
    const janelaImpressao = window.open('', '_blank');
    janelaImpressao.document.write(`
      <!DOCTYPE html>
      <html>
      <head>
        <title>${titulo}</title>
        <style>
          body { font-family: Arial, sans-serif; margin: 20px; }
          h1 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
          table { width: 100%; border-collapse: collapse; margin-top: 20px; }
          th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
          th { background-color: #f2f2f2; font-weight: bold; }
          tr:nth-child(even) { background-color: #f9f9f9; }
          .data-impressao { color: #666; font-size: 12px; margin-top: 30px; }
          @media print {
            body { margin: 10px; }
            .no-print { display: none; }
          }
        </style>
      </head>
      <body>
        <h1>${titulo}</h1>
        <p>${document.getElementById('detalhesCardSubtitulo').textContent}</p>
        ${tabela.outerHTML}
        <div class="data-impressao">Impresso em: ${new Date().toLocaleString('pt-BR')}</div>
      </body>
      </html>
    `);

    janelaImpressao.document.close();
    janelaImpressao.focus();

    // Aguardar um pouco antes de imprimir
    setTimeout(() => {
      janelaImpressao.print();
      janelaImpressao.close();
    }, 500);
  }

  async function gerarOficioEstoque() {
    const form = document.getElementById('relatoriosFormV2');
    const formData = new FormData(form);
    const dataInicio = formData.get('data_inicio');
    const dataFim = formData.get('data_fim');

    try {
      // Mostrar modal
      const modal = document.getElementById('modalOficio');
      modal.classList.add('show');
      modal.style.display = 'block';
      document.body.classList.add('modal-open');

      // Criar backdrop
      let backdrop = document.querySelector('.modal-backdrop');
      if (!backdrop) {
        backdrop = document.createElement('div');
        backdrop.className = 'modal-backdrop fade show';
        document.body.appendChild(backdrop);
      }

      const r = await api('gerar_oficio_estoque', {
        data_inicio: dataInicio,
        data_fim: dataFim
      });

      document.getElementById('oficioConteudo').innerHTML = r.data.html;

      // Salvar HTML para impressão/download
      window.oficioHTML = r.data.html;
      window.oficioMesAno = r.data.mes_ano;

    } catch (e) {
      toast('Erro ao gerar ofício: ' + e.message, 'error');
      fecharModalOficio();
    }
  }

  function fecharModalOficio() {
    const modal = document.getElementById('modalOficio');
    modal.classList.remove('show');
    modal.style.display = 'none';
    document.body.classList.remove('modal-open');

    const backdrop = document.querySelector('.modal-backdrop');
    if (backdrop) backdrop.remove();
  }

  function imprimirOficio() {
    if (!window.oficioHTML) {
      toast('Nenhum ofício gerado para imprimir', 'warning');
      return;
    }

    // Criar janela de impressão
    const janelaImpressao = window.open('', '_blank');
    janelaImpressao.document.write(window.oficioHTML);
    janelaImpressao.document.close();
    janelaImpressao.focus();

    setTimeout(() => {
      janelaImpressao.print();
      janelaImpressao.close();
    }, 500);
  }

  function baixarOficio() {
    if (!window.oficioHTML) {
      toast('Nenhum ofício gerado para download', 'warning');
      return;
    }

    const blob = new Blob([window.oficioHTML], { type: 'text/html;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `oficio_estoque_${window.oficioMesAno}.html`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);

    toast('Ofício baixado com sucesso!', 'success');
  }

  async function abrirDashboardAlmoxarifado() {
    try {
      const r = await api('dashboard_almoxarifado');
      renderizarDashboard(r.data);

      // Mostrar modal
      const modal = document.getElementById('modalDashboard');
      modal.classList.add('show');
      modal.style.display = 'block';
      document.body.classList.add('modal-open');

      // Criar backdrop
      let backdrop = document.querySelector('.modal-backdrop');
      if (!backdrop) {
        backdrop = document.createElement('div');
        backdrop.className = 'modal-backdrop fade show';
        document.body.appendChild(backdrop);
      }

      // Iniciar atualização automática
      iniciarAtualizacaoDashboard();

    } catch (e) {
      toast('Erro ao carregar dashboard: ' + e.message, 'error');
    }
  }

  function renderizarDashboard(dados) {
    const dashboardContent = document.getElementById('dashboardContent');

    const html = `
      <div class="dashboard-container">
        <!-- KPIs -->
        <div class="row mb-4">
          <div class="col-md-3">
            <div class="card bg-primary text-white">
              <div class="card-body">
                <div class="d-flex justify-content-between">
                  <div>
                    <h4 class="mb-0">${dados.kpis.total_produtos || 0}</h4>
                    <p class="mb-0">Total Produtos</p>
                  </div>
                  <div class="align-self-center">
                    <i class="fas fa-box fa-2x opacity-75"></i>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card bg-success text-white">
              <div class="card-body">
                <div class="d-flex justify-content-between">
                  <div>
                    <h4 class="mb-0">${dados.kpis.total_variantes || 0}</h4>
                    <p class="mb-0">Total Variantes</p>
                  </div>
                  <div class="align-self-center">
                    <i class="fas fa-tags fa-2x opacity-75"></i>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card bg-info text-white">
              <div class="card-body">
                <div class="d-flex justify-content-between">
                  <div>
                    <h4 class="mb-0">${dados.kpis.saldo_total || 0}</h4>
                    <p class="mb-0">Saldo Total</p>
                  </div>
                  <div class="align-self-center">
                    <i class="fas fa-warehouse fa-2x opacity-75"></i>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card bg-warning text-white">
              <div class="card-body">
                <div class="d-flex justify-content-between">
                  <div>
                    <h4 class="mb-0">${dados.kpis.itens_critico || 0}</h4>
                    <p class="mb-0">Itens Críticos</p>
                  </div>
                  <div class="align-self-center">
                    <i class="fas fa-exclamation-triangle fa-2x opacity-75"></i>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Gráficos -->
        <div class="row mb-4">
          <div class="col-md-8">
            <div class="card">
              <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-chart-line mr-2"></i>Movimentações - Últimos 30 dias</h5>
              </div>
              <div class="card-body">
                <canvas id="graficoMovimentacoes" height="100"></canvas>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="card">
              <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-chart-pie mr-2"></i>Estoque por Categoria</h5>
              </div>
              <div class="card-body">
                <canvas id="graficoCategorias" height="200"></canvas>
              </div>
            </div>
          </div>
        </div>

        <!-- Tabelas -->
        <div class="row">
          <div class="col-md-6">
            <div class="card">
              <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-fire mr-2"></i>Top 10 Produtos Mais Movimentados</h5>
              </div>
              <div class="card-body p-0">
                <div class="table-responsive">
                  <table class="table table-sm">
                    <thead>
                      <tr>
                        <th>Produto</th>
                        <th class="text-center">Movimentações</th>
                        <th class="text-center">Entradas</th>
                        <th class="text-center">Saídas</th>
                      </tr>
                    </thead>
                    <tbody>
                      ${dados.top_produtos.map(produto => `
                        <tr>
                          <td>${produto.produto_nome}</td>
                          <td class="text-center">${produto.total_movimentacoes}</td>
                          <td class="text-center text-success">${produto.total_entradas}</td>
                          <td class="text-center text-danger">${produto.total_saidas}</td>
                        </tr>
                      `).join('')}
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="card">
              <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-exclamation-circle mr-2"></i>Itens em Nível Crítico</h5>
              </div>
              <div class="card-body p-0">
                <div class="table-responsive">
                  <table class="table table-sm">
                    <thead>
                      <tr>
                        <th>Produto</th>
                        <th>Variante</th>
                        <th class="text-center">Saldo</th>
                        <th class="text-center">Alerta</th>
                        <th class="text-center">Mínimo</th>
                      </tr>
                    </thead>
                    <tbody>
                      ${dados.itens_criticos.map(item => `
                        <tr class="table-danger">
                          <td>${item.produto_nome}</td>
                          <td>${item.cor} / ${item.tamanho}</td>
                          <td class="text-center font-weight-bold">${item.quantidade_atual}</td>
                          <td class="text-center">${item.quantidade_alerta}</td>
                          <td class="text-center">${item.quantidade_minima}</td>
                        </tr>
                      `).join('')}
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <style>
        .dashboard-container {
          padding: 20px;
          background: #f8f9fa;
          min-height: 100vh;
        }
        .card {
          border: none;
          box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
          margin-bottom: 1rem;
        }
        .card-header {
          background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
          color: white;
          border-bottom: none;
        }
        .modal-fullscreen {
          max-width: 95vw;
          height: 95vh;
        }
        .modal-fullscreen .modal-content {
          height: 100%;
        }
        .modal-fullscreen .modal-body {
          height: calc(100% - 120px);
          overflow-y: auto;
        }
        .table-responsive {
          max-height: 300px;
          overflow-y: auto;
        }
      </style>

      <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    `;

    dashboardContent.innerHTML = html;

    // Renderizar gráficos
    setTimeout(() => {
      renderizarGraficoMovimentacoes(dados.movimentacoes);
      renderizarGraficoCategorias(dados.por_categoria);
    }, 100);
  }

  function renderizarGraficoMovimentacoes(movimentacoes) {
    const ctx = document.getElementById('graficoMovimentacoes');
    if (!ctx) return;

    const labels = movimentacoes.map(m => {
      const data = new Date(m.data);
      return data.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' });
    });

    new Chart(ctx, {
      type: 'line',
      data: {
        labels: labels,
        datasets: [{
          label: 'Entradas',
          data: movimentacoes.map(m => m.entradas_dia),
          borderColor: '#28a745',
          backgroundColor: 'rgba(40, 167, 69, 0.1)',
          tension: 0.4
        }, {
          label: 'Saídas',
          data: movimentacoes.map(m => m.saidas_dia),
          borderColor: '#dc3545',
          backgroundColor: 'rgba(220, 53, 69, 0.1)',
          tension: 0.4
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'top'
          }
        },
        scales: {
          y: {
            beginAtZero: true
          }
        }
      }
    });
  }

  function renderizarGraficoCategorias(categorias) {
    const ctx = document.getElementById('graficoCategorias');
    if (!ctx) return;

    new Chart(ctx, {
      type: 'doughnut',
      data: {
        labels: categorias.map(c => c.categoria || 'Sem Categoria'),
        datasets: [{
          data: categorias.map(c => c.saldo_atual),
          backgroundColor: [
            '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0',
            '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF'
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

  let dashboardInterval;
  function iniciarAtualizacaoDashboard() {
    // Atualizar a cada 30 segundos
    dashboardInterval = setInterval(async () => {
      try {
        const r = await api('dashboard_almoxarifado');

        // Atualizar apenas os KPIs
        const kpis = r.data.kpis;
        document.querySelector('.bg-primary h4').textContent = kpis.total_produtos || 0;
        document.querySelector('.bg-success h4').textContent = kpis.total_variantes || 0;
        document.querySelector('.bg-info h4').textContent = kpis.saldo_total || 0;
        document.querySelector('.bg-warning h4').textContent = kpis.itens_critico || 0;

        toast('Dashboard atualizado automaticamente', 'info');
      } catch (e) {
        console.error('Erro ao atualizar dashboard:', e);
      }
    }, 30000);
  }

  function pararAtualizacaoDashboard() {
    if (dashboardInterval) {
      clearInterval(dashboardInterval);
      dashboardInterval = null;
    }
  }

  function fecharModalDashboard() {
    const modal = document.getElementById('modalDashboard');
    modal.classList.remove('show');
    modal.style.display = 'none';
    document.body.classList.remove('modal-open');

    const backdrop = document.querySelector('.modal-backdrop');
    if (backdrop) backdrop.remove();

    pararAtualizacaoDashboard();
  }

  function atualizarDashboard() {
    abrirDashboardAlmoxarifado();
  }

  function telaCheia() {
    const modal = document.getElementById('modalDashboard');
    const dialog = modal.querySelector('.modal-dialog');

    if (dialog.classList.contains('modal-fullscreen')) {
      dialog.classList.remove('modal-fullscreen');
      dialog.classList.add('modal-xl');
    } else {
      dialog.classList.remove('modal-xl');
      dialog.classList.add('modal-fullscreen');
    }
  }

  async function buscarProdutosParaMovimentacao(termo) {
    if (!termo || termo.length < 2) {
      return;
    }

    try {
      const r = await api('produto_listar');
      const produtos = r.data || [];
      const filtrados = produtos.filter(p =>
        p.nome.toLowerCase().includes(termo.toLowerCase())
      );

      const resultadosDiv = document.getElementById('mov_produto_resultados');
      if (filtrados.length === 0) {
        resultadosDiv.innerHTML = '<div class="p-2 text-muted">Nenhum produto encontrado</div>';
      } else {
        resultadosDiv.innerHTML = filtrados.map(p => `
          <div class="p-2 border-bottom cursor-pointer hover-bg-light" data-produto-id="${p.id}" data-produto-nome="${p.nome}">
            <strong>${p.nome}</strong>
            <br><small class="text-muted">${p.tipo_nome || ''}</small>
          </div>
        `).join('');
      }
      resultadosDiv.style.display = 'block';
    } catch (e) {
      console.error('Erro ao buscar produtos:', e);
    }
  }

  async function carregarVariantesDoProduto(idProduto) {
    try {
      const r = await api('produto_obter', { id: idProduto });
      const variantes = r.data.variantes || [];

      const varianteSelect = document.getElementById('mov_variante_select');
      varianteSelect.innerHTML = '<option value="">Selecione uma variante...</option>';

      if (variantes.length === 0) {
        varianteSelect.innerHTML = '<option value="">Este produto não possui variantes</option>';
      } else {
        variantes.forEach(v => {
          const option = document.createElement('option');
          option.value = v.id;
          option.textContent = `${v.cor} / ${v.tamanho}`;
          varianteSelect.appendChild(option);
        });
      }

      varianteSelect.disabled = false;
    } catch (e) {
      console.error('Erro ao carregar variantes:', e);
    }
  }

  async function buscarInternosParaMovimentacao(termo) {
    if (!termo || termo.length < 2) {
      return;
    }

    try {
      const r = await api('interno_buscar', { termo });
      const internos = r.data || [];

      const resultadosDiv = document.getElementById('mov_interno_resultados');
      if (internos.length === 0) {
        resultadosDiv.innerHTML = '<div class="p-2 text-muted">Nenhum interno encontrado</div>';
      } else {
        resultadosDiv.innerHTML = internos.map(i => `
          <div class="p-2 border-bottom cursor-pointer hover-bg-light"
               data-interno-id="${i.id}"
               data-interno-ipen="${i.ipen}"
               data-interno-nome="${i.nome}"
               data-interno-nome-social="${i.nome_social}">
            <strong>${i.ipen}</strong> - ${i.nome}
            ${i.nome_social ? `<br><small class="text-muted">Nome social: ${i.nome_social}</small>` : ''}
          </div>
        `).join('');
      }
      resultadosDiv.style.display = 'block';
    } catch (e) {
      console.error('Erro ao buscar internos:', e);
    }
  }

  function selecionarInterno(interno) {
    document.getElementById('mov_interno_id').value = interno.id;
    document.getElementById('mov_interno_ipen').textContent = interno.ipen;
    document.getElementById('mov_interno_nome').textContent = interno.nome;
    document.getElementById('mov_interno_nome_social').textContent = interno.nome_social || '';
    document.getElementById('mov_interno_dados').style.display = 'block';
    document.getElementById('mov_interno_resultados').style.display = 'none';
    document.getElementById('mov_interno_busca').value = `${interno.ipen} - ${interno.nome}`;
  }

  async function salvarNovaEntrada() {
    const form = document.getElementById('novaEntradaForm');
    const formData = new FormData(form);
    const payload = Object.fromEntries(formData.entries());

    // Validar campos obrigatórios
    if (!payload.id_produto || !payload.id_variante || !payload.quantidade || !payload.id_fornecedor) {
      toast('Produto, variante, quantidade e fornecedor são obrigatórios.', 'warning');
      return;
    }

    try {
      await api('movimentacao_salvar', payload);
      toast('Entrada registrada com sucesso!', 'success');
      closeAllOc();
      carregarMovimentacoes();
      refreshKpis();
    } catch (e) {
      toast(e.message, 'error');
    }
  }

  async function salvarNovaSaida() {
    const form = document.getElementById('novaSaidaForm');
    const formData = new FormData(form);
    const payload = Object.fromEntries(formData.entries());

    // Validar campos obrigatórios
    if (!payload.id_produto || !payload.id_variante || !payload.quantidade || !payload.tipo_destino_origem || !payload.motivo_movimentacao) {
      toast('Produto, variante, quantidade, destino e motivo são obrigatórios.', 'warning');
      return;
    }

    // Validar destino específico
    if (payload.tipo_destino_origem === 'Interno' && !payload.id_interno) {
      toast('Selecione um interno para destino.', 'warning');
      return;
    }

    try {
      await api('movimentacao_salvar', payload);
      toast('Saída registrada com sucesso!', 'success');
      closeAllOc();
      carregarMovimentacoes();
      refreshKpis();
    } catch (e) {
      toast(e.message, 'error');
    }
  }

  async function buscarVariantesParaMovimentacao() {
    if (!state.novaMovimentacao.produto) {
      toast('Selecione um produto primeiro.', 'warning');
      return;
    }

    try {
      const r = await api('produto_obter', { id: state.novaMovimentacao.produto.id });
      const variantes = r.data.variantes || [];

      if (!variantes.length) {
        toast('Este produto não possui variantes.', 'warning');
        return;
      }

      // Criar lista de variantes para seleção
      const varianteLista = variantes.map(v =>
        `${v.cor} / ${v.tamanho} (Saldo: ${v.quantidade_atual})`
      ).join('\n');

      const selecionada = prompt(`Selecione uma variante digitando o número:\n${varianteLista}`);

      if (selecionada === null) return;

      const index = parseInt(selecionada) - 1;
      if (index >= 0 && index < variantes.length) {
        const variante = variantes[index];
        state.novaMovimentacao.variante = variante;
        document.getElementById('mov_id_variante').value = variante.id;
        document.getElementById('mov_variante_nome').value = `${variante.cor} / ${variante.tamanho}`;

        toast(`Variante selecionada: ${variante.cor} / ${variante.tamanho}`, 'success');
      } else {
        toast('Seleção inválida.', 'error');
      }
    } catch (e) {
      toast(e.message, 'error');
    }
  }

  async function salvarNovaMovimentacao() {
    const form = document.getElementById('novaMovimentacaoForm');
    const formData = new FormData(form);
    const payload = Object.fromEntries(formData.entries());

    // Validações
    if (!payload.id_produto || !payload.id_variante) {
      toast('Selecione produto e variante.', 'warning');
      return;
    }

    if (!payload.tipo_movimentacao || !payload.quantidade) {
      toast('Tipo e quantidade são obrigatórios.', 'warning');
      return;
    }

    try {
      await api('movimentacao_salvar', payload);
      toast('Movimentação registrada com sucesso!', 'success');

      // Fechar offcanvas
      closeAllOc();

      const backdrop = document.querySelector('.modal-backdrop');
      if (backdrop) backdrop.remove();

      // Recarregar lista de movimentações
      carregarMovimentacoes();
      refreshKpis();
    } catch (e) {
      toast(e.message, 'error');
    }
  }

document.addEventListener('click', async (e) => {
  if (e.target.closest('[data-offcanvas-close]')) closeAllOc();

  if (e.target.closest('#btnGerenciarProdutos')) { openOc('offcanvasProdutosV2'); carregarProdutosGrid(); }
  if (e.target.closest('#btnGerenciarFornecedores')) { openOc('offcanvasFornecedoresV2'); carregarFornecedoresGrid(); }
  if (e.target.closest('#btnGerenciarEstoques')) { openOc('offcanvasEstoquesV2'); carregarEstoquesGrid(); }
  if (e.target.closest('#btnRelatorios')) { openOc('offcanvasRelatoriosV2'); }

  // Cards clicáveis
  const card = e.target.closest('[data-kpi]');
  if (card) {
    const tipoCard = card.getAttribute('data-kpi');
    abrirDetalhesCard(tipoCard);
  }

  const novaEntradaBtn = e.target.closest('#btnNovaEntrada');
  if (novaEntradaBtn) {
    abrirOffcanvasNovaEntrada();
  }

  const novaSaidaBtn = e.target.closest('#btnNovaSaida');
  if (novaSaidaBtn) {
    abrirOffcanvasNovaSaida();
  }

  // Botão de impressão nos detalhes
  if (e.target.closest('#btnImprimirDetalhes')) {
    imprimirDetalhesCard();
  }

  // Botões do ofício
  if (e.target.closest('#btnGerarOficio')) {
    gerarOficioEstoque();
  }

  if (e.target.closest('#btnImprimirOficio')) {
    imprimirOficio();
  }

  if (e.target.closest('#btnBaixarOficio')) {
    baixarOficio();
  }

  // Botões do dashboard
  if (e.target.closest('#btnDashboardAlmoxarifado')) {
    abrirDashboardAlmoxarifado();
  }

  if (e.target.closest('#btnAtualizarDashboard')) {
    atualizarDashboard();
  }

  if (e.target.closest('#btnTelaCheia')) {
    telaCheia();
  }

  // Fechar modal do dashboard
  if (e.target.closest('#modalDashboard .btn-close') || e.target.closest('#modalDashboard .btn-secondary')) {
    fecharModalDashboard();
  }

  // Fechar modal do ofício
  if (e.target.closest('#modalOficio .btn-close') || e.target.closest('#modalOficio .btn-secondary')) {
    fecharModalOficio();
  }

  const prodBtn = e.target.closest('[data-prod-open]');
  if (prodBtn) abrirProduto(prodBtn.getAttribute('data-prod-open'));

  const herdarBtn = e.target.closest('[data-herdar]');
  if (herdarBtn) {
    const idProduto = herdarBtn.getAttribute('data-herdar');
    abrirModalHerdarVariantes(idProduto);
  }

  const produtoFonteBtn = e.target.closest('[data-produto-fonte]');
  if (produtoFonteBtn) {
    const idFonte = produtoFonteBtn.getAttribute('data-produto-fonte');
    state.herdarVariantes.produtoFonte = idFonte;

    // Visual feedback
    document.querySelectorAll('[data-produto-fonte]').forEach(btn => {
      btn.classList.remove('active', 'bg-primary', 'text-white');
    });
    produtoFonteBtn.classList.add('active', 'bg-primary', 'text-white');

    document.getElementById('btnConfirmarHerdar').disabled = false;
  }

  const varEdit = e.target.closest('[data-var-edit]');
  if (varEdit) {
    const id = varEdit.getAttribute('data-var-edit');
    try {
      const r = await api('variante_obter', { id });
      const v = r.data;
      document.getElementById('variante_id_v2').value = v.id;
      const vf = document.getElementById('varianteFormV2');
      vf.cor.value = v.cor || '';
      vf.tamanho.value = v.tamanho || '';
      vf.sku_interno.value = v.sku_interno || '';
      vf.quantidade_minima.value = v.quantidade_minima || 0;
      vf.quantidade_alerta.value = v.quantidade_alerta || 0;
      toast('Variante carregada para edição.', 'info');
    } catch (err) { toast(err.message, 'error'); }
  }

  const varDelete = e.target.closest('[data-var-delete]');
  if (varDelete) {
    const id = varDelete.getAttribute('data-var-delete');
    if (!confirm('Tem certeza que deseja excluir esta variante?')) return;
    try {
      await api('variante_inativar', { id });
      toast('Variante excluída com sucesso.', 'success');
      if (state.produtoAtual) {
        abrirProduto(state.produtoAtual);
      }
      refreshKpis();
    } catch (err) { toast(err.message, 'error'); }
  }

  const movCancel = e.target.closest('[data-mov-cancel]');
    if (movCancel) {
      if (!confirm('Cancelar esta movimentação?')) return;
      try {
        await api('movimentacao_cancelar', { id: movCancel.getAttribute('data-mov-cancel') });
        toast('Movimentação cancelada.', 'success');
        carregarMovimentacoes();
        refreshKpis();
      } catch (err) { toast(err.message, 'error'); }
    }

    const movEdit = e.target.closest('[data-mov-edit]');
    if (movEdit) {
      const id = movEdit.getAttribute('data-mov-edit');
      try {
        const r = await api('movimentacao_obter', { id });
        const m = r.data;
        const novaQtd = prompt('Nova quantidade:', m.quantidade);
        if (novaQtd === null) return;
        const novoTipo = prompt('Tipo (Entrada/Saida):', m.tipo_movimentacao);
        if (novoTipo === null) return;
        await api('movimentacao_editar', {
          id,
          quantidade: novaQtd,
          tipo_movimentacao: novoTipo,
          data_movimentacao: m.data_movimentacao,
          motivo_movimentacao: m.motivo_movimentacao || '',
          observacoes: m.observacoes || '',
          documento_referencia: m.documento_referencia || ''
        });
        toast('Movimentação editada.', 'success');
        carregarMovimentacoes();
        refreshKpis();
      } catch (err) { toast(err.message, 'error'); }
    }

    const relBtn = e.target.closest('[data-relatorio]');
    if (relBtn) {
      state.relatorioAtual = relBtn.getAttribute('data-relatorio');
      carregarRelatorio(state.relatorioAtual);
    }
  });

  document.getElementById('filtrosMovForm')?.addEventListener('submit', (e) => {
    e.preventDefault();
    state.mov.page = 1;
    carregarMovimentacoes();
  });

  document.getElementById('movPrev')?.addEventListener('click', () => {
    if (state.mov.page > 1) { state.mov.page -= 1; carregarMovimentacoes(); }
  });
  document.getElementById('movNext')?.addEventListener('click', () => {
    state.mov.page += 1; carregarMovimentacoes();
  });

  document.getElementById('produtoFormV2')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    try {
      const payload = Object.fromEntries(new FormData(e.target).entries());
      await api('produto_salvar', payload);
      toast('Produto salvo.', 'success');
      carregarProdutosGrid();
      refreshKpis();
      if (!payload.id) e.target.reset();
    } catch (err) { toast(err.message, 'error'); }
  });

  document.getElementById('btnNovoProdutoV2')?.addEventListener('click', () => {
    state.produtoAtual = null;
    document.getElementById('produtoFormV2').reset();
    document.getElementById('produto_id_v2').value = '';
    document.getElementById('variante_produto_id_v2').value = '';
    document.getElementById('variantesGridV2').innerHTML = '';
  });

  document.getElementById('btnNovaVarianteV2')?.addEventListener('click', () => {
    const form = document.getElementById('varianteFormV2');
    const idProduto = document.getElementById('variante_produto_id_v2').value;
    form.reset();
    document.getElementById('variante_id_v2').value = '';
    document.getElementById('variante_produto_id_v2').value = idProduto;
    toast('Formulário limpo. Ready para nova variante.', 'info');
  });

  document.getElementById('varianteFormV2')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    try {
      const payload = Object.fromEntries(new FormData(e.target).entries());
      if (!payload.id_produto) {
        toast('Abra um produto antes de cadastrar variante.', 'warning');
        return;
      }

      const action = payload.id ? 'variante_editar' : 'variante_salvar';
      await api(action, payload);

      const message = payload.id ? 'Variante atualizada.' : 'Variante salva.';
      toast(message, 'success');

      abrirProduto(payload.id_produto);
      refreshKpis();
      e.target.reset();
      document.getElementById('variante_produto_id_v2').value = payload.id_produto;
      document.getElementById('variante_id_v2').value = '';
    } catch (err) { toast(err.message, 'error'); }
  });

  document.getElementById('fornecedorFormV2')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    try {
      await api('fornecedor_salvar', Object.fromEntries(new FormData(e.target).entries()));
      toast('Fornecedor salvo.', 'success');
      e.target.reset();
      carregarFornecedoresGrid();
    } catch (err) { toast(err.message, 'error'); }
  });

  document.getElementById('estoqueFormV2')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    try {
      const payload = Object.fromEntries(new FormData(e.target).entries());
      if (payload.id) await api('estoque_editar', payload); else await api('estoque_salvar', payload);
      toast('Estoque salvo.', 'success');
      e.target.reset();
      carregarEstoquesGrid();
    } catch (err) { toast(err.message, 'error'); }
  });

  document.getElementById('relatoriosFormV2')?.addEventListener('submit', (e) => {
    e.preventDefault();
    carregarRelatorio('relatorio_resumo_periodo');
  });

  // Event listeners para modal de herdar variantes
  let buscaTimeout;
  document.getElementById('buscaProdutoFonte')?.addEventListener('input', (e) => {
    clearTimeout(buscaTimeout);
    const termo = e.target.value;
    buscaTimeout = setTimeout(() => {
      buscarProdutosParaHerdar(termo);
    }, 300);
  });

  document.getElementById('btnConfirmarHerdar')?.addEventListener('click', confirmarHerdarVariantes);

  // Event listeners para novas movimentações
  document.getElementById('btnNovaEntrada')?.addEventListener('click', abrirOffcanvasNovaEntrada);
  document.getElementById('btnNovaSaida')?.addEventListener('click', abrirOffcanvasNovaSaida);
  document.getElementById('btnSalvarEntrada')?.addEventListener('click', salvarNovaEntrada);
  document.getElementById('btnSalvarSaida')?.addEventListener('click', salvarNovaSaida);

  // Adicionar busca de produtos nos offcanvas de movimentações
  let buscaEntradaTimeout, buscaSaidaTimeout;

  // Busca para entrada
  document.getElementById('entrada_produto_busca')?.addEventListener('input', (e) => {
    clearTimeout(buscaEntradaTimeout);
    const termo = e.target.value;
    buscaEntradaTimeout = setTimeout(() => {
      if (termo.length >= 2) {
        buscarProdutosParaEntrada(termo);
      } else {
        document.getElementById('entrada_produto_resultados').style.display = 'none';
      }
    }, 300);
  });

  // Busca para saída
  document.getElementById('saida_produto_busca')?.addEventListener('input', (e) => {
    clearTimeout(buscaSaidaTimeout);
    const termo = e.target.value;
    buscaSaidaTimeout = setTimeout(() => {
      if (termo.length >= 2) {
        buscarProdutosParaSaida(termo);
      } else {
        document.getElementById('saida_produto_resultados').style.display = 'none';
      }
    }, 300);
  });

  // Busca de internos para saída
  let buscaInternoSaidaTimeout;
  document.getElementById('saida_interno_busca')?.addEventListener('input', (e) => {
    clearTimeout(buscaInternoSaidaTimeout);
    const termo = e.target.value;
    buscaInternoSaidaTimeout = setTimeout(() => {
      if (termo.length >= 2) {
        buscarInternosParaSaida(termo);
      } else {
        document.getElementById('saida_interno_resultados').style.display = 'none';
      }
    }, 300);
  });

  // Controle dinâmico dos campos de destino na saída
  document.getElementById('saida_tipo_destino')?.addEventListener('change', (e) => {
    const tipo = e.target.value;

    // Esconder todos
    document.getElementById('saida_destino_interno').style.display = 'none';
    document.getElementById('saida_destino_funcionario').style.display = 'none';
    document.getElementById('saida_destino_outro').style.display = 'none';

    // Mostrar o correspondente
    switch(tipo) {
      case 'Interno':
        document.getElementById('saida_destino_interno').style.display = 'block';
        break;
      case 'Funcionario':
        document.getElementById('saida_destino_funcionario').style.display = 'block';
        break;
      case 'Outro':
        document.getElementById('saida_destino_outro').style.display = 'block';
        break;
    }
  });

  // Event listeners para cliques nos resultados
  document.addEventListener('click', (e) => {
    // Selecionar produto para entrada
    if (e.target.closest('[data-entrada-produto-id]')) {
      const produtoEl = e.target.closest('[data-entrada-produto-id]');
      const produto = {
        id: produtoEl.getAttribute('data-entrada-produto-id'),
        nome: produtoEl.getAttribute('data-entrada-produto-nome')
      };

      document.getElementById('entrada_id_produto').value = produto.id;
      document.getElementById('entrada_produto_busca').value = produto.nome;
      document.getElementById('entrada_produto_resultados').style.display = 'none';

      // Carregar variantes do produto
      carregarVariantesDoProdutoEntrada(produto.id);
    }

    // Selecionar produto para saída
    if (e.target.closest('[data-saida-produto-id]')) {
      const produtoEl = e.target.closest('[data-saida-produto-id]');
      const produto = {
        id: produtoEl.getAttribute('data-saida-produto-id'),
        nome: produtoEl.getAttribute('data-saida-produto-nome')
      };

      document.getElementById('saida_id_produto').value = produto.id;
      document.getElementById('saida_produto_busca').value = produto.nome;
      document.getElementById('saida_produto_resultados').style.display = 'none';

      // Carregar variantes do produto
      carregarVariantesDoProdutoSaida(produto.id);
    }

    // Selecionar interno para saída
    if (e.target.closest('[data-saida-interno-id]')) {
      const internoEl = e.target.closest('[data-saida-interno-id]');
      const interno = {
        id: internoEl.getAttribute('data-saida-interno-id'),
        ipen: internoEl.getAttribute('data-saida-interno-ipen'),
        nome: internoEl.getAttribute('data-saida-interno-nome'),
        nome_social: internoEl.getAttribute('data-saida-interno-nome-social')
      };

      selecionarInternoParaSaida(interno);
    }
  });

  // Event listeners para seleção de variantes
  document.getElementById('entrada_variante_select')?.addEventListener('change', (e) => {
    const varianteId = e.target.value;
    document.getElementById('entrada_id_variante').value = varianteId;
  });

  document.getElementById('saida_variante_select')?.addEventListener('change', (e) => {
    const varianteId = e.target.value;
    document.getElementById('saida_id_variante').value = varianteId;
  });

  carregarMovimentacoes();
  refreshKpis();
})();
