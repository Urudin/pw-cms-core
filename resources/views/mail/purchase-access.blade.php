@php
    $fullName = trim(($purchase->personal_last_name ?? '') . ' ' . ($purchase->personal_first_name ?? ''));
@endphp

    <!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>Digitális tartalmak elérése</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #111827;">

<p>
    Tisztelt <strong>{{ $fullName }}</strong>!
</p>

<p>
    Köszönjük a vásárlást!
    Az alábbiakban találja a megvásárolt digitális tartalmak elérését:
</p>

@foreach($items as $item)
    @php
        $id = is_array($item)
            ? ($item['id'] ?? null)
            : (is_object($item)
                ? ($item->id ?? null)
                : (is_numeric($item) ? (int) $item : null));

        $video = $id ? ($videos[$id] ?? null) : null;
    @endphp

    @if($video)
        <div style="margin: 16px 0; padding: 12px 16px; border: 1px solid #e5e7eb; border-radius: 6px; background: #f9fafb;">
            <p style="margin: 0 0 6px 0;">
                <strong>{{ $video->title ?? 'Videó' }}</strong>
            </p>

            <p style="margin: 0 0 6px 0;">
                Elérés:
                <a href="{{ route('video.show', $video->id) }}" target="_blank" style="color: #2563eb; text-decoration: underline;">
                    {{ route('video.show', $video->id) }}
                </a>
            </p>

            <p style="margin: 0;">
                Felhasználónév: <strong>{{ $accesses[$video->id]['email'] }}</strong><br>
                Jelszó: <strong>{{ $accesses[$video->id]['password'] }}</strong>
            </p>
        </div>
    @endif
@endforeach

<p>
    Amennyiben bármilyen kérdése merülne fel, forduljon hozzánk bizalommal.
</p>

<p style="margin-top: 24px;">
    Üdvözlettel,<br>
    <strong>Az Innovációmenedzsment Akadémia csapata</strong>
</p>

</body>
</html>
