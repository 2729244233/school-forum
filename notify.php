<?php
// v1.3.0 站内消息接口：
//   GET  act=poll → {ok,enabled,unread,items[10]}（ENABLE_MSG 关闭时 enabled:0）
//   POST act=read → 标记当前用户全部未读为已读
require_once __DIR__.'/common.php';
header('Content-Type: application/json; charset=utf-8');
try{
  if(!ENABLE_MSG){ echo json_encode(['ok'=>1,'enabled'=>0]); exit; }
  $me=current_user();
  if(!$me) throw new Exception('请先登录');
  $pdo=db(); $uid=(int)$me['id'];
  $act=$_REQUEST['act']??'poll';
  if($act==='read'){
    if(!check_csrf()) throw new Exception('表单过期，请刷新');
    $pdo->prepare("UPDATE notifications SET is_read=1 WHERE uid=? AND is_read=0")->execute([$uid]);
    echo json_encode(['ok'=>1]); exit;
  }
  $c=$pdo->prepare("SELECT COUNT(*) FROM notifications WHERE uid=? AND is_read=0"); $c->execute([$uid]);
  $l=$pdo->prepare("SELECT n.*,u.username AS actor_name FROM notifications n LEFT JOIN users u ON u.id=n.actor_id WHERE n.uid=? ORDER BY n.id DESC LIMIT 10");
  $l->execute([$uid]);
  $items=[];
  foreach($l->fetchAll(PDO::FETCH_ASSOC) as $n){
    $who=trim((string)($n['actor_name']??'')) ?: '系统';
    $items[]=['id'=>(int)$n['id'],'type'=>(string)$n['type'],'post_id'=>(int)$n['post_id'],'read'=>(int)$n['is_read'],
      'text'=>$who.' · '.(string)$n['content'],'time'=>date('m-d H:i',(int)$n['created_at'])];
  }
  echo json_encode(['ok'=>1,'enabled'=>1,'unread'=>(int)$c->fetchColumn(),'items'=>$items]);
}catch(Exception $ex){ echo json_encode(['ok'=>0,'msg'=>$ex->getMessage()]); }
