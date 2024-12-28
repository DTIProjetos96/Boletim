<?php
require_once('content.php');

// Assinaturas e QR code
$sql_assinatura = "SELECT * FROM bg.vw_boletim_assinatura WHERE bole_cod = $id_boletim";
sc_lookup(ds, "select count(*) FROM bg.vw_boletim_assinatura WHERE bole_cod = $id_boletim");

if ($ds[0][0]){
    sc_select(ds_ass,$sql_ass);
    $bb = 0;

    while($resu_ds_np_ass = $ds_ass ->FetchRow()){
        $pdf->SetDrawColor(160, 160, 160);
        $bb = $bb + 1;

        $pdf->SetTextColor(0,0,0); // texto azul
        $pdf->SetFont('helvetica', 'B', 12);// retirar negrito

        $nome_posto_graduacao = $resu_ds_np_ass['nome_posto_graduacao'];
        $unid_sigla = $resu_ds_np_ass['assi_conf_cod'];	  

        $pdf->SetFont('helvetica', 'I', 8);
        $pdf->Cell(190, 5, "(Assinado Eletronicamente)",0,1,'C');
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(190, 5, " ".$nome_posto_graduacao, 0, 1, 'C');

        $pdf->SetTextColor(0,0,0);  
        $pdf->SetDrawColor(160, 160, 160);
    }
}

// QR code
$id_boletim = 1;
$endereco_qrcod='https://pmrr.com.br/autentica?acao=documento_conferir'.$id_boletim.'';
QRcode::png($endereco_qrcod,"qrcod_doc_pm.png");

$pdf->Ln(5);
$pdf->Cell(190, 2, (' '), 0, 1, 'C',false);
$pdf->Image("qrcod_doc_pm.png", '8', '','30', '20', "png", '', 'C', false,100, '', false, false, 0, false, false, false);	
$pdf->Cell(190, 2, (' '), 0, 1, 'C',false);
$pdf->SetFont('helvetica', '', 9);
$pdf->SetX(38);
$pdf->MultiCell(165, 6, 'A autenticidade do documento pode ser conferida endereço www.pmrr.com.br/autentica informando o codigo verificador: '.$id_boletim.'', 0, 1, 'C', true);

$pdf->Output();
?>
