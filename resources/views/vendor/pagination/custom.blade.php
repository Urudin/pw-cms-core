@if ($paginator->hasPages())
    <nav class="mt-6">
        <ul class="inline-flex items-center gap-2">
            @foreach ($elements as $element)
                {{-- ... --}}
                @if (is_string($element))
                    <li class="w-10 h-10 flex items-center justify-center bg-gray-800 text-white">
                        <span class="text-xs leading-none">{{ $element }}</span>
                    </li>
                @endif

                {{-- Pages --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="w-10 h-10 flex items-center justify-center bg-cyan-400 text-white font-semibold">
                                <span class="text-xs leading-none">{{ $page }}</span>
                            </li>
                        @else
                            <li>
                                <a href="{{ $url }}"
                                   class="w-10 h-10 flex items-center justify-center bg-gray-800 text-white hover:bg-cyan-500 transition">
                                    <span class="text-xs leading-none">{{ $page }}</span>
                                </a>
                            </li>
                        @endif
                    @endforeach
                @endif
            @endforeach
        </ul>
    </nav>
@endif
