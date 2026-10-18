<?php
declare(strict_types=1);
chdir(__DIR__);
function run(array $cmd): void { if (PHP_OS_FAMILY === "Windows") { if ($cmd[0] === "npm") $cmd[0]="npm.cmd"; if ($cmd[0] === "composer") $cmd[0]="composer.bat"; } $p=proc_open($cmd,[0=>STDIN,1=>STDOUT,2=>STDERR],$pipes); if(!is_resource($p)||proc_close($p)!==0)exit(1); }
foreach(['pdo_sqlite','mbstring','openssl','fileinfo','dom','xml'] as $e)if(!extension_loaded($e)){fwrite(STDERR,"Missing PHP extension: $e\n");exit(1);}
foreach(['storage/app/private','storage/app/public','storage/framework/cache/data','storage/framework/sessions','storage/framework/views','storage/logs','bootstrap/cache'] as $dir)if(!is_dir($dir)&&!mkdir($dir,0700,true))exit(1);
$fresh=!file_exists('.env');
if($fresh){copy('.env.example','.env');chmod('.env',0600);}
if(!file_exists('database/database.sqlite'))touch('database/database.sqlite');
run(['composer','install','--no-interaction','--prefer-dist']);
if($fresh)run([PHP_BINARY,'artisan','key:generate','--force']);
run([PHP_BINARY,'artisan','migrate','--force']);
if(!is_link('public/storage') && !file_exists('public/storage')) run([PHP_BINARY,'artisan','storage:link']);
run(['npm','ci']);run(['npm','run','build']);
echo "Installed. Run php artisan app:create-admin, then php artisan serve. Configure SMTP for public hosting.\n";
