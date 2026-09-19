<?php
require_once __DIR__.'/common.php';
$u = current_user();
$cur = basename($_SERVER['PHP_SELF']);
$curBoard = (string)($curBoard ?? ''); // v1.4 板块详情页当前板块（board.php 注入，导航下拉高亮用）
maybe_flush_mail(); // 无 cron：借前台页面流量触发邮件聚合（300s 软锁，见 common.php）
// v1.3.0 微信/QQ 分享卡片 OG meta：页面可用 $og 数组注入（view.php 注入帖子专属信息）
$ogTitle = (string)($og['title'] ?? '');
$ogDesc  = (string)($og['desc'] ?? SITE_SLOGAN_SUB);
$ogImg   = (string)($og['image'] ?? '');
$ogUrl   = (string)($og['url'] ?? (base_url().'/'.$cur));
$flashOk = $_SESSION['flash_ok'] ?? ''; unset($_SESSION['flash_ok']);
?>
<!doctype html><html lang="zh-CN"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=e(SITE_NAME)?><?php if(!empty($page_title)) echo ' - '.e($page_title); ?></title>
<meta property="og:title" content="<?=e($ogTitle!==''?$ogTitle:(!empty($page_title)?$page_title.' - '.SITE_NAME:SITE_NAME))?>">
<meta property="og:description" content="<?=e(mb_substr(preg_replace('/\s+/u',' ',trim($ogDesc)),0,60))?>">
<meta property="og:type" content="website">
<meta property="og:url" content="<?=e($ogUrl)?>">
<?php if($ogImg!==''): ?><meta property="og:image" content="<?=e($ogImg)?>"><?php endif; ?>
<link rel="icon" href="school.png" type="image/png"><link rel="stylesheet" href="assets/style.css?v=<?=@filemtime(__DIR__.'/assets/style.css')?>"></head>
<body>
<!-- v1.3.0 加载动画：资源就绪后淡出；noscript 环境直接隐藏直显内容 -->
<div class="app-loader" id="appLoader"><span class="ld-spin"></span><span class="ld-t"><?=e(SITE_NAME)?> 加载中…</span></div>
<noscript><style>#appLoader{display:none!important}</style></noscript>
<div class="top"><div class="wrap top-in">
<a class="logo" href="index.php"><img src="school.png" alt="<?=e(SITE_NAME)?>"><?=e(SITE_NAME)?></a>
<nav class="nav">
  <a href="index.php" class="<?=$cur=='index.php'?'on':''?>"><?=ico('home',15)?>主页</a>
  <div class="nav-drop" id="navBoard">
    <button class="nav-drop-btn <?=$cur=='board.php'?'on':''?>" type="button"><?=ico('grid',15)?>版块<i>▾</i></button>
    <div class="nav-drop-panel"><?php foreach(boards_all() as $nbd): ?><a href="board.php?b=<?=urlencode($nbd)?>" class="<?=$curBoard===$nbd?'on':''?>"><?=e($nbd)?></a><?php endforeach; ?></div>
  </div>
  <a href="index.php#hot"><?=ico('fire',15)?>热榜</a>
  <a href="user.php" class="<?=$cur=='user.php'?'on':''?>"><?=ico('user',15)?>个人中心</a>
</nav>
<div class="top-act">
<?php if($u): ?>
  <?php if($u && ENABLE_MSG): ?>
  <div class="bell-wrap">
    <button class="bell" id="bellBtn" type="button" aria-label="站内消息"><?=ico('bell',18)?><span class="bell-dot" id="bellDot" style="display:none"></span></button>
    <div class="bell-panel" id="bellPanel" style="display:none">
      <div class="bell-h">站内消息<button type="button" id="bellRead" class="bell-read">全部已读</button></div>
      <div class="bell-list" id="bellList"><div class="bell-empty">暂无消息</div></div>
    </div>
  </div>
  <?php endif; ?>
  <span class="hello">Hi，<?=e($u['username'])?></span>
  <?php if(is_staff($u)): ?>
  <a class="btn small ghost" href="admin/"><?=ico('shield',14)?>管理后台</a>
  <?php else: ?>
  <a class="btn small ghost" href="user.php"><?=ico('dash',14)?>控制台</a>
  <?php endif; ?>
  <a class="btn small ghost" href="logout.php"><?=ico('logout',14)?>退出</a>
