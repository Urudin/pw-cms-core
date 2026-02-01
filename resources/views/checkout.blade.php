@extends('layouts.frontend')

@section('page')
    <!-- Checkout Form -->
    @php
        $shippingMethods = config('shipping_methods');
        $paymentMethods = config('payment_methods');
    @endphp
    <div class="max-w-3xl mx-auto mt-10 p-6 bg-white shadow-md rounded-lg">
        <form id="checkout-form" action="{{ route('placeOrder') }}" method="POST" class="space-y-6">
            @csrf
            <h2 class="text-2xl font-semibold mb-4">{{ __('messages.checkout') }}</h2>
            <!-- Step 1: Shipping Address -->
            <div id="step-1" class="bg-gray-50 border border-gray-200 shadow-sm rounded-xl p-6 space-y-4">
                <div class="text-lg font-semibold text-gray-800 flex items-center gap-2">
                    <span class="text-sm w-6 h-6 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold">1</span>
                    {{ __('messages.shipping_address') }}
                </div>
                <div class="space-y-4">
                    <input type="text" name="shipping_name" placeholder="{{ __('messages.name') }}*" class="w-full px-3 py-2 border rounded">
                    <input type="email" name="email" placeholder="{{ __('messages.email') }}*" class="w-full px-3 py-2 border rounded">
                    <input type="text" name="shipping_postal_code" placeholder="{{ __('messages.postal_code') }}*" class="w-full px-3 py-2 border rounded">
                    <input type="text" name="shipping_city" placeholder="{{ __('messages.city') }}*" class="w-full px-3 py-2 border rounded">
                    <input type="text" name="shipping_address_line1" placeholder="{{ __('messages.address_line_1') }}*" class="w-full px-3 py-2 border rounded">
                    <input type="text" name="shipping_address_line2" placeholder="{{ __('messages.address_line_2') }} ({{ __('messages.optional') }})" class="w-full px-3 py-2 border rounded">
                    <input type="tel" name="shipping_phone" placeholder="{{ __('messages.phone') }}*" class="w-full px-3 py-2 border rounded">
                </div>
            </div>

            <!-- Step 2: Billing Address -->
            <div id="step-2" class="bg-gray-50 border border-gray-200 shadow-sm rounded-xl p-6 space-y-4">
                <div class="text-lg font-semibold text-gray-800 flex items-center gap-2">
                    <span class="text-sm w-6 h-6 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold">2</span>
                    {{ __('messages.billing_address') }}
                </div>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" id="same-as-shipping" name="same_as_shipping" class="sr-only peer">
                    <div class="w-5 h-5 rounded border-2 border-gray-300 peer-checked:border-blue-600 peer-checked:bg-blue-600 flex items-center justify-center transition-all duration-200">
                        <svg class="w-3 h-3 text-white peer-checked:inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <span class="text-sm text-gray-800">{{ __('messages.same_as_shipping') }}</span>
                </label>
                <div id="billing-address-fields" class="space-y-4 mt-4">
                    <input type="text" name="billing_name" placeholder="{{ __('messages.name') }}*" class="w-full px-3 py-2 border rounded">
                    <input type="text" name="billing_postal_code" placeholder="{{ __('messages.postal_code') }}*" class="w-full px-3 py-2 border rounded">
                    <input type="text" name="billing_city" placeholder="{{ __('messages.city') }}*" class="w-full px-3 py-2 border rounded">
                    <input type="text" name="billing_address_line1" placeholder="{{ __('messages.address_line_1') }}*" class="w-full px-3 py-2 border rounded">
                    <input type="text" name="billing_address_line2" placeholder="{{ __('messages.address_line_2') }} ({{ __('messages.optional') }})" class="w-full px-3 py-2 border rounded">
                </div>
            </div>

            <!-- Step 2.5: Request Invoice -->
            <div id="step-invoice" class="bg-gray-50 border border-gray-200 shadow-sm rounded-xl p-6 space-y-4">
                <div class="text-lg font-semibold text-gray-800 flex items-center gap-2">
                    <span class="text-sm w-6 h-6 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold">🧾</span>
                    {{ __('messages.request_invoice') }}
                </div>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" id="request-invoice" name="request_invoice" class="sr-only peer">
                    <div class="w-5 h-5 rounded border-2 border-gray-300 peer-checked:border-blue-600 peer-checked:bg-blue-600 flex items-center justify-center transition-all duration-200">
                        <svg class="w-3 h-3 text-white peer-checked:inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <span class="text-sm text-gray-800">{{ __('messages.request_invoice') }}</span>
                </label>
                <div id="invoice-fields" class="space-y-4 mt-4 hidden">
                    <input type="text" name="invoice_company_name" placeholder="{{ __('messages.invoice_company_name') }}*" class="w-full px-3 py-2 border rounded">
                    <input type="text" name="invoice_vat_number" placeholder="{{ __('messages.vat_number') }}*" class="w-full px-3 py-2 border rounded">
                </div>
            </div>

            <!-- Step 3: Shipping Method -->
            <div id="step-3" class="bg-gray-50 border border-gray-200 shadow-sm rounded-xl p-6 space-y-4">
                <div class="text-lg font-semibold text-gray-800 flex items-center gap-2">
                    <span class="text-sm w-6 h-6 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold">3</span>
                    {{ __('messages.shipping_method') }}*
                </div>
                <p>{{__('messages.shipping_info')}}</p>
                <div class="grid gap-4 sm:grid-cols-1 md:grid-cols-2">
                    @foreach ($shippingMethods as $key => $method)
                        <label class="shipping-option cursor-pointer border rounded-lg shadow-sm p-4 flex items-center justify-between transition-all duration-200 hover:shadow-md">
                            <input type="radio" name="shipping_method" value="{{ $key }}" data-cost="{{ $method['cost'] }}" class="sr-only">
                            <span>{{ __('messages.' . $method['label']) }} (+{{ number_format($method['cost'], 2) }} HUF)</span>
                            <span class="checkmark w-5 h-5 rounded-full border border-gray-400"></span>
                        </label>
                    @endforeach
                </div>
            </div>

            <!-- Step 4: Payment Method -->
            <div id="step-4" class="bg-gray-50 border border-gray-200 shadow-sm rounded-xl p-6 space-y-4">
                <div class="text-lg font-semibold text-gray-800 flex items-center gap-2">
                    <span class="text-sm w-6 h-6 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold">4</span>
                    {{ __('messages.payment_method') }}*
                </div>
                <div class="grid gap-4 sm:grid-cols-1 md:grid-cols-2">
                    @foreach ($paymentMethods as $key => $method)
                        <label class="payment-option cursor-pointer border rounded-lg shadow-sm p-4 flex items-center justify-between transition-all duration-200 hover:shadow-md">
                            <input type="radio" name="payment_method" value="{{ $key }}" data-cost="{{ $method['cost'] }}" class="sr-only">
                            <span>{{ __('messages.' . $method['label']) }} (+{{ number_format($method['cost'], 2) }} HUF)</span>
                            <span class="checkmark w-5 h-5 rounded-full border border-gray-400"></span>
                        </label>
                    @endforeach
                </div>
            </div>

            <!-- Step 5: Cart Summary -->
            <div id="step-5" class="bg-gray-50 border border-gray-200 shadow-sm rounded-xl p-6 space-y-4">
                <div class="text-lg font-semibold text-gray-800 flex items-center gap-2">
                    <span class="text-sm w-6 h-6 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold">5</span>
                    {{ __('messages.your_cart') }}
                </div>
                <ul id="cart-items" class="space-y-2"></ul>
                <div id="cart-total" class="font-semibold text-right mt-4 text-lg"></div>
            </div>

            <!-- Accept Terms -->
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="privacy_accepted" id="privacy_policy" class="sr-only peer">
                <div class="w-5 h-5 border-2 border-gray-400 rounded-sm flex items-center justify-center transition-all duration-200 peer-checked:bg-blue-600 peer-checked:border-blue-600">
                    <svg class="w-3 h-3 text-white peer-checked:inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <span>Az <a class="text-blue-500 hover:underline" href="{{ route('privacy-policy') }}">adatkezelési tájékoztatóban</a> foglaltakat megismertem és elfogadom.*</span>
            </label>

            <!-- Google reCAPTCHA -->
            <hr class="my-6 border-t border-gray-200">
            <div class="form-group mb-3">
                {!! NoCaptcha::renderJs() !!}
                {!! NoCaptcha::display() !!}
                @error('g-recaptcha-response')
                <span class="text-danger">{{ $message }}</span>
                @enderror
            </div>

            <!-- Submit Button -->
            <button id="checkout-button" class="w-full bg-gray-500 text-white py-2 rounded cursor-not-allowed flex items-center justify-center gap-2" disabled>
                <svg id="loading-spinner" class="hidden animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                <span id="checkout-button-text">{{ __('messages.place_order') }}</span>
            </button>
        </form>
    </div>


    <!-- Include SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function setupSelectableBoxes(groupName, boxClass) {
            const inputs = document.querySelectorAll(`input[name="${groupName}"]`);

            inputs.forEach(input => {
                input.addEventListener('change', () => {
                    document.querySelectorAll(`.${boxClass}`).forEach(label => {
                        label.classList.remove('border-blue-500', 'ring-2', 'ring-blue-300');
                        label.querySelector('.checkmark').classList.remove('bg-blue-500', 'border-blue-500');
                    });

                    const selected = Array.from(inputs).find(i => i.checked);
                    if (selected) {
                        const label = selected.closest(`.${boxClass}`);
                        label.classList.add('border-blue-500', 'ring-2', 'ring-blue-300');
                        label.querySelector('.checkmark').classList.add('bg-blue-500', 'border-blue-500');
                    }

                    renderCart(); // Update cart with selected method costs
                });
            });
        }


        setupSelectableBoxes('shipping_method', 'shipping-option');
        setupSelectableBoxes('payment_method', 'payment-option');
    </script>
    <script>

        document.querySelectorAll('input[name="shipping_method"], input[name="payment_method"]').forEach(input => {
            input.addEventListener('change', () => {
                renderCart();
            });
        });

        let cart = JSON.parse(localStorage.getItem("cart")) || [];
        const cartItemsContainer = document.getElementById("cart-items");
        const cartTotalContainer = document.getElementById("cart-total");
        const checkoutForm = document.getElementById("checkout-form");

        function renderCart() {
            cartItemsContainer.innerHTML = cart.length ? '' : `<p>{{ __('messages.empty_cart') }}</p>`;
            let total = 0;

            cart.forEach(item => {
                const itemTotal = item.price * item.quantity;
                total += itemTotal;
                cartItemsContainer.innerHTML += `
            <li class="flex justify-between p-2 border-b">
                <span>${item.name} (x${item.quantity})</span>
                <span>${itemTotal.toFixed(2)} HUF</span>
            </li>`;
            });

            // Add selected shipping/payment costs
            const shippingMethod = document.querySelector('input[name="shipping_method"]:checked');
            const paymentMethod = document.querySelector('input[name="payment_method"]:checked');

            let shippingCost = shippingMethod ? parseFloat(shippingMethod.dataset.cost) : 0;
            let paymentCost = paymentMethod ? parseFloat(paymentMethod.dataset.cost) : 0;

            if (shippingMethod) {
                cartItemsContainer.innerHTML += `
            <li class="flex justify-between p-2 border-b">
                <span>{{ __('messages.shipping_cost') }}</span>
                <span>${shippingCost.toFixed(2)} HUF</span>
            </li>`;
                total += shippingCost;
            }

            if (paymentMethod) {
                cartItemsContainer.innerHTML += `
            <li class="flex justify-between p-2 border-b">
                <span>{{ __('messages.payment_cost') }}</span>
                <span>${paymentCost.toFixed(2)} HUF</span>
            </li>`;
                total += paymentCost;
            }

            cartTotalContainer.innerHTML = cart.length ? `{{ __('messages.total') }}: <span class="text-blue-600">${total.toFixed(2)} HUF</span>` : '';
        }

        renderCart();

        checkoutForm.addEventListener("submit", function (event) {
            event.preventDefault();

            if (cart.length === 0) {
                Swal.fire({
                    icon: "error",
                    title: "{{ __('messages.cart_empty_title') }}",
                    text: "{{ __('messages.cart_empty_text') }}",
                });
                return;
            }

            // Disable the button + show spinner
            const button = document.getElementById('checkout-button');
            const spinner = document.getElementById('loading-spinner');
            const buttonText = document.getElementById('checkout-button-text');
            button.disabled = true;
            button.classList.add('cursor-not-allowed', 'opacity-70');
            spinner.classList.remove('hidden');
            buttonText.textContent = "{{ __('messages.please_wait') }}";

            let formData = new FormData(checkoutForm);

            // Append cart items properly as an array
            cart.forEach((item, index) => {
                formData.append(`items[${index}][id]`, item.id);
                formData.append(`items[${index}][quantity]`, item.quantity);
            });

            fetch(checkoutForm.action, {
                method: "POST",
                body: formData,
                headers: {
                    "X-CSRF-TOKEN": document.querySelector("meta[name='csrf-token']").getAttribute("content")
                }
            })
                .then(response => {
                    if (response.status === 201) {
                        return response.json();
                    } else {
                        return response.json().then(err => {
                            throw err;
                        });
                    }
                })
                .then(data => {
                    localStorage.removeItem("cart"); // Clear cart after order
                    cart = [];
                    renderCart();

                    Swal.fire({
                        icon: "success",
                        title: "{{ __('messages.order_success_title') }}",
                        text: "{{ __('messages.order_success_text') }}",
                        confirmButtonText: "OK"
                    }).then(() => {
                        window.location.href = "/";
                    });
                })
                .catch(error => {
                    console.error("Order error:", error);

                    Swal.fire({
                        icon: "error",
                        title: "{{ __('messages.order_fail_title') }}",
                        text: error.items ? error.items[0] : "{{ __('messages.order_fail_text') }}",
                    });
                    // Re-enable the button and hide spinner
                    button.disabled = false;
                    spinner.classList.add('hidden');
                    buttonText.textContent = "{{ __('messages.place_order') }}";
                });
        });
    </script>
    <!-- JavaScript for Handling Steps -->
    <script>
        const checkoutButton = document.getElementById('checkout-button');
        const billingFields = document.getElementById('billing-address-fields');
        const sameAsShipping = document.getElementById('same-as-shipping');

        sameAsShipping.addEventListener('change', () => {
            billingFields.style.display = sameAsShipping.checked ? 'none' : 'block';
        });

        document.addEventListener('input', () => {
            const shippingFieldsValid = Array.from(document.querySelectorAll('#step-1 input[type="text"], #step-1 input[type="tel"], #step-1 input[type="email"]')).every(input => input.value.trim() !== "");
            const billingFieldsValid = sameAsShipping.checked || Array.from(document.querySelectorAll('#billing-address-fields input[type="text"]')).every(input => input.value.trim() !== "");
            const shippingMethodSelected = document.querySelector('input[name="shipping_method"]:checked');
            const paymentMethodSelected = document.querySelector('input[name="payment_method"]:checked');
            const privacyAccepted = document.querySelector('input[name="privacy_accepted"]:checked');

            if (shippingFieldsValid && billingFieldsValid && shippingMethodSelected && paymentMethodSelected && privacyAccepted) {
                checkoutButton.classList.remove('bg-gray-500', 'cursor-not-allowed');
                checkoutButton.classList.add('bg-blue-600');
                checkoutButton.disabled = false;
            } else {
                checkoutButton.classList.add('bg-gray-500', 'cursor-not-allowed');
                checkoutButton.classList.remove('bg-blue-600');
                checkoutButton.disabled = true;
            }
        });
    </script>
    <script>
        const requestInvoice = document.getElementById('request-invoice');
        const invoiceFields = document.getElementById('invoice-fields');

        requestInvoice.addEventListener('change', () => {
            if (requestInvoice.checked) {
                invoiceFields.classList.remove('hidden');
            } else {
                invoiceFields.classList.add('hidden');
            }
            validateForm();
        });

        function validateForm() {
            const shippingFieldsValid = Array.from(document.querySelectorAll('#step-1 input[type="text"], #step-1 input[type="tel"], #step-1 input[type="email"]')).every(input => input.value.trim() !== "");
            const billingFieldsValid = sameAsShipping.checked || Array.from(document.querySelectorAll('#billing-address-fields input[type="text"]')).every(input => input.value.trim() !== "");
            const shippingMethodSelected = document.querySelector('input[name="shipping_method"]:checked');
            const paymentMethodSelected = document.querySelector('input[name="payment_method"]:checked');
            const privacyAccepted = document.querySelector('input[name="privacy_accepted"]:checked');

            const invoiceValid = !requestInvoice.checked || (
                document.querySelector('[name="invoice_company_name"]').value.trim() !== "" &&
                document.querySelector('[name="invoice_vat_number"]').value.trim() !== ""
            );

            if (shippingFieldsValid && billingFieldsValid && shippingMethodSelected && paymentMethodSelected && invoiceValid && privacyAccepted) {
                checkoutButton.classList.remove('bg-gray-500', 'cursor-not-allowed');
                checkoutButton.classList.add('bg-blue-600');
                checkoutButton.disabled = false;
            } else {
                checkoutButton.classList.add('bg-gray-500', 'cursor-not-allowed');
                checkoutButton.classList.remove('bg-blue-600');
                checkoutButton.disabled = true;
            }
        }

        document.addEventListener('input', validateForm);
    </script>
@endsection
