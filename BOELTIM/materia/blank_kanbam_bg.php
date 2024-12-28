<?php
// Ativar a exibição de erros para depuração (remover em produção)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Inclua o arquivo de conexão ao banco de dados
include('../db.php');

// Selecionar boletins com tipo, ano e mês
$sql_boletins = "
SELECT 
    b.bole_cod, 
    b.bole_numero, 
    b.bole_data_publicacao, 
    b.fk_tipo_bole_cod, 
    b.bole_ass_dp, 
    b.bole_ass_cmt, 
    EXTRACT(YEAR FROM b.bole_data_publicacao) AS ano, 
    EXTRACT(MONTH FROM b.bole_data_publicacao) AS mes, 
    tb.tipo_bole_descricao, 
    tb.tipo_bole_sigla
FROM 
    bg.boletim b
JOIN 
    bg.tipo_boletim tb 
    ON b.fk_tipo_bole_cod = tb.tipo_bole_cod
ORDER BY 
    b.bole_data_publicacao DESC";
$stmt_boletins = $pdo->query($sql_boletins);
$rs_boletins = $stmt_boletins->fetchAll(PDO::FETCH_ASSOC);

$boards_array = [];

// Criar uma board para matérias não atribuídas
$sql_materias_nao_atribuidas = "
SELECT 
    mb.mate_bole_cod, 
    mb.mate_bole_texto_aberto, 
    mp.mate_publ_cod
FROM 
    bg.materia_boletim mb
LEFT JOIN 
    bg.materia_publicacao mp 
    ON mb.mate_bole_cod = mp.fk_mate_bole_cod
WHERE 
    mp.fk_bole_cod IS NULL
GROUP BY 
    mb.mate_bole_cod, mb.mate_bole_texto_aberto, mp.mate_publ_cod";
$stmt_nao_atribuidas = $pdo->query($sql_materias_nao_atribuidas);
$rs_materias_nao_atribuidas = $stmt_nao_atribuidas->fetchAll(PDO::FETCH_ASSOC);

$items_nao_atribuidos = [];
if ($rs_materias_nao_atribuidas) {
    foreach ($rs_materias_nao_atribuidas as $mate) {
        if (!empty($mate['mate_publ_cod']) && ctype_digit((string)$mate['mate_publ_cod'])) {
            // Matéria já possui 'mate_publ_cod' (embora 'fk_bole_cod' seja NULL)
            $id = "mate_publ_" . $mate['mate_publ_cod'];
        } else {
            // Matéria não possui 'mate_publ_cod'
            $id = "mate_bole_" . $mate['mate_bole_cod'];
        }

        $items_nao_atribuidos[] = [
            "id" => $id,
            "title" => htmlspecialchars($mate['mate_bole_texto_aberto'])
        ];
    }
}

$boards_array[] = [
    "id" => "nao_atribuidas",
    "title" => "Matérias Não Atribuídas",
    "class" => "bgwhite",
    "item" => $items_nao_atribuidos
];

// Criar as boards para cada boletim
if ($rs_boletins) {
    foreach ($rs_boletins as $bole) {
        // Exibir o tipo de boletim, ano e mês
        $tipo_bole = $bole['tipo_bole_descricao'] . " (" . $bole['tipo_bole_sigla'] . ")";
        $ano_mes = $bole['ano'] . " - " . str_pad($bole['mes'], 2, '0', STR_PAD_LEFT);

        // Selecionar matérias para o boletim
        $sql_materias = "
        SELECT 
            mb.mate_bole_cod, 
            mb.mate_bole_texto_aberto, 
            mp.mate_publ_cod
        FROM 
            bg.materia_boletim mb
        JOIN 
            bg.materia_publicacao mp 
            ON mb.mate_bole_cod = mp.fk_mate_bole_cod
        WHERE 
            mp.fk_bole_cod = ?";
        $stmt_materias = $pdo->prepare($sql_materias);
        $stmt_materias->execute([$bole['bole_cod']]);
        $rs_materias = $stmt_materias->fetchAll(PDO::FETCH_ASSOC);

        $items_array = [];
        if ($rs_materias) {
            foreach ($rs_materias as $mate) {
                if (!empty($mate['mate_publ_cod']) && ctype_digit((string)$mate['mate_publ_cod'])) {
                    $id = "mate_publ_" . $mate['mate_publ_cod'];
                } else {
                    // Caso não possua 'mate_publ_cod', ignore
                    continue;
                }

                $items_array[] = [
                    "id" => $id,
                    "title" => htmlspecialchars($mate['mate_bole_texto_aberto'])
                ];
            }
        }

        $boards_array[] = [
            "id" => "bole_" . $bole['bole_cod'],
            "title" => "Boletim " . htmlspecialchars($bole['bole_numero']) . " - " . $tipo_bole . " - " . $ano_mes,
            "class" => "bgwhite",
            "item" => $items_array
        ];
    }
}