<?php else: ?>
  <a class="btn small ghost" href="login.php">登录</a>
  <a class="btn small" href="register.php">注册</a>
<?php endif; ?></div>
</div></div><div class="wrap">
<script>window.LOGGED_IN = <?=$u?1:0?>;window.UPLOAD_MAX_MB=<?=UPLOAD_MAX_MB?>;window.FORUM_ON=<?=ENABLE_FORUM?1:0?>;window.UPLOAD_ON=<?=ENABLE_UPLOAD?1:0?>;</script>
<?php if($flashOk!==''): ?><div class="success" style="margin-top:14px"><?=ico('check',15)?><?=e($flashOk)?></div><?php endif; ?>

<?php if($u && is_social_local((string)$u['email'])): $autoBind = empty($_SESSION['bind_asked']); ?>
<!-- v1.4 社交占位号（@social.local）绑定邮箱弹窗：可跳过；不绑定将不能及时收到站内消息邮件提醒 -->
<div class="modal-mask" id="bindModal"<?=$autoBind?' style="display:flex"':''?>>
  <div class="modal" style="max-width:420px">
    <div class="modal-head">
      <span class="modal-title"><?=ico('mail',17)?>绑定邮箱</span>
      <button class="modal-close" id="bindClose" type="button" aria-label="关闭"><?=ico('close',18)?></button>
    </div>
    <div class="bind-note">你的账号还没有绑定真实邮箱。绑定前只能用展示 ID 或第三方方式登录。如不绑定，将不能及时收到站内消息（点赞 / 回复 / 公告）的邮件提醒，也无法通过邮箱找回密码。</div>
    <input class="inp" id="bindEmail" type="email" placeholder="请输入真实邮箱">
    <div class="code-row"><input class="inp" id="bindCode" placeholder="邮箱验证码"><button type="button" class="btn ghost small" id="bindSend" style="white-space:nowrap"><?=ico('mail',14)?>发送验证码</button></div>
    <button class="btn block" id="bindOk" style="margin-top:10px"><?=ico('link',15)?>确认绑定</button>
    <div class="center"><a href="javascript:void(0)" id="bindSkip">暂不绑定，稍后再说</a></div>
  </div>
</div>
<?php endif; ?>

<?php if(ENABLE_FORUM): ?>
<!-- 右下角发帖按钮 -->
<button class="fab" id="fabOpen" aria-label="发帖" title="发布帖子" type="button"><?=ico('plus',26)?></button>

