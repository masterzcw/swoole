<?php

/**
 * UDP服务端
 */

// 创建server对象
$server = new Swoole\Server("0.0.0.0", 9800, SWOOLE_PROCESS, SWOOLE_SOCK_UDP);

$server->set([
    'worker_num' => 1,  // 进程数
    'heartbeat_idle_time' => 3, // 连接最大的空闲时间
    'heartbeat_check_interval' => 1, // 服务器定时检测在线列表的时间
]);

// 收到UDP数据包
// $server: Server对象
// $data: 收到的数据内容，可能是文本或者二进制内容
// $client_info: 客户端信息包括 address/port/server_socket 等多项客户端信息数据
$server->on('Packet', function($server, $data, $client_info){
    var_dump($data, $client_info);
    $server->sendto($client_info['address'], $client_info['port'], "Server ".$data.PHP_EOL);
});

//启动服务器
$server->start(); 






