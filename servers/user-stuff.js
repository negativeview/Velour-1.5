var string_stuff = require('./string_handling');
var crypt = require('bcrypt');
var crypto = require('crypto');
var fs = require('fs');
var md = require('markdown').markdown;
var entity_stuff = require('./entity-stuff');
var quips = require('./quip-stuff');
var images = require('./image-stuff');

exports.mustBeLoggedIn = function(req, res, next) {
	if (typeof req.session.authenticatedAs == 'undefined') {
		req.flash('error', 'You must be logged in to view that page.');
		res.redirect('back');
		return;
	}
	
	next();
};

exports.setupApp = function(app) {
	app.param('userId', function(req, res, next, id) {
		entity_stuff.getObject(id, function(err, user) {
			if (err) {
				console.log('ERROR: ' + error);
				next(error);
				return;
			}
			
			req.user = user;
			next();
		});
	});
	
	app.use(function(req, res, next) {
		if (typeof req.session !== 'undefined') {
			if (req.session.authenticatedAs) {
				get_user_from_static_id(req.session.authenticatedAs, req.db, function(err, user) {
					if (err) {
						res.flash('error', err);
						res.redirect('back');
						return;
					}
					
					req.loggedInUser = user;
					next();
				});
				return;
			}
		}
		next();
	});
	
	/* Set up the icon. This will eventually be pulled out into some sort of helper function. */
	app.post('/user/:userId/avatar.png', isLoggedInAs, function(req, res) {
	
		/* First get the file contents. */

		// Remove the base 64 header.
		var data = req.body.data.replace(/data:[^;]+;base64,/, '');
		
		// Get the md5 of the contents for the file name.
		var fileName = crypto.createHash('md5').update(data).digest('hex');
		fileName = './user_icons/' + fileName;
		
		fs.stat(fileName, function(err, stats) {
			if (err) {
				if (err.code == 'ENOENT') {
					string_stuff.getset_string(fileName, req.db, function(err, id) {
						if (err) throw err;

						var q = req.db.query().insert(
							'base_object',
							['creator', 'title', 'created'],
							[req.session.authenticatedAs, id, {value: 'NOW()', escape: false}]
						);

						q.execute(function(error, result) {
							if (error) throw error;
							
							var q = req.db.query().insert(
								'obj_static',
								['type', 'current', 'views', 'created'],
								[7, result.id, 0, {value: 'NOW()', escape: false}]
							);
							
							q.execute(function(error, result) {
								if (error) throw error;
								
								var total_object_id = result.id;
								
								entity_stuff.updateImageId(req.db, req.session.authenticatedAs, total_object_id, function(err) {
									if (err) throw err;
								});
								
							});
						});
					});

					doUpload(fileName, data, function(err) {
						if (err)
							throw err;
						
						res.send('ok');
					});
				}
				return;
			} else {
				// This file should already exist. Find it in the database.
			}
			return;
		});
	});
	
	// Get the icon for this project.	
	app.get('/user/:userId/avatar.png', function(req, res) {
		// Is there an image file in the special place?
		
		var readStream;

		if (req.user.image_id) {
			var q = req.db.query().select('obj_string.value')
			                      .from('obj_static')
			                      .join({table: 'base_object', conditions: 'base_object.id = obj_static.current'})
			                      .join({table: 'obj_string', conditions: 'base_object.title = obj_string.id'})
			                      .where('obj_static.id = ?', [req.user.image_id]);
			
			q.execute(function(err, res) {
				if (err) throw err;
				console.log(res);
			});
			return;
		} else {
			readStream = fs.createReadStream('../htdocs/images/anonymous.png');
		}
		readStream.pipe(res);
	});
	
	// Get the icon for this project.	
	app.get('/user/:userId/thumb.png', function(req, res) {
		// Is there an image file in the special place?
		fs.stat('./user_icons/' + req.user.id + '-45.png', function(err, stats) {
			var readStream;
			
			if (err) {
				// We got an error from fstat, use the default icon.
				readStream = fs.createReadStream('../htdocs/images/anonymous.png');
			} else {
				// We did not get an error. Assume that the file is good, and stream it.
				readStream = fs.createReadStream('./user_icons/' + req.user.id + '-45.png');
			}
			
			// Pipe the file to the result object.
			readStream.pipe(res);
		});
	});
	
	app.get('/logout', function(req, res) {
		req.session.destroy();
		res.redirect('back');
	});
	
	app.get('/user/:userId/editHistory', isLoggedInAs, function(req, res) {
		entity_stuff.getHistory(req.db, req.user.id, function(error, re) {
			if (error) {
				console.log(error);
				return;
			}
			
			res.render(
				'user-edit-history',
				{
					history: re,
					quip: quips.getQuip(),
					title: 'Edit History',
					user: req.user,
					bodyclass: '',
					bodyid: 'edit-user-history',
					flash: req.flash(),
					authUser: req.session.authenticatedAs
				}
			);
		});
	});
	
	app.post('/user/:userId/edit', isLoggedInAs, function(req, res) {
		entity_stuff.updateDescription(req.db, req.user.id, req.body.bio, function(error, re) {
			if (error) {
				req.error = error;
				return;
			}
			
			res.redirect('/user/' + req.user.id);
		});
	});
	
	app.get('/user/:userId/edit', isLoggedInAs, function(req, res) {
		res.render(
			'user-edit',
			{
				quip: quips.getQuip(),
				title: 'Edit User',
				user: req.user,
				bodyclass: '',
				bodyid: 'edit-user',
				flash: req.flash(),
				authUser: req.session.authenticatedAs
			}
		);
	});

	app.get('/user/:userId', function(req, res) {
		if (!req.user) {
			res.send(404);
			return;
		}
		
		req.user.getDescription(function(error, description) {
			if (error) {
				throw new Error(error);
			}
			
			req.user.desc = description;
			
			req.user.getProjects(function(error, projects) {
				if (error) {
					throw new Error(error);
				}
				
				console.log('projects:');
				console.log(projects);
				
				res.render(
						'user-info',
						{
							quip: quips.getQuip(),
							title: req.user.getTitle(),
							user: req.user,
							bodyclass: '',
							projects: projects,
							bodyid: 'user-info',
							flash: req.flash(),
							authUser: req.session.authenticatedAs,
						}
				);				
			});
	
		});
				
	});

	/**
	 * Handle logins. After logging in, the session is stored on the request object, and we
	 * shuffle them off to their personalized home page.
	 **/
	app.post('/login', function(req, res) {
		// Find the user by their email address.
		var q = req.db.query().
			select('*').
			from('users').
			where('email = ?', [req.body.email]);
		q.execute(
			function(error, rows, cols) {
				// Go ahead and bail out if there's no user. I might change this later to not
				// leak info.
				if (rows.length == 0) {
					req.flash('error', 'No such user');
					res.redirect('back');
					return;
				}
				
				// Figure out what password storing method I used for them. There are now... three.
				var user = rows[0];
				
				// If they have a salt, they must be using the individually-salted old method.
				// They aren't TOO out of date then...
				if (user.salt && user.salt !== '') {
					if (user.passhash == crypto.createHash('md5').update(req.body.password + user.salt).digest('hex')) {
						get_static_from_user(user.id, req.db, function(err, static_id) {
							if (err) {
								req.flash('error', err);
								res.redirect('back');
								return;
							}
							
							// This block can be done in the background. If it errors, we can just
							// do it the next time that they log in. Whatevs.
							gen_new_pass(req.body.password, function(err, hash) {
								if (err) {
									req.flash('error', err);
									res.redirect('back');
									return;
								}
								
								req.db.query().
									update('users').
									set({passhash: hash, salt: null}).
									where('id = ?', [user.id]).
									execute(function(err, result) {
										if (err) {
											console.log(err);
										}
									});
							});
							
							req.session.authenticatedAs = static_id;
							res.redirect('/');
							return;
						});
						return;
					} else {
						req.flash('error', 'Wrong username or password');
						res.redirect('back');
						return;
					}
				}
				
				// They don't have a salt, but their hash does look like md5. They must be using
				// v1. They probably haven't logged in in a while!
				if (user.passhash.length == 32) {
					if (user.passhash == crypto.createHash('md5').update(req.body.password + 'argtech').digest('hex')) {
						get_static_from_user(user.id, req.db, function(err, static_id) {
							if (err) {
								req.flash('error', err);
								res.redirect('back');
								return;
							}
							
							// This block can be done in the background. If it errors, we can just
							// do it the next time that they log in. Whatevs.
							gen_new_pass(req.body.password, function(err, hash) {
								if (err) {
									req.flash('error', err);
									res.redirect('back');
									return;
								}
								
								req.db.query().
									update('users').
									set({passhash: hash, salt: null}).
									where('id = ?', [user.id]).
									execute(function(err, result) {
										if (err) {
											console.log(err);
										}
									});
							});
							
							req.session.authenticatedAs = static_id;
							res.redirect('/');
							return;
						});
						return;
					} else {
						req.flash('error', 'Wrong username or password');
						res.redirect('back');
						return;
					}
				}
				
				// They should be a new user, yay! Or their passhash has been auto-updated.
				// Either way, this is the good, secure, path.
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
							get_static_from_user(user.id, req.db, function(err, static_id) {
								if (err) {
									req.flash('error', err);
									res.redirect('back');
									return;
								}
								
								req.session.authenticatedAs = static_id;
								res.redirect('/');
							});
							return;
						} else {
							req.flash('error', 'Wrong username or password');
							res.redirect('back');
							return;
						}
					}
				);
			}
		);
	});

	
	app.get('/register', function(req, res) {
		res.render(
			'register',
			{
				quip: quips.getQuip(),
				title: 'Register',
				bodyclass: '',
				bodyid: 'register',
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
		check_duplicate_email(req.body.email, req.db, function(err) {
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
				req.db.query().
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
						string_stuff.getset_string(req.body.displayname, req.db, function(err, id) {
							if (err) {
								res.flash('error', err);
								res.redirect('back');
								return;
							}
							req.db.query().
								insert('base_object',
									['title', 'created', 'buzz', 'buzz_date', 'specific_id'],
									[id, new Date(), 0.00, new Date(), result.id]
								).execute(function(error, result) {
									if (error) {
										req.flash('error', error);
										res.redirect('back');
										return;
									}
									req.db.query().
										insert('obj_static',
											['type', 'current', 'views'],
											[1, result.id, 0]
										).execute(function(error, result) {
											if (error) {
												req.flash('error', error);
												res.redirect('back');
												return;
											}
											req.session.authenticatedAs = result.id;
											res.redirect('/');
										});
								});
						});
					});
			});
		})
	});
};
	
