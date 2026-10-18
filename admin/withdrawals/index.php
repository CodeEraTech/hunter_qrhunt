<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/includes/bootstrap.php';
require_admin();
require_permission('withdrawals.view');
require_once APP_ROOT.'/includes/finance_directory.php';
require_once APP_ROOT.'/includes/admin_wallet.php';

$tab = (string) input('tab','withdrawals');
if (!in_array($tab, ['withdrawals','money','settlements'], true)) { http_response_code(422); exit('Invalid tab'); }

$sql = finance_withdrawals_sql($tab);
$allowed = ['source'=>['CUSTOMER','VENDOR','MONEY','SETTLEMENT'],
    'status'=>['PENDING','UNDER_REVIEW','APPROVED','PROCESSING','PAID','REJECTED','CANCELLED','HELD','SETTLED','FAILED','DISPUTED','REVERSED']];
try { [$where,$params,$filters] = finance_filters($_GET,$allowed); } catch (InvalidArgumentException $e) { http_response_code(422); exit(e($e->getMessage())); }
$filters['tab'] = $tab;
$base = ' FROM ('.$sql.') directory'.$where;

// --- Export ---
if (input('export') !== '') {
    if (can('withdrawals.view')) require_permission('reports.export');
    if (can('vendor_settlements.view')) require_permission('vendor_settlements.export');
    require_once APP_ROOT.'/includes/finance_export.php';
    $s = db()->prepare('SELECT *'.$base.' ORDER BY created_at DESC, source, id DESC');
    $s->execute($params);
    
    // Enrich rows with bank details for 15-column export
    $exportRows = (static function() use ($s) {
        while ($r = $s->fetch()) {
            $upi = '';
            $bankName = '';
            $bankAccount = '';
            $ifsc = '';
            
            if ($r['source'] === 'CUSTOMER') {
                // Try to get UPI/bank from user_payment_methods table
                $uid = (int) str_replace('USR_','', $r['party_id']);
                $uStmt = db()->prepare("SELECT method_type, account_holder, account_masked, ifsc FROM user_payment_methods WHERE user_id = ? AND status='ACTIVE' ORDER BY id DESC LIMIT 1");
                $uStmt->execute([$uid]);
                $u = $uStmt->fetch();
                if ($u) {
                    if (($u['method_type'] ?? '') === 'UPI') {
                        $upi = $u['account_masked'] ?? '';
                    } else {
                        $bankAccount = $u['account_masked'] ?? '';
                        $ifsc = $u['ifsc'] ?? '';
                    }
                }
            } elseif ($r['source'] === 'VENDOR') {
                // Get vendor bank details
                $vStmt = db()->prepare("SELECT settlement_upi, bank_account_number, bank_ifsc, bank_branch, bank_account_holder FROM vendors WHERE vendor_code = ?");
                $vStmt->execute([$r['party_id']]);
                $v = $vStmt->fetch();
                if ($v) {
                    $upi = $v['settlement_upi'] ?? '';
                    $bankName = $v['bank_branch'] ?? '';
                    $bankAccount = !empty($v['bank_account_number']) ? payment_account_mask($v['bank_account_number']) : '';
                    $ifsc = $v['bank_ifsc'] ?? '';
                }
            }
            
            // Determine UPI vs Bank from destination/method
            $upiId = '';
            $bankAcc = $bankAccount;
            if (str_contains(strtolower($r['method'] ?? ''), 'upi') || str_contains($r['destination'] ?? '', '@')) {
                $upiId = $upi ?: ($r['destination'] ?? '');
                $bankAcc = '';
            } else {
                $bankAcc = $bankAccount ?: ($r['destination'] ?? '');
            }
            
            yield [
                $r['code'],
                $r['created_at'],
                $r['party_id'],
                $r['party'],
                $r['mobile'] ?? '',
                $r['balance'] ?? '',
                $r['amount'],
                $r['method'] ?? '',
                $upiId,
                $bankName . ($bankAcc ? ' / ' . $bankAcc : ''),
                $ifsc,
                $r['status'],
                $r['reference'] ?? '',
                $r['note'] ?? '',
                $r['settled_at'] ?? ''
            ];
        }
    })();
    
    finance_export('withdrawals',
        ['Request ID','Request Timestamp','User ID','Customer Name','Mobile Number','Total Wallet Real Cash','Withdraw Amount','Payout Mode','UPI ID / VPA','Bank Name & A/C Number','Bank IFSC Code','Status','Bank UTR / Txn Ref','Auto Reason / Audit Note','Settled Timestamp'],
        $exportRows,
        input('export') === '1' ? 'csv' : (string) input('export')
    );
}

