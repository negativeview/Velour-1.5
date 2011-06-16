// We need to talk to the other components via faye.
var faye  = require('faye');
var sys   = require('util');
var redis = require('node-redis');

var redis_client = redis.createClient();

redis_client.on("error", function(err) {
    console.log("Redis Error: " + err);
});

var outstanding = {};

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
        
        redis_client.hgetall('user:' + userId, function(err, obj) {
            if (err) {
                console.log("Error fetching user: " + err);
                return;
            }
            
            if (!obj) {
                console.log('Creating new');
                user = new User(userId);
                console.log('2');
                redis_client.hmset('user:' + userId, "id", user.getId(), "email", user.getEmail(), "salt", user.getSalt());
                console.log('3');
            } else {
                user = obj;
                console.log('Got from redis');
            }
            reply(message, { user: user});        
        });
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