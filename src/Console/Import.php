<?php

namespace Bitrix24api\Console;

use Bitrix24api\ImportBitrix;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;

class Import extends GeneratorCommand {

    protected $content = [];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bitrix:import 
                           {name : Импортируемая сущность}
                           {--entity_id=0}
                           {--force=1}
                           {--class=}'
    ;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Импорт классов из Битрикс24. ' . PHP_EOL
            . 'Сущности: crm.lead, crm.deal etc., see help';
    protected $template = 'Entity';

    protected function getStub() {
        return __DIR__ . "/stubs/{$this->template}.stub";
    }

    protected function getStubByName($stub) {
        return __DIR__ . "/stubs/$stub.stub";
    }

    protected function getDefaultNamespace($rootNamespace) {
        return $rootNamespace . '\Bitrix24';
    }

    protected function buildClass($name) {
        $stub = $this->files->get($this->getStub());

        return $this->replaceNamespace($stub, $name)->replaceMyStubs($stub)
                    ->replaceEntity($stub, $this->entity)
                    ->replaceClass($stub, $name)
        ;
    }

    /**
     * Get the desired class name from the input.
     *
     * @return string
     */
    protected function getNameInput() {
        return trim($this->type ?? $this->argument('name'));
    }
    
    protected $mask_force = 1;
    public function option($key = null)
    {   
        if ($key === 'force') {
            return $this->mask_force && parent::option($key);
        }
        return parent::option($key);
    }
    public function handle() {
        $module = 'crm';
        $entity = ucfirst($this->argument('name'));
        if ($entity === 'help') {
            $this->help();
            return;
        }
        $this->module = $module;
        ImportBitrix::setCom($this);
        if (strpos($entity, '.') !== FALSE) {
            $list = explode('.', $entity);
            $module = array_shift($list);
            $entity = implode('', array_map('ucfirst', $list));
        }
        
        $this->entity = $entity;
        switch ("$module.$entity") {

            case 'crm.Deal':
            case 'crm.Lead':
            case 'crm.Product':
                $this->template = 'BaseEntity';
                $this->type = "/Base/Base$entity";
                $res = $this->withSetters($list??$entity, $module);
                $this->flatten();
                parent::handle();
                $this->content = [];
            case 'crm.Deal':
                $this->content['attributes'] = $this->insertAttributeStub();
            case 'crm.Lead':
            case 'crm.Product':
                $this->template = 'Entity';
                $this->type = $this->option('class')??$entity;
                $this->mask_force = 0;
                break;
            case 'crm.Status':
            case 'crm.Source':
            case 'calendar.resource':
                $this->template = 'Class';
                $this->type = $this->option('class')??$entity;
                $entity_id = $this->option('entity_id');
                $this->statuses($entity, $module, $entity_id);
                break;
            default:
                $this->template = 'BaseEntity';
                $this->type = "/Base/Base$entity";
                $res = $this->withSetters($list??$entity, $module);
                $this->flatten();
                parent::handle();
                $this->content = [];
                $this->template = 'Entity';
                $this->type = $entity;
                $this->mask_force = 0;
        }

        
            $this->flatten();
            $this->content['module'] = $module;
            parent::handle();
        
    }

    protected function flatten() {
        foreach ($this->content as &$item) {
            if (is_array($item)) {
                $item = implode(PHP_EOL, $item);
            }
        }
    }

    protected function withSetters($entity, $module) {
        $method = is_array($entity)?implode('.', $entity):$entity;
        $res = ImportBitrix::EntityFields($method, $module);

        foreach ($res ?? [] as $key => $r) {
            if (str_starts_with($key, 'UF')) {
                $propertyName = mb_strtoupper(translit($r['listLabel']));
                self::insertFieldConst($this->content, $r['listLabel'], $r['type'],
                        $propertyName, $key);
            } else {
                $propertyName = mb_strtoupper(translit($r['title']));
                self::insertFieldConst($this->content, $r['title'], $r['type'], $r['type'] === 'product_property' ? $propertyName : $key, $key);
            }
            if (isset($r['propertyType']) && $r['propertyType'] === 'L') {
                foreach ($r['values'] as $v) {
                    self::insertFieldConst($this->content, $v['VALUE'], 'Значение списка', "{$r['title']}_{$v['VALUE']}", $v['ID']);
                }
            }
        }
    }

