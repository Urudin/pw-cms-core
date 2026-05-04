<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>Jelentkezés visszaigazolása</title>
</head>
<body>
<p>Tisztelt {{ $details['applicant_name'] }}!</p>

<p>
    Köszönjük jelentkezését az Innovációmenedzsment Akadémia meghirdetett képzésére!
    A jelentkezését követően kollégánk felveszi Önnel a kapcsolatot, és e-mailben megküldi Önnek
    a képzés megkezdéséhez szükséges dokumentumokat.
</p>

<p>Ön a következő tanfolyamra jelentkezett:</p>

<p><strong>{{ $details['course_name'] }}</strong></p>

<table style="width: 100%; border-collapse: collapse;">
    @foreach([
        'Kezdés időpontja' => $details['course_start_date'],
        'Tanfolyam típusa' => $details['course_type'],
        'Oktatás helyszíne' => $details['course_location'],
        'Tanúsítvány nyelve a magyar mellett' => $details['certificate_language'],
        'Tanfolyam díja' => $details['course_fee'],
    ] as $label => $value)
        @if(filled($value))
            <tr>
                <td style="padding: 8px; border: 1px solid #ddd;"><strong>{{ $label }}:</strong></td>
                <td style="padding: 8px; border: 1px solid #ddd;">{{ $value }}</td>
            </tr>
        @endif
    @endforeach
</table>

<p>
    Kérem, vegye figyelembe, hogy az egyes képzéseken a jelentkezők száma korlátozott,
    a jelentkezéseket a díjfizetés sorrendjében fogadjuk. Az online jelentkezési lap kitöltése
    nem jelent automatikusan felvételt a képzésre. A jelentkezés csak akkor érvényes,
    ha a számla kiegyenlítése megtörtént.
</p>

<p>
    A képzés részleteivel kapcsolatosan tájékozódjon weboldalunkon! Kérdés esetén munkatársunk
    rendelkezésére áll!
</p>

<p>
    Üdvözlettel:<br>
    Glósz és Társa Kft. - Innovációmenedzsment Akadémia<br>
    Cím: 1051 Budapest, Arany János u. 15. III. lph. III./5.<br>
    E-mail: glosz@glosz.hu<br>
    Telefon: +36 1 302 4443<br>
    Mobil: +36 30 642 8910
</p>
</body>
</html>
