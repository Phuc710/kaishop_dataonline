<?php
/**
 * Edit Book Product
 * Full Edit: Name, Description, Label, Category, Image, Link, Price, Discount
 */

$exchange_rate = floatval($pdo->query("SELECT setting_value FROM settings WHERE setting_key='exchange_rate'")->fetchColumn() ?? 25000);
$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

$label_presets = [
    'HOT' => ['text' => '#ffffff', 'bg' => '#ef4444'],
    'SALE' => ['text' => '#ffffff', 'bg' => '#10b981'],
    'VIP' => ['text' => '#000000', 'bg' => '#f59e0b'],
    'NEW' => ['text' => '#ffffff', 'bg' => '#3b82f6'],
];

$errors = [];
$success = '';

// UPDATE HANDLER
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product'])) {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $label = trim($_POST['label'] ?? 'NORMAL');
    $label_text_color = trim($_POST['label_text_color'] ?? '#ffffff');
    $label_bg_color = trim($_POST['label_bg_color'] ?? '#8b5cf6');
    $category_id = trim($_POST['category_id'] ?? '');
    $label_id = !empty($_POST['label_id']) ? $_POST['label_id'] : null;
    $new_category = trim($_POST['new_category'] ?? '');
    $delivery_content = trim($_POST['delivery_content'] ?? '');
    $price_vnd = floatval($_POST['price_vnd'] ?? 0);
    $discount_percent = intval($_POST['discount_percent'] ?? 0);

    if (empty($name))
        $errors[] = 'Tên sách không được trống';
    if (empty($delivery_content))
        $errors[] = 'Link ebook không được trống';
    if ($price_vnd <= 0)
        $errors[] = 'Giá phải lớn hơn 0';

    // Handle new category
    if (!empty($new_category)) {
        $cat_id = 'cat_' . time() . '_' . rand(1000, 9999);
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $new_category)));
        $slug = trim($slug, '-') ?: 'cat-' . uniqid();

        $check = $pdo->prepare("SELECT id FROM categories WHERE slug = ?");
        $check->execute([$slug]);
        if ($check->fetch())
            $slug = $slug . '-' . time();

        $icon_text = trim($_POST['new_category_icon_text'] ?? '');
        $icon_value = $icon_text ?: '📚';
        $icon_type = 'emoji';

        // Check if image uploaded
        if (isset($_FILES['new_category_icon_image']) && $_FILES['new_category_icon_image']['error'] === UPLOAD_ERR_OK) {
            $allowed_icon = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
            $icon_ext = strtolower(pathinfo($_FILES['new_category_icon_image']['name'], PATHINFO_EXTENSION));

            if (in_array($icon_ext, $allowed_icon) && $_FILES['new_category_icon_image']['size'] <= 2 * 1024 * 1024) {
                $icon_filename = 'cat_' . uniqid() . '.' . $icon_ext;
                move_uploaded_file($_FILES['new_category_icon_image']['tmp_name'], __DIR__ . '/../../../assets/images/uploads/' . $icon_filename);
                $icon_value = $icon_filename;
                $icon_type = 'image';
            }
        }

        $pdo->prepare("INSERT INTO categories (id, name, slug, icon_value, icon_type) VALUES (?, ?, ?, ?, ?)")
            ->execute([$cat_id, $new_category, $slug, $icon_value, $icon_type]);
        $category_id = $cat_id;
    }

    // Handle image uploads (multi-image support)
    $image_name = $product['image'];
    $additional_images = [];

    // Process main image replacement
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

        if (in_array($ext, $allowed) && $_FILES['image']['size'] <= 10 * 1024 * 1024) {
            $image_name = uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], __DIR__ . '/../../../assets/images/uploads/' . $image_name);
        }
    }

    // Process additional images
    if (isset($_FILES['additional_images']) && is_array($_FILES['additional_images']['name'])) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $count = count($_FILES['additional_images']['name']);

        for ($i = 0; $i < $count; $i++) {
            if ($_FILES['additional_images']['error'][$i] === UPLOAD_ERR_OK) {
                $filename = $_FILES['additional_images']['name'][$i];
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

                if (in_array($ext, $allowed) && $_FILES['additional_images']['size'][$i] <= 10 * 1024 * 1024) {
                    $img_name = uniqid() . '_' . $i . '.' . $ext;
                    if (move_uploaded_file($_FILES['additional_images']['tmp_name'][$i], __DIR__ . '/../../../assets/images/uploads/' . $img_name)) {
                        if (empty($image_name)) {
                            $image_name = $img_name;
                        } else {
                            $additional_images[] = $img_name;
                        }
                    }
                }
            }
        }
    }

    // Merge with existing images to keep
    $images_json_input = $_POST['images_json'] ?? '[]';
    $existing_to_keep = json_decode($images_json_input, true) ?: [];
    $all_images = array_merge($existing_to_keep, $additional_images);
    $images_json = json_encode(array_values($all_images));

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $price_usd = round($price_vnd / $exchange_rate, 2);
            $discount_vnd = round($price_vnd * $discount_percent / 100);
            $discount_usd = round($price_usd * $discount_percent / 100, 2);
            $final_vnd = $price_vnd - $discount_vnd;
            $final_usd = $price_usd - $discount_usd;

            $pdo->prepare("
                UPDATE products SET
                name=?, description=?, price_vnd=?, price_usd=?,
                discount_percent=?, discount_amount_vnd=?, discount_amount_usd=?,
                final_price_vnd=?, final_price_usd=?, stock=9999, category_id=?,
                min_purchase=1, max_purchase=1,
                label=?, label_text_color=?, label_bg_color=?, image=?, images=?,
                delivery_content=?, label_id=?
                WHERE id=?
            ")->execute([
                        $name,
                        $description,
                        $price_vnd,
                        $price_usd,
                        $discount_percent,
                        $discount_vnd,
                        $discount_usd,
                        $final_vnd,
                        $final_usd,
                        $category_id,
                        $label,
                        $label_text_color,
                        $label_bg_color,
                        $image_name,
                        $images_json,
                        $delivery_content,
                        $label_id,
                        $product_id
                    ]);

            $pdo->commit();
            $success = 'Cập nhật sách thành công! ✅';

            // Reload
            $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch();

        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Lỗi: ' . $e->getMessage();
        }
    }
}
?>

