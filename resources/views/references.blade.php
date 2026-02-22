@php
    $key = 'innovaciomenedzsment-oktatas';
    $altTitle = 'Referenciánk - Innovációmenedzsment képzés';

    $folderPath = public_path("storage/referenciak/$key");
    $files = Illuminate\Support\Facades\File::exists($folderPath)
        ? Illuminate\Support\Facades\File::files($folderPath)
        : [];
@endphp

<section class="px-4 sm:px-6 lg:px-8 py-10 space-y-10">
    <header class="space-y-4">
        <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-slate-900">
            Referenciáink, partnereink
        </h1>
    </header>


    <p class="text-slate-900">
        Az elmúlt években az Innovációmenedzsment Akadémia weboldalt üzemeltető Glósz és Társa SYSTEM Csapata számos projektet bonyolított le a kutatás-fejlesztés, innovációmenedzsment,
        innovációmenedzsment oktatás, képzés területén. Referenciáinkban az elmúlt évek sikeres innovációs projektjeiből szemezgettünk.
    </p>

    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6 mt-6">
        @foreach ($files as $file)
            <div class="flip-card aspect-square perspective">
                <div class="flip-card-inner relative w-full h-full">
                    <!-- Front (bw) -->
                    <div class="absolute inset-0 backface-hidden transform rotate-y-0 flex items-center justify-center">
                        <img
                            src="{{ asset("storage/referenciak/$key/" . $file->getFilename()) }}"
                            class="max-w-full max-h-full object-contain filter grayscale brightness-[0.93]"
                            alt="{{ $altTitle }}"
                            title="{{ $altTitle }}"
                        />
                    </div>

                    <!-- Back (color) -->
                    <div class="absolute inset-0 backface-hidden transform rotate-y-180 flex items-center justify-center">
                        <img
                            src="{{ asset("storage/referenciak/$key/" . $file->getFilename()) }}"
                            class="max-w-full max-h-full object-contain"
                            alt="{{ $altTitle }}"
                            title="{{ $altTitle }}"
                        />
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</section>
