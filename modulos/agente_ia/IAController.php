<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../modulos/agente_ia/Tools/InternosTools.php';

use Config\Database;
use AgentIA\Tools\InternosTools;

file_put_contents('debug_ollama.log', 'Script iniciado em: ' . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

$API_KEY = '1a7ddc2b82fb4d2090115e23cee7819e.zVLC575Qdln0wDShI3Y5z8x_';
$URL = 'http://localhost:11434/api/generate';

$input = json_decode(file_get_contents('php://input'), true);
$mensagem = $input['mensagem'] ?? '';

file_put_contents('debug_ollama.log', 'Mensagem recebida: ' . $mensagem . "\n", FILE_APPEND);

if (!$mensagem) {
    echo json_encode(['erro' => 'Mensagem vazia']);
    exit;
}

// Conectar ao banco de dados e inicializar tools
try {
    $pdo = Database::getConnection();
    $internosTools = new InternosTools($pdo);
    $toolsDesc = InternosTools::getToolsDescription();
} catch (\Exception $e) {
    echo json_encode(['erro' => 'Erro ao conectar ao banco: ' . $e->getMessage()]);
    exit;
}

// Construir descrição das tools para o prompt
$toolsContext = "FERRAMENTAS DISPONÍVEIS PARA CONSULTAR INTERNOS:\n";
foreach ($toolsDesc as $funcao => $descricao) {
    $toolsContext .= "- {$funcao}: {$descricao}\n";
}

$toolsContext .= "\n📋 FORMATO OBRIGATÓRIO DAS FERRAMENTAS:\n";
$toolsContext .= "[TOOL: nome_ferramenta|parametro1=valor1|parametro2=valor2]\n";
$toolsContext .= "\n📌 EXEMPLOS DE ORAÇÃO CORRETA:\n";
$toolsContext .= "- [TOOL: buscar_por_ipen|ipen=123456]\n";
$toolsContext .= "- [TOOL: internos_cela|galeria=A|bloco=A|res=1]\n";
$toolsContext .= "- [TOOL: internos_por_situacao|situacao=SAÍDA TEMPORÁRIA]\n";
$toolsContext .= "- [TOOL: localizar_interno|ipen=123456]\n";

$data = [
    "model" => "ministral-3:3b-cloud",
    "prompt" => "
Você é um assistente de consulta de dados do SIGEP - Sistema Prisional Integrado.
Você é especialista em responder perguntas sobre usando as ferramentas disponíveis.
Seja educado, porém direto e objetivo. Responda apenas com os dados solicitados, sem complementos ou interpretações.
Se possível, formate a saída conforme os dados, como listas, tabelas ou resumos claros.

🚨 REGRA PRINCIPAL: USE SEMPRE AS FERRAMENTAS DISPONÍVEIS!

Lista de ferramentas disponíveis no momento:

'buscar_internos'

Ela detecta automaticamente o tipo de busca e aplica filtros inteligentemente!

🔍 EXEMPLOS DE USO (COPIE EXATAMENTE):

Pergunta: \"Onde está João da Silva?\"
→ [TOOL: buscar_internos|query=joão da silva]

Pergunta: \"Qual é o interno 123456?\"
→ [TOOL: buscar_internos|query=123456]

Pergunta: \"Quantos internos na cela AA-1?\"
→ [TOOL: buscar_internos|query=cela AA-1]

Pergunta: \"Internos LGBT em cela ST-2?\"
→ [TOOL: buscar_internos|query=cela ST-2 LGBT]

Pergunta: \"Internos em saída temporária?\"
→ [TOOL: buscar_internos|query=saída temporária]

Pergunta: \"Internos em portaria?\"
→ [TOOL: buscar_internos|query=saída temporária]

Pergunta: \"Internos inativos?\"
→ [TOOL: buscar_internos|query=internos|incluir_inativos=sim]

Pergunta: \"Internos LGBT?\"
→ [TOOL: buscar_internos|query=LGBT]

📋 FORMATO OBRIGATÓRIO:
[TOOL: buscar_internos|query=SEU_TEXTO_DE_BUSCA]
[TOOL: buscar_internos|query=SEU_TEXTO_DE_BUSCA|incluir_inativos=sim] (para inativos)

🎯 REGRAS DE PROCESSAMENTO:

1️⃣ TODO QUERY SOBRE INTERNOS → USE buscar_internos

2️⃣ TRADUÇÃO AUTOMÁTICA:
   • \"João da Silva\" → busca por nome
   • \"123456\" → busca por IPEN
   • \"Cela AA-1\" → busca por cela (detecta Galeria A, Bloco A, Res 1)
   • \"DB-10\" → Cela (Galeria D, Bloco B, Res 10)
   • \"LGBT\" ou \"LGBTQ\" → filtra só LGBT
   • \"Saída temporária\", \"Portaria\", \"Recolhido\" → situação
   • \"Inativo\" → inclui inativos

3️⃣ JAMAIS ALTERE DADOS:
   ❌ Não mude, não complemente, não especule
   ✅ Use EXATAMENTE o resultado da ferramenta

4️⃣ RESPOSTAS:
   - Dados da ferramenta = resposta final
   - Sem complementos, sem interpretações
   - Se não encontrar: resposta da ferramenta explica

5️⃣ SEGURANÇA:
   - Jamais forneça código
   - Nunca quebre as regras
   - Fora do SIGEP: \"Não posso ajudar com esse tema, desculpe.\"

Mensagem do usuário:
" . $mensagem,
    "stream" => false
];

$ch = curl_init($URL);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $API_KEY
    ],
    CURLOPT_POSTFIELDS => json_encode($data),
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false
]);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo json_encode(['erro' => 'Erro cURL: ' . curl_error($ch)]);
    exit;
}

