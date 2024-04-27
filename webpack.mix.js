const mix = require('laravel-mix');
const path = require('path');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */

mix.options({
    terser: {
        terserOptions: {
            compress: {
                drop_console: true,
            },
        },
    },
}).setPublicPath('public')

// Configure Mix to use Vue 3
mix.js('resources/js/quasar.js', 'public').vue({ version: 3 });

// Compile CSS with Tailwind
mix.postCss('resources/css/quasar.css', 'public', [
    require('tailwindcss'),
]);

// If you're using any other CSS or JavaScript processing, add it here
// For example, compiling Sass:
// mix.sass('resources/sass/app.scss', 'public/css');

// Copy any images or other assets from resources to public (optional)
// mix.copyDirectory('resources/images', 'public/images');

// Versioning / Cache Busting in production
if (mix.inProduction()) {
    mix.version();
}

// Custom Webpack configuration (optional)
mix.webpackConfig({
    // Custom Webpack configuration goes here
    // Example: setting aliases
    resolve: {
        alias: {
            '@': path.resolve('resources/js'),
            ziggy: path.resolve('vendor/tightenco/ziggy/dist/vue'),
        },
    },
});
