<!-- sugestao_form.php -->
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Cadastro de Sugestão de Melhoria</title>
</head>
<body>
    <h1>Nova Sugestão de Melhoria</h1>
    <form action="sugestao_cadastrar.php" method="POST" enctype="multipart/form-data">
        <label for="tipo">Tipo:</label>
        <select name="tipo" id="tipo" required>
            <option value="Erro">Erro</option>
            <option value="Nova Funcionalidade">Nova Funcionalidade</option>
        </select>
        <br><br>

        <label for="descricao">Descrição:</label><br>
        <textarea name="descricao" id="descricao" rows="5" required></textarea>
        <br><br>

        <label for="imagem">Imagem (opcional):</label>
        <input type="file" name="imagem" id="imagem" accept="image/*">
        <br><br>

        <button type="submit">Enviar Sugestão</button>
    </form>
</body>
</html>
