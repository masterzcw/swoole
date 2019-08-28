<?php
/**
 * 依赖pcntl扩展
 */

$ppid = posix_getpid(); // 当前进程id

echo "当前进程id: ".$ppid.PHP_EOL;

for($i=0;$i<3;$i++){
    echo "---------".$i."---------".PHP_EOL;
    $child_id = pcntl_fork(); // 返回子进程id

    if($child_id<0){
        exit('创建失败');
    }else if($child_id>0){
        $crrent_id = posix_getpid(); // 当前进程id
        echo "父进程".PHP_EOL." - 当前进程:{$crrent_id}".PHP_EOL." - 子进程:{$child_id}".PHP_EOL."".PHP_EOL."";
 
        $status = 0;
        $over_child_id = pcntl_wait($status);
        echo "父进程".PHP_EOL." - 当前进程:{$crrent_id}".PHP_EOL." - 结束子进程 {$over_child_id} 已结束".PHP_EOL;

    }else{
        $status = 0;
        $crrent_id = posix_getpid(); // 当前进程id
        echo "子进程".PHP_EOL." - 当前进程:{$crrent_id}".PHP_EOL." - 子进程:{$child_id}".PHP_EOL."".PHP_EOL."";
        // sleep(5);
    }
}





