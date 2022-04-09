<?php

namespace Bitrix24api;

use DateTime;
use DateTimeZone;
use Illuminate\Database\Connection as ConnectionBase;
use Illuminate\Database\Grammar as GrammerBase;
use Illuminate\Database\Query\Processors\Processor as ProcessorBase;
use Bitrix24api\Processor as Processor;
use Bitrix24api\CRest;

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
            //dd($params);
            $result =  CRest::call($method, $params);
            $allRows = $result['result']??[];
            // Convert timezone in datetime keys.
            $connectionTimezone = $this->getConfig('timezone');
            if ($connectionTimezone && !empty($result['result'])) {
                $appTimezone = config('app.timezone');
                if ($connectionTimezone !== $appTimezone) {
                    $configDatetimeKeys = $this->getConfig('datetime_keys');
                    if (!empty($configDatetimeKeys)) {
                        // Get available datetime keys.
                        $datetimeKeys = [];
                        $firstRow = $allRows[0];
                        foreach ($configDatetimeKeys as $key) {
                            if (array_key_exists($key, $firstRow)) {
                                $datetimeKeys[] = $key;
                            }
                        }
                        if (!empty($datetimeKeys)) {
                            $connDtZone = new DateTimeZone($connectionTimezone);
                            $appDtZone = new DateTimeZone($appTimezone);

                            // Convert timezone for each object.
                            foreach ($allRows as &$pRow) {
                                foreach ($datetimeKeys as $key) {
                                    $connValue = $pRow[$key];

                                    // Check if it is a correct datetime in 'Y-m-d H:i:s' format.
                                    if ($connValue != '' && strlen($connValue) === 19 && $connValue !== '0000-00-00 00:00:00') {
                                        // Convert and save.
                                        $dt = new DateTime($connValue, $connDtZone);
                                        $dt->setTimezone($appDtZone);
                                        $pRow[$key] = $dt->format('Y-m-d H:i:s');
                                    }
                                }
                            }
                        }
                    }
                }
            }

            return isset($result['result'])?$result['result']:$result;
        });
    }
    public function statement($query, $bindings = [])
    {
        return $this->run($query, $bindings, function ($query, $bindings) {
            if ($this->pretending()) {
                return true;
            }
            $query = unserialize($query);
            $result =  CRest::call($query['method'], $query['params']);
            $allRows = $result['result']??[];

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