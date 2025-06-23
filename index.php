<? require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php'); ?>
<?
require('./functions.php');
\Bitrix\Main\UI\Extension::load("ui.buttons");

use Bitrix\Main\Loader;
use Bitrix\Crm\Service\Container;

/* @todo проверка доступа, чтобы только для бухов, руков и админов */

Loader::includeModule('crm');
?>
<? $APPLICATION->SetTitle("Платежный календарь"); ?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
<link rel="stylesheet" href="./css/payCalendar.css">

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="select_type mt-3 mb-3">
                <a href="./" class="ui-btn ui-btn-success">По контрагентам</a>
                <a href="./?type=organization" class="ui-btn ui-btn-primary">По организациям</a>
            </div>
            <? if ($_GET['type'] != 'organization'): ?>
                <div class="list_tables">
                    <h1 class="mb-3">По контрагентам</h1>
                    <? $APPLICATION->IncludeComponent(
                        'bitrix:main.ui.filter',
                        '',
                        [
                            'FILTER_ID' => 'FilterPayCalendarCompany',
                            'GRID_ID' => 'PayCalendarCompany',
                            'FILTER' => [
                                [
                                    'id' => 'DATE',
                                    'name' => 'Дата платежа',
                                    'type' => 'date',
                                    'default' => true
                                ],
                                [
                                    'id' => 'ORGANIZATION',
                                    'name' => 'Организация',
                                    'type' => 'entity_selector',
                                    'default' => true,
                                    'params' => [
                                        'multiple' => 'N',
                                        'addEntityIdToResult' => 'Y',
                                        'dialogOptions' => [
                                            'items' => [
                                                ['id' => 6, 'title' => 'ООО «НИКОЛЬ ТЭК»', 'entityId' => 'organization', 'tabs' => 'organization-tabs'],
                                                ['id' => 1795, 'title' => 'ИП Франчук Павел Сергеевич', 'entityId' => 'organization', 'tabs' => 'organization-tabs']
                                            ],
                                            'tabs' => [
                                                ['id' => 'organization-tabs', 'title' => 'Организация']
                                            ]
                                        ]
                                    ]
                                ],
                                [
                                    'id' => 'COMPANY',
                                    'name' => 'Контрагент',
                                    'type' => 'entity_selector',
                                    'default' => true,
                                    'params' => [
                                        'multiple' => 'N',
                                        'addEntityIdToResult' => 'Y',
                                        'dialogOptions' => [
                                            'items' => companyList(),
                                            'tabs' => [
                                                ['id' => 'company-tabs', 'title' => 'Контрагент']
                                            ]
                                        ],
                                    ],
                                ],
                                [
                                    'id' => 'PAY_STATUS',
                                    'name' => 'Статус оплаты',
                                    'type' => 'list',
                                    'items' => [
                                        'N' => 'Не оплачен',
                                        'P' => 'Частично оплачен',
                                        'D' => 'Просрочка'
                                    ]
                                ]
                            ],
                            'ENABLE_LIVE_SEARCH' => false,
                            'ENABLE_LABEL' => true
                        ]
                    ); ?>

                    <? $APPLICATION->IncludeComponent(
                        'bitrix:main.ui.grid',
                        '',
                        [
                            'GRID_ID' => 'PayCalendarCompany',
                            'COLUMNS' => rowsAndColumns($filterFields, 'company')['columns'],
                            'ROWS' => rowsAndColumns($filterFields, 'company')['rows'],
                            'AJAX_MODE' => 'Y',
                            'AJAX_OPTION_JUMP' => 'N'
                        ]
                    ); ?>
                </div>
            <? endif; ?>
            <? if ($_GET['type'] == 'organization'): ?>
                <div class="list_tables">
                    <h1 class="mb-3">По организациям</h1>
                    <div class="accordion" id="accordionExample">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                                    ООО «НИКОЛЬ ТЭК»
                                </button>
                            </h2>
                            <div id="collapseOne" class="accordion-collapse collapse"
                                 data-bs-parent="#accordionExample">
                                <div class="accordion-body">
                                    <? $APPLICATION->IncludeComponent(
                                        'bitrix:main.ui.filter',
                                        '',
                                        [
                                            'FILTER_ID' => 'FilterPayCalendarOrganization1',
                                            'GRID_ID' => 'PayCalendarOrganization1',
                                            'FILTER' => [
                                                [
                                                    'id' => 'DATE',
                                                    'name' => 'Дата платежа',
                                                    'type' => 'date',
                                                    'default' => true
                                                ],
                                                [
                                                    'id' => 'COMPANY',
                                                    'name' => 'Контрагент',
                                                    'type' => 'entity_selector',
                                                    'default' => true,
                                                    'params' => [
                                                        'multiple' => 'N',
                                                        'addEntityIdToResult' => 'Y',
                                                        'dialogOptions' => [
                                                            'items' => companyList(),
                                                            'tabs' => [
                                                                ['id' => 'company-tabs', 'title' => 'Контрагент']
                                                            ]
                                                        ],
                                                    ],
                                                ],
                                                [
                                                    'id' => 'PAY_STATUS',
                                                    'name' => 'Статус оплаты',
                                                    'type' => 'list',
                                                    'items' => [
                                                        'N' => 'Не оплачен',
                                                        'P' => 'Частично оплачен',
                                                        'D' => 'Просрочка'
                                                    ]
                                                ]
                                            ],
                                            'ENABLE_LIVE_SEARCH' => false,
                                            'ENABLE_LABEL' => true
                                        ]
                                    ); ?>
                                    <? $APPLICATION->IncludeComponent(
                                        'bitrix:main.ui.grid',
                                        '',
                                        [
                                            'GRID_ID' => 'PayCalendarOrganization1',
                                            'COLUMNS' => rowsAndColumns($filterOrganizationFields1, 'nikol')['columns'],
                                            'ROWS' => rowsAndColumns($filterOrganizationFields1, 'nikol')['rows'],
                                            'AJAX_MODE' => 'Y',
                                            'AJAX_OPTION_JUMP' => 'N',
                                            'AJAX_OPTION_HISTORY' => 'N'
                                        ]
                                    ); ?>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                    ИП Франчук Павел Сергеевич
                                </button>
                            </h2>
                            <div id="collapseTwo" class="accordion-collapse collapse"
                                 data-bs-parent="#accordionExample">
                                <div class="accordion-body">
                                    <? $APPLICATION->IncludeComponent(
                                        'bitrix:main.ui.filter',
                                        '',
                                        [
                                            'FILTER_ID' => 'FilterPayCalendarOrganization2',
                                            'GRID_ID' => 'PayCalendarOrganization2',
                                            'FILTER' => [
                                                [
                                                    'id' => 'DATE',
                                                    'name' => 'Дата платежа',
                                                    'type' => 'date',
                                                    'default' => true
                                                ],
                                                [
                                                    'id' => 'COMPANY',
                                                    'name' => 'Контрагент',
                                                    'type' => 'entity_selector',
                                                    'default' => true,
                                                    'params' => [
                                                        'multiple' => 'N',
                                                        'addEntityIdToResult' => 'Y',
                                                        'dialogOptions' => [
                                                            'items' => companyList(),
                                                            'tabs' => [
                                                                ['id' => 'company-tabs', 'title' => 'Контрагент']
                                                            ]
                                                        ],
                                                    ],
                                                ],
                                                [
                                                    'id' => 'PAY_STATUS',
                                                    'name' => 'Статус оплаты',
                                                    'type' => 'list',
                                                    'items' => [
                                                        'N' => 'Не оплачен',
                                                        'P' => 'Частично оплачен',
                                                        'D' => 'Просрочка'
                                                    ]
                                                ]
                                            ],
                                            'ENABLE_LIVE_SEARCH' => false,
                                            'ENABLE_LABEL' => true
                                        ]
                                    ); ?>
                                    <? $APPLICATION->IncludeComponent(
                                        'bitrix:main.ui.grid',
                                        '',
                                        [
                                            'GRID_ID' => 'PayCalendarOrganization2',
                                            'COLUMNS' => rowsAndColumns($filterOrganizationFields2, 'faranchuk')['columns'],
                                            'ROWS' => rowsAndColumns($filterOrganizationFields2, 'faranchuk')['rows'],
                                            'AJAX_MODE' => 'Y',
                                            'AJAX_OPTION_JUMP' => 'N',
                                            'AJAX_OPTION_HISTORY' => 'N'
                                        ]
                                    ); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <? endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.min.js" \></script>
<script src="./js/jquery.js"></script>
<script src="./js/main.js"></script>

<? require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php'); ?>
