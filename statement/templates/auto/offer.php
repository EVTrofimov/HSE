<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */
?>
<link href="https://cdn.jsdelivr.net/jquery.suggestions/17.2/css/suggestions.css" type="text/css" rel="stylesheet" />
<script type="text/javascript" src="https://cdn.jsdelivr.net/jquery.suggestions/17.2/js/jquery.suggestions.min.js"></script>


<?
$statement = $arResult['CURRENT_OFFER'];
$order = $arResult['ORDER'];
$form_structure = $arResult['FORM_STRUCTURE'];

$steps[1] = "Начало";
$steps[2] = "Ввод данных";

if ("auto" == $arResult["SERVICE_TYPE"] AND intval($statement["PRICE"]) !== 0) {
    $steps[3] = "Оплата";
    $steps[4] = "Завершение";
} else {
    $steps[3] = "Завершение";
}


if ($arResult['CODE'] !== $statement['CODE']) {
    $step_0_style = "display: flex;";
    $step_1_style = "display: none;";
} else {
    $step_0_style = "display: none;";
    $step_1_style = "display: flex;";
}

$step_2_style = $step_3_style = $step_4_style = "display: none;";
if ($order) {
    // Если не оплачен
    if($order['PAYED'] != 'Y') {
        $step_0_style = $step_1_style = $step_2_style = $step_4_style = "display: none;";
        $step_3_style = "display: flex;";
    } else {
        $step_0_style = $step_1_style = $step_2_style = $step_3_style = "display: none;";
        $step_4_style = "display: flex;";
    }
}

if (isset($_GET['edit'])) {
    $step_0_style = $step_1_style = $step_3_style = $step_4_style = "display: none;";
    $step_2_style = "display: flex;";
}

// Показать только блок редактирования
//$step_0_style = $step_1_style = $step_2_style = $step_3_style = $step_4_style = "display: none;";$step_2_style = "display: flex;";

$title = $statement['NAME'];
//if ($statement['CODE'] !== 'obshchaya') {
//    $title .= ' '.mb_strtolower($statement['NAME']);
//}

// Значения для тестирования
if ($USER->isAdmin()) {
    $TEST_DATA = [
        'EMAIL_FOR_ORDER' => 'kadirov.rust0@yandex.ru',
    ];
    foreach ($form_structure as $block_t => $block) {
        foreach ($block['FIELDS'] as $f_id => $f) {
            switch ($f['TYPE']){
                case 'date':
                    $TEST_DATA[$f['CODE']] = date('d.m.Y');
                    break;
                case 'email':
                    $TEST_DATA[$f['CODE']] = 'email@inargument.ru';
                    break;
                case 'file':
                case 'address':
                    $TEST_DATA[$f['CODE']] = '';
                    break;
                case 'judge_district_num':
                    $TEST_DATA[$f['CODE']] = '13';
                    break;
                case 'city':
                    $TEST_DATA[$f['CODE']] = 'Петрозаводск';
                    break;
                case 'text':
                    $TEST_DATA[$f['CODE']] = 'Какое-то текстовое значение';
                    break;
                case 'text_long':
                    $TEST_DATA[$f['CODE']] = 'Какой-то длинный текст. Какой-то длинный текст. Какой-то длинный текст. 
                    Какой-то длинный текст. Какой-то длинный текст. Какой-то длинный текст. Какой-то длинный текст.';
                    break;
                case 'inn':
                    $TEST_DATA[$f['CODE']] = '123456789012';
                    break;
                case 'phone':
                    $TEST_DATA[$f['CODE']] = '+7 (999) 111-22-33';
                    break;
                case 'passport_seria_number':
                    $TEST_DATA[$f['CODE']] = '123';
                    break;
                case 'passport_code':
                    $TEST_DATA[$f['CODE']] = '1234 123456';
                    break;
                case 'snils':
                    $TEST_DATA[$f['CODE']] = '12345678901';
                    break;
                case 'price':
                    $TEST_DATA[$f['CODE']] = '10000';
                    break;
                default:
                    $TEST_DATA[$f['CODE']] = "Значение по умолчанию для тестирования";
            }
        }
    }
    ?>
    <script type="text/javascript">
        let TEST_DATA = <?= CUtil::PhpToJSObject($TEST_DATA, false, true); ?>;
    </script>
<?
}
?>

