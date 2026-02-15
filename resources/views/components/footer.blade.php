<footer class="w-full bg-[#e6e7eb] text-sm md:text-md text-gray-800">
    @if(\App\Models\Block::query()->where('name', 'PreFooter')->exists())
        {!! \App\Models\Block::query()->firstWhere('name', 'PreFooter')->content !!}
    @else
        <div class="w-full bg-[#004070] py-3 text-center text-white text-base border-t-[#B58E03]">
            <p>
                Glósz és Társa Kft. • 1051 Budapest, Arany János u. 15. III. lph III./5. • Telefon: (+36 1) 302 4443 •
                E-mail: <a href="mailto:glosz@glosz.hu?subject=info" class="underline hover:text-gray-100">glosz@glosz.hu</a>
                •
                <a href="http://www.glosz.hu" class="underline hover:text-gray-100" target="_blank">www.glosz.hu</a>
            </p>
        </div>
    @endif

    <div class="w-full max-w-screen-xl mx-auto flex flex-col md:flex-row py-8">
        <!-- Left image full height -->
        <div class="w-full md:w-[240px] flex-shrink-0 pr-4 flex justify-center md:justify-end">
            <img src="{{asset('images/footer/glosz-innovaciomenedzsment.jpeg')}}"
                 alt="Glósz és Tsa System - vállalati szolgáltatások"
                 title="Glósz és Tsa System - vállalati szolgáltatások"
                 class="h-full w-[50%] md:w-[100%] object-cover rounded">
        </div>

        <!-- Right content block with left border -->
        <div class="flex-1 md:border-l border-[#004070] md:pl-6">
            <div class="flex flex-col md:flex-row items-center md:items-start justify-between mb-4">
                <!-- Logo -->
                <div class="flex mt-6 md:mt-[0px] items-center space-x-4 mx-auto md:mx-0">
                    <img src="{{asset('images/footer/IMA_logo_kek.png')}}" alt="Pénzügyi és innovációs szolgáltatások"
                         title="Pénzügyi és innovációs szolgáltatások" class="max-h-32">
                </div>

                <!-- Social Icons -->
                <div class="flex space-x-2 mt-4 md:mt-0 mx-auto md:mx-0 md:ml-auto">
                    <a href="https://www.facebook.com/GloszesTarsa?ref=hl" title="Glósz és Tsa Kft. Facebook oldal"
                       target="_blank">
                        <img src="{{asset('images/footer/fb-footer.png')}}" title="Glósz és Tsa Kft. Facebook oldal"
                             alt="Glósz és Tsa Kft. Facebook oldal" class="h-6">
                    </a>
                    <a href="https://twitter.com/i/flow/login?redirect_after_login=%2FGlosz_es_Tarsa"
                       title="Glósz és Tsa Kft. X oldal" target="_blank">
                        <img src="{{asset('images/footer/twitter-lablec.png')}}" title="Glósz és Tsa Kft. X oldal"
                             alt="Glósz és Tsa Kft. X oldal" class="h-6">
                    </a>
                    <a href="https://hu.linkedin.com/company/gl%C3%B3sz-%C3%A9s-%C3%A1rsa-kft."
                       title="Glósz és Tsa Kft. LinkedIn oldal" target="_blank">
                        <img src="{{asset('images/footer/in-footer.png')}}" title="Glósz és Tsa Kft. LinkedIn oldal"
                             alt="Glósz és Tsa Kft. LinkedIn oldal" class="h-6">
                    </a>
                </div>
            </div>
            <div class="border-t border-[#004070] pb-6"></div>

            @if(\App\Models\Block::query()->where('name', 'Footer')->exists())
                {!! \App\Models\Block::query()->firstWhere('name', 'Footer')->content !!}
            @else
                <div class="grid grid-cols-1 md:grid-cols-[2fr_1fr] gap-8">
                    <!-- Szolgáltatásaink -->
                    <div class="flex flex-col">
                        <h3 class="text-md font-semibold text-[#004070] uppercase mb-2 text-center md:text-left">
                            Innovációmenedzsment folyamata - Szolgáltatásaink</h3>
                        <div
                            class="flex flex-col md:flex-row items-center md:items-start text-center md:text-left md:space-x-8">
                            <!-- Igazítás megoldása -->
                            <div class="flex flex-col space-y-1">
                                <div>Stratégia tervezés</div>
                                <div>Pályázati vállalások biztosítása</div>
                                <div>K+F projekttervezés</div>
                                <div>Innovációs projektmenedzsment</div>
                                <div>K+F minősítés</div>
                                <div>K+F azonosítás, fenntartás</div>
                            </div>
                            <div class="flex flex-col space-y-1">
                                <div>Szellemi alkotás védelme</div>
                                <div>K+F adóalap kedvezmények</div>
                                <div>Innováció finanszírozás</div>
                                <div>Szellemi alkotás dokumentálás</div>
                                <div>Szellemi vagyon értéke</div>
                                <div>Szellemivagyon gazdálkodás</div>
                                <div>K+F adókedvezmény</div>
                            </div>
                        </div>
                    </div>

                    <!-- Rólunk oszlop -->
                    <div class="flex flex-col items-center md:items-start text-center md:text-left">
                        <h3 class="text-md font-semibold text-[#004070] uppercase mb-2">Rólunk</h3>
                        <ul class="space-y-1 text-md">
                            <li><a href="https://innovacio-menedzsment.hu/kapcsolat" target="_blank"
                                   class="hover:underline">Rólunk - kapcsolat</a></li>
                            <li><a href="https://innovacio-menedzsment.hu/innovaciomenedzsment-szolgaltatasok"
                                   target="_blank" class="hover:underline">Szolgáltatásaink</a></li>
                            <li><a href="https://innovacio-menedzsment.hu/innovaciomenedzsment-szolgaltatas-csomagok"
                                   target="_blank" class="hover:underline">Szolgáltatás csomagjaink</a></li>
                            <li>
                                <a href="https://innovacio-menedzsment.hu/szellemi-alkotas-szellemi-termek-szellemi-tulajdon"
                                   target="_blank" class="hover:underline">Szellemi alkotás, szellemi termék</a></li>
                            <li><a href="https://innovacio-menedzsment.hu/innovaciomenedzsment" target="_blank"
                                   class="hover:underline">Innovációmenedzsment</a></li>
                            <li><a href="https://imakademia.hu/" target="_blank" class="hover:underline">Innovációmenedzsment
                                    oktatás</a></li>
                            <li>
                                <a href="{{route('aszf')}}"
                                   target="_blank" class="hover:underline">Felhasználási feltételeink</a></li>
                            <li><a href="{{route('data-handling-courses')}}"
                                   target="_blank" class="hover:underline">Adatvédelmi tájékoztató</a></li>
                        </ul>
                    </div>
                </div>

            @endif
        </div>
    </div>

    @if(\App\Models\Block::query()->firstWhere('name', 'PostFooter')->exists())
        {!! \App\Models\Block::query()->firstWhere('name', 'PostFooter')->content !!}
    @else
        <div class="w-full bg-[#004070] py-3 text-center text-white text-base">
            <p>© 2019 - 2025 • Glósz és Társa Kft. • Innováció, kutatás-fejlesztés, szellemi termék,
                innovációmenedzsment • Minden jog fenntartva!</p>
        </div>
    @endif
    <script src="https://cdn.jsdelivr.net/npm/glightbox/dist/js/glightbox.min.js"></script>
    <script>
        GLightbox({
            selector: '.js-lightbox',
            loop: true,
            touchNavigation: true,
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <script>
        new Swiper('.courseSwiper', {
            slidesPerView: 1,
            spaceBetween: 24,
            loop: true,
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
            },
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },
        });
    </script>
</footer>
