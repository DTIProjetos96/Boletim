<?php
require_once('../db.php'); // Inclui a conexão com o banco de dados

// Incluir bibliotecas TCPDF e PHPQRCode
require_once(__DIR__ . '/../bibliotecas/tcpdf/tcpdf.php');
require_once(__DIR__ . '/../bibliotecas/phpqrcode/qrlib.php');

// Carregar imagens
$imagerr = file_get_contents('../img/grp__NM__img__NM__Brasao_RR.jpg');  
$imagerr_bg = file_get_contents('../img/grp__NM__img__NM__bg.jpeg');  
$imagerr_brasao = file_get_contents('../img/grp__NM__img__NM__Brasao_RR.jpg');  
$imagepm = file_get_contents('../img/grp__NM__img__NM__Brasao_PMRR_grande.jpg');  

// Configurações iniciais do PDF
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Definindo o ID do boletim (Certifique-se de que $id_boletim esteja corretamente definido)
//$id_boletim = [bole_cod]; // Exemplo de valor, altere conforme necessário
$id_boletim = 65; // Exemplo de valor, altere conforme necessário


$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Autor');
$pdf->SetTitle('Boletim Geral');
$pdf->SetSubject('Assunto');

// Configurações de página e margens
$pdf->SetMargins(10, 15, 5);
$pdf->SetAutoPageBreak(true, 10);
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// Adicionando uma página
$pdf->AddPage('P', 'A4');

// Cabeçalho
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(190, 5, "ESTADO DE RORAIMA", 0, 1, 'C');
$pdf->Cell(190, 5, "SECRETARIA DE ESTADO DA SEGURANÇA PÚBLICA", 0, 1, 'C');
$pdf->Cell(190, 5, "POLÍCIA MILITAR DO ESTADO DE RORAIMA", 0, 1, 'C');
$pdf->Cell(190, 5, "QUARTEL DO COMANDO GERAL GOVERNADOR OTTOMAR DE SOUSA PINTO", 0, 1, 'C');
$pdf->Cell(190, 5, "$id_boletim", 0, 1, 'C');

$pdf->Ln(25);

// SUMÁRIO DA CAPA DO BG
$pdf->SetX(28);
$link_adm = $pdf->AddLink();
$pdf->Cell(190, 7, "1. ASSUNTOS ADMINISTRATIVOS:", 0, 1, 'L', false, $link_adm);

$pdf->SetX(28);
$link_oper = $pdf->AddLink();
$pdf->Cell(190, 7, "2. ASSUNTOS OPERACIONAIS:", 0, 1, 'L', false, $link_oper);

$pdf->SetX(28);
$link_mat_inst = $pdf->AddLink();
$pdf->Cell(190, 7, "3. ASSUNTOS MATERIAIS/INSTALAÇÕES:", 0, 1, 'L', false, $link_mat_inst);

$pdf->SetX(28);
$link_fin = $pdf->AddLink();
$pdf->Cell(190, 7, "4. ASSUNTOS FINANCEIROS:", 0, 1, 'L', false, $link_fin);

$pdf->SetX(28);
$link_just = $pdf->AddLink();
$pdf->Cell(190, 7, "5. ASSUNTOS DE JUSTIÇA:", 0, 1, 'L', false, $link_just);

$pdf->SetX(28);
$link_saude = $pdf->AddLink();
$pdf->Cell(190, 7, "6. ASSUNTOS DE SAÚDE:", 0, 1, 'L', false, $link_saude);

$pdf->Ln(50);

// Data do boletim
$sql_cabecalho = "SELECT bole_data_publicacao FROM bg.boletim WHERE bole_cod = $id_boletim";
$result_cabecalho = pg_query($dbconn, $sql_cabecalho);
$rs_cabecalho = pg_fetch_assoc($result_cabecalho);

if ($rs_cabecalho) {
    $dia_bg = date('d', strtotime($rs_cabecalho['bole_data_publicacao']));
    $mes_bg = date('F', strtotime($rs_cabecalho['bole_data_publicacao']));
    $ano_bg = date('Y', strtotime($rs_cabecalho['bole_data_publicacao']));

    $pdf->Cell(190, 7, "Boa Vista - RR, $dia_bg de $mes_bg de $ano_bg", 0, 1, 'C', false);
}

$pdf->AddPage('P', 'A4');

// Adicionando imagem do brasão
$pdf->Image('@'.$imagerr, 95, 5, 18, 18, 'JPG', '', 'C', false, 300, '', false, false, 0, false, false, false);

$pdf->Ln(25);
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(190, 5 , "BOLETIM ELETRÔNICO DA POLÍCIA MILITAR", 0, 1, 'C');
$pdf->Cell(190, 5 , "DE RORAIMA", 0, 1, 'C');
$pdf->Ln();
$pdf->Cell(190, 5 ,("BGPM/".$ano_bg), 0, 1, 'C');
$pdf->Ln(50);
$pdf->SetFont('helvetica', 'B', 8);
$pdf->Cell(190, 7, "Boa Vista - RR, $dia_bg de $mes_bg de $ano_bg", 0, 1, 'C', false);

$pdf->Ln(5);

$pdf->AddPage('P','A4');

