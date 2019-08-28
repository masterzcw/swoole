<?php

/**
 * UDP客户端
 */

$client = new Swoole\Client(SWOOLE_SOCK_UDP);

// $client->connect('192.168.11.125', 9800) || exit("连接失败");

// require请求
$client->sendto('192.168.11.125', 9800, "udp-client-msg");

// response返回
echo $client->recv();

$client->close();