<link rel="stylesheet" href="<?= asset('css/product_add.css') ?>?v=<?= time() ?>">

<div style="margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
    <button type="button" class="btn btn-secondary"
        onclick="window.location.href='?tab=product_manage&product_id=<?= $product_id ?>'"
        style="display: inline-flex; align-items: center; gap: 0.5rem;">
        <i class="fas fa-arrow-left"></i> Quay Lại
    </button>
    <button type="button" class="btn" onclick="deleteProduct('<?= $product_id ?>')"
        style="background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; color: #ef4444; display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.5rem; border-radius: 8px; cursor: pointer; transition: all 0.2s;">
        <i class="fas fa-trash"></i> Xóa Sản Phẩm
    </button>
</div>

<script>
    function deleteProduct(productId) {
        if (!confirm('⚠️ Bạn có chắc chắn muốn xóa sản phẩm này?\n\nHành động này sẽ xóa:\n- Thông tin sản phẩm\n- Link download\n- Tất cả hình ảnh liên quan\n\nKhông thể hoàn tác!')) {
            return;
        }

        if (window.showLoading) showLoading();

        fetch('<?= url('admin/api/delete-product.php') ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ product_id: productId })
        })
            .then(response => response.json())
            .then(data => {
                if (window.hideLoading) hideLoading();

                if (data.success) {
                    if (window.notify) {
                        notify.success('✅ Đã xóa sản phẩm thành công!');
                    }
                    setTimeout(() => {
                        window.location.href = '?tab=products';
                    }, 1000);
                } else {
                    if (window.notify) {
                        notify.error('❌ ' + (data.message || 'Lỗi khi xóa sản phẩm'));
                    } else {
                        alert('Lỗi: ' + (data.message || 'Không thể xóa sản phẩm'));
                    }
                }
            })
            .catch(error => {
                if (window.hideLoading) hideLoading();
                console.error('Error:', error);
                if (window.notify) {
                    notify.error('❌ Lỗi kết nối: ' + error.message);
                } else {
                    alert('Lỗi kết nối: ' + error.message);
                }
            });
    }
