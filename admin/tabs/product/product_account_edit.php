<?php
/**
 * Edit Account Product
 * Support: Single + Multi Variants + Stock Pool Management
 */

$exchange_rate = floatval($pdo->query("SELECT setting_value FROM settings WHERE setting_key='exchange_rate'")->fetchColumn() ?? 25000);
$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

// Check if has variants
$stmt = $pdo->prepare("SELECT * FROM product_variants WHERE product_id = ? ORDER BY sort_order ASC");
$stmt->execute([$product_id]);
$variants = $stmt->fetchAll();
$hasVariants = count($variants) > 0;

// Load stock pool
$stmt = $pdo->prepare("SELECT * FROM product_stock_pool WHERE product_id = ? AND is_used = 0 ORDER BY id ASC");
$stmt->execute([$product_id]);
$stockPool = $stmt->fetchAll();

$errors = [];
$success = '';

// ==================== UPDATE HANDLER ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product'])) {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $label = trim($_POST['label'] ?? '');
    $label_text_color = trim($_POST['label_text_color'] ?? '#ffffff');
    $label_bg_color = trim($_POST['label_bg_color'] ?? '#8b5cf6');
    $category_id = trim($_POST['category_id'] ?? '');
    $new_category = trim($_POST['new_category'] ?? '');
    $requires_customer_info = isset($_POST['requires_customer_info']) ? 1 : 0;
    $customer_info_label = trim($_POST['customer_info_label'] ?? '');
    $label_id = !empty($_POST['label_id']) ? $_POST['label_id'] : null;

    if (empty($name))
        $errors[] = 'Tên sản phẩm không được trống';

    // Handle new category
    if (!empty($new_category)) {
        $cat_id = 'cat_' . time() . '_' . rand(1000, 9999);
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $new_category)));

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

    // Handle image upload
    $image_name = $product['image'];
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

        if (in_array($ext, $allowed) && $_FILES['image']['size'] <= 10 * 1024 * 1024) {
            if ($product['image'] && file_exists(__DIR__ . '/../../../assets/images/uploads/' . $product['image'])) {
                @unlink(__DIR__ . '/../../../assets/images/uploads/' . $product['image']);
            }
            $image_name = uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], __DIR__ . '/../../../assets/images/uploads/' . $image_name);
        }
    }

    if (!$hasVariants) {
        // Single option update
        $price_vnd = floatval($_POST['price_vnd'] ?? 0);
        $discount_percent = intval($_POST['discount_percent'] ?? 0);
        $min_purchase = intval($_POST['min_purchase'] ?? 1);
        $max_purchase = intval($_POST['max_purchase'] ?? 999);

        // Calculate stock from pool
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM product_stock_pool WHERE product_id = ? AND variant_id IS NULL AND is_used = 0");
        $stmt->execute([$product_id]);
        $stock = $stmt->fetchColumn();

        if ($price_vnd <= 0)
            $errors[] = 'Giá phải lớn hơn 0';
        if ($max_purchase > $stock)
            $errors[] = "Max mua ($max_purchase) phải ≤ Tồn kho ($stock)";

        if (empty($errors)) {
            $price_usd = round($price_vnd / $exchange_rate, 2);
            $discount_amount_vnd = round($price_vnd * $discount_percent / 100);
            $discount_amount_usd = round($price_usd * $discount_percent / 100, 2);
            $final_price_vnd = $price_vnd - $discount_amount_vnd;
            $final_price_usd = $price_usd - $discount_amount_usd;

            $pdo->prepare("
                UPDATE products SET
                name=?, description=?, price_vnd=?, price_usd=?,
                discount_percent=?, discount_amount_vnd=?, discount_amount_usd=?,
                final_price_vnd=?, final_price_usd=?, stock=?, category_id=?,
                min_purchase=?, max_purchase=?, label=?, label_text_color=?, label_bg_color=?,
                image=?, requires_customer_info=?, customer_info_label=?, label_id=?
                WHERE id=?
            ")->execute([
                        $name,
                        $description,
                        $price_vnd,
                        $price_usd,
                        $discount_percent,
                        $discount_amount_vnd,
                        $discount_amount_usd,
                        $final_price_vnd,
                        $final_price_usd,
                        $stock,
                        $category_id,
                        $min_purchase,
                        $max_purchase,
                        $label,
                        $label_text_color,
                        $label_bg_color,
                        $image_name,
                        $requires_customer_info,
                        $customer_info_label,
                        $label_id,
                        $product_id
                    ]);

            $success = 'Cập nhật sản phẩm thành công! ✅';
        }
    } else {
        // Multi variants update
        $variant_ids = $_POST['variant_ids'] ?? [];
        $variant_names = $_POST['variant_names'] ?? [];
        $variant_prices = $_POST['variant_prices'] ?? [];
        $variant_discounts = $_POST['variant_discounts'] ?? [];
        $variant_mins = $_POST['variant_mins'] ?? [];
        $variant_maxs = $_POST['variant_maxs'] ?? [];

        if (empty($errors)) {
            $pdo->beginTransaction();

            try {
                // Update product main info
                $pdo->prepare("
                    UPDATE products SET
                    name=?, description=?, category_id=?,
                    label=?, label_text_color=?, label_bg_color=?,
                    image=?, requires_customer_info=?, customer_info_label=?, label_id=?
                    WHERE id=?
                ")->execute([
                            $name,
                            $description,
                            $category_id,
                            $label,
                            $label_text_color,
                            $label_bg_color,
                            $image_name,
                            $requires_customer_info,
                            $customer_info_label,
                            $label_id,
                            $product_id
                        ]);

                // Update each variant
                foreach ($variant_ids as $idx => $vid) {
                    $vname = trim($variant_names[$vid] ?? '');
                    $vprice = floatval($variant_prices[$vid] ?? 0);
                    $vdiscount = intval($variant_discounts[$vid] ?? 0);
                    $vmin = intval($variant_mins[$vid] ?? 1);
                    $vmax = intval($variant_maxs[$vid] ?? 999);

                    // Calculate stock from pool
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM product_stock_pool WHERE product_id = ? AND variant_id = ? AND is_used = 0");
                    $stmt->execute([$product_id, $vid]);
                    $vstock = $stmt->fetchColumn();

                    $vprice_usd = round($vprice / $exchange_rate, 2);
                    $vdiscount_vnd = round($vprice * $vdiscount / 100);
                    $vdiscount_usd = round($vprice_usd * $vdiscount / 100, 2);
                    $vfinal_vnd = $vprice - $vdiscount_vnd;
                    $vfinal_usd = $vprice_usd - $vdiscount_usd;

                    $pdo->prepare("
                        UPDATE product_variants SET
                        variant_name=?, price_vnd=?, price_usd=?,
                        discount_percent=?, discount_amount_vnd=?, discount_amount_usd=?,
                        final_price_vnd=?, final_price_usd=?, stock=?,
                        min_purchase=?, max_purchase=?
                        WHERE id=?
                    ")->execute([
                                $vname,
                                $vprice,
                                $vprice_usd,
                                $vdiscount,
                                $vdiscount_vnd,
                                $vdiscount_usd,
                                $vfinal_vnd,
                                $vfinal_usd,
                                $vstock,
                                $vmin,
                                $vmax,
                                $vid
                            ]);
                }

                $pdo->commit();
                $success = 'Cập nhật sản phẩm & variants thành công! ✅';

            } catch (Exception $e) {
                $pdo->rollBack();
                $errors[] = 'Lỗi: ' . $e->getMessage();
            }
        }
    }

    // Reload data after update
    if ($success) {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();

        $stmt = $pdo->prepare("SELECT * FROM product_variants WHERE product_id = ? ORDER BY sort_order ASC");
        $stmt->execute([$product_id]);
        $variants = $stmt->fetchAll();
        $hasVariants = count($variants) > 0;

        $stmt = $pdo->prepare("SELECT * FROM product_stock_pool WHERE product_id = ? AND is_used = 0 ORDER BY id ASC");
        $stmt->execute([$product_id]);
        $stockPool = $stmt->fetchAll();
    }
}

// Include UI
include __DIR__ . '/_account_edit_ui.php';
?>