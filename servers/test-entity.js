var faye = require('faye');
var c = new Faye.Client('http://localhost:8080/private');

var waiting = {};

exports.testBasic = function(test) {
	var msg = {};
	msg.server = 'entity-test';
	msg.type = 'getUser';
	msg.message = { userId: 1 };
	msg.mid = 'test';
	
	c.publish('/private', msg);
	
	waiting['test'] = {
		'ob': test,
		'callback': function(test, message) {
			test.equal(1, message.message.user_id);
			test.equal('Daniel Grace', message.message.display_name);
			test.equal('dgrace@doomstick.com', message.message.email);
			test.equal('admin', message.message.power);
			test.done();
		}
	};
}

c.subscribe('/private', function(message) {
	if (waiting[message.mid]) {
		waiting[message.mid].callback(waiting[message.mid].ob, message);
		delete waiting[message.mid];
	} else {
		console.log('do not recognize: ' + message.mid);
	}
});
