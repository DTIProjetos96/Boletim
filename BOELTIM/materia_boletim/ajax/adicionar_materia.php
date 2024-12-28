<?php
// ajax/adicionar_materia.php
require_once '../db.php'; // Caminho relativo para o arquivo db.php

header('Content-Type: application/json');

$bole_cod = isset($_POST['bole_cod']) ? intval($_POST['bole_cod']) : 0;
$mate_bole_cod = isset($_POST['mate_bole_cod']) ? intval($_POST['mate_bole_cod']) : 0;

if ($bole_cod <= 0 || $mate_bole_cod <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Dados inválidos.']);
    exit;
}

try {
    // Inserir na tabela materia_publicacao
    $sql_insert = "INSERT INTO bg.materia_publicacao (fk_mate_bole_cod, fk_bole_cod) VALUES (:mate_bole_cod, :bole_cod)";
    $stmt = $pdo->prepare($sql_insert);
    $stmt->execute([
        'mate_bole_cod' => $mate_bole_cod,
        'bole_cod' => $bole_cod
    ]);
    
    echo json_encode(['status' => 'success', 'message' => 'Matéria adicionada com sucesso ao boletim.']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Erro ao adicionar matéria: ' . $e->getMessage()]);
}
?>
