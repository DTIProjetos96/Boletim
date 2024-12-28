<?php
require_once('header.php');

// Consulta SQL principal para conteúdo
$sql1 = "SELECT bo.bole_cod,
    bo.bole_numero AS n_bg,
    concat('BG Nº:0', bo.bole_numero) AS n_bg_extenso,
    date_part('day'::text, bo.bole_data_publicacao) AS dia_bg,
    CASE date_part('month'::text, bo.bole_data_publicacao)
        WHEN 1 THEN 'Janeiro'::text
        WHEN 2 THEN 'Fevereiro'::text
        WHEN 3 THEN 'Março'::text
        WHEN 4 THEN 'Abril'::text
        WHEN 5 THEN 'Maio'::text
        WHEN 6 THEN 'Junho'::text
        WHEN 7 THEN 'Julho'::text
        WHEN 8 THEN 'Agosto'::text
        WHEN 9 THEN 'Setembro'::text
        WHEN 10 THEN 'Outubro'::text
        WHEN 11 THEN 'Novembro'::text
        WHEN 12 THEN 'Dezembro'::text
        ELSE NULL::text 
    END AS mes_extenso,
    date_part('Year'::text, bo.bole_data_publicacao) AS ano_bg,
    concat('Boa Vista, Roraima', ' ',date_part('day'::text, bo.bole_data_publicacao) ,' de ',  CASE date_part('month'::text, bo.bole_data_publicacao)
        WHEN 1 THEN 'Janeiro'::text
        WHEN 2 THEN 'Fevereiro'::text
        WHEN 3 THEN 'Março'::text
        WHEN 4 THEN 'Abril'::text
        WHEN 5 THEN 'Maio'::text
        WHEN 6 THEN 'Junho'::text
        WHEN 7 THEN 'Julho'::text
        WHEN 8 THEN 'Agosto'::text
        WHEN 9 THEN 'Setembro'::text
        WHEN 10 THEN 'Outubro'::text
        WHEN 11 THEN 'Novembro'::text
        WHEN 12 THEN 'Dezembro'::text
        ELSE NULL::text 
    END,' ', date_part('Year'::text, bo.bole_data_publicacao)) as dia_mes_ano,
    tb.tipo_bole_descricao AS tipo_bg
     FROM bg.boletim bo
     inner JOIN bg.tipo_boletim tb ON tb.tipo_bole_cod = bo.fk_tipo_bole_cod
   where bo.bole_cod = $id_boletim";

sc_select(rs, $sql1);

$pdf->SetFont('courier','',12);

$dia_bg = $rs->fields[3];
$mes_bg = $rs->fields[4];
$ano_bg = $rs->fields[5];

$pdf->AddPage('p','A4');
$pdf->SetY(25);

// Adicionando conteúdo ao PDF
$pdf->Bookmark('1 - BOLETIM GERAL');
$pdf->SetFont('helvetica', 'B',12);
$pdf->Cell(190, '5' , "ESTADO DE RORAIMA", 0, 1, 'C');
$pdf->Cell(190, 5 , "SECRETARIA DE ESTADO DA SEGURANÇA PÚBLICA", 0, 1, 'C');
$pdf->Cell(190, 5 , "POLÍCIA MILITAR DO ESTADO DE RORAIMA", 0, 1, 'C');
$pdf->Cell(190, 5 , "QUARTEL DO COMANDO GERAL GOVERNADOR OTTOMAR DE SOUSA PINTO", 0, 1, 'C');

$pdf->Ln(25);

// SUMÁRIO DA CAPA DO BG

$pdf->SetX(28);
$link_adm = $pdf->AddLink();
$pdf->Cell(190, 7 , "1. ASSUNTOS ADMINISTRATIVOS:", 0, 1, 'L', false, $link_adm);

$pdf->SetX(28);
$link_oper = $pdf->AddLink();
$pdf->Cell(190, 7 , "2. ASSUNTOS OPERACIONAIS:", 0, 1, 'L', false, $link_oper);

$pdf->SetX(28);
$link_mat_inst = $pdf->AddLink();
$pdf->Cell(190, 7 , "3. ASSUNTOS MATERIAIS/INSTALAÇÕES:", 0, 1, 'L', false, $link_mat_inst);

$pdf->SetX(28);
$link_fin = $pdf->AddLink();
$pdf->Cell(190, 7 , "4. ASSUNTOS FINANCEIROS:", 0, 1, 'L', false, $link_fin);

$pdf->SetX(28);
$link_just = $pdf->AddLink();
$pdf->Cell(190, 7 , "5. ASSUNTOS DE JUSTIÇA:", 0, 1, 'L', false, $link_just);

