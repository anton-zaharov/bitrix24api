<?php
use Illuminate\Filesystem\Filesystem;

$this->app->router->get('install.php', function(Filesystem $files) {
        $path = app()->basePath('app/Bitrix24');
        $files->ensureDirectoryExists($path);
        
        if (! $files->exists($path."/crest.php")){
            $files->copy(__DIR__ . '/crest.php', $path."/crest.php");
        }
        if (! $files->exists($path."/install.php")){
            $files->copy(__DIR__ . '/install.php', $path."/install.php");
        }
        if (! $files->exists($path."/settings.php")){
            $files->copy(__DIR__ . '/settings.php', $path."/settings.php");
        }
        if ($files->exists($path."/install.php")){
            try {
                $files->requireOnce($path."/install.php");
            } catch (Exception $e){
                echo '<pre>Ошибка скрипта инсталляции локального Приложения Битрикс24' . PHP_EOL
                . ' Инструкция - https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=99&LESSON_ID=857' . PHP_EOL
                . '</pre>';
                return $e->getMessage();
            }
        } else {
           echo 'Скрипт install.php отсутствует в каталоге /app/Bitrix24/'
            . ' Инструкция - https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=99&LESSON_ID=8579'
            . ' Архив - https://dev.1c-bitrix.ru/docs/marketplace-and-apps24/server-no-ui-crest.zip'; 
        }
        return;
    });
  
