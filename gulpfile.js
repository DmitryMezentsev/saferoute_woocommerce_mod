'use strict';

const { join } = require('path');

const $ = {
  gulp:  require('gulp'),
  zip:   require('gulp-zip'),
  clean: require('gulp-clean'),
  watch: require('gulp-watch'),
};


const buildDir  = './build';
const moduleDir = './src';
const svnDir    = './wp_svn_repo/trunk';

const moduleFileName  = 'saferoute_woocommerce_install.zip';

const moduleFiles = join(moduleDir, '**/*.*');


// Удаление старых файлов сборки
$.gulp.task('_cleanBuild', () =>
  $.gulp.src(join(buildDir, '*.*'), { read: false })
    .pipe($.clean())
);

// Сборка модуля
$.gulp.task('_buildModule', () =>
  $.gulp.src(moduleFiles, { base: moduleDir })
    .pipe($.zip(moduleFileName))
    .pipe($.gulp.dest(buildDir))
);

// Мониторинг изменений и пересборка
$.gulp.task('_watch', () =>
  $.watch(
    [moduleFiles],
    $.gulp.series('_cleanBuild', '_buildModule', '_cleanSvn', '_copyToSvn')
  )
);

// Удаление старых файлов из SVN-репозитория
$.gulp.task('_cleanSvn', () =>
  $.gulp.src(join(svnDir, '**/*.*'), { read: false })
    .pipe($.clean())
);

// Копирование файлов для SVN-репозитория
$.gulp.task('_copyToSvn', () =>
  $.gulp.src(join(moduleDir, 'saferoute-woocommerce/**/*.*'))
    .pipe($.gulp.dest(svnDir))
);


$.gulp.task('default', $.gulp.series('_cleanBuild', '_buildModule', '_cleanSvn', '_copyToSvn', '_watch'));