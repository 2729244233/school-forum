<?php
require_once __DIR__.'/../common.php';
$ADMIN=require_any('admin','official','operator'); // 三身份均可查看统计
$pdo=db();
$days=(string)($_GET['days']??'7'); if(!in_array($days,['7','30','all'],true)) $days='7';
$daysInt=$days==='all'?0:(int)$days;
/* v1.3.0：日期聚合按 DB_TYPE 分支（MySQL FROM_UNIXTIME/DATE_FORMAT，SQLite strftime/substr） */
$isMysql=DB_TYPE==='mysql';
$tsDayMysql="FROM_UNIXTIME(created_at,'%Y-%m-%d')";
$tsDaySqlite="strftime('%Y-%m-%d',created_at,'unixepoch')";
$dtDayMysql="DATE_FORMAT(created_at,'%Y-%m-%d')";
$dtDaySqlite="substr(created_at,1,10)";
// 组装三个序列：帖子/回复（datetime 列）、点赞（int 时间戳列）
function series_counts($pdo,$isMysql,$table,$colIsInt,$startTs,$startDt){
  if($colIsInt){
    $expr=$isMysql?"FROM_UNIXTIME(created_at,'%Y-%m-%d')":"strftime('%Y-%m-%d',created_at,'unixepoch')";
    $where=$startTs>0?" WHERE created_at>=$startTs":'';
    $rows=$pdo->query("SELECT $expr d,COUNT(*) c FROM $table$where GROUP BY d")->fetchAll(PDO::FETCH_ASSOC);
  }else{
    $expr=$isMysql?"DATE_FORMAT(created_at,'%Y-%m-%d')":"substr(created_at,1,10)";
    $where=$startTs>0?" WHERE created_at>='$startDt'":'';
    $rows=$pdo->query("SELECT $expr d,COUNT(*) c FROM $table$where GROUP BY d")->fetchAll(PDO::FETCH_ASSOC);
  }
  $m=[];
  foreach($rows as $r){ $m[(string)$r['d']]=(int)$r['c']; }
  return $m;
}
$startTs=$daysInt>0?time()-($daysInt-1)*86400:0;
$startDt=$startTs>0?date('Y-m-d 00:00:00',$startTs):'';
$mPosts=series_counts($pdo,$isMysql,'posts',false,$startTs,$startDt);
$mReplies=series_counts($pdo,$isMysql,'replies',false,$startTs,$startDt);
$mLikes=series_counts($pdo,$isMysql,'likes',true,$startTs,$startDt);
// 生成横轴日期与三组数据
if($daysInt>0){
  $labels=[]; for($i=$daysInt-1;$i>=0;$i--) $labels[]=date('Y-m-d',time()-$i*86400);
}else{
  $all=array_unique(array_merge(array_keys($mPosts),array_keys($mReplies),array_keys($mLikes)));
  sort($all); $labels=$all;
}
$dPosts=[];$dReplies=[];$dLikes=[];
foreach($labels as $d){ $dPosts[]=$mPosts[$d]??0; $dReplies[]=$mReplies[$d]??0; $dLikes[]=$mLikes[$d]??0; }
$labelsJson=json_encode(array_map(function($d){ return substr($d,5); },$labels),JSON_UNESCAPED_SLASHES);
// 汇总卡片
$cUsers=(int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$cPosts=(int)$pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
$cReplies=(int)$pdo->query("SELECT COUNT(*) FROM replies")->fetchColumn();
$cLikes=(int)$pdo->query("SELECT COUNT(*) FROM likes")->fetchColumn();
$cNotif=(int)$pdo->query("SELECT COUNT(*) FROM notifications WHERE is_read=0")->fetchColumn();
$page='stats'; $page_title='数据统计'; include __DIR__.'/head.php';
?>
<div class="stats">
  <div class="stat-card"><div class="icon blue"><?=ico('user',20)?></div><div><div class="label">注册账号</div><div class="num"><?=$cUsers?></div></div></div>
  <div class="stat-card"><div class="icon green"><?=ico('file',20)?></div><div><div class="label">帖子总数</div><div class="num"><?=$cPosts?></div></div></div>
  <div class="stat-card"><div class="icon orange"><?=ico('message',20)?></div><div><div class="label">回复总数</div><div class="num"><?=$cReplies?></div></div></div>
  <div class="stat-card"><div class="icon purple"><?=ico('like',20)?></div><div><div class="label">点赞总量</div><div class="num"><?=$cLikes?></div></div></div>
  <div class="stat-card"><div class="icon red"><?=ico('bell',20)?></div><div><div class="label">未读站内消息</div><div class="num"><?=$cNotif?></div></div></div>
</div>

<div class="panel">
  <div class="panel-title"><?=ico('dash',17)?>互动趋势</div>
  <div class="panel-desc">按日统计发帖 / 回复 / 点赞数量（服务器时区 UTC+8）</div>
  <div style="display:flex;gap:8px;margin:0 0 12px">
    <a class="btn <?=$days==='7'?'primary':'default'?> sm" href="stats.php?days=7">近 7 天</a>
    <a class="btn <?=$days==='30'?'primary':'default'?> sm" href="stats.php?days=30">近 30 天</a>
    <a class="btn <?=$days==='all'?'primary':'default'?> sm" href="stats.php?days=all">全部</a>
  </div>
  <div style="position:relative;height:340px"><canvas id="trendChart"></canvas></div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function(){
  var el=document.getElementById('trendChart'); if(!el||typeof Chart==='undefined') return;
  new Chart(el,{type:'line',data:{
    labels:<?=json_encode($labelsJson,JSON_UNESCAPED_SLASHES)?>,
    datasets:[
      {label:'发帖',data:<?=json_encode($dPosts)?>,borderColor:'#2563EB',backgroundColor:'rgba(37,99,235,.08)',tension:.35,fill:true,pointRadius:2},
      {label:'回复',data:<?=json_encode($dReplies)?>,borderColor:'#059669',backgroundColor:'rgba(5,150,105,.06)',tension:.35,fill:true,pointRadius:2},
      {label:'点赞',data:<?=json_encode($dLikes)?>,borderColor:'#D97706',backgroundColor:'rgba(217,119,6,.06)',tension:.35,fill:true,pointRadius:2}
    ]},options:{
      responsive:true,maintainAspectRatio:false,
      plugins:{legend:{position:'bottom',labels:{boxWidth:12,usePointStyle:true}}},
      scales:{y:{beginAtZero:true,ticks:{precision:0},grid:{color:'rgba(148,163,184,.15)'}},x:{grid:{display:false}}}
    }});
})();
</script>
<?php include __DIR__.'/foot.php'; ?>
