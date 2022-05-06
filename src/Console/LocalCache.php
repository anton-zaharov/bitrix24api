<?php

namespace Bitrix24api\Console;

use Bitrix24api\ImportBitrix;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;

//export XDEBUG_CONFIG="idekey=netbeans-xdebug"
class LocalCache extends GeneratorCommand {

    protected $content = '';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bitrix:migration 
                           {name : Импортируемая сущность}'
    ;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Миграция сущности из Битрикс24 (crm.product etc.)' ;
    
    protected function getStub() {
        return __DIR__ . "/stubs/Migration.stub";
    }
    protected function getPath($name) {
        $p = app()->basePath('database/migrations/') . date('Y-m-d_') 
                . Str::padLeft((time() - strtotime('today')), 6,'0') . '_'. $this->getTableName() 
                . '.php'; 
        return $p;
    }
    protected function getNameInput(){
        $n = explode('.', $this->argument('name'));
        $v = array_shift($n);
        return $v . implode('', array_map(function($i){ return Str::of($i)->ucfirst();}, $n));
    }
    protected function getTableName()
    {   
        $n = explode('.', $this->argument('name'));
        $v = array_pop($n);
        return implode('', $n) . '_' . Str::plural($v);
    }
    protected function buildClass($name) {
        $stub = $this->files->get($this->getStub());
        return $this->replaceContent($stub)
                    ->replaceClass($stub, $name);
    }
    protected function replaceContent(&$stub ) {
        $stub = str_replace(['{{ content }}', '{{content}}'], $this->content, $stub);
        $stub = str_replace(['{{ table }}', '{{table}}'], $this->getTableName(), $stub);
        return $this;
    }
    
    public function handle() {
        
        $name = $this->argument('name');
        $module = 'crm';
        if (strpos($name, '.') !== FALSE) {
            $list = explode('.', $name);
            $module = array_shift($list);
            $entity = implode('', $list);
        } else {
            $entity = $name;
        }
        
        $res = ImportBitrix::EntityFields($entity, $module);
        $content = [];
        foreach ($res as $key=>$field) {
            $content[] = match($field['type']) {
                'integer' => "\$table->integer('{$key}')" . (Str::upper($key)==='ID'?'->unique();':';'),
                'datetime', 'string', 'char' => "\$table->string('{$key}', 255);",
                default => null        
            };
        }
        $this->content = implode(PHP_EOL, array_filter($content));
        parent::handle();
    }
}