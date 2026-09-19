<?php require_once __DIR__.'/common.php';
$msg='';$ok='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 try{
  if(!check_csrf()) throw new Exception('表单过期');
  $r=geetest_verify(); if($r!==true) throw new Exception($r);
  $email=trim($_POST['email']??'');$code=trim($_POST['code']??'');$p1=$_POST['password']??'';$p2=$_POST['password2']??'';
  if(strlen($p1)<6) throw new Exception('密码至少6位');
  if($p1!==$p2) throw new Exception('两次密码不一致');
  $vr=verify_code($email,$code,'forgot'); if($vr!==true) throw new Exception($vr);
  $s=db()->prepare("SELECT id FROM users WHERE email=?");$s->execute([$email]);if(!$s->fetch()) throw new Exception('该邮箱未注册');
  db()->prepare("UPDATE users SET password_hash=? WHERE email=?")->execute([password_hash($p1,PASSWORD_DEFAULT),$email]);
  $ok='密码已重置，请重新登录。';
 }catch(Exception $ex){$msg=$ex->getMessage();}
}
$page_title='忘记密码';include 'header.php';
?>
<div class="form">
  <div class="flogo"><img src="school.png" alt="<?=e(SITE_NAME)?>"></div>
  <h2>找回密码</h2><div class="sub">通过邮箱验证码重置密码</div>
  <?php if($msg):?><div class="alert"><?=ico('alert',15)?><?=e($msg)?></div><?php endif;?>
  <?php if($ok):?><div class="success"><?=ico('check',15)?><?=e($ok)?> <a href="login.php">去登录</a></div><?php endif;?>
  <form method="post"><input type="hidden" name="csrf" value="<?=csrf_token()?>">
  <input class="inp" name="email" id="email" type="email" placeholder="注册邮箱" required>
  <div class="code-row"><input class="inp" name="code" placeholder="邮箱验证码" required><button type="button" class="btn ghost small" id="sendBtn" style="white-space:nowrap"><?=ico('mail',14)?>发送验证码</button></div>
  <input class="inp" type="password" name="password" placeholder="新密码（≥6 位）" required>
  <input class="inp" type="password" name="password2" placeholder="确认新密码" required>
  <?=geetest_widget()?>
  <button class="btn block"><?=ico('key',15)?>重置密码</button>
  <div class="center"><a href="login.php">返回登录</a></div>
  </form>
</div>
<script>document.getElementById('sendBtn').onclick=function(){var b=this;var em=document.getElementById('email').value;if(!em){alert('请填写邮箱');return;}b.disabled=true;b.textContent='发送中...';var f=new FormData();f.append('email',em);f.append('scene','forgot');fetch('send_code.php',{method:'POST',body:f}).then(r=>r.json()).then(j=>{alert(j.msg+(j.debug_code?'（测试码：'+j.debug_code+'）':''));b.disabled=false;b.textContent='发送验证码';}).catch(()=>{alert('发送失败');b.disabled=false;});};</script>
<?php include 'footer.php'; ?>
