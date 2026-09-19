<?php require_once __DIR__.'/common.php'; $u=require_login(); $msg='';$ok='';
$pdo=db();
/* v1.3.0：支持 ?uid=N 查看他人公开主页（仅展示昵称/身份/帖子，不含账号设置） */
$viewUid=(int)($_GET['uid']??0);
$isSelf = $viewUid<=0 || $viewUid===(int)$u['id'];
if($isSelf){ $pu=$u; }
else{
  $ps=$pdo->prepare("SELECT * FROM users WHERE id=?"); $ps->execute([$viewUid]);
  $pu=$ps->fetch(PDO::FETCH_ASSOC);
  if(!$pu){ header('Location: user.php'); exit; }
}
if($_SERVER['REQUEST_METHOD']==='POST' && $isSelf){
 try{
  if(!check_csrf()) throw new Exception('表单过期');
  $act=$_POST['act']??'';
  if($act==='password'){
    $old=$_POST['old']??'';$p1=$_POST['p1']??'';$p2=$_POST['p2']??'';
    if(!password_verify($old,$u['password_hash'])) throw new Exception('原密码错误');
    if(strlen($p1)<6) throw new Exception('新密码至少6位');
    if($p1!==$p2) throw new Exception('两次新密码不一致');
    $pdo->prepare("UPDATE users SET password_hash=? WHERE id=?")->execute([password_hash($p1,PASSWORD_DEFAULT),$u['id']]);
    $ok='密码修改成功';
  }elseif($act==='profile'){
    // v1.3.0：昵称每月限改 10 次（跨月自动重置计数）
    $name=trim($_POST['username']??'');
    if(mb_strlen($name)<2) throw new Exception('昵称至少2个字');
    if(mb_strlen($name)>20) throw new Exception('昵称不能超过 20 个字');
    $month=date('Y-m');
    $cnt=((string)$u['nick_month']===$month)?(int)$u['nick_count']:0;
    if($name!==$u['username']){
      if($cnt>=10) throw new Exception('本月昵称修改次数已用完（10 次/月，下月自动重置）');
      $pdo->prepare("UPDATE users SET username=?, nick_month=?, nick_count=? WHERE id=?")->execute([$name,$month,$cnt+1,$u['id']]);
      $ok='昵称已更新（本月剩余 '.(9-$cnt).' 次）';
    }else{ $ok='昵称未变化'; }
  }elseif($act==='unbind'){
    $type=$_POST['type']??'';
    $types=suyan_types();
    if(!isset($types[$type])) throw new Exception('未知的登录方式');
    $pdo->prepare("DELETE FROM social_accounts WHERE uid=? AND type=?")->execute([$u['id'],$type]);
    if($type==='wx') $pdo->prepare("UPDATE users SET wechat_openid='',wechat_name='' WHERE id=?")->execute([$u['id']]);
    $ok='已解绑'.$types[$type]['name'];
  }elseif($act==='delete'){
    // v1.4：未绑定邮箱的账号（占位邮箱）改用展示 ID 确认注销
    $confirm=trim($_POST['confirm']??'');
    $expect=is_social_local((string)$u['email'])?(string)display_uid($u['id']):(string)$u['email'];
    if($confirm!==$expect) throw new Exception(is_social_local((string)$u['email'])?'请输入展示 ID 以确认注销':'请输入注册邮箱以确认注销');
    $pdo->prepare("DELETE FROM replies WHERE uid=?")->execute([$u['id']]);
    $pdo->prepare("DELETE FROM posts WHERE uid=?")->execute([$u['id']]);
    $pdo->prepare("DELETE FROM social_accounts WHERE uid=?")->execute([$u['id']]);
    $pdo->prepare("DELETE FROM likes WHERE uid=?")->execute([$u['id']]);
    $pdo->prepare("DELETE FROM notifications WHERE uid=?")->execute([$u['id']]);
    $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$u['id']]);
    session_destroy(); header('Location: index.php?bye=1'); exit;
  }
  $u=current_user(); $pu=$u;
 }catch(Exception $ex){$msg=$ex->getMessage();}
}
$pc=(int)$pdo->query("SELECT COUNT(*) FROM posts WHERE uid=".(int)$pu['id'])->fetchColumn();
$rc=(int)$pdo->query("SELECT COUNT(*) FROM replies WHERE uid=".(int)$pu['id'])->fetchColumn();
$lc=(int)$pdo->query("SELECT COUNT(*) FROM likes WHERE target_type='post' AND target_id IN (SELECT id FROM posts WHERE uid=".(int)$pu['id'].")")->fetchColumn();
$myPosts=$pdo->prepare("SELECT id,board,title,created_at,views FROM posts WHERE uid=? ORDER BY id DESC LIMIT 10");
$myPosts->execute([(int)$pu['id']]); $myPosts=$myPosts->fetchAll(PDO::FETCH_ASSOC);
$socials=[];
$sq=$pdo->prepare("SELECT type,nickname FROM social_accounts WHERE uid=?");
$sq->execute([(int)$pu['id']]);
foreach($sq->fetchAll(PDO::FETCH_ASSOC) as $r) $socials[$r['type']]=$r['nickname'];
$page_title=$isSelf?'个人中心':$pu['username'].' 的主页'; include 'header.php';
?>
<div style="max-width:680px;margin:36px auto 0">
<?php if($msg):?><div class="alert"><?=ico('alert',15)?><?=e($msg)?></div><?php endif;?>
<?php if($ok):?><div class="success"><?=ico('check',15)?><?=e($ok)?></div><?php endif;?>

