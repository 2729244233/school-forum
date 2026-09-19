<?php
require_once __DIR__ . '/config.php';
session_start();
date_default_timezone_set('Asia/Shanghai');

// 调试模式：仅管理员可在后台开启/关闭，开启后显示详细错误便于排查
if(defined('DEBUG_MODE') && DEBUG_MODE){ ini_set('display_errors','1'); error_reporting(E_ALL); }

// 全局异常/错误兜底：避免白屏 500，给出可读提示（调试模式显示完整错误）
// 无论是否开启调试，都会把详情写入 _data/error.log 便于排查。
function _err_log($txt){
  @file_put_contents(__DIR__.'/_data/error.log', '['.date('Y-m-d H:i:s').'] '.$txt."\n", FILE_APPEND | LOCK_EX);
}
function _err_page($title,$msg,$detail=''){
  @http_response_code(500);
  if(headers_sent()) return;
  echo '<!doctype html><html lang="zh-CN"><head><meta charset="utf-8"><title>系统提示</title>'
     .'<style>body{font-family:-apple-system,"PingFang SC","Microsoft YaHei",sans-serif;background:#F6F5FA;color:#211F33;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0}'
     .'.b{background:#fff;border:1px solid #EAE8F3;border-radius:16px;box-shadow:0 16px 48px rgba(60,40,160,.12);padding:36px 40px;max-width:600px;width:92%}.b h1{font-size:19px;margin:0 0 12px;color:#B4232A}'
     .'.b p{font-size:14px;line-height:1.7;color:#55536B;word-break:break-all}.b .code{font-family:monospace;font-size:12px;background:#F6F5FA;border:1px solid #EAE8F3;border-radius:8px;padding:10px 12px;color:#B4232A;margin-top:10px;overflow-wrap:break-word}'
     .'a{color:#6C4CF6;text-decoration:none;font-weight:600}</style></head><body><div class="b"><h1>'.$title.'</h1><p>'.$msg.'</p>';
  if($detail) echo $detail;
  echo '<p><a href="./">返回首页</a>';
  if(defined('DEBUG_MODE') && DEBUG_MODE) echo ' · <a href="install.php">环境自检</a>';
  echo '</p></div></body></html>';
}
function _err_format($type,$message,$file,$line){ return "运行出错 [$type] $message  (in $file, line $line)"; }
function _err_line($type,$m,$f,$l){ return '<div class="code">'.e($m).'<br>'.e($f.' #'.$l).'</div>'; }
set_exception_handler(function($ex){
  _err_log(_err_format('EXCEPTION',$ex->getMessage(),$ex->getFile(),$ex->getLine()));
  $d=defined('DEBUG_MODE')&&DEBUG_MODE;
  $msg=$d?'异常：'.e($ex->getMessage()).'<div class="code">'.e($ex->getFile().' #'.$ex->getLine()).'</div>'
         :'页面运行出错，详情已记录到 _data/error.log。';
  _err_page('系统错误',$msg);
});
register_shutdown_function(function(){
  $e=error_get_last();
  if($e && in_array($e['type'],[E_ERROR,E_PARSE,E_CORE_ERROR,E_COMPILE_ERROR])){
    _err_log(_err_format('FATAL',$e['message'],$e['file'],$e['line']));
    $d=defined('DEBUG_MODE')&&DEBUG_MODE;
    $msg=$d?_err_line('FATAL',$e['message'],$e['file'],$e['line'])
            :'页面运行出错，详情已记录到 _data/error.log。';
    _err_page('系统错误',$msg);
  }
});

