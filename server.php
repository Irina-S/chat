<?php
    define("PORT", "8090");
    require_once("classes/chat.php");

    $chat = new Chat();

    $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    socket_set_option($socket,  SOL_SOCKET, SO_REUSEADDR, 0);
            // For example, to retrieve options at the socket level, a level parameter of SOL_SOCKET would be used.

            // SO_REUSEADDR	Reports whether local addresses can be reused
    socket_bind($socket, 0, PORT);
            // Binds the name given in address to the socket described by socket.

    socket_listen($socket);

    //массив клиентских сокетов
    $clientSocketArray = array($socket);

    //бесконечный цикл обработки поступающих сообщений
    while (true){
        $newSocketArray = $clientSocketArray;
        //есть ли доступные символы для чтения?
        //возвращает массив сокетов, состояние которых было изменено
        $nullA = [];
        //отслеживаем сокеты, состояние которых было изменено
        socket_select($newSocketArray, $nullA, $nullA, 0, 10);
                // Runs the select() system call on the given arrays of sockets with a specified timeout
        //если есть новые запросы на соединения
        if (in_array($socket, $newSocketArray)){
            //создаем новый клиентский сокет
            $newSocket = socket_accept($socket);
            $clientSocketArray[] = $newSocket;

            //устанавливаем соединение
            $header = socket_read($newSocket, 1024);
            $chat->sendHeaders($header, $newSocket, "localhost/chat", PORT);

            //получаем ip клиента
            socket_getpeername($newSocket, $client_ip_address);

            echo "Новый клиент:\n".$client_ip_address."\n";
            
            //отправляем оповещение о подключении
            $connectionACK = $chat->newConnectionACK($client_ip_address);
            $chat->send($connectionACK, $clientSocketArray);

            //удаляем обработанный сокет
            $newSocketArrayIndex = array_search($socket, $newSocketArray);
            unset($newSocketArray[$newSocketArrayIndex]);
        }
        
        //1
        
        foreach ($newSocketArray as $newSocketArrayResourse){

            //поэтапное считывание информации из сокета
            while (socket_recv($newSocketArrayResourse, $socketData, 1024, 0)>=1){
                echo "socketData: \n";
                echo $socketData;
                //декодируем сообщение
                $socketMessage = $chat->unseal($socketData);
                $messageObj = json_decode($socketMessage);

                //рассылаем сообщение всем клиентам
                $chatMessage = $chat->createChatMessage($messageObj->chat_user, $messageObj->chat_message);
                $chat->send($chatMessage, $clientSocketArray);
                break 2;
            }

            ///2
            //обработка клеинтов, которые покинули чат
            $socketData = @socket_read($newSocketArrayResourse, 1024, PHP_NORMAL_READ);
            if ($socketData === false){
                socket_getpeername($newSocketArrayResourse, $client_ip_address);
                $connectionACK = $chat->newDisconnectedACK($client_ip_address);
                $chat->send($connectionACK, $clientSocketArray);
                //удаляем сокет пользователя, который вышел
                $newSocketArrayIndex = array_search($newSocketArrayResourse, $clientSocketArray);
                unset($clientSocketArray[$newSocketArrayIndex]);
            }
        }  
    }



        
        
    // }

    socket_close($socket);

?>