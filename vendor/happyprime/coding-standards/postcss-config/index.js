module.exports = {
	plugins: {
		'postcss-import': {
			plugins: [ require( 'stylelint' ) ],
		},
		'postcss-preset-env': {},
	},
};
