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
        🛒
        <span id="cart-badge"
              class="hidden absolute -top-1 -right-1
                     bg-red-600 text-white text-xs font-bold
                     rounded-full min-w-[1.25rem] h-5 px-1
                     flex items-center justify-center">
        </span>
    </button>

    <div class="h-full px-6 py-4 overflow-y-auto">
        <div class="flex justify-between items-center">
            <h3 class="text-xl font-semibold text-gray-700">
                {{ __('messages.your_cart') }}
            </h3>

            <button type="button" class="toggle-button text-gray-600" aria-label="Bezárás">
                ✕
            </button>
        </div>

        <hr class="my-3">

        <div id="cartContainer"></div>

        <a id="checkout"
           class="block text-center mt-4 px-3 py-2
                  bg-blue-600 text-white text-sm uppercase font-medium
                  rounded hover:bg-blue-500">
            {{ __('messages.checkout') }}
        </a>
    </div>
</div>
