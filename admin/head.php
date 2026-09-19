<?php if(empty($ADMIN)){ require_once __DIR__.'/../common.php'; $ADMIN=require_staff(); } ?>
<?php $roles=staff_roles($ADMIN); $isAdm=in_array('admin',$roles,true); ?>
<!doctype html><html lang="zh-CN"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=e($page_title??'后台管理')?> - <?=e(SITE_NAME)?></title>
<link rel="icon" href="../school.png" type="image/png">
<link rel="stylesheet" href="../assets/admin.css?v=<?=@filemtime(__DIR__.'/../assets/admin.css')?>"></head>
<body>
<div class="layout">
<div class="side">
  <div class="brand"><img src="../school.png" alt="<?=e(SITE_NAME)?>"><div><div class="txt"><?=e(SITE_NAME)?></div><div class="sub">后台管理系统</div></div></div>
  <nav>
    <div class="group-label">导航</div>
    <a class="<?=$page==='dash'?'on':''?>" href="index.php"><?=ico('dash',17)?>仪表盘</a>
    <?php if($isAdm): ?><a class="<?=$page==='settings'?'on':''?>" href="settings.php"><?=ico('gear',17)?>系统设置</a><?php endif; ?>
    <div class="group-label">管理</div>
    <a class="<?=$page==='posts'?'on':''?>" href="posts.php"><?=ico('file',17)?>帖子管理</a>
    <a class="<?=$page==='ann'?'on':''?>" href="announcements.php"><?=ico('send',17)?>公告管理</a>
    <a class="<?=$page==='push'?'on':''?>" href="push.php"><?=ico('bell',17)?>消息推送</a>
    <a class="<?=$page==='stats'?'on':''?>" href="stats.php"><?=ico('dash',17)?>数据统计</a>
    <a class="<?=$page==='boards'?'on':''?>" href="boards.php"><?=ico('grid',17)?>板块管理</a>
    <?php if($isAdm): ?><a class="<?=$page==='users'?'on':''?>" href="users.php"><?=ico('users',17)?>账号管理</a><?php endif; ?>
    <div class="group-label">其他</div>
    <a href="../index.php"><?=ico('home',17)?>返回前台</a>
    <a href="../logout.php"><?=ico('logout',17)?>退出登录</a>
  </nav>
  <div class="foot">v1.4.1 正式版 · 校园论坛</div>
</div>
<div class="main">
  <div class="topbar">
    <h1><?=e($page_title??'后台管理')?></h1>
    <div class="right">
      <span style="font-size:12px;color:var(--muted)"><?=date('Y-m-d H:i')?></span>
      <div class="avatar-sm" title="<?=e($ADMIN['username'])?><?=staff_roles($ADMIN)?' · '.e(implode('/',staff_roles($ADMIN))):''?>"><?=mb_substr(e($ADMIN['username']),0,1,'UTF-8')?></div>
    </div>
  </div>
  <nav class="m-nav">
    <a class="<?=$page==='dash'?'on':''?>" href="index.php"><?=ico('dash',14)?>仪表盘</a>
    <a class="<?=$page==='posts'?'on':''?>" href="posts.php"><?=ico('file',14)?>帖子</a>
    <a class="<?=$page==='ann'?'on':''?>" href="announcements.php"><?=ico('send',14)?>公告</a>
    <a class="<?=$page==='push'?'on':''?>" href="push.php"><?=ico('bell',14)?>推送</a>
    <a class="<?=$page==='stats'?'on':''?>" href="stats.php"><?=ico('dash',14)?>统计</a>
    <a class="<?=$page==='boards'?'on':''?>" href="boards.php"><?=ico('grid',14)?>板块</a>
    <?php if($isAdm): ?><a class="<?=$page==='users'?'on':''?>" href="users.php"><?=ico('users',14)?>账号</a>
    <a class="<?=$page==='settings'?'on':''?>" href="settings.php"><?=ico('gear',14)?>设置</a><?php endif; ?>
    <a href="../index.php"><?=ico('home',14)?>前台</a>
    <a href="../logout.php"><?=ico('logout',14)?>退出</a>
  </nav>
  <div class="content">
    <?php if(!empty($_SESSION['flash'])): ?><div class="success-msg"><?=ico('check',15)?><?=e($_SESSION['flash'])?></div><?php unset($_SESSION['flash']); endif; ?>
    <?php if(!empty($msg)): ?><div class="alert"><?=ico('alert',15)?><?=e($msg)?></div><?php unset($msg); endif; ?>
    <?php if(!empty($ok)): ?><div class="success-msg"><?=ico('check',15)?><?=e($ok)?></div><?php unset($ok); endif; ?>
