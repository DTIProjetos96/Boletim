<?php
$id_boletim = 60; // Defina seu ID de boletim

$sql1 = "SELECT bo.bole_cod, bo.bole_numero AS n_bg, 
                date_part('day'::text, bo.bole_data_publicacao) AS dia_bg,
                CASE date_part('month'::text, bo.bole_data_publicacao)
                    WHEN 1 THEN 'Janeiro'::text
                    WHEN 2 THEN 'Fevereiro'::text
                    // outros meses...
                    WHEN 12 THEN 'Dezembro'::text
                    ELSE NULL::text 
                END AS mes_extenso,
                date_part('Year'::text, bo.bole_data_publicacao) AS ano_bg
            FROM bg.boletim bo
            INNER JOIN bg.tipo_boletim tb ON tb.tipo_bole_cod = bo.fk_tipo_bole_cod
            WHERE bo.bole_cod = :id_boletim";

$stmt = $pdo->prepare($sql1);
$stmt->bindParam(':id_boletim', $id_boletim, PDO::PARAM_INT);
$stmt->execute();
$rs = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$rs) {
    echo "Erro de acesso ao Banco de Dados!";
    exit();
}
?>
