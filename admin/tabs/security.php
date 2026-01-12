<?php
/**
 * Enhanced Security Management Dashboard with Tabbed Interface
 * Tabs: Overview, Banned Users, Blocked IPs, Blocked Fingerprints, Security Logs
 */

if (!isLoggedIn() || !isAdmin()) {
    redirect(url('403'));
}

require_once __DIR__ . '/../../config/SecurityLogger.php';
require_once __DIR__ . '/../../config/RateLimiter.php';

$logger = new SecurityLogger($pdo);
$rateLimiter = new RateLimiter($pdo);

// Get current admin info - PROTECTED
$currentAdminIP = SecurityLogger::getRealIP();
$currentAdminFingerprint = SecurityLogger::getFingerprint();

// Get active subtab
$subtab = $_GET['subtab'] ?? 'overview';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // FINGERPRINT BLOCKING
    if ($action === 'block_fingerprint' && isset($_POST['fingerprint'])) {
        $fingerprint = $_POST['fingerprint'];

        // PROTECT ADMIN
        if ($fingerprint === $currentAdminFingerprint) {
            echo "<script>notify.error('Lỗi', 'Không thể block fingerprint của chính bạn!');</script>";
        } else {
            $reason = $_POST['reason'] ?? 'Manual block by admin';
            $duration = isset($_POST['permanent']) ? null : ($_POST['duration'] ?? 3600);
            $is_permanent = isset($_POST['permanent']);

            if ($rateLimiter->blockFingerprint($fingerprint, $reason, $duration, $is_permanent)) {
                $shortFp = substr($fingerprint, 0, 8);
                echo "<script>notify.success('Thành công', 'Đã block Fingerprint: {$shortFp}...');</script>";
            }
        }
    }

    if ($action === 'unblock_fingerprint' && isset($_POST['fingerprint'])) {
        $fingerprint = $_POST['fingerprint'];
        if ($rateLimiter->unblockFingerprint($fingerprint)) {
            $shortFp = substr($fingerprint, 0, 8);
            echo "<script>notify.success('Thành công', 'Đã unblock Fingerprint: {$shortFp}...');</script>";
        }
    }

    // IP BLOCKING
    if ($action === 'block_ip' && isset($_POST['ip'])) {
        $ip = $_POST['ip'];

        // PROTECT ADMIN
        if ($ip === $currentAdminIP) {
            echo "<script>notify.error('Lỗi', 'Không thể block IP của chính bạn!');</script>";
        } else {
            $reason = $_POST['reason'] ?? 'Manual block by admin';
            $duration = isset($_POST['permanent']) ? null : ($_POST['duration'] ?? 3600);
            $is_permanent = isset($_POST['permanent']);

            if ($rateLimiter->blockIP($ip, $reason, $duration, $is_permanent)) {
                echo "<script>notify.success('Thành công', 'Đã block IP: {$ip}');</script>";
            }
        }
    }

    if ($action === 'unblock_ip' && isset($_POST['ip'])) {
        $ip = $_POST['ip'];
        if ($rateLimiter->unblockIP($ip)) {
            echo "<script>notify.success('Thành công', 'Đã unblock IP: {$ip}');</script>";
        }
    }
}

// Get statistics
$stats = $logger->getStats(7);
$blocked_fingerprints = $rateLimiter->getBlockedFingerprints(100);
$blocked_ips = $rateLimiter->getBlockedIPs(100);

// Combine for "Banned Users" tab
$all_bans = [];
foreach ($blocked_ips as $ban) {
    $all_bans[] = array_merge($ban, ['type' => 'IP']);
}
foreach ($blocked_fingerprints as $ban) {
    $all_bans[] = array_merge($ban, ['type' => 'Fingerprint']);
}
// Sort by blocked_at desc
usort($all_bans, function ($a, $b) {
    return strtotime($b['blocked_at']) - strtotime($a['blocked_at']);
});

// Security Logs pagination and filters
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$logsPerPage = 20;
$offset = ($page - 1) * $logsPerPage;

$search = $_GET['search'] ?? '';
$filter_threat = $_GET['threat'] ?? 'all';
$sort = $_GET['sort'] ?? 'newest';

$where = [];
$params = [];

