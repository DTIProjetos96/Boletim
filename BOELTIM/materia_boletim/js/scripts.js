// js/scripts.js

document.addEventListener('DOMContentLoaded', function() {
    // Função para alternar a exibição do texto truncado e completo
    function toggleTexto(event) {
        var toggle = event.target;
        var textoTruncado = toggle.parentElement.querySelector('.texto-truncado');
        var textoCompleto = toggle.parentElement.querySelector('.texto-completo');

        console.log("Toggle clicado:", toggle); // Para depuração

        if (textoCompleto.classList.contains('d-none')) {
            // Mostrar o texto completo
            textoTruncado.classList.add('d-none');
            textoCompleto.classList.remove('d-none');
            toggle.textContent = '-';
            toggle.setAttribute('aria-expanded', 'true');
        } else {
            // Mostrar o texto truncado
            textoTruncado.classList.remove('d-none');
            textoCompleto.classList.add('d-none');
            toggle.textContent = '+';
            toggle.setAttribute('aria-expanded', 'false');
        }
    }

    // Adiciona um ouvinte de eventos para os botões de toggle usando event delegation
    document.getElementById('boletinsContainer').addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('toggle-texto')) {
            toggleTexto(e);
        }
    });

    // Função para excluir matérias selecionadas 
    window.excluirMateriasSelecionadas = function() {
        var checkboxes = document.querySelectorAll('.select-materia:checked');
        if (checkboxes.length === 0) {
            alert("Nenhuma matéria selecionada para excluir.");
            return;
        }

        if (!confirm("Tem certeza que deseja excluir as matérias selecionadas do boletim?")) {
            return;
        }

        var mate_publ_cods = Array.from(checkboxes).map(cb => cb.getAttribute('data-mate-publ-cod'));

        if (mate_publ_cods.length === 0) {
            alert("Nenhuma matéria válida selecionada para excluir.");
            return;
        }

        // Cria um único objeto para enviar todos os códigos de uma vez
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "json.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                if(xhr.status === 200){
                    try {
                        var resposta = JSON.parse(xhr.responseText);
                        alert(resposta.message);
                        if (resposta.status === 'success') {
                            // Recarregar a página para atualizar o Kanban
                            location.reload();
                        }
                    } catch (e) {
                        alert("Erro ao processar resposta do servidor.");
                    }
                } else {
                    alert("Erro na requisição AJAX.");
                }
            }
        };
        // Enviar múltiplos códigos como array
        var params = "ajaxtp=delete&materias=" + encodeURIComponent(JSON.stringify(mate_publ_cods));
        xhr.send(params);
    }

    // Função para mover matérias selecionadas
    window.moverMateriasSelecionadas = function() {
        var checkboxes = document.querySelectorAll('.select-materia:checked');
        if (checkboxes.length === 0) {
            alert("Nenhuma matéria selecionada para mover.");
            return;
        }

        var bole_cod_destino = prompt("Digite o código do boletim de destino:");

        if (!bole_cod_destino || isNaN(bole_cod_destino) || parseInt(bole_cod_destino) <= 0) {
            alert("Código de boletim de destino inválido.");
            return;
        }

        var mate_publ_cods = Array.from(checkboxes).map(cb => cb.getAttribute('data-mate-publ-cod'));

        if (mate_publ_cods.length === 0) {
            alert("Nenhuma matéria válida selecionada para mover.");
            return;
        }

        // Cria um único objeto para enviar todos os códigos de uma vez
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "json.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                if(xhr.status === 200){
                    try {
                        var resposta = JSON.parse(xhr.responseText);
                        if (resposta.status === 'success') {
                            alert("Matérias movidas com sucesso.");
                            // Recarregar a página para atualizar o Kanban
                            location.reload();
                        } else {
                            alert("Erro ao mover matérias: " + resposta.message);
                        }
                    } catch (e) {
                        alert("Erro ao processar resposta do servidor.");
                    }
                } else {
                    alert("Erro na requisição AJAX.");
                }
            }
        };
        // Enviar múltiplos códigos como array e o código do boletim de destino
        var params = "ajaxtp=save&item_mode=updstep&bole_cod_destino=" + encodeURIComponent(bole_cod_destino) + "&materias=" + encodeURIComponent(JSON.stringify(mate_publ_cods));
        xhr.send(params);

        alert("Processo de movimentação iniciado.");
    }
});
