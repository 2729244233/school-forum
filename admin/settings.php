<?php
require_once __DIR__.'/../common.php';
$ADMIN=require_admin();
$msg=''; $ok='';
$cfg=settings_load();
if($_SERVER['REQUEST_METHOD']==='POST'){
 try{
  if(!check_csrf()) throw new Exception('表单过期，请刷新');
  $act=$_POST['act']??'';
  $c=settings_load();
  if($act==='basic'){
    $c['site_name']=trim($_POST['site_name']??$c['site_name']);
    $c['site_slogan_sub']=trim($_POST['site_slogan_sub']??$c['site_slogan_sub']);
    $c['site_url']=rtrim(trim($_POST['site_url']??$c['site_url']),'/');
    settings_save($c); $ok='基础信息已保存';
  }elseif($act==='geetest'){
    $c['geetest_enabled']=isset($_POST['geetest_enabled'])?1:0;
    $c['geetest_id']=trim($_POST['geetest_id']??$c['geetest_id']);
    $c['geetest_key']=trim($_POST['geetest_key']??$c['geetest_key']);
    settings_save($c); $ok='极验配置已保存';
  }elseif($act==='smtp'){
    $c['mail_use_smtp']=isset($_POST['mail_use_smtp'])?1:0;
    $c['mail_smtp_host']=trim($_POST['mail_smtp_host']??$c['mail_smtp_host']);
    $c['mail_smtp_port']=(int)($_POST['mail_smtp_port']??$c['mail_smtp_port']);
    $c['mail_smtp_user']=trim($_POST['mail_smtp_user']??$c['mail_smtp_user']);
    if(trim($_POST['mail_smtp_pass']??'')!=='') $c['mail_smtp_pass']=trim($_POST['mail_smtp_pass']);
    $c['mail_from']=trim($_POST['mail_from']??$c['mail_from']);
    $c['mail_from_name']=trim($_POST['mail_from_name']??$c['mail_from_name']);
    $c['mail_debug_show_code']=isset($_POST['mail_debug_show_code'])?1:0;
    settings_save($c); $ok='邮箱 / SMTP 配置已保存';
  }elseif($act==='suyan'){
    $c['suyan_enabled']=isset($_POST['suyan_enabled'])?1:0;
    $c['suyan_api']=rtrim(trim($_POST['suyan_api']??$c['suyan_api']),'/');
    $c['suyan_appid']=trim($_POST['suyan_appid']??$c['suyan_appid']);
    $c['suyan_appkey']=trim($_POST['suyan_appkey']??$c['suyan_appkey']);
    settings_save($c); $ok='素颜聚合登录配置已保存';
  }elseif($act==='debug'){
    $c['debug_mode']=isset($_POST['debug_mode'])?1:0;
    $c['mail_debug_show_code']=isset($_POST['mail_debug_show_code'])?1:0;
    settings_save($c); $ok=$c['debug_mode']?'调试模式已开启（显示详细错误信息）':'调试模式已关闭';
  }elseif($act==='features'){
    // v1.3.0：功能开关（enable_forum 为主控件，msg/upload 为其子能力）
    $c['enable_forum']=isset($_POST['enable_forum'])?1:0;
    $c['enable_msg']=isset($_POST['enable_msg'])?1:0;
    $c['enable_upload']=isset($_POST['enable_upload'])?1:0;
    $c['enable_register']=isset($_POST['enable_register'])?1:0;
    settings_save($c); $ok='功能开关已保存';
  }elseif($act==='notify'){
    // v1.3.0：邮件通知聚合（点赞/回复/公告 三类型开关 + 聚合窗口分钟数）
    $c['mail_notify_like']=isset($_POST['mail_notify_like'])?1:0;
    $c['mail_notify_reply']=isset($_POST['mail_notify_reply'])?1:0;
    $c['mail_notify_push']=isset($_POST['mail_notify_push'])?1:0;
    $c['mail_digest_mins']=max(1,(int)($_POST['mail_digest_mins']??$c['mail_digest_mins']));
    settings_save($c); $ok='邮件通知聚合配置已保存';
  }elseif($act==='upload'){
    // v1.4：图床（img.scdn.io，API 地址 / CDN 域名）
    $c['cdn_api']=trim($_POST['cdn_api']??$c['cdn_api']);
    $c['cdn_domain']=trim($_POST['cdn_domain']??$c['cdn_domain']);
    $c['upload_max_mb']=max(1,(int)($_POST['upload_max_mb']??$c['upload_max_mb']));
    settings_save($c); $ok='图床配置已保存';
  }elseif($act==='footer'){
    // v1.3.0：页脚（关于允许 a/br/strong 白名单，前台输出时 strip_tags）
    $c['footer_about']=(string)($_POST['footer_about']??'');
    $c['footer_donate']=trim($_POST['footer_donate']??'');
    $links=[];
    foreach(explode("\n",(string)($_POST['footer_links']??'')) as $line){
      $line=trim($line); if($line==='') continue;
      $parts=array_map('trim',explode('|',$line,2));
      if(count($parts)===1) $links[]=['text'=>$parts[0],'url'=>$parts[0]];
      else $links[]=['text'=>$parts[0],'url'=>$parts[1]];
    }
    $c['footer_links']=array_values(array_filter($links,function($l){ return $l['url']!==''; }));
    settings_save($c); $ok='页脚配置已保存';
  }elseif($act==='test_mail'){
    $to=trim($_POST['test_to']??'');
    if(!filter_var($to,FILTER_VALIDATE_EMAIL)) throw new Exception('请填写正确的测试邮箱');
    $code=make_code($to,'reg',$merr);
    if($merr) throw new Exception('测试邮件发送失败：'.$merr);
    $ok='测试邮件已发送至 '.$to.($code&&MAIL_DEBUG_SHOW_CODE?'（测试验证码：'.$code.'）':'');
  }
  if(!empty($ok)){ $cfg=settings_load(); }
 }catch(Exception $ex){ $msg=$ex->getMessage(); }
}
$page='settings'; $page_title='系统设置'; include __DIR__.'/head.php';
?>
<!-- ===== 基础信息 ===== -->
<form method="post"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="act" value="basic">
<div class="panel">
  <div class="panel-title"><?=ico('gear',17)?>基础信息</div>
  <div class="panel-desc">站点名称、标语与绑定域名，保存后即时生效。</div>
  <div class="fg"><label>站点名称</label><div class="field"><input type="text" name="site_name" value="<?=e($cfg['site_name'])?>"></div></div>
  <div class="fg"><label>站点标语</label><div class="field"><input type="text" name="site_slogan_sub" value="<?=e($cfg['site_slogan_sub'])?>"></div></div>
  <div class="fg"><label>站点域名</label><div class="field"><input type="text" name="site_url" value="<?=e($cfg['site_url'])?>" placeholder="留空自动识别，如 https://bbs.xxx.cn"><div class="hint">用于拼接第三方登录回调地址。留空自动识别；若手动填写且站点部署在子目录，必须带上子目录，例如 https://bbs.xxx.cn/admin（填错会导致回调 404）</div></div></div>
  <div class="save-bar"><button class="btn primary" type="submit">保存基础信息</button><span class="fl">即时生效</span></div>