if ($search) {
    $where[] = "(ip LIKE :search OR fingerprint LIKE :search OR country_code LIKE :search)";
    $params[':search'] = "%$search%";
}

if ($filter_threat !== 'all') {
    $where[] = "threat_level = :threat";
    $params[':threat'] = $filter_threat;
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM security_logs $whereClause");
$stmt->execute($params);
$totalLogs = $stmt->fetch()['total'];
$totalPages = ceil($totalLogs / $logsPerPage);

$orderBy = match ($sort) {
    'oldest' => 'created_at ASC',
    default => 'created_at DESC'
};

$sql = "SELECT * FROM security_logs $whereClause ORDER BY $orderBy LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);

foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $logsPerPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$recent_logs = $stmt->fetchAll();

?>

<style>
    /* Tab Navigation */
    .security-tabs {
        display: flex;
        gap: 0.5rem;
        margin-bottom: 2rem;
        border-bottom: 2px solid rgba(139, 92, 246, 0.2);
        overflow-x: auto;
    }

    .security-tab {
        padding: 1rem 1.5rem;
        background: transparent;
        border: none;
        color: #94a3b8;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        border-bottom: 3px solid transparent;
        white-space: nowrap;
    }

    .security-tab:hover {
        color: #f8fafc;
        background: rgba(139, 92, 246, 0.1);
    }

    .security-tab.active {
        color: #a78bfa;
        border-bottom-color: #8b5cf6;
        background: rgba(139, 92, 246, 0.15);
    }

    .security-tab i {
        margin-right: 0.5rem;
    }

    .tab-content {
        display: none;
    }

    .tab-content.active {
        display: block;
    }
</style>

<div class="page-header">
    <div>
        <h1><i class="fas fa-shield-alt"></i> Quản Lý Bảo Mật</h1>
        <p>Quản lý IP blocklist, fingerprint blocklist và security logs</p>
    </div>
    <div style="display:flex;gap:0.5rem">
        <div
            style="background:rgba(16,185,129,0.1);padding:0.75rem 1rem;border-radius:12px;border:1px solid rgba(16,185,129,0.3)">
            <small style="color:#94a3b8">IP của bạn:</small>
            <div style="color:#10b981;font-family:monospace;font-weight:600"><?= $currentAdminIP ?></div>
        </div>
        <div
            style="background:rgba(139,92,246,0.1);padding:0.75rem 1rem;border-radius:12px;border:1px solid rgba(139,92,246,0.3)">
            <small style="color:#94a3b8">Fingerprint:</small>
            <div style="color:#a78bfa;font-family:monospace;font-weight:600" title="<?= $currentAdminFingerprint ?>">
                <?= substr($currentAdminFingerprint, 0, 8) ?>...
            </div>
        </div>
    </div>
</div>

<!-- Tab Navigation -->
<div class="security-tabs">
    <button class="security-tab <?= $subtab === 'overview' ? 'active' : '' ?>" onclick="switchTab('overview')">
        <i class="fas fa-tachometer-alt"></i> Overview
    </button>
    <button class="security-tab <?= $subtab === 'banned' ? 'active' : '' ?>" onclick="switchTab('banned')">
        <i class="fas fa-ban"></i> Banned Users (<?= count($all_bans) ?>)
    </button>
    <button class="security-tab <?= $subtab === 'ips' ? 'active' : '' ?>" onclick="switchTab('ips')">
        <i class="fas fa-network-wired"></i> Blocked IPs (<?= count($blocked_ips) ?>)
    </button>
    <button class="security-tab <?= $subtab === 'fingerprints' ? 'active' : '' ?>" onclick="switchTab('fingerprints')">
        <i class="fas fa-fingerprint"></i> Blocked Fingerprints (<?= count($blocked_fingerprints) ?>)
    </button>
    <button class="security-tab <?= $subtab === 'logs' ? 'active' : '' ?>" onclick="switchTab('logs')">
        <i class="fas fa-list"></i> Security Logs
    </button>
</div>

<!-- TAB 1: OVERVIEW -->
<div class="tab-content <?= $subtab === 'overview' ? 'active' : '' ?>" id="tab-overview">
    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-card-header">
                <div class="stat-icon primary">
                    <i class="fas fa-eye"></i>
                </div>
            </div>
            <div class="stat-value"><?= number_format($stats['total_events'] ?? 0) ?></div>
            <div class="stat-label">Total Events (7 days)</div>
        </div>

        <div class="stat-card">
            <div class="stat-card-header">
                <div class="stat-icon success">
                    <i class="fas fa-network-wired"></i>
                </div>
            </div>
            <div class="stat-value"><?= number_format($stats['unique_ips'] ?? 0) ?></div>
            <div class="stat-label">Unique IPs</div>
        </div>

        <div class="stat-card">
            <div class="stat-card-header">
                <div class="stat-icon danger">
                    <i class="fas fa-ban"></i>
                </div>
            </div>
            <div class="stat-value"><?= number_format($stats['blocked_requests'] ?? 0) ?></div>
            <div class="stat-label">Blocked Requests</div>
        </div>

        <div class="stat-card">
            <div class="stat-card-header">
                <div class="stat-icon warning">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
            </div>
            <div class="stat-value"><?= number_format($stats['serious_threats'] ?? 0) ?></div>
            <div class="stat-label">Serious Threats</div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="card" style="margin-top:2rem">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-bolt"></i> Quick Actions</h3>
        </div>
        <div style="padding:2rem;display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:1rem">
            <button class="btn btn-primary" onclick="showBlockIPModal()">
                <i class="fas fa-ban"></i> Block IP Address
            </button>
            <button class="btn btn-primary" onclick="showBlockFingerprintModal()">
                <i class="fas fa-fingerprint"></i> Block Fingerprint
            </button>
            <button class="btn btn-secondary" onclick="switchTab('logs')">
                <i class="fas fa-list"></i> View Security Logs
            </button>
        </div>
    </div>
</div>

<!-- TAB 2: BANNED USERS (Combined IP + Fingerprint) -->
<div class="tab-content <?= $subtab === 'banned' ? 'active' : '' ?>" id="tab-banned">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-ban"></i> All Banned Users</h3>
            <div style="display:flex;gap:0.5rem">
                <button class="btn btn-primary btn-sm" onclick="showBlockIPModal()">
                    <i class="fas fa-plus"></i> Block IP
                </button>
                <button class="btn btn-primary btn-sm" onclick="showBlockFingerprintModal()">
                    <i class="fas fa-plus"></i> Block Fingerprint
                </button>
            </div>
        </div>

        <?php if (!empty($all_bans)): ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Value</th>
                            <th>Reason</th>
                            <th>Violations</th>
                            <th>Banned Date</th>
                            <th>Status</th>
                            <th style="text-align:right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_bans as $ban): ?>
                            <tr>
                                <td>
                                    <?php if ($ban['type'] === 'IP'): ?>
                                        <span class="badge badge-info"><i class="fas fa-network-wired"></i> IP</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning"><i class="fas fa-fingerprint"></i> Fingerprint</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <code style="color:<?= $ban['type'] === 'IP' ? '#3b82f6' : '#8b5cf6' ?>;font-weight:600">
                                                <?= $ban['type'] === 'IP' ? e($ban['ip']) : substr(e($ban['fingerprint']), 0, 16) . '...' ?>
                                            </code>
                                </td>
                                <td><?= e($ban['reason']) ?></td>
                                <td><span class="badge badge-warning"><?= $ban['violation_count'] ?></span></td>
                                <td><?= date('d/m/Y H:i', strtotime($ban['blocked_at'])) ?></td>
                                <td>
                                    <?php if ($ban['is_permanent']): ?>
                                        <span class="badge badge-danger">Permanent</span>
                                    <?php elseif ($ban['expires_at'] && strtotime($ban['expires_at']) > time()): ?>
                                        <span class="badge badge-warning">Active</span>
                                    <?php else: ?>
                                        <span class="badge badge-success">Expired</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align:right">
                                    <form method="POST" style="display:inline"
                                        onsubmit="return confirm('Unblock this <?= $ban['type'] ?>?')">
                                        <input type="hidden" name="action"
                                            value="unblock_<?= strtolower($ban['type']) === 'ip' ? 'ip' : 'fingerprint' ?>">
                                        <input type="hidden"
                                            name="<?= strtolower($ban['type']) === 'ip' ? 'ip' : 'fingerprint' ?>"
                                            value="<?= e($ban['type'] === 'IP' ? $ban['ip'] : $ban['fingerprint']) ?>">
                                        <button type="submit" class="btn btn-sm btn-success">
                                            <i class="fas fa-unlock"></i> Unblock
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div style="padding:3rem;text-align:center;color:var(--text-secondary)">
                <i class="fas fa-ban" style="font-size:3rem;opacity:0.3;margin-bottom:1rem"></i>
                <p>No banned users</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- TAB 3: BLOCKED IPS -->
<div class="tab-content <?= $subtab === 'ips' ? 'active' : '' ?>" id="tab-ips">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-network-wired"></i> Blocked IP Addresses</h3>
            <button class="btn btn-primary btn-sm" onclick="showBlockIPModal()">
                <i class="fas fa-plus"></i> Block New IP
            </button>
        </div>

        <?php if (!empty($blocked_ips)): ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>IP Address</th>
                            <th>Country</th>
                            <th>Last Fingerprint</th>
                            <th>Reason</th>
                            <th>Violations</th>
                            <th>Banned At</th>
                            <th>Status</th>
                            <th style="text-align:right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($blocked_ips as $block): ?>
                            <tr>
                                <td>
                                    <code style="color:#3b82f6;font-weight:600"><?= e($block['ip']) ?></code>
                                    <?php if ($block['ip'] === $currentAdminIP): ?>
                                        <span class="badge badge-success" style="margin-left:0.5rem">YOU</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= e($block['country_code'] ?? '-') ?></td>
                                <td>
                                    <?php if ($block['last_seen_fingerprint']): ?>
                                        <code
                                            style="color:#8b5cf6;font-size:0.75rem"><?= substr(e($block['last_seen_fingerprint']), 0, 12) ?>...</code>
                                    <?php else: ?>
                                        <span style="color:#64748b">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= e($block['reason']) ?></td>
                                <td><span class="badge badge-warning"><?= $block['violation_count'] ?></span></td>
                                <td><?= date('d/m/Y H:i', strtotime($block['blocked_at'])) ?></td>
                                <td>
                                    <?php if ($block['is_permanent']): ?>
                                        <span class="badge badge-danger">Permanent</span>
                                    <?php elseif ($block['expires_at'] && strtotime($block['expires_at']) > time()): ?>
                                        <span class="badge badge-warning">Active</span>
                                    <?php else: ?>
                                        <span class="badge badge-success">Expired</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align:right">
                                    <form method="POST" style="display:inline" onsubmit="return confirm('Unblock this IP?')">
                                        <input type="hidden" name="action" value="unblock_ip">
                                        <input type="hidden" name="ip" value="<?= e($block['ip']) ?>">
                                        <button type="submit" class="btn btn-sm btn-success">
                                            <i class="fas fa-unlock"></i> Unblock
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div style="padding:3rem;text-align:center;color:var(--text-secondary)">
                <i class="fas fa-network-wired" style="font-size:3rem;opacity:0.3;margin-bottom:1rem"></i>
                <p>No blocked IPs</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- TAB 4: BLOCKED FINGERPRINTS -->
<div class="tab-content <?= $subtab === 'fingerprints' ? 'active' : '' ?>" id="tab-fingerprints">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-fingerprint"></i> Blocked Fingerprints</h3>
            <button class="btn btn-primary btn-sm" onclick="showBlockFingerprintModal()">
                <i class="fas fa-plus"></i> Block New Fingerprint
            </button>
        </div>

        <?php if (!empty($blocked_fingerprints)): ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Fingerprint</th>
                            <th>Last Seen IP</th>
                            <th>Country/City</th>
                            <th>Reason</th>
                            <th>Violations</th>
                            <th>Banned At</th>
                            <th>Status</th>
                            <th style="text-align:right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($blocked_fingerprints as $block): ?>
                            <tr>
                                <td>
                                    <div style="display:flex;flex-direction:column;gap:0.25rem">
                                        <code
                                            style="color:#8b5cf6;font-weight:600;font-size:0.85rem;word-break:break-all"><?= e($block['fingerprint']) ?></code>
                                        <?php if ($block['fingerprint'] === $currentAdminFingerprint): ?>
                                            <span class="badge badge-success" style="width:fit-content">YOU</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td><code style="color:#64748b"><?= e($block['last_seen_ip'] ?? '-') ?></code></td>
                                <td>
                                    <?php if ($block['country_code'] || $block['last_seen_city']): ?>
                                        <div style="display:flex;flex-direction:column">
                                            <span><?= e($block['country_code'] ?? '-') ?></span>
                                            <small style="color:#64748b"><?= e($block['last_seen_city'] ?? '') ?></small>
                                        </div>
                                    <?php else: ?>
                                        <span style="color:#64748b">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= e($block['reason']) ?></td>
                                <td><span class="badge badge-warning"><?= $block['violation_count'] ?></span></td>
                                <td><?= date('d/m/Y H:i', strtotime($block['blocked_at'])) ?></td>
                                <td>
                                    <?php if ($block['is_permanent']): ?>
                                        <span class="badge badge-danger">Permanent</span>
                                    <?php elseif ($block['expires_at'] && strtotime($block['expires_at']) > time()): ?>
                                        <span class="badge badge-warning">Active</span>
                                    <?php else: ?>
                                        <span class="badge badge-success">Expired</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align:right">
                                    <form method="POST" style="display:inline"
                                        onsubmit="return confirm('Unblock this fingerprint?')">
                                        <input type="hidden" name="action" value="unblock_fingerprint">
                                        <input type="hidden" name="fingerprint" value="<?= e($block['fingerprint']) ?>">
                                        <button type="submit" class="btn btn-sm btn-success">
                                            <i class="fas fa-unlock"></i> Unblock
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div style="padding:3rem;text-align:center;color:var(--text-secondary)">
                <i class="fas fa-fingerprint" style="font-size:3rem;opacity:0.3;margin-bottom:1rem"></i>
                <p>No blocked fingerprints</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- TAB 5: SECURITY LOGS -->
<div class="tab-content <?= $subtab === 'logs' ? 'active' : '' ?>" id="tab-logs">
    <?php require __DIR__ . '/security_logs_tab.php'; ?>
</div>

<!-- Block IP Modal -->
<script>
    function switchTab(tab) {
        // Update URL
        const url = new URL(window.location);
        url.searchParams.set('subtab', tab);
        window.history.pushState({}, '', url);

        // Update tabs
        document.querySelectorAll('.security-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));

        document.querySelector(`.security-tab[onclick*="${tab}"]`).classList.add('active');
        document.getElementById(`tab-${tab}`).classList.add('active');
    }

    function showBlockIPModal() {
        const ip = prompt('Enter IP address to block:');
        if (!ip) return;

        // Validate IP format
        if (!ip.match(/^(\d{1,3}\.){3}\d{1,3}$/)) {
            alert('Invalid IP address format!');
            return;
        }

        const reason = prompt('Reason for blocking:', 'Manual block by admin');
        const permanent = confirm('Permanent block? (Cancel for 1-hour temporary block)');

        // Create and submit form
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="block_ip">
            <input type="hidden" name="ip" value="${ip}">
            <input type="hidden" name="reason" value="${reason || 'Manual block by admin'}">
            ${permanent ? '<input type="hidden" name="permanent" value="1">' : ''}
        `;
        document.body.appendChild(form);
        form.submit();
    }

    function showBlockFingerprintModal() {
        const fingerprint = prompt('Enter fingerprint hash to block:');
        if (!fingerprint) return;

        // Validate fingerprint format (should be hex string)
        if (fingerprint.length < 8) {
            alert('Fingerprint should be at least 8 characters!');
            return;
        }

        const reason = prompt('Reason for blocking:', 'Manual block by admin');
        const permanent = confirm('Permanent block? (Cancel for 1-hour temporary block)');

        // Create and submit form
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="block_fingerprint">
            <input type="hidden" name="fingerprint" value="${fingerprint}">
            <input type="hidden" name="reason" value="${reason || 'Manual block by admin'}">
            ${permanent ? '<input type="hidden" name="permanent" value="1">' : ''}
        `;
        document.body.appendChild(form);
        form.submit();
    }

    function blockFingerprintFromLog(fingerprint) {
        if (!confirm('Block this fingerprint?')) return;

        const reason = prompt('Reason for blocking:', 'Blocked from security logs');

        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="block_fingerprint">
            <input type="hidden" name="fingerprint" value="${fingerprint}">
            <input type="hidden" name="reason" value="${reason || 'Blocked from security logs'}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
</script>