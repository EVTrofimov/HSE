<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

use \Bitrix\Main\Page\Asset;

global $APPLICATION, $USER;



$APPLICATION->AddChainItem("Редактирование заявления");

Asset::getInstance()->addJs("/admin/forms/form_edit.js");

if(!$USER->isAdmin()) {
    LocalRedirect("/404.php");
}

$form_id = intval($_GET["ID"]);

if (!$form_id) {
   echo "Такой формы не найдено";
   return;
}

$form = \Bitrix\Iblock\Elements\ElementformsTable::getList(
    [
        'select' => [
            'ID', 'NAME', 'DETAIL_TEXT', 'CODE',
        ],
        'filter' => [
            'ACTIVE' => "Y",
            "ID" => $form_id
        ],
    ]
)->fetch();

if (!$form) {
    echo "Такой формы не найдено";
    return;
}

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
                <h1>Редактирование веб-формы &mdash; <?=$form["NAME"]?></h1>
            </div>

<!--                <div class="tabs">-->
<!--                    <a class="tab" href="/admin/statements/form_edit.php?ID=--><?php //=$offer_id?><!--">Веб-форма</a>-->
<!--                    <a class="tab" href="/admin/statements/pdf_edit.php?ID=--><?php //=$offer_id?><!--">PDF файл</a>-->
<!--                </div>-->
            <div class="form-section">
                <label class="form-item-title" for="form-name">Название формы</label>
                <input id="form-name" name="form-name" type="text" class="form__item" placeholder="Название формы" value="<?=$form["NAME"]?>">
            </div>

            <div class="form-section">
                <p class="form-section-title">Структура веб-формы</p>
            </div>
            <div id="form-edit" class="form-constructor" data-id="<?=$form["ID"]?>">
                <?
                $form_html = $form["DETAIL_TEXT"];
                echo $form_html;
                ?>
            </div>


            <div class="center" style="margin-bottom: 20px;">
                <button class="section-add">Добавить раздел</button>
            </div>

            <?
            $form_structure = $form_html ? getFormStructure($form_html) : "";
//            dump($form_structure);
            ?>

            <div class="enter_box__btns process_btns">
                <div class="left">

                </div>
                <div class="right">
                    <button type="button" class="btn form-create">Сохранить</button>
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

            <div class="form-section">
                <label>
                    <select name="form-item-type" class="form__item">
                        <option value="text" selected>Текст</option>
                        <option value="select">Выпадающий список (справочник)</option>
                        <option value="textarea">Длинный текст</option>
                        <option value="file">Файлы</option>
                        <option value="table">Таблица</option>
                    </select>
                </label>

                <label>
                    <input name="form-item-title" type="text" class="form__item" placeholder="Подпись элемента (выводится для клиента)">
                </label>
                <label>
                    <input name="form-item-name" type="text" class="form__item" placeholder="Сокр. название элемента до 100 символов.">
                </label>

                <label id="label-form-item-select-source">
                    <input name="form-item-select-source" type="text" class="form__item" placeholder="Название справочника" style="display:none;">
                </label>
            </div>

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

<div class="popup_wrapper">
    <div class="popup" id="modal-table-item-add">
        <div class="close-modal"><div class="round_link"><img src="/local/templates/inargument/img/cross.svg" alt="x"></div></div>
        <div class="popup_inner">
            <p class="text_bold">Добавить столбец таблицы</p>

            <div class="buttons_block">
                <div class="left">
                    <a href="#" class="btn table-item-add">Добавить</a>
                </div>
                <div class="right">
                    <a href="#close-modal" class="btn" rel="modal:close">Отменить</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    let SITE_TEMPLATE_PATH = '<?= SITE_TEMPLATE_PATH ?>';
</script>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>

