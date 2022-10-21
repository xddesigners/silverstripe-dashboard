const mix = require('laravel-mix');

mix
    .sass('client/src/styles/app.scss', 'client/dist/styles')
    .js('client/src/js/app.js', 'client/dist/js')