<?php
namespace Bitrix24api\Facades;

use Illuminate\Support\Facades\Facade;
class BxFacade extends Facade 
{
    protected static function getFacadeAccessor()
    {
        return App\Bitrix24\Classes\Service::class;
    }
}
