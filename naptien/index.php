<?php
require_once __DIR__ . '/../config/config.php';

if (!isLoggedIn()) {
    redirect(url('auth?redirect=' . urlencode(url('naptien'))));
}

$user = getCurrentUser();
$currency = $_COOKIE['currency'] ?? 'VND';
$required_amount = $_GET['amount'] ?? 0;

// Get payment settings from database
$stmt = $pdo->query("SELECT setting_key, setting_value FROM payment_settings");
$settings = [];
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}


$pageTitle = "Nạp Tiền Nhanh Chóng - Mua ChatGPT, Gemini, Canva | KaiShop";
$pageDescription = 'Nạp tiền tự động 24/7 để mua tài khoản ChatGPT, Gemini, Canva, Netflix. Hỗ trợ ngân hàng, Momo, ZaloPay. Cộng tiền ngay lập tức!';
$pageKeywords = 'nạp tiền kaishop, nạp tiền mua chatgpt, nạp tiền mua gemini, nạp tiền mua canva, qr code nạp tiền, nạp tiền tự động, thanh toán online';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Modern Styles -->
<?php require_once __DIR__ . '/naptien-styles.php'; ?>

<!-- Spotlight Hover Effect (REMOVED) -->



<div class="deposit-page">
    <div class="content-container">
        <!-- Header -->
        <div class="page-header-section">
            <div class="page-badge">
                <img src="https://media.giphy.com/media/xje7ITeGqNAFWyvZ7a/giphy.gif" alt="Auto"
                    style="width: 20px; height: 20px; object-fit: contain;">
                <span>Hệ thống tự động 24/7</span>
            </div>
            <h1 class="page-title">Nạp Tiền Tài Khoản</h1>
            <p class="page-subtitle">
                Nạp tiền nhanh chóng để mua tài khoản ChatGPT Plus, Gemini Pro, Canva Pro, Netflix, Spotify. Hệ thống tự
                động 24/7, hỗ trợ mọi ngân hàng Việt Nam.
            </p>
        </div>

        <!-- Feature Cards -->
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon" style="background: rgba(34, 197, 94, 0.1); color: #22c55e;">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h3 class="feature-title">Tự Động 100%</h3>
                <p class="feature-desc">Tiền được cộng vào tài khoản ngay sau khi giao dịch hoàn tất thành công.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h3 class="feature-title">An Toàn & Bảo Mật</h3>
                <p class="feature-desc">Thông tin giao dịch được mã hóa và bảo vệ tuyệt đối an toàn.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon" style="background: rgba(249, 115, 22, 0.1); color: #f97316;">
                    <i class="fas fa-bolt"></i>
                </div>
                <h3 class="feature-title">Siêu Tốc Độ</h3>
                <p class="feature-desc">Thời gian xử lý trung bình chỉ từ 1-3 phút cho mỗi giao dịch.</p>
            </div>
        </div>

        <!-- Main Payment Form -->
        <div class="payment-card">
            <!-- Quick Amounts -->
            <label>Chọn nhanh:</label>
            <div class="quick-amounts-grid">
                <button class="amount-btn" onclick="setAmount(10000)">10.000</button>
                <button class="amount-btn" onclick="setAmount(20000)">20.000</button>
                <button class="amount-btn" onclick="setAmount(50000)">50.000</button>
                <button class="amount-btn" onclick="setAmount(100000)">100.000</button>
                <button class="amount-btn" onclick="setAmount(200000)">200.000</button>
                <button class="amount-btn" onclick="setAmount(500000)">500.000</button>
            </div>

            <!-- Manual Input -->
            <div class="input-area">
                <label>Hoặc nhập số tiền
                    khác:</label>
                <div class="currency-input-wrapper">
                    <input type="number" id="amount" class="currency-input" placeholder="0" min="10000"
                        value="<?= $required_amount > 0 ? $required_amount : '' ?>" oninput="validateAmount(this)"
                        onkeypress="if(event.key === 'Enter') createTransaction()">
                    <span class="currency-suffix">VND</span>
                </div>
                <p style="color: #64748b; font-size: 0.85rem; margin-top: 0.75rem;">
                    <i class="fas fa-info-circle"></i> Số tiền nạp tối thiểu: 10.000đ
                </p>
            </div>

            <!-- Create Button -->
            <button class="btn-submit" id="btnCreate" onclick="createTransaction()">
                <span>Tạo Giao Dịch</span>
                <i class="fas fa-arrow-right"></i>
            </button>

            <!-- Payment Info Details (Hidden initially) -->
            <div id="paymentDetails" class="payment-details">
                <div class="details-grid">
                    <!-- Left: QR -->
                    <div>
                        <div class="qr-wrapper" id="qrSection">
                            <img id="qrCodeImg" src="" alt="QR Code" class="qr-img">
                        </div>
                        <button class="btn-download-qr" onclick="downloadQR()">
                            <i class="fas fa-download"></i>
                            <span>Tải QR về máy</span>
                        </button>

                    </div>

                    <!-- Right: Info -->
                    <div>
                        <div class="timer-box">
                            <div style="color: #ef4444; font-size: 0.9rem; margin-bottom: 5px;">Thời gian còn lại</div>
                            <div class="timer-text" id="timerDisplay">05:00</div>
                        </div>

                        <div class="info-row">
                            <span class="info-label">Ngân hàng</span>
                            <span class="info-value">MB Bank <button class="btn-copy" onclick="copyText('MB Bank')"><i
                                        class="fas fa-copy"></i></button></span>
                        </div>

                        <div class="info-row">
                            <span class="info-label">Chủ tài khoản</span>
                            <span class="info-value">
                                <?= $settings['mbbank_account_name'] ?? 'ADMIN' ?>
                                <button class="btn-copy"
                                    onclick="copyText('<?= $settings['mbbank_account_name'] ?? 'ADMIN' ?>')"><i
                                        class="fas fa-copy"></i></button>
                            </span>
                        </div>

                        <div class="info-row">
                            <span class="info-label">Số tài khoản</span>
                            <span class="info-value">
                                <?= $settings['mbbank_account_number'] ?? '0000' ?>
                                <button class="btn-copy"
                                    onclick="copyText('<?= $settings['mbbank_account_number'] ?? '0000' ?>')"><i
                                        class="fas fa-copy"></i></button>
                            </span>
                        </div>

                        <div class="info-row" style="background: rgba(139, 92, 246, 0.1); border: none;">
                            <span class="info-label" style="color: #a78bfa;">Nội dung (Bắt buộc)</span>
                            <span class="info-value">
                                <span id="transactionCode" class="code-highlight">KAI...</span>
                                <button class="btn-copy" onclick="copyTransactionCode()"><i
                                        class="fas fa-copy"></i></button>
                            </span>
                        </div>

                        <div class="info-row">
                            <span class="info-label">Số tiền</span>
                            <span class="info-value" style="color: #10b981; font-size: 1.2rem;">
                                <span id="displayAmount">0</span>đ
                            </span>
                        </div>

                        <button class="cancel-btn" onclick="cancelTransaction()">
                            <i class="fas fa-times"></i> Hủy giao dịch
                        </button>
                    </div>
                </div>

                <!-- Note -->
                <div
                    style="margin-top: 1.5rem; background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.2); border-radius: 12px; padding: 1rem; font-size: 0.9rem; color: #94a3b8; line-height: 1.6;">
                    <strong style="color: #60a5fa;"><i class="fas fa-info-circle"></i> Lưu ý quan trọng:</strong><br>
                    • Vui lòng chuyển khoản đúng số tiền và nội dung chuyển khoản để hệ thống tự động xử lý.<br>
                    • Tuyệt đối không sửa đổi nội dung chuyển khoản.<br>
                    • Nếu sau 5 phút chưa nhận được tiền, vui lòng liên hệ Admin để được hỗ trợ.
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let currentTransaction = null;
    let timerInterval = null;
    const mbAccount = '<?= $settings['mbbank_account_number'] ?? '09696969690' ?>';
    const mbName = '<?= $settings['mbbank_account_name'] ?? 'NGUYEN THANH PHUC' ?>';

    function setAmount(val) {
        // Remove active class from all buttons
        document.querySelectorAll('.amount-btn').forEach(btn => btn.classList.remove('active'));

        // Set value and trigger validation
        const input = document.getElementById('amount');
        input.value = val;
        validateAmount(input);

        // Highlight clicked button
        event.target.classList.add('active');
    }

    function validateAmount(input) {
        // Highlight corresponding quick button if matches
        const val = parseInt(input.value);
        document.querySelectorAll('.amount-btn').forEach(btn => {
            if (parseInt(btn.textContent.replace(/\./g, '')) === val) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });
    }

    // Load existing transaction
    window.addEventListener('DOMContentLoaded', async function () {
        const savedTransaction = localStorage.getItem('currentTransaction');
        if (savedTransaction) {
            const transaction = JSON.parse(savedTransaction);
            const expiresAtTimestamp = transaction.expiresAtTimestamp || new Date(transaction.expires_at).getTime();
            const now = new Date().getTime();

            if (expiresAtTimestamp > now && transaction.status === 'pending') {
                currentTransaction = transaction;
                document.getElementById('amount').value = transaction.amount;
                showPaymentDetails(transaction);
                startTimer(expiresAtTimestamp);

                // Disable input area
                document.getElementById('amount').disabled = true;
                document.getElementById('btnCreate').style.display = 'none';
            } else {
                localStorage.removeItem('currentTransaction');
            }
        }

        // Auto focus amount
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('amount')) {
            const amt = parseInt(urlParams.get('amount'));
            if (amt > 0) {
                setAmount(amt);
                document.getElementById('amount').focus();
            }
        }
    });

    async function createTransaction() {
        const amount = parseFloat(document.getElementById('amount').value);

        if (!amount || amount < 10000) {
            notify.error('Lỗi', 'Vui lòng nhập số tiền tối thiểu 10.000đ');
            return;
        }

        const btn = document.getElementById('btnCreate');
        btn.disabled = true;
        btn.innerHTML = '<span class="loading-icon-inline"></span> Đang tạo...';

        try {
            const response = await fetch('<?= url('api/create-transaction.php') ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ amount: amount, currency: 'VND' })
            });

            const data = await response.json();

            if (data.success) {
                currentTransaction = data.transaction;
                currentTransaction.expiresAtTimestamp = new Date(data.transaction.expires_at).getTime();
                localStorage.setItem('currentTransaction', JSON.stringify(currentTransaction));

                showPaymentDetails(currentTransaction);
                startTimer(currentTransaction.expiresAtTimestamp);

                // Lock input
                document.getElementById('amount').disabled = true;
                btn.style.display = 'none';
            } else {
                notify.error('Lỗi', data.message);
                btn.disabled = false;
                btn.innerHTML = '<span>Tạo Giao Dịch</span><i class="fas fa-arrow-right"></i>';
            }
        } catch (error) {
            console.error(error);
            notify.error('Lỗi', 'Không thể kết nối đến máy chủ');
            btn.disabled = false;
            btn.innerHTML = '<span>Tạo Giao Dịch</span><i class="fas fa-arrow-right"></i>';
        }
    }

    function showPaymentDetails(transaction) {
        const details = document.getElementById('paymentDetails');
        details.style.display = 'block';

        // Update Info
        document.getElementById('transactionCode').textContent = transaction.transaction_code;
        document.getElementById('displayAmount').textContent = new Intl.NumberFormat('vi-VN').format(transaction.amount);

        // Generate QR
        const qrUrl = `https://img.vietqr.io/image/MB-${mbAccount}-compact.png?amount=${transaction.amount}&addInfo=${encodeURIComponent(transaction.transaction_code)}&accountName=${encodeURIComponent(mbName)}`;
        document.getElementById('qrCodeImg').src = qrUrl;

        // Scroll to QR section
        setTimeout(() => {
            const qrSection = document.getElementById('qrSection');
            if (qrSection) {
                qrSection.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }, 100);
    }

    async function cancelTransaction() {
        if (!currentTransaction) return;

        notify.confirm({
            type: 'warning',
            title: 'Hủy giao dịch?',
            message: 'Bạn có chắc chắn muốn hủy giao dịch này?',
            confirmText: 'Hủy giao dịch',
            cancelText: 'Không'
        }).then(async (confirmed) => {
            if (!confirmed) return;

            try {
                await fetch('<?= url('api/cancel-transaction.php') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ transaction_code: currentTransaction.transaction_code })
                });

                resetUI();
                notify.success('Thành công', 'Đã hủy giao dịch');

            } catch (error) {
                resetUI();
            }
        });
    }

    function resetUI() {
        localStorage.removeItem('currentTransaction');
        currentTransaction = null;
        clearInterval(timerInterval);

        document.getElementById('paymentDetails').style.display = 'none';
        document.getElementById('amount').disabled = false;
        const btn = document.getElementById('btnCreate');
        btn.style.display = 'flex';
        btn.disabled = false;
        btn.innerHTML = '<span>Tạo Giao Dịch</span><i class="fas fa-arrow-right"></i>';
    }

    function startTimer(expiryTime) {
        if (timerInterval) clearInterval(timerInterval);

        const update = () => {
            const now = new Date().getTime();
            const dist = expiryTime - now;

            if (dist < 0) {
                clearInterval(timerInterval);
                document.getElementById('timerDisplay').textContent = '00:00';
                notify.error('Hết hạn', 'Giao dịch đã hết hạn');
                resetUI();
                return;
            }

            const m = Math.floor((dist % (1000 * 60 * 60)) / (1000 * 60));
            const s = Math.floor((dist % (1000 * 60)) / 1000);
            document.getElementById('timerDisplay').textContent = `${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
        };

        update();
        timerInterval = setInterval(update, 1000);
        startPolling();
    }

    let pollingInterval = null;

    function startPolling() {
        if (pollingInterval) clearInterval(pollingInterval);
        if (!currentTransaction) return;

        pollingInterval = setInterval(async () => {
            if (!currentTransaction) {
                clearInterval(pollingInterval);
                return;
            }

            try {
                const response = await fetch(`<?= url('api/check-transaction-status.php') ?>?code=${encodeURIComponent(currentTransaction.transaction_code)}`);
                const data = await response.json();

                if (data.status === 'completed') {
                    clearInterval(pollingInterval);
                    clearInterval(timerInterval);

                    Swal.fire({
                        title: "Nạp tiền thành công!",
                        html: `<strong style="color:green;">+${new Intl.NumberFormat('vi-VN').format(data.amount)}đ</strong> vào tài khoản`,
                        icon: "success",
                        draggable: true
                    }).then(() => {
                        localStorage.removeItem('currentTransaction');
                        window.location.href = '<?= url('user?tab=transactions') ?>';
                    });
                }
            } catch (error) {
                console.error('Polling error:', error);
            }
        }, 1500);
    }

    function copyText(text) {
        navigator.clipboard.writeText(text);
        notify.success('Đã sao chép', text);
    }

    function copyTransactionCode() {
        copyText(document.getElementById('transactionCode').textContent);
    }

    function downloadQR() {
        const link = document.createElement('a');
        link.href = document.getElementById('qrCodeImg').src;
        link.download = `QR_${currentTransaction.transaction_code}.png`;
        link.target = '_blank';
        link.click();
    }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>