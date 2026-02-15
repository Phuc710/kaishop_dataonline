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
// Hardcoded labels with colors matching navigation bar
$hardcoded_labels = [
    'Free' => ['bg' => '#18a52a', 'text' => '#ffffff'],           // Green (Kho Mã Nguồn)
    'Source' => ['bg' => '#2997f7', 'text' => '#ffffff'],         // Blue (Trang Chủ)
    'Account' => ['bg' => '#04b9b6', 'text' => '#ffffff'],        // Purple (Diễn Đàn)
    'Danh mục khác' => ['bg' => '#b70cb7d5', 'text' => '#ffffff']   // Cyan (Bảng Xếp Hạng)
];
$current_label = $product['label'] ?? ($_POST['label'] ?? '');
?>
<div class="form-section">
    <div class="form-section-header">
        <i class="fas fa-bookmark"></i>
        <h3>Nhãn Sản Phẩm</h3>
    </div>

    <div class="form-group">
        <label>
            <i class="fas fa-eye"></i>
            Xem trước Nhãn
        </label>
        <div class="label-preview-container"
            style="padding: 15px; background: rgba(15, 23, 42, 0.4); border-radius: 12px; border: 1px dashed rgba(148, 163, 184, 0.2); text-align: center; min-height: 60px; display: flex; align-items: center; justify-content: center;">
            <div id="labelPreview">
                <?php if ($current_label && isset($hardcoded_labels[$current_label])): ?>
                    <span
                        style="padding: 8px 20px; border-radius: 99px; font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; background: <?= $hardcoded_labels[$current_label]['bg'] ?>; color: <?= $hardcoded_labels[$current_label]['text'] ?>;">
                        <?= htmlspecialchars($current_label) ?>
                    </span>
                <?php else: ?>
                    <span style="color: var(--text-muted); font-size: 13px;">Chưa chọn nhãn</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="form-group" style="margin-top: 1.5rem;">
        <label>
            <i class="fas fa-tags"></i>
            Chọn Nhãn
        </label>
        <select name="label" id="labelSelect" class="form-control" onchange="updateLabelPreview(this)">
            <option value="">-- Không sử dụng nhãn --</option>
            <?php foreach ($hardcoded_labels as $labelName => $colors): ?>
                <option value="<?= htmlspecialchars($labelName) ?>" data-bg="<?= $colors['bg'] ?>"
                    data-text="<?= $colors['text'] ?>" <?= $current_label === $labelName ? 'selected' : '' ?>>
                    <?= htmlspecialchars($labelName) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Hidden fields for color compatibility -->
    <input type="hidden" name="label_text_color" id="labelTextColor"
        value="<?= $hardcoded_labels[$current_label]['text'] ?? '#ffffff' ?>">
    <input type="hidden" name="label_bg_color" id="labelBgColor"
        value="<?= $hardcoded_labels[$current_label]['bg'] ?? '#8b5cf6' ?>">
</div>

<script>
    function updateLabelPreview(select) {
        const option = select.options[select.selectedIndex];
        const preview = document.getElementById('labelPreview');
        const bgColor = option.getAttribute('data-bg');
        const textColor = option.getAttribute('data-text');

        // Update hidden fields
        document.getElementById('labelTextColor').value = textColor || '#ffffff';
        document.getElementById('labelBgColor').value = bgColor || '#8b5cf6';

        if (option.value) {
            preview.innerHTML = `<span style="padding: 8px 20px; border-radius: 99px; font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; background: ${bgColor}; color: ${textColor};">${option.value}</span>`;
        } else {
            preview.innerHTML = '<span style="color: var(--text-muted); font-size: 13px;">Chưa chọn nhãn</span>';
        }
    }
</script>

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

<!-- Section 4: Hình Ảnh (Multi-Image Upload) -->
<div class="form-section">
    <div class="form-section-header">
        <i class="fas fa-images"></i>
        <h3>Hình Ảnh Sản Phẩm</h3>
        <small style="margin-left: auto; color: var(--text-muted); font-weight: 400;">Ảnh đầu tiên = Ảnh đại
            diện</small>
    </div>

    <!-- Hidden inputs for form submission -->
    <input type="file" name="image" id="mainImageInput" accept="image/*" style="display: none;">
    <input type="file" name="additional_images[]" id="additionalImagesInput" accept="image/*" multiple
        style="display: none;">
    <input type="hidden" name="images_json" id="imagesJsonInput" value="[]">
    <input type="hidden" name="existing_images" id="existingImagesInput"
        value="<?= htmlspecialchars($product['images'] ?? '[]') ?>">

    <!-- Upload Zone -->
    <div class="multi-image-upload-zone" id="multiImageUploadZone"
        onclick="document.getElementById('additionalImagesInput').click()">
        <i class="fas fa-cloud-upload-alt"></i>
        <p style="margin: 0.75rem 0 0 0; font-weight: 600;">Kéo thả hoặc click để upload ảnh</p>
        <small style="color: var(--text-muted); display: block; margin-top: 0.5rem;">
            Hỗ trợ: JPG, PNG, GIF, WEBP • Tối đa 10 ảnh • 10MB/ảnh
        </small>
    </div>

    <!-- Image Gallery Preview -->
    <div class="multi-image-gallery" id="multiImageGallery" style="display: none;">
        <div class="gallery-grid" id="galleryGrid"></div>
        <button type="button" class="btn-add-more" onclick="document.getElementById('additionalImagesInput').click()">
            <i class="fas fa-plus"></i> Thêm ảnh
        </button>
    </div>

    <div class="form-group" style="margin-top: 1rem;">
        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
            <input type="checkbox" name="auto_resize" value="1" checked style="width: 18px; height: 18px;">
            <span>Tự động resize ảnh lớn hơn 1200px</span>
        </label>
    </div>
