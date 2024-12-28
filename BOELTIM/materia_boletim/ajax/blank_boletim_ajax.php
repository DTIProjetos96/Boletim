<?php 
// ajax/blank_boletim_ajax.php

header('Content-Type: application/json');
require_once '../db.php'; // Caminho relativo para o arquivo db.php

// Função para retornar respostas JSON
function respostaJSON($status, $mensagem) {
    echo json_encode(['status' => $status, 'message' => $mensagem]);
    exit;
}

// Função para registrar logs
function registrarLog($mensagem) {
    $arquivo_log = 'blank_boletim_ajax.log';
    $timestamp = date('Y-m-d H:i:s');
    $mensagem_completa = "[$timestamp] $mensagem\n";
    file_put_contents($arquivo_log, $mensagem_completa, FILE_APPEND);
}

// Verifica se a requisição é via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitiza os inputs
    $ajaxtp = isset($_POST['ajaxtp']) ? $_POST['ajaxtp'] : '';
    
    if ($ajaxtp === 'save') {
        // Atualizar matéria no boletim
        $item_mode = isset($_POST['item_mode']) ? $_POST['item_mode'] : '';
        $mate_publ_cod = isset($_POST['mate_publ_cod']) ? intval($_POST['mate_publ_cod']) : 0;
        $item_value = isset($_POST['item_value']) ? intval($_POST['item_value']) : 0;

        // Log dos parâmetros recebidos
        registrarLog("Recebido AJAX 'save': item_mode=$item_mode, mate_publ_cod=$mate_publ_cod, item_value=$item_value");

        // Verificação detalhada dos parâmetros
        if ($item_mode !== 'updstep') {
            registrarLog("Erro: Parâmetro 'item_mode' deve ser 'updstep'. Valor fornecido: $item_mode");
            respostaJSON('error', 'Erro: Parâmetro "item_mode" deve ser "updstep".');
        }

        if ($mate_publ_cod <= 0) {
            registrarLog("Erro: 'mate_publ_cod' deve ser maior que zero. Valor fornecido: $mate_publ_cod");
            respostaJSON('error', 'Erro: "mate_publ_cod" deve ser maior que zero.');
        }

        if ($item_value <= 0) {
            registrarLog("Erro: 'item_value' deve ser maior que zero. Valor fornecido: $item_value");
            respostaJSON('error', 'Erro: "item_value" deve ser maior que zero.');
        }

        // Se todos os parâmetros estiverem corretos, realiza a atualização
        try {
            $sql_update = "UPDATE bg.materia_publicacao SET fk_bole_cod = :fk_bole_cod WHERE mate_publ_cod = :mate_publ_cod";
            $stmt = $pdo->prepare($sql_update);
            $stmt->execute([
                'fk_bole_cod' => $item_value,
                'mate_publ_cod' => $mate_publ_cod
            ]);
            
            $linhas_afetadas = $stmt->rowCount();
            registrarLog("Tentativa de atualização: mate_publ_cod=$mate_publ_cod, novo fk_bole_cod=$item_value. Linhas afetadas: $linhas_afetadas");

            if ($linhas_afetadas > 0) {
                registrarLog("Atualização bem-sucedida para mate_publ_cod=$mate_publ_cod.");
                respostaJSON('success', 'Matéria movida com sucesso.');
            } else {
                registrarLog("Nenhuma linha atualizada para mate_publ_cod=$mate_publ_cod.");
                respostaJSON('error', 'Nenhuma linha foi atualizada. Verifique se os códigos estão corretos.');
            }
        } catch (PDOException $e) {
            registrarLog("Erro na atualização: " . $e->getMessage());
            respostaJSON('error', 'Erro na atualização: ' . $e->getMessage());
        }
    }
    elseif ($ajaxtp === 'insert') {
        // Inserir nova matéria no boletim
        $strtitle = isset($_POST['strtitle']) ? trim($_POST['strtitle']) : '';
        $bole_cod = isset($_POST['bole_cod']) ? intval($_POST['bole_cod']) : 0;

        if ($strtitle === '') {
            registrarLog("Erro: Título da matéria não pode ser vazio.");
            respostaJSON('error', 'Erro: Título da matéria não pode ser vazio.');
        }

        if ($bole_cod <= 0) {
            registrarLog("Erro: 'bole_cod' deve ser maior que zero. Valor fornecido: $bole_cod");
            respostaJSON('error', 'Erro: "bole_cod" deve ser maior que zero.');
        }

        $today = date("Y-m-d");
        try {
            $sql_insert = "INSERT INTO bg.materia_boletim (mate_bole_texto_aberto, mate_bole_data, fk_bole_cod) VALUES (:texto, :data, :bole_cod)";
            $stmt = $pdo->prepare($sql_insert);
            $stmt->execute([
                'texto' => $strtitle,
                'data' => $today,
                'bole_cod' => $bole_cod
            ]);
            $id_inserido = $pdo->lastInsertId();
            registrarLog("Inserção bem-sucedida: mate_bole_cod=$id_inserido, fk_bole_cod=$bole_cod");
            respostaJSON('success', 'Inserção bem-sucedida');
        } catch (PDOException $e) {
            registrarLog("Erro na inserção: " . $e->getMessage());
            respostaJSON('error', 'Erro na inserção: ' . $e->getMessage());
        }
    }
    elseif ($ajaxtp === 'delete') {
        // Excluir matéria do boletim
        $mate_cod = isset($_POST['mate_cod']) ? $_POST['mate_cod'] : '';

        if (empty($mate_cod)) {
            registrarLog("Erro: 'mate_cod' deve ser maior que zero. Valor fornecido: $mate_cod");
            respostaJSON('error', 'Erro: "mate_cod" deve ser maior que zero.');
        }

        // Pode receber múltiplos IDs separados por vírgula
        $mate_cod_array = explode(',', $mate_cod);
        $mate_cod_array = array_map('intval', $mate_cod_array);
        $mate_cod_array = array_filter($mate_cod_array, function($value) {
            return $value > 0;
        });

        if (empty($mate_cod_array)) {
            registrarLog("Erro: Nenhuma matéria válida para excluir.");
            respostaJSON('error', 'Erro: Nenhuma matéria válida para excluir.');
        }

        try {
            $sql_delete = "DELETE FROM bg.materia_publicacao WHERE mate_publ_cod = ANY(:mate_cod_array)";
            $stmt = $pdo->prepare($sql_delete);
            // Para passar array no PostgreSQL, converta para formato de array string
            $mate_cod_pg = '{' . implode(',', $mate_cod_array) . '}';
            $stmt->bindParam(':mate_cod_array', $mate_cod_pg, PDO::PARAM_STR);
            $stmt->execute();

            $linhas_afetadas = $stmt->rowCount();
            registrarLog("Tentativa de exclusão: mate_publ_cod=" . implode(',', $mate_cod_array) . ". Linhas afetadas: $linhas_afetadas");

            if ($linhas_afetadas > 0) {
                registrarLog("Exclusão bem-sucedida para mate_publ_cod=" . implode(',', $mate_cod_array) . ".");
                respostaJSON('success', 'Matérias excluídas com sucesso do boletim.');
            } else {
                registrarLog("Nenhuma linha excluída para mate_publ_cod=" . implode(',', $mate_cod_array) . ".");
                respostaJSON('error', 'Nenhuma linha foi excluída. Verifique se os códigos estão corretos.');
            }
        } catch (PDOException $e) {
            registrarLog("Erro na exclusão: " . $e->getMessage());
            respostaJSON('error', 'Erro na exclusão: ' . $e->getMessage());
        }
    }
    else {
        registrarLog("Ação AJAX desconhecida: $ajaxtp");
        respostaJSON('error', 'Ação AJAX desconhecida: ' . $ajaxtp);
    }
} else {
    registrarLog("Método de requisição inválido. Use o método POST.");
    respostaJSON('error', 'Método de requisição inválido. Use o método POST.');
}
?>
