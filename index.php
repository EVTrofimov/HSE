<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
//$APPLICATION->SetPageProperty("class_page_container", "page-subpage case-page");

$main_page = \Bitrix\Iblock\Elements\ElementPagesSettingsTable::getList(
    [
        'select' => [
            'ID', 'NAME', 'CODE',
            'MAIN_BLOCK_TEXT_1_' => 'MAIN_BLOCK_TEXT_1',
            'MAIN_BLOCK_TEXT_2_' => 'MAIN_BLOCK_TEXT_2'
        ],
        'filter' => ['ACTIVE' => 'Y'],
        'cache' => [
            'ttl' => 3600
        ],
    ]
)->fetch();

?>

<div class="page__hero">
    <div class="page__inner">
        <section class="page__item">
            <div class="hero_block">
                <div class="left">
                    <img src="<?=SITE_TEMPLATE_PATH?>/img/main.svg" alt="">
                </div>
                <div class="right">
                    <div class="h1_container">
                        <h1>Юридические услуги</h1>
                    </div>
                    <div class="search_container">
                        <?$APPLICATION->IncludeComponent(
                                "bitrix:search.form",
                            "mainpage_search",
                            [
                                "USE_SUGGEST" => "N",
                                "PAGE" => "#SITE_DIR#search/"
                            ]
                        );?>
                        <div class="search_container__services">
                            <?
                            // TODO: Здесь выводим только 2 элемента?
                            foreach ($common_info->getLinkSearchServices() as $s) {
//                                if ($i > 1) {
//                                    break;
//                                }
                                $id = $s->getValue();
                                ?>
                                <a href="/statement/<?= $statements[$id]['CODE'] ?>/" class="search_container__services_item"><p><?= $statements[$id]['NAME'] ?></p>
                                    <img src="<?=SITE_TEMPLATE_PATH?>/img/arr.svg" alt=""></a>
                            <?}?>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

<div class="page__about">
    <div class="page__inner">
        <section class="page__item">
            <div class="page__item_title">
                <h2>Юридические сервисы</h2>
                <a href="/about/" class="link">Узнать больше <img src="<?=SITE_TEMPLATE_PATH?>/img/arb.svg" alt=""></a>
            </div>
            <div class="page__item_body">
                <div class="about_text">
                    <div class="about_text__left">
                        <?=$main_page['MAIN_BLOCK_TEXT_1_VALUE']?>
                    </div>
                    <div class="about_text__right">
                        <?=$main_page['MAIN_BLOCK_TEXT_2_VALUE']?>
                    </div>
                </div>
                <div class="link_mobile">
                    <a href="/about/" class="btn btn_simple">Узнать больше</a>
                </div>
            </div>
        </section>
    </div>
</div>

<div class="page__inner">
    <?
    if($USER->IsAuthorized()) {
    ?>
        <section class="page__item">
            <div class="page__item_title">
                <h2>Мои дела</h2>
                <a href="/personal/" class="link">Перейти ко всем <img src="<?=SITE_TEMPLATE_PATH?>/img/arb.svg" alt=""></a>
            </div>
            <?
            $_REQUEST['show_all'] = "Y";
            $APPLICATION->IncludeComponent(
                "bitrix:sale.personal.order.list",
                "personal", Array(
                "STATUS_COLOR_N" => "green",	// Цвет статуса "Принят, ожидается оплата"
                "STATUS_COLOR_P" => "yellow",
                "STATUS_COLOR_F" => "gray",	// Цвет статуса "Выполнен"
                "STATUS_COLOR_PSEUDO_CANCELLED" => "red",	// Цвет отменённых заказов
                "PATH_TO_DETAIL" => "order_detail.php?ID=#ID#",	// Страница c подробной информацией о заказе
                "PATH_TO_COPY" => "basket.php",	// Страница повторения заказа
                "PATH_TO_CANCEL" => "order_cancel.php?ID=#ID#",	// Страница отмены заказа
                "PATH_TO_BASKET" => "basket.php",	// Страница корзины
                "PATH_TO_PAYMENT" => "payment.php",	// Страница подключения платежной системы
                "ORDERS_PER_PAGE" => "20",	// Количество заказов, выводимых на страницу
                "ID" => "",	// Идентификатор заказа
                "SET_TITLE" => "N",	// Устанавливать заголовок страницы
                "SAVE_IN_SESSION" => "Y",	// Сохранять установки фильтра в сессии пользователя
                "NAV_TEMPLATE" => "",	// Имя шаблона для постраничной навигации
                "CACHE_TYPE" => "A",	// Тип кеширования
                "CACHE_TIME" => "3600",	// Время кеширования (сек.)
                "CACHE_GROUPS" => "Y",	// Учитывать права доступа
                "HISTORIC_STATUSES" => "F",	// Перенести в историю заказы в статусах
                "ACTIVE_DATE_FORMAT" => "d.m.Y",	// Формат показа даты,
                "DEFAULT_SORT" => "DATE_INSERT"
            ),
                false
            );?>
        </section>
    <?
    }
    ?>

    <section class="page__item">
        <div class="page__item_title">
            <h2>Перечень юридических услуг</h2>
            <a href="/statement/" class="link">Перейти ко всем <img src="<?=SITE_TEMPLATE_PATH?>/img/arb.svg" alt=""></a>
        </div>
        <div class="page__item_body">
            <div class="catalog__box">
                <?
                foreach ($common_info->getLinkMainServices() as $s) {
                    $id = $s->getValue();?>
                    <a href="/statement/<?= $statements[$id]['CODE'] ?>/" class="catalog__box_item">
                        <p><?=$statements[$id]['NAME']?></p><span class="icon-02"></span>
                    </a>
                <?}?>
            </div>
            <div class="link_mobile">
                <a href="/statement/" class="btn btn_simple">Перейти ко всем</a>
            </div>
        </div>
    </section>
</div>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>

