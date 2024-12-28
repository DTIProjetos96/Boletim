<?php
// mestre.php

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

    // Consulta para selecionar todos os clientes no esquema bg
    $sql = "SELECT id, nome, email FROM bg.clientes ORDER BY nome";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Erro na conexão: " . $e->getMessage();
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Lista de Clientes</title>
    <style>
        table {
            width: 60%;
            border-collapse: collapse;
            margin: 20px auto;
        }
        th, td {
            padding: 8px 12px;
            border: 1px solid #ccc;
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
    <h2 style="text-align:center;">Lista de Clientes</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Email</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($clientes as $cliente): ?>
                <tr>
                    <td><?php echo htmlspecialchars($cliente['id']); ?></td>
                    <td><?php echo htmlspecialchars($cliente['nome']); ?></td>
                    <td><?php echo htmlspecialchars($cliente['email']); ?></td>
                    <td>
                        <a href="detalhe.php?cliente_id=<?php echo urlencode($cliente['id']); ?>">Ver Pedidos</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($clientes)): ?>
                <tr>
                    <td colspan="4" style="text-align:center;">Nenhum cliente encontrado.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
