<?php
declare(strict_types=1);
require_once __DIR__.'/admin_wallet.php';

function finance_transactions_sql(): string
{
    $parts=[];
    if(can('wallet.view')) $parts[]="SELECT 'WALLET' source,t.id,t.transaction_id code,CONCAT('USR_',u.id) party_id,u.name party,u.mobile,t.bucket pocket,t.type action,t.amount,t.previous_balance,t.new_balance,t.status,t.description note,t.reference,t.created_at FROM wallet_transactions t JOIN users u ON u.id=t.user_id";
    if(can('vendor_settlements.view')) $parts[]="SELECT 'SETTLEMENT',s.id,s.settlement_id,v.vendor_code,v.shop_name,v.mobile,'VENDOR','SETTLEMENT',s.merchant_amount,NULL,NULL,s.status,CONCAT('Gross: ',s.bill_amount,'; commission: ',s.platform_fee),s.checkout_id,s.created_at FROM merchant_settlements s JOIN vendors v ON v.id=s.merchant_id";
    if(finance_ready()) {
        if(can('wallet.master.view')) $parts[]="SELECT 'MASTER',m.id,m.transaction_id,COALESCE(CONCAT(m.target,'_',m.recipient_id),'MASTER'),COALESCE(v.shop_name,u.name,'Master wallet'),NULL,m.target,m.operation,m.amount,m.previous_balance,m.new_balance,'SUCCESS',m.reason,m.reference,m.created_at FROM finance_master_entries m LEFT JOIN vendors v ON m.target='VENDOR' AND v.id=m.recipient_id LEFT JOIN users u ON m.target IN ('REAL','BONUS') AND u.id=m.recipient_id";
        if(can('vendor_settlements.view')) $parts[]="SELECT 'VENDOR_DEBIT',d.request_id,CONCAT('VWD-',d.request_id),v.vendor_code,v.shop_name,v.mobile,'VENDOR','WITHDRAWAL',d.amount,d.previous_balance,d.new_balance,'SUCCESS','Vendor withdrawal debit',CONCAT('VWD-',d.request_id),d.created_at FROM finance_vendor_debits d JOIN vendors v ON v.id=d.vendor_id";
    }
    if(!$parts) { http_response_code(403);exit('Forbidden'); }
    return implode(' UNION ALL ',$parts);
}
function finance_withdrawals_sql(string $tab): string
{
    $reference=finance_ready();$parts=[];
    if($tab==='money') {
        require_permission('vendor_settlements.view');
        return "SELECT 'MONEY' source,r.id,r.request_code code,v.vendor_code party_id,v.shop_name party,v.mobile,r.amount,r.status,r.destination_type method,CONCAT('XXXX ',RIGHT(COALESCE(r.destination_reference,''),4)) destination,v.escrow_balance balance,'NOT_ASSESSED' risk,r.requested_at created_at,r.processed_at settled_at,NULL reference,r.admin_note note FROM vendor_money_requests r JOIN vendors v ON v.id=r.vendor_id";
    }
    if($tab==='settlements') {
        require_permission('vendor_settlements.view');
        return "SELECT 'SETTLEMENT' source,s.id,s.settlement_id code,v.vendor_code party_id,v.shop_name party,v.mobile,s.merchant_amount amount,s.status,'WALLET' method,s.checkout_id destination,v.escrow_balance balance,'NOT_ASSESSED' risk,s.created_at,s.settled_at,s.checkout_id reference,CONCAT('Gross ',s.bill_amount,'; platform commission ',s.platform_fee) note FROM merchant_settlements s JOIN vendors v ON v.id=s.merchant_id";
    }
    if(can('withdrawals.view')) $parts[]="SELECT 'CUSTOMER' source,w.id,w.withdrawal_id code,CONCAT('USR_',u.id) party_id,u.name party,u.mobile,w.amount,w.status,w.payment_method method,w.payment_account_masked destination,wa.balance,w.risk_level risk,w.requested_at created_at,w.processed_at settled_at,".($reference?'p.reference':'NULL reference').",w.rejection_reason note FROM withdrawals w JOIN users u ON u.id=w.user_id JOIN wallets wa ON wa.id=w.wallet_id ".($reference?"LEFT JOIN finance_payout_records p ON p.kind='CUSTOMER' AND p.request_id=w.id":'');
    if(can('vendor_settlements.view')) $parts[]="SELECT 'VENDOR',r.id,CONCAT('VWD-',r.id),v.vendor_code,v.shop_name,v.mobile,r.amount,r.status,'BANK',CONCAT('XXXX XXXX ',RIGHT(COALESCE(v.bank_account_number,''),4)),v.escrow_balance,'NOT_ASSESSED',r.requested_at,r.paid_at,".($reference?'p.reference':'NULL').",NULL FROM vendor_payout_requests r JOIN vendors v ON v.id=r.vendor_id ".($reference?"LEFT JOIN finance_payout_records p ON p.kind='VENDOR' AND p.request_id=r.id":'');
    if(!$parts) { http_response_code(403);exit('Forbidden'); }
    return implode(' UNION ALL ',$parts);
}
function finance_filters(array $input, array $allowed): array
{
    $where=[];$params=[];$values=[];
    foreach(['q','status','source','from','to'] as $key) $values[$key]=trim((string)($input[$key]??''));
    if($values['q']!=='') {$where[]='(code LIKE ? OR party_id LIKE ? OR party LIKE ? OR mobile LIKE ? OR reference LIKE ?)';array_push($params,...array_fill(0,5,'%'.$values['q'].'%'));}
    foreach(['source','status'] as $key) if($values[$key]!=='') {if(!in_array($values[$key],$allowed[$key]??[],true)) throw new InvalidArgumentException('Invalid '.$key.' filter.');$where[]=$key.'=?';$params[]=$values[$key];}
    foreach(['from','to'] as $key) if($values[$key]!=='') {if(!valid_date($values[$key])) throw new InvalidArgumentException('Use a valid date.');$where[]='created_at'.($key==='from'?'>=?':'<DATE_ADD(?,INTERVAL 1 DAY)');$params[]=$values[$key];}
    if($values['from']!=='' && $values['to']!=='' && $values['from']>$values['to']) throw new InvalidArgumentException('The end date must follow the start date.');
    return [$where?' WHERE '.implode(' AND ',$where):'',$params,$values];
}
function finance_page_links(int $page,int $total,array $filters): void
{
    ?><div class="pagination"><span><?=e($total)?> records · Page <?=e($page)?></span><?php foreach(['Previous'=>$page-1,'Next'=>$page+1] as $label=>$next) if($next>=1 && ($next<=$page || $page*20<$total)): ?><a href="?<?=e(http_build_query(array_merge($filters,['page'=>$next])))?>"><?=e($label)?></a><?php endif ?></div><?php
}
