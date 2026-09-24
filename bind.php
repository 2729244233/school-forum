<?php
// v1.3.0 第三方登录绑定邮箱：
// oauth.php 回调未匹配到已绑定账号、且当前未登录时 → 存 pending_social 跳转本页，
// 用户输入邮箱+验证码（scene=bind）→ 已注册则绑定老号，否则创建新号；faceimg 写入头像。
// v1.4 新增「跳过」：跳过则用占位邮箱（@social.local）创建账号，
//   占位邮箱不发信、不能用于邮箱登录；绑定真实邮箱前仅支持 展示ID+密码 或 第三方登录。
require_once __DIR__.'/common.php';
$pend=$_SESSION['pending_social']??null;
$TYPES=suyan_types();
if(!$pend || empty($pend['openid']) || empty($TYPES[$pend['type']??'']) || !suyan_channel_on($pend['type'])){ header('Location: login.php'); exit; }
if(current_user()){ unset($_SESSION['pending_social']); header('Location: index.php'); exit; } // 已登录无需绑定
$P=$TYPES[$pend['type']];
$msg='';$ok='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  try{
    if(!check_csrf()) throw new Exception('表单过期，请刷新');
    $pdo=db();
    if(($_POST['act']??'')==='skip'){
      // ---- 跳过绑定：用占位邮箱直接建号（仅第三方登录 / 展示ID+密码 可登录） ----
      $pwd=(string)($_POST['pwd']??'');
      if($pwd!=='' && strlen($pwd)<6) throw new Exception('密码至少 6 位；留空则由系统随机生成');
      $name=trim((string)$pend['nickname']); if($name==='') $name='同学'.substr((string)time(),-6);
      $name=mb_substr($name,0,20);
      $ph=$pend['type'].'_'.substr(md5((string)$pend['openid']),0,12).'@social.local';
      $ck=$pdo->prepare("SELECT id FROM users WHERE email=?"); $ck->execute([$ph]);
      if($ck->fetchColumn()) $ph=$pend['type'].'_'.substr(md5($pend['openid'].microtime(true)),0,12).'@social.local';
      if($pwd==='') $pwd=bin2hex(random_bytes(8));
      $pdo->prepare("INSERT INTO users(email,username,password_hash) VALUES(?,?,?)")->execute([$ph,$name,password_hash($pwd,PASSWORD_DEFAULT)]);
      $uid=(int)$pdo->lastInsertId();
      $pdo->prepare("INSERT INTO social_accounts(uid,type,social_uid,nickname) VALUES(?,?,?,?)")->execute([$uid,$pend['type'],$pend['openid'],(string)$pend['nickname']]);
      if($pend['type']==='wx') $pdo->prepare("UPDATE users SET wechat_openid=?,wechat_name=? WHERE id=?")->execute([$pend['openid'],(string)$pend['nickname'],$uid]);
      if(!empty($pend['avatar'])){
        try{ $pdo->prepare("UPDATE users SET avatar=? WHERE id=? AND (avatar IS NULL OR avatar='')")->execute([(string)$pend['avatar'],$uid]); }catch(Exception $ax){}
      }
      unset($_SESSION['pending_social']);
      session_regenerate_id(true);
      $_SESSION['uid']=$uid;
      $_SESSION['bind_asked']=1; // 刚在页面上跳过：本次会话不再弹绑定提醒，个人中心仍可随时绑定
      $_SESSION['flash_ok']='已用'.$P['name'].'登录，展示ID '.display_uid($uid).'。绑定邮箱后可用邮箱登录与找回密码';
      header('Location: index.php'); exit;
    }
    // ---- 绑定邮箱：已注册则绑定老号，未注册则创建新号 ----
    $r=geetest_verify(); if($r!==true) throw new Exception($r);
    $email=trim($_POST['email']??''); $code=trim($_POST['code']??'');
    if(!filter_var($email,FILTER_VALIDATE_EMAIL)) throw new Exception('请填写正确的邮箱地址');
    if(is_social_local($email)) throw new Exception('请填写真实邮箱地址');
    $vr=verify_code($email,$code,'bind'); if($vr!==true) throw new Exception($vr);
    $s=$pdo->prepare("SELECT * FROM users WHERE email=?"); $s->execute([$email]); $ex=$s->fetch(PDO::FETCH_ASSOC);
    if($ex){
      // 邮箱已注册 → 绑定老账号
      if((int)$ex['status']!==1) throw new Exception('该账号已被禁用');
      $c=$pdo->prepare("SELECT uid FROM social_accounts WHERE type=? AND social_uid=?"); $c->execute([$pend['type'],$pend['openid']]);
      $cu=$c->fetchColumn();
      if($cu && (int)$cu!==(int)$ex['id']) throw new Exception('该'.$P['name'].'账号已绑定其他邮箱');
      if(!$cu) $pdo->prepare("INSERT INTO social_accounts(uid,type,social_uid,nickname) VALUES(?,?,?,?)")->execute([(int)$ex['id'],$pend['type'],$pend['openid'],(string)$pend['nickname']]);
      if($pend['type']==='wx') $pdo->prepare("UPDATE users SET wechat_openid=?,wechat_name=? WHERE id=?")->execute([$pend['openid'],(string)$pend['nickname'],(int)$ex['id']]);
      $uid=(int)$ex['id']; $ok='绑定成功，已为你登录';
    }else{
      // 邮箱未注册 → 创建新号（随机密码，可用找回密码功能设置）
      $name=trim((string)$pend['nickname']); if($name==='') $name='同学'.substr((string)time(),-6);
      $name=mb_substr($name,0,20);
      $pdo->prepare("INSERT INTO users(email,username,password_hash) VALUES(?,?,?)")->execute([$email,$name,password_hash(bin2hex(random_bytes(8)),PASSWORD_DEFAULT)]);
      $uid=(int)$pdo->lastInsertId();
      $pdo->prepare("INSERT INTO social_accounts(uid,type,social_uid,nickname) VALUES(?,?,?,?)")->execute([$uid,$pend['type'],$pend['openid'],(string)$pend['nickname']]);
      if($pend['type']==='wx') $pdo->prepare("UPDATE users SET wechat_openid=?,wechat_name=? WHERE id=?")->execute([$pend['openid'],(string)$pend['nickname'],$uid]);
      $ok='注册成功，欢迎加入'.SITE_NAME;
    }
    // faceimg 写入头像（仅当账号还没有头像时，容错不阻断登录）
    if(!empty($pend['avatar'])){
      try{ $pdo->prepare("UPDATE users SET avatar=? WHERE id=? AND (avatar IS NULL OR avatar='')")->execute([(string)$pend['avatar'],$uid]); }catch(Exception $ax){}
    }
    unset($_SESSION['pending_social']);
    session_regenerate_id(true);
    $_SESSION['uid']=$uid;
    $_SESSION['flash_ok']=$ok;
    header('Location: index.php'); exit;
  }catch(Exception $ex2){ $msg=$ex2->getMessage(); }
}
$page_title='绑定邮箱'; include __DIR__.'/header.php';
?>
<div class="form">
  <div class="flogo"><img src="school.png" alt="<?=e(SITE_NAME)?>"></div>
  <h2>绑定邮箱</h2>
  <div class="sub">首次使用<?=e($P['name'])?>登录，绑定邮箱后可完成注册（保障账号安全与找回密码）；也可以先跳过，稍后在个人中心绑定。</div>
  <?php if($msg):?><div class="alert"><?=ico('alert',15)?><?=e($msg)?></div><?php endif;?>
  <form method="post">
  <input type="hidden" name="csrf" value="<?=csrf_token()?>">
  <input class="inp" name="email" id="email" type="email" placeholder="邮箱" required>
  <div class="code-row"><input class="inp" name="code" placeholder="邮箱验证码" required><button type="button" class="btn ghost small" id="sendBtn" style="white-space:nowrap"><?=ico('mail',14)?>发送验证码</button></div>
  <?=geetest_widget()?>
  <button class="btn block"><?=ico('link',15)?>绑定并登录</button>
  <div class="center">绑定即代表同意以该邮箱创建/登录<?=e(SITE_NAME)?>账号</div>
  </form>
