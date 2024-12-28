<?php
// db.php

define('DEBUG', true); // Defina como 'false' para desabilitar a depuração

$host = "pmrr.net";
$dbname = "pmrrnet_erp_prod"; // Substitua pelo nome do seu banco de dados
$user = "pmrrnet_usr_erp";     // Substitua pelo nome do seu usuário
$password = "stipmrr!@190";    // Substitua pela sua senha
$port = "5432";
$charset = "UTF8";              // Define a codificação de caracteres

try {
    // Inclui a porta e a codificação na string de conexão, além de definir o search_path para 'bg'
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;options='--client_encoding=$charset'";
    $pdo = new PDO($dsn, $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Definir o search_path para incluir o esquema 'bg'
    $pdo->exec("SET search_path TO bg, public");
} catch (PDOException $e) {
    if (DEBUG) {
        echo "Erro na conexão: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    } else {
        echo "Erro na conexão. Contate o administrador.";
    }
    die();
}
?>
