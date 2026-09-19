<?php
require_once __DIR__.'/common.php';
$errors=[]; $warns=[]; $info=[];
$drivers=PDO::getAvailableDrivers();
(in_array('sqlite',$drivers)||in_array('mysql',$drivers)) || $errors[]='服务器当前 PDO 可用驱动为空，需至少启用 pdo_sqlite 或 pdo_mysql（php.ini 打开 extension=pdo_sqlite / extension=pdo_mysql 并重启）';
version_compare(PHP_VERSION,'7.4','>=') ?: $errors[]='PHP 版本过低：当前 '.PHP_VERSION.'，需 ≥ 7.4';
is_writable(__DIR__.'/_data') || $errors[]='_data 目录不可写，请为虚拟主机授权写权限';
if(DB_TYPE==='mysql' && !in_array('mysql',$drivers)) $errors[]='DB_TYPE 为 mysql 但服务器无 pdo_mysql 扩展，可把 config.php 的 DB_TYPE 改为 sqlite';
if(DB_TYPE!=='mysql' && !in_array('sqlite',$drivers) && !in_array('mysql',$drivers)) $errors[]='SQLite 模式需要 pdo_sqlite 扩展';
if(!$errors){
  try{ db(); $ok_msg='数据库连接成功，表结构就绪（管理账号 admin@school.cn / 123456，请登录后台后尽快修改）。'; }
  catch(Exception $ex){ $errors[]=$ex->getMessage(); }
}
$isMysql=DB_TYPE==='mysql';
?>
<!doctype html><html lang="zh-CN"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>安装自检 - <?=e(SITE_NAME)?></title><link rel="icon" href="school.png"><link rel="stylesheet" href="assets/style.css?v=<?=@filemtime(__DIR__.'/assets/style.css')?>"></head>
<body><div class="wrap" style="max-width:640px;margin:60px auto">
<div class="form" style="max-width:640px"><h2>环境自检</h2><div class="sub"><?=e(SITE_NAME)?> · 虚拟主机部署检测</div>
<?php if(!empty($ok_msg)):?><div class="success"><?=e($ok_msg)?></div><?php endif;?>
<table class="tbl">
<tr><td>PHP 版本</td><td><?=PHP_VERSION?> （要求 ≥ 7.4）</td></tr>
<tr><td>运行方式</td><td>CLI <?=php_sapi_name()?>（虚拟主机一般为 apache/nginx fpm）</td></tr>
<tr><td>数据库模式</td><td><?=$isMysql?'MySQL（'.e(DB_MYSQL_NAME).'）':'SQLite（_data/forum.db）'?></td></tr>
<tr><td>PDO 可用驱动</td><td><?=e(implode('、', $drivers) ?: '无')?></td></tr>
<tr><td>_data 目录可写</td><td><?=is_writable(__DIR__.'/_data')?'是':'否'?></td></tr>
<tr><td>验证码回显模式</td><td><?=MAIL_DEBUG_SHOW_CODE?'开启（测试用，上线请在后台关闭）':'关闭'?></td></tr>
</table>
<?php if($errors): foreach($errors as $x):?><div class="alert"><?=e($x)?></div><?php endforeach; endif;?>
<?php if(!$errors):?><div class="center" style="margin-top:14px"><a class="btn" href="index.php">进入前台</a> <a class="btn ghost" href="admin/">进入后台</a></div>
<?php else:?><div class="center" style="margin-top:14px"><a class="btn ghost" href="install.php">重新检测</a></div><?php endif;?></div></div></body></html>