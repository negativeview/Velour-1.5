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
	       ['creator', 'parent', 'project', 'title', 'created', 'description', 'buzz', 'buzz_date', 'specific_id'],
	       [original.creator, original.parent, original.project, original.title, {value: 'NOW()', escape: false}, original.description, original.buzz, original.buzz_date, original.specific_id]
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