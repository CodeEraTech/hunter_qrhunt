<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/includes/bootstrap.php';require_admin();require_once APP_ROOT.'/includes/finance_directory.php';
$sql=finance_transactions_sql();$id=filter_var(input('id'),FILTER_VALIDATE_INT);$source=(string)input('source');
$s=db()->prepare('SELECT * FROM ('.$sql.') directory WHERE source=? AND id=?');$s->execute([$source,$id]);$row=$s->fetch();if(!$row){http_response_code(404);exit('Transaction not found');}
$pageTitle=$row['code'];$pageSubtitle='Transaction details';require APP_ROOT.'/includes/header.php'; ?>
<section class="panel"><div class="panel-head"><h2><?=e($row['party'])?> · <?=e($row['party_id'])?></h2><a class="btn secondary" href="<?=e(app_url('/admin/wallets/index.php'))?>">Transactions</a></div>
<?php foreach(['source'=>'Ledger','created_at'=>'Date','pocket'=>'Pocket','action'=>'Operation','status'=>'Status','reference'=>'Reference','note'=>'Audit note'] as $key=>$label):?><div class="kv"><span><?=$label?></span><strong><?=e($row[$key]??'—')?></strong></div><?php endforeach ?>
<?php foreach(['amount'=>'Amount','previous_balance'=>'Previous balance','new_balance'=>'New balance'] as $key=>$label):?><div class="kv"><span><?=$label?></span><strong><?=$row[$key]!==null?money($row[$key]):'Not recorded'?></strong></div><?php endforeach ?>
<?php if($source==='WALLET' && can('wallet.view')):?><a class="btn secondary" href="<?=e(app_url('/admin/wallets/view.php?id='.$id))?>">Wallet audit &amp; reversal details</a><?php endif ?>
</section><?php require APP_ROOT.'/includes/footer.php'; ?>
