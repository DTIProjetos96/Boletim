<?php
require '../db.php';

// Verificar o tipo de usuário
//$usuario_tipo = $_SESSION['usuario_tipo'] ?? 'usuario';

try {
    if ($usuario_tipo === 'admin') {
        // O administrador vê todas as sugestões
        $sql = "SELECT * FROM bg.sugestoes_melhoria ORDER BY criado_em DESC";
    } else {
        // O usuário comum vê apenas suas sugestões
        $sql = "SELECT id, tipo, status, descricao, imagem, criado_em FROM bg.sugestoes_melhoria ORDER BY criado_em DESC";
    }
    $stmt = $pdo->query($sql);
    $sugestoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro ao buscar sugestões: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Lista de Sugestões</title>
</head>
<body>
    <h1>Lista de Sugestões</h1>
    <table border="1">
        <tr>
            <th>ID</th>
            <th>Tipo</th>
            <th>Status</th>
            <th>Descrição</th>
            <th>Imagem</th>
            <?php if ($usuario_tipo === 'admin'): ?>
                <th>Resposta</th>
                <th>Ações</th>
            <?php endif; ?>
        </tr>
        <?php foreach ($sugestoes as $sugestao): ?>
            <tr>
                <td><?= $sugestao['id'] ?></td>
                <td><?= $sugestao['tipo'] ?></td>
                <td><?= $sugestao['status'] ?></td>
                <td><?= $sugestao['descricao'] ?></td>
                <td>
                    <?php if (!empty($sugestao['imagem'])): ?>
                        <a href="<?= $sugestao['imagem'] ?>" target="_blank">Ver Imagem</a>
                    <?php else: ?>
                        Nenhuma
                    <?php endif; ?>
                </td>
                <?php if ($usuario_tipo === 'admin'): ?>
                    <td><?= $sugestao['resposta_admin'] ?: 'Aguardando resposta' ?></td>
                    <td><a href="sugestao_editar.php?id=<?= $sugestao['id'] ?>">Editar</a></td>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
