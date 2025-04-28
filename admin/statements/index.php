<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
?>


<?
if(!$USER->isAdmin()) {
    LocalRedirect("/404.php");
}

$statements = \Bitrix\Iblock\Elements\ElementStatementsTable::getList(
    [
        'select' => [
            'ID', 'NAME', 'DETAIL_TEXT', 'CODE',
        ],
        'filter' => ['ACTIVE' => "Y"],
    ]
)->fetchAll();

$statements_offers = \Bitrix\Iblock\Elements\ElementStatementsOffersTable::getList(
    [
        'select' => [
            'ID', 'NAME', 'DETAIL_TEXT', 'CODE', "CML2_LINK_" => "CML2_LINK"
        ],
        'filter' => [
            'ACTIVE' => "Y",
            "CML2_LINK_VALUE" => array_column($statements, "ID"),
        ],
        'order' => ["SORT" => "ASC"],
    ]
)->fetchAll();

$statements_offers_groups = [];
foreach ($statements_offers as $so) {
    $statements_offers_groups[$so["CML2_LINK_IBLOCK_GENERIC_VALUE"]][] = $so;
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
                <h1>Список заявлений</h1>
            </div>

            <?
            foreach ($statements as $s) {?>
                <div class="statements-row">
                    <div class="statement-name">
                        <?=$s["NAME"]?>
                    </div>
                    <ul>
                        <?
                        foreach ($statements_offers_groups[$s["ID"]] as $so) {?>
                            <li><a href="/admin/statements/statement_edit.php?ID=<?=$so["ID"]?>"><?=$so["NAME"]?></a></li>
                        <?
                        }
                        ?>
                    </ul>
                </div>
            <?
            }
            ?>
        </div>
    </section>
</div>




<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>