</div></form>

<!-- ===== 极验 ===== -->
<form method="post" id="geetest"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="act" value="geetest">
<div class="panel">
  <div class="panel-title"><?=ico('shield',17)?>极验人机验证 <span class="tag <?=GEETEST_ENABLED?'ok':'err'?>"><?=GEETEST_ENABLED?'已启用':'未启用'?></span></div>
  <div class="panel-desc">控制登录 / 注册 / 忘记密码 / 发帖时的人机校验。前往极验开放平台申请 captcha_id 与 captcha_key。</div>
  <div class="fg"><label>启用极验</label><div class="field"><label class="sw"><input type="checkbox" name="geetest_enabled" value="1" <?=$cfg['geetest_enabled']?'checked':''?>><span class="track"></span><span class="sw-txt"><b>登录 / 注册走极验</b><i>开启后登录、注册、忘记密码、发帖需完成人机校验</i></span></label></div></div>
  <div class="fg"><label>captcha_id</label><div class="field"><input type="text" name="geetest_id" value="<?=e($cfg['geetest_id'])?>" placeholder="极验提交流程的 captchaId"></div></div>
  <div class="fg"><label>captcha_key</label><div class="field"><input type="text" name="geetest_key" value="<?=e($cfg['geetest_key'])?>" placeholder="极验客户密钥，用于服务端签名"></div></div>
  <div class="save-bar"><button class="btn primary" type="submit">保存极验配置</button></div>
</div></form>

