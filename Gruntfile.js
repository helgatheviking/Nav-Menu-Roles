module.exports = function(grunt) {

	// load most all grunt tasks
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
					'!svn/**',
					'!wp-assets/**',
					'!.git/**',
					'!**.md',
					'!Gruntfile.js',
					'!package.json',
          			'!gitcreds.json',
          			'!.gitcreds',
          			'!.transifexrc',
					'!.gitignore',
					'!.gitmodules',
					'!sftp-config.json',
					'!**.sublime-workspace',
					'!**.sublime-project',
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
					exclude: ['build/.*', 'svn/.*'], // List of files or directories to ignore.
					mainFile: '<%= pkg.name %>.php', // Main project file.
					potFilename: '<%= pkg.name %>.pot', // Name of the POT file.
					type: 'wp-plugin' // Type of project (wp-plugin or wp-theme).
				}
			}
		},

		transifex: {
			'nav-menu-roles': {
				options: {
					targetDir: "languages",     
					mode: "file",
					filename : "_resource_-_lang_.po",
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

		// # Check some git repo settings
		checkrepo: {
			deploy: {
				tag: {
					eq: '<%= pkg.version %>', // Check if highest repo tag is equal to pkg.version
				},
				tagged: false, // Check if last repo commit (HEAD) is not tagged
				clean: true, // Check if the repo working directory is clean
			}
		},

    	// # Check the plugin, package & readme files have same version 
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
	
		// bump version numbers
		replace: {
			Version: {
				src: [
					'readme.txt',
					'readme.md',
					'<%= pkg.name %>.php'
				],
				overwrite: true,
				replacements: [
					{
						from: /Stable tag:.*$/m,
						to: "Stable tag: <%= pkg.version %>"
					},
					{ 
						from: /Version:.*$/m,
						to: "Version: <%= pkg.version %>"
					},
					{ 
						from: /public \$version = \'.*.'/m,
						to: "public $version = '<%= pkg.version %>'"
					}
				]
			}
		},
		// # Deploy to WordPress

		wp_deploy: {
			deploy: {
				options: {
					svn_user: '<%= pkg.author %>',
					plugin_slug: '<%= pkg.name %>',
					build_dir: 'build/',
					assets_dir: 'wp-assets/'
				},
			}
		},

	});

	// makepot and addtextdomain tasks
	grunt.loadNpmTasks('grunt-wp-i18n');

	// Default task(s).
	grunt.registerTask('default', ['jshint', 'uglify']);

	grunt.registerTask('docs', ['wp_readme_to_markdown']);

	grunt.registerTask('test', ['jshint', 'addtextdomain']);

	grunt.registerTask('build', ['test', 'replace', 'newer:uglify', 'makepot', 'newer:po2mo', 'wp_readme_to_markdown', 'clean', 'copy']);


// bump version numbers 
// grunt release		1.4.1 -> 1.4.2
// grunt release:minor	1.4.1 -> 1.5.0
// grint release:major	1.4.1 -> 2.0.0

	grunt.registerTask('deploy', ['checkbranch:master', 'checkrepo:deploy', 'build', 'release', 'wp_deploy', 'clean']);

};