</div>

<!-- v1.4 跳过绑定：用第三方账号直接进入（占位邮箱，不发信、不可用于邮箱登录） -->
<div class="form">
  <div class="sub" style="margin-bottom:10px">不想现在绑定邮箱？可以先用<?=e($P['name'])?>账号进入；绑定前仅支持第三方登录或 展示ID + 密码 登录。</div>
  <form method="post">
  <input type="hidden" name="csrf" value="<?=csrf_token()?>">
  <input type="hidden" name="act" value="skip">
  <input class="inp" name="pwd" type="password" placeholder="设置密码（可选，用于展示ID登录）">
  <button class="btn ghost block" type="submit"><?=ico('back',15)?>跳过，稍后绑定邮箱</button>
  </form>
</div>
<script>
document.getElementById('sendBtn').onclick=function(){var b=this;var em=document.getElementById('email').value;if(!em){alert('请先填写邮箱');return;}
b.disabled=true;b.textContent='发送中...';var f=new FormData();f.append('email',em);f.append('scene','bind');
fetch('send_code.php',{method:'POST',body:f}).then(r=>r.json()).then(j=>{alert(j.msg+(j.debug_code?'（测试码：'+j.debug_code+'）':''));var s=60;b.textContent='重新发送(60s)';var t=setInterval(()=>{s--;b.textContent='重新发送('+s+'s)';if(s<=0){clearInterval(t);b.disabled=false;b.innerHTML='发送验证码';}},1000);}).catch(()=>{alert('发送失败');b.disabled=false;});};
</script>
<?php include __DIR__.'/footer.php'; ?>
