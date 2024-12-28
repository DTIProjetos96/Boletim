<?php 
session_start(); // Inicia a sessão

include '../db.php'; // Inclua a conexão com o banco de dados

// Ativa a exibição de erros para depuração (remova em produção)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Função auxiliar para buscar descrições
function buscarDescricao($pdo, $campo_codigo, $valor_codigo, $campo_descricao, $tabela) {
    $stmt = $pdo->prepare("SELECT $campo_descricao FROM $tabela WHERE $campo_codigo = :valor LIMIT 1");
    $stmt->execute(['valor' => $valor_codigo]);
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    return $resultado ? $resultado[$campo_descricao] : 'N/A';
}

// Detecta se a requisição é AJAX
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

// Busca todos os assuntos específicos e gerais do banco de dados
try {
    $stmt = $pdo->query("SELECT assu_espe_cod, assu_espe_descricao, assu_gera_cod, assu_gera_descricao FROM bg.vw_assunto_concatenado ORDER BY assu_espe_descricao");
    $assuntosEspecificos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erro ao buscar Assunto Específico: " . $e->getMessage());
    $assuntosEspecificos = [];
}

$mate_bole_cod = isset($_GET['mate_bole_cod']) ? (int)$_GET['mate_bole_cod'] : 0;
$materia = [];
$mensagem_sucesso = "";
$dados_cadastrados = []; // Array para armazenar os dados cadastrados

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
    // Verifica se a ação é para adicionar ou atualizar a matéria
    if (isset($_POST['action']) && $_POST['action'] === 'add_pessoa_materia') {
        // Adicionar uma nova pessoa_materia
        try {
            $stmt_add_pessoa = $pdo->prepare('
                INSERT INTO bg.pessoa_materia (
                    fk_mate_bole_cod,
                    fk_poli_mili_matricula
                ) VALUES (?, ?)
            ');
            $stmt_add_pessoa->execute([
                $mate_bole_cod,
                $_POST['fk_poli_mili_matricula']
            ]);
            $mensagem_sucesso = 'Pessoa associada à Matéria com sucesso!';

            // Recuperar os dados do PM associado
            $stmt_policia = $pdo->prepare('SELECT matricula, nome, pg_descricao, cpf, unidade FROM bg.vw_policiais_militares WHERE matricula = :matricula');
            $stmt_policia->execute(['matricula' => $_POST['fk_poli_mili_matricula']]);
            $policial = $stmt_policia->fetch(PDO::FETCH_ASSOC);

            if ($policial) {
                // Formatar os dados do PM para adicionar ao Texto da Matéria
                $texto_adicional = "\n\n-- PM Associado --\n";
                $texto_adicional .= "Matrícula: " . $policial['matricula'] . "\n";
                $texto_adicional .= "Nome: " . $policial['nome'] . "\n";
                $texto_adicional .= "Posto/Graduação: " . $policial['pg_descricao'] . "\n";
                $texto_adicional .= "CPF: " . $policial['cpf'] . "\n";
                $texto_adicional .= "Unidade: " . $policial['unidade'] . "\n";

                // Buscar o texto atual da matéria
                $stmt_texto = $pdo->prepare('SELECT mate_bole_texto FROM bg.materia_boletim WHERE mate_bole_cod = :cod');
                $stmt_texto->execute(['cod' => $mate_bole_cod]);
                $resultado_texto = $stmt_texto->fetch(PDO::FETCH_ASSOC);
                $texto_atual = $resultado_texto ? $resultado_texto['mate_bole_texto'] : '';

                // Adicionar o texto adicional sem substituir o existente
                $novo_texto = $texto_atual . $texto_adicional;

                // Atualizar o campo mate_bole_texto no banco de dados
                $stmt_update_texto = $pdo->prepare('UPDATE bg.materia_boletim SET mate_bole_texto = :novo_texto WHERE mate_bole_cod = :cod');
                $stmt_update_texto->execute([
                    'novo_texto' => $novo_texto,
                    'cod' => $mate_bole_cod
                ]);

                // Atualizar o array de dados cadastrados para exibição
                $dados_cadastrados['Texto da Matéria'] = nl2br(htmlspecialchars($novo_texto));
            } else {
                // Caso o PM não seja encontrado, exibir uma mensagem de erro
                $mensagem_sucesso .= " Porém, não foi possível recuperar os dados do PM associado.";
            }

            // Verificar se a requisição é AJAX
            if (isAjaxRequest()) {
                echo json_encode(['success' => $mensagem_sucesso, 'dados_cadastrados' => $dados_cadastrados]);
                exit;
            }

        } catch (PDOException $e) {
            if (isAjaxRequest()) {
                echo json_encode(['error' => 'Erro ao associar Pessoa: ' . $e->getMessage()]);
                exit;
            } else {
                echo '<div class="alert alert-danger">Erro ao associar Pessoa: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
        }
    } else {
        // Atualiza ou cadastra a matéria
        try {
            if ($mate_bole_cod > 0) {
                // Atualiza a matéria existente
                $stmt = $pdo->prepare('
                    UPDATE bg.materia_boletim 
                    SET mate_bole_texto = :mate_bole_texto, 
                        mate_bole_data = :mate_bole_data, 
                        fk_tipo_docu_cod = :fk_tipo_docu_cod, 
                        fk_assu_espe_cod = :fk_assu_espe_cod, 
                        mate_bole_nr_doc = :mate_bole_nr_doc, 
                        mate_bole_data_doc = :mate_bole_data_doc, 
                        fk_subu_cod = :fk_subu_cod
                    WHERE mate_bole_cod = :mate_bole_cod
                ');
                $stmt->execute([
                    'mate_bole_texto' => $_POST['mate_bole_texto'],
                    'mate_bole_data' => $_POST['mate_bole_data'],
                    'fk_tipo_docu_cod' => $_POST['fk_tipo_docu_cod'],
                    'fk_assu_espe_cod' => $_POST['fk_assu_espe_cod'],
                    'mate_bole_nr_doc' => $_POST['mate_bole_nr_doc'],
                    'mate_bole_data_doc' => $_POST['mate_bole_data_doc'],
                    'fk_subu_cod' => $_POST['fk_subu_cod'],
                    'mate_bole_cod' => $mate_bole_cod
                ]);
                $mensagem_sucesso = 'Matéria atualizada com sucesso!';

                // Captura os dados atualizados para exibição
                $dados_cadastrados = [
                    'Assunto Específico' => buscarDescricao($pdo, 'assu_espe_cod', $_POST['fk_assu_espe_cod'], 'assu_espe_descricao', 'bg.vw_assunto_concatenado'),
                    'Assunto Geral' => buscarDescricao($pdo, 'assu_gera_cod', $_POST['fk_assu_gera_cod'], 'assu_gera_descricao', 'bg.vw_assunto_concatenado'),
                    'Unidade' => buscarDescricao($pdo, 'subu_cod', $_POST['fk_subu_cod'], 'descricao', 'public.vw_comando_unidade_subunidade'),
                    'Data da Matéria' => $_POST['mate_bole_data'],
                    'Tipo de Documento' => buscarDescricao($pdo, 'tipo_docu_cod', $_POST['fk_tipo_docu_cod'], 'tipo_docu_descricao', 'bg.tipo_documento'),
                    'Número do Documento' => $_POST['mate_bole_nr_doc'],
                    'Data do Documento' => $_POST['mate_bole_data_doc'],
                    'Texto da Matéria' => nl2br(htmlspecialchars($_POST['mate_bole_texto']))
                ];

                if (isAjaxRequest()) {
                    echo json_encode(['success' => $mensagem_sucesso, 'dados_cadastrados' => $dados_cadastrados]);
                    exit;
                }

            } else {
                // Cadastra uma nova matéria
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
                $mensagem_sucesso = 'Matéria cadastrada com sucesso!';
                // Obter o último código inserido
                $mate_bole_cod = $pdo->lastInsertId('bg.materia_boletim_mate_bole_cod_seq');

                // Captura os dados inseridos para exibição
                $dados_cadastrados = [
                    'Assunto Específico' => buscarDescricao($pdo, 'assu_espe_cod', $_POST['fk_assu_espe_cod'], 'assu_espe_descricao', 'bg.vw_assunto_concatenado'),
                    'Assunto Geral' => buscarDescricao($pdo, 'assu_gera_cod', $_POST['fk_assu_gera_cod'], 'assu_gera_descricao', 'bg.vw_assunto_concatenado'),
                    'Unidade' => buscarDescricao($pdo, 'subu_cod', $_POST['fk_subu_cod'], 'descricao', 'public.vw_comando_unidade_subunidade'),
                    'Data da Matéria' => $_POST['mate_bole_data'],
                    'Tipo de Documento' => buscarDescricao($pdo, 'tipo_docu_cod', $_POST['fk_tipo_docu_cod'], 'tipo_docu_descricao', 'bg.tipo_documento'),
                    'Número do Documento' => $_POST['mate_bole_nr_doc'],
                    'Data do Documento' => $_POST['mate_bole_data_doc'],
                    'Texto da Matéria' => nl2br(htmlspecialchars($_POST['mate_bole_texto']))
                ];

                if (isAjaxRequest()) {
                    echo json_encode(['success' => $mensagem_sucesso, 'dados_cadastrados' => $dados_cadastrados]);
                    exit;
                }
            }
        } catch (PDOException $e) {
            if (isAjaxRequest()) {
                echo json_encode(['error' => 'Erro ao processar a solicitação: ' . $e->getMessage()]);
                exit;
            } else {
                echo '<div class="alert alert-danger">Erro ao processar a solicitação: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $mate_bole_cod > 0 ? 'Editar Matéria' : 'Cadastrar Matéria'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Seu CSS aqui */
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
        .dados-cadastrados th, .dados-cadastrados td {
            padding: 10px;
            text-align: left;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Exibe a mensagem de sucesso ou erro, se houver -->
        <?php if (!empty($mensagem_sucesso) || !empty($dados_cadastrados)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $mensagem_sucesso; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>

            <!-- Exibe os dados cadastrados -->
            <?php if (!empty($dados_cadastrados)): ?>
                <div class="dados-cadastrados">
                    <h4>Dados Cadastrados:</h4>
                    <table class="table table-striped table-bordered">
                        <tbody>
                            <?php foreach ($dados_cadastrados as $campo => $valor): ?>
                                <tr>
                                    <th><?php echo htmlspecialchars($campo); ?></th>
                                    <td><?php echo $campo === 'Texto da Matéria' ? $valor : htmlspecialchars($valor); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <h2 class="mt-2"><?php echo $mate_bole_cod > 0 ? 'Editar Matéria' : 'Cadastrar Matéria'; ?></h2>
        
        <form method="POST" action="" id="formMateria">
            <?php if ($mate_bole_cod > 0): ?>
                <input type="hidden" name="mate_bole_cod" value="<?php echo htmlspecialchars($mate_bole_cod); ?>">
            <?php endif; ?>
            <div class="row">
                <div class="col-md-6">
                    <label for="fk_assu_espe_cod" class="form-label">
                        Assunto Específico
                    </label>
                    <select class="form-select" id="fk_assu_espe_cod" name="fk_assu_espe_cod" required>
                        <option value="">Selecione</option>
                        <?php foreach ($assuntosEspecificos as $assunto): ?>
                            <option value="<?= htmlspecialchars($assunto['assu_espe_cod']) ?>" 
                                data-assu-geral="<?= htmlspecialchars($assunto['assu_gera_cod']) ?>" 
                                data-assu-geral-desc="<?= htmlspecialchars($assunto['assu_gera_descricao']) ?>"
                                <?php 
                                    // Retornar a seleção se estiver editando
                                    if ($mate_bole_cod > 0 && isset($_POST['fk_assu_espe_cod']) && $_POST['fk_assu_espe_cod'] == $assunto['assu_espe_cod']) {
                                        echo 'selected';
                                    }
                                ?>
                            >
                                <?= htmlspecialchars($assunto['assu_espe_descricao']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="fk_assu_gera_cod" class="form-label">Assunto Geral</label>
                    <select class="form-select" id="fk_assu_gera_cod" name="fk_assu_gera_cod" required>
                        <option value="">Selecione o Assunto Geral</option>
                        <?php 
                        // Se estiver editando, preenche o campo com o Assunto Geral correspondente
                        if ($mate_bole_cod > 0 && isset($_POST['fk_assu_gera_cod'])):
                            $assu_gera_cod_selected = $_POST['fk_assu_gera_cod'];
                            $assu_gera_desc_selected = buscarDescricao($pdo, 'assu_gera_cod', $assu_gera_cod_selected, 'assu_gera_descricao', 'bg.vw_assunto_concatenado');
                        ?>
                            <option value="<?= htmlspecialchars($assu_gera_cod_selected) ?>" selected>
                                <?= htmlspecialchars($assu_gera_desc_selected) ?>
                            </option>
                        <?php endif; ?>
                    </select>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <label for="fk_subu_cod" class="form-label">
                        Nome da Unidade
                    </label>
                    <select class="form-select" id="fk_subu_cod" name="fk_subu_cod" required>
                        <option value="">Selecione</option>
                        <?php foreach ($subunidades as $subunidade): ?>
                            <option value="<?= htmlspecialchars($subunidade['subu_cod']) ?>" 
                                <?php 
                                    if ($mate_bole_cod > 0 && isset($_POST['fk_subu_cod']) && $_POST['fk_subu_cod'] == $subunidade['subu_cod']) {
                                        echo 'selected';
                                    }
                                ?>
                            >
                                <?= htmlspecialchars($subunidade['descricao']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="mate_bole_data" class="form-label">
                        Data da Matéria
                    </label>
                    <input type="date" class="form-control" id="mate_bole_data" name="mate_bole_data" 
                        value="<?php 
                            echo isset($_POST['mate_bole_data']) ? htmlspecialchars($_POST['mate_bole_data']) : '';
                        ?>" required>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <label for="fk_tipo_docu_cod" class="form-label">
                        Tipo de Documento
                    </label>
                    <select class="form-select" id="fk_tipo_docu_cod" name="fk_tipo_docu_cod" required>
                        <option value="">Selecione</option>
                        <?php foreach ($tipos_documento as $tipo): ?>
                            <option value="<?= htmlspecialchars($tipo['tipo_docu_cod']) ?>" 
                                <?php 
                                    if ($mate_bole_cod > 0 && isset($_POST['fk_tipo_docu_cod']) && $_POST['fk_tipo_docu_cod'] == $tipo['tipo_docu_cod']) {
                                        echo 'selected';
                                    }
                                ?>
                            >
                                <?= htmlspecialchars($tipo['tipo_docu_descricao']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="mate_bole_nr_doc" class="form-label">
                        Número do Documento
                    </label>
                    <input type="text" class="form-control" id="mate_bole_nr_doc" name="mate_bole_nr_doc" 
                        value="<?php 
                            echo isset($_POST['mate_bole_nr_doc']) ? htmlspecialchars($_POST['mate_bole_nr_doc']) : '';
                        ?>">
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <label for="mate_bole_data_doc" class="form-label">
                        Data do Documento
                    </label>
                    <input type="date" class="form-control" id="mate_bole_data_doc" name="mate_bole_data_doc" 
                        value="<?php 
                            echo isset($_POST['mate_bole_data_doc']) ? htmlspecialchars($_POST['mate_bole_data_doc']) : '';
                        ?>">
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <label for="mate_bole_texto" class="form-label">
                        Texto da Matéria
                    </label>
                    <textarea class="form-control" id="mate_bole_texto" name="mate_bole_texto" rows="5" required><?php 
                        echo isset($_POST['mate_bole_texto']) ? htmlspecialchars($_POST['mate_bole_texto']) : '';
                    ?></textarea>
                </div>
            </div>
            <div class="button-group mt-4">
                <button type="submit" class="btn btn-primary" id="btnSalvar">Salvar</button>
                <a href="consulta_materia1.php" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>

        <!-- Formulário para associar pessoa à matéria -->
        <h4>Associar Pessoa à Matéria</h4>
        <form id="formPessoaMateria">
            <input type="hidden" name="mate_bole_cod" value="<?php echo htmlspecialchars($mate_bole_cod); ?>">

            <div class="row">
                <div class="col-md-6">
                    <label for="fk_poli_mili_matricula" class="form-label">Matrícula Militar</label>
                    <input type="number" class="form-control" id="fk_poli_mili_matricula" name="fk_poli_mili_matricula" required>
                    <!-- Elemento para exibir o nome do PM -->
                    <div id="nomePolicial" class="mt-2"></div>
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-success">Adicionar Pessoa</button>
            </div>
        </form>

        <!-- Tabela de Pessoas Associadas -->
        <h5 class="mt-4">Pessoas Associadas</h5>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Matrícula</th>
                    <th>Nome</th>
                    <th>CPF</th>
                    <th>Unidade</th>
                    <th>Posto/Graduação</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody id="tabelaPessoas">
                <!-- Conteúdo dinâmico -->
            </tbody>
        </table>
    </div>

    <script>
        // Função debounce
        function debounce(func, wait) {
            let timeout;
            return function(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func.apply(this, args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        // Função para buscar e exibir o nome do policial ao digitar a matrícula
        function buscarNomePolicial(matricula) {
            if (!matricula) {
                document.getElementById('nomePolicial').innerHTML = '';
                return;
            }

            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'cadastrar_pms.php', true); // Aponta para cadastrar_pms.php
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    try {
                        var resposta = JSON.parse(xhr.responseText);

                        if (resposta.error) {
                            document.getElementById('nomePolicial').innerHTML = '<span class="text-danger">' + resposta.error + '</span>';
                        } else {
                            document.getElementById('nomePolicial').innerHTML = '<span class="text-success">Nome: ' + resposta.nome + '</span>';
                        }
                    } catch (e) {
                        console.error('Erro ao parsear a resposta JSON:', e);
                        document.getElementById('nomePolicial').innerHTML = '<span class="text-danger">Erro ao processar a resposta do servidor.</span>';
                    }
                }
            };

            // Envia a requisição com a matrícula
            xhr.send('action=buscar_policiais&matricula=' + encodeURIComponent(matricula));
        }

        // Adiciona o evento 'input' com debounce ao campo de matrícula
        document.getElementById('fk_poli_mili_matricula').addEventListener('input', debounce(function() {
            var matricula = this.value.trim();
            buscarNomePolicial(matricula);
        }, 500)); // Aguarda 500ms após o usuário parar de digitar

        // Função para adicionar uma nova pessoa à matéria
        document.getElementById('formPessoaMateria').addEventListener('submit', function(event) {
            event.preventDefault(); // Evita o envio do formulário padrão

            var matricula = document.getElementById('fk_poli_mili_matricula').value.trim();

            // Verifica se o nome do policial está exibido (ou seja, se o PM foi encontrado)
            var nomePolicial = document.getElementById('nomePolicial').innerText;
            if (nomePolicial.startsWith('Nome:')) {
                // Faz a requisição AJAX para associar o PM à matéria
                var xhr = new XMLHttpRequest();
                xhr.open('POST', window.location.href, true); // Envia para o mesmo arquivo
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4 && xhr.status === 200) {
                        try {
                            var resposta = JSON.parse(xhr.responseText);

                            if (resposta.error) {
                                alert(resposta.error);
                            } else if (resposta.success) {
                                alert(resposta.success);

                                // Atualiza a tabela de Pessoas Associadas
                                if (resposta.dados_cadastrados['Texto da Matéria']) {
                                    document.getElementById('mate_bole_texto').value = resposta.dados_cadastrados['Texto da Matéria'].replace(/<br\s*\/?>/mg,"\n");
                                }

                                // Recarregar a página ou atualizar a tabela dinamicamente
                                // Aqui, vamos recarregar a página para refletir as mudanças
                                window.location.reload();
                            }
                        } catch (e) {
                            console.error('Erro ao parsear a resposta JSON:', e);
                            alert('Erro ao processar a resposta do servidor.');
                        }
                    }
                };

                // Envia a requisição para associar a pessoa à matéria
                xhr.send('action=add_pessoa_materia&fk_poli_mili_matricula=' + encodeURIComponent(matricula));
            } else {
                alert('Por favor, insira uma matrícula válida e certifique-se de que o PM existe.');
            }
        });

        // Função para alternar para o modo de edição
        function editarRegistro(matricula) {
            var linha = document.querySelector('tr[data-matricula="' + matricula + '"]');
            var unidade = linha.querySelector('.unidade').textContent;
            var pg_descricao = linha.querySelector('.pg_descricao').textContent;

            // Transformar campo de unidade em input de texto com datalist
            linha.querySelector('.unidade').innerHTML = '<input list="unidades" class="form-control" value="' + unidade + '">' +
                '<datalist id="unidades">' +
                '<?php foreach ($unidades as $unidade): ?>' +
                '<option value="<?= htmlspecialchars($unidade['unidade']); ?>">' +
                '<?php endforeach; ?>' +
                '</datalist>';

            // Substituir o campo de texto de posto/graduação por um select
            var selectPg = '<select class="form-select">';
            <?php foreach ($postosGraduacoes as $pg): ?>
                selectPg += '<option value="<?= htmlspecialchars($pg['pg_descricao']) ?>" ' + 
                            (pg_descricao === '<?= htmlspecialchars($pg['pg_descricao']) ?>' ? 'selected' : '') + 
                            '><?= htmlspecialchars($pg['pg_descricao']) ?></option>';
            <?php endforeach; ?>
            selectPg += '</select>';

            linha.querySelector('.pg_descricao').innerHTML = selectPg;

            // Trocar o botão "Editar" por "Salvar" e adicionar "Cancelar"
            var botoes = linha.querySelector('td:last-child');
            botoes.innerHTML = '<button class="btn btn-success btn-sm" onclick="salvarEdicao(\'' + matricula + '\')">Salvar</button> ' +
                               '<button class="btn btn-secondary btn-sm" onclick="cancelarEdicao(\'' + matricula + '\')">Cancelar</button>';
        }

        // Função para salvar a edição
        function salvarEdicao(matricula) {
            var linha = document.querySelector('tr[data-matricula="' + matricula + '"]');
            var unidade = linha.querySelector('.unidade input').value.trim();
            var pg_descricao = linha.querySelector('.pg_descricao select').value.trim(); // Captura correta do valor do select

            // Fazer a requisição AJAX para atualizar os dados
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'cadastrar_pms.php', true); // Aponta para cadastrar_pms.php
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    try {
                        var resposta = JSON.parse(xhr.responseText);
                        if (resposta.success) {
                            // Atualizar a linha com os novos valores
                            linha.querySelector('.unidade').textContent = unidade;
                            linha.querySelector('.pg_descricao').textContent = pg_descricao;

                            // Restaurar os botões de ação
                            var botoes = linha.querySelector('td:last-child');
                            botoes.innerHTML = '<button class="btn btn-warning btn-sm" onclick="editarRegistro(\'' + matricula + '\')">Editar</button> ' +
                                               '<button class="btn btn-danger btn-sm" onclick="excluirRegistro(\'' + matricula + '\')">Excluir</button>';

                            alert('Registro atualizado com sucesso!');
                        } else {
                            alert(resposta.error);
                        }
                    } catch (e) {
                        console.error('Erro ao parsear a resposta JSON:', e);
                        alert('Erro ao processar a resposta do servidor.');
                    }
                }
            };

            // Enviar os dados para atualização
            xhr.send('action=editar_policia&matricula=' + encodeURIComponent(matricula) + '&pg_descricao=' + encodeURIComponent(pg_descricao) + '&unidade=' + encodeURIComponent(unidade));
        }

        // Função para cancelar a edição
        function cancelarEdicao(matricula) {
            var linha = document.querySelector('tr[data-matricula="' + matricula + '"]');
            var unidade = linha.querySelector('.unidade input').getAttribute('value');
            var pg_descricao = linha.querySelector('.pg_descricao select').value;

            // Restaurar os valores antigos
            linha.querySelector('.unidade').textContent = unidade;
            linha.querySelector('.pg_descricao').textContent = pg_descricao;

            // Restaurar os botões de ação
            var botoes = linha.querySelector('td:last-child');
            botoes.innerHTML = '<button class="btn btn-warning btn-sm" onclick="editarRegistro(\'' + matricula + '\')">Editar</button> ' +
                               '<button class="btn btn-danger btn-sm" onclick="excluirRegistro(\'' + matricula + '\')">Excluir</button>';
        }

        // Função para excluir o registro
        function excluirRegistro(matricula) {
            if (confirm('Tem certeza que deseja excluir este registro?')) {
                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'cadastrar_pms.php', true); // Aponta para cadastrar_pms.php
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4 && xhr.status === 200) {
                        try {
                            var resposta = JSON.parse(xhr.responseText);
                            if (resposta.success) {
                                alert('Registro excluído com sucesso!');
                                document.querySelector('tr[data-matricula="' + matricula + '"]').remove();
                            } else {
                                alert(resposta.error);
                            }
                        } catch (e) {
                            console.error('Erro ao parsear a resposta JSON:', e);
                            alert('Erro ao processar a resposta do servidor.');
                        }
                    }
                };

                // Envia a requisição para excluir o registro
                xhr.send('action=excluir_policia&matricula=' + encodeURIComponent(matricula));
            }
        }

        // Evento para atualizar o Assunto Geral com base no Assunto Específico
        document.getElementById('fk_assu_espe_cod').addEventListener('change', function() {
            var selectedOption = this.options[this.selectedIndex];
            var assuGeralCod = selectedOption.getAttribute('data-assu-geral');
            var assuGeralDesc = selectedOption.getAttribute('data-assu-geral-desc');

            var geralSelect = document.getElementById('fk_assu_gera_cod');
            geralSelect.innerHTML = ''; // Limpa as opções

            if (assuGeralCod && assuGeralDesc) {
                var option = document.createElement('option');
                option.value = assuGeralCod;
                option.textContent = assuGeralDesc;
                option.selected = true;
                geralSelect.appendChild(option);
            }
        });
    </script>
</body>
</html>
