<?php require_once __DIR__.'/common.php';
$msg='';$ok='';
// v1.3.0：注册开关关闭时展示暂停注册提示
if(!ENABLE_REGISTER){
  $page_title='注册'; include 'header.php';
  echo '<div class="form"><div class="flogo"><img src="school.png" alt="'.e(SITE_NAME).'"></div><h2>暂停注册</h2><div class="sub">注册功能暂未开放，请稍后再来或联系管理员。</div><div class="center"><a href="login.php">已有账号？去登录</a></div></div>';
  include 'footer.php'; exit;
}
if($_SERVER['REQUEST_METHOD']==='POST'){
  try{
    if(!check_csrf()) throw new Exception('表单已过期，请刷新重试');
    $r=geetest_verify(); if($r!==true) throw new Exception($r);
    $email=trim($_POST['email']??''); $code=trim($_POST['code']??''); $name=trim($_POST['username']??''); $p1=$_POST['password']??''; $p2=$_POST['password2']??'';
    if(!filter_var($email,FILTER_VALIDATE_EMAIL)) throw new Exception('仅支持邮箱注册');
    if(mb_strlen($name)<2) throw new Exception('昵称至少2个字');
    if(strlen($p1)<6) throw new Exception('密码至少6位');
    if($p1!==$p2) throw new Exception('两次密码不一致');
    $vr=verify_code($email,$code,'reg'); if($vr!==true) throw new Exception($vr);
    $pdo=db(); $s=$pdo->prepare("SELECT id FROM users WHERE email=?"); $s->execute([$email]);
    if($s->fetch()) throw new Exception('该邮箱已注册，可直接登录');
    $pdo->prepare("INSERT INTO users(email,username,password_hash) VALUES(?,?,?)")->execute([$email,$name,password_hash($p1,PASSWORD_DEFAULT)]);
    session_regenerate_id(true);
    $_SESSION['uid']=$pdo->lastInsertId(); header('Location: index.php'); exit;
  }catch(Exception $ex){ $msg=$ex->getMessage(); }
}
$page_title='注册'; include 'header.php';
?>
<div class="form">
  <div class="flogo"><img src="school.png" alt="<?=e(SITE_NAME)?>"></div>
  <h2>创建账号</h2><div class="sub">仅支持邮箱注册 · 注册即加入校园社区</div>
  <?php if($msg):?><div class="alert"><?=ico('alert',15)?><?=e($msg)?></div><?php endif; ?>
  <form method="post">
  <input type="hidden" name="csrf" value="<?=csrf_token()?>">
  <input class="inp" name="email" id="email" type="email" placeholder="邮箱" required>
  <div class="code-row"><input class="inp" name="code" placeholder="邮箱验证码" required><button type="button" class="btn ghost small" id="sendBtn" style="white-space:nowrap"><?=ico('mail',14)?>发送验证码</button></div>
  <input class="inp" name="username" placeholder="昵称（至少 2 个字）" required>
  <input class="inp" type="password" name="password" placeholder="密码（≥6 位）" required>
  <input class="inp" type="password" name="password2" placeholder="确认密码" required>
  <?=geetest_widget()?>
  <button class="btn block"><?=ico('check',15)?>注册</button>
  <div class="center">已有账号？<a href="login.php">去登录</a></div>
  </form>
</div>
<script>
document.getElementById('sendBtn').onclick=function(){var b=this;var em=document.getElementById('email').value;if(!em){alert('请先填写邮箱');return;}
b.disabled=true;b.textContent='发送中...';var f=new FormData();f.append('email',em);f.append('scene','reg');
fetch('send_code.php',{method:'POST',body:f}).then(r=>r.json()).then(j=>{alert(j.msg+(j.debug_code?'（测试码：'+j.debug_code+'）':''));var s=60;b.textContent='重新发送(60s)';var t=setInterval(()=>{s--;b.textContent='重新发送('+s+'s)';if(s<=0){clearInterval(t);b.disabled=false;b.innerHTML='发送验证码';}},1000);}).catch(()=>{alert('发送失败');b.disabled=false;});};
</script>
<?php include 'footer.php'; ?>
