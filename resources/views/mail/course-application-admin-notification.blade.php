<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>Új tanfolyami jelentkezés</title>
</head>
<body>
<p><strong>Tisztelt Adminisztráció!</strong></p>

<p>
    Jelentkezés/érdeklődés érkezett az Innovációmenedzsment Akadémia oldalról.
    A jelentkezés részletei:
</p>

<p><strong>Előre meghirdetett tanfolyami oktatás:</strong></p>
<p>{{ $courseName }}</p>

@if(filled($courseCategory))
    <p><strong>Tanfolyam típusa/kategóriája:</strong> {{ $courseCategory }}</p>
@endif

<p><strong>Díjfizető adatai:</strong></p>
<table style="width: 100%; border-collapse: collapse;">
    @foreach([
        'Név' => $application->payer_name,
        'Székhely' => $application->payer_address,
        'Levelezési cím' => $application->payer_mailing_address,
        'E-mail' => $application->payer_email,
        'Aláíró/képviselő' => $application->payer_signatory,
        'Adószám' => $application->payer_tax_number,
        'Tanúsítvány nyelve a magyar mellett' => $application->certificate_language,
    ] as $label => $value)
        @if(filled($value))
            <tr>
                <td style="padding: 8px; border: 1px solid #ddd;"><strong>{{ $label }}:</strong></td>
                <td style="padding: 8px; border: 1px solid #ddd;">{{ $value }}</td>
            </tr>
        @endif
    @endforeach
</table>

<p><strong>Résztvevő adatai:</strong></p>
<table style="width: 100%; border-collapse: collapse;">
    @foreach([
        'Név' => $participantName,
        'Telefonszám' => $application->participant_phone,
        'E-mail' => $application->participant_email,
        'Születési név' => $application->participant_birth_name,
        'Születési ország' => $application->participant_birth_country,
        'Születési hely' => $application->participant_birth_place,
        'Születési idő' => $application->participant_birth_date,
        'Lakcím' => $application->participant_address,
        'Értesítési cím' => $application->participant_notification_address,
        'Anyja neve' => $application->participant_mother_name,
        'Iskolai végzettség' => $application->participant_education,
        'Oktatási azonosító' => $application->participant_education_id,
        'Támogatási forrás terhére szeretné elszámolni?' => $application->participant_supported,
        'Pályázati azonosítószám' => $application->participant_grant_id,
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
    Üdvözlettel:<br>
    Innovációmenedzsment Akadémia Gépház
</p>
</body>
</html>
