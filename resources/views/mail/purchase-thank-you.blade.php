<!doctype html>
<html lang="hu">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <title>Köszönjük megrendelését!</title>

    <style>
        @media screen and (max-width: 640px) {
            .container { width: 100% !important; }
            .p-40 { padding: 20px !important; }
            .stack { display: block !important; width: 100% !important; }
            .gap { height: 16px !important; }
            .center-sm { text-align: center !important; }
        }
    </style>
</head>
<body style="margin:0; padding:0; background:#ffffff;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#ffffff;">
    <tr>
        <td align="center" style="padding: 24px 12px;">
            <table role="presentation" class="container" width="720" cellspacing="0" cellpadding="0" border="0" style="width:720px; max-width:720px;">
                <tr>
                    <td class="p-40" style="padding: 40px; background:#ffffff;">

                        {{-- HEADER --}}
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                            <tr>
                                <td align="center">
                                    <div style="font-family: Arial, Helvetica, sans-serif; font-size: 34px; line-height: 1.2; font-weight: 900; color: {{ $accentBlue }};">
                                        Köszönjük megrendelését!
                                    </div>
                                    <div style="height: 10px;"></div>
                                    <div style="font-family: Arial, Helvetica, sans-serif; font-size: 14px; line-height: 1.6; color: #475569;">
                                        Köszönjük az érdeklődést és a digitális/video tartalmunk megrendelését!
                                    </div>
                                </td>
                            </tr>
                        </table>

                        <div style="height: 22px;"></div>

                        {{-- INFO BOXOK --}}
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                            <tr>
                                {{-- BOX 1 --}}
                                <td class="stack" width="50%" valign="top" style="padding-right: 10px;">
                                    <table role="presentation" width="100%" height="100%" cellspacing="0" cellpadding="0" border="0"
                                           style="background: {{ $cardBg }}; border-bottom: 2px solid {{ $accentPink }}; min-height: 160px;">
                                        <tr>
                                            <td style="padding: 18px; text-align:center; vertical-align: middle;">
                                                <div style="font-family: Arial, Helvetica, sans-serif; font-size: 13px; font-weight: 900; color:#334155;">
                                                    Megrendelés azonosító:
                                                </div>
                                                <div style="height: 6px;"></div>
                                                <div style="font-family: Arial, Helvetica, sans-serif; font-size: 13px; color:#334155;">
                                                    #{{ $orderId }}
                                                </div>
                                            </td>
                                        </tr>
                                    </table>
                                </td>

                                {{-- BOX 2 --}}
                                <td class="stack" width="50%" valign="top" style="padding-left: 10px;">
                                    <table role="presentation" width="100%" height="100%" cellspacing="0" cellpadding="0" border="0"
                                           style="background: {{ $cardBg }}; border-bottom: 2px solid {{ $accentPink }}; min-height: 160px;">
                                        <tr>
                                            <td style="padding: 18px; text-align:center; vertical-align: middle;">
                                                <div style="font-family: Arial, Helvetica, sans-serif; font-size: 13px; font-weight: 900; color:#334155;">
                                                    Megrendelt digitális tartalom/Video ({{ $videos->count() }} db):
                                                </div>
                                                <div style="height: 8px;"></div>

                                                @forelse($videos as $video)
                                                    <div style="font-family: Arial, Helvetica, sans-serif; font-size: 13px; line-height: 1.4; color:#334155;">
                                                        {{ $video->title }}
                                                    </div>
                                                    <div style="height: 4px;"></div>
                                                @empty
                                                    <div style="font-family: Arial, Helvetica, sans-serif; font-size: 13px; color:#334155;">
                                                        (nincs találat)
                                                    </div>
                                                @endforelse
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>

                            <tr><td class="gap" colspan="2" style="height: 20px;"></td></tr>

                            <tr>
                                {{-- BOX 3 --}}
                                <td class="stack" width="50%" valign="top" style="padding-right: 10px;">
                                    <table role="presentation" width="100%" height="100%" cellspacing="0" cellpadding="0" border="0"
                                           style="background: {{ $cardBg }}; border-bottom: 2px solid {{ $accentPink }}; min-height: 160px;">
                                        <tr>
                                            <td style="padding: 18px; text-align:center; vertical-align: middle;">
                                                <div style="font-family: Arial, Helvetica, sans-serif; font-size: 13px; font-weight: 900; color:#334155;">
                                                    A szolgáltatás költsége:
                                                </div>
                                                <div style="height: 6px;"></div>
                                                <div style="font-family: Arial, Helvetica, sans-serif; font-size: 13px; color:#334155;">
                                                    {{ number_format((float)$totalGross, 0, ',', ' ') }} Ft
                                                </div>
                                                <div style="height: 4px;"></div>
                                                <div style="font-family: Arial, Helvetica, sans-serif; font-size: 12px; color:#64748b;">
                                                    (bruttó, {{ (int)round($vatRate * 100) }}% ÁFA-val)
                                                </div>
                                            </td>
                                        </tr>
                                    </table>
                                </td>

                                {{-- BOX 4 --}}
                                <td class="stack" width="50%" valign="top" style="padding-left: 10px;">
                                    <table role="presentation" width="100%" height="100%" cellspacing="0" cellpadding="0" border="0"
                                           style="background: {{ $cardBg }}; border-bottom: 2px solid {{ $accentPink }}; min-height: 160px;">
                                        <tr>
                                            <td style="padding: 18px; text-align:center; vertical-align: middle;">
                                                <div style="font-family: Arial, Helvetica, sans-serif; font-size: 13px; font-weight: 900; color:#334155;">
                                                    Fizetési mód:
                                                </div>
                                                <div style="height: 6px;"></div>
                                                <div style="font-family: Arial, Helvetica, sans-serif; font-size: 13px; color:#334155;">
                                                    {{ $paymentMethodLabel }}
                                                </div>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>

                        <div style="height: 22px;"></div>

                        {{-- INFO BAR --}}
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
                               style="background: {{ $cardBg }}; border-bottom: 2px solid {{ $accentPink }};">
                            <tr>
                                <td style="padding: 18px; text-align:center;">
                                    <div style="font-family: Arial, Helvetica, sans-serif; font-size: 13px; line-height: 1.6; color:#334155;">
                                        A szolgáltatás díját kérjük bankszámlánkra <strong>8 napon belül</strong> utalni,
                                        a megjegyzésbe kérjük tüntesse fel a <strong>megrendelés azonosítóját</strong>!
                                    </div>

                                    <div style="height: 12px;"></div>

                                    <div style="font-family: Arial, Helvetica, sans-serif; font-size: 16px; font-weight: 900; color: {{ $accentBlue }};">
                                        Bankszámlaszámunk ({{ $bankName }}): {{ $bankAccount }}
                                    </div>
                                </td>
                            </tr>
                        </table>

                        <div style="height: 18px;"></div>

                        {{-- FOOTER --}}
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                            <tr>
                                <td align="center">
                                    <div style="font-family: Arial, Helvetica, sans-serif; font-size: 14px; font-weight: 900; color:#334155;">
                                        Üdvözlettel:
                                    </div>
                                    <div style="height: 4px;"></div>
                                    <div style="font-family: Arial, Helvetica, sans-serif; font-size: 12px; color:#475569;">
                                        Glósz és Tsa csapata
                                    </div>
                                </td>
                            </tr>
                        </table>

                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
