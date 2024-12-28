<?php
include("cabecalho.php");
?>

<?php

require 'db.php'; // Conexão com o banco de dados

require 'bibliotecas/pdfparser-master/autoload.php'; // Inclui o autoloader da biblioteca



use Smalot\PdfParser\Parser;



if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['pdf_file'])) {

    $file = $_FILES['pdf_file'];



    if ($file['error'] === UPLOAD_ERR_OK && strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) === 'pdf') {

        $filename = $file['name'];

        $filepath = $file['tmp_name'];



        // Usa o PdfParser para extrair o conteúdo do PDF

        $parser = new Parser();

        $pdf = $parser->parseFile($filepath);

        $content = $pdf->getText();



        // Insere o conteúdo do texto extraído no banco de dados

        $stmt = $pdo->prepare("INSERT INTO bg.documentos_pdf (nome_arquivo, conteudo_pdf) VALUES (:nome_arquivo, :conteudo_pdf)");

        $stmt->bindParam(':nome_arquivo', $filename);

        $stmt->bindParam(':conteudo_pdf', $content);



        if ($stmt->execute()) {

            $documentoId = $pdo->lastInsertId();



            // Definir as expressões regulares para cada seção

            $partes = [

                '2ª PARTE – INSTRUÇÃO' => '/2ª PARTE – INSTRUÇÃO(.*?)(?=\dª PARTE)/s',

                '3ª PARTE – ASSUNTOS GERAIS E ADMINISTRATIVOS' => '/3ª PARTE – ASSUNTOS GERAIS E ADMINISTRATIVOS(.*?)(?=\dª PARTE)/s',

                '4ª PARTE – JUSTIÇA E DISCIPLINA' => '/4ª PARTE – JUSTIÇA E DISCIPLINA(.*?)(?=\dª PARTE)/s',

                '5ª PARTE – COMUNICAÇÃO SOCIAL' => '/5ª PARTE – COMUNICAÇÃO SOCIAL(.*?)(?=\dª PARTE|$)/s',

            ];



            foreach ($partes as $nome => $regex) {

                if (preg_match($regex, $content, $match)) {

                    $secaoConteudo = trim($match[1]);



                    // Insere o conteúdo da seção no banco de dados

                    $stmtSecao = $pdo->prepare("INSERT INTO bg.secoes_boletim (documento_id, secao_nome, conteudo) VALUES (:documento_id, :secao_nome, :conteudo)");

                    $stmtSecao->bindParam(':documento_id', $documentoId);

                    $stmtSecao->bindParam(':secao_nome', $nome);

                    $stmtSecao->bindParam(':conteudo', $secaoConteudo);

                    $stmtSecao->execute();

                    $secaoId = $pdo->lastInsertId();



                    // Verifica se a seção é "4ª PARTE – JUSTIÇA E DISCIPLINA" para extrair elogios

                    if ($nome === '4ª PARTE – JUSTIÇA E DISCIPLINA') {

                        $elogioRegex = '/elogio individual ao ([\w\s]+) \(matrícula (\d+)\)(.*?)(?=NOTA PARA BOLETIM GERAL|$)/i';

                        if (preg_match_all($elogioRegex, $secaoConteudo, $elogios, PREG_SET_ORDER)) {

                            foreach ($elogios as $elogio) {

                                $policialNome = trim($elogio[1]);

                                $matricula = trim($elogio[2]);

                                $conteudoElogio = trim($elogio[3]);



                                // Insere o elogio na tabela elogios

                                $stmtElogio = $pdo->prepare("INSERT INTO bg.elogios (secao_id, policial_nome, matricula, conteudo_elogio) VALUES (:secao_id, :policial_nome, :matricula, :conteudo_elogio)");

                                $stmtElogio->bindParam(':secao_id', $secaoId);

                                $stmtElogio->bindParam(':policial_nome', $policialNome);

                                $stmtElogio->bindParam(':matricula', $matricula);

                                $stmtElogio->bindParam(':conteudo_elogio', $conteudoElogio);

                                $stmtElogio->execute();

                            }

                        }

                    }

                }

            }



            echo "Arquivo PDF enviado, conteúdo armazenado e dados de elogios extraídos com sucesso!";

        } else {

            echo "Erro ao armazenar o conteúdo no banco de dados.";

        }

    } else {

        echo "Por favor, envie um arquivo PDF válido.";

    }

}

?>



<!DOCTYPE html>

<html lang="pt-br">

<head>

    <meta charset="UTF-8">

    <title>Upload de Arquivo PDF</title>

</head>

<body>

    <h1>Envie um Arquivo PDF</h1>

    <form action="" method="post" enctype="multipart/form-data">

        <input type="file" name="pdf_file" accept="application/pdf" required>

        <button type="submit">Enviar</button>

    </form>

</body>

</html>

