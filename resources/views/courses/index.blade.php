@extends('layouts.app')

@section('content')
    <div class="pageContainer bg-white">
        <div class="lg:flex lg:gap-8">

            <div class="lg:flex-1 min-w-0">
                <div class="bg-gray-100 p-4 md:p-8 shadow-md w-full mx-auto space-y-12">

                    {{-- PAGE HEADER --}}
                    <div>
                        <h1 class="text-4xl sm:text-5xl font-black tracking-tight text-slate-900">
                            Tanfolyamtípusok témakörönként
                        </h1>
                        <div class="mt-3 h-1 w-24 bg-[#5035e6]"></div>

                        <p class="mt-4 max-w-4xl text-sm sm:text-base font-semibold leading-relaxed text-slate-700">
                            Engedje meg, hogy segítséget nyújtsunk a képzéseink közötti könnyebb tájékozódás,
                            valamint az igényeinek legjobban megfelelő képzéstípus kiválasztásának megkönnyítésére!
                        </p>
                    </div>

                    {{-- CATEGORIES --}}
                    <div class="space-y-14">
                        @foreach($categories as $category)
                            <section class="space-y-6">

                                <h3 class="text-2xl sm:text-3xl font-black text-slate-900">
                                    Tanfolyamok
                                    <span class="text-[#5035e6]">{{ $category->name }}</span>
                                    témakörben
                                </h3>

                                <div class="space-y-0">
                                    @foreach($category->courses as $course)
                                        @php
                                            $activeDates = ($course->actualCourses ?? collect())
                                                ->sortBy('start_date')
                                                ->pluck('start_date')
                                                ->map(fn($d) => \Carbon\Carbon::parse($d)->format('Y.m.d.'))
                                                ->unique()
                                                ->values();

                                            $firstStart = optional(($course->actualCourses ?? collect())->sortBy('start_date')->first())->start_date;
                                            $firstStartText = $firstStart ? \Carbon\Carbon::parse($firstStart)->format('Y.m.d.') : null;
                                        @endphp

                                        {{-- COURSE CARD --}}
                                        <div
                                            x-data="{ open: false }"
                                            id="course-{{ $course->id }}"
                                            class="bg-slate-700 text-white border border-white shadow-sm transition hover:shadow-md"
                                            :class="open ? 'ring-1 ring-[#39a7cc]/60' : ''"
                                        >
                                            <div class="border-r-4 border-[#39a7cc]">

                                                {{-- HEADER --}}
                                                <button
                                                    type="button"
                                                    @click="open = !open"
                                                    class="w-full flex items-start gap-4 px-5 py-4 text-left"
                                                >
                                                    <div class="pt-1">
                                                        <svg class="w-5 h-5 text-white/80 transition-transform duration-200"
                                                             :class="open ? 'rotate-90' : ''"
                                                             viewBox="0 0 20 20" fill="currentColor">
                                                            <path fill-rule="evenodd"
                                                                  d="M7.293 14.707a1 1 0 0 1 0-1.414L10.586 10 7.293 6.707a1 1 0 1 1 1.414-1.414l4 4a1 1 0 0 1 0 1.414l-4 4a1 1 0 0 1-1.414 0Z"
                                                                  clip-rule="evenodd" />
                                                        </svg>
                                                    </div>

                                                    <div class="flex-1 min-w-0">
                                                        <div class="font-black text-lg leading-snug">
                                                            {{ $loop->iteration }}. {{ $course->name }}
                                                        </div>

                                                        @if($course->description)
                                                            <div class="mt-1 text-sm text-white/70 line-clamp-2">
                                                                {!! html_entity_decode(strip_tags($course->description)) !!}
                                                            </div>
                                                        @endif
                                                    </div>

                                                    <div class="shrink-0 text-right">
                                                        @if($firstStartText)
                                                            <div class="text-xs font-semibold text-white/60">Aktuális időpontok</div>
                                                            <div class="text-sm font-extrabold text-white">{{ $firstStartText }}</div>
                                                        @else
                                                            <div class="text-sm font-semibold text-white/60">Nincs meghirdetett időpont</div>
                                                        @endif
                                                    </div>
                                                </button>

                                                {{-- EXPANDED CONTENT --}}
                                                <div x-cloak x-show="open" x-transition class="px-5 pb-6">
                                                    <div class="pt-4 border-t border-white/10 space-y-6">

                                                        {{-- DESCRIPTION --}}
                                                        <div class="space-y-2">
                                                            <div class="font-extrabold text-[#39a7cc]">Rövid leírás</div>
                                                            <div class="text-white/80 leading-relaxed text-sm sm:text-base">
                                                                @if($course->description)
                                                                    {!! html_entity_decode($course->description) !!}
                                                                @else
                                                                    <span class="text-white/50">Ehhez a tanfolyamhoz még nincs feltöltve leírás.</span>
                                                                @endif
                                                            </div>
                                                        </div>

                                                        {{-- INFO BOXES --}}
                                                        <div class="space-y-2">
                                                            <div class="font-extrabold text-[#39a7cc]">További információk</div>

                                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm sm:text-base">
                                                                <div class="bg-slate-800/60 border border-slate-700 p-3">
                                                                    <div class="text-white/60 font-semibold">Képzés díja</div>
                                                                    <div class="font-extrabold text-white">
                                                                        {{ number_format($course->price, 0, ',', ' ') }} Ft / fő
                                                                    </div>
                                                                </div>

                                                                <div class="bg-slate-800/60 border border-slate-700 p-3">
                                                                    <div class="text-white/60 font-semibold">Felnőttképzés rendszerében</div>
                                                                    <div class="font-extrabold text-white">Igen</div>
                                                                </div>

                                                                <div class="bg-slate-800/60 border border-slate-700 p-3 sm:col-span-2">
                                                                    <div class="text-white/60 font-semibold">Aktuális tanfolyam időpontok</div>
                                                                    <div class="font-extrabold text-white">
                                                                        @if($activeDates->count())
                                                                            {{ $activeDates->implode(', ') }}
                                                                        @else
                                                                            <span class="text-white/50 font-semibold">Nincs meghirdetett időpont</span>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        {{-- ACTION BUTTONS (RESTORED ORIGINAL STRUCTURE) --}}
                                                        <div class="flex flex-wrap gap-3 pt-1">
                                                            <a
                                                                class="inline-flex items-center justify-between bg-[#143c5a] hover:bg-[#39a7cc] text-white"
                                                                href="{{ route('course-applications.create', ['tanfolyam' => $course->id]) }}#tanfolyamvalaszto"
                                                            >
                                                                <span class="pr-[24px] pl-[16px] font-medium">Jelentkezem</span>
                                                                <span class="pl-[16px] pr-[16px] border border-[#39a7cc] bg-[#39a7cc] pt-[12px] pb-[12px]">▸</span>
                                                            </a>

                                                            <a
                                                                class="inline-flex items-center justify-between bg-slate-900 hover:bg-slate-800 text-white border border-white/20"
                                                                href="#tovabbi-info"
                                                            >
                                                                <span class="pr-[24px] pl-[16px] font-medium">További információt kérek</span>
                                                                <span class="pl-[16px] pr-[16px] pt-[12px] pb-[12px] border-l border-white/20">▸</span>
                                                            </a>

                                                            @if(!empty($course->pdf_url))
                                                                <a
                                                                    class="inline-flex items-center justify-between bg-slate-900 hover:bg-slate-800 text-white border border-white/20"
                                                                    href="{{ $course->pdf_url }}"
                                                                    target="_blank"
                                                                    rel="noopener"
                                                                >
                                                                    <span class="pr-[24px] pl-[16px] font-medium">Tanfolyam információk letöltése</span>
                                                                    <span class="pl-[16px] pr-[16px] pt-[12px] pb-[12px] border-l border-white/20">▸</span>
                                                                </a>
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

                    {{-- CONTACT FORM (UNCHANGED - FULL ORIGINAL) --}}
                    <div id="tovabbi-info" class="bg-white border border-gray-200 p-4 md:p-6 space-y-4">
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
                                <span class="font-medium text-[18px] text-gray-700 pl-[10px]">
                                    A checkbox bepipálásával hozzájárulok, hogy az adatkezelő a most megadott személyes adataimat az Adatvédelmi Rendelet,
                                    továbbá az oldal <a href="#" class="text-blue-500 underline">Adatkezelési tájékoztatójának</a> feltételei és az oldal
                                    <a href="#" class="text-blue-500 underline">Szerződési feltételeiben</a> leírtak szerint kezelje, és információt, üzleti ajánlatot küldjön a számomra.
                                    Tudomásul veszem, hogy a hozzájárulásomat bármikor visszavonhatom az adatkezelőnek küldött ez irányú kéréssel.
                                </span>
                            </label>

                            <div class="mt-6">
                                <button type="submit" class="bg-[#143c5a] hover:bg-[#39a7cc] text-white pb-[16px] py-[15px]">
                                    <span class="pr-[30px] pl-[20px] font-medium">Üzenet küldése</span>
                                    <span class="pl-[20px] pr-[20px] pt-[16px] pb-[19px] border border-[#39a7cc] bg-[#39a7cc]">▸</span>
                                </button>
                            </div>
                        </form>
                    </div>

                </div>
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