<!-- ===== SMTP / 邮箱 ===== -->
<form method="post" id="smtp"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="act" value="smtp">
<div class="panel">
  <div class="panel-title"><?=ico('mail',17)?>邮箱验证码 / SMTP <span class="tag <?=MAIL_USE_SMTP?'ok':'warn'?>"><?=MAIL_USE_SMTP?'SMTP 已启用':'mail() 直发'?></span></div>
  <div class="panel-desc">关闭 SMTP 时使用虚拟主机 mail() 直发；多数虚拟主机会过滤，建议开启 SMTP（如 QQ/163 授权码）。</div>
  <div class="fg"><label>使用 SMTP</label><div class="field"><label class="sw"><input type="checkbox" name="mail_use_smtp" value="1" <?=$cfg['mail_use_smtp']?'checked':''?>><span class="track"></span><span class="sw-txt"><b>通过 SMTP 发送邮件</b><i>关闭则使用虚拟主机 mail() 直发，多数主机商会过滤</i></span></label></div></div>
  <div class="fg"><label>SMTP 服务器</label><div class="field"><input type="text" name="mail_smtp_host" value="<?=e($cfg['mail_smtp_host'])?>" placeholder="smtp.qq.com"></div></div>
  <div class="fg"><label>SMTP 端口</label><div class="field"><input type="number" name="mail_smtp_port" value="<?=(int)$cfg['mail_smtp_port']?>"></div></div>
  <div class="fg"><label>账号</label><div class="field"><input type="text" name="mail_smtp_user" value="<?=e($cfg['mail_smtp_user'])?>" placeholder="发信邮箱账号"></div></div>
  <div class="fg"><label>授权码/密码</label><div class="field"><input type="password" name="mail_smtp_pass" value="" placeholder="<?=$cfg['mail_smtp_pass']?'已配置（留空不修改）':'填写授权码'?>"><div class="hint">若已配置，留空则保持不变</div></div></div>
  <div class="fg"><label>发件人地址</label><div class="field"><input type="text" name="mail_from" value="<?=e($cfg['mail_from'])?>" placeholder="noreply@example.com"></div></div>
  <div class="fg"><label>发件人名称</label><div class="field"><input type="text" name="mail_from_name" value="<?=e($cfg['mail_from_name'])?>"></div></div>
  <div class="fg"><label>验证码回显</label><div class="field"><label class="sw"><input type="checkbox" name="mail_debug_show_code" value="1" <?=$cfg['mail_debug_show_code']?'checked':''?>><span class="track"></span><span class="sw-txt"><b>发送失败时页面回显验证码</b><i>仅测试用，正式上线请关闭</i></span></label></div></div>
  <div class="save-bar"><button class="btn primary" type="submit">保存邮箱配置</button></div>
</div></form>

<!-- ===== 素颜聚合登录 ===== -->
<form method="post" id="suyan"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="act" value="suyan">
<div class="panel">
  <div class="panel-title"><?=ico('wechat',17)?>素颜聚合登录 <span class="tag <?=SUYAN_ENABLED?'ok':'err'?>"><?=SUYAN_ENABLED?'已启用':'未启用'?></span></div>
  <div class="panel-desc">支持 微信 / QQ / 抖音 / 微软 四种方式（需在素颜后台分别创建对应登录应用）。回调地址请在素颜后台配置：<?=e(base_url().'/oauth.php')?></div>
  <div class="fg"><label>启用聚合登录</label><div class="field"><label class="sw"><input type="checkbox" name="suyan_enabled" value="1" <?=$cfg['suyan_enabled']?'checked':''?>><span class="track"></span><span class="sw-txt"><b>微信 / QQ / 抖音 / 微软 快捷登录</b><i>开启后前台显示第三方登录与绑定入口</i></span></label></div></div>
  <div class="fg"><label>接口地址</label><div class="field"><input type="text" name="suyan_api" value="<?=e($cfg['suyan_api'])?>" placeholder="https://u.suyanw.cn/connect.php"><div class="hint">填素颜后台显示的 https://u.suyanw.cn/ 或完整端点 https://u.suyanw.cn/connect.php 均可，系统会自动补全为 connect.php 端点</div></div></div>
  <div class="fg"><label>APPID</label><div class="field"><input type="text" name="suyan_appid" value="<?=e($cfg['suyan_appid'])?>"></div></div>
  <div class="fg"><label>APPKEY</label><div class="field"><input type="text" name="suyan_appkey" value="<?=e($cfg['suyan_appkey'])?>"></div></div>
  <div class="save-bar"><button class="btn primary" type="submit">保存聚合登录配置</button></div>
</div></form>

