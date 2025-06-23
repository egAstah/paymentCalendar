<?php
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Loader;
use Bitrix\Crm\Service\Container;

/* @todo проверка доступа, чтобы только для бухов, руков и админов */

Loader::includeModule('crm');

class CUserOptionsOver extends CUserOptions
{
    public static function SetBaseUserCache($user_id, $base_user_id, $category, $name, $default_value = false)
    {
        if ($base_user_id == $user_id) {
            return;
        }
        if (!isset(parent::$cache[$user_id])) {
            parent::$cache[$user_id] = array();
        }
        if (!isset(parent::$cache[$user_id][$category])) {
            parent::$cache[$user_id][$category] = array();
        }
        parent::$cache[$user_id][$category][$name] = parent::GetOption($category, $name, $default_value, $base_user_id);
    }
}

global $USER;

function getDocumetnsData($filterFields, $type)
{
    $factory = Container::getInstance()->getFactory(1090);
    $filter1 = [
        "!STAGE_ID" => [
            "DT1090_18:FAIL",
            "DT1090_18:SUCCESS"
        ]
    ];
    $filter2 = [
        "!STAGE_ID" => [
            "DT1090_18:FAIL",
            "DT1090_18:SUCCESS"
        ]
    ];

    if($type == 'nikol'){
        $filter1['MYCOMPANY_ID'] = 6;
        $filter2['MYCOMPANY_ID'] = 6;
    }

    if($type == 'faranchuk'){
        $filter1['MYCOMPANY_ID'] = 1795;
        $filter2['MYCOMPANY_ID'] = 1795;
    }

    if ($filterFields['DATE_from']) {
        $filter1['>=UF_DOCS_CLIENT_PAY_DAY_PLAN'] = $filterFields['DATE_from'];
        $filter1['<=UF_DOCS_CLIENT_PAY_DAY_PLAN'] = $filterFields['DATE_to'];
        $filter2['>=UF_DOCS_CARRIER_PAY_DAY_PLAN'] = $filterFields['DATE_from'];
        $filter2['<=UF_DOCS_CARRIER_PAY_DAY_PLAN'] = $filterFields['DATE_to'];
    }

    if($filterFields['COMPANY']){
        $filter1['COMPANY_ID'] = json_decode($filterFields['COMPANY'], 1)[1];
        $filter2['COMPANY_ID'] = json_decode($filterFields['COMPANY'], 1)[1];
    }

    if ($filterFields['ORGANIZATION']) {
        $filter1['MYCOMPANY_ID'] = json_decode($filterFields['ORGANIZATION'], 1)[1];
        $filter2['MYCOMPANY_ID'] = json_decode($filterFields['ORGANIZATION'], 1)[1];
    }

    if ($filterFields['PAY_STATUS'] == 'N') {
        $filter1['UF_DOCS_CHECK_PAID'] = 0;
        $filter1['UF_DOCS_CARRIERCHECK_PAID'] = 0;
        $filter2['UF_DOCS_CHECK_PAID'] = 0;
        $filter2['UF_DOCS_CARRIERCHECK_PAID'] = 0;
    }

    if($filterFields['PAY_STATUS'] == 'D'){
        $filter1['UF_DOCS_CHECK_PAID'] = 0;
        $filter1['<UF_DOCS_CLIENT_PAY_DAY_PLAN'] = date('d.m.Y');
        $filter2['UF_DOCS_CARRIERCHECK_PAID'] = 0;
        $filter2['<UF_DOCS_CARRIER_PAY_DAY_PLAN'] = date('d.m.Y');
    }

    if ($filterFields['PAY_STATUS'] == 'P') {
        $filter1['!=UF_DOCS_CLIENT_PAY_PREPAY'] = '';
        $filter2['!=UF_DOCS_CARRIER_PAY_PREPAY'] = '';
    }

    $items1 = $factory->getItems([
        'filter' => $filter1,
        'select' => ['*', 'UF_*']
    ]);

    $items2 = $factory->getItems([
        'filter' => $filter2,
        'select' => ['*', 'UF_*']
    ]);

    $arrDocs = [];
    foreach ($items1 as $key => $item) {
        $arrDocs[] = $item->getData();
    }
    foreach ($items2 as $key => $item) {
        $arrDocs[] = $item->getData();
    }

    return $arrDocs;
}