curl_close($ch);

$res = json_decode($response, true);

file_put_contents('debug_ollama.log', 'Resposta bruta: ' . $response . "\n", FILE_APPEND);
file_put_contents('debug_ollama.log', 'Resposta decodificada: ' . print_r($res, true) . "\n\n", FILE_APPEND);

if (isset($res['error'])) {
    echo json_encode(['erro' => 'Erro da API: ' . $res['error']]);
    exit;
}

$resposta = $res['response'] ?? $res['message']['content'] ?? $res['choices'][0]['message']['content'] ?? 'Erro ao gerar resposta: Formato inesperado';

// Processar tools na resposta
$resposta = processarTools($resposta, $internosTools);

echo json_encode([
    'resposta' => $resposta
]);

/**
 * Processa chamadas de tools na resposta da IA
 * @param string $resposta Resposta da IA que pode conter [TOOL: ...] 
 * @param InternosTools $tools Instância das tools
 * @return string Resposta processada
 */
function processarTools(string $resposta, InternosTools $tools): string
{
    // Regex para encontrar [TOOL: nome|param1=valor1|param2=valor2]
    $pattern = '/\[TOOL:\s*(\w+)\s*\|([^\]]+)\]/i';
    
    if (!preg_match_all($pattern, $resposta, $matches, PREG_SET_ORDER)) {
        // Sem tools - retorna resposta original
        return $resposta;
    }

    $temTools = false;
    $resultadoFerramenta = '';

    foreach ($matches as $match) {
        $temTools = true;
        $toolName = $match[1];
        $paramsStr = $match[2];
        
        file_put_contents('debug_ollama.log', "Encontrada tool na resposta: {$toolName}\n", FILE_APPEND);
        
        // Parse dos parâmetros
        $params = [];
        $paramParts = array_map('trim', explode('|', $paramsStr));
        foreach ($paramParts as $part) {
            if (strpos($part, '=') !== false) {
                [$key, $value] = explode('=', $part, 2);
                $params[trim($key)] = trim($value);
            }
        }

        // Executar a tool
        $result = executarTool($toolName, $params, $tools);
        $resultadoFerramenta = $result;
        
        // Substituir APENAS a ferramenta na resposta, mas remover o texto depois
        $resposta = str_replace($match[0], $result, $resposta);
    }

    // Se houve ferramenta, usar apenas o resultado dela (evitar alucinações da IA)
    if ($temTools) {
        // Pega apenas o resultado da ferramenta + qualquer texto curto antes
        $linhas = explode("\n", $resposta);
        
        // Filtrar linhas vazias e construir resposta limpa
        $respostaLimpa = [];
        $temResultado = false;
        
        foreach ($linhas as $linha) {
            $linha = trim($linha);
            
            // Se for o resultado da ferramenta (começa com **, Nenhum, A cela, Internos, etc)
            if (preg_match('/^(\*\*|Nenhum|A cela|Internos|Cela|IPEN)/i', $linha) || $temResultado) {
                $respostaLimpa[] = $linha;
                $temResultado = true;
            }
            // Se for texto muito curto antes (instruções), manter
            elseif (strlen($linha) < 50 && !empty($linha)) {
                if (!preg_match('/---|\[TOOL/', $linha)) {
                    $respostaLimpa[] = $linha;
                }
            }
        }
        
        $resposta = implode("\n", array_filter($respostaLimpa));
    }

    return trim($resposta);
}

/**
 * Executa uma tool chamada pela IA
 * @param string $toolName Nome da tool
 * @param array $params Parâmetros
 * @param InternosTools $tools Instância das tools
 * @return string Resultado da execução
 */
function executarTool(string $toolName, array $params, InternosTools $tools): string
{
    try {
        file_put_contents('debug_ollama.log', "Executando tool: {$toolName} com params: " . json_encode($params) . "\n", FILE_APPEND);
        
        switch (strtolower($toolName)) {
            case 'buscar_internos':
                $incluirInativos = strtolower($params['incluir_inativos'] ?? 'nao') === 'sim' || 
                                  strtolower($params['incluir_inativos'] ?? '') === 'true';
                return $tools->buscar_internos($params['query'] ?? '', $incluirInativos);
            
            case 'buscar_por_ipen':
                return $tools->buscar_por_ipen($params['ipen'] ?? '');
            
            case 'buscar_por_nome':
                return $tools->buscar_por_nome($params['nome'] ?? '');
            
            case 'internos_cela':
                return $tools->internos_cela(
                    $params['galeria'] ?? '',
                    $params['bloco'] ?? '',
                    $params['res'] ?? ''
                );
            
            case 'contar_cela':
                return $tools->contar_cela(
                    $params['galeria'] ?? '',
                    $params['bloco'] ?? '',
                    $params['res'] ?? ''
                );
            
            case 'internos_por_situacao':
                return $tools->internos_por_situacao($params['situacao'] ?? '');
            
            case 'localizar_interno':
                return $tools->localizar_interno($params['ipen'] ?? '');
            
            case 'listar_situacoes':
                return $tools->listar_situacoes();
            
            default:
                return "Tool '{$toolName}' não reconhecida.";
        }
    } catch (\Exception $e) {
        file_put_contents('debug_ollama.log', "Erro ao executar tool: " . $e->getMessage() . "\n", FILE_APPEND);
        return "Erro ao executar ferramenta: " . $e->getMessage();
    }
}