<!-- ===== v1.3.0 功能开关 ===== -->
<form method="post" id="features"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="act" value="features">
<div class="panel">
  <div class="panel-title"><?=ico('power',17)?>功能开关</div>
  <div class="panel-desc">控制前台各功能的开放状态，保存后即时生效。</div>
  <div class="fg"><label>社区功能</label><div class="field">
    <label class="sw"><input type="checkbox" name="enable_forum" value="1" <?=$cfg['enable_forum']?'checked':''?>><span class="track"></span><span class="sw-txt"><b>社区功能总开关</b><i>发帖 / 回复 / 点赞，关闭后前台隐藏社区入口</i></span></label>
    <div class="sw-deps">
      <label class="sw"><input type="checkbox" name="enable_msg" value="1" <?=$cfg['enable_msg']?'checked':''?>><span class="track"></span><span class="sw-txt"><b>站内消息</b><i>导航铃铛通知，依赖社区功能</i></span></label>
      <label class="sw"><input type="checkbox" name="enable_upload" value="1" <?=$cfg['enable_upload']?'checked':''?>><span class="track"></span><span class="sw-txt"><b>发帖图片上传</b><i>依赖社区功能与图床配置</i></span></label>
    </div>
  </div></div>
  <div class="fg"><label>注册</label><div class="field"><label class="sw"><input type="checkbox" name="enable_register" value="1" <?=$cfg['enable_register']?'checked':''?>><span class="track"></span><span class="sw-txt"><b>开放邮箱注册</b><i>关闭后注册页显示「暂停注册」</i></span></label></div></div>
  <div class="save-bar"><button class="btn primary" type="submit">保存功能开关</button></div>
</div></form>

<!-- ===== v1.3.0 邮件通知聚合 ===== -->
<form method="post" id="notify"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="act" value="notify">
<div class="panel">
  <div class="panel-title"><?=ico('mail',17)?>邮件通知聚合</div>
  <div class="panel-desc">站点无定时任务：点赞 / 回复 / 公告会先入站内消息，再由前台页面流量按聚合窗口合并为一封 HTML 摘要邮件（需先开启上方 SMTP）。</div>
  <div class="fg"><label>通知类型</label><div class="field">
    <label class="sw"><input type="checkbox" name="mail_notify_like" value="1" <?=$cfg['mail_notify_like']?'checked':''?>><span class="track"></span><span class="sw-txt"><b>点赞通知进邮件</b><i>帖子被点赞时汇总进摘要邮件</i></span></label>
    <label class="sw"><input type="checkbox" name="mail_notify_reply" value="1" <?=$cfg['mail_notify_reply']?'checked':''?>><span class="track"></span><span class="sw-txt"><b>回复通知进邮件</b><i>帖子被回复时汇总进摘要邮件</i></span></label>
    <label class="sw"><input type="checkbox" name="mail_notify_push" value="1" <?=$cfg['mail_notify_push']?'checked':''?>><span class="track"></span><span class="sw-txt"><b>公告推送进邮件</b><i>管理员推送公告时汇总进摘要邮件</i></span></label>
  </div></div>
  <div class="fg"><label>聚合窗口（分钟）</label><div class="field"><input type="number" name="mail_digest_mins" value="<?=(int)$cfg['mail_digest_mins']?>" min="1" max="720"><div class="hint">该窗口内的多条通知合并为一封，默认 30 分钟</div></div></div>
  <div class="save-bar"><button class="btn primary" type="submit">保存邮件通知配置</button></div>
</div></form>

<!-- ===== v1.4 图床 ===== -->
<form method="post" id="upload"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="act" value="upload">
<div class="panel">
  <div class="panel-title"><?=ico('img',17)?>图床（img.scdn.io） <span class="tag <?=CDN_API?'ok':'err'?>"><?=CDN_API?'已配置':'未配置'?></span></div>
  <div class="panel-desc">发帖图片上传到 <a href="https://img.scdn.io/api_docs.php" target="_blank" rel="noopener">img.scdn.io</a> 公共图床（无需 Token）。接口会自动压缩并返回外链，请勿在上传大图时关闭页面。</div>
  <div class="fg"><label>API 地址</label><div class="field"><input type="text" name="cdn_api" value="<?=e($cfg['cdn_api'])?>" placeholder="https://img.scdn.io/api/v1.php"><div class="hint">默认 https://img.scdn.io/api/v1.php</div></div></div>
  <div class="fg"><label>CDN 域名</label><div class="field"><input type="text" name="cdn_domain" value="<?=e($cfg['cdn_domain'])?>" placeholder="img.scdn.io"><div class="hint">外链使用的 CDN 域名，多个用英文逗号分隔（留空则由图床自动选择）。如 img.scdn.io,cloudflarecnimg.scdn.io</div></div></div>
  <div class="fg"><label>单图上限（MB）</label><div class="field"><input type="number" name="upload_max_mb" value="<?=(int)$cfg['upload_max_mb']?>" min="1" max="20"><div class="hint">同时受主机 upload_max_filesize 限制</div></div></div>
  <div class="save-bar"><button class="btn primary" type="submit">保存图床配置</button></div>
