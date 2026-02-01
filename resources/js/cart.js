document.addEventListener('DOMContentLoaded', function () {
    const shoppingCart = document.getElementById('shopping-cart');
    const toggleButtons = document.querySelectorAll('.toggle-button');

    if (!shoppingCart || toggleButtons.length === 0) return;

    const CLOSED_CLASS = 'translate-x-[calc(100%-0.25rem)]';
    const OPEN_CLASS = 'translate-x-0';

    function setOpenStyles(isOpen) {
        // csak nyitva legyen “panel” érzete – ez szünteti meg a csukott csíkot
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

    // init
    if (localStorage.getItem('cartOpen') === 'true') openCart();
    else closeCart();

    toggleButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            toggleCart();
        });
    });

    /* ---------------- CART LOGIC ---------------- */

    const cartElement = document.getElementById('cartContainer');
    const checkoutButton = document.getElementById('checkout');
    const addToCartButtons = document.querySelectorAll('.add-to-cart');

    let cart = JSON.parse(localStorage.getItem('cart')) || [];

    function renderCart() {
        cartElement.innerHTML = '';
        const imgSrc = (item.image || '').startsWith('http') ? item.image : `/storage/${item.image}`;

        if (cart.length === 0) {
            cartElement.innerHTML = '<p class="text-gray-600">Your cart is empty.</p>';
            updateCartBadge();
            return;
        }

        cart.forEach(item => {
            const wrapper = document.createElement('div');
            wrapper.className = 'flex justify-between items-start mt-4';

            wrapper.innerHTML = `
                <div class="flex gap-3">
                    <img src="${imgSrc}" class="h-16 w-16 object-cover rounded">
                    <div>
                        <p class="text-sm font-medium text-gray-700">${item.name}</p>
                        <div class="flex items-center mt-1">
                            <button class="dec text-gray-500 px-1">−</button>
                            <span class="mx-2 text-sm">${item.quantity}</span>
                            <button class="inc text-gray-500 px-1">+</button>
                        </div>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-sm text-gray-600">${item.quantity * item.price} HUF</p>
                    <button class="remove text-red-500 text-sm mt-1">×</button>
                </div>
            `;

            wrapper.querySelector('.inc').onclick = () => {
                item.quantity++;
                saveCart();
            };

            wrapper.querySelector('.dec').onclick = () => {
                item.quantity--;
                if (item.quantity <= 0) {
                    cart = cart.filter(i => i.id !== item.id);
                }
                saveCart();
            };

            wrapper.querySelector('.remove').onclick = () => {
                cart = cart.filter(i => i.id !== item.id);
                saveCart();
            };

            cartElement.appendChild(wrapper);
        });

        updateCartBadge();
    }

    function saveCart() {
        localStorage.setItem('cart', JSON.stringify(cart));
        renderCart();
    }

    addToCartButtons.forEach(button => {
        button.addEventListener('click', () => {
            const id = button.dataset.id;
            const name = button.dataset.name;
            const price = parseFloat(button.dataset.price);
            const image = button.dataset.image;

            const existing = cart.find(i => i.id === id);
            if (existing) {
                existing.quantity++;
            } else {
                cart.push({ id, name, price, image, quantity: 1 });
            }

            saveCart();
            openCart();
        });
    });

    if (checkoutButton) {
        checkoutButton.addEventListener('click', () => {
            if (!cart.length) {
                alert('Your cart is empty.');
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

    const total = cart.reduce((s, i) => s + i.quantity, 0);
    if (total > 0) {
        badge.textContent = total;
        badge.classList.remove('hidden');
    } else {
        badge.classList.add('hidden');
    }
}

document.addEventListener('DOMContentLoaded', updateCartBadge);
window.updateCartBadge = updateCartBadge;
