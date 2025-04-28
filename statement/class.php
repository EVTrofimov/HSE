<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use
    Bitrix\Main\Application,
    Bitrix\Main\Context,
    Bitrix\Main\ArgumentException,
    Bitrix\Main\Loader,
    Bitrix\Main\Localization\Loc,
    Bitrix\Main\LoaderException,
    Bitrix\Main\ObjectPropertyException,
    Bitrix\Main\SystemException,
    Bitrix\Main\Engine\Contract\Controllerable,
    Bitrix\Main\Error,
    Bitrix\Main\Errorable,
    Bitrix\Main\ErrorCollection,
    Bitrix\Iblock\Component\Tools,
    Bitrix\Main\Data\LocalStorage\SessionLocalStorage,
    Bitrix\Currency\CurrencyManager,
    Bitrix\Sale,
    Bitrix\Sale\Order,
    Bitrix\Sale\Basket,
    Bitrix\Sale\Delivery,
    Bitrix\Sale\PaySystem,
    Bitrix\Main\Web\Uri,
    Bitrix\Main\Web\HttpClient;


class Statement extends CBitrixComponent implements Controllerable, Errorable
{
    protected $btr_tmp;
    protected $errorCollection;
    protected $suggestURL = "https://suggest-maps.yandex.ru/v1/suggest";

    public function configureActions(): array
    {
        return [
            'createFormResult' => [
                'prefilters' => []
            ],
            'getSuggest' => [
                'prefilters' => []
            ],
        ];
    }

    /**
     * Получение параметров компонента которые будут учтены при обращении к экшену компонента
     * через AJAX для этого надо будет при вызове этого action
     * передать signedParameters в параметры BX.ajax.runComponentAction;
     * @return array
     */
    protected function listKeysSignedParameters(): array
    {
        return [
            'SEF_FOLDER'
        ];
    }

    /**
     * @throws LoaderException
     */
    protected function checkModules(): bool
    {
        // TODO: Переделать через яызковые фразы, передавать во фразу имя модуля
        if (!Loader::includeModule('iblock')) {
            throw new SystemException("Cannot find module `iblock`");
        }

        if (!Loader::includeModule('form')) {
            throw new SystemException("Cannot find module `form`");
        }

        if (!Loader::includeModule('catalog')) {
            throw new SystemException("Cannot find module `catalog`");
        }

        if (!Loader::includeModule('sale')) {
            throw new SystemException("Cannot find module `sale`");
        }

        return true;
    }

    public function onPrepareComponentParams($arParams): array
    {
        $this->errorCollection = new ErrorCollection();
        return $arParams;
    }

    private function gen_guid($len = 20): string
    {
        $bytes = openssl_random_pseudo_bytes($len, $cstrong);
        return bin2hex($bytes);
    }

    /**
     * @throws ObjectPropertyException
     * @throws SystemException
     * @throws ArgumentException
     * @throws LoaderException
     */
    public function executeComponent(): void
    {
        global $APPLICATION;

        $this->includeComponentLang('class.php');

        $this->btr_tmp = $_SERVER["DOCUMENT_ROOT"]."/bitrix/tmp/";
        $this->arResult['ERROR'] = FALSE;
        $iblock_id = 0;
        $element_id = 0;

        $componentPage = $this->sefMode();

        // отдаем 404 статус если не найден шаблон
        if (!$componentPage) {
            Tools::process404(
                $this->arParams["MESSAGE_404"],
                TRUE,
                TRUE,
                TRUE,
                $this->arParams["FILE_404"]
            );
        }

        try {
            if ("index" === $componentPage) {
                $this->arResult['ALL_STATEMENTS'] = $this->getAllStatements();
            }

            if ("index" !== $componentPage) {
                $this->checkModules();
                $statement = $this->getStatement($this->arResult['VARIABLES']['ELEMENT_CODE']);

                if (!$statement) {
                    Tools::process404(
                        $this->arParams["MESSAGE_404"],
                        TRUE,
                        TRUE,
                        TRUE,
                        $this->arParams["FILE_404"]
                    );
                }
                $this->arResult = array_merge($this->arResult, $statement);

                $iblock_id = intval($this->arParams['IBLOCK_ID']);
                $element_id = $statement['ID'];

                $APPLICATION->AddChainItem($statement['NAME'], '/statement/' . $statement['CODE'] . '/');
            }

            if (in_array($componentPage, ['offer', 'offer_edit', 'pdf'])) {
                $this->arResult['CURRENT_OFFER'] = [];

                foreach ($this->arResult['OFFERS'] as $o) {
                    if ($o['CODE'] == $this->arResult['VARIABLES']['OFFER_CODE']) {
                        $this->arResult['CURRENT_OFFER'] = $o;
                        break;
                    }
                }

                if (empty($this->arResult['CURRENT_OFFER'])) {
                    Tools::process404(
                        $this->arParams["MESSAGE_404"],
                        TRUE,
                        TRUE,
                        TRUE,
                        $this->arParams["FILE_404"]
                    );
                }

                $iblock_id = $this->arResult['CURRENT_OFFER']['IBLOCK_ID'];
                $element_id = $this->arResult['CURRENT_OFFER']['ID'];

                $APPLICATION->AddChainItem($this->arResult['CURRENT_OFFER']['NAME']);

                //require_once($_SERVER["DOCUMENT_ROOT"] . $this->GetPath() . "/structures/"
                //    . $this->arResult['CODE'] . "-" . $this->arResult['CURRENT_OFFER']['CODE'] . ".php");

                $this->arResult['FORM_STRUCTURE'] = $this->arResult['CURRENT_OFFER']['STRUCTURE'];
            }

            // Получаем СЕО-информацию
            $ipropValues = new \Bitrix\Iblock\InheritedProperty\ElementValues(
                $iblock_id,
                $element_id
            );

            $this->arResult["SEO"] = $ipropValues->getValues();

            if (isset($this->arResult["SEO"]["ELEMENT_META_TITLE"]) && $this->arResult["SEO"]["ELEMENT_META_TITLE"] != "") {
                $APPLICATION->SetTitle($this->arResult["SEO"]['ELEMENT_META_TITLE']);
            }

            if (isset($this->arResult["SEO"]["ELEMENT_META_KEYWORDS"]) && $this->arResult["SEO"]["ELEMENT_META_KEYWORDS"] != "") {
                $APPLICATION->SetPageProperty("keywords",  $this->arResult["SEO"]["ELEMENT_META_KEYWORDS"]);
            }

            if (isset($this->arResult["SEO"]["ELEMENT_META_DESCRIPTION"]) && $this->arResult["SEO"]["ELEMENT_META_DESCRIPTION"] != "") {
                $APPLICATION->SetPageProperty("description",  $this->arResult["SEO"]["ELEMENT_META_DESCRIPTION"]);
            }


            $this->arResult['ORDER'] = FALSE;
            //$this->arResult['PDF_URL'] = "";
            if (in_array($componentPage, ['offer_edit', 'pdf'])) {
                $componentPage = $componentPage == 'offer_edit' ? "offer" : $componentPage;
                $this->arResult['ORDER'] = $this->getOrder($this->arResult['VARIABLES']['ORDER_ID']);
                $form_names = getFormStructureNamesByTypes($this->arResult['CURRENT_OFFER']["FORM_HTML"]);
                foreach ($form_names["file"] as $name) {
                    foreach ($this->arResult['ORDER']["FORM_DATA"][$name]["VALUE"] as $file_id) {
                        $this->arResult['ORDER']["FORM_DATA"][$name]["FILES"][$file_id] = CFile::GetFileArray($file_id);
                    }
                }

                //$this->arResult['PDF_URL'] = "/statement/" . $this->arResult['VARIABLES']['ELEMENT_CODE'] . "/" .
                //    $this->arResult['VARIABLES']['OFFER_CODE'] . "/" . $this->arResult['VARIABLES']['ORDER_ID'] . "/pdf/?get_pdf=1";
            }

            $this->arResult['PDF_FILE_NAME'] = str_replace(' ', '_', date("Y-m-d")
                    . '_' . $this->arResult['NAME'] . '_' . $this->arResult['CURRENT_OFFER']['NAME']) . '_' . '.pdf';

            if ('pdf' == $componentPage) {
                if (!isset($_GET['get_pdf'])) {
                    Tools::process404(
                        $this->arParams["MESSAGE_404"],
                        TRUE,
                        TRUE,
                        TRUE,
                        $this->arParams["FILE_404"]
                    );
                }

                if ($this->arResult['ORDER']['PAYED'] !== 'Y') {
                    throw new SystemException("Ошибка доступа. Оплатите файл.");
                }
            }

//            $ret = $this->createFormResultAction();
//            if(!$ret) {
//                dump($this->errorCollection->toArray());
//            }

            $this->includeComponentTemplate($componentPage);
        } catch (SystemException $e) {
//            ShowError($e->getMessage());
            $APPLICATION->SetTitle("Ошибка");
            $this->arResult['ERROR_TEXT'] = $e->getMessage();
            $this->includeComponentTemplate("error");
        }
    }

