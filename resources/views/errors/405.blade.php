<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ação não permitida</title>
    <style>
        body { margin: 0; font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; background: #f1f5f9; color: #0f172a; }
        main { min-height: 100vh; display: grid; place-items: center; padding: 24px; }
        section { width: 100%; max-width: 520px; border-radius: 24px; background: #fff; padding: 32px; box-shadow: 0 20px 45px rgba(15, 23, 42, .08); }
        span { display: inline-flex; border-radius: 999px; background: #fef3c7; color: #92400e; padding: 6px 12px; font-size: 12px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; }
        h1 { margin: 18px 0 8px; font-size: 28px; }
        p { margin: 0; color: #475569; line-height: 1.6; }
        a { margin-top: 24px; display: inline-flex; border-radius: 12px; background: #0f172a; color: #fff; padding: 12px 18px; font-weight: 700; text-decoration: none; }
    </style>
</head>
<body>
    <main>
        <section>
            <span>Erro 405</span>
            <h1>Ação não permitida</h1>
            <p>Esse endereço não aceita esse tipo de acesso. Se você digitou a URL manualmente, volte ao painel e navegue pelo menu.</p>
            <a href="{{ auth()->check() ? route('dashboard') : route('login') }}">Voltar ao sistema</a>
        </section>
    </main>
</body>
</html>
