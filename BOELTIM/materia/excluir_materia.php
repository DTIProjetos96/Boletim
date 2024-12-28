<?php
include '../db.php';

function delete_pessoa_materia($pdo, $mate_bole_cod) {
    try {
        $stmt = $pdo->prepare('DELETE FROM bg.pessoa_materia WHERE fk_mate_bole_cod = ?');
        if ($stmt->execute([$mate_bole_cod])) {
            return true;
        } else {
            $errorInfo = $stmt->errorInfo();
            echo "Erro ao excluir registros em bg.pessoa_materia: " . implode(" ", $errorInfo) . "<br>";
            return false;
        }
    } catch (PDOException $e) {
        echo "Erro ao excluir registros em bg.pessoa_materia: " . $e->getMessage() . "<br>";
        return false;
    }
}

function delete_materia_boletim($pdo, $mate_bole_cod) {
    try {
        $stmt = $pdo->prepare('DELETE FROM bg.materia_boletim WHERE mate_bole_cod = ?');
        if ($stmt->execute([$mate_bole_cod])) {
            return true;
        } else {
            $errorInfo = $stmt->errorInfo();
            echo "Erro ao excluir matéria: " . implode(" ", $errorInfo) . "<br>";
            return false;
        }
    } catch (PDOException $e) {
        echo "Erro ao excluir matéria: " . $e->getMessage() . "<br>";
        return false;
    }
}

// Função para verificar se a matéria pode ser excluída
function can_delete_materia($pdo, $mate_bole_cod) {
    try {
        $stmt = $pdo->prepare('SELECT mate_bole_publicada, mate_bole_enviada FROM bg.materia_boletim WHERE mate_bole_cod = ?');
        $stmt->execute([$mate_bole_cod]);
        $materia = $stmt->fetch(PDO::FETCH_ASSOC);

        //if ($materia['mate_bole_publicada'] == 1 || $materia['mate_bole_enviada'] == 1) {
        if ($materia['mate_bole_publicada'] == 1) {
            return false;
        }
        return true;
    } catch (PDOException $e) {
        echo "Erro ao verificar se a matéria pode ser excluída: " . $e->getMessage() . "<br>";
        return false;
    }
}

$mate_bole_cod = $_GET['mate_bole_cod'] ?? null;

if ($mate_bole_cod) {
    if (can_delete_materia($pdo, $mate_bole_cod)) {
        if (delete_pessoa_materia($pdo, $mate_bole_cod) && delete_materia_boletim($pdo, $mate_bole_cod)) {
            $message = "Matéria com código $mate_bole_cod excluída com sucesso!";
        } else {
            $message = "Erro ao excluir a matéria e/ou registros relacionados.";
        }
    } else {
        $message = "Matéria com código $mate_bole_cod não pode ser excluída porque está publicada ou enviada.";
    }
} else {
    $message = "Código da matéria não fornecido.";
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exclusão de Matéria</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .modal-content {
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            width: 400px;
            text-align: center;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.3);
        }
        .modal-content p {
            margin-bottom: 20px;
        }
        .modal-content button {
            padding: 10px 20px;
            background-color: #007bff;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div id="modalOverlay" class="modal-overlay">
        <div class="modal-content">
            <p><?php echo htmlspecialchars($message); ?></p>
            <button onclick="redirectToConsulta()">OK</button>
        </div>
    </div>

    <script>
        function redirectToConsulta() {
            window.location.href = 'consulta_materia1.php';
        }
    </script>
</body>
</html>
