<?php
// v1.4 板块详情页：仅展示该板块全部帖子（无热榜/无顶部标语），支持排序（时间/热度）与时间段筛选 + 分页
require_once __DIR__.'/common.php';
$pdo=db();
$me=current_user();
$boards=boards_all();
$board=trim($_GET['b']??'');
if($board==='' || !in_array($board,$boards,true)){ header('Location: index.php'); exit; } // 板块白名单校验
// 排序：time=最新发布 / hot=热度（点赞*3 + 回复*2 + 浏览）
$sort=($_GET['sort']??'')==='hot'?'hot':'time';
// 时间段：all=全部 / 1=今天 / 7=近7天 / 30=近30天（posts.created_at 为 DATETIME，字符串比较双驱动通用）
$range=$_GET['range']??'all';
if(!in_array($range,['all','1','7','30'],true)) $range='all';
$where=" WHERE p.board=? "; $args=[$board];
if($range!=='all'){
  $where.=" AND p.created_at>=? ";
  $args[]=date('Y-m-d H:i:s',time()-(int)$range*86400);
}
$order=$sort==='hot'?" (lc*3+rc*2+p.views) DESC, p.id DESC":" p.id DESC";
// 分页
$total=$pdo->prepare("SELECT COUNT(*) FROM posts p $where"); $total->execute($args); $total=(int)$total->fetchColumn();
$page=max(1,(int)($_GET['page']??1)); $per=15; $pages=max(1,(int)ceil($total/$per)); $page=min($page,$pages);
$off=($page-1)*$per;
$sql="SELECT p.*,u.username,u.avatar,u.is_admin,u.is_official,u.is_operator,(SELECT COUNT(*) FROM replies r WHERE r.pid=p.id) rc,(SELECT COUNT(*) FROM likes l WHERE l.target_type='post' AND l.target_id=p.id) lc FROM posts p LEFT JOIN users u ON u.id=p.uid $where ORDER BY $order LIMIT $per OFFSET $off";
$s=$pdo->prepare($sql); $s->execute($args); $posts=$s->fetchAll(PDO::FETCH_ASSOC);
// 当前用户对这批帖子的点赞状态
$likedSet=[];
if($me && $posts){
  $ids=implode(',',array_map(function($x){ return (int)$x['id']; },$posts));
  $ls=$pdo->query("SELECT target_id FROM likes WHERE target_type='post' AND uid=".(int)$me['id']." AND target_id IN ($ids)")->fetchAll(PDO::FETCH_COLUMN);
  $likedSet=array_flip(array_map('intval',$ls));
}
// 排序/时间段切换链接（互传对方当前值）
$mkLink=function($s2,$r2,$p2=1) use ($board){ return 'board.php?b='.urlencode($board).'&sort='.$s2.'&range='.$r2.($p2>1?'&page='.$p2:''); };
$curBoard=$board;
$page_title=$board; include __DIR__.'/header.php';
?>
<div class="board-bar">
  <h1><?=ico('grid',20)?><?=e($board)?><span class="bb-count"><?=$total?> 帖</span></h1>
  <div class="board-tools">
    <span class="seg-label">排序</span>
    <div class="seg">
      <a href="<?=$mkLink('time',$range)?>" class="<?=$sort==='time'?'on':''?>">最新</a>
      <a href="<?=$mkLink('hot',$range)?>" class="<?=$sort==='hot'?'on':''?>">最热</a>
    </div>
    <span class="seg-label">时间段</span>
    <div class="seg">
      <a href="<?=$mkLink($sort,'1')?>" class="<?=$range==='1'?'on':''?>">今天</a>
      <a href="<?=$mkLink($sort,'7')?>" class="<?=$range==='7'?'on':''?>">近7天</a>
      <a href="<?=$mkLink($sort,'30')?>" class="<?=$range==='30'?'on':''?>">近30天</a>
      <a href="<?=$mkLink($sort,'all')?>" class="<?=$range==='all'?'on':''?>">全部</a>
    </div>
  </div>
</div>

<?php foreach($posts as $p):
  $au=['id'=>(int)$p['uid'],'username'=>(string)$p['username'],'avatar'=>(string)($p['avatar']??''),
       'is_admin'=>$p['is_admin']??0,'is_official'=>$p['is_official']??0,'is_operator'=>$p['is_operator']??0];
  $liked=isset($likedSet[(int)$p['id']]);
  // v1.4：摘要去掉图片外链（改由缩略图展示），标题前缀从正文剔除
  $excerpt=content_text(trim((string)$p['title'])===''?(string)$p['content']:preg_replace('/^'.preg_quote((string)$p['title'],'/').'/u','',(string)$p['content']));
  $thumb=first_image_of((string)$p['content']);
