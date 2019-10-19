const path = require("path");
const HtmlWebpackPlugin = require("html-webpack-plugin");
const MonacoWebpackPlugin = require("monaco-editor-webpack-plugin");

module.exports = {
	mode: "development",
	resolve: {
		extensions: [".js", ".jsx"],
	},
	entry: "./index.js",
	output: {
		path: path.resolve(__dirname, "dist"),
		filename: "app.js",
	},
	module: {
		rules: [
			{
				test: /\.(js|jsx|css)?$/,
				loader: "babel-loader",
				use: ["style-loader", "css-loader"],
			},
		],
	},
	plugins: [
		new MonacoWebpackPlugin({
			// available options are documented at https://github.com/Microsoft/monaco-editor-webpack-plugin#options
			languages: ["json"],
		}),
		new HtmlWebpackPlugin({
			template: "./public/index.php",
		}),
	],
	devServer: {
		historyApiFallback: true,
	},
	externals: {
		// global app config object
		config: JSON.stringify({
			apiUrl: "http://localhost:4000",
		}),
	},
};