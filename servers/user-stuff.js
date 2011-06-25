var string_stuff = require('./string_handling');
var crypt = require('bcrypt');

var db;

exports.setupApp = function(app, d) {
	db = d;
	
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