<div
    id="shopping-cart"
    class="fixed top-0 right-0 z-[9999]
           w-[20rem] max-w-[85vw]
           h-screen
           bg-white
           transition-transform duration-300 ease-out
           translate-x-[calc(100%-0.25rem)]"
>
    <!-- FÜL – a panel része, ezért együtt mozog -->
    <button
        type="button"
        class="toggle-button absolute left-[-3.25rem]
               top-[calc(var(--header-h)+1.5rem)]
               w-[3.25rem] h-[3.25rem]
               rounded-l-lg bg-white border border-gray-300 border-r-0
               shadow flex items-center justify-center hover:bg-gray-50"
        aria-label="Kosár"
    >
        <svg class="w-6 h-6 text-black" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <path d="M3 3h2.5l.6 2.4h13.8c.8 0 1.4.8 1.2 1.6l-1.6 6.4c-.1.5-.6.8-1.1.8H7.3l-.5 2h10.8v2H6c-.7 0-1.2-.6-1.1-1.3l.8-3.2L4.2 5H3V3zm5 18a1.5 1.5 0 100-3 1.5 1.5 0 000 3zm10 0a1.5 1.5 0 100-3 1.5 1.5 0 000 3z"/>
        </svg>
        <span id="cart-badge"
              class="hidden absolute -top-1 -right-1
                     bg-cyan-500 text-white text-xs font-bold
                     rounded-full min-w-[1.25rem] h-5 px-1
                     flex items-center justify-center">
        </span>
    </button>

    <div class="h-full px-6 py-4 flex flex-col">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <svg class="w-6 h-6 text-[#39a7cc]" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M3 3h2.5l.6 2.4h13.8c.8 0 1.4.8 1.2 1.6l-1.6 6.4c-.1.5-.6.8-1.1.8H7.3l-.5 2h10.8v2H6c-.7 0-1.2-.6-1.1-1.3l.8-3.2L4.2 5H3V3zm5 18a1.5 1.5 0 100-3 1.5 1.5 0 000 3zm10 0a1.5 1.5 0 100-3 1.5 1.5 0 000 3z"/>
                </svg>
                <h3 class="text-2xl font-black text-slate-700">Kosaram</h3>
            </div>

            <button class="toggle-button text-slate-700" type="button">
                <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <hr class="my-3">

        <!-- görgethető lista -->
        <div id="cartContainer" class="flex-1 overflow-y-auto"></div>

        <!-- alsó összeg + gomb -->
        <div class="mt-6">
            <div
                id="cartTotalBox"
                class="bg-gray-300 border-b-2 border-[#d63b73] px-4 py-3 flex items-center justify-between"
            >
                <span class="font-semibold text-slate-700">Kosár összege:</span>
                <span id="cartTotal" class="font-black text-slate-800">0 Ft+áfa</span>
            </div>

            <a href="{{route('checkout')}}"
                class="mt-6 w-full inline-flex items-stretch bg-[#f2a44a] hover:brightness-95 text-white overflow-hidden">
                <span class="pl-[2.9rem] pr-6 py-3 font-semibold">Tovább a pénztárhoz</span>
                <span class="flex items-center justify-center w-[3.1rem] bg-[#c7802f] !text-white group-hover:!text-white">
                                                <svg
                                                    viewBox="0 0 14 14"
                                                    class="h-4 w-4 translate-x-[-5px]"
                                                    fill="currentColor"
                                                    aria-hidden="true"
                                                >
                                                    <path d="M10 2l4 5-4 5V2z"/>
                                                </svg>
                                            </span>
            </a>
        </div>
    </div>
</div>
