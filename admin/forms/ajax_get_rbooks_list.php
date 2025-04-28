<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Web\Json,
    Bitrix\Main\Loader;

if(!$USER->isAdmin()) {
    LocalRedirect("/404.php");
}

$search_str = $_POST["search"] ?? "";


$rbooks_list = \Bitrix\Iblock\Elements\ElementReferenceBooksTable::getList(
    [
        'select' => [
            'ID',
            'NAME',
        ],
        'filter' => ['NAME' => "%".$search_str."%"],
        'cache' => [
            'ttl' => 3600
        ],
    ]
)->fetchAll();
if (!$rbooks_list) {
    echo Json::encode([]);
    return;
}

$list = [];
foreach ($rbooks_list as $element) {
    $list[] = [
        "label" => $element["NAME"],
        "value" => $element["NAME"],
        "rbook_id" => $element["ID"],
    ];
}

echo Json::encode($list);

return;