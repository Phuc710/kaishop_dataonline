<?php
// Vouchers Management Tab
$success = $error = '';

// Get messages from session
if (isset($_SESSION['voucher_success'])) {
    $success = $_SESSION['voucher_success'];
    unset($_SESSION['voucher_success']);
}
if (isset($_SESSION['voucher_error'])) {
    $error = $_SESSION['voucher_error'];
    unset($_SESSION['voucher_error']);
}

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Add new voucher
    if ($action === 'add') {
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $discount_type = $_POST['discount_type'] ?? 'percentage';
        $discount_value = floatval($_POST['discount_value'] ?? 0);
        $min_amount = floatval($_POST['min_amount'] ?? 0);
        $max_discount = floatval($_POST['max_discount'] ?? 0);
        $usage_limit = intval($_POST['usage_limit'] ?? 0);
        $valid_from = $_POST['valid_from'] ?? null;
        $valid_until = $_POST['valid_until'] ?? null;
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $applicable_products = $_POST['applicable_products'] ?? null;
        
        if (empty($code)) {
            $error = 'Mã voucher không được để trống!';
        } else {
            try {
                $check = $pdo->prepare("SELECT id FROM vouchers WHERE code = ?");
                $check->execute([$code]);
                if ($check->fetch()) {
                    $error = 'Mã voucher đã tồn tại!';
                } else {
                    $stmt = $pdo->prepare("INSERT INTO vouchers (code, discount_type, discount_value, min_amount, max_discount, usage_limit, valid_from, valid_until, is_active, applicable_products) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$code, $discount_type, $discount_value, $min_amount, $max_discount, $usage_limit, $valid_from, $valid_until, $is_active, $applicable_products]);
                    
                    $_SESSION['voucher_success'] = 'Thêm voucher thành công!';
                    header('Location: ?tab=vouchers');
                    exit;
                }
            } catch (Exception $e) {
                $error = 'Lỗi: ' . $e->getMessage();
            }
        }
    }
    
    // Update voucher
    if ($action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $discount_type = $_POST['discount_type'] ?? 'percentage';
        $discount_value = floatval($_POST['discount_value'] ?? 0);
        $min_amount = floatval($_POST['min_amount'] ?? 0);
        $max_discount = floatval($_POST['max_discount'] ?? 0);
        $usage_limit = intval($_POST['usage_limit'] ?? 0);
        $valid_from = $_POST['valid_from'] ?? null;
        $valid_until = $_POST['valid_until'] ?? null;
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $applicable_products = $_POST['applicable_products'] ?? null;
        
        try {
            $stmt = $pdo->prepare("UPDATE vouchers SET code=?, discount_type=?, discount_value=?, min_amount=?, max_discount=?, usage_limit=?, valid_from=?, valid_until=?, is_active=?, applicable_products=? WHERE id=?");
            $stmt->execute([$code, $discount_type, $discount_value, $min_amount, $max_discount, $usage_limit, $valid_from, $valid_until, $is_active, $applicable_products, $id]);
            
            $_SESSION['voucher_success'] = 'Cập nhật voucher thành công!';
            header('Location: ?tab=vouchers');
            exit;
        } catch (Exception $e) {
            $error = 'Lỗi: ' . $e->getMessage();
        }
    }
    
    // Delete voucher
    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        try {
            $stmt = $pdo->prepare("DELETE FROM vouchers WHERE id=?");
            $stmt->execute([$id]);
            
            $_SESSION['voucher_success'] = 'Xóa voucher thành công!';
            header('Location: ?tab=vouchers');
            exit;
        } catch (Exception $e) {
            $error = 'Lỗi: ' . $e->getMessage();
        }
    }
    
    // Toggle active status
    if ($action === 'toggle_active') {
        $id = intval($_POST['id'] ?? 0);
        try {
            $stmt = $pdo->prepare("UPDATE vouchers SET is_active = NOT is_active WHERE id=?");
            $stmt->execute([$id]);
            
            $_SESSION['voucher_success'] = 'Cập nhật trạng thái thành công!';
            header('Location: ?tab=vouchers');
            exit;
        } catch (Exception $e) {
            $error = 'Lỗi: ' . $e->getMessage();
        }
    }
}

// Get all vouchers
$vouchers = $pdo->query("SELECT * FROM vouchers ORDER BY created_at DESC")->fetchAll();

// Get all products for selection
$products = $pdo->query("SELECT id, name, image FROM products WHERE is_active = 1 ORDER BY name")->fetchAll();

