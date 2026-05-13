<?php
// modulos/servicos/notificacoes/notificacoes_lib.php
// Biblioteca do sistema de notificações (sem side-effects).

class NotificationManager {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function criar(int $userId, string $tipo, string $titulo, string $mensagem, array $dados = []): bool {
        if (!$this->usuarioQuerReceber($userId, $tipo)) {
            return false;
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO sistema_notificacoes (user_id, tipo, titulo, mensagem, dados_json)
            VALUES (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([$userId, $tipo, $titulo, $mensagem, json_encode($dados)]);
    }

    public function buscarNaoLidas(int $userId, int $limit = 50): array {
        $stmt = $this->pdo->prepare("
            SELECT * FROM sistema_notificacoes
            WHERE user_id = ? AND lida = 0
            ORDER BY created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    }

    public function buscarTodas(int $userId, int $pagina = 1, int $limite = 20): array {
        $offset = ($pagina - 1) * $limite;

        // LIMIT/OFFSET não aceitam placeholder em alguns drivers; sanitizar via int.
        $limite = max(1, min(200, (int)$limite));
        $offset = max(0, (int)$offset);

        $stmt = $this->pdo->prepare("
            SELECT * FROM sistema_notificacoes
            WHERE user_id = ?
            ORDER BY created_at DESC
            LIMIT $limite OFFSET $offset
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function contarTodas(int $userId): int {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as total FROM sistema_notificacoes WHERE user_id = ?");
        $stmt->execute([$userId]);
        return (int)($stmt->fetch()['total'] ?? 0);
    }

    public function marcarComoLida(int $id, int $userId): bool {
        $stmt = $this->pdo->prepare("
            UPDATE sistema_notificacoes SET lida = 1
            WHERE id = ? AND user_id = ?
        ");
        return $stmt->execute([$id, $userId]);
    }

    public function marcarTodasComoLidas(int $userId): bool {
        $stmt = $this->pdo->prepare("
            UPDATE sistema_notificacoes SET lida = 1
            WHERE user_id = ? AND lida = 0
        ");
        return $stmt->execute([$userId]);
    }

    public function buscarPreferencias(int $userId): array {
        $stmt = $this->pdo->prepare("
            SELECT tipo, ativa FROM notificacoes_preferencias
            WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        $prefs = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        return $prefs ?: [];
    }

    public function atualizarPreferencia(int $userId, string $tipo, int $ativa): bool {
        $stmt = $this->pdo->prepare("
            INSERT INTO notificacoes_preferencias (user_id, tipo, ativa)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE ativa = ?
        ");
        return $stmt->execute([$userId, $tipo, $ativa, $ativa]);
    }

    public function getContagemNaoLidas(int $userId): int {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as count FROM sistema_notificacoes
            WHERE user_id = ? AND lida = 0
        ");
        $stmt->execute([$userId]);
        return (int)($stmt->fetch()['count'] ?? 0);
    }

    private function usuarioQuerReceber(int $userId, string $tipo): bool {
        $stmt = $this->pdo->prepare("
            SELECT ativa FROM notificacoes_preferencias
            WHERE user_id = ? AND tipo = ?
        ");
        $stmt->execute([$userId, $tipo]);
        $result = $stmt->fetch();
        return $result ? (bool)$result['ativa'] : true; // Padrão: ativo
    }

    // ===== MÉTODOS PARA CANAIS =====

    public function criarCanal(string $nome, string $descricao): int {
        $stmt = $this->pdo->prepare("INSERT INTO notificacao_canais (nome, descricao) VALUES (?, ?)");
        $stmt->execute([$nome, $descricao]);
        return $this->pdo->lastInsertId();
    }

    public function listarCanais(): array {
        $stmt = $this->pdo->query("SELECT * FROM notificacao_canais WHERE ativo = 1 ORDER BY nome");
        return $stmt->fetchAll();
    }

    public function buscarCanalPorNome(string $nome): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM notificacao_canais WHERE nome = ? AND ativo = 1");
        $stmt->execute([$nome]);
        return $stmt->fetch() ?: null;
    }

    public function inscreverUsuario(int $canalId, int $userId): bool {
        $stmt = $this->pdo->prepare("INSERT IGNORE INTO notificacao_canal_inscricoes (canal_id, tipo, identificador) VALUES (?, 'user', ?)");
        return $stmt->execute([$canalId, (string)$userId]);
    }

    public function inscreverSetor(int $canalId, string $setorSlug): bool {
        $stmt = $this->pdo->prepare("INSERT IGNORE INTO notificacao_canal_inscricoes (canal_id, tipo, identificador) VALUES (?, 'setor', ?)");
        return $stmt->execute([$canalId, $setorSlug]);
    }

    public function desinscrever(int $canalId, string $tipo, string $identificador): bool {
        $stmt = $this->pdo->prepare("DELETE FROM notificacao_canal_inscricoes WHERE canal_id = ? AND tipo = ? AND identificador = ?");
        return $stmt->execute([$canalId, $tipo, $identificador]);
    }

    public function listarInscricoes(int $canalId): array {
        $stmt = $this->pdo->prepare("
            SELECT nci.tipo, nci.identificador,
                   CASE
                       WHEN nci.tipo = 'user' THEN COALESCE(u.nome, CONCAT('Usuário ', nci.identificador))
                       WHEN nci.tipo = 'setor' THEN nci.identificador
                       ELSE nci.identificador
                   END as identificador_nome
            FROM notificacao_canal_inscricoes nci
            LEFT JOIN acesso_seguro u ON nci.tipo = 'user' AND u.id = nci.identificador
            WHERE nci.canal_id = ?
            ORDER BY nci.tipo, nci.identificador
        ");
        $stmt->execute([$canalId]);
        return $stmt->fetchAll();
    }

    public function adicionarTipoNotificacao(int $canalId, string $tipo): bool {
        $stmt = $this->pdo->prepare("INSERT IGNORE INTO notificacao_canal_tipos (canal_id, tipo_notificacao) VALUES (?, ?)");
        return $stmt->execute([$canalId, $tipo]);
    }

    public function removerTipoNotificacao(int $canalId, string $tipo): bool {
        $stmt = $this->pdo->prepare("DELETE FROM notificacao_canal_tipos WHERE canal_id = ? AND tipo_notificacao = ?");
        return $stmt->execute([$canalId, $tipo]);
    }

    public function listarTiposNotificacao(int $canalId): array {
        $stmt = $this->pdo->prepare("SELECT tipo_notificacao FROM notificacao_canal_tipos WHERE canal_id = ? AND ativo = 1");
        $stmt->execute([$canalId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function editarCanal(int $canalId, string $nome, string $descricao): bool {
        $stmt = $this->pdo->prepare("UPDATE notificacao_canais SET nome = ?, descricao = ? WHERE id = ?");
        return $stmt->execute([$nome, $descricao, $canalId]);
    }

    public function deletarCanal(int $canalId): bool {
        $stmt = $this->pdo->prepare("UPDATE notificacao_canais SET ativo = 0 WHERE id = ?");
        return $stmt->execute([$canalId]);
    }

    public function listarUsuarios(): array {
        $stmt = $this->pdo->query("SELECT id, nome, usuario FROM acesso_seguro WHERE status = 'Ativo' ORDER BY nome");
        return $stmt->fetchAll();
    }

    public function listarSetores(): array {
        // Matriz de setores do sistema
        $setores = [
            'censura' => 'Censura',
            'almoxarifado' => 'Almoxarifado',
            'laboral' => 'Laboral',
            'seg_trab' => 'Segurança do Trabalho',
            'rh' => 'Recursos Humanos',
            'coord' => 'Coordenação',
            'eclusa' => 'Eclusa',
            'direcao' => 'Direção',
            'portaria' => 'Portaria',
            'ti' => 'Tecnologia da Informação',
            'serralheria' => 'Serralheria',
            'escola' => 'Escola',
            'carga' => 'Carga / Logística',
            'industria' => 'Indústria',
            'juridico' => 'Jurídico',
            'cozinha' => 'Cozinha'
        ];
        $result = [];
        foreach ($setores as $slug => $nome) {
            $result[] = ['slug' => $slug, 'nome' => $nome];
        }
        return $result;
    }

    public function enviarParaCanal(string $nomeCanal, string $tipo, string $titulo, string $mensagem, array $dados = []): bool {
        $canal = $this->buscarCanalPorNome($nomeCanal);
        if (!$canal) return false;

        $canalId = $canal['id'];

        // Verificar se o tipo está ativo no canal
        $tipos = $this->listarTiposNotificacao($canalId);
        if (!in_array($tipo, $tipos)) return false;

        // Buscar usuários inscritos (direto ou via setor)
        $stmt = $this->pdo->prepare("
            SELECT DISTINCT a.id
            FROM acesso_seguro a
            LEFT JOIN notificacao_canal_inscricoes nci ON
                (nci.tipo = 'user' AND nci.identificador = CAST(a.id AS CHAR)) OR
                (nci.tipo = 'setor' AND nci.identificador = a.setor)
            WHERE nci.canal_id = ? AND a.status = 'Ativo'
        ");
        $stmt->execute([$canalId]);
        $users = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $success = true;
        foreach ($users as $userId) {
            if (!$this->criar((int)$userId, $tipo, $titulo, $mensagem, $dados)) {
                $success = false;
            }
        }
        return $success;
    }
}

