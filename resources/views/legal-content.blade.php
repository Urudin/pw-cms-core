@extends('layouts.app')

@push('styles')
    @php
        $legalDocumentsCss = \App\Models\UserSetting::getValueByName('legal-documents-css');
    @endphp

    @if($legalDocumentsCss)
        <style>
            {!! $legalDocumentsCss !!}
        </style>
    @endif
@endpush

@section('content')
    {!! $legalContent->content !!}
@endsection
