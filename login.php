<?php require_once __DIR__.'/common.php';
if(current_user()){ header('Location: index.php'); exit; }
$msg='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  try{
    if(!check_csrf()) throw new Exception('表单过期，请刷新');
    $r=geetest_verify(); if($r!==true) throw new Exception($r);
    $email=trim($_POST['email']??''); $pwd=$_POST['password']??'';
    // v1.4：占位邮箱（第三方直登账号）无密码可登录，给出明确指引
    if(is_social_local($email)) throw new Exception('该账号尚未绑定邮箱，仅支持第三方登录；请用微信/QQ 等方式登录后绑定邮箱');
    // v1.3.0：登录框同时支持 邮箱 / 展示ID（10000+注册序号）
    $u=resolve_user($email);
    if(!$u||!password_verify($pwd,$u['password_hash'])) throw new Exception('账号或密码错误');
    if($u['status']!=1) throw new Exception('账号已被禁用/注销');
    session_regenerate_id(true);
    $_SESSION['uid']=$u['id']; header('Location: index.php'); exit;
  }catch(Exception $ex){ $msg=$ex->getMessage(); }
}
$page_title='登录'; include 'header.php';
?>
<div class="form">
  <div class="flogo"><img src="school.png" alt="<?=e(SITE_NAME)?>"></div>
  <h2>欢迎回来</h2><div class="sub">使用邮箱 + 密码 或 展示ID + 密码 登录 <?=e(SITE_NAME)?></div>
  <?php if($msg):?><div class="alert"><?=ico('alert',15)?><?=e($msg)?></div><?php endif; ?>
  <form method="post"><input type="hidden" name="csrf" value="<?=csrf_token()?>">
  <input class="inp" name="email" type="text" placeholder="邮箱 / 展示ID（如 10001）" required>
  <input class="inp" type="password" name="password" placeholder="密码" required>
  <?=geetest_widget()?>
  <button class="btn block"><?=ico('user',15)?>登录</button>
  <div class="center"><a href="forgot.php">忘记密码</a> · <a href="register.php">邮箱注册</a></div>
  <?php if(SUYAN_ENABLED): ?>
  <div class="divider">其他方式</div>
  <div class="oauth-row">
    <?php foreach(suyan_types() as $t=>$p): ?>
    <a class="btn ghost" href="oauth.php?type=<?=$t?>"><span style="color:<?=$p['color']?>;display:inline-flex"><?=ico($p['icon'],16)?></span><?=$p['name']?>登录</a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
  </form>
</div>
<?php include 'footer.php'; ?>
