<?php
include("valida_session.php");

?>



<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Boletins Gerais - Página Inicial</title>
    <!-- Inclui Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background-color: #f8f9fa;
        }
        .navbar {
            margin-bottom: 0;
        }
        .frame-container {
            width: 100%;
            height: calc(100vh - 56px);
            border: none;
            margin-top: -4px;
            overflow: hidden;
        }
        .navbar-nav .nav-item .dropdown-menu {
            display: none;
        }
        .navbar-nav .nav-item:hover .dropdown-menu {
            display: block;
        }
        .navbar-brand {
            font-weight: bold;
            color: #007bff !important;
        }
        .navbar-light .navbar-nav .nav-link {
            color: #007bff;
        }
        .navbar-light .navbar-nav .nav-link:hover {
            color: #0056b3;
        }
    </style>
</head>
<body>

<!--Bem vindo Fulano de tal -->
<div class="container mt-5 d-flex justify-content-end position-fixed" style="right: 0; top: 0;">
    <h6>
        Bem-vindo <?= $posto ?> <?= $graduacao ?> <?= $guerra ?>
        (<?= $subunidade ?> | <?= $unidade ?> | <?= $coma_sigla ?>)
        <a href="../boletim/login/logout.php" class="btn btn-danger btn-sm ms-3">Sair</a>
    </h6>
</div>
	
    <!-- Barra de Navegação -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <a class="navbar-brand" href="#">Boletins Gerais</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" href="boletins.php" target="contentFrame">Inicial</a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Anos Anteriores
                    </a>
                    <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                        <a class="dropdown-item" href="boletim_anos_anteriores.php?ano=2023" target="contentFrame">2023</a>
                        <a class="dropdown-item" href="boletim_anos_anteriores.php?ano=2022" target="contentFrame">2022</a>
                        <a class="dropdown-item" href="boletim_anos_anteriores.php?ano=2021" target="contentFrame">2021</a>
                        <a class="dropdown-item" href="boletim_anos_anteriores.php?ano=2020" target="contentFrame">2020</a>
                    </div>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Frame Centralizado -->
    <iframe name="contentFrame" src="boletins.php" class="frame-container"></iframe>

    <!-- Inclui Bootstrap JS e dependências -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
