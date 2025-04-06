@extends('layouts.app')

@section('content')
    <link rel="stylesheet" href="{{asset('simple.css')}}">
    <div class="pageContainer p-[40px] bg-white">
        <h2>Tisztelt Érdeklődő!</h2>
        <p class="mb-6">
            Az Glósz és Tsa System oldalról elküldött üzenetét megkaptuk. Érdeklődését köszönjük!
            Kollégáink rövidesen jelentkeznek Önnél az információkérése során megadott elérhetőségein.
        </p>
        <p>
            Üdvözlettel,<br>
            a Glósz és Tsa System csapata
        </p>
    </div>
@endsection
