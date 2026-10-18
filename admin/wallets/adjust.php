<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/includes/bootstrap.php';
require_admin();
if (!can('wallet.credit') && !can('wallet.debit')) { http_response_code(403); exit('Forbidden'); }
require_once APP_ROOT.'/includes/wallet_console.php';

$search = trim((string) input('customer', input('user_id', '')));
$customer = wallet_console_customer($search);

if (is_post()) {
    verify_csrf();
    try {
        if (input('action') === 'cancel') {
            unset($_SESSION['wallet_preview']);
        } elseif (input('action') === 'preview') {
            if (!$customer) throw new RuntimeException('Fetch a customer first.');
            $bucket = (string) input('bucket');
            $type = (string) input('type');
            $amount = trim((string) input('amount'));
            $bonusUsagePct = trim((string) input('bonus_usage_pct'));
            $reason = trim((string) input('reason'));
            $reference = trim((string) input('reference'));

            if (!in_array($bucket, ['REAL','BONUS'], true) || !in_array($type, ['CREDIT','DEBIT'], true)
                || !preg_match('/^\d{1,12}(\.\d{1,2})?$/',$amount) || decimal_compare($amount,'0.00')<=0
                || $reason==='' || strlen($reason)>500 || strlen($reference)>120) {
                throw new RuntimeException('Enter a valid amount, wallet and mandatory reason.');
            }

            if ($bucket === 'BONUS' && $bonusUsagePct !== '') {
                if (!preg_match('/^\d{1,2}(\.\d{1,2})?$/',$bonusUsagePct) || (float)$bonusUsagePct < 0 || (float)$bonusUsagePct > 100) {
                    throw new RuntimeException('Bonus usage percentage must be 0-100.');
                }
            }

            require_permission($type==='CREDIT'?'wallet.credit':'wallet.debit');
            if ($bucket==='BONUS') require_permission('wallet.bonus');

            if ($customer['status']!=='ACTIVE' || $customer['wallet_status']!=='ACTIVE') {
                throw new RuntimeException('Customer and wallet must be active.');
            }

            $before = $bucket==='REAL' ? $customer['balance'] : $customer['bonus_balance'];
            $after = $type==='CREDIT' ? decimal_add($before,$amount) : decimal_subtract($before,$amount);
            if (decimal_compare($after,'0.00')<0) throw new RuntimeException('Insufficient wallet balance.');

            // Calculate split for bonus usage % (for REAL wallet DEBIT operations)
            $splitInfo = null;
            if ($bucket==='REAL' && $type==='DEBIT' && $bonusUsagePct!=='' && (float)$bonusUsagePct > 0) {
                $bonusPct = (float)$bonusUsagePct / 100;
                $fromBonus = decimal_mul($amount, (string)$bonusPct);
                $fromReal = decimal_subtract($amount, $fromBonus);
                $availableBonus = $customer['bonus_balance'];
                if (decimal_compare($fromBonus, $availableBonus) > 0) {
                    throw new RuntimeException('Insufficient bonus balance for the configured bonus usage percentage.');
                }
                $splitInfo = ['from_real'=>$fromReal, 'from_bonus'=>$fromBonus, 'bonus_pct'=>$bonusPct];
            }

            $_SESSION['wallet_preview'] = [
                'user_id' => (int) $customer['id'],
                'name' => $customer['name'],
                'bucket' => $bucket,
                'type' => $type,
                'amount' => $amount,
                'bonus_usage_pct' => $bonusUsagePct,
                'reason' => $reason,
                'reference' => $reference,
                'before' => $before,
                'after' => $after,
                'split' => $splitInfo,
                'key' => idempotency_key('console'),
                'admin_id' => (int) admin()['id'],
                'expires' => time() + 300
            ];
        } elseif (input('action') === 'confirm') {
            $p = $_SESSION['wallet_preview'] ?? null;
            if (!$p || $p['expires'] < time() || $p['admin_id'] !== (int) admin()['id']) {
                throw new RuntimeException('Preview expired. Review the adjustment again.');
            }
            $r = wallet_console_adjust($p['user_id'], $p['bucket'], $p['type'], $p['amount'], $p['reason'], $p['reference'], $p['key'], $p['admin_id'], $p['before']);
            unset($_SESSION['wallet_preview']);
            flash('success', 'Adjustment recorded: '.$r['transaction_id']);
            redirect('/admin/wallets/adjust.php?customer=USR_'.$p['user_id']);
        } else {
            throw new RuntimeException('Invalid action.');
        }
    } catch (Throwable $e) {
        flash('error', $e instanceof PDOException ? 'Could not save the adjustment. No partial balance update was committed.' : $e->getMessage());
    }
    redirect('/admin/wallets/adjust.php?customer='.urlencode($search));
}

