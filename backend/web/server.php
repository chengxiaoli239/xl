<?php
set_time_limit(0);
//获取tcp协议号码。
$tcp = getprotobyname("tcp");
// 建立server端socket ，创建并返回一个套接字，也称作一个通讯节点。一个典型的网络连接由 2 个套接字构成，一个运行在客户端，另一个运行在服务器端。

//$socket = socket_create(AF_INET, SOCK_STREAM, $tcp);
$commonProtocol = getprotobyname("tcp");
$socket = socket_create(AF_INET, SOCK_STREAM, $commonProtocol);

//绑定要监听的ip和端口，这里绑定的ip一定要写局域网ip，写成127.0.0.1客户端将无法与服务端建议连接。
socket_bind($socket, '127.0.0.1', 10014);
//监听端口
socket_listen($socket);

//初始化一个数据，和客户端通信
$buffer = "你觉得最美的是大海，那是你没看过我的眼睛,我的眼里只有你 客户端";
while (true) {
    // 接受客户端请求过来的一个socket连接
    $connection = socket_accept($socket);
    if(!$connection){
        echo "连接失败";
    }else{
        echo "连接成功  墙壁 眼睛 膝盖\n";
        // 向客户端传递一个信息数据
        if ($buffer != "") {
            echo "发送数据到客户端\n";
            socket_write($connection, $buffer . "\n");
            echo "写入到socket\n";
        } else {
            echo "no data in the buffer\n" ;
        }
        // 从客户端获取得的数据
        while ($data = @socket_read($connection, 1024, PHP_NORMAL_READ)) {
            printf("从客户端来的数据: " . $data . "\n");
            //取得信息给客户端一个反馈, Thank you client, you data is  Received success发给客户端的回应信息。
            for($i=0; $i<=100; $i++){
                socket_write($connection, $i."、感谢客户端, you data is  Received success\n");
                printf("从客户端来的数据: " . $data . "\n");
                sleep(2);
            }

            //socket_close($connection);
        }
    }

    //关闭 socket
    socket_close($connection);
    printf("Closed the socket\n");
}