// --- Dashboard Stats ---
$pdo = db();
$today = date('Y-m-d');
$monthStart = date('Y-m-01');
$yearStart = date('Y-01-01');

// Total Withdrawal Amount (all time)
$totalStmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM withdrawals UNION ALL SELECT COALESCE(SUM(amount),0) FROM vendor_payout_requests");
$totalStmt->execute();
$totalAmount = 0;
while ($val = $totalStmt->fetchColumn()) $totalAmount += (float) $val;

// Today Pending
$pendingStmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM withdrawals WHERE status IN ('PENDING','UNDER_REVIEW') AND DATE(requested_at)=? UNION ALL SELECT COALESCE(SUM(amount),0) FROM vendor_payout_requests WHERE status IN ('PENDING','UNDER_REVIEW') AND DATE(requested_at)=?");
$pendingStmt->execute([$today,$today]);
$todayPending = 0;
while ($val = $pendingStmt->fetchColumn()) $todayPending += (float) $val;

// Today Settled
$settledStmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM withdrawals WHERE status IN ('PAID','SETTLED') AND DATE(processed_at)=? UNION ALL SELECT COALESCE(SUM(amount),0) FROM vendor_payout_requests WHERE status IN ('PAID','SETTLED') AND DATE(paid_at)=?");
$settledStmt->execute([$today,$today]);
$todaySettled = 0;
while ($val = $settledStmt->fetchColumn()) $todaySettled += (float) $val;

// Today Rejected/Fraud
$rejectedStmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM withdrawals WHERE status IN ('REJECTED','CANCELLED') AND DATE(processed_at)=? UNION ALL SELECT COALESCE(SUM(amount),0) FROM vendor_payout_requests WHERE status='REJECTED' AND DATE(paid_at)=?");
$rejectedStmt->execute([$today,$today]);
$todayRejected = 0;
while ($val = $rejectedStmt->fetchColumn()) $todayRejected += (float) $val;

// This Month Settled
$monthStmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM withdrawals WHERE status IN ('PAID','SETTLED') AND DATE(processed_at)>=? UNION ALL SELECT COALESCE(SUM(amount),0) FROM vendor_payout_requests WHERE status IN ('PAID','SETTLED') AND DATE(paid_at)>=?");
$monthStmt->execute([$monthStart,$monthStart]);
$monthSettled = 0;
while ($val = $monthStmt->fetchColumn()) $monthSettled += (float) $val;

// This Year Settled
$yearStmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM withdrawals WHERE status IN ('PAID','SETTLED') AND DATE(processed_at)>=? UNION ALL SELECT COALESCE(SUM(amount),0) FROM vendor_payout_requests WHERE status IN ('PAID','SETTLED') AND DATE(paid_at)>=?");
$yearStmt->execute([$yearStart,$yearStart]);
$yearSettled = 0;
while ($val = $yearStmt->fetchColumn()) $yearSettled += (float) $val;

// Escrow Vault Reserves (vendor escrow balances)
$escrowStmt = $pdo->prepare("SELECT COALESCE(SUM(escrow_balance),0) FROM vendors WHERE status='ACTIVE'");
$escrowStmt->execute();
$escrowReserves = (float) $escrowStmt->fetchColumn();

// Total Users Paid (unique users/vendors paid all time)
$usersPaidStmt = $pdo->prepare("SELECT COALESCE(COUNT(DISTINCT user_id),0) FROM withdrawals WHERE status IN ('PAID','SETTLED') UNION ALL SELECT COALESCE(COUNT(DISTINCT vendor_id),0) FROM vendor_payout_requests WHERE status IN ('PAID','SETTLED')");
$usersPaidStmt->execute();
$totalUsersPaid = 0;
while ($val = $usersPaidStmt->fetchColumn()) $totalUsersPaid += (int) $val;

// --- Pagination ---
$countStmt = $pdo->prepare('SELECT COUNT(*) total'.$base);
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$perPage = 20;
$page = max(1, min(max(1, (int) ceil($total/$perPage)), page_int()));
$offset = ($page-1)*$perPage;

$listStmt = $pdo->prepare('SELECT *'.$base.' ORDER BY created_at DESC, source, id DESC LIMIT ? OFFSET ?');
$listStmt->execute(array_merge($params, [$perPage, $offset]));
$rows = $listStmt->fetchAll();

