<?php
// Admin Popup Management - 3 Options: Current, Off, Image
$current_template = get_setting('active_popup_template', '0');
$popup_image = get_setting('popup_custom_image', '');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['popup_template'])) {
    $template_id = $_POST['popup_template'];

    // Save template selection
    $stmt = $pdo->prepare("SELECT id FROM settings WHERE setting_key = 'active_popup_template'");
    $stmt->execute();
    if ($stmt->fetch()) {
        $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'active_popup_template'");
        $stmt->execute([$template_id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('active_popup_template', ?)");
        $stmt->execute([$template_id]);
    }

    // Handle image upload for option 2
    if ($template_id === '2' && isset($_FILES['popup_image']) && $_FILES['popup_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/../../../uploads/popup/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $ext = pathinfo($_FILES['popup_image']['name'], PATHINFO_EXTENSION);
        $filename = 'popup_' . time() . '.' . $ext;
        $target = $upload_dir . $filename;

        if (move_uploaded_file($_FILES['popup_image']['tmp_name'], $target)) {
            $image_path = 'uploads/popup/' . $filename;

            // Save image path to settings
            $stmt = $pdo->prepare("SELECT id FROM settings WHERE setting_key = 'popup_custom_image'");
            $stmt->execute();
            if ($stmt->fetch()) {
                $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'popup_custom_image'");
                $stmt->execute([$image_path]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('popup_custom_image', ?)");
                $stmt->execute([$image_path]);
            }
        }
    }

    $messages = [
        '0' => 'Đã tắt popup!',
        '1' => 'Đã bật popup mặc định!',
        '2' => 'Đã bật popup hình ảnh!'
    ];

    $_SESSION['notif_success'] = $messages[$template_id] ?? 'Đã lưu cài đặt!';
    header("Location: ?tab=notifications&subtab=popup");
    exit;
}
?>

<div class="card p-4">
    <h3 class="mb-4"><i class="fas fa-window-restore"></i> Cấu Hình Popup Trang Chủ</h3>

    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i> Popup sẽ hiển thị ngay khi khách truy cập vào trang chủ.
    </div>

    <form method="POST" action="" enctype="multipart/form-data">
        <div class="popup-template-grid">

            <!-- Option 1: Default Popup -->
            <label class="popup-template-option <?php echo $current_template == '1' ? 'active' : ''; ?>">
                <input type="radio" name="popup_template" value="1" <?php echo $current_template == '1' ? 'checked' : ''; ?>>
                <div class="template-card template-1">
                    <div class="template-preview">
                        <div class="preview-header">
                            <span class="badge bg-success">ĐANG HOẠT ĐỘNG</span>
                        </div>
                        <h5 class="text-uppercase fw-bold text-primary mb-1">Dịch Vụ Thiết Kế Web</h5>
                        <div class="text-danger fw-bold small">Khai Xuân 2026 - HELLO2026</div>
                        <div class="mt-2">
                            <span class="badge bg-danger">🔥 HOT</span>
                            <span class="badge bg-info"><i class="fab fa-telegram"></i> Telegram</span>
                        </div>
                    </div>
                    <div class="template-info">
                        <h4><i class="fas fa-check-circle text-success"></i> POPUP MẶC ĐỊNH</h4>
                        <p>Thông báo tham gia nhóm + Khai xuân</p>
                    </div>
                </div>
            </label>

            <!-- Option 0: Disable -->
            <label class="popup-template-option <?php echo $current_template == '0' ? 'active' : ''; ?>">
                <input type="radio" name="popup_template" value="0" <?php echo $current_template == '0' ? 'checked' : ''; ?>>
                <div class="template-card template-disabled">
                    <div class="template-icon">
                        <i class="fas fa-ban"></i>
                    </div>
                    <div class="template-info">
                        <h4>TẮT POPUP</h4>
                        <p>Không hiển thị popup nào</p>
                    </div>
                </div>
            </label>

            <!-- Option 2: Custom Image -->
            <label class="popup-template-option <?php echo $current_template == '2' ? 'active' : ''; ?>">
                <input type="radio" name="popup_template" value="2" <?php echo $current_template == '2' ? 'checked' : ''; ?>>
                <div class="template-card template-image">
                    <div class="template-preview">
                        <?php if (!empty($popup_image)): ?>
                            <img src="<?= BASE_URL ?>/<?= htmlspecialchars($popup_image) ?>" alt="Popup Image"
                                class="img-fluid rounded" style="max-height: 120px;">
                        <?php else: ?>
                            <div class="upload-placeholder">
                                <i class="fas fa-image"></i>
                                <span>Chưa có ảnh</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="template-info">
                        <h4><i class="fas fa-image text-info"></i> POPUP HÌNH ẢNH</h4>
                        <p>Sử dụng hình ảnh tùy chỉnh</p>
                    </div>
                    <div class="image-upload-section mt-2">
                        <input type="file" name="popup_image" accept="image/*" class="form-control form-control-sm">
                    </div>
                </div>
            </label>

        </div>

        <button type="submit" class="btn btn-primary mt-4 btn-lg px-5">
            <i class="fas fa-save"></i> Lưu Cài Đặt
        </button>
    </form>
</div>

<style>
    /* Modern Clean UI for Popup Admin */
    .popup-template-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1.75rem;
        margin-top: 1.5rem;
    }

    .popup-template-option {
        cursor: pointer;
        display: block;
        position: relative;
    }

    .popup-template-option input[type="radio"] {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    /* Clean Card Design */
    .template-card {
        background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
        border: 2px solid #e2e8f0;
        border-radius: 16px;
        padding: 1.75rem;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        height: 100%;
        position: relative;
        overflow: hidden;
    }

    .template-card::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #3b82f6 0%, #8b5cf6 100%);
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .template-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
        border-color: #cbd5e1;
    }

    .template-card:hover::after {
        opacity: 0.5;
    }

    /* Active State */
    .popup-template-option.active .template-card,
    .popup-template-option input:checked+.template-card {
        border-color: #3b82f6;
        background: linear-gradient(145deg, #eff6ff 0%, #dbeafe 100%);
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15),
            0 8px 24px rgba(59, 130, 246, 0.2);
        transform: translateY(-2px);
    }

    .popup-template-option.active .template-card::after,
    .popup-template-option input:checked+.template-card::after {
        opacity: 1;
    }

    /* Check Badge */
    .popup-template-option.active .template-card::before,
    .popup-template-option input:checked+.template-card::before {
        content: '✓';
        position: absolute;
        top: 16px;
        right: 16px;
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: #ffffff;
        font-size: 14px;
        font-weight: 700;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
        animation: checkPop 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        z-index: 5;
    }

    @keyframes checkPop {
        0% {
            transform: scale(0);
            opacity: 0;
        }

        50% {
            transform: scale(1.1);
        }

        100% {
            transform: scale(1);
            opacity: 1;
        }
    }

    /* Disabled Template */
    .template-disabled {
        border-style: dashed;
        border-color: #cbd5e1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-height: 200px;
        text-align: center;
        background: linear-gradient(145deg, #f8fafc 0%, #f1f5f9 100%);
    }

    .template-disabled .template-icon {
        font-size: 3rem;
        color: #94a3b8;
        margin-bottom: 1rem;
        opacity: 0.7;
    }

    .popup-template-option:hover .template-disabled .template-icon {
        color: #64748b;
        opacity: 1;
    }

    /* Template Preview */
    .template-1 .template-preview,
    .template-image .template-preview {
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        padding: 1.25rem;
        text-align: center;
        margin-bottom: 1.25rem;
        min-height: 140px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        position: relative;
        overflow: hidden;
    }

    .template-1 .template-preview::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.05) 0%, rgba(139, 92, 246, 0.05) 100%);
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .popup-template-option:hover .template-1 .template-preview::before {
        opacity: 1;
    }

    .preview-header {
        margin-bottom: 0.75rem;
    }

    .preview-header .badge {
        padding: 0.35rem 0.75rem;
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.5px;
        border-radius: 6px;
    }

    /* Upload Placeholder */
    .upload-placeholder {
        display: flex;
        flex-direction: column;
        align-items: center;
        color: #94a3b8;
        padding: 1rem;
    }

    .upload-placeholder i {
        font-size: 2.5rem;
        margin-bottom: 0.75rem;
        opacity: 0.6;
    }

    .upload-placeholder span {
        font-size: 0.9rem;
        font-weight: 500;
    }

    /* Template Info */
    .template-info {
        text-align: center;
    }

    .template-info h4 {
        color: #1e293b;
        font-size: 1.1rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
        letter-spacing: 0.3px;
    }

    .template-info h4 i {
        margin-right: 0.5rem;
        font-size: 1rem;
    }

    .template-info p {
        color: #64748b;
        font-size: 0.9rem;
        margin: 0;
        line-height: 1.5;
    }

    /* Image Upload Section */
    .image-upload-section {
        border-top: 2px solid #e5e7eb;
        padding-top: 1rem;
        margin-top: 1rem;
    }

    .image-upload-section .form-control {
        border-radius: 8px;
        border: 2px dashed #cbd5e1;
        padding: 0.6rem 0.75rem;
        transition: all 0.2s ease;
        background: #f8fafc;
    }

    .image-upload-section .form-control:hover {
        border-color: #3b82f6;
        background: #ffffff;
    }

    .image-upload-section .form-control:focus {
        border-style: solid;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        background: #ffffff;
    }

    /* Save Button Enhancement */
    .btn-primary.btn-lg {
        border-radius: 12px;
        padding: 0.85rem 2.5rem;
        font-weight: 600;
        font-size: 1rem;
        letter-spacing: 0.5px;
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        border: none;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        transition: all 0.3s ease;
    }

    .btn-primary.btn-lg:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    }

    .btn-primary.btn-lg:active {
        transform: translateY(0);
    }

    .btn-primary.btn-lg i {
        margin-right: 0.5rem;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .popup-template-grid {
            grid-template-columns: 1fr;
            gap: 1.25rem;
        }

        .template-card {
            padding: 1.5rem;
        }

        .template-disabled {
            min-height: 160px;
        }
    }
</style>