<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Bitrix24api;

use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * Description of BitrixBuilder
 *
 * @author HP
 */
class BitrixBuilder extends QueryBuilder {

    public $operators = [
        '=', '>', '<', '>=', '<=', '%', '!%', 'like', 'not like', 'in',
        '!=', '!>', '!<', '!>=', '!<=', 'not in', 'between', 
    ];
    
    protected $converter = [
        'like' => '=%', 'not like' => '!=%', 'in' => '@', 'not in' => '!@',
        'between' => '><'
    ];
    public function offset($value)
    {
        $property = $this->unions ? 'unionOffset' : 'offset';
        $this->$property = (int) $value;
        return $this;
    }
    public function getGrammar()
    {
        return $this->grammar;
    }
}