// ---- IconPark 风格 SVG 图标（线性 / 1.8px 圆角描边，全站统一） ----
function ico($n,$s=18){
  static $I=[
    'home'=>'<path d="M4 10.5 12 4l8 6.5V19a1.5 1.5 0 0 1-1.5 1.5h-3.2v-5.6H8.7v5.6H5.5A1.5 1.5 0 0 1 4 19z"/>',
    'grid'=>'<rect x="4" y="4" width="7" height="7" rx="1.6"/><rect x="13" y="4" width="7" height="7" rx="1.6"/><rect x="4" y="13" width="7" height="7" rx="1.6"/><rect x="13" y="13" width="7" height="7" rx="1.6"/>',
    'fire'=>'<path d="M12 21c3.9 0 6.5-2.5 6.5-6.1 0-2.5-1.4-4.6-3-6.4-.4 1.3-1.1 2.3-2.1 2.9.3-2.9-.9-6-3.4-7.9.2 2.8-.9 4.4-2.3 6.1-1.2 1.4-2.2 3.1-2.2 5.3C5.5 18.5 8.1 21 12 21z"/>',
    'user'=>'<circle cx="12" cy="8" r="3.6"/><path d="M4.8 20c.9-3.4 3.8-5.2 7.2-5.2s6.3 1.8 7.2 5.2"/>',
    'users'=>'<circle cx="9" cy="8.5" r="3.2"/><path d="M3.5 19.5c.7-3 2.9-4.7 5.5-4.7s4.8 1.7 5.5 4.7"/><path d="M15.5 5.7a3.2 3.2 0 0 1 0 5.7M17.5 15.1c1.6.6 2.7 2 3.1 4"/>',
    'gear'=>'<circle cx="12" cy="12" r="3"/><path d="M19.4 13.5a7.6 7.6 0 0 0 0-3l2-1.5-2-3.4-2.3.9a7.7 7.7 0 0 0-2.6-1.5L14 2h-4l-.5 2.5a7.7 7.7 0 0 0-2.6 1.5l-2.3-.9-2 3.4 2 1.5a7.6 7.6 0 0 0 0 3l-2 1.5 2 3.4 2.3-.9a7.7 7.7 0 0 0 2.6 1.5L10 22h4l.5-2.5a7.7 7.7 0 0 0 2.6-1.5l2.3.9 2-3.4z"/>',
    'dash'=>'<path d="M4 18a8 8 0 1 1 16 0"/><path d="M12 14l4-5"/><circle cx="12" cy="14" r="1.4"/>',
    'file'=>'<path d="M7 3h7l4 4v12.5A1.5 1.5 0 0 1 16.5 21h-9A1.5 1.5 0 0 1 6 19.5v-15A1.5 1.5 0 0 1 7.5 3z"/><path d="M14 3v4h4"/><path d="M9.5 12h5M9.5 15.5h5"/>',
    'logout'=>'<path d="M14 4h-8.5A1.5 1.5 0 0 0 4 5.5v13A1.5 1.5 0 0 0 5.5 20H14"/><path d="M10 12h10M17 8.5l3.5 3.5-3.5 3.5"/>',
    'back'=>'<path d="M10 20h8.5a1.5 1.5 0 0 0 1.5-1.5v-13A1.5 1.5 0 0 0 18.5 4H10"/><path d="M14 12H4M7 8.5 3.5 12 7 15.5"/>',
    'search'=>'<circle cx="11" cy="11" r="6"/><path d="m20 20-4.6-4.6"/>',
    'trash'=>'<path d="M5 7h14M10 7V5a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2"/><path d="M6.5 7 7.4 19a1.5 1.5 0 0 0 1.5 1.4h6.2a1.5 1.5 0 0 0 1.5-1.4L17.5 7"/><path d="M10 11v5M14 11v5"/>',
    'edit'=>'<path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4z"/>',
    'plus'=>'<path d="M12 5v14M5 12h14"/>',
    'mail'=>'<rect x="3.5" y="5.5" width="17" height="13" rx="1.8"/><path d="m4.5 7.5 7.5 5.5 7.5-5.5"/>',
    'shield'=>'<path d="M12 3 5 5.8v5.4c0 4.4 2.9 7.6 7 9.3 4.1-1.7 7-4.9 7-9.3V5.8z"/><path d="m9 11.6 2.2 2.2 3.8-4"/>',
    'wechat'=>'<path d="M10 4C6.1 4 3 6.6 3 9.9c0 1.8 1 3.4 2.5 4.5L4.8 17l2.6-1.3c.8.2 1.6.3 2.6.3"/><path d="M21 14.6c0-2.9-2.7-5.2-6-5.2s-6 2.3-6 5.2 2.7 5.2 6 5.2c.9 0 1.7-.1 2.4-.4L20 20.5l-.6-2.3A5 5 0 0 0 21 14.6z"/>',
    'qq'=>'<path d="M12 3c-3.2 0-5.4 2.5-5.4 6.1 0 1 .2 1.9.5 2.7-.6.7-1.5 1.8-1.5 2.8 0 .6.5 1 1.1.9.5-.1 1-.4 1.4-.7.6.4 1.4.8 2.2 1-1.2.4-2.8 1.2-2.8 2.6 0 1.6 2 2.6 4.5 2.6s4.5-1 4.5-2.6c0-1.4-1.6-2.2-2.8-2.6.8-.2 1.6-.6 2.2-1 .4.3.9.6 1.4.7.6.1 1.1-.3 1.1-.9 0-1-.9-2.1-1.5-2.8.3-.8.5-1.7.5-2.7C17.4 5.5 15.2 3 12 3z"/><path d="M10 8.8v2M14 8.8v2"/>',
    'douyin'=>'<circle cx="7.5" cy="17" r="3.2"/><path d="M10.7 17V4.5"/><path d="M10.7 4.5c.9 2.8 3 4.5 5.8 4.8"/><path d="M10.7 8.6c.8 2 2.5 3.4 4.7 3.8"/>',
    'microsoft'=>'<rect x="4" y="4" width="7" height="7" rx="1"/><rect x="13" y="4" width="7" height="7" rx="1"/><rect x="4" y="13" width="7" height="7" rx="1"/><rect x="13" y="13" width="7" height="7" rx="1"/>',
    'link'=>'<path d="M10 14a5 5 0 0 0 7.5.5l2-2a5 5 0 0 0-7-7l-1.2 1"/><path d="M14 10a5 5 0 0 0-7.5-.5l-2 2a5 5 0 0 0 7 7l1.2-1"/>',
    'eye'=>'<path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12z"/><circle cx="12" cy="12" r="2.8"/>',
    'send'=>'<path d="M21 3 10.5 13.5"/><path d="M21 3 14 21l-3.5-7.5L3 10z"/>',
    'close'=>'<path d="M6 6l12 12M18 6 6 18"/>',
    'font'=>'<path d="M5 19 11.5 5h1L19 19"/><path d="M7.5 14h9"/>',
    'textsize'=>'<path d="M4 19 9 7h.8L14 19"/><path d="M6 15h6"/><path d="M15 12l3-4 3 4"/>',
    'alert'=>'<circle cx="12" cy="12" r="8.5"/><path d="M12 8v5"/><circle cx="12" cy="16" r=".5" fill="currentColor"/>',
    'check'=>'<circle cx="12" cy="12" r="8.5"/><path d="m8.5 12.2 2.4 2.4 4.6-5"/>',
    'info'=>'<circle cx="12" cy="12" r="8.5"/><path d="M12 11v5"/><circle cx="12" cy="8" r=".5" fill="currentColor"/>',
    'message'=>'<path d="M21 12a8 8 0 0 1-8 8H4l1.7-3.4A8 8 0 1 1 21 12z"/>',
    'clock'=>'<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/>',
    'right'=>'<path d="M5 12h14M13 6l6 6-6 6"/>',
    'left'=>'<path d="M19 12H5M11 6l-6 6 6 6"/>',
    'refresh'=>'<path d="M20 12a8 8 0 1 1-2.5-5.8"/><path d="M20 3v4h-4"/>',
    'lock'=>'<rect x="5.5" y="10.5" width="13" height="9.5" rx="1.8"/><path d="M8.5 10.5V7.8a3.5 3.5 0 0 1 7 0v2.7"/>',
    'power'=>'<path d="M12 3v8"/><path d="M6.3 6.5a8 8 0 1 0 11.4 0"/>',
    'key'=>'<circle cx="8" cy="15" r="4.5"/><path d="m11 12 8.5-8.5M17 5l2.5 2.5M14 8l2 2"/>',
    'rocket'=>'<path d="M12 15c-1.5-.5-2.5-1.5-3-3C10 7 13 4 19 3c-1 6-4 9.5-7 12z"/><path d="M9 12c-2 .5-3.5 2-4 5 3-.5 4.5-2 5-4"/><circle cx="14.5" cy="8.5" r="1.2"/>',
    'like'=>'<path d="M6.5 10.5v10H4.4A1.4 1.4 0 0 1 3 19.1v-7.2a1.4 1.4 0 0 1 1.4-1.4h2.1z"/><path d="M6.5 11.5 10 4.2c1.8.2 3 1.5 2.9 3.3l-.2 2.2h5.5c1.3 0 2.3 1.2 2 2.4l-1.3 5.9a2.2 2.2 0 0 1-2.2 1.7H6.5"/>',
    'share'=>'<circle cx="6" cy="12" r="2.6"/><circle cx="17.5" cy="5.5" r="2.6"/><circle cx="17.5" cy="18.5" r="2.6"/><path d="m8.4 10.8 6.8-4M8.4 13.2l6.8 4"/>',
    'bell'=>'<path d="M6 10a6 6 0 0 1 12 0c0 4 1.3 5.4 2 6.2H4c.7-.8 2-2.2 2-6.2z"/><path d="M10 19.6a2.1 2.1 0 0 0 4 0"/>',
    'img'=>'<rect x="3.5" y="5" width="17" height="14" rx="2"/><circle cx="9" cy="10" r="1.6"/><path d="m5.5 17.5 4.5-4.5 3 3 2.5-2.5 3 3"/>',
  ];
  $d=$I[$n]??$I['info'];
  return '<svg class="svg-ico" width="'.$s.'" height="'.$s.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$d.'</svg>';
}

