@extends('layouts.app')

@section('content')
    <style>
        .pageContainer p{
            margin-bottom: 0.5%;
        }

        .pageContainer ul{
            list-style-type: disc;
            padding-left: 1.5rem;
        }

        .pageContainer ol {
            list-style-type: decimal;
            padding-left: 1.5rem;
        }

        .pageContainer hr {
            width: 100%;
            border: 0;
            height: 1px;
            background-color: #ccc;
        }

        .pageContainer a {
            color: #39a7cc;
            cursor: pointer;
        }

        .pageContainer a:hover {
            color: #B58E03;
        }

        .pageContainer h1, .pageContainer h2, .pageContainer h3, .pageContainer h4, .pageContainer h5, .pageContainer h6 {
            margin-bottom: 0.83em;
            font-weight: bold;
        }

        /* Font sizes for each heading level (common default sizes) */
        .pageContainer h1 {
            font-size: 250%; /* Usually 32px */
            margin-bottom: 2%;
            color: #666666;
        }

        .pageContainer h2 {
            font-size: 180%; /* Usually 24px */
            color:#666666;
            margin-bottom: 1%;
        }

        .pageContainer h3 {
            font-size: 130%; /* Usually 18.72px */
            color:#666666;
            margin-bottom: 1%;
        }

        .pageContainer h4 {
            font-size: 1em; /* Usually 16px */
        }

        .pageContainer h5 {
            font-size: 0.83em; /* Usually 13.28px */
        }

        .pageContainer h6 {
            font-size: 0.67em; /* Usually 10.72px */
        }

        /* Line heights (can be adjusted to improve readability) */
        .pageContainer h1, .pageContainer h2, .pageContainer h3, .pageContainer h4, .pageContainer h5, .pageContainer h6 {
            line-height: 1.2;
        }
    </style>
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
