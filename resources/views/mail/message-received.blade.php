<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajánlatkérés Megerősítése</title>
    <style>
        body {
            font-family: sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            padding: 20px;
        }
        .email-container {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            margin: 0 auto;
        }
        .email-header {
            font-size: 1.2rem;
            margin-bottom: 20px;
        }
        .email-section {
            margin-bottom: 20px;
        }
        .email-footer {
            font-size: 0.9rem;
            color: #777;
        }
    </style>
</head>
<body>
<div class="email-container">
    <div class="email-header">
        Tisztelt {{ $content['name'] ?? '' }}!
    </div>

    <div class="email-section">
        A weboldalunk - <a href="https://glosz.hu" target="_blank">glosz.hu</a> - segítségével számunkra elküldött ajánlatkérését megkaptuk, köszönjük.<br>
        Kollégáink az üzenet feldolgozását követően hamarosan jelentkeznek Önnél a kért információkkal megadott elérhetőségein.
    </div>

    <div class="email-section">
        <strong>Üzenetének tartalma:</strong><br><br>
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 8px; border: 1px solid #ddd;"><strong>Név:</strong></td>
                <td style="padding: 8px; border: 1px solid #ddd;">{{ $content['name'] ?? '' }}</td>
            </tr>
            <tr>
                <td style="padding: 8px; border: 1px solid #ddd;"><strong>E-mail:</strong></td>
                <td style="padding: 8px; border: 1px solid #ddd;">{{ $content['email'] ?? '' }}</td>
            </tr>
            <tr>
                <td style="padding: 8px; border: 1px solid #ddd;"><strong>Cégnév:</strong></td>
                <td style="padding: 8px; border: 1px solid #ddd;">{{ $content['company'] ?? '' }}</td>
            </tr>
            <tr>
                <td style="padding: 8px; border: 1px solid #ddd;"><strong>Telefonszám:</strong></td>
                <td style="padding: 8px; border: 1px solid #ddd;">{{ $content['phone'] ?? '' }}</td>
            </tr>
            <tr>
                <td style="padding: 8px; border: 1px solid #ddd;"><strong>Üzenet:</strong></td>
                <td style="padding: 8px; border: 1px solid #ddd;">{{ $content['message'] ?? '' }}</td>
            </tr>
        </table>
    </div>

    <div class="email-footer">
        Ez egy automatikus üzenet, kérjük, ne válaszoljon rá.
    </div>
</div>
</body>
</html>
