<?php
// v1.4 已登录账号绑定邮箱（供「绑定邮箱」弹窗调用）：
// act=send  发送验证码（scene=bind，send_code.php 已拦截 @social.local 假邮箱）
// act=bind  校验验证码并把邮箱写入当前账号（第三方占位号 social.local → 真实邮箱）
// act=skip  本次会话不再弹绑定询问
require_once __DIR__.'/common.php';
header('Content-Type: application/json; charset=utf-8');
$me=current_user();
if(!$me){ echo json_encode(['ok'=>0,'msg'=>'请先登录']); exit; }
$act=(string)($_POST['act']??'');
try{
  if(!check_csrf()) throw new Exception('表单过期，请刷新');
  if($act==='skip'){ $_SESSION['bind_asked']=1; echo json_encode(['ok'=>1]); exit; }
  $email=trim($_POST['email']??'');
  if($act==='send'){
    if(!filter_var($email,FILTER_VALIDATE_EMAIL)) throw new Exception('请填写正确的邮箱地址');
    if(is_social_local($email)) throw new Exception('请填写真实邮箱地址');
    $s=db()->prepare("SELECT id FROM users WHERE email=?"); $s->execute([$email]);
    $ex=$s->fetchColumn();
    if($ex && (int)$ex!==(int)$me['id']) throw new Exception('该邮箱已被其他账号使用');
    $code=make_code($email,'bind',$merr);
    $ret=['ok'=>1,'msg'=>'验证码已发送，请查收邮箱'];
    if($merr) $ret['msg']='邮件未发出：'.$merr;
    if(MAIL_DEBUG_SHOW_CODE) $ret['debug_code']=$code;
    echo json_encode($ret); exit;
  }
  if($act==='bind'){
    if(!filter_var($email,FILTER_VALIDATE_EMAIL)) throw new Exception('请填写正确的邮箱地址');
    if(is_social_local($email)) throw new Exception('请填写真实邮箱地址');
    $code=trim($_POST['code']??'');
    $vr=verify_code($email,$code,'bind'); if($vr!==true) throw new Exception($vr);
    $s=db()->prepare("SELECT id FROM users WHERE email=?"); $s->execute([$email]);
    $ex=$s->fetchColumn();
    if($ex && (int)$ex!==(int)$me['id']) throw new Exception('该邮箱已被其他账号使用');
    db()->prepare("UPDATE users SET email=? WHERE id=?")->execute([$email,(int)$me['id']]);
    $_SESSION['bind_asked']=1; // 绑定成功，本次会话不再询问
    echo json_encode(['ok'=>1,'msg'=>'邮箱绑定成功']);
    exit;
  }
  echo json_encode(['ok'=>0,'msg'=>'未知操作']);
}catch(Exception $ex){ echo json_encode(['ok'=>0,'msg'=>$ex->getMessage()]); }
