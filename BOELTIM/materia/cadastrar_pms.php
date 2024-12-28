<?php
// cadastrar_pms.php

session_start(); // Inicia a sessão

include '../db.php'; // Inclua a conexão com o banco de dados

// Ativa a exibição de erros para depuração (remova em produção)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verifique se é uma requisição AJAX para gerenciar PMs
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'buscar_policiais') {
        $matricula = $_POST['matricula'];

        // Consulta a view vw_policiais_militares com base na matrícula
        try {
            $stmt = $pdo->prepare('SELECT matricula, nome, pg_descricao, cpf, unidade FROM bg.vw_policiais_militares WHERE matricula = :matricula');
            $stmt->execute(['matricula' => $matricula]);
            $policial = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($policial) {
                // Retorna apenas os dados necessários
                echo json_encode([
                    'matricula' => $policial['matricula'],
                    'nome' => $policial['nome'],
                    'pg_descricao' => $policial['pg_descricao'],
                    'cpf' => $policial['cpf'],
                    'unidade' => $policial['unidade']
                ]);
            } else {
                echo json_encode(['error' => 'Policial não encontrado.']);
            }
        } catch (PDOException $e) {
            echo json_encode(['error' => 'Erro ao buscar policial: ' . $e->getMessage()]);
        }

    } elseif ($action === 'editar_policia') {
        // Atualiza os dados de posto/graduação e unidade
        $matricula = $_POST['matricula'];
        $pg_descricao = $_POST['pg_descricao'];
        $unidade = $_POST['unidade'];

        // Buscar id_pg com base em pg_descricao
        $stmt_pg = $pdo->prepare('SELECT DISTINCT id_pg FROM bg.vw_policiais_militares WHERE pg_descricao = :pg_descricao LIMIT 1');
        $stmt_pg->execute(['pg_descricao' => $pg_descricao]);
        $result_pg = $stmt_pg->fetch(PDO::FETCH_ASSOC);
        $id_pg = $result_pg ? $result_pg['id_pg'] : null;

        // Buscar cod_unidade com base em unidade
        $stmt_unidade = $pdo->prepare('SELECT DISTINCT cod_unidade FROM bg.vw_policiais_militares WHERE unidade = :unidade LIMIT 1');
        $stmt_unidade->execute(['unidade' => $unidade]);
        $result_unidade = $stmt_unidade->fetch(PDO::FETCH_ASSOC);
        $cod_unidade = $result_unidade ? $result_unidade['cod_unidade'] : null;

        // Verificar se os IDs foram encontrados
        if (!$id_pg || !$cod_unidade) {
            echo json_encode(['error' => 'Não foi possível encontrar os códigos correspondentes para Posto/Graduação ou Unidade.']);
            exit;
        }

        try {
            // Atualiza a tabela pessoa_materia com os códigos de posto/graduação e lotação (unidade)
            $stmt = $pdo->prepare('UPDATE bg.pessoa_materia SET fk_index_post_grad_cod = :fk_index_post_grad_cod, fk_poli_lota_cod = :fk_poli_lota_cod WHERE fk_poli_mili_matricula = :matricula');
            $stmt->execute([
                'fk_index_post_grad_cod' => $id_pg,
                'fk_poli_lota_cod' => $cod_unidade,
                'matricula' => $matricula
            ]);
            echo json_encode(['success' => 'Dados atualizados com sucesso.']);
        } catch (PDOException $e) {
            echo json_encode(['error' => 'Erro ao atualizar dados: ' . $e->getMessage()]);
        }
    } elseif ($action === 'excluir_policia') {
        // Exclui o registro da tabela
        $matricula = $_POST['matricula'];

        try {
            $stmt = $pdo->prepare('DELETE FROM bg.pessoa_materia WHERE fk_poli_mili_matricula = :matricula');
            $stmt->execute(['matricula' => $matricula]);
            echo json_encode(['success' => 'Registro excluído com sucesso!']);
        } catch (PDOException $e) {
            echo json_encode(['error' => 'Erro ao excluir registro: ' . $e->getMessage()]);
        }
    }
    exit;
}
?>
