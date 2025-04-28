<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Web\Json,
    Bitrix\Main\Loader;

if(!$USER->isAdmin()) {
    LocalRedirect("/404.php");
}

$form_name = $_POST["form_name"] ?? FALSE;
if (!$form_name) {
    echo Json::encode([
        "status" => "error",
        "text" => "Некорректные входные данные",
    ]);
    return;
}
$oElement = new CIBlockElement();
$result = $oElement->add([
    "ACTIVE" => "Y",
    "IBLOCK_ID" => IBID_FORMS,
    "NAME" => $form_name,
//    "CODE" => Cutil::translit($form_name,"ru", ["replace_space"=>"-","replace_other"=>"-"]),
]);

if($result) {
    echo Json::encode([
        "status" => "success",
        "text" => $form_name,
    ]);
} else {
    echo Json::encode([
        "status" => "error",
        "text" => "Не удалось обновить форму",
    ]);
}

return;
