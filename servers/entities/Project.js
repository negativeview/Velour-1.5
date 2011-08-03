var BaseEntity = require('./BaseEntity.js').BaseEntity;
var entity_stuff = require('../entity-stuff');

function Project() {
	this.setSpecificId = function(specificId) {
		this._specificId = specificId;
	};
	
	this.getRoster = function(cb) {
		var q = this.db.query();
		q.select('user_id')
		 .from('project_user')
		 .where('project_id = ?', [this._id]);
		console.log(q.sql() + ': ' + this._id);
		q.execute(function(error, result) {
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
		 		
		 		entity_stuff.getObject(r[idx].user_id, function(error, res) {
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

Project.prototype = new BaseEntity();

exports.registerType = function(db) {
	Project.prototype.db = db;
	entity_stuff.registerEntityType(2, Project);
};