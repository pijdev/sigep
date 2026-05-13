<?php
/**
 * Script para Cadastro de Eletrônicos Existentes nas Celas
 * Baseado no plano: cadastro-eletronicos-celas-aec165.md
 */

date_default_timezone_set('America/Sao_Paulo');

// --- CONFIGURAÇÃO ---
$config = require __DIR__ . '/../conf/db.php';

$DRY_RUN = false; // Mude para false para executar inserções reais
$LOG_FILE = __DIR__ . '/../temp/cadastro_eletronicos_' . date('Ymd_His') . '.log';

// Arquivos de entrada
$CONTAGEM_FILE = 'c:/temp/CONTAGEM_ELETRONICOS_ATUAL_POR_CELA.txt';
$CSV_ANTIGO_FILE = 'c:/temp/relatorio_eletronicos_sistema_antigo.csv';

// --- LOGGING ---
function logMessage($message, $level = 'INFO') {
    global $LOG_FILE;
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] [$level] $message\n";
    echo $logEntry;
    file_put_contents($LOG_FILE, $logEntry, FILE_APPEND);
}

// --- CONEXÃO BANCO ---
try {
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['user'], $config['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    logMessage("Conexão com banco estabelecida");
} catch (PDOException $e) {
    logMessage("Erro de conexão: " . $e->getMessage(), 'ERROR');
    die("Erro DB");
}

// --- CLASSES AUXILIARES ---

class ContagemCela {
    public $galeria;
    public $cela;
    public $radio;
    public $tv;
    public $chaleira;
    public $ventilador;

    public function __construct($linha) {
        $partes = preg_split('/\s+/', trim($linha));
        if (count($partes) >= 6) {
            $this->galeria = $partes[0];
            $this->cela = (int)$partes[1];
            $this->radio = (int)$partes[2];
            $this->tv = (int)$partes[3];
            $this->chaleira = (int)$partes[4];
            $this->ventilador = (int)$partes[5];
        }
    }

    public function getKey() {
        return $this->galeria . '-' . $this->cela;
    }
}

class ItemAntigo {
    public $galeria;
    public $cela;
    public $tipoItem;
    public $marca;
    public $proprietario;
    public $ipen;

    public function __construct($linha) {
        $partes = explode(';', $linha);
        if (count($partes) >= 9) {
            $this->galeria = trim($partes[4]);
            $this->cela = trim($partes[5]);
            $this->tipoItem = trim($partes[6]);
            $this->marca = trim($partes[7]);
            $this->proprietario = trim($partes[8]);
            $this->ipen = trim($partes[2]);
        }
    }

    public function getKey() {
        return $this->galeria . '-' . $this->cela;
    }

    public function getTipoNormalizado() {
        $tipo = strtoupper($this->tipoItem);
        switch($tipo) {
            case 'TELEVISAO': return 'TV';
            case 'RÁDIO':
            case 'RADIO': return 'Radio';
            case 'CHALEIRA': return 'Chaleira';
            case 'VENTILADOR': return 'Ventilador';
            default: return $tipo;
        }
    }
}

// --- FUNÇÕES PRINCIPAIS ---

function carregarContagemAtual() {
    global $CONTAGEM_FILE;

    $contagens = [];

    if (!file_exists($CONTAGEM_FILE)) {
        logMessage("Arquivo de contagem não encontrado: $CONTAGEM_FILE", 'ERROR');
        return $contagens;
    }

    $linhas = file($CONTAGEM_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($linhas as $linha) {
        if (strpos($linha, 'GALERIA') === 0) continue; // Pular cabeçalho

        $contagem = new ContagemCela($linha);
        if ($contagem->galeria) {
            $contagens[$contagem->getKey()] = $contagem;
        }
    }

    logMessage("Carregadas " . count($contagens) . " contagens de celas");
    return $contagens;
}

function carregarDadosAntigos() {
    global $CSV_ANTIGO_FILE;

    $itensAntigos = [];

    if (!file_exists($CSV_ANTIGO_FILE)) {
        logMessage("Arquivo CSV antigo não encontrado: $CSV_ANTIGO_FILE", 'ERROR');
        return $itensAntigos;
    }

    $linhas = file($CSV_ANTIGO_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($linhas as $linha) {
        $item = new ItemAntigo($linha);
        if ($item->galeria && in_array($item->getTipoNormalizado(), ['TV', 'Radio', 'Chaleira', 'Ventilador'])) {
            $key = $item->getKey();
            if (!isset($itensAntigos[$key])) {
                $itensAntigos[$key] = [];
            }
            $itensAntigos[$key][] = $item;
        }
    }

    logMessage("Carregados " . count($itensAntigos) . " registros do sistema antigo");
    return $itensAntigos;
}

function obterInternosCela($pdo, $galeria, $cela) {
    $stmt = $pdo->prepare("SELECT ipen, nome FROM internos WHERE galeria = ? AND res = ? AND status = 'A' ORDER BY ipen");
    $stmt->execute([$galeria, $cela]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function obterItensExistentesCela($pdo, $galeria, $cela) {
    $sql = "SELECT e.tipo_item, COUNT(*) as qtd
            FROM internos_eletronicos e
            JOIN internos i ON e.id_interno = i.ipen
            WHERE i.galeria = ? AND i.res = ? AND i.status = 'A'
            AND e.situacao = 'Na Cela'
            AND e.tipo_item IN ('TV', 'Radio', 'Chaleira', 'Ventilador')
            GROUP BY e.tipo_item";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$galeria, $cela]);
    $resultados = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    return [
        'TV' => $resultados['TV'] ?? 0,
        'Radio' => $resultados['Radio'] ?? 0,
        'Chaleira' => $resultados['Chaleira'] ?? 0,
        'Ventilador' => $resultados['Ventilador'] ?? 0
    ];
}

function calcularItensAAdicionar($contagem, $internosCount, $itensExistentes) {
    $adicionar = [];

    // TV: máximo 1 por cela
    $tvNecessarios = max(0, 1 - $itensExistentes['TV']);
    if ($tvNecessarios > 0 && $contagem->tv > $itensExistentes['TV']) {
        $adicionar['TV'] = min($tvNecessarios, $contagem->tv - $itensExistentes['TV']);
    }

    // Radio: máximo 1 por cela
    $radioNecessarios = max(0, 1 - $itensExistentes['Radio']);
    if ($radioNecessarios > 0 && $contagem->radio > $itensExistentes['Radio']) {
        $adicionar['Radio'] = min($radioNecessarios, $contagem->radio - $itensExistentes['Radio']);
    }

    // Chaleira: máximo 1 por cela
    $chaleiraNecessarios = max(0, 1 - $itensExistentes['Chaleira']);
    if ($chaleiraNecessarios > 0 && $contagem->chaleira > $itensExistentes['Chaleira']) {
        $adicionar['Chaleira'] = min($chaleiraNecessarios, $contagem->chaleira - $itensExistentes['Chaleira']);
    }

    // Ventilador: 1 por interno
    $ventiladoresNecessarios = max(0, ($internosCount * 1) - $itensExistentes['Ventilador']);
    if ($ventiladoresNecessarios > 0 && $contagem->ventilador > $itensExistentes['Ventilador']) {
        $adicionar['Ventilador'] = min($ventiladoresNecessarios, $contagem->ventilador - $itensExistentes['Ventilador']);
    }

    return $adicionar;
}

function atribuirItensAInternos($itensAAdicionar, $internos, $itensAntigos, $galeria, $cela) {
    $atribuicoes = [];

    // Primeiro, tentar atribuir baseado no sistema antigo
    $chaveCela = $galeria . '-' . $cela;
    $itensAntigosCela = $itensAntigos[$chaveCela] ?? [];

    foreach ($itensAAdicionar as $tipo => $quantidade) {
        // Procurar proprietários antigos para este tipo de item
        $proprietariosAntigos = array_filter($itensAntigosCela, function($item) use ($tipo) {
            return $item->getTipoNormalizado() === $tipo;
        });

        $atribuido = 0;

        // Atribuir aos proprietários antigos primeiro
        foreach ($proprietariosAntigos as $itemAntigo) {
            if ($atribuido >= $quantidade) break;

            // Verificar se o interno ainda está na cela
            $internoEncontrado = array_filter($internos, function($interno) use ($itemAntigo) {
                return $interno['ipen'] == $itemAntigo->ipen;
            });

            if (!empty($internoEncontrado)) {
                if (!isset($atribuicoes[$itemAntigo->ipen])) {
                    $atribuicoes[$itemAntigo->ipen] = [];
                }
                $atribuicoes[$itemAntigo->ipen][] = [
                    'tipo' => $tipo,
                    'fonte' => 'sistema_antigo',
                    'marca_antiga' => $itemAntigo->marca
                ];
                $atribuido++;
            }
        }

        // Atribuir o restante de forma round-robin
        $indexInterno = 0;
        while ($atribuido < $quantidade && $indexInterno < count($internos)) {
            $interno = $internos[$indexInterno];

            // Verificar se este interno já tem muitos itens
            $itensInterno = $atribuicoes[$interno['ipen']] ?? [];
            if (count($itensInterno) < 3) { // Limitar a no máximo 3 itens por interno
                if (!isset($atribuicoes[$interno['ipen']])) {
                    $atribuicoes[$interno['ipen']] = [];
                }
                $atribuicoes[$interno['ipen']][] = [
                    'tipo' => $tipo,
                    'fonte' => 'round_robin',
                    'marca_antiga' => null
                ];
                $atribuido++;
            }

            $indexInterno++;
        }
    }

    return $atribuicoes;
}

function inserirItem($pdo, $ipen, $tipoItem, $marcaModelo) {
    global $DRY_RUN;

    $sql = "INSERT INTO internos_eletronicos (
                id_interno, tipo_item, marca_modelo, cor, estado_conservacao,
                situacao, data_entrada, entregue_por, cadastrado_por
            ) VALUES (?, ?, ?, 'Preto', 'Usado', 'Na Cela', NOW(), ?, ?)";

    if ($DRY_RUN) {
        logMessage("[DRY RUN] Inserir $tipoItem para $ipen (marca: $marcaModelo)");
        return true;
    }

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$ipen, $tipoItem, $marcaModelo, 'Agente Penitenciário', 'Sistema - Cadastro Massa']);
        $idInserido = $pdo->lastInsertId();

        // Registrar no histórico
        registrarHistorico($pdo, $idInserido, 'ENTRADA', 'Sistema - Cadastro Massa',
                          "Item $tipoItem cadastrado via processo de migração de dados (marca: $marcaModelo)");

        return $idInserido;
    } catch (Exception $e) {
        logMessage("Erro ao inserir $tipoItem para $ipen: " . $e->getMessage(), 'ERROR');
        return false;
    }
}

function registrarHistorico($pdo, $idItem, $acao, $usuario, $detalhes) {
    $stmt = $pdo->prepare("INSERT INTO internos_eletronicos_historico (id_eletronico, acao, usuario, detalhes) VALUES (?, ?, ?, ?)");
    $stmt->execute([$idItem, $acao, $usuario, $detalhes]);
}

function obterMarcaPadrao($tipoItem) {
    switch($tipoItem) {
        case 'Ventilador':
            return 'Britânia 40cm';
        case 'TV':
        case 'Radio':
        case 'Chaleira':
            return 'Britânia';
        default:
            return 'Britânia';
    }
}

// --- EXECUÇÃO PRINCIPAL ---

logMessage("=== INÍCIO DO PROCESSO DE CADASTRO DE ELETRÔNICOS ===");
logMessage("DRY RUN: " . ($DRY_RUN ? 'SIM' : 'NÃO'));

// 1. Carregar dados
$contagens = carregarContagemAtual();
$dadosAntigos = carregarDadosAntigos();

// 2. Processar cada cela
$estatisticas = [
    'celas_processadas' => 0,
    'itens_inseridos' => 0,
    'erros' => 0
];

foreach ($contagens as $chave => $contagem) {
    logMessage("Processando cela $chave (G:$contagem->galeria C:$contagem->cela)");

    try {
        // Obter internos da cela
        $internos = obterInternosCela($pdo, $contagem->galeria, $contagem->cela);
        $numInternos = count($internos);

        if ($numInternos == 0) {
            logMessage("Nenhum interno ativo na cela $chave", 'WARNING');
            continue;
        }

        logMessage("Encontrados $numInternos internos na cela");

        // Obter itens existentes
        $itensExistentes = obterItensExistentesCela($pdo, $contagem->galeria, $contagem->cela);
        logMessage("Itens existentes: TV:{$itensExistentes['TV']} Radio:{$itensExistentes['Radio']} Chaleira:{$itensExistentes['Chaleira']} Vent:{$itensExistentes['Ventilador']}");

        // Calcular itens a adicionar
        $itensAAdicionar = calcularItensAAdicionar($contagem, $numInternos, $itensExistentes);

        if (empty($itensAAdicionar)) {
            logMessage("Nenhum item a adicionar para cela $chave");
            continue;
        }

        logMessage("Itens a adicionar: " . json_encode($itensAAdicionar));

        // Atribuir itens aos internos
        $atribuicoes = atribuirItensAInternos($itensAAdicionar, $internos, $dadosAntigos, $contagem->galeria, $contagem->cela);

        // Inserir itens
        foreach ($atribuicoes as $ipen => $itensInterno) {
            foreach ($itensInterno as $item) {
                $marca = $item['marca_antiga'] ?: obterMarcaPadrao($item['tipo']);
                $resultado = inserirItem($pdo, $ipen, $item['tipo'], $marca);

                if ($resultado) {
                    $estatisticas['itens_inseridos']++;
                    logMessage("Inserido {$item['tipo']} para $ipen (marca: $marca)");
                } else {
                    $estatisticas['erros']++;
                }
            }
        }

        $estatisticas['celas_processadas']++;

    } catch (Exception $e) {
        logMessage("Erro ao processar cela $chave: " . $e->getMessage(), 'ERROR');
        $estatisticas['erros']++;
    }
}

// 3. Relatório final
logMessage("=== RELATÓRIO FINAL ===");
logMessage("Celas processadas: {$estatisticas['celas_processadas']}");
logMessage("Itens inseridos: {$estatisticas['itens_inseridos']}");
logMessage("Erros: {$estatisticas['erros']}");
logMessage("Log salvo em: $LOG_FILE");

if (!$DRY_RUN && $estatisticas['itens_inseridos'] > 0) {
    logMessage("=== VERIFICAÇÃO FINAL ===");
    // Verificar se as contagens finais batem
    verificarContagensFinais($pdo, $contagens);
}

function verificarContagensFinais($pdo, $contagensOriginais) {
    logMessage("Verificando contagens finais...");

    foreach ($contagensOriginais as $contagem) {
        $itensFinais = obterItensExistentesCela($pdo, $contagem->galeria, $contagem->cela);

        $tvOk = $itensFinais['TV'] == $contagem->tv;
        $radioOk = $itensFinais['Radio'] == $contagem->radio;
        $chaleiraOk = $itensFinais['Chaleira'] == $contagem->chaleira;
        $ventOk = $itensFinais['Ventilador'] == $contagem->ventilador;

        if ($tvOk && $radioOk && $chaleiraOk && $ventOk) {
            logMessage("Cela {$contagem->getKey()}: ✓ Contagens batem");
        } else {
            logMessage("Cela {$contagem->getKey()}: ✗ Divergência - Atual: TV:{$itensFinais['TV']} Radio:{$itensFinais['Radio']} Chaleira:{$itensFinais['Chaleira']} Vent:{$itensFinais['Ventilador']} | Esperado: TV:{$contagem->tv} Radio:{$contagem->radio} Chaleira:{$contagem->chaleira} Vent:{$contagem->ventilador}", 'WARNING');
        }
    }
}

logMessage("=== PROCESSO CONCLUÍDO ===");
?>
