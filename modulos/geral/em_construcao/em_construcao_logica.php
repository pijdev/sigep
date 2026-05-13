<?php

/**
 * Em Construção - Lógica
 * SIGEP - Sistema de Gestão Penitenciária
 *
 * @author Cascade AI
 * @version 2.0
 * @since 2026-03-25
 */

// Configurações da página
$ano = date('Y');
$modulo_atual = 'Controle de Estoque';
$progresso_modulo = 25;
$progresso_geral = 65;

// Módulos implementados no SIGEP (módulos modernos)
$modulos_implementados = [
    'Painel de Internos',
    'Cadastro de Internos',
    'Recebimento de Eletrônicos',
    'Rouparia',
    'Censura de Cartas',
    'Eclusa',
    'Medidas Disciplinares',
    'Gestão de Colchões',
    'Kits LGBT',
    'Cálculo de Multas',
    'Gestão CTC',
    'Canais de Notificação',
    'Gestão de Contas'
];

// Módulos legado (em /paginas)
$modulos_legado = [
    'Caminhões Pipa',
    'Controle de Estoque (Censura)',
    'Extensão IPEN',
    'Dossiê de Internos',
    'Solicitações de Colchões',
    'Doação de Eletrônicos',
    'Gestão de Eletrônicos',
    'Recebimento de Cosméticos',
    'Recebimento de Livros',
    'Pecuário',
    'Assinatura de Pecuário',
    'Downloads',
    'Perfil',
    'Processamento Financeiro',
    'Autenticação e Sessão'
];

// Módulos em desenvolvimento
$modulos_desenvolvimento = [
    'Controle de Estoque (Moderno)',
    'Sistema de Relatórios Gerenciais',
    'Sistema de Backup Automatizado',
    'App Mobile SIGEP',
    'Dashboard Analytics',
    'Integração com Sistemas Externos'
];
