<?php
header('Content-Type: application/json');

// Incluir o arquivo de conexão
require '../db.php';

try {
    // ============================  feriado ==============================================
    $feriadosQuery = $pdo->query("SELECT feri_cod, feri_data, feri_descricao, feri_ativo FROM feriados WHERE feri_ativo = 1");
    $feriados = $feriadosQuery->fetchAll(PDO::FETCH_ASSOC);

    $resultado = [];

    foreach ($feriados as $feriado) {
        $idevento = $feriado['feri_cod'];
        $dhinicio = $feriado['feri_data'];
        $descricao = $feriado['feri_descricao'];
        $situacao = $feriado['feri_ativo'];

        switch($situacao){
            case 'null':
            case '0': $color = 'cyan'; // INATIVO
            break;

            case '1': $color = 'Rosybrown'; // ATIVO
            break;

            default: $color = 'cyan'; // Default
        }

        $resultado[] = [
            'id' => $idevento,
            'title' => $descricao,
            'start' => $dhinicio,
            'allDay' => true,
            'color' => $color,
            'situacao' => $situacao
        ];
    }

    // ============================ fim feriado ===========================================

    // ============================ boletins ==============================================
    $boletinsQuery = $pdo->query("SELECT 
        bo.bole_cod,
        bo.bole_data_publicacao,
        bo.bole_aberto,
        CONCAT(tb.tipo_bole_sigla, ' - ', comandos.coma_sigla, ' - Nº:0', bo.bole_numero) AS n_bg_extenso,
        bo.bole_data_publicacao AS data_bg,
        EXTRACT(DAY FROM bo.bole_data_publicacao) AS dia_bg,
        CASE EXTRACT(MONTH FROM bo.bole_data_publicacao)
            WHEN 1 THEN 'Janeiro'
            WHEN 2 THEN 'Fevereiro'
            WHEN 3 THEN 'Março'
            WHEN 4 THEN 'Abril'
            WHEN 5 THEN 'Maio'
            WHEN 6 THEN 'Junho'
            WHEN 7 THEN 'Julho'
            WHEN 8 THEN 'Agosto'
            WHEN 9 THEN 'Setembro'
            WHEN 10 THEN 'Outubro'
            WHEN 11 THEN 'Novembro'
            WHEN 12 THEN 'Dezembro'
            ELSE NULL
        END AS mes_extenso,
        EXTRACT(YEAR FROM bo.bole_data_publicacao) AS ano_bg,
        tb.tipo_bole_descricao AS tipo_bg,
        CASE
            WHEN ((bo.bole_aberto = 0) AND (bo.bole_ass_dp = 0) AND (bo.bole_ass_cmt = 0)) THEN 'A'
            WHEN ((bo.bole_aberto = 1) AND (bo.bole_ass_dp = 0) AND (bo.bole_ass_cmt = 0)) THEN 'B'
            WHEN ((bo.bole_aberto = 1) AND (bo.bole_ass_dp = 1) AND (bo.bole_ass_cmt = 0)) THEN 'C'
            WHEN ((bo.bole_aberto = 1) AND (bo.bole_ass_dp = 1) AND (bo.bole_ass_cmt = 1)) THEN 'D'
            ELSE 'E'
        END AS status
    FROM bg.boletim bo
    JOIN bg.tipo_boletim tb ON tb.tipo_bole_cod = bo.fk_tipo_bole_cod
    JOIN subunidades ON bo.fk_subu_cod = subunidades.subu_cod
    JOIN unidades ON subunidades.fk_unid_cod = unidades.unid_cod
    JOIN comandos ON unidades.fk_coma_cod = comandos.coma_cod");

    $boletins = $boletinsQuery->fetchAll(PDO::FETCH_ASSOC);

    foreach ($boletins as $boletim) {
        $idevento = $boletim['bole_cod'];
        $dhinicio = $boletim['bole_data_publicacao'];
        $n_bg = $boletim['n_bg_extenso'];
        $situacao = $boletim['status'];

        switch($situacao){
            case 'null': 
            case 'A': $color = 'red'; // CADASTRADO
            break;

            case 'B': $color = 'DodgerBlue'; // AGUARDANDO SER CONFERIDO
            break;

            case 'C': $color = 'Orange'; // AGUARDANDO SER ASSINADO
            break;

            case 'D': $color = 'green'; // BOLETIM PRONTO
            break;

            default: $color = 'green'; // Default
        }

        $resultado[] = [
            'id' => $idevento,
            'title' => $n_bg,
            'start' => $dhinicio,
            'allDay' => true,
            'color' => $color,
            'situacao' => $situacao
        ];
    }

    // ============================ fim boletins ===========================================

    echo json_encode($resultado);

} catch (PDOException $e) {
    echo 'Erro ao conectar ao banco de dados: ' . $e->getMessage();
}
?>
