<?php
// v1.3.0 图片上传接口：登录 + ENABLE_UPLOAD；白名单 jpg/png/gif/webp，≤ upload_max_mb
// v1.4 上传至 img.scdn.io 图床（后台可配 API 地址 / CDN 域名），返回 JSON {ok,url}
require_once __DIR__.'/common.php';
header('Content-Type: application/json; charset=utf-8');
try{
  $me=current_user();
  if(!$me) throw new Exception('请先登录');
  if(!ENABLE_FORUM) throw new Exception('社区功能暂未开放');
  if(!ENABLE_UPLOAD) throw new Exception('上传功能未开放');
  if(!check_csrf()) throw new Exception('表单过期，请刷新');
  if(empty($_FILES['file']) || !is_uploaded_file($_FILES['file']['tmp_name']??'')) throw new Exception('未选择图片');
  $f=$_FILES['file'];
  if($f['error']!==UPLOAD_ERR_OK) throw new Exception('上传失败（错误码 '.$f['error'].'，可能超过主机 upload_max_filesize 限制）');
  $max=UPLOAD_MAX_MB*1048576;
  if($f['size']>$max) throw new Exception('图片不能超过 '.UPLOAD_MAX_MB.'MB');
  // 用 getimagesize 校验真实类型，不信任客户端 MIME
  $info=@getimagesize($f['tmp_name']);
  $allow=['image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif','image/webp'=>'webp'];
  if(!$info || !isset($allow[$info['mime']])) throw new Exception('仅支持 jpg/png/gif/webp 图片');
  list($url,$err)=cdn_upload($f['tmp_name'],$info['mime'],'forum_'.date('YmdHis').'_'.substr(md5(uniqid('',true)),0,5).'.'.$allow[$info['mime']]);
  if($err) throw new Exception($err);
  echo json_encode(['ok'=>1,'url'=>$url]);
}catch(Exception $ex){ echo json_encode(['ok'=>0,'msg'=>$ex->getMessage()]); }
