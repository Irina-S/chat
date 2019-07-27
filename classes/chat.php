<?php
    class Chat
    {

        //отправка заголовков клиенту
        public function sendHeaders($headersText, $newSocket, $host, $port){
            $headers = array();
            $tmpline = preg_split("/\r\n/", $headersText);
            foreach ($tmpline as $line){
                $line = rtrim($line);
                // $matches = array();
                if (preg_match("/\A(\S+): (.*)\z/", $line, $matches)){
                    $headers[$matches[1]] = $matches[2];
                }
            }

            $key = $headers['Sec-WebSocket-Key'];
            $sKey = base64_encode(pack('H*', sha1($key.'258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
            $strHeader = "HTTP/1.1 101 Switching Protocols \r\n".
            "Upgrade: websocket\r\n".
            "Connection: Upgrade\r\n".
            "WebSocket-Origin: $host\r\n".
            "WebSocket-Location: ws://$host:$port/chat/server.php\r\n".
            "Sec-WebSocket-Accept:$sKey\r\n\r\n";

            socket_write($newSocket, $strHeader, strlen($strHeader));
        }
        
        //оповещение о подключившемся клиенте
        public function newConnectionACK($client_ip_address){
            $message = "New client ".$client_ip_address. " connected.";
            $messageArray = [
                "message"=>$message,
                "type"=>"newConnectionACK"
            ];
            $ask = $this->seal(json_encode($messageArray));
            return $ask;
        }

        //оповещение о отключившемся клиенте
        public function newDisconnectedACK($client_ip_address){
            $message = "Client ".$client_ip_address. " disconnected.";
            $messageArray = [
                "message"=>$message,
                "type"=>"newConnectionACK"
            ];
            $ask = $this->seal(json_encode($messageArray));
            return $ask;
        }

        //формирует данные, которуе будут переданны в клиентскую часть
        public function seal($socketData){
            $b1 = 0x81;
            $length = strlen($socketData);
            $header = "";
            //значение 2 байта зависит от длинны данных
            if ($length <= 125){
                $header = pack('CC', $b1, $length);
            } 
            else if (($length > 125) && ($length < 65536)){
                $header = pack('CCn', $b1, 126, $length);
            }
            else if ($length >= 65536){
                $header = pack('NN', $b1, 127, $length);
            }
            return $header.$socketData;
        }
        
        //разбирает пришедшие данные
        public function unseal($socketData){
            $length = ord($socketData[1]) & 127;
            //расположение маски и данных зависит от длинны данных и значения 2 байта
            if ($length == 126){
                $mask = substr($socketData, 4, 4);
                $data = substr($socketData, 8);
            }
            else if ($length == 127){
                $mask = substr($socketData, 10, 4);
                $data = substr($socketData, 14);
            }
            else{
                $mask = substr($socketData, 2, 4);
                $data = substr($socketData, 6);
            }
            $socketStr = "";
            //декодирование:применение маски к данным
            for ($i = 0; $i<strlen($data); ++$i){
                $socketStr.= $data[$i]^$mask[$i%4];
            }
            echo "Новое сообщение:".$socketStr;
            return $socketStr;
        }

        //рассылает сообщение сокетам клиентов
        public function send($message, $clientSocketArray){
            $messageLength = strlen($message);
            echo "Отправляем сообщение клиентам: \n".$message."\n";
            foreach($clientSocketArray as $clientSocket){
                @socket_write($clientSocket, $message, $messageLength);
            }

            return true;
        }

        //формирует сообщение чата
        public function createChatMessage($username, $messageStr){
            $message = $username."<div>".$messageStr."</div>";
            $messageArray = [
                "message"=>$message,
                "type"=>"chat-box"
            ];
            echo "Формируем сообщение :".$messageArray."\n";
            return $this->seal(json_encode($messageArray));
        }
     }
?>