$pageTitle = 'Withdrawals';
$pageSubtitle = 'Customer and vendor payouts, money requests and settlement history';
// Handle withdrawal status transitions from modal actions
if (is_post() && input('action') === 'transition') {
    verify_csrf();
    $id = filter_var(input('id'), FILTER_VALIDATE_INT);
    $source = (string) input('source');
    $status = strtoupper((string) input('status'));
    $reason = trim((string) input('reason'));
    $reference = trim((string) input('reference'));
    $adminId = (int) admin()['id'];

    if (!$id || !in_array($source, ['CUSTOMER','VENDOR'], true)) {
        flash('error', 'Invalid request');
    } elseif ($status === 'REJECTED' && $reason === '') {
        flash('error', 'Rejection reason required');
    } else {
        try {
            if ($source === 'CUSTOMER') {
                require_permission($status === 'REJECTED' ? 'withdrawals.reject' : 'withdrawals.approve');
                transition_withdrawal($id, $status, $reason, $adminId, $reference);
                flash('success', 'Customer withdrawal updated to ' . $status);
            } else {
                require_permission('vendor_settlements.approve');
                require_once APP_ROOT.'/includes/finance_withdrawals.php';
                finance_vendor_withdrawal($id, $status, $reason ?: ($status === 'PAID' ? 'Settled payout' : 'Status updated: '.$status), $reference, $adminId);
                flash('success', 'Vendor payout request updated to ' . $status);
            }
        } catch (Throwable $e) {
            flash('error', $e instanceof PDOException ? 'Could not update withdrawal.' : $e->getMessage());
        }
    }
    redirect('/admin/withdrawals/index.php?tab='.urlencode($tab).'&'.http_build_query($filters));
}
require APP_ROOT.'/includes/header.php';
?>
<?php foreach (take_flashes() as $f): ?>
<div class="alert <?=e($f['type'])?>"><?=e($f['message'])?></div>
<?php endforeach; ?>

<!-- Dashboard Summary Cards -->
<div class="grid stats" style="grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:24px">
    <div class="card"><span class="label">Total Withdrawal Amount</span><span class="value"><?=money($totalAmount)?></span></div>
    <div class="card"><span class="label">Today Pending Requests</span><span class="value" style="color:#825115"><?=money($todayPending)?></span></div>
    <div class="card"><span class="label">Today Settled Amount</span><span class="value" style="color:#256845"><?=money($todaySettled)?></span></div>
    <div class="card"><span class="label">Today Rejected / Fraud</span><span class="value" style="color:#a13442"><?=money($todayRejected)?></span></div>
    <div class="card"><span class="label">This Month Settled</span><span class="value"><?=money($monthSettled)?></span></div>
    <div class="card"><span class="label">This Year Settled</span><span class="value"><?=money($yearSettled)?></span></div>
    <div class="card"><span class="label">Escrow Vault Reserves</span><span class="value" style="color:#2781f6"><?=money($escrowReserves)?></span></div>
    <div class="card"><span class="label">Total Users / Vendors Paid</span><span class="value"><?=e($totalUsersPaid)?></span></div>
</div>

<!-- Tab Navigation -->
<nav class="actions" aria-label="Withdrawal sections" style="margin-bottom:16px">
    <a class="btn <?= $tab==='withdrawals'?'primary':'secondary' ?>" href="?tab=withdrawals">Customer & Vendor Withdrawals</a>
    <?php if (can('vendor_settlements.view')): ?>
    <a class="btn <?= $tab==='money'?'primary':'secondary' ?>" href="?tab=money">Money Requests</a>
    <a class="btn <?= $tab==='settlements'?'primary':'secondary' ?>" href="?tab=settlements">Settlement History</a>
    <?php endif; ?>
</nav>

