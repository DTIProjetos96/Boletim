<?php include '../valida_session.php'; ?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu de Navegação</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f8f9fa;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .navbar {
            background-color: #343a40;
        }
        .navbar-nav .nav-link {
            color: #ffffff !important;
            margin-right: 15px;
        }
        .navbar-nav .nav-link:hover {
            color: #007bff !important;
        }
        .navbar-nav .dropdown-menu {
            background-color: #343a40;
            border: none;
        }
        .navbar-nav .dropdown-menu .dropdown-item {
            color: #ffffff;
        }
        .navbar-nav .dropdown-menu .dropdown-item:hover {
            background-color: #007bff;
            color: #ffffff;
        }
        .dropdown-item i {
            margin-right: 8px;
        }
        #content-frame {
            border: none;
            width: 100%;
            height: calc(100vh - 56px - 50px); /* Altura dinâmica para preencher a tela */
            flex-grow: 1;
        }
        footer {
            background-color: #343a40;
            color: #ffffff;
            text-align: center;
            padding: 2px 0;
            font-size: 12px;
            margin-top: auto;
        }

        /* Estilos para o "Bem-vindo" e botão de "Sair" */
        .navbar .navbar-right {
            display: flex;
            align-items: center;
            margin-left: auto;
            color: white;
            font-weight: bold;
        }
        .navbar .navbar-right span {
            margin-right: 15px;
        }
        .navbar .navbar-right .btn {
            background-color: #dc3545;
            color: white;
            border: none;
            font-weight: bold;
        }
        .navbar .navbar-right .btn:hover {
            background-color: #c82333;
        }

        /* Estilos das caixas de meses */
        .month-box {
            background-color: #007bff;
            color: white;
            border-radius: 10px;
            text-align: center;
            padding: 20px;
            margin: 10px;
            transition: background-color 0.3s ease;
        }
        .month-box:hover {
            background-color: #0056b3; /* Cor ao passar o mouse */
        }
        .month-box.active {
            background-color: #004085; /* Cor diferenciada para o mês atual ou com arquivos */
        }
        .month-box h3, .month-box p {
            margin: 0;
        }
        .month-box .btn {
            margin-top: 10px;
            background-color: white;
            color: #007bff;
            border: none;
        }
        .month-box .btn:hover {
            background-color: #0056b3;
            color: white;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container-fluid">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavDropdown" aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNavDropdown">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link active" aria-current="page" href="/boletim/index_bkp.php" target="content-frame"><i class="fa fa-house"></i> Home</a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="materiaDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fa fa-file-alt"></i> Matéria
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="materiaDropdown">
                        <li><a class="dropdown-item" href="../materia_pessoas/cad.php" target="content-frame"><i class="fa fa-plus"></i> Cadastrar Matéria</a></li>
                        <li><a class="dropdown-item" href="../materia/consulta_materia1.php" target="content-frame"><i class="fa fa-search"></i> Consultar Matéria</a></li>
                        <li><a class="dropdown-item" href="../materia/materia_enviada.php" target="content-frame"><i class="fa fa-paper-plane"></i> Matérias Enviadas</a></li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="notasDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fa fa-envelope-open-text"></i> Matérias Recebidas
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="notasDropdown">
                        <li><a class="dropdown-item" href="../notas/nb_recebida.php" target="content-frame"><i class="fa fa-inbox"></i> Receber matérias</a></li>
                        <li><a class="dropdown-item" href="../notas/nota_recebida.php" target="content-frame"><i class="fa fa-inbox"></i> Matérias recebidas</a></li>
                        <li><a class="dropdown-item" href="#" target="content-frame"><i class="fa fa-paper-plane"></i> Matérias Enviadas</a></li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="boletimDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fa fa-book"></i> Boletim
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="boletimDropdown">
                        <li><a class="dropdown-item" href="../boletim/calendario_cad_boletim.php" target="content-frame"><i class="fa fa-plus-circle"></i> Cadastrar boletim</a></li>
                        <li><a class="dropdown-item" href="../materia_boletim/add_materia_boletim.php" target="content-frame"> <i class="fa fa-plus-circle"></i> Adicionar nota em boletim</a> </li>
                        <li><a class="dropdown-item" href="../boletim/assinatura_dp/consulta_boletim_assinatura_dp.php" target="content-frame"><i class="fa fa-plus-circle"></i> Assinar boletim - DP</a></li>
                        <li><a class="dropdown-item" href="../boletim/assinatura_cmt/consulta_boletim_assinatura_cmt_geral.php" target="content-frame"><i class="fa fa-plus-circle"></i> Assinar boletim - CMT GERAL</a></li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
					<a class="nav-link dropdown-toggle" href="#" id="boletimDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    	<i class="fa fa-book"></i> Administração
					</a>
					<ul class="dropdown-menu" aria-labelledby="admDropdown">
						<li><a class="dropdown-item" href="../controle_acesso/admin_acessos.php" target="content-frame"><i class="fa fa-plus-circle"></i> Controle por página (Acessos)</a></li>
					</ul>	
                </li>
            </ul>
        </div>

        <!-- Seção de "Bem-vindo" e botão Sair -->
        <div class="navbar-right">
            <span>Bem-vindo <?= $posto ?> <?= $graduacao ?> <?= $guerra ?>
        (<?= $subunidade ?> | <?= $unidade ?> | <?= $coma_sigla ?>)</span>
            <a href="../login/logout.php" class="btn">Sair</a>
        </div>
    </div>
</nav>

<!-- Iframe para carregar o conteúdo -->
<iframe id="content-frame" name="content-frame" src="/boletim/index_bkp.php"></iframe>

<!-- Rodapé -->
<footer>
    <p>Desenvolvido pelo Departamento de Tecnologia da Informação</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