function site_url($p=''){ $b = defined('SITE_URL') && SITE_URL ? rtrim(SITE_URL,'/') : ''; return $b . '/' . ltrim($p,'/'); }
function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
// 应用根的 URL 路径部分（如 '' 或 '/forum'），与当前脚本位于根目录还是 admin/ 子目录无关
function app_base_path(){
  static $b=null; if($b!==null) return $b;
  $root=str_replace('\\','/',__DIR__);          // common.php 所在目录 = 应用根（文件系统）
  $sf=str_replace('\\','/',$_SERVER['SCRIPT_FILENAME']??'');
  $sn=str_replace('\\','/',$_SERVER['SCRIPT_NAME']??'');
  $b='';
  // 首选：脚本真实路径相对应用根的部分，在 URL 中去掉同样的一段
  if($sf!=='' && $sn!=='' && stripos($sf,$root.'/')===0){
    $rel=str_replace('\\','/',substr($sf,strlen($root)+1)); // 如 login.php 或 admin/settings.php
    if($rel!=='' && substr($sn,-strlen($rel))===$rel) $b=rtrim(substr($sn,0,-strlen($rel)),'/');
  }
  // 兜底：DOCUMENT_ROOT 映射
  if($b===''){
    $doc=str_replace('\\','/',(string)@realpath((string)($_SERVER['DOCUMENT_ROOT']??'')));
    if($doc!=='' && stripos($root,$doc)===0) $b=rtrim(substr($root,strlen($doc)),'/');
  }
  if($b==='/') $b='';
  return $b;
}
function base_url(){
  if(defined('SITE_URL')&&SITE_URL) return rtrim(SITE_URL,'/');
  $https=(isset($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!==''&&$_SERVER['HTTPS']!=='off')
    ||(($_SERVER['HTTP_X_FORWARDED_PROTO']??'')==='https')   // CDN/反代
    ||(($_SERVER['HTTP_X_FORWARDED_SSL']??'')==='on')
    ||(($_SERVER['SERVER_PORT']??'')==='443');
  return ($https?'https://':'http://').$_SERVER['HTTP_HOST'].app_base_path();
}

function db(){
  static $pdo=null; if($pdo) return $pdo;
  $drivers=PDO::getAvailableDrivers();
  if(DB_TYPE==='mysql'){
    if(!in_array('mysql',$drivers)) throw new Exception('服务器未启用 pdo_mysql 扩展，请在 php.ini 开启 extension=pdo_mysql，或改用 SQLite（后台无法改，请在 config.php DB_TYPE 设为 sqlite）');
    $pdo=new PDO('mysql:host='.DB_MYSQL_HOST.';dbname='.DB_MYSQL_NAME.';charset=utf8mb4',DB_MYSQL_USER,DB_MYSQL_PASS,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    // 每次连接都确保 4 张表存在（CREATE IF NOT EXISTS 幂等）。
    // 兼容"旧文件/半成品建表"导致的缺表，例如 users 已存在但 posts 缺失。
    try{ init_schema($pdo); }
    catch(Exception $e){ throw new Exception('数据库初始化失败：'.$e->getMessage()); }
  }else{
    if(!in_array('sqlite',$drivers)) throw new Exception('服务器未启用 pdo_sqlite 扩展：请在 php.ini 开启 extension=pdo_sqlite（绝大多数虚拟主机默认已开启）后再访问 install.php');
    @mkdir(__DIR__.'/_data',0755,true);
    $pdo=new PDO('sqlite:'.DB_SQLITE_FILE); $pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
    // 每次连接都跑（CREATE IF NOT EXISTS 幂等），保证旧库也能拿到新增表
    try{ init_schema($pdo); }
    catch(Exception $e){ throw new Exception('数据库初始化失败：'.$e->getMessage()); }
  }
  return $pdo;
}
function init_schema($pdo){
  $isMysql = DB_TYPE==='mysql';
  $ai = $isMysql ? 'INT AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
  $ma = $isMysql ? 'TINYINT' : 'INTEGER';
  // MySQL 显式指定 utf8mb4，避免因库默认字符集非 utf8 导致中文默认值报 1067
  $tcs = $isMysql ? ' DEFAULT CHARSET=utf8mb4' : '';
  $pdo->exec("CREATE TABLE IF NOT EXISTS users(id $ai, email VARCHAR(128) UNIQUE NOT NULL, username VARCHAR(64) NOT NULL, password_hash VARCHAR(255) NOT NULL, wechat_openid VARCHAR(128) DEFAULT '', wechat_name VARCHAR(128) DEFAULT '', status INT DEFAULT 1, is_admin $ma DEFAULT 0, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)$tcs");
  $pdo->exec("CREATE TABLE IF NOT EXISTS email_codes(id $ai, email VARCHAR(128) NOT NULL, code VARCHAR(10) NOT NULL, scene VARCHAR(20) DEFAULT 'reg', expire_at INT NOT NULL, created_at INT NOT NULL)$tcs");
  $pdo->exec("CREATE TABLE IF NOT EXISTS posts(id $ai, uid INT NOT NULL, board VARCHAR(32) DEFAULT '综合交流', title VARCHAR(128) NOT NULL, content TEXT NOT NULL, views INT DEFAULT 0, font_family VARCHAR(64) DEFAULT '', font_size VARCHAR(16) DEFAULT '', created_at DATETIME DEFAULT CURRENT_TIMESTAMP)$tcs");
  $pdo->exec("CREATE TABLE IF NOT EXISTS replies(id $ai, pid INT NOT NULL, uid INT NOT NULL, content TEXT NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)$tcs");
  // 第三方（聚合登录）账号绑定表：type+social_uid 全局唯一，uid 关联 users.id
  $uniq = $isMysql ? ', UNIQUE KEY uq_social (type,social_uid)' : ', UNIQUE(type,social_uid)';
  $pdo->exec("CREATE TABLE IF NOT EXISTS social_accounts(id $ai, uid INT NOT NULL, type VARCHAR(20) NOT NULL, social_uid VARCHAR(128) NOT NULL, nickname VARCHAR(128) DEFAULT '', created_at DATETIME DEFAULT CURRENT_TIMESTAMP$uniq)$tcs");
  // ===== v1.3.0 新表（时间字段统一 INT 时间戳，规避双驱动日期函数差异） =====
  $uniqLike = $isMysql ? ', UNIQUE KEY uq_like (target_type,target_id,uid)' : ', UNIQUE(target_type,target_id,uid)';
  $pdo->exec("CREATE TABLE IF NOT EXISTS likes(id $ai, target_type VARCHAR(10) NOT NULL DEFAULT 'post', target_id INT NOT NULL, uid INT NOT NULL, created_at INT NOT NULL$uniqLike)$tcs");
  $pdo->exec("CREATE TABLE IF NOT EXISTS notifications(id $ai, uid INT NOT NULL, type VARCHAR(10) DEFAULT 'system', actor_id INT DEFAULT 0, post_id INT DEFAULT 0, reply_id INT DEFAULT 0, content TEXT, is_read $ma DEFAULT 0, mailed $ma DEFAULT 0, created_at INT NOT NULL)$tcs");
  $pdo->exec("CREATE TABLE IF NOT EXISTS announcements(id $ai, title VARCHAR(128) NOT NULL, content TEXT, btn_text VARCHAR(64) DEFAULT '', btn_url VARCHAR(512) DEFAULT '', btn_local $ma DEFAULT 0, active $ma DEFAULT 1, created_at INT NOT NULL)$tcs");
  // MySQL：把（可能来自旧建表的）各表统一转为 utf8mb4，保证中文数据正确存取
  if($isMysql){
    foreach(['users','email_codes','posts','replies','social_accounts','likes','notifications','announcements'] as $t){
      try{ @$pdo->exec("ALTER TABLE `$t` CONVERT TO CHARACTER SET utf8mb4"); }catch(Exception $e){}
    }
  }
  // 旧库迁移：补充 is_admin 字段 + 发帖字体相关字段
  try{ $pdo->exec("ALTER TABLE users ADD COLUMN is_admin $ma DEFAULT 0"); }catch(Exception $e){}
  try{ $pdo->exec("ALTER TABLE posts ADD COLUMN font_family VARCHAR(64) DEFAULT ''"); }catch(Exception $e){}
  try{ $pdo->exec("ALTER TABLE posts ADD COLUMN font_size VARCHAR(16) DEFAULT ''"); }catch(Exception $e){}
  // ===== v1.3.0 旧表补列（逐条 try/catch 幂等） =====
  try{ $pdo->exec("ALTER TABLE replies ADD COLUMN parent_id INT DEFAULT 0"); }catch(Exception $e){}
  try{ $pdo->exec("ALTER TABLE users ADD COLUMN is_official $ma DEFAULT 0"); }catch(Exception $e){}
  try{ $pdo->exec("ALTER TABLE users ADD COLUMN is_operator $ma DEFAULT 0"); }catch(Exception $e){}
  try{ $pdo->exec("ALTER TABLE users ADD COLUMN avatar VARCHAR(512) DEFAULT ''"); }catch(Exception $e){}
  try{ $pdo->exec("ALTER TABLE users ADD COLUMN nick_month VARCHAR(7) DEFAULT ''"); }catch(Exception $e){}
  try{ $pdo->exec("ALTER TABLE users ADD COLUMN nick_count INT DEFAULT 0"); }catch(Exception $e){}
  try{ $pdo->exec("ALTER TABLE posts ADD COLUMN is_announce $ma DEFAULT 0"); }catch(Exception $e){}
  try{ $pdo->exec("ALTER TABLE posts ADD COLUMN announce_btns TEXT"); }catch(Exception $e){}
  try{ $pdo->exec("ALTER TABLE posts ADD COLUMN first_image VARCHAR(512) DEFAULT ''"); }catch(Exception $e){}
  // ===== v1.4.1 公告多渠道（多选：帖子/推送/弹窗，可同时投放） =====
  try{ $pdo->exec("ALTER TABLE announcements ADD COLUMN chan_post $ma DEFAULT 0"); }catch(Exception $e){}
  try{ $pdo->exec("ALTER TABLE announcements ADD COLUMN chan_push $ma DEFAULT 0"); }catch(Exception $e){}
  try{ $pdo->exec("ALTER TABLE announcements ADD COLUMN chan_popup $ma DEFAULT 0"); }catch(Exception $e){}
  // 推送出去的标题（公告标题与推送标题可分开）
  try{ $pdo->exec("ALTER TABLE announcements ADD COLUMN push_title VARCHAR(128) DEFAULT ''"); }catch(Exception $e){}
  try{ $pdo->exec("ALTER TABLE announcements ADD COLUMN is_push $ma DEFAULT 0"); }catch(Exception $e){}
  // ===== v1.3.0 索引（MySQL 无 CREATE INDEX IF NOT EXISTS，统一 try/catch 幂等） =====
  $idxs=[
    "CREATE INDEX idx_posts_board ON posts(board)",
    "CREATE INDEX idx_posts_views ON posts(views)",
    "CREATE INDEX idx_posts_uid ON posts(uid)",
    "CREATE INDEX idx_posts_announce ON posts(is_announce)",
    "CREATE INDEX idx_replies_pid_parent ON replies(pid,parent_id)",
    "CREATE INDEX idx_likes_created ON likes(created_at)",
    "CREATE INDEX idx_likes_uid ON likes(uid)",
    "CREATE INDEX idx_notif_unread ON notifications(uid,is_read)",
    "CREATE INDEX idx_notif_mail ON notifications(mailed,created_at)",
    "CREATE INDEX idx_ann_active ON announcements(active)",
    "CREATE INDEX idx_ann_popup ON announcements(chan_popup,active)",
  ];
  foreach($idxs as $sql){ try{ $pdo->exec($sql); }catch(Exception $e){} }
  // 默认数据：仅创建管理员账号（不预置任何演示帖，帖子由真实用户发布）
  try{
    $a=$pdo->query("SELECT id FROM users WHERE email='admin@school.cn'")->fetchColumn();
    if(!$a){ $pdo->exec("INSERT INTO users(email,username,password_hash,is_admin) VALUES('admin@school.cn','管理员','".password_hash('123456',PASSWORD_DEFAULT)."',1)"); }
    else { $pdo->prepare("UPDATE users SET is_admin=1 WHERE id=?")->execute([$a]); }
    // 一次性清理历史版本自动生成的预展示帖（含其回复）
    $demoTitles="('新生报到攻略：宿舍/食堂/选课全指南','毕业季二手出：自行车/教材/小电器','校内助管/食堂勤工俭学岗位汇总')";
    $ids=$pdo->query("SELECT id FROM posts WHERE title IN $demoTitles")->fetchAll(PDO::FETCH_COLUMN);
    if($ids){
      $in=implode(',',array_map('intval',$ids));
      $pdo->exec("DELETE FROM replies WHERE pid IN ($in)");
      $pdo->exec("DELETE FROM posts WHERE id IN ($in)");
    }
  }catch(Exception $ex){}
  // 旧数据迁移：users.wechat_openid → social_accounts(type=wx)，幂等可重复执行
  try{
    $rows=$pdo->query("SELECT id,wechat_openid,wechat_name FROM users WHERE wechat_openid IS NOT NULL AND wechat_openid<>''")->fetchAll(PDO::FETCH_ASSOC);
    foreach($rows as $r){
      $c=$pdo->prepare("SELECT id FROM social_accounts WHERE type='wx' AND social_uid=?");
      $c->execute([$r['wechat_openid']]);
      if(!$c->fetchColumn()){
        $pdo->prepare("INSERT INTO social_accounts(uid,type,social_uid,nickname) VALUES(?, 'wx',?,?)")->execute([(int)$r['id'],$r['wechat_openid'],$r['wechat_name']]);
      }
    }
  }catch(Exception $ex){}
}
function current_user(){
  if(!empty($_SESSION['uid'])){ try{ $s=db()->prepare("SELECT * FROM users WHERE id=?"); $s->execute([$_SESSION['uid']]); $u=$s->fetch(PDO::FETCH_ASSOC); if($u&&$u['status']==1) return $u; }catch(Exception $ex){} }
  return null;
}
function require_login(){ $u=current_user(); if(!$u){ header('Location: login.php'); exit; } return $u; }
// ---- v1.3.0 角色权限体系 ----
// 超管：预置账号 admin@school.cn，兼三种身份；赋予「官方」身份仅超管可操作
function is_super_admin($u){ return $u && strcasecmp((string)($u['email']??''),'admin@school.cn')===0; }
// 返回某用户的身份角色数组：admin(管理员)/official(官方)/operator(运营)
function staff_roles($u){
  $r=[];
  if(!$u) return $r;
  if(!empty($u['is_admin'])) $r[]='admin';
  if(!empty($u['is_official'])) $r[]='official';
  if(!empty($u['is_operator'])) $r[]='operator';
  if(is_super_admin($u)){ foreach(['admin','official','operator'] as $x) if(!in_array($x,$r,true)) $r[]=$x; }
  return $r;
}
function is_staff($u=null){ $u=$u?:current_user(); return (bool)staff_roles($u); }
// 后台各页面统一入口：须已通过 /admin/login.php 登录（admin_ok 标记），且具备指定角色。
// 前台登录的会话没有 admin_ok 标记，即使身份是工作人员也无法直接进入后台。
function require_role($role){
  $u=current_user();
  if(empty($_SESSION['admin_ok']) || !in_array($role,staff_roles($u),true)){ header('Location: '.base_url().'/admin/login.php'); exit; }
  return $u;
}
function require_any(...$roles){
  $u=current_user(); $rs=staff_roles($u);
  foreach($roles as $r) if(in_array($r,$rs,true)){
    if(empty($_SESSION['admin_ok'])){ header('Location: '.base_url().'/admin/login.php'); exit; }
    return $u;
  }
  header('Location: '.base_url().'/admin/login.php'); exit;
}
function require_staff(){ return require_any('admin','official','operator'); }
function require_admin(){ return require_role('admin'); }
function is_admin(){ $u=current_user(); return $u && !empty($u['is_admin']); }
function csrf_token(){ if(empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(16)); return $_SESSION['csrf']; }
function check_csrf(){ return isset($_POST['csrf'])&&isset($_SESSION['csrf'])&&hash_equals($_SESSION['csrf'],$_POST['csrf']); }

// ---- 邮箱验证码 ----
function send_mail_code($email,$code,$scene='注册'){
  $subject="【".SITE_NAME."】$scene 验证码：$code";
  $body="{$scene}验证码：{$code}，有效期".(CODE_EXPIRE/60)."分钟。如非本人操作请忽略。——".SITE_NAME;
  $html=mail_code_template($scene,$code); // v1.4 验证码邮件 HTML（与网站同款蓝白样式）
  if (MAIL_USE_SMTP) {
    smtp_send($email, $subject, $body, $html);
    return true;
  }
  // 主机 mail() 直发：同样发 multipart/alternative（纯文本回退 + HTML）
  $bnd='=F'.md5(uniqid('',true));
  $headers="From: ".MAIL_FROM_NAME." <".MAIL_FROM.">\r\nMIME-Version: 1.0\r\nContent-Type: multipart/alternative; boundary=\"$bnd\"";
  $mime="--$bnd\r\nContent-Type: text/plain; charset=utf-8\r\n\r\n".$body
       ."\r\n--$bnd\r\nContent-Type: text/html; charset=utf-8\r\n\r\n".$html."\r\n--$bnd--";
  if(!@mail($email,$subject,$mime,$headers)) throw new Exception('主机 mail() 发送失败（多数虚拟主机已禁用该函数），请到后台「系统设置 → 邮箱」开启 SMTP');
  return true;
}
// $html 非空时发送 multipart/alternative（纯文本回退 + HTML base64 part），纯文本调用点不受影响
function smtp_send($to,$sub,$body,$html=''){
  $host=strpos(MAIL_SMTP_HOST,'://')===false ? 'ssl://'.MAIL_SMTP_HOST : MAIL_SMTP_HOST;
  $fp=fsockopen($host,MAIL_SMTP_PORT,$en,$es,10);
  if(!$fp) throw new Exception('SMTP连接失败：'.MAIL_SMTP_HOST.':'.MAIL_SMTP_PORT.' '.$es.'（错误码 '.$en.'）');
  // 读取一行/多行响应（以 "250 " 空格结尾判定结束）
  $rd=function()use($fp){
    $out='';
    while(($l=fgets($fp,512))!==false){ $out.=$l; if(strlen($l)>=4 && $l[3]===' ') break; }
    return trim($out);
  };
  // 发送命令并校验响应码，失败时抛出服务器原始回复，避免“假成功”
  $cmd=function($c,$ok)use($fp,$rd){
    if($c!==null) fputs($fp,$c."\r\n");
    $r=$rd();
    if(!preg_match('/^(\d{3})/',$r,$m) || !in_array($m[1],(array)$ok,true)) throw new Exception('SMTP 服务器拒绝：'.($r?:'无响应'));
    return $r;
  };
  $cmd(null,['220']);
  $cmd('EHLO localhost',['250']);
  $cmd('AUTH LOGIN',['334']);
  $cmd(base64_encode(MAIL_SMTP_USER),['334']);
  $cmd(base64_encode(MAIL_SMTP_PASS),['235']);
  $cmd('MAIL FROM:<'.MAIL_SMTP_USER.'>',['250']);
  $cmd('RCPT TO:<'.$to.'>',['250','251']);
  $cmd('DATA',['354']);
  $head='Subject: =?UTF-8?B?'.base64_encode($sub)."?=\r\n";
  $head.='From: =?UTF-8?B?'.base64_encode(MAIL_FROM_NAME).'?= <'.MAIL_SMTP_USER.">\r\n";
  if($html!==''){
    // multipart/alternative：客户端不支持 HTML 时回退显示纯文本
    $bnd='=F'.md5(uniqid('',true));
    $head.='To: <'.$to.">\r\nMIME-Version: 1.0\r\nContent-Type: multipart/alternative; boundary=\"$bnd\"\r\n\r\n";
    $head.="--$bnd\r\nContent-Type: text/plain; charset=utf-8\r\nContent-Transfer-Encoding: base64\r\n\r\n".chunk_split(base64_encode($body));
    $head.="--$bnd\r\nContent-Type: text/html; charset=utf-8\r\nContent-Transfer-Encoding: base64\r\n\r\n".chunk_split(base64_encode($html));
    $head.="--$bnd--\r\n";
    fputs($fp,$head);
  }else{
    $head.='To: <'.$to.">\r\nContent-Type: text/plain; charset=utf-8\r\n\r\n";
    fputs($fp,$head.$body."\r\n");
  }
  $cmd('.',['250']);
  fputs($fp,"QUIT\r\n");
  fclose($fp);
}
function make_code($email,$scene,&$mail_err=null){
  if(!filter_var($email,FILTER_VALIDATE_EMAIL)) throw new Exception('邮箱格式不正确，仅支持邮箱注册');
  $code=str_pad(rand(0,999999),6,'0',STR_PAD_LEFT);
  $pdo=db(); $pdo->prepare("DELETE FROM email_codes WHERE email=? AND scene=?")->execute([$email,$scene]);
  $pdo->prepare("INSERT INTO email_codes(email,code,scene,expire_at,created_at) VALUES(?,?,?,?,?)")->execute([$email,$code,$scene,time()+CODE_EXPIRE,time()]);
  try{ send_mail_code($email,$code,$scene==='reg'?'注册':($scene==='forgot'?'找回密码':($scene==='bind'?'绑定邮箱':'验证'))); }catch(Exception $ex){ $mail_err=$ex->getMessage(); if(!MAIL_DEBUG_SHOW_CODE) throw $ex; }
  return $code;
}
function verify_code($email,$code,$scene){
  $s=db()->prepare("SELECT * FROM email_codes WHERE email=? AND scene=? ORDER BY id DESC LIMIT 1"); $s->execute([$email,$scene]); $r=$s->fetch(PDO::FETCH_ASSOC);
  if(!$r) return '请先获取验证码';
  if(time()>(int)$r['expire_at']) return '验证码已过期，请重新获取';
  if(trim($code)!==$r['code']) return '验证码错误';
  db()->prepare("DELETE FROM email_codes WHERE email=? AND scene=?")->execute([$email,$scene]);
  return true;
}

// ---- 极验 GeeTest v4 服务端校验 ----
function geetest_verify(){
  if(!GEETEST_ENABLED) return true;
  $lot=$_POST['lot_number']??''; $cp=$_POST['captcha_output']??''; $pt=$_POST['pass_token']??''; $gid=$_POST['gen_time']??'';
  if(!$lot||!$cp) return '请先完成人机验证';
  $sign=hash_hmac('sha256',$lot,GEETEST_KEY);
  $post=http_build_query(['lot_number'=>$lot,'captcha_output'=>$cp,'pass_token'=>$pt,'gen_time'=>$gid,'sign_token'=>$sign,'captcha_id'=>GEETEST_ID]);
  $ctx=stream_context_create(['http'=>['method'=>'POST','header'=>'Content-Type: application/x-www-form-urlencoded','content'=>$post,'timeout'=>5]]);
  $res=@file_get_contents('https://gcaptcha4.geetest.com/validate?captcha_id='.GEETEST_ID, false, $ctx);
  if(!$res) return true; // 网络异常时放行，避免虚拟主机出网受限锁死
  $j=json_decode($res,true);
  if(isset($j['result'])&&$j['result']==='success') return true;
  if(isset($j['status'])&&$j['status']==='success') return true;
  return '人机验证未通过，请重试';
}
function geetest_widget(){
  if(!GEETEST_ENABLED) return '<p class="muted" style="font-size:13px">本地测试模式：极验未启用（config.php 填入 GEETEST_ID/KEY 后开启）。</p>';
  // GeeTest v4 bind 模式：不渲染内嵌组件；用户点击提交时才 showBox() 弹窗验证，
  // 通过后把凭证写入隐藏域并重新提交表单（form.submit() 不触发表单事件，避免死循环）
  static $lib=false; $s='';
  if(!$lib){ $s.='<script src="https://static.geetest.com/v4/gt4.js"></script>'; $lib=true; }
  $s.='<input type="hidden" name="lot_number"><input type="hidden" name="captcha_output"><input type="hidden" name="pass_token"><input type="hidden" name="gen_time">'
    .'<script>(function(){var sc=document.currentScript,f=sc.parentNode;'
    .'while(f&&f.tagName!=="FORM")f=f.parentNode;'
    .'if(!f||typeof initGeetest4!=="function")return;'
    .'initGeetest4({captchaId:"'.GEETEST_ID.'",product:"bind"},function(c){'
    .'c.onSuccess(function(){var v=c.getValidate();'
    .'f.querySelector("[name=lot_number]").value=v.lot_number;'
    .'f.querySelector("[name=captcha_output]").value=v.captcha_output;'
    .'f.querySelector("[name=pass_token]").value=v.pass_token;'
    .'f.querySelector("[name=gen_time]").value=v.gen_time;'
    .'f.submit();});'
    .'f.addEventListener("submit",function(e){'
    .'if(f.querySelector("[name=lot_number]").value)return;'  // 已验证过，直接放行
    .'e.preventDefault();'
    .'if(f.reportValidity&&!f.reportValidity())return;'       // 先走浏览器必填校验，为空不弹窗
    .'c.showBox();'
    .'});});})();</script>';
  return $s;
}
// ---- 外部 HTTP 请求 ----
// 发起 GET 请求，优先 cURL（多数虚拟主机禁用了 allow_url_fopen 出站）。返回 [body|null, error|null]
function http_get($url,$timeout=15){
  if(function_exists('curl_init')){
    $ch=curl_init($url);
    curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>$timeout,CURLOPT_CONNECTTIMEOUT=>8,
      CURLOPT_SSL_VERIFYPEER=>false,CURLOPT_SSL_VERIFYHOST=>0,CURLOPT_USERAGENT=>'Mozilla/5.0 (forum-oauth)']);
    $body=curl_exec($ch);
    if($body===false){ $err=curl_error($ch); return [null,'cURL 错误：'.$err]; }
    $code=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);
    if($code>=400) return [null,'HTTP '.$code];
    return [(string)$body,null];
  }
  if(ini_get('allow_url_fopen')){
    $ctx=stream_context_create(['http'=>['timeout'=>$timeout,'user_agent'=>'Mozilla/5.0 (forum-oauth)'],'ssl'=>['verify_peer'=>false,'verify_peer_name'=>false]]);
    $body=@file_get_contents($url,false,$ctx);
    if($body===false){ $e=error_get_last(); return [null,$e['message']??'file_get_contents 失败']; }
    return [(string)$body,null];
  }
  return [null,'主机禁用了 allow_url_fopen 且未安装 cURL 扩展，PHP 无法发起外部 HTTP 请求'];
}
// ---- 素颜聚合登录（https://u.suyanw.cn/doc.php）----
// 生成 connect.php 各 act 的请求地址；$act: login(获取授权地址) / callback(code换用户信息) / query(查询用户)
function suyan_connect_url($act, array $extra=[]){
  $api=rtrim(SUYAN_API,'/');
  // 容错：素颜后台展示的「接口地址」是根地址 https://u.suyanw.cn/，真实端点是其下的 connect.php
  if(!preg_match('#/[a-z0-9_]+\.php$#i',$api)) $api.='/connect.php';
  $q=array_merge(['act'=>$act,'appid'=>SUYAN_APPID,'appkey'=>SUYAN_APPKEY],$extra);
  return $api.'?'.http_build_query($q);
}
// 支持的登录方式：键即素颜 type 值；icon 对应 ico() 图标名，color 为品牌色
function suyan_types(){
  return [
    'wx'=>['name'=>'微信','icon'=>'wechat','color'=>'#07C160'],
    'qq'=>['name'=>'QQ','icon'=>'qq','color'=>'#12B7F5'],
    'douyin'=>['name'=>'抖音','icon'=>'douyin','color'=>'#161823'],
    'microsoft'=>['name'=>'微软','icon'=>'microsoft','color'=>'#0067B8'],
  ];
}
// 按 type+openid 查绑定账号对应的 users 行（不存在返回 null）
function social_find_user($type,$openid){
  $s=db()->prepare("SELECT u.* FROM social_accounts sa JOIN users u ON u.id=sa.uid WHERE sa.type=? AND sa.social_uid=?");
  $s->execute([$type,$openid]);
  return $s->fetch(PDO::FETCH_ASSOC) ?: null;
}

