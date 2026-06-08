<?php
namespace AgentIA;

use PDO;

/**
 * Tools para a IA consultar informações de internos
 */
class InternosTools
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Buscar interno por IPEN
     * @param string $ipen IPEN do interno
     * @return array|string
     */
    public function buscar_por_ipen(string $ipen)
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT ipen, nome, nome_social, galeria, bloco, res, ala, situacao, status 
                 FROM internos WHERE ipen = ? AND status = 'A'"
            );
            $stmt->execute([$ipen]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$result) {
                return "Interno com IPEN {$ipen} não encontrado ou inativo.";
            }

            return $this->_formatarInterno($result);
        } catch (\Exception $e) {
            return "Erro ao buscar interno: " . $e->getMessage();
        }
    }

    /**
     * Buscar interno por nome ou nome social
     * @param string $nome Nome ou parte do nome
     * @return array|string
     */
    public function buscar_por_nome(string $nome)
    {
        try {
            $searchTerm = "%{$nome}%";
            $stmt = $this->pdo->prepare(
                "SELECT ipen, nome, nome_social, galeria, bloco, res, ala, situacao, status 
                 FROM internos 
                 WHERE (nome LIKE ? OR nome_social LIKE ?) 
                 AND status = 'A'
                 ORDER BY COALESCE(NULLIF(nome_social, ''), nome)
                 LIMIT 10"
            );
            $stmt->execute([$searchTerm, $searchTerm]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($results)) {
                return "Nenhum interno encontrado com esse nome.";
            }

            if (count($results) === 1) {
                return $this->_formatarInterno($results[0]);
            }

            return $this->_formatarListaInternos($results, "Encontrados " . count($results) . " internos:");
        } catch (\Exception $e) {
            return "Erro ao buscar interno: " . $e->getMessage();
        }
    }

    /**
     * Listar internos em uma cela específica
     * @param string $galeria Galeria (ex: "S", "01", "C")
     * @param string $bloco Bloco (ex: "A", "B", "C")
     * @param string $res Número da cela (ex: "1", "10")
     * @return array|string
     */
    public function internos_cela(string $galeria, string $bloco, string $res)
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT ipen, nome, nome_social, ala, situacao
                 FROM internos 
                 WHERE galeria = ? AND bloco = ? AND res = ? AND status = 'A'
                 ORDER BY ala, COALESCE(NULLIF(nome_social, ''), nome)"
            );
            $stmt->execute([$galeria, $bloco, $res]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($results)) {
                return "Nenhum interno encontrado na cela {$galeria}-{$bloco}-{$res}.";
            }

            $total = count($results);
            $mensagem = "**Cela {$galeria}-{$bloco}-{$res}**: {$total} interno(s)\n\n";
            foreach ($results as $interno) {
                $nomeSocial = $interno['nome_social'] ? " ({$interno['nome_social']})" : "";
                $situacao = $interno['situacao'] ? " - Situação: {$interno['situacao']}" : "";
                $mensagem .= "• IPEN {$interno['ipen']}: {$interno['nome']}{$nomeSocial}{$situacao}\n";
            }

            return $mensagem;
        } catch (\Exception $e) {
            return "Erro ao buscar internos da cela: " . $e->getMessage();
        }
    }

    /**
     * Contar internos em uma cela
     * @param string $galeria Galeria (ex: "S", "01", "C")
     * @param string $bloco Bloco (ex: "A", "B", "C")
     * @param string $res Número da cela (ex: "1", "10")
     * @return string
     */
    public function contar_cela(string $galeria, string $bloco, string $res): string
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT COUNT(*) as total FROM internos 
                 WHERE galeria = ? AND bloco = ? AND res = ? AND status = 'A'"
            );
            $stmt->execute([$galeria, $bloco, $res]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $total = $result['total'] ?? 0;

            return "A cela {$galeria}-{$bloco}-{$res} contém **{$total}** interno(s).";
        } catch (\Exception $e) {
            return "Erro ao contar internos: " . $e->getMessage();
        }
    }

    /**
     * Listar internos por situação
     * @param string $situacao Situação (ex: "SAÍDA TEMPORÁRIA", "PORTARIA", "RECOLHIDO")
     * @return array|string
     */
    public function internos_por_situacao(string $situacao)
    {
        try {
            // Normalizar a busca
            $situacaoSearch = "%{$situacao}%";
            
            $stmt = $this->pdo->prepare(
                "SELECT ipen, nome, nome_social, galeria, bloco, res, ala, situacao
                 FROM internos 
                 WHERE situacao LIKE ? AND status = 'A'
                 ORDER BY nome
                 LIMIT 50"
            );
            $stmt->execute([$situacaoSearch]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($results)) {
                return "Nenhum interno encontrado com situação: '{$situacao}'.";
            }

            $mensagem = "**Internos em '{$situacao}'**: " . count($results) . " encontrado(s)\n\n";
            foreach ($results as $interno) {
                $nomeSocial = $interno['nome_social'] ? " ({$interno['nome_social']})" : "";
                $cela = $interno['res'] ? "{$interno['galeria']}-{$interno['bloco']}-{$interno['res']}" : "Sem cela";
                $mensagem .= "• IPEN {$interno['ipen']}: {$interno['nome']}{$nomeSocial} - Cela: {$cela}\n";
            }

            return $mensagem;
        } catch (\Exception $e) {
            return "Erro ao buscar internos por situação: " . $e->getMessage();
        }
    }

    /**
     * Localizar onde está um interno (onde pode estar: cela, portaria, saída, etc)
     * @param string $ipen IPEN do interno
     * @return string
     */
    public function localizar_interno(string $ipen): string
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT ipen, nome, nome_social, galeria, bloco, res, ala, situacao, status 
                 FROM internos WHERE ipen = ?"
            );
            $stmt->execute([$ipen]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$result) {
                return "Interno com IPEN {$ipen} não encontrado no sistema.";
            }

            if ($result['status'] !== 'A') {
                return "Interno com IPEN {$ipen} está inativo no sistema.";
            }

            $nomeSocial = $result['nome_social'] ? " ({$result['nome_social']})" : "";
            $nome = $result['nome'] . $nomeSocial;

            if ($result['situacao']) {
                // Se tem situação, prioritário (saída temporária, trabalho, etc)
                return "**{$nome}** (IPEN {$ipen}): {$result['situacao']}";
            }

            if ($result['res']) {
                // Se tem cela, está recolhido
                $cela = "{$result['galeria']}-{$result['bloco']}-{$result['res']}";
                $ala = $result['ala'] ? " ({$result['ala']})" : "";
                return "**{$nome}** (IPEN {$ipen}): Na cela {$cela}{$ala}";
            }

            return "**{$nome}** (IPEN {$ipen}): Localização desconhecida no sistema.";
        } catch (\Exception $e) {
            return "Erro ao localizar interno: " . $e->getMessage();
        }
    }

    /**
     * 🔍 FERRAMENTA INTELIGENTE: Busca universal de internos
     * Detecta automaticamente o tipo de busca e aplica filtros
     * @param string $query Busca em linguagem natural (ex: "odilon josé bastos", "787056", "cela SA-5 LGBT")
     * @param bool $incluirInativos Se deve incluir internos inativos
     * @return string
     */
    public function buscar_internos(string $query, bool $incluirInativos = false): string
    {
        if (empty(trim($query))) {
            return "Por favor, forneca um criterio de busca (IPEN, nome, cela, etc).";
        }

        $query = trim($query);
        
        // Detectar tipo de busca e aplicar filtros
        $filtros = $this->_analisarQuery($query);
        $filtros['incluirInativos'] = $incluirInativos;

        file_put_contents('debug_ollama.log', "Query: {$query}\nFiltros detectados: " . json_encode($filtros) . "\n", FILE_APPEND);

        // Construir query SQL baseado nos filtros
        $sql = "SELECT ipen, nome, nome_social, galeria, bloco, res, ala, situacao, status, lgbt FROM internos WHERE 1=1";
        $params = [];

        // Filtro de status
        if (!$filtros['incluirInativos']) {
            $sql .= " AND status = 'A'";
        }

        // Filtro por IPEN (se detectado)
        if (!empty($filtros['ipen'])) {
            $sql .= " AND ipen = ?";
            $params[] = $filtros['ipen'];
        }

        // Filtro por nome/nome_social
        if (!empty($filtros['nome'])) {
            $sql .= " AND (nome LIKE ? OR nome_social LIKE ?)";
            $params[] = "%{$filtros['nome']}%";
            $params[] = "%{$filtros['nome']}%";
        }

        // Filtro por cela (galeria, bloco, res)
        if (!empty($filtros['galeria'])) {
            $sql .= " AND galeria = ?";
            $params[] = $filtros['galeria'];
        }
        if (!empty($filtros['bloco'])) {
            $sql .= " AND bloco = ?";
            $params[] = $filtros['bloco'];
        }
        if (!empty($filtros['res'])) {
            $sql .= " AND res = ?";
            $params[] = $filtros['res'];
        }

        // Filtro por situação
        if (!empty($filtros['situacao'])) {
            $sql .= " AND situacao LIKE ?";
            $params[] = "%{$filtros['situacao']}%";
        }

        // Filtro por LGBT
        if ($filtros['lgbtOnly']) {
            $sql .= " AND lgbt = 'S'";
        }

        // Ordenação
        $sql .= " ORDER BY COALESCE(NULLIF(nome_social, ''), nome) ASC LIMIT 50";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($results)) {
                return "Nenhum interno encontrado com os critérios: " . implode(", ", array_filter($filtros));
            }

            // Formatar resultados
            $mensagem = "**Resultados da busca:** " . count($results) . " interno(s) encontrado(s)\n\n";
            
            foreach ($results as $interno) {
                $nomeSocial = $interno['nome_social'] ? " ({$interno['nome_social']})" : "";
                $cela = $interno['res'] ? "{$interno['galeria']}-{$interno['bloco']}-{$interno['res']}" : "Sem cela";
                $lgbt = $interno['lgbt'] === 'S' ? " 🏳️‍🌈" : "";
                $status = $interno['status'] === 'A' ? "" : " [INATIVO]";
                $situacao = $interno['situacao'] ? " ({$interno['situacao']})" : "";
                
                $mensagem .= "• **IPEN {$interno['ipen']}**: {$interno['nome']}{$nomeSocial}{$lgbt}\n";
                $mensagem .= "  Cela: {$cela}{$situacao}{$status}\n\n";
            }

            return $mensagem;
        } catch (\Exception $e) {
            return "Erro ao buscar internos: " . $e->getMessage();
        }
    }

    /**
     * Analisar query em linguagem natural e detectar filtros
     * @param string $query Texto da busca
     * @return array Filtros detectados
     */
    private function _analisarQuery(string $query): array
    {
        $query = trim($query);
        $filtros = [
            'ipen' => '',
            'nome' => '',
            'galeria' => '',
            'bloco' => '',
            'res' => '',
            'situacao' => '',
            'lgbtOnly' => false,
            'incluirInativos' => false
        ];

        // Detectar IPEN (6-7 dígitos puros)
        if (preg_match('/\b(\d{6,7})\b/', $query, $matches)) {
            $filtros['ipen'] = $matches[1];
            return $filtros; // Se tem IPEN puro, retorna só isso
        }

        // Detectar LGBT
        if (preg_match('/\blgbt|lgbtq\b/i', $query)) {
            $filtros['lgbtOnly'] = true;
            $query = preg_replace('/\blgbt[tq]?\b/i', '', $query);
        }

        // Detectar inativo
        if (preg_match('/\binativo|desativo/i', $query)) {
            $filtros['incluirInativos'] = true;
        }

        // Detectar cela (formato: Galeria-Bloco-Res ou XYZN)
        // Ex: SA-1, S-A-1, AA-1, DB-10, 01-C-5
        if (preg_match('/(?:cela|sala)?\s*([A-Z0-9]{1,2})\s*[-\s]*([A-Z0-9]{1,2})\s*[-\s]*(\d+)/i', $query, $matches)) {
            $galeria = strtoupper($matches[1]);
            $bloco = strtoupper($matches[2]);
            $res = $matches[3];

            $filtros['galeria'] = $galeria;
            $filtros['bloco'] = $bloco;
            $filtros['res'] = $res;

            // Remove cela da query para não buscar como nome
            $query = preg_replace('/(?:cela|sala)?\s*[A-Z0-9]{1,2}\s*[-\s]*[A-Z0-9]{1,2}\s*[-\s]*\d+/i', '', $query);
        }

        // Detectar situação
        $situacoes = ['saida temporaria', 'saída temporária', 'portaria', 'portária', 'recolhido', 'trabalho interno', 'trabalho externo', 'estudo'];
        foreach ($situacoes as $sit) {
            if (stripos($query, $sit) !== false) {
                // Mapeamento de sinônimos
                $situacaoMapeada = match(strtolower($sit)) {
                    'portaria', 'portária' => 'SAÍDA TEMPORÁRIA',  // Portaria = Saída Temporária
                    default => strtoupper($sit)
                };
                
                $filtros['situacao'] = $situacaoMapeada;
                $query = str_ireplace($sit, '', $query);
                break;
            }
        }

        // O resto da query é nome
        $query = trim(preg_replace('/\s+/', ' ', $query));
        if (!empty($query) && strlen($query) > 2) {
            $filtros['nome'] = $query;
        }

        return $filtros;
    }

    /**
     * Listar situações dos internos (para a IA entender que valores são válidos)
     * @return string
     */
    public function listar_situacoes(): string
    {
        try {
            $stmt = $this->pdo->query(
                "SELECT DISTINCT situacao FROM internos 
                 WHERE situacao IS NOT NULL AND situacao != '' AND status = 'A'
                 ORDER BY situacao
                 LIMIT 30"
            );
            $results = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (empty($results)) {
                return "Nenhuma situação registrada no sistema.";
            }

            $mensagem = "**Situações disponíveis**: \n";
            foreach ($results as $situacao) {
                $mensagem .= "• " . $situacao . "\n";
            }

            return $mensagem;
        } catch (\Exception $e) {
            return "Erro ao listar situações: " . $e->getMessage();
        }
    }

    /**
     * Helper: Formatar um interno para resposta legível
     */
    private function _formatarInterno(array $interno): string
    {
        $nomeSocial = $interno['nome_social'] ? " ({$interno['nome_social']})" : "";
        $nome = $interno['nome'] . $nomeSocial;
        $ipen = $interno['ipen'];

        $localizacao = "";
        if ($interno['situacao']) {
            $localizacao = "Situação: {$interno['situacao']}";
        } elseif ($interno['res']) {
            $cela = "{$interno['galeria']}-{$interno['bloco']}-{$interno['res']}";
            $ala = $interno['ala'] ? " ({$interno['ala']})" : "";
            $localizacao = "Cela: {$cela}{$ala}";
        } else {
            $localizacao = "Localização não registrada";
        }

        return "**{$nome}** • IPEN: {$ipen} • {$localizacao}";
    }

    /**
     * Helper: Formatar lista de internos
     */
    private function _formatarListaInternos(array $internos, string $titulo): string
    {
        $mensagem = "**{$titulo}**\n\n";
        foreach ($internos as $interno) {
            $nomeSocial = $interno['nome_social'] ? " ({$interno['nome_social']})" : "";
            $cela = $interno['res'] ? "{$interno['galeria']}-{$interno['bloco']}-{$interno['res']}" : "Sem cela";
            $mensagem .= "• IPEN {$interno['ipen']}: {$interno['nome']}{$nomeSocial} - {$cela}\n";
        }
        return $mensagem;
    }

    /**
     * Retornar lista de tools disponíveis (para contexto da IA)
     */
    public static function getToolsDescription(): array
    {
        return [
            "buscar_internos" => "🔍 FERRAMENTA PRINCIPAL - Busca inteligente de internos por: IPEN, nome, cela, situação, LGBT, status. Detecta automaticamente o tipo de busca (ex: 'odilon josé bastos', '787056', 'cela SA-5 LGBT', 'saída temporária')",
            "buscar_por_ipen" => "Buscar interno por número de IPEN (ex: 787056)",
            "buscar_por_nome" => "Buscar interno por nome ou nome social (ex: 'João Silva')",
            "internos_cela" => "Listar todos os internos em uma cela específica (ex: galeria='S', bloco='A', res='1')",
            "contar_cela" => "Contar quantos internos estão em uma cela específica",
            "internos_por_situacao" => "Listar internos por situação (ex: 'SAÍDA TEMPORÁRIA', 'PORTARIA', 'RECOLHIDO')",
            "localizar_interno" => "Localizar onde está um interno específico (cela, portaria, saída, etc)",
            "listar_situacoes" => "Listar todas as situações possíveis de internos no sistema"
        ];
    }
}
