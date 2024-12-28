<?php
include '../db.php';

if (isset($_GET['mate_bole_cod'])) {
    $mate_bole_cod = (int)$_GET['mate_bole_cod'];

    try {
        // Verifique se mate_bole_p1 é 1
        $stmt = $pdo->prepare('SELECT mate_bole_p1 FROM bg.materia_boletim WHERE mate_bole_cod = :mate_bole_cod');
        $stmt->execute(['mate_bole_cod' => $mate_bole_cod]);
        $materia = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($materia && $materia['mate_bole_p1'] == 1) {
            // Código para enviar a matéria
            // Atualize o status da matéria para enviada
            $stmt = $pdo->prepare('UPDATE bg.materia_boletim SET mate_bole_enviada = 1 WHERE mate_bole_cod = :mate_bole_cod');
            $stmt->execute(['mate_bole_cod' => $mate_bole_cod]);

            // Redirecionar para a tela de consulta com uma mensagem de sucesso
            header('Location: consulta_materia1.php?status=success');
        } else {
            // Redirecionar para a tela de consulta com uma mensagem de erro
            header('Location: consulta_materia1.php?status=error');
        }
        exit;
    } catch (PDOException $e) {
        // Em caso de erro, redirecionar para a tela de consulta com uma mensagem de erro
        header('Location: consulta_materia1.php?status=error');
        exit;
    }
} else {
    header('Location: consulta_materia1.php?status=invalid');
    exit;
}
?>