<!-- Withdrawal Requests & Real-Time Settlement Ledger -->
<section class="panel">
    <div class="panel-head">
        <h2><?=e(ucfirst($tab))?> · Requests & Ledger</h2>
        <?php if (can('reports.export') || can('vendor_settlements.export')): ?>
        <a class="btn secondary" href="?<?=e(http_build_query($filters+['export'=>'csv']))?>">Export CSV</a>
        <a class="btn secondary" href="?<?=e(http_build_query($filters+['export'=>'xlsx']))?>">Export Excel</a>
        <?php endif; ?>
    </div>

    <form class="filters" method="get" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px">
        <input type="hidden" name="tab" value="<?=e($tab)?>">
        <input name="q" value="<?=e($filters['q'])?>" placeholder="Request ID, User ID, name, mobile, UTR" aria-label="Search" style="min-width:260px">
        <select name="source" aria-label="Source">
            <option value="">All Sources</option>
            <?php foreach ($allowed['source'] as $v): ?><option value="<?=$v?>" <?=selected($filters['source'],$v)?>><?=$v?></option><?php endforeach; ?>
        </select>
        <select name="status" aria-label="Status">
            <option value="">All Statuses</option>
            <?php foreach ($allowed['status'] as $v): ?><option value="<?=$v?>" <?=selected($filters['status'],$v)?>><?=$v?></option><?php endforeach; ?>
        </select>
        <input type="date" name="from" value="<?=e($filters['from'])?>" aria-label="From date">
        <input type="date" name="to" value="<?=e($filters['to'])?>" aria-label="To date">
        <button class="btn" type="submit">Filter</button>
        <?php if ($filters['q']||$filters['source']||$filters['status']||$filters['from']||$filters['to']): ?>
        <a class="btn secondary" href="?tab=<?=e($tab)?>">Clear</a>
        <?php endif; ?>
    </form>

    <div class="table-wrap"><table>
        <thead>
            <tr>
                <th>Request ID</th><th>Date & Time</th><th>Customer / Vendor ID</th><th>Mobile / KYC</th>
                <th>Requested Amount</th><th>Payout Destination</th><th>Risk Check</th><th>Status</th><th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr data-id="<?=$r['id']?>" data-source="<?=$r['source']?>">
                <td><strong><?=e($r['code'])?></strong></td>
                <td><?=e($r['created_at'])?></td>
                <td><?=e($r['party_id'])?><br><small><?=e($r['party'])?></small></td>
                <td><?=e($r['mobile']??'—')?><?= ($r['source']==='CUSTOMER') ? '<br><span class="badge '.(!empty($r['is_verified'])?'success':'warning').'">'.(!empty($r['is_verified'])?'Verified':'Pending KYC').'</span>' : '' ?></td>
                <td><?=money($r['amount'])?></td>
                <td>
                    <?=e($r['method'])?><br>
                    <small>
                        <?php
                        $dest = $r['destination'] ?? '—';
                        if (str_starts_with($dest,'XXXX')) $dest = '•••• '.substr($dest,-4);
                        echo e($dest);
                        ?>
                    </small>
                </td>
                <td>
                    <span class="badge <?=strtolower(e($r['risk']??'NOT_ASSESSED'))?>"><?=e($r['risk']??'NOT_ASSESSED')?></span>
                </td>
                <td>
                    <span class="badge <?=strtolower(e($r['status']))?>"><?=e($r['status'])?></span>
                </td>
                <td>
                    <div style="display:flex;gap:6px;flex-wrap:wrap">
                        <button class="btn small secondary view-detail" data-id="<?=$r['id']?>" data-source="<?=$r['source']?>">View</button>
                        <?php if (in_array($r['status'], ['PENDING','UNDER_REVIEW']) && (can('withdrawals.approve') || can('withdrawals.reject'))): ?>
                        <button class="btn small success approve-btn" data-id="<?=$r['id']?>" data-source="<?=$r['source']?>">Approve</button>
                        <button class="btn small danger reject-btn" data-id="<?=$r['id']?>" data-source="<?=$r['source']?>">Reject</button>
                        <?php endif; ?>
                        <?php if ($r['status']==='APPROVED' && (can('withdrawals.approve') || can('withdrawals.approve'))): ?>
                        <button class="btn small success process-btn" data-id="<?=$r['id']?>" data-source="<?=$r['source']?>">Mark Processing</button>
                        <?php endif; ?>
                        <?php if ($r['status']==='PROCESSING' && (can('withdrawals.approve') || can('withdrawals.approve'))): ?>
                        <button class="btn small primary settle-btn" data-id="<?=$r['id']?>" data-source="<?=$r['source']?>">Settle / UTR</button>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?>
            <tr><td colspan="9" class="empty">No matching records.</td></tr>
        <?php endif; ?>
        </tbody>
    </table></div>

    <?php
    require_once APP_ROOT.'/includes/finance_directory.php';
    finance_page_links($page,$total,$filters);
    ?>
</section>