    protected function statuses($entity, $module, $entity_id) {
        $res = ImportBitrix::Status($entity, $module, $entity_id);
        foreach ($res as $r) {
            $ar = explode(':', $r['STATUS_ID']);
            $const = end($ar);
            $this->makeConsts($this->content, $r['NAME'], '', $const, $r['STATUS_ID']);
        }
    }

    protected function makeConsts(&$output, $name, $type, $id, $value) {
        $constSlug = $this->files->get($this->getStubByName('Const'));
        $output['protected'][] = str_replace(['{{ name }}', '{{ type }}', '{{ id }}', '{{ value }}'],
                [$name, $type, $id, $value], $constSlug);
    }
    
    /**
     * Replace entity name
     * @param string $stub
     * @param string $entity
     */
    protected function replaceEntity(&$stub, $entity=null) {
        $stub = str_replace(['{{ entity }}', '{{entity}}'], $entity??$this->entity, $stub);
        return $this;
    }
    
    /**
     * Replace the class name for the given stub.
     *
     * @param  string  $stub
     * @return string
     */
    protected function replaceMyStubs(&$stub) {
        $stub = str_replace(['{{ attributes }}', '{{attributes}}'], $this->content['attributes'] ?? '', $stub);
        $stub = str_replace(['{{ protected }}', '{{protected}}'], $this->content['protected'] ?? '', $stub);
        $stub = str_replace(['{{ functions }}', '{{functions}}'], $this->content['functions'] ?? '', $stub);
        $stub = str_replace(['{{ module }}', '{{module}}'], $this->content['module'] ?? '', $stub);

        return $this;
    }

    protected function insertAttributeStub() {
        return $this->files->get($this->getStubByName('Attribute'));
    }

    /**
     * 
     * @param array $output - буфер строк класса
     * @param type $name - понятное название
     * @param type $type - тип поля
     * @param type $id - ЧПУ название
     * @param type $value - строгий иднтификатор 
     */
    protected function insertFieldConst(&$output, $name, $type, $id, $value) {
        $this->makeConsts($output, $name, $type, $id, $value);
        $getterSlug = $this->files->get($this->getStubByName('Getter'));
        $setterSlug = $this->files->get($this->getStubByName('Setter'));
        $tok = ucfirst(Str::camel(mb_strtolower($id)));

        $output['functions'][] = str_replace(['{{ name }}', '{{ tok }}', '{{ id }}', '{{ value }}'],
                [$name, $tok, $id, $value], $getterSlug);
        $output['functions'][] = str_replace(['{{ name }}', '{{ tok }}', '{{ id }}', '{{ value }}'],
                [$name, $tok, $id, $value], $setterSlug);
    }
    
    protected function help(){
        $this->output('Комманда генерирует класс для любой сущности Битрикс24, имеющей метод fields.
Опция --class задает имя генерируемого класса.
Id CRM справочников Битрикс, в том числе направления, можно узнать коммандой 
   php artisan bitrix:references-list

Также можно генерировать классы с перечнем статусов для направлений, но в этом случае следует указывать 
символьный идентификатор направления. Например, генерировать класс Статусов для общего направления нужно коммандой
    php artisan ditrix:import crm.status --entity_id=STATUS');
           
    }
}

function translit($name) {
    $converter = array(
        'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd',
        'е' => 'e', 'ё' => 'e', 'ж' => 'zh', 'з' => 'z', 'и' => 'i',
        'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n',
        'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't',
        'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c', 'ч' => 'ch',
        'ш' => 'sh', 'щ' => 'sch', 'ь' => '', 'ы' => 'y', 'ъ' => '',
        'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
        'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D',
        'Е' => 'E', 'Ё' => 'E', 'Ж' => 'Zh', 'З' => 'Z', 'И' => 'I',
        'Й' => 'Y', 'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N',
        'О' => 'O', 'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T',
        'У' => 'U', 'Ф' => 'F', 'Х' => 'H', 'Ц' => 'C', 'Ч' => 'Ch',
        'Ш' => 'Sh', 'Щ' => 'Sch', 'Ь' => '', 'Ы' => 'Y', 'Ъ' => '',
        'Э' => 'E', 'Ю' => 'Yu', 'Я' => 'Ya',
    );
    if (!empty($name)) {
        $name = str_replace(array(' ', ','), '_', $name);
        $name = strtr($name, $converter);
        $name = preg_replace('/[-]+/', '_', $name);
        $name = trim($name, '_');
    }
    return $name;
}
