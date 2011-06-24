// Deal with HTTP.
var http = require('http');
var crypt = require('bcrypt');
var timeout = require('connect-timeout');
var express = require('express');
var minj = require('minj');
var less = require('less');

var mysql = require('db-mysql');
var db = new mysql.Database(
	{
		hostname: 'localhost',
		user:     'root',
		password: '',
		database: 'argtech'
	}
);
db.on('error', function(error) {
	console.log('ERROR: ' + error);
});
db.on('ready', function(server) {
	console.log('Connected to ' + server.hostname + ' (' + server.versin + ')');
	setupExpress();
});
db.connect();

var app;
function setupExpress() {
	app = express.createServer();
	app.use(minj.middleware({ src: __dirname + '/../htdocs'}));
	app.use(express.static(__dirname + '/../htdocs'));
	app.use(express.bodyParser());
	app.use(express.cookieParser());
	app.use(express.session({ secret: "foobar" }));
	//app.use(express.logger());
	app.use(timeout());

	// Use ejs rendering for our templates.
	app.set('view engine', 'ejs');
	app.set('view options', { cache: true});
	
	// Tell the system how to resolve the userId in a route.
	// NOTE: Right now we're doing double the work just to debug and test the
	//       code that makes them able to run in what is effectively parallel.
	app.param('userId', function(req, res, next, id) {
		var q = db.query().
			select(
				[
					'obj_static.id',
					'obj_static.views',
					'base_object.created',
					'base_object.buzz',
					'base_object.buzz_date',
					{
						'title': '(select value from obj_string where id = base_object.title)',
						'desc': '(select value from obj_text where id = base_object.description)'
					}
				]
			).
			from('obj_static').
			join({table: 'base_object', conditions: 'base_object.id = obj_static.current'}).
			where('obj_static.type = 1 AND obj_static.id = ?', [id]);
		console.log(q.sql());
		q.execute(function(error, rows, cols) {
				if (error) {
					console.log('ERROR: ' + error);
				} else {
					req.user = rows[0];
				}
				next();
			});
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
		var q = db.query().
			select('*').
			from('users').
			where('email = ?', [req.body.email]);
		q.execute(
			function(error, rows, cols) {
				if (rows.length == 0) {
					req.flash('error', 'No such user');
					res.redirect('back');
					return;
				}
				
				crypt.compare(
					req.body.password,
					rows[0].passhash,
					function(err, re) {
						if (err) {
							req.flash('error', err);
							res.redirect('back');
							return;
						}
						
						if (re) {
							req.session.authenticatedAs = rows[0].id;
							res.redirect('back');
							return;
						}
						
						if (rows[0].passhash.length == 32) {
							req.flash('error', 'Seem to be using an old password method:' + rows[0].passhash);
							res.redirect('back');
						} else {
							req.flash('error', 'Incorrect username or password: ' + rows[0].passhash);
							res.redirect('back');
						}
						
						// We are using an old password management thing. TODO: Add in compat. Inline update.
					}
				);
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
			if (err) {
				req.flash('error', err);
				res.redirect('back');
				return;
			}
			req.flash('info', req.body.password1);
			var s = salt;
			crypt.encrypt(req.body.password1, s, function(err, hash) {
				if (err) {
					req.flash('error', err);
					res.redirect('back');
					return;
				}
				db.query().
					insert('users',
						['display_name', 'passhash', 'email'],
						[req.body.displayname, hash, req.body.email]
					).
					execute(function(error, result) {
						if (error) {
							console.log('ERROR: ' + error);
						} else {
							console.log(result);
							res.redirect('/user/' + result.id);
						}
					});
			});
		});
	});
	
	app.get('/user/:userId', function(req, res) {
		if (!req.user) {
			res.send(404);
			return;
		}
		res.render(
			'user-info',
			{
				title: req.user.title,
				user: req.user,
				bodyclass: '',
				bodyid: 'user-info',
				flash: req.flash(),
				authUser: req.session.authenticatedAs,
			}
		);
	});
	
	app.listen(8081);
}