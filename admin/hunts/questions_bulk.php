<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/includes/bootstrap.php';
require_permission('hunts.manage');
$pdo=db();

if (isset($_GET['download_template'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="hunter-question-template.csv"');
    $out=fopen('php://output','w');
    fputcsv($out,['question_mode','riddle_text','instruction_text','primary_answer','synonyms','yt_video_id','channel_url','answer_time_seconds','status']);
    fputcsv($out,['SINGLE','Mujhe pehno toh waqt rukta nahi, main kaun?','Submit one answer within the time limit.','Watch','ghadi, clock, samay','dQw4w9WgXcQ','https://youtube.com/@example','60','ACTIVE']);
    exit;
}

if (is_post()) {
    verify_csrf();
    try {
        $spot=trim((string)input('spot_id')); $file=$_FILES['csv']??null;
        if ($spot==='' || !$file || ($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK) throw new InvalidArgumentException('Select a campaign and CSV file.');
        $fh=fopen((string)$file['tmp_name'],'rb'); if(!$fh) throw new RuntimeException('Could not read CSV file.');
        $expected=['question_mode','riddle_text','instruction_text','primary_answer','synonyms','yt_video_id','channel_url','answer_time_seconds','status'];
        $headers=array_map(static fn($v)=>strtolower(trim((string)$v)),fgetcsv($fh)?:[]);
        if($headers!==$expected) throw new InvalidArgumentException('Use the downloaded template headers exactly.');
        $rows=[];
        while(($r=fgetcsv($fh))!==false){ if(count(array_filter($r,static fn($v)=>trim((string)$v)!==''))===0) continue; if(count($r)!==count($expected)) throw new InvalidArgumentException('Every row must contain 9 columns.'); $v=array_combine($expected,$r); $mode=strtoupper(trim((string)$v['question_mode'])); $status=strtoupper(trim((string)$v['status'])); $time=(int)$v['answer_time_seconds']; if(!in_array($mode,['SINGLE','MULTIPLE'],true)||!in_array($status,['ACTIVE','INACTIVE'],true)||trim((string)$v['riddle_text'])===''||trim((string)$v['primary_answer'])===''||$time<5||$time>3600) throw new InvalidArgumentException('Invalid question mode, text, answer, timer or status.'); $rows[]=$v; }
        fclose($fh); if(!$rows) throw new InvalidArgumentException('CSV contains no question rows.'); if(count($rows)>5000) throw new InvalidArgumentException('Maximum 5,000 questions per upload.');
        $pdo->beginTransaction(); $stmt=$pdo->prepare('INSERT INTO riddle_pool(spot_id,question_mode,riddle_text,instruction_text,primary_answer,synonyms,yt_video_id,channel_url,answer_time_seconds,status) VALUES(?,?,?,?,?,?,?,?,?,?)');
        foreach($rows as $v){$syn=array_values(array_filter(array_map('trim',explode(',',(string)$v['synonyms']))));$stmt->execute([$spot,strtoupper(trim((string)$v['question_mode'])),trim((string)$v['riddle_text']),trim((string)$v['instruction_text'])?:null,trim((string)$v['primary_answer']),safe_json($syn),trim((string)$v['yt_video_id'])?:null,trim((string)$v['channel_url'])?:null,(int)$v['answer_time_seconds'],strtoupper(trim((string)$v['status']))]);}
        audit_log((int)admin()['id'],'QUESTIONS_BULK_IMPORTED','riddle_pool',null,null,['count'=>count($rows)]); $pdo->commit(); flash('success',count($rows).' questions imported.');
    } catch(Throwable $e){if(isset($pdo)&&$pdo->inTransaction())$pdo->rollBack();flash('error',$e->getMessage());}
    redirect('/admin/hunts/questions_bulk.php');
}
$spots=$pdo->query('SELECT spot_id,spot_name FROM spot_campaigns ORDER BY spot_name')->fetchAll();
$pageTitle='Bulk question upload'; $pageSubtitle='Import validated single or multiple questions using CSV'; require APP_ROOT.'/includes/header.php';
?>
<section class="panel"><div class="panel-head"><h2>Upload question bank</h2><a class="btn secondary" href="questions.php">Question Manager</a><a class="btn secondary" href="?download_template=1">Download sample CSV</a></div><form method="post" enctype="multipart/form-data" class="form-grid"><?=csrf_field()?><div class="field full"><label>Master category campaign</label><select name="spot_id" required><option value="">Select campaign</option><?php foreach($spots as $s):?><option value="<?=e($s['spot_id'])?>"><?=e($s['spot_name'].' · '.$s['spot_id'])?></option><?php endforeach?></select></div><div class="field full"><label>Question CSV file</label><input type="file" name="csv" accept=".csv,text/csv" required><small class="help">Use the sample CSV template. Supports up to 5,000 questions.</small></div><div class="field full"><button class="btn">Upload questions</button></div></form></section>
<?php require APP_ROOT.'/includes/footer.php';
