<?php
require_once __DIR__ . '/../config/config.php';

if (!isLoggedIn()) {
    redirect(url('auth'));
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    try {
        $user_id = $_SESSION['user_id'];

        // Check if user has pending tickets
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE user_id = ? AND status != 'closed'");
        $stmt->execute([$user_id]);
        $pending_count = $stmt->fetchColumn();

        if ($pending_count > 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Bạn còn ticket chưa được xử lý xong. Vui lòng đợi ticket hiện tại được đóng trước khi tạo ticket mới.'
            ]);
            exit;
        }

        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');
        $attachment = null;

        if (empty($subject) || empty($message)) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin']);
            exit;
        }

        // Handle image upload
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $filename = $_FILES['attachment']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            if (in_array($ext, $allowed) && $_FILES['attachment']['size'] <= 5242880) { // 5MB
                $upload_dir = __DIR__ . '/../assets/images/tickets/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                $new_filename = 'ticket_' . uniqid() . '.' . $ext;
                $upload_path = $upload_dir . $new_filename;

                if (move_uploaded_file($_FILES['attachment']['tmp_name'], $upload_path)) {
                    $attachment = 'assets/images/tickets/' . $new_filename;
                }
            }
        }

        // Start transaction
        $pdo->beginTransaction();

        try {
            // Generate hex8 ID (8 character hexadecimal)
            $ticket_id = strtolower(bin2hex(random_bytes(4)));

            // Generate unique ticket number
            $ticket_number = 'TKT-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

            $stmt = $pdo->prepare("INSERT INTO tickets (id, user_id, ticket_number, subject, message, attachment, status, created_at) 
                                   VALUES (?, ?, ?, ?, ?, ?, 'open', NOW())");

            if ($stmt->execute([$ticket_id, $user_id, $ticket_number, $subject, $message, $attachment])) {
                $pdo->commit();
                $_SESSION['ticket_created'] = $ticket_id;
                echo json_encode(['success' => true, 'ticket_id' => $ticket_id, 'ticket_number' => $ticket_number]);
                exit;
            } else {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra khi lưu ticket']);
                exit;
            }
        } catch (Exception $insertError) {
            $pdo->rollBack();
            throw $insertError;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
        exit;
    }
}

// Check if user has pending tickets (for UI warning)
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE user_id = ? AND status != 'closed'");
$stmt->execute([$user_id]);
$has_pending = $stmt->fetchColumn() > 0;

$pageTitle = "Tạo Ticket mới - " . SITE_NAME;
include '../includes/header.php';
?>

