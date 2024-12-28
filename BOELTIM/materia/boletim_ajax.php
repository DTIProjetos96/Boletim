<?php
// boletim_ajax.php

// Incluir a conexão com o banco de dados
require_once '../db.php';

// Definir o tipo de conteúdo como texto
header('Content-Type: text/plain; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ajaxtp = isset($_POST['ajaxtp']) ? $_POST['ajaxtp'] : '';

    if ($ajaxtp === 'save') {
        // Atualizar a posição da matéria no Kanban (mudar de boletim)
        $item_mode     = isset($_POST['item_mode']) ? $_POST['item_mode'] : '';
        $mate_publ_cod = isset($_POST['mate_publ_cod']) ? intval($_POST['mate_publ_cod']) : 0;
        $item_value    = isset($_POST['item_value']) ? intval($_POST['item_value']) : 0;

        if ($item_mode === 'updstep' && $mate_publ_cod > 0 && $item_value > 0) {
            try {
                $sql_update = "UPDATE bg.materia_publicacao 
                               SET fk_bole_cod = :new_bole_cod 
                               WHERE mate_publ_cod = :mate_publ_cod";
                $stmt_update = $pdo->prepare($sql_update);
                $stmt_update->bindParam(':new_bole_cod', $item_value, PDO::PARAM_INT);
                $stmt_update->bindParam(':mate_publ_cod', $mate_publ_cod, PDO::PARAM_INT);
                $stmt_update->execute();

                echo "Atualização bem-sucedida";
            } catch (PDOException $e) {
                echo "Erro ao atualizar: " . $e->getMessage();
            }
        } else {
            echo "Dados inválidos para atualização.";
        }

    } elseif ($ajaxtp === 'insert') {
        // Inserir uma nova matéria
        $strtitle = isset($_POST['strtitle']) ? trim($_POST['strtitle']) : '';
        $bole_cod = isset($_POST['bole_cod']) ? intval($_POST['bole_cod']) : 0;

        if ($strtitle !== '' && $bole_cod > 0) {
            $today = date("Y-m-d");
            try {
                $sql_insert = "INSERT INTO bg.materia_boletim 
                               (mate_bole_texto_aberto, mate_bole_data, fk_bole_cod) 
                               VALUES (:texto_aberto, :data, :bole_cod)";
                $stmt_insert = $pdo->prepare($sql_insert);
                $stmt_insert->bindParam(':texto_aberto', $strtitle, PDO::PARAM_STR);
                $stmt_insert->bindParam(':data', $today, PDO::PARAM_STR);
                $stmt_insert->bindParam(':bole_cod', $bole_cod, PDO::PARAM_INT);
                $stmt_insert->execute();

                echo "Inserção bem-sucedida";
            } catch (PDOException $e) {
                echo "Erro ao inserir: " . $e->getMessage();
            }
        } else {
            echo "Dados inválidos para inserção.";
        }

    } elseif ($ajaxtp === 'delete') {
        // Deletar uma matéria
        $mate_cod = isset($_POST['mate_cod']) ? intval($_POST['mate_cod']) : 0;

        if ($mate_cod > 0) {
            try {
                // Primeiro, deletar da tabela materia_publicacao
                $sql_delete_mp = "DELETE FROM boletimgeral.materia_publicacao 
                                  WHERE mate_publ_cod = :mate_cod";
                $stmt_delete_mp = $pdo->prepare($sql_delete_mp);
                $stmt_delete_mp->bindParam(':mate_cod', $mate_cod, PDO::PARAM_INT);
                $stmt_delete_mp->execute();

                // Depois, deletar da tabela materia_boletim (se necessário)
                $sql_delete_mb = "DELETE FROM boletimgeral.materia_boletim 
                                  WHERE mate_bole_cod = (SELECT fk_mate_bole_cod 
                                                         FROM boletimgeral.materia_publicacao 
                                                         WHERE mate_publ_cod = :mate_cod)";
                $stmt_delete_mb = $pdo->prepare($sql_delete_mb);
                $stmt_delete_mb->bindParam(':mate_cod', $mate_cod, PDO::PARAM_INT);
                $stmt_delete_mb->execute();

                echo "Exclusão bem-sucedida";
            } catch (PDOException $e) {
                echo "Erro ao excluir: " . $e->getMessage();
            }
        } else {
            echo "ID da matéria inválido para exclusão.";
        }

    } else {
        echo "Ação AJAX desconhecida.";
    }
} else {
    echo "Método de requisição inválido.";
}
?>
