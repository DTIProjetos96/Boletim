<?php

function sanitize_input($data)
{
    return trim(htmlspecialchars($data, ENT_QUOTES, 'UTF-8'));
}

function isAjaxRequest()
{
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

function buscarDescricao($pdo, $assu_espe_cod) {
    $stmt = $pdo->prepare("
        SELECT assu_gera_descricao 
        FROM bg.vw_assunto_concatenado 
        WHERE assu_espe_cod = :assu_espe_cod 
        LIMIT 1
    ");
    $stmt->execute(['assu_espe_cod' => $assu_espe_cod]);
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    return $resultado ? $resultado['assu_gera_descricao'] : 'N/A';
}

?>
