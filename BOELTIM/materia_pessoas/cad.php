<?php 
session_start(); // Inicia a sessão

// Debugging settings (comentadas)
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

$mostrarCamposFerias = isset($_GET['ferias']) && $_GET['ferias'] === 'true';
error_log("Parâmetro ferias recebido: " . ($mostrarCamposFerias ? "true" : "false"));

include '../db.php'; // Inclua a conexão com o banco de dados

$mate_bole_cod = isset($_GET['mate_bole_cod']) ? (int)$_GET['mate_bole_cod'] : 0;
$show_iframe = true; // Flag do iframe sempre visível

// Busca os dados do policial pelo campo matrícula e retorna a unidade
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'buscar_dados_policial') {
    $matricula = $_POST['matricula'] ?? null;

    if ($matricula) {
        try {
            // Consulta para buscar os dados do policial, incluindo a unidade
            $stmt = $pdo->prepare("
                SELECT matricula, nome, pg_descricao, unidade
                FROM vw_policiais_militares
                WHERE matricula = :matricula
                LIMIT 1
            ");
            $stmt->execute(['matricula' => $matricula]);
            $dados = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($dados) {
                echo json_encode(['success' => true, 'dados' => $dados]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Policial não encontrado.']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Matrícula não informada.']);
    }
    exit;
}



if ($mate_bole_cod > 0) {
    $sql = "SELECT * FROM bg.materia_boletim WHERE mate_bole_cod = :mate_bole_cod";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':mate_bole_cod', $mate_bole_cod, PDO::PARAM_INT);
    $stmt->execute();
    if ($stmt->rowCount() > 0) {
        $materia = $stmt->fetch(PDO::FETCH_ASSOC);

        // Recuperar Assunto Geral com base no Assunto Específico
        if (!empty($materia['fk_assu_espe_cod'])) {
            $stmt_geral = $pdo->prepare("
                SELECT assu_gera_cod, assu_gera_descricao 
                FROM bg.vw_assunto_concatenado 
                WHERE assu_espe_cod = :assu_espe_cod
                LIMIT 1
            ");
            $stmt_geral->execute(['assu_espe_cod' => $materia['fk_assu_espe_cod']]);
            $assunto_geral = $stmt_geral->fetch(PDO::FETCH_ASSOC);
            $materia['fk_assu_gera_cod'] = $assunto_geral['assu_gera_cod'] ?? '';
            $materia['fk_assu_gera_descricao'] = $assunto_geral['assu_gera_descricao'] ?? '';
        }
    } else {
        echo '<div class="alert alert-danger">Matéria não encontrada.</div>';
    }
}

// Função auxiliar para buscar descrições
function buscarDescricao($pdo, $campo_codigo, $valor_codigo, $campo_descricao, $tabela) {
    $stmt = $pdo->prepare("SELECT $campo_descricao FROM $tabela WHERE $campo_codigo = :valor LIMIT 1");
    $stmt->execute(['valor' => $valor_codigo]);
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    return $resultado ? $resultado[$campo_descricao] : 'N/A';
}

if (isAjaxRequest() && isset($_GET['action']) && $_GET['action'] === 'fetch_assunto_texto') {
    $assu_espe_cod = isset($_GET['assu_espe_cod']) ? (int)$_GET['assu_espe_cod'] : 0;

    try {
        $stmt = $pdo->prepare("
            SELECT assu_espe_texto, assu_gera_cod, assu_gera_descricao
            FROM bg.vw_assunto_concatenado
            WHERE assu_espe_cod = :assu_espe_cod
            LIMIT 1
        ");
        $stmt->execute(['assu_espe_cod' => $assu_espe_cod]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'assu_espe_texto' => $result['assu_espe_texto'] ?? '',
            'assu_gera_cod' => $result['assu_gera_cod'] ?? '',
            'assu_gera_descricao' => $result['assu_gera_descricao'] ?? '',
        ]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

function isAjaxRequest() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

// Busca as unidades para o datalist
try {
    $stmt_unidades = $pdo->query("SELECT DISTINCT unidade FROM bg.vw_policiais_militares ORDER BY unidade");
    $unidades = $stmt_unidades->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erro ao buscar unidades: " . $e->getMessage());
    $unidades = [];
}

try {
    $stmt_pg = $pdo->query("SELECT DISTINCT pg_descricao FROM bg.vw_policiais_militares ORDER BY pg_descricao");
    $postosGraduacoes = $stmt_pg->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erro ao buscar postos/graduações: " . $e->getMessage());
    $postosGraduacoes = [];
}

// **Busca os Assuntos Específicos com o campo assu_espe_texto**
try {
    $stmt = $pdo->query("
        SELECT assu_espe_cod, assu_espe_descricao, assu_gera_cod, assu_gera_descricao, assu_espe_texto 
        FROM bg.vw_assunto_concatenado 
        ORDER BY assu_espe_descricao
    ");
    $assuntosEspecificos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erro ao buscar Assunto Específico: " . $e->getMessage());
    $assuntosEspecificos = [];
}

$mate_bole_cod = isset($_GET['mate_bole_cod']) ? (int)$_GET['mate_bole_cod'] : 0;
$materia = [];
$mensagem_sucesso = "";
$dados_cadastrados = []; // Array para armazenar os dados cadastrados
$show_iframe = true; // Flag para exibir o iframe

// Defina o login do usuário manualmente para fins de teste
$user_login = '452912';

// Recupera as opções para o campo Subunidade
$stmt_subunidade = $pdo->prepare("
    SELECT subu_cod, concat(subu_descricao, ' - ', unid_descricao, ' - ', coma_descricao) as descricao
    FROM public.vw_comando_unidade_subunidade 
    WHERE subu_cod IN (
        SELECT fk_subunidade
        FROM bg.vw_permissao 
        WHERE fk_login = :login AND perm_ativo = 1
    )
    ORDER BY subu_descricao, unid_descricao, coma_descricao
");
$stmt_subunidade->execute(['login' => $user_login]);
$subunidades = $stmt_subunidade->fetchAll(PDO::FETCH_ASSOC);

// Recupera as opções para o campo Tipo de Documento
$stmt_tipo_docu = $pdo->prepare("
    SELECT tipo_docu_cod, tipo_docu_descricao 
    FROM bg.tipo_documento 
    ORDER BY tipo_docu_descricao
");
$stmt_tipo_docu->execute();
$tipos_documento = $stmt_tipo_docu->fetchAll(PDO::FETCH_ASSOC);

// Verifique se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'add_materia') {
        try {
            $stmt = $pdo->prepare('
                INSERT INTO bg.materia_boletim (
                    mate_bole_texto, 
                    mate_bole_data, 
                    fk_tipo_docu_cod, 
                    fk_assu_espe_cod, 
                    mate_bole_nr_doc, 
                    mate_bole_data_doc, 
                    fk_subu_cod
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
                RETURNING mate_bole_cod
            ');
            $stmt->execute([
                $_POST['mate_bole_texto'], 
                $_POST['mate_bole_data'], 
                $_POST['fk_tipo_docu_cod'],
                $_POST['fk_assu_espe_cod'], 
                $_POST['mate_bole_nr_doc'], 
                $_POST['mate_bole_data_doc'], 
                $_POST['fk_subu_cod']
            ]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $mate_bole_cod = $result['mate_bole_cod'];

            if (!$mate_bole_cod) {
                throw new Exception("Falha ao recuperar o código da Matéria.");
            }

            $mensagem_sucesso = 'Matéria cadastrada com sucesso!';
        } catch (PDOException $e) {
            $mensagem_sucesso = ''; // Certifique-se de que não haja mensagem de sucesso em caso de erro
            echo '<div class="alert alert-danger">Erro ao cadastrar a Matéria: ' . htmlspecialchars($e->getMessage()) . '</div>';
        } catch (Exception $e) {
            $mensagem_sucesso = ''; // Certifique-se de que não haja mensagem de sucesso em caso de erro
            echo '<div class="alert alert-danger">Erro: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
}

// Se estiver editando, carregar os dados para preencher o formulário
if ($mate_bole_cod > 0) {
    $sql = "SELECT * FROM bg.materia_boletim WHERE mate_bole_cod = :mate_bole_cod";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':mate_bole_cod', $mate_bole_cod, PDO::PARAM_INT);
    $stmt->execute();
    if ($stmt->rowCount() > 0) {
        $materia = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        echo '<div class="alert alert-danger">Matéria não encontrada.</div>';
    }
}

// Ação para buscar policiais para o autocomplete com base no CPF, nome ou matrícula
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'buscar_policiais_autocomplete') {
    $term = $_POST['term'];

    // Ajuste da SQL para garantir que unidade e posto/graduação sejam retornados
    $stmt = $pdo->prepare("SELECT matricula, nome, cpf, unidade, pg_descricao 
                           FROM bg.vw_policiais_militares 
                           WHERE nome ILIKE :term OR cpf ILIKE :term OR matricula::TEXT ILIKE :term 
                           LIMIT 10");
    $stmt->execute(['term' => '%' . $term . '%']);
    $policiais = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $results = [];
    foreach ($policiais as $policial) {
        $results[] = [
            'label' => "{$policial['nome']} - {$policial['cpf']} - {$policial['matricula']}",
            'matricula' => $policial['matricula'],
            'nome' => $policial['nome'],
            'cpf' => $policial['cpf'],
            'unidade' => $policial['unidade'], // Garante que unidade está presente
            'pg_descricao' => $policial['pg_descricao'] // Garante que posto/graduação está presente
        ];
    }

    echo json_encode($results);
    exit;
}

// Ação para salvar a edição no banco de dados
if (isset($_POST['action']) && $_POST['action'] === 'salvar_edicao') {
    $matricula = $_POST['matricula'];
    $novaUnidade = $_POST['unidade'];
    $novoIdPg = $_POST['id_pg']; // Recebemos o ID do posto/graduação, não o texto

    // Atualizar a unidade e o posto/graduação na tabela pessoa_materia
    $stmt = $pdo->prepare("UPDATE bg.pessoa_materia SET fk_poli_lota_cod = :unidade, fk_index_post_grad_cod = :id_pg WHERE fk_poli_mili_matricula = :matricula");
    $stmt->execute([
        'unidade' => $novaUnidade,
        'id_pg' => $novoIdPg,
        'matricula' => $matricula
    ]);

    echo json_encode(['success' => true]);
    exit;
}
     
// Ação para buscar as opções de postos/graduações com id_pg e pg_descricao
if (isset($_POST['action']) && $_POST['action'] === 'buscar_postos_graduacoes') {
    $stmt_pg = $pdo->query("SELECT DISTINCT id_pg, pg_descricao FROM bg.vw_policiais_militares ORDER BY pg_descricao");
    $postosGraduacoes = $stmt_pg->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($postosGraduacoes);
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $mate_bole_cod > 0 ? 'Editar Matéria' : 'Cadastrar Matéria'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
     <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <style>
        /* Seu CSS aqui */
        
/*        #campoFerias {*/
/*    border: 1px solid #ccc;*/
/*    padding: 10px;*/
/*    border-radius: 5px;*/
/*    background-color: #f9f9f9;*/
/*}*/

        
        body {
            background-color: #f4f4f9;
            color: #343a40;
        }
        .container {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            margin-top: 30px;
        }
        h2 {
            color: #007bff;
            margin-bottom: 30px;
        }
        .form-label {
            font-weight: bold;
        }
        .form-control, .form-select {
            border-radius: 5px;
        }
        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
        }
        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #0056b3;
        }
        .btn-secondary {
            background-color: #6c757d;
            border-color: #6c757d;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
            border-color: #545b62;
        }
        .btn-warning {
            background-color: #ffc107;
            border-color: #ffc107;
            color: #212529;
        }
        .btn-warning:hover {
            background-color: #e0a800;
            border-color: #d39e00;
        }
        .btn-success {
            background-color: #28a745;
            border-color: #28a745;
            color: white;
        }

        .btn-success:hover {
            background-color: #218838;
            border-color: #1e7e34;
        }
        .row {
            margin-bottom: 15px;
        }
        .col-md-6, .col-md-4 {
            padding-right: 10px;
            padding-left: 10px;
        }
        .button-group {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        .alert {
            margin-top: 20px;
        }
        /* Estilos para a tabela de dados cadastrados */
        .dados-cadastrados {
            margin-top: 30px;
        }
        .dados-cadastrados table {
            width: 100%;
        }
        .dados-cadastrados th, .dados_cadastrados td {
            padding: 10px;
            text-align: left;
        }
        
        .associar-pessoa-container {
            border: 2px solid rgba(0, 0, 0, 0.2); /* Borda preta suave com opacidade */
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }

        .associar-pessoa-container h3 {
            margin-bottom: 15px;
            color: #000000;
        }

        .associar-pessoa-fields {
            display: flex; /* Faz os campos aparecerem na mesma linha */
            gap: 15px; /* Espaçamento entre os campos */
            border: 1px solid rgba(0, 0, 0, 0.2); /* Borda preta suave com opacidade */
            padding: 10px;
            border-radius: 8px;
        }

        .associar-pessoa-fields .form-group {
            flex: 1; /* Faz os campos ocuparem o mesmo espaço */
        }

        .associar-pessoa-fields .form-group select,
        .associar-pessoa-fields .form-group input {
            width: 100%; /* Garantir que os campos preencham a largura disponível */
        }
        fieldset {
            border: 2px solid black;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 10px;
        }

        legend {
            font-size: 18px;
            font-weight: bold;
            color: #000;
        }

        .form-label {
            font-weight: bold;
        }

        .btn-success {
            background-color: #28a745;
            border-color: #28a745;
            color: white;
        }

        .btn-success:hover {
            background-color: #218838;
            border-color: #1e7e34;
        }
    </style>
    <script>
    // Passar as opções de Posto/Graduação e Unidade para o JavaScript
    var postosGraduacoes = <?php echo json_encode($postosGraduacoes); ?>;
    var subunidades = <?php echo json_encode($subunidades); ?>;

    // Script para atualizar o Assunto Geral com base no Assunto Específico
    document.addEventListener('DOMContentLoaded', function () {
        const assuntoEspecificoSelect = document.getElementById('fk_assu_espe_cod');
        const assuntoGeralSelect = document.getElementById('fk_assu_gera_cod');
        const textoMateriaTextarea = document.getElementById('mate_bole_texto');
        const campoFerias = document.getElementById('campoFerias');

        // Função para verificar o valor do Assunto Geral
        function verificarAssuntoGeral() {
            const assuntoGeralCod = assuntoGeralSelect.value;
            console.log("Assunto Geral Selecionado:", assuntoGeralCod); // Verificar o valor do Assunto Geral

            // Se o Assunto Geral for 12 (Férias), mostrar os campos de férias
            if (assuntoGeralCod == '12') {
                campoFerias.style.display = 'block'; // Exibe os campos de férias
                console.log("Campos de Férias visíveis.");
            } else {
                campoFerias.style.display = 'none'; // Esconde os campos de férias
                console.log("Campos de Férias ocultos.");
            }
        }

        // Adicionando um evento de mudança no Assunto Específico
        assuntoEspecificoSelect.addEventListener('change', function () {
            const assuEspeCod = this.value;

            if (assuEspeCod) {
                fetch(`?action=fetch_assunto_texto&assu_espe_cod=${assuEspeCod}`, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        textoMateriaTextarea.value = data.assu_espe_texto || ''; // Atualiza o campo de texto

                        // Atualiza o campo Assunto Geral
                        assuntoGeralSelect.innerHTML = ''; // Limpa as opções existentes
                        if (data.assu_gera_cod && data.assu_gera_descricao) {
                            const option = document.createElement('option');
                            option.value = data.assu_gera_cod;
                            option.textContent = data.assu_gera_descricao;
                            option.selected = true;
                            assuntoGeralSelect.appendChild(option);

                            // Após a mudança, verifica o Assunto Geral
                            verificarAssuntoGeral();
                        } else {
                            const defaultOption = document.createElement('option');
                            defaultOption.value = '';
                            defaultOption.textContent = 'Selecione o Assunto Geral';
                            assuntoGeralSelect.appendChild(defaultOption);
                        }
                    } else {
                        alert('Erro ao buscar os dados do Assunto Específico.');
                    }
                })
                .catch(error => console.error('Erro na requisição:', error));
            }
        });

        // Inicializa a visibilidade dos campos de férias com base no valor atual do Assunto Geral
        verificarAssuntoGeral(); // Chama a função para verificar se o Assunto Geral é Férias ao carregar a página
    });
    </script>
</head>
<body>
    <div class="container">
        <!-- Mensagem de Sucesso -->
        <?php if (!empty($mensagem_sucesso)): ?>
            <div class="alert alert-success">
                <?php echo $mensagem_sucesso; ?>
            </div>
        <?php endif; ?>

        <h2><?php echo $mate_bole_cod > 0 ? 'Editar Matéria' : 'Cadastrar Matéria'; ?></h2>
       <form method="POST" action="" enctype="multipart/form-data">
    <?php if ($mate_bole_cod > 0): ?>
        <input type="hidden" name="mate_bole_cod" value="<?php echo htmlspecialchars($mate_bole_cod); ?>">
    <?php endif; ?>

    <div class="row">
        <div class="col-md-6">
            <label for="fk_assu_espe_cod" class="form-label">Assunto Específico</label>
            <select class="form-select" id="fk_assu_espe_cod" name="fk_assu_espe_cod" required>
    <option value="">Selecione</option>
    <?php foreach ($assuntosEspecificos as $assunto): ?>
        <option value="<?= htmlspecialchars($assunto['assu_espe_cod']) ?>"
            <?= ($materia['fk_assu_espe_cod'] == $assunto['assu_espe_cod']) ? 'selected' : ''; ?>>
            <?= htmlspecialchars($assunto['assu_espe_descricao']) ?>
        </option>
    <?php endforeach; ?>
</select>
        </div>

        <div class="col-md-6">
    <label for="fk_assu_gera_cod" class="form-label">Assunto Geral</label>
    <select class="form-select" id="fk_assu_gera_cod" name="fk_assu_gera_cod" required>
        <?php if (!empty($materia['fk_assu_gera_cod'])): ?>
            <option value="<?= htmlspecialchars($materia['fk_assu_gera_cod']) ?>" selected>
                <?= htmlspecialchars($materia['fk_assu_gera_descricao']) ?>
            </option>
        <?php else: ?>
            <option value="">Selecione o Assunto Geral</option>
        <?php endif; ?>
    </select>
</div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <label for="fk_subu_cod" class="form-label">Nome da Unidade</label>
            <select class="form-select" id="fk_subu_cod" name="fk_subu_cod" required>
                <option value="">Selecione</option>
                <?php foreach ($subunidades as $subunidade): ?>
                    <option value="<?= htmlspecialchars($subunidade['subu_cod']) ?>" 
                        <?php 
                            if ($mate_bole_cod > 0 && isset($materia['fk_subu_cod']) && $materia['fk_subu_cod'] == $subunidade['subu_cod']) {
                                echo 'selected';
                            }
                        ?>>
                        <?= htmlspecialchars($subunidade['descricao']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-6">
            <label for="mate_bole_data" class="form-label">Data da Matéria</label>
            <input type="date" class="form-control" id="mate_bole_data" name="mate_bole_data" 
                value="<?php 
                    echo $mate_bole_cod > 0 ? htmlspecialchars($materia['mate_bole_data']) : (isset($_POST['mate_bole_data']) ? htmlspecialchars($_POST['mate_bole_data']) : '');
                ?>" required>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <label for="fk_tipo_docu_cod" class="form-label">Tipo de Documento</label>
            <select class="form-select" id="fk_tipo_docu_cod" name="fk_tipo_docu_cod" required>
                <option value="">Selecione</option>
                <?php foreach ($tipos_documento as $tipo): ?>
                    <option value="<?= htmlspecialchars($tipo['tipo_docu_cod']) ?>" 
                        <?php 
                            if ($mate_bole_cod > 0 && isset($materia['fk_tipo_docu_cod']) && $materia['fk_tipo_docu_cod'] == $tipo['tipo_docu_cod']) {
                                echo 'selected';
                            }
                        ?>>
                        <?= htmlspecialchars($tipo['tipo_docu_descricao']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-6">
            <label for="mate_bole_nr_doc" class="form-label">Número do Documento</label>
            <input type="text" class="form-control" id="mate_bole_nr_doc" name="mate_bole_nr_doc" 
                value="<?php 
                    echo $mate_bole_cod > 0 ? htmlspecialchars($materia['mate_bole_nr_doc']) : (isset($_POST['mate_bole_nr_doc']) ? htmlspecialchars($_POST['mate_bole_nr_doc']) : '');
                ?>">
        </div>
    </div>

        <div class="row">
            <div class="col-md-6">
                <label for="mate_bole_data_doc" class="form-label">Data do Documento</label>
                <input type="date" class="form-control" id="mate_bole_data_doc" name="mate_bole_data_doc" 
                    value="<?php 
                        echo $mate_bole_cod > 0 ? htmlspecialchars($materia['mate_bole_data_doc']) : (isset($_POST['mate_bole_data_doc']) ? htmlspecialchars($_POST['mate_bole_data_doc']) : '');
                    ?>">
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-12">
                <label for="mate_bole_texto" class="form-label">Texto da Matéria</label>
                <textarea class="form-control" id="mate_bole_texto" name="mate_bole_texto" rows="5" required><?= htmlspecialchars($materia['mate_bole_texto']) ?></textarea>
            </div>
        </div>
        
        <!-- Botões Salvar e Cancelar -->
<div class="button-group mt-4 mb-4"> <!-- Adicionei a classe mb-4 para adicionar o espaçamento entre os botões e a próxima seção -->
    <?php if ($mate_bole_cod > 0): ?>
        <button type="submit" class="btn btn-primary" id="btnSalvar">Atualizar</button>
    <?php else: ?>
        <!-- Botão Salvar -->
<div class="button-group mt-4">
    <button type="submit" name="action" value="add_materia" class="btn btn-primary">Salvar</button>
    <a href="consulta_materia1.php" class="btn btn-secondary">Cancelar</a>
</div>
    <?php endif; ?>
    
</div>

    <!-- Seção para Associar Pessoas à Matéria -->
    <div class="associar-pessoa-container">
        <h3>Associar Pessoas à Matéria</h3>
        <fieldset>
            <legend>Cadastro de Matéria de Pessoas</legend>
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="buscaPolicial" class="form-label">Policial Militar</label>
                    <input type="text" class="form-control" id="buscaPolicial" name="buscaPolicial">
                </div>
                <div class="col-md-4">
    <label for="postoGraduacao" class="form-label">Posto/Graduação</label>
    <select class="form-select" id="postoGraduacao" name="postoGraduacao" disabled>
        <option value="">Selecione</option>
    </select>
</div>
<div class="col-md-4">
    <label for="unidade" class="form-label">Unidade</label>
    <select class="form-select" id="unidade" name="unidade" disabled>
        <option value="">Selecione</option>
    </select>
</div>


<!-- férias --> 
<div id="campoFerias" style="display: none; border: 1px solid #ccc; padding: 10px; margin-top: 20px;">
    <div class="row mb-3">
        <div class="col-md-4">
            <label for="dataInicial" class="form-label">Data Inicial</label>
            <input type="date" class="form-control" id="dataInicial" name="dataInicial">
        </div>
        <div class="col-md-4">
            <label for="dataFinal" class="form-label">Data Final</label>
            <input type="date" class="form-control" id="dataFinal" name="dataFinal">
        </div>
        <div class="col-md-4">
            <label for="anoBase" class="form-label">Ano Base</label>
            <input type="number" class="form-control" id="anoBase" name="anoBase" min="2000" max="2100">
        </div>
    </div>
</div>




            </div>
            <div class="row mb-3">
                <div class="col-md-12 d-flex justify-content-end">
                    <button type="button" class="btn btn-primary" id="btnAdicionarPM" style="margin-right: 15px;">Adicionar PM</button>
                    <button type="reset" class="btn btn-secondary">Cancelar</button>
                </div>
            </div>
        </fieldset>

        <fieldset>
            <legend>Lista de Matérias de Pessoas</legend>
            <table class="table table-bordered" id="tabelaPessoas">
                <thead>
                    <tr>
                        <th>Ações</th>
                        <th>Nome</th>
                        <th>Posto/Graduação Atual</th>
                        <th>Unidade</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="4" class="text-center">Nenhum registro encontrado.</td>
                    </tr>
                </tbody>
            </table>
        </fieldset>
    </div>


<script>
        $(document).ready(function() {
            // Configuração do autocomplete para buscar policiais
            $('#buscaPolicial').autocomplete({
    source: function (request, response) {
        $.ajax({
            url: window.location.href,
            type: 'POST',
            data: {
                action: 'buscar_policiais_autocomplete',
                term: request.term
            },
            success: function (data) {
                response(JSON.parse(data));
            },
            error: function () {
                alert('Erro na busca de policiais.');
            }
        });
    },
    minLength: 2,
    select: function (event, ui) {
        if (ui.item) {
            // Preencha os campos com os dados retornados
            $('#buscaPolicial').data('selected-policial', ui.item);
            $('#buscaPolicial').val(ui.item.label); // Exibe o nome completo no campo

            // Preenche o campo Posto/Graduação
            $('#postoGraduacao').html('<option value="' + ui.item.pg_descricao + '">' + ui.item.pg_descricao + '</option>');

            // Preenche o campo Unidade
            $('#unidade').html('<option value="' + ui.item.unidade + '">' + ui.item.unidade + '</option>');

            // Habilita os campos (caso estejam desabilitados)
            $('#postoGraduacao').prop('disabled', false);
            $('#unidade').prop('disabled', false);
        } else {
            alert("Erro ao carregar os dados do policial. Verifique as informações na base de dados.");
        }
        return false; // Impede o autocomplete de substituir o campo com apenas o valor do 'label'
    }
});

//CAMPO DE FÉRIAS
document.addEventListener('DOMContentLoaded', function () {
    const assuntoGeralSelect = document.getElementById('fk_assu_gera_cod');
    const campoFerias = document.getElementById('campoFerias');

    // Função para verificar e exibir/esconder os campos de férias
    function verificarAssuntoGeral() {
        const assuntoGeralCod = assuntoGeralSelect.value;
        if (assuntoGeralCod == '12') {
            campoFerias.style.display = 'block'; // Exibe os campos de férias
        } else {
            campoFerias.style.display = 'none'; // Esconde os campos de férias
        }
    }

    // Chamada inicial ao carregar a página
    verificarAssuntoGeral();

    // Adiciona o evento de mudança ao campo Assunto Geral
    assuntoGeralSelect.addEventListener('change', verificarAssuntoGeral);
});



            // Adicionar pessoa à tabela de pessoas associadas
            $('#btnAdicionarPM').click(function() {
                var policial = $('#buscaPolicial').data('selected-policial');
                var unidade = $('#unidade').val();
                var postoGraduacao = $('#postoGraduacao').val();

                if (!policial || !unidade || !postoGraduacao) {
                    alert('Preencha todos os campos antes de adicionar.');
                    return;
                }

                // Verifica se a tabela está vazia
                var tabela = $('#tabelaPessoas tbody');
                var linhas = tabela.find('tr');
                if (linhas.length === 1 && linhas.find('td').length === 1) {
                    linhas.remove(); // Remove a linha "Nenhum registro encontrado."
                }

                // Verifica se o policial já foi adicionado
                if ($('tr[data-matricula="' + policial.matricula + '"]').length > 0) {
                    alert('Este policial já foi adicionado.');
                    return;
                }

                // Adiciona a nova linha na tabela
                var novaLinha = `
                    <tr data-matricula="${policial.matricula}">
                        <td>
                            <button class="btn btn-danger btn-sm" onclick="excluirRegistro('${policial.matricula}')">Excluir</button>
                            <button class="btn btn-warning btn-sm btnEditar" style="margin-left: 5px;">Editar</button>
                        </td>
                        <td>${policial.nome}</td>
                        <td>${postoGraduacao}</td>
                        <td>${unidade}</td>
                    </tr>
                `;
                tabela.append(novaLinha);

                // Limpa os campos de entrada
                $('#buscaPolicial').val('');
                $('#buscaPolicial').removeData('selected-policial');
                $('#unidade').html('<option value="">Selecione a Unidade</option>');
                $('#postoGraduacao').html('<option value="">Selecione o Posto/Graduação</option>');
            });

            // Listener para os botões de edição
            $('#tabelaPessoas').on('click', '.btnEditar', function() {
                var linha = $(this).closest('tr');
                var matricula = linha.data('matricula');

                // Evita múltiplas edições simultâneas
                if (linha.hasClass('editando')) {
                    return;
                }
                linha.addClass('editando');

                // Obter os valores atuais
                var postoAtual = linha.find('td:nth-child(3)').text();
                var unidadeAtual = linha.find('td:nth-child(4)').text();

                // Criar os elementos de seleção para Posto/Graduação
                var selectPosto = $('<select class="form-select form-select-sm posto-select"></select>');
                postosGraduacoes.forEach(function(posto) {
                    var option = $('<option></option>')
                        .attr('value', posto.pg_descricao)
                        .text(posto.pg_descricao);
                    if (posto.pg_descricao === postoAtual) {
                        option.attr('selected', 'selected');
                    }
                    selectPosto.append(option);
                });

                // Criar os elementos de seleção para Unidade
                var selectUnidade = $('<select class="form-select form-select-sm unidade-select"></select>');
                subunidades.forEach(function(subunidade) {
                    var option = $('<option></option>')
                        .attr('value', subunidade.descricao)
                        .text(subunidade.descricao);
                    if (subunidade.descricao === unidadeAtual) {
                        option.attr('selected', 'selected');
                    }
                    selectUnidade.append(option);
                });

                // Substituir os textos por selects
                linha.find('td:nth-child(3)').html(selectPosto);
                linha.find('td:nth-child(4)').html(selectUnidade);

                // Alterar os botões de ação
                var btnSalvar = $('<button class="btn btn-success btn-sm btnSalvar" style="margin-left: 5px;">Salvar</button>');
                var btnCancelar = $('<button class="btn btn-secondary btn-sm btnCancelar" style="margin-left: 5px;">Cancelar</button>');
                $(this).replaceWith(btnSalvar);
                $(this).siblings('.btnExcluir').replaceWith(btnCancelar);
            });

            // Listener para os botões de salvar
            $('#tabelaPessoas').on('click', '.btnSalvar', function() {
                var linha = $(this).closest('tr');
                var matricula = linha.data('matricula');

                // Obter os valores selecionados
                var novoPosto = linha.find('.posto-select').val();
                var novaUnidade = linha.find('.unidade-select').val();

                // Atualizar os campos com os novos valores
                linha.find('td:nth-child(3)').text(novoPosto);
                linha.find('td:nth-child(4)').text(novaUnidade);

                // Restaurar os botões de ação
                var btnEditar = $('<button class="btn btn-warning btn-sm btnEditar" style="margin-left: 5px;">Editar</button>');
                var btnExcluir = $('<button class="btn btn-danger btn-sm btnExcluir" onclick="excluirRegistro(\'' + matricula + '\')">Excluir</button>');
                $(this).replaceWith(btnEditar);
                $(this).siblings('.btnCancelar').replaceWith(btnExcluir);

                // Remover a classe 'editando'
                linha.removeClass('editando');
            });

            // Listener para os botões de cancelar
            $('#tabelaPessoas').on('click', '.btnCancelar', function() {
                var linha = $(this).closest('tr');
                var matricula = linha.data('matricula');

                // Obter os valores atuais do select para restaurar
                var selectPosto = linha.find('.posto-select');
                var selectUnidade = linha.find('.unidade-select');
                var postoOriginal = selectPosto.find('option:selected').text();
                var unidadeOriginal = selectUnidade.find('option:selected').text();

                // Restaurar os textos originais
                linha.find('td:nth-child(3)').text(postoOriginal);
                linha.find('td:nth-child(4)').text(unidadeOriginal);

                // Restaurar os botões de ação
                var btnEditar = $('<button class="btn btn-warning btn-sm btnEditar" style="margin-left: 5px;">Editar</button>');
                var btnExcluir = $('<button class="btn btn-danger btn-sm btnExcluir" onclick="excluirRegistro(\'' + matricula + '\')">Excluir</button>');
                $(this).replaceWith(btnEditar);
                $(this).siblings('.btnSalvar').replaceWith(btnExcluir);

                // Remover a classe 'editando'
                linha.removeClass('editando');
            });
        });

        // Função para excluir o registro
        function excluirRegistro(matricula) {
            $('tr[data-matricula="' + matricula + '"]').remove();

            // Se a tabela estiver vazia, adiciona a linha "Nenhum registro encontrado."
            var tabela = $('#tabelaPessoas tbody');
            if (tabela.find('tr').length === 0) {
                tabela.append('<tr><td colspan="4" class="text-center">Nenhum registro encontrado.</td></tr>');
            }
        }
    </script>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const assuntoEspecificoSelect = document.getElementById('fk_assu_espe_cod');
        const assuntoGeralSelect = document.getElementById('fk_assu_gera_cod');
        const textoMateriaTextarea = document.getElementById('mate_bole_texto');

        // Atualiza o Assunto Geral com base no Assunto Específico ao carregar a página
        if (assuntoEspecificoSelect.value) {
            fetch(`?action=fetch_assunto_texto&assu_espe_cod=${assuntoEspecificoSelect.value}`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    textoMateriaTextarea.value = data.assu_espe_texto || ''; // Atualiza o texto da matéria
                    assuntoGeralSelect.innerHTML = ''; // Limpa as opções
                    if (data.assu_gera_cod && data.assu_gera_descricao) {
                        const option = document.createElement('option');
                        option.value = data.assu_gera_cod;
                        option.textContent = data.assu_gera_descricao;
                        option.selected = true;
                        assuntoGeralSelect.appendChild(option);
                    }
                }
            })
            .catch(error => console.error('Erro ao carregar Assunto Geral:', error));
        }
    });
    </script>

</body>
</html>
