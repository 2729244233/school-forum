<?php
require_once __DIR__.'/../common.php';
$ADMIN=require_staff(); // v1.3.0：三身份均可进入后台
$pdo=db();
$users=$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$posts=$pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
$replies=$pdo->query("SELECT COUNT(*) FROM replies")->fetchColumn();
$social=$pdo->query("SELECT COUNT(*) FROM social_accounts")->fetchColumn();
$likes=$pdo->query("SELECT COUNT(*) FROM likes")->fetchColumn();
$anns=$pdo->query("SELECT COUNT(*) FROM announcements WHERE active=1")->fetchColumn();
$page='dash'; $page_title='仪表盘'; include __DIR__.'/head.php';
?>
<div class="stats">
  <div class="stat-card"><div class="icon blue"><?=ico('user',20)?></div><div><div class="label">注册账号</div><div class="num"><?=(int)$users?></div></div></div>
  <div class="stat-card"><div class="icon green"><?=ico('file',20)?></div><div><div class="label">帖子总数</div><div class="num"><?=(int)$posts?></div></div></div>
  <div class="stat-card"><div class="icon orange"><?=ico('message',20)?></div><div><div class="label">回复总数</div><div class="num"><?=(int)$replies?></div></div></div>
  <div class="stat-card"><div class="icon purple"><?=ico('like',20)?></div><div><div class="label">点赞总量</div><div class="num"><?=(int)$likes?></div></div></div>
  <div class="stat-card"><div class="icon red"><?=ico('send',20)?></div><div><div class="label">展示中公告</div><div class="num"><?=(int)$anns?></div></div></div>
</div>

<div class="panel">
  <div class="panel-title"><?=ico('dash',17)?>服务状态总览</div>
  <div class="panel-desc">当前系统各模块运行状态，点击右侧按钮快速配置</div>
  <div class="table-wrap"><table class="tb">
    <tr><th style="width:180px">模块</th><th>状态</th><th style="width:120px;text-align:right">操作</th></tr>
    <tr><td><strong>邮箱验证码</strong></td>
      <td><?=MAIL_USE_SMTP ? '<span class="tag ok">SMTP 已启用</span> '.e(MAIL_SMTP_HOST) : '<span class="tag warn">mail() 直发</span>'?></td>
      <td style="text-align:right"><a class="btn ghost sm" href="settings.php#smtp">配置</a></td></tr>
    <tr><td><strong>极验人机验证</strong></td>
      <td><?=GEETEST_ENABLED ? '<span class="tag ok">已启用</span>' : '<span class="tag err">未启用</span>'?></td>
      <td style="text-align:right"><a class="btn ghost sm" href="settings.php#geetest">配置</a></td></tr>
    <tr><td><strong>素颜聚合登录</strong></td>
      <td><?=SUYAN_ENABLED ? '<span class="tag ok">已启用</span>' : '<span class="tag err">未启用</span>'?></td>
      <td style="text-align:right"><a class="btn ghost sm" href="settings.php#suyan">配置</a></td></tr>
    <tr><td><strong>数据库</strong></td>
      <td><span class="tag blue"><?=DB_TYPE==='mysql'?'MySQL':'SQLite'?></span></td>
      <td></td></tr>
    <tr><td><strong>验证码回显</strong></td>
      <td><?=MAIL_DEBUG_SHOW_CODE ? '<span class="tag warn">已开启（测试模式）</span>' : '<span class="tag ok">已关闭</span>'?></td>
      <td style="text-align:right"><a class="btn ghost sm" href="settings.php">配置</a></td></tr>
  </table></div>
</div>

<div class="panel">
  <div class="panel-title"><?=ico('rocket',17)?>快捷操作</div>
  <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:4px">
    <a class="btn primary" href="posts.php"><?=ico('file')?>管理帖子</a>
    <a class="btn primary" href="announcements.php"><?=ico('send')?>发布公告</a>
    <a class="btn primary" href="stats.php"><?=ico('dash')?>数据统计</a>
    <a class="btn primary" href="boards.php"><?=ico('grid')?>板块管理</a>
    <?php if(in_array('admin',staff_roles($ADMIN),true)): ?><a class="btn primary" href="users.php"><?=ico('users')?>管理账号</a><?php endif; ?>
    <?php if(in_array('admin',staff_roles($ADMIN),true)): ?><a class="btn default" href="settings.php"><?=ico('gear')?>系统设置</a><?php endif; ?>
  </div>
</div>
<?php include __DIR__.'/foot.php'; ?>
