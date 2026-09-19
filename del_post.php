<?php require_once __DIR__.'/common.php';
$u=require_login();
$msg='';
try{
  if($_SERVER['REQUEST_METHOD']!=='POST') throw new Exception('非法请求');
  if(!check_csrf()) throw new Exception('表单过期');
  $id=intval($_POST['id']??0);
  $s=db()->prepare("SELECT * FROM posts WHERE id=?"); $s->execute([$id]); $p=$s->fetch(PDO::FETCH_ASSOC);
  if(!$p) throw new Exception('帖子不存在');
  if((int)$u['id']!==(int)$p['uid'] && empty($u['is_admin'])) throw new Exception('只能删除自己的帖子');
  db()->prepare("DELETE FROM replies WHERE pid=?")->execute([$id]);
  db()->prepare("DELETE FROM posts WHERE id=?")->execute([$id]);
  header('Location: index.php'); exit;
}catch(Exception $ex){ $msg=$ex->getMessage(); }
?><!doctype html><meta charset="utf-8"><script>alert(<?php echo json_encode($msg, JSON_UNESCAPED_UNICODE); ?>);history.back();</script>
