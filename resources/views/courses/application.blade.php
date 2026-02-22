@extends('layouts.app')

@section('content')
    <div class="pageContainer bg-gray-100">
        <div class="lg:flex lg:gap-8">

            {{-- LEFT: page content --}}
            <div class="lg:flex-1 min-w-0">
                <section class="px-4 sm:px-6 lg:px-8 py-10 space-y-10">
                    <header class="space-y-4">
                        <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-slate-900">
                            Online jelentkezési lap
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

                        <div class="bg-white border border-gray-200 p-4 md:p-6 space-y-3">
                            <h3 class="text-xl text-[#143c5a] font-semibold">Jelenleg elérhető aktuális tanfolyamaink</h3>

                            <div class="space-y-3">
                                @foreach($actualCourses as $ac)
                                    @php
                                        $courseName = $ac->course->name ?? '';
                                        $period = \Carbon\Carbon::parse($ac->start_date)->format('Y.m.d.')
                                            . ' - ' . \Carbon\Carbon::parse($ac->end_date)->format('Y.m.d.');

                                        $days = $ac->days->sortBy('day')->values();
                                        $daysText = $days->map(fn($d) => \Carbon\Carbon::parse($d->day)->format('Y.m.d.'))->implode(', ');

                                        $location = $ac->type === 'group' ? 'Csoportos képzés' : 'Egyéni képzés';
                                    @endphp

                                    <label
                                        class="course-item flex items-start gap-3 bg-gray-50 border border-gray-200 p-3 cursor-pointer hover:bg-gray-100"
                                        data-cat-id="{{ $ac->course->course_category_id }}"
                                        data-course-id="{{ $ac->course_id }}"
                                    >
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

                                        <div class="flex-1">
                                            <div class="font-semibold text-gray-800">{{ $courseName }}</div>
                                            <div class="text-sm text-gray-600">{{ $period }}</div>
                                            @if($daysText)
                                                <div class="text-sm text-gray-600">Oktatási napok: {{ $daysText }}</div>
                                            @endif
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="bg-white border border-gray-200 p-4 md:p-6 space-y-4">
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
                                        placeholder="Helyszín / megrendezés módja (automatikusan kitöltődő mező)"
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
                        </div>

                        <div class="bg-white border border-gray-200 p-4 md:p-6 space-y-4">
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
                                <span class="font-medium text-[18px] text-gray-700 pl-[10px]">
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
                                <span class="font-medium text-[18px] text-gray-700 pl-[10px]">
                                <span class="text-[#D21243]">*</span>
                                A checkbox bepipálásával hozzájárulok az adatkezeléshez az
                                <a href="#" class="text-blue-500 underline">Adatkezelési tájékoztató</a>
                                és
                                <a href="#" class="text-blue-500 underline">Szerződési feltételek</a>
                                szerint.
                            </span>
                            </label>

                            <div class="mt-6">
                                <button type="submit" class="bg-[#143c5a] hover:bg-[#39a7cc] text-white pb-[16px] py-[15px]">
                                    <span class="pr-[30px] pl-[20px] font-medium">Jelentkezni kívánok!</span>
                                    <span class="pl-[20px] pr-[20px] pt-[16px] pb-[19px] border border-[#39a7cc] bg-[#39a7cc]">▸</span>
                                </button>
                            </div>
                        </div>
                    </form>
                </section>
            </div>

            {{-- RIGHT: tiles --}}
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
