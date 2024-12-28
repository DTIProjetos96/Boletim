<?php if (!isset($materias) || empty($materias)): ?>
    <div class="alert alert-info">
        Nenhum registro encontrado.
    </div>
<?php else: ?>
    <div class="table-responsive mt-3">
        <table class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>Assunto Específico</th>
                    <th>Assunto Geral</th>
                    <th>Data</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($materias as $materia): ?>
                    <tr>
                        <td><?= htmlspecialchars($materia['assu_espe_descricao']) ?></td>
                        <td><?= htmlspecialchars($materia['assu_gera_descricao']) ?></td>
                        <td><?= htmlspecialchars(date('d/m/Y', strtotime($materia['mate_bole_data']))) ?></td>
                        <td>
                            <a href="cad_materia.php?mate_bole_cod=<?= urlencode($materia['mate_bole_cod']) ?>" class="btn btn-sm btn-primary">
                                Editar
                            </a>
                            <form method="POST" action="delete_materia.php" style="display:inline-block;">
                                <input type="hidden" name="mate_bole_cod" value="<?= htmlspecialchars($materia['mate_bole_cod']) ?>">
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza que deseja excluir esta matéria?');">
                                    Excluir
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