$pdf->SetX(28);
$link_saude = $pdf->AddLink();
$pdf->Cell(190, 7 , "6. ASSUNTOS DE SAÚDE:", 0, 1, 'L', false, $link_saude);

$pdf->Ln(50);

$pdf->Cell(190, 7, ("Boa Vista - RR, ".$dia_bg. " de ".$mes_bg. " de ".$ano_bg), 0, 1, 'C', false);

$pdf->AddPage('p','A4');

$imagerr =file_get_contents('../_lib/img/grp__NM__img__NM__Brasao_RR.jpg');  
$imagepm =file_get_contents('../_lib/img/grp__NM__img__NM__Brasao_PMRR_grande.jpg');  

$pdf->Image('@'.$imagerr, '20', '10', 18, 18, 'JPG', '', 'C', false, 300, '', false, false, 0, false, false, false);
$pdf->Image('@'.$imagepm, '175', '10', 18, 18, 'JPG', '', 'C', false, 300, '', false, false, 0, false, false, false);

$pdf->SetY(15);	
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(190, 5, 'GOVERNO DO ESTADO DE RORAIMA',0 ,1, 'C');
$pdf->Cell(190, 5, 'POLÍCIA MILITAR DO ESTADO DE RORAIMA', 0, 1, 'C');
$pdf->SetFont('courier', 'I',10);
$pdf->Cell(190, 5, '"Amazônia: patrimônio dos brasileiros"', 0, 1, 'C');

