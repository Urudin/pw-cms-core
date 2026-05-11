@extends('layouts.app')

@section('content')
    @php
        $paymentResult = $paymentResult ?? 'failed';
        $orderRef = $orderRef ?? $purchase->order_number ?? null;

        $messages = [
            'cancelled' => [
                'title' => 'Megszakított fizetés',
                'lead' => 'Ön megszakította a fizetést.',
                'body' => 'A rendelés fizetése nem folytatódott. A pénztárban újraindíthatja a fizetést, vagy választhat másik fizetési módot.',
            ],
            'timeout' => [
                'title' => 'Időtúllépés',
                'lead' => 'Ön túllépte a tranzakció elindításának lehetséges maximális idejét.',
                'body' => 'A fizetési folyamat időtúllépés miatt lezárult. Kérjük, indítsa újra a fizetést a pénztárból.',
            ],
            'failed' => [
                'title' => 'Sikertelen fizetés',
                'lead' => 'A fizetés nem sikerült.',
                'body' => 'Kérjük, próbálja meg újra a fizetést, vagy válasszon másik fizetési módot.',
            ],
        ];

        $message = $messages[$paymentResult] ?? $messages['failed'];
    @endphp

    <div class="pageContainer bg-white">
        <div class="bg-white p-4 md:p-10 w-full mx-auto space-y-6">
            <div class="text-center space-y-3">
                <h1 class="text-3xl sm:text-4xl font-black tracking-tight text-red-600">
                    {!! $message['title'] !!}
                </h1>

                <p class="text-slate-600 text-sm sm:text-base">
                    {!! $message['lead'] !!}
                </p>

                @if($orderRef)
                    <p class="text-slate-700 text-sm">
                        Rendelési Azonosító: <span class="font-black">#{{ $orderRef }}</span>
                    </p>
                @endif
            </div>

            <div class="bg-[#efeff2] border-b-2 border-[#d63b73] px-6 py-5 text-center text-slate-700 space-y-3">
                <p>
                    {!! $message['body'] !!}
                </p>

                <div>
                    <a href="{{ route('checkout') }}"
                       class="inline-flex items-stretch bg-[#f2a44a] hover:brightness-95 text-white overflow-hidden">
                        <span class="pl-[1.9rem] pr-[2.18rem] py-3 font-semibold">Vissza a p&eacute;nzt&aacute;rhoz</span>
                        <span class="flex items-center justify-center w-[3.1rem] bg-[#c7802f] !text-white">
                            <svg viewBox="0 0 14 14" class="h-4 w-4 translate-x-[-5px]" fill="currentColor" aria-hidden="true">
                                <path d="M10 2l4 5-4 5V2z"/>
                            </svg>
                        </span>
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
