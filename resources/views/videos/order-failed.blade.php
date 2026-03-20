@extends('layouts.app')

@section('content')
    <div class="pageContainer bg-white">
        <div class="bg-white p-4 md:p-10 w-full mx-auto space-y-6">
            <div class="text-center space-y-3">
                <h1 class="text-3xl sm:text-4xl font-black tracking-tight text-red-600">
                    A fizetés nem sikerült
                </h1>

                <p class="text-slate-600 text-sm sm:text-base">
                    A kártyás fizetés sikertelen volt, vagy megszakadt.
                </p>

                @if(!empty($orderRef))
                    <p class="text-slate-700 text-sm">
                        Rendelési azonosító: <span class="font-black">#{{ $orderRef }}</span>
                    </p>
                @endif
            </div>

            <div class="bg-[#efeff2] border-b-2 border-[#d63b73] px-6 py-5 text-center text-slate-700 space-y-3">
                <p>
                    Kérjük, próbáld meg újra a fizetést, vagy válassz másik fizetési módot.
                </p>

                <div>
                    <a href="{{ route('checkout') }}"
                       class="inline-flex items-stretch bg-[#f2a44a] hover:brightness-95 text-white overflow-hidden">
                        <span class="pl-[1.9rem] pr-[2.18rem] py-3 font-semibold">Vissza a pénztárhoz</span>
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
