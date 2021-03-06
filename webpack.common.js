/**
 * Shared builds.
 */

const { CleanWebpackPlugin } = require( "clean-webpack-plugin" );
const path                   = require( "path" );

module.exports = {
	entry: {
		customize: "./src/js/nav-menu-roles-customize-controls.js",
		roles: "./src/js/nav-menu-roles.js",
	},
	devtool: "source-map",
	output: {
		filename: "[name].js",
		path: path.resolve( "./dist/js" ),
	},
	module: {
		rules: [
			{
				test: /\.js$/,
				exclude: ["/node_modules"],
				loader: "babel-loader",
				options: {
					presets: ["@babel/preset-env"],
					plugins: ["@babel/proposal-class-properties"],
				},
		},
			{
				test: /\.s(a|c)ss$/,
				use: [
					{
						loader: "style-loader",
				},
					{
						loader: "babel-loader",
						options: {
							presets: ["@babel/preset-env"],
						},
				},
					{
						loader: "css-loader",
				},
					{
						loader: "sass-loader",
				},
				],
		},
		],
	},
	plugins: [new CleanWebpackPlugin()],
};
