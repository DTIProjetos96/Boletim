<?php
// Inclui a conexão com o banco de dados
include '../db.php';

// Função para obter os boletins com filtros
function getBoletins($pdo, $dataInicio = '', $dataFim = '', $tipo_bg = '', $subu_descricao = '') {
    $query = "SELECT n_bg, data_bg, tipo_bg, subu_descricao FROM bg.vw_boletim ";

    // Adiciona filtros à consulta se fornecidos
    if ($dataInicio && $dataFim) {
        $query .= " AND data_bg BETWEEN :dataInicio AND :dataFim";
    }

    if ($tipo_bg) {
        $query .= " AND tipo_bg = :tipo_bg";
    }

    if ($subu_descricao) {
        $query .= " AND subu_descricao = :subu_descricao";
    }

    $query .= " ORDER BY data_bg DESC";

    try {
        $stmt = $pdo->prepare($query);

        // Associa os valores dos parâmetros se os filtros forem fornecidos
        if ($dataInicio && $dataFim) {
            $stmt->bindValue(':dataInicio', $dataInicio);
            $stmt->bindValue(':dataFim', $dataFim);
        }
        if ($tipo_bg) {
            $stmt->bindValue(':tipo_bg', $tipo_bg);
        }
        if ($subu_descricao) {
            $stmt->bindValue(':subu_descricao', $subu_descricao);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Log e exibe o erro para depuração
        error_log("Erro na consulta SQL: " . $e->getMessage());
        echo "Erro na consulta SQL: " . $e->getMessage();
        return [];
    }
}

// Função para obter tipos de boletins
function getTiposBoletins($pdo) {
    try {
        $stmt = $pdo->query('SELECT * FROM "tipo_boletim"');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erro na consulta SQL: " . $e->getMessage());
        return [];
    }
}

// Função para obter subunidades
function getSubunidades($pdo) {
    try {
        $stmt = $pdo->query('SELECT * FROM "unidades"');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erro na consulta SQL: " . $e->getMessage());
        return [];
    }
}

// Recebe os dados do formulário de filtro
$dataInicio = isset($_POST['dataInicio']) ? $_POST['dataInicio'] : '';
$dataFim = isset($_POST['dataFim']) ? $_POST['dataFim'] : '';
$tipo_bg = isset($_POST['tipo_bg']) ? $_POST['tipo_bg'] : '';
$subu_descricao = isset($_POST['subu_descricao']) ? $_POST['subu_descricao'] : '';

// Chama as funções para obter os dados necessários
$boletins = getBoletins($pdo, $dataInicio, $dataFim, $tipo_bg, $subu_descricao);
$tiposBoletins = getTiposBoletins($pdo);
$subunidades = getSubunidades($pdo);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assinar Boletim</title>
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
        }
        .btn-assinar {
            background-color: #28a745;
            color: white;
        }
        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Assinar Boletim</h1>

        <!-- Formulário de Filtro -->
        <form method="POST" action="">
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
                    <label for="tipo_bg">Tipo de Boletim</label>
                    <select class="form-control" id="tipo_bg" name="tipo_bg">
                        <option value="">Selecione</option>
                        <?php foreach ($tiposBoletins as $tipo): ?>
                            <option value="<?php echo htmlspecialchars($tipo['tipo_bg']); ?>" <?php echo ($tipo_bg == $tipo['tipo_bg']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($tipo['tipo_bg']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group col-md-3">
                    <label for="subu_descricao">Subunidade</label>
                    <select class="form-control" id="subu_descricao" name="subu_descricao">
                        <option value="">Selecione</option>
                        <?php foreach ($subunidades as $subunidade): ?>
                            <option value="<?php echo htmlspecialchars($subunidade['unid_descricao']); ?>" <?php echo ($subu_descricao == $subunidade['unid_descricao']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($subunidade['unid_descricao']); ?>
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
                    <th>Número BG</th>
                    <th>Data</th>
                    <th>Tipo de Boletim</th>
                    <th>Subunidade</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($boletins): ?>
                    <?php foreach ($boletins as $boletim): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($boletim['n_bg']); ?></td>
                            <td><?php echo date('d-m-Y', strtotime($boletim['data_bg'])); ?></td>
                            <td><?php echo htmlspecialchars($boletim['tipo_bg']); ?></td>
                            <td><?php echo htmlspecialchars($boletim['subu_descricao']); ?></td>
                            <td>
                                <button class="btn btn-assinar">Assinar Boletim</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center">Nenhum boletim encontrado.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
