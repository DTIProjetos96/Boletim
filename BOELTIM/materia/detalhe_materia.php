<?php 
// detalhe_materia.php

// Ativar a exibição de erros para depuração
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Inclua o arquivo de conexão ao banco de dados
include('../db.php');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Detalhamento da Matéria</title>
    <!-- Inclusão do CSS do Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Estilos personalizados para melhor aparência */
        body {
            background-color: #f8f9fa;
            padding-top: 20px;
            padding-bottom: 20px;
        }
        .card-title {
            font-size: 1.5rem;
            font-weight: bold;
        }
        .info-label {
            font-weight: 600;
        }
        .badge-status {
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
<div class="container">
    <?php
    // Verifica se o ID da matéria foi passado como parâmetro
    if (isset($_GET['mate_bole_cod'])) {
        $mate_bole_cod = $_GET['mate_bole_cod'];

        // Depuração: Exibir o código recebido
        echo "<div class='alert alert-info'>Código da Matéria recebido: " . htmlspecialchars($mate_bole_cod) . "</div>";

        // Consulta para buscar os detalhes da matéria usando a nova SELECT com esquemas
        $sql_materia = "
        SELECT 
            mb.mate_bole_cod,
            mb.mate_bole_data,
            mb.mate_bole_ordem,
            mb.mate_bole_enviada,
            mb.mate_bole_p1,
            mb.mate_bole_publicada,
            mb.mate_bole_arquivada,
            mb.mate_bole_recebida,
            td.tipo_docu_descricao,
            mb.mate_bole_data_doc,
            mb.mate_bole_nr_doc,
            mb.mate_bole_pm_usuario,
            ae.assu_espe_descricao,
            ag.assu_gera_descricao,
            mb.mate_bole_texto,
            co.coma_descricao,
            co.coma_sigla,
            co.coma_cod,
            un.unid_descricao,
            un.unid_sigla,
            un.unid_cod,
            su.subu_descricao,
            su.subu_cod,
            mb.fk_subu_cod,
            mb.fk_mate_bole_referencia,
            mb.mate_bole_cmt, -- Certifique-se de que este campo existe na sua tabela
            CASE
                WHEN ((mb.mate_bole_enviada = 0) AND (mb.mate_bole_p1 = 1) AND (mb.mate_bole_recebida = 0) AND (mb.mate_bole_publicada = 0)) THEN 'Cadastrada'
                WHEN ((mb.mate_bole_enviada = 1) AND (mb.mate_bole_p1 = 0) AND (mb.mate_bole_recebida = 0) AND (mb.mate_bole_publicada = 0)) THEN 'Enviada'
                WHEN ((mb.mate_bole_enviada = 1) AND (mb.mate_bole_p1 = 1) AND (mb.mate_bole_recebida = 0) AND (mb.mate_bole_publicada = 0)) THEN 'Enviada e não recebida'
                WHEN ((mb.mate_bole_enviada = 1) AND (mb.mate_bole_p1 = 1) AND (mb.mate_bole_recebida = 1) AND (mb.mate_bole_publicada = 0)) THEN 'Recebida'
                WHEN ((mb.mate_bole_enviada = 1) AND (mb.mate_bole_p1 = 1) AND (mb.mate_bole_recebida = 1) AND (mb.mate_bole_publicada = 1)) THEN 'Recebida e publicada'
                ELSE 'Status desconhecido'
            END AS status,
            (
                SELECT 
                    array_to_string(
                        ARRAY(
                            SELECT 
                                CONCAT(pm.poli_mili_matricula, ' - ', pg.post_grad_sigla, ' ', pm.poli_mili_nome_guerra) 
                            FROM recursoshumanos.policial_militar pm
                            JOIN recursoshumanos.index_policial_posto_graduacao ippg ON pm.poli_mili_matricula = ippg.fk_poli_mili_matricula
                            JOIN recursoshumanos.posto_quadro pq ON ippg.fk_poqu_cod = pq.poqu_cod
                            JOIN recursoshumanos.posto_graduacao pg ON pq.fk_post_grad_cod = pg.post_grad_cod
                            JOIN recursoshumanos.policial_lotacao pl ON pm.poli_mili_matricula = pl.fk_poli_mili_matricula
                            JOIN bg.pessoa_materia pmate ON pl.poli_lota_cod = pmate.fk_poli_lota_cod
                            WHERE ippg.index_poli_post_grad_activo = TRUE 
                              AND pmate.fk_mate_bole_cod = mb.mate_bole_cod
                        ),
                        CASE
                            WHEN (array_length(
                                ARRAY(
                                    SELECT 
                                        CONCAT(pm.poli_mili_matricula, ' - ', pg.post_grad_sigla, ' ', pm.poli_mili_nome_guerra)
                                    FROM recursoshumanos.policial_militar pm
                                    JOIN recursoshumanos.index_policial_posto_graduacao ippg ON pm.poli_mili_matricula = ippg.fk_poli_mili_matricula
                                    JOIN recursoshumanos.posto_quadro pq ON ippg.fk_poqu_cod = pq.poqu_cod
                                    JOIN recursoshumanos.posto_graduacao pg ON pq.fk_post_grad_cod = pg.post_grad_cod
                                    JOIN recursoshumanos.policial_lotacao pl ON pm.poli_mili_matricula = pl.fk_poli_mili_matricula
                                    JOIN bg.pessoa_materia pmate ON pl.poli_lota_cod = pmate.fk_poli_lota_cod
                                    WHERE ippg.index_poli_post_grad_activo = TRUE 
                                      AND pmate.fk_mate_bole_cod = mb.mate_bole_cod
                                ), 1
                            ) > 1) THEN ' / '
                            ELSE ''
                        END
                    )
            ) AS pms
        FROM bg.materia_boletim mb
        JOIN bg.tipo_documento td ON td.tipo_docu_cod = mb.fk_tipo_docu_cod
        JOIN bg.assunto_especifico ae ON ae.assu_espe_cod = mb.fk_assu_espe_cod
        JOIN bg.assunto_geral ag ON ag.assu_gera_cod = ae.fk_assu_gera_cod
        JOIN bg.sumario suma ON suma.suma_cod = ag.fk_suma_cod
        JOIN public.subunidades su ON su.subu_cod = mb.fk_subu_cod
        JOIN public.unidades un ON su.fk_unid_cod = un.unid_cod
        JOIN public.comandos co ON un.fk_coma_cod = co.coma_cod 
        WHERE mb.mate_bole_cod = ? 
        ";

        try {
            $stmt = $pdo->prepare($sql_materia);
            $stmt->execute([$mate_bole_cod]);
            $materia = $stmt->fetch(PDO::FETCH_ASSOC);

            // Depuração: Verificar se a consulta retornou dados
            echo "<div class='alert alert-info'>Dados da Matéria recuperados: " . ($materia ? 'Sim' : 'Não') . "</div>";

            if ($materia) {
                // Exibir os dados (opcional: descomente para ver todos os dados)
                // echo "<pre>"; print_r($materia); echo "</pre>";

                ?>
                <div class="card mb-4 shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h2 class="card-title">Detalhamento da Matéria</h2>
                    </div>
                    <div class="card-body">
                        <!-- Primeira Linha: Código da Matéria, Data da Matéria, Tipo de Documento -->
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <p><span class="info-label">Código da Matéria:</span> <?php echo htmlspecialchars($materia['mate_bole_cod']); ?></p>
                            </div>
                            <div class="col-md-4">
                                <p><span class="info-label">Data da Matéria:</span> <?php echo htmlspecialchars($materia['mate_bole_data']); ?></p>
                            </div>
                            <div class="col-md-4">
                                <p><span class="info-label">Tipo de Documento:</span> <?php echo htmlspecialchars($materia['tipo_docu_descricao']); ?></p>
                            </div>
                        </div>

                        <!-- Segunda Linha: Assunto Específico, Assunto Geral, Número do Documento -->
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <p><span class="info-label">Assunto Específico:</span> <?php echo htmlspecialchars($materia['assu_espe_descricao']); ?></p>
                            </div>
                            <div class="col-md-4">
                                <p><span class="info-label">Assunto Geral:</span> <?php echo htmlspecialchars($materia['assu_gera_descricao']); ?></p>
                            </div>
                            <div class="col-md-4">
                                <p><span class="info-label">Número do Documento:</span> <?php echo htmlspecialchars($materia['mate_bole_nr_doc']); ?></p>
                            </div>
                        </div>

                        <!-- Terceira Linha: Comando, Unidade, Subunidade -->
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <p><span class="info-label">Comando:</span> <?php echo htmlspecialchars($materia['coma_descricao']); ?> (<strong><?php echo htmlspecialchars($materia['coma_sigla']); ?></strong>, Código: <?php echo htmlspecialchars($materia['coma_cod']); ?>)</p>
                            </div>
                            <div class="col-md-4">
                                <p><span class="info-label">Unidade:</span> <?php echo htmlspecialchars($materia['unid_descricao']); ?> (<strong><?php echo htmlspecialchars($materia['unid_sigla']); ?></strong>, Código: <?php echo htmlspecialchars($materia['unid_cod']); ?>)</p>
                            </div>
                            <div class="col-md-4">
                                <p><span class="info-label">Subunidade:</span> <?php echo htmlspecialchars($materia['subu_descricao']); ?> <span class="text-muted">(Código: <?php echo htmlspecialchars($materia['subu_cod']); ?>)</span></p>
                            </div>
                        </div>

                        <!-- Quarta Linha: Texto da Matéria (ocupando toda a largura) -->
                        <div class="row mb-3">
                            <div class="col-12">
                                <p><span class="info-label">Texto da Matéria:</span></p>
                                <div>
                                    <?php 
                                        // Permitir apenas certas tags HTML para segurança
                                        $allowed_tags = '<p><br><strong><em><ul><ol><li><a>'; // Adicione outras tags conforme necessário
                                        $texto_sanitizado = strip_tags($materia['mate_bole_texto'], $allowed_tags);
                                        echo $texto_sanitizado;
                                    ?>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <!-- Quinta Linha: Enviada, Primeira Página, Recebida -->
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <p><span class="info-label">Enviada:</span> <?php echo ($materia['mate_bole_enviada'] ? '<span class="badge bg-success badge-status">Sim</span>' : '<span class="badge bg-secondary badge-status">Não</span>'); ?></p>
                                <p><span class="info-label">Primeira Página:</span> <?php echo ($materia['mate_bole_p1'] ? '<span class="badge bg-success badge-status">Sim</span>' : '<span class="badge bg-secondary badge-status">Não</span>'); ?></p>
                                <p><span class="info-label">Recebida:</span> <?php echo ($materia['mate_bole_recebida'] ? '<span class="badge bg-success badge-status">Sim</span>' : '<span class="badge bg-secondary badge-status">Não</span>'); ?></p>
                            </div>
                            <div class="col-md-4">
                                <p><span class="info-label">Publicada:</span> <?php echo ($materia['mate_bole_publicada'] ? '<span class="badge bg-success badge-status">Sim</span>' : '<span class="badge bg-secondary badge-status">Não</span>'); ?></p>
                                <p><span class="info-label">Arquivada:</span> <?php echo ($materia['mate_bole_arquivada'] ? '<span class="badge bg-success badge-status">Sim</span>' : '<span class="badge bg-secondary badge-status">Não</span>'); ?></p>
                                <p><span class="info-label">Status:</span> 
                                    <?php 
                                        switch ($materia['status']) {
                                            case 'Cadastrada':
                                                echo '<span class="badge bg-info text-dark">' . htmlspecialchars($materia['status']) . '</span>';
                                                break;
                                            case 'Enviada':
                                                echo '<span class="badge bg-primary">' . htmlspecialchars($materia['status']) . '</span>';
                                                break;
                                            case 'Enviada e não recebida':
                                                echo '<span class="badge bg-warning text-dark">' . htmlspecialchars($materia['status']) . '</span>';
                                                break;
                                            case 'Recebida':
                                                echo '<span class="badge bg-success">' . htmlspecialchars($materia['status']) . '</span>';
                                                break;
                                            case 'Recebida e publicada':
                                                echo '<span class="badge bg-dark">' . htmlspecialchars($materia['status']) . '</span>';
                                                break;
                                            default:
                                                echo '<span class="badge bg-secondary">' . htmlspecialchars($materia['status']) . '</span>';
                                        }
                                    ?>
                                </p>
                            </div>
                            <div class="col-md-4">
                                <p><span class="info-label">Usuário PM:</span> <?php echo htmlspecialchars($materia['mate_bole_pm_usuario']); ?></p>
                                <p><span class="info-label">Policiais Relacionados:</span> <?php echo htmlspecialchars($materia['pms']); ?></p>
                            </div>
                        </div>

                        <!-- Comentário (se existir) -->
                     
                    </div>
                </div>
                <?php
            } else {
                echo "<div class='alert alert-warning' role='alert'>Matéria não encontrada.</div>";
            }
        } catch (PDOException $e) {
            // Tratamento de erro: Exibir mensagem amigável ao usuário
            echo "<div class='alert alert-danger' role='alert'>Erro ao recuperar os dados da Matéria: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    } else {
        echo "<div class='alert alert-info' role='alert'>Código da matéria não fornecido.</div>";
    }
    ?>
</div>

<!-- Inclusão do JavaScript do Bootstrap (opcional) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
