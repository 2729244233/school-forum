<?php
// v1.3.0 点赞接口：POST act=like/unlike + target_type(post|reply) + target_id
// 返回 JSON {ok,count,liked}；XHR 端点不做极验（bind 模式与表单提交不冲突）
require_once __DIR__.'/common.php';
header('Content-Type: application/json; charset=utf-8');
try{
  $me=current_user();
  if(!$me) throw new Exception('请先登录');
  if(!ENABLE_FORUM) throw new Exception('社区功能暂未开放');
  if(!check_csrf()) throw new Exception('表单过期，请刷新');
  $act=$_POST['act']??''; $tt=$_POST['target_type']??'post'; $tid=(int)($_POST['target_id']??0);
  if(!in_array($act,['like','unlike'],true)) throw new Exception('非法操作');
  if(!in_array($tt,['post','reply'],true) || $tid<=0) throw new Exception('目标不存在');
  $pdo=db(); $uid=(int)$me['id'];
  if($act==='like'){
    // 目标必须存在，且取回作者用于通知
    if($tt==='post'){
      $o=$pdo->prepare("SELECT uid,title FROM posts WHERE id=?"); $o->execute([$tid]);
      $row=$o->fetch(PDO::FETCH_ASSOC);
    }else{
      $o=$pdo->prepare("SELECT r.uid,p.title FROM replies r LEFT JOIN posts p ON p.id=r.pid WHERE r.id=?"); $o->execute([$tid]);
      $row=$o->fetch(PDO::FETCH_ASSOC);
    }
    if(!$row) throw new Exception('目标不存在');
    try{ $pdo->prepare("INSERT INTO likes(target_type,target_id,uid,created_at) VALUES(?,?,?,?)")->execute([$tt,$tid,$uid,time()]); }
    catch(Exception $dup){ /* 唯一约束命中 = 已点过，幂等处理 */ }
    // 通知目标作者（不通知自己）
    if(!empty($row['uid']) && (int)$row['uid']!==$uid){
      $t=trim((string)($row['title']??''));
      notify_add((int)$row['uid'],'like',$uid,$tt==='post'?$tid:0,$tt==='reply'?$tid:0,
        '赞了你的'.($tt==='post'?($t!==''?'帖子《'.$t.'》':'帖子'):'回复'));
    }
  }else{
    $pdo->prepare("DELETE FROM likes WHERE target_type=? AND target_id=? AND uid=?")->execute([$tt,$tid,$uid]);
  }
  $c=$pdo->prepare("SELECT COUNT(*) FROM likes WHERE target_type=? AND target_id=?"); $c->execute([$tt,$tid]);
  echo json_encode(['ok'=>1,'count'=>(int)$c->fetchColumn(),'liked'=>$act==='like'?1:0]);
}catch(Exception $ex){ echo json_encode(['ok'=>0,'msg'=>$ex->getMessage()]); }
