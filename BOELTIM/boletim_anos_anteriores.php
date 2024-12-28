<?php
//include("valida_session.php");
?>

<?php
// Inclui o arquivo de conexão ao banco de dados
include('db.php');

// Inicializa variáveis
$ano = isset($_GET['ano']) ? $_GET['ano'] : date('Y'); // Define o ano com base no parâmetro ou o ano atual
$mes = isset($_GET['mes']) ? $_GET['mes'] : date('m'); // Selecione o mês atual por padrão
$n_bg_extenso = isset($_GET['n_bg_extenso']) ? $_GET['n_bg_extenso'] : ''; // Número do BG filtrado

// Query para obter o número de boletins por mês, incluindo a junção correta
$query_meses = "
    SELECT 
        TO_CHAR(bole_data_publicacao, 'MM') AS mes,
        COUNT(*) AS total_boletins
    FROM 
        bg.boletim bo
    INNER JOIN bg.tipo_boletim tb ON tb.tipo_bole_cod = bo.fk_tipo_bole_cod
    WHERE 
        TO_CHAR(bole_data_publicacao, 'YYYY') = :ano
        AND tb.tipo_bole_descricao = 'Boletim Geral'
    GROUP BY mes
    ORDER BY mes ASC";
$stmt_meses = $pdo->prepare($query_meses);
$stmt_meses->execute(['ano' => $ano]);
$boletins_por_mes = $stmt_meses->fetchAll(PDO::FETCH_ASSOC);

// Query para obter os boletins do mês selecionado, incluindo a junção correta
$query_boletins = "
    SELECT 
        bo.bole_cod,
        bo.bole_numero AS n_bg,
        concat('BG Nº:0', bo.bole_numero) AS n_bg_extenso,
        date_part('day'::text, bo.bole_data_publicacao) AS dia_bg,
        CASE date_part('month'::text, bo.bole_data_publicacao)
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
        date_part('Year'::text, bo.bole_data_publicacao) AS ano_bg,
        concat('Boa Vista, Roraima', ' ', date_part('day'::text, bo.bole_data_publicacao) ,' de ',  
            CASE date_part('month'::text, bo.bole_data_publicacao)
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
            END,' ', date_part('Year'::text, bo.bole_data_publicacao)) AS dia_mes_ano,
        tb.tipo_bole_descricao AS tipo_bg
    FROM 
        bg.boletim bo
    INNER JOIN bg.tipo_boletim tb ON tb.tipo_bole_cod = bo.fk_tipo_bole_cod
    WHERE 
        TO_CHAR(bo.bole_data_publicacao, 'YYYY') = :ano 
        AND TO_CHAR(bo.bole_data_publicacao, 'MM') = :mes
        AND tb.tipo_bole_descricao = 'Boletim Geral'
        AND (:n_bg_extenso = '' OR concat('BG Nº:0', bo.bole_numero) LIKE :n_bg_extenso)
    ORDER BY bo.bole_data_publicacao DESC";
$stmt_boletins = $pdo->prepare($query_boletins);
$stmt_boletins->execute([
    'ano' => $ano, 
    'mes' => str_pad($mes, 2, '0', STR_PAD_LEFT),
    'n_bg_extenso' => '%' . $n_bg_extenso . '%'
]);
$boletins_do_mes = $stmt_boletins->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Boletins Gerais - Edições de <?= htmlspecialchars($ano) ?></title>
    <!-- Inclui Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .card {
            transition: transform 0.2s ease;
        }
        .card:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }
        .card-title {
            color: #007bff;
            font-weight: bold;
        }
        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(0, 123, 255, 0.1);
        }
        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
        }
        .btn-info {
            background-color: #17a2b8;
            border-color: #17a2b8;
        }
        .container h1 {
            color: #343a40;
        }
        .highlight-card {
            background-color: #17a2b8;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center mb-4">Edições do Ano de <?= htmlspecialchars($ano) ?></h1>

        <div class="row mb-4">
            <?php
            $meses = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
            foreach ($meses as $index => $nome_mes) {
                $num_mes = str_pad($index + 1, 2, '0', STR_PAD_LEFT);
                $total_boletins = 0;
                foreach ($boletins_por_mes as $boletim) {
                    if ($boletim['mes'] == $num_mes) {
                        $total_boletins = $boletim['total_boletins'];
                        break;
                    }
                }
                $highlight = ($num_mes == $mes) ? 'highlight-card' : '';
                echo "
                <div class='col-md-3 mb-4'>
                    <div class='card border-primary {$highlight}'>
                        <div class='card-body text-center'>
                            <h5 class='card-title'>{$nome_mes}</h5>
                            <p class='card-text'>{$total_boletins} Arquivos</p>
                            <a href='?ano={$ano}&mes={$num_mes}' class='btn btn-primary btn-block'>Ver Arquivos</a>
                        </div>
                    </div>
                </div>";
            }
            ?>
        </div>

        <h2 class="mb-4 text-center">Últimas Publicações do Mês <?= $meses[intval($mes) - 1] ?>/<?= htmlspecialchars($ano) ?></h2>

        <!-- Filtro -->
        <form method="GET" action="" class="mb-4">
            <input type="hidden" name="ano" value="<?= htmlspecialchars($ano) ?>">
            <div class="form-row">
                <div class="col-md-3">
                    <input type="text" name="n_bg_extenso" class="form-control" placeholder="Número do BG" value="<?= htmlspecialchars($n_bg_extenso) ?>">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary btn-block">Filtrar</button>
                </div>
            </div>
        </form>

        <table class="table table-hover table-striped table-bordered">
            <thead class="thead-dark">
                <tr>
                    <th>Número do BG</th>
                    <th>Data</th>
                    <th>Tipo</th>
                    <th>Local e Data Extensos</th>
                    <th>Visualizar</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (count($boletins_do_mes) > 0) {
                    foreach ($boletins_do_mes as $boletim) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($boletim['n_bg_extenso']) . "</td>";
                        echo "<td>" . htmlspecialchars($boletim['dia_bg'] . " de " . $boletim['mes_extenso'] . " de " . $boletim['ano_bg']) . "</td>";
                        echo "<td>" . htmlspecialchars($boletim['tipo_bg']) . "</td>";
                        echo "<td>" . htmlspecialchars($boletim['dia_mes_ano']) . "</td>";
                        echo "<td><a href='pdf_boletim.php?bole_cod=" . $boletim['bole_cod'] . "' class='btn btn-info btn-sm'>Visualizar</a></td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='5'>Nenhum boletim encontrado para este mês.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <!-- Inclui Bootstrap JS e dependências -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
