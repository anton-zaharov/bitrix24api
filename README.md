# bitrix24api
Laravel/Lumen eloquent wrapper for bitrix24 api

## Installation
`composer require anton-zaharov/bitrix24api`

add into bootstrap/app.php

`$app->register('Bitrix24api\ServiceProvider');`

After that you can install local application on the bitrix24 side, like describe
here: 

https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=99&LESSON_ID=8579

After the installation request Bitrix will show two secret variables, and you must define them in bootstrap/app.php

```
define('C_REST_CLIENT_ID', 'local.6213b......fe3.5016....');
define('C_REST_CLIENT_SECRET', 'e6KYiMcVRgDTqjM.............1TPXUTxxHEO6qmKf');
```

Also recomended 

`define('C_REST_BLOCK_LOG', true);`

for prevent logging every request to the Bitrix api.

## Configuration

In the config/database.php add following element to the connections array:

```
'bitrix24' => [
            'driver' => 'bitrix24',
            'database' => '',
            'prefix' => '',
        ],
```

Thats all.

## Console command

Пакет добаляет две консольных команды к списку 
php artisan 
```
bitrix:import           Импорт классов из Битрикс24. Сущности: crm.lead, crm.deal, crm.status with --entity_id=<deal direction> и прочие.
bitrix:references-list  Возвращает описание типов справочников.
```

Коменда bitrix:import позволяет сгенерировать классы сущностей Битрикс24, в котором основные 
и пользовательские поля объявлены как константы, что дает возможность IDE давать 
контекстные подсказки и проводить автодополнение.

Создаваемые классы сущностей наследуются от класса Model Eloquent, что позволяет 
использовать простой синтаксис этой ORM для работы с экземплярами сущности.

## Пример кода

выполним в консоли 

`php artisan bitrix:references-list`

```
+-----------------------+--------------------------------------+
| ID                    | NAME                                 |
+-----------------------+--------------------------------------+
| STATUS                | Статусы                              |
| SOURCE                | Источники                            |
| CONTACT_TYPE          | Тип контакта                         |
| COMPANY_TYPE          | Тип компании                         |
| EMPLOYEES             | Кол-во сотрудников                   |
| INDUSTRY              | Сфера деятельности                   |
| DEAL_TYPE             | Тип сделки                           |
|                       | Статусы счёта (старая версия)        |
| SMART_INVOICE_STAGE_1 | Статусы счёта                        |
| DEAL_STAGE_9          | Стадии сделки Причал                 |
| DEAL_STAGE            | Стадии сделки Общее                  |
| DEAL_STAGE_1          | Стадии сделки Отель                  |
| DEAL_STAGE_3          | Стадии сделки Частный дом            |
| DEAL_STAGE_5          | Стадии сделки Бизнес                 |
| DEAL_STAGE_7          | Стадии сделки Платформа под ресторан |
| QUOTE_STATUS          | Статусы предложения                  |
| HONORIFIC             | Обращения                            |
| EVENT_TYPE            | Тип события                          |
| CALL_LIST             | Статусы обзвона                      |
+-----------------------+--------------------------------------+
```
Если ответ команды выглядет похожим, то приложение установлено нормально.
Из ответа узнаем, что в Битриксе сконфигурированы 6 направлений для сделок.
Сгенерируем класс Deal и настроим его на направление DEAL_STAGE_9.

`php artisan bitrix:import crm.deal`

Результатом работы команды станет генерация двух файлов

```
app/Bitrix24/Base/BaseDeal.php
app/Bitrix24/Deal.php
```

файл Deal.php можно редактировать, дополняя константы или методы с привычными именами.
Также в этом файле в поле $attributes можно задать направление сделки, которое будет добавляться ко всем запросам к Bitrix24 api.
Требуется указать числовой id, который для направления DEAL_STAGE_9 равен 9.

`protected $attributes = [ 'CATEGORY_ID' => 9 ];`

При повторном выполнении команды класс BaseDeal.php будет перезаписываться всегда. Это необходимо для обновления свойств класса после добавления к сделке пользовательского поля на стороне Битрикс24.
Класс Deal.php перезаписываться не будет, чтобы не затереть ваши кастомные правки. Сообщение об этом будет в выводе консольной команды.

Сгенерируем класс с перечнем этапов направленя DEAL_STAGE_9

```
php artisan bitrix:import crm.status --direction=DEAL_STAGE_9
```

Итогом команды будет класс 

`app/Bitrix24/DealStage9`

##How to

Прочитаем из api Сделку с id=21, поменяем ей статус и сохраним.

```
use app\Bitrix24\Deal
/...

$deal = Deal::find(21);
$deal->setStageId(DealStage9::PREPARATION);
$deal->save();
```