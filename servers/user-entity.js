var User = function(id) {
	this.user_id = id;
	this.display_name = 'Daniel Grace';
	this.global_id = null;
	this.email = 'dgrace@doomstick.com';
	this.power = 'admin';
	return this;
};

User.prototype.toString = function() {
	return JSON.stringify(this, ['user_id', 'display_name', 'email', 'power']);
}

module.exports = User;