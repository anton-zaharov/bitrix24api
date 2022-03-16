<?php

namespace Bitrix24api\Classes;
use Bitrix24api\Batch;
use Bitrix24api\Product;
use Bitrix24api\CRest;
/**
 * Description of Service
 *
 * @author HP
 */
class Service {
    
    static function products(){
        $data = [];
        $batch = new Batch();
        $batch->add('product_0', Product::select([Product::NAME, Product::PRICE, Product::STATUS])->where(Product::ID, '>', 0));
        $batch->add('product_111', Product::select([Product::NAME, Product::PRICE, Product::STATUS])->where(Product::ID, '>', 111));
        $res = $batch->run();
        extract($res);
        
        foreach ($batch->keys() as $name) {
            if (isset($$name)) 
                foreach ($$name as $item){
                    $item['class'] = 'free';
                    $data[] = $item;
                }
        }
        return $data;
    }
    
    static function resources(){
        $data = CRest::call('calendar.resource.list');
        return $data['result']??[];
    }
}
