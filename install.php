<?php
declare(strict_types=1);
chdir(__DIR__);
function run(array $cmd): void {
 if (PHP_OS_FAMILY === 'Windows' && in_array($cmd[0], ['composer','npm'], true)) {
  $tool=array_shift($cmd);$resolved=null;
  foreach (explode(PATH_SEPARATOR, (string)getenv('PATH')) as $directory) {
   $directory=rtrim(str_replace('\\','/',trim($directory,'"')), '/');
   if ($tool==='composer') {
    foreach (['composer.phar','composer'] as $file) {
     $path=$directory.'/'.$file;
     if (is_file($path) && ($file==='composer.phar' || str_contains((string)file_get_contents($path,false,null,0,100),'php'))) {
      $resolved=[PHP_BINARY,$path];break 2;
     }
    }
   } else {
    $script=$directory.'/node_modules/npm/bin/npm-cli.js';
    if (is_file($script) && is_file($directory.'/node.exe')) {$resolved=[$directory.'/node.exe',$script];break;}
   }
  }
  if (!$resolved) {fwrite(STDERR,"Cannot resolve $tool. Add its standard installation directory to PATH.\n");exit(1);}
  $cmd=array_merge($resolved,$cmd);
 }
 $p=proc_open($cmd,[0=>STDIN,1=>STDOUT,2=>STDERR],$pipes);
 if(!is_resource($p)||proc_close($p)!==0)exit(1);
}
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
