<?php

/**
 * 解决TCP粘包的问题
 */

$client = new Swoole\Client(SWOOLE_SOCK_TCP);

$client->connect('192.168.11.125', 9800) || exit("连接失败");

// 发送消息
$pack1 = [1024*1024*30, 1024*1024*30, 1024*1024*30];
$pack2 = [50, 20, 30];
foreach($pack1 as $v){
    $body = json_encode(str_repeat('a', $v));
    $data = pack("N",strlen($body)).$body;
    $client->send($data);
    echo $client->recv();
}

$client->close();




