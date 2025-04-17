@extends('layouts.app')
@php
    $folders = [
        'innovaciomenedzsment' => 'Innovációmenedzsment',
        'innovaciomenedzsment-oktatas' => 'Innovációmenedzsment képzés',
        'penzugyi-szolgaltatasok' => 'Pénzügyi szolgáltatások',
        'iparjogvedelem' => 'Iparjogvédelem',
    ];
@endphp
@section('content')
    <div class="px-[25px] pb-[25px] bg-white" x-data="{ activeTab: '{{ array_key_first($folders) }}' }">
        <h1 class="text-[#143c5a] tracking-wider font-bold text-3xl py-[25px]">REFERENCIÁINK, PARTNEREINK</h1>
        <p class="text-lg pb-3 text-[#3E3D56]">
            Az elmúlt években, évtizedekben az innovációmenedzsment weboldalt üzemeltető Glósz és Társa SYSTEM Csapata számos projektet bonyolított le a kutatás-fejlesztés, innovációmenedzsment, innovációmenedzsment képzés, iparjogvédelem, forrásszerzés, vállalati pénzügyi szolgáltatások területén. Referenciáinkban az elmúlt évek sikeres projektjeiből szemezgettünk.
        </p>
        <!-- Tabok -->
        <div class="flex border-b-[2px]  border-[#B58E03] ">
            @foreach ($folders as $key => $label)
                <button
                    @click="activeTab = '{{ $key }}'"
                    :class="activeTab === '{{ $key }}'
                        ? 'bg-[#B58E03] text-white font-bold'
                        : 'bg-gray-100 text-[#839ea9] font-bold hover:bg-gray-300 hover:text-white'"
                    class="px-2 py-3 text-sm border-r border-gray-300 focus:outline-none transition w-[25%]">
                    <span class="text-lg">{{ $label }}</span>
                </button>
            @endforeach
        </div>

        <!-- Tartalom -->
        @foreach ($folders as $key => $label)
            @php
                $files = Illuminate\Support\Facades\File::exists(public_path("storage/referenciak/$key"))
                    ? Illuminate\Support\Facades\File::files(public_path("storage/referenciak/$key"))
                    : [];
            @endphp

            <div x-show="activeTab === '{{ $key }}'" class="grid grid-cols-5 gap-6 mt-6">
                @foreach ($files as $file)
                    <div class="flip-card aspect-square perspective">
                        <div class="flip-card-inner relative w-full h-full">
                            <!-- Front (bw) -->
                            <div
                                class="absolute inset-0 backface-hidden transform rotate-y-0 flex items-center justify-center">
                                <img src="{{ asset("storage/referenciak/$key/" . $file->getFilename()) }}"
                                     class="max-w-full max-h-full object-contain filter grayscale brightness-[0.93]"
                                     alt="Image"/>
                            </div>
                            <!-- Back (color) -->
                            <div
                                class="absolute inset-0 backface-hidden transform rotate-y-180 flex items-center justify-center">
                                <img src="{{ asset("storage/referenciak/$key/" . $file->getFilename()) }}"
                                     class="max-w-full max-h-full object-contain"
                                     alt="Image Colored"/>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>
    {!!\App\Models\Block::query()->find(\App\Models\UserSetting::query()->firstWHere('name', 'contact-form-block-id')->value)->content!!}

@endsection
