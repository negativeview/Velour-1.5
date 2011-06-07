// We need to talk to the other components via faye.
var faye = require('faye');

var outstanding = {};
var users = {};

// Keep a counter to have a unique message id.
var id = 0;

var c = new Faye.Client('http://localhost:8080/private');
var sub = c.subscribe('/private', function(message) {
    if (message.type == 'getUser') {
        var userId = message.message.userId;
        
        if (!users['id' + userId]) {
            users['id' + userId] = {
                'id': userId
            };
        }
        
        console.log('1');
        reply(message, { user: users['id' + userId]});
        console.log('2');
    }
});

function reply(message, body) {
    var msg = {};
    
    msg.server = 'entity';
    msg.type = 'reply';
    msg.message = body;
    msg.mid = message.mid;

    c.publish('/private', msg);

}

sub.callback(function() {
    console.log('Subscribed successfully');
});