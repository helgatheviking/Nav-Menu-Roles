module.exports = function(grunt) {

  require('load-grunt-tasks')(grunt);
	
  // Project configuration.
  grunt.initConfig({
	pkg: grunt.file.readJSON('package.json'),
	creds: grunt.file.readJSON('gitcreds.json'),
	uglify: {
		options: {
			compress: {
				global_defs: {
					"EO_SCRIPT_DEBUG": false
				},
				dead_code: true
				},
			banner: '/*! <%= pkg.title %> <%= pkg.version %> <%= grunt.template.today("yyyy-mm-dd HH:MM") %> */\n'
		},
		build: {
			files: [{
				expand: true,	// Enable dynamic expansion.
				src: ['js/*.js', '!js/*.min.js'], // Actual pattern(s) to match.
				ext: '.min.js',   // Dest filepaths will have this extension.
			}]
		}
	},
	jshint: {
		options: {
			reporter: require('jshint-stylish'),
			globals: {
				"EO_SCRIPT_DEBUG": false,
			},
			 '-W099': true, //Mixed spaces and tabs
			 '-W083': true,//TODO Fix functions within loop
			 '-W082': true, //Todo Function declarations should not be placed in blocks
			 '-W020': true, //Read only - error when assigning EO_SCRIPT_DEBUG a value.
		},
		all: [ 'js/*.js', '!js/*.min.js' ]
  	},

	compress: {
		//Compress build/<%= pkg.name %>
		main: {
			options: {
				mode: 'zip',
				archive: './deploy/<%= pkg.name %>.zip'
			},
			expand: true,
			cwd: 'deploy/<%= pkg.name %>/',
			src: ['**/*'],
			dest: '<%= pkg.name %>/'
		},
		version: {
			options: {
				mode: 'zip',
				archive: './deploy/<%= pkg.name %>-<%= pkg.version %>.zip'
			},
			expand: true,
			cwd: 'deploy/<%= pkg.name %>/',
			src: ['**/*'],
			dest: '<%= pkg.name %>/'
		}	
	},

	clean: {
		//Clean up build folder
		main: ['deploy/<%= pkg.name %>']
	},

	copy: {
		// Copy the plugin to a versioned release directory
		main: {
			src:  [
				'**',
				'!node_modules/**',
				'!deploy/**',
				'!.git/**',
				'!Gruntfile.js',
				'!package.json',
				'!gitcreds.json',
				'!.gitignore',
				'!.gitmodules',
				'!*~',
				'!*.sublime-workspace',
				'!*.sublime-project',
				'!*.transifexrc',
				'!deploy.sh',
				'!languages/.tx',
				'!languages/tx.exe'
			],
			dest: 'deploy/<%= pkg.name %>/'
		},
	
	},

	wp_readme_to_markdown: {
		convert:{
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
				src: ['*.php', '**/*.php', '!node_modules/**', '!deploy/**']
			}
		}
	},

	// Generate .pot file
	makepot: {
		target: {
			options: {
				domainPath: '/languages', // Where to save the POT file.
				exclude: ['deploy'], // List of files or directories to ignore.
				mainFile: '<%= pkg.name %>.php', // Main project file.
				potFilename: '<%= pkg.name %>.pot', // Name of the POT file.
				type: 'wp-plugin' // Type of project (wp-plugin or wp-theme).
			}
		}
	},

	// get transifex translations
	transifex: {
		"nav-menu-roles": {
			options: {
				targetDir: "languages",		// download specified resources / langs only
		//		resources: ["localizable_enstrings"],
				languages: ["es"],
				filename : "<%= pkg.name %>-_lang_.json",
		//		templateFn: function(strings) { return "bacon"; }
			}
		}
	},

	shell: {
		options: {
			stdout: true,
			stderr: true
		},
		txpull: {
			command: [
				'cd languages',
				'tx.exe pull -a -f',
			].join( '&&' )
		}
	},

	// turn po files into mo files
	po2mo: {
		files: {
			src: 'languages/*.po',
			expand: true,
		},
	},

	// make sure the repo is ok
	checkrepo: {
		deploy: {
			tag: {
				eq: '<%= pkg.version %>',	// Check if highest repo tag is equal to pkg.version
			},
			tagged: true, // Check if last repo commit (HEAD) is not tagged
			clean: true,   // Check if the repo working directory is clean
		}
	},

	// automatically update the docs, scripts on change	
	watch: {
		readme: {
			files: ['readme.txt'],
			tasks: ['wp_readme_to_markdown'],
			options: {
			spawn: false,
			},
		  },
		scripts: {
			files: ['js/*.js'],
			tasks: ['newer:jshint','newer:uglify'],
			options: {
			spawn: false,
			},
		  },
	},

	// bump version numbers and push tag to github
	release: {
		options: {
			github: { 
				repo: '<%= pkg.author %>/<%= pkg.name %>', //put your user/repo here
				usernameVar: '<%= creds.username %>', //ENVIRONMENT VARIABLE that contains Github username 
				passwordVar: '<%= creds.password %>' //ENVIRONMENT VARIABLE that contains Github password
			}
		}
	},

	// deploy to wordpress.org
	wp_deploy: {
		deploy:{
			options: {
				svn_user: '<%= pkg.author %>',
				plugin_slug: '<%= pkg.name %>',
				build_dir: 'deploy/<%= pkg.name %>/'
			},
		}
	}


});

grunt.registerTask( 'docs', ['wp_readme_to_markdown']);

grunt.registerTask( 'test', [ 'phpunit', 'jshint' ] );

grunt.registerTask( 'build', [ 'test', 'newer:uglify', 'pot', 'newer:po2mo', 'wp_readme_to_markdown', 'clean', 'copy' ] );

// bump version numbers 
// grunt release		1.4.1 -> 1.4.2
// grunt release:minor	1.4.1 -> 1.5.0
// grint release:major	1.4.1 -> 2.0.0

grunt.registerTask( 'deploy', [ 'checkbranch:master', 'checkrepo:deploy', 'build', 'wp_deploy',  'compress' ] );


grunt.registerTask( 'd', [ 'transifex' ] );

grunt.registerTask( 'tx', [ 'shell:txpull' ] );

};
