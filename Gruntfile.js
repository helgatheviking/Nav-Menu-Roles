module.exports = function(grunt) {

    // load most all grunt tasks
    require("load-grunt-tasks")(grunt);

    // Project configuration.
    grunt.initConfig({
        pkg: grunt.file.readJSON("package.json"),
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
                reporter: require("jshint-stylish")
            },
            all: ["js/*.js", "!js/*.min.js"]
        },

        // Remove the build directory files
        clean: {
            main: ["build/**"]
        },

        // Copy the plugin into the build directory
        copy: {
            main: {
                src: [
                    "**",
                    "!node_modules/**",
                    "!build/**",
                    "!svn/**",
                    "!wp-assets/**",
                    "!.git/**",
                    "!**.md",
                    "!Gruntfile.js",
                    "!package.json",
                    "!package-lock.json",
                    "!gitcreds.json",
                    "!.gitcreds",
                    "!.transifexrc",
                    "!.gitignore",
                    "!.gitmodules",
                    "!sftp-config.json",
                    "!**.sublime-workspace",
                    "!**.sublime-project",
                    "!deploy.sh",
                    "!**/*~"
                ],
                dest: "build/"
            }
        },

        // Generate git readme from readme.txt
        wp_readme_to_markdown: {
            convert: {
                files: {
                    "readme.md": "readme.txt"
                }
            }
        },

        // # Internationalization

        // Add text domain
        addtextdomain: {
            textdomain: "<%= pkg.name %>",
            target: {
                files: {
                    src: ["*.php", "**/*.php", "!node_modules/**", "!build/**"]
                }
            }
        },

        // Generate .pot file
        makepot: {
            target: {
                options: {
                    domainPath: "/languages", // Where to save the POT file.
                    exclude: ["build/.*", "svn/.*"], // List of files or directories to ignore.
                    mainFile: "<%= pkg.name %>.php", // Main project file.
                    potFilename: "<%= pkg.name %>.pot", // Name of the POT file.
                    type: "wp-plugin" // Type of project (wp-plugin or wp-theme).
                }
            }
        },

        // bump version numbers
        replace: {
          version: {
            src: [
                "readme.txt",
                "readme.md",
                "<%= pkg.name %>.php"
                ],
            overwrite: true,
            replacements: [
                    {
                        from: /\*\*Stable tag:\*\* .*/,
                        to: "**Stable tag:** <%= pkg.version %>  "
                    },
                    {
                        from: /Stable tag: .*/,
                        to: "Stable tag: <%= pkg.version %>"
                    },
                    { 
                        from: /Version:.*/,
                        to: "Version: <%= pkg.version %>"
                    },
                    { 
                        from: /public \$version .*/,
                        to: "public $version = '<%= pkg.version %>';"
                    },
                    {
                        from: /CONST VERSION = \'.*/,
                        to: "CONST VERSION = '<%= pkg.version %>';"
                    }]
          }
        },

        // # Deploy to WordPress
        wp_deploy: {
            deploy: {
                options: {
                    svn_user: "<%= pkg.author %>",
                    plugin_slug: "<%= pkg.name %>",
                    build_dir: "build/",
                    assets_dir: "wp-assets/",
                    tmp_dir: "C:/Temp/"
                }
            }
        }
    });

    // makepot and addtextdomain tasks
    grunt.loadNpmTasks("grunt-wp-i18n");

    // Default task(s).
    grunt.registerTask("default", ["jshint", "uglify"]);

    grunt.registerTask("docs", ["wp_readme_to_markdown"]);

    grunt.registerTask("test", ["jshint", "addtextdomain"]);

    grunt.registerTask("build", ["test", "replace", "newer:uglify", "clean", "copy"]);

    grunt.registerTask("deploy", ["build", "wp_deploy", "clean"]);

};