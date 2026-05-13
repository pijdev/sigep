<?php
// auth/session_auth.php
// Funções compartilhadas para inicialização de sessão autenticada.

if (!function_exists('sigep_apply_user_session')) {
    function sigep_apply_user_session(array $row, bool $isKiosk = false): void
    {
        $_SESSION['user_id']    = $row['id'];
        $_SESSION['user_nome']  = $row['nome'];
        $_SESSION['user_setor'] = $row['setor'];
        $_SESSION['user_admin'] = (bool)$row['is_admin'];
        $_SESSION['user_theme'] = (int)$row['dark_mode'];

        foreach ($row as $coluna => $valor) {
            if (strpos((string)$coluna, 'perm_') === 0) {
                $_SESSION[$coluna] = (int)$valor;
            }
        }

        $_SESSION['ultimo_clique'] = time();
        $_SESSION['kiosk_mode'] = $isKiosk ? 1 : 0;
    }
}

