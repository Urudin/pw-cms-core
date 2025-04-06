<script>
    grecaptcha.ready(function() {
        grecaptcha.execute('{{ config('services.nocaptcha.sitekey') }}', {action: 'submit'}).then(function(token) {
            document.getElementById('g-recaptcha-response').value = token;
        });
    });
</script>
<div class="bg-gray-100 p-8 shadow-md w-full mx-auto">
    <h2 class="text-2xl text-[#143c5a] mb-6">TOVÁBBI KÉRDÉSE VAN? <span class="text-[#B58E03]">LÉPJEN KAPCSOLATBA VELÜNK!</span></h2>

    <form class="space-y-6" action="/contact" method="POST">
        <!-- Input Fields -->
        @csrf
        <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <input type="text" id="name" name="name" placeholder="*Név" class="w-full border border-gray-300 p-2 focus:outline-none focus:border-blue-500" required>
            </div>
            <div>
                <input type="text" id="company" name="company" placeholder="Cégnév" class="w-full border border-gray-300 p-2 focus:outline-none focus:border-blue-500">
            </div>
            <div>
                <input type="tel" id="phone" name="phone" placeholder="*Telefon" class="w-full border border-gray-300 p-2 focus:outline-none focus:border-blue-500" required>
            </div>
            <div>
                <input type="email" id="email" name="email" placeholder="*E-mail cím" class="w-full border border-gray-300 p-2 focus:outline-none focus:border-blue-500" required>
            </div>
        </div>

        <!-- Message Field -->
        <div>
            <textarea id="message" name="message" rows="5" placeholder="*Üzenet" class="w-full border border-gray-300 p-2 focus:outline-none focus:border-blue-500" required></textarea>
        </div>

        <!-- Checkbox -->
        <label class="custom-label flex">
            <div class="bg-gray-[#ccc] shadow w-6 h-6 p-1 flex justify-center items-center mr-2">
                <input type="checkbox" id="consent" name="consent" class="hidden" onchange="this.nextElementSibling.classList.toggle('opacity-0')">
                <svg class="w-4 h-4 text-[#39a7cc] pointer-events-none opacity-0 transition-opacity duration-200" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M5 12.5L10 17.5L19 6.5" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            <span for="consent" class="font-medium text-[18px] text-gray-700 pl-[10px]">
                A checkbox bepipálásával hozzájárulok, hogy az adatkezelő a most megadott személyes adataimat az Adatvédelmi Rendelet, továbbá az oldal <a href="#" class="text-blue-500 underline">Adatkezelési tájékoztatójának</a> feltételei és az oldal <a href="#" class="text-blue-500 underline">Szerződési feltételeiben</a> leírtak szerint kezelje, és információt, üzleti ajánlatot küldjön a számomra. Tudomásul veszem, hogy a hozzájárulásomat bármikor visszavonhatom az adatkezelőnek küldött ez irányú kéréssel.
            </span>
        </label>
        <!-- Submit Button -->
        <div class="mt-6">
            <button class="bg-[#143c5a] hover:bg-[#39a7cc] text-white pb-[16px] py-[15px]">
                <span class="pr-[30px] pl-[20px] font-medium">Üzenet küldése</span>
                <span class="pl-[20px] pr-[20px] pt-[16px] pb-[18px] bg-[#39a7cc]">▸</span>
            </button>
        </div>
        @if ($errors->has('g-recaptcha-response'))
            <span class="help-block">
                <strong>{{ $errors->first('g-recaptcha-response') }}</strong>
            </span>
        @endif
    </form>
</div>
