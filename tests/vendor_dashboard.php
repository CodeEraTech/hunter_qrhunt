<?php
declare(strict_types=1);
// Isolated, in-memory fixtures only; never touches the configured application database.
require dirname(__DIR__).'/includes/functions.php';
require dirname(__DIR__).'/includes/vendor_dashboard.php';
function check_store(bool $ok, string $message): void { if (!$ok) throw new RuntimeException($message); }
$pdo = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$pdo->exec('CREATE TABLE vendors(id INTEGER PRIMARY KEY,shop_name TEXT,vendor_code TEXT,bank_account_number TEXT);
CREATE TABLE vendor_payout_requests(id INTEGER PRIMARY KEY,vendor_id INTEGER,amount NUMERIC,status TEXT,requested_at TEXT,paid_at TEXT);
CREATE TABLE vendor_products(id INTEGER PRIMARY KEY,vendor_id INTEGER,product_code TEXT,title TEXT,price NUMERIC,metadata TEXT,status TEXT,admin_note TEXT,submitted_at TEXT);
CREATE TABLE users(id INTEGER PRIMARY KEY,name TEXT);
CREATE TABLE wallet_checkout_transactions(id INTEGER PRIMARY KEY,checkout_id TEXT,user_id INTEGER,merchant_id INTEGER,bill_amount NUMERIC,status TEXT,created_at TEXT);
CREATE TABLE merchant_settlements(id INTEGER PRIMARY KEY,checkout_id TEXT,merchant_id INTEGER,settlement_id TEXT,status TEXT,platform_fee NUMERIC,merchant_amount NUMERIC,settled_at TEXT);');
$pdo->exec("INSERT INTO vendors VALUES(1,'First store','FIRST','123456789012'),(2,'Private second store','SECOND','987654321098'),(3,'Empty store','EMPTY',NULL);
INSERT INTO vendor_payout_requests VALUES(1,1,100,'PENDING','2026-09-01',NULL),(2,1,200,'APPROVED','2026-09-02',NULL),(3,1,300,'PAID','2026-09-03','2026-09-04'),(4,1,50,'REJECTED','2026-09-04',NULL),(5,2,9000,'PENDING','2026-09-05',NULL);
INSERT INTO vendor_products VALUES(1,1,'FIRST-SKU','First product',25,'{}','APPROVED',NULL,'2026-09-01'),(2,1,'REVIEW-SKU','Review product',35,'{}','RESUBMITTED','Correct title','2026-09-02'),(3,2,'SECRET-SKU','Private product',999,'{}','APPROVED',NULL,'2026-09-03');
INSERT INTO users VALUES(1,'Customer A'),(2,'Customer B');
INSERT INTO wallet_checkout_transactions VALUES(1,'FIRST-PAY',1,1,100,'SUCCESS','2026-09-01'),(2,'PENDING-PAY',1,1,50,'PENDING','2026-09-02'),(3,'FAILED-PAY',1,1,25,'FAILED','2026-09-03'),(4,'SECRET-PAY',2,2,9000,'SUCCESS','2026-09-04');
INSERT INTO merchant_settlements VALUES(1,'FIRST-PAY',1,'FIRST-SET','SETTLED',2,98,'2026-09-02'),(2,'SECRET-PAY',2,'SECRET-SET','SETTLED',180,8820,'2026-09-05'),(3,'PENDING-PAY',2,'WRONG-STORE-SET','PENDING',1,49,NULL);");
// URL/form vendor IDs must have no influence on the authenticated store scope.
$_GET['vendor_id'] = 2; $_GET['id'] = 2; $_POST['vendor_id'] = 2;
$data = vendor_dashboard_data($pdo,1);
check_store($data['store']['shop_name']==='First store','Store identity leaked.');
check_store($data['store']['bank_masked']==='XXXX XXXX 9012','Bank masking failed.');
check_store(!str_contains(json_encode($data),'123456789012'),'Raw banking data reached view data.');
check_store((int)$data['withdrawals']['summary']['total']===4,'Withdrawal scope/count failed.');
check_store((float)$data['withdrawals']['summary']['pending']===100.0,'Pending withdrawal calculation failed.');
check_store((float)$data['withdrawals']['summary']['approved']===500.0,'Approved/paid withdrawal calculation failed.');
check_store((float)$data['withdrawals']['summary']['amount']===650.0,'Total withdrawal calculation failed.');
check_store((int)$data['products']['summary']['approved']===1 && (int)$data['products']['summary']['pending']===1,'Product approval metrics failed.');
check_store((float)$data['payments']['summary']['approved']===100.0 && (float)$data['payments']['summary']['pending']===50.0,'Payment status metrics failed.');
check_store((float)$data['payments']['summary']['amount']===175.0,'All-status payment total failed.');
check_store(!str_contains(json_encode($data),'SECRET') && !str_contains(json_encode($data),'WRONG-STORE'),'Cross-vendor data leaked.');
$payment = array_values(array_filter($data['payments']['rows'],fn($r)=>$r['checkout_id']==='FIRST-PAY'))[0];
check_store((float)$payment['platform_fee']===2.0 && (float)$payment['merchant_amount']===98.0,'Recorded commission/net settlement failed.');
$empty = vendor_dashboard_data($pdo,3);
foreach (['withdrawals','products','payments'] as $section) check_store($empty[$section]['rows']===[] && (int)$empty[$section]['summary']['total']===0,'Empty state failed.');
$insert = $pdo->prepare("INSERT INTO vendor_payout_requests VALUES(?,1,1,'PENDING','2026-09-06',NULL)");
for ($i=6;$i<=30;$i++) $insert->execute([$i]);
$first = vendor_dashboard_data($pdo,1,['withdrawals'=>1]);
$second = vendor_dashboard_data($pdo,1,['withdrawals'=>999]);
check_store(count($first['withdrawals']['rows'])===20 && count($second['withdrawals']['rows'])===9,'Pagination lost records.');
check_store($second['withdrawals']['page']===2,'Pagination bounds failed.');
check_store(array_intersect(array_column($first['withdrawals']['rows'],'id'),array_column($second['withdrawals']['rows'],'id'))===[],'Pagination overlaps.');
check_store($first['withdrawals']['summary']===$second['withdrawals']['summary'],'Summary incorrectly limited to one page.');
try { vendor_dashboard_data($pdo,0); throw new RuntimeException('Invalid store accepted.'); } catch (InvalidArgumentException $e) {}
echo "Vendor dashboard: isolation, tampered IDs, bank masking, metrics, settlement joins, empty states and pagination passed.\n";
