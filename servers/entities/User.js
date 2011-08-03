var BaseEntity = require('./BaseEntity').BaseEntity;

var entity_stuff = require('../entity-stuff');

function User() {
	this.setSpecificId = function(specificId) {
		this._specificId = specificId;
	};
	
	this.getPower = function() {
		return 1;
	};
	
	this.getProjects = function(cb) {
		var q = this.db.query();
		q.select('project_id')
		 .from('project_user')
		 .where('user_id = ?', [this._id])
		 .execute(function(error, result) {
		 	if (error) {
		 		cb(error);
		 		return;
		 	}
		 	
		 	var ret = [];
		 	var r = result;
		 	var c = function(idx) {
		 		if (idx >= result.length) {
		 			cb(null, ret);
		 			return;
		 		}
		 		
		 		entity_stuff.getObject(r[idx].project_id, function(error, res) {
		 			if (error) {
		 				cb(error);
		 				return;
		 			}
		 			
		 			ret[idx] = res;
		 			c(++idx);
		 		});
		 	};
		 	
		 	c(0);
		 });
	};
};

User.prototype = new BaseEntity();

module.exports.registerType = function(db) {
	User.prototype.db = db;
	entity_stuff.registerEntityType(1, User);
};