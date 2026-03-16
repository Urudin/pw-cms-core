@extends('layouts.app')

@section('content')
    <div class="pageContainer bg-white">
        <div class="lg:flex lg:gap-8">
            <style>
                .course-desc h2{
                    color: #22d3ee !important;
                }
            </style>
            <div class="lg:flex-1 min-w-0">
                <section class="px-4 sm:px-6 lg:px-8 py-10 space-y-10">

                    {{-- PAGE HEADER --}}
                    <header class="space-y-4">
                        <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-slate-900">
                            Tanfolyamtípusok témakörönként
                        </h1>

                        <p class="text-base text-slate-700 mt-6">
                            Engedje meg, hogy segítséget nyújtsunk a képzéseink közötti könnyebb tájékozódás,
                            valamint az igényeinek legjobban megfelelő képzéstípus kiválasztásának megkönnyítésére!
                        </p>
                    </header>

                    {{-- CATEGORIES --}}
                    <div class="space-y-14">
                        @foreach($categories as $category)
                            @if($category->courses()->where('listed', 1)->where('is_active', 1)->count() == 0)
                                @continue
                            @endif
                            <section class="space-y-6">

                                <h3 class="text-2xl sm:text-3xl font-black text-slate-900">
                                    Tanfolyamok
                                    {{ strtolower($category->name) }}
                                    témakörben
                                </h3>

                                    <div x-data="{ open: null }" class="space-y-0">
                                        @foreach($category->courses()->where('listed', 1)->where('is_active', 1)->get() as $course)
                                            @php
                                                $today = now()->startOfDay();

                                                $nearestActualCourse = ($course->actualCourses ?? collect())
                                                    ->filter(function ($actualCourse) use ($today) {
                                                        return filled($actualCourse->start_date)
                                                            && \Carbon\Carbon::parse($actualCourse->start_date)->greaterThanOrEqualTo($today);
                                                    })
                                                    ->sortBy('start_date')
                                                    ->first();

                                                $nextStartText = $nearestActualCourse?->start_date
                                                    ? \Carbon\Carbon::parse($nearestActualCourse->start_date)->format('Y.m.d.')
                                                    : 'hamarosan';

                                                $courseTitle = $course->title ?: $course->name;
                                            @endphp

                                            <div
                                                id="course-{{ $course->id }}"
                                                class="bg-slate-700 text-white border border-white shadow-sm transition hover:shadow-md"
                                                :class="open === {{ $course->id }} ? 'ring-1 ring-[#39a7cc]/60' : ''"
                                            >
                                                <div class="flex items-stretch border-r-4 border-[#39a7cc] min-w-0">
                                                    {{-- ACCORDION TOGGLE AREA --}}
                                                    <div
                                                        class="flex-1 flex items-stretch min-w-0 cursor-pointer"
                                                        @click="open = open === {{ $course->id }} ? null : {{ $course->id }}"
                                                        role="button"
                                                        tabindex="0"
                                                        @keydown.enter.prevent="open = open === {{ $course->id }} ? null : {{ $course->id }}"
                                                        @keydown.space.prevent="open = open === {{ $course->id }} ? null : {{ $course->id }}"
                                                    >
                                                        {{-- BAL KÉK SÁV --}}
                                                        <div class="w-[82px] bg-[#39a7cc] flex items-center justify-center shrink-0">
                                                            <svg
                                                                class="w-5 h-5 text-white transition-transform duration-700 ease-in-out"
                                                                :class="open === {{ $course->id }} ? 'rotate-90' : ''"
                                                                viewBox="0 0 20 20"
                                                                fill="currentColor"
                                                            >
                                                                <path
                                                                    fill-rule="evenodd"
                                                                    d="M7.293 14.707a1 1 0 0 1 0-1.414L10.586 10 7.293 6.707a1 1 0 1 1 1.414-1.414l4 4a1 1 0 0 1 0 1.414l-4 4a1 1 0 0 1-1.414 0Z"
                                                                    clip-rule="evenodd"
                                                                />
                                                            </svg>
                                                        </div>

                                                        {{-- TARTALOM --}}
                                                        <div class="flex-1 flex items-start gap-4 px-5 py-4 min-w-0">
                                                            <div class="flex-1 min-w-0">
                                                                {{-- TANFOLYAM NÉV --}}
                                                                @if($course->page?->slug)
                                                                    <a
                                                                        href="{{ route('pages.show', ['slug' => $course->page->slug]) }}"
                                                                        title="{{ $courseTitle }}"
                                                                        @click.stop
                                                                        class="group inline-flex max-w-full items-center gap-2 text-xl font-black leading-snug text-white transition hover:text-[#d4af37]"
                                                                    >
                                <span class="truncate">
                                    {{ $loop->iteration }}. {{ $course->name }}
                                </span>

                                                                        <svg
                                                                            class="w-5 h-5 shrink-0 text-[#d4af37] opacity-0 -translate-x-1 transition-all duration-200 group-hover:opacity-100 group-hover:translate-x-0"
                                                                            viewBox="0 0 20 20"
                                                                            fill="currentColor"
                                                                            aria-hidden="true"
                                                                        >
                                                                            <path
                                                                                fill-rule="evenodd"
                                                                                d="M11.22 5.22a.75.75 0 0 1 1.06 0l4 4a.75.75 0 0 1 0 1.06l-4 4a.75.75 0 1 1-1.06-1.06l2.72-2.72H4.75a.75.75 0 0 1 0-1.5h9.19l-2.72-2.72a.75.75 0 0 1 0-1.06Z"
                                                                                clip-rule="evenodd"
                                                                            />
                                                                        </svg>
                                                                    </a>
                                                                @else
                                                                    <div
                                                                        title="{{ $courseTitle }}"
                                                                        class="inline-flex max-w-full items-center gap-2 text-xl font-black leading-snug text-white"
                                                                    >
                                <span class="truncate">
                                    {{ $loop->iteration }}. {{ $course->name }}
                                </span>
                                                                    </div>
                                                                @endif

                                                                {{-- ACCORDION TOGGLE SZÖVEG --}}
                                                                <div class="mt-2 text-sm text-white/70">
                                                                    <button
                                                                        type="button"
                                                                        @click.stop="open = open === {{ $course->id }} ? null : {{ $course->id }}"
                                                                        class="inline-flex items-center gap-1 text-white/70 transition hover:text-white focus:outline-none"
                                                                    >
                                                                        Kattintson a részletekért
                                                                    </button>
                                                                </div>
                                                            </div>

                                                            {{-- AKTUÁLIS IDŐPONT --}}
                                                            <div class="shrink-0 text-right self-center">
                                                                <div class="text-sm font-semibold text-white/75">
                                                                    Aktuális tanfolyam időpont:
                                                                    <span class="font-extrabold text-white">{{ $nextStartText }}</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                {{-- EXPANDED CONTENT --}}
                                                <div
                                                    x-cloak
                                                    x-show="open === {{ $course->id }}"
                                                    x-transition:enter="transition-all ease-in-out duration-700"
                                                    x-transition:enter-start="opacity-0 max-h-0"
                                                    x-transition:enter-end="opacity-100 max-h-[2000px]"
                                                    x-transition:leave="transition-all ease-in-out duration-700"
                                                    x-transition:leave-start="opacity-100 max-h-[2000px]"
                                                    x-transition:leave-end="opacity-0 max-h-0"
                                                    class="bg-slate-200 px-5 overflow-hidden"
                                                >
                                                    <div class="pt-4 pb-6 border-t border-white/10 space-y-6">
                                                        <div class="space-y-2">
                                                            <div class="text-slate-900 leading-relaxed text-sm sm:text-base course-desc">
                                                                @if($course->description)
                                                                    {!! html_entity_decode($course->description) !!}
                                                                @else
                                                                    <span class="text-slate-500">Ehhez a tanfolyamhoz még nincs feltöltve leírás.</span>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                            </section>
                        @endforeach
                    </div>
                    <section class="">
                        <h1 id="tovabbi-info" class="text-3xl sm:text-4xl mb-4 font-extrabold tracking-tight text-slate-900">
                            További információra van szüksége?
                        </h1>
                        <p class="text-base sm:text-lg font-semibold leading-relaxed text-slate-700">
                            Amennyiben az Innovációmenedzsment Akadémia oktatási programjaival, a tanfolyamokkal, az időponttal, a tananyaggal, a megrendezés módjával, a jelentkezéssel, a fizetési lehetőségekkel, vagy egyéb vonatkozó kérdésekben további információkra van szüksége, kérjük az alábbi kapcsolati űrlapon jelezze felénk és kollégáink a következő munkanapon jelentkeznek a kért információkkal megadott elérhetőségein.                        </p>
                    </section>
                    {{-- CONTACT FORM (UNCHANGED - FULL ORIGINAL) --}}
                    <div class="bg-white border border-gray-200 p-4 md:p-6 space-y-4">
                        <h2 class="text-2xl text-[#143c5a] font-semibold">További információt kérek</h2>

                        <form class="space-y-6" action="#" method="POST">
                            @csrf
                            <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response">

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <input type="text" name="name" placeholder="*Név"
                                           value="{{ old('name') }}"
                                           class="w-full border border-gray-300 p-2 focus:outline-none focus:border-blue-500" required>
                                </div>
                                <div>
                                    <input type="text" name="company" placeholder="Cégnév"
                                           value="{{ old('company') }}"
                                           class="w-full border border-gray-300 p-2 focus:outline-none focus:border-blue-500">
                                </div>
                                <div>
                                    <input type="tel" name="phone" placeholder="*Telefon"
                                           value="{{ old('phone') }}"
                                           class="w-full border border-gray-300 p-2 focus:outline-none focus:border-blue-500" required>
                                </div>
                                <div>
                                    <input type="email" name="email" placeholder="*E-mail cím"
                                           value="{{ old('email') }}"
                                           class="w-full border border-gray-300 p-2 focus:outline-none focus:border-blue-500" required>
                                </div>
                            </div>

                            <div>
                                <textarea name="message" rows="5" placeholder="*Üzenet"
                                          class="w-full border border-gray-300 p-2 focus:outline-none focus:border-blue-500" required>{{ old('message') }}</textarea>
                            </div>

                            <label class="custom-label flex items-start mt-4">
                                <div class="bg-[#ccc] shadow w-6 h-6 p-1 flex justify-center items-center mr-2 relative cursor-pointer">
                                    <input type="checkbox" name="consent" class="sr-only" onchange="this.nextElementSibling.classList.toggle('opacity-0')" required>
                                    <svg class="w-4 h-4 text-[#39a7cc] pointer-events-none opacity-0 transition-opacity duration-200"
                                         viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M5 12.5L10 17.5L19 6.5" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                                <span class="font-medium text-[16px] text-gray-700 pl-[10px]">
                                    A checkbox bepipálásával hozzájárulok, hogy az adatkezelő a most megadott személyes adataimat az Adatvédelmi Rendelet,
                                    továbbá az oldal <a href="#" class="text-blue-500 underline">Adatkezelési tájékoztatójának</a> feltételei és az oldal
                                    <a href="#" class="text-blue-500 underline">Szerződési feltételeiben</a> leírtak szerint kezelje, és információt, üzleti ajánlatot küldjön a számomra.
                                    Tudomásul veszem, hogy a hozzájárulásomat bármikor visszavonhatom az adatkezelőnek küldött ez irányú kéréssel.
                                </span>
                            </label>

                            <div class="mt-6">
                                <button
                                    type="submit"
                                    class="inline-flex items-stretch overflow-hidden bg-[#143c5a] text-white hover:bg-[#39a7cc] transition"
                                >
                                <span class="flex items-center px-5 py-4 font-medium leading-none">
                                    Üzenet küldése
                                </span>
                                <span class="flex items-center border-l border-[#39a7cc] bg-[#39a7cc] px-5 leading-none">
                                    ▸
                                </span>
                                </button>
                            </div>
                        </form>
                    </div>

                </section>
            </div>

            {{-- SIDEBAR --}}
            @if(!empty($tiles) && count($tiles))
                <aside class="hidden lg:block w-[352px] shrink-0">
                    <div class="sticky top-24 space-y-6">
                        @foreach($tiles as $tile)
                            {!! $tile->content !!}
                        @endforeach
                    </div>
                </aside>
            @endif

        </div>
    </div>
@endsection
