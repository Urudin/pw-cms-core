@php
    /** @var \TomatoPHP\FilamentMediaManager\Models\Media $mediaRecord */
    $mediaRecord = \TomatoPHP\FilamentMediaManager\Models\Media::query()
        ->where('collection_name', 'headers')
        ->where('file_name', \App\Models\UserSetting::query()->firstWhere('name', 'header-background')->value)
        ->first();

    // Check if media record exists, otherwise use a fallback image
    $url = $mediaRecord ? asset('storage/' . $mediaRecord->model_id . '/' . $mediaRecord->file_name) : asset('images/default-header.jpg');

    // Logo URL
    $logoUrl = asset('images/gloszlogo.png');
@endphp

<div class="relative container mx-auto max-w-screen-xl bg-no-repeat bg-top bg-cover h-[250px] md:h-[308px] flex flex-col justify-between px-6"
     style="background-image: url('{{ $url }}');">

    <!-- Logo -->
    <div class="absolute top-[40px] top-sm-[60px] top-md-[80px] top-lg-[100px] left-[20px] left-sm-[40px] left-md-[60px] left-lg-[80px]">
        <img src="{{ $logoUrl }}" alt="Logo" class="h-[30px] w-auto sm:h-[50px] xs:h-[60px]">
    </div>

    <!-- Navigáció (asztali) -->
    <nav id="menu" class="absolute bottom-0 left-6 text-xl text-white flex space-x-4 items-center sm:flex hidden">
        <div class="relative group">
            <a href="#" class="hover:text-gray-400">Főoldal</a>

            <!-- Lenyíló Almenü -->
            <div id="submenu" style="background-color: #004070;" class="text-white absolute hidden opacity-0 left-0 rounded-md shadow-lg py-2 w-48 z-10 transition-opacity duration-300">
                <a href="#" class="block px-4 py-2 hover:text-yellow-500">Almenü 1</a>
                <a href="#" class="block px-4 py-2 hover:text-yellow-500">Almenü 2</a>
            </div>
        </div>

        <span class="text-yellow-400">|</span>
        <a href="#" class="hover:text-gray-400">Szolgáltatások</a>
        <span class="text-yellow-400">|</span>
        <a href="#" class="hover:text-gray-400">Rólunk</a>
        <span class="text-yellow-400">|</span>
        <a href="#" class="hover:text-gray-400">Kapcsolat</a>
    </nav>


    <!-- Sub-Header -->
    <div class="absolute bottom-[65px] sm:bottom-[60px] md:bottom-[65px] lg:bottom-[70px]
            left-[25px] sm:left-[25px] md:left-[25px] lg:left-[25px] flex items-center space-x-4">

        <!-- Szövegblokk -->
        <div class="text-lg sm:text-xl md:text-2xl lg:text-3xl text-white font-bold leading-tight">
            <span>{{ \App\Models\UserSetting::query()->firstWhere('name', 'header-sub-title-row-1')->value }}</span><br>
            <span>{{ \App\Models\UserSetting::query()->firstWhere('name', 'header-sub-title-row-2')->value }}</span>
        </div>

        <!-- Kép a szöveg mellett -->
        <div>
            <img src="{{ asset('images/30.png') }}" alt="Dekoratív kép" class="h-[40px] md:h-full object-contain">
        </div>

    </div>


    <!-- Hamburger Menü (bal alsó sarok) -->
    <div class="absolute bottom-0 left-6 sm:hidden">
        <button id="menu-toggle" class="text-white focus:outline-none flex items-center space-x-2">
            <svg class="h-8 w-8" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
            <span>Menüpontok</span>
        </button>
    </div>

</div>
<!-- Mobilmenü (header közvetlen alá) -->
<div id="mobile-menu"
     style="background-color: #004070;"
     class="left-0 w-full bg-gradient-to-r text-white flex flex-col pl-6 py-4 sm:hidden transition-all duration-300 ease-in-out opacity-0 scale-y-0 origin-top hidden">
    <a href="#" class="py-2 text-lg hover:text-gray-400">Főoldal</a>
    <a href="#" class="py-2 text-lg hover:text-gray-400">Szolgáltatások</a>
    <a href="#" class="py-2 text-lg hover:text-gray-400">Rólunk</a>
    <a href="#" class="py-2 text-lg hover:text-gray-400">Kapcsolat</a>
</div>

<!-- JavaScript a hamburger menühöz -->
<script>
    document.getElementById("menu-toggle").addEventListener("click", function () {
        let menu = document.getElementById("mobile-menu");

        if (menu.classList.contains("hidden")) {
            menu.classList.remove("hidden"); // Először láthatóvá tesszük
            menu.offsetHeight; // Ezt be kell tenni, hogy a böngésző érzékelje a DOM változást (Force Reflow)
            menu.classList.remove("opacity-0", "scale-y-0");
            menu.classList.add("opacity-100", "scale-y-100");
        } else {
            menu.classList.remove("opacity-100", "scale-y-100");
            menu.classList.add("opacity-0", "scale-y-0");
            setTimeout(() => {
                menu.classList.add("hidden");
            }, 300); // Megvárjuk az animáció végét
        }
    });

    document.addEventListener("DOMContentLoaded", function () {
        let menuItem = document.querySelector(".group");
        let submenu = document.getElementById("submenu");
        let timeout;

        menuItem.addEventListener("mouseenter", function () {
            clearTimeout(timeout);
            submenu.classList.remove("hidden");
            submenu.classList.add("opacity-100");
        });

        menuItem.addEventListener("mouseleave", function () {
            timeout = setTimeout(() => {
                submenu.classList.add("hidden");
                submenu.classList.remove("opacity-100");
            }, 300);
        });

        submenu.addEventListener("mouseenter", function () {
            clearTimeout(timeout);
        });

        submenu.addEventListener("mouseleave", function () {
            timeout = setTimeout(() => {
                submenu.classList.add("hidden");
                submenu.classList.remove("opacity-100");
            }, 300);
        });
    });
</script>



