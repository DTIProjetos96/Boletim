<?php
require_once '../db.php'; // Inclui o arquivo de conexão com o banco

// Inicializa variáveis
$matricula = '';
$materias = [];
$error_message = '';
$success_message = '';

// Verifica se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $matricula = $_POST['matricula'] ?? '';

    // Valida a matrícula
    if (empty($matricula)) {
        $error_message = "Por favor, insira uma matrícula.";
    } else {
        // Query ajustada para buscar os campos usando a chave estrangeira materia_id
        $sql = '
            SELECT 
                mate_bole_cod,
                mate_bole_data,
            
                mate_bole_data_doc,
                
                mate_bole_texto
            FROM bg.vw_pessoa_materia_detalhada vpm
            INNER JOIN bg.materia_boletim mb ON vpm.materia_id = mb.mate_bole_cod
            WHERE vpm.matricula = :matricula
        ';

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['matricula' => $matricula]);
            $materias = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($materias)) {
                $error_message = "Não há matérias relacionadas à matrícula fornecida.";
            } else {
                $success_message = "Matérias encontradas:";
            }
        } catch (PDOException $e) {
            $error_message = "Erro ao buscar matérias: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consulta de Matérias</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        .form-container {
            margin-bottom: 20px;
        }
        .message {
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 4px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>

<div class="form-container">
    <h1>Consulta de Matérias</h1>
    <form method="POST">
        <label for="matricula">Matrícula:</label>
        <input type="text" id="matricula" name="matricula" value="<?= htmlspecialchars($matricula); ?>" required>
        <button type="submit">Consultar</button>
    </form>
</div>

<?php if ($error_message): ?>
    <div class="message error"><?= htmlspecialchars($error_message); ?></div>
<?php endif; ?>

<?php if ($success_message): ?>
    <div class="message success"><?= htmlspecialchars($success_message); ?></div>
<?php endif; ?>

<?php if (!empty($materias)): ?>
    <table>
        <thead>
            <tr>
                <th>Código</th>
                <th>Data</th>
                <th>Tipo Documento</th>
                <th>Data Documento</th>
                <th>Assunto Específico</th>
                <th>Assunto Geral</th>
                <th>Texto</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($materias as $materia): ?>
                <tr>
                    <td><?= htmlspecialchars($materia['mate_bole_cod']); ?></td>
                    <td><?= htmlspecialchars($materia['mate_bole_data']); ?></td>
                    <td><?= htmlspecialchars($materia['tipo_docu_descricao']); ?></td>
                    <td><?= htmlspecialchars($materia['mate_bole_data_doc']); ?></td>
                    <td><?= htmlspecialchars($materia['assu_espe_descricao']); ?></td>
                    <td><?= htmlspecialchars($materia['assu_gera_descricao']); ?></td>
                    <td><?= htmlspecialchars($materia['mate_bole_texto']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

</body>
</html>
