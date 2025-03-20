
<div class="max-w-[70rem] px-4 sm:px-6 lg:px-8 mx-auto">
  <div class="text-center">
    <h1 class="text-3xl font-bold text-center mb-6">Shop Our Products</h1>
    <p class="mt-1 text-gray-800">
      Explore our fresh products directly in our market
    </p>
  </div>

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
                    'image_url' => !empty($row['image_path']) ? "http://localhost/sjfbi-js/admin/uploads/products/" . $row['image_path'] : "http://localhost/sjfbi-js/admin/uploads/products/default.png",
                    'variants' => []
                ];
            }
            if (!empty($row['variant_name'])) {
                $products[$product_id]['variants'][] = [
                    'variant_id' => $row['variant_id'],
                    'variant_name' => $row['variant_name'],
                    'variant_price' => $row['variant_price'],
                    'discount_price' => $row['discount_price']
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
        <img src="<?= htmlspecialchars($image_url) ?>" alt="<?= htmlspecialchars($product_name) ?>" class="w-full h-48 object-cover rounded-md mb-4 shadow-sm">
        
        <h3 class="text-xl font-semibold text-gray-800"><?= htmlspecialchars($product_name) ?></h3>
        <p class="text-md text-gray-500 description" data-product-id="<?= $product_id ?>">
            <?= htmlspecialchars($product_description) ?>
        </p>

        <!-- Add to Cart Form -->
        <form class="add-to-cart-form" data-product-id="<?= $product_id ?>">
            <input type="hidden" name="add_to_cart" value="1"> <!-- Add this line -->
            <input type="hidden" name="product_id" value="<?= $product_id ?>">
            <input type="hidden" name="variant_id" value="">
            <input type="hidden" name="product_name" value="<?= htmlspecialchars($product_name) ?>">
            <input type="hidden" name="variant_name" value="">
            <input type="hidden" name="price" value="">
            <input type="hidden" name="image_url" value="<?= htmlspecialchars($image_url) ?>">
            <input type="hidden" name="quantity" value="1">


            <!-- Quantity Selector -->
            <div class="flex items-center border border-gray-300 rounded">
                <button type="button" class="decrease-quantity px-1 py-0.5 rounded-l text-sm hover:bg-orange-600">-</button>
                <input type="text" class="quantity w-12 px-1 py-0.5 text-center text-sm border-0" value="1" readonly>
                <button type="button" class="increase-quantity px-1 py-0.5 rounded-r text-sm hover:bg-orange-600">+</button>
            </div>

            <!-- Variant Buttons -->
            <div class="mt-2">
                <label class="block text-sm font-medium text-gray-700">Select Size:</label>
                <div class="flex flex-wrap gap-2">
                    <?php foreach ($variants as $variant) { ?>
                        <button type="button" 
                                class="variant-button px-4 py-2 border rounded-lg text-sm font-medium 
                                      hover:bg-gray-100 focus:bg-gray-200 transition-all duration-200"
                                data-product-id="<?= $product_id ?>"
                                data-variant-id="<?= $variant['variant_id'] ?>"
                                data-variant-name="<?= htmlspecialchars($variant['variant_name']) ?>"
                                data-variant-price="<?= $variant['variant_price'] ?>"
                                data-discount-price="<?= $variant['discount_price'] ?>">
                            <?= htmlspecialchars($variant['variant_name']) ?>
                        </button>
                    <?php } ?>
                </div>
            </div>

            <!-- Price and Discount Display -->
            <div class="price-display mt-3">
                <?php if (!empty($variants)) { ?>
                    <?php if ($variants[0]['discount_price'] > 0) { ?>
                        <span class="line-through text-gray-500 original-price">₱<?= number_format($variants[0]['variant_price'], 2) ?></span>
                        <span class="text-red-600 font-bold ml-2 discount-price">₱<?= number_format($variants[0]['discount_price'], 2) ?></span>
                    <?php } else { ?>
                        <span class="text-gray-800 font-bold original-price">₱<?= number_format($variants[0]['variant_price'], 2) ?></span>
                    <?php } ?>
                <?php } ?>
            </div>

            <!-- Add to Cart Button -->
            <button type="submit" name="add_to_cart" 
                    class="mt-4 w-full size-10 rounded-full justify-center items-center inline-flex bg-orange-600 hover:bg-orange-400 text-white hover:scale-110 transition-all duration-500 focus:outline-none" disabled>
                Add to Cart
            </button>
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
            const variantPrice = button.dataset.variantPrice;
            const discountPrice = button.dataset.discountPrice;

            // Update the form hidden fields
            const form = document.querySelector(`.add-to-cart-form[data-product-id="${productId}"]`);
            form.querySelector('input[name="variant_id"]').value = variantId;
            form.querySelector('input[name="variant_name"]').value = variantName;
            form.querySelector('input[name="price"]').value = discountPrice > 0 ? discountPrice : variantPrice;

            // Update the displayed price
            const priceDisplay = form.querySelector('.price-display');
            if (discountPrice > 0) {
                priceDisplay.innerHTML = `
                    <span class="line-through text-gray-500 original-price">₱${parseFloat(variantPrice).toFixed(2)}</span>
                    <span class="text-red-600 font-bold ml-2 discount-price">₱${parseFloat(discountPrice).toFixed(2)}</span>
                `;
            } else {
                priceDisplay.innerHTML = `
                    <span class="text-gray-800 font-bold original-price">₱${parseFloat(variantPrice).toFixed(2)}</span>
                `;
            }

            // Enable the "Add to Cart" button
            form.querySelector('button[name="add_to_cart"]').disabled = false;
        });
    });

    // Handle quantity changes
    document.querySelectorAll('.decrease-quantity').forEach(button => {
        button.addEventListener('click', function() {
            const form = button.closest('.add-to-cart-form');
            const quantityInput = form.querySelector('.quantity');
            if (quantityInput.value > 1) {
                quantityInput.value = parseInt(quantityInput.value) - 1;
            }
            // Update the hidden quantity input
            form.querySelector('input[name="quantity"]').value = quantityInput.value;
        });
    });

    document.querySelectorAll('.increase-quantity').forEach(button => {
        button.addEventListener('click', function() {
            const form = button.closest('.add-to-cart-form');
            const quantityInput = form.querySelector('.quantity');
            quantityInput.value = parseInt(quantityInput.value) + 1;
            // Update the hidden quantity input
            form.querySelector('input[name="quantity"]').value = quantityInput.value;
        });
    });

    document.querySelectorAll('.add-to-cart-form').forEach(form => {
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(form);

        // Log form data for debugging
        for (let [key, value] of formData.entries()) {
            console.log(key, value);
        }

        fetch('./functions/add_to_cart.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                alert('Product added to cart');
                fetchCart(); // Refresh the cart sidebar
            } else {
                alert('Failed to add product to cart: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    });
});
  function fetchCart() {
      fetch('./components/cart.php')
          .then(response => response.text())
          .then(data => {
              document.getElementById('cart-items-list').innerHTML = data;
          });
    }
});
</script>