<?php
function generatePDFFooter($pdf, $pdo, $id_boletim) {
    // Consulta para a assinatura
    $sql_assinatura = "SELECT * FROM bg.vw_boletim_assinatura WHERE bole_cod = :id_boletim";

    $stmt = $pdo->prepare($sql_assinatura);
    $stmt->bindParam(':id_boletim', $id_boletim, PDO::PARAM_INT);
    $stmt->execute();
    $resultSetAss = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($resultSetAss as $row) {
        $nome_posto_graduacao = $row['nome_posto_graduacao'];
        $pdf->SetFont('helvetica', 'I', 8);
        $pdf->Cell(190, 5, "(Assinado Eletronicamente)", 0, 1, 'C');
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(190, 5, $nome_posto_graduacao, 0, 1, 'C');
    }

    // Geração do QR Code
    $endereco_qrcode = 'https://pmrr.com.br/autentica?acao=documento_conferir'.$id_boletim;
    QRcode::png($endereco_qrcode, "qrcode_doc_pm.png");

    $pdf->Image("qrcode_doc_pm.png", 8, '', 30, 20, "png", '', 'C', false, 100);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->MultiCell(165, 6, 'A autenticidade do documento pode ser conferida no endereço www.pmrr.com.br/autentica informando o código verificador: '.$id_boletim, 0, 'C');
}
?>
