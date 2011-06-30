var fs = require('fs');
var user_stuff = require('./user-stuff');

var db;

exports.setupApp = function(app, d) {
	db = d;
	
	app.param('projectId', function(req, res, next, id) {
		var q = db.query().
			select(
				[
					'obj_static.id',
					'obj_static.views',
					'base_object.buzz',
					'base_object.buzz_date',
					'base_object.creator',
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
		q.execute(function(error, rows, cols) {
				if (error) {
					console.log('ERROR: ' + error);
				} else {
					req.project = rows[0];
				}
				next();
			});
	});

	app.get('/project/new', user_stuff.mustBeLoggedIn, function(req, res) {
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
		
		user_stuff.userFromStaticId(req.project.creator, function(err, owner) {
			if (err) {
				res.send(500);
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
					console.log(todos);
				
					get_conversations_for_project(req.project.id, function(err, conversations) {
						if (err) {
							res.send(500);
							return;
						}
						
						get_roster_for_project(req.project.id, function(err, roster) {
							if (err) {
								res.send(500);
								return;
							}
							
							req.project.roster = roster;
						
							res.render(
								'project-info',
								{
									title: req.project.title,
									project: req.project,
									bodyclass: '',
									owner: owner,
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
		});
	});
};

function get_sub_of_type_for_project(project_id, type_id, options, cb) {
	if (!cb) {
		cb = options;
	}
	
	if (!project_id) {
		cb('No project id provided');
		return;
	}
	
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
	console.log('SQL:' + q.sql());
	
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

function get_roster_for_project(project_id, cb) {
	db.query().
		select('obj_static.id, project_user.role_name, project_user.power, obj_string.value as title').
		from('project_user').
		join({table: 'obj_static', conditions: 'obj_static.id = project_user.user_id'}).
		join({table: 'base_object', conditions: 'base_object.id = obj_static.current'}).
		join({table: 'obj_string', conditions: 'obj_string.id = base_object.title'}).
		where('project_user.project_id = ?', [project_id]).
		order({'buzz': false}).
		execute(
			function(err, res) {
				if (err) {
					console.log(err);
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
		cb
	);
}

function get_conversations_for_project(project_id, cb) {
	get_sub_of_type_for_project(project_id, 6, cb);
}