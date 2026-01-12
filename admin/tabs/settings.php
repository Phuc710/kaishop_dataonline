<?php
// Settings Tab
$success = $error = '';

// Get messages from session
if (isset($_SESSION['settings_success'])) {
    $success = $_SESSION['settings_success'];
    unset($_SESSION['settings_success']);
}
if (isset($_SESSION['settings_error'])) {
    $error = $_SESSION['settings_error'];
    unset($_SESSION['settings_error']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'general') {
        $site_name = trim($_POST['site_name'] ?? '');
        $site_email = trim($_POST['site_email'] ?? '');
        $telegram_link = trim($_POST['telegram_link'] ?? '');
        $exchange_rate = floatval($_POST['exchange_rate'] ?? 24000);

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute(['site_name', $site_name, $site_name]);
            $stmt->execute(['site_email', $site_email, $site_email]);
            $stmt->execute(['telegram_link', $telegram_link, $telegram_link]);
            $stmt->execute(['exchange_rate', $exchange_rate, $exchange_rate]);

            // Save new settings
            $keys = ['contact_phone', 'site_slogan', 'social_zalo', 'social_tiktok', 'social_youtube', 'site_holiday_mode'];
            foreach ($keys as $key) {
                if (isset($_POST[$key])) {
                    $val = trim($_POST[$key]);
                    $stmt->execute([$key, $val, $val]);
                }
            }

            // Handle Logo Uploads
            $uploadDir = __DIR__ . '/../../assets/images/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
                error_log("Created upload directory: $uploadDir");
            }

            $logos = ['header_logo', 'footer_logo', 'tab_logo'];
            foreach ($logos as $logo) {
                // Log upload attempt
                if (isset($_FILES[$logo])) {
                    error_log("Upload attempt for $logo - Error code: " . $_FILES[$logo]['error']);
                    error_log("File name: " . $_FILES[$logo]['name']);
                    error_log("File size: " . $_FILES[$logo]['size']);
                    error_log("File type: " . $_FILES[$logo]['type']);
                }

                if (isset($_FILES[$logo]) && $_FILES[$logo]['error'] === UPLOAD_ERR_OK) {
                    $tmpName = $_FILES[$logo]['tmp_name'];
                    $name = basename($_FILES[$logo]['name']);
                    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

                    error_log("Processing $logo - Extension: $ext");

                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'ico'])) {
                        $newName = $logo . '.' . $ext;
                        $targetPath = $uploadDir . $newName;

                        error_log("Target path: $targetPath");
                        error_log("Upload dir writable: " . (is_writable($uploadDir) ? 'YES' : 'NO'));

                        if (move_uploaded_file($tmpName, $targetPath)) {
                            $dbPath = 'images/' . $newName; // Relative path for DB (asset helper adds /assets/)
                            $stmt->execute([$logo, $dbPath, $dbPath]);
                            error_log("✓ SUCCESS: $logo uploaded to $targetPath and saved to DB as $dbPath");
                        } else {
                            error_log("✗ FAILED: Could not move uploaded file for $logo");
                        }
                    } else {
                        error_log("✗ INVALID EXTENSION: $ext not allowed for $logo");
                    }
                } elseif (isset($_FILES[$logo]) && $_FILES[$logo]['error'] !== UPLOAD_ERR_NO_FILE) {
                    // Log specific upload errors
                    $errorMessages = [
                        UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
                        UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
                        UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                        UPLOAD_ERR_EXTENSION => 'PHP extension stopped the upload'
                    ];
                    $errorMsg = $errorMessages[$_FILES[$logo]['error']] ?? 'Unknown error';
                    error_log("✗ UPLOAD ERROR for $logo: $errorMsg (Code: " . $_FILES[$logo]['error'] . ")");
                }
            }

            $pdo->commit();

            // Hiện notify cho nút submit form (Lưu Cài Đặt, Cập Nhật Tỷ Giá)
            $_SESSION['settings_success'] = 'Cập nhật cài đặt thành công!';
            header('Location: ?tab=settings');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Lỗi: ' . $e->getMessage();
        }
    }

    if ($action === 'toggle_setting') {
        $setting_key = trim($_POST['setting_key'] ?? '');
        $setting_value = trim($_POST['setting_value'] ?? '0');

        try {
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$setting_key, $setting_value, $setting_value]);

            // If maintenance mode is being enabled, save start time
            if ($setting_key === 'maintenance_mode' && $setting_value === '1') {
                $start_time = $_POST['maintenance_start_time'] ?? time();
                $stmt->execute(['maintenance_start_time', $start_time, $start_time]);
            }

            // If maintenance mode is being disabled, clear start time
            if ($setting_key === 'maintenance_mode' && $setting_value === '0') {
                $stmt->execute(['maintenance_start_time', '0', '0']);
            }

            // AJAX call - No redirect, no notify, just return success
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || isset($_POST['ajax'])) {
                echo json_encode(['success' => true]);
                exit;
            }

            // Fallback for non-AJAX: redirect without notify
            header('Location: ?tab=settings');
            exit;
        } catch (Exception $e) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || isset($_POST['ajax'])) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                exit;
            }
            $error = 'Lỗi: ' . $e->getMessage();
        }
    }
}

