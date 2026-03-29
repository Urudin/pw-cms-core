@extends('layouts.app')

@section('content')
    @php
        $paymentMethods = config('payment_methods');
    @endphp

    <div class="pageContainer bg-white">
        <div class="bg-gray-100 p-4 md:p-8 shadow-md w-full mx-auto space-y-10">

            {{-- PAGE HEADER --}}
            <div>
                <h1 class="text-4xl sm:text-5xl font-black tracking-tight text-slate-900">
                    Pénztár
                </h1>
                <div class="mt-3 h-1 w-24 bg-[#5035e6]"></div>
            </div>

            {{-- CART SUMMARY (TOP) --}}
            <div class="bg-[#efeff2] border-b-2 border-[#d63b73]">
                <div class="px-6 py-4">
                    <h3 class="text-xl font-black text-slate-700">Kosár tartalma</h3>
                </div>

                <div id="checkoutCartItems" class="divide-y divide-gray-300"></div>

                <div class="px-6 py-4 flex items-center justify-between border-t border-gray-300 font-black text-slate-700">
                    <span>Összesen:</span>
                    <span id="checkoutCartTotal">0 Ft+áfa</span>
                </div>
            </div>

            <form id="checkout-form" action="{{ route('placeOrder') }}" method="POST" class="space-y-8">
                @csrf

                {{-- cart payload from localStorage --}}
                <input type="hidden" name="items_json" id="items_json" value="{{ old('items_json') }}">
                @error('items_json')
                <div class="text-sm text-red-600">{{ $message }}</div>
                @enderror

                {{-- PERSONAL DATA --}}
                <div class="bg-[#efeff2] border-b-2 border-[#d63b73] p-6 space-y-4">
                    <div class="text-lg font-black text-slate-800">Személyes adatok</div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <input
                                type="text"
                                name="personal_last_name"
                                value="{{ old('personal_last_name') }}"
                                placeholder="Vezetéknév*"
                                class="w-full px-3 py-2 border rounded @error('personal_last_name') border-red-400 bg-red-50 @else border-gray-300 @enderror" required>
                            @error('personal_last_name')
                            <div class="text-sm text-red-600 mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <input
                                type="text"
                                name="personal_first_name"
                                value="{{ old('personal_first_name') }}"
                                placeholder="Keresztnév*"
                                class="w-full px-3 py-2 border rounded @error('personal_first_name') border-red-400 bg-red-50 @else border-gray-300 @enderror" required>
                            @error('personal_first_name')
                            <div class="text-sm text-red-600 mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <input
                                type="tel"
                                name="personal_phone"
                                value="{{ old('personal_phone') }}"
                                placeholder="Telefonszám*"
                                class="w-full px-3 py-2 border rounded @error('personal_phone') border-red-400 bg-red-50 @else border-gray-300 @enderror" required>
                            @error('personal_phone')
                            <div class="text-sm text-red-600 mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <input
                                type="email"
                                name="personal_email"
                                value="{{ old('personal_email') }}"
                                placeholder="E-mail cím*"
                                class="w-full px-3 py-2 border rounded @error('personal_email') border-red-400 bg-red-50 @else border-gray-300 @enderror" required>
                            @error('personal_email')
                            <div class="text-sm text-red-600 mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <textarea
                            name="personal_note"
                            rows="4"
                            placeholder="Megjegyzés (opcionális)"
                            class="w-full px-3 py-2 border rounded @error('personal_note') border-red-400 bg-red-50 @else border-gray-300 @enderror">{{ old('personal_note') }}</textarea>
                        @error('personal_note')
                        <div class="text-sm text-red-600 mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- BILLING DATA --}}
                <div class="bg-[#efeff2] border-b-2 border-[#d63b73] p-6 space-y-4">
                    <div class="text-lg font-black text-slate-800">Számlázási adatok</div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <input
                                type="text"
                                name="billing_last_name"
                                value="{{ old('billing_last_name') }}"
                                placeholder="Vezetéknév*"
                                class="w-full px-3 py-2 border rounded @error('billing_last_name') border-red-400 bg-red-50 @else border-gray-300 @enderror" required>
                            @error('billing_last_name')
                            <div class="text-sm text-red-600 mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <input
                                type="text"
                                name="billing_first_name"
                                value="{{ old('billing_first_name') }}"
                                placeholder="Keresztnév*"
                                class="w-full px-3 py-2 border rounded @error('billing_first_name') border-red-400 bg-red-50 @else border-gray-300 @enderror" required>
                            @error('billing_first_name')
                            <div class="text-sm text-red-600 mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <input
                                type="text"
                                name="billing_company_name"
                                value="{{ old('billing_company_name') }}"
                                placeholder="Cégnév (opcionális)"
                                class="w-full px-3 py-2 border rounded @error('billing_company_name') border-red-400 bg-red-50 @else border-gray-300 @enderror">
                            @error('billing_company_name')
                            <div class="text-sm text-red-600 mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <input
                                type="text"
                                name="billing_vat_number"
                                id="billing_vat_number"
                                value="{{ old('billing_vat_number') }}"
                                placeholder="Adószám (opcionális) pl. 12345678-1-12"
                                inputmode="numeric"
                                maxlength="13"
                                pattern="^\d{8}-\d-\d{2}$"
                                class="w-full px-3 py-2 border rounded @error('billing_vat_number') border-red-400 bg-red-50 @else border-gray-300 @enderror"
                            >
                            @error('billing_vat_number')
                            <div class="text-sm text-red-600 mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <input
                                type="text"
                                name="billing_postal_code"
                                value="{{ old('billing_postal_code') }}"
                                placeholder="Irányítószám*"
                                class="w-full px-3 py-2 border rounded @error('billing_postal_code') border-red-400 bg-red-50 @else border-gray-300 @enderror"
                                required
                            >
                            @error('billing_postal_code')
                            <div class="text-sm text-red-600 mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <input
                                type="text"
                                name="billing_city"
                                value="{{ old('billing_city') }}"
                                placeholder="Város*"
                                class="w-full px-3 py-2 border rounded @error('billing_city') border-red-400 bg-red-50 @else border-gray-300 @enderror"
                                required
                            >
                            @error('billing_city')
                            <div class="text-sm text-red-600 mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                        <div class="md:col-span-3">
                            <input
                                type="text"
                                name="billing_street_address"
                                value="{{ old('billing_street_address') }}"
                                placeholder="Cím* (utca, házszám, emelet/ajtó)"
                                class="w-full px-3 py-2 border rounded @error('billing_street_address') border-red-400 bg-red-50 @else border-gray-300 @enderror"
                                required
                            >
                            @error('billing_street_address')
                            <div class="text-sm text-red-600 mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                </div>

                {{-- PAYMENT + DECLARATIONS + SUBMIT (ONE BOX) --}}
                <div class="bg-[#efeff2] border-b-2 border-[#d63b73]">
                    <div class="p-6 space-y-8">

                        {{-- PAYMENT METHOD --}}
                        <div class="space-y-4">
                            <div class="text-lg font-black text-slate-800">Fizetési mód*</div>

                            <div class="flex flex-col gap-4">
                                @foreach ($paymentMethods as $key => $method)
                                    <label
                                        class="payment-option cursor-pointer border rounded-lg shadow-sm p-4 flex items-start gap-4 transition-all duration-200 hover:shadow-md
                                       @error('payment_method') border-red-300 bg-red-50 @else border-gray-200 @enderror">
                                                                <input
                                                                    type="radio"
                                                                    name="payment_method"
                                                                    value="{{ $key }}"
                                                                    class="sr-only peer"
                                                                    @checked(old('payment_method') === $key) required>

                                                                {{-- CHECKBOX-LIKE RADIO (LEFT) --}}
                                                                <span class="w-5 h-5 border-2 border-gray-400 rounded-sm shrink-0 mt-1
                                                                   flex items-center justify-center transition-all duration-200
                                                                   peer-checked:bg-cyan-500
                                                                   peer-checked:border-[3px] peer-checked:border-white
                                                                   peer-checked:outline peer-checked:outline-1 peer-checked:outline-slate-300">
                                                                </span>
                                        {{-- TEXT + CARD LOGOS (RIGHT) --}}
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-start justify-between gap-4">
                                                <div class="min-w-0">
                                                    <div class="font-bold text-slate-800">{{ $method['label'] }}</div>

                                                    @if(!empty($method['info']))
                                                        <div class="mt-1 text-sm text-red-600 leading-relaxed">
                                                            {{ $method['info'] }}
                                                        </div>
                                                    @endif
                                                </div>

                                                @if($key === 'card')
                                                    <a href="https://simplepartner.hu/PaymentService/Fizetesi_tajekoztato.pdf" target="_blank">
                                                        <img
                                                            src="{{ asset('images/payments/checkout_simplepay_hu_v2_1.png') }}"
                                                            alt="SimplePay támogatott fizetési módok"
                                                            class="h-8 sm:h-7 w-auto object-contain shrink-0"
                                                        >
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    </label>
                                @endforeach
                            </div>

                            @error('payment_method')
                            <div class="text-sm text-red-600 mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- DECLARATIONS --}}
                        <div class="space-y-4">
                            <div class="text-lg font-black text-slate-800">Nyilatkozatok</div>

                            <div>
                                <label class="grid grid-cols-[20px_1fr] gap-3 cursor-pointer">
                                    <input type="checkbox" name="terms_accepted" id="terms_accepted" class="sr-only peer" @checked(old('terms_accepted')) required>

                                    <span
                                        class="w-5 h-5 shrink-0 border-2 rounded-sm flex items-center justify-center transition-all duration-200 mt-1
                                               peer-checked:bg-cyan-500
                                               peer-checked:border-[3px] peer-checked:border-white
                                               peer-checked:outline peer-checked:outline-1 peer-checked:outline-slate-300
                                               @error('terms_accepted') border-red-400 bg-red-50 @else border-gray-400 @enderror">
                                    </span>

                                    <span class="text-slate-700">
                                        Elolvastam és elfogadom, hogy az adatkezelő a most megadott személyes adataimat az <a class="text-blue-600 hover:underline" target="_blank" href="https://innovacio-menedzsment.hu/innovacio-menedzsment-adatvedelem">Adatkezelési tájékoztatójának</a> feltételei
                                        és az oldal
                                        <a class="text-blue-600 hover:underline" target="_blank" href="{{ route('aszf') }}">Szerződési feltételeiben</a>
                                        leírtak szerint kezelje.*
                                    </span>
                                </label>
                                @error('terms_accepted')
                                <div class="text-sm text-red-600 mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div>
                                <label class="flex items-start gap-3 cursor-pointer">
                                    <input type="checkbox" name="newsletter_opt_in" class="sr-only peer" @checked(old('newsletter_opt_in'))>

                                    <span
                                        class="w-5 h-5 border-2 border-gray-400 rounded-sm flex items-center justify-center transition-all duration-200
                                               peer-checked:bg-cyan-500
                                               peer-checked:border-[3px] peer-checked:border-white
                                               peer-checked:outline peer-checked:outline-1 peer-checked:outline-slate-300
                                               mt-1">
                                    </span>

                                    <span class="text-slate-700">Feliratkozom a hírlevélre</span>
                                </label>
                            </div>

                            <div>
                                <label class="flex items-start gap-3 cursor-pointer">
                                    <input type="checkbox" name="new_video_opt_in" class="sr-only peer" @checked(old('new_video_opt_in'))>

                                    <span
                                        class="w-5 h-5 border-2 border-gray-400 rounded-sm flex items-center justify-center transition-all duration-200
                                               peer-checked:bg-cyan-500
                                               peer-checked:border-[3px] peer-checked:border-white
                                               peer-checked:outline peer-checked:outline-1 peer-checked:outline-slate-300
                                               mt-1">
                                    </span>

                                    <span class="text-slate-700">Feliratkozom új videó értesítésre</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- DIVIDER BETWEEN CHECKBOXES AND BUTTON --}}
                    <div class="border-t border-gray-300"></div>

                    {{-- SUBMIT (INSIDE SAME BOX) --}}
                    <div class="p-6">
                        <button
                            type="submit"
                            class="w-[18rem] inline-flex items-stretch bg-[#f2a44a] hover:brightness-95 text-white overflow-hidden">
                            <span class="pl-[1.9rem] pr-[2.18rem] py-3 font-semibold">Megrendelem a videót</span>
                            <span class="flex items-center justify-center w-[3.1rem] bg-[#c7802f] !text-white">
                                <svg viewBox="0 0 14 14" class="h-4 w-4 translate-x-[-5px]" fill="currentColor" aria-hidden="true">
                                    <path d="M10 2l4 5-4 5V2z"/>
                                </svg>
                            </span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        function formatHufAfa(n) {
            const v = Math.round(Number(n) || 0);
            const s = v.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
            return `${s} Ft+áfa`;
        }

        function safeJsonParse(str, fallback) {
            try {
                const v = JSON.parse(str);
                return (v === null || v === undefined) ? fallback : v;
            } catch (e) {
                return fallback;
            }
        }

        // A te cart.js-ed pontosan ezt használja:
        function getLocalStorageCart() {
            const cart = safeJsonParse(localStorage.getItem('cart') || '[]', []);
            return Array.isArray(cart) ? cart : [];
        }

        // Ha validáció után visszadob (old('items_json') kitöltve), abból is tudunk dolgozni.
        function getOldItemsJson() {
            const el = document.getElementById('items_json');
            if (!el) return [];
            const arr = safeJsonParse(el.value || '[]', []);
            return Array.isArray(arr) ? arr : [];
        }

        function getCartForUi() {
            const cart = getLocalStorageCart();
            console.log(cart);
            if (cart.length) return cart;

            // fallback: items_json -> csinálunk belőle minimál UI-t (név/ár nélkül nem lesz fancy)
            const items = getOldItemsJson();
            return items.map(i => ({
                id: i.id,
                name: i.name ?? '',
                price: i.price ?? 0,
                image: i.image ?? '',
                quantity: i.quantity ?? 1,
            }));
        }

        // BACKEND-nek: items_json = [{id: 123, quantity: 1}]
        // Fontos: id nálad string, ezt normalizáljuk.
        function buildItemsPayloadFromCart(cart) {
            return (cart || [])
                .map(i => {
                    const idNum = Number(i.id); // datasetből string -> number
                    return {
                        id: Number.isFinite(idNum) ? idNum : i.id, // ha mégse szám, küldjük stringként
                        quantity: Number(i.quantity ?? 1) || 1,
                    };
                })
                .filter(i => (typeof i.id === 'number' ? i.id > 0 : String(i.id).length > 0));
        }

        function syncItemsJsonHidden() {
            const itemsJsonEl = document.getElementById('items_json');
            if (!itemsJsonEl) return;

            const cart = getLocalStorageCart();
            const payload = buildItemsPayloadFromCart(cart);

            itemsJsonEl.value = JSON.stringify(payload);
        }

        function showCartToast() {
            const el = document.getElementById('cart-toast');
            if (!el) return;

            el.classList.remove('hidden');
            el.classList.add('flex');

            clearTimeout(window.__cartToastTimer);
            window.__cartToastTimer = setTimeout(() => {
                el.classList.add('hidden');
                el.classList.remove('flex');
            }, 3000);
        }

        function renderCheckoutCartSummary() {
            const itemsEl = document.getElementById('checkoutCartItems');
            const totalEl = document.getElementById('checkoutCartTotal');
            if (!itemsEl || !totalEl) return;

            const cart = getCartForUi();
            itemsEl.innerHTML = '';

            if (!cart.length) {
                itemsEl.innerHTML = `<div class="px-6 py-4 text-slate-600">A kosár üres.</div>`;
                totalEl.textContent = formatHufAfa(0);
                return;
            }

            let total = 0;

            cart.forEach(item => {
                const imgSrc = (item.image || '').startsWith('http')
                    ? item.image
                    : (item.image ? `/storage/${item.image}` : '');

                const current = Number(item.price) || 0;
                total += current;

                const row = document.createElement('div');
                row.className = 'px-6 py-4 flex items-center justify-between';

                row.innerHTML = `
                    <div class="flex items-start gap-4 min-w-0">
                        ${imgSrc ? `
                            <img src="${imgSrc}" alt="" class="w-[170px] h-[107px] min-w-[170px] min-h-[107px] object-cover border border-gray-300">
                        ` : `
                            <div class="w-[170px] h-[107px] min-w-[70px] bg-gray-200 border border-gray-300"></div>
                        `}
                        <div class="min-w-0">
                            <div class="text-[#1f4fd6] font-black leading-snug truncate">
                                <a target="_blank" href="/online-tartalmak?q=${item.name || ''}">${item.name || ''}</a>
                            </div>
                            <div class="text-sm text-slate-600 leading-snug">
                                Digitális tartalom megtekintés jogosultság
                            </div>
                        </div>
                    </div>

                    <div class="shrink-0 font-black text-slate-700">
                        ${formatHufAfa(current)}
                    </div>
                `;

                itemsEl.appendChild(row);
            });

            totalEl.textContent = formatHufAfa(total);
        }

        document.addEventListener('DOMContentLoaded', () => {

            //Vat validáció
            const vatInput = document.getElementById('billing_vat_number');
            const form = document.getElementById('checkout-form');

            if (!vatInput) return;

            function formatVatNumber(value) {
                const digits = value.replace(/\D/g, '').slice(0, 11); // 8 + 1 + 2

                let result = '';

                if (digits.length > 0) {
                    result += digits.slice(0, 8);
                }

                if (digits.length > 8) {
                    result += '-' + digits.slice(8, 9);
                }

                if (digits.length > 9) {
                    result += '-' + digits.slice(9, 11);
                }

                return result;
            }

            function isValidVatNumber(value) {
                return /^\d{8}-\d-\d{2}$/.test(value);
            }

            vatInput.addEventListener('input', (e) => {
                const formatted = formatVatNumber(e.target.value);
                e.target.value = formatted;

                if (formatted === '' || isValidVatNumber(formatted)) {
                    e.target.setCustomValidity('');
                } else {
                    e.target.setCustomValidity('Az adószám formátuma: 12345678-1-12');
                }
            });

            vatInput.addEventListener('blur', (e) => {
                const value = e.target.value.trim();

                if (value !== '' && !isValidVatNumber(value)) {
                    e.target.setCustomValidity('Az adószám formátuma: 12345678-1-12');
                    e.target.reportValidity();
                } else {
                    e.target.setCustomValidity('');
                }
            });

            if (form) {
                form.addEventListener('submit', (e) => {
                    const value = vatInput.value.trim();

                    if (value !== '' && !isValidVatNumber(value)) {
                        vatInput.setCustomValidity('Az adószám formátuma: 12345678-1-12');
                        vatInput.reportValidity();
                        e.preventDefault();
                        return;
                    }

                    vatInput.setCustomValidity('');
                });
            }
            // 1) már betöltéskor töltsük ki a hidden mezőt a TE localStorage cartodból
            syncItemsJsonHidden();

            // 2) rajzoljuk a kosár összefoglalót
            renderCheckoutCartSummary();

            // 3) submit előtt frissítsünk, és ne engedjünk üres kosarat
            if (form) {
                form.addEventListener('submit', (e) => {
                    syncItemsJsonHidden();

                    const itemsJsonEl = document.getElementById('items_json');
                    const payload = safeJsonParse(itemsJsonEl?.value || '[]', []);

                    if (!Array.isArray(payload) || payload.length < 1) {
                        e.preventDefault();
                        alert('A kosár üres.');
                    }
                });
            }
            // 4) több tab support: ha másik ablak módosítja a cart-ot, frissítsük ezt a checkoutot is
            window.addEventListener('storage', (event) => {
                if (event.key !== 'cart') return;

                // frissítjük a hidden payloadot és az UI-t is
                syncItemsJsonHidden();
                renderCheckoutCartSummary();
                confirm('Figyelem! A kosár tartalma megváltozott!') // 👈 értesítés

                // opcionális: ha üres lett, jelezzük azonnal
                const payload = safeJsonParse(document.getElementById('items_json')?.value || '[]', []);
                if (!Array.isArray(payload) || payload.length < 1) {
                    // itt lehetne gomb tiltás is, lásd lentebb
                    console.log('Cart is empty on checkout tab after external update.');
                }
            });
        });
    </script>
@endsection
