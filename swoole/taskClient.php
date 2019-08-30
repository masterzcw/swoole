<?php

$client=new swoole\Client(SWOOLE_SOCK_TCP);

//发数据
$client->connect('127.0.0.1',9801);
$client->send('456');

//var_dump(strlen($client->recv())); //接收消息没有接收
$client->close();
