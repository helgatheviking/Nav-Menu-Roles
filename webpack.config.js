const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require("path");

module.exports = {
    ...defaultConfig,
    entry: {
        customize: "./js/src/customize-controls.js",
        roles: "./js/src/nav-menu-roles.js",
    },
    output: {
        filename: "[name].js",
        path: path.resolve("./js/dist"),
    },
};