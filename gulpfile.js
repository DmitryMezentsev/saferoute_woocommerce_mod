'use strict';



const path = require('path');



const buildPath  = './ddelivery_woocommerce.zip';
const moduleDir = './ddelivery_woocommerce';

const moduleFiles = path.join(moduleDir, '**/*.*');



const $ = {
    gulp:  require('gulp'),
    zip:   require('gulp-zip'),
    watch: require('gulp-watch'),
};



// Сборка модуля
$.gulp.task('_buildModule', () =>
    $.gulp.src(moduleFiles, { base: '.' })
        .pipe($.zip(buildPath))
        .pipe($.gulp.dest('.'))
);

// Мониторинг изменений и пересборка
$.gulp.task('_watch', () =>
    $.watch(moduleFiles, () => $.gulp.start('_buildModule'))
);



$.gulp.task('default', () =>
    $.gulp.start('_buildModule', '_watch')
);