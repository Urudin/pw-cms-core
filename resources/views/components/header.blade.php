@php
    /** @var \TomatoPHP\FilamentMediaManager\Models\Media $mediaRecord */
    $url = \App\Models\UserSetting::getHeaderUrl();

    $menu = \App\Models\Menu::query()->where('id', 1)->with('menu_items', fn($q) => $q->whereNull('parent_id')->with('children'))->first();
@endphp

<div
    class="relative container mx-auto max-w-screen-xl
           bg-no-repeat
           bg-[position:10%_center]
           bg-cover
           md:bg-center
           md:bg-cover
           h-[250px] md:h-[334px]
           flex flex-col justify-between px-6"
    style="background-image: url('{{ $url }}')">
    <a href="/" class="absolute inset-0 z-[20] block"></a>

    <!-- Navigáció (asztali) -->
    <div class="absolute bottom-0 md:bottom-[0] left-0 pl-[2%] bg-[#2137D6] w-full z-[30]">

    <nav id="menu"
         class="xl:text-[21px] lg:text-[18px] text-[13px] font-bold text-white md:tracking-[1px] xl:tracking-[2px] flex items-center md:flex hidden w-full">
        @foreach($menu->menu_items as $index => $menuItem)
            @if($menuItem->children->count())
                <div class="relative menu-item">
                    <div class="md:pb-[12px] lg:pb-[11px]">
                        <a target="{{$menuItem->target}}"
                           href="{{!empty($menuItem->menuable) ? route('pages.show', ['slug' => $menuItem->menuable->slug]) : $menuItem->url}}"
                           class="hover:text-gray-400 relative @if(!$loop->first) sm:pl-[10px] md:pl-[10px] lg:pl-[15px] xl:pl-[20px] @endif @if(!$loop->last) sm:pr-[10px] md:pr-[10px] lg:pr-[15px] xl:pr-[20px] border-r-2 border-[#B58E03] @endif"
                           title="{{ $menuItem->link_title ?? $menuItem->name }}">
                            {{$menuItem->name}}
                        </a>
                    </div>
                    @if(!$loop->last)
                    <!-- Border Connector -->
                    <div class="border-connector absolute top-full left-[100%] -mt-[14px] -ml-[2px] w-[2px] bg-[#B58E03]" style="height: 0; transition: height 0.1s;"></div>
                    @endif
                    <!-- Dropdown Menu -->
                    <div class="dropdown-menu text-white absolute hidden"
                         style="display: none; background-color: #2137D6; border-top: 3px solid #B58E03; white-space: nowrap; overflow-x: auto; position: absolute; top: 100%; left: 0;">
                    @foreach($menuItem->children as $subMenuItem)
                            <a target="{{$subMenuItem->target}}"
                               href="{{!empty($subMenuItem->menuable) ? route('pages.show', ['slug' => $subMenuItem->menuable->slug]) : $subMenuItem->url}}"
                               class="block px-4 py-2 hover:text-yellow-500 truncate"
                               title="{{ $subMenuItem->link_title ?? $subMenuItem->name }}">
                                {{$subMenuItem->name}}
                            </a>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="relative menu-item">
                    <div class="md:pb-[12px] lg:pb-[10px]">
                        <a target="{{$menuItem->target}}"
                           href="{{!empty($menuItem->menuable) ? route('pages.show', ['slug' => $menuItem->menuable->slug]) : $menuItem->url}}"
                           class="hover:text-gray-400 sm:px-[10px] md:px-[10px] lg:px-[15px] xl:px-[20px] relative @if(!$loop->last) border-r-2 border-[#B58E03] @endif"
                           title="{{ $menuItem->link_title ?? $menuItem->name }}">
                            {{$menuItem->name}}
                        </a>
                    </div>
                </div>
            @endif
        @endforeach
    </nav>
    </div>



    <!-- Sub-Header -->
    <div class="absolute bottom-[35px] sm:bottom-[30px] md:bottom-[40px] lg:bottom-[45px]
        left-[25px] sm:left-[25px] md:left-[25px] lg:left-[25px] flex flex-col md:flex-row-reverse md:items-center md:justify-between w-full max-w-screen-xl z-[30]">

        <!-- Kép a szöveg mellett (Asztali nézetben jobbra húzva) -->
        <div class="hidden md:block mr-[40px]">
        </div>

        <!-- Szövegblokk Kattinthatóvá Tétele (SEO Barát Módon) -->
        <div class="flex gap-4">
            <a href="/"
               class="lg:pl-[4%] no-underline block text-[0.95rem] sm:text-xl md:text-2xl xl:text-[1.675rem] text-white xl:leading-[2.2rem] z-[30] hover:no-underline">
                {{ \App\Models\UserSetting::query()->firstWhere('name', 'header-sub-title-row-1')->value }}
                <br/>
                {{ \App\Models\UserSetting::query()->firstWhere('name', 'header-sub-title-row-2')->value }}
            </a>
        </div>
    </div>


    <!-- Hamburger Menü (bal alsó sarok) -->
    <div class="absolute bottom-[4px] left-6 md:hidden z-[30]">
        <button id="menu-toggle" class="text-white focus:outline-none flex items-center space-x-2">
            <svg class="h-8 w-8" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                 stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
            <span>Menüpontok</span>
        </button>
    </div>

