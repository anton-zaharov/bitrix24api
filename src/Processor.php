<?php

namespace Bitrix24api;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Processors\Processor as BaseProcessor;

/**
 * Description of Processor
 *
 * @author HP
 */
class Processor extends BaseProcessor {
    public function processInsertGetId(Builder $query, $sql, $values, $sequence = null)
    {
        $id = $query->getConnection()->insert($sql, $values);

        return is_numeric($id) ? (int) $id : $id;
    }
}
