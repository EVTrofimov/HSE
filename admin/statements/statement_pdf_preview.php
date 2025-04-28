<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
?>

<?
if(!$USER->isAdmin()) {
    LocalRedirect("/404.php");
}
$s_id = intval($_GET['ID']);
use Bitrix\Main\Loader;

Loader::includeModule('catalog');
Loader::includeModule('iblock');
Loader::includeModule('sale');

$s = \Bitrix\Iblock\Elements\ElementStatementsOffersTable::getList(
    [
        'select' => [
            'ID', 'NAME', 'DETAIL_TEXT', 'CODE', "LINK_FORM_ID_" => "LINK_FORM_ID"
        ],
        'filter' => ['ID' => $s_id],
    ]
)->fetch();


$f = \Bitrix\Iblock\Elements\ElementFormsTable::getList(
    [
        'select' => [
            'ID', 'NAME', 'PREVIEW_TEXT', 'DETAIL_TEXT', 'CODE',
        ],
        'filter' => ['ID' => $s["LINK_FORM_ID_VALUE"]],
    ]
)->fetch();

$structure = unserialize($f["PREVIEW_TEXT"]);

$r = \Bitrix\Iblock\Elements\ElementResultsTable::getList(
    [
        'select' => [
            'ID', 'NAME', 'RESULT_' => "RESULT",
        ],
        'filter' => ['ID' => 804],
    ]
)->fetch();

$d = \Bitrix\Iblock\Elements\ElementDocumentsTemplatesTable::getList(
    [
        'select' => [
            'ID', 'NAME', 'PREVIEW_TEXT', 'DETAIL_TEXT',
        ],
        'filter' => ['ID' => 772],
    ]
)->fetch();

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
                <h1>Предварительный просмотр документа &mdash; <?=$form["NAME"]?></h1>
            </div>

            <div class="preview">
                <style>
                    <?require_once(TEMPLATE_PDF_STYLE_PATH);?>
                </style>

                <div class="pdf_file">
                    <?
                    $val = unserialize($r["RESULT_VALUE"]);
                    $full_html = $d["PREVIEW_TEXT"].$d["DETAIL_TEXT"];

                    preg_match_all(
                        "/{{#([А-яA-z\s]+?)}}/iu",
                        $full_html,
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
                        "/{{([А-яA-z\s]+?)}}/iu",
                        $full_html,
                        $exists_props
                    );
                    $exists_props = $exists_props[1] ?? [];

                    foreach ($exists_props as $ep) {
                        $all_mustache[] = "{{".$ep."}}";
                        $all_mustache_tr[] = "{{".Cutil::translit($ep,"ru", ["replace_space"=>"_","replace_other"=>"_"])."}}";
                    }


//                    dump($all_mustache);
//                    dump($all_mustache_tr);
                    $replaced_html = str_replace(
                        $all_mustache,
                        $all_mustache_tr,
                        $full_html
                    );
                    foreach ($structure as $item) {
                        if("table" === $item["type"]) {
                            // Меняем строки и столбцы местами, чтобы Мусташе мог прожевать таблицу
                            $table_tmp = [];
                            foreach ($val[$item["name"]] as $k => $v2) {
                                foreach ($v2 as $k1 => $v1) {
                                    $table_tmp[$k1][$k] = $v1;
                                }
                            }
                            $val[$item["name"]] = $table_tmp;
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

                    $m = new Mustache_Engine(['entity_flags' => ENT_QUOTES]);
                    $rendered_html = $m->render($replaced_html, $val);

                    $dom = new DOMDocument;
                    $rendered_html = mb_convert_encoding($rendered_html, 'HTML-ENTITIES', "UTF-8");
                    echo $rendered_html;
                    ?>
                </div>
            </div>
        </div>
    </section>
</div>


<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>
