<?php
// Inclua o arquivo de conexão ao banco de dados
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include('../db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['ajaxtp'];

    if ($action === 'save') {
        if ($_POST['item_mode'] === 'updstep') {
            $sql_update = "UPDATE bg.materia_publicacao SET fk_bole_cod = ? WHERE mate_publ_cod = ?";
            $stmt = $pdo->prepare($sql_update);
            $stmt->execute([$_POST['item_value'], $_POST['mate_publ_cod']]);
            $affected_rows = $stmt->rowCount();
            if ($affected_rows > 0) {
                echo "Atualização bem-sucedida";
            } else {
                echo "Nenhuma linha atualizada. Verifique o identificador.";
            }
        }
    } elseif ($action === 'insert') {
        $today = date("Y-m-d");
        $sql_insert = "INSERT INTO bg.materia_boletim (mate_bole_texto_aberto, mate_bole_data, fk_bole_cod) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql_insert);
        $stmt->execute([$_POST['strtitle'], $today, $_POST['bole_cod']]);
        echo "Inserção bem-sucedida";
    } elseif ($action === 'delete') {
        $sql_delete = "DELETE FROM bg.materia_boletim WHERE mate_bole_cod = ?";
        $stmt = $pdo->prepare($sql_delete);
        $stmt->execute([$_POST['mate_cod']]);
        echo "Exclusão bem-sucedida";
    }
}
?>
