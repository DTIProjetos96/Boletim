<?php
// logout.php

// Habilita a exibição de erros temporariamente (REMOVA EM PRODUÇÃO)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Configurações de Segurança de Sessão
ini_set('session.cookie_httponly', 1); // Impede acesso via JavaScript
ini_set('session.cookie_secure', 1);   // Envia cookies apenas via HTTPS
ini_set('session.use_strict_mode', 1); // Impede sessões não iniciadas

session_start();

// Inclui os arquivos necessários
require_once __DIR__ . '/../db.php'; // Ajuste o caminho conforme sua estrutura de diretórios
require_once __DIR__ . '/../functions.php'; // Inclua funções auxiliares como debug_log()

// Definir a constante BASE_URL
define('BASE_URL', 'https://pmrr.net/boletim/');

// Função para remover o token de autenticação do banco de dados
function removerAuthToken($pdo) {
    if (isset($_COOKIE['auth_token'])) {
        $token = $_COOKIE['auth_token'];
        $hashedToken = hash('sha256', $token);

        try {
            // Remover o token do banco de dados
            $stmt = $pdo->prepare("DELETE FROM bg.auth_tokens WHERE token = :token");
            $stmt->bindValue(':token', $hashedToken, PDO::PARAM_STR);
            $stmt->execute();
        } catch (PDOException $e) {
            // Log de erro sem expor detalhes ao usuário
            if (function_exists('debug_log')) {
                debug_log("Erro ao remover auth_token no logout: " . $e->getMessage());
            }
        }

        // Remover o cookie auth_token
        setcookie('auth_token', '', time() - 3600, "/", "", true, true); // Remove o cookie
    }
}

// Função para remover completamente a sessão
function removerSessao() {
    // Destruir todos os dados da sessão
    $_SESSION = [];

    // Obter os parâmetros de sessão
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    // Destruir a sessão
    session_destroy();
}

// Função para gerar tokens CSRF
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token_logout'])) {
        $_SESSION['csrf_token_logout'] = bin2hex(random_bytes(32));
    }
}

// Função para verificar tokens CSRF
function verifyCsrfToken($token) {
    if (isset($_SESSION['csrf_token_logout']) && hash_equals($_SESSION['csrf_token_logout'], $token)) {
        return true;
    }
    return false;
}

// Gerar o token CSRF para o formulário de logout
generateCsrfToken();

// Verifica se o formulário de logout foi submetido via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verifica o token CSRF
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        // Token inválido, redireciona com erro ou exibe mensagem
        $_SESSION['message'] = 'Token de segurança inválido. Por favor, tente novamente.';
        header('Location: ' . BASE_URL . 'index.php'); // Redireciona para a página principal ou uma página de erro
        exit();
    }

    // Executa o processo de logout
    removerAuthToken($pdo);
    removerSessao();

    // Redireciona para a página de login com uma mensagem de sucesso via GET
    header('Location: ' . BASE_URL . 'login/login_pm.php?message=' . urlencode('Você foi desconectado com sucesso.'));
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Logout</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <?php
            // Verificar se há uma mensagem via sessão
            if (isset($_SESSION['message'])) {
                echo "<div class='alert alert-info'>" . htmlspecialchars($_SESSION['message']) . "</div>";
                unset($_SESSION['message']);
            }

            // Verificar se há uma mensagem via GET
            if (isset($_GET['message'])) {
                echo "<div class='alert alert-info'>" . htmlspecialchars($_GET['message']) . "</div>";
            }
        ?>
        <h2>Logout</h2>
        <p>Tem certeza de que deseja sair?</p>
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token_logout']); ?>">
            <button type="submit" class="btn btn-danger">Sair</button>
            <a href="<?php echo BASE_URL; ?>index.php" class="btn btn-secondary">Cancelar</a>
        </form>
    </div>
</body>
</html>