    // метод обработки режима ЧПУ
    protected function sefMode(): string
    {
        // значение маски для подключения шаблона по умолчанию, section.php, element.php, index.php
        $arUrlTemplates = [
            "index" => "",
            "element" => "#ELEMENT_CODE#/",
            "offer" => "#ELEMENT_CODE#/#OFFER_CODE#/",
            "offer_edit" => "#ELEMENT_CODE#/#OFFER_CODE#/#ORDER_ID#/",
            "pdf" => "#ELEMENT_CODE#/#OFFER_CODE#/#ORDER_ID#/pdf/",
        ];
        // массив будут заполнен переменными, которые будут найдены по маске шаблонов url
        $arVariables = [];
        $arVariableAliases = [];
        // объект для поиска шаблонов
        $engine = new CComponentEngine($this);

        // определение шаблона, какой файл подключать section.php, element.php, index.php
        $componentPage = $engine->guessComponentPath(
            // путь до корня секции
            $this->arParams["SEF_FOLDER"],
            // массив масок
            $arUrlTemplates,
            // путь до секции SECTION_CODE и элемента ELEMENT_CODE
            $arVariables
        );

        if (!$componentPage) {
            $componentPage = 'index';
        }

        // получаем значения переменных в $arVariables
        CComponentEngine::initComponentVariables(
        // файл который будет подключен section.php, element.php, index.php
            $componentPage,
            // массив имен переменных, которые компонент может получать из GET запроса
            $this->arComponentVariables,
            // массив псевдонимов переменных из GET запроса
            $arVariableAliases,
            // востановленные переменные
            $arVariables
        );
        // формируем arResult
        $this->arResult = [
            "VARIABLES" => $arVariables,
            "ALIASES" => $arVariableAliases
        ];

        return $componentPage;
    }

    protected function getAllStatements() {
        $statements_res = \Bitrix\Iblock\Elements\ElementStatementsTable::getList(
            [
                'select' => [
                    'ID', 'NAME', 'CODE',
                ],
                'cache' => [
                    'ttl' => 3600
                ],
            ]
        )->fetchAll();

        return $statements_res;
    }

