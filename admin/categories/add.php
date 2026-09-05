<?php

require_once '../includes/auth.php';


/*
|--------------------------------------------------------------------------
| Handle Form Submission
|--------------------------------------------------------------------------
*/

$errorMessage = '';

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
            LIMIT 1
        ");

        $stmt->execute([$slug]);

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

    $uploadedImage = null;
    $uploadedFilePath = null;

    if (
        $errorMessage === '' &&
        isset($_FILES['image']) &&
        $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE
    ) {

        $file = $_FILES['image'];

        if ($file['error'] !== UPLOAD_ERR_OK) {

            $errorMessage = 'There was an error uploading the image.';

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

            $mimeType = $finfo->file($file['tmp_name']);

            if (!isset($allowedMimeTypes[$mimeType])) {

                $errorMessage =
                    'Only JPG, PNG, and WEBP images are allowed.';

            } else {

                $extension = $allowedMimeTypes[$mimeType];

                $uploadDirectory =
                    __DIR__ . '/../../assets/images/categories/';

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

                $uploadedFilePath =
                    $uploadDirectory . $fileName;

                if (
                    move_uploaded_file(
                        $file['tmp_name'],
                        $uploadedFilePath
                    )
                ) {

                    $uploadedImage = $fileName;

                } else {

                    $errorMessage =
                        'Failed to save the uploaded image.';
                }
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Insert Category
    |--------------------------------------------------------------------------
    */

    if ($errorMessage === '') {

        try {

            $stmt = $pdo->prepare("
                INSERT INTO categories (
                    name,
                    slug,
                    description,
                    image,
                    status
                )
                VALUES (?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $name,
                $slug,
                $description !== '' ? $description : null,
                $uploadedImage,
                $status
            ]);


            $_SESSION['admin_category_success'] =
                'Category added successfully.';

            redirect(
                baseUrl('admin/categories/index.php')
            );

        } catch (PDOException $e) {

            if (
                $uploadedFilePath !== null &&
                file_exists($uploadedFilePath)
            ) {

                unlink($uploadedFilePath);
            }

            $errorMessage =
                'Unable to add the category. Please try again.';
        }
    }
}


$pageTitle = 'Add Category';

require_once '../includes/header.php';

?>


<div class="admin-page-header">

    <div>

        <h2>Add Category</h2>

        <p>
            Create a new jewelry category for your store.
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
                    Enter the basic details of your category.
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
                        value="<?= e($_POST['name'] ?? '') ?>"
                        placeholder="e.g. Rings"
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
                        value="<?= e($_POST['slug'] ?? '') ?>"
                        placeholder="e.g. rings"
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
                    ><?= e($_POST['description'] ?? '') ?></textarea>

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
                            <?= (
                                ($_POST['status'] ?? 'active') === 'active'
                            )
                                ? 'selected'
                                : ''
                            ?>
                        >
                            Active
                        </option>

                        <option
                            value="inactive"
                            <?= (
                                ($_POST['status'] ?? '') === 'inactive'
                            )
                                ? 'selected'
                                : ''
                            ?>
                        >
                            Inactive
                        </option>

                    </select>

                </div>


                <div class="admin-form-group">

                    <label for="image">
                        Category Image
                    </label>

                    <input
                        type="file"
                        id="image"
                        name="image"
                        accept=".jpg,.jpeg,.png,.webp"
                    >

                    <small>
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
                <i class="fa-solid fa-plus"></i>
                Add Category
            </button>

        </div>

    </form>

</div>


<?php require_once '../includes/footer.php'; ?>