<?php
/**
 * Configuração do Sistema de Ofício - Versão Modelo CENSURA/PIJ
 * 
 * Este arquivo contém os dados específicos para o formato do ofício
 * baseado no modelo fornecido pelo usuário.
 */

// Dados do Presídio para Geração de Ofício - Modelo CENSURA/PIJ
return [
    'nome' => 'PENITENCIARIA INDUSTRIAL JUCEMAR CESCONETO',
    'estado' => 'ESTADO DE SANTA CATARINA',
    'responsavel' => 'Mateus Longareti',
    'cargo' => 'Monitor de Ressocialização',
    'setor' => 'Setor Logística e Censura',
    'empresa' => 'Soluções Serviços Terceirizadas Eireli',
    'cidade' => 'Joinville',
    'estado_sigla' => 'SC',
    
    // Assinaturas adicionais
    'assinaturas' => [
        [
            'nome' => 'Denis Willian Ribeiro',
            'cargo' => 'Monitor de Ressocialização',
            'setor' => 'Setor Logística e Censura',
            'empresa' => 'Soluções Serviços Terceirizadas Eireli',
            'cidade' => 'Joinville',
            'estado_sigla' => 'SC'
        ],
        [
            'nome' => 'José Luiz Ferreira',
            'cargo' => 'Coordenador de Ressocialização Prisional',
            'empresa' => 'Soluções Serviços Terceirizadas Eireli',
            'cidade' => 'Joinville',
            'estado_sigla' => 'SC',
            'destinatario' => true
        ],
        [
            'nome' => 'Diego Rafael Martins',
            'cargo' => 'Supervisor de Ressocialização Prisional',
            'empresa' => 'Soluções Serviços Terceirizadas Eireli',
            'cidade' => 'Joinville',
            'estado_sigla' => 'SC',
            'destinatario' => true
        ]
    ],
    
    // Cabeçalho oficial
    'cabecalho' => [
        'estado' => 'ESTADO DE SANTA CATARINA',
        'secretaria' => 'SECRETARIA DE ESTADO DE JUSTIÇA E REINTEGRAÇÃO SOCIAL',
        'policia' => 'POLÍCIA PENAL',
        'superintendencia' => 'SUPERINTENDÊNCIA REGIONAL NORTE—SR03',
        'penitenciaria' => 'PENITENCIARIA INDUSTRIAL JUCEMAR CESCONETO'
    ],
    
    // Rodapé oficial
    'rodape' => [
        'instituicao' => 'POLÍCIA PENAL DE SANTA CATARINA',
        'endereco' => 'Rua Servidão Antônio Delgmann Júnior, n. º 245 – Bairro Parque Guarani – CEP 89.209-240 – Joinville/SC',
        'telefone' => '(47) 3481-3988',
        'email' => 'pij_coordenacao@solucoesterceirizadas.com.br'
    ]
];

// NOTA: Para personalizar, altere os valores acima conforme necessário.
// O sistema usará estes dados automaticamente na geração do ofício.
?>