</div>

<style>
    .multi-image-upload-zone {
        border: 2px dashed rgba(139, 92, 246, 0.4);
        border-radius: 12px;
        padding: 40px 20px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        background: rgba(139, 92, 246, 0.05);
    }

    .multi-image-upload-zone:hover,
    .multi-image-upload-zone.dragover {
        border-color: #8b5cf6;
        background: rgba(139, 92, 246, 0.1);
    }

    .multi-image-upload-zone i {
        font-size: 2.5rem;
        color: #8b5cf6;
        opacity: 0.7;
    }

    .multi-image-gallery {
        margin-top: 1rem;
    }

    .gallery-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
        gap: 12px;
        margin-bottom: 1rem;
    }

    .gallery-item {
        position: relative;
        aspect-ratio: 1;
        border-radius: 8px;
        overflow: hidden;
        border: 2px solid transparent;
        cursor: grab;
        transition: all 0.2s ease;
    }

    .gallery-item:first-child {
        border-color: #8b5cf6;
    }

    .gallery-item:first-child::after {
        content: 'Ảnh chính';
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        padding: 4px;
        background: linear-gradient(transparent, rgba(139, 92, 246, 0.9));
        color: #fff;
        font-size: 10px;
        text-align: center;
        font-weight: 600;
    }

    .gallery-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .gallery-item .btn-remove {
        position: absolute;
        top: 4px;
        right: 4px;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        background: rgba(239, 68, 68, 0.9);
        color: #fff;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        opacity: 0;
        transition: opacity 0.2s;
    }

    .gallery-item:hover .btn-remove {
        opacity: 1;
    }

    .gallery-item.dragging {
        opacity: 0.5;
        border-color: #fbbf24;
    }

    .btn-add-more {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        width: 100%;
        padding: 12px;
        background: rgba(139, 92, 246, 0.1);
        border: 2px dashed rgba(139, 92, 246, 0.3);
        border-radius: 8px;
        color: #8b5cf6;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.2s;
    }

    .btn-add-more:hover {
        background: rgba(139, 92, 246, 0.15);
        border-color: #8b5cf6;
    }
</style>

