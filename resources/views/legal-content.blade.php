@extends('layouts.app')

<link href="{{asset('css/data-handling.css')}}" rel="stylesheet">
<style>
    #content {
        background-color: #ffffff;
        padding: 20px;
    }
    #akademia h1 {
        font-size: 2em;
        margin: 0.67em 0;
        font-weight: bold;
    }
    .data-related-rights
    {
        font-weight: 600;
        border-left: 5px #5035e6 solid;
        margin-left: 0 ! important;
        padding: 10px 20px;
        background-color: #dd5346;
    }
    #akademia h2 {
        font-size: 1.5em;
        margin: 0.83em 0;
        font-weight: bold;
    }

    #akademia h3 {
        font-size: 1.17em;
        margin: 1em 0;
        font-weight: bold;
    }

    .header-bg-gray {
        background-color: #ADBCCF
    }

    #akademia h4 {
        font-size: 1em;
        margin: 1.33em 0;
        font-weight: bold;
    }

    #akademia h5 {
        font-size: 0.83em;
        margin: 1.67em 0;
        font-weight: bold;
    }

    #akademia h6 {
        font-size: 0.67em;
        margin: 2.33em 0;
        font-weight: bold;
    }

    #akademia p {
        margin: 1em 0;
        line-height: 1.6;
    }

    /* Táblázat – klasszikus kinézet */
    #akademia table:not(.no-akademia-style) {
        width: 100%;
        border-collapse: collapse !important;
        margin: 1.25em 0;
        font-size: 0.95rem;
        border: 2px solid #000 !important;
    }

    /* Cellák */
    #akademia table:not(.no-akademia-style) th,
    #akademia table:not(.no-akademia-style) td {
        border: 2px solid #000 !important;
        padding: 0.5rem 0.75rem;
        text-align: left;
        vertical-align: top;
    }

    /* Fejléc sor */
    #akademia table:not(.no-akademia-style) th {
        background: #f1f5f9;
        font-weight: 600;
    }

    /* Zebra csíkozás */
    #akademia table:not(.no-akademia-style) tbody tr:nth-child(even) {
        background: #f8fafc;
    }

    /* Ha túl széles a táblázat mobilon */
    #akademia .table-wrap {
        overflow-x: auto;
    }
</style>
@section('content')
    {!!  $legalContent->content !!}
@endsection
