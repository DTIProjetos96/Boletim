<?php
session_start();
include '../db.php'; // Conexão com o banco de dados


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


// Verificação e processamento do formulário de "salvar matéria"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'salvar_materia') {
    $assu_espe_cod = $_POST['fk_assu_espe_cod'];
    $assu_gera_cod = $_POST['fk_assu_gera_cod'];
    $subu_cod = isset($_POST['fk_subu_cod']) ? $_POST['fk_subu_cod'] : null;
    $data_materia = isset($_POST['mate_bole_data']) ? $_POST['mate_bole_data'] : null;
    $tipo_docu_cod = $_POST['fk_tipo_docu_cod'];
    $nr_doc = $_POST['mate_bole_nr_doc'];
    $data_doc = $_POST['mate_bole_data_doc'];
    $texto_materia = $_POST['mate_bole_texto'];

    if (empty($data_materia)) {
        echo json_encode(['success' => false, 'message' => 'Data da matéria não pode estar vazia.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO bg.materia_boletim 
                               (fk_assu_espe_cod, fk_assu_gera_cod, fk_subu_cod, mate_bole_data, 
                                fk_tipo_docu_cod, mate_bole_nr_doc, mate_bole_data_doc, mate_bole_texto) 
                               VALUES (:assu_espe_cod, :assu_gera_cod, :subu_cod, :data_materia, 
                                       :tipo_docu_cod, :nr_doc, :data_doc, :texto_materia)");
        $stmt->execute([
            'assu_espe_cod' => $assu_espe_cod,
            'assu_gera_cod' => $assu_gera_cod,
            'subu_cod' => $subu_cod,
            'data_materia' => $data_materia,
            'tipo_docu_cod' => $tipo_docu_cod,
            'nr_doc' => $nr_doc,
            'data_doc' => $data_doc,
            'texto_materia' => $texto_materia,
        ]);

        echo json_encode(['success' => true, 'message' => 'Matéria salva com sucesso!']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erro ao salvar a matéria: ' . $e->getMessage()]);
    }
    exit;
}


// Login do usuário (exemplo para teste)
$user_login = isset($_SESSION['user_login']) ? $_SESSION['user_login'] : '452912';

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


    

$mate_bole_cod = isset($_GET['mate_bole_cod']) ? (int)$_GET['mate_bole_cod'] : 0;
$materia = [];

// Carregar detalhes da matéria para edição
if ($mate_bole_cod > 0) {
    $stmt = $pdo->prepare('SELECT * FROM bg.materia_boletim WHERE mate_bole_cod = :mate_bole_cod');
    $stmt->execute(['mate_bole_cod' => $mate_bole_cod]);
    $materia = $stmt->fetch(PDO::FETCH_ASSOC);
}

$stmt_unidades = $pdo->prepare("SELECT DISTINCT cod_unidade, unidade FROM bg.vw_policiais_militares WHERE matricula = :matricula ORDER BY unidade");
$stmt_unidades->execute(['matricula' => $user_login]);
$unidades = $stmt_unidades->fetchAll(PDO::FETCH_ASSOC);


$stmt_pg = $pdo->query("SELECT DISTINCT pg_descricao FROM bg.vw_policiais_militares ORDER BY pg_descricao");
$postosGraduacoes = $stmt_pg->fetchAll(PDO::FETCH_ASSOC);

$stmt_assuntos = $pdo->query("SELECT assu_espe_cod, assu_espe_descricao, assu_gera_cod, assu_gera_descricao FROM bg.vw_assunto_concatenado ORDER BY assu_espe_descricao");
$assuntosEspecificos = $stmt_assuntos->fetchAll(PDO::FETCH_ASSOC);

$stmt_tipo_docu = $pdo->query("SELECT tipo_docu_cod, tipo_docu_descricao FROM bg.tipo_documento ORDER BY tipo_docu_descricao");
$tipos_documento = $stmt_tipo_docu->fetchAll(PDO::FETCH_ASSOC);
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
</head>
<body>
<div class="container mt-5">
    <h2><?php echo $mate_bole_cod > 0 ? 'Editar Matéria' : 'Cadastrar Matéria'; ?></h2>
    
    <div id="mensagem"></div>


   <form id="formMateria" method="POST">
        <input type="hidden" name="mate_bole_cod" value="<?php echo $mate_bole_cod; ?>">

        <!-- Campo Assunto Específico e Assunto Geral -->
        <div class="row">
            <div class="col-md-6">
                <label for="fk_assu_espe_cod" class="form-label">Assunto Específico</label>
                <select class="form-select" id="fk_assu_espe_cod" name="fk_assu_espe_cod" required>
                    <option value="">Selecione</option>
                    <?php foreach ($assuntosEspecificos as $assunto): ?>
                        <option value="<?= htmlspecialchars($assunto['assu_espe_cod']) ?>"
                                data-assu-geral="<?= htmlspecialchars($assunto['assu_gera_cod']) ?>"
                                data-assu-geral-desc="<?= htmlspecialchars($assunto['assu_gera_descricao']) ?>"
                            <?php echo (isset($materia['fk_assu_espe_cod']) && $materia['fk_assu_espe_cod'] == $assunto['assu_espe_cod']) ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($assunto['assu_espe_descricao']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label for="fk_assu_gera_cod" class="form-label">Assunto Geral</label>
                <select class="form-select" id="fk_assu_gera_cod" name="fk_assu_gera_cod" required>
                    <option value="">Selecione o Assunto Geral</option>
                    <?php if (isset($materia['fk_assu_gera_cod'])): ?>
                        <option value="<?php echo $materia['fk_assu_gera_cod']; ?>" selected>
                            <?php echo $materia['fk_assu_gera_cod']; ?>
                        </option>
                    <?php endif; ?>
                </select>
            </div>
        </div>

        <!-- Campo Nome da Unidade e Data da Matéria -->
        <div class="row mt-3">
            <div class="col-md-6">
                <label for="fk_subu_cod" class="form-label">Nome da Unidade</label>
              <select class="form-select" id="fk_subu_cod" name="fk_subu_cod" required>
    <option value="">Selecione</option>
    <?php foreach ($unidades as $unidade): ?>
        <option value="<?= htmlspecialchars($unidade['cod_unidade']) ?>">
            <?= htmlspecialchars($unidade['unidade']) ?>
        </option>
    <?php endforeach; ?>
</select>

            </div>
            <div class="col-md-6">
                <label for="mate_bole_data" class="form-label">Data da Matéria</label>
                <input type="date" class="form-control" id="mate_bole_data" name="mate_bole_data" 
                       value="<?php echo isset($materia['mate_bole_data']) ? htmlspecialchars($materia['mate_bole_data']) : ''; ?>" required>
            </div>
        </div>

        <!-- Campo Tipo de Documento e Número do Documento -->
        <div class="row mt-3">
            <div class="col-md-6">
                <label for="fk_tipo_docu_cod" class="form-label">Tipo de Documento</label>
                <select class="form-select" id="fk_tipo_docu_cod" name="fk_tipo_docu_cod" required>
                    <option value="">Selecione</option>
                    <?php foreach ($tipos_documento as $tipo): ?>
                        <option value="<?= htmlspecialchars($tipo['tipo_docu_cod']) ?>"
                            <?php echo (isset($materia['fk_tipo_docu_cod']) && $materia['fk_tipo_docu_cod'] == $tipo['tipo_docu_cod']) ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($tipo['tipo_docu_descricao']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label for="mate_bole_nr_doc" class="form-label">Número do Documento</label>
                <input type="text" class="form-control" id="mate_bole_nr_doc" name="mate_bole_nr_doc"
                       value="<?php echo isset($materia['mate_bole_nr_doc']) ? htmlspecialchars($materia['mate_bole_nr_doc']) : ''; ?>">
            </div>
        </div>

        <!-- Campo Data do Documento -->
        <div class="row mt-3">
            <div class="col-md-6">
                <label for="mate_bole_data_doc" class="form-label">Data do Documento</label>
                <input type="date" class="form-control" id="mate_bole_data_doc" name="mate_bole_data_doc" 
                       value="<?php echo isset($materia['mate_bole_data_doc']) ? htmlspecialchars($materia['mate_bole_data_doc']) : ''; ?>">
            </div>
        </div>

        <!-- Campo Texto da Matéria -->
        <div class="row mt-3">
            <div class="col-md-12">
                <label for="mate_bole_texto" class="form-label">Texto da Matéria</label>
                <textarea class="form-control" id="mate_bole_texto" name="mate_bole_texto" rows="5" required><?php echo isset($materia['mate_bole_texto']) ? htmlspecialchars($materia['mate_bole_texto']) : ''; ?></textarea>
            </div>
        </div>

        <div class="d-flex justify-content-end mt-4">
        <button type="button" onclick="salvarMateria()" class="btn btn-primary">Salvar</button>
        <a href="consulta_materia1.php" class="btn btn-secondary ms-2">Cancelar</a>
    </div>
    </form>
    

    <!-- Formulário para associar pessoa -->
    <h4>Informe dados do investigado</h4>
    <form id="formPessoaMateria">
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="buscaPolicial" class="form-label">Buscar Policial (CPF, Nome ou Matrícula)</label>
                <input type="text" class="form-control" id="buscaPolicial" name="buscaPolicial" required disable>
            </div> 
            <div class="col-md-6">
                <label for="unidade" class="form-label">Unidade</label>
                <select class="form-select" id="unidade" name="unidade">
                    <option value="">Selecione a Unidade</option>
                    <!-- As unidades serão carregadas dinamicamente após a seleção do policial -->
                </select>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="postoGraduacao" class="form-label">Posto/Graduação</label>
                <select class="form-select" id="postoGraduacao" name="postoGraduacao">
                    <option value="">Selecione o Posto/Graduação</option>
                    <!-- As opções serão carregadas dinamicamente após a seleção do policial -->
                </select>
            </div>
            <div class="col-md-6 align-self-end">
                <button type="submit" class="btn btn-primary">Adicionar PM</button>
            </div>
        </div>
    </form>

    <h5 class="mt-4">PMs Denunciados</h5>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Ações</th>
                <th>Nome</th>
                <th>Posto/Grad Atual</th>
                <th>Unidade</th>
                <th>Acusação</th>
            </tr>
        </thead>
        <tbody id="tabelaPessoas">
            <!-- Conteúdo dinâmico -->
        </tbody>
    </table>
</div>

<script>
$(document).ready(function() {
    // Configuração do autocomplete para buscar policiais
    $('#buscaPolicial').autocomplete({
        source: function(request, response) {
            $.ajax({
                url: window.location.href,
                type: 'POST',
                data: {
                    action: 'buscar_policiais_autocomplete',
                    term: request.term
                },
                success: function(data) {
                    response(JSON.parse(data));
                },
                error: function() {
                    alert('Erro na busca de policiais.');
                }
            });
        },
        minLength: 2,
        select: function(event, ui) {
            // Armazena os dados do policial selecionado e preenche os campos de unidade e posto/graduação
            if (ui.item) {
                $('#buscaPolicial').data('selected-policial', ui.item);
                $('#buscaPolicial').val(ui.item.label); // Exibe o nome completo no campo

                // Preenche os campos de Unidade e Posto/Graduação com os dados do policial selecionado
                $('#unidade').html('<option value="' + ui.item.unidade + '">' + ui.item.unidade + '</option>');
                $('#postoGraduacao').html('<option value="' + ui.item.pg_descricao + '">' + ui.item.pg_descricao + '</option>');
            } else {
                alert("Erro ao carregar os dados do policial. Verifique as informações na base de dados.");
            }
            return false; // Impede o autocomplete de substituir o campo com apenas o valor do 'label'
        }
    });

    // Adicionar pessoa à tabela de pessoas associadas
    $('#formPessoaMateria').submit(function(event) {
        event.preventDefault();
        var policial = $('#buscaPolicial').data('selected-policial');
        var unidade = $('#unidade').val();
        var postoGraduacao = $('#postoGraduacao').val();

        if (policial) {
            if ($('#tabelaPessoas tr[data-matricula="' + policial.matricula + '"]').length === 0) {
                $('#tabelaPessoas').append(
                    '<tr data-matricula="' + policial.matricula + '">' +
                    '<td>' +
                    '<button class="btn btn-danger btn-sm" onclick="excluirRegistro(\'' + policial.matricula + '\')">Excluir</button> ' +
                    '<button class="btn btn-warning btn-sm" onclick="editarRegistro(\'' + policial.matricula + '\')">Editar</button>' +
                    '</td>' +
                    '<td>' + policial.nome + '</td>' +
                    '<td class="pg_descricao">' + postoGraduacao + '</td>' +
                    '<td class="unidade">' + unidade + '</td>' +
                    '<td>1</td>' + // Exemplo para a coluna de acusação, ajuste conforme necessário
                    '</tr>'
                );
                $('#buscaPolicial').val('');
                $('#buscaPolicial').removeData('selected-policial');
                $('#unidade').html('<option value="">Selecione a Unidade</option>'); // Reset
                $('#postoGraduacao').html('<option value="">Selecione o Posto/Graduação</option>'); // Reset
            } else {
                alert('Este policial já foi adicionado.');
            }
        } else {
            alert('Selecione um policial válido para adicionar.');
        }
    });
});

// Função para excluir o registro
function excluirRegistro(matricula) {
    $('tr[data-matricula="' + matricula + '"]').remove();
}
</script>
</body>
</html>