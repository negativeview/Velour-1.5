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

function check_duplicate_email(email, cb) {
	db.query().select(['id']).from('users').where('email = ?', [email]).execute(function(err, res) {
		if (err) {
			cb(err);
		} else {
			if (res.length) {
				cb('Email address already in use.');
			} else {
				cb(null);
			}
		}
	});
}

function getset_text(st, cb) {
	db.query().select(['id']).from('obj_text').where('value = ?', [st]).execute(function(err, res) {
		if (err) {
			cb(err);
		} else {
			if (res.length) {
				cb(null, res[0].id);
			} else {
				db.query().insert('obj_text', ['value'], [st]).execute(function(err, res) {
					if (err) {
						cb(err);
					} else {
						cb(null, res.id);
					}
				});
			}
		}
	});
}

function getset_string(st, cb) {
	db.query().select(['id']).from('obj_string').where('value = ?', [st]).execute(function(err, res) {
		if (err) {
			cb(err);
		} else {
			if (res.length) {
				cb(null, res[0].id);
			} else {
				db.query().insert('obj_string', ['value'], [st]).execute(function(err, res) {
					if (err) {
						cb(err);
					} else {
						cb(null, res.id);
					}
				});
			}
		}
	});
}

function gen_new_pass(password, cb) {
	crypt.gen_salt(10, function(err, salt) {
		if (err) {
			cb(err);
			return;
		}
		var s = salt;
		crypt.encrypt(password, s, function(err, hash) {
			if (err) {
				cb(err);
				return;
			}
			cb(null, hash);
		});
	});
}

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
	
	var user_cache = {};
	
	app.param('projectId', function(req, res, next, id) {
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
			where('obj_static.type = 2 AND obj_static.id = ?', [id]);
		console.log(q.sql());
		q.execute(function(error, rows, cols) {
				if (error) {
					console.log('ERROR: ' + error);
				} else {
					req.project = rows[0];
				}
				next();
			});
	});
	
	// Tell the system how to resolve the userId in a route.
	// NOTE: Right now we're doing double the work just to debug and test the
	//       code that makes them able to run in what is effectively parallel.
	app.param('userId', function(req, res, next, id) {
		if (user_cache['u' + id]) {
			req.user = user_cache['u' + id];
			next();
			return;
		}
			
		var q = db.query().
			select(
				[
					'obj_static.id',
					'obj_static.views',
					'base_object.created',
					'base_object.buzz',
					'users.email',
					'users.passhash',
					'users.power',
					'base_object.buzz_date',
					{
						'title': '(select value from obj_string where id = base_object.title)',
						'desc': '(select value from obj_text where id = base_object.description)'
					}
				]
			).
			from('obj_static').
			join({table: 'base_object', conditions: 'base_object.id = obj_static.current'}).
			join({table: 'users', conditions: 'base_object.specific_id = users.id'}).
			where('obj_static.type = 1 AND obj_static.id = ?', [id]);
		console.log(q.sql());
		q.execute(function(error, rows, cols) {
				if (error) {
					console.log('ERROR: ' + error);
				} else {
					req.user = rows[0];
					user_cache['u' + id] = req.user;
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
						
						// We now know which User object this is. We need our static id though.
						if (re) {
						
							db.query().
								select(['obj_static.id']).
								from('obj_static').
								join({table: 'base_object', conditions: 'base_object.id = obj_static.current'}).
								where('obj_static.type = 1 AND base_object.specific_id = ?', [rows[0].id]).
								execute(
									function(err, result) {
										if (err) {
											console.log(err);
											req.flash('error', err);
											res.redirect('back');
											return;
										}
										
										req.session.authenticatedAs = result[0].id;
										res.redirect('back');
									}
								);
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
				bodyclass: '',
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
		check_duplicate_email(req.body.email, function(err) {
			if (err) {
				req.flash('error', err);
				res.redirect('back');
				return;
			}
			
			gen_new_pass(req.body.password1, function(err, hash) {
				if (err) {
					req.flash('error', err);
					res.redirect('back');
					return;
				}
				db.query().
					insert('users',
						['passhash', 'email', 'power', 'theme'],
						[hash, req.body.email, 2, 'public']
					).
					execute(function(error, result) {
						if (error) {
							req.flash('error', error);
							res.redirect('back');
							return;
						}
						getset_string(req.body.displayname, function(err, id) {
							if (err) {
								res.flash('error', err);
								res.redirect('back');
								return;
							}
							db.query().
								insert('base_object',
									['title', 'created', 'buzz', 'buzz_date', 'specific_id'],
									[id, new Date(), 0.00, new Date(), result.id]
								).execute(function(error, result) {
									if (error) {
										req.flash('error', error);
										res.redirect('back');
										return;
									}
									db.query().
										insert('obj_static',
											['type', 'current', 'views'],
											[1, result.id, 0]
										).execute(function(error, result) {
											if (error) {
												req.flash('error', error);
												res.redirect('back');
												return;
											}
											res.redirect('/user/' + result.id);
										});
								});
						});
					});
			});
		})
	});
	
	app.get('/project/:projectId', function(req, res) {
		if (!req.project) {
			res.send(404);
			return;
		}
		
		res.render(
			'project-info',
			{
				title: req.project.title,
				project: req.project,
				bodyclass: '',
				bodyid: 'project-info',
				flash: req.flash(),
				authUser: req.session.authenticatedAs,
			}
		);
	});
	
	app.get('/user/:userId', function(req, res) {
		if (!req.user) {
			res.send(404);
			return;
		}
		
		db.query().
			select(['project_user.project_id', 'obj_string.value', 'project_user.role_name']).
			from('project_user').
			join({table: 'obj_static', conditions: 'project_user.project_id = obj_static.id'}).
			join({table: 'base_object', conditions: 'base_object.id = obj_static.current'}).
			join({table: 'obj_string', conditions: 'obj_string.id = base_object.title'}).
			where('user_id = ?', [req.user.id]).
			execute(function(err, result) {
				if (err) {
					req.flash('error', err);
				}
				
				var by_role = {};
				for (var project in result) {
					project = result[project];
					
					if (!by_role[project.role_name])
						by_role[project.role_name] = [];
					by_role[project.role_name][by_role[project.role_name].length] = project;
				}
				res.render(
					'user-info',
					{
						title: req.user.title,
						user: req.user,
						bodyclass: '',
						projects: by_role,
						bodyid: 'user-info',
						flash: req.flash(),
						authUser: req.session.authenticatedAs,
					}
				);				
			});
	});
	
	app.listen(8081);
}