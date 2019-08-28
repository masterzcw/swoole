<?php

/**
 * TCP服务端
 */

// 创建server对象
$server = new Swoole\Server("0.0.0.0", 9800);

$server->set([
    'worker_num' => 1,  // 进程数
    'heartbeat_idle_time' => 3, // 连接最大的空闲时间
    'heartbeat_check_interval' => 1, // 服务器定时检测在线列表的时间
]);

// 监听连接进入事件, 有客户端连接进来的时候出发
$server->on('connect', function($server, $fd){
    echo "新的连接标识为:{$fd}".PHP_EOL;
});

// 监听数据接收事件, server接收到客户端的数据后, worker进程内触发该事件
$server->on('receive', function($server, $fd, $from_id, $data){
    echo "收到客户端消息 fd:{$fd} data:{$data}".PHP_EOL;
    $server->send($fd, "服务端接收到{$fd}的数据{$data}\r\n");
});

$server->on('close', function(){
    echo "消息关闭".PHP_EOL;
});

// 服务器启动
$server->start();











