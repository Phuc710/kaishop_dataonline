<?php
/**
 * Product Account - Tài Khoản Game/Service
 * Supports: Single product OR Multiple variants
 */

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
    $new_category = trim($_POST['new_category'] ?? '');
    $requires_customer_info = isset($_POST['requires_customer_info']) ? 1 : 0;
    $customer_info_label = trim($_POST['customer_info_label'] ?? '');
    $option_mode = $_POST['option_mode'] ?? 'one'; // 'one' or 'multi'
    $label_id = !empty($_POST['label_id']) ? $_POST['label_id'] : null;

    // Validation
    if (empty($name))
        $errors[] = 'Tên sản phẩm không được trống';

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

    if ($option_mode === 'one') {
        // Single option processing
        $price_vnd = floatval($_POST['price_vnd'] ?? 0);
        $discount_percent = intval($_POST['discount_percent'] ?? 0);
        $stock = intval($_POST['stock'] ?? 0);
        $min_purchase = intval($_POST['min_purchase'] ?? 1);
        $max_purchase = intval($_POST['max_purchase'] ?? 2);

        if ($price_vnd <= 0)
            $errors[] = 'Giá phải lớn hơn 0';
        if ($stock < 0)
            $errors[] = 'Tồn kho không hợp lệ';
        if ($max_purchase > $stock)
            $errors[] = "Max mua ($max_purchase) phải ≤ Tồn kho ($stock)";

    } else {
        // Multi variants processing
        $variants = [];
        $variant_names = $_POST['variant_name'] ?? [];
        $variant_prices = $_POST['variant_price'] ?? [];
        $variant_discounts = $_POST['variant_discount'] ?? [];
        $variant_stocks = $_POST['variant_stock'] ?? [];
        $variant_mins = $_POST['variant_min'] ?? [];
        $variant_maxs = $_POST['variant_max'] ?? [];
        $variant_requires_customer_info = $_POST['variant_requires_customer_info'] ?? [];
        $variant_customer_info_label = $_POST['variant_customer_info_label'] ?? [];

        if (count($variant_names) < 2) {
            $errors[] = 'Phải có ít nhất 2 variants';
        }

        foreach ($variant_names as $idx => $vname) {
            $vname = trim($vname);
            $vprice = floatval($variant_prices[$idx] ?? 0);
            $vdiscount = intval($variant_discounts[$idx] ?? 0);
            $vstock = intval($variant_stocks[$idx] ?? 0);
            $vmin = intval($variant_mins[$idx] ?? 1);
            $vmax = intval($variant_maxs[$idx] ?? 2);
            $v_requires_info = isset($variant_requires_customer_info[$idx]) ? 1 : 0;
            $v_info_label = trim($variant_customer_info_label[$idx] ?? '');

            if (empty($vname)) {
                $errors[] = "Variant #" . ($idx + 1) . ": Tên không được trống";
                continue;
            }
            if ($vprice <= 0) {
                $errors[] = "Variant '$vname': Giá phải lớn hơn 0";
                continue;
            }
            if ($vstock < 0) {
                $errors[] = "Variant '$vname': Tồn kho không hợp lệ";
                continue;
            }
            if ($vmax > $vstock) {
                $errors[] = "Variant '$vname': Max mua ($vmax) phải ≤ Tồn kho ($vstock)";
                continue;
            }

            $variants[] = [
                'name' => $vname,
                'price' => $vprice,
                'discount' => $vdiscount,
                'stock' => $vstock,
                'min' => $vmin,
                'max' => $vmax,
                'requires_customer_info' => $v_requires_info,
                'customer_info_label' => $v_info_label
            ];
        }
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

            if ($option_mode === 'one') {
                // Single option
                $price_usd = round($price_vnd / $exchange_rate, 2);
                $discount_amount_vnd = round($price_vnd * $discount_percent / 100);
                $discount_amount_usd = round($price_usd * $discount_percent / 100, 2);
                $final_price_vnd = $price_vnd - $discount_amount_vnd;
                $final_price_usd = $price_usd - $discount_amount_usd;

                $pdo->prepare("
                    INSERT INTO products (
                        id, name, slug, description, price_vnd, price_usd,
                        discount_percent, discount_amount_vnd, discount_amount_usd,
                        final_price_vnd, final_price_usd, stock, category_id,
                        min_purchase, max_purchase, label, label_text_color, label_bg_color, image,
                        requires_customer_info, customer_info_label, product_type, label_id
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
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
                            'account',
                            $label_id
                        ]);

            } else {
                // Multi variants
                $first = $variants[0];
                $price_vnd = $first['price'];
                $price_usd = round($price_vnd / $exchange_rate, 2);
                $discount_percent = $first['discount'];
                $discount_amount_vnd = round($price_vnd * $discount_percent / 100);
                $discount_amount_usd = round($price_usd * $discount_percent / 100, 2);
                $final_price_vnd = $price_vnd - $discount_amount_vnd;
                $final_price_usd = $price_usd - $discount_amount_usd;
                $stock = $first['stock'];

                $pdo->prepare("
                    INSERT INTO products (
                        id, name, slug, description, price_vnd, price_usd,
                        discount_percent, discount_amount_vnd, discount_amount_usd,
                        final_price_vnd, final_price_usd, stock, category_id,
                        min_purchase, max_purchase, label, label_text_color, label_bg_color, image,
                        requires_customer_info, customer_info_label, product_type, label_id
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
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
                            $stock,
                            $category_id,
                            1,
                            2,
                            $label,
                            $label_text_color,
                            $label_bg_color,
                            $image_name,
                            $requires_customer_info,
                            $customer_info_label,
                            'account',
                            $label_id
                        ]);

                // Insert variants
                $sort_order = 0;
                foreach ($variants as $vdata) {
                    $variant_id = generateSnowflakeId();
                    $vprice_vnd = $vdata['price'];
                    $vprice_usd = round($vprice_vnd / $exchange_rate, 2);
                    $vdiscount = $vdata['discount'];
                    $vdiscount_vnd = round($vprice_vnd * $vdiscount / 100);
                    $vdiscount_usd = round($vprice_usd * $vdiscount / 100, 2);
                    $vfinal_vnd = $vprice_vnd - $vdiscount_vnd;
                    $vfinal_usd = $vprice_usd - $vdiscount_usd;

                    $pdo->prepare("
                        INSERT INTO product_variants (
                            id, product_id, variant_name, price_vnd, price_usd,
                            discount_percent, discount_amount_vnd, discount_amount_usd,
                            final_price_vnd, final_price_usd, stock,
                            min_purchase, max_purchase, sort_order,
                            requires_customer_info, customer_info_label
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ")->execute([
                                $variant_id,
                                $product_id,
                                $vdata['name'],
                                $vprice_vnd,
                                $vprice_usd,
                                $vdiscount,
                                $vdiscount_vnd,
                                $vdiscount_usd,
                                $vfinal_vnd,
                                $vfinal_usd,
                                $vdata['stock'],
                                $vdata['min'],
                                $vdata['max'],
                                $sort_order++,
                                $vdata['requires_customer_info'],
                                $vdata['customer_info_label']
                            ]);
                }
            }

            // ==================== INSERT ACCOUNTS INTO STOCK POOL ====================
            $accounts_json = $_POST['accounts_data'] ?? '';
            if (!empty($accounts_json)) {
                $accounts_obj = json_decode($accounts_json, true);

                if ($option_mode === 'one' && isset($accounts_obj['one']) && is_array($accounts_obj['one'])) {
                    // Single option - Insert all accounts with variant_id = NULL
                    foreach ($accounts_obj['one'] as $acc) {
                        if (isset($acc['content'])) {
                            $content = trim($acc['content']);
                            $pdo->prepare("
                                INSERT INTO product_stock_pool 
                                (product_id, variant_id, content, is_used) 
                                VALUES (?, NULL, ?, 0)
                            ")->execute([$product_id, $content]);
                        }
                    }
                } else if ($option_mode === 'multi' && isset($accounts_obj['multi']) && is_array($accounts_obj['multi'])) {
                    // Multi variants - Map variant index to variant_id
                    $variant_map = [];
                    $sort = 0;
                    foreach ($variants as $vdata) {
                        $sort++;
                        // Find variant_id by sort_order
                        $stmt = $pdo->prepare("SELECT id FROM product_variants WHERE product_id = ? AND sort_order = ?");
                        $stmt->execute([$product_id, $sort - 1]);
                        $vid = $stmt->fetchColumn();
                        if ($vid) {
                            $variant_map[$sort] = $vid;
                        }
                    }

                    // Insert accounts for each variant
                    foreach ($accounts_obj['multi'] as $variant_idx => $accs) {
                        if (is_array($accs) && isset($variant_map[$variant_idx])) {
                            $vid = $variant_map[$variant_idx];
                            foreach ($accs as $acc) {
                                if (isset($acc['content'])) {
                                    $content = trim($acc['content']);
                                    $pdo->prepare("
                                        INSERT INTO product_stock_pool 
                                        (product_id, variant_id, content, is_used) 
                                        VALUES (?, ?, ?, 0)
                                    ")->execute([$product_id, $vid, $content]);
                                }
                            }
                        }
                    }
                }
            }

            $pdo->commit();

            // Redirect về trang quản lý sản phẩm
            header("Location: ?tab=products&success=1&type=account");
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
    <input type="hidden" name="product_type" value="account">
    <input type="hidden" name="accounts_data" id="accountsDataInput" value="">

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

    <!-- Account-Specific: Options -->
    <div class="form-section" id="pricingStockSection">
        <div class="form-section-header">
            <i class="fas fa-boxes"></i>
            <h3>Số lượng + Giá</h3>
        </div>

        <div class="option-mode-tabs">
            <button type="button" class="option-mode-tab active" data-mode="one" onclick="switchOptionMode('one')">
                <i class="fas fa-cube"></i> One Option
            </button>
            <button type="button" class="option-mode-tab" data-mode="multi" onclick="switchOptionMode('multi')">
                <i class="fas fa-cubes"></i> Nhiều Option
            </button>
        </div>

        <input type="hidden" name="option_mode" id="optionMode" value="one">

        <!-- One Option Mode -->
        <div id="oneOptionContent" class="option-content active">
            <div class="pricing-box">
                <h4 style="color: #10b981; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-dollar-sign"></i> Giá & Giảm Giá
                </h4>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label>
                            <i class="fas fa-money-bill-wave"></i>
                            Giá Gốc (VND) <span style="color: #ef4444;">*</span>
                        </label>
                        <input type="number" name="price_vnd" class="form-control price-input"
                            placeholder="" oninput="calculateOneOption()">
                        <small id="oneOptionUsd"
                            style="color: #10b981; font-weight: 600; display: block; margin-top: 0.5rem;">
                            USD: $0.00
                        </small>
                    </div>

                    <div class="form-group">
                        <label>
                            <i class="fas fa-percent"></i>
                            Giảm Giá (%) <span style="color: #ef4444;">*</span>
                        </label>
                        <input type="number" name="discount_percent" class="form-control" min="0" max="100" value="0"
                            oninput="calculateOneOption()">
                        <small id="oneOptionFinal"
                            style="color: #10b981; font-weight: 600; display: block; margin-top: 0.5rem;">
                            Giá cuối: 0đ
                        </small>
                    </div>
                </div>

                <!-- Customer Info Requirement -->
                <div style="border-top: 1px solid rgba(139, 92, 246, 0.2); padding-top: 1rem; margin-top: 1rem;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label
                            style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer; margin-bottom: 1rem;">
                            <input type="checkbox" name="requires_customer_info" value="1" id="requiresCustomerInfo"
                                style="width: 20px; height: 20px;">
                            <span style="color: var(--text-primary); font-weight: 600;">
                                <i class="fas fa-user-circle" style="color: #8b5cf6;"></i>
                                Yêu cầu khách hàng nhập thông tin khi mua
                            </span>
                        </label>
                        <small style="color: var(--text-muted); display: block; margin-left: 2.25rem;">
                            <i class="fas fa-info-circle"></i>
                            Dành cho sản phẩm dịch vụ
                        </small>
                    </div>

                    <div id="customerInfoLabelGroup"
                        style="display: none; margin-top: 1rem; padding-left: 2rem; border-left: 3px solid #8b5cf6;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label>
                                <i class="fas fa-tag"></i>
                                Nhãn yêu cầu
                            </label>
                            <textarea name="customer_info_label" class="form-control" rows="3"
                                placeholder="VD: Nhập email ...."></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="stock-box">
                <h4 style="color: #a78bfa; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-warehouse"></i> Upload Tài Khoản Vào Kho
                </h4>

                <div class="form-group file-upload-section">
                    <label>
                        <i class="fas fa-file-upload"></i>
                        File Tài Khoản (.txt) <span style="color: #ef4444;">*</span>
                    </label>
                    <input type="file" name="account_file" id="accountFile" class="form-control" accept=".txt"
                        onchange="parseAccountFile(this, 'one')" style="padding: 0.5rem;">
                    <small style="color: var(--text-muted); display: block; margin-top: 0.5rem;">
                        <i class="fas fa-info-circle"></i>
                        Format: <code
                            style="background: rgba(139, 92, 246, 0.2); padding: 0.2rem 0.5rem; border-radius: 4px;">Mỗi dòng 1 tài khoản</code>
                    </small>
                </div>

                <div id="accountPreviewOne" class="file-upload-section" style="display: none; margin-top: 1rem;">
                    <div
                        style="background: rgba(16, 185, 129, 0.1); border: 1px solid #10b981; border-radius: 8px; padding: 1rem;">
                        <div
                            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                            <div>
                                <span style="color: #10b981; font-weight: 600;">
                                    <i class="fas fa-check-circle"></i> File hợp lệ
                                </span>
                                <span id="accountCountOne"
                                    style="background: #10b981; color: #fff; padding: 0.25rem 0.75rem; border-radius: 12px; font-weight: 700; margin-left: 0.75rem;"></span>
                            </div>
                            <button type="button" class="btn btn-primary btn-sm" onclick="openAccountManager('one')">
                                <i class="fas fa-cog"></i> Quản Lý Kho
                            </button>
                        </div>
                        <div id="accountListOne"
                            style="max-height: 150px; overflow-y: auto; font-family: 'Courier New', monospace; font-size: 0.85rem; color: var(--text-secondary);">
                        </div>
                    </div>
                </div>

                <div class="form-group" style="margin-top: 1rem;">
                    <label>
                        <i class="fas fa-boxes"></i>
                        Số Lượng Tồn Kho <span style="color: #ef4444;">*</span>
                    </label>
                    <input type="number" name="stock" id="oneOptionStock" class="form-control" min="0" value="0"
                        readonly style="background: rgba(139, 92, 246, 0.1); cursor: not-allowed;">
                    <small style="color: var(--text-muted); display: block; margin-top: 0.5rem;">
                        <i class="fas fa-info-circle"></i>
                        file .txt (số lượng TK hợp lệ)
                    </small>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label>
                            <i class="fas fa-arrow-down"></i>
                            Min Mua
                        </label>
                        <input type="number" name="min_purchase" class="form-control" min="1" value="1">
                    </div>

                    <div class="form-group">
                        <label>
                            <i class="fas fa-arrow-up"></i>
                            Max Mua
                        </label>
                        <input type="number" name="max_purchase" id="oneOptionMax" class="form-control" min="1"
                            value="2" oninput="validateOneOptionMax()">
                    </div>
                </div>
            </div>
        </div>

        <!-- Multi Options Mode -->
        <div id="multiOptionContent" class="option-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h4 style="color: #8b5cf6; margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-list"></i> Danh Sách Option
                </h4>
                <button type="button" class="btn btn-primary" onclick="addVariant()">
                    <i class="fas fa-plus-circle"></i> Thêm
                </button>
            </div>

            <div id="variantsList">
                <!-- Variants will be added here -->
            </div>
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

<!-- Account Manager Modal -->
<div id="accountManagerModal" class="modal-overlay" style="display: none;">
    <div class="modal-container" style="max-width: 1200px;">
        <div class="modal-header">
            <h3 style="margin: 0; color: var(--text-primary); display: flex; align-items: center; gap: 0.75rem;">
                <i class="fas fa-database" style="color: #8b5cf6;"></i>
                Quản Lý Kho Tài Khoản
            </h3>
            <button type="button" class="modal-close" onclick="closeAccountManager()">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="modal-body">
            <div style="margin-bottom: 1.5rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <div>
                        <span style="color: var(--text-muted);">Tổng số:</span>
                        <span id="modalAccountCount"
                            style="background: #8b5cf6; color: #fff; padding: 0.25rem 0.75rem; border-radius: 12px; font-weight: 700; margin-left: 0.5rem;"></span>
                    </div>
                    <button type="button" class="btn btn-primary" onclick="addAccountRow()">
                        <i class="fas fa-plus"></i> Thêm Tài Khoản
                    </button>
                </div>

                <div style="position: relative;">
                    <button type="button" id="clearSearchBtn" onclick="clearAccountSearch()"
                        style="position: absolute; right: 0.5rem; top: 50%; transform: translateY(-50%); background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; color: #ef4444; padding: 0.25rem 0.5rem; border-radius: 6px; cursor: pointer; display: none; transition: all 0.2s;"
                        onmouseover="this.style.background='#ef4444'; this.style.color='#fff';"
                        onmouseout="this.style.background='rgba(239, 68, 68, 0.1)'; this.style.color='#ef4444';"
                        title="Xóa tìm kiếm">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

            <div style="overflow-x: auto;">
                <table class="account-table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>Nội Dung Stock (1 dòng = 1 tài khoản)</th>
                            <th style="width: 120px;">Thao Tác</th>
                        </tr>
                    </thead>
                    <tbody id="accountTableBody">
                        <!-- Rows will be added here -->
                    </tbody>
                </table>
            </div>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeAccountManager()">
                <i class="fas fa-times"></i> Đóng
            </button>
            <button type="button" class="btn btn-primary" onclick="saveAccountManager()">
                <i class="fas fa-save"></i> Lưu Thay Đổi
            </button>
        </div>
    </div>
</div>

<script src="../assets/js/product_account.js?v=<?php echo time(); ?>"></script>

<style>
    /* Modal Styles */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.8);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 99999;
        padding: 2rem;
    }

    .modal-container {
        background: rgba(15, 23, 42, 0.95);
        border: 1px solid rgba(139, 92, 246, 0.3);
        border-radius: 16px;
        width: 100%;
        max-height: 90vh;
        display: flex;
        flex-direction: column;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
    }

    .modal-header {
        padding: 1.5rem 2rem;
        border-bottom: 2px solid rgba(139, 92, 246, 0.2);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .modal-close {
        background: rgba(239, 68, 68, 0.1);
        border: 1px solid #ef4444;
        color: #ef4444;
        width: 36px;
        height: 36px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s;
    }

    .modal-close:hover {
        background: #ef4444;
        color: #fff;
        transform: scale(1.1);
    }

    .modal-body {
        padding: 2rem;
        overflow-y: auto;
        flex: 1;
    }

    .modal-footer {
        padding: 1.5rem 2rem;
        border-top: 2px solid rgba(139, 92, 246, 0.2);
        display: flex;
        justify-content: flex-end;
        gap: 1rem;
    }

    .account-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.9rem;
    }

    .account-table thead {
        background: rgba(139, 92, 246, 0.15);
    }

    .account-table th {
        padding: 1rem;
        text-align: left;
        color: var(--text-primary);
        font-weight: 600;
        border-bottom: 2px solid rgba(139, 92, 246, 0.3);
    }

    .account-table tbody tr {
        border-bottom: 1px solid rgba(139, 92, 246, 0.1);
        transition: all 0.2s;
    }

    .account-table tbody tr:hover {
        background: rgba(139, 92, 246, 0.05);
    }

    .account-table td {
        padding: 0.75rem 1rem;
        color: var(--text-secondary);
    }

    .account-table input {
        background: rgba(30, 41, 59, 0.6);
        border: 1px solid rgba(139, 92, 246, 0.2);
        border-radius: 6px;
        padding: 0.5rem 0.75rem;
        color: var(--text-primary);
        width: 100%;
        font-family: 'Courier New', monospace;
    }

    .account-table input:focus {
        outline: none;
        border-color: #8b5cf6;
    }

    .btn-secondary {
        background: rgba(100, 116, 139, 0.2);
        border: 1px solid #64748b;
        color: #cbd5e1;
    }

    .btn-secondary:hover {
        background: rgba(100, 116, 139, 0.3);
        border-color: #94a3b8;
    }

    .btn-icon {
        padding: 0.5rem;
        width: 36px;
        height: 36px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.2s;
    }

    .btn-icon.btn-edit {
        background: rgba(59, 130, 246, 0.1);
        border: 1px solid #3b82f6;
        color: #3b82f6;
    }

    .btn-icon.btn-edit:hover {
        background: #3b82f6;
        color: #fff;
    }

    .btn-icon.btn-delete {
        background: rgba(239, 68, 68, 0.1);
        border: 1px solid #ef4444;
        color: #ef4444;
    }

    .btn-icon.btn-delete:hover {
        background: #ef4444;
        color: #fff;
    }
</style>

<script>
    document.getElementById('requiresCustomerInfo')?.addEventListener('change', function () {
        const isChecked = this.checked;

        document.getElementById('customerInfoLabelGroup').style.display = isChecked ? 'block' : 'none';

        const fileUploadSections = document.querySelectorAll('.file-upload-section');
        fileUploadSections.forEach(section => {
            section.style.display = isChecked ? 'none' : 'block';
        });

        const stockInputs = document.querySelectorAll('input[name="stock"], input[name="variant_stock[]"]');
        stockInputs.forEach(input => {
            if (isChecked) {
                input.removeAttribute('readonly');
                input.style.background = '';
                input.style.cursor = '';
            } else {
                input.setAttribute('readonly', 'readonly');
                input.style.background = 'rgba(139, 92, 246, 0.1)';
                input.style.cursor = 'not-allowed';
            }
        });
    });
</script>