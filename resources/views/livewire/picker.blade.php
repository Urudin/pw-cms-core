<div>
    @if ($showModal)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl p-6">
                <div class="flex justify-between items-center border-b pb-2 mb-4">
                    <h2 class="text-xl font-bold text-gray-800">Médiatár – Képek</h2>
                    <button wire:click="$set('showModal', false)" class="text-gray-500 hover:text-gray-700">
                        ✖
                    </button>
                </div>

                <!-- Képek rácsos elrendezésben -->
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4 overflow-y-auto max-h-[500px]">
                    @foreach ($images as $image)
                        <div class="relative group cursor-pointer">
                            <img src="{{ $image['original_url'] }}"
                                 class="w-full h-[75px] object-cover rounded-lg shadow-md transition-transform transform hover:scale-105"
                                 wire:click="selectImage('{{ $image['original_url'] }}')"
                            />
                            <div class="absolute inset-0 bg-black bg-opacity-50 hidden group-hover:flex items-center justify-center text-white font-semibold rounded-lg">
                                Kiválasztás
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Üres állapot -->
                @if (empty($images))
                    <p class="text-gray-500 text-center mt-4">Nincsenek elérhető képek.</p>
                @endif

                <div class="flex justify-end mt-4">
                    <button wire:click="$set('showModal', false)" class="px-4 py-2 bg-red-500 text-white rounded-lg shadow hover:bg-red-600">
                        Bezárás
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
