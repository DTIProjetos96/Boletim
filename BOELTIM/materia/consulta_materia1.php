<?php
include '../db.php';
include '../valida_session.php'; 

// Lógica de paginação
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$limite = 15;
$offset = ($pagina - 1) * $limite;


function getTotalMaterias($pdo, $status = '') {
    $query = "SELECT COUNT(*) as total FROM bg.vw_materias_boletins";
    $conditions = [];

    // Filtro por status
    switch ($status) {
        case 'cadastrada':
            $conditions[] = "mate_bole_p1 = 1";
            $conditions[] = "mate_bole_enviada = 0";
            break;
        case 'enviada':
            $conditions[] = "mate_bole_enviada = 1";
            $conditions[] = "mate_bole_recebida = 0";
            break;
        case 'nao_recebida':
            $conditions[] = "mate_bole_enviada = 1";
            $conditions[] = "mate_bole_recebida = 0";
            break;
        case 'recebida':
            $conditions[] = "mate_bole_recebida = 1";
            $conditions[] = "mate_bole_publicada = 0";
            break;
        case 'publicada':
            $conditions[] = "mate_bole_publicada = 1";
            break;
    }

    if (!empty($conditions)) {
        $query .= " WHERE " . implode(" AND ", $conditions);
    }

    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    } catch (PDOException $e) {
        error_log("Erro ao contar matérias: " . $e->getMessage());
        return 0;
    }
}

function countMateriasByFilter($pdo, $filter) {
    $query = "SELECT COUNT(*) as total FROM bg.vw_materias_boletins";
    $conditions = [];

    switch ($filter) {
        case 'cadastrada':
            $conditions[] = "mate_bole_p1 = 1";
            $conditions[] = "mate_bole_enviada = 0";
            break;
        case 'enviada':
            $conditions[] = "mate_bole_enviada = 1";
            $conditions[] = "mate_bole_recebida = 0";
            break;
        case 'nao_recebida':
            $conditions[] = "mate_bole_enviada = 1";
            $conditions[] = "mate_bole_publicada = 0";
            break;
        case 'recebida':
            $conditions[] = "mate_bole_recebida = 1";
            $conditions[] = "mate_bole_publicada = 0";
            break;
        case 'publicada':
            $conditions[] = "mate_bole_publicada = 1";
            break;
    }

    if (!empty($conditions)) {
        $query .= " WHERE " . implode(" AND ", $conditions);
    }

    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    } catch (PDOException $e) {
        error_log("Erro ao contar matérias por filtro: " . $e->getMessage());
        return 0;
    }
}



