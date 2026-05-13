<?php

/**
 * Debug de Registro Específico - Interno ID
 *
 * Analisa dados brutos e parseados de um registro
 * específico para identificar problemas
 * Mostra informações detalhadas para debugging
 *
 * @category Scripts SIGEP
 * @package Debugging
 * @author SIGEP Team
 * @version 1.0
 * @since 2024-03-25
 *
 * Uso: php debug_registro_especifico.php [id_interno] [opcoes]
 * Opções:
 *   --json: Saída em formato JSON
 *   --completo: Mostra informações completas
 *   --banco: Consulta diretamente no banco
 */

set_time_limit(0);
ini_set('memory_limit', '512M');
date_default_timezone_set('America/Sao_Paulo');

// Argumentos da linha de comando
$options = getopt('', ['json', 'completo', 'banco']);
$saidaJSON = isset($options['json']);
$saidaCompleta = isset($options['completo']);
$usarBancoDireto = isset($options['banco']);

// Configuração
$idInterno = $argv[1] ?? null;
$arquivoCSV = 'C:/Servicos/ConsultaUnidades/RELATORIO_ESTADUAL_COMPLETO_304321_MAXIMO.csv';

// Funções utilitárias
function logMessage($message, $level = 'INFO')
{
    $timestamp = date('Y-m-d H:i:s');
    echo "[$timestamp] [$level] $message\n";
}

function formatarCPF($cpf)
{
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    if (strlen($cpf) === 11) {
        return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf);
    }
    return $cpf;
}

function formatarData($data)
{
    if (empty($data)) return '';

    $data = trim($data);

    $formatos = ['d/m/Y', 'Y-m-d', 'd/m/Y H:i:s', 'Y-m-d H:i:s'];

    foreach ($formatos as $formato) {
        $date = DateTime::createFromFormat($formato, $data);
        if ($date !== false) {
            return $date->format('d/m/Y');
        }
    }

    return $data;
}

function limparTexto($texto)
{
    if (empty($texto)) return '';

    // Remover caracteres especiais, espaços extras
    $texto = trim($texto);
    $texto = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $texto);
    $texto = preg_replace('/\s+/', ' ', $texto);

    return $texto;
}

function validarCPF($cpf)
{
    $cpf = preg_replace('/[^0-9]/', '', $cpf);

    if (strlen($cpf) != 11) {
        return false;
    }

    // Verificar se todos os dígitos são iguais
    if (preg_match('/^(\d)\1{10}$/', $cpf)) {
        return false;
    }

    // Validação básica do CPF
    return true;
}

// Classe para debugging
class DebugRegistroEspecifico
{
    private $pdo;
    private $registroCSV;
    private $registroBanco;
    private $idInterno;
    private $arquivoCSV;
    private $saidaJSON;
    private $saidaCompleta;
    private $usarBancoDireto;
    private $comparacao;

