<?php
// valida_session.php

// Impede o acesso direto ao arquivo
if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    // Impede o acesso direto ao arquivo
    http_response_code(403);
    exit('Acesso proibido.');
}

// Configurações de Segurança de Sessão devem ser definidas antes de iniciar a sessão
ini_set('session.cookie_httponly', 1); // Impede acesso via JavaScript
ini_set('session.cookie_secure', 1);   // Envia cookies apenas via HTTPS
ini_set('session.use_strict_mode', 1); // Impede sessões não iniciadas

session_start();

define('BASE_URL', 'https://pmrr.net/boletim/');

require_once __DIR__ . '/db.php';  // Ajuste o caminho conforme sua estrutura de diretórios

if (!isset($_SESSION['matricula'])) {
    // Verifica se há um token de autenticação no cookie
    if (isset($_COOKIE['auth_token'])) {
        $token = $_COOKIE['auth_token'];
        $hashedToken = hash('sha256', $token);

        try {
            // Consulta o token no banco de dados
            $stmt = $pdo->prepare("SELECT login FROM bg.auth_tokens WHERE token = :token AND expires_at > NOW()");
            $stmt->bindValue(':token', $hashedToken, PDO::PARAM_STR);
            $stmt->execute();

            $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($tokenData) {
                // Token válido, autentica o usuário
                $login = $tokenData['login'];

                // Recupera os dados do usuário
                $query = "
                    SELECT s.login, p.post_grad_sigla, p.quad_sigla, p.poli_mili_nome_guerra, p.subunidade, p.unidade, p.coma_sigla
                    FROM bg.sec_users s
                    JOIN recursoshumanos.vw_policial p ON s.login = CAST(p.poli_mili_matricula AS VARCHAR)
                    WHERE s.login = :login AND s.active = 'Y'
                ";

                $stmtUser = $pdo->prepare($query);
                $stmtUser->bindValue(':login', $login, PDO::PARAM_STR);
                $stmtUser->execute();

                $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

                if ($user) {
                    // Armazena dados do usuário na sessão
                    $_SESSION['matricula'] = $user['login'];
                    $_SESSION['subunidade'] = $user['subunidade'];
                    $_SESSION['posto'] = $user['post_grad_sigla'];
                    $_SESSION['quadro'] = $user['quad_sigla'];
                    $_SESSION['guerra'] = $user['poli_mili_nome_guerra'];
                    $_SESSION['unidade'] = $user['unidade'];
                    $_SESSION['coma_sigla'] = $user['coma_sigla'];
                } else {
                    // Usuário não encontrado, limpa o cookie e redireciona para o login
                    setcookie('auth_token', '', time() - 3600, "/", "", true, true);
                    header('Location: ' . BASE_URL . 'login/login_pm.php');
                    exit();
                }
            } else {
                // Token inválido ou expirado, redireciona para o login
                header('Location: ' . BASE_URL . 'login/login_pm.php');
                exit();
            }
        } catch (PDOException $e) {
            // Log de erro e redirecionamento com mensagem genérica
            debug_log("Erro na verificação do token de autenticação: " . $e->getMessage());
            header('Location: ' . BASE_URL . 'login/login_pm.php');
            exit();
        }
    } else {
        // Nenhuma sessão ou token válido, redireciona para o login
        header('Location: ' . BASE_URL . 'login/login_pm.php');
        exit();
    }
}

// Recupera os dados da sessão
$posto = htmlspecialchars($_SESSION['posto']);
$graduacao = htmlspecialchars($_SESSION['quadro']);
$guerra = htmlspecialchars($_SESSION['guerra']);
$subunidade = htmlspecialchars($_SESSION['subunidade']);
$unidade = htmlspecialchars($_SESSION['unidade']);
$coma_sigla = htmlspecialchars($_SESSION['coma_sigla']);
?>