    /**
     * @throws ObjectPropertyException
     * @throws SystemException
     * @throws ArgumentException
     */
    protected function getStatement(string $element_code): array|bool
    {
        $statement_res = \Bitrix\Iblock\Elements\ElementStatementsTable::getList(
            [
                'select' => [
                    'ID', 'NAME', 'DETAIL_TEXT', 'CODE', 'DEADLINE_' => 'DEADLINE',
                    'STEPS_TITLE_' => 'STEPS_TITLE', 'BTN_ORDER_TEXT_' => 'BTN_ORDER_TEXT',
                    'SERVICES_DO_AFTER_TITLE_' => 'SERVICES_DO_AFTER_TITLE',
                    'LINK_SERVICES_SIMILAR_' => 'LINK_SERVICES_SIMILAR', 'SERVICE_TYPE_' => 'SERVICE_TYPE.ITEM',
                    'LINK_SERVICES_DO_AFTER_' => 'LINK_SERVICES_DO_AFTER', 'BLOCK_ADVANTAGES_TITLE_' => 'BLOCK_ADVANTAGES_TITLE',
                    'BLOCK_ADVANTAGES_LIST_' => 'BLOCK_ADVANTAGES_LIST',
                    'STEP_1_TITLE_' => 'STEP_1_TITLE', 'STEP_1_TEXT_' => 'STEP_1_TEXT',
                    'STEP_2_TITLE_' => 'STEP_2_TITLE', 'STEP_3_TITLE_' => 'STEP_3_TITLE',
                    'STEP_4_TITLE_' => 'STEP_4_TITLE', 'STEP_4_TEXT_' => 'STEP_4_TEXT',
                ],
                'filter' => ['CODE' => $element_code],
                'cache' => [
                    'ttl' => 3600
                ],
            ]
        )->fetchAll();

        if (empty($statement_res)) {
            return FALSE;
        }

        $statement = [
            'ID' => $statement_res[0]['ID'],
            'CODE' => $statement_res[0]['CODE'],
            'NAME' => $statement_res[0]['NAME'],
            'DETAIL_TEXT' => $statement_res[0]['DETAIL_TEXT'],
            'DEADLINE_VALUE' => $statement_res[0]['DEADLINE_VALUE'],
            'STEPS_TITLE' => $statement_res[0]['STEPS_TITLE_VALUE'],
            'BTN_ORDER_TEXT' => $statement_res[0]['BTN_ORDER_TEXT_VALUE'],
            'SERVICE_TYPE' => $statement_res[0]['SERVICE_TYPE_XML_ID'],
            'SERVICES_DO_AFTER_TITLE' => $statement_res[0]['SERVICES_DO_AFTER_TITLE_VALUE'],
            'STEP_1_TITLE' => $statement_res[0]['STEP_1_TITLE_VALUE'],
            'STEP_1_TEXT' => unserialize($statement_res[0]['STEP_1_TEXT_VALUE'])['TEXT'],
            'STEP_2_TITLE' => $statement_res[0]['STEP_2_TITLE_VALUE'],
            'STEP_3_TITLE' => $statement_res[0]['STEP_3_TITLE_VALUE'],
            'STEP_4_TITLE' => $statement_res[0]['STEP_4_TITLE_VALUE'],
            'STEP_4_TEXT' => unserialize($statement_res[0]['STEP_4_TEXT_VALUE'])['TEXT'],
            'LINK_SERVICES_SIMILAR' => [],
            'LINK_SERVICES_DO_AFTER' => [],
            'ADVANTAGES_TITLE' => $statement_res[0]['BLOCK_ADVANTAGES_TITLE_VALUE'],
            'ADVANTAGES_LIST' => [],
        ];

        $linked_services_id = [];
        $linked_services_similar_id = [];
        $linked_services_do_after_id = [];
        foreach ($statement_res as $s) {
            $linked_services_id[] = $s['LINK_SERVICES_SIMILAR_IBLOCK_GENERIC_VALUE'];
            $linked_services_id[] = $s['LINK_SERVICES_DO_AFTER_IBLOCK_GENERIC_VALUE'];

            $linked_services_similar_id[] = $s['LINK_SERVICES_SIMILAR_IBLOCK_GENERIC_VALUE'];
            $linked_services_do_after_id[] = $s['LINK_SERVICES_DO_AFTER_IBLOCK_GENERIC_VALUE'];

            if ($s['BLOCK_ADVANTAGES_LIST_VALUE']) {
                $statement['ADVANTAGES_LIST'][$s['BLOCK_ADVANTAGES_LIST_ID']] = $s['BLOCK_ADVANTAGES_LIST_VALUE'];
            }
        }

        if (count($linked_services_id)) {
            $linked_services_res = \Bitrix\Iblock\Elements\ElementStatementsTable::getList(
                [
                    'select' => [
                        'ID', 'CODE', 'NAME', 'IBLOCK_ID', 'IBLOCK_SECTION_ID',
                        'DETAIL_PAGE_URL' => 'IBLOCK.DETAIL_PAGE_URL'
                    ],
                    'filter' => ['ID' => $linked_services_id],
                    'cache' => [
                        'ttl' => 3600
                    ],
                ]
            )->fetchAll();

            foreach ($linked_services_res as $ls) {
                if (in_array($ls['ID'], $linked_services_similar_id)) {
                    $statement['LINK_SERVICES_SIMILAR'][] = [
                        'NAME' => $ls['NAME'],
                        'URL' => CIBlock::ReplaceDetailUrl($ls['DETAIL_PAGE_URL'], $ls, false, 'E'),
                    ];
                } elseif (in_array($ls['ID'], $linked_services_do_after_id)) {
                    $statement['LINK_SERVICES_DO_AFTER'][] = [
                        'NAME' => $ls['NAME'],
                        'URL' => CIBlock::ReplaceDetailUrl($ls['DETAIL_PAGE_URL'], $ls, false, 'E'),
                    ];
                }
            }
        }


        $offers_res = \Bitrix\Iblock\Elements\ElementStatementsOffersTable::getList(
            [
                'select' => [
                    'ID', 'NAME', 'DETAIL_TEXT', 'CODE', "CML2_LINK_" => "CML2_LINK",
                    'CATEGORY_SHORT_NAME_' => 'CATEGORY_SHORT_NAME',
                    'LINK_GENERATED_DOCUMENTS_ID_' => 'LINK_GENERATED_DOCUMENTS_ID',
                    'LINK_FORM_ID_' => 'LINK_FORM_ID',
                ],
                'filter' => ['CML2_LINK_VALUE' => $statement['ID']],
                "order" => ["SORT" => "ASC"],
                'cache' => [
                    'ttl' => 3600
                ],
            ]
        )->fetchAll();

        $statement['OFFERS'] = [];

        $forms_id = [];
        $html_templates_id = [];

        $offers = [];
        foreach ($offers_res as $o) {
            $statement['OFFERS'][$o["ID"]]["ID"] = $o["ID"];
            $statement['OFFERS'][$o["ID"]]["NAME"] = $o["NAME"];
            $statement['OFFERS'][$o["ID"]]["CODE"] = $o["CODE"];
            $statement['OFFERS'][$o["ID"]]["CATEGORY_SHORT_NAME"] = $o["CATEGORY_SHORT_NAME_VALUE"];
            $statement['OFFERS'][$o["ID"]]["LINK_FORM_ID"] = intval($o["LINK_FORM_ID_VALUE"]);
            $statement['OFFERS'][$o["ID"]]["LINK_GENERATED_DOCUMENTS_ID"][] = intval($o["LINK_GENERATED_DOCUMENTS_ID_VALUE"]);

            $forms_id[] = intval($o["LINK_FORM_ID_VALUE"]);
            $html_templates_id[] = intval($o["LINK_GENERATED_DOCUMENTS_ID_VALUE"]);
        }

        $forms_id = array_filter($forms_id);
        $html_templates_id = array_filter($html_templates_id);

        // Забираем цены
        $dbPrice = \Bitrix\Catalog\Model\Price::getList([
            'filter' => [
                'PRODUCT_ID' => array_column($statement['OFFERS'], "ID"),
                'CATALOG_GROUP_ID' => 1
            ]
        ]);
        while ($p = $dbPrice->fetch()) {
            $statement['OFFERS'][$p["PRODUCT_ID"]]['PRICE'] = intval($p['PRICE']);
        }

        // Забираем формы
        if (count($forms_id)) {
            $forms_res = \Bitrix\Iblock\Elements\ElementFormsTable::getList(
                [
                    'select' => [
                        'ID', 'CODE', 'NAME', "DETAIL_TEXT", "PREVIEW_TEXT",
                    ],
                    'filter' => ['ID' => $forms_id],
                    'cache' => [
                        'ttl' => 3600
                    ],
                ]
            )->fetchAll();

            $forms = [];
            foreach ($forms_res as $f) {
                $forms[$f["ID"]] = [
                    "FORM_HTML" => $f["DETAIL_TEXT"],
                    "FORM_STRUCTURE" => unserialize($f["PREVIEW_TEXT"]),
                ];
            }

            foreach ($statement['OFFERS'] as &$o) {
                $o["FORM_HTML"] = $forms[$o["LINK_FORM_ID"]]["FORM_HTML"];
                $o["FORM_STRUCTURE"] = $forms[$o["LINK_FORM_ID"]]["FORM_STRUCTURE"];
            }
        }

        // Собираем шаблоны генерируемых документов
        if (count($html_templates_id)) {
            $docs_res = \Bitrix\Iblock\Elements\ElementDocumentsTemplatesTable::getList(
                [
                    'select' => [
                        'ID', 'CODE', 'NAME', "DETAIL_TEXT", "PREVIEW_TEXT",
                    ],
                    'filter' => ['ID' => $html_templates_id],
                    'cache' => [
                        'ttl' => 3600
                    ],
                ]
            )->fetchAll();

            $docs = [];
            foreach ($docs_res as $d) {
                $docs[$d["ID"]] = [
                    "ID" => $d["ID"],
                    "NAME" => $d["NAME"],
                    "HEADER" => $d["PREVIEW_TEXT"],
                    "BODY" => $d["DETAIL_TEXT"],
                ];
            }

            foreach ($statement['OFFERS'] as &$o) {
                foreach ($docs as $d) {
                    if (in_array($d["ID"], $o["LINK_GENERATED_DOCUMENTS_ID"])) {
                        $o["TEMPLATES_HTML"][] = $d;
                    }
                }
            }

            // Берём все свойства из PDF-шаблона, находя их по русским названиям
            //            $exists_props = [];
            //            preg_match_all(
            //                "/{{([А-яA-z\s]+?)}}/iu",
            //                $o["PROPERTY_TEMPLATE_PDF_HEADER_VALUE"]
            //                .$o["PROPERTY_TEMPLATE_PDF_BODY_VALUE"],
            //                $exists_props
            //            );
            //            $exists_props = $exists_props[1] ?? [];

        }

        return $statement;
    }

