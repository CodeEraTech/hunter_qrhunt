<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
if (PHP_SAPI !== 'cli') exit("CLI only\n");
[$script,$name,$username,$email,$password] = array_pad($argv,5,null);
if (!$name || !$username || !$email || !$password || strlen($password)<12) exit("Usage: php database/create_admin.php 'Name' username email password(12+ chars)\n");
$role=(int)db()->query("SELECT id FROM admin_roles WHERE name='Super Admin'")->fetchColumn();
$stmt=db()->prepare('INSERT INTO admins(role_id,name,username,email,password_hash,status) VALUES(?,?,?,?,?,"ACTIVE")');
$stmt->execute([$role,$name,$username,$email,password_hash($password,PASSWORD_DEFAULT)]);
echo "Super Admin created.\n";

