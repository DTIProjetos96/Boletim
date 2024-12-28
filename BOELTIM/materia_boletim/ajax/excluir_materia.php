<?php
// ajax/excluir_materia.php
require_once '../db.php';

if(isset($_POST['mate_publ_cod'])) {
    $mate_publ_cod = intval($_POST['mate_publ_cod']);

    // Excluir da tabela materia_publicacao
    $sql = "DELETE FROM bg.materia_publicacao WHERE mate_publ_cod = :mate_publ_cod";
    $stmt = $pdo->prepare($sql);
    if($stmt->execute(['mate_publ_cod' => $mate_publ_cod])) {
        echo json_encode(['status' => 'success', 'message' => 'Matéria excluída com sucesso.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Erro ao excluir a matéria.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Parâmetros inválidos.']);
}
?>
