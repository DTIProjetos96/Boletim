<?php

// Ativação de erros para depuração (remova em produção)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Inicia a sessão
session_start();

// Inclui os arquivos necessários
require_once '../db.php';
require_once 'functions.php';
require_once 'handlers.php';

// Define variáveis básicas
$mate_bole_cod = isset($_GET['mate_bole_cod']) ? (int)$_GET['mate_bole_cod'] : 0; // ID da matéria
$user_login = '452912'; // ID do usuário (ajuste para autenticação real)

// Verifica se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'add_materia') {
            // Inserir nova matéria
            $stmt = $pdo->prepare("
                INSERT INTO bg.materia_boletim 
                (mate_bole_texto, mate_bole_data, fk_tipo_docu_cod, fk_assu_espe_cod, mate_bole_nr_doc, mate_bole_data_doc, fk_subu_cod) 
                VALUES 
                (:mate_bole_texto, :mate_bole_data, :fk_tipo_docu_cod, :fk_assu_espe_cod, :mate_bole_nr_doc, :mate_bole_data_doc, :fk_subu_cod)
            ");
            $stmt->execute([
                ':mate_bole_texto' => $_POST['mate_bole_texto'] ?? '',
                ':mate_bole_data' => $_POST['mate_bole_data'] ?? null,
                ':fk_tipo_docu_cod' => $_POST['fk_tipo_docu_cod'] ?? null,
                ':fk_assu_espe_cod' => $_POST['fk_assu_espe_cod'] ?? null,
                ':mate_bole_nr_doc' => $_POST['mate_bole_nr_doc'] ?? '',
                ':mate_bole_data_doc' => $_POST['mate_bole_data_doc'] ?? null,
                ':fk_subu_cod' => $_POST['fk_subu_cod'] ?? null,
            ]);
            $mate_bole_cod = $pdo->lastInsertId();

            // Redireciona para recarregar a página com a matéria criada
            header("Location: cad_materia.php?mate_bole_cod=$mate_bole_cod&success=Matéria cadastrada com sucesso!");
            exit;
        } elseif ($action === 'update_materia') {
            // Atualizar matéria existente
            $stmt = $pdo->prepare("
                UPDATE bg.materia_boletim SET 
                    mate_bole_texto = :mate_bole_texto, 
                    mate_bole_data = :mate_bole_data, 
                    fk_tipo_docu_cod = :fk_tipo_docu_cod, 
                    fk_assu_espe_cod = :fk_assu_espe_cod, 
                    mate_bole_nr_doc = :mate_bole_nr_doc, 
                    mate_bole_data_doc = :mate_bole_data_doc, 
                    fk_subu_cod = :fk_subu_cod 
                WHERE mate_bole_cod = :mate_bole_cod
            ");
            $stmt->execute([
                ':mate_bole_texto' => $_POST['mate_bole_texto'] ?? '',
                ':mate_bole_data' => $_POST['mate_bole_data'] ?? null,
                ':fk_tipo_docu_cod' => $_POST['fk_tipo_docu_cod'] ?? null,
                ':fk_assu_espe_cod' => $_POST['fk_assu_espe_cod'] ?? null,
                ':mate_bole_nr_doc' => $_POST['mate_bole_nr_doc'] ?? '',
                ':mate_bole_data_doc' => $_POST['mate_bole_data_doc'] ?? null,
                ':fk_subu_cod' => $_POST['fk_subu_cod'] ?? null,
                ':mate_bole_cod' => $_POST['mate_bole_cod'] ?? null,
            ]);

            // Redireciona para recarregar a página com a matéria atualizada
            header("Location: cad_materia.php?mate_bole_cod={$_POST['mate_bole_cod']}&success=Matéria atualizada com sucesso!");
            exit;
        }
    } catch (Exception $e) {
        // Exibe mensagem de erro no caso de falha
        $errorMessage = "Erro ao processar a matéria: " . $e->getMessage();
    }
}

// Mensagem de sucesso ou erro
if (isset($_GET['success'])) {
    echo "<div class='alert alert-success'>{$_GET['success']}</div>";
} elseif (isset($errorMessage)) {
    echo "<div class='alert alert-danger'>$errorMessage</div>";
}

// Busca dados necessários para o formulário
try {
    $unidades = fetchUnidades($pdo);
    $postosGraduacoes = fetchPostosGraduacoes($pdo);
    $assuntosEspecificos = fetchAssuntosEspecificos($pdo);
    $subunidades = fetchSubunidades($pdo, $user_login);
    $tiposDocumento = fetchTiposDocumento($pdo);
    $materia = $mate_bole_cod > 0 ? fetchMateriaById($pdo, $mate_bole_cod) : [];
} catch (Exception $e) {
    die("Erro ao carregar dados: " . $e->getMessage());
}

// Mensagem de sucesso após operações
$mensagem_sucesso = isset($_GET['success']) ? htmlspecialchars($_GET['success']) : '';


// Inclui as partes do layout
include 'form.html.php';
include 'iframe.html.php';
?>

