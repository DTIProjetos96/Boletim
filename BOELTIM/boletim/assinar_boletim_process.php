<?php
// assinar_boletim_process.php
// Inclui a conexão com o banco de dados
include '../db.php';

// Define o cabeçalho para resposta JSON
header('Content-Type: application/json');

// Verifica se a requisição é POST e se o parâmetro 'n_bg' está presente
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['n_bg'])) {
    $n_bg = $_POST['n_bg'];

    try {
        // Prepara a consulta para atualizar o campo bole_ass_cmt para 1
        $stmt = $pdo->prepare("UPDATE bg.vw_boletim SET bole_ass_cmt = 1 WHERE n_bg = :n_bg");
        $stmt->bindValue(':n_bg', $n_bg, PDO::PARAM_STR); // Ajuste o tipo conforme necessário
        $stmt->execute();

        // Verifica se algum registro foi atualizado
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Boletim assinado com sucesso!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Boletim não encontrado ou já assinado.']);
        }
    } catch (PDOException $e) {
        // Log do erro
        error_log("Erro ao assinar boletim: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erro ao assinar o boletim.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Requisição inválida.']);
}
?>
