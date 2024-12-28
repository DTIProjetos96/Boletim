<?php
// login_pm.php

// Habilita a exibição de erros temporariamente (remova em produção)
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

// Configurações de Segurança de Sessão (antes de session_start())
ini_set('session.cookie_httponly', 1); // Impede acesso via JavaScript
ini_set('session.cookie_secure', 1);   // Envia cookies apenas via HTTPS
ini_set('session.use_strict_mode', 1); // Impede sessões não iniciadas

session_start();

// Inclui os arquivos necessários
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../functions.php';

$error = '';

// Função para gerar tokens CSRF
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

// Função para verificar tokens CSRF
function verifyCsrfToken($token) {
    if (isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token)) {
        return true;
    }
    return false;
}

// Gerar o token CSRF para o formulário
generateCsrfToken();

// Verifica se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Verifica o token CSRF
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $error = 'Token de segurança inválido. Por favor, recarregue a página e tente novamente.';
    } else {
        // Captura e sanitiza as entradas

        // Validação da matrícula: deve seguir o formato "##.###-#"
        $matricula = filter_input(INPUT_POST, 'matricula', FILTER_VALIDATE_REGEXP, [
            'options' => ['regexp' => '/^\d{2}\.\d{3}-\d$/'] // Formato "##.###-#"
        ]);

        // Sanitização da senha
        $senha = trim($_POST['senha']);

        $lembrar = isset($_POST['lembrar']) ? true : false;

        if ($matricula && !empty($senha)) {
            try {
                // Remover a máscara da matrícula para enviar apenas os números
                $matricula_unmasked = str_replace(['.', '-'], '', $matricula); // Exemplo: "40.555-8" => "405558"

                // Adicione logs para depuração
                error_log("Matrícula Recebida: " . $matricula);
                error_log("Matrícula Sanitizada: " . $matricula_unmasked);

                // Consulta SQL usando a matrícula sem máscara
                $query = "
                    SELECT s.login, s.pswd, p.post_grad_sigla, p.quad_sigla, 
                           p.poli_mili_nome_guerra, p.subunidade, p.unidade, p.coma_sigla
                    FROM qra.sec_users s
                    JOIN recursoshumanos.vw_policial p 
                      ON s.login = CAST(p.poli_mili_matricula AS VARCHAR)
                    WHERE s.login = :login AND s.active = 'Y'
                ";

                // Prepara a consulta
                $stmt = $pdo->prepare($query);
                $stmt->bindValue(':login', $matricula_unmasked, PDO::PARAM_STR); // Usando $matricula_unmasked
                $stmt->execute();

                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user) {
                    // Adicione log para usuário encontrado
                    error_log("Usuário Encontrado: " . $user['login']);

                    // Verifica a senha usando MD5 (Recomenda-se atualizar para um hash mais seguro)
                    if (md5($senha) === $user['pswd']) {
                        // Regenera o ID da sessão para prevenir fixação de sessão
                        session_regenerate_id(true);

                        // Armazena dados do usuário na sessão
                        $_SESSION['matricula'] = $user['login'];
                        $_SESSION['subunidade'] = $user['subunidade'];
                        $_SESSION['posto'] = $user['post_grad_sigla'];
                        $_SESSION['quadro'] = $user['quad_sigla'];
                        $_SESSION['guerra'] = $user['poli_mili_nome_guerra'];
                        $_SESSION['unidade'] = $user['unidade'];
                        $_SESSION['coma_sigla'] = $user['coma_sigla'];

                        // Se o usuário escolheu "lembrar-me", cria um token de autenticação
                        if ($lembrar) {
                            // Gera um token único de 64 caracteres (32 bytes hexadecimais)
                            $token = bin2hex(random_bytes(32));

                            // Armazena o hash do token no banco de dados com uma associação ao usuário
                            $insertTokenQuery = "INSERT INTO bg.auth_tokens (login, token, expires_at) 
                                                 VALUES (:login, :token, NOW() + INTERVAL '30 days')";
                            $stmtToken = $pdo->prepare($insertTokenQuery);
                            $stmtToken->bindValue(':login', $matricula_unmasked, PDO::PARAM_STR);
                            $stmtToken->bindValue(':token', hash('sha256', $token), PDO::PARAM_STR);
                            $stmtToken->execute();

                            // Armazena o token no cookie
                            setcookie('auth_token', $token, time() + (86400 * 30), "/", "", true, true); // 30 dias, HttpOnly, Secure
                        } else {
                            // Se a opção "lembrar-me" não for marcada, destrói o cookie de token
                            setcookie('auth_token', '', time() - 3600, "/", "", true, true); // Remove o cookie
                        }

                        // Redireciona para a página principal
                        header('Location: ../index.php');
                        exit;
                    } else {
                        $error = 'Matrícula ou senha incorretos.';
                        error_log("Senha Incorreta para Matrícula: " . $matricula_unmasked);
                    }
                } else {
                    $error = 'Matrícula ou senha incorretos.';
                    error_log("Usuário Não Encontrado para Matrícula: " . $matricula_unmasked);
                }

            } catch (PDOException $e) {
                // Trata erros do banco de dados
                debug_log("Erro ao acessar o banco de dados: " . $e->getMessage());
                $error = 'Erro ao acessar o banco de dados. Por favor, tente novamente mais tarde.';
            }
        } else {
            $error = 'Por favor, preencha todos os campos.';
        }
    }
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- CDN do Inputmask -->
    <script src="https://cdn.jsdelivr.net/npm/inputmask/dist/inputmask.min.js"></script>
    <style>
        body {
            background: linear-gradient(to top, #d3b68d, #ffffff); /* Tons amadeirados e branco */
            height: 100vh;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: Arial, sans-serif;
        }

        .login-card {
            width: 400px;
            padding: 40px;
            background-color: rgba(255, 255, 255, 0.7); /* Fundo semitransparente para melhor legibilidade */
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }

        .login-card .login-background {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url('/boletim/login/images/boletim.webp'); /* Caminho correto da imagem */
            background-size: cover;
            background-position: center;
            filter: blur(1px); /* Menos desfoque para dar mais visibilidade à imagem */
            z-index: -1; /* Coloca a imagem atrás do conteúdo */
        }

        .form-label {
            font-weight: bold;
        }

        .btn {
            width: 100%;
        }

        .alert {
            text-align: center;
        }

        .login-card h2 {
            color: #333;
        }

        .form-check-label {
            font-weight: bold; /* Negrito no texto "Lembrar-me" */
        }

        .form-check-input {
            border: 2px solid #007bff; /* Borda azul na caixa de seleção */
        }

        .form-check-input:checked {
            background-color: #007bff;
            border-color: #007bff;
        }

        input::placeholder {
            color: #888; /* Cor do texto explicativo */
            font-style: italic; /* Estilo itálico no texto explicativo */
        }

        /* Estilo do ícone do olho */
        .eye-icon {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-background"></div> <!-- A imagem desfocada aqui -->
        <h2 class="text-center mb-4">Login</h2>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form id="loginForm" method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <div class="mb-3">
                <label for="matricula" class="form-label">Matrícula</label>
                <input type="text" class="form-control" id="matricula" name="matricula" required placeholder="Digite a matrícula ex: 40.555-5">
            </div>
            <div class="mb-3">
                <label for="senha" class="form-label">Senha</label>
                <div class="position-relative">
                    <input type="password" class="form-control" id="senha" name="senha" required placeholder="Digite sua senha">
                    <i class="eye-icon" id="eye-icon" onclick="togglePasswordVisibility()">
                        👁️
                    </i>
                </div>
            </div>
            <div class="form-check mb-3">
                <input type="checkbox" class="form-check-input" id="lembrar" name="lembrar" <?= isset($_COOKIE['auth_token']) ? 'checked' : '' ?>>
                <label class="form-check-label" for="lembrar">Lembrar-me</label>
            </div>
            <button type="submit" class="btn btn-primary">Entrar</button>
        </form>
    </div>

    <script>
        // Coloca o foco automaticamente no campo matrícula quando a página é carregada
        window.onload = function() {
            var matriculaInput = document.getElementById('matricula');
            matriculaInput.focus();

            // Configura o Inputmask com a máscara "99.999-9"
            var im = new Inputmask({
                mask: "99.999-9", // Define a máscara "##.###-#"
                // Remova a opção 'placeholder' do Inputmask para evitar conflito com o placeholder do HTML
                showMaskOnHover: false,
                showMaskOnFocus: false, // Evita mostrar a máscara ao focar
                clearIncomplete: true, // Limpa entradas incompletas
                placeholder: "_", // Define um caractere de preenchimento simples
                definitions: {
                    '9': {
                        validator: "[0-9]",
                        cardinality: 1,
                        casing: "lower"
                    }
                },
                onBeforePaste: function(pastedValue, opts) {
                    // Remove quaisquer caracteres que não sejam dígitos, ponto ou hífen antes de colar
                    return pastedValue.replace(/[^0-9\.-]/g, '');
                }
            });
            im.mask(matriculaInput);

            // **Removido o evento de input para evitar conflito com o Inputmask**
            // matriculaInput.addEventListener('input', function() {
            //     // Remover caracteres não permitidos (dígitos, ponto e hífen)
            //     this.value = this.value.replace(/[^0-9\.-]/g, '');
            // });
        }

        // Função para alternar a visibilidade da senha
        function togglePasswordVisibility() {
            var senhaInput = document.getElementById('senha');
            var eyeIcon = document.getElementById('eye-icon');

            if (senhaInput.type === "password") {
                senhaInput.type = "text";
                eyeIcon.innerHTML = "🙈";
            } else {
                senhaInput.type = "password";
                eyeIcon.innerHTML = "👁️";
            }
        }
    </script>
</body>
</html>
