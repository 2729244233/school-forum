<?php
require_once __DIR__.'/common.php';
header('Content-Type: application/json; charset=utf-8');
try{
  $email=trim($_POST['email']??''); $scene=trim($_POST['scene']??'reg');
  if(!in_array($scene,['reg','forgot','bind'])) $scene='reg'; // v1.3.0：bind=第三方登录绑定邮箱
  if(is_social_local($email)) throw new Exception('该邮箱无法接收验证码，请填写真实邮箱'); // v1.4：社交占位邮箱不发信
  // 登录/注册等操作先过极验（前端传参时校验，无参则跳过以兼容直接发码）
  if(GEETEST_ENABLED && isset($_POST['lot_number'])){
    $r=geetest_verify(); if($r!==true) throw new Exception($r);
  }
  $code=make_code($email,$scene,$merr);
  $ret=['ok'=>1,'msg'=>'验证码已发送，请查收邮箱'];
  if($merr) $ret['msg']='邮件未发出：'.$merr;
  if(MAIL_DEBUG_SHOW_CODE) $ret['debug_code']=$code;
  echo json_encode($ret);
}catch(Exception $ex){ echo json_encode(['ok'=>0,'msg'=>$ex->getMessage()]); }