    public function __construct($config = null, $arquivoCSV = null, $saidaJSON = false, $saidaCompleta = false, $usarBancoDireto = false)
    {
        $this->arquivoCSV = $arquivoCSV;
        $this->saidaJSON = $saidaJSON;
        $this->saidaCompleta = $saidaCompleta;
        $this->usarBancoDireto = $usarBancoDireto;

        if ($config && $this->usarBancoDireto) {
            // Conectar ao banco SIGEP
            $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
            $this->pdo = new PDO($dsn, $config['user'], $config['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);

            $this->pdo->exec("SET time_zone = '-03:00'");
        }
    }

    public function debugPorID($idInterno)
    {
        $this->idInterno = $idInterno;

        logMessage("Iniciando debug do registro ID: $idInterno");

        // Tentar encontrar no CSV
        $this->buscarNoCSV();

        // Se tiver banco configurado, buscar no banco também
        if ($this->pdo) {
            $this->buscarNoBanco();
        }

        $this->compararRegistros();
        $this->mostrarResultados();
    }

    private function buscarNoCSV()
    {
        logMessage("Buscando registro no arquivo CSV...");

        if (!file_exists($this->arquivoCSV)) {
            logMessage("ERRO: Arquivo CSV não encontrado: $this->arquivoCSV", 'ERROR');
            return;
        }

        $handle = fopen($this->arquivoCSV, 'r');
        if (!$handle) {
            logMessage("ERRO: Não foi possível abrir o arquivo CSV", 'ERROR');
            return;
        }

        // Pular cabeçalho
        fgets($handle);

        $linhaAtual = 0;
        while (($linha = fgets($handle)) !== false) {
            $linhaAtual++;

            $dados = str_getcsv($linha, ';', '"', '\\');

            if (count($dados) >= 1) {
                $idCSV = trim($dados[0]);

                if ($idCSV == $this->idInterno) {
                    $this->registroCSV = [
                        'linha' => $linhaAtual,
                        'dados_brutos' => $linha,
                        'dados_parseados' => $dados
                    ];

                    logMessage("Registro encontrado na linha $linhaAtual do CSV");
                    fclose($handle);
                    return;
                }
            }
        }

        fclose($handle);
        logMessage("AVISO: Registro ID {$this->idInterno} não encontrado no arquivo CSV", 'WARNING');
    }

    private function buscarNoBanco()
    {
        logMessage("Buscando registro no banco de dados...");

        try {
            $stmt = $this->pdo->prepare("
                SELECT i.*, u.nome as unidade_nome
                FROM internos i
                LEFT JOIN unidades u ON i.unidade_id = u.id
                WHERE i.id = ?
            ");

            $stmt->execute([$this->idInterno]);
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($resultado) {
                $this->registroBanco = $resultado;
                logMessage("Registro encontrado no banco de dados");
            } else {
                logMessage("AVISO: Registro ID {$this->idInterno} não encontrado no banco de dados", 'WARNING');
            }
        } catch (Exception $e) {
            logMessage("ERRO: Falha na consulta ao banco: " . $e->getMessage(), 'ERROR');
        }
    }

    private function compararRegistros()
    {
        logMessage("Comparando informações entre CSV e banco...");

        if (!$this->registroCSV || !$this->registroBanco) {
            logMessage("AVISO: Impossível comparar - dados incompletos", 'WARNING');
            return;
        }

        $comparacao = [];

        // Comparar campos principais
        $camposCSV = array_slice($this->registroCSV['dados_parseados'], 0, 6);
        $camposBanco = [
            $this->registroBanco['prontuario'] ?? '',
            $this->registroBanco['nome'] ?? '',
            $this->registroBanco['cpf'] ?? '',
            $this->registroBanco['data_nascimento'] ?? '',
            $this->registroBanco['situacao'] ?? '',
            $this->registroBanco['unidade_id'] ?? ''
        ];

        foreach ($camposCSV as $i => $valorCSV) {
            $valorBanco = $camposBanco[$i] ?? '';

            $valorCSV = trim($valorCSV);
            $valorBanco = trim($valorBanco);

            $campoNome = $this->getNomeCampo($i);

            $diferente = $valorCSV !== $valorBanco;
            $igual = $valorCSV === $valorBanco;

            $comparacao[] = [
                'campo' => $campoNome,
                'valor_csv' => $valorCSV,
                'valor_banco' => $valorBanco,
                'diferente' => $diferente,
                'igual' => $igual
            ];
        }

        $this->comparacao = $comparacao;
    }

    private function getNomeCampo($indice)
    {
        $nomes = [
            0 => 'prontuario',
            1 => 'nome',
            2 => 'cpf',
            3 => 'data_nascimento',
            4 => 'situacao',
            5 => 'unidade_id'
        ];

        return $nomes[$indice] ?? "campo_$indice";
    }

    private function mostrarResultados()
    {
        if ($this->saidaJSON) {
            $this->mostrarResultadosJSON();
        } else {
            $this->mostrarResultadosTexto();
        }
    }

    private function mostrarResultadosTexto()
    {
        echo "\n=== RESULTADO DO DEBUG ===\n";
        echo "ID Interno: {$this->idInterno}\n";
        echo "Data/Hora: " . date('Y-m-d H:i:s') . "\n\n";

        // Informações do CSV
        if ($this->registroCSV) {
            echo "📄 INFORMAÇÕES DO CSV:\n";
            echo "Linha: {$this->registroCSV['linha']}\n";
            echo "Dados brutos: " . var_export($this->registroCSV['dados_brutos'], true) . "\n";
            echo "Dados parseados: " . var_export($this->registroCSV['dados_parseados'], true) . "\n\n";

            // Análise dos dados do CSV
            $dadosCSV = $this->registroCSV['dados_parseados'];
            echo "Análise dos dados CSV:\n";
            echo "- Prontuário: " . ($dadosCSV[0] ?? 'não informado') . "\n";
            echo "- Nome: " . ($dadosCSV[1] ?? 'não informado') . "\n";
            echo "- CPF: " . ($dadosCSV[2] ?? 'não informado') . "\n";
            echo "- Data Nascimento: " . ($dadosCSV[3] ?? 'não informado') . "\n";
            echo "- Situação: " . ($dadosCSV[4] ?? 'não informado') . "\n";
            echo "- Unidade ID: " . ($dadosCSV[5] ?? 'não informado') . "\n";

            // Validações
            echo "Validações CSV:\n";
            if (!empty($dadosCSV[1])) {
                echo "- Nome: " . strlen(trim($dadosCSV[1])) . " caracteres\n";
            }
            if (!empty($dadosCSV[2])) {
                $cpfValido = validarCPF($dadosCSV[2]);
                echo "- CPF: " . ($cpfValido ? 'VÁLIDO' : 'INVÁLIDO') . " (" . formatarCPF($dadosCSV[2]) . ")\n";
            }
            if (!empty($dadosCSV[3])) {
                $dataFormatada = formatarData($dadosCSV[3]);
                echo "- Data Nascimento: " . ($dataFormatada === $dadosCSV[3] ? 'VÁLIDA' : 'INVÁLIDA') . "\n";
            }
        }

        // Informações do Banco
        if ($this->registroBanco) {
            echo "\n🗄️ INFORMAÇÕES DO BANCO:\n";
            foreach ($this->registroBanco as $campo => $valor) {
                echo "- " . ucfirst($campo) . ": " . ($valor ?? 'nulo') . "\n";
            }
        }

        // Comparação
        if (isset($this->comparacao)) {
            echo "\n📊 COMPARAÇÃO CSV vs BANCO:\n";
            $diferentes = array_filter($this->comparacao, fn($c) => $c['diferente']);
            $iguais = array_filter($this->comparacao, fn($c) => $c['igual']);

            echo "Campos diferentes: " . count($diferentes) . "\n";
            echo "Campos iguais: " . count($iguais) . "\n\n";

            if (!empty($diferentes) && $this->saidaCompleta) {
                echo "Detalhes das diferenças:\n";
                foreach ($diferentes as $comp) {
                    echo "- {$comp['campo']}: CSV='{$comp['valor_csv']}' vs Banco='{$comp['valor_banco']}'\n";
                }
            }
        }

        // Sugestões
        $this->mostrarSugestoes();
    }

    private function mostrarResultadosJSON()
    {
        $resultado = [
            'id_interno' => $this->idInterno,
            'data_hora' => date('Y-m-d H:i:s'),
            'csv' => $this->registroCSV,
            'banco' => $this->registroBanco,
            'comparacao' => $this->comparacao ?? null,
            'sugestoes' => $this->getSugestoes()
        ];

        echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    private function mostrarSugestoes()
    {
        $sugestoes = [];

        // Analisar problemas comuns
        if ($this->registroCSV && $this->registroBanco) {
            $dadosCSV = $this->registroCSV['dados_parseados'];
            $dadosBanco = $this->registroBanco;

            // Nome em branco ou inconsistente
            if (empty(trim($dadosCSV[1]))) {
                $sugestoes[] = "Verificar se o nome está sendo preenchido corretamente no CSV";
            } elseif (trim($dadosCSV[1]) !== trim($dadosBanco['nome'])) {
                $sugestoes[] = "Nome diferente entre CSV e banco. Verificar qual está correto";
            }

            // CPF inválido
            if (!empty($dadosCSV[2]) && !validarCPF($dadosCSV[2])) {
                $sugestoes[] = "CPF inválido no CSV. Verificar formatação e dígitos";
            }

            // Situação inconsistente
            if (isset($dadosCSV[4]) && isset($dadosBanco['situacao'])) {
                if (trim($dadosCSV[4]) !== trim($dadosBanco['situacao'])) {
                    $sugestoes[] = "Situação diferente entre CSV e banco. Verificar fluxo de atualização";
                }
            }
        }

        if (empty($sugestoes)) {
            $sugestoes[] = "Nenhuma sugestão - dados parecem consistentes";
        }

        echo "\n💡 SUGESTÕES:\n";
        foreach ($sugestoes as $i => $sugestao) {
            echo ($i + 1) . ". $sugestao\n";
        }
    }

    private function getSugestoes()
    {
        $sugestoes = [];

        // Analisar problemas comuns
        if ($this->registroCSV && $this->registroBanco) {
            $dadosCSV = $this->registroCSV['dados_parseados'];
            $dadosBanco = $this->registroBanco;

            // Nome em branco ou inconsistente
            if (empty(trim($dadosCSV[1]))) {
                $sugestoes[] = "Verificar se o nome está sendo preenchido corretamente no CSV";
            } elseif (trim($dadosCSV[1]) !== trim($dadosBanco['nome'])) {
                $sugestoes[] = "Nome diferente entre CSV e banco. Verificar qual está correto";
            }

            // CPF inválido
            if (!empty($dadosCSV[2]) && !validarCPF($dadosCSV[2])) {
                $sugestoes[] = "CPF inválido no CSV. Verificar formatação e dígitos";
            }

            // Situação inconsistente
            if (isset($dadosCSV[4]) && isset($dadosBanco['situacao'])) {
                if (trim($dadosCSV[4]) !== trim($dadosBanco['situacao'])) {
                    $sugestoes[] = "Situação diferente entre CSV e banco. Verificar fluxo de atualização";
                }
            }

            // Unidade não encontrada
            if (!empty($dadosCSV[5]) && !empty($dadosBanco['unidade_id'])) {
                if (trim($dadosCSV[5]) !== trim($dadosBanco['unidade_id'])) {
                    $sugestoes[] = "Unidade diferente. Verificar se ID da unidade está correto";
                }
            }
        }

        // Sugestões gerais
        if (empty($this->registroCSV)) {
            $sugestoes[] = "Registro não encontrado no arquivo CSV. Verificar se o ID está correto";
        }

        if ($this->pdo && empty($this->registroBanco)) {
            $sugestoes[] = "Registro não encontrado no banco. Verificar se foi importado";
        }

        if (empty($sugestoes)) {
            $sugestoes[] = "Nenhuma sugestão - dados parecem consistentes";
        }

        return $sugestoes;
    }
}

// Execução principal
try {
    logMessage("=== INÍCIO DO DEBUG DE REGISTRO ESPECÍFICO ===");

    if (empty($idInterno)) {
        logMessage("ERRO: ID do interno não informado", 'ERROR');
        logMessage("Uso: php debug_registro_especifico.php [id_interno] [opcoes]");
        logMessage("Exemplo: php debug_registro_especifico.php 12345 --completo");
        exit(1);
    }

    // Validar ID
    if (!is_numeric($idInterno) || $idInterno <= 0) {
        logMessage("ERRO: ID do interno deve ser um número positivo", 'ERROR');
        exit(1);
    }

    // Carregar configuração do banco se necessário
    $configBanco = null;
    if ($usarBancoDireto) {
        $configBanco = require __DIR__ . '/../conf/db.php';
    }

    // Executar debug
    $debug = new DebugRegistroEspecifico($configBanco, $arquivoCSV, $saidaJSON, $saidaCompleta, $usarBancoDireto);
    $debug->debugPorID($idInterno);

    logMessage("=== DEBUG CONCLUÍDO ===");
} catch (Exception $e) {
    logMessage("ERRO FATAL: " . $e->getMessage(), 'ERROR');
    exit(1);
}
