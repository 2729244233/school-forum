<?php
require_once __DIR__.'/common.php';
$pdo=db();
$me=current_user();
$boards=boards_all(); // v1.4：板块入口（导航下拉 + 底部引导卡）
// v1.4：主页只展示最新 6 条（全部帖子按板块浏览：导航「版块」下拉 → board.php）
$posts=$pdo->query("SELECT p.*,u.username,u.avatar,u.is_admin,u.is_official,u.is_operator,(SELECT COUNT(*) FROM replies r WHERE r.pid=p.id) rc,(SELECT COUNT(*) FROM likes l WHERE l.target_type='post' AND l.target_id=p.id) lc FROM posts p LEFT JOIN users u ON u.id=p.uid ORDER BY p.id DESC LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);
$totalPosts=(int)$pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
// 当前用户对这批帖子的点赞状态（用于卡片点赞按钮高亮）
$likedSet=[];
if($me && $posts){
  $ids=implode(',',array_map(function($x){ return (int)$x['id']; },$posts));
  $ls=$pdo->query("SELECT target_id FROM likes WHERE target_type='post' AND uid=".(int)$me['id']." AND target_id IN ($ids)")->fetchAll(PDO::FETCH_COLUMN);
  $likedSet=array_flip(array_map('intval',$ls));
}
// v1.3.0 双热榜：帖子浏览 TOP10 + 作者发帖数 TOP10
$hot=$pdo->query("SELECT id,board,title,views FROM posts ORDER BY views DESC, id DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
$hotUsers=$pdo->query("SELECT u.id,u.username,u.avatar,u.is_admin,u.is_official,u.is_operator,COUNT(*) pc FROM posts p JOIN users u ON u.id=p.uid GROUP BY u.id,u.username,u.avatar,u.is_admin,u.is_official,u.is_operator ORDER BY pc DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
$page_title='主页'; include __DIR__.'/header.php';
?>
<!-- v1.3.0 PC 立绘：≥1200px 显示，图片缺失时无痕隐藏 -->
<figure class="pc-fig l" aria-hidden="true"><img src="assets/fig-left.png" alt="" onerror="this.parentNode.style.display='none'"></figure>
<figure class="pc-fig r" aria-hidden="true"><img src="assets/fig-right.png" alt="" onerror="this.parentNode.style.display='none'"></figure>

<div class="home-head">
  <span class="badge"><?=ico('fire',13)?>实时校园动态</span>
  <p class="home-slogan"><?=e(SITE_SLOGAN_SUB)?></p>
  <div class="hero-btns">
    <?php if(ENABLE_FORUM): ?><button class="btn" type="button" onclick="window.openPost&&window.openPost()"><?=ico('edit',15)?>前往发帖</button>
    <?php else: ?><span class="btn" style="opacity:.6;cursor:not-allowed">社区暂未开放发帖</span><?php endif; ?>
    <a class="btn ghost" href="#hot"><?=ico('fire',15)?>查看热榜</a>
  </div>
</div>

<div class="card">
  <div class="card-h"><?=ico('file',16)?>最新动态<span class="r">共 <?=$totalPosts?> 帖 · 从上方「版块」菜单浏览全部</span></div>
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
        <button class="pc-act pc-share" type="button" data-title="<?=e($p['title']!==''?$p['title']:mb_substr(content_text((string)$p['content']),0,30))?>" data-url="<?=e(base_url().'/view.php?id='.$p['id'])?>"><?=ico('share',15)?><b>转发</b></button>
      </div>
    </div>
  </article>
  <?php endforeach; ?>
  <?php if(!$posts): ?><div class="empty"><?=ico('file',34)?><br>暂无帖子，点击右下角 + 发布第一条吧。</div><?php endif; ?>
</div>

<!-- v1.4 板块入口：主页不再展示全部帖子，按板块浏览 -->
<div class="card board-jump">
  <div class="card-h"><?=ico('grid',16)?>选择版块进入浏览<span class="r">也可以用顶部「版块」菜单</span></div>
  <div class="bj-in">
    <?php foreach($boards as $b): ?>
    <a class="chip" href="board.php?b=<?=urlencode($b)?>"><?=ico('grid',13)?><?=e($b)?></a>
    <?php endforeach; ?>
  </div>
</div>

<div class="hot-grid" id="hot">
  <div class="card">
    <div class="card-h"><?=ico('fire',16)?>帖子热榜<span class="r">按浏览排序</span></div>
    <?php foreach($hot as $i=>$p): ?>
    <a class="hot-item" href="view.php?id=<?=$p['id']?>">
      <span class="hot-rank"><?=$i+1?></span>
      <span class="hot-main">
        <span class="hot-title"><?=e($p['title']!==''?$p['title']:mb_substr($p['board'].' · '.strip_tags($p['content']),0,24))?></span>
        <span class="hot-sub" style="display:block"><?=e($p['board'])?> · <?=$p['views']?> 热度</span>
      </span>
    </a>
    <?php endforeach; ?>
    <?php if(!$hot): ?><div class="empty">暂无内容</div><?php endif; ?>
  </div>
  <div class="card">
    <div class="card-h"><?=ico('users',16)?>作者榜<span class="r">按发帖数</span></div>
    <?php foreach($hotUsers as $i=>$hu): ?>
    <a class="hot-item" href="user.php?uid=<?=$hu['id']?>">
      <span class="hot-rank"><?=$i+1?></span>
      <?=avatar_html($hu,30)?>
      <span class="hot-main">
        <span class="hot-title"><?=e($hu['username'])?> <?=role_badges($hu)?></span>
        <span class="hot-sub" style="display:block">发帖 <?=$hu['pc']?> 篇</span>
      </span>
    </a>
    <?php endforeach; ?>
    <?php if(!$hotUsers): ?><div class="empty">暂无内容</div><?php endif; ?>
  </div>
</div>
<script>
(function(){
  var logged=window.LOGGED_IN===1;
  /* 卡片点赞：like.php 幂等，未登录跳登录页 */
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
  /* 转发：优先系统分享面板，失败回退复制链接 */
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
