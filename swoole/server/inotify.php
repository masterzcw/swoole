<?php
/**
 * 监听文件的修改事件
 */


$inotify = inotify_init();
include "index.php";
$files = get_included_files();
foreach($files as $file){
    $_ = inotify_add_watch($inotify, $file, IN_MODIFY); // 监视相关的文件
}
// 监听: 这个监听居然没有实现, 也没有报错
echo swoole_event_add($inotify, function($fd){
    // $events = inotify_read($fd);
    // var_dump($events);
});







