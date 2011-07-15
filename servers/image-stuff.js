var im = require('imagemagick');

exports.makeImageOfSize = function(baseName, size, cb) {
	im.convert([baseName + '.png', '-resize', size + 'x' + size + '^', '-gravity', 'center', '-extent', size + 'x' + size, baseName + '-' + size + '.png'],
		function (err, metadata) {
			if (err) {
				cb(err);
			} else {
				cb(null, metadata);
			}
		}
	);
};