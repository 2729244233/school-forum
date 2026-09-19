<?php
require_once __DIR__.'/../common.php';
$ADMIN=require_any('admin','official','operator'); // 三身份均可管理公告
$pdo=db(); $msg='';
/* v1.3.0：公告帖 = posts.is_announce=1 的特殊帖子，announce_btns 存按钮 JSON [{text,url,local}] */
function mk_announce_post($pdo,$ADMIN,$title,$content,$btns){
  $pdo->prepare("INSERT INTO posts(uid,board,title,content,is_announce,announce_btns) VALUES(?,?,?,?,1,?)")
      ->execute([(int)$ADMIN['id'],'公告',(string)$title,(string)$content,json_encode($btns,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]);
}
/* v1.4.1：向全部启用用户推送站内 system 通知
   mailed=2 免即时群发邮件，交由前台流量的聚合摘要发送，避免主机超时 */
function push_to_all($pdo,$adminId,$title,$content){
  $ncontent=trim((string)$title);
  $plain=trim(preg_replace('/\s+/u',' ',trim(strip_tags((string)$content))));
  if($plain!=='') $ncontent.='：'.mb_substr($plain,0,50);
  $users=$pdo->query("SELECT id FROM users WHERE status=1")->fetchAll(PDO::FETCH_COLUMN);
  $now=time(); $sent=0;
  foreach(array_chunk($users,200) as $ch){
    $vals=[]; $args=[];
    foreach($ch as $x){ $vals[]='(?,?,?,?,?,?,0,2,?)'; array_push($args,(int)$x,'system',(int)$adminId,0,0,$ncontent,$now); }
    $pdo->prepare("INSERT INTO notifications(uid,type,actor_id,post_id,reply_id,content,is_read,mailed,created_at) VALUES".implode(',',$vals))->execute($args);
    $sent+=count($ch);
  }
  return $sent;
}
if($_SERVER['REQUEST_METHOD']==='POST'){
  try{
    if(!check_csrf()) throw new Exception('表单过期，请重试');
    $act=$_POST['act']??''; $id=intval($_POST['id']??0);
    if($act==='save'){
      $title=trim($_POST['title']??''); $content=trim($_POST['content']??'');
      $btnText=trim($_POST['btn_text']??''); $btnUrl=trim($_POST['btn_url']??''); $btnLocal=isset($_POST['btn_local'])?1:0;
      // v1.4.1：投放渠道多选（帖子 / 推送 / 弹窗，可任意组合）
      $chanPost=isset($_POST['chan_post'])?1:0;
      $chanPush=isset($_POST['chan_push'])?1:0;
      $chanPopup=isset($_POST['chan_popup'])?1:0;
      if($title==='') throw new Exception('请填写公告标题');
      if(mb_strlen($title)>60) throw new Exception('标题不能超过 60 字');
      if(!$chanPost && !$chanPush && !$chanPopup) throw new Exception('请至少选择一种投放渠道（帖子 / 推送 / 弹窗）');
      if($chanPost && $chanPopup) $chanPopup=1; // 允许共存
      if($chanPost && $btnText==='' ) $btnText=$btnUrl!==''?'查看详情':'';
      if($btnText!=='' && $btnUrl==='') throw new Exception('填写了按钮文字但未填写跳转地址');
      if($btnUrl!=='' && $btnText==='') $btnText='查看详情';
      $btns=$btnUrl!==''?[['text'=>$btnText,'url'=>$btnUrl,'local'=>$btnLocal]]:[];
      // 弹窗渠道不使用帖子按钮，帖子渠道才带按钮
      $postBtns=$chanPost?$btns:[];
      if($id>0){ // 编辑
        $st=$pdo->prepare("SELECT id FROM announcements WHERE id=?"); $st->execute([$id]);
        if(!$st->fetchColumn()) throw new Exception('公告不存在');
        $pdo->prepare("UPDATE announcements SET title=?,content=?,btn_text=?,btn_url=?,btn_local=?,chan_post=?,chan_push=?,chan_popup=? WHERE id=?")
            ->execute([$title,$content,$btnText,$btnUrl,$btnLocal,$chanPost,$chanPush,$chanPopup,$id]);
        $_SESSION['flash']='公告已更新';
      }else{ // 新建
        $pdo->prepare("INSERT INTO announcements(title,content,btn_text,btn_url,btn_local,active,chan_post,chan_push,chan_popup,created_at) VALUES(?,?,?,?,?,1,?,?,?,?)")
            ->execute([$title,$content,$btnText,$btnUrl,$btnLocal,$chanPost,$chanPush,$chanPopup,time()]);
        $_SESSION['flash']='公告已创建';
      }
      // 帖子渠道 → 同步生成一篇公告帖
      if($chanPost) mk_announce_post($pdo,$ADMIN,$title,$content,$postBtns);
    }elseif($act==='toggle'){
      if($id<=0) throw new Exception('非法操作');
      $pdo->prepare("UPDATE announcements SET active=1-active WHERE id=?")->execute([$id]);
      $_SESSION['flash']='公告展示状态已切换';
    }elseif($act==='del'){
      if($id<=0) throw new Exception('非法操作');
      $pdo->prepare("DELETE FROM announcements WHERE id=?")->execute([$id]);
      $_SESSION['flash']='公告已删除';
    }elseif($act==='push'){
      if($id<=0) throw new Exception('非法操作');
      $st=$pdo->prepare("SELECT * FROM announcements WHERE id=?"); $st->execute([$id]); $a=$st->fetch(PDO::FETCH_ASSOC);
      if(!$a) throw new Exception('公告不存在');
      $sent=push_to_all($pdo,$ADMIN['id'],$a['title'],$a['content']);
      $_SESSION['flash']='已向 '.$sent.' 位用户推送站内公告（邮件将按聚合策略发送）';
    }else throw new Exception('非法操作');
  }catch(Exception $ex){ $_SESSION['flash']=$ex->getMessage(); }
  header('Location: announcements.php'); exit;
}
$editId=intval($_GET['edit']??0); $editRow=null;
if($editId>0){ $st=$pdo->prepare("SELECT * FROM announcements WHERE id=?"); $st->execute([$editId]); $editRow=$st->fetch(PDO::FETCH_ASSOC); }
$list=$pdo->query("SELECT * FROM announcements ORDER BY id DESC LIMIT 100")->fetchAll(PDO::FETCH_ASSOC);
$page='ann'; $page_title='公告管理'; include __DIR__.'/head.php';
?>
<!-- ===== 新建/编辑公告 ===== -->
<form method="post"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="act" value="save"><input type="hidden" name="id" value="<?=$editRow?(int)$editRow['id']:0?>">
<div class="panel">
  <div class="panel-title"><?=ico('send',17)?><?=$editRow?'编辑公告 #'.$editRow['id']:'新建公告'?></div>
  <div class="panel-desc">一篇公告可同时选择多种投放渠道，任选其一或组合使用。弹窗公告在前台以弹窗展示（localStorage 记忆，同一公告只弹一次）。</div>
  <div class="fg"><label>投放渠道</label><div class="field">
    <label class="sw"><input type="checkbox" name="chan_post" value="1" <?=(!$editRow||!empty($editRow['chan_post']))?'checked':''?>><span class="track"></span><span class="sw-txt"><b>帖子</b><i>在「公告」版块生成一篇公告帖，可带跳转按钮</i></span></label>
    <label class="sw"><input type="checkbox" name="chan_push" value="1" <?=!empty($editRow['chan_push'])?'checked':''?>><span class="track"></span><span class="sw-txt"><b>推送</b><i>创建后立即向全部用户发送站内通知（邮件按聚合策略发送）</i></span></label>
    <label class="sw"><input type="checkbox" name="chan_popup" value="1" <?=!empty($editRow['chan_popup'])?'checked':''?>><span class="track"></span><span class="sw-txt"><b>弹窗</b><i>前台打开页面时弹出公告，同一用户只弹一次</i></span></label>
  </div></div>
  <div class="fg"><label>公告标题</label><div class="field"><input type="text" name="title" value="<?=e($editRow['title']??'')?>" maxlength="60" required></div></div>
  <div class="fg"><label>公告内容</label><div class="field"><textarea name="content" rows="4" style="width:100%;border:1.5px solid var(--border);border-radius:10px;padding:10px 12px;font-family:inherit;font-size:13.5px"><?=e($editRow['content']??'')?></textarea></div></div>
  <div class="fg"><label>按钮文字（选填）</label><div class="field"><input type="text" name="btn_text" value="<?=e($editRow['btn_text']??'')?>" placeholder="如：查看详情"><div class="hint">仅「帖子」渠道的公告帖会显示按钮</div></div></div>
  <div class="fg"><label>按钮跳转地址（选填）</label><div class="field"><input type="text" name="btn_url" value="<?=e($editRow['btn_url']??'')?>" placeholder="https://... 或本站相对地址 view.php?id=1"><div class="hint">外部地址将新窗口打开；勾选「本站链接」则当前窗口跳转</div></div></div>
  <div class="fg"><label>选项</label><div class="field">
    <label class="sw"><input type="checkbox" name="btn_local" value="1" <?=!empty($editRow['btn_local'])?'checked':''?>><span class="track"></span><span class="sw-txt"><b>按钮为本站链接</b><i>在当前窗口跳转，而非新开标签页</i></span></label>
  </div></div>
  <div class="save-bar"><button class="btn primary" type="submit"><?=$editRow?'保存修改':'创建公告'?></button>
  <?php if($editRow): ?><a class="btn default" href="announcements.php">取消编辑</a><?php endif; ?></div>
</div></form>

<!-- ===== 公告列表 ===== -->
<div class="panel">
  <div class="panel-title"><?=ico('file',17)?>公告列表</div>
  <div class="table-wrap"><table class="tb"><tr><th style="width:50px">ID</th><th>标题</th><th>投放渠道</th><th>状态</th><th>创建时间</th><th style="width:250px">操作</th></tr>
  <?php foreach($list as $a): ?>
  <tr><td>#<?=$a['id']?></td>
    <td><strong><?=e($a['title'])?></strong><div style="color:var(--muted);font-size:12px;max-width:320px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?=e(mb_substr(strip_tags((string)$a['content']),0,40))?></div></td>
    <td>
      <?php if(!empty($a['chan_post'])): ?><span class="tag blue">帖子</span> <?php endif; ?>
      <?php if(!empty($a['chan_push'])): ?><span class="tag purple">推送</span> <?php endif; ?>
      <?php if(!empty($a['chan_popup'])): ?><span class="tag ok">弹窗</span> <?php endif; ?>
      <?php if(empty($a['chan_post'])&&empty($a['chan_push'])&&empty($a['chan_popup'])): ?><span class="tag default">仅存档</span><?php endif; ?>
    </td>
    <td><?=$a['active']?'<span class="tag ok">展示中</span>':'<span class="tag default">已下架</span>'?></td>
    <td style="font-size:12px;color:var(--muted)"><?=date('Y-m-d H:i',(int)$a['created_at'])?></td>
    <td>
      <form method="post" style="display:inline"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="act" value="push"><input type="hidden" name="id" value="<?=$a['id']?>"><button class="btn primary sm" type="submit" onclick="return confirm('向全部用户推送站内公告通知？')"><?=ico('send',13)?>推送</button></form>
      <a class="btn default sm" href="announcements.php?edit=<?=$a['id']?>"><?=ico('edit',13)?>编辑</a>
      <form method="post" style="display:inline"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="act" value="toggle"><input type="hidden" name="id" value="<?=$a['id']?>"><button class="btn default sm" type="submit"><?=$a['active']?'下架':'上架'?></button></form>
      <form method="post" style="display:inline" onsubmit="return confirm('删除该公告？')"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="act" value="del"><input type="hidden" name="id" value="<?=$a['id']?>"><button class="btn danger sm" type="submit"><?=ico('trash',13)?>删除</button></form>
    </td></tr>
  <?php endforeach; ?>
  <?php if(!$list): ?><tr><td colspan="6" style="text-align:center;color:#999;padding:30px">暂无公告</td></tr><?php endif; ?>
  </table></div>
</div>
<?php include __DIR__.'/foot.php'; ?>