function check_duplicate_email(email, db, cb) {
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

function get_user_from_static_id(id, db, cb) {
	var q = db.query().
		select(
			[
				'obj_static.id',
				'obj_static.views',
				'base_object.created',
				'base_object.buzz',
				'base_object.image_id',
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
	q.execute(function(error, rows, cols) {
		if (error) {
			cb(error);
		} else {
			cb(null, rows[0]);
		}
	});
}

exports.userFromStaticId = get_user_from_static_id;

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

function get_static_from_user(user_id, db, cb) {
	db.query().
		select(['obj_static.id']).
		from('obj_static').
		join({table: 'base_object', conditions: 'base_object.id = obj_static.current'}).
		where('obj_static.type = 1 AND base_object.specific_id = ?', [user_id]).
		execute(
			function(err, result) {
				if (err) {
					cb(err);
					return;
				}
				
				if (result.length == 0) {
					cb('Cannot find user ' + user_id + '.');
					return;
				}
				
				cb(null, result[0].id);
			}
		);
}

function isLoggedInAs(req, res, next) {
	if (!req.user) {
		req.error = 'No such user';
	}
	
	if (!req.session.authenticatedAs) {
		req.error = 'Not logged in';
	}
	
	if (req.session.authenticatedAs != req.user.id) {
		req.error = 'Permission denied.';
		console.log(req.session.authenticatedAs + ' trying to edit ' + req.user);
	}
	
	if (req.error) {
		next(req.error);
		return;
	}
	
	next();
}

function doUpload(fileName, data, cb) {
	fs.open(fileName, 'w', function(error, fd) {
		if (error)
			return cb(error);
		
		var buff = new Buffer(data, 'base64');
		fs.write(fd, buff, 0, buff.length, 0, function(error, r) {
			if (error)
				return cb(error);
			
			images.makeImageOfSize(fileName, 150, function(err, metadata) {
				if (err)
					return cb(error);
				
				cb();
				
				images.makeImageOfSize(fileName, 45, function(err, metadata) {
					if (err)
						return cb(error);
				});
			});
		});
	});
}