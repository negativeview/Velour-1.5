var faye = require('faye');

var r = new faye.NodeAdapter(
    {
        mount: '/private',
        timeout: 45
    }
);

var placeholderUser = {
    incoming: function(message, callback) {
        if (
            message.channel == '/private' &&
            message.data.type == 'getUser'
        ) {
            r.getClient().publish(
                '/private',
                {
                    server: 'server',
                    type: 'reply',
                    message: {
                        userId: message.data.message.userId
                    },
                    mid: message.data.mid
                }
            );
        }
        
        callback(message);
    }
};

var wellFormed = {
    incoming: function(message, callback) {
        if (message.channel == '/meta/subscribe' || message.channel == '/meta/handshake' || message.channel == '/meta/connect') {
            if (message.channel == '/meta/connect')
                console.log("Connected: " + message.clientId);
            callback(message);
            return;
        }
        
        if (!message.data) {
            message.error = 'All messages must have data';
            console.log('message to channel ' + message.channel + ' has no data');
        }
        if (!message.data.server) {
            message.error = 'All messages must include a server name';
            console.log('Received message with no server.');
        }
        
        if (!message.data.type) {
            message.error = 'All messages must include a type';
            console.log('Received message with no type.');
        }
        
        if (!message.data.message) {
            message.error = 'All messages must include a message';
            console.log('Received message with no message.');
        }
        
        if (!message.data.mid) {
            message.error = 'All messages must include a mid';
            console.log('Received message with no mid.');
        }
        
        console.log('Incoming: ' + message.data.mid + ':' + message.data.type);
        for (var i in message.data.message) {
            console.log('\t' + i + ': ' + message.data.message[i]);
        }
        
        callback(message);
    },
    outgoing: function(message, callback) {
        callback(message);
    }
};
r.addExtension(wellFormed);
r.addExtension(placeholderUser);

r.listen(8080);