<?php

namespace Bitrix24api;

use DateTime;
use DateTimeZone;
use Illuminate\Database\Connection as ConnectionBase;
use Illuminate\Database\Grammar as GrammerBase;
use Illuminate\Database\Query\Processors\Processor as ProcessorBase;
use Bitrix24api\Processor as Processor;
use Bitrix24api\CRest;
use Illuminate\Support\Str;

class Connection extends ConnectionBase
{
    const TEST = 'test';
    function __construct($pdo, $database = '', $tablePrefix = '', array $config = [])
    {
        parent::__construct($pdo, $database, $tablePrefix, $config);
        $this->useDefaultPostProcessor();
    }
    /**
     * @return GrammerBase
     */
    protected function getDefaultQueryGrammar()
    {
        $grammar = app(Grammar::class);
        $grammar->setConfig($this->getConfig());
        
        return $this->withTablePrefix($grammar);
    }
    
    protected function getDefaultPostProcessor()
    {
        return new Processor;
    }
    public function selectOne($query, $bindings = [], $useReadPdo = true)
    {
        $records = $this->select($query, $bindings);

        return array_shift($records);
    }
    /**
     * @param string|false $query E.g. /articles?status=published
     * @param mixed[] $bindings
     * @param bool $useReadPdo
     * @return mixed[]
     */
    public function select($query, $bindings = [], $useReadPdo = true)
    {
        // Check query.
        if (!$query) {
            return [];
        }
        
        return $this->run($query, $bindings, function ($query, $bindings) {
            extract(unserialize($query));
            $result =  CRest::call($method, $params);
            //dd($method, $params, $result);
            if (isset($result['error'])) { return null; }
            if (Str::endsWith($method, 'get')) {
                $allRows = [$result['result']];
            } else {
                $allRows = $result['result']??[];
            }
            if (isset($allRows['items'])) {
                $allRows = $allRows['items'];
            }
            if (isset($allRows['item'])) {
                $allRows = [$allRows['item']];
            }
            return isset($result['result'])?$allRows:$result;
        });
    }
    public function statement($query, $bindings = [])
    {
        return $this->run($query, $bindings, function ($query, $bindings) {
            if ($this->pretending()) {
                return true;
            }
            $query = unserialize($query);
            if (isset($query['params']['fields']['entityTypeId'])){
                $query['params']['entityTypeId'] = $query['params']['fields']['entityTypeId'];
                unset($query['params']['fields']['entityTypeId']);
            }
            $result =  CRest::call($query['method'], $query['params']);
            $allRows = $result['result']??[];
            if (isset($allRows['item'])) {
                $allRows = [$allRows['item']];
            }
            $this->recordsHaveBeenModified();

            return $allRows;
        });
    }
    /**
     * Run an SQL statement and get the number of rows affected.
     *
     * @param  string  $query
     * @param  array  $bindings
     * @return int
     */
    public function affectingStatement($query, $bindings = [])
    {
        return $this->run($query, $bindings, function ($query, $bindings) {
            if ($this->pretending()) {
                return 0;
            }
            $query = unserialize($query);
            $result =  CRest::call($query['method'], $query['params']);
            $allRows = $result['result']??[];
            $this->recordsHaveBeenModified(
                ($count = (int)($allRows > 0) ) 
            );

            return $count;
        });
    }
    
}