    /**
     * @throws ObjectPropertyException
     * @throws SystemException
     * @throws ArgumentException
     */
    protected function getOrder($order_id): array
    {
        global $USER, $APPLICATION;
        $order_id = intval($order_id);

        // Забираем ID заказа, который создавал неавторизованный пользователь, если он вообще есть.
        $localStorage = \Bitrix\Main\Application::getInstance()->getLocalSession('orders_session');
        $unauth_order_id = intval($localStorage->get('order_id'));

        if (!$USER->IsAuthorized() and $order_id !== $unauth_order_id) {
            throw new SystemException("Ошибка доступа к заказу. Необходима авторизация.");
        }

        $order = Order::load($order_id);
        if (!$order) {
            throw new SystemException("Не удалось загрузить заказ.");
        }

        $propertyCollection = $order->getPropertyCollection();
        $result_id = $propertyCollection->getItemByOrderPropertyCode('RESULT_ID')->getValue();

        $paySystemService = PaySystem\Manager::getObjectById(T_BANK_ID);
        $paymentCollection = $order->getPaymentCollection();
        $pay_form = "";
        if ($paymentCollection->count() and !$paymentCollection->isPaid() and !empty($paySystemService)) {

            $payment = $paymentCollection[0];

            $arPaySysAction = $paySystemService->getFieldsValues();

            $initResult = $paySystemService->initiatePay($payment, null, PaySystem\BaseServiceHandler::STRING);
            if ($initResult->isSuccess()) {
                $pay_form = $initResult->getTemplate();
            } else {
                throw new SystemException($initResult->getErrorMessages());
            }
        }

        $form_data_res = \Bitrix\Iblock\Iblock::wakeUp(IBID_RESULTS)->getEntityDataClass()::getList(
            [
                'select' => ["RESULT_" => "RESULT", "FILES_RESULT_" => "FILES_RESULT", "FILES_ARCHIVE_" => "FILES_ARCHIVE", ],
                'filter' => ['ID' => $result_id],
                'cache' => ['ttl' => 3600],
            ]
        )->fetch();

        $form_data = unserialize($form_data_res["RESULT_VALUE"]);

        return [
            'ID' => $order->getId(),
            'PAYED' => $order->getField('PAYED'),
            'STATUS_ID' => $order->getField('STATUS_ID'),
            'FORM_DATA' => $form_data,
            'PAY_FORM' => $pay_form,
            "FILES_RESULT" => intval($form_data_res["FILES_RESULT_VALUE"]),
            "FILES_ARCHIVE" => intval($form_data_res["FILES_ARCHIVE_VALUE"]),
        ];
    }

    public function setGuidAction($orders_session): array
    {
        $order_id = intval($orders_session['order_id']);
        $guid = $orders_session['order_guid'];

        if (!$this->checkModules()) {
            $this->errorCollection[] = new Error("Не найден нужный модуль");
            return [];
        }

        if ($order_id !== $this->getOrderByGuid($guid)) {
            $this->errorCollection[] = new Error('Ошибка доступа');
            return [];
        }

        $localStorage = \Bitrix\Main\Application::getInstance()->getLocalSession('orders_session');
        $localStorage->set('order_id', $order_id);


        return [$localStorage->get('order_id')];
    }

    public function cancelOrderRequestAction($order_id): array
    {
        global $USER;

        $request = Application::getInstance()->getContext()->getRequest();
        $post_data = $request->getPostList();
        $order_id = intval($post_data['order_id']);

        if (!$order_id) {
            $this->errorCollection[] = new Error('Некорректные параметры запроса.');
            return [];
        }

        $order = Order::load($order_id);

        if (!$USER->IsAuthorized() OR $USER->GetID() != $order->getUserId()) {
            $this->errorCollection[] = new Error('Ошибка доступа к заказу.');
            return [];
        }

        $order->setField('STATUS_ID', 'RR');
        $result = $order->save();

        $res_mail = CEvent::Send(
            'ORDER_RETURN_REQUEST',
            's1',
            [
                'ORDER_ID' => $order_id,
            ],
            $Duplicate = "N",
        );

        return [];
    }

