exports.getset_text = function(st, db, cb) {
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

exports.getset_string = function(st, db, cb) {
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