$boards_json = json_encode($boards_array);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Boletins Gerais</title>
    
    <!-- jKanban CSS e JS -->
    <link rel="stylesheet" href="https://unpkg.com/jkanban@1.3.1/dist/jkanban.min.css">
    <script src="https://unpkg.com/jkanban@1.3.1/dist/jkanban.min.js"></script>
    
    <style>
        /* Estilos para o modal */
        .modal {
            display: none; 
            position: fixed; 
            z-index: 1; 
            padding-top: 60px; 
            left: 0;
            top: 0;
            width: 100%; 
            height: 100%; 
            overflow: auto; 
            background-color: rgba(0,0,0,0.4); 
        }
        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div id="boletinsContainer"></div>
    
    <!-- Modal -->
    <div id="materiaModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <div id="modalContent">
                <!-- O conteúdo da matéria será carregado aqui -->
            </div>
        </div>
    </div>
    
    <script>
    // Receber os boards em formato JSON do PHP
    var boards = <?php echo $boards_json; ?>;
    
    var kanban = new jKanban({
        element: '#boletinsContainer',
        boards: boards,
        dragBoards: false, // Impede o arrastar e soltar de boards
        addItemButton: false, // Impede a adição de novos itens via botão
        click: function(el) {
            var fullId = el.dataset.eid; // ex: "mate_publ_456" ou "mate_bole_123"
            var parts = fullId.split('_');
            var tipo = parts[1]; // 'publ' ou 'bole'
            var mateId = parts[2];

            if (tipo === 'publ') {
                // Requisição para 'mate_publ_cod'
                var xhr = new XMLHttpRequest();
                xhr.open('GET', 'detalhe_materia.php?mate_publ_cod=' + encodeURIComponent(mateId), true);
                xhr.onreadystatechange = function () {
                    if (xhr.readyState == 4 && xhr.status == 200) {
                        document.getElementById('modalContent').innerHTML = xhr.responseText;
                        document.getElementById('materiaModal').style.display = 'block';
                    }
                };
                xhr.send();
            } else if (tipo === 'bole') {
                // Requisição para 'mate_bole_cod'
                var xhr = new XMLHttpRequest();
                xhr.open('GET', 'detalhe_materia.php?mate_bole_cod=' + encodeURIComponent(mateId), true);
                xhr.onreadystatechange = function () {
                    if (xhr.readyState == 4 && xhr.status == 200) {
                        document.getElementById('modalContent').innerHTML = xhr.responseText;
                        document.getElementById('materiaModal').style.display = 'block';
                    }
                };
                xhr.send();
            }
        },
        dropEl: function(el, target, source, sibling) {
            var boardId = target.parentElement.dataset.id;
            var fullId = el.dataset.eid; // ex: "mate_publ_456" ou "mate_bole_123"
            var parts = fullId.split('_');
            var tipo = parts[1]; // 'publ' ou 'bole'
            var mateId = parts[2];
            var newBoardId;

            if (boardId === "nao_atribuidas") {
                newBoardId = "nao_atribuidas"; // Indica que deve ser setado como NULL
            } else if (boardId.startsWith("bole_")) {
                // Extrai o boletim código do id da board (e.g., "bole_1" -> "1")
                newBoardId = boardId.split('_')[1];
            } else {
                // Caso não corresponda a nenhum padrão conhecido
                alert("Board inválida.");
                return;
            }

            // Enviar dados atualizados via AJAX
            var xhr = new XMLHttpRequest();
            xhr.open("POST", "blank_boletim_ajax.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        var response = xhr.responseText.trim();
                        if (response.includes("bem-sucedida")) {
                            alert(response); // Exibir mensagem de sucesso
                            location.reload(); // Recarregar a página para atualizar o Kanban
                        } else {
                            alert("Erro: " + response); // Exibir mensagem de erro
                            location.reload(); // Recarregar a página para reverter
                        }
                    } else {
                        alert("Erro na requisição AJAX.");
                        location.reload(); // Recarregar a página para reverter
                    }
                }
            };

            if (tipo === 'publ') {
                // Enviar 'mate_publ_cod' e 'item_value'
                xhr.send("ajaxtp=save&item_mode=updstep&mate_publ_cod=" + encodeURIComponent(mateId) + "&item_value=" + encodeURIComponent(newBoardId));
            } else if (tipo === 'bole') {
                // Enviar 'mate_bole_cod' e 'item_value'
                xhr.send("ajaxtp=save&item_mode=updstep&mate_bole_cod=" + encodeURIComponent(mateId) + "&item_value=" + encodeURIComponent(newBoardId));
            }
        }
    });

    // Fechar o modal quando o botão de fechamento for clicado
    var modal = document.getElementById('materiaModal');
    var span = document.getElementsByClassName('close')[0];

    span.onclick = function() {
        modal.style.display = 'none';
    }

    // Fechar o modal quando o usuário clicar fora dele
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
    </script>
</body>
</html>
