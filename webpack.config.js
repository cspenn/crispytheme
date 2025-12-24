/**
 * WordPress webpack configuration for CrispyTheme.
 *
 * @package CrispyTheme
 */

const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );

module.exports = {
	...defaultConfig,
	entry: {
		'dark-mode-toggle': path.resolve( __dirname, 'assets/js/src/dark-mode-toggle.js' ),
		'admin-preview': path.resolve( __dirname, 'assets/js/src/admin-preview.js' ),
		'prism-clipboard': path.resolve( __dirname, 'assets/js/src/prism-clipboard.js' ),
	},
	output: {
		...defaultConfig.output,
		path: path.resolve( __dirname, 'build' ),
	},
};
