<?php

/**
 * 单进程阻塞的网络服务端模型
 */

class Worker{
    //监听socket
    protected $socket = NULL;
    //连接事件回调
    public $onConnect = NULL;
    //接收消息事件回调
    public $onMessage = NULL;
    public function __construct($socket_address) {
        // 监听地址+端口
        $this->socket= stream_socket_server($socket_address);
    }
    public function start() {

        while(true){
            // 阻塞监听
            $clientSocket = stream_socket_accept($this->socket);

            // 触发onConnect
            if(!empty($clientSocket) && is_callable($this->onConnect)){
                
                call_user_func($this->onConnect, $clientSocket);
            }

            // 读取客户端请求
            $buffer = fread($clientSocket, 65535);

            // 触发onMessage
            if(!empty($buffer) && is_callable($this->onMessage)){
                call_user_func($this->onMessage, $clientSocket, $buffer);
            }

            fclose($clientSocket);

        }


    }
}

$worker = new Worker('tcp://0.0.0.0:9800');

$worker->onConnect = function ($fd) {
    echo '新的连接来了'.intval($fd).PHP_EOL;
};

$worker->onMessage = function ($conn, $message) {
    // 事件回调等汇总写业务逻辑, $message太丑, 这里就不打印了
    $content = "这是返回的响应.";
    $http_resonse = "HTTP/1.1 200 OK\r\n";
    $http_resonse .= "Content-Type: text/html;charset=UTF-8\r\n";
    $http_resonse .= "Connection: keep-alive\r\n"; // 保持连接
    $http_resonse .= "Server: php socket server\r\n";
    $http_resonse .= "Content-length: ".strlen($content)."\r\n\r\n";
    $http_resonse .= $content;
    fwrite($conn, $http_resonse);
};
$worker->start();


