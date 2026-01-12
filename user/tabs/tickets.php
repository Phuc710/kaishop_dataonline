<?php
// Get tickets
$stmt = $pdo->prepare("SELECT * FROM tickets WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$tickets = $stmt->fetchAll();
?>

<style>
/* Mobile Responsive for Tickets */
@media (max-width: 768px) {
    .tickets-table-wrapper {
        display: none;
    }
    .tickets-mobile-list {
        display: flex !important;
    }
}
@media (min-width: 769px) {
    .tickets-mobile-list {
        display: none !important;
    }
}
.tickets-mobile-list {
    display: none;
    flex-direction: column;
    gap: 0.75rem;
}
.ticket-card-mobile {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 1rem;
    transition: all 0.2s;
}
.ticket-card-mobile:hover {
    border-color: var(--primary);
    transform: translateY(-2px);
}
.ticket-card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 0.75rem;
}
.ticket-card-id {
    background: rgba(139, 92, 246, 0.15);
    color: #8b5cf6;
    padding: 0.3rem 0.5rem;
    border-radius: 6px;
    font-weight: 600;
    font-size: 0.75rem;
}
.ticket-card-title {
    font-weight: 600;
    color: var(--text-main);
    font-size: 0.95rem;
    margin-bottom: 0.5rem;
}
.ticket-card-meta {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
    margin-bottom: 0.75rem;
}
.ticket-card-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 0.75rem;
    border-top: 1px solid var(--border-color);
}
.ticket-card-date {
    color: var(--text-muted);
    font-size: 0.8rem;
}
</style>

<div class="page-header fade-in">
    <div>
        <h1><i class="fas fa-ticket-alt"></i> Tickets Hỗ Trợ</h1>
        <p>Quản lý các yêu cầu hỗ trợ của bạn</p>
    </div>
    <a href="<?= url('ticket/create') ?>" class="btn btn-primary">
        <i class="fas fa-plus"></i> Tạo Ticket Mới
    </a>
</div>

<?php if (empty($tickets)): ?>
    <div class="card fade-in">
        <div class="empty-state">
            <div class="empty-state-icon">
                <i class="fas fa-headset"></i>
            </div>
            <h3>Chưa Có Ticket</h3>
            <p>Bạn chưa tạo ticket hỗ trợ nào</p>
            <a href="<?= url('ticket/create') ?>" class="btn btn-primary">
                <i class="fas fa-plus-circle"></i> Tạo Ticket Đầu Tiên
            </a>
        </div>
    </div>
<?php else: ?>
    <!-- Desktop Table -->
    <div class="table-wrapper fade-in tickets-table-wrapper">
        <table class="modern-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tiêu Đề</th>
                    <th>Danh Mục</th>
                    <th>Trạng Thái</th>
                    <th>Ngày Tạo</th>
                    <th style="text-align: right;">Hành Động</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tickets as $ticket): 
                    $status_map = [
                        'open' => ['Đang Mở', 'badge-success'],
                        'pending' => ['Chờ Xử Lý', 'badge-warning'],
                        'closed' => ['Đã Đóng', 'badge-dark'],
                        'answered' => ['Đã Trả Lời', 'badge-info']
                    ];
                    $s = $status_map[$ticket['status']] ?? [ucfirst($ticket['status']), 'badge-secondary'];
                ?>
                <tr>
                    <td>
                        <code style="background:rgba(139,92,246,0.15);color:#8b5cf6;padding:0.4rem 0.6rem;border-radius:6px;font-weight:600;font-size:0.8rem">
                            #<?= $ticket['ticket_number'] ?>
                        </code>
                    </td>
                    <td>
                        <span style="font-weight: 600; color: var(--text-main);"><?= e($ticket['subject']) ?></span>
                    </td>
                    <td>
                        <span class="badge" style="background: rgba(30, 41, 59, 0.5); border: 1px solid var(--border); color: var(--text-muted);">
                            <?= strtoupper(e($ticket['category'] ?? 'GENERAL')) ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge <?= $s[1] ?>"><?= $s[0] ?></span>
                    </td>
                    <td>
                        <div style="color: var(--text-main); font-size: 0.9rem; font-weight: 500;">
                            <?= date('d/m/Y', strtotime($ticket['created_at'])) ?>
                        </div>
                        <small style="color: var(--text-muted);"><?= date('H:i', strtotime($ticket['created_at'])) ?></small>
                    </td>
                    <td style="text-align: right;">
                        <a href="<?= url('ticket/view?id=' . $ticket['id']) ?>" class="btn btn-sm btn-secondary">
                            <i class="fas fa-eye"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Mobile Cards -->
    <div class="tickets-mobile-list fade-in">
        <?php foreach ($tickets as $ticket): 
            $status_map = [
                'open' => ['Đang Mở', 'badge-success'],
                'pending' => ['Chờ Xử Lý', 'badge-warning'],
                'closed' => ['Đã Đóng', 'badge-dark'],
                'answered' => ['Đã Trả Lời', 'badge-info']
            ];
            $s = $status_map[$ticket['status']] ?? [ucfirst($ticket['status']), 'badge-secondary'];
        ?>
        <a href="<?= url('ticket/view?id=' . $ticket['id']) ?>" class="ticket-card-mobile">
            <div class="ticket-card-header">
                <span class="ticket-card-id">#<?= $ticket['ticket_number'] ?></span>
                <span class="badge <?= $s[1] ?>"><?= $s[0] ?></span>
            </div>
            <div class="ticket-card-title"><?= e($ticket['subject']) ?></div>
            <div class="ticket-card-meta">
                <span class="badge" style="background: rgba(30, 41, 59, 0.5); border: 1px solid var(--border); color: var(--text-muted); font-size: 0.7rem;">
                    <?= strtoupper(e($ticket['category'] ?? 'GENERAL')) ?>
                </span>
            </div>
            <div class="ticket-card-footer">
                <span class="ticket-card-date">
                    <i class="fas fa-clock"></i> <?= date('d/m/Y H:i', strtotime($ticket['created_at'])) ?>
                </span>
                <span style="color: var(--primary); font-size: 0.8rem; font-weight: 600;">
                    Xem chi tiết <i class="fas fa-chevron-right"></i>
                </span>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        if (typeof notify !== 'undefined') {
            notify.success('Đã copy!', `Mã ticket ${text}`);
        }
    }).catch(function(err) {
        if (typeof notify !== 'undefined') {
            notify.error('Lỗi', 'Không thể copy mã ticket');
        }
    });
}
</script>
