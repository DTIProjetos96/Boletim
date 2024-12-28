<?php
include '../db.php';



// Função para buscar matérias recebidas
function getMateriasRecebidas($pdo, $dataInicio = '', $dataFim = '', $assunto_especifico = '') {
    $query = "SELECT mate_bole_cod, mate_bole_texto, mate_bole_data, assu_espe_descricao, assu_gera_descricao
              FROM bg.vw_materias_boletins 
              WHERE mate_bole_enviada = 1 AND mate_bole_recebida = 0";

    // Adiciona filtro por data de início e fim
    if ($dataInicio && $dataFim) {
        $query .= " AND mate_bole_data BETWEEN :dataInicio AND :dataFim";
    }

    // Adiciona filtro por assunto específico
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

// Função para devolver a nota
function devolverNota($pdo, $mate_bole_cod) {
    try {
        // Atualiza mate_bole_enviada para 0
        $stmt = $pdo->prepare("UPDATE bg.materia_boletim SET mate_bole_enviada = 0 WHERE mate_bole_cod = :mate_bole_cod");
        $stmt->execute([':mate_bole_cod' => $mate_bole_cod]);

        if ($stmt->rowCount() > 0) {
            echo "<script>alert('Nota devolvida com sucesso!'); window.location.href = 'nb_recebida.php';</script>";
        } else {
            echo "<script>alert('A nota não pôde ser devolvida. Verifique se a matéria está cadastrada corretamente.');</script>";
        }
    } catch (PDOException $e) {
        echo "<script>alert('Erro ao devolver a nota: " . htmlspecialchars($e->getMessage()) . "');</script>";
    }
}

// Verifica se uma ação foi solicitada
if (isset($_GET['action']) && isset($_GET['mate_bole_cod'])) {
    $mate_bole_cod = (int)$_GET['mate_bole_cod'];
    if ($_GET['action'] === 'receber') {
        receberNota($pdo, $mate_bole_cod);
    } elseif ($_GET['action'] === 'devolver') {
        devolverNota($pdo, $mate_bole_cod);
    }
}

// Função para buscar todos os assuntos específicos
function getAssuntosEspecificos($pdo) {
    $query = "SELECT assu_espe_cod, assu_espe_descricao 
              FROM bg.assunto_especifico 
              ORDER BY assu_espe_descricao";

    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erro na consulta de assuntos específicos: " . $e->getMessage());
        return [];
    }
}

// Função para marcar a nota como recebida
function receberNota($pdo, $mate_bole_cod) {
    try {
        $stmt = $pdo->prepare("UPDATE bg.materia_boletim SET mate_bole_recebida = 1 WHERE mate_bole_cod = :mate_bole_cod AND mate_bole_p1 = 1 AND mate_bole_enviada = 1");
        $stmt->execute(['mate_bole_cod' => $mate_bole_cod]);

        if ($stmt->rowCount() > 0) {
            echo "<script>alert('Nota recebida com sucesso!'); window.location.href = 'nb_recebida.php';</script>";
        } else {
            echo "<script>alert('A nota não pôde ser recebida. Verifique se a matéria foi enviada e está cadastrada corretamente.');</script>";
        }
    } catch (PDOException $e) {
        echo "<script>alert('Erro ao receber a nota: " . htmlspecialchars($e->getMessage()) . "');</script>";
    }
}

// Verifica se uma ação de receber nota foi solicitada
if (isset($_GET['action']) && $_GET['action'] === 'receber' && isset($_GET['mate_bole_cod'])) {
    receberNota($pdo, (int)$_GET['mate_bole_cod']);
}

// Recebendo os dados do formulário de filtro
$dataInicio = isset($_POST['dataInicio']) ? $_POST['dataInicio'] : '';
$dataFim = isset($_POST['dataFim']) ? $_POST['dataFim'] : '';
$assunto_especifico = isset($_POST['assunto_especifico']) ? $_POST['assunto_especifico'] : '';