    public function removeFileFromResultAction($file_id, $order_id, $file_name): array
    {
        global $USER;

        $request = Application::getInstance()->getContext()->getRequest();
        $post_data = $request->getPostList();
        $order_id = intval($post_data['order_id']);
        $del_file_id = intval($post_data['file_id']);
        $file_name = preg_match('/^[a-zA-Z0-9_]+$/', $post_data['file_name']) ? $post_data['file_name'] : FALSE;

        if (bitrix_sessid() !== $post_data['sessid']) {
            $this->errorCollection[] = new Error('Некорректная сессия.');
            return [];
        }

        if (!$order_id OR !$del_file_id OR !$file_name) {
            $this->errorCollection[] = new Error('Некорректные параметры запроса.');
            return [];
        }

        $order = Order::load($order_id);

        if ($USER->IsAuthorized()) {
            if ($USER->GetID() != $order->getUserId()) {
                $this->errorCollection[] = new Error('Ошибка доступа к заказу.');
                return [];
            }
        } else {
            // Забираем ID заказа, который создавал неавторизованный пользователь, если он вообще есть.
            $localStorage = \Bitrix\Main\Application::getInstance()->getLocalSession('orders_session');
            $unauth_order_id = intval($localStorage->get('order_id'));

            if ($order_id !== $unauth_order_id) {
                $this->errorCollection[] = new Error('Ошибка доступа при редактировании заказа неавторизованным'
                .'пользователем.');
                return [];
            }
        }

        $propertyCollection = $order->getPropertyCollection();
        $result_id = $propertyCollection->getItemByOrderPropertyCode('RESULT_ID')->getValue();

        $form_data_res = \Bitrix\Iblock\Iblock::wakeUp(IBID_RESULTS)->getEntityDataClass()::getList(
            [
                'select' => ["RESULT_" => "RESULT"],
                'filter' => ['ID' => $result_id],
                'cache' => ['ttl' => 3600],
            ]
        )->fetch();

        if (!$form_data_res) {
            $this->errorCollection[] = new Error('Не найден результат заказа.');
            return [];
        }


        $form_data = unserialize($form_data_res["RESULT_VALUE"]);
        if (!isset($form_data[$file_name])) {
            $this->errorCollection[] = new Error('Среди результатов нет таких файлов.');
            return [];
        }

        $new_files = [];
        if (($key = array_search($del_file_id, $form_data[$file_name])) !== false) {
            unset($form_data[$file_name][$key]);
        }

        $form_data_values = [
            "RESULT" => ["VALUE" => serialize($form_data)],
        ];

        // Обновляем свойство элемента
        CIBlockElement::SetPropertyValuesEx(
            $result_id,
            IBID_RESULTS,
            $form_data_values
        );

        CFile::Delete($file_id);

        return [
            "file_id" => $file_id,
            "order_id" => $order_id,
        ];
    }

