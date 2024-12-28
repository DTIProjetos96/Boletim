<?php include '../valida_session.php'; ?>
<?php

include '../db.php';



// Função para buscar matérias enviadas com filtros

function getMateriasEnviadas($pdo, $dataInicio = '', $dataFim = '', $assunto_especifico = '') {

    $query = "SELECT mate_bole_cod, mate_bole_texto, mate_bole_data, assu_espe_descricao, assu_gera_descricao

              FROM bg.vw_materias_boletins

              WHERE mate_bole_p1 = 1 AND mate_bole_enviada = 1";



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



// Recebendo os dados do formulário de filtro

$dataInicio = isset($_GET['dataInicio']) ? $_GET['dataInicio'] : '';

$dataFim = isset($_GET['dataFim']) ? $_GET['dataFim'] : '';

$assunto_especifico = isset($_GET['assunto_especifico']) ? $_GET['assunto_especifico'] : '';



// Obtém todos os registros

$materias = getMateriasEnviadas($pdo, $dataInicio, $dataFim, $assunto_especifico);



// Configurações de paginação

$registrosPorPagina = 50;

$totalRegistros = count($materias);

$totalPaginas = ceil($totalRegistros / $registrosPorPagina);



// Página atual

$paginaAtual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;

$inicio = ($paginaAtual - 1) * $registrosPorPagina;



// Pega os registros para a página atual

$materiasPagina = array_slice($materias, $inicio, $registrosPorPagina);



// Obtém todos os assuntos específicos

$assuntosEspecificos = getAssuntosEspecificos($pdo);

?>



<!DOCTYPE html>

<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Matérias Enviadas</title>

    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">

    <style>

        .container { padding-top: 20px; }

        h1 { margin-bottom: 20px; font-size: 24px; color: #007bff; }

        table { width: 100%; margin-top: 20px; }

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

        .btn-primary { background-color: #007bff; border-color: #007bff; }

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

        .expanded-text { white-space: normal; max-width: none; }
        
        .page-container {
    border: 1px solid #000; /* Cor preta da borda */
    border-radius: 8px; /* Cantos arredondados */
    padding: 20px; /* Espaçamento interno */
    margin: 20px auto; /* Espaçamento externo e centralização */
    background-color: #fff; /* Fundo branco para destacar */
    box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1); /* Sombra leve para destaque */
    max-width: 1200px; /* Largura máxima */
}


    </style>

</head>

<body>

<div class="page-container">
    <div class="container">

        <h1>Matérias Enviadas</h1>



        <!-- Filtro -->

        <form method="GET" action="materia_enviada.php">

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

                <?php if ($materiasPagina): ?>

                    <?php foreach ($materiasPagina as $materia): ?>

                        <tr>

                            <td><?php echo htmlspecialchars($materia['mate_bole_cod']); ?></td>

                            <td>

                                <span class="expand-toggle" onclick="toggleExpand(this)">+</span>

                                <div class="text-preview" data-full-text="<?php echo htmlspecialchars_decode($materia['mate_bole_texto']); ?>">

                                    <?php echo htmlspecialchars_decode(mb_strimwidth($materia['mate_bole_texto'], 0, 20, '...')); ?>

                                </div>

                            </td>

                            <td><?php echo date('d-m-Y', strtotime($materia['mate_bole_data'])); ?></td>

                            <td><?php echo htmlspecialchars($materia['assu_espe_descricao']); ?></td>

                            <td><?php echo htmlspecialchars($materia['assu_gera_descricao']); ?></td>

                            <td>

                                <button class="btn btn-primary btn-sm" onclick="openPdfModal('<?php echo htmlspecialchars($materia['mate_bole_cod']); ?>')">PDF</button>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>

                        <td colspan="6" class="text-center">Nenhuma matéria enviada encontrada.</td>

                    </tr>

                <?php endif; ?>

            </tbody>

        </table>



        <!-- Navegação de Paginação -->

        <nav aria-label="Navegação de página">

            <ul class="pagination justify-content-center mt-4">

                <?php if ($paginaAtual > 1): ?>

                    <li class="page-item">

                        <a class="page-link" href="?pagina=<?php echo $paginaAtual - 1; ?>&dataInicio=<?php echo $dataInicio; ?>&dataFim=<?php echo $dataFim; ?>&assunto_especifico=<?php echo $assunto_especifico; ?>" aria-label="Anterior">

                            <span aria-hidden="true">&laquo;</span>

                        </a>

                    </li>

                <?php endif; ?>



                <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>

                    <li class="page-item <?php echo ($i == $paginaAtual) ? 'active' : ''; ?>">

                        <a class="page-link" href="?pagina=<?php echo $i; ?>&dataInicio=<?php echo $dataInicio; ?>&dataFim=<?php echo $dataFim; ?>&assunto_especifico=<?php echo $assunto_especifico; ?>">

                            <?php echo $i; ?>

                        </a>

                    </li>

                <?php endfor; ?>



                <?php if ($paginaAtual < $totalPaginas): ?>

                    <li class="page-item">

                        <a class="page-link" href="?pagina=<?php echo $paginaAtual + 1; ?>&dataInicio=<?php echo $dataInicio; ?>&dataFim=<?php echo $dataFim; ?>&assunto_especifico=<?php echo $assunto_especifico; ?>" aria-label="Próximo">

                            <span aria-hidden="true">&raquo;</span>

                        </a>

                    </li>

                <?php endif; ?>

            </ul>

        </nav>

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

