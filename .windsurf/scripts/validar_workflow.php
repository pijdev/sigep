<?php
/**
 * Validador do ArquitetoSIGEP
 * Verifica integridade do workflow e arquivos críticos
 */

function validarWorkflow() {
    $resultados = [];
    $base_path = __DIR__ . '/..';
    
    // 1. Verificar arquivos críticos
    $arquivos_criticos = [
        'workflows/ArquitetoSIGEP.md' => 'Workflow principal',
        'architecture/visao_geral.md' => 'Visão geral',
        'architecture/stack_tecnologico.md' => 'Stack tecnológico',
        'architecture/index.md' => 'Estrutura do código',
        'architecture/database/schema_completo.md' => 'Schema do banco',
        'scripts/coletar_schema.php' => 'Coletor de schema'
    ];
    
    foreach ($arquivos_criticos as $arquivo => $descricao) {
        $caminho = $base_path . '/' . $arquivo;
        $resultados['arquivos'][$arquivo] = [
            'descricao' => $descricao,
            'existe' => file_exists($caminho),
            'tamanho' => file_exists($caminho) ? filesize($caminho) : 0
        ];
    }
    
    // 2. Verificar skills
    $skills_dir = $base_path . '/skills';
    if (is_dir($skills_dir)) {
        $skills = scandir($skills_dir);
        $skills = array_diff($skills, ['.', '..']);
        $resultados['skills'] = array_values($skills);
    }
    
    // 3. Testar coletor de schema
    if (file_exists($base_path . '/scripts/coletar_schema.php')) {
        ob_start();
        include $base_path . '/scripts/coletar_schema.php';
        $resultado_schema = ob_get_clean();
        $resultados['schema'] = [
            'funcionando' => strpos($resultado_schema, '✅') !== false,
            'resultado' => $resultado_schema
        ];
    }
    
    // 4. Gerar relatório
    $relatorio = "# 🔍 Relatório de Validação - ArquitetoSIGEP\n\n";
    $relatorio .= "**Gerado em**: " . date('Y-m-d H:i:s') . "\n\n";
    
    // Status dos arquivos
    $relatorio .= "## 📁 Status dos Arquivos Críticos\n\n";
    foreach ($resultados['arquivos'] as $arquivo => $info) {
        $status = $info['existe'] ? '✅' : '❌';
        $tamanho = $info['existe'] ? " ({$info['tamanho']} bytes)" : '';
        $relatorio .= "- {$status} **{$info['descricao']}**: `{$arquivo}`{$tamanho}\n";
    }
    
    // Skills disponíveis
    if (!empty($resultados['skills'])) {
        $relatorio .= "\n## 🛠️ Skills Disponíveis\n\n";
        foreach ($resultados['skills'] as $skill) {
            $relatorio .= "- ✅ `{$skill}`\n";
        }
    }
    
    // Schema test
    if (isset($resultados['schema'])) {
        $status_schema = $resultados['schema']['funcionando'] ? '✅' : '❌';
        $relatorio .= "\n## 🗄️ Teste do Coletor de Schema\n\n";
        $relatorio .= "{$status_schema} " . $resultados['schema']['resultado'] . "\n";
    }
    
    // Salvar relatório
    file_put_contents($base_path . '/workflow_validation_report.md', $relatorio);
    
    return $relatorio;
}

echo validarWorkflow();
