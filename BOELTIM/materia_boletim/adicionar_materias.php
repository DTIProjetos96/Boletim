<?php
// adicionar_materias.php

header('Content-Type: application/json'); // Define o tipo de conteúdo para JSON

require_once '../db.php'; // Ajuste o caminho conforme sua estrutura de diretórios

// Verifica se a requisição é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Método de requisição inválido.']);
    exit;
}

// Recupera e valida os dados enviados
$bole_cod = isset($_POST['bole_cod']) ? intval($_POST['bole_cod']) : 0;
$materias = isset($_POST['materias']) && is_array($_POST['materias']) ? $_POST['materias'] : [];

if ($bole_cod <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Código de boletim inválido.']);
    exit;
}

if (empty($materias)) {
    echo json_encode(['status' => 'error', 'message' => 'Nenhuma matéria selecionada.']);
    exit;
}

try {
    // Iniciar transação
    $pdo->beginTransaction();

    // Preparar a consulta de inserção
    $sql_insert = "INSERT INTO bg.materia_publicacao (fk_bole_cod, fk_mate_bole_cod) VALUES (:bole_cod, :mate_bole_cod)";
    $stmt_insert = $pdo->prepare($sql_insert);

    foreach ($materias as $mate_cod) {
        $mate_cod = intval($mate_cod);
        if ($mate_cod > 0) {
            // Inserir cada matéria
            $stmt_insert->execute(['bole_cod' => $bole_cod, 'mate_bole_cod' => $mate_cod]);
        }
    }

    // Confirmar transação
    $pdo->commit();

    echo json_encode(['status' => 'success', 'message' => 'Matérias adicionadas com sucesso.']);
} catch (PDOException $e) {
    // Reverter transação em caso de erro
    $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => 'Erro ao adicionar matérias: ' . $e->getMessage()]);
}
?>
