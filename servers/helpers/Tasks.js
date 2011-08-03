function SerialTask(req, res) {
	this.tasks = [];
	this.index = 0;
	this.req = req;
	this.res = res;
	
	this.addTask = function(task) {
		this.tasks[this.tasks.length] = {
			task: task
		};
	};
	
	this.start = function(cb) {
		this._next(cb);
	};
	
	var t = this;
	this._next = function(cb) {
		if (t.index >= t.tasks.length) {
			cb();
			return;
		}
		
		console.log('running task ' + t.index);
		var task = t.tasks[t.index];
		t.index++;
		var n = t._next;
		task.task(t.req, t.res, function(error) {
			n(cb);
		});
	};
};

exports.serialTask = SerialTask;