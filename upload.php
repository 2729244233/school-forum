<?php
// v1.4.2 上传接口：登录 + ENABLE_UPLOAD；图片 jpg/png/gif/webp，视频 mp4/webm/mov/avi（图床统一转 GIF / 动态 WebP 外链）
// v1.4 上传至 img.scdn.io 图床（后台可配 API 地址 / CDN 域名），返回 JSON {ok,url,type}
require_once __DIR__.'/common.php';
header('Content-Type: application/json; charset=utf-8');
try{
  $me=current_user();
  if(!$me) throw new Exception('请先登录');
  if(!ENABLE_FORUM) throw new Exception('社区功能暂未开放');
  if(!ENABLE_UPLOAD) throw new Exception('上传功能未开放');
  if(!check_csrf()) throw new Exception('表单过期，请刷新');
  if(empty($_FILES['file']) || !is_uploaded_file($_FILES['file']['tmp_name']??'')) throw new Exception('未选择文件');
  $f=$_FILES['file'];
  if($f['error']!==UPLOAD_ERR_OK) throw new Exception('上传失败（错误码 '.$f['error'].'，可能超过主机 upload_max_filesize 限制）');
  $ext=strtolower(pathinfo((string)($f['name']??''),PATHINFO_EXTENSION));
  // 图片：用 getimagesize 校验真实类型，不信任客户端 MIME
  $imgAllow=['image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif','image/webp'=>'webp'];
  // 视频：按扩展名 + 文件头（magic bytes）双重校验，避免任意文件被转发到图床
  $vidAllow=['mp4'=>'video/mp4','mov'=>'video/quicktime','webm'=>'video/webm','avi'=>'video/x-msvideo'];
  $info=@getimagesize($f['tmp_name']);
  if($info && isset($imgAllow[$info['mime']])){
    $mime=$info['mime']; $ext=$imgAllow[$mime]; $type='image';
    if($f['size']>UPLOAD_MAX_MB*1048576) throw new Exception('图片不能超过 '.UPLOAD_MAX_MB.'MB');
  }elseif(isset($vidAllow[$ext])){
    $head=(string)@file_get_contents($f['tmp_name'],false,null,0,12);
    $sigOk=($ext==='mp4'||$ext==='mov') ? (strpos($head,'ftyp')!==false)
      : ($ext==='webm' ? substr($head,0,4)==="\x1A\x45\xDF\xA3"
      : ($ext==='avi' ? substr($head,0,4)==='RIFF' : false));
    if(!$sigOk) throw new Exception('视频文件格式不正确，请重新选择');
    $type='video'; $mime=$vidAllow[$ext];
    if($f['size']>UPLOAD_VIDEO_MB*1048576) throw new Exception('视频不能超过 '.UPLOAD_VIDEO_MB.'MB');
  }else{
    throw new Exception('仅支持 jpg/png/gif/webp 图片，或 mp4/webm/mov/avi 视频');
  }
  list($url,$err)=cdn_upload($f['tmp_name'],$mime,'forum_'.date('YmdHis').'_'.substr(md5(uniqid('',true)),0,5).'.'.$ext);
  if($err) throw new Exception($err);
  echo json_encode(['ok'=>1,'url'=>$url,'type'=>$type]);
}catch(Exception $ex){ echo json_encode(['ok'=>0,'msg'=>$ex->getMessage()]); }
