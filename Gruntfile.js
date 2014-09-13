'use strict';
var LIVERELOAD_PORT = 35729;
var lrSnippet = require('connect-livereload')({port: LIVERELOAD_PORT});
var mountFolder = function (connect, dir) {
	return connect.static(require('path').resolve(dir));
};

// # Globbing
// for performance reasons we're only matching one level down:
// 'test/spec/{,*/}*.js'
// use this if you want to match all subfolders:
// 'test/spec/**/*.js'

module.exports = function (grunt) {
	// show elapsed time at the end
	require('time-grunt')(grunt);
	// load all grunt tasks
	require('load-grunt-tasks')(grunt);

	// configurable paths
	var yeomanConfig = {
		app: 'app',
		dist: 'dist'
	};

	grunt.initConfig({
		yeoman: yeomanConfig,
		watch: {
			options: {
				nospawn: true,
				livereload: { liveCSS: true }
			},
			livereload: {
				options: {
					livereload: true
				},
				files: [
					'<%= yeoman.app %>/*.html',
					'<%= yeoman.app %>/*.php',
					'<%= yeoman.app %>/php.src/*.php',
					'<%= yeoman.app %>/elements/{,*/}*.html',
					'{.tmp,<%= yeoman.app %>}/elements/{,*/}*.css',
					'{.tmp,<%= yeoman.app %>}/styles/{,*/}*.css',
					'{.tmp,<%= yeoman.app %>}/scripts/{,*/}*.js',
					'<%= yeoman.app %>/images/{,*/}*.{png,jpg,jpeg,gif,webp}'
				]
			},
			js: {
				files: ['<%= yeoman.app %>/scripts/{,*/}*.js'],
				tasks: ['jshint']
			},
			styles: {
				files: [
					'<%= yeoman.app %>/styles/{,*/}*.css',
					'<%= yeoman.app %>/elements/{,*/}*.css'
				],
				tasks: ['copy:styles', 'autoprefixer:server']
			},
			sass: {
				files: [
					'<%= yeoman.app %>/styles/{,*/}*.{scss,sass}',
					'<%= yeoman.app %>/elements/{,*/}*.{scss,sass}'
				],
				tasks: ['sass:server', 'autoprefixer:server']
			}
		},
		// Compiles Sass to CSS and generates necessary files if requested
		sass: {
			options: {
				sourcemap: true,
				loadPath: '<%= yeoman.app %>/bower_components'
			},
			dist: {
				options: {
					style: 'compressed'
				},
				files: [
					{
						expand: true,
						cwd: '<%= yeoman.app %>',
						src: ['styles/{,*/}*.{scss,sass}', 'elements/{,*/}*.{scss,sass}'],
						dest: '<%= yeoman.dist %>',
						ext: '.css'
					}
				]
			},
			server: {
				files: [
					{
						expand: true,
						cwd: '<%= yeoman.app %>',
						src: ['styles/{,*/}*.{scss,sass}', 'elements/{,*/}*.{scss,sass}'],
						dest: '.tmp',
						ext: '.css'
					}
				]
			}
		},
		autoprefixer: {
			options: {
				browsers: ['last 2 versions']
			},
			server: {
				files: [
					{
						expand: true,
						cwd: '.tmp',
						src: '**/*.css',
						dest: '.tmp'
					}
				]
			},
			dist: {
				files: [
					{
						expand: true,
						cwd: '<%= yeoman.dist %>',
						src: ['**/*.css', '!bower_components/**/*.css'],
						dest: '<%= yeoman.dist %>'
					}
				]
			}
		},
		connect: {
			options: {
				port: 9000,
				// change this to '0.0.0.0' to access the server from outside
				hostname: 'localhost'
			},
			livereload: {
				options: {
					middleware: function (connect) {
						return [
							lrSnippet,
							mountFolder(connect, '.tmp'),
							mountFolder(connect, yeomanConfig.app)
						];
					}
				}
			},
			test: {
				options: {
					middleware: function (connect) {
						return [
							mountFolder(connect, '.tmp'),
							mountFolder(connect, 'test'),
							mountFolder(connect, yeomanConfig.app)
						];
					}
				}
			},
			dist: {
				options: {
					middleware: function (connect) {
						return [
							mountFolder(connect, yeomanConfig.dist)
						];
					}
				}
			}
		},
		open: {
			server: {
				path: 'http://localhost:<%= connect.options.port %>'
			}
		},
		clean: {
			dist: ['.tmp', '<%= yeoman.dist %>/*'],
			server: '.tmp'
		},
		jshint: {
			options: {
				jshintrc: '.jshintrc',
				reporter: require('jshint-stylish')
			},
			all: [
				'<%= yeoman.app %>/scripts/{,*/}*.js',
				'!<%= yeoman.app %>/scripts/vendor/*',
				'test/spec/{,*/}*.js'
			]
		},
		mocha: {
			all: {
				options: {
					run: true,
					urls: ['http://localhost:<%= connect.options.port %>/index.html']
				}
			}
		},
		useminPrepare: {
			html: '<%= yeoman.app %>/index.html',
			options: {
				dest: '<%= yeoman.dist %>'
			}
		},
		usemin: {
			html: ['<%= yeoman.dist %>/{,*/}*.html'],
			css: ['<%= yeoman.dist %>/styles/{,*/}*.css'],
			options: {
				dirs: ['<%= yeoman.dist %>'],
				blockReplacements: {
					vulcanized: function (block) {
						return '<link rel="import" href="' + block.dest + '">';
					}
				}
			}
		},
		vulcanize: {
			default: {
				options: {
					strip: true,
					inline: true
				},
				files: {
					'<%= yeoman.dist %>/elements/elements.vulcanized.html': [
						'<%= yeoman.dist %>/elements/elements.html'
					]
				}
			}
		},
		imagemin: {
			dist: {
				files: [
					{
						expand: true,
						cwd: '<%= yeoman.app %>/images',
						src: '{,*/}*.{png,jpg,jpeg}',
						dest: '<%= yeoman.dist %>/images'
					}
				]
			}
		},
		minifyHtml: {
			options: {
				quotes: true,
				empty: true
			},
			app: {
				files: [
					{
						expand: true,
						cwd: '<%= yeoman.dist %>',
						src: '*.html',
						dest: '<%= yeoman.dist %>'
					}
				]
			}
		},
		copy: {
			main: {
				files: [
					{expand: true, cwd: '<%= yeoman.app %>', src: ['index.php' , 'php.src/**', 'vendor/**', 'config/**' ], dest: '<%= yeoman.dist %>'},
					{expand: true, cwd: 'vendor/history.js/scripts/bundled/html4+html5/', src: ['native.history.js' ], dest: '<%= yeoman.dist %>/scripts'}
				]
			},
			dist: {
				files: [
					{
						expand: true,
						dot: true,
						cwd: '<%= yeoman.app %>',
						dest: '<%= yeoman.dist %>',
						src: [
							'*.{ico,txt}',
							'.htaccess',
							'*.html',
							'elements/**',
							'!elements/**/*.scss',
							'images/{,*/}*.{webp,gif}',
							'scripts/vendor/**',
							'bower_components/**'
						]
					}
				]
			},
			styles: {
				files: [
					{
						expand: true,
						cwd: '<%= yeoman.app %>',
						dest: '.tmp',
						src: ['{styles,elements}/{,*/}*.css']
					}
				]
			},
			styles4test: {
				files: [
					{
						expand: true,
						cwd: '<%= yeoman.app %>/../.tmp/styles',
						dest: '<%= yeoman.app %>/styles/',
						src: ['*.css']
					}
				]
			}
		},
		// See this tutorial if you'd like to run PageSpeed
		// against localhost: http://www.jamescryer.com/2014/06/12/grunt-pagespeed-and-ngrok-locally-testing/
		pagespeed: {
			options: {
				// By default, we use the PageSpeed Insights
				// free (no API key) tier. You can use a Google
				// Developer API key if you have one. See
				// http://goo.gl/RkN0vE for info
				nokey: true
			},
			// Update `url` below to the public URL for your site
			mobile: {
				options: {
					url: "https://developers.google.com/web/fundamentals/",
					locale: "en_GB",
					strategy: "mobile",
					threshold: 80
				}
			}
		},
		php: {
			options: {
				port: 5000,
				keepalive: true,
				open: true,
				hostname: 'localhost'
			},
			dist: {
				options: {
					base: '<%= yeoman.dist %>',
				}
			},
			watch: {
				options: {
					port: 5000,
					livereload: 5000,
					base: '<%= yeoman.app %>',
					hostname: 'localhost',
					keepalive: true,
					open: true,
					atBegin: true
				}

			}
		}
	});

	grunt.registerTask('server', function (target) {
		grunt.log.warn('The `server` task has been deprecated. Use `grunt serve` to start a server.');
		grunt.task.run(['serve:' + target]);
	});

	grunt.registerTask('serve', function (target) {
		if (target === 'dist') {
			return grunt.task.run(['build', 'open', 'connect:dist:keepalive']);
		}

		grunt.task.run([
			'clean:server',
			'sass:server',
			'copy:styles',
			'autoprefixer:server',
			'connect:livereload',
			'open',
			'watch'
		]);
	});

	grunt.registerTask('phpwatch', ['php:watch', 'watch']);

	grunt.registerTask('default', [
		'clean:server',
		'sass:server',
		'copy:styles',
		'autoprefixer:server',
		'copy:styles4test',
		'phpwatch',
	]);

	grunt.registerTask('dist', [
		'jshint',
		'build',
		'php:dist',
	]);

	grunt.registerTask('build', [
		'clean:dist',
		'sass',
		'copy',
		'copy:main',
		'useminPrepare',
		'imagemin',
		'concat',
		'autoprefixer',
		'uglify',
		'vulcanize',
		'usemin',
		'minifyHtml'
	]);

	grunt.registerTask('static', [
		'connect:dist',
		'open',
		'watch'
	]);
};
