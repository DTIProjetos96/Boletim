<?php
// adicionar_materia.php

header('Content-Type: application/json');
require_once '../db.php'; // Caminho relativo para o arquivo db.php

$response = ['status' => 'error', 'message' => 'Ação não executada.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mate_bole_cod = isset($_POST['mate_bole_cod']) ? intval($_POST['mate_bole_cod']) : 0;
    $bole_cod = isset($_POST['bole_cod']) ? intval($_POST['bole_cod']) : 0;

    if ($mate_bole_cod <= 0 || $bole_cod <= 0) {
        $response['message'] = 'Código da matéria ou boletim inválido.';
        echo json_encode($response);
        exit;
    }

    try {
        // Inserir na tabela materia_publicacao
        $sql_insert = "INSERT INTO bg.materia_publicacao (fk_mate_bole_cod, fk_bole_cod) VALUES (:fk_mate_bole_cod, :fk_bole_cod)";
        $stmt = $pdo->prepare($sql_insert);
        $stmt->execute([
            'fk_mate_bole_cod' => $mate_bole_cod,
            'fk_bole_cod' => $bole_cod
        ]);

        $response['status'] = 'success';
        $response['message'] = 'Matéria adicionada ao boletim com sucesso.';
    } catch (PDOException $e) {
        $response['message'] = 'Erro ao adicionar matéria: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Método de requisição inválido.';
}

echo json_encode($response);
?>
