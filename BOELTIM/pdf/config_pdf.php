<?php
require_once(__DIR__ . '/../bibliotecas/tcpdf/tcpdf.php');
require_once(__DIR__ . '/../bibliotecas/phpqrcode/qrlib.php');

// Configurações iniciais do PDF
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Arquimedes');
$pdf->SetTitle('BOLETIM GERAL');
$pdf->SetSubject('bg/bi/br');

$pdf->setPrintHeader(true);
$pdf->setPrintFooter(true);

// Configurações de margem e quebra de página
$pdf->SetMargins('10', '15', '5');
$pdf->SetAutoPageBreak(true, 10);
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// Carrega as strings de idioma, se disponíveis
if (@file_exists(dirname(__FILE__) . '/lang/eng.php')) {
    require_once(dirname(__FILE__) . '/lang/eng.php');
    $pdf->setLanguageArray($l);
}
?>
