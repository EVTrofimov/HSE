<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Web\Json,
    Bitrix\Main\Loader;

if(!$USER->isAdmin()) {
    LocalRedirect("/404.php");
}

Loader::includeModule('iblock');

$rbook_id = intval($_POST["rbook_id"]) ?? FALSE;

if (!$rbook_id) {
    echo Json::encode([
        "status" => "error",
        "text" => "Некорректные входные данные",
    ]);
    return;
}

$rbooks = \Bitrix\Iblock\Elements\ElementReferenceBooksTable::getList(
    [
        'select' => [
            'ID', 'CODE', 'NAME', "LIST_" => "LIST",
        ],
        'filter' => ['ID' => $rbook_id],
        'cache' => [
            'ttl' => 3600
        ],
    ]
)->fetchAll();
if (!$rbooks) {
    echo Json::encode([
        "status" => "error",
        "text" => "Не удалось получить список",
    ]);
    return;
}

$list = [
    0 => "Выбрать",
];
foreach ($rbooks as $element) {
    $list[$element["LIST_ID"]] = $element["LIST_VALUE"];
}

echo Json::encode([
    "status" => "success",
    "list" => $list,
]);

return;
