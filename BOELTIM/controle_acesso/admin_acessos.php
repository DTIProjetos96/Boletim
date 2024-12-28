<?php
// admin_acessos.php

// Definir o cabeçalho de conteúdo para UTF-8
header('Content-Type: text/html; charset=UTF-8');

// Ativa a exibição de erros para depuração (remova em produção)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Inclui o arquivo de conexão com o banco de dados e funções
include_once __DIR__ . '/../functions.php';          // Ajuste o caminho conforme sua estrutura de diretórios
include_once __DIR__ . '/../db.php';                // Ajuste o caminho conforme sua estrutura de diretórios

// Inicia a sessão se ainda não estiver iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verifica se há uma mensagem na sessão
if (isset($_SESSION['message'])) {
    echo "<div class='alert alert-info alert-dismissible fade show' role='alert'>" . htmlspecialchars($_SESSION['message'], ENT_QUOTES, 'UTF-8') . "
        <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
            <span aria-hidden='true'>&times;</span>
        </button>
    </div>";
    unset($_SESSION['message']); // Remove a mensagem da sessão
}

// Verifica se o usuário está logado
if (!isset($_SESSION['matricula'])) {
    debug_log("Usuário não está logado. Redirecionando para a página de login.");
    header('Location: https://pmrr.net/boletim/login/login_pm.php');
    exit();
}

// Recupera a matrícula do usuário da sessão
$matricula = $_SESSION['matricula'];
debug_log("Matrícula do usuário: $matricula");

/**
 * Função para listar os acessos de botões com paginação, filtro por matrícula e ordenação
 *
 * @param PDO $pdo Objeto PDO para conexão com o banco de dados
 * @param int $currentPage Página atual
 * @param int $recordsPerPage Número de registros por página
 * @param string $filterMatricula Matrícula para filtrar os resultados
 * @param string $sortCol Coluna para ordenar
 * @param string $sortOrder Ordem de ordenação (asc|desc)
 * @return array Array contendo os resultados e informações de paginação
 */
