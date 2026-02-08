@extends('layouts.app')

@section('content')
    <div class="pageContainer bg-white">
        <div class="lg:flex lg:gap-8">
            <div class="lg:flex-1 min-w-0">
                <div class="bg-gray-100 p-4 md:p-8 shadow-md w-full mx-auto space-y-6">

                    {{-- PAGE HEADER --}}
                    <div>
                        <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-slate-900">
                            Válasszon video tartalmaink szerint!
                        </h1>
                        <p class="text-base text-slate-700 mt-6">
                            Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur
                        </p>
                    </div>

                    {{-- FILTER --}}
                    <form method="GET" class="bg-[#e9eaee] border-b-2 border-[#d63b73] p-4 md:p-6 space-y-4">

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1">
                                    Video típusa<span class="text-red-600">*</span>
                                </label>
                                <select name="type_id" class="w-full border border-gray-300 px-3 py-[10px]">
                                    <option value="">Kérjük válasszon!</option>
                                    @foreach($types as $t)
                                        <option
                                            value="{{ $t->id }}" @selected(request('type_id') == $t->id)>{{ $t->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1">
                                    Video témája<span class="text-red-600">*</span>
                                </label>
                                <select name="topic_id" class="w-full border border-gray-300 px-3 py-[10px]">
                                    <option value="">Kérjük válasszon!</option>
                                    @foreach($topics as $t)
                                        <option
                                            value="{{ $t->id }}" @selected(request('topic_id') == $t->id)>{{ $t->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1">
                                    Video szakterülete<span class="text-red-600">*</span>
                                </label>
                                <select name="domain_id" class="w-full border border-gray-300 px-3 py-[10px]">
                                    <option value="">Kérjük válasszon!</option>
                                    @foreach($domains as $d)
                                        <option
                                            value="{{ $d->id }}" @selected(request('domain_id') == $d->id)>{{ $d->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                            <div class="md:col-span-8">
                                <label class="block text-sm font-semibold text-slate-700 mb-1">
                                    Szabadszavas keresés<span class="text-red-600">*</span>
                                </label>
                                <input
                                    type="text"
                                    name="q"
                                    value="{{ request('q') }}"
                                    placeholder="Kezdjen gépelni itt..."
                                    class="w-full border border-gray-300 px-3 py-[11px]"
                                >
                            </div>

                            <div class="md:col-span-4">
                                <button type="submit"
                                        class="inline-flex items-stretch bg-[#f2a44a] hover:brightness-95 text-white w-full overflow-hidden">
                                <span class="px-[4.65rem] py-[11px] font-semibold flex items-center">
                                    Szűrés
                                </span>
                                    <span
                                        class="flex items-center justify-center w-[3.1rem] bg-[#c7802f] !text-white group-hover:!text-white">
                                                <svg
                                                    viewBox="0 0 14 14"
                                                    class="h-4 w-4 translate-x-[-5px]"
                                                    fill="currentColor"
                                                    aria-hidden="true"
                                                >
                                                    <path d="M10 2l4 5-4 5V2z"/>
                                                </svg>
                                    </span>
                                </button>
                            </div>
                        </div>
                    </form>

                    {{-- LIST --}}
                    <div class="space-y-6">
                        @foreach($videos as $video)
                            @php
                                $even = $loop->iteration % 2 === 0;
                                $original = (int)$video->original_price_huf;
                                $current = (int)$video->price_huf;
                                $CARD_H = 'md:h-[320px]';      // <-- itt állítod a fix magasságot
                                $IMG_H  = 'h-[240px] md:h-[320px]'; // mobil + desktop
                            @endphp

                            <article class="bg-white border border-gray-200 overflow-hidden">
                                <div class="grid grid-cols-1 md:grid-cols-12 {{ $CARD_H }}">

                                    {{-- THUMB --}}
                                    <div class="{{ $even ? 'md:order-2' : '' }} md:col-span-8 relative {{ $IMG_H }} overflow-hidden">
                                        <img
                                            src="{{ $video->thumbnail_url }}"
                                            class="absolute inset-0 w-full h-full object-cover"
                                            alt="Video - {{ $video->title }}"
                                            title="Video - {{ $video->title }}"
                                        >

                                        <div class="absolute inset-0 bg-slate-900/60"></div>

                                        <div class="absolute top-5 left-8 right-5 flex items-center justify-between pointer-events-none">
                                            <div class="w-7 h-7 rounded-full flex items-center justify-center">
                                                <img src="{{ asset('images/info.svg')}}" alt="info"/>
                                            </div>

                                            <svg class="w-12 h-12 text-white" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                                <path d="M8.2 6.1c0-.8.9-1.3 1.6-.9l8.6 5.1c.7.4.7 1.4 0 1.8l-8.6 5.1c-.7.4-1.6-.1-1.6-.9V6.1z"/>
                                            </svg>
                                        </div>

                                        <div class="absolute left-8 top-16 text-white">
                                            <p class="text-sm leading-relaxed opacity-95 max-w-[85%] xl:max-w-[260px] line-clamp-4">
                                                {{ $video->description }}
                                            </p>
                                        </div>
                                    </div>
                                    {{-- TEXT --}}
                                    <div
                                        class="{{ $even ? 'md:order-1' : '' }} md:col-span-4 bg-[#efeff2] p-6 flex flex-col justify-between">

                                        <div class="space-y-4">
                                            <div class="flex justify-between items-center">
                                            <span
                                                class="text-[11px] font-bold bg-slate-800 text-[#69b7ff] px-3 py-1 uppercase">
                                                {{ $video->type->name ?? 'ONLINE TANFOLYAM' }}
                                            </span>

                                                <span class="text-[12px] text-slate-400 flex items-center gap-1">
                                                    <svg class="w-4 h-4" viewBox="0 0 24 24"
                                                         fill="currentColor" aria-hidden="true">
                                                        <path d="M8 5v14l11-7z"></path>
                                                    </svg>
                                                    @php
                                                        $total = (int) $video->duration_seconds;

                                                        $h = intdiv($total, 3600);
                                                        $m = intdiv($total % 3600, 60);
                                                        $s = $total % 60;
                                                    @endphp
                                                    @if ($h > 0)
                                                        {{ sprintf('%d:%02d:%02d', $h, $m, $s) }}
                                                    @else
                                                        {{ sprintf('%d:%02d', $m, $s) }}
                                                    @endif
                                            </span>
                                            </div>

                                            <h2 class="text-[22px] font-black leading-tight text-[#1f4fd6] max-w-[260px] line-clamp-2">
                                                {{ $video->title }}
                                            </h2>

                                            <div class="flex gap-2 flex-wrap">
                                                @if($video->topic)
                                                    <span
                                                        class="text-[10px] px-2 py-[2px] border-2 border-slate-900/60 text-slate-700 bg-white font-bold">
                                                    {{ strtoupper($video->topic->name) }}
                                                </span>
                                                @endif
                                                @if($video->domain)
                                                    <span
                                                        class="text-[10px] px-2 py-[2px] border-2 border-slate-900/60 text-slate-700 bg-white font-bold">
                                                    {{ strtoupper($video->domain->name) }}
                                                </span>
                                                @endif
                                            </div>

                                            <div class="flex items-end gap-4 pt-2">
                                                @if(!empty($original))
                                                    <span class="text-base font-black text-slate-300 line-through">
                                                        {{ number_format($original,0,' ',' ') }} Ft+áfa
                                                    </span>
                                                @endif
                                                <span class="text-base font-black text-slate-800">
                                                    {{ number_format($current,0,' ',' ') }} Ft+áfa
                                                </span>
                                            </div>
                                        </div>

                                        <button
                                            class="mt-6 inline-flex items-stretch bg-[#f2a44a] hover:brightness-95 text-white overflow-hidden add-to-cart"
                                            data-id="video_{{ $video->id }}"
                                            data-name="{{ $video->title }}"
                                            data-price="{{ $current }}"
                                            data-image="{{ $video->thumbnail_url }}">
                                            <span class="pl-8 pr-6 py-3 font-semibold">Kosárba teszem</span>
                                            <span class="flex items-center justify-center w-[3.1rem] bg-[#c7802f] !text-white group-hover:!text-white">
                                                <svg
                                                    viewBox="0 0 14 14"
                                                    class="h-4 w-4 translate-x-[-5px]"
                                                    fill="currentColor"
                                                    aria-hidden="true"
                                                >
                                                    <path d="M10 2l4 5-4 5V2z"/>
                                                </svg>
                                            </span>
                                        </button>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    {{ $videos->links('vendor.pagination.custom') }}

                </div>
            </div>
            {{-- SIDEBAR --}}
            @if(!empty($tiles) && count($tiles))
                <aside class="hidden lg:block w-[352px] shrink-0">
                    <div class="sticky top-24 space-y-6">
                        @foreach($tiles as $tile)
                            {!! $tile->content !!}
                        @endforeach
                    </div>
                </aside>
            @endif
        </div>
    </div>
@endsection
