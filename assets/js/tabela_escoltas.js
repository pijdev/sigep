// JavaScript para tabela completa de escoltas com período e impressão
$(document).ready(function() {
  carregarTodasEscoltas();

  // Evento de mudança de período
  $('#periodoTabela').on('change', function() {
    carregarTodasEscoltas();
  });

  // Evento de impressão
  $('#btnImprimirTabela').on('click', function() {
    imprimirTabela();
  });
});

function carregarTodasEscoltas() {
  const periodo = $('#periodoTabela').val();

  // Mostrar loading
  $('#tabelaEscoltasGeral tbody').html(
    '<tr>' +
      '<td colspan="11" class="text-center py-4">' +
        '<i class="fas fa-spinner fa-spin mr-2"></i>' +
        'Carregando escoltas...' +
      '</td>' +
    '</tr>'
  );

  $.post('../includes/tabela_escoltas_logica.php', {
    action: 'get_todas_escoltas',
    periodo: periodo
  }, function(response) {
    if (response.error) {
      $('#tabelaEscoltasGeral tbody').html(
        '<tr>' +
          '<td colspan="11" class="text-center text-danger py-4">' +
            '<i class="fas fa-exclamation-triangle mr-2"></i>' +
            'Erro: ' + response.error +
          '</td>' +
        '</tr>'
      );
      return;
    }

    const escoltas = response.escoltas || [];

    if (escoltas.length === 0) {
      $('#tabelaEscoltasGeral tbody').html(
        '<tr>' +
          '<td colspan="11" class="text-center text-muted py-4">' +
            '<i class="fas fa-info-circle mr-2"></i>' +
            'Nenhuma escolta encontrada' +
          '</td>' +
        '</tr>'
      );
    } else {
      let html = '';
      escoltas.forEach(function(esc) {
        const data = new Date(esc.data_cadastro).toLocaleDateString('pt-BR');
        const statusClass = esc.status === 'Finalizado' ? 'success' : 'warning';
        const notClass = esc.eh_not === 'Sim' ? 'info' : 'secondary';

        html +=
          '<tr>' +
            '<td>' + data + '</td>' +
            '<td>' + (esc.interno || '-') + '</td>' +
            '<td>' + (esc.destino || '-') + '</td>' +
            '<td>' + (esc.motorista || '-') + '</td>' +
            '<td>' + (esc.placa || '-') + '</td>' +
            '<td><span class="badge badge-' + statusClass + '">' + esc.status + '</span></td>' +
            '<td>' + (esc.hora_prevista || '-') + '</td>' +
            '<td>' + (esc.hora_chegada || '-') + '</td>' +
            '<td>' + (esc.hora_retorno || '-') + '</td>' +
            '<td><span class="badge badge-' + notClass + '">' + (esc.eh_not || '-') + '</span></td>' +
            '<td>' + (esc.cadastrado_por || '-') + '</td>' +
          '</tr>';
      });

      $('#tabelaEscoltasGeral tbody').html(html);
    }

    // Atualizar informações
    const periodoTexto = response.periodo == 0 ? 'Todo período' : 'Últimos ' + response.periodo + ' dias';
    $('#infoPaginacaoEscoltas').html(
      'Total de ' + response.total + ' escoltas encontradas (' + periodoTexto + ')'
    );
  });
}

function imprimirTabela() {
  // Criar uma nova janela para impressão
  const janelaImpressao = window.open('', '_blank');

  // Obter o período selecionado
  const periodo = $('#periodoTabela').val();
  const periodoTexto = periodo == 0 ? 'Todo período' : 'Últimos ' + periodo + ' dias';

  // Obter os dados da tabela
  const tabelaHTML = $('#tabelaEscoltasGeral').clone();

  // Remover classes que não são necessárias para impressão
  tabelaHTML.removeClass('table-striped table-hover');
  tabelaHTML.addClass('table-bordered');

  // Construir o HTML para impressão
  const impressaoHTML =
    '<!DOCTYPE html>' +
    '<html>' +
    '<head>' +
      '<title>Relatório de Escoltas - ' + periodoTexto + '</title>' +
      '<style>' +
        '@media print {' +
          '@page {' +
            'size: A4;' +
            'margin: 1cm;' +
            'orientation: landscape;' +
          '}' +
          '' +
          'body {' +
            'font-family: Arial, sans-serif;' +
            'font-size: 10px;' +
            'margin: 0;' +
            'padding: 0;' +
          '}' +
          '' +
          '.table {' +
            'width: 100%;' +
            'border-collapse: collapse;' +
            'font-size: 9px;' +
          '}' +
          '' +
          '.table th,' +
          '.table td {' +
            'border: 1px solid #ddd;' +
            'padding: 4px;' +
            'text-align: left;' +
            'white-space: nowrap;' +
            'vertical-align: top;' +
          '}' +
          '' +
          '.table th {' +
            'background-color: #f2f2f2;' +
            'font-weight: bold;' +
          '}' +
          '' +
          '.badge {' +
            'padding: 2px 4px;' +
            'font-size: 8px;' +
            'border-radius: 3px;' +
          '}' +
          '' +
          '.badge-success {' +
            'background-color: #28a745;' +
            'color: white;' +
          '}' +
          '' +
          '.badge-warning {' +
            'background-color: #ffc107;' +
            'color: black;' +
          '}' +
          '' +
          '.badge-info {' +
            'background-color: #17a2b8;' +
            'color: white;' +
          '}' +
          '' +
          '.badge-secondary {' +
            'background-color: #6c757d;' +
            'color: white;' +
          '}' +
          '' +
          'h1 {' +
            'font-size: 16px;' +
            'margin-bottom: 10px;' +
          '}' +
          '' +
          '.info-periodo {' +
            'font-size: 12px;' +
            'margin-bottom: 15px;' +
            'color: #666;' +
          '}' +
          '' +
          '.no-break {' +
            'page-break-inside: avoid;' +
          '}' +
        '}' +
        '' +
        '@media screen {' +
          'body {' +
            'font-family: Arial, sans-serif;' +
            'margin: 20px;' +
          '}' +
        '}' +
      '</style>' +
    '</head>' +
    '<body>' +
      '<h1>Relatório de Escoltas</h1>' +
      '<div class="info-periodo">Período: ' + periodoTexto + '</div>' +
      '<div class="no-break">' +
        tabelaHTML.prop('outerHTML') +
      '</div>' +
      '<script>' +
        'window.onload = function() {' +
          'window.print();' +
          'window.close();' +
        '};' +
      '</script>' +
    '</body>' +
    '</html>';

  // Escrever o HTML na nova janela
  janelaImpressao.document.write(impressaoHTML);
  janelaImpressao.document.close();
}
