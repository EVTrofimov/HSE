<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use \Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);
?>

<div class="page__inner">
    <section class="page__item">
        <div class="white_container">
            <div class="white_container__title">
                <?$APPLICATION->IncludeComponent(
                    "bitrix:breadcrumb",
                    "breadcrumb",
                    array(
                        "PATH" => "",
                        "SITE_ID" => SITE_ID,
                        "START_FROM" => "0",
                        "COMPONENT_TEMPLATE" => ""
                    ),
                    false
                ); ?>
                <h1><?=$arResult['NAME']?></h1>
            </div>

            <div class="about_text">
                <?
                echo htmlspecialcharsback($arResult['DETAIL_TEXT']);
                ?>
            </div>

            <?
            if(count($arResult['OFFERS']) > 1) {
            ?>
            <div class="serv_cat">
                <p class="semibold">Выберите категорию:</p>
                <div class="catalog__box">
                    <?
                    foreach ($arResult['OFFERS'] as $o) {
                        // Исключаем общую форму, её выводим через кнопку ниже
                        if ($arResult['CODE'] == $o['CODE']) {
                            continue;
                        }

                        ?>
                        <a href="<?=$arParams['SEF_FOLDER'].$arResult['CODE']."/".$o['CODE']?>/" class="catalog__box_item">
                            <p><?=$o['CATEGORY_SHORT_NAME']?></p>
                            <span class="icon-02"></span>
                        </a>
                    <?}?>
                </div>
            </div>
            <?}
            ?>

            <div class="action_box">
                <div class="left">
                    <div class="price">
                        <div class="price_detail">
                            <p class="detail_info">Стоимость услуги:</p>
                            <p class="price_blue">
                                <?=
                                $arResult['OFFERS'][array_key_first($arResult['OFFERS'])]['PRICE'] === 0 ?
                                    "Бесплатно" :
                                    CurrencyFormat($arResult['OFFERS'][array_key_first($arResult['OFFERS'])]['PRICE'], "RUB")
                                ?>
                            </p>
                        </div>
                        <div class="price_detail">
                            <p class="detail_info">Срок предоставления:</p>
                            <p class="price_blue"><?=$arResult['DEADLINE_VALUE']?></p>
                        </div>
                    </div>
                </div>
                <div class="right">
                    <a class="btn" href="<?=$arParams['SEF_FOLDER'].$arResult['CODE']."/".$arResult['CODE']."/"?>">
                        <?=$arResult['BTN_ORDER_TEXT']?>
                    </a>
                </div>
            </div>

            <?
            if ($arResult['ADVANTAGES_LIST']) {
            ?>
                <div class="descr_box">
                    <h3><?=$arResult['ADVANTAGES_TITLE']?></h3>

                    <div class="tile">
                        <?foreach ($arResult['ADVANTAGES_LIST'] as $al) {?>
                            <div class="tile__item">
                                <img src="<?=SITE_TEMPLATE_PATH?>/img/check.svg" alt="">
                                <p><?=$al?></p>
                            </div>
                        <?}?>
                    </div>
                </div>
            <?}?>

            <?
            if ($arResult['LINK_SERVICES_DO_AFTER']) {
            ?>
                <div class="descr_box">
                    <h3><?=$arResult['SERVICES_DO_AFTER_TITLE']?></h3>
                    <div class="serv_tile">
                        <?foreach ($arResult['LINK_SERVICES_DO_AFTER'] as $ls) {?>
                            <a href="<?=$ls['URL']?>" class="serv_tile__item">
                                <p><?=$ls['NAME']?></p>
                                <span class="icon-02"></span>
                            </a>
                        <?}?>
                    </div>
                </div>
            <?}?>
        </div>
    </section>

    <?
    if ($arResult['LINK_SERVICES_SIMILAR']) {
    ?>
    <section class="page__item">
        <div class="page__item_title">
            <h2 class="min_h2">Похожие услуги</h2>
        </div>
        <div class="page__item_body">
            <div class="catalog__box">
                <?foreach ($arResult['LINK_SERVICES_SIMILAR'] as $ls) {?>
                    <a href="<?=$ls['URL']?>" class="catalog__box_item"><p><?=$ls['NAME']?></p><span class="icon-02"></span></a>
                <?}?>
            </div>
        </div>
    </section>
    <?}?>
</div>
