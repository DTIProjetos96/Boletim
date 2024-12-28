<?php if ($mate_bole_cod > 0): ?>
    <div class="iframe-container mt-5">
        <h4>Associar Pessoas à Matéria</h4>
        <iframe src="cad_materia_pessoas.php?mate_bole_cod=<?= urlencode($mate_bole_cod) ?>" title="Associar Pessoas"></iframe>
    </div>
<?php endif; ?>
