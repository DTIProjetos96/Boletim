<?php
include("cabecalho.php");
?>


<?php

require 'db.php'; // Conexão com o banco de dados



if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['termo'])) {

    $termo = $_GET['termo'];

    $termoBusca = '%' . $termo . '%';



    echo "<h2>Resultados para '{$termo}':</h2><ul>";



    // Pesquisa no conteúdo dos PDFs para encontrar o termo

    $stmt = $pdo->prepare("SELECT id, nome_arquivo, conteudo_pdf FROM bg.documentos_pdf WHERE conteudo_pdf ILIKE :termo");

    $stmt->bindParam(':termo', $termoBusca);

    $stmt->execute();

    $resultados = $stmt->fetchAll();



    if ($resultados) {

        foreach ($resultados as $documento) {

            echo "<li><strong>Arquivo:</strong> {$documento['nome_arquivo']} (ID: {$documento['id']})</li>";



            // Pesquisa nas seções para encontrar o termo

            $stmtSecao = $pdo->prepare("SELECT id, secao_nome, conteudo FROM bg.secoes_boletim WHERE documento_id = :documento_id AND conteudo ILIKE :termo");

            $stmtSecao->bindParam(':documento_id', $documento['id']);

            $stmtSecao->bindParam(':termo', $termoBusca);

            $stmtSecao->execute();

            $secoes = $stmtSecao->fetchAll();



            if ($secoes) {

                foreach ($secoes as $secao) {

                    echo "<h3>Seção: {$secao['secao_nome']}</h3>";

                    echo "<p>{$secao['conteudo']}</p>";



                    // Pesquisa elogios específicos nesta seção

                    $stmtElogio = $pdo->prepare("SELECT policial_nome, matricula, conteudo_elogio FROM bg.elogios WHERE secao_id = :secao_id AND conteudo_elogio ILIKE :termo");

                    $stmtElogio->bindParam(':secao_id', $secao['id']);

                    $stmtElogio->bindParam(':termo', $termoBusca);

                    $stmtElogio->execute();

                    $elogios = $stmtElogio->fetchAll();



                    if ($elogios) {

                        echo "<h4>Elogios Encontrados:</h4><ul>";

                        foreach ($elogios as $elogio) {

                            echo "<li><strong>Policial:</strong> {$elogio['policial_nome']} (Matrícula: {$elogio['matricula']})<br>{$elogio['conteudo_elogio']}</li>";

                        }

                        echo "</ul>";

                    }

                }

            }

        }

    } else {

        echo "<p>Nenhum resultado encontrado para '{$termo}'.</p>";

    }

    echo "</ul>";

}

?>



<!DOCTYPE html>

<html lang="pt-br">

<head>

    <meta charset="UTF-8">

    <title>Pesquisar no Conteúdo dos PDFs</title>

</head>

<body>

    <h1>Pesquisar no Conteúdo dos PDFs</h1>

    <form action="pesquisar.php" method="get">

        <input type="text" name="termo" placeholder="Digite o termo de pesquisa" required>

        <button type="submit">Pesquisar</button>

    </form>

</body>

</html>

