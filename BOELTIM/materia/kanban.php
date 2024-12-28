<?php
// kanban.php

// Incluir a conexão com o banco de dados
require_once '../db.php';

// Selecionar boletins
$sql_boletins = "
    SELECT bole_cod, bole_numero, bole_data_publicacao, fk_tipo_bole_cod, bole_assinado, bole_aprovado 
    FROM boletimgeral.boletim
    ORDER BY bole_data_publicacao DESC
";

try {
    $stmt_boletins = $pdo->prepare($sql_boletins);
    $stmt_boletins->execute();
    $boletins = $stmt_boletins->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro ao buscar boletins: " . $e->getMessage());
}

$boards_array = [];

foreach ($boletins as $bole) {
    // Selecionar matérias para o boletim
    $sql_materias = "
        SELECT 
            mp.mate_publ_cod, 
            mb.mate_bole_texto_fechado, 
            mb.mate_bole_texto_aberto, 
            mb.mate_bole_data
        FROM boletimgeral.materia_publicacao mp
        JOIN boletimgeral.materia_boletim mb ON mp.fk_mate_bole_cod = mb.mate_bole_cod
        WHERE mp.fk_bole_cod = :bole_cod
    ";

    try {
        $stmt_materias = $pdo->prepare($sql_materias);
        $stmt_materias->bindParam(':bole_cod', $bole['bole_cod'], PDO::PARAM_INT);
        $stmt_materias->execute();
        $materias = $stmt_materias->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Erro ao buscar matérias: " . $e->getMessage());
    }

    $items_array = [];
    foreach ($materias as $mate) {
        $items_array[] = [
            "id"    => "mate_" . $mate['mate_publ_cod'],
            "title" => $mate['mate_bole_texto_fechado'],
            "desc"  => $mate['mate_bole_texto_aberto'],
            "date"  => $mate['mate_bole_data']
        ];
    }

    $boards_array[] = [
        "id"    => "bole_" . $bole['bole_cod'],
        "title" => "Boletim " . $bole['bole_numero'],
        "class" => "bgwhite",
        "item"  => $items_array
    ];
}

$boards_json = json_encode($boards_array, JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Boletins Gerais - Kanban</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- jKanban CSS -->
    <link rel="stylesheet" href="https://unpkg.com/jkanban@1.3.1/dist/jkanban.min.css">
    <!-- Estilos Personalizados -->
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
        }
        .kanban-container {
            margin-top: 20px;
        }
        /* Estilos do Modal */
        #modal {
            display: none; 
            position: fixed; 
            top:0; 
            left:0; 
            width:100%; 
            height:100%; 
            background:rgba(0,0,0,0.5); 
            justify-content: center; 
            align-items: center;
            z-index: 1000;
        }
        #modalContent {
            background: white; 
            padding: 20px; 
            border-radius: 5px; 
            width: 300px; 
            position: relative;
        }
        #closeModal {
            position: absolute; 
            top: 10px; 
            right: 15px; 
            cursor: pointer; 
            font-size: 20px;
        }
    </style>
</head>
<body>
    <h1>Boletins Gerais - Kanban</h1>
    <div id="boletinsContainer" class="kanban-container"></div>

    <!-- Modal para exibir detalhes da matéria -->
    <div id="modal">
        <div id="modalContent">
            <span id="closeModal">&times;</span>
            <h2>Detalhes da Matéria</h2>
            <p><strong>ID:</strong> <span id="mateId"></span></p>
            <p><strong>Texto:</strong> <span id="mateTexto"></span></p>
            <p><strong>Data:</strong> <span id="mateData"></span></p>
        </div>
    </div>

    <!-- jKanban JS -->
    <script src="https://unpkg.com/jkanban@1.3.1/dist/jkanban.min.js"></script>
    <!-- Script Personalizado -->
    <script>
    var boards = <?php echo $boards_json; ?>;

    var kanban = new jKanban({
        element: '#boletinsContainer',
        boards: boards,
        item: function (data, boardId) {
            // Cria o elemento do item com os atributos de dados adicionais
            var item = document.createElement('div');
            item.classList.add('kanban-item');
            item.setAttribute('data-id', data.id);
            item.setAttribute('data-desc', data.desc);
            item.setAttribute('data-date', data.date);
            item.innerHTML = data.title;
            return item;
        },
        click: function(el) {
            var mateId = el.getAttribute('data-id').split('_')[1];
            var mateTexto = el.getAttribute('data-desc');
            var mateData = el.getAttribute('data-date');

            // Preencher os dados no modal
            document.getElementById('mateId').innerText = mateId;
            document.getElementById('mateTexto').innerText = mateTexto;
            document.getElementById('mateData').innerText = mateData;

            // Exibir o modal
            document.getElementById('modal').style.display = 'flex';
        },
        dragOptions: {
            // Permitir arrastar os itens entre os quadros
            enabled: true,
            // Outras opções de arrastar podem ser configuradas aqui
        },
        dropEl: function(el, target, source, sibling) {
            var mateId = el.getAttribute('data-id').split('_')[1];
            var newBoardId = target.parentElement.getAttribute('data-id').split('_')[1];

            // Enviar dados atualizados via AJAX usando Fetch API
            fetch('boletim_ajax.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `ajaxtp=save&item_mode=updstep&mate_publ_cod=${mateId}&item_value=${newBoardId}`
            })
            .then(response => response.text())
            .then(data => {
                alert(data); // Exibir mensagem de feedback
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Ocorreu um erro ao atualizar a matéria.');
            });
        }
    });

    // Fechar o modal ao clicar no "X"
    document.getElementById('closeModal').addEventListener('click', function() {
        document.getElementById('modal').style.display = 'none';
    });

    // Fechar o modal ao clicar fora da caixa de conteúdo
    window.addEventListener('click', function(event) {
        va
</body>r modal = document.getElementById('modal');
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    });
    </script>
</html>
