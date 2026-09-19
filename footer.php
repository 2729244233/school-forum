</div>
<!-- v1.3.0 黑色页脚：关于（可配 HTML，白名单 a/br/strong）/ 捐赠 / 友情链接，均后台可配 -->
<footer class="footer-dark">
  <div class="wrap fd-in">
    <div class="fd-col fd-brand">
      <img src="school.png" alt="">
      <div><b><?=e(SITE_NAME)?></b><span>校园交流社区 · 邮箱或第三方登录</span></div>
    </div>
    <div class="fd-col">
      <b>关于</b>
      <div class="fd-about"><?php
        $ab=trim((string)FOOTER_ABOUT);
        echo $ab!=='' ? strip_tags($ab,'<a><br><strong><b>') : e(SITE_SLOGAN_SUB);
      ?></div>
    </div>
    <div class="fd-col">
      <b>链接</b>
      <?php if(trim(FOOTER_DONATE)!==''): ?><a href="<?=e(FOOTER_DONATE)?>" target="_blank" rel="noopener">捐赠支持</a><?php endif; ?>
      <?php foreach((array)FOOTER_LINKS as $fl):
        if(!is_array($fl) || empty($fl['url'])) continue;
        $fu=trim((string)$fl['url']); $ft=trim((string)($fl['text']??'')); if($ft==='') $ft=$fu;
        $ext=preg_match('#^https?://#i',$fu); ?>
      <a href="<?=e($fu)?>"<?=$ext?' target="_blank" rel="noopener"':''?>><?=e($ft)?></a>
      <?php endforeach; ?>
      <a href="admin/">后台管理</a>
    </div>
  </div>
  <div class="fd-bt">© <?=date('Y')?> <?=e(SITE_NAME)?> · 校园论坛系统</div>
</footer>
<!-- v1.4 正文图片点击放大：轻量灯箱，无外部依赖 -->
<div class="img-view" id="imgView" hidden><img src="" alt=""></div>
<script>
(function(){
  var v=document.getElementById('imgView'); if(!v) return;
  var vi=v.querySelector('img');
  document.addEventListener('click',function(e){
    var t=e.target;
    if(t&&t.classList&&t.classList.contains('post-img')){ vi.src=t.src; v.hidden=false; document.body.style.overflow='hidden'; return; }
    if(!v.hidden){ v.hidden=true; vi.src=''; document.body.style.overflow=''; }
  });
  document.addEventListener('keydown',function(e){ if(e.key==='Escape'&&!v.hidden){ v.hidden=true; vi.src=''; document.body.style.overflow=''; } });
})();
</script>
<?php
/* ===== v1.4.1 弹窗公告：chan_popup=1 且 active=1 的最新一条，localStorage 记忆只弹一次 ===== */
$__popup=null;
try{
  $__st=db()->query("SELECT id,title,content,btn_text,btn_url,btn_local FROM announcements WHERE active=1 AND chan_popup=1 ORDER BY id DESC LIMIT 1");
  $__popup=$__st?$__st->fetch(PDO::FETCH_ASSOC):null;
}catch(Exception $__e){ $__popup=null; }
if($__popup):
  $__pBtn=trim((string)$__popup['btn_url']);
  $__pText=trim((string)$__popup['btn_text']); if($__pText==='') $__pText='查看详情';
  $__pExt=$__pBtn!=='' && preg_match('#^https?://#i',$__pBtn);
?>
<div class="pop-mask" id="popAnn" hidden>
  <div class="pop-box" role="dialog" aria-modal="true" aria-labelledby="popAnnTitle">
    <div class="pop-head">
      <span class="pop-badge"><?=ico('bell',15)?>公告</span>
      <button class="pop-close" type="button" aria-label="关闭公告">&times;</button>
    </div>
    <h3 class="pop-title" id="popAnnTitle"><?=e($__popup['title'])?></h3>
    <div class="pop-body"><?=nl2br(e((string)$__popup['content']))?></div>
    <div class="pop-foot">
      <button class="pop-btn ghost" type="button" data-pop-close>我知道了</button>
      <?php if($__pBtn!==''): ?><a class="pop-btn primary" href="<?=e($__pBtn)?>"<?=($__pExt||empty($__popup['btn_local']))?' target="_blank" rel="noopener"':''?>><?=e($__pText)?></a><?php endif; ?>
    </div>
  </div>
</div>
<script>
(function(){
  var m=document.getElementById('popAnn'); if(!m) return;
  var KEY='ann_popup_<?=(int)$__popup['id']?>';
  try{ if(localStorage.getItem(KEY)==='1') return; }catch(e){}
  function close(){
    m.hidden=true; document.body.style.overflow='';
    try{ localStorage.setItem(KEY,'1'); }catch(e){}
  }
  setTimeout(function(){
    m.hidden=false; document.body.style.overflow='hidden';
    var f=m.querySelector('.pop-btn'); if(f) f.focus();
  },600);
  m.addEventListener('click',function(e){ if(e.target===m) close(); });
  m.querySelectorAll('[data-pop-close],.pop-close').forEach(function(b){ b.addEventListener('click',close); });
  document.addEventListener('keydown',function(e){ if(e.key==='Escape'&&!m.hidden) close(); });
})();
</script>
<?php endif; ?>
</body></html>
