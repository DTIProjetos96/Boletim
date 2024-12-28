<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consulta com Marcações</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            position: relative;
        }
        /* Estilos para o botão Retornar */
        .btn-retornar {
            position: absolute;
            top: 20px;
            right: 20px;
            padding: 10px 20px;
            background-color: #007BFF; /* Cor azul */
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 16px;
        }
        .btn-retornar:hover {
            background-color: #0056b3; /* Tom mais escuro de azul */
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 220px; /* Espaço para os filtros e botão Retornar */
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .filter-container {
            margin-bottom: 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
        }
        .filter-container h3 {
            width: 100%;
            margin-bottom: 10px;
        }
        .filter-item {
            display: flex;
            align-items: center;
            margin-right: 15px;
        }
        /* Ajustes específicos para o container de datas */
        #filters {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        /* Ajustes específicos para o container de unidades */
        #unit-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .btn-adicionar {
            margin-top: 10px;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
            font-size: 16px;
            border-radius: 4px;
        }
        .btn-adicionar:hover {
            background-color: #45a049;
        }
        .message {
            margin-top: 20px;
            padding: 10px;
            border-radius: 5px;
            display: none;
        }
        .message.success {
            background-color: #d4edda;
            color: #155724;
        }
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
        }
        /* Responsividade para a tabela */
        @media (max-width: 600px) {
            table, thead, tbody, th, td, tr {
                display: block;
            }
            thead tr {
                display: none;
            }
            tr {
                margin-bottom: 15px;
            }
            td {
                padding-left: 50%;
                position: relative;
            }
            td::before {
                content: attr(data-label);
                position: absolute;
                left: 0;
                width: 45%;
                padding-left: 15px;
                font-weight: bold;
            }
        }

        /* Estilos para o overlay de carregamento */
        .overlay {
            position: fixed; /* Fica fixo na tela */
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5); /* Fundo semi-transparente */
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000; /* Garante que o overlay fique acima de outros elementos */
            display: none; /* Inicialmente oculto */
        }

        .overlay.active {
            display: flex;
        }

        .overlay .loader {
            background: white;
            padding: 20px 40px;
            border-radius: 8px;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Opcional: Adicionar um spinner */
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>

<!-- Overlay de Carregamento -->
<div id="loadingOverlay" class="overlay" aria-live="assertive" aria-busy="true">
    <div class="loader">
        <div class="spinner"></div>
        <span>Matérias sendo cadastradas...</span>
    </div>
</div>

<!-- Botão "Retornar" no canto superior -->
<a href="add_materia_boletim.php?bole_cod=<?php echo htmlspecialchars($bole_cod, ENT_QUOTES, 'UTF-8'); ?>" class="btn-retornar">Retornar</a>

<?php
// Ativar exibição de erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../db.php'; // Verifique se o caminho está correto

// Obter o código do boletim, se necessário
$bole_cod = isset($_GET['bole_cod']) ? intval($_GET['bole_cod']) : 0;
//$bole_cod =68;

if ($bole_cod <= 0) {
    echo "<p>Código de boletim inválido.</p>";
    exit;
}

// Consulta ao banco de dados
$query = "
    SELECT 
        mb.mate_bole_cod, 
        mb.mate_bole_texto, 
        TO_CHAR(mb.mate_bole_data, 'DD/MM/YYYY') AS mate_bole_data, 
        c.coma_sigla AS comando 
    FROM 
        bg.materia_boletim mb
    LEFT JOIN bg.materia_publicacao mp ON mb.mate_bole_cod = mp.fk_mate_bole_cod
    INNER JOIN public.subunidades su ON su.subu_cod = mb.fk_subu_cod
    INNER JOIN public.unidades u ON u.unid_cod = su.fk_unid_cod
    INNER JOIN public.comandos c ON u.fk_coma_cod = c.coma_cod
    WHERE 
        mp.fk_mate_bole_cod IS NULL";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "<p>Erro na consulta ao banco de dados: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// Debugging para verificar os registros
if (empty($registros)) {
    echo "<p>Nenhum registro encontrado.</p>";
} else {
    echo "<script>const registros = " . json_encode($registros, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ";</script>";
}
?>

<h1>Consulta com Marcações</h1>

<!-- Filtro por Período de Datas -->
<div class="filter-container">
    <h3>Filtrar por Período:</h3>
    <input type="date" id="startDate">
    <input type="date" id="endDate">
    <button id="btnFiltrar" class="btn-adicionar">Filtrar</button>
    <button id="btnLimparFiltro" class="btn-adicionar">Limpar Filtro</button>
</div>

<!-- Filtros Existentes -->
<div class="filter-container">
    <h3>Marcar por Data:</h3>
    <div id="filters"></div>
</div>
<div class="filter-container">
    <h3>Marcar por Unidade:</h3>
    <div id="unit-filters"></div>
</div>

<!-- Botão "Adicionar Selecionados" -->
<button id="btnAdicionarSelecionados" class="btn-adicionar">Adicionar Selecionados</button>

<table>
    <thead>
        <tr>
            <th><input type="checkbox" id="selectAll"></th>
            <th>Texto</th>
            <th>Data</th>
            <th>Unidade</th>
        </tr>
    </thead>
    <tbody id="consulta">
        <!-- Linhas serão geradas dinamicamente -->
    </tbody>
</table>

<!-- Área para exibir mensagens de sucesso ou erro -->
<div id="mensagem" class="message" role="alert"></div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Verificar se os registros estão carregados
        console.log('Registros recebidos no JavaScript:', registros);

        if (typeof registros !== 'undefined' && registros.length > 0) {
            let filteredRegistros = [...registros]; // Inicialmente, todos os registros
            const consulta = document.getElementById('consulta');
            const filtersContainer = document.getElementById('filters');
            const unitFiltersContainer = document.getElementById('unit-filters');
            const selectAllCheckbox = document.getElementById('selectAll');
            const btnAdicionarSelecionados = document.getElementById('btnAdicionarSelecionados');
            const mensagemDiv = document.getElementById('mensagem');
            const loadingOverlay = document.getElementById('loadingOverlay'); // Seleciona o overlay
            const btnFiltrar = document.getElementById('btnFiltrar');
            const btnLimparFiltro = document.getElementById('btnLimparFiltro');
            const startDateInput = document.getElementById('startDate');
            const endDateInput = document.getElementById('endDate');

            // Função para parsear data no formato DD/MM/YYYY para objeto Date
            const parseDate = (str) => {
                const [day, month, year] = str.split('/');
                return new Date(`${year}-${month}-${day}`);
            };

            // Função para formatar data no formato DD/MM/YYYY
            const formatDate = (date) => {
                const day = String(date.getDate()).padStart(2, '0');
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const year = date.getFullYear();
                return `${day}/${month}/${year}`;
            };

            // Função para agrupar registros por uma chave específica
            const groupBy = (records, key) => {
                return records.reduce((acc, record) => {
                    acc[record[key]] = acc[record[key]] || [];
                    acc[record[key]].push(record);
                    return acc;
                }, {});
            };

            // Função para renderizar filtros de data
            const renderDateFilters = (data) => {
                filtersContainer.innerHTML = ''; // Limpar filtros existentes
                const groupedData = groupBy(data, 'mate_bole_data');

                Object.keys(groupedData).forEach(date => {
                    const count = groupedData[date].length; // Obter a contagem
                    const filter = document.createElement('div');
                    filter.classList.add('filter-item');
                    filter.innerHTML = `
                        <input type="checkbox" id="filter-${date}" class="filter-checkbox" data-date="${date}">
                        <label for="filter-${date}">${date} (${count})</label>
                    `;
                    filtersContainer.appendChild(filter);
                });

                // Re-adicionar os eventos aos novos filtros de data
                const filterCheckboxes = document.querySelectorAll('.filter-checkbox');
                const rowCheckboxes = document.querySelectorAll('.row-checkbox');

                filterCheckboxes.forEach(filterCheckbox => {
                    filterCheckbox.addEventListener('change', (event) => {
                        const date = event.target.getAttribute('data-date');
                        const isChecked = event.target.checked;

                        rowCheckboxes.forEach(rowCheckbox => {
                            if (rowCheckbox.getAttribute('data-date') === date) {
                                rowCheckbox.checked = isChecked;
                            }
                        });

                        // Atualizar o checkbox "Selecionar Todos" se necessário
                        atualizarSelecionarTodos();
                    });
                });
            };

            // Função para renderizar filtros de unidade
            const renderUnitFilters = (data) => {
                unitFiltersContainer.innerHTML = ''; // Limpar filtros existentes
                const groupedUnits = groupBy(data, 'comando');

                Object.keys(groupedUnits).forEach(unit => {
                    const count = groupedUnits[unit].length; // Obter a contagem
                    const filter = document.createElement('div');
                    filter.classList.add('filter-item');
                    filter.innerHTML = `
                        <input type="checkbox" id="filter-${unit}" class="unit-checkbox" data-unit="${unit}">
                        <label for="filter-${unit}">${unit} (${count})</label>
                    `;
                    unitFiltersContainer.appendChild(filter);
                });

                // Re-adicionar os eventos aos novos filtros de unidade
                const unitCheckboxes = document.querySelectorAll('.unit-checkbox');
                const rowCheckboxes = document.querySelectorAll('.row-checkbox');

                unitCheckboxes.forEach(unitCheckbox => {
                    unitCheckbox.addEventListener('change', (event) => {
                        const unit = event.target.getAttribute('data-unit');
                        const isChecked = event.target.checked;

                        rowCheckboxes.forEach(rowCheckbox => {
                            if (rowCheckbox.getAttribute('data-unit') === unit) {
                                rowCheckbox.checked = isChecked;
                            }
                        });

                        // Atualizar o checkbox "Selecionar Todos" se necessário
                        atualizarSelecionarTodos();
                    });
                });
            };

            // Função para renderizar a tabela
            const renderTable = (data) => {
                consulta.innerHTML = ''; // Limpar tabela existente

                data.forEach((registro, index) => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td><input type="checkbox" class="row-checkbox" name="materias[]" value="${registro.mate_bole_cod}" data-date="${registro.mate_bole_data}" data-unit="${registro.comando}" id="row-${index}"></td>
                        <td data-label="Texto">${registro.mate_bole_texto}</td>
                        <td data-label="Data">${registro.mate_bole_data}</td>
                        <td data-label="Unidade">${registro.comando}</td>
                    `;
                    consulta.appendChild(row);
                });

                // Re-adicionar os eventos aos novos checkboxes de linha
                const rowCheckboxes = document.querySelectorAll('.row-checkbox');

                rowCheckboxes.forEach(rowCheckbox => {
                    rowCheckbox.addEventListener('change', () => {
                        const date = rowCheckbox.getAttribute('data-date');
                        const unit = rowCheckbox.getAttribute('data-unit');

                        const relatedDateCheckboxes = Array.from(rowCheckboxes).filter(rc => rc.getAttribute('data-date') === date);
                        const relatedUnitCheckboxes = Array.from(rowCheckboxes).filter(rc => rc.getAttribute('data-unit') === unit);

                        const allDateChecked = relatedDateCheckboxes.every(rc => rc.checked);
                        const allUnitChecked = relatedUnitCheckboxes.every(rc => rc.checked);

                        const dateFilterCheckbox = document.querySelector(`.filter-checkbox[data-date="${date}"]`);
                        const unitFilterCheckbox = document.querySelector(`.unit-checkbox[data-unit="${unit}"]`);

                        if (dateFilterCheckbox) {
                            dateFilterCheckbox.checked = allDateChecked;
                        }
                        if (unitFilterCheckbox) {
                            unitFilterCheckbox.checked = allUnitChecked;
                        }

                        // Atualizar o checkbox "Selecionar Todos" se necessário
                        atualizarSelecionarTodos();
                    });
                });

                // Atualizar o estado dos checkboxes de filtro com base nas seleções individuais
                atualizarSelecionarTodos();
            };

            // Função para atualizar o checkbox "Selecionar Todos"
            const atualizarSelecionarTodos = () => {
                const rowCheckboxes = document.querySelectorAll('.row-checkbox');
                const total = rowCheckboxes.length;
                const checked = Array.from(rowCheckboxes).filter(cb => cb.checked).length;
                selectAllCheckbox.checked = total > 0 && checked === total;
            };

            // Função para exibir mensagens
            const exibirMensagem = (tipo, texto) => {
                mensagemDiv.className = `message ${tipo}`;
                mensagemDiv.textContent = texto;
                mensagemDiv.style.display = 'block';
                // Ocultar a mensagem após 5 segundos
                setTimeout(() => {
                    mensagemDiv.style.display = 'none';
                }, 5000);
            };

            // Função para mostrar o overlay de carregamento
            const mostrarLoading = () => {
                loadingOverlay.classList.add('active');
            };

            // Função para esconder o overlay de carregamento
            const esconderLoading = () => {
                loadingOverlay.classList.remove('active');
            };

            // Função para aplicar o filtro por período de datas
            const aplicarFiltroPorPeriodo = () => {
                const startDateValue = startDateInput.value;
                const endDateValue = endDateInput.value;

                if (!startDateValue || !endDateValue) {
                    exibirMensagem('error', 'Por favor, selecione ambas as datas de início e fim.');
                    return;
                }

                const startDate = new Date(startDateValue);
                const endDate = new Date(endDateValue);

                if (startDate > endDate) {
                    exibirMensagem('error', 'A data de início não pode ser posterior à data de fim.');
                    return;
                }

                // Filtrar os registros dentro do período
                filteredRegistros = registros.filter(registro => {
                    const registroDate = parseDate(registro.mate_bole_data);
                    return registroDate >= startDate && registroDate <= endDate;
                });

                renderTable(filteredRegistros);
                renderDateFilters(filteredRegistros);
                renderUnitFilters(filteredRegistros);
            };

            // Função para limpar o filtro por período de datas
            const limparFiltroPorPeriodo = () => {
                startDateInput.value = '';
                endDateInput.value = '';
                filteredRegistros = [...registros];
                renderTable(filteredRegistros);
                renderDateFilters(filteredRegistros);
                renderUnitFilters(filteredRegistros);
            };

            // Evento para o botão "Filtrar"
            btnFiltrar.addEventListener('click', aplicarFiltroPorPeriodo);

            // Evento para o botão "Limpar Filtro"
            btnLimparFiltro.addEventListener('click', limparFiltroPorPeriodo);

            // Renderizar os filtros e a tabela inicialmente
            renderDateFilters(filteredRegistros);
            renderUnitFilters(filteredRegistros);
            renderTable(filteredRegistros);

            // Evento para o botão "Adicionar Selecionados"
            btnAdicionarSelecionados.addEventListener('click', () => {
                const rowCheckboxes = document.querySelectorAll('.row-checkbox');
                const selecionados = Array.from(rowCheckboxes)
                    .filter(cb => cb.checked)
                    .map(cb => cb.value);

                if (selecionados.length === 0) {
                    exibirMensagem('error', 'Nenhuma matéria selecionada.');
                    return;
                }

                // Exibir o overlay de carregamento
                mostrarLoading();

                // Registrar o tempo de início
                const startTime = Date.now();

                // Enviar os selecionados via AJAX para adicionar_materias.php
                const formData = new FormData();
                formData.append('bole_cod', <?php echo htmlspecialchars($bole_cod, ENT_QUOTES, 'UTF-8'); ?>);
                selecionados.forEach(mate_bole_cod => {
                    formData.append('materias[]', mate_bole_cod);
                });

                fetch('adicionar_materias.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    // Calcular o tempo decorrido
                    const elapsed = Date.now() - startTime;
                    const remaining = 3000 - elapsed;

                    if (remaining > 0) {
                        // Esperar o tempo restante para completar 3 segundos
                        setTimeout(() => {
                            // Esconder o overlay após completar 3 segundos
                            esconderLoading();

                            if (data.status === 'success') {
                                exibirMensagem('success', data.message);
                                // Redirecionar para add_materia_boletim.php após 2 segundos
                                setTimeout(() => {
                                    window.location.href = 'add_materia_boletim.php?bole_cod=<?php echo htmlspecialchars($bole_cod, ENT_QUOTES, 'UTF-8'); ?>';
                                }, 2000);
                            } else {
                                exibirMensagem('error', data.message);
                            }
                        }, remaining);
                    } else {
                        // Se já passaram mais de 3 segundos, esconder imediatamente
                        esconderLoading();

                        if (data.status === 'success') {
                            exibirMensagem('success', data.message);
                            // Redirecionar para add_materia_boletim.php após 2 segundos
                            setTimeout(() => {
                                window.location.href = 'add_materia_boletim.php?bole_cod=<?php echo htmlspecialchars($bole_cod, ENT_QUOTES, 'UTF-8'); ?>';
                            }, 2000);
                        } else {
                            exibirMensagem('error', data.message);
                        }
                    }
                })
                .catch(error => {
                    // Calcular o tempo decorrido
                    const elapsed = Date.now() - startTime;
                    const remaining = 3000 - elapsed;

                    if (remaining > 0) {
                        // Esperar o tempo restante para completar 3 segundos
                        setTimeout(() => {
                            // Esconder o overlay após completar 3 segundos
                            esconderLoading();
                            console.error('Erro:', error);
                            exibirMensagem('error', 'Ocorreu um erro ao adicionar as matérias.');
                        }, remaining);
                    } else {
                        // Se já passaram mais de 3 segundos, esconder imediatamente
                        esconderLoading();
                        console.error('Erro:', error);
                        exibirMensagem('error', 'Ocorreu um erro ao adicionar as matérias.');
                    }
                });
            });

            // Evento para o checkbox "Selecionar Todos"
            selectAllCheckbox.addEventListener('change', (event) => {
                const isChecked = event.target.checked;
                const rowCheckboxes = document.querySelectorAll('.row-checkbox');
                rowCheckboxes.forEach(rowCheckbox => {
                    rowCheckbox.checked = isChecked;
                });
            });
        } else {
            console.log('Nenhum registro disponível ou registros não definidos.');
            document.getElementById('consulta').innerHTML = '<tr><td colspan="4">Nenhum registro disponível</td></tr>';
        }
    });
</script>

</body>
</html>
