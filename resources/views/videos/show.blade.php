@extends('layouts.app')

@section('content')
    <div class="mb-6 space-y-4">

        <div class="p-4 bg-gray-50 border border-gray-200 rounded-lg space-y-3">

            <h1 class="text-2xl font-bold">{{ $video->title }}</h1>

            @if(!empty($video->description))
                <p class="text-gray-600">{{ $video->description }}</p>
            @endif

            @php
                $total = (int) $video->duration_seconds;
                $h = intdiv($total, 3600);
                $m = intdiv($total % 3600, 60);
                $s = $total % 60;

                $duration = $h > 0
                    ? sprintf('%d:%02d:%02d', $h, $m, $s)
                    : sprintf('%d:%02d', $m, $s);
            @endphp
            {{-- típus + hossz --}}
            <div class="flex flex-wrap items-center justify-between gap-3">
            <span class="text-[11px] font-bold bg-slate-800 text-[#69b7ff] px-3 py-1 uppercase">
                {{ $video->type->name ?? 'ONLINE TANFOLYAM' }}
            </span>

                @if(!empty($video->duration_seconds))
                    <span class="text-sm text-slate-500 flex items-center gap-1">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M8 5v14l11-7z"></path>
                    </svg>
                    {{ $duration }}
                </span>
                @endif
            </div>

            {{-- topic / domain --}}
            <div class="flex gap-2 flex-wrap">
                @if($video->topic)
                    <span class="text-[10px] px-2 py-[2px] border-2 border-slate-900/60 text-slate-700 bg-white font-bold">
                    {{ strtoupper($video->topic->name) }}
                </span>
                @endif

                @if($video->domain)
                    <span class="text-[10px] px-2 py-[2px] border-2 border-slate-900/60 text-slate-700 bg-white font-bold">
                    {{ strtoupper($video->domain->name) }}
                </span>
                @endif
            </div>


        @if($embedUrl)
            <div class="bg-black/5 border border-gray-200 rounded-lg overflow-hidden">
                <div class="relative w-full" style="padding-top: 56.25%;">
                    <iframe
                        class="absolute inset-0 w-full h-full"
                        src="{{ $embedUrl }}"
                        title="{{ $video->title }}"
                        frameborder="0"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                        allowfullscreen
                    ></iframe>
                </div>
            </div>
        @else
            <div class="p-4 bg-red-50 border border-red-200 text-red-800 rounded-lg">
                Nem sikerült YouTube embed URL-t előállítani a megadott <code class="font-mono">{{ $video->video_url }}</code> alapján.
            </div>
        @endif
        </div>
    </div>
@endsection
