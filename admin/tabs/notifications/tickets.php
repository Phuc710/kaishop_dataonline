<?php
// Get filters
$status_filter = $_GET['status_filter'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query
$where_conditions = [];
$params = [];

if ($status_filter != 'all') {
    $where_conditions[] = "t.status = ?";
    $params[] = $status_filter;
}

if (!empty($search)) {
    $where_conditions[] = "(t.ticket_number LIKE ? OR t.subject LIKE ? OR u.username LIKE ? OR u.id LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

$where_sql = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get tickets with user info - ORDER BY created_at DESC
$sql = "SELECT t.*, u.username, u.email,
        (SELECT COUNT(*) FROM ticket_messages WHERE ticket_id = t.id) as message_count
        FROM tickets t
        LEFT JOIN users u ON t.user_id = u.id
        $where_sql
        ORDER BY t.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tickets = $stmt->fetchAll();

// Get statistics
$stats = [
    'total' => $pdo->query("SELECT COUNT(*) FROM tickets")->fetchColumn(),
    'open' => $pdo->query("SELECT COUNT(*) FROM tickets WHERE status='open'")->fetchColumn(),
    'answered' => $pdo->query("SELECT COUNT(*) FROM tickets WHERE status='answered'")->fetchColumn(),
    'closed' => $pdo->query("SELECT COUNT(*) FROM tickets WHERE status='closed'")->fetchColumn(),
];
?>

<style>
    .tickets-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .ticket-stat-card {
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.05), rgba(124, 58, 237, 0.05));
        border: 1px solid rgba(139, 92, 246, 0.2);
        border-radius: 12px;
        padding: 1.5rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        transition: all 0.3s ease;
    }

    .ticket-stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(139, 92, 246, 0.2);
        border-color: rgba(139, 92, 246, 0.4);
    }

    .ticket-stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }

    .ticket-stat-icon.primary {
        background: linear-gradient(135deg, #8b5cf6, #7c3aed);
        color: white;
    }

    .ticket-stat-icon.success {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
    }

    .ticket-stat-icon.info {
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        color: white;
    }

    .ticket-stat-icon.danger {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        color: white;
    }

    .ticket-stat-info h3 {
        font-size: 2rem;
        font-weight: 700;
        color: #8b5cf6;
        margin: 0;
    }

    .ticket-stat-info p {
        margin: 0;
        color: #94a3b8;
        font-size: 0.9rem;
    }

    .tickets-filter-bar {
        background: rgba(139, 92, 246, 0.05);
        border: 1px solid rgba(139, 92, 246, 0.2);
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 2rem;
    }

    .filter-form {
        display: flex;
        gap: 1rem;
        align-items: center;
        flex-wrap: wrap;
    }

    .filter-input {
        flex: 1;
        min-width: 250px;
        padding: 0.75rem 1rem;
        background: rgba(15, 23, 42, 0.5);
        border: 1px solid rgba(139, 92, 246, 0.2);
        border-radius: 8px;
        color: #e2e8f0;
        font-size: 0.95rem;
    }

    .filter-input:focus {
        outline: none;
        border-color: #8b5cf6;
        box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
    }

    .filter-select {
        padding: 0.75rem 1rem;
        background: rgba(15, 23, 42, 0.5);
        border: 1px solid rgba(139, 92, 246, 0.2);
        border-radius: 8px;
        color: #e2e8f0;
        font-size: 0.95rem;
        min-width: 150px;
    }

    .filter-select:focus {
        outline: none;
        border-color: #8b5cf6;
    }

    .btn-filter {
        padding: 0.75rem 1.5rem;
        background: linear-gradient(135deg, #8b5cf6, #7c3aed);
        color: white;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .btn-filter:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(139, 92, 246, 0.4);
    }

    .btn-clear {
        padding: 0.75rem 1.5rem;
        background: rgba(239, 68, 68, 0.1);
        color: #ef4444;
        border: 1px solid rgba(239, 68, 68, 0.3);
        border-radius: 8px;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.3s ease;
    }

    .btn-clear:hover {
        background: rgba(239, 68, 68, 0.2);
    }

    .tickets-table-container {
        background: rgba(139, 92, 246, 0.05);
        border: 1px solid rgba(139, 92, 246, 0.2);
        border-radius: 12px;
        overflow: hidden;
    }

    .tickets-table {
        width: 100%;
        border-collapse: collapse;
    }

    .tickets-table thead {
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.2), rgba(124, 58, 237, 0.2));
    }

    .tickets-table th {
        padding: 1rem;
        text-align: left;
        font-weight: 700;
        color: #e2e8f0;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .tickets-table td {
        padding: 1rem;
        border-top: 1px solid rgba(139, 92, 246, 0.1);
        color: #cbd5e1;
    }

    .tickets-table tbody tr {
        transition: all 0.2s ease;
    }

    .tickets-table tbody tr:hover {
        background: rgba(139, 92, 246, 0.1);
    }

    .ticket-badge {
        padding: 0.4rem 0.8rem;
        border-radius: 6px;
        font-size: 0.85rem;
        font-weight: 600;
        display: inline-block;
    }

    .badge-primary {
        background: rgba(139, 92, 246, 0.2);
        color: #a78bfa;
    }

    .badge-success {
        background: rgba(16, 185, 129, 0.2);
        color: #34d399;
    }

    .badge-warning {
        background: rgba(251, 146, 60, 0.2);
        color: #fb923c;
    }

    .badge-danger {
        background: rgba(239, 68, 68, 0.2);
        color: #f87171;
    }

    .badge-info {
        background: rgba(59, 130, 246, 0.2);
        color: #60a5fa;
    }

    .badge-secondary {
        background: rgba(148, 163, 184, 0.2);
        color: #94a3b8;
    }

    .ticket-status-select {
        padding: 0.5rem 0.75rem;
        background: rgba(15, 23, 42, 0.8);
        border: 1px solid rgba(139, 92, 246, 0.2);
        border-radius: 6px;
        color: #e2e8f0;
        font-size: 0.85rem;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .ticket-status-select:hover {
        border-color: #8b5cf6;
    }

    .ticket-actions {
        display: flex;
        gap: 0.5rem;
    }

    .btn-action {
        padding: 0.5rem 1rem;
        border: none;
        border-radius: 6px;
        font-size: 0.85rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-view {
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        color: white;
    }

    .btn-view:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
    }

    .btn-delete {
        background: rgba(239, 68, 68, 0.1);
        color: #ef4444;
        border: 1px solid rgba(239, 68, 68, 0.3);
    }

    .btn-delete:hover {
        background: rgba(239, 68, 68, 0.2);
    }

    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
    }

    .empty-state-icon {
        font-size: 4rem;
        color: #ffffff;
        margin-bottom: 1rem;
    }

    .empty-state h3 {
        color: #e2e8f0;
        font-size: 1.5rem;
        margin-bottom: 0.5rem;
    }

    .empty-state p {
        color: #94a3b8;
    }
