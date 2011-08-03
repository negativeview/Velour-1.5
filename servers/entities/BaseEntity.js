var fs = require('fs');

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
	
	this.getCreator = function() {
		return this._creator;
	};
	
	this.getFullSizedIcon = function(cb) {
		var readStream;
		
		if (this.image_id) {
			var q = db.query().select('obj_string.value')
			                      .from('obj_static')
			                      .join({table: 'base_object', conditions: 'base_object.id = obj_static.current'})
			                      .join({table: 'obj_string', conditions: 'base_object.title = obj_string.id'})
			                      .where('obj_static.id = ?', [this.image_id]);
			
			q.execute(function(err, res) {
				if (err) return cb(err);
			});
			return;
		} else {
			readStream = fs.createReadStream('../htdocs/images/anonymous.png');
		}
		
		cb(null, readStream);
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

exports.BaseEntity = BaseEntity;