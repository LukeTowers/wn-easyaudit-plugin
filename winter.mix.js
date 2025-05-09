const mix = require('laravel-mix');
mix.setPublicPath(__dirname);

mix.js(
    'formwidgets/changeviewer/assets/src/js/changeviewer.js',
    'formwidgets/changeviewer/assets/dist/js/changeviewer.js'
);

mix.copy(
    'node_modules/jsondiffpatch/lib/formatters/styles/html.css',
    'formwidgets/changeviewer/assets/dist/css/changeviewer.css'
)