    public function createFormResultAction(): array
    {
        $request = Application::getInstance()->getContext()->getRequest();
        $form_data_raw = $request->getPostList();

        if (!$this->checkModules()) {
            $this->errorCollection[] = new Error("Не найден нужный модуль.");
            return [];
        }

        if (bitrix_sessid() !== $form_data_raw['sessid']) {
            $this->errorCollection[] = new Error('Некорректная сессия.');
            return [];
        }
//        $_SERVER['HTTP_REFERER'] = "http://inargument.test/statement/vozrazhenie-na-sudebnyy-prikaz/vozrazhenie-na-sudebnyy-prikaz/182/";


        // TODO: Заменить _SERVER на д7, get http_referer (получить параметр)
        // Определяем по url страницы заявление с оферами и формами
        $uri_path = explode("/", parse_url($_SERVER['HTTP_REFERER'], PHP_URL_PATH));
        if ($this->arParams['SEF_FOLDER'] !== "/" . $uri_path[1] . "/") {
            $this->errorCollection[] = new Error('Невозможно определить URL.');
            return [];
        }

        $element_code = $uri_path[2];
        $statement = $this->getStatement($element_code);
        if (!$statement) {
            $this->errorCollection[] = new Error('Указана несуществующая форма.');
            return [];
        }

        $current_offer = FALSE;
        $offer_code = $uri_path[3];
        foreach ($statement['OFFERS'] as $o) {
            if ($o['CODE'] == $offer_code) {
                $current_offer = $o;
                break;
            }
        }
        if (!$current_offer) {
            $this->errorCollection[] = new Error('Указан несуществующий оффер.');
            return [];
        }

        $email_for_order = FALSE;
        $order_id = FALSE;
        $form_data = [];

//        addmessage2log($form_data_raw);
        // Проходим по массиву значений, пришедших от пользователя из формы
        foreach ($form_data_raw as $key_ans => $value) {

            // Забираем email для отправки
            if ('EMAIL_FOR_ORDER' == $key_ans) {
                $email_for_order = $value;
            } else if ('ORDER_ID' == $key_ans) { // Если это редактирование заказа, то должен быть его ID
                $order_id = intval($value);
            } else {
                $form_data["RESULT"][$key_ans] = $value;
            }
        }

        $files_arr = $request->getFileList()->toArray();
//        $files_key = array_keys($files)[0];
//        $files_count = 0;
//        if (is_array($files) and isset($files[$files_key])) {
//            $files_count = count($files[$files_key]['name']);
//        }

//        addmessage2log($files);

        $form_structure = getFormStructure($current_offer["FORM_HTML"]);
//        addmessage2log($form_structure);

        // Сохраняем файлы
        foreach ($files_arr as $f_name => $files_props) {
            $arF = [];

            $files_count = count($files_props['name']);

            for ($i = 0; $i < $files_count; $i++) {
                $arIMAGE["name"] = $files_props['name'][$i];
                $arIMAGE["size"] = $files_props['size'][$i];
                $arIMAGE["tmp_name"] = $files_props['tmp_name'][$i];
                $arIMAGE["type"] = $files_props['type'][$i];
                $arIMAGE["description"] = $form_structure[$f_name]["title"]." (файл ".($i + 1).")";
                $arIMAGE["MODULE_ID"] = "vote";
                $fid = CFile::SaveFile($arIMAGE, "vote");
                $arF[] = $fid;
            }
            // все ид файлов присваиваем свойству
            $form_data["RESULT"][$f_name]['VALUE'] = $arF;
        }

        // Создаём или определяем пользователя
        global $USER;

        // TODO: перевести на d7
        $user_id = 0;
        if ($USER->IsAuthorized()) {
            $user_id = $USER->GetID();
            $email_for_order = $USER->GetEmail();

            // Проверяем можем ли текущий пользователь редактировать заказ, если передаёт его ID
            if ($order_id) {
                $order = Order::load($order_id);
                if ($user_id != $order->getUserId()) {
                    $this->errorCollection[] = new Error('Ошибка доступа к заказу.');
                    return [];
                }
            }
        } else {
            // Если не авторизован и пытается отредактировать заказ
            if ($order_id) {
                // Забираем ID заказа, который создавал неавторизованный пользователь, если он вообще есть.
                $localStorage = \Bitrix\Main\Application::getInstance()->getLocalSession('orders_session');
                $unauth_order_id = intval($localStorage->get('order_id'));

                if ($order_id !== $unauth_order_id) {
                    $this->errorCollection[] = new Error('Ошибка доступа при редактировании заказа неавторизованным пользователем.');
                    return [];
                }
            }

            if (!$email_for_order) {
                $this->errorCollection[] = new Error('Укажите email для получения заказа.');
                return [];
            }

            // TODO: на d7
            // Проверяем, зарегистрирован ли такой пользователь
            $user_result = CUser::GetList("", "", ["=EMAIL" => $email_for_order]);
            if ($user = $user_result->Fetch()) {
                $user_id = $user['ID'];
            } else {
                // Иначе регистрируем и авторизуем
                $pass = Bitrix\Main\Security\Random::getString(MAX_PASSWORD_LENGTH, TRUE);
                $user_result = $USER->Register($email_for_order, "", "", $pass, $pass, $email_for_order);
                if ('ERROR' == $user_result['TYPE']) {
                    $this->errorCollection[] = new Error($user_result['MESSAGE']);
                    return [$form_data];
                }

                $user_id = $user_result['ID'];
            }
        }

        $form_data["EMAIL_FOR_ORDER"] = $email_for_order;

        $order_guid = "";
        if ($order_id) {
            $this->editOrder($order_id, $statement, $current_offer, $form_data);
        } else {
            $order = $this->createOrder($user_id, $statement, $current_offer, $form_data);
            if(!$order) {
                $this->errorCollection[] = new Error('Не удалось создать заказ');
                return [];
            }
            $order_id = $order['order_id'];
            $order_guid = $order['order_guid'];
        }

        // Если автоматическая услуга, то редиректим на шаг 3 оплата
        /*
        if ("auto" == $statement["SERVICE_TYPE"]) {
            $redirect_url = "/statement/" . $element_code . "/" . $offer_code . "/" . $order_id . "/";
        } else { // Если ручная услуга, то редиректим сразу на оплату
            $order = $this->getOrder($order_id);
            $pattern = '/<a\s+(?:[^>]*?\s+)?href=(["\'])(.*?)\1/';
            preg_match_all($pattern, $order['PAY_FORM'], $matches);
            $payment_url = $matches[2][0];
            $redirect_url = $payment_url;
        }*/

        $redirect_url = "/statement/" . $element_code . "/" . $offer_code . "/" . $order_id . "/";

//        $this->errorCollection[] = new Error($form_data);
//        return [];

        return [
            "redirect_url" => $redirect_url,
            "order_id" => $order_id, "order_guid" => $order_guid, "user_id" => $user_id,
        ];
    }

