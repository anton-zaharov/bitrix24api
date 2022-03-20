<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Bitrix24api;

use Illuminate\Database\Connection as ConnectionBase;
use Illuminate\Support\ServiceProvider as ServiceProviderBase;

/**
 * Description of ServiceProvider
 *
 * @author HP
 */
class ServiceProvider extends ServiceProviderBase {

    public function register() {
        include __DIR__.'/routes.php';
        
        ConnectionBase::resolverFor('bitrix24', static function ($connection, $database, $prefix, $config) {
            if (app()->has(Connection::class)) {
                return app(Connection::class);
            }
            return new Connection($connection, $database, $prefix, $config);
        });

        $this->commands([
            Console\BitrixEntity::class,
            Console\Import::class
        ]);
        require_once (__DIR__ . '/crest.php');
    }

}
