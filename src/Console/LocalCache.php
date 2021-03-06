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
                           {name : Импортируемая сущность}
                           {--method=fields}
                           {--entity_id=null}'
    ;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Миграция сущности из Битрикс24 (crm.product etc.)';

    protected function getStub() {
        return __DIR__ . "/stubs/Migration.stub";
    }

    protected function getPath($name) {
        $n = $this->getArrayName();
        array_unshift($n, 'create');
        $p = app()->basePath('database/migrations/') . date('Y_m_d_')
                . Str::padLeft((time() - strtotime('today')), 6, '0') . '_' . implode('_', $n)
                . '.php';
        return $p;
    }

    protected function getArrayName() {
        return explode('.', $this->argument('name'));
    }

    protected function getNameInput() {
        $n = $this->getArrayName();
        array_unshift($n, 'create');
        return implode('', array_map('ucfirst', $n));
    }

    protected function getTableName() {
        $n = $this->getArrayName();
        $v = array_pop($n);
        return implode('', $n) . '_' . Str::plural($v);
    }

    protected function buildClass($name) {
        $stub = $this->files->get($this->getStub());
        return $this->replaceContent($stub)
                        ->replaceClass($stub, Str::camel($name));
    }

    protected function replaceContent(&$stub) {
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
        $method = $this->option('method');
        $entityId = $this->option('entity_id');
        $res = ImportBitrix::EntityFields($entity, $module, $entityId, $method);
        $content = [];
        foreach ($res as $key => $field) {
            if ($field['isMultiple']) {
                $content[] = "\$table->text('{$key}')->nullable(); // {$field['type']}";
            } else {
                $content[] = match ($field['type']) {
                            'integer' => "\$table->integer('{$key}')"
                            . (Str::upper($key) === 'ID' ? '->unique()' : '')
                            . (!$field['isRequired'] ? '->nullable()' : '')
                            . ';',
                            'double',
                            'crm_currency', 'string', 'char', 'crm_status' => "\$table->string('{$key}', 255)"
                            . '->nullable()' .(!$field['isRequired'] ? '' : '->default("")')
                            . ';',
                            'boolean' => "\$table->boolean('{$key}', 255)"
                            . (!$field['isRequired'] ? '->nullable()' : '')
                            . ';',
                            'date', 'datetime' => "\$table->{$field['type']}('{$key}')"
                            . (!$field['isRequired'] ? '->nullable()' : '')
                            . ';',
                            'crm_multifield' => "\$table->text('{$key}')->nullable();",
                            Str::startsWith($key, 'crm_') => "\$table->integer('{$key}')->nullable();",
                            default => "\$table->integer('{$key}')"
                            . (!$field['isRequired'] ? '->nullable()' : '')
                            . ';',
                        } . " // {$field['type']}";
            }
        }
        $this->content = implode(PHP_EOL, array_filter($content));
        parent::handle();
    }

}
