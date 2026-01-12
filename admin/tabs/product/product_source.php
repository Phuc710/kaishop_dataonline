<?php
$errors = [];
$success = '';
$exchange_rate = floatval($pdo->query("SELECT setting_value FROM settings WHERE setting_key='exchange_rate'")->fetchColumn() ?? 25000);
$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

$label_presets = [
    'HOT' => ['text' => '#ffffff', 'bg' => '#ef4444'],
    'SALE' => ['text' => '#ffffff', 'bg' => '#10b981'],
    'VIP' => ['text' => '#000000', 'bg' => '#f59e0b'],
    'NEW' => ['text' => '#ffffff', 'bg' => '#3b82f6'],
];

// ==================== FORM PROCESSING ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Common fields
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $label = trim($_POST['label'] ?? 'NORMAL');
    $label_text_color = trim($_POST['label_text_color'] ?? '#ffffff');
    $label_bg_color = trim($_POST['label_bg_color'] ?? '#8b5cf6');
    $category_id = trim($_POST['category_id'] ?? '');
    $label_id = !empty($_POST['label_id']) ? $_POST['label_id'] : null;
    $new_category = trim($_POST['new_category'] ?? '');

    // Source-specific
    $price_vnd = floatval($_POST['price_vnd'] ?? 0);
    $discount_percent = intval($_POST['discount_percent'] ?? 0);
    $delivery_content = trim($_POST['delivery_content'] ?? '');

    // Validation
    if (empty($name))
        $errors[] = 'Tên sản phẩm không được trống';
    if ($price_vnd <= 0)
        $errors[] = 'Giá phải lớn hơn 0';
    if (empty($delivery_content))
        $errors[] = 'Link download không được trống';

    // Handle new category
    if (!empty($new_category)) {
        $cat_id = generateSnowflakeId();
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $new_category)));
        $slug = trim($slug, '-') ?: 'cat-' . uniqid();

        $check = $pdo->prepare("SELECT id FROM categories WHERE slug = ?");
        $check->execute([$slug]);
        if ($check->fetch())
            $slug = $slug . '-' . time();

        // Handle category icon - image or emoji
        $icon_value = trim($_POST['new_category_icon_text'] ?? '📦');
        $icon_type = 'emoji';

        // Check if image was uploaded
        if (!empty($_FILES['new_category_icon_image']['name']) && $_FILES['new_category_icon_image']['error'] === UPLOAD_ERR_OK) {
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
            $icon_ext = strtolower(pathinfo($_FILES['new_category_icon_image']['name'], PATHINFO_EXTENSION));

            if (in_array($icon_ext, $allowed_ext) && $_FILES['new_category_icon_image']['size'] <= 2 * 1024 * 1024) {
                $icon_filename = 'cat_' . uniqid() . '.' . $icon_ext;
                $icon_upload_path = __DIR__ . '/../../../assets/images/uploads/' . $icon_filename;

                if (move_uploaded_file($_FILES['new_category_icon_image']['tmp_name'], $icon_upload_path)) {
                    $icon_value = $icon_filename;
                    $icon_type = 'image';
                }
            }
        }

        $pdo->prepare("INSERT INTO categories (id, name, slug, icon_value, icon_type) VALUES (?, ?, ?, ?, ?)")
            ->execute([$cat_id, $new_category, $slug, $icon_value, $icon_type]);
        $category_id = $cat_id;
    }

    // Insert if no errors
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $product_id = generateSnowflakeId();
            $product_slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
            $product_slug = trim($product_slug, '-') ?: 'p-' . uniqid();

            $check = $pdo->prepare("SELECT id FROM products WHERE slug = ?");
            $check->execute([$product_slug]);
            if ($check->fetch())
                $product_slug = $product_slug . '-' . time();

            // Handle image upload
            $image_name = '';
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp'];
                $filename = $_FILES['image']['name'];
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

                if (in_array($ext, $allowed) && $_FILES['image']['size'] <= 10 * 1024 * 1024) {
                    $image_name = uniqid() . '.' . $ext;
                    move_uploaded_file($_FILES['image']['tmp_name'], __DIR__ . '/../../../assets/images/uploads/' . $image_name);
                }
            }

            $price_usd = round($price_vnd / $exchange_rate, 2);
            $discount_amount_vnd = round($price_vnd * $discount_percent / 100);
            $discount_amount_usd = round($price_usd * $discount_percent / 100, 2);
            $final_price_vnd = $price_vnd - $discount_amount_vnd;
            $final_price_usd = $price_usd - $discount_amount_usd;

            $pdo->prepare("
                INSERT INTO products (
                    id, name, slug, description, price_vnd, price_usd,
                    discount_percent, discount_amount_vnd, discount_amount_usd,
                    final_price_vnd, final_price_usd, stock, category_id, label_id,
                    min_purchase, max_purchase, label, label_text_color, label_bg_color, image,
                    delivery_content, product_type
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ")->execute([
                        $product_id,
                        $name,
                        $product_slug,
                        $description,
                        $price_vnd,
                        $price_usd,
                        $discount_percent,
                        $discount_amount_vnd,
                        $discount_amount_usd,
                        $final_price_vnd,
                        $final_price_usd,
                        9999,
                        $category_id,
                        $label_id,
                        1,
                        1,
                        $label,
                        $label_text_color,
                        $label_bg_color,
                        $image_name,
                        $delivery_content,
                        'source'
                    ]);

            $pdo->commit();

            // Redirect về trang quản lý sản phẩm
            header("Location: ?tab=products&success=1&type=source");
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Lỗi: ' . $e->getMessage();
        }
    }
}
?>

