<?php
require_once __DIR__.'/../common.php';
// v1.3.0：前台登录永不进后台 —— 只有通过本页登录（admin_ok 标记）的会话才能进入后台
if(current_user() && !empty($_SESSION['admin_ok'])){ header('Location: index.php'); exit; }
$msg='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 try{
  if(!check_csrf()) throw new Exception('表单过期，请刷新');
  $r=geetest_verify(); if($r!==true) throw new Exception($r);
  $email=trim($_POST['email']??''); $pwd=$_POST['password']??'';
  // v1.3.0：后台登录放宽为 管理员/官方/运营 三身份任一
  $s=db()->prepare("SELECT * FROM users WHERE email=? AND (is_admin=1 OR is_official=1 OR is_operator=1)"); $s->execute([$email]); $u=$s->fetch(PDO::FETCH_ASSOC);
  if(!$u || !password_verify($pwd,$u['password_hash'])) throw new Exception('账号或密码错误');
  if($u['status']!=1) throw new Exception('账号已被禁用');
  session_regenerate_id(true);
  $_SESSION['uid']=$u['id'];
  $_SESSION['admin_ok']=true; // 后台区域标记
  header('Location: index.php'); exit;
 }catch(Exception $ex){ $msg=$ex->getMessage(); }
}
?><!doctype html><html lang="zh-CN"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>后台登录 - <?=e(SITE_NAME)?></title>
<link rel="icon" href="../school.png" type="image/png">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:-apple-system,BlinkMacSystemFont,"PingFang SC","Microsoft YaHei",Inter,sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#F6F5FA;background-image:radial-gradient(closest-side at 50% 20%,rgba(108,76,246,.12),transparent);-webkit-font-smoothing:antialiased}
.svg-ico{vertical-align:-3px}
.login-card{background:#fff;border:1px solid #EAE8F3;border-radius:18px;box-shadow:0 16px 48px rgba(60,40,160,.12);padding:40px 36px;width:100%;max-width:380px}
.login-card .brand{display:flex;align-items:center;gap:10px;justify-content:center;margin-bottom:6px}
.login-card .brand img{width:38px;height:38px;border-radius:10px;box-shadow:0 2px 8px rgba(108,76,246,.25)}
.login-card .brand .t{font-size:18px;font-weight:800;color:#211F33}
.login-card .sub{text-align:center;color:#7A7790;font-size:13px;margin-bottom:26px}
.fg{margin-bottom:14px}
.fg label{display:block;font-size:12.5px;color:#55536B;font-weight:600;margin-bottom:6px}
.fg input{width:100%;border:1.5px solid #EAE8F3;border-radius:10px;padding:11px 13px;font-size:14px;outline:none;transition:all .18s;font-family:inherit}
.fg input:focus{border-color:#6C4CF6;box-shadow:0 0 0 3px rgba(108,76,246,.12)}
.btn{width:100%;display:flex;align-items:center;justify-content:center;gap:7px;border:0;border-radius:11px;padding:12px 0;font-size:14.5px;font-weight:600;cursor:pointer;transition:all .18s;margin-top:6px;background:#6C4CF6;color:#fff}
.btn:hover{background:#5B3FE0;box-shadow:0 6px 18px rgba(108,76,246,.32)}
.alert{display:flex;align-items:flex-start;gap:8px;background:#FEF2F2;border:1px solid #FBD5D6;color:#B4232A;padding:10px 14px;border-radius:10px;font-size:13px;margin-bottom:16px;line-height:1.6}
.links{display:flex;justify-content:center;gap:18px;margin-top:20px;font-size:13px;color:#7A7790}.links a{color:#6C4CF6}.links a:hover{text-decoration:underline}
</style></head>
<body>
<div class="login-card">
  <div class="brand"><img src="../school.png" alt="<?=e(SITE_NAME)?>"><span class="t"><?=e(SITE_NAME)?> 后台</span></div>
  <div class="sub">管理员登录</div>
  <?php if($msg):?><div class="alert"><?=ico('alert',15)?><?=e($msg)?></div><?php endif;?>
  <form method="post"><input type="hidden" name="csrf" value="<?=csrf_token()?>">
    <div class="fg"><label>管理员邮箱</label><input name="email" type="email" placeholder="请输入邮箱" required></div>
    <div class="fg"><label>密码</label><input type="password" name="password" placeholder="请输入密码" required></div>
    <?=geetest_widget()?>
    <button class="btn" type="submit"><?=ico('lock',15)?>登录</button>
  </form>
  <div class="links"><a href="../index.php">返回前台</a><a href="../login.php">前台登录</a></div>
</div></body></html>
