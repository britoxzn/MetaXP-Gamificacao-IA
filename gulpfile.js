"use strict";

// Plugins de build e automação
const autoprefixer = require("gulp-autoprefixer");
const browsersync = require("browser-sync").create();
const cleanCSS = require("gulp-clean-css");
const del = require("del");
const gulp = require("gulp");
const header = require("gulp-header");
const merge = require("merge-stream");
const plumber = require("gulp-plumber");
const rename = require("gulp-rename");
const sass = require("gulp-sass")(require("sass"));
const uglify = require("gulp-uglify");

// Informações da plataforma MetaXP
const pkg = require('./package.json');
const banner = [
  '/*!\n',
  ' * MetaXP - Plataforma de Metas & IA para Produtividade\n',
  ' * v<%= pkg.version %>\n',
  ' * (c) ' + new Date().getFullYear() + ' - <%= pkg.author %>\n',
  ' * Gamificação e Inteligência Artificial para otimizar suas metas pessoais\n',
  ' */\n',
  '\n'
].join('');

// Sincronização com o navegador
function browserSync(done) {
  browsersync.init({
    server: { baseDir: "./" },
    port: 4000 // Port 4000 para evitar conflito com outras ferramentas
  });
  done();
}

function browserSyncReload(done) {
  browsersync.reload();
  done();
}

// Limpa dependências (como pacotes desnecessários ou arquivos temporários)
function clean() {
  return del(["./vendor/"]);
}

// Copia dependências externas para a pasta /vendor (frameworks e plugins úteis para a plataforma)
function modules() {
  const bootstrap = gulp.src('./node_modules/bootstrap/dist/**/*')
    .pipe(gulp.dest('./vendor/bootstrap'));

  const chartJS = gulp.src('./node_modules/chart.js/dist/*.js')
    .pipe(gulp.dest('./vendor/chartjs'));

  const datatables = gulp.src([
    './node_modules/datatables.net/js/*.js',
    './node_modules/datatables.net-bs4/js/*.js',
    './node_modules/datatables.net-bs4/css/*.css'
  ]).pipe(gulp.dest('./vendor/datatables'));

  const fontAwesome = gulp.src('./node_modules/@fortawesome/**/*')
    .pipe(gulp.dest('./vendor/fontawesome'));

  const easing = gulp.src('./node_modules/jquery.easing/*.js')
    .pipe(gulp.dest('./vendor/jquery-easing'));

  const jquery = gulp.src([
    './node_modules/jquery/dist/*',
    '!./node_modules/jquery/dist/core.js'
  ]).pipe(gulp.dest('./vendor/jquery'));

  return merge(bootstrap, chartJS, datatables, fontAwesome, easing, jquery);
}

// Compila SCSS com suporte a autoprefix e minificação (para estilos modernos e otimizados para IA e produtividade)
function css() {
  return gulp.src("./scss/**/*.scss")
    .pipe(plumber())
    .pipe(sass({ outputStyle: "expanded", includePaths: "./node_modules" }))
    .on("error", sass.logError)
    .pipe(autoprefixer({ cascade: false }))
    .pipe(header(banner, { pkg: pkg }))
    .pipe(gulp.dest("./css"))
    .pipe(rename({ suffix: ".min" }))
    .pipe(cleanCSS())
    .pipe(gulp.dest("./css"))
    .pipe(browsersync.stream());
}

// Minifica scripts JS, adiciona banner e salva versão minificada
// Esses scripts podem incluir funcionalidades de gamificação e otimização da produtividade pessoal
function js() {
  return gulp.src(['./js/*.js', '!./js/*.min.js'])
    .pipe(plumber())
    .pipe(uglify())
    .pipe(header(banner, { pkg: pkg }))
    .pipe(rename({ suffix: '.min' }))
    .pipe(gulp.dest('./js'))
    .pipe(browsersync.stream());
}

// Observadores (detectar mudanças em arquivos e recarregar ou recompilar automaticamente)
function watchFiles() {
  gulp.watch("./scss/**/*", css);
  gulp.watch(["./js/**/*", "!./js/**/*.min.js"], js);
  gulp.watch("./**/*.html", browserSyncReload);
}

// Tarefas compostas para facilitar o fluxo de desenvolvimento
const vendor = gulp.series(clean, modules);
const build = gulp.series(vendor, gulp.parallel(css, js));
const watch = gulp.series(build, gulp.parallel(watchFiles, browserSync));

// Exportações de tarefas
exports.clean = clean;
exports.modules = modules;
exports.css = css;
exports.js = js;
exports.build = build;
exports.watch = watch;
exports.default = build;
