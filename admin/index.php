<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
?>

<?
if(!$USER->isAdmin()) {
    LocalRedirect("/404.php");
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
                <h1>Админка</h1>
            </div>

            <div>
                <a href="/admin/statements/">Список заявлений</a>
            </div>
            <div>
                <a href="/admin/forms/">Список форм</a>
            </div>

        </div>
    </section>
</div>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>