<div class="card"><div class="card-body">
  <div class="uc-head">
    <?=avatar_html($pu,64)?>
    <div>
      <div class="uc-name"><?=e($pu['username'])?> <?=role_badges($pu)?></div>
      <div class="uc-sub">UID：<?=display_uid($pu['id'])?> · 发帖 <?=$pc?> · 回复 <?=$rc?> · 获赞 <?=$lc?></div>
    </div>
  </div>
</div></div>

<div class="card"><div class="card-body">
  <div class="uc-sec"><?=ico('file',16)?>最近帖子</div>
  <?php foreach($myPosts as $mp): ?>
  <a class="hot-item" href="view.php?id=<?=$mp['id']?>">
    <span class="hot-rank"><?=ico('file',13)?></span>
    <span class="hot-main">
      <span class="hot-title"><?=e($mp['title']!==''?$mp['title']:'（无标题）')?></span>
      <span class="hot-sub" style="display:block"><?=e($mp['board'])?> · <?=e($mp['created_at'])?> · <?=$mp['views']?> 浏览</span>
    </span>
  </a>
  <?php endforeach; ?>
  <?php if(!$myPosts): ?><div class="empty">暂无帖子</div><?php endif; ?>
</div></div>

<?php if($isSelf): ?>
<div class="card"><div class="card-body">
  <div class="uc-sec"><?=ico('info',16)?>账号信息</div>
  <table class="tbl">
    <tr><td>展示 ID</td><td><?=display_uid($pu['id'])?> <span class="muted">（可用于登录）</span></td></tr>
    <?php $mailBound=!is_social_local((string)$pu['email']); ?>
    <tr><td>注册邮箱</td><td>
      <?php if($mailBound): ?>
        <?=e($pu['email'])?> <span class="muted">（不可修改）</span>
      <?php else: ?>
        <span class="tagx no">未绑定</span>
        <button class="btn small" type="button" onclick="window.openBind&&window.openBind()"><?=ico('mail',14)?>绑定邮箱</button>
        <div class="muted" style="margin-top:6px">绑定前仅支持 展示 ID + 密码 或 第三方方式登录，且收不到站内消息邮件提醒。</div>
      <?php endif; ?>
    </td></tr>
    <tr><td>昵称</td><td><?=e($pu['username'])?></td></tr>
    <?php foreach(suyan_types() as $t=>$p): $bound=array_key_exists($t,$socials); ?>
    <tr><td><span style="color:<?=$p['color']?>;display:inline-flex;vertical-align:-3px"><?=ico($p['icon'],15)?></span> <?=$p['name']?></td><td>
      <?php if($bound):?>
        <span class="tagx ok">已绑定</span> <?=e($socials[$t]?:$p['name'].'用户')?>
        <form method="post" style="display:inline" onsubmit="return confirm('确定解绑<?=$p['name']?>吗？')"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="act" value="unbind"><input type="hidden" name="type" value="<?=$t?>"><button class="btn ghost small">解绑</button></form>
      <?php else: ?>
        <span class="tagx no">未绑定</span>
        <?php if(SUYAN_ENABLED): ?><a class="btn small" href="oauth.php?type=<?=$t?>"><?=ico($p['icon'],14)?>绑定<?=$p['name']?></a>
        <?php else: ?><span class="muted">聚合登录开启后可绑定</span><?php endif;?>
      <?php endif;?>
    </td></tr>
    <?php endforeach; ?>
  </table>
