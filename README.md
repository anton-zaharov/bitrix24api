# bitrix24api
Laravel/Lumen eloquent wrapper for bitrix24 api

## Installation
`composer require anton-zaharov/bitrix24api`

add into bootstrap/app.php
`$app->register('Bitrix24api\ServiceProvider');`

## Console command
php artisan will show 2 command

bitrix:import           Импорт классов из Битрикс24. Сущности: lead, deal, status, параметр --entity_id= направление для статусов

bitrix:references-list  Возвращает описание типов справочников.

