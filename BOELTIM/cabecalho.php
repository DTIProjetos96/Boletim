<?php
session_start();

// Verifica se o usuário está logado
if (!isset($_SESSION['matricula'])) {
    // Redireciona para a página de login se não estiver logado
    header('Location: ../boletim/login/login_pm.php');
    exit;
}

// Recupera os dados da sessão
$posto = htmlspecialchars($_SESSION['posto']);
$graduacao = htmlspecialchars($_SESSION['quadro']);
$guerra = htmlspecialchars($_SESSION['guerra']);
$subunidade = htmlspecialchars($_SESSION['subunidade']);
$unidade = htmlspecialchars($_SESSION['unidade']);
$coma_sigla = htmlspecialchars($_SESSION['coma_sigla']);
?>

