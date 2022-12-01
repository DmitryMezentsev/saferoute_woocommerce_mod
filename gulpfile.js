'use strict';

const { join } = require('path');
const merge = require('merge-stream');

const $ = {
  gulp:  require('gulp'),
  zip:   require('gulp-zip'),
  clean: require('gulp-clean'),
  watch: require('gulp-watch'),
};


const buildDir     = './build';
const srcDir       = './src';
const assetsDir    = './assets';
const svnTrunkDir  = './wp_svn_repo/trunk';
const svnAssetsDir = './wp_svn_repo/assets';

const moduleFileName  = 'saferoute_woocommerce_install.zip';

const moduleFiles = join(srcDir, '**/*.*');


// Удаление старых файлов сборки
$.gulp.task('_cleanBuild', () =>
  $.gulp.src(join(buildDir, '*.*'), { read: false })
    .pipe($.clean())
);

// Сборка модуля
$.gulp.task('_buildModule', () =>
  $.gulp.src(moduleFiles, { base: srcDir })
    .pipe($.zip(moduleFileName))
    .pipe($.gulp.dest(buildDir))
);

// Удаление старых файлов из SVN-репозитория
$.gulp.task('_cleanSvn', () => {
  const removeSrc = $.gulp.src(join(svnTrunkDir, '**/*.*'), { read: false })
    .pipe($.clean());
  const removeAssets = $.gulp.src(join(svnAssetsDir, '**/*.*'), { read: false })
    .pipe($.clean());

  return merge(removeSrc, removeAssets);
});

// Копирование файлов для SVN-репозитория
$.gulp.task('_copyToSvn', () => {
  const copySrc = $.gulp.src(join(srcDir, 'saferoute-woocommerce/**/*.*'), { base: join(srcDir, 'saferoute-woocommerce') })
    .pipe($.gulp.dest(svnTrunkDir));
  const copyAssets = $.gulp.src(assetsDir + '*/*', { base: assetsDir })
    .pipe($.gulp.dest(svnAssetsDir));

  return merge(copySrc, copyAssets);
});

// Мониторинг изменений и пересборка
$.gulp.task('_watch', () =>
  $.watch(
    [moduleFiles],
    $.gulp.series('_cleanBuild', '_buildModule', '_cleanSvn', '_copyToSvn'),
  )
);


$.gulp.task('default', $.gulp.series('_cleanBuild', '_buildModule', '_cleanSvn', '_copyToSvn', '_watch'));