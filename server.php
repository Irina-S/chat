<?php
    define("PORT", "8090");
    require_once("classes/chat.php");

    $chat = new Chat();

    $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    socket_set_option($socket,  SOL_SOCKET, SO_REUSEADDR, 0);
    socket_bind($socket, 0, PORT);

    socket_listen($socket);

    while (true){
        $newSocket = socket_accept($socket);
        $header = socket_read($newSocket, 1024);
        // print_r($header);
        $chat->sendHeaders($header, $newSocket, "localhost/chat", PORT);
    }

    socket_close($socket);

?>