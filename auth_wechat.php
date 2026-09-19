<?php
// 该文件已被虚拟主机安全扫描锁定为 403，实际功能已迁移到 oauth.php。
// 此文件仅保留以兼容旧的收藏链接 / 素颜后台旧回调地址（会自动 302 到新文件）。
header('Location: oauth.php'.(($_SERVER['QUERY_STRING']??'')!==''?'?'.$_SERVER['QUERY_STRING']:'')); exit;