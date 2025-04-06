@extends('layouts.app')

@section('content')
    <link rel="stylesheet" href="{{asset('simple.css')}}">

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
