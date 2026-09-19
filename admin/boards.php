<?php
require_once __DIR__.'/../common.php';
$ADMIN=require_any('admin','official','operator'); // 三身份均可维护板块
$pdo=db(); $msg='';
/* v1.3.0：板块不再硬编码，存 settings.json 的 boards 数组，发帖/首页/弹窗均读配置 */
if($_SERVER['REQUEST_METHOD']==='POST'){
  try{
    if(!check_csrf()) throw new Exception('表单过期，请重试');
    $act=$_POST['act']??'';
    if($act==='boards'){
      $raw=(array)($_POST['boards']??[]);
      $clean=[]; $seen=[];
      foreach($raw as $b){
        $b=trim((string)$b);
        if($b==='' || isset($seen[$b])) continue;
        if(mb_strlen($b)>16) throw new Exception('板块名称「'.$b.'」超过 16 个字');
        $seen[$b]=1; $clean[]=$b;
      }
      if(!$clean) throw new Exception('至少保留一个板块');
      settings_save(['boards'=>$clean]);
      $msg='板块已保存（共 '.count($clean).' 个）'; $ok='';
      $_SESSION['flash']=$msg;
      header('Location: boards.php'); exit;
    }
    throw new Exception('非法操作');
  }catch(Exception $ex){ $_SESSION['flash']=$ex->getMessage(); header('Location: boards.php'); exit; }
}
$boards=boards_all();
// 每个板块的帖子数（含不在当前配置中的历史板块）
$counts=[];
foreach($pdo->query("SELECT board,COUNT(*) c FROM posts GROUP BY board")->fetchAll(PDO::FETCH_ASSOC) as $r) $counts[$r['board']]=(int)$r['c'];
$orphan=array_diff(array_keys($counts),$boards); // 已不在配置里的旧板块
$page='boards'; $page_title='板块管理'; include __DIR__.'/head.php';
?>
<form method="post"><input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="act" value="boards">
<div class="panel">
  <div class="panel-title"><?=ico('grid',17)?>板块列表</div>
  <div class="panel-desc">板块用于发帖弹窗、首页筛选与发帖页。名称不超过 16 字；拖动顺序即展示顺序（从上到下）。删除板块不会删除其中的帖子（帖子仍可从「全部」访问）。</div>
  <div id="boardRows">
    <?php foreach($boards as $b): ?>
    <div class="board-row" style="display:flex;gap:8px;margin-bottom:10px;align-items:center">
      <span style="color:var(--muted);cursor:grab" title="拖动排序">⋮⋮</span>
      <input type="text" name="boards[]" value="<?=e($b)?>" maxlength="16" style="flex:1;border:1.5px solid var(--border);border-radius:10px;padding:9px 12px;font-size:13.5px" required>
      <span class="tag blue"><?=isset($counts[$b])?$counts[$b]:0?> 帖</span>
      <button type="button" class="btn danger sm" onclick="rmRow(this)"><?=ico('trash',13)?></button>
    </div>
    <?php endforeach; ?>
  </div>
  <div class="save-bar">
    <button type="button" class="btn default" onclick="addRow()"><?=ico('plus',14)?>添加板块</button>
    <button class="btn primary" type="submit">保存板块</button>
  </div>
</div></form>

<?php if($orphan): ?>
<div class="panel">
  <div class="panel-title"><?=ico('info',17)?>历史板块</div>
  <div class="panel-desc">以下板块不在当前配置中，但仍有历史帖子：</div>
  <div style="display:flex;gap:8px;flex-wrap:wrap">
    <?php foreach($orphan as $ob): ?><span class="tag default"><?=e($ob)?> · <?=$counts[$ob]?> 帖</span><?php endforeach; ?>
  </div>
</div>
<?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
function addRow(){
  var rows=document.getElementById('boardRows');
  var div=document.createElement('div');
  div.className='board-row';
  div.style.cssText='display:flex;gap:8px;margin-bottom:10px;align-items:center';
  div.innerHTML='<span style="color:var(--muted);cursor:grab" title="拖动排序">⋮⋮</span>'
    +'<input type="text" name="boards[]" maxlength="16" style="flex:1;border:1.5px solid var(--border);border-radius:10px;padding:9px 12px;font-size:13.5px" required>'
    +'<button type="button" class="btn danger sm" onclick="rmRow(this)"><?=ico('trash',13)?></button>';
  rows.appendChild(div);
  div.querySelector('input').focus();
}
function rmRow(btn){
  var rows=document.querySelectorAll('#boardRows .board-row');
  if(rows.length<=1){ alert('至少保留一个板块'); return; }
  btn.parentNode.remove();
}
if(typeof Sortable!=='undefined'){ new Sortable(document.getElementById('boardRows'),{handle:'span',animation:150}); }
</script>
<?php include __DIR__.'/foot.php'; ?>