    private function createOrder($user_id, $statement, $current_offer, $form_data): array | bool
    {
        $form_data_values = [
            "EMAIL_FOR_ORDER" => ["VALUE" => $form_data["EMAIL_FOR_ORDER"]],
            "RESULT" => ["VALUE" => serialize($form_data["RESULT"])],
        ];

        $files_paths = [];
        // Создаём результат форм только для автоматических услуг
        if($statement['SERVICE_TYPE'] == "auto") {
            foreach ($current_offer["TEMPLATES_HTML"] as $d) {
                // TODO: Временный костыль, потом надо подумать, как убирать дубли для определённых списков.
                $is_arr_uniq = FALSE;
                if (
                    "Перечень коррупционно опасных функций" === $d["NAME"]
                    OR "Перечень должностей, замещение которых связано с коррупционными рисками" === $d["NAME"]
                ) {
                    $is_arr_uniq = TRUE;
                }

                $pdf_file_name = $this->createPDF($form_data, $d["HEADER"]."<div class='body'>".$d["BODY"]."</div>", $current_offer["FORM_STRUCTURE"]);
                $files_paths[$pdf_file_name] = $this->btr_tmp.$pdf_file_name;
                $file_arr = CFile::MakeFileArray($this->btr_tmp.$pdf_file_name);
                $form_data_values['FILES_RESULT'][] = $file_arr;
            }

            $zip = new ZipArchive;
            $zip_path = $_SERVER["DOCUMENT_ROOT"]."/bitrix/tmp/".$this->gen_guid().".zip";
            if ($zip->open($zip_path, ZipArchive::CREATE) === TRUE) {
                foreach ($files_paths as $f_name => $f_path) {
                    $zip->addFile($f_path, $f_name.".pdf");
                }
                $zip->close();

                $zip_arr = CFile::MakeFileArray($zip_path);
                $zip_filename = str_replace(' ', '_', date("Y-m-d") . '_' . $statement['NAME']) . '.zip';
                $zip_arr["name"] = $zip_filename;
                $form_data_values['FILES_ARCHIVE'] = $zip_arr;
            }
        }

        // Создаём результат формы
        $arFields = array(
            "ACTIVE" => "Y",
            "IBLOCK_ID" => IBID_RESULTS,
            "NAME" => "Заказ от " . $form_data["EMAIL_FOR_ORDER"]. " " . date("d.m.Y"),
            "PROPERTY_VALUES" => $form_data_values
        );
//        addmessage2log($arFields);
        $oElement = new CIBlockElement();
        $iblock_res_id = $oElement->Add($arFields);

        if($statement['SERVICE_TYPE'] == "auto") {
            foreach ($files_paths as $f_name => $f_path) {
                unlink($f_path);
            }
        }

        if (FALSE === $iblock_res_id) {
            $msg = "Не удалось создать заказ.";
            addmessage2log($msg);
            addmessage2log($arFields);
            return FALSE;
        }

        // Создаем заказ
        $siteId = Context::getCurrent()->getSite();
        $currencyCode = CurrencyManager::getBaseCurrency();

        $order = Order::create($siteId, $user_id);

        // Генерируем GUID для нового заявления
        $new_guid = $this->gen_guid();
        $propertyCollection = $order->getPropertyCollection();
        foreach ($propertyCollection as $popertyObj) {
            if ($popertyObj->getField('CODE') == "GUID") $popertyObj->setValue($new_guid);
            if ($popertyObj->getField('CODE') == "RESULT_ID") $popertyObj->setValue($iblock_res_id);
            if ($popertyObj->getField('CODE') == "EMAIL") $popertyObj->setValue($form_data_values['EMAIL_FOR_ORDER']);
        }

        $order->setPersonTypeId(1);
        $order->setField('CURRENCY', $currencyCode);

        $basket = Basket::create($siteId);
        $item = $basket->createItem('catalog', $current_offer['ID']);

        $item->setFields(array(
            'QUANTITY' => 1,
            'CURRENCY' => $currencyCode,
            'LID' => $siteId,
            'PRODUCT_PROVIDER_CLASS' => '\CCatalogProductProvider',
        ));
        $order->setBasket($basket);

        $shipmentCollection = $order->getShipmentCollection();
        $shipment = $shipmentCollection->createItem();
        $service = Delivery\Services\Manager::getById(Delivery\Services\EmptyDeliveryService::getEmptyDeliveryServiceId());
        $shipment->setFields(array(
            'DELIVERY_ID' => $service['ID'],
            'DELIVERY_NAME' => $service['NAME'],
        ));
        $shipmentItemCollection = $shipment->getShipmentItemCollection();
        $shipmentItem = $shipmentItemCollection->createItem($item);
        $shipmentItem->setQuantity($item->getQuantity());

        $paymentCollection = $order->getPaymentCollection();
        $payment = $paymentCollection->createItem();
        $paySystemService = PaySystem\Manager::getObjectById(T_BANK_ID);
        $payment->setFields(array(
            'PAY_SYSTEM_ID' => $paySystemService->getField("PAY_SYSTEM_ID"),
            'PAY_SYSTEM_NAME' => $paySystemService->getField("NAME"),
            "SUM" => $order->getPrice(),
            "CURRENCY" => $order->getCurrency(),
        ));

        if ($current_offer["PRICE"] === 0 ) {
            $payment->setPaid('Y');
        }

        // Сохраняем
        $order->doFinalAction(true);
        $result = $order->save();
        $order_id = $order->getId();

        // Сохраняем id заказа в сессию, которые может править текущий неавторизованный пользователь
        $localStorage = \Bitrix\Main\Application::getInstance()->getLocalSession('orders_session');
        $localStorage->set('order_id', $order_id);

        // Обновляем имя результату, чтобы учесть в названии ID заказа
        $result = $oElement->update($iblock_res_id, [
            'NAME' => "Заказ №".$order_id." от ". $form_data['EMAIL_FOR_ORDER'] . " " . date("d.m.Y"),
        ]);

        return ["order_id" => $order_id, "order_guid" => $new_guid];
    }

    protected function editOrder($order_id, $statement, $current_offer, $form_data): void
    {
        $order = Order::load($order_id);
        $basket = $order->getBasket();
        $offer_id = $basket->getBasketItems()[0]->getProductId();

        $propertyCollection = $order->getPropertyCollection();
        $result_id = $propertyCollection->getItemByOrderPropertyCode('RESULT_ID')->getValue();

        $form_data_values = [
            "RESULT" => ["VALUE" => serialize($form_data["RESULT"])],
        ];

//        addmessage2log($form_data);
        // Пересоздаём PDF-файлы и архив только для автоматических услуг
        if($statement['SERVICE_TYPE'] == "auto") {
            $files_paths = [];
            foreach ($current_offer["TEMPLATES_HTML"] as $d) {
                // TODO: Временный костыль, потом надо подумать, как убирать дубли для определённых списков.
                $is_arr_uniq = FALSE;
                if (
                    "Перечень коррупционно опасных функций" === $d["NAME"]
                    OR "Перечень должностей, замещение которых связано с коррупционными рисками" === $d["NAME"]
                ) {
                    $is_arr_uniq = TRUE;
                }

                $pdf_file_name = $this->createPDF($form_data, $d["HEADER"]."<div class='body'>".$d["BODY"]."</div>", $current_offer["FORM_STRUCTURE"], $is_arr_uniq);
                $files_paths[$d["NAME"]] = $this->btr_tmp.$pdf_file_name;
                $file_arr = CFile::MakeFileArray($this->btr_tmp.$pdf_file_name);
                $form_data_values['FILES_RESULT'][] = $file_arr;
            }

            $zip = new ZipArchive;
            $zip_path = $_SERVER["DOCUMENT_ROOT"]."/bitrix/tmp/".$this->gen_guid().".zip";
            if ($zip->open($zip_path, ZipArchive::CREATE) === TRUE) {
                foreach ($files_paths as $f_name => $f_path) {
                    $zip->addFile($f_path, $f_name.".pdf");
                }
                $zip->close();

                $zip_arr = CFile::MakeFileArray($zip_path);
                $zip_filename = str_replace(' ', '_', date("Y-m-d"). '_' . $statement['NAME']) . '.zip';
                $zip_arr["name"] = $zip_filename;
                $form_data_values['FILES_ARCHIVE'] = $zip_arr;
            }
        }

        //addmessage2log($prepare_data);
        CIBlockElement::SetPropertyValues($result_id, IBID_RESULTS, $form_data_values);
    }