<!-- 发帖弹窗 -->
<div class="modal-mask" id="postModal">
  <div class="modal">
    <div class="modal-head">
      <span class="modal-title"><?=ico('edit',17)?>发布帖子</span>
      <button class="modal-close" id="modalClose" type="button" aria-label="关闭"><?=ico('close',18)?></button>
    </div>
    <form method="post" action="new.php">
      <input type="hidden" name="csrf" value="<?=csrf_token()?>">
      <select class="inp" name="board"><?php foreach(boards_all() as $bd): ?><option><?=e($bd)?></option><?php endforeach; ?></select>
      <input class="inp" name="title" placeholder="标题（选填，不超过 30 字）" maxlength="30">
      <div class="font-ctrl">
        <label><?=ico('font',14)?>
          <select id="ffSel">
            <option value="">系统默认字体</option>
            <option value="serif">宋体 (serif)</option>
            <option value="'PingFang SC','Hiragino Sans GB','Microsoft YaHei',sans-serif">微软雅黑</option>
            <option value="'KaiTi','STKaiti',serif">楷体 (KaiTi)</option>
            <option value="'FangSong','STFangsong',serif">仿宋 (FangSong)</option>
            <option value="'SimHei',sans-serif">黑体 (SimHei)</option>
            <option value="'STSong',serif">中易宋体</option>
          </select>
        </label>
        <label><?=ico('textsize',14)?>
          <select id="fsSel">
            <option value="">默认字号</option>
            <option value="14px">小 14px</option>
            <option value="16px">正常 16px</option>
            <option value="18px">18px</option>
            <option value="20px">20px</option>
            <option value="22px">22px</option>
            <option value="24px">24px</option>
            <option value="28px">28px</option>
          </select>
        </label>
        <input type="hidden" name="font_family" id="hfFamily">
        <input type="hidden" name="font_size" id="hfSize">
      </div>
      <textarea class="inp" name="content" id="postContent" rows="6" placeholder="分享你的想法…" required
        style="transition:font-size .15s;font-size:16px;line-height:1.8"></textarea>
      <?php if(ENABLE_UPLOAD): ?>
      <div class="up-row">
        <input type="file" id="postImg" accept="image/jpeg,image/png,image/gif,image/webp" style="display:none">
        <button type="button" class="btn ghost small" id="postImgBtn"><?=ico('img',14)?>插入图片</button>
        <span class="up-bar" id="upBar"><i id="upBarIn"></i></span>
        <span class="up-tip" id="upTip">≤ <?=UPLOAD_MAX_MB?>MB · 自动上传图床并插入正文</span>
      </div>
      <div class="up-prev" id="upPrev"></div>
      <?php endif; ?>
      <?=geetest_widget()?>
      <button class="btn block" type="submit"><?=ico('send',15)?>发布</button>
    </form>
  </div>
</div>
<?php endif; ?>

