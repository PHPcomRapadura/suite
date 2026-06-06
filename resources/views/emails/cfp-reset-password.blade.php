<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinição de senha</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background-color: #f5f6f8; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #111827; }
        .wrapper { max-width: 560px; margin: 40px auto; padding: 0 16px 40px; }
        .logo-bar { text-align: center; padding: 32px 0 24px; }
        .logo-bar img { height: 40px; }
        .card { background: #ffffff; border-radius: 16px; border: 1px solid #e5e7eb; padding: 40px 36px; }
        h1 { font-size: 20px; font-weight: 700; color: #111827; margin-bottom: 8px; }
        p { font-size: 15px; color: #6b7280; line-height: 1.6; margin-bottom: 16px; }
        .btn { display: inline-block; margin: 8px 0 24px; padding: 14px 28px; background-color: #025c98; color: #ffffff !important; font-size: 15px; font-weight: 600; text-decoration: none; border-radius: 10px; }
        .divider { border: none; border-top: 1px solid #e5e7eb; margin: 24px 0; }
        .link-fallback { font-size: 13px; color: #6b7280; word-break: break-all; }
        .link-fallback a { color: #025c98; }
        .footer { text-align: center; font-size: 12px; color: #9ca3af; margin-top: 28px; line-height: 1.6; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="logo-bar">
            <img src="{{ asset('images/PHPcomRapadura_color.svg') }}" alt="PHP com Rapadura">
        </div>

        <div class="card">
            <h1>Redefinir sua senha</h1>
            <p>Olá, <strong>{{ $user->name }}</strong>!</p>
            <p>
                Recebemos uma solicitação para redefinir a senha da sua conta no CFP da comunidade
                <strong>PHP com Rapadura</strong>. Clique no botão abaixo para criar uma nova senha.
                O link é válido por <strong>60 minutos</strong>.
            </p>

            <a href="{{ $resetUrl }}" class="btn">Redefinir minha senha</a>

            <p style="margin-bottom: 0;">
                Se você não solicitou a redefinição de senha, ignore este e-mail.
                Sua senha permanecerá a mesma.
            </p>

            <hr class="divider">

            <p class="link-fallback">
                Se o botão não funcionar, copie e cole o link abaixo no seu navegador:<br>
                <a href="{{ $resetUrl }}">{{ $resetUrl }}</a>
            </p>
        </div>

        <div class="footer">
            PHP com Rapadura — Comunidade PHP do Ceará<br>
            Este e-mail foi enviado automaticamente. Por favor, não responda.
        </div>
    </div>
</body>
</html>
