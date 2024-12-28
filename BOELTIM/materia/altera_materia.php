<?php
include '../db.php';

// Função para buscar matérias para boletim
function getMateriasBoletim($pdo, $texto_materia = '', $assunto_especifico = '', $assunto_geral = '', $status = '') {
    $query = "SELECT DISTINCT mate_bole_cod, mate_bole_texto, mate_bole_data, assu_espe_descricao, assu_gera_descricao
              FROM bg.vw_materia_boletim 
              WHERE 1=1";

    if ($texto_materia) {
        $query .= " AND mate_bole_texto LIKE :texto_materia";
    }
    if ($assunto_especifico) {
        $query .= " AND assu_espe_descricao LIKE :assunto_especifico";
    }
    if ($assunto_geral) {
        $query .= " AND assu_gera_descricao LIKE :assunto_geral";
    }

    // Filtro de status
    if ($status !== '') {
        if ($status === 'cadastrada') {
            $query .= " AND mate_bole_enviada = 0 AND mate_bole_p1 = 0 AND mate_bole_refebida = 0 AND mate_bole_publicada = 0";
        } elseif ($status === 'enviada') {
            $query .= " AND mate_bole_enviada = 1 AND mate_bole_p1 = 0 AND mate_bole_refebida = 0 AND mate_bole_publicada = 0";
        } elseif ($status === 'nao_recebida') {
            $query .= " AND mate_bole_enviada = 1 AND mate_bole_p1 = 1 AND mate_bole_refebida = 0 AND mate_bole_publicada = 0";
        } elseif ($status === 'recebida') {
            $query .= " AND mate_bole_enviada = 1 AND mate_bole_p1 = 1 AND mate_bole_refebida = 1 AND mate_bole_publicada = 0";
        } elseif ($status === 'publicada') {
            $query .= " AND mate_bole_enviada = 1 AND mate_bole_p1 = 1 AND mate_bole_refebida = 1 AND mate_bole_publicada = 1";
        }
    }

    $query .= " ORDER BY mate_bole_data DESC LIMIT 10";

    try {
        $stmt = $pdo->prepare($query);

        if ($texto_materia) {
            $stmt->bindValue(':texto_materia', "%$texto_materia%");
        }
        if ($assunto_especifico) {
            $stmt->bindValue(':assunto_especifico', "%$assunto_especifico%");
        }
        if ($assunto_geral) {
            $stmt->bindValue(':assunto_geral', "%$assunto_geral%");
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erro na consulta SQL: " . $e->getMessage());
        return [];
    }
}

// Recebendo os dados do formulário de filtro
$texto_materia = isset($_POST['texto_materia']) ? $_POST['texto_materia'] : '';
$assunto_especifico = isset($_POST['assunto_especifico']) ? $_POST['assunto_especifico'] : '';
$assunto_geral = isset($_POST['assunto_geral']) ? $_POST['assunto_geral'] : '';
$status = isset($_POST['status']) ? $_POST['status'] : '';

$materias = getMateriasBoletim($pdo, $texto_materia, $assunto_especifico, $assunto_geral, $status);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consulta de Matérias para Boletim</title>
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
        .actions {
            display: flex;
            justify-content: center;
            gap: 5px;
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
        /* Estilos para o modal de exibição de texto */
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
    <div class="container">
        <h1>Consulta de Matérias para Boletim</h1>

        <!-- Filtro -->
        <form method="POST" action="consulta_materia1.php">
            <div class="form-row">
                <div class="form-group col-md-3">
                    <label for="texto_materia">Texto da Matéria</label>
                    <input type="text" class="form-control" id="texto_materia" name="texto_materia" placeholder="Texto da Matéria" value="<?php echo htmlspecialchars($texto_materia); ?>">
                </div>
                <div class="form-group col-md-3">
                    <label for="assunto_especifico">Assunto Específico</label>
                    <input type="text" class="form-control" id="assunto_especifico" name="assunto_especifico" placeholder="Assunto Específico" value="<?php echo htmlspecialchars($assunto_especifico); ?>">
                </div>
                <div class="form-group col-md-3">
                    <label for="assunto_geral">Assunto Geral</label>
                    <input type="text" class="form-control" id="assunto_geral" name="assunto_geral" placeholder="Assunto Geral" value="<?php echo htmlspecialchars($assunto_geral); ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-4">
                    <label>Status da Matéria</label><br>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="status" id="status1" value="cadastrada" <?php echo ($status === 'cadastrada') ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="status1">
                            Cadastrada
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="status" id="status2" value="enviada" <?php echo ($status === 'enviada') ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="status2">
                            Enviada
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="status" id="status3" value="nao_recebida" <?php echo ($status === 'nao_recebida') ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="status3">
                            Enviada e não recebida
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="status" id="status4" value="recebida" <?php echo ($status === 'recebida') ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="status4">
                            Recebida
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="status" id="status5" value="publicada" <?php echo ($status === 'publicada') ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="status5">
                            Publicada
                        </label>
                    </div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-2">
                    <button type="submit" class="btn btn-primary btn-block">Filtrar</button>
                </div>
                <div class="form-group col-md-2">
                    <button type="button" class="btn btn-secondary btn-block" onclick="resetForm()">Limpar</button>
                </div>
            </div>
        </form>

        <!-- Tabela de Resultados -->
        <table class="table table-bordered table-striped">
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
                            <td class="actions">
                                <a href="cad_materia.php?mate_bole_cod=<?php echo htmlspecialchars($materia['mate_bole_cod']); ?>" 
                                   class="btn btn-warning btn-sm">Alterar</a>
                                <a href="enviar_materia.php?mate_bole_cod=<?php echo htmlspecialchars($materia['mate_bole_cod']); ?>" 
                                   class="btn btn-info btn-sm" 
                                   onclick="return confirm('Tem certeza que deseja enviar esta matéria?');">Enviar</a>
                                <a href="excluir_materia.php?mate_bole_cod=<?php echo htmlspecialchars($materia['mate_bole_cod']); ?>" 
                                   class="btn btn-danger btn-sm" 
                                   onclick="return confirm('Tem certeza que deseja excluir esta matéria e os registros relacionados? Esta ação não pode ser desfeita.');">Excluir</a>
                                <button class="btn btn-primary btn-sm" onclick="openPdfModal('<?php echo htmlspecialchars($materia['mate_bole_cod']); ?>')">PDF</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center">Nenhuma matéria encontrada.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Modal para exibir o texto completo -->
    <div id="modalOverlay" class="modal-overlay"></div>

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
        function resetForm() {
            document.getElementById('texto_materia').value = '';
            document.getElementById('assunto_especifico').value = '';
            document.getElementById('assunto_geral').value = '';
            document.querySelector('input[name="status"]:checked').checked = false;
            document.forms[0].submit();
        }

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
