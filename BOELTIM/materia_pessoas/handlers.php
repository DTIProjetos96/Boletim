<?php
require_once 'functions.php';

// Verifica requisição AJAX para buscar texto do Assunto Específico
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'fetch_texto_assunto') {
    require_once '../db.php'; // Certifique-se de incluir a conexão com o banco de dados
    $assuEspeCod = isset($_GET['assu_espe_cod']) ? (int)$_GET['assu_espe_cod'] : 0;

    try {
        // Consulta que busca o texto do Assunto Específico e informações do Assunto Geral
        $stmt = $pdo->prepare("
            SELECT assu_espe_texto, assu_gera_cod, assu_gera_descricao 
            FROM bg.vw_assunto_concatenado 
            WHERE assu_espe_cod = :assu_espe_cod
        ");
        $stmt->execute(['assu_espe_cod' => $assuEspeCod]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        // Retorna os dados no formato JSON
        echo json_encode([
            'success' => true,
            'assu_espe_texto' => $result['assu_espe_texto'] ?? '',
            'assu_gera_cod' => $result['assu_gera_cod'] ?? '',
            'assu_gera_descricao' => $result['assu_gera_descricao'] ?? '',
        ]);
    } catch (PDOException $e) {
        // Registra o erro no log e retorna um JSON de erro
        error_log("Erro ao buscar texto do Assunto Específico: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Funções auxiliares para buscar dados
function fetchUnidades($pdo)
{
    $stmt = $pdo->query("SELECT DISTINCT unidade FROM bg.vw_policiais_militares ORDER BY unidade");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetchPostosGraduacoes($pdo)
{
    $stmt = $pdo->query("SELECT DISTINCT pg_descricao FROM bg.vw_policiais_militares ORDER BY pg_descricao");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetchAssuntosEspecificos($pdo)
{
    $stmt = $pdo->query("
        SELECT assu_espe_cod, assu_espe_descricao, assu_gera_cod, assu_gera_descricao, assu_espe_texto 
        FROM bg.vw_assunto_concatenado 
        ORDER BY assu_espe_descricao
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetchSubunidades($pdo, $user_login)
{
    $stmt = $pdo->prepare("
        SELECT subu_cod, concat(subu_descricao, ' - ', unid_descricao, ' - ', coma_descricao) as descricao
        FROM public.vw_comando_unidade_subunidade 
        WHERE subu_cod IN (
            SELECT fk_subunidade
            FROM bg.vw_permissao 
            WHERE fk_login = :login AND perm_ativo = 1
        )
        ORDER BY subu_descricao, unid_descricao, coma_descricao
    ");
    $stmt->execute(['login' => $user_login]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetchTiposDocumento($pdo)
{
    $stmt = $pdo->query("SELECT tipo_docu_cod, tipo_docu_descricao FROM bg.tipo_documento ORDER BY tipo_docu_descricao");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetchMateriaById($pdo, $mate_bole_cod)
{
    $stmt = $pdo->prepare("SELECT * FROM bg.materia_boletim WHERE mate_bole_cod = :mate_bole_cod");
    $stmt->execute(['mate_bole_cod' => $mate_bole_cod]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
