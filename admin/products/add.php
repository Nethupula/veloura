<?php

$pageTitle = 'Add Product';

require_once '../includes/auth.php';

$errors = [];

$name = '';
$sku = '';
$categoryId = '';
$description = '';
$shortDescription = '';
$price = '';
$discountPrice = '';
$material = '';
$color = '';
$gender = 'women';
$featured = 0;
$status = 'active';
$stockQuantity = 0;

$variants = [];


/*
|--------------------------------------------------------------------------
| Get Categories
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT id, name
    FROM categories
    WHERE status = 'active'
    ORDER BY name ASC
");

$categories = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Handle Form Submission
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $sku = trim($_POST['sku'] ?? '');
    $categoryId = (int) ($_POST['category_id'] ?? 0);

    $description = trim($_POST['description'] ?? '');
    $shortDescription = trim($_POST['short_description'] ?? '');

    $price = trim($_POST['price'] ?? '');
    $discountPrice = trim($_POST['discount_price'] ?? '');

    $material = trim($_POST['material'] ?? '');
    $color = trim($_POST['color'] ?? '');

    $gender = $_POST['gender'] ?? 'women';

    $featured = isset($_POST['featured']) ? 1 : 0;

    $status = $_POST['status'] ?? 'active';

    $stockQuantity = (int) ($_POST['stock_quantity'] ?? 0);

    $hasVariants = isset($_POST['has_variants']);


    /*
    |--------------------------------------------------------------------------
    | Basic Validation
    |--------------------------------------------------------------------------
    */

    if ($name === '') {
        $errors[] = 'Product name is required.';
    }

    if ($sku === '') {
        $errors[] = 'SKU is required.';
    }

    if ($categoryId <= 0) {
        $errors[] = 'Please select a category.';
    }

    if ($price === '' || !is_numeric($price) || (float) $price < 0) {
        $errors[] = 'Please enter a valid regular price.';
    }

    if (
        $discountPrice !== '' &&
        (
            !is_numeric($discountPrice) ||
            (float) $discountPrice < 0
        )
    ) {
        $errors[] = 'Please enter a valid discount price.';
    }

    if (
        $discountPrice !== '' &&
        is_numeric($price) &&
        (float) $discountPrice >= (float) $price
    ) {
        $errors[] = 'Discount price must be lower than the regular price.';
    }

    if (!in_array($gender, ['women', 'men', 'unisex'], true)) {
        $errors[] = 'Invalid gender selected.';
    }

    if (!in_array($status, ['active', 'inactive', 'out_of_stock'], true)) {
        $errors[] = 'Invalid product status.';
    }


    /*
    |--------------------------------------------------------------------------
    | Validate Stock
    |--------------------------------------------------------------------------
    */

    if (!$hasVariants && $stockQuantity < 0) {
        $errors[] = 'Stock quantity cannot be negative.';
    }


    /*
    |--------------------------------------------------------------------------
    | Validate Variants
    |--------------------------------------------------------------------------
    */

    if ($hasVariants) {

        $variantNames = $_POST['variant_name'] ?? [];
        $variantSkus = $_POST['variant_sku'] ?? [];
        $variantPrices = $_POST['variant_price'] ?? [];
        $variantStocks = $_POST['variant_stock'] ?? [];
        $variantReorderLevels = $_POST['variant_reorder_level'] ?? [];

        foreach ($variantNames as $index => $variantName) {

            $variantName = trim($variantName);

            if ($variantName === '') {
                continue;
            }

            $variantSku = trim(
                $variantSkus[$index] ?? ''
            );

            $variantPrice = trim(
                $variantPrices[$index] ?? ''
            );

            $variantStock = (int) (
                $variantStocks[$index] ?? 0
            );

            $variantReorderLevel = (int) (
                $variantReorderLevels[$index] ?? 1
            );

            if ($variantSku === '') {

                $errors[] =
                    'Every variant must have a SKU.';

            }

            if (
                $variantPrice !== '' &&
                (
                    !is_numeric($variantPrice) ||
                    (float) $variantPrice < 0
                )
            ) {

                $errors[] =
                    'Variant prices must be valid.';

            }

            if ($variantStock < 0) {

                $errors[] =
                    'Variant stock cannot be negative.';

            }

            if ($variantReorderLevel < 0) {

                $errors[] =
                    'Variant reorder level cannot be negative.';

            }

            $variants[] = [
                'name' => $variantName,
                'sku' => $variantSku,
                'price' => $variantPrice,
                'stock' => $variantStock,
                'reorder_level' => $variantReorderLevel
            ];
        }

        if (empty($variants)) {

            $errors[] =
                'Please add at least one product variant.';

        }
    }


    /*
    |--------------------------------------------------------------------------
    | Validate Product SKU
    |--------------------------------------------------------------------------
    */

    if (empty($errors)) {

        $stmt = $pdo->prepare("
            SELECT id
            FROM products
            WHERE sku = ?
            LIMIT 1
        ");

        $stmt->execute([$sku]);

        if ($stmt->fetch()) {

            $errors[] =
                'A product with this SKU already exists.';

        }
    }


    /*
    |--------------------------------------------------------------------------
    | Create Product
    |--------------------------------------------------------------------------
    */

    if (empty($errors)) {

        $slug = strtolower(
            trim(
                preg_replace(
                    '/[^A-Za-z0-9-]+/',
                    '-',
                    $name
                ),
                '-'
            )
        );

        /*
        |--------------------------------------------------------------------------
        | Make slug unique
        |--------------------------------------------------------------------------
        */

        $baseSlug = $slug;
        $counter = 1;

        while (true) {

            $stmt = $pdo->prepare("
                SELECT id
                FROM products
                WHERE slug = ?
                LIMIT 1
            ");

            $stmt->execute([$slug]);

            if (!$stmt->fetch()) {
                break;
            }

            $counter++;

            $slug = $baseSlug . '-' . $counter;
        }


        /*
        |--------------------------------------------------------------------------
        | Product Image
        |--------------------------------------------------------------------------
        */

        $uploadedImagePath = null;
        $uploadedPhysicalPath = null;

        if (
            isset($_FILES['product_image']) &&
            $_FILES['product_image']['error'] !== UPLOAD_ERR_NO_FILE
        ) {

            $image = $_FILES['product_image'];

            if ($image['error'] !== UPLOAD_ERR_OK) {

                $errors[] =
                    'There was a problem uploading the product image.';

            } elseif ($image['size'] > 5 * 1024 * 1024) {

                $errors[] =
                    'Product image must be 5MB or smaller.';

            } else {

                $finfo = new finfo(FILEINFO_MIME_TYPE);

                $mimeType = $finfo->file(
                    $image['tmp_name']
                );

                $allowedTypes = [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/webp' => 'webp'
                ];

                if (!isset($allowedTypes[$mimeType])) {

                    $errors[] =
                        'Only JPG, PNG, and WEBP images are allowed.';

                } else {

                    $extension =
                        $allowedTypes[$mimeType];

                    $fileName =
                        'product_' .
                        bin2hex(random_bytes(8)) .
                        '.' .
                        $extension;

                    $uploadDirectory =
                        __DIR__ .
                        '/../../assets/images/products/';

                    if (!is_dir($uploadDirectory)) {

                        mkdir(
                            $uploadDirectory,
                            0755,
                            true
                        );
                    }

                    $uploadedPhysicalPath =
                        $uploadDirectory .
                        $fileName;

                    $uploadedImagePath =
                        'products/' .
                        $fileName;

                    if (
                        !move_uploaded_file(
                            $image['tmp_name'],
                            $uploadedPhysicalPath
                        )
                    ) {

                        $errors[] =
                            'Failed to save the product image.';

                        $uploadedPhysicalPath = null;
                        $uploadedImagePath = null;
                    }
                }
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Database Transaction
        |--------------------------------------------------------------------------
        */

        if (empty($errors)) {

            try {

                $pdo->beginTransaction();


                /*
                |--------------------------------------------------------------------------
                | Calculate Initial Stock
                |--------------------------------------------------------------------------
                */

                if ($hasVariants) {

                    $stockQuantity = 0;

                    foreach ($variants as $variant) {

                        $stockQuantity +=
                            $variant['stock'];
                    }
                }


                /*
                |--------------------------------------------------------------------------
                | Insert Product
                |--------------------------------------------------------------------------
                */

                $stmt = $pdo->prepare("
                    INSERT INTO products (
                        category_id,
                        name,
                        slug,
                        sku,
                        description,
                        short_description,
                        price,
                        discount_price,
                        material,
                        color,
                        gender,
                        stock_quantity,
                        featured,
                        status
                    )
                    VALUES (
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                    )
                ");

                $stmt->execute([
                    $categoryId,
                    $name,
                    $slug,
                    $sku,
                    $description !== ''
                        ? $description
                        : null,
                    $shortDescription !== ''
                        ? $shortDescription
                        : null,
                    (float) $price,
                    $discountPrice !== ''
                        ? (float) $discountPrice
                        : null,
                    $material !== ''
                        ? $material
                        : null,
                    $color !== ''
                        ? $color
                        : null,
                    $gender,
                    $stockQuantity,
                    $featured,
                    $status
                ]);

                $productId =
                    (int) $pdo->lastInsertId();


                /*
                |--------------------------------------------------------------------------
                | Product Image
                |--------------------------------------------------------------------------
                */

                if ($uploadedImagePath !== null) {

                    $stmt = $pdo->prepare("
                        INSERT INTO product_images (
                            product_id,
                            image_path,
                            alt_text,
                            is_primary,
                            sort_order
                        )
                        VALUES (?, ?, ?, 1, 1)
                    ");

                    $stmt->execute([
                        $productId,
                        $uploadedImagePath,
                        $name
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Variants + Inventory
                |--------------------------------------------------------------------------
                */

                if ($hasVariants) {

                    $variantInsert = $pdo->prepare("
                        INSERT INTO product_variants (
                            product_id,
                            variant_name,
                            sku,
                            price,
                            stock_quantity,
                            status
                        )
                        VALUES (?, ?, ?, ?, ?, 'active')
                    ");

                    $inventoryInsert = $pdo->prepare("
                        INSERT INTO inventory (
                            product_id,
                            variant_id,
                            quantity,
                            reserved_quantity,
                            reorder_level
                        )
                        VALUES (?, ?, ?, 0, ?)
                    ");

                    foreach ($variants as $variant) {

                        $variantPrice =
                            $variant['price'] !== ''
                                ? (float) $variant['price']
                                : null;

                        $variantInsert->execute([
                            $productId,
                            $variant['name'],
                            $variant['sku'],
                            $variantPrice,
                            $variant['stock']
                        ]);

                        $variantId =
                            (int) $pdo->lastInsertId();

                        $inventoryInsert->execute([
                            $productId,
                            $variantId,
                            $variant['stock'],
                            $variant['reorder_level']
                        ]);
                    }

                } else {

                    /*
                    |--------------------------------------------------------------------------
                    | Product-level Inventory
                    |--------------------------------------------------------------------------
                    */

                    $stmt = $pdo->prepare("
                        INSERT INTO inventory (
                            product_id,
                            variant_id,
                            quantity,
                            reserved_quantity,
                            reorder_level
                        )
                        VALUES (?, NULL, ?, 0, 1)
                    ");

                    $stmt->execute([
                        $productId,
                        $stockQuantity
                    ]);
                }


                $pdo->commit();


                redirect(
                    baseUrl('admin/products/index.php')
                );

            } catch (Throwable $e) {

                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                if (
                    $uploadedPhysicalPath !== null &&
                    file_exists($uploadedPhysicalPath)
                ) {
                    unlink($uploadedPhysicalPath);
                }

                $errors[] =
                    'Unable to create the product. Please try again.';
            }
        }
    }
}

?>
<?php require_once '../includes/header.php'; ?>

<div class="admin-page-header">

    <div>

        <h2>Add Product</h2>

        <p>
            Add a new piece to your Veloura collection.
        </p>

    </div>

    <a
        href="<?= e(baseUrl('admin/products/index.php')) ?>"
        class="admin-secondary-button"
    >
        <i class="fa-solid fa-arrow-left"></i>
        Back to Products
    </a>

</div>


<?php if (!empty($errors)): ?>

    <div class="admin-form-errors">

        <?php foreach ($errors as $error): ?>

            <div class="admin-form-error">

                <i class="fa-solid fa-circle-exclamation"></i>

                <?= e($error) ?>

            </div>

        <?php endforeach; ?>

    </div>

<?php endif; ?>


<form
    method="POST"
    enctype="multipart/form-data"
    class="admin-product-form"
>


    <!-- =====================================================
         BASIC INFORMATION
         ===================================================== -->

    <div class="admin-form-card">

        <div class="admin-form-card-header">

            <h3>Basic Information</h3>

            <p>
                Enter the main details of your product.
            </p>

        </div>


        <div class="admin-form-card-body">

            <div class="admin-form-grid">

                <div class="admin-form-group full">

                    <label for="name">
                        Product Name
                    </label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="<?= e($name) ?>"
                        placeholder="e.g. Elegant Gold Necklace"
                        required
                    >

                </div>


                <div class="admin-form-group">

                    <label for="sku">
                        SKU
                    </label>

                    <input
                        type="text"
                        id="sku"
                        name="sku"
                        value="<?= e($sku) ?>"
                        placeholder="VEL-NCK-001"
                        required
                    >

                </div>


                <div class="admin-form-group">

                    <label for="category_id">
                        Category
                    </label>

                    <select
                        id="category_id"
                        name="category_id"
                        required
                    >

                        <option value="">
                            Select Category
                        </option>

                        <?php foreach ($categories as $category): ?>

                            <option
                                value="<?= e($category['id']) ?>"
                                <?= (int) $categoryId ===
                                    (int) $category['id']
                                    ? 'selected'
                                    : '' ?>
                            >
                                <?= e($category['name']) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="admin-form-group full">

                    <label for="short_description">
                        Short Description
                    </label>

                    <input
                        type="text"
                        id="short_description"
                        name="short_description"
                        value="<?= e($shortDescription) ?>"
                        maxlength="500"
                        placeholder="A short description shown on product cards."
                    >

                </div>


                <div class="admin-form-group full">

                    <label for="description">
                        Description
                    </label>

                    <textarea
                        id="description"
                        name="description"
                        rows="6"
                        placeholder="Describe the product in detail..."
                    ><?= e($description) ?></textarea>

                </div>

            </div>

        </div>

    </div>


    <!-- =====================================================
         PRICING
         ===================================================== -->

    <div class="admin-form-card">

        <div class="admin-form-card-header">

            <h3>Pricing & Details</h3>

            <p>
                Set pricing and product characteristics.
            </p>

        </div>


        <div class="admin-form-card-body">

            <div class="admin-form-grid">

                <div class="admin-form-group">

                    <label for="price">
                        Regular Price (Rs.)
                    </label>

                    <input
                        type="number"
                        id="price"
                        name="price"
                        value="<?= e($price) ?>"
                        min="0"
                        step="0.01"
                        placeholder="8500.00"
                        required
                    >

                </div>


                <div class="admin-form-group">

                    <label for="discount_price">
                        Discount Price (Rs.)
                    </label>

                    <input
                        type="number"
                        id="discount_price"
                        name="discount_price"
                        value="<?= e($discountPrice) ?>"
                        min="0"
                        step="0.01"
                        placeholder="7500.00"
                    >

                </div>


                <div class="admin-form-group">

                    <label for="material">
                        Material
                    </label>

                    <input
                        type="text"
                        id="material"
                        name="material"
                        value="<?= e($material) ?>"
                        placeholder="Gold Plated"
                    >

                </div>


                <div class="admin-form-group">

                    <label for="color">
                        Color
                    </label>

                    <input
                        type="text"
                        id="color"
                        name="color"
                        value="<?= e($color) ?>"
                        placeholder="Gold"
                    >

                </div>


                <div class="admin-form-group">

                    <label for="gender">
                        Gender
                    </label>

                    <select
                        id="gender"
                        name="gender"
                    >

                        <option
                            value="women"
                            <?= $gender === 'women'
                                ? 'selected'
                                : '' ?>
                        >
                            Women
                        </option>

                        <option
                            value="men"
                            <?= $gender === 'men'
                                ? 'selected'
                                : '' ?>
                        >
                            Men
                        </option>

                        <option
                            value="unisex"
                            <?= $gender === 'unisex'
                                ? 'selected'
                                : '' ?>
                        >
                            Unisex
                        </option>

                    </select>

                </div>


                <div class="admin-form-group">

                    <label for="status">
                        Status
                    </label>

                    <select
                        id="status"
                        name="status"
                    >

                        <option
                            value="active"
                            <?= $status === 'active'
                                ? 'selected'
                                : '' ?>
                        >
                            Active
                        </option>

                        <option
                            value="inactive"
                            <?= $status === 'inactive'
                                ? 'selected'
                                : '' ?>
                        >
                            Inactive
                        </option>

                        <option
                            value="out_of_stock"
                            <?= $status === 'out_of_stock'
                                ? 'selected'
                                : '' ?>
                        >
                            Out of Stock
                        </option>

                    </select>

                </div>

            </div>

        </div>

    </div>


    <!-- =====================================================
         IMAGE
         ===================================================== -->

    <div class="admin-form-card">

        <div class="admin-form-card-header">

            <h3>Product Image</h3>

            <p>
                Upload the main image for this product.
            </p>

        </div>


        <div class="admin-form-card-body">

            <div class="admin-form-group">

                <label for="product_image">
                    Main Product Image
                </label>

                <input
                    type="file"
                    id="product_image"
                    name="product_image"
                    accept=".jpg,.jpeg,.png,.webp"
                >

                <small class="admin-form-help">
                    JPG, PNG or WEBP. Maximum 5MB.
                </small>

            </div>

        </div>

    </div>


    <!-- =====================================================
         INVENTORY
         ===================================================== -->

    <div class="admin-form-card">

        <div class="admin-form-card-header">

            <h3>Inventory</h3>

            <p>
                Choose whether this product uses variants.
            </p>

        </div>


        <div class="admin-form-card-body">

            <label class="admin-checkbox-row">

                <input
                    type="checkbox"
                    name="has_variants"
                    id="hasVariants"
                    value="1"
                >

                <span>
                    This product has variants
                    (e.g. Size 7, Size 8, Gold, Silver)
                </span>

            </label>


            <div
                id="simpleStockSection"
                class="simple-stock-section"
            >

                <div class="admin-form-group">

                    <label for="stock_quantity">
                        Stock Quantity
                    </label>

                    <input
                        type="number"
                        id="stock_quantity"
                        name="stock_quantity"
                        value="<?= e($stockQuantity) ?>"
                        min="0"
                        step="1"
                    >

                    <small class="admin-form-help">
                        Used for products without variants.
                    </small>

                </div>

            </div>


            <div
                id="variantsSection"
                class="variants-section"
                style="display: none;"
            >

                <div class="variants-header">

                    <div>

                        <h4>Product Variants</h4>

                        <p>
                            Add each variant and its inventory.
                        </p>

                    </div>

                    <button
                        type="button"
                        id="addVariantButton"
                        class="admin-small-button"
                    >
                        <i class="fa-solid fa-plus"></i>
                        Add Variant
                    </button>

                </div>


                <div id="variantsContainer">

                    <!-- JavaScript inserts variants here -->

                </div>

            </div>

        </div>

    </div>


    <!-- =====================================================
         OPTIONS
         ===================================================== -->

    <div class="admin-form-card">

        <div class="admin-form-card-header">

            <h3>Store Options</h3>

            <p>
                Control how this product appears in your store.
            </p>

        </div>


        <div class="admin-form-card-body">

            <label class="admin-checkbox-row">

                <input
                    type="checkbox"
                    name="featured"
                    value="1"
                    <?= $featured
                        ? 'checked'
                        : '' ?>
                >

                <span>
                    Show this product as a featured product.
                </span>

            </label>

        </div>

    </div>


    <!-- =====================================================
         SUBMIT
         ===================================================== -->

    <div class="admin-form-actions">

        <a
            href="<?= e(baseUrl('admin/products/index.php')) ?>"
            class="admin-cancel-button"
        >
            Cancel
        </a>

        <button
            type="submit"
            class="admin-primary-button"
        >
            <i class="fa-solid fa-check"></i>
            Create Product
        </button>

    </div>

</form>


<script>

document.addEventListener('DOMContentLoaded', function () {

    const hasVariants =
        document.getElementById('hasVariants');

    const simpleStockSection =
        document.getElementById('simpleStockSection');

    const variantsSection =
        document.getElementById('variantsSection');

    const addVariantButton =
        document.getElementById('addVariantButton');

    const variantsContainer =
        document.getElementById('variantsContainer');


    /*
    |--------------------------------------------------------------------------
    | Toggle Variant Mode
    |--------------------------------------------------------------------------
    */

    function updateVariantMode() {

        if (hasVariants.checked) {

            simpleStockSection.style.display = 'none';

            variantsSection.style.display = 'block';

            if (
                variantsContainer.children.length === 0
            ) {
                addVariant();
            }

        } else {

            simpleStockSection.style.display = 'block';

            variantsSection.style.display = 'none';

        }

    }


    /*
    |--------------------------------------------------------------------------
    | Add Variant
    |--------------------------------------------------------------------------
    */

    function addVariant() {

        const row =
            document.createElement('div');

        row.className = 'variant-row';

        row.innerHTML = `

            <div class="variant-field">

                <label>Variant Name</label>

                <input
                    type="text"
                    name="variant_name[]"
                    placeholder="Size 7"
                >

            </div>


            <div class="variant-field">

                <label>SKU</label>

                <input
                    type="text"
                    name="variant_sku[]"
                    placeholder="VEL-RNG-001-S7"
                >

            </div>


            <div class="variant-field">

                <label>Price</label>

                <input
                    type="number"
                    name="variant_price[]"
                    min="0"
                    step="0.01"
                    placeholder="7500.00"
                >

            </div>


            <div class="variant-field">

                <label>Stock</label>

                <input
                    type="number"
                    name="variant_stock[]"
                    min="0"
                    step="1"
                    value="0"
                >

            </div>


            <div class="variant-field">

                <label>Reorder Level</label>

                <input
                    type="number"
                    name="variant_reorder_level[]"
                    min="0"
                    step="1"
                    value="1"
                >

            </div>


            <button
                type="button"
                class="remove-variant"
                title="Remove Variant"
            >
                <i class="fa-solid fa-xmark"></i>
            </button>

        `;

        variantsContainer.appendChild(row);


        const removeButton =
            row.querySelector('.remove-variant');

        removeButton.addEventListener(
            'click',
            function () {
                row.remove();
            }
        );

    }


    /*
    |--------------------------------------------------------------------------
    | Events
    |--------------------------------------------------------------------------
    */

    hasVariants.addEventListener(
        'change',
        updateVariantMode
    );

    addVariantButton.addEventListener(
        'click',
        addVariant
    );

});

</script>


<?php require_once '../includes/footer.php'; ?>