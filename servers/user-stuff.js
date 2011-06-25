var string_stuff = require('./string_handling');
var crypt = require('bcrypt');

var db;

exports.setupApp = function(app, d) {
	db = d;

	/**
	 * Handle logins. After logging in, the session is stored on the request object, and we
	 * shuffle them off to their personalized home page.
	 **/
	app.post('/login', function(req, res) {
		// Find the user by their email address.
		var q = db.query().
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
					req.flash('error', 'Cannot login with v2 login right now.');
					res.redirect('back');
					return;
				}
				
				// They don't have a salt, but their hash does look like md5. They must be using
				// v1. They probably haven't logged in in a while!
				if (user.passhash.length == 32) {
					req.flash('error', 'Cannot login with v1 login right now.');
					res.redirect('back');
					return;
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
							get_static_from_user(user.id, function(err, static_id) {
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
						string_stuff.getset_string(req.body.displayname, db, function(err, id) {
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

function get_static_from_user(user_id, cb) {
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
				
				cb(null, result[0].id);
			}
		);
}