# bitrix24api
Laravel/Lumen eloquent wrapper for bitrix24 api

## Installation
`composer require anton-zaharov/bitrix24api`

add into bootstrap/app.php

`$app->register('Bitrix24api\ServiceProvider');`

After that you can install local application on the bitrix24 side, like describe
here: 

https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=99&LESSON_ID=8579

## Console command
php artisan will show 2 command

bitrix:import           Импорт классов из Битрикс24. Сущности: lead, deal, status, параметр --entity_id= направление для статусов

bitrix:references-list  Возвращает описание типов справочников.

Приложение позволяет сгенерировать классы сущностей Битрикс24, в котором основные 
и пользовательские поля объявлены как константы, что дает возможность использования 
контекстных подсказок и автодополнения.

Создаваемые классы сущностей наследуются от моделей Eloquent, что позволяет 
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
Сгенерируем класс lead и настроим его на направление DEAL_STAGE_9.

`php artisan bitrix:import crm.deal`