exports.mustBeLoggedIn = function(req, res, next) {
	if (typeof req.session.authenticatedAs == 'undefined') {
		req.flash('error', 'You must be logged in to view that page.');
		res.redirect('back');
		return;
	}
	
	next();
};