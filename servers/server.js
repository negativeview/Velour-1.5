var faye = require('faye');

var r = new faye.NodeAdapter(
    {
        mount: '/private',
        timeout: 45
    }
);

var wellFormed = {
    incoming: function(message, callback) {
        if (message.channel == '/meta/subscribe' || message.channel == '/meta/handshake' || message.channel == '/meta/connect') {
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
        
        console.log(message.data);
        
        callback(message);
    },
    outgoing: function(message, callback) {
        callback(message);
    }
};
r.addExtension(wellFormed);

r.listen(8080);