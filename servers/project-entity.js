var entity_stuff = require('./entity-stuff');

function Project() {
	this.setSpecificId = function(specificId) {
		this._specificId = specificId;
	};
};

Project.prototype = new entity_stuff.BaseEntity();

module.exports.registerType = function(db) {
	Project.prototype.db = db;
	entity_stuff.registerEntityType(2, Project);
};