</style>

<!-- Statistics Cards -->
<div class="tickets-stats">
    <div class="ticket-stat-card">
        <div class="ticket-stat-icon primary">
            <i class="fas fa-ticket-alt"></i>
        </div>
        <div class="ticket-stat-info">
            <h3><?= number_format($stats['total']) ?></h3>
            <p>Tổng Tickets</p>
        </div>
    </div>

    <div class="ticket-stat-card">
        <div class="ticket-stat-icon success">
            <i class="fas fa-folder-open"></i>
        </div>
        <div class="ticket-stat-info">
            <h3><?= number_format($stats['open']) ?></h3>
            <p>Đang Mở</p>
        </div>
    </div>

    <div class="ticket-stat-card">
        <div class="ticket-stat-icon info">
            <i class="fas fa-reply"></i>
        </div>
        <div class="ticket-stat-info">
            <h3><?= number_format($stats['answered']) ?></h3>
            <p>Đã Trả Lời</p>
        </div>
    </div>

    <div class="ticket-stat-card">
        <div class="ticket-stat-icon danger">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="ticket-stat-info">
            <h3><?= number_format($stats['closed']) ?></h3>
            <p>Đã Đóng</p>
        </div>
    </div>
</div>

<!-- Filter Bar -->
<div class="tickets-filter-bar">
    <form method="GET" class="filter-form">
        <input type="hidden" name="tab" value="notifications">
        <input type="hidden" name="subtab" value="tickets">

        <input type="text" name="search" placeholder="🔍 Tìm kiếm mã ticket, tiêu đề, username, User ID..."
            value="<?= htmlspecialchars($search) ?>" class="filter-input">

        <select name="status_filter" class="filter-select">
            <option value="all" <?= $status_filter == 'all' ? 'selected' : '' ?>>📋 Tất cả trạng thái</option>
            <option value="open" <?= $status_filter == 'open' ? 'selected' : '' ?>>🟢 Đang mở</option>
            <option value="answered" <?= $status_filter == 'answered' ? 'selected' : '' ?>>✅ Đã trả lời</option>
            <option value="closed" <?= $status_filter == 'closed' ? 'selected' : '' ?>>🔒 Đã đóng</option>
        </select>

        <button type="submit" class="btn-filter">
            <i class="fas fa-search"></i> Lọc
        </button>

        <?php if ($status_filter != 'all' || !empty($search)): ?>
            <a href="?tab=notifications&subtab=tickets" class="btn-clear">
                <i class="fas fa-times"></i> Xóa bộ lọc
            </a>
        <?php endif; ?>
    </form>
</div>

