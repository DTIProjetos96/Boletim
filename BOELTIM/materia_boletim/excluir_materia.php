<?php
// excluir_materia.php

header('Content-Type: application/json');
require_once '../db.php'; // Caminho relativo para o arquivo db.php

$response = ['status' => 'error', 'message' => 'Ação não executada.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mate_publ_cod = isset($_POST['mate_publ_cod']) ? intval($_POST['mate_publ_cod']) : 0;

    if ($mate_publ_cod <= 0) {
        $response['message'] = 'Código da matéria inválido.';
        echo json_encode($response);
        exit;
    }

    try {
        // Excluir da tabela materia_publicacao
        $sql_delete = "DELETE FROM bg.materia_publicacao WHERE mate_publ_cod = :mate_publ_cod";
        $stmt = $pdo->prepare($sql_delete);
        $stmt->execute(['mate_publ_cod' => $mate_publ_cod]);

        if ($stmt->rowCount() > 0) {
            $response['status'] = 'success';
            $response['message'] = 'Matéria excluída do boletim com sucesso.';
        } else {
            $response['message'] = 'Nenhuma matéria foi excluída.';
        }
    } catch (PDOException $e) {
        $response['message'] = 'Erro ao excluir matéria: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Método de requisição inválido.';
}

echo json_encode($response);
?>
