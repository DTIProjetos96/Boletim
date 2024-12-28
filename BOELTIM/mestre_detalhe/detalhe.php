<?php
// detalhe.php

// Verifica se o cliente_id foi passado via GET
if (!isset($_GET['cliente_id']) || empty($_GET['cliente_id'])) {
    echo "ID do cliente não especificado.";
    exit;
}

$cliente_id = intval($_GET['cliente_id']);

// Parâmetros de conexão com o banco de dados
$host = "pmrr.net";
$dbname = "pmrrnet_erp_prod";
$user = "pmrrnet_usr_erp";
$password = "stipmrr!@190";

try {
    // Cria uma nova conexão PDO
    $dsn = "pgsql:host=$host;dbname=$dbname";
    $pdo = new PDO($dsn, $user, $password);

    // Define o modo de erro do PDO para exceção
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Consulta para obter os detalhes do cliente no esquema bg
    $sql_cliente = "SELECT id, nome, email FROM bg.clientes WHERE id = :id";
    $stmt_cliente = $pdo->prepare($sql_cliente);
    $stmt_cliente->execute(['id' => $cliente_id]);
    $cliente = $stmt_cliente->fetch(PDO::FETCH_ASSOC);

    if (!$cliente) {
        echo "Cliente não encontrado.";
        exit;
    }

    // Consulta para selecionar os pedidos do cliente no esquema bg
    $sql_pedidos = "SELECT id, data_pedido, valor FROM bg.pedidos WHERE cliente_id = :cliente_id ORDER BY data_pedido DESC";
    $stmt_pedidos = $pdo->prepare($sql_pedidos);
    $stmt_pedidos->execute(['cliente_id' => $cliente_id]);
    $pedidos = $stmt_pedidos->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Erro na conexão: " . $e->getMessage();
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Pedidos de <?php echo htmlspecialchars($cliente['nome']); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        .container {
            width: 80%;
            margin: 20px auto;
        }
        h2, h3 {
            text-align: center;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            padding: 8px 12px;
            border: 1px solid #ccc;
            text-align: center;
        }
        th {
            background-color: #f4f4f4;
        }
        a {
            text-decoration: none;
            color: #3498db;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Pedidos de <?php echo htmlspecialchars($cliente['nome']); ?></h2>
        <p style="text-align:center;"><strong>Email:</strong> <?php echo htmlspecialchars($cliente['email']); ?></p>
        <table>
            <thead>
                <tr>
                    <th>ID do Pedido</th>
                    <th>Data do Pedido</th>
                    <th>Valor (R$)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pedidos as $pedido): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($pedido['id']); ?></td>
                        <td><?php echo htmlspecialchars($pedido['data_pedido']); ?></td>
                        <td><?php echo number_format($pedido['valor'], 2, ',', '.'); ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($pedidos)): ?>
                    <tr>
                        <td colspan="3" style="text-align:center;">Nenhum pedido encontrado para este cliente.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <p style="text-align:center;"><a href="mestre.php">Voltar para a lista de clientes</a></p>
    </div>
</body>
</html>
