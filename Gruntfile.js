/**
 * Build automation scripts.
 *
 * @package Nav Menu Roles
 */

module.exports = function (grunt) {
	// load most all grunt tasks
	require( "load-grunt-tasks" )( grunt );

	// Project configuration.
	grunt.initConfig(
		{
			pkg: grunt.file.readJSON( "package.json" ),
			jshint: {
				options: {
					reporter: require( "jshint-stylish" ),
					jshintrc: true,
				},
				all: ["src/js/*.js", "!src/js/*.min.js"],
			},

			// Remove the build directory files
			clean: {
				main: ["build/**"],
			},

			// Copy the plugin into the build directory
			copy: {
				main: {
					src: [
					"**",
					"!.afdesign",
					"!.gitcreds",
					"!.gitcreds.json",
					"!.gitignore",
					"!.gitmodules",
					"!.transifexrc",
					"!**/*~",
					"!**.md",
					"!**.sublime-project",
					"!**.sublime-workspace",
					"!build/**",
					"!composer.json",
					"!composer.lock",
					"!deploy/**",
					"!deploy.sh",
					"!Gruntfile.js",
					"!gitcreds.json",
					"!inc/block-editor/**", // Remove once you begin block compat.
					"!node_modules/**",
					"!package-lock.json",
					"!package.json",
					"!phpcs.xml",
					"!sftp-config.json",
					"!src/**",
					"!svn/**",
					"!vendor/**",
					"!webpack.config.js",
					"!wp-assets/**",
					],
					dest: "build/",
				},
			},

			// Make a zipfile.
			compress: {
				main: {
					options: {
						mode: 'zip',
						archive: 'deploy/<%= pkg.name %>-<%= pkg.version %>.zip'
					},
					expand: true,
					cwd: 'build/',
					src: ['**/*'],
					dest: '/<%= pkg.name %>'
				}
			},

			// Generate git readme from readme.txt
			wp_readme_to_markdown: {
				convert: {
					files: {
						"readme.md": "readme.txt",
					},
				},
			},

			// # Internationalization

			// Add text domain
			addtextdomain: {
				textdomain: "<%= pkg.name %>",
				target: {
					files: {
						src: ["*.php", "**/*.php", "!node_modules/**", "!build/**"],
					},
				},
			},

			// Generate .pot file
			makepot: {
				target: {
					options: {
						domainPath: "/languages", // Where to save the POT file.
						exclude: ["build/.*", "svn/.*"], // List of files or directories to ignore.
						mainFile: "<%= pkg.name %>.php", // Main project file.
						potFilename: "<%= pkg.name %>.pot", // Name of the POT file.
						type: "wp-plugin", // Type of project (wp-plugin or wp-theme).
					},
				},
			},

			// bump version numbers
			replace: {
				version: {
					src: ["readme.txt", "readme.md", "<%= pkg.name %>.php", "inc/class-<%= pkg.name %>.php"],
					overwrite: true,
					replacements: [
					{
						from: /\*\*Stable tag:\*\* .*/,
						to: "**Stable tag:** <%= pkg.version %>  ",
					},
					{
						from: /Stable tag: .*/,
						to: "Stable tag: <%= pkg.version %>",
					},
					{
						from: /Version:.*/,
						to: "Version: <%= pkg.version %>",
					},
					{
						from: /public \$version .*/,
						to: "public $version = '<%= pkg.version %>';",
					},
					{
						from: /CONST VERSION = \'.*/,
						to: "CONST VERSION = '<%= pkg.version %>';",
					},
					],
				},
			},
		}
	);

	// makepot and addtextdomain tasks
	grunt.loadNpmTasks( "grunt-wp-i18n" );

	// Default task(s).
	grunt.registerTask( "default", ["jshint"] );

	grunt.registerTask( "docs", ["wp_readme_to_markdown"] );

	grunt.registerTask( "test", ["jshint", "addtextdomain"] );

	grunt.registerTask(
		"build",
		[
		"test",
		"replace",
		]
	);

	grunt.registerTask(
		'zip',
		[
		'clean',
		'copy',
		'compress'
		]
	);

};
