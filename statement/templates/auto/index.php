<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */

$APPLICATION->SetTitle("Перечень юридических услуг");
?>

<div class="page__hero">
    <div class="page__inner">
        <section class="page__item">
            <div class="hero_block hero_subpage">
                <div class="bc">
                    <ul class="bc_list">
                        <li><a href="/">Главная</a></li>
                        <span>/</span>
                        <li><a href="javascript:void();"><?$APPLICATION->ShowTitle();?></a></li>
                    </ul>
                </div>
                <div class="hero_subpage__title">
                    <h1><?$APPLICATION->ShowTitle();?></h1>
                </div>
            </div>
        </section>
    </div>
</div>

<div class="page__inner catalog_inner">
    <section class="page__item">
        <div class="page__item_body">
            <div class="catalog__box">
                <?
                foreach ($arResult['ALL_STATEMENTS'] as $s) {?>
                    <a href="/statement/<?= $s['CODE'] ?>/" class="catalog__box_item"><p><?=$s['NAME']?></p><span class="icon-02"></span></a>
                <?}?>
            </div>
        </div>
    </section>
</div>