<!-- Customer View Detail Modal -->
<dialog id="detail-modal" class="detail-modal" style="max-width:900px;width:calc(100%-32px);padding:0;border:none;border-radius:16px;box-shadow:0 24px 70px #0b1b2e55">
    <form method="dialog" style="width:100%;height:100%;display:flex;flex-direction:column">
        <header class="modal-head" style="display:flex;align-items:center;justify-content:space-between;padding:16px 24px;border-bottom:1px solid var(--line);background:#fff;border-radius:16px 16px 0 0">
            <div>
                <h2 id="modal-title" style="margin:0">Request Details</h2>
                <small id="modal-subtitle" style="color:var(--muted)"></small>
            </div>
            <button class="modal-close" value="close" style="background:none;border:none;font-size:24px;cursor:pointer;color:var(--muted)">×</button>
        </header>
        <div class="modal-body" style="flex:1;overflow:auto;padding:24px">
            <div id="modal-content" class="grid detail-grid" style="grid-template-columns:1fr 1fr;gap:24px">
                <!-- Loading placeholder -->
                <div class="panel" style="grid-column:1/-1;text-align:center;padding:48px;color:var(--muted)">Loading…</div>
            </div>
        </div>
    </form>
</dialog>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('detail-modal');
    const modalContent = document.getElementById('modal-content');
    const modalTitle = document.getElementById('modal-title');
    const modalSubtitle = document.getElementById('modal-subtitle');

    // View Detail
    document.querySelectorAll('.view-detail').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const source = this.dataset.source;
            loadDetail(id, source);
        });
    });

    // Action buttons
    function actionHandler(className, action, needsReference=false) {
        document.querySelectorAll(className).forEach(btn => {
            btn.addEventListener('click', async function() {
                const id = this.dataset.id;
                const source = this.dataset.source;
                let reason = '';
                let reference = '';
                if (action === 'REJECTED') {
                    reason = prompt('Rejection reason (required):');
                    if (!reason) return;
                }
                if (needsReference) {
                    reference = prompt('Bank UTR / Reference (required):');
                    if (!reference) return;
                }
                if (!confirm(`Confirm ${action} for request ${id}? Financial actions cannot be silently undone.`)) return;

                const form = new FormData();
                form.append('action', 'transition');
                form.append('id', id);
                form.append('source', source);
                form.append('status', action);
                form.append('reason', reason);
                if (reference) form.append('reference', reference);
                form.append('csrf_token', document.querySelector('input[name="csrf_token"]')?.value || '');

                try {
                    const res = await fetch(window.location.href, {method:'POST', body:form, credentials:'same-origin'});
                    if (res.ok || res.redirected) {
                        location.reload();
                    } else {
                        alert('Action failed');
                    }
                } catch (e) {
                    alert('Network error');
                }
            });
        });
    }

    actionHandler('.approve-btn', 'APPROVED');
    actionHandler('.reject-btn', 'REJECTED');
    actionHandler('.process-btn', 'PROCESSING');
    actionHandler('.settle-btn', 'PAID', true);

    async function loadDetail(id, source) {
        modalTitle.textContent = `${source} Withdrawal · ${id}`;
        modalSubtitle.textContent = 'Loading…';
        modalContent.innerHTML = '<div class="panel" style="grid-column:1/-1;text-align:center;padding:48px;color:var(--muted)">Loading…</div>';
        modal.showModal();

        try {
            const res = await fetch(`/admin/withdrawals/detail.php?id=${id}&source=${source}`, {credentials:'same-origin'});
            if (!res.ok) throw new Error('Failed to load');
            const html = await res.text();
            modalContent.innerHTML = html;
            // Re-bind settle/approve/reject in modal
            modalContent.querySelectorAll('.settle-btn,.approve-btn,.reject-btn,.process-btn').forEach(btn => {
                btn.addEventListener('click', async function() {
                    const mid = this.dataset.id;
                    const msource = this.dataset.source;
                    const action = this.dataset.action || this.textContent.trim().toUpperCase();
                    // Reuse action logic
                    // For simplicity, redirect to view.php for actions
                    window.location.href = (msource === 'VENDOR' || msource === 'MONEY') ? `/admin/withdrawals/vendor.php?source=${msource}&id=${mid}` : `/admin/withdrawals/view.php?id=${mid}`;
                });
            });
        } catch (e) {
            modalContent.innerHTML = '<div class="panel" style="grid-column:1/-1;text-align:center;padding:48px;color:var(--red)">Failed to load details.</div>';
        }
    }

    // Close modal
    modal.querySelector('.modal-close').addEventListener('click', () => modal.close());
    modal.addEventListener('click', e => { if (e.target === modal) modal.close(); });
});
</script>

<?php require APP_ROOT.'/includes/footer.php'; ?>