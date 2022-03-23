<?php
namespace Bitrix24api\Facades;

use Illuminate\Support\Facades\Facade;
class Bitrix24 extends Facade 
{
    protected static function getFacadeAccessor()
    {
        return \App\Bitrix24\Classes\Service::class;
    }
}
