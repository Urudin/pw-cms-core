@extends('layouts.app')

@section('content')
    <div class="pageContainer bg-white">
        @foreach($page->pageBlocks as $section)
            {!!$section?->wrap_section_start ?? '' !!}
            @foreach($section->blocks as $item)
                @if($item->block->name === 'referenciaink-partnereink')
                    @include('references')
                @else
                    {!!$item->block->content!!}
                @endif
            @endforeach
            {!!$section?->wrap_section_close ?? '' !!}
        @endforeach
    </div>
@endsection
