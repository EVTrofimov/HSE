<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
?>

<?
if(!$USER->isAdmin()) {
    LocalRedirect("/404.php");
}
use \Bitrix\Main\Page\Asset;
Asset::getInstance()->addJs("/admin/forms/forms.js");

$forms = \Bitrix\Iblock\Elements\ElementformsTable::getList(
    [
        'select' => [
            'ID', 'NAME', 'DETAIL_TEXT', 'CODE',
        ],
        'filter' => ['ACTIVE' => "Y"],
    ]
)->fetchAll();

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
                <h1>Список форм</h1>
            </div>

            <?
            foreach ($forms as $f) {?>
                <div class="statements-row">
                    <div class="statement-name">
                        <a href="/admin/forms/form_edit.php?ID=<?=$f["ID"]?>"><?=$f["NAME"]?></a>
                    </div>
                </div>
                <?
            }
            ?>

            <div class="buttons_block" style="margin-top: 50px">
                <button class="show-modal-form-add">Добавить</button>
            </div>
        </div>
    </section>
</div>

<div class="popup_wrapper">
    <div class="popup" id="modal-form-add">
        <div class="close-modal"><div class="round_link"><img src="/local/templates/inargument/img/cross.svg" alt="x"></div></div>
        <div class="popup_inner">
            <p class="text_bold">Добавить форму</p>

            <label>
                <p class="form-title">Название формы</p>
                <input id="form-name" name="form-name" type="text" class="form__item" placeholder="Название формы">
            </label>

            <div class="buttons_block">
                <div class="left">
                    <a href="#" class="btn form-add">Добавить</a>
                </div>
                <div class="right">
                    <a href="#close-modal" class="btn" rel="modal:close">Отменить</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>
