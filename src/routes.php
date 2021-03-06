<?php

use Illuminate\Filesystem\Filesystem;
use Bitrix24api\CRest;
use Illuminate\Support\Facades\Log;

$this->app->router->post('install.php', function(Filesystem $files) {
    $path = app()->basePath('app/Bitrix24');
    $files->ensureDirectoryExists($path);
    Log::info('call reinstall');
    
    try {
        $files->requireOnce(__DIR__ . "/bin/install.php");
    } catch (Exception $e) {
        echo '<pre>Ошибка скрипта инсталляции локального Приложения Битрикс24' . PHP_EOL
        . ' Инструкция - https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=99&LESSON_ID=857' . PHP_EOL
        . '</pre>';
        return $e->getMessage();
    }

    return;
});

$this->app->router->get('checkserver.php', function() {
    CRest::checkServer();
});
