// We need to talk to the other components via faye.
var faye  = require('faye');
var sys   = require('util');

var outstanding = {};

var c = new Faye.Client('http://localhost:8080/private');
var sub = c.subscribe('/private', function(message) {
    } else if (message.type == 'addUser') {
        reply(message, {user: user});
    } else if (message.type == 'getUserByEmail') {
    	var email = message.message.email;
    	console.log("Looking up " + email);
    	for (var i in users) {
    		if (users[i].email == email) {
    			reply(message, {user: users[i]});
    			return;
    		}
    	}
    	
    	reply(message, {user: null});
    }
});