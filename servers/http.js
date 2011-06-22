// Deal with HTTP.
var http = require('http');
var crypt = require('bcrypt');
var timeout = require('connect-timeout');
var express = require('express');
var minj = require('minj');

var app = express.createServer();
app.use(minj.middleware({ src: __dirname + '/../htdocs'}));
app.use(express.static(__dirname + '/../htdocs'));
app.use(express.bodyParser());
app.use(express.cookieParser());
app.use(express.session({ secret: "foobar" }));
app.use(express.logger());
app.use(timeout());

// We need to talk to the other components via faye.
var faye = require('faye');

var outstanding = {};

// Keep a counter to have a unique message id.
var id = 0;

// Use ejs rendering for our templates.
app.set('view engine', 'ejs');
app.set('view options', { cache: true});

// Tell the system how to resolve the userId in a route.
// NOTE: Right now we're doing double the work just to debug and test the
//       code that makes them able to run in what is effectively parallel.
app.param('userId', function(req, res, next, id) {
    add_required_message(req, next, 'getUser', 'user', {userId: id});
});

app.get('/task/new', function(req, res) {
	if (req.session.authenticatedAs) {
		res.render(
			'task-new',
			{
				title: 'New Task',
				bodyclass: '',
				bodyid: 'task-new',
				flash: req.flash(),
				authUser: req.session.authenticatedAs,
			}
		);
	} else {
		req.flash('error', 'You must be logged in to create a task');
		res.redirect('back');
	}
});

app.get('/project/new', function(req, res) {
	if (req.session.authenticatedAs) {
		res.render(
			'project-new',
			{
				title: 'New Project',
				bodyclass: '',
				bodyid: 'project-new',
				flash: req.flash(),
				authUser: req.session.authenticatedAs,
			}
		);
	} else {
		req.flash('error', 'You must be logged in to create a project');
		res.redirect('back');
	}
});

app.post('/login', function(req, res) {
	message_with_reply(
		'getUserByEmail',
		{
			email: req.body.email
		},
		function(err, reply) {
			if (reply.message.user == null) {
				req.flash('error', 'No such user');
				res.redirect('back');
			}
			crypt.compare(req.body.password, reply.message.user.passhash, function(err, re) {
				if (re) {
					req.session.authenticatedAs = reply.message.user.id;
					res.redirect('back');
				} else {
					req.flash('error', err);
					res.redirect('back');
				}
			});
		}
	);
});

// Due to the param stuff above, this code is super easy, as user and
// anotherUser are populated from above. We just have to pass stuff to the
// view.
app.get('/', function(req, res) {
    res.render(
        'dashboard',
        {
            title: 'Dashboard',
            bodyclass: '',
            bodyid: 'dashboard',
			flash: req.flash(),
			authUser: req.session.authenticatedAs,
        }
    );
});

app.get('/register', function(req, res) {
    res.render(
        'register',
        {
            title: 'Register',
            bodyclass: 'nowatch',
            bodyid: 'register',
			flash: req.flash(),
			authUser: req.session.authenticatedAs,
        }
    );
});

app.get('/credits', function(req, res) {
	res.render(
		'credits',
		{
			title: 'Credits',
			bodyclass: '',
			bodyid: 'credits',
			flash: req.flash(),
			authUser: req.session.authenticatedAs,
		}
	);
});

app.post('/register', function(req, res) {
	if (req.body.password1 != req.body.password2) {
		req.flash('error', 'Your passwords did not match.');
		res.redirect('/register/');
		return;
	}
	crypt.gen_salt(10, function(err, salt) {
		crypt.encrypt(req.body.password1, salt, function(err, hash) {
			message_with_reply(
				'addUser',
				{
					email: req.body.email,
					displayname: req.body.displayname,
					password: hash,
					roles: req.body.role
				},
				function(err, reply) {
					res.redirect('/user/' + reply.message.user.id);
				}
			);
		});
	});
});

app.get('/user/:userId', function(req, res) {
    res.render(
        'user-info',
        {
            title: req.user.user.display_name,
            user: req.user.user,
            bodyclass: '',
            bodyid: 'user-info',
			flash: req.flash(),
			authUser: req.session.authenticatedAs,
        }
    );
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