<!-- Layout base compartilhado pelas paginas da aplicacao (Bootstrap 5 + jQuery) -->
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars($title ?? config('app.name')) ?></title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
    <?= $content ?? '' ?>
    <script src="/assets/js/app.js"></script>
</body>
</html>
