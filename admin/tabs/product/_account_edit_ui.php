<link rel="stylesheet" href="<?= asset('css/product_add.css') ?>?v=<?= time() ?>">

<div style="margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
    <button type="button" class="btn btn-secondary"
        onclick="window.location.href='?tab=product_manage&product_id=<?= $product_id ?>'"
        style="display: inline-flex; align-items: center; gap: 0.5rem;">
        <i class="fas fa-arrow-left"></i> Quay Lại
    </button>
    <button type="button" class="btn" onclick="toggleProductVisibility('<?= $product_id ?>')" id="toggleVisibilityBtn"
        style="background: <?= $product['is_hidden'] ? 'rgba(16, 185, 129, 0.1)' : 'rgba(239, 68, 68, 0.1)' ?>; 
               border: 1px solid <?= $product['is_hidden'] ? '#10b981' : '#ef4444' ?>; 
               color: <?= $product['is_hidden'] ? '#10b981' : '#ef4444' ?>; 
               display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.5rem; 
               border-radius: 8px; cursor: pointer; transition: all 0.2s;">
        <i class="fas <?= $product['is_hidden'] ? 'fa-eye' : 'fa-eye-slash' ?>"></i>
        <?= $product['is_hidden'] ? 'Hiện Sản Phẩm' : 'Ẩn Sản Phẩm' ?>
    </button>
</div>

<script>
    function toggleProductVisibility(productId) {
        const btn = document.getElementById('toggleVisibilityBtn');
        const currentlyHidden = btn.querySelector('i').classList.contains('fa-eye');

        const action = currentlyHidden ? 'hiện' : 'ẩn';
        if (!confirm(`Bạn có chắc muốn ${action} sản phẩm này?`)) {
            return;
        }

        if (window.showLoading) showLoading();

        fetch('<?= url('admin/api/toggle-product-visibility.php') ?>', {
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
                        notify.success('✅ ' + data.message);
                    }

                    // Update button appearance
                    const icon = btn.querySelector('i');
                    const text = btn.childNodes[btn.childNodes.length - 1];

                    if (data.is_hidden) {
                        // Product is now hidden
                        btn.style.background = 'rgba(16, 185, 129, 0.1)';
                        btn.style.borderColor = '#10b981';
                        btn.style.color = '#10b981';
                        icon.className = 'fas fa-eye';
                        text.textContent = ' Hiện Sản Phẩm';
                    } else {
                        // Product is now visible
                        btn.style.background = 'rgba(239, 68, 68, 0.1)';
                        btn.style.borderColor = '#ef4444';
                        btn.style.color = '#ef4444';
                        icon.className = 'fas fa-eye-slash';
                        text.textContent = ' Ẩn Sản Phẩm';
                    }
                } else {
                    if (window.notify) {
                        notify.error('❌ ' + (data.message || 'Lỗi khi thay đổi trạng thái sản phẩm'));
                    } else {
                        alert('Lỗi: ' + (data.message || 'Không thể thay đổi trạng thái'));
                    }
                }
            })
            .catch(error => {
                if (window.hideLoading) hideLoading();
                if (window.notify) {
                    notify.error('❌ Lỗi kết nối: ' + error.message);
                } else {
                    alert('Lỗi kết nối: ' + error.message);
                }
            });
    }
</script>

