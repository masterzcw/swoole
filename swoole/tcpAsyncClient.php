<?php

/**
 * TCP异步客户端
 */

$client = new Swoole\Client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);

$client->on("connect", function($cli){
    $cli->send("GET / HTTP/1.1\r\n\r\n ");
});

$client->on("receive", function($cli, $data){
    echo "Receive: $data";
    // $cli->send(date('H:i:s')."\n");
    // sleep(1);
});

$client->on("error", function($cli){
    echo "error\n";
}); 

$client->on("close", function($cli){
    echo "Connection close\n";
});

$client->connect('192.168.11.125', 9800) || exit("连接失败");

// 心跳测试: 间隔时间小于heartbeat_idle_time
swoole_timer_tick(1500, function ($timer_id) use ($client) {
    echo "tick-2999ms $timer_id \n";
    $client->send('1');
});

echo "写日志\n";
echo "请求API接口\n";






