<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Web\Json,
    Bitrix\Main\Loader;

if(!$USER->isAdmin()) {
    LocalRedirect("/404.php");
}

$form_id = intval($_POST["form_id"]) ?? FALSE;
$form_name = $_POST["form_name"] ?? FALSE;
$form_html = trim($_POST["form_html"]) ?? "";

if (!$form_id OR !$form_name) {
    echo Json::encode([
        "status" => "error",
        "text" => "Некорректные входные данные",
    ]);
    return;
}

$form_structure = !empty($form_html) ? getFormStructure($form_html) : "";

$oElement = new CIBlockElement();
// Обновляем имя результату, чтобы учесть в названии ID заказа
$result = $oElement->update($form_id, [
    "NAME" => $form_name,
    "DETAIL_TEXT" => $form_html,
    "PREVIEW_TEXT" => serialize($form_structure),
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