// Get statistics
$total_vouchers = count($vouchers);
$active_vouchers = count(array_filter($vouchers, fn($v) => $v['is_active']));
$total_usage = $pdo->query("SELECT SUM(used_count) FROM vouchers")->fetchColumn() ?? 0;

// Prepare voucher data for AG Grid
$voucherGridData = array_map(function($v) use ($products) {
    $applicableIds = $v['applicable_products'] ? json_decode($v['applicable_products'], true) : null;
    $applicableCount = $applicableIds ? count($applicableIds) : 'Tất cả';
    
    return [
        'id' => $v['id'],
        'code' => $v['code'],
        'discount_type' => $v['discount_type'],
        'discount_value' => $v['discount_value'],
        'min_amount' => $v['min_amount'],
        'max_discount' => $v['max_discount'],
        'used_count' => $v['used_count'],
        'usage_limit' => $v['usage_limit'],
        'valid_from' => $v['valid_from'],
        'valid_until' => $v['valid_until'],
        'is_active' => $v['is_active'],
        'applicable_products' => $v['applicable_products'],
        'applicable_count' => $applicableCount,
        'created_at' => $v['created_at']
    ];
}, $vouchers);
?>

<div class="page-header">
    <div>
        <h1><i class="fas fa-ticket-alt"></i> Quản Lý Voucher</h1>
        <p>Tạo và quản lý mã giảm giá</p>
    </div>
    <button onclick="openAddModal()" class="btn btn-primary">
        <i class="fas fa-plus"></i> Thêm Voucher Mới
    </button>
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

<!-- Statistics -->
<div class="stats-grid" style="margin-bottom:2rem">
    <div class="stat-card">
        <div class="stat-card-header">
            <div>
                <div class="stat-value"><?= number_format($total_vouchers) ?></div>
                <div class="stat-label">Tổng Voucher</div>
            </div>
            <div class="stat-icon primary"><i class="fas fa-ticket-alt"></i></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-header">
            <div>
                <div class="stat-value"><?= number_format($active_vouchers) ?></div>
                <div class="stat-label">Đang Kích Hoạt</div>
            </div>
            <div class="stat-icon success"><i class="fas fa-check-circle"></i></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-header">
            <div>
                <div class="stat-value"><?= number_format($total_usage) ?></div>
                <div class="stat-label">Lượt Sử Dụng</div>
            </div>
            <div class="stat-icon warning"><i class="fas fa-chart-line"></i></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-header">
            <div>
                <div class="stat-value"><?= number_format($total_vouchers - $active_vouchers) ?></div>
                <div class="stat-label">Không Hoạt Động</div>
            </div>
            <div class="stat-icon danger"><i class="fas fa-times-circle"></i></div>
        </div>
    </div>
</div>