<link rel="stylesheet" href="<?= asset('css/product_add.css') ?>?v=<?= time() ?>">

<form method="POST" enctype="multipart/form-data" id="productForm">
    <input type="hidden" name="product_type" value="source">

    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <div>
                <?php foreach ($errors as $error): ?>
                    <div><?= htmlspecialchars($error) ?></div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <div><?= htmlspecialchars($success) ?></div>
        </div>
    <?php endif; ?>

    <!-- Common Fields -->
    <?php include __DIR__ . '/_common_fields.php'; ?>

    <!-- Source-Specific: Link Download -->
    <div class="form-section">
        <div class="form-section-header">
            <i class="fas fa-link"></i>
            <h3>Link Download</h3>
        </div>

        <div class="form-group">
            <label>
                <i class="fas fa-cloud-download-alt"></i>
                Link Download (Google Drive, MediaFire, etc.) <span style="color: #ef4444;">*</span>
            </label>
            <textarea name="delivery_content" class="form-control" rows="4" required
                placeholder="VD: https://drive.google.com/file/d/...&#10;Hoặc nhiều link, mỗi link 1 dòng"><?= htmlspecialchars($_POST['delivery_content'] ?? '') ?></textarea>
            <small style="color: var(--text-muted); display: block; margin-top: 0.5rem;">
                <i class="fas fa-info-circle"></i>
                Tất cả khách hàng mua sẽ nhận cùng link này. Có thể nhập nhiều link (mỗi dòng 1 link).
            </small>
        </div>
    </div>

    <!-- Source-Specific: Giá -->
    <div class="form-section">
        <div class="form-section-header">
            <i class="fas fa-dollar-sign"></i>
            <h3>Giá & Giảm Giá</h3>
        </div>

        <div class="pricing-box">
            <div class="form-grid-2">
                <div class="form-group">
                    <label>
                        <i class="fas fa-money-bill-wave"></i>
                        Giá Gốc (VND) <span style="color: #ef4444;">*</span>
                    </label>
                    <input type="number" name="price_vnd" id="priceVnd" class="form-control" required min="1000"
                        step="1000" placeholder="100000" oninput="calculatePrice()"
                        value="<?= htmlspecialchars($_POST['price_vnd'] ?? '') ?>">
                    <small id="priceUsd" style="color: #10b981; font-weight: 600; display: block; margin-top: 0.5rem;">
                        USD: $0.00
                    </small>
                </div>

                <div class="form-group">
                    <label>
                        <i class="fas fa-percent"></i>
                        Giảm Giá (%) <span style="color: #ef4444;">*</span>
                    </label>
                    <input type="number" name="discount_percent" id="discountPercent" class="form-control" min="0"
                        max="100" value="<?= htmlspecialchars($_POST['discount_percent'] ?? '0') ?>"
                        oninput="calculatePrice()">
                    <small id="finalPrice"
                        style="color: #10b981; font-weight: 600; display: block; margin-top: 0.5rem;">
                        Giá cuối: 0đ
                    </small>
                </div>
            </div>
        </div>

        <div class="info-box"
            style="background: rgba(59, 130, 246, 0.1); border-left: 4px solid #3b82f6; padding: 1rem; border-radius: 8px; margin-top: 1rem;">
            <i class="fas fa-info-circle" style="color: #3b82f6;"></i>
            <strong style="color: #60a5fa;">Lưu ý:</strong>
            <span style="color: var(--text-secondary);">
                Source Code không cần nhập tồn kho. Mỗi khách hàng mua sẽ nhận link download, không giới hạn số lượng.
            </span>
        </div>
    </div>

    <!-- Submit Button -->
    <div style="text-align: right; margin-top: 2rem;">
        <button type="submit" class="submit-btn">
            <i class="fas fa-plus-circle"></i>
            <span>Thêm Sản Phẩm</span>
        </button>
    </div>
</form>

<script>
    const exchangeRate = <?= $exchange_rate ?>;

    function calculatePrice() {
        const priceVnd = parseFloat(document.getElementById('priceVnd').value) || 0;
        const discount = parseFloat(document.getElementById('discountPercent').value) || 0;

        const priceUsd = (priceVnd / exchangeRate).toFixed(2);
        const finalVnd = Math.round(priceVnd * (1 - discount / 100));

        document.getElementById('priceUsd').textContent = `USD: $${priceUsd}`;
        document.getElementById('finalPrice').textContent = `Giá cuối: ${finalVnd.toLocaleString('vi-VN')}đ`;
    }
</script>