$pdf->Image('@'.$imagerr_brasao, 20, 10, 18, 18, 'JPG', '', 'C', false, 300, '', false, false, 0, false, false, false);
$pdf->Image('@'.$imagepm, 175, 10, 18, 18, 'JPG', '', 'C', false, 300, '', false, false, 0, false, false, false);

$pdf->SetY(15);	
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(190, 5, 'GOVERNO DO ESTADO DE RORAIMA',0 ,1, 'C');
$pdf->Cell(190, 5, 'POLÍCIA MILITAR DO ESTADO DE RORAIMA', 0, 1, 'C');
$pdf->SetFont('courier', 'I',10);
$pdf->Cell(190, 5, '"Amazônia: patrimônio dos brasileiros"', 0, 1, 'C');

// Consultando os assuntos administrativos
$sql_assuntos_adm = "SELECT * FROM bg.vw_nota_bg_publicada_sumario WHERE bole_cod = $id_boletim and fk_suma_cod = 1";
$result_adm = pg_query($dbconn, $sql_assuntos_adm);

if ($result_adm && pg_num_rows($result_adm) > 0) {
    $bb = 0;
    while ($resu_ds_np = pg_fetch_assoc($result_adm)) {
        $pdf->SetDrawColor(160, 160, 160);
        $bb++;

        $assu_espe_descricao = $resu_ds_np['assu_espe_descricao'];
        $assu_materia = $resu_ds_np['mate_bole_texto'];	  

        $pdf->Cell(190, 7, "1.$bb $assu_espe_descricao", 0, 1, 'L');		  
        $pdf->SetFont('helvetica', '', 10);
        
        // Corrigir a chamada do método writeHTML
        $pdf->writeHTML($assu_materia, true, false, true, false, '');
        
        $pdf->Cell(190, 7, ' ', 0, 1, '');
    }
} else {
    // Exibir "sem alteração" se não houver dados
    $pdf->Cell(0, 0, "sem alteração", 0, 1, 'C');
}


// ASSUNTO OPERACIONAL
$pdf->bookmark('3 - ASSUNTOS OPERACIONAIS');
$pdf->Cell(190, 1, " ", 0, 1, 'L');
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetFillColor(255, 255, 0);

$pdf->SetLink($link_oper);
$pdf->Cell(190, 7, "2. ASSUNTOS OPERACIONAIS:", 1, 1, 'C', true);
$pdf->SetFont('helvetica', '', 12);
$pdf->SetTextColor(0, 0, 0); // preto
$pdf->Ln();

// Consultando os assuntos operacionais
$sql_nota_bg_publicada_sumario_assunto_operacional = "SELECT * FROM bg.vw_nota_bg_publicada_sumario WHERE bole_cod = $id_boletim AND fk_suma_cod = 2";
$result_operacional = pg_query($dbconn, $sql_nota_bg_publicada_sumario_assunto_operacional);
if ($result_operacional && pg_num_rows($result_operacional) > 0) {
    $bb = 0;
    while ($resu_ds_np = pg_fetch_assoc($result_operacional)) {
        $bb++;

        $pdf->SetDrawColor(160, 160, 160);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('helvetica', 'B', 12);

        $assu_espe_descricao = $resu_ds_np['assu_espe_descricao'];
        $assu_materia = $resu_ds_np['mate_bole_texto'];

        $pdf->Cell(190, 7, "2.$bb $assu_espe_descricao", 0, 1, 'L');		  
        $pdf->SetFont('helvetica', '', 10);
        $pdf->WriteHTML($assu_materia);
        $pdf->Cell(190, 7, ' ', 0, 1, '');
    }
} else {
    $pdf->Cell(0, 0, "sem alteração", 0, 1, 'C');
}

// Assinaturas e QRCode
$sql_assinatura = "SELECT * FROM bg.vw_boletim_assinatura WHERE bole_cod = $id_boletim";
$result_ass = pg_query($dbconn, $sql_assinatura);
if ($result_ass && pg_num_rows($result_ass) > 0) {
    while ($resu_ds_np_ass = pg_fetch_assoc($result_ass)) {
        $nome_posto_graduacao = $resu_ds_np_ass['nome_posto_graduacao'];

        $pdf->SetFont('helvetica', 'I', 8);
        $pdf->Cell(190, 5, "(Assinado Eletronicamente)", 0, 1, 'C');
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(190, 5, " ".$nome_posto_graduacao, 0, 1, 'C');
        $pdf->SetTextColor(0, 0, 0);	  
        $pdf->SetDrawColor(160, 160, 160);
    }
}

// Gerando o QRCode
$endereco_qrcod = 'https://pmrr.com.br/autentica?acao=documento_conferir'.$id_boletim;
QRcode::png($endereco_qrcod, "qrcod_doc_pm.png");

$pdf->Ln(5);
$pdf->Cell(190, 2, (' '), 0, 1, 'C', false);
$pdf->Image("qrcod_doc_pm.png", '8', '', '30', '20', "PNG", '', 'C', false, 100, '', false, false, 0, false, false, false);
$pdf->SetFillColor(255, 255, 255);
$pdf->SetFont('helvetica', '', 9);
$pdf->SetX(38);
$pdf->MultiCell(165, 6, 'A autenticidade do documento pode ser conferida endereço www.pmrr.com.br/autentica informando o código verificador: '.$id_boletim, 0, 1, 'C', true);

$pdf->Line(10, 235, 200, 235);

$pdf->Output();
?>