</div></form>

<!-- ===== v1.3.0 页脚配置 ===== -->
<form method="post" id="footer"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="act" value="footer">
<div class="panel">
  <div class="panel-title"><?=ico('link',17)?>页脚配置</div>
  <div class="panel-desc">黑色页脚的「关于 / 捐赠 / 友情链接」。板块的新增与改名请前往 <a href="boards.php">板块管理</a>。</div>
  <div class="fg"><label>关于（支持 HTML）</label><div class="field"><textarea name="footer_about" rows="3" style="width:100%;border:1.5px solid var(--border);border-radius:10px;padding:10px 12px;font-family:inherit;font-size:13.5px" placeholder="留空显示站点标语"><?=e($cfg['footer_about'])?></textarea><div class="hint">仅允许 a / br / strong 标签，其余标签将被过滤</div></div></div>
  <div class="fg"><label>捐赠链接</label><div class="field"><input type="text" name="footer_donate" value="<?=e($cfg['footer_donate'])?>" placeholder="如 paypro 收款页地址，留空隐藏入口"></div></div>
  <div class="fg"><label>友情链接</label><div class="field"><textarea name="footer_links" rows="3" style="width:100%;border:1.5px solid var(--border);border-radius:10px;padding:10px 12px;font-family:inherit;font-size:13.5px" placeholder="每行一个：名称|地址"><?=e(implode("\n",array_map(function($l){ return ($l['text']??'').'|'.($l['url']??''); },(array)$cfg['footer_links'])))?></textarea><div class="hint">格式「名称|地址」，一行一条；只写地址则名称同地址</div></div></div>
  <div class="save-bar"><button class="btn primary" type="submit">保存页脚配置</button></div>
</div></form>

<!-- ===== 调试模式 ===== -->
<form method="post" id="debug"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="act" value="debug">
<div class="panel">
  <div class="panel-title"><?=ico('power',17)?>调试模式 <span class="tag <?=DEBUG_MODE?'warn':'ok'?>"><?=DEBUG_MODE?'已开启':'已关闭'?></span></div>
  <div class="panel-desc">仅管理员可在后台开启或关闭。开启后：① 仅管理员可登录后台；② 页面显示详细 PHP 错误信息便于排查问题。排查完成后请及时关闭。</div>
  <div class="fg"><label>调试模式</label><div class="field"><label class="sw"><input type="checkbox" name="debug_mode" value="1" <?=$cfg['debug_mode']?'checked':''?>><span class="track"></span><span class="sw-txt"><b>显示详细错误信息</b><i>开启后仅管理员可登录后台，排查完成后请及时关闭</i></span></label></div></div>
  <div class="fg"><label>同步开启</label><div class="field"><label class="sw"><input type="checkbox" name="mail_debug_show_code" value="1" <?=$cfg['mail_debug_show_code']?'checked':''?>><span class="track"></span><span class="sw-txt"><b>验证码回显</b><i>发送失败时页面直接显示验证码，便于测试</i></span></label></div></div>
  <div class="save-bar"><button class="btn <?=DEBUG_MODE?'default':'primary'?>" type="submit"><?=DEBUG_MODE?'关闭调试模式':'开启调试模式'?></button>
  <span class="fl">当前：<?=DEBUG_MODE?'开启':'关闭'?></span></div>
</div></form>

<!-- ===== 发送测试邮件 ===== -->
<div class="panel">
  <div class="panel-title"><?=ico('send',17)?>发送测试邮件</div>
  <div class="panel-desc">使用当前 SMTP 配置向指定邮箱发送一封验证码测试邮件，用于验证发信是否正常。</div>
  <form method="post"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="act" value="test_mail">
  <div class="fg"><label>测试收件</label><div class="field"><input type="text" name="test_to" placeholder="输入测试收件邮箱" required></div></div>
  <div class="save-bar"><button class="btn default" type="submit">发送测试</button></div></form>
</div>
<?php include __DIR__.'/foot.php'; ?>