const mix = require('laravel-mix');
require('laravel-mix-merge-manifest');

mix.setPublicPath('../../public').mergeManifest();

mix.js(__dirname + '/Resources/assets/js/app.js', 'js/orderschedule.js')
    .sass( __dirname + '/Resources/assets/sass/app.scss', 'css/orderschedule.css');

if (mix.inProduction()) {
    mix.version();
}