@extends('layouts.app')

@section('content')
    <div class="pageContainer bg-gray-100">
        <div class="lg:flex lg:gap-8">
            <style>
                .how-list li {
                    display: flex;
                    align-items: flex-start;
                }

                .how-list li::before {
                    content: "";
                    width: 0;
                    height: 0;
                    border-bottom: 12px #00d4fb solid;
                    border-right: 12px solid transparent;
                    margin: 5px 10px 0 0;
                    flex: 0 0 auto;
                }
            </style>
            {{-- LEFT: page content --}}
            <div class="lg:flex-1 min-w-0">
                <section class="px-4 sm:px-6 lg:px-8 py-10 space-y-10">
                    <header class="space-y-4">
                        <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-slate-900">
                            Jelentkezés tanfolyamra
                        </h1>

                        <p class="text-base text-slate-700 mt-6">
                            Előre meghirdetett tanfolyamot keres: <strong>Jó helyen jár!</strong>
                        </p>
                    </header>

                    @if(session('success'))
                        <div class="border border-green-200 bg-green-50 p-4 text-green-800">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="border border-red-200 bg-red-50 p-4 text-red-800">
                            <div class="font-semibold mb-2">Hibás kitöltés:</div>
                            <ul class="list-disc pl-5 space-y-1">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <div class="bg-gray-300 p-6 md:p-8">
                        <h2 class="text-xl font-extrabold text-[#1f355e] mb-6 leading-tight">
                            Hogyan tovább, ha jelentkezni szeretne egy meghirdetett tanfolyamra:
                        </h2>

                        <div class="space-y-1">
                            <div class="flex items-start gap-4">
                                <div class="shrink-0 bg-[#11c5e8] text-white font-bold text-sm leading-none px-2 py-1 min-w-[2rem] text-center">
                                    1.
                                </div>
                                <p class="text-base leading-snug text-[#1f355e]">
                                    Válassza ki, és pipálással jelölje meg, hogy melyik tanfolyamra jelentkezne!
                                </p>
                            </div>

                            <div class="flex items-start gap-4">
                                <div class="shrink-0 bg-[#11c5e8] text-white font-bold text-sm leading-none px-2 py-1 min-w-[2rem] text-center">
                                    2.
                                </div>
                                <p class="text-base leading-snug text-[#1f355e]">
                                    Töltse ki a lap alján található online jelentkezési lapot!
                                </p>
                            </div>

                            <div class="flex items-start gap-4">
                                <div class="shrink-0 bg-[#11c5e8] text-white font-bold text-sm leading-none px-2 py-1 min-w-[2rem] text-center">
                                    3.
                                </div>
                                <p class="text-base leading-snug text-[#1f355e]">
                                    A jelentkezési lap alján jelezze, ha hozzájárul adatai szabályozott kezeléséhez!
                                </p>
                            </div>

                            <div class="flex items-start gap-4">
                                <div class="shrink-0 bg-[#11c5e8] text-white font-bold text-sm leading-none px-2 py-1 min-w-[2rem] text-center">
                                    4.
                                </div>
                                <p class="text-base leading-snug text-[#1f355e]">
                                    Nyomja meg a jelentkezési lap alján a „Jelentkezni szeretnék” gombot!
                                </p>
                            </div>

                            <div class="flex items-start gap-4">
                                <div class="shrink-0 bg-[#11c5e8] text-white font-bold text-sm leading-none px-2 py-1 min-w-[2rem] text-center">
                                    5.
                                </div>
                                <p class="text-base leading-snug text-[#1f355e]">
                                    Sikeres jelentkezését követően, az Ön által megadott e-mail címre elküldjük a jelentkezési lapot és a felnőttképzési szerződést jóváhagyás és aláírás céljára!
                                </p>
                            </div>

                            <div class="flex items-start gap-4">
                                <div class="shrink-0 bg-[#11c5e8] text-white font-bold text-sm leading-none px-2 py-1 min-w-[2rem] text-center">
                                    6.
                                </div>
                                <p class="text-base leading-snug text-[#1f355e]">
                                    Az aláírt dokumentumokat küldje vissza címünkre!
                                </p>
                            </div>
                        </div>
                    </div>
                    <p>
                        A jelentkezéssel kapcsolatos további információkkal kapcsolatban <a class="text-[#11c5e8]" href="/kapcsolat" title="Kérdése van? Lépjen kapcsolatba velünk!"><strong>vegye fel a kapcsolatot</strong></a>
                        kollégánkkal!
                    </p>
                    <form class="space-y-8" action="{{ route('course-applications.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response">

                        <div id="tanfolyamvalaszto" class="space-y-2">
                            <label class="block font-semibold text-gray-700">
                                Kérjük válasszon tanfolyamtípust, vagy tanfolyamot!
                            </label>

                            <select
                                id="courseFilter"
                                class="w-full text-[#00c1e6] font-semibold border-2 border-[#00c1e6] p-2 focus:outline-none"
                            >
                                <option value="all">Minden tanfolyam</option>

                                @foreach($categories as $cat)
                                    <option value="cat:{{ $cat->id }}" class="text-black font-semibold">
                                        ◾&nbsp;&nbsp; {{ $cat->name }}
                                    </option>

                                    @foreach($cat->courses as $course)
                                        <option value="course:{{ $course->id }}" class="text-black">
                                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;○&nbsp;&nbsp; {{ $course->name }}
                                        </option>
                                    @endforeach
                                @endforeach
                            </select>
                        </div>

                        <div
                            x-data="{ openCourse: null }"
                            class="bg-white border border-gray-200 p-4 md:p-6 space-y-4"
                        >
                            <h3 class="text-xl text-[#143c5a] font-semibold">Jelenleg elérhető aktuális tanfolyamaink</h3>

                            <div class="space-y-0">
                                @foreach($actualCourses as $ac)
                                    @php
                                        $courseName = $ac->course->name ?? '';

                                        $period = \Carbon\Carbon::parse($ac->start_date)->format('Y.m.d.')
                                            . ' - ' . \Carbon\Carbon::parse($ac->end_date)->format('Y.m.d.');

                                        $days = $ac->days->sortBy('day')->values();
                                        $daysText = $days->map(fn($d) => \Carbon\Carbon::parse($d->day)->format('Y.m.d.'))->implode(', ');
                                        $daysTextDetailed = $days
                                            ->map(function ($d) {
                                                $date = \Carbon\Carbon::parse($d->day)->format('Y.m.d.');
                                                $startTime = $d->start_time ? \Carbon\Carbon::parse($d->start_time)->format('H:i') : null;
                                                $endTime = $d->end_time ? \Carbon\Carbon::parse($d->end_time)->format('H:i') : null;

                                                if ($startTime && $endTime) {
                                                    return "{$date} ({$startTime} - {$endTime})";
                                                }

                                                if ($startTime) {
                                                    return "{$date} ({$startTime})";
                                                }

                                                return $date;
                                            })
                                            ->implode('<br>');

                                        $location = $ac->place_of_event;

                                        $participationText = match($ac->way_of_participation) {
                                            'group' => 'Csoportos',
                                            'individual' => 'Egyéni',
                                            default => $ac->way_of_participation,
                                        };
                                    @endphp

                                    <div
                                        class="course-item bg-slate-700 text-white border border-white shadow-sm transition hover:shadow-md"
                                        data-cat-id="{{ $ac->course->course_category_id }}"
                                        data-course-id="{{ $ac->course_id }}"
                                        :class="openCourse === {{ $ac->id }} ? 'ring-1 ring-[#39a7cc]/60' : ''"
                                    >
                                        <div class="flex items-stretch border-r-4 border-[#39a7cc] min-w-0">
                                            {{-- NYITÓ/ZÁRÓ IKON --}}
                                            <button
                                                type="button"
                                                class="w-[82px] bg-[#39a7cc] flex items-center justify-center shrink-0"
                                                @click.stop="openCourse = openCourse === {{ $ac->id }} ? null : {{ $ac->id }}"
                                                aria-label="Részletek megnyitása"
                                            >
                                                <svg
                                                    class="w-5 h-5 text-white transition-transform duration-700 ease-in-out"
                                                    :class="openCourse === {{ $ac->id }} ? 'rotate-90' : ''"
                                                    viewBox="0 0 20 20"
                                                    fill="currentColor"
                                                >
                                                    <path
                                                        fill-rule="evenodd"
                                                        d="M7.293 14.707a1 1 0 0 1 0-1.414L10.586 10 7.293 6.707a1 1 0 1 1 1.414-1.414l4 4a1 1 0 0 1 0 1.414l-4 4a1 1 0 0 1-1.414 0Z"
                                                        clip-rule="evenodd"
                                                    />
                                                </svg>
                                            </button>

                                            {{-- FŐ TARTALOM --}}
                                            <label
                                                class="flex-1 min-w-0 cursor-pointer px-5 py-4"
                                            >
                                                <div class="flex items-start gap-4">
                                                    {{-- RADIO --}}
                                                    <div class="pt-1 shrink-0">
                                                        <input
                                                            required
                                                            type="radio"
                                                            name="actual_course_id"
                                                            value="{{ $ac->id }}"
                                                            class="mt-1"
                                                            data-course-name="{{ $courseName }}"
                                                            data-course-period="{{ $period }}"
                                                            data-course-location="{{ $location }}"
                                                            data-course-days="{{ $daysText }}"
                                                        >
                                                    </div>

                                                    {{-- SZÖVEGES TARTALOM --}}
                                                    <div class="flex-1 min-w-0">
                                                        {{-- FELSŐ SOR --}}
                                                        <div class="font-black text-lg sm:text-xl leading-snug text-white">
                                                            {{ $courseName }}
                                                        </div>

                                                        {{-- ALSÓ SOR --}}
                                                        <div class="mt-2 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                                            <div class="text-sm text-white/70">
                                                                <button
                                                                    type="button"
                                                                    @click.stop="openCourse = openCourse === {{ $ac->id }} ? null : {{ $ac->id }}"
                                                                    class="inline-flex items-center gap-1 text-white/70 transition hover:text-white focus:outline-none"
                                                                >
                                                                    Kattintson a részletekért
                                                                </button>
                                                            </div>

                                                            <div class="text-sm font-semibold text-white/75 sm:text-right">
                                                                Aktuális tanfolyam időpont:
                                                                <span class="font-extrabold text-white">
                                            {{ \Carbon\Carbon::parse($ac->start_date)->format('Y.m.d.') }}
                                        </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </label>
                                        </div>

                                        {{-- LENYÍLÓ RÉSZ --}}
                                        <div
                                            x-cloak
                                            x-show="openCourse === {{ $ac->id }}"
                                            x-transition:enter="transition-all ease-in-out duration-700"
                                            x-transition:enter-start="opacity-0 max-h-0"
                                            x-transition:enter-end="opacity-100 max-h-[2000px]"
                                            x-transition:leave="transition-all ease-in-out duration-700"
                                            x-transition:leave-start="opacity-100 max-h-[2000px]"
                                            x-transition:leave-end="opacity-0 max-h-0"
                                            class="bg-slate-200 px-5 overflow-hidden"
                                        >
                                            <div class="pt-4 pb-6 border-t border-white/10">
                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm sm:text-base text-slate-800">
                                                    <div>
                                                        <div class="font-semibold text-slate-900">Képzés megnevezése</div>
                                                        <div>{{ $courseName }}</div>
                                                    </div>

                                                    <div>
                                                        <div class="font-semibold text-slate-900">Időszak</div>
                                                        <div>{{ $period }}</div>
                                                    </div>

                                                    <div>
                                                        <div class="font-semibold text-slate-900">Oktatási forma</div>
                                                        <div>{{ $participationText }}</div>
                                                    </div>

                                                    <div>
                                                        <div class="font-semibold text-slate-900">Maximális létszám</div>
                                                        <div>{{ $ac->max_participants }} fő</div>
                                                    </div>

                                                    <div>
                                                        <div class="font-semibold text-slate-900">Minimum létszám</div>
                                                        <div>{{ $ac->min_participants }} fő</div>
                                                    </div>

                                                    <div>
                                                        <div class="font-semibold text-slate-900">Helyszín</div>
                                                        <div>{{ $ac->place_of_event }}</div>
                                                    </div>

                                                    <div>
                                                        <div class="font-semibold text-slate-900">Jelentkezési határidő</div>
                                                        <div>{{ $ac->application_deadline }}</div>
                                                    </div>

                                                    <div>
                                                        <div class="font-semibold text-slate-900">Részvételi díj</div>
                                                        <div>{{ number_format($ac->price, 0, ',', ' ') }} Ft</div>
                                                    </div>

                                                    @if($daysTextDetailed)
                                                        <div class="md:col-span-2">
                                                            <div class="font-semibold text-slate-900">Oktatási napok</div>
                                                            <div>{!! $daysTextDetailed !!}</div>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="bg-white border border-gray-200 p-4 md:p-6 space-y-4">
                            <h3 class="text-3xl text-[#143c5a] font-semibold">Online <span class="text-[#11c5e8]">jelentkezési lap</span></h3>
                            <p class="font-bold">
                                Tájékoztatás! Az Innovációmenedzsment Akadémia a megújult felnőttképzésről szóló 2013. évi LXXVII. törvény (Fktv.) hatálya alá tartozó képzéseit előre meghirdetett formában biztosítja. Az ilyen tanfolyamok szakmai tartalma, a képzés helye és az időpontja ezen az oldalon előre meghirdetésre kerül. A képzési napok 4-8 óra időtartamú oktatásból állnak, az órák 45 percesek, az órákat szünet tagolja.
                            </p>
                            <p>
                                A csillaggal (*) jelölt mezők kitöltése kötelező.
                            </p>


                            <h3 class="text-xl text-[#143c5a] font-semibold">Képzés</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <input
                                        type="text"
                                        id="course_name"
                                        readonly
                                        name="course_name_readonly"
                                        placeholder="Képzés neve (automatikusan kitöltődő mező)"
                                        class="w-full border border-gray-300 p-2 bg-[#ADD8E6] focus:outline-none"
                                    >
                                </div>

                                <div>
                                    <input
                                        type="text"
                                        id="course_period"
                                        readonly
                                        name="course_period_readonly"
                                        placeholder="Időpont (automatikusan kitöltődő mező)"
                                        class="w-full border border-gray-300 p-2 bg-[#ADD8E6] focus:outline-none"
                                    >
                                </div>

                                <div>
                                    <input
                                        type="text"
                                        id="course_location"
                                        readonly
                                        name="course_location_readonly"
                                        placeholder="Oktatás helyszíne"
                                        class="w-full border border-gray-300 p-2 bg-[#ADD8E6] focus:outline-none"
                                    >
                                </div>

                                <div>
                                    <select
                                        required
                                        name="certificate_language"
                                        class="w-full border border-gray-300 p-2 focus:outline-none focus:border-blue-500"
                                    >
                                        <option value="">Tanúsítvány nyelve a magyar mellett</option>
                                        <option value="Angol" @selected(old('certificate_language')==='Angol')>Angol</option>
                                        <option value="Német" @selected(old('certificate_language')==='Német')>Német</option>
                                    </select>
                                </div>

                                <div class="md:col-span-2">
                                    <input
                                        type="text"
                                        id="course_days"
                                        readonly
                                        name="course_days_readonly"
                                        placeholder="Oktatási napok (automatikusan kitöltődő mező)"
                                        class="w-full border border-gray-300 p-2 bg-[#ADD8E6] focus:outline-none"
                                    >
                                </div>
                            </div>

                            <h3 class="text-xl text-[#143c5a] font-semibold">Díjfizető</h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <input type="text" name="payer_name" placeholder="*Neve"
                                           value="{{ old('payer_name') }}"
                                           class="w-full border border-gray-300 p-2 focus:outline-none focus:border-blue-500" required>
                                </div>

                                <div>
                                    <input type="text" name="payer_tax_number" placeholder="*Adószáma"
                                           value="{{ old('payer_tax_number') }}"
                                           class="w-full border border-gray-300 p-2 focus:outline-none focus:border-blue-500" required>
                                </div>

                                <div class="md:col-span-2">
                                    <input type="text" name="payer_address" placeholder="*Székhely"
                                           value="{{ old('payer_address') }}"
                                           class="w-full border border-gray-300 p-2 focus:outline-none focus:border-blue-500" required>
                                </div>

                                <div class="md:col-span-2">
                                    <input type="text" name="payer_mailing_address" placeholder="*Levelezési cím"
                                           value="{{ old('payer_mailing_address') }}"
                                           class="w-full border border-gray-300 p-2 focus:outline-none focus:border-blue-500" required>
                                </div>

                                <div class="md:col-span-2">
                                    <input type="text" name="payer_email" placeholder="*E-mail cím"
                                           value="{{ old('payer_email') }}"
                                           class="w-full border border-gray-300 p-2 focus:outline-none focus:border-blue-500" required>
                                </div>

                                <div class="md:col-span-2">
                                    <input type="text" name="payer_signatory" placeholder="*Aláíró/képviselő"
                                           value="{{ old('payer_signatory') }}"
                                           class="w-full border border-gray-300 p-2 focus:outline-none focus:border-blue-500" required>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white border border-gray-200 p-4 md:p-6 space-y-4">
                            <h3 class="text-xl text-[#143c5a] font-semibold">Résztvevő</h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <input type="text" name="participant_last_name" placeholder="*Vezetéknév"
                                           value="{{ old('participant_last_name') }}"
                                           class="w-full border border-gray-300 p-2 focus:outline-none focus:border-blue-500" required>
                                </div>
                                <div>
                                    <input type="text" name="participant_first_name" placeholder="*Keresztnév"
                                           value="{{ old('participant_first_name') }}"
                                           class="w-full border border-gray-300 p-2 focus:outline-none focus:border-blue-500" required>
                                </div>

                                <div>
                                    <input type="text" name="participant_birth_name" placeholder="*Születési név"
                                           value="{{ old('participant_birth_name') }}"
                                           class="w-full border border-gray-300 p-2 focus:outline-none focus:border-blue-500" required>
                                </div>
                                <div>
                                    <input type="text" name="participant_mother_name" placeholder="*Anyja neve"
                                           value="{{ old('participant_mother_name') }}"
                                           class="w-full border border-gray-300 p-2 focus:outline-none focus:border-blue-500" required>
                                </div>

                                <div>
                                    <input type="text" name="participant_birth_place" placeholder="*Születési hely"
                                           value="{{ old('participant_birth_place') }}"
                                           class="w-full border border-gray-300 p-2 focus:outline-none focus:border-blue-500" required>
                                </div>
                                <div>
                                    <input type="text" name="participant_birth_country" placeholder="*Születési ország"
                                           value="{{ old('participant_birth_country') }}"
                                           class="w-full border border-gray-300 p-2 focus:outline-none focus:border-blue-500" required>
                                </div>

                                <div>
                                    <input type="text" name="participant_birth_date" placeholder="*Születési idő"
                                           value="{{ old('participant_birth_date') }}"
                                           class="w-full border border-gray-300 p-2 focus:outline-none focus:border-blue-500" required>
                                </div>
                                <div>
                                    <input type="text" name="participant_phone" placeholder="*Telefonszám"
                                           value="{{ old('participant_phone') }}"
                                           class="w-full border border-gray-300 p-2 focus:outline-none focus:border-blue-500" required>
                                </div>

                                <div>
                                    <input type="email" name="participant_email" placeholder="*E-mail címe"
                                           value="{{ old('participant_email') }}"
                                           class="w-full border border-gray-300 p-2 focus:outline-none focus:border-blue-500" required>
                                </div>
                                <div>
                                    <input type="text" name="participant_education_id" placeholder="Oktatási azonosító"
                                           value="{{ old('participant_education_id') }}"
                                           class="w-full border border-gray-300 p-2 focus:outline-none focus:border-blue-500">
                                </div>

                                <div class="md:col-span-2">
                                    <input type="text" name="participant_address" placeholder="*Lakcím"
                                           value="{{ old('participant_address') }}"
                                           class="w-full border border-gray-300 p-2 focus:outline-none focus:border-blue-500" required>
                                </div>

                                <div class="md:col-span-2">
                                    <input type="text" name="participant_notification_address" placeholder="*Értesítési cím"
                                           value="{{ old('participant_notification_address') }}"
                                           class="w-full border border-gray-300 p-2 focus:outline-none focus:border-blue-500" required>
                                </div>

                                <div>
                                    <select name="participant_education"
                                            class="w-full border border-gray-300 p-2 focus:outline-none focus:border-blue-500">
                                        <option value="">Legmagasabb iskolai végzettsége</option>
                                        @foreach([
                                            'Végzettség nélkül',
                                            'Általános iskolai végzettség',
                                            'Középfokú végzettség és gimnáziumi érettségi (gimnázium)',
                                            'Középfokú végzettség és középfokú szakképesítés (szakgimnázium, szakképző iskola, szakiskola)',
                                            'Középfokú végzettség és középfokú szakképzettség (technikum)',
                                            'Felsőfokú végzettségi szint és felsőfokú szakképzettség (felsőoktatási intézmény)',
                                            'Felsőoktatási szakképzés (felsőoktatási intézmény)',
                                        ] as $opt)
                                            <option value="{{ $opt }}" @selected(old('participant_education')===$opt)>{{ $opt }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <select name="participant_supported"
                                            class="w-full border border-gray-300 p-2 focus:outline-none focus:border-blue-500">
                                        <option value="">Támogatási forrás terhére szeretné elszámolni?</option>
                                        <option value="igen" @selected(old('participant_supported')==='igen')>igen</option>
                                        <option value="nem" @selected(old('participant_supported')==='nem')>nem</option>
                                    </select>
                                </div>

                                <div class="md:col-span-2">
                                    <input type="text" name="participant_grant_id" placeholder="Pályázati azonosítószám"
                                           value="{{ old('participant_grant_id') }}"
                                           class="w-full border border-gray-300 p-2 focus:outline-none focus:border-blue-500">
                                </div>
                            </div>
                        </div>

                        <input name="robot_field" type="text" class="hidden" tabindex="-1" autocomplete="off">

                        <div class="bg-white border border-gray-200 p-4 md:p-6 space-y-4">
                            <label class="custom-label flex items-start">
                                <div class="bg-[#ccc] shadow w-6 h-6 p-1 flex justify-center items-center mr-2 relative cursor-pointer">
                                    <input type="checkbox" name="newsletter_opt_in" value="1" class="sr-only"
                                           onchange="this.nextElementSibling.classList.toggle('opacity-0')">
                                    <svg class="w-4 h-4 text-[#39a7cc] pointer-events-none opacity-0 transition-opacity duration-200"
                                         viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M5 12.5L10 17.5L19 6.5" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                                <span class="font-medium text-[16px] text-gray-700 pl-[10px]">
                                Hozzájárulok, hogy programjaikról, szakmai anyagokról a megadott elérhetőségeimen tájékoztassanak.
                            </span>
                            </label>

                            <label class="custom-label flex items-start">
                                <div class="bg-[#ccc] shadow w-6 h-6 p-1 flex justify-center items-center mr-2 relative cursor-pointer">
                                    <input type="checkbox" name="privacy_accepted" value="1" class="sr-only" required
                                           onchange="this.nextElementSibling.classList.toggle('opacity-0')">
                                    <svg class="w-4 h-4 text-[#39a7cc] pointer-events-none opacity-0 transition-opacity duration-200"
                                         viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M5 12.5L10 17.5L19 6.5" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                                <span class="font-medium text-[16px] text-gray-700 pl-[10px]">
                                <span class="text-[#D21243]">*</span>
                                Hozzájárulok, hogy az Innovációmenedzsment Akadémia (mint Adatkezelő) a most megadott személyes adataimat az Adatvédelmi Rendelet, továbbá a weboldal
                                <a href="https://innovacio-menedzsment.hu/innovacio-menedzsment-adatvedelem" target="_blank" class="text-blue-500 underline">Adatkezelési tájékoztatójának</a>
                                a tanfolyamokkal kapcsolatos <a href="/adatkezelesi-tajekoztato-kepzes" target="_blank" class="text-blue-500 underline">Adatkezelési tájékoztatójának</a> és a
                                <a href="/innovacio-menedzsment-aszf" target="_blank" class="text-blue-500 underline">Felhasználási feltételeiben</a>
                                leírtaknak megfelelően kezelje és információt, üzleti ajánlatot, hírlevelet küldjön a számomra.
                            </span>
                            </label>

                            <div class="mt-6">
                                <button
                                    type="submit"
                                    class="inline-flex items-stretch overflow-hidden bg-[#143c5a] text-white hover:bg-[#39a7cc] transition"
                                >
                                <span class="flex items-center px-5 py-4 font-medium leading-none">
                                    Jelentkezni kívánok!
                                </span>
                                    <span class="flex items-center border-l border-[#39a7cc] bg-[#39a7cc] px-5 leading-none">
                                    ▸
                                </span>
                                </button>
                            </div>
                        </div>
                    </form>
                </section>
            </div>

            {{-- RIGHT: tiles --}}
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

    <script>
        document.querySelectorAll('input[name="actual_course_id"]').forEach((el) => {
            el.addEventListener('change', function () {
                const set = (id, v) => { const e = document.getElementById(id); if (e) e.value = v || ''; };
                set('course_name', this.dataset.courseName);
                set('course_period', this.dataset.coursePeriod);
                set('course_location', this.dataset.courseLocation);
                set('course_days', this.dataset.courseDays);
            });
        });

        const filter = document.getElementById('courseFilter');
        const items = Array.from(document.querySelectorAll('.course-item'));
        const radios = Array.from(document.querySelectorAll('input[name="actual_course_id"]'));

        function clearAutoFields() {
            ['course_name','course_period','course_location','course_days'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.value = '';
            });
        }

        function clearRadioSelection() {
            radios.forEach(r => r.checked = false);
            clearAutoFields();
        }

        function applyFilter(value) {
            let mode = 'all';
            let id = null;

            if (value && value !== 'all') {
                const parts = value.split(':');
                mode = parts[0];
                id = String(parts[1] ?? '');
            }

            items.forEach((item) => {
                const catId = String(item.dataset.catId || '');
                const courseId = String(item.dataset.courseId || '');

                const show =
                    mode === 'all' ||
                    (mode === 'cat' && catId === id) ||
                    (mode === 'course' && courseId === id);

                item.classList.toggle('hidden', !show);
            });

            clearRadioSelection();
        }

        if (filter) {
            filter.addEventListener('change', function () {
                applyFilter(this.value);
                document.getElementById('tanfolyamvalaszto')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        }
    </script>
@endsection