<script>
(function(){
  var logged = window.LOGGED_IN === 1;
  var fab = document.getElementById('fabOpen');
  var mask = document.getElementById('postModal');
  if(fab&&mask){
    var close = document.getElementById('modalClose');
    var ffSel = document.getElementById('ffSel');
    var fsSel = document.getElementById('fsSel');
    var ta = document.getElementById('postContent');
    var hff = document.getElementById('hfFamily');
    var hfs = document.getElementById('hfSize');
    function open(){
      if(!logged){ location.href='login.php'; return; }
      mask.style.display='flex';
      document.body.style.overflow='hidden';
      applyFonts();
    }
    function closeM(){ mask.style.display='none'; document.body.style.overflow=''; }
    function applyFonts(){
      ta.style.fontFamily = ffSel.value||'';
      ta.style.fontSize = fsSel.value||'16px';
    }
    function sync(){ applyFonts(); hff.value = ffSel.value; hfs.value = fsSel.value; }
    fab.addEventListener('click', open);
    close.addEventListener('click', closeM);
    mask.addEventListener('click', function(e){ if(e.target===mask) closeM(); });
    document.addEventListener('keydown', function(e){ if(e.key==='Escape') closeM(); });
    ffSel.addEventListener('change', sync);
    fsSel.addEventListener('change', sync);
    window.openPost = open;
    window.closePost = closeM;
  }
  /* v1.3.0 发帖弹窗图片上传：XHR 带进度条，成功后把图床 URL 插入正文 */
  var imgBtn=document.getElementById('postImgBtn');
  if(imgBtn){
    var imgIn=document.getElementById('postImg'), bar=document.getElementById('upBar'),
        barIn=document.getElementById('upBarIn'), tip=document.getElementById('upTip'),
        prev=document.getElementById('upPrev');
    function showPrev(url){
      if(!prev||!url) return;
      var im=document.createElement('img');
      im.src=url; im.alt=''; im.title='已插入正文，发布后展示';
      im.onerror=function(){ im.remove(); };
      prev.appendChild(im);
    }
    imgBtn.addEventListener('click',function(){
      if(!logged){ location.href='login.php'; return; }
      imgIn.click();
    });
    imgIn.addEventListener('change',function(){
      var f=imgIn.files[0]; if(!f) return;
      if(f.size>window.UPLOAD_MAX_MB*1048576){ tip.textContent='图片不能超过 '+window.UPLOAD_MAX_MB+'MB'; imgIn.value=''; return; }
      var fd=new FormData(); fd.append('file',f); fd.append('csrf','<?=csrf_token()?>');
      bar.style.display='inline-block'; barIn.style.width='0%';
      var xhr=new XMLHttpRequest();
      xhr.upload.onprogress=function(e){ if(e.lengthComputable){ var p=Math.round(e.loaded/e.total*100); barIn.style.width=p+'%'; tip.textContent='上传中 '+p+'%'; } };
      xhr.onload=function(){
        bar.style.display='none'; imgIn.value='';
        try{
          var j=JSON.parse(xhr.responseText);
          if(j.ok){ ta.value+=(ta.value?'\n':'')+j.url; tip.textContent='图片已插入正文，发布后展示'; showPrev(j.url); }
          else tip.textContent=j.msg||'上传失败';
        }catch(ex){ tip.textContent='上传失败'; }
      };
      xhr.onerror=function(){ bar.style.display='none'; imgIn.value=''; tip.textContent='上传失败，请重试'; };
      xhr.open('POST','upload.php'); xhr.send(fd);
    });
  }
  /* v1.3.0 站内消息铃铛：定时轮询 notify.php，失败静默 */
  var bellBtn=document.getElementById('bellBtn');
  if(bellBtn){
    var panel=document.getElementById('bellPanel'),dot=document.getElementById('bellDot'),
        list=document.getElementById('bellList'),readBtn=document.getElementById('bellRead');
    var esc=function(s){ var d=document.createElement('i'); d.textContent=s==null?'':String(s); return d.innerHTML; };
    function render(j){
      if(!j||!j.ok||j.enabled===0){ panel.style.display='none'; return; }
      dot.style.display=j.unread>0?'block':'none';
      if(!j.items||!j.items.length){ list.innerHTML='<div class="bell-empty">暂无消息</div>'; return; }
      var h='';
      j.items.forEach(function(n){
        var tag=n.type==='like'?'赞':(n.type==='reply'?'回复':'公告');
        var href=n.post_id>0?('view.php?id='+n.post_id):'index.php';
        h+='<a class="bell-item'+(n.read?'':' unread')+'" href="'+href+'">'
          +'<b class="bell-tag t-'+esc(n.type)+'">'+tag+'</b>'
          +'<span class="bell-txt">'+esc(n.text)+'</span><i class="bell-time">'+esc(n.time)+'</i></a>';
      });
      list.innerHTML=h;
    }
    function poll(){ fetch('notify.php?act=poll',{cache:'no-store'}).then(function(r){return r.json();}).then(render).catch(function(){}); }
    function markRead(){ var f=new FormData(); f.append('act','read'); f.append('csrf','<?=csrf_token()?>');
      fetch('notify.php',{method:'POST',body:f}).then(function(r){return r.json();}).then(function(j){ if(j.ok){ dot.style.display='none'; var us=list.querySelectorAll('.bell-item.unread'); for(var i=0;i<us.length;i++) us[i].classList.remove('unread'); } }).catch(function(){}); }
    bellBtn.addEventListener('click',function(e){
      e.stopPropagation();
      var show=panel.style.display==='none'||panel.style.display==='';
      panel.style.display=show?'block':'none';
      if(show){ poll(); if(dot.style.display!=='none') markRead(); }
    });
    readBtn.addEventListener('click',function(e){ e.stopPropagation(); markRead(); });
    document.addEventListener('click',function(e){ if(panel.style.display==='block'&&!panel.contains(e.target)&&e.target!==bellBtn) panel.style.display='none'; });
    poll();
    setInterval(poll,<?=NOTIFY_POLL_SECS?>*1000);
  }
})();
/* v1.4 导航板块下拉：点击展开/收起，点外部关闭 */
var nb=document.getElementById('navBoard');
if(nb){
  var nbBtn=nb.querySelector('.nav-drop-btn');
  nbBtn.addEventListener('click',function(e){ e.stopPropagation(); nb.classList.toggle('open'); });
  document.addEventListener('click',function(e){ if(!nb.contains(e.target)) nb.classList.remove('open'); });
}
/* v1.4 绑定邮箱弹窗（社交占位号）：发送验证码/提交绑定/跳过；window.openBind() 供个人中心调用 */
var bm=document.getElementById('bindModal');
if(bm){
  var bE=document.getElementById('bindEmail'),bC=document.getElementById('bindCode'),
      bS=document.getElementById('bindSend'),bO=document.getElementById('bindOk'),
      bX=document.getElementById('bindClose'),bK=document.getElementById('bindSkip');
  var bCs='<?=csrf_token()?>';
  window.openBind=function(){ bm.style.display='flex'; document.body.style.overflow='hidden'; if(bE) bE.focus(); };
  var bSkipReq=function(){ var f=new FormData(); f.append('act','skip'); f.append('csrf',bCs); fetch('bind_email.php',{method:'POST',body:f}).catch(function(){}); };
  bK.addEventListener('click',function(){ bSkipReq(); bm.style.display='none'; document.body.style.overflow=''; });
  bX.addEventListener('click',function(){ bSkipReq(); bm.style.display='none'; document.body.style.overflow=''; });
  bm.addEventListener('click',function(e){ if(e.target===bm){ bSkipReq(); bm.style.display='none'; document.body.style.overflow=''; } });
  bS.addEventListener('click',function(){
    var em=bE.value; if(!em){ alert('请先填写邮箱'); return; }
    bS.disabled=true; bS.textContent='发送中...';
    var f=new FormData(); f.append('act','send'); f.append('email',em); f.append('csrf',bCs);
    fetch('bind_email.php',{method:'POST',body:f}).then(function(r){return r.json();}).then(function(j){
      alert(j.msg+(j.debug_code?'（测试码：'+j.debug_code+'）':''));
      if(j.ok){ var s=60; bS.textContent='重新发送(60s)'; var t=setInterval(function(){ s--; bS.textContent='重新发送('+s+'s)'; if(s<=0){ clearInterval(t); bS.disabled=false; bS.innerHTML='发送验证码'; } },1000); }
      else{ bS.disabled=false; bS.innerHTML='发送验证码'; }
    }).catch(function(){ bS.disabled=false; bS.innerHTML='发送验证码'; });
  });
  bO.addEventListener('click',function(){
    var em=bE.value,c=bC.value; if(!em||!c){ alert('请填写邮箱和验证码'); return; }
    bO.disabled=true; bO.textContent='提交中...';
    var f=new FormData(); f.append('act','bind'); f.append('email',em); f.append('code',c); f.append('csrf',bCs);
    fetch('bind_email.php',{method:'POST',body:f}).then(function(r){return r.json();}).then(function(j){
      if(j.ok){ alert('邮箱绑定成功！'); location.reload(); }
      else{ alert(j.msg||'绑定失败'); bO.disabled=false; bO.textContent='确认绑定'; }
    }).catch(function(){ bO.disabled=false; bO.textContent='确认绑定'; });
  });
  if(bm.style.display==='flex') document.body.style.overflow='hidden';
}
/* v1.3.0 加载动画：window.load + fonts.ready 后淡出移除（最长 1.2s 兜底） */
window.addEventListener('load',function(){
  var done=false;
  var fin=function(){ if(done) return; done=true; var l=document.getElementById('appLoader'); if(l){ l.classList.add('ld-out'); setTimeout(function(){ l.remove(); },380); } };
  if(document.fonts&&document.fonts.ready){ document.fonts.ready.then(fin); setTimeout(fin,1200); } else fin();
});
</script>