<script>
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

    // ==================== MULTI-IMAGE UPLOAD MANAGER ====================
    const MultiImageManager = {
        images: [], // Array of {file: File|null, url: string, isExisting: boolean}
        maxImages: 10,
        
        init() {
            const uploadZone = document.getElementById('multiImageUploadZone');
            const fileInput = document.getElementById('additionalImagesInput');
            const existingImagesInput = document.getElementById('existingImagesInput');
            
            // Load existing images (for edit mode)
            if (existingImagesInput && existingImagesInput.value) {
                try {
                    const existingImages = JSON.parse(existingImagesInput.value);
                    if (Array.isArray(existingImages)) {
                        existingImages.forEach(imgName => {
                            this.images.push({
                                file: null,
                                url: window.APP_CONFIG?.ASSET_URL ? 
                                    window.APP_CONFIG.ASSET_URL + 'images/uploads/' + imgName :
                                    '/assets/images/uploads/' + imgName,
                                filename: imgName,
                                isExisting: true
                            });
                        });
                    }
                } catch(e) {}
            }
            
            // Drag and drop
            if (uploadZone) {
                ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                    uploadZone.addEventListener(eventName, (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                    });
                });
                
                ['dragenter', 'dragover'].forEach(eventName => {
                    uploadZone.addEventListener(eventName, () => {
                        uploadZone.classList.add('dragover');
                    });
                });
                
                ['dragleave', 'drop'].forEach(eventName => {
                    uploadZone.addEventListener(eventName, () => {
                        uploadZone.classList.remove('dragover');
                    });
                });
                
                uploadZone.addEventListener('drop', (e) => {
                    const files = e.dataTransfer.files;
                    this.handleFiles(files);
                });
            }
            
            // File input change
            if (fileInput) {
                fileInput.addEventListener('change', (e) => {
                    this.handleFiles(e.target.files);
                    e.target.value = ''; // Reset for re-upload
                });
            }
            
            this.render();
        },
        
        handleFiles(files) {
            const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            const maxSize = 10 * 1024 * 1024; // 10MB
            
            Array.from(files).forEach(file => {
                if (this.images.length >= this.maxImages) {
                    alert('Tối đa ' + this.maxImages + ' ảnh!');
                    return;
                }
                
                if (!validTypes.includes(file.type)) {
                    alert('File ' + file.name + ' không hợp lệ!');
                    return;
                }
                
                if (file.size > maxSize) {
                    alert('File ' + file.name + ' quá lớn (max 10MB)!');
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.images.push({
                        file: file,
                        url: e.target.result,
                        isExisting: false
                    });
                    this.render();
                    this.updateFormData();
                };
                reader.readAsDataURL(file);
            });
        },
        
        removeImage(index) {
            this.images.splice(index, 1);
            this.render();
            this.updateFormData();
        },
        
        render() {
            const uploadZone = document.getElementById('multiImageUploadZone');
            const gallery = document.getElementById('multiImageGallery');
            const grid = document.getElementById('galleryGrid');
            
            if (this.images.length === 0) {
                uploadZone.style.display = 'block';
                gallery.style.display = 'none';
                return;
            }
            
            uploadZone.style.display = 'none';
            gallery.style.display = 'block';
            
            grid.innerHTML = this.images.map((img, idx) => `
                <div class="gallery-item" draggable="true" data-index="${idx}">
                    <img src="${img.url}" alt="Image ${idx + 1}">
                    <button type="button" class="btn-remove" onclick="MultiImageManager.removeImage(${idx})">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `).join('');
            
            // Setup drag reordering
            this.setupDragReorder();
        },
        
        setupDragReorder() {
            const items = document.querySelectorAll('.gallery-item');
            let draggedItem = null;
            
            items.forEach(item => {
                item.addEventListener('dragstart', (e) => {
                    draggedItem = item;
                    item.classList.add('dragging');
                    e.dataTransfer.effectAllowed = 'move';
                });
                
                item.addEventListener('dragend', () => {
                    item.classList.remove('dragging');
                    draggedItem = null;
                    this.updateFormData();
                });
                
                item.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    if (draggedItem && draggedItem !== item) {
                        const grid = document.getElementById('galleryGrid');
                        const items = Array.from(grid.children);
                        const draggedIdx = items.indexOf(draggedItem);
                        const targetIdx = items.indexOf(item);
                        
                        if (draggedIdx < targetIdx) {
                            item.after(draggedItem);
                        } else {
                            item.before(draggedItem);
                        }
                        
                        // Update images array order
                        const movedImg = this.images.splice(draggedIdx, 1)[0];
                        this.images.splice(targetIdx, 0, movedImg);
                    }
                });
            });
        },
        
        updateFormData() {
            const jsonInput = document.getElementById('imagesJsonInput');
            const mainImageInput = document.getElementById('mainImageInput');
            
            // Get existing image filenames to keep
            const existingToKeep = this.images
                .filter(img => img.isExisting)
                .map(img => img.filename);
            
            jsonInput.value = JSON.stringify(existingToKeep);
            
            // Create DataTransfer to set files on input
            const dt = new DataTransfer();
            this.images.forEach((img, idx) => {
                if (img.file) {
                    // Rename first new file to be main image
                    if (idx === 0 || (idx === 0 && !img.isExisting)) {
                        dt.items.add(img.file);
                    } else {
                        dt.items.add(img.file);
                    }
                }
            });
            
            // Store new files for form submission
            const newFilesInput = document.getElementById('additionalImagesInput');
            if (newFilesInput && dt.files.length > 0) {
                // We'll handle this differently - store in hidden container
                this.storeNewFiles();
            }
        },
        
        storeNewFiles() {
            // Remove old hidden container
            let container = document.getElementById('newImagesContainer');
            if (container) container.remove();
            
            // Create new container
            container = document.createElement('div');
            container.id = 'newImagesContainer';
            container.style.display = 'none';
            
            this.images.forEach((img, idx) => {
                if (img.file) {
                    const input = document.createElement('input');
                    input.type = 'file';
                    input.name = idx === 0 && !this.images[0].isExisting ? 'image' : 'additional_images[]';
                    
                    // Clone file using DataTransfer
                    const dt = new DataTransfer();
                    dt.items.add(img.file);
                    input.files = dt.files;
                    
                    container.appendChild(input);
                }
            });
            
            document.querySelector('form').appendChild(container);
        },
        
        getFormData() {
            return {
                existingImages: this.images.filter(i => i.isExisting).map(i => i.filename),
                newFiles: this.images.filter(i => !i.isExisting).map(i => i.file)
            };
        }
    };
    
    // Initialize on DOM ready
    document.addEventListener('DOMContentLoaded', () => {
        MultiImageManager.init();
    });
</script>