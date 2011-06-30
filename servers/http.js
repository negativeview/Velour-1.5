// Deal with HTTP.
var http = require('http');
var timeout = require('connect-timeout');
var express = require('express');
var minj = require('minj');
var less = require('less');
var user_stuff = require('./user-stuff');
var project_stuff = require('./project-stuff');

var mysql = require('db-mysql');
var db = new mysql.Database(
	{
		hostname: 'localhost',
		user:     'root',
		password: '',
		database: 'argtech'
	}
);
db.on('error', function(error) {
	console.log('ERROR: ' + error);
});
db.on('ready', function(server) {
	console.log('Connected to ' + server.hostname + ' (' + server.versin + ')');
	setupExpress();
});
db.connect();

var app;
function setupExpress() {
	app = express.createServer();
	app.use(minj.middleware({ src: __dirname + '/../htdocs'}));
	app.use(express.static(__dirname + '/../htdocs'));
	app.use(express.bodyParser());
	app.use(express.cookieParser());
	app.use(express.session({ secret: "foobar" }));
	//app.use(express.logger());
	app.use(timeout());
	
	user_stuff.setupApp(app, db);
	project_stuff.setupApp(app, db);

	// Use ejs rendering for our templates.
	app.set('view engine', 'ejs');
	app.set('view options', { cache: true});
	
	app.get('/task/new', user_stuff.mustBeLoggedIn, function(req, res) {
		res.render(
			'task-new',
			{
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
				title: 'Dashboard',
				bodyclass: '',
				bodyid: 'dashboard',
				flash: req.flash(),
				authUser: req.session.authenticatedAs,
			}
		);
	});
	
	app.get('/credits', function(req, res) {
		res.render(
			'credits',
			{
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