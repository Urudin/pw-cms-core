@php use App\Models\Purchase; @endphp
@extends('layouts.app')

@section('content')
    @php
        /**
         * VÁLTOZÓK (példák)
         * - $orderId: pl. 652
         * - $videoTitle: pl. "Innovációmenedzsment a mezőgazdaságban"
         * - $total: pl. "6.900 Ft+áfa"
         * - $paymentMethodLabel: pl. "átutalás"
         * - $bankName: pl. "K&H Bank"
         * - $bankAccount: pl. "10200823-22223649-00000000"
         */
        $purchase = Purchase::query()->find(request()->input('purchaseId'));
        $orderId = $orderId ?? ($purchase->id ?? '#Error');
        $videos = \App\Models\Video::query()->whereIn('id', array_column($purchase->items, 'id'))->get();
        $videoTitle = $videoTitle ?? ($purchase->items[0]->name ?? 'Megrendelt videó címe');
        $total = $total ?? ($purchase->total_formatted ?? '6.900 Ft+áfa');
        $paymentMethodLabel = $paymentMethodLabel ?? ($order->payment_method_label ?? 'átutalás');

        $bankName = $bankName ?? 'K&H Bank';
        $bankAccount = $bankAccount ?? '10200823-22223649-00000000';

        $accentBlue = '#2f46d6'; // a képen látott kékhez hasonló
        $accentPink = '#d63b73'; // a piros/pink alsó csík
        $cardBg = '#efeff2';     // doboz háttér
    @endphp

    <div class="pageContainer bg-white">
        <div class="bg-white p-4 md:p-10 w-full mx-auto space-y-6">

            {{-- HEADER --}}
            <div class="text-center space-y-2">
                <h1 class="text-3xl sm:text-4xl font-black tracking-tight" style="color: {{ $accentBlue }};">
                    Köszönjük megrendelését!
                </h1>
                <p class="text-slate-600 text-sm sm:text-base">
                    Köszönjük az érdeklődést és a digitális/video tartalmunk megrendelését!
                </p>
            </div>

            {{-- 4 INFO BOX --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 pt-2">

                {{-- BOX 1 --}}
                <div class="bg-[{{ $cardBg }}] border-b-2 p-6 text-center" style="border-color: {{ $accentPink }};">
                    <div class="flex items-center justify-center mb-3">
                        {{-- receipt icon --}}
                        <svg class="w-9 h-9" style="color: {{ $accentBlue }};" xmlns="http://www.w3.org/2000/svg"
                             fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M9 14h6m-6-4h6m-7 11 1.5-1.5L12 21l1.5-1.5L15 21l1.5-1.5L18 21V5a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16Z"/>
                        </svg>
                    </div>
                    <div class="text-sm font-black text-slate-700">Megrendelés azonosító:</div>
                    <div class="mt-1 text-slate-700 text-sm">#{{ $orderId }}</div>
                </div>

                {{-- BOX 2 --}}
                <div class="bg-[{{ $cardBg }}] border-b-2 p-6 text-center" style="border-color: {{ $accentPink }};">
                    <div class="flex items-center justify-center mb-3">
                        {{-- video icon --}}
                        <svg class="w-9 h-9" style="color: {{ $accentBlue }};" xmlns="http://www.w3.org/2000/svg"
                             fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M15.75 10.5 19.5 8.25v7.5l-3.75-2.25m-1.5 3.75H6.75A2.25 2.25 0 0 1 4.5 15V9A2.25 2.25 0 0 1 6.75 6.75h7.5A2.25 2.25 0 0 1 16.5 9v6a2.25 2.25 0 0 1-2.25 2.25Z"/>
                        </svg>
                    </div>
                    <div class="text-sm font-black text-slate-700">Megrendelt digitális tartalom/Video {{$videos->count()}} db:</div>
                    @foreach($videos as $video)
                    <div class="mt-1 text-slate-700 text-sm leading-snug">
                        {{ $video->title }}
                    </div>
                    @endforeach
                </div>

                {{-- BOX 3 --}}
                <div class="bg-[{{ $cardBg }}] border-b-2 p-6 text-center" style="border-color: {{ $accentPink }};">
                    <div class="flex items-center justify-center mb-3">
                        {{-- wallet icon --}}
                        <svg class="w-9 h-9" style="color: {{ $accentBlue }};" xmlns="http://www.w3.org/2000/svg"
                             fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M21 12.75V17.25A2.25 2.25 0 0 1 18.75 19.5H5.25A2.25 2.25 0 0 1 3 17.25V6.75A2.25 2.25 0 0 1 5.25 4.5h12.5A2.25 2.25 0 0 1 20 6.75V9m1 3.75h-4.5A1.5 1.5 0 0 1 15 11.25v0A1.5 1.5 0 0 1 16.5 9.75H21"/>
                        </svg>
                    </div>
                    <div class="text-sm font-black text-slate-700">A szolgáltatás költsége:</div>
                    <div class="mt-1 text-slate-700 text-sm">{{ round($purchase->getTotal() * 1.27) }} Ft</div>
                </div>

                {{-- BOX 4 --}}
                <div class="bg-[{{ $cardBg }}] border-b-2 p-6 text-center" style="border-color: {{ $accentPink }};">
                    <div class="flex items-center justify-center mb-3">
                        {{-- credit card / payment icon --}}
                        <svg class="w-9 h-9" style="color: {{ $accentBlue }};" xmlns="http://www.w3.org/2000/svg"
                             fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M3 7.5A2.25 2.25 0 0 1 5.25 5.25h13.5A2.25 2.25 0 0 1 21 7.5v9A2.25 2.25 0 0 1 18.75 18.75H5.25A2.25 2.25 0 0 1 3 16.5v-9Z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 9h18"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 15h3"/>
                        </svg>
                    </div>
                    <div class="text-sm font-black text-slate-700">Fizetési mód:</div>
                    <div class="mt-1 text-slate-700 text-sm">{{ $paymentMethodLabel }}</div>
                </div>

            </div>

            {{-- INFO BAR --}}
            <div class="bg-[{{ $cardBg }}] border-b-2 px-6 py-5 space-y-3" style="border-color: {{ $accentPink }};">
                <div class="flex flex-col items-center gap-2 text-slate-700 text-sm leading-relaxed text-center">

                    <div>
                        A szolgáltatás díját kérjük bankszámlánkra <span class="font-black">8 napon belül</span> utalni,
                        a megjegyzésbe kérjük tüntesse fel a <span class="font-black">megrendelés azonosítóját</span>!
                    </div>
                </div>

                <div class="text-center font-black text-base sm:text-lg" style="color: {{ $accentBlue }};">
                    Bankszámlaszámunk ({{ $bankName }}): {{ $bankAccount }}
                </div>
            </div>

            {{-- FOOTER --}}
            <div class="text-center pt-2">
                <div class="text-slate-700 font-black">Üdvözlettel:</div>
                <div class="text-slate-600 text-sm">Glósz és Tsa csapata</div>
            </div>

        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // ha localStorage-ben van a kosár
            localStorage.removeItem('cart');
            localStorage.removeItem('shoppingCart'); // ha több kulcsod van

            // ha sessionStorage-ben van
            sessionStorage.removeItem('cart');

            // opcionális: ha van globális cart state-ed
            if (window.cart) {
                window.cart.clear?.();
            }

            console.log('Kosár ürítve a köszönő oldalon.');
        });
    </script>
@endsection
