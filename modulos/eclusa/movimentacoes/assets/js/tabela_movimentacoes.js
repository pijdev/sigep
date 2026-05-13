// JavaScript para tabela completa de movimentações com período e impressão
$(document).ready(function() {
  carregarTodasMovimentacoes();

  // Evento de mudança de período
  $('#periodoTabela').on('change', function() {
    carregarTodasMovimentacoes();
  });

  // Evento de impressão
  $('#btnImprimirTabela').on('click', function() {
    imprimirTabela();
  });
});

function carregarTodasMovimentacoes() {
  const periodo = $('#periodoTabela').val();

  // Mostrar loading
  $('#tabelaMovimentacoesGeral tbody').html(`
    <tr>
      <td colspan="11" class="text-center py-4">
        <i class="fas fa-spinner fa-spin mr-2"></i>
        Carregando movimentações...
      </td>
    </tr>
  `);

  $.post('/includes/tabela_movimentacoes_logica.php', {
    action: 'get_todas_movimentacoes',
    periodo: periodo
  }, function(response) {
    if (response.error) {
      $('#tabelaMovimentacoesGeral tbody').html(`
        <tr>
          <td colspan="11" class="text-center text-danger py-4">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            Erro: ${response.error}
          </td>
        </tr>
      `);
      return;
    }

    const movimentacoes = response.movimentacoes || [];

    if (movimentacoes.length === 0) {
      $('#tabelaMovimentacoesGeral tbody').html(`
        <tr>
          <td colspan="11" class="text-center text-muted py-4">
            <i class="fas fa-info-circle mr-2"></i>
            Nenhuma movimentação encontrada
          </td>
        </tr>
      `);
    } else {
      let html = '';
      movimentacoes.forEach(function(mov) {
        const data = new Date(mov.data_movimentacao).toLocaleDateString('pt-BR');
        const tipoClass = mov.tipo_movimento === 'Entrada' ? 'success' :
                         mov.tipo_movimento === 'Saída' ? 'danger' : 'info';

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
            <td><span class="badge badge-${tipoClass}">${mov.tipo_movimento}</span></td>
            <td>${mov.observacoes || '-'}</td>
            <td>${mov.cadastrado_por || '-'}</td>
          </tr>
        `;
      });

      $('#tabelaMovimentacoesGeral tbody').html(html);
    }

    // Atualizar informações
    const periodoTexto = response.periodo == 0 ? 'Todo período' : `Últimos ${response.periodo} dias`;
    $('#infoPaginacaoMovimentacoes').html(
      `Total de ${response.total} movimentações encontradas (${periodoTexto})`
    );
  });
}

function imprimirTabela() {
  // Criar uma nova janela para impressão
  const janelaImpressao = window.open('', '_blank');

  // Obter o período selecionado
  const periodo = $('#periodoTabela').val();
  const periodoTexto = periodo == 0 ? 'Todo período' : `Últimos ${periodo} dias`;

  // Obter os dados da tabela
  const tabelaHTML = $('#tabelaMovimentacoesGeral').clone();

  // Remover classes que não são necessárias para impressão
  tabelaHTML.removeClass('table-striped table-hover');
  tabelaHTML.addClass('table-bordered');

  // Construir o HTML para impressão
  const impressaoHTML = `
    <!DOCTYPE html>
    <html>
    <head>
      <title>Relatório de Movimentações - ${periodoTexto}</title>
      <style>
        @media print {
          @page {
            size: A4;
            margin: 1cm;
            orientation: landscape;
          }

          body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            margin: 0;
            padding: 0;
          }

          .table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
          }

          .table th,
          .table td {
            border: 1px solid #ddd;
            padding: 4px;
            text-align: left;
            white-space: nowrap;
            vertical-align: top;
          }

          .table th {
            background-color: #f2f2f2;
            font-weight: bold;
          }

          .badge {
            padding: 2px 4px;
            font-size: 8px;
            border-radius: 3px;
          }

          .badge-success {
            background-color: #28a745;
            color: white;
          }

          .badge-danger {
            background-color: #dc3545;
            color: white;
          }

          .badge-info {
            background-color: #17a2b8;
            color: white;
          }

          h1 {
            font-size: 16px;
            margin-bottom: 10px;
          }

          .info-periodo {
            font-size: 12px;
            margin-bottom: 15px;
            color: #666;
          }

          .no-break {
            page-break-inside: avoid;
          }
        }

        @media screen {
          body {
            font-family: Arial, sans-serif;
            margin: 20px;
          }
        }
      </style>
    </head>
    <body>
      <h1>Relatório de Movimentações da Eclusa</h1>
      <div class="info-periodo">Período: ${periodoTexto}</div>
      <div class="no-break">
        ${tabelaHTML.prop('outerHTML')}
      </div>
      <script>
        window.onload = function() {
          window.print();
          window.close();
        };
      </script>
    </body>
    </html>
  `;

  // Escrever o HTML na nova janela
  janelaImpressao.document.write(impressaoHTML);
  janelaImpressao.document.close();
}
