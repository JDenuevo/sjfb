<div class="max-w-[70rem] px-4 sm:px-6 lg:px-8 mx-auto">
  <div class="text-center">
    <h1 class="text-3xl font-bold text-center mb-6">Shop Our Products</h1>
    <p class="mt-1 text-gray-800">
      Explore our fresh products directly in our market
    </p>
  </div>

  <div id="toastContainer"></div>

  <div class="grid sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
    <?php
    if ($result->num_rows > 0) {
        $products = [];
        while ($row = $result->fetch_assoc()) {
            $product_id = $row['product_id'];
            if (!isset($products[$product_id])) {
                $products[$product_id] = [
                    'product_name' => $row['product_name'],
                    'product_description' => $row['product_description'],
                    'image_url' => !empty($row['image_path']) ? "http://localhost/sjfbi-js/uploads/products/" . $row['image_path'] : "http://localhost/sjfbi-js/uploads/products/default.png",
                    'variants' => []
                ];
            }
            if (!empty($row['variant_name'])) {
                $products[$product_id]['variants'][] = [
                    'variant_id' => $row['variant_id'],
                    'variant_name' => $row['variant_name'],
                    'variant_price' => $row['variant_price'],
                    'discount_price' => $row['discount_price'],
                    'unit_type' => $row['unit_type'] ?? 'piece',
                    'minimum_order' => $row['minimum_order'] ?? 1,
                    'order_increment' => $row['order_increment'] ?? 1
                ];
            }
        }

        foreach ($products as $product_id => $product) {
            $product_name = $product['product_name'];
            $product_description = $product['product_description'];
            $image_url = $product['image_url'];
            $variants = $product['variants'];
    ?>
    <div class="bg-white shadow-lg rounded-lg p-4 relative group block">
        <a href="item/<?= urlencode(strtolower(str_replace(' ', '-', $product_name))) ?>">
            <img src="<?= htmlspecialchars($image_url) ?>" alt="<?= htmlspecialchars($product_name) ?>" class="w-full h-48 object-cover rounded-md mb-4 shadow-sm">
        </a>
        <h3 class="text-xl font-semibold text-gray-800"><?= htmlspecialchars($product_name) ?></h3>
        <p class="text-md text-gray-500 description" data-product-id="<?= $product_id ?>">
            <?= htmlspecialchars($product_description) ?>
        </p>

        <!-- Add to Cart Form -->
        <form class="add-to-cart-form" data-product-id="<?= $product_id ?>">
            <input type="hidden" name="add_to_cart" value="1">
            <input type="hidden" name="product_id" value="<?= $product_id ?>">
            <input type="hidden" name="variant_id" value="">
            <input type="hidden" name="product_name" value="<?= htmlspecialchars($product_name) ?>">
            <input type="hidden" name="variant_name" value="">
            <input type="hidden" name="price" value="">
            <input type="hidden" name="image_url" value="<?= htmlspecialchars($image_url) ?>">
            <input type="hidden" name="quantity" value="">
            <input type="hidden" name="unit_type" value="">
            <input type="hidden" name="minimum_order" value="">
            <input type="hidden" name="order_increment" value="">

            <!-- Variant Buttons -->
            <div class="mt-2">
                <label class="block text-sm font-medium text-gray-700">Select Size:</label>
                <div class="flex flex-wrap gap-2">
                    <?php 
                    $first = true;
                    foreach ($variants as $variant) { ?>
                        <button type="button"
                            class="variant-button px-3 py-2 border rounded-lg text-sm font-medium 
                                hover:bg-gray-100 focus:bg-gray-200 transition-all duration-200 text-dark 
                                <?= $first ? 'selected-variant' : '' ?>"
                            data-product-id="<?= $product_id ?>"
                            data-variant-id="<?= $variant['variant_id'] ?>"
                            data-variant-name="<?= htmlspecialchars($variant['variant_name']) ?>"
                            data-variant-price="<?= $variant['variant_price'] ?>"
                            data-discount-price="<?= $variant['discount_price'] ?>"
                            data-unit-type="<?= $variant['unit_type'] ?>"
                            data-minimum-order="<?= $variant['minimum_order'] ?>"
                            data-order-increment="<?= $variant['order_increment'] ?>">
                            <?= htmlspecialchars($variant['variant_name']) ?>
                        </button>
                    <?php 
                        $first = false;
                    } ?>
                </div>
            </div>

            <!-- Quantity Selector with Unit Display -->
            <div class="mt-3">
                <div class="flex items-center">
                    <div class="flex items-center border border-gray-300 rounded">
                        <button type="button" class="decrease-quantity px-1 py-0.5 rounded-l text-sm hover:bg-orange-600 hover:text-white">-</button>
                        <input type="text" class="quantity w-12 px-1 py-0.5 text-center text-sm border-0" value="" placeholder="1" readonly>
                        <button type="button" class="increase-quantity px-1 py-0.5 rounded-r text-sm hover:bg-orange-600 hover:text-white">+</button>
                    </div>
                    &nbsp;
                    <span class="ml-2 text-sm font-medium text-gray-600 unit-display"></span>
                </div>
                <p class="text-xs text-gray-500 mt-1 minimum-order-text"></p>
            </div>

            <!-- Price and Discount Display -->
            <div class="price-display mt-3"></div>

            <!-- Add to Cart Button -->
            <button type="submit" name="add_to_cart" 
                    class="cursor-pointer mt-4 w-full py-2 rounded-lg justify-center items-center inline-flex bg-orange-600 hover:bg-orange-700 text-white transition-all duration-300 focus:outline-none" disabled>
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M17 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M17 17h-11v-14h-2" /><path d="M6 5l14 1l-1 7h-13" /></svg>
                    Add to Cart
            </button>

            <!-- Message to select a variant -->
            <p class="text-red-500 text-sm mt-2 variant-message hidden">Please select a variant first.</p>
            <p class="text-red-500 text-sm mt-2 minimum-error-message hidden"></p>
        </form>
    </div>
    <?php
        }
    } else {
        echo "<p class='text-center text-gray-500'>No products found.</p>";
    }
    $conn->close();
    ?>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle variant selection
    document.querySelectorAll('.variant-button').forEach(button => {
        button.addEventListener('click', function() {
            const productId = button.dataset.productId;
            const variantId = button.dataset.variantId;
            const variantName = button.dataset.variantName;
            const variantPrice = parseFloat(button.dataset.variantPrice);
            const discountPrice = parseFloat(button.dataset.discountPrice);
            const unitType = button.dataset.unitType;
            const minimumOrder = parseFloat(button.dataset.minimumOrder);
            const orderIncrement = parseFloat(button.dataset.orderIncrement);

            const form = document.querySelector(`.add-to-cart-form[data-product-id="${productId}"]`);
            
            // Remove selected class from all variant buttons
            form.querySelectorAll('.variant-button').forEach(btn => {
                btn.classList.remove('selected-variant');
            });
            button.classList.add('selected-variant');

            // Update hidden fields
            form.querySelector('input[name="variant_id"]').value = variantId;
            form.querySelector('input[name="variant_name"]').value = variantName;
            form.querySelector('input[name="price"]').value = discountPrice > 0 ? discountPrice : variantPrice;
            form.querySelector('input[name="unit_type"]').value = unitType;
            form.querySelector('input[name="minimum_order"]').value = minimumOrder;
            form.querySelector('input[name="order_increment"]').value = orderIncrement;

            // Set quantity to minimum order
            const quantityInput = form.querySelector('.quantity');
            quantityInput.value = minimumOrder;
            form.querySelector('input[name="quantity"]').value = minimumOrder;

            // Update unit display
            const unitDisplay = form.querySelector('.unit-display');
            unitDisplay.textContent = unitType === 'piece' ? 'pcs' : unitType;

            // Update minimum order text
            const minOrderText = form.querySelector('.minimum-order-text');
            minOrderText.textContent = `Minimum: ${minimumOrder} ${unitType === 'piece' ? 'pcs' : unitType}`;

            // Update displayed price
            updatePriceDisplay(form, variantPrice, discountPrice, minimumOrder);

            // Enable "Add to Cart"
            form.querySelector('button[name="add_to_cart"]').disabled = false;
            form.querySelector('.variant-message').classList.add('hidden');
            form.querySelector('.minimum-error-message').classList.add('hidden');
        });
    });

    // Handle quantity changes
    document.querySelectorAll('.decrease-quantity').forEach(button => {
        button.addEventListener('click', function() {
            const form = button.closest('.add-to-cart-form');
            const quantityInput = form.querySelector('.quantity');
            const minimumOrder = parseFloat(form.querySelector('input[name="minimum_order"]').value);
            const orderIncrement = parseFloat(form.querySelector('input[name="order_increment"]').value);
            const currentQty = parseFloat(quantityInput.value);

            const newQty = Math.max(minimumOrder, currentQty - orderIncrement);
            quantityInput.value = newQty.toFixed(2).replace(/\.?0+$/, '');
            form.querySelector('input[name="quantity"]').value = newQty;
            
            updateTotalPrice(form);
        });
    });

    document.querySelectorAll('.increase-quantity').forEach(button => {
        button.addEventListener('click', function() {
            const form = button.closest('.add-to-cart-form');
            const quantityInput = form.querySelector('.quantity');
            const orderIncrement = parseFloat(form.querySelector('input[name="order_increment"]').value);
            const currentQty = parseFloat(quantityInput.value);

            const newQty = currentQty + orderIncrement;
            quantityInput.value = newQty.toFixed(2).replace(/\.?0+$/, '');
            form.querySelector('input[name="quantity"]').value = newQty;
            
            updateTotalPrice(form);
        });
    });

    function updatePriceDisplay(form, variantPrice, discountPrice, quantity) {
        const priceDisplay = form.querySelector('.price-display');
        const price = discountPrice > 0 ? discountPrice : variantPrice;
        const total = price * quantity;

        if (discountPrice > 0) {
            priceDisplay.innerHTML = `
                <span class="line-through text-gray-500 text-sm">₱${(variantPrice * quantity).toFixed(2)}</span>
                <span class="text-red-600 font-bold ml-2">₱${total.toFixed(2)}</span>
            `;
        } else {
            priceDisplay.innerHTML = `
                <span class="text-gray-800 font-bold">₱${total.toFixed(2)}</span>
            `;
        }
    }

    function updateTotalPrice(form) {
        const quantity = parseFloat(form.querySelector('.quantity').value);
        const price = parseFloat(form.querySelector('input[name="price"]').value);
        const variantPrice = parseFloat(form.querySelector('.selected-variant').dataset.variantPrice);
        const discountPrice = parseFloat(form.querySelector('.selected-variant').dataset.discountPrice);
        
        updatePriceDisplay(form, variantPrice, discountPrice, quantity);
    }

    // Auto-select first variant on load
    document.querySelectorAll('.add-to-cart-form').forEach(form => {
        const firstButton = form.querySelector('.variant-button');
        if (firstButton) firstButton.click();
    });

    // Enhanced add to cart handler with better error handling
    document.querySelectorAll('.add-to-cart-form').forEach(form => {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();

            const variantId = form.querySelector('input[name="variant_id"]').value;
            const quantity = parseFloat(form.querySelector('input[name="quantity"]').value);
            const minimumOrder = parseFloat(form.querySelector('input[name="minimum_order"]').value);
            const unitType = form.querySelector('input[name="unit_type"]').value;
            const errorMessage = form.querySelector('.minimum-error-message');

            if (!variantId) {
                form.querySelector('.variant-message').classList.remove('hidden');
                return;
            }

            if (quantity < minimumOrder) {
                errorMessage.textContent = `Minimum order is ${minimumOrder} ${unitType === 'piece' ? 'pcs' : unitType}`;
                errorMessage.classList.remove('hidden');
                return;
            }

            try {
                const response = await fetch('./functions/add_to_cart.php', {
                    method: 'POST',
                    body: new FormData(form)
                });
                
                // Check if response is OK
                if (!response.ok) {
                    throw new Error(`Server error: ${response.status} ${response.statusText}`);
                }
                
                // Try to parse JSON
                const text = await response.text();
                let data;
                try {
                    data = JSON.parse(text);
                } catch (parseError) {
                    console.error('JSON parse error:', parseError, 'Response text:', text);
                    throw new Error('Invalid response from server');
                }
                
                if (data.status === 'success') {
                    showToast('Product added to cart');
                    await updateCartUI();
                    
                    // Reset to minimum order
                    const firstButton = form.querySelector('.variant-button');
                    if (firstButton) firstButton.click();
                } else {
                    showToast(data.message || 'Failed to add product', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('An error occurred while adding to cart: ' + error.message, 'error');
            }
        });
    });

    async function updateCartUI() {
        try {
            const response = await fetch('./functions/fetch_cart_items.php');
            const data = await response.json();
            
            if (data.status === 'success') {
                document.getElementById('cart-items-list').innerHTML = data.cart_items;
                
                document.querySelectorAll('.cart-count').forEach(el => {
                    el.textContent = data.cart_count;
                    el.classList.add('animate-bounce');
                    setTimeout(() => el.classList.remove('animate-bounce'), 1000);
                });
                
                const cartTotal = document.getElementById('cart-total-sidebar');
                if (cartTotal) {
                    cartTotal.textContent = `₱${data.cart_total.toFixed(2)}`;
                }
            }
        } catch (error) {
            console.error('Error updating cart:', error);
        }
    }

    function showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = 'toast';
        if (type === 'error') {
            toast.style.background = '#dc2626';
        }
        toast.textContent = message;

        document.getElementById('toastContainer').appendChild(toast);

        setTimeout(() => {
            toast.remove();
        }, 3500);
    }
});
</script>

<style>
.selected-variant {
    background-color: #f59e0b;
    border-color: #f59e0b;
    color: #FFFFFF;
}

#toastContainer {
    position: fixed;
    bottom: 50%;
    left: 50%;
    transform: translateX(-50%);
    z-index: 50;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.toast {
    background: #ea580c;
    color: #fff;
    padding: 12px 20px;
    border-radius: 9999px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
    font-size: 1rem;
    font-weight: bold;
    text-align: center;
    min-width: 250px;
    animation: fadeIn 0.3s ease-in, fadeOut 0.3s ease-out 3s forwards;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes fadeOut {
    from { opacity: 1; transform: translateY(0); }
    to { opacity: 0; transform: translateY(10px); }
}

.animate-bounce {
    animation: bounce 0.5s;
}

@keyframes bounce {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.5); }
}
</style>