$materias = getMateriasRecebidas($pdo, $dataInicio, $dataFim, $assunto_especifico);
$assuntosEspecificos = getAssuntosEspecificos($pdo);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receber matéria</title>
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
        .btn-secondary {
            background-color: #6c757d;
            border-color: #6c757d;
        }
        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
        }
        .form-check-input:checked {
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
        .modal-overlay {
            position: absolute;
            background-color: white;
            padding: 10px;
            border-radius: 5px;
            width: 400px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.25);
            z-index: 1000;
            display: none;
        }
        .expanded-text {
            white-space: normal;
            max-width: none;
        }

        /* Modal para exibir o PDF */
        #pdfModal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: none;
            justify-content: center;
            align-items: center;
        }
        #pdfModal .modal-content {
            width: 80%;
            height: 80%;
            background-color: #fff;
            border-radius: 5px;
            padding: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.3);
            position: relative;
        }
        #pdfModal iframe {
            width: 100%;
            height: 100%;
            border: none;
            border-radius: 5px;
        }
        #pdfModal .close-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: #dc3545;
            color: #fff;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
        }
    </style>
</head>
<body>
<div class="container border p-4 rounded shadow" style="margin-left: 40px; max-width: 95%;">
        <h1>Receber matéria</h1>

        <!-- Filtro -->
        <form method="POST" action="nb_recebida.php">
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
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($materias): ?>
                    <?php foreach ($materias as $materia): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($materia['mate_bole_cod']); ?></td>
                            <td>
                                <span class="expand-toggle" onclick="toggleExpand(this)">+</span>
                                <div class="text-preview" onmouseover="showModal(this)" onmouseout="hideModal()" data-full-text="<?php echo htmlspecialchars_decode($materia['mate_bole_texto']); ?>">
                                    <?php echo htmlspecialchars_decode(mb_strimwidth($materia['mate_bole_texto'], 0, 20, '...')); ?>
                                </div>
                            </td>
                            <td><?php echo date('d-m-Y', strtotime($materia['mate_bole_data'])); ?></td>
                            <td><?php echo htmlspecialchars($materia['assu_espe_descricao']); ?></td>
                            <td><?php echo htmlspecialchars($materia['assu_gera_descricao']); ?></td>
                            <td>
                                <button class="btn btn-primary btn-sm" onclick="openPdfModal('<?php echo htmlspecialchars($materia['mate_bole_cod']); ?>')">PDF</button>
                                <a href="nb_recebida.php?action=receber&mate_bole_cod=<?php echo htmlspecialchars($materia['mate_bole_cod']); ?>" class="btn btn-success btn-sm">Receber Nota</a>
                                <a href="nb_recebida.php?action=devolver&mate_bole_cod=<?php echo htmlspecialchars($materia['mate_bole_cod']); ?>" 
   class="btn btn-danger btn-sm"
   onclick="return confirm('Você tem certeza que deseja devolver esta nota?');">Devolver Nota</a>

                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center">Nenhuma matéria recebida encontrada.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Modal para exibir o PDF -->
    <div id="pdfModal">
        <div class="modal-content">
            <button class="close-btn" onclick="closePdfModal()">Fechar</button>
            <iframe id="pdfFrame" src=""></iframe>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        function showModal(element) {
            var fullText = element.getAttribute('data-full-text');
            var modalOverlay = document.getElementById('modalOverlay');
            var rect = element.getBoundingClientRect();
            modalOverlay.innerHTML = fullText;
            modalOverlay.style.top = (rect.top + window.scrollY - modalOverlay.offsetHeight - 10) + 'px';
            modalOverlay.style.left = (rect.left + window.scrollX) + 'px';
            modalOverlay.style.display = 'block';
        }

        function hideModal() {
            var modalOverlay = document.getElementById('modalOverlay');
            modalOverlay.style.display = 'none';
        }

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

        function openPdfModal(mate_bole_cod) {
            var pdfModal = document.getElementById('pdfModal');
            var pdfFrame = document.getElementById('pdfFrame');
            pdfFrame.src = '/boletim/pdf/materia_pdf.php?mate_bole_cod=' + mate_bole_cod;
            pdfModal.style.display = 'flex';
        }

        function closePdfModal() {
            var pdfModal = document.getElementById('pdfModal');
            pdfModal.style.display = 'none';
            var pdfFrame = document.getElementById('pdfFrame');
            pdfFrame.src = ''; // Limpar o src para parar o carregamento do PDF
        }
    </script>
</body>
</html>
