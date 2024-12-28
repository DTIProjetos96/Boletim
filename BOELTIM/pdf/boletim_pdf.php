<?php
// gerar_boletim.php

// Exibir erros para depuração (remova em produção)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Incluir a conexão com o banco de dados
require_once('../db.php'); 

// Incluir bibliotecas TCPDF e PHPQRCode
require_once(__DIR__ . '/../bibliotecas/tcpdf/tcpdf.php');
require_once(__DIR__ . '/../bibliotecas/phpqrcode/qrlib.php');

// Iniciar o log de depuração
$log_file = __DIR__ . '/debug_log.txt';
file_put_contents($log_file, "Início da execução do script.\n", FILE_APPEND);

// Definir o ID do boletim (obter via GET ou definir um valor fixo para teste)
if (isset($_GET['bole_cod'])) {
    $id_boletim = intval($_GET['bole_cod']);
} 

//else {       $id_boletim = 65;}


file_put_contents($log_file, "ID do boletim: $id_boletim\n", FILE_APPEND);

// Função para adicionar uma seção de assuntos
function adicionarAssunto($pdo, $id_boletim, $fk_suma_cod, $titulo, $link, $pdf, $log_file) {
    // Consulta SQL para obter os assuntos
    $sql = "SELECT * FROM bg.vw_nota_bg_publicada_sumario WHERE bole_cod = :id_boletim AND fk_suma_cod = :fk_suma_cod ORDER BY bole_cod ASC";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id_boletim', $id_boletim, PDO::PARAM_INT);
        $stmt->bindParam(':fk_suma_cod', $fk_suma_cod, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Definir cores: fundo amarelo e texto preto
        $pdf->SetFillColor(255, 255, 0); // Amarelo
        $pdf->SetTextColor(0, 0, 0);     // Preto
        $pdf->SetFont('helvetica', 'B', 10);
        
        // Adicionar título com link no sumário
        $pdf->Cell(190, 7, $titulo, 1, 1, 'C', true, $link);
        
        // Resetar cores para o restante do conteúdo
        $pdf->SetFillColor(255, 255, 255); // Branco
        $pdf->SetTextColor(0, 0, 0);       // Preto
        
        // Definir o destino do link na seção correspondente
        $pdf->SetLink($link);
        
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Ln(2);
        
        if ($result && count($result) > 0) {
            $contador = 0;
            foreach ($result as $row) {
                $contador++;
                $descricao = $row['assu_espe_descricao'];
                $materia = $row['mate_bole_texto'];
                
                file_put_contents($log_file, "Processando assunto: $descricao - $materia\n", FILE_APPEND);
                
                // Adicionar descrição do assunto
                $pdf->SetFont('helvetica', 'B', 12);
                $pdf->Cell(190, 7, "$contador. $descricao", 0, 1, 'L');
                
                // Adicionar matéria do assunto
                $pdf->SetFont('helvetica', '', 10);
                
                // Verificar se o conteúdo é HTML válido
                if (strip_tags($materia) != $materia) {
                    // Contém HTML
                    $pdf->writeHTML($materia, true, false, true, false, '');
                } else {
                    // Texto simples
                    $pdf->MultiCell(190, 7, $materia, 0, 'L', false, 1);
                }
                
                $pdf->Ln(2); // Espaçamento entre assuntos
            }
        } else {
            file_put_contents($log_file, "Nenhum dado encontrado para $titulo.\n", FILE_APPEND);
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 7, "Sem alteração", 0, 1, 'C');
        }
        
    } catch (PDOException $e) {
        file_put_contents($log_file, "Erro na consulta SQL ($titulo): " . $e->getMessage() . "\n", FILE_APPEND);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(190, 7, "Erro ao obter $titulo.", 0, 1, 'C');
    }
}

