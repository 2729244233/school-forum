<?php
// ============ 校园论坛 - 全局配置文件 ============
// 站点默认参数在此；极验 / SMTP / 素颜聚合 等可通过「后台管理 → 系统设置」写入
// _data/settings.json 覆盖默认值，无需手动改本文件。本文件所有常量在加载时自动合并设置。

@mkdir(__DIR__ . '/_data', 0755, true);
define('SETTINGS_FILE', __DIR__ . '/_data/settings.json');
define('DB_SQLITE_FILE', __DIR__ . '/_data/forum.db');

function settings_defaults(){
  return [
    'site_name' => '校园论坛',
    'site_slogan_sub' => '通过一个论坛连接全校。按版块、热度和时间聚合内容，让每条动态全程可见。',
    'site_url' => '', // 留空自动识别，绑定域名后可填 https://bbs.xxx.cn
    'db_type' => 'mysql', // sqlite | mysql
    'db_mysql_host' => 'sql306.ccccocccc.cc',
    'db_mysql_name' => 'vh0_42169787_school',
    'db_mysql_user' => 'vh0_42169787',
    'db_mysql_pass' => '085279tsh',
    'mail_from' => 'noreply@example.com',
    'mail_from_name' => '校园论坛',
    'mail_use_smtp' => false,
    'mail_smtp_host' => 'smtp.qq.com',
    'mail_smtp_port' => 465,
    'mail_smtp_user' => '',
    'mail_smtp_pass' => '',
    'mail_debug_show_code' => true, // 上线后改 false；true 时发送失败会回显验证码便于测试
    'geetest_enabled' => false, // 极验 v4，申请 captcha_id/key 后开启
    'geetest_id' => '请填写极验captcha_id',
    'geetest_key' => '请填写极验captcha_key',
    'suyan_enabled' => false, // 素颜聚合登录（微信绑定/快捷登录），文档：https://u.suyanw.cn/doc.php
    'suyan_api' => 'https://u.suyanw.cn/connect.php',
    'suyan_appid' => '',
    'suyan_appkey' => '',
    // v1.4.2 聚合登录单通道开关：留空的通道前台不再展示/不可登录（后台仍可看到全部通道）
    'suyan_channels' => ['wx','qq','douyin','microsoft'],
    'code_expire' => 300, // 邮箱验证码有效期（秒）
    'cookie_days' => 14,
    'debug_mode' => true, // 调试模式：仅管理员可登录后台，开启后显示详细错误便于排查（部署排错期间为 true，修复后请改回 false）
    // ===== v1.3.0 新增配置 =====
    'boards' => ['综合交流','新生入学','二手交易','兼职实习','失物招领','表白墙'], // 板块（后台「板块管理」维护）
    'enable_forum' => true,    // 社区功能总开关（发帖 / 回复 / 点赞）
    'enable_msg' => true,      // 站内消息（铃铛通知）
    'enable_upload' => true,   // 发帖图片上传（img.scdn.io 图床）
    'enable_register' => true, // 开放注册
    'mail_notify_like' => true,  // 点赞通知是否进入邮件聚合
    'mail_notify_reply' => true, // 回复通知是否进入邮件聚合
    'mail_notify_push' => true,  // 公告推送是否进入邮件聚合
    // v1.4 图床：img.scdn.io 公共 API（文档 https://img.scdn.io/api_docs.php）
    'cdn_api' => 'https://img.scdn.io/api/v1.php', // 上传端点
    'cdn_domain' => 'img.scdn.io', // 外链 CDN 域名，多个用英文逗号分隔（留空则由图床自动选择）
    'upload_max_mb' => 4,   // 单图大小上限（MB），同时受主机 upload_max_filesize 限制
    'upload_video_mb' => 10, // v1.4.2 单个视频大小上限（MB）；图床会把 ≤10 秒视频转为 GIF/动态 WebP
    'notify_poll_secs' => 10, // 铃铛轮询间隔（秒）
    'mail_digest_mins' => 30, // 邮件聚合窗口（分钟），期间多条通知合并为一封
    'footer_about' => '',   // 页脚「关于」内容，允许 a/br/strong 标签
    'footer_donate' => '',  // 页脚「捐赠」链接（如 paypro 收款页地址）
    'footer_links' => [],   // 页脚友情链接 [{text,url}]
  ];
}
function settings_load(){
  $def=settings_defaults(); $j=[];
  if(is_file(SETTINGS_FILE)){ $raw=file_get_contents(SETTINGS_FILE); $j=json_decode($raw,true); if(!is_array($j)) $j=[]; }
  return array_merge($def,$j);
}
function settings_save(array $arr){
  $arr=array_merge(settings_load(),$arr);
  return file_put_contents(SETTINGS_FILE, json_encode($arr, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES), LOCK_EX)!==false;
}