function listar_acessos_botao($pdo, $currentPage, $recordsPerPage, $filterMatricula = '', $sortCol = 'controle_acesso_pagina', $sortOrder = 'asc') {
    // Validação das colunas permitidas para ordenação
    $allowedSort = ['controle_acesso_pagina', 'controle_acesso_matricula'];
    if (!in_array($sortCol, $allowedSort)) {
        $sortCol = 'controle_acesso_pagina';
    }

    // Validação da ordem de ordenação
    $sortOrder = strtolower($sortOrder) === 'desc' ? 'desc' : 'asc';

    // Base da consulta sem agregação
    $sql = "SELECT 
                split_part(controle_acesso_pagina, '|', 1) AS pagina,
                controle_acesso_matricula,
                split_part(controle_acesso_pagina, '|', 2) AS botao_id,
                controle_acesso_id
            FROM 
                bg.controle_acesso 
            WHERE 
                tipo = 'botao'";

    // Adiciona filtro por matrícula se fornecido
    if (!empty($filterMatricula)) {
        $sql .= " AND controle_acesso_matricula = :filterMatricula";
    }

    // Ordenação
    $sql .= " ORDER BY $sortCol $sortOrder";

    // Contagem total de registros para paginação
    $countSql = "SELECT COUNT(*) FROM bg.controle_acesso WHERE tipo = 'botao'";
    if (!empty($filterMatricula)) {
        $countSql .= " AND controle_acesso_matricula = :filterMatricula";
    }

    try {
        // Preparar e executar a consulta de contagem
        $countStmt = $pdo->prepare($countSql);
        if (!empty($filterMatricula)) {
            $countStmt->bindValue(':filterMatricula', $filterMatricula, PDO::PARAM_INT);
        }
        $countStmt->execute();
        $totalRecords = $countStmt->fetchColumn();
        debug_log("Total de registros encontrados: $totalRecords");

        // Cálculo do offset
        $offset = ($currentPage - 1) * $recordsPerPage;

        // Adiciona LIMIT e OFFSET à consulta
        $sql .= " LIMIT :limit OFFSET :offset";

        // Preparar a consulta principal
        $stmt = $pdo->prepare($sql);


        // Vincular parâmetros se necessário
        if (!empty($filterMatricula)) {
            $stmt->bindValue(':filterMatricula', $filterMatricula, PDO::PARAM_INT);
        }
        $stmt->bindValue(':limit', (int)$recordsPerPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

        // Executar a consulta
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        debug_log("Número de registros na página atual: " . count($result));

        // Cálculo do total de páginas
        $totalPages = ceil($totalRecords / $recordsPerPage);

        return [
            'data' => $result,
            'total_pages' => $totalPages,
            'current_page' => $currentPage,
            'total_records' => $totalRecords
        ];
    } catch (PDOException $e) {
        // Log e exibe o erro para depuração
        debug_log("Erro na consulta SQL (listar_acessos_botao): " . $e->getMessage());
        echo "<div class='alert alert-danger'>Erro ao listar acessos de botões: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "</div>";
        return [
            'data' => [],
            'total_pages' => 0,
            'current_page' => 1,
            'total_records' => 0
        ];
    }
}

/**
 * Função para excluir um acesso de botão
 *
 * @param int $id ID do acesso a ser excluído
 * @return void
 */
function excluir_acesso_botao($id) {
    global $pdo; // Declaração global para acessar a variável $pdo

    debug_log("Tentando excluir o acesso de botão com ID: $id");

    // SQL para excluir o acesso no esquema 'bg'
    $sql = "DELETE FROM bg.controle_acesso WHERE controle_acesso_id = :id AND tipo = 'botao'";

    try {
        // Preparar a consulta
        $stmt = $pdo->prepare($sql);
        debug_log("Executando a consulta: $sql com ID: $id");

        // Executar a consulta
        $stmt->execute([':id' => $id]);

        // Armazena a mensagem na sessão
        $_SESSION['message'] = "Acesso de botão excluído com sucesso.";
        debug_log("Acesso de botão com ID $id excluído com sucesso.");

        // Redireciona para a mesma página para aplicar o PRG
        // Preserva os parâmetros atuais de página, filtro e ordenação
        $redirectPage = isset($_GET['page']) ? $_GET['page'] : 1;
        $redirectFilter = isset($_GET['filter_matricula']) ? '&filter_matricula=' . $_GET['filter_matricula'] : '';
        $redirectSort = isset($_GET['sort']) ? '&sort=' . $_GET['sort'] . '&order=' . $_GET['order'] : '';
        header("Location: " . $_SERVER['PHP_SELF'] . "?page=" . $redirectPage . $redirectFilter . $redirectSort);
        exit();

    } catch (PDOException $e) {
        // Log e exibe o erro para depuração
        debug_log("Erro na consulta SQL (excluir_acesso_botao): " . $e->getMessage());
        $_SESSION['message'] = "Erro ao excluir acesso de botão.";
        $redirectPage = isset($_GET['page']) ? $_GET['page'] : 1;
        $redirectFilter = isset($_GET['filter_matricula']) ? '&filter_matricula=' . $_GET['filter_matricula'] : '';
        $redirectSort = isset($_GET['sort']) ? '&sort=' . $_GET['sort'] . '&order=' . $_GET['order'] : '';
        header("Location: " . $_SERVER['PHP_SELF'] . "?page=" . $redirectPage . $redirectFilter . $redirectSort);
        exit();
    }
}

/**
 * Função para adicionar ou atualizar o acesso de botão
 *
 * @param string $id ID do acesso (para atualização)
 * @param string $pagina Nome da página
 * @param string $botao_ids Identificador do botão (não mais múltiplos)
 * @param string $matriculas Matrícula do usuário (não mais múltiplas)
 * @return void
 */
function salvar_acesso_botao($id, $pagina, $botao_ids, $matriculas) {
    global $pdo; // Declaração global para acessar a variável $pdo

    debug_log("Salvando acesso de botão. ID: $id, Página: $pagina, Botão: $botao_ids, Matrícula: $matriculas");

    // Validação básica dos dados
    if (empty($pagina) || empty($botao_ids) || empty($matriculas)) {
        debug_log("Dados incompletos para salvar o acesso de botão.");
        $_SESSION['message'] = "Todos os campos são obrigatórios.";
        // Preserva os parâmetros atuais de página, filtro e ordenação
        $redirectPage = isset($_GET['page']) ? $_GET['page'] : 1;
        $redirectFilter = isset($_GET['filter_matricula']) ? '&filter_matricula=' . $_GET['filter_matricula'] : '';
        $redirectSort = isset($_GET['sort']) ? '&sort=' . $_GET['sort'] . '&order=' . $_GET['order'] : '';
        header("Location: " . $_SERVER['PHP_SELF'] . "?page=" . $redirectPage . $redirectFilter . $redirectSort);
        exit();
    }

    // Sanitização básica
    $pagina = trim($pagina);
    $botao_id = trim($botao_ids);
    $matricula = trim($matriculas);

    // Validar matrícula numérica
    if (!is_numeric($matricula)) {
        debug_log("Matrícula inválida: $matricula");
        $_SESSION['message'] = "A matrícula deve ser numérica.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    try {
        if ($id === '') {
            // Inserção de um novo acesso de botão
            // Verificar se já existe um registro com a mesma página, botão e matrícula
            $pagina_botao = strtolower($pagina . '|' . $botao_id);
            $checkSql = "SELECT COUNT(*) FROM bg.controle_acesso 
                        WHERE controle_acesso_pagina = :pagina_botao 
                          AND controle_acesso_matricula = :matricula 
                          AND tipo = 'botao'";
            $checkStmt = $pdo->prepare($checkSql);
            $checkStmt->execute([
                ':pagina_botao' => $pagina_botao,
                ':matricula' => $matricula
            ]);
            $count = $checkStmt->fetchColumn();

            if ($count == 0) {
                $insertSql = "INSERT INTO bg.controle_acesso (controle_acesso_pagina, controle_acesso_matricula, tipo) 
                              VALUES (:pagina_botao, :matricula, 'botao')";
                $insertStmt = $pdo->prepare($insertSql);
                $insertStmt->execute([
                    ':pagina_botao' => $pagina_botao,
                    ':matricula' => $matricula
                ]);
                debug_log("Acesso de botão inserido para matrícula: $matricula");
                $_SESSION['message'] = "Acesso de botão adicionado com sucesso.";
            } else {
                debug_log("Acesso já existe para PáginaBotão: $pagina_botao, Matrícula: $matricula");
                $_SESSION['message'] = "Acesso já existe para esta Página, Botão e Matrícula.";
            }
        } else {
            // Atualização de um acesso de botão existente
            // Atualizar o registro com o ID fornecido
            $pagina_botao = strtolower($pagina . '|' . $botao_id);
            $updateSql = "UPDATE bg.controle_acesso 
                          SET controle_acesso_pagina = :pagina_botao, controle_acesso_matricula = :matricula 
                          WHERE controle_acesso_id = :id AND tipo = 'botao'";
            $updateStmt = $pdo->prepare($updateSql);
            $updateStmt->execute([
                ':pagina_botao' => $pagina_botao,
                ':matricula' => $matricula,
                ':id' => $id
            ]);
            debug_log("Acesso de botão com ID $id atualizado com sucesso.");
            $_SESSION['message'] = "Acesso de botão atualizado com sucesso.";
        }

        // Redireciona para a mesma página para aplicar o PRG
        $redirectPage = isset($_GET['page']) ? $_GET['page'] : 1;
        $redirectFilter = isset($_GET['filter_matricula']) ? '&filter_matricula=' . $_GET['filter_matricula'] : '';
        $redirectSort = isset($_GET['sort']) ? '&sort=' . $_GET['sort'] . '&order=' . $_GET['order'] : '';
        header("Location: " . $_SERVER['PHP_SELF'] . "?page=" . $redirectPage . $redirectFilter . $redirectSort);
        exit();

    } catch (PDOException $e) {
        // Log e exibe o erro para depuração
        debug_log("Erro na consulta SQL (salvar_acesso_botao): " . $e->getMessage());
        $_SESSION['message'] = "Erro ao salvar acesso de botão.";
        $redirectPage = isset($_GET['page']) ? $_GET['page'] : 1;
        $redirectFilter = isset($_GET['filter_matricula']) ? '&filter_matricula=' . $_GET['filter_matricula'] : '';
        $redirectSort = isset($_GET['sort']) ? '&sort=' . $_GET['sort'] . '&order=' . $_GET['order'] : '';
        header("Location: " . $_SERVER['PHP_SELF'] . "?page=" . $redirectPage . $redirectFilter . $redirectSort);
        exit();
    }
}

// Processa as ações de salvar e excluir
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        debug_log("Ação recebida: $action");

        if ($action === 'add') {
            // Adicionar um novo acesso de botão
            $pagina = isset($_POST['pagina']) ? $_POST['pagina'] : '';
            $botao_ids = isset($_POST['botao_id']) ? $_POST['botao_id'] : '';
            $matriculas = isset($_POST['matricula']) ? $_POST['matricula'] : '';

            // Dividir os botões e matrículas
            $botaoArray = preg_split('/[;,]+/', $botao_ids);
            $matriculaArray = preg_split('/[;,]+/', $matriculas);

            foreach ($botaoArray as $botao) {
                foreach ($matriculaArray as $matricula) {
                    salvar_acesso_botao('', $pagina, $botao, $matricula);
                }
            }
        } elseif ($action === 'save_edit') {
            // Editar um acesso de botão existente
            $id = isset($_POST['id']) ? $_POST['id'] : '';
            $pagina = isset($_POST['pagina']) ? $_POST['pagina'] : '';
            $botao_ids = isset($_POST['botao_ids']) ? $_POST['botao_ids'] : '';
            $matriculas = isset($_POST['matricula']) ? $_POST['matricula'] : '';
            salvar_acesso_botao($id, $pagina, $botao_ids, $matriculas);
        } elseif ($action === 'delete') {
            // Excluir um acesso de botão
            $id = isset($_POST['id']) ? $_POST['id'] : '';
            excluir_acesso_botao($id);
        }
    }
}

