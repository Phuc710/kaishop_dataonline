<?php
// Get filter, search, sort, and pagination
$filter_status = $_GET['status'] ?? 'all';
$search = trim($_GET['search'] ?? '');
$sort = $_GET['sort'] ?? 'newest';
$view_order_number = $_GET['view'] ?? null;
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 10;


$current_currency = $_COOKIE['currency'] ?? 'VND';
global $pdo;
$exchange_rate = 25000;
try {
    $stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key='exchange_rate'");
    $exchange_rate = floatval($stmt->fetchColumn() ?? 25000);
} catch (Exception $e) {
}

$render_list = true;
if ($view_order_number) {
    $stmt = $pdo->prepare("
        SELECT o.*, 
               GROUP_CONCAT(CONCAT(p.name, ' (x', oi.quantity, ')') SEPARATOR ', ') as product_names,
               GROUP_CONCAT(p.image SEPARATOR ',') as product_images,
               GROUP_CONCAT(DISTINCT oi.product_id SEPARATOR ',') as product_ids,
               GROUP_CONCAT(oi.account_data SEPARATOR '\n\n') as account_data,
               GROUP_CONCAT(DISTINCT p.delivery_content SEPARATOR '\n\n---\n\n') as delivery_content,
               SUM(oi.quantity) as quantity
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        LEFT JOIN products p ON oi.product_id = p.id
        WHERE o.order_number = ? AND o.user_id = ?
        GROUP BY o.id
    ");
    $stmt->execute([$view_order_number, $user_id]);
    $order_detail = $stmt->fetch();

    // Get products in this order (each item separately)
    if ($order_detail) {
        $render_list = false;
        $stmt = $pdo->prepare("
            SELECT oi.id as item_id, oi.product_id, oi.quantity, oi.price_vnd, oi.price_usd, oi.delivery_content,
                   p.name as product_name, p.image as product_image, p.product_type,
                   p.requires_customer_info,
                   pv.variant_name,
                   (SELECT id FROM reviews WHERE product_id = oi.product_id AND user_id = ? AND order_id = o.id LIMIT 1) as review_id
            FROM order_items oi
            LEFT JOIN products p ON oi.product_id = p.id
            LEFT JOIN product_variants pv ON oi.variant_id = pv.id
            LEFT JOIN orders o ON oi.order_id = o.id
            WHERE o.order_number = ? AND o.user_id = ?
            ORDER BY oi.id ASC
        ");
        $stmt->execute([$user_id, $view_order_number, $user_id]);
        $order_products = $stmt->fetchAll();
    }

    if ($order_detail) {
        ?>
        <link rel="stylesheet" href="assets/css/orders.css">
        <div class="page-header fade-in">
            <div>
                <h1><i class="fas fa-receipt"></i> Chi Tiết Đơn Hàng</h1>
                <p style="margin-bottom:0">
                    <span style="color:var(--text-secondary)">Mã đơn:</span>
                    <strong style="color:var(--primary)"><?= e($order_detail['order_number']) ?></strong>
                </p>
            </div>
            <a href="?tab=orders" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Quay Lại
            </a>
        </div>

        <div class="card fade-in">
            <div
                style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; margin-bottom: 2rem;">
                <div>
                    <h3 style="color: var(--text-primary); margin-bottom: 1rem; font-size: 1.2rem;">
                        <i class="fas fa-info-circle"></i> Thông Tin Đơn Hàng
                    </h3>
                    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                        <div>
                            <span style="color: var(--text-secondary);">Trạng thái:</span>
                            <?php
                            $status_class = '';
                            $status_text = '';
                            switch ($order_detail['status']) {
                                case 'completed':
                                    $status_class = 'badge-success';
                                    $status_text = 'Hoàn Thành';
                                    break;
                                case 'pending':
                                    $status_class = 'badge-warning';
                                    $status_text = 'Đang Xử Lý';
                                    break;
                                case 'cancelled':
                                    $status_class = 'badge-secondary';
                                    $status_text = 'Đã Hủy';
                                    break;
                                default:
                                    $status_class = 'badge-info';
                                    $status_text = ucfirst($order_detail['status']);
                            }
                            ?>
                            <span class="badge <?= $status_class ?>"><?= $status_text ?></span>
                        </div>
                        <?php if ($order_detail['status'] == 'cancelled' && !empty($order_detail['cancellation_reason'])): ?>
                            <div
                                style="margin-top:0.5rem;padding:1rem;background:rgba(239,68,68,0.1);border-left:3px solid #ef4444;border-radius:6px">
                                <div style="color:#ef4444;font-weight:600;margin-bottom:0.25rem;font-size:0.9rem">
                                    <i class="fas fa-exclamation-triangle"></i> Lý do hủy:
                                </div>
                                <div style="color:var(--text-primary);font-size:0.95rem">
                                    <?= nl2br(e($order_detail['cancellation_reason'])) ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        <div>
                            <span style="color: var(--text-secondary);">Ngày đặt:</span>
                            <strong
                                style="color: var(--text-primary);"><?= date('d/m/Y H:i', strtotime($order_detail['created_at'])) ?></strong>
                        </div>
                        <div>
                            <span style="color: var(--text-secondary);">Số lượng:</span>
                            <strong style="color: var(--text-primary);"><?= $order_detail['quantity'] ?> sản phẩm</strong>
                        </div>
                        <div>
                            <span style="color: var(--text-secondary);">Phương thức:</span>
                            <strong style="color: var(--text-primary);"><?= e($order_detail['payment_method']) ?></strong>
                        </div>
                    </div>
                </div>

                <div>
                    <h3 style="color: var(--text-primary); margin-bottom: 1rem; font-size: 1.2rem;">
                        <i class="fas fa-dollar-sign"></i> Chi Tiết Thanh Toán
                    </h3>
                    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: var(--text-secondary);">Đơn giá:</span>
                            <strong style="color: var(--text-primary);">
                                <?php
                                $qty = $order_detail['quantity'] ?? 1;
                                if ($current_currency === 'USD') {
                                    $val = ($order_detail['currency'] === 'USD') ? ($order_detail['total_amount_usd'] / $qty) : ($order_detail['total_amount_vnd'] / $qty / $exchange_rate);
                                    echo '$' . number_format($val, 2);
                                } else {
                                    $val = ($order_detail['currency'] === 'USD') ? ($order_detail['total_amount_usd'] / $qty * $exchange_rate) : ($order_detail['total_amount_vnd'] / $qty);
                                    echo number_format($val) . 'đ';
                                }
                                ?>
                            </strong>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: var(--text-secondary);">Số lượng:</span>
                            <strong style="color: var(--text-primary);">x<?= $order_detail['quantity'] ?></strong>
                        </div>
                        <?php if (!empty($order_detail['voucher_code'])): ?>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: var(--text-secondary);">Voucher:</span>
                                <span class="badge badge-success"><?= e($order_detail['voucher_code']) ?></span>
                            </div>
                        <?php endif; ?>
                        <div
                            style="border-top: 2px solid var(--border); padding-top: 0.75rem; margin-top: 0.5rem; display: flex; justify-content: space-between;">
                            <span style="color: var(--text-primary); font-weight: 700; font-size: 1.1rem;">Tổng cộng:</span>
                            <strong style="color: #ef4444; font-size: 1.3rem;">
                                <?php if ($current_currency === 'USD'): ?>
                                    $<?= number_format(($order_detail['currency'] === 'USD' ? $order_detail['total_amount_usd'] : $order_detail['total_amount_vnd'] / $exchange_rate), 2) ?>
                                <?php else: ?>
                                    <?= number_format(($order_detail['currency'] === 'USD' ? $order_detail['total_amount_usd'] * $exchange_rate : $order_detail['total_amount_vnd'])) ?>đ
                                <?php endif; ?>
                            </strong>
                        </div>
                    </div>
                </div>
            </div>

            <?php
            // Group products by product_id + variant_id, keep original quantity
            $grouped_products = [];
            foreach ($order_products as $item) {
                $key = $item['product_id'] . '_' . ($item['variant_id'] ?? 'no_variant');
                $qty = intval($item['quantity']);
                $delivery_lines = [];
                if (!empty($item['delivery_content'])) {
                    $delivery_lines = array_filter(array_map('trim', explode("\n", $item['delivery_content'])));
                }

                $requires_info = !empty($item['requires_customer_info']);
                $delivered_count = count($delivery_lines);

                if (!isset($grouped_products[$key])) {
                    $grouped_products[$key] = $item;
                    $grouped_products[$key]['delivery_lines'] = $delivery_lines;
                    $grouped_products[$key]['delivered_count'] = $delivered_count;
                    $grouped_products[$key]['item_status'] = $delivered_count > 0 ? 'delivered' : 'waiting_admin';
                }
            }
            $expanded_products = array_values($grouped_products);
            ?>
            <div>
                <h3 style="color: var(--text-primary); margin-bottom: 1rem; font-size: 1.2rem;">
                    <i class="fas fa-box"></i> Sản Phẩm Trong Đơn (<?= count($expanded_products) ?>)
                </h3>
                <div
                    style="background: rgba(0, 12, 117, 0.18); border-radius: 12px; border: 1px solid var(--border); overflow-x: auto; -webkit-overflow-scrolling: touch;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: rgba(148, 163, 184, 0.1);">
                                <th style="padding: 12px 15px; text-align: left; color: var(--text-main); font-size: 0.85rem;">
                                    Sản Phẩm</th>
                                <th
                                    style="padding: 12px 15px; text-align: center; color: var(--text-main); font-size: 0.85rem;">
                                    SL</th>
                                <th
                                    style="padding: 12px 15px; text-align: center; color: var(--text-main); font-size: 0.85rem;">
                                    Đơn Giá</th>
                                <th
                                    style="padding: 12px 15px; text-align: center; color: var(--text-main); font-size: 0.85rem;">
                                    Thành Tiền</th>
                                <th
                                    style="padding: 12px 15px; text-align: center; color: var(--text-main); font-size: 0.85rem;">
                                    Trạng Thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($expanded_products as $item): ?>
                                <tr style="border-top: 1px solid var(--border);">
                                    <td style="padding: 12px 15px;">
                                        <div style="display: flex; align-items: center; gap: 12px;">
                                            <?php if ($item['product_image']): ?>
                                                <img src="<?= asset('images/uploads/' . $item['product_image']) ?>"
                                                    style="width: 45px; height: 45px; border-radius: 8px; object-fit: cover;">
                                            <?php endif; ?>
                                            <div>
                                                <div style="color: var(--text-main); font-weight: 600; font-size: 0.95rem;">
                                                    <?= e($item['product_name']) ?>
                                                </div>
                                                <?php if ($item['variant_name']): ?>
                                                    <small style="color: #8b5cf6;">(<?= e($item['variant_name']) ?>)</small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="padding: 12px 15px; text-align: center;">
                                        <span style="color: var(--text-main); font-weight: 700; font-size: 0.95rem;">
                                            ×<?= $item['quantity'] ?>
                                        </span>
                                    </td>
                                    <td style="padding: 12px 15px; text-align: center; color: var(--text-secondary);">
                                        <?php if ($current_currency === 'USD'): ?>
                                            $<?= number_format($item['price_usd'], 2) ?>
                                        <?php else: ?>
                                            <?= number_format($item['price_vnd']) ?>đ
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 12px 15px; text-align: center; color: #10b981; font-weight: 700;">
                                        <?php if ($current_currency === 'USD'): ?>
                                            $<?= number_format($item['price_usd'] * $item['quantity'], 2) ?>
                                        <?php else: ?>
                                            <?= number_format($item['price_vnd'] * $item['quantity']) ?>đ
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 12px 15px; text-align: center;">
                                        <?php if ($item['item_status'] == 'delivered'): ?>
                                            <span
                                                style="background: rgba(16, 185, 129, 0.2); color: #10b981; padding: 4px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: 600;">
                                                <i class="fas fa-check-circle"></i> Đã nhận
                                            </span>
                                        <?php else: ?>
                                            <span
                                                style="background: rgba(245, 158, 11, 0.2); color: #f59e0b; padding: 4px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: 600;">
                                                <i class="fas fa-clock"></i> Chờ Admin
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if (!empty($order_detail['note'])): ?>
                    <div
                        style="margin-top:1rem;padding:1rem;background:rgba(15,23,42,0.5);border-radius:8px;border:1px solid var(--border)">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.5rem">
                            <div style="color:var(--primary);font-weight:600;font-size:0.9rem">
                                <i class="fas fa-user-edit"></i> Thông tin khách hàng:
                            </div>
                            <?php if ($order_detail['status'] == 'pending'): ?>
                                <button onclick="toggleEditNote()" class="btn btn-sm btn-secondary"
                                    style="padding:4px 10px;font-size:0.8rem">
                                    <i class="fas fa-pen"></i> Sửa
                                </button>
                            <?php endif; ?>
                        </div>

                        <div id="note-display" style="color:var(--text-primary);font-size:0.95rem;">
                            <?= e($order_detail['note']) ?>
                        </div>

                        <?php if ($order_detail['status'] == 'pending'): ?>
                            <div id="note-edit-form" style="display:none;margin-top:10px;">
                                <div style="display:flex;gap:10px;">
                                    <input type="text" id="edit-note-input" value="<?= e($order_detail['note']) ?>" class="form-control"
                                        style="background:rgba(15,23,42,0.5);border:1px solid var(--primary);color:var(--text-primary);flex:1;padding:8px 12px;border-radius:6px;">
                                    <button id="save-note-btn" onclick="saveOrderNote('<?= $order_detail['id'] ?>')"
                                        class="btn btn-primary btn-sm">
                                        <i class="fas fa-save"></i> Lưu
                                    </button>
                                    <button onclick="toggleEditNote()" class="btn btn-secondary btn-sm">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>



            <?php if ($order_detail['status'] == 'completed'): ?>
                <div style="margin-top: 2rem;">
                    <h3 style="color: var(--text-primary); margin-bottom: 1rem; font-size: 1.2rem;">
                        <i class="fas fa-gift"></i> Sản Phẩm Đã Nhận
                        (<?= array_sum(array_column($expanded_products, 'quantity')) ?>)
                    </h3>
                    <?php foreach ($expanded_products as $idx => $item): ?>
                        <?php
                        $product_type = $item['product_type'] ?? 'account';
                        $unique_id = $item['product_id'] . '_' . $idx;
                        $delivery_lines = $item['delivery_lines'] ?? [];
                        $has_delivery = count($delivery_lines) > 0;
                        $quantity = intval($item['quantity']);
                        ?>
                        <div
                            style="padding: 1.5rem; background: border-radius: 12px; border: 1px solid var(--border); margin-bottom: 1rem;">
                            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                                <?php if ($product_type == 'account'): ?>
                                    <span class="badge"
                                        style="background: #8b5cf6; color: #fff; padding: 0.4rem 0.9rem; border-radius: 8px;">
                                        <i class="fas fa-user-shield"></i> Tài Khoản
                                    </span>
                                <?php elseif ($product_type == 'source'): ?>
                                    <span class="badge"
                                        style="background: #3b82f6; color: #fff; padding: 0.4rem 0.9rem; border-radius: 8px;">
                                        <i class="fas fa-code"></i> Source Code
                                    </span>
                                <?php elseif ($product_type == 'book'): ?>
                                    <span class="badge"
                                        style="background: #f59e0b; color: #000; padding: 0.4rem 0.9rem; border-radius: 8px;">
                                        <i class="fas fa-book"></i> Ebook
                                    </span>
                                <?php endif; ?>
                                <strong style="color: var(--text-primary);">
                                    <?= e($item['product_name']) ?>
                                    <?php if ($quantity > 1): ?>
                                        <span style="color: #a78bfa; font-size: 0.9rem;"> x<?= $quantity ?></span>
                                    <?php endif; ?>
                                </strong>
                            </div>

                            <?php if ($has_delivery): ?>
                                <?php if ($product_type == 'account'): ?>
                                    <div style="position: relative;">
                                        <div id="accounts_<?= $unique_id ?>"
                                            style="background: rgba(15, 23, 42, 0.8); padding: 1.5rem; border-radius: 8px; color: #10b981; font-family: 'Courier New', monospace; font-size: 0.9rem; line-height: 1.8;">
                                            <?php foreach ($delivery_lines as $idx => $account): ?>
                                                <div
                                                    style="display: flex; align-items: center; margin-bottom: 0.5rem; <?= $idx > 0 ? 'border-top: 1px solid rgba(139, 92, 246, 0.2); padding-top: 0.5rem;' : '' ?>">
                                                    <span style="color: #a78bfa; font-weight: bold; min-width: 30px;"><?= $idx + 1 ?>.</span>
                                                    <span style="flex: 1; margin-left: 10px;"><?= e($account) ?></span>
                                                    <button onclick="copyAccount('<?= e($account) ?>')"
                                                        style="background: #ffffff; border: 1px solid #8b5cf6; color: #c4b5fd; padding: 2px 8px; border-radius: 4px; font-size: 0.7rem; margin-left: 10px; cursor: pointer;">
                                                        <i class="fas fa-copy"></i>
                                                    </button>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <script>
                                        function copyAllAccounts_<?= $unique_id ?>() {
                                            const accounts = <?= json_encode($delivery_lines) ?>;
                                            const text = accounts.map((acc, idx) => `${idx + 1}. ${acc}`).join('\n');
                                            navigator.clipboard.writeText(text).then(() => {
                                                if (window.notify) notify.success('Đã copy tất cả tài khoản!');
                                                else alert('Đã copy tất cả tài khoản!');
                                            });
                                        }
                                        function copyAccount(account) {
                                            navigator.clipboard.writeText(account).then(() => {
                                                if (window.notify) notify.success('Đã copy!');
                                                else alert('Đã copy!');
                                            });
                                        }
                                    </script>
                                <?php else: ?>
                                    <!-- Source/Book: Show download links -->
                                    <?php
                                    $links = array_filter(array_map('trim', explode("\n", $item['delivery_content'])));
                                    foreach ($links as $link_idx => $link):
                                        if (empty($link))
                                            continue;
                                        ?>
                                        <a href="<?= e($link) ?>" target="_blank" class="download-link-card">
                                            <div class="download-icon-box">
                                                <i class="fas fa-download" style="color: #fff;"></i>
                                            </div>
                                            <div style="flex: 1; min-width: 0;">
                                                <div style="font-weight: 600; font-size: 0.95rem;">
                                                    <?= $product_type == 'source' ? 'Link Download' : 'Link Download' ?>
                                                    <?= count($links) > 1 ? '#' . ($link_idx + 1) : '' ?>
                                                </div>
                                                <div class="download-link-url"
                                                    style="font-size: 0.8rem; word-break: break-all; margin-top: 0.25rem;"><?= e($link) ?></div>
                                            </div>
                                            <i class="fas fa-external-link-alt" style="color: #3b82f6; flex-shrink: 0;"></i>
                                        </a>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            <?php else: ?>
                                <div
                                    style="background: rgba(245, 158, 11, 0.1); padding: 1rem; border-radius: 8px; color: #f59e0b; text-align: center;">
                                    <i class="fas fa-clock"></i> Đang chờ Admin xử lý ...
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($order_detail['status'] == 'completed' && !empty($order_products)): ?>
                <!-- Review Section -->
                <div style="margin-top: 2rem;">
                    <h3 style="color: var(--text-primary); margin-bottom: 1rem; font-size: 1.2rem;">
                        <i class="fas fa-star"></i> Đánh Giá Sản Phẩm
                    </h3>
                    <?php foreach ($order_products as $product): ?>
                        <div class="card review-card" style="margin-bottom: 1rem; padding: 1.5rem;">
                            <div style="display: flex; gap: 1rem; align-items: start;">
                                <img src="<?= asset('images/uploads/' . $product['product_image']) ?>"
                                    style="width: 80px; height: 80px; border-radius: 10px; object-fit: cover; border: 2px solid var(--border);">
                                <div style="flex: 1;">
                                    <h4 style="color: var(--text-primary); margin-bottom: 0.5rem;"><?= e($product['product_name']) ?>
                                    </h4>

                                    <?php if ($product['review_id']): ?>
                                        <div class="review-success-msg">
                                            <i class="fas fa-check-circle"></i> Bạn đã đánh giá sản phẩm này
                                        </div>
                                    <?php else: ?>
                                        <!-- Review Form -->
                                        <div class="review-form" id="review-form-<?= $product['product_id'] ?>">
                                            <div style="margin-bottom: 1rem;">
                                                <label
                                                    style="color: var(--text-primary); display: block; margin-bottom: 0.5rem; font-weight: 600;">
                                                    Đánh giá của bạn:
                                                </label>
                                                <div class="star-rating" data-product-id="<?= $product['product_id'] ?>">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <i class="far fa-star" data-rating="<?= $i ?>"
                                                            style="font-size: 2rem; color: #fbbf24; cursor: pointer; margin-right: 0.25rem;"></i>
                                                    <?php endfor; ?>
                                                </div>
                                                <input type="hidden" id="rating-<?= $product['product_id'] ?>" value="0">
                                            </div>
                                            <div style="margin-bottom: 1rem;">
                                                <label
                                                    style="color: var(--text-primary); display: block; margin-bottom: 0.5rem; font-weight: 600;">
                                                    Nhận xét:
                                                </label>
                                                <textarea id="comment-<?= $product['product_id'] ?>" class="review-textarea"
                                                    placeholder="Chia sẻ trải nghiệm của bạn về sản phẩm..."
                                                    style="width: 100%; min-height: 100px; padding: 0.75rem; border: 1px solid var(--border); border-radius: 8px; color: var(--text-primary); font-size: 14px; resize: vertical;"></textarea>
                                            </div>
                                            <button onclick="submitReview('<?= $product['product_id'] ?>', '<?= $order_detail['id'] ?>')"
                                                class="btn btn-primary">
                                                <i class="fas fa-paper-plane"></i> Gửi Đánh Giá
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
}

if ($render_list):

    // Get orders with filter and search
    $where_clause = "o.user_id = ?";
    $params = [$user_id];

    if ($filter_status == 'unreviewed') {
        // Filter completed orders that haven't been fully reviewed
        $where_clause .= " AND o.status = 'completed' AND (
        SELECT COUNT(DISTINCT r.id) 
        FROM order_items oi_r 
        LEFT JOIN reviews r ON r.product_id = oi_r.product_id AND r.user_id = o.user_id AND r.order_id = o.id
        WHERE oi_r.order_id = o.id
    ) < (
        SELECT COUNT(DISTINCT oi_c.product_id) FROM order_items oi_c WHERE oi_c.order_id = o.id
    )";
    } elseif ($filter_status != 'all') {
        $where_clause .= " AND o.status = ?";
        $params[] = $filter_status;
    }

    if ($search) {
        $where_clause .= " AND (o.order_number LIKE ? OR EXISTS (
        SELECT 1 FROM order_items oi_s 
        LEFT JOIN products p_s ON oi_s.product_id = p_s.id 
        WHERE oi_s.order_id = o.id AND p_s.name LIKE ?
    ))";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    // Get total count for pagination (count orders)
    $count_stmt = $pdo->prepare("SELECT COUNT(DISTINCT o.id) FROM orders o WHERE $where_clause");
    $count_stmt->execute($params);
    $total_orders = $count_stmt->fetchColumn();
    $total_pages = ceil($total_orders / $per_page);
    $offset = ($page - 1) * $per_page;

    // Get orders grouped by order_id (1 row = 1 order)
    $stmt = $pdo->prepare("
    SELECT o.*, 
           (SELECT GROUP_CONCAT(CONCAT(p.name, IF(pv.variant_name IS NOT NULL, CONCAT(' (', pv.variant_name, ')'), '')) SEPARATOR ', ') 
            FROM order_items oi2 
            LEFT JOIN products p ON oi2.product_id = p.id 
            LEFT JOIN product_variants pv ON oi2.variant_id = pv.id
            WHERE oi2.order_id = o.id) as product_names,
           (SELECT p.image FROM order_items oi2 
            LEFT JOIN products p ON oi2.product_id = p.id 
            WHERE oi2.order_id = o.id LIMIT 1) as product_image,
           (SELECT SUM(oi2.quantity) FROM order_items oi2 WHERE oi2.order_id = o.id) as total_quantity,
           (SELECT COUNT(DISTINCT oi3.product_id) FROM order_items oi3 WHERE oi3.order_id = o.id) as product_count,
           (SELECT COUNT(DISTINCT r.id) 
            FROM order_items oi4 
            LEFT JOIN reviews r ON r.product_id = oi4.product_id AND r.user_id = o.user_id AND r.order_id = o.id
            WHERE oi4.order_id = o.id) as review_count
    FROM orders o
    WHERE $where_clause
    ORDER BY " .
        ($sort === 'oldest' ? 'o.created_at ASC' :
            ($sort === 'price_high' ? 'o.total_amount_vnd DESC' :
                ($sort === 'price_low' ? 'o.total_amount_vnd ASC' :
                    'o.created_at DESC'))) . "
    LIMIT $per_page OFFSET $offset
");
    $stmt->execute($params);
    $orders = $stmt->fetchAll();

    // Get status counts (count orders)
    $stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM orders WHERE user_id = ? GROUP BY status");
    $stmt->execute([$user_id]);
    $status_counts = [];
    foreach ($stmt->fetchAll() as $row) {
        $status_counts[$row['status']] = $row['count'];
    }
    $total_count = array_sum($status_counts);

    // Get unreviewed count (completed orders that haven't been fully reviewed)
    $stmt = $pdo->prepare("
    SELECT COUNT(*) FROM orders o 
    WHERE o.user_id = ? AND o.status = 'completed' AND (
        SELECT COUNT(DISTINCT r.id) 
        FROM order_items oi_r 
        LEFT JOIN reviews r ON r.product_id = oi_r.product_id AND r.user_id = o.user_id AND r.order_id = o.id
        WHERE oi_r.order_id = o.id
    ) < (
        SELECT COUNT(DISTINCT oi_c.product_id) FROM order_items oi_c WHERE oi_c.order_id = o.id
    )
");
    $stmt->execute([$user_id]);
    $unreviewed_count = $stmt->fetchColumn();
    ?>

    <div class="page-header fade-in">
        <div>
            <h1><i class="fas fa-shopping-bag"></i> Đơn Hàng Của Tôi</h1>
            <p>Quản lý và theo dõi đơn hàng của bạn</p>
        </div>
        <div style="display: flex;">
            <a href="<?= url('sanpham') ?>" class="btn btn-primary">
                <i class="fas fa-plus"></i> Mua Sắm Ngay
            </a>
        </div>
    </div>

    <!-- Search & Sort -->
    <div class="card fade-in" style="margin-bottom: 1.5rem;">
        <form method="GET">
            <input type="hidden" name="tab" value="orders">
            <input type="hidden" name="status" value="<?= e($filter_status) ?>">

            <div style="display: grid; grid-template-columns: 1fr; gap: 1rem;">
                <!-- Row 1: Search and Sort -->
                <div style="display: grid; grid-template-columns: 1fr 200px; gap: 1rem;">
                    <div class="form-group" style="margin: 0;">
                        <label
                            style="color: var(--text-primary); font-weight: 600; margin-bottom: 0.5rem; display: block; font-size: 0.9rem;">
                            <i class="fas fa-search"></i> Tìm Kiếm
                        </label>
                        <input type="text" name="search" class="form-control" value="<?= e($search) ?>"
                            placeholder="Mã đơn, tên sản phẩm..."
                            style="background: var(--card-bg); border: 1px solid var(--border); color: var(--text-primary);">
                    </div>

                    <div class="form-group" style="margin: 0;">
                        <label
                            style="color: var(--text-primary); font-weight: 600; margin-bottom: 0.5rem; display: block; font-size: 0.9rem;">
                            <i class="fas fa-sort"></i> Sắp Xếp
                        </label>
                        <select name="sort" class="form-control"
                            style="background: var(--card-bg); border: 1px solid var(--border); color: var(--text-primary);">
                            <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Mới nhất</option>
                            <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Cũ nhất</option>
                            <option value="price_high" <?= $sort === 'price_high' ? 'selected' : '' ?>>Giá cao → thấp</option>
                            <option value="price_low" <?= $sort === 'price_low' ? 'selected' : '' ?>>Giá thấp → cao</option>
                        </select>
                    </div>
                </div>

                <!-- Row 2: Buttons -->
                <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Lọc
                    </button>
                    <?php if ($search || $sort !== 'newest'): ?>
                        <a href="?tab=orders&status=<?= $filter_status ?>" class="btn btn-secondary">
                            <i class="fas fa-redo"></i> Reset
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>

    <style>
        @media (max-width: 768px) {
            .card form>div>div:first-child {
                grid-template-columns: 1fr !important;
            }
        }

        /* Dashboard Style Copy Button */
        .copy-btn-dashboard {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            background: transparent;
            border: 1px solid var(--border);
            border-radius: 6px;
            color: var(--text-muted);
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .copy-btn-dashboard:hover {
            background: var(--bg-hover);
            border-color: var(--primary);
            color: var(--primary);
        }

        .copy-btn-dashboard i {
            font-size: 13px;
        }
    </style>

    <!-- Filter Tabs -->
    <div class="card fade-in" style="margin-bottom: 2rem;">
        <div class="filter-tabs-container">
            <a href="?tab=orders&status=all<?= $search ? '&search=' . urlencode($search) : '' ?><?= $sort !== 'newest' ? '&sort=' . $sort : '' ?>"
                class="btn <?= $filter_status == 'all' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
                <i class="fas fa-list"></i> Tất Cả (<?= $total_count ?>)
            </a>
            <a href="?tab=orders&status=pending<?= $search ? '&search=' . urlencode($search) : '' ?><?= $sort !== 'newest' ? '&sort=' . $sort : '' ?>"
                class="btn <?= $filter_status == 'pending' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
                <i class="fas fa-clock"></i> Đang Xử Lý (<?= $status_counts['pending'] ?? 0 ?>)
            </a>
            <a href="?tab=orders&status=completed<?= $search ? '&search=' . urlencode($search) : '' ?><?= $sort !== 'newest' ? '&sort=' . $sort : '' ?>"
                class="btn <?= $filter_status == 'completed' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
                <i class="fas fa-check-circle"></i> Hoàn Thành (<?= $status_counts['completed'] ?? 0 ?>)
            </a>
            <a href="?tab=orders&status=cancelled<?= $search ? '&search=' . urlencode($search) : '' ?><?= $sort !== 'newest' ? '&sort=' . $sort : '' ?>"
                class="btn <?= $filter_status == 'cancelled' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
                <i class="fas fa-times-circle"></i> Đã Hủy (<?= $status_counts['cancelled'] ?? 0 ?>)
            </a>
            <a href="?tab=orders&status=unreviewed<?= $search ? '&search=' . urlencode($search) : '' ?><?= $sort !== 'newest' ? '&sort=' . $sort : '' ?>"
                class="btn <?= $filter_status == 'unreviewed' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
                <i class="fas fa-star-half-alt"></i> Chưa Đánh Giá (<?= $unreviewed_count ?>)
            </a>
        </div>
    </div>

    <!-- Orders List -->
    <?php if (empty($orders)): ?>
        <div class="card fade-in">
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <h3>Chưa Có Đơn Hàng <?= $filter_status != 'all' ? ucfirst($filter_status) : '' ?></h3>
                <p>Bạn chưa có đơn hàng nào<?= $filter_status != 'all' ? ' ở trạng thái này' : '' ?></p>
                <a href="<?= url('sanpham') ?>" class="btn btn-primary">
                    <i class="fas fa-shopping-cart"></i> Khám Phá Sản Phẩm
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="card fade-in">
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th class="text-left">Mã Đơn</th>
                            <th class="text-left">Sản Phẩm</th>
                            <th class="text-center">Số Lượng</th>
                            <th class="text-center">Tổng Tiền</th>
                            <th class="text-center">Trạng Thái</th>
                            <th class="text-center">Đánh Giá</th>
                            <th class="text-left">Ngày Mua</th>
                            <th class="text-center">Thao Tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>
                                    <button class="copy-btn-dashboard" onclick="copyToClipboard('<?= e($order['order_number']) ?>')"
                                        title="Click để copy">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <?php if ($order['product_image']): ?>
                                            <img src="<?= asset('images/uploads/' . $order['product_image']) ?>"
                                                style="width: 40px; height: 40px; border-radius: 8px; object-fit: cover; border: 1px solid var(--border-light);">
                                        <?php endif; ?>
                                        <div style="min-width: 0;">
                                            <strong
                                                style="color: var(--text-main); font-size: 0.95rem; display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"
                                                title="<?= e($order['product_names'] ?? '') ?>">
                                                <?php
                                                $names = $order['product_names'] ?? '';
                                                echo e($names);
                                                ?>
                                            </strong>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span style="font-weight: 600; color: var(--text-main);">x<?= $order['total_quantity'] ?></span>
                                </td>
                                <td class="text-center">
                                    <div style="font-weight: 700; color: var(--text-main); font-size: 1.05rem;">
                                        <?php if ($current_currency === 'USD'): ?>
                                            $<?= number_format(($order['total_amount_usd'] > 0 ? $order['total_amount_usd'] : $order['total_amount_vnd'] / $exchange_rate), 2) ?>
                                        <?php else: ?>
                                            <?= number_format(($order['total_amount_usd'] > 0 ? $order['total_amount_usd'] * $exchange_rate : $order['total_amount_vnd'])) ?>đ
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <?php
                                    $status_map = [
                                        'completed' => ['Hoàn Thành', 'badge-success'],
                                        'pending' => ['Đang Xử Lý', 'badge-warning'],
                                        'cancelled' => ['Đã Hủy', 'badge-danger'],
                                        'refunded' => ['Hoàn Tiền', 'badge-info']
                                    ];
                                    $s = $status_map[$order['status']] ?? [$order['status'], 'badge'];
                                    ?>
                                    <span class="badge <?= $s[1] ?>"><?= $s[0] ?></span>
                                </td>
                                <td class="text-center">
                                    <?php
                                    if ($order['status'] == 'completed') {
                                        $review_count = intval($order['review_count'] ?? 0);
                                        $product_count = intval($order['product_count'] ?? 0);

                                        if ($review_count >= $product_count && $product_count > 0) {
                                            echo '<span style="color: #10b981; font-size: 0.85rem; font-weight: 600;"><i class="fas fa-check-circle"></i> Đã đánh giá</span>';
                                        } else {
                                            $pending = $product_count - $review_count;
                                            echo '<a href="?tab=orders&view=' . $order['order_number'] . '#review" class="btn btn-sm" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.3); padding: 4px 10px;">';
                                            echo '<i class="fas fa-star"></i> Đánh giá (' . $pending . ')';
                                            echo '</a>';
                                        }
                                    } else {
                                        echo '<span style="color: var(--text-muted); font-size: 0.85rem;">-</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <span
                                        style="color: var(--text-muted); font-size: 14px;"><?= date('d/m/Y', strtotime($order['created_at'])) ?></span>
                                </td>
                                <td class="text-center">
                                    <div style="display: flex; gap: 0.5rem; justify-content: center;">
                                        <a href="?tab=orders&view=<?= $order['order_number'] ?>" class="btn btn-sm"
                                            style="background: transparent; color: var(--text-muted); border: 1px solid var(--border); padding: 6px 12px;"
                                            onmouseover="this.style.color='var(--primary)'; this.style.borderColor='var(--primary)'"
                                            onmouseout="this.style.color='var(--text-muted)'; this.style.borderColor='var(--border)'">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination-container fade-in" style="margin-top: 2rem;">
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?tab=orders&status=<?= $filter_status ?>&page=<?= $page - 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $sort !== 'newest' ? '&sort=' . $sort : '' ?>"
                            class="page-btn">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    <?php else: ?>
                        <span class="page-btn disabled">
                            <i class="fas fa-chevron-left"></i>
                        </span>
                    <?php endif; ?>

                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);

                    if ($start_page > 1): ?>
                        <a href="?tab=orders&status=<?= $filter_status ?>&page=1<?= $search ? '&search=' . urlencode($search) : '' ?><?= $sort !== 'newest' ? '&sort=' . $sort : '' ?>"
                            class="page-btn">1</a>
                        <?php if ($start_page > 2): ?>
                            <span class="page-dots">...</span>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <a href="?tab=orders&status=<?= $filter_status ?>&page=<?= $i ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $sort !== 'newest' ? '&sort=' . $sort : '' ?>"
                            class="page-btn <?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>

                    <?php if ($end_page < $total_pages): ?>
                        <?php if ($end_page < $total_pages - 1): ?>
                            <span class="page-dots">...</span>
                        <?php endif; ?>
                        <a href="?tab=orders&status=<?= $filter_status ?>&page=<?= $total_pages ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $sort !== 'newest' ? '&sort=' . $sort : '' ?>"
                            class="page-btn"><?= $total_pages ?></a>
                    <?php endif; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?tab=orders&status=<?= $filter_status ?>&page=<?= $page + 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $sort !== 'newest' ? '&sort=' . $sort : '' ?>"
                            class="page-btn">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php else: ?>
                        <span class="page-btn disabled">
                            <i class="fas fa-chevron-right"></i>
                        </span>
                    <?php endif; ?>
                </div>
                <div class="pagination-info">
                    <span>Trang <?= $page ?> / <?= $total_pages ?></span>
                    <span class="separator">•</span>
                    <span>Tổng <?= $total_orders ?> đơn</span>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
<?php endif; ?>

<style>
    /* ========================================
   MODERN ORDERS TABLE - CLEAN DESIGN
   ======================================== */
    * {
        box-sizing: border-box;
    }

    .orders-table-modern {
        width: 100%;
        max-width: 100%;
        margin: 0 auto;
    }

    .table-container {
        background: rgba(30, 41, 59, 0.4);
        border-radius: 16px;
        overflow-x: auto;
        border: 1px solid rgba(148, 163, 184, 0.1);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }

    .modern-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }

    /* ========== TABLE HEADER ========== */
    .modern-table thead {
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.15), rgba(109, 40, 217, 0.15));
        border-bottom: 2px solid #ffffff;
    }

    .modern-table thead th {
        padding: 18px 20px;
        text-align: left;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: #c4b5fd;
        white-space: nowrap;
        background: rgba(139, 92, 246, 0.08);
    }

    /* ========== TABLE BODY ========== */
    .modern-table tbody tr {
        background: rgba(30, 41, 59, 0.3);
        border-bottom: 1px solid rgba(148, 163, 184, 0.08);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }


    .modern-table tbody tr:last-child {
        border-bottom: none;
    }

    .modern-table tbody td {
        padding: 18px 20px;
        vertical-align: middle;
    }

    /* ========== ORDER ID COLUMN ========== */
    .col-order-id {
        width: 70px;
        text-align: center;
    }

    .order-id-badge {
        display: inline-block;
        cursor: pointer;
        user-select: none;
        position: relative;
    }

    .order-number {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 38px;
        height: 38px;
        background: rgba(139, 92, 246, 0.15);
        border: 2px solid #ffffff;
        border-radius: 10px;
        transition: all 0.2s ease;
    }


    .order-id-badge:active .order-number {
        transform: translateY(0);
    }

    .copy-icon {
        font-size: 1rem;
        color: #a78bfa;
        transition: all 0.2s ease;
    }



    .order-id-badge.copied .order-number {
        background: rgba(16, 185, 129, 0.2);
        border-color: rgba(16, 185, 129, 0.4);
    }

    .order-id-badge.copied .copy-icon {
        color: #34d399;
    }

    /* ========== PRODUCT COLUMN ========== */
    .col-product {
        width: 240px;
        max-width: 240px;
    }

    .product-info {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .product-image {
        flex-shrink: 0;
        width: 52px;
        height: 52px;
        border-radius: 12px;
        overflow: hidden;
        background: rgba(15, 23, 42, 0.6);
        border: 2px solid rgba(139, 92, 246, 0.25);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    }



    .product-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .product-details {
        flex: 1;
        min-width: 0;
    }

    .product-name {
        font-size: 0.875rem;
        font-weight: 600;
        color: #f1f5f9;
        margin-bottom: 4px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .product-meta {
        font-size: 0.8125rem;
        color: #94a3b8;
        font-weight: 500;
    }

    /* ========== QUANTITY COLUMN ========== */
    .col-qty {
        width: 90px;
        text-align: center;
    }

    .quantity-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 48px;
        padding: 6px 12px;
        background: rgba(100, 116, 139, 0.15);
        border: 1.5px solid rgba(148, 163, 184, 0.2);
        border-radius: 8px;
        font-size: 0.9375rem;
        font-weight: 700;
        color: #cbd5e1;
    }

    /* ========== TOTAL COLUMN ========== */
    .col-total {
        width: 110px;
    }

    .price-amount {
        font-size: 0.9375rem;
        font-weight: 700;
        color: #a78bfa;
        letter-spacing: 0.3px;
        white-space: nowrap;
    }

    /* ========== STATUS COLUMN ========== */
    .col-status {
        width: 130px;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 10px;
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.3px;
        text-transform: uppercase;
        border: 1.5px solid;
        transition: all 0.2s ease;
    }

    .status-badge i {
        font-size: 0.75rem;
    }

    .status-success {
        background: rgba(16, 185, 129, 0.12);
        color: #34d399;
        border-color: rgba(16, 185, 129, 0.3);
    }

    .status-warning {
        background: rgba(245, 158, 11, 0.12);
        color: #fbbf24;
        border-color: rgba(245, 158, 11, 0.3);
    }

    .status-danger {
        background: rgba(239, 68, 68, 0.12);
        color: #f87171;
        border-color: rgba(239, 68, 68, 0.3);
    }

    .status-info {
        background: rgba(59, 130, 246, 0.12);
        color: #60a5fa;
        border-color: rgba(59, 130, 246, 0.3);
    }

    /* ========== REVIEW COLUMN ========== */
    .col-review {
        width: 120px;
    }

    .review-done {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 0.75rem;
        font-weight: 600;
        color: #34d399;
    }

    .review-btn {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 6px 10px;
        background: rgba(245, 158, 11, 0.12);
        border: 1.5px solid rgba(245, 158, 11, 0.25);
        border-radius: 8px;
        font-size: 0.75rem;
        font-weight: 600;
        color: #fbbf24;
        text-decoration: none;
        transition: all 0.2s ease;
        white-space: nowrap;
    }

    .review-na {
        font-size: 1.25rem;
        color: #475569;
    }

    /* ========== DATE COLUMN ========== */
    .col-date {
        width: 100px;
    }

    .date-info {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }

    .date-main {
        font-size: 0.8125rem;
        font-weight: 600;
        color: #cbd5e1;
    }

    .date-time {
        font-size: 0.6875rem;
        font-weight: 500;
        color: #64748b;
    }

    /* ========== ACTION COLUMN ========== */
    .col-action {
        width: 70px;
        text-align: center;
    }

    .action-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 38px;
        height: 38px;
        background: linear-gradient(135deg, #8b5cf6, #7c3aed);
        border: none;
        border-radius: 10px;
        font-size: 0.9rem;
        color: #ffffff;
        text-decoration: none;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 4px 8px rgba(139, 92, 246, 0.4);
    }


    /* ========== RESPONSIVE DESIGN ========== */
    @media (max-width: 1200px) {

        .modern-table thead th,
        .modern-table tbody td {
            padding: 16px 18px;
        }
    }

    @media (max-width: 1400px) {
        .table-container {
            overflow-x: auto;
        }

        .modern-table {
            min-width: 1000px;
        }
    }

    @media (max-width: 1024px) {
        .modern-table {
            min-width: 900px;
        }
    }

    /* ========== OLD BADGE STYLES (Keep for other pages) ========== */
    .badge {
        border-radius: 50px;
        font-weight: bold;
        font-size: 0.7rem;
        letter-spacing: 0.5px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        border: 1px solid transparent;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s ease;
        text-transform: uppercase;
    }


    .badge i {
        font-size: 1rem;
    }

    .badge-success {
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(16, 185, 129, 0.2));
        color: #059669;
        border-color: rgba(16, 185, 129, 0.2);
    }

    .badge-warning {
        background: linear-gradient(135deg, rgba(245, 158, 11, 0.1), rgba(245, 158, 11, 0.2));
        color: #d97706;
        border-color: rgba(245, 158, 11, 0.2);
    }

    .badge-danger {
        background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(239, 68, 68, 0.2));
        color: #dc2626;
        border-color: rgba(239, 68, 68, 0.2);
    }

    .badge-info {
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(59, 130, 246, 0.2));
        color: #2563eb;
        border-color: rgba(59, 130, 246, 0.2);
    }

    .badge-primary {
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.1), rgba(139, 92, 246, 0.2));
        color: #7c3aed;
        border-color: rgba(139, 92, 246, 0.2);
    }

    .star-rating i {
        transition: all 0.2s ease;
    }

    .star-rating i.active {
        transform: scale(1.2);
    }

    .star-rating i.fas {
        color: #fbbf24 !important;
    }

    .review-form {
        animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* ========================================
   PAGINATION STYLES
   ======================================== */
    .pagination-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 1rem;
        padding: 1.5rem;
        background: rgba(30, 41, 59, 0.4);
        border-radius: 16px;
        border: 1px solid rgba(148, 163, 184, 0.1);
    }

    .pagination {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .page-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 40px;
        height: 40px;
        padding: 0 12px;
        background: rgba(30, 41, 59, 0.6);
        border: 1.5px solid rgba(139, 92, 246, 0.2);
        border-radius: 10px;
        color: #cbd5e1;
        font-weight: 600;
        font-size: 0.9rem;
        text-decoration: none;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .page-btn:hover:not(.disabled):not(.active) {
        background: rgba(139, 92, 246, 0.15);
        border-color: rgba(139, 92, 246, 0.4);
        color: #a78bfa;
    }

    .page-btn.active {
        background: linear-gradient(135deg, #8b5cf6, #7c3aed);
        border-color: #8b5cf6;
        color: #ffffff;
        box-shadow: 0 4px 12px rgba(139, 92, 246, 0.4);
        transform: translateY(-2px);
    }

    .page-btn.disabled {
        opacity: 0.3;
        cursor: not-allowed;
    }

    .page-dots {
        color: #64748b;
        font-weight: 700;
        padding: 0 0.5rem;
    }

    .pagination-info {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        color: #94a3b8;
        font-size: 0.875rem;
        font-weight: 600;
    }

    .pagination-info .separator {
        color: #475569;
    }

    @media (max-width: 640px) {
        .pagination {
            flex-wrap: wrap;
            justify-content: center;
        }

        .pagination-info {
            font-size: 0.8rem;
        }
    }
</style>

<script>
    // Edit Note Functionality
    function toggleEditNote() {
        const displayDiv = document.getElementById('note-display');
        const formDiv = document.getElementById('note-edit-form');

        if (displayDiv.style.display !== 'none') {
            displayDiv.style.display = 'none';
            formDiv.style.display = 'block';
        } else {
            displayDiv.style.display = 'block';
            formDiv.style.display = 'none';
        }
    }

    async function saveOrderNote(orderId) {
        const input = document.getElementById('edit-note-input');
        const newNote = input.value.trim();

        if (!newNote) {
            notify.warning('Lỗi', 'Thông tin không được để trống');
            return;
        }

        const btn = document.getElementById('save-note-btn');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="loading-icon-inline"></span> Lưu...';

        try {
            const response = await fetch('<?= url('api/update-order-note.php') ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ order_id: orderId, note: newNote })
            });

            const result = await response.json();

            if (result.success) {
                notify.success('Thành công', result.message);
                setTimeout(() => location.reload(), 1000);
            } else {
                notify.error('Lỗi', result.message);
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        } catch (error) {
            console.error('Error:', error);
            notify.error('Lỗi', 'Không thể cập nhật thông tin');
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    }

    // Star rating functionality
    document.querySelectorAll('.star-rating').forEach(ratingContainer => {
        const stars = ratingContainer.querySelectorAll('i');
        const productId = ratingContainer.dataset.productId;
        const ratingInput = document.getElementById('rating-' + productId);

        stars.forEach((star, index) => {
            star.addEventListener('click', () => {
                const rating = index + 1;
                ratingInput.value = rating;

                // Update star display
                stars.forEach((s, i) => {
                    if (i < rating) {
                        s.classList.remove('far');
                        s.classList.add('fas');
                    } else {
                        s.classList.remove('fas');
                        s.classList.add('far');
                    }
                });
            });

            // Hover effect
            star.addEventListener('mouseenter', () => {
                stars.forEach((s, i) => {
                    if (i <= index) {
                        s.classList.add('active');
                    } else {
                        s.classList.remove('active');
                    }
                });
            });
        });

        ratingContainer.addEventListener('mouseleave', () => {
            stars.forEach(s => s.classList.remove('active'));
        });
    });

    // Submit review
    async function submitReview(productId, orderId) {
        // Keep IDs as string to preserve large Snowflake IDs
        productId = String(productId);
        orderId = String(orderId);

        // Get elements
        const ratingElement = document.getElementById('rating-' + productId);
        const commentElement = document.getElementById('comment-' + productId);
        const reviewForm = document.getElementById('review-form-' + productId);

        // Validate elements exist
        if (!ratingElement || !commentElement) {
            notify.error('Lỗi', 'Form đánh giá không tồn tại. Vui lòng tải lại trang.');
            console.error('Review form elements not found for product:', productId);
            return;
        }

        const rating = ratingElement.value;
        const comment = commentElement.value.trim();

        // Validation
        if (!rating || rating == '0') {
            notify.warning('Thiếu đánh giá', 'Vui lòng chọn số sao đánh giá');
            return;
        }

        if (!comment || comment.length < 10) {
            notify.warning('Thiếu nhận xét', 'Nhận xét phải có ít nhất 10 ký tự');
            commentElement.focus();
            return;
        }

        // Disable button and show loading
        const submitBtn = event.target;
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="loading-icon-inline"></span> Đang gửi...';

        try {
            const response = await fetch('<?= url('api/submit-review.php') ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    product_id: productId,
                    order_id: orderId,
                    rating: parseInt(rating),
                    comment: comment
                })
            });

            if (!response.ok) {
                throw new Error('Network response was not ok');
            }

            const result = await response.json();

            if (result.success) {
                notify.success('Thành công!', result.message || 'Cảm ơn bạn đã đánh giá!');
                // Replace form with success message - NO PAGE RELOAD
                if (reviewForm) {
                    reviewForm.style.transition = 'all 0.3s ease';
                    reviewForm.style.opacity = '0';
                    setTimeout(() => {
                        reviewForm.innerHTML = `
                        <div class="alert alert-success" style="opacity: 0; transition: all 0.3s ease;">
                            <i class="fas fa-check-circle"></i> Bạn đã đánh giá sản phẩm này trong đơn hàng này
                        </div>
                    `;
                        setTimeout(() => {
                            reviewForm.style.opacity = '1';
                            reviewForm.querySelector('.alert').style.opacity = '1';
                        }, 50);
                    }, 300);
                }
            } else {
                notify.error('Lỗi', result.message || 'Không thể gửi đánh giá');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        } catch (error) {
            console.error('Review submission error:', error);
            notify.error('Lỗi', 'Có lỗi xảy ra khi gửi đánh giá. Vui lòng thử lại.');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    }

    // Copy order number to clipboard
    async function copyOrderNumber(orderNumber) {
        const clickedBadge = event.currentTarget;

        try {
            // Copy to clipboard
            await navigator.clipboard.writeText(orderNumber);

            // Add copied class
            clickedBadge.classList.add('copied');

            // Change icon temporarily
            const icon = clickedBadge.querySelector('.copy-icon');
            const originalClass = icon.className;
            icon.className = 'fas fa-check copy-icon';

            // Reset after 2 seconds
            setTimeout(() => {
                clickedBadge.classList.remove('copied');
                icon.className = originalClass;
            }, 2000);

            // Show notification
            if (typeof notify !== 'undefined') {
                notify.success('Đã copy!', `Mã đơn ${orderNumber}`);
            }
        } catch (error) {
            console.error('Failed to copy:', error);

            // Fallback for older browsers
            const textArea = document.createElement('textarea');
            textArea.value = orderNumber;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            document.body.appendChild(textArea);
            textArea.select();

            try {
                document.execCommand('copy');
                if (typeof notify !== 'undefined') {
                    notify.success('Đã copy!', `Mã đơn ${orderNumber}`);
                }
            } catch (err) {
                if (typeof notify !== 'undefined') {
                    notify.error('Lỗi', 'Không thể copy mã đơn');
                }
            }

            document.body.removeChild(textArea);
        }
    }

</script>

<script>
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(function () {
            if (typeof notify !== 'undefined') {
                notify.success('Đã copy!', `Mã đơn ${text}`);
            } else {
                alert('Đã copy: ' + text);
            }
        }).catch(function (err) {
            console.error('Copy failed:', err);
            if (typeof notify !== 'undefined') {
                notify.error('Lỗi', 'Không thể copy');
            }
        });
    }
</script>