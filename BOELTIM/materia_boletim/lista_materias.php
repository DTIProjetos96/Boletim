<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consulta com Filtro</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
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
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }
    </style>
</head>
<body>

<?php
// Ativar exibição de erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../db.php'; // Verifique se o caminho está correto

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

<h1>Consulta com Filtro</h1>

<div class="filter-container">
    <h3>Filtrar por Data:</h3>
    <div id="filters"></div>
</div>
<div class="filter-container">
    <h3>Filtrar por Unidade:</h3>
    <div id="unit-filters"></div>
</div>

<table>
    <thead>
        <tr>
            <th>Selecionar</th>
            <th>Texto</th>
            <th>Data</th>
            <th>Unidade</th>
        </tr>
    </thead>
    <tbody id="consulta">
        <!-- Linhas serão geradas dinamicamente -->
    </tbody>
</table>

<script>
    // Verificar se os registros estão carregados
    console.log('Registros recebidos no JavaScript:', registros);

    if (typeof registros !== 'undefined' && registros.length > 0) {
        const consulta = document.getElementById('consulta');
        const filtersContainer = document.getElementById('filters');
        const unitFiltersContainer = document.getElementById('unit-filters');

        // Agrupar registros por data e unidade
        const groupBy = (records, key) => {
            return records.reduce((acc, record) => {
                acc[record[key]] = acc[record[key]] || [];
                acc[record[key]].push(record);
                return acc;
            }, {});
        };

        const groupedData = groupBy(registros, 'mate_bole_data');
        const groupedUnits = groupBy(registros, 'comando');

        // Renderizar filtros por data
        Object.keys(groupedData).forEach(date => {
            const filter = document.createElement('div');
            filter.innerHTML = `
                <input type="checkbox" id="filter-${date}" class="filter-checkbox" data-date="${date}">
                <label for="filter-${date}">${date}</label>
            `;
            filtersContainer.appendChild(filter);
        });

        // Renderizar filtros por unidade
        Object.keys(groupedUnits).forEach(unit => {
            const filter = document.createElement('div');
            filter.innerHTML = `
                <input type="checkbox" id="filter-${unit}" class="unit-checkbox" data-unit="${unit}">
                <label for="filter-${unit}">${unit}</label>
            `;
            unitFiltersContainer.appendChild(filter);
        });

        // Renderizar registros na tabela
        registros.forEach((registro, index) => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td><input type="checkbox" class="row-checkbox" data-date="${registro.mate_bole_data}" data-unit="${registro.comando}" id="row-${index}"></td>
                <td>${registro.mate_bole_texto}</td>
                <td>${registro.mate_bole_data}</td>
                <td>${registro.comando}</td>
            `;
            consulta.appendChild(row);
        });

        // Adicionar eventos aos filtros
        const filterCheckboxes = document.querySelectorAll('.filter-checkbox');
        const unitCheckboxes = document.querySelectorAll('.unit-checkbox');
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
            });
        });

        unitCheckboxes.forEach(unitCheckbox => {
            unitCheckbox.addEventListener('change', (event) => {
                const unit = event.target.getAttribute('data-unit');
                const isChecked = event.target.checked;

                rowCheckboxes.forEach(rowCheckbox => {
                    if (rowCheckbox.getAttribute('data-unit') === unit) {
                        rowCheckbox.checked = isChecked;
                    }
                });
            });
        });

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
            });
        });
    } else {
        console.log('Nenhum registro disponível ou registros não definidos.');
        document.getElementById('consulta').innerHTML = '<tr><td colspan="4">Nenhum registro disponível</td></tr>';
    }
</script>

</body>
</html>