// Função para adicionar assinaturas
function adicionarAssinaturas($pdo, $id_boletim, $pdf, $log_file) {
    // Consulta SQL para obter assinaturas
    $sql_assinaturas = "SELECT * FROM bg.vw_boletim_assinatura WHERE bole_cod = :id_boletim";
    
    try {
        $stmt_assinaturas = $pdo->prepare($sql_assinaturas);
        $stmt_assinaturas->bindParam(':id_boletim', $id_boletim, PDO::PARAM_INT);
        $stmt_assinaturas->execute();
        
        $assinaturas = $stmt_assinaturas->fetchAll(PDO::FETCH_ASSOC);
        
        if ($assinaturas && count($assinaturas) > 0) {
            foreach ($assinaturas as $assinatura) {
                $nome_posto_graduacao = $assinatura['nome_posto_graduacao'];
                $data_assinatura = $assinatura['data']; // Supondo que há um campo 'data'
                $hora_assinatura = $assinatura['hh_mm_ss']; // Supondo que há um campo 'hh_mm_ss'
                
                // Adicionar assinatura eletrônica
                $pdf->SetFont('helvetica', 'I', 8);
                $pdf->Cell(190, 5, "(Assinado Eletronicamente)", 0, 1, 'C');
                $pdf->SetFont('helvetica', 'B', 10);
                $pdf->Cell(190, 5, " " . $nome_posto_graduacao, 0, 1, 'C');
                $pdf->SetTextColor(0, 0, 0);	  
                $pdf->SetDrawColor(160, 160, 160);
            }
        } else {
            file_put_contents($log_file, "Nenhuma assinatura encontrada para o boletim $id_boletim.\n", FILE_APPEND);
        }
    } catch (PDOException $e) {
        file_put_contents($log_file, "Erro na consulta SQL (assinaturas): " . $e->getMessage() . "\n", FILE_APPEND);
    }
}

// Função para gerar QR Code
function gerarQRCode($id_boletim, $pdf, $log_file) {
    $endereco_qrcod = 'https://pmrr.com.br/autentica?acao=documento_conferir=' . $id_boletim;
    $qr_path = __DIR__ . '/qrcod_doc_pm.png';
    QRcode::png($endereco_qrcod, $qr_path);
    file_put_contents($log_file, "QR Code gerado em: $qr_path\n", FILE_APPEND);
    
    $pdf->Ln(5);
    $pdf->Cell(190, 2, ' ', 0, 1, 'C', false);
    $pdf->Image($qr_path, '8', '', '30', '20', "PNG", '', 'C', false, 100, '', false, false, 0, false, false, false);
    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetX(38);
    $pdf->MultiCell(165, 6, 'A autenticidade do documento pode ser conferida no endereço www.pmrr.com.br/autentica informando o código verificador: ' . $id_boletim, 0, 'C', true);
}

// Consulta SQL para obter o cabeçalho
$sql_cabecalho = "SELECT 
    CONCAT('BOLETIM: ', bo.bole_numero) AS boletim_numero,
    CONCAT('BOA VISTA, RORAIMA, ', 
        CASE EXTRACT(MONTH FROM bo.bole_data_publicacao)::INTEGER
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
        END, 
        ' de ', EXTRACT(YEAR FROM bo.bole_data_publicacao)::INTEGER
    ) AS data_publicacao,
    'PÁGINA' AS pagina
FROM 
    bg.boletim bo
    INNER JOIN bg.tipo_boletim tb ON tb.tipo_bole_cod = bo.fk_tipo_bole_cod
WHERE
    bo.bole_cod = :id_boletim";

// Preparar e executar a consulta usando PDO
try {
    $stmt_cabecalho = $pdo->prepare($sql_cabecalho);
    $stmt_cabecalho->bindParam(':id_boletim', $id_boletim, PDO::PARAM_INT);
    $stmt_cabecalho->execute();
    
    $rs_cabecalho = $stmt_cabecalho->fetch(PDO::FETCH_ASSOC);
    
    if ($rs_cabecalho) {
        $boletim_numero = $rs_cabecalho['boletim_numero'];
        $data_publicacao = $rs_cabecalho['data_publicacao'];
        $pagina = $rs_cabecalho['pagina'];
        
        file_put_contents($log_file, "Boletim Número: $boletim_numero\nData Publicação: $data_publicacao\n", FILE_APPEND);
    } else {
        file_put_contents($log_file, "Nenhuma data de publicação encontrada para o boletim $id_boletim.\n", FILE_APPEND);
        die("Nenhuma data de publicação encontrada para o boletim.");
    }
} catch (PDOException $e) {
    file_put_contents($log_file, "Erro na consulta SQL (cabecalho): " . $e->getMessage() . "\n", FILE_APPEND);
    die("Erro na consulta SQL (cabecalho). Consulte o log para mais detalhes.");
}

