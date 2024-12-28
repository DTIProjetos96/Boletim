<?php
function generatePDFBody($pdf, $pdo, $id_boletim) {
    // Definindo as seções e as respectivas categorias de assuntos
    $sections = [
        '1. ASSUNTOS ADMINISTRATIVOS' => 1,
        '2. ASSUNTOS OPERACIONAIS' => 2,
        '3. ASSUNTOS MATERIAIS/INSTALAÇÕES' => 3,
        '4. ASSUNTOS FINANCEIROS' => 4,
        '5. ASSUNTOS DE JUSTIÇA' => 5,
        '6. ASSUNTOS DE SAÚDE' => 6
    ];

    foreach ($sections as $section_title => $category_id) {
        $pdf->AddPage(); // Adiciona uma nova página para cada seção

        // Configura o cabeçalho da seção
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(190, 7, $section_title, 0, 1, 'C');
        $pdf->Ln(5);

        // Consulta para obter os dados da seção atual
        $sql_section = "SELECT assu_espe_descricao, mate_bole_texto 
                        FROM bg.vw_nota_bg_publicada_sumario 
                        WHERE bole_cod = :id_boletim 
                        AND fk_suma_cod = :category_id 
                        ORDER BY bole_cod ASC";
        $stmt = $pdo->prepare($sql_section);
        $stmt->bindParam(':id_boletim', $id_boletim, PDO::PARAM_INT);
        $stmt->bindParam(':category_id', $category_id, PDO::PARAM_INT);
        $stmt->execute();
        $resultSet = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($resultSet) {
            foreach ($resultSet as $row) {
                $assu_espe_descricao = $row['assu_espe_descricao'];
                $assu_materia = $row['mate_bole_texto'];

                // Imprime a descrição do assunto e o texto da matéria
                $pdf->SetFont('helvetica', 'B', 10);
                $pdf->Cell(190, 7, $assu_espe_descricao, 0, 1, 'L');
                $pdf->SetFont('helvetica', '', 10);
                $pdf->WriteHTML($assu_materia);
                $pdf->Ln();
            }
        } else {
            // Caso não haja registros para a categoria
            $pdf->SetFont('helvetica', 'I', 10);
            $pdf->Cell(190, 7, "Sem registros nesta categoria.", 0, 1, 'C');
        }
    }
}
?>
