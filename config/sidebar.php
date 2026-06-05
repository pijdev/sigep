<?php

function podeVisualizarSetor($perm_column)
{
    if (isset($_SESSION['user_admin']) && $_SESSION['user_admin'] === true) return true;
    return (isset($_SESSION[$perm_column]) && $_SESSION[$perm_column] > 0);
}

// Obter configurações de menu baseadas nas permissões do usuário
function getMenuConfig()
{
    $menu = [];

    // Menu Censura
    if (podeVisualizarSetor('perm_censura')) {
        $menu['censura'] = [
            'title' => 'Censura',
            'icon' => 'fas fa-lock text-primary',
            'items' => [
                ['title' => 'Rouparia', 'icon' => 'fas fa-barcode text-info', 'page' => '/paginas/censura_rouparia_numeros.php', 'parent' => 'Rouparia'],
                ['title' => 'Recebimento Roupas', 'icon' => 'fas fa-tshirt', 'style' => 'color: #ffc107;', 'page' => '/paginas/internos_recebimento_roupas.php', 'parent' => 'Censura'],
                ['title' => 'Recebimento Livros', 'icon' => 'fas fa-book', 'style' => 'color: #17a2b8;', 'page' => '/paginas/internos_recebimento_livros.php', 'parent' => 'Censura'],
                ['title' => 'Recebimento Cosméticos', 'icon' => 'fas fa-pump-soap', 'style' => 'color: #e83e8c;', 'page' => '/paginas/internos_recebimento_cosmeticos.php', 'parent' => 'Censura'],
                ['title' => 'Entrega de Kits', 'icon' => 'fas fa-box-open', 'style' => 'color: #28a745;', 'page' => '/paginas/internos_entrega_kits.php', 'parent' => 'Entrega de Kits'],
                ['title' => 'Entrada Eletrônicos', 'icon' => 'fas fa-plug', 'style' => 'color: #6f42c1;', 'page' => '/paginas/internos_recebimento_eletronicos.php', 'parent' => 'Entrada Eletrônicos'],
                ['title' => 'Gestão Eletrônicos', 'icon' => 'fas fa-laptop', 'style' => 'color: #fd7e14;', 'page' => '/paginas/internos_eletronicos_gestao.php', 'parent' => 'Gestão Eletrônicos'],
                ['title' => 'Doação Eletrônicos', 'icon' => 'fas fa-hand-holding-heart', 'style' => 'color: #20c997;', 'page' => '/paginas/internos_doacao_eletronicos.php', 'parent' => 'Doação Eletrônicos'],
                ['title' => 'Cartas', 'icon' => 'fas fa-envelope text-primary', 'page' => '/modulos/censura/cartas/cartas_view.php', 'parent' => 'Cartas'],
                ['title' => 'Memorandos', 'icon' => 'fas fa-file-alt text-secondary', 'page' => '/modulos/geral/em_construcao/em_construcao_view.php', 'parent' => 'Memorandos'],
                ['title' => 'Solicitações de Colchões', 'icon' => 'fas fa-clipboard-list text-warning', 'page' => '/paginas/internos_colchoes_solicitacoes.php', 'parent' => 'Solicitações de Colchões'],
                ['title' => 'Gestão de Colchões', 'icon' => 'fas fa-bed text-info', 'page' => '/paginas/internos_colchoes_gestao.php', 'parent' => 'Gestão de Colchões'],
                ['title' => 'Controle de Estoque', 'icon' => 'fas fa-warehouse text-warning', 'page' => '/paginas/censura_estoque_controle_v2.php', 'parent' => 'Controle de Estoque']
            ]
        ];
    }

    // Menu Manutenção
    if (podeVisualizarSetor('perm_manutencao') || isset($_SESSION['user_admin']) && $_SESSION['user_admin'] === true) {
        $menu['manutencao'] = [
            'title' => 'Manutenção',
            'icon' => 'fas fa-wrench text-info',
            'items' => [
                ['title' => 'Controle de Manutenções', 'icon' => 'fas fa-tools text-primary', 'page' => '/modulos/censura/manutencao/manutencao_view.php', 'parent' => 'Controle de Manutenções']
            ]
        ];
    }

    // Menu TI
    if (podeVisualizarSetor('perm_ti') || isset($_SESSION['user_admin']) && $_SESSION['user_admin'] === true) {
        $menu['ti'] = [
            'title' => 'TI',
            'icon' => 'fa-duotone fa-solid fa-network-wired',
            'items' => [
                ['title' => 'Gestão de Kits', 'icon' => 'fas fa-box-open text-success', 'page' => '/modulos/censura/rouparia/gestao_kits/view.php', 'parent' => 'Gestão de Kits'],
                ['title' => 'Painel de Internos', 'icon' => 'fas fa-th-large text-primary', 'page' => '/modulos/geral/painel_internos/painel_internos_view.php', 'parent' => 'Painel de Internos'],
                [
                    'title' => 'CFTV',
                    'icon' => 'fas fa-video text-danger',
                    'page' => '/modulos/seguranca/cftv/cftv_view.php',
                    'parent' => 'CFTV'
                ]
            ]
        ];
    }

    // Menu Almoxarifado (sempre visível)
    $menu['almoxarifado'] = [
        'title' => 'Almoxarifado',
        'icon' => 'fas fa-boxes text-teal',
        'items' => []
    ];

    // Menu Segurança do Trabalho
    if (podeVisualizarSetor('perm_seg_trab')) {
        $menu['seguranca_trabalho'] = [
            'title' => 'Segurança do Trabalho',
            'icon' => 'fas fa-shield-alt text-warning',
            'items' => []
        ];
    }

    // Menu Laboral
    if (podeVisualizarSetor('perm_laboral')) {
        $menu['laboral'] = [
            'title' => 'Laboral',
            'icon' => 'fas fa-hand-holding-usd text-success',
            'items' => [
                [
                    'title' => 'Pecúlio',
                    'icon' => 'fas fa-coins text-warning',
                    'items' => [
                        ['title' => 'Folha de Pecúlio', 'icon' => 'fas fa-list-ul', 'page' => '/paginas/peculio_itens.php', 'parent' => 'Pecúlio'],
                        ['title' => 'Cadastrar Pecúlio', 'icon' => 'fas fa-check-double text-primary', 'page' => '/paginas/peculio_gestao.php', 'parent' => 'Pecúlio']
                    ]
                ],
                [
                    'title' => 'RH',
                    'icon' => 'fas fa-users text-info',
                    'items' => [
                        ['title' => 'Lista de Trabalho', 'icon' => 'fas fa-list text-primary', 'page' => '/modulos/laboral/lista_trabalho/lista_trabalho_view.php', 'parent' => 'RH'],
                        ['title' => 'Canteiros', 'icon' => 'fas fa-industry text-success', 'page' => '/modulos/laboral/canteiros/canteiros_view.php', 'parent' => 'RH']
                    ]
                ],
                [
                    'title' => 'Financeiro',
                    'icon' => 'fas fa-chart-line text-success',
                    'items' => [
                        ['title' => 'Controle de Dívidas', 'icon' => 'fas fa-hand-holding-usd text-danger', 'page' => '/modulos/laboral/controle_dividas/controle_dividas_view.php', 'parent' => 'Financeiro']
                    ]
                ]
            ]
        ];
    }

    // Menu Portaria
    if (podeVisualizarSetor('perm_portaria')) {
        $menu['portaria'] = [
            'title' => 'Portaria',
            'icon' => 'fas fa-concierge-bell text-success',
            'items' => [
                ['title' => 'Recebimento Roupas', 'icon' => 'fas fa-tshirt', 'style' => 'color: #ffc107;', 'page' => '/paginas/internos_recebimento_roupas.php', 'parent' => 'Censura'],
                ['title' => 'Recebimento Eletrônicos', 'icon' => 'fas fa-plug', 'style' => 'color: #6f42c1;', 'page' => '/paginas/internos_recebimento_eletronicos.php', 'parent' => 'Entrada Eletrônicos'],
                ['title' => 'Recebimento Livros', 'icon' => 'fas fa-book', 'style' => 'color: #17a2b8;', 'page' => '/paginas/internos_recebimento_livros.php', 'parent' => 'Censura'],
                ['title' => 'Recebimento Cosméticos', 'icon' => 'fas fa-pump-soap', 'style' => 'color: #e83e8c;', 'page' => '/paginas/internos_recebimento_cosmeticos.php', 'parent' => 'Censura']
            ]
        ];
    }

    // Menu Coordenação
    if (podeVisualizarSetor('perm_coord')) {
        $menu['coordenacao'] = [
            'title' => 'Coordenação',
            'icon' => 'fas fa-sitemap text-primary',
            'items' => [
                ['title' => 'Painel Regalias', 'icon' => 'fas fa-users-cog text-success', 'page' => '/modulos/coordenacao/regalias/regalias_view.php', 'parent' => 'Painel Regalias'],
                ['title' => 'Medidas Disciplinares', 'icon' => 'fas fa-gavel text-danger', 'page' => '/medidas-disciplinares', 'parent' => 'Medidas Disciplinares'],
                ['title' => 'Solicitações de Internos', 'icon' => 'fas fa-users-cog text-success', 'page' => '/modulos/coordenacao/solicitacoes/index.php', 'parent' => 'Solicitações de Internos']
            ]
        ];
    }

    // Menu Eclusa
    if (podeVisualizarSetor('perm_eclusa')) {
        $menu['eclusa'] = [
            'title' => 'Eclusa',
            'icon' => 'fas fa-archway text-warning',
            'items' => [
                ['title' => 'Movimentações', 'icon' => 'fas fa-truck-loading text-info', 'page' => '/modulos/eclusa/movimentacoes/movimentacoes_view.php', 'parent' => 'Movimentações'],
                ['title' => 'Escoltas', 'icon' => 'fas fa-user-shield text-success', 'page' => '/modulos/eclusa/escolta/escolta_view.php', 'parent' => 'Escoltas'],
                ['title' => 'Caminhões Pipa', 'icon' => 'fas fa-truck-pickup text-primary', 'page' => '/paginas/caminhoes_pipa.php', 'parent' => 'Caminhões Pipa']
            ]
        ];
    }

    if (isset($_SESSION['user_admin']) && $_SESSION['user_admin'] === true) {
        $menu['ferramentas'] = [
            'title' => 'Ferramentas',
            'icon' => 'fas fa-tools text-secondary',
            'items' => [
                ['title' => 'Painel de Internos', 'icon' => 'fas fa-th-large text-primary', 'page' => '/modulos/geral/painel_internos/internos_painel_view_adminlte4.php', 'parent' => 'Painel de Internos'],
                ['title' => 'Cadastro de Internos', 'icon' => 'fas fa-users-cog text-primary', 'page' => '/modulos/geral/cadastro_internos/cadastro_internos_view.php', 'parent' => 'Cadastro de Internos'],
                ['title' => 'Serviços', 'icon' => 'fas fa-cogs text-info', 'page' => 'javascript:void(0)', 'onclick' => 'loadPage(\'/modulos/servicos/job_manager/job_manager_view.php\', \'Agendador de Tarefas\', \'Ferramentas\')', 'parent' => 'Serviços'],
                ['title' => 'MySQL', 'icon' => 'fas fa-database text-warning', 'page' => '/modulos/servicos/phpmyadmin/phpmyadmin.php', 'parent' => 'Serviços'],
                ['title' => 'Notificações', 'icon' => 'fas fa-bell text-info', 'page' => '/modulos/servicos/notificacoes/notificacoes_view.php', 'parent' => 'Serviços']
            ]
        ];
    }

    $setores = [
        'perm_rh' => ['Recursos Humanos', 'fa-users', 'text-info'],
        'perm_direcao' => ['Direção', 'fa-user-tie', 'text-danger'],
        'perm_ti' => ['TI', 'fa-network-wired', 'text-info'],
        'perm_serralheria' => ['Serralheria', 'fa-tools', 'text-secondary'],
        'perm_escola' => ['Escola', 'fa-school', 'text-warning'],
        'perm_carga' => ['Carga / Logística', 'fa-truck-loading', 'text-teal'],
        'perm_industria' => ['Indústria', 'fa-industry', 'text-primary'],
        'perm_juridico' => ['Jurídico', 'fa-gavel', 'text-warning'],
        'perm_cozinha' => ['Cozinha', 'fa-utensils', 'text-danger'],
    ];

    foreach ($setores as $perm => $info) {
        if (podeVisualizarSetor($perm)) {
            $menu['outros'][] = [
                'title' => $info[0],
                'icon' => $info[1],
                'class' => $info[2],
                'perm' => $perm
            ];
        }
    }

    return $menu;
}

function mostrarPainelInternos()
{
    return ($_SESSION['user_admin'] or
        $_SESSION['perm_censura'] or
        $_SESSION['perm_seg_trab'] or
        $_SESSION['perm_laboral']) or
        $_SESSION['perm_coord'];
}

function getUserInfo()
{
    return [
        'nome' => $_SESSION['user_nome'] ?? '',
        'setor' => $_SESSION['user_setor'] ?? '',
        'admin' => $_SESSION['user_admin'] ?? false
    ];
}
