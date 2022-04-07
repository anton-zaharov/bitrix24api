<?php

namespace Bitrix24api;

class Batch 
{
    protected $queries ;
    
    function __construct(array $params = []) {
        $this->queries = collect($params);   
    }
    public function add($key, $method, $params=null){
        if (gettype($method)==='object'){
            $this->queries->put($key, $method);
        } else {
            $this->queries->put($key, compact('method', 'params'));
        }
    }
    
    public function remove($key){
        $this->queries->forget($key);
    }
    
    public function keys()
    {
        return $this->queries->keys();
    }
    public function run(){
        $batch = 
        $this->queries->mapWithKeys(function($q, $key){
            if (gettype($q) === 'object') {
                $apl = $q->getQuery()->getGrammar()->compileSelect($q->getQuery());
                $q = unserialize($apl);
            }
            return [$key => $q];
        })->all();
        $r = CRest::callBatch($batch);
        if (isset($r['result']['result']))
        {
            $r = $r['result']['result'];
        }
        return $r;
    }
    
    
}