</div>
<!-- Mobilmenü (header közvetlen alá) -->
<div id="mobile-menu"
     style="background-color: #2137D6;"
     class="left-0 w-full bg-gradient-to-r text-white flex flex-col pl-6 py-4 md:hidden transition-all duration-300 ease-in-out opacity-0 scale-y-0 origin-top hidden">

    @foreach($menu->menu_items as $menuItem)
        @if($menuItem->children->count())
            <div class="flex flex-col">
                <!-- Főmenü + Nyitó ikon egy sorban -->
                <div class="flex justify-between items-center pr-6">
                    <a href="{{!empty($menuItem->menuable) ? route('pages.show', ['slug' => $menuItem->menuable->slug]) : $menuItem->url}}"
                       class="py-2 text-lg hover:text-gray-400"
                       title="{{ $menuItem->link_title ?? $menuItem->name }}">{{$menuItem->name}}</a>
                    <button id="mobile-menu-toggle" class="focus:outline-none">
                        <svg id="mobile-menu-icon" class="h-5 w-5 transform transition-transform duration-300"
                             xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                </div>

                <!-- Almenü -->
                <div id="mobile-submenu" class="hidden pl-4 transition-all duration-300">
                    @foreach($menuItem->children as $subMenuItem)
                        <a target="{{$subMenuItem->target}}"
                           href="{{!empty($subMenuItem->menuable) ? route('pages.show', ['slug' => $subMenuItem->menuable->slug]) : $subMenuItem->url}}"
                           class="block py-2 text-lg hover:text-gray-400"
                           title="{{ $subMenuItem->link_title ?? $subMenuItem->name }}"
                        >{{$subMenuItem->name}}</a>
                    @endforeach
                </div>
            </div>
        @else
            <a href="{{!empty($menuItem->menuable) ? route('pages.show', ['slug' => $menuItem->menuable->slug]) : $menuItem->url}}"
               class="py-2 text-lg hover:text-gray-400">{{$menuItem->name}}</a>
        @endempty
    @endforeach

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

    document.addEventListener("DOMContentLoaded", function () {
        let menuToggle = document.getElementById("mobile-menu-toggle");
        let submenu = document.getElementById("mobile-submenu");
        let menuIcon = document.getElementById("mobile-menu-icon");

        menuToggle.addEventListener("click", function () {
            submenu.classList.toggle("hidden");

            if (!submenu.classList.contains("hidden")) {
                menuIcon.classList.add("rotate-180");
            } else {
                menuIcon.classList.remove("rotate-180");
            }
        });
    });

    document.addEventListener("DOMContentLoaded", function () {
        const menuItems = document.querySelectorAll('.menu-item');

        function setDropdownWidth(item, dropdown) {
            const itemRect = item.getBoundingClientRect();
            const next = item.nextElementSibling; // a következő menüelem (ha van)
            const nextRect = next ? next.getBoundingClientRect() : null;

            // Alap: legalább a saját szélessége
            let width = itemRect.width;

            // + a következő elem szélessége, hogy “átlógjon” rá
            if (nextRect) width += nextRect.width;

            dropdown.style.minWidth = `${Math.ceil(width)}px`;
        }

        menuItems.forEach(item => {
            const dropdown = item.querySelector('.dropdown-menu');
            const connector = item.querySelector('.border-connector');

            item.addEventListener('mouseenter', () => {
                if (dropdown) {
                    setDropdownWidth(item, dropdown);
                    dropdown.style.display = 'block';
                }
                if (connector) {
                    connector.style.height = '15px';
                }
            });

            item.addEventListener('mouseleave', () => {
                if (dropdown) dropdown.style.display = 'none';
                if (connector) connector.style.height = '0';
            });
        });
    });
</script>

