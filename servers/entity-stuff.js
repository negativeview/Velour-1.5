var string_handling = require('./string_handling');

var BaseEntity = require('./entities/BaseEntity.js').BaseEntity;

var entity_types = {};
exports.registerEntityType = function(type_id, c) {
	entity_types[type_id] = c;
};

var db;

exports.setup = function(d) {
	db = d;

	var user_entity = require('./entities/User');
	user_entity.registerType(db);

	var project_entity = require('./entities/Project');
	project_entity.registerType(db);
};

exports.BaseEntity = BaseEntity;

exports.newObject = function(data, cb) {
	var q = db.query();
	
	if (data.title_raw) {
		string_handling.getset_string(data.title_raw, db, function(error, id) {
			if (error)
				return cb(error);
				
			data.title = id;
			delete data.title_raw;
			exports.newObject(data, cb);
		});
		return;
	}
	
	if (data.description_raw) {
		string_handling.getset_text(data.description_raw, db, function(error, id) {
			if (error)
				return cb(error);
			
			data.description = id;
			delete data.description_raw;
			exports.newObject(data, cb);
		});
		return;
	}
	
	var fields = [
		'created',
		'creator',
		'buzz',
		'buzz_date',
	];
	
	var optional_fields = [
		'parent',
		'project',
		'image_id',
		'specific_id',
		'title',
		'description'
	];
	
	var values = [
		{value: 'NOW()', escape: false},
		data.creator,
		0,
		{value: 'NOW()', escape: false}
	];
	
	for (var i = 0; i < optional_fields.length; i++) {
		if (data[optional_fields[i]]) {
			fields[fields.length] = optional_fields[i];
			values[values.length] = data[optional_fields[i]];
		}
	}
		
	q.insert('base_object', fields, values);
	
	console.log(q.sql());
	q.execute(function(error, result) {
		if (error)
			return cb(error);
		
		q = db.query();
		
		fields = [
			'type',
			'current',
			'views',
			'created'
		];
		
		values = [
			data.type,
			result.id,
			0,
			{value: 'NOW()', escape: false}
		];
		
		q.insert('obj_static', fields, values);
		q.execute(function (error, result) {
			if (error)
				return cb(error);
			
			cb(null, result);
		});
	});
};

exports.getObject = function(id, cb) {
	var q = db.query()
	          .select('obj_static.id AS static_id, base_object.*, obj_static.type, obj_text.value AS description_full, obj_string.value AS title_full')
              .from('obj_static')
	          .join({table: 'base_object', conditions: 'obj_static.current = base_object.id'})
	          .join({table: 'obj_text', conditions: 'base_object.description = obj_text.id'})
	          .join({table: 'obj_string', conditions: 'base_object.title = obj_string.id'})
	          .where('obj_static.id = ?', [id]);
	q.execute(function(error, result) {
	      if (error) {
			  console.log(q.sql() + ': ' + id);
	          cb(error);
	          return;
	      }
	      
	      if (result.length == 0) {
	          cb(null, null);
	      }
	      console.log(result);
	      
	      result = result[0];
	      if (!result) {
	          cb('No entity found for ' + id);
	          return;
	      }
	      
	      var c = entity_types[result.type];
	      if (!c) {
	      	throw new Error('No handler for object type ' + result.type);
	      }
	      
	      var ob = new c();
	      ob.initFromRow(result);
	      
	      cb(null, ob);
	  });
};

function addText(db, text, cb) {
	db.query().insert('obj_text', ['value'], [text]).execute(
		function(error, result) {
			if (error) {
				cb(error);
				return;
			}
			
			cb(null, result.id);
		}
	);
}

function baseObjectFromStaticId(db, static_id, cb) {
	db.query().
	   select('base_object.*').
	   from('obj_static').
	   join({table: 'base_object', conditions: 'obj_static.current = base_object.id'}).
	   where('obj_static.id = ?', [static_id]).
	   execute(function(error, result) {
	       if (error) {
	           cb(error);
	           return;
	       }
	       
	       cb(null, result);
	   });
}

