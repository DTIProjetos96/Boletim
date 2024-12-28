<?php
// kanbam_boletim.php

require_once '../db.php'; // Caminho relativo para o arquivo db.php

// Função para gerar o HTML dos botões "Boletim" e "Matérias" com layout ajustado
function gerarBotaoBoletim($bole_cod) {
    return '
        <div class="d-flex justify-content-start align-items-center mt-2">
            <button class="btn btn-primary btn-sm me-2" onclick="abrirModalBoletimPDF(' . htmlspecialchars($bole_cod, ENT_QUOTES, 'UTF-8') . ')">Boletim Nº</button>
            <a href="list_materias_sem_boletim.php?bole_cod=' . urlencode($bole_cod) . '" class="btn btn-secondary btn-sm">Matérias</a>
        </div>
    ';
}


// Função para gerar os botões "Ver" e "Excluir" para cada matéria
function gerarBotoesMateria($mate_publ_cod, $fk_mate_bole_cod) {
    return '
        <button class="btn btn-info btn-sm me-1" onclick="abrirModalDetalheMateria(' . htmlspecialchars($fk_mate_bole_cod, ENT_QUOTES, 'UTF-8') . ')">Ver</button>
        <button class="btn btn-danger btn-sm" onclick="excluirMateria(' . htmlspecialchars($mate_publ_cod, ENT_QUOTES, 'UTF-8') . ')">Excluir</button>
    ';
}

// Filtrar boletins por data se o formulário foi enviado
$filterDate = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['filter_date'])) {
    $filterDate = $_POST['filter_date'];
}

try {
    $sql_boletins = "
        SELECT 
            bole_cod, 
            bole_numero, 
            bole_data_publicacao, 
            fk_tipo_bole_cod,
            bole_aberto,
            bole_ass_dp,
            bole_ass_cmt,
            fk_subu_cod
        FROM bg.boletim
        WHERE 1=1
    ";

    // Adicionar filtro por data se fornecido
    if (!empty($filterDate)) {
        $sql_boletins .= " AND DATE(bole_data_publicacao) = :filter_date";
    }

    $sql_boletins .= " ORDER BY bole_data_publicacao DESC";

    $stmt = $pdo->prepare($sql_boletins);

    if (!empty($filterDate)) {
        $stmt->bindParam(':filter_date', $filterDate);
    }

    $stmt->execute();
    $rs_boletins = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro ao buscar boletins: " . $e->getMessage());
}

$boards_array = [];