// Função para buscar matérias para boletim
function getMateriasBoletim($pdo, $status = '', $texto_materia = '', $assunto_especifico = '', $assunto_geral = '', $limite = 15, $offset = 0) {
    $query = "SELECT DISTINCT mate_bole_cod, mate_bole_texto, mate_bole_data, assu_espe_descricao, assu_gera_descricao, mate_bole_p1, mate_bole_enviada, mate_bole_recebida, mate_bole_publicada FROM bg.vw_materias_boletins ";
    $conditions = [];

    // Filtro por status
    switch ($status) {
        case 'cadastrada':
            $conditions[] = "mate_bole_p1 = 1";
            $conditions[] = "mate_bole_enviada = 0";
            break;
        case 'enviada':
            $conditions[] = "mate_bole_enviada = 1";
            $conditions[] = "mate_bole_recebida = 0";
            break;
        case 'nao_recebida':
            $conditions[] = "mate_bole_enviada = 1";
            $conditions[] = "mate_bole_recebida = 0";
            break;
        case 'recebida':
            $conditions[] = "mate_bole_recebida = 1";
            $conditions[] = "mate_bole_publicada = 0";
            break;
        case 'publicada':
            $conditions[] = "mate_bole_publicada = 1";
            break;
    }

    // Filtros adicionais
    if ($texto_materia) {
        $conditions[] = "mate_bole_texto LIKE :texto_materia";
    }
    if ($assunto_especifico) {
        $conditions[] = "assu_espe_descricao LIKE :assunto_especifico";
    }
    if ($assunto_geral) {
        $conditions[] = "assu_gera_descricao LIKE :assunto_geral";
    }

    if (!empty($conditions)) {
        $query .= " WHERE " . implode(" AND ", $conditions);
    }

    $query .= " ORDER BY mate_bole_data DESC LIMIT :limite OFFSET :offset";

    try {
        $stmt = $pdo->prepare($query);

        // Bind de parâmetros opcionais
        if ($texto_materia) {
            $stmt->bindValue(':texto_materia', "%$texto_materia%");
        }
        if ($assunto_especifico) {
            $stmt->bindValue(':assunto_especifico', "%$assunto_especifico%");
        }
        if ($assunto_geral) {
            $stmt->bindValue(':assunto_geral', "%$assunto_geral%");
        }
        $stmt->bindValue(':limite', (int)$limite, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erro ao buscar matérias: " . $e->getMessage());
        return [];
    }
}


// Função para obter todos os assuntos específicos
function getAssuntosEspecificos($pdo) {
    try {
        $stmt = $pdo->query("SELECT assu_espe_descricao FROM bg.assunto_especifico ORDER BY assu_espe_descricao");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erro ao buscar assuntos específicos: " . $e->getMessage());
        return [];
    }
}

// Função para enviar a matéria
function enviarMateria($pdo, $mate_bole_cod) {
    try {
        // Verificar se mate_bole_p1 = 1 e mate_bole_enviada = 0
        $stmt = $pdo->prepare("SELECT mate_bole_p1, mate_bole_enviada FROM bg.materia_boletim WHERE mate_bole_cod = :mate_bole_cod");
        $stmt->execute([':mate_bole_cod' => $mate_bole_cod]);
        $materia = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($materia && $materia['mate_bole_p1'] == 1 && $materia['mate_bole_enviada'] == 0) {
            // Atualizar mate_bole_enviada para 1
            $stmt = $pdo->prepare("UPDATE bg.materia_boletim SET mate_bole_enviada = 1 WHERE mate_bole_cod = :mate_bole_cod");
            $stmt->execute([':mate_bole_cod' => $mate_bole_cod]);
            echo "<script>alert('Matéria enviada com sucesso!');</script>";
        } else {
            echo "<script>alert('A matéria não pode ser enviada.');</script>";
        }
    } catch (PDOException $e) {
        echo "<script>alert('Erro ao enviar matéria: " . htmlspecialchars($e->getMessage()) . "');</script>";
    }
}


// Recebendo os dados do formulário de filtro
$texto_materia = isset($_POST['texto_materia']) ? $_POST['texto_materia'] : '';
$assunto_especifico = isset($_POST['assunto_especifico']) ? $_POST['assunto_especifico'] : '';
$assunto_geral = isset($_POST['assunto_geral']) ? $_POST['assunto_geral'] : '';
$status = isset($_POST['status']) ? $_POST['status'] : '';

// Verificar se uma matéria está sendo enviada
if (isset($_GET['action']) && $_GET['action'] === 'enviar' && isset($_GET['mate_bole_cod'])) {
    enviarMateria($pdo, $_GET['mate_bole_cod']);
}

$quantidadeCadastradas = countMateriasByFilter($pdo, 'cadastrada');
$quantidadeEnviadas = countMateriasByFilter($pdo, 'enviada');
$quantidadeRecebidas = countMateriasByFilter($pdo, 'recebida');
$quantidadePublicadas = countMateriasByFilter($pdo, 'publicada');
$quantidadeNaoRecebidas = countMateriasByFilter($pdo, 'nao_recebida');

$totalRegistros = getTotalMaterias($pdo, $status);
$totalPaginas = ceil($totalRegistros / $limite);




// Buscar matérias e assuntos específicos
$materias = getMateriasBoletim($pdo, $status, $texto_materia, $assunto_especifico, $assunto_geral, $limite, $offset);
$assuntosEspecificos = getAssuntosEspecificos($pdo);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consulta de Matérias para Boletim</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
    
    .status-bar {
    display: flex;
    flex-direction: row;
    justify-content: space-around; /* Distribui os itens uniformemente */
    align-items: center;
    background-color: #f8f9fa; /* Cor de fundo */
    padding: 10px 20px; /* Ajuste para espaçamento interno */
    border-radius: 8px; /* Cantos arredondados */
    border: 1px solid #dee2e6; /* Borda opcional */
    gap: 15px; /* Espaçamento entre itens */
}


.status-item {
    text-align: center;
    flex: 1; /* Itens ocupam o mesmo espaço */
}

.status-item .nav-link {
    text-decoration: none;
    font-weight: normal;
    color: #007bff;
    padding: px;
    border-radius: 5px;
    transition: all 0.2s;
}

.status-item .nav-link.active {
    background-color: #007bff;
    color: white;
}

/*.status-item .nav-link:hover {*/
/*    background-color: #0056b3;*/
/*    color: white;*/
/*}*/
.status-item .nav-link:hover {
    background-color: #d9e3f0; /* Suave tom azul */
    transform: scale(1.1); /* Amplia ligeiramente */
    transition: 0.2s;
}

    
       .container {
    padding-top: 20px;
    margin: 0 auto; /* Centraliza o container horizontalmente */
    max-width: 1500px; /* Limita a largura do container */
    background-color: #ffffff;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
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
        .filter-container {
    border: 2px solid #dee2e6; /* Cor e espessura da borda */
    border-radius: 8px; /* Cantos arredondados */
    padding: 20px; /* Espaçamento interno */
    margin-bottom: 20px; /* Espaçamento inferior */
    margin-top: 20px; /* Espaçamento superior */
    background-color: #f8f9fa; /* Cor de fundo para destacar */
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); /* Sombra para dar destaque */
    width: 100%; /* Para alinhar com o container principal */
}


@media (max-width: 768px) {
    .filter-container {
        padding: 10px;
    }

    .status-bar {
        flex-direction: column; /* Organiza os itens em coluna */
        gap: 10px; /* Ajusta o espaçamento entre eles */
    }

    .status-item {
        flex: unset; /* Remove a largura proporcional */
        text-align: left; /* Alinha à esquerda */
    }

    table {
        font-size: 12px; /* Reduz o tamanho da fonte */
    }
}


    </style>
</head>
<body>
    <div class="container">
        <h1>Consulta de Matérias para Boletim</h1>
        
        <div class="filter-container">
    <form method="POST" action="consulta_materia1.php">
    <div class="form-row align-items-end">
        <div class="form-group col-md-3">
            <label for="texto_materia">Número da Matéria</label>
            <input type="text" class="form-control" id="texto_materia" name="texto_materia" placeholder="Número da Matéria" value="<?php echo htmlspecialchars($texto_materia); ?>">
        </div>
        <div class="form-group col-md-3">
            <label for="assunto_especifico">Assunto Específico</label>
            <input list="assuntos_especificos" class="form-control" id="assunto_especifico" name="assunto_especifico" placeholder="Assunto Específico" value="<?php echo htmlspecialchars($assunto_especifico); ?>">
            <datalist id="assuntos_especificos">
                <?php foreach ($assuntosEspecificos as $assunto): ?>
                    <option value="<?php echo htmlspecialchars($assunto['assu_espe_descricao']); ?>">
                <?php endforeach; ?>
            </datalist>
        </div>
        <div class="form-group col-md-3">
            <label for="assunto_geral">Assunto Geral</label>
            <input type="text" class="form-control" id="assunto_geral" name="assunto_geral" placeholder="Assunto Geral" value="<?php echo htmlspecialchars($assunto_geral); ?>">
        </div>
        <div class="form-group col-md-3">
            <button type="submit" class="btn btn-primary w-60">Filtrar</button>
        </div>
    </div>


         
            

        <!-- Adicione aqui os filtros de status -->
        <div class="status-bar d-flex justify-content-around mb-3">
            <div class="status-item">
                <a class="nav-link <?= ($status === 'cadastrada') ? 'active' : '' ?>" href="consulta_materia1.php?status=cadastrada">
                    Cadastrada <span>(<?= $quantidadeCadastradas ?>)</span>
                </a>
            </div>
            <div class="status-item">
                <a class="nav-link <?= ($status === 'enviada') ? 'active' : '' ?>" href="consulta_materia1.php?status=enviada">
                    Enviada <span>(<?= $quantidadeEnviadas ?>)</span>
                </a>
            </div>
            <div class="status-item">
    <a class="nav-link <?= ($status === 'nao_recebida') ? 'active' : '' ?>" href="consulta_materia1.php?status=nao_recebida">
        Enviada e Não Recebida <span>(<?= $quantidadeNaoRecebidas ?>)</span>
    </a>
</div>
            <div class="status-item">
                <a class="nav-link <?= ($status === 'recebida') ? 'active' : '' ?>" href="consulta_materia1.php?status=recebida">
                    Recebida <span>(<?= $quantidadeRecebidas ?>)</span>
                </a>
            </div>
            <div class="status-item">
                <a class="nav-link <?= ($status === 'publicada') ? 'active' : '' ?>" href="consulta_materia1.php?status=publicada">
                    Publicada <span>(<?= $quantidadePublicadas ?>)</span>
                </a>
            </div>
        </div>
    </form>
</div>

        
        <!--<div class="form-row DEVE SER AJUSTADO DENTRO DO FORMULÁRIO">--> 
            <!--    <div class="form-group col-md-2">-->
            <!--        <button type="submit" class="btn btn-primary btn-block">Filtrar</button>-->
            <!--    </div>-->
            <!--    <div class="form-group col-md-2">-->
            <!--        <button type="button" class="btn btn-secondary btn-block" onclick="resetForm()">Limpar</button>-->
            <!--    </div>-->
            <!--</div>-->

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
                                <?php if ($materia['mate_bole_p1'] == 1): ?>
                                    <a href="consulta_materia1.php?action=enviar&mate_bole_cod=<?php echo htmlspecialchars($materia['mate_bole_cod']); ?>" 
                                       class="btn btn-info btn-sm" 
                                       onclick="return confirm('Tem certeza que deseja enviar esta matéria?');">Enviar</a>
                                <?php else: ?>
                                    <button class="btn btn-info btn-sm" disabled title="A matéria não pode ser enviada até que `mate_bole_p1` seja igual a 1.">Enviar</button>
                                <?php endif; ?>
                                <a href="/boletim/materia_pessoas/cad.php?mate_bole_cod=<?php echo htmlspecialchars($materia['mate_bole_cod']); ?>" 
                                   class="btn btn-warning btn-sm">Alterar</a>
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
        
    <nav aria-label="Navegação de página">
    <ul class="pagination justify-content-center">
        <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
            <li class="page-item <?= ($i == $pagina) ? 'active' : '' ?>">
                <a class="page-link" href="consulta_materia1.php?pagina=<?= $i ?>&status=<?= $status ?>">
                    <?= $i ?>
                </a>
            </li>
        <?php endfor; ?>
    </ul>
</nav>
    
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