<div class="page__inner step_block" data-step="0" style="<?=$step_0_style?>">
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
                <h1><?=$title?></h1>
            </div>

            <div class="about_text">
                <?
                echo htmlspecialcharsback($arResult['DETAIL_TEXT']);
                ?>
            </div>

            <div class="action_box">
                <div class="left">
                    <div class="price">
                        <div class="price_detail">
                            <p class="detail_info">Стоимость услуги:</p>
                                <?=
                                $statement['PRICE'] === 0 ?
                                    "Бесплатно" :
                                    CurrencyFormat($statement['PRICE'], "RUB")
                                ?>
                            </p>
                        </div>
                        <div class="price_detail">
                            <p class="detail_info">Срок предоставления:</p>
                            <p class="price_blue"><?=$arResult['DEADLINE_VALUE']?></p>
                        </div>
                    </div>
                </div>
                <div class="right">
                    <a class="btn btn_show_next_step" href="#"><?=$arResult['BTN_ORDER_TEXT']?></a>
                </div>
            </div>

            <?
            if ($arResult['ADVANTAGES_LIST']) {
                ?>
                <div class="descr_box">
                    <h3><?=$arResult['ADVANTAGES_TITLE']?></h3>
                    <div class="tile">
                        <?foreach ($arResult['ADVANTAGES_LIST'] as $al) {?>
                            <div class="tile__item">
                                <img src="<?=SITE_TEMPLATE_PATH?>/img/check.svg" alt="">
                                <p><?=$al?></p>
                            </div>
                        <?}?>
                    </div>
                </div>
            <?}?>

            <?
            if ($arResult['LINK_SERVICES_DO_AFTER']) {
                ?>
                <div class="descr_box">
                    <h3><?=$arResult['SERVICES_DO_AFTER_TITLE']?></h3>
                    <div class="serv_tile">
                        <?foreach ($arResult['LINK_SERVICES_DO_AFTER'] as $ls) {?>
                            <a href="<?=$ls['URL']?>" class="serv_tile__item">
                                <p><?=$ls['NAME']?></p>
                                <span class="icon-02"></span>
                            </a>
                        <?}?>
                    </div>
                </div>
            <?}?>
        </div>
    </section>

    <?
    if ($arResult['LINK_SERVICES_SIMILAR']) {
        ?>
        <section class="page__item">
            <div class="page__item_title">
                <h2 class="min_h2">Похожие услуги</h2>
            </div>
            <div class="page__item_body">
                <div class="catalog__box">
                    <?foreach ($arResult['LINK_SERVICES_SIMILAR'] as $ls) {?>
                        <a href="<?=$ls['URL']?>" class="catalog__box_item"><p><?=$ls['NAME']?></p><span class="icon-02"></span></a>
                    <?}?>
                </div>
            </div>
        </section>
    <?}?>
</div>