function buildColumns($rows)
{
    $columnsArr = [];

    foreach ($rows as $row) {
        $key = array_key_first($row['columns']);
        if ($columnsArr[$key]) {
            continue;
        }
        $columnsArr[$key] = [
            'id' => $key,
            'name' => $key,
            'default' => true,
            'showname' => true,
            'width' => 120,
            'resizeable' => false,
        ];
    }

    return $columnsArr;
}

function calcPayDay($doc, $carrier = false)
{
    if ($doc['UF_DOCS_CLIENT_PAY_DAY_PLAN'] && !$carrier) {
        return $doc['UF_DOCS_CLIENT_PAY_DAY_PLAN'];
    }
    if ($doc['UF_DOCS_CARRIER_PAY_DAY_PLAN'] && $carrier) {
        return $doc['UF_DOCS_CARRIER_PAY_DAY_PLAN'];
    }

    $payCondBlockID = $doc['UF_DOCS_CLIENT_PAY_COND'];
    $payDelayFull = $doc['UF_DOCS_CLIENT_PAY_DELAY'];

    if ($carrier) {
        $payCondBlockID = $doc['UF_DOCS_CARRIER_PAY_COND'];
        $payDelayFull = $doc['UF_DOCS_CARRIER_PAY_DELAY'];
    }

    $payDelay = preg_replace('/\D/', '', $payDelayFull);

    $addDaysParams = [
        'TYPE' => 1,
        'INTERVAL_DAY' => $payDelay,
        'IS_WORKDAY' => 'N',
    ];

    if (strpos($payDelayFull, 'банк') !== FALSE) {
        $addDaysParams['IS_WORKDAY'] = 'Y';
    }

    switch ($payCondBlockID) {
        case 47:
            $startDate = $doc['UF_DOCS_UNLOAD_DATE'];
            break;
        case 46:
            $startDate = $doc['UF_DOCS_LOAD_DATE'];
            break;
        case 44:
            $startDate = $doc['UF_DOCS_TTN_SCAN_DATE'];
            break;
        case 43:
            $startDate = $doc['UF_DOCS_TTN_ORIG_DATE'];
            break;
        default:
            return false;
            break;
    }
    if (!$startDate) {
        return false;
    }
    $payDay = \Bitrix\Crm\Recurring\DateType\Day::calculateDate($addDaysParams, $startDate);
    return $payDay->add('1D');
}

function formatMoney($payRate, $docID, $isPaid)
{
    $class = '';
    if ($isPaid) {
        $class = 'class="billPaidClient"';
    }
    $color = '#158015';
    if (strpos($payRate, '-') !== FALSE) {
        $color = '#C81D1D';
        if ($isPaid) {
            $class = 'class="billPaidCarrier"';
        }
    }

    $pay = str_replace('|RUB', '', $payRate);

    $payWithLink = '<a ' . $class . ' target="_blank" style="color: ' . $color . '; text-decoration: underline;" href="/crm/type/1090/details/' . $docID . '/">' . $pay . '</a>';

    $moneyRow = "<div>$payWithLink</div>";
    return $moneyRow;
}

function buildRows($arrDocs, $filterFields)
{
    $rowsArr = [];
    $payArr = [];

    foreach ($arrDocs as $doc) {
        $payDayClient = $doc['UF_DOCS_CLIENT_PAY_DAY_PLAN'];
        $payDayCarrier = $doc['UF_DOCS_CARRIER_PAY_DAY_PLAN'];
        if ($payDayClient != '') {
            $dateStringClient = $payDayClient->format("d.m.y");
            $clientMoney = str_replace('|RUB', '', $doc['UF_DOCS_CLIENT_PAY_RATE']);
            $formatedMoney = formatMoney($clientMoney, $doc['ID'], $doc['UF_DOCS_CHECK_PAID']);
            $payArr[$dateStringClient][$doc['COMPANY_ID']]['html'] = $formatedMoney;
            $payArr[$dateStringClient][$doc['COMPANY_ID']]['data'] = $clientMoney;
            if($filterFields['PAY_STATUS'] == 'D'){
                if($doc['UF_DOCS_CHECK_PAID'] == 0 && $payDayClient > date('d.m.Y')){
                    unset($payArr[$dateStringClient]);
                }
            }
        }
        if ($payDayCarrier != '') {
            $dateStringCarrier = $payDayCarrier->format("d.m.y");
            $carrierMoney = '-' . str_replace('|RUB', '', $doc['UF_DOCS_CARRIER_PAY_RATE']);
            $formatedMoney = formatMoney($carrierMoney, $doc['ID'], $doc['UF_DOCS_CARRIERCHECK_PAID']);
            $payArr[$dateStringCarrier][$doc['UF_DOCS_CARRIER']]['html'] = $formatedMoney;
            $payArr[$dateStringCarrier][$doc['UF_DOCS_CARRIER']]['data'] = $carrierMoney;
            if($filterFields['PAY_STATUS'] == 'D'){
                if($doc['UF_DOCS_CARRIERCHECK_PAID'] == 0 && $payDayCarrier > date('d.m.Y')){
                    unset($payArr[$dateStringCarrier]);
                }
            }
        }
    }

    $rowsArr = [];
    foreach ($payArr as $payDay => $payemnts) {
        foreach ($payemnts as $key => $payment) {
            $rowsArr[] = [
                'id' => $key,
                'columns' => [
                    $payDay => $payment['html'],
                ],
                'data' => [
                    $payDay => $payment['data'],
                ]
            ];
        }
    }
    return $rowsArr;
}