?>
<article class="post-card">
  <div class="pc-head">
    <a class="pc-user" href="user.php?uid=<?=$p['uid']?>"><?=avatar_html($au,34)?>
      <span class="pc-nick"><?=e($p['username']??'同学')?></span><?=role_badges($au)?></a>
    <span class="pc-side"><?php if(!empty($p['is_announce'])):?><span class="badge-role r-announce">公告</span><?php endif; ?><span class="post-tag"><?=e($p['board'])?></span></span>
  </div>
  <a class="pc-body" href="view.php?id=<?=$p['id']?>">
    <?php if(trim((string)$p['title'])!==''): ?><div class="pc-title"><?=e($p['title'])?></div><?php endif; ?>
    <div class="pc-excerpt"><?=e($excerpt)?></div>
    <?php if($thumb!==''): ?><img class="pc-thumb" src="<?=e($thumb)?>" alt="" loading="lazy" onerror="this.style.display='none'"><?php endif; ?>
  </a>
  <div class="pc-foot">
    <span class="pc-time"><?=ico('clock',13)?><?=e($p['created_at'])?></span>
    <div class="pc-acts">
      <button class="pc-act pc-like<?=$liked?' on':''?>" data-type="post" data-id="<?=$p['id']?>" type="button"<?=ENABLE_FORUM?'':' disabled'?>><?=ico('like',15)?><b><?=(int)$p['lc']?></b></button>
      <a class="pc-act" href="view.php?id=<?=$p['id']?>#reply"><?=ico('message',15)?><b><?=(int)$p['rc']?></b></a>
      <span class="pc-act" title="浏览量"><?=ico('eye',15)?><b><?=(int)$p['views']?></b></span>
      <button class="pc-act pc-share" type="button" data-title="<?=e($p['title']!==''?$p['title']:mb_substr(content_text((string)$p['content']),0,30))?>" data-url="<?=e(base_url().'/view.php?id='.$p['id'])?>"><?=ico('share',15)?><b>转发</b></button>
    </div>
  </div>
</article>
<?php endforeach; ?>
<?php if(!$posts): ?><div class="card"><div class="empty"><?=ico('file',34)?><br>该板块暂无帖子<?=($range!=='all')?'，试试切换到更长的时间段':'';?>。</div></div><?php endif; ?>

<?php if($pages>1): ?>
<div class="pg">
  <?php if($page>1): ?><a href="<?=$mkLink($sort,$range,$page-1)?>">上一页</a><?php endif; ?>
  <?php for($i=1;$i<=$pages;$i++): if($pages>9 && $i>2 && $i<$pages-1 && abs($i-$page)>2){ if($i===3) echo '<span>…</span>'; continue; } ?>
  <?php if($i===$page): ?><span class="cur"><?=$i?></span><?php else: ?><a href="<?=$mkLink($sort,$range,$i)?>"><?=$i?></a><?php endif; ?>
  <?php endfor; ?>
  <?php if($page<$pages): ?><a href="<?=$mkLink($sort,$range,$page+1)?>">下一页</a><?php endif; ?>
  <span>第 <?=$page?>/<?=$pages?> 页</span>
</div>
<?php endif; ?>

<script>
(function(){
  var logged=window.LOGGED_IN===1;
  /* 卡片点赞（与 index.php 同逻辑） */
  document.querySelectorAll('.pc-like').forEach(function(btn){
    btn.addEventListener('click',function(){
      if(!logged){ location.href='login.php'; return; }
      var b=btn, n=b.querySelector('b');
      var on=b.classList.contains('on');
      var f=new FormData();
      f.append('act',on?'unlike':'like'); f.append('target_type',b.dataset.type);
      f.append('target_id',b.dataset.id); f.append('csrf','<?=csrf_token()?>');
      b.classList.add('busy');
      fetch('like.php',{method:'POST',body:f}).then(function(r){return r.json();}).then(function(j){
        b.classList.remove('busy');
        if(!j.ok){ if(j.msg) alert(j.msg); return; }
        b.classList.toggle('on',!!j.liked);
        n.textContent=j.count;
      }).catch(function(){ b.classList.remove('busy'); });
    });
  });
  /* 转发 */
  document.querySelectorAll('.pc-share').forEach(function(btn){
    btn.addEventListener('click',function(){
      var url=btn.dataset.url,title=btn.dataset.title||document.title;
      if(navigator.share){ navigator.share({title:title,url:url}).catch(function(){}); return; }
      var done=function(){ var o=btn.innerHTML; btn.innerHTML='<b>已复制</b>'; setTimeout(function(){ btn.innerHTML=o; },1200); };
      if(navigator.clipboard&&navigator.clipboard.writeText){ navigator.clipboard.writeText(url).then(done).catch(function(){}); }
      else{ var i=document.createElement('input'); i.value=url; document.body.appendChild(i); i.select(); document.execCommand('copy'); i.remove(); done(); }
    });
  });
})();
</script>
<?php include __DIR__.'/footer.php'; ?>