<div class="page__inner step_block" data-step="1" style="<?=$step_1_style?>">
    <section class="page__item">
        <div class="centered_group process_group">
            <div class="enter_box centered_group_box">
                <div class="enter_box__title">
                    <p><?=$arResult['STEPS_TITLE']?></p>
                </div>
                <div class="enter_box__body">
                    <div class="progress_mobile">
                        <div class="progress_title">
                            <p class="prog_num">Шаг 01<span></span></p>
                            <p><?=$arResult['STEP_1_TITLE']?></p>
                        </div>
                    </div>
                    <div class="progress" data-steps="<?=count($steps)?>">
                        <div class="progress_line">
                            <div class="progress_line__points">
                                <?foreach ($steps as $i => $s) {
                                    $cur = $i == 1 ? 'active_point' : '';
                                    ?>
                                    <div class="progress_line__points_item <?=$cur?>">
                                        <div class="num"><?=$i?></div>
                                        <p><?=$s?></p>
                                    </div>
                                <?}?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="enter_box__body no-top">
                    <?= htmlspecialcharsback($arResult['STEP_1_TEXT']);?>
                </div>

                <div class="enter_box__btns process_btns">
                    <div class="left">
                        <div class="price">
                            <div class="price_detail">
                                <p class="detail_info">Стоимость услуги:</p>
                                <p class="price_blue">
                                    <?=
                                    $statement['PRICE'] === 0 ?
                                        "Бесплатно" :
                                        CurrencyFormat($statement['PRICE'], "RUB")
                                    ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="right">
                        <a href="" class="btn btn_show_next_step">Начать</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<div class="page__inner step_block" data-step="2" style="<?=$step_2_style?>">
    <section class="page__item">
        <div class="centered_group process_group">
            <div class="enter_box centered_group_box">

                <?
                if ($USER->isAdmin()) {
                    ?>
                    <div style="text-align: center; margin-top: 50px;">
                        <button class="fill_test_values">Заполнить тестовыми значениями</button>
                    </div>
                    <?
                }
                ?>

                <div class="enter_box__title">
                    <p><?=$arResult['STEPS_TITLE']?></p>
                </div>
                <div class="enter_box__body">
                    <div class="progress_mobile">
                        <div class="progress_title">
                            <p class="prog_num">Шаг 02<span></span></p>
                            <p><?=$arResult['STEP_2_TITLE']?></p>
                        </div>
                    </div>
                    <div class="progress" data-steps="<?=count($steps)?>">
                        <div class="progress_line second">
                            <div class="progress_line__points">
                                <?foreach ($steps as $i => $s) {
                                    $cur = $i == 2 ? 'active_point' : '';
                                    ?>
                                    <div class="progress_line__points_item <?=$cur?>">
                                        <div class="num"><?=$i?></div>
                                        <p><?=$s?></p>
                                    </div>
                                <?}?>
                            </div>
                        </div>
                    </div>
                </div>

                <form id="statement-form" class="statement-form" method="post" enctype="multipart/form-data" novalidate>
                    <?=bitrix_sessid_post()?>
                    <? if($order) {?>
                        <input type="hidden" name="ORDER_ID" value="<?=$order['ID']?>">
                    <?}?>

                    <div class="enter_box__body">
                        <?
                        $q_name = "EMAIL_FOR_ORDER";
                        if (!$USER->IsAuthorized()) {
                        ?>
                            <div class="width_100">
                                <div class="big_form">
                                    <p class="semibold"><?= Loc::getMessage($q_name.'_TITLE') ?></p>
                                    <input
                                        id="ID_<?=$q_name?>"
                                        name="<?=$q_name?>"
                                        type="email"
                                        class="form__item"
                                        placeholder="<?= Loc::getMessage($q_name.'_PLACEHOLDER') ?>"
                                        required
                                        value=""
                                    >
                                </div>
                            </div>
                        <?
                        }
                        ?>
                    </div>

                    <?
                    //dump($order['FORM_DATA']);
                    ?>

                    <script>
                        let order_form_data = <?= Bitrix\Main\Web\Json::encode($order['FORM_DATA'])?>;
                    </script>
                    <?
                    $form_html = $statement["FORM_HTML"];
                    echo $form_html;
                    ?>

                    <div class="enter_box__agr">
                        <div class="agreement">
                            <label class="inline-label form__checkbox check">
                                <input class="form__checkbox_hidden" type="checkbox" name="checkbox-user-agreement" checked>
                                <span class="form__checkbox_indicator"></span>
                                <p>Я принимаю условия <a href="/oferta/" class="link link_black">Пользовательского соглашения</a></p>
                            </label>
                            <label class="inline-label form__checkbox check">
                                <input class="form__checkbox_hidden" type="checkbox" name="checkbox-personal" checked>
                                <span class="form__checkbox_indicator"></span>
                                <p>
                                    Я даю согласие ООО «Название» на обработку моих персональных данных
                                    в соответствии с Федеральным законом от 27.07.2006
                                    152 ФЗ «О персональных данных» на условиях и для целей, определенных
                                    <a href="/polit/" class="link link_black">Политикой конфиденциальности</a>
                                </p>
                            </label>
                        </div>
                    </div>

                    <div class="enter_box__btns process_btns">
                        <div class="left">
                            <p>
                                Проверьте данные перед формированием, чтобы исключить указание в Заявлении
                                некорректных данных
                            </p>
                            <div class="data_check">
                                <label class="inline-label form__checkbox check">
                                    <input class="form__checkbox_hidden" type="checkbox" name="checkbox-data-is-correct" checked>
                                    <span class="form__checkbox_indicator"></span>
                                    <p><?= Loc::getMessage('DATA_IS_CORRECT_TITLE') ?></p>
                                </label>
                            </div>
                        </div>
                        <div class="right">
                            <?
                            if('auto' == $arResult['SERVICE_TYPE']) {
                            ?>
                                <button type="button" class="btn btn_create_form">Продолжить</button>
                            <?
                            } else {
                                $btn_title = ("Y" == $order['PAYED']) ? "Сохранить" : "Сохранить и оплатить";
                            ?>
                                <button type="button" class="btn btn_create_form"><?=$btn_title?></button>
                            <?
                            }
                            ?>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>
