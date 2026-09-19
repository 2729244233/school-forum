<?php require_once __DIR__.'/common.php'; $pdo=db(); $me=current_user();
$id=intval($_GET['id']??0);
$s=$pdo->prepare("SELECT p.*,u.username,u.avatar,u.is_admin,u.is_official,u.is_operator FROM posts p LEFT JOIN users u ON u.id=p.uid WHERE p.id=?");$s->execute([$id]);$p=$s->fetch(PDO::FETCH_ASSOC);
if(!$p){ header('Location: index.php'); exit; }
$can_del = $me && ($me['id']==$p['uid'] || !empty($me['is_admin']));
if($me && $_SERVER['REQUEST_METHOD']==='POST' && ($_POST['act']??'')==='del_post'){
  try{
    if(!check_csrf()) throw new Exception('表单过期');
    if(!$can_del) throw new Exception('只能删除自己的帖子');
    $pdo->prepare("DELETE FROM replies WHERE pid=?")->execute([$id]);
    $pdo->prepare("DELETE FROM likes WHERE target_type='post' AND target_id=?")->execute([$id]);
    $pdo->prepare("DELETE FROM posts WHERE id=?")->execute([$id]);
    header('Location: index.php'); exit;
  }catch(Exception $dx){ $msg=$dx->getMessage(); }
}
$pdo->prepare("UPDATE posts SET views=views+1 WHERE id=?")->execute([$id]);
if(!isset($msg)) $msg='';
if($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['act']??'')!=='del_post'){
 try{
  if(!$me) throw new Exception('请先登录再回复');
  if(!ENABLE_FORUM) throw new Exception('社区功能暂未开放');
  if(!check_csrf()) throw new Exception('表单过期');
  $c=trim($_POST['content']??''); if(mb_strlen($c)<2) throw new Exception('回复太短');
  $parentId=(int)($_POST['parent_id']??0);
  if($parentId>0){ // 楼中楼：父回复必须属于本帖
    $pr=$pdo->prepare("SELECT uid FROM replies WHERE id=? AND pid=?"); $pr->execute([$parentId,$id]);
    $parentUid=(int)($pr->fetchColumn()?:0);
    if($parentUid<=0) $parentId=0;
  }else $parentUid=0;
  $pdo->prepare("INSERT INTO replies(pid,uid,content,parent_id) VALUES(?,?,?,?)")->execute([$id,$me['id'],$c,$parentId]);
  $newId=(int)$pdo->lastInsertId();
  // 通知：帖主（非本人）；父回复作者（非本人且非帖主，避免重复）
  $postOwner=(int)$p['uid'];
  if($postOwner!==$me['id']) notify_add($postOwner,'reply',$me['id'],$id,$newId,'回复了你的帖子'.(trim((string)$p['title'])!==''?'《'.$p['title'].'》':''));
  if($parentUid>0 && $parentUid!==$postOwner && $parentUid!==$me['id']) notify_add($parentUid,'reply',$me['id'],$id,$newId,'回复了你的回复');
  header('Location: view.php?id='.$id); exit;
 }catch(Exception $ex){$msg=$ex->getMessage();}
}
// 帖子点赞数与当前用户点赞状态
$plc=$pdo->prepare("SELECT COUNT(*) FROM likes WHERE target_type='post' AND target_id=?"); $plc->execute([$id]); $plc=(int)$plc->fetchColumn();
$postLiked=false;
if($me){ $pl=$pdo->prepare("SELECT 1 FROM likes WHERE target_type='post' AND target_id=? AND uid=?"); $pl->execute([$id,$me['id']]); $postLiked=(bool)$pl->fetchColumn(); }
// 回复列表（含作者身份与每条点赞数）
$rs=$pdo->prepare("SELECT r.*,u.username,u.avatar,u.is_admin,u.is_official,u.is_operator,(SELECT COUNT(*) FROM likes l WHERE l.target_type='reply' AND l.target_id=r.id) lc FROM replies r LEFT JOIN users u ON u.id=r.uid WHERE r.pid=? ORDER BY r.id ASC");$rs->execute([$id]);$list=$rs->fetchAll(PDO::FETCH_ASSOC);
$likedReplySet=[];
if($me && $list){
  $rids=implode(',',array_map(function($x){ return (int)$x['id']; },$list));
  $lr=$pdo->query("SELECT target_id FROM likes WHERE target_type='reply' AND uid=".(int)$me['id']." AND target_id IN ($rids)")->fetchAll(PDO::FETCH_COLUMN);
  $likedReplySet=array_flip(array_map('intval',$lr));
}
// v1.3.0 楼中楼：按 parent_id 建树，顶层顺序楼层
$byId=[]; $top=[];
foreach($list as $r){ $r['children']=[]; $byId[(int)$r['id']]=$r; }
foreach($byId as $k=>$r){ $pid=(int)$r['parent_id']; if($pid>0 && isset($byId[$pid])){ $byId[$pid]['children'][]=$k; } else { $top[]=$k; } }
// 公告帖按钮（announce_btns JSON：[{text,url,local}]）
$announceBtns=[];
if(!empty($p['is_announce']) && !empty($p['announce_btns'])){
  $ab=json_decode((string)$p['announce_btns'],true);
  if(is_array($ab)) foreach($ab as $b) if(is_array($b) && !empty($b['url'])) $announceBtns[]=$b;
}
// v1.3.0 微信/QQ 分享卡片 OG：标题空则取正文前 30 字；og:image=first_image（绝对 URL）
// v1.4：OG 文案用纯文本（剔除图片外链），避免分享卡片里出现一长串链接
$ogText=content_text((string)$p['content']);
$ogTitle=trim((string)$p['title'])!==''?$p['title']:mb_substr($ogText,0,30);
$og=['title'=>$ogTitle,'desc'=>$ogText,'url'=>base_url().'/view.php?id='.$id];
if(!empty($p['first_image'])) $og['image']=$p['first_image'];
$showTitle=trim((string)$p['title'])!==''?$p['title']:'（无标题）';
$page_title=$showTitle; include 'header.php';
?>
<div style="max-width:860px;margin:30px auto 0">
<div class="card">
  <div class="card-h"><span class="post-tag"><?=e($p['board'])?></span><?php if(!empty($p['is_announce'])):?><span class="badge-role r-announce">公告</span><?php endif; ?><span class="r"><?=ico('eye',14)?> <?=$p['views']+1?> 次浏览<?php if($can_del):?> · <form method="post" style="display:inline" onsubmit="return confirm('删除该帖及其全部回复？不可恢复！')"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="act" value="del_post"><button class="link-del" type="submit" style="border:0;background:none;color:var(--danger);font-size:12.5px;font-weight:600;cursor:pointer;padding:0"><?=ico('trash',13)?>删除帖子</button></form><?php endif;?></span></div>
  <div class="card-body">
    <h1 class="detail-title"><?=e($showTitle)?></h1>
    <div class="detail-meta">
      <a href="user.php?uid=<?=$p['uid']?>" style="display:inline-flex;align-items:center;gap:7px"><?=avatar_html(['id'=>(int)$p['uid'],'username'=>(string)$p['username'],'avatar'=>(string)($p['avatar']??'')],22)?> <?=e($p['username']??'同学')?> <?=role_badges($p)?></a>
      <span><?=ico('clock')?> <?=e($p['created_at'])?></span>
      <?php if($p['font_family']||$p['font_size']):?><span><?=ico('font')?> 自定义排版</span><?php endif;?>
    </div>
    <div class="detail-content" style="<?php if($p['font_family']):?>font-family:<?=e($p['font_family'])?>;<?php endif; if($p['font_size']):?>font-size:<?=e($p['font_size'])?>;<?php endif; ?>"><?=content_html($p['content'])?></div>
    <?php if($announceBtns): ?>
    <div class="announce-btns"><?php foreach($announceBtns as $b): $local=!empty($b['local']) && !preg_match('#^https?://#i',(string)$b['url']); ?>
      <a class="btn" href="<?=e($b['url'])?>"<?=$local?'':' target="_blank" rel="noopener"'?>><?=ico('right',14)?><?=e($b['text']??'查看详情')?></a>
    <?php endforeach; ?></div>
    <?php endif; ?>
    <div class="detail-acts">
      <button class="pc-act pc-like<?=$postLiked?' on':''?>" data-type="post" data-id="<?=$id?>" type="button"<?=ENABLE_FORUM?'':' disabled'?>><?=ico('like',15)?><b id="postLikeN"><?=$plc?></b></button>
      <button class="pc-act pc-share" type="button" data-title="<?=e($ogTitle)?>" data-url="<?=e(base_url().'/view.php?id='.$id)?>"><?=ico('share',15)?><b>转发</b></button>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-h"><?=ico('message',16)?>全部回复<span class="r"><?=count($list)?> 条</span></div>
  <?php
  /* 递归渲染一条回复及楼中楼（子回复抽屉默认折叠） */
  $renderReply=function($r,$depth) use (&$renderReply,&$byId,&$likedReplySet,&$me,$id){
    $au=['id'=>(int)$r['uid'],'username'=>(string)($r['username']??''),'avatar'=>(string)($r['avatar']??''),
         'is_admin'=>$r['is_admin']??0,'is_official'=>$r['is_official']??0,'is_operator'=>$r['is_operator']??0];
    $liked=isset($likedReplySet[(int)$r['id']]);
    $kids=$byId[(int)$r['id']]['children'];
  ?>
  <div class="reply-item<?=$depth>0?' rc-deep':''?>" style="<?=$depth>0?'margin-left:'.min($depth*18,54).'px':''?>">
    <div class="reply-main">
      <div class="reply-meta">
        <a class="pc-user" href="user.php?uid=<?=$r['uid']?>"><?=avatar_html($au,26)?><span class="pc-nick"><?=e($r['username']??'同学')?></span><?=role_badges($au)?></a>
        <span class="r-time"><?=e($r['created_at'])?></span>
      </div>
      <div class="reply-content"><?=content_html($r['content'])?></div>
      <div class="reply-acts">
        <?php if(ENABLE_FORUM && $me): ?><button class="ra-btn ra-reply" type="button" data-rid="<?=$r['id']?>" data-name="<?=e($r['username']??'')?>">回复</button><?php endif; ?>
        <?php if(ENABLE_FORUM): ?><button class="ra-btn ra-like pc-like<?=$liked?' on':''?>" data-type="reply" data-id="<?=$r['id']?>" type="button"><?=ico('like',13)?><b><?=(int)$r['lc']?></b></button><?php endif; ?>
      </div>
    </div>
    <?php if($kids): ?>
    <button class="rc-toggle" type="button" data-target="rc<?=$r['id']?>">展开 <?=count($kids)?> 条回复 <i>▾</i></button>
    <div class="reply-children" id="rc<?=$r['id']?>" style="display:none">
      <?php foreach($kids as $ck) $renderReply($byId[$ck],$depth+1); ?>
    </div>
    <?php endif; ?>
  </div>
  <?php };
  $floor=0;
  foreach($top as $tk){ $floor++; echo '<div class="reply-floor"><span class="floor-no">#'.$floor.'</span>'; $renderReply($byId[$tk],0); echo '</div>'; }
  ?>
  <?php if(!$list): ?><div class="empty"><?=ico('message',34)?><br>暂无回复，来抢沙发吧。</div><?php endif; ?>
