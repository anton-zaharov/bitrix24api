<?php
namespace Bitrix24api;
use Illuminate\Support\Str;
use Bitrix24api\CRest;

/**
 * Description of ImportBitrix
 *
 * @author Anton Zakharov
 * Service class to call Bitrix24 api method for import entities and  
 */
class ImportBitrix {
    protected $stack = [
        'protected' => [],
        'getters' => [],
        'setters' => []
    ];
    private static $com = null;
    public static function setCom($com) {
        self::$com = $com;
    }
    static public function Status($className, $module, $entity = null) {
        $camelClassName = Str::camel(mb_strtolower($className));
        $UpClassName = ucfirst($camelClassName);
        $params = [ 'order' => ["SORT" => "ASC"], 'filter'=>[]];
        if (isset($entity)) { $params['filter'] = ["ENTITY_ID" => $entity]; }
        $result = CRest::call("$module.$className.list", $params);
        return $result['result'];
    }
    static public function EntityFields($className, $module = 'crm', $entityTypeId=null, $method='fields'){
        $params = [];
        if ($entityTypeId) {
            $params['entityTypeId'] = $entityTypeId;
        }
        $result = CRest::call("$module.$className.$method", $params);
        
        if (empty($result['result'])) {
            throw new \Exception("$module.$className.$method " . ($result['error_information']??$result['error_description']));
        }
        if (isset($result['result'][Str::lower($className)])) {
            return $result['result'][Str::lower($className)];
        }
        return isset($result['result']['fields'])?$result['result']['fields']:$result['result'];
    }
}    



