<?php
/**
 * Security Logs Tab Content
 * Included in security.php for the "Security Logs" tab
 */
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-list"></i> Security Logs</h3>
    </div>
    <div style="padding:1rem">
        <form method="GET" action="" style="display:flex;gap:1rem;flex-wrap:wrap;align-items:center">
            <input type="hidden" name="tab" value="security">
            <input type="hidden" name="subtab" value="logs">

            <!-- Search -->
            <div style="flex:1;min-width:200px">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search IP, Fingerprint..."
                        value="<?= e($search) ?>">
                </div>
            </div>

            <!-- Threat Filter -->
            <select name="threat" class="form-select" style="width:auto">
                <option value="all" <?= $filter_threat === 'all' ? 'selected' : '' ?>>All Threats</option>
                <option value="low" <?= $filter_threat === 'low' ? 'selected' : '' ?>>Low</option>
                <option value="medium" <?= $filter_threat === 'medium' ? 'selected' : '' ?>>Medium</option>
                <option value="high" <?= $filter_threat === 'high' ? 'selected' : '' ?>>High</option>
                <option value="critical" <?= $filter_threat === 'critical' ? 'selected' : '' ?>>Critical</option>
            </select>

            <!-- Sort -->
            <select name="sort" class="form-select" style="width:auto">
                <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Mới nhất</option>
                <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Cũ nhất</option>
            </select>

            <button type="submit" class="btn btn-primary">
                <i class="fas fa-filter"></i> Lọc
            </button>

            <?php if ($search || $filter_threat !== 'all'): ?>
                <a href="?tab=security&subtab=logs" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Reset
                </a>
            <?php endif; ?>
        </form>
    </div>

    <style>
        /* Security Filter Styles */
        .input-group {
            display: flex;
            align-items: center;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 8px;
            padding: 0 1rem;
            transition: all 0.3s ease;
        }

        .input-group:focus-within {
            border-color: #3b82f6;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
        }

        .input-group-text {
            color: #94a3b8;
            font-size: 1rem;
            margin-right: 0.5rem;
        }

        .form-control,
        .form-select {
            background: transparent !important;
            border: none !important;
            color: #f8fafc !important;
            padding: 0.75rem 0 !important;
            font-size: 0.95rem;
            width: 100%;
        }

        .form-control:focus,
        .form-select:focus {
            outline: none !important;
            box-shadow: none !important;
        }

        .form-select {
            background: rgba(15, 23, 42, 0.6) !important;
            border: 1px solid rgba(148, 163, 184, 0.2) !important;
            border-radius: 8px !important;
            padding: 0.75rem 2rem 0.75rem 1rem !important;
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%2394a3b8' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 16px 12px;
        }

        .form-select option {
            background: #1e293b;
            color: #f8fafc;
            padding: 10px;
        }

        ::placeholder {
            color: #64748b !important;
            opacity: 1;
        }
    </style>

    <?php if (!empty($recent_logs)): ?>
        <div style="overflow-x:auto;margin-top:1rem">
            <div style="min-width:1200px">
                <table>
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>IP</th>
                            <th>Fingerprint</th>
                            <th>Threat</th>
                            <th>Attack Type</th>
                            <th>Country</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_logs as $log): ?>
                            <tr>
                                <td>
                                    <div style="color:#f8fafc">
                                        <?= date('d/m/Y', strtotime($log['created_at'])) ?>
                                    </div>
                                    <small style="color:#64748b">
                                        <?= date('H:i:s', strtotime($log['created_at'])) ?>
                                    </small>
                                </td>
                                <td>
                                    <div style="display:flex;flex-direction:column;gap:0.25rem">
                                        <code
                                            style="color:#3b82f6;font-weight:600;font-size:0.85rem"><?= e($log['ip']) ?></code>
                                    </div>
                                </td>
                                <td>
                                    <div style="display:flex;flex-direction:column;gap:0.25rem">
                                        <code
                                            style="color:#8b5cf6;font-weight:600;font-size:0.75rem;word-break:break-all;max-width:300px"><?= e($log['fingerprint'] ?? '-') ?></code>
                                    </div>
                                </td>
                                <td>
                                    <?php
                                    $threat_class = [
                                        'low' => 'badge-success',
                                        'medium' => 'badge-warning',
                                        'high' => 'badge-danger',
                                        'critical' => 'badge-danger'
                                    ];
                                    $class = $threat_class[$log['threat_level']] ?? 'badge-secondary';
                                    ?>
                                    <span class="badge <?= $class ?>">
                                        <?= strtoupper($log['threat_level']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $attackTypes = [
                                        'sql_injection' => 'Tấn công SQL Injection',
                                        'xss' => 'Tấn công XSS',
                                        'path_traversal' => 'Truy cập trái phép',
                                        'cmd_injection' => 'Chèn mã lệnh',
                                        'rate_limit' => 'Spam / Flood',
                                        'scan_tool' => 'Tool Scan / Bot',
                                        'pattern_match' => 'Nghi vấn',
                                        'user_login' => 'Đăng nhập',
                                        'blocked_ip' => 'IP bị chặn',
                                        'blocked_fingerprint' => 'Fingerprint bị chặn',
                                        'manual_block' => 'Block thủ công'
                                    ];
                                    $type = $log['attack_type'];
                                    $displayType = $attackTypes[$type] ?? $type;
                                    ?>
                                    <?= $type ? '<code>' . e($displayType) . '</code>' : '-' ?>
                                </td>
                                <td>
                                    <?php
                                    $countryData = [
                                        'VN' => ['flag' => '🇻🇳', 'name' => 'Vietnam'],
                                        'US' => ['flag' => '🇺🇸', 'name' => 'United States'],
                                        'CN' => ['flag' => '🇨🇳', 'name' => 'China'],
                                        'JP' => ['flag' => '🇯🇵', 'name' => 'Japan'],
                                        'KR' => ['flag' => '🇰🇷', 'name' => 'Korea'],
                                        'TH' => ['flag' => '🇹🇭', 'name' => 'Thailand'],
                                        'SG' => ['flag' => '🇸🇬', 'name' => 'Singapore'],
                                        'GB' => ['flag' => '🇬🇧', 'name' => 'United Kingdom'],
                                        'DE' => ['flag' => '🇩🇪', 'name' => 'Germany'],
                                        'FR' => ['flag' => '🇫🇷', 'name' => 'France'],
                                    ];
                                    $countryCode = $log['country_code'] ?? '';
                                    $country = $countryData[$countryCode] ?? ['flag' => '🌍', 'name' => $countryCode ?: 'Unknown'];
                                    ?>
                                    <div style="display:flex;align-items:center;gap:0.5rem">
                                        <span style="font-size:1.2rem">
                                            <?= $country['flag'] ?>
                                        </span>
                                        <div style="display:flex;flex-direction:column">
                                            <span style="color:#f8fafc;font-weight:600">
                                                <?= e($country['name']) ?>
                                            </span>
                                            <small style="color:#64748b">
                                                <?= e($countryCode ?: '-') ?>
                                            </small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?= $log['is_blocked'] ? '<span class="badge badge-danger">Blocked</span>' : '<span class="badge badge-success">Allowed</span>' ?>
                                </td>
                                <td>
                                    <?php if (!empty($log['fingerprint']) && $log['fingerprint'] !== $currentAdminFingerprint): ?>
                                        <button type="button" class="btn btn-sm btn-danger"
                                            onclick="blockFingerprintFromLog('<?= e($log['fingerprint']) ?>')"
                                            title="Block Fingerprint">
                                            <i class="fas fa-ban"></i> Block
                                        </button>
                                    <?php elseif ($log['fingerprint'] === $currentAdminFingerprint): ?>
                                        <span class="badge badge-success">YOU</span>
                                    <?php else: ?>
                                        <span style="color:#64748b">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div style="display:flex;justify-content:center;align-items:center;gap:0.5rem;margin-top:2rem;padding:1rem">
                <?php
                $queryParams = $_GET;
                unset($queryParams['page']);
                $queryString = http_build_query($queryParams);
                ?>

                <?php if ($page > 1): ?>
                    <a href="?<?= $queryString ?>&page=<?= $page - 1 ?>" class="btn btn-sm btn-secondary">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                <?php endif; ?>

                <?php
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);

                for ($i = $startPage; $i <= $endPage; $i++):
                    ?>
                    <a href="?<?= $queryString ?>&page=<?= $i ?>"
                        class="btn btn-sm <?= $i == $page ? 'btn-primary' : 'btn-secondary' ?>" style="min-width:40px">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="?<?= $queryString ?>&page=<?= $page + 1 ?>" class="btn btn-sm btn-secondary">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
                <span style="color:#94a3b8;margin-left:1rem">
                    Page
                    <?= $page ?> of
                    <?= $totalPages ?> (
                    <?= number_format($totalLogs) ?> logs)
                </span>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <div style="padding:3rem;text-align:center;color:var(--text-secondary)">
            <i class="fas fa-clipboard-list" style="font-size:3rem;opacity:0.3;margin-bottom:1rem"></i>
            <p>No logs found</p>
        </div>
    <?php endif; ?>
</div>

<style>
    /* Custom Scrollbar for Security Logs Table */
    div[style*="overflow-x:auto"] {
        scrollbar-width: thin;
        scrollbar-color: rgba(139, 92, 246, 0.5) rgba(15, 23, 42, 0.3);
    }

    div[style*="overflow-x:auto"]::-webkit-scrollbar {
        height: 8px;
    }

    div[style*="overflow-x:auto"]::-webkit-scrollbar-track {
        background: rgba(15, 23, 42, 0.3);
        border-radius: 10px;
    }

    div[style*="overflow-x:auto"]::-webkit-scrollbar-thumb {
        background: rgba(139, 92, 246, 0.5);
        border-radius: 10px;
    }

    div[style*="overflow-x:auto"]::-webkit-scrollbar-thumb:hover {
        background: rgba(139, 92, 246, 0.8);
    }

    /* Fingerprint column width control */
    table td:nth-child(3) {
        max-width: 350px;
        min-width: 250px;
    }

    /* IP column */
    table td:nth-child(2) {
        min-width: 140px;
    }

    /* Country column */
    table td:nth-child(6) {
        min-width: 180px;
    }
</style>