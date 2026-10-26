const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path          = require( 'path' );

module.exports = {
	...defaultConfig,
	entry: {
		'blocks/settings': '/src/blocks/index.js',
		'customizer/controls': '/src/customizer/controls.js',
		'nav-menu-roles': '/src/nav-menu-roles.js',
	},
	output: {
		path: path.resolve( __dirname, 'assets/js' ),
		filename: '[name].js',
	},
};
