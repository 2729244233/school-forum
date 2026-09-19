<?php
require_once __DIR__.'/../common.php';
$ADMIN=require_any('admin','official','operator'); // 三身份均可推送
$pdo=db(); $msg=''; $ok='';

/* v1.4.1：直接推送消息 —— 只填标题 + 内容，立即向全部启用用户发送站内通知。
   mailed=2 免即时群发邮件，交由前台流量的聚合摘要统一发送，避免主机超时。 */
function push_msg_to_all($pdo,$adminId,$title,$content,$link=''){
  $ncontent=trim((string)$title);
  $plain=trim(preg_replace('/\s+/u',' ',trim(strip_tags((string)$content))));
  if($plain!=='') $ncontent.='：'.mb_substr($plain,0,50);
  if($link!=='') $ncontent.='（'.mb_substr($link,0,80).'）';
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
    if(!check_csrf()) throw new Exception('表单过期，请刷新');
    $act=$_POST['act']??'';
    if($act==='send'){
      $title=trim($_POST['title']??'');
      $content=trim($_POST['content']??'');
      $link=trim($_POST['link']??'');
      if($title==='') throw new Exception('请填写推送标题');
      if(mb_strlen($title)>60) throw new Exception('标题不能超过 60 字');
      if($content==='') throw new Exception('请填写推送内容');
      if($link!=='' && !preg_match('~^(https?://|/)~i',$link)) throw new Exception('跳转链接需以 http(s):// 或 / 开头');
      $sent=push_msg_to_all($pdo,$ADMIN['id'],$title,$content,$link);
      $ok='已成功推送给 '.$sent.' 位用户（站内消息即时送达，邮件按聚合策略发送）';
    }else throw new Exception('非法操作');
  }catch(Exception $ex){ $msg=$ex->getMessage(); }
}

$userCount=(int)$pdo->query("SELECT COUNT(*) FROM users WHERE status=1")->fetchColumn();
$recent=$pdo->query("SELECT content,created_at FROM notifications WHERE type='system' ORDER BY id DESC LIMIT 8")->fetchAll(PDO::FETCH_ASSOC);
$page='push'; $page_title='消息推送'; include __DIR__.'/head.php';
?>
<!-- ===== 直接推送 ===== -->
<form method="post" id="pushform"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="act" value="send">
<div class="panel">
  <div class="panel-title"><?=ico('bell',17)?>直接推送消息 <span class="tag blue"><?=$userCount?> 位用户可接收</span></div>
  <div class="panel-desc">填写标题与内容即可立即向全部启用用户推送站内通知，无需先创建公告。推送内容会出现在前台导航的铃铛消息中；邮件将按「系统设置 → 邮件通知聚合」的窗口合并发送。</div>
  <div class="fg"><label>推送标题</label><div class="field"><input type="text" name="title" maxlength="60" required placeholder="如：校园网维护通知"><div class="hint">建议 20 字以内，过长会截断显示</div></div></div>
  <div class="fg"><label>推送内容</label><div class="field"><textarea name="content" rows="5" required style="width:100%;border:1.5px solid var(--border);border-radius:10px;padding:10px 12px;font-family:inherit;font-size:13.5px" placeholder="填写要推送给用户的具体内容，支持纯文本"></textarea></div></div>
  <div class="fg"><label>跳转链接（选填）</label><div class="field"><input type="text" name="link" placeholder="https://... 或本站相对地址 view.php?id=1"><div class="hint">填写后会附在消息末尾，方便用户点击查看详情</div></div></div>
  <div class="save-bar"><button class="btn primary" type="submit" onclick="return confirm('确认向全部 <?=$userCount?> 位用户推送该消息？')"><?=ico('send',14)?>立即推送</button>
  <span class="fl">推送后不可撤回，请确认内容无误</span></div>
</div></form>

<!-- ===== 最近推送 ===== -->
<div class="panel">
  <div class="panel-title"><?=ico('file',17)?>最近推送记录</div>
  <div class="panel-desc">展示最近 8 条系统推送消息（同一批推送会为每位用户各生成一条记录，此处按时间倒序展示）。</div>
  <div class="table-wrap"><table class="tb"><tr><th style="width:170px">时间</th><th>消息内容</th></tr>
  <?php foreach($recent as $r): ?>
  <tr><td style="font-size:12px;color:var(--muted)"><?=date('Y-m-d H:i',$r['created_at'])?></td><td><?=e($r['content'])?></td></tr>
  <?php endforeach; ?>
  <?php if(!$recent): ?><tr><td colspan="2" style="text-align:center;color:#999;padding:30px">暂无推送记录</td></tr><?php endif; ?>
  </table></div>
</div>
<?php include __DIR__.'/foot.php'; ?>
