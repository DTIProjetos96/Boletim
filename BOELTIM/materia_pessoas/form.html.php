<?php
if (!isset($mate_bole_cod)) {
    $mate_bole_cod = 0; // Define um valor padrão
}
?>

<!-- Adicionar links de CSS diretamente no formulário -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="styles.css" rel="stylesheet"> <!-- Certifique-se de que esse arquivo está no local correto -->

<form method="POST" action="cad_materia.php" enctype="multipart/form-data" id="formMateria" class="container mt-4">
    <h2 class="mb-4 text-primary text-center"><?= $mate_bole_cod > 0 ? 'Editar Matéria' : 'Cadastrar Matéria'; ?></h2>

    <?php if ($mate_bole_cod > 0): ?>
        <input type="hidden" name="mate_bole_cod" value="<?= htmlspecialchars($mate_bole_cod); ?>">
        <input type="hidden" name="action" value="update_materia">
    <?php else: ?>
        <input type="hidden" name="action" value="add_materia">
    <?php endif; ?>

    <!-- Assunto Específico e Assunto Geral -->
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="fk_assu_espe_cod" class="form-label">Assunto Específico</label>
            <select class="form-select" id="fk_assu_espe_cod" name="fk_assu_espe_cod" required>
                <option value="">Selecione</option>
                <?php foreach ($assuntosEspecificos as $assunto): ?>
                    <option value="<?= htmlspecialchars($assunto['assu_espe_cod']) ?>"
                        data-geral-cod="<?= htmlspecialchars($assunto['assu_gera_cod']) ?>"
                        data-geral-desc="<?= htmlspecialchars($assunto['assu_gera_descricao']) ?>">
                        <?= htmlspecialchars($assunto['assu_espe_descricao']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6 mb-3">
            <label for="fk_assu_gera_cod" class="form-label">Assunto Geral</label>
            <select class="form-select" id="fk_assu_gera_cod" name="fk_assu_gera_cod" required>
                <option value="">Selecione o Assunto Geral</option>
            </select>
        </div>
    </div>

    <!-- Nome da Unidade e Data da Matéria -->
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="fk_subu_cod" class="form-label">Nome da Unidade</label>
            <select class="form-select" id="fk_subu_cod" name="fk_subu_cod" required>
                <option value="">Selecione</option>
                <?php foreach ($subunidades as $subunidade): ?>
                    <option value="<?= htmlspecialchars($subunidade['subu_cod']) ?>"
                        <?php if ($mate_bole_cod > 0 && isset($materia['fk_subu_cod']) && $materia['fk_subu_cod'] == $subunidade['subu_cod']) echo 'selected'; ?>>
                        <?= htmlspecialchars($subunidade['descricao']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6 mb-3">
            <label for="mate_bole_data" class="form-label">Data da Matéria</label>
            <input type="date" class="form-control" id="mate_bole_data" name="mate_bole_data"
                value="<?php echo $mate_bole_cod > 0 ? htmlspecialchars($materia['mate_bole_data']) : ''; ?>" required>
        </div>
    </div>

    <!-- Tipo de Documento e Número do Documento -->
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="fk_tipo_docu_cod" class="form-label">Tipo de Documento</label>
            <select class="form-select" id="fk_tipo_docu_cod" name="fk_tipo_docu_cod" required>
                <option value="">Selecione</option>
                <?php foreach ($tiposDocumento as $tipo): ?>
                    <option value="<?= htmlspecialchars($tipo['tipo_docu_cod']) ?>"
                        <?php if ($mate_bole_cod > 0 && isset($materia['fk_tipo_docu_cod']) && $materia['fk_tipo_docu_cod'] == $tipo['tipo_docu_cod']) echo 'selected'; ?>>
                        <?= htmlspecialchars($tipo['tipo_docu_descricao']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6 mb-3">
            <label for="mate_bole_nr_doc" class="form-label">Número do Documento</label>
            <input type="text" class="form-control" id="mate_bole_nr_doc" name="mate_bole_nr_doc"
                value="<?php echo $mate_bole_cod > 0 ? htmlspecialchars($materia['mate_bole_nr_doc']) : ''; ?>">
        </div>
    </div>

    <!-- Data do Documento -->
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="mate_bole_data_doc" class="form-label">Data do Documento</label>
            <input type="date" class="form-control" id="mate_bole_data_doc" name="mate_bole_data_doc"
                value="<?php echo $mate_bole_cod > 0 ? htmlspecialchars($materia['mate_bole_data_doc']) : ''; ?>">
        </div>
    </div>

    <!-- Texto da Matéria -->
    <div class="row">
        <div class="col-md-12 mb-3">
            <label for="assu_espe_texto" class="form-label">Texto da Matéria</label>
            <textarea class="form-control" id="assu_espe_texto" rows="3" ></textarea>
        </div>
    </div>

    <!-- Botões -->
    <div class="row mt-4">
        <div class="col-md-12 d-flex justify-content-end gap-2">
            <button type="submit" class="btn btn-primary"><?= $mate_bole_cod > 0 ? 'Atualizar' : 'Salvar'; ?></button>

            <a href="consulta_materia1.php" class="btn btn-secondary">Cancelar</a>
        </div>
    </div>
</form>

<?php if ($mate_bole_cod > 0): ?>
    <div class="mt-4">
        <h4>Associar Pessoas à Matéria</h4>
        <iframe 
            src="cad_materia_pessoas.php?mate_bole_cod=<?= htmlspecialchars($mate_bole_cod); ?>" 
            width="100%" 
            height="600px" 
            style="border: none;"></iframe>
    </div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="scripts.js"></script>
