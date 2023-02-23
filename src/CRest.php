<?php

namespace Bitrix24api;
include_once __DIR__.'/bin/crest.php';
use \CRest as BaseRest;
use Illuminate\Support\Facades\Log;
/**
 * Description of CRest
 *
 * @author HP
 */
class CRest extends BaseRest {
    public static function installApp(){
        //Log::info(request()->all());
        return parent::installApp();
    }

    protected static function getSettingData()
	{
		$return = [];
                $path = app()->basePath('app/Bitrix24');
		if(file_exists($path . '/settings.json'))
		{
			$return = static::expandData(file_get_contents($path . '/settings.json'));
			if(defined("C_REST_CLIENT_ID") && !empty(C_REST_CLIENT_ID))
			{
				$return['C_REST_CLIENT_ID'] = C_REST_CLIENT_ID;
			}
			if(defined("C_REST_CLIENT_SECRET") && !empty(C_REST_CLIENT_SECRET))
			{
				$return['C_REST_CLIENT_SECRET'] = C_REST_CLIENT_SECRET;
			}
		}
		return $return;
	}

    protected static function setSettingData($arSettings)
	{
            $path = app()->basePath('app/Bitrix24');
            return  (boolean)file_put_contents($path . '/settings.json', static::wrapData($arSettings));
	}
}