// Configurações iniciais do PDF
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Metadados do PDF
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Arquimedes');
$pdf->SetTitle('BOLETIM GERAL');
$pdf->SetSubject('bg/bi/br');

// Configurações de cabeçalho e rodapé
$pdf->setPrintHeader(false); // Desativar cabeçalho padrão
$pdf->setPrintFooter(false); // Desativar rodapé padrão

// Configurações de página e margens
$pdf->SetMargins(10, 15, 5);
$pdf->SetAutoPageBreak(true, 10);
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// Adicionando uma página
$pdf->AddPage('P', 'A4');
file_put_contents($log_file, "Página adicionada ao PDF.\n", FILE_APPEND);

// Adicionar o cabeçalho personalizado
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 5, "ESTADO DE RORAIMA", 0, 1, 'C');
$pdf->Cell(0, 5, "SECRETARIA DE ESTADO DA SEGURANÇA PÚBLICA", 0, 1, 'C');
$pdf->Cell(0, 5, "POLÍCIA MILITAR DO ESTADO DE RORAIMA", 0, 1, 'C');
$pdf->Cell(0, 5, "QUARTEL DO COMANDO GERAL GOVERNADOR OTTOMAR DE SOUSA PINTO", 0, 1, 'C');
$pdf->Cell(0, 5, $boletim_numero, 0, 1, 'C');

$pdf->Ln(25);

// SUMÁRIO DA CAPA DO BG
$pdf->SetFont('helvetica', 'B', 10);
$links = array();

// Definindo links para as seções
$links['administrativos'] = $pdf->AddLink();
$links['operacionais'] = $pdf->AddLink();
$links['materiais'] = $pdf->AddLink();
$links['financeiros'] = $pdf->AddLink();
$links['justica'] = $pdf->AddLink();
$links['saude'] = $pdf->AddLink();

// Adicionando os itens do sumário com links
$pdf->SetX(28);
$pdf->Cell(190, 7, "1. ASSUNTOS ADMINISTRATIVOS:", 0, 1, 'L', false, $links['administrativos']);

$pdf->SetX(28);
$pdf->Cell(190, 7, "2. ASSUNTOS OPERACIONAIS:", 0, 1, 'L', false, $links['operacionais']);

$pdf->SetX(28);
$pdf->Cell(190, 7, "3. ASSUNTOS MATERIAIS/INSTALAÇÕES:", 0, 1, 'L', false, $links['materiais']);

$pdf->SetX(28);
$pdf->Cell(190, 7, "4. ASSUNTOS FINANCEIROS:", 0, 1, 'L', false, $links['financeiros']);

$pdf->SetX(28);
$pdf->Cell(190, 7, "5. ASSUNTOS DE JUSTIÇA:", 0, 1, 'L', false, $links['justica']);

$pdf->SetX(28);
$pdf->Cell(190, 7, "6. ASSUNTOS DE SAÚDE:", 0, 1, 'L', false, $links['saude']);

$pdf->Ln(50);

// Data do boletim
$pdf->SetFont('helvetica', '', 12);
$pdf->Cell(0, 7, "Boa Vista - RR, $data_publicacao", 0, 1, 'C', false);

// Adicionar imagens (brasão e background)
$imagem_bg = '../img/grp__NM__img__NM__bg.jpeg';
$imagem_brasao = '../img/grp__NM__img__NM__Brasao_RR.jpg';
$imagem_pm_grande = '../img/grp__NM__img__NM__Brasao_PMRR_grande.jpg';

if (file_exists($imagem_bg)) {
    $pdf->Image($imagem_bg, 30, 130, 150, 50, 'JPG', '', 'C', false, 300, '', false, false, 0, false, false, false);
    file_put_contents($log_file, "Imagem de background adicionada.\n", FILE_APPEND);
} else {
    file_put_contents($log_file, "Erro: Imagem de background não encontrada.\n", FILE_APPEND);
}

if (file_exists($imagem_brasao)) {
    $pdf->Image($imagem_brasao, 95, 5, 18, 18, 'JPG', '', 'C', false, 300, '', false, false, 0, false, false, false);
    file_put_contents($log_file, "Imagem do brasão adicionada.\n", FILE_APPEND);
} else {
    file_put_contents($log_file, "Erro: Imagem do brasão não encontrada.\n", FILE_APPEND);
}