</div>

<?
if ("Y" != $order['PAYED']) {
?>
<div class="page__inner step_block" data-step="3" style="<?=$step_3_style?>">
    <section class="page__item">
        <div class="centered_group process_group">

            <div class="enter_box centered_group_box">
                <div class="enter_box__title">
                    <p><?=$arResult['STEPS_TITLE']?></p>
                </div>
                <div class="enter_box__body">
                    <div class="progress_mobile">
                        <div class="progress_title">
                            <p class="prog_num">Шаг 03<span></span></p>
                            <p><?=$arResult['STEP_3_TITLE']?></p>
                        </div>
                    </div>
                    <div class="progress">
                        <div class="progress_line third">
                            <div class="progress_line__points">
                                <?foreach ($steps as $i => $s) {
                                    $cur = $i == 3 ? 'active_point' : '';
                                    ?>
                                    <div class="progress_line__points_item <?=$cur?>">
                                        <div class="num"><?=$i?></div>
                                        <p><?=$s?></p>
                                    </div>
                                <?}?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="enter_box__body">
                    <div class="enter_box__body_container">
                        <div class="upload_container">
                            <p class="semibold">Ваши документы почти готовы:</p>
                            <div class="added_files ready_files">
                                <?
                                // Возьмём url для оплаты из формы
                                $pattern = '/<a\s+(?:[^>]*?\s+)?href=(["\'])(.*?)\1/';
                                preg_match_all($pattern, $order['PAY_FORM'], $matches);
                                $payment_url = $matches[2][0];
                                foreach ($statement["TEMPLATES_HTML"] as $d) {?>
                                    <div class="added_files__item file_disabled">
                                        <span class="icon-01"></span>
                                        <a href="<?=$payment_url?>" class="uploaded_filename">
                                            <?= cut_string($d["NAME"]); ?>
                                        </a>

                                        <div class="hint">
                                            <p>Скачивание документа будет доступно после оплаты услуги</p>
                                        </div>
                                    </div>
                                    <?
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>

                <?
                if('auto' == $arResult['SERVICE_TYPE']) {
                ?>
                <div class="enter_box__body no-top">
                    <p class="semibold tile_title">Предварительный просмотр документа</p>
                    <p style="margin-bottom: 20px;">
                        После зачисления денежных средств вам будет доступна полная версия документа с подробной
                        инструкцией
                    </p>
                    <div class="preview">
                        <div class="preview__container">
                            <style>
                                <?require_once(TEMPLATE_PDF_STYLE_PATH);?>
                            </style>
                            <?
                            $FIELDS = [];
//                            foreach ($order['FORM_DATA'] as $f_id => $f) {
//                                $FIELDS[$f["NAME"]] = $f["VALUE"];
//                            }

                            ?>
                            <div class="pdf_file">
                                <br><br>
                                <?
                                $m = new Mustache_Engine(['entity_flags' => ENT_QUOTES]);
                                echo $m->render($statement["TEMPLATES_HTML"][0]["HEADER"], $FIELDS);
                                ?>
                                <p style="filter: blur(4px);">
                                    Sed ut perspiciatis, unde omnis iste natus error sit voluptatem accusantium doloremque
                                    laudantium, totam rem aperiam eaque ipsa, quae ab illo inventore veritatis et quasi
                                    architecto beatae vitae dicta sunt, explicabo. Nemo enim ipsam voluptatem, quia voluptas
                                    sit, aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos, qui ratione
                                    voluptatem sequi nesciunt, neque porro quisquam est, qui dolorem ipsum, quia dolor sit,
                                    amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt, ut
                                    labore et dolore magnam aliquam quaerat voluptatem. Ut enim ad minima veniam, quis nostrum
                                    exercitationem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi consequatur?
                                </p><br><br>
                                <p style="filter: blur(4px);">
                                    Quis autem vel eum iure reprehenderit, qui in ea voluptate velit esse, quam nihil molestiae
                                    consequatur, vel illum, qui dolorem eum fugiat, quo voluptas nulla pariatur? At vero eos
                                    et accusamus et iusto odio dignissimos ducimus, qui blanditiis praesentium voluptatum
                                    deleniti atque corrupti, quos dolores et quas molestias excepturi sint, obcaecati cupiditate
                                    non provident, similique sunt in culpa, qui officia deserunt mollitia animi, id est laborum
                                    et dolorum fuga. Et harum quidem rerum facilis est et expedita distinctio.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <?}?>

                <div class="enter_box__btns process_btns">
                    <div class="">
                        <a class="btn prev_step" href="javascript:void(0);">Назад</a>
                    </div>
                    <div class="step_pay">
                        <?= $order['PAY_FORM'] ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
<?} else {?>
<div class="page__inner step_block" data-step="4" style="<?=$step_4_style?>">
    <section class="page__item">
        <div class="centered_group process_group">
            <div class="enter_box centered_group_box">
                <div class="enter_box__title">
                    <p><?=$arResult['STEPS_TITLE']?></p>
                </div>
                <div class="enter_box__body">
                    <div class="progress_mobile">
                        <div class="progress_title">
                            <p class="prog_num">Шаг 04<span></span></p>
                            <p>Готово</p>
                        </div>
                    </div>
                    <div class="progress">
                        <div class="progress_line fourth">
                            <div class="progress_line__points">
                                <?foreach ($steps as $i => $s) {
                                    $cur = $i == 4 ? 'active_point' : '';
                                    ?>
                                    <div class="progress_line__points_item <?=$cur?>">
                                        <div class="num"><?=$i?></div>
                                        <p><?=$s?></p>
                                    </div>
                                <?}?>
                            </div>
                        </div>
                    </div>
                </div>

                <?
                if('auto' == $arResult['SERVICE_TYPE']) {
                    if ($order['FILES_ARCHIVE']) {?>
                        <div class="enter_box__body">
                            <div class="enter_box__body_container">
                                <div class="upload_container">
                                    <p class="semibold">Ваши документы:</p>
                                    <div class="added_files ready_files">
                                        <a class="added_files__item ready_file"
                                           href="/download/?order_id=<?=$order["ID"]?>&file_id=<?=$order["FILES_ARCHIVE"]?>" download="">
                                            <span class="icon-01"></span>
                                            <p class="uploaded_filename">
                                                <?= cut_string($arResult['PDF_FILE_NAME']); ?>
                                            </p>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="enter_box__body no-top">
                            <?=$arResult['STEP_4_TEXT']?>
                        </div>
                        <?
                    } else {?>
                        <div class="enter_box__body no-top">
                            <?= Loc::getMessage("ERROR_HREF_GENERATING");?>
                        </div>
                    <?
                    }
                } else {?>
                    <div class="enter_box__body no-top">
                        <?=$arResult['STEP_4_TEXT']?>
                    </div>
                <?
                }?>


                    <?
                    /*
                        <?
                        if($order['FORM_DATA']['COURT_RECEIVING_DATE']) {
                        $court_order_date = strtotime($order['FORM_DATA']['COURT_RECEIVING_DATE']);
                        $court_order_date_10 = strtotime('+10 day', $court_order_date);
                        ?>
                            <div class="tile steps_tile">
                                <div class="steps_tile__item">
                                    <p class="price_blue"><?=date("d.m.Y", $court_order_date)?></p>
                                    <p class="simple_p">День получения вашего судебного приказа</p>
                                </div>
                                <div class="steps_tile__item">
                                    <p class="price_blue"><?=date("d.m.Y",  $court_order_date_10)?></p>
                                    <p class="simple_p">Последний день подачи возражения</p>
                                </div>
                                <div class="steps_tile__item">
                                    <p class="price_blue"><?
                                        $left_days = round(($court_order_date_10-time()) / (60 * 60 * 24));
                                        echo max($left_days, 0);
                                    ?> дней</p>
                                    <p class="simple_p">Осталось на отправку документа</p>
                                </div>
                            </div>
                        <?
                        } else {
                        ?>
                            <p class="semibold tile_title">Вы не получали копию судебного приказа.</p>
                        <?}?>
                    </div>

                    <div class="enter_box__body">
                        <p class="medium tile_title">В случае пропуска срока подачи Возражения на судебный приказ,
                            к Вашему Возражению нужно приложить Заявление о восстановление процессуального срока.
                            Оформить его можно по ссылке:</p>
                        <a href="#" class="link link_arrow">Оформить заявление<span class="icon-02"></span></a>
                    </div>

                    <div class="enter_box__body">
                        <p class="base_p tile_title">
                            Дополнительно, здесь Вы можете ознакомиться с пошаговой инструкцией отправки
                            документов Почтой РФ:
                        </p>
                        <a href="/instructions/post/" class="link link_arrow">Инструкция по отправке письма<span class="icon-02"></span></a>
                    </div>
                    */?>

                <div class="enter_box__btns process_btns">
                    <div class="">
                        <a class="btn prev_step" href="javascript:void(0);">Назад</a>
                    </div>
                    <div class="right">
                        <a class="btn" href="/personal/">В мои дела</a>
                    </div>
                </div>

                    <!--                <div class="enter_box__btns process_btns">-->
<!--                    <div class="left text_description">-->
<!--                        <p class="no_bottom">Также вы можете задать свой вопрос, связанный с совершением других юридических действий</p>-->
<!--                    </div>-->
<!--                    <div class="right">-->
<!--                        <button class="btn">Задать вопрос</button>-->
<!--                    </div>-->
<!--                </div>-->
            </div>
        </div>
    </section>
</div>
<?
}
?>

<script type="text/javascript">
    let params = <?=Bitrix\Main\Web\Json::encode(['signedParameters'=>$this->getComponent()->getSignedParameters()])?>;
    console.log(params);
    let MAX_CHILDRENS = <?= MAX_CHILDRENS ?>;
    let SITE_TEMPLATE_PATH = '<?= SITE_TEMPLATE_PATH ?>';
    let DADATA_TOKEN = "9d5293235a6541e00e33477059c5e2cecb059f90";
</script>