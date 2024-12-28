<?php
include '../db.php';

function getNotasRecebidas($pdo, $dataInicio = '', $dataFim = '', $assunto_especifico = '') {
    $query = "SELECT mate_bole_cod, mate_bole_texto, mate_bole_data, assu_espe_descricao, assu_gera_descricao
              FROM bg.vw_materias_boletins 
              WHERE mate_bole_enviada = 1 AND mate_bole_recebida = 1";

    if ($dataInicio && $dataFim) {
        $query .= " AND mate_bole_data BETWEEN :dataInicio AND :dataFim";
    }

    if ($assunto_especifico) {
        $query .= " AND assu_espe_cod = :assunto_especifico";
    }

    $query .= " ORDER BY mate_bole_data DESC";

    try {
        $stmt = $pdo->prepare($query);

        if ($dataInicio && $dataFim) {
            $stmt->bindValue(':dataInicio', $dataInicio);
            $stmt->bindValue(':dataFim', $dataFim);
        }
        if ($assunto_especifico) {
            $stmt->bindValue(':assunto_especifico', $assunto_especifico);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erro na consulta SQL: " . $e->getMessage());
        return [];
    }
}

// Recebendo os dados do formulário de filtro
$dataInicio = isset($_POST['dataInicio']) ? $_POST['dataInicio'] : '';
$dataFim = isset($_POST['dataFim']) ? $_POST['dataFim'] : '';
$assunto_especifico = isset($_POST['assunto_especifico']) ? $_POST['assunto_especifico'] : '';

$notas = getNotasRecebidas($pdo, $dataInicio, $dataFim, $assunto_especifico);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notas Recebidas</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .container {
            padding-top: 20px;
        }
        h1 {
            margin-bottom: 20px;
            font-size: 24px;
            color: #007bff;
        }
        table {
            width: 100%;
            margin-top: 20px;
        }
        thead th {
            background-color: #007bff;
            color: #fff;
            padding: 12px;
            text-align: center;
            white-space: nowrap;
        }
        tbody td {
            padding: 10px;
            text-align: center;
            white-space: nowrap;
            position: relative;
        }
        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
        }
        .text-preview {
            display: inline-block;
            max-width: 150px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            cursor: pointer;
            padding-left: 20px;
        }
        .expand-toggle {
            position: absolute;
            top: 50%;
            left: 5px;
            transform: translateY(-50%);
            font-weight: bold;
            font-size: 14px;
            cursor: pointer;
            z-index: 10;
            color: green;
        }
        .expanded-text .expand-toggle {
            color: red;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Matérias recebidas</h1>

        <!-- Filtro -->
        <form method="POST" action="notas_recebidas.php">
            <div class="form-row">
                <div class="form-group col-md-3">
                    <label for="dataInicio">Data Início</label>
                    <input type="date" class="form-control" id="dataInicio" name="dataInicio" value="<?php echo htmlspecialchars($dataInicio); ?>">
                </div>
                <div class="form-group col-md-3">
                    <label for="dataFim">Data Fim</label>
                    <input type="date" class="form-control" id="dataFim" name="dataFim" value="<?php echo htmlspecialchars($dataFim); ?>">
                </div>
                <div class="form-group col-md-3">
                    <label for="assunto_especifico">Assunto Específico</label>
                    <select class="form-control" id="assunto_especifico" name="assunto_especifico">
                        <option value="">Selecione</option>
                        <?php foreach ($assuntosEspecificos as $assunto): ?>
                            <option value="<?php echo htmlspecialchars($assunto['assu_espe_cod']); ?>" <?php echo ($assunto_especifico == $assunto['assu_espe_cod']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($assunto['assu_espe_descricao']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group col-md-2 align-self-end">
                    <button type="submit" class="btn btn-primary btn-block">Filtrar</button>
                </div>
            </div>
        </form>

        <!-- Tabela de Resultados -->
        <table class="table table-bordered table-striped mt-4">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Texto da Matéria</th>
                    <th>Data</th>
                    <th>Assunto Específico</th>
                    <th>Assunto Geral</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($notas): ?>
                    <?php foreach ($notas as $nota): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($nota['mate_bole_cod']); ?></td>
                            <td>
                                <span class="expand-toggle" onclick="toggleExpand(this)">+</span>
                                <div class="text-preview" onmouseover="showModal(this)" onmouseout="hideModal()" data-full-text="<?php echo htmlspecialchars_decode($nota['mate_bole_texto']); ?>">
                                    <?php echo htmlspecialchars_decode(mb_strimwidth($nota['mate_bole_texto'], 0, 20, '...')); ?>
                                </div>
                            </td>
                            <td><?php echo date('d-m-Y', strtotime($nota['mate_bole_data'])); ?></td>
                            <td><?php echo htmlspecialchars($nota['assu_espe_descricao']); ?></td>
                            <td><?php echo htmlspecialchars($nota['assu_gera_descricao']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center">Nenhuma nota recebida encontrada.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        function toggleExpand(toggleElement) {
            var isExpanded = toggleElement.textContent === '-';
            var textPreview = toggleElement.nextElementSibling;

            document.querySelectorAll('.text-preview').forEach(function(el) {
                var toggle = el.previousElementSibling;
                el.classList.remove('expanded-text');
                toggle.textContent = '+';
                toggle.style.color = 'green';
                el.innerHTML = el.getAttribute('data-full-text').substring(0, 20) + '...';
            });

            if (!isExpanded) {
                textPreview.classList.add('expanded-text');
                toggleElement.textContent = '-';
                toggleElement.style.color = 'red';
                textPreview.innerHTML = textPreview.getAttribute('data-full-text');
            }
        }
    </script>
</body>
</html>