$pdf->SetFont('helvetica', 'B', 16);
$pdf->Ln(25);
$pdf->Cell(0, 5, "BOLETIM ELETRÔNICO DA POLÍCIA MILITAR", 0, 1, 'C');
$pdf->Cell(0, 5, "DE RORAIMA", 0, 1, 'C');
$pdf->Ln();
$pdf->Cell(0, 5, "BGPM/" . date('Y'), 0, 1, 'C');
$pdf->Ln(50);

$pdf->SetFont('helvetica', 'B', 8);
$pdf->Cell(0, 7, "Boa Vista - RR, $data_publicacao", 0, 1, 'C', false);
$pdf->Ln(5);

// Adicionar uma nova página para conteúdo
$pdf->AddPage('P', 'A4');
file_put_contents($log_file, "Nova página adicionada para conteúdo.\n", FILE_APPEND);

// Adicionar imagens adicionais
$imagem_brasao_dup = '../img/grp__NM__img__NM__Brasao_RR.jpg';
$imagem_pm_dup = '../img/grp__NM__img__NM__Brasao_PMRR_grande.jpg';

if (file_exists($imagem_brasao_dup)) {
    $pdf->Image($imagem_brasao_dup, 20, 10, 18, 18, 'JPG', '', 'C', false, 300, '', false, false, 0, false, false, false);
    file_put_contents($log_file, "Imagem do brasão duplicada adicionada.\n", FILE_APPEND);
} else {
    file_put_contents($log_file, "Erro: Imagem do brasão duplicada não encontrada.\n", FILE_APPEND);
}

if (file_exists($imagem_pm_dup)) {
    $pdf->Image($imagem_pm_dup, 175, 10, 18, 18, 'JPG', '', 'C', false, 300, '', false, false, 0, false, false, false);
    file_put_contents($log_file, "Imagem PM grande adicionada.\n", FILE_APPEND);
} else {
    file_put_contents($log_file, "Erro: Imagem PM grande não encontrada.\n", FILE_APPEND);
}

$pdf->SetY(15);	
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 5, 'GOVERNO DO ESTADO DE RORAIMA', 0, 1, 'C');
$pdf->Cell(0, 5, 'POLÍCIA MILITAR DO ESTADO DE RORAIMA', 0, 1, 'C');
$pdf->SetFont('courier', 'I', 10);
$pdf->Cell(0, 5, '"Amazônia: patrimônio dos brasileiros"', 0, 1, 'C');

// -------------------------------------------- FIM DE CABEÇALHO

// Adicionar Assuntos Administrativos
adicionarAssunto($pdo, $id_boletim, 1, "1. ASSUNTOS ADMINISTRATIVOS:", $links['administrativos'], $pdf, $log_file);

// Adicionar Assuntos Operacionais
adicionarAssunto($pdo, $id_boletim, 2, "2. ASSUNTOS OPERACIONAIS:", $links['operacionais'], $pdf, $log_file);

// Adicionar Assuntos Materiais/Instalações
adicionarAssunto($pdo, $id_boletim, 3, "3. ASSUNTOS MATERIAIS/INSTALAÇÕES:", $links['materiais'], $pdf, $log_file);

// Adicionar Assuntos Financeiros
adicionarAssunto($pdo, $id_boletim, 4, "4. ASSUNTOS FINANCEIROS:", $links['financeiros'], $pdf, $log_file);

// Adicionar Assuntos de Justiça
adicionarAssunto($pdo, $id_boletim, 5, "5. ASSUNTOS DE JUSTIÇA:", $links['justica'], $pdf, $log_file);

// Adicionar Assuntos de Saúde
adicionarAssunto($pdo, $id_boletim, 6, "6. ASSUNTOS DE SAÚDE:", $links['saude'], $pdf, $log_file);

// ----------------------------- Assinaturas e QR Code -----------------------------

// Adicionar assinaturas
adicionarAssinaturas($pdo, $id_boletim, $pdf, $log_file);

// Gerar QRCode e adicionar ao PDF
gerarQRCode($id_boletim, $pdf, $log_file);

// Linha de rodapé
$pdf->Line(10, 235, 200, 235);

// Finalizar o log
file_put_contents($log_file, "Fim da execução do script.\n", FILE_APPEND);

// Gerar o PDF no navegador
$pdf->Output('boletim_geral.pdf', 'I');
?>
