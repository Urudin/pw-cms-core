<div>
    @if ($showModal)
        @vite(['resources/css/app.css'])
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl p-6">
                <div class="flex justify-between items-center border-b pb-2 mb-4">
                    <h2 class="text-xl font-bold text-gray-800">Médiatár – Képek2</h2>
                    <button wire:click="$set('showModal', false)" class="text-gray-500 hover:text-gray-700">
                        ✖
                    </button>
                </div>

                <!-- Görgethető képrács -->
                <div class="overflow-y-auto max-h-[400px]">
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                        @foreach ($images as $image)
                            <div class="relative group cursor-pointer">
                                <img src="{{ $image['original_url'] }}"
                                     class="w-full max-h-32 object-cover rounded-lg shadow-md transition-transform transform hover:scale-105"
                                     wire:click="selectImage('{{ $image['original_url'] }}')"
                                />
{{--                                <div class="absolute inset-0 bg-black bg-opacity-50 hidden group-hover:flex items-center justify-center text-white font-semibold rounded-lg">--}}
{{--                                    Kiválasztás--}}
{{--                                </div>--}}
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Üres állapot -->
                @if (empty($images))
                    <p class="text-gray-500 text-center mt-4">Nincsenek elérhető képek.</p>
                @endif
            </div>
        </div>
    @endif
</div>