$pageTitle = 'Wallet Adjustments';
$pageSubtitle = 'Search customer, preview balances, and make audited real cash or bonus adjustments with live balance preview';
require APP_ROOT.'/includes/header.php';
?>
<?php foreach (take_flashes() as $f): ?>
<div class="alert <?=e($f['type'])?>"><?=e($f['message'])?></div>
<?php endforeach; ?>

<!-- Search Customer -->
<section class="panel">
    <div class="panel-head">
        <h2>Wallet Adjustments Console</h2>
        <a class="btn secondary" href="<?=e(app_url('/admin/wallets/admin.php'))?>">Admin Wallet</a>
    </div>
    <form class="filters" method="get" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px">
        <input name="customer" value="<?=e($search)?>" placeholder="User ID (USR_123) or mobile number" aria-label="Customer ID or mobile" required style="min-width:300px">
        <button class="btn" type="submit">Fetch Customer</button>
    </form>
    <?php if ($search!=='' && !$customer): ?>
    <div class="empty">No matching customer wallet found.</div>
    <?php endif; ?>
</section>

<?php if ($customer): ?>
<!-- Customer Info & Current Balances -->
<div class="grid detail-grid" style="margin-bottom:24px">
    <section class="panel">
        <h2><?=e($customer['name'])?> · USR_<?=e($customer['id'])?></h2>
        <p><?=e($customer['mobile'])?> · <?=e($customer['email']??'')?></p>
        <div class="finance-pools" style="display:flex;gap:16px;margin-top:12px">
            <article class="finance-pool pool-real">
                <h2>Real Cash</h2>
                <strong><?=money($customer['balance'])?></strong>
            </article>
            <article class="finance-pool pool-bonus">
                <h2>Bonus Balance</h2>
                <strong><?=money($customer['bonus_balance'])?></strong>
            </article>
        </div>
        <div class="kv"><span>Account Verification</span><strong><?=$customer['is_verified']?'Verified':'Pending'?></strong></div>
        <div class="kv"><span>Wallet / Account Status</span><strong><?=e($customer['wallet_status'].' / '.$customer['status'])?></strong></div>
        <?php if (can('wallet.view')): ?>
        <a class="btn secondary" href="<?=e(app_url('/admin/wallets/customer.php?id='.$customer['id']))?>">View Customer Passbook</a>
        <?php endif; ?>
    </section>

    <!-- Prepare Adjustment Form -->
    <section class="panel">
        <h2>Prepare Adjustment</h2>
        <form method="post" class="form-grid" style="grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px">
            <?=csrf_field()?>
            <input type="hidden" name="action" value="preview">
            <input type="hidden" name="customer" value="USR_<?=e($customer['id'])?>">

            <div class="field">
                <label>Target Wallet</label>
                <select name="bucket" required>
                    <option value="REAL">Real Cash</option>
                    <?php if (can('wallet.bonus')): ?><option value="BONUS">Bonus</option><?php endif; ?>
                </select>
            </div>

            <div class="field">
                <label>Credit / Debit</label>
                <select name="type" required>
                    <?php if (can('wallet.credit')): ?><option value="CREDIT">Credit</option><?php endif; ?>
                    <?php if (can('wallet.debit')): ?><option value="DEBIT">Debit</option><?php endif; ?>
                </select>
            </div>

            <div class="field">
                <label>Amount (₹)</label>
                <input name="amount" type="number" step="0.01" min="0.01" inputmode="decimal" required placeholder="e.g., 100.00">
            </div>

            <!-- Bonus Usage % - Only for REAL wallet DEBIT -->
            <div class="field" id="bonus-pct-field" style="display:none">
                <label>Bonus Usage % (optional)</label>
                <input name="bonus_usage_pct" type="number" step="0.01" min="0" max="100" placeholder="0-100 (e.g., 30 for 30%)" style="width:100%">
                <small style="color:var(--muted)">Percentage of amount to deduct from Bonus balance instead of Real Cash. Only for Real Cash Debit.</small>
            </div>

            <div class="field">
                <label>Reference (auto-generated if blank)</label>
                <input name="reference" maxlength="120" placeholder="Optional reference">
            </div>

            <div class="field full">
                <label>Mandatory Adjustment Reason</label>
                <textarea name="reason" maxlength="500" required placeholder="Enter reason for this adjustment (max 500 chars)"></textarea>
            </div>

            <div class="field full" style="display:flex;gap:12px">
                <button class="btn" type="submit">Preview Adjustment</button>
            </div>
        </form>
    </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const bucketSelect = document.querySelector('select[name="bucket"]');
    const typeSelect = document.querySelector('select[name="type"]');
    const bonusField = document.getElementById('bonus-pct-field');

    function toggleBonusField() {
        if (bucketSelect && typeSelect && bonusField) {
            const show = bucketSelect.value === 'REAL' && typeSelect.value === 'DEBIT';
            bonusField.style.display = show ? 'block' : 'none';
        }
    }
    bucketSelect?.addEventListener('change', toggleBonusField);
    typeSelect?.addEventListener('change', toggleBonusField);
    toggleBonusField();
});
</script>

