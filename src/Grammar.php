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
    
    protected function detectGet(Builder $query){
        return (false && count($query->wheres) === 1 
                && $query->wheres[0]['type'] === 'Basic'
                && str_ends_with($query->wheres[0]['column'],'.id') 
                );
    } 
    public function mapQuery(Builder $query): array {
        $operators = &$query->operators;
        $params = [];
        $params['SELECT'] = $query->columns;
        foreach ($query->wheres as $where) {
            // Get key and strip table name.
            $key = mb_strtoupper($where['column']);
            $dotIx = strrpos($key, '.');
            if ($dotIx !== false) {
                $key = substr($key, $dotIx + 1);

                // If the key has dot and type = 'Basic', we need to change type to 'In'.
                // This fixes lazy loads.
                if ($where['type'] === 'Basic') {
                    $where['type'] = 'In';
                    $where['values'] = [$where['value']];
                    unset($where['value']);
                }
            }

            // Check where type.
            switch ($where['type']) {
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
                    $params['filter'][$param] = $where['value'];
                    break;
                case 'In':
                case 'InRaw':
                    $params['filter']["=$key"] = $where['values'];
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
        //dd($params);
        if ($this->detectGet($query)){
            $url = "{$query->from}.get";
            $params = ['ID' => $params['filter']['=id'][0] ];
        } else {
            $url = "{$query->from}.list";
        }
        if (!empty($params)) {
            /*             * $url .= '?';
              $queryStr = Str::httpBuildQuery(
              $params,
              !empty($this->config['pluralize_array_query_params']),
              $this->config['pluralize_except'] ?? [],
              );
              if ($queryStr === false) {
              return false;
              }
              $url .= $queryStr;
             * 
             */
        }

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
