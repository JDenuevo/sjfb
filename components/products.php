<div class="max-w-[70rem] px-4 sm:px-6 lg:px-8 mx-auto">
  <div class="text-center">
    <h1 class="text-3xl font-bold text-center mb-6">Shop Our Products</h1>
    <p class="mt-1 text-gray-800">
      Explore our fresh products directly in our market
    </p>
  </div>

  <div class="grid sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
      <?php

      $query = "SELECT p.product_name, 
            GROUP_CONCAT(DISTINCT p.product_id ORDER BY p.product_size ASC) AS product_ids,
            GROUP_CONCAT(DISTINCT p.product_size ORDER BY p.product_size ASC) AS sizes,
            GROUP_CONCAT(DISTINCT p.product_description ORDER BY p.product_size ASC) AS descriptions,
            GROUP_CONCAT(DISTINCT p.product_price ORDER BY p.product_size ASC) AS prices,
            GROUP_CONCAT(DISTINCT p.discount_price ORDER BY p.product_size ASC) AS discount_prices,
            (SELECT image_path FROM product_images WHERE product_id = MIN(p.product_id) LIMIT 1) AS image_path 
      FROM products p
      GROUP BY p.product_name;";

      $result = mysqli_query($conn, $query);

      while ($row = mysqli_fetch_assoc($result)) {
          $product_name = $row['product_name'];
          $image_url = !empty($row['image_path']) ? "http://localhost/sjfbi-js/admin/uploads/products/" . $row['image_path'] : "http://localhost/sjfbi-js/admin/uploads/products/default.jpg";
          
          $product_ids = explode(',', $row['product_ids']);
          $sizes = explode(',', $row['sizes']);
          $descriptions = explode(',', $row['descriptions']);
          $prices = explode(',', $row['prices']);
          $discount_prices = explode(',', $row['discount_prices']);
      
          $default_description = $descriptions[0];
          $default_price = number_format($prices[0], 2);
          $default_discount_price = !empty($discount_prices[0]) ? number_format($discount_prices[0], 2) : null;
      ?>
      
      <div class="bg-white shadow-lg rounded-lg p-4 relative group">
        <img src="<?= htmlspecialchars($image_url) ?>" alt="<?= htmlspecialchars($product_name) ?>" class="w-full h-48 object-cover rounded-md mb-4 shadow-sm">
        
        <h3 class="text-xl font-semibold text-gray-800"><?= htmlspecialchars($product_name) ?></h3>
        <p class="text-md text-gray-500 description" data-product-id="<?= $product_ids[0] ?>">
            <?= htmlspecialchars($default_description) ?>
        </p>

        <label class="block text-sm font-medium text-gray-700 mt-2">Select Size:</label>
        <select class="block w-full p-2 border rounded size-selector" 
                data-product-id="<?= $product_ids[0] ?>">
            <?php foreach ($sizes as $index => $size) { ?>
                <option value="<?= htmlspecialchars($size) ?>"><?= htmlspecialchars($size) ?></option>
            <?php } ?>
        </select>

        <p id="price_<?= $product_ids[0] ?>" class="text-gray-600 text-lg font-semibold mt-3 price-display">
            <?php if ($default_discount_price > 0) { ?>
                <span class="line-through text-gray-500 original-price">₱<?= $default_price ?></span>
                <span class="text-red-600 font-bold ml-2 my-5 discount-price">₱<?= $default_discount_price ?></span>
            <?php } else { ?>
                <span class="text-gray-800 font-bold original-price">₱<?= $default_price ?></span>
            <?php } ?>
        </p>

        <button name="add_to_cart" class="mt-4 w-full size-10 rounded-full justify-center items-center inline-flex bg-orange-600 hover:bg-orange-400 text-white hover:scale-110 transition-all duration-500 focus:outline-none">
            Add to Cart
        </button>
      </div>

      <?php } ?>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.size-selector').forEach(select => {
        select.addEventListener('change', function () {
            let productName = this.closest('.group').querySelector('h3').innerText.trim(); // Get product name
            let selectedSize = this.value;

            if (selectedSize !== "") {
                fetch('./functions/fetch_price.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `product_name=${encodeURIComponent(productName)}&size=${encodeURIComponent(selectedSize)}`
                })
                .then(response => response.json())
                .then(data => {
                    console.log("Fetched Data:", data);

                    let priceDisplay = this.closest('.group').querySelector('.price-display');
                    let descriptionDisplay = this.closest('.group').querySelector('.description');

                    if (data.error) {
                        console.error("Error:", data.error);
                        descriptionDisplay.innerText = "Product not found!";
                        priceDisplay.innerHTML = `<span class="text-red-600 font-bold">₱0.00</span>`;
                        return;
                    }

                    descriptionDisplay.innerText = data.product_description || "No description available";

                    let originalPrice = parseFloat(data.product_price).toFixed(2);
                    let discountPrice = data.discount_price ? parseFloat(data.discount_price).toFixed(2) : null;

                    if (discountPrice > 0) {
                        priceDisplay.innerHTML = `<span class="line-through text-gray-500">₱${originalPrice}</span>
                                                  <span class="text-red-600 font-bold ml-2 my-5">₱${discountPrice}</span>`;
                    } else {
                        priceDisplay.innerHTML = `<span class="text-gray-800 font-bold">₱${originalPrice}</span>`;
                    }
                })
                .catch(error => console.error('Fetch Error:', error));
            }
        });
    });
});

</script>
