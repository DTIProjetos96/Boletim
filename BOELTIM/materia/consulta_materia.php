<?php
// Inclua o arquivo de conexão ao banco de dados
require '../db.php';

try {
    // Consulta SQL para buscar as matérias, ordenando pela data (mate_bole_data) em ordem decrescente, limitado a 10 resultados
    $sql = 'SELECT * FROM bg.vw_materia_boletim ORDER BY mate_bole_data DESC LIMIT 10';
    
    // Preparar e executar a consulta
    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    // Obter os resultados
    $materias = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo 'Erro na consulta: ' . $e->getMessage();
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consulta de Matérias para Boletim</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f4f4f9; /* Cor de fundo aplicada ao body */
            color: #333;
            font-family: 'Arial', sans-serif;
        }
        .container {
            margin-top: 50px;
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); /* Aplicando sombra ao quadro */
            margin-bottom: 50px;
        }
        h2 {
            color: #007bff;
            text-align: center;
            margin-bottom: 30px;
            font-weight: bold;
        }

        /* Novo estilo para agrupar blocos */
        .group-box {
            border: 1px solid #007bff; /* Borda azul ao redor do bloco */
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            background-color: #ffffff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); /* Leve sombra para destacar o bloco */
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }
        thead th, tbody td {
            font-size: 0.85rem; /* Diminui o tamanho da fonte */
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
            vertical-align: middle;
            background-color: #e9ecef; /* Cor de fundo aplicada para destacar os campos */
        }
        tbody tr:nth-child(even) {
            background-color: #dee2e6; /* Alterna a cor de fundo das linhas */
        }
        tbody tr:hover {
            background-color: #ced4da; /* Cor de fundo ao passar o mouse */
        }
        .text-left {
            text-align: left;
        }
        .text-center {
            text-align: center;
        }
        .col-data {
            width: 150px; /* Aumenta a largura da coluna de data */
            white-space: nowrap; /* Impede que a data quebre em linhas */
        }
        .actions {
            display: flex;
            justify-content: center;
            gap: 10px; /* Espaço entre os ícones */
        }
        .actions i {
            cursor: pointer;
            color: #007bff;
        }

        /* Adicionando blocos visíveis */
        .bloco {
            border: 1px solid #007bff;
            border-radius: 10px;
            margin-bottom: 20px;
            padding: 20px;
        }

        .bloco-table {
            border: 1px solid #007bff;
            border-radius: 10px;
            margin-bottom: 20px;
            padding: 20px;
            background-color: #f9f9f9; /* Cor de fundo diferente para o bloco da tabela */
        }

        .status-group {
            margin-top: 15px;
        }

        .filter-buttons {
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="container">

        <!-- Bloco 1 - Formulário de Filtro -->
        <div class="bloco">
            <h2>Consulta de Matérias para Boletim</h2>

            <div class="form-group">
                <label for="textoMateria">Texto da Matéria</label>
                <input type="text" class="form-control" id="textoMateria" placeholder="Texto da Matéria">
            </div>

            <div class="form-row">
                <div class="col-md-6">
                    <label for="assuntoEspecifico">Assunto Específico</label>
                    <input type="text" class="form-control" id="assuntoEspecifico" placeholder="Assunto Específico">
                </div>
                <div class="col-md-6">
                    <label for="assuntoGeral">Assunto Geral</label>
                    <input type="text" class="form-control" id="assuntoGeral" placeholder="Assunto Geral">
                </div>
            </div>

            <div class="status-group">
                <label>Status da Matéria</label><br>
                <input type="radio" name="status" id="status1" value="Cadastrada">
                <label for="status1">Cadastrada</label><br>
                <input type="radio" name="status" id="status2" value="Enviada">
                <label for="status2">Enviada</label><br>
                <input type="radio" name="status" id="status3" value="Enviada e não recebida">
                <label for="status3">Enviada e não recebida</label><br>
                <input type="radio" name="status" id="status4" value="Recebida">
                <label for="status4">Recebida</label><br>
                <input type="radio" name="status" id="status5" value="Publicada">
                <label for="status5">Publicada</label>
            </div>

            <div class="filter-buttons">
                <button type="button" class="btn btn-primary">Filtrar</button>
                <button type="button" class="btn btn-secondary">Limpar</button>
            </div>
        </div>

        <!-- Bloco 2 - Tabela de Matérias -->
        <div class="bloco-table">
            <h2>Matérias Filtradas</h2>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th></th> <!-- Coluna para as ações, sem título -->
                            <th>Texto da Matéria</th>
                            <th class="col-data">Data</th>
                            <th>Assunto Específico</th>
                            <th>Assunto Geral</th>
                            <th>Tipo Documento</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($materias): ?>
                            <?php foreach ($materias as $materia): ?>
                                <tr>
                                    <td class="actions">
                                        <i class="fas fa-paper-plane" title="Enviar"></i>
                                        <i class="fas fa-edit" title="Alterar"></i>
                                        <i class="fas fa-trash-alt" title="Excluir"></i>
                                    </td>
                                    <td class="text-left"><?php echo htmlspecialchars($materia['mate_bole_texto']); ?></td>
                                    <td class="text-center col-data">
                                        <?php echo date('d-m-Y', strtotime($materia['mate_bole_data'])); ?>
                                    </td>
                                    <td class="text-center"><?php echo htmlspecialchars($materia['assu_espe_descricao']); ?></td>
                                    <td class="text-center"><?php echo htmlspecialchars($materia['assu_gera_descricao']); ?></td>
                                    <td class="text-center"><?php echo htmlspecialchars($materia['tipo_docu_descricao']); ?></td>
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
        </div>
        
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
