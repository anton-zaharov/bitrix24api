<?php

namespace Bitrix24api;
use Illuminate\Database\Eloquent\Model;
use Bitrix24api\BitrixBuilder;

class BitrixModel extends Model
{
    protected $connection = 'bitrix24';
    protected function newBaseQueryBuilder() {
        $connection = $this->getConnection();
        $grammar = $connection->getQueryGrammar();
        $grammar->selectWord = $this->selectWord??'list';
        return new BitrixBuilder(
                $connection, $grammar,
                $connection->getPostProcessor()
        );
    }
}
