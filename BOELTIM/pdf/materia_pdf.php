<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);


require_once('../db.php'); // Inclui o arquivo de conexão
require_once(__DIR__ . '/../bibliotecas/tcpdf/tcpdf.php'); // Inclua o TCPDF
require_once(__DIR__ . '/../bibliotecas/phpqrcode/qrlib.php'); // Inclua o PHPQRCode, se necessário

// Criação do PDF
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('ARQUIMEDES');
$pdf->SetTitle('NOTA PARA BOLETIM');
$pdf->SetSubject('bg/bi/br');

// Remove header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Adiciona uma página
$pdf->AddPage('P', 'A4');

// Defina o código da matéria (pode ser passado por GET ou POST)
//   $mate_bole_cod = 8645;
 $mate_bole_cod = $_GET['mate_bole_cod']; // Passe o valor por GET ou POST


// Executa a consulta usando a conexão PDO
$sql = "SELECT mate_bole_cod, mate_bole_data, assu_espe_descricao, assu_gera_descricao, mate_bole_texto, unid_descricao, coma_descricao 
        FROM bg.vw_materia_boletim_movimentacao 
        WHERE mate_bole_cod = :mate_bole_cod";

$stmt = $pdo->prepare($sql);
$stmt->bindParam(':mate_bole_cod', $mate_bole_cod, PDO::PARAM_INT);
$stmt->execute();

$result = $stmt->fetch(PDO::FETCH_ASSOC);

if ($result) {
    // Use os dados recuperados para preencher o PDF
    $mate_bole_cod = $result['mate_bole_cod'];
    $mate_bole_data_doc = $result['mate_bole_data'];
    $assu_espe_descricao = $result['assu_espe_descricao'];
    $assu_gera_descricao = $result['assu_gera_descricao'];
    $mate_bole_texto = $result['mate_bole_texto'];
    $unid_sigla = $result['unid_descricao'];
    $coma_descricao = $result['coma_descricao'];

    // SQL - BUSCAR DATA
    $sql = "SELECT mate_bole_data AS data_bg,
                date_part('day', mate_bole_data) AS dia_bg,
                CASE date_part('month', mate_bole_data)
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
                date_part('Year', mate_bole_data) AS ano_bg     
            FROM bg.vw_materia_boletim_movimentacao
            WHERE mate_bole_cod = :mate_bole_cod";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':mate_bole_cod', $mate_bole_cod, PDO::PARAM_INT);
    $stmt->execute();
    $date_result = $stmt->fetch(PDO::FETCH_ASSOC);

    $dia_bg = $date_result['dia_bg'];
    $mes_bg = $date_result['mes_extenso'];
    $ano_bg = $date_result['ano_bg'];

    // BRASÃO DO GOVERNO
    $imagerr = file_get_contents('../img/grp__NM__img__NM__Brasao_RR.jpg');  
    $pdf->Image('@' . $imagerr, '95', '5', 18, 18, 'JPG', '', 'C', false, 300, '', false, false, 0, false, false, false);

    $pdf->Ln(13);

    $pdf->SetFont('helvetica', 'B',12);
    $pdf->Cell(190, 5, 'Governo do Estado de Roraima', 0, 1, 'C');
    $pdf->Cell(190, 5, 'Polícia Militar do Estado de Roraima', 0, 1, 'C');
    $pdf->Cell(190, 5, " ".$coma_descricao, 0, 1, 'C');
    $pdf->Cell(190, 5, " ".$unid_sigla, 0, 1, 'C');

    $pdf->SetFont('courier', 'I',10);
    $pdf->Cell(190, 5, '"Amazônia: patrimônio dos brasileiros"', 0, 1, 'C');

    $pdf->SetY(68);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(90, 5, ("NOTA PARA BOLETIM Nº ".$mate_bole_cod. "/".$ano_bg), 0, 0, 'L');
    $pdf->Cell(100, 5, ("Boa Vista - RR, ".$dia_bg. " de ".$mes_bg. " de ".$ano_bg), 0, 1, 'R', false);

    $pdf->SetY(83);

    // ASSUNTO GERAL
    $pdf->Ln();
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetFillColor(0, 255, 64);
    $pdf->Cell(190, 5, ('ASSUNTO GERAL:'), 1, 1, 'C', true);
    $pdf->Cell(190, 5, '', 0, 1, 'C');
    $pdf->Cell(190, 5, $assu_gera_descricao, 0, 1, 'C');
    $pdf->Ln();
    $pdf->SetFillColor(115, 185, 255);
    $pdf->Cell(190, 5, "ASSUNTO ESPECÍFICO: ", 1, 1, 'L', true);

    // ASSUNTO ESPECÍFICO
    $pdf->Ln();
    $pdf->Cell(190, 5, ("1 - ".$assu_espe_descricao), 0, 1, 'L');
    $pdf->Ln();

    // TEXTO DE MATÉRIA
    $pdf->WriteHTML($mate_bole_texto); 

    $pdf->Ln(15); // espaço do texto para assinatura

    // O código para listar policiais militares, assinatura, e rodapé deve ser adaptado
    // similar ao que foi feito com a consulta e inserção dos dados acima.
}

// Gere o PDF
$pdf->Output('nota_boletim.pdf', 'I');
?>
