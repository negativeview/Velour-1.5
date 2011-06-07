// Deal with HTTP.
var http = require('http');

// Create an "Express," which makes HTTP stuff easier by handling a lot for you
var express = require('express');
var app = express.createServer();

// We need to talk to the other components via faye.
var faye = require('faye');

var outstanding = {};

// Keep a counter to have a unique message id.
var id = 0;

// Use ejs rendering for our templates.
app.set('view engine', 'ejs');

// Tell the system how to resolve the userId in a route.
// NOTE: Right now we're doing double the work just to debug and test the
//       code that makes them able to run in what is effectively parallel.
app.param('userId', function(req, res, next, id) {
    add_required_message(req, next, 'getUser', 'user', {userId: id});
});

// Due to the param stuff above, this code is super easy, as user and
// anotherUser are populated from above. We just have to pass stuff to the
// view.
app.get('/user/:userId', function(req, res) {
    res.render('index', {message: {message: req.user.userId}});
});

var c = new Faye.Client('http://localhost:8080/private');
var sub = c.subscribe('/private', function(message) {
    if (message.type == 'reply') {
        if (outstanding[message.mid]) {
            outstanding[message.mid](null, message);
            outstanding[message.mid] = null;
        } else {
            console.log('No message named ' + message.mid);
        }
    }
});

sub.callback(function() {
    console.log('Subscribed successfully');
});

app.listen(8081);

function message_with_reply(message, data, callback) {
    var msg = {};
    msg.server = 'butler';
    msg.type = message;
    msg.message = data;
    
    var message_id = 'butler-' + (++id);
    msg.mid = message_id;
    outstanding[message_id] = callback;
    c.publish('/private', msg);
    
    return message_id;
}

function add_required_message(req, next, messageName, key, data) {
    if (!req.requiredMessages)
        req.requiredMessages = {};
    
    var r = req;
    var k = key;
    var n = next;
    
    var l = 0;
    for (var i in r.requiredMessages) {
        l++;
    }

    var mid = message_with_reply(messageName, data, function(err, message) {
        r[k] = message.message;
        delete r.requiredMessages[message.mid];
        
        var l = 0;
        for (var i in r.requiredMessages) {
            console.log('i:' + i);
            l++;
        }
        
        if (l == 0) {
            n();
        }
    });
    r.requiredMessages[mid] = 1;
};