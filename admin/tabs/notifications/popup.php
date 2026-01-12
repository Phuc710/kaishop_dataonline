<!-- ======================== POPUP TAB ======================== -->
<div class="card">
    <form method="POST" id="popupForm">
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="type" value="popup">
        <input type="hidden" name="image" id="popup-image-path">
        <input type="hidden" name="image_width" id="popup-image-width" value="800">
        <input type="hidden" name="image_height" id="popup-image-height" value="500">
        <input type="hidden" name="content_mode" id="popup-content-mode" value="text">
        <input type="hidden" name="background_code" id="popup-background-code">

        <div class="form-grid">
            <div class="form-group" style="grid-column: 1 / -1;">
                <label><i class="fas fa-align-left"></i> Chế độ nội dung</label>
                <div style="display:flex;gap:1.5rem;margin-bottom:1rem">
                    <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer">
                        <input type="radio" name="mode_selector" value="text" checked onchange="switchPopupMode('text')"
                            style="cursor:pointer">
                        <span style="color:#f8fafc;font-weight:600">📝 Text thường</span>
                    </label>
                    <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer">
                        <input type="radio" name="mode_selector" value="html" onchange="switchPopupMode('html')"
                            style="cursor:pointer">
                        <span style="color:#f8fafc;font-weight:600">💻 Code HTML/CSS</span>
                    </label>
                </div>
            </div>

            <div class="form-group" style="grid-column: 1 / -1;">
                <label id="popup-content-label"><i class="fas fa-align-left"></i> Nội dung popup</label>
                <textarea name="title" id="popup-content-textarea" class="form-control" rows="8"
                    placeholder="VD: Chúc mừng năm mới 2026!&#10;&#10;Chúc ae năm mới vui vẻ, hạnh phúc! 🎉"></textarea>
                <small id="popup-content-help" style="color:#94a3b8;margin-top:0.5rem;display:block">
                    💡 Nhập text thường, hệ thống tự động thêm style đẹp
                </small>
            </div>

            <div class="form-group" id="background-code-group" style="grid-column: 1 / -1;display:none">
                <label><i class="fas fa-paint-brush"></i> Code nền (Background CSS)</label>
                <textarea id="background-code-textarea" class="form-control" rows="4"
                    placeholder="VD: background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);"></textarea>
                <small style="color:#94a3b8;margin-top:0.5rem;display:block">
                    🎨 Paste code CSS cho nền popup (chỉ hiện khi không upload ảnh)
                </small>
            </div>

            <div class="form-group" style="grid-column: 1 / -1;">
                <label><i class="fas fa-image"></i> Ảnh nền popup</label>
                <div style="display:flex;gap:1rem;align-items:start">
                    <div style="flex:1">
                        <input type="file" id="popup-image-upload" accept="image/*" class="form-control"
                            style="margin-bottom:0.5rem">
                        <small style="color:#94a3b8">Hỗ trợ: JPG, PNG, GIF, WEBP</small>
                        <div id="upload-progress" style="display:none;margin-top:0.5rem">
                            <div style="background:#1e293b;border-radius:8px;overflow:hidden;height:4px">
                                <div id="progress-bar"
                                    style="background:linear-gradient(90deg,#8b5cf6,#7c3aed);height:100%;width:0%;transition:width 0.3s">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="image-preview"
                        style="width:200px;height:125px;border:2px dashed rgba(139,92,246,0.3);border-radius:8px;overflow:hidden;display:none">
                        <img id="preview-img" style="width:100%;height:100%;object-fit:cover">
                    </div>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary" id="popup-submit-btn">
            <i class="fas fa-plus"></i> Thêm Popup
        </button>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Danh Sách Popup (<?= count($popups) ?>)</h3>
    </div>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Ảnh</th>
                    <th>Tiêu đề</th>
                    <th>Trạng thái</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($popups)): ?>
                    <tr>
                        <td colspan="5" style="text-align:center;padding:3rem;color:#64748b"><i class="fas fa-inbox"
                                style="font-size:3rem;margin-bottom:1rem"></i>
                            <p>Chưa có popup nào</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($popups as $popup): ?>
                        <tr>
                            <td>#<?= $popup['id'] ?></td>
                            <td>
                                <img src="<?= url($popup['image']) ?>"
                                    style="width:100px;height:auto;border-radius:8px;border:2px solid rgba(139,92,246,0.3)"
                                    onerror="this.style.display='none';this.parentElement.innerHTML='<div style=&quot;width:100px;height:62px;border:2px dashed rgba(139,92,246,0.3);border-radius:8px;display:flex;align-items:center;justify-content:center;color:#64748b&quot;><i class=&quot;fas fa-image&quot;></i></div>'">
                            </td>
                            <td>
                                <?php
                                $displayTitle = $popup['title'];
                                $isHtml = ($popup['content_mode'] ?? 'text') === 'html' || strpos($displayTitle, '<') !== false;
                                if (mb_strlen($displayTitle) > 50) {
                                    $displayTitle = mb_substr($displayTitle, 0, 50) . '...';
                                }
                                ?>
                                <div style="max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
                                    title="<?= e($popup['title']) ?>">
                                    <?php if ($isHtml): ?>
                                        <span class="badge badge-info"
                                            style="margin-right:0.5rem;background:#0ea5e9;padding:2px 6px;border-radius:4px;font-size:0.75rem">HTML</span>
                                        <code style="color:#d1d5db"><?= e($displayTitle) ?></code>
                                    <?php else: ?>
                                        <strong style="color:#f8fafc"><?= e($displayTitle) ?></strong>
                                    <?php endif; ?>
                                </div>
                                <small style="color:#94a3b8;display:block;margin-top:4px">
                                    <?php if (!empty($popup['background_code'])): ?>
                                        <i class="fas fa-paint-brush"></i> Có code nền
                                    <?php endif; ?>
                                </small>
                            </td>
                            <td>
                                <label class="toggle">
                                    <input type="checkbox" <?= $popup['is_active'] ? 'checked' : '' ?>
                                        onchange="togglePopup(<?= $popup['id'] ?>, this.checked)">
                                    <span class="toggle-slider"></span>
                                </label>
                            </td>
                            <td>
                                <div style="display:flex;gap:0.5rem">
                                    <button onclick="editPopup(<?= htmlspecialchars(json_encode($popup)) ?>)"
                                        class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="deletePopup(<?= $popup['id'] ?>)" class="btn btn-sm btn-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Popup Edit Modal -->
