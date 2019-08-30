<?php
/**
 * worker进程中，我们调用对应的task()方法发送数据通知到task worker进程
 * task worker进程会在onTask()回调中接收到这些数据，并进行处理。
 * 处理完成之后通过调用finsh()函数或者直接return返回消息给worker进程
 * worker进程在onFinsh()中收到这些消息并进行处理
 */



// tcp协议
$server = new Swoole\Server("0.0.0.0",9801);   // 创建server对象

// include '222xx'; 不能

$server->set([
    'worker_num'=>2, // 设置进程
    // 'heartbeat_idle_time'=>10, // 连接最大的空闲时间
    // 'heartbeat_check_interval'=>3 // 服务器定时检查
    'task_worker_num'=>3,  // task进程数
    'task_ipc_mode'=>3, // 使用模式1时，支持定向投递; 2|3是消息队列模式: 模式2支持定向投递, 模式3是完全争抢模式;
]);

$server->on('start',function (){
    // include 'index.php'; 不能
});


$server->on('Shutdown',function (){
    // include 'index.php'; 不能
    echo "正常关闭";
});

$server->on('workerStart',function ($server,$fd){
    //include 'index.php';
    $_ptype = $server->taskworker?"tesk":"worker";
    echo "新的连接进入{$fd} 进程".posix_getpid()." 子进程模式".$_ptype.PHP_EOL;
    // var_dump($server->taskworker);

});


// 监听事件,连接事件
$server->on('connect',function ($server,$fd){
    // echo "新的连接进入xxx:{$fd}".PHP_EOL;
});


// 消息发送过来
$server->on('receive',function (swoole_server $server, int $fd, int $reactor_id, string $data){
    $data=['tid'=>time()];
    sleep(2);
    $server->task($data, 1); // 投递到taskWorker进程组
    // 使用模式1时，支持定向投递，可在task和taskwait方法中使用dst_worker_id，制定目标Task进程。
    // dst_worker_id设置为-1时，底层会判断每个Task进程的状态，向当前状态为空闲的进程投递任务。
});

// ontask事件回调
$server->on('task',function ($server, $task_id, $form_id, $data){
    // var_dump(posix_getpid());  // 进程确实是发生了变化
    echo "任务来自于:$form_id".", 任务id为{$task_id}, 进程".posix_getpid().PHP_EOL;
    sleep(3);
    $server->finish("-执行完毕-");
});

$server->on('finish',function ($server, $task_id, $data){
    // echo "任务{$task_id}执行完毕:{$data}".PHP_EOL;
    // var_dump(posix_getpid());
});



// 消息关闭
$server->on('close',function (){
    // echo "消息关闭".PHP_EOL;
});



// 服务器开启
$server->start();




