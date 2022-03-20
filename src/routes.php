<?php

use Illuminate\Filesystem\Filesystem;

$this->app->router->get('install.php', function(Filesystem $files) {
    $path = app()->basePath('app/Bitrix24');
    $files->ensureDirectoryExists($path);

    require_once (__DIR__ . '/crest.php');
    //if (! $files->exists($path."/crest.php")){
    //    $files->copy(__DIR__ . '/crest.php', $path."/crest.php");
    //}
    //if (! $files->exists($path."/install.php")){
    //    $files->copy(__DIR__ . '/install.php', $path."/install.php");
    //}
    //if (! $files->exists($path."/settings.php")){
    //    $files->copy(__DIR__ . '/settings.php', $path."/settings.php");
    //}

    try {
        $files->requireOnce(__DIR__ . "/install.php");
    } catch (Exception $e) {
        echo '<pre>Ошибка скрипта инсталляции локального Приложения Битрикс24' . PHP_EOL
        . ' Инструкция - https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=99&LESSON_ID=857' . PHP_EOL
        . '</pre>';
        return $e->getMessage();
    }

    return;
});

$this->app->router->get('checkserver.php', function() {
    require_once (__DIR__ . '/crest.php');
    CRest::checkServer();
});
