module.exports = function(grunt) {

	require('load-grunt-tasks')(grunt);

	grunt.initConfig({

		pkg: grunt.file.readJSON('package.json'),

		watch: {
			options: {
				atBegin: true
			}
		},

		php: {
			dist: {
				options: {
					port: 5000
				}
			}
		}

	});

	grunt.registerTask('default', ['php']);
};