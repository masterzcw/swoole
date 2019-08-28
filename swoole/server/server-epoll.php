<?php

class Worker{
    //监听socket
    protected $socket = NULL;
    //监听地址
    protected $socket_address = NULL;
    //连接事件回调
    public $onConnect = NULL;
    //接收消息事件回调
    public $onMessage = NULL;

    private $workerNum = 3;

    // 允许多进程监听同意端口
    private $reusePort = true;
 
    public function __construct($socket_address) {
        $this->socket_address = $socket_address;
    }

    public function accept(){

        //创建一个资源流
        $opts = [
            'socket' => [
                'backlog' => 10240, // 成功建立连接的等待个数
            ],
        ];
        $stream_context = stream_context_create($opts);

        // 开启多端口监听, 并实现负载均衡
        stream_context_set_option($stream_context, 'socket', 'so_reuseport', $this->reusePort);
        stream_context_set_option($stream_context, 'socket', 'so_reuseaddr', $this->reusePort);

        // 监听地址+端口
        $this->socket= stream_socket_server($this->socket_address, $errno, $errstr, STREAM_SERVER_BIND|STREAM_SERVER_LISTEN, $stream_context);

        // 监听服务端socket的事件
        swoole_event_add($this->socket, function($fd){

            $clientSocket = stream_socket_accept($fd);

            // 触发onConnect
            if(!empty($clientSocket) && is_callable($this->onConnect)){
                call_user_func($this->onConnect, $clientSocket, posix_getpid());
            }
            
            // 监听客户端可读
            swoole_event_add($clientSocket, function($fd){
                // 读取客户端请求
                $this->readClient($fd);
            });

        });
        echo "非阻塞".posix_getpid().PHP_EOL;
    }
    public function readClient($fd){
        $buffer = fread($fd, 65535);

        //如果数据为空, 或者为false, 不是资源类型
        if(empty($buffer) || feof($fd) || !is_resource($fd)){
            // 触发关闭事件
            fclose($fd);
        }
    
        // 触发onMessage
        if(!empty($buffer) && is_callable($this->onMessage)){
            call_user_func($this->onMessage, $fd, $buffer);
        }
    }
    private function fork(){
        for($i=0; $i<$this->workerNum; $i++){
            $child_id = pcntl_fork(); // 返回子进程id
            if($child_id<0){
                exit('创建失败');
            }else if($child_id>0){

            }else{
                $this->accept();
                exit; // 避免无法回收子进程
            }
        }
        // 回收子进程
        pcntl_wait($status);

    }
    public function start() {

        // $this->accept();
        $this->fork();


    }

}

$worker = new Worker('tcp://0.0.0.0:9801');

$worker->onConnect = function ($fd, $pid) {
    echo PHP_EOL.$pid.': 新的连接来了'.intval($fd).PHP_EOL;
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


