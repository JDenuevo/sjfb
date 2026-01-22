<div class="grid sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">

    <?php
    // fetch_products.php
    session_start();
    include '../conn.php';

    $search = isset($_GET['search']) ? $_GET['search'] : '';

    // Build query with search filter
    $query = "SELECT p.product_id, p.product_name, p.product_description, 
                pi.image_path, 
                v.variant_id, v.variant_name, v.variant_price, v.discount_price,
                v.unit_type, v.minimum_order, v.order_increment,
                c.category_name
            FROM products p
            LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
            LEFT JOIN product_variants v ON p.product_id = v.product_id
            LEFT JOIN product_categories c ON p.product_category = c.category_id
            WHERE v.stock_status = 'In Stock' 
                AND p.is_deleted = 0";

            // Add search condition if search term exists
            if (!empty($search)) {
                $query .= " AND (p.product_name LIKE ? 
                            OR p.product_description LIKE ? 
                            OR c.category_name LIKE ? 
                            OR v.variant_name LIKE ?)";
            }

            $query .= " GROUP BY p.product_id, v.variant_id ORDER BY p.created_at DESC";

            $stmt = $conn->prepare($query);

            if (!empty($search)) {
                $searchTerm = "%" . $search . "%";
                $stmt->bind_param("ssss", $searchTerm, $searchTerm, $searchTerm, $searchTerm);
            }

            $stmt->execute();
            $result = $stmt->get_result();

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
        ?>
        <div class="col-span-full text-center py-12">
            <p class="text-gray-500 text-lg mb-4"><?php echo $search ? 'No products found matching your search.' : 'No products available.' ?></p>
            <?php if ($search) { ?>
                <button onclick="window.location.href='index1.php'" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    View All Products
                </button>
            <?php } ?>
        </div>
        <?php
    }

    if (isset($stmt)) {
        $stmt->close();
    }
    $conn->close();
    ?>
</div>