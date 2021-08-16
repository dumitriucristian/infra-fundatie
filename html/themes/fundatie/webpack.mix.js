let mix = require('laravel-mix');

mix.setPublicPath("./assets/compiled");

mix.js('./assets/distro/js/main.js', "/js");
mix.css('./assets/distro/css/main.css', "/css");
mix.sass('./assets/distro/sass/custom.scss', "/css")
    

//watch files
mix.browserSync({
    proxy: 'localhost',
    host: 'localhost',
    notify: false,
    files: [
        "./assets/distro/js/*.js",
        "./assets/distro/css/*.css",
        "./assets/distro/sass/*.scss",
        "./assets/distro/sass/sections/*.scss",
        "./layouts/*.htm",
        "./pages/*.htm",
        "./partials/*.htm"
    ]
});

//minimize files and combine
mix.combine( './assets/compiled/css/*.css','custom.css');
mix.combine( './assets/compiled/js/*.js','main.js',true);