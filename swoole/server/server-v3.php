<?php

/**
 * 单进程非阻塞I/O复用的网络服务模型(select)
 * 经过ab测试qps优于阻塞的模型
 */

class Worker{
    //监听socket
    protected $socket = NULL;
    //连接事件回调
    public $onConnect = NULL;
    //接收消息事件回调
    public $onMessage = NULL;

    private $workerNum = 5;

    private $allSocket; // 所有的socket

    public function __construct($socket_address) {
        // 监听地址+端口
        $this->socket= stream_socket_server($socket_address);

        stream_set_blocking($this->socket, 0); // 设置非阻塞

        $this->setAllSocket($this->socket);
        
    }
    private function fork(){
        for($i=0; $i<$this->workerNum; $i++){
            echo $i.PHP_EOL; 
            $child_id = pcntl_fork(); // 返回子进程id
            if($child_id<0){
                exit('创建失败');
            }else if($child_id>0){

            }else{
                $this->accept();
            }
        }
        // 回收子进程
        pcntl_wait($status);

    }
    public function accept(){
        while(true){
            $write = $except = [];
            // 需要监听的socket
            $read = $this->allSocket;
            // 状态改变
            if(stream_select($read, $write, $except, 60)>0){
                // 怎么区分服务端跟客户端
                foreach($read as $index=>$val){
                    if($val === $this->socket){// 当前该发生改变的是服务端, 有连接进入
                        // 阻塞监听
                        $clientSocket = stream_socket_accept($this->socket);

                        // 触发onConnect
                        if(!empty($clientSocket) && is_callable($this->onConnect)){
                            call_user_func($this->onConnect, $clientSocket, posix_getpid());
                        }

                        // 连接注册到可监听列表
                        $this->setAllSocket($clientSocket);

                    }else{
                        // 读取客户端请求
                        $buffer = fread($val, 65535);

                        //如果数据为空, 或者为false, 不是资源类型
                        if(empty($buffer)){
                            if(feof($val) || !is_resource($val)){
                                // 触发关闭事件
                                fclose($val);
                                $this->removeSocket($val);
                                continue;
                            }
                        }

                        // 触发onMessage
                        if(!empty($buffer) && is_callable($this->onMessage)){
                            call_user_func($this->onMessage, $val, $buffer);
                        }
                    }
                }
            }

            

            // fclose($clientSocket);

        }
    }
    public function start() {

        // $this->fork();
        $this->accept();


    }
    private function setAllSocket($socket){
        $this->allSocket[intval($socket)] = $socket;
    }
    private function removeSocket($socket){
        // $this->allSocket[intval($socket)] = null;
        unset($this->allSocket[intval($socket)]);
    }
}

$worker = new Worker('tcp://0.0.0.0:9800');

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