<style>
.voucher-table {
    width: 100%;
    border-collapse: collapse;
    background: #1e293b;
    border-radius: 12px;
    overflow: hidden;
}
.voucher-table th {
    background: linear-gradient(180deg, #334155 0%, #1e293b 100%);
    color: #f1f5f9;
    font-weight: 700;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 1rem 0.75rem;
    text-align: left;
    border-bottom: 2px solid #8b5cf6;
}
.voucher-table td {
    padding: 1rem 0.75rem;
    color: #e2e8f0;
    border-bottom: 1px solid #334155;
    vertical-align: middle;
}
.voucher-table tr:nth-child(odd) {
    background: #0f172a;
}
.voucher-table tr:hover {
    background: rgba(139, 92, 246, 0.15);
}
.voucher-table tr.inactive {
    opacity: 0.6;
}
.voucher-code { 
    color: #c4b5fd; 
    font-weight: 700; 
    font-size: 1.1rem;
    font-family: 'JetBrains Mono', 'Consolas', monospace;
    letter-spacing: 1.5px;
}
.discount-value { 
    color: #4ade80; 
    font-weight: 700;
    font-size: 1.1rem;
}
.badge-type { 
    padding: 6px 14px; 
    border-radius: 8px; 
    font-size: 0.8rem; 
    font-weight: 600;
    white-space: nowrap;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.badge-percent { 
    background: linear-gradient(135deg, rgba(6, 182, 212, 0.3), rgba(6, 182, 212, 0.1)); 
    color: #67e8f9; 
    border: 1px solid rgba(6, 182, 212, 0.5); 
}
.badge-fixed { 
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.3), rgba(16, 185, 129, 0.1)); 
    color: #6ee7b7; 
    border: 1px solid rgba(16, 185, 129, 0.5); 
}
.status-on { 
    background: linear-gradient(135deg, #22c55e, #16a34a); 
    color: #fff; 
    padding: 6px 16px; 
    border-radius: 8px; 
    font-size: 0.85rem;
    font-weight: 700;
}
.status-off { 
    background: linear-gradient(135deg, #ef4444, #dc2626); 
    color: #fff; 
    padding: 6px 16px; 
    border-radius: 8px; 
    font-size: 0.85rem;
    font-weight: 700;
}
.applicable-badge { 
    color: #c4b5fd; 
    padding: 6px 14px; 
    border-radius: 8px; 
    font-size: 0.85rem;
    font-weight: 600;
    border: 1px solid rgba(139, 92, 246, 0.5);
}
.validity-text { font-size: 0.85rem; line-height: 1.6; }
.validity-from { color: #4ade80; font-weight: 500; }
.validity-until { color: #f87171; font-weight: 500; }
.validity-forever { color: #94a3b8; font-style: italic; }
.amount-text { color: #f1f5f9; font-weight: 600; }
.amount-none { color: #64748b; font-size: 1.2rem; }
</style>

<!-- Vouchers Table -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-list"></i> Danh Sách Voucher</h3>
        <input type="text" id="voucher-search" placeholder="Tìm kiếm..." class="form-control" style="max-width:250px" oninput="filterVouchers(this.value)">
    </div>
    <div style="overflow-x:auto">
        <table class="voucher-table" id="voucherTable">
            <thead>
                <tr>
                    <th>Mã</th>
                    <th>Loại</th>
                    <th>Giảm</th>
                    <th>Đơn Tối Thiểu</th>
                    <th>Giảm Tối Đa</th>
                    <th>Sử Dụng</th>
                    <th>Áp Dụng</th>
                    <th>Hiệu Lực</th>
                    <th>Trạng Thái</th>
                    <th style="text-align:center">Thao Tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($vouchers)): ?>
                <tr><td colspan="10" style="text-align:center;padding:2rem;color:#94a3b8">Chưa có voucher nào</td></tr>
                <?php else: ?>
                <?php foreach ($vouchers as $v): 
                    $applicableIds = $v['applicable_products'] ? json_decode($v['applicable_products'], true) : null;
                    $applicableCount = $applicableIds ? count($applicableIds) . ' SP' : 'Tất cả';
                    $used = $v['used_count'] ?? 0;
                    $limit = $v['usage_limit'] ?? 0;
                ?>
                <tr class="<?= $v['is_active'] ? '' : 'inactive' ?>" data-code="<?= strtolower($v['code']) ?>">
                    <td><span class="voucher-code"><?= e($v['code']) ?></span></td>
                    <td>
                        <?php if ($v['discount_type'] === 'percentage'): ?>
                            <span class="badge-type badge-percent">% Phần trăm</span>
                        <?php else: ?>
                            <span class="badge-type badge-fixed">₫ Cố định</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="discount-value">
                            <?= $v['discount_type'] === 'percentage' 
                                ? $v['discount_value'] . '%' 
                                : number_format($v['discount_value']) . 'đ' ?>
                        </span>
                    </td>
                    <td>
                        <?= $v['min_amount'] > 0 
                            ? '<span class="amount-text">' . number_format($v['min_amount']) . 'đ</span>' 
                            : '<span class="amount-none">—</span>' ?>
                    </td>
                    <td>
                        <?= $v['max_discount'] > 0 
                            ? '<span class="amount-text">' . number_format($v['max_discount']) . 'đ</span>' 
                            : '<span class="amount-none">∞</span>' ?>
                    </td>
                    <td>
                        <?php if ($limit > 0): 
                            $pct = min(($used / $limit) * 100, 100);
                            $color = $pct >= 90 ? '#ef4444' : ($pct >= 70 ? '#f59e0b' : '#10b981');
                        ?>
                        <div style="min-width:80px">
                            <div style="display:flex;justify-content:space-between;font-size:0.85rem;margin-bottom:3px">
                                <span style="color:#a78bfa;font-weight:600"><?= $used ?></span>
                                <span style="color:#64748b">/ <?= $limit ?></span>
                            </div>
                            <div style="height:4px;background:rgba(100,116,139,0.2);border-radius:2px;overflow:hidden">
                                <div style="width:<?= $pct ?>%;height:100%;background:<?= $color ?>;border-radius:2px"></div>
                            </div>
                        </div>
                        <?php else: ?>
                        <span style="color:#a78bfa;font-weight:600"><?= $used ?></span> <span style="color:#64748b">/ ∞</span>
                        <?php endif; ?>
                    </td>
                    <td><span class="applicable-badge"><?= $applicableCount === 'Tất cả' ? '✓ Tất cả' : $applicableCount ?></span></td>
                    <td>
                        <?php 
                        $from = $v['valid_from'] ? date('d/m/Y', strtotime($v['valid_from'])) : '';
                        $until = $v['valid_until'] ? date('d/m/Y', strtotime($v['valid_until'])) : '';
                        if (!$from && !$until): ?>
                            <span class="validity-forever">∞ Vĩnh viễn</span>
                        <?php else: ?>
                            <div class="validity-text">
                                <?php if ($from): ?><div class="validity-from">▶ <?= $from ?></div><?php endif; ?>
                                <?php if ($until): ?><div class="validity-until">◼ <?= $until ?></div><?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?= $v['is_active'] 
                            ? '<span class="status-on">✓ ON</span>' 
                            : '<span class="status-off">✗ OFF</span>' ?>
                    </td>
                    <td>
                        <div style="display:flex;gap:6px;justify-content:center">
                            <button onclick="toggleVoucherStatus(<?= $v['id'] ?>)" class="btn btn-sm <?= $v['is_active'] ? 'btn-warning' : 'btn-success' ?>" style="padding:6px 10px" title="<?= $v['is_active'] ? 'Tắt' : 'Bật' ?>">
                                <i class="fas fa-power-off"></i>
                            </button>
                            <button onclick="editVoucher(<?= $v['id'] ?>)" class="btn btn-sm btn-primary" style="padding:6px 10px" title="Sửa">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="deleteVoucher(<?= $v['id'] ?>, <?= htmlspecialchars(json_encode($v['code']), ENT_QUOTES, 'UTF-8') ?>)" class="btn btn-sm btn-danger" style="padding:6px 10px" title="Xóa">
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

<script>
const voucherData = <?= json_encode($voucherGridData) ?>;

function filterVouchers(query) {
    const rows = document.querySelectorAll('#voucherTable tbody tr');
    const q = query.toLowerCase();
    rows.forEach(row => {
        if (row.dataset.code) {
            row.style.display = row.dataset.code.includes(q) ? '' : 'none';
        }
    });
}

function toggleVoucherStatus(id) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `<input type="hidden" name="action" value="toggle_active"><input type="hidden" name="id" value="${id}">`;
    document.body.appendChild(form);
    form.submit();
}

function editVoucher(id) {
    const voucher = voucherData.find(v => v.id == id);
    if (voucher) editVoucherData(voucher);
}
</script>

<!-- Add/Edit Modal -->
<div id="voucher-modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.8);z-index:9999;align-items:center;justify-content:center">
    <div style="background:#1e293b;border-radius:16px;width:90%;max-width:800px;max-height:90vh;overflow-y:auto">
        <div style="padding:1.5rem;border-bottom:1px solid rgba(100,116,139,0.2);display:flex;justify-content:space-between;align-items:center">
            <h3 style="margin:0;color:#f8fafc"><i class="fas fa-ticket-alt"></i> <span id="modal-title">Thêm Voucher Mới</span></h3>
            <button onclick="closeModal()" class="btn btn-sm btn-secondary"><i class="fas fa-times"></i></button>
        </div>
        
        <form method="POST" style="padding:1.5rem">
            <input type="hidden" name="action" id="form-action" value="add">
            <input type="hidden" name="id" id="form-id">
            
            <div class="form-grid" style="grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <!-- Left Column -->
                <div>
                    <div class="form-group">
                        <label><i class="fas fa-barcode"></i> Mã Voucher <span style="color:#ef4444">*</span></label>
                        <input type="text" name="code" id="form-code" class="form-control" required placeholder="VD: SUMMER2024" style="text-transform:uppercase">
                        <small style="color:#64748b">Chỉ chữ và số, không dấu</small>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-tags"></i> Loại Giảm Giá <span style="color:#ef4444">*</span></label>
                        <select name="discount_type" id="form-discount-type" class="form-control" onchange="updateDiscountLabel()">
                            <option value="percentage">Phần Trăm (%)</option>
                            <option value="fixed">Số Tiền Cố Định (VND)</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-percentage"></i> <span id="discount-label">Giảm Giá (%)</span> <span style="color:#ef4444">*</span></label>
                        <input type="number" name="discount_value" id="form-discount-value" class="form-control" required min="0" step="0.01" placeholder="VD: 10">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-calendar-alt"></i> Ngày Bắt Đầu</label>
                        <input type="datetime-local" name="valid_from" id="form-valid-from" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label style="display:flex;align-items:center;justify-content:space-between;cursor:pointer">
                            <span><i class="fas fa-power-off"></i> Kích Hoạt Ngay</span>
                            <label class="toggle">
                                <input type="checkbox" name="is_active" id="form-is-active" checked>
                                <span class="toggle-slider"></span>
                            </label>
                        </label>
                    </div>
                </div>
                
                <!-- Right Column -->
                <div>
                    <div class="form-group">
                        <label><i class="fas fa-shopping-cart"></i> Đơn Hàng Tối Thiểu (VND)</label>
                        <input type="number" name="min_amount" id="form-min-amount" class="form-control" min="0" step="1000" value="0" placeholder="VD: 100000">
                        <small style="color:#64748b">Để 0 nếu không giới hạn</small>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-hand-holding-usd"></i> Giảm Tối Đa (VND)</label>
                        <input type="number" name="max_discount" id="form-max-discount" class="form-control" min="0" step="1000" value="0" placeholder="VD: 50000">
                        <small style="color:#64748b">Áp dụng cho loại %, để 0 nếu không giới hạn</small>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-list-ol"></i> Giới Hạn Số Lần Dùng</label>
                        <input type="number" name="usage_limit" id="form-usage-limit" class="form-control" min="0" value="0" placeholder="VD: 100">
                        <small style="color:#64748b">Để 0 nếu không giới hạn</small>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-calendar-times"></i> Ngày Kết Thúc</label>
                        <input type="datetime-local" name="valid_until" id="form-valid-until" class="form-control">
                    </div>
                </div>
            </div>
            
            <!-- Product Selection -->
            <div class="form-group" style="margin-top:1.5rem;padding-top:1.5rem;border-top:1px solid rgba(100,116,139,0.2)">
                <label style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem">
                    <span><i class="fas fa-box"></i> Sản Phẩm Áp Dụng</span>
                    <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer">
                        <input type="checkbox" id="apply-all-products" checked onchange="toggleProductSelection()">
                        <span style="font-size:0.9rem;color:#94a3b8">Áp dụng tất cả sản phẩm</span>
                    </label>
                </label>
                <input type="hidden" name="applicable_products" id="form-applicable-products" value="">
                
                <div id="product-selection" style="display:none;max-height:250px;overflow-y:auto;background:rgba(15,23,42,0.6);border-radius:8px;padding:1rem">
                    <div style="margin-bottom:0.75rem">
                        <input type="text" id="product-search" placeholder="Tìm sản phẩm..." class="form-control" style="padding:0.5rem" oninput="filterProducts(this.value)">
                    </div>
                    <div style="display:flex;gap:0.5rem;margin-bottom:0.75rem">
                        <button type="button" onclick="selectAllProducts()" class="btn btn-sm btn-secondary">Chọn tất cả</button>
                        <button type="button" onclick="deselectAllProducts()" class="btn btn-sm btn-secondary">Bỏ chọn tất cả</button>
                    </div>
                    <div id="product-list" style="display:flex;flex-direction:column;gap:0.5rem">
                        <?php foreach ($products as $p): ?>
                        <label class="product-item" data-name="<?= strtolower($p['name']) ?>" style="display:flex;align-items:center;gap:0.75rem;padding:0.5rem;background:rgba(30,41,59,0.6);border-radius:6px;cursor:pointer;transition:all 0.2s">
                            <input type="checkbox" class="product-checkbox" value="<?= $p['id'] ?>" onchange="updateSelectedProducts()">
                            <img src="<?= asset('images/uploads/' . $p['image']) ?>" style="width:32px;height:32px;border-radius:4px;object-fit:cover">
                            <span style="flex:1;font-size:0.9rem;color:#e2e8f0"><?= e($p['name']) ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <small id="selected-count" style="color:#64748b;display:none;margin-top:0.5rem">Đã chọn: <span>0</span> sản phẩm</small>
            </div>
            
            <div style="display:flex;gap:1rem;margin-top:1.5rem">
                <button type="submit" class="btn btn-primary" style="flex:1">
                    <i class="fas fa-save"></i> <span id="submit-text">Thêm Voucher</span>
                </button>
                <button type="button" onclick="closeModal()" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Hủy
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Modal functions
function openAddModal() {
    document.getElementById('modal-title').textContent = 'Thêm Voucher Mới';
    document.getElementById('form-action').value = 'add';
    document.getElementById('submit-text').textContent = 'Thêm Voucher';
    document.querySelector('#voucher-modal form').reset();
    document.getElementById('form-is-active').checked = true;
    document.getElementById('apply-all-products').checked = true;
    toggleProductSelection();
    deselectAllProducts();
    document.getElementById('voucher-modal').style.display = 'flex';
}

function editVoucherData(voucher) {
    document.getElementById('modal-title').textContent = 'Chỉnh Sửa Voucher';
    document.getElementById('form-action').value = 'edit';
    document.getElementById('submit-text').textContent = 'Cập Nhật';
    document.getElementById('form-id').value = voucher.id;
    document.getElementById('form-code').value = voucher.code || '';
    document.getElementById('form-discount-type').value = voucher.discount_type || 'percentage';
    document.getElementById('form-discount-value').value = voucher.discount_value || '';
    document.getElementById('form-min-amount').value = voucher.min_amount || 0;
    document.getElementById('form-max-discount').value = voucher.max_discount || 0;
    document.getElementById('form-usage-limit').value = voucher.usage_limit || 0;
    document.getElementById('form-valid-from').value = voucher.valid_from ? voucher.valid_from.replace(' ', 'T') : '';
    document.getElementById('form-valid-until').value = voucher.valid_until ? voucher.valid_until.replace(' ', 'T') : '';
    document.getElementById('form-is-active').checked = voucher.is_active == 1;
    
    // Handle applicable products
    const applicable = voucher.applicable_products ? JSON.parse(voucher.applicable_products) : null;
    if (applicable && applicable.length > 0) {
        document.getElementById('apply-all-products').checked = false;
        toggleProductSelection();
        deselectAllProducts();
        applicable.forEach(id => {
            const cb = document.querySelector(`.product-checkbox[value="${id}"]`);
            if (cb) cb.checked = true;
        });
        updateSelectedProducts();
    } else {
        document.getElementById('apply-all-products').checked = true;
        toggleProductSelection();
    }
    
    updateDiscountLabel();
    document.getElementById('voucher-modal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('voucher-modal').style.display = 'none';
}

function updateDiscountLabel() {
    const type = document.getElementById('form-discount-type').value;
    const label = document.getElementById('discount-label');
    label.innerHTML = type === 'percentage' ? 'Giảm Giá (%)' : 'Giảm Giá (VND)';
}

// Product selection functions
function toggleProductSelection() {
    const applyAll = document.getElementById('apply-all-products').checked;
    const selectionDiv = document.getElementById('product-selection');
    const countDiv = document.getElementById('selected-count');
    
    if (applyAll) {
        selectionDiv.style.display = 'none';
        countDiv.style.display = 'none';
        document.getElementById('form-applicable-products').value = '';
    } else {
        selectionDiv.style.display = 'block';
        countDiv.style.display = 'block';
        updateSelectedProducts();
    }
}

function filterProducts(query) {
    const items = document.querySelectorAll('.product-item');
    const q = query.toLowerCase();
    items.forEach(item => {
        item.style.display = item.dataset.name.includes(q) ? 'flex' : 'none';
    });
}

function selectAllProducts() {
    document.querySelectorAll('.product-checkbox').forEach(cb => cb.checked = true);
    updateSelectedProducts();
}

function deselectAllProducts() {
    document.querySelectorAll('.product-checkbox').forEach(cb => cb.checked = false);
    updateSelectedProducts();
}

function updateSelectedProducts() {
    const checked = document.querySelectorAll('.product-checkbox:checked');
    const ids = Array.from(checked).map(cb => cb.value);
    document.getElementById('form-applicable-products').value = ids.length > 0 ? JSON.stringify(ids) : '';
    document.querySelector('#selected-count span').textContent = ids.length;
}

// Close modal on outside click
document.getElementById('voucher-modal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});

// Delete voucher with notify confirm
async function deleteVoucher(id, code) {
    const confirmed = await notify.confirm({
        title: 'Xác nhận xóa voucher?',
        message: `Bạn có chắc muốn xóa voucher "${code}"? Hành động này không thể hoàn tác.`,
        type: 'warning',
        confirmText: 'Xóa',
        cancelText: 'Hủy'
    });
    
    if (confirmed) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
