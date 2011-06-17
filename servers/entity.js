// We need to talk to the other components via faye.
var faye  = require('faye');
var sys   = require('util');

var outstanding = {};
var users = {};

function Entity() {
    this.valid = false;
    this.created = new Date();
}
User.prototype = new Entity;
User.prototype.constructor = User;

function User(id) {
    this.id = id;
    this.email = '';
    this.salt = '';
    this.passhash = '';
    this.email_validated = false;
    this.display_name = 'Anonymous Coward';
}

User.prototype.toString = function() {
    JSON.stringify(this, ['id', 'email', 'salt', 'email_validated', 'display_name', 'valid', 'created']);
}

// Keep a counter to have a unique message id.
var id = 0;

var c = new Faye.Client('http://localhost:8080/private');
var sub = c.subscribe('/private', function(message) {
    if (message.type == 'getUser') {
        var userId = message.message.userId;
        
        var user = users['user:' + userId];
        if (!user) {
            console.log('Creating new user');
            user = new User(userId);
            users['user:' + userId] = user;
        }
        reply(message, { user: user});        
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