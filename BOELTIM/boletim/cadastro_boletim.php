<?php
include '../db.php'; // Certifique-se de que o caminho para db.php está correto

// Ativar exibição de erros para depuração (remova em produção)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Captura os parâmetros da URL
$bole_cod = isset($_GET['bole_cod']) ? (int)$_GET['bole_cod'] : 0;
$bole_data_publicacao = isset($_GET['bole_data_publicacao']) ? $_GET['bole_data_publicacao'] : '';
$boletim = [];
$bole_numero = 0;

// Verifica se é uma edição ou cadastro
if ($bole_cod > 0) {
    // Recupera os dados do boletim para edição
    $stmt = $pdo->prepare('SELECT * FROM bg.boletim WHERE bole_cod = :bole_cod');
    $stmt->execute(['bole_cod' => $bole_cod]);
    $boletim = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$boletim) {
        echo '<div class="alert alert-danger">Boletim não encontrado!</div>';
        exit;
    }
} else {
    // Para cadastro, busca o último número de boletim e incrementa
    $stmt = $pdo->query('SELECT MAX(bole_numero) AS max_numero FROM bg.boletim');
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $bole_numero = $result['max_numero'] + 1;
}

// Recupera as opções para o campo Tipo de Boletim
$stmt_tipo_boletim = $pdo->query('SELECT tipo_bole_cod, tipo_bole_descricao FROM bg.tipo_boletim ORDER BY tipo_bole_descricao');
$tipos_boletim = $stmt_tipo_boletim->fetchAll(PDO::FETCH_ASSOC);

