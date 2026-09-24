<?php require_once __DIR__.'/common.php'; $u=require_login(); $msg='';
if(!ENABLE_FORUM){ $page_title='发帖'; include 'header.php'; echo '<div class="form"><div class="alert">'.ico('alert',15).'社区功能暂未开放发帖，请稍后再来。</div><div class="center"><a href="index.php">返回首页</a></div></div>'; include 'footer.php'; exit; }
if($_SERVER['REQUEST_METHOD']==='POST'){
 try{
  if(!check_csrf()) throw new Exception('表单过期');
  $r=geetest_verify(); if($r!==true) throw new Exception($r);
  $board=trim($_POST['board']??'综合交流'); $title=trim($_POST['title']??''); $content=trim($_POST['content']??'');
  // v1.4.2：还原正文中的 [[IMGn]] 占位符为真实图床外链（顺序与隐藏域 imgs 一致）
  $imgs=array_values(array_filter(array_map('trim',explode("\n",(string)($_POST['imgs']??''))),function($v){ return $v!==''; }));
  if($imgs){
    $content=preg_replace_callback('/\[\[IMG(\d+)\]\]/',function($m) use($imgs){
      $i=((int)$m[1])-1;
      return isset($imgs[$i])?$imgs[$i]:'';
    },$content);
    $content=trim($content);
  }
  // v1.3.0：版块白名单改为读配置，防注入自定义版块
  if(!in_array($board,boards_all(),true)) $board=boards_all()[0];
  $font_family=trim($_POST['font_family']??''); $font_size=trim($_POST['font_size']??'');
  $allow_ff=['','serif',"'PingFang SC','Hiragino Sans GB','Microsoft YaHei',sans-serif","'KaiTi','STKaiti',serif","'FangSong','STFangsong',serif","'SimHei',sans-serif","'STSong',serif"];
  if(!in_array($font_family,$allow_ff,true)) $font_family='';
  if(!in_array($font_size,['14px','15px','16px','18px','20px','22px','24px','28px'])) $font_size='';
  // v1.3.0：标题选填（≤30 字），正文必填 ≥5 字
  if(mb_strlen($title)>30) throw new Exception('标题不能超过 30 个字');
  if(mb_strlen($content)<5) throw new Exception('内容太短（至少 5 个字）');
  $firstImg=first_image_of($content); // 提取首个图片 URL 用于分享卡片
  db()->prepare("INSERT INTO posts(uid,board,title,content,font_family,font_size,first_image) VALUES(?,?,?,?,?,?,?)")->execute([$u['id'],$board,$title,$content,$font_family,$font_size,$firstImg]);
  $newId=db()->lastInsertId();
  header('Location: view.php?id='.$newId); exit; // v1.3.0：发帖成功直接进入详情页
 }catch(Exception $ex){$msg=$ex->getMessage();}
}
$page_title='发帖'; include 'header.php';
?>
<div class="form wide"><h2>发布帖子</h2><div class="sub">文明发言 · 共建校园</div>
<?php if($msg):?><div class="alert"><?=ico('alert',15)?><?=e($msg)?></div><?php endif;?>
<form method="post"><input type="hidden" name="csrf" value="<?=csrf_token()?>">
<select class="inp" name="board"><?php foreach(boards_all() as $bd): ?><option><?=e($bd)?></option><?php endforeach; ?></select>
<input class="inp" name="title" placeholder="标题（选填，不超过 30 字）" maxlength="30">
<textarea class="inp" name="content" rows="7" placeholder="内容（至少 5 个字，可直接粘贴图片链接）" required></textarea>
<?=geetest_widget()?>
<button class="btn block"><?=ico('send',15)?>发布</button></form></div>
<?php include 'footer.php'; ?>
