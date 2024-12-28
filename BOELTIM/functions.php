<?php
// functions.php

/**
 * Função para registrar mensagens de depuração
 *
 * @param string $message Mensagem de depuração
 * @return void
 */
function debug_log($message) {
    if (defined('DEBUG') && DEBUG) {
        // Define o caminho do arquivo de log
        $log_file = __DIR__ . '/debug.log';
        
        // Obtém o timestamp atual
        $timestamp = date("Y-m-d H:i:s");
        
        // Formata a mensagem com o timestamp
        $log_message = "[$timestamp] $message\n";
        
        // Escreve a mensagem no arquivo de log
        file_put_contents($log_file, $log_message, FILE_APPEND);
        
        // Opcional: Exibir mensagens de debug no navegador (não recomendado em produção)
        // echo "<!-- DEBUG: " . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . " -->\n";
    }
}

/**
 * Função para verificar se o usuário tem acesso a uma determinada ação
 *
 * @param string $acao Ação a ser verificada (ex: 'pagina|botao_id')
 * @return bool Verdadeiro se o usuário tiver acesso, falso caso contrário
 */
function usuario_tem_acesso($acao) {
    global $pdo;

    if (!isset($_SESSION['matricula'])) {
        return false;
    }

    $matricula = $_SESSION['matricula'];

    $sql = "SELECT COUNT(*) FROM bg.controle_acesso 
            WHERE controle_acesso_pagina = :acao 
              AND controle_acesso_matricula = :matricula
              AND tipo = 'botao'";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':acao', $acao, PDO::PARAM_STR);
        $stmt->bindValue(':matricula', $matricula, PDO::PARAM_STR);
        $stmt->execute();

        $count = $stmt->fetchColumn();

        return $count > 0;
    } catch (PDOException $e) {
        debug_log("Erro na consulta SQL (usuario_tem_acesso): " . $e->getMessage());
        return false;
    }
}


// Evite fechar a tag PHP para prevenir saída acidental
