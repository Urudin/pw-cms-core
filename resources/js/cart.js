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

    function formatHuf(n) {
        // egyszerű MVP formázás
        return `${Math.round(n)} Ft`;
    }

    function renderCart() {
        cartElement.innerHTML = '';

        if (!cart.length) {
            cartElement.innerHTML = '<p class="text-gray-600">A kosár üres.</p>';
            updateCartBadge();
            return;
        }

        cart.forEach(item => {
            const wrapper = document.createElement('div');
            wrapper.className = 'flex justify-between items-start mt-4';

            const imgSrc = (item.image || '').startsWith('http')
                ? item.image
                : (item.image ? `/storage/${item.image}` : '');

            wrapper.innerHTML = `
                <div class="flex gap-3">
                    ${imgSrc ? `<img src="${imgSrc}" class="h-16 w-16 object-cover rounded" alt="">` : ''}
                    <div>
                        <p class="text-sm font-medium text-gray-700">${item.name}</p>
                        <p class="text-xs text-gray-500 mt-1">1 db / videó</p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-sm text-gray-700 font-semibold">${formatHuf(item.price)}</p>
                    <button class="remove text-red-500 text-sm mt-1" type="button">Eltávolítás</button>
                </div>
            `;

            wrapper.querySelector('.remove').onclick = () => {
                cart = cart.filter(i => i.id !== item.id);
                saveCart();
            };

            cartElement.appendChild(wrapper);
        });

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
