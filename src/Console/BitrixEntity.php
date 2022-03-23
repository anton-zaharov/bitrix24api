<?php
namespace Bitrix24api\Console;

use Illuminate\Console\Command;
use Bitrix24api\CRest;
/**
 * Description of BitrixEntity
 *
 * @author HP
 */
class BitrixEntity extends Command {
    protected $signature = 'bitrix:references-list';
    protected $description = 'Возвращает описание типов справочников.';
    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $result = CRest::call("crm.status.entity.types");
        if (isset($result['error'])) {
            $this->error($result['error_information']);
            return ;
        }
        $headers = ['ID', 'NAME'];
        $rows =[];
        foreach ($result['result'] as $r) {
            $rows[] = ['ID'=> $r['ID']??'', 'NAME'=>$r['NAME']];
        }
        
        $this->table($headers, $rows);
        return 0;
    }
}
