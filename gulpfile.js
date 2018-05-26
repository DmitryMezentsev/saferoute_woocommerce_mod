'use strict';



const path = require('path');



const buildDir  = './build';
const moduleDir = './src';
const docDir    = './doc';

const moduleFiles = path.join(moduleDir, '**/*.*');
const docFiles    = path.join(docDir, '*.*');

const moduleFileName  = 'ddelivery_woocommerce_install.zip';
const archiveFileName = 'ddelivery_woocommerce.zip';



const $ = {
    gulp:     require('gulp'),
    zip:      require('gulp-zip'),
    clean:    require('gulp-clean'),
    watch:    require('gulp-watch'),
    sequence: require('run-sequence'),
};



// Удаление старых файлов сборки
$.gulp.task('_clean', () =>
    $.gulp.src(path.join(buildDir, '*.*'), { read: false })
        .pipe($.clean())
);

// Сборка модуля
$.gulp.task('_buildModule', () =>
    $.gulp.src(moduleFiles, { base: moduleDir })
        .pipe($.zip(moduleFileName))
        .pipe($.gulp.dest(buildDir))
);

// Создание архива для выгрузки на сайт
$.gulp.task('_createArchive', () => {
    let src = [
        path.join(buildDir, moduleFileName),
        docFiles,
        // exclude
        path.join(`!${docDir}`, '~*'),
        path.join(`!${docDir}`, '*.tmp'),
    ];

    return $.gulp.src(src)
        .pipe($.zip(archiveFileName))
        .pipe($.gulp.dest(buildDir))
});

// Мониторинг изменений и пересборка
$.gulp.task('_watch', () =>
    $.watch([moduleFiles, docFiles], () =>
        $.sequence('_clean', '_buildModule', '_createArchive')
    )
);



$.gulp.task('default', () =>
    $.sequence('_clean', '_buildModule', '_createArchive', '_watch')
);