foreach ($rs_boletins as $bole) {
    // Selecionar matérias para o boletim
    try {
    $sql_materias = "
        SELECT 
            mp.mate_publ_cod,              
            mp.fk_mate_bole_cod,           
            mb.fk_assu_espe_cod,           
            ae.assu_espe_descricao                           
        FROM 
            materia_publicacao mp
        JOIN 
            materia_boletim mb 
        ON 
            mp.fk_mate_bole_cod = mb.mate_bole_cod
        JOIN 
            assunto_especifico ae
        ON 
            mb.fk_assu_espe_cod = ae.assu_espe_cod
        WHERE 
            mp.fk_bole_cod = :bole_cod
    ";
    $stmt_materias = $pdo->prepare($sql_materias);
    $stmt_materias->execute(['bole_cod' => $bole['bole_cod']]);
    $rs_materias = $stmt_materias->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro ao buscar matérias: " . $e->getMessage());
}

    $items_array = [];
    foreach ($rs_materias as $mate) {
        // Gerar um ID único para o collapse baseado no código da matéria
        $collapse_id = 'collapse_' . htmlspecialchars($mate['mate_publ_cod'], ENT_QUOTES, 'UTF-8');

        // Preparar o conteúdo para o popover
        $popover_content = htmlspecialchars($mate['mate_bole_texto'], ENT_QUOTES, 'UTF-8');

        $items_array[] = [
            "id" => "mate_" . htmlspecialchars($mate['mate_publ_cod'], ENT_QUOTES, 'UTF-8'),
            "title" => '
                <div>
                 <div class="mb-2"><strong></strong> ' . htmlspecialchars($mate['assu_espe_descricao'], ENT_QUOTES, 'UTF-8') . '</div>
                 
                    <!-- Cabeçalho com botão de toggle e botões de ação -->
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <button class="btn btn-link btn-sm toggle-button me-2"  style="margin-right: 20px;" type="button" data-bs-toggle="collapse" data-bs-target="#' . $collapse_id . '" aria-expanded="false" aria-controls="' . $collapse_id . '"
                                data-bs-toggle-popover="' . $popover_content . '">
                                <span class="toggle-icon">+</span>
                            </button> 
                            <div class="buttons-materia d-flex align-items-center">
                                ' . gerarBotoesMateria($mate['mate_publ_cod'], $mate['fk_mate_bole_cod']) . '
                            </div>
                        </div>
                    </div>
                    <!-- Conteúdo colapsável -->
                    <div class="collapse" id="' . $collapse_id . '">
                        <div class="mt-2">' . htmlspecialchars($mate['mate_bole_texto'], ENT_QUOTES, 'UTF-8') . '</div>
                    </div>
                </div>',
            "class" => "bgblue"
        ];
    }

    // Formatar os detalhes adicionais do boletim
    $detalhes_boletim = "
        <div class='detalhes-boletim'>
            <p><strong>Data de Publicação:</strong> " . htmlspecialchars(date("d/m/Y", strtotime($bole['bole_data_publicacao'])), ENT_QUOTES, 'UTF-8') . "</p>
            <p><strong>Aberto:</strong> " . ($bole['bole_aberto'] ? 'Sim' : 'Não') . "</p>
            <p><strong>Ass. DP:</strong> " . ($bole['bole_ass_dp'] ? 'Sim' : 'Não') . "</p>
            <p><strong>Ass. CMT:</strong> " . ($bole['bole_ass_cmt'] ? 'Sim' : 'Não') . "</p>
            <p><strong>Subu Cod:</strong> " . htmlspecialchars($bole['fk_subu_cod'], ENT_QUOTES, 'UTF-8') . "</p>
        </div>
    ";
// Ajuste no título do boletim e posição dos botões
$titulo_boletim = "
    <div class='card'>
        <div class='card-header text-center'>
            <strong>Boletim Nº " . htmlspecialchars($bole['bole_numero'], ENT_QUOTES, 'UTF-8') . " - " . htmlspecialchars(date("d/m/Y", strtotime($bole['bole_data_publicacao'])), ENT_QUOTES, 'UTF-8') . "</strong>
        </div>
        <div class='card-body d-flex justify-content-center'>
            <button class='btn btn-primary btn-sm me-2' onclick='abrirModalBoletimPDF(" . htmlspecialchars($bole['bole_cod'], ENT_QUOTES, 'UTF-8') . ")'>Boletim</button>
            <a href='list_materias_sem_boletim.php?bole_cod=" . urlencode($bole['bole_cod']) . "' class='btn btn-secondary btn-sm'>Matérias</a>
        </div>
    </div>
";

    $boards_array[] = [
        "id" => "bole_" . htmlspecialchars($bole['bole_cod'], ENT_QUOTES, 'UTF-8'),
        "title" => $titulo_boletim,
        "class" => "boardblue",
        "item" => $items_array,
        "metadata" => $detalhes_boletim
    ];
}

$boards_json = json_encode($boards_array);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Adiciionoar Materias aos Boletins</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- jKanban CSS -->
    <link rel="stylesheet" href="https://unpkg.com/jkanban@1.3.1/dist/jkanban.min.css">
    <!-- Font Awesome CSS -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
        integrity="sha512-p6o4sDHRx4GXPA3TvCjby8H4M5ZzILiGfE2Gxq6kXG7X1iMxTSUnMYLzFJfJgzwb1EJZK76R3oA/0+6Tllw8UA=="
        crossorigin="anonymous"
        referrerpolicy="no-referrer"
    />
    <!-- Seu CSS Personalizado -->
    <link rel="stylesheet" href="css/styles.css">
     <style>
     
     .d-flex .btn {
    margin-right: 8px; /* Ajuste o espaçamento horizontal entre os botões */
}

.d-flex .btn:last-child {
    margin-right: 0; /* Remove margem do último botão */
}

     
     .card {
    border: 1px solid #ccc;
    border-radius: 5px;
    background-color: #f8f9fa;
    padding: 10px;
}

