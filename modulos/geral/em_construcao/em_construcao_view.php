<?php

/**
 * SIGEP - Módulos em Desenvolvimento
 * Sistema Integrado de Gestão Prisional
 *
 * Versão AdminLTE 4 - Dashboard de Progresso
 * @author Cascade AI
 * @version 2.0
 * @since 2026-03-25
 */

// Bloquear usuário Rouparia de acessar relatórios
if (isset($_SESSION['user_nome']) && $_SESSION['user_nome'] === 'Rouparia') {
    die('<div class="alert alert-danger m-3">
        <h5 class="alert-heading"><i class="fas fa-exclamation-triangle"></i> Acesso Negado</h5>
        <p>Usuário rouparia não tem permissão para acessar este módulo.</p>
        <hr>
        <a href="javascript:history.back()" class="btn btn-primary"><i class="fas fa-arrow-left"></i> Voltar</a>
    </div>');
}

require_once __DIR__ . '/em_construcao_logica.php';
?>

<!-- Content Header (Page header) -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">
                    <i class="fas fa-rocket text-primary mr-2"></i>
                    Módulos em Desenvolvimento
                </h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i> Dashboard</a></li>
                    <li class="breadcrumb-item active">Módulos</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">

        <!-- Cards de Estatísticas Principais -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3><?= count($modulos_implementados) ?></h3>
                        <p>Módulos Ativos</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3><?= count($modulos_legado) ?></h3>
                        <p>Módulos Legado</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-history"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3><?= count($modulos_desenvolvimento) ?></h3>
                        <p>Em Desenvolvimento</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-tools"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-primary">
                    <div class="inner">
                        <h3><?= $progresso_geral ?>%</h3>
                        <p>Progresso Total</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-chart-pie"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Módulos Implementados -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-check-double text-success mr-2"></i>
                            Módulos Implementados
                        </h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- Painel de Internos -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card card-success card-outline">
                                    <div class="card-header">
                                        <h5 class="card-title">
                                            <i class="fas fa-users mr-2"></i>
                                            Painel de Internos
                                        </h5>
                                        <div class="card-tools">
                                            <span class="badge badge-success">100%</span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <p class="card-text">
                                            Visualização completa da estrutura prisional com galerias, blocos e celas em tempo real.
                                        </p>
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-check text-success mr-2"></i>Mapa visual interativo</li>
                                            <li><i class="fas fa-check text-success mr-2"></i>Contagem por cela</li>
                                            <li><i class="fas fa-check text-success mr-2"></i>Busca avançada</li>
                                            <li><i class="fas fa-check text-success mr-2"></i>Histórico MDs</li>
                                        </ul>
                                        <a href="/paginas/internos_painel.php" class="btn btn-success btn-sm">
                                            <i class="fas fa-external-link-alt mr-1"></i> Acessar
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Cadastro de Internos -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card card-primary card-outline">
                                    <div class="card-header">
                                        <h5 class="card-title">
                                            <i class="fas fa-user-plus mr-2"></i>
                                            Cadastro de Internos
                                        </h5>
                                        <div class="card-tools">
                                            <span class="badge badge-primary">100%</span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <p class="card-text">
                                            Sistema completo de cadastro e gestão de dados pessoais dos internos.
                                        </p>
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-check text-success mr-2"></i>Dados pessoais</li>
                                            <li><i class="fas fa-check text-success mr-2"></i>Foto e biometria</li>
                                            <li><i class="fas fa-check text-success mr-2"></i>Histórico criminal</li>
                                            <li><i class="fas fa-check text-success mr-2"></i>Documentos</li>
                                        </ul>
                                        <a href="/modulos/geral/cadastro_internos/cadastro_internos_view.php" class="btn btn-primary btn-sm">
                                            <i class="fas fa-external-link-alt mr-1"></i> Acessar
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Recebimento de Eletrônicos -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card card-info card-outline">
                                    <div class="card-header">
                                        <h5 class="card-title">
                                            <i class="fas fa-tv mr-2"></i>
                                            Eletrônicos
                                        </h5>
                                        <div class="card-tools">
                                            <span class="badge badge-info">100%</span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <p class="card-text">
                                            Controle completo de entrada e saída de aparelhos eletrônicos dos internos.
                                        </p>
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-check text-success mr-2"></i>TVs, rádios, ventiladores</li>
                                            <li><i class="fas fa-check text-success mr-2"></i>Controle de marcas</li>
                                            <li><i class="fas fa-check text-success mr-2"></i>Gestão por cela</li>
                                            <li><i class="fas fa-check text-success mr-2"></i>Emissão de recibos</li>
                                        </ul>
                                        <a href="/paginas/cadastro_eletronicos_celas.php" class="btn btn-info btn-sm">
                                            <i class="fas fa-external-link-alt mr-1"></i> Acessar
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Rouparia -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card card-warning card-outline">
                                    <div class="card-header">
                                        <h5 class="card-title">
                                            <i class="fas fa-tshirt mr-2"></i>
                                            Rouparia
                                        </h5>
                                        <div class="card-tools">
                                            <span class="badge badge-warning">100%</span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <p class="card-text">
                                            Gestão de recebimento e controle de roupas dos internos com sistema de cores.
                                        </p>
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-check text-success mr-2"></i>Recebimento por interno</li>
                                            <li><i class="fas fa-check text-success mr-2"></i>Identificação por cores</li>
                                            <li><i class="fas fa-check text-success mr-2"></i>Controle de datas</li>
                                            <li><i class="fas fa-check text-success mr-2"></i>Relatórios</li>
                                        </ul>
                                        <a href="/paginas/internos_recebimento_roupas.php" class="btn btn-warning btn-sm">
                                            <i class="fas fa-external-link-alt mr-1"></i> Acessar
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Censura de Cartas -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card card-danger card-outline">
                                    <div class="card-header">
                                        <h5 class="card-title">
                                            <i class="fas fa-envelope-open-text mr-2"></i>
                                            Censura de Cartas
                                        </h5>
                                        <div class="card-tools">
                                            <span class="badge badge-danger">100%</span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <p class="card-text">
                                            Sistema completo de controle e censura de correspondências dos internos.
                                        </p>
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-check text-success mr-2"></i>Registro de cartas</li>
                                            <li><i class="fas fa-check text-success mr-2"></i>Controle de devolução</li>
                                            <li><i class="fas fa-check text-success mr-2"></i>Histórico completo</li>
                                            <li><i class="fas fa-check text-success mr-2"></i>Relatórios mensais</li>
                                        </ul>
                                        <a href="/modulos/censura/cartas/cartas_view.php" class="btn btn-danger btn-sm">
                                            <i class="fas fa-external-link-alt mr-1"></i> Acessar
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Eclusa -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card card-secondary card-outline">
                                    <div class="card-header">
                                        <h5 class="card-title">
                                            <i class="fas fa-door-open mr-2"></i>
                                            Eclusa
                                        </h5>
                                        <div class="card-tools">
                                            <span class="badge badge-secondary">100%</span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <p class="card-text">
                                            Controle de entrada e saída de pessoas e materiais do estabelecimento.
                                        </p>
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-check text-success mr-2"></i>Registro de acessos</li>
                                            <li><i class="fas fa-check text-success mr-2"></i>Controle de materiais</li>
                                            <li><i class="fas fa-check text-success mr-2"></i>Escolta de presos</li>
                                            <li><i class="fas fa-check text-success mr-2"></i>Histórico completo</li>
                                        </ul>
                                        <a href="/modulos/eclusa/escolta/escolta_view.php" class="btn btn-secondary btn-sm">
                                            <i class="fas fa-external-link-alt mr-1"></i> Acessar
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Medidas Disciplinares -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card card-dark card-outline">
                                    <div class="card-header">
                                        <h5 class="card-title">
                                            <i class="fas fa-gavel mr-2"></i>
                                            Medidas Disciplinares
                                        </h5>
                                        <div class="card-tools">
                                            <span class="badge badge-dark">100%</span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <p class="card-text">
                                            Sistema completo de registro e acompanhamento de medidas disciplinares.
                                        </p>
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-check text-success mr-2"></i>Registro de MDs</li>
                                            <li><i class="fas fa-check text-success mr-2"></i>Acompanhamento</li>
                                            <li><i class="fas fa-check text-success mr-2"></i>Relatórios</li>
                                            <li><i class="fas fa-check text-success mr-2"></i>Controle de prazos</li>
                                        </ul>
                                        <a href="/modulos/coordenacao/medidas_disciplinares/md_view.php" class="btn btn-dark btn-sm">
                                            <i class="fas fa-external-link-alt mr-1"></i> Acessar
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Gestão de Colchões -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card card-info card-outline">
                                    <div class="card-header">
                                        <h5 class="card-title">
                                            <i class="fas fa-bed mr-2"></i>
                                            Gestão de Colchões
                                        </h5>
                                        <div class="card-tools">
                                            <span class="badge badge-info">100%</span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <p class="card-text">
                                            Sistema completo de solicitação, controle e gestão de colchões do estabelecimento.
                                        </p>
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-check text-success mr-2"></i>Sistema de solicitações</li>
                                            <li><i class="fas fa-check text-success mr-2"></i>Controle de estoque</li>
                                            <li><i class="fas fa-check text-success mr-2"></i>Histórico completo</li>
                                            <li><i class="fas fa-check text-success mr-2"></i>Manutenção</li>
                                        </ul>
                                        <a href="/paginas/internos_colchoes_gestao.php" class="btn btn-info btn-sm">
                                            <i class="fas fa-external-link-alt mr-1"></i> Acessar
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Kits LGBT -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card card-success card-outline">
                                    <div class="card-header">
                                        <h5 class="card-title">
                                            <i class="fas fa-rainbow mr-2"></i>
                                            Kits LGBT
                                        </h5>
                                        <div class="card-tools">
                                            <span class="badge badge-success">100%</span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <p class="card-text">
                                            Entrega e controle de kits específicos para população LGBT com termos personalizados.
                                        </p>
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-check text-success mr-2"></i>Cadastro de kits</li>
                                            <li><i class="fas fa-check text-success mr-2"></i>Controle de entregas</li>
                                            <li><i class="fas fa-check text-success mr-2"></i>Termos personalizados</li>
                                            <li><i class="fas fa-check text-success mr-2"></i>Relatórios mensais</li>
                                        </ul>
                                        <a href="/paginas/internos_entrega_kits.php" class="btn btn-success btn-sm">
                                            <i class="fas fa-external-link-alt mr-1"></i> Acessar
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Cálculo de Multas -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card card-warning card-outline">
                                    <div class="card-header">
                                        <h5 class="card-title">
                                            <i class="fas fa-calculator mr-2"></i>
                                            Cálculo de Multas
                                        </h5>
                                        <div class="card-tools">
                                            <span class="badge badge-warning">100%</span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <p class="card-text">
                                            Sistema automatizado de cálculo e gestão de multas disciplinares.
                                        </p>
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-check text-success mr-2"></i>Cálculo automático</li>
                                            <li><i class="fas fa-check text-success mr-2"></i>Gestão de valores</li>
                                            <li><i class="fas fa-check text-success mr-2"></i>Relatórios de multas</li>
                                            <li><i class="fas fa-check text-success mr-2"></i>Controle de pagamentos</li>
                                        </ul>
                                        <a href="/modulos/laboral/calculo_multas/calculo_multas_view.php" class="btn btn-warning btn-sm">
                                            <i class="fas fa-external-link-alt mr-1"></i> Acessar
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Gestão CTC -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card card-secondary card-outline">
                                    <div class="card-header">
                                        <h5 class="card-title">
                                            <i class="fas fa-briefcase mr-2"></i>
                                            Gestão CTC
                                        </h5>
                                        <div class="card-tools">
                                            <span class="badge badge-secondary">100%</span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <p class="card-text">
                                            Sistema de gestão de trabalho e controle de CTC dos internos.
                                        </p>
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-check text-success mr-2"></i>Controle de trabalhadores</li>
                                            <li><i class="fas fa-check text-success mr-2"></i>Gestão de pontos</li>
                                            <li><i class="fas fa-check text-success mr-2"></i>Relatórios CTC</li>
                                            <li><i class="fas fa-check text-success mr-2"></i>Acompanhamento</li>
                                        </ul>
                                        <a href="/modulos/laboral/gestao_ctc/gestao_ctc_view.php" class="btn btn-secondary btn-sm">
                                            <i class="fas fa-external-link-alt mr-1"></i> Acessar
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Canais de Notificação -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card card-primary card-outline">
                                    <div class="card-header">
                                        <h5 class="card-title">
                                            <i class="fas fa-bell mr-2"></i>
                                            Canais de Notificação
                                        </h5>
                                        <div class="card-tools">
                                            <span class="badge badge-primary">100%</span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <p class="card-text">
                                            Sistema completo de gerenciamento de canais de notificação do sistema.
                                        </p>
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-check text-success mr-2"></i>Configuração de canais</li>
                                            <li><i class="fas fa-check text-success mr-2"></i>Notificações automáticas</li>
                                            <li><i class="fas fa-check text-success mr-2"></i>Integração com sistemas</li>
                                            <li><i class="fas fa-check text-success mr-2"></i>Relatórios de envio</li>
                                        </ul>
                                        <a href="/modulos/servicos/canais/canais_view.php" class="btn btn-primary btn-sm">
                                            <i class="fas fa-external-link-alt mr-1"></i> Acessar
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Gestão de Contas -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card card-dark card-outline">
                                    <div class="card-header">
                                        <h5 class="card-title">
                                            <i class="fas fa-user-cog mr-2"></i>
                                            Gestão de Contas
                                        </h5>
                                        <div class="card-tools">
                                            <span class="badge badge-dark">100%</span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <p class="card-text">
                                            Sistema completo de gestão de usuários e permissões do SIGEP.
                                        </p>
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-check text-success mr-2"></i>Cadastro de usuários</li>
                                            <li><i class="fas fa-check text-success mr-2"></i>Controle de permissões</li>
                                            <li><i class="fas fa-check text-success mr-2"></i>Gestão de acessos</li>
                                            <li><i class="fas fa-check text-success mr-2"></i>Auditoria de segurança</li>
                                        </ul>
                                        <a href="/modulos/seguranca/gestao_contas/gestao_contas_view.php" class="btn btn-dark btn-sm">
                                            <i class="fas fa-external-link-alt mr-1"></i> Acessar
                                        </a>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Módulos Legado -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-history text-warning mr-2"></i>
                            Módulos Legado (Páginas Antigas)
                        </h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- Caminhões Pipa -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card card-warning card-outline">
                                    <div class="card-header">
                                        <h5 class="card-title">
                                            <i class="fas fa-truck mr-2"></i>
                                            Caminhões Pipa
                                        </h5>
                                        <div class="card-tools">
                                            <span class="badge badge-warning">100%</span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <p class="card-text">
                                            Sistema de controle e gestão de caminhões pipa do estabelecimento.
                                        </p>
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-check text-success mr-2"></i>Controle de viagens</li>
                                            <li><i class="fas fa-check text-success mr-2"></i>Registro de litros</li>
                                            <li><i class="fas fa-check text-success mr-2"></i>Histórico completo</li>
                                            <li><i class="fas fa-check text-success mr-2"></i>Relatórios</li>
                                        </ul>
                                        <a href="/paginas/caminhoes_pipa.php" class="btn btn-warning btn-sm">
                                            <i class="fas fa-external-link-alt mr-1"></i> Acessar
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Pecuário -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card card-success card-outline">
                                    <div class="card-header">
                                        <h5 class="card-title">
                                            <i class="fas fa-shopping-basket mr-2"></i>
                                            Pecuário
                                        </h5>
                                        <div class="card-tools">
                                            <span class="badge badge-success">100%</span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <p class="card-text">
                                            Sistema completo de gestão de itens e pecúlio dos internos.
                                        </p>
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-check text-success mr-2"></i>Cadastro de itens</li>
                                            <li><i class="fas fa-check text-success mr-2"></i>Controle de entregas</li>
                                            <li><i class="fas fa-check text-success mr-2"></i>Assinatura digital</li>
                                            <li><i class="fas fa-check text-success mr-2"></i>Relatórios</li>
                                        </ul>
                                        <a href="/paginas/peculio_gestao.php" class="btn btn-success btn-sm">
                                            <i class="fas fa-external-link-alt mr-1"></i> Acessar
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Extensão IPEN -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card card-info card-outline">
                                    <div class="card-header">
                                        <h5 class="card-title">
                                            <i class="fas fa-id-badge mr-2"></i>
                                            Extensão IPEN
                                        </h5>
                                        <div class="card-tools">
                                            <span class="badge badge-info">100%</span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <p class="card-text">
                                            Sistema de geração e extensão de números IPEN para identificação.
                                        </p>
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-check text-success mr-2"></i>Geração automática</li>
                                            <li><i class="fas fa-check text-success mr-2"></i>Controle sequencial</li>
                                            <li><i class="fas fa-check text-success mr-2"></i>Validação de dados</li>
                                            <li><i class="fas fa-check text-success mr-2"></i>Relatórios</li>
                                        </ul>
                                        <a href="/paginas/extensao_ipen.php" class="btn btn-info btn-sm">
                                            <i class="fas fa-external-link-alt mr-1"></i> Acessar
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Dossiê de Internos -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card card-secondary card-outline">
                                    <div class="card-header">
                                        <h5 class="card-title">
                                            <i class="fas fa-folder-open mr-2"></i>
                                            Dossiê de Internos
                                        </h5>
                                        <div class="card-tools">
                                            <span class="badge badge-secondary">100%</span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <p class="card-text">
                                            Sistema completo de gestão de dossiês e documentos dos internos.
                                        </p>
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-check text-success mr-2"></i>Documentos digitais</li>
                                            <li><i class="fas fa-check text-success mr-2"></i>Histórico completo</li>
                                            <li><i class="fas fa-check text-success mr-2"></i>Anexos e fotos</li>
                                            <li><i class="fas fa-check text-success mr-2"></i>Relatórios</li>
                                        </ul>
                                        <a href="/paginas/interno_dossie.php" class="btn btn-secondary btn-sm">
                                            <i class="fas fa-external-link-alt mr-1"></i> Acessar
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Downloads -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card card-dark card-outline">
                                    <div class="card-header">
                                        <h5 class="card-title">
                                            <i class="fas fa-download mr-2"></i>
                                            Downloads
                                        </h5>
                                        <div class="card-tools">
                                            <span class="badge badge-dark">100%</span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <p class="card-text">
                                            Área central de downloads de documentos e relatórios do sistema.
                                        </p>
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-check text-success mr-2"></i>Relatórios periódicos</li>
                                            <li><i class="fas fa-check text-success mr-2"></i>Documentos oficiais</li>
                                            <li><i class="fas fa-check text-success mr-2"></i>Formulários</li>
                                            <li><i class="fas fa-check text-success mr-2"></i>Manuais</li>
                                        </ul>
                                        <a href="/modulos/downloads/index.php" class="btn btn-dark btn-sm">
                                            <i class="fas fa-external-link-alt mr-1"></i> Acessar
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Perfil -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card card-primary card-outline">
                                    <div class="card-header">
                                        <h5 class="card-title">
                                            <i class="fas fa-user-circle mr-2"></i>
                                            Perfil do Usuário
                                        </h5>
                                        <div class="card-tools">
                                            <span class="badge badge-primary">100%</span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <p class="card-text">
                                            Área pessoal de configuração e gestão do perfil do usuário.
                                        </p>
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-check text-success mr-2"></i>Dados pessoais</li>
                                            <li><i class="fas fa-check text-success mr-2"></i>Alteração de senha</li>
                                            <li><i class="fas fa-check text-success mr-2"></i>Preferências</li>
                                            <li><i class="fas fa-check text-success mr-2"></i>Histórico de acesso</li>
                                        </ul>
                                        <a href="/paginas/perfil.php" class="btn btn-primary btn-sm">
                                            <i class="fas fa-external-link-alt mr-1"></i> Acessar
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Autenticação -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card card-danger card-outline">
                                    <div class="card-header">
                                        <h5 class="card-title">
                                            <i class="fas fa-shield-alt mr-2"></i>
                                            Autenticação
                                        </h5>
                                        <div class="card-tools">
                                            <span class="badge badge-danger">100%</span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <p class="card-text">
                                            Sistema completo de autenticação, sessão e segurança do SIGEP.
                                        </p>
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-check text-success mr-2"></i>Login seguro</li>
                                            <li><i class="fas fa-check text-success mr-2"></i>Controle de sessão</li>
                                            <li><i class="fas fa-check text-success mr-2"></i>Recuperação de senha</li>
                                            <li><i class="fas fa-check text-success mr-2"></i>Auditoria de acesso</li>
                                        </ul>
                                        <a href="/auth/login.php" class="btn btn-danger btn-sm">
                                            <i class="fas fa-external-link-alt mr-1"></i> Acessar
                                        </a>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Módulos em Desenvolvimento -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-hammer text-warning mr-2"></i>
                            Módulos em Desenvolvimento
                        </h3>
                        <div class="card-tools">
                            <div type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <!-- Controle de Estoque Moderno -->
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card card-primary card-outline">
                                        <div class="card-header">
                                            <h5 class="card-title">
                                                <i class="fas fa-warehouse mr-2"></i>
                                                Controle de Estoque (Moderno)
                                            </h5>
                                            <div class="card-tools">
                                                <span class="badge badge-primary"><?= $progresso_modulo ?>%</span>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <p class="card-text">
                                                Gestão completa de materiais e suprimentos do presídio com alertas automáticos.
                                            </p>
                                            <div class="progress mb-3">
                                                <div class="progress-bar bg-primary" role="progressbar"
                                                    style="width: <?= $progresso_modulo ?>%"
                                                    aria-valuenow="<?= $progresso_modulo ?>"
                                                    aria-valuemin="0"
                                                    aria-valuemax="100">
                                                    <?= $progresso_modulo ?>%
                                                </div>
                                            </div>
                                            <ul class="list-unstyled">
                                                <li><i class="fas fa-clock text-warning mr-2"></i>Controle de níveis</li>
                                                <li><i class="fas fa-check text-success mr-2"></i>Alertas automáticos</li>
                                                <li><i class="fas fa-check text-success mr-2"></i>Gestão de entradas</li>
                                                <li><i class="fas fa-times text-danger mr-2"></i>Relatórios avançados</li>
                                            </ul>
                                            <button class="btn btn-primary btn-sm" disabled>
                                                <i class="fas fa-tools mr-1"></i> Em Desenvolvimento
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Sistema de Relatórios Gerenciais -->
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card card-info card-outline">
                                        <div class="card-header">
                                            <h5 class="card-title">
                                                <i class="fas fa-chart-bar mr-2"></i>
                                                Relatórios Gerenciais
                                            </h5>
                                            <div class="card-tools">
                                                <span class="badge badge-info">40%</span>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <p class="card-text">
                                                Sistema completo de geração de relatórios gerenciais e operacionais.
                                            </p>
                                            <div class="progress mb-3">
                                                <div class="progress-bar bg-info" role="progressbar"
                                                    style="width: 40%"
                                                    aria-valuenow="40"
                                                    aria-valuemin="0"
                                                    aria-valuemax="100">
                                                    40%
                                                </div>
                                            </div>
                                            <ul class="list-unstyled">
                                                <li><i class="fas fa-check text-success mr-2"></i>Relatórios básicos</li>
                                                <li><i class="fas fa-clock text-warning mr-2"></i>Gráficos dinâmicos</li>
                                                <li><i class="fas fa-times text-danger mr-2"></i>Exportação avançada</li>
                                                <li><i class="fas fa-times text-danger mr-2"></i>Dashboard gerencial</li>
                                            </ul>
                                            <button class="btn btn-info btn-sm" disabled>
                                                <i class="fas fa-tools mr-1"></i> Em Desenvolvimento
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Sistema de Backup Automatizado -->
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card card-warning card-outline">
                                        <div class="card-header">
                                            <h5 class="card-title">
                                                <i class="fas fa-database mr-2"></i>
                                                Backup Automatizado
                                            </h5>
                                            <div class="card-tools">
                                                <span class="badge badge-warning">30%</span>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <p class="card-text">
                                                Sistema automatizado de backup e restauração de dados críticos.
                                            </p>
                                            <div class="progress mb-3">
                                                <div class="progress-bar bg-warning" role="progressbar"
                                                    style="width: 30%"
                                                    aria-valuenow="30"
                                                    aria-valuemin="0"
                                                    aria-valuemax="100">
                                                    30%
                                                </div>
                                            </div>
                                            <ul class="list-unstyled">
                                                <li><i class="fas fa-check text-success mr-2"></i>Backup manual</li>
                                                <li><i class="fas fa-clock text-warning mr-2"></i>Agendamento</li>
                                                <li><i class="fas fa-times text-danger mr-2"></i>Restauração</li>
                                                <li><i class="fas fa-times text-danger mr-2"></i>Monitoramento</li>
                                            </ul>
                                            <button class="btn btn-warning btn-sm" disabled>
                                                <i class="fas fa-tools mr-1"></i> Em Desenvolvimento
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- App Mobile SIGEP -->
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card card-success card-outline">
                                        <div class="card-header">
                                            <h5 class="card-title">
                                                <i class="fas fa-mobile-alt mr-2"></i>
                                                App Mobile SIGEP
                                            </h5>
                                            <div class="card-tools">
                                                <span class="badge badge-success">20%</span>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <p class="card-text">
                                                Aplicativo mobile para acesso remoto às funcionalidades principais do SIGEP.
                                            </p>
                                            <div class="progress mb-3">
                                                <div class="progress-bar bg-success" role="progressbar"
                                                    style="width: 20%"
                                                    aria-valuenow="20"
                                                    aria-valuemin="0"
                                                    aria-valuemax="100">
                                                    20%
                                                </div>
                                            </div>
                                            <ul class="list-unstyled">
                                                <li><i class="fas fa-check text-success mr-2"></i>Design UI/UX</li>
                                                <li><i class="fas fa-clock text-warning mr-2"></i>Desenvolvimento API</li>
                                                <li><i class="fas fa-times text-danger mr-2"></i>Integração</li>
                                                <li><i class="fas fa-times text-danger mr-2"></i>Publicação</li>
                                            </ul>
                                            <button class="btn btn-success btn-sm" disabled>
                                                <i class="fas fa-tools mr-1"></i> Em Desenvolvimento
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Dashboard Analytics -->
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card card-secondary card-outline">
                                        <div class="card-header">
                                            <h5 class="card-title">
                                                <i class="fas fa-chart-line mr-2"></i>
                                                Dashboard Analytics
                                            </h5>
                                            <div class="card-tools">
                                                <span class="badge badge-secondary">15%</span>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <p class="card-text">
                                                Sistema analítico avançado com métricas e KPIs do sistema.
                                            </p>
                                            <div class="progress mb-3">
                                                <div class="progress-bar bg-secondary" role="progressbar"
                                                    style="width: 15%"
                                                    aria-valuenow="15"
                                                    aria-valuemin="0"
                                                    aria-valuemax="100">
                                                    15%
                                                </div>
                                            </div>
                                            <ul class="list-unstyled">
                                                <li><i class="fas fa-check text-success mr-2"></i>Métricas básicas</li>
                                                <li><i class="fas fa-clock text-warning mr-2"></i>KPIs avançados</li>
                                                <li><i class="fas fa-times text-danger mr-2"></i>Tempo real</li>
                                                <li><i class="fas fa-times text-danger mr-2"></i>Previsões</li>
                                            </ul>
                                            <button class="btn btn-secondary btn-sm" disabled>
                                                <i class="fas fa-tools mr-1"></i> Em Desenvolvimento
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Integração com Sistemas Externos -->
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card card-dark card-outline">
                                        <div class="card-header">
                                            <h5 class="card-title">
                                                <i class="fas fa-plug mr-2"></i>
                                                Integração Externa
                                            </h5>
                                            <div class="card-tools">
                                                <span class="badge badge-dark">10%</span>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <p class="card-text">
                                                Sistema de integração com APIs e sistemas externos de gestão prisional.
                                            </p>
                                            <div class="progress mb-3">
                                                <div class="progress-bar bg-dark" role="progressbar"
                                                    style="width: 10%"
                                                    aria-valuenow="10"
                                                    aria-valuemin="0"
                                                    aria-valuemax="100">
                                                    10%
                                                </div>
                                            </div>
                                            <ul class="list-unstyled">
                                                <li><i class="fas fa-check text-success mr-2"></i>Estudo de APIs</li>
                                                <li><i class="fas fa-clock text-warning mr-2"></i>Desenvolvimento REST</li>
                                                <li><i class="fas fa-times text-danger mr-2"></i>Webhooks</li>
                                                <li><i class="fas fa-times text-danger mr-2"></i>Documentação</li>
                                            </ul>
                                            <button class="btn btn-dark btn-sm" disabled>
                                                <i class="fas fa-tools mr-1"></i> Em Desenvolvimento
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Progresso Geral do Sistema -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-chart-line text-primary mr-2"></i>
                            Progresso Geral do Sistema
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <h5>Implementação Geral do SIGEP</h5>
                                <div class="progress mb-3" style="height: 25px;">
                                    <div class="progress-bar bg-success progress-bar-striped progress-bar-animated"
                                        role="progressbar"
                                        style="width: <?= $progresso_geral ?>%"
                                        aria-valuenow="<?= $progresso_geral ?>"
                                        aria-valuemin="0"
                                        aria-valuemax="100">
                                        <?= $progresso_geral ?>% Completo
                                    </div>
                                </div>
                                <p class="text-muted">
                                    <i class="fas fa-info-circle mr-2"></i>
                                    O sistema SIGEP está em constante desenvolvimento para melhorar a gestão prisional.
                                    Atualmente focamos no módulo <strong><?= $modulo_atual ?></strong>.
                                </p>
                            </div>
                            <div class="col-md-4">
                                <div class="info-box">
                                    <span class="info-box-icon bg-info"><i class="fas fa-calendar-alt"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Previsão de Conclusão</span>
                                        <span class="info-box-number">Dez/2026</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tecnologias Utilizadas -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-code text-info mr-2"></i>
                            Stack Tecnológico
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-2 col-6 mb-3">
                                <div class="tech-badge">
                                    <i class="fab fa-php fa-3x text-primary mb-2"></i>
                                    <h6>PHP 8.2</h6>
                                    <small>Backend Principal</small>
                                </div>
                            </div>
                            <div class="col-md-2 col-6 mb-3">
                                <div class="tech-badge">
                                    <i class="fas fa-database fa-3x text-success mb-2"></i>
                                    <h6>MySQL 8.0</h6>
                                    <small>Banco de Dados</small>
                                </div>
                            </div>
                            <div class="col-md-2 col-6 mb-3">
                                <div class="tech-badge">
                                    <i class="fab fa-html5 fa-3x text-warning mb-2"></i>
                                    <h6>AdminLTE 4</h6>
                                    <small>Frontend Framework</small>
                                </div>
                            </div>
                            <div class="col-md-2 col-6 mb-3">
                                <div class="tech-badge">
                                    <i class="fab fa-js fa-3x text-info mb-2"></i>
                                    <h6>JavaScript</h6>
                                    <small>Interatividade</small>
                                </div>
                            </div>
                            <div class="col-md-2 col-6 mb-3">
                                <div class="tech-badge">
                                    <i class="fab fa-css3-alt fa-3x text-danger mb-2"></i>
                                    <h6>CSS3</h6>
                                    <small>Estilização</small>
                                </div>
                            </div>
                            <div class="col-md-2 col-6 mb-3">
                                <div class="tech-badge">
                                    <i class="fas fa-lock fa-3x text-secondary mb-2"></i>
                                    <h6>Segurança</h6>
                                    <small>Prepared Statements</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Botão de Retorno -->
        <div class="row">
            <div class="col-12 text-center">
                <a href="/" class="btn btn-primary btn-lg">
                    <i class="fas fa-th-large mr-2"></i>
                    Voltar para o Dashboard
                </a>
            </div>
        </div>

    </div>
</section>

<!-- CSS Customizado -->
<style>
    .tech-badge {
        padding: 20px;
        border-radius: 10px;
        background: rgba(0, 0, 0, 0.02);
        transition: all 0.3s ease;
    }

    .tech-badge:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }

    .card-outline {
        border-top: 3px solid;
    }

    .card-success.card-outline {
        border-top-color: #28a745;
    }

    .card-info.card-outline {
        border-top-color: #17a2b8;
    }

    .card-warning.card-outline {
        border-top-color: #ffc107;
    }

    .card-danger.card-outline {
        border-top-color: #dc3545;
    }

    .card-secondary.card-outline {
        border-top-color: #6c757d;
    }

    .card-dark.card-outline {
        border-top-color: #343a40;
    }

    .card-primary.card-outline {
        border-top-color: #007bff;
    }

    .progress {
        background-color: #e9ecef;
    }

    .badge {
        font-size: 0.75rem;
    }

    .list-unstyled li {
        padding: 2px 0;
    }

    .card-body .btn {
        transition: all 0.3s ease;
    }

    .card-body .btn:hover {
        transform: translateY(-2px);
    }

    @media (max-width: 768px) {
        .tech-badge {
            margin-bottom: 15px;
        }
    }
</style>

<!-- JavaScript -->
<script>
    // Proteção contra múltiplos carregamentos no SPA
    if (typeof window.modulosDesenvolvimentoLoaded === 'undefined') {
        window.modulosDesenvolvimentoLoaded = true;

        $(document).ready(function() {
            // Animação dos cards
            $('.card').hover(
                function() {
                    $(this).addClass('shadow-lg');
                },
                function() {
                    $(this).removeClass('shadow-lg');
                }
            );

            // Animação dos badges de progresso
            $('.progress-bar').each(function() {
                const width = $(this).attr('aria-valuenow');
                $(this).css('width', '0%');
                setTimeout(() => {
                    $(this).css('width', width + '%');
                }, 100);
            });

            // Tooltip nos cards
            $('[data-toggle="tooltip"]').tooltip();

            console.log("SIGEP - Módulos em Desenvolvimento carregado com sucesso!");
        });
    }
</script>