function addSummRows($rows)
{
    $newrows['left'] = [
        'columns' => [
            'title' => 'Остаток (планируемый), руб.'
        ]
    ];
    $newrows['plus'] = [
        'columns' => [
            'title' => 'Итоговый приход (планируемый), руб.'
        ]
    ];
    $newrows['minus'] = [
        'columns' => [
            'title' => 'Итоговый расход (планируемый), руб.'
        ]
    ];

    foreach ($rows as $row) {
        $key = array_key_first($row['data']);
        $value = (int)$row['data'][$key];
        if ($value < 0) {
            $newrows['minus']['columns'][$key] += $value;
        } else {
            $newrows['plus']['columns'][$key] += $value;
        }
        $newrows['left']['columns'][$key] += $value;
    }

    return $rows + $newrows;
}

function companyList()
{
    $result = [];
    $entityResult = \CCrmCompany::GetListEx(['ID' => 'DESC'], [], false, false, ['ID', 'TITLE']);
    while ($arCompany = $entityResult->fetch()) {
        $result[] = [
            'id' => $arCompany['ID'],
            'title' => $arCompany['TITLE'],
            'entityId' => 'my_company',
            'tabs' => 'company-tabs'
        ];
    }

    return $result;
}

function rowsAndColumns($filterFields, $type)
{
    $docsData = getDocumetnsData($filterFields, $type);
    $rows = buildRows($docsData, $filterFields);
    $columns = buildColumns($rows);

    uksort($columns, function ($a, $b) {
        $dateA = DateTime::createFromFormat('d.m.y', $a);
        $dateB = DateTime::createFromFormat('d.m.y', $b);
        return $dateA <=> $dateB;
    });

    $rows = addSummRows($rows);
    $columns = array_reverse($columns);

    $columns['companies'] = [
        'id' => 'title',
        'name' => 'Название',
        'default' => true,
        'showname' => true,
        'color' => 'rgb(204, 236, 251)',
//    'sticked' => true,
        'width' => 250,
        'resizeable' => false
    ];

    $columns = array_reverse($columns);

    foreach ($rows as $key => $row) {
        $arSelect = ['ID', 'TITLE'];
        $arFilter = array('ID' => $row['id']);
        $rsCompany = CCrmCompany::GetList(array(), $arFilter, $arSelect);
        if ($arCompany = $rsCompany->Fetch()) {
            $compName = "<a href='/crm/company/details/{$row['id']}/'>{$arCompany['TITLE']}</a>";
            $rows[$key]['columns']['title'] = $compName;
        }
    }

    return $result = [
        'rows' => $rows,
        'columns' => $columns
    ];
}

$filterOptions = new \Bitrix\Main\UI\Filter\Options("FilterPayCalendarCompany");
$filterFields = $filterOptions->getFilter();

$filterOrganizationOption1 = new \Bitrix\Main\UI\Filter\Options("FilterPayCalendarOrganization1");
$filterOrganizationFields1 = $filterOrganizationOption1->getFilter();

$filterOrganizationOption2 = new \Bitrix\Main\UI\Filter\Options("FilterPayCalendarOrganization2");
$filterOrganizationFields2 = $filterOrganizationOption2->getFilter();

