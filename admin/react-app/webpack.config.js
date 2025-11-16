const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const path = require('path');

module.exports = {
    ...defaultConfig,
    output: {
        ...defaultConfig.output,
        path: path.resolve(__dirname, '../js'),
        filename: 'admin-script.js',
    },
    plugins: [
        ...defaultConfig.plugins.map(plugin => {
            if (plugin.constructor.name === 'MiniCssExtractPlugin') {
                return new plugin.constructor({
                    filename: '../css/admin-style.css',
                });
            }
            if (plugin.constructor.name === 'DependencyExtractionWebpackPlugin') {
                return new plugin.constructor({
                    outputFilename: 'admin-script.asset.php',
                });
            }
            return plugin;
        }),
    ],
};
