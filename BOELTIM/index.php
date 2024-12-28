<?php

// Define a URL base
define('BASE_URL', 'https://pmrr.net/boletim/');

// Realiza o redirecionamento para a página desejada
header("Location: " . BASE_URL . "menu/menu_bg.php");
exit(); // Interrompe a execução do código após o redirecionamento

?>