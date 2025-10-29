const path = require('path');
const DependencyExtractionWebpackPlugin = require('@wordpress/dependency-extraction-webpack-plugin');
const CopyPlugin = require('copy-webpack-plugin');
const TerserPlugin = require('terser-webpack-plugin');

module.exports = {
	entry: {
		'theme-image/index': path.resolve(
			__dirname,
			'blocks/src/theme-image',
			'index.js'
		),
	},
	output: {
		path: path.resolve(__dirname, 'blocks/build'),
		filename: '[name].js',
	},
	optimization: {
		minimize: true,
		minimizer: [
			new TerserPlugin({
				extractComments: false,
			}),
		],
	},
	module: {
		rules: [
			{
				test: /\.js$/,
				exclude: /node_modules/,
				use: {
					loader: 'babel-loader',
					options: {
						presets: ['@babel/preset-env', '@babel/preset-react'],
						plugins: ['@babel/plugin-transform-runtime'],
					},
				},
			},
		],
	},
	plugins: [
		new DependencyExtractionWebpackPlugin(),
		new CopyPlugin({
			patterns: [
				{
					from: 'blocks/src/**/block.json',
					to({ context, absoluteFilename }) {
						const srcDir = path.resolve(context, 'src');
						const relativeToSrc = path.relative(
							srcDir,
							absoluteFilename
						);
						const dir = path.dirname(relativeToSrc);
						return path.resolve(
							context,
							'build',
							dir,
							'[name][ext]'
						);
					},
				},
			],
		}),
	],

	// External dependencies that should not be bundled.
	externals: {
		react: 'React',
		'react-dom': 'ReactDOM',
	},
};
