<?php
// verify_password.php

// Definir o cabeçalho de conteúdo para JSON
header('Content-Type: application/json; charset=UTF-8');

// Habilita a exibição de erros temporariamente (REMOVA EM PRODUÇÃO)
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

// Inclui o arquivo de conexão com o banco de dados e funções
require_once __DIR__ . '/db.php';          // Se estiver na raiz
require_once __DIR__ . '/functions.php';   // Se estiver na raiz

// Inicia a sessão se ainda não estiver iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Defina a constante para habilitar/desabilitar logs
define('ENABLE_LOGGING', false); // Defina como true para habilitar os logs

// Inicializa um array para armazenar os logs
$logs = [];

// Função para adicionar mensagens aos logs
function add_log(&$logs, $message) {
    if (defined('ENABLE_LOGGING') && ENABLE_LOGGING) {
        $logs[] = $message;
    }
}

// Função para retornar a resposta JSON e encerrar o script
function return_response($status, $message, $logs, $additional_data = []) {
    $response = [
        'status' => $status,
        'message' => $message,
    ];

    if (defined('ENABLE_LOGGING') && ENABLE_LOGGING) {
        $response['logs'] = $logs;
    }

    if (!empty($additional_data)) {
        $response = array_merge($response, $additional_data);
    }

    echo json_encode($response);
    exit();
}

// Verifica se o usuário está logado
if (!isset($_SESSION['matricula'])) {
    add_log($logs, "Usuário não está logado.");
    return_response('error', 'Usuário não está logado.', $logs);
}

// Recupera a matrícula do usuário da sessão
$matricula = $_SESSION['matricula'];
add_log($logs, "Matrícula do usuário: " . $matricula);

// Recebe a senha e o token CSRF enviados via POST
$password = isset($_POST['password']) ? $_POST['password'] : '';
$csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
add_log($logs, "Senha recebida: " . str_repeat('*', strlen($password))); // Mascarar a senha
add_log($logs, "CSRF Token recebido: " . $csrf_token);

// Verifica se os dados estão completos
if (empty($password) || empty($csrf_token)) {
    add_log($logs, "Dados incompletos: " . (empty($password) ? "Senha ausente. " : "") . (empty($csrf_token) ? "CSRF Token ausente." : ""));
    return_response('error', 'Dados incompletos.', $logs);
}

// Verifica o token CSRF
if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
    add_log($logs, "Token CSRF inválido ou ausente.");
    return_response('error', 'Token de segurança inválido.', $logs);
}
add_log($logs, "Token CSRF verificado com sucesso.");

// Tentar executar a consulta no banco de dados
try {
    add_log($logs, "Preparando consulta SQL para recuperar a senha armazenada.");
    // Consulta a senha armazenada no banco de dados usando MD5
    $stmt = $pdo->prepare('SELECT pswd FROM qra.sec_users WHERE login = :login AND active = \'Y\'');
    $stmt->bindValue(':login', $matricula, PDO::PARAM_STR);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    add_log($logs, "Consulta SQL executada com sucesso.");

    if ($user) {
        add_log($logs, "Usuário encontrado no banco de dados.");
        // Verifica a senha usando MD5
        $hashedPassword = md5($password);
        add_log($logs, "Hash MD5 da senha fornecida: " . $hashedPassword);
        add_log($logs, "Hash MD5 armazenado: " . $user['pswd']);
        
        if ($hashedPassword === $user['pswd']) {
            add_log($logs, "Senha verificada com sucesso.");
            return_response('success', 'Senha verificada com sucesso.', $logs);
        } else {
            add_log($logs, "Senha incorreta.");
            return_response('error', 'Senha incorreta.', $logs);
        }
    } else {
        add_log($logs, "Usuário não encontrado ou não está ativo.");
        return_response('error', 'Usuário não encontrado.', $logs);
    }
} catch (PDOException $e) {
    // Log do erro e resposta genérica
    add_log($logs, "Erro na consulta SQL: " . $e->getMessage());
    return_response('error', 'Erro no servidor.', $logs);
}
?>