<style>
    .ticket-create-container {
        min-height: 100vh;
        padding: 3rem 1rem;
        background: linear-gradient(135deg, rgba(15, 23, 42, 0.95), rgba(30, 41, 59, 0.95));
    }

    .ticket-header-card {
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.1), rgba(124, 58, 237, 0.05));
        border: 1px solid rgba(139, 92, 246, 0.2);
        border-radius: 16px;
        padding: 2rem;
        margin-bottom: 2rem;
        position: relative;
        overflow: hidden;
    }

    .ticket-header-card::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(139, 92, 246, 0.1) 0%, transparent 70%);
        animation: pulse 3s ease-in-out infinite;
    }

    @keyframes pulse {

        0%,
        100% {
            transform: scale(1);
            opacity: 0.5;
        }

        50% {
            transform: scale(1.1);
            opacity: 0.8;
        }
    }

    .ticket-form-card {
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.05), rgba(124, 58, 237, 0.02));
        border: 1px solid rgba(139, 92, 246, 0.2);
        border-radius: 16px;
        padding: 2rem;
        backdrop-filter: blur(10px);
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-label {
        display: block;
        color: #e2e8f0;
        font-weight: 600;
        margin-bottom: 0.5rem;
        font-size: 0.95rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .form-label svg {
        width: 18px;
        height: 18px;
        color: #8b5cf6;
    }

    .form-input {
        width: 100%;
        padding: 0.875rem 1rem;
        background: rgba(15, 23, 42, 0.6);
        border: 1px solid rgba(139, 92, 246, 0.2);
        border-radius: 10px;
        color: #e2e8f0;
        font-size: 0.95rem;
        transition: all 0.3s ease;
    }

    .form-input:focus {
        outline: none;
        border-color: #8b5cf6;
        box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
        background: rgba(15, 23, 42, 0.8);
    }

    .form-input::placeholder {
        color: #64748b;
    }

    textarea.form-input {
        resize: vertical;
        min-height: 150px;
    }

    .category-pills {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        margin-top: 0.5rem;
    }

    .category-pill {
        padding: 0.5rem 1rem;
        background: rgba(139, 92, 246, 0.1);
        border: 1px solid rgba(139, 92, 246, 0.2);
        border-radius: 20px;
        color: #a78bfa;
        font-size: 0.875rem;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .category-pill:hover {
        background: rgba(139, 92, 246, 0.2);
        border-color: #8b5cf6;
        transform: translateY(-2px);
    }

    .category-pill.active {
        background: linear-gradient(135deg, #8b5cf6, #7c3aed);
        color: white;
        border-color: #8b5cf6;
    }

    .info-box {
        background: rgba(59, 130, 246, 0.1);
        border: 1px solid rgba(59, 130, 246, 0.3);
        border-radius: 12px;
        padding: 1.25rem;
        margin-top: 1.5rem;
    }

    .info-box-header {
        display: flex;
        align-items: start;
        gap: 1rem;
    }

    .info-box-icon {
        width: 40px;
        height: 40px;
        background: rgba(59, 130, 246, 0.2);
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .info-box-icon svg {
        width: 20px;
        height: 20px;
        color: #60a5fa;
    }

    .info-list {
        list-style: none;
        padding: 0;
        margin: 0.75rem 0 0 0;
    }

    .info-list li {
        color: #94a3b8;
        font-size: 0.875rem;
        padding: 0.375rem 0;
        padding-left: 1.5rem;
        position: relative;
    }

    .info-list li::before {
        content: '✓';
        position: absolute;
        left: 0;
        color: #60a5fa;
        font-weight: bold;
    }

    .form-actions {
        display: flex;
        gap: 1rem;
        margin-top: 2rem;
    }

    .btn-submit {
        flex: 1;
        padding: 1rem 2rem;
        background: linear-gradient(135deg, #8b5cf6, #7c3aed);
        color: white;
        border: none;
        border-radius: 10px;
        font-weight: 600;
        font-size: 1rem;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(139, 92, 246, 0.4);
    }

    .btn-cancel {
        padding: 1rem 2rem;
        background: rgba(239, 68, 68, 0.1);
        color: #f87171;
        border: 1px solid rgba(239, 68, 68, 0.3);
        border-radius: 10px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .btn-cancel:hover {
        background: rgba(239, 68, 68, 0.2);
    }

    .char-counter {
        text-align: right;
        font-size: 0.75rem;
        color: #64748b;
        margin-top: 0.25rem;
    }

    .upload-area {
        border: 2px dashed rgba(139, 92, 246, 0.3);
        border-radius: 12px;
        padding: 2rem;
        text-align: center;
        background: rgba(139, 92, 246, 0.05);
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .upload-area:hover {
        border-color: #8b5cf6;
        background: rgba(139, 92, 246, 0.1);
    }

    .upload-area.dragover {
        border-color: #8b5cf6;
        background: rgba(139, 92, 246, 0.15);
        transform: scale(1.02);
    }

    .upload-icon {
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, #8b5cf6, #7c3aed);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1rem;
    }

    .upload-icon svg {
        width: 30px;
        height: 30px;
        color: white;
    }

    .file-input {
        display: none;
    }

    .file-preview {
        margin-top: 1rem;
        display: none;
    }

    .file-preview.active {
        display: block;
    }

    .preview-item {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem;
        background: rgba(139, 92, 246, 0.1);
        border: 1px solid rgba(139, 92, 246, 0.2);
        border-radius: 8px;
    }

    .preview-image {
        width: 80px;
        height: 80px;
        object-fit: cover;
        border-radius: 8px;
    }

    .preview-info {
        flex: 1;
    }

    .preview-name {
        color: #e2e8f0;
        font-weight: 600;
        margin-bottom: 0.25rem;
    }

    .preview-size {
        color: #94a3b8;
        font-size: 0.875rem;
    }

    .remove-file {
        background: rgba(239, 68, 68, 0.1);
        color: #ef4444;
        border: 1px solid rgba(239, 68, 68, 0.3);
        border-radius: 6px;
        padding: 0.5rem 1rem;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .remove-file:hover {
        background: rgba(239, 68, 68, 0.2);
    }

    @keyframes spin {
        from {
            transform: rotate(0deg);
        }

        to {
            transform: rotate(360deg);
        }
    }

    .btn-submit:disabled {
        opacity: 0.7;
        cursor: not-allowed;
    }

    /* --- LIGHT THEME OVERRIDES --- */
    [data-theme="light"] .ticket-create-container {
        background: linear-gradient(135deg, #f8fafc, #e2e8f0);
    }

    [data-theme="light"] .ticket-header-card {
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(37, 99, 235, 0.05));
        border-color: rgba(59, 130, 246, 0.3);
    }

    [data-theme="light"] .ticket-header-card h1 {
        color: #0f172a !important;
    }

    [data-theme="light"] .ticket-header-card p {
        color: #475569 !important;
    }

    [data-theme="light"] .ticket-form-card {
        background: #ffffff;
        border: 1px solid #1f1f1f;
        box-shadow: none;
    }

    [data-theme="light"] .form-label {
        color: #0f172a !important;
        font-weight: 700;
    }

    [data-theme="light"] .form-input {
        background: #ffffff !important;
        border: 1px solid #1f1f1f !important;
        color: #0f172a !important;
    }

    [data-theme="light"] .form-input::placeholder {
        color: #94a3b8 !important;
    }

    [data-theme="light"] .form-input:focus {
        background: #ffffff !important;
        border-color: #3b82f6 !important;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
    }

    [data-theme="light"] .category-pill {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        color: #475569;
    }

    [data-theme="light"] .category-pill:hover {
        background: #f1f5f9;
        border-color: #3b82f6;
    }

    [data-theme="light"] .category-pill.active {
        background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        color: white;
        border-color: #3b82f6;
    }

    [data-theme="light"] .info-box {
        background: rgba(59, 130, 246, 0.05);
        border-color: rgba(59, 130, 246, 0.2);
    }

    [data-theme="light"] .info-box p {
        color: #1d4ed8 !important;
    }

    [data-theme="light"] .info-list li {
        color: #475569 !important;
    }

    [data-theme="light"] .info-list li::before {
        color: #3b82f6 !important;
    }

    [data-theme="light"] .upload-area {
        background: #f8fafc;
        border-color: rgba(59, 130, 246, 0.3);
    }

    [data-theme="light"] .upload-area:hover {
        background: #f1f5f9;
        border-color: #3b82f6;
    }

    [data-theme="light"] .upload-area p {
        color: #0f172a !important;
    }

    [data-theme="light"] .upload-area p:last-child {
        color: #64748b !important;
    }

    [data-theme="light"] .char-counter {
        color: #64748b !important;
    }

    [data-theme="light"] .preview-item {
        background: #f8fafc;
        border-color: rgba(59, 130, 246, 0.3);
    }

    [data-theme="light"] .preview-name {
        color: #0f172a !important;
    }

    [data-theme="light"] .preview-size {
        color: #64748b !important;
    }
</style>

<div class="ticket-create-container">
    <div class="container mx-auto px-4 max-w-4xl">
        <div class="ticket-header-card">
            <div style="position: relative; z-index: 1;">
                <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 0.5rem;">
                    <div>
                        <h1 style="font-size: 2rem; font-weight: 700; color: white; margin: 0;">🎫 Tạo Ticket Hỗ Trợ
                        </h1>
                        <p style="color: #94a3b8; margin: 0.25rem 0 0 0;">Chúng tôi sẵn sàng hỗ trợ bạn 24/7</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="ticket-form-card">
            <?php if ($has_pending): ?>
                <div
                    style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 12px; padding: 1.25rem; margin-bottom: 1.5rem;">
                    <div style="display: flex; align-items: start; gap: 1rem;">
                        <div
                            style="width: 40px; height: 40px; background: rgba(239, 68, 68, 0.2); border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <svg style="width: 20px; height: 20px; color: #f87171;" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div style="flex: 1;">
                            <p style="color: #f87171; font-weight: 600; margin: 0 0 0.5rem 0;">⚠️ Không thể tạo ticket mới
                            </p>
                            <p style="color: #fca5a5; font-size: 0.875rem; margin: 0;">
                                Bạn còn ticket chưa được xử lý xong. Vui lòng đợi ticket hiện tại được đóng trước khi tạo
                                ticket mới.
                                <br><br>
                                <a href="<?= url('user?tab=tickets') ?>"
                                    style="color: #f87171; text-decoration: underline; font-weight: 600;">
                                    👉 Xem tickets của bạn
                                </a>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <form id="ticketForm" <?= $has_pending ? 'style="pointer-events: none; opacity: 0.5;"' : '' ?>>
                <!-- Category Quick Select -->
                <div class="form-group">
                    <label class="form-label">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                        </svg>
                        Chọn loại vấn đề
                    </label>
                    <div class="category-pills">
                        <div class="category-pill" data-category="🛍️ Vấn đề đơn hàng">
                            🛍️ Vấn đề đơn hàng
                        </div>
                        <div class="category-pill" data-category="💳 Thanh toán">
                            💳 Thanh toán
                        </div>
                        <div class="category-pill" data-category="📦 Sản phẩm">
                            📦 Sản phẩm
                        </div>
                        <div class="category-pill" data-category="🔄 Hoàn tiền">
                            🔄 Hoàn tiền
                        </div>
                        <div class="category-pill" data-category="❓ Khác">
                            ❓ Khác
                        </div>
                    </div>
                </div>

                <!-- Subject -->
                <div class="form-group">
                    <label class="form-label" for="subject">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                        </svg>
                        Tiêu đề <span style="color: #ef4444;">*</span>
                    </label>
                    <input type="text" id="subject" name="subject" class="form-input"
                        placeholder="Ví dụ: Đơn hàng #12345 chưa nhận được hàng" maxlength="200" required>
                    <div class="char-counter"><span id="subjectCounter">0</span>/200</div>
                </div>

                <!-- Image Upload -->
                <div class="form-group">
                    <label class="form-label">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        Đính kèm hình ảnh <span style="color: #64748b; font-weight: normal;">(không bắt buộc)</span>
                    </label>
                    <div class="upload-area" id="uploadArea">
                        <div class="upload-icon">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                            </svg>
                        </div>
                        <p style="color: #e2e8f0; font-weight: 600; margin-bottom: 0.5rem;">
                            📸 Kéo thả hình ảnh vào đây
                        </p>
                        <p style="color: #94a3b8; font-size: 0.875rem; margin-bottom: 1rem;">
                            hoặc click để chọn file
                        </p>
                        <p style="color: #64748b; font-size: 0.75rem;">
                            Hỗ trợ: JPG, PNG, GIF, WEBP (Tối đa 5MB)
                        </p>
                    </div>
                    <input type="file" id="fileInput" name="attachment" class="file-input" accept="image/*">
                    <div class="file-preview" id="filePreview"></div>
                </div>

                <!-- Message -->
                <div class="form-group">
                    <label class="form-label" for="message">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Mô tả chi tiết <span style="color: #ef4444;">*</span>
                    </label>
                    <textarea id="message" name="message" class="form-input" placeholder="📝 Hãy mô tả rõ ràng vấn đề của bạn:
• Điều gì đã xảy ra?
• Bạn mong muốn điều gì?
• Hãy gửi cho tôi biết bạn cần giúp gì?" maxlength="2000" required></textarea>
                    <div class="char-counter"><span id="messageCounter">0</span>/2000</div>
                </div>

                <!-- Info Box -->
                <div class="info-box">
                    <div class="info-box-header">
                        <div style="flex: 1;">
                            <p style="color: #60a5fa; font-weight: 600; margin: 0 0 0.5rem 0;">✨ Lưu ý quan trọng</p>
                            <ul class="info-list">
                                <li>Thời gian phản hồi: 24 giờ (nhanh hơn vào giờ hành chính)</li>
                                <li>Cung cấp đầy đủ thông tin để xử lý nhanh chóng</li>
                                <li>Theo dõi ticket qua email hoặc trang quản lý ticket</li>
                                <li>Có thể gửi kèm ảnh chụp màn hình nếu cần</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-submit">
                        <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                        </svg>
                        Gửi Ticket Hỗ Trợ
                    </button>
                    <a href="<?= url('user?tab=tickets') ?>" class="btn-cancel">Hủy</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Category pills selection
    document.querySelectorAll('.category-pill').forEach(pill => {
        pill.addEventListener('click', function () {
            // Toggle active state
            document.querySelectorAll('.category-pill').forEach(p => p.classList.remove('active'));
            this.classList.add('active');

            // Auto-fill subject if empty
            const subjectInput = document.getElementById('subject');
            const category = this.dataset.category;
            if (!subjectInput.value.trim()) {
                subjectInput.value = category + ': ';
                subjectInput.focus();
                updateCounter('subject');
            }
        });
    });

    // Character counters
    function updateCounter(fieldName) {
        const field = document.getElementById(fieldName);
        const counter = document.getElementById(fieldName + 'Counter');
        if (field && counter) {
            counter.textContent = field.value.length;

            // Color based on length
            const maxLength = field.maxLength;
            const percentage = (field.value.length / maxLength) * 100;

            if (percentage > 90) {
                counter.style.color = '#ef4444';
            } else if (percentage > 70) {
                counter.style.color = '#f59e0b';
            } else {
                counter.style.color = '#64748b';
            }
        }
    }

    document.getElementById('subject')?.addEventListener('input', () => updateCounter('subject'));
    document.getElementById('message')?.addEventListener('input', () => updateCounter('message'));

    // File upload handling
    const uploadArea = document.getElementById('uploadArea');
    const fileInput = document.getElementById('fileInput');
    const filePreview = document.getElementById('filePreview');

    uploadArea.addEventListener('click', () => fileInput.click());

    // Drag and drop
    uploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadArea.classList.add('dragover');
    });

    uploadArea.addEventListener('dragleave', () => {
        uploadArea.classList.remove('dragover');
    });

    uploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadArea.classList.remove('dragover');

        const files = e.dataTransfer.files;
        if (files.length > 0) {
            handleFile(files[0]);
        }
    });

    fileInput.addEventListener('change', (e) => {
        if (e.target.files.length > 0) {
            handleFile(e.target.files[0]);
        }
    });

    function handleFile(file) {
        // Validate file type
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            alert('❌ Chỉ hỗ trợ file ảnh (JPG, PNG, GIF, WEBP)');
            return;
        }

        // Validate file size (5MB)
        if (file.size > 5242880) {
            alert('❌ Kích thước file quá lớn! Tối đa 5MB');
            return;
        }

        // Create preview
        const reader = new FileReader();
        reader.onload = (e) => {
            filePreview.innerHTML = `
            <div class="preview-item">
                <img src="${e.target.result}" class="preview-image" alt="Preview">
                <div class="preview-info">
                    <div class="preview-name">🖼️ ${file.name}</div>
                    <div class="preview-size">${(file.size / 1024).toFixed(2)} KB</div>
                </div>
                <button type="button" class="remove-file" onclick="removeFile()">
                    <i class="fas fa-times"></i> Xóa
                </button>
            </div>
        `;
            filePreview.classList.add('active');
            uploadArea.style.display = 'none';
        };
        reader.readAsDataURL(file);
    }

    function removeFile() {
        fileInput.value = '';
        filePreview.innerHTML = '';
        filePreview.classList.remove('active');
        uploadArea.style.display = 'block';
    }

    window.removeFile = removeFile;

    // Form submission - ĐƠN GIẢN, KHÔNG LOADING
    document.getElementById('ticketForm').addEventListener('submit', async (e) => {
        e.preventDefault();

        const formData = new FormData(e.target);
        const submitBtn = e.target.querySelector('.btn-submit');
        const originalText = submitBtn.innerHTML;

        // Disable button
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<svg style="width: 20px; height: 20px; animation: spin 1s linear infinite;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" opacity="0.25"></circle><path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" opacity="0.75"></path></svg> Đang gửi...';

        try {
            const response = await fetch('<?= url('ticket/create') ?>', {
                method: 'POST',
                body: formData
            });

            const text = await response.text();
            let result;

            try {
                result = JSON.parse(text);
            } catch (e) {
                console.error('Invalid JSON response:', text);
                throw new Error('Server trả về dữ liệu không hợp lệ');
            }

            if (result.success) {
                // THÀNH CÔNG - Chuyển đến trang danh sách tickets
                if (window.notify) {
                    notify.success('✅ Gửi ticket thành công!', `Mã ticket: ${result.ticket_number}`);
                }

                // Redirect sau 1s
                setTimeout(() => {
                    window.location.href = '<?= url('user') ?>?tab=tickets';
                }, 1000);
            } else {
                // THẤT BẠI - Không lưu, hiện lỗi
                if (window.notify) {
                    notify.error('❌ Gửi ticket thất bại!', result.message || 'Có lỗi xảy ra. Vui lòng thử lại.');
                } else {
                    alert('❌ ' + (result.message || 'Có lỗi xảy ra. Vui lòng thử lại.'));
                }

                // Re-enable button để user thử lại
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        } catch (error) {
            // LỖI KẾT NỐI - Không lưu
            console.error('Error:', error);

            if (window.notify) {
                notify.error('❌ Lỗi kết nối!', error.message || 'Không thể kết nối đến máy chủ. Vui lòng kiểm tra kết nối và thử lại.');
            } else {
                alert('❌ Lỗi: ' + (error.message || 'Không thể kết nối đến máy chủ'));
            }

            // Re-enable button để user thử lại
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    });
</script>

<?php include '../includes/footer.php'; ?>