document.addEventListener('DOMContentLoaded', function () {
    const shoppingCart = document.getElementById('shopping-cart');
    const toggleButtons = document.querySelectorAll('.toggle-button');

    if (!shoppingCart || toggleButtons.length === 0) return;

    const cartElement = document.getElementById('cartContainer');
    const checkoutButton = document.getElementById('checkout');
    const addToCartButtons = document.querySelectorAll('.add-to-cart');

    if (!cartElement) return;

    const CLOSED_CLASS = 'translate-x-[calc(100%-0.25rem)]';
    const OPEN_CLASS = 'translate-x-0';

    function setOpenStyles(isOpen) {
        shoppingCart.classList.toggle('border-l', isOpen);
        shoppingCart.classList.toggle('border-gray-300', isOpen);
        shoppingCart.classList.toggle('shadow-xl', isOpen);
    }

    function openCart() {
        shoppingCart.classList.remove(CLOSED_CLASS);
        shoppingCart.classList.add(OPEN_CLASS);
        setOpenStyles(true);
        localStorage.setItem('cartOpen', 'true');
    }

    function closeCart() {
        shoppingCart.classList.remove(OPEN_CLASS);
        shoppingCart.classList.add(CLOSED_CLASS);
        setOpenStyles(false);
        localStorage.setItem('cartOpen', 'false');
    }

    function toggleCart() {
        if (shoppingCart.classList.contains(OPEN_CLASS)) closeCart();
        else openCart();
    }

    // init open/close
    if (localStorage.getItem('cartOpen') === 'true') openCart();
    else closeCart();

    toggleButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            toggleCart();
        });
    });

    /* ---------------- CART STORAGE ---------------- */

    let cart = JSON.parse(localStorage.getItem('cart') || '[]');

    function saveCart() {
        localStorage.setItem('cart', JSON.stringify(cart));
        renderCart();
    }

    function formatHufAfa(n) {
        const v = Math.round(Number(n) || 0);
        // 6 900 formátum
        const s = v.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
        return `${s} Ft+áfa`;
    }

    function renderCart() {
        cartElement.innerHTML = '';

        if (!cart.length) {
            cartElement.innerHTML = '<p class="text-gray-600">A kosár üres.</p>';
            updateCartBadge();
            return;
        }

        cart.forEach(item => {
            const imgSrc = (item.image || '').startsWith('http')
                ? item.image
                : (item.image ? `/storage/${item.image}` : '');

            // MVP: fix kedvezmény 2000 → eredeti = current + 2000
            const current = Number(item.price) || 0;
            const original = current + 2000;

            const wrapper = document.createElement('div');
            wrapper.className = 'bg-[#efeff2] border border-gray-200 p-3 mb-4';

            wrapper.innerHTML = `
            <div class="relative">
                ${imgSrc ? `
                    <img src="${imgSrc}" class="w-full h-[150px] object-cover" alt="">
                ` : `
                    <div class="w-full h-[150px] bg-gray-200 flex items-center justify-center text-gray-500">
                        Nincs kép
                    </div>
                `}

                <!-- remove button (piros trash) -->
                <button type="button"
                        class="remove absolute top-2 left-2 w-8 h-8 bg-[#ff2b5c] rounded-sm flex items-center justify-center shadow">
                    <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M3 6h18"></path>
                        <path d="M8 6V4h8v2"></path>
                        <path d="M19 6l-1 14H6L5 6"></path>
                        <path d="M10 11v6"></path>
                        <path d="M14 11v6"></path>
                    </svg>
                </button>
            </div>

            <div class="pt-3">
                <div class="text-[#1f4fd6] font-black leading-snug text-[15px]">
                    ${item.name}
                </div>

                <div class="mt-2 flex items-end gap-4">
                    <div class="text-slate-400 line-through font-extrabold text-[14px]">
                        ${formatHufAfa(original)}
                    </div>
                    <div class="text-slate-800 font-extrabold text-[14px]">
                        ${formatHufAfa(current)}
                    </div>
                </div>
            </div>
        `;

            wrapper.querySelector('.remove').onclick = () => {
                cart = cart.filter(i => i.id !== item.id);
                saveCart();
            };

            cartElement.appendChild(wrapper);
        });
        const total = cart.reduce((sum, i) => sum + (Number(i.price) || 0), 0);
        const totalEl = document.getElementById('cartTotal');
        if (totalEl) totalEl.textContent = formatHufAfa(total);

        updateCartBadge();
    }

    /* ---------------- ADD TO CART (1x / video) ---------------- */

    addToCartButtons.forEach(button => {
        button.addEventListener('click', (e) => {
            e.preventDefault();

            const id = button.dataset.id;
            const name = button.dataset.name;
            const price = parseFloat(button.dataset.price || '0');
            const image = button.dataset.image || '';

            const existing = cart.find(i => i.id === id);

            if (existing) {
                // már benne van – csak nyissuk ki
                openCart();
                // opcionális: ide később tehetsz toastot
                return;
            }

            cart.push({ id, name, price, image, quantity: 1 });
            saveCart();
            openCart();
        });
    });

    /* ---------------- CHECKOUT ---------------- */

    if (checkoutButton) {
        checkoutButton.addEventListener('click', (e) => {
            e.preventDefault();

            if (!cart.length) {
                alert('A kosár üres.');
                return;
            }
            window.location.href = '/checkout';
        });
    }

    renderCart();
});

/* ---------------- BADGE ---------------- */

function updateCartBadge() {
    const cart = JSON.parse(localStorage.getItem('cart') || '[]');
    const badge = document.getElementById('cart-badge');
    if (!badge) return;

    const total = cart.length; // 1 db / videó → ennyi elem
    if (total > 0) {
        badge.textContent = total;
        badge.classList.remove('hidden');
    } else {
        badge.classList.add('hidden');
    }
}

document.addEventListener('DOMContentLoaded', updateCartBadge);
window.updateCartBadge = updateCartBadge;
