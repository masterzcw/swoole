<?php

$server = new Swoole\Server("0.0.0.0", 9800);

$server->set([
    'worker_num' => 1,  // 进程数
    # 心跳检测
    // 'heartbeat_idle_time' => 3, // 连接最大的空闲时间
    // 'heartbeat_check_interval' => 1, // 服务器定时检测在线列表的时间
    # 包分隔符的方式会带来CPU资源的严重损耗
    // 'open_eof_check' => true, // 打开EOF检测
    // 'package_eof' => "\r\n", // 设置EOF
    // 'open_eof_check' => true, // 打开自动拆分
    # 固定包头+包体的协议
    'open_length_check' => true, // 打开包长检测特性
    'package_max_length' => 1024*1024*100+1000, // 协议最大长度
    'package_length_type' => 'N', // 长度值的类型, 与pack函数有关系, 可以去手册查看
    'package_length_offset' => 0, // 第n个字节是包长度的值
    'package_body_offset' => 4, // 第几个字节开始计算长度
    'buffer_output_size' => 1024*1024*60+1000, // 单次最大发送长度
    // 'socket_buffer_size' => 1024*1024*60+1000, // 客户端连接最大允许占用内存


]);

$server->on('Start', function(){
    var_dump(1);
    // 设置主进程的名称
    swoole_set_process_name("server_process:master");
});
$server->on('shutdown', function(){
    //
}); 
$server->on('ManagerStart', function(){
    sleep(1);
    var_dump(2);
    swoole_set_process_name("server_process:manager");
});
$server->on('WorkerStart', function($server, $workerId){
    var_dump(3);
    swoole_set_process_name("server_process:worker");
});


#-----------------------------------------------------

// 监听连接进入事件, 有客户端连接进来的时候出发
$server->on('connect', function($server, $fd){
    echo "新的连接标识为:{$fd}".PHP_EOL;
});

// 监听数据接收事件, server接收到客户端的数据后, worker进程内触发该事件
$server->on('receive', function($server, $fd, $from_id, $data){

    // 解包: 去掉包头
    $pack_head_len = unpack('N', $data)[1];
    $data = substr($data, 4, $pack_head_len);

    echo "收到客户端消息 fd:{$fd} data-lenght:".strlen($data).PHP_EOL;
    $server->send($fd, "服务端接收到{$fd}的数据".PHP_EOL."data-lenght:".strlen($data).PHP_EOL);
});

$server->on('close', function(){
    echo "消息关闭".PHP_EOL;
});

// 服务器启动
$server->start();




