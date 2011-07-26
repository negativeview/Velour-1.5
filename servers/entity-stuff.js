function BaseEntity(id) {
	this._id = id;
	this._creator = null;
	this._parent = null;
	this._project = null;
	this._title = null;
	this._titleFull = null;
	this._created = null;
	this._description = null;
	this._descriptionFull = null;
	this._buzz = null;
	this._buzzDate = null;
	this._imageId = null;
	this._specificId = null;
	
	this.getId = function() {
		return this._id;
	};
	
	this.initFromRow = function(row) {
		this.setId(row.static_id);
		this.setCreator(row.creator);
		this.setParent(row.parent);
		this.setProject(row.project);
		this.setTitle(row.title);
		this.setCreated(row.created);
		this.setDescription(row.description);
		this.setBuzz(row.buzz);
		this.setBuzzDate(row.buzz_date);
		this.setImageId(row.image_id);
		this.setSpecificId(row.specific_id);
		this.setType(row.type);
		
		if (row.description_full)
			this.setFullDescription(row.description_full);
		
		if (row.title_full)
			this.setFullTitle(row.title_full);
	};
	
	this.getBuzz = function() {
		return this._buzz;
	};
	
	this.getTitle = function(cb) {
		if (this._titleFull) {
			if (cb == null)
				return this._titleFull;
			
			cb(null, this._titleFull);
		}
		
		var q = this.db.query();
		q.select('value')
		 .from('obj_string')
		 .where('id = ?', [this._description])
		 .execute(function(error, result) {
		     if (error) {
		         cb(error);
		         return;
		     }
		     
		     this._titleFull = result[0];
		     cb(null, this._titleFull);
		 });		
	};
	
	this.getCreated = function() {
		return this._created;
	};
	
	this.getDescription = function(cb) {
		if (this._descriptionFull) {
			if (cb == null)
				return this._descriptionFull;
				
			cb(null, this._descriptionFull);
			return;
		}
		
		var q = this.db.query();
		q.select('value')
		 .from('obj_text')
		 .where('id = ?', [this._description])
		 .execute(function(error, result) {
		     if (error) {
		         cb(error);
		         return;
		     }
		     
		     this._descriptionFull = result[0];
		     cb(null, this._descriptionFull);
		 });
	};
	
	this.setType = function(type) {
		this._type = type;
	};
	
	this.setSpecificId = function(specificId) {
		throw new Error('Specific ID not implemented in base class.');
	};
	
	this.setImageId = function(imageId) {
		this._imageId = imageId;
	};
	
	this.setBuzz = function(buzz) {
		this._buzz = buzz;
	};
	
	this.setBuzzDate = function(buzzDate) {
		this._buzzDate = buzzDate;
	};
	
	this.setDescription = function(description) {
		this._description = description;
	};
	
	this.setFullDescription = function(description) {
		this._descriptionFull = description;
	};
	
	this.setFullTitle = function(title) {
		this._titleFull = title;
	};
	
	this.setCreated = function(created) {
		this._created = created;
	};
	
	this.setProject = function(project) {
		this._project = project;
	};
	
	this.setTitle = function(title) {
		this._title = title;
	};
	
	this.setCreator = function(creator) {
		this._creator = creator;
	};
	
	this.setParent = function(parent) {
		this._parent = parent;
	};
	
	this.setId = function(id) {
		if (this._id) {
			throw new Error('Cannot re-define id after it is stored.');
		}
		
		if (!id) {
			throw new Error('Cannot set id to a false value.');
		}
		
		this._id = id;
		
		return this;
	};
	
	this.save = function() {
		if (!this._changed) {
			return;
		}
		
		throw new Error('Saving not implemented yet.');
	};
}

var entity_types = {};
exports.registerEntityType = function(type_id, c) {
	entity_types[type_id] = c;
};

var db;

exports.setup = function(d) {
	db = d;

	var user_entity = require('./user-entity');
	user_entity.registerType(db);

	var project_entity = require('./project-entity');
	project_entity.registerType(db);
};

exports.BaseEntity = BaseEntity;

exports.getObject = function(id, cb) {
	db.query()
	  .select('obj_static.id AS static_id, base_object.*, obj_static.type, obj_text.value AS description_full, obj_string.value AS title_full')
	  .from('obj_static')
	  .join({table: 'base_object', conditions: 'obj_static.current = base_object.id'})
	  .join({table: 'obj_text', conditions: 'base_object.description = obj_text.id'})
	  .join({table: 'obj_string', conditions: 'base_object.description = obj_string.id'})
	  .where('obj_static.id = ?', [id])
	  .execute(function(error, result) {
	      if (error) {
	          cb(error);
	          return;
	      }
	      
	      if (result.length == 0) {
	          cb(null, null);
	      }
	      console.log(result);
	      
	      result = result[0];
	      
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