<?php
// Inclui a conexão com o banco de dados
include('../../db.php'); // Ajuste o número de '../' conforme a estrutura de diretórios

// Função para obter os boletins com filtros
function getBoletins($pdo, $dataInicio = '', $dataFim = '', $tipo_bg = '') {
    // Inicia a consulta com WHERE 1=1 para facilitar a adição de condições
    $query = "SELECT n_bg, data_bg, tipo_bg, bole_cod FROM bg.vw_bg WHERE 1=1";

    // Adiciona filtros à consulta se fornecidos
    if ($dataInicio && $dataFim) {
        $query .= " AND data_bg BETWEEN :dataInicio AND :dataFim";
    }

    if ($tipo_bg) {
        $query .= " AND tipo_bg = :tipo_bg";
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

// Chama as funções para obter os dados necessários
$boletins = getBoletins($pdo, $dataInicio, $dataFim, $tipo_bg);
$tiposBoletins = getTiposBoletins($pdo);
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
                            <td>
                                <button 
                                    class="btn btn-assinar assinar-boletim-btn" 
                                    data-bolecod="<?php echo htmlspecialchars($boletim['bole_cod']); ?>"
                                >
                                    Assinar Boletim
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="text-center">Nenhum boletim encontrado.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="assinarBoletimModal" tabindex="-1" aria-labelledby="assinarBoletimModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="assinarBoletimModalLabel">Assinar Boletim</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <!-- O conteúdo será carregado aqui via AJAX -->
            <iframe id="modalContentFrame" src="" width="100%" height="400px" frameborder="0"></iframe>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
          </div>
        </div>
      </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
    $(document).ready(function() {
        $('.assinar-boletim-btn').on('click', function() {
            // Obter o valor de bole_cod a partir do atributo data
            var boleCod = $(this).data('bolecod');
            
            // Construir a URL com o parâmetro bole_cod
            var url = 'cad_assina_boletim_cmt.php?bole_cod=' + encodeURIComponent(boleCod);
            
            // Definir a URL no iframe do modal
            $('#modalContentFrame').attr('src', url);
            
            // Abrir o modal
            $('#assinarBoletimModal').modal('show');
        });
        
        // Limpar o iframe quando o modal for fechado
        $('#assinarBoletimModal').on('hidden.bs.modal', function () {
            $('#modalContentFrame').attr('src', '');
        });
    });
    </script>
</body>
</html>
