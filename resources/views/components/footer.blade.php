{{--TODO MOBILE VIEW--}}
<footer class="w-full bg-[#e6e7eb] text-sm text-gray-800">
    <div class="w-full bg-[#004070] py-3 text-center text-white text-base border-t-[#B58E03]">
        <p>
            Glósz és Társa Kft. • 1051 Budapest, Arany János u. 15. III. lph III./5. • Telefon: (+36 1) 302 4443 •
            E-mail: <a href="mailto:glosz@glosz.hu?subject=info" class="underline hover:text-gray-100">glosz@glosz.hu</a> •
            <a href="http://www.glosz.hu" class="underline hover:text-gray-100" target="_blank">www.glosz.hu</a>
        </p>
    </div>

    <div class="w-full max-w-screen-xl mx-auto px-4 flex flex-col md:flex-row py-8">
        <!-- Left image full height -->
        <div class="w-full md:w-[240px] flex-shrink-0 pr-4 flex justify-center md:justify-end">
            <img src="{{asset('images/footer/glosz-innovaciomenedzsment.jpeg')}}" alt="Innováció, kutatás-fejlesztés - Glósz és Társa System" class="h-full w-full object-cover rounded">
        </div>

        <!-- Right content block with left border -->
        <div class="flex-1 border-l border-[#004070] md:pl-6">
            <div class="flex flex-col md:flex-row items-start justify-between mb-4">
                <!-- Logo and social icons -->
                <div class="flex items-center space-x-4">
                    <img src="{{asset('images/footer/logo-kek.png')}}" alt="Innovációmenedzsment logó" class="max-h-12">
                </div>
                <div class="flex space-x-2 mt-4 md:mt-0">
                    <a href="https://www.facebook.com/GloszesTarsa?ref=hl" target="_blank">
                        <img src="{{asset('images/footer/fb-footer.png')}}" alt="Facebook" class="h-6">
                    </a>
                    <a href="https://twitter.com/i/flow/login?redirect_after_login=%2FGlosz_es_Tarsa" target="_blank">
                        <img src="{{asset('images/footer/twitter-lablec.png')}}" alt="Twitter" class="h-6">
                    </a>
                    <a href="https://hu.linkedin.com/company/gl%C3%B3sz-%C3%A9s-%C3%A1rsa-kft." target="_blank">
                        <img src="{{asset('images/footer/in-footer.png')}}" alt="LinkedIn" class="h-6">
                    </a>
                </div>
            </div>

            <div class="border-t border-[#004070] pb-6"></div>

            <div class="grid grid-cols-1 md:grid-cols-[2fr_1fr] gap-8 pt-6">
                <!-- Szolgáltatásaink -->
                <div class="flex flex-col">
                    <h3 class="text-sm font-semibold text-[#004070] uppercase mb-2">Innovációmenedzsment folyamata - Szolgáltatásaink</h3>
                    <div class="grid grid-cols-2 gap-y-1 text-sm">
                        <span>Stratégia tervezés</span>
                        <span>Pályázati vállalások biztosítása</span>
                        <span>K+F projekttervezés</span>
                        <span>Innovációs projektmenedzsment</span>
                        <span>K+F minősítés</span>
                        <span>K+F azonosítás, fenntartás</span>
                        <span>Szellemi alkotás védelme</span>
                        <span>K+F adóalap kedvezmények</span>
                        <span>Innováció finanszírozás</span>
                        <span>Szellemi alkotás dokumentálás</span>
                        <span>Szellemi vagyon értéke</span>
                        <span>Szellemivagyon gazdálkodás</span>
                        <span>K+F adókedvezmény</span>
                    </div>
                </div>

                <!-- Rólunk oszlop -->
                <div class="flex flex-col">
                    <h3 class="text-sm font-semibold text-[#004070] uppercase mb-2">Rólunk</h3>
                    <ul class="space-y-1 text-sm">
                        <li><a href="https://innovacio-menedzsment.hu/kapcsolat" target="_blank" class="hover:underline">Rólunk - kapcsolat</a></li>
                        <li><a href="https://innovacio-menedzsment.hu/innovaciomenedzsment-szolgaltatasok" target="_blank" class="hover:underline">Szolgáltatásaink</a></li>
                        <li><a href="https://innovacio-menedzsment.hu/innovaciomenedzsment-szolgaltatas-csomagok" target="_blank" class="hover:underline">Szolgáltatás csomagjaink</a></li>
                        <li><a href="https://innovacio-menedzsment.hu/szellemi-alkotas-szellemi-termek-szellemi-tulajdon" target="_blank" class="hover:underline">Szellemi alkotás, szellemi termék</a></li>
                        <li><a href="https://innovacio-menedzsment.hu/innovaciomenedzsment" target="_blank" class="hover:underline">Innovációmenedzsment</a></li>
                        <li><a href="https://imakademia.hu/" target="_blank" class="hover:underline">Innovációmenedzsment oktatás</a></li>
                        <li><a href="https://innovacio-menedzsment.hu/innovacio-menedzsment-felhasznalasi-feltetelek" target="_blank" class="hover:underline">Felhasználási feltételeink</a></li>
                        <li><a href="https://innovacio-menedzsment.hu/innovacio-menedzsment-adatvedelem" target="_blank" class="hover:underline">Adatvédelmi tájékoztató</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="w-full bg-[#004070] py-3 text-center text-white text-base">
        <p>© 2019 - 2025 • Glósz és Társa Kft. • Innováció, kutatás-fejlesztés, szellemi termék, innovációmenedzsment • Minden jog fenntartva!</p>
    </div>
</footer>
