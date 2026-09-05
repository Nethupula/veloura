<?php

require_once '../includes/auth.php';

$errorMessage = '';

$categoryId = (int) ($_GET['id'] ?? 0);

if ($categoryId <= 0) {
    redirect(baseUrl('admin/categories/index.php'));
}


/*
|--------------------------------------------------------------------------
| Get Category
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        id,
        name,
        slug,
        description,
        image,
        status
    FROM categories
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$categoryId]);

$category = $stmt->fetch();

if (!$category) {
    $_SESSION['admin_category_error'] =
        'Category not found.';

    redirect(baseUrl('admin/categories/index.php'));
}


/*
|--------------------------------------------------------------------------
| Handle Form Submission
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $status = $_POST['status'] ?? 'active';


    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if ($name === '') {

        $errorMessage = 'Category name is required.';

    } elseif ($slug === '') {

        $errorMessage = 'Category slug is required.';

    } elseif (!preg_match('/^[a-z0-9-]+$/', $slug)) {

        $errorMessage =
            'Slug can only contain lowercase letters, numbers, and hyphens.';

    } elseif (!in_array($status, ['active', 'inactive'], true)) {

        $errorMessage = 'Invalid category status.';

    }


    /*
    |--------------------------------------------------------------------------
    | Check Duplicate Slug
    |--------------------------------------------------------------------------
    */

    if ($errorMessage === '') {

        $stmt = $pdo->prepare("
            SELECT id
            FROM categories
            WHERE slug = ?
            AND id != ?
            LIMIT 1
        ");

        $stmt->execute([
            $slug,
            $categoryId
        ]);

        if ($stmt->fetch()) {

            $errorMessage =
                'A category with this slug already exists.';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Image Upload
    |--------------------------------------------------------------------------
    */

    $newImage = null;
    $newImagePath = null;

    if (
        $errorMessage === '' &&
        isset($_FILES['image']) &&
        $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE
    ) {

        $file = $_FILES['image'];

        if ($file['error'] !== UPLOAD_ERR_OK) {

            $errorMessage =
                'There was an error uploading the image.';

        } elseif ($file['size'] > 5 * 1024 * 1024) {

            $errorMessage =
                'Image size must be less than 5MB.';

        } else {

            $allowedMimeTypes = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp'
            ];

            $finfo = new finfo(FILEINFO_MIME_TYPE);

            $mimeType =
                $finfo->file($file['tmp_name']);

            if (!isset($allowedMimeTypes[$mimeType])) {

                $errorMessage =
                    'Only JPG, PNG, and WEBP images are allowed.';

            } else {

                $extension =
                    $allowedMimeTypes[$mimeType];

                $uploadDirectory =
                    __DIR__ .
                    '/../../assets/images/categories/';

                if (!is_dir($uploadDirectory)) {

                    mkdir(
                        $uploadDirectory,
                        0755,
                        true
                    );
                }

                $fileName =
                    'category_' .
                    time() .
                    '_' .
                    bin2hex(random_bytes(4)) .
                    '.' .
                    $extension;

                $newImagePath =
                    $uploadDirectory .
                    $fileName;

                if (
                    move_uploaded_file(
                        $file['tmp_name'],
                        $newImagePath
                    )
                ) {

                    $newImage = $fileName;

                } else {

                    $errorMessage =
                        'Failed to save the uploaded image.';
                }
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Update Category
    |--------------------------------------------------------------------------
    */

    if ($errorMessage === '') {

        try {

            if ($newImage !== null) {

                $stmt = $pdo->prepare("
                    UPDATE categories
                    SET
                        name = ?,
                        slug = ?,
                        description = ?,
                        image = ?,
                        status = ?
                    WHERE id = ?
                ");

                $stmt->execute([
                    $name,
                    $slug,
                    $description !== ''
                        ? $description
                        : null,
                    $newImage,
                    $status,
                    $categoryId
                ]);

            } else {

                $stmt = $pdo->prepare("
                    UPDATE categories
                    SET
                        name = ?,
                        slug = ?,
                        description = ?,
                        status = ?
                    WHERE id = ?
                ");

                $stmt->execute([
                    $name,
                    $slug,
                    $description !== ''
                        ? $description
                        : null,
                    $status,
                    $categoryId
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | Delete Old Image
            |--------------------------------------------------------------------------
            */

            if (
                $newImage !== null &&
                !empty($category['image'])
            ) {

                $oldImagePath =
                    __DIR__ .
                    '/../../assets/images/categories/' .
                    $category['image'];

                if (
                    file_exists($oldImagePath) &&
                    is_file($oldImagePath)
                ) {

                    unlink($oldImagePath);
                }
            }


            $_SESSION['admin_category_success'] =
                'Category updated successfully.';

            redirect(
                baseUrl('admin/categories/index.php')
            );

        } catch (PDOException $e) {

            /*
            |--------------------------------------------------------------------------
            | Remove Newly Uploaded Image if Database Update Fails
            |--------------------------------------------------------------------------
            */

            if (
                $newImagePath !== null &&
                file_exists($newImagePath)
            ) {

                unlink($newImagePath);
            }

            $errorMessage =
                'Unable to update the category. Please try again.';
        }
    }
}


/*
|--------------------------------------------------------------------------
| Display Values
|--------------------------------------------------------------------------
*/

$formName =
    $_POST['name'] ?? $category['name'];

$formSlug =
    $_POST['slug'] ?? $category['slug'];

$formDescription =
    $_POST['description'] ?? $category['description'];

$formStatus =
    $_POST['status'] ?? $category['status'];


$pageTitle = 'Edit Category';

require_once '../includes/header.php';

?>


<div class="admin-page-header">

    <div>

        <h2>Edit Category</h2>

        <p>
            Update the details of your jewelry category.
        </p>

    </div>

    <a
        href="<?= e(baseUrl('admin/categories/index.php')) ?>"
        class="admin-secondary-button"
    >
        <i class="fa-solid fa-arrow-left"></i>
        Back to Categories
    </a>

</div>


<?php if ($errorMessage !== ''): ?>

    <div class="admin-category-error">

        <i class="fa-solid fa-circle-exclamation"></i>

        <?= e($errorMessage) ?>

    </div>

<?php endif; ?>


<div class="admin-form-container">

    <form
        method="POST"
        enctype="multipart/form-data"
        class="admin-product-form"
    >

        <div class="admin-form-section">

            <div class="admin-form-section-title">

                <h3>Category Information</h3>

                <p>
                    Update the category information below.
                </p>

            </div>


            <div class="admin-form-grid">

                <div class="admin-form-group">

                    <label for="name">
                        Category Name
                        <span>*</span>
                    </label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="<?= e($formName) ?>"
                        required
                    >

                </div>


                <div class="admin-form-group">

                    <label for="slug">
                        Slug
                        <span>*</span>
                    </label>

                    <input
                        type="text"
                        id="slug"
                        name="slug"
                        value="<?= e($formSlug) ?>"
                        required
                    >

                    <small>
                        Use lowercase letters, numbers, and hyphens only.
                    </small>

                </div>


                <div class="admin-form-group admin-form-full">

                    <label for="description">
                        Description
                    </label>

                    <textarea
                        id="description"
                        name="description"
                        rows="5"
                        placeholder="Describe this jewelry category..."
                    ><?= e($formDescription) ?></textarea>

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
                            <?= $formStatus === 'active'
                                ? 'selected'
                                : ''
                            ?>
                        >
                            Active
                        </option>

                        <option
                            value="inactive"
                            <?= $formStatus === 'inactive'
                                ? 'selected'
                                : ''
                            ?>
                        >
                            Inactive
                        </option>

                    </select>

                </div>


                <div class="admin-form-group">

                    <label>
                        Current Image
                    </label>

                    <?php if (!empty($category['image'])): ?>

                        <div class="category-current-image">

                            <img
                                src="<?= e(
                                    baseUrl(
                                        'assets/images/categories/' .
                                        $category['image']
                                    )
                                ) ?>"
                                alt="<?= e($category['name']) ?>"
                            >

                        </div>

                    <?php else: ?>

                        <div class="category-no-image">
                            No image uploaded.
                        </div>

                    <?php endif; ?>

                </div>


                <div class="admin-form-group admin-form-full">

                    <label for="image">
                        Replace Category Image
                    </label>

                    <input
                        type="file"
                        id="image"
                        name="image"
                        accept=".jpg,.jpeg,.png,.webp"
                    >

                    <small>
                        Leave empty to keep the current image.
                        JPG, PNG, or WEBP. Maximum 5MB.
                    </small>

                </div>

            </div>

        </div>


        <div class="admin-form-actions">

            <a
                href="<?= e(baseUrl('admin/categories/index.php')) ?>"
                class="admin-secondary-button"
            >
                Cancel
            </a>

            <button
                type="submit"
                class="admin-primary-button"
            >
                <i class="fa-solid fa-floppy-disk"></i>
                Save Changes
            </button>

        </div>

    </form>

</div>


<?php require_once '../includes/footer.php'; ?>