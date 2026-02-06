@extends('layouts.app')

@section('content')
    <div class="w-full mx-auto px-4 py-8">
        <div class="mb-6">
            <h1 class="text-2xl font-bold">{{ $video->title }}</h1>

            @if(!empty($video->description))
                <p class="mt-2 text-gray-600">{{ $video->description }}</p>
            @endif

            @if(!empty($video->duration_seconds))
                @php
                    $total = (int) $video->duration_seconds;
                    $h = intdiv($total, 3600);
                    $m = intdiv($total % 3600, 60);
                    $s = $total % 60;
                    $duration = $h > 0
                        ? sprintf('%d:%02d:%02d', $h, $m, $s)
                        : sprintf('%d:%02d', $m, $s);
                @endphp

                <div class="mt-2 text-sm text-gray-500">
                    Hossz: <span class="font-medium text-gray-700">{{ $duration }}</span>
                </div>
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
@endsection