</script>

<form method="POST" enctype="multipart/form-data" id="productEditForm">
    <input type="hidden" name="update_product" value="1">
    <input type="hidden" name="product_type" value="book">

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
    <?php
    if (empty($_POST)) {
        $_POST['name'] = $product['name'];
        $_POST['description'] = $product['description'];
        $_POST['label'] = $product['label'];
        $_POST['label_text_color'] = $product['label_text_color'];
        $_POST['label_bg_color'] = $product['label_bg_color'];
        $_POST['category_id'] = $product['category_id'];
    }
    include __DIR__ . '/_common_fields.php';
    ?>

    <?php if ($product['image']): ?>
        <div class="form-section">
            <div class="form-section-header">
                <i class="fas fa-image"></i>
                <h3>Hình Ảnh Hiện Tại</h3>
            </div>
            <div style="text-align: center;">
                <img src="<?= asset('images/uploads/' . $product['image']) ?>" alt="Current"
                    style="max-width: 300px; border-radius: 12px; border: 2px solid #ffffff;">
                <p style="color: var(--text-muted); margin-top: 0.5rem;">
                    <i class="fas fa-info-circle"></i> Upload ảnh mới bên trên để thay đổi
                </p>
            </div>
        </div>
    <?php endif; ?>

    <!-- Book-Specific: Link Ebook -->
    <div class="form-section">
        <div class="form-section-header">
            <i class="fas fa-book"></i>
            <h3>Link Ebook</h3>
        </div>

        <div class="form-group">
            <label>
                <i class="fas fa-cloud-download-alt"></i>
                Link Ebook (PDF, EPUB, MOBI, etc.) <span style="color: #ef4444;">*</span>
            </label>
            <textarea name="delivery_content" class="form-control" rows="4"
                required><?= htmlspecialchars($product['delivery_content'] ?? '') ?></textarea>
            <small style="color: var(--text-muted); display: block; margin-top: 0.5rem;">
                <i class="fas fa-info-circle"></i>
                Link Google Drive, Mega, hoặc server hosting. Có thể nhập nhiều link (mỗi dòng 1 link).
            </small>
        </div>
    </div>

    <!-- Book-Specific: Giá -->
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
                    <input type="number" name="price_vnd" id="priceVnd" class="form-control" required
                        value="<?= $product['price_vnd'] ?>" oninput="calculatePrice()">
                    <small id="priceUsd" style="color: #10b981; font-weight: 600; display: block; margin-top: 0.5rem;">
                        USD: $<?= number_format($product['price_usd'], 2) ?>
                    </small>
                </div>

                <div class="form-group">
                    <label>
                        <i class="fas fa-percent"></i>
                        Giảm Giá (%) <span style="color: #ef4444;">*</span>
                    </label>
                    <input type="number" name="discount_percent" id="discountPercent" class="form-control" min="0"
                        max="100" value="<?= $product['discount_percent'] ?>" oninput="calculatePrice()">
                    <small id="finalPrice"
                        style="color: #10b981; font-weight: 600; display: block; margin-top: 0.5rem;">
                        Giá cuối: <?= number_format($product['final_price_vnd']) ?>đ
                    </small>
                </div>
            </div>
        </div>

        <div class="info-box"
            style="background: rgba(59, 130, 246, 0.1); border-left: 4px solid #3b82f6; padding: 1rem; border-radius: 8px; margin-top: 1rem;">
            <i class="fas fa-info-circle" style="color: #3b82f6;"></i>
            <strong style="color: #60a5fa;">Lưu ý:</strong>
            <span style="color: var(--text-secondary);">
                Sách điện tử không cần nhập tồn kho. Mỗi khách hàng mua sẽ nhận link ebook, không giới hạn số lượng.
            </span>
        </div>
    </div>

    <!-- Submit Button -->
    <div style="text-align: right; margin-top: 2rem;">
        <button type="submit" class="submit-btn">
            <i class="fas fa-save"></i>
            <span>Lưu Thay Đổi</span>
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

    // Calculate on load
    window.addEventListener('DOMContentLoaded', function () {
        calculatePrice();
    });
</script>