<?php
require '../db.php';

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

// Recupera as opções para o campo Assunto Específico
$stmt_assunto = $pdo->prepare("
    SELECT assu_espe_cod, assu_espe_descricao 
    FROM bg.assunto_especifico 
    ORDER BY assu_espe_descricao
");
$stmt_assunto->execute();
$assuntos = $stmt_assunto->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $mate_bole_texto = $_POST['mate_bole_texto'];
    $mate_bole_data = $_POST['mate_bole_data'];
    $mate_bole_ordem = $_POST['mate_bole_ordem'];
    $fk_tipo_docu_cod = $_POST['fk_tipo_docu_cod'];
    $fk_assu_espe_cod = $_POST['fk_assu_espe_cod'];
    $mate_bole_nr_doc = $_POST['mate_bole_nr_doc'];
    $mate_bole_data_doc = $_POST['mate_bole_data_doc'];
    $fk_subu_cod = $_POST['fk_subu_cod'];

    $stmt = $pdo->prepare("INSERT INTO bg.materia_boletim (
        mate_bole_texto, mate_bole_data, mate_bole_ordem, fk_tipo_docu_cod, 
        fk_assu_espe_cod, mate_bole_nr_doc, mate_bole_data_doc, fk_subu_cod
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->execute([
        $mate_bole_texto, $mate_bole_data, $mate_bole_ordem, $fk_tipo_docu_cod,
        $fk_assu_espe_cod, $mate_bole_nr_doc, $mate_bole_data_doc, $fk_subu_cod
    ]);

    echo "<div class='alert alert-success'>Matéria cadastrada com sucesso!</div>";
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Matéria</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .form-control, .form-select, .form-label {
            margin-bottom: 10px; /* Reduz o espaçamento entre os campos */
        }
        .row {
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="mt-5">Cadastro de Matéria</h2>
        <form method="POST" action="cad_materia.php">
            <div class="row">
                <div class="col-md-6">
                    <label for="fk_assu_espe_cod" class="form-label">Assunto Específico</label>
                    <select class="form-control" id="fk_assu_espe_cod" name="fk_assu_espe_cod" required>
                        <option value="">Selecione</option>
                        <?php foreach ($assuntos as $assunto): ?>
                            <option value="<?= $assunto['assu_espe_cod'] ?>">
                                <?= $assunto['assu_espe_descricao'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="fk_subu_cod" class="form-label">Código da Subunidade</label>
                    <select class="form-control" id="fk_subu_cod" name="fk_subu_cod" required>
                        <option value="">Selecione</option>
                        <?php foreach ($subunidades as $subunidade): ?>
                            <option value="<?= $subunidade['subu_cod'] ?>">
                                <?= $subunidade['descricao'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="mb-3">
                <label for="mate_bole_texto" class="form-label">Texto da Matéria</label>
                <textarea class="form-control" id="mate_bole_texto" name="mate_bole_texto" rows="4" required></textarea>
            </div>
            <div class="mb-3">
                <label for="mate_bole_data" class="form-label">Data da Matéria</label>
                <input type="date" class="form-control" id="mate_bole_data" name="mate_bole_data" required>
            </div>
            <div class="mb-3">
                <label for="mate_bole_ordem" class="form-label">Ordem da Matéria</label>
                <input type="number" class="form-control" id="mate_bole_ordem" name="mate_bole_ordem" required>
            </div>
            <div class="mb-3">
                <label for="fk_tipo_docu_cod" class="form-label">Tipo de Documento</label>
                <select class="form-control" id="fk_tipo_docu_cod" name="fk_tipo_docu_cod" required>
                    <option value="">Selecione</option>
                    <option value="1">Não possui</option>
                    <option value="2">Portaria</option>
                    <option value="3">Ofício</option>
                    <option value="4">Memorando</option>
                    <option value="5">Parte</option>
                    <option value="6">Requerimento</option>
                    <option value="7">Sindicância</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="mate_bole_nr_doc" class="form-label">Número do Documento</label>
                <input type="text" class="form-control" id="mate_bole_nr_doc" name="mate_bole_nr_doc">
            </div>
            <div class="mb-3">
                <label for="mate_bole_data_doc" class="form-label">Data do Documento</label>
                <input type="datetime-local" class="form-control" id="mate_bole_data_doc" name="mate_bole_data_doc">
            </div>
            <button type="submit" class="btn btn-primary">Cadastrar Matéria</button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.ckeditor.com/4.24.0-lts/standard/ckeditor.js"></script>
    <script>
        CKEDITOR.replace('mate_bole_texto');
    </script>
</body>
</html>
