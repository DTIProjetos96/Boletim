<?php 
session_start(); 
include '../db.php';

// Definindo o valor de 'ferias'
$mostrarCamposFerias = isset($_GET['ferias']) && filter_var($_GET['ferias'], FILTER_VALIDATE_BOOLEAN);
error_log("Valor do parâmetro ferias: " . ($mostrarCamposFerias ? "true" : "false"));
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Matéria de Pessoas</title>
    <style>
        /* Seu estilo permanece aqui */
    </style>
    <script>
        // Autocomplete e preenchimento automático
        async function autocompletePM(query) {
            if (query.length < 3) {
                document.getElementById('autocomplete-results').innerHTML = '';
                return;
            }

            try {
                const response = await fetch('cad_materia_pessoas.php?action=autocomplete&query=' + encodeURIComponent(query));
                const data = await response.json();
                const resultsContainer = document.getElementById('autocomplete-results');
                resultsContainer.innerHTML = '';

                data.forEach(item => {
                    const div = document.createElement('div');
                    div.className = 'autocomplete-item';
                    div.textContent = item.policial_militar;
                    div.onclick = () => selectPM(item.matricula, item.policial_militar);
                    resultsContainer.appendChild(div);
                });
            } catch (error) {
                console.error("Erro no autocomplete:", error);
            }
        }

        function selectPM(matricula, nome) {
            document.getElementById('pm').value = nome;
            document.getElementById('matricula_hidden').value = matricula;
            document.getElementById('autocomplete-results').innerHTML = '';
        }

        function adicionarPolicial() {
            const nomePM = document.getElementById("pm").value;
            const postoPM = document.getElementById("pg").value;
            const unidadePM = document.getElementById("unidade").value;

            if (!nomePM || !postoPM || !unidadePM) {
                alert("Preencha todos os campos!");
                return;
            }

            const listaPM = document.getElementById("policialList");
            const novoItem = document.createElement("div");
            novoItem.classList.add("policial-item");
            novoItem.innerHTML = `
                <span>Nome: ${nomePM}</span>
                <span>Posto: ${postoPM}</span>
                <span>Unidade: ${unidadePM}</span>
                <button onclick="this.parentElement.remove()">Remover</button>
            `;
            listaPM.appendChild(novoItem);
        }
    </script>
</head>
<body>
    <!-- Botões para mostrar/ocultar os campos -->
    <div>
        <a href="?ferias=true" class="btn btn-success">Mostrar Campos Férias</a>
        <a href="?ferias=false" class="btn btn-danger">Ocultar Campos Férias</a>
    </div>

    <div class="form-container">
        <h2>Associar Pessoas à Matéria</h2>
        <form id="pmForm" method="POST">
            <!-- Campo Policial Militar com autocomplete -->
            <div class="form-group">
                <label for="pm">Policial Militar</label>
                <input type="text" id="pm" name="nome_pm" placeholder="Digite o nome do policial" oninput="autocompletePM(this.value)" required>
                <input type="hidden" id="matricula_hidden" name="matricula_hidden">
                <div id="autocomplete-results"></div>
            </div>

            <!-- Campos adicionais visíveis apenas com 'ferias=true' -->
            <?php if ($mostrarCamposFerias): ?>
                <div class="form-group">
                    <label for="data_inicio">Data de Início</label>
                    <input type="date" id="data_inicio" name="data_inicio" required>
                </div>
                <div class="form-group">
                    <label for="data_final">Data Final</label>
                    <input type="date" id="data_final" name="data_final" required>
                </div>
                <div class="form-group">
                    <label for="ano_base">Ano Base</label>
                    <input type="number" id="ano_base" name="ano_base" value="<?= date('Y'); ?>" required>
                </div>
            <?php endif; ?>

            <!-- Posto e Unidade -->
            <div class="form-group">
                <label for="pg">Posto/Graduação</label>
                <select id="pg" name="posto_graduacao" required>
                    <option value="">Selecione</option>
                    <option value="1">Soldado</option>
                    <option value="2">Cabo</option>
                    <option value="3">Sargento</option>
                </select>
            </div>
            <div class="form-group">
                <label for="unidade">Unidade</label>
                <select id="unidade" name="unidade" required>
                    <option value="">Selecione</option>
                    <option value="Unidade 1">Unidade 1</option>
                    <option value="Unidade 2">Unidade 2</option>
                </select>
            </div>

            <!-- Botões -->
            <div class="button-group">
                <button type="button" class="btn-green" onclick="adicionarPolicial()">Adicionar PM</button>
                <button type="reset" class="btn-red">Cancelar</button>
            </div>
        </form>
        
        <!-- Lista de Policiais Adicionados -->
        <div id="policialList" class="policial-list">
            <h3>Policiais Militares Adicionados</h3>
        </div>
    </div>
</body>
</html>
