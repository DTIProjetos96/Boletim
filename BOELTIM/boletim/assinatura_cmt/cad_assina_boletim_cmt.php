<?php
include('../../db.php'); // Ajuste o número de '../' conforme a estrutura de diretórios

// Iniciar sessão para armazenar mensagens temporárias
session_start();

// Ativar exibição de erros para depuração (remova em produção)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Captura o parâmetro 'bole_cod' da URL
$bole_cod = isset($_GET['bole_cod']) ? (int)$_GET['bole_cod'] : 0;

$boletim = [];
$bole_numero = 0;

// Verifica se é uma visualização de boletim existente ou cadastro de novo boletim
if ($bole_cod > 0) {
    // Recupera os dados do boletim para visualização
    $stmt = $pdo->prepare('SELECT * FROM bg.boletim WHERE bole_cod = :bole_cod');
    $stmt->execute(['bole_cod' => $bole_cod]);
    $boletim = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$boletim) {
        $_SESSION['message'] = ['type' => 'danger', 'text' => 'Boletim não encontrado!'];
        header('Location: cad_assina_boletim_cmt.php');
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

// Verifica se o formulário de assinatura foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assinar_boletim'])) {
    $bole_cod_post = isset($_POST['bole_cod']) ? (int)$_POST['bole_cod'] : 0;

    if ($bole_cod_post > 0) {
        try {
            // Iniciar transação
            $pdo->beginTransaction();

            // Valores a serem inseridos
            $id_lotacao = 16255; // Substitua com [usr_id_lotacao] se disponível
            $id_status = 4; // Cadastrado
            $id_post_grad = 7703; // Substitua com [usr_id_index_posto] se disponível
            $login = 403474; // Substitua com [usr_login] se disponível

            // Inserir na tabela assina_conferi_boletim
            $stmt_insert = $pdo->prepare('
                INSERT INTO bg.assina_conferi_boletim 
                    (fk_bole_cod, fk_poli_lota_cod, fk_stat_bole, fk_index_poli_post_grad_cod, fk_poli_mili_matricula) 
                VALUES 
                    (:fk_bole_cod, :fk_poli_lota_cod, :fk_stat_bole, :fk_index_poli_post_grad_cod, :fk_poli_mili_matricula)
            ');
            $stmt_insert->execute([
                'fk_bole_cod' => $bole_cod_post,
                'fk_poli_lota_cod' => $id_lotacao,
                'fk_stat_bole' => $id_status,
                'fk_index_poli_post_grad_cod' => $id_post_grad,
                'fk_poli_mili_matricula' => $login
            ]);

            // Atualizar o boletim para indicar que foi assinado pelo CMT
            $stmt_update = $pdo->prepare('
                UPDATE bg.boletim 
                SET bole_ass_cmt = 1 
                WHERE bole_cod = :bole_cod
            ');
            $stmt_update->execute(['bole_cod' => $bole_cod_post]);

            // Confirmar transação
            $pdo->commit();

            // Armazenar mensagem de sucesso na sessão
            $_SESSION['message'] = ['type' => 'success', 'text' => 'Boletim assinado com sucesso!'];

            // Redirecionar para a página com o parâmetro 'bole_cod'
            header('Location: cad_assina_boletim_cmt.php?bole_cod=' . $bole_cod_post);
            exit;
        } catch (PDOException $e) {
            // Reverter transação em caso de erro
            $pdo->rollBack();
            $_SESSION['message'] = ['type' => 'danger', 'text' => 'Erro ao assinar o boletim: ' . htmlspecialchars($e->getMessage())];
            header('Location: cad_assina_boletim_cmt.php?bole_cod=' . $bole_cod_post);
            exit;
        }
    } else {
        $_SESSION['message'] = ['type' => 'danger', 'text' => 'Código de boletim inválido.'];
        header('Location: cad_assina_boletim_cmt.php');
        exit;
    }
}

// Verifica se o formulário principal foi enviado para cadastrar ou atualizar boletim
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['assinar_boletim'])) {
    try {
        if ($bole_cod > 0) {
            // Atualiza o boletim existente (se necessário, mas como os campos estão desabilitados, isso pode não ser necessário)
            // Mantive o código para permitir futuras edições caso os campos sejam habilitados novamente
            $stmt = $pdo->prepare('
                UPDATE bg.boletim 
                SET bole_numero = :bole_numero, fk_tipo_bole_cod = :fk_tipo_bole_cod,
                    bole_aberto = :bole_aberto, bole_ass_dp = :bole_ass_dp, bole_ass_cmt = :bole_ass_cmt, fk_subu_cod = :fk_subu_cod
                WHERE bole_cod = :bole_cod
            ');
            $stmt->execute([
                'bole_numero' => $_POST['bole_numero'],
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
                INSERT INTO bg.boletim (bole_numero, fk_tipo_bole_cod, bole_aberto, bole_ass_dp, bole_ass_cmt, fk_subu_cod)
                VALUES (:bole_numero, :fk_tipo_bole_cod, :bole_aberto, :bole_ass_dp, :bole_ass_cmt, :fk_subu_cod)
            ');
            $stmt->execute([
                'bole_numero' => $_POST['bole_numero'],
                'fk_tipo_bole_cod' => $_POST['fk_tipo_bole_cod'],
                'bole_aberto' => $_POST['bole_aberto'],
                'bole_ass_dp' => $_POST['bole_ass_dp'],
                'bole_ass_cmt' => $_POST['bole_ass_cmt'],
                'fk_subu_cod' => $_POST['fk_subu_cod']
            ]);

            // Obter o ID do boletim recém-criado
            $bole_cod = $pdo->lastInsertId();
        }

        // Armazenar mensagem de sucesso na sessão
        $_SESSION['message'] = ['type' => 'success', 'text' => 'Boletim salvo com sucesso!'];

        // Redirecionar para a página com o parâmetro 'bole_cod'
        header('Location: cad_assina_boletim_cmt.php?bole_cod=' . $bole_cod);
        exit;
    } catch (PDOException $e) {
        $_SESSION['message'] = ['type' => 'danger', 'text' => 'Erro ao processar a solicitação: ' . htmlspecialchars($e->getMessage())];
        header('Location: cad_assina_boletim_cmt.php' . ($bole_cod > 0 ? '?bole_cod=' . $bole_cod : ''));
        exit;
    }
}

// Recupera a mensagem da sessão, se houver
$message = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $bole_cod > 0 ? 'Assinar Boletim' : 'Cadastrar Boletim'; ?></title>
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
        /* Estilo para os indicadores de status */
        .status-indicator {
            display: inline-flex;
            align-items: center;
            font-size: 1rem;
        }
        .status-indicator .circle {
            height: 15px;
            width: 15px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 5px;
        }
        .status-indicator .circle.green {
            background-color: #28a745;
        }
        .status-indicator .circle.red {
            background-color: #dc3545;
        }
        /* Estilo para o iframe */
        #pdfIframe {
            width: 100%;
            height: 800px;
            border: none;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2><?php echo $bole_cod > 0 ? 'Assinar Boletim' : 'Cadastrar Boletim'; ?></h2>

        <!-- Exibir mensagens de feedback -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= htmlspecialchars($message['type']) ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($message['text']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <!-- Primeira Linha: Número e Tipo de Boletim -->
            <div class="row">
                <div class="col-md-6 col-6 mb-2">
                    <label for="bole_numero" class="form-label">Número do Boletim</label>
                    <input type="number" class="form-control" id="bole_numero" name="bole_numero" 
                    value="<?php echo $bole_cod > 0 ? htmlspecialchars($boletim['bole_numero']) : htmlspecialchars($bole_numero); ?>" required <?php echo ($bole_cod > 0) ? 'readonly' : ''; ?>>
                </div>
                <div class="col-md-6 col-6 mb-2">
                    <label for="fk_tipo_bole_cod" class="form-label">Tipo de Boletim</label>
                    <select class="form-select" id="fk_tipo_bole_cod" name="fk_tipo_bole_cod" required <?php echo ($bole_cod > 0) ? 'disabled' : ''; ?>>
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
                    <select class="form-select" id="fk_subu_cod" name="fk_subu_cod" required <?php echo ($bole_cod > 0) ? 'disabled' : ''; ?>>
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
                    <label class="form-label">Boletim Aberto</label>
                    <?php if ($bole_cod > 0): ?>
                        <div class="status-indicator">
                            <?php if ($boletim['bole_aberto'] == 1): ?>
                                <span class="circle green"></span> Sim
                            <?php else: ?>
                                <span class="circle red"></span> Não
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <select class="form-select" id="bole_aberto" name="bole_aberto" required>
                            <option value="0" <?php echo (isset($boletim['bole_aberto']) && $boletim['bole_aberto'] == '0') ? 'selected' : ''; ?>>Não</option>
                            <option value="1" <?php echo (isset($boletim['bole_aberto']) && $boletim['bole_aberto'] == '1') ? 'selected' : ''; ?>>Sim</option>
                        </select>
                    <?php endif; ?>
                </div>
                <div class="col-4 mb-2">
                    <label class="form-label">Assinatura do DP</label>
                    <?php if ($bole_cod > 0): ?>
                        <div class="status-indicator">
                            <?php if ($boletim['bole_ass_dp'] == 1): ?>
                                <span class="circle green"></span> Sim
                            <?php else: ?>
                                <span class="circle red"></span> Não
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <select class="form-select" id="bole_ass_dp" name="bole_ass_dp" required>
                            <option value="0" <?php echo (isset($boletim['bole_ass_dp']) && $boletim['bole_ass_dp'] == '0') ? 'selected' : ''; ?>>Não</option>
                            <option value="1" <?php echo (isset($boletim['bole_ass_dp']) && $boletim['bole_ass_dp'] == '1') ? 'selected' : ''; ?>>Sim</option>
                        </select>
                    <?php endif; ?>
                </div>
                <div class="col-4 mb-2">
                    <label class="form-label">Assinatura do CMT</label>
                    <?php if ($bole_cod > 0): ?>
                        <div class="status-indicator">
                            <?php if ($boletim['bole_ass_cmt'] == 1): ?>
                                <span class="circle green"></span> Sim
                            <?php else: ?>
                                <span class="circle red"></span> Não
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <select class="form-select" id="bole_ass_cmt" name="bole_ass_cmt" required>
                            <option value="0" <?php echo (isset($boletim['bole_ass_cmt']) && $boletim['bole_ass_cmt'] == '0') ? 'selected' : ''; ?>>Não</option>
                            <option value="1" <?php echo (isset($boletim['bole_ass_cmt']) && $boletim['bole_ass_cmt'] == '1') ? 'selected' : ''; ?>>Sim</option>
                        </select>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Botões -->
            <div class="d-flex justify-content-end mt-3">
                <div class="btn-group">
                    <?php if ($bole_cod > 0): ?>
                        <?php if ($boletim['bole_ass_cmt'] == 1): ?>
                            <button type="button" class="btn btn-success" disabled title="Boletim já assinado">
                                Assinar Boletim
                            </button>
                        <?php else: ?>
                            <!-- Botão para Assinar Boletim que abre o modal de confirmação -->
                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#confirmAssinarModal">
                                Assinar Boletim
                            </button>
                        <?php endif; ?>
                    <?php endif; ?>
                    <button type="button" class="btn btn-secondary" onclick="window.close();">Cancelar</button>
                </div>
            </div>

            <!-- Exibe o PDF abaixo dos botões, se for um boletim existente -->
            <?php if ($bole_cod > 0): ?>
                <iframe id="pdfIframe" src="../pdf/boletim_pdf.php?bole_cod=<?= htmlspecialchars($bole_cod) ?>" title="Boletim PDF"></iframe>
            <?php endif; ?>
        </form>

        <!-- Modal de Confirmação para Assinar Boletim -->
        <?php if ($bole_cod > 0 && $boletim['bole_ass_cmt'] != 1): ?>
            <div class="modal fade" id="confirmAssinarModal" tabindex="-1" aria-labelledby="confirmAssinarModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="POST" action="cad_assina_boletim_cmt.php">
                            <div class="modal-header">
                                <h5 class="modal-title" id="confirmAssinarModalLabel">Confirmar Assinatura</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                            </div>
                            <div class="modal-body">
                                <p>Você tem certeza que deseja assinar este boletim?</p>
                                <input type="hidden" name="bole_cod" value="<?= htmlspecialchars($bole_cod) ?>">
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" name="assinar_boletim" class="btn btn-success">Confirmar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </div>

    <!-- Bootstrap JS Bundle (Inclui Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Script para exibir mensagem quando o botão "Assinar Boletim" está desabilitado -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const assinarBtn = document.querySelector('button[data-bs-target="#confirmAssinarModal"]');
            if (assinarBtn) {
                assinarBtn.addEventListener('click', function(event) {
                    // Se o botão estiver desabilitado, exibe a mensagem
                    if (assinarBtn.disabled) {
                        alert('Boletim já assinado.');
                        event.preventDefault();
                    }
                });
            }
        });
    </script>
</body>
</html>