</div>

<div class="card" id="reply"><div class="card-h"><?=ico('edit',16)?>写下你的回复</div>
<div class="card-body">
  <?php if($msg):?><div class="alert"><?=ico('alert',15)?><?=e($msg)?></div><?php endif;?>
  <?php if($me && ENABLE_FORUM): ?>
  <form method="post" id="replyForm"><input type="hidden" name="csrf" value="<?=csrf_token()?>">
    <input type="hidden" name="parent_id" id="parentId" value="0">
    <div class="replying-to" id="replyingTo" style="display:none">回复 @<b id="replyToName"></b><button type="button" id="cancelReply" class="ra-btn">取消</button></div>
    <textarea class="inp" name="content" rows="4" placeholder="友善回复，文明交流…" required></textarea>
    <button class="btn block"><?=ico('send',15)?>回复</button>
  </form>
  <?php elseif(!ENABLE_FORUM): ?><div class="center muted">社区功能暂未开放回复</div>
  <?php else: ?><div class="center" style="margin-top:0"><a class="btn" href="login.php"><?=ico('user',15)?>登录后回复</a></div><?php endif;?>
</div></div>
</div>
<script>
(function(){
  var logged=window.LOGGED_IN===1;
  /* 点赞（帖子+回复共用 like.php） */
  document.querySelectorAll('.pc-like').forEach(function(btn){
    btn.addEventListener('click',function(){
      if(!logged){ location.href='login.php'; return; }
      var b=btn,n=b.querySelector('b');
      var on=b.classList.contains('on');
      var f=new FormData();
      f.append('act',on?'unlike':'like'); f.append('target_type',b.dataset.type);
      f.append('target_id',b.dataset.id); f.append('csrf','<?=csrf_token()?>');
      fetch('like.php',{method:'POST',body:f}).then(function(r){return r.json();}).then(function(j){
        if(!j.ok){ if(j.msg) alert(j.msg); return; }
        b.classList.toggle('on',!!j.liked);
        if(n) n.textContent=j.count;
      }).catch(function(){});
    });
  });
  /* 转发：navigator.share + 复制兜底 */
  document.querySelectorAll('.pc-share').forEach(function(btn){
    btn.addEventListener('click',function(){
      var url=btn.dataset.url,title=btn.dataset.title||document.title;
      if(navigator.share){ navigator.share({title:title,url:url}).catch(function(){}); return; }
      var done=function(){ var o=btn.innerHTML; btn.innerHTML='<b>已复制</b>'; setTimeout(function(){ btn.innerHTML=o; },1200); };
      if(navigator.clipboard&&navigator.clipboard.writeText){ navigator.clipboard.writeText(url).then(done).catch(function(){}); }
      else{ var i=document.createElement('input'); i.value=url; document.body.appendChild(i); i.select(); document.execCommand('copy'); i.remove(); done(); }
    });
  });
  /* 楼中楼抽屉展开/收起 */
  document.querySelectorAll('.rc-toggle').forEach(function(t){
    t.addEventListener('click',function(){
      var box=document.getElementById(t.dataset.target);
      if(!box) return;
      var open=box.style.display!=='none';
      box.style.display=open?'none':'block';
      t.innerHTML=open?('展开 '+box.querySelectorAll('.reply-item').length+' 条回复 <i>▾</i>'):('收起回复 <i>▴</i>');
    });
  });
  /* 回复某楼：写入 parent_id 并聚焦输入框 */
  var pidInput=document.getElementById('parentId'),rto=document.getElementById('replyingTo'),rtoName=document.getElementById('replyToName');
  document.querySelectorAll('.ra-reply').forEach(function(b){
    b.addEventListener('click',function(){
      if(!pidInput) return;
      pidInput.value=b.dataset.rid; rtoName.textContent=b.dataset.name;
      rto.style.display='block';
      document.getElementById('reply').scrollIntoView({behavior:'smooth'});
      document.getElementById('replyForm').querySelector('textarea').focus();
    });
  });
  var cr=document.getElementById('cancelReply');
  if(cr) cr.addEventListener('click',function(){ pidInput.value='0'; rto.style.display='none'; });
})();
</script>
<?php include 'footer.php'; ?>