// Definições para Paginação
$recordsPerPage = 10; // Número de registros por página
$currentPage = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$filterMatricula = isset($_GET['filter_matricula']) && is_numeric($_GET['filter_matricula']) ? (int)$_GET['filter_matricula'] : '';

// Definições para Ordenação
$sortCol = isset($_GET['sort']) ? $_GET['sort'] : 'controle_acesso_pagina';
$sortOrder = isset($_GET['order']) ? $_GET['order'] : 'asc';

// Obter os dados com paginação, filtro e ordenação
$acessos = listar_acessos_botao($pdo, $currentPage, $recordsPerPage, $filterMatricula, $sortCol, $sortOrder);
$totalPages = $acessos['total_pages'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Administração de Acessos - Botões</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- Font Awesome para ícones -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <style>
        /* Estilos personalizados ajustados para serem mais compactos */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f8fb;
            color: #333;
            padding: 10px; /* Reduzido de 20px para 10px */
        }

        h1 {
            color: #1e2a36;
            margin-bottom: 15px; /* Reduzido de 20px para 15px */
            font-size: 22px; /* Reduzido de 24px para 22px */
        }

        .form-container, .table-container, .faq-container {
            background-color: #fff;
            padding: 15px; /* Reduzido de 20px para 15px */
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px; /* Reduzido de 30px para 20px */
        }

        .form-container form label {
            display: block;
            margin-top: 8px; /* Reduzido de 10px para 8px */
            font-weight: bold;
            font-size: 14px; /* Adicionado tamanho de fonte */
        }

        .form-container form input, .form-container form select {
            width: 100%;
            padding: 8px; /* Reduzido de 10px para 8px */
            margin-top: 3px; /* Reduzido de 5px para 3px */
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px; /* Adicionado tamanho de fonte */
        }

        .form-container form button {
            margin-top: 10px; /* Reduzido de 15px para 10px */
            padding: 8px 16px; /* Reduzido de 10px 20px para 8px 16px */
            background-color: #3498db;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px; /* Adicionado tamanho de fonte */
        }

        .form-container form button:hover {
            background-color: #2980b9;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px; /* Adicionado tamanho de fonte */
        }

        table thead th {
            background-color: #3498db;
            color: #fff;
            padding: 10px; /* Reduzido de 12px para 10px */
            text-align: center;
            white-space: nowrap;
            cursor: pointer;
        }

        table thead th a {
            color: #fff;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        table thead th a .sort-icon {
            margin-left: 5px;
            font-size: 12px;
        }

        table tbody td {
            padding: 8px; /* Reduzido de 10px para 8px */
            text-align: center;
            white-space: nowrap;
        }

        .btn-sm {
            padding: 4px 8px;
            font-size: 12px;
        }

        .actions button {
            margin-right: 5px; /* Reduzido de 10px para 5px */
        }

        .actions button:last-child {
            margin-right: 0;
        }

        .alert {
            font-size: 14px; /* Adicionado tamanho de fonte */
        }

        /* Ajuste para dispositivos menores */
        @media (max-width: 576px) {
            .form-row {
                flex-direction: column;
            }

            .form-group.col-md-4 {
                max-width: 100%;
            }

            table thead {
                display: none;
            }

            table, table tbody, table tr, table td {
                display: block;
                width: 100%;
            }

            table tr {
                margin-bottom: 15px;
            }

            table td {
                text-align: right;
                padding-left: 50%;
                position: relative;
            }

            table td::before {
                content: attr(data-label);
                position: absolute;
                left: 10px;
                width: 45%;
                padding-right: 10px;
                white-space: nowrap;
                text-align: left;
                font-weight: bold;
            }
        }

        /* Estilos para a seção FAQ */
        .faq-container h2 {
            font-size: 18px;
            margin-bottom: 10px;
            color: #1e2a36;
        }

        .faq-container .faq-item {
            margin-bottom: 10px;
        }

        .faq-container .faq-item h5 {
            cursor: pointer;
            position: relative;
        }

        .faq-container .faq-item h5::after {
            content: '\f0da'; /* Font Awesome icon for chevron-down */
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            position: absolute;
            right: 0;
        }

        .faq-container .faq-item.collapsed h5::after {
            content: '\f0d7'; /* Font Awesome icon for chevron-up */
        }

        .faq-container .faq-item .faq-content {
            display: none;
            padding: 10px;
            border-left: 2px solid #3498db;
            background-color: #f9f9f9;
        }

        .faq-container .faq-item.show .faq-content {
            display: block;
        }
    </style>
