var fs = require('fs');
//var user_stuff = require('./user-stuff');
var md = require('markdown').markdown;
var images = require('../image-stuff');
//var string_handling = require('./string_handling');
var entity_stuff = require('../entity-stuff');
var tasks = require('../helpers/Tasks');

var permissionHelper = require('../helpers/Permissions');

exports.setupUrlHandlers = function(app) {
	app.param('projectId', function(req, res, next, id) {
		entity_stuff.getObject(id, function(error, res) {
			if (error)
				return console.log('Error: ' + error);
			
			req.project = res;
			next();
		});
	});
};

exports.setupRoutes = function(app) {
	app.post('/project/new', permissionHelper.mustBeLoggedIn, function(req, res) {
		entity_stuff.newObject(
			{
				type: 2,
				creator: req.session.authenticatedAs,
				title_raw: req.body.title,
				description_raw: req.body.body
			},
			function (error, result) {
				if (error) throw new Error(error);
				res.redirect('/project/' + result.id);				
			}
		);
	});

	app.get('/project/new', permissionHelper.mustBeLoggedIn, function(req, res) {
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
	});

	app.post('/project/:projectId/icon.png', function(req, res) {
		fs.open('./project_icons/' + req.project.id + '.png', 'w', function(error, fd) {
			if (error) {
				console.log(error);
				res.end(error);
				return;
			}
			
			var data = req.body.data.replace(/data:[^;]+;base64,/, '');
			
			var buff = new Buffer(data, 'base64');
			fs.write(fd, buff, 0, buff.length, 0, function(error, r) {
				if (error) {
					console.log(error);
					res.end(error);
					return;
				}
				
				images.makeImageOfSize('./project_icons/' + req.project.id, 150, function(err, metadata) {
					if (err) throw err;
					
					res.send('ok');
					
					images.makeImageOfSize('./project_icons/' + req.project.id, 45, function(err, metadata) {
						if (err) throw err;
					});
				});
			});
		});
	});

	// Get the icon for this project.	
	app.get('/project/:projectId/thumb.png', function(req, res) {
		// Is there an image file in the special place?
		fs.stat('./project_icons/' + req.project.id + '-45.png', function(err, stats) {
			var readStream;
			
			if (err) {
				// We got an error from fstat, use the default icon.
				readStream = fs.createReadStream('../htdocs/images/anonymous.png');
			} else {
				// We did not get an error. Assume that the file is good, and stream it.
				readStream = fs.createReadStream('./project_icons/' + req.project.id + '-45.png');
			}
			
			// Pipe the file to the result object.
			readStream.pipe(res);
		});
	});

	// Get the icon for this project.	
	app.get('/project/:projectId/icon.png', function(req, res) {
		// Is there an image file in the special place?
		fs.stat('./project_icons/' + req.project.id + '-150.png', function(err, stats) {
			var readStream;
			
			if (err) {
				// We got an error from fstat, use the default icon.
				readStream = fs.createReadStream('../htdocs/images/anonymous.png');
			} else {
				// We did not get an error. Assume that the file is good, and stream it.
				readStream = fs.createReadStream('./project_icons/' + req.project.id + '-150.png');
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

		var task = new tasks.serialTask(req, res);
		task.addTask(
			function(req, res, next) {
				entity_stuff.getObject(req.project.getCreator(), function(err, owner) {
					req.project.creator = owner;
					next();
				});
			}
		);
		task.addTask(
			function(req, res, next) {
				get_characters_for_project(req.project.id, req, function(err, characters) {
					req.project.characters = characters;
					next();
				});
			}
		);
		task.addTask(
			function(req, res, next) {
				get_todos_for_project(req.project.id, req, function(err, todos) {
					req.project.todos = todos;
					next();
				});
			}
		);
		task.addTask(
			function(req, res, next) {
				get_conversations_for_project(req.project.id, req, function(err, conversations) {
					req.project.conversations = conversations;
					next();
				});
			}
		);
		task.addTask(
			function(req, res, next) {
				req.project.getRoster(function(error, roster) {
					if (error)
						throw new Error(error);
					console.log('roster: ' + roster);
					req.project.roster = roster;
					next();
				});
			}
		);
		
		task.start(function() {
			if (req.project.desc) {
				req.project.desc = md.toHTML(req.project.desc);
			}
			res.render(
				'project-info',
				{
					title: req.project.getTitle(),
					project: req.project,
					bodyclass: 'left',
					owner: req.project.creator,
					characters: req.project.characters,
					conversations: req.project.conversations,
					todos: req.project.todos,
					bodyid: 'project-info',
					flash: req.flash(),
					authUser: req.session.authenticatedAs,
				}
			);
		});
	});
};

function get_sub_of_type_for_project(project_id, type_id, options, req, cb) {
	if (!cb) {
		cb = options;
	}
	
	if (!project_id) {
		cb('No project id provided');
		return;
	}
	
	db = req.db;
	var select = 'obj_string.value AS name, obj_static.id';
	
	if (options && typeof options == 'object' && options['extra_select']) {
		select += ', ' + options['extra_select'];
	}
	
	var q = db.
		query().
		select(select).
		from('obj_static').
		join({table: 'base_object', conditions: 'obj_static.current = base_object.id'}).
		join({table: 'obj_string', conditions: 'base_object.title = obj_string.id'});

	if (options && typeof options == 'object' && options['extra_join']) {
		q.join(options['extra_join']);
	}
	
	var where = 'obj_static.type = ? AND base_object.project = ?';
	if (options && typeof options == 'object' && options['extra_where']) {
		where += ' AND ' + options['extra_where'];
	}
	
	q.where(where, [type_id, project_id]);
	
	q.execute(
		function(err, res) {
			if (err) {
				cb(err);
				return;
			}
			cb(null, res);
		}
	);
}

function get_characters_for_project(project_id, req, cb) {
	get_sub_of_type_for_project(project_id, 5, {}, req, cb);
}

function get_todos_for_project(project_id, req, cb) {
	get_sub_of_type_for_project(
		project_id,
		4,
		{
			'extra_join':
			{
				     table: 'todo',
				conditions: 'todo.id = base_object.specific_id'
			},
			'extra_select': 'todo.*',
			'extra_where': 'todo.status <> 1'
		},
		req,
		cb
	);
}

function get_conversations_for_project(project_id, req, cb) {
	get_sub_of_type_for_project(project_id, 6, {}, req, cb);
}