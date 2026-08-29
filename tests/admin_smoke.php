<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') exit("CLI only\n");
$base=rtrim($argv[1]??'http://127.0.0.1:8099','/');$username=getenv('TEST_ADMIN_USERNAME');$password=getenv('TEST_ADMIN_PASSWORD');if(!$username||!$password)throw new RuntimeException('TEST_ADMIN_USERNAME and TEST_ADMIN_PASSWORD are required.');
$cookieFile=tempnam(sys_get_temp_dir(),'hunter-cookie-');
function web_request(string $base,string $path,string $cookieFile,string $method='GET',array $form=[]): array{$handle=curl_init($base.$path);curl_setopt_array($handle,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_HEADER=>true,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_CUSTOMREQUEST=>$method,CURLOPT_COOKIEJAR=>$cookieFile,CURLOPT_COOKIEFILE=>$cookieFile,CURLOPT_TIMEOUT=>10]);if($form)curl_setopt($handle,CURLOPT_POSTFIELDS,http_build_query($form));$raw=curl_exec($handle);if($raw===false)throw new RuntimeException(curl_error($handle));$code=(int)curl_getinfo($handle,CURLINFO_RESPONSE_CODE);$headerSize=(int)curl_getinfo($handle,CURLINFO_HEADER_SIZE);return[$code,substr($raw,0,$headerSize),substr($raw,$headerSize)];}
try{
    [$code]=web_request($base,'/admin/dashboard.php',$cookieFile);if($code!==302)throw new RuntimeException('Unauthenticated dashboard did not redirect.');
    [$code,$headers,$login]=web_request($base,'/admin/login.php',$cookieFile);if($code!==200||!preg_match('/name="csrf_token" value="([a-f0-9]{64})"/',$login,$match))throw new RuntimeException('Login form or CSRF token missing.');if(!str_contains($headers,'Content-Security-Policy:'))throw new RuntimeException('Security headers missing.');$csrf=$match[1];
    [$code]=web_request($base,'/admin/login.php',$cookieFile,'POST',['csrf_token'=>$csrf,'identity'=>$username,'password'=>$password]);if($code!==302)throw new RuntimeException('Admin login failed.');
    $routes=['/admin/dashboard.php','/admin/users/index.php','/admin/wallets/index.php','/admin/wallets/adjust.php','/admin/withdrawals/index.php','/admin/fraud/index.php','/admin/fraud/devices.php','/admin/fraud/login-attempts.php','/admin/notifications/index.php','/admin/reports/transactions.php','/admin/reports/users.php','/admin/reports/withdrawals.php','/admin/reports/security.php','/admin/admins/index.php','/admin/admins/roles.php','/admin/admins/permissions.php?role_id=1','/admin/audit/index.php','/admin/settings/index.php'];
    foreach($routes as $route){[$status,,$body]=web_request($base,$route,$cookieFile);if($status!==200||str_contains($body,'Something went wrong'))throw new RuntimeException("Route failed: {$route} ({$status})");}
    [$code]=web_request($base,'/admin/logout.php',$cookieFile);if($code!==405)throw new RuntimeException('GET logout was not rejected.');
    [$code]=web_request($base,'/admin/logout.php',$cookieFile,'POST',['csrf_token'=>$csrf]);if($code!==302)throw new RuntimeException('POST logout failed.');
    echo "Authenticated admin route, CSRF, security-header, and logout assertions passed\n";
}finally{if(is_file($cookieFile))unlink($cookieFile);}
