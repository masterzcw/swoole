<?php
/**
 * 捕获信号, 监视worker进程, 拉起进程
 */
// declare(ticks=1);
class Worker{
    //监听socket
    protected $socket = NULL;
    //监听地址
    protected $socket_address = NULL;
    //连接事件回调
    public $onConnect = NULL;
    //接收消息事件回调
    public $onMessage = NULL;

    public $onTask = NULL;

    private $workerNum = 2;

    // 子进程
    protected $worker_pid = [];
    // 主进程
    protected $master_pid;

    // 允许多进程监听同一端口
    private $reusePort = true;
 
    public function __construct($socket_address) {
        $this->socket_address = $socket_address;
        $this->master_pid = posix_getpid();
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

        // 可以服务于多进程的流
        $this->socket = stream_socket_server($this->socket_address, $errno, $errstr, STREAM_SERVER_BIND|STREAM_SERVER_LISTEN, $stream_context);

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
        if(empty($buffer) || !is_resource($fd) || feof($fd)){
            // 触发关闭事件
            fclose($fd);
        }
    
        // 触发onMessage
        if(!empty($buffer) && is_callable($this->onMessage)){
            call_user_func($this->onMessage, $fd, $buffer);
        }
    }

    /**
     * 捕获信号, 监视worker进程, 拉起进程
     */
    public function monitorWorkers(){
        // 注册信号事件回调, 不会自动执行
        pcntl_signal(SIGUSR1, [$this, "signalHandler"], false); // SIGUSR1: 重启worker进程的信号

        // 在父进程中, 监听信号并利用"回收子进程"来阻塞
        while(1){
            pcntl_signal_dispatch(); // 当发现信号队列中有信号, 就会触发主进程绑定的事件回调
            // 当在命令行中收到"kill -10 主进程号"的信号后, 会重启子进程
            $pid = pcntl_wait($status); // 当信号到达之后, 就会被中断

            // 为维持子进程个数, 当子进程不是正常情况下的退出(例如kill -9), 重启子进程
            if($pid>-1 && $pid!=$this->master_pid && !pcntl_wifexited($status)){
                // 注销子进程
                $_index = array_search($pid, $this->worker_pid);
                unset($this->worker_pid[$_index]);

                // 启动一个子进程
                $this->fork(1);
            }

            pcntl_signal_dispatch(); // 避免信号被忽略, 进程重启的过程中, 监听信号.
            
        }
    }
    public function signalHandler($sigo){
        switch($sigo){
            case SIGUSR1:
            $this->reload();
                echo "收到重启信号";
                break;
        }
    }
    
    /**
     * 重启worker进程
     */
    public function reload(){
        foreach($this->worker_pid as $index => $pid){
            posix_kill($pid, SIGKILL);
            unset($this->worker_pid[$index]);
            $this->fork(1);
        }
    }
    private function fork($worker_num){
        for($i=0; $i<$worker_num; $i++){
            $child_id = pcntl_fork(); // 返回子进程id
            if($child_id<0){
                exit('创建失败');
            }else if($child_id>0){
                $this->worker_pid[] = $child_id;
            }else{
                $this->accept();
                exit; // 避fork更多的子进程等问题 
            }
        }
        
    }
    public function start() {

        // $this->accept();
        $this->fork($this->workerNum);
        $this->monitorWorkers();

    }

}

$worker = new Worker('tcp://0.0.0.0:9801');

// 连接事件
$worker->onConnect = function ($fd, $pid) {
    echo PHP_EOL.$pid.': 新的连接来了'.intval($fd).PHP_EOL;
    include "index.php";
};

//
$worder->onTask = function ($fd) {
    //
};

// 消息接收
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


