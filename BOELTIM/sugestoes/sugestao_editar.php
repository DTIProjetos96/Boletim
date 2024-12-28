<?php
require '../db.php';

if ($_SESSION['usuario_tipo'] !== 'admin') {
    die("Acesso negado.");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $status = $_POST['status'];
    $resposta = $_POST['resposta'];

    try {
        $sql = "UPDATE bg.sugestoes_melhoria SET status = :status, resposta_admin = :resposta, atualizado_em = NOW() WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':status' => $status,
            ':resposta' => $resposta,
            ':id' => $id,
        ]);
        echo "Sugestão atualizada com sucesso!";
    } catch (PDOException $e) {
        echo "Erro ao atualizar sugestão: " . $e->getMessage();
    }
} elseif (isset($_GET['id'])) {
    $id = $_GET['id'];
    $sql = "SELECT * FROM bg.sugestoes_melhoria WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    $sugestao = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
