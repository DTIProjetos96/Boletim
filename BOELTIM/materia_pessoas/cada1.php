<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CKEditor Example</title>
    <script src="/boletim/bibliotecas/ckeditor/ckeditor.js"></script>
    <script src="/boletim/bibliotecas/ckeditor/config.js"></script>
    
</head>
<body>
    <h1>CKEditor Example</h1>
    <form action="#" method="post">
        <textarea name="editor" id="editor" rows="10" cols="80">
            Escreva seu texto aqui...
        </textarea>
        <script>
            CKEDITOR.replace('editor', {
                toolbar: 'Full',
                extraPlugins: 'image,stylescombo',
            });
        </script>
        <br>
        <button type="submit">Enviar</button>
    </form>
</body>
</html>