// Recupera as opções para o campo Subunidade a partir da visualização
$stmt_subunidade = $pdo->query('
    SELECT subu_cod, descricao 
    FROM bg.vw_comando_unidade_subunidade 
    ORDER BY descricao
');
$subunidades = $stmt_subunidade->fetchAll(PDO::FETCH_ASSOC);

// Verifica se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if ($bole_cod > 0) {
            // Atualiza o boletim existente
            $stmt = $pdo->prepare('
                UPDATE bg.boletim 
                SET bole_numero = :bole_numero, bole_data_publicacao = :bole_data_publicacao, fk_tipo_bole_cod = :fk_tipo_bole_cod,
                    bole_aberto = :bole_aberto, bole_ass_dp = :bole_ass_dp, bole_ass_cmt = :bole_ass_cmt, fk_subu_cod = :fk_subu_cod
                WHERE bole_cod = :bole_cod
            ');
            $stmt->execute([
                'bole_numero' => $_POST['bole_numero'],
                'bole_data_publicacao' => $_POST['bole_data_publicacao'],
                'fk_tipo_bole_cod' => $_POST['fk_tipo_bole_cod'],
                'bole_aberto' => $_POST['bole_aberto'],
                'bole_ass_dp' => $_POST['bole_ass_dp'],
                'bole_ass_cmt' => $_POST['bole_ass_cmt'],
                'fk_subu_cod' => $_POST['fk_subu_cod'],
                'bole_cod' => $bole_cod
            ]);
        } else {
            // Cadastra um novo boletim
            $stmt = $pdo->prepare('
                INSERT INTO bg.boletim (bole_numero, bole_data_publicacao, fk_tipo_bole_cod, bole_aberto, bole_ass_dp, bole_ass_cmt, fk_subu_cod)
                VALUES (:bole_numero, :bole_data_publicacao, :fk_tipo_bole_cod, :bole_aberto, :bole_ass_dp, :bole_ass_cmt, :fk_subu_cod)
            ');
            $stmt->execute([
                'bole_numero' => $_POST['bole_numero'],
                'bole_data_publicacao' => $_POST['bole_data_publicacao'],
                'fk_tipo_bole_cod' => $_POST['fk_tipo_bole_cod'],
                'bole_aberto' => $_POST['bole_aberto'],
                'bole_ass_dp' => $_POST['bole_ass_dp'],
                'bole_ass_cmt' => $_POST['bole_ass_cmt'],
                'fk_subu_cod' => $_POST['fk_subu_cod']
            ]);

            // Obter o ID do boletim recém-criado
            $bole_cod = $pdo->lastInsertId();
        }

        // Após o processamento bem-sucedido, envia um script para fechar o modal e atualizar o calendário
        echo "<script type='text/javascript'>
                if (window.parent && window.parent.bootstrap) {
                    window.parent.bootstrap.Modal.getInstance(window.parent.document.getElementById('boletimModal')).hide();
                    window.parent.$('#calendar').fullCalendar('refetchEvents');
                }
              </script>";
        exit;
    } catch (PDOException $e) {
        echo '<div class="alert alert-danger">Erro ao processar a solicitação: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $bole_cod > 0 ? 'Editar Boletim' : 'Cadastrar Boletim'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Estilos CSS Otimizados */
        body {
            background-color: #f4f4f9;
            color: #343a40;
            font-size: 0.95rem;
        }
        .container {
            background-color: #ffffff;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 0 8px rgba(0, 0, 0, 0.1);
            margin-top: 20px;
            max-width: 900px;
        }
        h2 {
            color: #007bff;
            margin-bottom: 20px;
            font-size: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2><?php echo $bole_cod > 0 ? 'Editar Boletim' : 'Cadastrar Boletim'; ?></h2>
        <form method="POST" action="">
            <!-- Primeira Linha: Número, Data e Tipo de Boletim -->
            <div class="row">
                <div class="col-md-4 col-4 mb-2">
                    <label for="bole_numero" class="form-label">Número do Boletim</label>
                    <input type="number" class="form-control" id="bole_numero" name="bole_numero" 
                    value="<?php echo $bole_cod > 0 ? htmlspecialchars($boletim['bole_numero']) : htmlspecialchars($bole_numero); ?>" required>
                </div>
                <div class="col-md-4 col-4 mb-2">
                    <label for="bole_data_publicacao" class="form-label">Data de Publicação</label>
                    <input type="date" class="form-control" id="bole_data_publicacao" name="bole_data_publicacao" 
                    value="<?php echo isset($boletim['bole_data_publicacao']) ? htmlspecialchars(substr($boletim['bole_data_publicacao'], 0, 10)) : ($bole_data_publicacao ? htmlspecialchars($bole_data_publicacao) : ''); ?>" required>
                </div>
                <div class="col-md-4 col-4 mb-2">
                    <label for="fk_tipo_bole_cod" class="form-label">Tipo de Boletim</label>
                    <select class="form-select" id="fk_tipo_bole_cod" name="fk_tipo_bole_cod" required>
                        <option value="">Selecione</option>
                        <?php foreach ($tipos_boletim as $tipo): ?>
                            <option value="<?= htmlspecialchars($tipo['tipo_bole_cod']) ?>" 
                            <?php echo (isset($boletim['fk_tipo_bole_cod']) && $boletim['fk_tipo_bole_cod'] == $tipo['tipo_bole_cod']) ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($tipo['tipo_bole_descricao']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <!-- Segunda Linha: Subunidade -->
            <div class="row">
                <div class="col-md-12 col-12 mb-2">
                    <label for="fk_subu_cod" class="form-label">Subunidade</label>
                    <select class="form-select" id="fk_subu_cod" name="fk_subu_cod" required>
                        <option value="">Selecione</option>
                        <?php foreach ($subunidades as $subunidade): ?>
                            <option value="<?= htmlspecialchars($subunidade['subu_cod']) ?>" 
                            <?php echo (isset($boletim['fk_subu_cod']) && $boletim['fk_subu_cod'] == $subunidade['subu_cod']) ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($subunidade['descricao']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <!-- Terceira Linha: Boletim Aberto, Assinatura do DP e Assinatura do CMT -->
            <div class="row">
                <div class="col-4 mb-2">
                    <label for="bole_aberto" class="form-label">Boletim Aberto</label>
                    <select class="form-select" id="bole_aberto" name="bole_aberto" required>
                        <option value="0" <?php echo (isset($boletim['bole_aberto']) && $boletim['bole_aberto'] == '0') ? 'selected' : ''; ?>>Não</option>
                        <option value="1" <?php echo (isset($boletim['bole_aberto']) && $boletim['bole_aberto'] == '1') ? 'selected' : ''; ?>>Sim</option>
                    </select>
                </div>
                <div class="col-4 mb-2">
                    <label for="bole_ass_dp" class="form-label">Assinatura do DP</label>
                    <select class="form-select" id="bole_ass_dp" name="bole_ass_dp" required>
                        <option value="0" <?php echo (isset($boletim['bole_ass_dp']) && $boletim['bole_ass_dp'] == '0') ? 'selected' : ''; ?>>Não</option>
                        <option value="1" <?php echo (isset($boletim['bole_ass_dp']) && $boletim['bole_ass_dp'] == '1') ? 'selected' : ''; ?>>Sim</option>
                    </select>
                </div>
                <div class="col-4 mb-2">
                    <label for="bole_ass_cmt" class="form-label">Assinatura do CMT</label>
                    <select class="form-select" id="bole_ass_cmt" name="bole_ass_cmt" required>
                        <option value="0" <?php echo (isset($boletim['bole_ass_cmt']) && $boletim['bole_ass_cmt'] == '0') ? 'selected' : ''; ?>>Não</option>
                        <option value="1" <?php echo (isset($boletim['bole_ass_cmt']) && $boletim['bole_ass_cmt'] == '1') ? 'selected' : ''; ?>>Sim</option>
                    </select>
                </div>
            </div>
            
            <!-- Botões -->
            <div class="d-flex justify-content-end mt-3">
                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">Salvar</button>
                    <?php if ($bole_cod > 0): ?>
                        <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#visualizarPdfModal">Visualizar PDF</button>
                    <?php endif; ?>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Modal para Visualizar PDF -->
    <?php if ($bole_cod > 0): ?>
    <div class="modal fade" id="visualizarPdfModal" tabindex="-1" aria-labelledby="visualizarPdfModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="visualizarPdfModalLabel">Visualizar Boletim PDF</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <iframe id="pdfIframe" src="" width="100%" height="800px" frameborder="0"></iframe>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Bootstrap JS Bundle (Inclui Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($bole_cod > 0): ?>
                var visualizarPdfModal = document.getElementById('visualizarPdfModal');
                visualizarPdfModal.addEventListener('show.bs.modal', function () {
                    var pdfIframe = document.getElementById('pdfIframe');
                    var url = '../pdf/boletim_pdf.php?bole_cod=<?= htmlspecialchars($bole_cod) ?>';
                    pdfIframe.src = url;
                });

                visualizarPdfModal.addEventListener('hidden.bs.modal', function () {
                    var pdfIframe = document.getElementById('pdfIframe');
                    pdfIframe.src = '';
                });
            <?php endif; ?>
        });
    </script>
</body>
</html>
