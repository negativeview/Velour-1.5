// Deal with HTTP.
var http = require('http');
var timeout = require('connect-timeout');
var express = require('express');
var minj = require('minj');
var less = require('less');
var user_stuff = require('./user-stuff');
var project_stuff = require('./project-stuff');
var generic_pool = require('generic-pool');
var mysql = require('db-mysql');
var quips = require('./quip-stuff');

var db;

new mysql.Database({
	hostname: 'localhost',
	user: 'root',
	password: '',
	database: 'argtech'
}).connect(function(err, server) {
	db = this;
	setupExpress();
});


var db_pool = generic_pool.Pool({
	name: 'mysql',
	create: function(callback) {
		new mysql.Database({
			hostname: 'localhost',
			user: 'root',
			password: '',
			database: 'argtech'
		}).connect(function(err, server) {
			callback(err, this);
		})
	},
	destroy: function(db) {
		db.disconnect();
	},
	max: 100,
	idleTimeoutMillis: 30000,
	log: false
});

var app;
function setupExpress() {
	app = express.createServer();
	app.use(minj.middleware({ src: __dirname + '/../htdocs'}));
	app.use(express.static(__dirname + '/../htdocs'));
	app.use(express.bodyParser());
	app.use(express.cookieParser());
	app.use(express.session({ secret: "foobar" }));
	app.use(timeout());
	
	app.use(function(req, res, next) {
		req.db = db;
		next();
	});
	
	user_stuff.setupApp(app);
	project_stuff.setupApp(app);

	// Use ejs rendering for our templates.
	app.set('view engine', 'ejs');
	app.set('view options', { cache: true});
	
	app.get('/task/new', user_stuff.mustBeLoggedIn, function(req, res) {
		res.render(
			'task-new',
			{
				quip: quips.getQuip(),
				title: 'New Task',
				bodyclass: '',
				bodyid: 'task-new',
				flash: req.flash(),
				authUser: req.session.authenticatedAs,
			}
		);
	});
	
	app.get('/', function(req, res) {
		res.render(
			'dashboard',
			{
				quip: quips.getQuip(),
				title: 'Dashboard',
				bodyclass: '',
				bodyid: 'dashboard',
				flash: req.flash(),
				authUser: req.session.authenticatedAs,
				watches: [
					{'url': '/project/29/', image: '/project/29/thumb.png'},
					{'url': '/project/33/', image: '/project/33/thumb.png'}
				],
			}
		);
	});
	
	app.get('/credits', function(req, res) {
		res.render(
			'credits',
			{
				quip: quips.getQuip(),
				title: 'Credits',
				bodyclass: '',
				bodyid: 'credits',
				flash: req.flash(),
				authUser: req.session.authenticatedAs,
			}
		);
	});
	
	app.listen(8081);
}