<!-- Tickets Table -->
<div class="tickets-table-container">
    <?php if (empty($tickets)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">
                <i class="fas fa-inbox"></i>
            </div>
            <h3>Không Có Tickets</h3>
            <p>Chưa có ticket nào hoặc không tìm thấy kết quả phù hợp</p>
        </div>
    <?php else: ?>
        <table class="tickets-table">
            <thead>
                <tr>
                    <th>Mã Ticket</th>
                    <th>Khách Hàng</th>
                    <th>Tiêu Đề</th>
                    <th>Trạng Thái</th>
                    <th>Tin Nhắn</th>
                    <th>Ngày Tạo</th>
                    <th>Thao Tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tickets as $ticket): ?>
                    <tr>
                        <td>
                            <span class="ticket-badge badge-primary">
                                <?= htmlspecialchars($ticket['ticket_number']) ?>
                            </span>
                        </td>
                        <td>
                            <div>
                                <strong style="color: #e2e8f0;"><?= htmlspecialchars($ticket['username']) ?></strong>
                                <br>
                                <small style="color: #64748b;"><?= htmlspecialchars($ticket['email']) ?></small>
                            </div>
                        </td>
                        <td>
                            <div style="max-width: 300px; color: #cbd5e1;">
                                <?= htmlspecialchars($ticket['subject']) ?>
                            </div>
                        </td>
                        <td>
                            <select class="ticket-status-select" data-ticket-id="<?= $ticket['id'] ?>">
                                <option value="open" <?= $ticket['status'] == 'open' ? 'selected' : '' ?>>🟢 Đang mở</option>
                                <option value="answered" <?= $ticket['status'] == 'answered' ? 'selected' : '' ?>>✅ Đã trả lời
                                </option>
                                <option value="closed" <?= $ticket['status'] == 'closed' ? 'selected' : '' ?>>🔒 Đã đóng</option>
                            </select>
                        </td>
                        <td>
                            <span class="ticket-badge badge-info">
                                <i class="fas fa-comments"></i> <?= $ticket['message_count'] ?>
                            </span>
                        </td>
                        <td style="color: #94a3b8;">
                            <?= date('d/m/Y H:i', strtotime($ticket['created_at'])) ?>
                        </td>
                        <td>
                            <div class="ticket-actions">
                                <a href="<?= url('ticket/view?id=' . $ticket['id']) ?>" class="btn-action btn-view"
                                    target="_blank">
                                    <i class="fas fa-eye"></i> Xem
                                </a>
                                <button class="btn-action btn-delete delete-ticket-btn" data-ticket-id="<?= $ticket['id'] ?>"
                                    data-ticket-number="<?= htmlspecialchars($ticket['ticket_number']) ?>">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<script>
    // Update ticket status
    document.querySelectorAll('.ticket-status-select').forEach(select => {
        select.addEventListener('change', async function () {
            const ticketId = this.dataset.ticketId;
            const newStatus = this.value;
            const originalValue = this.querySelector('option[selected]')?.value || this.value;

            try {
                const response = await fetch('<?= url('api/tickets') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'update_status',
                        ticket_id: ticketId,
                        status: newStatus
                    })
                });

                const result = await response.json();

                if (result.success) {
                    if (window.notify) {
                        notify.success('Thành công!', 'Đã cập nhật trạng thái ticket');
                    }

                    // Update the selected attribute
                    this.querySelectorAll('option').forEach(opt => {
                        opt.removeAttribute('selected');
                        if (opt.value === newStatus) {
                            opt.setAttribute('selected', 'selected');
                        }
                    });
                } else {
                    if (window.notify) {
                        notify.error('Lỗi!', result.message || 'Không thể cập nhật trạng thái');
                    }
                    this.value = originalValue;
                }
            } catch (error) {
                if (window.notify) {
                    notify.error('Lỗi!', 'Lỗi kết nối server');
                }
                this.value = originalValue;
            }
        });
    });

    // Delete ticket
    document.querySelectorAll('.delete-ticket-btn').forEach(btn => {
        btn.addEventListener('click', async function () {
            const ticketId = this.dataset.ticketId;
            const ticketNumber = this.dataset.ticketNumber;

            const confirmed = await notify.confirm({
                title: 'Xác nhận xóa ticket',
                message: `Bạn có chắc muốn xóa ticket ${ticketNumber}?\n\n⚠️ Lưu ý: Xóa ticket sẽ xóa luôn tất cả tin nhắn liên quan!`,
                type: 'warning',
                confirmText: 'Xóa',
                cancelText: 'Hủy'
            });

            if (!confirmed) {
                return;
            }

            try {
                const response = await fetch('<?= url('api/tickets') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'delete',
                        ticket_id: ticketId
                    })
                });

                const result = await response.json();

                if (result.success) {
                    if (window.notify) {
                        notify.success('Thành công!', 'Đã xóa ticket');
                    }
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    if (window.notify) {
                        notify.error('Lỗi!', result.message || 'Không thể xóa ticket');
                    }
                }
            } catch (error) {
                if (window.notify) {
                    notify.error('Lỗi!', 'Lỗi kết nối server');
                }
            }
        });
    });
</script>