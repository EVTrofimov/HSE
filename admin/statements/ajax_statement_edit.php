<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Web\Json,
    Bitrix\Main\Loader;

if(!$USER->isAdmin()) {
    LocalRedirect("/404.php");
}

$offer_id = intval($_POST["id"]) ?? FALSE;
$name = $_POST["name"] ?? FALSE;

if (!$offer_id OR !$name) {
    echo Json::encode([
        "status" => "error",
        "text" => "Некорректные входные данные",
    ]);
    return;
}

CIBlockElement::SetPropertyValuesEx(
    $offer_id, IBID_STATEMENTS_OFFERS,
    [
        "TEMPLATE_PDF_HEADER" => $template_pdf_header,
        "TEMPLATE_PDF_BODY" => $template_pdf_body,
    ]
);

echo Json::encode([
    "status" => "success",
    "text" => $name,
]);

return;
