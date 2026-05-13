// Proteção SPA
if (typeof window.gestaoKitsLoaded === 'undefined') {
    window.gestaoKitsLoaded = true;

    $(document).ready(function () {
        carregarEstatisticas();
        carregarInternos();

        // Filtros
        $('#form-filtros').on('submit', function (e) {
            e.preventDefault();
            carregarInternos();
        });

        // Limpar filtros
        $('#btn-limpar').on('click', function () {
            $('#form-filtros')[0].reset();
            carregarInternos();
        });
    });

    function carregarEstatisticas() {
        $.ajax({
            url: 'controller.php',
            method: 'POST',
            data: { action: 'carregar_estatisticas' },
            success: function (response) {
                if (response.success) {
                    $('#kits-disponiveis').text(response.data.kits_disponiveis);
                    $('#internos-ativos').text(response.data.internos_ativos);
                    $('#sem-kit').text(response.data.sem_kit);
                    $('#conflitos').text(response.data.conflitos);
                }
            }
        });
    }

    function carregarInternos() {
        const dados = {
            action: 'carregar_internos',
            search: $('input[name="search"]').val(),
            situacao: $('select[name="situacao"]').val(),
            galeria: $('select[name="galeria"]').val(),
            bloco: $('select[name="bloco"]').val()
        };

        $.ajax({
            url: 'controller.php',
            method: 'POST',
            data: dados,
            success: function (response) {
                if (response.success) {
                    popularTabela(response.data);
                }
            }
        });
    }

    function popularTabela(internos) {
        const tbody = $('#tabela-internos tbody');
        tbody.empty();

        if (internos.length === 0) {
            tbody.append('<tr><td colspan="6" class="text-center">Nenhum registro encontrado</td></tr>');
            return;
        }

        internos.forEach(interno => {
            const nome = interno.nome_social || interno.nome;
            const local = `${interno.galeria}${interno.bloco}-${interno.res}`;
            const corClasse = interno.cor_roupa?.toLowerCase() === 'verde' ? 'table-success' : '';

            const tr = `
            <tr class="${corClasse}">
                <td>${interno.ipen}</td>
                <td>${nome}</td>
                <td>${local}</td>
                <td>${interno.situacao || 'N/A'}</td>
                <td>${interno.kit || '-'}</td>
                <td>
                    <button class="btn btn-sm btn-primary">
                        <i class="fas fa-edit"></i>
                    </button>
                </td>
            </tr>
        `;
            tbody.append(tr);
        });
    }

    // Fechar proteção SPA
}
