<?php

namespace Bitrix24api;
use Illuminate\Database\Eloquent\Model;

class BitrixModel extends Model
{
    protected $connection = 'bitrix24';
    protected function newBaseQueryBuilder() {
        $connection = $this->getConnection();
        return new BitrixBuilder(
                $connection, $connection->getQueryGrammar(),
                $connection->getPostProcessor()
        );
    }
}
