const fs = require('fs')
const path = require('path')
const autoprefixer = require('autoprefixer')
const postcss = require('postcss')
const sass = require('node-sass')
const watch = require('watch')
const webpack = require('webpack')

const outputDirectory = '../public/application/themes/KARFU'
const cssPath = path.join(outputDirectory, 'css/bundle.css')
const jsPath = path.join(outputDirectory, 'js/bundle.js')


const webpackConfig = {
	entry: './src/main.js',
	output: {
		filename: jsPath
	},
	module: {
		rules: [
			{
				test: /\.js$/,
				exclude: /node_modules/,
				use: {
					loader: 'babel-loader',
					options: {
						presets: ['env']
					}
				}
			}
		]
	},
 	plugins: [
 		new webpack.optimize.UglifyJsPlugin({ sourceMap: false })
 	],
 	devtool: 'source-map'
}

const prefixer = postcss([
	autoprefixer({
		BrowserList: ['last 2 versions']
		})
	])

watch.watchTree('./src', function () {
	process.stdout.write('rendering js...\n')

	webpack(webpackConfig, function (err, stats) {
		if (err) {
			process.stdout.write(`js error. ${err}\n`)
			return
		}
		process.stdout.write('js done.\n')
	})
})

watch.watchTree('./scss', function () {
	process.stdout.write('rendering scss...\n')

	sass.render({
		file: './scss/main.scss',
		outputStyle: 'compressed',
		sourceMap: true
	}, 
	function (error, result) {
	if (error) {
		process.stderr.write(`\nCSS error: ${error.message}\n`)
		process.stderr.write(`${error.file}\n`)
		process.stderr.write(`${error.line}\n\n`)

	return
	}

	prefixer
	.process(result.css)
	.then(function (result) {
		fs.writeFileSync(cssPath, result.css)
		process.stdout.write('scss done....\n')
		})
		.catch(function (error) {
			process.stderr.write(`${error}\n`)
		})
	})
})
