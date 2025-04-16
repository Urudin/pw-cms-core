@extends('layouts.app')

@section('content')
    <div class="pageContainer bg-white">
        @foreach($page->pageBlocks as $section)
            {!!$section?->wrap_section_start ?? '' !!}
            @foreach($section->blocks as $item)
                {!!$item->block->content!!}
            @endforeach
            {!!$section?->wrap_section_close ?? '' !!}
        @endforeach
    </div>
@endsection