/* ==================== v1.3.0 工具函数 ==================== */

// 展示 ID：10000 + 注册顺序 id（≥5 位）
function display_uid($id){ return 10000 + (int)$id; }
// 登录框账号解析：邮箱 或 展示ID（纯数字且 ≥10000 走 ID 分支）
// v1.4：占位邮箱（第三方直登账号，@social.local 随机串）不可用于登录，必须先绑定真实邮箱
function resolve_user($key){
  $key=trim((string)$key);
  if($key==='') return null;
  $pdo=db();
  if(preg_match('/^\d{5,}$/',$key) && (int)$key>=10000){
    $s=$pdo->prepare("SELECT * FROM users WHERE id=?"); $s->execute([(int)$key-10000]);
    $u=$s->fetch(PDO::FETCH_ASSOC); if($u) return $u;
  }
  if(is_social_local($key)) return null; // 占位邮箱不能作为登录账号
  $s=$pdo->prepare("SELECT * FROM users WHERE email=?"); $s->execute([$key]);
  return $s->fetch(PDO::FETCH_ASSOC) ?: null;
}
// 头像：第三方 faceimg 优先（users.avatar），否则首字母按 uid hash 取色兜底
function avatar_color($uid){
  $palette=['#2563EB','#0891B2','#059669','#D97706','#DC2626','#7C3AED','#DB2777','#4F46E5'];
  return $palette[abs((int)$uid) % count($palette)];
}
function avatar_html($u,$size=42,$cls=''){
  $s=(int)$size; $url=trim((string)($u['avatar']??''));
  if($url!=='') return '<img class="ava-img '.e($cls).'" src="'.e($url).'" width="'.$s.'" height="'.$s.'" style="width:'.$s.'px;height:'.$s.'px" alt="" loading="lazy" onerror="this.outerHTML=\'\'">';
  $ch=mb_substr(trim((string)($u['username']??'')),0,1,'UTF-8'); if($ch==='') $ch='同';
  $c=avatar_color((int)($u['id']??0));
  return '<span class="ava-letter '.e($cls).'" style="width:'.$s.'px;height:'.$s.'px;line-height:'.$s.'px;font-size:'.(int)round($s*0.42).'px;background:'.$c.'1f;color:'.$c.'">'.e($ch).'</span>';
}
// 身份徽章（管理员 > 官方 > 运营，显示最高身份）
function role_badges($u){
  if(!$u) return '';
  if(!empty($u['is_admin'])||is_super_admin($u)) return '<span class="badge-role r-admin">管理员</span>';
  if(!empty($u['is_official'])) return '<span class="badge-role r-official">官方</span>';
  if(!empty($u['is_operator'])) return '<span class="badge-role r-op">运营</span>';
  return '';
}
// 板块列表：读配置，容错兜底
// v1.4：社交占位邮箱判断（历史第三方登录占位号后缀 @social.local）：不向其发信，前台引导绑定真实邮箱
function is_social_local($email){ return preg_match('/@social\.local$/i',trim((string)$email))===1; }
function boards_all(){
  $b=array_values(array_filter(array_map('trim',(array)(defined('BOARDS')?BOARDS:[])),function($x){ return $x!==''; }));
  return $b ? $b : ['综合交流'];
}
// 站内通知写入（mailed：0 待聚合发送 / 2 免发如公告推送）
function notify_add($uid,$type,$actor_id,$post_id,$reply_id,$content,$mailed=0){
  try{
    db()->prepare("INSERT INTO notifications(uid,type,actor_id,post_id,reply_id,content,is_read,mailed,created_at) VALUES(?,?,?,?,?,?,0,?,?)")
       ->execute([(int)$uid,(string)$type,(int)$actor_id,(int)$post_id,(int)$reply_id,(string)$content,(int)$mailed,time()]);
  }catch(Exception $ex){ _err_log('notify_add 失败：'.$ex->getMessage()); }
}
// 从正文提取首个图片 URL（写入 posts.first_image 用于 OG 卡片）
function first_image_of($content){
  if(preg_match('#https?://[^\s<>"\']+\.(?:jpg|jpeg|png|gif|webp)(?:\?[^\s<>"\']*)?#i',(string)$content,$m)) return $m[0];
  return '';
}
// v1.4 正文渲染：先转义防 XSS，再把图片外链转成 <img>，其余保留换行
// 用于帖子详情 / 回复正文（上传图床后正文里存的是图片 URL，需渲染成图片而非一串链接）
// 注：不用 preg_split(...,PREG_SPLIT_DELIM_CAPTURE)，改用偏移量逐段扫描，行为跨 PHP 版本一致
function content_html($text){
  $text=(string)$text;
  if($text==='') return '';
  $re='#https?://[^\s<>"\']+\.(?:jpe?g|png|gif|webp|bmp|tiff)(?:\?[^\s<>"\']*)?#i';
  $out=''; $offset=0;
  while(preg_match($re,$text,$m,PREG_OFFSET_CAPTURE,$offset)){
    $pos=(int)$m[0][1]; $url=(string)$m[0][0];
    if($pos>$offset) $out.=nl2br(e(substr($text,$offset,$pos-$offset)));
    $u=e($url);
    $out.='<img class="post-img" src="'.$u.'" alt="图片" loading="lazy">';
    $offset=$pos+strlen($url);
  }
  if($offset<strlen($text)) $out.=nl2br(e(substr($text,$offset)));
  return $out;
}
// v1.4 列表摘要纯文本：去掉图片外链并压缩空白，避免卡片里显示一长串链接
function content_text($text){
  $re='#https?://[^\s<>"\']+\.(?:jpe?g|png|gif|webp|bmp|tiff)(?:\?[^\s<>"\']*)?#i';
  return trim(preg_replace('/\s+/u',' ',(string)preg_replace($re,' ',(string)$text)));
}
// 邮件聚合 flush：无 cron → 任意前台页面加载时触发（300 秒软锁 + 单次 50 用户 + SMTP 异常即停）
function maybe_flush_mail(){
  if(!MAIL_USE_SMTP) return;
  if(defined('FORUM_NO_MAIL_FLUSH') && FORUM_NO_MAIL_FLUSH) return;
  @mkdir(__DIR__.'/_data',0755,true);
  $lock=__DIR__.'/_data/mail_flush.lock';
  $t=@filemtime($lock);
  if($t && (time()-$t)<300) return;
  @file_put_contents($lock,(string)time(),LOCK_EX);
  try{
    $pdo=db();
    // 顺带清理 30 天前已处理通知，防表膨胀
    try{ $pdo->exec("DELETE FROM notifications WHERE mailed<>0 AND created_at<".(time()-30*86400)); }catch(Exception $ex){}
    $uids=$pdo->query("SELECT DISTINCT uid FROM notifications WHERE mailed=0 ORDER BY uid LIMIT 50")->fetchAll(PDO::FETCH_COLUMN);
    if(!$uids) return;
    $q=$pdo->prepare("SELECT n.*,u2.username AS actor_name,p.title AS post_title FROM notifications n LEFT JOIN users u2 ON u2.id=n.actor_id LEFT JOIN posts p ON p.id=n.post_id WHERE n.uid=? AND n.mailed=0 ORDER BY n.id ASC LIMIT 100");
    $us=$pdo->prepare("SELECT * FROM users WHERE id=?");
    foreach($uids as $uid){
      $q->execute([(int)$uid]); $rows=$q->fetchAll(PDO::FETCH_ASSOC);
      if(!$rows) continue;
      $us->execute([(int)$uid]); $tu=$us->fetch(PDO::FETCH_ASSOC);
      $ids=array_map(function($n){ return (int)$n['id']; },$rows);
      if(!$tu || (int)$tu['status']!==1 || is_social_local((string)$tu['email'])){ // 收件人不存在/被禁用/社交占位邮箱：标记免发
        $pdo->exec("UPDATE notifications SET mailed=2 WHERE id IN (".implode(',',$ids).")"); continue;
      }
      $likeN=0;$replyN=0;$pushN=0;$items=[];
      foreach($rows as $n){
        // 收件偏好过滤：关闭的类型不进邮件
        if($n['type']==='like' && !MAIL_NOTIFY_LIKE) continue;
        if($n['type']==='reply' && !MAIL_NOTIFY_REPLY) continue;
        if($n['type']==='system' && !MAIL_NOTIFY_PUSH) continue;
        if($n['type']==='like') $likeN++; elseif($n['type']==='reply') $replyN++; else $pushN++;
        if(count($items)<10) $items[]=$n;
      }
      $ids=array_map(function($n){ return (int)$n['id']; },$rows); // 无论是否进邮件，本批都标记已处理
      if($likeN+$replyN+$pushN>0){
        $un=$pdo->prepare("SELECT COUNT(*) FROM notifications WHERE uid=? AND is_read=0"); $un->execute([(int)$uid]); $unreadN=(int)$un->fetchColumn();
        $total=$likeN+$replyN+$pushN;
        $html=mail_html_template($tu,$items,$likeN,$replyN,$pushN,$unreadN);
        try{
          smtp_send($tu['email'],'【'.SITE_NAME.'】你有 '.$total.' 条新消息','你有 '.$total.' 条新消息（点赞 '.$likeN.' · 回复 '.$replyN.' · 公告 '.$pushN.'），请登录 '.base_url().' 查看。',$html);
        }catch(Exception $me){ _err_log('邮件聚合发送失败 uid='.(int)$uid.'：'.$me->getMessage()); break; } // SMTP 异常即停，防主机超时
      }
      $pdo->exec("UPDATE notifications SET mailed=1 WHERE id IN (".implode(',',$ids).")");
    }
  }catch(Exception $ex){ _err_log('邮件聚合 flush 异常：'.$ex->getMessage()); }
}
// v1.4 邮件通用外壳：与网站同款蓝白配色（米白底 #FAF7F2 → 品牌蓝条 → 白色卡片 → 黑色页脚）
// $sub 品牌条右侧小字；$inner 卡片内 HTML（各场景自行拼装）
function mail_shell($sub,$inner){
  $site=e(SITE_NAME); $home=e(base_url());
  return '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>'
   .'<body style="margin:0;padding:0;background:#FAF7F2">'
   .'<div style="max-width:600px;margin:0 auto;padding:24px 14px;font-family:-apple-system,\'PingFang SC\',\'Microsoft YaHei\',sans-serif">'
   .'<div style="background:#2563EB;padding:20px 22px;border-radius:14px 14px 0 0">'
   .'<span style="color:#fff;font-size:19px;font-weight:800;letter-spacing:.5px">'.$site.'</span>'
   .'<span style="color:rgba(255,255,255,.78);font-size:12.5px;margin-left:10px">'.$sub.'</span></div>'
   .'<div style="background:#fff;border:1px solid #E9E4DB;border-top:0;border-radius:0 0 14px 14px;padding:22px">'.$inner.'</div>'
   .'<div style="background:#111726;border-radius:14px;margin-top:14px;padding:16px 20px;text-align:center;color:#8B94A6;font-size:12px;line-height:1.9">'
   .'此邮件由 <a href="'.$home.'" style="color:#6AA5F8;text-decoration:none">'.$site.'</a> 系统发送 · 请勿直接回复<br>'
   .'<span style="color:#5E6980">© '.date('Y').' '.$site.'</span></div>'
   .'</div></body></html>';
}
// v1.4 验证码邮件 HTML：大字验证码 + 有效期提示（$scene 为中文场景名）
function mail_code_template($scene,$code){
  $inner='<p style="margin:0 0 6px;font-size:15px;color:#1F2937">你正在进行 <b style="color:#2563EB">'.e($scene).'</b> 操作</p>'
   .'<p style="margin:0 0 16px;font-size:13px;color:#7C8494">请在页面中输入下面的验证码完成验证：</p>'
   .'<div style="background:#EFF6FF;border:1px solid #BFDBFE;border-radius:14px;padding:18px;text-align:center">'
   .'<span style="font-family:\'SFMono-Regular\',Consolas,Menlo,monospace;font-size:32px;font-weight:800;letter-spacing:6px;color:#2563EB">'.e($code).'</span></div>'
   .'<p style="margin:16px 0 0;font-size:13px;color:#7C8494;line-height:1.9">验证码 '.(int)(CODE_EXPIRE/60).' 分钟内有效，请勿泄露给他人。<br>如非本人操作，请忽略此邮件。</p>';
  return mail_shell('邮箱验证码',$inner);
}
// 聚合通知邮件 HTML 模板：复用 mail_shell（品牌条 → 明细 → 查看按钮 → 版权）
function mail_html_template($toUser,$items,$likeN,$replyN,$pushN,$unreadN){
  $home=e(base_url());
  $rows='';
  foreach($items as $n){
    $act=$n['type']==='like'?'点赞了':($n['type']==='reply'?'回复了':'发布了一条公告');
    $who=trim((string)($n['actor_name']??''))?:'系统';
    $t=trim((string)($n['post_title']??''));
    $sum=mb_substr(preg_replace('/\s+/u',' ',trim((string)$n['content'])),0,60);
    $rows.='<tr><td style="padding:12px 2px;border-bottom:1px solid #E9E4DB;font-size:14px;color:#1F2937;line-height:1.7">'
      .'<b style="color:#2563EB">'.e($who).'</b> 在'.($t!==''?'你的帖子《'.e($t).'》':'你这里').' '.e($act)
      .($sum!==''?'：<span style="color:#7C8494">'.e($sum).'</span>':'')
      .'</td></tr>';
  }
  if($rows==='') $rows='<tr><td style="padding:18px 2px;color:#7C8494;font-size:14px">暂无明细</td></tr>';
  $btnText='查看全部'.$unreadN.'条';
  if($unreadN<=0) $btnText='去'.e(SITE_NAME).'看看';
  $inner='<p style="margin:0 0 8px;font-size:14px;color:#1F2937">Hi <b>'.e($toUser['username']??'同学').'</b>，聚合窗口内有 <b style="color:#2563EB">'.((int)$likeN).'</b> 条点赞、<b style="color:#2563EB">'.((int)$replyN).'</b> 条回复、<b style="color:#2563EB">'.((int)$pushN).'</b> 条公告：</p>'
   .'<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse">'.$rows.'</table>'
   .'<div style="padding:20px 0 4px;text-align:center"><a href="'.$home.'" style="display:inline-block;background:#2563EB;color:#fff;text-decoration:none;font-size:14.5px;font-weight:600;padding:11px 34px;border-radius:11px">'.$btnText.'</a></div>';
  return mail_shell('消息摘要（每 '.MAIL_DIGEST_MINS.' 分钟聚合一次）',$inner);
}
// 发起 POST 请求（cURL）。$postFields 支持数组或字符串；返回 [body|null, http码, error|null]
function http_post($url,$postFields,$headers=[],$timeout=30){
  if(!function_exists('curl_init')) return [null,0,'主机未启用 cURL 扩展'];
  $ch=curl_init($url);
  curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$postFields,
    CURLOPT_TIMEOUT=>$timeout,CURLOPT_CONNECTTIMEOUT=>8,CURLOPT_SSL_VERIFYPEER=>false,CURLOPT_SSL_VERIFYHOST=>0,
    CURLOPT_USERAGENT=>'Mozilla/5.0 (forum-http)']);
  if($headers) curl_setopt($ch,CURLOPT_HTTPHEADER,$headers);
  $body=curl_exec($ch);
  if($body===false){ $err=curl_error($ch); return [null,0,'cURL 错误：'.$err]; }
  $code=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);
  return [(string)$body,$code,null];
}
// v1.4 图床上传（适配 img.scdn.io 公共 API，见 https://img.scdn.io/api_docs.php）
// POST multipart：字段名 image；可选 cdn_domain（指定外链 CDN 域名）
// 成功：{"success":true,"url":"...","data":{"url":"..."}}；失败：{"success":false,"error":"..."}
// 成功返回 [url,null]；失败返回 [null,错误摘要]（摘要仅 DEBUG 模式含响应内容）
function cdn_upload($tmpfile,$mime,$name){
  if(!function_exists('curl_init')) return [null,'主机未启用 cURL 扩展，无法上传图片'];
  if(!defined('CDN_API') || CDN_API==='') return [null,'图床未配置：请管理员在后台「系统设置 → 图床」填写 API 地址'];
  // 逐个尝试配置的 CDN 域名（逗号分隔），首个成功即返回
  $domains=[];
  if(defined('CDN_DOMAIN') && CDN_DOMAIN!==''){ foreach(explode(',',CDN_DOMAIN) as $d){ $d=trim($d); if($d!=='') $domains[]=$d; } }
  if(!$domains) $domains=['']; // 留空由图床自动选择
  $last='';
  foreach($domains as $d){
    $fields=['image'=>new CURLFile($tmpfile,$mime,$name)];
    if($d!=='') $fields['cdn_domain']=$d;
    list($body,$code,$err)=http_post(CDN_API,$fields,[],60);
    if($err){ $last='上传请求失败：'.$err; continue; }
    $j=json_decode((string)$body,true);
    if(!empty($j['url'])) return [(string)$j['url'],null];
    if(!empty($j['data']['url'])) return [(string)$j['data']['url'],null];
    $em=trim((string)($j['error']??($j['message']??'')));
    $last='图床返回 HTTP '.$code.($em!==''?'：'.$em:'');
    if(defined('DEBUG_MODE') && DEBUG_MODE) $last.=' ｜ 响应：'.mb_substr((string)$body,0,300);
    if($code===429) break; // 触发限流：换域名无意义，直接返回
  }
  return [null,$last?:'图床上传失败'];
}
