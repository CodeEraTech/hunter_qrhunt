<?php
declare(strict_types=1); require dirname(__DIR__,2).'/includes/bootstrap.php'; require_permission('campaigns.manage');
if(!is_post()){http_response_code(405);exit('Method not allowed');} verify_csrf(); $spot=trim((string)input('spot_id')); $action=input('action');
if(!preg_match('/^[A-Z0-9_-]{3,64}$/',$spot)||!in_array($action,['pause','resume','archive'],true)){flash('error','Invalid campaign action.');redirect('/admin/campaigns/index.php');}
$pdo=db();$stmt=$pdo->prepare('SELECT is_active FROM spot_campaigns WHERE spot_id=?');$stmt->execute([$spot]);if(!$stmt->fetch()){flash('error','Campaign not found.');redirect('/admin/campaigns/index.php');}
$active=$action==='resume'?1:0;$pdo->prepare('UPDATE spot_campaigns SET is_active=? WHERE spot_id=?')->execute([$active,$spot]);audit_log((int)admin()['id'],'MASTER_SPOT_'.strtoupper($action),'spot_campaigns',$spot,null,['is_active'=>$active]);flash('success','Campaign status updated.');redirect('/admin/campaigns/index.php');
