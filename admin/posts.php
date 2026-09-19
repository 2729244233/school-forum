<?php
require_once __DIR__.'/../common.php';
$ADMIN=require_any('admin','official','operator'); // v1.3.0：三身份均可管理帖子
$pdo=db();
$msg='';
// 删除帖子（连带删除回复/点赞）：仅接受 POST + CSRF；公告帖标记切换
if($_SERVER['REQUEST_METHOD']==='POST' && in_array(($_POST['act']??''),['del','announce'],true)){
  if(check_csrf()){
    $id=intval($_POST['id']??0);
    if($id>0){
      if(($_POST['act'])==='del'){
        $pdo->prepare("DELETE FROM replies WHERE pid=?")->execute([$id]);
        $pdo->prepare("DELETE FROM likes WHERE target_type='post' AND target_id=?")->execute([$id]);
        $pdo->prepare("DELETE FROM posts WHERE id=?")->execute([$id]);
        $_SESSION['flash']='帖子 #'.$id.' 已删除';
      }else{
        $pdo->prepare("UPDATE posts SET is_announce=1-is_announce WHERE id=?")->execute([$id]); // v1.3.0：标记/取消公告帖
        $_SESSION['flash']='帖子 #'.$id.' 公告标记已切换';
      }
    }
  }else{ $_SESSION['flash']='表单过期，请重试'; }
  header('Location: posts.php'); exit;
}
$q=trim($_GET['q']??'');
$where=''; $args=[];
if($q!==''){ $where=' WHERE p.title LIKE ? OR p.board LIKE ? OR u.username LIKE ?'; $like='%'.$q.'%'; $args=[$like,$like,$like]; }
$total=$pdo->prepare("SELECT COUNT(*) FROM posts p LEFT JOIN users u ON u.id=p.uid $where"); $total->execute($args); $total=$total->fetchColumn();
$page=max(1,intval($_GET['page']??1)); $per=15; $pages=max(1,ceil($total/$per)); $page=min($page,$pages);
$off=($page-1)*$per;
$sql="SELECT p.*,u.username,u.email FROM posts p LEFT JOIN users u ON u.id=p.uid $where ORDER BY p.id DESC LIMIT $per OFFSET $off";
$list=$pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
$page='posts'; $page_title='帖子管理'; include __DIR__.'/head.php';
?>
<form class="search-bar" method="get" action="posts.php"><input type="text" name="q" value="<?=e($q)?>" placeholder="搜索标题 / 版块 / 作者"><button class="btn primary" type="submit"><?=ico('search')?>搜索</button><a class="btn default" href="posts.php">清空</a></form>
<div style="display:flex;gap:8px;margin:-4px 0 8px"><span class="tag blue">共 <?=(int)$total?> 条</span></div>
<div class="panel"><div class="table-wrap"><table class="tb"><tr><th style="width:60px">ID</th><th>标题</th><th>版块</th><th>作者</th><th>浏览</th><th>发布时间</th><th style="width:210px">操作</th></tr>
<?php foreach($list as $p): ?>
<tr><td>#<?=$p['id']?></td>
<td><a href="../view.php?id=<?=$p['id']?>" target="_blank" style="color:var(--primary);font-weight:600"><?=e($p['title']!==''?$p['title']:'（无标题）')?></a><?php if(!empty($p['is_announce'])):?> <span class="tag warn">公告帖</span><?php endif;?></td>
<td><span class="tag blue"><?=e($p['board'])?></span></td>
<td><?=e($p['username']?:('UID '.$p['uid']))?></td>
<td><?=$p['views']?></td>
<td><?=e($p['created_at'])?></td>
<td>
<form method="post" style="display:inline"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="act" value="announce"><input type="hidden" name="id" value="<?=$p['id']?>"><button class="btn <?=$p['is_announce']?'default':'primary'?> sm" type="submit"><?=$p['is_announce']?'取消公告':'设为公告'?></button></form>
<form method="post" style="display:inline" onsubmit="return confirm('删除该帖及其全部回复？')"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="act" value="del"><input type="hidden" name="id" value="<?=$p['id']?>"><button class="btn danger sm" type="submit"><?=ico('trash',13)?>删除</button></form></td></tr>
<?php endforeach; ?>
<?php if(!$list): ?><tr><td colspan="7" style="text-align:center;color:#999;padding:30px">暂无帖子</td></tr><?php endif; ?>
</table></div>
<div class="pager">共 <?=$total?> 条 · 第 <?=$page?>/<?=$pages?> 页
<?php for($i=1;$i<=$pages;$i++): ?><?php if($i==$page):?><span class="cur"><?=$i?></span><?php else:?><a href="posts.php?page=<?=$i?>&q=<?=urlencode($q)?>"><?=$i?></a><?php endif;?><?php endfor;?>
</div></div>
<?php include __DIR__.'/foot.php'; ?>