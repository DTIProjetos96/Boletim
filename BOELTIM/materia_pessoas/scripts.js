document.addEventListener('DOMContentLoaded', function () {
    const formMateria = document.getElementById('formMateria');
    
    // Função para exibir a confirmação antes de enviar o formulário
    function confirmarCriacao(event) {
        const mensagem = "Tem certeza de que deseja criar/atualizar esta matéria?";
        const confirmar = confirm(mensagem); // Abre o diálogo de confirmação
        if (!confirmar) {
            event.preventDefault(); // Cancela o envio do formulário se o usuário não confirmar
        }
    }

    // Adiciona o evento ao formulário
    formMateria.addEventListener('submit', confirmarCriacao);

    // Restante do código já existente
    const assuntoEspecificoSelect = document.getElementById('fk_assu_espe_cod');
    const assuntoGeralSelect = document.getElementById('fk_assu_gera_cod');
    const textoMateriaTextarea = document.getElementById('mate_bole_texto');
    const camposFerias = document.getElementById('campos-ferias');

    const codigoFerias = '34'; // Substitua pelo código do assunto específico para "férias"
    
     // Adiciona o evento ao formulário
    if (formMateria) {
        formMateria.addEventListener('submit', confirmarCriacao);
    }

    assuntoEspecificoSelect.addEventListener('change', function () {
        const assuEspeCod = this.value;

        if (assuEspeCod) {
            if (assuEspeCod === codigoFerias) {
                camposFerias.style.display = 'block';
            } else {
                camposFerias.style.display = 'none';
            }

            fetch(`handlers.php?action=fetch_assunto_texto&assu_espe_cod=${assuEspeCod}`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances['mate_bole_texto']) {
                            CKEDITOR.instances['mate_bole_texto'].setData(data.assu_espe_texto || '');
                        } else {
                            textoMateriaTextarea.value = data.assu_espe_texto || '';
                        }

                        assuntoGeralSelect.innerHTML = '';
                        if (data.assu_gera_cod && data.assu_gera_descricao) {
                            const option = document.createElement('option');
                            option.value = data.assu_gera_cod;
                            option.textContent = data.assu_gera_descricao;
                            option.selected = true;
                            assuntoGeralSelect.appendChild(option);
                        } else {
                            const defaultOption = document.createElement('option');
                            defaultOption.value = '';
                            defaultOption.textContent = 'Selecione o Assunto Geral';
                            assuntoGeralSelect.appendChild(defaultOption);
                        }
                    } else {
                        console.error('Erro ao buscar dados do Assunto Específico:', data.error);
                        alert('Erro ao buscar os dados do Assunto Específico.');
                    }
                })
                .catch(error => console.error('Erro na requisição:', error));
        } else {
            camposFerias.style.display = 'none';
            assuntoGeralSelect.innerHTML = '<option value="">Selecione o Assunto Geral</option>';
            if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances['mate_bole_texto']) {
                CKEDITOR.instances['mate_bole_texto'].setData('');
            } else {
                textoMateriaTextarea.value = '';
            }
        }
    });

    if (assuntoEspecificoSelect.value) {
        assuntoEspecificoSelect.dispatchEvent(new Event('change'));
    }
});
