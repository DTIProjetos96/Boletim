<?php
// excluir_materias_selecionadas.php

header('Content-Type: application/json');

require_once '../db.php'; // Caminho relativo para o arquivo db.php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['materias']) && is_array($_POST['materias'])) {
        $materias = $_POST['materias'];

        // Validação: Verificar se todos os valores são numéricos para prevenir injeção SQL
        foreach ($materias as $mate_publ_cod) {
            if (!is_numeric($mate_publ_cod)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Código da matéria inválido.'
                ]);
                exit;
            }
        }

        try {
            // Iniciar uma transação
            $pdo->beginTransaction();

            // Preparar a consulta de exclusão
            $inQuery = implode(',', array_fill(0, count($materias), '?'));
            $sql = "DELETE FROM bg.materia_publicacao WHERE mate_publ_cod IN ($inQuery)";
            $stmt = $pdo->prepare($sql);

            // Executar a consulta
            $stmt->execute($materias);

            // Confirmar a transação
            $pdo->commit();

            echo json_encode([
                'status' => 'success',
                'message' => 'Matérias excluídas com sucesso.'
            ]);
        } catch (PDOException $e) {
            // Reverter a transação em caso de erro
            $pdo->rollBack();
            echo json_encode([
                'status' => 'error',
                'message' => 'Erro ao excluir matérias: ' . $e->getMessage()
            ]);
        }
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Nenhuma matéria recebida para exclusão.'
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Método de requisição inválido.'
    ]);
}
?>
