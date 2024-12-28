<?php
// modal/modal_adicionar_materia.php
?>
<div id="modalAdicionarMateria" class="modal fade" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Adicionar Matéria ao Boletim</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div id="listMateriasSemBoletim">
                    <!-- Conteúdo carregado via AJAX -->
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Evento para adicionar matéria ao boletim
$(document).on('click', '.adicionar-materia', function(e) {
    var mate_bole_cod = $(this).data('mate-cod');
    var bole_cod = $('#modalAdicionarMateria').data('bole-cod');

    $.ajax({
        url: 'ajax/adicionar_materia.php',
        method: 'POST',
        data: {
            bole_cod: bole_cod,
            mate_bole_cod: mate_bole_cod
        },
        success: function(response) {
            var res = JSON.parse(response);
            if (res.status === 'success') {
                alert(res.message);
                // Recarregar a lista de matérias sem boletim
                $('#listMateriasSemBoletim').load('ajax/list_materias_sem_boletim.php?bole_cod=' + encodeURIComponent(bole_cod));
                // Opcional: Recarregar a página para refletir as mudanças no Kanban
                location.reload();
            } else {
                alert(res.message);
            }
        },
        error: function() {
            alert('Erro na requisição AJAX.');
        }
    });
});
</script>