<form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="update_product" value="1">

    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <div><?php foreach ($errors as $error): ?>
                    <div><?= htmlspecialchars($error) ?></div><?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <div><?= htmlspecialchars($success) ?></div>
        </div>
    <?php endif; ?>

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
            </div>
        </div>
    <?php endif; ?>


    <!-- Pricing & Stock -->
    <div class="form-section" id="pricingStockSection">
        <div class="form-section-header">
            <i class="fas fa-boxes"></i>
            <h3>Giá & Tồn Kho</h3>
        </div>

        <?php if (!$hasVariants): ?>
            <!-- Single Option -->
            <div class="pricing-box">
                <h4 style="color: #10b981; margin-bottom: 1rem;"><i class="fas fa-dollar-sign"></i> Giá & Giảm Giá</h4>
                <div class="form-grid-2">
                    <div class="form-group">
                        <label><i class="fas fa-money-bill-wave"></i> Giá Gốc (VND)</label>
                        <input type="number" name="price_vnd" class="form-control" value="<?= $product['price_vnd'] ?>"
                            min="1000" step="1000" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-percent"></i> Giảm Giá (%)</label>
                        <input type="number" name="discount_percent" class="form-control"
                            value="<?= $product['discount_percent'] ?>" min="0" max="100">
                    </div>
                </div>

                <!-- Customer Info Requirement -->
                <div style="border-top: 1px solid rgba(139, 92, 246, 0.2); padding-top: 1rem; margin-top: 1rem;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label
                            style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer; margin-bottom: 1rem;">
                            <input type="checkbox" name="requires_customer_info" value="1" id="requiresCustomerInfo"
                                <?= $product['requires_customer_info'] ? 'checked' : '' ?>
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
                        style="<?= $product['requires_customer_info'] ? 'display: block;' : 'display: none;' ?> margin-top: 1rem; padding-left: 2rem; border-left: 3px solid #8b5cf6;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label><i class="fas fa-tag"></i> Nhãn yêu cầu</label>
                            <textarea name="customer_info_label" class="form-control" rows="3"
                                placeholder="VD: Nhập email ...."><?= htmlspecialchars($product['customer_info_label'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="stock-box">
                <h4 style="color: #a78bfa; margin-bottom: 1rem;"><i class="fas fa-warehouse"></i> Quản Lý Kho</h4>

                <div class="form-group">
                    <label><i class="fas fa-boxes"></i> Số Lượng Tồn Kho</label>
                    <input type="number" name="stock" id="stockInput" class="form-control" value="<?= $product['stock'] ?>"
                        <?= $product['requires_customer_info'] ? '' : 'readonly' ?>
                        style="
                <?= $product['requires_customer_info'] ? '' : 'background: rgba(139, 92, 246, 0.1); cursor: not-allowed;' ?>" min="0">
                    <small style="color: var(--text-muted); margin-top: 0.5rem; display: block;" id="stockHint">
                        <i class="fas fa-info-circle"></i>
                        <span id="stockHintText">
                            <?= $product['requires_customer_info'] ? 'Nhập số lượng stock trong kho' : 'Tự động tính từ kho.' ?>
                        </span>
                    </small>
                </div>

                <button type="button" class="btn btn-primary stock-manager-btn" onclick="openStockManager('single', null)"
                    style="margin-top: 1rem; width: 100%; <?= $product['requires_customer_info'] ? 'display:none;' : '' ?>">
                    <i class="fas fa-cog"></i> Quản Lý Kho Tài Khoản (<?= $product['stock'] ?>)
                </button>

                <div class="form-grid-2" style="margin-top: 1.5rem;">
                    <div class="form-group">
                        <label><i class="fas fa-arrow-down"></i> Min Mua</label>
                        <input type="number" name="min_purchase" class="form-control"
                            value="<?= $product['min_purchase'] ?? 1 ?>" min="1">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-arrow-up"></i> Max Mua</label>
                        <input type="number" name="max_purchase" class="form-control"
                            value="<?= $product['max_purchase'] ?? 999 ?>" min="1">
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- Multi Variants -->
            <div class="info-box"
                style="background: rgba(139, 92, 246, 0.1); border-left-color: #8b5cf6; margin-bottom: 1.5rem;">
                <i class="fas fa-layer-group" style="color: #8b5cf6;"></i>
                Sản phẩm có <strong><?= count($variants) ?> variants</strong>. Cập nhật thông tin từng variant bên dưới.
            </div>

            <?php foreach ($variants as $idx => $variant): ?>
                <div class="variant-item" style="margin-bottom: 1.5rem;">
                    <div class="variant-header">
                        <h5><i class="fas fa-star"></i> <?= htmlspecialchars($variant['variant_name']) ?></h5>
                        <button type="button" class="btn btn-danger btn-sm"
                            onclick="if(confirm('Xóa variant này?'))deleteVariant('<?= $variant['id'] ?>')">
                            <i class="fas fa-trash"></i> Xóa
                        </button>
                    </div>

                    <div class="variant-body">
                        <input type="hidden" name="variant_ids[]" value="<?= $variant['id'] ?>">

                        <div class="form-group">
                            <label><i class="fas fa-tag"></i> Tên Variant</label>
                            <input type="text" name="variant_names[<?= $variant['id'] ?>]" class="form-control"
                                value="<?= htmlspecialchars($variant['variant_name']) ?>" required>
                        </div>

                        <div class="form-grid-2">
                            <div class="form-group">
                                <label><i class="fas fa-money-bill-wave"></i> Giá (VND)</label>
                                <input type="number" name="variant_prices[<?= $variant['id'] ?>]" class="form-control"
                                    value="<?= $variant['price_vnd'] ?>" min="1000" step="1000" required>
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-percent"></i> Giảm Giá (%)</label>
                                <input type="number" name="variant_discounts[<?= $variant['id'] ?>]" class="form-control"
                                    value="<?= $variant['discount_percent'] ?>" min="0" max="100">
                            </div>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-boxes"></i> Tồn Kho</label>
                            <input type="number" name="variant_stocks[<?= $variant['id'] ?>]"
                                class="form-control variant-stock-input" value="<?= $variant['stock'] ?>"
                                <?= $product['requires_customer_info'] ? '' : 'readonly' ?>
                                style="<?= $product['requires_customer_info'] ? '' : 'background: rgba(139, 92, 246, 0.1);' ?>"
                                min="0">
                        </div>

                        <button type="button" class="btn btn-primary btn-sm stock-manager-btn"
                            onclick="openStockManager('variant', '<?= $variant['id'] ?>')"
                            style="margin-top: 0.5rem; width: 100%; <?= $product['requires_customer_info'] ? 'display:none;' : '' ?>">
                            <i class="fas fa-cog"></i> Quản Lý Kho (<?= $variant['stock'] ?>)
                        </button>

                        <div class="form-grid-2" style="margin-top: 1rem;">
                            <div class="form-group">
                                <label><i class="fas fa-arrow-down"></i> Min</label>
                                <input type="number" name="variant_mins[<?= $variant['id'] ?>]" class="form-control"
                                    value="<?= $variant['min_purchase'] ?? 1 ?>" min="1">
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-arrow-up"></i> Max</label>
                                <input type="number" name="variant_maxs[<?= $variant['id'] ?>]" class="form-control"
                                    value="<?= $variant['max_purchase'] ?? 999 ?>" min="1">
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div style="text-align: right; margin-top: 2rem;">
        <button type="submit" class="submit-btn">
            <i class="fas fa-save"></i> Lưu Thay Đổi
        </button>
    </div>
</form>

<!-- Stock Manager Modal -->
<?php include __DIR__ . '/_stock_manager_modal.php'; ?>

<script src="<?= asset('js/stock_manager.js') ?>?v=<?= time() ?>"></script>
<script>
    initStockManager(<?= json_encode($stockPool) ?>);

    document.getElementById('requiresCustomerInfo')?.addEventListener('change', function () {
        const isChecked = this.checked;

        // Show/hide customer info label input
        document.getElementById('customerInfoLabelGroup').style.display = isChecked ? 'block' : 'none';

        // Hide/show stock manager button (not the entire stock box)
        const stockManagerBtns = document.querySelectorAll('.stock-manager-btn');
        stockManagerBtns.forEach(btn => {
            btn.style.display = isChecked ? 'none' : 'block';
        });

        // Toggle stock input readonly state
        const stockInput = document.getElementById('stockInput');
        const stockHintText = document.getElementById('stockHintText');
        if (stockInput) {
            if (isChecked) {
                stockInput.removeAttribute('readonly');
                stockInput.style.background = '';
                stockInput.style.cursor = '';
                if (stockHintText) stockHintText.textContent = 'Nhập số lượng thủ công';
            } else {
                stockInput.setAttribute('readonly', 'readonly');
                stockInput.style.background = 'rgba(139, 92, 246, 0.1)';
                stockInput.style.cursor = 'not-allowed';
                if (stockHintText) stockHintText.textContent = 'Tự động tính từ kho.';
            }
        }

        // Toggle variant stock inputs readonly state
        const variantStockInputs = document.querySelectorAll('.variant-stock-input');
        variantStockInputs.forEach(input => {
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