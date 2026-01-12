<?php
/**
 * Common Fields - Dùng chung cho cả 3 loại sản phẩm
 * Account | Source Code | Sách
 */

// Get categories and label presets from parent scope
// Assumed $pdo, $label_presets, $categories are available
?>

<!-- Section 1: Thông Tin Cơ Bản -->
<div class="form-section">
    <div class="form-section-header">
        <i class="fas fa-info-circle"></i>
        <h3>Thông Tin Cơ Bản</h3>
    </div>

    <div class="form-group">
        <label>
            <i class="fas fa-tag"></i>
            Tên Sản Phẩm <span style="color: #ef4444;">*</span>
        </label>
        <input type="text" name="name" class="form-control" required
            value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
    </div>

    <div class="form-group">
        <label>
            <i class="fas fa-align-left"></i>
            Mô Tả
        </label>
        <textarea name="description" class="form-control" rows="4"
            placeholder="Mô tả chi tiết về sản phẩm..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
    </div>
</div>

<!-- Section 2: Nhãn Sản Phẩm -->
<?php
// Fetch all labels from DB
$all_labels = $pdo->query("SELECT * FROM product_labels ORDER BY name ASC")->fetchAll();
$current_label_id = $product['label_id'] ?? ($_POST['label_id'] ?? null);
?>
<div class="form-section">
    <div class="form-section-header">
        <i class="fas fa-bookmark"></i>
        <h3>Nhãn Sản Phẩm</h3>
    </div>

    <div class="form-group">
        <label>
            <i class="fas fa-eye"></i>
            Xem trước Nhãn (Ảnh)
        </label>
        <div class="label-preview-container"
            style="padding: 15px; background: rgba(15, 23, 42, 0.4); border-radius: 12px; border: 1px dashed rgba(148, 163, 184, 0.2); text-align: center; min-height: 60px; display: flex; align-items: center; justify-content: center;">
            <div id="systemLabelPreview">
                <span style="color: var(--text-muted); font-size: 13px;">Chưa chọn nhãn</span>
            </div>
        </div>
    </div>

    <div class="form-group" style="margin-top: 1.5rem;">
        <label>
            <i class="fas fa-database"></i>
            Chọn Nhãn Từ Hệ Thống
        </label>
        <select name="label_id" id="systemLabelSelect" class="form-control" onchange="updateSystemLabelPreview(this)">
            <option value="">-- Không sử dụng nhãn --</option>
            <?php foreach ($all_labels as $lbl): ?>
                <option value="<?= $lbl['id'] ?>" data-image="<?= asset('images/uploads/' . $lbl['image_url']) ?>"
                    <?= $current_label_id == $lbl['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($lbl['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Preset chips removed as requested (Name + Image only focus) -->
    <input type="hidden" name="label" id="labelInput" value="">
    <input type="hidden" name="label_text_color" id="labelTextColor" value="">
    <input type="hidden" name="label_bg_color" id="labelBgColor" value="">

    <!-- Hidden color inputs to maintain compatibility with existing logic if any, 
         but we'll override them with presets for simplicity -->
    <input type="hidden" name="label_text_color" id="labelTextColor" value="#ffffff">
    <input type="hidden" name="label_bg_color" id="labelBgColor" value="#8b5cf6">
</div>

<!-- Section 3: Danh Mục -->
<div class="form-section">
    <div class="form-section-header">
        <i class="fas fa-folder-open"></i>
        <h3>Danh Mục</h3>
    </div>

    <div class="category-tabs">
        <button type="button" class="category-tab active" data-tab="existing" onclick="switchCategoryTab('existing')">
            <i class="fas fa-list"></i> Có Sẵn
        </button>
        <button type="button" class="category-tab" data-tab="new" onclick="switchCategoryTab('new')">
            <i class="fas fa-plus-circle"></i> Tạo Mới
        </button>
    </div>

    <div id="categoryExisting" class="category-tab-content active">
        <div class="form-group">
            <label>
                <i class="fas fa-folder"></i>
                Chọn Danh Mục
            </label>
            <select name="category_id" class="form-control" id="categorySelect">
                <option value="">-- Chọn danh mục --</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= (isset($_POST['category_id']) && $_POST['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                        <?= isset($cat['icon']) ? $cat['icon'] . ' ' : '' ?>     <?= htmlspecialchars($cat['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div id="categoryNew" class="category-tab-content">
        <div class="form-group">
            <label>
                <i class="fas fa-font"></i>
                Tên Danh Mục Mới
            </label>
            <input type="text" name="new_category" id="newCategoryInput" class="form-control">
        </div>

        <div class="form-group">
            <label>
                <i class="fas fa-icons"></i>
                Icon (Emoji hoặc Upload Ảnh)
            </label>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div>
                    <label style="font-size: 0.85rem; color: var(--text-muted); font-weight: 500;">Text/Emoji</label>
                    <input type="text" name="new_category_icon_text" id="categoryIconText" class="form-control"
                        value="📦" style="font-size: 1.5rem;">
                </div>

                <div>
                    <label style="font-size: 0.85rem; color: var(--text-muted); font-weight: 500;">Upload Icon</label>
                    <input type="file" name="new_category_icon_image" id="categoryIconImage" class="form-control"
                        accept="image/*" onchange="previewCategoryIcon(this)" style="padding: 0.5rem;">
                </div>
            </div>

            <div id="categoryIconPreview" style="display: none; margin-top: 1rem; text-align: center;">
                <img id="categoryIconPreviewImg" src="" alt="Icon"
                    style="width: 48px; height: 48px; object-fit: contain; border-radius: 8px; border: 2px solid #ffffff; padding: 4px; background: rgba(15, 23, 42, 0.6);">
            </div>

            <small style="color: var(--text-muted); display: block; margin-top: 0.5rem;">
                <i class="fas fa-info-circle"></i>
                Chọn emoji/text HOẶC upload ảnh (ảnh sẽ tự resize về 48x48px)
            </small>
        </div>
    </div>
</div>

<!-- Section 4: Hình Ảnh -->
<div class="form-section">
    <div class="form-section-header">
        <i class="fas fa-image"></i>
        <h3>Hình Ảnh</h3>
    </div>

    <div class="image-upload-area" id="imageUploadArea">
        <input type="file" name="image" id="imageInput" accept="image/*" style="display: none;"
            onchange="previewImage(this)">
        <div class="upload-placeholder" onclick="document.getElementById('imageInput').click()">
            <i class="fas fa-cloud-upload-alt"></i>
            <p style="margin: 0.75rem 0 0 0; font-weight: 600;">Click để upload ảnh</p>
            <small style="color: var(--text-muted); display: block; margin-top: 0.5rem;">
                Hỗ trợ: JPG, PNG, GIF, WEBP, SVG, BMP<br>
                Dung lượng tối đa: 10MB
            </small>
        </div>
        <div class="image-preview" id="imagePreview" style="display: none;">
            <img id="previewImg" src="" alt="Preview" style="max-width: 100%; max-height: 300px; border-radius: 8px;">
            <button type="button" class="btn-remove-image" onclick="removeImage()">
                <i class="fas fa-times"></i> Xóa
            </button>
        </div>
    </div>

    <div class="form-group" style="margin-top: 1rem;">
        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
            <input type="checkbox" name="auto_resize" value="1" checked style="width: 18px; height: 18px;">
            <span>Tự động resize ảnh lớn hơn 1200px</span>
        </label>
    </div>
</div>

<script>
    // Label preview functions
    function updateSystemLabelPreview(select) {
        const option = select.options[select.selectedIndex];
        const preview = document.getElementById('systemLabelPreview');
        if (!preview) return;

        if (option.value && option.getAttribute('data-image')) {
            const imgSrc = option.getAttribute('data-image');
            preview.innerHTML = `<img src="${imgSrc}" style="max-height: 40px; object-fit: contain;">`;
        } else {
            preview.innerHTML = `<span style="color: var(--text-muted); font-size: 13px;">Chưa chọn nhãn</span>`;
        }
    }

    // Initial preview
    window.addEventListener('DOMContentLoaded', () => {
        const select = document.getElementById('systemLabelSelect');
        if (select) updateSystemLabelPreview(select);
    });

    // Category tab switching
    function switchCategoryTab(tab) {
        document.querySelectorAll('.category-tab').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelectorAll('.category-tab-content').forEach(content => {
            content.classList.remove('active');
        });

        document.querySelector(`[data-tab="${tab}"]`).classList.add('active');
        document.getElementById(tab === 'existing' ? 'categoryExisting' : 'categoryNew').classList.add('active');

        if (tab === 'existing') {
            document.getElementById('newCategoryInput').value = '';
            document.getElementById('categorySelect').required = true;
        } else {
            document.getElementById('categorySelect').value = '';
            document.getElementById('categorySelect').required = false;
        }
    }

    // Preview category icon
    function previewCategoryIcon(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function (e) {
                document.getElementById('categoryIconPreviewImg').src = e.target.result;
                document.getElementById('categoryIconPreview').style.display = 'block';

                // Clear text input if image uploaded
                document.getElementById('categoryIconText').value = '';
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    // Image preview
    function previewImage(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function (e) {
                document.getElementById('previewImg').src = e.target.result;
                document.querySelector('.upload-placeholder').style.display = 'none';
                document.getElementById('imagePreview').style.display = 'block';
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    function removeImage() {
        document.getElementById('imageInput').value = '';
        document.getElementById('previewImg').src = '';
        document.querySelector('.upload-placeholder').style.display = 'flex';
        document.getElementById('imagePreview').style.display = 'none';
    }
</script>