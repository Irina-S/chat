function message(text){
    $('#chat-result').append(text);
}

$(document).ready(function($){
    var socket = new WebSocket("ws://localhost:8090/chat/server.php");

    socket.onopen = function(){
        message("<div>Соединение установленно.</div>");
    }

    socket.onerror = function(error){
        message("<div>Ошибка при соединении."+(error.message?error.message:"")+"</div>");
        console.dir(error);
    }

    socket.onclose = function(){
        message("<div>Соединение закрыто.</div>");
    }

    socket.onmessage = function(event){
        var data= JSON.parse(event.data);
        message("<div>"+data.type+" - "+data.message+"</div>");
    }

    $("#chat").on('submit', function(){
        var message = {
            chat_message:$("#chat-message").val(),
            chat_user:$("#chat-user").val()
        }
        console.dir(message);
        $("#chat-user").attr("type", "hidden");
        socket.send(JSON.stringify(message));
        console.log(JSON.stringify(message));
        return false;
    })
});