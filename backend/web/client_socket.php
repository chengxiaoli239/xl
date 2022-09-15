<?php
set_time_limit(0);
// 建立客户端的socet连接
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
//连接服务器端socket
$connection = socket_connect($socket, '127.0.0.1', 10014);
//要发送到服务端的信息。
$send_data = "你好,凤姐";

$i = 0;
//客户端去连接服务端并接受服务端返回的数据，如果返回的数据保护not connect就提示不能连接。
while ($buffer = @socket_read($socket, 1024, PHP_NORMAL_READ)) {
    echo "客户端消息开始\n";
    if (preg_match("/not connect/",$buffer)) {
        echo "don`t connect\n";
        break;
    } else {
        //服务端传来的信息
        echo "从服务端过来的数据: " . $buffer . "\n";
        echo "准备写入Socket\n";
        $i++;
        // 将客户的信息写到通道中，传给服务器端
        $send_data = $send_data.$i;
        if (!socket_write($socket, "$send_data\n")) {
            echo "Write failed\n";
        }
        //服务器端收到信息后，客户端接收服务端传给客户端的回应信息。
        while ($buffer = socket_read($socket, 1024, PHP_NORMAL_READ)) {
            echo "发给服务器的数据:$send_data \n服务器返回的数据:" . $buffer . "\n";
        }
    }
    echo "客户端消息结束。\n";
}
//socket_close($connection);
//printf("Closed the socket\n");