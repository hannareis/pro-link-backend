<!-- RF01 - formulario de login; token CSRF obrigatorio para o POST em /login -->
<form method="post" action="/login">
    <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
    <input type="email" name="email" required>
    <input type="password" name="senha" required>
    <button type="submit">Entrar</button>
</form>
