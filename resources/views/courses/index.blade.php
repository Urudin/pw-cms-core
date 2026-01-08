@extends('layouts.app')

@section('content')
    <div class="pageContainer bg-white">
        <div class="lg:flex lg:gap-8">

            <div class="lg:flex-1 min-w-0">
                <div class="bg-gray-100 p-4 md:p-8 shadow-md w-full mx-auto space-y-8">
                    <div>
                        <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-slate-900">
                            Tanfolyamtípusok témakörönként
                        </h1>

                        <p class="max-w-4xl text-sm sm:text-base font-semibold leading-relaxed text-slate-700">
                            Engedje meg, hogy segítséget nyújtsunk a képzéseink közötti könnyebb tájékozódás, valamint az igényeinek
                            legjobban megfelelő képzéstípus kiválasztásának megkönnyítésére!
                        </p>
                    </div>

                    <div class="space-y-10">
                        @foreach($categories as $category)
                            <section class="space-y-4">
                                <h3 class="text-xl font-extrabold text-[#5035e6]">
                                    Tanfolyamok {{ $category->name }} témakörben
                                </h3>

                                <div class="h-px bg-gray-200"></div>

                                <div class="space-y-3">
                                    @foreach($category->courses as $course)
                                        @php
                                            $courseId = $course->id;
                                            $hasActive = $course->actualCourses?->where('course_id', $courseId)->count() || ($course->actualCourses?->count() ?? 0);
                                            $activeDates = ($course->actualCourses ?? collect())
                                                ->sortBy('start_date')
                                                ->pluck('start_date')
                                                ->map(fn($d) => \Carbon\Carbon::parse($d)->format('Y.m.d.'))
                                                ->unique()
                                                ->values();

                                            $firstStart = optional(($course->actualCourses ?? collect())->sortBy('start_date')->first())->start_date;
                                            $firstStartText = $firstStart ? \Carbon\Carbon::parse($firstStart)->format('Y.m.d.') : null;
                                        @endphp

                                        <div
                                            x-data="{ open: false }"
                                            class="bg-white border border-gray-200 shadow-sm"
                                            id="course-{{ $course->id }}"
                                        >
                                            <button
                                                type="button"
                                                @click="open = !open"
                                                class="w-full flex items-start gap-4 px-5 py-4 text-left"
                                            >
                                                <div class="pt-0.5">
                                                    <svg class="w-5 h-5 text-slate-600 transition-transform duration-200" :class="open ? 'rotate-90' : ''" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 0 1 0-1.414L10.586 10 7.293 6.707a1 1 0 1 1 1.414-1.414l4 4a1 1 0 0 1 0 1.414l-4 4a1 1 0 0 1-1.414 0Z" clip-rule="evenodd" />
                                                    </svg>
                                                </div>

                                                <div class="flex-1 min-w-0">
                                                    <div class="font-extrabold text-slate-900 leading-snug">
                                                        {{ $loop->iteration }}. {{ $course->name }}
                                                    </div>

                                                    @if($course->description)
                                                        <div class="mt-1 text-sm text-slate-600 line-clamp-2">
                                                            {!! strip_tags($course->description) !!}
                                                        </div>
                                                    @endif
                                                </div>

                                                <div class="shrink-0 text-right">
                                                    @if($firstStartText)
                                                        <div class="text-xs font-semibold text-slate-500">Aktuális időpontok</div>
                                                        <div class="text-sm font-extrabold text-slate-800">{{ $firstStartText }}</div>
                                                    @else
                                                        <div class="text-sm font-semibold text-slate-500">Nincs meghirdetett időpont</div>
                                                    @endif
                                                </div>
                                            </button>

                                            <div x-cloak x-show="open" x-transition class="px-5 pb-5">
                                                <div class="pt-3 border-t border-gray-200 space-y-5">
                                                    <div class="space-y-2">
                                                        <div class="font-extrabold text-[#5035e6]">Rövid leírás</div>
                                                        <div class="text-slate-700 leading-relaxed text-sm sm:text-base">
                                                            @if($course->description)
                                                                {!! $course->description !!}
                                                            @else
                                                                <span class="text-slate-500">Ehhez a tanfolyamhoz még nincs feltöltve leírás.</span>
                                                            @endif
                                                        </div>
                                                    </div>

                                                    <div class="space-y-2">
                                                        <div class="font-extrabold text-[#5035e6]">További információk</div>
                                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm sm:text-base">
                                                            <div class="bg-gray-50 border border-gray-200 p-3">
                                                                <div class="text-slate-500 font-semibold">Képzés díja</div>
                                                                <div class="font-extrabold text-slate-900">
                                                                    {{ number_format($course->price, 0, ',', ' ') }} Ft / fő
                                                                </div>
                                                            </div>

                                                            <div class="bg-gray-50 border border-gray-200 p-3">
                                                                <div class="text-slate-500 font-semibold">Felnőttképzés rendszerében</div>
                                                                <div class="font-extrabold text-slate-900">Igen</div>
                                                            </div>

                                                            <div class="bg-gray-50 border border-gray-200 p-3 sm:col-span-2">
                                                                <div class="text-slate-500 font-semibold">Aktuális tanfolyam időpontok</div>
                                                                <div class="font-extrabold text-slate-900">
                                                                    @if($activeDates->count())
                                                                        {{ $activeDates->implode(', ') }}
                                                                    @else
                                                                        <span class="text-slate-500 font-semibold">Nincs meghirdetett időpont</span>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="flex flex-wrap gap-3 pt-1">
                                                        <a
                                                            class="inline-flex items-center justify-between bg-[#143c5a] hover:bg-[#39a7cc] text-white"
                                                            href="{{ route('course-applications.create', ['tanfolyam' => $course->id]) }}#tanfolyamvalaszto"
                                                        >
                                                            <span class="pr-[24px] pl-[16px] font-medium">Jelentkezem</span>
                                                            <span class="pl-[16px] pr-[16px] border border-[#39a7cc] bg-[#39a7cc] pt-[12px] pb-[12px]">▸</span>
                                                        </a>

                                                        <a
                                                            class="inline-flex items-center justify-between bg-white hover:bg-gray-50 text-[#143c5a] border border-[#143c5a]"
                                                            href="#tovabbi-info"
                                                        >
                                                            <span class="pr-[24px] pl-[16px] font-medium">További információt kérek</span>
                                                            <span class="pl-[16px] pr-[16px] pt-[12px] pb-[12px] border-l border-[#143c5a]">▸</span>
                                                        </a>

                                                        @if(!empty($course->pdf_url))
                                                            <a
                                                                class="inline-flex items-center justify-between bg-white hover:bg-gray-50 text-[#143c5a] border border-[#143c5a]"
                                                                href="{{ $course->pdf_url }}"
                                                                target="_blank"
                                                                rel="noopener"
                                                            >
                                                                <span class="pr-[24px] pl-[16px] font-medium">Tanfolyam információk letöltése</span>
                                                                <span class="pl-[16px] pr-[16px] pt-[12px] pb-[12px] border-l border-[#143c5a]">▸</span>
                                                            </a>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </section>
                        @endforeach
                    </div>

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

            @if(!empty($tiles) && count($tiles))
                <aside class="hidden lg:block w-[460px] shrink-0">
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
