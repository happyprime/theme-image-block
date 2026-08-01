module.exports = {
	root: true,
	extends: [
		'plugin:import/recommended',
		'plugin:jsdoc/recommended',
		'plugin:jsx-a11y/recommended',
		'plugin:prettier/recommended',
		'plugin:react/recommended',
		'plugin:react-hooks/recommended',
	],
	overrides: [
		{
			files: ['**/*.{js,cjs,mjs}'],
		},
	],
	env: {
		browser: true,
	},
	ignorePatterns: [
		'build/*.*',
		'build/**/*.*',
		'**/build/*.*',
		'**/build/**/*.*',
		'vendor/**/*.*',
	],
	parserOptions: {
		ecmaVersion: "latest",
	},
	env: {
		"es2024": true,
	},
	rules: {
		'no-console': ['warn', { allow: ['error'] }],
		'import/no-unresolved': [2, { ignore: ['^@wordpress/'] }],
		'react/react-in-jsx-scope': 'off',
		'react/prop-types': 'off',
		'jsdoc/no-undefined-types': 'off',
		'jsdoc/tag-lines': [
			'error',
			'any',
			{
				startLines: 1,
			},
		],
	},
	settings: {
		react: {
			version: '18',
		},
	}
};