function cloneBaseObject(db, original, cb) {
	var q = db.query().
	   insert(
	       'base_object',
	       ['creator', 'parent', 'project', 'title', 'created', 'description', 'buzz', 'buzz_date', 'specific_id', 'image_id'],
	       [original.creator, original.parent, original.project, original.title, {value: 'NOW()', escape: false}, original.description, original.buzz, original.buzz_date, original.specific_id, original.image_id]
	   );
	
	q.execute(
		function(error, result) {
			if (error) {
				cb(error);
				return;
			}
			
			cb(null, result.id);
		}
	);
}

function setNewCurrent(db, obj_id, current, cb) {
	db.query().
	   update('obj_static').
	   set({current: current}).
	   where('id = ?', [obj_id]).
	   execute(function(error, res) {
	       if (error) {
	           cb(error);
	           return;
	       }
	       
	       cb(null, res);
	   });
}

exports.getHistory = function(db, obj_id, cb) {
	db.query().
	   select('*').
	   from('base_object').
	   where('parent = ?', [obj_id]).
	   order({created: true}).
	   execute(
	       function(error, result) {
	           if (error) {
	               cb(error);
	               return;
	           }
	           
	           var last_i = null;
	           var current_row = null;
	           var last_row = null;
	           for (var i in result) {
	               last_row = current_row;
	               current_row = result[i];
	               
	               if (last_row) {
	                   last_row['differences'] = [];
	                   
	                   if (last_row['title'] != current_row['title']) {
	                       last_row['differences'][last_row['differences'].length] = 'Title';
	                   }
	                   
	                   if (last_row['description'] != current_row['description']) {
	                       last_row['differences'][last_row['differences'].length] = 'Description';
	                   }
	               }
	           }
	           
	           cb(null, result);
	       }
	   );
};

exports.updateImageId = function(db, obj_id, image_id, cb) {
	/**
	 * Gets the entire current base object from a static id.
	 * Res is an object representing the current db row.
	 */
	baseObjectFromStaticId(db, obj_id, function(error, res) {
		if (error) throw error;
		
		var base_object = res[0];
		/**
		 * Cheat and modify the result in-place so we can just use a clone call.
		 */
		base_object.image_id = image_id;
		
		/**
		 * Clones a base object into a new row. Res is the new row id.
		 */
		cloneBaseObject(db, base_object, function(error, res) {
			if (error) throw error;
			
			/**
			 * Sets this new row as the "current" value for our static object id.
			 * Res is the db res object, which is mostly useless in this case.
			 */
			setNewCurrent(db, obj_id, res, function(error, res) {
				if (error) throw error;
				
				cb(null, res);
			});
		});
	});
};

/**
 * Updates the description field for any generic object.
 *
 * @param db The database handle to use.
 * @param obj_id The static id for this object.
 * @param description The new description to use.
 * @param cb The callback to use when done.
 * @return void
 */
exports.updateDescription = function(db, obj_id, description, cb) {
	/**
	 * Adds text to our long-text storage table.
	 * Res in this case is the ID of the resultant row.
	 */
	addText(db, description, function(error, res) {
		if (error) {
			cb(error);
			return;
		}
		
		var obj_text_id = res;
		
		/**
		 * Gets the entire current base object from a static id.
		 * Res is an object representing the current db row.
		 */
		baseObjectFromStaticId(db, obj_id, function(error, res) {
			if (error) {
				cb(error);
				return;
			}
			
			var base_object = res[0];
			/**
			 * Cheat and modify the result in-place so we can just use a clone call.
			 */
			base_object.description = obj_text_id;
			
			/**
			 * Clones a base object into a new row. Res is the new row id.
			 */
			cloneBaseObject(db, base_object, function(error, res) {
			    if (error) {
			    	console.log(error);
			        cb(error);
			        return;
			    }
			    
			    /**
			     * Sets this new row as the "current" value for our static object id.
			     * Res is the db res object, which is mostly useless in this case.
			     */
			    setNewCurrent(db, obj_id, res, function(error, res) {
			    	if (error) {
			    		cb(error);
			    		return;
			    	}
			    	
			    	cb(null, res);
			    });
			});
		});
	});
};