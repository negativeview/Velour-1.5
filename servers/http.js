// Deal with HTTP.
var http = require('http');
var timeout = require('connect-timeout');
var express = require('express');
var minj = require('minj');
var less = require('less');
var user_stuff = require('./user-stuff');
var fs = require('fs');

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
	
	user_stuff.setupApp(app, db);

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
					'obj_static.created',
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

	// Get the icon for this project.	
	app.get('/project/:projectId/icon.png', function(req, res) {
		// Is there an image file in the special place?
		fs.stat('./project_icons/' + req.project.id + '.png', function(err, stats) {
			var readStream;
			
			if (err) {
				// We got an error from fstat, use the default icon.
				readStream = fs.createReadStream('../htdocs/images/anonymous.png');
			} else {
				// We did not get an error. Assume that the file is good, and stream it.
				readStream = fs.createReadStream('./project_icons/' + req.project.id + '.png');
			}
			
			// Pipe the file to the result object.
			readStream.pipe(res);
		});
	});
	
	app.get('/project/:projectId', function(req, res) {
		if (!req.project) {
			res.send(404);
			return;
		}
		
		get_characters_for_project(req.project.id, function(err, characters) {
			if (err) {
				res.send(500);
				return;
			}
			
			get_todos_for_project(req.project.id, function(err, todos) {
				if (err) {
					res.send(500);
					return;
				}
			
				get_conversations_for_project(req.project.id, function(err, conversations) {
					if (err) {
						res.send(500);
						return;
					}
				
					res.render(
						'project-info',
						{
							title: req.project.title,
							project: req.project,
							bodyclass: '',
							characters: characters,
							conversations: conversations,
							todos: todos,
							bodyid: 'project-info',
							flash: req.flash(),
							authUser: req.session.authenticatedAs,
						}
					);
				});
			});
		});
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

function get_sub_of_type_for_project(project_id, type_id, cb) {
	if (!project_id) {
		cb('No project id provided');
		return;
	}
	
	db.query().
		select('obj_string.value AS name, obj_static.id').
		from('obj_static').
		join({table: 'base_object', conditions: 'obj_static.current = base_object.id'}).
		join({table: 'obj_string', conditions: 'base_object.title = obj_string.id'}).
		where('obj_static.type = ? AND base_object.project = ?', [type_id, project_id]).
		execute(
			function(err, res) {
				if (err) {
					cb(err);
					return;
				}
				
				cb(null, res);
			}
		);
}

function get_characters_for_project(project_id, cb) {
	get_sub_of_type_for_project(project_id, 5, cb);
}

function get_todos_for_project(project_id, cb) {
	get_sub_of_type_for_project(project_id, 4, cb);
}

function get_conversations_for_project(project_id, cb) {
	get_sub_of_type_for_project(project_id, 6, cb);
}