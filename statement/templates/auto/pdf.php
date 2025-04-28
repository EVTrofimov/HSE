<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

require $_SERVER["DOCUMENT_ROOT"].'/local/php_interface/lib/mpdf/vendor/autoload.php';

$FIELDS = $arResult['ORDER']['FORM_DATA'];
$FIELDS['TEMPLATE_PDF_HEADER_PATH'] = $arResult['TEMPLATE_PDF_HEADER_PATH'];
//dump($FIELDS);
ob_start();

require_once($arResult['TEMPLATE_PDF_PATH']);

$template_pdf = ob_get_contents();
ob_end_clean();

//echo($template_pdf);exit;

// Подключаем стили
ob_start();
require_once(TEMPLATE_PDF_STYLE_PATH);
$stylesheet = ob_get_contents();
ob_end_clean();

$mpdf = new \Mpdf\Mpdf();
$mpdf->charset_in = 'utf-8';
$mpdf->SetDisplayMode('fullpage');

$mpdf->WriteHTML($stylesheet,1);

$mpdf->WriteHTML($template_pdf);
$mpdf->Output($arResult['PDF_FILE_NAME'], 'I');