.card-header {
    font-weight: bold;
    font-size: 14px;
    margin-bottom: 10px;
}

.card-body button,
.card-body a {
    display: inline-block;
}

     
     .toggle-button {
    margin-right: 10px; /* Ajuste o valor conforme necessário */
}
        .page-container {
            border: 1px solid black; /* Cor e largura da borda */
            padding: 20px; /* Espaçamento interno */
            margin: 20px auto; /* Espaçamento externo */
            border-radius: 10px; /* Borda arredondada */
            background-color: white; /* Cor de fundo */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Sombra para destaque */
            max-width: 1200px; /* Largura máxima */
        }
        body {
    background-color: #f8f9fa; /* Fundo claro */
}
h1 {
    font-weight: bold; /* Título destacado */
}
    </style>
</head>
<body>
     <div class="page-container">
    <div class="container my-4">
        <h1 class="mb-4">Adicionar Materias em um Boletim</h1>

        <!-- Formulário de Pesquisa por Data -->
        <form method="POST" class="row g-3 mb-4">
            <div class="col-auto">
                <label for="filter_date" class="visually-hidden">Data</label>
                <input type="date" class="form-control" id="filter_date" name="filter_date" value="<?php echo htmlspecialchars($filterDate); ?>">
            </div>
            <div class="col-auto">
        <label for="filter_boletim_num" class="visually-hidden">Número do Boletim</label>
        <input type="text" class="form-control" id="filter_boletim_num" name="filter_boletim_num" value="<?php echo htmlspecialchars($_POST['filter_boletim_num'] ?? ''); ?>" placeholder="Número do Boletim">
    </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary mb-3">Pesquisar</button>
            </div>
        </form>

        <!-- Botões para Ações em Massa -->
        <div class="mb-3">
            <button class="btn btn-danger me-2" onclick="excluirMateriasSelecionadas()">Excluir Selecionados</button>
            <button class="btn btn-warning me-2" onclick="moverMateriasSelecionadas()">Mover Selecionados</button>
            <button id="toggleAllContent" class="btn btn-secondary">Exibir Conteúdo</button>
        </div>

        <!-- Container do Kanban -->
        <div id="boletinsContainer"></div>
    </div>

    <!-- Modal para Detalhes da Matéria -->
    <div id="detalhesModal" class="modal fade" tabindex="-1" aria-labelledby="detalhesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 id="detalhesModalLabel" class="modal-title">Detalhes da Matéria</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <iframe id="detalhesMateriaIframe" src="" width="100%" height="400px" frameborder="0"></iframe>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para PDF do Boletim -->
    <div id="boletimPDFModal" class="modal fade" tabindex="-1" aria-labelledby="boletimPDFModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 id="boletimPDFModalLabel" class="modal-title">Boletim PDF</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <iframe id="iframeBoletimPDF" src="" width="100%" height="500px" frameborder="0"></iframe>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Lista de Matérias Disponíveis -->
    <div id="materiasDisponiveisModal" class="modal fade" tabindex="-1" aria-labelledby="materiasDisponiveisModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 id="materiasDisponiveisModalLabel" class="modal-title">Matérias Disponíveis</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div id="listaMateriasDisponiveis">
                        <!-- O conteúdo carregado via AJAX será inserido aqui -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle (inclui Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jKanban JS -->
    <script src="https://unpkg.com/jkanban@1.3.1/dist/jkanban.min.js"></script>
    <!-- Seu JS Personalizado -->
    <script src="js/scripts.js"></script>
    <script>
    var boards = <?php echo $boards_json; ?>;

    var kanban = new jKanban({
        element: '#boletinsContainer',
        boards: boards,
        dragItems: true,
        itemHandleOptions: {
            enabled: true,
            visible: true,
            content: '<i class="fas fa-bars"></i>'
        },
        // Função para personalizar o HTML dos itens
        template: function(item) {
            return item.title;
        },
        // Função para atualizar a posição da matéria ao soltar
        dropEl: function(el, target, source, sibling) {
            var mateId = el.dataset.eid.split('_')[1];
            var newBoardId = target.parentElement.dataset.id.split('_')[1];

            // Enviar dados atualizados via AJAX
            var xhr = new XMLHttpRequest();
            xhr.open("POST", "json.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        try {
                            var resposta = JSON.parse(xhr.responseText);
                            if (resposta.status === 'success') {
                                alert(resposta.message);
                            } else {
                                alert(resposta.message);
                                // Reverter a posição se houver erro
                                kanban.refresh();
                            }
                        } catch (e) {
                            alert("Erro ao processar resposta do servidor.");
                        }
                    } else {
                        alert("Erro na requisição AJAX.");
                    }
                }
            };
            xhr.send("ajaxtp=save&item_mode=updstep&mate_publ_cod=" + encodeURIComponent(mateId) + "&item_value=" + encodeURIComponent(newBoardId));
        }
    });

    // Funções para os botões nos itens
    function abrirModalDetalheMateria(fk_mate_bole_cod) {
        var iframe = document.getElementById('detalhesMateriaIframe');
        iframe.src = "../materia/detalhe_materia.php?mate_bole_cod=" + encodeURIComponent(fk_mate_bole_cod);

        // Exibir o modal de detalhes da matéria
        var modal = new bootstrap.Modal(document.getElementById('detalhesModal'));
        modal.show();
    }

    function excluirMateria(mate_publ_cod) {
        if (!mate_publ_cod) {
            alert("Código da matéria inválido.");
            return;
        }

        if (!confirm("Tem certeza que deseja excluir esta matéria do boletim?")) {
            return;
        }

        // Enviar requisição AJAX para excluir a matéria do boletim
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "excluir_materia.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
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
            }
        };
        xhr.send("mate_publ_cod=" + encodeURIComponent(mate_publ_cod));
    }

    // Funções para o Modal do PDF do Boletim
    function abrirModalBoletimPDF(bole_cod) {
        var iframe = document.getElementById('iframeBoletimPDF');
        iframe.src = "../pdf/boletim_pdf.php?bole_cod=" + encodeURIComponent(bole_cod);

        // Exibir o modal de PDF do boletim
        var modal = new bootstrap.Modal(document.getElementById('boletimPDFModal'));
        modal.show();
    }

    function fecharModalBoletimPDF() {
        var iframe = document.getElementById('iframeBoletimPDF');
        iframe.src = "";

        // Fechar o modal de PDF do boletim
        var modal = new bootstrap.Modal(document.getElementById('boletimPDFModal'));
        modal.hide();
    }

    // Função para abrir a lista de matérias
    function abrirListaMaterias(bole_cod) {
        // Enviar requisição AJAX para obter matérias sem boletim
        var xhr = new XMLHttpRequest();
        xhr.open("GET", "list_materias_sem_boletim.php?bole_cod=" + encodeURIComponent(bole_cod), true);
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    document.getElementById('listaMateriasDisponiveis').innerHTML = xhr.responseText;
                    var modal = new bootstrap.Modal(document.getElementById('materiasDisponiveisModal'));
                    modal.show();
                } else {
                    alert("Erro ao carregar matérias disponíveis. Status: " + xhr.status);
                }
            }
        };
        xhr.send();
    }

    // Função para adicionar matérias selecionadas
    function adicionarMateriasSelecionadas(bole_cod) {
        // Recupera todas as checkboxes selecionadas
        var checkboxes = document.querySelectorAll('.materia-checkbox:checked');
        var materias = Array.from(checkboxes).map(function(checkbox) {
            return checkbox.value;
        });

        if (materias.length === 0) {
            alert("Nenhuma matéria selecionada para adicionar.");
            return;
        }

        // Envia os dados via AJAX para adicionar_materias.php
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "adicionar_materias.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    try {
                        var resposta = JSON.parse(xhr.responseText);
                        if (resposta.status === 'success') {
                            alert(resposta.message);
                            // Recarrega o Kanban para refletir as mudanças
                            location.reload();
                        } else {
                            alert("Erro: " + resposta.message);
                        }
                    } catch (e) {
                        alert("Erro ao processar resposta do servidor.");
                    }
                } else {
                    alert("Erro na requisição AJAX. Status: " + xhr.status);
                }
            }
        };
        // Prepara os dados para envio
        var params = "bole_cod=" + encodeURIComponent(bole_cod) + "&materias[]=" + materias.map(encodeURIComponent).join("&materias[]=");
        xhr.send(params);
    }

    // Função para selecionar/deselecionar todas as checkboxes
    document.addEventListener('DOMContentLoaded', function() {
        var selectAllCheckbox = document.getElementById('selectAll');
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function() {
                var checkboxes = document.querySelectorAll('.materia-checkbox');
                checkboxes.forEach(function(checkbox) {
                    checkbox.checked = selectAllCheckbox.checked;
                });
            });
        }

        // Inicializar Popovers
        var popoverTriggerList = [].slice.call(document.querySelectorAll('.toggle-button'));
        popoverTriggerList.forEach(function(popoverTriggerEl) {
            var popoverContent = popoverTriggerEl.getAttribute('data-bs-toggle-popover') || '';
            var popover = new bootstrap.Popover(popoverTriggerEl, {
                trigger: 'hover',
                html: true,
                content: popoverContent,
                placement: 'right', // Pode ajustar conforme necessário
                sanitize: false // Importante para permitir HTML no conteúdo
            });
        });

        // Adicionar listeners para alternar os ícones de collapse
        var toggleButtons = document.querySelectorAll('.toggle-button');

        toggleButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                var toggleIcon = this.querySelector('.toggle-icon');
                var target = document.querySelector(this.getAttribute('data-bs-target'));

                // Utiliza setTimeout para garantir que a classe 'show' já foi atualizada
                setTimeout(function() {
                    if (target.classList.contains('show')) {
                        toggleIcon.textContent = '-';
                    } else {
                        toggleIcon.textContent = '+';
                    }
                }, 350); // Tempo de transição do collapse (ajustável)
            });
        });

        // Atualiza os ícones quando o collapse é mostrado ou escondido via outros meios
        var collapses = document.querySelectorAll('.collapse');

        collapses.forEach(function(collapse) {
            collapse.addEventListener('show.bs.collapse', function () {
                var button = document.querySelector('[data-bs-target="#' + this.id + '"]');
                if (button) {
                    var toggleIcon = button.querySelector('.toggle-icon');
                    if (toggleIcon) {
                        toggleIcon.textContent = '-';
                    }
                }
            });

            collapse.addEventListener('hide.bs.collapse', function () {
                var button = document.querySelector('[data-bs-target="#' + this.id + '"]');
                if (button) {
                    var toggleIcon = button.querySelector('.toggle-icon');
                    if (toggleIcon) {
                        toggleIcon.textContent = '+';
                    }
                }
            });
        });
    });

    // Função para inicializar a funcionalidade "Exibir/Ocultar Conteúdo"
    document.addEventListener('DOMContentLoaded', function() {
        var toggleAllBtn = document.getElementById('toggleAllContent');
        var allExpanded = false; // Estado inicial

        toggleAllBtn.addEventListener('click', function() {
            var collapseElements = document.querySelectorAll('.collapse');
            var toggleButtons = document.querySelectorAll('.toggle-button');

            if (!allExpanded) {
                // Expandir todos
                collapseElements.forEach(function(collapseEl) {
                    var bsCollapse = new bootstrap.Collapse(collapseEl, {
                        toggle: false
                    });
                    bsCollapse.show();
                });

                // Atualizar ícones para '-'
                toggleButtons.forEach(function(button) {
                    var toggleIcon = button.querySelector('.toggle-icon');
                    if (toggleIcon) {
                        toggleIcon.textContent = '-';
                    }
                });

                // Alterar texto do botão
                toggleAllBtn.textContent = 'Ocultar Conteúdo';
                allExpanded = true;
            } else {
                // Colapsar todos
                collapseElements.forEach(function(collapseEl) {
                    var bsCollapse = new bootstrap.Collapse(collapseEl, {
                        toggle: false
                    });
                    bsCollapse.hide();
                });

                // Atualizar ícones para '+'
                toggleButtons.forEach(function(button) {
                    var toggleIcon = button.querySelector('.toggle-icon');
                    if (toggleIcon) {
                        toggleIcon.textContent = '+';
                    }
                });

                // Alterar texto do botão
                toggleAllBtn.textContent = 'Exibir Conteúdo';
                allExpanded = false;
            }
        });
    });
    </script>
</body>
</html>