$__CFG=settings_load();
define('SITE_NAME', $__CFG['site_name']);
define('SITE_SLOGAN_SUB', $__CFG['site_slogan_sub']);
define('SITE_URL', $__CFG['site_url']);
define('DB_TYPE', $__CFG['db_type']);
define('DB_MYSQL_HOST', $__CFG['db_mysql_host']);
define('DB_MYSQL_NAME', $__CFG['db_mysql_name']);
define('DB_MYSQL_USER', $__CFG['db_mysql_user']);
define('DB_MYSQL_PASS', $__CFG['db_mysql_pass']);
define('MAIL_FROM', $__CFG['mail_from']);
define('MAIL_FROM_NAME', $__CFG['mail_from_name']);
define('MAIL_USE_SMTP', (bool)$__CFG['mail_use_smtp']);
define('MAIL_SMTP_HOST', $__CFG['mail_smtp_host']);
define('MAIL_SMTP_PORT', (int)$__CFG['mail_smtp_port']);
define('MAIL_SMTP_USER', $__CFG['mail_smtp_user']);
define('MAIL_SMTP_PASS', $__CFG['mail_smtp_pass']);
define('MAIL_DEBUG_SHOW_CODE', (bool)$__CFG['mail_debug_show_code']);
define('GEETEST_ENABLED', (bool)$__CFG['geetest_enabled']);
define('GEETEST_ID', $__CFG['geetest_id']);
define('GEETEST_KEY', $__CFG['geetest_key']);
define('SUYAN_ENABLED', (bool)$__CFG['suyan_enabled']);
define('SUYAN_API', $__CFG['suyan_api']);
define('SUYAN_APPID', $__CFG['suyan_appid']);
define('SUYAN_APPKEY', $__CFG['suyan_appkey']);
// v1.4.2 已启用的聚合登录通道（前台过滤用；admin/users.php 反查仍用全量 suyan_types()）
define('SUYAN_CHANNELS', (is_array($__CFG['suyan_channels']) && $__CFG['suyan_channels'])
  ? array_values(array_map('strval',$__CFG['suyan_channels'])) : ['wx','qq','douyin','microsoft']);
define('CODE_EXPIRE', (int)$__CFG['code_expire']);
define('COOKIE_DAYS', (int)$__CFG['cookie_days']);
define('DEBUG_MODE', (bool)$__CFG['debug_mode']);
// ===== v1.3.0 新增常量导出 =====
define('BOARDS', (is_array($__CFG['boards']) && $__CFG['boards']) ? array_values($__CFG['boards']) : ['综合交流','新生入学','二手交易','兼职实习','失物招领','表白墙']);
define('ENABLE_FORUM', (bool)$__CFG['enable_forum']);
define('ENABLE_MSG', (bool)$__CFG['enable_msg']);
define('ENABLE_UPLOAD', (bool)$__CFG['enable_upload']);
define('ENABLE_REGISTER', (bool)$__CFG['enable_register']);
define('MAIL_NOTIFY_LIKE', (bool)$__CFG['mail_notify_like']);
define('MAIL_NOTIFY_REPLY', (bool)$__CFG['mail_notify_reply']);
define('MAIL_NOTIFY_PUSH', (bool)$__CFG['mail_notify_push']);
define('CDN_API', (string)$__CFG['cdn_api']);
define('CDN_DOMAIN', (string)$__CFG['cdn_domain']);
define('UPLOAD_MAX_MB', max(1, (int)$__CFG['upload_max_mb']));
define('UPLOAD_VIDEO_MB', max(1, (int)$__CFG['upload_video_mb']));
define('NOTIFY_POLL_SECS', max(5, (int)$__CFG['notify_poll_secs']));
define('MAIL_DIGEST_MINS', max(1, (int)$__CFG['mail_digest_mins']));
define('FOOTER_ABOUT', (string)$__CFG['footer_about']);
define('FOOTER_DONATE', (string)$__CFG['footer_donate']);
define('FOOTER_LINKS', is_array($__CFG['footer_links']) ? $__CFG['footer_links'] : []);