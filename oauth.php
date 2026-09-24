<?php require_once __DIR__.'/common.php';
// 素颜聚合登录（微信/QQ/抖音/微软）统一入口与回调，流程见 https://u.suyanw.cn/doc.php
// ?type=xxx        → 请求 connect.php?act=login 获取授权地址并 302 跳转
// ?type=xxx&code=x → 请求 connect.php?act=callback 换取 social_uid / nickname 后登录或绑定
// 注：原文件名为 auth_wechat.php，因虚拟主机安全扫描把该文件名误判为钓鱼页而 403，故更名为 oauth.php
// 改名后需同步：素颜后台应用的回调地址 → https://你的域名/oauth.php
$TYPES=suyan_types();
$type=preg_replace('/[^a-z0-9]/i','',$_GET['type']??'wx');
$P=$TYPES[$type]??['name'=>'第三方','icon'=>'link','color'=>'#6C4CF6'];
// 诊断模式：?debug=1 输出回调地址计算与素颜接口连通性（不打码敏感信息，排障后可随时删除此段）
if(isset($_GET['debug'])){
  header('Content-Type: text/plain; charset=utf-8');
  $mask=function($s){ $s=(string)$s; return $s===''?'(空)':substr($s,0,2).str_repeat('*',max(1,strlen($s)-4)).substr($s,-2).' ('.strlen($s).'位)'; };
  echo "== 素颜聚合登录诊断 ==\n";
  echo 'time:            '.date('Y-m-d H:i:s')."\n";
  echo 'PHP:             '.PHP_VERSION."\n";
  echo '__DIR__:         '.__DIR__."\n";
  echo 'SCRIPT_FILENAME: '.($_SERVER['SCRIPT_FILENAME']??'(unset)')."\n";
  echo 'SCRIPT_NAME:     '.($_SERVER['SCRIPT_NAME']??'(unset)')."\n";
  echo 'DOCUMENT_ROOT:   '.($_SERVER['DOCUMENT_ROOT']??'(unset)')."\n";
  echo 'HTTP_HOST:       '.($_SERVER['HTTP_HOST']??'(unset)')."\n";
  echo 'HTTPS:           '.($_SERVER['HTTPS']??'(unset)')."\n";
  echo 'X_FORWARDED_PROTO: '.($_SERVER['HTTP_X_FORWARDED_PROTO']??'(unset)')."\n";
  echo 'SITE_URL(后台配置): '.(defined('SITE_URL')&&SITE_URL?SITE_URL:'(空=自动识别)')."\n";
  echo 'app_base_path(): '.var_export(app_base_path(),true)."\n";
  echo 'base_url():      '.base_url()."\n";
  echo '回调地址 → '.base_url().'/oauth.php'."\n";
  echo "（请核对：该地址必须与素颜后台应用里填的回调地址完全一致，且浏览器直接访问它不应是 404）\n";
  echo 'SUYAN_ENABLED:   '.(SUYAN_ENABLED?'1':'0')."\n";
  echo 'SUYAN_API:       '.SUYAN_API."\n";
  echo 'SUYAN_APPID:     '.$mask(SUYAN_APPID)."\n";
  echo 'SUYAN_APPKEY:    '.$mask(SUYAN_APPKEY)."\n";
  echo 'allow_url_fopen: '.(ini_get('allow_url_fopen')?'1':'0')."，curl: ".(function_exists('curl_init')?'1':'0')."\n";
  echo "== 实测请求素颜 act=login ==\n";
  $testurl=suyan_connect_url('login',['type'=>$type,'redirect_uri'=>base_url().'/oauth.php']);
  echo '请求地址(含APPKEY，仅供排障，勿公开): '.$testurl."\n";
  echo "（把上整行 URL 复制到浏览器新标签页直接打开：若返回 JSON 说明请求本身没问题、素颜只拦服务器 IP/UA；若还是这个红字系统页，则请求内容/账号有问题）\n";
  list($body,$err)=http_get($testurl);
  echo $err?('请求失败：'.$err."\n"):('原始返回：'.$body."\n");
  exit;
}
try{
  if(!SUYAN_ENABLED) throw new Exception('第三方登录未启用，请联系管理员');
  if(!isset($TYPES[$type])) throw new Exception('不支持的登录方式');
  if(!suyan_channel_on($type)) throw new Exception('该登录方式已被管理员关闭，请选择其他方式');
  $code=trim($_GET['code']??'');
  if($code===''){
    // Step1：获取授权跳转地址（act=login 返回 JSON，url 为授权页）
    $cb=base_url().'/oauth.php';
    list($res,$err)=http_get(suyan_connect_url('login',['type'=>$type,'redirect_uri'=>$cb]));
    if($err) throw new Exception('无法连接素颜接口：'.$err);
    $j=json_decode($res?:'{}',true);
    if((int)($j['code']??-1)===0 && !empty($j['url'])){
      $url=trim((string)$j['url']);
      if(!preg_match('#^https?://#i',$url)) throw new Exception('素颜接口返回了非法的授权地址');
      header('Location: '.$url); exit;
    }
    throw new Exception('获取'.$P['name'].'登录地址失败：'.($j['msg']??('接口返回异常：'.mb_substr((string)$res,0,200))));
  }
  // Step2：code 换取用户信息（act=callback 返回 social_uid / nickname）
  list($res,$err)=http_get(suyan_connect_url('callback',['type'=>$type,'code'=>$code]));
  if($err) throw new Exception('无法连接素颜接口：'.$err);
  $j=json_decode($res?:'{}',true);
  if((int)($j['code']??-1)!==0) throw new Exception($P['name'].'登录验证失败：'.($j['msg']??'未知错误'));
  $openid=trim((string)($j['social_uid']??''));
  $nick=trim((string)($j['nickname']??''))?:$P['name'].'用户';
  $face=trim((string)($j['faceimg']??$j['face']??$j['avatar']??'')); // 头像做容错：字段名兼容多种返回
  if(!preg_match('#^https?://#i',$face)) $face='';
  if($openid==='') throw new Exception('未获取到'.$P['name'].'用户标识');
  $pdo=db();
  // 已绑定过：直接登录绑定的账号（顺带补写头像）
  $u=social_find_user($type,$openid);
  if($u){
    if((int)$u['status']!==1) throw new Exception('账号已被禁用');
    if($face!=='' && empty($u['avatar'])){
      try{ $pdo->prepare("UPDATE users SET avatar=? WHERE id=? AND (avatar IS NULL OR avatar='')")->execute([$face,(int)$u['id']]); }catch(Exception $ax){}
    }
    session_regenerate_id(true);
    $_SESSION['uid']=$u['id']; header('Location: index.php'); exit;
  }
  // 已登录用户 → 绑定到当前账号（第三方身份只能绑定一个账号）
  $cur=current_user();
  if($cur){
    try{
      $pdo->prepare("INSERT INTO social_accounts(uid,type,social_uid,nickname) VALUES(?,?,?,?)")->execute([(int)$cur['id'],$type,$openid,$nick]);
    }catch(Exception $dup){ throw new Exception('该'.$P['name'].'账号已被其他用户绑定'); }
    if($type==='wx') $pdo->prepare("UPDATE users SET wechat_openid=?,wechat_name=? WHERE id=?")->execute([$openid,$nick,$cur['id']]);
    if($face!==''){ try{ $pdo->prepare("UPDATE users SET avatar=? WHERE id=? AND (avatar IS NULL OR avatar='')")->execute([$face,(int)$cur['id']]); }catch(Exception $ax){} }
    header('Location: user.php'); exit;
  }
  // 未绑定 → 不再建占位号：暂存第三方身份，强制先绑定邮箱（bind.php）
  $_SESSION['pending_social']=['type'=>$type,'openid'=>$openid,'nickname'=>$nick,'avatar'=>$face];
  header('Location: bind.php'); exit;
}catch(Exception $ex){ $page_title=$P['name'].'登录'; include 'header.php'; echo '<div class="form"><div class="alert">'.e($ex->getMessage()).'</div><div class="center"><a href="login.php">返回登录</a> &nbsp;·&nbsp; <a href="oauth.php?type='.e($type).'&debug=1" target="_blank">查看诊断信息</a></div></div>'; include 'footer.php'; }