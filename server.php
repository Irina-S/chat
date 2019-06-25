<?php
    define("PORT", "8090");
    require_once("classes/chat.php");

    $chat = new Chat();

    $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    socket_set_option($socket,  SOL_SOCKET, SO_REUSEADDR, 0);
    socket_bind($socket, 0, PORT);

    socket_listen($socket);

    $clientSocketArray = array($socket);

    while (true){
        $newSocketArray = $clientSocketArray;
        //есть ли доступные символы для чтения?
        //возвращает массиво сокетов, состояние которых было изменено
        $nullA = [];
        socket_select($newSocketArray, $nullA, $nullA, 0, 10);
        if (in_array($socket, $newSocketArray)){
            $newSocket = socket_accept($socket);
            $clientSocketArray[] = $newSocket;

            $header = socket_read($newSocket, 1024);
            $chat->sendHeaders($header, $newSocket, "localhost/chat", PORT);

            socket_getpeername($newSocket, $client_ip_address);
            
            $connectionACK = $chat->newConnectionACK($client_ip_address);
            $chat->send($connectionACK, $clientSocketArray);

            // echo $client_ip_address;
            //удаляем обработанный сокет
            $newSocketArrayIndex = array_search($socket, $newSocketArray);
            unset($newSocketArray[$newSocketArrayIndex]);
        }
            //обработка клеинтов, которые покинули чат
            //1
            //поэтапное считывание информации из сокета
            foreach ($newSocketArray as $newSocketArrayResourse){
                //пока сокет не пуст
                echo "считываем данные из сокетов";
                while (socket_recv($newSocketArrayResourse, $socketData, 1024, 0)>=1){
                    echo "socketData";
                    echo $socketData;
                    $socketMessage = $chat->unseal($socketData);
                    $messageObj = json_decode($socketMessage);

                    $chatMessage = $chat->createChatMessage($messageObj->chat_user, $messageObj->chat_message);
                    $chat->send($chatMessage, $clientSocketArray);

                    break 2;
                }
            }

            ///2
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



        
        
    // }

    socket_close($socket);

?>