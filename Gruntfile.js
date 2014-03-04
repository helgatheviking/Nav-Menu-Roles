module.exports = function(grunt) {

	require('load-grunt-tasks')(grunt);

	// Project configuration.
	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),
		uglify: {
			options: {
				compress: {
					global_defs: {
						"EO_SCRIPT_DEBUG": false
					},
					dead_code: true
				},
				banner: '/*! <%= pkg.name %> <%= pkg.version %> */\n'
			},
			build: {
				files: [{
					expand: true, // Enable dynamic expansion.
					src: ['js/*.js', '!js/*.min.js'], // Actual pattern(s) to match.
					ext: '.min.js', // Dest filepaths will have this extension.
				}, ]
			}
		},
		jshint: {
			options: {
				reporter: require('jshint-stylish'),
				globals: {
					"EO_SCRIPT_DEBUG": false,
				},
				'-W020': true, //Read only - error when assigning EO_SCRIPT_DEBUG a value.
			},
			all: ['js/*.js', '!js/*.min.js']
		},

		// Remove the build directory files
		clean: {
			main: ['build/**']
		},

		// Copy the plugin into the build directory
		copy: {
			main: {
				src: [
					'**',
					'!node_modules/**',
					'!build/**',
					'!.git/**',
					'!Gruntfile.js',
					'!package.json',
					'!.gitignore',
					'!.gitmodules',
					'!**/*.sublime-workspace',
					'!**/*.sublime-project',
					'!deploy.sh',
					'!**/*~'
				],
				dest: 'build/'
			}
		},

		// Generate git readme from readme.txt
		wp_readme_to_markdown: {
			convert: {
				files: {
					'readme.md': 'readme.txt'
				},
			},
		},

		// # Internationalization 

		// Add text domain
		addtextdomain: {
			textdomain: '<%= pkg.name %>',
			target: {
				files: {
					src: ['*.php', '**/*.php', '!node_modules/**', '!build/**']
				}
			}
		},

		// Generate .pot file
		makepot: {
			target: {
				options: {
					domainPath: '/languages', // Where to save the POT file.
					exclude: ['build/**'], // List of files or directories to ignore.
					mainFile: '<%= pkg.name %>.php', // Main project file.
					potFilename: '<%= pkg.name %>.pot', // Name of the POT file.
					type: 'wp-plugin' // Type of project (wp-plugin or wp-theme).
				}
			}
		},

		// Create .mo files for existing .po
		po2mo: {
			files: {
				src: 'languages/*.po',
				expand: true,
			},
		},

		// # Deploy to WordPress

		checkrepo: {
			deploy: {
				tag: {
					eq: '<%= pkg.version %>', // Check if highest repo tag is equal to pkg.version
				},
				tagged: true, // Check if last repo commit (HEAD) is not tagged
				clean: true, // Check if the repo working directory is clean
			}
		},

		checkwpversion: {
			plugin_equals_stable: {
				version1: 'plugin',
				version2: 'readme',
				compare: '==',
			},
			plugin_equals_package: {
				version1: 'plugin',
				version2: '<%= pkg.version %>',
				compare: '==',
			},
		},

		wp_deploy: {
			deploy: {
				options: {
					svn_user: '<%= pkg.author %>',
					plugin_slug: '<%= pkg.name %>',
					build_dir: 'build/'
				},
			}
		},

	});

	// makepot and addtextdomain tasks
	grunt.loadNpmTasks('grunt-wp-i18n');

	// Default task(s).
	grunt.registerTask('default', ['jshint', 'uglify']);

	grunt.registerTask('test', ['jshint', 'addtextdomain']);

  grunt.registerTask('readme', ['wp_readme_to_markdown']);

	grunt.registerTask('build', ['test', 'newer:uglify', 'makepot', 'newer:po2mo', 'wp_readme_to_markdown', 'clean', 'copy']);

	grunt.registerTask('deploy', ['checkwpversion', 'checkbranch:master', 'checkrepo:deploy', 'build', 'wp_deploy', 'clean']);

};