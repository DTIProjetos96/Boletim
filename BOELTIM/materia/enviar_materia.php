<?php
include '../db.php';

if (isset($_GET['mate_bole_cod'])) {
    $mate_bole_cod = $_GET['mate_bole_cod'];

    try {
        $query = "UPDATE bg.materia_boletim SET mate_bole_enviada = 1 WHERE mate_bole_cod = :mate_bole_cod";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':mate_bole_cod', $mate_bole_cod, PDO::PARAM_INT);

        if ($stmt->execute()) {
            echo "<script>alert('Matéria enviada com sucesso!');</script>";
            echo "<script>window.location.href = 'consulta_materia1.php';</script>";
        } else {
            echo "<script>alert('Erro ao enviar a matéria.');</script>";
            echo "<script>window.location.href = 'consulta_materia1.php';</script>";
        }
    } catch (PDOException $e) {
        error_log("Erro na atualização: " . $e->getMessage());
        echo "<script>alert('Erro ao enviar a matéria.');</script>";
        echo "<script>window.location.href = 'consulta_materia1.php';</script>";
    }
} else {
    echo "<script>alert('Código da matéria não fornecido.');</script>";
    echo "<script>window.location.href = 'consulta_materia1.php';</script>";
}
?>
