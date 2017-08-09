module.exports = function( grunt ) {

	grunt.initConfig({

		concat: {
			core: {
				src: [
					'assets/js/peepso-pre.js',
					'assets/js/observer.js',
					'assets/js/npm-expanded.js',
					'assets/js/util.js'
				],
				dest: 'assets/js/peepso-core.js'
			}
		},

		sass: {
			dist: {
				options: {
					style: 'compressed',
					sourcemap: 'none'
				},
				files: {
					'templates/css/template.css': 'assets/scss/styles.scss',
					'templates/css/template-rtl.css': 'assets/scss/styles-rtl.scss',
					'templates/css/template-dark.css': 'assets/scss/styles-dark.scss',
					'templates/css/template-dark-rtl.css': 'assets/scss/styles-dark-rtl.scss'
				}
			}
		},

		uglify: {
			core: {
				options: {
					report: 'none',
					sourceMap: true
				},
				files: [{
					src: [ 'assets/js/peepso-core.js' ],
					expand: true,
					ext: '.min.js',
					extDot: 'last'
				}],
			},
			other: {
				options: {
					report: 'none',
					sourceMap: false
				},
				files: [{
					src: [
						'assets/js/activitystream.js',
						'assets/js/postbox-legacy.js',
						'assets/js/postbox.js'
					],
					expand: true,
					ext: '.min.js',
					extDot: 'last'
				}],
			}
			// dist: {
			// 	options: {
			// 		report: 'none',
			// 		sourceMap: false
			// 	},
			// 	files: [{
			// 		src: [
			// 			'assets/js/*.js',
			// 			'!assets/js/respond.js',
			// 			'!assets/js/*-min.js',
			// 			'!assets/js/*.min.js',
			// 			'!assets/js/npm.js'
			// 		],
			// 		expand: true,
			// 		ext: '.min.js',
			// 		extDot: 'last'
			// 	}],
			// }
		}

	});

	grunt.loadNpmTasks( 'grunt-contrib-concat' );
	grunt.loadNpmTasks( 'grunt-contrib-sass' );
	grunt.loadNpmTasks( 'grunt-contrib-uglify' );

	grunt.registerTask( 'default', [ 'concat', 'uglify' ]);
	grunt.registerTask( 'css', [ 'sass' ]);

};
