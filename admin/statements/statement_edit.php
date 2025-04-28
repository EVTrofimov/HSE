<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

use \Bitrix\Main\Page\Asset;

$APPLICATION->AddChainItem("Редактирование заявления");

Asset::getInstance()->addJs("/admin/statements/statement_edit.js");
?>


<?
if(!$USER->isAdmin()) {
    LocalRedirect("/404.php");
}

$offer_id = intval($_GET["ID"]);

$statement = \Bitrix\Iblock\Elements\ElementStatementsOffersTable::getList(
    [
        "select" => [
            "ID", "NAME", "CODE", "CML2_LINK_" => "CML2_LINK",
        ],
        "filter" => [
            "ACTIVE" => "Y",
            "ID" => $offer_id
        ],
    ]
)->fetch();
?>

<style>
    #template_pdf_header {
        width: 100%;
    }
</style>

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
                <h1>Редактирование PDF шаблона &mdash; <?=$statement["NAME"]?></h1>
            </div>

            <div class="form-section">
                <label class="form-item-title" for="statement-name">Название заявления</label>
                <input id="statement-name" name="statement-name" type="text" class="form__item" placeholder="Название формы" value="<?=$statement["NAME"]?>">
            </div>

<!--            <div id="statement-edit" class="form-constructor" method="post" enctype="multipart/form-data" data-id="--><?php //=$statement["ID"]?><!--">-->
<!--                <section class="form-section">-->
<!--                    <textarea id="template_pdf_header" class="form__item">--><?php //=$statement["TEMPLATE_PDF_HEADER_VALUE"];?><!--</textarea>-->
<!--                </section>-->
<!---->
<!--                <section class="form-section">-->
<!--                    <textarea id="template_pdf_body" class="form__item">--><?php //=$statement["TEMPLATE_PDF_BODY_VALUE"];?><!--</textarea>-->
<!--                </section>-->
<!--            </div>-->

            <div class="enter_box__btns">
                <div class="left">
                    <a href="/admin/statements/statement_pdf_preview.php?ID=<?=$statement["ID"]?>" target="_blank" class="btn">Предпросмотр</a>
                </div>
                <div class="right">
                    <button type="button" class="btn form-save">Сохранить</button>
                </div>
            </div>

        </div>
    </section>
</div>

<div class="popup_wrapper">
    <div class="popup" id="modal-form-item-add">
        <div class="close-modal"><div class="round_link"><img src="/local/templates/inargument/img/cross.svg" alt="x"></div></div>
        <div class="popup_inner">
            <p class="text_bold">Добавить элемент</p>

            <label>
                <input name="form-item-name" placeholder="Название элемента">
            </label>

            <label>
                <select name="form-item-type">
                    <option value="text" selected>Текст</option>
                    <option value="textarea">Длинный текст</option>
                    <option value="file">Файлы</option>
                </select>
            </label>

            <div class="buttons_block">
                <div class="left">
                    <a href="#" class="btn item-add">Добавить</a>
                </div>
                <div class="right">
                    <a href="#close-modal" class="btn" rel="modal:close">Отменить</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>

