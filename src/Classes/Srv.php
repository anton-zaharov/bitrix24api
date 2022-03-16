<?php

namespace App\Classes;

use App\Bitrix24\CRest;
use App\Bitrix24\Deal;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Srv
 *
 * @author HP
 */
class Srv {

    static $SecPerDay = 60 * 60 * 24;
    protected static $statusMap = ['93' => 'free', '95' => 'booking', '97' => 'busy'];

    public static function getProducts() {
        $batch = [
            'products' => ['method' => 'crm.product.list', 'params' => ['SELECT' => ['ID', 'NAME', 'PROPERTY_109', 'PRICE']]],
            'books' => ['method' => 'crm.deal.list', 'params' =>
                ['select' => ['ID', Deal::BOOKING_START, Deal::BOOKING_END],
                    'filter' => [
                        "STATUS_ID" => 'IN_PROCESS',
                        '<' . Deal::BOOKING_START => date('d-m-Y') . ' 12:00:00',
                        '>' . Deal::BOOKING_END => date('d-m-Y') . ' 12:00:00'
                    ],
                ]]
        ];

        $r = CRest::callBatch($batch);
        //dd($r);
        extract($r['result']['result']);

        $leads = [];
        foreach ($books as $b) {
            $leads[$b['ID']] = [
                'from' => $b[Deal::BOOKING_START],
                'to' => $b[Deal::BOOKING_END]
            ];
        }
        if ($books) {
            $ids = array_column($books, 'ID');
            $rows = CRest::call('crm.item.productrow.list', [
                        'select' => ['ownerId', 'productId'],
                        "filter" => [
                            "=ownerType" => "D",
                            "=ownerId" => $ids
                        ]
            ]);

            $lpl = array_column($rows['result']['productRows'], 'ownerId', 'productId');
        }
        foreach ($products as &$r) {
            $r['class'] = self::$statusMap[$r['PROPERTY_109']['value']];
            $r['booking'] = isset($lpl)?($leads[$lpl[$r['ID']]] ?? null):null;
            unset($r['PROPERTY_109']);
        }
        return $products;
    }

    public static function setBooking() {
        CRest::setLog($_POST, 'test');
        $result = [];
        $fields = ['fields' => array(
                "TITLE" => "Бронирование места " . $_POST['board_id'],
                "PRODUCT_ID" => $_POST['board_id'],
                "NAME" => $_POST['Name'],
                "EMAIL" => array(array('VALUE' => $_POST['Email'], 'VALUE_TYPE' => 'WORK')),
                "PHONE" => array(array('VALUE' => $_POST['Phone'], 'VALUE_TYPE' => 'WORK')),
                "UF_CRM_1642937420351" => date('d-m-Y') . ' 12:00:00',
                "UF_CRM_1642937530165" => date('d-m-Y', time() + self::$SecPerDay * 2) . ' 12:00:00',
                "STATUS_ID" => 'IN_PROCESS'
        )];

        $res1 = CRest::call(
                        'crm.lead.add',
                        $fields
        );
        CRest::setLog($res1, 'addLead');
        $res2 = CRest::call(
                        'crm.lead.productrows.set',
                        ['id' => $res1['result'],
                            'rows' => [
                                ['PRODUCT_ID' => $_POST['board_id'], "QUANTITY" => 1]
                            ]
                        ]
        );

        $res3 = self::setStatus($_POST['board_id']);
        CRest::setLog($result);
        self::output($result);
    }

    public static function setStatus($product_id = null) {
        //PROPERTY_109
        $pid = $product_id ?? $_REQUEST['board_id'];
        $status = 95;
        $result = CRest::call(
                        "crm.product.update",
                        [
                            'id' => $pid,
                            'fields' =>
                            [
                                'PROPERTY_109' => $status
                            ]
                        ]
        );
        return $result;
    }

    public static function output($result) {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
        header('Content-Type: application/javascript');
        echo json_encode($result);
        return;
    }

}
