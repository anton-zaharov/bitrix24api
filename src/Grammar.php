<?php

namespace Bitrix24api;

use DateTime;
use DateTimeZone;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\Grammar as GrammarBase;
use RuntimeException;

class Grammar extends GrammarBase {

    private $config = [];

    /**
     * @param array $config
     * @return Grammar
     */
    public function setConfig(array $config): self {
        $this->config = $config;

        return $this;
    }

    /**
     * @param Builder $query
     * @return string|false
     */
    protected $converter = [
        //'>' => '<', '<' => '>', '>=' => '<=', '<=' => '>=',
        'like' => '=%', 'not like' => '!=%', 'in' => '@', 'not in' => '!@',
        'between' => '><'
    ];
    
    
    public function mapQuery(Builder $query): array {
        $operators = &$query->operators;
        $params = [];
        $params['SELECT'] = $query->columns;
        foreach ($query->wheres as $where) {
            
            $key = mb_strtoupper($where['column']);
            $dotIx = strrpos($key, '.');
            if ($dotIx !== false) {
                $key = substr($key, $dotIx + 1);
                if (strtolower($key)==='id') {
                    $where['type'] = 'Find';
                    $this->selectWord = 'get';
                }
            }

            // Check where type.
            switch ($where['type']) {
                case 'Find':
                    $params["id"] = $where['value'];
                    break;
                case 'Basic':
                    if (in_array($where['operator'], $operators)) {
                        if (in_array($where['operator'], array_keys($this->converter))) {
                            $op = strtr($where['operator'], $this->converter);
                            $param = "$op$key";
                        } else {
                            $param = "{$where['operator']}$key";
                        }
                    } else {
                        throw new RuntimeException('Unsupported query where operator ' . $where['operator']);
                    }
                    if ($where['column'] === 'entityTypeId') {
                        $params['entityTypeId'] = $where['value'];
                    } else {
                        $params['filter'][$param] = $where['value'];
                    }
                    break;
                case 'In':
                case 'InRaw':
                    if ($this->selectWord === 'get') {
                        $params["id"] = $where['values'][0];
                    } else {
                        $params['filter']["=$key"] = $where['values'];
                    }
                    break;

                case 'between':
                    $params['filter']["><$key"] = ["><$key" => $where['values']];
                    break;

                // Ignore the following where types.
                case 'NotNull':
                    break;

                default:
                    throw new RuntimeException('Unsupported query where type ' . $where['type']);
            }
        }
        if (!empty($query->orders)) {
            if (count($query->orders) > 1) {
                throw new RuntimeException('API query does not support multiple orders');
            }
            foreach ($query->orders as $order) {
                $params['order_by'] = $order['column'];
                if ($order['direction'] === 'desc') {
                    $params['sort'] = 'desc';
                } else {
                    unset($params['sort']);
                }
            }
        }
        if ($query->limit) {
            $params['per_page'] = $query->limit;
        }

        return $params;
    }

    public function compileSelect(Builder $query): string {
        $params = $this->mapQuery($query);
        $url = "{$query->from}.{$this->selectWord}";
        return serialize(['method' => $url, 'params' => $params]);
    }

    /**
     * @param string $key
     * @param string|array|integer|null $value
     * @return mixed
     */
    private function filterKeyValue($key, $value) {
        // Convert timezone.
        $connTimezone = $this->config['timezone'] ?? null;
        if ($connTimezone && in_array($key, $this->config['datetime_keys'])) {
            $connDtZone = new DateTimeZone($connTimezone);
            $appDtZone = new DateTimeZone(config('app.timezone'));
            if (is_string($value)) {
                if (strlen($value) === 19) {
                    $value = (new DateTime($value, $appDtZone))->setTimezone($connDtZone)->format('Y-m-d H:i:s');
                }
            } else if (is_array($value)) {
                $value = array_map(function ($value) use ($connDtZone, $appDtZone) {
                    if (is_string($value) && strlen($value) === 19) {
                        $value = (new DateTime($value, $appDtZone))->setTimezone($connDtZone)->format('Y-m-d H:i:s');
                    }
                    return $value;
                }, $value);
            }
        }

        return $value;
    }
    
    /**
     * Compile an insert statement into SQL.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $values
     * @return string
     */
    public function compileInsert(Builder $query, array $values)
    {
        $params = $this->mapQuery($query);
        //dd($params);
        $url = "{$query->from}.add";
        return serialize(['method' => $url, 'params' => ['fields'=>$values] ]);
    }
    
    public function compileUpdate(Builder $query, array $values)
    {
        $params = $this->mapQuery($query);
        //dd($params);
        $url = "{$query->from}.update";
        return serialize(['method' => $url, 'params' => ['id'=>$params['filter']['=ID'], 'fields'=>$values] ]);
    }
    
    /**
     * Compile a delete statement into SQL.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return string
     */
    public function compileDelete(Builder $query)
    {
        $params = $this->mapQuery($query);
        //dd($params);
        $url = "{$query->from}.delete";
        return serialize(['method' => $url, 'params' => ['id'=>$params['filter']['=ID'] ] ]);
    }


}
