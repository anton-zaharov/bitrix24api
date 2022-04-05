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
    static public function EntityFields($className, $module = 'crm'){
        $result = CRest::call("$module.$className.fields", []);
        
        if (empty($result['result'])) {
            throw new \Exception("$module.$className.fields " . $result['error_information']??'');
        }
        return $result['result'];
    }
}    



