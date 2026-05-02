<div class="w-[320px] border-b-2 border-pink-500 bg-gray-200 shadow-sm mb-5">
    <div class="relative bg-slate-600 text-white font-semibold px-6 py-5 text-xl">
        Népszerű Videó
        <span class="absolute right-0 top-0 h-full w-2 bg-[#39a7cc]"></span>
    </div>

    <div class="py-5 px-4">
        <article class="bg-white border border-gray-200 overflow-hidden">
            <div class="relative h-[170px] overflow-hidden">
                <img
                    src="{{ $video->thumbnail_url }}"
                    class="absolute inset-0 w-full h-full object-cover"
                    alt="{{ $video->title }}"
                    title="{{ $video->title }}"
                >

                <div class="absolute inset-0 bg-slate-900/60"></div>

                <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                    <svg class="w-14 h-14 text-white drop-shadow-lg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M8.2 6.1c0-.8.9-1.3 1.6-.9l8.6 5.1c.7.4.7 1.4 0 1.8l-8.6 5.1c-.7.4-1.6-.1-1.6-.9V6.1z"/>
                    </svg>
                </div>
            </div>

            <div class="bg-gray-200 pt-4 pb-4 flex flex-col justify-between">
                <div class="space-y-2 px-4">
                    <h2 class="text-[20px] font-black leading-tight text-[#1f4fd6] line-clamp-2">
                        {{ $video->title }}
                    </h2>

                    <p class="text-sm leading-relaxed opacity-95 line-clamp-3">
                        {{ $video->description }}
                    </p>
                </div>

                <button
                    type="button"
                    data-id="video_{{ $video->id }}"
                    data-name="{{ $video->title }}"
                    data-price="{{ $video->price_huf }}"
                    data-image="{{ $video->thumbnail_url }}"
                    title="{{ $video->title }}"
                    class="group flex items-stretch font-semibold add-to-cart
                           h-12 min-w-[290px] mt-4
                           bg-[#143c5a] hover:bg-[#39a7cc] transition-colors
                           !text-white hover:!text-white no-underline hover:no-underline"
                >
                    <span class="flex items-center pl-6 tracking-wide text-lg flex-1 !text-white group-hover:!text-white">
                        Kosárba teszem
                    </span>

                    <span class="flex items-center justify-center w-12 bg-[#39a7cc] !text-white group-hover:!text-white">
                        <svg
                            viewBox="0 0 14 14"
                            class="h-4 w-4 translate-x-[-5px]"
                            fill="currentColor"
                            aria-hidden="true"
                        >
                            <path d="M10 2l4 5-4 5V2z"/>
                        </svg>
                    </span>
                </button>
            </div>
        </article>
    </div>
</div>