</div></div>

<div class="card"><div class="card-body">
  <div class="uc-sec"><?=ico('edit',16)?>账号设置</div>
  <!-- v1.3.0：改昵称 / 改密码改为弹窗操作 -->
  <div style="display:flex;gap:10px;flex-wrap:wrap">
    <button class="btn ghost" type="button" onclick="openModal('nickModal')"><?=ico('edit',15)?>修改昵称</button>
    <button class="btn ghost" type="button" onclick="openModal('pwModal')"><?=ico('key',15)?>修改密码</button>
  </div>
  <p class="muted" style="margin-top:10px">昵称每自然月限改 10 次（当前<?=((string)$pu['nick_month']===date('Y-m'))?(int)$pu['nick_count']:0?>/10 次，跨月重置）</p>
</div></div>

<div class="card"><div class="card-body">
  <div class="uc-sec danger"><?=ico('alert',16)?>注销账号</div>
  <p class="muted" style="margin-bottom:10px">注销将永久删除你的帖子与回复，不可恢复。请输入<?=is_social_local((string)$u['email'])?'展示 ID':'注册邮箱'?>确认。</p>
  <form method="post" onsubmit="return confirm('确定注销账号吗？不可恢复！')"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="act" value="delete">
  <input class="inp" name="confirm" placeholder="输入 <?=is_social_local((string)$u['email'])?display_uid($u['id']):e($u['email'])?> 确认注销" required>
  <button class="btn danger block"><?=ico('trash',15)?>确认注销账号</button></form>
</div></div>

<!-- 修改昵称弹窗 -->
<div class="modal-mask" id="nickModal">
  <div class="modal">
    <div class="modal-head"><span class="modal-title"><?=ico('edit',17)?>修改昵称</span><button class="modal-close" type="button" onclick="closeModal('nickModal')" aria-label="关闭"><?=ico('close',18)?></button></div>
    <form method="post"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="act" value="profile">
      <input class="inp" name="username" value="<?=e($u['username'])?>" required maxlength="20">
      <button class="btn block"><?=ico('check',15)?>保存昵称</button>
    </form>
  </div>
</div>
<!-- 修改密码弹窗 -->
<div class="modal-mask" id="pwModal">
  <div class="modal">
    <div class="modal-head"><span class="modal-title"><?=ico('key',17)?>修改密码</span><button class="modal-close" type="button" onclick="closeModal('pwModal')" aria-label="关闭"><?=ico('close',18)?></button></div>
    <form method="post"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="act" value="password">
      <input class="inp" type="password" name="old" placeholder="原密码" required>
      <input class="inp" type="password" name="p1" placeholder="新密码（≥6 位）" required>
      <input class="inp" type="password" name="p2" placeholder="确认新密码" required>
      <button class="btn block"><?=ico('key',15)?>修改密码</button>
    </form>
  </div>
</div>
<script>
function openModal(id){ document.getElementById(id).style.display='flex'; document.body.style.overflow='hidden'; }
function closeModal(id){ document.getElementById(id).style.display='none'; document.body.style.overflow=''; }
document.querySelectorAll('.modal-mask').forEach(function(m){
  m.addEventListener('click',function(e){ if(e.target===m){ m.style.display='none'; document.body.style.overflow=''; } });
});
document.addEventListener('keydown',function(e){ if(e.key==='Escape'){ document.querySelectorAll('.modal-mask').forEach(function(m){ m.style.display='none'; }); document.body.style.overflow=''; } });
</script>
<?php endif; ?>
</div>
<?php include 'footer.php'; ?>