// Get settings
$settings = [];
$result = $pdo->query("SELECT * FROM settings");
while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
?>

<div class="page-header">
    <div>
        <h1><i class="fas fa-cog"></i> Cài Đặt Hệ Thống</h1>
        <p>Quản lý cấu hình website</p>
    </div>
</div>

<?php if ($success): ?>
    <script>
        if (window.notify) {
            notify.success('<?= $success ?>');
        }
    </script>
<?php endif; ?>

<?php if ($error): ?>
    <script>
        if (window.notify) {
            notify.error('<?= $error ?>');
        }
    </script>
<?php endif; ?>

<div class="form-grid" style="grid-template-columns: 1fr 1fr; gap: 1.5rem;">
    <!-- LEFT COLUMN -->
    <div>
        <!-- General Settings -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-globe"></i> Cài Đặt Chung</h3>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="general">

                <div class="form-group">
                    <label><i class="fas fa-tag"></i> Tên Website</label>
                    <input type="text" name="site_name" class="form-control"
                        value="<?= e($settings['site_name'] ?? SITE_NAME) ?>" required>
                    <small style="color:#64748b">Tên hiển thị của website</small>
                </div>

                <!-- Holiday Mode Setting -->
                <div class="form-group">
                    <label><i class="fas fa-gift"></i> Chế Độ Lễ Hội (Holiday Mode)</label>
                    <select name="site_holiday_mode" class="form-control">
                        <?php
                        require_once __DIR__ . '/../../includes/HolidayModeManager.php';
                        $currentMode = $settings['site_holiday_mode'] ?? 'normal';
                        foreach (HolidayModeManager::getModes() as $key => $label):
                            ?>
                            <option value="<?= $key ?>" <?= $currentMode === $key ? 'selected' : '' ?>>
                                <?= $label ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small style="color:#64748b">Thay đổi giao diện và hiệu ứng toàn trang (Tết, Noel...)</small>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-envelope"></i> Email Hỗ Trợ</label>
                    <input type="email" name="site_email" class="form-control"
                        value="<?= e($settings['site_email'] ?? '') ?>" required>
                    <small style="color:#64748b">Email liên hệ và hỗ trợ khách hàng</small>
                </div>

                <div class="form-group">
                    <label><i class="fab fa-telegram"></i> Telegram Link</label>
                    <input type="text" name="telegram_link" class="form-control"
                        value="<?= e($settings['telegram_link'] ?? 'https://t.me/Biinj') ?>"
                        placeholder="https://t.me/YourChannel">
                    <small style="color:#64748b">L(ví dụ: https://t.me/Biinj)</small>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-phone"></i> Số điện thoại</label>
                    <input type="text" name="contact_phone" class="form-control"
                        value="<?= e($settings['contact_phone'] ?? '0812420710') ?>" placeholder="0812420710">
                    <small style="color:#64748b">(ví dụ: 0812420710)</small>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-share-alt"></i> Mạng Xã Hội</label>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
                        <div>
                            <label style="font-size:0.85rem;color:#94a3b8">Zalo Link</label>
                            <input type="text" name="social_zalo" class="form-control" placeholder="https://zalo.me/..."
                                value="<?= e($settings['social_zalo'] ?? '') ?>">
                        </div>
                        <div>
                            <label style="font-size:0.85rem;color:#94a3b8">TikTok Link</label>
                            <input type="text" name="social_tiktok" class="form-control"
                                placeholder="https://tiktok.com/@..."
                                value="<?= e($settings['social_tiktok'] ?? '') ?>">
                        </div>
                        <div>
                            <label style="font-size:0.85rem;color:#94a3b8">YouTube Link</label>
                            <input type="text" name="social_youtube" class="form-control"
                                placeholder="https://youtube.com/..."
                                value="<?= e($settings['social_youtube'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <!-- Header Logo -->
                <div class="form-group">
                    <label><i class="fas fa-image"></i> Header Logo (Website)</label>
                    <div style="display:flex;gap:1rem;align-items:center">
                        <input type="file" name="header_logo" id="header-logo-upload" accept="image/*"
                            style="display:none" onchange="previewImage(this, 'header-logo-preview')">
                        <button type="button" onclick="document.getElementById('header-logo-upload').click()"
                            class="btn btn-secondary">
                            <i class="fas fa-upload"></i> Chọn Logo Header
                        </button>
                        <div id="header-logo-preview"
                            style="width:100px;height:50px;border:2px dashed rgba(139,92,246,0.3);border-radius:8px;display:flex;align-items:center;justify-content:center;color:#64748b;overflow:hidden">
                            <?php if (!empty($settings['header_logo'])): ?>
                                <img src="<?= BASE_URL . '/assets/' . $settings['header_logo'] ?>"
                                    style="max-width:100%;max-height:100%;object-fit:contain">
                            <?php else: ?>
                                <i class="fas fa-image"></i>
                            <?php endif; ?>
                        </div>
                    </div>
                    <small style="color:#64748b">Logo hiển thị trên thanh menu (Transparent PNG/GIF)</small>
                </div>

                <!-- Footer Logo -->
                <div class="form-group">
                    <label><i class="fas fa-image"></i> Footer Logo (Chân Trang)</label>
                    <div style="display:flex;gap:1rem;align-items:center">
                        <input type="file" name="footer_logo" id="footer-logo-upload" accept="image/*"
                            style="display:none" onchange="previewImage(this, 'footer-logo-preview')">
                        <button type="button" onclick="document.getElementById('footer-logo-upload').click()"
                            class="btn btn-secondary">
                            <i class="fas fa-upload"></i> Chọn Logo Footer
                        </button>
                        <div id="footer-logo-preview"
                            style="width:100px;height:50px;border:2px dashed rgba(139,92,246,0.3);border-radius:8px;display:flex;align-items:center;justify-content:center;color:#64748b;overflow:hidden">
                            <?php if (!empty($settings['footer_logo'])): ?>
                                <img src="<?= BASE_URL . '/assets/' . $settings['footer_logo'] ?>"
                                    style="max-width:100%;max-height:100%;object-fit:contain">
                            <?php else: ?>
                                <i class="fas fa-image"></i>
                            <?php endif; ?>
                        </div>
                    </div>
                    <small style="color:#64748b">Logo hiển thị dưới chân trang</small>
                </div>

                <!-- Tab Logo -->
                <div class="form-group">
                    <label><i class="fas fa-icons"></i> Tab Logo (Favicon)</label>
                    <div style="display:flex;gap:1rem;align-items:center">
                        <input type="file" name="tab_logo" id="tab-logo-upload" accept="image/*" style="display:none"
                            onchange="previewImage(this, 'tab-logo-preview')">
                        <button type="button" onclick="document.getElementById('tab-logo-upload').click()"
                            class="btn btn-secondary">
                            <i class="fas fa-upload"></i> Chọn Favicon
                        </button>
                        <div id="tab-logo-preview"
                            style="width:50px;height:50px;border:2px dashed rgba(139,92,246,0.3);border-radius:8px;display:flex;align-items:center;justify-content:center;color:#64748b;overflow:hidden">
                            <?php if (!empty($settings['tab_logo'])): ?>
                                <img src="<?= BASE_URL . '/assets/' . $settings['tab_logo'] ?>"
                                    style="max-width:100%;max-height:100%;object-fit:contain">
                            <?php else: ?>
                                <i class="fas fa-image"></i>
                            <?php endif; ?>
                        </div>
                    </div>
                    <small style="color:#64748b">Icon hiển thị trên tab trình duyệt (Vuông, PNG/ICO)</small>
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-save"></i> Lưu Cài Đặt
                </button>
            </form>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-top: 1.5rem;">

            </div>

        </div>
    </div>

    <!-- RIGHT COLUMN -->
    <div>
        <!-- Currency Settings -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-dollar-sign"></i> Cài Đặt Tiền Tệ</h3>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="general">
                <input type="hidden" name="site_name" value="<?= e($settings['site_name'] ?? SITE_NAME) ?>">
                <input type="hidden" name="site_email" value="<?= e($settings['site_email'] ?? '') ?>">
                <input type="hidden" name="telegram_link"
                    value="<?= e($settings['telegram_link'] ?? 'https://t.me/Biinj') ?>">

                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <strong>AUTO Tranfer:</strong> Mặc định hiển thị VND.
                </div>

                <div class="form-group">
                    <label><i class="fas fa-exchange-alt"></i> Tỷ Giá USD/VND</label>
                    <div style="display:grid;grid-template-columns:1fr auto;gap:1rem">
                        <input type="number" name="exchange_rate" id="exchange-rate" class="form-control"
                            value="<?= e($settings['exchange_rate'] ?? 24000) ?>" min="1" step="1">
                        <div
                            style="padding:0.75rem 1.5rem;background:rgba(139,92,246,0.1);border-radius:8px;color:#8b5cf6;font-weight:600;white-space:nowrap">
                            1 USD = <span
                                id="rate-display"><?= number_format($settings['exchange_rate'] ?? 24000) ?></span> VND
                        </div>
                    </div>
                    <small style="color:#64748b">Nhập số bất kỳ (VD: 20000, 33000). Không bắt buộc điều kiện.</small>
                </div>

                <div class="form-group">
                    <label>Preview Chuyển Đổi</label>
                    <div
                        style="padding:1.5rem;background:rgba(16,185,129,0.1);border-radius:12px;border:1px solid #10b981">
                        <div style="font-weight:600;color:#f8fafc;margin-bottom:1rem">Ví dụ:</div>
                        <div style="display:grid;grid-template-columns:1fr auto 1fr;gap:1rem;align-items:center">
                            <div style="text-align:center">
                                <div style="font-size:1.5rem;font-weight:700;color:#10b981">100,000đ</div>
                                <div style="color:#64748b;font-size:0.9rem">VND (Mặc định)</div>
                            </div>
                            <div style="color:#8b5cf6;font-size:1.5rem">
                                <i class="fas fa-arrow-right"></i>
                            </div>
                            <div style="text-align:center">
                                <div style="font-size:1.5rem;font-weight:700;color:#10b981" id="preview-usd">$4.17</div>
                                <div style="color:#64748b;font-size:0.9rem">USD (English)</div>
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-save"></i> Cập Nhật Tỷ Giá
                </button>
            </form>
        </div>

        <!-- Features Settings -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-toggle-on"></i> Tính Năng</h3>
            </div>

            <div style="padding:1.5rem">
                <!-- 1. Allow Registration -->
                <div class="form-group">
                    <div style="display:flex;align-items:center;justify-content:space-between">
                        <div>
                            <div style="color:#f8fafc;font-weight:600;margin-bottom:0.25rem">
                                <i class="fas fa-user-plus"></i> Cho phép đăng ký tài khoản mới
                            </div>
                            <small style="color:#64748b">Người dùng có thể tự đăng ký tài khoản</small>
                        </div>
                        <label class="toggle">
                            <input type="checkbox" <?= ($settings['allow_registration'] ?? '1') == '1' ? 'checked' : '' ?>
                                onchange="toggleSetting('allow_registration', this.checked)">
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>

                <!-- 2. Enable Cart -->
                <div class="form-group">
                    <div style="display:flex;align-items:center;justify-content:space-between">
                        <div>
                            <div style="color:#f8fafc;font-weight:600;margin-bottom:0.25rem">
                                <i class="fas fa-shopping-cart"></i> Bật giỏ hàng
                            </div>
                            <small style="color:#64748b">Cho phép thêm nhiều sản phẩm vào giỏ</small>
                        </div>
                        <label class="toggle">
                            <input type="checkbox" <?= ($settings['enable_cart'] ?? '1') == '1' ? 'checked' : '' ?>
                                onchange="toggleSetting('enable_cart', this.checked)">
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>

                <!-- 3. Maintenance Mode -->
                <div class="form-group">
                    <div style="display:flex;align-items:center;justify-content:space-between">
                        <div>
                            <div style="color:#f8fafc;font-weight:600;margin-bottom:0.25rem">
                                <i class="fas fa-wrench"></i> Chế độ bảo trì
                            </div>
                            <small style="color:#64748b">Tạm khóa truy cập website sau 5 phút (trừ admin)</small>
                        </div>
                        <label class="toggle">
                            <input type="checkbox" id="maintenance-toggle" <?= ($settings['maintenance_mode'] ?? '0') == '1' ? 'checked' : '' ?> onchange="toggleMaintenance(this.checked)">
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>

                <!-- 4. Enable Voucher -->
                <div class="form-group">
                    <div style="display:flex;align-items:center;justify-content:space-between">
                        <div>
                            <div style="color:#f8fafc;font-weight:600;margin-bottom:0.25rem">
                                <i class="fas fa-ticket-alt"></i> Bật mã giảm giá (Voucher)
                            </div>
                            <small style="color:#64748b">Cho phép sử dụng voucher khi thanh toán</small>
                        </div>
                        <label class="toggle">
                            <input type="checkbox" <?= ($settings['enable_voucher'] ?? '1') == '1' ? 'checked' : '' ?>
                                onchange="toggleSetting('enable_voucher', this.checked)">
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>

                <!-- 5. Enable Product Reviews -->
                <div class="form-group" style="margin-bottom:0">
                    <div style="display:flex;align-items:center;justify-content:space-between">
                        <div>
                            <div style="color:#f8fafc;font-weight:600;margin-bottom:0.25rem">
                                <i class="fas fa-comments"></i> Bật bình luận sản phẩm
                            </div>
                            <small style="color:#64748b">Khách hàng có thể đánh giá sản phẩm</small>
                        </div>
                        <label class="toggle">
                            <input type="checkbox" <?= ($settings['enable_product_reviews'] ?? '1') == '1' ? 'checked' : '' ?> onchange="toggleSetting('enable_product_reviews', this.checked)">
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <script>
            // Toggle setting function with AJAX - No reload, no notify, no scroll
            async function toggleSetting(settingKey, isActive) {
                try {
                    const formData = new FormData();
                    formData.append('action', 'toggle_setting');
                    formData.append('setting_key', settingKey);
                    formData.append('setting_value', isActive ? '1' : '0');
                    formData.append('ajax', '1');

                    const response = await fetch(window.location.href, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    // Silent update - no notification, no reload, no scroll
                    if (!response.ok) {
                        console.error('Toggle failed');
                    }
                } catch (error) {
                    console.error('Toggle error:', error);
                }
            }

            // Maintenance Mode Toggle with Confirm & 5-minute countdown
            async function toggleMaintenance(isActive) {
                const toggle = document.getElementById('maintenance-toggle');

                if (isActive) {
                    // Confirm before enabling
                    let confirmed = false;

                    if (window.notify && typeof window.notify.confirm === 'function') {
                        confirmed = await notify.confirm({
                            type: 'warning',
                            title: '⚠️ Xác Nhận Bật Bảo Trì',
                            message: 'Hệ thống sẽ thông báo cho toàn bộ users và tự động kick sau 5 phút. Chỉ admin có thể truy cập.',
                            confirmText: '✓ Bật Bảo Trì',
                            cancelText: '✕ Hủy'
                        });
                    } else {
                        confirmed = confirm('⚠️ Bật chế độ bảo trì?\n\nHệ thống sẽ thông báo cho toàn bộ users và tự động kick sau 5 phút.');
                    }

                    if (!confirmed) {
                        toggle.checked = false;
                        return;
                    }

                    // Enable maintenance with countdown start time
                    const formData = new FormData();
                    formData.append('action', 'toggle_setting');
                    formData.append('setting_key', 'maintenance_mode');
                    formData.append('setting_value', '1');
                    formData.append('ajax', '1');

                    // Save start time
                    const startTime = Math.floor(Date.now() / 1000);
                    formData.append('maintenance_start_time', startTime);

                    const response = await fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    });

                    if (response.ok) {
                        if (window.notify) {
                            notify.success('Đã bật chế độ bảo trì', 'Users sẽ nhận thông báo countdown 5 phút');
                        }
                    } else {
                        toggle.checked = false;
                        if (window.notify) {
                            notify.error('Lỗi khi bật bảo trì');
                        }
                    }
                } else {
                    // Disable maintenance immediately
                    await toggleSetting('maintenance_mode', false);
                    await toggleSetting('maintenance_start_time', '0');

                    if (window.notify) {
                        notify.success('Đã tắt chế độ bảo trì');
                    }
                }
            }
        </script>
    </div>

    <script>
        // Update exchange rate preview
        document.getElementById('exchange-rate').addEventListener('input', function () {
            const rate = parseFloat(this.value) || 24000;
            document.getElementById('rate-display').textContent = new Intl.NumberFormat('vi-VN').format(rate);

            const vnd = 100000;
            const usd = vnd / rate;
            document.getElementById('preview-usd').textContent = '$' + usd.toFixed(2);
        });

        // Image preview helper
        function previewImage(input, previewId) {
            const file = input.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    document.getElementById(previewId).innerHTML = `<img src="${e.target.result}" style="max-width:100%;max-height:100%;object-fit:contain">`;
                }
                reader.readAsDataURL(file);
            }
        }
    </script>