<?php endif; ?>

<?php
// Live Balance Preview
$p = $_SESSION['wallet_preview'] ?? null;
if ($p && $p['admin_id'] === (int) admin()['id'] && $p['expires'] >= time()):
?>
<!-- Live Balance Preview & Confirmation -->
<section class="panel finance-review" style="margin-top:24px;border:2px solid var(--brand-primary);border-radius:16px">
    <h2>Live Balance Preview · <?=e($p['name'])?> (USR_<?=e($p['user_id'])?>)</h2>
    <p style="margin-bottom:16px"><strong><?=e($p['bucket'].' · '.$p['type'])?> <?=money($p['amount'])?></strong></p>

    <?php if (!empty($p['split'])): ?>
    <div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:12px;padding:16px;margin-bottom:16px">
        <h3 style="margin:0 0 12px;color:#0369a1">Split Preview (Real Cash Debit with Bonus Usage)</h3>
        <div class="grid" style="grid-template-columns:1fr 1fr;gap:16px">
            <div class="kv"><span>From Real Cash</span><strong><?=money($p['split']['from_real'])?></strong></div>
            <div class="kv"><span>From Bonus (<?=e(number_format($p['split']['bonus_pct']*100,2))?>%)</span><strong><?=money($p['split']['from_bonus'])?></strong></div>
        </div>
        <p class="help" style="margin:8px 0 0">Available Bonus: <?=money($customer['bonus_balance']??'0.00')?></p>
    </div>
    <?php endif; ?>

    <div class="grid stats" style="grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
        <div class="card" style="background:#fef2f2;border:1px solid #fecaca">
            <span class="label">Balance Before</span>
            <span class="value" style="color:#dc2626"><?=money($p['before'])?></span>
        </div>
        <div class="card" style="background:#f0fdf4;border:1px solid #bbf7d0">
            <span class="label">Balance After</span>
            <span class="value" style="color:#16a34a"><?=money($p['after'])?></span>
        </div>
    </div>

    <p><?=e($p['reason'])?></p>
    <?php if ($p['reference']): ?><p class="help">Reference: <?=e($p['reference'])?></p><?php endif; ?>
    <p class="help">The other wallet stays unchanged. Confirmation expires in 5 minutes; a changed balance requires a fresh preview.</p>

    <div class="actions" style="display:flex;gap:12px;margin-top:16px">
        <form method="post">
            <?=csrf_field()?>
            <input type="hidden" name="action" value="confirm">
            <input type="hidden" name="customer" value="USR_<?=e($p['user_id'])?>">
            <button class="btn <?=$p['type']==='DEBIT'?'danger':'success'?>">Confirm adjustment (<?=e(strtolower($p['type']))?>)</button>
        </form>
        <form method="post">
            <?=csrf_field()?>
            <input type="hidden" name="action" value="cancel">
            <button class="btn secondary">Cancel</button>
        </form>
    </div>
</section>
<?php endif; ?>

<?php require APP_ROOT.'/includes/footer.php'; ?>