    protected function createPDF($form_data, $html_template, $structure, $is_array_uniq): string
    {
        require $_SERVER["DOCUMENT_ROOT"].'/local/php_interface/lib/mpdf/vendor/autoload.php';

        $pdf_file_name = $this->gen_guid().'.pdf';

        //dump($pdf_file_name);

        $FIELDS = [];
        foreach ($form_data["RESULT"] as $k => $v) {
            $FIELDS[$k] = $v;
        }
        $FIELDS['Текущая дата'] = date("d.m.Y");

        preg_match_all(
            "/{{#([А-яA-z0-9\s]+?)}}/iu",
            $html_template,
            $exists_tables_tmp
        );

        $exists_tables_tmp = $exists_tables_tmp[1] ?? [];

        $all_mustache = [];
        $all_mustache_tr = [];
        foreach ($exists_tables_tmp as $et) {
            $all_mustache[] = "{{#".$et."}}";
            $all_mustache[] = "{{/".$et."}}";

            $all_mustache_tr[] = "{{#".Cutil::translit($et,"ru", ["replace_space"=>"_","replace_other"=>"_"])."}}";
            $all_mustache_tr[] = "{{/".Cutil::translit($et,"ru", ["replace_space"=>"_","replace_other"=>"_"])."}}";
        }

        $exists_props = [];
        preg_match_all(
            "/{{([А-яA-z0-9\s]+?)}}/iu",
            $html_template,
            $exists_props
        );
        $exists_props = $exists_props[1] ?? [];

        foreach ($exists_props as $ep) {
            $all_mustache[] = "{{".$ep."}}";
            $all_mustache_tr[] = "{{".Cutil::translit($ep,"ru", ["replace_space"=>"_","replace_other"=>"_"])."}}";
        }

//        addmessage2log($all_mustache);
//        addmessage2log($all_mustache_tr);
        $replaced_html = str_replace(
            $all_mustache,
            $all_mustache_tr,
            $html_template
        );

        foreach ($structure as $item) {
            if("table" === $item["type"]) {
                // Меняем строки и столбцы местами, чтобы Мусташе мог прожевать таблицу
                $table_tmp = [];
                foreach ($FIELDS[$item["name"]] as $k => $v2) {
                    foreach ($v2 as $k1 => $v1) {
                        $table_tmp[$k1][$k] = $v1;
                    }
                }
                $FIELDS[$item["name"]] = $table_tmp;
            }
        }

        // Заполнение только для карты коррупционных рисков, временный костыль
        if (isset($FIELDS["karta_korruptsionnykh_riskov"])) {
            foreach ($FIELDS["karta_korruptsionnykh_riskov"] as &$row) {
                $points = 0;
                if ("Низкий" == $row["vozmozhnyy_ushcherb"]) $points += 1;
                elseif ("Средний" == $row["vozmozhnyy_ushcherb"]) $points += 2;
                elseif ("Высокий" == $row["vozmozhnyy_ushcherb"]) $points += 3;

                if ("Низкая" == $row["veroyatnost_riska"]) $points += 1;
                elseif ("Средняя" == $row["veroyatnost_riska"]) $points += 2;
                elseif ("Высокая" == $row["veroyatnost_riska"]) $points += 3;

                if ($points <= 3) $row["avtozapolnenie"] = "Низкий";
                elseif($points == 4) $row["avtozapolnenie"] = "Средний";
                elseif($points >= 5) $row["avtozapolnenie"] = "Высокий";
            }
        }

        // TODO: Временный костыль, потом надо подумать, как убирать дубли для определённых списков.
        if ($is_array_uniq AND isset($FIELDS["karta_korruptsionnykh_riskov"]["korruptsionno_opasnaya_funktsiya"])) {
            $FIELDS["karta_korruptsionnykh_riskov"]["korruptsionno_opasnaya_funktsiya"] = array_filter($FIELDS["karta_korruptsionnykh_riskov"]["korruptsionno_opasnaya_funktsiya"]);
        } else if ($is_array_uniq AND isset($FIELDS["karta_korruptsionnykh_riskov"]["klyuchevaya_dolzhnost"])) {
            $FIELDS["karta_korruptsionnykh_riskov"]["klyuchevaya_dolzhnost"] = array_filter($FIELDS["karta_korruptsionnykh_riskov"]["klyuchevaya_dolzhnost"]);
        }

//        addmessage2log($all_mustache);
//        addmessage2log($all_mustache_tr);
//        addmessage2log($FIELDS);
        $m = new Mustache_Engine(['entity_flags' => ENT_QUOTES]);

        ob_start();
        echo '<div class="pdf_file">'.$m->render($replaced_html, $FIELDS).'</div>';

        $filled_html_template = ob_get_contents();
        ob_end_clean();

//        addmessage2log($filled_html_template);

        // Подключаем стили
        ob_start();
        require_once(TEMPLATE_PDF_STYLE_PATH);
        $stylesheet = ob_get_contents();
        ob_end_clean();

        $mpdf = new \Mpdf\Mpdf();
        $mpdf->charset_in = 'utf-8';
        $mpdf->SetDisplayMode('fullpage');
        $mpdf->WriteHTML($stylesheet,1);
        $mpdf->WriteHTML($filled_html_template);
        $mpdf->Output($this->btr_tmp.$pdf_file_name, 'F');

//        addmessage2log($pdf_file_name);

        return $pdf_file_name;
    }

    protected function getOrderByGuid($order_guid): int | bool
    {
        $parameters = [
            'select' => [
                "ID",
            ],
            'filter' => [
                '=PROPERTY_VAL.CODE' => 'GUID',
                '=PROPERTY_VAL.VALUE' => $order_guid,
            ],
            'runtime' => [
                new \Bitrix\Main\Entity\ReferenceField(
                    'PROPERTY_VAL',
                    '\Bitrix\sale\Internals\OrderPropsValueTable',
                    ["=this.ID" => "ref.ORDER_ID"],
                    ["join_type"=>"left"]
                ),
            ]
        ];
        $dbRes = \Bitrix\Sale\Order::getList($parameters);
        if ($order = $dbRes->fetch())
        {
            return $order['ID'];
        }

        return FALSE;
    }

    public function getSuggestAction($input, $types='street,district,locality,area,province,country,house')
    {
        $ya_js_key = "d17c96b4-08e3-414b-a7e0-d874fbf1c8a5";
        $apiToken = "3fe4421f-7abd-467e-803c-b0b7a7d1cef7";
        //$apiToken = ModuleOption::get(self::$moduleName, 'API_TOKEN_GEOSUGGEST');
        $data = [
            'apikey'=>$apiToken,
            'lang'=>'ru',
            'results'=>'5',
            'print_address'=>1,
            'types'=>$types,
            'text'=>$input,
        ];

        $request = $this->suggestURL.'?'.http_build_query($data);
        $httpClient = new HttpClient();
        $response = json_decode($httpClient->get($request));

        return $response;
    }

    /**
     * Getting array of errors.
     * @return Error[]
     */
    public function getErrors()
    {
        return $this->errorCollection->toArray();
    }
    /**
     * Getting once error with the necessary code.
     * @param string $code Code of error.
     * @return Error
     */
    public function getErrorByCode($code)
    {
        return $this->errorCollection->getErrorByCode($code);
    }
}
