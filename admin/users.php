<?php
require_once __DIR__.'/../common.php';
$ADMIN=require_admin();
$pdo=db();
$msg='';
// 账号操作：仅接受 POST + CSRF，且不可操作自己或其他管理员
if($_SERVER['REQUEST_METHOD']==='POST'){
  try{
    if(!check_csrf()) throw new Exception('表单过期，请重试');
    $act=$_POST['act']??''; $id=intval($_POST['id']??0);
    if(!in_array($act,['toggle','resetpw','del','role'],true) || $id<=0) throw new Exception('非法操作');
    if($id===(int)$ADMIN['id']) throw new Exception('不可操作当前登录的管理员账号');
    $s=$pdo->prepare("SELECT * FROM users WHERE id=?"); $s->execute([$id]); $t=$s->fetch(PDO::FETCH_ASSOC);
    if(!$t) throw new Exception('账号不存在');
    if(!empty($t['is_admin'])) throw new Exception('不可操作其他管理员账号');
    if($act==='toggle'){
      $pdo->prepare("UPDATE users SET status = 1 - status WHERE id=?")->execute([$id]);
      $_SESSION['flash']='账号状态已切换';
    }elseif($act==='role'){
      // v1.3.0：身份切换（官方/运营）；「官方」仅超级管理员可赋予/撤销
      $role=$_POST['role']??'';
      if(!in_array($role,['official','operator'],true)) throw new Exception('未知身份');
      if($role==='official' && !is_super_admin($ADMIN)) throw new Exception('赋予/撤销「官方」身份仅超级管理员（admin@school.cn）可操作');
      $col=$role==='official'?'is_official':'is_operator';
      $pdo->prepare("UPDATE users SET $col=1-$col WHERE id=?")->execute([$id]);
      $_SESSION['flash']=((int)$t[$col]===1?'已撤销':'已赋予').($role==='official'?'「官方」':'「运营」').'身份';
    }elseif($act==='resetpw'){
      $newpwd=substr(bin2hex(random_bytes(4)),0,6);
      $pdo->prepare("UPDATE users SET password_hash=? WHERE id=?")->execute([password_hash($newpwd,PASSWORD_DEFAULT),$id]);
      $pdo->prepare("DELETE FROM email_codes WHERE email=?")->execute([$t['email']]);
      $_SESSION['flash']='已重置该账号密码，新密码：'.$newpwd.'（请尽快告知用户）';
    }else{
      $pdo->prepare("DELETE FROM replies WHERE uid=?")->execute([$id]);
      $pdo->prepare("DELETE FROM posts WHERE uid=?")->execute([$id]);
      $pdo->prepare("DELETE FROM social_accounts WHERE uid=?")->execute([$id]);
      $pdo->prepare("DELETE FROM likes WHERE uid=?")->execute([$id]);
      $pdo->prepare("DELETE FROM notifications WHERE uid=?")->execute([$id]);
      $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$id]);
      $_SESSION['flash']='账号 #'.$id.' 已删除（含其帖子与回复）';
    }
  }catch(Exception $ex){ $_SESSION['flash']=$ex->getMessage(); }
  header('Location: users.php'); exit;
}
$q=trim($_GET['q']??'');
$where=''; $args=[];
if($q!==''){ $where=' WHERE email LIKE ? OR username LIKE ?'; $like='%'.$q.'%'; $args=[$like,$like]; }
$total=$pdo->prepare("SELECT COUNT(*) FROM users $where"); $total->execute($args); $total=$total->fetchColumn();
$page=max(1,intval($_GET['page']??1)); $per=15; $pages=max(1,ceil($total/$per)); $page=min($page,$pages);
$off=($page-1)*$per;
$list=$pdo->query("SELECT u.*,(SELECT COUNT(*) FROM posts p WHERE p.uid=u.id) pc,(SELECT COUNT(*) FROM replies r WHERE r.uid=u.id) rc,(SELECT GROUP_CONCAT(DISTINCT sa.type) FROM social_accounts sa WHERE sa.uid=u.id) socials FROM users u $where ORDER BY u.id DESC LIMIT $per OFFSET $off")->fetchAll(PDO::FETCH_ASSOC);
$page='users'; $page_title='账号管理'; include __DIR__.'/head.php';
?>
<form class="search-bar" method="get" action="users.php"><input type="text" name="q" value="<?=e($q)?>" placeholder="搜索邮箱 / 昵称"><button class="btn primary" type="submit"><?=ico('search')?>搜索</button><a class="btn default" href="users.php">清空</a></form>
<div style="display:flex;gap:8px;margin:-4px 0 8px"><span class="tag blue">共 <?=(int)$total?> 条</span></div>
<div class="panel"><div class="table-wrap"><table class="tb"><tr>
<th style="width:50px">ID</th><th style="width:70px">展示ID</th><th>邮箱</th><th>昵称</th><th>第三方绑定</th><th>身份</th><th>帖子/回复</th><th>状态</th><th style="width:280px">操作</th></tr>
<?php foreach($list as $u): $self=($u['id']==$ADMIN['id']); ?>
<tr><td>#<?=$u['id']?></td>
<td style="font-weight:600"><?=display_uid($u['id'])?></td>
<td><?=e($u['email'])?><?=$self?' <span class="tag blue">我</span>':''?></td>
<td><?=e($u['username'])?></td>
<td><?php if(!empty($u['socials'])){ $all=suyan_types(); $tags=[]; foreach(explode(',',$u['socials']) as $t){ $tags[]='<span class="tag ok">'.e($all[$t]['name']??$t).'</span>'; } echo implode(' ',$tags); } else echo '<span class="tag default">未绑定</span>'; ?></td>
<td><?=role_badges($u) ?: '<span class="tag default">用户</span>'?></td>
<td><?=$u['pc']?> / <?=$u['rc']?></td>
<td><?=$u['status']==1?'<span class="tag ok">正常</span>':'<span class="tag err">已禁用</span>'?></td>
<td><?php if($self): ?><span class="tag default" style="color:#777">不可操作自己</span>
<?php elseif($u['is_admin']): ?><span class="tag default" style="color:#777">不可操作管理员</span>
<?php else: ?>
<form method="post" style="display:inline" title="赋予/撤销官方身份（仅超管）"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="act" value="role"><input type="hidden" name="role" value="official"><input type="hidden" name="id" value="<?=$u['id']?>"><button class="btn <?=$u['is_official']?'default':'primary'?> sm" type="submit" <?=is_super_admin($ADMIN)?'':'disabled title="仅超级管理员可操作"'?>><?=$u['is_official']?'撤销官方':'设为官方'?></button></form>
<form method="post" style="display:inline"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="act" value="role"><input type="hidden" name="role" value="operator"><input type="hidden" name="id" value="<?=$u['id']?>"><button class="btn <?=$u['is_operator']?'default':'primary'?> sm" type="submit"><?=$u['is_operator']?'撤销运营':'设为运营'?></button></form>
<form method="post" style="display:inline"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="act" value="toggle"><input type="hidden" name="id" value="<?=$u['id']?>"><button class="btn default sm" type="submit"><?=$u['status']==1?'禁用':'启用'?></button></form>
<form method="post" style="display:inline" onsubmit="return confirm('重置该账号密码（新密码将显示）？')"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="act" value="resetpw"><input type="hidden" name="id" value="<?=$u['id']?>"><button class="btn default sm" type="submit">重置密码</button></form>
<form method="post" style="display:inline" onsubmit="return confirm('删除该账号及其帖子回复？')"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="act" value="del"><input type="hidden" name="id" value="<?=$u['id']?>"><button class="btn danger sm" type="submit">删除</button></form>
<?php endif; ?></td></tr>
<?php endforeach; ?>
<?php if(!$list): ?><tr><td colspan="9" style="text-align:center;color:#999;padding:30px">暂无账号</td></tr><?php endif; ?>
</table></div>
<div class="pager">共 <?=$total?> 条 · 第 <?=$page?>/<?=$pages?> 页
<?php for($i=1;$i<=$pages;$i++): ?><?php if($i==$page):?><span class="cur"><?=$i?></span><?php else:?><a href="users.php?page=<?=$i?>&q=<?=urlencode($q)?>"><?=$i?></a><?php endif;?><?php endfor;?>
</div></div>
<?php include __DIR__.'/foot.php'; ?>