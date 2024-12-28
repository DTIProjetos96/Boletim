<?php
// valida_acesso.php

header('Content-Type: text/html; charset=UTF-8');

error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once __DIR__ . '/../db.php';  // Ajuste o caminho conforme sua estrutura de diretórios

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['matricula'])) {
    header('Location: https://pmrr.net/boletim/login/login_pm.php');
    exit();
}

$matricula = $_SESSION['matricula'];
$pagina_atual = basename($_SERVER['PHP_SELF']);

function verificar_acesso_botao($pdo, $pagina, $matricula, $botao_id) {
    $pagina_botao = strtolower($pagina . '|' . $botao_id);
    $sql = "SELECT COUNT(*) FROM bg.controle_acesso 
            WHERE controle_acesso_pagina = :pagina_botao 
              AND controle_acesso_matricula = :matricula
              AND tipo = 'botao'";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':pagina_botao' => $pagina_botao, 
            ':matricula' => $matricula
        ]);

        $count = $stmt->fetchColumn();

        // Depuração: exibir no HTML
        //echo "<!-- Verificar acesso para '$pagina_botao' com matrícula '$matricula': $count -->";

        return $count > 0;
    } catch (PDOException $e) {
        // Depuração: exibir no HTML
        echo "<!-- Erro na consulta SQL (verificar_acesso_botao): " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . " -->";
        return false;
    }
}

function processar_html($html, $pdo, $pagina_atual, $matricula) {
    $dom = new DOMDocument();

    libxml_use_internal_errors(true);

    $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

    libxml_clear_errors();

    $xpath = new DOMXPath($dom);
    $botao_nodes = $xpath->query('//*[@data-acesso-id]');

    foreach ($botao_nodes as $botao) {
        $acesso_id = $botao->getAttribute('data-acesso-id');

        $tem_acesso = verificar_acesso_botao($pdo, $pagina_atual, $matricula, $acesso_id);

        // Depuração: adicionar comentário no HTML
        $status = $tem_acesso ? 'Sim' : 'Não';
        $dom->createComment(" Botão '$acesso_id' permitido: $status ");

        if (!$tem_acesso) {
            $botao->parentNode->removeChild($botao);
        }
    }

    return $dom->saveHTML();
}

function finaliza_buffer($buffer) {
    global $pdo, $pagina_atual, $matricula;
    return processar_html($buffer, $pdo, $pagina_atual, $matricula);
}

ob_start('finaliza_buffer');
?>