<div id="editPopupModal"
    style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.8);z-index:9999;padding:2rem;overflow-y:auto">
    <div
        style="max-width:800px;margin:0 auto;background:linear-gradient(135deg,#1e293b,#0f172a);padding:2rem;border-radius:16px;border:1px solid rgba(139,92,246,0.3)">
        <h2 style="color:#f8fafc;margin-bottom:1.5rem"><i class="fas fa-edit"></i> Sửa Popup</h2>
        <form method="POST" id="editPopupForm" enctype="multipart/form-data">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="type" value="popup">
            <input type="hidden" name="id" id="edit-popup-id">
            <input type="hidden" name="image" id="edit-popup-image">
            <input type="hidden" name="image_width" id="edit-popup-width">
            <input type="hidden" name="image_height" id="edit-popup-height">

            <div class="form-group">
                <label><i class="fas fa-align-left"></i> Nội dung popup</label>
                <textarea name="title" id="edit-popup-title" class="form-control" rows="8"
                    placeholder="Nhập text thường hoặc paste code HTML/CSS"></textarea>
                <small style="color:#94a3b8;margin-top:0.5rem;display:block">
                    💡 <strong>Hỗ trợ cả text thường và HTML/CSS</strong> - Paste code trực tiếp để tùy chỉnh style
                </small>
            </div>

            <div style="display:flex;gap:1rem;margin-top:1.5rem">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Lưu</button>
                <button type="button" onclick="closePopupModal()" class="btn btn-secondary">Hủy</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Mode switching function
    function switchPopupMode(mode) {
        const textarea = document.getElementById('popup-content-textarea');
        const helpText = document.getElementById('popup-content-help');
        const modeInput = document.getElementById('popup-content-mode');

        modeInput.value = mode;

        if (mode === 'text') {
            textarea.placeholder = 'VD: Chúc mừng năm mới 2026!\n\nChúc ae năm mới vui vẻ, hạnh phúc! 🎉';
            helpText.innerHTML = '💡 Nhập text thường, hệ thống tự động thêm style đẹp';
        } else {
            textarea.placeholder = '<h1 class="popup-gradient-text">\n    CHÚC MỪNG NĂM MỚI 2026\n</h1>\n<p class="popup-text-white">\n    Chúc ae năm mới vui vẻ, hạnh phúc! 🎉\n</p>';
            helpText.innerHTML = '💻 <strong>Paste code HTML/CSS</strong> - Dùng class có sẵn: <code style="background:#1e293b;padding:2px 6px;border-radius:4px">popup-gradient-text</code>, <code style="background:#1e293b;padding:2px 6px;border-radius:4px">popup-text-white</code>, <code style="background:#1e293b;padding:2px 6px;border-radius:4px">popup-text-glow</code>';
        }
    }

    // Background code field sync and visibility
    const backgroundCodeTextarea = document.getElementById('background-code-textarea');
    const backgroundCodeGroup = document.getElementById('background-code-group');
    const popupImagePath = document.getElementById('popup-image-path');

    // Show background code field initially (no image)
    backgroundCodeGroup.style.display = 'block';

    // Sync textarea to hidden field
    backgroundCodeTextarea?.addEventListener('input', function () {
        document.getElementById('popup-background-code').value = this.value;
    });

    // Form submit handler to sync background code
    document.getElementById('popupForm')?.addEventListener('submit', function () {
        document.getElementById('popup-background-code').value = backgroundCodeTextarea.value;
    });

    // Image upload handler
    document.getElementById('popup-image-upload')?.addEventListener('change', async function (e) {
        const file = e.target.files[0];
        if (!file) return;

        // Show progress
        document.getElementById('upload-progress').style.display = 'block';
        document.getElementById('progress-bar').style.width = '30%';

        const formData = new FormData();
        formData.append('image', file);
        formData.append('width', 800);
        formData.append('height', 500);

        try {
            const response = await fetch(`${window.API_URL}/upload_popup_image`, {
                method: 'POST',
                body: formData
            });

            document.getElementById('progress-bar').style.width = '70%';
            const result = await response.json();

            if (result.success) {
                document.getElementById('progress-bar').style.width = '100%';
                document.getElementById('popup-image-path').value = result.path;
                document.getElementById('popup-image-width').value = result.width;
                document.getElementById('popup-image-height').value = result.height;

                // Hide background code field when image is uploaded
                document.getElementById('background-code-group').style.display = 'none';
                document.getElementById('background-code-textarea').value = '';
                document.getElementById('popup-background-code').value = '';

                // Show preview
                const preview = document.getElementById('image-preview');
                const img = document.getElementById('preview-img');
                img.src = window.APP_URL + '/' + result.path;
                preview.style.display = 'block';

                if (window.notify) {
                    notify.success('Thành công!', 'Upload ảnh thành công!');
                }

                setTimeout(() => {
                    document.getElementById('upload-progress').style.display = 'none';
                    document.getElementById('progress-bar').style.width = '0%';
                }, 1000);
            } else {
                throw new Error(result.error || 'Upload failed');
            }
        } catch (error) {
            document.getElementById('upload-progress').style.display = 'none';
            if (window.notify) {
                notify.error('Lỗi!', error.message || 'Không thể upload ảnh');
            }
        }
    });

    // Toggle popup active status (only one can be active)
    function togglePopup(id, isActive) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
        <input type="hidden" name="action" value="toggle">
        <input type="hidden" name="type" value="popup">
        <input type="hidden" name="id" value="${id}">
        <input type="hidden" name="is_active" value="${isActive ? 1 : 0}">
    `;
        document.body.appendChild(form);
        form.submit();
    }

    // Popup CRUD functions
    function editPopup(data) {
        document.getElementById('edit-popup-id').value = data.id;
        document.getElementById('edit-popup-title').value = data.title;
        document.getElementById('edit-popup-image').value = data.image;
        document.getElementById('edit-popup-width').value = data.image_width;
        document.getElementById('edit-popup-height').value = data.image_height;
        document.getElementById('editPopupModal').style.display = 'block';
    }

    function closePopupModal() {
        document.getElementById('editPopupModal').style.display = 'none';
    }

    async function deletePopup(id) {
        const confirmed = await notify.confirm({
            title: 'Xác nhận xóa popup',
            message: 'Bạn có chắc muốn xóa popup này? Ảnh cũng sẽ bị xóa.',
            type: 'warning',
            confirmText: 'Xóa',
            cancelText: 'Hủy'
        });

        if (confirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="type" value="popup">
            <input type="hidden" name="id" value="${id}">
        `;
            document.body.appendChild(form);
            form.submit();
        }
    }
</script>