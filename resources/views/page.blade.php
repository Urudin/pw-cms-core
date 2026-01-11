@extends('layouts.app')

@section('content')
    <div class="pageContainer bg-white">
        <div class="lg:flex lg:gap-8">
            {{-- LEFT: page content --}}
            <div class="lg:flex-1 min-w-0">
                @foreach($page->pageBlocks as $section)
                    {!! $section?->wrap_section_start ?? '' !!}
                    @foreach($section->blocks as $item)
                        @if($item->block->name === 'referenciaink-partnereink')
                            @include('references')
                        @else
                            {!! $item->block->content !!}
                        @endif
                    @endforeach
                    {!! $section?->wrap_section_close ?? '' !!}
                @endforeach
            </div>

            {{-- RIGHT: tiles --}}
            @if(!empty($page->tiles) && count($page->tiles))
                <aside class="hidden lg:block w-[352px] shrink-0">
                    <div class="sticky top-24 space-y-6">
                        @foreach($page->tiles as $tile)
                            {!! $tile->content !!}
                        @endforeach
                    </div>
                </aside>
            @endif
        </div>
    </div>
@endsection
