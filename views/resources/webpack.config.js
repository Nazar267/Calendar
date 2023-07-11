let Encore = require('@symfony/webpack-encore');
let HtmlWebpackPlugin = require('html-webpack-plugin');

Encore
    .setOutputPath('public/build/')
    .setPublicPath('/build')
    .enableTypeScriptLoader()
    .enableSassLoader()
    .enableSourceMaps(!Encore.isProduction())
    // .enableVersioning()
    .disableSingleRuntimeChunk()
    .addStyleEntry('style', './src/app.scss')
    .addEntry('app', './src/app.ts');



module.exports = Encore.getWebpackConfig();