</head>
<body>

    <h1>Administração de Acessos - Botões</h1>

    <!-- Formulário para adicionar novos acessos de botões -->
    <div class="form-container">
        <form method="POST" action="">
            <input type="hidden" name="action" value="add">
            <div class="form-row">
                <div class="form-group col-md-3">
                    <label for="pagina">Página:</label>
                    <input type="text" name="pagina" id="pagina" placeholder="Ex: admin_acessos.php" required>
                </div>

                <div class="form-group col-md-3">
                    <label for="botao_id">Identificador do Botão:</label>
                    <input type="text" name="botao_id" id="botao_id" placeholder="Ex: editar, salvar, excluir" required>
                    <small class="form-text text-muted">Insira múltiplos identificadores de botões separados por vírgula ou ponto e vírgula.</small>
                </div>

                <div class="form-group col-md-3">
                    <label for="matricula">Matrícula do Usuário:</label>
                    <input type="text" name="matricula" id="matricula" placeholder="Ex: 405558,405559" required>
                    <small class="form-text text-muted">Insira múltiplas matrículas separadas por vírgula ou ponto e vírgula.</small>
                </div>

                <div class="form-group col-md-3 align-self-end">
                    <button type="submit">Adicionar</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Filtro por Matrícula -->
    <div class="form-container">
        <form method="GET" action="">
            <div class="form-row align-items-end">
                <div class="form-group col-md-4">
                    <label for="filter_matricula">Filtrar por Matrícula:</label>
                    <input type="number" name="filter_matricula" id="filter_matricula" placeholder="Ex: 405558" value="<?php echo htmlspecialchars($filterMatricula); ?>">
                </div>
                <div class="form-group col-md-4">
                    <button type="submit" class="btn btn-primary btn-sm">Filtrar</button>
                    <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-secondary btn-sm">Limpar</a>
                </div>
            </div>
        </form>
    </div>

    <!-- Tabela com os acessos de botões -->
    <div class="table-container">
        <h2>Controle de Acessos de Botões</h2>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <?php
                        /**
                         * Define as colunas disponíveis para ordenação
                         * Formato: 'coluna_bd' => 'Nome Exibido'
                         */
                        $columns = [
                            'controle_acesso_pagina' => 'Página',
                            'controle_acesso_matricula' => 'Matrícula'
                        ];

                        foreach ($columns as $col => $display) {
                            // Determina a ordem de ordenação para a coluna atual
                            $newOrder = 'asc';
                            $sortIcon = '';
                            if ($sortCol === $col) {
                                if ($sortOrder === 'asc') {
                                    $newOrder = 'desc';
                                    $sortIcon = '<i class="fas fa-sort-up sort-icon"></i>';
                                } else {
                                    $newOrder = 'asc';
                                    $sortIcon = '<i class="fas fa-sort-down sort-icon"></i>';
                                }
                            }

                            // Construir a URL para ordenação
                            $sortUrl = $_SERVER['PHP_SELF'] . '?page=1';
                            if (!empty($filterMatricula)) {
                                $sortUrl .= '&filter_matricula=' . $filterMatricula;
                            }
                            $sortUrl .= '&sort=' . $col . '&order=' . $newOrder;

                            echo "<th>
                                    <a href='" . htmlspecialchars($sortUrl) . "'>
                                        $display $sortIcon
                                    </a>
                                  </th>";
                        }

                        // Adicione a coluna "Identificadores dos Botões" sem ordenação
                        echo "<th>Identificador do Botão</th>";
                        echo "<th>Ações</th>";
                        ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $resultados = $acessos['data'];
                    if (count($resultados) > 0) {
                        foreach ($resultados as $row) {
                            debug_log("Renderizando a linha para Página: " . $row['pagina'] . ", Matrícula: " . $row['controle_acesso_matricula'] . ", Botão: " . $row['botao_id']);

                            // Obter o ID de acesso
                            $accessId = $row['controle_acesso_id'];

                            echo "<tr>";
                            echo "<form method='POST' action=''>";
                            echo "<input type='hidden' name='action' value='save_edit'>";
                            echo "<input type='hidden' name='id' value='" . htmlspecialchars($accessId, ENT_QUOTES, 'UTF-8') . "'>";

                            // Página
                            echo "<td data-label='Página'>";
                            echo "<span class='text'>" . htmlspecialchars($row['pagina'], ENT_QUOTES, 'UTF-8') . "</span>";
                            echo "<input type='text' name='pagina' value='" . htmlspecialchars($row['pagina'], ENT_QUOTES, 'UTF-8') . "' required readonly class='input-hidden form-control form-control-sm'>";
                            echo "</td>";

                            // Matrícula
                            echo "<td data-label='Matrícula'>";
                            echo "<span class='text'>" . htmlspecialchars($row['controle_acesso_matricula'], ENT_QUOTES, 'UTF-8') . "</span>";
                            echo "<input type='number' name='matricula' value='" . htmlspecialchars($row['controle_acesso_matricula'], ENT_QUOTES, 'UTF-8') . "' required readonly class='input-hidden form-control form-control-sm'>";
                            echo "</td>";

                            // Identificador do Botão
                            echo "<td data-label='Identificador do Botão'>";
                            echo "<span class='text'>" . htmlspecialchars($row['botao_id'], ENT_QUOTES, 'UTF-8') . "</span>";
                            echo "<input type='text' name='botao_ids' value='" . htmlspecialchars($row['botao_id'], ENT_QUOTES, 'UTF-8') . "' required readonly class='input-hidden form-control form-control-sm'>";
                            echo "</td>";

                            // Ações
                            echo "<td data-label='Ações' class='actions'>";
                            // Botão Editar
                            if (usuario_tem_acesso('admin_acessos.php|editar')) {
                                echo "<button 
                                        type='button' 
                                        class='btn btn-primary btn-acoes editar-btn btn-sm' 
                                        onclick='editRow(this)' 
                                        aria-label='Editar'
                                      >
                                        <i class='fas fa-pencil-alt'></i>
                                      </button>";
                            } else {
                                debug_log("Usuário não tem permissão para 'editar' em 'admin_acessos.php'.");
                            }

                            // Botão Excluir
                            if (usuario_tem_acesso('admin_acessos.php|excluir')) {
                                echo "<button 
                                        type='button' 
                                        class='btn btn-danger btn-acoes delete-btn btn-sm' 
                                        onclick='confirmDelete(" . htmlspecialchars($accessId, ENT_QUOTES, 'UTF-8') . ")' 
                                        aria-label='Excluir'
                                      >
                                        <i class='fas fa-trash'></i>
                                      </button>";
                            } else {
                                debug_log("Usuário não tem permissão para 'excluir' em 'admin_acessos.php'.");
                            }

                            // Botão Salvar
                            if (usuario_tem_acesso('admin_acessos.php|salvar')) {
                                echo "<button 
                                        type='submit' 
                                        class='btn btn-success save-btn btn-sm' 
                                        style='display: none;' 
                                        aria-label='Salvar'
                                      >
                                        <i class='fas fa-save'></i>
                                      </button>";

                                // Botão Cancelar
                                echo "<button 
                                        type='button' 
                                        class='btn btn-secondary cancel-btn btn-sm' 
                                        onclick='cancelEdit(this)' 
                                        style='display: none;' 
                                        aria-label='Cancelar'
                                      >
                                        <i class='fas fa-times'></i>
                                      </button>";
                            } else {
                                debug_log("Usuário não tem permissão para 'salvar' em 'admin_acessos.php'.");
                            }
                            echo "</td>";

                            echo "</form>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='4' class='text-center'>Nenhum acesso de botão encontrado.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <!-- Seção FAQ -->
        <div class="faq-container">
            <h2>FAQ - Como Integrar Controles de Acesso de Botões em Novas Páginas</h2>
            <div class="faq-item collapsed">
                <h5 onclick="toggleFaq(this)">Como ativar o controle de acesso para botões em uma nova página?</h5>
                <div class="faq-content">
                    <p>Para ativar o controle de acesso para botões em uma nova página, siga os passos abaixo:</p>
                    <ol>
                        <li><strong>Inclua os Arquivos Necessários:</strong> No início do seu arquivo PHP da nova página, inclua os arquivos de funções e conexão com o banco de dados:</li>
                        <pre><code>&lt;?php
include_once __DIR__ . '/../functions.php';
include_once __DIR__ . '/../db.php';

// Inicia a sessão se ainda não estiver iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verifica se o usuário está logado
if (!isset($_SESSION['matricula'])) {
    header('Location: https://pmrr.net/boletim/login/login_pm.php');
    exit();

}

// Recupera a matrícula do usuário da sessão
$matricula = $_SESSION['matricula'];
?&gt;</code></pre>
                        <li><strong>Verifique Permissões Antes de Exibir Botões:</strong> Utilize a função <code>usuario_tem_acesso()</code> para verificar se o usuário atual tem permissão para visualizar ou interagir com um botão específico. Envolva os botões dentro de condicionais PHP.</li>
                        <pre><code>&lt;?php if (usuario_tem_acesso('nome_da_pagina.php|identificador_do_botao')): ?&gt;
    &lt;button type="button" class="btn btn-primary"&gt;Meu Botão&lt;/button&gt;
&lt;?php endif; ?&gt;</code></pre>
                        <li><strong>Identifique os Botões Adequadamente:</strong> Cada botão deve ter um identificador único que corresponda ao que está registrado na tabela de acessos de botões. Por exemplo, para um botão de "Salvar", você pode usar "salvar".</li>
                        <li><strong>Adicione o Botão ao Controle de Acessos:</strong> Vá para a página <strong>Administração de Acessos - Botões</strong> e adicione um novo acesso especificando:
                            <ul>
                                <li><strong>Página:</strong> Nome do arquivo da nova página (e.g., <code>nova_pagina.php</code>).</li>
                                <li><strong>Identificador do Botão:</strong> Identificador único do botão (e.g., <code>salvar</code>).</li>
                                <li><strong>Matrícula do Usuário:</strong> Matrícula do usuário que terá acesso ao botão.</li>
                            </ul>
                        </li>
                        <li><strong>Teste o Controle de Acesso:</strong> Faça login com a matrícula especificada e verifique se o botão aparece ou não conforme o controle de acesso configurado.</li>
                    </ol>
                </div>
            </div>
        </div>

        <!-- Modal de Confirmação de Exclusão -->
        <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="confirmDeleteModalLabel">Confirmar Exclusão</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
              <div class="modal-body">
                Você tem certeza que deseja excluir este acesso de botão?
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger btn-sm" id="confirmDeleteBtn">Excluir</button>
              </div>
            </div>
          </div>
        </div>

        <!-- Font Awesome para ícones -->
        <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
        <!-- jQuery e Bootstrap JS -->
        <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
        <script>
            // Variável para armazenar o ID a ser excluído
            let deleteId = null;

            // Função para mostrar inputs de edição e remover 'readonly'
            function editRow(button) {
                // Fecha qualquer edição aberta atualmente
                $('.save-btn').hide();
                $('.cancel-btn').hide();
                $('.input-hidden').attr('readonly', true).hide();
                $('.text').show();

                // Seleciona a linha atual
                var row = $(button).closest('tr');

                // Esconde os textos e mostra os inputs
                row.find('span.text').hide();
                row.find('input.input-hidden').show().removeAttr('readonly'); // Remove 'readonly' para permitir edição

                // Mostra os botões Salvar e Cancelar
                row.find('.save-btn').show();
                row.find('.cancel-btn').show();
            }

            // Função para cancelar a edição
            function cancelEdit(button) {
                var row = $(button).closest('tr');

                // Esconde os inputs e mostra os textos
                row.find('input.input-hidden').hide().attr('readonly', true);
                row.find('span.text').show();

                // Esconde os botões Salvar e Cancelar
                row.find('.save-btn').hide();
                row.find('.cancel-btn').hide();
            }

            // Função para confirmar a exclusão
            function confirmDelete(id) {
                deleteId = id;
                $('#confirmDeleteModal').modal('show');
            }

            // Evento para confirmar a exclusão
            $('#confirmDeleteBtn').on('click', function() {
                if (deleteId) {
                    // Cria um formulário temporário para enviar a ação de exclusão
                    var form = $('<form>', {
                        'method': 'POST',
                        'action': ''
                    });

                    form.append($('<input>', {
                        'type': 'hidden',
                        'name': 'action',
                        'value': 'delete'
                    }));

                    form.append($('<input>', {
                        'type': 'hidden',
                        'name': 'id',
                        'value': deleteId
                    }));

                    $('body').append(form);
                    form.submit();
                }
            });

            // Função para alternar a exibição da FAQ
            function toggleFaq(element) {
                var faqItem = $(element).closest('.faq-item');
                faqItem.toggleClass('show collapsed');
            }

            // Inicializa os inputs como escondidos
            $(document).ready(function() {
                $('.input-hidden').hide();
                $('.save-btn').hide();
                $('.cancel-btn').hide();
            });
        </script>
    </body>
</html>
