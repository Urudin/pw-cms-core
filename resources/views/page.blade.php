@extends('layouts.app')

@section('content')
    @foreach($page->pageBlocks as $section)
        {!!$section?->wrap_section_start ?? '' !!}
        @foreach($section->blocks as $item)
            {!!$item->block->content!!}
        @endforeach
        {!!$section?->wrap_section_close ?? '' !!}
    @endforeach
@endsection
