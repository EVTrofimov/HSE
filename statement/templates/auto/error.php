<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */
$this->setFrameMode(true);?>

<div class="page__inner">
    <section class="page__item">
        <div class="white_container">
            <h3 class="invalid"><?=$arResult["ERROR_TEXT"]?></h3>

            <p>Попробуйте вернуться на <a href="/">Главную страницу</a> и повторить действий. Если ошибка будет
                повторяться, то свяжитесь с технической поддержкой сайта.</p>
        </div>
    </section>
</div>