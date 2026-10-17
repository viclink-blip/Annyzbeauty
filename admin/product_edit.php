<?php
$adminTitle = 'Edit Product';
require_once 'includes/admin_header.php';
$pdo = getDB();

$id = intval($_GET['id'] ?? 0);
$product = $pdo->prepare("SELECT * FROM products WHERE id=?");
$product->execute([$id]);
$product = $product->fetch();
if (!$product) { flashMessage('error','Product not found.'); redirect(SITE_URL.'/admin/products.php'); }

$cats   = $pdo->query("SELECT * FROM categories WHERE is_active=1 ORDER BY sort_order")->fetchAll();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'category_id'       => intval($_POST['category_id']  ?? 0),
        'name'              => trim($_POST['name']            ?? ''),
        'description'       => trim($_POST['description']     ?? ''),
        'short_description' => trim($_POST['short_description']??''),
        'price'             => floatval($_POST['price']       ?? 0),
        'sale_price'        => !empty($_POST['sale_price']) ? floatval($_POST['sale_price']) : null,
        'stock_quantity'    => intval($_POST['stock_quantity'] ?? 0),
        'sku'               => trim($_POST['sku']             ?? ''),
        'is_featured'       => isset($_POST['is_featured']) ? 1 : 0,
    ];

    if (!$data['category_id']) $errors[] = 'Category required.';
    if (!$data['name'])        $errors[] = 'Product name required.';
    if ($data['price'] <= 0)   $errors[] = 'Valid price required.';

    $imageName = $product['image'];
    if (!empty($_FILES['image']['name'])) {
        $file = $_FILES['image'];
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
            $errors[] = 'Invalid image format.';
        } elseif ($file['size'] > MAX_FILE_SIZE) {
            $errors[] = 'Image must be under 2MB.';
        } else {
            $imageName = 'product_' . time() . '.' . $ext;
        }
    }

    if (empty($errors)) {
        if (!empty($_FILES['image']['name']) && empty($errors)) {
            $uploadDir = UPLOAD_DIR;
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            move_uploaded_file($_FILES['image']['tmp_name'], UPLOAD_DIR . $imageName);
        }
        $pdo->prepare("UPDATE products SET category_id=?,name=?,description=?,short_description=?,price=?,sale_price=?,stock_quantity=?,sku=?,image=?,is_featured=? WHERE id=?")
            ->execute([$data['category_id'],$data['name'],$data['description'],$data['short_description'],$data['price'],$data['sale_price'],$data['stock_quantity'],$data['sku'],$imageName,$data['is_featured'],$id]);
        flashMessage('success','Product updated successfully!');
        redirect(SITE_URL.'/admin/products.php');
    }
} else {
    $data = $product;
}

$imgSrc = SITE_URL . '/assets/images/products/' . ($product['image'] ?? 'placeholder.jpg');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="fw-bold mb-0" style="font-family:var(--font-display);font-size:1.4rem;">Edit Product</h5>
    <div class="d-flex gap-2">
        <a href="<?= SITE_URL ?>/product.php?id=<?= $id ?>" class="btn btn-outline-secondary btn-sm" target="_blank"><i class="bi bi-eye me-1"></i>View</a>
        <a href="products.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
    </div>
</div>

<?php if ($errors): ?>
<div class="alert alert-danger mb-4"><ul class="mb-0 ps-3"><?php foreach ($errors as $e): ?><li><?= sanitize($e) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-xl mb-4">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3">Product Information</h6>
                <div class="mb-3">
                    <label class="form-label">Product Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="<?= sanitize($data['name']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Short Description</label>
                    <input type="text" name="short_description" class="form-control" maxlength="300" value="<?= sanitize($data['short_description'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Full Description</label>
                    <textarea name="description" class="form-control" rows="5"><?= sanitize($data['description'] ?? '') ?></textarea>
                </div>
            </div>
        </div>
        <div class="card border-0 shadow-sm rounded-xl">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3">Pricing & Inventory</h6>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Regular Price (KSh) <span class="text-danger">*</span></label>
                        <input type="number" name="price" class="form-control" step="0.01" min="0" value="<?= $data['price'] ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Sale Price (KSh)</label>
                        <input type="number" name="sale_price" class="form-control" step="0.01" min="0" value="<?= $data['sale_price'] ?? '' ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Stock Quantity</label>
                        <input type="number" name="stock_quantity" class="form-control" min="0" value="<?= $data['stock_quantity'] ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">SKU</label>
                        <input type="text" name="sku" class="form-control" value="<?= sanitize($data['sku'] ?? '') ?>">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-xl mb-4">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3">Category & Options</h6>
                <div class="mb-3">
                    <label class="form-label">Category <span class="text-danger">*</span></label>
                    <select name="category_id" class="form-select" required>
                        <?php foreach ($cats as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $data['category_id']==$c['id']?'selected':'' ?>><?= sanitize($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_featured" id="isFeatured" <?= $data['is_featured']?'checked':'' ?>>
                    <label class="form-check-label" for="isFeatured">Mark as Featured</label>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-xl">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3">Product Image</h6>
                <img id="imgPreview" src="<?= $imgSrc ?>"
                     style="width:100%;height:180px;object-fit:cover;border-radius:10px;border:1px solid var(--border);margin-bottom:10px;"
                     onerror="this.src='https://placehold.co/280x180/FFF0F7/E91E8C?text=No+Image'">
                <input type="file" name="image" class="form-control form-control-sm" accept="image/*" onchange="previewImg(this)">
                <div style="font-size:11px;color:var(--mid-grey);margin-top:6px;">Leave blank to keep current image.</div>
            </div>
        </div>

        <div class="mt-4">
            <button type="submit" class="btn btn-pink-lg w-100">
                <i class="bi bi-save me-1"></i>Save Changes
            </button>
            <a href="products.php" class="btn btn-outline-secondary w-100 mt-2">Cancel</a>
        </div>
    </div>
</div>
</form>

<script>
function previewImg(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => document.getElementById('imgPreview').src = e.target.result;
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php require_once 'includes/admin_footer.php'; ?>