// Verificando se o registro foi encontrado
if (false == {rs})
{
	sc_alert("Erro de acesso ao Banco de Dados!!");
}
elseif ({rs}->EOF)
{
    // Não encontrou o registro
	sc_error_message("Não existiram termos nesse valor!");
	sc_error_exit();
}
else // encontrou o registro
{
	$li = 0; // variável para contar o número de linhas do corpo
	$vCont = 0;

	// ASSUNTO ADMINISTRATIVO
	$pdf->bookmark('2 - ASSUNTOS ADMINISTRATIVOS');
	$pdf->Cell(190, 1, " ", 0, 1, 'L');
	$pdf->SetFont('helvetica', 'B', 10);
	$pdf->SetLink($link_adm);
	$pdf->SetFillColor(255, 255, 0);
	$pdf->SetTextColor(0,0,0);
	$pdf->Cell(190, 7 , "1. ASSUNTOS ADMINISTRATIVOS:", 1, 1, 'C', true, $link_adm);
	$pdf->SetFont('helvetica', '', 12);
	$pdf->SetTextColor(0,0,0);

	sc_lookup(ds, "select * from bg.vw_nota_bg_publicada_sumario where bole_cod = $id_boletim and fk_suma_cod = 1");
	if (!empty($ds)) {
	    foreach ($ds as $row) {
	        $pdf->SetFont('helvetica', '', 10);
	        $pdf->Cell(190, 7, $row['assu_espe_descricao'], 0, 1, 'L');
	        $pdf->MultiCell(0, 6, $row['mate_bole_texto'], 0, 'L', false);
	        $pdf->Ln();
	    }
	} else {
	    $pdf->Cell(190, 7, "Sem alterações", 0, 1, 'L');
	}

	// ASSUNTO OPERACIONAL
	$pdf->bookmark('3 - ASSUNTOS OPERACIONAIS');
	$pdf->Cell(190, 1, " ", 0, 1, 'L');
	$pdf->SetFont('helvetica', 'B', 10);
	$pdf->SetFillColor(255, 255, 0);
	$pdf->SetLink($link_oper);
	$pdf->Cell(190, 7 , "2. ASSUNTOS OPERACIONAIS:", 1, 1, 'C', true, $link_oper);
	$pdf->SetFont('helvetica', '', 12);
	$pdf->SetTextColor(0,0,0);

	sc_lookup(ds, "select * from bg.vw_nota_bg_publicada_sumario where bole_cod = $id_boletim and fk_suma_cod = 2");
	if (!empty($ds)) {
	    foreach ($ds as $row) {
	        $pdf->SetFont('helvetica', '', 10);
	        $pdf->Cell(190, 7, $row['assu_espe_descricao'], 0, 1, 'L');
	        $pdf->MultiCell(0, 6, $row['mate_bole_texto'], 0, 'L', false);
	        $pdf->Ln();
	    }
	} else {
	    $pdf->Cell(190, 7, "Sem alterações", 0, 1, 'L');
	}

	// ASSUNTO MATERIAL/INSTALAÇÃO
	$pdf->bookmark('4 - ASSUNTOS MATERIAIS/INSTALAÇÕES');
	$pdf->Cell(190, 1, " ", 0, 1, 'L');
	$pdf->SetFont('helvetica', 'B', 10);
	$pdf->SetFillColor(255, 255, 0);
	$pdf->SetLink($link_mat_inst);
	$pdf->Cell(190, 7 , "3. MATERIAL/INSTALAÇÕES:", 1, 1, 'C', true, $link_mat_inst);
	$pdf->SetFont('helvetica', '', 12);
	$pdf->SetTextColor(0,0,0);

	sc_lookup(ds, "select * from bg.vw_nota_bg_publicada_sumario where bole_cod = $id_boletim and fk_suma_cod = 3");
	if (!empty($ds)) {
	    foreach ($ds as $row) {
	        $pdf->SetFont('helvetica', '', 10);
	        $pdf->Cell(190, 7, $row['assu_espe_descricao'], 0, 1, 'L');
	        $pdf->MultiCell(0, 6, $row['mate_bole_texto'], 0, 'L', false);
	        $pdf->Ln();
	    }
	} else {
	    $pdf->Cell(190, 7, "Sem alterações", 0, 1, 'L');
	}

	// ASSUNTO FINANCEIRO
	$pdf->bookmark('5 - ASSUNTOS FINANCEIROS');
	$pdf->Cell(190, 1, " ", 0, 1, 'L');
	$pdf->SetFont('helvetica', 'B', 10);
	$pdf->SetFillColor(255, 255, 0);
	$pdf->SetLink($link_fin);
	$pdf->Cell(190, 7 , "4. ASSUNTOS FINANCEIROS:", 1, 1, 'C', true, $link_fin);
	$pdf->SetFont('helvetica', '', 12);
	$pdf->SetTextColor(0,0,0);

	sc_lookup(ds, "select * from bg.vw_nota_bg_publicada_sumario where bole_cod = $id_boletim and fk_suma_cod = 4");
	if (!empty($ds)) {
	    foreach ($ds as $row) {
	        $pdf->SetFont('helvetica', '', 10);
	        $pdf->Cell(190, 7, $row['assu_espe_descricao'], 0, 1, 'L');
	        $pdf->MultiCell(0, 6, $row['mate_bole_texto'], 0, 'L', false);
	        $pdf->Ln();
	    }
	} else {
	    $pdf->Cell(190, 7, "Sem alterações", 0, 1, 'L');
	}

	// ASSUNTO JUSTIÇA
	$pdf->bookmark('6 - ASSUNTOS DE JUSTIÇA');
	$pdf->Cell(190, 1, " ", 0, 1, 'L');
	$pdf->SetFont('helvetica', 'B', 10);
	$pdf->SetFillColor(255, 255, 0);
	$pdf->SetLink($link_just);
	$pdf->Cell(190, 7 , "5. JUSTIÇA:", 1, 1, 'C', true, $link_just);
	$pdf->SetFont('helvetica', '', 12);
	$pdf->SetTextColor(0,0,0);

	sc_lookup(ds, "select * from bg.vw_nota_bg_publicada_sumario where bole_cod = $id_boletim and fk_suma_cod = 5");
	if (!empty($ds)) {
	    foreach ($ds as $row) {
	        $pdf->SetFont('helvetica', '', 10);
	        $pdf->Cell(190, 7, $row['assu_espe_descricao'], 0, 1, 'L');
	        $pdf->MultiCell(0, 6, $row['mate_bole_texto'], 0, 'L', false);
	        $pdf->Ln();
	    }
	} else {
	    $pdf->Cell(190, 7, "Sem alterações", 0, 1, 'L');
	}

	// ASSUNTO SAÚDE
	$pdf->bookmark('7 - ASSUNTOS DE SAÚDE');
	$pdf->Cell(190, 1, " ", 0, 1, 'L');
	$pdf->SetFont('helvetica', 'B', 10);
	$pdf->SetFillColor(255, 255, 0);
	$pdf->SetLink($link_saude);
	$pdf->Cell(190, 7 , "6. SAÚDE:", 1, 1, 'C', true, $link_saude);
	$pdf->SetFont('helvetica', '', 12);
	$pdf->SetTextColor(0,0,0);

	sc_lookup(ds, "select * from bg.vw_nota_bg_publicada_sumario where bole_cod = $id_boletim and fk_suma_cod = 6");
	if (!empty($ds)) {
	    foreach ($ds as $row) {
	        $pdf->SetFont('helvetica', '', 10);
	        $pdf->Cell(190, 7, $row['assu_espe_descricao'], 0, 1, 'L');
	        $pdf->MultiCell(0, 6, $row['mate_bole_texto'], 0, 'L', false);
	        $pdf->Ln();
	    }
	} else {
	    $pdf->Cell(190, 7, "Sem alterações", 0, 1, 'L');
	}
}

$pdf->Ln();
?>
