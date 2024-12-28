<?php
function generatePDFHeader($pdf, $rs) {
    $dia_bg = $rs['dia_bg'];
    $mes_bg = $rs['mes_extenso'];
    $ano_bg = $rs['ano_bg'];

    $pdf->AddPage('P', 'A4');
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(190, 5, "ESTADO DE RORAIMA", 0, 1, 'C');
    $pdf->Cell(190, 5, "SECRETARIA DE ESTADO DA SEGURANÇA PÚBLICA", 0, 1, 'C');
    $pdf->Cell(190, 5, "POLÍCIA MILITAR DO ESTADO DE RORAIMA", 0, 1, 'C');
    $pdf->Cell(190, 5, "QUARTEL DO COMANDO GERAL GOVERNADOR OTTOMAR DE SOUSA PINTO", 0, 1, 'C');
    $pdf->Ln(25);

    $pdf->Cell(190, 7, "Boa Vista - RR, $dia_bg de $mes_bg de $ano_bg", 0, 1, 'C', false);
}
?>
